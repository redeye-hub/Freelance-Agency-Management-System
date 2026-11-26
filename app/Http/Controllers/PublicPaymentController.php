<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use App\Services\SandboxGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Public "pay online" page, reached through the secret link in the invoice email.
 * SANDBOX ONLY: see SandboxGateway. No card number is ever stored.
 */
class PublicPaymentController extends Controller
{
    public function show(Invoice $invoice)
    {
        $invoice->load('items', 'client', 'project');

        return view('invoices.pay', [
            'invoice' => $invoice,
            'successCard' => SandboxGateway::SUCCESS_CARD,
            'declineCard' => SandboxGateway::DECLINE_CARD,
        ]);
    }

    public function pay(Request $request, Invoice $invoice, SandboxGateway $gateway)
    {
        $data = $request->validate([
            'card_number' => ['required', 'string', 'max:30'],
            'card_name' => ['required', 'string', 'max:100'],
        ]);

        // Lock the row so a double click cannot charge twice.
        $result = DB::transaction(function () use ($invoice, $data, $gateway) {
            $locked = Invoice::whereKey($invoice->id)->lockForUpdate()->first();

            if (! $locked->isPayable()) {
                return ['ok' => false, 'message' => 'This invoice can no longer be paid online.'];
            }

            $charge = $gateway->charge($data['card_number'], (float) $locked->total);

            Payment::create([
                'invoice_id' => $locked->id,
                'amount' => $locked->total,
                'method' => 'sandbox_card',
                'status' => $charge['ok'] ? 'succeeded' : 'failed',
                'reference' => $charge['reference'] ?? null,
                'card_last4' => $charge['last4'] ?? null,
                'failure_reason' => $charge['ok'] ? null : $charge['message'],
            ]);

            if ($charge['ok']) {
                $locked->update(['status' => 'paid', 'paid_at' => now()]);
            }

            return $charge;
        });

        if (! $result['ok']) {
            return back()->withErrors(['card_number' => $result['message']]);
        }

        return redirect()->route('pay.show', $invoice->public_token)
            ->with('success', 'Payment received (sandbox). Thank you!');
    }
}
