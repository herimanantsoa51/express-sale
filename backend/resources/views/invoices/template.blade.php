{{-- resources/views/invoices/template.blade.php --}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Facture {{ $sale->sale_number }}</title>
    <style>
        @page {
            margin: 15mm;
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10pt;
            color: #1f2937;
            line-height: 1.5;
        }
        
        /* Header & Footer */
        .header {
            border-bottom: 3px solid #2563eb;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }
        .company-name {
            font-size: 20pt;
            font-weight: bold;
            color: #2563eb;
            margin-bottom: 8px;
        }
        .company-detail {
            font-size: 9pt;
            color: #4b5563;
            margin: 3px 0;
        }
        .document-title {
            font-size: 18pt;
            font-weight: bold;
            color: #1f2937;
            margin-bottom: 5px;
        }
        .document-number {
            font-size: 14pt;
            font-weight: bold;
            color: #2563eb;
            margin-bottom: 5px;
        }
        .document-date {
            font-size: 10pt;
            color: #6b7280;
        }
        
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 15px 0;
            border-top: 2px solid #e5e7eb;
            background: white;
        }
        
        /* Badges */
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 8pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .badge-paid {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #10b981;
        }
        .badge-partial {
            background: #fef3c7;
            color: #92400e;
            border: 1px solid #f59e0b;
        }
        .badge-pending {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #ef4444;
        }
        
        /* Section Client */
        .customer-section {
            background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
            border-left: 4px solid #2563eb;
            padding: 15px;
            margin: 20px 0;
            border-radius: 6px;
        }
        .customer-title {
            font-weight: bold;
            font-size: 11pt;
            color: #2563eb;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .customer-info {
            font-size: 9pt;
            line-height: 1.6;
        }
        
        /* Table */
        table.items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        table.items-table thead {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            color: white;
        }
        table.items-table th {
            padding: 12px 10px;
            text-align: left;
            font-weight: bold;
            font-size: 9pt;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        table.items-table td {
            padding: 10px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 9pt;
        }
        table.items-table tbody tr:nth-child(even) {
            background: #f9fafb;
        }
        table.items-table tbody tr:hover {
            background: #f3f4f6;
        }
        .product-name {
            font-weight: bold;
            color: #1f2937;
            margin-bottom: 3px;
        }
        .product-attributes {
            font-size: 8pt;
            color: #6b7280;
            font-style: italic;
        }
        .sku {
            font-size: 8pt;
            color: #9ca3af;
            font-family: 'Courier New', monospace;
        }
        
        /* Totaux */
        .totals-section {
            margin-top: 25px;
            float: right;
            width: 300px;
            background: #f9fafb;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            padding: 15px;
        }
        .totals-row {
            display: table;
            width: 100%;
            margin: 8px 0;
        }
        .totals-row .label {
            display: table-cell;
            text-align: left;
            font-size: 10pt;
        }
        .totals-row .value {
            display: table-cell;
            text-align: right;
            font-size: 10pt;
        }
        .totals-row.discount {
            color: #dc2626;
        }
        .totals-row.total {
            font-size: 13pt;
            font-weight: bold;
            border-top: 2px solid #2563eb;
            margin-top: 12px;
            padding-top: 12px;
            color: #2563eb;
        }
        .discount-reason {
            font-size: 8pt;
            color: #6b7280;
            font-style: italic;
            margin-top: -5px;
            text-align: right;
        }
        
        /* Payment Info */
        .payment-box {
            background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
            border: 2px solid #10b981;
            border-radius: 8px;
            padding: 15px;
            margin-top: 25px;
            clear: both;
        }
        .payment-box.partial {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            border-color: #f59e0b;
        }
        .payment-method {
            font-weight: bold;
            color: #065f46;
            margin-bottom: 8px;
        }
        .payment-box.partial .payment-method {
            color: #92400e;
        }
        
        /* Notes */
        .notes-box {
            background: #fffbeb;
            border-left: 4px solid #f59e0b;
            padding: 12px;
            margin-top: 15px;
            border-radius: 4px;
        }
        
        /* Utilities */
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .clearfix::after {
            content: "";
            display: table;
            clear: both;
        }
    </style>
</head>
<body>
    {{-- Header --}}
    @include('invoices.partials.header', [
        'documentTitle' => 'FACTURE',
        'documentNumber' => $sale->sale_number,
        'documentDate' => 'Date: ' . $sale->sale_date->format('d/m/Y à H:i'),
        'documentStatus' => '<span class="badge badge-' . $sale->payment_status->value . '">' . strtoupper($sale->payment_status->value) . '</span>'
    ])

    {{-- Section Client --}}
    <div class="customer-section">
        <div class="customer-title">Informations Client</div>
        <div class="customer-info">
            @if($sale->customer)
                <div><strong style="font-size: 11pt;">{{ $sale->customer->name }}</strong></div>
                <div style="margin-top: 5px;">N° Client: <strong>{{ $sale->customer->customer_number }}</strong></div>
                @if($sale->customer->phone)
                    <div>Téléphone: {{ $sale->customer->phone }}</div>
                @endif
                @if($sale->customer->address)
                    <div>Adresse: {{ $sale->customer->address }}</div>
                @endif
            @else
                <div><strong style="font-size: 11pt;">Vente au comptant</strong></div>
                <div style="color: #6b7280; margin-top: 3px;">Client non enregistré</div>
            @endif
            <div style="margin-top: 8px; padding-top: 8px; border-top: 1px solid #d1d5db;">
                <strong>Type de vente:</strong> 
                <span style="text-transform: uppercase; color: #2563eb;">{{ ucfirst($sale->sale_type->value) }}</span>
            </div>
        </div>
    </div>

    {{-- Articles --}}
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 12%;">SKU</th>
                <th style="width: 40%;">Produit</th>
                <th class="text-center" style="width: 12%;">Quantité</th>
                <th class="text-right" style="width: 18%;">Prix Unit.</th>
                <th class="text-right" style="width: 18%;">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sale->items as $item)
            <tr>
                <td class="sku">{{ $item->variant->sku }}</td>
                <td>
                    <div class="product-name">{{ $item->variant->product->name }}</div>
                    @php
                        $attributes = $item->variant->attributeTypeValueMap();
                    @endphp
                    @if(!empty($attributes))
                        <div class="product-attributes">
                            {{ collect($attributes)->map(fn($v, $k) => "$k: $v")->implode(' • ') }}
                        </div>
                    @endif
                </td>
                <td class="text-center">
                    <strong>{{ $item->quantity }}</strong>
                </td>
                <td class="text-right">{{ number_format($item->unit_price, 0, ',', ' ') }} Ar</td>
                <td class="text-right">
                    <strong style="color: #2563eb;">{{ number_format($item->subtotal, 0, ',', ' ') }} Ar</strong>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Totaux --}}
    <div class="totals-section">
        <div class="totals-row">
            <span class="label">Sous-total:</span>
            <span class="value">{{ number_format($sale->subtotal, 0, ',', ' ') }} Ar</span>
        </div>
        
        @if($sale->discount_amount > 0)
        <div class="totals-row discount">
            <span class="label">Remise:</span>
            <span class="value">- {{ number_format($sale->discount_amount, 0, ',', ' ') }} Ar</span>
        </div>
        @if($sale->discount_reason)
        <div class="discount-reason">
            {{ $sale->discount_reason }}
        </div>
        @endif
        @endif
        
        <div class="totals-row total">
            <span class="label">TOTAL:</span>
            <span class="value">{{ number_format($sale->total_amount, 0, ',', ' ') }} Ar</span>
        </div>
    </div>

    <div class="clearfix"></div>

    {{-- Informations de paiement --}}
    @if($sale->payment_method)
    <div class="payment-box {{ $sale->payment_status === \App\Enums\PaymentStatus::PARTIAL ? 'partial' : '' }}">
        <div class="payment-method">
            Mode de paiement: 
            @switch($sale->payment_method->value)
                @case('cash') Espèces @break
                @case('mobile_money') Mobile Money @break
                @case('bank_transfer') Virement bancaire @break
                @case('mixed') Paiement mixte @break
            @endswitch
        </div>
        
        @if($sale->payment_status === \App\Enums\PaymentStatus::PAID)
            <div style="color: #065f46; font-size: 10pt;">
                ✓ Paiement reçu intégralement
            </div>
        @elseif($sale->payment_status === \App\Enums\PaymentStatus::PARTIAL)
            <div style="color: #92400e; font-size: 9pt; line-height: 1.6;">
                <div>Acompte versé: <strong>{{ number_format($sale->getPaidAmount(), 0, ',', ' ') }} Ar</strong></div>
                <div>Reste à payer: <strong>{{ number_format($sale->getRemainingAmount(), 0, ',', ' ') }} Ar</strong></div>
            </div>
        @endif
    </div>
    @endif

    {{-- Notes --}}
    @if($sale->notes)
    <div class="notes-box">
        <strong>Notes:</strong> {{ $sale->notes }}
    </div>
    @endif

    {{-- Footer --}}
    @include('invoices.partials.footer', [
        'userName' => $sale->user->name
    ])
</body>
</html>