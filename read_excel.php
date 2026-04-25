<?php
set_time_limit(300);
ini_set('memory_limit', '512M');
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Reader\Xlsx;

$reader = new Xlsx();
$reader->setReadDataOnly(true);

$file = '6. ยอดรายวันและ Productivity มี.ค. 68.xlsx';
$spreadsheet = $reader->load($file);
$names = $spreadsheet->getSheetNames();
echo 'Sheets: ' . count($names) . PHP_EOL;
foreach ($names as $i => $n) { echo $i . ': ' . $n . PHP_EOL; }

// Read first sheet header rows
$sheet = $spreadsheet->getActiveSheet();
echo PHP_EOL . 'Active: ' . $sheet->getTitle() . PHP_EOL;
echo 'MaxRow: ' . $sheet->getHighestRow() . ', MaxCol: ' . $sheet->getHighestColumn() . PHP_EOL . PHP_EOL;

for ($row = 1; $row <= min(60, $sheet->getHighestRow()); $row++) {
    $cols = [];
    for ($col = 'A'; $col <= 'Z'; $col++) {
        $val = $sheet->getCell($col . $row)->getValue();
        if ($val !== null && $val !== '') $cols[$col] = $val;
        if ($col === 'Z') break;
    }
    if (!empty($cols)) {
        echo "R$row: ";
        foreach ($cols as $c => $v) echo "$c=" . str_replace(["\n","\r"], ' ', $v) . " | ";
        echo PHP_EOL;
    }
}
