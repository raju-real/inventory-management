<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ImageService
{
    /** Handles the optional `image` / `remove_image` inputs and returns data ready to save. */
    public function resolve(array $data, ?Model $model, string $directory): array
    {
        $current = $model?->image;
        $remove = ! empty($data['remove_image']);
        unset($data['remove_image']);

        if (($data['image'] ?? null) instanceof UploadedFile) {
            $this->delete($current);
            $data['image'] = $data['image']->store($directory, 'public');
        } elseif ($remove) {
            $this->delete($current);
            $data['image'] = null;
        } else {
            unset($data['image']);
        }

        return $data;
    }

    public function store(UploadedFile $file, string $directory): string
    {
        return $file->store($directory, 'public');
    }

    public function delete(?string $path): void
    {
        if ($path) {
            Storage::disk('public')->delete($path);
        }
    }
}
