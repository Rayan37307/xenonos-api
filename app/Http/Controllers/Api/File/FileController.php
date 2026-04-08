<?php

namespace App\Http\Controllers\Api\File;

use App\Http\Controllers\Controller;
use App\Http\Resources\FileResource;
use App\Models\File;
use App\Models\Project;
use App\Models\Task;
use App\Services\FileService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class FileController extends Controller
{
    public function __construct(private FileService $fileService)
    {
    }

    public function index(Request $request)
    {
        $query = File::query();

        if ($request->has('fileable_type') && $request->has('fileable_id')) {
            $query->where('fileable_type', $request->fileable_type)
                  ->where('fileable_id', $request->fileable_id);
        }

        if ($request->has('uploaded_by')) {
            $query->where('uploaded_by', $request->uploaded_by);
        }

        if (!$request->user()->isAdmin()) {
            $query->where('uploaded_by', $request->user()->id);
        }

        $files = $query->latest()->paginate($request->get('limit', 20));

        return response()->json([
            'files' => FileResource::collection($files),
        ]);
    }

    public function store(Request $request)
    {
        if (!$request->user()->isAdmin()) {
            throw ValidationException::withMessages([
                'message' => 'Only administrators can upload files',
            ]);
        }

        $validated = $request->validate([
            'file' => 'nullable|file|max:102400',
            'external_link' => 'nullable|url|max:500',
            'name' => 'nullable|string|max:255',
            'fileable_type' => 'nullable|string|in:project,task',
            'fileable_id' => 'nullable|integer|exists:projects,id',
        ]);

        if (!$request->hasFile('file') && empty($validated['external_link'])) {
            throw ValidationException::withMessages([
                'file' => 'Either a file or external_link must be provided',
            ]);
        }

        $file = new File();
        $file->uploaded_by = $request->user()->id;

        if (!empty($validated['external_link'])) {
            $file->is_external = true;
            $file->external_link = $validated['external_link'];
            $file->name = $validated['name'] ?? basename($validated['external_link']) ?? 'External File';
            $file->original_name = $file->name;
            $file->mime_type = 'application/octet-stream';
            $file->size = 0;
            $file->disk = 'external';
        }

        if ($request->hasFile('file')) {
            $uploadedFile = $request->file('file');
            
            $originalName = $uploadedFile->getClientOriginalName();
            $mimeType = $uploadedFile->getMimeType();
            $size = $uploadedFile->getSize();

            $filename = uniqid() . '_' . time() . '_' . $uploadedFile->hashName();
            $path = $uploadedFile->storeAs('uploads', $filename, 'public');

            $file->name = $filename;
            $file->original_name = $originalName;
            $file->path = $path;
            $file->mime_type = $mimeType;
            $file->size = $size;
            $file->is_external = false;
            $file->disk = 'public';
        }

        if (!empty($validated['name'])) {
            $file->name = $validated['name'];
            $file->original_name = $validated['name'];
        }

        if (!empty($validated['fileable_type']) && !empty($validated['fileable_id'])) {
            if ($validated['fileable_type'] === 'project') {
                $fileable = Project::find($validated['fileable_id']);
            } elseif ($validated['fileable_type'] === 'task') {
                $fileable = Task::find($validated['fileable_id']);
            }

            if ($fileable) {
                $fileable->files()->save($file);
            }
        } else {
            $file->save();
        }

        return response()->json([
            'message' => $file->is_external ? 'External link added successfully' : 'File uploaded successfully',
            'file' => $file,
        ], 201);
    }

    public function show(Request $request, string $id)
    {
        $file = File::with('uploader')->findOrFail($id);

        if (!$request->user()->isAdmin()) {
            if ($file->uploaded_by !== $request->user()->id && !$this->fileService->canAccess($file)) {
                throw ValidationException::withMessages([
                    'message' => 'Unauthorized access to file',
                ]);
            }
        }

        $this->fileService->logAccess($file, 'view', $request->user()->id, $request->ip(), $request->userAgent());

        return response()->json([
            'file' => new FileResource($file),
        ]);
    }

    public function download(Request $request, string $id)
    {
        $file = File::findOrFail($id);

        if (!$request->user()->isAdmin()) {
            if ($file->uploaded_by !== $request->user()->id && !$this->fileService->canDownload($file)) {
                throw ValidationException::withMessages([
                    'message' => 'Unauthorized access to file',
                ]);
            }
        }

        $this->fileService->logAccess($file, 'download', $request->user()->id, $request->ip(), $request->userAgent());

        if ($file->is_external) {
            return response()->json([
                'url' => $file->external_link,
                'is_external' => true,
            ]);
        }

        if (!Storage::disk('public')->exists($file->path)) {
            throw ValidationException::withMessages([
                'message' => 'File not found on server',
            ]);
        }

        return Storage::disk('public')->download($file->path, $file->original_name);
    }

    public function update(Request $request, string $id)
    {
        if (!$request->user()->isAdmin()) {
            throw ValidationException::withMessages([
                'message' => 'Only administrators can update files',
            ]);
        }

        $file = File::findOrFail($id);

        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'external_link' => 'nullable|url|max:500',
        ]);

        if (!empty($validated['name'])) {
            $file->name = $validated['name'];
            $file->original_name = $validated['name'];
        }

        if (!empty($validated['external_link'])) {
            $file->external_link = $validated['external_link'];
            $file->is_external = true;
        }

        $file->save();

        return response()->json([
            'message' => 'File updated successfully',
            'file' => $file,
        ]);
    }

    public function destroy(Request $request, string $id)
    {
        if (!$request->user()->isAdmin()) {
            throw ValidationException::withMessages([
                'message' => 'Only administrators can delete files',
            ]);
        }

        $file = File::findOrFail($id);

        if (!$file->is_external && $file->path) {
            if (Storage::disk('public')->exists($file->path)) {
                Storage::disk('public')->delete($file->path);
            }
        }

        $file->delete();

        return response()->json([
            'message' => 'File deleted successfully',
        ]);
    }

    public function share(Request $request, string $id)
    {
        $file = File::findOrFail($id);

        if ($file->uploaded_by !== $request->user()->id) {
            throw ValidationException::withMessages(['message' => 'Only file owner can share']);
        }

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'permission' => 'nullable|string|in:view,download,edit',
            'expires_at' => 'nullable|date',
        ]);

        $share = $this->fileService->shareFile(
            $file,
            $validated['user_id'],
            $validated['permission'] ?? 'view',
            $validated['expires_at'] ? \Carbon\Carbon::parse($validated['expires_at']) : null
        );

        $this->fileService->logAccess($file, 'share', $request->user()->id, $request->ip(), $request->userAgent());

        return response()->json(['message' => 'File shared successfully', 'share' => $share]);
    }

    public function unshare(Request $request, string $id, string $userId)
    {
        $file = File::findOrFail($id);

        if ($file->uploaded_by !== $request->user()->id) {
            throw ValidationException::withMessages(['message' => 'Only file owner can unshare']);
        }

        $this->fileService->unshareFile($file, (int) $userId);

        return response()->json(['message' => 'File unshared successfully']);
    }

    public function sharedWithMe(Request $request)
    {
        $files = $this->fileService->getSharedWithMe();

        return response()->json(['files' => $files]);
    }

    public function sharedByMe(Request $request)
    {
        $files = $this->fileService->getSharedByMe();

        return response()->json(['files' => $files]);
    }

    public function activity(Request $request, string $id)
    {
        $file = File::findOrFail($id);

        if ($file->uploaded_by !== $request->user()->id && !$request->user()->isAdmin()) {
            throw ValidationException::withMessages(['message' => 'Unauthorized']);
        }

        $activity = $this->fileService->getFileActivity($file);

        return response()->json(['activity' => $activity]);
    }
}
