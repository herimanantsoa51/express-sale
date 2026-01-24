<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\CompanyInfo;
use Mike42\Escpos\PrintConnectors\FilePrintConnector;
use Mike42\Escpos\Printer;
use Mike42\Escpos\EscposImage;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Models\Reservation;
use App\Models\Credit;
use App\Models\InstallmentTransaction;
use App\Models\CashCount;

class PosPrintService
{
    private $printer;
    private $companyInfo;
    private $connectorPath;

    public function __construct()
    {
        $this->connectorPath = config('printing.connector_path', '/dev/usb/lp0');
        $this->companyInfo = $this->getCompanyInfo();
    }

    /**
     * Vérifier si l'impression automatique est activée pour un type donné
     */
    private function shouldAutoPrint(string $type): bool
    {
        $settingKey = 'auto_print_' . $type;
        return $this->companyInfo[$settingKey] ?? false;
    }

    /**
     * Wrapper pour impression automatique conditionnelle
     */
    public function autoPrintIfEnabled(string $type, callable $printMethod)
    {
        if (!$this->shouldAutoPrint($type)) {
            return [
                'success' => true,
                'message' => 'Impression automatique desactivee',
                'auto_print_skipped' => true
            ];
        }

        try {
            return $printMethod();
        } catch (\Exception $e) {
            Log::error("Erreur impression auto ($type): " . $e->getMessage());
            // Ne pas bloquer l'opération si l'impression échoue
            return [
                'success' => false,
                'message' => 'Erreur impression: ' . $e->getMessage(),
                'auto_print_failed' => true
            ];
        }
    }
    /**
     * Imprimer une vente - Style blade optimisé
     * ATTENTION: Ce service est conçu pour imprimante THERMIQUE (tickets 58mm/80mm)
     * Pour impression A4, utilisez plutôt InvoicePdfService->generateSale()
     */
    public function printSale(Sale $sale)
    {
        try {
            // Chargement optimisé comme dans generateSale
            $sale->loadMissing([
                'customer:id,customer_number,name,phone,address',
                'user:id,name',
                'items:id,sale_id,variant_id,quantity,unit_price,subtotal',
                'items.variant:id,sku,product_id',
                'items.variant.product:id,name',
            ]);

            // Charger les attributs uniquement si nécessaire
            $needsAttributes = $sale->items->some(function($item) {
                return $item->variant->variantAttributeValues()->exists();
            });

            if ($needsAttributes) {
                $sale->load('items.variant.variantAttributeValues.attributeValue.attributeType');
            }

            $connector = new FilePrintConnector($this->connectorPath);
            $this->printer = new Printer($connector);

            $this->printHeader($sale);
            $this->printCustomerInfo($sale);
            $this->printItems($sale);
            $this->printTotals($sale);
            // $this->printPaymentInfo($sale);
            $this->printFooter($sale);

            $this->printer->cut(Printer::CUT_PARTIAL);
            $this->printer->close();

            return [
                'success' => true,
                'message' => 'Recu imprime avec succes'
            ];

        } catch (\Exception $e) {
            Log::error('Erreur impression POS: ' . $e->getMessage());
            throw new \Exception("Erreur d'impression: " . $e->getMessage());
        }
    }

    /**
     * Imprimer une réservation
     */
    public function printReservation(Reservation $reservation)
    {
        try {
            Log::error('DEBUG printReservation()', [
                'reservation_id' => $reservation->id,
                'sale_loaded' => $reservation->relationLoaded('sale'),
                'sale_id' => $reservation->sale_id ?? null,
                'sale_is_null' => is_null($reservation->sale),
            ]);
            $reservation->loadMissing([
                'sale:id,sale_number,sale_date,subtotal,discount_amount,total_amount,payment_method',
                'sale.customer:id,customer_number,name,phone,address',
                'sale.user:id,name',
                'sale.items:id,sale_id,variant_id,quantity,unit_price,subtotal',
                'sale.items.variant:id,sku,product_id',
                'sale.items.variant.product:id,name',
                'customer:id,customer_number,name,phone,address',
            ]);
            
            
            $needsAttributes = $reservation->sale->items->some(function($item) {
                return $item->variant->variantAttributeValues()->exists();
            });

            if ($needsAttributes) {
                $reservation->load('sale.items.variant.variantAttributeValues.attributeValue.attributeType');
            }

            $connector = new FilePrintConnector($this->connectorPath);
            $this->printer = new Printer($connector);

            $this->printReservationHeader($reservation);
            $this->printReservationCustomer($reservation);
            $this->printReservationItems($reservation);
            $this->printReservationTotals($reservation);
            $this->printReservationDetails($reservation);
            $this->printReservationFooter($reservation);

            $this->printer->cut(Printer::CUT_PARTIAL);
            $this->printer->close();

            return [
                'success' => true,
                'message' => 'Recu reservation imprime avec succes'
            ];

        } catch (\Exception $e) {
            Log::error('Erreur impression reservation: ' . $e->getMessage());
            throw new \Exception("Erreur d'impression: " . $e->getMessage());
        }
    }

    /**
     * Imprimer reçu de finalisation de réservation
     */
    public function printReservationReceipt(Reservation $reservation)
    {
        try {
            $reservation->loadMissing([
                'sale:id,sale_number',
                'customer:id,customer_number,name,phone,address',
                'transactionComplete:id,account_id,amount,transaction_date,reference_number,created_by',
                'transactionComplete.account:id,account_type_id',
                'transactionComplete.account.accountType:id,display_name',
                'transactionComplete.creator:id,name',
            ]);

            $connector = new FilePrintConnector($this->connectorPath);
            $this->printer = new Printer($connector);

            $this->printReservationReceiptHeader($reservation);
            $this->printReservationReceiptClient($reservation);
            $this->printReservationReceiptPayment($reservation);
            $this->printReservationReceiptSummary($reservation);
            // $this->printReservationReceiptTotals($reservation);
            $this->printReservationReceiptFooter($reservation);

            $this->printer->cut(Printer::CUT_PARTIAL);
            $this->printer->close();

            return [
                'success' => true,
                'message' => 'Recu de finalisation imprime avec succes'
            ];

        } catch (\Exception $e) {
            Log::error('Erreur impression recu reservation: ' . $e->getMessage());
            throw new \Exception("Erreur d'impression: " . $e->getMessage());
        }
    }

    /**
     * Imprimer un crédit
     */
    public function printCredit(Credit $credit)
    {
        try {
            $credit->loadMissing([
                'sale:id,sale_number,sale_date,subtotal,discount_amount,total_amount,payment_method',
                'sale.customer:id,customer_number,name,phone,address',
                'sale.user:id,name',
                'sale.items:id,sale_id,variant_id,quantity,unit_price,subtotal',
                'sale.items.variant:id,sku,product_id',
                'sale.items.variant.product:id,name',
                'customer:id,customer_number,name,phone,address',
                'installments:id,credit_id,installment_number,due_date,amount_due,amount_paid,status',
            ]);

            $needsAttributes = $credit->sale->items->some(function($item) {
                return $item->variant->variantAttributeValues()->exists();
            });

            if ($needsAttributes) {
                $credit->load('sale.items.variant.variantAttributeValues.attributeValue.attributeType');
            }

            $connector = new FilePrintConnector($this->connectorPath);
            $this->printer = new Printer($connector);

            $this->printCreditHeader($credit);
            $this->printCreditCustomer($credit);
            $this->printCreditItems($credit);
            $this->printCreditTotals($credit);
            $this->printCreditInstallments($credit);
            $this->printCreditFooter($credit);

            $this->printer->cut(Printer::CUT_PARTIAL);
            $this->printer->close();

            return [
                'success' => true,
                'message' => 'Recu credit imprime avec succes'
            ];

        } catch (\Exception $e) {
            Log::error('Erreur impression credit: ' . $e->getMessage());
            throw new \Exception("Erreur d'impression: " . $e->getMessage());
        }
    }

    // /**
    //  * Imprimer reçu de paiement crédit
    //  */
    public function printCreditPayment(InstallmentTransaction $installmentTransaction)
    {
        try {
            $installmentTransaction->loadMissing([
                'installment:id,credit_id,installment_number,due_date,amount_due,amount_paid,status',
                'installment.credit:id,sale_id,customer_id,total_amount,amount_paid,amount_due',
                'installment.credit.sale:id,sale_number,sale_date',
                'installment.credit.sale.customer:id,customer_number,name,phone',
                'installment.credit.customer:id,customer_number,name,phone',
                'transaction:id,account_id,amount,transaction_date,created_by,reference_number',
                'transaction.creator:id,name',
                'transaction.account.accountType:id,display_name'
            ]);

            $connector = new FilePrintConnector($this->connectorPath);
            $this->printer = new Printer($connector);

            $this->printCreditPaymentHeader($installmentTransaction);
            $this->printCreditPaymentClient($installmentTransaction);
            $this->printCreditPaymentDetails($installmentTransaction);
            $this->printCreditPaymentInstallment($installmentTransaction);
            $this->printCreditPaymentFooter($installmentTransaction);

            $this->printer->cut(Printer::CUT_PARTIAL);
            $this->printer->close();

            return [
                'success' => true,
                'message' => 'Recu de paiement imprime avec succes'
            ];

        } catch (\Exception $e) {
            Log::error('Erreur impression paiement credit: ' . $e->getMessage());
            throw new \Exception("Erreur d'impression: " . $e->getMessage());
        }
    }

    /**
     * Imprimer comptage de caisse
     */
    public function printCashCount(CashCount $cashCount)
    {
        try {
            $cashCount->loadMissing([
                'creator:id,name',
                'denominations:id,cash_count_id,denomination,quantity,subtotal',
            ]);

            $connector = new FilePrintConnector($this->connectorPath);
            $this->printer = new Printer($connector);

            $this->printCashCountHeader($cashCount);
            //$this->printCashCountInfo($cashCount);
            $this->printCashCountDenominations($cashCount);
            $this->printCashCountTotals($cashCount);
            $this->printCashCountFooter($cashCount);

            $this->printer->cut(Printer::CUT_PARTIAL);
            $this->printer->close();

            return [
                'success' => true,
                'message' => 'Comptage de caisse imprime avec succes'
            ];

        } catch (\Exception $e) {
            Log::error('Erreur impression comptage: ' . $e->getMessage());
            throw new \Exception("Erreur d'impression: " . $e->getMessage());
        }
    }

    // ==================== VENTE RAPIDE ====================

    private function printHeader(Sale $sale)
    {
        $this->printer->setJustification(Printer::JUSTIFY_CENTER);
        
        if (!empty($this->companyInfo['logo_path']) && Storage::exists($this->companyInfo['logo_path'])) {
            try {
                $logoPath = storage_path('app/' . $this->companyInfo['logo_path']);
                if (file_exists($logoPath)) {
                    $logo = EscposImage::load($logoPath);
                    $this->printer->bitImage($logo);
                    $this->printer->feed();
                }
            } catch (\Exception $e) {
                Log::warning('Logo non charge: ' . $e->getMessage());
            }
        }

        $this->printer->selectPrintMode(Printer::MODE_DOUBLE_HEIGHT | Printer::MODE_DOUBLE_WIDTH);
        $this->printer->setEmphasis(true);
        $this->printer->text($this->clean($this->companyInfo['name']) . "\n");
        $this->printer->selectPrintMode();
        $this->printer->setEmphasis(false);
        $this->printer->feed();

        $this->printer->text("FACTURE DE VENTE RAPIDE\n");
        $this->printer->feed();

        $this->printer->selectPrintMode(Printer::MODE_DOUBLE_HEIGHT);
        $this->printer->setEmphasis(true);
        $this->printer->text($sale->sale_number . "\n");
        $this->printer->selectPrintMode();
        $this->printer->setEmphasis(false);

        $this->printer->text($sale->sale_date->format('d/m/Y') . "\n");
        $this->printer->feed();

    }

    private function printCustomerInfo(Sale $sale)
    {
        $this->printer->setJustification(Printer::JUSTIFY_LEFT);
    
        $this->printer->text(str_repeat("-", 48) . "\n");

        if ($sale->customer) {
            $this->printer->setEmphasis(true);
            $this->printer->text($this->clean($sale->customer->name) . "\n");
            $this->printer->setEmphasis(false);
            
            $this->printer->text("N° Client: " . $sale->customer->customer_number . "\n");
            
            if ($sale->customer->phone) {
                $this->printer->text("Telephone: " . $sale->customer->phone . "\n");
            }
            
            if ($sale->customer->address) {
                $address = $this->clean($sale->customer->address);
                if (strlen($address) > 46) {
                    $this->printer->text(substr($address, 0, 46) . "\n");
                    if (strlen($address) > 46) {
                        $this->printer->text(substr($address, 46, 46) . "\n");
                    }
                } else {
                    $this->printer->text($address . "\n");
                }
            }
        } else {
            $this->printer->setEmphasis(true);
            $this->printer->text("Vente au comptant\n");
            $this->printer->setEmphasis(false);
            $this->printer->text("Client non enregistre\n");
        }
        
        $this->printer->feed();
        $this->printer->text(str_repeat("=", 48) . "\n");
    }

    private function printItems(Sale $sale)
    {
        $this->printer->setJustification(Printer::JUSTIFY_LEFT);
        
        $this->printer->setEmphasis(true);
        $this->printer->text(str_pad("Produit", 24) . 
                            str_pad("Qte", 6, " ", STR_PAD_LEFT) . 
                            str_pad("P.U", 8, " ", STR_PAD_LEFT) . 
                            str_pad("Total", 10, " ", STR_PAD_LEFT) . "\n");
        $this->printer->setEmphasis(false);
        $this->printer->text(str_repeat("-", 48) . "\n");

        foreach ($sale->items as $item) {
            $productName = $this->clean($item->variant->product->name);
            
            $this->printer->setEmphasis(true);
            if (strlen($productName) > 46) {
                $this->printer->text(substr($productName, 0, 43) . "...\n");
            } else {
                $this->printer->text($productName . "\n");
            }
            $this->printer->setEmphasis(false);

            $attrs = $this->getAttributesMap($item->variant);
            if (!empty($attrs)) {
                $attrsText = collect($attrs)
                    ->map(fn($v, $k) => "$k: $v")
                    ->implode(' • ');
                
                if (strlen($attrsText) > 46) {
                    $this->printer->text(substr($attrsText, 0, 43) . "...\n");
                } else {
                    $this->printer->text($attrsText . "\n");
                }
            }

            $qtyStr = str_pad($item->quantity, 6, " ", STR_PAD_LEFT);
            $priceStr = str_pad(number_format($item->unit_price, 0, '', ' '), 8, " ", STR_PAD_LEFT);
            $totalStr = str_pad(number_format($item->subtotal, 0, '', ' '), 10, " ", STR_PAD_LEFT);
            
            $this->printer->text(str_pad("", 24) . $qtyStr . $priceStr . $totalStr . "\n");
            $this->printer->feed();
        }

        $this->printer->text(str_repeat("=", 48) . "\n");
    }

    private function printTotals(Sale $sale)
    {
        $this->printer->setJustification(Printer::JUSTIFY_RIGHT);
        

        if ($sale->discount_amount > 0) {
            $this->printer->text("Remise\n");
            $this->printer->text("- " . number_format($sale->discount_amount, 0, '', ' ') . " Ar\n");
            
            if ($sale->discount_reason) {
                $this->printer->setJustification(Printer::JUSTIFY_LEFT);
                $reason = $this->clean($sale->discount_reason);
                $this->printer->text("(" . substr($reason, 0, 44) . ")\n");
                $this->printer->setJustification(Printer::JUSTIFY_RIGHT);
            }
            $this->printer->feed();
        }

        $this->printer->text(str_repeat("-", 48) . "\n");
        $this->printer->selectPrintMode(Printer::MODE_DOUBLE_HEIGHT);
        $this->printer->setEmphasis(true);
        $this->printer->text("TOTAL\n");
        $this->printer->text(number_format($sale->total_amount, 0, '', ' ') . " Ar\n");
        $this->printer->selectPrintMode();
        $this->printer->setEmphasis(false);
        
        $this->printer->feed();
        $this->printer->text(str_repeat("=", 48) . "\n");
    }

    private function printPaymentInfo(Sale $sale)
    {
        if (!$sale->payment_method) {
            return;
        }

        $this->printer->setJustification(Printer::JUSTIFY_LEFT);
        $this->printer->text(str_repeat("-", 48) . "\n");
        
        $this->printer->text("Mode de paiement: ");
        $this->printer->setEmphasis(true);
        $paymentMethod = match($sale->payment_method->value) {
            'cash' => 'Especes',
            'mobile_money' => 'Mobile Money',
            'bank_transfer' => 'Virement bancaire',
            'mixed' => 'Paiement mixte',
            default => 'Non specifie'
        };
        $this->printer->text($paymentMethod . "\n");
        $this->printer->setEmphasis(false);
        $this->printer->feed();

        if ($sale->payment_status->value === 'paid') {
            $this->printer->text("✓ Paiement recu integralement\n");
        } elseif ($sale->payment_status->value === 'partial') {
            $this->printer->text("Paiement partiel\n");
            $this->printer->text("Acompte verse: " . number_format($sale->getPaidAmount(), 0, '', ' ') . " Ar\n");
            $this->printer->text("Reste a payer: " . number_format($sale->getRemainingAmount(), 0, '', ' ') . " Ar\n");
        }
        
        $this->printer->feed();
        $this->printer->text(str_repeat("=", 48) . "\n");
    }

    private function printFooter(Sale $sale)
    {
        $this->printer->setJustification(Printer::JUSTIFY_CENTER);
        
        if (!empty($this->companyInfo['invoice_signature'])) {
            $this->printer->feed();
            $signature = $this->clean($this->companyInfo['invoice_signature']);
            $this->printer->text($signature . "\n");
            $this->printer->feed();
        }

        $this->printer->text(str_repeat("-", 48) . "\n");
        if ($this->companyInfo['address']) {
            $this->printer->text($this->clean($this->companyInfo['address']) . "\n");
        }
        
        if ($this->companyInfo['phone']) {
            $this->printer->text("Tel: " . $this->companyInfo['phone'] . "\n");
        }
        
        if ($this->companyInfo['email']) {
            $this->printer->text("Fb:: " . $this->companyInfo['email'] . "\n");
        }
        $this->printer->text(str_repeat("*", 48) . "\n");
    }

    // ==================== RÉSERVATION ====================

    private function printReservationHeader(Reservation $reservation)
    {
        $this->printer->setJustification(Printer::JUSTIFY_CENTER);
        
        $this->printer->selectPrintMode(Printer::MODE_DOUBLE_HEIGHT | Printer::MODE_DOUBLE_WIDTH);
        $this->printer->setEmphasis(true);
        $this->printer->text($this->clean($this->companyInfo['name']) . "\n");
        $this->printer->selectPrintMode();
        $this->printer->setEmphasis(false);
        $this->printer->feed();

        $this->printer->text("FACTURE DE RESERVATION\n");

        $this->printer->selectPrintMode(Printer::MODE_DOUBLE_HEIGHT);
        $this->printer->setEmphasis(true);
        $this->printer->text($reservation->sale->sale_number . "\n");
        $this->printer->selectPrintMode();
        $this->printer->setEmphasis(false);

        $this->printer->text("Reservation du " . $reservation->reservation_date->format('d/m/Y') . "\n");
        $this->printer->text(str_repeat("=", 48) . "\n");
    }

    private function printReservationCustomer(Reservation $reservation)
    {
        $this->printer->setJustification(Printer::JUSTIFY_LEFT);
        

        $this->printer->setEmphasis(true);
        $this->printer->text($this->clean($reservation->customer->name) . "\n");
        $this->printer->setEmphasis(false);
        
        $this->printer->text("N° Client: " . $reservation->customer->customer_number . "\n");
        
        if ($reservation->customer->phone) {
            $this->printer->text("Telephone: " . $reservation->customer->phone . "\n");
        }
        
        if ($reservation->customer->address) {
            $address = $this->clean($reservation->customer->address);
            if (strlen($address) > 46) {
                $this->printer->text(substr($address, 0, 46) . "\n");
            } else {
                $this->printer->text($address . "\n");
            }
        }        
        $this->printer->text(str_repeat("=", 48) . "\n");
    }

    private function printReservationItems(Reservation $reservation)
    {
        $this->printer->setJustification(Printer::JUSTIFY_LEFT);
        
        $this->printer->setEmphasis(true);
        $this->printer->text(str_pad("Produit", 24) . 
                            str_pad("Qte", 6, " ", STR_PAD_LEFT) . 
                            str_pad("P.U", 8, " ", STR_PAD_LEFT) . 
                            str_pad("Total", 10, " ", STR_PAD_LEFT) . "\n");
        $this->printer->setEmphasis(false);
        $this->printer->text(str_repeat("-", 48) . "\n");

        foreach ($reservation->sale->items as $item) {
            $productName = $this->clean($item->variant->product->name);
            
            $this->printer->setEmphasis(true);
            if (strlen($productName) > 46) {
                $this->printer->text(substr($productName, 0, 43) . "...\n");
            } else {
                $this->printer->text($productName . "\n");
            }
            $this->printer->setEmphasis(false);

            $attrs = $this->getAttributesMap($item->variant);
            if (!empty($attrs)) {
                $attrsText = collect($attrs)
                    ->map(fn($v, $k) => "$k: $v")
                    ->implode(' • ');
                
                if (strlen($attrsText) > 46) {
                    $this->printer->text(substr($attrsText, 0, 43) . "...\n");
                } else {
                    $this->printer->text($attrsText . "\n");
                }
            }

            $qtyStr = str_pad($item->quantity, 6, " ", STR_PAD_LEFT);
            $priceStr = str_pad(number_format($item->unit_price, 0, '', ' '), 8, " ", STR_PAD_LEFT);
            $totalStr = str_pad(number_format($item->subtotal, 0, '', ' '), 10, " ", STR_PAD_LEFT);
            
            $this->printer->text(str_pad("", 24) . $qtyStr . $priceStr . $totalStr . "\n");
            $this->printer->feed();
        }

        $this->printer->text(str_repeat("=", 48) . "\n");
    }

    private function printReservationTotals(Reservation $reservation)
    {
        $this->printer->setJustification(Printer::JUSTIFY_RIGHT);
        
        if ($reservation->sale->discount_amount > 0) {           
            $this->printer->text("Remise\n");
            $this->printer->text("-" . number_format($reservation->sale->discount_amount, 0, '', ' ') . " Ar\n");
            $this->printer->feed();
        }

        $this->printer->text(str_repeat("-", 48) . "\n");
        $this->printer->selectPrintMode(Printer::MODE_DOUBLE_HEIGHT);
        $this->printer->setEmphasis(true);
        $this->printer->text("Montant total\n");
        $this->printer->text(number_format($reservation->sale->total_amount, 0, '', ' ') . " Ar\n");
        $this->printer->selectPrintMode();
        $this->printer->setEmphasis(false);
        
        $this->printer->text("Acompte verse\n");
        $this->printer->text(number_format($reservation->deposit_amount, 0, '', ' ') . " Ar\n");

        
        $this->printer->selectPrintMode(Printer::MODE_DOUBLE_HEIGHT);
        $this->printer->setEmphasis(true);
        $this->printer->text("Reste a payer\n");
        $this->printer->text(number_format($reservation->remaining_amount, 0, '', ' ') . " Ar\n");
        // Suite de printReservationTotals
        $this->printer->selectPrintMode();
        $this->printer->setEmphasis(false);
        $this->printer->text(str_repeat("=", 48) . "\n");
    }

    private function printReservationDetails(Reservation $reservation)
    {
        $this->printer->setJustification(Printer::JUSTIFY_LEFT);
        

        if ($reservation->expiry_date) {
            $this->printer->text("Date d'expiration:\n");
            $this->printer->setEmphasis(true);
            $this->printer->text($reservation->expiry_date->format('d/m/Y') . "\n");
            $this->printer->setEmphasis(false);
            $this->printer->feed();
        }


        $this->printer->text(str_repeat("=", 48) . "\n");
    }

    private function printReservationFooter(Reservation $reservation)
    {
        $this->printer->setJustification(Printer::JUSTIFY_CENTER);
        
        if (!empty($this->companyInfo['invoice_signature'])) {
            $this->printer->feed();
            $signature = $this->clean($this->companyInfo['invoice_signature']);
            $this->printer->text($signature . "\n");
            $this->printer->feed();
        }

        $this->printer->text(str_repeat("-", 48) . "\n");
  
        
        if ($this->companyInfo['address']) {
            $this->printer->text($this->clean($this->companyInfo['address']) . "\n");
        }
        
        if ($this->companyInfo['phone']) {
            $this->printer->text("Tel: " . $this->companyInfo['phone'] . "\n");
        }
        
        if ($this->companyInfo['email']) {
            $this->printer->text("Fb:: " . $this->companyInfo['email'] . "\n");
        }

        $this->printer->text(str_repeat("*", 48) . "\n");
    }

    // ==================== REÇU FINALISATION RÉSERVATION ====================

    private function printReservationReceiptHeader(Reservation $reservation)
    {
        $this->printer->setJustification(Printer::JUSTIFY_CENTER);
        
        if (!empty($this->companyInfo['logo_path']) && Storage::exists($this->companyInfo['logo_path'])) {
            try {
                $logoPath = storage_path('app/' . $this->companyInfo['logo_path']);
                if (file_exists($logoPath)) {
                    $logo = EscposImage::load($logoPath);
                    $this->printer->bitImage($logo);
                    $this->printer->feed();
                }
            } catch (\Exception $e) {
                Log::warning('Logo non charge: ' . $e->getMessage());
            }
        }

        $this->printer->selectPrintMode(Printer::MODE_DOUBLE_HEIGHT | Printer::MODE_DOUBLE_WIDTH);
        $this->printer->setEmphasis(true);
        $this->printer->text($this->clean($this->companyInfo['name']) . "\n");
        $this->printer->selectPrintMode();
        $this->printer->setEmphasis(false);
        $this->printer->feed();

        $this->printer->text("RECU DE FINALISATION DE RESERVATION\n");
        $this->printer->feed();

        $refNumber = $reservation->transactionComplete->reference_number ?? 'REC-' . str_pad($reservation->id, 6, '0', STR_PAD_LEFT);
        $this->printer->selectPrintMode(Printer::MODE_DOUBLE_HEIGHT);
        $this->printer->setEmphasis(true);
        $this->printer->text($refNumber . "\n");
        $this->printer->selectPrintMode();
        $this->printer->setEmphasis(false);

        $this->printer->text($reservation->completed_at->format('d/m/Y') . "\n");
        $this->printer->text(str_repeat("=", 48) . "\n");
    }

    private function printReservationReceiptClient(Reservation $reservation)
    {
        $this->printer->setJustification(Printer::JUSTIFY_LEFT);
        
        $this->printer->setEmphasis(true);
        $this->printer->text($this->clean($reservation->customer->name) . "\n");
        $this->printer->setEmphasis(false);
        
        $this->printer->text("Client #" . $reservation->customer->customer_number);
        if ($reservation->customer->phone) {
            $this->printer->text(" • " . $reservation->customer->phone);
        }
        $this->printer->text("\n");
        $this->printer->text(str_repeat("-", 48) . "\n");
    }

    private function printReservationReceiptPayment(Reservation $reservation)
    {
        if (!$reservation->transactionComplete) {
            return;
        }

        $this->printer->setJustification(Printer::JUSTIFY_LEFT);
        
        $this->printer->text("Montant\n");
        $this->printer->selectPrintMode(Printer::MODE_DOUBLE_HEIGHT);
        $this->printer->setEmphasis(true);
        $this->printer->text(number_format($reservation->transactionComplete->amount, 0, '', ' ') . " Ar\n");
        $this->printer->selectPrintMode();
        $this->printer->setEmphasis(false);
        $this->printer->feed();
    }

    private function printReservationReceiptSummary(Reservation $reservation)
    {
        $this->printer->setJustification(Printer::JUSTIFY_LEFT);
        
        $this->printer->setEmphasis(true);
        $this->printer->text("Resume de la Reservation\n");
        $this->printer->setEmphasis(false);
        $this->printer->text(str_repeat("-", 48) . "\n");

        $this->printer->text(str_pad("N° Vente", 24) . $reservation->sale->sale_number . "\n");
        $this->printer->text(str_pad("Date de reservation", 24) . $reservation->reservation_date->format('d/m/Y') . "\n");
        
        if ($reservation->expiry_date) {
            $this->printer->text(str_pad("Date d'expiration", 24) . $reservation->expiry_date->format('d/m/Y') . "\n");
        }
        
        $this->printer->text(str_pad("Date de completion", 24) . $reservation->completed_at->format('d/m/Y') . "\n");
        
        $this->printer->feed();
        $this->printer->text(str_repeat("=", 48) . "\n");
    }

    private function printReservationReceiptTotals(Reservation $reservation)
    {
        $this->printer->setJustification(Printer::JUSTIFY_RIGHT);
        
        $this->printer->text("Montant total\n");
        $this->printer->text(number_format($reservation->total_amount, 0, '', ' ') . " Ar\n");
        $this->printer->feed();
        
        $this->printer->text("Acompte verse\n");
        $this->printer->text(number_format($reservation->deposit_amount, 0, '', ' ') . " Ar\n");
        $this->printer->feed();
        
        if ($reservation->transactionComplete) {
            $this->printer->text("Paiement final\n");
            $this->printer->text(number_format($reservation->transactionComplete->amount, 0, '', ' ') . " Ar\n");
            $this->printer->feed();
        }
        
        $this->printer->text(str_repeat("-", 48) . "\n");
        $this->printer->selectPrintMode(Printer::MODE_DOUBLE_HEIGHT);
        $this->printer->setEmphasis(true);
        $this->printer->text("Montant paye\n");
        $this->printer->text(number_format($reservation->total_amount, 0, '', ' ') . " Ar\n");
        $this->printer->selectPrintMode();
        $this->printer->setEmphasis(false);
        
        $this->printer->feed();
        $this->printer->text(str_repeat("=", 48) . "\n");
    }

    private function printReservationReceiptFooter(Reservation $reservation)
    {
        $this->printer->setJustification(Printer::JUSTIFY_CENTER);
        

        if (!empty($this->companyInfo['invoice_signature'])) {
            $this->printer->feed();
            $signature = $this->clean($this->companyInfo['invoice_signature']);
            $this->printer->text($signature . "\n");
            $this->printer->feed();
        }

        $this->printer->text(str_repeat("-", 48) . "\n");
        if ($this->companyInfo['address']) {
            $this->printer->text($this->clean($this->companyInfo['address']) . "\n");
        }
        
        if ($this->companyInfo['phone']) {
            $this->printer->text("Tel: " . $this->companyInfo['phone'] . "\n");
        }
        
        $this->printer->feed();
        $this->printer->text(str_repeat("*", 48) . "\n");
    }

    // ==================== CRÉDIT ====================

    private function printCreditHeader(Credit $credit)
    {
        $this->printer->setJustification(Printer::JUSTIFY_CENTER);
        
        $this->printer->selectPrintMode(Printer::MODE_DOUBLE_HEIGHT | Printer::MODE_DOUBLE_WIDTH);
        $this->printer->setEmphasis(true);
        $this->printer->text($this->clean($this->companyInfo['name']) . "\n");
        $this->printer->selectPrintMode();
        $this->printer->setEmphasis(false);
        $this->printer->feed();

        $this->printer->text("FACTURE DE CREDIT\n");
        $this->printer->feed();

        $this->printer->selectPrintMode(Printer::MODE_DOUBLE_HEIGHT);
        $this->printer->setEmphasis(true);
        $this->printer->text($credit->sale->sale_number . "\n");
        $this->printer->selectPrintMode();
        $this->printer->setEmphasis(false);

        $this->printer->text("Credit du " . $credit->credit_date->format('d/m/Y') . "\n");
        
        $this->printer->text(str_repeat("=", 48) . "\n");
    }

    private function printCreditCustomer(Credit $credit)
    {
        $this->printer->setJustification(Printer::JUSTIFY_LEFT);
      

        $this->printer->setEmphasis(true);
        $this->printer->text($this->clean($credit->customer->name) . "\n");
        $this->printer->setEmphasis(false);
        
        $this->printer->text("N° Client: " . $credit->customer->customer_number . "\n");
        
        $this->printer->text(str_repeat("=", 48) . "\n");
    }

    private function printCreditItems(Credit $credit)
    {
        $this->printer->setJustification(Printer::JUSTIFY_LEFT);
        
        $this->printer->setEmphasis(true);
        $this->printer->text(str_pad("Produit", 24) . 
                            str_pad("Qte", 6, " ", STR_PAD_LEFT) . 
                            str_pad("P.U", 8, " ", STR_PAD_LEFT) . 
                            str_pad("Total", 10, " ", STR_PAD_LEFT) . "\n");
        $this->printer->setEmphasis(false);
        $this->printer->text(str_repeat("-", 48) . "\n");

        foreach ($credit->sale->items as $item) {
            $productName = $this->clean($item->variant->product->name);
            
            $this->printer->setEmphasis(true);
            if (strlen($productName) > 46) {
                $this->printer->text(substr($productName, 0, 43) . "...\n");
            } else {
                $this->printer->text($productName . "\n");
            }
            $this->printer->setEmphasis(false);

            $attrs = $this->getAttributesMap($item->variant);
            if (!empty($attrs)) {
                $attrsText = collect($attrs)
                    ->map(fn($v, $k) => "$k: $v")
                    ->implode(' • ');
                
                if (strlen($attrsText) > 46) {
                    $this->printer->text(substr($attrsText, 0, 43) . "...\n");
                } else {
                    $this->printer->text($attrsText . "\n");
                }
            }

            $qtyStr = str_pad($item->quantity, 6, " ", STR_PAD_LEFT);
            $priceStr = str_pad(number_format($item->unit_price, 0, '', ' '), 8, " ", STR_PAD_LEFT);
            $totalStr = str_pad(number_format($item->subtotal, 0, '', ' '), 10, " ", STR_PAD_LEFT);
            
            $this->printer->text(str_pad("", 24) . $qtyStr . $priceStr . $totalStr . "\n");
            $this->printer->feed();
        }

        $this->printer->text(str_repeat("=", 48) . "\n");
    }

    private function printCreditTotals(Credit $credit)
    {
        $this->printer->setJustification(Printer::JUSTIFY_RIGHT);
        if ($credit->sale->discount_amount > 0) {           
            $this->printer->text("Remise\n");
            $this->printer->text("-" . number_format($credit->sale->discount_amount, 0, '', ' ') . " Ar\n");
            $this->printer->feed();
        }
        
        $this->printer->text("Montant total du credit\n");
        $this->printer->setEmphasis(true);
        $this->printer->text(number_format($credit->total_amount, 0, '', ' ') . " Ar\n");
        $this->printer->selectPrintMode();
        $this->printer->setEmphasis(false);
        $this->printer->text(str_repeat("=", 48) . "\n");
    }

    private function printCreditInstallments(Credit $credit)
    {
        if ($credit->installments->count() === 0) {
            return;
        }

        $this->printer->setJustification(Printer::JUSTIFY_LEFT);
        
        $this->printer->setEmphasis(true);
        $this->printer->text("PLAN DE PAIEMENT\n");
        $this->printer->setEmphasis(false);
        $this->printer->text(str_repeat("-", 48) . "\n");

        foreach ($credit->installments as $installment) {
            $this->printer->text("#" . $installment->installment_number . " - " . 
                                 $installment->due_date->format('d/m/Y') . "\n");
            
            $this->printer->text("  Montant du: " . number_format($installment->amount_due, 0, '', ' ') . " Ar\n");
            
        }

        $this->printer->text(str_repeat("=", 48) . "\n");
    }

    private function printCreditFooter(Credit $credit)
    {
        $this->printer->setJustification(Printer::JUSTIFY_CENTER);

        if ($credit->notes) {
            $this->printer->setJustification(Printer::JUSTIFY_LEFT);
            $this->printer->setEmphasis(true);
            $this->printer->text("Notes:\n");
            $this->printer->setEmphasis(false);
            $notes = $this->clean($credit->notes);
            $this->printer->text($notes . "\n");
            $this->printer->feed();
            $this->printer->text(str_repeat("-", 48) . "\n");
            $this->printer->setJustification(Printer::JUSTIFY_CENTER);
        }
        
        if (!empty($this->companyInfo['invoice_signature'])) {
            $this->printer->feed();
            $signature = $this->clean($this->companyInfo['invoice_signature']);
            $this->printer->text($signature . "\n");
            $this->printer->feed();
        }

        $this->printer->text(str_repeat("-", 48) . "\n");
        
        if ($this->companyInfo['address']) {
            $this->printer->text($this->clean($this->companyInfo['address']) . "\n");
        }
        
        if ($this->companyInfo['phone']) {
            $this->printer->text("Tel: " . $this->companyInfo['phone'] . "\n");
        }
        
        if ($this->companyInfo['email']) {
            $this->printer->text("Fb:: " . $this->companyInfo['email'] . "\n");
        }
        $this->printer->text(str_repeat("*", 48) . "\n");
    }


        private function printCreditPaymentInstallment(InstallmentTransaction $installmentTransaction)
    {
        $installment = $installmentTransaction->installment;
        $credit = $installment->credit;
        
        $this->printer->setJustification(Printer::JUSTIFY_LEFT);
        
        $this->printer->setEmphasis(true);
        $this->printer->text("Echeance\n");
        $this->printer->setEmphasis(false);
        $this->printer->text(str_repeat("-", 48) . "\n");

        $this->printer->text(str_pad("Credit", 16) . $credit->sale->sale_number . "\n");
        $this->printer->text(str_pad("Echeance", 16) . "#" . $installment->installment_number . "\n");
        $this->printer->text(str_pad("Due", 16) . $installment->due_date->format('d/m/Y') . "\n");
        $this->printer->text(str_pad("Montant", 16) . number_format($installment->amount_due, 0, '', ' ') . " Ar\n");
        $this->printer->text(str_pad("Reste sur l'echance ", 16) . number_format($installment->getRemainingAmount(), 0, '', ' ') . " Ar\n");
        $this->printer->text(str_pad("Reste total", 16) . number_format($credit->amount_due, 0, '', ' ') . " Ar\n");
        
        
        $this->printer->feed();
        $this->printer->text(str_repeat("=", 48) . "\n");
    }

    private function printCreditPaymentFooter(InstallmentTransaction $installmentTransaction)
    {
        $this->printer->setJustification(Printer::JUSTIFY_CENTER);
        
        if (!empty($this->companyInfo['invoice_signature'])) {
            $this->printer->feed();
            $signature = $this->clean($this->companyInfo['invoice_signature']);
            $this->printer->text($signature . "\n");
            $this->printer->feed();
        }

        $this->printer->text(str_repeat("-", 48) . "\n");
        $this->printer->setEmphasis(true);
        $this->printer->text($this->clean($this->companyInfo['name']) . "\n");
        $this->printer->setEmphasis(false);
        
        if ($this->companyInfo['address']) {
            $this->printer->text($this->clean($this->companyInfo['address']) . "\n");
        }
        
        if ($this->companyInfo['phone']) {
            $this->printer->text("Tel: " . $this->companyInfo['phone'] . "\n");
        }
        
        $this->printer->feed();
        $this->printer->text(str_repeat("*", 48) . "\n");
    }

    // ==================== COMPTAGE DE CAISSE ====================

    private function printCashCountHeader(CashCount $cashCount)
    {
        $this->printer->setJustification(Printer::JUSTIFY_CENTER);
        
        if (!empty($this->companyInfo['logo_path']) && Storage::exists($this->companyInfo['logo_path'])) {
            try {
                $logoPath = storage_path('app/' . $this->companyInfo['logo_path']);
                if (file_exists($logoPath)) {
                    $logo = EscposImage::load($logoPath);
                    $this->printer->bitImage($logo);
                    $this->printer->feed();
                }
            } catch (\Exception $e) {
                Log::warning('Logo non charge: ' . $e->getMessage());
            }
        }

        $this->printer->selectPrintMode(Printer::MODE_DOUBLE_HEIGHT | Printer::MODE_DOUBLE_WIDTH);
        $this->printer->setEmphasis(true);
        $this->printer->text($this->clean($this->companyInfo['name']) . "\n");
        $this->printer->selectPrintMode();
        $this->printer->setEmphasis(false);
        $this->printer->feed();

        $this->printer->text("COMPTAGE DE CAISSE\n");
        $this->printer->feed();

        $this->printer->selectPrintMode(Printer::MODE_DOUBLE_HEIGHT);
        $this->printer->setEmphasis(true);
        $this->printer->text("#CC-" . str_pad($cashCount->id, 6, '0', STR_PAD_LEFT) . "\n");
        $this->printer->selectPrintMode();
        $this->printer->setEmphasis(false);
        $this->printer->text(str_repeat("=", 48) . "\n");
    }

    private function printCashCountInfo(CashCount $cashCount)
    {
        $this->printer->setJustification(Printer::JUSTIFY_LEFT);
        
        $this->printer->text(str_pad("Date du comptage:", 24) . $cashCount->count_date->format('d/m/Y') . "\n");
        $this->printer->text(str_pad("Cree par:", 24) . $this->clean($cashCount->creator->name) . "\n");
        
        $this->printer->feed();
        $this->printer->text(str_repeat("-", 48) . "\n");
        
        // Stats
        $this->printer->setJustification(Printer::JUSTIFY_CENTER);
        $this->printer->feed();
        
        $this->printer->text("Types de billets: ");
        $this->printer->setEmphasis(true);
        $this->printer->text($cashCount->denominations->count() . "\n");
        $this->printer->setEmphasis(false);
        
        $this->printer->text("Total billets: ");
        $this->printer->setEmphasis(true);
        $this->printer->text($cashCount->denominations->sum('quantity') . "\n");
        $this->printer->setEmphasis(false);
        
        $this->printer->text("Montant total: ");
        $this->printer->setEmphasis(true);
        $this->printer->text(number_format($cashCount->total_amount, 0, '', ' ') . " Ar\n");
        $this->printer->setEmphasis(false);
        
        $this->printer->feed();
        $this->printer->text(str_repeat("=", 48) . "\n");
    }

    private function printCashCountDenominations(CashCount $cashCount)
    {
        $this->printer->setJustification(Printer::JUSTIFY_LEFT);
        
        $this->printer->setEmphasis(true);
        $this->printer->text(str_pad("Denomination", 20) . 
                            str_pad("Qte", 8, " ", STR_PAD_LEFT) . 
                            str_pad("Sous-total", 20, " ", STR_PAD_LEFT) . "\n");
        $this->printer->setEmphasis(false);
        $this->printer->text(str_repeat("-", 48) . "\n");

        $denominations = $cashCount->denominations->sortByDesc('denomination');
        
        foreach ($denominations as $index => $denom) {
            $this->printer->text(($index + 1) . ". ");
            
            $denomText = "Billet " . number_format($denom->denomination, 0, '', ' ') . " Ar";
            if (strlen($denomText) > 18) {
                $this->printer->text(substr($denomText, 0, 18) . "\n");
            } else {
                $this->printer->text($denomText . "\n");
            }
            
            $qtyStr = str_pad($denom->quantity, 8, " ", STR_PAD_LEFT);
            $subtotalStr = str_pad(number_format($denom->subtotal, 0, '', ' ') . " Ar", 20, " ", STR_PAD_LEFT);
            
            $this->printer->text(str_pad("", 20) . $qtyStr . $subtotalStr . "\n");
            $this->printer->feed();
        }

        $this->printer->text(str_repeat("=", 48) . "\n");
    }

    private function printCashCountTotals(CashCount $cashCount)
    {
        $this->printer->setJustification(Printer::JUSTIFY_RIGHT);
        
        $this->printer->text("Nombre de billets:\n");
        $this->printer->text($cashCount->denominations->sum('quantity') . "\n");
        $this->printer->feed();
        
        $this->printer->text(str_repeat("-", 48) . "\n");
        $this->printer->selectPrintMode(Printer::MODE_DOUBLE_HEIGHT);
        $this->printer->setEmphasis(true);
        $this->printer->text("TOTAL\n");
        $this->printer->text(number_format($cashCount->total_amount, 0, '', ' ') . " Ar\n");
        $this->printer->selectPrintMode();
        $this->printer->setEmphasis(false);
        $this->printer->text(str_repeat("=", 48) . "\n");
    }

    private function printCashCountFooter(CashCount $cashCount)
    {
        if ($cashCount->notes) {
            $this->printer->setJustification(Printer::JUSTIFY_LEFT);
            $this->printer->text(str_repeat("-", 48) . "\n");
            $this->printer->setEmphasis(true);
            $this->printer->text("Notes\n");
            $this->printer->setEmphasis(false);
            $notes = $this->clean($cashCount->notes);
            $this->printer->text($notes . "\n");
            $this->printer->feed();
            $this->printer->text(str_repeat("=", 48) . "\n");
        }

        $this->printer->setJustification(Printer::JUSTIFY_CENTER);
        
        if (!empty($this->companyInfo['invoice_signature'])) {
            $this->printer->feed();
            $signature = $this->clean($this->companyInfo['invoice_signature']);
            $this->printer->text($signature . "\n");
            $this->printer->feed();
        }
        $this->printer->text(str_repeat("*", 48) . "\n");
    }
    // ==================== PAIEMENT CRÉDIT ====================

    private function printCreditPaymentHeader(InstallmentTransaction $installmentTransaction)
    {
        $this->printer->setJustification(Printer::JUSTIFY_CENTER);
        
        if (!empty($this->companyInfo['logo_path']) && Storage::exists($this->companyInfo['logo_path'])) {
            try {
                $logoPath = storage_path('app/' . $this->companyInfo['logo_path']);
                if (file_exists($logoPath)) {
                    $logo = EscposImage::load($logoPath);
                    $this->printer->bitImage($logo);
                    $this->printer->feed();
                }
            } catch (\Exception $e) {
                Log::warning('Logo non charge: ' . $e->getMessage());
            }
        }

        $this->printer->selectPrintMode(Printer::MODE_DOUBLE_HEIGHT | Printer::MODE_DOUBLE_WIDTH);
        $this->printer->setEmphasis(true);
        $this->printer->text($this->clean($this->companyInfo['name']) . "\n");
        $this->printer->selectPrintMode();
        $this->printer->setEmphasis(false);
        $this->printer->feed();

        $this->printer->text("RECU DE PAIEMENT\n");
        $refNumber = $installmentTransaction->transaction->reference_number ?? 'REC-' . str_pad($installmentTransaction->id, 6, '0', STR_PAD_LEFT);
        $this->printer->selectPrintMode(Printer::MODE_DOUBLE_HEIGHT);
        $this->printer->setEmphasis(true);
        $this->printer->text($refNumber . "\n");
        $this->printer->selectPrintMode();
        $this->printer->setEmphasis(false);

        $this->printer->text($installmentTransaction->payment_date->format('d/m/Y') . "\n");

        $this->printer->text(str_repeat("=", 48) . "\n");
    }

    private function printCreditPaymentClient(InstallmentTransaction $installmentTransaction)
    {
        $credit = $installmentTransaction->installment->credit;
        
        $this->printer->setJustification(Printer::JUSTIFY_LEFT);
        
        $this->printer->setEmphasis(true);
        $this->printer->text($this->clean($credit->customer->name) . "\n");
        $this->printer->setEmphasis(false);
        
        $this->printer->text("Client #" . $credit->customer->customer_number);
        if ($credit->customer->phone) {
            $this->printer->text(" • " . $credit->customer->phone);
        }
        $this->printer->text("\n");
        
        $this->printer->text(str_repeat("-", 48) . "\n");
    }

    private function printCreditPaymentDetails(InstallmentTransaction $installmentTransaction)
    {
        $this->printer->setJustification(Printer::JUSTIFY_LEFT);
        

        $this->printer->text("Montant\n");
        $this->printer->selectPrintMode(Printer::MODE_DOUBLE_HEIGHT);
        $this->printer->setEmphasis(true);
        $this->printer->text(number_format($installmentTransaction->amount, 0, '', ' ') . " Ar\n");
        $this->printer->selectPrintMode();
        $this->printer->setEmphasis(false);
        $this->printer->feed();
        $this->printer->text(str_repeat("=", 48) . "\n");
    }

    /**
     * Récupérer les attributs formatés - Comme blade attributeTypeValueMap()
     */
    private function getAttributesMap($variant)
    {
        if (!$variant->relationLoaded('variantAttributeValues')) {
            return [];
        }

        $attributeMap = [];
        foreach ($variant->variantAttributeValues as $vav) {
            if ($vav->attributeValue && $vav->attributeValue->attributeType) {
                $typeName = $this->clean($vav->attributeValue->attributeType->display_name);
                $value = $this->clean($vav->attributeValue->value);
                $attributeMap[$typeName] = $value;
            }
        }

        return $attributeMap;
    }

    /**
     * Nettoyer texte - Accents + caractères spéciaux
     */
    private function clean($text)
    {
        if (empty($text)) {
            return '';
        }

        // Accents
        $search = ['à','â','ä','á','ã','å','ç','é','è','ê','ë','í','ì','î','ï','ñ',
                   'ó','ò','ô','ö','õ','ø','ú','ù','û','ü','ý','ÿ',
                   'À','Â','Ä','Á','Ã','Å','Ç','É','È','Ê','Ë','Í','Ì','Î','Ï','Ñ',
                   'Ó','Ò','Ô','Ö','Õ','Ø','Ú','Ù','Û','Ü','Ý'];
        
        $replace = ['a','a','a','a','a','a','c','e','e','e','e','i','i','i','i','n',
                    'o','o','o','o','o','o','u','u','u','u','y','y',
                    'A','A','A','A','A','A','C','E','E','E','E','I','I','I','I','N',
                    'O','O','O','O','O','O','U','U','U','U','Y'];
        
        return str_replace($search, $replace, $text);
    }

    /**
     * Récupérer infos entreprise
     */
    private function getCompanyInfo(): array
    {
        $info = CompanyInfo::first();
        
        return [
            'name' => $info->name ?? config('app.name'),
            'address' => $info->address ?? '',
            'phone' => $info->phone ?? '',
            'email' => $info->email ?? '',
            'logo_path' => $info->logo_path ?? null,
            'invoice_signature' => $info->invoice_signature ?? null,
            'printer_path' => $info->printer_path ?? null,
            'auto_print_immediate_sale' => $info->auto_print_immediate_sale ?? false,
            'auto_print_credit' => $info->auto_print_credit ?? false,
            'auto_print_credit_payment' => $info->auto_print_credit_payment ?? false,
            'auto_print_reservation' => $info->auto_print_reservation ?? false,
            'auto_print_reservation_complete' => $info->auto_print_reservation_complete ?? false,
            'auto_print_cash_count' => $info->auto_print_cash_count ?? false,

        ];
    }

    /**
     * Test imprimante
     */
    public function testPrinter()
    {
        try {
            $connector = new FilePrintConnector($this->connectorPath);
            $printer = new Printer($connector);
            
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->selectPrintMode(Printer::MODE_DOUBLE_HEIGHT | Printer::MODE_DOUBLE_WIDTH);
            $printer->setEmphasis(true);
            $printer->text("TEST OK!\n");
            $printer->selectPrintMode();
            $printer->setEmphasis(false);
            
            $printer->text(str_repeat("=", 48) . "\n");
            $printer->text("Imprimante connectee\n");
            $printer->text("Xprinter fonctionnelle\n");
            $printer->text("Largeur: 48 caracteres (80mm)\n");
            $printer->text(str_repeat("=", 48) . "\n");
            
            $printer->cut(Printer::CUT_PARTIAL);
            $printer->close();
            
            return true;
        } catch (\Exception $e) {
            throw new \Exception("Test echoue: " . $e->getMessage());
        }
    }
}