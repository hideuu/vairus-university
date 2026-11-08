<?php
require __DIR__.'/../../includes/auth.php';
require_admin();
require __DIR__.'/../../includes/conn.php';
require __DIR__.'/../../includes/portal_data.php';
require __DIR__.'/../../includes/report.php';
try {
    $filters=report_filters($_GET);
    $query=report_query($filters);
    $generated=report_time('Y-m-d-His');
    $statement=db('SELECT '.$query['select'].' FROM '.$query['from'].$query['where'].' ORDER BY '.$query['order'],$query['params']);
} catch (DomainException $exception) {
    http_response_code(400); exit($exception->getMessage());
} catch (Throwable $exception) {
    error_log('Report export failed: '.$exception->getMessage());
    http_response_code(503); exit('The report could not be generated. Please try again.');
}
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="vairus-'.$filters['report_type'].'-'.$generated.'.csv"');
$output=fopen('php://output','w');
fwrite($output,"\xEF\xBB\xBF"); // UTF-8 BOM for spreadsheet compatibility.
fputcsv($output,array_values($query['columns']),',','"','');
$values=array_fill(0,count($query['columns']),null);
$statement->bind_result(...$values);
while ($statement->fetch()) { fputcsv($output,array_map('report_csv_cell',$values),',','"',''); }
fclose($output);
exit;
