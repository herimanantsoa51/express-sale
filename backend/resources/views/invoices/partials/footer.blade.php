{{-- resources/views/invoices/partials/footer.blade.php --}}
<div class="footer">
    <div style="text-align: center; margin-bottom: 8px;">
        <strong>Merci pour votre confiance !</strong>
    </div>
    
    <table style="width: 100%; font-size: 8pt; color: #6b7280;">
        <tr>
            <td style="width: 33%; text-align: left;">
                @if(isset($userName))
                    <strong>Vendeur:</strong> {{ $userName }}
                @endif
            </td>
            <td style="width: 34%; text-align: center;">
                <strong>{{ $company['name'] }}</strong>
            </td>
            <td style="width: 33%; text-align: right;">
                Document généré le {{ now()->format('d/m/Y à H:i') }}
            </td>
        </tr>
        @if($company['phone'] || $company['email'])
        <tr>
            <td colspan="3" style="text-align: center; padding-top: 5px;">
                @if($company['phone'])
                    Tel: {{ $company['phone'] }}
                @endif
                @if($company['phone'] && $company['email'])
                    |
                @endif
                @if($company['email'])
                    Email: {{ $company['email'] }}
                @endif
            </td>
        </tr>
        @endif
    </table>
</div>