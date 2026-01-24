<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        @page { 
            size: 80mm 200mm; 
            margin: 2mm;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Courier New', monospace;
            font-size: 10pt;
            width: 72mm; /* 80mm - marges */
            margin: 0 auto;
        }
        .center { text-align: center; }
        .bold { font-weight: bold; }
        .line { border-bottom: 1px dashed #000; margin: 5px 0; }
        .row { display: flex; justify-content: space-between; margin: 2px 0; }
        .company { font-size: 12pt; font-weight: bold; }
        .total { font-size: 14pt; font-weight: bold; border-top: 2px solid #000; padding-top: 5px; }
    </style>
</head>
<body> 
    <div class="center">
        <div class="company">{{ $company['name'] }}</div>
        @if($company['phone'])<div>{{ $company['phone'] }}</div>@endif
        <div class="line"></div>
    </div>

    <div class="center bold">REÇU DE FINALISATION</div>
    <div class="center">{{ $payment->transactionComplete->reference_number ?? 'REC-' . str_pad($payment->id, 6, '0', STR_PAD_LEFT) }}</div>
    <div class="center">{{ $payment->completed_at->format('d/m/Y H:i') }}</div>
    
    <div class="line"></div>

    <div><strong>Client:</strong> {{ $payment->customer->name }}</div>
    <div>N° {{ $payment->customer->customer_number }}</div>
    
    <div class="line"></div>

    <div><strong>N° Vente:</strong> {{ $payment->sale->sale_number }}</div>
    <div><strong>Date:</strong> {{ $payment->reservation_date->format('d/m/Y') }}</div>
    
    <div class="line"></div>

    <div class="row">
        <span>Montant total</span>
        <span>{{ number_format($payment->total_amount, 0, ',', ' ') }} Ar</span>
    </div>
    <div class="row">
        <span>Acompte versé</span>
        <span>{{ number_format($payment->deposit_amount, 0, ',', ' ') }} Ar</span>
    </div>
    @if($payment->transactionComplete)
    <div class="row">
        <span>Paiement final</span>
        <span>{{ number_format($payment->transactionComplete->amount, 0, ',', ' ') }} Ar</span>
    </div>
    @endif
    
    <div class="line"></div>
    
    <div class="row total">
        <span>PAYÉ</span>
        <span>{{ number_format($payment->total_amount, 0, ',', ' ') }} Ar</span>
    </div>

    <div class="line"></div>
    
    <div class="center" style="margin-top: 10px;">
        <div>Merci pour votre confiance!</div>
        @if($company['invoice_signature'])
        <div style="margin-top: 5px; font-style: italic;">{{ $company['invoice_signature'] }}</div>
        @endif
    </div>
</body>
</html>