{{-- resources/views/invoices/credit-invoice.blade.php --}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Facture Crédit {{ $credit->sale->sale_number }}</title>
    <style>
        /* MÊME CSS QUE template-fast.blade.php */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'DejaVu Sans', sans-serif; 
            font-size: 9pt; 
            color: #1d1d1f; 
            line-height: 1.5;
            background: #fff;
        }
        
        .header { 
            padding: 30px 0 20px 0;
            margin-bottom: 30px;
        }
        .header-content {
            display: table;
            width: 100%;
        }
        .logo-section {
            display: table-cell;
            width: 200px;
            vertical-align: middle;
        }
        .company-name-header {
            font-size: 20pt;
            font-weight: 700;
            color: #1d1d1f;
            letter-spacing: -0.5px;
        }
        .invoice-details {
            display: table-cell;
            vertical-align: middle;
            text-align: right;
        }
        .invoice-type {
            font-size: 11pt;
            font-weight: 600;
            color: #1d1d1f;
            letter-spacing: -0.3px;
            margin-bottom: 8px;
        }
        .invoice-number {
            font-size: 20pt;
            font-weight: 700;
            color: #1d1d1f;
            letter-spacing: -0.5px;
            margin-bottom: 6px;
        }
        .invoice-date {
            font-size: 9pt;
            color: #86868b;
            margin-bottom: 10px;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 8pt;
            font-weight: 600;
            letter-spacing: 0.3px;
        }
        .status-badge.active { background: #dbeafe; color: #1e40af; }
        .status-badge.partial_paid { background: #fef9c3; color: #a16207; }
        .status-badge.completed { background: #f0fdf4; color: #15803d; }
        .status-badge.overdue { background: #fee2e2; color: #b91c1c; }
        .status-badge.defaulted { background: #f3f4f6; color: #374151; }
        
        .customer-section {
            background: #f5f5f7;
            padding: 20px;
            margin: 25px 0;
            border-radius: 12px;
        }
        .customer-label {
            font-size: 8pt;
            font-weight: 600;
            color: #86868b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }
        .customer-name {
            font-size: 12pt;
            font-weight: 600;
            color: #1d1d1f;
            margin-bottom: 4px;
        }
        .customer-info {
            font-size: 9pt;
            color: #515154;
            line-height: 1.6;
        }
        .credit-type {
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px solid #d2d2d7;
            font-size: 9pt;
        }
        .credit-type-label {
            color: #86868b;
            font-weight: 500;
        }
        .credit-type-value {
            color: #1d1d1f;
            font-weight: 600;
        }
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 25px 0;
        }
        .items-table thead {
            border-bottom: 1px solid #d2d2d7;
        }
        .items-table th {
            padding: 10px 8px;
            font-size: 8pt;
            font-weight: 600;
            color: #86868b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            text-align: left;
        }
        .items-table th.text-center { text-align: center; }
        .items-table th.text-right { text-align: right; }
        .items-table td {
            padding: 12px 8px;
            font-size: 9pt;
            border-bottom: 1px solid #f5f5f7;
            vertical-align: top;
        }
        .items-table td.text-center { text-align: center; }
        .items-table td.text-right { text-align: right; }
        .items-table tbody tr:last-child td { border-bottom: none; }
        
        .product-sku {
            font-family: 'SF Mono', 'Courier New', monospace;
            font-size: 8pt;
            color: #86868b;
        }
        .product-name {
            font-weight: 600;
            color: #1d1d1f;
            margin-bottom: 2px;
        }
        .product-attributes {
            font-size: 8pt;
            color: #86868b;
            margin-top: 2px;
        }
        .item-quantity {
            font-weight: 600;
            color: #1d1d1f;
        }
        .item-price {
            color: #515154;
        }
        .item-total {
            font-weight: 600;
            color: #1d1d1f;
        }
        
        .totals-section {
            float: right;
            width: 280px;
            margin-top: 20px;
        }
        .totals-row {
            display: table;
            width: 100%;
            padding: 8px 0;
        }
        .totals-label {
            display: table-cell;
            font-size: 9pt;
            color: #515154;
        }
        .totals-value {
            display: table-cell;
            text-align: right;
            font-size: 9pt;
            color: #1d1d1f;
        }
        .totals-row.total {
            border-top: 1px solid #d2d2d7;
            margin-top: 8px;
            padding-top: 12px;
        }
        .totals-row.total .totals-label {
            font-size: 11pt;
            font-weight: 600;
            color: #1d1d1f;
        }
        .totals-row.total .totals-value {
            font-size: 14pt;
            font-weight: 700;
            color: #1d1d1f;
        }
        
        /* Section Échéances */
        .installments-section {
            clear: both;
            background: #f8fafc;
            padding: 16px 20px;
            margin: 25px 0;
            border-radius: 12px;
        }
        .installments-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
        }
        .installments-table th {
            padding: 8px;
            font-size: 8pt;
            font-weight: 600;
            color: #86868b;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }
        .installments-table td {
            padding: 10px 8px;
            font-size: 9pt;
            border-bottom: 1px solid #f1f5f9;
        }
        .installment-status {
            font-size: 8pt;
            padding: 2px 8px;
            border-radius: 12px;
            display: inline-block;
        }
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-partial { background: #dbeafe; color: #1e40af; }
        .status-paid { background: #d1fae5; color: #065f46; }
        .status-overdue { background: #fee2e2; color: #991b1b; }
        
        /* Paiement */
        .payment-summary {
            background: #f5f5f7;
            padding: 16px 20px;
            margin: 25px 0;
            border-radius: 12px;
        }
        .payment-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }
        .payment-label {
            font-size: 9pt;
            color: #515154;
        }
        .payment-value {
            font-size: 9pt;
            font-weight: 600;
            color: #1d1d1f;
        }
        .payment-progress {
            height: 8px;
            background: #e5e7eb;
            border-radius: 4px;
            margin: 12px 0;
            overflow: hidden;
        }
        .payment-progress-bar {
            height: 100%;
            background: #10b981;
            border-radius: 4px;
        }
        
        .notes-section {
            background: #fffbeb;
            padding: 16px 20px;
            margin: 20px 0;
            border-radius: 12px;
            font-size: 9pt;
            color: #515154;
        }
        .notes-label {
            font-weight: 600;
            color: #1d1d1f;
            margin-bottom: 4px;
        }
        
        .footer {
            margin-top: 50px;
            padding-top: 20px;
            border-top: 1px solid #d2d2d7;
        }
        .signature-section {
            text-align: center;
            margin-bottom: 20px;
        }
        .signature-text {
            font-size: 9pt;
            color: #515154;
            line-height: 1.6;
            font-style: italic;
        }
        .company-footer {
            background: #f5f5f7;
            padding: 16px 20px;
            border-radius: 12px;
            font-size: 8pt;
            color: #86868b;
            line-height: 1.8;
        }
        .company-footer-name {
            font-weight: 600;
            color: #1d1d1f;
            font-size: 9pt;
            margin-bottom: 6px;
        }
        .footer-contacts {
            margin-top: 4px;
        }
        .footer-meta {
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px solid #d2d2d7;
            text-align: center;
            color: #86868b;
        }
        
        .clearfix::after {
            content: "";
            display: table;
            clear: both;
        }
    </style>
</head>
<body>
    {{-- Header --}}
    <div class="header">
        <div class="header-content">
            <div class="logo-section">
                <div class="company-name-header">{{ $company['name'] }}</div>
            </div>
            <div class="invoice-details">
                <div class="invoice-type">FACTURE DE CRÉDIT</div>
                <div class="invoice-number">{{ $credit->sale->sale_number }}</div>
                <div class="invoice-date">Crédit du {{ $credit->credit_date->format('d/m/Y') }}</div>
                <div>
                    <span class="status-badge {{ $credit->status }}">
                        {{ strtoupper(str_replace('_', ' ', $credit->status)) }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    {{-- Client --}}
    <div class="customer-section">
        <div class="customer-label">Client</div>
        <div class="customer-name">{{ $credit->customer->name }}</div>
        <div class="customer-info">
            N° Client: {{ $credit->customer->customer_number }}
            @if($credit->customer->phone)
                <br>Téléphone: {{ $credit->customer->phone }}
            @endif
            @if($credit->customer->address)
                <br>{{ $credit->customer->address }}
            @endif
        </div>
        <div class="credit-type">
            <span class="credit-type-label">Type de vente:</span>
            <span class="credit-type-value">CRÉDIT</span>
        </div>
    </div>

    {{-- Articles --}}
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 15%;">SKU</th>
                <th style="width: 40%;">Produit</th>
                <th class="text-center" style="width: 10%;">Qté</th>
                <th class="text-right" style="width: 17%;">Prix unitaire</th>
                <th class="text-right" style="width: 18%;">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($credit->sale->items as $item)
            <tr>
                <td>
                    <div class="product-sku">{{ $item->variant->sku }}</div>
                </td>
                <td>
                    <div class="product-name">{{ $item->variant->product->name }}</div>
                    @php
                        $attrs = $item->variant->relationLoaded('variantAttributeValues') 
                            ? $item->variant->attributeTypeValueMap() 
                            : [];
                    @endphp
                    @if(!empty($attrs))
                        <div class="product-attributes">
                            {{ collect($attrs)->map(fn($v, $k) => "$k: $v")->implode(' • ') }}
                        </div>
                    @endif
                </td>
                <td class="text-center">
                    <span class="item-quantity">{{ $item->quantity }}</span>
                </td>
                <td class="text-right">
                    <span class="item-price">{{ number_format($item->unit_price, 0, ',', ' ') }} Ar</span>
                </td>
                <td class="text-right">
                    <span class="item-total">{{ number_format($item->subtotal, 0, ',', ' ') }} Ar</span>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Totaux --}}
    <div class="totals-section">
        <div class="totals-row">
            <span class="totals-label">Montant total du crédit</span>
            <span class="totals-value">{{ number_format($credit->total_amount, 0, ',', ' ') }} Ar</span>
        </div>
        
        <div class="totals-row">
            <span class="totals-label">Déjà payé</span>
            <span class="totals-value">{{ number_format($credit->amount_paid, 0, ',', ' ') }} Ar</span>
        </div>
        
        <div class="totals-row total">
            <span class="totals-label">Reste à payer</span>
            <span class="totals-value">{{ number_format($credit->amount_due, 0, ',', ' ') }} Ar</span>
        </div>
    </div>

    <div class="clearfix"></div>

    {{-- Échéances --}}
    @if($credit->installments->count() > 0)
    <div class="installments-section">
        <div style="font-weight: 600; color: #1d1d1f; margin-bottom: 8px;">
            PLAN DE PAIEMENT
        </div>
        <table class="installments-table">
            <thead>
                <tr>
                    <th>Échéance</th>
                    <th>Date d'échéance</th>
                    <th class="text-right">Montant dû</th>
                    <th class="text-right">Payé</th>
                    <th class="text-right">Reste</th>
                    <th>Statut</th>
                </tr>
            </thead>
            <tbody>
                @foreach($credit->installments as $installment)
                <tr>
                    <td>#{{ $installment->installment_number }}</td>
                    <td>{{ $installment->due_date->format('d/m/Y') }}</td>
                    <td class="text-right">{{ number_format($installment->amount_due, 0, ',', ' ') }} Ar</td>
                    <td class="text-right">{{ number_format($installment->amount_paid, 0, ',', ' ') }} Ar</td>
                    <td class="text-right">{{ number_format($installment->getRemainingAmount(), 0, ',', ' ') }} Ar</td>
                    <td>
                        @php
                            $statusClass = 'status-' . $installment->status;
                            $statusText = $installment->status;
                            if($installment->isOverdue()) {
                                $statusClass = 'status-overdue';
                                $statusText = 'overdue';
                            }
                        @endphp
                        <span class="installment-status {{ $statusClass }}">
                            {{ strtoupper($statusText) }}
                        </span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif


    {{-- Notes --}}
    @if($credit->notes)
    <div class="notes-section">
        <div class="notes-label">Notes sur le crédit</div>
        <div>{{ $credit->notes }}</div>
    </div>
    @endif

    {{-- Footer --}}
    <div class="footer">
        @if($company['invoice_signature'])
        <div class="signature-section">
            <div class="signature-text">{{ $company['invoice_signature'] }}</div>
        </div>
        @endif
        
        <div class="company-footer">
            <div class="company-footer-name">{{ $company['name'] }}</div>
            <div class="footer-contacts">
                @if($company['address'])
                    {{ $company['address'] }}
                @endif
                @if($company['phone'])
                    <br>Tél: {{ $company['phone'] }}
                @endif
                @if($company['email'])
                    <br>Email: {{ $company['email'] }}
                @endif
            </div>
            <div class="footer-meta">
                Créé par: {{ $credit->sale->user->name ?? 'Système' }} • 
                Document généré le {{ now()->format('d/m/Y à H:i') }}
            </div>
        </div>
    </div>
</body>
</html>