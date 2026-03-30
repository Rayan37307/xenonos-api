<?php

namespace App\Http\Controllers\Api\File;

use App\Http\Controllers\Controller;
use App\Http\Resources\FileResource;
use App\Models\File;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class FileController extends Controller
{
    /**
     * Display a listing of files.
     */
    public function index(Request $request)
    {
        $query = File::query();

        // Filter by fileable type and id
        if ($request->has('fileable_type') && $request->has('fileable_id')) {
            $query->where('fileable_type', $request->fileable_type)
                  ->where('fileable_id', $request->fileable_id);
        }

        // Filter by uploaded_by
        if ($request->has('uploaded_by')) {
            $query->where('uploaded_by', $request->uploaded_by);
        }

        // Only admin can see all files, others see only their files
        if (!$request->user()->isAdmin()) {
            $query->where('uploaded_by', $request->user()->id);
        }

        $files = $query->latest()->paginate($request->get('limit', 20));

        return response()->json([
            'files' => FileResource::collection($files),
        ]);
    }

    /**
     * Store a newly created file.
     * Admin only - can upload binary file OR external link (both optional)
     */
    public function store(Request $request)
    {
        // Only admins can upload files
        if (!$request->user()->isAdmin()) {
            throw ValidationException::withMessages([
                'message' => 'Only administrators can upload files',
            ]);
        }

        $validated = $request->validate([
            'file' => 'nullable|file|max:102400', // 100MB max for direct upload
            'external_link' => 'nullable|url|max:500',
            'name' => 'nullable|string|max:255',
            'fileable_type' => 'nullable|string|in:project,task',
            'fileable_id' => 'nullable|integer|exists:projects,id',
        ]);

        // Require either file or external_link
        if (!$request->hasFile('file') && empty($validated['external_link'])) {
            throw ValidationException::withMessages([
                'file' => 'Either a file or external_link must be provided',
            ]);
        }

        $file = new File();
        $file->uploaded_by = $request->user()->id;

        // Handle external link (e.g., Google Drive)
        if (!empty($validated['external_link'])) {
            $file->is_external = true;
            $file->external_link = $validated['external_link'];
            $file->name = $validated['name'] ?? basename($validated['external_link']) ?? 'External File';
            $file->original_name = $file->name;
            $file->mime_type = 'application/octet-stream';
            $file->size = 0;
            $file->disk = 'external';
        }

        // Handle binary file upload
        if ($request->hasFile('file')) {
            $uploadedFile = $request->file('file');
            
            // For files > 10MB, suggest using external link
            if ($uploadedFile->getSize() > 10 * 1024 * 1024) {
                // Still allow upload but could add warning in response
            }

            $originalName = $uploadedFile->getClientOriginalName();
            $mimeType = $uploadedFile->getMimeType();
            $size = $uploadedFile->getSize();

            // Generate unique filename
            $filename = uniqid() . '_' . time() . '_' . $uploadedFile->hashName();

            // Store file
            $path = $uploadedFile->storeAs('uploads', $filename, 'public');

            $file->name = $filename;
            $file->original_name = $originalName;
            $file->path = $path;
            $file->mime_type = $mimeType;
            $file->size = $size;
            $file->is_external = false;
            $file->disk = 'public';
        }

        // Use provided name if available
        if (!empty($validated['name'])) {
            $file->name = $validated['name'];
            $file->original_name = $validated['name'];
        }

        // Attach to model if provided
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

        $statusCode = $file->is_external ? 201 : 201;

        return response()->json([
            'message' => $file->is_external ? 'External link added successfully' : 'File uploaded successfully',
            'file' => $file,
        ], $statusCode);
    }

    /**
     * Display the specified file.
     */
    public function show(Request $request, string $id)
    {
        $file = File::with('uploader')->findOrFail($id);

        // Check authorization
        if (!$request->user()->isAdmin()) {
            if ($file->uploaded_by !== $request->user()->id) {
                throw ValidationException::withMessages([
                    'message' => 'Unauthorized access to file',
                ]);
            }
        }

        return response()->json([
            'file' => new FileResource($file),
        ]);
    }

    /**
     * Download/access the specified file.
     */
    public function download(Request $request, string $id)
    {
        $file = File::findOrFail($id);

        // Check authorization
        if (!$request->user()->isAdmin()) {
            if ($file->uploaded_by !== $request->user()->id) {
                throw ValidationException::withMessages([
                    'message' => 'Unauthorized access to file',
                ]);
            }
        }

        // Redirect to external link if it's an external file
        if ($file->is_external) {
            return response()->json([
                'url' => $file->external_link,
                'is_external' => true,
            ]);
        }

        // Download from storage
        if (!Storage::disk('public')->exists($file->path)) {
            throw ValidationException::withMessages([
                'message' => 'File not found on server',
            ]);
        }

        return Storage::disk('public')->download($file->path, $file->original_name);
    }

    /**
     * Update the specified file.
     */
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

    /**
     * Remove the specified file.
     */
    public function destroy(Request $request, string $id)
    {
        if (!$request->user()->isAdmin()) {
            throw ValidationException::withMessages([
                'message' => 'Only administrators can delete files',
            ]);
        }

        $file = File::findOrFail($id);

        // Delete from storage if it's not an external link
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
}
