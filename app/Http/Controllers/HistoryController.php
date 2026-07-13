<?php

namespace App\Http\Controllers;

use App\Models\Upload;
use App\Models\UploadLog;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class HistoryController extends Controller
{
    /**
     * Show import history.
     */
    public function index()
    {
        $uploads = Upload::orderByDesc('created_at')->paginate(15);
        return view('history.index', compact('uploads'));
    }

    /**
     * Show details for a specific upload.
     */
    public function show(Upload $upload)
    {
        $logs = $upload->logs()->orderByDesc('created_at')->paginate(50);
        $errorCount = $upload->logs()->where('level', 'error')->count();
        $warningCount = $upload->logs()->where('level', 'warning')->count();
        $infoCount = $upload->logs()->where('level', 'info')->count();

        return view('history.show', compact('upload', 'logs', 'errorCount', 'warningCount', 'infoCount'));
    }

    /**
     * Download error report as Excel.
     */
    public function downloadErrorReport(Upload $upload)
    {
        $errors = $upload->logs()->where('level', 'error')->get();
        $warnings = $upload->logs()->where('level', 'warning')->get();

        $spreadsheet = new Spreadsheet();

        // Error sheet
        $errorSheet = $spreadsheet->getActiveSheet();
        $errorSheet->setTitle('Errors');
        $this->writeLogSheet($errorSheet, $errors, 'FF4444');

        // Warning sheet
        $warningSheet = $spreadsheet->createSheet();
        $warningSheet->setTitle('Warnings');
        $this->writeLogSheet($warningSheet, $warnings, 'FFAA00');

        $filename = "Error_Report_{$upload->batch_code}.xlsx";
        $path = storage_path("app/private/outputs/{$filename}");

        $writer = new Xlsx($spreadsheet);
        $writer->save($path);
        $spreadsheet->disconnectWorksheets();

        return response()->download($path, $filename)->deleteFileAfterSend(false);
    }

    /**
     * Write logs to a sheet.
     */
    private function writeLogSheet($sheet, $logs, string $headerColor): void
    {
        $headers = ['No', 'File Source', 'Row Number', 'Level', 'Message', 'Raw Data', 'Timestamp'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue("{$col}1", $header);
            $col++;
        }

        $sheet->getStyle('A1:G1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => $headerColor],
            ],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN],
            ],
        ]);

        $row = 2;
        foreach ($logs as $idx => $log) {
            $sheet->setCellValue("A{$row}", $idx + 1);
            $sheet->setCellValue("B{$row}", $log->file_source);
            $sheet->setCellValue("C{$row}", $log->row_number ?? '-');
            $sheet->setCellValue("D{$row}", $log->level);
            $sheet->setCellValue("E{$row}", $log->message);
            $sheet->setCellValue("F{$row}", $log->raw_data ? json_encode($log->raw_data, JSON_UNESCAPED_UNICODE) : '-');
            $sheet->setCellValue("G{$row}", $log->created_at->format('Y-m-d H:i:s'));
            $row++;
        }

        foreach (range('A', 'G') as $c) {
            $sheet->getColumnDimension($c)->setAutoSize(true);
        }
    }
}
