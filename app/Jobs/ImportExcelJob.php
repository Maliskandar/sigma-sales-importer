<?php

namespace App\Jobs;

use App\Models\Upload;
use App\Services\ExcelImportService;
use App\Services\OutputGeneratorService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ImportExcelJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300; // 5 minutes
    public int $tries = 1;

    public function __construct(
        public Upload $upload
    ) {}

    public function handle(ExcelImportService $importService, OutputGeneratorService $outputService): void
    {
        Log::info("Starting import job for batch: {$this->upload->batch_code}");

        try {
            // Step 1: Import and process Excel files
            $importService->processUpload($this->upload);

            // Step 2: Generate output files if there are successful imports
            if ($this->upload->success_rows > 0) {
                $outputFiles = $outputService->generate($this->upload);
                Log::info("Generated output files", ['files' => $outputFiles]);
            }

            Log::info("Import job completed for batch: {$this->upload->batch_code}", [
                'success' => $this->upload->success_rows,
                'errors' => $this->upload->error_rows,
                'warnings' => $this->upload->warning_rows,
            ]);
        } catch (\Exception $e) {
            Log::error("Import job failed for batch: {$this->upload->batch_code}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->upload->update([
                'status' => 'failed',
                'error_summary' => $e->getMessage(),
                'completed_at' => now(),
            ]);

            throw $e;
        }
    }
}
