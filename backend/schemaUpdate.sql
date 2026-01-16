-- ============================================
-- SCRIPT DE NETTOYAGE ET RECRÉATION COMPLÈTE
-- SCHÉMA POSTGRESQL - GESTION BOUTIQUE OFFLINE
-- ============================================

-- ============================================
-- SUPPRESSION DE TOUTES LES TABLES EXISTANTES
-- ============================================

-- Désactiver temporairement les contraintes de clés étrangères
SET session_replication_role = 'replica';

-- Supprimer toutes les vues
DROP VIEW IF EXISTS v_credits_at_risk CASCADE;
DROP VIEW IF EXISTS v_reservations_pending CASCADE;
DROP VIEW IF EXISTS v_treasury_summary CASCADE;
DROP VIEW IF EXISTS v_stock_available CASCADE;

-- Supprimer toutes les fonctions
DROP FUNCTION IF EXISTS update_account_balance() CASCADE;
DROP FUNCTION IF EXISTS decrease_stock_on_immediate_sale() CASCADE;
DROP FUNCTION IF EXISTS release_credit_stock() CASCADE;
DROP FUNCTION IF EXISTS move_stock_to_credit() CASCADE;
DROP FUNCTION IF EXISTS reserve_stock_on_reservation() CASCADE;
DROP FUNCTION IF EXISTS release_reserved_stock() CASCADE;
DROP FUNCTION IF EXISTS update_updated_at_column() CASCADE;

-- Supprimer toutes les tables dans l'ordre inverse des dépendances
DROP TABLE IF EXISTS audit_logs CASCADE;
DROP TABLE IF EXISTS notifications CASCADE;
DROP TABLE IF EXISTS system_settings CASCADE;
DROP TABLE IF EXISTS exchange_rates CASCADE;
DROP TABLE IF EXISTS account_transactions CASCADE;
DROP TABLE IF EXISTS expenses CASCADE;
DROP TABLE IF EXISTS expense_categories CASCADE;
DROP TABLE IF EXISTS payments CASCADE;
DROP TABLE IF EXISTS credit_installments CASCADE;
DROP TABLE IF EXISTS credits CASCADE;
DROP TABLE IF EXISTS reservations CASCADE;
DROP TABLE IF EXISTS sale_items CASCADE;
DROP TABLE IF EXISTS sales CASCADE;
DROP TABLE IF EXISTS customers CASCADE;
DROP TABLE IF EXISTS stock_receipt_items CASCADE;
DROP TABLE IF EXISTS stock_receipts CASCADE;
DROP TABLE IF EXISTS variant_attribute_values CASCADE;
DROP TABLE IF EXISTS product_variants CASCADE;
DROP TABLE IF EXISTS product_attributes CASCADE;
DROP TABLE IF EXISTS attribute_values CASCADE;
DROP TABLE IF EXISTS attribute_types CASCADE;
DROP TABLE IF EXISTS products CASCADE;
DROP TABLE IF EXISTS categories CASCADE;
DROP TABLE IF EXISTS accounts CASCADE;
DROP TABLE IF EXISTS account_types CASCADE;
DROP TABLE IF EXISTS freight_forwarders CASCADE;
DROP TABLE IF EXISTS suppliers CASCADE;
DROP TABLE IF EXISTS user_sessions CASCADE;
DROP TABLE IF EXISTS users CASCADE;

-- Réactiver les contraintes
SET session_replication_role = 'origin';

-- Extension UUID
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

-- ============================================
-- 1. AUTHENTIFICATION & UTILISATEURS
-- ============================================

CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    usename VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(50) DEFAULT 'vendeur' CHECK (role IN ('admin', 'vendeur')),
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

COMMENT ON TABLE users IS 'Utilisateurs internes (admin + vendeurs)';

CREATE TABLE user_sessions (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    login_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    logout_at TIMESTAMP,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_user_sessions_user_id ON user_sessions(user_id);
CREATE INDEX idx_user_sessions_login_at ON user_sessions(login_at);

-- ============================================
-- 2. FOURNISSEURS & TRANSITAIRES (AVANT PRODUITS)
-- ============================================

CREATE TABLE suppliers (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    wechat VARCHAR(100),
    profile TEXT,
    contact VARCHAR(255),
    accessibility_notes TEXT,
    reliability_score DECIMAL(3, 2) DEFAULT 5.00 CHECK (reliability_score BETWEEN 0 AND 10),
    logo_url VARCHAR(500),
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

COMMENT ON TABLE suppliers IS 'Fournisseurs Chine';

CREATE TABLE freight_forwarders (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    logo_url VARCHAR(500),
    contact VARCHAR(255),
    service_score DECIMAL(3, 2) DEFAULT 5.00 CHECK (service_score BETWEEN 0 AND 10),
    notes TEXT,
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

COMMENT ON TABLE freight_forwarders IS 'Transitaires pour transport depuis Chine';

-- ============================================
-- 3. CATÉGORIES & ATTRIBUTS
-- ============================================

CREATE TABLE categories (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    parent_id INTEGER REFERENCES categories(id) ON DELETE SET NULL,
    image_url VARCHAR(500),
    sort_order INTEGER DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_categories_parent_id ON categories(parent_id);

CREATE TABLE attribute_types (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    display_name VARCHAR(255) NOT NULL,
    input_type VARCHAR(50) DEFAULT 'text' CHECK (input_type IN ('text', 'number', 'select', 'color')),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE attribute_values (
    id SERIAL PRIMARY KEY,
    attribute_type_id INTEGER NOT NULL REFERENCES attribute_types(id) ON DELETE CASCADE,
    value VARCHAR(100) NOT NULL,
    sort_order INTEGER DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(attribute_type_id, value)
);

CREATE INDEX idx_attribute_values_type ON attribute_values(attribute_type_id);

-- ============================================
-- 4. PRODUITS & VARIANTES
-- ============================================

CREATE TABLE products (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    category_id INTEGER NOT NULL REFERENCES categories(id) ON DELETE RESTRICT,
    subcategory_id INTEGER REFERENCES categories(id) ON DELETE SET NULL,
    base_price DECIMAL(12, 2) NOT NULL CHECK (base_price >= 0),
    supplier_id INTEGER REFERENCES suppliers(id) ON DELETE SET NULL,
    image_url VARCHAR(500),
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_products_category_id ON products(category_id);
CREATE INDEX idx_products_subcategory_id ON products(subcategory_id);
CREATE INDEX idx_products_supplier_id ON products(supplier_id);
CREATE INDEX idx_products_active ON products(is_active) WHERE is_active = true;

CREATE TABLE product_attributes (
    id SERIAL PRIMARY KEY,
    product_id INTEGER NOT NULL REFERENCES products(id) ON DELETE CASCADE,
    attribute_type_id INTEGER NOT NULL REFERENCES attribute_types(id) ON DELETE RESTRICT,
    is_required BOOLEAN DEFAULT true,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(product_id, attribute_type_id)
);

CREATE INDEX idx_product_attributes_product ON product_attributes(product_id);

CREATE TABLE product_variants (
    id SERIAL PRIMARY KEY,
    product_id INTEGER NOT NULL REFERENCES products(id) ON DELETE CASCADE,
    sku VARCHAR(100) UNIQUE,
    price_adjustment DECIMAL(12, 2) DEFAULT 0,
    stock_quantity INTEGER NOT NULL DEFAULT 0 CHECK (stock_quantity >= 0),
    reserved_quantity INTEGER NOT NULL DEFAULT 0 CHECK (reserved_quantity >= 0),
    credit_quantity INTEGER NOT NULL DEFAULT 0 CHECK (credit_quantity >= 0),
    low_stock_threshold INTEGER DEFAULT 5,
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CHECK (reserved_quantity + credit_quantity <= stock_quantity)
);

CREATE INDEX idx_product_variants_product ON product_variants(product_id);
CREATE INDEX idx_product_variants_sku ON product_variants(sku);
CREATE INDEX idx_product_variants_active ON product_variants(is_active) WHERE is_active = true;
CREATE INDEX idx_product_variants_low_stock ON product_variants(stock_quantity) WHERE stock_quantity <= low_stock_threshold;

CREATE TABLE variant_attribute_values (
    id SERIAL PRIMARY KEY,
    variant_id INTEGER NOT NULL REFERENCES product_variants(id) ON DELETE CASCADE,
    attribute_value_id INTEGER NOT NULL REFERENCES attribute_values(id) ON DELETE RESTRICT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (variant_id, attribute_value_id)
);

CREATE INDEX idx_variant_attributes_variant ON variant_attribute_values(variant_id);
CREATE INDEX idx_variant_attributes_type ON variant_attribute_values(attribute_type_id);

-- ============================================
-- 5. RÉCEPTIONS DE STOCK
-- ============================================

CREATE TABLE stock_receipts (
    id SERIAL PRIMARY KEY,
    receipt_number VARCHAR(50) UNIQUE NOT NULL,
    supplier_id INTEGER NOT NULL REFERENCES suppliers(id) ON DELETE RESTRICT,
    freight_forwarder_id INTEGER REFERENCES freight_forwarders(id) ON DELETE SET NULL,
    receipt_date DATE NOT NULL DEFAULT CURRENT_DATE,
    total_cost_yuan DECIMAL(12, 2),
    total_cost_ariary DECIMAL(12, 2),
    exchange_rate DECIMAL(10, 4),
    status VARCHAR(50) DEFAULT 'pending' CHECK (status IN ('pending', 'validated', 'cancelled')),
    notes TEXT,
    created_by INTEGER REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_stock_receipts_supplier ON stock_receipts(supplier_id);
CREATE INDEX idx_stock_receipts_date ON stock_receipts(receipt_date);
CREATE INDEX idx_stock_receipts_number ON stock_receipts(receipt_number);

CREATE TABLE stock_receipt_items (
    id SERIAL PRIMARY KEY,
    stock_receipt_id INTEGER NOT NULL REFERENCES stock_receipts(id) ON DELETE CASCADE,
    variant_id INTEGER NOT NULL REFERENCES product_variants(id) ON DELETE RESTRICT,
    quantity_ordered INTEGER NOT NULL CHECK (quantity_ordered > 0),
    quantity_received INTEGER NOT NULL CHECK (quantity_received >= 0),
    unit_cost_yuan DECIMAL(12, 2),
    unit_cost_ariary DECIMAL(12, 2),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_stock_receipt_items_receipt ON stock_receipt_items(stock_receipt_id);
CREATE INDEX idx_stock_receipt_items_variant ON stock_receipt_items(variant_id);

-- ============================================
-- 6. CLIENTS
-- ============================================

CREATE TABLE customers (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    phone VARCHAR(50),
    email VARCHAR(255),
    address TEXT,
    mobile_money_number VARCHAR(50),
    reliability_score DECIMAL(3, 2) DEFAULT 5.00 CHECK (reliability_score BETWEEN 0 AND 10),
    loyalty_points INTEGER DEFAULT 0,
    credit_limit DECIMAL(12, 2) DEFAULT 0,
    notes TEXT,
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_customers_phone ON customers(phone);
CREATE INDEX idx_customers_name ON customers(name);
CREATE INDEX idx_customers_reliability ON customers(reliability_score);

-- ============================================
-- 7. TRÉSORERIE (AVANT PAIEMENTS/DÉPENSES)
-- ============================================

CREATE TABLE account_types (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    display_name VARCHAR(255) NOT NULL,
    icon VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE accounts (
    id SERIAL PRIMARY KEY,
    account_type_id INTEGER NOT NULL REFERENCES account_types(id) ON DELETE RESTRICT,
    name VARCHAR(255) NOT NULL,
    account_number VARCHAR(100),
    current_balance DECIMAL(12, 2) DEFAULT 0,
    initial_balance DECIMAL(12, 2) DEFAULT 0,
    notes TEXT,
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_accounts_type ON accounts(account_type_id);
CREATE INDEX idx_accounts_active ON accounts(is_active) WHERE is_active = true;

CREATE TABLE account_transactions (
    id SERIAL PRIMARY KEY,
    account_id INTEGER NOT NULL REFERENCES accounts(id) ON DELETE RESTRICT,
    transaction_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    amount DECIMAL(12, 2) NOT NULL,
    type VARCHAR(50) NOT NULL CHECK (type IN ('credit', 'debit')),
    reference_type VARCHAR(100),
    reference_id INTEGER,
    description TEXT,
    balance_after DECIMAL(12, 2) NOT NULL,
    created_by INTEGER REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_account_transactions_account ON account_transactions(account_id);
CREATE INDEX idx_account_transactions_date ON account_transactions(transaction_date);
CREATE INDEX idx_account_transactions_reference ON account_transactions(reference_type, reference_id);

-- ============================================
-- 8. VENTES
-- ============================================

CREATE TABLE sales (
    id SERIAL PRIMARY KEY,
    sale_number VARCHAR(50) UNIQUE NOT NULL,
    customer_id INTEGER REFERENCES customers(id) ON DELETE SET NULL,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE RESTRICT,
    sale_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    sale_type VARCHAR(50) NOT NULL DEFAULT 'immediate' CHECK (sale_type IN ('immediate', 'reservation', 'credit')),
    subtotal DECIMAL(12, 2) NOT NULL CHECK (subtotal >= 0),
    discount_amount DECIMAL(12, 2) DEFAULT 0 CHECK (discount_amount >= 0),
    discount_reason TEXT,
    total_amount DECIMAL(12, 2) NOT NULL CHECK (total_amount >= 0),
    payment_status VARCHAR(50) DEFAULT 'pending' CHECK (payment_status IN ('pending', 'partial', 'paid', 'cancelled')),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_sales_customer ON sales(customer_id);
CREATE INDEX idx_sales_user ON sales(user_id);
CREATE INDEX idx_sales_date ON sales(sale_date);
CREATE INDEX idx_sales_status ON sales(payment_status);
CREATE INDEX idx_sales_type ON sales(sale_type);
CREATE INDEX idx_sales_number ON sales(sale_number);

CREATE TABLE sale_items (
    id SERIAL PRIMARY KEY,
    sale_id INTEGER NOT NULL REFERENCES sales(id) ON DELETE CASCADE,
    variant_id INTEGER NOT NULL REFERENCES product_variants(id) ON DELETE RESTRICT,
    quantity INTEGER NOT NULL CHECK (quantity > 0),
    unit_price DECIMAL(12, 2) NOT NULL CHECK (unit_price >= 0),
    subtotal DECIMAL(12, 2) NOT NULL CHECK (subtotal >= 0),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_sale_items_sale ON sale_items(sale_id);
CREATE INDEX idx_sale_items_variant ON sale_items(variant_id);

-- ============================================
-- 9. RÉSERVATIONS
-- ============================================

CREATE TABLE reservations (
    id SERIAL PRIMARY KEY,
    sale_id INTEGER NOT NULL UNIQUE REFERENCES sales(id) ON DELETE CASCADE,
    customer_id INTEGER NOT NULL REFERENCES customers(id) ON DELETE RESTRICT,
    reservation_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expiry_date TIMESTAMP NOT NULL,
    total_amount DECIMAL(12, 2) NOT NULL,
    deposit_amount DECIMAL(12, 2) DEFAULT 0,
    remaining_amount DECIMAL(12, 2) NOT NULL,
    status VARCHAR(50) DEFAULT 'pending' CHECK (status IN ('pending', 'confirmed', 'partial_paid', 'completed', 'expired', 'cancelled')),
    cancellation_reason TEXT,
    completed_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_reservations_sale ON reservations(sale_id);
CREATE INDEX idx_reservations_customer ON reservations(customer_id);
CREATE INDEX idx_reservations_status ON reservations(status);
CREATE INDEX idx_reservations_expiry ON reservations(expiry_date);

-- ============================================
-- 10. CRÉDITS
-- ============================================

CREATE TABLE credits (
    id SERIAL PRIMARY KEY,
    sale_id INTEGER NOT NULL UNIQUE REFERENCES sales(id) ON DELETE CASCADE,
    customer_id INTEGER NOT NULL REFERENCES customers(id) ON DELETE RESTRICT,
    total_amount DECIMAL(12, 2) NOT NULL,
    amount_paid DECIMAL(12, 2) DEFAULT 0 CHECK (amount_paid >= 0),
    amount_due DECIMAL(12, 2) NOT NULL CHECK (amount_due >= 0),
    credit_date DATE NOT NULL DEFAULT CURRENT_DATE,
    due_date DATE,
    last_payment_date DATE,
    status VARCHAR(50) DEFAULT 'active' CHECK (status IN ('active', 'partial_paid', 'completed', 'overdue', 'defaulted', 'recovered')),
    interest_rate DECIMAL(5, 2) DEFAULT 0,
    interest_amount DECIMAL(12, 2) DEFAULT 0,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_credits_sale ON credits(sale_id);
CREATE INDEX idx_credits_customer ON credits(customer_id);
CREATE INDEX idx_credits_status ON credits(status);
CREATE INDEX idx_credits_due_date ON credits(due_date);

CREATE TABLE credit_installments (
    id SERIAL PRIMARY KEY,
    credit_id INTEGER NOT NULL REFERENCES credits(id) ON DELETE CASCADE,
    installment_number INTEGER NOT NULL,
    due_date DATE NOT NULL,
    amount_due DECIMAL(12, 2) NOT NULL,
    amount_paid DECIMAL(12, 2) DEFAULT 0,
    status VARCHAR(50) DEFAULT 'pending' CHECK (status IN ('pending', 'paid', 'overdue')),
    paid_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(credit_id, installment_number)
);

CREATE INDEX idx_credit_installments_credit ON credit_installments(credit_id);
CREATE INDEX idx_credit_installments_due_date ON credit_installments(due_date);

-- ============================================
-- 11. PAIEMENTS
-- ============================================

CREATE TABLE payments (
    id SERIAL PRIMARY KEY,
    payment_type VARCHAR(50) NOT NULL CHECK (payment_type IN ('sale', 'reservation', 'credit')),
    reference_id INTEGER NOT NULL,
    payment_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    amount DECIMAL(12, 2) NOT NULL CHECK (amount > 0),
    payment_method VARCHAR(50) NOT NULL CHECK (payment_method IN ('cash', 'mobile_money', 'bank_transfer', 'mixed')),
    mobile_money_number VARCHAR(50),
    mobile_money_fees DECIMAL(12, 2) DEFAULT 0,
    cash_amount DECIMAL(12, 2) DEFAULT 0,
    mobile_money_amount DECIMAL(12, 2) DEFAULT 0,
    account_id INTEGER REFERENCES accounts(id) ON DELETE SET NULL,
    reference_number VARCHAR(255),
    notes TEXT,
    created_by INTEGER REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_payments_reference ON payments(payment_type, reference_id);
CREATE INDEX idx_payments_date ON payments(payment_date);
CREATE INDEX idx_payments_method ON payments(payment_method);
CREATE INDEX idx_payments_account ON payments(account_id);

-- ============================================
-- 12. DÉPENSES
-- ============================================

CREATE TABLE expense_categories (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE,
    description TEXT,
    icon VARCHAR(50),
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE expenses (
    id SERIAL PRIMARY KEY,
    category_id INTEGER NOT NULL REFERENCES expense_categories(id) ON DELETE RESTRICT,
    description TEXT NOT NULL,
    amount DECIMAL(12, 2) NOT NULL CHECK (amount > 0),
    expense_date DATE NOT NULL DEFAULT CURRENT_DATE,
    payment_method VARCHAR(50) CHECK (payment_method IN ('cash', 'mobile_money', 'bank_transfer')),
    account_id INTEGER REFERENCES accounts(id) ON DELETE SET NULL,
    reference VARCHAR(255),
    attachment_url VARCHAR(500),
    is_recurring BOOLEAN DEFAULT false,
    recurrence_frequency VARCHAR(50),
    notes TEXT,
    created_by INTEGER REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_expenses_category ON expenses(category_id);
CREATE INDEX idx_expenses_date ON expenses(expense_date);
CREATE INDEX idx_expenses_account ON expenses(account_id);

-- ============================================
-- 13. CONVERSION DEVISES
-- ============================================

CREATE TABLE exchange_rates (
    id SERIAL PRIMARY KEY,
    currency_from VARCHAR(10) NOT NULL,
    currency_to VARCHAR(10) NOT NULL,
    rate DECIMAL(10, 4) NOT NULL CHECK (rate > 0),
    effective_date DATE NOT NULL DEFAULT CURRENT_DATE,
    notes TEXT,
    created_by INTEGER REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(currency_from, currency_to, effective_date)
);

CREATE INDEX idx_exchange_rates_date ON exchange_rates(effective_date DESC);

-- ============================================
-- 14. NOTIFICATIONS
-- ============================================

CREATE TABLE notifications (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
    type VARCHAR(100) NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    reference_type VARCHAR(100),
    reference_id INTEGER,
    is_read BOOLEAN DEFAULT false,
    read_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_notifications_user ON notifications(user_id);
CREATE INDEX idx_notifications_read ON notifications(is_read) WHERE is_read = false;
CREATE INDEX idx_notifications_created ON notifications(created_at DESC);

-- ============================================
-- 15. LOGS & AUDIT
-- ============================================

CREATE TABLE audit_logs (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
    action VARCHAR(100) NOT NULL,
    table_name VARCHAR(100) NOT NULL,
    record_id INTEGER,
    old_values JSONB,
    new_values JSONB,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_audit_logs_user ON audit_logs(user_id);
CREATE INDEX idx_audit_logs_table ON audit_logs(table_name, record_id);
CREATE INDEX idx_audit_logs_created ON audit_logs(created_at DESC);

-- ============================================
-- 16. CONFIGURATION SYSTÈME
-- ============================================

CREATE TABLE system_settings (
    id SERIAL PRIMARY KEY,
    key VARCHAR(255) NOT NULL UNIQUE,
    value TEXT,
    value_type VARCHAR(50) DEFAULT 'string' CHECK (value_type IN ('string', 'number', 'boolean', 'json')),
    description TEXT,
    is_public BOOLEAN DEFAULT false,
    updated_by INTEGER REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_system_settings_key ON system_settings(key);

-- ============================================
-- 17. FONCTIONS & TRIGGERS (SYNTAXE CORRIGÉE)
-- ============================================

-- Fonction: Mise à jour automatique updated_at
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

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

-- Fonction: Libération automatique stock réservé
CREATE OR REPLACE FUNCTION release_reserved_stock()
RETURNS TRIGGER AS $$
BEGIN
    IF (NEW.status IN ('expired', 'cancelled') AND OLD.status NOT IN ('expired', 'cancelled')) THEN
        UPDATE product_variants pv
        SET reserved_quantity = reserved_quantity - si.quantity
        FROM sale_items si
        WHERE si.sale_id = NEW.sale_id
          AND si.variant_id = pv.id;
    END IF;
    
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trigger_release_reserved_stock
AFTER UPDATE ON reservations
FOR EACH ROW EXECUTE FUNCTION release_reserved_stock();

-- Fonction: Bloquer stock lors création réservation
CREATE OR REPLACE FUNCTION reserve_stock_on_reservation()
RETURNS TRIGGER AS $$
BEGIN
    UPDATE product_variants pv
    SET reserved_quantity = reserved_quantity + si.quantity
    FROM sale_items si
    WHERE si.sale_id = NEW.sale_id
      AND si.variant_id = pv.id;
    
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
$$ LANGUAGE plpgsql;

CREATE TRIGGER trigger_reserve_stock_on_reservation
AFTER INSERT ON reservations
FOR EACH ROW EXECUTE FUNCTION reserve_stock_on_reservation();

-- Fonction: Transfert stock vers credit_quantity
CREATE OR REPLACE FUNCTION move_stock_to_credit()
RETURNS TRIGGER AS $$
BEGIN
    UPDATE product_variants pv
    SET stock_quantity = stock_quantity - si.quantity,
        credit_quantity = credit_quantity + si.quantity
    FROM sale_items si
    WHERE si.sale_id = NEW.sale_id
      AND si.variant_id = pv.id;
    
    IF EXISTS (
        SELECT 1 FROM product_variants pv
        WHERE pv.stock_quantity < 0
    ) THEN
        RAISE EXCEPTION 'Stock physique insuffisant pour vente à crédit';
    END IF;
    
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trigger_move_stock_to_credit
AFTER INSERT ON credits
FOR EACH ROW EXECUTE FUNCTION move_stock_to_credit();

-- Fonction: Libération stock crédit
CREATE OR REPLACE FUNCTION release_credit_stock()
RETURNS TRIGGER AS $$
BEGIN
    IF (NEW.status = 'completed' AND OLD.status != 'completed') THEN
        UPDATE product_variants pv
        SET credit_quantity = credit_quantity - si.quantity
        FROM sale_items si
        WHERE si.sale_id = NEW.sale_id
          AND si.variant_id = pv.id;
    END IF;
    
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
$$ LANGUAGE plpgsql;

CREATE TRIGGER trigger_release_credit_stock
AFTER UPDATE ON credits
FOR EACH ROW EXECUTE FUNCTION release_credit_stock();

-- Fonction: Décrémentation stock vente immédiate
CREATE OR REPLACE FUNCTION decrease_stock_on_immediate_sale()
RETURNS TRIGGER AS $$
BEGIN
    IF NEW.sale_type = 'immediate' THEN
        UPDATE product_variants pv
        SET stock_quantity = stock_quantity - si.quantity
        FROM sale_items si
        WHERE si.sale_id = NEW.id
          AND si.variant_id = pv.id;
        
        IF EXISTS (
            SELECT 1 FROM product_variants pv
            JOIN sale_items si ON si.variant_id = pv.id
            WHERE si.sale_id = NEW.id
              AND (pv.stock_quantity - pv.reserved_quantity - pv.credit_quantity) < 0
        ) THEN
            RAISE EXCEPTION 'Stock disponible insuffisant pour vente immédiate';
        END IF;
    END IF;
    
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trigger_decrease_stock_on_immediate_sale
AFTER INSERT ON sales
FOR EACH ROW EXECUTE FUNCTION decrease_stock_on_immediate_sale();

-- Fonction: MAJ solde compte
CREATE OR REPLACE FUNCTION update_account_balance()
RETURNS TRIGGER AS $$
BEGIN
    IF NEW.type = 'credit' THEN
        NEW.balance_after := (SELECT current_balance FROM accounts WHERE id = NEW.account_id) + NEW.amount;
    ELSE
        NEW.balance_after := (SELECT current_balance FROM accounts WHERE id = NEW.account_id) - NEW.amount;
    END IF;
    
    UPDATE accounts 
    SET current_balance = NEW.balance_after 
    WHERE id = NEW.account_id;
    
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trigger_update_account_balance
BEFORE INSERT ON account_transactions
FOR EACH ROW EXECUTE FUNCTION update_account_balance();

-- ============================================
-- 18. VUES MÉTIER
-- ============================================

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

CREATE VIEW v_treasury_summary AS
SELECT 
    at.name AS account_type,
    at.display_name,
    COUNT(a.id) AS account_count,
    SUM(a.current_balance) AS total_balance
FROM account_types at
LEFT JOIN accounts a ON a.account_type_id = at.id AND a.is_active = true
GROUP BY at.id, at.name, at.display_name;

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

-- ============================================
-- 19. DONNÉES INITIALES
-- ============================================

INSERT INTO account_types (name, display_name, icon) VALUES
('cash', 'Espèces', 'cash'),
('mobile_money', 'Mobile Money', 'smartphone'),
('bank', 'Compte Bancaire', 'bank');

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

INSERT INTO attribute_types (name, display_name, input_type) VALUES
('pointure', 'Pointure', 'select'),
('taille', 'Taille', 'select'),
('couleur', 'Couleur', 'color'),
('longueur', 'Longueur (cm)', 'number'),
('matiere', 'Matière', 'text');

INSERT INTO attribute_values (attribute_type_id, value, sort_order)
SELECT 
    (SELECT id FROM attribute_types WHERE name = 'pointure'),
    value, 
    sort_order 
FROM (VALUES 
    ('35', 1), ('36', 2), ('37', 3), ('38', 4), ('39', 5),
    ('40', 6), ('41', 7), ('42', 8), ('43', 9), ('44', 10), ('45', 11)
) AS v(value, sort_order);

INSERT INTO attribute_values (attribute_type_id, value, sort_order)
SELECT 
    (SELECT id FROM attribute_types WHERE name = 'taille'),
    value,
    sort_order
FROM (VALUES 
    ('XS', 1), ('S', 2), ('M', 3), ('L', 4), ('XL', 5), ('XXL', 6), ('XXXL', 7)
) AS v(value, sort_order);

INSERT INTO system_settings (key, value, value_type, description, is_public) VALUES
('shop_name', 'Ma Boutique', 'string', 'Nom de la boutique', true),
('shop_address', '', 'string', 'Adresse boutique', true),
('reservation_default_days', '3', 'number', 'Délai réservation par défaut (jours)', false),
('low_stock_threshold', '5', 'number', 'Seuil alerte stock bas', false),
('credit_interest_rate', '0', 'number', 'Taux intérêt retard crédit (%)', false),
('currency_main', 'MGA', 'string', 'Devise principale', true),
('currency_secondary', 'CNY', 'string', 'Devise secondaire (achats)', true);

-- Utilisateur admin par défaut (mot de passe: "password")
INSERT INTO users (name, email, password, role) VALUES
('Administrateur', 'admin@boutique.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- ============================================
-- FIN DU SCHÉMA
-- ============================================