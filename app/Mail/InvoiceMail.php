<?php

namespace App\Mail;

use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class InvoiceMail extends Mailable
{
    public function __construct(public Invoice $invoice)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Invoice {$this->invoice->number} from " . config('app.name'));
    }

    public function content(): Content
    {
        return new Content(view: 'emails.invoice');
    }

    /** The invoice PDF is generated on the fly and attached. */
    public function attachments(): array
    {
        $invoice = $this->invoice->loadMissing('items', 'client', 'project');
        $pdf = Pdf::loadView('invoices.pdf', ['invoice' => $invoice])->output();

        return [
            Attachment::fromData(fn () => $pdf, "invoice-{$invoice->number}.pdf")->withMime('application/pdf'),
        ];
    }
}
