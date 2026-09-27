<strong>Transfer #: </strong>{{ $parent->transfer_number }},
<strong>Transfer Date: </strong>{{ \App\Support\YiiFormat::dateTime($parent->transfer_date) }},
<strong>Transfer By: </strong>{{ $parent->transferBy?->full_name }}
