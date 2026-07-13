@extends('layouts.app')

@section('title', 'Upload Import — Sigma Sales Importer')
@section('page-title', 'Upload Import')

@section('content')
<div class="card mb-6">
    <div class="card-header">
        <h3><i class="ri-upload-cloud-2-line" style="margin-right:8px;color:var(--accent-hover)"></i>Upload File Excel</h3>
    </div>
    <div class="card-body">
        <form action="{{ route('upload.store') }}" method="POST" enctype="multipart/form-data" id="uploadForm">
            @csrf
            <div class="grid-3" style="margin-bottom: 24px;">
                <!-- SALES DAILY -->
                <div class="file-input-wrapper">
                    <label class="file-label">
                        <i class="ri-file-excel-2-line" style="color:var(--success);margin-right:4px"></i>
                        SALES DAILY
                    </label>
                    <div class="file-input-area" id="drop-daily" onclick="document.getElementById('file_daily').click()">
                        <input type="file" name="file_daily" id="file_daily" accept=".xlsx,.xls">
                        <i class="ri-drag-drop-line" style="font-size:28px;color:var(--text-muted);display:block;margin-bottom:8px"></i>
                        <div class="text-sm text-muted">Drag & drop atau klik</div>
                        <div class="file-name" id="name-daily"></div>
                    </div>
                    @error('file_daily')
                        <div style="color:var(--error);font-size:12px;margin-top:6px">{{ $message }}</div>
                    @enderror
                </div>

                <!-- SALES MP -->
                <div class="file-input-wrapper">
                    <label class="file-label">
                        <i class="ri-file-excel-2-line" style="color:var(--info);margin-right:4px"></i>
                        SALES MP (Marketplace)
                    </label>
                    <div class="file-input-area" id="drop-mp" onclick="document.getElementById('file_mp').click()">
                        <input type="file" name="file_mp" id="file_mp" accept=".xlsx,.xls">
                        <i class="ri-drag-drop-line" style="font-size:28px;color:var(--text-muted);display:block;margin-bottom:8px"></i>
                        <div class="text-sm text-muted">Drag & drop atau klik</div>
                        <div class="file-name" id="name-mp"></div>
                    </div>
                    @error('file_mp')
                        <div style="color:var(--error);font-size:12px;margin-top:6px">{{ $message }}</div>
                    @enderror
                </div>

                <!-- SALES PRODUK -->
                <div class="file-input-wrapper">
                    <label class="file-label">
                        <i class="ri-file-excel-2-line" style="color:var(--warning);margin-right:4px"></i>
                        SALES PRODUK
                    </label>
                    <div class="file-input-area" id="drop-produk" onclick="document.getElementById('file_produk').click()">
                        <input type="file" name="file_produk" id="file_produk" accept=".xlsx,.xls">
                        <i class="ri-drag-drop-line" style="font-size:28px;color:var(--text-muted);display:block;margin-bottom:8px"></i>
                        <div class="text-sm text-muted">Drag & drop atau klik</div>
                        <div class="file-name" id="name-produk"></div>
                    </div>
                    @error('file_produk')
                        <div style="color:var(--error);font-size:12px;margin-top:6px">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="flex items-center justify-between">
                <span class="text-sm text-muted">
                    <i class="ri-information-line"></i> Format yang diterima: .xlsx, .xls (maks. 50MB per file)
                </span>
                <button type="submit" class="btn btn-primary" id="submitBtn">
                    <i class="ri-upload-2-line"></i> Mulai Import
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Recent Uploads -->
@if($recentUploads->count() > 0)
<div class="card">
    <div class="card-header">
        <h3><i class="ri-time-line" style="margin-right:8px;color:var(--text-muted)"></i>Upload Terbaru</h3>
    </div>
    <div class="card-body">
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Batch</th>
                        <th>Status</th>
                        <th>Progress</th>
                        <th>Waktu</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentUploads as $up)
                        <tr>
                            <td><strong>{{ $up->batch_code }}</strong></td>
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
                            <td>
                                <div style="display:flex;align-items:center;gap:8px">
                                    <div class="progress-container" style="width:120px">
                                        <div class="progress-bar" style="width:{{ $up->progress_percentage }}%"></div>
                                    </div>
                                    <span class="text-sm">{{ $up->progress_percentage }}%</span>
                                </div>
                            </td>
                            <td class="text-muted">{{ $up->created_at->diffForHumans() }}</td>
                            <td><a href="{{ route('upload.show', $up->id) }}" class="btn btn-secondary btn-sm">Detail</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

@push('scripts')
<script>
    // File input handling with drag & drop
    ['daily', 'mp', 'produk'].forEach(type => {
        const dropArea = document.getElementById(`drop-${type}`);
        const fileInput = document.getElementById(`file_${type}`);
        const fileName = document.getElementById(`name-${type}`);

        // File input change
        fileInput.addEventListener('change', function() {
            if (this.files[0]) {
                fileName.textContent = '✓ ' + this.files[0].name;
                dropArea.classList.add('has-file');
            }
        });

        // Drag & drop
        ['dragenter', 'dragover'].forEach(evt => {
            dropArea.addEventListener(evt, e => {
                e.preventDefault();
                dropArea.classList.add('has-file');
            });
        });

        ['dragleave', 'drop'].forEach(evt => {
            dropArea.addEventListener(evt, e => {
                e.preventDefault();
                if (evt === 'dragleave') dropArea.classList.remove('has-file');
            });
        });

        dropArea.addEventListener('drop', e => {
            e.preventDefault();
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                fileInput.files = files;
                fileName.textContent = '✓ ' + files[0].name;
                dropArea.classList.add('has-file');
            }
        });
    });

    // Submit loading state
    document.getElementById('uploadForm').addEventListener('submit', function() {
        const btn = document.getElementById('submitBtn');
        btn.innerHTML = '<i class="ri-loader-4-line" style="animation:spin 1s linear infinite"></i> Mengupload...';
        btn.disabled = true;
    });
</script>
<style>
    @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
</style>
@endpush
@endsection
