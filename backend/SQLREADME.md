

# 🧠 Logique du schéma : Pourquoi ces choix ?

## 1️⃣ **Caractéristiques variables (EAV Pattern adapté)**

### Le défi principal
Tu as des produits **très différents** :
- Chaussures → pointure + couleur
- Robes → taille + couleur  
- Pantalons → taille + longueur

### ❌ Solution naïve (à éviter)
```sql
-- MAUVAIS : colonnes fixes
ALTER TABLE products ADD COLUMN pointure VARCHAR(10);
ALTER TABLE products ADD COLUMN taille VARCHAR(10);
-- Problème : colonnes inutiles pour 90% des produits
```

### ✅ Solution adoptée (EAV flexible)

**3 tables clés** :

1. **`attribute_types`** : Définit les types de caractéristiques
   - `pointure`, `taille`, `couleur`, etc.

2. **`product_attributes`** : Relie produits → caractéristiques applicables
   - "Stan Smith a besoin de pointure + couleur"

3. **`variant_attribute_values`** : Stocke les valeurs réelles
   - "Cette Stan Smith = pointure 42, couleur blanche"

### Avantages scalables :
- ✅ Ajouter un nouveau type sans modifier la structure
- ✅ Chaque produit a **exactement** ses caractéristiques
- ✅ Requêtes simples avec jointures

---

## 2️⃣ **Variantes = Produits vendables**

### Concept fondamental
```
Produit (Stan Smith) → MODÈLE
   ├─ Variante 1 (42, blanc) → VENDABLE
   ├─ Variante 2 (43, noir) → VENDABLE
   └─ Variante 3 (40, blanc) → VENDABLE
```

- **`products`** = modèle générique
- **`product_variants`** = ce qu'on vend réellement
- Chaque variante a **son propre stock**

### Pourquoi ?
- Stock géré **par combinaison** (42 blanc ≠ 43 blanc)
- Prix peut varier (pointure rare = +500 Ar)
- SKU unique pour traçabilité

---

## 3️⃣ **Normalisation & intégrité**

### Relations strictes
```sql
ON DELETE CASCADE  -- Supprimer parent = supprimer enfants
ON DELETE RESTRICT -- Bloquer si enfants existent
ON DELETE SET NULL -- Garder l'historique
```

**Exemples** :
- Supprimer un produit → supprime ses variantes (`CASCADE`)
- Supprimer un fournisseur avec stock → **BLOQUÉ** (`RESTRICT`)
- Supprimer un client → les ventes restent (`SET NULL`)

---

## 4️⃣ **Scalabilité financière**

### Trésorerie multi-comptes
```
account_types → accounts → account_transactions
```

- Gérer **plusieurs caisses**
- Plusieurs **Mobile Money** (Orange, Airtel, Telma)
- Plusieurs **banques**

### Traçabilité complète
- Chaque mouvement → référence (vente, dépense, transfert)
- Solde calculé **après chaque opération**

---

## 5️⃣ **Performances & indexation**

### Index stratégiques
```sql
CREATE INDEX idx_sales_date ON sales(sale_date);
CREATE INDEX idx_variant_attributes_variant ON variant_attribute_values(variant_id);
```

**Pourquoi** :
- Recherches rapides par date
- Jointures optimisées
- Statistiques performantes

---

## 6️⃣ **Vues métier**

### `v_stock_overview`
Agrège **instantanément** :
- Produit + variantes + attributs + stock
- Évite 5 jointures répétées

### `v_treasury_balance`
- Solde global en temps réel
- Par type de compte

---

## 7️⃣ **Triggers intelligents**

### Automatisation
```sql
-- Stock décrémenté automatiquement à la vente
CREATE TRIGGER trigger_update_stock_on_sale
```

### Protection
```sql
IF stock < 0 THEN RAISE EXCEPTION
```

---

## 8️⃣ **Crédits & fiabilité clients**

### Table `credits`
- Montant dû / payé
- Date échéance
- Statut (en retard → pénalise `reliability_score`)

### Score dynamique
- Calculé selon historique paiements
- Aide à décider : "Faire crédit ou pas ?"

---

## 9️⃣ **Conversion Yuan ↔ Ariary**

### Table `exchange_rates`
- Taux historisés par date
- Calcul coût réel selon date d'achat

---

## 🔟 **Extensibilité**

### Facile d'ajouter :
- Nouveaux attributs (ex : `matiere`)
- Nouvelles catégories dépenses
- Nouveaux moyens paiement
- Multi-magasins (ajouter `store_id`)

### Sans casser l'existant 🎯

---

# 📊 Exemple concret

## Ajouter une Stan Smith blanche pointure 42

```sql
-- 1. Créer le produit modèle
INSERT INTO products (name, category_id, base_price, location) 
VALUES ('Stan Smith', 1, 25000, 'Rayon A-3');

-- 2. Définir ses attributs
INSERT INTO product_attributes (product_id, attribute_type_id)
VALUES (1, 1), (1, 3); -- pointure + couleur

-- 3. Créer la variante
INSERT INTO product_variants (product_id, sku, stock_quantity)
VALUES (1, 'STAN-42-WHT', 10);

-- 4. Ajouter les valeurs
INSERT INTO variant_attribute_values (variant_id, attribute_type_id, value)
VALUES 
    (1, 1, '42'),    -- pointure
    (1, 3, 'blanc'); -- couleur
```

---

# ✅ Résumé : Pourquoi ce schéma est optimal

| Critère | Solution |
|---------|----------|
| **Flexibilité** | EAV adapté = ajouter attributs sans migration |
| **Performance** | Index + vues + triggers |
| **Intégrité** | Contraintes strictes + types ENUM |
| **Scalabilité** | Normalisé (3NF) + extensible |
| **Métier** | Reflète exactement ton processus |
| **Maintenance** | Logique claire + commentaires |

---

**Ce schéma est prêt pour Laravel** avec Eloquent ORM et peut gérer **10 000+ produits** sans souci 🚀