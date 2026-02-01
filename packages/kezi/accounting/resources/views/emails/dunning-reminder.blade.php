<x-mail::message>
# Payment Reminder

Dear {{ $customerName }},

{{ $body }}

**Invoice Details:**
- **Invoice Number:** {{ $invoiceNumber }}
- **Due Date:** {{ $dueDate }}
- **Total Amount:** {{ $totalAmount }}

<x-mail::button :url="''">
View Invoice
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
