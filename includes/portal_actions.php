<?php
function submit_enrollment($student)
{
    global $conn;
    if (!$student) { throw new DomainException('Ask your administrator to link a student record first.'); }
    $year = post_text('academic_year');
    $semester = post_text('semester');
    if (!preg_match('/^(\d{4})-(\d{4})$/D', $year, $match) || (int)$match[2] !== (int)$match[1] + 1) { throw new DomainException('Use consecutive years, for example 2026-2027.'); }
    if (!in_array($semester, ['First semester','Second semester','Summer'], true)) { throw new DomainException('Choose a semester.'); }
    $section_id = filter_var($_POST['section_id'] ?? null, FILTER_VALIDATE_INT);
    $section = $section_id ? row('SELECT * FROM sections_tbl WHERE section_ID = ?', [$section_id]) : null;
    if (!$section || $section['section_course'] !== $student['course'] || $section['yr_level'] !== $student['yearlvl']) { throw new DomainException('Choose a section for your course and year level.'); }
    $selection = $_POST['subjects'] ?? [];
    if (!is_array($selection) || !$selection || count($selection) > 30) { throw new DomainException('Select between 1 and 30 subjects.'); }
    $ids = [];
    foreach ($selection as $id) {
        if (!is_string($id) || !ctype_digit($id) || (int)$id < 1) { throw new DomainException('Choose valid subjects.'); }
        $ids[] = (int)$id;
    }
    $ids = array_unique($ids);
    $subjects = rows('SELECT * FROM subjecttbl WHERE ID IN (' . implode(',',array_fill(0,count($ids),'?')) . ') AND course_con = ?', array_merge($ids,[$student['course']]));
    if (count($subjects) !== count($ids)) { throw new DomainException('One or more subjects are unavailable for your course.'); }
    foreach ($subjects as $subject) {
        if (!is_numeric($subject['numofunits']) || (float)$subject['numofunits'] <= 0 || (float)$subject['numofunits'] > 30) { throw new DomainException('A selected subject has invalid units. Ask your administrator to update it.'); }
    }
    // Detect overlapping classes in the selected section before accepting a request.
    $classes = rows('SELECT s.*, t.day FROM sched_tbl s JOIN subjecttbl t ON t.ID = s.subject_ID WHERE s.section_ID = ? AND s.subject_ID IN (' . implode(',',array_fill(0,count($ids),'?')) . ')', array_merge([$section_id],$ids));
    foreach ($classes as $a=>$first) {
        foreach ($classes as $b=>$second) {
            if ($a < $b && $first['day'] === $second['day'] && $first['time_from'] < $second['time_to'] && $first['time_to'] > $second['time_from']) { throw new DomainException('The selected subjects have overlapping classes. Choose a different combination.'); }
        }
    }
    $conn->begin_transaction();
    try {
        $old = row('SELECT * FROM enrollment_requests WHERE student_id = ? AND academic_year = ? AND semester = ? FOR UPDATE',[$student['ID'],$year,$semester]);
        if ($old && in_array($old['status'],['pending','approved'],true)) { throw new DomainException('You already have a pending or approved enrollment for this term.'); }
        if ($old) {
            $id = $old['id'];
            db("UPDATE enrollment_requests SET section_id = ?, status = 'pending', notes = '', created_at = CURRENT_TIMESTAMP WHERE id = ?",[$section_id,$id]);
            db('DELETE FROM enrollment_items WHERE request_id = ?',[$id]);
        } else {
            db('INSERT INTO enrollment_requests (student_id, section_id, academic_year, semester) VALUES (?, ?, ?, ?)',[$student['ID'],$section_id,$year,$semester]);
            $id = $conn->insert_id;
        }
        foreach ($subjects as $subject) {
            db('INSERT INTO enrollment_items (request_id, subject_id, subject_code, subject_title, units) VALUES (?, ?, ?, ?, ?)',[$id,$subject['ID'],$subject['subjectcode'],$subject['subjecttitle'],$subject['numofunits']]);
        }
        $conn->commit();
        return $id;
    } catch (Throwable $error) { $conn->rollback(); throw $error; }
}
function decide_enrollment($id)
{
    global $conn;
    $decision = post_text('decision');
    $notes = post_text('notes');
    if (!in_array($decision,['approved','rejected'],true)) { throw new DomainException('Choose an enrollment decision.'); }
    if (mb_strlen($notes) > 1000) { throw new DomainException('Keep notes under 1,000 characters.'); }
    if ($decision === 'rejected' && $notes === '') { throw new DomainException('Add a note explaining what the student needs to change.'); }
    $conn->begin_transaction();
    try {
        $request = row('SELECT * FROM enrollment_requests WHERE id = ? FOR UPDATE',[$id]);
        if (!$request || $request['status'] !== 'pending') { throw new DomainException('Only pending requests can be reviewed.'); }
        if ($decision === 'approved') {
            $section = row('SELECT * FROM sections_tbl WHERE section_ID = ? FOR UPDATE',[$request['section_id']]);
            $student = row('SELECT * FROM studenttbl WHERE ID = ?',[$request['student_id']]);
            if (!$section || !$student || $student['course'] !== $section['section_course'] || $student['yearlvl'] !== $section['yr_level']) { throw new DomainException('The student and section must still have matching course and year level.'); }
            if (total("SELECT COUNT(*) FROM enrollment_requests WHERE section_id = ? AND academic_year = ? AND semester = ? AND status = 'approved'",[$request['section_id'],$request['academic_year'],$request['semester']]) >= (int)$section['max_num']) { throw new DomainException('This section has reached its maximum capacity for the term.'); }
            if (total('SELECT COUNT(*) FROM enrollment_items i LEFT JOIN subjecttbl s ON s.ID = i.subject_id WHERE i.request_id = ? AND (s.ID IS NULL OR s.course_con <> ?)',[$id,$student['course']])) { throw new DomainException('A subject is no longer available for this course.'); }
        }
        db('UPDATE enrollment_requests SET status = ?, notes = ? WHERE id = ?',[$decision,$notes,$id]);
        $conn->commit();
    } catch (Throwable $error) { $conn->rollback(); throw $error; }
}
function handle_portal_post()
{
    global $page, $user, $student, $modules, $portal_role;
    if (!valid_csrf()) { http_response_code(403); throw new DomainException('Your form expired. Reload the page and try again.'); }
    $action = post_text('action');
    $id = max(0,(int)post_text('id'));
    if ($portal_role === 'admin' && isset($modules[$page]) && $action === 'save') {
        save_record($page,$id);
        return ['?page='.$page, ucfirst($modules[$page]['singular']).' saved.'];
    }
    if ($portal_role === 'admin' && isset($modules[$page]) && $action === 'delete') {
        delete_record($page,$id);
        return ['?page='.$page, 'Record deleted.'];
    }
    if ($portal_role === 'admin' && $page === 'enrollments' && $action === 'decide') {
        decide_enrollment($id);
        return ['?page=enrollments&view='.$id,'Enrollment decision saved.'];
    }
    if ($portal_role === 'student' && $page === 'enroll' && $action === 'enroll') {
        $id = submit_enrollment($student);
        return ['?page=enrollments&view='.$id,'Enrollment request submitted for review.'];
    }
    if ($portal_role === 'student' && $page === 'enrollments' && $action === 'withdraw') {
        $result = db("UPDATE enrollment_requests SET status = 'withdrawn' WHERE id = ? AND student_id = ? AND status = 'pending'",[$id,$student['ID'] ?? 0]);
        if (!$result->affected_rows) { throw new DomainException('Only your pending requests can be withdrawn.'); }
        return ['?page=enrollments&view='.$id,'Enrollment request withdrawn.'];
    }
    if ($portal_role === 'student' && $page === 'profile' && $action === 'profile') {
        if (!$student) { throw new DomainException('No student record is linked to your account.'); }
        $editable = array_intersect_key($modules['students']['fields'],array_flip(['emailaddress','contact','address','guardianname','fathername','mothername']));
        $values = read_fields($editable);
        db('UPDATE studenttbl SET '.implode(',',array_map(fn($name)=>'`'.$name.'` = ?',array_keys($values))).' WHERE ID = ?',array_merge(array_values($values),[$student['ID']]));
        return ['?page=profile','Contact information saved.'];
    }
    if ($page === 'settings' && $action === 'password') {
        $current = is_string($_POST['current_password'] ?? null) ? $_POST['current_password'] : '';
        $new = is_string($_POST['new_password'] ?? null) ? $_POST['new_password'] : '';
        $confirm = is_string($_POST['confirm_password'] ?? null) ? $_POST['confirm_password'] : '';
        if (time() < ($_SESSION['password_retry_after'] ?? 0)) { throw new DomainException('Wait a few seconds before trying again.'); }
        $account = row('SELECT password_hash FROM auth_users WHERE id = ?',[$user['id']]);
        if (!password_verify($current,$account['password_hash'])) { $_SESSION['password_retry_after'] = time()+5; throw new DomainException('Your current password is incorrect.'); }
        if (strlen($new) < 8 || strlen($new) > 72) { throw new DomainException('Use a new password of 8–72 bytes.'); }
        if ($new !== $confirm) { throw new DomainException('The new passwords do not match.'); }
        db('UPDATE auth_users SET password_hash = ? WHERE id = ?',[password_hash($new,PASSWORD_DEFAULT),$user['id']]);
        session_regenerate_id(true);
        return ['?page=settings','Password updated.'];
    }
    http_response_code(400);
    throw new DomainException('This action is not available on this page.');
}
