<!DOCTYPE html>
<html>
<body style="font-family: Arial, sans-serif; color: #1f2937; line-height: 1.5;">
    <p>Hello {{ $invoice->client->contact_name }},</p>
    <p>
        Please find invoice <strong>{{ $invoice->number }}</strong> for <strong>{{ $invoice->project->name }}</strong> attached as a PDF.
    </p>
    <p>
        Amount due: <strong>${{ number_format((float) $invoice->total, 2) }}</strong><br>
        Due date: {{ $invoice->due_date->format('d M Y') }}
    </p>
    <p>
        You can pay online (sandbox demo) here:<br>
        <a href="{{ route('pay.show', $invoice->public_token) }}">{{ route('pay.show', $invoice->public_token) }}</a>
    </p>
    <p>Thank you,<br>{{ config('app.name') }}</p>
</body>
</html>
