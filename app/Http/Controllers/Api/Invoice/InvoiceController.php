<?php

namespace App\Http\Controllers\Api\Invoice;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Transaction;
use App\Models\Subscription;
use App\Models\Client;
use App\Http\Resources\InvoiceResource;
use App\Services\BillingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class InvoiceController extends Controller
{
    public function __construct(private BillingService $billingService)
    {
    }

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

    public function clientInvoices(Request $request): JsonResponse
    {
        $client = $request->user()->clientProfile;

        if (!$client) {
            throw ValidationException::withMessages(['message' => 'Client profile not found']);
        }

        $invoices = $this->billingService->getClientInvoices($client);

        return response()->json([
            'invoices' => InvoiceResource::collection($invoices),
        ]);
    }

    public function markAsPaid(Request $request, int $id): JsonResponse
    {
        $invoice = Invoice::findOrFail($id);

        $validated = $request->validate([
            'payment_method' => 'required|string|in:cash,bank_transfer,credit_card,stripe,paypal',
            'transaction_id' => 'nullable|string',
            'reference' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $transaction = $this->billingService->markInvoiceAsPaid($invoice, $validated);

        return response()->json([
            'message' => 'Invoice marked as paid',
            'invoice' => new InvoiceResource($invoice->fresh()),
            'transaction' => $transaction,
        ]);
    }

    public function transactions(Request $request): JsonResponse
    {
        $query = Transaction::with(['client', 'user', 'invoice']);

        if ($request->filled('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $transactions = $query->latest()->get();

        return response()->json(['transactions' => $transactions]);
    }

    public function subscriptions(Request $request): JsonResponse
    {
        $client = $request->user()->clientProfile;

        if (!$client) {
            throw ValidationException::withMessages(['message' => 'Client profile not found']);
        }

        $subscriptions = $this->billingService->getClientSubscriptions($client);

        return response()->json(['subscriptions' => $subscriptions]);
    }

    public function createSubscription(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'plan_name' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'billing_cycle' => 'required|string|in:monthly,quarterly,yearly',
            'start_date' => 'nullable|date',
            'payment_method' => 'nullable|string',
            'external_subscription_id' => 'nullable|string',
            'features' => 'nullable|array',
        ]);

        $subscription = $this->billingService->createSubscription($validated);

        return response()->json([
            'message' => 'Subscription created successfully',
            'subscription' => $subscription,
        ], 201);
    }

    public function updateSubscription(Request $request, int $id): JsonResponse
    {
        $subscription = Subscription::findOrFail($id);

        $validated = $request->validate([
            'plan_name' => 'sometimes|string|max:255',
            'amount' => 'sometimes|numeric|min:0',
            'billing_cycle' => 'sometimes|string|in:monthly,quarterly,yearly',
            'status' => 'sometimes|string|in:active,cancelled,expired,past_due',
        ]);

        $subscription->update($validated);

        return response()->json([
            'message' => 'Subscription updated successfully',
            'subscription' => $subscription->fresh(),
        ]);
    }

    public function cancelSubscription(int $id): JsonResponse
    {
        $subscription = Subscription::findOrFail($id);
        
        $this->billingService->cancelSubscription($subscription);

        return response()->json([
            'message' => 'Subscription cancelled successfully',
            'subscription' => $subscription->fresh(),
        ]);
    }
}
