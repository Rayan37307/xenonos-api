<?php

namespace App\Http\Controllers\Api\Invoice;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Http\Resources\InvoiceResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class InvoiceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Invoice::with(['project', 'client', 'issuedBy', 'updatedBy']);

        if ($request->filled('project_id')) {
            $query->where('project_id', $request->project_id);
        }

        if ($request->filled('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        $invoices = $query->latest()->get();

        return response()->json([
            'invoices' => InvoiceResource::collection($invoices),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'project_id' => ['required', 'exists:projects,id'],
            'client_id' => ['required', 'exists:clients,id'],
            'date_issued' => ['required', 'date'],
            'due_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0'],
            'status' => ['sometimes', 'in:pending,paid,overdue'],
            'file_path' => ['nullable', 'string'],
        ]);

        $validated['issued_by'] = $request->user()->id;

        return response()->json([
            'message' => 'Invoice created successfully',
            'invoice' => new InvoiceResource(Invoice::create($validated)),
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json([
            'invoice' => new InvoiceResource(Invoice::with(['project', 'client', 'issuedBy', 'updatedBy'])->findOrFail($id)),
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $invoice = Invoice::findOrFail($id);

        $validated = $request->validate([
            'date_issued' => ['sometimes', 'date'],
            'due_date' => ['sometimes', 'date'],
            'amount' => ['sometimes', 'numeric', 'min:0'],
            'status' => ['sometimes', 'in:pending,paid,overdue'],
            'file_path' => ['nullable', 'string'],
        ]);

        $validated['updated_by'] = $request->user()->id;

        $invoice->update($validated);

        return response()->json([
            'message' => 'Invoice updated successfully',
            'invoice' => new InvoiceResource($invoice->fresh()),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        Invoice::destroy($id);

        return response()->json([
            'message' => 'Invoice deleted successfully',
        ]);
    }
}
