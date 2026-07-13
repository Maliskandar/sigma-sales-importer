<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Platform;
use App\Models\Product;
use App\Models\BundleItem;
use App\Models\ProductPrice;
use App\Models\ColumnMapping;
use App\Models\Store;
use App\Models\Region;

/**
 * Master data disusun berdasarkan file input & contoh output (FINANCE / MARKETING)
 * pada Business Case. Semua aturan transformasi (mapping platform, admin toko,
 * region, HPP per-platform, dan komponen bundle) dikendalikan lewat tabel ini,
 * BUKAN di-hardcode di kode — sesuai poin "validasi/normalisasi via DB".
 */
class MasterDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedPlatforms();
        $this->seedProducts();
        $this->seedProductPrices();
        $this->seedBundleItems();
        $this->seedStores();
        $this->seedRegions();
        $this->seedColumnMappings();
    }

    private function seedPlatforms(): void
    {
        $platforms = [
            [
                'code' => 'A', 'name' => 'Website / Direct', 'channel_type' => 'direct',
                'output_label' => 'WEB', 'payment_label' => null, 'aliases' => ['A', 'WEB'],
            ],
            [
                'code' => 'SHOPEE', 'name' => 'Shopee', 'channel_type' => 'marketplace',
                'output_label' => 'SHOPEE', 'payment_label' => 'Shopee', 'aliases' => ['SHOPEE'],
            ],
            [
                'code' => 'TIKTOK', 'name' => 'TikTok Shop', 'channel_type' => 'social_commerce',
                'output_label' => 'TIKTOK SHOP', 'payment_label' => 'Tiktok', 'aliases' => ['TIKTOK SHOP', 'TIKTOK'],
            ],
            [
                'code' => 'TOKOPEDIA', 'name' => 'Tokopedia', 'channel_type' => 'marketplace',
                'output_label' => 'TOKOPEDIA', 'payment_label' => 'Tokopedia', 'aliases' => ['TOKOPEDIA'],
            ],
            [
                'code' => 'LAZADA', 'name' => 'Lazada', 'channel_type' => 'marketplace',
                'output_label' => 'LAZADA', 'payment_label' => 'Lazada', 'aliases' => ['LAZADA'],
            ],
        ];

        foreach ($platforms as $p) {
            Platform::updateOrCreate(['code' => $p['code']], $p);
        }
    }

    private function seedProducts(): void
    {
        $products = [
            ['code' => 'PR01', 'name' => 'PRODUK SATU', 'is_bundle' => false, 'base_price' => 140000, 'hpp' => 56000],
            ['code' => 'BRG01', 'name' => 'BARANG SATU', 'is_bundle' => false, 'base_price' => 90000, 'hpp' => 36000],
            ['code' => 'BDL01', 'name' => 'BUNDLE BOXL (BOXL A + BOXL B)', 'is_bundle' => true, 'base_price' => 280000, 'hpp' => 49500],
        ];

        foreach ($products as $p) {
            Product::updateOrCreate(['code' => $p['code']], $p);
        }
    }

    /**
     * Harga jual & HPP per platform (Petunjuk: harga/HPP bisa berbeda tiap platform).
     * Contoh nyata dari file output: PR01 di WEB HPP 56.000, di SHOPEE HPP 84.000.
     */
    private function seedProductPrices(): void
    {
        $prices = [
            ['product_code' => 'PR01', 'platform_code' => 'A', 'selling_price' => 140000, 'hpp' => 56000],
            ['product_code' => 'PR01', 'platform_code' => 'SHOPEE', 'selling_price' => 147000, 'hpp' => 84000],
            ['product_code' => 'PR01', 'platform_code' => 'TIKTOK', 'selling_price' => 145000, 'hpp' => 56000],

            ['product_code' => 'BRG01', 'platform_code' => 'A', 'selling_price' => 90000, 'hpp' => 36000],
            ['product_code' => 'BRG01', 'platform_code' => 'SHOPEE', 'selling_price' => 95000, 'hpp' => 36000],
        ];

        foreach ($prices as $p) {
            $product = Product::where('code', $p['product_code'])->first();
            $platform = Platform::where('code', $p['platform_code'])->first();
            if ($product && $platform) {
                ProductPrice::updateOrCreate(
                    ['product_id' => $product->id, 'platform_id' => $platform->id],
                    ['selling_price' => $p['selling_price'], 'hpp' => $p['hpp']]
                );
            }
        }
    }

    /**
     * Komponen bundle. 1 baris bundle di input (mis. BDL01) dipecah menjadi
     * beberapa baris produk di output. Omzet FINANCE & MARKETING berbeda
     * (harga list vs harga tagihan), HPP mengikuti komponen.
     */
    private function seedBundleItems(): void
    {
        $bundle = Product::where('code', 'BDL01')->first();
        if (!$bundle) {
            return;
        }

        $items = [
            [
                'sku' => 'BDL01', 'name' => 'BOXL A', 'quantity' => 1, 'sort_order' => 0,
                'finance_price' => 175000, 'marketing_price' => 190000, 'hpp' => 27000,
            ],
            [
                'sku' => 'BDL02', 'name' => 'BOXL B', 'quantity' => 1, 'sort_order' => 1,
                'finance_price' => 93000, 'marketing_price' => 90000, 'hpp' => 22500,
            ],
        ];

        foreach ($items as $item) {
            BundleItem::updateOrCreate(
                ['bundle_product_id' => $bundle->id, 'sku' => $item['sku']],
                $item + ['item_product_id' => null]
            );
        }
    }

    /**
     * Master toko: kode hasil parsing kolom "Toko" -> Admin & Advertiser default.
     * Contoh: "SHOPEE|raya" -> kode RAYA -> admin YAYA, advertiser default ADV EMPAT.
     */
    private function seedStores(): void
    {
        $stores = [
            ['code' => 'SC', 'platform_code' => 'A', 'admin_name' => 'Putri', 'default_advertiser' => null],
            ['code' => 'TB', 'platform_code' => 'TIKTOK', 'admin_name' => 'HANDOKO', 'default_advertiser' => null],
            ['code' => 'RAYA', 'platform_code' => 'SHOPEE', 'admin_name' => 'YAYA', 'default_advertiser' => 'ADV EMPAT'],
        ];

        foreach ($stores as $s) {
            $platform = Platform::where('code', $s['platform_code'])->first();
            Store::updateOrCreate(
                ['code' => $s['code']],
                [
                    'platform_id' => $platform?->id,
                    'admin_name' => $s['admin_name'],
                    'default_advertiser' => $s['default_advertiser'],
                ]
            );
        }
    }

    /**
     * Normalisasi Provinsi -> Region untuk file MARKETING.
     */
    private function seedRegions(): void
    {
        $regions = [
            'Jawa Timur' => 'JAWA',
            'Jawa Tengah' => 'JAWA',
            'Jawa Barat' => 'JAWA',
            'Banten' => 'JAWA',
            'DKI Jakarta' => 'JAWA',
            'DI Yogyakarta' => 'JAWA',
        ];

        foreach ($regions as $province => $region) {
            Region::updateOrCreate(['province' => $province], ['region' => $region]);
        }
    }

    private function seedColumnMappings(): void
    {
        // SALES DAILY column mappings
        $dailyMappings = [
            ['excel_column' => 'Date', 'db_column' => 'sale_date', 'is_required' => true],
            ['excel_column' => 'Group', 'db_column' => 'group_code', 'is_required' => false],
            ['excel_column' => 'Kanal', 'db_column' => 'kanal', 'is_required' => false],
            ['excel_column' => 'MetodeBayar', 'db_column' => 'metode_bayar', 'is_required' => false],
            ['excel_column' => 'Toko', 'db_column' => 'toko', 'is_required' => false],
            ['excel_column' => 'ADV', 'db_column' => 'adv', 'is_required' => false],
            ['excel_column' => 'TypeTransaksi', 'db_column' => 'type_transaksi', 'is_required' => false],
            ['excel_column' => 'OrderNumber', 'db_column' => 'order_number', 'is_required' => true],
            ['excel_column' => 'Awb', 'db_column' => 'awb', 'is_required' => false],
            ['excel_column' => 'CustomerPhoneNumber', 'db_column' => 'customer_phone', 'is_required' => false],
            ['excel_column' => 'CustomerDisplayName', 'db_column' => 'customer_name', 'is_required' => false],
            ['excel_column' => 'BillingAddress', 'db_column' => 'billing_address', 'is_required' => false],
            ['excel_column' => 'ProvinsiCustomer', 'db_column' => 'provinsi', 'is_required' => false],
            ['excel_column' => 'KabupatenCustomer', 'db_column' => 'kabupaten', 'is_required' => false],
            ['excel_column' => 'KecamatanCustomer', 'db_column' => 'kecamatan', 'is_required' => false],
            ['excel_column' => 'Note', 'db_column' => 'note', 'is_required' => false],
            ['excel_column' => 'ProductCode', 'db_column' => 'product_code', 'is_required' => true],
            ['excel_column' => 'Quantity', 'db_column' => 'quantity', 'is_required' => true],
            ['excel_column' => 'UnitPrice', 'db_column' => 'unit_price', 'is_required' => true],
            ['excel_column' => 'Totalperline', 'db_column' => 'total_per_line', 'is_required' => true],
            ['excel_column' => 'Ekspedisi', 'db_column' => 'ekspedisi', 'is_required' => false],
            ['excel_column' => 'Warehouse', 'db_column' => 'warehouse', 'is_required' => false],
            ['excel_column' => 'Status Order', 'db_column' => 'status_order', 'is_required' => false],
        ];

        // SALES MP column mappings (different column names)
        $mpMappings = [
            ['excel_column' => 'Date', 'db_column' => 'sale_date', 'is_required' => true],
            ['excel_column' => 'Group', 'db_column' => 'group_code', 'is_required' => false],
            ['excel_column' => 'Kanal', 'db_column' => 'kanal', 'is_required' => false],
            ['excel_column' => 'MetodeBayar', 'db_column' => 'metode_bayar', 'is_required' => false],
            ['excel_column' => 'Toko', 'db_column' => 'toko', 'is_required' => false],
            ['excel_column' => 'TypeTransaksi', 'db_column' => 'type_transaksi', 'is_required' => false],
            ['excel_column' => 'OrderNumber', 'db_column' => 'order_number', 'is_required' => true],
            ['excel_column' => 'Awb', 'db_column' => 'awb', 'is_required' => false],
            ['excel_column' => 'Note', 'db_column' => 'note', 'is_required' => false],
            ['excel_column' => 'ProductCode', 'db_column' => 'product_code', 'is_required' => true],
            ['excel_column' => 'Quantity', 'db_column' => 'quantity', 'is_required' => true],
            ['excel_column' => 'UnitPrice', 'db_column' => 'unit_price', 'is_required' => true],
            ['excel_column' => 'Totalperline', 'db_column' => 'total_per_line', 'is_required' => true],
            ['excel_column' => 'Ekspedisi', 'db_column' => 'ekspedisi', 'is_required' => false],
            ['excel_column' => 'City', 'db_column' => 'kabupaten', 'is_required' => false],
            ['excel_column' => 'Province', 'db_column' => 'provinsi', 'is_required' => false],
        ];

        // SALES PRODUK column mappings
        $produkMappings = [
            ['excel_column' => 'Date', 'db_column' => 'sale_date', 'is_required' => true],
            ['excel_column' => 'Group', 'db_column' => 'group_code', 'is_required' => false],
            ['excel_column' => 'Kanal', 'db_column' => 'kanal', 'is_required' => false],
            ['excel_column' => 'MetodeBayar', 'db_column' => 'metode_bayar', 'is_required' => false],
            ['excel_column' => 'Toko', 'db_column' => 'toko', 'is_required' => false],
            ['excel_column' => 'ADV', 'db_column' => 'adv', 'is_required' => false],
            ['excel_column' => 'TypeTransaksi', 'db_column' => 'type_transaksi', 'is_required' => false],
            ['excel_column' => 'OrderNumber', 'db_column' => 'order_number', 'is_required' => true],
            ['excel_column' => 'Awb', 'db_column' => 'awb', 'is_required' => false],
            ['excel_column' => 'CustomerPhoneNumber', 'db_column' => 'customer_phone', 'is_required' => false],
            ['excel_column' => 'CustomerDisplayName', 'db_column' => 'customer_name', 'is_required' => false],
            ['excel_column' => 'BillingAddress', 'db_column' => 'billing_address', 'is_required' => false],
            ['excel_column' => 'ProvinsiCustomer', 'db_column' => 'provinsi', 'is_required' => false],
            ['excel_column' => 'KabupatenCustomer', 'db_column' => 'kabupaten', 'is_required' => false],
            ['excel_column' => 'KecamatanCustomer', 'db_column' => 'kecamatan', 'is_required' => false],
            ['excel_column' => 'Note', 'db_column' => 'note', 'is_required' => false],
            ['excel_column' => 'ProductCode', 'db_column' => 'product_code', 'is_required' => true],
            ['excel_column' => 'Quantity', 'db_column' => 'quantity', 'is_required' => true],
            ['excel_column' => 'UnitPrice', 'db_column' => 'unit_price', 'is_required' => true],
            ['excel_column' => 'Totalperline', 'db_column' => 'total_per_line', 'is_required' => true],
            ['excel_column' => 'Ekspedisi', 'db_column' => 'ekspedisi', 'is_required' => false],
        ];

        $allMappings = [
            'daily' => $dailyMappings,
            'mp' => $mpMappings,
            'produk' => $produkMappings,
        ];

        foreach ($allMappings as $fileType => $mappings) {
            foreach ($mappings as $mapping) {
                ColumnMapping::updateOrCreate(
                    ['file_type' => $fileType, 'excel_column' => $mapping['excel_column']],
                    [
                        'db_column' => $mapping['db_column'],
                        'is_required' => $mapping['is_required'],
                        'default_value' => $mapping['default_value'] ?? null,
                    ]
                );
            }
        }
    }
}
