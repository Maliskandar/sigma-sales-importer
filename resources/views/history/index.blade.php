@extends('layouts.app')

@section('title', 'History Log — Sigma Sales Importer')
@section('page-title', 'History Log')

@section('content')
<div class="card">
    <div class="card-header">
        <h3><i class="ri-history-line" style="margin-right:8px;color:var(--accent-hover)"></i>Riwayat Import</h3>
    </div>
    <div class="card-body">
        @if($uploads->count() > 0)
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Batch Code</th>
                            <th>File Daily</th>
                            <th>File MP</th>
                            <th>File Produk</th>
                            <th>Status</th>
                            <th>Success</th>
                            <th>Error</th>
                            <th>Warning</th>
                            <th>Waktu</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($uploads as $up)
                            <tr>
                                <td>
                                    <a href="{{ route('upload.show', $up->id) }}" style="color:var(--accent-hover);text-decoration:none;font-weight:600">
                                        {{ $up->batch_code }}
                                    </a>
                                </td>
                                <td>
                                    @if($up->file_daily)
                                        <span class="badge badge-success"><i class="ri-check-line"></i></span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if($up->file_mp)
                                        <span class="badge badge-success"><i class="ri-check-line"></i></span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if($up->file_produk)
                                        <span class="badge badge-success"><i class="ri-check-line"></i></span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if($up->status === 'completed')
                                        <span class="badge badge-success"><span class="badge-dot"></span> Completed</span>
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
                                <td class="text-muted text-sm">{{ $up->created_at->format('d/m/Y H:i') }}</td>
                                <td>
                                    <div class="flex items-center gap-3">
                                        <a href="{{ route('upload.show', $up->id) }}" class="btn btn-secondary btn-sm">
                                            <i class="ri-eye-line"></i>
                                        </a>
                                        @if($up->error_rows > 0)
                                            <a href="{{ route('history.error-report', $up->id) }}" class="btn btn-danger btn-sm" title="Download Error Report">
                                                <i class="ri-bug-line"></i>
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="pagination">
                {{ $uploads->links() }}
            </div>
        @else
            <div class="empty-state">
                <i class="ri-history-line"></i>
                <h3>Belum ada riwayat import</h3>
                <p>Mulai dengan mengupload file Excel</p>
                <a href="{{ route('upload.index') }}" class="btn btn-primary mt-4">
                    <i class="ri-upload-2-line"></i> Upload Sekarang
                </a>
            </div>
        @endif
    </div>
</div>
@endsection
