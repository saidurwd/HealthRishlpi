<strong>Order #: </strong>{{ $parent->order_number }},
<strong>Order Date: </strong>{{ \App\Support\YiiFormat::dateTime($parent->order_date) }},
<strong>Order By: </strong>{{ $parent->orderBy?->full_name }}
