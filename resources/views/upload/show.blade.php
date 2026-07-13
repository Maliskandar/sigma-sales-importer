@extends('layouts.app')

@section('title', "Upload #{$upload->batch_code} — Sigma Sales Importer")
@section('page-title', 'Detail Upload')

@section('content')
<!-- Upload Header -->
<div class="card mb-6">
    <div class="card-body">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 style="font-size:18px;font-weight:700;margin-bottom:4px">{{ $upload->batch_code }}</h2>
                <span class="text-sm text-muted">{{ $upload->created_at->format('d M Y, H:i:s') }}</span>
            </div>
            <div class="flex items-center gap-3">
                @if($upload->status === 'completed')
                    <span class="badge badge-success" style="font-size:13px;padding:6px 14px"><span class="badge-dot"></span> Completed</span>
                @elseif($upload->status === 'processing')
                    <span class="badge badge-info" style="font-size:13px;padding:6px 14px"><span class="badge-dot"></span> Processing</span>
                @elseif($upload->status === 'failed')
                    <span class="badge badge-error" style="font-size:13px;padding:6px 14px"><span class="badge-dot"></span> Failed</span>
                @else
                    <span class="badge badge-pending" style="font-size:13px;padding:6px 14px"><span class="badge-dot"></span> Pending</span>
                @endif
            </div>
        </div>

        <!-- Progress bar -->
        <div style="margin-bottom:16px">
            <div class="flex items-center justify-between mb-4" style="margin-bottom:8px">
                <span class="text-sm text-muted">Progress</span>
                <span class="text-sm" id="progress-text">{{ $upload->progress_percentage }}%</span>
            </div>
            <div class="progress-container" style="height:10px">
                <div class="progress-bar {{ $upload->status === 'processing' ? 'animated' : '' }}" id="progress-bar" style="width:{{ $upload->progress_percentage }}%"></div>
            </div>
        </div>

        <!-- Stats -->
        <div class="stats-grid" style="margin-bottom:0">
            <div class="stat-card green" style="padding:16px">
                <div class="stat-value" style="font-size:22px" id="stat-success">{{ number_format($upload->success_rows) }}</div>
                <div class="stat-label">Berhasil</div>
            </div>
            <div class="stat-card orange" style="padding:16px">
                <div class="stat-value" style="font-size:22px" id="stat-error">{{ number_format($upload->error_rows) }}</div>
                <div class="stat-label">Error</div>
            </div>
            <div class="stat-card blue" style="padding:16px">
                <div class="stat-value" style="font-size:22px" id="stat-warning">{{ number_format($upload->warning_rows) }}</div>
                <div class="stat-label">Warning</div>
            </div>
            <div class="stat-card purple" style="padding:16px">
                <div class="stat-value" style="font-size:22px" id="stat-total">{{ number_format($upload->total_rows) }}</div>
                <div class="stat-label">Total Baris</div>
            </div>
        </div>
    </div>
</div>

<!-- Actions -->
<div class="flex items-center gap-3 mb-6">
    @if(!empty($outputFiles))
        @if(isset($outputFiles['finance']))
            <a href="{{ route('output.download', [$upload->id, 'finance']) }}" class="btn btn-success">
                <i class="ri-file-download-line"></i> Download FINANCE
            </a>
        @endif
        @if(isset($outputFiles['marketing']))
            <a href="{{ route('output.download', [$upload->id, 'marketing']) }}" class="btn btn-success">
                <i class="ri-file-download-line"></i> Download MARKETING
            </a>
        @endif
    @endif

    @if($upload->error_rows > 0)
        <a href="{{ route('history.error-report', $upload->id) }}" class="btn btn-secondary">
            <i class="ri-bug-line"></i> Download Error Report
        </a>
    @endif

    @if($upload->status === 'completed')
        <form action="{{ route('upload.rollback', $upload->id) }}" method="POST" style="display:inline"
              onsubmit="return confirm('Apakah Anda yakin ingin rollback upload ini? Semua data transaksi dari batch ini akan dihapus.')">
            @csrf
            <button type="submit" class="btn btn-danger">
                <i class="ri-arrow-go-back-line"></i> Rollback
            </button>
        </form>
    @endif

    <a href="{{ route('upload.index') }}" class="btn btn-secondary" style="margin-left:auto">
        <i class="ri-arrow-left-line"></i> Kembali
    </a>
</div>

<!-- Log Entries -->
<div class="card">
    <div class="card-header">
        <h3><i class="ri-file-list-3-line" style="margin-right:8px;color:var(--accent-hover)"></i>Log Proses</h3>
        <div class="flex items-center gap-3">
            <span class="badge badge-error">{{ $errorLogs->count() }} Error</span>
            <span class="badge badge-warning">{{ $warningLogs->count() }} Warning</span>
            <span class="badge badge-info">{{ $infoLogs->count() }} Info</span>
        </div>
    </div>
    <div class="card-body">
        @if($errorLogs->count() > 0)
            <h4 style="font-size:14px;color:var(--error);margin-bottom:12px"><i class="ri-error-warning-line"></i> Errors</h4>
            @foreach($errorLogs as $log)
                <div class="log-entry error">
                    <i class="ri-close-circle-fill" style="color:var(--error)"></i>
                    <div>
                        <strong>[{{ strtoupper($log->file_source) }}]</strong>
                        @if($log->row_number) Baris {{ $log->row_number }}: @endif
                        {{ $log->message }}
                        <div class="text-sm text-muted" style="margin-top:4px">{{ $log->created_at->format('H:i:s') }}</div>
                    </div>
                </div>
            @endforeach
        @endif

        @if($warningLogs->count() > 0)
            <h4 style="font-size:14px;color:var(--warning);margin:20px 0 12px"><i class="ri-alert-line"></i> Warnings</h4>
            @foreach($warningLogs as $log)
                <div class="log-entry warning">
                    <i class="ri-alert-fill" style="color:var(--warning)"></i>
                    <div>
                        <strong>[{{ strtoupper($log->file_source) }}]</strong>
                        @if($log->row_number) Baris {{ $log->row_number }}: @endif
                        {{ $log->message }}
                        <div class="text-sm text-muted" style="margin-top:4px">{{ $log->created_at->format('H:i:s') }}</div>
                    </div>
                </div>
            @endforeach
        @endif

        @if($infoLogs->count() > 0)
            <h4 style="font-size:14px;color:var(--info);margin:20px 0 12px"><i class="ri-information-line"></i> Info</h4>
            @foreach($infoLogs as $log)
                <div class="log-entry info">
                    <i class="ri-information-fill" style="color:var(--info)"></i>
                    <div>
                        <strong>[{{ strtoupper($log->file_source) }}]</strong>
                        {{ $log->message }}
                        <div class="text-sm text-muted" style="margin-top:4px">{{ $log->created_at->format('H:i:s') }}</div>
                    </div>
                </div>
            @endforeach
        @endif

        @if($errorLogs->count() === 0 && $warningLogs->count() === 0 && $infoLogs->count() === 0)
            <div class="empty-state">
                <i class="ri-file-list-3-line"></i>
                <h3>Belum ada log</h3>
                <p>Log akan muncul saat proses import berjalan</p>
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
    // Poll progress if still processing
    @if(in_array($upload->status, ['pending', 'processing']))
    const uploadId = {{ $upload->id }};
    let pollInterval = setInterval(function() {
        fetch(`/api/upload/${uploadId}/progress`)
            .then(r => r.json())
            .then(data => {
                document.getElementById('progress-bar').style.width = data.progress_percentage + '%';
                document.getElementById('progress-text').textContent = data.progress_percentage + '%';
                document.getElementById('stat-success').textContent = data.success_rows.toLocaleString('id-ID');
                document.getElementById('stat-error').textContent = data.error_rows.toLocaleString('id-ID');
                document.getElementById('stat-warning').textContent = data.warning_rows.toLocaleString('id-ID');
                document.getElementById('stat-total').textContent = data.total_rows.toLocaleString('id-ID');

                if (data.status === 'completed' || data.status === 'failed') {
                    clearInterval(pollInterval);
                    showToast(data.status === 'completed' ? 'Import selesai!' : 'Import gagal!', data.status === 'completed' ? 'success' : 'error');
                    setTimeout(() => location.reload(), 1500);
                }
            })
            .catch(() => {});
    }, 2000);
    @endif
</script>
@endpush
@endsection
