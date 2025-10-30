<h1>Balance Sheet</h1>
<p>As of: {{ $asOf }}</p>
<table border="1" cellpadding="6">
<tr><th>Assets</th><td style="text-align:right">{{ number_format($data['assets'],2) }}</td></tr>
<tr><th>Liabilities</th><td style="text-align:right">{{ number_format($data['liabilities'],2) }}</td></tr>
<tr><th>Equity (incl. retained)</th><td style="text-align:right">{{ number_format($data['equity_incl_retained'],2) }}</td></tr>
<tr><th>Balanced?</th><td>{{ $data['balanced'] ? 'Yes' : 'No' }}</td></tr>
</table>
