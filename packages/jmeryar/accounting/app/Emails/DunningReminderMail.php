<?php

namespace Jmeryar\Accounting\Emails;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Jmeryar\Accounting\Models\DunningLevel;
use Jmeryar\Sales\Models\Invoice;

class DunningReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Invoice $invoice,
        public DunningLevel $dunningLevel
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->dunningLevel->email_subject ?? "Payment Reminder: Invoice {$this->invoice->invoice_number}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'accounting::emails.dunning-reminder',
            with: [
                'body' => $this->dunningLevel->email_body,
                'customerName' => $this->invoice->customer?->name,
                'invoiceNumber' => $this->invoice->invoice_number,
                'dueDate' => $this->invoice->due_date->format('Y-m-d'),
                'totalAmount' => $this->invoice->total_amount->formatTo('en_US'),
            ],
        );
    }
}
