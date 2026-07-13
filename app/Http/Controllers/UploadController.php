<?php

namespace App\Http\Controllers;

use App\Jobs\ImportExcelJob;
use App\Models\Upload;
use App\Models\UploadLog;
use App\Models\SalesTransaction;
use App\Services\ExcelImportService;
use App\Services\OutputGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class UploadController extends Controller
{
    /**
     * Show the upload page.
     */
    public function index()
    {
        $recentUploads = Upload::orderByDesc('created_at')->take(5)->get();
        return view('upload.index', compact('recentUploads'));
    }

    /**
     * Handle file upload and start processing.
     */
    public function store(Request $request)
    {
        $request->validate([
            'file_daily' => 'nullable|file|mimes:xlsx,xls|max:51200',
            'file_mp' => 'nullable|file|mimes:xlsx,xls|max:51200',
            'file_produk' => 'nullable|file|mimes:xlsx,xls|max:51200',
        ], [
            'file_daily.mimes' => 'File SALES DAILY harus berformat .xlsx atau .xls',
            'file_mp.mimes' => 'File SALES MP harus berformat .xlsx atau .xls',
            'file_produk.mimes' => 'File SALES PRODUK harus berformat .xlsx atau .xls',
            '*.max' => 'Ukuran file maksimal 50MB',
        ]);

        // At least one file must be uploaded
        if (!$request->hasFile('file_daily') && !$request->hasFile('file_mp') && !$request->hasFile('file_produk')) {
            return back()->with('error', 'Minimal satu file harus diupload.');
        }

        $batchCode = 'BATCH-' . date('Ymd-His') . '-' . strtoupper(Str::random(4));

        $fileDaily = null;
        $fileMp = null;
        $fileProduk = null;

        if ($request->hasFile('file_daily')) {
            $fileDaily = $request->file('file_daily')->store('uploads', 'local');
            $fileDaily = str_replace('uploads/', '', $fileDaily);
        }

        if ($request->hasFile('file_mp')) {
            $fileMp = $request->file('file_mp')->store('uploads', 'local');
            $fileMp = str_replace('uploads/', '', $fileMp);
        }

        if ($request->hasFile('file_produk')) {
            $fileProduk = $request->file('file_produk')->store('uploads', 'local');
            $fileProduk = str_replace('uploads/', '', $fileProduk);
        }

        $upload = Upload::create([
            'batch_code' => $batchCode,
            'file_daily' => $fileDaily,
            'file_mp' => $fileMp,
            'file_produk' => $fileProduk,
            'status' => 'pending',
        ]);

        // Dispatch to queue
        ImportExcelJob::dispatch($upload);

        return redirect()->route('upload.show', $upload->id)
            ->with('success', "Upload berhasil! Batch {$batchCode} sedang diproses.");
    }

    /**
     * Show upload progress and result.
     */
    public function show(Upload $upload)
    {
        $upload->load(['logs' => function ($q) {
            $q->orderByDesc('created_at');
        }]);

        $errorLogs = $upload->logs()->where('level', 'error')->get();
        $warningLogs = $upload->logs()->where('level', 'warning')->get();
        $infoLogs = $upload->logs()->where('level', 'info')->get();

        // Check for output files
        $outputFiles = $this->getOutputFiles($upload);

        return view('upload.show', compact('upload', 'errorLogs', 'warningLogs', 'infoLogs', 'outputFiles'));
    }

    /**
     * API endpoint for polling upload progress.
     */
    public function progress(Upload $upload)
    {
        return response()->json([
            'id' => $upload->id,
            'batch_code' => $upload->batch_code,
            'status' => $upload->status,
            'total_rows' => $upload->total_rows,
            'processed_rows' => $upload->processed_rows,
            'success_rows' => $upload->success_rows,
            'error_rows' => $upload->error_rows,
            'warning_rows' => $upload->warning_rows,
            'progress_percentage' => $upload->progress_percentage,
            'completed_at' => $upload->completed_at?->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Rollback an upload — delete all its transactions.
     */
    public function rollback(Upload $upload)
    {
        if ($upload->status !== 'completed') {
            return back()->with('error', 'Hanya upload yang completed yang dapat di-rollback.');
        }

        $deletedCount = SalesTransaction::where('upload_id', $upload->id)->delete();
        $upload->update([
            'status' => 'failed',
            'error_summary' => "Rollback manual: {$deletedCount} transaksi dihapus pada " . now()->format('Y-m-d H:i:s'),
        ]);

        UploadLog::create([
            'upload_id' => $upload->id,
            'file_source' => 'system',
            'level' => 'warning',
            'message' => "Rollback dilakukan: {$deletedCount} transaksi dihapus.",
        ]);

        return back()->with('success', "Rollback berhasil. {$deletedCount} transaksi telah dihapus.");
    }

    /**
     * Get output files for a given upload.
     */
    private function getOutputFiles(Upload $upload): array
    {
        $outputDir = storage_path('app/private/outputs');
        $files = [];

        $financeFile = $outputDir . "/FINANCE_{$upload->batch_code}.xlsx";
        $marketingFile = $outputDir . "/MARKETING_{$upload->batch_code}.xlsx";

        if (file_exists($financeFile)) {
            $files['finance'] = [
                'name' => "FINANCE_{$upload->batch_code}.xlsx",
                'size' => filesize($financeFile),
            ];
        }

        if (file_exists($marketingFile)) {
            $files['marketing'] = [
                'name' => "MARKETING_{$upload->batch_code}.xlsx",
                'size' => filesize($marketingFile),
            ];
        }

        return $files;
    }
}
