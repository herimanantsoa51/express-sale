# 🎯 CAHIER DES CHARGES COMPLET - BOUTIQUE OFFLINE

## 📐 Architecture technique

- **Backend** : Laravel (API REST)
- **Frontend** : React (SPA)
- **Base de données** : PostgreSQL
- **Déploiement** : **Application locale OFFLINE** (pas de cloud)
- **Réseau** : LAN local ou machine unique
- **Utilisateurs** : 2 vendeurs + 1 admin

---

## 🔄 WORKFLOW MÉTIER COMPLET

### 1️⃣ **RÉSERVATIONS CLIENTS**

#### Règles métier
- ✅ Client réserve un produit
- ⏰ Délai de paiement : **configurable** (ex: 48h, 7 jours)
- 🔒 Stock réservé = **bloqué** mais pas vendu
- ❌ Dépassement délai → **annulation automatique** + remise en stock
- 💰 Paiement partiel possible (acompte)
- 📱 Notification : statut réservation

#### États d'une réservation
```
pending → confirmed → partial_paid → completed
   ↓
expired (remise en stock automatique)
```

---

### 2️⃣ **VENTES À CRÉDIT (NOUVEAU WORKFLOW)**

#### Règles métier CRITIQUES
- ✅ Client reçoit le produit **immédiatement**
- 🔒 Stock **physiquement sorti** mais **comptablement bloqué**
- ❌ Stock **non disponible** pour autres ventes tant que crédit actif
- 💵 Paiement échelonné ou différé
- 📊 Suivi strict des échéances
- 🚫 Si défaut paiement : procédure récupération

#### États d'un crédit
```
active → partial_paid → completed
   ↓
overdue (en retard) → defaulted (défaut paiement)
```

#### Stock virtuel
```
Stock physique : 10 pièces
  - Réservations actives : -2 (bloqué)
  - Crédits actifs : -3 (sorti mais comptablement bloqué)
  = Stock disponible vente : 5 pièces
```

---

### 3️⃣ **VENTES CLASSIQUES**

#### Types de paiement
1. **Espèces** : immédiat
2. **Mobile Money** : 
   - Numéro client
   - Frais d'envoi enregistrés
3. **Mixte** : combinaison espèces + mobile money

#### Remises
- Montant fixe en Ariary
- Appliquée sur le total panier
- Enregistrement du motif

---

## 📋 FONCTIONNALITÉS COMPLÈTES

### 🔐 MODULE AUTHENTIFICATION & UTILISATEURS

#### Features
- [x] Login/Logout
- [x] 2 rôles : `admin` | `vendeur`
- [x] Session tracking automatique (login_at / logout_at)
- [x] Dashboard selon rôle
- [x] Vendeurs : accès limité (vente + stock uniquement)
- [x] Admin : accès complet + statistiques + configuration

---

### 📦 MODULE PRODUITS & STOCK

#### Features
- [x] Gestion catégories (hiérarchique : catégorie → sous-catégorie)
- [x] CRUD produits avec :
  - Nom, description
  - Prix de vente
  - Emplacement physique (rayon, étagère)
  - Fournisseur associé
  - Image produit
- [x] **Système d'attributs dynamiques** :
  - Définir attributs par type produit (pointure, taille, couleur, longueur)
  - Valeurs prédéfinies ou saisie libre
  - Attributs obligatoires/optionnels
- [x] **Gestion variantes** :
  - Génération automatique SKU
  - Stock par variante
  - Prix ajusté par variante (optionnel)
  - Emplacement précis par variante
- [x] **États du stock** :
  - Stock physique total
  - Stock réservé (réservations actives)
  - Stock en crédit (vendu à crédit)
  - **Stock disponible** = physique - réservé - crédit
- [x] Alertes stock bas (seuil configurable)
- [x] Historique mouvements stock (entrées/sorties)
- [x] Recherche produit :
  - Par nom
  - Par catégorie
  - Par attribut (ex: toutes les pointures 42)
  - Par emplacement
- [x] Vue stock : filtres multiples + export

---

### 🛒 MODULE VENTE (POINT DE VENTE)

#### Interface vendeur
- [x] Recherche produit rapide (scan code-barre ou recherche)
- [x] Sélection produit → affichage variantes disponibles :
  - Attributs (pointure, couleur, etc.)
  - Stock disponible réel
  - Emplacement
  - Prix
- [x] Ajout panier avec quantité
- [x] Panier dynamique :
  - Modification quantités
  - Suppression articles
  - Calcul total temps réel
- [x] Application remise (montant fixe Ariary + motif)
- [x] Sélection/création client
- [x] **Choix type vente** :
  1. **Vente immédiate** (cash/mobile money)
  2. **Réservation** (acompte optionnel)
  3. **Vente à crédit** (produit livré, paiement différé)
- [x] **Paiement vente immédiate** :
  - Cash
  - Mobile Money (numéro + frais)
  - Paiement mixte
  - Sélection compte destinataire
- [x] Génération reçu/facture (imprimable)
- [x] Mise à jour automatique stock selon type vente

#### Validation
- ❌ Bloquer si stock disponible insuffisant
- ❌ Bloquer si client crédit en défaut
- ✅ Vérifier limites crédit client (configurable)

---

### 📅 MODULE RÉSERVATIONS

#### Features
- [x] Liste réservations actives
- [x] Filtres : statut, client, date, produit
- [x] **Gestion par réservation** :
  - Voir détails (produits, montants, délai restant)
  - Encaisser acompte
  - Finaliser vente (paiement solde)
  - Annuler manuellement
  - Prolonger délai
- [x] **Système automatique** :
  - Job quotidien : check réservations expirées
  - Annulation auto + remise en stock
  - Notification vendeur
- [x] Historique réservations (complétées/annulées)
- [x] Statistiques : taux conversion réservation → vente

---

### 💳 MODULE CRÉDITS

#### Features
- [x] Liste crédits actifs
- [x] Vue par statut :
  - À jour
  - Échéance proche (< 7 jours)
  - En retard
  - Défaut paiement
- [x] **Gestion crédit** :
  - Voir détails vente associée
  - Enregistrer paiement partiel
  - Calculer intérêts retard (optionnel)
  - Solder crédit
  - Marquer défaut paiement
- [x] **Récupération produit** :
  - Si défaut grave : procédure récupération
  - Remise en stock si produit récupéré
  - Enregistrement perte si irrécupérable
- [x] Planification échéances (paiements prévus)
- [x] Notifications automatiques :
  - J-3 avant échéance
  - Le jour J
  - Retard J+1, J+7
- [x] Historique paiements par crédit
- [x] Impact score fiabilité client

---

### 👤 MODULE CLIENTS

#### Features
- [x] CRUD clients (nom, téléphone, email, adresse)
- [x] Numéro mobile money principal
- [x] **Score fiabilité** (0-10) :
  - Calculé automatiquement selon :
    - Historique paiements crédits
    - Retards
    - Défauts
    - Réservations non honorées
- [x] **Score fidélité** :
  - Points selon achats
  - Niveaux : Bronze, Silver, Gold
- [x] Vue client :
  - Achats totaux (montant + nombre)
  - Réservations actives
  - Crédits en cours
  - Historique complet
  - Crédit autorisé (limite selon score)
- [x] Filtres : fiabilité, fidélité, dette en cours
- [x] Notes vendeur (commentaires internes)
- [x] Export base clients

---

### 🏭 MODULE FOURNISSEURS

#### Features
- [x] CRUD fournisseurs :
  - Nom, WeChat, contact
  - Profil (spécialités)
  - Accessibilité (délais, conditions)
  - Logo/photo
- [x] **Score fiabilité fournisseur** :
  - Ratio quantité commandée/reçue
  - Respect délais
  - Qualité produits
- [x] Historique commandes par fournisseur
- [x] Liste produits par fournisseur
- [x] Analyse performance fournisseur

---

### 🚛 MODULE TRANSITAIRES

#### Features
- [x] CRUD transitaires :
  - Nom, logo, contact, localisation
- [x] **Score service** (0-10) :
  - Ponctualité
  - État colis
  - Tarifs
  - Évaluations manuelles
- [x] Historique livraisons
- [x] Comparaison coûts/performances

---

### 📥 MODULE RÉAPPROVISIONNEMENT

#### Features
- [x] Création réception stock :
  - Sélection fournisseur
  - Sélection transitaire
  - Date réception
  - Coût total (Yuan + Ariary)
  - Taux conversion du jour
- [x] **Ajout articles réception** :
  - Sélection produit/variante
  - Quantité commandée
  - Quantité reçue (peut différer)
  - Coût unitaire
- [x] **Comparaison commandé/reçu** :
  - Alertes si écart > 10%
  - Impact score fournisseur
  - Enregistrement réclamation
- [x] Validation réception → mise à jour stock automatique
- [x] Génération bon de réception
- [x] Historique réceptions
- [x] Analyse coûts d'achat

---

### 💰 MODULE DÉPENSES

#### Features
- [x] Catégories dépenses configurables :
  - Salaires
  - Loyer
  - Électricité
  - Transport
  - Marketing
  - Autres
- [x] Enregistrement dépense :
  - Catégorie
  - Montant
  - Date
  - Moyen paiement (compte débité)
  - Pièce jointe (facture scan)
  - Notes
- [x] Dépenses récurrentes :
  - Planification automatique (ex: loyer mensuel)
  - Alerte si non enregistrée
- [x] Validation admin (optionnel)
- [x] Historique et export
- [x] Analyse dépenses :
  - Par catégorie
  - Par période
  - Évolution

---

### 🏦 MODULE TRÉSORERIE

#### Features
- [x] **Comptes multiples** :
  - Espèces (plusieurs caisses possibles)
  - Mobile Money (Orange, Airtel, Telma, etc.)
  - Comptes bancaires
- [x] Solde en temps réel par compte
- [x] **Mouvements automatiques** :
  - Vente → crédit compte
  - Dépense → débit compte
  - Transfert inter-comptes
- [x] Ajustements manuels (correction, apport capital)
- [x] Rapprochement bancaire
- [x] Historique exhaustif mouvements
- [x] Vue consolidée (solde global)
- [x] Projection trésorerie (J+7, J+30)
- [x] Alertes trésorerie basse

---

### 💱 MODULE CONVERSION DEVISES

#### Features
- [x] Gestion taux Yuan ↔ Ariary
- [x] Saisie taux du jour
- [x] Historique taux
- [x] Calculatrice conversion temps réel
- [x] Application automatique lors réapprovisionnements
- [x] Analyse impact variation taux sur marges

---

### 📊 MODULE STATISTIQUES & RAPPORTS

#### Dashboard admin
- [x] **Vue d'ensemble** :
  - Chiffre affaires jour/semaine/mois
  - Nombre ventes
  - Nombre clients
  - Bénéfice brut
  - Stock total (valeur)
  - Trésorerie totale
- [x] **Graphiques** :
  - Évolution ventes (ligne temps)
  - Ventes par catégorie (camembert)
  - Ventes par heure (bar chart)
  - Top produits vendus
  - Performance vendeurs
- [x] **Analyses avancées** :
  - Marge par produit/catégorie
  - Rotation stock
  - Taux de conversion réservations
  - Taux recouvrement crédits
  - Panier moyen
  - Client moyen
- [x] **Rapports générables** :
  - Rapport ventes (quotidien, hebdo, mensuel)
  - Rapport stock
  - Rapport clients
  - Rapport financier complet
  - Export Excel/PDF

#### Statistiques vendeur
- [x] Ventes personnelles (jour/semaine/mois)
- [x] Objectifs/performances
- [x] Temps connexion

---

### ⚙️ MODULE CONFIGURATION

#### Paramètres généraux
- [x] Informations boutique (nom, adresse, logo)
- [x] Délai réservation par défaut
- [x] Seuil stock bas
- [x] Limite crédit par niveau client
- [x] Taux intérêt retard crédit
- [x] Devise principale (Ariary)
- [x] Format reçus/factures
- [x] Notifications activées/désactivées

#### Gestion utilisateurs (admin)
- [x] Créer/modifier/désactiver vendeurs
- [x] Réinitialiser mots de passe
- [x] Voir sessions actives
- [x] Logs actions utilisateurs

---

### 🔔 MODULE NOTIFICATIONS

#### Types notifications
- [x] Réservation proche expiration
- [x] Réservation expirée
- [x] Crédit échéance proche
- [x] Crédit en retard
- [x] Stock bas
- [x] Alerte trésorerie
- [x] Vente importante
- [x] Nouvelle réception stock

#### Canaux
- [x] In-app (interface)
- [x] Email (optionnel si connexion internet)

---

### 🔍 MODULE AUDIT & LOGS

#### Features
- [x] Traçabilité complète :
  - Qui a fait quoi
  - Quand
  - Sur quel enregistrement
- [x] Logs critiques :
  - Modifications stock
  - Annulations ventes
  - Modifications prix
  - Suppressions
- [x] Consultation logs (admin)
- [x] Export logs

---

## 🎨 INTERFACE REACT - ÉCRANS PRINCIPAUX

### 1. Login
- Formulaire simple
- Redirection selon rôle

### 2. Dashboard vendeur
- Bouton "Nouvelle vente" (large)
- Réservations à traiter
- Crédits échéance proche
- Stats journée

### 3. Point de vente
- Recherche produit (barre + scan)
- Grille variantes disponibles (cards)
- Panier latéral
- Bloc client
- Bloc paiement
- Récapitulatif

### 4. Gestion réservations
- Liste cards/table
- Filtres statut
- Actions rapides

### 5. Gestion crédits
- Liste avec badges statut
- Vue détail crédit
- Formulaire paiement partiel

### 6. Gestion stock
- Vue grille/liste produits
- Filtres avancés
- Modal ajout/édition
- Historique mouvements

### 7. Dashboard admin
- Widgets KPIs
- Graphiques interactifs
- Raccourcis modules

### 8. Autres modules
- Formulaires standard CRUD
- Tables paginées
- Modales confirmation

---
