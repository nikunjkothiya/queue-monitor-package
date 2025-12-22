<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('queue-monitor.dashboard.title', 'Queue Monitor') }}</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-neutral-950 text-neutral-100">
    <div class="min-h-screen flex flex-col">
        <nav class="border-b border-neutral-800 bg-neutral-950/80 backdrop-blur">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-red-600/20 text-red-400 text-lg font-semibold">
                        Q
                    </span>
                    <div>
                        <div class="font-semibold text-sm tracking-tight">Queue Monitor</div>
                        <div class="text-[11px] text-neutral-400">Production queue health overview</div>
                    </div>
                </div>
                <div class="flex items-center space-x-4 text-xs text-neutral-400">
                    <span class="hidden sm:inline-flex items-center space-x-1">
                        <span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span>Connected</span>
                    </span>
                    <span class="px-2 py-1 rounded-full bg-neutral-900 border border-neutral-800">
                        {{ ucfirst(app()->environment()) }}
                    </span>
                </div>
            </div>
        </nav>

        <main class="flex-1">
            <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8 space-y-4">
                @if (session('queue-monitor.success'))
                    <div class="rounded-md border border-emerald-800/60 bg-emerald-900/40 px-4 py-3 text-xs text-emerald-100">
                        {{ session('queue-monitor.success') }}
                    </div>
                @endif
                @if (session('queue-monitor.error'))
                    <div class="rounded-md border border-red-800/60 bg-red-900/40 px-4 py-3 text-xs text-red-100">
                        {{ session('queue-monitor.error') }}
                    </div>
                @endif

                @yield('content')
            </div>
        </main>
    </div>
</body>

</html>
