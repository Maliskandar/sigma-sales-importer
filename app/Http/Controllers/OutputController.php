<?php

namespace App\Http\Controllers;

use App\Models\Upload;
use Illuminate\Http\Request;

class OutputController extends Controller
{
    /**
     * Show output files page.
     */
    public function index()
    {
        $uploads = Upload::where('status', 'completed')
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('output.index', compact('uploads'));
    }

    /**
     * Download an output file.
     */
    public function download(Upload $upload, string $type)
    {
        $outputDir = storage_path('app/private/outputs');

        $fileMap = [
            'finance' => "FINANCE_{$upload->batch_code}.xlsx",
            'marketing' => "MARKETING_{$upload->batch_code}.xlsx",
        ];

        if (!isset($fileMap[$type])) {
            abort(404, 'Tipe file tidak valid.');
        }

        $filePath = $outputDir . '/' . $fileMap[$type];

        if (!file_exists($filePath)) {
            return back()->with('error', 'File output belum tersedia. Pastikan proses import berhasil.');
        }

        return response()->download($filePath, $fileMap[$type]);
    }
}
