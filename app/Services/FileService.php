<?php

namespace App\Services;

use App\Models\File;
use App\Models\FileAccessLog;
use App\Models\FileShare;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class FileService
{
    public function upload(UploadedFile $file, Model $model, string $collection = 'default'): File
    {
        $originalName = $file->getClientOriginalName();
        $mimeType = $file->getMimeType();
        $size = $file->getSize();
        
        $filename = uniqid() . '_' . time() . '_' . $file->hashName();
        $path = $file->storeAs('uploads/' . $collection, $filename, 'public');

        $fileModel = File::create([
            'name' => $filename,
            'original_name' => $originalName,
            'path' => $path,
            'mime_type' => $mimeType,
            'size' => $size,
            'uploaded_by' => auth()->id(),
        ]);

        $model->files()->save($fileModel);

        return $fileModel;
    }

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

    public function download(File $file)
    {
        return Storage::disk('public')->download($file->path);
    }

    public function delete(File $file): bool
    {
        if (Storage::disk('public')->exists($file->path)) {
            Storage::disk('public')->delete($file->path);
        }

        return $file->delete();
    }

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

    public function getFilesForModel(Model $model)
    {
        return $model->files;
    }

    public function validateFile(UploadedFile $file, array $rules = []): bool
    {
        $defaultRules = [
            'max_size' => 10 * 1024 * 1024,
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

        if ($file->getSize() > $rules['max_size']) {
            throw new \Exception('File size exceeds maximum allowed size.');
        }

        if (!in_array($file->getMimeType(), $rules['allowed_mime_types'])) {
            throw new \Exception('File type is not allowed.');
        }

        return true;
    }

    public function shareFile(File $file, int $userId, string $permission = 'view', ?\Carbon\Carbon $expiresAt = null): FileShare
    {
        return FileShare::updateOrCreate(
            ['file_id' => $file->id, 'user_id' => $userId],
            [
                'shared_by' => Auth::id(),
                'permission' => $permission,
                'expires_at' => $expiresAt,
            ]
        );
    }

    public function unshareFile(File $file, int $userId): bool
    {
        return FileShare::where('file_id', $file->id)
            ->where('user_id', $userId)
            ->delete() > 0;
    }

    public function getSharedWithMe()
    {
        return FileShare::where('user_id', Auth::id())
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->with(['file', 'file.uploader'])
            ->latest()
            ->get();
    }

    public function getSharedByMe()
    {
        return FileShare::where('shared_by', Auth::id())
            ->with(['file', 'file.uploader', 'user'])
            ->latest()
            ->get();
    }

    public function canAccess(File $file, ?int $userId = null): bool
    {
        $userId = $userId ?? Auth::id();
        
        if ($file->uploaded_by === $userId) {
            return true;
        }

        $share = FileShare::where('file_id', $file->id)
            ->where('user_id', $userId)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->first();

        return $share !== null;
    }

    public function canDownload(File $file, ?int $userId = null): bool
    {
        $userId = $userId ?? Auth::id();
        
        if ($file->uploaded_by === $userId) {
            return true;
        }

        $share = FileShare::where('file_id', $file->id)
            ->where('user_id', $userId)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->first();

        return $share && $share->canDownload();
    }

    public function logAccess(File $file, string $action, ?int $userId = null, ?string $ipAddress = null, ?string $userAgent = null): FileAccessLog
    {
        return FileAccessLog::create([
            'file_id' => $file->id,
            'user_id' => $userId ?? Auth::id(),
            'action' => $action,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ]);
    }

    public function getAccessLogs(File $file)
    {
        return $file->accessLogs()
            ->with('user')
            ->latest()
            ->get();
    }

    public function getFileActivity(File $file): array
    {
        return [
            'access_logs' => $this->getAccessLogs($file),
            'shares' => $file->shares()->with('user')->get(),
            'created_at' => $file->created_at,
            'updated_at' => $file->updated_at,
        ];
    }
}
