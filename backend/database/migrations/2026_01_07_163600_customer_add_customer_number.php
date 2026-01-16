<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Customer;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // Ajouter la colonne customer_number
        Schema::table('customers', function (Blueprint $table) {
            $table->string('customer_number')->unique()->nullable()->after('id');
        });

        // Générer des numéros pour les clients existants
        $this->generateCustomerNumbersForExisting();
    }

    public function down()
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('customer_number');
        });
    }

    /**
     * Génère des numéros de client pour les clients existants
     */
    private function generateCustomerNumbersForExisting(): void
    {
        $customers = Customer::orderBy('created_at')->get();
        
        // Grouper par date de création
        $groupedByDate = [];
        foreach ($customers as $customer) {
            $dateKey = $customer->created_at->format('Ymd');
            if (!isset($groupedByDate[$dateKey])) {
                $groupedByDate[$dateKey] = [];
            }
            $groupedByDate[$dateKey][] = $customer;
        }
        
        // Générer les numéros pour chaque groupe de date
        foreach ($groupedByDate as $dateKey => $dateCustomers) {
            $counter = 1;
            foreach ($dateCustomers as $customer) {
                $customerNumber = 'CL-' . $dateKey . '-' . str_pad($counter, 4, '0', STR_PAD_LEFT);
                
                // Vérifier si le numéro existe déjà (au cas où)
                while (Customer::where('customer_number', $customerNumber)->exists()) {
                    $counter++;
                    $customerNumber = 'CL-' . $dateKey . '-' . str_pad($counter, 4, '0', STR_PAD_LEFT);
                }
                
                $customer->customer_number = $customerNumber;
                $customer->save();
                
                $counter++;
            }
        }
    }
};