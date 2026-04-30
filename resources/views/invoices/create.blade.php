@extends('layouts.invoice')
@section('title', 'New Invoice')

@section('content')
<div class="mb-8">
    <a href="{{ route('invoices.index') }}" class="text-slate-400 hover:text-white text-sm flex items-center gap-2 w-fit transition-colors mb-4">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
        Back to invoices
    </a>
    <h1 class="font-heading text-2xl font-semibold text-white">New Invoice</h1>
</div>

@if($errors->any())
    <div class="flash-error rounded-xl px-5 py-4 mb-6 text-sm space-y-1">
        @foreach($errors->all() as $error)
            <div>{{ $error }}</div>
        @endforeach
    </div>
@endif

<form method="POST" action="{{ route('invoices.store') }}" enctype="multipart/form-data"
      x-data="invoiceBuilder()" @submit="recalculate()">
    @csrf

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Left column --}}
        <div class="lg:col-span-2 space-y-6">

            {{-- Invoice metadata --}}
            <div class="bg-bg-card border border-white/[0.07] rounded-2xl p-6">
                <h2 class="font-heading text-base font-semibold text-white mb-5">Invoice Details</h2>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label>Invoice Number</label>
                        <input type="text" name="invoice_number" value="{{ old('invoice_number', $nextNumber) }}" required>
                    </div>
                    <div>
                        <label>Currency</label>
                        <select name="currency">
                            @foreach(['GBP','USD','EUR','AUD','CAD','ZAR'] as $cur)
                                <option value="{{ $cur }}" {{ old('currency', $user->default_currency ?? 'GBP') === $cur ? 'selected' : '' }}>{{ $cur }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label>Invoice Date</label>
                        <input type="date" name="invoice_date" value="{{ old('invoice_date', date('Y-m-d')) }}" required>
                    </div>
                    <div>
                        <label>Due Date</label>
                        <input type="date" name="due_date" value="{{ old('due_date') }}">
                    </div>
                </div>
            </div>

            {{-- From / To --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-bg-card border border-white/[0.07] rounded-2xl p-6">
                    <h2 class="font-heading text-base font-semibold text-white mb-5">From (You)</h2>
                    <div class="space-y-3">
                        <div><label>Name / Company</label><input type="text" name="from_name" value="{{ old('from_name', $user->company_name ?? $user->name) }}" required></div>
                        <div><label>Email</label><input type="email" name="from_email" value="{{ old('from_email', $user->company_email ?? $user->email) }}"></div>
                        <div><label>Address</label><textarea name="from_address" rows="3">{{ old('from_address', $user->company_address) }}</textarea></div>
                        <div><label>Phone</label><input type="text" name="from_phone" value="{{ old('from_phone', $user->company_phone) }}"></div>
                        <div><label>VAT / Tax Number</label><input type="text" name="from_vat" value="{{ old('from_vat', $user->company_vat) }}"></div>
                    </div>
                </div>
                <div class="bg-bg-card border border-white/[0.07] rounded-2xl p-6">
                    <h2 class="font-heading text-base font-semibold text-white mb-5">Bill To (Client)</h2>
                    <div class="space-y-3">
                        <div><label>Name / Company</label><input type="text" name="to_name" value="{{ old('to_name') }}" required></div>
                        <div><label>Email</label><input type="email" name="to_email" value="{{ old('to_email') }}"></div>
                        <div><label>Address</label><textarea name="to_address" rows="3">{{ old('to_address') }}</textarea></div>
                        <div><label>Phone</label><input type="text" name="to_phone" value="{{ old('to_phone') }}"></div>
                        <div><label>VAT / Tax Number</label><input type="text" name="to_vat" value="{{ old('to_vat') }}"></div>
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
                        <textarea name="notes" rows="3" placeholder="Payment terms, bank details, or any additional notes...">{{ old('notes') }}</textarea>
                    </div>
                    <div>
                        <label>Footer Text</label>
                        <input type="text" name="footer" value="{{ old('footer') }}" placeholder="e.g. Thank you for your business!">
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
                               placeholder="0" class="!w-20 text-right !py-1.5 text-sm">
                    </div>
                    <div class="flex justify-between text-slate-400">
                        <span>Tax amount</span>
                        <span class="font-mono text-white" x-text="fmt(taxAmount)"></span>
                    </div>
                    <div class="flex justify-between items-center gap-3">
                        <span class="text-slate-400">Discount</span>
                        <input type="number" name="discount" x-model.number="discount"
                               @input="recalculate()" step="0.01" min="0"
                               placeholder="0.00" class="!w-28 text-right !py-1.5 text-sm">
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

                <button type="submit" class="btn-primary w-full py-3 rounded-xl text-sm font-semibold flex items-center justify-center gap-2 mt-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                    Generate Invoice PDF
                </button>
            </div>

            {{-- Logo upload --}}
            <div class="bg-bg-card border border-white/[0.07] rounded-2xl p-6" x-data="logoUpload()">
                <h2 class="font-heading text-base font-semibold text-white mb-4">Logo</h2>

                @if($user->logo_s3_key)
                    <p class="text-slate-400 text-xs mb-3">You have a logo saved. Upload a new one to replace it.</p>
                @endif

                <div class="border-2 border-dashed border-white/10 rounded-xl p-6 text-center hover:border-accent/30 transition-colors cursor-pointer"
                     @click="$refs.logoInput.click()"
                     @dragover.prevent
                     @drop.prevent="handleDrop($event)">
                    <template x-if="!preview">
                        <div>
                            <svg class="mx-auto mb-2 text-slate-600" xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect width="18" height="18" x="3" y="3" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>
                            <p class="text-slate-400 text-sm">Click or drag &amp; drop</p>
                            <p class="text-slate-600 text-xs mt-1">PNG, JPG up to 2MB</p>
                        </div>
                    </template>
                    <template x-if="preview">
                        <img :src="preview" class="max-h-24 mx-auto rounded object-contain">
                    </template>
                </div>
                <input type="file" name="logo" accept="image/*" x-ref="logoInput"
                       class="hidden" @change="handleFile($event)">
            </div>

        </div>
    </div>
</form>

@push('head')
<script>
function invoiceBuilder() {
    const oldItems = @json(old('items', [['description'=>'','quantity'=>1,'unit_price'=>0]]));
    return {
        items: oldItems.map(i => ({
            description: i.description || '',
            quantity: parseFloat(i.quantity) || 1,
            unit_price: parseFloat(i.unit_price) || 0
        })),
        taxRate: parseFloat('{{ old('tax_rate', 0) }}') || 0,
        discount: parseFloat('{{ old('discount', 0) }}') || 0,
        subtotal: 0,
        taxAmount: 0,
        total: 0,

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
        }
    };
}

function logoUpload() {
    return {
        preview: null,
        handleFile(e) {
            const file = e.target.files[0];
            if (file) this.preview = URL.createObjectURL(file);
        },
        handleDrop(e) {
            const file = e.dataTransfer.files[0];
            if (file && file.type.startsWith('image/')) {
                const dt = new DataTransfer();
                dt.items.add(file);
                this.$refs.logoInput.files = dt.files;
                this.preview = URL.createObjectURL(file);
            }
        }
    };
}
</script>
@endpush
@endsection
