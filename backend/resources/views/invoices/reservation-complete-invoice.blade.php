{{-- resources/views/invoices/reservation-complete-invoice.blade.php --}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Reçu de Finalisation de Réservation</title>
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
            color: #2563eb;
        }
        .receipt-number {
            font-size: 12pt;
            font-weight: 700;
        }
        .receipt-date {
            font-size: 7pt;
            color: #6b7280;
        }
        .status-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 6.5pt;
            font-weight: 600;
            background: #d1fae5;
            color: #065f46;
            margin-top: 3px;
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

        /* RESERVATION SUMMARY */
        .reservation-summary {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
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
        .info-table tr:last-child td {
            border-bottom: none;
        }

        /* TOTALS */
        .totals-section {
            margin: 8px 0;
            padding: 8px;
            background: #fafafa;
            border-radius: 6px;
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 3px 0;
            font-size: 7.5pt;
        }
        .total-row.final {
            border-top: 1px solid #d1d5db;
            padding-top: 5px;
            margin-top: 3px;
            font-weight: 700;
            font-size: 8.5pt;
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

        .thank-you {
            margin-top: 10px;
            padding: 6px;
            background: #fef3c7;
            border-radius: 4px;
            font-size: 7pt;
            color: #92400e;
            text-align: center;
            font-weight: 600;
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
            <div class="receipt-type">REÇU DE FINALISATION</div>
            <div class="receipt-number">
                {{ $payment->transactionComplete->reference_number ?? 'REC-' . str_pad($payment->id, 6, '0', STR_PAD_LEFT) }}
            </div>
            <div class="receipt-date">{{ $payment->completed_at->format('d/m/Y H:i') }}</div>
            <div>
                <span class="status-badge">RÉSERVATION COMPLÉTÉE</span>
            </div>
        </div>
    </div>
</div>

{{-- CLIENT --}}
<div class="client-info">
    <div class="client-name">{{ $payment->customer->name }}</div>
    <div class="client-details">
        Client #{{ $payment->customer->customer_number }}
        @if($payment->customer->phone) • {{ $payment->customer->phone }} @endif
    </div>
</div>

{{-- PAIEMENT FINAL --}}
@if($payment->transactionComplete)
<div class="section payment-details">
    <div class="section-title">Paiement Final</div>

    <div class="detail-row">
        <span class="detail-label">Montant</span>
        <span class="detail-value amount">{{ number_format($payment->transactionComplete->amount, 0, ',', ' ') }} Ar</span>
    </div>

    <div class="detail-row">
        <span class="detail-label">Mode de paiement</span>
        <span class="detail-value">
            {{ $payment->transactionComplete->account->accountType->display_name ?? '—' }}
        </span>
    </div>

    <div class="detail-row">
        <span class="detail-label">Encaissé par</span>
        <span class="detail-value">
            {{ $payment->transactionComplete->creator->name ?? '—' }}
        </span>
    </div>

    <div class="detail-row">
        <span class="detail-label">Date de paiement</span>
        <span class="detail-value">
            {{ $payment->transactionComplete->transaction_date->format('d/m/Y H:i') }}
        </span>
    </div>
</div>
@endif

{{-- RÉSUMÉ RÉSERVATION --}}
<div class="section reservation-summary">
    <div class="section-title">Résumé de la Réservation</div>
    <table class="info-table">
        <tr>
            <td>N° Vente</td>
            <td>{{ $payment->sale->sale_number }}</td>
        </tr>
        <tr>
            <td>Date de réservation</td>
            <td>{{ $payment->reservation_date->format('d/m/Y') }}</td>
        </tr>
        @if($payment->expiry_date)
        <tr>
            <td>Date d'expiration</td>
            <td>{{ $payment->expiry_date->format('d/m/Y') }}</td>
        </tr>
        @endif
        <tr>
            <td>Date de complétion</td>
            <td>{{ $payment->completed_at->format('d/m/Y H:i') }}</td>
        </tr>
    </table>
</div>

{{-- DÉTAIL DES MONTANTS --}}
<div class="totals-section">
    <div class="total-row">
        <span>Montant total</span>
        <span>{{ number_format($payment->total_amount, 0, ',', ' ') }} Ar</span>
    </div>
    <div class="total-row">
        <span>Acompte versé</span>
        <span>{{ number_format($payment->deposit_amount, 0, ',', ' ') }} Ar</span>
    </div>
    @if($payment->transactionComplete)
    <div class="total-row">
        <span>Paiement final</span>
        <span>{{ number_format($payment->transactionComplete->amount, 0, ',', ' ') }} Ar</span>
    </div>
    @endif
    <div class="total-row final">
        <span>Montant payé</span>
        <span>{{ number_format($payment->total_amount, 0, ',', ' ') }} Ar</span>
    </div>
</div>

{{-- MESSAGE DE REMERCIEMENT --}}
<div class="thank-you">
    ✓ Réservation finalisée avec succès • Merci pour votre confiance
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