<?php

namespace App\Http\Controllers;

use App\Models\SalesTransaction;
use App\Models\Upload;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        // Upload statistics
        $totalUploads = Upload::count();
        $totalTransactions = SalesTransaction::count();
        $totalRevenue = SalesTransaction::sum('total_per_line');
        $totalProducts = Product::count();

        // Upload status breakdown
        $uploadStats = Upload::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // Sales per channel/kanal
        $salesByKanal = SalesTransaction::selectRaw('kanal, COUNT(*) as total_orders, SUM(total_per_line) as total_revenue')
            ->groupBy('kanal')
            ->orderByDesc('total_revenue')
            ->get();

        // Daily sales trend
        $dailySales = SalesTransaction::selectRaw('sale_date, COUNT(*) as total_orders, SUM(total_per_line) as total_revenue')
            ->groupBy('sale_date')
            ->orderBy('sale_date')
            ->get();

        // Top products
        $topProducts = SalesTransaction::selectRaw('product_code, SUM(quantity) as total_qty, SUM(total_per_line) as total_revenue')
            ->groupBy('product_code')
            ->orderByDesc('total_revenue')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                $product = Product::where('code', $item->product_code)->first();
                $item->product_name = $product ? $product->name : $item->product_code;
                $item->is_bundle = $product ? $product->is_bundle : false;
                return $item;
            });

        // Recent uploads
        $recentUploads = Upload::orderByDesc('created_at')->limit(5)->get();

        // Sales by file source
        $salesBySource = SalesTransaction::selectRaw('file_source, COUNT(*) as total_orders, SUM(total_per_line) as total_revenue')
            ->groupBy('file_source')
            ->get();

        return view('dashboard.index', compact(
            'totalUploads', 'totalTransactions', 'totalRevenue', 'totalProducts',
            'uploadStats', 'salesByKanal', 'dailySales', 'topProducts',
            'recentUploads', 'salesBySource'
        ));
    }
}
