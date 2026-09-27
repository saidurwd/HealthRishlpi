<strong>Requisition #: </strong>{{ $parent->requisition_number }},
<strong>Requisition Date: </strong>{{ \App\Support\YiiFormat::dateTime($parent->requisition_date) }},
<strong>Requisition By: </strong>{{ $parent->requisitionBy?->full_name }}
