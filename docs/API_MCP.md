# Express Sale — Référence API MCP (v2.1.0)

> Serveur : `POST /mcp/express-sale` (Streamable HTTP, JSON-RPC 2.0, auth Bearer Sanctum).
> 61 outils. `tools/list` est paginé (15 par page, suivre `nextCursor`).
> Workflows recommandés : voir instructions du serveur et `docs/SKILL.md`.
> Parcours UI détaillés : `docs/GUIDE_UTILISATEUR.md`.

## Ventes — lecture

| Outil | Usage |
|---|---|
| `list-sales-tool` | Ventes `immediate` / `credit` / `reservation` : statut, client, recherche (n°), période, pagination |
| `list-customers-tool` | Recherche clients (nom, téléphone, n°) |
| `list-customer-history-tool` | Fiche consolidée : plafond restant, retards, 5 derniers de chaque flux |
| `sales-report-tool` | 9 rapports : `overview`, `timeline`, `top-products`, `by-category`, `by-seller`, `by-payment-method`, `discounts`, `credits`, `reservations` |
| `sales-statistics-tool` | CA, panier moyen, top produits (période) |
| `dashboard-tool` | Tableau de bord général (`today`, `week`, `month`, `year`) |

## Ventes — écriture et cycle de vie

| Outil | Usage |
|---|---|
| `create-sale-tool` | Vente immédiate : `account_id` + `items[]` (`variant_id`, `location_id`, quantité), client/remise/notes optionnels |
| `create-credit-sale-tool` | Vente à crédit : client obligatoire, `due_date`, échéances optionnelles ; plafond + retards vérifiés |
| `create-reservation-tool` | Réservation : client, `expiry_date` future, `deposit_amount` (compte requis si > 0) |
| `complete-reservation-tool` | Finalise : encaisse le restant dû sur `account_id` |
| `pay-credit-installment-tool` | Paie une échéance (`installment_id` omis = première non soldée, auto) |
| `cancel-sale-tool` / `cancel-credit-tool` / `cancel-reservation-tool` | **Statut seul** — signaler les régularisations manuelles (argent : `cancel-transaction-tool`, stock : `stock-adjustment-tool`) |

## Catalogue

| Outil | Usage |
|---|---|
| `search-products-tool` / `get-product-tool` | Recherche, détail + variantes + stock |
| `create-product-tool` / `update-product-tool` / `create-product-variant-tool` / `update-product-variant-tool` | CRUD produit et variantes (attributs complets requis, SKU régénéré) |
| `list-categories-tool` / `create-category-tool` | Catégories (`all`, `roots`, `children`, `for-sale`) |
| `list-attribute-types-tool` / `create-attribute-value-tool` | Types (couleur, taille...) + valeurs (unique par type) |
| `manage-image-tool` | Upload base64 (jpeg/png/gif/webp, 5 Mo) → chemin `image_url`/`image_path` ; suppression |

## Stock

| Outil | Usage |
|---|---|
| `list-locations-tool` / `manage-location-tool` | Emplacements + stock agrégé ; création/modif |
| `list-low-stock-tool` | Variantes sous seuil + déficit + quantité déjà en commande |
| `stock-transfer-tool` / `stock-adjustment-tool` / `declare-stock-loss-tool` | Transfert, régularisation +/-, perte FIFO avec impact financier |
| `stock-movements-report-tool` | Traçabilité : `list`, `grouped`, `statistics`, `batch` |

## Achats

| Outil | Usage |
|---|---|
| `list-suppliers-tool` / `create-supplier-tool` | Fournisseurs + score fiabilité + nb commandes |
| `list-freight-forwarders-tool` / `create-freight-forwarder-tool` | Transitaires `aerien`/`maritime` |
| `list-stock-receipts-tool` | Commandes : statut, fournisseur, transitaire, `supplier_paid`, `freight_paid`, pagination |
| `create-stock-receipt-tool` | Commande : fournisseur, transitaire optionnel, articles + coûts unitaires |
| `pay-stock-receipt-tool` | Paiement : `supplier` (coût total, anti-doublon), `freight` (montant libre), `complete` |
| `update-stock-receipt-status-tool` | Transitions : `shipped`, `in_transit`, `arrived` (quantités), `rated`, `validated` (emplacement unique), `cancelled` |
| `rate-stock-receipt-tool` | Évaluation qualité 0-10 (score fournisseur, crée les batches) |
| `allocate-stock-receipt-costs-tool` | `recommend` (weight/price/quantity) puis `apply` (par produit) |

## Finance

| Outil | Usage |
|---|---|
| `list-accounts-tool` / `list-account-types-tool` / `manage-account-tool` | Comptes + soldes, types, création/modif (solde via transactions) |
| `create-expense-tool` | Dépense opérationnelle (solde vérifié, catégorie requise) |
| `transfer-funds-tool` | Transfert entre comptes (comptes différents, solde vérifié) |
| `cancel-transaction-tool` | Annulation = transaction inverse (créateur seul, une fois) |
| `list-transactions-tool` | Historique : compte, catégorie, période, pagination |
| `list-expense-categories-tool` | Catégories de dépenses |
| `manage-planned-expense-tool` | Charges récurrentes : `list`, `create`, `update`, `mark-paid` |
| `manage-cash-count-tool` | Comptage caisse : `list`, `create` par coupures |
| `manage-company-info-tool` | Coordonnées boutique (factures) : `get`, `update` |
| `currency-tool` | Taux (`current`, `history`), `convert` en Ariary |
| `financial-report-tool` | `dashboard`, `profits`, `expenses`, `losses` |
| `financial-timeline-tool` | Évolution CA/profits (`day`, `week`, `month`) |

## Documents, audit

| Outil | Usage |
|---|---|
| `generate-document-tool` | PDF : `sale`, `credit`, `installment`, `reservation`, `reservation-receipt`, `cash-count` — base64 ou stocké (`download=true`) |
| `send-document-email-tool` | Envoi avec pièce jointe (objet/corps auto FR ; SMTP réel requis sinon log) |
| `list-activity-logs-tool` | Journaux : `logs`, `statistics`, `failures`, `by-model`, `by-category` — lecture seule |
