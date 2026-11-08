<?php
function icon($name)
{
    $paths = [
        'dashboard'=>'M3 3h7v7H3z M14 3h7v7h-7z M3 14h7v7H3z M14 14h7v7h-7z',
        'enrollments'=>'M7 3h10v4H7z M7 5H4v16h16V5h-3 M8 12h8 M8 16h5',
        'students'=>'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2 M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8 M22 21v-2a4 4 0 0 0-3-3.87 M16 3.13a4 4 0 0 1 0 7.75',
        'courses'=>'M3 4h7l2 2 2-2h7v15h-7l-2 2-2-2H3z M12 6v15',
        'subjects'=>'M5 3h14v18H5z M8 7h8 M8 11h8 M8 15h5',
        'sections'=>'M3 5h18v14H3z M3 10h18 M10 10v9',
        'schedules'=>'M4 5h16v16H4z M8 3v4 M16 3v4 M4 10h16 M8 14h2 M14 14h2 M8 17h2',
        'users'=>'M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8 M4 21v-2a6 6 0 0 1 6-6h4a6 6 0 0 1 6 6v2',
        'settings'=>'M12 8a4 4 0 1 0 0 8 4 4 0 0 0 0-8 M12 2v3 M12 19v3 M2 12h3 M19 12h3 M5 5l2 2 M17 17l2 2 M5 19l2-2 M17 7l2-2',
        'logout'=>'M9 4H4v16h5 M10 12h11 M17 8l4 4-4 4',
        'menu'=>'M4 6h16 M4 12h16 M4 18h16',
        'plus'=>'M12 5v14 M5 12h14', 'search'=>'M10 17a7 7 0 1 0 0-14 7 7 0 0 0 0 14 M15 15l6 6',
        'arrow'=>'M5 12h14 M13 6l6 6-6 6', 'check'=>'M5 12l4 4L19 6',
        'info'=>'M12 8v.01 M12 11v6 M12 22a10 10 0 1 0 0-20 10 10 0 0 0 0 20',
        'print'=>'M6 8V3h12v5 M6 17H3V8h18v9h-3 M6 14h12v7H6z',
        'reports'=>'M5 3h14v18H5z M8 16v-3 M12 16V8 M16 16v-5',
    ];
    $key = ['enroll'=>'plus','profile'=>'users'][$name] ?? $name;
    return '<svg class="icon" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="'.($paths[$key] ?? $paths['subjects']).'"/></svg>';
}
function csrf_input() { return '<input type="hidden" name="csrf_token" value="'.escape_html($_SESSION['csrf_token']).'">'; }
function portal_nav_link($key,$label,$current)
{
    return '<a href="?page='.escape_html($key).'"'.($key===$current?' aria-current="page"':'').'>'.icon($key).'<span>'.escape_html($label).'</span></a>';
}
function badge($value)
{
    $allowed = ['pending','approved','rejected','withdrawn','active','disabled'];
    $class = in_array($value,$allowed,true) ? $value : 'neutral';
    return '<span class="badge badge-'.$class.'">'.escape_html(ucfirst($value)).'</span>';
}
function page_heading($title,$description,$action='')
{
    echo '<div class="page-heading"><div><h1>'.escape_html($title).'</h1><p>'.escape_html($description).'</p></div>'.$action.'</div>';
}
function empty_state($title,$description,$action='')
{
    echo '<div class="empty-state">'.icon('subjects').'<h2>'.escape_html($title).'</h2><p>'.escape_html($description).'</p>'.$action.'</div>';
}
function link_button($href,$label,$primary=true)
{
    return '<a class="button'.($primary ? ' button-primary' : ' button-secondary').'" href="'.escape_html($href).'">'.escape_html($label).'</a>';
}
function record_cell($value)
{
    $value = (string) $value;
    if (mb_strlen($value) > 160) {
        return '<details class="cell-details"><summary>'.escape_html(mb_substr($value,0,100)).'…<span class="visually-hidden"> Expand full text</span></summary><p>'.nl2br(escape_html($value)).'</p></details>';
    }
    return '<span class="cell-text">'.escape_html($value !== '' ? $value : '—').'</span>';
}
function render_field($name,$spec,$value='')
{
    $type=$spec['type']; $required=$spec['required'] ? ' required' : '';
    $id='field-'.$name;
    echo '<div class="input-group'.($type === 'textarea' ? ' field-wide' : '').'">';
    if ($type === 'checkbox') {
        echo '<label class="checkbox-label" for="'.$id.'"><input id="'.$id.'" type="checkbox" name="'.escape_html($name).'" value="1" '.((string)$value==='1'?'checked':'').'>'.escape_html($spec['label']).'</label>';
    } else {
        echo '<label for="'.$id.'">'.escape_html($spec['label']).($spec['required'] ? '<span class="required-mark" aria-hidden="true"> *</span>' : '').'</label>';
        if (in_array($type,['select','course','section','subject','student'],true)) {
            $options=$type === 'select' ? $spec['options'] : choices($type);
            echo '<select id="'.$id.'" name="'.escape_html($name).'"'.$required.'><option value="">Select an option</option>';
            if ($value !== '' && $value !== null && !array_key_exists((string)$value,$options)) { echo '<option selected value="'.escape_html($value).'">'.escape_html($value).' (existing value)</option>'; }
            foreach ($options as $key=>$label) { if ((string)$key==='') { continue; } echo '<option value="'.escape_html($key).'"'.((string)$key===(string)$value?' selected':'').'>'.escape_html($label).'</option>'; }
            echo '</select>';
        } elseif ($type === 'textarea') {
            echo '<textarea rows="3" id="'.$id.'" name="'.escape_html($name).'" maxlength="'.$spec['max'].'"'.$required.'>'.escape_html($value).'</textarea>';
        } else {
            $html_type=$type === 'units' ? 'number' : $type;
            $extra=$type === 'number' ? ' min="0" max="1000000" step="1"' : ($type === 'units' ? ' min="0.1" max="30" step="0.1"' : '');
            if ($type === 'password') { $extra.=' autocomplete="new-password"'; $value=''; }
            echo '<input id="'.$id.'" name="'.escape_html($name).'" type="'.escape_html($html_type).'" maxlength="'.$spec['max'].'" value="'.escape_html($value).'"'.$required.$extra.'>';
        }
    }
    echo '</div>';
}
function render_records($module)
{
    global $modules;
    $spec=$modules[$module];
    $editing=isset($_GET['new']) || isset($_GET['edit']) || ($_SERVER['REQUEST_METHOD']==='POST' && post_text('action')==='save');
    $id = $_SERVER['REQUEST_METHOD']==='POST' ? max(0,(int)post_text('id')) : max(0,(int)($_GET['edit'] ?? $_GET['delete'] ?? 0));
    if ($editing) {
        $record=$id ? row('SELECT * FROM `'.$spec['table'].'` WHERE `'.$spec['pk'].'` = ?',[$id]) : [];
        if ($id && !$record) { empty_state('Record not found','It may have been removed.',link_button('?page='.$module,'Back to '.$spec['title'],false)); return; }
        if ($_SERVER['REQUEST_METHOD']==='POST') { foreach ($spec['fields'] as $key=>$field) { $record[$key]=$field['type']==='checkbox' ? (isset($_POST[$key])?'1':'0') : post_text($key); } }
        elseif (!$id && $module==='users') { $record['active']='1'; }
        page_heading(($id?'Edit ':'Add ').$spec['singular'], 'Fields marked with * are required.', link_button('?page='.$module,'Back to '.strtolower($spec['title']),false));
        echo '<form class="record-form surface" method="POST">'.csrf_input().'<input type="hidden" name="action" value="save"><input type="hidden" name="id" value="'.$id.'">';
        echo '<fieldset><legend>'.ucfirst($spec['singular']).' details</legend><div class="form-grid">';
        $optional_groups = $module === 'students' ? [
            'Personal details'=>['birthdate','gender','birthplace','civilstatus'],
            'Family and guardian'=>['fathername','mothername','guardianname'],
            'Previous education'=>['elemschool','dategradelem','hsschool','datehsgrad','lastgrad','datelastgrad','addresslastgrad'],
        ] : [];
        $optional_fields = $optional_groups ? array_merge(...array_values($optional_groups)) : [];
        foreach ($spec['fields'] as $key=>$field) {
            if (in_array($key,$optional_fields,true)) { continue; }
            if ($module === 'users' && $key === 'password' && !$id) { $field['required']=true; $field['label']='Password'; }
            render_field($key,$field,$record[$key] ?? '');
        }
        echo '</div>';
        foreach ($optional_groups as $label=>$fields) {
            $has_values=false;
            foreach ($fields as $key) { if (($record[$key]??'')!=='') { $has_values=true; } }
            echo '<details class="optional-fields"'.($has_values?' open':'').'><summary>'.escape_html($label).' <span>Optional</span></summary><div class="form-grid">';
            foreach ($fields as $key) { render_field($key,$spec['fields'][$key],$record[$key]??''); }
            echo '</div></details>';
        }
        echo '</fieldset><div class="form-actions"><button class="button button-primary" type="submit">Save '.escape_html($spec['singular']).'</button>'.link_button('?page='.$module,'Cancel',false).'</div></form>';
        return;
    }
    page_heading($spec['title'],$spec['description'],link_button('?page='.$module.'&new=1','Add '.$spec['singular']));
    if (isset($_GET['delete']) && $id && $module!=='users') {
        echo '<section class="surface confirmation"><h2>Delete this '.escape_html($spec['singular']).'?</h2><p>This permanently removes the record. Linked records cannot be deleted.</p><form method="POST">'.csrf_input().'<input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="'.$id.'"><button class="button button-danger" type="submit">Delete record</button> '.link_button('?page='.$module,'Keep record',false).'</form></section>';
    }
    $search=is_string($_GET['q'] ?? null) ? mb_substr(trim($_GET['q']),0,100) : '';
    $number=max(1,(int)($_GET['p'] ?? 1));
    $where=''; $params=[];
    if ($search!=='') { $where=' WHERE '.implode(' OR ',array_map(fn($column)=>'`'.$column.'` LIKE ?', $spec['search'])); $params=array_fill(0,count($spec['search']),'%'.$search.'%'); }
    $count=total('SELECT COUNT(*) FROM `'.$spec['table'].'`'.$where,$params);
    $number=min($number,max(1,(int)ceil($count/15))); $offset=($number-1)*15;
    $records=rows('SELECT * FROM `'.$spec['table'].'`'.$where.' ORDER BY `'.$spec['pk'].'` DESC LIMIT 15 OFFSET '.$offset,$params);
    echo '<section class="surface"><div class="table-toolbar"><h2>All '.escape_html(strtolower($spec['title'])).' <span class="count-tag">'.$count.'</span></h2><form class="search-form" method="GET"><input type="hidden" name="page" value="'.escape_html($module).'"><label class="visually-hidden" for="record-search">Search '.escape_html(strtolower($spec['title'])).'</label><div class="search-input">'.icon('search').'<input id="record-search" type="search" name="q" value="'.escape_html($search).'" placeholder="Search '.escape_html(strtolower($spec['title'])).'"></div><button class="button button-secondary" type="submit">Search</button></form></div>';
    if (!$records) { empty_state($search!=='' ? 'No matching records' : 'No '.strtolower($spec['title']).' yet',$search!=='' ? 'Try a different search or clear the search field.' : 'Add your first '.$spec['singular'].' to start building your university records.',link_button('?page='.$module.($search!==''?'':'&new=1'),$search!==''?'Clear search':'Add '.$spec['singular'],false)); }
    elseif ($module === 'subjects') { render_subject_catalog($records); }
    else {
        $section_names=$module==='schedules' ? choices('section') : [];
        $subject_names=$module==='schedules' ? choices('subject') : [];
        $columns=$module==='students' ? ['student_name'=>'Student','studnum'=>'Student number','course'=>'Course','yearlvl'=>'Year level'] : $spec['columns'];
        echo '<div class="table-scroll" tabindex="0" role="region" aria-label="'.escape_html($spec['title']).' table"><table><thead><tr>';
        foreach ($columns as $label) { echo '<th scope="col">'.escape_html($label).'</th>'; }
        echo '<th scope="col" class="table-actions">Actions</th></tr></thead><tbody>';
        foreach ($records as $record) {
            echo '<tr>';
            foreach ($columns as $column=>$label) {
                if ($column==='student_name') {
                    $name=trim($record['fname'].' '.$record['minitial'].' '.$record['lname']);
                    $initials=mb_strtoupper(mb_substr($record['fname'],0,1).mb_substr($record['lname'],0,1));
                    echo '<td><a class="student-identity" href="?page=students&edit='.(int)$record['ID'].'"><span class="row-avatar" aria-hidden="true">'.escape_html($initials).'</span><span><strong>'.escape_html($name).'</strong><small>'.escape_html($record['emailaddress']).'</small></span></a></td>';
                    continue;
                }
                $value=$record[$column] ?? '';
                if ($module==='schedules' && $column==='section_ID') { $value=$section_names[$value] ?? 'Section #'.$value; }
                if ($module==='schedules' && $column==='subject_ID') { $value=$subject_names[$value] ?? 'Subject #'.$value; }
                $cell=$column==='active' ? badge($value?'active':'disabled') : record_cell($value);
                if ($module==='students' && in_array($column,['studnum','course'],true)) { $cell='<span class="'.($column==='course'?'course-label':'record-code').'">'.escape_html($value).'</span>'; }
                echo '<td>'.$cell.'</td>';
            }
            echo '<td class="table-actions"><a class="text-link" href="?page='.$module.'&edit='.(int)$record[$spec['pk']].'">Edit</a>';
            if ($module!=='users') { echo '<a class="delete-link" href="?page='.$module.'&delete='.(int)$record[$spec['pk']].'">Delete</a>'; }
            echo '</td></tr>';
        }
        echo '</tbody></table></div>';
    }
    echo '<div class="pagination"><span>'.($count ? ($offset+1).'–'.min($offset+15,$count).' of '.$count : '0 records').'</span><div>';
    if ($number>1) { echo link_button('?'.http_build_query(['page'=>$module,'q'=>$search,'p'=>$number-1]),'Previous',false); }
    if ($offset+15<$count) { echo link_button('?'.http_build_query(['page'=>$module,'q'=>$search,'p'=>$number+1]),'Next',false); }
    echo '</div></div></section>';
}
function render_subject_catalog($subjects)
{
    echo '<div class="catalog-grid">';
    foreach ($subjects as $subject) {
        echo '<article class="catalog-card"><div class="catalog-card-top"><span class="record-code">'.escape_html($subject['subjectcode']).'</span><span class="units-tag">'.escape_html($subject['numofunits']).' units</span></div><h3>'.escape_html($subject['subjecttitle']).'</h3><p class="catalog-description">'.escape_html($subject['description']?:$subject['course_con']).'</p><div class="catalog-flags">';
        if ($subject['islab']) { echo '<span class="badge badge-pending">Laboratory</span>'; }
        if ($subject['istutorial']) { echo '<span class="badge">Tutorial</span>'; }
        echo '</div><div class="catalog-meta">'.escape_html($subject['day']?:'Day not assigned').'<br>'.escape_html($subject['instructor']?:'Instructor not assigned').'<br>'.escape_html($subject['room']!==''?'Room '.$subject['room']:'Room not assigned').'</div><div class="catalog-actions"><a class="text-link" href="?page=subjects&edit='.(int)$subject['ID'].'">Edit subject</a><a class="delete-link" href="?page=subjects&delete='.(int)$subject['ID'].'">Delete</a></div></article>';
    }
    echo '</div>';
}
function enrollment_rows($where='', $params=[], $limit=15, $offset=0)
{
    return rows("SELECT r.*, s.studnum, s.fname, s.lname, s.course, c.section_name,
        (SELECT COALESCE(SUM(i.units),0) FROM enrollment_items i WHERE i.request_id=r.id) AS total_units,
        (SELECT COUNT(*) FROM enrollment_items i WHERE i.request_id=r.id) AS subject_count
        FROM enrollment_requests r JOIN studenttbl s ON s.ID=r.student_id LEFT JOIN sections_tbl c ON c.section_ID=r.section_id $where ORDER BY r.created_at DESC, r.id DESC LIMIT ".(int)$limit.' OFFSET '.(int)$offset,$params);
}
function enrollment_table($requests,$show_student=true)
{
    echo '<div class="table-scroll" tabindex="0" role="region" aria-label="Enrollment requests"><table><thead><tr><th scope="col">Request</th>'.($show_student?'<th scope="col">Student</th>':'').'<th scope="col">Term</th><th scope="col">Units</th><th scope="col">Status</th><th scope="col">Action</th></tr></thead><tbody>';
    foreach ($requests as $request) {
        echo '<tr><td><strong>ENR-'.str_pad($request['id'],5,'0',STR_PAD_LEFT).'</strong><small>'.escape_html(date('M j, Y',strtotime($request['created_at']))).'</small></td>';
        if ($show_student) { echo '<td>'.escape_html($request['fname'].' '.$request['lname']).'<small>'.escape_html($request['studnum']).'</small></td>'; }
        echo '<td>'.escape_html($request['academic_year']).'<small>'.escape_html($request['semester']).'</small></td><td>'.escape_html($request['total_units']).'</td><td>'.badge($request['status']).'</td><td><a class="text-link" href="?page=enrollments&view='.(int)$request['id'].'">View request</a></td></tr>';
    }
    echo '</tbody></table></div>';
}
function render_dashboard()
{
    global $user, $portal_role, $student;
    $admin=$portal_role==='admin';
    $greeting=$admin?'Welcome to administration.':'Hi '.explode(' ',$user['full_name'])[0].', ready to enroll?';
    echo '<section class="welcome-banner"><div><h1>'.escape_html($greeting).'</h1><p>'.($admin?'Manage student records, organize subject offerings, and keep enrollment moving.':'Choose your subjects, track your enrollment, and keep your university profile up to date.').'</p>'.link_button($admin?'?page=enrollments&status=pending':'?page=enroll',$admin?'Review enrollments':'Enroll in subjects',false).'</div><img src="../assets/img/university_bg1.jpg" alt="University campus" class="welcome-photo"></section>';
    $shortcuts = $admin ? [
        ['students','Manage students','Student records and contact details'], ['subjects','Manage subjects','Your university subject catalog'], ['sections','Manage sections','Classes, year levels, and capacity'], ['reports','Generate reports','Download CSV or print your records'],
    ] : [
        ['enroll','Enroll in subjects','Build your subject selection'], ['profile','My profile','Keep your details up to date'], ['schedules','My schedule','View your approved classes'], ['settings','Change password','Manage your account access'],
    ];
    echo '<div class="shortcut-grid">';
    foreach ($shortcuts as [$key,$label,$description]) { echo '<a class="shortcut'.($admin&&$key==='reports'?' shortcut-featured':'').'" href="?page='.$key.'"><span class="shortcut-icon">'.icon($key).'</span><strong>'.escape_html($label).'</strong><span>'.escape_html($description).'</span></a>'; }
    echo '</div>';
    $stats=$admin ? [
        ['students','Students',total('SELECT COUNT(*) FROM studenttbl'),'Student records'],
        ['enrollments','Pending review',total("SELECT COUNT(*) FROM enrollment_requests WHERE status='pending'"),'Enrollment requests'],
        ['courses','Courses',total('SELECT COUNT(*) FROM courses_tbl'),'Available programs'],
        ['subjects','Subjects',total('SELECT COUNT(*) FROM subjecttbl'),'In the catalog'],
    ] : [
        ['enrollments','My requests',total('SELECT COUNT(*) FROM enrollment_requests WHERE student_id = ?',[$student['ID']??0]),'Enrollment history'],
        ['enrollments','Approved',total("SELECT COUNT(*) FROM enrollment_requests WHERE student_id = ? AND status='approved'",[$student['ID']??0]),'Approved enrollments'],
        ['subjects','Course subjects',total('SELECT COUNT(*) FROM subjecttbl WHERE course_con = ?',[$student['course']??'']),'Available to select'],
    ];
    echo '<div class="stats-grid">';
    foreach ($stats as [$key,$label,$value,$hint]) { echo '<a class="stat" href="?page='.escape_html($admin?$key:($key==='subjects'?'enroll':'enrollments')).'"><div><span>'.escape_html($label).'</span>'.icon($key).'</div><strong>'.$value.'</strong><small>'.escape_html($hint).'</small></a>'; }
    echo '</div><div class="dashboard-grid"><section class="surface"><div class="section-heading"><h2>Recent enrollments</h2><a class="text-link" href="?page=enrollments">View all</a></div>';
    $requests=enrollment_rows($admin?'':'WHERE r.student_id = ?', $admin?[]:[$student['ID']??0],5);
    if ($requests) { enrollment_table($requests,$admin); }
    else { empty_state('No enrollment requests yet',$admin?'Requests will appear here when students submit their subject selections.':'Choose your subjects to submit your first enrollment request.',$admin?'':link_button('?page=enroll','Choose subjects',false)); }
    echo '</section><section class="surface overview-panel"><h2>'.($admin?'Set up your university':'Your student record').'</h2>';
    if ($admin) {
        $items=[['courses','Add courses','Define your academic programs.'],['subjects','Build the subject catalog','Set units and class details.'],['students','Add students and accounts','Link each account to a student record.'],['sections','Arrange sections','Group students by course and year.']];
        echo '<div class="setup-links">';
        foreach ($items as [$key,$label,$hint]) { echo '<a href="?page='.$key.'">'.icon($key).'<span><strong>'.escape_html($label).'</strong><small>'.escape_html($hint).'</small></span>'.icon('arrow').'</a>'; }
        echo '</div>';
    } elseif ($student) {
        echo '<dl class="detail-list"><dt>Student number</dt><dd>'.escape_html($student['studnum']).'</dd><dt>Course</dt><dd>'.escape_html($student['course']).'</dd><dt>Year level</dt><dd>'.escape_html($student['yearlvl']).'</dd></dl>'.link_button('?page=profile','View my profile',false);
    } else { echo '<p>No student record is linked yet. Contact your university administrator.</p>'; }
    echo '</section></div>';
}
function render_enrollments()
{
    global $portal_role,$student;
    $admin=$portal_role==='admin';
    $id=max(0,(int)($_GET['view'] ?? 0));
    if ($id) {
        $requests=enrollment_rows('WHERE r.id = ?'.($admin?'':' AND r.student_id = ?'),$admin?[$id]:[$id,$student['ID']??0],1);
        if (!$requests) { http_response_code(404); empty_state('Enrollment not found','This request is unavailable or does not belong to your account.',link_button('?page=enrollments','Back to enrollments',false)); return; }
        $request=$requests[0];
        page_heading('Enrollment ENR-'.str_pad($id,5,'0',STR_PAD_LEFT),'Review the subjects and enrollment information.', '<div class="heading-actions">'.link_button('?page=enrollments','Back',false).'<button class="button button-secondary print-button" type="button">'.icon('print').'Print</button></div>');
        echo '<section class="surface request-summary"><div class="section-heading"><h2>Enrollment details</h2>'.badge($request['status']).'</div><dl class="detail-grid">';
        foreach (['Student'=>$request['fname'].' '.$request['lname'],'Student number'=>$request['studnum'],'Course'=>$request['course'],'Academic year'=>$request['academic_year'],'Semester'=>$request['semester'],'Section'=>$request['section_name']??'Unassigned'] as $label=>$value) { echo '<div><dt>'.escape_html($label).'</dt><dd>'.escape_html($value).'</dd></div>'; }
        echo '</dl></section><section class="surface"><div class="section-heading"><h2>Selected subjects</h2><span class="count-tag">'.escape_html($request['total_units']).' units</span></div><div class="table-scroll"><table><thead><tr><th scope="col">Code</th><th scope="col">Subject</th><th scope="col">Units</th></tr></thead><tbody>';
        foreach (rows('SELECT * FROM enrollment_items WHERE request_id = ? ORDER BY subject_code',[$id]) as $item) { echo '<tr><td>'.escape_html($item['subject_code']).'</td><td>'.escape_html($item['subject_title']).'</td><td>'.escape_html($item['units']).'</td></tr>'; }
        echo '</tbody></table></div></section>';
        if ($request['notes']!=='') { echo '<section class="surface notes-panel"><h2>Administrator’s note</h2><p>'.nl2br(escape_html($request['notes'])).'</p></section>'; }
        if ($request['status']==='pending') {
            if ($admin) {
                echo '<form method="POST" class="surface decision-form">'.csrf_input().'<input type="hidden" name="action" value="decide"><input type="hidden" name="id" value="'.$id.'"><h2>Review enrollment</h2><label for="review-notes">Note to the student (required when rejecting)</label><textarea id="review-notes" name="notes" maxlength="1000" rows="3">'.escape_html(post_text('notes')).'</textarea><div class="form-actions"><button class="button button-primary" name="decision" value="approved" type="submit">Approve enrollment</button><button class="button button-secondary" name="decision" value="rejected" type="submit">Reject enrollment</button></div></form>';
            } else {
                echo '<form method="POST" class="surface confirmation">'.csrf_input().'<input type="hidden" name="action" value="withdraw"><input type="hidden" name="id" value="'.$id.'"><h2>Need to change your subjects?</h2><p>Withdraw this pending request, then submit a new subject selection for the same term.</p><button class="button button-secondary" type="submit">Withdraw request</button></form>';
            }
        }
        return;
    }
    page_heading($admin?'Enrollments':'My enrollments',$admin?'Review subject selections and help students move forward.':'Track your requests and see enrollment decisions.',$admin?'':link_button('?page=enroll','New enrollment'));
    $status=is_string($_GET['status']??null) ? $_GET['status'] : '';
    if (!in_array($status,['pending','approved','rejected','withdrawn'],true)) { $status=''; }
    $search=is_string($_GET['q']??null) ? mb_substr(trim($_GET['q']),0,100) : '';
    $clauses=[]; $params=[];
    if (!$admin) { $clauses[]='r.student_id = ?'; $params[]=$student['ID']??0; }
    if ($status!=='') { $clauses[]='r.status = ?'; $params[]=$status; }
    if ($search!=='') { $clauses[]='(s.studnum LIKE ? OR s.fname LIKE ? OR s.lname LIKE ?)'; array_push($params,'%'.$search.'%','%'.$search.'%','%'.$search.'%'); }
    $where=$clauses?'WHERE '.implode(' AND ',$clauses):'';
    $count=total('SELECT COUNT(*) FROM enrollment_requests r JOIN studenttbl s ON s.ID=r.student_id '.$where,$params);
    $number=min(max(1,(int)($_GET['p']??1)),max(1,(int)ceil($count/15)));
    $requests=enrollment_rows($where,$params,15,($number-1)*15);
    echo '<section class="surface"><form method="GET" class="filter-toolbar"><input type="hidden" name="page" value="enrollments"><div><label for="status-filter">Status</label><select name="status" id="status-filter"><option value="">All statuses</option>';
    foreach (['pending','approved','rejected','withdrawn'] as $option) { echo '<option value="'.$option.'"'.($option===$status?' selected':'').'>'.ucfirst($option).'</option>'; }
    echo '</select></div><div><label for="enrollment-search">Student name or number</label><input type="search" id="enrollment-search" name="q" value="'.escape_html($search).'" placeholder="Search enrollments"></div><button class="button button-secondary" type="submit">Apply filters</button></form>';
    if ($requests) { enrollment_table($requests,$admin); } else { empty_state('No enrollments to display','Submitted requests will appear here. Try changing the filters if you expected a result.'); }
    echo '<div class="pagination"><span>'.$count.' requests</span><div>';
    if ($number>1) { echo link_button('?'.http_build_query(['page'=>'enrollments','status'=>$status,'q'=>$search,'p'=>$number-1]),'Previous',false); }
    if ($number*15<$count) { echo link_button('?'.http_build_query(['page'=>'enrollments','status'=>$status,'q'=>$search,'p'=>$number+1]),'Next',false); }
    echo '</div></div></section>';
}
function render_enroll()
{
    global $student;
    page_heading('Enroll in subjects','Choose your term, section, and subjects. Your administrator will review the request.');
    if (!$student) { empty_state('Student record not linked','Contact your university administrator to link your account.'); return; }
    $subjects=rows('SELECT * FROM subjecttbl WHERE course_con = ? ORDER BY subjectcode',[$student['course']]);
    $sections=rows('SELECT * FROM sections_tbl WHERE section_course = ? AND yr_level = ? ORDER BY section_name',[$student['course'],$student['yearlvl']]);
    if (!$subjects || !$sections) { empty_state('Your course is being prepared','Your administrator needs to add subjects and a section for your course and year level before you can enroll.'); return; }
    echo '<form method="POST" id="enrollment-form">'.csrf_input().'<input type="hidden" name="action" value="enroll"><section class="surface record-form"><fieldset><legend>Enrollment term</legend><p class="form-help">'.escape_html($student['course']).' · Year '.escape_html($student['yearlvl']).'</p><div class="form-grid">';
    render_field('academic_year',field('Academic year','text',true,9),post_text('academic_year'));
    render_field('semester',field('Semester','select',true,20,['First semester'=>'First semester','Second semester'=>'Second semester','Summer'=>'Summer']),post_text('semester'));
    render_field('section_id',field('Section','select',true,100,array_column($sections,'section_name','section_ID')),post_text('section_id'));
    echo '</div><p class="form-help">Enter the academic year as two consecutive years, for example 2026-2027.</p></fieldset></section><div class="enrollment-layout"><section aria-labelledby="available-subjects"><div class="section-heading"><h2 id="available-subjects">Available subjects</h2></div><div class="subject-selection-grid">';
    $selected=is_array($_POST['subjects']??null)?array_filter($_POST['subjects'],'is_string'):[];
    foreach ($subjects as $subject) {
        echo '<label class="subject-card subject-choice"><span class="catalog-card-top"><span class="record-code">'.escape_html($subject['subjectcode']).'</span><input type="checkbox" class="subject-checkbox" name="subjects[]" value="'.(int)$subject['ID'].'" data-units="'.escape_html($subject['numofunits']).'" aria-label="Select '.escape_html($subject['subjectcode'].' '.$subject['subjecttitle']).'"'.(in_array((string)$subject['ID'],$selected,true)?' checked':'').'></span><strong>'.escape_html($subject['subjecttitle']).'</strong><small>'.escape_html($subject['day']?:'Day not assigned').'<br>'.escape_html($subject['instructor']?:'Instructor not assigned').'</small><span class="catalog-flags"><span class="badge">'.escape_html($subject['numofunits']).' units</span>'.($subject['islab']?'<span class="badge badge-pending">Laboratory</span>':'').($subject['istutorial']?'<span class="badge">Tutorial</span>':'').'</span></label>';
    }
    echo '</div></section><aside class="selection-panel" aria-labelledby="selection-heading"><h2 id="selection-heading">Your selection</h2><div class="selection-totals" aria-live="polite"><div>Subjects<strong id="selected-count">0</strong></div><div>Units<strong id="selected-units">0</strong></div></div><button class="button" type="submit">Submit enrollment request</button><p>Your administrator will review your request. You can withdraw it while it is pending.</p></aside></div></form>';
}
function render_profile()
{
    global $student,$modules;
    page_heading('My profile','Keep your contact information current. Contact your administrator for academic record changes.');
    if (!$student) { empty_state('Student record not linked','Contact your university administrator to link your account.'); return; }
    echo '<section class="surface profile-summary"><span class="profile-avatar">'.escape_html(mb_strtoupper(mb_substr($student['fname'],0,1))).'</span><div><h2>'.escape_html($student['fname'].' '.$student['lname']).'</h2><p>'.escape_html($student['studnum']).' · '.escape_html($student['course']).' · Year '.escape_html($student['yearlvl']).'</p></div></section>';
    echo '<div class="profile-layout"><nav class="profile-sections" aria-label="Profile sections"><a href="#profile-contact">Contact and family</a><a href="#profile-personal">Personal record</a><a href="?page=settings">Change password</a><a href="?page=enroll">Enroll in subjects</a></nav><div><form id="profile-contact" method="POST" class="surface record-form">'.csrf_input().'<input type="hidden" name="action" value="profile"><fieldset><legend>Contact and family information</legend><div class="form-grid">';
    foreach (['emailaddress','contact','address','guardianname','fathername','mothername'] as $key) { render_field($key,$modules['students']['fields'][$key],$_SERVER['REQUEST_METHOD']==='POST'?post_text($key):$student[$key]); }
    echo '</div></fieldset><div class="form-actions"><button class="button button-primary" type="submit">Save contact information</button></div></form>';
    echo '<section id="profile-personal" class="surface notes-panel"><h2>Academic and personal record</h2><dl class="detail-grid">';
    foreach (['birthdate'=>'Date of birth','gender'=>'Gender','birthplace'=>'Place of birth','elemschool'=>'Elementary school','hsschool'=>'High school','lastgrad'=>'Previous school'] as $key=>$label) { echo '<div><dt>'.escape_html($label).'</dt><dd>'.escape_html($student[$key]?:'Not provided').'</dd></div>'; }
    echo '</dl></section></div></div>';
}
function render_settings()
{
    global $user;
    page_heading('Settings','Manage your account and password.');
    echo '<section class="surface notes-panel"><h2>Your account</h2><dl class="detail-grid"><div><dt>Full name</dt><dd>'.escape_html($user['full_name']).'</dd></div><div><dt>Username</dt><dd>'.escape_html($user['username']).'</dd></div><div><dt>Role</dt><dd>'.escape_html(ucfirst($user['role'])).'</dd></div></dl></section><form method="POST" class="surface record-form narrow-form">'.csrf_input().'<input type="hidden" name="action" value="password"><fieldset><legend>Change password</legend><p class="form-help">Use 8–72 bytes. You will stay signed in after changing your password.</p>';
    foreach (['current_password'=>'Current password','new_password'=>'New password','confirm_password'=>'Confirm new password'] as $name=>$label) {
        echo '<div class="input-group"><label for="'.$name.'">'.$label.'</label><input id="'.$name.'" type="password" name="'.$name.'" autocomplete="'.($name==='current_password'?'current-password':'new-password').'" required maxlength="72"'.($name!=='current_password'?' minlength="8"':'').'></div>';
    }
    echo '</fieldset><div class="form-actions"><button class="button button-primary" type="submit">Update password</button></div></form>';
}
function render_student_schedule()
{
    global $student;
    page_heading('My schedule','Classes from your approved enrollments.');
    $classes=rows("SELECT s.*, t.subjectcode, t.subjecttitle, t.day, t.instructor, c.section_name, r.academic_year, r.semester FROM enrollment_requests r JOIN enrollment_items i ON i.request_id=r.id JOIN sched_tbl s ON s.section_ID=r.section_id AND s.subject_ID=i.subject_id JOIN subjecttbl t ON t.ID=s.subject_ID JOIN sections_tbl c ON c.section_ID=s.section_ID WHERE r.student_id = ? AND r.status='approved' ORDER BY r.academic_year DESC, r.semester, FIELD(t.day,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'), s.time_from",[$student['ID']??0]);
    echo '<section class="surface"><div class="section-heading"><h2>Class timetable</h2><button type="button" class="button button-secondary print-button">'.icon('print').'Print schedule</button></div>';
    if (!$classes) { empty_state('No scheduled classes yet','Approved enrollments with assigned class times will appear here.',link_button('?page=enrollments','View my enrollments',false)); }
    else {
        echo '<div class="table-scroll"><table><thead><tr><th>Day and time</th><th>Subject</th><th>Room</th><th>Section</th><th>Term</th></tr></thead><tbody>';
        foreach ($classes as $class) { echo '<tr><td><strong>'.escape_html($class['day']).'</strong><small>'.escape_html($class['time_from'].'–'.$class['time_to']).'</small></td><td>'.escape_html($class['subjectcode'].' — '.$class['subjecttitle']).'<small>'.escape_html($class['instructor']).'</small></td><td>'.escape_html($class['room_ID']).'</td><td>'.escape_html($class['section_name']).'</td><td>'.escape_html($class['academic_year']).'<small>'.escape_html($class['semester']).'</small></td></tr>'; }
        echo '</tbody></table></div>';
    }
    echo '</section>';
}
