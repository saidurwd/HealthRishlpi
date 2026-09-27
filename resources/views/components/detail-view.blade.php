@props(['rows'])
{{-- Port of Yii's CDetailView: label => value rows. Values are escaped unless they are Htmlable. --}}
<table class="table table-bordered table-striped mb-0 detail-view">
    <tbody>
        @foreach ($rows as $label => $value)
            <tr>
                <th class="w-25">{{ $label }}</th>
                <td>{{ $value }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
