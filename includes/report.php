<?php
// Shared definitions keep the preview and export on the same filtered query.
function report_types()
{
    return ['enrollments'=>'Enrollment report','students'=>'Student report','courses'=>'Course report','subjects'=>'Subject report','sections'=>'Section report','schedules'=>'Schedule report'];
}
function report_time($format)
{
    // Use the database clock, matching enrollment submission timestamps.
    static $generated=null;
    if ($generated===null) { $generated=new DateTimeImmutable(row('SELECT CURRENT_TIMESTAMP AS generated_at')['generated_at']); }
    return $generated->format($format);
}
function report_filters($input)
{
    $type = $input['report_type'] ?? 'enrollments';
    if (!is_string($type) || !isset(report_types()[$type])) { throw new DomainException('Choose a valid report type.'); }
    $filters = ['report_type'=>$type];
    $allowed = ['course'];
    if (in_array($type,['students','sections','schedules','enrollments'],true)) { $allowed[]='year_level'; }
    if ($type==='enrollments') { array_push($allowed,'academic_year','semester','status','date_from','date_to'); }
    if ($type==='schedules') { $allowed[]='day'; }
    foreach ($allowed as $key) {
        $value = $input[$key] ?? '';
        if (!is_string($value) || mb_strlen($value)>100) { throw new DomainException('One of the report filters is invalid.'); }
        $filters[$key] = trim($value);
    }
    $options = ['year_level'=>['1','2','3','4','5'], 'semester'=>['First semester','Second semester','Summer'], 'status'=>['pending','approved','rejected','withdrawn'], 'day'=>['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday']];
    foreach ($options as $key=>$values) {
        if (($filters[$key]??'')!=='' && !in_array($filters[$key],$values,true)) { throw new DomainException('Choose a valid '.str_replace('_',' ',$key).'.'); }
    }
    if (($filters['academic_year']??'')!=='' && (!preg_match('/^(\d{4})-(\d{4})$/D',$filters['academic_year'],$match) || (int)$match[2] !== (int)$match[1]+1)) { throw new DomainException('Use consecutive academic years, for example 2026-2027.'); }
    foreach (['date_from','date_to'] as $key) {
        if (($filters[$key]??'')!=='') {
            $date=DateTime::createFromFormat('!Y-m-d',$filters[$key]);
            if (!$date || $date->format('Y-m-d')!==$filters[$key]) { throw new DomainException('Choose valid report dates.'); }
        }
    }
    if (($filters['date_from']??'')!=='' && ($filters['date_to']??'')!=='' && $filters['date_from']>$filters['date_to']) { throw new DomainException('The end date must be on or after the start date.'); }
    return $filters;
}
function report_query($filters)
{
    $type=$filters['report_type'];
    $definitions = [
        'enrollments'=>[
            'columns'=>['id'=>'Request ID','student_number'=>'Student number','student_name'=>'Student name','course'=>'Course','year_level'=>'Year level','academic_year'=>'Academic year','semester'=>'Semester','section'=>'Section','status'=>'Status','subject_count'=>'Subjects','total_units'=>'Total units','submitted'=>'Submitted','notes'=>'Administrator note'],
            'select'=>"r.id, s.studnum AS student_number, CONCAT_WS(' ',s.fname,NULLIF(s.minitial,''),s.lname) AS student_name, s.course, s.yearlvl AS year_level, r.academic_year, r.semester, c.section_name AS section, r.status, (SELECT COUNT(*) FROM enrollment_items i WHERE i.request_id=r.id) AS subject_count, (SELECT COALESCE(SUM(i.units),0) FROM enrollment_items i WHERE i.request_id=r.id) AS total_units, r.created_at AS submitted, r.notes",
            'from'=>'enrollment_requests r JOIN studenttbl s ON s.ID=r.student_id LEFT JOIN sections_tbl c ON c.section_ID=r.section_id', 'course'=>'s.course','year'=>'s.yearlvl','order'=>'r.created_at DESC, r.id DESC',
        ],
        'students'=>[
            'columns'=>['student_number'=>'Student number','student_name'=>'Student name','course'=>'Course','year_level'=>'Year level','email'=>'Email','contact'=>'Contact number'],
            'select'=>"s.studnum AS student_number, CONCAT_WS(' ',s.fname,NULLIF(s.minitial,''),s.lname) AS student_name, s.course, s.yearlvl AS year_level, s.emailaddress AS email, s.contact", 'from'=>'studenttbl s','course'=>'s.course','year'=>'s.yearlvl','order'=>'s.lname, s.fname, s.ID',
        ],
        'courses'=>[
            'columns'=>['course'=>'Course','description'=>'Description','students'=>'Students','subjects'=>'Subjects','sections'=>'Sections'],
            'select'=>'c.course_title AS course, c.course_desc AS description, (SELECT COUNT(*) FROM studenttbl s WHERE s.course=c.course_title) AS students, (SELECT COUNT(*) FROM subjecttbl t WHERE t.course_con=c.course_title) AS subjects, (SELECT COUNT(*) FROM sections_tbl x WHERE x.section_course=c.course_title) AS sections', 'from'=>'courses_tbl c','course'=>'c.course_title','order'=>'c.course_title, c.course_ID',
        ],
        'subjects'=>[
            'columns'=>['code'=>'Subject code','subject'=>'Subject','course'=>'Course','units'=>'Units','day'=>'Class day','instructor'=>'Instructor','room'=>'Room','laboratory'=>'Laboratory','tutorial'=>'Tutorial'],
            'select'=>"t.subjectcode AS code, t.subjecttitle AS subject, t.course_con AS course, t.numofunits AS units, t.day, t.instructor, t.room, IF(t.islab=1,'Yes','No') AS laboratory, IF(t.istutorial=1,'Yes','No') AS tutorial", 'from'=>'subjecttbl t','course'=>'t.course_con','order'=>'t.course_con, t.subjectcode, t.ID',
        ],
        'sections'=>[
            'columns'=>['section'=>'Section','course'=>'Course','year_level'=>'Year level','minimum'=>'Minimum students','capacity'=>'Maximum students','classes'=>'Scheduled classes'],
            'select'=>'c.section_name AS section, c.section_course AS course, c.yr_level AS year_level, c.min_num AS minimum, c.max_num AS capacity, (SELECT COUNT(*) FROM sched_tbl x WHERE x.section_ID=c.section_ID) AS classes', 'from'=>'sections_tbl c','course'=>'c.section_course','year'=>'c.yr_level','order'=>'c.section_course, c.yr_level, c.section_name, c.section_ID',
        ],
        'schedules'=>[
            'columns'=>['section'=>'Section','course'=>'Course','year_level'=>'Year level','code'=>'Subject code','subject'=>'Subject','day'=>'Class day','start'=>'Start time','end'=>'End time','room'=>'Room number','instructor'=>'Instructor'],
            'select'=>'c.section_name AS section, c.section_course AS course, c.yr_level AS year_level, t.subjectcode AS code, t.subjecttitle AS subject, t.day, x.time_from AS start, x.time_to AS end, x.room_ID AS room, t.instructor', 'from'=>'sched_tbl x LEFT JOIN sections_tbl c ON c.section_ID=x.section_ID LEFT JOIN subjecttbl t ON t.ID=x.subject_ID','course'=>'c.section_course','year'=>'c.yr_level','order'=>"c.section_name, FIELD(t.day,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'), x.time_from, x.sched_ID",
        ],
    ];
    $definition=$definitions[$type]; $clauses=[]; $params=[];
    if (($filters['course']??'')!=='') { $clauses[]=$definition['course'].' = ?'; $params[]=$filters['course']; }
    if (($filters['year_level']??'')!=='') { $clauses[]=$definition['year'].' = ?'; $params[]=$filters['year_level']; }
    if ($type==='enrollments') {
        foreach (['academic_year','semester','status'] as $key) {
            if ($filters[$key]!=='') { $clauses[]='r.'.$key.' = ?'; $params[]=$filters[$key]; }
        }
        if ($filters['date_from']!=='') { $clauses[]='r.created_at >= ?'; $params[]=$filters['date_from'].' 00:00:00'; }
        if ($filters['date_to']!=='') { $clauses[]='r.created_at < ?'; $params[]=(new DateTimeImmutable($filters['date_to']))->modify('+1 day')->format('Y-m-d').' 00:00:00'; }
    }
    if ($type==='schedules' && $filters['day']!=='') { $clauses[]='t.day = ?'; $params[]=$filters['day']; }
    $definition['where']=$clauses?' WHERE '.implode(' AND ',$clauses):'';
    $definition['params']=$params;
    return $definition;
}
function report_csv_cell($value)
{
    $value=(string)($value??'');
    // Spreadsheet programs can execute cells beginning with formula characters.
    if (preg_match('/^[\s\p{Z}\x{FEFF}\x00-\x20]*[=+@-]/u',$value) || preg_match('/^[\t\r\n]/',$value)) { $value="'".$value; }
    return $value;
}
function report_courses()
{
    return array_column(rows("SELECT course_title AS course FROM courses_tbl UNION SELECT course FROM studenttbl WHERE course<>'' UNION SELECT course_con FROM subjecttbl WHERE course_con<>'' UNION SELECT section_course FROM sections_tbl WHERE section_course<>'' ORDER BY course"),'course','course');
}
function render_reports()
{
    try { $filters=report_filters($_GET); }
    catch (DomainException $exception) {
        http_response_code(400);
        echo '<div class="notice notice-error" role="alert">'.escape_html($exception->getMessage()).'</div>';
        $filters=report_filters([]);
    }
    $query=report_query($filters);
    $count=total('SELECT COUNT(*) FROM '.$query['from'].$query['where'],$query['params']);
    $number=min(max(1,(int)($_GET['p']??1)),max(1,(int)ceil($count/20)));
    $offset=($number-1)*20;
    $records=rows('SELECT '.$query['select'].' FROM '.$query['from'].$query['where'].' ORDER BY '.$query['order'].' LIMIT 20 OFFSET '.$offset,$query['params']);
    $title=report_types()[$filters['report_type']];
    $download='export.php?'.http_build_query($filters);
    page_heading('Reports','Filter university records, review the results, and download a CSV for your spreadsheet.');
    echo '<section class="surface report-filters"><form method="GET" class="record-form"><input type="hidden" name="page" value="reports"><fieldset><legend>Choose your report</legend><div class="form-grid">';
    render_field('report_type',field('Report type','select',true,100,report_types()),$filters['report_type']);
    render_field('course',field('Course','select',false,100,report_courses()),$filters['course']);
    if (isset($filters['year_level'])) { render_field('year_level',field('Year level','select',false,100,['1'=>'First year','2'=>'Second year','3'=>'Third year','4'=>'Fourth year','5'=>'Fifth year']),$filters['year_level']); }
    if ($filters['report_type']==='enrollments') {
        render_field('academic_year',field('Academic year (for example 2026-2027)','text',false,9),$filters['academic_year']);
        render_field('semester',field('Semester','select',false,100,['First semester'=>'First semester','Second semester'=>'Second semester','Summer'=>'Summer']),$filters['semester']);
        render_field('status',field('Enrollment status','select',false,100,['pending'=>'Pending','approved'=>'Approved','rejected'=>'Rejected','withdrawn'=>'Withdrawn']),$filters['status']);
        render_field('date_from',field('Submitted from','date'),$filters['date_from']);
        render_field('date_to',field('Submitted through','date'),$filters['date_to']);
    }
    if ($filters['report_type']==='schedules') { render_field('day',field('Class day','select',false,100,array_combine(['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'],['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'])),$filters['day']); }
    echo '</div><p class="form-help">Blank filters include all records. Change the report type and apply filters to see its options.</p></fieldset><div class="form-actions"><button type="submit" class="button button-primary">Apply filters</button>'.link_button('?page=reports&report_type='.$filters['report_type'],'Reset filters',false).'</div></form></section>';
    echo '<section class="surface"><div class="section-heading"><div><h2>'.escape_html($title).'</h2><p class="report-caption">'.$count.' matching '.($count===1?'record':'records').' · Generated '.escape_html(report_time('M j, Y, g:i a')).'</p></div><div class="heading-actions">'.link_button($download,'Download CSV').'<a class="button button-secondary" href="'.escape_html('report-print.php?'.http_build_query($filters)).'" target="_blank" rel="noopener">'.icon('print').'Print / Save as PDF</a></div></div>';
    $active=array_filter(array_diff_key($filters,['report_type'=>true]),fn($value)=>$value!=='');
    if ($active) {
        echo '<dl class="report-filter-summary">';
        foreach ($active as $key=>$value) { echo '<div><dt>'.escape_html(ucfirst(str_replace('_',' ',$key))).'</dt><dd>'.escape_html($value).'</dd></div>'; }
        echo '</dl>';
    }
    echo '<p class="report-preview-note">Preview shows up to 20 rows per page. Downloads and the print view include every matching record.</p>';
    if (!$records) { empty_state('No matching records','Try changing the filters or add records in the corresponding administration section.'); }
    else { render_report_table($query['columns'],$records); }
    echo '<div class="pagination"><span>'.($count?($offset+1).'–'.min($offset+20,$count).' of '.$count:'0 records').'</span><div>';
    if ($number>1) { echo link_button('?'.http_build_query(array_merge($filters,['page'=>'reports','p'=>$number-1])),'Previous',false); }
    if ($offset+20<$count) { echo link_button('?'.http_build_query(array_merge($filters,['page'=>'reports','p'=>$number+1])),'Next',false); }
    echo '</div></div></section>';
}
function render_report_table($columns,$records,$print=false)
{
    echo '<div class="table-scroll" tabindex="0" role="region" aria-label="Report results"><table><thead><tr>';
    foreach ($columns as $label) { echo '<th scope="col">'.escape_html($label).'</th>'; }
    echo '</tr></thead><tbody>';
    foreach ($records as $record) {
        echo '<tr>';
        foreach ($columns as $key=>$label) { echo '<td>'.($print ? '<span class="cell-text">'.nl2br(escape_html($record[$key]??'')).'</span>' : ($key==='status'?badge($record[$key]):record_cell($record[$key]??''))).'</td>'; }
        echo '</tr>';
    }
    echo '</tbody></table></div>';
}
