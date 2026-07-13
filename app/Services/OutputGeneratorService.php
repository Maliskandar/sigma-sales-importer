<?php

namespace App\Services;

use App\Models\Platform;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\Region;
use App\Models\SalesTransaction;
use App\Models\Store;
use App\Models\Upload;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

/**
 * Menghasilkan 2 file output sesuai contoh Business Case:
 *   - FINANCE.xlsx   (17 kolom, sudut pandang keuangan)
 *   - MARKETING.xlsx (21 kolom, sudut pandang marketing)
 *
 * Seluruh aturan transformasi (platform, admin, region, HPP per-platform,
 * pemecahan bundle) diambil dari tabel master — bukan hardcode.
 */
class OutputGeneratorService
{
    private const BULAN_ID = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];

    private string $outputDir;

    /** @var array<string,Platform|null> */
    private array $platformCache = [];
    /** @var array<string,Store|null> */
    private array $storeCache = [];
    /** @var array<string,Region|null> */
    private array $regionCache = [];
    /** @var array<string,Product|null> */
    private array $productCache = [];

    public function __construct()
    {
        $this->outputDir = storage_path('app/private/outputs');
        if (!is_dir($this->outputDir)) {
            mkdir($this->outputDir, 0755, true);
        }
    }

    /**
     * Generate kedua file output untuk satu batch upload.
     *
     * @return array{finance:string,marketing:string}|array{}
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

        // Bangun baris output (bundle sudah dipecah) satu kali, dipakai dua file.
        $rows = [];
        foreach ($transactions as $trx) {
            foreach ($this->transformTransaction($trx) as $row) {
                $rows[] = $row;
            }
        }

        return [
            'finance' => $this->writeFinance($upload, $rows),
            'marketing' => $this->writeMarketing($upload, $rows),
        ];
    }

    /**
     * Ubah 1 transaksi menjadi 1+ baris output. Bundle dipecah per komponen.
     *
     * @return array<int,array<string,mixed>>
     */
    private function transformTransaction(SalesTransaction $trx): array
    {
        $platform = $this->resolvePlatform($trx->kanal);
        $store = $this->resolveStore($trx->toko);
        $product = $this->resolveProduct($trx->product_code);

        $base = [
            'tahun'        => (int) $trx->sale_date->format('Y'),
            'bulan'        => self::BULAN_ID[(int) $trx->sale_date->format('n')] ?? $trx->sale_date->format('F'),
            'tgl_closing'  => $trx->sale_date->format('d/m/Y'),
            'tgl_pesanan'  => $trx->sale_date->format('d/m/Y'),
            'invoice'      => $trx->order_number,
            'resi'         => $trx->awb,
            'memo'         => $trx->type_transaksi,
            'region'       => $this->resolveRegion($trx->provinsi),
            'ekspedisi'    => $trx->ekspedisi,
            'advertiser'   => $this->resolveAdvertiser($trx, $store),
            'platform'     => $platform?->output_label ?? $trx->kanal,
            'nama_toko'    => $this->parseStoreCode($trx->toko),
            'admin'        => $store?->admin_name,
            'promo'        => $this->extractPromo($trx->note),
            'payment'      => $platform?->payment_label ?? $trx->metode_bayar,
        ];

        // Produk bundling: pecah menjadi beberapa baris komponen.
        if ($product && $product->is_bundle && $product->components->isNotEmpty()) {
            $rows = [];
            foreach ($product->components as $component) {
                $qty = (int) $trx->quantity * (int) $component->quantity;
                $rows[] = $base + [
                    'produk'          => $component->name,
                    'sku'             => $component->sku,
                    'jumlah'          => $qty,
                    'omzet_finance'   => (float) $component->finance_price * $qty,
                    'omzet_marketing' => (float) $component->marketing_price * $qty,
                    'hpp'             => (float) $component->hpp * $qty,
                ];
            }
            return $rows;
        }

        // Produk tunggal.
        $qty = (int) $trx->quantity;
        $omzet = (float) $trx->total_per_line;
        $hpp = $this->resolveHpp($product, $platform) * $qty;

        return [
            $base + [
                'produk'          => $product?->name ?? $trx->product_code,
                'sku'             => $trx->product_code,
                'jumlah'          => $qty,
                'omzet_finance'   => $omzet,
                'omzet_marketing' => $omzet,
                'hpp'             => $hpp,
            ],
        ];
    }

    /**
     * FINANCE.xlsx — 17 kolom.
     */
    private function writeFinance(Upload $upload, array $rows): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('FINANCE');

        $headers = [
            'Tanggal Closing', 'Tanggal Pesanan', 'No. Invoice', 'No Resi', 'Ekspedisi',
            'Type Transaksi', 'Advertiser', 'Platform', 'Nama Toko', 'Admin',
            'Produk Name', 'Jumlah', 'Omzet', 'HPP Sigma', 'TaxName(%)',
            'Total Bayar', 'Payment type',
        ];
        $this->writeHeaders($sheet, $headers);

        $r = 2;
        foreach ($rows as $row) {
            $sheet->fromArray([
                $row['tgl_closing'],
                $row['tgl_pesanan'],
                $row['invoice'],
                $row['resi'],
                $row['ekspedisi'],
                $row['memo'],
                $row['advertiser'],
                $row['platform'],
                $row['nama_toko'],
                $row['admin'],
                $row['produk'],
                $row['jumlah'],
                $row['omzet_finance'],
                $row['hpp'],
                $row['promo'],
                $row['omzet_finance'], // Total Bayar = Omzet
                $row['payment'],
            ], null, "A{$r}", true);
            $r++;
        }

        $lastRow = $r - 1;
        if ($lastRow >= 2) {
            // Kolom angka: Jumlah(L), Omzet(M), HPP(N), Total Bayar(P)
            foreach (['M', 'N', 'P'] as $col) {
                $sheet->getStyle("{$col}2:{$col}{$lastRow}")->getNumberFormat()
                    ->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
            }
        }
        $this->autoSize($sheet, 'A', 'Q');

        return $this->save($spreadsheet, 'FINANCE', $upload);
    }

    /**
     * MARKETING.xlsx — 21 kolom.
     */
    private function writeMarketing(Upload $upload, array $rows): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('MARKETING');

        $headers = [
            'Tahun', 'Bulan', 'Tanggal Closing', 'Tanggal Pesanan', 'No. Invoice',
            'No. Resi', 'Memo', 'Region', 'Ekspedisi', 'Advertiser',
            'Platform', 'Nama Toko', 'Admin', 'Produk', 'Jumlah',
            'Omzet', 'HPP', 'Kode Promo', 'Total Bayar', 'Metode Pembayaran', 'SKU',
        ];
        $this->writeHeaders($sheet, $headers);

        $r = 2;
        foreach ($rows as $row) {
            $sheet->fromArray([
                $row['tahun'],
                $row['bulan'],
                $row['tgl_closing'],
                $row['tgl_pesanan'],
                $row['invoice'],
                $row['resi'],
                $row['memo'],
                $row['region'],
                $row['ekspedisi'],
                $row['advertiser'],
                $row['platform'],
                $row['nama_toko'],
                $row['admin'],
                $row['produk'],
                $row['jumlah'],
                $row['omzet_marketing'],
                $row['hpp'],
                $row['promo'],
                $row['omzet_marketing'], // Total Bayar = Omzet
                $row['payment'],
                $row['sku'],
            ], null, "A{$r}", true);
            $r++;
        }

        $lastRow = $r - 1;
        if ($lastRow >= 2) {
            // Kolom angka: Omzet(P), HPP(Q), Total Bayar(S)
            foreach (['P', 'Q', 'S'] as $col) {
                $sheet->getStyle("{$col}2:{$col}{$lastRow}")->getNumberFormat()
                    ->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
            }
        }
        $this->autoSize($sheet, 'A', 'U');

        return $this->save($spreadsheet, 'MARKETING', $upload);
    }

    // ------------------------------------------------------------------
    // Resolver (semua berbasis tabel master, dengan cache in-memory)
    // ------------------------------------------------------------------

    private function resolvePlatform(?string $kanal): ?Platform
    {
        $key = strtoupper(trim((string) $kanal));
        return $this->platformCache[$key] ??= Platform::resolveByKanal($kanal);
    }

    private function resolveStore(?string $toko): ?Store
    {
        $code = $this->parseStoreCode($toko);
        if ($code === null) {
            return null;
        }
        return $this->storeCache[$code] ??= Store::where('code', $code)->first();
    }

    private function resolveProduct(?string $code): ?Product
    {
        if ($code === null) {
            return null;
        }
        return $this->productCache[$code] ??= Product::with('components')->where('code', $code)->first();
    }

    private function resolveRegion(?string $provinsi): ?string
    {
        if ($provinsi === null || trim($provinsi) === '' || $provinsi === '-') {
            return null;
        }
        $key = strtolower(trim($provinsi));
        $region = $this->regionCache[$key] ??= Region::whereRaw('LOWER(province) = ?', [$key])->first();
        return $region?->region;
    }

    /**
     * HPP per unit dari harga per-platform; fallback ke HPP produk.
     */
    private function resolveHpp(?Product $product, ?Platform $platform): float
    {
        if (!$product) {
            return 0.0;
        }
        if ($platform) {
            $price = ProductPrice::where('product_id', $product->id)
                ->where('platform_id', $platform->id)
                ->first();
            if ($price) {
                return (float) $price->hpp;
            }
        }
        return (float) $product->hpp;
    }

    /**
     * Advertiser dari kolom ADV; bila kosong pakai default advertiser toko
     * (kasus file marketplace yang tak punya kolom ADV).
     */
    private function resolveAdvertiser(SalesTransaction $trx, ?Store $store): ?string
    {
        $adv = $trx->adv !== null ? trim($trx->adv) : '';
        if ($adv !== '') {
            return $adv;
        }
        return $store?->default_advertiser;
    }

    /**
     * Kode toko dari kolom "Toko". Ambil bagian setelah "|" bila ada.
     * "SHOPEE|raya" -> "RAYA", "TIKTOK SHOP|TB" -> "TB", "SC" -> "SC".
     */
    private function parseStoreCode(?string $toko): ?string
    {
        if ($toko === null || trim($toko) === '') {
            return null;
        }
        $parts = explode('|', $toko);
        return strtoupper(trim(end($parts)));
    }

    /**
     * Kode promo dari kolom Note. Ambil segmen terakhir setelah "/".
     * "RN/CO/CODE" -> "CODE", "ZIP" -> "ZIP".
     */
    private function extractPromo(?string $note): ?string
    {
        if ($note === null || trim($note) === '') {
            return null;
        }
        $parts = explode('/', $note);
        return trim(end($parts));
    }

    // ------------------------------------------------------------------
    // Helper penulisan spreadsheet
    // ------------------------------------------------------------------

    private function autoSize($sheet, string $from, string $to): void
    {
        foreach (range($from, $to) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    private function save(Spreadsheet $spreadsheet, string $name, Upload $upload): string
    {
        $filename = "{$name}_{$upload->batch_code}.xlsx";
        $writer = new Xlsx($spreadsheet);
        $writer->save($this->outputDir . '/' . $filename);
        $spreadsheet->disconnectWorksheets();

        return $filename;
    }

    private function writeHeaders($sheet, array $headers): void
    {
        $sheet->fromArray($headers, null, 'A1', true);

        $lastCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($headers));
        $headerRange = "A1:{$lastCol}1";

        $sheet->getStyle($headerRange)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1a56db']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ]);

        $sheet->setAutoFilter($headerRange);
        $sheet->freezePane('A2');
    }
}
