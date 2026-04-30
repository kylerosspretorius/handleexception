<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class StorageService
{
    public function uploadPdf(int $userId, string $pdfContent): string
    {
        $key = "invoices/{$userId}/" . Str::uuid() . '.pdf';
        Storage::disk('s3')->put($key, $pdfContent, 'private');

        return $key;
    }

    public function uploadLogo(int $userId, UploadedFile $file): string
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $key = "logos/{$userId}/logo.{$extension}";
        Storage::disk('s3')->put($key, file_get_contents($file->getRealPath()), 'private');

        return $key;
    }

    public function getDownloadUrl(string $key, int $minutes = 30): string
    {
        return Storage::disk('s3')->temporaryUrl($key, now()->addMinutes($minutes));
    }

    public function delete(string $key): void
    {
        Storage::disk('s3')->delete($key);
    }
}
