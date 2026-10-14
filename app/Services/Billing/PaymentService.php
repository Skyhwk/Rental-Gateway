<?php

namespace App\Services\Billing;

use App\Models\AccessLog;
use App\Models\Application;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use App\Services\AccessControlService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    /** @var InvoiceService */
    protected $invoices;

    /** @var AccessControlService */
    protected $accessControl;

    public function __construct(InvoiceService $invoices, AccessControlService $accessControl)
    {
        $this->invoices = $invoices;
        $this->accessControl = $accessControl;
    }

    public function recordPayment(Invoice $invoice, array $data, UploadedFile $proof = null)
    {
        if (in_array($invoice->status, [Invoice::STATUS_PAID, Invoice::STATUS_VOID], true)) {
            throw ValidationException::withMessages(['invoice' => ['Invoice tidak menerima pembayaran.']]);
        }

        $reference = isset($data['reference_number']) ? trim((string) $data['reference_number']) : null;
        if ($reference === '') {
            $reference = null;
        }

        if ($reference) {
            $exists = Payment::where('invoice_id', $invoice->id)
                ->where('reference_number', $reference)
                ->exists();
            if ($exists) {
                throw ValidationException::withMessages(['reference_number' => ['Nomor referensi sudah digunakan untuk invoice ini.']]);
            }
        }

        $proofPath = null;
        if ($proof) {
            $proofPath = $proof->store('payment-proofs/'.now()->format('Y/m'), config('billing.payment_proof_disk'));
        }

        return Payment::create([
            'invoice_id' => $invoice->id,
            'amount' => $data['amount'],
            'paid_at' => $data['paid_at'],
            'method' => $data['method'] ?? 'transfer',
            'reference_number' => $reference,
            'proof_path' => $proofPath,
            'verification_status' => Payment::STATUS_PENDING,
            'notes' => $data['notes'] ?? null,
        ]);
    }

    public function verify(Payment $payment, User $verifier)
    {
        if ($payment->verification_status !== Payment::STATUS_PENDING) {
            throw ValidationException::withMessages(['payment' => ['Pembayaran sudah diproses.']]);
        }

        return DB::transaction(function () use ($payment, $verifier) {
            $invoice = Invoice::where('id', $payment->invoice_id)->lockForUpdate()->firstOrFail();

            $payment->update([
                'verification_status' => Payment::STATUS_VERIFIED,
                'verified_by' => $verifier->id,
                'verified_at' => now(),
            ]);

            $invoice->amount_paid = (float) $invoice->amount_paid + (float) $payment->amount;
            $invoice->save();

            $invoice = $this->invoices->refreshStatus($invoice);

            if (config('billing.auto_activate_on_payment_verified') && $invoice->status === Invoice::STATUS_PAID) {
                $this->maybeActivateApplication($invoice, $verifier);
            }

            return $payment->fresh(['invoice']);
        });
    }

    public function reject(Payment $payment, User $verifier, $reason = null)
    {
        if ($payment->verification_status !== Payment::STATUS_PENDING) {
            throw ValidationException::withMessages(['payment' => ['Pembayaran sudah diproses.']]);
        }

        $payment->update([
            'verification_status' => Payment::STATUS_REJECTED,
            'verified_by' => $verifier->id,
            'verified_at' => now(),
            'notes' => trim(($payment->notes ? $payment->notes."\n" : '').'Ditolak: '.($reason ?: '-')),
        ]);

        return $payment->fresh();
    }

    protected function maybeActivateApplication(Invoice $invoice, User $verifier)
    {
        $subscription = $invoice->subscription()->with('application')->first();
        if (! $subscription || ! $subscription->application) {
            return;
        }

        $application = $subscription->application;
        if ($application->access_status === Application::ACCESS_ACTIVE) {
            return;
        }

        $this->accessControl->changeStatus(
            $application,
            Application::ACCESS_ACTIVE,
            'Pembayaran diverifikasi — invoice '.$invoice->invoice_number,
            $verifier,
            AccessLog::SOURCE_SYSTEM
        );
    }
}
