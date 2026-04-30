@extends('layouts.invoice')
@section('title', $invoice->invoice_number)

@section('content')
<div class="flex items-start justify-between mb-8 gap-4">
    <div>
        <a href="{{ route('invoices.index') }}" class="text-slate-400 hover:text-white text-sm flex items-center gap-2 w-fit transition-colors mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
            Back to invoices
        </a>
        <h1 class="font-mono text-2xl font-semibold text-accent">{{ $invoice->invoice_number }}</h1>
        <p class="text-slate-400 text-sm mt-1">Created {{ $invoice->created_at->format('d M Y') }}</p>
    </div>
    @if($invoice->pdf_s3_key)
        <a href="{{ route('invoices.download', $invoice) }}"
           class="btn-primary px-5 py-2.5 rounded-xl text-sm font-medium flex items-center gap-2 shrink-0">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Download PDF
        </a>
    @endif
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 space-y-6">

        <div class="bg-bg-card border border-white/[0.07] rounded-2xl p-6">
            <div class="grid grid-cols-2 gap-6">
                <div>
                    <p class="text-xs text-slate-500 uppercase tracking-wider mb-2">From</p>
                    <p class="font-semibold text-white">{{ $invoice->from_name }}</p>
                    @if($invoice->from_email)<p class="text-slate-400 text-sm">{{ $invoice->from_email }}</p>@endif
                    @if($invoice->from_address)<p class="text-slate-400 text-sm whitespace-pre-line">{{ $invoice->from_address }}</p>@endif
                    @if($invoice->from_vat)<p class="text-slate-500 text-xs mt-1">VAT: {{ $invoice->from_vat }}</p>@endif
                </div>
                <div>
                    <p class="text-xs text-slate-500 uppercase tracking-wider mb-2">Bill To</p>
                    <p class="font-semibold text-white">{{ $invoice->to_name }}</p>
                    @if($invoice->to_email)<p class="text-slate-400 text-sm">{{ $invoice->to_email }}</p>@endif
                    @if($invoice->to_address)<p class="text-slate-400 text-sm whitespace-pre-line">{{ $invoice->to_address }}</p>@endif
                    @if($invoice->to_vat)<p class="text-slate-500 text-xs mt-1">VAT: {{ $invoice->to_vat }}</p>@endif
                </div>
            </div>
        </div>

        <div class="bg-bg-card border border-white/[0.07] rounded-2xl overflow-hidden">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-white/[0.07]">
                        <th class="text-left px-6 py-4 text-slate-400 font-medium text-xs uppercase tracking-wider">Description</th>
                        <th class="text-right px-6 py-4 text-slate-400 font-medium text-xs uppercase tracking-wider">Qty</th>
                        <th class="text-right px-6 py-4 text-slate-400 font-medium text-xs uppercase tracking-wider">Unit Price</th>
                        <th class="text-right px-6 py-4 text-slate-400 font-medium text-xs uppercase tracking-wider">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/[0.05]">
                    @foreach($invoice->items as $item)
                    <tr>
                        <td class="px-6 py-4 text-slate-300">{{ $item->description }}</td>
                        <td class="px-6 py-4 text-right font-mono text-slate-400">{{ rtrim(rtrim(number_format($item->quantity, 2), '0'), '.') }}</td>
                        <td class="px-6 py-4 text-right font-mono text-slate-300">{{ number_format($item->unit_price, 2) }}</td>
                        <td class="px-6 py-4 text-right font-mono text-white font-medium">{{ number_format($item->line_total, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($invoice->notes)
        <div class="bg-bg-card border border-white/[0.07] rounded-2xl p-6">
            <p class="text-xs text-slate-500 uppercase tracking-wider mb-2">Notes</p>
            <p class="text-slate-300 text-sm whitespace-pre-line">{{ $invoice->notes }}</p>
        </div>
        @endif
    </div>

    <div>
        <div class="bg-bg-card border border-white/[0.07] rounded-2xl p-6 space-y-3 text-sm">
            <div class="flex justify-between text-slate-400">
                <span>Invoice Date</span>
                <span class="text-white">{{ $invoice->invoice_date->format('d M Y') }}</span>
            </div>
            @if($invoice->due_date)
            <div class="flex justify-between text-slate-400">
                <span>Due Date</span>
                <span class="text-white">{{ $invoice->due_date->format('d M Y') }}</span>
            </div>
            @endif
            <div class="border-t border-white/[0.07] pt-3 space-y-2">
                <div class="flex justify-between text-slate-400">
                    <span>Subtotal</span>
                    <span class="font-mono">{{ $invoice->currency }} {{ number_format($invoice->subtotal, 2) }}</span>
                </div>
                @if($invoice->tax_rate)
                <div class="flex justify-between text-slate-400">
                    <span>Tax ({{ $invoice->tax_rate }}%)</span>
                    <span class="font-mono">{{ $invoice->currency }} {{ number_format($invoice->tax_amount, 2) }}</span>
                </div>
                @endif
                @if($invoice->discount > 0)
                <div class="flex justify-between text-slate-400">
                    <span>Discount</span>
                    <span class="font-mono text-emerald-400">-{{ $invoice->currency }} {{ number_format($invoice->discount, 2) }}</span>
                </div>
                @endif
                <div class="border-t border-white/[0.07] pt-3 flex justify-between font-semibold">
                    <span class="text-white">Total</span>
                    <span class="font-mono text-accent text-xl">{{ $invoice->currency }} {{ number_format($invoice->total, 2) }}</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
