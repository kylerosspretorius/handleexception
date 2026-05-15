<?php

namespace App\Services;

use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class PdfService
{
    public function generate(Invoice $invoice): string
    {
        $invoice->load('items');

        $logoBase64 = null;
        if ($invoice->logo_s3_key) {
            $logoBase64 = $this->getLogoAsBase64($invoice->logo_s3_key);
        }

        $html = view('invoices.pdf', [
            'invoice' => $invoice,
            'logoBase64' => $logoBase64,
        ])->render();

        $pdf = Pdf::loadHTML($html)->setPaper('a4', 'portrait');

        return $pdf->output();
    }

    private function getLogoAsBase64(string $s3Key): ?string
    {
        try {
            $content = Storage::disk(config('filesystems.default'))->get($s3Key);
            $extension = strtolower(pathinfo($s3Key, PATHINFO_EXTENSION));
            $mime = match ($extension) {
                'jpg', 'jpeg' => 'image/jpeg',
                'gif' => 'image/gif',
                default => 'image/png',
            };

            return 'data:' . $mime . ';base64,' . base64_encode($content);
        } catch (\Throwable) {
            return null;
        }
    }
}
