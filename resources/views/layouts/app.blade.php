<!DOCTYPE html>
<html lang="en" data-theme="dark">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('queue-monitor.dashboard.title', 'Queue Monitor') }}</title>

    {{-- Google Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    {{-- Chart.js --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    {{-- Lucide Icons --}}
    <script src="https://unpkg.com/lucide@latest"></script>

    <style>
        :root {
            /* Dark Theme (Default) */
            --bg-primary: #0f0f23;
            --bg-secondary: #1a1a2e;
            --bg-tertiary: #16213e;
            --bg-card: rgba(26, 26, 46, 0.8);
            --bg-card-hover: rgba(26, 26, 46, 0.95);

            --text-primary: #ffffff;
            --text-secondary: #a0a0b8;
            --text-muted: #6b6b80;

            --accent-primary: #667eea;
            --accent-secondary: #764ba2;
            --accent-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);

            --success: #10b981;
            --success-bg: rgba(16, 185, 129, 0.15);
            --warning: #f59e0b;
            --warning-bg: rgba(245, 158, 11, 0.15);
            --danger: #ef4444;
            --danger-bg: rgba(239, 68, 68, 0.15);
            --info: #3b82f6;
            --info-bg: rgba(59, 130, 246, 0.15);

            --border-color: rgba(255, 255, 255, 0.08);
            --border-color-light: rgba(255, 255, 255, 0.12);

            --shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.3);
            --shadow-md: 0 4px 16px rgba(0, 0, 0, 0.4);
            --shadow-lg: 0 8px 32px rgba(0, 0, 0, 0.5);
            --shadow-glow: 0 0 40px rgba(102, 126, 234, 0.15);

            --radius-sm: 8px;
            --radius-md: 12px;
            --radius-lg: 16px;
            --radius-xl: 24px;

            --header-height: 72px;

            --transition-fast: 150ms ease;
            --transition-normal: 250ms ease;
            --transition-slow: 350ms ease;
        }

        [data-theme="light"] {
            --bg-primary: #f8fafc;
            --bg-secondary: #ffffff;
            --bg-tertiary: #f1f5f9;
            --bg-card: rgba(255, 255, 255, 0.9);
            --bg-card-hover: rgba(255, 255, 255, 1);

            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --text-muted: #94a3b8;

            --border-color: rgba(0, 0, 0, 0.08);
            --border-color-light: rgba(0, 0, 0, 0.12);

            --shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.08);
            --shadow-md: 0 4px 16px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 8px 32px rgba(0, 0, 0, 0.12);
            --shadow-glow: 0 0 40px rgba(102, 126, 234, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            line-height: 1.6;
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* Background Pattern */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background:
                radial-gradient(circle at 20% 20%, rgba(102, 126, 234, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(118, 75, 162, 0.1) 0%, transparent 50%);
            pointer-events: none;
            z-index: -1;
        }

        /* Layout */
        .app-wrapper {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        /* Navbar */
        .navbar {
            height: var(--header-height);
            background: var(--bg-secondary);
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 32px;
            position: sticky;
            top: 0;
            z-index: 100;
            backdrop-filter: blur(12px);
        }

        .navbar-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: var(--text-primary);
            margin-right: 48px;
        }

        .brand-icon {
            width: 36px;
            height: 36px;
            background: var(--accent-gradient);
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 16px;
            color: white;
            box-shadow: var(--shadow-glow);
        }

        .brand-text h1 {
            font-size: 16px;
            font-weight: 600;
            letter-spacing: -0.02em;
        }

        .brand-text span {
            font-size: 11px;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .navbar-nav {
            display: flex;
            align-items: center;
            gap: 8px;
            flex: 1;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 16px;
            color: var(--text-secondary);
            text-decoration: none;
            border-radius: var(--radius-md);
            font-size: 14px;
            font-weight: 500;
            transition: all var(--transition-fast);
        }

        .nav-link:hover {
            background: var(--bg-tertiary);
            color: var(--text-primary);
        }

        .nav-link.active {
            background: var(--accent-gradient);
            color: white;
            box-shadow: var(--shadow-sm);
        }

        .nav-link i {
            width: 18px;
            height: 18px;
            opacity: 0.85;
        }

        .nav-link.active i {
            opacity: 1;
        }

        .nav-badge {
            background: var(--danger);
            color: white;
            font-size: 11px;
            font-weight: 600;
            padding: 2px 8px;
            border-radius: 100px;
            min-width: 22px;
            text-align: center;
        }

        .navbar-right {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .env-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            background: var(--bg-tertiary);
            border-radius: var(--radius-sm);
            font-size: 12px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            color: var(--text-secondary);
        }

        .env-badge::before {
            content: '';
            width: 8px;
            height: 8px;
            background: var(--success);
            border-radius: 50%;
            animation: pulse 2s ease-in-out infinite;
        }

        .theme-toggle {
            background: var(--bg-tertiary);
            border: 1px solid var(--border-color);
            color: var(--text-secondary);
            cursor: pointer;
            padding: 10px;
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all var(--transition-fast);
        }

        .theme-toggle:hover {
            background: var(--bg-card-hover);
            color: var(--text-primary);
            border-color: var(--border-color-light);
        }

        .refresh-indicator {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 14px;
            background: var(--success-bg);
            color: var(--success);
            border-radius: var(--radius-md);
            font-size: 13px;
            font-weight: 500;
        }

        .refresh-indicator i {
            animation: spin 2s linear infinite;
        }

        @keyframes pulse {

            0%,
            100% {
                opacity: 1;
                transform: scale(1);
            }

            50% {
                opacity: 0.6;
                transform: scale(0.9);
            }
        }

        @keyframes spin {
            from {
                transform: rotate(0deg);
            }

            to {
                transform: rotate(360deg);
            }
        }

        /* Page Content */
        .page-content {
            flex: 1;
            padding: 32px;
            max-width: 1600px;
            width: 100%;
            margin: 0 auto;
        }

        /* Toast Notifications */
        .toast-container {
            position: fixed;
            top: 88px;
            /* Below Navbar */
            right: 32px;
            z-index: 1000;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .toast {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            padding: 16px 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 320px;
            box-shadow: var(--shadow-lg);
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(100px);
            }

            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .toast-success {
            border-left: 4px solid var(--success);
        }

        .toast-success i {
            color: var(--success);
        }

        .toast-error {
            border-left: 4px solid var(--danger);
        }

        .toast-error i {
            color: var(--danger);
        }

        .toast-message {
            flex: 1;
            font-size: 14px;
        }

        .toast-close {
            background: none;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            padding: 4px;
            transition: color var(--transition-fast);
        }

        .toast-close:hover {
            color: var(--text-primary);
        }

        /* Utility Classes */
        .card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            backdrop-filter: blur(12px);
            transition: all var(--transition-normal);
        }

        .card:hover {
            box-shadow: var(--shadow-md);
            border-color: var(--border-color-light);
        }

        .card-header {
            padding: 20px 24px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .card-title {
            font-size: 16px;
            font-weight: 600;
            color: var(--text-primary);
        }

        .card-subtitle {
            font-size: 13px;
            color: var(--text-muted);
        }

        .card-body {
            padding: 24px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 10px 20px;
            font-size: 14px;
            font-weight: 500;
            font-family: inherit;
            border-radius: var(--radius-md);
            border: none;
            cursor: pointer;
            transition: all var(--transition-fast);
            text-decoration: none;
        }

        .btn-primary {
            background: var(--accent-gradient);
            color: white;
            box-shadow: var(--shadow-sm);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md), var(--shadow-glow);
        }

        .btn-secondary {
            background: var(--bg-tertiary);
            color: var(--text-primary);
            border: 1px solid var(--border-color);
        }

        .btn-secondary:hover {
            background: var(--bg-card-hover);
            border-color: var(--border-color-light);
        }

        .btn-success {
            background: var(--success);
            color: white;
        }

        .btn-success:hover {
            filter: brightness(1.1);
        }

        .btn-danger {
            background: var(--danger);
            color: white;
        }

        .btn-danger:hover {
            filter: brightness(1.1);
        }

        .btn-sm {
            padding: 8px 14px;
            font-size: 13px;
        }

        .btn-ghost {
            background: transparent;
            color: var(--text-secondary);
        }

        .btn-ghost:hover {
            background: var(--bg-tertiary);
            color: var(--text-primary);
        }

        /* Status Badges */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 12px;
            font-size: 12px;
            font-weight: 600;
            border-radius: 100px;
        }

        .badge-success {
            background: var(--success-bg);
            color: var(--success);
        }

        .badge-warning {
            background: var(--warning-bg);
            color: var(--warning);
        }

        .badge-danger {
            background: var(--danger-bg);
            color: var(--danger);
        }

        .badge-info {
            background: var(--info-bg);
            color: var(--info);
        }

        /* Form Elements */
        .form-input {
            width: 100%;
            padding: 12px 16px;
            background: var(--bg-tertiary);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            color: var(--text-primary);
            font-size: 14px;
            font-family: inherit;
            transition: all var(--transition-fast);
        }

        .form-input:focus {
            outline: none;
            border-color: var(--accent-primary);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.2);
        }

        .form-input::placeholder {
            color: var(--text-muted);
        }

        textarea.form-input {
            resize: vertical;
            min-height: 100px;
        }

        .form-checkbox {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            font-size: 14px;
            color: var(--text-secondary);
        }

        .form-checkbox input[type="checkbox"] {
            width: 18px;
            height: 18px;
            accent-color: var(--accent-primary);
            cursor: pointer;
        }

        /* Table Styles */
        .table-wrapper {
            overflow-x: auto;
            border-radius: var(--radius-lg);
            border: 1px solid var(--border-color);
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th {
            background: var(--bg-tertiary);
            padding: 14px 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-muted);
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }

        .table td {
            padding: 16px 20px;
            font-size: 14px;
            color: var(--text-secondary);
            border-bottom: 1px solid var(--border-color);
            transition: background var(--transition-fast);
        }

        .table tr:last-child td {
            border-bottom: none;
        }

        .table tr:hover td {
            background: var(--bg-tertiary);
        }

        .table-link {
            color: var(--accent-primary);
            text-decoration: none;
            font-weight: 500;
            transition: color var(--transition-fast);
        }

        .table-link:hover {
            color: var(--accent-secondary);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }

        .empty-state-icon {
            width: 64px;
            height: 64px;
            margin: 0 auto 20px;
            color: var(--text-muted);
            opacity: 0.5;
        }

        .empty-state-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 8px;
        }

        .empty-state-text {
            font-size: 14px;
            color: var(--text-muted);
        }

        /* Helper Classes */
        .grid {
            display: grid;
            gap: 24px;
        }

        .grid-cols-2 {
            grid-template-columns: repeat(2, 1fr);
        }

        .grid-cols-3 {
            grid-template-columns: repeat(3, 1fr);
        }

        .grid-cols-4 {
            grid-template-columns: repeat(4, 1fr);
        }

        @media (max-width: 1200px) {
            .grid-cols-4 {
                grid-template-columns: repeat(2, 1fr);
            }

            .grid-cols-3 {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 640px) {

            .grid-cols-4,
            .grid-cols-3,
            .grid-cols-2 {
                grid-template-columns: 1fr;
            }
        }

        .flex {
            display: flex;
        }

        .flex-col {
            flex-direction: column;
        }

        .items-center {
            align-items: center;
        }

        .justify-between {
            justify-content: space-between;
        }

        .gap-2 {
            gap: 8px;
        }

        .gap-3 {
            gap: 12px;
        }

        .gap-4 {
            gap: 16px;
        }

        .gap-6 {
            gap: 24px;
        }

        .mb-2 {
            margin-bottom: 8px;
        }

        .mb-3 {
            margin-bottom: 12px;
        }

        .mb-4 {
            margin-bottom: 16px;
        }

        .mb-6 {
            margin-bottom: 24px;
        }

        .mt-4 {
            margin-top: 16px;
        }

        .mt-6 {
            margin-top: 24px;
        }

        .text-sm {
            font-size: 13px;
        }

        .text-xs {
            font-size: 11px;
        }

        .text-muted {
            color: var(--text-muted);
        }

        .text-success {
            color: var(--success);
        }

        .text-danger {
            color: var(--danger);
        }

        .text-warning {
            color: var(--warning);
        }

        .font-medium {
            font-weight: 500;
        }

        .font-semibold {
            font-weight: 600;
        }

        .truncate {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        pre,
        code {
            font-family: 'JetBrains Mono', 'Fira Code', 'Consolas', monospace;
            font-size: 13px;
        }

        pre {
            background: var(--bg-tertiary);
            padding: 16px;
            border-radius: var(--radius-md);
            overflow-x: auto;
            border: 1px solid var(--border-color);
        }

        code {
            background: var(--bg-tertiary);
            padding: 2px 6px;
            border-radius: 4px;
        }

        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-track {
            background: transparent;
        }

        ::-webkit-scrollbar-thumb {
            background: var(--border-color-light);
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--text-muted);
        }

        .page-title {
            font-size: 28px;
            font-weight: 700;
            letter-spacing: -0.02em;
            margin-bottom: 8px;
        }

        .page-subtitle {
            font-size: 15px;
            color: var(--text-muted);
        }

        /* Responsive Navbar */
        @media (max-width: 768px) {
            .navbar {
                padding: 0 20px;
            }

            .navbar-nav {
                display: none;
                position: absolute;
                top: var(--header-height);
                left: 0;
                right: 0;
                background: var(--bg-secondary);
                flex-direction: column;
                padding: 16px;
                border-bottom: 1px solid var(--border-color);
                gap: 4px;
            }

            .navbar-nav.show {
                display: flex;
            }

            .navbar-brand {
                margin-right: 0;
            }

            .page-content {
                padding: 20px;
            }

            .nav-link {
                width: 100%;
                border-radius: var(--radius-sm);
            }
        }
    </style>
</head>

<body>
    <div class="app-wrapper">
        {{-- Navbar --}}
        <nav class="navbar">
            <div class="flex items-center">
                <a href="{{ route('queue-monitor.dashboard') }}" class="navbar-brand">
                    <div class="brand-icon">Q</div>
                    <div class="brand-text">
                        <h1>Queue Monitor</h1>
                        <span>v1.0</span>
                    </div>
                </a>

                {{-- Desktop Nav --}}
                <div class="navbar-nav" id="navbarNav">
                    <a href="{{ route('queue-monitor.dashboard') }}"
                        class="nav-link {{ request()->routeIs('queue-monitor.dashboard') ? 'active' : '' }}">
                        <i data-lucide="layout-dashboard"></i>
                        Dashboard
                    </a>

                    <a href="{{ route('queue-monitor.failures.index') }}"
                        class="nav-link {{ request()->routeIs('queue-monitor.failures.*') ? 'active' : '' }}">
                        <i data-lucide="alert-triangle"></i>
                        Failed Jobs
                        @php
                            $unresolvedNavCount = \NikunjKothiya\QueueMonitor\Models\QueueFailure::unresolved()->count();
                        @endphp
                        @if($unresolvedNavCount > 0)
                            <span class="nav-badge">{{ $unresolvedNavCount > 99 ? '99+' : $unresolvedNavCount }}</span>
                        @endif
                    </a>
                </div>
            </div>

            <div class="navbar-right">
                <div class="env-badge">
                    {{ app()->environment() }}
                </div>

                @if(config('queue-monitor.dashboard.auto_refresh_seconds', 0) > 0)
                    <div class="refresh-indicator" id="refreshIndicator" title="Auto Refresh">
                        <i data-lucide="refresh-cw" style="width: 14px; height: 14px;"></i>
                        <span id="refreshCountdown"
                            class="text-xs">{{ config('queue-monitor.dashboard.auto_refresh_seconds') }}s</span>
                    </div>
                @endif

                <button class="theme-toggle" onclick="toggleTheme()" title="Toggle theme">
                    <i data-lucide="moon" style="width: 18px; height: 18px;" id="themeIcon"></i>
                </button>
            </div>
        </nav>

        {{-- Toast Notifications --}}
        <div class="toast-container" id="toastContainer">
            @if (session('queue-monitor.success'))
                <div class="toast toast-success">
                    <i data-lucide="check-circle" style="width: 20px; height: 20px;"></i>
                    <div class="toast-message">{{ session('queue-monitor.success') }}</div>
                    <button class="toast-close" onclick="this.parentElement.remove()">
                        <i data-lucide="x" style="width: 16px; height: 16px;"></i>
                    </button>
                </div>
            @endif
            @if (session('queue-monitor.error'))
                <div class="toast toast-error">
                    <i data-lucide="alert-circle" style="width: 20px; height: 20px;"></i>
                    <div class="toast-message">{{ session('queue-monitor.error') }}</div>
                    <button class="toast-close" onclick="this.parentElement.remove()">
                        <i data-lucide="x" style="width: 16px; height: 16px;"></i>
                    </button>
                </div>
            @endif
        </div>

        {{-- Page Content --}}
        <div class="page-content">
            @yield('content')
        </div>
    </div>

    <script>
        // Initialize Lucide icons
        lucide.createIcons();

        // Theme Toggle
        function toggleTheme() {
            const html = document.documentElement;
            const currentTheme = html.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            html.setAttribute('data-theme', newTheme);
            localStorage.setItem('queue-monitor-theme', newTheme);
            updateThemeIcon();
        }

        function updateThemeIcon() {
            const icon = document.getElementById('themeIcon');
            const theme = document.documentElement.getAttribute('data-theme');
            if (icon) {
                icon.setAttribute('data-lucide', theme === 'dark' ? 'moon' : 'sun');
                lucide.createIcons();
            }
        }

        // Load saved theme
        const savedTheme = localStorage.getItem('queue-monitor-theme');
        if (savedTheme) {
            document.documentElement.setAttribute('data-theme', savedTheme);
            updateThemeIcon();
        }

        // Mobile Menu Toggle
        function toggleMobileMenu() {
            document.getElementById('navbarNav').classList.toggle('show');
        }

        // Auto-refresh countdown
        const autoRefreshSeconds = {{ (int) config('queue-monitor.dashboard.auto_refresh_seconds', 0) }};
        if (autoRefreshSeconds > 0) {
            let countdown = autoRefreshSeconds;
            const countdownEl = document.getElementById('refreshCountdown');

            setInterval(function () {
                countdown--;
                if (countdownEl) {
                    countdownEl.textContent = countdown + 's';
                }
                if (countdown <= 0) {
                    window.location.reload();
                }
            }, 1000);
        }

        // Auto-dismiss toasts
        document.querySelectorAll('.toast').forEach(toast => {
            setTimeout(() => {
                toast.style.animation = 'slideOut 0.3s ease forwards';
                setTimeout(() => toast.remove(), 300);
            }, 5000);
        });
    </script>
    @yield('scripts')
</body>

</html>