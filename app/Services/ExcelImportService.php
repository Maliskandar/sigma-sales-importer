<?php

namespace App\Services;

use App\Models\ColumnMapping;
use App\Models\Product;
use App\Models\SalesTransaction;
use App\Models\Upload;
use App\Models\UploadLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class ExcelImportService
{
    private array $fileTypeMap = [
        'daily' => 'SALES DAILY',
        'mp' => 'SALES MP',
        'produk' => 'SALES PRODUK',
    ];

    /**
     * Process all uploaded Excel files for a given upload batch.
     */
    public function processUpload(Upload $upload): void
    {
        $upload->update(['status' => 'processing']);

        $totalRows = 0;
        $processedRows = 0;
        $successRows = 0;
        $errorRows = 0;
        $warningRows = 0;

        $filesToProcess = [];
        if ($upload->file_daily) $filesToProcess['daily'] = storage_path('app/private/uploads/' . $upload->file_daily);
        if ($upload->file_mp) $filesToProcess['mp'] = storage_path('app/private/uploads/' . $upload->file_mp);
        if ($upload->file_produk) $filesToProcess['produk'] = storage_path('app/private/uploads/' . $upload->file_produk);

        // First pass: count total rows
        foreach ($filesToProcess as $fileType => $filePath) {
            if (!file_exists($filePath)) {
                $this->logEntry($upload, $fileType, null, 'error', "File tidak ditemukan: {$filePath}");
                continue;
            }
            $spreadsheet = IOFactory::load($filePath);
            $worksheet = $spreadsheet->getActiveSheet();
            // Hitung hanya baris yang benar-benar berisi data. Baris kosong yang sekadar
            // punya sisa format membuat getHighestRow() membengkak sampai ratusan/ribuan.
            $totalRows += $this->countDataRows($worksheet); // exclude header
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);
        }

        $upload->update(['total_rows' => $totalRows]);

        // Second pass: process each file
        foreach ($filesToProcess as $fileType => $filePath) {
            if (!file_exists($filePath)) continue;

            try {
                $result = $this->processFile($upload, $fileType, $filePath);
                $processedRows += $result['processed'];
                $successRows += $result['success'];
                $errorRows += $result['errors'];
                $warningRows += $result['warnings'];

                $upload->update([
                    'processed_rows' => $processedRows,
                    'success_rows' => $successRows,
                    'error_rows' => $errorRows,
                    'warning_rows' => $warningRows,
                ]);
            } catch (\Exception $e) {
                $this->logEntry($upload, $fileType, null, 'error', "Gagal memproses file {$this->fileTypeMap[$fileType]}: {$e->getMessage()}");
                $errorRows++;
                Log::error("Excel import error", ['file_type' => $fileType, 'error' => $e->getMessage()]);
            }
        }

        $status = $errorRows > 0 && $successRows === 0 ? 'failed' : 'completed';
        $upload->update([
            'status' => $status,
            'processed_rows' => $processedRows,
            'success_rows' => $successRows,
            'error_rows' => $errorRows,
            'warning_rows' => $warningRows,
            'completed_at' => now(),
        ]);
    }

    /**
     * Process a single Excel file.
     */
    private function processFile(Upload $upload, string $fileType, string $filePath): array
    {
        $result = ['processed' => 0, 'success' => 0, 'errors' => 0, 'warnings' => 0];

        $spreadsheet = IOFactory::load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();

        // Get column mapping from DB
        $columnMappings = ColumnMapping::getMappingFor($fileType);
        $requiredColumns = ColumnMapping::getRequiredColumns($fileType);

        // Read header row
        $headerRow = [];
        $highestColumn = $worksheet->getHighestColumn();
        $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);

        for ($col = 1; $col <= $highestColumnIndex; $col++) {
            $value = $worksheet->getCellByColumnAndRow($col, 1)->getValue();
            if ($value !== null) {
                $headerRow[$col] = trim((string)$value);
            }
        }

        // Validate headers
        $missingRequired = [];
        foreach ($requiredColumns as $reqCol) {
            if (!in_array($reqCol, $headerRow)) {
                $missingRequired[] = $reqCol;
            }
        }

        if (!empty($missingRequired)) {
            $this->logEntry($upload, $fileType, null, 'error',
                "Kolom wajib tidak ditemukan: " . implode(', ', $missingRequired));
            $result['errors']++;
            $spreadsheet->disconnectWorksheets();
            return $result;
        }

        $dataRowCount = $this->countDataRows($worksheet);
        $this->logEntry($upload, $fileType, null, 'info',
            "Memulai proses file {$this->fileTypeMap[$fileType]} ({$dataRowCount} baris)");

        // Process data rows in chunks. Pakai getHighestDataRow() agar tidak
        // mengiterasi ribuan baris kosong berformat.
        $highestRow = $worksheet->getHighestDataRow();
        $chunkSize = 100;
        $insertBatch = [];

        for ($row = 2; $row <= $highestRow; $row++) {
            // Read row data
            $rowData = [];
            $rawRow = [];
            $allEmpty = true;

            for ($col = 1; $col <= $highestColumnIndex; $col++) {
                if (!isset($headerRow[$col])) continue;
                $cellValue = $worksheet->getCellByColumnAndRow($col, $row)->getValue();

                // Sel teks bisa dikembalikan sebagai objek RichText. Ubah ke string biasa
                // agar pembersihan data (trim, '-'/'' -> null) & validasi empty() bekerja.
                if ($cellValue instanceof \PhpOffice\PhpSpreadsheet\RichText\RichText) {
                    $cellValue = $cellValue->getPlainText();
                }

                // Handle Excel date serial numbers
                if ($headerRow[$col] === 'Date' && is_numeric($cellValue)) {
                    try {
                        $dateObj = ExcelDate::excelToDateTimeObject($cellValue);
                        $cellValue = $dateObj->format('Y-m-d');
                    } catch (\Exception $e) {
                        // Keep original value
                    }
                }

                $rawRow[$headerRow[$col]] = $cellValue;
                if ($cellValue !== null && $cellValue !== '') {
                    $allEmpty = false;
                }
            }

            // Skip completely empty rows
            if ($allEmpty) continue;

            $result['processed']++;

            // Map Excel columns to DB columns using column_mappings table
            $mappedData = $this->mapRowData($rawRow, $columnMappings, $fileType);

            // Validate mapped data
            $validationErrors = $this->validateRow($mappedData, $fileType, $row);
            if (!empty($validationErrors)) {
                foreach ($validationErrors as $error) {
                    $this->logEntry($upload, $fileType, $row, 'error', $error, $rawRow);
                }
                $result['errors']++;
                continue;
            }

            // Check for warnings
            $warnings = $this->checkWarnings($mappedData, $fileType, $row);
            if (!empty($warnings)) {
                foreach ($warnings as $warning) {
                    $this->logEntry($upload, $fileType, $row, 'warning', $warning, $rawRow);
                }
                $result['warnings']++;
            }

            // Add to batch
            $mappedData['upload_id'] = $upload->id;
            $mappedData['file_source'] = $fileType;
            $mappedData['created_at'] = now();
            $mappedData['updated_at'] = now();
            $insertBatch[] = $mappedData;

            // Bulk insert every chunk
            if (count($insertBatch) >= $chunkSize) {
                $inserted = $this->bulkUpsert($insertBatch, $upload, $fileType);
                $result['success'] += $inserted;
                $insertBatch = [];

                // Update progress
                $upload->update(['processed_rows' => $upload->processed_rows + $chunkSize]);
            }
        }

        // Insert remaining rows
        if (!empty($insertBatch)) {
            $inserted = $this->bulkUpsert($insertBatch, $upload, $fileType);
            $result['success'] += $inserted;
        }

        $this->logEntry($upload, $fileType, null, 'info',
            "Selesai memproses {$this->fileTypeMap[$fileType]}: {$result['success']} berhasil, {$result['errors']} error, {$result['warnings']} warning");

        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

        return $result;
    }

    /**
     * Hitung jumlah baris data yang benar-benar terisi (di luar header).
     * getHighestRow() bisa membengkak karena baris kosong yang punya sisa format,
     * jadi kita batasi ke area data dan lewati baris yang seluruh selnya kosong.
     */
    private function countDataRows($worksheet): int
    {
        $highestRow = $worksheet->getHighestDataRow();
        $highestCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString(
            $worksheet->getHighestDataColumn()
        );

        $count = 0;
        for ($row = 2; $row <= $highestRow; $row++) {
            for ($col = 1; $col <= $highestCol; $col++) {
                $value = $worksheet->getCellByColumnAndRow($col, $row)->getValue();
                if ($value instanceof \PhpOffice\PhpSpreadsheet\RichText\RichText) {
                    $value = $value->getPlainText();
                }
                if ($value !== null && trim((string) $value) !== '') {
                    $count++;
                    break; // baris ini ada isinya, lanjut ke baris berikutnya
                }
            }
        }

        return $count;
    }

    /**
     * Map raw Excel row data to DB columns using column_mappings table.
     */
    private function mapRowData(array $rawRow, array $columnMappings, string $fileType): array
    {
        $mapped = [];

        foreach ($columnMappings as $excelCol => $dbCol) {
            $value = $rawRow[$excelCol] ?? null;

            // Clean up data
            if (is_string($value)) {
                $value = trim($value);
                if ($value === '' || $value === '-') {
                    $value = null;
                }
            }

            // Handle date formatting
            if ($dbCol === 'sale_date' && $value !== null) {
                if ($value instanceof \DateTimeInterface) {
                    $value = $value->format('Y-m-d');
                } elseif (is_string($value) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
                    try {
                        $value = date('Y-m-d', strtotime($value));
                    } catch (\Exception $e) {
                        // Keep original
                    }
                }
            }

            // Handle numeric values
            if (in_array($dbCol, ['quantity', 'unit_price', 'total_per_line'])) {
                $value = is_numeric($value) ? $value : 0;
            }

            // Handle customer phone — convert to string
            if ($dbCol === 'customer_phone' && $value !== null) {
                $value = (string) $value;
            }

            $mapped[$dbCol] = $value;
        }

        return $mapped;
    }

    /**
     * Validate a single row of mapped data using DB rules.
     */
    private function validateRow(array $data, string $fileType, int $rowNum): array
    {
        $errors = [];

        // Required field validation
        if (empty($data['order_number'])) {
            $errors[] = "Baris {$rowNum}: OrderNumber wajib diisi";
        }

        if (empty($data['product_code'])) {
            $errors[] = "Baris {$rowNum}: ProductCode wajib diisi";
        }

        if (empty($data['sale_date'])) {
            $errors[] = "Baris {$rowNum}: Date wajib diisi";
        }

        // Validate product_code exists in DB
        if (!empty($data['product_code'])) {
            $productExists = Product::where('code', $data['product_code'])->exists();
            if (!$productExists) {
                $errors[] = "Baris {$rowNum}: ProductCode '{$data['product_code']}' tidak terdaftar di database";
            }
        }

        // Validate quantity
        if (isset($data['quantity']) && $data['quantity'] <= 0) {
            $errors[] = "Baris {$rowNum}: Quantity harus lebih dari 0";
        }

        // Validate date format
        if (!empty($data['sale_date'])) {
            $date = date_parse($data['sale_date']);
            if ($date['error_count'] > 0 || !checkdate($date['month'] ?? 0, $date['day'] ?? 0, $date['year'] ?? 0)) {
                $errors[] = "Baris {$rowNum}: Format tanggal tidak valid: {$data['sale_date']}";
            }
        }

        return $errors;
    }

    /**
     * Check for non-fatal warnings.
     */
    private function checkWarnings(array $data, string $fileType, int $rowNum): array
    {
        $warnings = [];

        // Warn if unit_price seems too low or too high
        if (isset($data['unit_price'])) {
            if ($data['unit_price'] == 0) {
                $warnings[] = "Baris {$rowNum}: UnitPrice bernilai 0";
            }
            if ($data['unit_price'] > 10000000) {
                $warnings[] = "Baris {$rowNum}: UnitPrice sangat tinggi ({$data['unit_price']})";
            }
        }

        // Warn if total_per_line doesn't match quantity * unit_price
        if (isset($data['quantity'], $data['unit_price'], $data['total_per_line'])) {
            $expected = $data['quantity'] * $data['unit_price'];
            if (abs($expected - $data['total_per_line']) > 1) {
                $warnings[] = "Baris {$rowNum}: Totalperline ({$data['total_per_line']}) tidak sama dengan Quantity x UnitPrice ({$expected})";
            }
        }

        return $warnings;
    }

    /**
     * Bulk upsert rows into sales_transactions.
     * Uses INSERT ... ON DUPLICATE KEY UPDATE for re-import handling.
     */
    private function bulkUpsert(array $rows, Upload $upload, string $fileType): int
    {
        $inserted = 0;

        DB::beginTransaction();
        try {
            foreach ($rows as $row) {
                $result = SalesTransaction::updateOrCreate(
                    [
                        'order_number' => $row['order_number'],
                        'product_code' => $row['product_code'],
                        'file_source' => $row['file_source'],
                    ],
                    $row
                );

                if ($result->wasRecentlyCreated || $result->wasChanged()) {
                    $inserted++;
                }
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            $this->logEntry($upload, $fileType, null, 'error',
                "Gagal menyimpan batch ke database: {$e->getMessage()}");
            Log::error("Bulk upsert error", ['error' => $e->getMessage()]);
        }

        return $inserted;
    }

    /**
     * Log an entry for the upload process.
     */
    private function logEntry(Upload $upload, string $fileSource, ?int $rowNum, string $level, string $message, ?array $rawData = null): void
    {
        UploadLog::create([
            'upload_id' => $upload->id,
            'file_source' => $fileSource,
            'row_number' => $rowNum,
            'level' => $level,
            'message' => $message,
            'raw_data' => $rawData,
        ]);
    }
}
