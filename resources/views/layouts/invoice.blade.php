<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Invoices') — handleexception.com</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=Space+Grotesk:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'bg-primary': '#0a0f1e',
                        'bg-secondary': '#0d1526',
                        'bg-card': '#111827',
                        'bg-card-hover': '#162035',
                        'accent': '#0ea5e9',
                        'accent-muted': 'rgba(14,165,233,0.12)',
                        'border-subtle': 'rgba(255,255,255,0.07)',
                        'border-accent': 'rgba(14,165,233,0.3)',
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                        heading: ['"Space Grotesk"', 'sans-serif'],
                        mono: ['"JetBrains Mono"', 'monospace'],
                    },
                }
            }
        }
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        body { background-color: #0a0f1e; color: #f1f5f9; }
        .card-glow:hover { box-shadow: 0 0 0 1px rgba(14,165,233,0.3), 0 8px 32px rgba(14,165,233,0.08); }
        .btn-primary { background: #0ea5e9; color: #fff; transition: all 0.2s; }
        .btn-primary:hover { background: #0284c7; }
        .btn-ghost { background: transparent; border: 1px solid rgba(255,255,255,0.1); color: #94a3b8; transition: all 0.2s; }
        .btn-ghost:hover { border-color: rgba(14,165,233,0.4); color: #f1f5f9; }
        .btn-danger { background: transparent; border: 1px solid rgba(239,68,68,0.3); color: #f87171; transition: all 0.2s; }
        .btn-danger:hover { background: rgba(239,68,68,0.1); }
        input, textarea, select {
            background: #0d1526;
            border: 1px solid rgba(255,255,255,0.07);
            color: #f1f5f9;
            border-radius: 8px;
            padding: 10px 14px;
            width: 100%;
            outline: none;
            transition: border-color 0.2s;
        }
        input:focus, textarea:focus, select:focus {
            border-color: rgba(14,165,233,0.5);
        }
        input::placeholder, textarea::placeholder { color: #475569; }
        label { color: #94a3b8; font-size: 0.8rem; font-weight: 500; letter-spacing: 0.05em; text-transform: uppercase; display: block; margin-bottom: 6px; }
        .flash-success { background: rgba(16,185,129,0.1); border: 1px solid rgba(16,185,129,0.3); color: #34d399; }
        .flash-error { background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.3); color: #f87171; }
    </style>
    @stack('head')
</head>
<body class="min-h-screen font-sans">

    <nav class="border-b border-white/[0.07] bg-bg-secondary/80 backdrop-blur sticky top-0 z-50">
        <div class="max-w-6xl mx-auto px-6 h-16 flex items-center justify-between">
            <div class="flex items-center gap-6">
                <a href="{{ route('home') }}" class="font-mono text-accent text-lg font-semibold">&lt;KP/&gt;</a>
                <span class="text-white/20">/</span>
                <a href="{{ route('invoices.index') }}" class="text-slate-300 hover:text-white transition-colors text-sm font-medium">Invoices</a>
            </div>
            <div class="flex items-center gap-4">
                @auth
                    <div class="flex items-center gap-3">
                        @if(auth()->user()->avatar)
                            <img src="{{ auth()->user()->avatar }}" alt="" class="w-8 h-8 rounded-full ring-2 ring-accent/30">
                        @endif
                        <span class="text-slate-400 text-sm hidden sm:block">{{ auth()->user()->name }}</span>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="btn-ghost text-xs px-3 py-1.5 rounded-lg">Sign out</button>
                    </form>
                @endauth
            </div>
        </div>
    </nav>

    <main class="max-w-6xl mx-auto px-6 py-10">

        @if(session('success'))
            <div class="flash-success rounded-xl px-5 py-4 mb-6 text-sm">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="flash-error rounded-xl px-5 py-4 mb-6 text-sm">{{ session('error') }}</div>
        @endif

        @yield('content')
    </main>

</body>
</html>
