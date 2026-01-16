--
-- PostgreSQL database dump
--

\restrict Jkp05na3WYc5unbayrTwYzLikXDjgfk80siSxggiEAEKeZxao7JO20xbbMDDdtV

-- Dumped from database version 15.15 (Debian 15.15-1.pgdg13+1)
-- Dumped by pg_dump version 15.15 (Debian 15.15-1.pgdg13+1)

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

--
-- Name: public; Type: SCHEMA; Schema: -; Owner: express_sale_user
--

-- *not* creating schema, since initdb creates it


ALTER SCHEMA public OWNER TO express_sale_user;

--
-- Name: SCHEMA public; Type: COMMENT; Schema: -; Owner: express_sale_user
--

COMMENT ON SCHEMA public IS '';


--
-- Name: uuid-ossp; Type: EXTENSION; Schema: -; Owner: -
--

CREATE EXTENSION IF NOT EXISTS "uuid-ossp" WITH SCHEMA public;


--
-- Name: EXTENSION "uuid-ossp"; Type: COMMENT; Schema: -; Owner: 
--

COMMENT ON EXTENSION "uuid-ossp" IS 'generate universally unique identifiers (UUIDs)';


--
-- Name: calculate_freight_forwarder_score(integer); Type: FUNCTION; Schema: public; Owner: express_sale_user
--

CREATE FUNCTION public.calculate_freight_forwarder_score(ff_id_param integer) RETURNS numeric
    LANGUAGE plpgsql
    AS $$
DECLARE
    delivery_rate numeric;
    weighted_service_avg numeric;
    final_score numeric;
BEGIN
    -- Récupérer les stats du transitaire
    SELECT 
        -- Taux de livraison basé sur la VALEUR
        CASE 
            WHEN total_value_shipped > 0 
            THEN (total_value_delivered / total_value_shipped) * 100
            ELSE 100
        END,
        -- Service moyen PONDÉRÉ par valeur
        CASE 
            WHEN total_weighted_shipment_value > 0 
            THEN weighted_service_sum / total_weighted_shipment_value
            ELSE 5.00
        END
    INTO delivery_rate, weighted_service_avg
    FROM freight_forwarders
    WHERE id = ff_id_param;
    
    -- Calcul du score: 60% taux de livraison (par valeur) + 40% service pondéré
    final_score := ((delivery_rate / 100) * 10 * 0.6) + (weighted_service_avg * 0.4);
    
    RETURN ROUND(final_score, 2);
END;
$$;


ALTER FUNCTION public.calculate_freight_forwarder_score(ff_id_param integer) OWNER TO express_sale_user;

--
-- Name: FUNCTION calculate_freight_forwarder_score(ff_id_param integer); Type: COMMENT; Schema: public; Owner: express_sale_user
--

COMMENT ON FUNCTION public.calculate_freight_forwarder_score(ff_id_param integer) IS 'Calcule le score transitaire pondéré par valeur monétaire';


--
-- Name: calculate_supplier_score(integer); Type: FUNCTION; Schema: public; Owner: express_sale_user
--

CREATE FUNCTION public.calculate_supplier_score(supplier_id_param integer) RETURNS numeric
    LANGUAGE plpgsql
    AS $$
DECLARE
    delivery_rate numeric;
    weighted_quality_avg numeric;
    final_score numeric;
BEGIN
    -- Récupérer les stats du fournisseur
    SELECT 
        -- Taux de livraison basé sur la VALEUR (pas la quantité)
        CASE 
            WHEN total_value_ordered > 0 
            THEN (total_value_received / total_value_ordered) * 100
            ELSE 100
        END,
        -- Qualité moyenne PONDÉRÉE par valeur
        CASE 
            WHEN total_weighted_value > 0 
            THEN weighted_quality_sum / total_weighted_value
            ELSE 5.00
        END
    INTO delivery_rate, weighted_quality_avg
    FROM suppliers
    WHERE id = supplier_id_param;
    
    -- Calcul du score: 50% taux de livraison (par valeur) + 50% qualité pondérée
    -- Taux de livraison converti sur 10: (delivery_rate / 100) * 10
    -- Qualité déjà sur 10
    final_score := ((delivery_rate / 100) * 10 * 0.5) + (weighted_quality_avg * 0.5);
    
    RETURN ROUND(final_score, 2);
END;
$$;


ALTER FUNCTION public.calculate_supplier_score(supplier_id_param integer) OWNER TO express_sale_user;

--
-- Name: FUNCTION calculate_supplier_score(supplier_id_param integer); Type: COMMENT; Schema: public; Owner: express_sale_user
--

COMMENT ON FUNCTION public.calculate_supplier_score(supplier_id_param integer) IS 'Calcule le score fournisseur pondéré par valeur monétaire';


--
-- Name: complete_reservation_payment(integer, integer, integer, numeric, character varying, character varying, integer); Type: FUNCTION; Schema: public; Owner: express_sale_user
--

CREATE FUNCTION public.complete_reservation_payment(p_reservation_id integer, p_sale_id integer, p_account_id integer, p_remaining_amount numeric, p_payment_method character varying, p_reference_number character varying DEFAULT NULL::character varying, p_created_by integer DEFAULT NULL::integer) RETURNS bigint
    LANGUAGE plpgsql
    AS $$
DECLARE
    v_transaction_id bigint;
    v_transaction_type_id integer;
    v_current_balance numeric;
BEGIN
    SELECT current_balance INTO v_current_balance
    FROM accounts WHERE id = p_account_id;
    
    SELECT id INTO v_transaction_type_id 
    FROM transaction_types WHERE code = 'SALE';
    
    -- Créer la transaction pour le solde restant
    INSERT INTO account_transactions (
        account_id, transaction_type_id, amount,
        balance_before, balance_after,
        sale_id, description, created_by
    ) VALUES (
        p_account_id, v_transaction_type_id, p_remaining_amount,
        v_current_balance, v_current_balance + p_remaining_amount,
        p_sale_id, 'Solde réservation', p_created_by
    ) RETURNING id INTO v_transaction_id;
    
    -- Mettre à jour le solde du compte
    UPDATE accounts 
    SET current_balance = current_balance + p_remaining_amount,
        updated_at = CURRENT_TIMESTAMP
    WHERE id = p_account_id;
    
    -- Mettre à jour la vente
    UPDATE sales
    SET 
        payment_status = 'paid',
        updated_at = CURRENT_TIMESTAMP
    WHERE id = p_sale_id;
    
    -- Mettre à jour la réservation
    UPDATE reservations
    SET 
        status = 'completed',
        completed_at = CURRENT_TIMESTAMP
    WHERE id = p_reservation_id;
    
    RETURN v_transaction_id;
END;
$$;


ALTER FUNCTION public.complete_reservation_payment(p_reservation_id integer, p_sale_id integer, p_account_id integer, p_remaining_amount numeric, p_payment_method character varying, p_reference_number character varying, p_created_by integer) OWNER TO express_sale_user;

--
-- Name: FUNCTION complete_reservation_payment(p_reservation_id integer, p_sale_id integer, p_account_id integer, p_remaining_amount numeric, p_payment_method character varying, p_reference_number character varying, p_created_by integer); Type: COMMENT; Schema: public; Owner: express_sale_user
--

COMMENT ON FUNCTION public.complete_reservation_payment(p_reservation_id integer, p_sale_id integer, p_account_id integer, p_remaining_amount numeric, p_payment_method character varying, p_reference_number character varying, p_created_by integer) IS 'Finalise le paiement d''une réservation';


--
-- Name: create_account_with_initial_balance(integer, character varying, character varying, numeric, text, integer); Type: FUNCTION; Schema: public; Owner: express_sale_user
--

CREATE FUNCTION public.create_account_with_initial_balance(p_account_type_id integer, p_name character varying, p_account_number character varying, p_initial_balance numeric, p_notes text, p_created_by integer) RETURNS integer
    LANGUAGE plpgsql
    AS $$
DECLARE
    v_account_id integer;
    v_transaction_type_id integer;
BEGIN
    -- 1️⃣ Créer le compte
    INSERT INTO accounts (
        account_type_id,
        name,
        account_number,
        initial_balance,
        current_balance,
        notes,
        created_by
    )
    VALUES (
        p_account_type_id,
        p_name,
        p_account_number,
        p_initial_balance,
        p_initial_balance,
        p_notes,
        p_created_by
    )
    RETURNING id INTO v_account_id;

    -- 2️⃣ Récupérer automatiquement le type OPENING_BALANCE
    SELECT id
    INTO v_transaction_type_id
    FROM transaction_types
    WHERE code = 'OPENING_BALANCE'
    LIMIT 1;

    IF v_transaction_type_id IS NULL THEN
        RAISE EXCEPTION 'Transaction type OPENING_BALANCE introuvable';
    END IF;

    -- 3️⃣ Créer la transaction si solde > 0
    IF p_initial_balance > 0 THEN
        INSERT INTO account_transactions (
            account_id,
            transaction_type_id,
            amount,
            balance_before,
            balance_after,
            transaction_date,
            description,
            created_by
        )
        VALUES (
            v_account_id,
            v_transaction_type_id,
            p_initial_balance,
            0,                     -- solde avant l'ouverture
            p_initial_balance,      -- solde après
            CURRENT_TIMESTAMP,
            'Solde initial du compte',
            p_created_by
        );
    END IF;

    RETURN v_account_id;
END;
$$;


ALTER FUNCTION public.create_account_with_initial_balance(p_account_type_id integer, p_name character varying, p_account_number character varying, p_initial_balance numeric, p_notes text, p_created_by integer) OWNER TO express_sale_user;

--
-- Name: FUNCTION create_account_with_initial_balance(p_account_type_id integer, p_name character varying, p_account_number character varying, p_initial_balance numeric, p_notes text, p_created_by integer); Type: COMMENT; Schema: public; Owner: express_sale_user
--

COMMENT ON FUNCTION public.create_account_with_initial_balance(p_account_type_id integer, p_name character varying, p_account_number character varying, p_initial_balance numeric, p_notes text, p_created_by integer) IS 'Crée un compte avec son solde initial et enregistre la transaction';


--
-- Name: recalculate_variant_stock(); Type: FUNCTION; Schema: public; Owner: express_sale_user
--

CREATE FUNCTION public.recalculate_variant_stock() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
    -- Recalculer le stock total du variant
    UPDATE product_variants
    SET stock_quantity = COALESCE((
        SELECT SUM(quantity)
        FROM product_variant_locations
        WHERE variant_id = COALESCE(NEW.variant_id, OLD.variant_id)
    ), 0)
    WHERE id = COALESCE(NEW.variant_id, OLD.variant_id);
    
    RETURN COALESCE(NEW, OLD);
END;
$$;


ALTER FUNCTION public.recalculate_variant_stock() OWNER TO express_sale_user;

--
-- Name: record_account_transfer(integer, integer, numeric, text, integer); Type: FUNCTION; Schema: public; Owner: express_sale_user
--

CREATE FUNCTION public.record_account_transfer(p_from_account_id integer, p_to_account_id integer, p_amount numeric, p_description text, p_created_by integer) RETURNS bigint
    LANGUAGE plpgsql
    AS $$
DECLARE
    v_transaction_id_from bigint;
    v_transaction_id_to bigint;
    v_transaction_type_id integer;
    v_balance_from numeric;
    v_balance_to numeric;
BEGIN
    -- Vérifier les soldes
    SELECT current_balance INTO v_balance_from
    FROM accounts WHERE id = p_from_account_id;
    
    SELECT current_balance INTO v_balance_to
    FROM accounts WHERE id = p_to_account_id;
    
    IF v_balance_from < p_amount THEN
        RAISE EXCEPTION 'Solde insuffisant dans le compte source';
    END IF;
    
    SELECT id INTO v_transaction_type_id 
    FROM transaction_types WHERE code = 'TRANSFER';
    
    -- Transaction de débit (compte source)
    INSERT INTO account_transactions (
        account_id, transaction_type_id, amount,
        balance_before, balance_after,
        related_account_id, description, created_by
    ) VALUES (
        p_from_account_id, v_transaction_type_id, p_amount,
        v_balance_from, v_balance_from - p_amount,
        p_to_account_id, 'Transfert sortant: ' || COALESCE(p_description, ''), p_created_by
    ) RETURNING id INTO v_transaction_id_from;
    
    -- Transaction de crédit (compte destination)
    INSERT INTO account_transactions (
        account_id, transaction_type_id, amount,
        balance_before, balance_after,
        related_account_id, related_transaction_id, description, created_by
    ) VALUES (
        p_to_account_id, v_transaction_type_id, p_amount,
        v_balance_to, v_balance_to + p_amount,
        p_from_account_id, v_transaction_id_from, 
        'Transfert entrant: ' || COALESCE(p_description, ''), p_created_by
    ) RETURNING id INTO v_transaction_id_to;
    
    -- Lier les deux transactions
    UPDATE account_transactions 
    SET related_transaction_id = v_transaction_id_to
    WHERE id = v_transaction_id_from;
    
    -- Mettre à jour les soldes
    UPDATE accounts SET current_balance = current_balance - p_amount, updated_at = CURRENT_TIMESTAMP
    WHERE id = p_from_account_id;
    
    UPDATE accounts SET current_balance = current_balance + p_amount, updated_at = CURRENT_TIMESTAMP
    WHERE id = p_to_account_id;
    
    RETURN v_transaction_id_from;
END;
$$;


ALTER FUNCTION public.record_account_transfer(p_from_account_id integer, p_to_account_id integer, p_amount numeric, p_description text, p_created_by integer) OWNER TO express_sale_user;

--
-- Name: record_credit_payment(integer, integer, numeric, character varying, character varying, text, integer); Type: FUNCTION; Schema: public; Owner: express_sale_user
--

CREATE FUNCTION public.record_credit_payment(p_credit_installment_id integer, p_account_id integer, p_amount_paid numeric, p_payment_method character varying, p_reference_number character varying, p_notes text, p_created_by integer) RETURNS bigint
    LANGUAGE plpgsql
    AS $$
DECLARE
    v_transaction_id bigint;
    v_transaction_type_id integer;
    v_current_balance numeric;
    v_credit_id integer;
    v_customer_id integer;
    v_installment_amount_due numeric;
    v_installment_amount_paid numeric;
BEGIN
    -- Récupérer les informations de l'échéance
    SELECT ci.credit_id, ci.amount_due, ci.amount_paid, c.customer_id
    INTO v_credit_id, v_installment_amount_due, v_installment_amount_paid, v_customer_id
    FROM credit_installments ci
    JOIN credits c ON ci.credit_id = c.id
    WHERE ci.id = p_credit_installment_id;
    
    -- Vérifier que le montant n'excède pas ce qui reste à payer
    IF (v_installment_amount_paid + p_amount_paid) > v_installment_amount_due THEN
        RAISE EXCEPTION 'Le montant payé (%) excède le montant dû (%)', 
            v_installment_amount_paid + p_amount_paid, v_installment_amount_due;
    END IF;
    
    -- Récupérer le solde actuel du compte
    SELECT current_balance INTO v_current_balance
    FROM accounts WHERE id = p_account_id;
    
    -- Récupérer le type de transaction
    SELECT id INTO v_transaction_type_id 
    FROM transaction_types WHERE code = 'CREDIT_PAYMENT';
    
    -- Créer la transaction dans account_transactions
    INSERT INTO account_transactions (
        account_id, transaction_type_id, amount,
        balance_before, balance_after,
        sale_id, description, notes, created_by
    ) VALUES (
        p_account_id, v_transaction_type_id, p_amount_paid,
        v_current_balance, v_current_balance + p_amount_paid,
        (SELECT sale_id FROM credits WHERE id = v_credit_id),
        'Paiement crédit - Échéance #' || p_credit_installment_id,
        p_notes, p_created_by
    ) RETURNING id INTO v_transaction_id;
    
    -- Mettre à jour le solde du compte
    UPDATE accounts 
    SET current_balance = current_balance + p_amount_paid,
        updated_at = CURRENT_TIMESTAMP
    WHERE id = p_account_id;
    
    -- Mettre à jour l'échéance
    UPDATE credit_installments
    SET 
        amount_paid = amount_paid + p_amount_paid,
        status = CASE 
            WHEN (amount_paid + p_amount_paid) >= amount_due THEN 'paid'
            ELSE 'partial'
        END,
        paid_date = CASE 
            WHEN (amount_paid + p_amount_paid) >= amount_due THEN CURRENT_DATE
            ELSE paid_date
        END,
        account_id = p_account_id,
        payment_method = p_payment_method,
        reference_number = p_reference_number,
        transaction_id = v_transaction_id
    WHERE id = p_credit_installment_id;
    
    -- Mettre à jour le crédit global
    UPDATE credits
    SET 
        amount_paid = amount_paid + p_amount_paid,
        amount_due = amount_due - p_amount_paid,
        last_payment_date = CURRENT_DATE,
        status = CASE
            WHEN (amount_due - p_amount_paid) <= 0 THEN 'completed'
            WHEN amount_paid > 0 THEN 'partial_paid'
            ELSE status
        END,
        updated_at = CURRENT_TIMESTAMP
    WHERE id = v_credit_id;
    
    -- Créer une notification pour l'utilisateur
    INSERT INTO notifications (
        user_id, type, title, message,
        reference_type, reference_id
    ) VALUES (
        p_created_by,
        'credit_payment',
        'Paiement de crédit reçu',
        'Paiement de ' || p_amount_paid || ' Ar reçu pour le crédit #' || v_credit_id,
        'credit',
        v_credit_id
    );
    
    RETURN v_transaction_id;
END;
$$;


ALTER FUNCTION public.record_credit_payment(p_credit_installment_id integer, p_account_id integer, p_amount_paid numeric, p_payment_method character varying, p_reference_number character varying, p_notes text, p_created_by integer) OWNER TO express_sale_user;

--
-- Name: FUNCTION record_credit_payment(p_credit_installment_id integer, p_account_id integer, p_amount_paid numeric, p_payment_method character varying, p_reference_number character varying, p_notes text, p_created_by integer); Type: COMMENT; Schema: public; Owner: express_sale_user
--

COMMENT ON FUNCTION public.record_credit_payment(p_credit_installment_id integer, p_account_id integer, p_amount_paid numeric, p_payment_method character varying, p_reference_number character varying, p_notes text, p_created_by integer) IS 'Enregistre un paiement de crédit et met à jour le compte bancaire';


--
-- Name: record_credit_sale_initial_payment(integer, integer, integer, numeric, character varying, character varying, integer); Type: FUNCTION; Schema: public; Owner: express_sale_user
--

CREATE FUNCTION public.record_credit_sale_initial_payment(p_sale_id integer, p_credit_id integer, p_account_id integer, p_amount_paid numeric, p_payment_method character varying, p_reference_number character varying, p_created_by integer) RETURNS bigint
    LANGUAGE plpgsql
    AS $$
DECLARE
    v_transaction_id bigint;
    v_transaction_type_id integer;
    v_current_balance numeric;
BEGIN
    -- Si montant initial > 0
    IF p_amount_paid > 0 THEN
        SELECT current_balance INTO v_current_balance
        FROM accounts WHERE id = p_account_id;
        
        SELECT id INTO v_transaction_type_id 
        FROM transaction_types WHERE code = 'SALE';
        
        -- Créer la transaction
        INSERT INTO account_transactions (
            account_id, transaction_type_id, amount,
            balance_before, balance_after,
            sale_id, description, created_by
        ) VALUES (
            p_account_id, v_transaction_type_id, p_amount_paid,
            v_current_balance, v_current_balance + p_amount_paid,
            p_sale_id, 'Acompte vente à crédit', p_created_by
        ) RETURNING id INTO v_transaction_id;
        
        -- Mettre à jour le solde du compte
        UPDATE accounts 
        SET current_balance = current_balance + p_amount_paid,
            updated_at = CURRENT_TIMESTAMP
        WHERE id = p_account_id;
        
        -- Définir le compte par défaut pour ce crédit
        UPDATE credits
        SET default_account_id = p_account_id
        WHERE id = p_credit_id;
    END IF;
    
    RETURN v_transaction_id;
END;
$$;


ALTER FUNCTION public.record_credit_sale_initial_payment(p_sale_id integer, p_credit_id integer, p_account_id integer, p_amount_paid numeric, p_payment_method character varying, p_reference_number character varying, p_created_by integer) OWNER TO express_sale_user;

--
-- Name: FUNCTION record_credit_sale_initial_payment(p_sale_id integer, p_credit_id integer, p_account_id integer, p_amount_paid numeric, p_payment_method character varying, p_reference_number character varying, p_created_by integer); Type: COMMENT; Schema: public; Owner: express_sale_user
--

COMMENT ON FUNCTION public.record_credit_sale_initial_payment(p_sale_id integer, p_credit_id integer, p_account_id integer, p_amount_paid numeric, p_payment_method character varying, p_reference_number character varying, p_created_by integer) IS 'Enregistre l''acompte initial d''une vente à crédit';


--
-- Name: record_direct_sale_payment(integer, integer, character varying, numeric, numeric, numeric, character varying, numeric, numeric, character varying, integer); Type: FUNCTION; Schema: public; Owner: express_sale_user
--

CREATE FUNCTION public.record_direct_sale_payment(p_sale_id integer, p_account_id integer, p_payment_method character varying, p_amount numeric, p_cash_amount numeric DEFAULT 0, p_mobile_money_amount numeric DEFAULT 0, p_mobile_money_number character varying DEFAULT NULL::character varying, p_mobile_money_fees numeric DEFAULT 0, p_bank_transfer_amount numeric DEFAULT 0, p_reference_number character varying DEFAULT NULL::character varying, p_created_by integer DEFAULT NULL::integer) RETURNS bigint
    LANGUAGE plpgsql
    AS $$
DECLARE
    v_transaction_id bigint;
    v_transaction_type_id integer;
    v_current_balance numeric;
    v_sale_total numeric;
    v_customer_id integer;
BEGIN
    -- Récupérer le montant total de la vente
    SELECT total_amount, customer_id INTO v_sale_total, v_customer_id
    FROM sales WHERE id = p_sale_id;
    
    -- Vérifier que le montant correspond
    IF p_amount != v_sale_total THEN
        RAISE EXCEPTION 'Le montant payé (%) ne correspond pas au total de la vente (%)', 
            p_amount, v_sale_total;
    END IF;
    
    -- Pour paiement mixte, vérifier que la somme correspond
    IF p_payment_method = 'mixed' THEN
        IF (p_cash_amount + p_mobile_money_amount + p_bank_transfer_amount) != p_amount THEN
            RAISE EXCEPTION 'La somme des paiements mixtes ne correspond pas au total';
        END IF;
    END IF;
    
    -- Récupérer le solde actuel du compte
    SELECT current_balance INTO v_current_balance
    FROM accounts WHERE id = p_account_id;
    
    -- Récupérer le type de transaction
    SELECT id INTO v_transaction_type_id 
    FROM transaction_types WHERE code = 'SALE';
    
    -- Créer la transaction dans account_transactions
    INSERT INTO account_transactions (
        account_id, transaction_type_id, amount,
        balance_before, balance_after,
        sale_id, 
        description, 
        notes,
        created_by
    ) VALUES (
        p_account_id, 
        v_transaction_type_id, 
        p_amount,
        v_current_balance, 
        v_current_balance + p_amount,
        p_sale_id,
        'Vente directe - ' || p_payment_method,
        CASE 
            WHEN p_payment_method = 'mixed' THEN 
                'Cash: ' || p_cash_amount || ' Ar, Mobile: ' || p_mobile_money_amount || ' Ar, Banque: ' || p_bank_transfer_amount || ' Ar'
            WHEN p_payment_method = 'mobile_money' THEN
                'Mobile Money: ' || COALESCE(p_mobile_money_number, '') || ' (Frais: ' || p_mobile_money_fees || ' Ar)'
            ELSE NULL
        END,
        p_created_by
    ) RETURNING id INTO v_transaction_id;
    
    -- Mettre à jour le solde du compte (moins les frais mobile money si applicable)
    UPDATE accounts 
    SET current_balance = current_balance + p_amount - COALESCE(p_mobile_money_fees, 0),
        updated_at = CURRENT_TIMESTAMP
    WHERE id = p_account_id;
    
    -- Si des frais mobile money existent, créer une dépense pour ces frais
    IF p_mobile_money_fees > 0 THEN
        DECLARE
            v_expense_transaction_id bigint;
            v_expense_type_id integer;
            v_expense_category_id integer;
            v_new_balance numeric;
        BEGIN
            SELECT current_balance INTO v_new_balance
            FROM accounts WHERE id = p_account_id;
            
            SELECT id INTO v_expense_type_id 
            FROM transaction_types WHERE code = 'OPERATING_EXPENSE';
            
            SELECT id INTO v_expense_category_id
            FROM expense_categories WHERE name = 'Autres';
            
            INSERT INTO account_transactions (
                account_id, transaction_type_id, amount,
                balance_before, balance_after,
                expense_category_id,
                recipient_name,
                description,
                created_by
            ) VALUES (
                p_account_id, v_expense_type_id, p_mobile_money_fees,
                v_new_balance, v_new_balance - p_mobile_money_fees,
                v_expense_category_id,
                'Opérateur Mobile Money',
                'Frais de transaction mobile money - Vente #' || p_sale_id,
                p_created_by
            );
        END;
    END IF;
    
    -- Mettre à jour la vente avec les informations de paiement
    UPDATE sales
    SET 
        account_id = p_account_id,
        payment_method = p_payment_method,
        cash_amount = p_cash_amount,
        mobile_money_amount = p_mobile_money_amount,
        mobile_money_number = p_mobile_money_number,
        mobile_money_fees = p_mobile_money_fees,
        bank_transfer_amount = p_bank_transfer_amount,
        reference_number = p_reference_number,
        transaction_id = v_transaction_id,
        payment_status = 'paid',
        updated_at = CURRENT_TIMESTAMP
    WHERE id = p_sale_id;
    
    -- Créer une notification
    INSERT INTO notifications (
        user_id, type, title, message,
        reference_type, reference_id
    ) VALUES (
        p_created_by,
        'sale_completed',
        'Vente complétée',
        'Vente #' || p_sale_id || ' payée: ' || p_amount || ' Ar (' || p_payment_method || ')',
        'sale',
        p_sale_id
    );
    
    RETURN v_transaction_id;
END;
$$;


ALTER FUNCTION public.record_direct_sale_payment(p_sale_id integer, p_account_id integer, p_payment_method character varying, p_amount numeric, p_cash_amount numeric, p_mobile_money_amount numeric, p_mobile_money_number character varying, p_mobile_money_fees numeric, p_bank_transfer_amount numeric, p_reference_number character varying, p_created_by integer) OWNER TO express_sale_user;

--
-- Name: FUNCTION record_direct_sale_payment(p_sale_id integer, p_account_id integer, p_payment_method character varying, p_amount numeric, p_cash_amount numeric, p_mobile_money_amount numeric, p_mobile_money_number character varying, p_mobile_money_fees numeric, p_bank_transfer_amount numeric, p_reference_number character varying, p_created_by integer); Type: COMMENT; Schema: public; Owner: express_sale_user
--

COMMENT ON FUNCTION public.record_direct_sale_payment(p_sale_id integer, p_account_id integer, p_payment_method character varying, p_amount numeric, p_cash_amount numeric, p_mobile_money_amount numeric, p_mobile_money_number character varying, p_mobile_money_fees numeric, p_bank_transfer_amount numeric, p_reference_number character varying, p_created_by integer) IS 'Enregistre le paiement d''une vente directe et met à jour le compte';


--
-- Name: record_freight_payment(integer, integer, integer, numeric, character varying, text, integer); Type: FUNCTION; Schema: public; Owner: express_sale_user
--

CREATE FUNCTION public.record_freight_payment(p_account_id integer, p_freight_forwarder_id integer, p_stock_receipt_id integer, p_amount numeric, p_reference_number character varying, p_notes text, p_created_by integer) RETURNS bigint
    LANGUAGE plpgsql
    AS $$
DECLARE
    v_transaction_id bigint;
    v_transaction_type_id integer;
    v_current_balance numeric;
BEGIN
    SELECT current_balance INTO v_current_balance
    FROM accounts WHERE id = p_account_id;
    
    IF v_current_balance < p_amount THEN
        RAISE EXCEPTION 'Solde insuffisant dans le compte';
    END IF;
    
    SELECT id INTO v_transaction_type_id 
    FROM transaction_types WHERE code = 'FREIGHT_PAYMENT';
    
    INSERT INTO account_transactions (
        account_id, transaction_type_id, amount,
        balance_before, balance_after,
        freight_forwarder_id, stock_receipt_id, reference_number,
        notes, created_by
    ) VALUES (
        p_account_id, v_transaction_type_id, p_amount,
        v_current_balance, v_current_balance - p_amount,
        p_freight_forwarder_id, p_stock_receipt_id, p_reference_number,
        p_notes, p_created_by
    ) RETURNING id INTO v_transaction_id;
    
    UPDATE accounts 
    SET current_balance = current_balance - p_amount,
        updated_at = CURRENT_TIMESTAMP
    WHERE id = p_account_id;
    
    RETURN v_transaction_id;
END;
$$;


ALTER FUNCTION public.record_freight_payment(p_account_id integer, p_freight_forwarder_id integer, p_stock_receipt_id integer, p_amount numeric, p_reference_number character varying, p_notes text, p_created_by integer) OWNER TO express_sale_user;

--
-- Name: record_operating_expense(integer, integer, numeric, character varying, text, text, integer); Type: FUNCTION; Schema: public; Owner: express_sale_user
--

CREATE FUNCTION public.record_operating_expense(p_account_id integer, p_expense_category_id integer, p_amount numeric, p_recipient_name character varying, p_description text, p_notes text, p_created_by integer) RETURNS bigint
    LANGUAGE plpgsql
    AS $$
DECLARE
    v_transaction_id bigint;
    v_transaction_type_id integer;
    v_current_balance numeric;
BEGIN
    SELECT current_balance INTO v_current_balance
    FROM accounts WHERE id = p_account_id;
    
    IF v_current_balance < p_amount THEN
        RAISE EXCEPTION 'Solde insuffisant dans le compte';
    END IF;
    
    SELECT id INTO v_transaction_type_id 
    FROM transaction_types WHERE code = 'OPERATING_EXPENSE';
    
    INSERT INTO account_transactions (
        account_id, transaction_type_id, amount,
        balance_before, balance_after,
        expense_category_id, recipient_name,
        description, notes, created_by
    ) VALUES (
        p_account_id, v_transaction_type_id, p_amount,
        v_current_balance, v_current_balance - p_amount,
        p_expense_category_id, p_recipient_name,
        p_description, p_notes, p_created_by
    ) RETURNING id INTO v_transaction_id;
    
    UPDATE accounts 
    SET current_balance = current_balance - p_amount,
        updated_at = CURRENT_TIMESTAMP
    WHERE id = p_account_id;
    
    RETURN v_transaction_id;
END;
$$;


ALTER FUNCTION public.record_operating_expense(p_account_id integer, p_expense_category_id integer, p_amount numeric, p_recipient_name character varying, p_description text, p_notes text, p_created_by integer) OWNER TO express_sale_user;

--
-- Name: record_reservation_deposit(integer, integer, integer, numeric, character varying, character varying, integer); Type: FUNCTION; Schema: public; Owner: express_sale_user
--

CREATE FUNCTION public.record_reservation_deposit(p_reservation_id integer, p_sale_id integer, p_account_id integer, p_deposit_amount numeric, p_payment_method character varying, p_reference_number character varying DEFAULT NULL::character varying, p_created_by integer DEFAULT NULL::integer) RETURNS bigint
    LANGUAGE plpgsql
    AS $$
DECLARE
    v_transaction_id bigint;
    v_transaction_type_id integer;
    v_current_balance numeric;
BEGIN
    -- Si dépôt > 0
    IF p_deposit_amount > 0 THEN
        SELECT current_balance INTO v_current_balance
        FROM accounts WHERE id = p_account_id;
        
        SELECT id INTO v_transaction_type_id 
        FROM transaction_types WHERE code = 'SALE';
        
        -- Créer la transaction
        INSERT INTO account_transactions (
            account_id, transaction_type_id, amount,
            balance_before, balance_after,
            sale_id, description, created_by
        ) VALUES (
            p_account_id, v_transaction_type_id, p_deposit_amount,
            v_current_balance, v_current_balance + p_deposit_amount,
            p_sale_id, 'Acompte réservation', p_created_by
        ) RETURNING id INTO v_transaction_id;
        
        -- Mettre à jour le solde du compte
        UPDATE accounts 
        SET current_balance = current_balance + p_deposit_amount,
            updated_at = CURRENT_TIMESTAMP
        WHERE id = p_account_id;
        
        -- Mettre à jour la vente
        UPDATE sales
        SET 
            account_id = p_account_id,
            payment_method = p_payment_method,
            reference_number = p_reference_number,
            transaction_id = v_transaction_id,
            payment_status = 'partial',
            updated_at = CURRENT_TIMESTAMP
        WHERE id = p_sale_id;
        
        -- Mettre à jour la réservation
        UPDATE reservations
        SET status = 'confirmed'
        WHERE id = p_reservation_id;
    END IF;
    
    RETURN v_transaction_id;
END;
$$;


ALTER FUNCTION public.record_reservation_deposit(p_reservation_id integer, p_sale_id integer, p_account_id integer, p_deposit_amount numeric, p_payment_method character varying, p_reference_number character varying, p_created_by integer) OWNER TO express_sale_user;

--
-- Name: FUNCTION record_reservation_deposit(p_reservation_id integer, p_sale_id integer, p_account_id integer, p_deposit_amount numeric, p_payment_method character varying, p_reference_number character varying, p_created_by integer); Type: COMMENT; Schema: public; Owner: express_sale_user
--

COMMENT ON FUNCTION public.record_reservation_deposit(p_reservation_id integer, p_sale_id integer, p_account_id integer, p_deposit_amount numeric, p_payment_method character varying, p_reference_number character varying, p_created_by integer) IS 'Enregistre l''acompte d''une réservation';


--
-- Name: record_sale_transaction(integer, integer, numeric, integer); Type: FUNCTION; Schema: public; Owner: express_sale_user
--

CREATE FUNCTION public.record_sale_transaction(p_account_id integer, p_sale_id integer, p_amount numeric, p_created_by integer) RETURNS bigint
    LANGUAGE plpgsql
    AS $$
DECLARE
    v_transaction_id bigint;
    v_transaction_type_id integer;
    v_current_balance numeric;
BEGIN
    -- Récupérer le solde actuel
    SELECT current_balance INTO v_current_balance
    FROM accounts WHERE id = p_account_id;
    
    -- Récupérer le type de transaction
    SELECT id INTO v_transaction_type_id 
    FROM transaction_types WHERE code = 'SALE';
    
    -- Créer la transaction
    INSERT INTO account_transactions (
        account_id, transaction_type_id, amount,
        balance_before, balance_after,
        sale_id, description, created_by
    ) VALUES (
        p_account_id, v_transaction_type_id, p_amount,
        v_current_balance, v_current_balance + p_amount,
        p_sale_id, 'Encaissement vente', p_created_by
    ) RETURNING id INTO v_transaction_id;
    
    -- Mettre à jour le solde du compte
    UPDATE accounts 
    SET current_balance = current_balance + p_amount,
        updated_at = CURRENT_TIMESTAMP
    WHERE id = p_account_id;
    
    RETURN v_transaction_id;
END;
$$;


ALTER FUNCTION public.record_sale_transaction(p_account_id integer, p_sale_id integer, p_amount numeric, p_created_by integer) OWNER TO express_sale_user;

--
-- Name: record_supplier_payment(integer, integer, integer, numeric, character varying, text, integer); Type: FUNCTION; Schema: public; Owner: express_sale_user
--

CREATE FUNCTION public.record_supplier_payment(p_account_id integer, p_supplier_id integer, p_stock_receipt_id integer, p_amount numeric, p_reference_number character varying, p_notes text, p_created_by integer) RETURNS bigint
    LANGUAGE plpgsql
    AS $$
DECLARE
    v_transaction_id bigint;
    v_transaction_type_id integer;
    v_current_balance numeric;
BEGIN
    SELECT current_balance INTO v_current_balance
    FROM accounts WHERE id = p_account_id;
    
    IF v_current_balance < p_amount THEN
        RAISE EXCEPTION 'Solde insuffisant dans le compte';
    END IF;
    
    SELECT id INTO v_transaction_type_id 
    FROM transaction_types WHERE code = 'SUPPLIER_PAYMENT';
    
    INSERT INTO account_transactions (
        account_id, transaction_type_id, amount,
        balance_before, balance_after,
        supplier_id, stock_receipt_id, reference_number,
        notes, created_by
    ) VALUES (
        p_account_id, v_transaction_type_id, p_amount,
        v_current_balance, v_current_balance - p_amount,
        p_supplier_id, p_stock_receipt_id, p_reference_number,
        p_notes, p_created_by
    ) RETURNING id INTO v_transaction_id;
    
    UPDATE accounts 
    SET current_balance = current_balance - p_amount,
        updated_at = CURRENT_TIMESTAMP
    WHERE id = p_account_id;
    
    RETURN v_transaction_id;
END;
$$;


ALTER FUNCTION public.record_supplier_payment(p_account_id integer, p_supplier_id integer, p_stock_receipt_id integer, p_amount numeric, p_reference_number character varying, p_notes text, p_created_by integer) OWNER TO express_sale_user;

--
-- Name: release_credit_stock(); Type: FUNCTION; Schema: public; Owner: express_sale_user
--

CREATE FUNCTION public.release_credit_stock() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
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
$$;


ALTER FUNCTION public.release_credit_stock() OWNER TO express_sale_user;

--
-- Name: update_account_balance(); Type: FUNCTION; Schema: public; Owner: express_sale_user
--

CREATE FUNCTION public.update_account_balance() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
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
$$;


ALTER FUNCTION public.update_account_balance() OWNER TO express_sale_user;

--
-- Name: update_supplier_quality_rating(); Type: FUNCTION; Schema: public; Owner: express_sale_user
--

CREATE FUNCTION public.update_supplier_quality_rating() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
DECLARE
    supplier_id_var integer;
    item_value numeric(15,2);
BEGIN
    -- Récupérer le supplier_id et la valeur de l'article
    SELECT sr.supplier_id, 
           (sri.quantity_received * sri.unit_cost_ariary)
    INTO supplier_id_var, item_value
    FROM stock_receipt_items sri
    JOIN stock_receipts sr ON sri.stock_receipt_id = sr.id
    WHERE sri.id = NEW.stock_receipt_item_id;
    
    -- Mettre à jour les stats qualité PONDÉRÉES par valeur
    UPDATE suppliers
    SET 
        quality_rating_sum = quality_rating_sum + NEW.quality_rating,
        quality_rating_count = quality_rating_count + 1,
        -- Pondération: note × valeur de l'article
        weighted_quality_sum = weighted_quality_sum + (NEW.quality_rating * item_value),
        total_weighted_value = total_weighted_value + item_value,
        reliability_score = calculate_supplier_score(supplier_id_var)
    WHERE id = supplier_id_var;
    
    RETURN NEW;
END;
$$;


ALTER FUNCTION public.update_supplier_quality_rating() OWNER TO express_sale_user;

--
-- Name: update_supplier_stats_on_receipt(); Type: FUNCTION; Schema: public; Owner: express_sale_user
--

CREATE FUNCTION public.update_supplier_stats_on_receipt() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
    IF NEW.status = 'validated' AND OLD.status != 'validated' THEN
        -- Mettre à jour les compteurs du fournisseur (PONDÉRÉS PAR VALEUR)
        UPDATE suppliers s
        SET 
            total_orders = total_orders + 1,
            total_value_ordered = total_value_ordered + (
                SELECT COALESCE(SUM(quantity_ordered * unit_cost_ariary), 0)
                FROM stock_receipt_items
                WHERE stock_receipt_id = NEW.id
            ),
            total_value_received = total_value_received + (
                SELECT COALESCE(SUM(quantity_received * unit_cost_ariary), 0)
                FROM stock_receipt_items
                WHERE stock_receipt_id = NEW.id
            )
        WHERE s.id = NEW.supplier_id;
        
        -- Recalculer le score
        UPDATE suppliers
        SET reliability_score = calculate_supplier_score(NEW.supplier_id)
        WHERE id = NEW.supplier_id;
        
        -- Mettre à jour les stats du transitaire si présent (PONDÉRÉS PAR VALEUR)
        IF NEW.freight_forwarder_id IS NOT NULL THEN
            UPDATE freight_forwarders f
            SET 
                total_shipments = total_shipments + 1,
                total_value_shipped = total_value_shipped + (
                    SELECT COALESCE(SUM(quantity_ordered * unit_cost_ariary), 0)
                    FROM stock_receipt_items
                    WHERE stock_receipt_id = NEW.id
                ),
                total_value_delivered = total_value_delivered + (
                    SELECT COALESCE(SUM(quantity_received * unit_cost_ariary), 0)
                    FROM stock_receipt_items
                    WHERE stock_receipt_id = NEW.id
                )
            WHERE f.id = NEW.freight_forwarder_id;
            
            -- Recalculer le score
            UPDATE freight_forwarders
            SET service_score = calculate_freight_forwarder_score(NEW.freight_forwarder_id)
            WHERE id = NEW.freight_forwarder_id;
        END IF;
    END IF;
    
    RETURN NEW;
END;
$$;


ALTER FUNCTION public.update_supplier_stats_on_receipt() OWNER TO express_sale_user;

--
-- Name: update_updated_at_column(); Type: FUNCTION; Schema: public; Owner: express_sale_user
--

CREATE FUNCTION public.update_updated_at_column() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$;


ALTER FUNCTION public.update_updated_at_column() OWNER TO express_sale_user;

SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: account_transactions; Type: TABLE; Schema: public; Owner: express_sale_user
--

CREATE TABLE public.account_transactions (
    id bigint NOT NULL,
    account_id integer NOT NULL,
    transaction_type_id integer NOT NULL,
    amount numeric(15,2) NOT NULL,
    balance_before numeric(15,2) NOT NULL,
    balance_after numeric(15,2) NOT NULL,
    transaction_date timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    related_account_id integer,
    related_transaction_id bigint,
    supplier_id integer,
    freight_forwarder_id integer,
    stock_receipt_id integer,
    expense_category_id integer,
    recipient_name character varying(255),
    sale_id integer,
    reference_number character varying(255),
    description text,
    notes text,
    created_by integer,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    reversed_transaction_id bigint,
    CONSTRAINT account_transactions_amount_check CHECK ((amount <> (0)::numeric))
);


ALTER TABLE public.account_transactions OWNER TO express_sale_user;

--
-- Name: TABLE account_transactions; Type: COMMENT; Schema: public; Owner: express_sale_user
--

COMMENT ON TABLE public.account_transactions IS 'Historique complet de toutes les transactions (entrées, sorties, transferts)';


--
-- Name: COLUMN account_transactions.balance_before; Type: COMMENT; Schema: public; Owner: express_sale_user
--

COMMENT ON COLUMN public.account_transactions.balance_before IS 'Solde du compte AVANT la transaction';


--
-- Name: COLUMN account_transactions.balance_after; Type: COMMENT; Schema: public; Owner: express_sale_user
--

COMMENT ON COLUMN public.account_transactions.balance_after IS 'Solde du compte APRÈS la transaction';


--
-- Name: COLUMN account_transactions.related_account_id; Type: COMMENT; Schema: public; Owner: express_sale_user
--

COMMENT ON COLUMN public.account_transactions.related_account_id IS 'Compte de destination pour les transferts';


--
-- Name: COLUMN account_transactions.related_transaction_id; Type: COMMENT; Schema: public; Owner: express_sale_user
--

COMMENT ON COLUMN public.account_transactions.related_transaction_id IS 'Transaction liée (pour les transferts bidirectionnels)';


--
-- Name: COLUMN account_transactions.recipient_name; Type: COMMENT; Schema: public; Owner: express_sale_user
--

COMMENT ON COLUMN public.account_transactions.recipient_name IS 'Nom du bénéficiaire pour les dépenses opérationnelles';


--
-- Name: account_transactions_id_seq; Type: SEQUENCE; Schema: public; Owner: express_sale_user
--

CREATE SEQUENCE public.account_transactions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.account_transactions_id_seq OWNER TO express_sale_user;

--
-- Name: account_transactions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: express_sale_user
--

ALTER SEQUENCE public.account_transactions_id_seq OWNED BY public.account_transactions.id;


--
-- Name: account_types; Type: TABLE; Schema: public; Owner: express_sale_user
--

CREATE TABLE public.account_types (
    id integer NOT NULL,
    code character varying(50) NOT NULL,
    name character varying(100) NOT NULL,
    display_name character varying(255) NOT NULL,
    description text,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.account_types OWNER TO express_sale_user;

--
-- Name: TABLE account_types; Type: COMMENT; Schema: public; Owner: express_sale_user
--

COMMENT ON TABLE public.account_types IS 'Types de comptes monétaires disponibles';


--
-- Name: account_types_id_seq; Type: SEQUENCE; Schema: public; Owner: express_sale_user
--

CREATE SEQUENCE public.account_types_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.account_types_id_seq OWNER TO express_sale_user;

--
-- Name: account_types_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: express_sale_user
--

ALTER SEQUENCE public.account_types_id_seq OWNED BY public.account_types.id;


--
-- Name: accounts; Type: TABLE; Schema: public; Owner: express_sale_user
--

CREATE TABLE public.accounts (
    id integer NOT NULL,
    account_type_id integer NOT NULL,
    name character varying(255) NOT NULL,
    account_number character varying(100),
    initial_balance numeric(15,2) DEFAULT 0 NOT NULL,
    current_balance numeric(15,2) DEFAULT 0 NOT NULL,
    notes text,
    is_active boolean DEFAULT true,
    created_by integer,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT accounts_current_balance_check CHECK ((current_balance >= (0)::numeric)),
    CONSTRAINT accounts_initial_balance_check CHECK ((initial_balance >= (0)::numeric))
);


ALTER TABLE public.accounts OWNER TO express_sale_user;

--
-- Name: TABLE accounts; Type: COMMENT; Schema: public; Owner: express_sale_user
--

COMMENT ON TABLE public.accounts IS 'Comptes monétaires (Cash, Mobile Money, Banque)';


--
-- Name: COLUMN accounts.initial_balance; Type: COMMENT; Schema: public; Owner: express_sale_user
--

COMMENT ON COLUMN public.accounts.initial_balance IS 'Solde initial lors de la création du compte';


--
-- Name: COLUMN accounts.current_balance; Type: COMMENT; Schema: public; Owner: express_sale_user
--

COMMENT ON COLUMN public.accounts.current_balance IS 'Solde actuel du compte';


--
-- Name: accounts_id_seq; Type: SEQUENCE; Schema: public; Owner: express_sale_user
--

CREATE SEQUENCE public.accounts_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.accounts_id_seq OWNER TO express_sale_user;

--
-- Name: accounts_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: express_sale_user
--

ALTER SEQUENCE public.accounts_id_seq OWNED BY public.accounts.id;


--
-- Name: attribute_types; Type: TABLE; Schema: public; Owner: express_sale_user
--

CREATE TABLE public.attribute_types (
    id integer NOT NULL,
    name character varying(100) NOT NULL,
    display_name character varying(255) NOT NULL,
    input_type character varying(50) DEFAULT 'text'::character varying,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT attribute_types_input_type_check CHECK (((input_type)::text = ANY (ARRAY[('text'::character varying)::text, ('number'::character varying)::text, ('select'::character varying)::text, ('color'::character varying)::text])))
);


ALTER TABLE public.attribute_types OWNER TO express_sale_user;

--
-- Name: attribute_types_id_seq; Type: SEQUENCE; Schema: public; Owner: express_sale_user
--

CREATE SEQUENCE public.attribute_types_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.attribute_types_id_seq OWNER TO express_sale_user;

--
-- Name: attribute_types_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: express_sale_user
--

ALTER SEQUENCE public.attribute_types_id_seq OWNED BY public.attribute_types.id;


--
-- Name: attribute_values; Type: TABLE; Schema: public; Owner: express_sale_user
--

CREATE TABLE public.attribute_values (
    id integer NOT NULL,
    attribute_type_id integer NOT NULL,
    value character varying(100) NOT NULL,
    sort_order integer DEFAULT 0,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.attribute_values OWNER TO express_sale_user;

--
-- Name: attribute_values_id_seq; Type: SEQUENCE; Schema: public; Owner: express_sale_user
--

CREATE SEQUENCE public.attribute_values_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.attribute_values_id_seq OWNER TO express_sale_user;

--
-- Name: attribute_values_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: express_sale_user
--

ALTER SEQUENCE public.attribute_values_id_seq OWNED BY public.attribute_values.id;


--
-- Name: audit_logs; Type: TABLE; Schema: public; Owner: express_sale_user
--

CREATE TABLE public.audit_logs (
    id integer NOT NULL,
    user_id integer,
    action character varying(100) NOT NULL,
    table_name character varying(100) NOT NULL,
    record_id integer,
    old_values jsonb,
    new_values jsonb,
    ip_address character varying(45),
    user_agent text,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.audit_logs OWNER TO express_sale_user;

--
-- Name: audit_logs_id_seq; Type: SEQUENCE; Schema: public; Owner: express_sale_user
--

CREATE SEQUENCE public.audit_logs_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.audit_logs_id_seq OWNER TO express_sale_user;

--
-- Name: audit_logs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: express_sale_user
--

ALTER SEQUENCE public.audit_logs_id_seq OWNED BY public.audit_logs.id;


--
-- Name: cache; Type: TABLE; Schema: public; Owner: express_sale_user
--

CREATE TABLE public.cache (
    key character varying(255) NOT NULL,
    value text NOT NULL,
    expiration integer NOT NULL
);


ALTER TABLE public.cache OWNER TO express_sale_user;

--
-- Name: cache_locks; Type: TABLE; Schema: public; Owner: express_sale_user
--

CREATE TABLE public.cache_locks (
    key character varying(255) NOT NULL,
    owner character varying(255) NOT NULL,
    expiration integer NOT NULL
);


ALTER TABLE public.cache_locks OWNER TO express_sale_user;

--
-- Name: categories; Type: TABLE; Schema: public; Owner: express_sale_user
--

CREATE TABLE public.categories (
    id integer NOT NULL,
    name character varying(255) NOT NULL,
    description text,
    parent_id integer,
    image_url character varying(500),
    sort_order integer DEFAULT 0,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.categories OWNER TO express_sale_user;

--
-- Name: categories_id_seq; Type: SEQUENCE; Schema: public; Owner: express_sale_user
--

CREATE SEQUENCE public.categories_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.categories_id_seq OWNER TO express_sale_user;

--
-- Name: categories_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: express_sale_user
--

ALTER SEQUENCE public.categories_id_seq OWNED BY public.categories.id;


--
-- Name: coordinates; Type: TABLE; Schema: public; Owner: express_sale_user
--

CREATE TABLE public.coordinates (
    id bigint NOT NULL,
    country character varying(100) NOT NULL,
    city character varying(100) NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.coordinates OWNER TO express_sale_user;

--
-- Name: coordinates_id_seq; Type: SEQUENCE; Schema: public; Owner: express_sale_user
--

CREATE SEQUENCE public.coordinates_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.coordinates_id_seq OWNER TO express_sale_user;

--
-- Name: coordinates_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: express_sale_user
--

ALTER SEQUENCE public.coordinates_id_seq OWNED BY public.coordinates.id;


--
-- Name: credit_installments; Type: TABLE; Schema: public; Owner: express_sale_user
--

CREATE TABLE public.credit_installments (
    id integer NOT NULL,
    credit_id integer NOT NULL,
    installment_number integer NOT NULL,
    due_date date NOT NULL,
    amount_due numeric(12,2) NOT NULL,
    amount_paid numeric(12,2) DEFAULT 0,
    status character varying(50) DEFAULT 'pending'::character varying,
    paid_date date,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT chk_amount_paid CHECK ((amount_paid <= amount_due)),
    CONSTRAINT credit_installments_status_check CHECK (((status)::text = ANY (ARRAY[('pending'::character varying)::text, ('partial'::character varying)::text, ('paid'::character varying)::text, ('overdue'::character varying)::text])))
);


ALTER TABLE public.credit_installments OWNER TO express_sale_user;

--
-- Name: credit_installments_id_seq; Type: SEQUENCE; Schema: public; Owner: express_sale_user
--

CREATE SEQUENCE public.credit_installments_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.credit_installments_id_seq OWNER TO express_sale_user;

--
-- Name: credit_installments_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: express_sale_user
--

ALTER SEQUENCE public.credit_installments_id_seq OWNED BY public.credit_installments.id;


--
-- Name: credits; Type: TABLE; Schema: public; Owner: express_sale_user
--

CREATE TABLE public.credits (
    id integer NOT NULL,
    sale_id integer NOT NULL,
    customer_id integer NOT NULL,
    total_amount numeric(12,2) NOT NULL,
    amount_paid numeric(12,2) DEFAULT 0,
    amount_due numeric(12,2) NOT NULL,
    credit_date date DEFAULT CURRENT_DATE NOT NULL,
    due_date date,
    status character varying(50) DEFAULT 'active'::character varying,
    notes text,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    last_payment_date timestamp without time zone,
    CONSTRAINT chk_amount_paid CHECK ((amount_paid <= total_amount)),
    CONSTRAINT credits_amount_due_check CHECK ((amount_due >= (0)::numeric)),
    CONSTRAINT credits_amount_paid_check CHECK ((amount_paid >= (0)::numeric)),
    CONSTRAINT credits_status_check CHECK (((status)::text = ANY (ARRAY[('active'::character varying)::text, ('partial_paid'::character varying)::text, ('completed'::character varying)::text, ('overdue'::character varying)::text, ('defaulted'::character varying)::text, ('recovered'::character varying)::text])))
);


ALTER TABLE public.credits OWNER TO express_sale_user;

--
-- Name: credits_id_seq; Type: SEQUENCE; Schema: public; Owner: express_sale_user
--

CREATE SEQUENCE public.credits_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.credits_id_seq OWNER TO express_sale_user;

--
-- Name: credits_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: express_sale_user
--

ALTER SEQUENCE public.credits_id_seq OWNED BY public.credits.id;


--
-- Name: currency_rates; Type: TABLE; Schema: public; Owner: express_sale_user
--

CREATE TABLE public.currency_rates (
    id integer NOT NULL,
    euro_rate numeric(10,4) NOT NULL,
    yuan_rate numeric(10,4) NOT NULL,
    dollar_rate numeric(10,4) NOT NULL,
    dirham_rate numeric(10,4) NOT NULL,
    effective_date date DEFAULT CURRENT_DATE NOT NULL,
    notes text,
    created_by integer,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    baht_rate numeric(10,4) NOT NULL,
    is_active boolean DEFAULT false NOT NULL,
    CONSTRAINT currency_rates_dirham_rate_check CHECK ((dirham_rate > (0)::numeric)),
    CONSTRAINT currency_rates_dollar_rate_check CHECK ((dollar_rate > (0)::numeric)),
    CONSTRAINT currency_rates_euro_rate_check CHECK ((euro_rate > (0)::numeric)),
    CONSTRAINT currency_rates_yen_rate_check CHECK ((yuan_rate > (0)::numeric))
);


ALTER TABLE public.currency_rates OWNER TO express_sale_user;

--
-- Name: TABLE currency_rates; Type: COMMENT; Schema: public; Owner: express_sale_user
--

COMMENT ON TABLE public.currency_rates IS 'Taux de change: valeur de 100 Ariary vers chaque devise (Euro, Yen, Dollar, Dirham)';


--
-- Name: currency_rates_id_seq; Type: SEQUENCE; Schema: public; Owner: express_sale_user
--

CREATE SEQUENCE public.currency_rates_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.currency_rates_id_seq OWNER TO express_sale_user;

--
-- Name: currency_rates_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: express_sale_user
--

ALTER SEQUENCE public.currency_rates_id_seq OWNED BY public.currency_rates.id;


--
-- Name: customers; Type: TABLE; Schema: public; Owner: express_sale_user
--

CREATE TABLE public.customers (
    id integer NOT NULL,
    name character varying(255) NOT NULL,
    phone character varying(50),
    address text,
    reliability_score numeric(3,2) DEFAULT 5.00,
    loyalty_points integer DEFAULT 0,
    credit_limit numeric(12,2) DEFAULT 0,
    notes text,
    is_active boolean DEFAULT true,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    customer_number character varying(255),
    CONSTRAINT customers_reliability_score_check CHECK (((reliability_score >= (0)::numeric) AND (reliability_score <= (10)::numeric)))
);


ALTER TABLE public.customers OWNER TO express_sale_user;

--
-- Name: customers_id_seq; Type: SEQUENCE; Schema: public; Owner: express_sale_user
--

CREATE SEQUENCE public.customers_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.customers_id_seq OWNER TO express_sale_user;

--
-- Name: customers_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: express_sale_user
--

ALTER SEQUENCE public.customers_id_seq OWNED BY public.customers.id;


--
-- Name: expense_categories; Type: TABLE; Schema: public; Owner: express_sale_user
--

CREATE TABLE public.expense_categories (
    id integer NOT NULL,
    name character varying(255) NOT NULL,
    description text,
    icon character varying(50),
    is_active boolean DEFAULT true,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.expense_categories OWNER TO express_sale_user;

--
-- Name: TABLE expense_categories; Type: COMMENT; Schema: public; Owner: express_sale_user
--

COMMENT ON TABLE public.expense_categories IS 'Catégories de dépenses opérationnelles';


--
-- Name: expense_categories_id_seq; Type: SEQUENCE; Schema: public; Owner: express_sale_user
--

CREATE SEQUENCE public.expense_categories_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.expense_categories_id_seq OWNER TO express_sale_user;

--
-- Name: expense_categories_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: express_sale_user
--

ALTER SEQUENCE public.expense_categories_id_seq OWNED BY public.expense_categories.id;


--
-- Name: failed_jobs; Type: TABLE; Schema: public; Owner: express_sale_user
--

CREATE TABLE public.failed_jobs (
    id bigint NOT NULL,
    uuid character varying(255) NOT NULL,
    connection text NOT NULL,
    queue text NOT NULL,
    payload text NOT NULL,
    exception text NOT NULL,
    failed_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


ALTER TABLE public.failed_jobs OWNER TO express_sale_user;

--
-- Name: failed_jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: express_sale_user
--

CREATE SEQUENCE public.failed_jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.failed_jobs_id_seq OWNER TO express_sale_user;

--
-- Name: failed_jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: express_sale_user
--

ALTER SEQUENCE public.failed_jobs_id_seq OWNED BY public.failed_jobs.id;


--
-- Name: freight_forwarders; Type: TABLE; Schema: public; Owner: express_sale_user
--

CREATE TABLE public.freight_forwarders (
    id integer NOT NULL,
    name character varying(255) NOT NULL,
    logo_url character varying(500),
    contact character varying(255),
    service_score numeric(4,2) DEFAULT 5.00,
    notes text,
    is_active boolean DEFAULT true,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    coordinate_id bigint,
    total_shipments integer DEFAULT 0,
    total_items_shipped integer DEFAULT 0,
    total_items_delivered integer DEFAULT 0,
    service_rating_sum numeric(10,2) DEFAULT 0,
    service_rating_count integer DEFAULT 0,
    total_value_shipped numeric(15,2) DEFAULT 0,
    total_value_delivered numeric(15,2) DEFAULT 0,
    weighted_service_sum numeric(15,2) DEFAULT 0,
    total_weighted_shipment_value numeric(15,2) DEFAULT 0,
    CONSTRAINT freight_forwarders_service_score_check CHECK (((service_score >= (0)::numeric) AND (service_score <= (10)::numeric)))
);


ALTER TABLE public.freight_forwarders OWNER TO express_sale_user;

--
-- Name: TABLE freight_forwarders; Type: COMMENT; Schema: public; Owner: express_sale_user
--

COMMENT ON TABLE public.freight_forwarders IS 'Transitaires pour transport depuis Chine';


--
-- Name: COLUMN freight_forwarders.service_score; Type: COMMENT; Schema: public; Owner: express_sale_user
--

COMMENT ON COLUMN public.freight_forwarders.service_score IS 'Score global sur 10 (pondéré par valeur)';


--
-- Name: COLUMN freight_forwarders.total_shipments; Type: COMMENT; Schema: public; Owner: express_sale_user
--

COMMENT ON COLUMN public.freight_forwarders.total_shipments IS 'Nombre total de transports effectués';


--
-- Name: COLUMN freight_forwarders.total_items_shipped; Type: COMMENT; Schema: public; Owner: express_sale_user
--

COMMENT ON COLUMN public.freight_forwarders.total_items_shipped IS 'Quantité totale expédiée';


--
-- Name: COLUMN freight_forwarders.total_items_delivered; Type: COMMENT; Schema: public; Owner: express_sale_user
--

COMMENT ON COLUMN public.freight_forwarders.total_items_delivered IS 'Quantité totale livrée';


--
-- Name: COLUMN freight_forwarders.service_rating_sum; Type: COMMENT; Schema: public; Owner: express_sale_user
--

COMMENT ON COLUMN public.freight_forwarders.service_rating_sum IS 'Somme simple des notes de service';


--
-- Name: COLUMN freight_forwarders.service_rating_count; Type: COMMENT; Schema: public; Owner: express_sale_user
--

COMMENT ON COLUMN public.freight_forwarders.service_rating_count IS 'Nombre de notations service reçues';


--
-- Name: COLUMN freight_forwarders.total_value_shipped; Type: COMMENT; Schema: public; Owner: express_sale_user
--

COMMENT ON COLUMN public.freight_forwarders.total_value_shipped IS 'Valeur totale expédiée en Ariary';


--
-- Name: COLUMN freight_forwarders.total_value_delivered; Type: COMMENT; Schema: public; Owner: express_sale_user
--

COMMENT ON COLUMN public.freight_forwarders.total_value_delivered IS 'Valeur totale livrée en Ariary';


--
-- Name: COLUMN freight_forwarders.weighted_service_sum; Type: COMMENT; Schema: public; Owner: express_sale_user
--

COMMENT ON COLUMN public.freight_forwarders.weighted_service_sum IS 'Somme pondérée: note × valeur expédition';


--
-- Name: COLUMN freight_forwarders.total_weighted_shipment_value; Type: COMMENT; Schema: public; Owner: express_sale_user
--

COMMENT ON COLUMN public.freight_forwarders.total_weighted_shipment_value IS 'Somme totale des valeurs utilisées pour pondération';


--
-- Name: freight_forwarders_id_seq; Type: SEQUENCE; Schema: public; Owner: express_sale_user
--

CREATE SEQUENCE public.freight_forwarders_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.freight_forwarders_id_seq OWNER TO express_sale_user;

--
-- Name: freight_forwarders_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: express_sale_user
--

ALTER SEQUENCE public.freight_forwarders_id_seq OWNED BY public.freight_forwarders.id;


--
-- Name: installment_transactions; Type: TABLE; Schema: public; Owner: express_sale_user
--

CREATE TABLE public.installment_transactions (
    id bigint NOT NULL,
    installment_id bigint NOT NULL,
    transaction_id bigint NOT NULL,
    amount numeric(12,2) NOT NULL,
    payment_date timestamp(0) without time zone NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.installment_transactions OWNER TO express_sale_user;

--
-- Name: installment_transactions_id_seq; Type: SEQUENCE; Schema: public; Owner: express_sale_user
--

CREATE SEQUENCE public.installment_transactions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.installment_transactions_id_seq OWNER TO express_sale_user;

--
-- Name: installment_transactions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: express_sale_user
--

ALTER SEQUENCE public.installment_transactions_id_seq OWNED BY public.installment_transactions.id;


--
-- Name: job_batches; Type: TABLE; Schema: public; Owner: express_sale_user
--

CREATE TABLE public.job_batches (
    id character varying(255) NOT NULL,
    name character varying(255) NOT NULL,
    total_jobs integer NOT NULL,
    pending_jobs integer NOT NULL,
    failed_jobs integer NOT NULL,
    failed_job_ids text NOT NULL,
    options text,
    cancelled_at integer,
    created_at integer NOT NULL,
    finished_at integer
);


ALTER TABLE public.job_batches OWNER TO express_sale_user;

--
-- Name: jobs; Type: TABLE; Schema: public; Owner: express_sale_user
--

CREATE TABLE public.jobs (
    id bigint NOT NULL,
    queue character varying(255) NOT NULL,
    payload text NOT NULL,
    attempts smallint NOT NULL,
    reserved_at integer,
    available_at integer NOT NULL,
    created_at integer NOT NULL
);


ALTER TABLE public.jobs OWNER TO express_sale_user;

--
-- Name: jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: express_sale_user
--

CREATE SEQUENCE public.jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.jobs_id_seq OWNER TO express_sale_user;

--
-- Name: jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: express_sale_user
--

ALTER SEQUENCE public.jobs_id_seq OWNED BY public.jobs.id;


--
-- Name: locations; Type: TABLE; Schema: public; Owner: express_sale_user
--

CREATE TABLE public.locations (
    id integer NOT NULL,
    name character varying(255) NOT NULL,
    code character varying(50),
    warehouse character varying(100),
    aisle character varying(50),
    shelf character varying(50),
    bin character varying(50),
    description text,
    capacity integer,
    is_active boolean DEFAULT true,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.locations OWNER TO express_sale_user;

--
-- Name: locations_id_seq; Type: SEQUENCE; Schema: public; Owner: express_sale_user
--

CREATE SEQUENCE public.locations_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.locations_id_seq OWNER TO express_sale_user;

--
-- Name: locations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: express_sale_user
--

ALTER SEQUENCE public.locations_id_seq OWNED BY public.locations.id;


--
-- Name: migrations; Type: TABLE; Schema: public; Owner: express_sale_user
--

CREATE TABLE public.migrations (
    id integer NOT NULL,
    migration character varying(255) NOT NULL,
    batch integer NOT NULL
);


ALTER TABLE public.migrations OWNER TO express_sale_user;

--
-- Name: migrations_id_seq; Type: SEQUENCE; Schema: public; Owner: express_sale_user
--

CREATE SEQUENCE public.migrations_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.migrations_id_seq OWNER TO express_sale_user;

--
-- Name: migrations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: express_sale_user
--

ALTER SEQUENCE public.migrations_id_seq OWNED BY public.migrations.id;


--
-- Name: notifications; Type: TABLE; Schema: public; Owner: express_sale_user
--

CREATE TABLE public.notifications (
    id integer NOT NULL,
    user_id integer,
    type character varying(100) NOT NULL,
    title character varying(255) NOT NULL,
    message text NOT NULL,
    reference_type character varying(100),
    reference_id integer,
    is_read boolean DEFAULT false,
    read_at timestamp without time zone,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.notifications OWNER TO express_sale_user;

--
-- Name: notifications_id_seq; Type: SEQUENCE; Schema: public; Owner: express_sale_user
--

CREATE SEQUENCE public.notifications_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.notifications_id_seq OWNER TO express_sale_user;

--
-- Name: notifications_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: express_sale_user
--

ALTER SEQUENCE public.notifications_id_seq OWNED BY public.notifications.id;


--
-- Name: password_reset_tokens; Type: TABLE; Schema: public; Owner: express_sale_user
--

CREATE TABLE public.password_reset_tokens (
    username character varying(255) NOT NULL,
    token character varying(255) NOT NULL,
    created_at timestamp(0) without time zone
);


ALTER TABLE public.password_reset_tokens OWNER TO express_sale_user;

--
-- Name: personal_access_tokens; Type: TABLE; Schema: public; Owner: express_sale_user
--

CREATE TABLE public.personal_access_tokens (
    id bigint NOT NULL,
    tokenable_type character varying(255) NOT NULL,
    tokenable_id bigint NOT NULL,
    name text NOT NULL,
    token character varying(64) NOT NULL,
    abilities text,
    last_used_at timestamp(0) without time zone,
    expires_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.personal_access_tokens OWNER TO express_sale_user;

--
-- Name: personal_access_tokens_id_seq; Type: SEQUENCE; Schema: public; Owner: express_sale_user
--

CREATE SEQUENCE public.personal_access_tokens_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.personal_access_tokens_id_seq OWNER TO express_sale_user;

--
-- Name: personal_access_tokens_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: express_sale_user
--

ALTER SEQUENCE public.personal_access_tokens_id_seq OWNED BY public.personal_access_tokens.id;


--
-- Name: product_attributes; Type: TABLE; Schema: public; Owner: express_sale_user
--

CREATE TABLE public.product_attributes (
    id integer NOT NULL,
    product_id integer NOT NULL,
    attribute_type_id integer NOT NULL,
    is_required boolean DEFAULT true,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.product_attributes OWNER TO express_sale_user;

--
-- Name: product_attributes_id_seq; Type: SEQUENCE; Schema: public; Owner: express_sale_user
--

CREATE SEQUENCE public.product_attributes_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.product_attributes_id_seq OWNER TO express_sale_user;

--
-- Name: product_attributes_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: express_sale_user
--

ALTER SEQUENCE public.product_attributes_id_seq OWNED BY public.product_attributes.id;


--
-- Name: product_variant_locations; Type: TABLE; Schema: public; Owner: express_sale_user
--

CREATE TABLE public.product_variant_locations (
    id bigint NOT NULL,
    variant_id integer NOT NULL,
    location_id integer NOT NULL,
    quantity integer DEFAULT 0 NOT NULL,
    notes text,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.product_variant_locations OWNER TO express_sale_user;

--
-- Name: product_variant_locations_id_seq; Type: SEQUENCE; Schema: public; Owner: express_sale_user
--

CREATE SEQUENCE public.product_variant_locations_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.product_variant_locations_id_seq OWNER TO express_sale_user;

--
-- Name: product_variant_locations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: express_sale_user
--

ALTER SEQUENCE public.product_variant_locations_id_seq OWNED BY public.product_variant_locations.id;


--
-- Name: product_variants; Type: TABLE; Schema: public; Owner: express_sale_user
--

CREATE TABLE public.product_variants (
    id integer NOT NULL,
    product_id integer NOT NULL,
    sku character varying(100),
    price_adjustment numeric(12,2) DEFAULT 0,
    stock_quantity integer DEFAULT 0 NOT NULL,
    reserved_quantity integer DEFAULT 0 NOT NULL,
    low_stock_threshold integer DEFAULT 5,
    is_active boolean DEFAULT true,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    image_path text,
    credit_quantity integer DEFAULT 0 NOT NULL,
    CONSTRAINT product_variants_reserved_quantity_check CHECK ((reserved_quantity >= 0)),
    CONSTRAINT product_variants_stock_quantity_check CHECK ((stock_quantity >= 0))
);


ALTER TABLE public.product_variants OWNER TO express_sale_user;

--
-- Name: product_variants_id_seq; Type: SEQUENCE; Schema: public; Owner: express_sale_user
--

CREATE SEQUENCE public.product_variants_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.product_variants_id_seq OWNER TO express_sale_user;

--
-- Name: product_variants_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: express_sale_user
--

ALTER SEQUENCE public.product_variants_id_seq OWNED BY public.product_variants.id;


--
-- Name: products; Type: TABLE; Schema: public; Owner: express_sale_user
--

CREATE TABLE public.products (
    id integer NOT NULL,
    name character varying(255) NOT NULL,
    description text,
    category_id integer NOT NULL,
    subcategory_id integer,
    base_price numeric(12,2) NOT NULL,
    is_active boolean DEFAULT true,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    image_url text,
    CONSTRAINT products_base_price_check CHECK ((base_price >= (0)::numeric))
);


ALTER TABLE public.products OWNER TO express_sale_user;

--
-- Name: products_id_seq; Type: SEQUENCE; Schema: public; Owner: express_sale_user
--

CREATE SEQUENCE public.products_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.products_id_seq OWNER TO express_sale_user;

--
-- Name: products_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: express_sale_user
--

ALTER SEQUENCE public.products_id_seq OWNED BY public.products.id;


--
-- Name: reservation_deposits; Type: TABLE; Schema: public; Owner: express_sale_user
--

CREATE TABLE public.reservation_deposits (
    id bigint NOT NULL,
    reservation_id bigint NOT NULL,
    transaction_id bigint NOT NULL,
    amount numeric(12,2) NOT NULL,
    payment_date timestamp(0) without time zone NOT NULL,
    notes character varying(255),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.reservation_deposits OWNER TO express_sale_user;

--
-- Name: reservation_deposits_id_seq; Type: SEQUENCE; Schema: public; Owner: express_sale_user
--

CREATE SEQUENCE public.reservation_deposits_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.reservation_deposits_id_seq OWNER TO express_sale_user;

--
-- Name: reservation_deposits_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: express_sale_user
--

ALTER SEQUENCE public.reservation_deposits_id_seq OWNED BY public.reservation_deposits.id;


--
-- Name: reservations; Type: TABLE; Schema: public; Owner: express_sale_user
--

CREATE TABLE public.reservations (
    id integer NOT NULL,
    sale_id integer NOT NULL,
    customer_id integer NOT NULL,
    reservation_date timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    expiry_date timestamp without time zone NOT NULL,
    total_amount numeric(12,2) NOT NULL,
    deposit_amount numeric(12,2) DEFAULT 0,
    remaining_amount numeric(12,2) NOT NULL,
    status character varying(50) DEFAULT 'pending'::character varying,
    cancellation_reason text,
    completed_at timestamp without time zone,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT reservations_status_check CHECK (((status)::text = ANY (ARRAY[('pending'::character varying)::text, ('confirmed'::character varying)::text, ('partial_paid'::character varying)::text, ('completed'::character varying)::text, ('expired'::character varying)::text, ('cancelled'::character varying)::text])))
);


ALTER TABLE public.reservations OWNER TO express_sale_user;

--
-- Name: reservations_id_seq; Type: SEQUENCE; Schema: public; Owner: express_sale_user
--

CREATE SEQUENCE public.reservations_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.reservations_id_seq OWNER TO express_sale_user;

--
-- Name: reservations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: express_sale_user
--

ALTER SEQUENCE public.reservations_id_seq OWNED BY public.reservations.id;


--
-- Name: sale_items; Type: TABLE; Schema: public; Owner: express_sale_user
--

CREATE TABLE public.sale_items (
    id integer NOT NULL,
    sale_id integer NOT NULL,
    variant_id integer NOT NULL,
    quantity integer NOT NULL,
    unit_price numeric(12,2) NOT NULL,
    subtotal numeric(12,2) NOT NULL,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT sale_items_quantity_check CHECK ((quantity > 0)),
    CONSTRAINT sale_items_subtotal_check CHECK ((subtotal >= (0)::numeric)),
    CONSTRAINT sale_items_unit_price_check CHECK ((unit_price >= (0)::numeric))
);


ALTER TABLE public.sale_items OWNER TO express_sale_user;

--
-- Name: sale_items_id_seq; Type: SEQUENCE; Schema: public; Owner: express_sale_user
--

CREATE SEQUENCE public.sale_items_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.sale_items_id_seq OWNER TO express_sale_user;

--
-- Name: sale_items_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: express_sale_user
--

ALTER SEQUENCE public.sale_items_id_seq OWNED BY public.sale_items.id;


--
-- Name: sales; Type: TABLE; Schema: public; Owner: express_sale_user
--

CREATE TABLE public.sales (
    id integer NOT NULL,
    sale_number character varying(50) NOT NULL,
    customer_id integer,
    user_id integer NOT NULL,
    sale_date timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    sale_type character varying(50) DEFAULT 'immediate'::character varying NOT NULL,
    subtotal numeric(12,2) NOT NULL,
    discount_amount numeric(12,2) DEFAULT 0,
    discount_reason text,
    total_amount numeric(12,2) NOT NULL,
    payment_status character varying(50) DEFAULT 'pending'::character varying,
    notes text,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    payment_method character varying(50),
    CONSTRAINT sale_type_check CHECK (((sale_type)::text = ANY (ARRAY[('immediate'::character varying)::text, ('credit'::character varying)::text, ('reservation'::character varying)::text]))),
    CONSTRAINT sales_discount_amount_check CHECK ((discount_amount >= (0)::numeric)),
    CONSTRAINT sales_payment_method_check CHECK (((payment_method)::text = ANY (ARRAY[('cash'::character varying)::text, ('mobile_money'::character varying)::text, ('bank_transfer'::character varying)::text, ('mixed'::character varying)::text]))),
    CONSTRAINT sales_payment_status_check CHECK (((payment_status)::text = ANY (ARRAY[('pending'::character varying)::text, ('partial'::character varying)::text, ('paid'::character varying)::text, ('cancelled'::character varying)::text]))),
    CONSTRAINT sales_sale_type_check CHECK (((sale_type)::text = ANY (ARRAY[('immediate'::character varying)::text, ('reservation'::character varying)::text, ('credit'::character varying)::text]))),
    CONSTRAINT sales_subtotal_check CHECK ((subtotal >= (0)::numeric)),
    CONSTRAINT sales_total_amount_check CHECK ((total_amount >= (0)::numeric))
);


ALTER TABLE public.sales OWNER TO express_sale_user;

--
-- Name: COLUMN sales.payment_method; Type: COMMENT; Schema: public; Owner: express_sale_user
--

COMMENT ON COLUMN public.sales.payment_method IS 'Méthode de paiement: cash, mobile_money, bank_transfer, mixed';


--
-- Name: sales_id_seq; Type: SEQUENCE; Schema: public; Owner: express_sale_user
--

CREATE SEQUENCE public.sales_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.sales_id_seq OWNER TO express_sale_user;

--
-- Name: sales_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: express_sale_user
--

ALTER SEQUENCE public.sales_id_seq OWNED BY public.sales.id;


--
-- Name: sessions; Type: TABLE; Schema: public; Owner: express_sale_user
--

CREATE TABLE public.sessions (
    id character varying(255) NOT NULL,
    user_id bigint,
    ip_address character varying(45),
    user_agent text,
    payload text NOT NULL,
    last_activity integer NOT NULL
);


ALTER TABLE public.sessions OWNER TO express_sale_user;

--
-- Name: stock_movements; Type: TABLE; Schema: public; Owner: express_sale_user
--

CREATE TABLE public.stock_movements (
    id bigint NOT NULL,
    variant_id integer NOT NULL,
    from_location_id integer,
    to_location_id integer,
    quantity integer NOT NULL,
    movement_type character varying(50) NOT NULL,
    sale_id integer,
    stock_receipt_id integer,
    performed_by integer NOT NULL,
    reason text,
    notes text,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    batch_id character varying(255),
    CONSTRAINT stock_movements_quantity_check CHECK ((quantity > 0)),
    CONSTRAINT valid_movement CHECK ((((from_location_id IS NOT NULL) OR (to_location_id IS NOT NULL)) AND ((from_location_id IS NULL) OR (to_location_id IS NULL) OR (from_location_id <> to_location_id))))
);


ALTER TABLE public.stock_movements OWNER TO express_sale_user;

--
-- Name: TABLE stock_movements; Type: COMMENT; Schema: public; Owner: express_sale_user
--

COMMENT ON TABLE public.stock_movements IS 'Historique de tous les mouvements de stock';


--
-- Name: COLUMN stock_movements.from_location_id; Type: COMMENT; Schema: public; Owner: express_sale_user
--

COMMENT ON COLUMN public.stock_movements.from_location_id IS 'Location source (NULL pour réception/ajustement positif)';


--
-- Name: COLUMN stock_movements.to_location_id; Type: COMMENT; Schema: public; Owner: express_sale_user
--

COMMENT ON COLUMN public.stock_movements.to_location_id IS 'Location destination (NULL pour vente/ajustement négatif)';


--
-- Name: COLUMN stock_movements.movement_type; Type: COMMENT; Schema: public; Owner: express_sale_user
--

COMMENT ON COLUMN public.stock_movements.movement_type IS 'Types: transfer (transfert entre locations), receipt (réception stock), sale (vente), adjustment (ajustement), return (retour)';


--
-- Name: COLUMN stock_movements.sale_id; Type: COMMENT; Schema: public; Owner: express_sale_user
--

COMMENT ON COLUMN public.stock_movements.sale_id IS 'Référence à la vente si le mouvement est lié à une vente';


--
-- Name: COLUMN stock_movements.stock_receipt_id; Type: COMMENT; Schema: public; Owner: express_sale_user
--

COMMENT ON COLUMN public.stock_movements.stock_receipt_id IS 'Référence au réapprovisionnement si le mouvement est lié à une réception';


--
-- Name: stock_movements_id_seq; Type: SEQUENCE; Schema: public; Owner: express_sale_user
--

CREATE SEQUENCE public.stock_movements_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.stock_movements_id_seq OWNER TO express_sale_user;

--
-- Name: stock_movements_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: express_sale_user
--

ALTER SEQUENCE public.stock_movements_id_seq OWNED BY public.stock_movements.id;


--
-- Name: stock_receipt_item_ratings; Type: TABLE; Schema: public; Owner: express_sale_user
--

CREATE TABLE public.stock_receipt_item_ratings (
    id integer NOT NULL,
    stock_receipt_item_id integer NOT NULL,
    attribute_type_id integer,
    attribute_conformity_rating numeric(4,2) DEFAULT 5.00,
    quality_rating numeric(4,2) NOT NULL,
    quality_notes text,
    rated_by integer,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT stock_receipt_item_ratings_attribute_conformity_check CHECK (((attribute_conformity_rating >= (0)::numeric) AND (attribute_conformity_rating <= (10)::numeric))),
    CONSTRAINT stock_receipt_item_ratings_quality_rating_check CHECK (((quality_rating >= (0)::numeric) AND (quality_rating <= (10)::numeric)))
);


ALTER TABLE public.stock_receipt_item_ratings OWNER TO express_sale_user;

--
-- Name: TABLE stock_receipt_item_ratings; Type: COMMENT; Schema: public; Owner: express_sale_user
--

COMMENT ON TABLE public.stock_receipt_item_ratings IS 'Évaluation de la qualité des articles reçus par commande';


--
-- Name: COLUMN stock_receipt_item_ratings.attribute_conformity_rating; Type: COMMENT; Schema: public; Owner: express_sale_user
--

COMMENT ON COLUMN public.stock_receipt_item_ratings.attribute_conformity_rating IS 'Note de conformité pour un attribut spécifique (couleur, taille, etc.) sur 10';


--
-- Name: COLUMN stock_receipt_item_ratings.quality_rating; Type: COMMENT; Schema: public; Owner: express_sale_user
--

COMMENT ON COLUMN public.stock_receipt_item_ratings.quality_rating IS 'Note de qualité générale du produit sur 10';


--
-- Name: stock_receipt_item_ratings_id_seq; Type: SEQUENCE; Schema: public; Owner: express_sale_user
--

CREATE SEQUENCE public.stock_receipt_item_ratings_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.stock_receipt_item_ratings_id_seq OWNER TO express_sale_user;

--
-- Name: stock_receipt_item_ratings_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: express_sale_user
--

ALTER SEQUENCE public.stock_receipt_item_ratings_id_seq OWNED BY public.stock_receipt_item_ratings.id;


--
-- Name: stock_receipt_items; Type: TABLE; Schema: public; Owner: express_sale_user
--

CREATE TABLE public.stock_receipt_items (
    id integer NOT NULL,
    stock_receipt_id integer NOT NULL,
    variant_id integer NOT NULL,
    quantity_ordered integer NOT NULL,
    quantity_received integer NOT NULL,
    unit_cost_ariary numeric(12,2) NOT NULL,
    notes text,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT stock_receipt_items_quantity_ordered_check CHECK ((quantity_ordered > 0)),
    CONSTRAINT stock_receipt_items_quantity_received_check CHECK ((quantity_received >= 0))
);


ALTER TABLE public.stock_receipt_items OWNER TO express_sale_user;

--
-- Name: COLUMN stock_receipt_items.unit_cost_ariary; Type: COMMENT; Schema: public; Owner: express_sale_user
--

COMMENT ON COLUMN public.stock_receipt_items.unit_cost_ariary IS 'Coût unitaire en Ariary (conversion faite côté frontend)';


--
-- Name: stock_receipt_items_id_seq; Type: SEQUENCE; Schema: public; Owner: express_sale_user
--

CREATE SEQUENCE public.stock_receipt_items_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.stock_receipt_items_id_seq OWNER TO express_sale_user;

--
-- Name: stock_receipt_items_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: express_sale_user
--

ALTER SEQUENCE public.stock_receipt_items_id_seq OWNED BY public.stock_receipt_items.id;


--
-- Name: stock_receipts; Type: TABLE; Schema: public; Owner: express_sale_user
--

CREATE TABLE public.stock_receipts (
    id integer NOT NULL,
    receipt_number character varying(50) NOT NULL,
    supplier_id integer NOT NULL,
    freight_forwarder_id integer,
    total_cost_ariary numeric(12,2) NOT NULL,
    status character varying(50) DEFAULT 'pending'::character varying,
    notes text,
    created_by integer,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    expected_delivery_date date,
    actual_delivery_date timestamp without time zone,
    validated_at timestamp without time zone
);


ALTER TABLE public.stock_receipts OWNER TO express_sale_user;

--
-- Name: TABLE stock_receipts; Type: COMMENT; Schema: public; Owner: express_sale_user
--

COMMENT ON TABLE public.stock_receipts IS 'Réception de stock - Tous les montants en Ariary uniquement';


--
-- Name: COLUMN stock_receipts.status; Type: COMMENT; Schema: public; Owner: express_sale_user
--

COMMENT ON COLUMN public.stock_receipts.status IS 'pending, sent, in_transit, arrived, validated, cancelled';


--
-- Name: stock_receipts_id_seq; Type: SEQUENCE; Schema: public; Owner: express_sale_user
--

CREATE SEQUENCE public.stock_receipts_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.stock_receipts_id_seq OWNER TO express_sale_user;

--
-- Name: stock_receipts_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: express_sale_user
--

ALTER SEQUENCE public.stock_receipts_id_seq OWNED BY public.stock_receipts.id;


--
-- Name: suppliers; Type: TABLE; Schema: public; Owner: express_sale_user
--

CREATE TABLE public.suppliers (
    id integer NOT NULL,
    name character varying(255) NOT NULL,
    wechat character varying(100),
    profile text,
    contact character varying(255),
    accessibility_notes text,
    reliability_score numeric(4,2) DEFAULT 5.00,
    logo_url character varying(500),
    is_active boolean DEFAULT true,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    coordinate_id bigint,
    total_orders integer DEFAULT 0,
    total_items_ordered integer DEFAULT 0,
    total_items_received integer DEFAULT 0,
    quality_rating_sum numeric(10,2) DEFAULT 0,
    quality_rating_count integer DEFAULT 0,
    total_value_ordered numeric(15,2) DEFAULT 0,
    total_value_received numeric(15,2) DEFAULT 0,
    weighted_quality_sum numeric(15,2) DEFAULT 0,
    total_weighted_value numeric(15,2) DEFAULT 0,
    CONSTRAINT suppliers_reliability_score_check CHECK (((reliability_score >= (0)::numeric) AND (reliability_score <= (10)::numeric)))
);


ALTER TABLE public.suppliers OWNER TO express_sale_user;

--
-- Name: TABLE suppliers; Type: COMMENT; Schema: public; Owner: express_sale_user
--

COMMENT ON TABLE public.suppliers IS 'Fournisseurs Chine';


--
-- Name: COLUMN suppliers.reliability_score; Type: COMMENT; Schema: public; Owner: express_sale_user
--

COMMENT ON COLUMN public.suppliers.reliability_score IS 'Score global sur 10 (pondéré par valeur)';


--
-- Name: COLUMN suppliers.total_orders; Type: COMMENT; Schema: public; Owner: express_sale_user
--

COMMENT ON COLUMN public.suppliers.total_orders IS 'Nombre total de commandes passées';


--
-- Name: COLUMN suppliers.total_items_ordered; Type: COMMENT; Schema: public; Owner: express_sale_user
--

COMMENT ON COLUMN public.suppliers.total_items_ordered IS 'Quantité totale commandée (tous produits)';


--
-- Name: COLUMN suppliers.total_items_received; Type: COMMENT; Schema: public; Owner: express_sale_user
--

COMMENT ON COLUMN public.suppliers.total_items_received IS 'Quantité totale reçue (tous produits)';


--
-- Name: COLUMN suppliers.quality_rating_sum; Type: COMMENT; Schema: public; Owner: express_sale_user
--

COMMENT ON COLUMN public.suppliers.quality_rating_sum IS 'Somme simple des notes de qualité';


--
-- Name: COLUMN suppliers.quality_rating_count; Type: COMMENT; Schema: public; Owner: express_sale_user
--

COMMENT ON COLUMN public.suppliers.quality_rating_count IS 'Nombre de notations qualité reçues';


--
-- Name: COLUMN suppliers.total_value_ordered; Type: COMMENT; Schema: public; Owner: express_sale_user
--

COMMENT ON COLUMN public.suppliers.total_value_ordered IS 'Valeur totale commandée en Ariary';


--
-- Name: COLUMN suppliers.total_value_received; Type: COMMENT; Schema: public; Owner: express_sale_user
--

COMMENT ON COLUMN public.suppliers.total_value_received IS 'Valeur totale reçue en Ariary (quantité reçue × prix unitaire)';


--
-- Name: COLUMN suppliers.weighted_quality_sum; Type: COMMENT; Schema: public; Owner: express_sale_user
--

COMMENT ON COLUMN public.suppliers.weighted_quality_sum IS 'Somme pondérée: note × valeur article';


--
-- Name: COLUMN suppliers.total_weighted_value; Type: COMMENT; Schema: public; Owner: express_sale_user
--

COMMENT ON COLUMN public.suppliers.total_weighted_value IS 'Somme totale des valeurs utilisées pour pondération';


--
-- Name: suppliers_id_seq; Type: SEQUENCE; Schema: public; Owner: express_sale_user
--

CREATE SEQUENCE public.suppliers_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.suppliers_id_seq OWNER TO express_sale_user;

--
-- Name: suppliers_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: express_sale_user
--

ALTER SEQUENCE public.suppliers_id_seq OWNED BY public.suppliers.id;


--
-- Name: system_settings; Type: TABLE; Schema: public; Owner: express_sale_user
--

CREATE TABLE public.system_settings (
    id integer NOT NULL,
    key character varying(255) NOT NULL,
    value text,
    value_type character varying(50) DEFAULT 'string'::character varying,
    description text,
    is_public boolean DEFAULT false,
    updated_by integer,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT system_settings_value_type_check CHECK (((value_type)::text = ANY (ARRAY[('string'::character varying)::text, ('number'::character varying)::text, ('boolean'::character varying)::text, ('json'::character varying)::text])))
);


ALTER TABLE public.system_settings OWNER TO express_sale_user;

--
-- Name: system_settings_id_seq; Type: SEQUENCE; Schema: public; Owner: express_sale_user
--

CREATE SEQUENCE public.system_settings_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.system_settings_id_seq OWNER TO express_sale_user;

--
-- Name: system_settings_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: express_sale_user
--

ALTER SEQUENCE public.system_settings_id_seq OWNED BY public.system_settings.id;


--
-- Name: transaction_types; Type: TABLE; Schema: public; Owner: express_sale_user
--

CREATE TABLE public.transaction_types (
    id integer NOT NULL,
    code character varying(50) NOT NULL,
    name character varying(100) NOT NULL,
    display_name character varying(255) NOT NULL,
    category character varying(50) NOT NULL,
    description text,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT transaction_types_category_check CHECK (((category)::text = ANY (ARRAY[('income'::character varying)::text, ('expense'::character varying)::text, ('transfer'::character varying)::text, ('adjustment'::character varying)::text, ('opening_balance'::character varying)::text, ('refund'::character varying)::text])))
);


ALTER TABLE public.transaction_types OWNER TO express_sale_user;

--
-- Name: TABLE transaction_types; Type: COMMENT; Schema: public; Owner: express_sale_user
--

COMMENT ON TABLE public.transaction_types IS 'Types de transactions possibles (revenus, dépenses, transferts)';


--
-- Name: transaction_types_id_seq; Type: SEQUENCE; Schema: public; Owner: express_sale_user
--

CREATE SEQUENCE public.transaction_types_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.transaction_types_id_seq OWNER TO express_sale_user;

--
-- Name: transaction_types_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: express_sale_user
--

ALTER SEQUENCE public.transaction_types_id_seq OWNED BY public.transaction_types.id;


--
-- Name: user_sessions; Type: TABLE; Schema: public; Owner: express_sale_user
--

CREATE TABLE public.user_sessions (
    id integer NOT NULL,
    user_id integer NOT NULL,
    login_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    logout_at timestamp without time zone,
    ip_address character varying(45),
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.user_sessions OWNER TO express_sale_user;

--
-- Name: user_sessions_id_seq; Type: SEQUENCE; Schema: public; Owner: express_sale_user
--

CREATE SEQUENCE public.user_sessions_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.user_sessions_id_seq OWNER TO express_sale_user;

--
-- Name: user_sessions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: express_sale_user
--

ALTER SEQUENCE public.user_sessions_id_seq OWNED BY public.user_sessions.id;


--
-- Name: users; Type: TABLE; Schema: public; Owner: express_sale_user
--

CREATE TABLE public.users (
    id integer NOT NULL,
    name character varying(255) NOT NULL,
    username character varying(255) NOT NULL,
    password character varying(255) NOT NULL,
    role character varying(50) DEFAULT 'vendeur'::character varying,
    is_active boolean DEFAULT true,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT users_role_check CHECK (((role)::text = ANY (ARRAY[('admin'::character varying)::text, ('vendeur'::character varying)::text])))
);


ALTER TABLE public.users OWNER TO express_sale_user;

--
-- Name: TABLE users; Type: COMMENT; Schema: public; Owner: express_sale_user
--

COMMENT ON TABLE public.users IS 'Utilisateurs internes (admin + vendeurs)';


--
-- Name: users_id_seq; Type: SEQUENCE; Schema: public; Owner: express_sale_user
--

CREATE SEQUENCE public.users_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.users_id_seq OWNER TO express_sale_user;

--
-- Name: users_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: express_sale_user
--

ALTER SEQUENCE public.users_id_seq OWNED BY public.users.id;


--
-- Name: v_accounts_summary; Type: VIEW; Schema: public; Owner: express_sale_user
--

CREATE VIEW public.v_accounts_summary AS
 SELECT a.id,
    a.name,
    at.display_name AS account_type,
    a.account_number,
    a.initial_balance,
    a.current_balance,
    (a.current_balance - a.initial_balance) AS net_change,
    ( SELECT count(*) AS count
           FROM public.account_transactions
          WHERE (account_transactions.account_id = a.id)) AS transaction_count,
    a.is_active,
    a.created_at
   FROM (public.accounts a
     JOIN public.account_types at ON ((a.account_type_id = at.id)))
  ORDER BY at.code, a.name;


ALTER TABLE public.v_accounts_summary OWNER TO express_sale_user;

--
-- Name: VIEW v_accounts_summary; Type: COMMENT; Schema: public; Owner: express_sale_user
--

COMMENT ON VIEW public.v_accounts_summary IS 'Résumé de tous les comptes avec statistiques';


--
-- Name: v_credits_at_risk; Type: VIEW; Schema: public; Owner: express_sale_user
--

CREATE VIEW public.v_credits_at_risk AS
 SELECT cr.id AS credit_id,
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
            WHEN ((cr.status)::text = 'overdue'::text) THEN (CURRENT_DATE - cr.due_date)
            ELSE 0
        END AS days_overdue,
    cr.credit_date
   FROM ((public.credits cr
     JOIN public.sales s ON ((cr.sale_id = s.id)))
     JOIN public.customers c ON ((cr.customer_id = c.id)))
  WHERE (((cr.status)::text = ANY (ARRAY[('active'::character varying)::text, ('partial_paid'::character varying)::text, ('overdue'::character varying)::text])) AND ((cr.due_date <= (CURRENT_DATE + '7 days'::interval)) OR ((cr.status)::text = 'overdue'::text)))
  ORDER BY cr.due_date;


ALTER TABLE public.v_credits_at_risk OWNER TO express_sale_user;

--
-- Name: v_recent_transactions; Type: VIEW; Schema: public; Owner: express_sale_user
--

CREATE VIEW public.v_recent_transactions AS
 SELECT t.id,
    t.transaction_date,
    a.name AS account_name,
    tt.display_name AS transaction_type,
    tt.category,
    t.amount,
    t.balance_after,
    COALESCE(s.name, ff.name, ec.name, t.recipient_name) AS counterparty,
    t.description,
    u.name AS created_by_name
   FROM ((((((public.account_transactions t
     JOIN public.accounts a ON ((t.account_id = a.id)))
     JOIN public.transaction_types tt ON ((t.transaction_type_id = tt.id)))
     LEFT JOIN public.suppliers s ON ((t.supplier_id = s.id)))
     LEFT JOIN public.freight_forwarders ff ON ((t.freight_forwarder_id = ff.id)))
     LEFT JOIN public.expense_categories ec ON ((t.expense_category_id = ec.id)))
     LEFT JOIN public.users u ON ((t.created_by = u.id)))
  ORDER BY t.transaction_date DESC, t.id DESC
 LIMIT 100;


ALTER TABLE public.v_recent_transactions OWNER TO express_sale_user;

--
-- Name: VIEW v_recent_transactions; Type: COMMENT; Schema: public; Owner: express_sale_user
--

COMMENT ON VIEW public.v_recent_transactions IS 'Dernières transactions avec tous les détails';


--
-- Name: v_reservations_pending; Type: VIEW; Schema: public; Owner: express_sale_user
--

CREATE VIEW public.v_reservations_pending AS
 SELECT r.id AS reservation_id,
    r.sale_id,
    s.sale_number,
    c.name AS customer_name,
    c.phone AS customer_phone,
    r.total_amount,
    r.deposit_amount,
    r.remaining_amount,
    r.expiry_date,
    (EXTRACT(epoch FROM ((r.expiry_date)::timestamp with time zone - CURRENT_TIMESTAMP)) / (3600)::numeric) AS hours_remaining,
    r.status,
    r.reservation_date
   FROM ((public.reservations r
     JOIN public.sales s ON ((r.sale_id = s.id)))
     JOIN public.customers c ON ((r.customer_id = c.id)))
  WHERE (((r.status)::text = ANY (ARRAY[('pending'::character varying)::text, ('confirmed'::character varying)::text, ('partial_paid'::character varying)::text])) AND (r.expiry_date > CURRENT_TIMESTAMP))
  ORDER BY r.expiry_date;


ALTER TABLE public.v_reservations_pending OWNER TO express_sale_user;

--
-- Name: v_treasury_dashboard; Type: VIEW; Schema: public; Owner: express_sale_user
--

CREATE VIEW public.v_treasury_dashboard AS
 SELECT at.display_name AS account_type,
    count(a.id) AS account_count,
    sum(a.current_balance) AS total_balance,
    sum(a.initial_balance) AS total_initial_balance,
    sum((a.current_balance - a.initial_balance)) AS net_change
   FROM (public.account_types at
     LEFT JOIN public.accounts a ON (((at.id = a.account_type_id) AND (a.is_active = true))))
  GROUP BY at.id, at.display_name, at.code
  ORDER BY at.code;


ALTER TABLE public.v_treasury_dashboard OWNER TO express_sale_user;

--
-- Name: VIEW v_treasury_dashboard; Type: COMMENT; Schema: public; Owner: express_sale_user
--

COMMENT ON VIEW public.v_treasury_dashboard IS 'Tableau de bord de la trésorerie globale';


--
-- Name: variant_attribute_values; Type: TABLE; Schema: public; Owner: express_sale_user
--

CREATE TABLE public.variant_attribute_values (
    id integer NOT NULL,
    variant_id integer NOT NULL,
    attribute_value_id integer NOT NULL,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.variant_attribute_values OWNER TO express_sale_user;

--
-- Name: variant_attribute_values_id_seq; Type: SEQUENCE; Schema: public; Owner: express_sale_user
--

CREATE SEQUENCE public.variant_attribute_values_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.variant_attribute_values_id_seq OWNER TO express_sale_user;

--
-- Name: variant_attribute_values_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: express_sale_user
--

ALTER SEQUENCE public.variant_attribute_values_id_seq OWNED BY public.variant_attribute_values.id;


--
-- Name: account_transactions id; Type: DEFAULT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.account_transactions ALTER COLUMN id SET DEFAULT nextval('public.account_transactions_id_seq'::regclass);


--
-- Name: account_types id; Type: DEFAULT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.account_types ALTER COLUMN id SET DEFAULT nextval('public.account_types_id_seq'::regclass);


--
-- Name: accounts id; Type: DEFAULT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.accounts ALTER COLUMN id SET DEFAULT nextval('public.accounts_id_seq'::regclass);


--
-- Name: attribute_types id; Type: DEFAULT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.attribute_types ALTER COLUMN id SET DEFAULT nextval('public.attribute_types_id_seq'::regclass);


--
-- Name: attribute_values id; Type: DEFAULT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.attribute_values ALTER COLUMN id SET DEFAULT nextval('public.attribute_values_id_seq'::regclass);


--
-- Name: audit_logs id; Type: DEFAULT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.audit_logs ALTER COLUMN id SET DEFAULT nextval('public.audit_logs_id_seq'::regclass);


--
-- Name: categories id; Type: DEFAULT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.categories ALTER COLUMN id SET DEFAULT nextval('public.categories_id_seq'::regclass);


--
-- Name: coordinates id; Type: DEFAULT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.coordinates ALTER COLUMN id SET DEFAULT nextval('public.coordinates_id_seq'::regclass);


--
-- Name: credit_installments id; Type: DEFAULT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.credit_installments ALTER COLUMN id SET DEFAULT nextval('public.credit_installments_id_seq'::regclass);


--
-- Name: credits id; Type: DEFAULT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.credits ALTER COLUMN id SET DEFAULT nextval('public.credits_id_seq'::regclass);


--
-- Name: currency_rates id; Type: DEFAULT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.currency_rates ALTER COLUMN id SET DEFAULT nextval('public.currency_rates_id_seq'::regclass);


--
-- Name: customers id; Type: DEFAULT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.customers ALTER COLUMN id SET DEFAULT nextval('public.customers_id_seq'::regclass);


--
-- Name: expense_categories id; Type: DEFAULT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.expense_categories ALTER COLUMN id SET DEFAULT nextval('public.expense_categories_id_seq'::regclass);


--
-- Name: failed_jobs id; Type: DEFAULT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.failed_jobs ALTER COLUMN id SET DEFAULT nextval('public.failed_jobs_id_seq'::regclass);


--
-- Name: freight_forwarders id; Type: DEFAULT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.freight_forwarders ALTER COLUMN id SET DEFAULT nextval('public.freight_forwarders_id_seq'::regclass);


--
-- Name: installment_transactions id; Type: DEFAULT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.installment_transactions ALTER COLUMN id SET DEFAULT nextval('public.installment_transactions_id_seq'::regclass);


--
-- Name: jobs id; Type: DEFAULT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.jobs ALTER COLUMN id SET DEFAULT nextval('public.jobs_id_seq'::regclass);


--
-- Name: locations id; Type: DEFAULT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.locations ALTER COLUMN id SET DEFAULT nextval('public.locations_id_seq'::regclass);


--
-- Name: migrations id; Type: DEFAULT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.migrations ALTER COLUMN id SET DEFAULT nextval('public.migrations_id_seq'::regclass);


--
-- Name: notifications id; Type: DEFAULT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.notifications ALTER COLUMN id SET DEFAULT nextval('public.notifications_id_seq'::regclass);


--
-- Name: personal_access_tokens id; Type: DEFAULT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.personal_access_tokens ALTER COLUMN id SET DEFAULT nextval('public.personal_access_tokens_id_seq'::regclass);


--
-- Name: product_attributes id; Type: DEFAULT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.product_attributes ALTER COLUMN id SET DEFAULT nextval('public.product_attributes_id_seq'::regclass);


--
-- Name: product_variant_locations id; Type: DEFAULT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.product_variant_locations ALTER COLUMN id SET DEFAULT nextval('public.product_variant_locations_id_seq'::regclass);


--
-- Name: product_variants id; Type: DEFAULT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.product_variants ALTER COLUMN id SET DEFAULT nextval('public.product_variants_id_seq'::regclass);


--
-- Name: products id; Type: DEFAULT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.products ALTER COLUMN id SET DEFAULT nextval('public.products_id_seq'::regclass);


--
-- Name: reservation_deposits id; Type: DEFAULT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.reservation_deposits ALTER COLUMN id SET DEFAULT nextval('public.reservation_deposits_id_seq'::regclass);


--
-- Name: reservations id; Type: DEFAULT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.reservations ALTER COLUMN id SET DEFAULT nextval('public.reservations_id_seq'::regclass);


--
-- Name: sale_items id; Type: DEFAULT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.sale_items ALTER COLUMN id SET DEFAULT nextval('public.sale_items_id_seq'::regclass);


--
-- Name: sales id; Type: DEFAULT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.sales ALTER COLUMN id SET DEFAULT nextval('public.sales_id_seq'::regclass);


--
-- Name: stock_movements id; Type: DEFAULT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.stock_movements ALTER COLUMN id SET DEFAULT nextval('public.stock_movements_id_seq'::regclass);


--
-- Name: stock_receipt_item_ratings id; Type: DEFAULT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.stock_receipt_item_ratings ALTER COLUMN id SET DEFAULT nextval('public.stock_receipt_item_ratings_id_seq'::regclass);


--
-- Name: stock_receipt_items id; Type: DEFAULT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.stock_receipt_items ALTER COLUMN id SET DEFAULT nextval('public.stock_receipt_items_id_seq'::regclass);


--
-- Name: stock_receipts id; Type: DEFAULT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.stock_receipts ALTER COLUMN id SET DEFAULT nextval('public.stock_receipts_id_seq'::regclass);


--
-- Name: suppliers id; Type: DEFAULT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.suppliers ALTER COLUMN id SET DEFAULT nextval('public.suppliers_id_seq'::regclass);


--
-- Name: system_settings id; Type: DEFAULT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.system_settings ALTER COLUMN id SET DEFAULT nextval('public.system_settings_id_seq'::regclass);


--
-- Name: transaction_types id; Type: DEFAULT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.transaction_types ALTER COLUMN id SET DEFAULT nextval('public.transaction_types_id_seq'::regclass);


--
-- Name: user_sessions id; Type: DEFAULT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.user_sessions ALTER COLUMN id SET DEFAULT nextval('public.user_sessions_id_seq'::regclass);


--
-- Name: users id; Type: DEFAULT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.users ALTER COLUMN id SET DEFAULT nextval('public.users_id_seq'::regclass);


--
-- Name: variant_attribute_values id; Type: DEFAULT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.variant_attribute_values ALTER COLUMN id SET DEFAULT nextval('public.variant_attribute_values_id_seq'::regclass);


--
-- Data for Name: account_transactions; Type: TABLE DATA; Schema: public; Owner: express_sale_user
--

COPY public.account_transactions (id, account_id, transaction_type_id, amount, balance_before, balance_after, transaction_date, related_account_id, related_transaction_id, supplier_id, freight_forwarder_id, stock_receipt_id, expense_category_id, recipient_name, sale_id, reference_number, description, notes, created_by, created_at, reversed_transaction_id) FROM stdin;
1	2	15	5000000.00	0.00	5000000.00	2025-12-29 15:11:25.726849	\N	\N	\N	\N	\N	\N	\N	\N	\N	Solde initial du compte	\N	4	2025-12-29 15:11:25.726849	\N
2	3	15	2000000.00	0.00	2000000.00	2025-12-29 15:17:58.13301	\N	\N	\N	\N	\N	\N	\N	\N	\N	Solde initial du compte	\N	4	2025-12-29 15:17:58.13301	\N
3	4	15	10000000.00	0.00	10000000.00	2025-12-29 15:18:31.131506	\N	\N	\N	\N	\N	\N	\N	\N	\N	Solde initial du compte	\N	4	2025-12-29 15:18:31.131506	\N
4	2	18	500000.00	5000000.00	4500000.00	2025-12-29 15:52:31.033682	3	\N	\N	\N	\N	\N	\N	\N	TRF-001	Transfert sortant: Transfert pour approvisionnement MVola	\N	4	2025-12-29 15:52:31.033682	\N
5	3	18	500000.00	2000000.00	2500000.00	2025-12-29 15:52:31.033682	2	4	\N	\N	\N	\N	\N	\N	TRF-001	Transfert entrant: Transfert pour approvisionnement MVola	\N	4	2025-12-29 15:52:31.033682	\N
6	3	18	100.00	2500000.00	2499900.00	2025-12-29 16:10:18.391169	4	\N	\N	\N	\N	\N	\N	\N	Ta-001	Transfert sortant: Transfert pour approvisionnement Banque	\N	4	2025-12-29 16:10:18.391169	\N
7	4	18	100.00	10000000.00	10000100.00	2025-12-29 16:10:18.391169	3	6	\N	\N	\N	\N	\N	\N	Ta-001	Transfert entrant: Transfert pour approvisionnement Banque	\N	4	2025-12-29 16:10:18.391169	\N
8	3	18	200.00	2499900.00	2499700.00	2025-12-29 16:12:42.975987	4	\N	\N	\N	\N	\N	\N	\N	Ta-0011	Transfert sortant: Transfert pour approvisionnement Banque	\N	4	2025-12-29 16:12:42.975987	\N
9	4	18	200.00	10000100.00	10000300.00	2025-12-29 16:12:42.975987	3	8	\N	\N	\N	\N	\N	\N	Ta-0011	Transfert entrant: Transfert pour approvisionnement Banque	\N	4	2025-12-29 16:12:42.975987	\N
10	3	18	300.00	2499700.00	2499400.00	2025-12-29 13:17:50	4	\N	\N	\N	\N	\N	\N	\N	Ta-00111	Transfert sortant: Transfert pour approvisionnement Banque	\N	4	2025-12-29 16:17:50.241267	\N
11	4	18	300.00	10000300.00	10000600.00	2025-12-29 13:17:50	3	10	\N	\N	\N	\N	\N	\N	Ta-00111	Transfert entrant: Transfert pour approvisionnement Banque	\N	4	2025-12-29 16:17:50.241267	\N
12	3	18	300.00	2499400.00	2499100.00	2025-12-29 13:22:51	4	\N	\N	\N	\N	\N	\N	\N	Ta-00111	Transfert sortant: Transfert pour approvisionnement Banque	\N	4	2025-12-29 16:22:51.251975	\N
13	4	18	300.00	10000600.00	10000900.00	2025-12-29 13:22:51	3	12	\N	\N	\N	\N	\N	\N	Ta-00111	Transfert entrant: Transfert pour approvisionnement Banque	\N	4	2025-12-29 16:22:51.251975	\N
14	3	18	300.00	2499100.00	2498800.00	2025-12-29 13:23:38	4	\N	\N	\N	\N	\N	\N	\N	Ta-00111	Transfert sortant: Transfert pour approvisionnement Banque	\N	4	2025-12-29 16:23:38.960203	\N
15	4	18	300.00	10000900.00	10001200.00	2025-12-29 13:23:38	3	14	\N	\N	\N	\N	\N	\N	Ta-00111	Transfert entrant: Transfert pour approvisionnement Banque	\N	4	2025-12-29 16:23:38.960203	\N
17	4	18	300.00	10001200.00	10001500.00	2025-12-30 16:00:00	3	16	\N	\N	\N	\N	\N	\N	Ta-00111	Transfert entrant: Transfert pour approvisionnement Banque	\N	4	2025-12-29 16:43:51.085524	\N
16	3	18	300.00	2498800.00	2498500.00	2025-12-30 16:00:00	4	17	\N	\N	\N	\N	\N	\N	Ta-00111	Transfert sortant: Transfert pour approvisionnement Banque	\N	4	2025-12-29 16:43:51.085524	\N
19	4	18	300.00	10001500.00	10001800.00	2025-12-30 16:00:00	3	18	\N	\N	\N	\N	\N	\N	Ta-00111	Transfert de compte MVola Principal	onjourur	4	2025-12-29 16:50:19.795625	\N
18	3	18	300.00	2498500.00	2498200.00	2025-12-30 16:00:00	4	19	\N	\N	\N	\N	\N	\N	Ta-00111	Transfert vers compte BNI Madagascar	onjourur	4	2025-12-29 16:50:19.795625	\N
21	4	17	50000.00	10001800.00	9951800.00	2026-01-01 15:00:00	\N	\N	\N	\N	\N	2	JIRAMA	\N	EXP-2025-001	Facture Janvier 2025 - Réf: FACT-001	Facture Janvier 2025 - Réf: FACT-001	4	2025-12-29 17:16:30.104938	\N
23	4	18	300.00	9951800.00	9952100.00	2025-12-30 16:00:00	3	22	\N	\N	\N	\N	\N	\N	Ta-00111	Transfert de compte MVola Principal	auyuy	4	2025-12-29 17:17:58.468607	\N
22	3	18	300.00	2498200.00	2497900.00	2025-12-30 16:00:00	4	23	\N	\N	\N	\N	\N	\N	Ta-00111	Transfert vers compte BNI Madagascar	auyuy	4	2025-12-29 17:17:58.468607	\N
24	3	17	2000000.00	2497900.00	497900.00	2026-01-30 16:00:00	\N	\N	\N	\N	\N	5	Personnel	\N	\N	3 employés	3 employés	4	2025-12-29 22:04:23.876559	\N
26	4	17	155000.00	9952100.00	9797100.00	2025-12-30 10:30:00	\N	\N	1	\N	2	15	Guangzhou_corpaaa	\N	PAY-SUPP-001	Paiement fournisseur Guangzhou_corpaaa - Réception #RCP-20251230-0002	Paiement partiel pour réception #REC-2025-001	4	2025-12-30 19:38:09.567823	\N
27	4	17	190000.00	9797100.00	9607100.00	2025-12-30 10:30:00	\N	\N	\N	1	2	6	China express logistics be	\N	PAY-SUPP-001	Paiement transitaire China express logistics be - Réception #RCP-20251230-0002	Paiement pour réception #REC-2025-001	4	2025-12-30 19:42:57.490557	\N
28	4	17	2750000.00	9607100.00	6857100.00	2025-12-30 10:30:00	\N	\N	1	\N	1	15	Guangzhou_corpaaa	\N	PAY-SUPP-00101	Paiement fournisseur Guangzhou_corpaaa - Réception #RCP-20251230-0001	Paiement pour réception #REC-2025-001	4	2025-12-30 16:52:44	\N
29	4	17	207088.00	6857100.00	6650012.00	2026-01-01 00:00:00	\N	\N	1	\N	3	15	Guangzhou_corpaaa	\N	jhzhsjhz	Paiement fournisseur Guangzhou_corpaaa - Réception #RCP-20260101-0001	as	4	2026-01-01 10:58:05	\N
30	3	17	19000.00	497900.00	478900.00	2026-01-01 00:00:00	\N	\N	\N	1	3	6	China express logistics be	\N	asa	Paiement transitaire China express logistics be - Réception #RCP-20260101-0001	ksjkzkej	4	2026-01-01 10:58:05	\N
31	4	17	300.01	6650012.00	6649711.99	2026-01-01 00:00:00	\N	\N	3	\N	5	15	raggathon	\N	KJKJK2	Paiement fournisseur raggathon - Réception #RCP-20260101-0003	kjdkjkdjeke	4	2026-01-01 11:42:15	\N
32	4	17	360000.00	6649711.99	6289711.99	2026-01-01 00:00:00	\N	\N	\N	1	6	6	China express logistics be	\N	freiieuei	Paiement transitaire China express logistics be - Réception #RCP-20260101-0004	ejkjdkej	4	2026-01-01 12:32:22	\N
33	4	17	144000.00	6289711.99	6145711.99	2026-01-01 00:00:00	\N	\N	2	\N	7	15	HERIMANANTSOA Manitriniaina Christian AA	\N	\N	Paiement fournisseur HERIMANANTSOA Manitriniaina Christian AA - Réception #RCP-20260101-0005	\N	4	2026-01-01 13:19:39	\N
34	3	17	408000.00	478900.00	70900.00	2026-01-01 00:00:00	\N	\N	\N	1	7	6	China express logistics be	\N	\N	Paiement transitaire China express logistics be - Réception #RCP-20260101-0005	\N	4	2026-01-01 13:19:39	\N
35	4	17	516000.00	6145711.99	5629711.99	2026-01-03 00:00:00	\N	\N	2	\N	9	15	HERIMANANTSOA Manitriniaina Christian AA	\N	REF-RARRARA	Paiement fournisseur HERIMANANTSOA Manitriniaina Christian AA - Réception #RCP-20260103-0001	FEKFLEKLEK	4	2026-01-03 14:01:53	\N
36	4	17	120000.00	5629711.99	5509711.99	2026-01-03 00:00:00	\N	\N	\N	1	9	6	China express logistics be	\N	RAREAT	Paiement transitaire China express logistics be - Réception #RCP-20260103-0001	mety tsara	4	2026-01-03 14:01:53	\N
37	4	17	463800.00	5509711.99	5045911.99	2026-01-03 00:00:00	\N	\N	2	\N	10	15	HERIMANANTSOA Manitriniaina Christian AA	\N	\N	Paiement fournisseur HERIMANANTSOA Manitriniaina Christian AA - Réception #RCP-20260103-0002	\N	4	2026-01-03 15:18:07	\N
38	4	17	120000.00	5045911.99	4925911.99	2026-01-03 00:00:00	\N	\N	\N	1	10	6	China express logistics be	\N	\N	Paiement transitaire China express logistics be - Réception #RCP-20260103-0002	\N	4	2026-01-03 15:18:07	\N
39	4	17	2060000.00	4925911.99	2865911.99	2026-01-03 00:00:00	\N	\N	3	\N	11	15	raggathon	\N	\N	Paiement fournisseur raggathon - Réception #RCP-20260103-0003	\N	4	2026-01-03 20:00:52	\N
40	4	17	50000.00	2865911.99	2815911.99	2026-01-03 00:00:00	\N	\N	\N	1	11	6	China express logistics be	\N	\N	Paiement transitaire China express logistics be - Réception #RCP-20260103-0003	\N	4	2026-01-03 20:00:52	\N
41	4	17	1716960.00	2815911.99	1098951.99	2026-01-04 00:00:00	\N	\N	3	\N	12	15	raggathon	\N	REF-atay	Paiement fournisseur raggathon - Réception #RCP-20260104-0001	dajkajkjkj	4	2026-01-04 07:49:40	\N
42	4	17	122640.00	1098951.99	976311.99	2026-01-04 00:00:00	\N	\N	\N	1	12	6	China express logistics be	\N	chrisyaiannn	Paiement transitaire China express logistics be - Réception #RCP-20260104-0001	ayytyaetya	4	2026-01-04 07:49:40	\N
43	4	17	660000.00	976311.99	316311.99	2026-01-04 00:00:00	\N	\N	1	\N	13	15	Guangzhou_corpaaa	\N	\N	Paiement fournisseur Guangzhou_corpaaa - Réception #RCP-20260104-0002	\N	4	2026-01-04 07:53:02	\N
44	4	17	95000.00	316311.99	221311.99	2026-01-04 00:00:00	\N	\N	\N	1	13	6	China express logistics be	\N	\N	Paiement transitaire China express logistics be - Réception #RCP-20260104-0002	\N	4	2026-01-04 07:53:03	\N
45	2	17	2400000.00	4500000.00	2100000.00	2026-01-04 00:00:00	\N	\N	3	\N	14	15	raggathon	\N	\N	Paiement fournisseur raggathon - Réception #RCP-20260104-0003	\N	4	2026-01-04 08:08:36	\N
46	2	17	1049000.00	2100000.00	1051000.00	2026-01-02 00:00:00	\N	\N	1	\N	15	15	Guangzhou_corpaaa	\N	\N	Paiement fournisseur Guangzhou_corpaaa - Réception #RCP-20260104-0004	\N	4	2026-01-04 09:31:52	\N
47	4	17	60000.00	221311.99	161311.99	2026-01-03 00:00:00	\N	\N	\N	1	15	6	China express logistics be	\N	\N	Paiement transitaire China express logistics be - Réception #RCP-20260104-0004	\N	4	2026-01-04 09:31:52	\N
48	2	17	132000.00	1051000.00	919000.00	2026-01-04 10:05:30	\N	\N	2	\N	16	15	HERIMANANTSOA Manitriniaina Christian AA	\N	\N	Paiement fournisseur HERIMANANTSOA Manitriniaina Christian AA - Réception #RCP-20260104-0005	\N	4	2026-01-04 10:05:30	\N
49	2	17	144120.00	919000.00	774880.00	2025-12-26 00:00:00	\N	\N	\N	1	16	6	China express logistics be	\N	\N	Paiement transitaire China express logistics be - Réception #RCP-20260104-0005	\N	4	2026-01-04 10:05:30	\N
50	4	17	24000.00	161311.99	137311.99	2026-01-04 10:11:42	\N	\N	2	\N	17	15	HERIMANANTSOA Manitriniaina Christian AA	\N	\N	Paiement fournisseur HERIMANANTSOA Manitriniaina Christian AA - Réception #RCP-20260104-0006	\N	4	2026-01-04 10:11:42	\N
57	2	21	1049000.00	774880.00	1823880.00	2026-01-04 11:26:34	\N	\N	1	\N	15	15	Guangzhou_corpaaa	\N	\N	Annulation: Paiement fournisseur Guangzhou_corpaaa - Réception #RCP-20260104-0004	Annulation de la transaction #46	4	2026-01-04 11:26:34	46
58	4	21	60000.00	137311.99	197311.99	2026-01-04 11:26:34	\N	\N	\N	1	15	6	China express logistics be	\N	\N	Annulation: Paiement transitaire China express logistics be - Réception #RCP-20260104-0004	Annulation de la transaction #47	4	2026-01-04 11:26:34	47
59	2	17	480000.00	1823880.00	1343880.00	2026-01-04 00:00:00	\N	\N	2	\N	18	15	HERIMANANTSOA Manitriniaina Christian AA	\N	RE-20260104-000008	Paiement fournisseur HERIMANANTSOA Manitriniaina Christian AA - Réception #RCP-20260104-0007	\N	4	2026-01-04 12:14:04	\N
60	4	17	143760.00	197311.99	53551.99	2026-01-04 00:00:00	\N	\N	\N	1	18	6	China express logistics be	\N	RE-20260104-000009	Paiement transitaire China express logistics be - Réception #RCP-20260104-0007	\N	4	2026-01-04 12:14:04	\N
61	2	21	480000.00	1343880.00	1823880.00	2026-01-04 12:14:54	\N	\N	2	\N	18	15	HERIMANANTSOA Manitriniaina Christian AA	\N	REV-20260104-000003	Annulation: Paiement fournisseur HERIMANANTSOA Manitriniaina Christian AA - Réception #RCP-20260104-0007	Annulation de la transaction #59	4	2026-01-04 12:14:54	59
62	4	21	143760.00	53551.99	197311.99	2026-01-04 12:14:54	\N	\N	\N	1	18	6	China express logistics be	\N	REV-20260104-000004	Annulation: Paiement transitaire China express logistics be - Réception #RCP-20260104-0007	Annulation de la transaction #60	4	2026-01-04 12:14:54	60
63	4	17	120000.00	197311.99	77311.99	2026-01-03 00:00:00	\N	\N	2	\N	19	15	HERIMANANTSOA Manitriniaina Christian AA	\N	RE-20260103-000008	Paiement fournisseur HERIMANANTSOA Manitriniaina Christian AA - Réception #RCP-20260104-0008	\N	4	2026-01-04 12:20:52	\N
64	4	21	120000.00	77311.99	197311.99	2026-01-04 12:21:42	\N	\N	2	\N	19	15	HERIMANANTSOA Manitriniaina Christian AA	\N	REV-20260104-000005	Annulation: Paiement fournisseur HERIMANANTSOA Manitriniaina Christian AA - Réception #RCP-20260104-0008	Annulation de la transaction #RE-20260103-000008	4	2026-01-04 12:21:42	63
65	2	17	360000.00	1823880.00	1463880.00	2026-01-02 00:00:00	\N	\N	3	\N	20	15	raggathon	\N	RE-20260102-000002	Paiement fournisseur raggathon - Réception #RCP-20260105-0001	\N	4	2026-01-05 13:16:50	\N
66	4	17	144000.00	197311.99	53311.99	2026-01-04 00:00:00	\N	\N	\N	1	20	6	China express logistics be	\N	RE-20260104-000010	Paiement transitaire China express logistics be - Réception #RCP-20260105-0001	\N	4	2026-01-05 13:16:51	\N
67	2	16	45519500.00	1463880.00	46983380.00	2026-01-06 11:06:35	\N	\N	\N	\N	\N	\N	\N	2	VNT-VNT-20260106-0001	Vente #VNT-20260106-0001	\N	4	2026-01-06 11:06:35	\N
69	2	16	10000.00	46983380.00	46993380.00	2026-01-06 18:22:09	\N	\N	\N	\N	\N	\N	\N	6	FAC-20260106-000002	Paiement crédit #VNT-20260106-0002 - Échéance #1	\N	4	2026-01-06 18:22:09	\N
70	2	16	100000.00	46993380.00	47093380.00	2026-01-06 18:46:44	\N	\N	\N	\N	\N	\N	Administrateur	6	FAC-20260106-000003	Paiement crédit #VNT-20260106-0002 - Échéance #1	\N	4	2026-01-06 18:46:44	\N
71	2	16	25000.00	47093380.00	47118380.00	2026-01-06 20:36:44	\N	\N	\N	\N	\N	\N	\N	6	CRD-VNT-20260106-0002	Paiement crédit #VNT-20260106-0002 - Échéance #1	\N	4	2026-01-06 20:36:44	\N
72	3	16	300000.00	70900.00	370900.00	2026-01-07 10:48:05	\N	\N	\N	\N	\N	\N	\N	8	RSV-VNT-20260107-0001	Acompte réservation #VNT-20260107-0001	\N	4	2026-01-07 10:48:05	\N
74	3	16	4517905600.00	370900.00	4518276500.00	2026-01-07 11:22:51	\N	\N	\N	\N	\N	\N	\N	8	FAC-20260107-000002	Paiement final réservation #VNT-20260107-0001	\N	4	2026-01-07 11:22:51	\N
75	3	16	300000.00	4518276500.00	4518576500.00	2026-01-07 11:51:25	\N	\N	\N	\N	\N	\N	\N	9	FAC-20260107-000003	Acompte réservation #VNT-20260107-0002	\N	4	2026-01-07 11:51:25	\N
76	3	16	300000.00	4518576500.00	4518876500.00	2026-01-07 11:52:41	\N	\N	\N	\N	\N	\N	\N	10	FAC-20260107-000004	Acompte réservation #VNT-20260107-0003	\N	4	2026-01-07 11:52:41	\N
77	3	16	108199622.00	4518876500.00	4627076122.00	2026-01-07 15:20:19	\N	\N	\N	\N	\N	\N	\N	11	FAC-20260107-000005	Vente #VNT-20260107-0004	\N	4	2026-01-07 15:20:19	\N
78	3	16	108199622.00	4627076122.00	4735275744.00	2026-01-07 18:47:50	\N	\N	\N	\N	\N	\N	\N	12	FAC-20260107-000006	Vente #VNT-20260107-0005	\N	4	2026-01-07 18:47:50	\N
79	3	16	300000.00	4735275744.00	4735575744.00	2026-01-07 18:49:37	\N	\N	\N	\N	\N	\N	\N	13	FAC-20260107-000007	Acompte réservation #VNT-20260107-0006	\N	4	2026-01-07 18:49:37	\N
80	3	16	179879568.00	4735575744.00	4915455312.00	2026-01-07 19:43:35	\N	\N	\N	\N	\N	\N	\N	15	FAC-20260107-000008	Vente #VNT-20260107-0008	\N	4	2026-01-07 19:43:35	\N
81	3	16	15000.00	4915455312.00	4915470312.00	2026-01-07 20:48:42	\N	\N	\N	\N	\N	\N	\N	16	FAC-20260107-000009	Acompte réservation #VNT-20260107-0009	\N	4	2026-01-07 20:48:42	\N
82	4	17	2160.00	53311.99	51151.99	2026-01-04 00:00:00	\N	\N	3	\N	21	15	raggathon	\N	RE-20260104-000011	Paiement fournisseur raggathon - Réception #RCP-20260107-0001	\N	4	2026-01-07 20:56:15	\N
83	2	17	144000.00	47118380.00	46974380.00	2026-01-04 00:00:00	\N	\N	\N	1	21	6	China express logistics be	\N	RE-20260104-000012	Paiement transitaire China express logistics be - Réception #RCP-20260107-0001	\N	4	2026-01-07 20:56:16	\N
84	2	16	450000000.00	46974380.00	496974380.00	2026-01-08 13:46:16	\N	\N	\N	\N	\N	\N	\N	19	FAC-20260108-000001	Vente #VNT-20260108-0002	\N	4	2026-01-08 13:46:16	\N
85	2	16	8000.00	496974380.00	496982380.00	2026-01-08 14:01:39	\N	\N	\N	\N	\N	\N	\N	20	FAC-20260108-000002	Vente #VNT-20260108-0003	\N	4	2026-01-08 14:01:39	\N
86	3	16	9000.00	4915470312.00	4915479312.00	2026-01-08 21:11:50	\N	\N	\N	\N	\N	\N	\N	6	FAC-20260108-000003	Paiement crédit #VNT-20260106-0002 - Échéance #2	a vous	4	2026-01-08 21:11:50	\N
87	3	16	18000000.00	4915479312.00	4933479312.00	2026-01-08 21:32:40	\N	\N	\N	\N	\N	\N	\N	6	FAC-20260108-000004	Paiement crédit #VNT-20260106-0002 - Échéance #3	a vous	4	2026-01-08 21:32:40	\N
88	3	16	5000.00	4933479312.00	4933484312.00	2026-01-08 21:39:48	\N	\N	\N	\N	\N	\N	\N	6	FAC-20260108-000005	Paiement crédit #VNT-20260106-0002 - Échéance #1	a vous	4	2026-01-08 21:39:48	\N
89	3	16	60000.00	4933484312.00	4933544312.00	2026-01-08 21:40:33	\N	\N	\N	\N	\N	\N	\N	6	FAC-20260108-000006	Paiement crédit #VNT-20260106-0002 - Échéance #1	a vous	4	2026-01-08 21:40:33	\N
90	3	16	30000.00	4933544312.00	4933574312.00	2026-01-09 07:42:24	\N	\N	\N	\N	\N	\N	\N	17	FAC-20260109-000001	Paiement crédit #VNT-20260107-0010 - Échéance #1	mt	4	2026-01-09 07:42:24	\N
91	3	16	200000.00	4933574312.00	4933774312.00	2026-01-09 07:44:08	\N	\N	\N	\N	\N	\N	\N	14	FAC-20260109-000002	Paiement crédit #VNT-20260107-0007 - Échéance #1		4	2026-01-09 07:44:08	\N
92	3	16	9000.00	4933774312.00	4933783312.00	2026-01-09 10:10:31	\N	\N	\N	\N	\N	\N	\N	14	FAC-20260109-000003	Paiement crédit #VNT-20260107-0007 - Échéance #2		4	2026-01-09 10:10:31	\N
93	2	16	3000.00	496982380.00	496985380.00	2026-01-09 10:10:46	\N	\N	\N	\N	\N	\N	\N	14	FAC-20260109-000004	Paiement crédit #VNT-20260107-0007 - Échéance #3		4	2026-01-09 10:10:46	\N
94	2	16	17997000.00	496985380.00	514982380.00	2026-01-09 10:11:07	\N	\N	\N	\N	\N	\N	\N	14	FAC-20260109-000005	Paiement crédit #VNT-20260107-0007 - Échéance #3		4	2026-01-09 10:11:07	\N
95	2	16	127383767.00	514982380.00	642366147.00	2026-01-09 13:46:04	\N	\N	\N	\N	\N	\N	\N	16	FAC-20260109-000006	Paiement final réservation #VNT-20260107-0009	ok	4	2026-01-09 13:46:04	\N
96	2	16	98799622.00	642366147.00	741165769.00	2026-01-09 14:10:35	\N	\N	\N	\N	\N	\N	\N	13	FAC-20260109-000007	Paiement final réservation #VNT-20260107-0006		4	2026-01-09 14:10:35	\N
97	5	15	180000.00	0.00	180000.00	2026-01-09 22:06:46.98726	\N	\N	\N	\N	\N	\N	\N	\N	\N	Solde initial du compte	\N	4	2026-01-09 22:06:46.98726	\N
99	3	18	165000.00	4933783312.00	4933948312.00	2026-01-09 19:24:52	2	98	\N	\N	\N	\N	\N	\N	TRF-20260109-000001	Transfert de compte Caisse Principale	\N	4	2026-01-09 19:24:52	\N
98	2	18	165000.00	741165769.00	741000769.00	2026-01-09 19:24:52	3	99	\N	\N	\N	\N	\N	\N	TRF-20260109-000001	Transfert vers compte MVola Principal	\N	4	2026-01-09 19:24:52	\N
100	3	17	45000.00	4933948312.00	4933903312.00	2026-01-09 00:00:00	\N	\N	\N	\N	\N	16	hahaha	\N	RE-20260109-000001	Dépense mety be à hahaha	\N	4	2026-01-10 10:35:32	\N
101	2	17	50000.00	741000769.00	740950769.00	2026-01-10 00:00:00	\N	\N	\N	\N	\N	10	\N	\N	RE-20260110-000001	Dépense Taxes	\N	4	2026-01-10 10:57:06	\N
102	3	17	56000.00	4933903312.00	4933847312.00	2026-01-10 00:00:00	\N	\N	\N	\N	\N	7	Facebook	\N	RE-20260110-000002	ok	ok	4	2026-01-10 12:50:24	\N
103	4	21	24000.00	51151.99	75151.99	2026-01-10 12:52:39	\N	\N	2	\N	17	15	HERIMANANTSOA Manitriniaina Christian AA	\N	REV-20260110-000001	Annulation: Paiement fournisseur HERIMANANTSOA Manitriniaina Christian AA - Réception #RCP-20260104-0006	Annulation de la transaction #	4	2026-01-10 12:52:39	50
104	2	16	45500000.00	740950769.00	786450769.00	2026-01-10 13:17:00	\N	\N	\N	\N	\N	\N	\N	22	FAC-20260110-000001	Vente #VNT-20260110-0001		4	2026-01-10 13:17:00	\N
105	4	21	1716960.00	75151.99	1792111.99	2026-01-10 14:13:12	\N	\N	3	\N	12	15	raggathon	\N	REV-20260110-000002	Annulation: Paiement fournisseur raggathon - Réception #RCP-20260104-0001	Annulation de la transaction #REF-atay - Original: dajkajkjkj	4	2026-01-10 14:13:12	41
106	4	21	50000.00	1792111.99	1842111.99	2026-01-10 14:14:25	\N	\N	\N	\N	\N	2	JIRAMA	\N	REV-20260110-000003	Annulation: Facture Janvier 2025 - Réf: FACT-001	Annulation de la transaction #EXP-2025-001 - Original: Facture Janvier 2025 - Réf: FACT-001	4	2026-01-10 14:14:25	21
107	2	21	50000.00	786450769.00	786500769.00	2026-01-10 14:40:50	\N	\N	\N	\N	\N	10	\N	\N	REV-20260110-000004	Annulation: Dépense Taxes	Annulation de la transaction #RE-20260110-000001	4	2026-01-10 14:40:50	101
108	3	21	56000.00	4933847312.00	4933903312.00	2026-01-10 14:53:08	\N	\N	\N	\N	\N	7	Facebook	\N	REV-20260110-000005	Annulation: ok	Annulation de la transaction #RE-20260110-000002 - Original: ok	4	2026-01-10 14:53:08	102
109	4	17	550000.00	1842111.99	1292111.99	2026-01-01 00:00:00	\N	\N	3	\N	22	15	raggathon	\N	RE-20260101-000008	Paiement fournisseur raggathon - Réception #RCP-20260110-0001	\N	4	2026-01-10 15:02:03	\N
110	4	17	60000.00	1292111.99	1232111.99	2026-01-02 00:00:00	\N	\N	\N	1	22	6	China express logistics be	\N	RE-20260102-000003	Paiement transitaire China express logistics be - Réception #RCP-20260110-0001	\N	4	2026-01-10 15:02:03	\N
111	2	17	1020000.00	786500769.00	785480769.00	2026-01-11 13:26:05	\N	\N	2	\N	23	15	HERIMANANTSOA Manitriniaina Christian AA	\N	RE-20260111-000001	Paiement fournisseur HERIMANANTSOA Manitriniaina Christian AA - Réception #RCP-20260111-0001	\N	4	2026-01-11 13:26:05	\N
112	2	17	61200.00	785480769.00	785419569.00	2026-01-10 00:00:00	\N	\N	\N	1	23	6	China express logistics be	\N	RE-20260110-000003	Paiement transitaire China express logistics be - Réception #RCP-20260111-0001	\N	4	2026-01-11 13:26:06	\N
113	3	16	540900610.00	4933903312.00	5474803922.00	2026-01-11 13:27:27	\N	\N	\N	\N	\N	\N	\N	23	FAC-20260111-000001	Vente #VNT-20260111-0001		4	2026-01-11 13:27:27	\N
114	3	16	300000.00	5474803922.00	5475103922.00	2026-01-11 13:31:53	\N	\N	\N	\N	\N	\N	\N	25	FAC-20260111-000002	Acompte réservation #VNT-20260111-0003		4	2026-01-11 13:31:53	\N
115	2	16	98799922.00	785419569.00	884219491.00	2026-01-11 13:32:16	\N	\N	\N	\N	\N	\N	\N	25	FAC-20260111-000003	Paiement final réservation #VNT-20260111-0003		4	2026-01-11 13:32:16	\N
116	5	16	306400366.00	180000.00	306580366.00	2026-01-11 13:32:38	\N	\N	\N	\N	\N	\N	\N	24	FAC-20260111-000004	Paiement crédit #VNT-20260111-0002 - Échéance #1		4	2026-01-11 13:32:38	\N
117	3	17	10200.00	5475103922.00	5475093722.00	2026-01-12 20:01:50	\N	\N	2	\N	24	15	HERIMANANTSOA Manitriniaina Christian AA	\N	RE-20260112-000001	Paiement fournisseur HERIMANANTSOA Manitriniaina Christian AA - Réception #RCP-20260112-0001	\N	4	2026-01-12 20:01:50	\N
118	5	17	61200.00	306580366.00	306519166.00	2026-01-12 20:01:50	\N	\N	\N	1	24	6	China express logistics be	\N	RE-20260112-000002	Paiement transitaire China express logistics be - Réception #RCP-20260112-0001	\N	4	2026-01-12 20:01:50	\N
119	2	17	83850.00	884219491.00	884135641.00	2026-01-11 00:00:00	\N	\N	2	\N	25	15	HERIMANANTSOA Manitriniaina Christian AA	\N	RE-20260111-000002	Paiement fournisseur HERIMANANTSOA Manitriniaina Christian AA - Réception #RCP-20260113-0001	\N	4	2026-01-13 07:09:38	\N
120	4	17	7740.00	1232111.99	1224371.99	2026-01-11 00:00:00	\N	\N	\N	1	25	6	China express logistics be	\N	RE-20260111-000003	Paiement transitaire China express logistics be - Réception #RCP-20260113-0001	\N	4	2026-01-13 07:09:38	\N
121	4	17	45900.00	1224371.99	1178471.99	2026-01-13 10:01:15	\N	\N	3	\N	26	15	raggathon	\N	RE-20260113-000001	Paiement fournisseur raggathon - Réception #RCP-20260113-0002	\N	4	2026-01-13 10:01:15	\N
122	4	17	51000.00	1178471.99	1127471.99	2026-01-13 10:01:16	\N	\N	\N	1	26	6	China express logistics be	\N	RE-20260113-000002	Paiement transitaire China express logistics be - Réception #RCP-20260113-0002	\N	4	2026-01-13 10:01:16	\N
\.


--
-- Data for Name: account_types; Type: TABLE DATA; Schema: public; Owner: express_sale_user
--

COPY public.account_types (id, code, name, display_name, description, created_at) FROM stdin;
1	CASH	cash	Espèces	Compte en espèces (liquide)	2025-12-28 11:35:31.68283
2	MOBILE_MONEY	mobile_money	Mobile Money	Compte Mobile Money (MVola, Orange Money, etc.)	2025-12-28 11:35:31.68283
3	BANK	bank	Banque	Compte bancaire	2025-12-28 11:35:31.68283
\.


--
-- Data for Name: accounts; Type: TABLE DATA; Schema: public; Owner: express_sale_user
--

COPY public.accounts (id, account_type_id, name, account_number, initial_balance, current_balance, notes, is_active, created_by, created_at, updated_at) FROM stdin;
3	2	MVola Principal	034 12 345 67	2000000.00	5475093722.00	Compte MVola pour les ventes à paiments mobiles	t	4	2025-12-29 15:17:58.13301	2026-01-12 20:01:50
5	2	Mvola persone	03402112881	180000.00	306519166.00	tsara be	t	4	2026-01-09 22:06:46.98726	2026-01-12 20:01:50
4	3	BNI Madagascar	00001-12345-67890	10000000.00	1127471.99	Compte bancaire principal BNI	t	4	2025-12-29 15:18:31.131506	2026-01-13 10:01:16
2	1	Caisse Principale	\N	5000000.00	884135641.00	Caisse du magasin principal	t	4	2025-12-29 15:11:25.726849	2026-01-13 10:51:15
\.


--
-- Data for Name: attribute_types; Type: TABLE DATA; Schema: public; Owner: express_sale_user
--

COPY public.attribute_types (id, name, display_name, input_type, created_at) FROM stdin;
1	pointure	Pointure	select	2025-12-19 11:51:23.324205
2	taille	Taille	select	2025-12-19 11:51:23.324205
3	couleur	Couleur	color	2025-12-19 11:51:23.324205
4	longueur	Longueur (cm)	number	2025-12-19 11:51:23.324205
5	matiere	Matière	text	2025-12-19 11:51:23.324205
6	size	Pointure	number	2025-12-20 00:25:00.168983
7	material	couleur	text	2025-12-23 15:11:37.588501
8	miz	Épaisseur	number	2025-12-23 17:00:27.130175
9	izy	aaa	number	2025-12-23 18:33:53.318971
10	longue	Longeuea	select	2025-12-24 22:22:50.383255
12	chrichri	christian	text	2025-12-25 22:44:45.201755
15	test	Test	select	2025-12-25 22:57:11.04112
\.


--
-- Data for Name: attribute_values; Type: TABLE DATA; Schema: public; Owner: express_sale_user
--

COPY public.attribute_values (id, attribute_type_id, value, sort_order, created_at) FROM stdin;
1	1	35	1	2025-12-19 11:51:23.326147
2	1	36	2	2025-12-19 11:51:23.326147
3	1	37	3	2025-12-19 11:51:23.326147
4	1	38	4	2025-12-19 11:51:23.326147
5	1	39	5	2025-12-19 11:51:23.326147
6	1	40	6	2025-12-19 11:51:23.326147
7	1	41	7	2025-12-19 11:51:23.326147
8	1	42	8	2025-12-19 11:51:23.326147
9	1	43	9	2025-12-19 11:51:23.326147
10	1	44	10	2025-12-19 11:51:23.326147
11	1	45	11	2025-12-19 11:51:23.326147
12	2	XS	1	2025-12-19 11:51:23.332128
13	2	S	2	2025-12-19 11:51:23.332128
14	2	M	3	2025-12-19 11:51:23.332128
15	2	L	4	2025-12-19 11:51:23.332128
16	2	XL	5	2025-12-19 11:51:23.332128
17	2	XXL	6	2025-12-19 11:51:23.332128
18	2	XXXL	7	2025-12-19 11:51:23.332128
19	10	S	1	2025-12-24 22:22:50.391442
20	10	M	2	2025-12-24 22:22:50.397151
21	10	L	3	2025-12-24 22:22:50.40067
22	8	102	999	2025-12-25 22:40:36.384973
23	8	1000	999	2025-12-25 22:45:21.337768
24	12	christian	999	2025-12-25 22:45:21.347564
25	12	christian a	999	2025-12-25 22:49:17.762948
26	15	red	1	2025-12-25 22:57:11.045695
27	15	christian	2	2025-12-25 22:57:11.049088
28	15	chrichri	3	2025-12-25 22:57:11.053969
29	8	996	999	2025-12-25 23:25:26.900115
30	8	100	999	2025-12-30 14:45:28.264234
31	12	snzk	999	2025-12-30 14:45:28.275864
32	8	120	999	2025-12-30 14:45:53.917094
33	12	n,	999	2025-12-30 14:45:53.927037
34	3	#fb0909	999	2025-12-30 15:14:24.130689
35	3	#32e600	999	2025-12-30 15:15:15.264112
36	8	10	999	2025-12-30 15:16:27.230895
37	4	100	999	2025-12-30 15:16:27.236852
38	4	10	999	2025-12-30 15:16:42.281815
39	3	#e41111	999	2026-01-11 16:24:13.209949
40	4	12	999	2026-01-12 21:52:46.540224
41	6	32	999	2026-01-12 21:52:46.547051
\.


--
-- Data for Name: audit_logs; Type: TABLE DATA; Schema: public; Owner: express_sale_user
--

COPY public.audit_logs (id, user_id, action, table_name, record_id, old_values, new_values, ip_address, user_agent, created_at) FROM stdin;
\.


--
-- Data for Name: cache; Type: TABLE DATA; Schema: public; Owner: express_sale_user
--

COPY public.cache (key, value, expiration) FROM stdin;
\.


--
-- Data for Name: cache_locks; Type: TABLE DATA; Schema: public; Owner: express_sale_user
--

COPY public.cache_locks (key, owner, expiration) FROM stdin;
\.


--
-- Data for Name: categories; Type: TABLE DATA; Schema: public; Owner: express_sale_user
--

COPY public.categories (id, name, description, parent_id, image_url, sort_order, created_at, updated_at) FROM stdin;
1	milay	cat	\N	\N	0	2025-12-19 23:31:40.570355	2025-12-19 23:31:40.570355
2	kely	kely de kely	1	\N	0	2025-12-20 00:27:32.025456	2025-12-20 00:27:32.025456
3	tennis	maivana	\N	\N	0	2025-12-23 13:59:30	2025-12-23 13:59:30
4	tennis	maivana	\N	\N	0	2025-12-23 13:59:32	2025-12-23 13:59:32
5	ioioaoaoa	azertyuiq	\N	http://localhost:8000/storage/uploads/images/2025/12/9e9e6408-b951-42fe-a9ae-c77b9f96b5d1.jpg	0	2025-12-23 14:31:38	2025-12-23 14:31:38
6	ioioaoaoa	azertyuiq	\N	http://localhost:8000/storage/uploads/images/2025/12/9e9e6408-b951-42fe-a9ae-c77b9f96b5d1.jpg	0	2025-12-23 14:31:40	2025-12-23 14:31:40
7	christian	tsara be	\N	http://localhost:8000/storage/uploads/images/2025/12/f181ec7d-3c8b-49cf-9a97-86aebf3e2e8d.jpg	0	2025-12-23 14:59:53	2025-12-23 14:59:53
8	christian	tsara be	\N	http://localhost:8000/storage/uploads/images/2025/12/f181ec7d-3c8b-49cf-9a97-86aebf3e2e8d.jpg	0	2025-12-23 14:59:56	2025-12-23 14:59:56
9	new_eh	kjkdjekjdkjekjfk	\N	http://localhost:8000/storage/uploads/images/2025/12/f6f205b8-ab90-422f-8dd5-86ef58cb31b5.jpeg	0	2025-12-23 15:17:22	2025-12-23 15:17:22
10	tsy new eh	sa	\N	http://localhost:8000/storage/uploads/images/2025/12/4e142452-98cb-4fdf-8717-7777fba3f5c6.jpg	0	2025-12-23 15:17:47	2025-12-23 15:17:47
11	heihei	dzz	\N	\N	0	2025-12-23 15:19:15	2025-12-23 15:19:15
12	Electronique e	hono eh	\N	\N	0	2025-12-23 15:32:42	2025-12-23 15:32:42
13	milay	zdd	12	http://localhost:8000/storage/uploads/images/2025/12/ee4dfd05-6494-4d06-838b-082411b8beb2.jpeg	0	2025-12-23 15:32:58	2025-12-23 15:32:58
14	jhaafa	szdddddddddd	4	http://localhost:8000/storage/uploads/images/2025/12/ccc5d4d2-f78f-4079-86e5-999e676754bf.jpg	0	2025-12-23 15:35:07	2025-12-23 15:35:07
15	dzzd	ada	10	http://localhost:8000/storage/uploads/images/2025/12/5f5c934a-7e78-4b5a-9839-07528d95c576.jpg	0	2025-12-23 18:45:15	2025-12-23 18:45:15
16	hgqgs	szzzz	11	http://localhost:8000/storage/uploads/images/2025/12/a6a67676-6fbe-46df-8faa-78dd3a70d215.jpeg	0	2025-12-23 19:19:02	2025-12-23 19:19:02
17	aaaa	\N	7	\N	0	2025-12-23 19:37:37	2025-12-23 19:37:37
18	pqqq	\N	\N	\N	0	2025-12-24 11:52:46	2025-12-24 11:52:46
19	spq	\N	18	\N	0	2025-12-24 11:52:59	2025-12-24 11:52:59
20	kely be	mihits	1	http://localhost:8000/storage/uploads/images/2026/01/0d4aa2a5-929c-4aae-9316-b288cb123c57.jpg	0	2026-01-01 15:06:23	2026-01-01 15:06:23
21	SZ	DZDD	12	http://localhost/storage/uploads/images/2026/01/14ea29a5-46ca-4bf3-b907-c0ab2cc388b2.png	0	2026-01-13 13:22:37	2026-01-13 13:22:37
\.


--
-- Data for Name: coordinates; Type: TABLE DATA; Schema: public; Owner: express_sale_user
--

COPY public.coordinates (id, country, city, is_active, created_at, updated_at) FROM stdin;
1	Madagascar	Antananarivo	t	2025-12-27 14:50:08	2025-12-27 14:50:08
2	MADAGASCAR	Antananarivo	t	2025-12-27 15:15:24	2025-12-27 15:15:24
3	Madagascar	Antsirabe	t	2025-12-27 15:34:46	2025-12-27 15:34:46
4	Chine	mandeha	t	2025-12-27 15:35:06	2025-12-27 15:35:06
5	Madagascar	Fianarantsoa	t	2025-12-27 17:14:30	2025-12-27 17:14:30
\.


--
-- Data for Name: credit_installments; Type: TABLE DATA; Schema: public; Owner: express_sale_user
--

COPY public.credit_installments (id, credit_id, installment_number, due_date, amount_due, amount_paid, status, paid_date, created_at) FROM stdin;
8	7	1	2026-01-28	3000.00	0.00	pending	\N	2026-01-08 10:52:42
9	7	2	2026-01-29	7000.00	0.00	pending	\N	2026-01-08 10:52:42
2	4	2	2026-03-06	9000.00	9000.00	paid	2026-01-08	2026-01-06 17:45:56
3	4	3	2026-04-06	18000000.00	18000000.00	paid	2026-01-08	2026-01-06 17:45:56
1	4	1	2026-02-06	200000.00	200000.00	paid	2026-01-08	2026-01-06 17:45:56
7	6	1	2026-01-29	89900122.00	30000.00	partial	\N	2026-01-07 20:53:39
4	5	1	2026-02-06	200000.00	200000.00	paid	2026-01-09	2026-01-07 18:51:43
10	8	1	2026-01-31	53700000.00	0.00	pending	\N	2026-01-09 08:09:27
5	5	2	2026-03-06	9000.00	9000.00	paid	2026-01-09	2026-01-07 18:51:43
6	5	3	2026-04-06	18000000.00	18000000.00	paid	2026-01-09	2026-01-07 18:51:43
11	9	1	2026-01-26	306400366.00	306400366.00	paid	2026-01-11	2026-01-11 13:30:56
\.


--
-- Data for Name: credits; Type: TABLE DATA; Schema: public; Owner: express_sale_user
--

COPY public.credits (id, sale_id, customer_id, total_amount, amount_paid, amount_due, credit_date, due_date, status, notes, created_at, updated_at, last_payment_date) FROM stdin;
7	18	2	10000.00	0.00	10000.00	2026-01-08	2026-01-31	active	\N	2026-01-08 10:52:42	2026-01-08 10:52:42	\N
4	6	1	18209000.00	18209000.00	0.00	2026-01-06	2026-04-06	completed	Crédit sur 3 mois - Paiement mensuel	2026-01-06 17:45:56	2026-01-08 21:40:33	2026-01-08 21:40:33
6	17	4	89900122.00	30000.00	89870122.00	2026-01-07	2026-01-31	partial_paid	\N	2026-01-07 20:53:39	2026-01-09 07:42:24	2026-01-09 07:42:24
8	21	1	53700000.00	0.00	53700000.00	2026-01-09	2026-01-31	active	\N	2026-01-09 08:09:27	2026-01-09 08:09:27	\N
5	14	1	18209000.00	18209000.00	0.00	2026-01-07	2026-04-06	completed	Crédit sur 3 mois - Paiement mensuel	2026-01-07 18:51:43	2026-01-09 10:11:07	2026-01-09 10:11:07
9	24	1	306400366.00	306400366.00	0.00	2026-01-11	2026-01-29	completed	\N	2026-01-11 13:30:56	2026-01-11 13:32:38	2026-01-11 13:32:38
\.


--
-- Data for Name: currency_rates; Type: TABLE DATA; Schema: public; Owner: express_sale_user
--

COPY public.currency_rates (id, euro_rate, yuan_rate, dollar_rate, dirham_rate, effective_date, notes, created_by, created_at, updated_at, baht_rate, is_active) FROM stdin;
6	5000.0000	12000.0000	1022.0000	2000.0000	2026-01-28	milay  eh	4	2026-01-01 10:32:48	2026-01-10 17:29:38	12000.0000	f
7	5200.0000	1200.0000	5200.0000	0.1000	2026-01-10	\N	4	2026-01-10 17:05:19	2026-01-10 17:29:38	145.0000	f
5	0.0204	3.1000	0.0201	0.1000	2025-12-28	Taux du 15 janvier 2025	4	2025-12-28 12:07:35	2026-01-10 17:29:38	0.1201	f
8	5100.0000	645.0000	4578.0000	0.1000	2026-01-12	\N	4	2026-01-10 17:29:38	2026-01-10 17:29:38	135.0000	t
\.


--
-- Data for Name: customers; Type: TABLE DATA; Schema: public; Owner: express_sale_user
--

COPY public.customers (id, name, phone, address, reliability_score, loyalty_points, credit_limit, notes, is_active, created_at, updated_at, customer_number) FROM stdin;
3	Vaovao misy	\N	\N	5.00	757285	0.00	[2026-01-07 19:43] +179879 points: Achat de 179879568 Ar\n[2026-01-08 13:46] +450000 points: Achat de 450000000 Ar\n[2026-01-08 14:01] +8 points: Achat de 8000 Ar\n[2026-01-09 13:46] +127398 points: Achat de 127398767 Ar	t	2026-01-07 19:41:03	2026-01-09 16:46:03.99644	CL-20260107-0002
1	Jean Bosco	03412221162	tsy haiko	8.10	4924721	990000000.00	[2026-01-06 11:06] +45519 points: Achat de 45519500 Ar\n[2026-01-06 18:22] Score ajusté de 5.00 à 0.10: Paiement partiel à temps (5%)\n[2026-01-06 18:46] Score ajusté de 0.10 à 0.10: Paiement partiel à temps (50%)\n[2026-01-07 11:22] +4518205 points: Achat de 4518205600 Ar\n[2026-01-07 15:20] +108199 points: Achat de 108199622 Ar\nhelloe\n[2026-01-07 18:47] +108199 points: Achat de 108199622 Ar\n[2026-01-09 14:10] +99099 points: Achat de 99099622 Ar\n[2026-01-10 13:17] +45500 points: Achat de 45500000 Ar	t	2026-01-06 10:05:15	2026-01-10 16:17:00.46734	CL-20260106-0001
5	Jean	\N	\N	5.00	540900	0.00	[2026-01-11 13:27] +540900 points: Achat de 540900610 Ar	t	2026-01-11 13:27:10	2026-01-11 16:27:27.011403	CL-20260111-0001
6	Jean Dupont	\N	\N	5.00	99099	0.00	[2026-01-11 13:32] +99099 points: Achat de 99099922 Ar	t	2026-01-11 13:31:48	2026-01-11 16:32:16.386569	CL-20260111-0002
4	HAFA EH	\N	\N	5.00	0	99999999.99	\N	t	2026-01-07 20:49:48	2026-01-07 23:52:19.401879	CL-20260107-0003
2	HERIMANANTSOA Manitriniaina Christian	+261340425089	Lot 102 ter P Mahity	5.00	0	12311001.00	mety eh	f	2026-01-07 13:55:43	2026-01-08 13:54:00.655242	CL-20260107-0001
\.


--
-- Data for Name: expense_categories; Type: TABLE DATA; Schema: public; Owner: express_sale_user
--

COPY public.expense_categories (id, name, description, icon, is_active, created_at, updated_at) FROM stdin;
2	Électricité	Factures d'électricité	zap	t	2025-12-28 11:35:31.745253	2025-12-29 14:02:35.330082
3	Eau	Factures d'eau	droplet	t	2025-12-28 11:35:31.745253	2025-12-29 14:02:35.330082
4	Internet	Abonnement internet	wifi	t	2025-12-28 11:35:31.745253	2025-12-29 14:02:35.330082
9	Maintenance	Réparations et maintenance	tool	t	2025-12-28 11:35:31.745253	2025-12-29 14:02:35.330082
1	Loyer	Loyer du magasin/entrepôt	building	t	2025-12-28 11:35:31.745253	2025-12-29 11:02:39
5	Salaires	Rémunération du personnel	users	t	2025-12-28 11:35:31.745253	2025-12-29 11:02:39
7	Marketing	Publicité et promotion	megaphone	t	2025-12-28 11:35:31.745253	2025-12-29 11:02:39
8	Fournitures	Fournitures de bureau et magasin	package	t	2025-12-28 11:35:31.745253	2025-12-29 11:02:39
10	Taxes	Impôts et taxes	file-text	t	2025-12-28 11:35:31.745253	2025-12-29 11:02:39
14	Assurance	Assurances diverses	shield	t	2025-12-29 11:02:39	2025-12-29 11:02:39
11	Autres	Autres dépenses non catégorisées	more-horizontal	t	2025-12-28 11:35:31.745253	2025-12-29 11:02:39
6	Transport & Transit	Frais de transport, transit et dédouanement	truck	t	2025-12-28 11:35:31.745253	2025-12-29 11:02:39
15	Approvisionnement	Achat de marchandises auprès des fournisseurs	package	t	2025-12-29 14:35:16.06127	2025-12-29 14:35:16.06127
16	mety be	\N	more-horizontal	t	2026-01-10 10:32:01	2026-01-10 10:32:01
\.


--
-- Data for Name: failed_jobs; Type: TABLE DATA; Schema: public; Owner: express_sale_user
--

COPY public.failed_jobs (id, uuid, connection, queue, payload, exception, failed_at) FROM stdin;
\.


--
-- Data for Name: freight_forwarders; Type: TABLE DATA; Schema: public; Owner: express_sale_user
--

COPY public.freight_forwarders (id, name, logo_url, contact, service_score, notes, is_active, created_at, updated_at, coordinate_id, total_shipments, total_items_shipped, total_items_delivered, service_rating_sum, service_rating_count, total_value_shipped, total_value_delivered, weighted_service_sum, total_weighted_shipment_value) FROM stdin;
1	China express logistics be	http://localhost:8000/storage/uploads/images/2025/12/1fa671bb-aa6d-428a-a60c-ed3b0e9e9155.jpg	+972918991	8.88	dazdkzjkjkj	t	2025-12-27 16:07:00	2026-01-13 08:45:11	1	24	157	107	0.00	0	7871178.02	4916778.02	27955736.46	3149878.01
\.


--
-- Data for Name: installment_transactions; Type: TABLE DATA; Schema: public; Owner: express_sale_user
--

COPY public.installment_transactions (id, installment_id, transaction_id, amount, payment_date, created_at, updated_at) FROM stdin;
2	1	69	10000.00	2026-01-06 18:22:09	2026-01-06 18:22:09	2026-01-06 18:22:09
3	1	70	100000.00	2026-01-06 18:46:44	2026-01-06 18:46:44	2026-01-06 18:46:44
4	1	88	5000.00	2026-01-08 21:39:48	2026-01-08 21:39:48	2026-01-08 21:39:48
5	1	89	60000.00	2026-01-08 21:40:33	2026-01-08 21:40:33	2026-01-08 21:40:33
6	7	90	30000.00	2026-01-09 07:42:24	2026-01-09 07:42:24	2026-01-09 07:42:24
7	4	91	200000.00	2026-01-09 07:44:08	2026-01-09 07:44:08	2026-01-09 07:44:08
8	5	92	9000.00	2026-01-09 10:10:31	2026-01-09 10:10:31	2026-01-09 10:10:31
9	6	93	3000.00	2026-01-09 10:10:46	2026-01-09 10:10:46	2026-01-09 10:10:46
10	6	94	17997000.00	2026-01-09 10:11:07	2026-01-09 10:11:07	2026-01-09 10:11:07
11	11	116	306400366.00	2026-01-11 13:32:38	2026-01-11 13:32:38	2026-01-11 13:32:38
\.


--
-- Data for Name: job_batches; Type: TABLE DATA; Schema: public; Owner: express_sale_user
--

COPY public.job_batches (id, name, total_jobs, pending_jobs, failed_jobs, failed_job_ids, options, cancelled_at, created_at, finished_at) FROM stdin;
\.


--
-- Data for Name: jobs; Type: TABLE DATA; Schema: public; Owner: express_sale_user
--

COPY public.jobs (id, queue, payload, attempts, reserved_at, available_at, created_at) FROM stdin;
\.


--
-- Data for Name: locations; Type: TABLE DATA; Schema: public; Owner: express_sale_user
--

COPY public.locations (id, name, code, warehouse, aisle, shelf, bin, description, capacity, is_active, created_at, updated_at) FROM stdin;
1	Entrepôt A - Allée 1 - Étagère 1	A-01-01	Entrepôt A	Allée 1	Étagère 1	\N	\N	100	t	2025-12-24 16:56:24.112216	2025-12-24 16:56:24.112216
2	Entrepôt A - Allée 1 - Étagère 2	A-01-02	Entrepôt A	Allée 1	Étagère 2	\N	\N	100	t	2025-12-24 16:56:24.112216	2025-12-24 16:56:24.112216
3	Entrepôt A - Allée 2 - Étagère 1	A-02-01	Entrepôt A	Allée 2	Étagère 1	\N	\N	150	t	2025-12-24 16:56:24.112216	2025-12-24 16:56:24.112216
4	Entrepôt B - Allée 1 - Étagère 1	B-01-01	Entrepôt B	Allée 1	Étagère 1	\N	\N	200	t	2025-12-24 16:56:24.112216	2025-12-24 16:56:24.112216
5	Magasin principal	SHOP-01	Magasin	\N	\N	\N	\N	50	t	2025-12-24 16:56:24.112216	2025-12-24 16:56:24.112216
8	Toerana hafa mihitsy eh	Loc A	Entrepôt C	A1	4	1	\N	1000	t	2026-01-05 10:36:38	2026-01-05 10:36:38
\.


--
-- Data for Name: migrations; Type: TABLE DATA; Schema: public; Owner: express_sale_user
--

COPY public.migrations (id, migration, batch) FROM stdin;
1	0001_01_01_000000_create_users_table	1
2	0001_01_01_000001_create_cache_table	1
3	0001_01_01_000002_create_jobs_table	1
4	2025_12_18_184343_create_personal_access_tokens_table	1
5	2025_12_27_122501_create_coordinates_table_and_add_foreign_keys	2
6	2026_01_01_125653_rename_yen_rate_to_yuan_rate_in_currency_rates_table	3
7	2026_01_03_142905_update_stock_receipt_item_ratings_precision	4
8	2026_01_03_143204_update_all_score_columns_precision	5
9	2026_01_04_085120_add_reversed_transaction_id_to_account_transactions_table	6
10	2026_01_04_091017_add_constraint_transaction_types	7
12	2026_01_04_110350_modify_amount_check_constraint_on_account_transactions	8
13	2026_01_05_123606_add_batch_id_to_stock_movements_table	8
15	2026_01_05_195149_add_tracking_columns_to_customers_table	9
16	2026_01_06_160758_create_installment_transactions_table	9
17	2026_01_06_162638_create_reservation_deposits_table	10
18	2026_01_06_170911_remove_customer_tracking_columns	11
19	2026_01_07_163600_customer_add_customer_number	12
20	2026_01_07_173807_product_variant_add_credit_quantity	13
\.


--
-- Data for Name: notifications; Type: TABLE DATA; Schema: public; Owner: express_sale_user
--

COPY public.notifications (id, user_id, type, title, message, reference_type, reference_id, is_read, read_at, created_at) FROM stdin;
\.


--
-- Data for Name: password_reset_tokens; Type: TABLE DATA; Schema: public; Owner: express_sale_user
--

COPY public.password_reset_tokens (username, token, created_at) FROM stdin;
\.


--
-- Data for Name: personal_access_tokens; Type: TABLE DATA; Schema: public; Owner: express_sale_user
--

COPY public.personal_access_tokens (id, tokenable_type, tokenable_id, name, token, abilities, last_used_at, expires_at, created_at, updated_at) FROM stdin;
2	App\\Models\\User	2	express-sale-token	d8a9de02341ed9b1102651e97a91318373a401751aef6f95d5e2ee39f0673158	["*"]	\N	\N	2025-12-19 13:01:47	2025-12-19 13:01:47
41	App\\Models\\User	4	express-sale-token	8cfd1e6646bc6f009b6c81fe1e4017dafe01fdfe52bf1582814c8efb7efef6b3	["*"]	2026-01-13 18:44:34	\N	2026-01-13 18:06:24	2026-01-13 18:44:34
\.


--
-- Data for Name: product_attributes; Type: TABLE DATA; Schema: public; Owner: express_sale_user
--

COPY public.product_attributes (id, product_id, attribute_type_id, is_required, created_at) FROM stdin;
18	17	1	t	2025-12-24 15:44:13.730512
19	17	3	t	2025-12-24 15:44:13.730512
35	18	10	t	2026-01-02 22:01:45.530527
31	18	2	t	2025-12-25 22:30:27.731729
32	18	12	t	2025-12-25 22:44:49.187994
41	22	10	t	2026-01-13 12:59:54.573358
42	22	4	t	2026-01-13 12:59:54.573358
43	23	12	t	2026-01-13 13:12:35.081986
44	23	3	t	2026-01-13 13:12:35.081986
46	24	3	t	2026-01-13 13:28:52.955769
45	24	12	t	2026-01-13 13:28:52.955769
47	26	12	t	2026-01-13 17:09:58.671487
40	21	2	t	2026-01-12 22:07:41.654578
38	20	4	t	2026-01-11 16:21:59.047014
39	20	6	t	2026-01-11 16:21:59.047014
34	11	4	t	2025-12-30 15:16:03.340612
33	11	8	t	2025-12-30 15:16:03.340612
37	19	1	t	2026-01-11 16:20:28.858514
36	19	4	t	2026-01-11 16:20:28.858514
\.


--
-- Data for Name: product_variant_locations; Type: TABLE DATA; Schema: public; Owner: express_sale_user
--

COPY public.product_variant_locations (id, variant_id, location_id, quantity, notes, created_at, updated_at) FROM stdin;
1	8	4	9	Réception RCP-20251230-0002	2025-12-30 13:44:11	2025-12-30 13:44:11
10	9	1	0	Réception RCP-20260104-0002	2026-01-03 15:20:55	2026-01-07 20:48:42
22	11	2	2	Réception RCP-20260101-0001	2026-01-03 20:32:54	2026-01-07 20:53:39
20	7	3	20	Réception RCP-20260101-0001	2026-01-03 20:01:47	2026-01-08 10:52:42
6	9	2	1	Réception RCP-20260101-0006	2026-01-02 18:42:45	2026-01-09 09:13:16
18	9	3	1	Réception RCP-20260103-0003	2026-01-03 20:01:47	2026-01-09 09:13:16
19	8	2	3	Réception RCP-20260103-0003	2026-01-03 20:01:47	2026-01-09 09:13:16
23	8	3	11	Réception RCP-20260101-0001	2026-01-03 20:32:54	2026-01-09 09:13:16
24	6	4	77	Réception RCP-20260101-0001	2026-01-03 20:32:54	2026-01-10 13:17:00
9	10	3	1	Réception RCP-20260101-0006	2026-01-02 20:06:25	2026-01-02 20:06:25
4	11	1	2	Réception RCP-20260101-0002	2026-01-02 17:53:36	2026-01-11 13:27:27
7	9	4	28	Réception RCP-20260105-0001	2026-01-02 20:04:40	2026-01-11 13:27:27
11	12	1	12	Réception RCP-20260101-0002	2026-01-03 15:20:55	2026-01-11 13:31:53
5	10	1	18	Réception RCP-20260104-0002	2026-01-02 18:42:45	2026-01-11 13:31:53
12	7	1	8	Réception RCP-20260103-0002	2026-01-03 15:20:55	2026-01-03 15:20:55
8	6	1	54	Réception RCP-20260101-0005	2026-01-02 20:04:40	2026-01-05 13:09:41
14	10	5	1	Réception RCP-20260103-0001	2026-01-03 18:53:38	2026-01-13 06:48:32
27	10	4	19	Réception RCP-20260105-0001	2026-01-05 13:18:58	2026-01-13 06:48:32
17	12	5	1	Réception RCP-20260103-0001	2026-01-03 18:53:38	2026-01-13 06:48:32
25	12	4	4	\N	2026-01-05 13:09:41	2026-01-13 06:48:32
28	15	1	10	\N	2026-01-13 07:10:40	2026-01-13 07:10:40
29	16	2	10	\N	2026-01-13 07:10:40	2026-01-13 07:10:40
30	14	1	1	\N	2026-01-13 08:44:55	2026-01-13 08:44:56
31	16	1	1	\N	2026-01-13 08:44:56	2026-01-13 08:44:56
16	11	5	5	Réception RCP-20260103-0001	2026-01-03 18:53:38	2026-01-03 18:53:38
26	11	4	6	\N	2026-01-05 13:09:41	2026-01-05 13:09:41
13	8	1	11	Réception RCP-20260103-0002	2026-01-03 15:20:55	2026-01-05 13:13:13
21	6	3	1	Réception RCP-20260103-0003	2026-01-03 20:01:47	2026-01-05 13:39:22
2	6	5	24	Réception RCP-20251230-0002	2025-12-30 13:44:11	2026-01-05 13:39:22
15	9	5	8	Réception RCP-20260103-0001	2026-01-03 18:53:38	2026-01-05 13:39:22
3	12	2	1	Réception RCP-20260101-0001	2026-01-02 17:53:36	2026-01-05 14:11:30
\.


--
-- Data for Name: product_variants; Type: TABLE DATA; Schema: public; Owner: express_sale_user
--

COPY public.product_variants (id, product_id, sku, price_adjustment, stock_quantity, reserved_quantity, low_stock_threshold, is_active, created_at, updated_at, image_path, credit_quantity) FROM stdin;
7	18	KIR--8FU7	0.00	28	0	10	t	2025-12-30 11:45:28	2026-01-08 13:52:42.545666	http://localhost:8000/storage/uploads/images/2025/12/e11afae2-14a6-496a-b59a-f5603b555881.jpg	0
8	18	KIR--AGLN	0.00	34	0	100	t	2025-12-30 11:45:53	2026-01-09 12:13:16.004483	http://localhost:8000/storage/uploads/images/2026/01/2088a6eb-1301-4f50-b5b8-e4fd5a9c7029.png	0
10	17	TEN--SWDH	0.00	39	0	10	t	2025-12-30 12:15:15	2026-01-13 09:48:32.387494	http://localhost:8000/storage/uploads/images/2025/12/f6a7b68e-ae00-4fb2-a26f-8744f680e48f.jpg	-4
6	18	KIR--KHWS	100.00	156	0	100	t	2025-12-25 19:29:53	2026-01-10 16:17:00.46734	http://localhost:8000/storage/uploads/images/2025/12/caa1d2be-85ba-4d73-bc25-984caa82e707.jpg	-10
13	17	TEN--XLSX	0.00	0	0	18	t	2026-01-11 13:24:13	2026-01-11 13:24:13	http://localhost:8000/storage/uploads/images/2026/01/9d90ca76-b242-4c80-9043-15bb1ac9668a.png	0
11	11	PRO--6CPP	0.00	15	0	10	t	2025-12-30 12:16:27	2026-01-11 16:27:27.011403	http://localhost:8000/storage/uploads/images/2025/12/cb39284f-c5f3-42e1-b233-9f061189e37a.jpg	0
9	17	TEN--UZV4	0.00	38	0	10	t	2025-12-30 12:14:24	2026-01-11 16:27:27.011403	\N	-4
12	11	PRO--EXQL	0.00	18	0	5	t	2025-12-30 12:16:42	2026-01-13 09:48:32.387494	\N	-3
15	21	KAY--FTGW	0.00	10	0	5	t	2026-01-12 19:08:13	2026-01-13 10:10:40.728893	http://localhost:8000/storage/uploads/images/2026/01/914bdc77-6b13-4e77-ab7f-cfd732849979.png	0
14	20	EXA--SPYO	0.00	1	0	5	t	2026-01-12 18:52:46	2026-01-13 11:44:55.983697	http://localhost:8000/storage/uploads/images/2026/01/048129ba-5b5c-4fd0-b3f6-874d714d0de8.png	0
16	19	MIL--QNWW	0.00	11	0	5	t	2026-01-12 19:29:25	2026-01-13 11:44:55.983697	http://localhost:8000/storage/uploads/images/2026/01/2696fc80-d09b-444a-9230-be4cba186503.png	0
\.


--
-- Data for Name: products; Type: TABLE DATA; Schema: public; Owner: express_sale_user
--

COPY public.products (id, name, description, category_id, subcategory_id, base_price, is_active, created_at, updated_at, image_url) FROM stdin;
20	examenworkflow	\N	10	15	900000.00	t	2026-01-11 13:21:59	2026-01-13 17:25:03.071889	uploads/images/2026/01/a92b4dd8-4e12-4b7e-85b2-8e7553ff0001.png
11	Produits mail	addz	12	13	90000122.00	t	2025-12-23 19:19:30	2026-01-13 17:40:27.031564	uploads/images/2026/01/2135695e-2bc4-4de2-bc31-5cca55dfac2f.png
19	Milay be le produits	Tena tsara eh	7	17	100000.00	t	2026-01-11 13:20:28	2026-01-13 17:40:47.487484	uploads/images/2026/01/1bef3f12-554e-473a-83ea-5f954b3a3eb9.png
18	KIraro tsara be	kiraro mihaja  be	12	13	2000.00	t	2025-12-24 19:23:48	2026-01-13 17:07:25.362252	\N
17	Tennis manja	myproduct	11	16	9100000.00	t	2025-12-24 12:25:41	2026-01-13 17:07:25.362252	\N
22	mialy eu	dz	12	\N	1000.00	t	2026-01-13 12:59:54	2026-01-13 17:07:25.362252	uploads/images/2026/01/afca5c7f-7e6e-4967-8ec0-50356b9b8c14.png
23	hello	\N	1	2	9011.00	t	2026-01-13 13:12:35	2026-01-13 17:07:25.362252	uploads/images/2026/01/67d95775-57ab-4c18-93c0-ad2dc22b532a.png
24	Tennis manja	e	4	\N	122.00	t	2026-01-13 13:28:52	2026-01-13 17:07:25.362252	uploads/images/2026/01/9c8c7d5f-517c-479b-a32e-8e7727bbca10.png
26	manaja eh	ded	4	14	19010.00	t	2026-01-13 17:09:58	2026-01-13 17:22:20.518384	uploads/images/2026/01/2fb3c5a0-6d50-4909-ba2e-ee0f23b7ade8.png
25	stan	djzhjd	11	16	1900000.00	t	2026-01-13 16:17:24	2026-01-13 17:22:33.361646	uploads/images/2026/01/2541a5a0-b00a-4ed2-a8e2-6a6246846cf5.png
21	kay kay	mety kosa	10	15	9000.00	t	2026-01-12 19:07:41	2026-01-13 17:24:48.642348	uploads/images/2026/01/a3f03975-e741-4d78-a246-2e33c076b3e2.png
\.


--
-- Data for Name: reservation_deposits; Type: TABLE DATA; Schema: public; Owner: express_sale_user
--

COPY public.reservation_deposits (id, reservation_id, transaction_id, amount, payment_date, notes, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: reservations; Type: TABLE DATA; Schema: public; Owner: express_sale_user
--

COPY public.reservations (id, sale_id, customer_id, reservation_date, expiry_date, total_amount, deposit_amount, remaining_amount, status, cancellation_reason, completed_at, created_at, updated_at) FROM stdin;
2	8	1	2026-01-07 10:48:05	2026-01-25 00:00:00	4518205600.00	4518205600.00	0.00	completed	\N	2026-01-07 11:22:51	2026-01-07 10:48:05	2026-01-07 11:22:51
4	10	1	2026-01-07 11:52:41	2026-01-25 00:00:00	99099622.00	300000.00	98799622.00	cancelled	tsy mety eh	\N	2026-01-07 11:52:41	2026-01-07 12:01:00
6	16	3	2026-01-07 20:48:42	2026-01-29 00:00:00	127398767.00	127398767.00	0.00	completed	\N	2026-01-09 13:46:04	2026-01-07 20:48:42	2026-01-09 13:46:04
5	13	1	2026-01-07 18:49:37	2026-01-25 00:00:00	99099622.00	99099622.00	0.00	completed	\N	2026-01-09 14:10:35	2026-01-07 18:49:37	2026-01-09 14:10:35
3	9	1	2026-01-07 11:51:25	2026-01-25 00:00:00	99099622.00	300000.00	98799622.00	cancelled	tena tsy clé	\N	2026-01-07 11:51:25	2026-01-09 15:00:33
7	25	6	2026-01-11 13:31:53	2026-01-27 00:00:00	99099922.00	99099922.00	0.00	completed	\N	2026-01-11 13:32:16	2026-01-11 13:31:53	2026-01-11 13:32:16
\.


--
-- Data for Name: sale_items; Type: TABLE DATA; Schema: public; Owner: express_sale_user
--

COPY public.sale_items (id, sale_id, variant_id, quantity, unit_price, subtotal, created_at) FROM stdin;
1	2	6	10	2000.00	20000.00	2026-01-06 14:06:34.969011
2	2	9	5	9100000.00	45500000.00	2026-01-06 14:06:34.969011
9	6	6	5	2000.00	10000.00	2026-01-06 20:45:55.983519
10	6	9	2	9100000.00	18200000.00	2026-01-06 20:45:55.983519
13	8	9	2	9100000.00	18200000.00	2026-01-07 13:48:05.018676
14	8	11	50	90000122.00	4500006100.00	2026-01-07 13:48:05.018676
15	9	9	1	9100000.00	9100000.00	2026-01-07 14:51:25.707653
16	9	11	1	90000122.00	90000122.00	2026-01-07 14:51:25.707653
17	10	9	1	9100000.00	9100000.00	2026-01-07 14:52:40.973285
18	10	11	1	90000122.00	90000122.00	2026-01-07 14:52:40.973285
19	11	9	2	9100000.00	18200000.00	2026-01-07 18:20:19.117714
20	11	11	1	90000122.00	90000122.00	2026-01-07 18:20:19.117714
21	12	9	2	9100000.00	18200000.00	2026-01-07 21:47:50.554646
22	12	11	1	90000122.00	90000122.00	2026-01-07 21:47:50.554646
23	13	9	1	9100000.00	9100000.00	2026-01-07 21:49:36.916896
24	13	11	1	90000122.00	90000122.00	2026-01-07 21:49:36.916896
25	14	6	5	2000.00	10000.00	2026-01-07 21:51:43.348266
26	14	9	2	9100000.00	18200000.00	2026-01-07 21:51:43.348266
27	15	8	1	2000.00	2000.00	2026-01-07 22:43:35.50381
28	15	11	2	90000122.00	180000244.00	2026-01-07 22:43:35.50381
29	16	9	14	9100000.00	127400000.00	2026-01-07 23:48:42.496431
30	17	11	1	90000122.00	90000122.00	2026-01-07 23:53:39.583266
31	18	7	5	2000.00	10000.00	2026-01-08 13:52:42.545666
32	19	9	50	9100000.00	455000000.00	2026-01-08 16:46:16.37453
33	20	6	4	2000.00	8000.00	2026-01-08 17:01:39.631749
34	21	9	7	9100000.00	63700000.00	2026-01-09 11:09:27.358562
35	22	10	4	9100000.00	36400000.00	2026-01-10 16:17:00.46734
36	22	6	4	2000.00	8000.00	2026-01-10 16:17:00.46734
37	22	10	1	9100000.00	9100000.00	2026-01-10 16:17:00.46734
38	23	11	5	90000122.00	450000610.00	2026-01-11 16:27:27.011403
39	23	9	10	9100000.00	91000000.00	2026-01-11 16:27:27.011403
40	24	10	4	9100000.00	36400000.00	2026-01-11 16:30:56.634279
41	24	12	3	90000122.00	270000366.00	2026-01-11 16:30:56.634279
42	25	12	1	90000122.00	90000122.00	2026-01-11 16:31:53.803127
43	25	10	1	9100000.00	9100000.00	2026-01-11 16:31:53.803127
\.


--
-- Data for Name: sales; Type: TABLE DATA; Schema: public; Owner: express_sale_user
--

COPY public.sales (id, sale_number, customer_id, user_id, sale_date, sale_type, subtotal, discount_amount, discount_reason, total_amount, payment_status, notes, created_at, updated_at, payment_method) FROM stdin;
2	VNT-20260106-0001	1	4	2026-01-06 08:06:34	immediate	45520000.00	500.00	Client fidèle	45519500.00	paid	Vente effectuée en boutique	2026-01-06 11:06:34	2026-01-08 14:00:23.391369	cash
8	VNT-20260107-0001	1	4	2026-01-07 07:48:05	reservation	4518206100.00	500.00	Réservation anticipée	4518205600.00	paid	Réservation pour mariage - Client viendra chercher le 25 janvier	2026-01-07 10:48:05	2026-01-08 14:00:23.391369	mobile_money
10	VNT-20260107-0003	1	4	2026-01-07 08:52:40	reservation	99100122.00	500.00	Réservation anticipée	99099622.00	cancelled	Réservation pour mariage - Client viendra chercher le 25 janvier	2026-01-07 11:52:40	2026-01-08 14:00:23.391369	mobile_money
11	VNT-20260107-0004	1	4	2026-01-07 12:20:19	immediate	108200122.00	500.00	anticipée	108199622.00	paid	mety tsara	2026-01-07 15:20:19	2026-01-08 14:00:23.391369	mobile_money
12	VNT-20260107-0005	1	4	2026-01-07 15:47:50	immediate	108200122.00	500.00	anticipée	108199622.00	paid	mety tsara	2026-01-07 18:47:50	2026-01-08 14:00:23.391369	mobile_money
15	VNT-20260107-0008	3	4	2026-01-07 16:43:35	immediate	180002244.00	122676.00	\N	179879568.00	paid	\N	2026-01-07 19:43:35	2026-01-08 14:00:23.391369	mobile_money
18	VNT-20260108-0001	2	4	2026-01-08 07:52:42	credit	10000.00	0.00	\N	10000.00	pending	\N	2026-01-08 10:52:42	2026-01-08 14:00:23.391369	\N
19	VNT-20260108-0002	3	4	2026-01-08 10:46:16	immediate	455000000.00	5000000.00	\N	450000000.00	paid	\N	2026-01-08 13:46:16	2026-01-08 14:00:23.391369	cash
20	VNT-20260108-0003	3	4	2026-01-08 14:01:39	immediate	8000.00	0.00	\N	8000.00	paid	\N	2026-01-08 14:01:39	2026-01-08 14:01:39	cash
6	VNT-20260106-0002	1	4	2026-01-06 14:45:56	credit	18210000.00	1000.00	Client fidèle - 3ème achat	18209000.00	paid	Crédit sur 3 mois - Paiement mensuel	2026-01-06 17:45:56	2026-01-09 00:40:33.827543	\N
17	VNT-20260107-0010	4	4	2026-01-07 17:53:39	credit	90000122.00	100000.00	\N	89900122.00	partial	\N	2026-01-07 20:53:39	2026-01-09 10:42:24.295263	\N
21	VNT-20260109-0001	1	4	2026-01-09 08:09:27	credit	63700000.00	10000000.00	\N	53700000.00	pending	\N	2026-01-09 08:09:27	2026-01-09 08:09:27	\N
14	VNT-20260107-0007	1	4	2026-01-07 15:51:43	credit	18210000.00	1000.00	Client fidèle - 3ème achat	18209000.00	paid	Crédit sur 3 mois - Paiement mensuel	2026-01-07 18:51:43	2026-01-09 13:11:07.590822	\N
16	VNT-20260107-0009	3	4	2026-01-07 17:48:42	reservation	127400000.00	1233.00	\N	127398767.00	paid	\N	2026-01-07 20:48:42	2026-01-09 16:46:03.99644	mobile_money
13	VNT-20260107-0006	1	4	2026-01-07 15:49:36	reservation	99100122.00	500.00	Réservation anticipée	99099622.00	paid	Réservation pour mariage - Client viendra chercher le 25 janvier	2026-01-07 18:49:36	2026-01-09 17:10:35.315122	mobile_money
9	VNT-20260107-0002	1	4	2026-01-07 08:51:25	reservation	99100122.00	500.00	Réservation anticipée	99099622.00	cancelled	Réservation pour mariage - Client viendra chercher le 25 janvier	2026-01-07 11:51:25	2026-01-09 18:00:33.257091	mobile_money
22	VNT-20260110-0001	1	4	2026-01-10 13:17:00	immediate	45508000.00	8000.00	\N	45500000.00	paid	\N	2026-01-10 13:17:00	2026-01-10 13:17:00	cash
23	VNT-20260111-0001	5	4	2026-01-11 13:27:27	immediate	541000610.00	100000.00	\N	540900610.00	paid	\N	2026-01-11 13:27:27	2026-01-11 13:27:27	mobile_money
25	VNT-20260111-0003	6	4	2026-01-11 13:31:53	reservation	99100122.00	200.00	\N	99099922.00	paid	\N	2026-01-11 13:31:53	2026-01-11 16:32:16.386569	mobile_money
24	VNT-20260111-0002	1	4	2026-01-11 13:30:56	credit	306400366.00	0.00	\N	306400366.00	paid	\N	2026-01-11 13:30:56	2026-01-11 16:32:38.275593	\N
\.


--
-- Data for Name: sessions; Type: TABLE DATA; Schema: public; Owner: express_sale_user
--

COPY public.sessions (id, user_id, ip_address, user_agent, payload, last_activity) FROM stdin;
vVmpFxbqYVHRPK9eC5d0w4rBaXmEHDW8Smq3cZ5x	\N	127.0.0.1	PostmanRuntime/7.49.1	YTozOntzOjY6Il90b2tlbiI7czo0MDoiRlVXM1Bob01nQUp6eGp0VzVKYU1rbXhnQ2ZDQWRvWm5JNGphM3lXZyI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjE6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMCI7czo1OiJyb3V0ZSI7Tjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==	1766089185
viawJtmGKrx1Pp8J1Yjeu6OX5TnljmGByMMOe9K9	\N	127.0.0.1	PostmanRuntime/7.49.1	YTozOntzOjY6Il90b2tlbiI7czo0MDoieUlYVVZtR1VlU3d0Y2R5RDFYZ09tTjFlamhUdTZWSGxZWlJLb2Q0SCI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjE6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMCI7czo1OiJyb3V0ZSI7Tjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==	1766133920
poOKGY614sW6xO3f6gwjE4AYk7MI5DVJaSZeOrzL	\N	127.0.0.1	PostmanRuntime/7.51.0	YTozOntzOjY6Il90b2tlbiI7czo0MDoicHEwenVnV1hWVllCdWxqbWdQYTBkSmpzbFBYUjZxM1U5Vk8xalRhWCI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjE6Imh0dHA6Ly9sb2NhbGhvc3Q6ODAwMCI7czo1OiJyb3V0ZSI7Tjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==	1767696450
K2x0RJTCDXoQpYjyZr2E4NANB1WRPbEqaltXveU6	\N	127.0.0.1	PostmanRuntime/7.51.0	YTozOntzOjY6Il90b2tlbiI7czo0MDoiSFpiZEt1amNwZW00MU00dWVtNlFUd25HNXp5NnRyMmRlM2lCWmNtRSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjE6Imh0dHA6Ly9sb2NhbGhvc3Q6ODAwMCI7czo1OiJyb3V0ZSI7Tjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==	1767786983
DPy7QiYfD57A1LT4bSlg4cVi1p1icU94BKWhzgcA	\N	127.0.0.1	PostmanRuntime/7.51.0	YTozOntzOjY6Il90b2tlbiI7czo0MDoiT3BSR09kQ2ZVR2FsZmdUek05dFdzYTRkd2FHOHlSNVl2VVhrbjg4NSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjE6Imh0dHA6Ly9sb2NhbGhvc3Q6ODAwMCI7czo1OiJyb3V0ZSI7Tjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==	1767810476
\.


--
-- Data for Name: stock_movements; Type: TABLE DATA; Schema: public; Owner: express_sale_user
--

COPY public.stock_movements (id, variant_id, from_location_id, to_location_id, quantity, movement_type, sale_id, stock_receipt_id, performed_by, reason, notes, created_at, batch_id) FROM stdin;
1	11	2	1	5	transfer	\N	\N	4	tsy misy	mety eh	2026-01-05 09:07:21	\N
2	12	1	4	3	transfer	\N	\N	4	\N	\N	2026-01-05 13:09:41	TRF-20260105-130941-b9Np1D
3	6	1	4	5	transfer	\N	\N	4	\N	\N	2026-01-05 13:09:41	TRF-20260105-130941-b9Np1D
4	11	1	4	6	transfer	\N	\N	4	\N	\N	2026-01-05 13:09:41	TRF-20260105-130941-b9Np1D
5	8	2	1	1	transfer	\N	\N	4	\N	\N	2026-01-05 13:13:13	TRF-20260105-131313-2WaBeG
6	10	\N	4	10	receipt	\N	16	4	Réception RCP-20260104-0005	\N	2026-01-05 13:36:28	RCP-RCP-20260104-0005-XAfG
7	9	\N	4	1	receipt	\N	16	4	Réception RCP-20260104-0005	\N	2026-01-05 13:36:28	RCP-RCP-20260104-0005-XAfG
8	6	3	5	4	transfer	\N	\N	4	\N	\N	2026-01-05 13:39:22	TRF-20260105-133922-1WZv7f
9	9	3	5	4	transfer	\N	\N	4	\N	\N	2026-01-05 13:39:22	TRF-20260105-133922-1WZv7f
10	12	2	\N	30	adjustment	\N	\N	4	Inventaire physique	\N	2026-01-05 14:11:30	\N
11	6	4	\N	10	sale	2	\N	4	\N	Vente #2	2026-01-06 11:06:35	\N
12	9	4	\N	5	sale	2	\N	4	\N	Vente #2	2026-01-06 11:06:35	\N
19	6	4	\N	5	sale	6	\N	4	\N	Vente #6	2026-01-06 17:45:56	\N
20	9	4	\N	2	sale	6	\N	4	\N	Vente #6	2026-01-06 17:45:56	\N
23	9	4	\N	2	reservation	8	\N	4	reservation	Réservation vente #8	2026-01-07 10:48:05	\N
24	11	1	\N	50	reservation	8	\N	4	reservation	Réservation vente #8	2026-01-07 10:48:05	\N
26	9	4	\N	2	sale	8	\N	4	reservation_completed	Réservation #8 complétée - stock libéré	2026-01-07 11:22:51	\N
27	11	1	\N	50	sale	8	\N	4	reservation_completed	Réservation #8 complétée - stock libéré	2026-01-07 11:22:51	\N
28	9	4	\N	1	reservation	9	\N	4	reservation	Réservation vente #9	2026-01-07 11:51:25	\N
29	11	1	\N	1	reservation	9	\N	4	reservation	Réservation vente #9	2026-01-07 11:51:25	\N
30	9	4	\N	1	reservation	10	\N	4	reservation	Réservation vente #10	2026-01-07 11:52:40	\N
31	11	1	\N	1	reservation	10	\N	4	reservation	Réservation vente #10	2026-01-07 11:52:41	\N
34	9	\N	4	1	return	10	\N	4	reservation_cancelled	Réservation #10 annulée - stock libéré	2026-01-07 12:01:00	\N
35	11	\N	1	1	return	10	\N	4	reservation_cancelled	Réservation #10 annulée - stock libéré	2026-01-07 12:01:00	\N
36	9	4	\N	2	sale	11	\N	4	\N	Vente #11	2026-01-07 15:20:19	\N
37	11	1	\N	1	sale	11	\N	4	\N	Vente #11	2026-01-07 15:20:19	\N
38	9	4	\N	2	sale	12	\N	4	\N	Vente #12	2026-01-07 18:47:50	\N
39	11	1	\N	1	sale	12	\N	4	\N	Vente #12	2026-01-07 18:47:50	\N
40	9	4	\N	1	reservation	13	\N	4	reservation	Réservation vente #13	2026-01-07 18:49:36	\N
41	11	1	\N	1	reservation	13	\N	4	reservation	Réservation vente #13	2026-01-07 18:49:37	\N
42	6	4	\N	5	sale	14	\N	4	\N	Vente #14	2026-01-07 18:51:43	\N
43	9	4	\N	2	sale	14	\N	4	\N	Vente #14	2026-01-07 18:51:43	\N
44	8	3	\N	1	sale	15	\N	4	\N	Vente #15	2026-01-07 19:43:35	\N
45	11	2	\N	2	sale	15	\N	4	\N	Vente #15	2026-01-07 19:43:35	\N
46	9	1	\N	14	reservation	16	\N	4	reservation	Réservation vente #16	2026-01-07 20:48:42	\N
47	11	2	\N	1	sale	17	\N	4	\N	Vente #17	2026-01-07 20:53:39	\N
48	7	3	\N	5	sale	18	\N	4	\N	Vente #18	2026-01-08 10:52:42	\N
49	9	4	\N	50	sale	19	\N	4	\N	Vente #19	2026-01-08 13:46:16	\N
50	6	4	\N	4	sale	20	\N	4	\N	Vente #20	2026-01-08 14:01:39	\N
51	9	4	\N	7	sale	21	\N	4	\N	Vente #21	2026-01-09 08:09:27	\N
52	9	2	3	1	transfer	\N	\N	4	\N	\N	2026-01-09 09:13:16	TRF-20260109-091316-F0zuhJ
53	8	2	3	1	transfer	\N	\N	4	\N	\N	2026-01-09 09:13:16	TRF-20260109-091316-F0zuhJ
54	9	1	\N	14	sale	16	\N	4	reservation_completed	Réservation #16 complétée - stock libéré	2026-01-09 13:46:04	\N
55	9	4	\N	1	sale	13	\N	4	reservation_completed	Réservation #13 complétée - stock libéré	2026-01-09 14:10:35	\N
56	11	1	\N	1	sale	13	\N	4	reservation_completed	Réservation #13 complétée - stock libéré	2026-01-09 14:10:35	\N
57	9	\N	4	1	return	9	\N	4	reservation_cancelled	Réservation #9 annulée - stock libéré	2026-01-09 15:00:33	\N
58	11	\N	1	1	return	9	\N	4	reservation_cancelled	Réservation #9 annulée - stock libéré	2026-01-09 15:00:33	\N
59	10	1	\N	4	sale	22	\N	4	\N	Vente #22	2026-01-10 13:17:00	\N
60	6	4	\N	4	sale	22	\N	4	\N	Vente #22	2026-01-10 13:17:00	\N
61	10	5	\N	1	sale	22	\N	4	\N	Vente #22	2026-01-10 13:17:00	\N
62	11	1	\N	5	sale	23	\N	4	\N	Vente #23	2026-01-11 13:27:27	\N
63	9	4	\N	10	sale	23	\N	4	\N	Vente #23	2026-01-11 13:27:27	\N
64	10	4	\N	4	sale	24	\N	4	\N	Vente #24	2026-01-11 13:30:56	\N
65	12	4	\N	3	sale	24	\N	4	\N	Vente #24	2026-01-11 13:30:56	\N
66	12	1	\N	1	reservation	25	\N	4	reservation	Réservation vente #25	2026-01-11 13:31:53	\N
67	10	1	\N	1	reservation	25	\N	4	reservation	Réservation vente #25	2026-01-11 13:31:53	\N
68	12	1	\N	1	sale	25	\N	4	reservation_completed	Réservation #25 complétée - stock libéré	2026-01-11 13:32:16	\N
69	10	1	\N	1	sale	25	\N	4	reservation_completed	Réservation #25 complétée - stock libéré	2026-01-11 13:32:16	\N
70	10	5	4	3	transfer	\N	\N	4	\N	\N	2026-01-13 06:48:32	TRF-20260113-064832-fzl5SC
71	12	5	4	4	transfer	\N	\N	4	\N	\N	2026-01-13 06:48:32	TRF-20260113-064832-fzl5SC
72	15	\N	1	10	receipt	\N	25	4	Réception RCP-20260113-0001	\N	2026-01-13 07:10:40	RCP-RCP-20260113-0001-mlz3
73	16	\N	2	10	receipt	\N	25	4	Réception RCP-20260113-0001	\N	2026-01-13 07:10:40	RCP-RCP-20260113-0001-mlz3
74	14	\N	1	1	receipt	\N	24	4	Réception RCP-20260112-0001	\N	2026-01-13 08:44:56	RCP-RCP-20260112-0001-tuj1
75	16	\N	1	1	receipt	\N	24	4	Réception RCP-20260112-0001	\N	2026-01-13 08:44:56	RCP-RCP-20260112-0001-tuj1
\.


--
-- Data for Name: stock_receipt_item_ratings; Type: TABLE DATA; Schema: public; Owner: express_sale_user
--

COPY public.stock_receipt_item_ratings (id, stock_receipt_item_id, attribute_type_id, attribute_conformity_rating, quality_rating, quality_notes, rated_by, created_at) FROM stdin;
1	3	8	8.50	9.00	Couleur parfaitement conforme au cahier des charges	4	2025-12-30 14:16:33
2	3	2	7.00	8.00	Taille légèrement inférieure mais acceptable	4	2025-12-30 14:16:33
3	3	12	9.50	9.50	Matériau excellent, très bonne qualité	4	2025-12-30 14:16:33
4	3	\N	5.00	8.50	Qualité générale très satisfaisante	4	2025-12-30 14:16:33
5	14	\N	4.00	6.00	\N	4	2026-01-02 17:54:15
6	15	\N	4.00	4.00	\N	4	2026-01-02 17:54:15
7	14	\N	4.00	6.00	\N	4	2026-01-02 17:54:43
8	15	\N	4.00	4.00	\N	4	2026-01-02 17:54:43
9	14	\N	4.00	6.00	\N	4	2026-01-02 17:55:14
10	15	\N	4.00	4.00	\N	4	2026-01-02 17:55:14
11	14	\N	8.00	8.00	\N	4	2026-01-02 17:58:08
12	15	\N	8.00	8.00	\N	4	2026-01-02 17:58:08
13	12	\N	8.00	8.00	\N	4	2026-01-02 18:43:13
14	13	\N	8.00	8.00	\N	4	2026-01-02 18:43:13
15	12	\N	9.00	9.00	\N	4	2026-01-03 14:14:50
16	13	\N	9.00	9.00	\N	4	2026-01-03 14:14:50
17	12	\N	9.00	9.00	\N	4	2026-01-03 14:23:23
18	13	\N	9.00	9.00	\N	4	2026-01-03 14:23:23
19	12	\N	8.00	8.00	\N	4	2026-01-03 14:24:21
20	13	\N	8.00	8.00	\N	4	2026-01-03 14:24:21
21	12	\N	8.00	8.00	\N	4	2026-01-03 14:24:29
22	13	\N	8.00	8.00	\N	4	2026-01-03 14:24:29
23	12	\N	8.00	8.00	\N	4	2026-01-03 14:25:37
24	13	\N	8.00	8.00	\N	4	2026-01-03 14:25:37
25	12	\N	8.00	8.00	\N	4	2026-01-03 14:30:27
26	13	\N	8.00	8.00	\N	4	2026-01-03 14:30:27
27	12	\N	8.00	8.00	\N	4	2026-01-03 14:34:45
28	13	\N	8.00	8.00	\N	4	2026-01-03 14:34:46
29	25	\N	7.00	7.00	\N	4	2026-01-03 15:24:10
30	26	\N	7.00	7.00	\N	4	2026-01-03 15:24:11
31	27	\N	7.00	7.00	\N	4	2026-01-03 15:24:11
32	28	\N	7.00	7.00	\N	4	2026-01-03 15:24:11
33	29	\N	7.00	7.00	\N	4	2026-01-03 15:24:12
34	30	\N	7.00	7.00	\N	4	2026-01-03 15:24:12
35	31	\N	8.00	7.00	\N	4	2026-01-03 15:24:12
36	16	\N	8.00	7.00	\N	4	2026-01-03 18:46:24
37	16	1	7.00	7.00	\N	4	2026-01-03 18:46:24
38	16	3	8.00	7.00	\N	4	2026-01-03 18:46:25
39	17	\N	8.00	8.00	\N	4	2026-01-03 18:46:38
40	17	1	8.00	8.00	\N	4	2026-01-03 18:46:38
41	17	3	8.00	8.00	\N	4	2026-01-03 18:46:38
42	17	\N	9.00	8.00	\N	4	2026-01-03 18:47:07
43	17	1	9.00	8.00	\N	4	2026-01-03 18:47:07
44	17	3	9.00	8.00	\N	4	2026-01-03 18:47:07
45	18	\N	8.00	7.00	\N	4	2026-01-03 18:47:45
46	18	8	8.00	7.00	\N	4	2026-01-03 18:47:45
47	18	2	9.00	7.00	\N	4	2026-01-03 18:47:45
48	18	12	8.00	7.00	\N	4	2026-01-03 18:47:45
49	21	\N	9.00	7.00	\N	4	2026-01-03 18:54:34
50	21	1	8.00	7.00	\N	4	2026-01-03 18:54:34
51	21	3	9.00	7.00	\N	4	2026-01-03 18:54:34
52	22	\N	8.00	8.00	\N	4	2026-01-03 18:54:53
53	22	1	8.00	8.00	\N	4	2026-01-03 18:54:53
54	22	3	8.00	8.00	\N	4	2026-01-03 18:54:53
55	23	\N	2.00	2.00	\N	4	2026-01-03 18:55:41
56	23	8	2.00	2.00	\N	4	2026-01-03 18:55:41
57	23	4	1.00	2.00	\N	4	2026-01-03 18:55:41
58	24	\N	5.00	4.00	\N	4	2026-01-03 18:56:00
59	24	8	4.00	4.00	\N	4	2026-01-03 18:56:00
60	24	4	5.00	4.00	\N	4	2026-01-03 18:56:00
61	32	\N	7.00	7.00	\N	4	2026-01-03 20:02:08
62	32	1	7.00	7.00	\N	4	2026-01-03 20:02:08
63	32	3	7.00	7.00	\N	4	2026-01-03 20:02:08
64	32	\N	7.00	7.00	\N	4	2026-01-03 20:02:12
65	32	1	7.00	7.00	\N	4	2026-01-03 20:02:13
66	32	3	7.00	7.00	\N	4	2026-01-03 20:02:13
67	33	\N	8.00	8.00	\N	4	2026-01-03 20:02:22
68	33	8	8.00	8.00	\N	4	2026-01-03 20:02:22
69	33	2	8.00	8.00	\N	4	2026-01-03 20:02:22
70	33	12	8.00	8.00	\N	4	2026-01-03 20:02:22
71	34	\N	5.00	6.00	\N	4	2026-01-03 20:02:37
72	34	10	5.00	6.00	\N	4	2026-01-03 20:02:37
73	34	2	5.00	6.00	\N	4	2026-01-03 20:02:37
74	34	12	5.00	6.00	\N	4	2026-01-03 20:02:37
75	35	\N	8.00	7.00	\N	4	2026-01-03 20:02:43
76	35	8	8.00	7.00	\N	4	2026-01-03 20:02:43
77	35	2	9.00	7.00	\N	4	2026-01-03 20:02:43
78	35	12	7.00	7.00	\N	4	2026-01-03 20:02:43
79	10	\N	8.00	8.00	\N	4	2026-01-03 20:18:22
80	10	8	8.00	8.00	\N	4	2026-01-03 20:18:22
81	10	4	8.00	8.00	\N	4	2026-01-03 20:18:22
82	5	\N	8.00	7.00	\N	4	2026-01-03 20:34:03
83	5	8	7.00	7.00	\N	4	2026-01-03 20:34:03
84	5	4	8.00	7.00	\N	4	2026-01-03 20:34:03
85	6	\N	3.00	2.00	\N	4	2026-01-03 20:34:03
86	6	8	2.00	2.00	\N	4	2026-01-03 20:34:03
87	6	4	3.00	2.00	\N	4	2026-01-03 20:34:03
88	7	\N	5.00	8.00	\N	4	2026-01-03 20:34:03
89	7	10	5.00	8.00	\N	4	2026-01-03 20:34:03
90	7	2	5.00	8.00	\N	4	2026-01-03 20:34:03
91	7	12	5.00	8.00	\N	4	2026-01-03 20:34:03
92	8	\N	7.00	7.00	\N	4	2026-01-03 20:34:03
93	8	8	7.00	7.00	\N	4	2026-01-03 20:34:03
94	8	2	7.00	7.00	\N	4	2026-01-03 20:34:03
95	8	12	7.00	7.00	\N	4	2026-01-03 20:34:03
96	9	\N	7.00	8.00	\N	4	2026-01-03 20:34:04
97	9	8	6.00	8.00	\N	4	2026-01-03 20:34:04
98	9	2	7.00	8.00	\N	4	2026-01-03 20:34:04
99	9	12	7.00	8.00	\N	4	2026-01-03 20:34:04
100	39	\N	6.00	8.00	\N	4	2026-01-04 07:57:06
101	39	1	6.00	8.00	\N	4	2026-01-04 07:57:06
102	39	3	6.00	8.00	\N	4	2026-01-04 07:57:06
103	40	\N	8.00	7.00	\N	4	2026-01-04 07:57:06
104	40	1	7.00	7.00	\N	4	2026-01-04 07:57:06
105	40	3	8.00	7.00	\N	4	2026-01-04 07:57:06
106	52	\N	10.00	8.00	\N	4	2026-01-05 13:20:26
107	52	1	10.00	8.00	\N	4	2026-01-05 13:20:26
108	52	3	10.00	8.00	\N	4	2026-01-05 13:20:26
109	53	\N	8.00	8.00	\N	4	2026-01-05 13:20:26
110	53	1	8.00	8.00	\N	4	2026-01-05 13:20:26
111	53	3	8.00	8.00	\N	4	2026-01-05 13:20:26
112	44	\N	8.00	8.00	\N	4	2026-01-05 13:36:45
113	44	1	8.00	8.00	\N	4	2026-01-05 13:36:45
114	44	3	8.00	8.00	\N	4	2026-01-05 13:36:45
115	45	\N	9.00	8.00	\N	4	2026-01-05 13:36:46
116	45	1	8.00	8.00	\N	4	2026-01-05 13:36:46
117	45	3	9.00	8.00	\N	4	2026-01-05 13:36:46
118	61	\N	8.00	8.00	\N	4	2026-01-13 07:11:01
119	61	2	8.00	8.00	\N	4	2026-01-13 07:11:01
120	62	\N	9.00	8.00	\N	4	2026-01-13 07:11:01
121	62	4	8.00	8.00	\N	4	2026-01-13 07:11:01
122	62	1	9.00	8.00	\N	4	2026-01-13 07:11:01
123	59	\N	8.00	8.00	\N	4	2026-01-13 08:45:09
124	59	4	8.00	8.00	\N	4	2026-01-13 08:45:09
125	59	6	8.00	8.00	\N	4	2026-01-13 08:45:09
126	60	\N	8.00	9.00	\N	4	2026-01-13 08:45:09
127	60	4	8.00	9.00	\N	4	2026-01-13 08:45:09
128	60	1	8.00	9.00	\N	4	2026-01-13 08:45:09
\.


--
-- Data for Name: stock_receipt_items; Type: TABLE DATA; Schema: public; Owner: express_sale_user
--

COPY public.stock_receipt_items (id, stock_receipt_id, variant_id, quantity_ordered, quantity_received, unit_cost_ariary, notes, created_at) FROM stdin;
3	2	8	10	9	15000.00	Premier item de test	2025-12-30 16:03:27.771016
4	2	6	20	20	1000.00	Deuxième item de test	2025-12-30 16:03:27.771016
14	6	12	10	10	240000.00	\N	2026-01-01 15:32:22.503366
15	6	11	30	30	480000.00	\N	2026-01-01 15:32:22.503366
12	5	10	1	1	100.00	\N	2026-01-01 14:42:15.043828
13	5	9	1	1	200.01	\N	2026-01-01 14:42:15.043828
1	1	9	100	100	15000.00	Premier item de test	2025-12-30 15:27:02.733666
2	1	6	50	50	25000.00	Deuxième item de test	2025-12-30 15:27:02.733666
19	8	9	1	1	50000.00	\N	2026-01-01 17:42:01.744221
20	8	10	1	1	50000.00	\N	2026-01-01 17:42:01.744221
25	10	10	10	10	14400.00	\N	2026-01-03 18:18:07.229946
26	10	9	10	10	14400.00	\N	2026-01-03 18:18:07.229946
27	10	11	11	11	2400.00	\N	2026-01-03 18:18:07.229946
28	10	12	6	6	2400.00	\N	2026-01-03 18:18:07.229946
29	10	6	7	7	5400.00	\N	2026-01-03 18:18:07.229946
30	10	7	8	8	5400.00	\N	2026-01-03 18:18:07.229946
31	10	8	10	10	5400.00	\N	2026-01-03 18:18:07.229946
16	7	10	2	2	24000.00	\N	2026-01-01 16:19:39.578521
17	7	9	3	3	24000.00	\N	2026-01-01 16:19:39.578521
18	7	6	2	2	12000.00	\N	2026-01-01 16:19:39.578521
21	9	10	10	5	13200.00	\N	2026-01-03 17:01:53.043067
22	9	9	20	4	13200.00	\N	2026-01-03 17:01:53.043067
23	9	11	10	5	6000.00	\N	2026-01-03 17:01:53.043067
24	9	12	10	5	6000.00	\N	2026-01-03 17:01:53.043067
32	11	9	10	4	50000.00	\N	2026-01-03 23:00:52.292126
33	11	8	10	5	60000.00	\N	2026-01-03 23:00:52.292126
34	11	7	11	3	60000.00	\N	2026-01-03 23:00:52.292126
35	11	6	5	5	60000.00	\N	2026-01-03 23:00:52.292126
10	4	12	10	10	1200.00	\N	2026-01-01 14:41:20.905883
11	4	11	20	20	1892.00	\N	2026-01-01 14:41:20.905883
5	3	11	10	10	19000.00	\N	2026-01-01 13:58:05.512624
6	3	12	21	21	19.00	\N	2026-01-01 13:58:05.512624
7	3	7	22	22	190.00	\N	2026-01-01 13:58:05.512624
8	3	8	11	11	119.00	\N	2026-01-01 13:58:05.512624
9	3	6	100	100	112.00	\N	2026-01-01 13:58:05.512624
36	12	8	10	0	12264.00	\N	2026-01-04 10:49:39.86229
37	12	7	10	0	12264.00	\N	2026-01-04 10:49:39.86229
38	12	6	120	0	12264.00	\N	2026-01-04 10:49:39.86229
39	13	9	1	1	60000.00	\N	2026-01-04 10:53:02.875332
40	13	10	10	10	60000.00	\N	2026-01-04 10:53:02.875332
41	14	10	10	0	120000.00	\N	2026-01-04 11:08:36.116645
42	14	9	10	0	120000.00	\N	2026-01-04 11:08:36.116645
43	15	12	10	0	104900.00	\N	2026-01-04 12:31:52.099817
46	17	10	1	0	12000.00	\N	2026-01-04 13:11:42.875676
47	17	9	1	0	12000.00	\N	2026-01-04 13:11:42.875676
48	18	9	1000	0	120.00	\N	2026-01-04 15:14:04.174581
49	18	10	3000	0	120.00	\N	2026-01-04 15:14:04.174581
50	19	10	1	0	60000.00	\N	2026-01-04 15:20:52.836164
51	19	9	1	0	60000.00	\N	2026-01-04 15:20:52.836164
52	20	10	10	10	12000.00	\N	2026-01-05 16:16:50.808813
53	20	9	20	10	12000.00	\N	2026-01-05 16:16:50.808813
44	16	10	10	10	12000.00	\N	2026-01-04 13:05:30.534505
45	16	9	1	1	12000.00	\N	2026-01-04 13:05:30.534505
54	21	10	1	0	1080.00	\N	2026-01-07 23:56:15.817991
55	21	9	1	0	1080.00	\N	2026-01-07 23:56:15.817991
56	22	10	10	0	50000.00	\N	2026-01-10 18:02:03.330801
57	22	9	1	0	50000.00	\N	2026-01-10 18:02:03.330801
58	23	13	10	0	102000.00	\N	2026-01-11 16:26:05.856058
61	25	15	10	10	7740.00	\N	2026-01-13 10:09:38.061305
62	25	16	10	10	645.00	\N	2026-01-13 10:09:38.061305
59	24	14	1	1	5100.00	\N	2026-01-12 23:01:50.659941
60	24	16	1	1	5100.00	\N	2026-01-12 23:01:50.659941
63	26	16	9	0	5100.00	\N	2026-01-13 13:01:15.653126
\.


--
-- Data for Name: stock_receipts; Type: TABLE DATA; Schema: public; Owner: express_sale_user
--

COPY public.stock_receipts (id, receipt_number, supplier_id, freight_forwarder_id, total_cost_ariary, status, notes, created_by, created_at, updated_at, expected_delivery_date, actual_delivery_date, validated_at) FROM stdin;
22	RCP-20260110-0001	3	1	550000.00	pending	\N	4	2026-01-10 15:02:03	2026-01-10 15:02:03	2026-01-25	\N	\N
2	RCP-20251230-0002	1	1	155000.00	arrived	Arrivé à la douane de Tamatave - En cours de dédouanement	4	2025-12-30 13:03:27	2025-12-30 13:44:11	2026-01-20	2025-12-30 13:44:11	\N
23	RCP-20260111-0001	2	1	1020000.00	pending	\N	4	2026-01-11 13:26:05	2026-01-11 13:26:05	2026-01-25	\N	\N
11	RCP-20260103-0003	3	1	980000.00	validated	\N	4	2026-01-03 20:00:52	2026-01-03 20:02:44	\N	2026-01-03 20:01:47	2026-01-03 20:02:44
4	RCP-20260101-0002	1	1	49840.00	validated	jj'kk	4	2026-01-01 11:41:20	2026-01-03 20:18:36	2026-01-25	2026-01-03 20:18:07	2026-01-03 20:18:36
6	RCP-20260101-0004	3	1	16800000.00	arrived	\N	4	2026-01-01 12:32:22	2026-01-02 17:53:36	2026-01-31	2026-01-02 17:53:36	\N
3	RCP-20260101-0001	1	1	207088.00	validated	\N	4	2026-01-01 10:58:05	2026-01-03 20:34:09	2026-01-18	2026-01-03 20:32:54	2026-01-03 20:34:09
1	RCP-20251230-0001	1	1	2750000.00	arrived	Arrivé à la douane de Tamatave - En cours de dédouanement	4	2025-12-30 12:27:02	2026-01-02 20:04:40	\N	2026-01-02 20:04:40	\N
25	RCP-20260113-0001	2	1	83850.00	validated	\N	4	2026-01-13 07:09:38	2026-01-13 07:11:03	2026-01-31	2026-01-13 07:10:40	2026-01-13 07:11:03
8	RCP-20260101-0006	3	1	100000.00	arrived	\N	4	2026-01-01 14:42:01	2026-01-02 20:06:25	2026-01-24	2026-01-02 20:06:25	\N
13	RCP-20260104-0002	1	1	660000.00	validated	\N	4	2026-01-04 07:53:02	2026-01-04 07:57:09	2026-01-31	2026-01-04 07:56:00	2026-01-04 07:57:09
14	RCP-20260104-0003	3	1	2400000.00	cancelled	\N	4	2026-01-04 08:08:36	2026-01-04 08:14:24	2026-01-31	\N	\N
12	RCP-20260104-0001	3	1	1716960.00	in_transit	\N	4	2026-01-04 07:49:39	2026-01-13 07:57:01	\N	\N	\N
5	RCP-20260101-0003	3	1	300.01	validated	\N	4	2026-01-01 11:42:15	2026-01-03 14:34:46	\N	2026-01-02 18:42:45	\N
10	RCP-20260103-0002	2	1	463800.00	validated	\N	4	2026-01-03 15:18:07	2026-01-03 15:24:12	\N	2026-01-03 15:20:55	\N
17	RCP-20260104-0006	2	1	24000.00	cancelled	\N	4	2026-01-04 10:11:42	2026-01-04 11:06:45	2026-01-16	\N	\N
7	RCP-20260101-0005	2	1	144000.00	validated	\N	4	2026-01-01 13:19:39	2026-01-03 18:47:51	\N	2026-01-03 16:30:49	\N
15	RCP-20260104-0004	1	1	1049000.00	cancelled	\N	4	2026-01-04 09:31:52	2026-01-04 11:26:34	2026-01-23	\N	\N
9	RCP-20260103-0001	2	1	178800.00	validated	\N	4	2026-01-03 14:01:53	2026-01-03 18:56:04	2026-01-31	2026-01-03 18:53:38	\N
24	RCP-20260112-0001	2	1	10200.00	validated	\N	4	2026-01-12 20:01:50	2026-01-13 08:45:11	2026-01-18	2026-01-13 08:44:55	2026-01-13 08:45:11
18	RCP-20260104-0007	2	1	480000.00	cancelled	\N	4	2026-01-04 12:14:04	2026-01-04 12:14:54	2026-01-15	\N	\N
19	RCP-20260104-0008	2	\N	120000.00	cancelled	\N	4	2026-01-04 12:20:52	2026-01-04 12:21:42	2026-01-25	\N	\N
26	RCP-20260113-0002	3	1	45900.00	pending	\N	4	2026-01-13 10:01:15	2026-01-13 10:01:15	2026-01-17	\N	\N
20	RCP-20260105-0001	3	1	240000.00	validated	\N	4	2026-01-05 13:16:50	2026-01-05 13:20:34	2026-01-24	2026-01-05 13:18:58	2026-01-05 13:20:34
16	RCP-20260104-0005	2	1	132000.00	validated	\N	4	2026-01-04 10:05:30	2026-01-05 13:36:48	2026-01-24	2026-01-05 13:36:28	2026-01-05 13:36:48
21	RCP-20260107-0001	3	1	2160.00	pending	\N	4	2026-01-07 20:56:15	2026-01-07 20:56:15	2026-01-25	\N	\N
\.


--
-- Data for Name: suppliers; Type: TABLE DATA; Schema: public; Owner: express_sale_user
--

COPY public.suppliers (id, name, wechat, profile, contact, accessibility_notes, reliability_score, logo_url, is_active, created_at, updated_at, coordinate_id, total_orders, total_items_ordered, total_items_received, quality_rating_sum, quality_rating_count, total_value_ordered, total_value_received, weighted_quality_sum, total_weighted_value) FROM stdin;
3	raggathon	AZERTYUIOP	FZFHZGH	+97291899111	ALKJLKJEEJA	5.35	\N	t	2025-12-27 15:35:55	2026-01-13 17:41:38	4	6	68	39	352.00	48	4840600.02	2440600.02	395902275.74	73462700.09
2	HERIMANANTSOA Manitriniaina Christian AA	ajhsjhjash	dkzjkjkjkjdkzj	+97291899111	dizuhuiiuhiu"h"uh	6.94	\N	t	2025-12-27 14:50:39	2026-01-13 17:41:47	1	12	152	121	348.00	49	2699700.00	2025300.00	23654700.00	3285600.00
1	Guangzhou_corpaaa	ajhsjhjash	fekzkjkjkjkejkjz	+97291899111	eahjhejhejhejahjhjhh	7.19	\N	t	2025-12-27 07:49:06	2026-01-13 17:41:53	5	6	205	205	223.00	31	1833856.00	1833856.00	29878351.53	4073041.00
\.


--
-- Data for Name: system_settings; Type: TABLE DATA; Schema: public; Owner: express_sale_user
--

COPY public.system_settings (id, key, value, value_type, description, is_public, updated_by, created_at, updated_at) FROM stdin;
1	shop_name	Ma Boutique	string	Nom de la boutique	t	\N	2025-12-19 11:51:23.334656	2025-12-19 11:51:23.334656
2	shop_address		string	Adresse boutique	t	\N	2025-12-19 11:51:23.334656	2025-12-19 11:51:23.334656
3	reservation_default_days	3	number	Délai réservation par défaut (jours)	f	\N	2025-12-19 11:51:23.334656	2025-12-19 11:51:23.334656
4	low_stock_threshold	5	number	Seuil alerte stock bas	f	\N	2025-12-19 11:51:23.334656	2025-12-19 11:51:23.334656
5	credit_interest_rate	0	number	Taux intérêt retard crédit (%)	f	\N	2025-12-19 11:51:23.334656	2025-12-19 11:51:23.334656
6	currency_main	MGA	string	Devise principale	t	\N	2025-12-19 11:51:23.334656	2025-12-19 11:51:23.334656
7	currency_secondary	CNY	string	Devise secondaire (achats)	t	\N	2025-12-19 11:51:23.334656	2025-12-19 11:51:23.334656
\.


--
-- Data for Name: transaction_types; Type: TABLE DATA; Schema: public; Owner: express_sale_user
--

COPY public.transaction_types (id, code, name, display_name, category, description, created_at, updated_at) FROM stdin;
15	OPENING_BALANCE	opening_balance	Solde initial	income	Solde initial du compte	2025-12-29 14:34:13.404169	2025-12-29 14:34:13.404169
16	INCOME	income	Revenu	income	Entrée d’argent	2025-12-29 14:34:13.404169	2025-12-29 14:34:13.404169
17	EXPENSE	expense	Dépense	expense	Sortie d’argent	2025-12-29 14:34:13.404169	2025-12-29 14:34:13.404169
18	TRANSFER	transfer	Transfert	transfer	Transfert entre comptes	2025-12-29 14:34:13.404169	2025-12-29 14:34:13.404169
19	REFUND	refund	Remboursement	expense	Remboursement effectué	2025-12-29 14:34:13.404169	2025-12-29 14:34:13.404169
21	REVERSAL	Annulation	Annulation	adjustment	Transaction d'annulation/contre-passation	2026-01-04 09:12:22	2026-01-04 09:12:22
\.


--
-- Data for Name: user_sessions; Type: TABLE DATA; Schema: public; Owner: express_sale_user
--

COPY public.user_sessions (id, user_id, login_at, logout_at, ip_address, created_at) FROM stdin;
\.


--
-- Data for Name: users; Type: TABLE DATA; Schema: public; Owner: express_sale_user
--

COPY public.users (id, name, username, password, role, is_active, created_at, updated_at) FROM stdin;
3	chirstian	herimanantsoa	$2y$12$JIz2z14FuV4vde7Y6TaXluB0WnUR5hLquq3keQZ5raES/bbfA/rAu	vendeur	t	2025-12-19 13:08:48	2025-12-19 13:08:48
2	christianHerimanantsoa	herimanantsoa51	$2y$12$M8EzX7//xt8Iu4owbXvBDej4KRZ0rDR9WqUhIHk8PFgTR8Pexz.vG	admin	t	2025-12-19 11:09:55	2025-12-27 10:20:50.94049
4	Administrateur	admin	$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi	admin	t	2025-12-27 10:28:54.853759	2025-12-27 10:28:54.853759
\.


--
-- Data for Name: variant_attribute_values; Type: TABLE DATA; Schema: public; Owner: express_sale_user
--

COPY public.variant_attribute_values (id, variant_id, attribute_value_id, created_at) FROM stdin;
66	9	7	2025-12-30 15:14:24.126577
67	9	34	2025-12-30 15:14:24.134446
68	10	10	2025-12-30 15:15:15.223996
69	10	35	2025-12-30 15:15:15.27413
70	11	36	2025-12-30 15:16:27.233827
71	11	37	2025-12-30 15:16:27.23873
72	12	30	2025-12-30 15:16:42.278852
73	12	38	2025-12-30 15:16:42.284114
74	7	20	2026-01-02 22:01:59.72436
75	7	16	2026-01-02 22:01:59.728694
76	7	31	2026-01-02 22:01:59.730988
80	6	20	2026-01-08 16:38:04.990817
81	6	15	2026-01-08 16:38:04.996319
82	6	24	2026-01-08 16:38:04.999495
83	8	20	2026-01-08 16:38:37.15941
84	8	17	2026-01-08 16:38:37.163253
85	8	33	2026-01-08 16:38:37.167149
86	13	5	2026-01-11 16:24:13.205051
87	13	39	2026-01-11 16:24:13.212682
88	14	40	2026-01-12 21:52:46.543238
89	14	41	2026-01-12 21:52:46.548929
90	15	15	2026-01-12 22:08:13.230703
91	16	40	2026-01-12 22:29:25.575269
92	16	10	2026-01-12 22:29:25.579154
\.


--
-- Name: account_transactions_id_seq; Type: SEQUENCE SET; Schema: public; Owner: express_sale_user
--

SELECT pg_catalog.setval('public.account_transactions_id_seq', 122, true);


--
-- Name: account_types_id_seq; Type: SEQUENCE SET; Schema: public; Owner: express_sale_user
--

SELECT pg_catalog.setval('public.account_types_id_seq', 3, true);


--
-- Name: accounts_id_seq; Type: SEQUENCE SET; Schema: public; Owner: express_sale_user
--

SELECT pg_catalog.setval('public.accounts_id_seq', 5, true);


--
-- Name: attribute_types_id_seq; Type: SEQUENCE SET; Schema: public; Owner: express_sale_user
--

SELECT pg_catalog.setval('public.attribute_types_id_seq', 15, true);


--
-- Name: attribute_values_id_seq; Type: SEQUENCE SET; Schema: public; Owner: express_sale_user
--

SELECT pg_catalog.setval('public.attribute_values_id_seq', 41, true);


--
-- Name: audit_logs_id_seq; Type: SEQUENCE SET; Schema: public; Owner: express_sale_user
--

SELECT pg_catalog.setval('public.audit_logs_id_seq', 1, false);


--
-- Name: categories_id_seq; Type: SEQUENCE SET; Schema: public; Owner: express_sale_user
--

SELECT pg_catalog.setval('public.categories_id_seq', 21, true);


--
-- Name: coordinates_id_seq; Type: SEQUENCE SET; Schema: public; Owner: express_sale_user
--

SELECT pg_catalog.setval('public.coordinates_id_seq', 5, true);


--
-- Name: credit_installments_id_seq; Type: SEQUENCE SET; Schema: public; Owner: express_sale_user
--

SELECT pg_catalog.setval('public.credit_installments_id_seq', 11, true);


--
-- Name: credits_id_seq; Type: SEQUENCE SET; Schema: public; Owner: express_sale_user
--

SELECT pg_catalog.setval('public.credits_id_seq', 9, true);


--
-- Name: currency_rates_id_seq; Type: SEQUENCE SET; Schema: public; Owner: express_sale_user
--

SELECT pg_catalog.setval('public.currency_rates_id_seq', 8, true);


--
-- Name: customers_id_seq; Type: SEQUENCE SET; Schema: public; Owner: express_sale_user
--

SELECT pg_catalog.setval('public.customers_id_seq', 6, true);


--
-- Name: expense_categories_id_seq; Type: SEQUENCE SET; Schema: public; Owner: express_sale_user
--

SELECT pg_catalog.setval('public.expense_categories_id_seq', 16, true);


--
-- Name: failed_jobs_id_seq; Type: SEQUENCE SET; Schema: public; Owner: express_sale_user
--

SELECT pg_catalog.setval('public.failed_jobs_id_seq', 1, false);


--
-- Name: freight_forwarders_id_seq; Type: SEQUENCE SET; Schema: public; Owner: express_sale_user
--

SELECT pg_catalog.setval('public.freight_forwarders_id_seq', 1, true);


--
-- Name: installment_transactions_id_seq; Type: SEQUENCE SET; Schema: public; Owner: express_sale_user
--

SELECT pg_catalog.setval('public.installment_transactions_id_seq', 11, true);


--
-- Name: jobs_id_seq; Type: SEQUENCE SET; Schema: public; Owner: express_sale_user
--

SELECT pg_catalog.setval('public.jobs_id_seq', 1, false);


--
-- Name: locations_id_seq; Type: SEQUENCE SET; Schema: public; Owner: express_sale_user
--

SELECT pg_catalog.setval('public.locations_id_seq', 8, true);


--
-- Name: migrations_id_seq; Type: SEQUENCE SET; Schema: public; Owner: express_sale_user
--

SELECT pg_catalog.setval('public.migrations_id_seq', 20, true);


--
-- Name: notifications_id_seq; Type: SEQUENCE SET; Schema: public; Owner: express_sale_user
--

SELECT pg_catalog.setval('public.notifications_id_seq', 1, false);


--
-- Name: personal_access_tokens_id_seq; Type: SEQUENCE SET; Schema: public; Owner: express_sale_user
--

SELECT pg_catalog.setval('public.personal_access_tokens_id_seq', 41, true);


--
-- Name: product_attributes_id_seq; Type: SEQUENCE SET; Schema: public; Owner: express_sale_user
--

SELECT pg_catalog.setval('public.product_attributes_id_seq', 47, true);


--
-- Name: product_variant_locations_id_seq; Type: SEQUENCE SET; Schema: public; Owner: express_sale_user
--

SELECT pg_catalog.setval('public.product_variant_locations_id_seq', 31, true);


--
-- Name: product_variants_id_seq; Type: SEQUENCE SET; Schema: public; Owner: express_sale_user
--

SELECT pg_catalog.setval('public.product_variants_id_seq', 16, true);


--
-- Name: products_id_seq; Type: SEQUENCE SET; Schema: public; Owner: express_sale_user
--

SELECT pg_catalog.setval('public.products_id_seq', 26, true);


--
-- Name: reservation_deposits_id_seq; Type: SEQUENCE SET; Schema: public; Owner: express_sale_user
--

SELECT pg_catalog.setval('public.reservation_deposits_id_seq', 1, false);


--
-- Name: reservations_id_seq; Type: SEQUENCE SET; Schema: public; Owner: express_sale_user
--

SELECT pg_catalog.setval('public.reservations_id_seq', 7, true);


--
-- Name: sale_items_id_seq; Type: SEQUENCE SET; Schema: public; Owner: express_sale_user
--

SELECT pg_catalog.setval('public.sale_items_id_seq', 43, true);


--
-- Name: sales_id_seq; Type: SEQUENCE SET; Schema: public; Owner: express_sale_user
--

SELECT pg_catalog.setval('public.sales_id_seq', 25, true);


--
-- Name: stock_movements_id_seq; Type: SEQUENCE SET; Schema: public; Owner: express_sale_user
--

SELECT pg_catalog.setval('public.stock_movements_id_seq', 75, true);


--
-- Name: stock_receipt_item_ratings_id_seq; Type: SEQUENCE SET; Schema: public; Owner: express_sale_user
--

SELECT pg_catalog.setval('public.stock_receipt_item_ratings_id_seq', 128, true);


--
-- Name: stock_receipt_items_id_seq; Type: SEQUENCE SET; Schema: public; Owner: express_sale_user
--

SELECT pg_catalog.setval('public.stock_receipt_items_id_seq', 63, true);


--
-- Name: stock_receipts_id_seq; Type: SEQUENCE SET; Schema: public; Owner: express_sale_user
--

SELECT pg_catalog.setval('public.stock_receipts_id_seq', 26, true);


--
-- Name: suppliers_id_seq; Type: SEQUENCE SET; Schema: public; Owner: express_sale_user
--

SELECT pg_catalog.setval('public.suppliers_id_seq', 3, true);


--
-- Name: system_settings_id_seq; Type: SEQUENCE SET; Schema: public; Owner: express_sale_user
--

SELECT pg_catalog.setval('public.system_settings_id_seq', 7, true);


--
-- Name: transaction_types_id_seq; Type: SEQUENCE SET; Schema: public; Owner: express_sale_user
--

SELECT pg_catalog.setval('public.transaction_types_id_seq', 21, true);


--
-- Name: user_sessions_id_seq; Type: SEQUENCE SET; Schema: public; Owner: express_sale_user
--

SELECT pg_catalog.setval('public.user_sessions_id_seq', 1, false);


--
-- Name: users_id_seq; Type: SEQUENCE SET; Schema: public; Owner: express_sale_user
--

SELECT pg_catalog.setval('public.users_id_seq', 4, true);


--
-- Name: variant_attribute_values_id_seq; Type: SEQUENCE SET; Schema: public; Owner: express_sale_user
--

SELECT pg_catalog.setval('public.variant_attribute_values_id_seq', 92, true);


--
-- Name: account_transactions account_transactions_pkey; Type: CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.account_transactions
    ADD CONSTRAINT account_transactions_pkey PRIMARY KEY (id);


--
-- Name: account_types account_types_code_key; Type: CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.account_types
    ADD CONSTRAINT account_types_code_key UNIQUE (code);


--
-- Name: account_types account_types_pkey; Type: CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.account_types
    ADD CONSTRAINT account_types_pkey PRIMARY KEY (id);


--
-- Name: accounts accounts_pkey; Type: CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.accounts
    ADD CONSTRAINT accounts_pkey PRIMARY KEY (id);


--
-- Name: attribute_types attribute_types_name_key; Type: CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.attribute_types
    ADD CONSTRAINT attribute_types_name_key UNIQUE (name);


--
-- Name: attribute_types attribute_types_pkey; Type: CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.attribute_types
    ADD CONSTRAINT attribute_types_pkey PRIMARY KEY (id);


--
-- Name: attribute_values attribute_values_attribute_type_id_value_key; Type: CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.attribute_values
    ADD CONSTRAINT attribute_values_attribute_type_id_value_key UNIQUE (attribute_type_id, value);


--
-- Name: attribute_values attribute_values_pkey; Type: CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.attribute_values
    ADD CONSTRAINT attribute_values_pkey PRIMARY KEY (id);


--
-- Name: audit_logs audit_logs_pkey; Type: CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.audit_logs
    ADD CONSTRAINT audit_logs_pkey PRIMARY KEY (id);


--
-- Name: cache_locks cache_locks_pkey; Type: CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.cache_locks
    ADD CONSTRAINT cache_locks_pkey PRIMARY KEY (key);


--
-- Name: cache cache_pkey; Type: CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.cache
    ADD CONSTRAINT cache_pkey PRIMARY KEY (key);


--
-- Name: categories categories_pkey; Type: CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.categories
    ADD CONSTRAINT categories_pkey PRIMARY KEY (id);


--
-- Name: coordinates coordinates_country_city_unique; Type: CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.coordinates
    ADD CONSTRAINT coordinates_country_city_unique UNIQUE (country, city);


--
-- Name: coordinates coordinates_pkey; Type: CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.coordinates
    ADD CONSTRAINT coordinates_pkey PRIMARY KEY (id);


--
-- Name: credit_installments credit_installments_credit_id_installment_number_key; Type: CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.credit_installments
    ADD CONSTRAINT credit_installments_credit_id_installment_number_key UNIQUE (credit_id, installment_number);


--
-- Name: credit_installments credit_installments_pkey; Type: CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.credit_installments
    ADD CONSTRAINT credit_installments_pkey PRIMARY KEY (id);


--
-- Name: credits credits_pkey; Type: CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.credits
    ADD CONSTRAINT credits_pkey PRIMARY KEY (id);


--
-- Name: credits credits_sale_id_key; Type: CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.credits
    ADD CONSTRAINT credits_sale_id_key UNIQUE (sale_id);


--
-- Name: currency_rates currency_rates_pkey; Type: CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.currency_rates
    ADD CONSTRAINT currency_rates_pkey PRIMARY KEY (id);


--
-- Name: customers customers_customer_number_unique; Type: CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.customers
    ADD CONSTRAINT customers_customer_number_unique UNIQUE (customer_number);


--
-- Name: customers customers_pkey; Type: CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.customers
    ADD CONSTRAINT customers_pkey PRIMARY KEY (id);


--
-- Name: expense_categories expense_categories_pkey; Type: CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.expense_categories
    ADD CONSTRAINT expense_categories_pkey PRIMARY KEY (id);


--
-- Name: failed_jobs failed_jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.failed_jobs
    ADD CONSTRAINT failed_jobs_pkey PRIMARY KEY (id);


--
-- Name: failed_jobs failed_jobs_uuid_unique; Type: CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.failed_jobs
    ADD CONSTRAINT failed_jobs_uuid_unique UNIQUE (uuid);


--
-- Name: freight_forwarders freight_forwarders_pkey; Type: CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.freight_forwarders
    ADD CONSTRAINT freight_forwarders_pkey PRIMARY KEY (id);


--
-- Name: installment_transactions installment_transactions_pkey; Type: CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.installment_transactions
    ADD CONSTRAINT installment_transactions_pkey PRIMARY KEY (id);


--
-- Name: job_batches job_batches_pkey; Type: CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.job_batches
    ADD CONSTRAINT job_batches_pkey PRIMARY KEY (id);


--
-- Name: jobs jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.jobs
    ADD CONSTRAINT jobs_pkey PRIMARY KEY (id);


--
-- Name: locations locations_code_key; Type: CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.locations
    ADD CONSTRAINT locations_code_key UNIQUE (code);


--
-- Name: locations locations_name_key; Type: CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.locations
    ADD CONSTRAINT locations_name_key UNIQUE (name);


--
-- Name: locations locations_pkey; Type: CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.locations
    ADD CONSTRAINT locations_pkey PRIMARY KEY (id);


--
-- Name: migrations migrations_pkey; Type: CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.migrations
    ADD CONSTRAINT migrations_pkey PRIMARY KEY (id);


--
-- Name: notifications notifications_pkey; Type: CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.notifications
    ADD CONSTRAINT notifications_pkey PRIMARY KEY (id);


--
-- Name: password_reset_tokens password_reset_tokens_pkey; Type: CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.password_reset_tokens
    ADD CONSTRAINT password_reset_tokens_pkey PRIMARY KEY (username);


--
-- Name: personal_access_tokens personal_access_tokens_pkey; Type: CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.personal_access_tokens
    ADD CONSTRAINT personal_access_tokens_pkey PRIMARY KEY (id);


--
-- Name: personal_access_tokens personal_access_tokens_token_unique; Type: CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.personal_access_tokens
    ADD CONSTRAINT personal_access_tokens_token_unique UNIQUE (token);


--
-- Name: product_attributes product_attributes_pkey; Type: CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.product_attributes
    ADD CONSTRAINT product_attributes_pkey PRIMARY KEY (id);


--
-- Name: product_attributes product_attributes_product_id_attribute_type_id_key; Type: CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.product_attributes
    ADD CONSTRAINT product_attributes_product_id_attribute_type_id_key UNIQUE (product_id, attribute_type_id);


--
-- Name: product_variant_locations product_variant_locations_pkey; Type: CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.product_variant_locations
    ADD CONSTRAINT product_variant_locations_pkey PRIMARY KEY (id);


--
-- Name: product_variants product_variants_pkey; Type: CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.product_variants
    ADD CONSTRAINT product_variants_pkey PRIMARY KEY (id);


--
-- Name: product_variants product_variants_sku_key; Type: CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.product_variants
    ADD CONSTRAINT product_variants_sku_key UNIQUE (sku);


--
-- Name: products products_pkey; Type: CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.products
    ADD CONSTRAINT products_pkey PRIMARY KEY (id);


--
-- Name: reservation_deposits reservation_deposits_pkey; Type: CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.reservation_deposits
    ADD CONSTRAINT reservation_deposits_pkey PRIMARY KEY (id);


--
-- Name: reservations reservations_pkey; Type: CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.reservations
    ADD CONSTRAINT reservations_pkey PRIMARY KEY (id);


--
-- Name: reservations reservations_sale_id_key; Type: CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.reservations
    ADD CONSTRAINT reservations_sale_id_key UNIQUE (sale_id);


--
-- Name: sale_items sale_items_pkey; Type: CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.sale_items
    ADD CONSTRAINT sale_items_pkey PRIMARY KEY (id);


--
-- Name: sales sales_pkey; Type: CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.sales
    ADD CONSTRAINT sales_pkey PRIMARY KEY (id);


--
-- Name: sales sales_sale_number_key; Type: CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.sales
    ADD CONSTRAINT sales_sale_number_key UNIQUE (sale_number);


--
-- Name: sessions sessions_pkey; Type: CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.sessions
    ADD CONSTRAINT sessions_pkey PRIMARY KEY (id);


--
-- Name: stock_movements stock_movements_pkey; Type: CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.stock_movements
    ADD CONSTRAINT stock_movements_pkey PRIMARY KEY (id);


--
-- Name: stock_receipt_item_ratings stock_receipt_item_ratings_pkey; Type: CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.stock_receipt_item_ratings
    ADD CONSTRAINT stock_receipt_item_ratings_pkey PRIMARY KEY (id);


--
-- Name: stock_receipt_items stock_receipt_items_pkey; Type: CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.stock_receipt_items
    ADD CONSTRAINT stock_receipt_items_pkey PRIMARY KEY (id);


--
-- Name: stock_receipts stock_receipts_pkey; Type: CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.stock_receipts
    ADD CONSTRAINT stock_receipts_pkey PRIMARY KEY (id);


--
-- Name: stock_receipts stock_receipts_receipt_number_key; Type: CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.stock_receipts
    ADD CONSTRAINT stock_receipts_receipt_number_key UNIQUE (receipt_number);


--
-- Name: suppliers suppliers_pkey; Type: CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.suppliers
    ADD CONSTRAINT suppliers_pkey PRIMARY KEY (id);


--
-- Name: system_settings system_settings_key_key; Type: CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.system_settings
    ADD CONSTRAINT system_settings_key_key UNIQUE (key);


--
-- Name: system_settings system_settings_pkey; Type: CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.system_settings
    ADD CONSTRAINT system_settings_pkey PRIMARY KEY (id);


--
-- Name: transaction_types transaction_types_code_key; Type: CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.transaction_types
    ADD CONSTRAINT transaction_types_code_key UNIQUE (code);


--
-- Name: transaction_types transaction_types_pkey; Type: CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.transaction_types
    ADD CONSTRAINT transaction_types_pkey PRIMARY KEY (id);


--
-- Name: product_variant_locations uq_variant_location; Type: CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.product_variant_locations
    ADD CONSTRAINT uq_variant_location UNIQUE (variant_id, location_id);


--
-- Name: user_sessions user_sessions_pkey; Type: CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.user_sessions
    ADD CONSTRAINT user_sessions_pkey PRIMARY KEY (id);


--
-- Name: users users_pkey; Type: CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_pkey PRIMARY KEY (id);


--
-- Name: users users_usename_key; Type: CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_usename_key UNIQUE (username);


--
-- Name: variant_attribute_values variant_attribute_values_pkey; Type: CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.variant_attribute_values
    ADD CONSTRAINT variant_attribute_values_pkey PRIMARY KEY (id);


--
-- Name: variant_attribute_values variant_attribute_values_variant_id_attribute_value_id_key; Type: CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.variant_attribute_values
    ADD CONSTRAINT variant_attribute_values_variant_id_attribute_value_id_key UNIQUE (variant_id, attribute_value_id);


--
-- Name: account_transactions_reversed_transaction_id_index; Type: INDEX; Schema: public; Owner: express_sale_user
--

CREATE INDEX account_transactions_reversed_transaction_id_index ON public.account_transactions USING btree (reversed_transaction_id);


--
-- Name: coordinates_city_index; Type: INDEX; Schema: public; Owner: express_sale_user
--

CREATE INDEX coordinates_city_index ON public.coordinates USING btree (city);


--
-- Name: coordinates_country_index; Type: INDEX; Schema: public; Owner: express_sale_user
--

CREATE INDEX coordinates_country_index ON public.coordinates USING btree (country);


--
-- Name: idx_account_transactions_account; Type: INDEX; Schema: public; Owner: express_sale_user
--

CREATE INDEX idx_account_transactions_account ON public.account_transactions USING btree (account_id);


--
-- Name: idx_account_transactions_date; Type: INDEX; Schema: public; Owner: express_sale_user
--

CREATE INDEX idx_account_transactions_date ON public.account_transactions USING btree (transaction_date);


--
-- Name: idx_account_transactions_freight; Type: INDEX; Schema: public; Owner: express_sale_user
--

CREATE INDEX idx_account_transactions_freight ON public.account_transactions USING btree (freight_forwarder_id);


--
-- Name: idx_account_transactions_sale; Type: INDEX; Schema: public; Owner: express_sale_user
--

CREATE INDEX idx_account_transactions_sale ON public.account_transactions USING btree (sale_id);


--
-- Name: idx_account_transactions_supplier; Type: INDEX; Schema: public; Owner: express_sale_user
--

CREATE INDEX idx_account_transactions_supplier ON public.account_transactions USING btree (supplier_id);


--
-- Name: idx_account_transactions_type; Type: INDEX; Schema: public; Owner: express_sale_user
--

CREATE INDEX idx_account_transactions_type ON public.account_transactions USING btree (transaction_type_id);


--
-- Name: idx_accounts_active; Type: INDEX; Schema: public; Owner: express_sale_user
--

CREATE INDEX idx_accounts_active ON public.accounts USING btree (is_active);


--
-- Name: idx_accounts_type; Type: INDEX; Schema: public; Owner: express_sale_user
--

CREATE INDEX idx_accounts_type ON public.accounts USING btree (account_type_id);


--
-- Name: idx_attribute_values_type; Type: INDEX; Schema: public; Owner: express_sale_user
--

CREATE INDEX idx_attribute_values_type ON public.attribute_values USING btree (attribute_type_id);


--
-- Name: idx_audit_logs_created; Type: INDEX; Schema: public; Owner: express_sale_user
--

CREATE INDEX idx_audit_logs_created ON public.audit_logs USING btree (created_at DESC);


--
-- Name: idx_audit_logs_table; Type: INDEX; Schema: public; Owner: express_sale_user
--

CREATE INDEX idx_audit_logs_table ON public.audit_logs USING btree (table_name, record_id);


--
-- Name: idx_audit_logs_user; Type: INDEX; Schema: public; Owner: express_sale_user
--

CREATE INDEX idx_audit_logs_user ON public.audit_logs USING btree (user_id);


--
-- Name: idx_categories_parent_id; Type: INDEX; Schema: public; Owner: express_sale_user
--

CREATE INDEX idx_categories_parent_id ON public.categories USING btree (parent_id);


--
-- Name: idx_credit_installments_credit; Type: INDEX; Schema: public; Owner: express_sale_user
--

CREATE INDEX idx_credit_installments_credit ON public.credit_installments USING btree (credit_id);


--
-- Name: idx_credit_installments_due_date; Type: INDEX; Schema: public; Owner: express_sale_user
--

CREATE INDEX idx_credit_installments_due_date ON public.credit_installments USING btree (due_date);


--
-- Name: idx_credits_customer; Type: INDEX; Schema: public; Owner: express_sale_user
--

CREATE INDEX idx_credits_customer ON public.credits USING btree (customer_id);


--
-- Name: idx_credits_due_date; Type: INDEX; Schema: public; Owner: express_sale_user
--

CREATE INDEX idx_credits_due_date ON public.credits USING btree (due_date);


--
-- Name: idx_credits_sale; Type: INDEX; Schema: public; Owner: express_sale_user
--

CREATE INDEX idx_credits_sale ON public.credits USING btree (sale_id);


--
-- Name: idx_credits_status; Type: INDEX; Schema: public; Owner: express_sale_user
--

CREATE INDEX idx_credits_status ON public.credits USING btree (status);


--
-- Name: idx_customers_name; Type: INDEX; Schema: public; Owner: express_sale_user
--

CREATE INDEX idx_customers_name ON public.customers USING btree (name);


--
-- Name: idx_customers_phone; Type: INDEX; Schema: public; Owner: express_sale_user
--

CREATE INDEX idx_customers_phone ON public.customers USING btree (phone);


--
-- Name: idx_installments_due_status; Type: INDEX; Schema: public; Owner: express_sale_user
--

CREATE INDEX idx_installments_due_status ON public.credit_installments USING btree (due_date, status);


--
-- Name: idx_locations_active; Type: INDEX; Schema: public; Owner: express_sale_user
--

CREATE INDEX idx_locations_active ON public.locations USING btree (is_active) WHERE (is_active = true);


--
-- Name: idx_locations_warehouse; Type: INDEX; Schema: public; Owner: express_sale_user
--

CREATE INDEX idx_locations_warehouse ON public.locations USING btree (warehouse);


--
-- Name: idx_notifications_created; Type: INDEX; Schema: public; Owner: express_sale_user
--

CREATE INDEX idx_notifications_created ON public.notifications USING btree (created_at DESC);


--
-- Name: idx_notifications_read; Type: INDEX; Schema: public; Owner: express_sale_user
--

CREATE INDEX idx_notifications_read ON public.notifications USING btree (is_read) WHERE (is_read = false);


--
-- Name: idx_notifications_user; Type: INDEX; Schema: public; Owner: express_sale_user
--

CREATE INDEX idx_notifications_user ON public.notifications USING btree (user_id);


--
-- Name: idx_product_attributes_product; Type: INDEX; Schema: public; Owner: express_sale_user
--

CREATE INDEX idx_product_attributes_product ON public.product_attributes USING btree (product_id);


--
-- Name: idx_product_variants_active; Type: INDEX; Schema: public; Owner: express_sale_user
--

CREATE INDEX idx_product_variants_active ON public.product_variants USING btree (is_active) WHERE (is_active = true);


--
-- Name: idx_product_variants_low_stock; Type: INDEX; Schema: public; Owner: express_sale_user
--

CREATE INDEX idx_product_variants_low_stock ON public.product_variants USING btree (stock_quantity) WHERE (stock_quantity <= low_stock_threshold);


--
-- Name: idx_product_variants_product; Type: INDEX; Schema: public; Owner: express_sale_user
--

CREATE INDEX idx_product_variants_product ON public.product_variants USING btree (product_id);


--
-- Name: idx_product_variants_sku; Type: INDEX; Schema: public; Owner: express_sale_user
--

CREATE INDEX idx_product_variants_sku ON public.product_variants USING btree (sku);


--
-- Name: idx_products_active; Type: INDEX; Schema: public; Owner: express_sale_user
--

CREATE INDEX idx_products_active ON public.products USING btree (is_active) WHERE (is_active = true);


--
-- Name: idx_products_category_id; Type: INDEX; Schema: public; Owner: express_sale_user
--

CREATE INDEX idx_products_category_id ON public.products USING btree (category_id);


--
-- Name: idx_products_subcategory_id; Type: INDEX; Schema: public; Owner: express_sale_user
--

CREATE INDEX idx_products_subcategory_id ON public.products USING btree (subcategory_id);


--
-- Name: idx_pvl_location; Type: INDEX; Schema: public; Owner: express_sale_user
--

CREATE INDEX idx_pvl_location ON public.product_variant_locations USING btree (location_id);


--
-- Name: idx_pvl_quantity; Type: INDEX; Schema: public; Owner: express_sale_user
--

CREATE INDEX idx_pvl_quantity ON public.product_variant_locations USING btree (quantity);


--
-- Name: idx_pvl_variant; Type: INDEX; Schema: public; Owner: express_sale_user
--

CREATE INDEX idx_pvl_variant ON public.product_variant_locations USING btree (variant_id);


--
-- Name: idx_pvl_variant_stock; Type: INDEX; Schema: public; Owner: express_sale_user
--

CREATE INDEX idx_pvl_variant_stock ON public.product_variant_locations USING btree (variant_id, quantity);


--
-- Name: idx_reservations_customer; Type: INDEX; Schema: public; Owner: express_sale_user
--

CREATE INDEX idx_reservations_customer ON public.reservations USING btree (customer_id);


--
-- Name: idx_reservations_expiry; Type: INDEX; Schema: public; Owner: express_sale_user
--

CREATE INDEX idx_reservations_expiry ON public.reservations USING btree (expiry_date);


--
-- Name: idx_reservations_sale; Type: INDEX; Schema: public; Owner: express_sale_user
--

CREATE INDEX idx_reservations_sale ON public.reservations USING btree (sale_id);


--
-- Name: idx_reservations_status; Type: INDEX; Schema: public; Owner: express_sale_user
--

CREATE INDEX idx_reservations_status ON public.reservations USING btree (status);


--
-- Name: idx_sale_items_sale; Type: INDEX; Schema: public; Owner: express_sale_user
--

CREATE INDEX idx_sale_items_sale ON public.sale_items USING btree (sale_id);


--
-- Name: idx_sale_items_variant; Type: INDEX; Schema: public; Owner: express_sale_user
--

CREATE INDEX idx_sale_items_variant ON public.sale_items USING btree (variant_id);


--
-- Name: idx_sales_customer; Type: INDEX; Schema: public; Owner: express_sale_user
--

CREATE INDEX idx_sales_customer ON public.sales USING btree (customer_id);


--
-- Name: idx_sales_date; Type: INDEX; Schema: public; Owner: express_sale_user
--

CREATE INDEX idx_sales_date ON public.sales USING btree (sale_date);


--
-- Name: idx_sales_number; Type: INDEX; Schema: public; Owner: express_sale_user
--

CREATE INDEX idx_sales_number ON public.sales USING btree (sale_number);


--
-- Name: idx_sales_status; Type: INDEX; Schema: public; Owner: express_sale_user
--

CREATE INDEX idx_sales_status ON public.sales USING btree (payment_status);


--
-- Name: idx_sales_type; Type: INDEX; Schema: public; Owner: express_sale_user
--

CREATE INDEX idx_sales_type ON public.sales USING btree (sale_type);


--
-- Name: idx_sales_user; Type: INDEX; Schema: public; Owner: express_sale_user
--

CREATE INDEX idx_sales_user ON public.sales USING btree (user_id);


--
-- Name: idx_sr_actual_date; Type: INDEX; Schema: public; Owner: express_sale_user
--

CREATE INDEX idx_sr_actual_date ON public.stock_receipts USING btree (actual_delivery_date);


--
-- Name: idx_sr_expected_date; Type: INDEX; Schema: public; Owner: express_sale_user
--

CREATE INDEX idx_sr_expected_date ON public.stock_receipts USING btree (expected_delivery_date);


--
-- Name: idx_stock_movements_created_at; Type: INDEX; Schema: public; Owner: express_sale_user
--

CREATE INDEX idx_stock_movements_created_at ON public.stock_movements USING btree (created_at);


--
-- Name: idx_stock_movements_from_location; Type: INDEX; Schema: public; Owner: express_sale_user
--

CREATE INDEX idx_stock_movements_from_location ON public.stock_movements USING btree (from_location_id);


--
-- Name: idx_stock_movements_performed_by; Type: INDEX; Schema: public; Owner: express_sale_user
--

CREATE INDEX idx_stock_movements_performed_by ON public.stock_movements USING btree (performed_by);


--
-- Name: idx_stock_movements_receipt; Type: INDEX; Schema: public; Owner: express_sale_user
--

CREATE INDEX idx_stock_movements_receipt ON public.stock_movements USING btree (stock_receipt_id);


--
-- Name: idx_stock_movements_sale; Type: INDEX; Schema: public; Owner: express_sale_user
--

CREATE INDEX idx_stock_movements_sale ON public.stock_movements USING btree (sale_id);


--
-- Name: idx_stock_movements_to_location; Type: INDEX; Schema: public; Owner: express_sale_user
--

CREATE INDEX idx_stock_movements_to_location ON public.stock_movements USING btree (to_location_id);


--
-- Name: idx_stock_movements_type; Type: INDEX; Schema: public; Owner: express_sale_user
--

CREATE INDEX idx_stock_movements_type ON public.stock_movements USING btree (movement_type);


--
-- Name: idx_stock_movements_variant; Type: INDEX; Schema: public; Owner: express_sale_user
--

CREATE INDEX idx_stock_movements_variant ON public.stock_movements USING btree (variant_id);


--
-- Name: idx_stock_receipt_item_ratings_attribute; Type: INDEX; Schema: public; Owner: express_sale_user
--

CREATE INDEX idx_stock_receipt_item_ratings_attribute ON public.stock_receipt_item_ratings USING btree (attribute_type_id);


--
-- Name: idx_stock_receipt_item_ratings_item; Type: INDEX; Schema: public; Owner: express_sale_user
--

CREATE INDEX idx_stock_receipt_item_ratings_item ON public.stock_receipt_item_ratings USING btree (stock_receipt_item_id);


--
-- Name: idx_stock_receipt_items_receipt; Type: INDEX; Schema: public; Owner: express_sale_user
--

CREATE INDEX idx_stock_receipt_items_receipt ON public.stock_receipt_items USING btree (stock_receipt_id);


--
-- Name: idx_stock_receipt_items_variant; Type: INDEX; Schema: public; Owner: express_sale_user
--

CREATE INDEX idx_stock_receipt_items_variant ON public.stock_receipt_items USING btree (variant_id);


--
-- Name: idx_stock_receipts_number; Type: INDEX; Schema: public; Owner: express_sale_user
--

CREATE INDEX idx_stock_receipts_number ON public.stock_receipts USING btree (receipt_number);


--
-- Name: idx_stock_receipts_supplier; Type: INDEX; Schema: public; Owner: express_sale_user
--

CREATE INDEX idx_stock_receipts_supplier ON public.stock_receipts USING btree (supplier_id);


--
-- Name: idx_system_settings_key; Type: INDEX; Schema: public; Owner: express_sale_user
--

CREATE INDEX idx_system_settings_key ON public.system_settings USING btree (key);


--
-- Name: idx_user_sessions_login_at; Type: INDEX; Schema: public; Owner: express_sale_user
--

CREATE INDEX idx_user_sessions_login_at ON public.user_sessions USING btree (login_at);


--
-- Name: idx_user_sessions_user_id; Type: INDEX; Schema: public; Owner: express_sale_user
--

CREATE INDEX idx_user_sessions_user_id ON public.user_sessions USING btree (user_id);


--
-- Name: installment_transactions_installment_id_payment_date_index; Type: INDEX; Schema: public; Owner: express_sale_user
--

CREATE INDEX installment_transactions_installment_id_payment_date_index ON public.installment_transactions USING btree (installment_id, payment_date);


--
-- Name: jobs_queue_index; Type: INDEX; Schema: public; Owner: express_sale_user
--

CREATE INDEX jobs_queue_index ON public.jobs USING btree (queue);


--
-- Name: personal_access_tokens_expires_at_index; Type: INDEX; Schema: public; Owner: express_sale_user
--

CREATE INDEX personal_access_tokens_expires_at_index ON public.personal_access_tokens USING btree (expires_at);


--
-- Name: personal_access_tokens_tokenable_type_tokenable_id_index; Type: INDEX; Schema: public; Owner: express_sale_user
--

CREATE INDEX personal_access_tokens_tokenable_type_tokenable_id_index ON public.personal_access_tokens USING btree (tokenable_type, tokenable_id);


--
-- Name: reservation_deposits_reservation_id_payment_date_index; Type: INDEX; Schema: public; Owner: express_sale_user
--

CREATE INDEX reservation_deposits_reservation_id_payment_date_index ON public.reservation_deposits USING btree (reservation_id, payment_date);


--
-- Name: sessions_last_activity_index; Type: INDEX; Schema: public; Owner: express_sale_user
--

CREATE INDEX sessions_last_activity_index ON public.sessions USING btree (last_activity);


--
-- Name: sessions_user_id_index; Type: INDEX; Schema: public; Owner: express_sale_user
--

CREATE INDEX sessions_user_id_index ON public.sessions USING btree (user_id);


--
-- Name: stock_movements_batch_id_index; Type: INDEX; Schema: public; Owner: express_sale_user
--

CREATE INDEX stock_movements_batch_id_index ON public.stock_movements USING btree (batch_id);


--
-- Name: product_variant_locations trg_pvl_delete_update_stock; Type: TRIGGER; Schema: public; Owner: express_sale_user
--

CREATE TRIGGER trg_pvl_delete_update_stock AFTER DELETE ON public.product_variant_locations FOR EACH ROW EXECUTE FUNCTION public.recalculate_variant_stock();


--
-- Name: product_variant_locations trg_pvl_insert_update_stock; Type: TRIGGER; Schema: public; Owner: express_sale_user
--

CREATE TRIGGER trg_pvl_insert_update_stock AFTER INSERT OR UPDATE OF quantity ON public.product_variant_locations FOR EACH ROW EXECUTE FUNCTION public.recalculate_variant_stock();


--
-- Name: credits trigger_release_credit_stock; Type: TRIGGER; Schema: public; Owner: express_sale_user
--

CREATE TRIGGER trigger_release_credit_stock AFTER UPDATE ON public.credits FOR EACH ROW EXECUTE FUNCTION public.release_credit_stock();


--
-- Name: stock_receipt_item_ratings trigger_update_supplier_quality; Type: TRIGGER; Schema: public; Owner: express_sale_user
--

CREATE TRIGGER trigger_update_supplier_quality AFTER INSERT ON public.stock_receipt_item_ratings FOR EACH ROW EXECUTE FUNCTION public.update_supplier_quality_rating();


--
-- Name: stock_receipts trigger_update_supplier_stats; Type: TRIGGER; Schema: public; Owner: express_sale_user
--

CREATE TRIGGER trigger_update_supplier_stats AFTER UPDATE ON public.stock_receipts FOR EACH ROW EXECUTE FUNCTION public.update_supplier_stats_on_receipt();


--
-- Name: customers update_customers_updated_at; Type: TRIGGER; Schema: public; Owner: express_sale_user
--

CREATE TRIGGER update_customers_updated_at BEFORE UPDATE ON public.customers FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column();


--
-- Name: product_variants update_product_variants_updated_at; Type: TRIGGER; Schema: public; Owner: express_sale_user
--

CREATE TRIGGER update_product_variants_updated_at BEFORE UPDATE ON public.product_variants FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column();


--
-- Name: products update_products_updated_at; Type: TRIGGER; Schema: public; Owner: express_sale_user
--

CREATE TRIGGER update_products_updated_at BEFORE UPDATE ON public.products FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column();


--
-- Name: sales update_sales_updated_at; Type: TRIGGER; Schema: public; Owner: express_sale_user
--

CREATE TRIGGER update_sales_updated_at BEFORE UPDATE ON public.sales FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column();


--
-- Name: users update_users_updated_at; Type: TRIGGER; Schema: public; Owner: express_sale_user
--

CREATE TRIGGER update_users_updated_at BEFORE UPDATE ON public.users FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column();


--
-- Name: account_transactions account_transactions_account_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.account_transactions
    ADD CONSTRAINT account_transactions_account_id_fkey FOREIGN KEY (account_id) REFERENCES public.accounts(id);


--
-- Name: account_transactions account_transactions_created_by_fkey; Type: FK CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.account_transactions
    ADD CONSTRAINT account_transactions_created_by_fkey FOREIGN KEY (created_by) REFERENCES public.users(id);


--
-- Name: account_transactions account_transactions_expense_category_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.account_transactions
    ADD CONSTRAINT account_transactions_expense_category_id_fkey FOREIGN KEY (expense_category_id) REFERENCES public.expense_categories(id);


--
-- Name: account_transactions account_transactions_freight_forwarder_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.account_transactions
    ADD CONSTRAINT account_transactions_freight_forwarder_id_fkey FOREIGN KEY (freight_forwarder_id) REFERENCES public.freight_forwarders(id);


--
-- Name: account_transactions account_transactions_related_account_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.account_transactions
    ADD CONSTRAINT account_transactions_related_account_id_fkey FOREIGN KEY (related_account_id) REFERENCES public.accounts(id);


--
-- Name: account_transactions account_transactions_related_transaction_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.account_transactions
    ADD CONSTRAINT account_transactions_related_transaction_id_fkey FOREIGN KEY (related_transaction_id) REFERENCES public.account_transactions(id);


--
-- Name: account_transactions account_transactions_reversed_transaction_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.account_transactions
    ADD CONSTRAINT account_transactions_reversed_transaction_id_foreign FOREIGN KEY (reversed_transaction_id) REFERENCES public.account_transactions(id) ON DELETE SET NULL;


--
-- Name: account_transactions account_transactions_sale_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.account_transactions
    ADD CONSTRAINT account_transactions_sale_id_fkey FOREIGN KEY (sale_id) REFERENCES public.sales(id);


--
-- Name: account_transactions account_transactions_stock_receipt_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.account_transactions
    ADD CONSTRAINT account_transactions_stock_receipt_id_fkey FOREIGN KEY (stock_receipt_id) REFERENCES public.stock_receipts(id);


--
-- Name: account_transactions account_transactions_supplier_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.account_transactions
    ADD CONSTRAINT account_transactions_supplier_id_fkey FOREIGN KEY (supplier_id) REFERENCES public.suppliers(id);


--
-- Name: account_transactions account_transactions_transaction_type_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.account_transactions
    ADD CONSTRAINT account_transactions_transaction_type_id_fkey FOREIGN KEY (transaction_type_id) REFERENCES public.transaction_types(id);


--
-- Name: accounts accounts_account_type_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.accounts
    ADD CONSTRAINT accounts_account_type_id_fkey FOREIGN KEY (account_type_id) REFERENCES public.account_types(id);


--
-- Name: accounts accounts_created_by_fkey; Type: FK CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.accounts
    ADD CONSTRAINT accounts_created_by_fkey FOREIGN KEY (created_by) REFERENCES public.users(id);


--
-- Name: attribute_values attribute_values_attribute_type_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.attribute_values
    ADD CONSTRAINT attribute_values_attribute_type_id_fkey FOREIGN KEY (attribute_type_id) REFERENCES public.attribute_types(id) ON DELETE CASCADE;


--
-- Name: audit_logs audit_logs_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.audit_logs
    ADD CONSTRAINT audit_logs_user_id_fkey FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: categories categories_parent_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.categories
    ADD CONSTRAINT categories_parent_id_fkey FOREIGN KEY (parent_id) REFERENCES public.categories(id) ON DELETE SET NULL;


--
-- Name: credit_installments credit_installments_credit_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.credit_installments
    ADD CONSTRAINT credit_installments_credit_id_fkey FOREIGN KEY (credit_id) REFERENCES public.credits(id) ON DELETE CASCADE;


--
-- Name: credits credits_customer_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.credits
    ADD CONSTRAINT credits_customer_id_fkey FOREIGN KEY (customer_id) REFERENCES public.customers(id) ON DELETE RESTRICT;


--
-- Name: credits credits_sale_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.credits
    ADD CONSTRAINT credits_sale_id_fkey FOREIGN KEY (sale_id) REFERENCES public.sales(id) ON DELETE CASCADE;


--
-- Name: currency_rates currency_rates_created_by_fkey; Type: FK CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.currency_rates
    ADD CONSTRAINT currency_rates_created_by_fkey FOREIGN KEY (created_by) REFERENCES public.users(id);


--
-- Name: product_variant_locations fk_location; Type: FK CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.product_variant_locations
    ADD CONSTRAINT fk_location FOREIGN KEY (location_id) REFERENCES public.locations(id) ON DELETE RESTRICT;


--
-- Name: product_variant_locations fk_variant; Type: FK CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.product_variant_locations
    ADD CONSTRAINT fk_variant FOREIGN KEY (variant_id) REFERENCES public.product_variants(id) ON DELETE CASCADE;


--
-- Name: freight_forwarders freight_forwarders_coordinate_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.freight_forwarders
    ADD CONSTRAINT freight_forwarders_coordinate_id_foreign FOREIGN KEY (coordinate_id) REFERENCES public.coordinates(id) ON DELETE SET NULL;


--
-- Name: installment_transactions installment_transactions_installment_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.installment_transactions
    ADD CONSTRAINT installment_transactions_installment_id_foreign FOREIGN KEY (installment_id) REFERENCES public.credit_installments(id) ON DELETE CASCADE;


--
-- Name: installment_transactions installment_transactions_transaction_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.installment_transactions
    ADD CONSTRAINT installment_transactions_transaction_id_foreign FOREIGN KEY (transaction_id) REFERENCES public.account_transactions(id) ON DELETE CASCADE;


--
-- Name: notifications notifications_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.notifications
    ADD CONSTRAINT notifications_user_id_fkey FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: product_attributes product_attributes_attribute_type_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.product_attributes
    ADD CONSTRAINT product_attributes_attribute_type_id_fkey FOREIGN KEY (attribute_type_id) REFERENCES public.attribute_types(id) ON DELETE RESTRICT;


--
-- Name: product_attributes product_attributes_product_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.product_attributes
    ADD CONSTRAINT product_attributes_product_id_fkey FOREIGN KEY (product_id) REFERENCES public.products(id) ON DELETE CASCADE;


--
-- Name: product_variants product_variants_product_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.product_variants
    ADD CONSTRAINT product_variants_product_id_fkey FOREIGN KEY (product_id) REFERENCES public.products(id) ON DELETE CASCADE;


--
-- Name: products products_category_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.products
    ADD CONSTRAINT products_category_id_fkey FOREIGN KEY (category_id) REFERENCES public.categories(id) ON DELETE RESTRICT;


--
-- Name: products products_subcategory_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.products
    ADD CONSTRAINT products_subcategory_id_fkey FOREIGN KEY (subcategory_id) REFERENCES public.categories(id) ON DELETE SET NULL;


--
-- Name: reservation_deposits reservation_deposits_reservation_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.reservation_deposits
    ADD CONSTRAINT reservation_deposits_reservation_id_foreign FOREIGN KEY (reservation_id) REFERENCES public.reservations(id) ON DELETE CASCADE;


--
-- Name: reservation_deposits reservation_deposits_transaction_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.reservation_deposits
    ADD CONSTRAINT reservation_deposits_transaction_id_foreign FOREIGN KEY (transaction_id) REFERENCES public.account_transactions(id) ON DELETE CASCADE;


--
-- Name: reservations reservations_customer_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.reservations
    ADD CONSTRAINT reservations_customer_id_fkey FOREIGN KEY (customer_id) REFERENCES public.customers(id) ON DELETE RESTRICT;


--
-- Name: reservations reservations_sale_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.reservations
    ADD CONSTRAINT reservations_sale_id_fkey FOREIGN KEY (sale_id) REFERENCES public.sales(id) ON DELETE CASCADE;


--
-- Name: sale_items sale_items_sale_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.sale_items
    ADD CONSTRAINT sale_items_sale_id_fkey FOREIGN KEY (sale_id) REFERENCES public.sales(id) ON DELETE CASCADE;


--
-- Name: sale_items sale_items_variant_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.sale_items
    ADD CONSTRAINT sale_items_variant_id_fkey FOREIGN KEY (variant_id) REFERENCES public.product_variants(id) ON DELETE RESTRICT;


--
-- Name: sales sales_customer_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.sales
    ADD CONSTRAINT sales_customer_id_fkey FOREIGN KEY (customer_id) REFERENCES public.customers(id) ON DELETE SET NULL;


--
-- Name: sales sales_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.sales
    ADD CONSTRAINT sales_user_id_fkey FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE RESTRICT;


--
-- Name: stock_movements stock_movements_from_location_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.stock_movements
    ADD CONSTRAINT stock_movements_from_location_id_fkey FOREIGN KEY (from_location_id) REFERENCES public.locations(id) ON DELETE RESTRICT;


--
-- Name: stock_movements stock_movements_performed_by_fkey; Type: FK CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.stock_movements
    ADD CONSTRAINT stock_movements_performed_by_fkey FOREIGN KEY (performed_by) REFERENCES public.users(id) ON DELETE RESTRICT;


--
-- Name: stock_movements stock_movements_sale_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.stock_movements
    ADD CONSTRAINT stock_movements_sale_id_fkey FOREIGN KEY (sale_id) REFERENCES public.sales(id) ON DELETE SET NULL;


--
-- Name: stock_movements stock_movements_stock_receipt_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.stock_movements
    ADD CONSTRAINT stock_movements_stock_receipt_id_fkey FOREIGN KEY (stock_receipt_id) REFERENCES public.stock_receipts(id) ON DELETE SET NULL;


--
-- Name: stock_movements stock_movements_to_location_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.stock_movements
    ADD CONSTRAINT stock_movements_to_location_id_fkey FOREIGN KEY (to_location_id) REFERENCES public.locations(id) ON DELETE RESTRICT;


--
-- Name: stock_movements stock_movements_variant_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.stock_movements
    ADD CONSTRAINT stock_movements_variant_id_fkey FOREIGN KEY (variant_id) REFERENCES public.product_variants(id) ON DELETE RESTRICT;


--
-- Name: stock_receipt_item_ratings stock_receipt_item_ratings_attribute_type_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.stock_receipt_item_ratings
    ADD CONSTRAINT stock_receipt_item_ratings_attribute_type_id_fkey FOREIGN KEY (attribute_type_id) REFERENCES public.attribute_types(id);


--
-- Name: stock_receipt_item_ratings stock_receipt_item_ratings_rated_by_fkey; Type: FK CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.stock_receipt_item_ratings
    ADD CONSTRAINT stock_receipt_item_ratings_rated_by_fkey FOREIGN KEY (rated_by) REFERENCES public.users(id);


--
-- Name: stock_receipt_item_ratings stock_receipt_item_ratings_stock_receipt_item_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.stock_receipt_item_ratings
    ADD CONSTRAINT stock_receipt_item_ratings_stock_receipt_item_id_fkey FOREIGN KEY (stock_receipt_item_id) REFERENCES public.stock_receipt_items(id) ON DELETE CASCADE;


--
-- Name: stock_receipt_items stock_receipt_items_stock_receipt_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.stock_receipt_items
    ADD CONSTRAINT stock_receipt_items_stock_receipt_id_fkey FOREIGN KEY (stock_receipt_id) REFERENCES public.stock_receipts(id) ON DELETE CASCADE;


--
-- Name: stock_receipt_items stock_receipt_items_variant_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.stock_receipt_items
    ADD CONSTRAINT stock_receipt_items_variant_id_fkey FOREIGN KEY (variant_id) REFERENCES public.product_variants(id) ON DELETE RESTRICT;


--
-- Name: stock_receipts stock_receipts_created_by_fkey; Type: FK CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.stock_receipts
    ADD CONSTRAINT stock_receipts_created_by_fkey FOREIGN KEY (created_by) REFERENCES public.users(id);


--
-- Name: stock_receipts stock_receipts_freight_forwarder_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.stock_receipts
    ADD CONSTRAINT stock_receipts_freight_forwarder_id_fkey FOREIGN KEY (freight_forwarder_id) REFERENCES public.freight_forwarders(id) ON DELETE SET NULL;


--
-- Name: stock_receipts stock_receipts_supplier_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.stock_receipts
    ADD CONSTRAINT stock_receipts_supplier_id_fkey FOREIGN KEY (supplier_id) REFERENCES public.suppliers(id) ON DELETE RESTRICT;


--
-- Name: suppliers suppliers_coordinate_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.suppliers
    ADD CONSTRAINT suppliers_coordinate_id_foreign FOREIGN KEY (coordinate_id) REFERENCES public.coordinates(id) ON DELETE SET NULL;


--
-- Name: system_settings system_settings_updated_by_fkey; Type: FK CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.system_settings
    ADD CONSTRAINT system_settings_updated_by_fkey FOREIGN KEY (updated_by) REFERENCES public.users(id);


--
-- Name: user_sessions user_sessions_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.user_sessions
    ADD CONSTRAINT user_sessions_user_id_fkey FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: variant_attribute_values variant_attribute_values_attribute_value_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.variant_attribute_values
    ADD CONSTRAINT variant_attribute_values_attribute_value_id_fkey FOREIGN KEY (attribute_value_id) REFERENCES public.attribute_values(id) ON DELETE RESTRICT;


--
-- Name: variant_attribute_values variant_attribute_values_variant_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.variant_attribute_values
    ADD CONSTRAINT variant_attribute_values_variant_id_fkey FOREIGN KEY (variant_id) REFERENCES public.product_variants(id) ON DELETE CASCADE;


--
-- Name: SCHEMA public; Type: ACL; Schema: -; Owner: express_sale_user
--

REVOKE USAGE ON SCHEMA public FROM PUBLIC;


--
-- PostgreSQL database dump complete
--

\unrestrict Jkp05na3WYc5unbayrTwYzLikXDjgfk80siSxggiEAEKeZxao7JO20xbbMDDdtV

