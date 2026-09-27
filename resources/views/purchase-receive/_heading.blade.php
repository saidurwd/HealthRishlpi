<strong>Receive #: </strong>{{ $parent->receive_number }},
<strong>Receive Date: </strong>{{ \App\Support\YiiFormat::dateTime($parent->receive_date) }},
<strong>Receive By: </strong>{{ $parent->receiveBy?->full_name }}
