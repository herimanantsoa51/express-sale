# Express Sale — Guide d'utilisation détaillé

> Application de gestion de boutique : point de vente, stock, achats, trésorerie.
> Montants en Ariary (Ar). Rôles : **admin** (tout) et **vendeur** (ventes, produits, emplacements, comptages, configuration).
> Ce guide décrit les écrans, boutons et étapes exacts. Les libellés entre guillemets sont les libellés réels.

---

## 1. Navigation

Menu latéral :

| Menu | URL | Qui |
|---|---|---|
| `Dashboard` | `/dashboard` | admin, vendeur |
| `Point de vente` | `/ventes/rapide` | admin, vendeur |
| `Produits` | `/produits` | admin, vendeur |
| `Réapprovisionnement` | `/reapprovisionnements` | admin seul |
| `Mouvements de stock` | `/mouvements-stock` | admin seul |
| `Emplacements` | `/locations` | admin, vendeur |
| `Ventes rapides` | `/ventes/immediates` | admin, vendeur |
| `Réservations` | `/ventes/reservations` | admin, vendeur |
| `Crédits` | `/ventes/credits` | admin, vendeur |
| `Clients` | `/clients` | admin (route ouverte au vendeur si URL tapée directement) |
| `Fournisseurs` | `/fournisseurs` | admin seul |
| `Transitaires` | `/transitaires` | admin seul |
| `Trésorerie` | `/comptes` | admin seul |
| `Dépenses` | `/depenses` | admin seul |
| `Statistiques` | `/statistiques` | admin seul |
| `Comptages` | `/comptages` | admin, vendeur |
| `Configuration` | `/parametres` | admin, vendeur |
| `Journaux d'activité` | `/journaux-activite` | admin seul |

---

## 2. Vente rapide (comptant)

**Chemin :** Menu `Point de vente` (`/ventes/rapide`) > onglet `Vente rapide`.

### 2.1 Client (optionnel)

- Par défaut : badge `Vente anonyme`, texte `Un client anonyme sera automatiquement créé`.
- Bouton `Afficher la recherche client` → champ `Rechercher un client...` (taper 2 lettres minimum) → cliquer une suggestion → toast `Client X sélectionné`.
- Bouton `Nouveau client` → modale `Nouveau client` (nom requis, téléphone, adresse).
- Sans client : un client `Anonyme` est créé automatiquement à la validation.

### 2.2 Produits et panier

- Filtres : champ `Rechercher...`, listes `Catégories`, `Sous-catégories`, `Attributs`, `Valeurs`.
- Cartes produits : nom, prix `X Ar`, `X en stock • Y variant(s)`.
- Clic carte → modale produit :
  1. Section `Stocks par emplacement (total)` — cartes par emplacement (nom, code, badge `Principal`).
  2. Section `Choisir une variante` — boutons par SKU, badge `Indisponible` si pas de stock.
  3. Section `Stocks de cette variante` — lignes par emplacement.
  4. Section `Quantité` — sélecteur `-` / nombre / `+`.
  5. Pied : `Annuler` et `Ajouter au panier (Magasin principal)`.
- **Règle stock :** en vente rapide et crédit, seul le **MAGASIN-PRINCIPAL** avec stock > 0 est utilisable ; les autres emplacements sont grisés. En réservation, tout emplacement avec stock est sélectionnable.
- Panneau `Panier` à droite : lignes avec `-` / quantité / `+` (bloqué au max dispo), corbeille.
- Bouton `Remise` → champ montant + `Ar` (plafonné au sous-total : `La remise ne peut pas dépasser X Ar`) + champ `Raison de la remise...`.

### 2.3 Paiement et validation

- Bloc `Méthode de paiement` : boutons `Espèces` / `Mobile Money` + liste déroulante du compte (`Nom • N° compte`).
- Bouton `Valider la vente` (grisé si panier vide ou pas de compte) → modale `Confirmer cette vente` (`Client`, `Articles : X produit(s)`, `Total : X Ar`, boutons `Annuler` / `Confirmer`).
- Après validation : toast, panier vidé, **pas de redirection** — retrouver la vente via Menu `Ventes rapides`.
- **Brouillon auto :** saisie sauvegardée localement 2h, toast `Vente en cours restaurée (X articles)` au retour.

### 2.4 Détail et annulation

Menu `Ventes rapides` > recherche `Rechercher par numéro, client...` > clic ligne → fiche avec boutons `Retour`, `Imprimer`, `Télécharger`, `Envoyer` (email, si le client a un email).

- Si statut `CONFIRMED` : bouton `Annuler la vente` → modale `Annuler cette vente ?` (`Vous êtes sur le point d'annuler la vente X.`).
- **Politique d'annulation (règle métier) : l'annulation marque UNIQUEMENT le statut comme annulé. Ni remboursement automatique, ni remise en stock.** L'opérateur régularise manuellement : annuler la transaction dans `Trésorerie / Transactions`, remettre en stock via un ajustement.

---

## 3. Vente à crédit

**Chemin :** Menu `Point de vente` > onglet `Vente à crédit` > bouton `Créer le crédit`.

- **Client obligatoire** (sans client, bouton grisé + `Veuillez sélectionner un client`).
- Bloc crédit sous les totaux :
  1. `Date limite` — champ date requis (min aujourd'hui).
  2. `Échéances (optionnel)` — lignes `date` + `Montant` + corbeille ; bouton `Ajouter échéance` ; si > 1 échéance : bouton `Répartir équitablement`.
  3. Indicateur : `Somme des échéances : X Ar ✓` ou alerte si différence.
- Règles : somme des échéances = `Total` à 1 Ar près (`La somme des échéances (...) doit être égale au total (...)`), chaque échéance doit avoir une date. **Plafond de crédit du client vérifié** + refus si crédits en retard.
- **Particularité :** quand des échéances sont saisies, le bloc `Méthode de paiement` est **masqué** mais un compte reste exigé en interne — le compte précédemment sélectionné est conservé silencieusement.

### Suivi : Menu `Crédits` (`/ventes/credits`)

- Titre `Gestion des crédits`, recherche `Rechercher par numéro, client...`, filtres, tableau, pagination.
- Détail : cartes `Montant total`, `Montant payé`, `Reste à payer` ; bloc `Échéances (X)` ; boutons `Annuler le crédit`, `Imprimer`, `Télécharger`.
- **Payer une échéance :** bouton payer → modale `Effectuer un paiement` (`Échéance n°`, `Montant total dû`, `Reste à payer` ; champs `Montant à payer *`, `Compte de paiement *`, `Notes (optionnel)` ; boutons `Annuler` / `Valider le paiement`). Validations : `Montant invalide`, `Montant supérieur au reste à payer`, `Veuillez sélectionner un compte`. Bloqué si crédit annulé : `Impossible d'effectuer un paiement sur un crédit annulé`.
- **Annulation :** bouton `Annuler le crédit` → modale avec avertissements (`Restock des produits`, `Pas d'annulation de transactions`, `Transactions confirmées détectées` si paiements). Après : toast `Crédit annulé avec succès` + **rechargement complet de la page**. **Remboursements et stock : régularisation manuelle par l'opérateur.**

---

## 4. Réservation

**Chemin :** Menu `Point de vente` > onglet `Réservation` > bouton `Créer la réservation`.

- **Client obligatoire**, pas de mode anonyme.
- Produits : tout emplacement avec stock cliquable, boutons `Sélectionner` / `✓ Sélectionné`, bouton final `Réserver (Nom emplacement)`.
- Bloc réservation :
  1. `Date limite de retrait` — date requise (min aujourd'hui).
  2. `Acompte` — champ + `Ar`, plafonné au total (`L'acompte (...) ne peut pas dépasser le total (...)`) ; raccourcis `10%`, `25%`, `50%`, `100%` ; si 100 % : `(Paiement complet)`.
- **Si acompte > 0, un compte de trésorerie est requis** pour l'encaisser.

### Suivi : Menu `Réservations` (`/ventes/reservations`)

- Recherche `Rechercher par numéro, client...`, filtre statuts (`Tous les statuts`, `En attente`, `Confirmée`, `Partiellement payée`, `Terminée`, `Annulée`, `Expirée`), bouton `Filtres` (dates réservation/expiration, `Actives uniquement`, `Expirées uniquement`), pagination.
- Détail : boutons `Télécharger`, `Imprimer`, `Compléter` (si reste > 0), `Annuler` ; alertes `Expire dans X jour(s)`, `Réservation expirée`, `Annulée : raison` ; panneau `Résumé` (`Total`, `Payé`, `Reste`, progression `%`).
- **Finalisation (`Compléter`) :** modale `Compléter la réservation` (n°, client, total, reste ; `Compte de paiement *` en cartes cliquables ; `Notes (optionnel)`). **Le montant n'est pas saisissable : c'est toujours le solde restant payé en une fois.** Validation : `Veuillez sélectionner un compte`.
- **Annulation :** bouton `Annuler` → **boîte de dialogue système du navigateur** (`Raison de l'annulation :`, prompt natif — pas une modale applicative). Vide ou annulé = rien ne se passe. **Acompte et stock : régularisation manuelle par l'opérateur.**
- **Expiration automatique :** tâche planifiée toutes les 6h — **statut seul (`expired`), sans remboursement ni libération de stock**, régularisation manuelle.

---

## 5. Réception de stock (commande fournisseur)

**Chemin :** Menu `Réapprovisionnement` (`/reapprovisionnements`), admin seul. Bouton `+ Nouvelle réception` (`/reapprovisionnements/nouveau`).

### 5.1 Liste

- Cartes stats : `Total`, `En cours`, `En retard`, `Ce mois` ; `Répartition par statut` (`En attente`, `Envoyé`, `En transit`, `Arrivé`, `Validé`, `Annulé`) ; `Valeur en cours`, `Valeur validée`.
- Filtres : recherche `Rechercher par numéro ou notes...`, `Statut`, dates création et livraison prévue, `Uniquement les retards`.
- Tableau : `N° Réception`, `Fournisseur` (lien fiche), `Transitaire`, `Date prévue`, `Statut`, `Articles`, `Montant`, `Actions` (œil `Voir les détails`).

### 5.2 Création (assistant en 3 étapes)

Stepper : 1. `Devise & Produits` → 2. `Fournisseur & Transport` → 3. `Paiement`.

- **Étape 1 :** sélecteur devise (EUR €, USD $, CNY ¥, MAD, THB ฿) avec taux en Ariary ; recherche produit ; clic variante ajoute (doublon bloqué : `Cette variante est déjà ajoutée`). Validations : `Veuillez ajouter au moins un article`, `Toutes les quantités doivent être supérieures à 0`, `Tous les prix doivent être supérieurs à 0`.
- **Étape 2 :** `Date de livraison prévue *` (min aujourd'hui), `Notes de commande`, `Fournisseur *` (recherche, **obligatoire**), `Transitaire (optionnel)` + coût transport en devise. Validations : `Veuillez sélectionner un fournisseur`, `Veuillez sélectionner une date de livraison prévue`.
- **Étape 3 :** `Récapitulatif de la commande` (totaux devise + Ariary) ; carte `Paiement Fournisseur` + badge `Obligatoire` (`Le paiement au fournisseur doit être effectué immédiatement`) ; `Choisir le compte de débit *` (cartes avec `Solde actuel`, `À débiter`, `Solde après`, alerte `Fonds insuffisants`) ; `Date de transaction (optionnelle)`, `Notes`. Bouton final `Créer le Réapprovisionnement` (grisé si `Fonds insuffisants`).
- Après : écran `Bon de commande créé avec succès !` puis détail après 2s. **Brouillon conservé 24h** et restauré auto.

### 5.3 Progression des statuts (boutons manuels, ordre strict)

| Statut actuel | Boutons visibles |
|---|---|
| `En attente` | `Marquer envoyé`, `Annuler` |
| `Envoyé` | `Marquer en transit`, `Annuler` |
| `En transit` | `Confirmer arrivée`, `Annuler` |
| `Arrivé` | `Évaluer`, `Paiement` |
| `Évalué` | `Répartir les coûts`, `Paiement` |
| `Coûts répartis` | `Valider définitivement`, `Modifier la répartition des coûts` |
| `Validé` | `Voir la répartition des coûts` |

- `Confirmer arrivée` → modale `Réception des articles` (quantités réellement reçues par article, `Confirm
er la réception (X unités)`, grisé si total reçu = 0).
- `Évaluer` → page `/evaluation` : curseurs qualité 0–10 par article (+ conformité des attributs), bouton `Enregistrer et valider (X/Y)` bloqué tant que tout n'est pas évalué, modale `Confirmer et valider la réception` (`⚠️ Cette action est irréversible. Les batches seront créés automatiquement.`).
- `Répartir les coûts` → page `/cout-repartition` : modale d'avertissement `Attention : Dernière chance` (`Une fois la répartition effectuée, vous ne pourrez plus ajouter de paiements.`) ; `Méthode de répartition` (`Par prix`, `Par quantité`, `Par pondération prix×quantité`) ; tableau avec `Transport/u`, `Autres/u`, `Marge %`, `Prix vente` modifiables ; bouton `Enregistrer tout` ; modale `Mise à jour des prix de vente` si prix modifiés.
- `Valider définitivement` → modale `Validation de réception` : **un seul emplacement pour tous les articles** (`Tous les articles seront stockés au même emplacement *`), `Notes (optionnel)`.
- `Paiement` (statuts Arrivé/Évalué) → page `/paiements` : étapes `Compte` → `Type` (`Paiement transitaire` ou `Autre dépense`, un seul paiement transitaire possible) → `Détails` (montant, date, destinataire, catégorie + `+ Nouvelle catégorie`) → `Confirmation`.
- `Annuler` → confirmation `Êtes-vous sûr de vouloir annuler cette réception ? Cette action est irréversible.` Impossible si déjà validée.

---

## 6. Mouvements de stock

**Chemin :** Menu `Mouvements de stock` (`/mouvements-stock`), admin seul. Boutons `Déclarer une perte`, `Nouveau Transfert`. La réconciliation (`/mouvements-stock/reconciliation`) n'a **pas de bouton visible** — accès par URL directe.

- **Liste :** stats (`Mouvements`, `Entrées`, `Sorties`, `Transferts`), filtres type (`Tous les types`, `Transferts`, `Réceptions`, `Ventes`, `Pertes`, `Retours`) et période, vues groupée/détaillée, liens vers documents sources (`Réappro #id`, `Vente #n°`, `Réservation #n°`, `Credit #n°`).
- **Transfert (`Nouveau Transfert`) :** étapes `Source` (cartes emplacements) → `Produits` (recherche, quantités, `Max: X`) → `Destination et confirmation` (`Raison du transfert (optionnel)`, `Notes (optionnel)`, récapitulatif). Final : écran `Transfert effectué avec succès !` puis retour liste après 2s.
- **Perte (`Déclaration de Perte`) :** `Emplacement *` → produit → `Type de perte *` (`Casse`, `Vol`, `Péremption`, `Dommage`, `Écart`, `Autre`) → `Quantité *` (plafonnée au dispo) → `Raison détaillée *` → étape `Confirmation de sécurité` : **saisir exactement `SKU ANNULER`** (ex. `CHAUSS-42 ANNULER`) pour valider. Action irréversible, consommation FIFO automatique.
- **Réconciliation :** `Emplacement *`, `Date d'inventaire *`, tableau `Comptage physique` vs `Stock système` avec écarts (`0`, `+X` orange, `-X` rouge) ; bouton `Valider la réconciliation (X écart(s))` **grisé s'il n'y a aucun écart** ; note obligatoire pour tout produit manquant (`Veuillez ajouter une note pour tous les produits avec stock manquant`). Excédent = alerte à vérifier ; manquant = perte automatique en FIFO.

---

## 7. Dépenses, trésorerie, caisse

### 7.1 Dépenses (`/depenses`)

- Résumé (`Total des dépenses`, `Catégories utilisées`), `Dépenses par catégorie` (cartes cliquables), filtres dates/comptes/catégories, liste `Transactions (X)`, pagination.
- Boutons `+ Nouvelle catégorie`, `Dépenses planifiées`, `+ Nouvelle dépense` (`/depenses/nouveau`).
- **Création :** étapes `Compte` (cartes avec `Solde X Ar`) → `Catégorie` (grille + `Nouvelle catégorie` : `Nom *`, `Description`, `Icône`, switch `Catégorie active`) → `Détails` (`Montant *`, `Date de transaction *`, `Destinataire / Bénéficiaire`, `Notes`, contrôle `Solde insuffisant. Le compte dispose de X Ar`) → `Confirmation`. Final : `Dépense enregistrée avec succès!` → `/depenses` après 1,5s.
- **Dépenses planifiées (`/depenses/planifie`) :** stats (`Revenu mensuel minimum`, `Dépenses actives`, `En retard`, `À venir (7j)`), filtres fréquence/statut, cartes → détail (`Modifier`, `Payer maintenant` → `/depenses/nouveau?plannedExpenseId=:id` avec catégorie verrouillée). **Création :** `Catégorie de dépense`, `Informations` (`Nom de la charge *`, `Montant estimé *`, destinataire, description), `Récurrence` (`Quotidienne`, `Hebdomadaire` + `Jour de la semaine *`, `Mensuelle` + `Jour du mois`, `Annuelle`), `Période` (`Date de début *`, fin optionnelle).

### 7.2 Transfert entre comptes (`/comptes/transfert`)

- `Compte source *` (cartes + soldes) → `Compte destination *` (source grisée) → `Montant *` + `Ar` (aide `Disponible: X Ar`) → `Description` (optionnel) → aperçu `Nouveau solde` des deux comptes → `Effectuer le transfert`. Erreurs : `Sélectionnez un compte source/destination`, `Le montant doit être supérieur à zéro`, `Solde insuffisant sur le compte source`. Après : `Transfert effectué — X Ar transférés avec succès.` → `/comptes` après 2s.

### 7.3 Comptage de caisse (`/comptages`)

- Liste avec filtres dates ; bouton `+ Nouveau comptage` (`/comptages/nouveau`).
- Formulaire : `Date du comptage`, `Notes (optionnel)`, lignes de coupures (`20 000 Ar` … `100 Ar`) avec quantités, badge `Total: X Ar`. Validation : `Veuillez saisir au moins une coupure`.
- Détail : `Montant total`, `Détail des coupures`, boutons `Rapport PDF`, `Imprimer`, `Modifier`.
- **Particularité :** constat simple — **pas de comparaison automatique** avec le solde du compte caisse, pas d'écart calculé.

---

## 8. Clients et produits

### 8.1 Clients (`/clients`)

- Recherche, filtres statut/fiabilité, cartes (nom, score `X.X /10`, badges `Excellent`/`Bon`/`Moyen`/`À risque`, `VIP`, `Risque`, stats ventes/crédits/réservations, points fidélité).
- **Création :** pas de bouton visible quand la liste est non vide — passer par l'état vide (`Créer un client`) ou `Point de vente` > `Nouveau client`. Champs : `Nom du client` requis, `Téléphone`, `Adresse`, `Notes`, switches `Client actif`, `Client anonyme`.

### 8.2 Produits (`/produits`)

- Filtres : `Search products`, `All Categories`, `All Subcategories`, `Min price — Max price`, `Active products only`, `Low stock`. Boutons `Attributes`, `Nouveau Produit` (`/produits/nouveau`).
- **Création/édition :** colonne `Informations générales` (`Nom du produit` requis, `Description`, `Catégorie` requise + `+ Nouvelle`, `Sous-catégorie`, `Prix de base (Ar)` requis, `Image du produit` — PNG/JPG/WEBP max 5 Mo, case `Produit actif`) ; colonne `Attributs & Variantes` (boutons `Nouveau type` + `Ajouter`, lignes `#X Nom` + type + case `Obligatoire`). Après : `Produit créé / modifié avec succès !` → `/produits`. **Un produit sans variantes ne peut pas être vendu.**

---

## 9. Règles métier importantes (à connaître)

1. **Annulation = statut seul.** Annuler une vente, un crédit, une réservation (ou expiration auto) ne rembourse rien et ne remet rien en stock. Régularisations manuelles : annuler la transaction dans `Trésorerie / Transactions`, ajuster le stock.
2. **Stock réservation :** le stock est bloqué dès la création de la réservation (pas de double-vente). Libération manuelle si annulation/expiration.
3. **Crédit :** plafond du client et retards vérifiés à la création ; paiement d'échéance plafonné au restant dû.
4. **Réception :** ordre strict des statuts (boutons manuels) ; fournisseur payé à la création ; un seul paiement transitaire ; plus de paiements après répartition des coûts ; **un seul emplacement** à la validation.
5. **Remise et acompte** plafonnés respectivement au sous-total et au total.
6. **Vente rapide/crédit :** uniquement le MAGASIN-PRINCIPAL ; produits/variantes désactivés non vendables.
