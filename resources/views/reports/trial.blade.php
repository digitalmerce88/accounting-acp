<h1>Trial Balance</h1>
<p>Period: {{ $from }} - {{ $to }}</p>
<table border="1" cellpadding="6"><tr><th>Code</th><th>Name</th><th>Type</th><th>Dr</th><th>Cr</th></tr>
@foreach($data as $r)
<tr><td>{{ $r[0] }}</td><td>{{ $r[1] }}</td><td>{{ $r[2] }}</td><td style="text-align:right">{{ number_format($r[3],2) }}</td><td style="text-align:right">{{ number_format($r[4],2) }}</td></tr>
@endforeach</table>
