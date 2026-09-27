<strong>Issue #: </strong>{{ $parent->issue_number }},
<strong>Issue Date: </strong>{{ \App\Support\YiiFormat::dateTime($parent->issue_date) }},
<strong>Issue By: </strong>{{ $parent->issueBy?->full_name }}
