<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CompanyInfoController extends Controller
{
    public function index()
    {
        $companyInfo = \App\Models\CompanyInfo::first();

        return response()->json($companyInfo);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|string|max:255',
            'logo_path' => 'nullable|string|max:255',
            'invoice_signature' => 'nullable|string|max:1000',
            
            // Paramètres imprimante
            'printer_path' => 'nullable|string|max:255',
            'auto_print_immediate_sale' => 'nullable|boolean',
            'auto_print_credit' => 'nullable|boolean',
            'auto_print_credit_payment' => 'nullable|boolean',
            'auto_print_reservation' => 'nullable|boolean',
            'auto_print_reservation_complete' => 'nullable|boolean',
            'auto_print_cash_count' => 'nullable|boolean',
        ]);

        \App\Models\CompanyInfo::query()->update($data);

        return response()->json(\App\Models\CompanyInfo::first());
    }
}