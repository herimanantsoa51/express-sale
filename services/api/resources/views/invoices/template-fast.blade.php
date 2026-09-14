{{-- resources/views/invoices/template-fast.blade.php --}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Facture {{ $sale->sale_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'DejaVu Sans', sans-serif; 
            font-size: 9pt; 
            color: #1d1d1f; 
            line-height: 1.5;
            background: #fff;
        }
        
        /* Header minimaliste Apple */
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
        
        /* Badge Apple style */
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 8pt;
            font-weight: 600;
            letter-spacing: 0.3px;
        }
        .status-badge.paid {
            background: #f0fdf4;
            color: #15803d;
        }
        .status-badge.partial {
            background: #fef9c3;
            color: #a16207;
        }
        .status-badge.pending {
            background: #fef2f2;
            color: #b91c1c;
        }
        
        /* Section Client - Style Apple */
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
        .sale-type {
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px solid #d2d2d7;
            font-size: 9pt;
        }
        .sale-type-label {
            color: #86868b;
            font-weight: 500;
        }
        .sale-type-value {
            color: #1d1d1f;
            font-weight: 600;
        }
        
        /* Table Apple style - Alignement parfait */
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
        .items-table th.text-center {
            text-align: center;
        }
        .items-table th.text-right {
            text-align: right;
        }
        .items-table td {
            padding: 12px 8px;
            font-size: 9pt;
            border-bottom: 1px solid #f5f5f7;
            vertical-align: top;
        }
        .items-table td.text-center {
            text-align: center;
        }
        .items-table td.text-right {
            text-align: right;
        }
        .items-table tbody tr:last-child td {
            border-bottom: none;
        }
        
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
        
        /* Totaux - Style Apple */
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
        .totals-row.discount .totals-label,
        .totals-row.discount .totals-value {
            color: #d1001f;
        }
        .discount-reason {
            font-size: 8pt;
            color: #86868b;
            text-align: right;
            margin-top: -4px;
            font-style: italic;
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
        
        /* Payment - Style Apple */
        .payment-section {
            clear: both;
            background: #f5f5f7;
            padding: 16px 20px;
            margin: 25px 0;
            border-radius: 12px;
        }
        .payment-section.partial {
            background: #fffbeb;
        }
        .payment-method {
            font-size: 9pt;
            color: #515154;
            margin-bottom: 6px;
        }
        .payment-method-value {
            font-weight: 600;
            color: #1d1d1f;
        }
        .payment-status {
            font-size: 9pt;
            margin-top: 8px;
        }
        .payment-status.paid {
            color: #15803d;
        }
        .payment-status.partial {
            color: #a16207;
        }
        .payment-details {
            font-size: 8pt;
            color: #86868b;
            line-height: 1.6;
            margin-top: 6px;
        }
        
        /* Notes */
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
        
        /* Footer Apple style */
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
        
        /* Utilities */
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
                <div class="invoice-type">Facture de Vente Rapide</div>
                <div class="invoice-number">{{ $sale->sale_number }}</div>
                <div class="invoice-date">{{ $sale->sale_date->format('d/m/Y à H:i') }}</div>
                <div>
                    <span class="status-badge {{ $sale->payment_status->value }}">
                        {{ strtoupper($sale->payment_status->value) }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    {{-- Client --}}
    <div class="customer-section">
        <div class="customer-label">Informations Client</div>
        @if($sale->customer)
            <div class="customer-name">{{ $sale->customer->name }}</div>
            <div class="customer-info">
                N° Client: {{ $sale->customer->customer_number }}
                @if($sale->customer->phone)
                    <br>Téléphone: {{ $sale->customer->phone }}
                @endif
                @if($sale->customer->address)
                    <br>{{ $sale->customer->address }}
                @endif
            </div>
        @else
            <div class="customer-name">Vente au comptant</div>
            <div class="customer-info">Client non enregistré</div>
        @endif
        <div class="sale-type">
            <span class="sale-type-label">Type de vente:</span>
            <span class="sale-type-value">{{ ucfirst($sale->sale_type->value) }}</span>
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
            @foreach($sale->items as $item)
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
            <span class="totals-label">Sous-total</span>
            <span class="totals-value">{{ number_format($sale->subtotal, 0, ',', ' ') }} Ar</span>
        </div>
        
        @if($sale->discount_amount > 0)
        <div class="totals-row discount">
            <span class="totals-label">Remise</span>
            <span class="totals-value">- {{ number_format($sale->discount_amount, 0, ',', ' ') }} Ar</span>
        </div>
        @if($sale->discount_reason)
        <div class="discount-reason">{{ $sale->discount_reason }}</div>
        @endif
        @endif
        
        <div class="totals-row total">
            <span class="totals-label">Total</span>
            <span class="totals-value">{{ number_format($sale->total_amount, 0, ',', ' ') }} Ar</span>
        </div>
    </div>

    <div class="clearfix"></div>

    {{-- Paiement --}}
    @if($sale->payment_method)
    <div class="payment-section {{ $sale->payment_status === \App\Enums\PaymentStatus::PARTIAL ? 'partial' : '' }}">
        <div class="payment-method">
            Mode de paiement: 
            <span class="payment-method-value">
                @switch($sale->payment_method->value)
                    @case('cash') Espèces @break
                    @case('mobile_money') Mobile Money @break
                    @case('bank_transfer') Virement bancaire @break
                    @case('mixed') Paiement mixte @break
                @endswitch
            </span>
        </div>
        
        @if($sale->payment_status === \App\Enums\PaymentStatus::PAID)
            <div class="payment-status paid">✓ Paiement reçu intégralement</div>
        @elseif($sale->payment_status === \App\Enums\PaymentStatus::PARTIAL)
            <div class="payment-status partial">Paiement partiel</div>
            <div class="payment-details">
                Acompte versé: {{ number_format($sale->getPaidAmount(), 0, ',', ' ') }} Ar<br>
                Reste à payer: {{ number_format($sale->getRemainingAmount(), 0, ',', ' ') }} Ar
            </div>
        @endif
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
                Vendeur: {{ $sale->user->name }} • Document généré le {{ now()->format('d/m/Y à H:i') }}
            </div>
        </div>
    </div>
</body>
</html>