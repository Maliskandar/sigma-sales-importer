<?php

namespace App\Services;

use App\Models\BundleItem;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\SalesTransaction;
use App\Models\Upload;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class OutputGeneratorService
{
    private string $outputDir;

    public function __construct()
    {
        $this->outputDir = storage_path('app/private/outputs');
        if (!is_dir($this->outputDir)) {
            mkdir($this->outputDir, 0755, true);
        }
    }

    /**
     * Generate both output files for a given upload batch.
     * Returns array of file paths.
     */
    public function generate(Upload $upload): array
    {
        $transactions = SalesTransaction::where('upload_id', $upload->id)
            ->orderBy('sale_date')
            ->orderBy('order_number')
            ->get();

        if ($transactions->isEmpty()) {
            return [];
        }

        $files = [];

        // Output 1: Sales Summary per Order
        $files['summary'] = $this->generateSalesSummary($upload, $transactions);

        // Output 2: Sales Detail per Produk (with bundling breakdown)
        $files['detail'] = $this->generateSalesDetail($upload, $transactions);

        return $files;
    }

    /**
     * Output 1: Sales Summary — all transactions consolidated per order.
     */
    private function generateSalesSummary(Upload $upload, $transactions): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Sales Summary');

        // Headers
        $headers = [
            'No', 'Date', 'Source', 'Group', 'Kanal', 'Metode Bayar', 'Toko',
            'ADV', 'Type Transaksi', 'Order Number', 'AWB',
            'Customer Name', 'Provinsi', 'Kabupaten',
            'Product Code', 'Product Name', 'Is Bundle', 'Qty', 'Unit Price',
            'Total Per Line', 'Ekspedisi', 'Status Order'
        ];

        $this->writeHeaders($sheet, $headers);

        // Data rows
        $row = 2;
        $no = 1;
        foreach ($transactions as $trx) {
            $product = Product::where('code', $trx->product_code)->first();

            $sheet->setCellValue("A{$row}", $no);
            $sheet->setCellValue("B{$row}", $trx->sale_date->format('Y-m-d'));
            $sheet->setCellValue("C{$row}", strtoupper($trx->file_source));
            $sheet->setCellValue("D{$row}", $trx->group_code);
            $sheet->setCellValue("E{$row}", $trx->kanal);
            $sheet->setCellValue("F{$row}", $trx->metode_bayar);
            $sheet->setCellValue("G{$row}", $trx->toko);
            $sheet->setCellValue("H{$row}", $trx->adv);
            $sheet->setCellValue("I{$row}", $trx->type_transaksi);
            $sheet->setCellValue("J{$row}", $trx->order_number);
            $sheet->setCellValue("K{$row}", $trx->awb);
            $sheet->setCellValue("L{$row}", $trx->customer_name);
            $sheet->setCellValue("M{$row}", $trx->provinsi);
            $sheet->setCellValue("N{$row}", $trx->kabupaten);
            $sheet->setCellValue("O{$row}", $trx->product_code);
            $sheet->setCellValue("P{$row}", $product ? $product->name : '-');
            $sheet->setCellValue("Q{$row}", $product && $product->is_bundle ? 'Ya' : 'Tidak');
            $sheet->setCellValue("R{$row}", $trx->quantity);
            $sheet->setCellValue("S{$row}", $trx->unit_price);
            $sheet->setCellValue("T{$row}", $trx->total_per_line);
            $sheet->setCellValue("U{$row}", $trx->ekspedisi);
            $sheet->setCellValue("V{$row}", $trx->status_order);

            // Number formatting
            $sheet->getStyle("S{$row}:T{$row}")->getNumberFormat()
                ->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);

            $row++;
            $no++;
        }

        // Summary row
        $row++;
        $sheet->setCellValue("Q{$row}", 'TOTAL:');
        $sheet->setCellValue("R{$row}", $transactions->sum('quantity'));
        $sheet->setCellValue("T{$row}", $transactions->sum('total_per_line'));
        $sheet->getStyle("Q{$row}:T{$row}")->getFont()->setBold(true);
        $sheet->getStyle("T{$row}")->getNumberFormat()
            ->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);

        // Auto-size columns
        foreach (range('A', 'V') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Save file
        $filename = "Sales_Summary_Batch_{$upload->batch_code}.xlsx";
        $filepath = $this->outputDir . '/' . $filename;
        $writer = new Xlsx($spreadsheet);
        $writer->save($filepath);
        $spreadsheet->disconnectWorksheets();

        return $filename;
    }

    /**
     * Output 2: Sales Detail per Produk — includes bundling breakdown and HPP.
     */
    private function generateSalesDetail(Upload $upload, $transactions): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Sales Detail Produk');

        // Headers
        $headers = [
            'No', 'Date', 'Order Number', 'Kanal', 'Toko',
            'Product Code', 'Product Name', 'Is Bundle',
            'Bundle Item Code', 'Bundle Item Name', 'Bundle Item Qty',
            'Qty', 'Selling Price', 'HPP', 'Total Selling', 'Total HPP', 'Profit'
        ];

        $this->writeHeaders($sheet, $headers);

        // Data rows
        $row = 2;
        $no = 1;
        $totalSelling = 0;
        $totalHpp = 0;
        $totalProfit = 0;

        foreach ($transactions as $trx) {
            $product = Product::where('code', $trx->product_code)->first();

            if ($product && $product->is_bundle) {
                // Expand bundle items
                $bundleItems = BundleItem::where('bundle_product_id', $product->id)
                    ->with('itemProduct')
                    ->get();

                if ($bundleItems->isEmpty()) {
                    // Bundle without items — treat as regular product
                    $this->writeDetailRow($sheet, $row, $no, $trx, $product, null, null);
                    $row++;
                    $no++;
                } else {
                    foreach ($bundleItems as $idx => $bundleItem) {
                        $this->writeDetailRow($sheet, $row, $no, $trx, $product, $bundleItem->itemProduct, $bundleItem->quantity);
                        $row++;
                        if ($idx === 0) $no++;
                    }
                }
            } else {
                // Regular product
                $this->writeDetailRow($sheet, $row, $no, $trx, $product, null, null);
                $row++;
                $no++;
            }
        }

        // Apply number formatting to price columns
        $lastDataRow = $row - 1;
        if ($lastDataRow >= 2) {
            $sheet->getStyle("M2:Q{$lastDataRow}")->getNumberFormat()
                ->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
        }

        // Summary row
        $row++;
        $sheet->setCellValue("N{$row}", 'GRAND TOTAL:');
        $sheet->setCellValue("O{$row}", "=SUM(O2:O{$lastDataRow})");
        $sheet->setCellValue("P{$row}", "=SUM(P2:P{$lastDataRow})");
        $sheet->setCellValue("Q{$row}", "=SUM(Q2:Q{$lastDataRow})");
        $sheet->getStyle("N{$row}:Q{$row}")->getFont()->setBold(true);
        $sheet->getStyle("O{$row}:Q{$row}")->getNumberFormat()
            ->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);

        // Auto-size columns
        foreach (range('A', 'Q') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Save file
        $filename = "Sales_Detail_Produk_Batch_{$upload->batch_code}.xlsx";
        $filepath = $this->outputDir . '/' . $filename;
        $writer = new Xlsx($spreadsheet);
        $writer->save($filepath);
        $spreadsheet->disconnectWorksheets();

        return $filename;
    }

    /**
     * Write a single detail row, handling bundle item breakdown.
     */
    private function writeDetailRow($sheet, int $row, int $no, $trx, ?Product $product, ?Product $bundleItemProduct, ?int $bundleItemQty): void
    {
        $sellingPrice = (float) $trx->unit_price;
        $hpp = $product ? (float) $product->hpp : 0;
        $quantity = (int) $trx->quantity;

        $totalSelling = $sellingPrice * $quantity;
        $totalHppVal = $hpp * $quantity;
        $profit = $totalSelling - $totalHppVal;

        $sheet->setCellValue("A{$row}", $no);
        $sheet->setCellValue("B{$row}", $trx->sale_date->format('Y-m-d'));
        $sheet->setCellValue("C{$row}", $trx->order_number);
        $sheet->setCellValue("D{$row}", $trx->kanal);
        $sheet->setCellValue("E{$row}", $trx->toko);
        $sheet->setCellValue("F{$row}", $trx->product_code);
        $sheet->setCellValue("G{$row}", $product ? $product->name : '-');
        $sheet->setCellValue("H{$row}", $product && $product->is_bundle ? 'Ya' : 'Tidak');
        $sheet->setCellValue("I{$row}", $bundleItemProduct ? $bundleItemProduct->code : '-');
        $sheet->setCellValue("J{$row}", $bundleItemProduct ? $bundleItemProduct->name : '-');
        $sheet->setCellValue("K{$row}", $bundleItemQty ?? '-');
        $sheet->setCellValue("L{$row}", $quantity);
        $sheet->setCellValue("M{$row}", $sellingPrice);
        $sheet->setCellValue("N{$row}", $hpp);
        $sheet->setCellValue("O{$row}", $totalSelling);
        $sheet->setCellValue("P{$row}", $totalHppVal);
        $sheet->setCellValue("Q{$row}", $profit);
    }

    /**
     * Write styled header row.
     */
    private function writeHeaders($sheet, array $headers): void
    {
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue("{$col}1", $header);
            $col++;
        }

        $lastCol = chr(ord('A') + count($headers) - 1);
        $headerRange = "A1:{$lastCol}1";

        $sheet->getStyle($headerRange)->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1a56db'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
        ]);

        $sheet->setAutoFilter($headerRange);
    }
}
