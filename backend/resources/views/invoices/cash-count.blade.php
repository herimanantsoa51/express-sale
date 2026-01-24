{{-- resources/views/invoices/cash-count.blade.php --}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Comptage de Caisse - {{ $cashCount->count_date->format('d/m/Y') }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 8mm;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'DejaVu Sans', sans-serif;
            font-size: 8pt;
            color: #1d1d1f;
            line-height: 1.3;
            background: #fff;
            padding: 10px;
        }

        /* HEADER */
        .header {
            padding-bottom: 12px;
            margin-bottom: 15px;
            border-bottom: 2px solid #e5e7eb;
        }
        .header-content {
            display: table;
            width: 100%;
        }
        .logo-section,
        .report-details {
            display: table-cell;
            vertical-align: middle;
        }
        .company-name-header {
            font-size: 16pt;
            font-weight: 700;
        }
        .report-details {
            text-align: right;
        }
        .report-type {
            font-size: 10pt;
            font-weight: 600;
            color: #059669;
            margin-bottom: 4px;
        }
        .report-number {
            font-size: 14pt;
            font-weight: 700;
            margin-bottom: 3px;
        }
        .report-date {
            font-size: 8pt;
            color: #6b7280;
        }

        /* INFO SECTION */
        .info-section {
            margin: 12px 0;
            padding: 10px 12px;
            background: #f9fafb;
            border-radius: 8px;
            display: table;
            width: 100%;
        }
        .info-left,
        .info-right {
            display: table-cell;
            vertical-align: top;
            width: 50%;
        }
        .info-row {
            margin-bottom: 5px;
            font-size: 8pt;
        }
        .info-label {
            color: #6b7280;
            display: inline-block;
            width: 100px;
        }
        .info-value {
            font-weight: 600;
            color: #1d1d1f;
        }

        /* DENOMINATIONS TABLE */
        .denominations-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        .denominations-table thead {
            background: #f3f4f6;
            border-bottom: 2px solid #d1d5db;
        }
        .denominations-table th {
            padding: 8px 10px;
            font-size: 7pt;
            font-weight: 700;
            color: #374151;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            text-align: left;
        }
        .denominations-table th.text-center { text-align: center; }
        .denominations-table th.text-right { text-align: right; }
        
        .denominations-table td {
            padding: 10px;
            font-size: 8pt;
            border-bottom: 1px solid #f3f4f6;
        }
        .denominations-table td.text-center { text-align: center; }
        .denominations-table td.text-right { text-align: right; }
        
        .denominations-table tbody tr:hover {
            background: #f9fafb;
        }
        
        .denomination-value {
            font-weight: 600;
            color: #059669;
            font-size: 9pt;
        }
        .quantity-value {
            font-weight: 600;
            color: #1d1d1f;
        }
        .subtotal-value {
            font-weight: 700;
            color: #1d1d1f;
        }

        /* TOTALS SECTION */
        .totals-section {
            margin-top: 20px;
            float: right;
            width: 320px;
        }
        .totals-box {
            background: #ecfdf5;
            border: 2px solid #059669;
            border-radius: 8px;
            padding: 15px;
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            font-size: 9pt;
        }
        .total-row.final {
            border-top: 2px solid #059669;
            margin-top: 8px;
            padding-top: 12px;
            font-size: 14pt;
            font-weight: 700;
        }
        .total-label {
            color: #047857;
        }
        .total-value {
            font-weight: 600;
            color: #1d1d1f;
        }
        .total-row.final .total-value {
            color: #059669;
        }

        /* NOTES */
        .notes-section {
            clear: both;
            margin: 20px 0;
            padding: 12px;
            background: #fffbeb;
            border-left: 3px solid #f59e0b;
            border-radius: 4px;
        }
        .notes-title {
            font-weight: 700;
            font-size: 8pt;
            color: #92400e;
            margin-bottom: 5px;
        }
        .notes-content {
            font-size: 8pt;
            color: #78350f;
            line-height: 1.5;
        }

        /* SIGNATURE */
        .signature-section {
            margin-top: 40px;
            display: table;
            width: 100%;
        }
        .signature-box {
            display: table-cell;
            width: 45%;
            text-align: center;
            padding: 15px;
            border: 1px dashed #d1d5db;
            border-radius: 6px;
        }
        .signature-box:last-child {
            float: right;
        }
        .signature-label {
            font-size: 7pt;
            color: #6b7280;
            text-transform: uppercase;
            margin-bottom: 30px;
        }
        .signature-name {
            font-size: 8pt;
            font-weight: 600;
            color: #1d1d1f;
            border-top: 1px solid #d1d5db;
            padding-top: 5px;
            display: inline-block;
            min-width: 150px;
        }

        /* FOOTER */
        .footer {
            margin-top: 30px;
            padding-top: 12px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
        }
        .company-footer {
            font-size: 7pt;
            color: #6b7280;
            line-height: 1.6;
        }
        .company-footer-name {
            font-weight: 600;
            color: #1d1d1f;
            font-size: 8pt;
        }
        .generation-info {
            margin-top: 8px;
            font-size: 6.5pt;
            color: #9ca3af;
        }

        .clearfix::after {
            content: "";
            display: table;
            clear: both;
        }

        /* STATS */
        .stats-section {
            margin: 15px 0;
            display: table;
            width: 100%;
        }
        .stat-box {
            display: table-cell;
            width: 33.33%;
            padding: 10px;
            text-align: center;
            background: #f9fafb;
            border-radius: 6px;
            margin: 0 5px;
        }
        .stat-value {
            font-size: 14pt;
            font-weight: 700;
            color: #059669;
        }
        .stat-label {
            font-size: 7pt;
            color: #6b7280;
            text-transform: uppercase;
            margin-top: 3px;
        }
    </style>
</head>
<body>

{{-- HEADER --}}
<div class="header">
    <div class="header-content">
        <div class="logo-section">
            <div class="company-name-header">{{ $company['name'] }}</div>
        </div>
        <div class="report-details">
            <div class="report-type">COMPTAGE DE CAISSE</div>
            <div class="report-number">#CC-{{ str_pad($cashCount->id, 6, '0', STR_PAD_LEFT) }}</div>
            <div class="report-date">{{ $cashCount->count_date->format('d/m/Y') }}</div>
        </div>
    </div>
</div>

{{-- INFO SECTION --}}
<div class="info-section">
    <div class="info-left">
        <div class="info-row">
            <span class="info-label">Date du comptage:</span>
            <span class="info-value">{{ $cashCount->count_date->format('d/m/Y') }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Heure:</span>
            <span class="info-value">{{ $cashCount->created_at->format('H:i') }}</span>
        </div>
    </div>
    <div class="info-right">
        <div class="info-row">
            <span class="info-label">Créé par:</span>
            <span class="info-value">{{ $cashCount->creator->name }}</span>
        </div>
    </div>
</div>

{{-- STATS --}}
<div class="stats-section">
    <div class="stat-box" style="margin-left: 0;">
        <div class="stat-value">{{ $cashCount->denominations->count() }}</div>
        <div class="stat-label">Types de billets</div>
    </div>
    <div class="stat-box">
        <div class="stat-value">{{ $cashCount->denominations->sum('quantity') }}</div>
        <div class="stat-label">Total billets</div>
    </div>
    <div class="stat-box" style="margin-right: 0;">
        <div class="stat-value">{{ number_format($cashCount->total_amount, 0, ',', ' ') }}</div>
        <div class="stat-label">Montant total (Ar)</div>
    </div>
</div>

{{-- DENOMINATIONS TABLE --}}
<table class="denominations-table">
    <thead>
        <tr>
            <th style="width: 5%;">#</th>
            <th style="width: 35%;">Dénomination</th>
            <th class="text-center" style="width: 20%;">Quantité</th>
            <th class="text-right" style="width: 20%;">Valeur unitaire</th>
            <th class="text-right" style="width: 20%;">Sous-total</th>
        </tr>
    </thead>
    <tbody>
        @foreach($cashCount->denominations->sortByDesc('denomination') as $index => $denom)
        <tr>
            <td>{{ $index + 1 }}</td>
            <td>
                <span style="font-weight: 600;">Billet de {{ number_format($denom->denomination, 0, ',', ' ') }} Ar</span>
            </td>
            <td class="text-center">
                <span class="quantity-value">{{ $denom->quantity }}</span>
            </td>
            <td class="text-right">
                <span class="denomination-value">{{ number_format($denom->denomination, 0, ',', ' ') }} Ar</span>
            </td>
            <td class="text-right">
                <span class="subtotal-value">{{ number_format($denom->subtotal, 0, ',', ' ') }} Ar</span>
            </td>
        </tr>
        @endforeach
    </tbody>
</table>

{{-- TOTALS --}}
<div class="totals-section">
    <div class="totals-box">
        <div class="total-row">
            <span class="total-label">Nombre de billets:</span>
            <span class="total-value">{{ $cashCount->denominations->sum('quantity') }}</span>
        </div>
        <div class="total-row final">
            <span class="total-label">TOTAL:</span>
            <span class="total-value">{{ number_format($cashCount->total_amount, 0, ',', ' ') }} Ar</span>
        </div>
    </div>
</div>

<div class="clearfix"></div>

{{-- NOTES --}}
@if($cashCount->notes)
<div class="notes-section">
    <div class="notes-title">Notes</div>
    <div class="notes-content">{{ $cashCount->notes }}</div>
</div>
@endif

{{-- SIGNATURE --}}
<div class="signature-section">
    <div class="signature-box">
        <div class="signature-label">Comptage effectué par</div>
        <div class="signature-name">{{ $cashCount->creator->name }}</div>
    </div>
</div>

<div class="clearfix"></div>

{{-- FOOTER --}}
<div class="footer">
    <div class="company-footer">
        <div class="company-footer-name">{{ $company['name'] }}</div>
        @if($company['address']) {{ $company['address'] }}<br> @endif
        @if($company['phone']) Tél: {{ $company['phone'] }} @endif
        @if($company['email']) • Email: {{ $company['email'] }} @endif
    </div>
    <div class="generation-info">
        Document généré le {{ now()->format('d/m/Y à H:i') }}
    </div>
</div>

</body>
</html>