{{-- Heading shared by the invoice pages: "Invoice#: ..., Invoice Date: ..., Invoice By: ..." --}}
<strong>Invoice#: </strong>{{ $parent->invoice_number }},
<strong>Invoice Date: </strong>{{ \App\Support\YiiFormat::dateTime($parent->invoice_date) }},
<strong>{{ $byLabel ?? 'Invoice By' }}: </strong>{{ $parent->invoiceBy?->full_name }}
