<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\CensusModel;
use App\Models\WardModel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class ImportExportController extends BaseController
{
    protected $censusModel;
    protected $wardModel;

    public function __construct()
    {
        $this->censusModel = new CensusModel();
        $this->wardModel   = new WardModel();
    }

    public function index()
    {
        $wards = $this->wardModel->where('is_active', true)->findAll();

        return view('admin/import_export/index', [
            'title' => 'นำเข้า / ส่งออกข้อมูล',
            'wards' => $wards,
        ]);
    }

    // ─── EXPORT ──────────────────────────────────────────────────────────────

    /**
     * Export census data for a ward and month range to Excel.
     * GET admin/import-export/export?ward_id=&month=&year=
     */
    public function exportCensus()
    {
        $wardId = $this->request->getGet('ward_id');
        $month  = (int) $this->request->getGet('month');
        $year   = (int) $this->request->getGet('year');

        if (!$wardId || !$month || !$year) {
            return redirect()->back()->with('error', 'กรุณาเลือกแผนก เดือน และปี');
        }

        $ward = $this->wardModel->find($wardId);
        if (!$ward) {
            return redirect()->back()->with('error', 'ไม่พบแผนกที่เลือก');
        }

        $rows = $this->censusModel
            ->where('ward_id', $wardId)
            ->where("MONTH(record_date)", $month)
            ->where("YEAR(record_date)", $year)
            ->orderBy('record_date', 'ASC')
            ->orderBy('shift', 'ASC')
            ->findAll();

        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Census Data');

        $thaiMonths = ['', 'มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน',
            'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'];

        // Title
        $sheet->setCellValue('A1', 'ข้อมูลยอดผู้ป่วยรายวัน');
        $sheet->mergeCells('A1:I1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue('A2', 'แผนก: ' . $ward['name'] . '   เดือน: ' . $thaiMonths[$month] . ' ' . ($year + 543));
        $sheet->mergeCells('A2:I2');
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Header row
        $headers = ['ชื่อแผนก', 'วันที่ (YYYY-MM-DD)', 'เวร', 'รับใหม่', 'จำหน่าย', 'รับย้าย', 'ส่งย้าย', 'เสียชีวิต', 'คงเหลือ'];
        $cols    = range('A', 'I');
        foreach ($headers as $i => $header) {
            $sheet->setCellValue($cols[$i] . '4', $header);
        }

        $headerStyle = [
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '005DAC']],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ];
        $sheet->getStyle('A4:I4')->applyFromArray($headerStyle);

        // Data rows
        $rowNum = 5;
        foreach ($rows as $row) {
            $sheet->setCellValue('A' . $rowNum, $ward['name']);
            $sheet->setCellValue('B' . $rowNum, $row['record_date']);
            $sheet->setCellValue('C' . $rowNum, $row['shift']);
            $sheet->setCellValue('D' . $rowNum, $row['admissions']);
            $sheet->setCellValue('E' . $rowNum, $row['discharges']);
            $sheet->setCellValue('F' . $rowNum, $row['transfers_in']);
            $sheet->setCellValue('G' . $rowNum, $row['transfers_out']);
            $sheet->setCellValue('H' . $rowNum, $row['deaths']);
            $sheet->setCellValue('I' . $rowNum, $row['total_patients'] ?? $row['total_remaining'] ?? 0);

            $dataStyle = ['borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]];
            $sheet->getStyle('A' . $rowNum . ':I' . $rowNum)->applyFromArray($dataStyle);

            // Alternate row color
            if ($rowNum % 2 === 0) {
                $sheet->getStyle('A' . $rowNum . ':I' . $rowNum)->getFill()
                    ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F2F3FC');
            }
            $rowNum++;
        }

        foreach ($cols as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $thaiMonth = $thaiMonths[$month];
        $filename  = 'Census_' . $ward['name'] . '_' . $thaiMonth . '_' . $year . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    /**
     * Download blank import template.
     */
    public function downloadTemplate()
    {
        $wards = $this->wardModel->where('is_active', true)->findAll();

        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Template');

        // Instructions sheet
        $instSheet = $spreadsheet->createSheet();
        $instSheet->setTitle('คำอธิบาย');
        $instSheet->setCellValue('A1', 'คำอธิบายการกรอกข้อมูล');
        $instSheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);
        $instructions = [
            ['คอลัมน์', 'คำอธิบาย', 'ตัวอย่าง'],
            ['ชื่อแผนก', 'ชื่อแผนกต้องตรงกับในระบบ', implode(', ', array_column($wards, 'name'))],
            ['วันที่', 'รูปแบบ YYYY-MM-DD', '2025-03-15'],
            ['เวร', 'Morning, Afternoon หรือ Night เท่านั้น', 'Morning'],
            ['รับใหม่', 'จำนวนผู้ป่วยรับใหม่ (ตัวเลข >= 0)', '3'],
            ['จำหน่าย', 'จำนวนผู้ป่วยจำหน่าย', '2'],
            ['รับย้าย', 'รับย้ายจากแผนกอื่น', '1'],
            ['ส่งย้าย', 'ส่งย้ายไปแผนกอื่น', '0'],
            ['เสียชีวิต', 'จำนวนผู้ป่วยเสียชีวิต', '0'],
            ['คงเหลือ', 'จำนวนผู้ป่วยคงเหลือ ณ สิ้นเวร', '25'],
        ];
        foreach ($instructions as $i => $inst) {
            $instSheet->setCellValue('A' . ($i + 2), $inst[0]);
            $instSheet->setCellValue('B' . ($i + 2), $inst[1]);
            $instSheet->setCellValue('C' . ($i + 2), $inst[2]);
        }
        $instSheet->getStyle('A2:C2')->getFont()->setBold(true);
        foreach (['A', 'B', 'C'] as $col) {
            $instSheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Template sheet
        $spreadsheet->setActiveSheetIndex(0);
        $headers = ['ชื่อแผนก', 'วันที่ (YYYY-MM-DD)', 'เวร', 'รับใหม่', 'จำหน่าย', 'รับย้าย', 'ส่งย้าย', 'เสียชีวิต', 'คงเหลือ'];
        $cols    = range('A', 'I');
        foreach ($headers as $i => $header) {
            $sheet->setCellValue($cols[$i] . '1', $header);
        }
        $headerStyle = [
            'font'    => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '005DAC']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ];
        $sheet->getStyle('A1:I1')->applyFromArray($headerStyle);

        // Example row
        $wardName = !empty($wards) ? $wards[0]['name'] : 'ชื่อแผนก';
        $example  = [$wardName, date('Y-m-d'), 'Morning', 0, 0, 0, 0, 0, 0];
        foreach ($example as $i => $val) {
            $sheet->setCellValue($cols[$i] . '2', $val);
        }
        $sheet->getStyle('A2:I2')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFF9C4');

        foreach ($cols as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="Census_Import_Template.xlsx"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    // ─── IMPORT ──────────────────────────────────────────────────────────────

    /**
     * Process uploaded Excel file and import census data.
     * POST admin/import-export/import
     */
    public function importCensus()
    {
        $file = $this->request->getFile('excel_file');

        if (!$file || !$file->isValid() || $file->hasMoved()) {
            return redirect()->back()->with('error', 'กรุณาเลือกไฟล์ Excel ที่ถูกต้อง');
        }

        $ext = strtolower($file->getClientExtension());
        if (!in_array($ext, ['xlsx', 'xls'], true)) {
            return redirect()->back()->with('error', 'รองรับเฉพาะไฟล์ .xlsx และ .xls เท่านั้น');
        }

        // Move to writable/uploads temporarily
        $tmpPath = WRITEPATH . 'uploads/' . $file->getRandomName();
        $file->move(WRITEPATH . 'uploads/', basename($tmpPath));

        try {
            $spreadsheet = IOFactory::load($tmpPath);
        } catch (\Exception $e) {
            unlink($tmpPath);
            return redirect()->back()->with('error', 'ไม่สามารถอ่านไฟล์ได้: ' . $e->getMessage());
        }

        $sheet    = $spreadsheet->getActiveSheet();
        $highRow  = $sheet->getHighestRow();

        // Build ward name → id map
        $allWards  = $this->wardModel->findAll();
        $wardMap   = [];
        foreach ($allWards as $w) {
            $wardMap[trim($w['name'])] = (int) $w['id'];
        }

        $imported = 0;
        $skipped  = 0;
        $errors   = [];

        $validShifts = ['Morning', 'Afternoon', 'Night'];

        for ($r = 2; $r <= $highRow; $r++) {
            $wardName      = trim((string) $sheet->getCell('A' . $r)->getValue());
            $dateCell      = $sheet->getCell('B' . $r);
            $dateVal       = $dateCell->getValue();
            if (is_numeric($dateVal) && $dateVal > 0) {
                $recordDate = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $dateVal)->format('Y-m-d');
            } else {
                $recordDate = trim((string) $dateVal);
            }
            $shift         = trim((string) $sheet->getCell('C' . $r)->getValue());
            $admissions    = (int) $sheet->getCell('D' . $r)->getValue();
            $discharges    = (int) $sheet->getCell('E' . $r)->getValue();
            $transfersIn   = (int) $sheet->getCell('F' . $r)->getValue();
            $transfersOut  = (int) $sheet->getCell('G' . $r)->getValue();
            $deaths        = (int) $sheet->getCell('H' . $r)->getValue();
            $totalRemain   = (int) $sheet->getCell('I' . $r)->getValue();

            // Skip blank rows
            if (empty($wardName) && empty($recordDate)) {
                continue;
            }

            // Validate ward
            if (!isset($wardMap[$wardName])) {
                $errors[] = "แถว {$r}: ไม่พบแผนก '{$wardName}' ในระบบ";
                $skipped++;
                continue;
            }

            // Validate date
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $recordDate)) {
                $errors[] = "แถว {$r}: รูปแบบวันที่ไม่ถูกต้อง '{$recordDate}' (ต้องเป็น YYYY-MM-DD)";
                $skipped++;
                continue;
            }

            // Validate shift
            if (!in_array($shift, $validShifts, true)) {
                $errors[] = "แถว {$r}: เวรไม่ถูกต้อง '{$shift}' (ต้องเป็น Morning, Afternoon หรือ Night)";
                $skipped++;
                continue;
            }

            $wardId = $wardMap[$wardName];

            // Check duplicate (ward + date + shift)
            $existing = $this->censusModel
                ->where('ward_id', $wardId)
                ->where('record_date', $recordDate)
                ->where('shift', $shift)
                ->first();

            if ($existing) {
                // Update existing record
                $this->censusModel->update($existing['id'], [
                    'admissions'      => $admissions,
                    'discharges'      => $discharges,
                    'transfers_in'    => $transfersIn,
                    'transfers_out'   => $transfersOut,
                    'deaths'          => $deaths,
                    'total_patients'  => $totalRemain,
                ]);
            } else {
                $this->censusModel->insert([
                    'ward_id'         => $wardId,
                    'record_date'     => $recordDate,
                    'shift'           => $shift,
                    'admissions'      => $admissions,
                    'discharges'      => $discharges,
                    'transfers_in'    => $transfersIn,
                    'transfers_out'   => $transfersOut,
                    'deaths'          => $deaths,
                    'total_patients'  => $totalRemain,
                    'created_by'      => auth()->id(),
                ]);
            }
            $imported++;
        }

        unlink($tmpPath);

        $message = "นำเข้าสำเร็จ {$imported} รายการ";
        if ($skipped > 0) {
            $message .= ", ข้าม {$skipped} รายการ";
        }

        return redirect()->back()
            ->with('message', $message)
            ->with('import_errors', $errors);
    }

    // ─── CSV EXPORT (ทั้งโรงพยาบาล) ─────────────────────────────────────────

    private const CSV_COLUMNS = [
        'ward_name', 'record_date', 'shift',
        'patients_general_level_5', 'patients_general_level_4', 'patients_general_level_3',
        'patients_general_level_2', 'patients_general_level_1',
        'patients_special_level_5', 'patients_special_level_4', 'patients_special_level_3',
        'patients_special_level_2', 'patients_special_level_1',
        'total_patients', 'carried_forward_patients',
        'admissions', 'discharges', 'transfers_in', 'transfers_out', 'deaths',
        'nurses_hw', 'nurses_rn', 'nurses_tn', 'nurses_pn', 'nurses_aide', 'nurses_ward',
        'equipment_ventilator', 'equipment_hfnc',
        'notes', 'recorder_username',
    ];

    // Columns that exist as real DB fields (excludes derived/joined cols like recorder_username)
    private const CSV_READONLY_COLUMNS = ['recorder_username'];

    /**
     * Export ALL wards for a given month/year to CSV.
     * GET admin/import-export/export-csv?month=&year=
     */
    public function exportCsv()
    {
        $month = (int) $this->request->getGet('month');
        $year  = (int) $this->request->getGet('year');

        if (!$month || $month < 1 || $month > 12 || !$year) {
            return redirect()->back()->with('error', 'กรุณาเลือกเดือนและปีที่ต้องการส่งออก');
        }

        $db      = \Config\Database::connect();
        $builder = $db->table('daily_census dc');

        $selectFields = array_map(function ($col) {
            if ($col === 'ward_name')         return 'wards.name AS ward_name';
            if ($col === 'recorder_username') return 'users.username AS recorder_username';
            return "dc.{$col}";
        }, self::CSV_COLUMNS);

        $builder->select(implode(', ', $selectFields));
        $builder->join('wards', 'wards.id = dc.ward_id', 'left');
        $builder->join('users', 'users.id = dc.created_by', 'left');
        $builder->where('MONTH(dc.record_date)', $month);
        $builder->where('YEAR(dc.record_date)', $year);
        $builder->orderBy('dc.record_date', 'ASC');
        $builder->orderBy('wards.name', 'ASC');
        $builder->orderBy('FIELD(dc.shift,"Night","Morning","Afternoon")', '', false);

        $rows = $builder->get()->getResultArray();

        $mm       = str_pad($month, 2, '0', STR_PAD_LEFT);
        $filename = "Hospital_Census_{$year}_{$mm}.csv";

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $out = fopen('php://output', 'w');

        // UTF-8 BOM for correct Thai display in Excel
        fwrite($out, "\xEF\xBB\xBF");

        fputcsv($out, self::CSV_COLUMNS);

        foreach ($rows as $row) {
            $line = [];
            foreach (self::CSV_COLUMNS as $col) {
                $line[] = $row[$col] ?? '';
            }
            fputcsv($out, $line);
        }

        fclose($out);
        exit;
    }

    // ─── CSV IMPORT (ทั้งโรงพยาบาล) ─────────────────────────────────────────

    /**
     * Import CSV file (hospital-wide census).
     * POST admin/import-export/import-csv
     */
    public function importCsv()
    {
        $file = $this->request->getFile('csv_file');

        if (!$file || !$file->isValid() || $file->hasMoved()) {
            return redirect()->back()->with('error', 'กรุณาเลือกไฟล์ CSV ที่ถูกต้อง');
        }

        if (strtolower($file->getClientExtension()) !== 'csv') {
            return redirect()->back()->with('error', 'รองรับเฉพาะไฟล์ .csv เท่านั้น');
        }

        $tmpPath = WRITEPATH . 'uploads/' . $file->getRandomName();
        $file->move(WRITEPATH . 'uploads/', basename($tmpPath));

        $handle = fopen($tmpPath, 'r');
        if (!$handle) {
            unlink($tmpPath);
            return redirect()->back()->with('error', 'ไม่สามารถเปิดไฟล์ได้');
        }

        // Strip UTF-8 BOM if present
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        // Map header → column index
        $headerRow = fgetcsv($handle);
        if (!$headerRow) {
            fclose($handle);
            unlink($tmpPath);
            return redirect()->back()->with('error', 'ไฟล์ CSV ว่างเปล่าหรือไม่มี header');
        }
        $headerMap = array_flip(array_map('trim', $headerRow));

        $required = ['ward_name', 'record_date', 'shift'];
        foreach ($required as $col) {
            if (!isset($headerMap[$col])) {
                fclose($handle);
                unlink($tmpPath);
                return redirect()->back()->with('error', "ไม่พบคอลัมน์ที่จำเป็น: '{$col}' ในไฟล์ CSV");
            }
        }

        $allWards = $this->wardModel->findAll();
        $wardMap  = [];
        foreach ($allWards as $w) {
            $wardMap[trim($w['name'])] = (int) $w['id'];
        }

        $validShifts = ['Morning', 'Afternoon', 'Night'];
        $imported    = 0;
        $skipped     = 0;
        $errors      = [];
        $lineNum     = 1;

        $numericFields = [
            'patients_general_level_5', 'patients_general_level_4', 'patients_general_level_3',
            'patients_general_level_2', 'patients_general_level_1',
            'patients_special_level_5', 'patients_special_level_4', 'patients_special_level_3',
            'patients_special_level_2', 'patients_special_level_1',
            'total_patients', 'carried_forward_patients',
            'admissions', 'discharges', 'transfers_in', 'transfers_out', 'deaths',
            'nurses_hw', 'nurses_rn', 'nurses_tn', 'nurses_pn', 'nurses_aide', 'nurses_ward',
            'equipment_ventilator', 'equipment_hfnc',
        ];

        while (($row = fgetcsv($handle)) !== false) {
            $lineNum++;

            $get = fn(string $col) => isset($headerMap[$col]) ? trim((string)($row[$headerMap[$col]] ?? '')) : '';

            $wardName   = $get('ward_name');
            $recordDate = $get('record_date');
            $shift      = $get('shift');

            if ($wardName === '' && $recordDate === '') {
                continue;
            }

            if (!isset($wardMap[$wardName])) {
                $errors[] = "แถว {$lineNum}: ไม่พบแผนก '{$wardName}' ในระบบ";
                $skipped++;
                continue;
            }

            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $recordDate)) {
                $errors[] = "แถว {$lineNum}: รูปแบบวันที่ไม่ถูกต้อง '{$recordDate}'";
                $skipped++;
                continue;
            }

            if (!in_array($shift, $validShifts, true)) {
                $errors[] = "แถว {$lineNum}: เวรไม่ถูกต้อง '{$shift}'";
                $skipped++;
                continue;
            }

            $wardId = $wardMap[$wardName];
            $data   = [
                'ward_id'     => $wardId,
                'record_date' => $recordDate,
                'shift'       => $shift,
                'notes'       => $get('notes'),
                // recorder_username is read-only reference info — not written to DB
            ];

            foreach ($numericFields as $field) {
                $val = $get($field);
                $data[$field] = ($val !== '') ? (int) $val : 0;
            }

            $this->censusModel->computeDerived($data);

            $existing = $this->censusModel
                ->where('ward_id', $wardId)
                ->where('record_date', $recordDate)
                ->where('shift', $shift)
                ->first();

            if ($existing) {
                unset($data['ward_id'], $data['record_date'], $data['shift']);
                $this->censusModel->update($existing['id'], $data);
            } else {
                $data['created_by'] = auth()->id();
                $this->censusModel->insert($data);
            }
            $imported++;
        }

        fclose($handle);
        unlink($tmpPath);

        $message = "นำเข้า CSV สำเร็จ {$imported} รายการ";
        if ($skipped > 0) {
            $message .= ", ข้าม {$skipped} รายการ";
        }

        return redirect()->back()
            ->with('message', $message)
            ->with('import_errors', $errors);
    }
}
