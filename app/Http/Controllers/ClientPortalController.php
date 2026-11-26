<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Invoice;
use Illuminate\Http\Request;

class ClientPortalController extends Controller
{
    private function client(): Client
    {
        // Links the logged-in user to a client record by email
        return Client::where('email', auth()->user()->email)->firstOrFail();
    }

    public function invoices(Request $request)
    {
        $invoices = Invoice::where('client_id', $this->client()->id)
            ->latest()
            ->paginate(15);

        return view('portal.invoices', compact('invoices'));
    }

    public function showInvoice(Invoice $invoice)
    {
        // Block clients from opening someone else's invoice
        abort_unless($invoice->client_id === $this->client()->id, 403);

        return view('portal.invoice-show', compact('invoice'));
    }
}