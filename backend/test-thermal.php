<?php
require 'vendor/autoload.php';

use Mike42\Escpos\PrintConnectors\FilePrintConnector;
use Mike42\Escpos\Printer;
use Mike42\Escpos\EscposImage;

try {
    $connector = new FilePrintConnector("/dev/usb/lp0");
    $printer = new Printer($connector);
    
    // ═══════════════════════════════════════
    // EN-TÊTE STYLÉ
    // ═══════════════════════════════════════
    $printer->setJustification(Printer::JUSTIFY_CENTER);
    $printer->selectPrintMode(Printer::MODE_DOUBLE_HEIGHT | Printer::MODE_DOUBLE_WIDTH | Printer::MODE_EMPHASIZED);
    $printer->text("VOTRE BOUTIQUE\n");
    $printer->selectPrintMode(); // Reset
    
    $printer->setEmphasis(false);
    $printer->text("123 Rue Example, Tana\n");
    $printer->text("Tel: +261 34 12 345 67\n");
    
    // Ligne de séparation stylée
    $printer->text(str_repeat("=", 32) . "\n");
    $printer->feed();
    
    // ═══════════════════════════════════════
    // TITRE DU DOCUMENT
    // ═══════════════════════════════════════
    $printer->setEmphasis(true);
    $printer->setTextSize(2, 1);
    $printer->text("RECU FINALISATION\n");
    $printer->setTextSize(1, 1);
    $printer->setEmphasis(false);
    
    $printer->text("Ref: REC-000011\n");
    $printer->text("Date: 22/01/2026 18:30\n");
    
    $printer->feed();
    $printer->text(str_repeat("-", 32) . "\n");
    
    // ═══════════════════════════════════════
    // INFO CLIENT (avec encadré)
    // ═══════════════════════════════════════
    $printer->setJustification(Printer::JUSTIFY_LEFT);
    $printer->setEmphasis(true);
    $printer->text("CLIENT\n");
    $printer->setEmphasis(false);
    $printer->text("Nom: Jean Rakoto\n");
    $printer->text("N°: CUST-001234\n");
    $printer->text("Tel: +261 34 00 000 00\n");
    
    $printer->feed();
    $printer->text(str_repeat("-", 32) . "\n");
    
    // ═══════════════════════════════════════
    // DÉTAILS VENTE
    // ═══════════════════════════════════════
    $printer->setEmphasis(true);
    $printer->text("DETAILS RESERVATION\n");
    $printer->setEmphasis(false);
    $printer->text(sprintf("%-20s %10s\n", "N° Vente", "VNT-20260121-0011"));
    $printer->text(sprintf("%-20s %10s\n", "Date reserv.", "20/01/2026"));
    $printer->text(sprintf("%-20s %10s\n", "Date expir.", "27/01/2026"));
    
    $printer->feed();
    $printer->text(str_repeat("-", 32) . "\n");
    
    // ═══════════════════════════════════════
    // MONTANTS (avec alignement propre)
    // ═══════════════════════════════════════
    $printer->text(sprintf("%-20s %11s\n", "Montant total", "250 000 Ar"));
    $printer->text(sprintf("%-20s %11s\n", "Acompte verse", "100 000 Ar"));
    $printer->text(sprintf("%-20s %11s\n", "Paiement final", "150 000 Ar"));
    
    $printer->feed();
    $printer->setEmphasis(true);
    $printer->text(str_repeat("=", 32) . "\n");
    
    // TOTAL en gros
    $printer->setJustification(Printer::JUSTIFY_CENTER);
    $printer->setTextSize(2, 2);
    $printer->text("PAYE\n");
    $printer->text("250 000 Ar\n");
    $printer->setTextSize(1, 1);
    
    $printer->text(str_repeat("=", 32) . "\n");
    $printer->setEmphasis(false);
    
    // ═══════════════════════════════════════
    // PIED DE PAGE
    // ═══════════════════════════════════════
    $printer->feed();
    $printer->setJustification(Printer::JUSTIFY_CENTER);
    $printer->text("Merci pour votre confiance!\n");
    $printer->setEmphasis(true);
    $printer->text("A bientot!\n");
    $printer->setEmphasis(false);
    
    $printer->feed();
    $printer->text("www.votreboutique.mg\n");
    
    // Code barre (optionnel)
    $printer->feed();
    $printer->setBarcodeHeight(50);
    $printer->barcode("REC000011", Printer::BARCODE_CODE39);
    
    $printer->feed(3);
    $printer->cut();
    
    $printer->close();
    echo "✓ Reçu stylé imprimé!\n";
    
} catch (Exception $e) {
    echo "✗ Erreur: " . $e->getMessage() . "\n";
}