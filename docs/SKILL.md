---
name: express-sale-guide
description: Guide Express Sale boutique, ventes et API MCP.
version: 2.1.0
author: Express Sale
license: MIT
platforms: [linux, macos, windows]
metadata:
  hermes:
    tags: [Express Sale, Boutique, Ventes, Stock, MCP]
    category: productivity
---

# Express Sale — Guide applicatif (boutique, ventes, API MCP)

Utiliser `mcporter` pour appeler le serveur MCP Express Sale depuis le `terminal`
(`mcporter list --http-url <URL> --name express-sale`, puis `mcporter call express-sale.<tool>`).
Le serveur expose 61 outils en lecture et écriture (auth Bearer Sanctum, headers
`Content-Type: application/json` + `Accept: application/json, text/event-stream`).
Référence complète des outils : `docs/API_MCP.md`.
Parcours détaillés écran par écran (menus, boutons, champs) : `docs/GUIDE_UTILISATEUR.md`.

## Vendre

Workflow : 1) `list-locations-tool` + `list-accounts-tool`, 2) `search-products-tool`
(consulter `list-customer-history-tool` avant un crédit), 3) `create-sale-tool` /
`create-credit-sale-tool` / `create-reservation-tool`, 4) `complete-reservation-tool`
ou `pay-credit-installment-tool` pour encaisser, ou `cancel-*-tool` pour annuler.
Vente rapide/crédit : seul le MAGASIN-PRINCIPAL. Réservation : tout emplacement
avec stock. Client obligatoire en crédit/réservation (anonyme auto en rapide).
Acompte > 0 : compte requis. Remise et acompte plafonnés. Crédit : plafond client
et retards vérifiés, somme des échéances = total. Finalisation réservation =
solde restant en une fois.

## Règle d'annulation (critique)

Annuler (vente, crédit, réservation, expiration auto toutes les 6h) marque
UNIQUEMENT le statut. Ni remboursement auto, ni remise en stock. Toujours lister
les régularisations manuelles restantes : argent via `cancel-transaction-tool`
(une transaction à la fois), stock via `stock-adjustment-tool`.

## Acheter (commandes fournisseur)

Workflow : 1) `list-low-stock-tool`, 2) `list-suppliers-tool` +
`list-freight-forwarders-tool`, 3) `create-stock-receipt-tool`, 4) `pay-stock-receipt-tool`
(supplier/freight/complete, anti-double-paiement fournisseur), 5) `update-stock-receipt-status-tool`
(shipped, in_transit, arrived avec quantités), 6) `rate-stock-receipt-tool` (crée les
batches) puis `allocate-stock-receipt-costs-tool` (recommend puis apply),
7) `update-stock-receipt-status-tool` action=validated avec emplacement
(un seul pour tous les articles). Fournisseur payé à la création côté frontend ;
un seul paiement transitaire ; plus de paiements après répartition des coûts.

## Stock et finance

Transferts, ajustements, pertes (FIFO, irréversible), réconciliation (sans bouton
UI, URL directe). Dépenses (catégories verrouillables si planifiée), transferts
entre comptes, dépenses planifiées (fréquences + mark-paid), comptage de caisse
(constat simple, sans écart auto), devises (euro, yuan, dollar, dirham, baht).
Documents PDF (factures, reçus, rapports) + `send-document-email-tool` (demander
l'email du destinataire ; SMTP réel requis, sinon journalisation).
`sales-report-tool`, `financial-report-tool`, `financial-timeline-tool`,
`dashboard-tool`, `stock-movements-report-tool`, `list-activity-logs-tool`
(lecture seule).

## Créer un produit complet

1) `list-categories-tool` + `list-attribute-types-tool`, 2) `create-product-tool`,
3) `create-product-variant-tool` avec attributs (`create-attribute-value-tool` si
valeur manquante). Produit sans variantes = invendable. Produits/variantes
désactivés non vendables.
