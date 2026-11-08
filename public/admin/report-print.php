<?php
require __DIR__.'/../../includes/auth.php';
require_admin();
require __DIR__.'/../../includes/conn.php';
require __DIR__.'/../../includes/portal_data.php';
require __DIR__.'/../../includes/portal_view.php';
require __DIR__.'/../../includes/report.php';
try {
    $filters=report_filters($_GET);
    $query=report_query($filters);
    $records=rows('SELECT '.$query['select'].' FROM '.$query['from'].$query['where'].' ORDER BY '.$query['order'],$query['params']);
} catch (DomainException $exception) { http_response_code(400); exit(escape_html($exception->getMessage())); }
catch (Throwable $exception) { error_log('Printable report failed: '.$exception->getMessage()); http_response_code(503); exit('The report could not be generated. Please try again.'); }
$title=report_types()[$filters['report_type']];
?><!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title><?= escape_html($title) ?> | Vairus University</title><link rel="stylesheet" href="../styles.css"><link rel="stylesheet" href="../portal.css"><link rel="stylesheet" href="report-print.css"><script src="report-print.js" defer></script></head>
<body class="portal-body report-print-body">
<main class="report-print-main">
<div class="report-print-actions"><?= link_button('index.php?'.http_build_query(array_merge($filters,['page'=>'reports'])),'Back to reports',false) ?><button class="button button-primary" type="button" id="print-report">Print / Save as PDF</button></div>
<p class="report-university">Vairus University</p>
<h1><?= escape_html($title) ?></h1>
<p class="report-caption"><?= count($records) ?> matching records · Generated <?= escape_html(report_time('M j, Y, g:i a')) ?></p>
<dl class="report-filter-summary">
<?php foreach ($filters as $key=>$value): if ($key!=='report_type' && $value!==''): ?><div><dt><?= escape_html(ucfirst(str_replace('_',' ',$key))) ?></dt><dd><?= escape_html($value) ?></dd></div><?php endif; endforeach; ?>
</dl>
<?php if ($records): render_report_table($query['columns'],$records,true); else: ?><p>No matching records.</p><?php endif; ?>
</main>
</body>
</html>
