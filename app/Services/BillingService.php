<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Transaction;
use App\Models\Subscription;
use App\Models\Client;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class BillingService
{
    public function createInvoice(array $data): Invoice
    {
        return Invoice::create([
            'project_id' => $data['project_id'] ?? null,
            'client_id' => $data['client_id'],
            'issued_by' => Auth::id(),
            'date_issued' => $data['date_issued'] ?? now(),
            'due_date' => $data['due_date'] ?? now()->addDays(30),
            'amount' => $data['amount'],
            'status' => 'pending',
            'file_path' => $data['file_path'] ?? null,
        ]);
    }

    public function generateInvoiceNumber(): string
    {
        $prefix = 'INV-' . date('Y');
        $lastInvoice = Invoice::where('created_at', '>=', now()->startOfYear())
            ->orderBy('id', 'desc')
            ->first();
        
        $sequence = $lastInvoice ? ((int)substr($lastInvoice->id, -4)) + 1 : 1;
        
        return $prefix . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    public function markInvoiceAsPaid(Invoice $invoice, array $transactionData): Transaction
    {
        $invoice->status = 'paid';
        $invoice->save();

        return $this->createTransaction([
            'invoice_id' => $invoice->id,
            'client_id' => $invoice->client_id,
            'amount' => $invoice->amount,
            'payment_method' => $transactionData['payment_method'],
            'status' => 'completed',
            'type' => 'payment',
            'processed_at' => now(),
        ]);
    }

    public function createTransaction(array $data): Transaction
    {
        $fee = $data['fee'] ?? 0;
        $netAmount = $data['amount'] - $fee;

        return Transaction::create([
            'invoice_id' => $data['invoice_id'] ?? null,
            'client_id' => $data['client_id'] ?? null,
            'user_id' => Auth::id(),
            'transaction_id' => $data['transaction_id'] ?? $this->generateTransactionId(),
            'payment_method' => $data['payment_method'],
            'amount' => $data['amount'],
            'fee' => $fee,
            'net_amount' => $netAmount,
            'currency' => $data['currency'] ?? 'USD',
            'status' => $data['status'] ?? 'pending',
            'type' => $data['type'] ?? 'payment',
            'reference' => $data['reference'] ?? null,
            'notes' => $data['notes'] ?? null,
            'processed_at' => $data['processed_at'] ?? null,
        ]);
    }

    public function generateTransactionId(): string
    {
        return 'TXN-' . strtoupper(Str::random(16));
    }

    public function createSubscription(array $data): Subscription
    {
        $cycleDays = match($data['billing_cycle']) {
            'monthly' => 30,
            'quarterly' => 90,
            'yearly' => 365,
            default => 30,
        };

        return Subscription::create([
            'client_id' => $data['client_id'],
            'plan_name' => $data['plan_name'],
            'amount' => $data['amount'],
            'billing_cycle' => $data['billing_cycle'],
            'start_date' => $data['start_date'] ?? now(),
            'end_date' => $data['end_date'] ?? now()->addDays($cycleDays),
            'status' => 'active',
            'payment_method' => $data['payment_method'] ?? null,
            'external_subscription_id' => $data['external_subscription_id'] ?? null,
            'features' => $data['features'] ?? [],
        ]);
    }

    public function renewSubscription(Subscription $subscription): Subscription
    {
        $subscription->renew();
        return $subscription->fresh();
    }

    public function cancelSubscription(Subscription $subscription): Subscription
    {
        $subscription->cancel();
        return $subscription->fresh();
    }

    public function getClientInvoices(Client $client)
    {
        return $client->invoices()
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getClientTransactions(Client $client)
    {
        return $client->transactions()
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getClientSubscriptions(Client $client)
    {
        return $client->subscriptions()
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getActiveSubscription(Client $client): ?Subscription
    {
        return $client->subscriptions()
            ->where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>', now());
            })
            ->first();
    }

    public function getRevenueStats(array $dateRange = []): array
    {
        $query = Transaction::where('status', 'completed');

        if (!empty($dateRange['start'])) {
            $query->where('created_at', '>=', $dateRange['start']);
        }
        if (!empty($dateRange['end'])) {
            $query->where('created_at', '<=', $dateRange['end']);
        }

        $totalRevenue = $query->sum('amount');
        $totalFees = $query->sum('fee');
        $transactionCount = $query->count();

        return [
            'total_revenue' => $totalRevenue,
            'total_fees' => $totalFees,
            'net_revenue' => $totalRevenue - $totalFees,
            'transaction_count' => $transactionCount,
            'average_transaction' => $transactionCount > 0 ? $totalRevenue / $transactionCount : 0,
        ];
    }

    public function getPendingInvoices(): \Illuminate\Database\Eloquent\Collection
    {
        return Invoice::where('status', 'pending')
            ->where('due_date', '<', now())
            ->get();
    }
}
