@extends('layouts.app')

@section('title', 'Output Files Sigma Sales Importer')
@section('page-title', 'Output Files')

@section('content')
    <div class="card">
        <div class="card-header">
            <h3><i class="ri-file-download-line" style="margin-right:8px;color:var(--accent-hover)"></i>File Output Hasil
                Transformasi</h3>
        </div>
        <div class="card-body">
            @if ($uploads->count() > 0)
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Batch Code</th>
                                <th>Success Rows</th>
                                <th>Waktu Selesai</th>
                                <th>FINANCE</th>
                                <th>MARKETING</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($uploads as $up)
                                @php
                                    $summaryFile = storage_path("app/private/outputs/FINANCE_{$up->batch_code}.xlsx");
                                    $detailFile = storage_path("app/private/outputs/MARKETING_{$up->batch_code}.xlsx");
                                @endphp
                                <tr>
                                    <td>
                                        <a href="{{ route('upload.show', $up->id) }}"
                                            style="color:var(--accent-hover);text-decoration:none;font-weight:600">
                                            {{ $up->batch_code }}
                                        </a>
                                    </td>
                                    <td style="color:var(--success)">{{ number_format($up->success_rows) }}</td>
                                    <td class="text-muted text-sm">
                                        {{ $up->completed_at ? $up->completed_at->format('d/m/Y H:i') : '-' }}</td>
                                    <td>
                                        @if (file_exists($summaryFile))
                                            <a href="{{ route('output.download', [$up->id, 'finance']) }}"
                                                class="btn btn-success btn-sm">
                                                <i class="ri-download-2-line"></i> Download
                                            </a>
                                        @else
                                            <span class="text-muted text-sm">Tidak tersedia</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if (file_exists($detailFile))
                                            <a href="{{ route('output.download', [$up->id, 'marketing']) }}"
                                                class="btn btn-success btn-sm">
                                                <i class="ri-download-2-line"></i> Download
                                            </a>
                                        @else
                                            <span class="text-muted text-sm">Tidak tersedia</span>
                                        @endif
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
                    <i class="ri-file-download-line"></i>
                    <h3>Belum ada output file</h3>
                    <p>Output akan otomatis di-generate setelah proses import berhasil</p>
                    <a href="{{ route('upload.index') }}" class="btn btn-primary mt-4">
                        <i class="ri-upload-2-line"></i> Upload Sekarang
                    </a>
                </div>
            @endif
        </div>
    </div>
@endsection
