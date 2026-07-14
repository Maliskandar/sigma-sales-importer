<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Sigma Sales Importer')</title>
    <meta name="description" content="Sistem otomatis import data sales Excel - PT Sigma Digital Nusantara">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.1.0/fonts/remixicon.css" rel="stylesheet">
    <style>
        /* ===== DESIGN SYSTEM — LIGHT THEME (brand PT Sigma Digital Nusantara) ===== */
        :root {
            --bg-primary: #f3f6fb;
            --bg-secondary: #ffffff;
            --bg-card: #ffffff;
            --bg-card-hover: #f8fafc;
            --bg-glass: #f1f5f9;
            --border-glass: #e4e9f1;
            --border-glow: rgba(22, 144, 204, 0.35);

            --text-primary: #14202e;
            --text-secondary: #51617a;
            --text-muted: #8493a8;

            /* Biru logo */
            --accent-primary: #1690cc;
            --accent-primary-rgb: 22, 144, 204;
            --accent-hover: #127bb0;
            --accent-glow: rgba(22, 144, 204, 0.10);
            --accent-gradient: linear-gradient(135deg, #1f9bd6, #1573b8);

            /* Oranye logo (aksen sekunder) */
            --brand-orange: #f5a623;
            --brand-orange-strong: #f08a2e;
            --brand-gradient: linear-gradient(135deg, #f5b431, #f08a2e);

            --success: #16a34a;
            --success-bg: rgba(22, 163, 74, 0.10);
            --warning: #d97706;
            --warning-bg: rgba(217, 119, 6, 0.10);
            --error: #dc2626;
            --error-bg: rgba(220, 38, 38, 0.09);
            --info: #1690cc;
            --info-bg: rgba(22, 144, 204, 0.10);

            --sidebar-width: 260px;
            --header-height: 70px;
            --radius-sm: 8px;
            --radius-md: 12px;
            --radius-lg: 16px;
            --radius-xl: 24px;

            --shadow-sm: 0 1px 3px rgba(20, 40, 70, 0.06);
            --shadow-md: 0 4px 14px rgba(20, 40, 70, 0.08);
            --shadow-lg: 0 10px 30px rgba(20, 40, 70, 0.10);
            --shadow-glow: 0 4px 18px rgba(22, 144, 204, 0.15);

            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', -apple-system, sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* Background gradient mesh */
        body::before {
            content: '';
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background:
                radial-gradient(ellipse at 18% 12%, rgba(22, 144, 204, 0.07) 0%, transparent 45%),
                radial-gradient(ellipse at 85% 85%, rgba(245, 166, 35, 0.06) 0%, transparent 45%);
            pointer-events: none;
            z-index: 0;
        }

        /* ===== SIDEBAR ===== */
        .sidebar {
            position: fixed;
            top: 0; left: 0;
            width: var(--sidebar-width);
            height: 100vh;
            background: var(--bg-secondary);
            border-right: 1px solid var(--border-glass);
            backdrop-filter: blur(20px);
            z-index: 100;
            display: flex;
            flex-direction: column;
            transition: var(--transition);
        }

        .sidebar-brand {
            padding: 20px 18px;
            border-bottom: 1px solid var(--border-glass);
        }

        .sidebar-brand-link {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
        }

        .sidebar-mark {
            width: 42px;
            height: auto;
            flex-shrink: 0;
            display: block;
        }

        .sidebar-brand-text {
            font-size: 16px;
            font-weight: 800;
            line-height: 1.15;
            letter-spacing: -0.01em;
            color: #1573b8; /* fallback bila gradient tak didukung */
            background: linear-gradient(135deg, #1f9bd6, #1573b8);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .sidebar-nav {
            flex: 1;
            padding: 16px 12px;
            overflow-y: auto;
        }

        .sidebar-label {
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: var(--text-muted);
            padding: 16px 12px 8px;
        }

        .sidebar-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 11px 14px;
            border-radius: var(--radius-sm);
            color: var(--text-secondary);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: var(--transition);
            position: relative;
            margin-bottom: 2px;
        }

        .sidebar-link:hover {
            background: var(--accent-glow);
            color: var(--text-primary);
        }

        .sidebar-link.active {
            background: var(--accent-glow);
            color: var(--accent-hover);
            border: 1px solid var(--border-glow);
        }

        .sidebar-link.active::before {
            content: '';
            position: absolute;
            left: -12px;
            top: 50%;
            transform: translateY(-50%);
            width: 3px;
            height: 24px;
            background: var(--accent-gradient);
            border-radius: 0 4px 4px 0;
        }

        .sidebar-link i {
            font-size: 18px;
            width: 22px;
            text-align: center;
        }

        /* ===== MAIN CONTENT ===== */
        .main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            position: relative;
            z-index: 1;
        }

        .header {
            height: var(--header-height);
            padding: 0 32px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid var(--border-glass);
            background: rgba(255, 255, 255, 0.78);
            backdrop-filter: blur(12px);
            position: sticky;
            top: 0;
            z-index: 50;
        }

        .header-title {
            font-size: 20px;
            font-weight: 700;
            letter-spacing: -0.02em;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .content {
            padding: 28px 32px;
        }

        /* ===== CARDS ===== */
        .card {
            background: var(--bg-card);
            border: 1px solid var(--border-glass);
            border-radius: var(--radius-lg);
            backdrop-filter: blur(16px);
            transition: var(--transition);
        }

        .card:hover {
            border-color: var(--border-glow);
            box-shadow: var(--shadow-glow);
        }

        .card-header {
            padding: 20px 24px;
            border-bottom: 1px solid var(--border-glass);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .card-header h3 {
            font-size: 16px;
            font-weight: 600;
        }

        .card-body {
            padding: 24px;
        }

        /* ===== STAT CARDS ===== */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 28px;
        }

        .stat-card {
            background: var(--bg-card);
            border: 1px solid var(--border-glass);
            border-radius: var(--radius-lg);
            padding: 24px;
            position: relative;
            overflow: hidden;
            transition: var(--transition);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0;
            width: 100%; height: 3px;
            border-radius: var(--radius-lg) var(--radius-lg) 0 0;
        }

        .stat-card.purple::before { background: var(--accent-gradient); }
        .stat-card.green::before { background: linear-gradient(135deg, #22c55e, #16a34a); }
        .stat-card.blue::before { background: linear-gradient(135deg, #3b82f6, #2563eb); }
        .stat-card.orange::before { background: linear-gradient(135deg, #f59e0b, #d97706); }

        .stat-card:hover {
            transform: translateY(-2px);
            border-color: var(--border-glow);
            box-shadow: var(--shadow-glow);
        }

        .stat-icon {
            width: 44px; height: 44px;
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            margin-bottom: 16px;
        }

        .stat-card.purple .stat-icon { background: var(--accent-glow); color: var(--accent-hover); }
        .stat-card.green .stat-icon { background: var(--success-bg); color: var(--success); }
        .stat-card.blue .stat-icon { background: var(--info-bg); color: var(--info); }
        .stat-card.orange .stat-icon { background: var(--warning-bg); color: var(--warning); }

        .stat-value {
            font-size: 28px;
            font-weight: 800;
            letter-spacing: -0.03em;
            margin-bottom: 4px;
        }

        .stat-label {
            font-size: 13px;
            color: var(--text-muted);
            font-weight: 500;
        }

        /* ===== BUTTONS ===== */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            border-radius: var(--radius-sm);
            font-size: 14px;
            font-weight: 600;
            font-family: inherit;
            text-decoration: none;
            border: none;
            cursor: pointer;
            transition: var(--transition);
        }

        .btn-primary {
            background: var(--accent-gradient);
            color: white;
            box-shadow: 0 4px 12px rgba(22, 144, 204, 0.28);
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(22, 144, 204, 0.4);
        }

        .btn-secondary {
            background: var(--bg-glass);
            color: var(--text-primary);
            border: 1px solid var(--border-glass);
        }

        .btn-secondary:hover {
            background: var(--accent-glow);
            border-color: var(--border-glow);
        }

        .btn-danger {
            background: var(--error-bg);
            color: var(--error);
            border: 1px solid rgba(239, 68, 68, 0.2);
        }

        .btn-danger:hover {
            background: rgba(239, 68, 68, 0.2);
        }

        .btn-success {
            background: var(--success-bg);
            color: var(--success);
            border: 1px solid rgba(34, 197, 94, 0.2);
        }

        .btn-success:hover {
            background: rgba(34, 197, 94, 0.2);
        }

        .btn-sm {
            padding: 6px 14px;
            font-size: 12px;
        }

        /* ===== TABLES ===== */
        .table-container {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        table th {
            text-align: left;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-muted);
            padding: 12px 16px;
            border-bottom: 1px solid var(--border-glass);
            white-space: nowrap;
        }

        table td {
            padding: 14px 16px;
            font-size: 13px;
            border-bottom: 1px solid var(--border-glass);
            color: var(--text-secondary);
        }

        table tr:hover td {
            background: var(--bg-glass);
            color: var(--text-primary);
        }

        /* ===== BADGES ===== */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.02em;
        }

        .badge-success { background: var(--success-bg); color: var(--success); }
        .badge-warning { background: var(--warning-bg); color: var(--warning); }
        .badge-error { background: var(--error-bg); color: var(--error); }
        .badge-info { background: var(--info-bg); color: var(--info); }
        .badge-pending { background: rgba(148, 163, 184, 0.1); color: var(--text-muted); }

        .badge-dot {
            width: 6px; height: 6px;
            border-radius: 50%;
            display: inline-block;
        }

        .badge-success .badge-dot { background: var(--success); }
        .badge-warning .badge-dot { background: var(--warning); }
        .badge-error .badge-dot { background: var(--error); }
        .badge-info .badge-dot { background: var(--info); }
        .badge-pending .badge-dot { background: var(--text-muted); }

        /* ===== PROGRESS BAR ===== */
        .progress-container {
            width: 100%;
            background: #e6ecf3;
            border-radius: 999px;
            height: 8px;
            overflow: hidden;
        }

        .progress-bar {
            height: 100%;
            background: var(--accent-gradient);
            border-radius: 999px;
            transition: width 0.5s ease;
            position: relative;
        }

        .progress-bar.animated::after {
            content: '';
            position: absolute;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            animation: shimmer 2s infinite;
        }

        @keyframes shimmer {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }

        /* ===== DROPZONE ===== */
        .dropzone {
            border: 2px dashed var(--border-glass);
            border-radius: var(--radius-lg);
            padding: 40px;
            text-align: center;
            transition: var(--transition);
            cursor: pointer;
            position: relative;
            background: var(--bg-glass);
        }

        .dropzone:hover,
        .dropzone.dragover {
            border-color: var(--accent-primary);
            background: var(--accent-glow);
            box-shadow: var(--shadow-glow);
        }

        .dropzone-icon {
            font-size: 48px;
            color: var(--accent-hover);
            margin-bottom: 16px;
            transition: var(--transition);
        }

        .dropzone:hover .dropzone-icon {
            transform: translateY(-4px);
        }

        .dropzone-text {
            font-size: 15px;
            color: var(--text-secondary);
            margin-bottom: 8px;
        }

        .dropzone-hint {
            font-size: 12px;
            color: var(--text-muted);
        }

        .file-input-wrapper {
            margin-bottom: 20px;
        }

        .file-label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: var(--text-secondary);
            margin-bottom: 8px;
        }

        .file-input-area {
            border: 1px dashed var(--border-glass);
            border-radius: var(--radius-md);
            padding: 20px;
            text-align: center;
            transition: var(--transition);
            cursor: pointer;
            background: var(--bg-glass);
        }

        .file-input-area:hover,
        .file-input-area.has-file {
            border-color: var(--accent-primary);
            background: var(--accent-glow);
        }

        .file-input-area.has-file {
            border-style: solid;
        }

        .file-input-area input[type="file"] {
            display: none;
        }

        .file-name {
            font-size: 13px;
            color: var(--success);
            font-weight: 500;
            margin-top: 6px;
        }

        /* ===== TOAST ===== */
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .toast {
            padding: 14px 20px;
            border-radius: var(--radius-md);
            backdrop-filter: blur(16px);
            border: 1px solid var(--border-glass);
            min-width: 300px;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: toastIn 0.4s cubic-bezier(0.22, 1, 0.36, 1);
            font-size: 14px;
            font-weight: 500;
        }

        .toast-success {
            background: rgba(34, 197, 94, 0.15);
            border-color: rgba(34, 197, 94, 0.3);
            color: var(--success);
        }

        .toast-error {
            background: rgba(239, 68, 68, 0.15);
            border-color: rgba(239, 68, 68, 0.3);
            color: var(--error);
        }

        .toast-warning {
            background: rgba(245, 158, 11, 0.15);
            border-color: rgba(245, 158, 11, 0.3);
            color: var(--warning);
        }

        @keyframes toastIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }

        .toast-close {
            margin-left: auto;
            cursor: pointer;
            opacity: 0.6;
            background: none;
            border: none;
            color: inherit;
            font-size: 18px;
        }

        /* ===== GRID LAYOUTS ===== */
        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
        }

        .grid-3 {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }

        /* ===== PAGINATION ===== */
        .pagination {
            display: flex;
            gap: 4px;
            justify-content: center;
            padding: 20px 0;
        }

        .pagination a,
        .pagination span {
            padding: 8px 14px;
            border-radius: var(--radius-sm);
            font-size: 13px;
            font-weight: 500;
            text-decoration: none;
            transition: var(--transition);
        }

        .pagination a {
            color: var(--text-secondary);
            border: 1px solid var(--border-glass);
        }

        .pagination a:hover {
            background: var(--accent-glow);
            border-color: var(--border-glow);
            color: var(--text-primary);
        }

        .pagination .active span {
            background: var(--accent-gradient);
            color: white;
        }

        .pagination .disabled span {
            color: var(--text-muted);
            opacity: 0.5;
        }

        /* ===== EMPTY STATE ===== */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }

        .empty-state i {
            font-size: 56px;
            color: var(--text-muted);
            margin-bottom: 16px;
        }

        .empty-state h3 {
            font-size: 18px;
            margin-bottom: 8px;
        }

        .empty-state p {
            color: var(--text-muted);
            font-size: 14px;
        }

        /* ===== CHART PLACEHOLDER ===== */
        .chart-bar {
            display: flex;
            align-items: flex-end;
            gap: 8px;
            height: 180px;
            padding-top: 20px;
        }

        .chart-bar-item {
            flex: 1;
            border-radius: 6px 6px 0 0;
            background: var(--accent-gradient);
            min-height: 20px;
            position: relative;
            transition: var(--transition);
            opacity: 0.7;
        }

        .chart-bar-item:hover {
            opacity: 1;
            transform: scaleY(1.02);
            transform-origin: bottom;
        }

        .chart-bar-label {
            position: absolute;
            bottom: -28px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 10px;
            color: var(--text-muted);
            white-space: nowrap;
        }

        .chart-bar-value {
            position: absolute;
            top: -22px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 10px;
            font-weight: 600;
            color: var(--text-secondary);
            white-space: nowrap;
        }

        /* ===== LOG ENTRIES ===== */
        .log-entry {
            padding: 12px 16px;
            border-radius: var(--radius-sm);
            margin-bottom: 8px;
            font-size: 13px;
            display: flex;
            gap: 10px;
            align-items: flex-start;
        }

        .log-entry.error { background: var(--error-bg); border-left: 3px solid var(--error); }
        .log-entry.warning { background: var(--warning-bg); border-left: 3px solid var(--warning); }
        .log-entry.info { background: var(--info-bg); border-left: 3px solid var(--info); }

        .log-entry i { margin-top: 2px; }

        /* ===== UTILITIES ===== */
        .mb-4 { margin-bottom: 16px; }
        .mb-6 { margin-bottom: 24px; }
        .mb-8 { margin-bottom: 32px; }
        .mt-4 { margin-top: 16px; }
        .mt-6 { margin-top: 24px; }
        .gap-3 { gap: 12px; }
        .flex { display: flex; }
        .items-center { align-items: center; }
        .justify-between { justify-content: space-between; }
        .text-right { text-align: right; }
        .text-sm { font-size: 13px; }
        .text-muted { color: var(--text-muted); }
        .font-mono { font-family: 'JetBrains Mono', 'Fira Code', monospace; }
        .truncate { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 1024px) {
            .sidebar { width: 72px; }
            .sidebar-label,
            .sidebar-link span,
            .sidebar-brand-text { display: none; }
            .sidebar-brand-link { justify-content: center; gap: 0; }
            .sidebar-brand { padding: 18px 8px; }
            .sidebar-link { justify-content: center; padding: 12px; }
            .sidebar-link.active::before { display: none; }
            .main-content { margin-left: 72px; }
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            .grid-2 { grid-template-columns: 1fr; }
        }

        /* ===== SCROLLBAR ===== */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: var(--border-glass); border-radius: 999px; }
        ::-webkit-scrollbar-thumb:hover { background: var(--text-muted); }
    </style>
    @stack('styles')
</head>
<body>
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-brand">
            <a href="{{ route('dashboard') }}" class="sidebar-brand-link">
                <img src="{{ asset('img/sigma-mark.png') }}" alt="Sigma" class="sidebar-mark">
                <span class="sidebar-brand-text">Sigma Sales Importer</span>
            </a>
        </div>
        <nav class="sidebar-nav">
            <div class="sidebar-label">Menu</div>
            <a href="{{ route('dashboard') }}" class="sidebar-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <i class="ri-dashboard-3-line"></i>
                <span>Dashboard</span>
            </a>
            <a href="{{ route('upload.index') }}" class="sidebar-link {{ request()->routeIs('upload.*') ? 'active' : '' }}">
                <i class="ri-upload-cloud-2-line"></i>
                <span>Upload Import</span>
            </a>
            <a href="{{ route('history.index') }}" class="sidebar-link {{ request()->routeIs('history.*') ? 'active' : '' }}">
                <i class="ri-history-line"></i>
                <span>History Log</span>
            </a>
            <a href="{{ route('output.index') }}" class="sidebar-link {{ request()->routeIs('output.*') ? 'active' : '' }}">
                <i class="ri-file-download-line"></i>
                <span>Output Files</span>
            </a>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <header class="header">
            <h1 class="header-title">@yield('page-title', 'Dashboard')</h1>
            <div class="header-actions">
                <span class="text-sm text-muted">
                    <i class="ri-time-line"></i>
                    {{ now()->format('d M Y, H:i') }}
                </span>
            </div>
        </header>

        <div class="content">
            @yield('content')
        </div>
    </main>

    <!-- Toast Container -->
    <div class="toast-container" id="toast-container">
        @if(session('success'))
            <div class="toast toast-success">
                <i class="ri-checkbox-circle-fill"></i>
                <span>{{ session('success') }}</span>
                <button class="toast-close" onclick="this.parentElement.remove()">&times;</button>
            </div>
        @endif
        @if(session('error'))
            <div class="toast toast-error">
                <i class="ri-error-warning-fill"></i>
                <span>{{ session('error') }}</span>
                <button class="toast-close" onclick="this.parentElement.remove()">&times;</button>
            </div>
        @endif
    </div>

    <script>
        // Auto-dismiss toasts
        document.querySelectorAll('.toast').forEach(toast => {
            setTimeout(() => {
                toast.style.animation = 'toastIn 0.3s reverse forwards';
                setTimeout(() => toast.remove(), 300);
            }, 5000);
        });

        // Show toast helper
        function showToast(message, type = 'success') {
            const container = document.getElementById('toast-container');
            const icons = { success: 'ri-checkbox-circle-fill', error: 'ri-error-warning-fill', warning: 'ri-alert-fill' };
            const toast = document.createElement('div');
            toast.className = `toast toast-${type}`;
            toast.innerHTML = `<i class="${icons[type] || icons.success}"></i><span>${message}</span><button class="toast-close" onclick="this.parentElement.remove()">&times;</button>`;
            container.appendChild(toast);
            setTimeout(() => { toast.style.animation = 'toastIn 0.3s reverse forwards'; setTimeout(() => toast.remove(), 300); }, 5000);
        }

        // Format number helper
        function formatNumber(n) {
            return new Intl.NumberFormat('id-ID').format(n);
        }

        // Format currency helper
        function formatCurrency(n) {
            return 'Rp ' + new Intl.NumberFormat('id-ID').format(n);
        }
    </script>
    @stack('scripts')
</body>
</html>
