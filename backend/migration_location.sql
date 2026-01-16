-- ============================================
-- TABLE LOCATIONS
-- ============================================

CREATE TABLE locations (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE,
    code VARCHAR(50) UNIQUE,
    warehouse VARCHAR(100),
    aisle VARCHAR(50),
    shelf VARCHAR(50),
    bin VARCHAR(50),
    description TEXT,
    capacity INTEGER,
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


CREATE TABLE product_locations (
    product_variant_id INTEGER REFERENCES product_variants(id) ON DELETE CASCADE,
    location_id INTEGER REFERENCES locations(id) ON DELETE CASCADE,
    quantity INTEGER NOT NULL CHECK (quantity >= 0),
    PRIMARY KEY (product_variant_id, location_id)
);
CREATE TABLE variant_attribute_values (
    id SERIAL PRIMARY KEY,
    variant_id INTEGER NOT NULL REFERENCES product_variants(id) ON DELETE CASCADE,
    attribute_value_id INTEGER NOT NULL REFERENCES attribute_values(id) ON DELETE RESTRICT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (variant_id, attribute_value_id)
);

-- Indexes pour optimiser les requêtes

CREATE INDEX idx_locations_active ON locations(is_active) WHERE is_active = true;
CREATE INDEX idx_locations_warehouse ON locations(warehouse);

-- Modifier la table product_variants pour utiliser location_id
ALTER TABLE product_variants 
    DROP COLUMN IF EXISTS location_detail,
    ADD COLUMN location_id INTEGER REFERENCES locations(id) ON DELETE SET NULL;

CREATE INDEX idx_product_variants_location ON product_variants(location_id);

-- ============================================
-- DONNÉES D'EXEMPLE
-- ============================================

INSERT INTO locations (name, code, warehouse, aisle, shelf, capacity, is_active) VALUES
('Entrepôt A - Allée 1 - Étagère 1', 'A-01-01', 'Entrepôt A', 'Allée 1', 'Étagère 1', 100, true),
('Entrepôt A - Allée 1 - Étagère 2', 'A-01-02', 'Entrepôt A', 'Allée 1', 'Étagère 2', 100, true),
('Entrepôt A - Allée 2 - Étagère 1', 'A-02-01', 'Entrepôt A', 'Allée 2', 'Étagère 1', 150, true),
('Entrepôt B - Allée 1 - Étagère 1', 'B-01-01', 'Entrepôt B', 'Allée 1', 'Étagère 1', 200, true),
('Magasin principal', 'SHOP-01', 'Magasin', NULL, NULL, 50, true),
('Réserve arrière', 'BACK-01', 'Réserve', NULL, NULL, 75, true);

-- ============================================
-- VUES UTILES
-- ============================================

-- Vue pour voir l'occupation des emplacements
CREATE OR REPLACE VIEW location_occupancy AS
SELECT 
    l.id,
    l.name,
    l.code,
    l.warehouse,
    l.capacity,
    COUNT(pv.id) AS variant_count,
    COALESCE(SUM(pv.stock_quantity), 0) AS total_stock,
    CASE 
        WHEN l.capacity > 0 THEN 
            ROUND((COALESCE(SUM(pv.stock_quantity), 0)::DECIMAL / l.capacity) * 100, 2)
        ELSE 0 
    END AS occupancy_percentage
FROM locations l
LEFT JOIN product_variants pv ON l.id = pv.location_id AND pv.is_active = true
WHERE l.is_active = true
GROUP BY l.id, l.name, l.code, l.warehouse, l.capacity
ORDER BY l.warehouse, l.code;