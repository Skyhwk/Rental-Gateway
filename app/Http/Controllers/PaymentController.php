<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use App\Services\Billing\PaymentService;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', Payment::class);

        $unpaidInvoices = Invoice::with(['subscription.client', 'subscription.application'])
            ->whereNotIn('status', [Invoice::STATUS_PAID, Invoice::STATUS_VOID])
            ->orderBy('due_date')
            ->limit(50)
            ->get();

        $paymentsQuery = Payment::with(['invoice.subscription.client', 'verifiedBy'])
            ->orderByDesc('id');

        if ($request->get('filter') === 'pending') {
            $paymentsQuery->where('verification_status', Payment::STATUS_PENDING);
        }

        $payments = $paymentsQuery->paginate(20)->withQueryString();

        $pendingCount = Payment::where('verification_status', Payment::STATUS_PENDING)->count();

        $openInvoices = Invoice::with(['subscription.client'])
            ->whereNotIn('status', [Invoice::STATUS_PAID, Invoice::STATUS_VOID])
            ->orderBy('due_date')
            ->get();

        return view('billing.payments.index', compact('unpaidInvoices', 'openInvoices', 'payments', 'pendingCount'));
    }

    public function create(Request $request)
    {
        $this->authorize('create', Payment::class);

        $params = [];
        if ($request->filled('invoice_id')) {
            $params['record'] = $request->get('invoice_id');
        }

        return redirect()->route('billing.payments.index', $params);
    }

    public function store(Request $request, PaymentService $payments)
    {
        $this->authorize('create', Payment::class);

        $maxKb = config('billing.payment_proof_max_kb', 5120);

        $data = $request->validate([
            'invoice_id' => ['required', 'exists:invoices,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'paid_at' => ['required', 'date'],
            'method' => ['required', 'in:'.implode(',', array_keys(Payment::methods()))],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
            'proof' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:'.$maxKb],
        ]);

        $invoice = Invoice::findOrFail($data['invoice_id']);
        $this->authorize('view', $invoice);
        unset($data['invoice_id']);

        if ((float) $data['amount'] > $invoice->balanceDue()) {
            return redirect()
                ->route('billing.payments.index', ['record' => $invoice->id])
                ->withInput()
                ->with('error', 'Nominal melebihi sisa tagihan.');
        }

        $payments->recordPayment($invoice, $data, $request->file('proof'));

        return redirect()
            ->route('billing.payments.index')
            ->with('success', 'Pembayaran dicatat — menunggu verifikasi.');
    }

    public function verify(Payment $payment, PaymentService $payments)
    {
        $this->authorize('verify', $payment);

        $payments->verify($payment, auth()->user());

        return redirect()
            ->route('billing.payments.index', ['filter' => 'pending'])
            ->with('success', 'Pembayaran diverifikasi.');
    }

    public function reject(Request $request, Payment $payment, PaymentService $payments)
    {
        $this->authorize('verify', $payment);

        $data = $request->validate(['reason' => ['nullable', 'string', 'max:500']]);

        $payments->reject($payment, auth()->user(), $data['reason'] ?? null);

        return redirect()
            ->route('billing.payments.index', ['filter' => 'pending'])
            ->with('success', 'Pembayaran ditolak.');
    }
}
