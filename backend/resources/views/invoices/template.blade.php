{{-- resources/views/invoices/template.blade.php --}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Facture {{ $sale->sale_number }}</title>
    <style>
        @page {
            margin: 20mm;
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10pt;
            color: #333;
            line-height: 1.4;
        }
        .container {
            width: 100%;
        }
        
        /* En-tête */
        .header {
            border-bottom: 3px solid #2563eb;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .header-row {
            width: 100%;
        }
        .company-info {
            width: 50%;
            float: left;
        }
        .invoice-info {
            width: 50%;
            float: right;
            text-align: right;
        }
        .company-name {
            font-size: 18pt;
            font-weight: bold;
            color: #2563eb;
            margin-bottom: 5px;
        }
        .invoice-number {
            font-size: 16pt;
            font-weight: bold;
            margin-bottom: 8px;
        }
        .clearfix::after {
            content: "";
            display: table;
            clear: both;
        }
        
        /* Section client */
        .customer-section {
            margin: 20px 0;
            padding: 12px;
            background: #f3f4f6;
            border-radius: 4px;
        }
        .customer-title {
            font-weight: bold;
            margin-bottom: 8px;
            font-size: 11pt;
        }
        .customer-section div {
            margin: 3px 0;
        }
        
        /* Badge */
        .badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 10px;
            font-size: 8pt;
            font-weight: bold;
        }
        .badge-paid {
            background: #d1fae5;
            color: #065f46;
        }
        .badge-partial {
            background: #fef3c7;
            color: #92400e;
        }
        .badge-pending {
            background: #fee2e2;
            color: #991b1b;
        }
        
        /* Table */
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        thead {
            background: #2563eb;
            color: white;
        }
        th {
            padding: 10px;
            text-align: left;
            font-weight: bold;
            font-size: 9pt;
        }
        td {
            padding: 8px 10px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 9pt;
        }
        tbody tr:nth-child(even) {
            background: #f9fafb;
        }
        
        /* Totaux */
        .totals {
            margin-top: 15px;
            float: right;
            width: 250px;
        }
        .totals-row {
            padding: 6px 0;
            overflow: hidden;
        }
        .totals-row span:first-child {
            float: left;
        }
        .totals-row span:last-child {
            float: right;
        }
        .totals-row.total {
            font-size: 12pt;
            font-weight: bold;
            border-top: 2px solid #333;
            margin-top: 8px;
            padding-top: 8px;
        }
        
        /* Paiement */
        .payment-info {
            margin-top: 30px;
            padding: 12px;
            background: #ecfdf5;
            border-left: 4px solid #10b981;
            clear: both;
        }
        
        /* Notes */
        .notes {
            margin-top: 15px;
            padding: 10px;
            background: #fef3c7;
            border-radius: 4px;
        }
        
        /* Footer */
        .footer {
            margin-top: 40px;
            padding-top: 15px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            font-size: 8pt;
            color: #6b7280;
        }
        
        /* Helpers */
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .text-small { font-size: 8pt; color: #6b7280; }
        .mt-1 { margin-top: 5px; }
    </style>
</head>
<body>
    <div class="container">
        {{-- En-tête --}}
        <div class="header clearfix">
            <div class="company-info">
                <div class="company-name">{{ $company['name'] }}</div>
                @if($company['address'])
                    <div>{{ $company['address'] }}</div>
                @endif
                @if($company['phone'])
                    <div>Tél: {{ $company['phone'] }}</div>
                @endif
                @if($company['email'])
                    <div>Email: {{ $company['email'] }}</div>
                @endif
            </div>
            
            <div class="invoice-info">
                <div class="invoice-number">FACTURE</div>
                <div style="font-size: 12pt; margin-bottom: 5px;">{{ $sale->sale_number }}</div>
                <div>Date: {{ $sale->sale_date->format('d/m/Y H:i') }}</div>
                <div class="mt-1">
                    <span class="badge badge-{{ $sale->payment_status->value }}">
                        {{ strtoupper($sale->payment_status->value) }}
                    </span>
                </div>
            </div>
        </div>

        {{-- Informations client --}}
        <div class="customer-section">
            <div class="customer-title">CLIENT</div>
            @if($sale->customer)
                <div><strong>{{ $sale->customer->name }}</strong></div>
                <div>N° Client: {{ $sale->customer->customer_number }}</div>
                @if($sale->customer->phone)
                    <div>Tél: {{ $sale->customer->phone }}</div>
                @endif
                @if($sale->customer->address)
                    <div>Adresse: {{ $sale->customer->address }}</div>
                @endif
            @else
                <div><strong>Vente au comptant</strong></div>
            @endif
            <div class="mt-1">
                <strong>Type de vente:</strong> {{ ucfirst($sale->sale_type->value) }}
            </div>
        </div>

        {{-- Articles --}}
        <table>
            <thead>
                <tr>
                    <th style="width: 12%;">SKU</th>
                    <th style="width: 40%;">Produit</th>
                    <th class="text-center" style="width: 12%;">Qté</th>
                    <th class="text-right" style="width: 18%;">Prix Unit.</th>
                    <th class="text-right" style="width: 18%;">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($sale->items as $item)
                <tr>
                    <td class="text-small">{{ $item->variant->sku }}</td>
                    <td><strong>{{ $item->variant->product->name }}</strong></td>
                    <td class="text-center">{{ $item->quantity }}</td>
                    <td class="text-right">{{ number_format($item->unit_price, 0, ',', ' ') }} Ar</td>
                    <td class="text-right"><strong>{{ number_format($item->subtotal, 0, ',', ' ') }} Ar</strong></td>
                </tr>
                @endforeach
            </tbody>
        </table>

        {{-- Totaux --}}
        <div class="totals">
            <div class="totals-row">
                <span>Sous-total:</span>
                <span>{{ number_format($sale->subtotal, 0, ',', ' ') }} Ar</span>
            </div>
            @if($sale->discount_amount > 0)
            <div class="totals-row" style="color: #dc2626;">
                <span>Remise</span>
                <span>- {{ number_format($sale->discount_amount, 0, ',', ' ') }} Ar</span>
            </div>
            @if($sale->discount_reason)
            <div class="text-small" style="margin-top: -3px;">
                ({{ $sale->discount_reason }})
            </div>
            @endif
            @endif
            <div class="totals-row total">
                <span>TOTAL:</span>
                <span>{{ number_format($sale->total_amount, 0, ',', ' ') }} Ar</span>
            </div>
        </div>

        <div class="clearfix"></div>

        {{-- Informations de paiement --}}
        @if($sale->payment_method)
        <div class="payment-info">
            <strong>Mode de paiement:</strong> 
            @switch($sale->payment_method->value)
                @case('cash') Espèces @break
                @case('mobile_money') Mobile Money @break
                @case('bank_transfer') Virement bancaire @break
                @case('mixed') Paiement mixte @break
            @endswitch
            
            @if($sale->payment_status === \App\Enums\PaymentStatus::PAID)
                <div class="mt-1" style="color: #065f46;">
                    ✓ Paiement reçu intégralement
                </div>
            @elseif($sale->payment_status === \App\Enums\PaymentStatus::PARTIAL)
                <div class="mt-1" style="color: #92400e;">
                    Acompte versé: {{ number_format($sale->getPaidAmount(), 0, ',', ' ') }} Ar<br>
                    Reste à payer: {{ number_format($sale->getRemainingAmount(), 0, ',', ' ') }} Ar
                </div>
            @endif
        </div>
        @endif

        {{-- Notes --}}
        @if($sale->notes)
        <div class="notes">
            <strong>Notes:</strong> {{ $sale->notes }}
        </div>
        @endif

        {{-- Pied de page --}}
        <div class="footer">
            <p>Merci pour votre confiance !</p>
            <p class="mt-1">Vendeur: {{ $sale->user->name }}</p>
            <p class="mt-1">
                Document généré le {{ now()->format('d/m/Y à H:i') }}
            </p>
        </div>
    </div>
</body>
</html>