<h1>Profit & Loss</h1>
<p>Period: {{ $from }} - {{ $to }}</p>
<table border="1" cellpadding="6">
<tr><th>Income</th><td style="text-align:right">{{ number_format($data['income'],2) }}</td></tr>
<tr><th>Expense</th><td style="text-align:right">{{ number_format($data['expense'],2) }}</td></tr>
<tr><th>Profit</th><td style="text-align:right">{{ number_format($data['profit'],2) }}</td></tr>
</table>
