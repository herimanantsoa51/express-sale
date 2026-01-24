<?php

namespace App\Observers;

use App\Models\CompanyInfo;
use Illuminate\Support\Facades\Cache;

class CompanyInfoObserver
{
    /**
     * Vider le cache après création
     */
    public function created(CompanyInfo $companyInfo): void
    {
        $this->clearCache();
    }

    /**
     * Vider le cache après modification
     */
    public function updated(CompanyInfo $companyInfo): void
    {
        $this->clearCache();
    }

    /**
     * Vider le cache après suppression
     */
    public function deleted(CompanyInfo $companyInfo): void
    {
        $this->clearCache();
    }

    /**
     * Vider tous les caches liés aux infos entreprise
     */
    private function clearCache(): void
    {
        Cache::forget('company_info_invoice');
        
        // Si vous avez d'autres caches liés à CompanyInfo
        // Cache::forget('company_info_receipt');
        // Cache::forget('company_info_credit');
    }
}