<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Services\PdfService;
use App\Services\StorageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    public function __construct(
        private PdfService $pdfService,
        private StorageService $storageService,
    ) {}

    public function index(): View
    {
        $invoices = Auth::user()
            ->invoices()
            ->latest()
            ->paginate(15);

        return view('invoices.index', compact('invoices'));
    }

    public function create(): View
    {
        $user = Auth::user();
        $nextNumber = Invoice::generateNumber($user->id);

        return view('invoices.create', compact('user', 'nextNumber'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'invoice_number'  => 'required|string|max:50',
            'currency'        => 'required|string|size:3',
            'from_name'       => 'required|string|max:255',
            'from_email'      => 'nullable|email|max:255',
            'from_address'    => 'nullable|string|max:500',
            'from_phone'      => 'nullable|string|max:50',
            'from_vat'        => 'nullable|string|max:50',
            'to_name'         => 'required|string|max:255',
            'to_email'        => 'nullable|email|max:255',
            'to_address'      => 'nullable|string|max:500',
            'to_phone'        => 'nullable|string|max:50',
            'to_vat'          => 'nullable|string|max:50',
            'invoice_date'    => 'required|date',
            'due_date'        => 'nullable|date|after_or_equal:invoice_date',
            'tax_rate'        => 'nullable|numeric|min:0|max:100',
            'discount'        => 'nullable|numeric|min:0',
            'notes'           => 'nullable|string|max:2000',
            'footer'          => 'nullable|string|max:500',
            'logo'            => 'nullable|image|max:2048',
            'items'           => 'required|array|min:1',
            'items.*.description' => 'required|string|max:500',
            'items.*.quantity'    => 'required|numeric|min:0.01',
            'items.*.unit_price'  => 'required|numeric|min:0',
        ]);

        $user = Auth::user();

        // Handle logo upload
        $logoKey = $user->logo_s3_key;
        if ($request->hasFile('logo')) {
            $logoKey = $this->storageService->uploadLogo($user->id, $request->file('logo'));
            $user->update(['logo_s3_key' => $logoKey]);
        }

        // Calculate totals
        $subtotal = 0;
        foreach ($validated['items'] as $item) {
            $subtotal += round($item['quantity'] * $item['unit_price'], 2);
        }

        $taxRate    = $validated['tax_rate'] ?? 0;
        $taxAmount  = round($subtotal * ($taxRate / 100), 2);
        $discount   = $validated['discount'] ?? 0;
        $total      = round($subtotal + $taxAmount - $discount, 2);

        $invoice = $user->invoices()->create([
            ...$validated,
            'subtotal'   => $subtotal,
            'tax_amount' => $taxAmount,
            'discount'   => $discount,
            'total'      => $total,
            'logo_s3_key' => $logoKey,
        ]);

        foreach ($validated['items'] as $index => $item) {
            $invoice->items()->create([
                'description' => $item['description'],
                'quantity'    => $item['quantity'],
                'unit_price'  => $item['unit_price'],
                'line_total'  => round($item['quantity'] * $item['unit_price'], 2),
                'sort_order'  => $index,
            ]);
        }

        // Generate and store PDF
        $pdfContent = $this->pdfService->generate($invoice);
        $pdfKey = $this->storageService->uploadPdf($user->id, $pdfContent);
        $invoice->update(['pdf_s3_key' => $pdfKey]);

        // Save company defaults back to user profile
        $user->update([
            'company_name'    => $validated['from_name'],
            'company_email'   => $validated['from_email'],
            'company_address' => $validated['from_address'],
            'company_phone'   => $validated['from_phone'],
            'company_vat'     => $validated['from_vat'],
            'default_currency' => $validated['currency'],
        ]);

        return redirect()->route('invoices.index')
            ->with('success', "Invoice {$invoice->invoice_number} created successfully.");
    }

    public function show(Invoice $invoice): View
    {
        $this->authorizeInvoice($invoice);
        $invoice->load('items');

        return view('invoices.show', compact('invoice'));
    }

    public function download(Invoice $invoice): RedirectResponse
    {
        $this->authorizeInvoice($invoice);

        if (!$invoice->pdf_s3_key) {
            return back()->with('error', 'PDF not available for this invoice.');
        }

        $url = $this->storageService->getDownloadUrl($invoice->pdf_s3_key);

        return redirect($url);
    }

    public function destroy(Invoice $invoice): RedirectResponse
    {
        $this->authorizeInvoice($invoice);

        if ($invoice->pdf_s3_key) {
            $this->storageService->delete($invoice->pdf_s3_key);
        }

        $invoice->delete();

        return back()->with('success', 'Invoice deleted.');
    }

    private function authorizeInvoice(Invoice $invoice): void
    {
        abort_unless($invoice->user_id === Auth::id(), 403);
    }
}
