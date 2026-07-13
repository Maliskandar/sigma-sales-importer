@extends('layouts.app')

@section('title', "History #{$upload->batch_code} — Sigma Sales Importer")
@section('page-title', 'Detail History')

@section('content')
<div class="flex items-center gap-3 mb-6">
    <a href="{{ route('history.index') }}" class="btn btn-secondary btn-sm">
        <i class="ri-arrow-left-line"></i> Kembali
    </a>
    <h2 style="font-size:18px;font-weight:700">{{ $upload->batch_code }}</h2>
    @if($upload->status === 'completed')
        <span class="badge badge-success"><span class="badge-dot"></span> Completed</span>
    @elseif($upload->status === 'failed')
        <span class="badge badge-error"><span class="badge-dot"></span> Failed</span>
    @endif
</div>

<!-- Summary Stats -->
<div class="stats-grid mb-6">
    <div class="stat-card blue" style="padding:16px">
        <div class="stat-value" style="font-size:22px">{{ $infoCount }}</div>
        <div class="stat-label">Info</div>
    </div>
    <div class="stat-card orange" style="padding:16px">
        <div class="stat-value" style="font-size:22px">{{ $warningCount }}</div>
        <div class="stat-label">Warnings</div>
    </div>
    <div class="stat-card purple" style="padding:16px">
        <div class="stat-value" style="font-size:22px">{{ $errorCount }}</div>
        <div class="stat-label">Errors</div>
    </div>
</div>

@if($errorCount > 0)
    <div class="mb-4">
        <a href="{{ route('history.error-report', $upload->id) }}" class="btn btn-danger">
            <i class="ri-download-2-line"></i> Download Error Report (.xlsx)
        </a>
    </div>
@endif

<!-- Log Table -->
<div class="card">
    <div class="card-header">
        <h3><i class="ri-file-list-3-line" style="margin-right:8px;color:var(--accent-hover)"></i>Log Entries</h3>
    </div>
    <div class="card-body">
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Level</th>
                        <th>Source</th>
                        <th>Row</th>
                        <th>Message</th>
                        <th>Waktu</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($logs as $log)
                        <tr>
                            <td>
                                @if($log->level === 'error')
                                    <span class="badge badge-error">Error</span>
                                @elseif($log->level === 'warning')
                                    <span class="badge badge-warning">Warning</span>
                                @else
                                    <span class="badge badge-info">Info</span>
                                @endif
                            </td>
                            <td><strong>{{ strtoupper($log->file_source) }}</strong></td>
                            <td>{{ $log->row_number ?? '-' }}</td>
                            <td style="max-width:400px">{{ $log->message }}</td>
                            <td class="text-muted text-sm">{{ $log->created_at->format('H:i:s') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="pagination">
            {{ $logs->links() }}
        </div>
    </div>
</div>
@endsection
