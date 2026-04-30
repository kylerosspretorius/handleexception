@extends('layouts.invoice')
@section('title', 'My Invoices')

@section('content')
<div class="flex items-center justify-between mb-8">
    <div>
        <h1 class="font-heading text-2xl font-semibold text-white">My Invoices</h1>
        <p class="text-slate-400 text-sm mt-1">{{ $invoices->total() }} invoice{{ $invoices->total() !== 1 ? 's' : '' }}</p>
    </div>
    <a href="{{ route('invoices.create') }}" class="btn-primary px-5 py-2.5 rounded-xl text-sm font-medium flex items-center gap-2">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
        New Invoice
    </a>
</div>

@if($invoices->isEmpty())
    <div class="bg-bg-card border border-white/[0.07] rounded-2xl p-16 text-center">
        <div class="w-16 h-16 rounded-2xl bg-accent/10 flex items-center justify-center mx-auto mb-5">
            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#0ea5e9" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
        </div>
        <h3 class="font-heading text-lg font-semibold text-white mb-2">No invoices yet</h3>
        <p class="text-slate-400 text-sm mb-6">Create your first invoice and it will appear here.</p>
        <a href="{{ route('invoices.create') }}" class="btn-primary px-6 py-2.5 rounded-xl text-sm font-medium inline-flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
            Create Invoice
        </a>
    </div>
@else
    <div class="bg-bg-card border border-white/[0.07] rounded-2xl overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-white/[0.07]">
                    <th class="text-left px-6 py-4 text-slate-400 font-medium text-xs uppercase tracking-wider">Invoice</th>
                    <th class="text-left px-6 py-4 text-slate-400 font-medium text-xs uppercase tracking-wider">Client</th>
                    <th class="text-left px-6 py-4 text-slate-400 font-medium text-xs uppercase tracking-wider hidden md:table-cell">Date</th>
                    <th class="text-left px-6 py-4 text-slate-400 font-medium text-xs uppercase tracking-wider hidden md:table-cell">Due</th>
                    <th class="text-right px-6 py-4 text-slate-400 font-medium text-xs uppercase tracking-wider">Amount</th>
                    <th class="text-center px-6 py-4 text-slate-400 font-medium text-xs uppercase tracking-wider hidden sm:table-cell">Status</th>
                    <th class="px-6 py-4"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/[0.05]">
                @foreach($invoices as $invoice)
                <tr class="hover:bg-bg-card-hover transition-colors">
                    <td class="px-6 py-4">
                        <a href="{{ route('invoices.show', $invoice) }}" class="font-mono text-accent hover:text-white transition-colors text-sm">
                            {{ $invoice->invoice_number }}
                        </a>
                    </td>
                    <td class="px-6 py-4 text-slate-300">{{ $invoice->to_name }}</td>
                    <td class="px-6 py-4 text-slate-400 hidden md:table-cell">{{ $invoice->invoice_date->format('d M Y') }}</td>
                    <td class="px-6 py-4 text-slate-400 hidden md:table-cell">
                        {{ $invoice->due_date ? $invoice->due_date->format('d M Y') : '—' }}
                    </td>
                    <td class="px-6 py-4 text-right font-mono text-white font-medium">
                        {{ $invoice->currency }} {{ number_format($invoice->total, 2) }}
                    </td>
                    <td class="px-6 py-4 text-center hidden sm:table-cell">
                        @php
                            $statusStyles = [
                                'draft' => 'bg-slate-700/50 text-slate-300',
                                'sent'  => 'bg-blue-500/20 text-blue-300',
                                'paid'  => 'bg-emerald-500/20 text-emerald-300',
                            ];
                        @endphp
                        <span class="px-2.5 py-1 rounded-full text-xs font-medium {{ $statusStyles[$invoice->status] }}">
                            {{ ucfirst($invoice->status) }}
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center justify-end gap-2">
                            @if($invoice->pdf_s3_key)
                                <a href="{{ route('invoices.download', $invoice) }}" title="Download PDF"
                                   class="w-8 h-8 rounded-lg bg-accent/10 hover:bg-accent/20 flex items-center justify-center transition-colors text-accent">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                                </a>
                            @endif
                            <form method="POST" action="{{ route('invoices.destroy', $invoice) }}"
                                  onsubmit="return confirm('Delete {{ $invoice->invoice_number }}?')">
                                @csrf @method('DELETE')
                                <button type="submit" title="Delete"
                                    class="w-8 h-8 rounded-lg hover:bg-red-500/10 flex items-center justify-center transition-colors text-slate-600 hover:text-red-400">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4h6v2"/></svg>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if($invoices->hasPages())
        <div class="mt-6">{{ $invoices->links() }}</div>
    @endif
@endif
@endsection
