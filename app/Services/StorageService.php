<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class StorageService
{
    public function uploadPdf(User $user, string $pdfContent): string
    {
        $key = $this->userFolder($user) . '/invoices/' . Str::uuid() . '.pdf';
        $this->disk()->put($key, $pdfContent);

        return $key;
    }

    public function uploadLogo(User $user, UploadedFile $file): string
    {
        $ext = strtolower($file->getClientOriginalExtension());
        $key = $this->userFolder($user) . '/logos/logo.' . $ext;
        $this->disk()->put($key, file_get_contents($file->getRealPath()));

        return $key;
    }

    public function getDownloadUrl(string $key, int $minutes = 30): string
    {
        return $this->disk()->temporaryUrl($key, now()->addMinutes($minutes));
    }

    public function exists(string $key): bool
    {
        return $this->disk()->exists($key);
    }

    public function delete(string $key): void
    {
        $this->disk()->delete($key);
    }

    private function disk(): \Illuminate\Contracts\Filesystem\Filesystem
    {
        return Storage::disk(config('filesystems.default'));
    }

    private function userFolder(User $user): string
    {
        $slug = Str::slug($user->name);

        return $slug ?: 'user-' . $user->id;
    }
}
