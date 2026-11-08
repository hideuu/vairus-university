<?php
function db($sql, $params = [])
{
    global $conn;
    $statement = $conn->prepare($sql);
    if ($params) { $statement->bind_param(str_repeat('s', count($params)), ...$params); }
    $statement->execute();
    return $statement;
}
function rows($sql, $params = []) { return db($sql, $params)->get_result()->fetch_all(MYSQLI_ASSOC); }
function row($sql, $params = []) { return db($sql, $params)->get_result()->fetch_assoc(); }
function total($sql, $params = []) { return (int) db($sql, $params)->get_result()->fetch_row()[0]; }
function field($label, $type = 'text', $required = false, $max = 100, $options = [])
{
    return compact('label', 'type', 'required', 'max', 'options');
}
function portal_modules()
{
    return [
        'students' => ['title'=>'Students', 'singular'=>'student', 'table'=>'studenttbl', 'pk'=>'ID', 'description'=>'Keep student records and contact information up to date.', 'search'=>['studnum','fname','lname','course'], 'columns'=>['studnum'=>'Student number','fname'=>'First name','lname'=>'Last name','course'=>'Course','yearlvl'=>'Year level'], 'fields'=>[
            'studnum'=>field('Student number','text',true,64), 'fname'=>field('First name','text',true), 'minitial'=>field('Middle initial'), 'lname'=>field('Last name','text',true),
            'course'=>field('Course','course',true), 'yearlvl'=>field('Year level','select',true,100,['1'=>'First year','2'=>'Second year','3'=>'Third year','4'=>'Fourth year','5'=>'Fifth year']),
            'emailaddress'=>field('Email address','email',false,254), 'contact'=>field('Contact number','tel',false,32), 'address'=>field('Address','textarea',false,500),
            'birthdate'=>field('Date of birth','date'), 'gender'=>field('Gender','select',false,11,['female'=>'Female','male'=>'Male','other'=>'Other',''=>'Prefer not to say']), 'birthplace'=>field('Place of birth'), 'civilstatus'=>field('Civil status','select',false,100,['single'=>'Single','married'=>'Married','other'=>'Other']),
            'fathername'=>field('Father’s name'), 'mothername'=>field('Mother’s name'), 'guardianname'=>field('Guardian’s name'),
            'elemschool'=>field('Elementary school'), 'dategradelem'=>field('Elementary graduation'), 'hsschool'=>field('High school'), 'datehsgrad'=>field('High school graduation'), 'lastgrad'=>field('Previous school'), 'datelastgrad'=>field('Previous graduation'), 'addresslastgrad'=>field('Previous school address'),
        ]],
        'courses'=>['title'=>'Courses','singular'=>'course','table'=>'courses_tbl','pk'=>'course_ID','description'=>'Manage the programs available to your students.','search'=>['course_title','course_desc'],'columns'=>['course_title'=>'Course','course_desc'=>'Description'],'fields'=>['course_title'=>field('Course title','text',true),'course_desc'=>field('Description','textarea',false,5000)]],
        'subjects'=>['title'=>'Subjects','singular'=>'subject','table'=>'subjecttbl','pk'=>'ID','description'=>'Build the subject catalog and define teaching details.','search'=>['subjectcode','subjecttitle','course_con'],'columns'=>['subjectcode'=>'Code','subjecttitle'=>'Subject','numofunits'=>'Units','course_con'=>'Course','day'=>'Days'],'fields'=>[
            'subjectcode'=>field('Subject code','text',true), 'subjecttitle'=>field('Subject title','text',true), 'numofunits'=>field('Units','units',true), 'course_con'=>field('Course','course',true), 'description'=>field('Description','textarea'), 'instructor'=>field('Instructor name'), 'room'=>field('Room'), 'day'=>field('Class days','select',false,100,['Monday'=>'Monday','Tuesday'=>'Tuesday','Wednesday'=>'Wednesday','Thursday'=>'Thursday','Friday'=>'Friday','Saturday'=>'Saturday','Sunday'=>'Sunday']), 'time'=>field('Time description'), 'islab'=>field('Laboratory subject','checkbox'), 'istutorial'=>field('Tutorial subject','checkbox'),
        ]],
        'sections'=>['title'=>'Sections','singular'=>'section','table'=>'sections_tbl','pk'=>'section_ID','description'=>'Organize classes by course, year, and capacity.','search'=>['section_name','section_course','yr_level'],'columns'=>['section_name'=>'Section','section_course'=>'Course','yr_level'=>'Year level','max_num'=>'Capacity','min_num'=>'Minimum students'],'fields'=>[
            'section_name'=>field('Section name','text',true), 'section_course'=>field('Course','course',true), 'yr_level'=>field('Year level','select',true,100,['1'=>'First year','2'=>'Second year','3'=>'Third year','4'=>'Fourth year','5'=>'Fifth year']), 'max_num'=>field('Maximum students','number',true), 'min_num'=>field('Minimum students','number',true),
        ]],
        'schedules'=>['title'=>'Schedules','singular'=>'schedule','table'=>'sched_tbl','pk'=>'sched_ID','description'=>'Assign subjects to sections, rooms, and class times.','search'=>['sched_ID'],'columns'=>['section_ID'=>'Section','subject_ID'=>'Subject','time_from'=>'Starts','time_to'=>'Ends','room_ID'=>'Room number'],'fields'=>[
            'section_ID'=>field('Section','section',true), 'subject_ID'=>field('Subject','subject',true), 'time_from'=>field('Start time','time',true), 'time_to'=>field('End time','time',true), 'room_ID'=>field('Room number','number',true),
        ]],
        'users'=>['title'=>'Accounts','singular'=>'account','table'=>'auth_users','pk'=>'id','description'=>'Create secure accounts and link students to their records.','search'=>['username','full_name','role'],'columns'=>['full_name'=>'Full name','username'=>'Username','role'=>'Role','active'=>'Access'],'fields'=>[
            'full_name'=>field('Full name','text',true), 'username'=>field('Username','text',true,64), 'role'=>field('Role','select',true,20,['admin'=>'Administrator','student'=>'Student']), 'student_id'=>field('Linked student (required for student accounts)','student'), 'password'=>field('Password (leave blank to keep current)','password',false,72), 'active'=>field('Account active','checkbox'),
        ]],
    ];
}
function choices($type)
{
    if ($type === 'course') { return array_column(rows('SELECT course_title FROM courses_tbl ORDER BY course_title'), 'course_title', 'course_title'); }
    if ($type === 'section') { return array_column(rows('SELECT section_ID, CONCAT(section_name, \' / \', section_course) AS label FROM sections_tbl ORDER BY section_name'), 'label', 'section_ID'); }
    if ($type === 'subject') { return array_column(rows('SELECT ID, CONCAT(subjectcode, \' — \', subjecttitle) AS label FROM subjecttbl ORDER BY subjectcode'), 'label', 'ID'); }
    if ($type === 'student') { return array_column(rows('SELECT ID, CONCAT(studnum, \' — \', fname, \' \', lname) AS label FROM studenttbl ORDER BY lname'), 'label', 'ID'); }
    return [];
}
function post_text($name)
{
    return is_string($_POST[$name] ?? null) ? trim($_POST[$name]) : '';
}
function read_fields($fields)
{
    $values = [];
    foreach ($fields as $name=>$spec) {
        $value = $spec['type'] === 'checkbox' ? (isset($_POST[$name]) && $_POST[$name] === '1' ? '1' : '0') : post_text($name);
        if ($spec['required'] && $value === '') { throw new DomainException($spec['label'] . ' is required.'); }
        if (mb_strlen($value) > $spec['max']) { throw new DomainException($spec['label'] . ' is too long.'); }
        if ($value !== '') {
            if (in_array($spec['type'], ['course','section','subject','student'], true) && !array_key_exists($value, choices($spec['type']))) { throw new DomainException('Choose a valid ' . strtolower($spec['label']) . '.'); }
            if ($spec['type'] === 'select' && !array_key_exists($value, $spec['options'])) { throw new DomainException('Choose a valid ' . strtolower($spec['label']) . '.'); }
            if ($spec['type'] === 'email' && !filter_var($value, FILTER_VALIDATE_EMAIL)) { throw new DomainException('Enter a valid email address.'); }
            if ($spec['type'] === 'number' && (!ctype_digit($value) || (int)$value > 1000000)) { throw new DomainException($spec['label'] . ' must be a nonnegative whole number.'); }
            if ($spec['type'] === 'units' && (!is_numeric($value) || (float)$value <= 0 || (float)$value > 30 || (float)$value * 10 !== floor((float)$value * 10))) { throw new DomainException('Units must be between 0.1 and 30, with at most one decimal place.'); }
            if ($spec['type'] === 'time' && !preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/D', $value)) { throw new DomainException('Enter a valid time.'); }
            if ($spec['type'] === 'date') {
                $date = DateTime::createFromFormat('!Y-m-d', $value);
                if (!$date || $date->format('Y-m-d') !== $value || $value > date('Y-m-d')) { throw new DomainException('Enter a valid date of birth.'); }
            }
        }
        $values[$name] = $value;
    }
    return $values;
}
function save_record($module, $id)
{
    global $modules, $user, $conn;
    $spec = $modules[$module];
    $old = $id ? row('SELECT * FROM `' . $spec['table'] . '` WHERE `' . $spec['pk'] . '` = ?', [$id]) : null;
    if ($id && !$old) { throw new DomainException('This record no longer exists.'); }
    $values = read_fields($spec['fields']);
    $unique = ['students'=>'studnum','courses'=>'course_title','subjects'=>'subjectcode','sections'=>'section_name','users'=>'username'];
    if (isset($unique[$module])) {
        $column = $unique[$module];
        if (total('SELECT COUNT(*) FROM `' . $spec['table'] . '` WHERE `' . $column . '` = ? AND `' . $spec['pk'] . '` <> ?', [$values[$column], $id])) { throw new DomainException($spec['fields'][$column]['label'] . ' is already in use.'); }
    }
    if ($module === 'students' && !$id) { $values['accountID'] = ''; }
    if ($module === 'students' && $old && ($old['course'] !== $values['course'] || $old['yearlvl'] !== $values['yearlvl']) && total("SELECT COUNT(*) FROM enrollment_requests WHERE student_id = ? AND status IN ('pending','approved')",[$id])) { throw new DomainException('The student has a pending or approved enrollment. Keep the current course and year level.'); }
    if ($module === 'sections' && ((int)$values['max_num'] < 1 || (int)$values['min_num'] > (int)$values['max_num'])) { throw new DomainException('Capacity must be at least one and no smaller than the minimum.'); }
    if ($module === 'sections' && $old) {
        if (($old['section_course'] !== $values['section_course'] || $old['yr_level'] !== $values['yr_level']) && (total('SELECT COUNT(*) FROM sched_tbl WHERE section_ID = ?',[$id]) || total('SELECT COUNT(*) FROM enrollment_requests WHERE section_id = ?',[$id]))) { throw new DomainException('This section is in use. Keep its course and year level.'); }
        $largest = row("SELECT COUNT(*) AS students FROM enrollment_requests WHERE section_id = ? AND status = 'approved' GROUP BY academic_year, semester ORDER BY students DESC LIMIT 1",[$id]);
        if ($largest && (int)$values['max_num'] < (int)$largest['students']) { throw new DomainException('Capacity cannot be lower than the number of approved students.'); }
    }
    if ($module === 'subjects' && $old && ($old['course_con'] !== $values['course_con'] || $old['day'] !== $values['day']) && total('SELECT COUNT(*) FROM sched_tbl WHERE subject_ID = ?',[$id])) { throw new DomainException('This subject has assigned classes. Remove its schedules before changing its course or day.'); }
    if ($module === 'schedules') {
        $section = row('SELECT * FROM sections_tbl WHERE section_ID = ?', [$values['section_ID']]);
        $subject = row('SELECT * FROM subjecttbl WHERE ID = ?', [$values['subject_ID']]);
        if ($section['section_course'] !== $subject['course_con']) { throw new DomainException('The subject and section must belong to the same course.'); }
        if ($values['time_from'] >= $values['time_to']) { throw new DomainException('End time must be after start time.'); }
        if ((int)$values['room_ID'] < 1) { throw new DomainException('Enter a positive room number.'); }
        if ($subject['day'] === '') { throw new DomainException('Set the subject’s class day before scheduling it.'); }
        if (total('SELECT COUNT(*) FROM sched_tbl s JOIN subjecttbl t ON t.ID = s.subject_ID WHERE s.sched_ID <> ? AND t.day = ? AND s.time_from < ? AND s.time_to > ? AND (s.section_ID = ? OR s.room_ID = ?)', [$id,$subject['day'],$values['time_to'],$values['time_from'],$values['section_ID'],$values['room_ID']])) { throw new DomainException('This section or room already has a class at that time.'); }
        $values['Instructor_ID'] = $old['Instructor_ID'] ?? '0';
    }
    if ($module === 'users') {
        if (!preg_match('/^[A-Za-z0-9_.-]{3,64}$/D', $values['username'])) { throw new DomainException('Username must be 3–64 letters, numbers, dots, underscores, or hyphens.'); }
        if ((int)$id === (int)$user['id'] && ($values['role'] !== 'admin' || $values['active'] !== '1')) { throw new DomainException('You cannot remove your own administrator access.'); }
        if ($values['role'] === 'student' && $values['student_id'] === '') { throw new DomainException('Link a student record to this student account.'); }
        $values['student_id'] = $values['role'] === 'student' ? $values['student_id'] : null;
        if ($values['student_id'] && total('SELECT COUNT(*) FROM auth_users WHERE student_id = ? AND id <> ?', [$values['student_id'],$id])) { throw new DomainException('That student already has an account.'); }
        if ($old && $old['student_id'] && (string)$old['student_id'] !== (string)$values['student_id']) { throw new DomainException('An existing student account cannot be reassigned. Create a separate account.'); }
        $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
        if (!$id && $password === '') { throw new DomainException('A password is required for a new account.'); }
        unset($values['password']);
        if ($password !== '') {
            if (strlen($password) < 8 || strlen($password) > 72) { throw new DomainException('Use a password of 8–72 bytes.'); }
            $values['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
        }
    }
    // Avoid silently breaking existing course-name relationships on rename.
    if ($module === 'courses' && $old && $old['course_title'] !== $values['course_title'] && (total('SELECT COUNT(*) FROM studenttbl WHERE course = ?', [$old['course_title']]) || total('SELECT COUNT(*) FROM subjecttbl WHERE course_con = ?', [$old['course_title']]) || total('SELECT COUNT(*) FROM sections_tbl WHERE section_course = ?', [$old['course_title']]))) { throw new DomainException('This course is in use. Keep its title or update its linked records first.'); }
    $columns = array_keys($values);
    if ($id) {
        db('UPDATE `' . $spec['table'] . '` SET ' . implode(', ', array_map(fn($column)=>'`'.$column.'` = ?', $columns)) . ' WHERE `' . $spec['pk'] . '` = ?', array_merge(array_values($values), [$id]));
    } else {
        db('INSERT INTO `' . $spec['table'] . '` (`' . implode('`,`', $columns) . '`) VALUES (' . implode(',', array_fill(0,count($values),'?')) . ')', array_values($values));
        $id = $conn->insert_id;
    }
    return $id;
}
function delete_record($module, $id)
{
    global $modules;
    $spec = $modules[$module];
    $existing = row('SELECT * FROM `' . $spec['table'] . '` WHERE `' . $spec['pk'] . '` = ?', [$id]);
    if (!$existing) { throw new DomainException('This record no longer exists.'); }
    $used = false;
    if ($module === 'students') { $used = total('SELECT COUNT(*) FROM auth_users WHERE student_id = ?',[$id]) || total('SELECT COUNT(*) FROM enrollment_requests WHERE student_id = ?',[$id]); }
    if ($module === 'subjects') { $used = total('SELECT COUNT(*) FROM enrollment_items WHERE subject_id = ?',[$id]) || total('SELECT COUNT(*) FROM sched_tbl WHERE subject_ID = ?',[$id]); }
    if ($module === 'sections') { $used = total('SELECT COUNT(*) FROM enrollment_requests WHERE section_id = ?',[$id]) || total('SELECT COUNT(*) FROM sched_tbl WHERE section_ID = ?',[$id]); }
    if ($module === 'courses') { $title=$existing['course_title']; $used=total('SELECT COUNT(*) FROM studenttbl WHERE course = ?',[$title]) || total('SELECT COUNT(*) FROM subjecttbl WHERE course_con = ?',[$title]) || total('SELECT COUNT(*) FROM sections_tbl WHERE section_course = ?',[$title]); }
    if ($module === 'users' || $used) { throw new DomainException('This record is in use and cannot be deleted.'); }
    db('DELETE FROM `' . $spec['table'] . '` WHERE `' . $spec['pk'] . '` = ?', [$id]);
}
