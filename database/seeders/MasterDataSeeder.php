<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Platform;
use App\Models\Product;
use App\Models\BundleItem;
use App\Models\ProductPrice;
use App\Models\ColumnMapping;

class MasterDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedPlatforms();
        $this->seedProducts();
        $this->seedBundleItems();
        $this->seedProductPrices();
        $this->seedColumnMappings();
    }

    private function seedPlatforms(): void
    {
        $platforms = [
            ['code' => 'A', 'name' => 'Direct / API', 'channel_type' => 'direct'],
            ['code' => 'SHOPEE', 'name' => 'Shopee', 'channel_type' => 'marketplace'],
            ['code' => 'TIKTOK', 'name' => 'TikTok Shop', 'channel_type' => 'social_commerce'],
            ['code' => 'TOKOPEDIA', 'name' => 'Tokopedia', 'channel_type' => 'marketplace'],
            ['code' => 'LAZADA', 'name' => 'Lazada', 'channel_type' => 'marketplace'],
        ];

        foreach ($platforms as $p) {
            Platform::updateOrCreate(['code' => $p['code']], $p);
        }
    }

    private function seedProducts(): void
    {
        $products = [
            ['code' => 'PR01', 'name' => 'Produk Regular 01', 'is_bundle' => false, 'base_price' => 140000, 'hpp' => 80000],
            ['code' => 'BRG01', 'name' => 'Barang 01', 'is_bundle' => false, 'base_price' => 90000, 'hpp' => 50000],
            ['code' => 'BDL01', 'name' => 'Bundle 01 (PR01 + BRG01)', 'is_bundle' => true, 'base_price' => 280000, 'hpp' => 130000],
        ];

        foreach ($products as $p) {
            Product::updateOrCreate(['code' => $p['code']], $p);
        }
    }

    private function seedBundleItems(): void
    {
        $bundle = Product::where('code', 'BDL01')->first();
        $pr01 = Product::where('code', 'PR01')->first();
        $brg01 = Product::where('code', 'BRG01')->first();

        if ($bundle && $pr01 && $brg01) {
            BundleItem::updateOrCreate(
                ['bundle_product_id' => $bundle->id, 'item_product_id' => $pr01->id],
                ['quantity' => 1]
            );
            BundleItem::updateOrCreate(
                ['bundle_product_id' => $bundle->id, 'item_product_id' => $brg01->id],
                ['quantity' => 1]
            );
        }
    }

    private function seedProductPrices(): void
    {
        $prices = [
            // PR01 prices per platform
            ['product_code' => 'PR01', 'platform_code' => 'A', 'selling_price' => 140000, 'hpp' => 80000],
            ['product_code' => 'PR01', 'platform_code' => 'SHOPEE', 'selling_price' => 147000, 'hpp' => 80000],
            ['product_code' => 'PR01', 'platform_code' => 'TIKTOK', 'selling_price' => 145000, 'hpp' => 80000],
            // BRG01 prices per platform
            ['product_code' => 'BRG01', 'platform_code' => 'A', 'selling_price' => 90000, 'hpp' => 50000],
            ['product_code' => 'BRG01', 'platform_code' => 'SHOPEE', 'selling_price' => 95000, 'hpp' => 50000],
            // BDL01 (bundle) prices per platform
            ['product_code' => 'BDL01', 'platform_code' => 'A', 'selling_price' => 280000, 'hpp' => 130000],
            ['product_code' => 'BDL01', 'platform_code' => 'TIKTOK', 'selling_price' => 280000, 'hpp' => 130000],
            ['product_code' => 'BDL01', 'platform_code' => 'SHOPEE', 'selling_price' => 290000, 'hpp' => 130000],
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
