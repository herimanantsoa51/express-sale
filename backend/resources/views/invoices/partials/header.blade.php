{{-- resources/views/invoices/partials/header.blade.php --}}
<div class="header">
    <table style="width: 100%; border-collapse: collapse;">
        <tr>
            <td style="width: 60%; vertical-align: top;">
                @if($company['logo_path'])
                    <img src="{{ $company['logo_path'] }}" alt="Logo" style="max-width: 150px; max-height: 80px; margin-bottom: 10px;">
                @endif
                <div class="company-name">{{ $company['name'] }}</div>
                @if($company['address'])
                    <div class="company-detail">
                        <svg style="width: 12px; height: 12px; display: inline-block; vertical-align: middle;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                            <circle cx="12" cy="10" r="3"></circle>
                        </svg>
                        {{ $company['address'] }}
                    </div>
                @endif
                @if($company['phone'])
                    <div class="company-detail">
                        <svg style="width: 12px; height: 12px; display: inline-block; vertical-align: middle;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                        </svg>
                        {{ $company['phone'] }}
                    </div>
                @endif
                @if($company['email'])
                    <div class="company-detail">
                        <svg style="width: 12px; height: 12px; display: inline-block; vertical-align: middle;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                            <polyline points="22,6 12,13 2,6"></polyline>
                        </svg>
                        {{ $company['email'] }}
                    </div>
                @endif
            </td>
            <td style="width: 40%; vertical-align: top; text-align: right;">
                <div class="document-title">{{ $documentTitle ?? 'FACTURE' }}</div>
                <div class="document-number">{{ $documentNumber }}</div>
                <div class="document-date">{{ $documentDate }}</div>
                @if(isset($documentStatus))
                    <div style="margin-top: 8px;">
                        {!! $documentStatus !!}
                    </div>
                @endif
            </td>
        </tr>
    </table>
</div>