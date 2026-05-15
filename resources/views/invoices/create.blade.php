@extends('layouts.dashboard')
@section('title', 'New Invoice')
@section('nav_section', 'Invoices')

@php
    $p = isset($invoice) ? $invoice : null;
    $defaultItems = $p
        ? $p->items->map(fn($i) => ['description' => $i->description, 'quantity' => $i->quantity, 'unit_price' => $i->unit_price])->toArray()
        : [['description' => '', 'quantity' => 1, 'unit_price' => 0]];
    $oldItems    = old('items', $defaultItems);
    $oldTaxRate  = old('tax_rate',  $p?->tax_rate  ?? 0);
    $oldDiscount = old('discount',  $p?->discount   ?? 0);
@endphp

@section('content')
<div class="mb-8">
    <a href="{{ route('dashboard.invoices.index') }}" class="text-slate-400 hover:text-white text-sm flex items-center gap-2 w-fit transition-colors mb-4">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
        Back to invoices
    </a>
    <h1 class="font-heading text-2xl font-semibold text-white">New Invoice</h1>
</div>

@isset($invoice)
<div class="flex items-center gap-3 bg-accent/10 border border-accent/20 rounded-xl px-5 py-3 mb-6 text-sm text-accent">
    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="14" height="14" x="8" y="8" rx="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg>
    Copied from <span class="font-semibold ml-1">{{ $invoice->invoice_number }}</span> &mdash; a new invoice number has been assigned.
</div>
@endisset

@if($errors->any())
    <div class="flash-error rounded-xl px-5 py-4 mb-6 text-sm space-y-1">
        @foreach($errors->all() as $error)
            <div>{{ $error }}</div>
        @endforeach
    </div>
@endif

<form method="POST" action="{{ route('dashboard.invoices.store') }}" enctype="multipart/form-data"
      x-data="invoiceBuilder()" @submit="recalculate()">
    @csrf

    {{-- Preview Modal --}}
    <div x-show="showPreview" x-cloak
         class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto py-10 px-4"
         style="background: rgba(0,0,0,0.75)">
        <div class="relative w-full max-w-2xl">
            <button type="button" @click="showPreview = false"
                    class="absolute -top-8 right-0 text-slate-400 hover:text-white text-sm flex items-center gap-1 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                Close preview
            </button>

            {{-- A4 paper --}}
            <div class="bg-white text-gray-900 rounded-lg shadow-2xl p-10 text-sm font-sans leading-relaxed">

                {{-- Header --}}
                <div class="flex justify-between items-start mb-8">
                    <div>
                        <template x-if="logoPreview">
                            <img :src="logoPreview" class="max-h-16 max-w-[160px] object-contain mb-2">
                        </template>
                        <div class="font-bold text-lg" x-text="fromName || 'Your Company'"></div>
                        <div class="text-gray-500 text-xs whitespace-pre-line" x-text="fromAddress"></div>
                        <div class="text-gray-500 text-xs" x-text="fromEmail"></div>
                        <div class="text-gray-500 text-xs" x-text="fromPhone"></div>
                        <template x-if="fromVat">
                            <div class="text-gray-500 text-xs" x-text="'VAT: ' + fromVat"></div>
                        </template>
                    </div>
                    <div class="text-right">
                        <div class="text-3xl font-bold text-gray-800 tracking-tight mb-2">INVOICE</div>
                        <div class="text-gray-700 font-semibold" x-text="invoiceNumber"></div>
                        <div class="text-gray-500 text-xs mt-1">
                            <span>Date: </span><span x-text="invoiceDate || '—'"></span>
                        </div>
                        <template x-if="dueDate">
                            <div class="text-gray-500 text-xs">
                                <span>Due: </span><span x-text="dueDate"></span>
                            </div>
                        </template>
                        <div class="mt-2 inline-block bg-gray-100 text-gray-600 text-xs px-2 py-0.5 rounded font-mono" x-text="currency"></div>
                    </div>
                </div>

                {{-- Divider --}}
                <div class="border-t border-gray-200 mb-6"></div>

                {{-- Bill To --}}
                <div class="mb-6">
                    <div class="text-xs uppercase tracking-widest text-gray-400 mb-1">Bill To</div>
                    <div class="font-semibold text-gray-800" x-text="toName || '—'"></div>
                    <div class="text-gray-500 text-xs whitespace-pre-line" x-text="toAddress"></div>
                    <div class="text-gray-500 text-xs" x-text="toEmail"></div>
                    <div class="text-gray-500 text-xs" x-text="toPhone"></div>
                    <template x-if="toVat">
                        <div class="text-gray-500 text-xs" x-text="'VAT: ' + toVat"></div>
                    </template>
                </div>

                {{-- Line items --}}
                <table class="w-full mb-4 text-xs">
                    <thead>
                        <tr class="border-b-2 border-gray-200 text-gray-500 uppercase tracking-wider">
                            <th class="text-left py-2 font-medium">Description</th>
                            <th class="text-right py-2 font-medium w-16">Qty</th>
                            <th class="text-right py-2 font-medium w-24">Unit Price</th>
                            <th class="text-right py-2 font-medium w-24">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(item, i) in items" :key="i">
                            <tr class="border-b border-gray-100">
                                <td class="py-2 text-gray-800" x-text="item.description || '—'"></td>
                                <td class="py-2 text-right text-gray-600 font-mono" x-text="item.quantity"></td>
                                <td class="py-2 text-right text-gray-600 font-mono" x-text="currencySymbol() + fmt(item.unit_price)"></td>
                                <td class="py-2 text-right text-gray-800 font-mono font-medium" x-text="currencySymbol() + fmt(item.quantity * item.unit_price)"></td>
                            </tr>
                        </template>
                    </tbody>
                </table>

                {{-- Totals --}}
                <div class="flex justify-end mb-6">
                    <div class="w-56 space-y-1 text-xs">
                        <div class="flex justify-between text-gray-500">
                            <span>Subtotal</span>
                            <span class="font-mono" x-text="currencySymbol() + fmt(subtotal)"></span>
                        </div>
                        <template x-if="taxRate > 0">
                            <div class="flex justify-between text-gray-500">
                                <span x-text="'Tax (' + taxRate + '%)'"></span>
                                <span class="font-mono" x-text="currencySymbol() + fmt(taxAmount)"></span>
                            </div>
                        </template>
                        <template x-if="discount > 0">
                            <div class="flex justify-between text-gray-500">
                                <span>Discount</span>
                                <span class="font-mono" x-text="'- ' + currencySymbol() + fmt(discount)"></span>
                            </div>
                        </template>
                        <div class="flex justify-between font-bold text-gray-900 border-t border-gray-300 pt-1 text-sm">
                            <span>Total</span>
                            <span class="font-mono" x-text="currencySymbol() + fmt(total)"></span>
                        </div>
                    </div>
                </div>

                {{-- Notes --}}
                <template x-if="notes">
                    <div class="bg-gray-50 rounded p-3 mb-4 text-xs text-gray-600 whitespace-pre-line">
                        <div class="font-semibold text-gray-700 mb-1">Notes</div>
                        <div x-text="notes"></div>
                    </div>
                </template>

                {{-- Footer --}}
                <template x-if="footer">
                    <div class="border-t border-gray-200 pt-3 text-center text-xs text-gray-400" x-text="footer"></div>
                </template>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Left column --}}
        <div class="lg:col-span-2 space-y-6">

            {{-- Invoice metadata --}}
            <div class="bg-bg-card border border-white/[0.07] rounded-2xl p-6">
                <h2 class="font-heading text-base font-semibold text-white mb-5">Invoice Details</h2>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label>Invoice Number</label>
                        <input type="text" name="invoice_number" x-model="invoiceNumber" value="{{ old('invoice_number', $nextNumber) }}" required>
                    </div>
                    <div>
                        <label>Currency</label>
                        <select name="currency" x-model="currency">
                            @foreach(['GBP','USD','EUR','AUD','CAD','ZAR'] as $cur)
                                <option value="{{ $cur }}" {{ old('currency', $p?->currency ?? $user->default_currency ?? 'GBP') === $cur ? 'selected' : '' }}>{{ $cur }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label>Invoice Date</label>
                        <input type="date" name="invoice_date" x-model="invoiceDate" value="{{ old('invoice_date', date('Y-m-d')) }}" required>
                    </div>
                    <div>
                        <label>Due Date</label>
                        <input type="date" name="due_date" x-model="dueDate" value="{{ old('due_date') }}">
                    </div>
                </div>
            </div>

            {{-- From / To --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-bg-card border border-white/[0.07] rounded-2xl p-6">
                    <h2 class="font-heading text-base font-semibold text-white mb-5">From (You)</h2>
                    <div class="space-y-3">
                        <div><label>Name / Company</label><input type="text" name="from_name" x-model="fromName" value="{{ old('from_name', $user->company_name ?? $user->name) }}" required></div>
                        <div><label>Email</label><input type="email" name="from_email" x-model="fromEmail" value="{{ old('from_email', $user->company_email ?? $user->email) }}"></div>
                        <div><label>Address</label><textarea name="from_address" x-model="fromAddress" rows="3">{{ old('from_address', $user->company_address) }}</textarea></div>
                        <div><label>Phone</label><input type="text" name="from_phone" x-model="fromPhone" value="{{ old('from_phone', $user->company_phone) }}"></div>
                        <div><label>VAT / Tax Number</label><input type="text" name="from_vat" x-model="fromVat" value="{{ old('from_vat', $user->company_vat) }}"></div>
                    </div>
                </div>
                <div class="bg-bg-card border border-white/[0.07] rounded-2xl p-6">
                    <h2 class="font-heading text-base font-semibold text-white mb-5">Bill To (Client)</h2>
                    <div class="space-y-3">
                        <div><label>Name / Company</label><input type="text" name="to_name" x-model="toName" value="{{ old('to_name', $p?->to_name) }}" required></div>
                        <div><label>Email</label><input type="email" name="to_email" x-model="toEmail" value="{{ old('to_email', $p?->to_email) }}"></div>
                        <div><label>Address</label><textarea name="to_address" x-model="toAddress" rows="3">{{ old('to_address', $p?->to_address) }}</textarea></div>
                        <div><label>Phone</label><input type="text" name="to_phone" x-model="toPhone" value="{{ old('to_phone', $p?->to_phone) }}"></div>
                        <div><label>VAT / Tax Number</label><input type="text" name="to_vat" x-model="toVat" value="{{ old('to_vat', $p?->to_vat) }}"></div>
                    </div>
                </div>
            </div>

            {{-- Line items --}}
            <div class="bg-bg-card border border-white/[0.07] rounded-2xl p-6">
                <h2 class="font-heading text-base font-semibold text-white mb-5">Line Items</h2>

                <div class="hidden md:grid grid-cols-12 gap-3 mb-2 px-1">
                    <div class="col-span-6"><span class="text-slate-500 text-xs uppercase tracking-wider">Description</span></div>
                    <div class="col-span-2 text-right"><span class="text-slate-500 text-xs uppercase tracking-wider">Qty</span></div>
                    <div class="col-span-2 text-right"><span class="text-slate-500 text-xs uppercase tracking-wider">Unit Price</span></div>
                    <div class="col-span-2 text-right"><span class="text-slate-500 text-xs uppercase tracking-wider">Total</span></div>
                </div>

                <div class="space-y-3" id="line-items">
                    <template x-for="(item, index) in items" :key="index">
                        <div class="grid grid-cols-12 gap-3 items-start group">
                            <div class="col-span-12 md:col-span-6">
                                <input type="text" :name="`items[${index}][description]`"
                                       x-model="item.description" placeholder="Description of work or product" required>
                            </div>
                            <div class="col-span-4 md:col-span-2">
                                <input type="number" :name="`items[${index}][quantity]`"
                                       x-model.number="item.quantity" @input="recalculate()"
                                       step="0.01" min="0.01" placeholder="1" required class="text-right">
                            </div>
                            <div class="col-span-4 md:col-span-2">
                                <input type="number" :name="`items[${index}][unit_price]`"
                                       x-model.number="item.unit_price" @input="recalculate()"
                                       step="0.01" min="0" placeholder="0.00" required class="text-right">
                            </div>
                            <div class="col-span-3 md:col-span-1 flex items-center justify-end">
                                <span class="font-mono text-white text-sm" x-text="lineTotal(item)"></span>
                            </div>
                            <div class="col-span-1 flex items-center justify-end">
                                <button type="button" @click="removeItem(index)"
                                        x-show="items.length > 1"
                                        class="w-7 h-7 rounded-lg hover:bg-red-500/10 flex items-center justify-center text-slate-600 hover:text-red-400 transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                                </button>
                            </div>
                        </div>
                    </template>
                </div>

                <button type="button" @click="addItem()"
                        class="mt-4 btn-ghost text-sm px-4 py-2 rounded-lg flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                    Add line item
                </button>
            </div>

            {{-- Notes & Footer --}}
            <div class="bg-bg-card border border-white/[0.07] rounded-2xl p-6">
                <h2 class="font-heading text-base font-semibold text-white mb-5">Notes &amp; Footer</h2>
                <div class="space-y-4">
                    <div>
                        <label>Notes (visible on invoice)</label>
                        <textarea name="notes" x-model="notes" rows="3" placeholder="Payment terms, bank details, or any additional notes...">{{ old('notes', $p?->notes) }}</textarea>
                    </div>
                    <div>
                        <label>Footer Text</label>
                        <input type="text" name="footer" x-model="footer" value="{{ old('footer', $p?->footer) }}" placeholder="e.g. Thank you for your business!">
                    </div>
                </div>
            </div>

        </div>

        {{-- Right column — totals + logo --}}
        <div class="space-y-6">

            {{-- Totals --}}
            <div class="bg-bg-card border border-white/[0.07] rounded-2xl p-6 sticky top-24">
                <h2 class="font-heading text-base font-semibold text-white mb-5">Summary</h2>

                <div class="space-y-2 text-sm mb-5">
                    <div class="flex justify-between text-slate-400">
                        <span>Subtotal</span>
                        <span class="font-mono text-white" x-text="fmt(subtotal)"></span>
                    </div>
                    <div class="flex justify-between items-center gap-3">
                        <span class="text-slate-400">Tax (%)</span>
                        <input type="number" name="tax_rate" x-model.number="taxRate"
                               @input="recalculate()" step="0.01" min="0" max="100"
                               placeholder="0" class="!w-20 text-right">
                    </div>
                    <div class="flex justify-between text-slate-400">
                        <span>Tax amount</span>
                        <span class="font-mono text-white" x-text="fmt(taxAmount)"></span>
                    </div>
                    <div class="flex justify-between items-center gap-3">
                        <span class="text-slate-400">Discount</span>
                        <input type="number" name="discount" x-model.number="discount"
                               @input="recalculate()" step="0.01" min="0"
                               placeholder="0.00" class="!w-28 text-right">
                    </div>
                    <div class="border-t border-white/[0.07] pt-3 flex justify-between font-semibold">
                        <span class="text-white">Total</span>
                        <span class="font-mono text-accent text-lg" x-text="fmt(total)"></span>
                    </div>
                </div>

                {{-- Hidden computed fields --}}
                <input type="hidden" name="subtotal" :value="subtotal">
                <input type="hidden" name="tax_amount" :value="taxAmount">
                <input type="hidden" name="total" :value="total">

                <button type="button" @click="showPreview = true"
                        class="btn-ghost w-full py-3 rounded-xl text-sm font-semibold flex items-center justify-center gap-2 mb-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                    Preview Invoice
                </button>

                <button type="submit" class="btn-primary w-full py-3 rounded-xl text-sm font-semibold flex items-center justify-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                    Generate Invoice PDF
                </button>
            </div>

            {{-- Logo upload --}}
            <div class="bg-bg-card border border-white/[0.07] rounded-2xl p-6">
                <h2 class="font-heading text-base font-semibold text-white mb-4">Logo</h2>

                <div class="border-2 border-dashed border-white/10 rounded-xl p-6 text-center hover:border-accent/30 transition-colors cursor-pointer"
                     @click="$refs.logoInput.click()"
                     @dragover.prevent
                     @drop.prevent="handleLogoDrop($event)">
                    <div x-show="!logoPreview">
                        <svg class="mx-auto mb-2 text-slate-600" xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect width="18" height="18" x="3" y="3" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>
                        <p class="text-slate-400 text-sm">Click or drag &amp; drop</p>
                        <p class="text-slate-600 text-xs mt-1">PNG, JPG up to 2MB</p>
                    </div>
                    <div x-show="logoPreview" x-cloak>
                        <img :src="logoPreview" class="max-h-24 mx-auto rounded object-contain mb-2">
                        <p class="text-slate-500 text-xs">Click to replace</p>
                    </div>
                </div>
                <input type="file" name="logo" accept="image/*" x-ref="logoInput"
                       class="hidden" @change="handleLogoFile($event)">
            </div>

        </div>
    </div>
</form>

@push('head')
<script>
function invoiceBuilder() {
    const oldItems = @json($oldItems);
    return {
        items: oldItems.map(i => ({
            description: i.description || '',
            quantity: parseFloat(i.quantity) || 1,
            unit_price: parseFloat(i.unit_price) || 0
        })),
        taxRate: parseFloat('{{ $oldTaxRate }}') || 0,
        discount: parseFloat('{{ $oldDiscount }}') || 0,
        subtotal: 0,
        taxAmount: 0,
        total: 0,

        invoiceNumber: '{{ old('invoice_number', $nextNumber) }}',
        currency: '{{ old('currency', $p?->currency ?? $user->default_currency ?? 'GBP') }}',
        invoiceDate: '{{ old('invoice_date', date('Y-m-d')) }}',
        dueDate: '{{ old('due_date') }}',

        fromName: @json(old('from_name', $user->company_name ?? $user->name)),
        fromEmail: @json(old('from_email', $user->company_email ?? $user->email)),
        fromAddress: @json(old('from_address', $user->company_address ?? '')),
        fromPhone: @json(old('from_phone', $user->company_phone ?? '')),
        fromVat: @json(old('from_vat', $user->company_vat ?? '')),

        toName: @json(old('to_name', $p?->to_name ?? '')),
        toEmail: @json(old('to_email', $p?->to_email ?? '')),
        toAddress: @json(old('to_address', $p?->to_address ?? '')),
        toPhone: @json(old('to_phone', $p?->to_phone ?? '')),
        toVat: @json(old('to_vat', $p?->to_vat ?? '')),

        notes: @json(old('notes', $p?->notes ?? '')),
        footer: @json(old('footer', $p?->footer ?? '')),

        logoPreview: @json($logoUrl ?? null),
        showPreview: false,

        init() { this.recalculate(); },

        addItem() {
            this.items.push({ description: '', quantity: 1, unit_price: 0 });
        },
        removeItem(i) {
            this.items.splice(i, 1);
            this.recalculate();
        },
        lineTotal(item) {
            return this.fmt(item.quantity * item.unit_price);
        },
        recalculate() {
            this.subtotal = this.items.reduce((s, i) => s + (i.quantity * i.unit_price), 0);
            this.taxAmount = this.subtotal * (this.taxRate / 100);
            this.total = this.subtotal + this.taxAmount - this.discount;
        },
        fmt(v) {
            return isNaN(v) ? '0.00' : parseFloat(v).toFixed(2);
        },
        currencySymbol() {
            return { GBP: '£', USD: '$', EUR: '€', AUD: 'A$', CAD: 'C$', ZAR: 'R' }[this.currency] || this.currency + ' ';
        },
        handleLogoFile(e) {
            const file = e.target.files[0];
            if (file) this.logoPreview = URL.createObjectURL(file);
        },
        handleLogoDrop(e) {
            const file = e.dataTransfer.files[0];
            if (file && file.type.startsWith('image/')) {
                const dt = new DataTransfer();
                dt.items.add(file);
                this.$refs.logoInput.files = dt.files;
                this.logoPreview = URL.createObjectURL(file);
            }
        }
    };
}
</script>
@endpush
@endsection
