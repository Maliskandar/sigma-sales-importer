@extends('layouts.app')

@section('title', 'Dashboard Sigma Sales Importer')
@section('page-title', 'Dashboard')

@section('content')
    <!-- Stats Cards -->
    <div class="stats-grid">
        <div class="stat-card purple">
            <div class="stat-icon"><i class="ri-upload-cloud-2-line"></i></div>
            <div class="stat-value">{{ number_format($totalUploads) }}</div>
            <div class="stat-label">Total Upload</div>
        </div>
        <div class="stat-card blue">
            <div class="stat-icon"><i class="ri-shopping-cart-2-line"></i></div>
            <div class="stat-value">{{ number_format($totalTransactions) }}</div>
            <div class="stat-label">Total Transaksi</div>
        </div>
        <div class="stat-card green">
            <div class="stat-icon"><i class="ri-money-dollar-circle-line"></i></div>
            <div class="stat-value">Rp {{ number_format($totalRevenue, 0, ',', '.') }}</div>
            <div class="stat-label">Total Revenue</div>
        </div>
        <div class="stat-card orange">
            <div class="stat-icon"><i class="ri-box-3-line"></i></div>
            <div class="stat-value">{{ number_format($totalProducts) }}</div>
            <div class="stat-label">Master Produk</div>
        </div>
    </div>

    <div class="grid-2">
        <!-- Sales by Channel -->
        <div class="card">
            <div class="card-header">
                <h3><i class="ri-store-2-line" style="margin-right:8px;color:var(--accent-hover)"></i>Penjualan per Kanal
                </h3>
            </div>
            <div class="card-body">
                @if ($salesByKanal->count() > 0)
                    @php $maxRevenue = $salesByKanal->max('total_revenue') ?: 1; @endphp
                    <div class="chart-bar" style="margin-bottom: 40px;">
                        @foreach ($salesByKanal as $kanal)
                            <div class="chart-bar-item"
                                style="height: {{ max(20, ($kanal->total_revenue / $maxRevenue) * 100) }}%">
                                <span class="chart-bar-value">Rp
                                    {{ number_format($kanal->total_revenue / 1000, 0) }}K</span>
                                <span class="chart-bar-label">{{ $kanal->platform_label ?: 'N/A' }}</span>
                            </div>
                        @endforeach
                    </div>
                    <div class="table-container mt-4">
                        <table>
                            <thead>
                                <tr>
                                    <th>Kanal</th>
                                    <th>Orders</th>
                                    <th class="text-right">Revenue</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($salesByKanal as $kanal)
                                    <tr>
                                        <td><strong>{{ $kanal->platform_label ?: 'N/A' }}</strong></td>
                                        <td>{{ number_format($kanal->total_orders) }}</td>
                                        <td class="text-right">Rp {{ number_format($kanal->total_revenue, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="empty-state">
                        <i class="ri-bar-chart-line"></i>
                        <h3>Belum ada data</h3>
                        <p>Upload file Excel untuk melihat statistik penjualan</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Top Products -->
        <div class="card">
            <div class="card-header">
                <h3><i class="ri-trophy-line" style="margin-right:8px;color:var(--warning)"></i>Top Produk</h3>
            </div>
            <div class="card-body">
                @if ($topProducts->count() > 0)
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Produk</th>
                                    <th>Qty</th>
                                    <th class="text-right">Revenue</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($topProducts as $idx => $prod)
                                    <tr>
                                        <td>
                                            @if ($idx === 0)
                                                <span style="color:gold">🥇</span>
                                            @elseif($idx === 1)
                                                <span style="color:silver">🥈</span>
                                            @elseif($idx === 2)
                                                <span style="color:#cd7f32">🥉</span>
                                            @else
                                                {{ $idx + 1 }}
                                            @endif
                                        </td>
                                        <td>
                                            <strong>{{ $prod->product_code }}</strong>
                                            <br><span class="text-sm text-muted">{{ $prod->product_name }}</span>
                                            @if ($prod->is_bundle)
                                                <span class="badge badge-info" style="margin-left:4px">Bundle</span>
                                            @endif
                                        </td>
                                        <td>{{ number_format($prod->total_qty) }}</td>
                                        <td class="text-right">Rp {{ number_format($prod->total_revenue, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="empty-state">
                        <i class="ri-box-3-line"></i>
                        <h3>Belum ada data</h3>
                        <p>Upload file Excel untuk melihat produk terlaris</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Recent Uploads -->
    <div class="card mt-6">
        <div class="card-header">
            <h3><i class="ri-time-line" style="margin-right:8px;color:var(--accent-hover)"></i>Upload Terbaru</h3>
            <a href="{{ route('history.index') }}" class="btn btn-secondary btn-sm">Lihat Semua</a>
        </div>
        <div class="card-body">
            @if ($recentUploads->count() > 0)
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Batch</th>
                                <th>Status</th>
                                <th>Success</th>
                                <th>Error</th>
                                <th>Warning</th>
                                <th>Waktu</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($recentUploads as $up)
                                <tr>
                                    <td><a href="{{ route('upload.show', $up->id) }}"
                                            style="color:var(--accent-hover);text-decoration:none">{{ $up->batch_code }}</a>
                                    </td>
                                    <td>
                                        @if ($up->status === 'completed')
                                            <span class="badge badge-success"><span class="badge-dot"></span>
                                                Completed</span>
                                        @elseif($up->status === 'processing')
                                            <span class="badge badge-info"><span class="badge-dot"></span> Processing</span>
                                        @elseif($up->status === 'failed')
                                            <span class="badge badge-error"><span class="badge-dot"></span> Failed</span>
                                        @else
                                            <span class="badge badge-pending"><span class="badge-dot"></span> Pending</span>
                                        @endif
                                    </td>
                                    <td style="color:var(--success)">{{ $up->success_rows }}</td>
                                    <td style="color:var(--error)">{{ $up->error_rows }}</td>
                                    <td style="color:var(--warning)">{{ $up->warning_rows }}</td>
                                    <td class="text-muted">{{ $up->created_at->diffForHumans() }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="empty-state">
                    <i class="ri-upload-cloud-2-line"></i>
                    <h3>Belum ada upload</h3>
                    <p>Mulai dengan mengupload file Excel sales Anda</p>
                    <a href="{{ route('upload.index') }}" class="btn btn-primary mt-4">
                        <i class="ri-upload-2-line"></i> Upload Sekarang
                    </a>
                </div>
            @endif
        </div>
    </div>
@endsection
