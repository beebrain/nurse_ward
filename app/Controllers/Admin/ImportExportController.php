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
        $headers = ['ชื่อแผนก', 'วันที่ (YYYY-MM-DD)', 'กะ', 'รับใหม่', 'จำหน่าย', 'รับย้าย', 'ส่งย้าย', 'เสียชีวิต', 'คงเหลือ'];
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
            $sheet->setCellValue('I' . $rowNum, $row['total_remaining']);

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
            ['กะ', 'Morning, Afternoon หรือ Night เท่านั้น', 'Morning'],
            ['รับใหม่', 'จำนวนผู้ป่วยรับใหม่ (ตัวเลข >= 0)', '3'],
            ['จำหน่าย', 'จำนวนผู้ป่วยจำหน่าย', '2'],
            ['รับย้าย', 'รับย้ายจากแผนกอื่น', '1'],
            ['ส่งย้าย', 'ส่งย้ายไปแผนกอื่น', '0'],
            ['เสียชีวิต', 'จำนวนผู้ป่วยเสียชีวิต', '0'],
            ['คงเหลือ', 'จำนวนผู้ป่วยคงเหลือ ณ สิ้นกะ', '25'],
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
        $headers = ['ชื่อแผนก', 'วันที่ (YYYY-MM-DD)', 'กะ', 'รับใหม่', 'จำหน่าย', 'รับย้าย', 'ส่งย้าย', 'เสียชีวิต', 'คงเหลือ'];
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
            $recordDate    = trim((string) $sheet->getCell('B' . $r)->getValue());
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
                $errors[] = "แถว {$r}: กะไม่ถูกต้อง '{$shift}' (ต้องเป็น Morning, Afternoon หรือ Night)";
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
                    'total_remaining' => $totalRemain,
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
                    'total_remaining' => $totalRemain,
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
}
