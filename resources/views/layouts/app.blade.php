<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('queue-monitor.dashboard.title', 'Queue Monitor') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</head>

<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark border-bottom">
        <div class="container-fluid">
            <a class="navbar-brand d-flex align-items-center" href="#">
                <span class="badge bg-danger me-2">Q</span>
                <span>Queue Monitor</span>
            </a>
            <div class="d-flex align-items-center small text-light">
                <span class="me-3 d-none d-sm-inline-flex align-items-center">
                    <span class="badge bg-success rounded-circle me-1">&nbsp;</span>
                    <span>Connected</span>
                </span>
                <span class="badge bg-secondary text-uppercase">
                    {{ app()->environment() }}
                </span>
            </div>
        </div>
    </nav>

    <main class="py-4">
        <div class="container">
            @if (session('queue-monitor.success'))
                <div class="alert alert-success small">
                    {{ session('queue-monitor.success') }}
                </div>
            @endif
            @if (session('queue-monitor.error'))
                <div class="alert alert-danger small">
                    {{ session('queue-monitor.error') }}
                </div>
            @endif

            @yield('content')
        </div>
    </main>
</body>

</html>
