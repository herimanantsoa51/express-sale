-- ============================================
-- SCHÉMA POSTGRESQL - GESTION BOUTIQUE OFFLINE
-- Architecture: Laravel API + React Frontend
-- Déploiement: Local/LAN uniquement
-- ============================================

-- Extension pour génération UUID (utile pour tokens, références uniques)
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

-- ============================================
-- 1. AUTHENTIFICATION & UTILISATEURS
-- ============================================

-- Table des utilisateurs (vendeurs + admin)
-- Utilisée par Laravel Sanctum pour l'authentification API
CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL, -- Nom complet de l'utilisateur
    username VARCHAR(255) UNIQUE NOT NULL, -- Email unique (login)
    password VARCHAR(255) NOT NULL, -- Hash bcrypt du mot de passe
    role VARCHAR(50) DEFAULT 'vendeur' CHECK (role IN ('admin', 'vendeur')), -- Rôle pour permissions
    is_active BOOLEAN DEFAULT true, -- Permet de désactiver sans supprimer
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

COMMENT ON TABLE users IS 'Utilisateurs internes (admin + vendeurs)';
COMMENT ON COLUMN users.role IS 'admin: tous droits | vendeur: vente + stock uniquement';
COMMENT ON COLUMN users.is_active IS 'Désactiver un utilisateur sans perdre son historique';

-- Sessions de connexion : tracking automatique login/logout
-- Permet de voir qui travaille quand et durée des sessions
CREATE TABLE user_sessions (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    login_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, -- Heure connexion
    logout_at TIMESTAMP, -- NULL = session en cours
    ip_address VARCHAR(45), -- IP pour traçabilité (LAN)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

COMMENT ON TABLE user_sessions IS 'Historique des connexions pour suivi du temps de travail';
COMMENT ON COLUMN user_sessions.logout_at IS 'NULL si session active, rempli au logout';

CREATE INDEX idx_user_sessions_user_id ON user_sessions(user_id);
CREATE INDEX idx_user_sessions_login_at ON user_sessions(login_at);

-- ============================================
-- 2. CATÉGORIES & PRODUITS (MODÈLES)
-- ============================================

-- Catégories hiérarchiques (catégorie → sous-catégorie)
-- Ex: Chaussures (parent) → Stan Smith (enfant)
CREATE TABLE categories (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL, -- Nom de la catégorie
    description TEXT, -- Description optionnelle
    parent_id INTEGER REFERENCES categories(id) ON DELETE SET NULL, -- Sous-catégorie si non NULL
    image_url VARCHAR(500), -- Image pour frontend
    sort_order INTEGER DEFAULT 0, -- Ordre d'affichage
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

COMMENT ON TABLE categories IS 'Catégories hiérarchiques produits (2 niveaux max recommandé)';
COMMENT ON COLUMN categories.parent_id IS 'NULL = catégorie principale, sinon = sous-catégorie';

CREATE INDEX idx_categories_parent_id ON categories(parent_id);

-- Produits = MODÈLES génériques (ex: "Stan Smith")
-- Les variantes vendables sont dans product_variants
CREATE TABLE products (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL, -- Nom du produit modèle
    description TEXT, -- Description détaillée
    category_id INTEGER NOT NULL REFERENCES categories(id) ON DELETE RESTRICT, -- Catégorie principale obligatoire
    subcategory_id INTEGER REFERENCES categories(id) ON DELETE SET NULL, -- Sous-catégorie optionnelle
    base_price DECIMAL(12, 2) NOT NULL CHECK (base_price >= 0), -- Prix de vente de base (Ariary)
    location VARCHAR(255), -- Emplacement physique général (ex: "Rayon A-3")
    image_url VARCHAR(500), -- Image principale produit
    is_active BOOLEAN DEFAULT true, -- Désactiver sans supprimer
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

COMMENT ON TABLE products IS 'Produits modèles (ex: Stan Smith). Les variantes vendables sont ailleurs';
COMMENT ON COLUMN products.base_price IS 'Prix de base, peut être ajusté par variante (ex: pointure rare)';
COMMENT ON COLUMN products.location IS 'Emplacement physique général dans la boutique';

CREATE INDEX idx_products_category_id ON products(category_id);
CREATE INDEX idx_products_subcategory_id ON products(subcategory_id);
CREATE INDEX idx_products_supplier_id ON products(supplier_id);
CREATE INDEX idx_products_active ON products(is_active) WHERE is_active = true;

-- ============================================
-- 3. SYSTÈME D'ATTRIBUTS VARIABLES (EAV)
-- ============================================

-- Types d'attributs configurables (pointure, taille, couleur, longueur, etc.)
-- Permet d'ajouter de nouveaux attributs sans modifier la structure
CREATE TABLE attribute_types (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE, -- Nom technique (ex: 'pointure', 'taille')
    display_name VARCHAR(255) NOT NULL, -- Nom affiché frontend (ex: 'Pointure', 'Taille')
    input_type VARCHAR(50) DEFAULT 'text' CHECK (input_type IN ('text', 'number', 'select', 'color')), -- Type de saisie
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

COMMENT ON TABLE attribute_types IS 'Définition des types d''attributs variables (pointure, taille, couleur, etc.)';
COMMENT ON COLUMN attribute_types.input_type IS 'Type de champ frontend: text, number, select, color';

-- Valeurs prédéfinies pour certains attributs (ex: liste des pointures)
-- Permet d'avoir des dropdowns au lieu de saisie libre
CREATE TABLE attribute_values (
    id SERIAL PRIMARY KEY,
    attribute_type_id INTEGER NOT NULL REFERENCES attribute_types(id) ON DELETE CASCADE,
    value VARCHAR(100) NOT NULL, -- Valeur (ex: '42', 'M', 'Rouge')
    sort_order INTEGER DEFAULT 0, -- Ordre d'affichage
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(attribute_type_id, value)
);

COMMENT ON TABLE attribute_values IS 'Valeurs prédéfinies pour les attributs (ex: 36,37,38 pour pointure)';

CREATE INDEX idx_attribute_values_type ON attribute_values(attribute_type_id);

-- Relation : Quels attributs s'appliquent à quel produit
-- Ex: Stan Smith nécessite pointure + couleur
CREATE TABLE product_attributes (
    id SERIAL PRIMARY KEY,
    product_id INTEGER NOT NULL REFERENCES products(id) ON DELETE CASCADE,
    attribute_type_id INTEGER NOT NULL REFERENCES attribute_types(id) ON DELETE RESTRICT,
    is_required BOOLEAN DEFAULT true, -- Attribut obligatoire ou optionnel
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(product_id, attribute_type_id)
);

COMMENT ON TABLE product_attributes IS 'Définit quels attributs sont nécessaires pour chaque produit';
COMMENT ON COLUMN product_attributes.is_required IS 'true = attribut obligatoire pour créer une variante';

CREATE INDEX idx_product_attributes_product ON product_attributes(product_id);

-- ============================================
-- 4. VARIANTES VENDABLES (PRODUITS RÉELS)
-- ============================================

-- VARIANTES = Combinaisons vendables réelles
-- Ex: Stan Smith pointure 42 blanc = 1 variante avec son propre stock
CREATE TABLE product_variants (
    id SERIAL PRIMARY KEY,
    product_id INTEGER NOT NULL REFERENCES products(id) ON DELETE CASCADE,
    sku VARCHAR(100) UNIQUE, -- Code unique (généré auto ou manuel)
    price_adjustment DECIMAL(12, 2) DEFAULT 0, -- Ajustement prix (+/- par rapport au base_price)
    
    -- GESTION STOCK AVANCÉE
    stock_quantity INTEGER NOT NULL DEFAULT 0 CHECK (stock_quantity >= 0), -- Stock physique total
    reserved_quantity INTEGER NOT NULL DEFAULT 0 CHECK (reserved_quantity >= 0), -- Stock réservé (réservations actives)
    credit_quantity INTEGER NOT NULL DEFAULT 0 CHECK (credit_quantity >= 0), -- Stock en crédit (vendu à crédit, pas dispo)
    -- Stock disponible = stock_quantity - reserved_quantity - credit_quantity (calculé)
    
    location_detail VARCHAR(255), -- Emplacement précis de cette variante (ex: "Étagère 2, casier B")
    low_stock_threshold INTEGER DEFAULT 5, -- Seuil alerte stock bas
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Contrainte : les quantités réservées/crédit ne peuvent dépasser le stock
    CHECK (reserved_quantity + credit_quantity <= stock_quantity)
);

COMMENT ON TABLE product_variants IS 'Variantes vendables (ex: Stan Smith 42 blanc). CHAQUE variante a son propre stock';
COMMENT ON COLUMN product_variants.stock_quantity IS 'Stock physique TOTAL';
COMMENT ON COLUMN product_variants.reserved_quantity IS 'Stock BLOQUÉ par réservations actives';
COMMENT ON COLUMN product_variants.credit_quantity IS 'Stock SORTI (livré) mais en crédit actif';
COMMENT ON COLUMN product_variants.price_adjustment IS 'Ajustement au base_price (ex: +500 pour pointure rare)';

CREATE INDEX idx_product_variants_product ON product_variants(product_id);
CREATE INDEX idx_product_variants_sku ON product_variants(sku);
CREATE INDEX idx_product_variants_active ON product_variants(is_active) WHERE is_active = true;
CREATE INDEX idx_product_variants_low_stock ON product_variants(stock_quantity) WHERE stock_quantity <= low_stock_threshold;

-- Valeurs concrètes des attributs pour chaque variante
-- Ex: variante_id=1 → pointure='42', couleur='blanc'
CREATE TABLE variant_attribute_values (
    id SERIAL PRIMARY KEY,
    variant_id INTEGER NOT NULL REFERENCES product_variants(id) ON DELETE CASCADE,
    attribute_type_id INTEGER NOT NULL REFERENCES attribute_types(id) ON DELETE RESTRICT,
    value VARCHAR(255) NOT NULL, -- Valeur concrète
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(variant_id, attribute_type_id)
);

COMMENT ON TABLE variant_attribute_values IS 'Valeurs concrètes attributs par variante (ex: pointure=42, couleur=blanc)';

CREATE INDEX idx_variant_attributes_variant ON variant_attribute_values(variant_id);
CREATE INDEX idx_variant_attributes_type ON variant_attribute_values(attribute_type_id);

-- ============================================
-- 5. FOURNISSEURS
-- ============================================

CREATE TABLE suppliers (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL, -- Nom du fournisseur
    wechat VARCHAR(100), -- Identifiant WeChat
    profile TEXT, -- Description profil (spécialités, etc.)
    contact VARCHAR(255), -- Téléphone, email
    accessibility_notes TEXT, -- Notes sur accessibilité (délais, conditions)
    reliability_score DECIMAL(3, 2) DEFAULT 5.00 CHECK (reliability_score BETWEEN 0 AND 10), -- Score fiabilité
    logo_url VARCHAR(500),
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

COMMENT ON TABLE suppliers IS 'Fournisseurs Chine';
COMMENT ON COLUMN suppliers.reliability_score IS 'Score 0-10 calculé selon ratio commandé/reçu + qualité';

-- ============================================
-- 6. TRANSITAIRES
-- ============================================

CREATE TABLE freight_forwarders (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL, -- Nom du transitaire
    logo_url VARCHAR(500), -- Logo
    contact VARCHAR(255), -- Contact
    location VARCHAR(255), -- Localisation
    service_score DECIMAL(3, 2) DEFAULT 5.00 CHECK (service_score BETWEEN 0 AND 10), -- Score qualité service
    notes TEXT,
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

COMMENT ON TABLE freight_forwarders IS 'Transitaires pour transport depuis Chine';
COMMENT ON COLUMN freight_forwarders.service_score IS 'Score 0-10 selon ponctualité, état colis, tarifs';

-- ============================================
-- 7. RÉCEPTIONS DE STOCK
-- ============================================

-- Réceptions de marchandises depuis fournisseurs
CREATE TABLE stock_receipts (
    id SERIAL PRIMARY KEY,
    receipt_number VARCHAR(50) UNIQUE NOT NULL, -- Numéro bon de réception
    supplier_id INTEGER NOT NULL REFERENCES suppliers(id) ON DELETE RESTRICT,
    freight_forwarder_id INTEGER REFERENCES freight_forwarders(id) ON DELETE SET NULL,
    receipt_date DATE NOT NULL DEFAULT CURRENT_DATE, -- Date réception
    total_cost_yuan DECIMAL(12, 2), -- Coût total Yuan
    total_cost_ariary DECIMAL(12, 2), -- Coût total Ariary
    exchange_rate DECIMAL(10, 4), -- Taux conversion Yuan→Ariary au moment achat
    status VARCHAR(50) DEFAULT 'pending' CHECK (status IN ('pending', 'validated', 'cancelled')),
    notes TEXT,
    created_by INTEGER REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

COMMENT ON TABLE stock_receipts IS 'Réceptions marchandises depuis fournisseurs';
COMMENT ON COLUMN stock_receipts.status IS 'pending=en attente, validated=confirmé (maj stock), cancelled=annulé';

CREATE INDEX idx_stock_receipts_supplier ON stock_receipts(supplier_id);
CREATE INDEX idx_stock_receipts_date ON stock_receipts(receipt_date);
CREATE INDEX idx_stock_receipts_number ON stock_receipts(receipt_number);

-- Détails articles reçus (par variante)
CREATE TABLE stock_receipt_items (
    id SERIAL PRIMARY KEY,
    stock_receipt_id INTEGER NOT NULL REFERENCES stock_receipts(id) ON DELETE CASCADE,
    variant_id INTEGER NOT NULL REFERENCES product_variants(id) ON DELETE RESTRICT,
    quantity_ordered INTEGER NOT NULL CHECK (quantity_ordered > 0), -- Quantité commandée
    quantity_received INTEGER NOT NULL CHECK (quantity_received >= 0), -- Quantité réellement reçue
    unit_cost_yuan DECIMAL(12, 2), -- Coût unitaire Yuan
    unit_cost_ariary DECIMAL(12, 2), -- Coût unitaire Ariary
    notes TEXT, -- Notes si écart (produit endommagé, etc.)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

COMMENT ON TABLE stock_receipt_items IS 'Détail articles par réception. Comparaison commandé vs reçu';
COMMENT ON COLUMN stock_receipt_items.quantity_ordered IS 'Ce qui était commandé';
COMMENT ON COLUMN stock_receipt_items.quantity_received IS 'Ce qui est arrivé (peut différer)';

CREATE INDEX idx_stock_receipt_items_receipt ON stock_receipt_items(stock_receipt_id);
CREATE INDEX idx_stock_receipt_items_variant ON stock_receipt_items(variant_id);

-- ============================================
-- 8. CLIENTS
-- ============================================

CREATE TABLE customers (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL, -- Nom complet
    phone VARCHAR(50), -- Téléphone principal
    email VARCHAR(255),
    address TEXT, -- Adresse physique
    mobile_money_number VARCHAR(50), -- Numéro mobile money principal
    
    -- SCORES
    reliability_score DECIMAL(3, 2) DEFAULT 5.00 CHECK (reliability_score BETWEEN 0 AND 10), -- Fiabilité paiements
    loyalty_points INTEGER DEFAULT 0, -- Points fidélité
    credit_limit DECIMAL(12, 2) DEFAULT 0, -- Limite crédit autorisé (Ariary)
    
    notes TEXT, -- Notes internes vendeur
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

COMMENT ON TABLE customers IS 'Clients de la boutique';
COMMENT ON COLUMN customers.reliability_score IS '0-10 calculé selon historique paiements crédits/réservations';
COMMENT ON COLUMN customers.credit_limit IS 'Montant max crédit autorisé selon fiabilité';
COMMENT ON COLUMN customers.loyalty_points IS 'Points accumulés selon achats';

CREATE INDEX idx_customers_phone ON customers(phone);
CREATE INDEX idx_customers_name ON customers(name);
CREATE INDEX idx_customers_reliability ON customers(reliability_score);

-- ============================================
-- 9. VENTES
-- ============================================

CREATE TABLE sales (
    id SERIAL PRIMARY KEY,
    sale_number VARCHAR(50) UNIQUE NOT NULL, -- Numéro facture/reçu
    customer_id INTEGER REFERENCES customers(id) ON DELETE SET NULL,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE RESTRICT, -- Vendeur
    sale_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    
    -- TYPE DE VENTE
    sale_type VARCHAR(50) NOT NULL DEFAULT 'immediate' CHECK (sale_type IN ('immediate', 'reservation', 'credit')),
    -- immediate = vente classique immédiate
    -- reservation = client réserve, paiera plus tard
    -- credit = produit livré, paiement échelonné
    
    -- MONTANTS
    subtotal DECIMAL(12, 2) NOT NULL CHECK (subtotal >= 0), -- Sous-total avant remise
    discount_amount DECIMAL(12, 2) DEFAULT 0 CHECK (discount_amount >= 0), -- Remise fixe Ariary
    discount_reason TEXT, -- Motif remise
    total_amount DECIMAL(12, 2) NOT NULL CHECK (total_amount >= 0), -- Total final
    
    -- STATUT PAIEMENT
    payment_status VARCHAR(50) DEFAULT 'pending' CHECK (payment_status IN ('pending', 'partial', 'paid', 'cancelled')),
    
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

COMMENT ON TABLE sales IS 'Ventes (immédiates, réservations, crédits)';
COMMENT ON COLUMN sales.sale_type IS 'immediate=vente classique, reservation=réservation client, credit=vente à crédit';
COMMENT ON COLUMN sales.payment_status IS 'pending=non payé, partial=partiellement payé, paid=soldé, cancelled=annulé';

CREATE INDEX idx_sales_customer ON sales(customer_id);
CREATE INDEX idx_sales_user ON sales(user_id);
CREATE INDEX idx_sales_date ON sales(sale_date);
CREATE INDEX idx_sales_status ON sales(payment_status);
CREATE INDEX idx_sales_type ON sales(sale_type);
CREATE INDEX idx_sales_number ON sales(sale_number);

-- Articles vendus (détail ligne par ligne)
CREATE TABLE sale_items (
    id SERIAL PRIMARY KEY,
    sale_id INTEGER NOT NULL REFERENCES sales(id) ON DELETE CASCADE,
    variant_id INTEGER NOT NULL REFERENCES product_variants(id) ON DELETE RESTRICT,
    quantity INTEGER NOT NULL CHECK (quantity > 0),
    unit_price DECIMAL(12, 2) NOT NULL CHECK (unit_price >= 0), -- Prix unitaire au moment vente
    subtotal DECIMAL(12, 2) NOT NULL CHECK (subtotal >= 0), -- quantity * unit_price
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

COMMENT ON TABLE sale_items IS 'Lignes articles vendus. Prix fixé au moment de la vente';

CREATE INDEX idx_sale_items_sale ON sale_items(sale_id);
CREATE INDEX idx_sale_items_variant ON sale_items(variant_id);

-- ============================================
-- 10. RÉSERVATIONS (NOUVEAU)
-- ============================================

CREATE TABLE reservations (
    id SERIAL PRIMARY KEY,
    sale_id INTEGER NOT NULL UNIQUE REFERENCES sales(id) ON DELETE CASCADE, -- Lien vers vente
    customer_id INTEGER NOT NULL REFERENCES customers(id) ON DELETE RESTRICT,
    reservation_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, -- Date réservation
    expiry_date TIMESTAMP NOT NULL, -- Date limite paiement
    
    -- MONTANTS
    total_amount DECIMAL(12, 2) NOT NULL, -- Montant total à payer
    deposit_amount DECIMAL(12, 2) DEFAULT 0, -- Acompte versé
    remaining_amount DECIMAL(12, 2) NOT NULL, -- Reste à payer
    
    -- STATUT
    status VARCHAR(50) DEFAULT 'pending' CHECK (status IN ('pending', 'confirmed', 'partial_paid', 'completed', 'expired', 'cancelled')),
    -- pending = en attente acompte
    -- confirmed = acompte reçu, en attente solde
    -- partial_paid = paiements partiels en cours
    -- completed = payé → vente finalisée
    -- expired = délai dépassé → stock libéré
    -- cancelled = annulé manuellement
    
    cancellation_reason TEXT, -- Raison si annulé
    completed_at TIMESTAMP, -- Date finalisation
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

COMMENT ON TABLE reservations IS 'Réservations clients avec délai paiement. Stock bloqué mais pas vendu';
COMMENT ON COLUMN reservations.expiry_date IS 'Date limite. Après = annulation auto + libération stock';
COMMENT ON COLUMN reservations.status IS 'expired/cancelled → stock automatiquement libéré (reserved_quantity décrémenté)';

CREATE INDEX idx_reservations_sale ON reservations(sale_id);
CREATE INDEX idx_reservations_customer ON reservations(customer_id);
CREATE INDEX idx_reservations_status ON reservations(status);
CREATE INDEX idx_reservations_expiry ON reservations(expiry_date);

-- ============================================
-- 11. CRÉDITS (NOUVEAU WORKFLOW)
-- ============================================

CREATE TABLE credits (
    id SERIAL PRIMARY KEY,
    sale_id INTEGER NOT NULL UNIQUE REFERENCES sales(id) ON DELETE CASCADE,
    customer_id INTEGER NOT NULL REFERENCES customers(id) ON DELETE RESTRICT,
    
    -- MONTANTS
    total_amount DECIMAL(12, 2) NOT NULL, -- Montant total crédit
    amount_paid DECIMAL(12, 2) DEFAULT 0 CHECK (amount_paid >= 0), -- Montant déjà payé
    amount_due DECIMAL(12, 2) NOT NULL CHECK (amount_due >= 0), -- Reste à payer
    
    -- DATES
    credit_date DATE NOT NULL DEFAULT CURRENT_DATE, -- Date octroi crédit
    due_date DATE, -- Date échéance finale (optionnel)
    last_payment_date DATE, -- Date dernier paiement
    
    -- STATUT
    status VARCHAR(50) DEFAULT 'active' CHECK (status IN ('active', 'partial_paid', 'completed', 'overdue', 'defaulted', 'recovered')),
    -- active = crédit actif, paiements à jour
    -- partial_paid = paiements partiels effectués
    -- completed = soldé
    -- overdue = en retard
    -- defaulted = défaut paiement grave
    -- recovered = produit récupéré suite défaut
    
    -- INTÉRÊTS (optionnel)
    interest_rate DECIMAL(5, 2) DEFAULT 0, -- Taux intérêt retard (%)
    interest_amount DECIMAL(12, 2) DEFAULT 0, -- Montant intérêts cumulés
    
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

COMMENT ON TABLE credits IS 'Crédits clients. Produit LIVRÉ mais paiement différé. Stock en credit_quantity';
COMMENT ON COLUMN credits.status IS 'active=ok, overdue=retard, defaulted=défaut, recovered=produit récupéré';
COMMENT ON COLUMN credits.amount_due IS 'Reste à payer (total - paid + interests)';

CREATE INDEX idx_credits_sale ON credits(sale_id);
CREATE INDEX idx_credits_customer ON credits(customer_id);
CREATE INDEX idx_credits_status ON credits(status);
CREATE INDEX idx_credits_due_date ON credits(due_date);

-- Échéances planifiées pour crédits
CREATE TABLE credit_installments (
    id SERIAL PRIMARY KEY,
    credit_id INTEGER NOT NULL REFERENCES credits(id) ON DELETE CASCADE,
    installment_number INTEGER NOT NULL, -- Numéro échéance (1, 2, 3...)
    due_date DATE NOT NULL, -- Date échéance
    amount_due DECIMAL(12, 2) NOT NULL, -- Montant à payer
    amount_paid DECIMAL(12, 2) DEFAULT 0, -- Montant payé
    status VARCHAR(50) DEFAULT 'pending' CHECK (status IN ('pending', 'paid', 'overdue')),
    paid_date DATE, -- Date paiement effectif
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(credit_id, installment_number)
);

COMMENT ON TABLE credit_installments IS 'Échéances planifiées pour crédits échelonnés';

CREATE INDEX idx_credit_installments_credit ON credit_installments(credit_id);
CREATE INDEX idx_credit_installments_due_date ON credit_installments(due_date);

-- ============================================
-- 12. PAIEMENTS
-- ============================================

CREATE TABLE payments (
    id SERIAL PRIMARY KEY,
    
    -- RÉFÉRENCES
    payment_type VARCHAR(50) NOT NULL CHECK (payment_type IN ('sale', 'reservation', 'credit')),
    -- sale = paiement vente immédiate
    -- reservation = acompte ou solde réservation
    -- credit = remboursement crédit
    
    reference_id INTEGER NOT NULL, -- ID de la sale, reservation ou credit
    
    -- DÉTAILS PAIEMENT
    payment_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    amount DECIMAL(12, 2) NOT NULL CHECK (amount > 0),
    payment_method VARCHAR(50) NOT NULL CHECK (payment_method IN ('cash', 'mobile_money', 'bank_transfer', 'mixed')),
    
    -- DÉTAILS MOBILE MONEY
    mobile_money_number VARCHAR(50), -- Numéro si mobile money
    mobile_money_fees DECIMAL(12, 2) DEFAULT 0, -- Frais mobile money
    
    -- PAIEMENT MIXTE (cash + mobile money)
    cash_amount DECIMAL(12, 2) DEFAULT 0, -- Part espèces
    mobile_money_amount DECIMAL(12, 2) DEFAULT 0, -- Part mobile money
    
    -- COMPTE DESTINATAIRE
    account_id INTEGER REFERENCES accounts(id) ON DELETE SET NULL, -- Compte crédité
    
    reference_number VARCHAR(255), -- Numéro transaction
    notes TEXT,
    created_by INTEGER REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

COMMENT ON TABLE payments IS 'Tous paiements (ventes immédiates, réservations, crédits)';
COMMENT ON COLUMN payments.payment_type IS 'sale=vente immédiate, reservation=acompte/solde réservation, credit=remboursement crédit';
COMMENT ON COLUMN payments.payment_method IS 'mixed=paiement combiné cash+mobile money';

CREATE INDEX idx_payments_reference ON payments(payment_type, reference_id);
CREATE INDEX idx_payments_date ON payments(payment_date);
CREATE INDEX idx_payments_method ON payments(payment_method);
CREATE INDEX idx_payments_account ON payments(account_id);

-- ============================================
-- 13. DÉPENSES
-- ============================================

CREATE TABLE expense_categories (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE, -- Nom catégorie
    description TEXT,
    icon VARCHAR(50), -- Icône frontend
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

COMMENT ON TABLE expense_categories IS 'Catégories de dépenses configurables';

CREATE TABLE expenses (
    id SERIAL PRIMARY KEY,
    category_id INTEGER NOT NULL REFERENCES expense_categories(id) ON DELETE RESTRICT,
    description TEXT NOT NULL, -- Description dépense
    amount DECIMAL(12, 2) NOT NULL CHECK (amount > 0),
    expense_date DATE NOT NULL DEFAULT CURRENT_DATE,
    payment_method VARCHAR(50) CHECK (payment_method IN ('cash', 'mobile_money', 'bank_transfer')),
    account_id INTEGER REFERENCES accounts(id) ON DELETE SET NULL, -- Compte débité
    reference VARCHAR(255), -- Numéro facture, etc.
    attachment_url VARCHAR(500), -- Scan facture
    is_recurring BOOLEAN DEFAULT false, -- Dépense récurrente (loyer, etc.)
    recurrence_frequency VARCHAR(50), -- 'monthly', 'yearly', etc.
    notes TEXT,
    created_by INTEGER REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

COMMENT ON TABLE expenses IS 'Dépenses boutique (salaires, loyer, etc.)';
COMMENT ON COLUMN expenses.is_recurring IS 'true si dépense récurrente (loyer, salaires)';

CREATE INDEX idx_expenses_category ON expenses(category_id);
CREATE INDEX idx_expenses_date ON expenses(expense_date);
CREATE INDEX idx_expenses_account ON expenses(account_id);

-- ============================================
-- 14. TRÉSORERIE & COMPTES
-- ============================================

CREATE TABLE account_types (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE, -- 'cash', 'mobile_money', 'bank'
    display_name VARCHAR(255) NOT NULL, -- Nom affiché
    icon VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

COMMENT ON TABLE account_types IS 'Types de comptes (espèces, mobile money, banque)';

CREATE TABLE accounts (
    id SERIAL PRIMARY KEY,
    account_type_id INTEGER NOT NULL REFERENCES account_types(id) ON DELETE RESTRICT,
    name VARCHAR(255) NOT NULL, -- Ex: "Caisse principale", "Orange Money", "BOA"
    account_number VARCHAR(100), -- Numéro compte bancaire ou mobile money
    current_balance DECIMAL(12, 2) DEFAULT 0, -- Solde actuel
    initial_balance DECIMAL(12, 2) DEFAULT 0, -- Solde initial (à l'ouverture)
    notes TEXT,
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

COMMENT ON TABLE accounts IS 'Comptes trésorerie (caisses, mobile money, banques)';
COMMENT ON COLUMN accounts.current_balance IS 'Solde en temps réel, mis à jour par triggers';

CREATE INDEX idx_accounts_type ON accounts(account_type_id);
CREATE INDEX idx_accounts_active ON accounts(is_active) WHERE is_active = true;

-- Mouvements trésorerie (transactions)
CREATE TABLE account_transactions (
    id SERIAL PRIMARY KEY,
    account_id INTEGER NOT NULL REFERENCES accounts(id) ON DELETE RESTRICT,
    transaction_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    amount DECIMAL(12, 2) NOT NULL, -- Montant (positif=crédit, négatif=débit)
    type VARCHAR(50) NOT NULL CHECK (type IN ('credit', 'debit')),
    
    -- RÉFÉRENCE origine transaction
    reference_type VARCHAR(100), -- 'sale', 'expense', 'transfer', 'adjustment', 'payment'
    reference_id INTEGER, -- ID de l'enregistrement source
    
    description TEXT,
    balance_after DECIMAL(12, 2) NOT NULL, -- Solde après cette transaction
    created_by INTEGER REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

COMMENT ON TABLE account_transactions IS 'Historique mouvements trésorerie. Chaque paiement/dépense génère transaction';
COMMENT ON COLUMN account_transactions.balance_after IS 'Solde du compte après cette transaction (traçabilité)';

CREATE INDEX idx_account_transactions_account ON account_transactions(account_id);
CREATE INDEX idx_account_transactions_date ON account_transactions(transaction_date);
CREATE INDEX idx_account_transactions_reference ON account_transactions(reference_type, reference_id);

-- ============================================
-- 15. CONVERSION DEVISES
-- ============================================

CREATE TABLE exchange_rates (
    id SERIAL PRIMARY KEY,
    currency_from VARCHAR(10) NOT NULL, -- 'CNY' (Yuan)
    currency_to VARCHAR(10) NOT NULL, -- 'MGA' (Ariary)
    rate DECIMAL(10, 4) NOT NULL CHECK (rate > 0), -- Taux conversion
    effective_date DATE NOT NULL DEFAULT CURRENT_DATE, -- Date application
    notes TEXT,
    created_by INTEGER REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(currency_from, currency_to, effective_date)
);

COMMENT ON TABLE exchange_rates IS 'Historique taux conversion Yuan↔Ariary';
COMMENT ON COLUMN exchange_rates.rate IS '1 CNY = X MGA';

CREATE INDEX idx_exchange_rates_date ON exchange_rates(effective_date DESC);

-- ============================================
-- 16. NOTIFICATIONS
-- ============================================

CREATE TABLE notifications (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id) ON DELETE CASCADE, -- NULL = notification globale
    type VARCHAR(100) NOT NULL, -- 'reservation_expiring', 'credit_overdue', 'low_stock', etc.
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    reference_type VARCHAR(100), -- Type entité concernée
    reference_id INTEGER, -- ID entité
    is_read BOOLEAN DEFAULT false,
    read_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

COMMENT ON TABLE notifications IS 'Notifications in-app pour utilisateurs';

CREATE INDEX idx_notifications_user ON notifications(user_id);
CREATE INDEX idx_notifications_read ON notifications(is_read) WHERE is_read = false;
CREATE INDEX idx_notifications_created ON notifications(created_at DESC);

-- ============================================
-- 17. LOGS & AUDIT
-- ============================================

CREATE TABLE audit_logs (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
    action VARCHAR(100) NOT NULL, -- 'create', 'update', 'delete', 'view'
    table_name VARCHAR(100) NOT NULL, -- Table concernée
    record_id INTEGER, -- ID enregistrement
    old_values JSONB, -- Anciennes valeurs (update/delete)
    new_values JSONB, -- Nouvelles valeurs (create/update)
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

COMMENT ON TABLE audit_logs IS 'Logs audit traçabilité actions utilisateurs';

CREATE INDEX idx_audit_logs_user ON audit_logs(user_id);
CREATE INDEX idx_audit_logs_table ON audit_logs(table_name, record_id);
CREATE INDEX idx_audit_logs_created ON audit_logs(created_at DESC);

-- ============================================
-- 18. CONFIGURATION SYSTÈME
-- ============================================

CREATE TABLE system_settings (
    id SERIAL PRIMARY KEY,
    key VARCHAR(255) NOT NULL UNIQUE, -- Clé paramètre
    value TEXT, -- Valeur
    value_type VARCHAR(50) DEFAULT 'string' CHECK (value_type IN ('string', 'number', 'boolean', 'json')),
    description TEXT, -- Description paramètre
    is_public BOOLEAN DEFAULT false, -- Accessible frontend sans auth
    updated_by INTEGER REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

COMMENT ON TABLE system_settings IS 'Configuration globale application (délais, seuils, etc.)';
COMMENT ON COLUMN system_settings.is_public IS 'true si peut être exposé frontend (infos boutique)';

CREATE INDEX idx_system_settings_key ON system_settings(key);

-- ============================================
-- 19. VUES MÉTIER
-- ============================================

-- Vue: Stock disponible par variante avec calcul automatique
CREATE VIEW v_stock_available AS
SELECT 
    pv.id AS variant_id,
    p.id AS product_id,
    p.name AS product_name,
    pv.sku,
    pv.stock_quantity AS physical_stock,
    pv.reserved_quantity,
    pv.credit_quantity,
    (pv.stock_quantity - pv.reserved_quantity - pv.credit_quantity) AS available_stock,
    (p.base_price + COALESCE(pv.price_adjustment, 0)) AS selling_price,
    pv.location_detail,
    CASE 
        WHEN (pv.stock_quantity - pv.reserved_quantity - pv.credit_quantity) <= pv.low_stock_threshold THEN true
        ELSE false
    END AS is_low_stock,
    STRING_AGG(at.display_name || ': ' || vav.value, ', ' ORDER BY at.name) AS attributes
FROM product_variants pv
JOIN products p ON pv.product_id = p.id
LEFT JOIN variant_attribute_values vav ON vav.variant_id = pv.id
LEFT JOIN attribute_types at ON vav.attribute_type_id = at.id
WHERE pv.is_active = true AND p.is_active = true
GROUP BY pv.id, p.id, p.name, pv.sku, pv.stock_quantity, pv.reserved_quantity, 
         pv.credit_quantity, p.base_price, pv.price_adjustment, pv.location_detail, pv.low_stock_threshold;

COMMENT ON VIEW v_stock_available IS 'Stock disponible = stock physique - réservé - crédit';

-- Vue: Solde trésorerie global par type compte
CREATE VIEW v_treasury_summary AS
SELECT 
    at.name AS account_type,
    at.display_name,
    COUNT(a.id) AS account_count,
    SUM(a.current_balance) AS total_balance
FROM account_types at
LEFT JOIN accounts a ON a.account_type_id = at.id AND a.is_active = true
GROUP BY at.id, at.name, at.display_name;

COMMENT ON VIEW v_treasury_summary IS 'Vue consolidée soldes par type compte';

-- Vue: Réservations à traiter (expirant bientôt)
CREATE VIEW v_reservations_pending AS
SELECT 
    r.id AS reservation_id,
    r.sale_id,
    s.sale_number,
    c.name AS customer_name,
    c.phone AS customer_phone,
    r.total_amount,
    r.deposit_amount,
    r.remaining_amount,
    r.expiry_date,
    EXTRACT(EPOCH FROM (r.expiry_date - CURRENT_TIMESTAMP))/3600 AS hours_remaining,
    r.status,
    r.reservation_date
FROM reservations r
JOIN sales s ON r.sale_id = s.id
JOIN customers c ON r.customer_id = c.id
WHERE r.status IN ('pending', 'confirmed', 'partial_paid')
  AND r.expiry_date > CURRENT_TIMESTAMP
ORDER BY r.expiry_date ASC;

COMMENT ON VIEW v_reservations_pending IS 'Réservations actives triées par urgence';

-- Vue: Crédits à risque (en retard ou échéance proche)
CREATE VIEW v_credits_at_risk AS
SELECT 
    cr.id AS credit_id,
    cr.sale_id,
    s.sale_number,
    c.name AS customer_name,
    c.phone AS customer_phone,
    c.reliability_score,
    cr.total_amount,
    cr.amount_paid,
    cr.amount_due,
    cr.due_date,
    cr.status,
    CASE 
        WHEN cr.status = 'overdue' THEN CURRENT_DATE - cr.due_date
        ELSE 0
    END AS days_overdue,
    cr.credit_date
FROM credits cr
JOIN sales s ON cr.sale_id = s.id
JOIN customers c ON cr.customer_id = c.id
WHERE cr.status IN ('active', 'partial_paid', 'overdue')
  AND (cr.due_date <= CURRENT_DATE + INTERVAL '7 days' OR cr.status = 'overdue')
ORDER BY cr.due_date ASC;

COMMENT ON VIEW v_credits_at_risk IS 'Crédits nécessitant attention (retard ou échéance < 7j)';

-- ============================================
-- 20. FONCTIONS & TRIGGERS
-- ============================================

-- Fonction: Mise à jour automatique updated_at
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$ LANGUAGE plpgsql;

COMMENT ON FUNCTION update_updated_at_column IS 'MAJ auto colonne updated_at';

-- Appliquer aux tables concernées
CREATE TRIGGER update_users_updated_at BEFORE UPDATE ON users 
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();
CREATE TRIGGER update_products_updated_at BEFORE UPDATE ON products 
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();
CREATE TRIGGER update_product_variants_updated_at BEFORE UPDATE ON product_variants 
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();
CREATE TRIGGER update_sales_updated_at BEFORE UPDATE ON sales 
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();
CREATE TRIGGER update_customers_updated_at BEFORE UPDATE ON customers 
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- Fonction: Libération automatique stock réservé (annulation réservation)
CREATE OR REPLACE FUNCTION release_reserved_stock()
RETURNS TRIGGER AS $
BEGIN
    -- Si réservation annulée ou expirée, libérer stock
    IF (NEW.status IN ('expired', 'cancelled') AND OLD.status NOT IN ('expired', 'cancelled')) THEN
        -- Décrémente reserved_quantity pour chaque article de la vente
        UPDATE product_variants pv
        SET reserved_quantity = reserved_quantity - si.quantity
        FROM sale_items si
        WHERE si.sale_id = NEW.sale_id
          AND si.variant_id = pv.id;
    END IF;
    
    RETURN NEW;
END;
$ LANGUAGE plpgsql;

COMMENT ON FUNCTION release_reserved_stock IS 'Libère stock réservé si réservation annulée/expirée';

CREATE TRIGGER trigger_release_reserved_stock
AFTER UPDATE ON reservations
FOR EACH ROW EXECUTE FUNCTION release_reserved_stock();

-- Fonction: Bloquer stock lors création réservation
CREATE OR REPLACE FUNCTION reserve_stock_on_reservation()
RETURNS TRIGGER AS $
BEGIN
    -- Incrémente reserved_quantity pour chaque article
    UPDATE product_variants pv
    SET reserved_quantity = reserved_quantity + si.quantity
    FROM sale_items si
    WHERE si.sale_id = NEW.sale_id
      AND si.variant_id = pv.id;
    
    -- Vérifier stock disponible suffisant
    IF EXISTS (
        SELECT 1 FROM product_variants pv
        JOIN sale_items si ON si.variant_id = pv.id
        WHERE si.sale_id = NEW.sale_id
          AND (pv.stock_quantity - pv.reserved_quantity - pv.credit_quantity) < 0
    ) THEN
        RAISE EXCEPTION 'Stock disponible insuffisant pour réservation';
    END IF;
    
    RETURN NEW;
END;
$ LANGUAGE plpgsql;

COMMENT ON FUNCTION reserve_stock_on_reservation IS 'Bloque stock lors création réservation';

CREATE TRIGGER trigger_reserve_stock_on_reservation
AFTER INSERT ON reservations
FOR EACH ROW EXECUTE FUNCTION reserve_stock_on_reservation();

-- Fonction: Transfert stock vers credit_quantity lors création crédit
CREATE OR REPLACE FUNCTION move_stock_to_credit()
RETURNS TRIGGER AS $
BEGIN
    -- Décrémente stock_quantity et incrémente credit_quantity
    UPDATE product_variants pv
    SET stock_quantity = stock_quantity - si.quantity,
        credit_quantity = credit_quantity + si.quantity
    FROM sale_items si
    WHERE si.sale_id = NEW.sale_id
      AND si.variant_id = pv.id;
    
    -- Vérifier stock physique suffisant
    IF EXISTS (
        SELECT 1 FROM product_variants pv
        WHERE pv.stock_quantity < 0
    ) THEN
        RAISE EXCEPTION 'Stock physique insuffisant pour vente à crédit';
    END IF;
    
    RETURN NEW;
END;
$ LANGUAGE plpgsql;

COMMENT ON FUNCTION move_stock_to_credit IS 'Produit livré à crédit: stock_quantity décrémenté, credit_quantity incrémenté';

CREATE TRIGGER trigger_move_stock_to_credit
AFTER INSERT ON credits
FOR EACH ROW EXECUTE FUNCTION move_stock_to_credit();

-- Fonction: Libération stock crédit lors solde crédit
CREATE OR REPLACE FUNCTION release_credit_stock()
RETURNS TRIGGER AS $
BEGIN
    -- Si crédit soldé, décrémente credit_quantity (produit définitivement sorti)
    IF (NEW.status = 'completed' AND OLD.status != 'completed') THEN
        UPDATE product_variants pv
        SET credit_quantity = credit_quantity - si.quantity
        FROM sale_items si
        WHERE si.sale_id = NEW.sale_id
          AND si.variant_id = pv.id;
    END IF;
    
    -- Si produit récupéré (défaut), remet en stock
    IF (NEW.status = 'recovered' AND OLD.status != 'recovered') THEN
        UPDATE product_variants pv
        SET credit_quantity = credit_quantity - si.quantity,
            stock_quantity = stock_quantity + si.quantity
        FROM sale_items si
        WHERE si.sale_id = NEW.sale_id
          AND si.variant_id = pv.id;
    END IF;
    
    RETURN NEW;
END;
$ LANGUAGE plpgsql;

COMMENT ON FUNCTION release_credit_stock IS 'Gère credit_quantity selon statut crédit (completed/recovered)';

CREATE TRIGGER trigger_release_credit_stock
AFTER UPDATE ON credits
FOR EACH ROW EXECUTE FUNCTION release_credit_stock();

-- Fonction: Décrémentation stock lors vente immédiate
CREATE OR REPLACE FUNCTION decrease_stock_on_immediate_sale()
RETURNS TRIGGER AS $
BEGIN
    -- Uniquement pour ventes immédiates (pas réservations ni crédits)
    UPDATE product_variants pv
    SET stock_quantity = stock_quantity - si.quantity
    FROM sale_items si
    WHERE si.sale_id = NEW.id
      AND si.variant_id = pv.id
      AND NEW.sale_type = 'immediate';
    
    -- Vérifier stock disponible suffisant
    IF NEW.sale_type = 'immediate' AND EXISTS (
        SELECT 1 FROM product_variants pv
        JOIN sale_items si ON si.variant_id = pv.id
        WHERE si.sale_id = NEW.id
          AND (pv.stock_quantity - pv.reserved_quantity - pv.credit_quantity) < 0
    ) THEN
        RAISE EXCEPTION 'Stock disponible insuffisant pour vente immédiate';
    END IF;
    
    RETURN NEW;
END;
$ LANGUAGE plpgsql;

COMMENT ON FUNCTION decrease_stock_on_immediate_sale IS 'Décrémente stock_quantity UNIQUEMENT pour ventes immédiates';

CREATE TRIGGER trigger_decrease_stock_on_immediate_sale
AFTER INSERT ON sales
FOR EACH ROW EXECUTE FUNCTION decrease_stock_on_immediate_sale();

-- Fonction: MAJ solde compte lors transaction
CREATE OR REPLACE FUNCTION update_account_balance()
RETURNS TRIGGER AS $
BEGIN
    -- Calculer nouveau solde
    IF NEW.type = 'credit' THEN
        NEW.balance_after := (SELECT current_balance FROM accounts WHERE id = NEW.account_id) + NEW.amount;
    ELSE
        NEW.balance_after := (SELECT current_balance FROM accounts WHERE id = NEW.account_id) - NEW.amount;
    END IF;
    
    -- Mettre à jour solde compte
    UPDATE accounts 
    SET current_balance = NEW.balance_after 
    WHERE id = NEW.account_id;
    
    RETURN NEW;
END;
$ LANGUAGE plpgsql;

COMMENT ON FUNCTION update_account_balance IS 'MAJ automatique solde compte lors transaction';

CREATE TRIGGER trigger_update_account_balance
BEFORE INSERT ON account_transactions
FOR EACH ROW EXECUTE FUNCTION update_account_balance();

-- ============================================
-- 21. DONNÉES INITIALES
-- ============================================

-- Types de comptes
INSERT INTO account_types (name, display_name, icon) VALUES
('cash', 'Espèces', 'cash'),
('mobile_money', 'Mobile Money', 'smartphone'),
('bank', 'Compte Bancaire', 'bank');

-- Catégories de dépenses
INSERT INTO expense_categories (name, description, icon) VALUES
('Salaires', 'Rémunération du personnel', 'users'),
('Loyer', 'Loyer du local commercial', 'home'),
('Électricité', 'Factures d''électricité', 'zap'),
('Eau', 'Factures d''eau', 'droplet'),
('Transport', 'Frais de transport et livraison', 'truck'),
('Marketing', 'Publicité et promotion', 'megaphone'),
('Maintenance', 'Entretien local et matériel', 'tool'),
('Fournitures', 'Fournitures bureau', 'package'),
('Autres', 'Dépenses diverses', 'more-horizontal');

-- Types d'attributs courants
INSERT INTO attribute_types (name, display_name, input_type) VALUES
('pointure', 'Pointure', 'select'),
('taille', 'Taille', 'select'),
('couleur', 'Couleur', 'color'),
('longueur', 'Longueur (cm)', 'number'),
('matiere', 'Matière', 'text');

-- Valeurs prédéfinies pointures
INSERT INTO attribute_values (attribute_type_id, value, sort_order)
SELECT 
    (SELECT id FROM attribute_types WHERE name = 'pointure'),
    value, 
    sort_order 
FROM (VALUES 
    ('35', 1), ('36', 2), ('37', 3), ('38', 4), ('39', 5),
    ('40', 6), ('41', 7), ('42', 8), ('43', 9), ('44', 10), ('45', 11)
) AS v(value, sort_order);

-- Valeurs prédéfinies tailles
INSERT INTO attribute_values (attribute_type_id, value, sort_order)
SELECT 
    (SELECT id FROM attribute_types WHERE name = 'taille'),
    value,
    sort_order
FROM (VALUES 
    ('XS', 1), ('S', 2), ('M', 3), ('L', 4), ('XL', 5), ('XXL', 6), ('XXXL', 7)
) AS v(value, sort_order);

-- Paramètres système par défaut
INSERT INTO system_settings (key, value, value_type, description, is_public) VALUES
('shop_name', 'Ma Boutique', 'string', 'Nom de la boutique', true),
('shop_address', '', 'string', 'Adresse boutique', true),
('reservation_default_days', '3', 'number', 'Délai réservation par défaut (jours)', false),
('low_stock_threshold', '5', 'number', 'Seuil alerte stock bas', false),
('credit_interest_rate', '0', 'number', 'Taux intérêt retard crédit (%)', false),
('currency_main', 'MGA', 'string', 'Devise principale', true),
('currency_secondary', 'CNY', 'string', 'Devise secondaire (achats)', true);

-- Utilisateur admin par défaut
-- Mot de passe: "password" (à changer en production)
INSERT INTO users (name, email, password, role) VALUES
('Administrateur', 'admin@boutique.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- ============================================
-- FIN DU SCHÉMA
-- ============================================

-- Instructions post-installation:
-- 1. Modifier le mot de passe admin
-- 2. Créer les comptes trésorerie initiaux
-- 3. Configurer les paramètres système
-- 4. Importer catégories produits
-- 5. Ajouter fournisseurs et transitaires