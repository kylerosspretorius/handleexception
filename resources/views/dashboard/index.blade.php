@extends('layouts.dashboard')
@section('title', 'Dashboard')

@section('content')

<div class="mb-8">
    <h1 class="font-heading text-2xl font-semibold text-white">Dashboard</h1>
    <p class="text-slate-400 text-sm mt-1">Welcome back, {{ auth()->user()->name }}</p>
</div>

{{-- Stats bar --}}
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-10">
    <div class="bg-bg-card border border-white/[0.07] rounded-2xl px-6 py-5">
        <p class="text-xs text-slate-500 uppercase tracking-wider mb-1">Total Invoices</p>
        <p class="font-heading text-2xl font-semibold text-white">{{ $stats['total'] }}</p>
    </div>
    <div class="bg-bg-card border border-white/[0.07] rounded-2xl px-6 py-5">
        <p class="text-xs text-slate-500 uppercase tracking-wider mb-1">Invoiced This Month</p>
        <p class="font-heading text-2xl font-semibold text-accent font-mono">
            {{ $stats['currency'] }} {{ number_format($stats['revenue'], 2) }}
        </p>
    </div>
    <div class="bg-bg-card border border-white/[0.07] rounded-2xl px-6 py-5">
        <p class="text-xs text-slate-500 uppercase tracking-wider mb-1">Unpaid</p>
        <p class="font-heading text-2xl font-semibold text-white">{{ $stats['unpaid'] }}</p>
    </div>
</div>

{{-- Feature cards --}}
<div class="mb-4">
    <h2 class="font-heading text-sm font-medium text-slate-400 uppercase tracking-wider">Tools</h2>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">

    {{-- Invoices — active --}}
    <a href="{{ route('dashboard.invoices.index') }}"
       class="bg-bg-card border border-white/[0.07] rounded-2xl p-6 hover:border-accent/30 hover:bg-bg-card-hover transition-all group card-glow">
        <div class="w-12 h-12 rounded-xl bg-accent/10 flex items-center justify-center mb-4 group-hover:bg-accent/20 transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#0ea5e9" stroke-width="1.5">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                <polyline points="14 2 14 8 20 8"/>
                <line x1="16" y1="13" x2="8" y2="13"/>
                <line x1="16" y1="17" x2="8" y2="17"/>
                <polyline points="10 9 9 9 8 9"/>
            </svg>
        </div>
        <div class="flex items-center justify-between mb-1">
            <h2 class="font-heading text-base font-semibold text-white">Invoices</h2>
        </div>
        <p class="text-slate-400 text-sm">Create and manage client invoices</p>
    </a>

    {{-- Clients — coming soon --}}
    <div class="bg-bg-card border border-white/[0.04] rounded-2xl p-6 opacity-50 cursor-not-allowed">
        <div class="w-12 h-12 rounded-xl bg-white/[0.04] flex items-center justify-center mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="1.5">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                <circle cx="9" cy="7" r="4"/>
                <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
            </svg>
        </div>
        <div class="flex items-center justify-between mb-1">
            <h2 class="font-heading text-base font-semibold text-slate-400">Clients</h2>
            <span class="text-xs bg-white/[0.06] text-slate-500 px-2 py-0.5 rounded-full">Soon</span>
        </div>
        <p class="text-slate-500 text-sm">Manage your client contacts</p>
    </div>

    {{-- Expenses — coming soon --}}
    <div class="bg-bg-card border border-white/[0.04] rounded-2xl p-6 opacity-50 cursor-not-allowed">
        <div class="w-12 h-12 rounded-xl bg-white/[0.04] flex items-center justify-center mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="1.5">
                <rect width="20" height="14" x="2" y="5" rx="2"/>
                <line x1="2" y1="10" x2="22" y2="10"/>
            </svg>
        </div>
        <div class="flex items-center justify-between mb-1">
            <h2 class="font-heading text-base font-semibold text-slate-400">Expenses</h2>
            <span class="text-xs bg-white/[0.06] text-slate-500 px-2 py-0.5 rounded-full">Soon</span>
        </div>
        <p class="text-slate-500 text-sm">Track and categorise expenses</p>
    </div>

    {{-- Reports — coming soon --}}
    <div class="bg-bg-card border border-white/[0.04] rounded-2xl p-6 opacity-50 cursor-not-allowed">
        <div class="w-12 h-12 rounded-xl bg-white/[0.04] flex items-center justify-center mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="1.5">
                <line x1="18" y1="20" x2="18" y2="10"/>
                <line x1="12" y1="20" x2="12" y2="4"/>
                <line x1="6" y1="20" x2="6" y2="14"/>
            </svg>
        </div>
        <div class="flex items-center justify-between mb-1">
            <h2 class="font-heading text-base font-semibold text-slate-400">Reports</h2>
            <span class="text-xs bg-white/[0.06] text-slate-500 px-2 py-0.5 rounded-full">Soon</span>
        </div>
        <p class="text-slate-500 text-sm">Revenue and financial summaries</p>
    </div>

    {{-- Time Tracking — coming soon --}}
    <div class="bg-bg-card border border-white/[0.04] rounded-2xl p-6 opacity-50 cursor-not-allowed">
        <div class="w-12 h-12 rounded-xl bg-white/[0.04] flex items-center justify-center mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="1.5">
                <circle cx="12" cy="12" r="10"/>
                <polyline points="12 6 12 12 16 14"/>
            </svg>
        </div>
        <div class="flex items-center justify-between mb-1">
            <h2 class="font-heading text-base font-semibold text-slate-400">Time Tracking</h2>
            <span class="text-xs bg-white/[0.06] text-slate-500 px-2 py-0.5 rounded-full">Soon</span>
        </div>
        <p class="text-slate-500 text-sm">Log billable hours per project</p>
    </div>

    {{-- Contracts — coming soon --}}
    <div class="bg-bg-card border border-white/[0.04] rounded-2xl p-6 opacity-50 cursor-not-allowed">
        <div class="w-12 h-12 rounded-xl bg-white/[0.04] flex items-center justify-center mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="1.5">
                <path d="M12 22h6a2 2 0 0 0 2-2V7l-5-5H6a2 2 0 0 0-2 2v3"/>
                <path d="M14 2v4a2 2 0 0 0 2 2h4"/>
                <path d="M3 15h6"/>
                <path d="M3 18h6"/>
                <path d="M3 12h3"/>
            </svg>
        </div>
        <div class="flex items-center justify-between mb-1">
            <h2 class="font-heading text-base font-semibold text-slate-400">Contracts</h2>
            <span class="text-xs bg-white/[0.06] text-slate-500 px-2 py-0.5 rounded-full">Soon</span>
        </div>
        <p class="text-slate-500 text-sm">Send and sign client contracts</p>
    </div>

</div>
@endsection
