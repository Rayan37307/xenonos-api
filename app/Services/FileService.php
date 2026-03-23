<?php

namespace App\Services;

use App\Models\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Model;

class FileService
{
    /**
     * Upload a file and attach it to a model.
     */
    public function upload(UploadedFile $file, Model $model, string $collection = 'default'): File
    {
        $originalName = $file->getClientOriginalName();
        $mimeType = $file->getMimeType();
        $size = $file->getSize();
        
        // Generate unique filename
        $filename = uniqid() . '_' . time() . '_' . $file->hashName();
        
        // Store file
        $path = $file->storeAs('uploads/' . $collection, $filename, 'public');

        $fileModel = File::create([
            'name' => $filename,
            'original_name' => $originalName,
            'path' => $path,
            'mime_type' => $mimeType,
            'size' => $size,
            'uploaded_by' => auth()->id(),
        ]);

        // Attach to model
        $model->files()->save($fileModel);

        return $fileModel;
    }

    /**
     * Upload multiple files.
     */
    public function uploadMultiple(array $files, Model $model, string $collection = 'default'): array
    {
        $uploadedFiles = [];

        foreach ($files as $file) {
            if ($file instanceof UploadedFile) {
                $uploadedFiles[] = $this->upload($file, $model, $collection);
            }
        }

        return $uploadedFiles;
    }

    /**
     * Download a file.
     */
    public function download(File $file)
    {
        return Storage::disk('public')->download($file->path);
    }

    /**
     * Delete a file.
     */
    public function delete(File $file): bool
    {
        // Delete from storage
        if (Storage::disk('public')->exists($file->path)) {
            Storage::disk('public')->delete($file->path);
        }

        return $file->delete();
    }

    /**
     * Delete all files attached to a model.
     */
    public function deleteAllForModel(Model $model): int
    {
        $files = $model->files;
        $count = 0;

        foreach ($files as $file) {
            if ($this->delete($file)) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Get files for a model.
     */
    public function getFilesForModel(Model $model): \Illuminate\Database\Eloquent\Collection
    {
        return $model->files;
    }

    /**
     * Validate file upload.
     */
    public function validateFile(UploadedFile $file, array $rules = []): bool
    {
        $defaultRules = [
            'max_size' => 10 * 1024 * 1024, // 10MB
            'allowed_mime_types' => [
                'image/jpeg',
                'image/png',
                'image/gif',
                'image/webp',
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'text/plain',
            ],
        ];

        $rules = array_merge($defaultRules, $rules);

        // Check file size
        if ($file->getSize() > $rules['max_size']) {
            throw new \Exception('File size exceeds maximum allowed size.');
        }

        // Check MIME type
        if (!in_array($file->getMimeType(), $rules['allowed_mime_types'])) {
            throw new \Exception('File type is not allowed.');
        }

        return true;
    }
}
