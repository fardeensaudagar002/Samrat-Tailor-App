<?php
require __DIR__ . '/inc/boot.php';
require_role('master');

$rows = all(JOB_SELECT . ' ORDER BY j.assigned_at');
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="samrat-tailors-' . date('Y-m-d') . '.csv"');
$out = fopen('php://output', 'w');
fwrite($out, "\xEF\xBB\xBF"); // Excel में हिंदी ठीक दिखे
fputcsv($out, ['कॉपी नंबर', 'कपड़ा', 'पीस', 'कारीगर', 'रेट', 'रकम', 'स्थिति', 'दिया', 'पूरा हुआ', 'भुगतान', 'नोट']);
foreach ($rows as $j) {
    fputcsv($out, [
        $j['copy_no'], $j['item_name'], $j['qty'], $j['k_name'], $j['rate'], $j['amount'],
        $j['status'] === 'done' ? 'पूरा' : 'बाकी',
        date('d-m-Y H:i', (int)$j['assigned_at']),
        $j['done_at'] ? date('d-m-Y H:i', (int)$j['done_at']) : '',
        $j['payment_id'] ? 'हुआ' : 'बाकी',
        $j['note'],
    ]);
}
fclose($out);
