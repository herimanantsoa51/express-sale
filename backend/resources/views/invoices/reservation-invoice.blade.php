{{-- resources/views/invoices/reservation-invoice.blade.php --}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Facture Réservation {{ $reservation->sale->sale_number }}</title>
    <style>
        /* CSS OPTIMISÉ POUR COMPACITÉ */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'DejaVu Sans', sans-serif; 
            font-size: 8pt; 
            color: #1d1d1f; 
            line-height: 1.3;
            background: #fff;
        }
        
        .header { 
            padding: 15px 0 10px 0;
            margin-bottom: 15px;
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
            font-size: 16pt;
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
            font-size: 9pt;
            font-weight: 600;
            color: #1d1d1f;
            letter-spacing: -0.3px;
            margin-bottom: 4px;
        }
        .invoice-number {
            font-size: 16pt;
            font-weight: 700;
            color: #1d1d1f;
            letter-spacing: -0.5px;
            margin-bottom: 3px;
        }
        .invoice-date {
            font-size: 8pt;
            color: #86868b;
            margin-bottom: 5px;
        }
        
        .status-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 7pt;
            font-weight: 600;
            letter-spacing: 0.3px;
        }
        .status-badge.pending { background: #fef9c3; color: #a16207; }
        .status-badge.confirmed { background: #dbeafe; color: #1e40af; }
        .status-badge.completed { background: #f0fdf4; color: #15803d; }
        .status-badge.cancelled { background: #fee2e2; color: #b91c1c; }
        .status-badge.expired { background: #f3f4f6; color: #374151; }
        
        .customer-section {
            background: #f5f5f7;
            padding: 12px 15px;
            margin: 15px 0;
            border-radius: 8px;
        }
        .customer-label {
            font-size: 7pt;
            font-weight: 600;
            color: #86868b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }
        .customer-name {
            font-size: 10pt;
            font-weight: 600;
            color: #1d1d1f;
            margin-bottom: 2px;
        }
        .customer-info {
            font-size: 8pt;
            color: #515154;
            line-height: 1.4;
        }
        .reservation-info {
            margin-top: 8px;
            padding-top: 8px;
            border-top: 1px solid #d2d2d7;
            font-size: 8pt;
        }
        .reservation-label {
            color: #86868b;
            font-weight: 500;
        }
        .reservation-value {
            color: #1d1d1f;
            font-weight: 600;
        }
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        .items-table thead {
            border-bottom: 1px solid #d2d2d7;
        }
        .items-table th {
            padding: 6px 5px;
            font-size: 7pt;
            font-weight: 600;
            color: #86868b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            text-align: left;
        }
        .items-table th.text-center { text-align: center; }
        .items-table th.text-right { text-align: right; }
        .items-table td {
            padding: 8px 5px;
            font-size: 8pt;
            border-bottom: 1px solid #f5f5f7;
            vertical-align: top;
        }
        .items-table td.text-center { text-align: center; }
        .items-table td.text-right { text-align: right; }
        .items-table tbody tr:last-child td { border-bottom: none; }
        
        .product-sku {
            font-family: 'SF Mono', 'Courier New', monospace;
            font-size: 7pt;
            color: #86868b;
        }
        .product-name {
            font-weight: 600;
            color: #1d1d1f;
            margin-bottom: 1px;
        }
        .product-attributes {
            font-size: 7pt;
            color: #86868b;
            margin-top: 1px;
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
            width: 240px;
            margin-top: 10px;
        }
        .totals-row {
            display: table;
            width: 100%;
            padding: 5px 0;
        }
        .totals-label {
            display: table-cell;
            font-size: 8pt;
            color: #515154;
        }
        .totals-value {
            display: table-cell;
            text-align: right;
            font-size: 8pt;
            color: #1d1d1f;
        }
        .totals-row.total {
            border-top: 1px solid #d2d2d7;
            margin-top: 5px;
            padding-top: 8px;
        }
        .totals-row.total .totals-label {
            font-size: 9pt;
            font-weight: 600;
            color: #1d1d1f;
        }
        .totals-row.total .totals-value {
            font-size: 11pt;
            font-weight: 700;
            color: #1d1d1f;
        }
        
        /* Section Réservation */
        .reservation-section {
            clear: both;
            background: #eff6ff;
            padding: 12px 15px;
            margin: 15px 0;
            border-radius: 8px;
            border-left: 3px solid #3b82f6;
        }
        .reservation-section-title {
            font-weight: 600;
            color: #1d1d1f;
            margin-bottom: 8px;
            font-size: 9pt;
        }
        .reservation-details-grid {
            display: table;
            width: 100%;
        }
        .reservation-detail-row {
            display: table-row;
        }
        .reservation-detail-label {
            display: table-cell;
            padding: 4px 0;
            font-size: 8pt;
            color: #515154;
            width: 40%;
        }
        .reservation-detail-value {
            display: table-cell;
            padding: 4px 0;
            font-size: 8pt;
            font-weight: 600;
            color: #1d1d1f;
            text-align: right;
        }
        .expiry-warning {
            margin-top: 8px;
            padding-top: 8px;
            border-top: 1px solid #bfdbfe;
            font-size: 7pt;
            color: #1e40af;
            font-style: italic;
        }
        
        .notes-section {
            background: #fffbeb;
            padding: 12px 15px;
            margin: 15px 0;
            border-radius: 8px;
            font-size: 8pt;
            color: #515154;
        }
        .notes-label {
            font-weight: 600;
            color: #1d1d1f;
            margin-bottom: 3px;
        }
        
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #d2d2d7;
        }
        .signature-section {
            text-align: center;
            margin-bottom: 12px;
        }
        .signature-text {
            font-size: 8pt;
            color: #515154;
            line-height: 1.4;
            font-style: italic;
        }
        .company-footer {
            background: #f5f5f7;
            padding: 12px 15px;
            border-radius: 8px;
            font-size: 7pt;
            color: #86868b;
            line-height: 1.6;
        }
        .company-footer-name {
            font-weight: 600;
            color: #1d1d1f;
            font-size: 8pt;
            margin-bottom: 4px;
        }
        .footer-contacts {
            margin-top: 3px;
        }
        .footer-meta {
            margin-top: 8px;
            padding-top: 8px;
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
                <div class="invoice-type">FACTURE DE RÉSERVATION</div>
                <div class="invoice-number">{{ $reservation->sale->sale_number }}</div>
                <div class="invoice-date">Réservation du {{ $reservation->reservation_date->format('d/m/Y') }}</div>
                <div>
                    <span class="status-badge {{ $reservation->status }}">
                        {{ strtoupper(str_replace('_', ' ', $reservation->status)) }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    {{-- Client --}}
    <div class="customer-section">
        <div class="customer-label">Client</div>
        <div class="customer-name">{{ $reservation->customer->name }}</div>
        <div class="customer-info">
            N° Client: {{ $reservation->customer->customer_number }}
            @if($reservation->customer->phone)
                <br>Téléphone: {{ $reservation->customer->phone }}
            @endif
            @if($reservation->customer->address)
                <br>{{ $reservation->customer->address }}
            @endif
        </div>
        <div class="reservation-info">
            <span class="reservation-label">Type de vente:</span>
            <span class="reservation-value">RÉSERVATION</span>
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
            @foreach($reservation->sale->items as $item)
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
        @if($reservation->sale->discount_amount > 0)
        <div class="totals-row">
            <span class="totals-label">Sous-total</span>
            <span class="totals-value">{{ number_format($reservation->sale->subtotal, 0, ',', ' ') }} Ar</span>
        </div>
        <div class="totals-row">
            <span class="totals-label">Remise</span>
            <span class="totals-value">-{{ number_format($reservation->sale->discount_amount, 0, ',', ' ') }} Ar</span>
        </div>
        @endif
        
        <div class="totals-row total">
            <span class="totals-label">Montant total</span>
            <span class="totals-value">{{ number_format($reservation->sale->total_amount, 0, ',', ' ') }} Ar</span>
        </div>
        
        <div class="totals-row">
            <span class="totals-label">Acompte versé</span>
            <span class="totals-value">{{ number_format($deposit_amount, 0, ',', ' ') }} Ar</span>
        </div>
        
        <div class="totals-row total">
            <span class="totals-label">Reste à payer</span>
            <span class="totals-value">{{ number_format($remaining_amount, 0, ',', ' ') }} Ar</span>
        </div>
    </div>

    <div class="clearfix"></div>

    {{-- Détails Réservation --}}
    <div class="reservation-section">
        <div class="reservation-section-title">INFORMATIONS DE RÉSERVATION</div>
        <div class="reservation-details-grid">
            <div class="reservation-detail-row">
                <div class="reservation-detail-label">Date de réservation:</div>
                <div class="reservation-detail-value">{{ $reservation->reservation_date->format('d/m/Y') }}</div>
            </div>
            @if($expiry_date)
            <div class="reservation-detail-row">
                <div class="reservation-detail-label">Date d'expiration:</div>
                <div class="reservation-detail-value">{{ $expiry_date->format('d/m/Y') }}</div>
            </div>
            @endif
            <div class="reservation-detail-row">
                <div class="reservation-detail-label">Acompte versé:</div>
                <div class="reservation-detail-value">{{ number_format($deposit_amount, 0, ',', ' ') }} Ar</div>
            </div>
            <div class="reservation-detail-row">
                <div class="reservation-detail-label">Montant restant:</div>
                <div class="reservation-detail-value">{{ number_format($remaining_amount, 0, ',', ' ') }} Ar</div>
            </div>
            @if($reservation->sale->payment_method)
            <div class="reservation-detail-row">
                <div class="reservation-detail-label">Mode de paiement:</div>
                <div class="reservation-detail-value">{{ strtoupper($reservation->sale->payment_method->value) }}</div>
            </div>
            @endif
        </div>
        @if($expiry_date)
        <div class="expiry-warning">
            ⚠ Cette réservation expire le {{ $expiry_date->format('d/m/Y') }}. Veuillez finaliser votre achat avant cette date.
        </div>
        @endif
    </div>

    {{-- Notes --}}
    @if($reservation->notes)
    <div class="notes-section">
        <div class="notes-label">Notes sur la réservation</div>
        <div>{{ $reservation->notes }}</div>
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
                Créé par: {{ $reservation->sale->user->name ?? 'Système' }} • 
                Document généré le {{ now()->format('d/m/Y à H:i') }}
            </div>
        </div>
    </div>
</body>
</html>