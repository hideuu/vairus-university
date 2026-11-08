<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
if (!getenv('TEST_ADMIN_PASSWORD')) { exit("Set TEST_ADMIN_PASSWORD.\n"); }
require __DIR__.'/../includes/conn.php';
require __DIR__.'/../includes/portal_data.php';
$prefix='qa_'.bin2hex(random_bytes(4));
$jars=[]; $ids=[]; $admin_token='';
$ip=hash('sha256','127.0.0.1');
$prior=row('SELECT * FROM auth_login_limits WHERE ip_key = ?',[$ip]);
function verify($condition,$label) { if (!$condition) { throw new RuntimeException('FAIL: '.$label); } echo 'PASS: '.$label."\n"; }
function request($who,$path,$fields=null) {
    global $jars;
    if (!isset($jars[$who])) { $jars[$who]=tempnam(sys_get_temp_dir(),'vairus-portal-'); }
    $c=curl_init('http://localhost/vairusuniversity/public/'.$path);
    curl_setopt_array($c,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_HEADER=>true,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_COOKIEJAR=>$jars[$who],CURLOPT_COOKIEFILE=>$jars[$who],CURLOPT_TIMEOUT=>15,CURLOPT_IPRESOLVE=>CURL_IPRESOLVE_V4]);
    if ($fields!==null) { curl_setopt($c,CURLOPT_POSTFIELDS,http_build_query($fields)); }
    $response=curl_exec($c); if ($response===false) { throw new RuntimeException(curl_error($c)); }
    $code=curl_getinfo($c,CURLINFO_HTTP_CODE); curl_close($c); return [$code,$response];
}
function token($page) { if (!preg_match('/name="csrf_token" value="([a-f0-9]+)"/',$page,$m)) { throw new RuntimeException('CSRF token missing'); } return $m[1]; }
function login_as($who,$username,$password) {
    [$code,$body]=request($who,'index.php');
    [$code,$body]=request($who,'index.php',['username'=>$username,'password'=>$password,'csrf_token'=>token($body)]);
    verify($code===303,$who.' login');
    [$code,$body]=request($who,($who==='admin'?'admin':'student').'/index.php');
    verify($code===200 && strpos($body,'temporarily unavailable')===false,$who.' dashboard');
    return token($body);
}
function save_fixture($module,$fields) {
    global $admin_token,$ids;
    [$code,$body]=request('admin','admin/index.php?page='.$module.'&new=1',array_merge($fields,['action'=>'save','id'=>'0','csrf_token'=>$admin_token]));
    verify($code===303,'Create '.$module);
    $spec=portal_modules()[$module];
    $unique=['courses'=>'course_title','subjects'=>'subjectcode','sections'=>'section_name','students'=>'studnum','users'=>'username','schedules'=>'room_ID'][$module];
    $record=row('SELECT * FROM `'.$spec['table'].'` WHERE `'.$unique.'` = ? ORDER BY `'.$spec['pk'].'` DESC LIMIT 1',[$fields[$unique]]);
    verify((bool)$record,$module.' persisted');
    $ids[$module][]=$record[$spec['pk']]; return $record[$spec['pk']];
}
try {
    db('DELETE FROM auth_login_limits WHERE ip_key = ?',[$ip]);
    $admin_token=login_as('admin','admin',getenv('TEST_ADMIN_PASSWORD'));
    foreach (['dashboard','students','courses','subjects','sections','schedules','users','enrollments','reports','settings'] as $page) {
        [$code,$body]=request('admin','admin/index.php?page='.$page);
        verify($code===200 && strpos($body,'temporarily unavailable')===false,'Admin page '.$page);
        if (isset(portal_modules()[$page])) { [$code,$body]=request('admin','admin/index.php?page='.$page.'&new=1'); verify($code===200 && strpos($body,'temporarily unavailable')===false,'Form '.$page); }
    }
    [$code]=request('anonymous','student/index.php'); verify($code===303,'Student portal requires login');
    [$code]=request('admin','admin/index.php?page=courses',['action'=>'save','course_title'=>'bad']); verify($code===403,'CRUD CSRF check');
    $course=$prefix.'_course';
    $course_id=save_fixture('courses',['course_title'=>$course,'course_desc'=>'Integration test course']);
    $long_description=rtrim('Updated integration course. '.str_repeat('Course description for reading and keyboard expansion. ',8));
    [$code]=request('admin','admin/index.php?page=courses&edit='.$course_id,['csrf_token'=>$admin_token,'action'=>'save','id'=>(string)$course_id,'course_title'=>$course,'course_desc'=>$long_description]);
    verify($code===303 && row('SELECT course_desc FROM courses_tbl WHERE course_ID = ?',[$course_id])['course_desc']===$long_description,'Record update persists');
    [$code,$body]=request('admin','admin/index.php?page=courses&q='.urlencode($course));
    verify($code===200 && strpos($body,$course)!==false,'Record search');
    $unused_id=save_fixture('courses',['course_title'=>$prefix.'_unused']);
    [$code]=request('admin','admin/index.php?page=courses',['csrf_token'=>$admin_token,'action'=>'delete','id'=>(string)$unused_id]);
    verify($code===303 && !row('SELECT course_ID FROM courses_tbl WHERE course_ID = ?',[$unused_id]),'Unreferenced record deletion');
    $subject_id=save_fixture('subjects',['subjectcode'=>$prefix.'_sub','subjecttitle'=>'Integration test subject','numofunits'=>'3','course_con'=>$course,'day'=>'Monday']);
    $section_id=save_fixture('sections',['section_name'=>$prefix.'_section','section_course'=>$course,'yr_level'=>'1','max_num'=>'1','min_num'=>'0']);
    $room=(string)random_int(10000,99999);
    $schedule_id=save_fixture('schedules',['section_ID'=>(string)$section_id,'subject_ID'=>(string)$subject_id,'time_from'=>'09:00','time_to'=>'10:00','room_ID'=>$room]);
    [$code,$body]=request('admin','admin/index.php?page=schedules&new=1',['csrf_token'=>$admin_token,'action'=>'save','id'=>'0','section_ID'=>(string)$section_id,'subject_ID'=>(string)$subject_id,'time_from'=>'09:30','time_to'=>'10:30','room_ID'=>$room]);
    verify($code===200 && strpos($body,'already has a class')!==false,'Schedule conflict rejected');
    $student_id=save_fixture('students',['studnum'=>$prefix.'_student','fname'=>'Portal','lname'=>'Test','course'=>$course,'yearlvl'=>'1']);
    $other_id=save_fixture('students',['studnum'=>$prefix.'_other','fname'=>'Other','lname'=>'Student','course'=>$course,'yearlvl'=>'1']);
    $student_password=bin2hex(random_bytes(8));
    $account_id=save_fixture('users',['full_name'=>'Portal Test','username'=>$prefix,'role'=>'student','student_id'=>(string)$student_id,'password'=>$student_password,'active'=>'1']);
    $student_token=login_as('student',$prefix,$student_password);
    foreach (['profile','enroll','enrollments','schedules','settings'] as $page) {
        [$code,$body]=request('student','student/index.php?page='.$page); verify($code===200 && strpos($body,'temporarily unavailable')===false,'Student page '.$page);
    }
    [$code]=request('student','admin/index.php'); verify($code===403,'Student cannot access administration');
    [$code]=request('student','student/index.php?page=users'); verify($code===404,'Student cannot access account management');
    [$code]=request('student','student/index.php?page=reports'); verify($code===404,'Student has no reports navigation route');
    [$code]=request('student','admin/export.php?report_type=students'); verify($code===403,'Student cannot export administrative reports');
    [$code]=request('student','admin/report-print.php?report_type=students'); verify($code===403,'Student cannot open administrative print reports');
    [$code]=request('anonymous','admin/export.php'); verify($code===303,'Anonymous CSV export requires login');
    $admin_id=row("SELECT id FROM auth_users WHERE username='admin'")['id'];
    [$code,$body]=request('admin','admin/index.php?page=users&edit='.$admin_id,['csrf_token'=>$admin_token,'action'=>'save','id'=>(string)$admin_id,'full_name'=>'University Administrator','username'=>'admin','role'=>'admin']);
    verify($code===200 && strpos($body,'cannot remove your own')!==false,'Administrator cannot disable own account');
    [$code]=request('student','student/index.php?page=profile',['csrf_token'=>$student_token,'action'=>'profile','id'=>(string)$other_id,'emailaddress'=>'student@example.test','contact'=>'123456789','course'=>'tampered']);
    verify($code===303 && row('SELECT emailaddress, course FROM studenttbl WHERE ID = ?',[$student_id])===['emailaddress'=>'student@example.test','course'=>$course],'Student profile saves allowed fields only');
    verify(row('SELECT emailaddress FROM studenttbl WHERE ID = ?',[$other_id])['emailaddress']==='','Profile cannot target another student');
    $payload=['csrf_token'=>$student_token,'action'=>'enroll','academic_year'=>'2026-2027','semester'=>'First semester','section_id'=>(string)$section_id,'subjects'=>[(string)$subject_id]];
    [$code]=request('student','student/index.php?page=enroll',$payload); verify($code===303,'Student enrollment submitted');
    $enrollment=row('SELECT * FROM enrollment_requests WHERE student_id = ?',[$student_id]);
    $ids['enrollments'][]=$enrollment['id'];
    verify(total('SELECT COUNT(*) FROM enrollment_items WHERE request_id = ?',[$enrollment['id']])===1,'Enrollment subjects persisted');
    [$code,$body]=request('student','student/index.php?page=enroll',$payload); verify($code===200 && strpos($body,'already have a pending')!==false,'Duplicate term rejected');
    [$code,$body]=request('admin','admin/index.php?page=enrollments&view='.$enrollment['id']); verify($code===200 && strpos($body,'Review enrollment')!==false,'Admin enrollment detail');
    [$code]=request('admin','admin/index.php?page=enrollments&view='.$enrollment['id'],['csrf_token'=>$admin_token,'action'=>'decide','id'=>(string)$enrollment['id'],'decision'=>'approved','notes'=>'Test approval']);
    verify($code===303 && row('SELECT status FROM enrollment_requests WHERE id = ?',[$enrollment['id']])['status']==='approved','Admin approval persisted');
    [$code,$body]=request('student','student/index.php?page=schedules'); verify($code===200 && strpos($body,$prefix.'_sub')!==false,'Approved class appears in schedule');
    [$code,$body]=request('student','student/index.php?page=enrollments&view='.$enrollment['id'],['csrf_token'=>$student_token,'action'=>'withdraw','id'=>(string)$enrollment['id']]);
    verify($code===200 && strpos($body,'Only your pending')!==false,'Approved enrollment cannot be withdrawn');
    db("INSERT INTO enrollment_requests (student_id,section_id,academic_year,semester,status) VALUES (?,?,'2026-2027','First semester','pending')",[$other_id,$section_id]);
    $other_request=$conn->insert_id; $ids['enrollments'][]=$other_request;
    db("INSERT INTO enrollment_items (request_id,subject_id,subject_code,subject_title,units) VALUES (?,?,'TEST','Test',3)",[$other_request,$subject_id]);
    [$code,$body]=request('student','student/index.php?page=enrollments&view='.$other_request); verify($code===404,'Other student enrollment is private');
    [$code,$body]=request('admin','admin/index.php?page=enrollments&view='.$other_request,['csrf_token'=>$admin_token,'action'=>'decide','id'=>(string)$other_request,'decision'=>'approved']);
    verify($code===200 && strpos($body,'maximum capacity')!==false,'Section capacity enforced');
    [$code]=request('admin','admin/index.php?page=enrollments&view='.$other_request,['csrf_token'=>$admin_token,'action'=>'decide','id'=>(string)$other_request,'decision'=>'rejected','notes'=>'Choose another section.']);
    verify($code===303,'Request rejection');
    $payload['semester']='Second semester';
    [$code]=request('student','student/index.php?page=enroll',$payload); verify($code===303,'Second term request');
    $second=row("SELECT id FROM enrollment_requests WHERE student_id = ? AND semester='Second semester'",[$student_id]); $ids['enrollments'][]=$second['id'];
    [$code]=request('student','student/index.php?page=enrollments&view='.$second['id'],['csrf_token'=>$student_token,'action'=>'withdraw','id'=>(string)$second['id']]); verify($code===303,'Pending request withdrawal');
    [$code]=request('student','student/index.php?page=enroll',$payload); verify($code===303,'Withdrawn term can be resubmitted');
    foreach (['enrollments','students','courses','subjects','sections','schedules'] as $report_type) {
        $query=http_build_query(['report_type'=>$report_type,'course'=>$course]);
        [$code,$body]=request('admin','admin/index.php?page=reports&'.$query);
        verify($code===200 && strpos($body,'Download CSV')!==false && strpos($body,$course)!==false,'Report preview '.$report_type);
        [$code,$body]=request('admin','admin/export.php?'.$query);
        verify($code===200 && stripos($body,'Content-Type: text/csv; charset=UTF-8')!==false && stripos($body,'Content-Disposition: attachment;')!==false,'CSV download headers '.$report_type);
        $csv=explode("\r\n\r\n",$body,2)[1];
        verify(str_starts_with($csv,"\xEF\xBB\xBF") && strpos($csv,$course)!==false && strpos($csv,'password_hash')===false,'CSV data '.$report_type);
        [$code,$body]=request('admin','admin/report-print.php?'.$query);
        verify($code===200 && strpos($body,$course)!==false && strpos($body,'id="print-report"')!==false,'Printable report '.$report_type);
    }
    [$code,$body]=request('admin','admin/export.php?'.http_build_query(['report_type'=>'enrollments','course'=>$course,'status'=>'approved','semester'=>'First semester','academic_year'=>'2026-2027']));
    $csv=explode("\r\n\r\n",$body,2)[1];
    $csv_stream=fopen('php://memory','w+'); fwrite($csv_stream,substr($csv,3)); rewind($csv_stream); $csv_rows=[];
    while (($csv_row=fgetcsv($csv_stream,0,',','"',''))!==false) { $csv_rows[]=$csv_row; } fclose($csv_stream);
    verify($code===200 && count($csv_rows)===2 && $csv_rows[1][8]==='approved','Combined CSV filters match exactly one enrollment');
    [$code,$body]=request('admin','admin/export.php?report_type=students&course=nonexistent_'.$prefix);
    $csv=explode("\r\n\r\n",$body,2)[1]; verify($code===200 && substr_count(trim($csv),"\n")===0,'Empty export contains a header only');
    foreach (['report_type=invalid','report_type[]=students','report_type=enrollments&status=invalid','report_type=enrollments&date_from=2026-02-30','report_type=enrollments&date_from=2026-12-01&date_to=2026-01-01'] as $bad_query) {
        [$code]=request('admin','admin/export.php?'.$bad_query); verify($code===400,'Invalid export filters rejected');
    }
    [$code,$body]=request('admin','admin/export.php?'.http_build_query(['report_type'=>'students','course'=>"' OR 1=1 --"]));
    verify($code===200 && strpos(explode("\r\n\r\n",$body,2)[1],$prefix)===false,'Report filter SQL injection rejected');
    $original_name=row('SELECT fname FROM studenttbl WHERE ID = ?',[$student_id])['fname'];
    db('UPDATE studenttbl SET fname = ? WHERE ID = ?',["=HYPERLINK(\"https://example.test\")",$student_id]);
    [$code,$body]=request('admin','admin/export.php?'.http_build_query(['report_type'=>'students','course'=>$course]));
    $csv=explode("\r\n\r\n",$body,2)[1];
    verify($code===200 && strpos($csv,"'=HYPERLINK")!==false,'Spreadsheet formula injection neutralized');
    db('UPDATE studenttbl SET fname = ? WHERE ID = ?',[$original_name,$student_id]);
    $fixture=row('SELECT * FROM studenttbl WHERE ID = ?',[$student_id]); unset($fixture['ID']);
    $extra_students=[];
    for ($n=0;$n<21;$n++) {
        $fixture['studnum']=$prefix.'_page_'.$n;
        db('INSERT INTO studenttbl (`'.implode('`,`',array_keys($fixture)).'`) VALUES ('.implode(',',array_fill(0,count($fixture),'?')).')',array_values($fixture));
        $extra_students[]=$conn->insert_id; $ids['students'][]=$conn->insert_id;
    }
    [$code,$body]=request('admin','admin/index.php?page=reports&'.http_build_query(['report_type'=>'students','course'=>$course,'p'=>2]));
    verify($code===200 && strpos($body,'21–23 of 23')!==false,'Report preview paginates matching records');
    [$code,$body]=request('admin','admin/export.php?'.http_build_query(['report_type'=>'students','course'=>$course,'p'=>2]));
    $csv=explode("\r\n\r\n",$body,2)[1];
    verify($code===200 && substr_count(trim($csv),"\n")===23,'CSV exports all matches beyond preview pagination');
    [$code,$body]=request('admin','admin/report-print.php?'.http_build_query(['report_type'=>'students','course'=>$course,'p'=>2]));
    verify($code===200 && strpos($body,'23 matching records')!==false,'Print report includes all matches');
    foreach ($extra_students as $extra_id) { db('DELETE FROM studenttbl WHERE ID = ?',[$extra_id]); }
    [$code,$body]=request('admin','admin/index.php?page=courses',['csrf_token'=>$admin_token,'action'=>'delete','id'=>(string)$course_id]); verify($code===200 && strpos($body,'in use')!==false,'Referenced course deletion blocked');
    [$code]=request('student','student/index.php?page=settings',['csrf_token'=>$student_token,'action'=>'password','current_password'=>$student_password,'new_password'=>$student_password.'2','confirm_password'=>$student_password.'2']); verify($code===303,'Student password change');
    [$code]=request('student','logout.php',['csrf_token'=>$student_token]); verify($code===303,'Shared logout');
    db('DELETE FROM auth_login_limits WHERE ip_key = ?',[$ip]);
    login_as('student',$prefix,$student_password.'2');
    if (getenv('CAPTURE_PORTAL') === '1') {
        $environment = array_merge(getenv(),['UI_STUDENT_USERNAME'=>$prefix,'UI_STUDENT_PASSWORD'=>$student_password.'2']);
        $process = proc_open(['node',__DIR__.'/ui-check.mjs'],[0=>['pipe','r'],1=>STDOUT,2=>STDERR],$pipes,dirname(__DIR__),$environment);
        if (!is_resource($process) || proc_close($process) !== 0) { throw new RuntimeException('Browser capture failed'); }
    }
    echo "All portal integration checks passed.\n";
} finally {
    foreach ($ids['enrollments']??[] as $id) { db('DELETE FROM enrollment_requests WHERE id = ?',[$id]); }
    foreach (['schedules','users','students','sections','subjects','courses'] as $module) {
        $spec=portal_modules()[$module];
        foreach ($ids[$module]??[] as $id) { db('DELETE FROM `'.$spec['table'].'` WHERE `'.$spec['pk'].'` = ?',[$id]); }
    }
    db('DELETE FROM auth_login_limits WHERE ip_key = ?',[$ip]);
    if ($prior) { db('INSERT INTO auth_login_limits (ip_key,attempts,window_started) VALUES (?,?,?)',[$ip,$prior['attempts'],$prior['window_started']]); }
    foreach ($jars as $jar) { if (is_file($jar)) { unlink($jar); } }
}
