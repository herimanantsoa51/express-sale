{{-- resources/views/invoices/payment-receipt.blade.php --}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Reçu de Paiement</title>
    <style>
        @page {
            size: A5 portrait;
            margin: 8mm;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'DejaVu Sans', sans-serif;
            font-size: 7.5pt;
            color: #1d1d1f;
            line-height: 1.35;
            background: #fff;
            padding: 6px;
        }

        /* HEADER */
        .header {
            padding-bottom: 8px;
            margin-bottom: 10px;
            border-bottom: 1px solid #e5e7eb;
        }
        .header-content {
            display: table;
            width: 100%;
        }
        .logo-section,
        .receipt-details {
            display: table-cell;
            vertical-align: middle;
        }
        .company-name-header {
            font-size: 12pt;
            font-weight: 700;
        }
        .receipt-details {
            text-align: right;
        }
        .receipt-type {
            font-size: 8pt;
            font-weight: 600;
        }
        .receipt-number {
            font-size: 12pt;
            font-weight: 700;
        }
        .receipt-date {
            font-size: 7pt;
            color: #6b7280;
        }

        /* CLIENT */
        .client-info {
            margin: 8px 0;
        }
        .client-name {
            font-size: 9pt;
            font-weight: 600;
        }
        .client-details {
            font-size: 7pt;
            color: #6b7280;
        }

        /* SECTIONS */
        .section {
            margin: 8px 0;
            padding: 8px;
            background: #f9fafb;
            border-radius: 6px;
        }
        .section-title {
            font-size: 7pt;
            font-weight: 600;
            margin-bottom: 6px;
            text-transform: uppercase;
            color: #374151;
        }

        /* PAYMENT DETAILS */
        .payment-details {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 4px;
        }
        .detail-label {
            font-size: 7pt;
            color: #6b7280;
        }
        .detail-value {
            font-size: 7.5pt;
            font-weight: 600;
        }
        .detail-value.amount {
            font-size: 9pt;
            color: #065f46;
        }

        /* TABLE */
        .info-table {
            width: 100%;
            border-collapse: collapse;
        }
        .info-table td {
            padding: 4px 2px;
            font-size: 7pt;
            border-bottom: 1px solid #f3f4f6;
        }
        .info-table td:first-child {
            width: 42%;
            color: #6b7280;
        }

        /* FOOTER */
        .footer {
            margin-top: 14px;
            padding-top: 8px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            font-size: 6.5pt;
            color: #6b7280;
        }

        .signature-text {
            font-style: italic;
            margin-bottom: 6px;
            color: #515154;
        }

        .company-footer-name {
            font-weight: 600;
            font-size: 7.5pt;
            color: #1d1d1f;
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
        <div class="receipt-details">
            <div class="receipt-type">REÇU DE PAIEMENT</div>
            <div class="receipt-number">
                {{ $transaction->transaction->reference_number ?? 'REC-' . str_pad($transaction->id, 6, '0', STR_PAD_LEFT) }}
            </div>
            <div class="receipt-date">{{ $transaction->payment_date->format('d/m/Y H:i') }}</div>
        </div>
    </div>
</div>

{{-- CLIENT --}}
<div class="client-info">
    <div class="client-name">{{ $credit->customer->name }}</div>
    <div class="client-details">
        Client #{{ $credit->customer->customer_number }}
        @if($credit->customer->phone) • {{ $credit->customer->phone }} @endif
    </div>
</div>

{{-- PAIEMENT --}}
<div class="section payment-details">
    <div class="section-title">Paiement</div>

    <div class="detail-row">
        <span class="detail-label">Montant</span>
        <span class="detail-value amount">{{ number_format($transaction->amount, 0, ',', ' ') }} Ar</span>
    </div>

    <div class="detail-row">
        <span class="detail-label">Mode</span>
        <span class="detail-value">
            {{ $transaction->transaction->account->accountType->display_name ?? '—' }}
        </span>
    </div>

    <div class="detail-row">
        <span class="detail-label">Encaissé par</span>
        <span class="detail-value">
            {{ $transaction->transaction->creator->name ?? '—' }}
        </span>
    </div>
</div>

{{-- ÉCHÉANCE --}}
<div class="section">
    <div class="section-title">Échéance</div>
    <table class="info-table">
        <tr><td>Crédit</td><td>{{ $credit->sale->sale_number }}</td></tr>
        <tr><td>Échéance</td><td>#{{ $installment->installment_number }}</td></tr>
        <tr><td>Due</td><td>{{ $installment->due_date->format('d/m/Y') }}</td></tr>
        <tr><td>Montant</td><td>{{ number_format($installment->amount_due, 0, ',', ' ') }} Ar</td></tr>
        <tr><td>Reste</td><td>{{ number_format($remaining_amount, 0, ',', ' ') }} Ar</td></tr>
    </table>
</div>

{{-- FOOTER --}}
<div class="footer">
    @if($company['invoice_signature'])
        <div class="signature-text">{{ $company['invoice_signature'] }}</div>
    @endif

    <div class="company-footer-name">{{ $company['name'] }}</div>
    @if($company['address']) {{ $company['address'] }}<br> @endif
    @if($company['phone']) Tél: {{ $company['phone'] }} @endif
</div>

</body>
</html>
