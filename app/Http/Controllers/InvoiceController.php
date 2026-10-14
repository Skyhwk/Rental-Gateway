<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Subscription;
use App\Services\Billing\InvoiceService;
use App\Services\Billing\SubscriptionBillingService;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', Invoice::class);

        $baseQuery = Invoice::query();

        $recap = [
            'paid' => (clone $baseQuery)->where('status', Invoice::STATUS_PAID)->count(),
            'unpaid' => (clone $baseQuery)->whereNotIn('status', [Invoice::STATUS_PAID, Invoice::STATUS_VOID])->count(),
            'void' => (clone $baseQuery)->where('status', Invoice::STATUS_VOID)->count(),
        ];

        $query = Invoice::with(['subscription.client', 'subscription.application']);

        if ($state = $request->get('payment_state')) {
            if ($state === 'paid') {
                $query->where('status', Invoice::STATUS_PAID);
            } elseif ($state === 'unpaid') {
                $query->whereNotIn('status', [Invoice::STATUS_PAID, Invoice::STATUS_VOID]);
            }
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($request->boolean('overdue_only')) {
            $query->where('status', Invoice::STATUS_OVERDUE);
        }

        $invoices = $query->orderByDesc('due_date')->paginate(20)->withQueryString();

        return view('billing.invoices.index', [
            'invoices' => $invoices,
            'statuses' => Invoice::statuses(),
            'recap' => $recap,
        ]);
    }

    public function show(Invoice $invoice)
    {
        $this->authorize('view', $invoice);

        $invoice->load(['subscription.client', 'subscription.application', 'items']);

        return view('billing.invoices.show', compact('invoice'));
    }

    public function pdf(Invoice $invoice)
    {
        $this->authorize('view', $invoice);

        $invoice->load(['subscription.client', 'subscription.application', 'items']);

        return view('billing.invoices.pdf', compact('invoice'));
    }

    public function download(Invoice $invoice)
    {
        $this->authorize('view', $invoice);

        $invoice->load(['subscription.client', 'subscription.application', 'items']);

        $html = view('billing.invoices.pdf', [
            'invoice' => $invoice,
            'forDownload' => true,
        ])->render();

        $filename = $invoice->invoice_number.'.html';

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    public function store(Request $request, Subscription $subscription, InvoiceService $invoiceService, SubscriptionBillingService $billing)
    {
        $this->authorize('create', Invoice::class);

        $subscription->load(['plan', 'application']);
        $scheduledDue = $subscription->next_billing_date
            ? $subscription->next_billing_date->toDateString()
            : null;

        $invoice = $invoiceService->createForSubscription($subscription);

        if ($scheduledDue && $invoice->due_date->toDateString() === $scheduledDue) {
            $billing->advanceNextBillingDate($subscription->fresh(['plan']));
        }

        return redirect()
            ->route('billing.invoices.show', $invoice)
            ->with('success', 'Invoice dibuat: '.$invoice->invoice_number.' (jatuh tempo '.$invoice->due_date->format('d/m/Y').')');
    }

    public function destroy(Subscription $subscription, Invoice $invoice)
    {
        $this->authorize('delete', $invoice);

        if ((int) $invoice->subscription_id !== (int) $subscription->id) {
            abort(404);
        }

        $number = $invoice->invoice_number;

        $invoice->payments()->delete();
        $invoice->items()->delete();
        $invoice->delete();

        return redirect()
            ->route('billing.subscriptions.show', $subscription)
            ->with('success', 'Invoice '.$number.' dihapus.');
    }

    public function export(Request $request)
    {
        $this->authorize('viewAny', Invoice::class);

        $rows = Invoice::with(['subscription.client'])
            ->orderByDesc('due_date')
            ->limit(5000)
            ->get();

        $filename = 'invoices-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['invoice_number', 'client', 'amount', 'amount_paid', 'due_date', 'status']);
            foreach ($rows as $inv) {
                fputcsv($out, [
                    $inv->invoice_number,
                    optional($inv->subscription->client)->company_name,
                    $inv->amount,
                    $inv->amount_paid,
                    $inv->due_date->toDateString(),
                    $inv->status,
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
