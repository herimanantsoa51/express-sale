--
-- PostgreSQL database dump
--

\restrict WuFuGhO1nl6skH3haCRrLWWqr5QjaZUEXqGtegDAO9GlSwJ1uaFmkbTBsOnpDcD

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
    planned_expense_id bigint,
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
-- Name: activity_logs; Type: TABLE; Schema: public; Owner: express_sale_user
--

CREATE TABLE public.activity_logs (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    action character varying(255) NOT NULL,
    status character varying(255) DEFAULT 'success'::character varying NOT NULL,
    model_type character varying(255),
    model_id bigint,
    description character varying(255) NOT NULL,
    metadata json,
    error_message text,
    error_trace text,
    ip_address character varying(255),
    user_agent character varying(255),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    frontend_path text
);


ALTER TABLE public.activity_logs OWNER TO express_sale_user;

--
-- Name: activity_logs_id_seq; Type: SEQUENCE; Schema: public; Owner: express_sale_user
--

CREATE SEQUENCE public.activity_logs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.activity_logs_id_seq OWNER TO express_sale_user;

--
-- Name: activity_logs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: express_sale_user
--

ALTER SEQUENCE public.activity_logs_id_seq OWNED BY public.activity_logs.id;


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
-- Name: cash_count_denominations; Type: TABLE; Schema: public; Owner: express_sale_user
--

CREATE TABLE public.cash_count_denominations (
    id bigint NOT NULL,
    cash_count_id bigint NOT NULL,
    denomination integer NOT NULL,
    quantity integer NOT NULL,
    subtotal bigint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.cash_count_denominations OWNER TO express_sale_user;

--
-- Name: cash_count_denominations_id_seq; Type: SEQUENCE; Schema: public; Owner: express_sale_user
--

CREATE SEQUENCE public.cash_count_denominations_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.cash_count_denominations_id_seq OWNER TO express_sale_user;

--
-- Name: cash_count_denominations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: express_sale_user
--

ALTER SEQUENCE public.cash_count_denominations_id_seq OWNED BY public.cash_count_denominations.id;


--
-- Name: cash_counts; Type: TABLE; Schema: public; Owner: express_sale_user
--

CREATE TABLE public.cash_counts (
    id bigint NOT NULL,
    count_date date NOT NULL,
    created_by bigint NOT NULL,
    notes text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    total_amount bigint NOT NULL
);


ALTER TABLE public.cash_counts OWNER TO express_sale_user;

--
-- Name: cash_counts_id_seq; Type: SEQUENCE; Schema: public; Owner: express_sale_user
--

CREATE SEQUENCE public.cash_counts_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.cash_counts_id_seq OWNER TO express_sale_user;

--
-- Name: cash_counts_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: express_sale_user
--

ALTER SEQUENCE public.cash_counts_id_seq OWNED BY public.cash_counts.id;


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
-- Name: company_infos; Type: TABLE; Schema: public; Owner: express_sale_user
--

CREATE TABLE public.company_infos (
    name text NOT NULL,
    phone text,
    address text,
    email text,
    logo_path text,
    invoice_signature text,
    printer_path character varying(255) DEFAULT '/dev/usb/lp0'::character varying NOT NULL,
    auto_print_immediate_sale boolean DEFAULT false NOT NULL,
    auto_print_credit boolean DEFAULT false NOT NULL,
    auto_print_credit_payment boolean DEFAULT false NOT NULL,
    auto_print_reservation boolean DEFAULT false NOT NULL,
    auto_print_reservation_complete boolean DEFAULT false NOT NULL,
    auto_print_cash_count boolean DEFAULT false NOT NULL
);


ALTER TABLE public.company_infos OWNER TO express_sale_user;

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
    CONSTRAINT credits_status_check CHECK (((status)::text = ANY (ARRAY[('partial_paid'::character varying)::text, ('completed'::character varying)::text, ('overdue'::character varying)::text, ('defaulted'::character varying)::text, ('recovered'::character varying)::text, ('cancelled'::character varying)::text, ('active'::character varying)::text])))
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
    is_extra_customer boolean DEFAULT false,
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
    type character varying(20),
    CONSTRAINT freight_forwarders_service_score_check CHECK (((service_score >= (0)::numeric) AND (service_score <= (10)::numeric))),
    CONSTRAINT freight_forwarders_type_check CHECK (((type)::text = ANY (ARRAY[('aerien'::character varying)::text, ('maritime'::character varying)::text])))
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
    updated_at timestamp(0) without time zone,
    status character varying(20) DEFAULT 'CONFIRMED'::character varying,
    CONSTRAINT installment_transactions_status_check CHECK (((status)::text = ANY (ARRAY[('CONFIRMED'::character varying)::text, ('CANCELLED'::character varying)::text])))
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
-- Name: notification_preferences; Type: TABLE; Schema: public; Owner: express_sale_user
--

CREATE TABLE public.notification_preferences (
    id integer NOT NULL,
    user_id integer NOT NULL,
    notification_type character varying(50) NOT NULL,
    reminder_interval_days integer DEFAULT 1,
    enabled boolean DEFAULT true,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.notification_preferences OWNER TO express_sale_user;

--
-- Name: TABLE notification_preferences; Type: COMMENT; Schema: public; Owner: express_sale_user
--

COMMENT ON TABLE public.notification_preferences IS 'Préférences de notifications par utilisateur et type';


--
-- Name: COLUMN notification_preferences.notification_type; Type: COMMENT; Schema: public; Owner: express_sale_user
--

COMMENT ON COLUMN public.notification_preferences.notification_type IS 'Types: stock_low, stock_out, reservation_expiring, credit_due';


--
-- Name: COLUMN notification_preferences.reminder_interval_days; Type: COMMENT; Schema: public; Owner: express_sale_user
--

COMMENT ON COLUMN public.notification_preferences.reminder_interval_days IS 'Intervalle en jours entre les rappels pour la même alerte';


--
-- Name: notification_preferences_id_seq; Type: SEQUENCE; Schema: public; Owner: express_sale_user
--

CREATE SEQUENCE public.notification_preferences_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.notification_preferences_id_seq OWNER TO express_sale_user;

--
-- Name: notification_preferences_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: express_sale_user
--

ALTER SEQUENCE public.notification_preferences_id_seq OWNED BY public.notification_preferences.id;


--
-- Name: notifications; Type: TABLE; Schema: public; Owner: express_sale_user
--

CREATE TABLE public.notifications (
    id integer NOT NULL,
    user_id integer,
    type character varying(100) NOT NULL,
    title character varying(255) NOT NULL,
    message text NOT NULL,
    is_read boolean DEFAULT false,
    read_at timestamp without time zone,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    severity character varying(20) DEFAULT 'info'::character varying NOT NULL,
    data jsonb,
    dedup_key character varying(150),
    dismissed_at timestamp without time zone
);


ALTER TABLE public.notifications OWNER TO express_sale_user;

--
-- Name: COLUMN notifications.dismissed_at; Type: COMMENT; Schema: public; Owner: express_sale_user
--

COMMENT ON COLUMN public.notifications.dismissed_at IS 'Date de dismissal définitif (différent de read_at)';


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
-- Name: planned_expenses; Type: TABLE; Schema: public; Owner: express_sale_user
--

CREATE TABLE public.planned_expenses (
    id bigint NOT NULL,
    expense_category_id bigint NOT NULL,
    name character varying(255) NOT NULL,
    description text,
    estimated_amount numeric(15,2) NOT NULL,
    frequency character varying(255) NOT NULL,
    day_of_week smallint,
    day_of_month smallint,
    start_date date NOT NULL,
    end_date date,
    next_due_date date,
    recipient_name character varying(255),
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT planned_expenses_frequency_check CHECK (((frequency)::text = ANY (ARRAY[('daily'::character varying)::text, ('weekly'::character varying)::text, ('monthly'::character varying)::text, ('yearly'::character varying)::text])))
);


ALTER TABLE public.planned_expenses OWNER TO express_sale_user;

--
-- Name: planned_expenses_id_seq; Type: SEQUENCE; Schema: public; Owner: express_sale_user
--

CREATE SEQUENCE public.planned_expenses_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.planned_expenses_id_seq OWNER TO express_sale_user;

--
-- Name: planned_expenses_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: express_sale_user
--

ALTER SEQUENCE public.planned_expenses_id_seq OWNED BY public.planned_expenses.id;


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
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    reserved_quantity integer DEFAULT 0 NOT NULL
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
    available_quantity integer,
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
    transaction_complete_id bigint,
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
-- Name: sale_item_batches; Type: TABLE; Schema: public; Owner: express_sale_user
--

CREATE TABLE public.sale_item_batches (
    id bigint NOT NULL,
    sale_item_id bigint NOT NULL,
    batch_id bigint NOT NULL,
    quantity integer NOT NULL,
    unit_price_at_sale numeric(12,2) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    discount_at_sale real,
    status character varying(20),
    location_id integer,
    CONSTRAINT chk_sib_price_positive CHECK ((unit_price_at_sale >= (0)::numeric)),
    CONSTRAINT chk_sib_quantity_positive CHECK ((quantity > 0)),
    CONSTRAINT sale_item_batches_status_check CHECK (((status)::text = ANY (ARRAY[('reserved'::character varying)::text, ('sold'::character varying)::text, ('cancelled'::character varying)::text, ('credit'::character varying)::text])))
);


ALTER TABLE public.sale_item_batches OWNER TO express_sale_user;

--
-- Name: TABLE sale_item_batches; Type: COMMENT; Schema: public; Owner: express_sale_user
--

COMMENT ON TABLE public.sale_item_batches IS 'Traçabilité FIFO : quel batch a été vendu dans quelle vente (sans snapshot de coût)';


--
-- Name: COLUMN sale_item_batches.quantity; Type: COMMENT; Schema: public; Owner: express_sale_user
--

COMMENT ON COLUMN public.sale_item_batches.quantity IS 'Quantité vendue provenant de ce lot';


--
-- Name: COLUMN sale_item_batches.unit_price_at_sale; Type: COMMENT; Schema: public; Owner: express_sale_user
--

COMMENT ON COLUMN public.sale_item_batches.unit_price_at_sale IS 'Snapshot du prix de vente (le coût est lu depuis batch.total_unit_cost)';


--
-- Name: sale_item_batches_id_seq; Type: SEQUENCE; Schema: public; Owner: express_sale_user
--

CREATE SEQUENCE public.sale_item_batches_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.sale_item_batches_id_seq OWNER TO express_sale_user;

--
-- Name: sale_item_batches_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: express_sale_user
--

ALTER SEQUENCE public.sale_item_batches_id_seq OWNED BY public.sale_item_batches.id;


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
    status character varying(20) DEFAULT 'CONFIRMED'::character varying,
    CONSTRAINT sale_type_check CHECK (((sale_type)::text = ANY (ARRAY[('immediate'::character varying)::text, ('credit'::character varying)::text, ('reservation'::character varying)::text]))),
    CONSTRAINT sales_discount_amount_check CHECK ((discount_amount >= (0)::numeric)),
    CONSTRAINT sales_payment_method_check CHECK (((payment_method)::text = ANY (ARRAY[('cash'::character varying)::text, ('mobile_money'::character varying)::text, ('bank_transfer'::character varying)::text, ('mixed'::character varying)::text]))),
    CONSTRAINT sales_payment_status_check CHECK (((payment_status)::text = ANY (ARRAY[('pending'::character varying)::text, ('partial'::character varying)::text, ('paid'::character varying)::text, ('cancelled'::character varying)::text]))),
    CONSTRAINT sales_sale_type_check CHECK (((sale_type)::text = ANY (ARRAY[('immediate'::character varying)::text, ('reservation'::character varying)::text, ('credit'::character varying)::text]))),
    CONSTRAINT sales_status_check CHECK (((status)::text = ANY (ARRAY[('CONFIRMED'::character varying)::text, ('CANCELLED'::character varying)::text]))),
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
-- Name: stock_batches; Type: TABLE; Schema: public; Owner: express_sale_user
--

CREATE TABLE public.stock_batches (
    id bigint NOT NULL,
    variant_id bigint NOT NULL,
    stock_receipt_item_id bigint,
    batch_number character varying(50) NOT NULL,
    initial_quantity integer NOT NULL,
    remaining_quantity integer DEFAULT 0 NOT NULL,
    supplier_unit_cost numeric(12,2) NOT NULL,
    freight_cost_per_unit numeric(12,2) DEFAULT '0'::numeric NOT NULL,
    other_costs_per_unit numeric(12,2) DEFAULT '0'::numeric NOT NULL,
    total_unit_cost numeric(12,2) GENERATED ALWAYS AS (((supplier_unit_cost + freight_cost_per_unit) + other_costs_per_unit)) STORED NOT NULL,
    cost_status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    cost_validated_at timestamp(0) without time zone,
    received_date timestamp without time zone,
    reserved_quantity integer DEFAULT 0 NOT NULL,
    available_quantity integer,
    CONSTRAINT chk_batch_costs_non_negative CHECK (((freight_cost_per_unit >= (0)::numeric) AND (other_costs_per_unit >= (0)::numeric))),
    CONSTRAINT chk_batch_remaining_lte_initial CHECK ((remaining_quantity <= initial_quantity)),
    CONSTRAINT chk_batch_remaining_positive CHECK ((remaining_quantity >= 0)),
    CONSTRAINT chk_batch_supplier_cost_positive CHECK ((supplier_unit_cost > (0)::numeric)),
    CONSTRAINT stock_batches_cost_status_check CHECK (((cost_status)::text = ANY (ARRAY[('pending'::character varying)::text, ('estimated'::character varying)::text, ('validated'::character varying)::text])))
);


ALTER TABLE public.stock_batches OWNER TO express_sale_user;

--
-- Name: TABLE stock_batches; Type: COMMENT; Schema: public; Owner: express_sale_user
--

COMMENT ON TABLE public.stock_batches IS 'Lots de stock pour tracking FIFO des coûts réels';


--
-- Name: COLUMN stock_batches.initial_quantity; Type: COMMENT; Schema: public; Owner: express_sale_user
--

COMMENT ON COLUMN public.stock_batches.initial_quantity IS 'Quantité initiale du lot';


--
-- Name: COLUMN stock_batches.remaining_quantity; Type: COMMENT; Schema: public; Owner: express_sale_user
--

COMMENT ON COLUMN public.stock_batches.remaining_quantity IS 'Quantité encore disponible (diminue à chaque vente FIFO)';


--
-- Name: COLUMN stock_batches.supplier_unit_cost; Type: COMMENT; Schema: public; Owner: express_sale_user
--

COMMENT ON COLUMN public.stock_batches.supplier_unit_cost IS 'Coût unitaire payé au fournisseur (depuis stock_receipt_items)';


--
-- Name: COLUMN stock_batches.freight_cost_per_unit; Type: COMMENT; Schema: public; Owner: express_sale_user
--

COMMENT ON COLUMN public.stock_batches.freight_cost_per_unit IS 'Frais de transport par unité (répartis)';


--
-- Name: COLUMN stock_batches.other_costs_per_unit; Type: COMMENT; Schema: public; Owner: express_sale_user
--

COMMENT ON COLUMN public.stock_batches.other_costs_per_unit IS 'Autres frais par unité (manutention, stockage, etc.)';


--
-- Name: COLUMN stock_batches.total_unit_cost; Type: COMMENT; Schema: public; Owner: express_sale_user
--

COMMENT ON COLUMN public.stock_batches.total_unit_cost IS 'Coût complet par unité utilisé pour calcul des bénéfices';


--
-- Name: COLUMN stock_batches.cost_status; Type: COMMENT; Schema: public; Owner: express_sale_user
--

COMMENT ON COLUMN public.stock_batches.cost_status IS 'pending = pas réparti, estimated = recommandation calculée, validated = validé manuellement';


--
-- Name: COLUMN stock_batches.cost_validated_at; Type: COMMENT; Schema: public; Owner: express_sale_user
--

COMMENT ON COLUMN public.stock_batches.cost_validated_at IS 'Date de validation des coûts';


--
-- Name: stock_batches_id_seq; Type: SEQUENCE; Schema: public; Owner: express_sale_user
--

CREATE SEQUENCE public.stock_batches_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.stock_batches_id_seq OWNER TO express_sale_user;

--
-- Name: stock_batches_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: express_sale_user
--

ALTER SEQUENCE public.stock_batches_id_seq OWNED BY public.stock_batches.id;


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
    loss_type text,
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
    attribute_type_id bigint NOT NULL,
    conformity_rating numeric(4,2) DEFAULT 5.00,
    notes text,
    rated_by integer,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT stock_receipt_item_ratings_attribute_conformity_check CHECK (((conformity_rating >= (0)::numeric) AND (conformity_rating <= (10)::numeric)))
);


ALTER TABLE public.stock_receipt_item_ratings OWNER TO express_sale_user;

--
-- Name: TABLE stock_receipt_item_ratings; Type: COMMENT; Schema: public; Owner: express_sale_user
--

COMMENT ON TABLE public.stock_receipt_item_ratings IS 'Évaluation de la qualité des articles reçus par commande';


--
-- Name: COLUMN stock_receipt_item_ratings.conformity_rating; Type: COMMENT; Schema: public; Owner: express_sale_user
--

COMMENT ON COLUMN public.stock_receipt_item_ratings.conformity_rating IS 'Note de conformité pour un attribut spécifique (couleur, taille, etc.) sur 10';


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
    quality_rating numeric(4,2),
    quality_notes text,
    CONSTRAINT stock_receipt_items_quantity_ordered_check CHECK ((quantity_ordered >= 0)),
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
    cost_validated_at timestamp with time zone,
    cost_validated_by integer
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
-- Name: activity_logs id; Type: DEFAULT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.activity_logs ALTER COLUMN id SET DEFAULT nextval('public.activity_logs_id_seq'::regclass);


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
-- Name: cash_count_denominations id; Type: DEFAULT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.cash_count_denominations ALTER COLUMN id SET DEFAULT nextval('public.cash_count_denominations_id_seq'::regclass);


--
-- Name: cash_counts id; Type: DEFAULT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.cash_counts ALTER COLUMN id SET DEFAULT nextval('public.cash_counts_id_seq'::regclass);


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
-- Name: notification_preferences id; Type: DEFAULT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.notification_preferences ALTER COLUMN id SET DEFAULT nextval('public.notification_preferences_id_seq'::regclass);


--
-- Name: notifications id; Type: DEFAULT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.notifications ALTER COLUMN id SET DEFAULT nextval('public.notifications_id_seq'::regclass);


--
-- Name: personal_access_tokens id; Type: DEFAULT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.personal_access_tokens ALTER COLUMN id SET DEFAULT nextval('public.personal_access_tokens_id_seq'::regclass);


--
-- Name: planned_expenses id; Type: DEFAULT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.planned_expenses ALTER COLUMN id SET DEFAULT nextval('public.planned_expenses_id_seq'::regclass);


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
-- Name: sale_item_batches id; Type: DEFAULT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.sale_item_batches ALTER COLUMN id SET DEFAULT nextval('public.sale_item_batches_id_seq'::regclass);


--
-- Name: sale_items id; Type: DEFAULT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.sale_items ALTER COLUMN id SET DEFAULT nextval('public.sale_items_id_seq'::regclass);


--
-- Name: sales id; Type: DEFAULT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.sales ALTER COLUMN id SET DEFAULT nextval('public.sales_id_seq'::regclass);


--
-- Name: stock_batches id; Type: DEFAULT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.stock_batches ALTER COLUMN id SET DEFAULT nextval('public.stock_batches_id_seq'::regclass);


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

COPY public.account_transactions (id, account_id, transaction_type_id, amount, balance_before, balance_after, transaction_date, related_account_id, related_transaction_id, supplier_id, freight_forwarder_id, stock_receipt_id, expense_category_id, recipient_name, sale_id, reference_number, description, notes, created_by, created_at, reversed_transaction_id, planned_expense_id) FROM stdin;
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
\.


--
-- Data for Name: activity_logs; Type: TABLE DATA; Schema: public; Owner: express_sale_user
--

COPY public.activity_logs (id, user_id, action, status, model_type, model_id, description, metadata, error_message, error_trace, ip_address, user_agent, created_at, updated_at, frontend_path) FROM stdin;
56	4	login_success	success	App\\Models\\User	4	s'est connecté avec succès	{"username":"admin","role":"admin"}	\N	\N	127.0.0.1	Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36	2026-01-28 06:26:41	2026-01-28 06:26:41	\N
63	7	customer_created	success	App\\Models\\Customer	59	 a créé le client CL-20260128-0001	{"name":"Anonyme","is_extra_customer":false,"reliability_score":"5.00","loyalty_points":0,"credit_limit":"100000.00","is_active":true,"customer_number":"CL-20260128-0001","updated_at":"2026-01-28T07:29:29.000000Z","created_at":"2026-01-28T07:29:29.000000Z","id":59}	\N	\N	127.0.0.1	Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36	2026-01-28 07:29:29	2026-01-28 07:29:29	clients/59
70	4	login_success	success	App\\Models\\User	4	s'est connecté avec succès	{"username":"admin","role":"admin"}	\N	\N	192.168.0.199	Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36	2026-01-28 08:33:53	2026-01-28 08:33:53	\N
57	4	logout	success	App\\Models\\User	4	s'est déconnecté	{"username":"admin"}	\N	\N	127.0.0.1	Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36	2026-01-28 06:27:57	2026-01-28 06:27:57	\N
64	7	sale_created	success	App\\Models\\Sale	138	 a créé une vente rapide d'une valeur de 25000.00	{"sale_number":"VNT-20260128-0001","customer_id":59,"user_id":7,"sale_date":"2026-01-28T07:29:29.000000Z","sale_type":"immediate","subtotal":"25000.00","discount_amount":"0.00","discount_reason":null,"total_amount":"25000.00","payment_status":"paid","payment_method":"cash","notes":null,"status":"CONFIRMED","updated_at":"2026-01-28T07:29:29.000000Z","created_at":"2026-01-28T07:29:29.000000Z","id":138,"items":[{"id":183,"sale_id":138,"variant_id":34,"quantity":1,"unit_price":"25000.00","subtotal":"25000.00","created_at":"2026-01-28T10:29:29.618524Z","variant":{"id":34,"product_id":29,"sku":"NOI--OIG0","price_adjustment":"0.00","stock_quantity":10,"reserved_quantity":0,"low_stock_threshold":5,"is_active":true,"created_at":"2026-01-20T13:32:17.000000Z","updated_at":"2026-01-28T10:29:29.618524Z","image_path":null,"credit_quantity":-4,"available_quantity":10,"product":{"id":29,"name":"Noir kely","description":null,"category_id":28,"subcategory_id":29,"base_price":"25000.00","is_active":true,"created_at":"2026-01-20T13:31:24.000000Z","updated_at":"2026-01-23T13:47:39.687619Z","image_url":"http:\\/\\/localhost:8000\\/storage\\/uploads\\/images\\/2026\\/01\\/af8e4ac3-5104-43f5-a590-c84354dfd044.jpeg"}}}],"customer":{"id":59,"name":"Anonyme","phone":null,"address":null,"reliability_score":"5.00","loyalty_points":25,"credit_limit":"100000.00","notes":"[2026-01-28 07:29] +25 points: Achat de 25000 Ar","is_active":true,"created_at":"2026-01-28T07:29:29.000000Z","updated_at":"2026-01-28T10:29:29.618524Z","customer_number":"CL-20260128-0001","is_extra_customer":false},"user":{"id":7,"name":"heyo","username":"coco","role":"vendeur","is_active":true,"created_at":"2026-01-16T07:22:50.000000Z","updated_at":"2026-01-27T22:50:35.616634Z"},"transactions":[{"id":381,"account_id":9,"transaction_type_id":16,"amount":"25000.00","balance_before":"1718903.00","balance_after":"1743903.00","transaction_date":"2026-01-28T07:29:29.000000Z","related_account_id":null,"related_transaction_id":null,"supplier_id":null,"freight_forwarder_id":null,"stock_receipt_id":null,"expense_category_id":null,"recipient_name":null,"sale_id":138,"reference_number":"FAC-20260128-000001","description":"Vente #VNT-20260128-0001","notes":"","created_by":7,"created_at":"2026-01-28T10:29:29.618524Z","reversed_transaction_id":null,"planned_expense_id":null}]}	\N	\N	127.0.0.1	Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36	2026-01-28 07:29:29	2026-01-28 07:29:29	/ventes/rapide/138
71	4	file_uploaded	success	\N	\N	Image uploadée : uploads/images/2026/01/169a07ac-34c9-42b5-a926-d12cff08828f.jpg	{"path":"uploads\\/images\\/2026\\/01\\/169a07ac-34c9-42b5-a926-d12cff08828f.jpg"}	\N	\N	192.168.0.199	Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36	2026-01-28 08:35:42	2026-01-28 08:35:42	\N
58	4	login_success	success	App\\Models\\User	4	s'est connecté avec succès	{"username":"admin","role":"admin"}	\N	\N	127.0.0.1	Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36	2026-01-28 06:51:12	2026-01-28 06:51:12	\N
65	7	account_transaction_cancelled	error	App\\Models\\AccountTransaction	364	tentative non autorisée d'annuler la transaction ID 364 FAC-20260127-000016 par l'utilisateur ID 7	{"user_id":7}	Unauthorized cancellation attempt	#0 /home/christian/Programming/express-sale/backend/vendor/laravel/framework/src/Illuminate/Routing/Controller.php(54): App\\Http\\Controllers\\AccountTransactionController->cancel()\n#1 /home/christian/Programming/express-sale/backend/vendor/laravel/framework/src/Illuminate/Routing/ControllerDispatcher.php(43): Illuminate\\Routing\\Controller->callAction()\n#2 /home/christian/Programming/express-sale/backend/vendor/laravel/framework/src/Illuminate/Routing/Route.php(265): Illuminate\\Routing\\ControllerDispatcher->dispatch()\n#3 /home/christian/Programming/express-sale/backend/vendor/laravel/framework/src/Illuminate/Routing/Route.php(211): Illuminate\\Routing\\Route->runController()\n#4 /home/christian/Programming/express-sale/backend/vendor/laravel/framework/src/Illuminate/Routing/Router.php(822): Illuminate\\Routing\\Route->run()\n#5 /home/christian/Programming/express-sale/backend/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(180): Illuminate\\Routing\\Router->Illuminate\\Routing\\{closure}()\n#6 /home/christian/Programming/express-sale/backend/app/Http/Middleware/AdminNotificationsMiddleware.php(28): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}()\n#7 /home/christian/Programming/express-sale/backend/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): App\\Http\\Middleware\\AdminNotificationsMiddleware->handle()\n#8 /home/christian/Programming/express-sale/backend/vendor/laravel/framework/src/Illuminate/Routing/Middleware/SubstituteBindings.php(50): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}()\n#9 /home/christian/Programming/express-sale/backend/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Routing\\Middleware\\SubstituteBindings->handle()\n#10 /home/christian/Programming/express-sale/backend/vendor/laravel/framework/src/Illuminate/Auth/Middleware/Authenticate.php(63): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}()\n#11 /home/christian/Programming/express-sale/backend/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Auth\\Middleware\\Authenticate->handle()\n#12 /home/christian/Programming/express-sale/backend/app/Http/Middleware/SetDynamicUrl.php(26): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}()\n#13 /home/christian/Programming/express-sale/backend/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): App\\Http\\Middleware\\SetDynamicUrl->handle()\n#14 /home/christian/Programming/express-sale/backend/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(137): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}()\n#15 /home/christian/Programming/express-sale/backend/vendor/laravel/framework/src/Illuminate/Routing/Router.php(821): Illuminate\\Pipeline\\Pipeline->then()\n#16 /home/christian/Programming/express-sale/backend/vendor/laravel/framework/src/Illuminate/Routing/Router.php(800): Illuminate\\Routing\\Router->runRouteWithinStack()\n#17 /home/christian/Programming/express-sale/backend/vendor/laravel/framework/src/Illuminate/Routing/Router.php(764): Illuminate\\Routing\\Router->runRoute()\n#18 /home/christian/Programming/express-sale/backend/vendor/laravel/framework/src/Illuminate/Routing/Router.php(753): Illuminate\\Routing\\Router->dispatchToRoute()\n#19 /home/christian/Programming/express-sale/backend/vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php(200): Illuminate\\Routing\\Router->dispatch()\n#20 /home/christian/Programming/express-sale/backend/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(180): Illuminate\\Foundation\\Http\\Kernel->Illuminate\\Foundation\\Http\\{closure}()\n#21 /home/christian/Programming/express-sale/backend/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TransformsRequest.php(21): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}()\n#22 /home/christian/Programming/express-sale/backend/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/ConvertEmptyStringsToNull.php(31): Illuminate\\Foundation\\Http\\Middleware\\TransformsRequest->handle()\n#23 /home/christian/Programming/express-sale/backend/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Foundation\\Http\\Middleware\\ConvertEmptyStringsToNull->handle()\n#24 /home/christian/Programming/express-sale/backend/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TransformsRequest.php(21): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}()\n#25 /home/christian/Programming/express-sale/backend/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TrimStrings.php(51): Illuminate\\Foundation\\Http\\Middleware\\TransformsRequest->handle()\n#26 /home/christian/Programming/express-sale/backend/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Foundation\\Http\\Middleware\\TrimStrings->handle()\n#27 /home/christian/Programming/express-sale/backend/vendor/laravel/framework/src/Illuminate/Http/Middleware/ValidatePostSize.php(27): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}()\n#28 /home/christian/Programming/express-sale/backend/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Http\\Middleware\\ValidatePostSize->handle()\n#29 /home/christian/Programming/express-sale/backend/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/PreventRequestsDuringMaintenance.php(109): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}()\n#30 /home/christian/Programming/express-sale/backend/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Foundation\\Http\\Middleware\\PreventRequestsDuringMaintenance->handle()\n#31 /home/christian/Programming/express-sale/backend/vendor/laravel/framework/src/Illuminate/Http/Middleware/HandleCors.php(61): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}()\n#32 /home/christian/Programming/express-sale/backend/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Http\\Middleware\\HandleCors->handle()\n#33 /home/christian/Programming/express-sale/backend/vendor/laravel/framework/src/Illuminate/Http/Middleware/TrustProxies.php(58): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}()\n#34 /home/christian/Programming/express-sale/backend/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Http\\Middleware\\TrustProxies->handle()\n#35 /home/christian/Programming/express-sale/backend/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/InvokeDeferredCallbacks.php(22): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}()\n#36 /home/christian/Programming/express-sale/backend/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Foundation\\Http\\Middleware\\InvokeDeferredCallbacks->handle()\n#37 /home/christian/Programming/express-sale/backend/vendor/laravel/framework/src/Illuminate/Http/Middleware/ValidatePathEncoding.php(26): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}()\n#38 /home/christian/Programming/express-sale/backend/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Http\\Middleware\\ValidatePathEncoding->handle()\n#39 /home/christian/Programming/express-sale/backend/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(137): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}()\n#40 /home/christian/Programming/express-sale/backend/vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php(175): Illuminate\\Pipeline\\Pipeline->then()\n#41 /home/christian/Programming/express-sale/backend/vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php(144): Illuminate\\Foundation\\Http\\Kernel->sendRequestThroughRouter()\n#42 /home/christian/Programming/express-sale/backend/vendor/laravel/framework/src/Illuminate/Foundation/Application.php(1220): Illuminate\\Foundation\\Http\\Kernel->handle()\n#43 /home/christian/Programming/express-sale/backend/public/index.php(20): Illuminate\\Foundation\\Application->handleRequest()\n#44 /home/christian/Programming/express-sale/backend/vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php(23): require_once('...')\n#45 {main}	127.0.0.1	Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36	2026-01-28 07:30:21	2026-01-28 07:30:21	\N
72	4	product_variant_created	success	App\\Models\\ProductVariant	1	a créé une nouvelle variante (SKU: MIL--CSBG) pour le produit Milay	{"product_id":37,"product_name":"Milay","sku":"MIL--CSBG"}	\N	\N	192.168.0.199	Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36	2026-01-28 08:35:42	2026-01-28 08:35:42	/produits/37
59	4	logout	success	App\\Models\\User	4	s'est déconnecté	{"username":"admin"}	\N	\N	127.0.0.1	Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36	2026-01-28 06:51:22	2026-01-28 06:51:22	\N
66	7	sale_cancelled	success	App\\Models\\Sale	131	 a annulée la vente rapide VNT-20260127-0011	{"id":131,"sale_number":"VNT-20260127-0011","customer_id":56,"user_id":4,"sale_date":"2026-01-27T12:05:56.000000Z","sale_type":"immediate","subtotal":"350000.00","discount_amount":"0.00","discount_reason":null,"total_amount":"350000.00","payment_status":"paid","notes":null,"created_at":"2026-01-27T12:05:56.000000Z","updated_at":"2026-01-28T07:30:41.000000Z","payment_method":"cash","status":"CANCELLED","items":[{"id":176,"sale_id":131,"variant_id":33,"quantity":14,"unit_price":"25000.00","subtotal":"350000.00","created_at":"2026-01-27T15:05:56.208735Z","variant":{"id":33,"product_id":29,"sku":"NOI--4FTG","price_adjustment":"0.00","stock_quantity":17,"reserved_quantity":0,"low_stock_threshold":5,"is_active":true,"created_at":"2026-01-20T13:32:05.000000Z","updated_at":"2026-01-28T10:30:41.014354Z","image_path":null,"credit_quantity":0,"available_quantity":17,"product":{"id":29,"name":"Noir kely","description":null,"category_id":28,"subcategory_id":29,"base_price":"25000.00","is_active":true,"created_at":"2026-01-20T13:31:24.000000Z","updated_at":"2026-01-23T13:47:39.687619Z","image_url":"http:\\/\\/localhost:8000\\/storage\\/uploads\\/images\\/2026\\/01\\/af8e4ac3-5104-43f5-a590-c84354dfd044.jpeg"}}}],"customer":{"id":56,"name":"Anonyme","phone":null,"address":null,"reliability_score":"5.00","loyalty_points":350,"credit_limit":"100000.00","notes":"[2026-01-27 12:05] +350 points: Achat de 350000 Ar","is_active":true,"created_at":"2026-01-27T12:05:56.000000Z","updated_at":"2026-01-27T15:05:56.208735Z","customer_number":"CL-20260127-0005","is_extra_customer":false},"user":{"id":4,"name":"Administrateur","username":"admin","role":"admin","is_active":true,"created_at":"2025-12-27T10:28:54.853759Z","updated_at":"2025-12-27T10:28:54.853759Z"},"transactions":[{"id":364,"account_id":9,"transaction_type_id":16,"amount":"350000.00","balance_before":"1361403.00","balance_after":"1711403.00","transaction_date":"2026-01-27T12:05:56.000000Z","related_account_id":null,"related_transaction_id":null,"supplier_id":null,"freight_forwarder_id":null,"stock_receipt_id":null,"expense_category_id":null,"recipient_name":null,"sale_id":131,"reference_number":"FAC-20260127-000016","description":"Vente #VNT-20260127-0011","notes":"","created_by":4,"created_at":"2026-01-27T15:05:56.208735Z","reversed_transaction_id":null,"planned_expense_id":null}]}	\N	\N	127.0.0.1	Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36	2026-01-28 07:30:41	2026-01-28 07:30:41	/ventes/rapide/131
73	4	login_success	success	App\\Models\\User	4	s'est connecté avec succès	{"username":"admin","role":"admin"}	\N	\N	192.168.0.128	Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36	2026-01-28 08:40:46	2026-01-28 08:40:46	\N
60	4	login_success	success	App\\Models\\User	4	s'est connecté avec succès	{"username":"admin","role":"admin"}	\N	\N	127.0.0.1	Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36	2026-01-28 06:55:06	2026-01-28 06:55:06	\N
67	7	logout	success	App\\Models\\User	7	s'est déconnecté	{"username":"coco"}	\N	\N	127.0.0.1	Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36	2026-01-28 07:31:00	2026-01-28 07:31:00	\N
61	4	logout	success	App\\Models\\User	4	s'est déconnecté	{"username":"admin"}	\N	\N	127.0.0.1	Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36	2026-01-28 07:04:03	2026-01-28 07:04:03	\N
68	4	login_success	success	App\\Models\\User	4	s'est connecté avec succès	{"username":"admin","role":"admin"}	\N	\N	127.0.0.1	Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36	2026-01-28 07:31:29	2026-01-28 07:31:29	\N
62	7	login_success	success	App\\Models\\User	7	s'est connecté avec succès	{"username":"coco","role":"vendeur"}	\N	\N	127.0.0.1	Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36	2026-01-28 07:04:16	2026-01-28 07:04:16	\N
69	4	login_success	success	App\\Models\\User	4	s'est connecté avec succès	{"username":"admin","role":"admin"}	\N	\N	192.168.0.128	Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36	2026-01-28 08:32:09	2026-01-28 08:32:09	\N
53	4	logout	success	App\\Models\\User	4	s'est déconnecté	{"username":"admin"}	\N	\N	127.0.0.1	Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36	2026-01-27 21:32:10	2026-01-27 21:32:10	\N
54	4	login_success	success	App\\Models\\User	4	s'est connecté avec succès	{"username":"admin","role":"admin"}	\N	\N	127.0.0.1	Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36	2026-01-27 21:42:26	2026-01-27 21:42:26	\N
55	4	logout	success	App\\Models\\User	4	s'est déconnecté	{"username":"admin"}	\N	\N	127.0.0.1	Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36	2026-01-28 06:19:45	2026-01-28 06:19:45	\N
74	4	login_success	success	App\\Models\\User	4	s'est connecté avec succès	{"username":"admin","role":"admin"}	\N	\N	172.18.0.1	Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36	2026-01-28 15:27:36	2026-01-28 15:27:36	\N
75	4	product_variant_created	success	App\\Models\\ProductVariant	2	a créé une nouvelle variante (SKU: JEA--RHSQ) pour le produit Jean fitness	{"product_id":30,"product_name":"Jean fitness","sku":"JEA--RHSQ"}	\N	\N	172.18.0.1	Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36	2026-01-28 15:27:55	2026-01-28 15:27:55	produits/30
76	4	freight_forwarder_created	success	App\\Models\\FreightForwarder	1	Transitaire créé : vaovao	{"name":"vaovao","type":"aerien","logo_url":null,"contact":null,"notes":null,"coordinate_id":null,"is_active":true,"updated_at":"2026-01-28T15:28:30.000000Z","created_at":"2026-01-28T15:28:30.000000Z","id":1}	\N	\N	172.18.0.1	Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36	2026-01-28 15:28:30	2026-01-28 15:28:30	/transitaires/1
77	4	stock_receipt_created	success	App\\Models\\StockReceipt	1	a créé un réapprovisionnement	{"receipt_number":"RCP-20260128-0001","supplier_id":6,"freight_forwarder_id":1,"expected_delivery_date":"2026-01-31T00:00:00.000000Z","total_cost_ariary":"1022000.00","status":"pending","notes":null,"created_by":4,"updated_at":"2026-01-28T15:33:50.000000Z","created_at":"2026-01-28T15:33:50.000000Z","id":1,"items":[{"id":1,"stock_receipt_id":1,"variant_id":2,"quantity_ordered":10,"quantity_received":0,"unit_cost_ariary":"102200.00","notes":null,"created_at":"2026-01-28 15:33:50.011066","quality_rating":null,"quality_notes":null,"variant":{"id":2,"product_id":30,"sku":"JEA--RHSQ","price_adjustment":"0.00","stock_quantity":0,"reserved_quantity":0,"low_stock_threshold":5,"is_active":true,"created_at":"2026-01-28T15:27:55.000000Z","updated_at":"2026-01-28T15:27:55.000000Z","image_path":null,"credit_quantity":0,"available_quantity":0,"product":{"id":30,"name":"Jean fitness","description":null,"category_id":28,"subcategory_id":30,"base_price":"20000.00","is_active":true,"created_at":"2026-01-20T13:34:54.000000Z","updated_at":"2026-01-20T13:34:54.000000Z","image_url":"http:\\/\\/localhost:8000\\/storage\\/uploads\\/images\\/2026\\/01\\/c21574d8-bcdc-429a-8817-28c3d16b4d33.jpeg"}}}],"supplier":{"id":6,"name":"dhhd","wechat":"fashion aa","profile":null,"contact":"+972918991","accessibility_notes":null,"reliability_score":"5.00","logo_url":"http:\\/\\/localhost:8000\\/storage\\/uploads\\/images\\/2026\\/01\\/7cd544b9-a8e1-4bb9-ae78-df360ed2a1b5.png","is_active":true,"created_at":"2026-01-14T04:13:10.000000Z","updated_at":"2026-01-27T14:28:10.000000Z","coordinate_id":2,"coordinate":{"id":2,"country":"MADAGASCAR","city":"Antananarivo","is_active":true,"created_at":"2025-12-27T15:15:24.000000Z","updated_at":"2025-12-27T15:15:24.000000Z","full_location":"Antananarivo, MADAGASCAR"}},"freight_forwarder":{"id":1,"name":"vaovao","logo_url":null,"contact":null,"service_score":"5.00","notes":null,"is_active":true,"created_at":"2026-01-28T15:28:30.000000Z","updated_at":"2026-01-28T15:28:30.000000Z","coordinate_id":null,"total_shipments":0,"total_items_shipped":0,"total_items_delivered":0,"service_rating_sum":"0.00","service_rating_count":0,"total_value_shipped":"0.00","total_value_delivered":"0.00","weighted_service_sum":"0.00","total_weighted_shipment_value":"0.00","type":"aerien","coordinate":{"country":"","city":"","full_location":", "}}}	\N	\N	172.18.0.1	Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36	2026-01-28 15:33:50	2026-01-28 15:33:50	reapprovisionnements/1
78	4	stock_receipt_shipped	success	App\\Models\\StockReceipt	1	a marqué la réapprovisionnement RCP-20260128-0001 envoyé	{"id":1,"receipt_number":"RCP-20260128-0001","supplier_id":6,"freight_forwarder_id":1,"total_cost_ariary":"1022000.00","status":"sent","notes":null,"created_by":4,"created_at":"2026-01-28T15:33:50.000000Z","updated_at":"2026-01-28T15:34:02.000000Z","expected_delivery_date":"2026-01-31T00:00:00.000000Z","actual_delivery_date":null,"cost_validated_at":null,"cost_validated_by":null}	\N	\N	172.18.0.1	Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36	2026-01-28 15:34:02	2026-01-28 15:34:02	reapprovisionnements/1
79	4	stock_receipt_in_transit	success	App\\Models\\StockReceipt	1	a marqué la réapprovisionnement RCP-20260128-0001 en transit	{"id":1,"receipt_number":"RCP-20260128-0001","supplier_id":6,"freight_forwarder_id":1,"total_cost_ariary":"1022000.00","status":"in_transit","notes":null,"created_by":4,"created_at":"2026-01-28T15:33:50.000000Z","updated_at":"2026-01-28T15:34:04.000000Z","expected_delivery_date":"2026-01-31T00:00:00.000000Z","actual_delivery_date":null,"cost_validated_at":null,"cost_validated_by":null}	\N	\N	172.18.0.1	Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36	2026-01-28 15:34:04	2026-01-28 15:34:04	reapprovisionnements/1
80	4	stock_receipt_arrived	success	App\\Models\\StockReceipt	1	a marqué la réapprovisionnement RCP-20260128-0001 en arrivé	{"id":1,"receipt_number":"RCP-20260128-0001","supplier_id":6,"freight_forwarder_id":1,"total_cost_ariary":"1022000.00","status":"arrived","notes":null,"created_by":4,"created_at":"2026-01-28T15:33:50.000000Z","updated_at":"2026-01-28T15:34:06.000000Z","expected_delivery_date":"2026-01-31T00:00:00.000000Z","actual_delivery_date":"2026-01-28T15:34:06.000000Z","cost_validated_at":null,"cost_validated_by":null}	\N	\N	172.18.0.1	Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36	2026-01-28 15:34:06	2026-01-28 15:34:06	reapprovisionnements/1
81	4	stock_receipt_payment	success	App\\Models\\AccountTransaction	2	a payer 56000.00 pour le réapprovisionnement RCP-20260128-0001	{"id":2,"account_id":11,"transaction_type_id":17,"amount":"56000.00","balance_before":"4405700.00","balance_after":"4349700.00","transaction_date":"2026-01-28T00:00:00.000000Z","related_account_id":null,"related_transaction_id":null,"supplier_id":null,"freight_forwarder_id":1,"stock_receipt_id":1,"expense_category_id":6,"recipient_name":"vaovao","sale_id":null,"reference_number":"RE-20260128-000002","description":"D\\u00e9pense Transport & Transit \\u00e0 vaovao","notes":null,"created_by":4,"created_at":"2026-01-28T15:34:23.000000Z","reversed_transaction_id":null,"planned_expense_id":null}	\N	\N	172.18.0.1	Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36	2026-01-28 15:34:23	2026-01-28 15:34:23	transactions/2
82	4	stock_receipt_rated	success	App\\Models\\StockReceipt	1	a évalué la réapprovisionnement RCP-20260128-0001	{"id":1,"receipt_number":"RCP-20260128-0001","supplier_id":6,"freight_forwarder_id":1,"total_cost_ariary":"1022000.00","status":"rated","notes":null,"created_by":4,"created_at":"2026-01-28T15:33:50.000000Z","updated_at":"2026-01-28T15:34:33.000000Z","expected_delivery_date":"2026-01-31T00:00:00.000000Z","actual_delivery_date":"2026-01-28T15:34:06.000000Z","cost_validated_at":null,"cost_validated_by":null,"supplier":{"id":6,"name":"dhhd","wechat":"fashion aa","profile":null,"contact":"+972918991","accessibility_notes":null,"reliability_score":"9.00","logo_url":"http:\\/\\/localhost:8000\\/storage\\/uploads\\/images\\/2026\\/01\\/7cd544b9-a8e1-4bb9-ae78-df360ed2a1b5.png","is_active":true,"created_at":"2026-01-14T04:13:10.000000Z","updated_at":"2026-01-28T15:34:33.000000Z","coordinate_id":2,"coordinate":{"id":2,"country":"MADAGASCAR","city":"Antananarivo","is_active":true,"created_at":"2025-12-27T15:15:24.000000Z","updated_at":"2025-12-27T15:15:24.000000Z","full_location":"Antananarivo, MADAGASCAR"}},"items":[{"id":1,"stock_receipt_id":1,"variant_id":2,"quantity_ordered":10,"quantity_received":10,"unit_cost_ariary":"102200.00","notes":null,"created_at":"2026-01-28 15:33:50.011066","quality_rating":"9.00","quality_notes":null,"ratings":[{"id":1,"stock_receipt_item_id":1,"attribute_type_id":16,"conformity_rating":"9.00","notes":null,"rated_by":{"id":4,"name":"Administrateur","username":"admin","role":"admin","is_active":true,"created_at":"2025-12-27T10:28:54.853759Z","updated_at":"2025-12-27T10:28:54.853759Z"},"created_at":"2026-01-28T15:34:33.000000Z","attribute_type":{"id":16,"name":"color","display_name":"couleur","input_type":"select","created_at":"2026-01-14 09:24:20.639543"}},{"id":2,"stock_receipt_item_id":1,"attribute_type_id":18,"conformity_rating":"9.00","notes":null,"rated_by":{"id":4,"name":"Administrateur","username":"admin","role":"admin","is_active":true,"created_at":"2025-12-27T10:28:54.853759Z","updated_at":"2025-12-27T10:28:54.853759Z"},"created_at":"2026-01-28T15:34:33.000000Z","attribute_type":{"id":18,"name":"size","display_name":"Taille","input_type":"select","created_at":"2026-01-14 09:28:55.488978"}}],"variant":{"id":2,"product_id":30,"sku":"JEA--RHSQ","price_adjustment":"0.00","stock_quantity":0,"reserved_quantity":0,"low_stock_threshold":5,"is_active":true,"created_at":"2026-01-28T15:27:55.000000Z","updated_at":"2026-01-28T15:27:55.000000Z","image_path":null,"credit_quantity":0,"available_quantity":0,"product":{"id":30,"name":"Jean fitness","description":null,"category_id":28,"subcategory_id":30,"base_price":"20000.00","is_active":true,"created_at":"2026-01-20T13:34:54.000000Z","updated_at":"2026-01-20T13:34:54.000000Z","image_url":"http:\\/\\/localhost:8000\\/storage\\/uploads\\/images\\/2026\\/01\\/c21574d8-bcdc-429a-8817-28c3d16b4d33.jpeg"}}}]}	\N	\N	172.18.0.1	Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36	2026-01-28 15:34:33	2026-01-28 15:34:33	reapprovisionnements/1
83	4	stock_receipt_cost_distributed	success	App\\Models\\StockReceipt	1	a répartit les couts du réapprovisionnement RCP-20260128-0001	{"id":1,"receipt_number":"RCP-20260128-0001","supplier_id":6,"freight_forwarder_id":1,"total_cost_ariary":"1022000.00","status":"cost_allocated","notes":null,"created_by":4,"created_at":"2026-01-28T15:33:50.000000Z","updated_at":"2026-01-28T15:34:42.000000Z","expected_delivery_date":"2026-01-31T00:00:00.000000Z","actual_delivery_date":"2026-01-28T15:34:06.000000Z","cost_validated_at":"2026-01-28T00:00:00.000000Z","cost_validated_by":4,"items":[{"id":1,"stock_receipt_id":1,"variant_id":2,"quantity_ordered":10,"quantity_received":10,"unit_cost_ariary":"102200.00","notes":null,"created_at":"2026-01-28 15:33:50.011066","quality_rating":"9.00","quality_notes":null}]}	\N	\N	172.18.0.1	Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36	2026-01-28 15:34:42	2026-01-28 15:34:42	reapprovisionnements/1
84	4	stock_receipt_validated	success	App\\Models\\StockReceipt	1	a marqué la réapprovisionnement RCP-20260128-0001 en validé	{"id":1,"receipt_number":"RCP-20260128-0001","supplier_id":6,"freight_forwarder_id":1,"total_cost_ariary":"1022000.00","status":"validated","notes":null,"created_by":4,"created_at":"2026-01-28T15:33:50.000000Z","updated_at":"2026-01-28T15:35:04.000000Z","expected_delivery_date":"2026-01-31T00:00:00.000000Z","actual_delivery_date":"2026-01-28T15:34:06.000000Z","cost_validated_at":"2026-01-28T00:00:00.000000Z","cost_validated_by":4,"supplier":{"id":6,"name":"dhhd","wechat":"fashion aa","profile":null,"contact":"+972918991","accessibility_notes":null,"reliability_score":"9.00","logo_url":"http:\\/\\/localhost:8000\\/storage\\/uploads\\/images\\/2026\\/01\\/7cd544b9-a8e1-4bb9-ae78-df360ed2a1b5.png","is_active":true,"created_at":"2026-01-14T04:13:10.000000Z","updated_at":"2026-01-28T15:34:33.000000Z","coordinate_id":2,"coordinate":{"id":2,"country":"MADAGASCAR","city":"Antananarivo","is_active":true,"created_at":"2025-12-27T15:15:24.000000Z","updated_at":"2025-12-27T15:15:24.000000Z","full_location":"Antananarivo, MADAGASCAR"}},"freight_forwarder":{"id":1,"name":"vaovao","logo_url":null,"contact":null,"service_score":"10.00","notes":null,"is_active":true,"created_at":"2026-01-28T15:28:30.000000Z","updated_at":"2026-01-28T15:35:04.000000Z","coordinate_id":null,"total_shipments":1,"total_items_shipped":0,"total_items_delivered":0,"service_rating_sum":"0.00","service_rating_count":0,"total_value_shipped":"0.00","total_value_delivered":"0.00","weighted_service_sum":10220000,"total_weighted_shipment_value":1022000,"type":"aerien","coordinate":{"country":"","city":"","full_location":", "}},"items":[{"id":1,"stock_receipt_id":1,"variant_id":2,"quantity_ordered":10,"quantity_received":10,"unit_cost_ariary":"102200.00","notes":null,"created_at":"2026-01-28 15:33:50.011066","quality_rating":"9.00","quality_notes":null}]}	\N	\N	172.18.0.1	Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36	2026-01-28 15:35:04	2026-01-28 15:35:04	reapprovisionnements/1
85	4	stock_loss_declared	success	App\\Models\\StockMovement	2	 a déclaré des pertes de produits de Entrepôt principal 	{"variant_id":2,"from_location_id":10,"to_location_id":null,"quantity":1,"movement_type":"loss","loss_type":"theft","performed_by":4,"reason":"tsy haiko eh","notes":null,"created_at":"2026-01-28T15:36:00.000000Z","id":2,"from_location":{"id":10,"name":"Entrep\\u00f4t principal","code":"ENTREP\\u00d4T-PRINCIPAL","warehouse":"Entrepot","aisle":null,"shelf":null,"bin":null,"description":null,"capacity":null,"is_active":true,"created_at":"2026-01-20T13:05:11.000000Z","updated_at":"2026-01-20T13:05:11.000000Z"}}	\N	\N	172.18.0.1	Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36	2026-01-28 15:36:00	2026-01-28 15:36:00	movements-stock
86	4	stock_transferred	error	\N	\N	transfert échoué	{"attributes":{},"request":{},"query":{},"server":{},"files":{},"cookies":{},"headers":{}}	Property [fromLocation] does not exist on this collection instance.	#0 /var/www/app/Http/Controllers/StockMovementController.php(349): Illuminate\\Support\\Collection->__get('fromLocation')\n#1 /var/www/vendor/laravel/framework/src/Illuminate/Routing/Controller.php(54): App\\Http\\Controllers\\StockMovementController->bulkTransfer(Object(Illuminate\\Http\\Request))\n#2 /var/www/vendor/laravel/framework/src/Illuminate/Routing/ControllerDispatcher.php(43): Illuminate\\Routing\\Controller->callAction('bulkTransfer', Array)\n#3 /var/www/vendor/laravel/framework/src/Illuminate/Routing/Route.php(265): Illuminate\\Routing\\ControllerDispatcher->dispatch(Object(Illuminate\\Routing\\Route), Object(App\\Http\\Controllers\\StockMovementController), 'bulkTransfer')\n#4 /var/www/vendor/laravel/framework/src/Illuminate/Routing/Route.php(211): Illuminate\\Routing\\Route->runController()\n#5 /var/www/vendor/laravel/framework/src/Illuminate/Routing/Router.php(822): Illuminate\\Routing\\Route->run()\n#6 /var/www/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(180): Illuminate\\Routing\\Router->Illuminate\\Routing\\{closure}(Object(Illuminate\\Http\\Request))\n#7 /var/www/app/Http/Middleware/AdminNotificationsMiddleware.php(28): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}(Object(Illuminate\\Http\\Request))\n#8 /var/www/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): App\\Http\\Middleware\\AdminNotificationsMiddleware->handle(Object(Illuminate\\Http\\Request), Object(Closure))\n#9 /var/www/vendor/laravel/framework/src/Illuminate/Routing/Middleware/SubstituteBindings.php(50): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}(Object(Illuminate\\Http\\Request))\n#10 /var/www/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Routing\\Middleware\\SubstituteBindings->handle(Object(Illuminate\\Http\\Request), Object(Closure))\n#11 /var/www/vendor/laravel/framework/src/Illuminate/Auth/Middleware/Authenticate.php(63): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}(Object(Illuminate\\Http\\Request))\n#12 /var/www/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Auth\\Middleware\\Authenticate->handle(Object(Illuminate\\Http\\Request), Object(Closure), 'sanctum')\n#13 /var/www/app/Http/Middleware/SetDynamicUrl.php(26): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}(Object(Illuminate\\Http\\Request))\n#14 /var/www/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): App\\Http\\Middleware\\SetDynamicUrl->handle(Object(Illuminate\\Http\\Request), Object(Closure))\n#15 /var/www/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(137): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}(Object(Illuminate\\Http\\Request))\n#16 /var/www/vendor/laravel/framework/src/Illuminate/Routing/Router.php(821): Illuminate\\Pipeline\\Pipeline->then(Object(Closure))\n#17 /var/www/vendor/laravel/framework/src/Illuminate/Routing/Router.php(800): Illuminate\\Routing\\Router->runRouteWithinStack(Object(Illuminate\\Routing\\Route), Object(Illuminate\\Http\\Request))\n#18 /var/www/vendor/laravel/framework/src/Illuminate/Routing/Router.php(764): Illuminate\\Routing\\Router->runRoute(Object(Illuminate\\Http\\Request), Object(Illuminate\\Routing\\Route))\n#19 /var/www/vendor/laravel/framework/src/Illuminate/Routing/Router.php(753): Illuminate\\Routing\\Router->dispatchToRoute(Object(Illuminate\\Http\\Request))\n#20 /var/www/vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php(200): Illuminate\\Routing\\Router->dispatch(Object(Illuminate\\Http\\Request))\n#21 /var/www/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(180): Illuminate\\Foundation\\Http\\Kernel->Illuminate\\Foundation\\Http\\{closure}(Object(Illuminate\\Http\\Request))\n#22 /var/www/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TransformsRequest.php(21): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}(Object(Illuminate\\Http\\Request))\n#23 /var/www/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/ConvertEmptyStringsToNull.php(31): Illuminate\\Foundation\\Http\\Middleware\\TransformsRequest->handle(Object(Illuminate\\Http\\Request), Object(Closure))\n#24 /var/www/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Foundation\\Http\\Middleware\\ConvertEmptyStringsToNull->handle(Object(Illuminate\\Http\\Request), Object(Closure))\n#25 /var/www/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TransformsRequest.php(21): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}(Object(Illuminate\\Http\\Request))\n#26 /var/www/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TrimStrings.php(51): Illuminate\\Foundation\\Http\\Middleware\\TransformsRequest->handle(Object(Illuminate\\Http\\Request), Object(Closure))\n#27 /var/www/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Foundation\\Http\\Middleware\\TrimStrings->handle(Object(Illuminate\\Http\\Request), Object(Closure))\n#28 /var/www/vendor/laravel/framework/src/Illuminate/Http/Middleware/ValidatePostSize.php(27): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}(Object(Illuminate\\Http\\Request))\n#29 /var/www/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Http\\Middleware\\ValidatePostSize->handle(Object(Illuminate\\Http\\Request), Object(Closure))\n#30 /var/www/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/PreventRequestsDuringMaintenance.php(109): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}(Object(Illuminate\\Http\\Request))\n#31 /var/www/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Foundation\\Http\\Middleware\\PreventRequestsDuringMaintenance->handle(Object(Illuminate\\Http\\Request), Object(Closure))\n#32 /var/www/vendor/laravel/framework/src/Illuminate/Http/Middleware/HandleCors.php(61): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}(Object(Illuminate\\Http\\Request))\n#33 /var/www/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Http\\Middleware\\HandleCors->handle(Object(Illuminate\\Http\\Request), Object(Closure))\n#34 /var/www/vendor/laravel/framework/src/Illuminate/Http/Middleware/TrustProxies.php(58): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}(Object(Illuminate\\Http\\Request))\n#35 /var/www/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Http\\Middleware\\TrustProxies->handle(Object(Illuminate\\Http\\Request), Object(Closure))\n#36 /var/www/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/InvokeDeferredCallbacks.php(22): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}(Object(Illuminate\\Http\\Request))\n#37 /var/www/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Foundation\\Http\\Middleware\\InvokeDeferredCallbacks->handle(Object(Illuminate\\Http\\Request), Object(Closure))\n#38 /var/www/vendor/laravel/framework/src/Illuminate/Http/Middleware/ValidatePathEncoding.php(26): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}(Object(Illuminate\\Http\\Request))\n#39 /var/www/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Http\\Middleware\\ValidatePathEncoding->handle(Object(Illuminate\\Http\\Request), Object(Closure))\n#40 /var/www/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(137): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}(Object(Illuminate\\Http\\Request))\n#41 /var/www/vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php(175): Illuminate\\Pipeline\\Pipeline->then(Object(Closure))\n#42 /var/www/vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php(144): Illuminate\\Foundation\\Http\\Kernel->sendRequestThroughRouter(Object(Illuminate\\Http\\Request))\n#43 /var/www/vendor/laravel/framework/src/Illuminate/Foundation/Application.php(1220): Illuminate\\Foundation\\Http\\Kernel->handle(Object(Illuminate\\Http\\Request))\n#44 /var/www/public/index.php(20): Illuminate\\Foundation\\Application->handleRequest(Object(Illuminate\\Http\\Request))\n#45 /var/www/vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php(23): require_once('/var/www/public...')\n#46 {main}	172.18.0.1	Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36	2026-01-28 15:36:49	2026-01-28 15:36:49	\N
94	4	customer_created	success	App\\Models\\Customer	2	 a créé le client CL-20260128-0002	{"name":"kil milay","phone":null,"address":null,"credit_limit":"0.00","notes":null,"is_active":true,"is_extra_customer":true,"reliability_score":"5.00","loyalty_points":0,"customer_number":"CL-20260128-0002","updated_at":"2026-01-28T15:50:13.000000Z","created_at":"2026-01-28T15:50:13.000000Z","id":2}	\N	\N	172.18.0.4	Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36	2026-01-28 15:50:13	2026-01-28 15:50:13	clients/2
87	4	stock_transferred	error	\N	\N	transfert échoué	{"attributes":{},"request":{},"query":{},"server":{},"files":{},"cookies":{},"headers":{}}	Property [fromLocation] does not exist on this collection instance.	#0 /var/www/app/Http/Controllers/StockMovementController.php(349): Illuminate\\Support\\Collection->__get('fromLocation')\n#1 /var/www/vendor/laravel/framework/src/Illuminate/Routing/Controller.php(54): App\\Http\\Controllers\\StockMovementController->bulkTransfer(Object(Illuminate\\Http\\Request))\n#2 /var/www/vendor/laravel/framework/src/Illuminate/Routing/ControllerDispatcher.php(43): Illuminate\\Routing\\Controller->callAction('bulkTransfer', Array)\n#3 /var/www/vendor/laravel/framework/src/Illuminate/Routing/Route.php(265): Illuminate\\Routing\\ControllerDispatcher->dispatch(Object(Illuminate\\Routing\\Route), Object(App\\Http\\Controllers\\StockMovementController), 'bulkTransfer')\n#4 /var/www/vendor/laravel/framework/src/Illuminate/Routing/Route.php(211): Illuminate\\Routing\\Route->runController()\n#5 /var/www/vendor/laravel/framework/src/Illuminate/Routing/Router.php(822): Illuminate\\Routing\\Route->run()\n#6 /var/www/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(180): Illuminate\\Routing\\Router->Illuminate\\Routing\\{closure}(Object(Illuminate\\Http\\Request))\n#7 /var/www/app/Http/Middleware/AdminNotificationsMiddleware.php(28): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}(Object(Illuminate\\Http\\Request))\n#8 /var/www/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): App\\Http\\Middleware\\AdminNotificationsMiddleware->handle(Object(Illuminate\\Http\\Request), Object(Closure))\n#9 /var/www/vendor/laravel/framework/src/Illuminate/Routing/Middleware/SubstituteBindings.php(50): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}(Object(Illuminate\\Http\\Request))\n#10 /var/www/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Routing\\Middleware\\SubstituteBindings->handle(Object(Illuminate\\Http\\Request), Object(Closure))\n#11 /var/www/vendor/laravel/framework/src/Illuminate/Auth/Middleware/Authenticate.php(63): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}(Object(Illuminate\\Http\\Request))\n#12 /var/www/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Auth\\Middleware\\Authenticate->handle(Object(Illuminate\\Http\\Request), Object(Closure), 'sanctum')\n#13 /var/www/app/Http/Middleware/SetDynamicUrl.php(26): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}(Object(Illuminate\\Http\\Request))\n#14 /var/www/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): App\\Http\\Middleware\\SetDynamicUrl->handle(Object(Illuminate\\Http\\Request), Object(Closure))\n#15 /var/www/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(137): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}(Object(Illuminate\\Http\\Request))\n#16 /var/www/vendor/laravel/framework/src/Illuminate/Routing/Router.php(821): Illuminate\\Pipeline\\Pipeline->then(Object(Closure))\n#17 /var/www/vendor/laravel/framework/src/Illuminate/Routing/Router.php(800): Illuminate\\Routing\\Router->runRouteWithinStack(Object(Illuminate\\Routing\\Route), Object(Illuminate\\Http\\Request))\n#18 /var/www/vendor/laravel/framework/src/Illuminate/Routing/Router.php(764): Illuminate\\Routing\\Router->runRoute(Object(Illuminate\\Http\\Request), Object(Illuminate\\Routing\\Route))\n#19 /var/www/vendor/laravel/framework/src/Illuminate/Routing/Router.php(753): Illuminate\\Routing\\Router->dispatchToRoute(Object(Illuminate\\Http\\Request))\n#20 /var/www/vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php(200): Illuminate\\Routing\\Router->dispatch(Object(Illuminate\\Http\\Request))\n#21 /var/www/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(180): Illuminate\\Foundation\\Http\\Kernel->Illuminate\\Foundation\\Http\\{closure}(Object(Illuminate\\Http\\Request))\n#22 /var/www/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TransformsRequest.php(21): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}(Object(Illuminate\\Http\\Request))\n#23 /var/www/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/ConvertEmptyStringsToNull.php(31): Illuminate\\Foundation\\Http\\Middleware\\TransformsRequest->handle(Object(Illuminate\\Http\\Request), Object(Closure))\n#24 /var/www/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Foundation\\Http\\Middleware\\ConvertEmptyStringsToNull->handle(Object(Illuminate\\Http\\Request), Object(Closure))\n#25 /var/www/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TransformsRequest.php(21): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}(Object(Illuminate\\Http\\Request))\n#26 /var/www/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TrimStrings.php(51): Illuminate\\Foundation\\Http\\Middleware\\TransformsRequest->handle(Object(Illuminate\\Http\\Request), Object(Closure))\n#27 /var/www/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Foundation\\Http\\Middleware\\TrimStrings->handle(Object(Illuminate\\Http\\Request), Object(Closure))\n#28 /var/www/vendor/laravel/framework/src/Illuminate/Http/Middleware/ValidatePostSize.php(27): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}(Object(Illuminate\\Http\\Request))\n#29 /var/www/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Http\\Middleware\\ValidatePostSize->handle(Object(Illuminate\\Http\\Request), Object(Closure))\n#30 /var/www/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/PreventRequestsDuringMaintenance.php(109): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}(Object(Illuminate\\Http\\Request))\n#31 /var/www/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Foundation\\Http\\Middleware\\PreventRequestsDuringMaintenance->handle(Object(Illuminate\\Http\\Request), Object(Closure))\n#32 /var/www/vendor/laravel/framework/src/Illuminate/Http/Middleware/HandleCors.php(61): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}(Object(Illuminate\\Http\\Request))\n#33 /var/www/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Http\\Middleware\\HandleCors->handle(Object(Illuminate\\Http\\Request), Object(Closure))\n#34 /var/www/vendor/laravel/framework/src/Illuminate/Http/Middleware/TrustProxies.php(58): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}(Object(Illuminate\\Http\\Request))\n#35 /var/www/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Http\\Middleware\\TrustProxies->handle(Object(Illuminate\\Http\\Request), Object(Closure))\n#36 /var/www/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/InvokeDeferredCallbacks.php(22): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}(Object(Illuminate\\Http\\Request))\n#37 /var/www/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Foundation\\Http\\Middleware\\InvokeDeferredCallbacks->handle(Object(Illuminate\\Http\\Request), Object(Closure))\n#38 /var/www/vendor/laravel/framework/src/Illuminate/Http/Middleware/ValidatePathEncoding.php(26): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}(Object(Illuminate\\Http\\Request))\n#39 /var/www/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Http\\Middleware\\ValidatePathEncoding->handle(Object(Illuminate\\Http\\Request), Object(Closure))\n#40 /var/www/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(137): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}(Object(Illuminate\\Http\\Request))\n#41 /var/www/vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php(175): Illuminate\\Pipeline\\Pipeline->then(Object(Closure))\n#42 /var/www/vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php(144): Illuminate\\Foundation\\Http\\Kernel->sendRequestThroughRouter(Object(Illuminate\\Http\\Request))\n#43 /var/www/vendor/laravel/framework/src/Illuminate/Foundation/Application.php(1220): Illuminate\\Foundation\\Http\\Kernel->handle(Object(Illuminate\\Http\\Request))\n#44 /var/www/public/index.php(20): Illuminate\\Foundation\\Application->handleRequest(Object(Illuminate\\Http\\Request))\n#45 /var/www/vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php(23): require_once('/var/www/public...')\n#46 {main}	172.18.0.1	Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36	2026-01-28 15:38:34	2026-01-28 15:38:34	\N
88	4	stock_transferred	error	\N	\N	transfert échoué	{"attributes":{},"request":{},"query":{},"server":{},"files":{},"cookies":{},"headers":{}}	Property [fromLocation] does not exist on this collection instance.	#0 /var/www/app/Http/Controllers/StockMovementController.php(349): Illuminate\\Support\\Collection->__get('fromLocation')\n#1 /var/www/vendor/laravel/framework/src/Illuminate/Routing/Controller.php(54): App\\Http\\Controllers\\StockMovementController->bulkTransfer(Object(Illuminate\\Http\\Request))\n#2 /var/www/vendor/laravel/framework/src/Illuminate/Routing/ControllerDispatcher.php(43): Illuminate\\Routing\\Controller->callAction('bulkTransfer', Array)\n#3 /var/www/vendor/laravel/framework/src/Illuminate/Routing/Route.php(265): Illuminate\\Routing\\ControllerDispatcher->dispatch(Object(Illuminate\\Routing\\Route), Object(App\\Http\\Controllers\\StockMovementController), 'bulkTransfer')\n#4 /var/www/vendor/laravel/framework/src/Illuminate/Routing/Route.php(211): Illuminate\\Routing\\Route->runController()\n#5 /var/www/vendor/laravel/framework/src/Illuminate/Routing/Router.php(822): Illuminate\\Routing\\Route->run()\n#6 /var/www/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(180): Illuminate\\Routing\\Router->Illuminate\\Routing\\{closure}(Object(Illuminate\\Http\\Request))\n#7 /var/www/app/Http/Middleware/AdminNotificationsMiddleware.php(28): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}(Object(Illuminate\\Http\\Request))\n#8 /var/www/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): App\\Http\\Middleware\\AdminNotificationsMiddleware->handle(Object(Illuminate\\Http\\Request), Object(Closure))\n#9 /var/www/vendor/laravel/framework/src/Illuminate/Routing/Middleware/SubstituteBindings.php(50): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}(Object(Illuminate\\Http\\Request))\n#10 /var/www/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Routing\\Middleware\\SubstituteBindings->handle(Object(Illuminate\\Http\\Request), Object(Closure))\n#11 /var/www/vendor/laravel/framework/src/Illuminate/Auth/Middleware/Authenticate.php(63): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}(Object(Illuminate\\Http\\Request))\n#12 /var/www/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Auth\\Middleware\\Authenticate->handle(Object(Illuminate\\Http\\Request), Object(Closure), 'sanctum')\n#13 /var/www/app/Http/Middleware/SetDynamicUrl.php(26): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}(Object(Illuminate\\Http\\Request))\n#14 /var/www/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): App\\Http\\Middleware\\SetDynamicUrl->handle(Object(Illuminate\\Http\\Request), Object(Closure))\n#15 /var/www/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(137): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}(Object(Illuminate\\Http\\Request))\n#16 /var/www/vendor/laravel/framework/src/Illuminate/Routing/Router.php(821): Illuminate\\Pipeline\\Pipeline->then(Object(Closure))\n#17 /var/www/vendor/laravel/framework/src/Illuminate/Routing/Router.php(800): Illuminate\\Routing\\Router->runRouteWithinStack(Object(Illuminate\\Routing\\Route), Object(Illuminate\\Http\\Request))\n#18 /var/www/vendor/laravel/framework/src/Illuminate/Routing/Router.php(764): Illuminate\\Routing\\Router->runRoute(Object(Illuminate\\Http\\Request), Object(Illuminate\\Routing\\Route))\n#19 /var/www/vendor/laravel/framework/src/Illuminate/Routing/Router.php(753): Illuminate\\Routing\\Router->dispatchToRoute(Object(Illuminate\\Http\\Request))\n#20 /var/www/vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php(200): Illuminate\\Routing\\Router->dispatch(Object(Illuminate\\Http\\Request))\n#21 /var/www/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(180): Illuminate\\Foundation\\Http\\Kernel->Illuminate\\Foundation\\Http\\{closure}(Object(Illuminate\\Http\\Request))\n#22 /var/www/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TransformsRequest.php(21): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}(Object(Illuminate\\Http\\Request))\n#23 /var/www/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/ConvertEmptyStringsToNull.php(31): Illuminate\\Foundation\\Http\\Middleware\\TransformsRequest->handle(Object(Illuminate\\Http\\Request), Object(Closure))\n#24 /var/www/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Foundation\\Http\\Middleware\\ConvertEmptyStringsToNull->handle(Object(Illuminate\\Http\\Request), Object(Closure))\n#25 /var/www/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TransformsRequest.php(21): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}(Object(Illuminate\\Http\\Request))\n#26 /var/www/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TrimStrings.php(51): Illuminate\\Foundation\\Http\\Middleware\\TransformsRequest->handle(Object(Illuminate\\Http\\Request), Object(Closure))\n#27 /var/www/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Foundation\\Http\\Middleware\\TrimStrings->handle(Object(Illuminate\\Http\\Request), Object(Closure))\n#28 /var/www/vendor/laravel/framework/src/Illuminate/Http/Middleware/ValidatePostSize.php(27): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}(Object(Illuminate\\Http\\Request))\n#29 /var/www/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Http\\Middleware\\ValidatePostSize->handle(Object(Illuminate\\Http\\Request), Object(Closure))\n#30 /var/www/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/PreventRequestsDuringMaintenance.php(109): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}(Object(Illuminate\\Http\\Request))\n#31 /var/www/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Foundation\\Http\\Middleware\\PreventRequestsDuringMaintenance->handle(Object(Illuminate\\Http\\Request), Object(Closure))\n#32 /var/www/vendor/laravel/framework/src/Illuminate/Http/Middleware/HandleCors.php(61): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}(Object(Illuminate\\Http\\Request))\n#33 /var/www/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Http\\Middleware\\HandleCors->handle(Object(Illuminate\\Http\\Request), Object(Closure))\n#34 /var/www/vendor/laravel/framework/src/Illuminate/Http/Middleware/TrustProxies.php(58): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}(Object(Illuminate\\Http\\Request))\n#35 /var/www/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Http\\Middleware\\TrustProxies->handle(Object(Illuminate\\Http\\Request), Object(Closure))\n#36 /var/www/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/InvokeDeferredCallbacks.php(22): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}(Object(Illuminate\\Http\\Request))\n#37 /var/www/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Foundation\\Http\\Middleware\\InvokeDeferredCallbacks->handle(Object(Illuminate\\Http\\Request), Object(Closure))\n#38 /var/www/vendor/laravel/framework/src/Illuminate/Http/Middleware/ValidatePathEncoding.php(26): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}(Object(Illuminate\\Http\\Request))\n#39 /var/www/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Http\\Middleware\\ValidatePathEncoding->handle(Object(Illuminate\\Http\\Request), Object(Closure))\n#40 /var/www/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(137): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}(Object(Illuminate\\Http\\Request))\n#41 /var/www/vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php(175): Illuminate\\Pipeline\\Pipeline->then(Object(Closure))\n#42 /var/www/vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php(144): Illuminate\\Foundation\\Http\\Kernel->sendRequestThroughRouter(Object(Illuminate\\Http\\Request))\n#43 /var/www/vendor/laravel/framework/src/Illuminate/Foundation/Application.php(1220): Illuminate\\Foundation\\Http\\Kernel->handle(Object(Illuminate\\Http\\Request))\n#44 /var/www/public/index.php(20): Illuminate\\Foundation\\Application->handleRequest(Object(Illuminate\\Http\\Request))\n#45 /var/www/vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php(23): require_once('/var/www/public...')\n#46 {main}	172.18.0.1	Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36	2026-01-28 15:39:39	2026-01-28 15:39:39	\N
89	4	stock_transferred	error	\N	\N	transfert échoué	{"attributes":{},"request":{},"query":{},"server":{},"files":{},"cookies":{},"headers":{}}	Property [fromLocation] does not exist on this collection instance.	#0 /var/www/app/Http/Controllers/StockMovementController.php(346): Illuminate\\Support\\Collection->__get('fromLocation')\n#1 /var/www/vendor/laravel/framework/src/Illuminate/Routing/Controller.php(54): App\\Http\\Controllers\\StockMovementController->bulkTransfer(Object(Illuminate\\Http\\Request))\n#2 /var/www/vendor/laravel/framework/src/Illuminate/Routing/ControllerDispatcher.php(43): Illuminate\\Routing\\Controller->callAction('bulkTransfer', Array)\n#3 /var/www/vendor/laravel/framework/src/Illuminate/Routing/Route.php(265): Illuminate\\Routing\\ControllerDispatcher->dispatch(Object(Illuminate\\Routing\\Route), Object(App\\Http\\Controllers\\StockMovementController), 'bulkTransfer')\n#4 /var/www/vendor/laravel/framework/src/Illuminate/Routing/Route.php(211): Illuminate\\Routing\\Route->runController()\n#5 /var/www/vendor/laravel/framework/src/Illuminate/Routing/Router.php(822): Illuminate\\Routing\\Route->run()\n#6 /var/www/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(180): Illuminate\\Routing\\Router->Illuminate\\Routing\\{closure}(Object(Illuminate\\Http\\Request))\n#7 /var/www/app/Http/Middleware/AdminNotificationsMiddleware.php(28): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}(Object(Illuminate\\Http\\Request))\n#8 /var/www/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): App\\Http\\Middleware\\AdminNotificationsMiddleware->handle(Object(Illuminate\\Http\\Request), Object(Closure))\n#9 /var/www/vendor/laravel/framework/src/Illuminate/Routing/Middleware/SubstituteBindings.php(50): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}(Object(Illuminate\\Http\\Request))\n#10 /var/www/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Routing\\Middleware\\SubstituteBindings->handle(Object(Illuminate\\Http\\Request), Object(Closure))\n#11 /var/www/vendor/laravel/framework/src/Illuminate/Auth/Middleware/Authenticate.php(63): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}(Object(Illuminate\\Http\\Request))\n#12 /var/www/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Auth\\Middleware\\Authenticate->handle(Object(Illuminate\\Http\\Request), Object(Closure), 'sanctum')\n#13 /var/www/app/Http/Middleware/SetDynamicUrl.php(26): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}(Object(Illuminate\\Http\\Request))\n#14 /var/www/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): App\\Http\\Middleware\\SetDynamicUrl->handle(Object(Illuminate\\Http\\Request), Object(Closure))\n#15 /var/www/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(137): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}(Object(Illuminate\\Http\\Request))\n#16 /var/www/vendor/laravel/framework/src/Illuminate/Routing/Router.php(821): Illuminate\\Pipeline\\Pipeline->then(Object(Closure))\n#17 /var/www/vendor/laravel/framework/src/Illuminate/Routing/Router.php(800): Illuminate\\Routing\\Router->runRouteWithinStack(Object(Illuminate\\Routing\\Route), Object(Illuminate\\Http\\Request))\n#18 /var/www/vendor/laravel/framework/src/Illuminate/Routing/Router.php(764): Illuminate\\Routing\\Router->runRoute(Object(Illuminate\\Http\\Request), Object(Illuminate\\Routing\\Route))\n#19 /var/www/vendor/laravel/framework/src/Illuminate/Routing/Router.php(753): Illuminate\\Routing\\Router->dispatchToRoute(Object(Illuminate\\Http\\Request))\n#20 /var/www/vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php(200): Illuminate\\Routing\\Router->dispatch(Object(Illuminate\\Http\\Request))\n#21 /var/www/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(180): Illuminate\\Foundation\\Http\\Kernel->Illuminate\\Foundation\\Http\\{closure}(Object(Illuminate\\Http\\Request))\n#22 /var/www/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TransformsRequest.php(21): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}(Object(Illuminate\\Http\\Request))\n#23 /var/www/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/ConvertEmptyStringsToNull.php(31): Illuminate\\Foundation\\Http\\Middleware\\TransformsRequest->handle(Object(Illuminate\\Http\\Request), Object(Closure))\n#24 /var/www/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Foundation\\Http\\Middleware\\ConvertEmptyStringsToNull->handle(Object(Illuminate\\Http\\Request), Object(Closure))\n#25 /var/www/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TransformsRequest.php(21): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}(Object(Illuminate\\Http\\Request))\n#26 /var/www/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TrimStrings.php(51): Illuminate\\Foundation\\Http\\Middleware\\TransformsRequest->handle(Object(Illuminate\\Http\\Request), Object(Closure))\n#27 /var/www/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Foundation\\Http\\Middleware\\TrimStrings->handle(Object(Illuminate\\Http\\Request), Object(Closure))\n#28 /var/www/vendor/laravel/framework/src/Illuminate/Http/Middleware/ValidatePostSize.php(27): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}(Object(Illuminate\\Http\\Request))\n#29 /var/www/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Http\\Middleware\\ValidatePostSize->handle(Object(Illuminate\\Http\\Request), Object(Closure))\n#30 /var/www/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/PreventRequestsDuringMaintenance.php(109): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}(Object(Illuminate\\Http\\Request))\n#31 /var/www/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Foundation\\Http\\Middleware\\PreventRequestsDuringMaintenance->handle(Object(Illuminate\\Http\\Request), Object(Closure))\n#32 /var/www/vendor/laravel/framework/src/Illuminate/Http/Middleware/HandleCors.php(61): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}(Object(Illuminate\\Http\\Request))\n#33 /var/www/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Http\\Middleware\\HandleCors->handle(Object(Illuminate\\Http\\Request), Object(Closure))\n#34 /var/www/vendor/laravel/framework/src/Illuminate/Http/Middleware/TrustProxies.php(58): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}(Object(Illuminate\\Http\\Request))\n#35 /var/www/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Http\\Middleware\\TrustProxies->handle(Object(Illuminate\\Http\\Request), Object(Closure))\n#36 /var/www/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/InvokeDeferredCallbacks.php(22): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}(Object(Illuminate\\Http\\Request))\n#37 /var/www/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Foundation\\Http\\Middleware\\InvokeDeferredCallbacks->handle(Object(Illuminate\\Http\\Request), Object(Closure))\n#38 /var/www/vendor/laravel/framework/src/Illuminate/Http/Middleware/ValidatePathEncoding.php(26): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}(Object(Illuminate\\Http\\Request))\n#39 /var/www/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(219): Illuminate\\Http\\Middleware\\ValidatePathEncoding->handle(Object(Illuminate\\Http\\Request), Object(Closure))\n#40 /var/www/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(137): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}(Object(Illuminate\\Http\\Request))\n#41 /var/www/vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php(175): Illuminate\\Pipeline\\Pipeline->then(Object(Closure))\n#42 /var/www/vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php(144): Illuminate\\Foundation\\Http\\Kernel->sendRequestThroughRouter(Object(Illuminate\\Http\\Request))\n#43 /var/www/vendor/laravel/framework/src/Illuminate/Foundation/Application.php(1220): Illuminate\\Foundation\\Http\\Kernel->handle(Object(Illuminate\\Http\\Request))\n#44 /var/www/public/index.php(20): Illuminate\\Foundation\\Application->handleRequest(Object(Illuminate\\Http\\Request))\n#45 /var/www/vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php(23): require_once('/var/www/public...')\n#46 {main}	172.18.0.1	Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36	2026-01-28 15:42:16	2026-01-28 15:42:16	\N
90	4	stock_transferred	success	App\\Models\\StockMovement	\N	 a transféré 1 produit(s) de Magasin principal vers Entrepôt principal	\N	\N	\N	172.18.0.1	Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36	2026-01-28 15:48:25	2026-01-28 15:48:25	movements-stock
91	4	stock_transferred	success	App\\Models\\StockMovement	\N	 a transféré 1 produit(s) de Entrepôt principal vers Magasin principal	\N	\N	\N	172.18.0.1	Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36	2026-01-28 15:49:17	2026-01-28 15:49:17	movements-stock
92	4	customer_created	success	App\\Models\\Customer	1	 a créé le client CL-20260128-0001	{"name":"Anonyme","is_extra_customer":false,"reliability_score":"5.00","loyalty_points":0,"credit_limit":"100000.00","is_active":true,"customer_number":"CL-20260128-0001","updated_at":"2026-01-28T15:49:34.000000Z","created_at":"2026-01-28T15:49:34.000000Z","id":1}	\N	\N	172.18.0.1	Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36	2026-01-28 15:49:34	2026-01-28 15:49:34	clients/1
93	4	sale_created	success	App\\Models\\Sale	1	 a créé une vente rapide d'une valeur de 20000.00	{"sale_number":"VNT-20260128-0001","customer_id":1,"user_id":4,"sale_date":"2026-01-28T15:49:34.000000Z","sale_type":"immediate","subtotal":"20000.00","discount_amount":"0.00","discount_reason":null,"total_amount":"20000.00","payment_status":"paid","payment_method":"cash","notes":null,"status":"CONFIRMED","updated_at":"2026-01-28T15:49:34.000000Z","created_at":"2026-01-28T15:49:34.000000Z","id":1,"items":[{"id":1,"sale_id":1,"variant_id":2,"quantity":1,"unit_price":"20000.00","subtotal":"20000.00","created_at":"2026-01-28T15:49:34.242864Z","variant":{"id":2,"product_id":30,"sku":"JEA--RHSQ","price_adjustment":"0.00","stock_quantity":8,"reserved_quantity":0,"low_stock_threshold":5,"is_active":true,"created_at":"2026-01-28T15:27:55.000000Z","updated_at":"2026-01-28T15:49:34.242864Z","image_path":null,"credit_quantity":0,"available_quantity":8,"product":{"id":30,"name":"Jean fitness","description":null,"category_id":28,"subcategory_id":30,"base_price":"20000.00","is_active":true,"created_at":"2026-01-20T13:34:54.000000Z","updated_at":"2026-01-20T13:34:54.000000Z","image_url":"http:\\/\\/localhost:8000\\/storage\\/uploads\\/images\\/2026\\/01\\/c21574d8-bcdc-429a-8817-28c3d16b4d33.jpeg"}}}],"customer":{"id":1,"name":"Anonyme","phone":null,"address":null,"reliability_score":"5.00","loyalty_points":20,"credit_limit":"100000.00","notes":"[2026-01-28 15:49] +20 points: Achat de 20000 Ar","is_active":true,"created_at":"2026-01-28T15:49:34.000000Z","updated_at":"2026-01-28T15:49:34.242864Z","customer_number":"CL-20260128-0001","is_extra_customer":false},"user":{"id":4,"name":"Administrateur","username":"admin","role":"admin","is_active":true,"created_at":"2025-12-27T10:28:54.853759Z","updated_at":"2025-12-27T10:28:54.853759Z"},"transactions":[{"id":3,"account_id":9,"transaction_type_id":16,"amount":"20000.00","balance_before":"1743903.00","balance_after":"1763903.00","transaction_date":"2026-01-28T15:49:34.000000Z","related_account_id":null,"related_transaction_id":null,"supplier_id":null,"freight_forwarder_id":null,"stock_receipt_id":null,"expense_category_id":null,"recipient_name":null,"sale_id":1,"reference_number":"FAC-20260128-000001","description":"Vente #VNT-20260128-0001","notes":"","created_by":4,"created_at":"2026-01-28T15:49:34.242864Z","reversed_transaction_id":null,"planned_expense_id":null}]}	\N	\N	172.18.0.1	Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36	2026-01-28 15:49:34	2026-01-28 15:49:34	ventes/rapide/1
95	4	credit_created	success	App\\Models\\Credit	1	 a créé une vente à crédit d'une valeur de 17000.00	{"sale_number":"VNT-20260128-0002","customer_id":2,"user_id":4,"sale_date":"2026-01-28T15:50:16.000000Z","sale_type":"credit","subtotal":"20000.00","discount_amount":"3000.00","discount_reason":null,"total_amount":"17000.00","payment_status":"pending","payment_method":null,"notes":null,"status":"CONFIRMED","updated_at":"2026-01-28T15:50:16.000000Z","created_at":"2026-01-28T15:50:16.000000Z","id":2,"items":[{"id":2,"sale_id":2,"variant_id":2,"quantity":1,"unit_price":"20000.00","subtotal":"20000.00","created_at":"2026-01-28T15:50:16.840524Z","variant":{"id":2,"product_id":30,"sku":"JEA--RHSQ","price_adjustment":"0.00","stock_quantity":7,"reserved_quantity":0,"low_stock_threshold":5,"is_active":true,"created_at":"2026-01-28T15:27:55.000000Z","updated_at":"2026-01-28T15:50:16.840524Z","image_path":null,"credit_quantity":0,"available_quantity":7,"product":{"id":30,"name":"Jean fitness","description":null,"category_id":28,"subcategory_id":30,"base_price":"20000.00","is_active":true,"created_at":"2026-01-20T13:34:54.000000Z","updated_at":"2026-01-20T13:34:54.000000Z","image_url":"http:\\/\\/localhost:8000\\/storage\\/uploads\\/images\\/2026\\/01\\/c21574d8-bcdc-429a-8817-28c3d16b4d33.jpeg"}}}],"customer":{"id":2,"name":"kil milay","phone":null,"address":null,"reliability_score":"5.00","loyalty_points":0,"credit_limit":"0.00","notes":null,"is_active":true,"created_at":"2026-01-28T15:50:13.000000Z","updated_at":"2026-01-28T15:50:13.000000Z","customer_number":"CL-20260128-0002","is_extra_customer":true},"user":{"id":4,"name":"Administrateur","username":"admin","role":"admin","is_active":true,"created_at":"2025-12-27T10:28:54.853759Z","updated_at":"2025-12-27T10:28:54.853759Z"},"credit":{"id":1,"sale_id":2,"customer_id":2,"total_amount":"17000.00","amount_paid":"0.00","amount_due":"17000.00","credit_date":"2026-01-28T00:00:00.000000Z","due_date":"2026-01-31T00:00:00.000000Z","status":"active","notes":null,"created_at":"2026-01-28T15:50:16.000000Z","updated_at":"2026-01-28T15:50:16.000000Z","last_payment_date":null,"installments":[{"id":1,"credit_id":1,"installment_number":1,"due_date":"2026-01-31T00:00:00.000000Z","amount_due":"17000.00","amount_paid":"0.00","status":"pending","paid_date":null,"created_at":"2026-01-28T15:50:16.000000Z"}]}}	\N	\N	172.18.0.1	Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36	2026-01-28 15:50:16	2026-01-28 15:50:16	ventes/credits/1
96	4	customer_created	success	App\\Models\\Customer	3	 a créé le client CL-20260128-0003	{"name":"Mr Dupont","phone":null,"address":null,"credit_limit":"0.00","notes":null,"is_active":true,"is_extra_customer":true,"reliability_score":"5.00","loyalty_points":0,"customer_number":"CL-20260128-0003","updated_at":"2026-01-28T15:50:40.000000Z","created_at":"2026-01-28T15:50:40.000000Z","id":3}	\N	\N	172.18.0.4	Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36	2026-01-28 15:50:40	2026-01-28 15:50:40	clients/3
97	4	reservation_created	success	App\\Models\\Reservation	1	 a créé une réservation d'une valeur de 20000.00	{"sale_number":"VNT-20260128-0003","customer_id":3,"user_id":4,"sale_date":"2026-01-28T15:50:48.000000Z","sale_type":"reservation","subtotal":"20000.00","discount_amount":"0.00","discount_reason":null,"total_amount":"20000.00","payment_status":"partial","payment_method":"mobile_money","notes":null,"status":"CONFIRMED","updated_at":"2026-01-28T15:50:48.000000Z","created_at":"2026-01-28T15:50:48.000000Z","id":3,"items":[{"id":3,"sale_id":3,"variant_id":2,"quantity":1,"unit_price":"20000.00","subtotal":"20000.00","created_at":"2026-01-28T15:50:48.005414Z","variant":{"id":2,"product_id":30,"sku":"JEA--RHSQ","price_adjustment":"0.00","stock_quantity":7,"reserved_quantity":1,"low_stock_threshold":5,"is_active":true,"created_at":"2026-01-28T15:27:55.000000Z","updated_at":"2026-01-28T15:50:48.005414Z","image_path":null,"credit_quantity":0,"available_quantity":6,"product":{"id":30,"name":"Jean fitness","description":null,"category_id":28,"subcategory_id":30,"base_price":"20000.00","is_active":true,"created_at":"2026-01-20T13:34:54.000000Z","updated_at":"2026-01-20T13:34:54.000000Z","image_url":"http:\\/\\/localhost:8000\\/storage\\/uploads\\/images\\/2026\\/01\\/c21574d8-bcdc-429a-8817-28c3d16b4d33.jpeg"}}}],"customer":{"id":3,"name":"Mr Dupont","phone":null,"address":null,"reliability_score":"5.00","loyalty_points":0,"credit_limit":"0.00","notes":null,"is_active":true,"created_at":"2026-01-28T15:50:40.000000Z","updated_at":"2026-01-28T15:50:40.000000Z","customer_number":"CL-20260128-0003","is_extra_customer":true},"user":{"id":4,"name":"Administrateur","username":"admin","role":"admin","is_active":true,"created_at":"2025-12-27T10:28:54.853759Z","updated_at":"2025-12-27T10:28:54.853759Z"},"reservation":{"id":1,"sale_id":3,"customer_id":3,"reservation_date":"2026-01-28T15:50:48.000000Z","expiry_date":"2026-01-31T00:00:00.000000Z","total_amount":"20000.00","deposit_amount":"2000.00","remaining_amount":"18000.00","status":"confirmed","cancellation_reason":null,"completed_at":null,"created_at":"2026-01-28T15:50:48.000000Z","updated_at":"2026-01-28T15:50:48.000000Z","transaction_complete_id":null},"transactions":[{"id":4,"account_id":11,"transaction_type_id":16,"amount":"2000.00","balance_before":"4349700.00","balance_after":"4351700.00","transaction_date":"2026-01-28T15:50:48.000000Z","related_account_id":null,"related_transaction_id":null,"supplier_id":null,"freight_forwarder_id":null,"stock_receipt_id":null,"expense_category_id":null,"recipient_name":null,"sale_id":3,"reference_number":"FAC-20260128-000002","description":"Acompte r\\u00e9servation #VNT-20260128-0003","notes":"","created_by":4,"created_at":"2026-01-28T15:50:48.005414Z","reversed_transaction_id":null,"planned_expense_id":null}]}	\N	\N	172.18.0.1	Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36	2026-01-28 15:50:48	2026-01-28 15:50:48	ventes/reservations/1
98	4	account_transaction_cancelled	success	App\\Models\\AccountTransaction	3	a annulé la transaction ID 3 FAC-20260128-000001 montant 20000.00	{"id":3,"account_id":9,"transaction_type_id":16,"amount":"20000.00","balance_before":"1743903.00","balance_after":"1763903.00","transaction_date":"2026-01-28T15:49:34.000000Z","related_account_id":null,"related_transaction_id":null,"supplier_id":null,"freight_forwarder_id":null,"stock_receipt_id":null,"expense_category_id":null,"recipient_name":null,"sale_id":1,"reference_number":"FAC-20260128-000001","description":"Vente #VNT-20260128-0001","notes":"","created_by":4,"created_at":"2026-01-28T15:49:34.242864Z","reversed_transaction_id":null,"planned_expense_id":null,"account":{"id":9,"account_type_id":1,"name":"CAISSE numero 2","account_number":null,"initial_balance":"100000.00","current_balance":"1743903.00","notes":null,"is_active":true,"created_by":4,"created_at":"2026-01-14T13:37:33.707327Z","updated_at":"2026-01-28T15:51:17.000000Z"},"sale":{"id":1,"sale_number":"VNT-20260128-0001","customer_id":1,"user_id":4,"sale_date":"2026-01-28T15:49:34.000000Z","sale_type":"immediate","subtotal":"20000.00","discount_amount":"0.00","discount_reason":null,"total_amount":"20000.00","payment_status":"paid","notes":null,"created_at":"2026-01-28T15:49:34.000000Z","updated_at":"2026-01-28T15:49:34.000000Z","payment_method":"cash","status":"CONFIRMED","reservation":null},"transaction_type":{"id":16,"code":"INCOME","name":"income","display_name":"Revenu","category":"income","description":"Entr\\u00e9e d\\u2019argent","created_at":"2025-12-29T14:34:13.404169Z","updated_at":"2025-12-29T14:34:13.404169Z"}}	\N	\N	172.18.0.1	Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36	2026-01-28 15:51:17	2026-01-28 15:51:17	transactions/3
99	4	sale_cancelled	success	App\\Models\\Sale	1	 a annulée la vente rapide VNT-20260128-0001	{"id":1,"sale_number":"VNT-20260128-0001","customer_id":1,"user_id":4,"sale_date":"2026-01-28T15:49:34.000000Z","sale_type":"immediate","subtotal":"20000.00","discount_amount":"0.00","discount_reason":null,"total_amount":"20000.00","payment_status":"paid","notes":null,"created_at":"2026-01-28T15:49:34.000000Z","updated_at":"2026-01-28T15:51:22.000000Z","payment_method":"cash","status":"CANCELLED","items":[{"id":1,"sale_id":1,"variant_id":2,"quantity":1,"unit_price":"20000.00","subtotal":"20000.00","created_at":"2026-01-28T15:49:34.242864Z","variant":{"id":2,"product_id":30,"sku":"JEA--RHSQ","price_adjustment":"0.00","stock_quantity":8,"reserved_quantity":1,"low_stock_threshold":5,"is_active":true,"created_at":"2026-01-28T15:27:55.000000Z","updated_at":"2026-01-28T15:51:22.536821Z","image_path":null,"credit_quantity":0,"available_quantity":7,"product":{"id":30,"name":"Jean fitness","description":null,"category_id":28,"subcategory_id":30,"base_price":"20000.00","is_active":true,"created_at":"2026-01-20T13:34:54.000000Z","updated_at":"2026-01-20T13:34:54.000000Z","image_url":"http:\\/\\/localhost:8000\\/storage\\/uploads\\/images\\/2026\\/01\\/c21574d8-bcdc-429a-8817-28c3d16b4d33.jpeg"}}}],"customer":{"id":1,"name":"Anonyme","phone":null,"address":null,"reliability_score":"5.00","loyalty_points":20,"credit_limit":"100000.00","notes":"[2026-01-28 15:49] +20 points: Achat de 20000 Ar","is_active":true,"created_at":"2026-01-28T15:49:34.000000Z","updated_at":"2026-01-28T15:49:34.242864Z","customer_number":"CL-20260128-0001","is_extra_customer":false},"user":{"id":4,"name":"Administrateur","username":"admin","role":"admin","is_active":true,"created_at":"2025-12-27T10:28:54.853759Z","updated_at":"2025-12-27T10:28:54.853759Z"},"transactions":[{"id":3,"account_id":9,"transaction_type_id":16,"amount":"20000.00","balance_before":"1743903.00","balance_after":"1763903.00","transaction_date":"2026-01-28T15:49:34.000000Z","related_account_id":null,"related_transaction_id":null,"supplier_id":null,"freight_forwarder_id":null,"stock_receipt_id":null,"expense_category_id":null,"recipient_name":null,"sale_id":1,"reference_number":"FAC-20260128-000001","description":"Vente #VNT-20260128-0001","notes":"","created_by":4,"created_at":"2026-01-28T15:49:34.242864Z","reversed_transaction_id":null,"planned_expense_id":null}]}	\N	\N	172.18.0.1	Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36	2026-01-28 15:51:22	2026-01-28 15:51:22	ventes/rapide/1
100	4	reservation_completed	success	App\\Models\\Reservation	1	 a reçu le paiement de finalisation d'une reservation	{"id":1,"sale_id":3,"customer_id":3,"reservation_date":"2026-01-28T15:50:48.000000Z","expiry_date":"2026-01-31T00:00:00.000000Z","total_amount":"20000.00","deposit_amount":"20000.00","remaining_amount":"0.00","status":"completed","cancellation_reason":null,"completed_at":"2026-01-28T15:51:33.000000Z","created_at":"2026-01-28T15:50:48.000000Z","updated_at":"2026-01-28T15:51:33.000000Z","transaction_complete_id":6,"sale":{"id":3,"sale_number":"VNT-20260128-0003","customer_id":3,"user_id":4,"sale_date":"2026-01-28T15:50:48.000000Z","sale_type":"reservation","subtotal":"20000.00","discount_amount":"0.00","discount_reason":null,"total_amount":"20000.00","payment_status":"paid","notes":null,"created_at":"2026-01-28T15:50:48.000000Z","updated_at":"2026-01-28T15:51:33.581608Z","payment_method":"mobile_money","status":"CONFIRMED","items":[{"id":3,"sale_id":3,"variant_id":2,"quantity":1,"unit_price":"20000.00","subtotal":"20000.00","created_at":"2026-01-28T15:50:48.005414Z","variant":{"id":2,"product_id":30,"sku":"JEA--RHSQ","price_adjustment":"0.00","stock_quantity":7,"reserved_quantity":0,"low_stock_threshold":5,"is_active":true,"created_at":"2026-01-28T15:27:55.000000Z","updated_at":"2026-01-28T15:51:33.581608Z","image_path":null,"credit_quantity":0,"available_quantity":7,"product":{"id":30,"name":"Jean fitness","description":null,"category_id":28,"subcategory_id":30,"base_price":"20000.00","is_active":true,"created_at":"2026-01-20T13:34:54.000000Z","updated_at":"2026-01-20T13:34:54.000000Z","image_url":"http:\\/\\/localhost:8000\\/storage\\/uploads\\/images\\/2026\\/01\\/c21574d8-bcdc-429a-8817-28c3d16b4d33.jpeg"}}}],"transactions":[{"id":4,"account_id":11,"transaction_type_id":16,"amount":"2000.00","balance_before":"4349700.00","balance_after":"4351700.00","transaction_date":"2026-01-28T15:50:48.000000Z","related_account_id":null,"related_transaction_id":null,"supplier_id":null,"freight_forwarder_id":null,"stock_receipt_id":null,"expense_category_id":null,"recipient_name":null,"sale_id":3,"reference_number":"FAC-20260128-000002","description":"Acompte r\\u00e9servation #VNT-20260128-0003","notes":"","created_by":4,"created_at":"2026-01-28T15:50:48.005414Z","reversed_transaction_id":null,"planned_expense_id":null},{"id":6,"account_id":11,"transaction_type_id":16,"amount":"18000.00","balance_before":"4351700.00","balance_after":"4369700.00","transaction_date":"2026-01-28T15:51:33.000000Z","related_account_id":null,"related_transaction_id":null,"supplier_id":null,"freight_forwarder_id":null,"stock_receipt_id":null,"expense_category_id":null,"recipient_name":null,"sale_id":3,"reference_number":"FAC-20260128-000003","description":"Paiement final r\\u00e9servation #VNT-20260128-0003","notes":"","created_by":4,"created_at":"2026-01-28T15:51:33.581608Z","reversed_transaction_id":null,"planned_expense_id":null}]},"customer":{"id":3,"name":"Mr Dupont","phone":null,"address":null,"reliability_score":"5.00","loyalty_points":20,"credit_limit":"0.00","notes":"[2026-01-28 15:51] +20 points: Achat de 20000 Ar","is_active":true,"created_at":"2026-01-28T15:50:40.000000Z","updated_at":"2026-01-28T15:51:33.581608Z","customer_number":"CL-20260128-0003","is_extra_customer":true}}	\N	\N	172.18.0.1	Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36	2026-01-28 15:51:33	2026-01-28 15:51:33	ventes/reservations/1
101	4	credit_cancelled	success	App\\Models\\Credit	1	 a annulée la vente à crédit  VNT-20260128-0002	{"id":1,"sale_id":2,"customer_id":2,"total_amount":"17000.00","amount_paid":"0.00","amount_due":"17000.00","credit_date":"2026-01-28T00:00:00.000000Z","due_date":"2026-01-31T00:00:00.000000Z","status":"cancelled","notes":null,"created_at":"2026-01-28T15:50:16.000000Z","updated_at":"2026-01-28T15:51:41.000000Z","last_payment_date":null,"sale":{"id":2,"sale_number":"VNT-20260128-0002","customer_id":2,"user_id":4,"sale_date":"2026-01-28T15:50:16.000000Z","sale_type":"credit","subtotal":"20000.00","discount_amount":"3000.00","discount_reason":null,"total_amount":"17000.00","payment_status":"pending","notes":null,"created_at":"2026-01-28T15:50:16.000000Z","updated_at":"2026-01-28T15:51:41.232233Z","payment_method":null,"status":"CANCELLED","items":[{"id":2,"sale_id":2,"variant_id":2,"quantity":1,"unit_price":"20000.00","subtotal":"20000.00","created_at":"2026-01-28T15:50:16.840524Z","variant":{"id":2,"product_id":30,"sku":"JEA--RHSQ","price_adjustment":"0.00","stock_quantity":8,"reserved_quantity":0,"low_stock_threshold":5,"is_active":true,"created_at":"2026-01-28T15:27:55.000000Z","updated_at":"2026-01-28T15:51:41.232233Z","image_path":null,"credit_quantity":0,"available_quantity":8,"product":{"id":30,"name":"Jean fitness","description":null,"category_id":28,"subcategory_id":30,"base_price":"20000.00","is_active":true,"created_at":"2026-01-20T13:34:54.000000Z","updated_at":"2026-01-20T13:34:54.000000Z","image_url":"http:\\/\\/localhost:8000\\/storage\\/uploads\\/images\\/2026\\/01\\/c21574d8-bcdc-429a-8817-28c3d16b4d33.jpeg"}}}],"transactions":[]},"customer":{"id":2,"name":"kil milay","phone":null,"address":null,"reliability_score":"5.00","loyalty_points":0,"credit_limit":"0.00","notes":null,"is_active":true,"created_at":"2026-01-28T15:50:13.000000Z","updated_at":"2026-01-28T15:50:13.000000Z","customer_number":"CL-20260128-0002","is_extra_customer":true}}	\N	\N	172.18.0.1	Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36	2026-01-28 15:51:41	2026-01-28 15:51:41	ventes/credits/1
102	4	account_transaction_transfer	success	App\\Models\\AccountTransaction	7	a transféré 800000 de BMOI	{"outgoing":{"id":7,"account_id":10,"transaction_type_id":18,"amount":"800000.00","balance_before":"837146.01","balance_after":"37146.01","transaction_date":"2026-01-28T15:52:20.000000Z","related_account_id":11,"related_transaction_id":8,"supplier_id":null,"freight_forwarder_id":null,"stock_receipt_id":null,"expense_category_id":null,"recipient_name":null,"sale_id":null,"reference_number":"TRF-20260128-000001","description":"Transfert vers compte orange money","notes":null,"created_by":4,"created_at":"2026-01-28T15:52:20.000000Z","reversed_transaction_id":null,"planned_expense_id":null,"account":{"id":10,"account_type_id":3,"name":"BMOI","account_number":"1526182719289180","initial_balance":"4500000.00","current_balance":"37146.01","notes":null,"is_active":true,"created_by":4,"created_at":"2026-01-14T13:39:55.228949Z","updated_at":"2026-01-28T15:52:20.000000Z","account_type":{"id":3,"code":"BANK","name":"bank","display_name":"Banque","description":"Compte bancaire","created_at":"2025-12-28T11:35:31.682830Z"}},"transaction_type":{"id":18,"code":"TRANSFER","name":"transfer","display_name":"Transfert","category":"transfer","description":"Transfert entre comptes","created_at":"2025-12-29T14:34:13.404169Z","updated_at":"2025-12-29T14:34:13.404169Z"},"related_account":{"id":11,"account_type_id":2,"name":"orange money","account_number":"0290909092","initial_balance":"5600000.00","current_balance":"5169700.00","notes":null,"is_active":true,"created_by":4,"created_at":"2026-01-27T17:52:53.122797Z","updated_at":"2026-01-28T15:52:20.000000Z"},"creator":{"id":4,"name":"Administrateur","username":"admin","role":"admin","is_active":true,"created_at":"2025-12-27T10:28:54.853759Z","updated_at":"2025-12-27T10:28:54.853759Z"}},"incoming":{"id":8,"account_id":11,"transaction_type_id":18,"amount":"800000.00","balance_before":"4369700.00","balance_after":"5169700.00","transaction_date":"2026-01-28T15:52:20.000000Z","related_account_id":10,"related_transaction_id":7,"supplier_id":null,"freight_forwarder_id":null,"stock_receipt_id":null,"expense_category_id":null,"recipient_name":null,"sale_id":null,"reference_number":"TRF-20260128-000001","description":"Transfert de compte BMOI","notes":null,"created_by":4,"created_at":"2026-01-28T15:52:20.000000Z","reversed_transaction_id":null,"planned_expense_id":null,"account":{"id":11,"account_type_id":2,"name":"orange money","account_number":"0290909092","initial_balance":"5600000.00","current_balance":"5169700.00","notes":null,"is_active":true,"created_by":4,"created_at":"2026-01-27T17:52:53.122797Z","updated_at":"2026-01-28T15:52:20.000000Z","account_type":{"id":2,"code":"MOBILE_MONEY","name":"mobile_money","display_name":"Mobile Money","description":"Compte Mobile Money (MVola, Orange Money, etc.)","created_at":"2025-12-28T11:35:31.682830Z"}},"transaction_type":{"id":18,"code":"TRANSFER","name":"transfer","display_name":"Transfert","category":"transfer","description":"Transfert entre comptes","created_at":"2025-12-29T14:34:13.404169Z","updated_at":"2025-12-29T14:34:13.404169Z"},"related_account":{"id":10,"account_type_id":3,"name":"BMOI","account_number":"1526182719289180","initial_balance":"4500000.00","current_balance":"37146.01","notes":null,"is_active":true,"created_by":4,"created_at":"2026-01-14T13:39:55.228949Z","updated_at":"2026-01-28T15:52:20.000000Z"},"creator":{"id":4,"name":"Administrateur","username":"admin","role":"admin","is_active":true,"created_at":"2025-12-27T10:28:54.853759Z","updated_at":"2025-12-27T10:28:54.853759Z"}}}	\N	\N	172.18.0.1	Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36	2026-01-28 15:52:20	2026-01-28 15:52:20	transactions/7
103	4	expense_category_created	success	App\\Models\\ExpenseCategory	19	a créé la catégorie de dépense Gardien	{"name":"Gardien","description":null,"is_active":true}	\N	\N	172.18.0.1	Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36	2026-01-28 15:53:08	2026-01-28 15:53:08	/depenses
104	4	account_transaction_created	success	App\\Models\\AccountTransaction	9	a enregistré une Dépense opérationnelle : ID RE-20260128-000003, Montant 500000.00	{"id":9,"account_id":11,"transaction_type_id":17,"amount":"500000.00","balance_before":"5169700.00","balance_after":"4669700.00","transaction_date":"2026-01-28T15:54:23.000000Z","related_account_id":null,"related_transaction_id":null,"supplier_id":null,"freight_forwarder_id":null,"stock_receipt_id":null,"expense_category_id":19,"recipient_name":"MR lel garde","sale_id":null,"reference_number":"RE-20260128-000003","description":"D\\u00e9pense Gardien \\u00e0 MR lel garde","notes":null,"created_by":4,"created_at":"2026-01-28T15:54:23.000000Z","reversed_transaction_id":null,"planned_expense_id":1,"account":{"id":11,"account_type_id":2,"name":"orange money","account_number":"0290909092","initial_balance":"5600000.00","current_balance":"4669700.00","notes":null,"is_active":true,"created_by":4,"created_at":"2026-01-27T17:52:53.122797Z","updated_at":"2026-01-28T15:54:23.000000Z","account_type":{"id":2,"code":"MOBILE_MONEY","name":"mobile_money","display_name":"Mobile Money","description":"Compte Mobile Money (MVola, Orange Money, etc.)","created_at":"2025-12-28T11:35:31.682830Z"}},"transaction_type":{"id":17,"code":"EXPENSE","name":"expense","display_name":"D\\u00e9pense","category":"expense","description":"Sortie d\\u2019argent","created_at":"2025-12-29T14:34:13.404169Z","updated_at":"2025-12-29T14:34:13.404169Z"},"expense_category":{"id":19,"name":"Gardien","description":null,"icon":"tag","is_active":true,"created_at":"2026-01-28T15:53:08.000000Z","updated_at":"2026-01-28T15:53:08.000000Z"},"creator":{"id":4,"name":"Administrateur","username":"admin","role":"admin","is_active":true,"created_at":"2025-12-27T10:28:54.853759Z","updated_at":"2025-12-27T10:28:54.853759Z"}}	\N	\N	172.18.0.1	Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36	2026-01-28 15:54:23	2026-01-28 15:54:23	transactions/9
105	4	account_transaction_created	success	App\\Models\\AccountTransaction	10	a enregistré une Dépense opérationnelle : ID RE-20260128-000004, Montant 500000.00	{"id":10,"account_id":11,"transaction_type_id":17,"amount":"500000.00","balance_before":"4669700.00","balance_after":"4169700.00","transaction_date":"2026-01-28T15:55:27.000000Z","related_account_id":null,"related_transaction_id":null,"supplier_id":null,"freight_forwarder_id":null,"stock_receipt_id":null,"expense_category_id":19,"recipient_name":"MR lel garde","sale_id":null,"reference_number":"RE-20260128-000004","description":"D\\u00e9pense Gardien \\u00e0 MR lel garde","notes":null,"created_by":4,"created_at":"2026-01-28T15:55:27.000000Z","reversed_transaction_id":null,"planned_expense_id":1,"account":{"id":11,"account_type_id":2,"name":"orange money","account_number":"0290909092","initial_balance":"5600000.00","current_balance":"4169700.00","notes":null,"is_active":true,"created_by":4,"created_at":"2026-01-27T17:52:53.122797Z","updated_at":"2026-01-28T15:55:27.000000Z","account_type":{"id":2,"code":"MOBILE_MONEY","name":"mobile_money","display_name":"Mobile Money","description":"Compte Mobile Money (MVola, Orange Money, etc.)","created_at":"2025-12-28T11:35:31.682830Z"}},"transaction_type":{"id":17,"code":"EXPENSE","name":"expense","display_name":"D\\u00e9pense","category":"expense","description":"Sortie d\\u2019argent","created_at":"2025-12-29T14:34:13.404169Z","updated_at":"2025-12-29T14:34:13.404169Z"},"expense_category":{"id":19,"name":"Gardien","description":null,"icon":"tag","is_active":true,"created_at":"2026-01-28T15:53:08.000000Z","updated_at":"2026-01-28T15:53:08.000000Z"},"creator":{"id":4,"name":"Administrateur","username":"admin","role":"admin","is_active":true,"created_at":"2025-12-27T10:28:54.853759Z","updated_at":"2025-12-27T10:28:54.853759Z"}}	\N	\N	172.18.0.1	Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36	2026-01-28 15:55:27	2026-01-28 15:55:27	transactions/10
106	4	account_transaction_cancelled	success	App\\Models\\AccountTransaction	10	a annulé la transaction ID 10 RE-20260128-000004 montant 500000.00	{"id":10,"account_id":11,"transaction_type_id":17,"amount":"500000.00","balance_before":"4669700.00","balance_after":"4169700.00","transaction_date":"2026-01-28T15:55:27.000000Z","related_account_id":null,"related_transaction_id":null,"supplier_id":null,"freight_forwarder_id":null,"stock_receipt_id":null,"expense_category_id":19,"recipient_name":"MR lel garde","sale_id":null,"reference_number":"RE-20260128-000004","description":"D\\u00e9pense Gardien \\u00e0 MR lel garde","notes":null,"created_by":4,"created_at":"2026-01-28T15:55:27.000000Z","reversed_transaction_id":null,"planned_expense_id":1,"account":{"id":11,"account_type_id":2,"name":"orange money","account_number":"0290909092","initial_balance":"5600000.00","current_balance":"4669700.00","notes":null,"is_active":true,"created_by":4,"created_at":"2026-01-27T17:52:53.122797Z","updated_at":"2026-01-28T15:56:42.000000Z"},"sale":null,"transaction_type":{"id":17,"code":"EXPENSE","name":"expense","display_name":"D\\u00e9pense","category":"expense","description":"Sortie d\\u2019argent","created_at":"2025-12-29T14:34:13.404169Z","updated_at":"2025-12-29T14:34:13.404169Z"}}	\N	\N	172.18.0.1	Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36	2026-01-28 15:56:42	2026-01-28 15:56:42	transactions/10
107	4	account_transaction_cancelled	success	App\\Models\\AccountTransaction	9	a annulé la transaction ID 9 RE-20260128-000003 montant 500000.00	{"id":9,"account_id":11,"transaction_type_id":17,"amount":"500000.00","balance_before":"5169700.00","balance_after":"4669700.00","transaction_date":"2026-01-28T15:54:23.000000Z","related_account_id":null,"related_transaction_id":null,"supplier_id":null,"freight_forwarder_id":null,"stock_receipt_id":null,"expense_category_id":19,"recipient_name":"MR lel garde","sale_id":null,"reference_number":"RE-20260128-000003","description":"D\\u00e9pense Gardien \\u00e0 MR lel garde","notes":null,"created_by":4,"created_at":"2026-01-28T15:54:23.000000Z","reversed_transaction_id":null,"planned_expense_id":1,"account":{"id":11,"account_type_id":2,"name":"orange money","account_number":"0290909092","initial_balance":"5600000.00","current_balance":"5169700.00","notes":null,"is_active":true,"created_by":4,"created_at":"2026-01-27T17:52:53.122797Z","updated_at":"2026-01-28T15:56:56.000000Z"},"sale":null,"transaction_type":{"id":17,"code":"EXPENSE","name":"expense","display_name":"D\\u00e9pense","category":"expense","description":"Sortie d\\u2019argent","created_at":"2025-12-29T14:34:13.404169Z","updated_at":"2025-12-29T14:34:13.404169Z"}}	\N	\N	172.18.0.1	Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36	2026-01-28 15:56:56	2026-01-28 15:56:56	transactions/9
108	4	currency_rate_updated	success	App\\Models\\CurrencyRate	6	Taux de change mis à jour pour la date : 2026-01-28 00:00:00	{"id":6,"euro_rate":"5000.0000","yuan_rate":"12000.0000","dollar_rate":"1022.0000","dirham_rate":"0.1000","effective_date":"2026-01-28T00:00:00.000000Z","notes":"milay  eh","created_by":4,"created_at":"2026-01-01T10:32:48.000000Z","updated_at":"2026-01-28T16:33:01.000000Z","baht_rate":"123.0000","is_active":true}	\N	\N	172.18.0.1	Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36	2026-01-28 16:33:01	2026-01-28 16:33:01	comptes/conversion
109	4	customer_created	success	App\\Models\\Customer	4	 a créé le client CL-20260128-0004	{"name":"Anonyme","is_extra_customer":false,"reliability_score":"5.00","loyalty_points":0,"credit_limit":"100000.00","is_active":true,"customer_number":"CL-20260128-0004","updated_at":"2026-01-28T16:34:03.000000Z","created_at":"2026-01-28T16:34:03.000000Z","id":4}	\N	\N	172.18.0.1	Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36	2026-01-28 16:34:03	2026-01-28 16:34:03	clients/4
110	4	sale_created	success	App\\Models\\Sale	4	 a créé une vente rapide d'une valeur de 20000.00	{"sale_number":"VNT-20260128-0004","customer_id":4,"user_id":4,"sale_date":"2026-01-28T16:34:03.000000Z","sale_type":"immediate","subtotal":"20000.00","discount_amount":"0.00","discount_reason":null,"total_amount":"20000.00","payment_status":"paid","payment_method":"mobile_money","notes":null,"status":"CONFIRMED","updated_at":"2026-01-28T16:34:03.000000Z","created_at":"2026-01-28T16:34:03.000000Z","id":4,"items":[{"id":4,"sale_id":4,"variant_id":2,"quantity":1,"unit_price":"20000.00","subtotal":"20000.00","created_at":"2026-01-28T16:34:03.640412Z","variant":{"id":2,"product_id":30,"sku":"JEA--RHSQ","price_adjustment":"0.00","stock_quantity":7,"reserved_quantity":0,"low_stock_threshold":5,"is_active":true,"created_at":"2026-01-28T15:27:55.000000Z","updated_at":"2026-01-28T16:34:03.640412Z","image_path":null,"credit_quantity":0,"available_quantity":7,"product":{"id":30,"name":"Jean fitness","description":null,"category_id":28,"subcategory_id":30,"base_price":"20000.00","is_active":true,"created_at":"2026-01-20T13:34:54.000000Z","updated_at":"2026-01-20T13:34:54.000000Z","image_url":"http:\\/\\/localhost:8000\\/storage\\/uploads\\/images\\/2026\\/01\\/c21574d8-bcdc-429a-8817-28c3d16b4d33.jpeg"}}}],"customer":{"id":4,"name":"Anonyme","phone":null,"address":null,"reliability_score":"5.00","loyalty_points":20,"credit_limit":"100000.00","notes":"[2026-01-28 16:34] +20 points: Achat de 20000 Ar","is_active":true,"created_at":"2026-01-28T16:34:03.000000Z","updated_at":"2026-01-28T16:34:03.640412Z","customer_number":"CL-20260128-0004","is_extra_customer":false},"user":{"id":4,"name":"Administrateur","username":"admin","role":"admin","is_active":true,"created_at":"2025-12-27T10:28:54.853759Z","updated_at":"2025-12-27T10:28:54.853759Z"},"transactions":[{"id":13,"account_id":7,"transaction_type_id":16,"amount":"20000.00","balance_before":"23641571.34","balance_after":"23661571.34","transaction_date":"2026-01-28T16:34:03.000000Z","related_account_id":null,"related_transaction_id":null,"supplier_id":null,"freight_forwarder_id":null,"stock_receipt_id":null,"expense_category_id":null,"recipient_name":null,"sale_id":4,"reference_number":"FAC-20260128-000004","description":"Vente #VNT-20260128-0004","notes":"","created_by":4,"created_at":"2026-01-28T16:34:03.640412Z","reversed_transaction_id":null,"planned_expense_id":null}]}	\N	\N	172.18.0.1	Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36	2026-01-28 16:34:03	2026-01-28 16:34:03	ventes/rapide/4
111	4	customer_created	success	App\\Models\\Customer	3	 a modifié le client CL-20260128-0003	{"id":3,"name":"Anonyme","phone":null,"address":null,"reliability_score":"5.00","loyalty_points":20,"credit_limit":"0.00","notes":"[2026-01-28 15:51] +20 points: Achat de 20000 Ar","is_active":true,"created_at":"2026-01-28T15:50:40.000000Z","updated_at":"2026-01-28T16:37:14.000000Z","customer_number":"CL-20260128-0003","is_extra_customer":false}	\N	\N	172.18.0.1	Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36	2026-01-28 16:37:14	2026-01-28 16:37:14	clients/3
112	4	cash_count_created	success	App\\Models\\CashCount	1	a créé une comptage de billet d'un total de 800000 Ar.	{"count_date":"2026-01-28T00:00:00.000000Z","notes":null,"created_by":4,"total_amount":800000,"updated_at":"2026-01-28T16:37:43.000000Z","created_at":"2026-01-28T16:37:43.000000Z","id":1,"creator":{"id":4,"name":"Administrateur","username":"admin","role":"admin","is_active":true,"created_at":"2025-12-27T10:28:54.853759Z","updated_at":"2025-12-27T10:28:54.853759Z"},"denominations":[{"id":1,"cash_count_id":1,"denomination":20000,"quantity":34,"subtotal":680000,"created_at":"2026-01-28T16:37:43.000000Z","updated_at":"2026-01-28T16:37:43.000000Z"},{"id":2,"cash_count_id":1,"denomination":10000,"quantity":10,"subtotal":100000,"created_at":"2026-01-28T16:37:43.000000Z","updated_at":"2026-01-28T16:37:43.000000Z"},{"id":3,"cash_count_id":1,"denomination":5000,"quantity":4,"subtotal":20000,"created_at":"2026-01-28T16:37:43.000000Z","updated_at":"2026-01-28T16:37:43.000000Z"}]}	\N	\N	172.18.0.1	Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36	2026-01-28 16:37:43	2026-01-28 16:37:43	comptages/1
113	4	customer_created	success	App\\Models\\Customer	5	 a créé le client CL-20260128-0005	{"name":"Anonyme","is_extra_customer":false,"reliability_score":"5.00","loyalty_points":0,"credit_limit":"100000.00","is_active":true,"customer_number":"CL-20260128-0005","updated_at":"2026-01-28T16:53:11.000000Z","created_at":"2026-01-28T16:53:11.000000Z","id":5}	\N	\N	172.18.0.1	Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36	2026-01-28 16:53:11	2026-01-28 16:53:11	clients/5
114	4	sale_created	success	App\\Models\\Sale	5	 a créé une vente rapide d'une valeur de 20000.00	{"sale_number":"VNT-20260128-0005","customer_id":5,"user_id":4,"sale_date":"2026-01-28T16:53:11.000000Z","sale_type":"immediate","subtotal":"20000.00","discount_amount":"0.00","discount_reason":null,"total_amount":"20000.00","payment_status":"paid","payment_method":"mobile_money","notes":null,"status":"CONFIRMED","updated_at":"2026-01-28T16:53:11.000000Z","created_at":"2026-01-28T16:53:11.000000Z","id":5,"items":[{"id":5,"sale_id":5,"variant_id":2,"quantity":1,"unit_price":"20000.00","subtotal":"20000.00","created_at":"2026-01-28T16:53:11.961028Z","variant":{"id":2,"product_id":30,"sku":"JEA--RHSQ","price_adjustment":"0.00","stock_quantity":6,"reserved_quantity":0,"low_stock_threshold":5,"is_active":true,"created_at":"2026-01-28T15:27:55.000000Z","updated_at":"2026-01-28T16:53:11.961028Z","image_path":null,"credit_quantity":0,"available_quantity":6,"product":{"id":30,"name":"Jean fitness","description":null,"category_id":28,"subcategory_id":30,"base_price":"20000.00","is_active":true,"created_at":"2026-01-20T13:34:54.000000Z","updated_at":"2026-01-20T13:34:54.000000Z","image_url":"http:\\/\\/localhost:8000\\/storage\\/uploads\\/images\\/2026\\/01\\/c21574d8-bcdc-429a-8817-28c3d16b4d33.jpeg"}}}],"customer":{"id":5,"name":"Anonyme","phone":null,"address":null,"reliability_score":"5.00","loyalty_points":20,"credit_limit":"100000.00","notes":"[2026-01-28 16:53] +20 points: Achat de 20000 Ar","is_active":true,"created_at":"2026-01-28T16:53:11.000000Z","updated_at":"2026-01-28T16:53:11.961028Z","customer_number":"CL-20260128-0005","is_extra_customer":false},"user":{"id":4,"name":"Administrateur","username":"admin","role":"admin","is_active":true,"created_at":"2025-12-27T10:28:54.853759Z","updated_at":"2025-12-27T10:28:54.853759Z"},"transactions":[{"id":14,"account_id":7,"transaction_type_id":16,"amount":"20000.00","balance_before":"23661571.34","balance_after":"23681571.34","transaction_date":"2026-01-28T16:53:12.000000Z","related_account_id":null,"related_transaction_id":null,"supplier_id":null,"freight_forwarder_id":null,"stock_receipt_id":null,"expense_category_id":null,"recipient_name":null,"sale_id":5,"reference_number":"FAC-20260128-000005","description":"Vente #VNT-20260128-0005","notes":"","created_by":4,"created_at":"2026-01-28T16:53:11.961028Z","reversed_transaction_id":null,"planned_expense_id":null}]}	\N	\N	172.18.0.1	Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36	2026-01-28 16:53:12	2026-01-28 16:53:12	ventes/rapide/5
\.


--
-- Data for Name: attribute_types; Type: TABLE DATA; Schema: public; Owner: express_sale_user
--

COPY public.attribute_types (id, name, display_name, input_type, created_at) FROM stdin;
16	color	couleur	select	2026-01-14 09:24:20.639543
17	pointure	Pointure	number	2026-01-14 09:24:41.363288
18	size	Taille	select	2026-01-14 09:28:55.488978
19	marque	Marque	text	2026-01-20 16:23:27.214977
\.


--
-- Data for Name: attribute_values; Type: TABLE DATA; Schema: public; Owner: express_sale_user
--

COPY public.attribute_values (id, attribute_type_id, value, sort_order, created_at) FROM stdin;
42	16	Rouge	1	2026-01-14 09:24:20.644974
43	16	vert	2	2026-01-14 09:24:20.651634
44	16	bleu	3	2026-01-14 09:24:20.653824
45	16	violet	4	2026-01-14 09:24:20.65591
46	16	beige	5	2026-01-14 09:24:20.660694
47	16	blanc	6	2026-01-14 09:24:20.665549
48	16	noir	7	2026-01-14 09:24:20.667986
49	16	jaune	8	2026-01-14 09:24:20.670009
50	16	arc-en-ciel	9	2026-01-14 09:24:20.671911
51	17	39	999	2026-01-14 09:25:18.408059
52	17	41	999	2026-01-14 09:25:37.665946
53	17	30	999	2026-01-14 09:26:49.748382
54	18	S	1	2026-01-14 09:28:55.491753
56	18	L	3	2026-01-14 09:28:55.499137
57	18	XL	4	2026-01-14 09:28:55.502697
58	18	XXL	5	2026-01-14 09:28:55.505305
59	17	12	999	2026-01-17 23:27:51.973283
60	17	90	999	2026-01-18 00:57:00.882745
61	17	36	999	2026-01-20 16:24:45.385635
62	17	37	999	2026-01-20 16:28:36.820951
63	17	40	999	2026-01-20 16:29:24.724384
64	17	34	999	2026-01-20 16:44:56.509138
55	18	ML	2	2026-01-14 09:28:55.494349
67	17	23	999	2026-01-28 11:35:42.741847
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
expresssale-cache-notifications:last_gen	b:1;	1769621264
\.


--
-- Data for Name: cache_locks; Type: TABLE DATA; Schema: public; Owner: express_sale_user
--

COPY public.cache_locks (key, owner, expiration) FROM stdin;
\.


--
-- Data for Name: cash_count_denominations; Type: TABLE DATA; Schema: public; Owner: express_sale_user
--

COPY public.cash_count_denominations (id, cash_count_id, denomination, quantity, subtotal, created_at, updated_at) FROM stdin;
1	1	20000	34	680000	2026-01-28 16:37:43	2026-01-28 16:37:43
2	1	10000	10	100000	2026-01-28 16:37:43	2026-01-28 16:37:43
3	1	5000	4	20000	2026-01-28 16:37:43	2026-01-28 16:37:43
\.


--
-- Data for Name: cash_counts; Type: TABLE DATA; Schema: public; Owner: express_sale_user
--

COPY public.cash_counts (id, count_date, created_by, notes, created_at, updated_at, total_amount) FROM stdin;
1	2026-01-28	4	\N	2026-01-28 16:37:43	2026-01-28 16:37:43	800000
\.


--
-- Data for Name: categories; Type: TABLE DATA; Schema: public; Owner: express_sale_user
--

COPY public.categories (id, name, description, parent_id, image_url, sort_order, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: company_infos; Type: TABLE DATA; Schema: public; Owner: express_sale_user
--

COPY public.company_infos (name, phone, address, email, logo_path, invoice_signature, printer_path, auto_print_immediate_sale, auto_print_credit, auto_print_credit_payment, auto_print_reservation, auto_print_reservation_complete, auto_print_cash_count) FROM stdin;
Express Sale	0320001192	CASIN MALL Behoririka	Express Sale	http://localhost:8000/storage/uploads/images/2026/01/b049b2e3-73ca-47bc-83f5-66fa4c4c67e0.jpg	Fly Higher	/dev/usb/lp0	f	f	f	f	f	f
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
6	Chine	Shenzen	t	2026-01-14 06:32:27	2026-01-14 06:32:27
7	Chine	Pekin	t	2026-01-14 08:25:52	2026-01-14 08:25:52
\.


--
-- Data for Name: credit_installments; Type: TABLE DATA; Schema: public; Owner: express_sale_user
--

COPY public.credit_installments (id, credit_id, installment_number, due_date, amount_due, amount_paid, status, paid_date, created_at) FROM stdin;
\.


--
-- Data for Name: credits; Type: TABLE DATA; Schema: public; Owner: express_sale_user
--

COPY public.credits (id, sale_id, customer_id, total_amount, amount_paid, amount_due, credit_date, due_date, status, notes, created_at, updated_at, last_payment_date) FROM stdin;
\.


--
-- Data for Name: currency_rates; Type: TABLE DATA; Schema: public; Owner: express_sale_user
--

COPY public.currency_rates (id, euro_rate, yuan_rate, dollar_rate, dirham_rate, effective_date, notes, created_by, created_at, updated_at, baht_rate, is_active) FROM stdin;
7	5200.0000	1200.0000	5200.0000	0.1000	2026-01-10	\N	4	2026-01-10 17:05:19	2026-01-28 16:33:01	145.0000	f
5	0.0204	3.1000	0.0201	0.1000	2025-12-28	Taux du 15 janvier 2025	4	2025-12-28 12:07:35	2026-01-28 16:33:01	0.1201	f
9	5200.0000	700.0000	4800.0000	0.1000	2026-01-14	\N	4	2026-01-14 08:22:06	2026-01-28 16:33:01	140.0000	f
8	5100.0000	645.0000	4578.0000	0.1000	2026-01-12	\N	4	2026-01-10 17:29:38	2026-01-28 16:33:01	135.0000	f
10	6600.0000	340.0000	4500.0000	0.1000	2026-01-27	\N	4	2026-01-27 14:30:28	2026-01-28 16:33:01	130.0000	f
6	5000.0000	12000.0000	1022.0000	0.1000	2026-01-28	milay  eh	4	2026-01-01 10:32:48	2026-01-28 16:33:01	123.0000	t
\.


--
-- Data for Name: customers; Type: TABLE DATA; Schema: public; Owner: express_sale_user
--

COPY public.customers (id, name, phone, address, reliability_score, loyalty_points, credit_limit, notes, is_active, created_at, updated_at, customer_number, is_extra_customer) FROM stdin;
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
18	Livraison à domicile	\N	truck	t	2026-01-18 13:52:14	2026-01-18 13:52:14
19	Gardien	\N	tag	t	2026-01-28 15:53:08	2026-01-28 15:53:08
\.


--
-- Data for Name: failed_jobs; Type: TABLE DATA; Schema: public; Owner: express_sale_user
--

COPY public.failed_jobs (id, uuid, connection, queue, payload, exception, failed_at) FROM stdin;
\.


--
-- Data for Name: freight_forwarders; Type: TABLE DATA; Schema: public; Owner: express_sale_user
--

COPY public.freight_forwarders (id, name, logo_url, contact, service_score, notes, is_active, created_at, updated_at, coordinate_id, total_shipments, total_items_shipped, total_items_delivered, service_rating_sum, service_rating_count, total_value_shipped, total_value_delivered, weighted_service_sum, total_weighted_shipment_value, type) FROM stdin;
1	vaovao	\N	\N	10.00	\N	t	2026-01-28 15:28:30	2026-01-28 15:35:04	\N	1	0	0	0.00	0	0.00	0.00	10220000.00	1022000.00	aerien
\.


--
-- Data for Name: installment_transactions; Type: TABLE DATA; Schema: public; Owner: express_sale_user
--

COPY public.installment_transactions (id, installment_id, transaction_id, amount, payment_date, created_at, updated_at, status) FROM stdin;
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
10	Entrepôt principal	ENTREPÔT-PRINCIPAL	Entrepot	\N	\N	\N	\N	\N	t	2026-01-20 13:05:11	2026-01-20 13:05:11
11	Magasin principal	MAGASIN-PRINCIPAL	MAGASIN PRINCIPAL	\N	\N	\N	\N	\N	t	2026-01-20 13:06:14	2026-01-20 13:06:14
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
21	2026_01_16_103716_create_cash_counts_table	14
22	2026_01_16_104236_create_cash_count_denominations_table	14
24	2026_01_16_211516_create_stock_batches_table	15
25	2026_01_17_115455_create_sale_item_batches	16
26	2026_01_23_081444_add_print_info_to_company_infos	17
27	2026_01_23_213600_add_quality_notes_and_quality_rates_to_stock_receipt_items	18
28	2026_01_25_120407_create_table_planned_expense	19
30	2026_01_27_062809_create_activity_logs	20
\.


--
-- Data for Name: notification_preferences; Type: TABLE DATA; Schema: public; Owner: express_sale_user
--

COPY public.notification_preferences (id, user_id, notification_type, reminder_interval_days, enabled, created_at, updated_at) FROM stdin;
1	4	stock_low	1	t	2026-01-19 17:16:29.368518	2026-01-19 17:16:29.368518
2	4	stock_out	1	t	2026-01-19 17:16:29.368518	2026-01-19 17:16:29.368518
4	4	credit_due	7	t	2026-01-19 17:16:29.368518	2026-01-19 17:16:29.368518
3	4	reservation_expiring	1	t	2026-01-19 17:16:29.368518	2026-01-20 06:43:38
6	8	stock_low	1	t	2026-01-27 12:48:02	2026-01-27 12:48:02
7	8	stock_out	1	t	2026-01-27 12:48:02	2026-01-27 12:48:02
8	8	reservation_expiring	2	t	2026-01-27 12:48:02	2026-01-27 12:48:02
9	8	credit_due	7	t	2026-01-27 12:48:02	2026-01-27 12:48:02
10	8	planned_expense_due	3	t	2026-01-27 12:48:02	2026-01-27 12:48:02
5	4	planned_expense_due	1	t	2026-01-25 12:12:58	2026-01-28 15:57:10
\.


--
-- Data for Name: notifications; Type: TABLE DATA; Schema: public; Owner: express_sale_user
--

COPY public.notifications (id, user_id, type, title, message, is_read, read_at, created_at, severity, data, dedup_key, dismissed_at) FROM stdin;
32	4	credit_due	Échéance de crédit proche	Le crédit #VNT-20260120-0016 du client "christian" a une échéance dans 1 jour(s) - Montant: 9000.00	t	2026-01-20 20:07:32	2026-01-20 23:07:25.041694	warning	{"sale_id": 67, "due_date": "2026-01-22 00:00:00", "credit_id": 19, "days_left": 1.1615157355439816, "amount_due": 9000, "customer_id": 21, "installment_id": 22}	credit_due_19	\N
33	4	credit_due	Échéance de crédit proche	Le crédit #VNT-20260120-0021 du client "chris" a une échéance dans 0 jour(s) - Montant: 9700.00	t	2026-01-20 20:25:49	2026-01-20 23:25:01.139869	warning	{"sale_id": 72, "due_date": "2026-01-21 00:00:00", "credit_id": 22, "days_left": 0.14929237532407408, "amount_due": 9700, "customer_id": 15, "installment_id": 25}	credit_due_22	\N
34	4	stock_low	Stock faible	Le produit "Pointillé" (POI--QAZR) a un stock faible : 5 unités (seuil: 5)	t	2026-01-20 20:38:09	2026-01-20 23:37:38.366778	warning	{"sku": "POI--QAZR", "threshold": 5, "product_id": 33, "variant_id": 39, "stock_quantity": 5}	stock_low_39	\N
11	4	stock_out	Stock épuisé	Le produit "LABUBU" (LAB--3NOC) est en rupture de stock !	t	2026-01-20 14:36:51	2026-01-20 17:36:32.795207	critical	{"sku": "LAB--3NOC", "product_id": 35, "variant_id": 40}	stock_out_40	\N
12	4	stock_out	Stock épuisé	Le produit "LABUBU" (LAB--SINC) est en rupture de stock !	t	2026-01-20 14:36:51	2026-01-20 17:36:32.800109	critical	{"sku": "LAB--SINC", "product_id": 35, "variant_id": 41}	stock_out_41	\N
13	4	stock_out	Stock épuisé	Le produit "Air Jordan" (AIR--2DMS) est en rupture de stock !	t	2026-01-20 14:36:51	2026-01-20 17:36:32.80911	critical	{"sku": "AIR--2DMS", "product_id": 36, "variant_id": 42}	stock_out_42	\N
14	4	stock_out	Stock épuisé	Le produit "Air Jordan" (AIR--3SXC) est en rupture de stock !	t	2026-01-20 14:36:51	2026-01-20 17:36:32.813194	critical	{"sku": "AIR--3SXC", "product_id": 36, "variant_id": 43}	stock_out_43	\N
15	4	stock_out	Stock épuisé	Le produit "Air Jordan" (AIR--0ICY) est en rupture de stock !	t	2026-01-20 14:40:21	2026-01-20 17:40:08.901642	critical	{"sku": "AIR--0ICY", "product_id": 36, "variant_id": 44}	stock_out_44	\N
16	4	stock_low	Stock faible	Le produit "Air Jordan" (AIR--0ICY) a un stock faible : 3 unités (seuil: 5)	t	2026-01-20 15:48:47	2026-01-20 18:47:51.912871	warning	{"sku": "AIR--0ICY", "threshold": 5, "product_id": 36, "variant_id": 44, "stock_quantity": 3}	stock_low_44	\N
19	4	credit_due	Échéance de crédit proche	Le crédit #VNT-20260120-0003 du client "Jean Bas" a une échéance dans 0 jour(s) - Montant: 705000.00	t	2026-01-20 16:01:54	2026-01-20 19:01:25.301713	warning	{"sale_id": 46, "due_date": "2026-01-21 00:00:00", "credit_id": 16, "days_left": 0.3323460709375, "amount_due": 705000, "customer_id": 8, "installment_id": 19}	credit_due_16	\N
18	4	stock_low	Stock faible	Le produit "Air Jordan" (AIR--3SXC) a un stock faible : 5 unités (seuil: 5)	t	2026-01-20 16:02:02	2026-01-20 19:01:25.249577	warning	{"sku": "AIR--3SXC", "threshold": 5, "product_id": 36, "variant_id": 43, "stock_quantity": 5}	stock_low_43	\N
17	4	stock_low	Stock faible	Le produit "LABUBU" (LAB--SINC) a un stock faible : 2 unités (seuil: 5)	t	2026-01-20 16:02:04	2026-01-20 19:01:25.239092	warning	{"sku": "LAB--SINC", "threshold": 5, "product_id": 35, "variant_id": 41, "stock_quantity": 2}	stock_low_41	\N
20	4	stock_low	Stock faible	Le produit "Air Jordan" (AIR--2DMS) a un stock faible : 4 unités (seuil: 5)	t	2026-01-20 18:25:23	2026-01-20 21:25:02.386824	warning	{"sku": "AIR--2DMS", "threshold": 5, "product_id": 36, "variant_id": 42, "stock_quantity": 4}	stock_low_42	\N
21	4	stock_low	Stock faible	Le produit "LABUBU" (LAB--3NOC) a un stock faible : 2 unités (seuil: 5)	t	2026-01-20 18:42:48	2026-01-20 21:42:16.631096	warning	{"sku": "LAB--3NOC", "threshold": 5, "product_id": 35, "variant_id": 40, "stock_quantity": 2}	stock_low_40	\N
22	4	reservation_expiring	Réservation expire bientôt	La réservation #VNT-20260120-0007 du client "chrichri" expire dans 0 jour(s) - Montant restant: 63700.00	t	2026-01-20 18:44:40	2026-01-20 21:44:00.331155	info	{"sale_id": 58, "days_left": 0.2194406462037037, "customer_id": 14, "expiry_date": "2026-01-21 00:00:00", "reservation_id": 18, "remaining_amount": 63700}	reservation_expiring_18	\N
23	4	stock_out	Stock épuisé	Le produit "Noir kely" (NOI--4FTG) est en rupture de stock !	t	2026-01-20 19:28:52	2026-01-20 22:28:34.423631	critical	{"sku": "NOI--4FTG", "product_id": 29, "variant_id": 33}	stock_out_33	\N
24	4	stock_out	Stock épuisé	Le produit "Noir kely" (NOI--OIG0) est en rupture de stock !	t	2026-01-20 19:28:52	2026-01-20 22:28:34.428785	critical	{"sku": "NOI--OIG0", "product_id": 29, "variant_id": 34}	stock_out_34	\N
25	4	stock_out	Stock épuisé	Le produit "Jean Malalaka" (JEA--JOKS) est en rupture de stock !	t	2026-01-20 19:28:52	2026-01-20 22:28:34.432829	critical	{"sku": "JEA--JOKS", "product_id": 31, "variant_id": 35}	stock_out_35	\N
26	4	stock_out	Stock épuisé	Le produit "Jean Malalaka" (JEA--IAUX) est en rupture de stock !	t	2026-01-20 19:28:52	2026-01-20 22:28:34.436105	critical	{"sku": "JEA--IAUX", "product_id": 31, "variant_id": 36}	stock_out_36	\N
27	4	stock_out	Stock épuisé	Le produit "Robe fitness" (ROB--BSBM) est en rupture de stock !	t	2026-01-20 19:28:52	2026-01-20 22:28:34.439312	critical	{"sku": "ROB--BSBM", "product_id": 32, "variant_id": 37}	stock_out_37	\N
28	4	stock_out	Stock épuisé	Le produit "Robe fitness" (ROB--9XLS) est en rupture de stock !	t	2026-01-20 19:28:52	2026-01-20 22:28:34.443068	critical	{"sku": "ROB--9XLS", "product_id": 32, "variant_id": 38}	stock_out_38	\N
29	4	stock_out	Stock épuisé	Le produit "Pointillé" (POI--QAZR) est en rupture de stock !	t	2026-01-20 19:28:52	2026-01-20 22:28:34.447211	critical	{"sku": "POI--QAZR", "product_id": 33, "variant_id": 39}	stock_out_39	\N
30	4	stock_low	Stock faible	Le produit "Robe fitness" (ROB--BSBM) a un stock faible : 5 unités (seuil: 5)	t	2026-01-20 19:37:57	2026-01-20 22:32:30.374387	warning	{"sku": "ROB--BSBM", "threshold": 5, "product_id": 32, "variant_id": 37, "stock_quantity": 5}	stock_low_37	\N
31	4	credit_due	Échéance de crédit proche	Le crédit #VNT-20260120-0015 du client "christian" a une échéance dans 1 jour(s) - Montant: 9000.00	t	2026-01-20 20:05:40	2026-01-20 23:05:04.783834	warning	{"sale_id": 66, "due_date": "2026-01-22 00:00:00", "credit_id": 18, "days_left": 1.1631390933333334, "amount_due": 9000, "customer_id": 21, "installment_id": 21}	credit_due_18	\N
36	4	stock_out	Stock épuisé	Le produit "LABUBU" (LAB--SINC) est en rupture de stock !	t	2026-01-21 18:23:12	2026-01-21 20:36:54.228406	critical	{"sku": "LAB--SINC", "product_id": 35, "variant_id": 41}	stock_out_41	\N
35	4	stock_out	Stock épuisé	Le produit "LABUBU" (LAB--3NOC) est en rupture de stock !	t	2026-01-21 18:23:13	2026-01-21 20:36:54.215159	critical	{"sku": "LAB--3NOC", "product_id": 35, "variant_id": 40}	stock_out_40	\N
37	4	stock_low	Stock faible	Le produit "Robe fitness" (ROB--9XLS) a un stock faible : 5 unités (seuil: 5)	t	2026-01-21 19:02:40	2026-01-21 21:30:53.905832	warning	{"sku": "ROB--9XLS", "threshold": 5, "product_id": 32, "variant_id": 38, "stock_quantity": 5}	stock_low_38	\N
98	4	stock_out	Stock épuisé	Le produit "LABUBU" (LAB--3NOC) est en rupture de stock !	t	2026-01-25 20:06:09	2026-01-25 22:48:37.402603	critical	{"sku": "LAB--3NOC", "product_id": 35, "variant_id": 40}	stock_out_40	2026-01-25 20:03:46
101	4	stock_low	Stock faible	Le produit "Jean Malalaka" (JEA--JOKS) a un stock faible : 3 unités (seuil: 5)	t	2026-01-26 11:47:24	2026-01-26 14:47:07.000689	warning	{"sku": "JEA--JOKS", "threshold": 5, "product_id": 31, "variant_id": 35, "stock_quantity": 3}	stock_low_35	\N
38	4	stock_low	Stock faible	Le produit "Air Jordan" (AIR--0ICY) a un stock faible : 4 unités (seuil: 5)	t	2026-01-21 19:37:56	2026-01-21 22:09:57.648957	warning	{"sku": "AIR--0ICY", "threshold": 5, "product_id": 36, "variant_id": 44, "stock_quantity": 4}	stock_low_44	\N
39	4	stock_low	Stock faible	Le produit "Air Jordan" (AIR--3SXC) a un stock faible : 1 unités (seuil: 5)	t	2026-01-21 19:37:56	2026-01-21 22:09:57.655784	warning	{"sku": "AIR--3SXC", "threshold": 5, "product_id": 36, "variant_id": 43, "stock_quantity": 1}	stock_low_43	\N
42	4	stock_out	Stock épuisé	Le produit "Robe fitness" (ROB--BSBM) est en rupture de stock !	t	2026-01-22 06:14:55	2026-01-22 08:00:17.652796	critical	{"sku": "ROB--BSBM", "product_id": 32, "variant_id": 37}	stock_out_37	\N
41	4	stock_out	Stock épuisé	Le produit "Pointillé" (POI--QAZR) est en rupture de stock !	t	2026-01-22 06:14:56	2026-01-22 08:00:17.649424	critical	{"sku": "POI--QAZR", "product_id": 33, "variant_id": 39}	stock_out_39	\N
40	4	stock_low	Stock faible	Le produit "Air Jordan" (AIR--2DMS) a un stock faible : 1 unités (seuil: 5)	t	2026-01-22 06:14:57	2026-01-22 08:00:17.636519	warning	{"sku": "AIR--2DMS", "threshold": 5, "product_id": 36, "variant_id": 42, "stock_quantity": 1}	stock_low_42	\N
43	4	stock_low	Stock faible	Le produit "Jean Malalaka" (JEA--IAUX) a un stock faible : 5 unités (seuil: 5)	t	2026-01-22 08:35:52	2026-01-22 11:01:42.254365	warning	{"sku": "JEA--IAUX", "threshold": 5, "product_id": 31, "variant_id": 36, "stock_quantity": 5}	stock_low_36	\N
45	4	stock_out	Stock épuisé	Le produit "Air Jordan" (AIR--2DMS) est en rupture de stock !	t	2026-01-22 14:37:21	2026-01-22 11:37:21.613097	critical	{"sku": "AIR--2DMS", "product_id": 36, "variant_id": 42}	stock_out_42	\N
44	4	stock_low	Stock faible	Le produit "Noir kely" (NOI--OIG0) a un stock faible : 4 unités (seuil: 5)	t	2026-01-22 14:37:22	2026-01-22 11:37:21.602892	warning	{"sku": "NOI--OIG0", "threshold": 5, "product_id": 29, "variant_id": 34, "stock_quantity": 4}	stock_low_34	\N
47	4	stock_out	Stock épuisé	Le produit "Jean Malalaka" (JEA--IAUX) est en rupture de stock !	t	2026-01-22 19:37:17	2026-01-22 22:35:09.824746	critical	{"sku": "JEA--IAUX", "product_id": 31, "variant_id": 36}	stock_out_36	\N
46	4	stock_out	Stock épuisé	Le produit "Air Jordan" (AIR--0ICY) est en rupture de stock !	t	2026-01-22 19:37:18	2026-01-22 22:35:09.819374	critical	{"sku": "AIR--0ICY", "product_id": 36, "variant_id": 44}	stock_out_44	\N
54	4	credit_due	Échéance de crédit proche	Le crédit #VNT-20260121-0006 du client "chris" a une échéance dans 6 jour(s) - Montant: 10000.00	t	2026-01-23 10:28:32	2026-01-23 13:28:08.44912	warning	{"sale_id": 83, "due_date": "2026-01-30 00:00:00", "credit_id": 26, "days_left": 6.5637911716898145, "amount_due": 10000, "customer_id": 15, "installment_id": 29}	credit_due_26	\N
48	4	stock_low	Stock faible	Le produit "Robe fitness" (ROB--9XLS) a un stock faible : 2 unités (seuil: 5)	t	2026-01-23 10:38:43	2026-01-23 13:28:08.376274	warning	{"sku": "ROB--9XLS", "threshold": 5, "product_id": 32, "variant_id": 38, "stock_quantity": 2}	stock_low_38	\N
49	4	stock_low	Stock faible	Le produit "Air Jordan" (AIR--3SXC) a un stock faible : 1 unités (seuil: 5)	t	2026-01-23 10:38:43	2026-01-23 13:28:08.385492	warning	{"sku": "AIR--3SXC", "threshold": 5, "product_id": 36, "variant_id": 43, "stock_quantity": 1}	stock_low_43	\N
50	4	stock_out	Stock épuisé	Le produit "Pointillé" (POI--QAZR) est en rupture de stock !	t	2026-01-23 10:38:43	2026-01-23 13:28:08.393778	critical	{"sku": "POI--QAZR", "product_id": 33, "variant_id": 39}	stock_out_39	\N
51	4	stock_out	Stock épuisé	Le produit "Robe fitness" (ROB--BSBM) est en rupture de stock !	t	2026-01-23 10:38:43	2026-01-23 13:28:08.399387	critical	{"sku": "ROB--BSBM", "product_id": 32, "variant_id": 37}	stock_out_37	\N
52	4	stock_out	Stock épuisé	Le produit "LABUBU" (LAB--3NOC) est en rupture de stock !	t	2026-01-23 10:38:43	2026-01-23 13:28:08.40479	critical	{"sku": "LAB--3NOC", "product_id": 35, "variant_id": 40}	stock_out_40	\N
53	4	stock_out	Stock épuisé	Le produit "LABUBU" (LAB--SINC) est en rupture de stock !	t	2026-01-23 10:38:43	2026-01-23 13:28:08.407469	critical	{"sku": "LAB--SINC", "product_id": 35, "variant_id": 41}	stock_out_41	\N
55	4	stock_out	Stock épuisé	Le produit "SUPERSTAR Adidas" (SUP--D3N3) est en rupture de stock !	t	2026-01-23 10:42:30	2026-01-23 13:41:08.819867	critical	{"sku": "SUP--D3N3", "product_id": 28, "variant_id": 45}	stock_out_45	\N
56	4	stock_out	Stock épuisé	Le produit "Sandale Adidas" (SAN--Y1M5) est en rupture de stock !	t	2026-01-23 10:42:30	2026-01-23 13:41:08.828668	critical	{"sku": "SAN--Y1M5", "product_id": 27, "variant_id": 30}	stock_out_30	\N
57	4	stock_out	Stock épuisé	Le produit "Sandale Adidas" (SAN--IZTF) est en rupture de stock !	t	2026-01-23 10:42:30	2026-01-23 13:41:08.831957	critical	{"sku": "SAN--IZTF", "product_id": 27, "variant_id": 31}	stock_out_31	\N
58	4	stock_low	Stock faible	Le produit "Air Jordan" (AIR--2DMS) a un stock faible : 5 unités (seuil: 5)	t	2026-01-23 14:35:50	2026-01-23 15:27:24.67459	warning	{"sku": "AIR--2DMS", "threshold": 5, "product_id": 36, "variant_id": 42, "stock_quantity": 5}	stock_low_42	\N
59	4	stock_low	Stock faible	Le produit "Robe fitness" (ROB--BSBM) a un stock faible : 4 unités (seuil: 5)	t	2026-01-23 20:16:05	2026-01-23 22:25:33.076555	warning	{"sku": "ROB--BSBM", "threshold": 5, "product_id": 32, "variant_id": 37, "stock_quantity": 4}	stock_low_37	\N
60	4	credit_due	Échéance de crédit proche	Le crédit #VNT-20260120-0019 du client "hafa mihitsy" a une échéance dans 4 jour(s) - Montant: 6000.00	t	2026-01-23 20:29:15	2026-01-23 23:17:34.744575	warning	{"sale_id": 70, "due_date": "2026-01-28 00:00:00", "credit_id": 21, "days_left": 4.1544589850810185, "amount_due": 6000, "customer_id": 9, "installment_id": 24}	credit_due_21	\N
79	4	stock_out	Stock épuisé	Le produit "LABUBU" (LAB--3NOC) est en rupture de stock !	t	2026-01-25 17:42:40	2026-01-25 19:51:04.727967	critical	{"sku": "LAB--3NOC", "product_id": 35, "variant_id": 40}	stock_out_40	2026-01-25 19:29:00
80	4	planned_expense_due	Charge fixe à payer bientôt	Ranon'i Magasin Principale (Électricité) dans 0 jour(s) - Montant estimé: 30 000 Ar	t	2026-01-25 18:19:10	2026-01-25 21:18:42.095322	warning	{"due_date": "2026-01-25 00:00:00", "days_left": 0, "frequency": "weekly", "recipient_name": null, "estimated_amount": 30000, "planned_expense_id": 2, "expense_category_id": 2}	planned_expense_due_2_2026-01-25	2026-01-25 19:29:01
82	4	planned_expense_due	Charge fixe à payer bientôt	Ranon'i Magasin Principale (Électricité) dans 0 jour(s) - Montant estimé: 30 000 Ar	t	2026-01-25 18:19:04	2026-01-25 21:18:53.763997	warning	{"due_date": "2026-01-25 00:00:00", "days_left": 0, "frequency": "weekly", "recipient_name": null, "estimated_amount": 30000, "planned_expense_id": 2, "expense_category_id": 2}	planned_expense_due_2_2026-01-25	2026-01-25 19:29:03
62	4	reservation_expiring	Réservation expire bientôt	La réservation #VNT-20260121-0009 du client "christian" expire dans 0 jour(s) - Montant restant: 16000.00	t	2026-01-24 06:47:17	2026-01-24 09:46:52.519966	info	{"sale_id": 86, "days_left": 0.7174477422106481, "customer_id": 21, "expiry_date": "2026-01-25 00:00:00", "reservation_id": 24, "remaining_amount": 16000}	reservation_expiring_24	\N
61	4	stock_out	Stock épuisé	Le produit "Jean Malalaka" (JEA--IAUX) est en rupture de stock !	t	2026-01-24 06:47:19	2026-01-24 09:46:52.488349	critical	{"sku": "JEA--IAUX", "product_id": 31, "variant_id": 36}	stock_out_36	\N
63	4	credit_due	Échéance de crédit proche	Le crédit #VNT-20260123-0008 du client "hello" a une échéance dans 6 jour(s) - Montant: 156000.00	t	2026-01-24 06:47:21	2026-01-24 09:46:52.54047	warning	{"sale_id": 104, "due_date": "2026-01-31 00:00:00", "credit_id": 31, "days_left": 6.717447459803241, "amount_due": 156000, "customer_id": 18, "installment_id": 40}	credit_due_31	\N
64	4	stock_out	Stock épuisé	Le produit "Stan Smith" (STA--R2BC) est en rupture de stock !	t	2026-01-24 06:52:08	2026-01-24 09:51:38.574956	critical	{"sku": "STA--R2BC", "product_id": 26, "variant_id": 29}	stock_out_29	\N
99	4	stock_out	Stock épuisé	Le produit "LABUBU" (LAB--3NOC) est en rupture de stock !	t	2026-01-25 20:06:07	2026-01-25 23:03:51.675934	critical	{"sku": "LAB--3NOC", "product_id": 35, "variant_id": 40}	stock_out_40	\N
65	4	stock_out	Stock épuisé	Le produit "Stan Smith" (STA--Y0JA) est en rupture de stock !	t	2026-01-24 07:24:19	2026-01-24 10:17:00.155245	critical	{"sku": "STA--Y0JA", "product_id": 26, "variant_id": 28}	stock_out_28	\N
66	4	stock_low	Stock faible	Le produit "Stan Smith" (STA--Y0JA) a un stock faible : 2 unités (seuil: 5)	t	2026-01-24 08:26:57	2026-01-24 11:10:10.209962	warning	{"sku": "STA--Y0JA", "threshold": 5, "product_id": 26, "variant_id": 28, "stock_quantity": 2}	stock_low_28	\N
67	4	stock_low	Stock faible	Le produit "Sandale Adidas" (SAN--Y1M5) a un stock faible : 5 unités (seuil: 5)	t	2026-01-24 12:20:44	2026-01-24 14:41:32.802771	warning	{"sku": "SAN--Y1M5", "threshold": 5, "product_id": 27, "variant_id": 30, "stock_quantity": 5}	stock_low_30	\N
68	4	stock_low	Stock faible	Le produit "Air Jordan" (AIR--3SXC) a un stock faible : 1 unités (seuil: 5)	t	2026-01-24 16:16:05	2026-01-24 16:49:32.295994	warning	{"sku": "AIR--3SXC", "threshold": 5, "product_id": 36, "variant_id": 43, "stock_quantity": 1}	stock_low_43	\N
69	4	stock_out	Stock épuisé	Le produit "Pointillé" (POI--QAZR) est en rupture de stock !	t	2026-01-24 16:16:05	2026-01-24 16:49:32.304528	critical	{"sku": "POI--QAZR", "product_id": 33, "variant_id": 39}	stock_out_39	\N
70	4	stock_out	Stock épuisé	Le produit "LABUBU" (LAB--3NOC) est en rupture de stock !	t	2026-01-24 16:16:05	2026-01-24 16:49:32.308698	critical	{"sku": "LAB--3NOC", "product_id": 35, "variant_id": 40}	stock_out_40	\N
71	4	stock_out	Stock épuisé	Le produit "LABUBU" (LAB--SINC) est en rupture de stock !	t	2026-01-24 16:16:05	2026-01-24 16:49:32.311966	critical	{"sku": "LAB--SINC", "product_id": 35, "variant_id": 41}	stock_out_41	2026-01-24 17:19:48
72	4	stock_out	Stock épuisé	Le produit "LABUBU" (LAB--SINC) est en rupture de stock !	t	2026-01-24 17:24:24	2026-01-24 20:20:55.106775	critical	{"sku": "LAB--SINC", "product_id": 35, "variant_id": 41}	stock_out_41	\N
73	4	credit_due	Échéance de crédit proche	Le crédit #VNT-20260122-0004 du client "Jean Bas" a une échéance dans 6 jour(s) - Montant: 2000.00	t	2026-01-25 08:14:55	2026-01-25 11:13:34.896137	warning	{"sale_id": 95, "due_date": "2026-02-01 00:00:00", "credit_id": 28, "days_left": 6.657235065381944, "amount_due": 2000, "customer_id": 8, "installment_id": 31}	credit_due_28	\N
75	4	stock_out	Stock épuisé	Le produit "Stan Smith" (STA--Y0JA) est en rupture de stock !	t	2026-01-25 11:12:30	2026-01-25 13:44:37.423948	critical	{"sku": "STA--Y0JA", "product_id": 26, "variant_id": 28}	stock_out_28	\N
74	4	stock_out	Stock épuisé	Le produit "Jean Malalaka" (JEA--IAUX) est en rupture de stock !	t	2026-01-25 11:12:31	2026-01-25 13:14:37.409371	critical	{"sku": "JEA--IAUX", "product_id": 31, "variant_id": 36}	stock_out_36	\N
76	4	stock_low	Stock faible	Le produit "Sandale Adidas" (SAN--Y1M5) a un stock faible : 5 unités (seuil: 5)	t	2026-01-25 14:52:59	2026-01-25 17:46:37.404189	warning	{"sku": "SAN--Y1M5", "threshold": 5, "product_id": 27, "variant_id": 30, "stock_quantity": 5}	stock_low_30	\N
78	4	stock_out	Stock épuisé	Le produit "Pointillé" (POI--QAZR) est en rupture de stock !	t	2026-01-25 17:42:41	2026-01-25 19:51:04.721473	critical	{"sku": "POI--QAZR", "product_id": 33, "variant_id": 39}	stock_out_39	\N
77	4	stock_low	Stock faible	Le produit "Air Jordan" (AIR--3SXC) a un stock faible : 1 unités (seuil: 5)	t	2026-01-25 17:42:42	2026-01-25 19:51:04.70938	warning	{"sku": "AIR--3SXC", "threshold": 5, "product_id": 36, "variant_id": 43, "stock_quantity": 1}	stock_low_43	\N
93	4	planned_expense_due	Charge fixe à payer bientôt	Ranon'i Magasin Principale (Électricité) dans 0 jour(s) - Montant estimé: 30 000 Ar	t	2026-01-25 18:22:30	2026-01-25 21:21:33.837933	warning	{"due_date": "2026-01-25 00:00:00", "days_left": 0, "frequency": "weekly", "recipient_name": null, "estimated_amount": 30000, "planned_expense_id": 2, "expense_category_id": 2}	planned_expense_due_2_2026-01-25	2026-01-25 19:29:15
92	4	planned_expense_due	Charge fixe à payer bientôt	Ranon'i Magasin Principale (Électricité) dans 0 jour(s) - Montant estimé: 30 000 Ar	t	2026-01-25 18:22:30	2026-01-25 21:21:33.616498	warning	{"due_date": "2026-01-25 00:00:00", "days_left": 0, "frequency": "weekly", "recipient_name": null, "estimated_amount": 30000, "planned_expense_id": 2, "expense_category_id": 2}	planned_expense_due_2_2026-01-25	2026-01-25 19:29:15
91	4	planned_expense_due	Charge fixe à payer bientôt	Ranon'i Magasin Principale (Électricité) dans 0 jour(s) - Montant estimé: 30 000 Ar	t	2026-01-25 18:21:27	2026-01-25 21:21:21.461346	warning	{"due_date": "2026-01-25 00:00:00", "days_left": 0, "frequency": "weekly", "recipient_name": null, "estimated_amount": 30000, "planned_expense_id": 2, "expense_category_id": 2}	planned_expense_due_2_2026-01-25	2026-01-25 19:29:15
90	4	planned_expense_due	Charge fixe à payer bientôt	Ranon'i Magasin Principale (Électricité) dans 0 jour(s) - Montant estimé: 30 000 Ar	t	2026-01-25 18:21:27	2026-01-25 21:20:19.361716	warning	{"due_date": "2026-01-25 00:00:00", "days_left": 0, "frequency": "weekly", "recipient_name": null, "estimated_amount": 30000, "planned_expense_id": 2, "expense_category_id": 2}	planned_expense_due_2_2026-01-25	2026-01-25 19:29:16
89	4	planned_expense_due	Charge fixe à payer bientôt	Ranon'i Magasin Principale (Électricité) dans 0 jour(s) - Montant estimé: 30 000 Ar	t	2026-01-25 18:21:27	2026-01-25 21:20:19.102283	warning	{"due_date": "2026-01-25 00:00:00", "days_left": 0, "frequency": "weekly", "recipient_name": null, "estimated_amount": 30000, "planned_expense_id": 2, "expense_category_id": 2}	planned_expense_due_2_2026-01-25	2026-01-25 19:29:16
88	4	planned_expense_due	Charge fixe à payer bientôt	Ranon'i Magasin Principale (Électricité) dans 0 jour(s) - Montant estimé: 30 000 Ar	t	2026-01-25 18:21:27	2026-01-25 21:20:16.470749	warning	{"due_date": "2026-01-25 00:00:00", "days_left": 0, "frequency": "weekly", "recipient_name": null, "estimated_amount": 30000, "planned_expense_id": 2, "expense_category_id": 2}	planned_expense_due_2_2026-01-25	2026-01-25 19:29:16
87	4	planned_expense_due	Charge fixe à payer bientôt	Ranon'i Magasin Principale (Électricité) dans 0 jour(s) - Montant estimé: 30 000 Ar	t	2026-01-25 18:21:27	2026-01-25 21:20:13.741001	warning	{"due_date": "2026-01-25 00:00:00", "days_left": 0, "frequency": "weekly", "recipient_name": null, "estimated_amount": 30000, "planned_expense_id": 2, "expense_category_id": 2}	planned_expense_due_2_2026-01-25	2026-01-25 19:29:17
86	4	planned_expense_due	Charge fixe à payer bientôt	Ranon'i Magasin Principale (Électricité) dans 0 jour(s) - Montant estimé: 30 000 Ar	t	2026-01-25 18:21:27	2026-01-25 21:20:13.473073	warning	{"due_date": "2026-01-25 00:00:00", "days_left": 0, "frequency": "weekly", "recipient_name": null, "estimated_amount": 30000, "planned_expense_id": 2, "expense_category_id": 2}	planned_expense_due_2_2026-01-25	2026-01-25 19:29:17
85	4	planned_expense_due	Charge fixe à payer bientôt	Ranon'i Magasin Principale (Électricité) dans 0 jour(s) - Montant estimé: 30 000 Ar	t	2026-01-25 18:19:57	2026-01-25 21:19:40.67632	warning	{"due_date": "2026-01-25 00:00:00", "days_left": 0, "frequency": "weekly", "recipient_name": null, "estimated_amount": 30000, "planned_expense_id": 2, "expense_category_id": 2}	planned_expense_due_2_2026-01-25	2026-01-25 19:29:18
84	4	planned_expense_due	Charge fixe à payer bientôt	Ranon'i Magasin Principale (Électricité) dans 0 jour(s) - Montant estimé: 30 000 Ar	t	2026-01-25 18:19:57	2026-01-25 21:19:38.356859	warning	{"due_date": "2026-01-25 00:00:00", "days_left": 0, "frequency": "weekly", "recipient_name": null, "estimated_amount": 30000, "planned_expense_id": 2, "expense_category_id": 2}	planned_expense_due_2_2026-01-25	2026-01-25 19:29:18
83	4	planned_expense_due	Charge fixe à payer bientôt	Ranon'i Magasin Principale (Électricité) dans 0 jour(s) - Montant estimé: 30 000 Ar	t	2026-01-25 18:19:57	2026-01-25 21:19:34.089443	warning	{"due_date": "2026-01-25 00:00:00", "days_left": 0, "frequency": "weekly", "recipient_name": null, "estimated_amount": 30000, "planned_expense_id": 2, "expense_category_id": 2}	planned_expense_due_2_2026-01-25	2026-01-25 19:29:21
100	4	stock_out	Stock épuisé	Le produit "LABUBU" (LAB--SINC) est en rupture de stock !	t	2026-01-26 07:19:09	2026-01-25 23:49:37.423739	critical	{"sku": "LAB--SINC", "product_id": 35, "variant_id": 41}	stock_out_41	\N
103	4	stock_out	Stock épuisé	Le produit "Jean Malalaka" (JEA--IAUX) est en rupture de stock !	t	2026-01-26 13:38:58	2026-01-26 16:14:39.523329	critical	{"sku": "JEA--IAUX", "product_id": 31, "variant_id": 36}	stock_out_36	\N
104	4	stock_out	Stock épuisé	Le produit "Stan Smith" (STA--Y0JA) est en rupture de stock !	t	2026-01-26 13:55:44	2026-01-26 16:49:47.566792	critical	{"sku": "STA--Y0JA", "product_id": 26, "variant_id": 28}	stock_out_28	\N
106	4	stock_out	Stock épuisé	Le produit "Jean Malalaka" (JEA--JOKS) est en rupture de stock !	t	2026-01-26 15:19:46	2026-01-26 18:14:14.089449	critical	{"sku": "JEA--JOKS", "product_id": 31, "variant_id": 35}	stock_out_35	\N
107	4	stock_low	Stock faible	Le produit "Sandale Adidas" (SAN--Y1M5) a un stock faible : 1 unités (seuil: 5)	t	2026-01-26 17:52:04	2026-01-26 20:48:20.317452	warning	{"sku": "SAN--Y1M5", "threshold": 5, "product_id": 27, "variant_id": 30, "stock_quantity": 1}	stock_low_30	\N
108	4	stock_low	Stock faible	Le produit "Air Jordan" (AIR--3SXC) a un stock faible : 1 unités (seuil: 5)	t	2026-01-27 07:12:39	2026-01-27 10:11:46.005108	warning	{"sku": "AIR--3SXC", "threshold": 5, "product_id": 36, "variant_id": 43, "stock_quantity": 1}	stock_low_43	\N
109	4	stock_out	Stock épuisé	Le produit "Pointillé" (POI--QAZR) est en rupture de stock !	t	2026-01-27 07:12:39	2026-01-27 10:11:46.016359	critical	{"sku": "POI--QAZR", "product_id": 33, "variant_id": 39}	stock_out_39	\N
110	4	stock_out	Stock épuisé	Le produit "LABUBU" (LAB--3NOC) est en rupture de stock !	t	2026-01-27 07:12:39	2026-01-27 10:11:46.025004	critical	{"sku": "LAB--3NOC", "product_id": 35, "variant_id": 40}	stock_out_40	\N
81	4	planned_expense_due	Charge fixe à payer bientôt	Ranon'i Magasin Principale (Électricité) dans 0 jour(s) - Montant estimé: 30 000 Ar	t	2026-01-25 18:19:06	2026-01-25 21:18:45.557716	warning	{"due_date": "2026-01-25 00:00:00", "days_left": 0, "frequency": "weekly", "recipient_name": null, "estimated_amount": 30000, "planned_expense_id": 2, "expense_category_id": 2}	planned_expense_due_2_2026-01-25	2026-01-25 19:29:02
97	4	planned_expense_due	Charge fixe à payer bientôt	Ranon'i Magasin Principale (Électricité) dans 0 jour(s) - Montant estimé: 30 000 Ar	t	2026-01-25 18:22:30	2026-01-25 21:22:01.539855	warning	{"due_date": "2026-01-25 00:00:00", "days_left": 0, "frequency": "weekly", "recipient_name": null, "estimated_amount": 30000, "planned_expense_id": 2, "expense_category_id": 2}	planned_expense_due_2_2026-01-25	2026-01-25 19:29:12
96	4	planned_expense_due	Charge fixe à payer bientôt	Ranon'i Magasin Principale (Électricité) dans 0 jour(s) - Montant estimé: 30 000 Ar	t	2026-01-25 18:22:30	2026-01-25 21:21:38.833824	warning	{"due_date": "2026-01-25 00:00:00", "days_left": 0, "frequency": "weekly", "recipient_name": null, "estimated_amount": 30000, "planned_expense_id": 2, "expense_category_id": 2}	planned_expense_due_2_2026-01-25	2026-01-25 19:29:13
95	4	planned_expense_due	Charge fixe à payer bientôt	Ranon'i Magasin Principale (Électricité) dans 0 jour(s) - Montant estimé: 30 000 Ar	t	2026-01-25 18:22:30	2026-01-25 21:21:38.687331	warning	{"due_date": "2026-01-25 00:00:00", "days_left": 0, "frequency": "weekly", "recipient_name": null, "estimated_amount": 30000, "planned_expense_id": 2, "expense_category_id": 2}	planned_expense_due_2_2026-01-25	2026-01-25 19:29:14
94	4	planned_expense_due	Charge fixe à payer bientôt	Ranon'i Magasin Principale (Électricité) dans 0 jour(s) - Montant estimé: 30 000 Ar	t	2026-01-25 18:22:30	2026-01-25 21:21:36.061602	warning	{"due_date": "2026-01-25 00:00:00", "days_left": 0, "frequency": "weekly", "recipient_name": null, "estimated_amount": 30000, "planned_expense_id": 2, "expense_category_id": 2}	planned_expense_due_2_2026-01-25	2026-01-25 19:29:14
102	4	credit_due	Échéance de crédit proche	Le crédit #VNT-20260126-0003 du client "christianHerimanantsoa h" a une échéance dans 2 jour(s) - Montant: 30000.00	t	2026-01-26 11:53:20	2026-01-26 14:52:43.245996	warning	{"sale_id": 117, "due_date": "2026-01-29 00:00:00", "credit_id": 33, "days_left": 2.5050550352430556, "amount_due": 30000, "customer_id": 30, "installment_id": 42}	credit_due_33	\N
105	4	credit_due	Échéance de crédit proche	Le crédit #VNT-20260126-0004 du client "Jean Bas" a une échéance dans 4 jour(s) - Montant: 35000.00	t	2026-01-26 15:19:48	2026-01-26 17:33:53.311315	warning	{"sale_id": 118, "due_date": "2026-01-31 00:00:00", "credit_id": 34, "days_left": 4.393132992696759, "amount_due": 35000, "customer_id": 8, "installment_id": 43}	credit_due_34	\N
111	4	stock_out	Stock épuisé	Le produit "LABUBU" (LAB--SINC) est en rupture de stock !	t	2026-01-27 07:12:39	2026-01-27 10:11:46.040734	critical	{"sku": "LAB--SINC", "product_id": 35, "variant_id": 41}	stock_out_41	\N
112	4	reservation_expiring	Réservation expire bientôt	La réservation #VNT-20260127-0004 du client "christianHerimanantsoa h" expire dans 0 jour(s) - Montant restant: 28000.00	t	2026-01-27 07:30:12	2026-01-27 10:27:33.240197	info	{"sale_id": 124, "days_left": 0.6891986264814814, "customer_id": 30, "expiry_date": "2026-01-28 00:00:00", "reservation_id": 34, "remaining_amount": 28000}	reservation_expiring_34	\N
113	4	stock_low	Stock faible	Le produit "Air Jordan" (AIR--0ICY) a un stock faible : 5 unités (seuil: 5)	t	2026-01-27 08:12:35	2026-01-27 10:59:32.68179	warning	{"sku": "AIR--0ICY", "threshold": 5, "product_id": 36, "variant_id": 44, "stock_quantity": 5}	stock_low_44	\N
114	4	stock_out	Stock épuisé	Le produit "Sandale Adidas" (SAN--Y1M5) est en rupture de stock !	t	2026-01-27 12:06:23	2026-01-27 14:24:56.99565	critical	{"sku": "SAN--Y1M5", "product_id": 27, "variant_id": 30}	stock_out_30	\N
115	4	stock_out	Stock épuisé	Le produit "Sandale Adidas" (SAN--9QG8) est en rupture de stock !	t	2026-01-27 12:06:23	2026-01-27 14:55:59.368625	critical	{"sku": "SAN--9QG8", "product_id": 27, "variant_id": 32}	stock_out_32	\N
116	4	stock_low	Stock faible	Le produit "Noir kely" (NOI--4FTG) a un stock faible : 3 unités (seuil: 5)	t	2026-01-27 12:06:23	2026-01-27 15:05:58.211143	warning	{"sku": "NOI--4FTG", "threshold": 5, "product_id": 29, "variant_id": 33, "stock_quantity": 3}	stock_low_33	\N
117	8	stock_low	Stock faible	Le produit "Noir kely" (NOI--4FTG) a un stock faible : 3 unités (seuil: 5)	f	\N	2026-01-27 15:48:02.36418	warning	{"sku": "NOI--4FTG", "threshold": 5, "product_id": 29, "variant_id": 33, "stock_quantity": 3}	stock_low_33	\N
118	8	stock_low	Stock faible	Le produit "Air Jordan" (AIR--3SXC) a un stock faible : 1 unités (seuil: 5)	f	\N	2026-01-27 15:48:02.372766	warning	{"sku": "AIR--3SXC", "threshold": 5, "product_id": 36, "variant_id": 43, "stock_quantity": 1}	stock_low_43	\N
119	8	stock_out	Stock épuisé	Le produit "Jean Malalaka" (JEA--IAUX) est en rupture de stock !	f	\N	2026-01-27 15:48:02.396996	critical	{"sku": "JEA--IAUX", "product_id": 31, "variant_id": 36}	stock_out_36	\N
120	8	stock_out	Stock épuisé	Le produit "Pointillé" (POI--QAZR) est en rupture de stock !	f	\N	2026-01-27 15:48:02.401591	critical	{"sku": "POI--QAZR", "product_id": 33, "variant_id": 39}	stock_out_39	\N
121	8	stock_out	Stock épuisé	Le produit "Sandale Adidas" (SAN--Y1M5) est en rupture de stock !	f	\N	2026-01-27 15:48:02.405848	critical	{"sku": "SAN--Y1M5", "product_id": 27, "variant_id": 30}	stock_out_30	\N
122	8	stock_out	Stock épuisé	Le produit "Stan Smith" (STA--Y0JA) est en rupture de stock !	f	\N	2026-01-27 15:48:02.409678	critical	{"sku": "STA--Y0JA", "product_id": 26, "variant_id": 28}	stock_out_28	\N
123	8	stock_out	Stock épuisé	Le produit "LABUBU" (LAB--3NOC) est en rupture de stock !	f	\N	2026-01-27 15:48:02.413431	critical	{"sku": "LAB--3NOC", "product_id": 35, "variant_id": 40}	stock_out_40	\N
124	8	stock_out	Stock épuisé	Le produit "Sandale Adidas" (SAN--9QG8) est en rupture de stock !	f	\N	2026-01-27 15:48:02.417073	critical	{"sku": "SAN--9QG8", "product_id": 27, "variant_id": 32}	stock_out_32	\N
125	8	stock_out	Stock épuisé	Le produit "Jean Malalaka" (JEA--JOKS) est en rupture de stock !	f	\N	2026-01-27 15:48:02.420354	critical	{"sku": "JEA--JOKS", "product_id": 31, "variant_id": 35}	stock_out_35	\N
126	8	stock_out	Stock épuisé	Le produit "LABUBU" (LAB--SINC) est en rupture de stock !	f	\N	2026-01-27 15:48:02.42397	critical	{"sku": "LAB--SINC", "product_id": 35, "variant_id": 41}	stock_out_41	\N
127	4	stock_out	Stock épuisé	Le produit "Jean Malalaka" (JEA--IAUX) est en rupture de stock !	t	2026-01-27 17:06:09	2026-01-27 19:18:43.761073	critical	{"sku": "JEA--IAUX", "product_id": 31, "variant_id": 36}	stock_out_36	\N
128	4	stock_out	Stock épuisé	Le produit "Stan Smith" (STA--Y0JA) est en rupture de stock !	t	2026-01-27 17:06:09	2026-01-27 20:05:59.806651	critical	{"sku": "STA--Y0JA", "product_id": 26, "variant_id": 28}	stock_out_28	\N
131	8	stock_low	Stock faible	Le produit "Sandale Adidas" (SAN--IZTF) a un stock faible : 4 unités (seuil: 5)	f	\N	2026-01-27 21:48:43.73478	warning	{"sku": "SAN--IZTF", "threshold": 5, "product_id": 27, "variant_id": 31, "stock_quantity": 4}	stock_low_31	\N
133	8	credit_due	Échéance de crédit proche	Le crédit #VNT-20260127-0015 du client "christian" a une échéance dans 3 jour(s) - Montant: 15000.00	f	\N	2026-01-27 21:48:43.792574	warning	{"sale_id": 135, "due_date": "2026-01-31 00:00:00", "credit_id": 37, "days_left": 3.216159817974537, "amount_due": 15000, "customer_id": 21, "installment_id": 46}	credit_due_37	\N
129	4	stock_out	Stock épuisé	Le produit "Jean Malalaka" (JEA--JOKS) est en rupture de stock !	t	2026-01-27 18:55:23	2026-01-27 21:18:43.785724	critical	{"sku": "JEA--JOKS", "product_id": 31, "variant_id": 35}	stock_out_35	\N
130	4	stock_low	Stock faible	Le produit "Sandale Adidas" (SAN--IZTF) a un stock faible : 4 unités (seuil: 5)	t	2026-01-27 18:55:23	2026-01-27 21:48:43.726133	warning	{"sku": "SAN--IZTF", "threshold": 5, "product_id": 27, "variant_id": 31, "stock_quantity": 4}	stock_low_31	\N
132	4	credit_due	Échéance de crédit proche	Le crédit #VNT-20260127-0015 du client "christian" a une échéance dans 3 jour(s) - Montant: 15000.00	t	2026-01-27 18:55:23	2026-01-27 21:48:43.781251	warning	{"sale_id": 135, "due_date": "2026-01-31 00:00:00", "credit_id": 37, "days_left": 3.2161599597800925, "amount_due": 15000, "customer_id": 21, "installment_id": 46}	credit_due_37	\N
135	8	credit_due	Échéance de crédit proche	Le crédit #VNT-20260127-0016 du client "christian" a une échéance dans 3 jour(s) - Montant: 3625.00	f	\N	2026-01-27 22:12:18.022471	warning	{"sale_id": 136, "due_date": "2026-01-31 00:00:00", "credit_id": 38, "days_left": 3.1997914141087964, "amount_due": 3625, "customer_id": 21, "installment_id": 47}	credit_due_38	\N
134	4	credit_due	Échéance de crédit proche	Le crédit #VNT-20260127-0016 du client "christian" a une échéance dans 3 jour(s) - Montant: 3625.00	t	2026-01-27 20:02:10	2026-01-27 22:12:18.010808	warning	{"sale_id": 136, "due_date": "2026-01-31 00:00:00", "credit_id": 38, "days_left": 3.1997915534837964, "amount_due": 3625, "customer_id": 21, "installment_id": 47}	credit_due_38	\N
137	8	stock_out	Stock épuisé	Le produit "Jean fitness" (JEA--RHSQ) est en rupture de stock !	f	\N	2026-01-28 15:33:52.533173	critical	{"sku": "JEA--RHSQ", "product_id": 30, "variant_id": 2}	stock_out_2	\N
136	4	stock_out	Stock épuisé	Le produit "Jean fitness" (JEA--RHSQ) est en rupture de stock !	t	2026-01-28 15:35:08	2026-01-28 15:33:52.526365	critical	{"sku": "JEA--RHSQ", "product_id": 30, "variant_id": 2}	stock_out_2	\N
139	8	credit_due	Échéance de crédit proche	Le crédit #VNT-20260128-0002 du client "kil milay" a une échéance dans 2 jour(s) - Montant: 17000.00	f	\N	2026-01-28 15:50:53.854405	warning	{"sale_id": 2, "due_date": "2026-01-31 00:00:00", "credit_id": 1, "days_left": 2.3396544727546296, "amount_due": 17000, "customer_id": 2, "installment_id": 1}	credit_due_1	\N
141	8	planned_expense_due	Charge fixe en retard	⚠️ Karama an'i gardien (Gardien) en retard de 2 jour(s) - Montant estimé: 500 000 Ar - MR lel garde	f	\N	2026-01-28 15:54:06.991432	critical	{"due_date": "2026-01-26 00:00:00", "frequency": "daily", "is_overdue": true, "days_overdue": 2, "recipient_name": "MR lel garde", "estimated_amount": 500000, "planned_expense_id": 1, "expense_category_id": 19}	planned_expense_overdue_1	\N
143	8	planned_expense_due	Charge fixe à payer bientôt	Karama an'i gardien (Gardien) dans 0 jour(s) - Montant estimé: 500 000 Ar - MR lel garde	f	\N	2026-01-28 15:54:25.004144	warning	{"due_date": "2026-01-29 00:00:00", "days_left": 0, "frequency": "daily", "recipient_name": "MR lel garde", "estimated_amount": 500000, "planned_expense_id": 1, "expense_category_id": 19}	planned_expense_due_1_2026-01-29	\N
145	8	planned_expense_due	Charge fixe à payer bientôt	Karama an'i gardien (Gardien) dans 0 jour(s) - Montant estimé: 500 000 Ar - MR lel garde	f	\N	2026-01-28 15:54:28.679018	warning	{"due_date": "2026-01-29 00:00:00", "days_left": 0, "frequency": "daily", "recipient_name": "MR lel garde", "estimated_amount": 500000, "planned_expense_id": 1, "expense_category_id": 19}	planned_expense_due_1_2026-01-29	\N
147	8	planned_expense_due	Charge fixe à payer bientôt	Karama an'i gardien (Gardien) dans 0 jour(s) - Montant estimé: 500 000 Ar - MR lel garde	f	\N	2026-01-28 15:54:31.370872	warning	{"due_date": "2026-01-29 00:00:00", "days_left": 0, "frequency": "daily", "recipient_name": "MR lel garde", "estimated_amount": 500000, "planned_expense_id": 1, "expense_category_id": 19}	planned_expense_due_1_2026-01-29	\N
149	8	planned_expense_due	Charge fixe à payer bientôt	Karama an'i gardien (Gardien) dans 0 jour(s) - Montant estimé: 500 000 Ar - MR lel garde	f	\N	2026-01-28 15:54:35.632383	warning	{"due_date": "2026-01-29 00:00:00", "days_left": 0, "frequency": "daily", "recipient_name": "MR lel garde", "estimated_amount": 500000, "planned_expense_id": 1, "expense_category_id": 19}	planned_expense_due_1_2026-01-29	\N
151	8	planned_expense_due	Charge fixe à payer bientôt	Karama an'i gardien (Gardien) dans 0 jour(s) - Montant estimé: 500 000 Ar - MR lel garde	f	\N	2026-01-28 15:54:42.508779	warning	{"due_date": "2026-01-29 00:00:00", "days_left": 0, "frequency": "monthly", "recipient_name": "MR lel garde", "estimated_amount": 500000, "planned_expense_id": 1, "expense_category_id": 19}	planned_expense_due_1_2026-01-29	\N
153	8	planned_expense_due	Charge fixe à payer bientôt	Karama an'i gardien (Gardien) dans 0 jour(s) - Montant estimé: 500 000 Ar - MR lel garde	f	\N	2026-01-28 15:54:57.620558	warning	{"due_date": "2026-01-29 00:00:00", "days_left": 0, "frequency": "monthly", "recipient_name": "MR lel garde", "estimated_amount": 500000, "planned_expense_id": 1, "expense_category_id": 19}	planned_expense_due_1_2026-01-29	\N
155	8	planned_expense_due	Charge fixe à payer bientôt	Karama an'i gardien (Gardien) dans 0 jour(s) - Montant estimé: 500 000 Ar - MR lel garde	f	\N	2026-01-28 15:55:02.414943	warning	{"due_date": "2026-01-29 00:00:00", "days_left": 0, "frequency": "monthly", "recipient_name": "MR lel garde", "estimated_amount": 500000, "planned_expense_id": 1, "expense_category_id": 19}	planned_expense_due_1_2026-01-29	\N
138	4	credit_due	Échéance de crédit proche	Le crédit #VNT-20260128-0002 du client "kil milay" a une échéance dans 2 jour(s) - Montant: 17000.00	t	2026-01-28 15:55:10	2026-01-28 15:50:53.798899	warning	{"sale_id": 2, "due_date": "2026-01-31 00:00:00", "credit_id": 1, "days_left": 2.339655118148148, "amount_due": 17000, "customer_id": 2, "installment_id": 1}	credit_due_1	\N
140	4	planned_expense_due	Charge fixe en retard	⚠️ Karama an'i gardien (Gardien) en retard de 2 jour(s) - Montant estimé: 500 000 Ar - MR lel garde	t	2026-01-28 15:55:10	2026-01-28 15:54:06.980767	critical	{"due_date": "2026-01-26 00:00:00", "frequency": "daily", "is_overdue": true, "days_overdue": 2, "recipient_name": "MR lel garde", "estimated_amount": 500000, "planned_expense_id": 1, "expense_category_id": 19}	planned_expense_overdue_1	\N
142	4	planned_expense_due	Charge fixe à payer bientôt	Karama an'i gardien (Gardien) dans 0 jour(s) - Montant estimé: 500 000 Ar - MR lel garde	t	2026-01-28 15:55:10	2026-01-28 15:54:24.992437	warning	{"due_date": "2026-01-29 00:00:00", "days_left": 0, "frequency": "daily", "recipient_name": "MR lel garde", "estimated_amount": 500000, "planned_expense_id": 1, "expense_category_id": 19}	planned_expense_due_1_2026-01-29	\N
144	4	planned_expense_due	Charge fixe à payer bientôt	Karama an'i gardien (Gardien) dans 0 jour(s) - Montant estimé: 500 000 Ar - MR lel garde	t	2026-01-28 15:55:10	2026-01-28 15:54:28.668577	warning	{"due_date": "2026-01-29 00:00:00", "days_left": 0, "frequency": "daily", "recipient_name": "MR lel garde", "estimated_amount": 500000, "planned_expense_id": 1, "expense_category_id": 19}	planned_expense_due_1_2026-01-29	\N
146	4	planned_expense_due	Charge fixe à payer bientôt	Karama an'i gardien (Gardien) dans 0 jour(s) - Montant estimé: 500 000 Ar - MR lel garde	t	2026-01-28 15:55:10	2026-01-28 15:54:31.360503	warning	{"due_date": "2026-01-29 00:00:00", "days_left": 0, "frequency": "daily", "recipient_name": "MR lel garde", "estimated_amount": 500000, "planned_expense_id": 1, "expense_category_id": 19}	planned_expense_due_1_2026-01-29	\N
148	4	planned_expense_due	Charge fixe à payer bientôt	Karama an'i gardien (Gardien) dans 0 jour(s) - Montant estimé: 500 000 Ar - MR lel garde	t	2026-01-28 15:55:10	2026-01-28 15:54:35.621226	warning	{"due_date": "2026-01-29 00:00:00", "days_left": 0, "frequency": "daily", "recipient_name": "MR lel garde", "estimated_amount": 500000, "planned_expense_id": 1, "expense_category_id": 19}	planned_expense_due_1_2026-01-29	\N
150	4	planned_expense_due	Charge fixe à payer bientôt	Karama an'i gardien (Gardien) dans 0 jour(s) - Montant estimé: 500 000 Ar - MR lel garde	t	2026-01-28 15:55:10	2026-01-28 15:54:42.497383	warning	{"due_date": "2026-01-29 00:00:00", "days_left": 0, "frequency": "monthly", "recipient_name": "MR lel garde", "estimated_amount": 500000, "planned_expense_id": 1, "expense_category_id": 19}	planned_expense_due_1_2026-01-29	\N
152	4	planned_expense_due	Charge fixe à payer bientôt	Karama an'i gardien (Gardien) dans 0 jour(s) - Montant estimé: 500 000 Ar - MR lel garde	t	2026-01-28 15:55:10	2026-01-28 15:54:57.609214	warning	{"due_date": "2026-01-29 00:00:00", "days_left": 0, "frequency": "monthly", "recipient_name": "MR lel garde", "estimated_amount": 500000, "planned_expense_id": 1, "expense_category_id": 19}	planned_expense_due_1_2026-01-29	\N
154	4	planned_expense_due	Charge fixe à payer bientôt	Karama an'i gardien (Gardien) dans 0 jour(s) - Montant estimé: 500 000 Ar - MR lel garde	t	2026-01-28 15:55:10	2026-01-28 15:55:02.40293	warning	{"due_date": "2026-01-29 00:00:00", "days_left": 0, "frequency": "monthly", "recipient_name": "MR lel garde", "estimated_amount": 500000, "planned_expense_id": 1, "expense_category_id": 19}	planned_expense_due_1_2026-01-29	\N
157	8	planned_expense_due	Charge fixe à payer bientôt	Karama an'i gardien (Gardien) dans 0 jour(s) - Montant estimé: 500 000 Ar - MR lel garde	f	\N	2026-01-28 15:55:19.694145	warning	{"due_date": "2026-01-29 00:00:00", "days_left": 0, "frequency": "monthly", "recipient_name": "MR lel garde", "estimated_amount": 500000, "planned_expense_id": 1, "expense_category_id": 19}	planned_expense_due_1_2026-01-29	\N
156	4	planned_expense_due	Charge fixe à payer bientôt	Karama an'i gardien (Gardien) dans 0 jour(s) - Montant estimé: 500 000 Ar - MR lel garde	f	\N	2026-01-28 15:55:19.68647	warning	{"due_date": "2026-01-29 00:00:00", "days_left": 0, "frequency": "monthly", "recipient_name": "MR lel garde", "estimated_amount": 500000, "planned_expense_id": 1, "expense_category_id": 19}	planned_expense_due_1_2026-01-29	2026-01-28 15:55:33
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
76	App\\Models\\User	4	express-sale-token	28e6eda57b4f4493c365edca03ad7349ff9dae46da17efcc7e44b5546bbcf24c	["*"]	2026-01-28 17:05:37	\N	2026-01-28 15:27:36	2026-01-28 17:05:37
\.


--
-- Data for Name: planned_expenses; Type: TABLE DATA; Schema: public; Owner: express_sale_user
--

COPY public.planned_expenses (id, expense_category_id, name, description, estimated_amount, frequency, day_of_week, day_of_month, start_date, end_date, next_due_date, recipient_name, is_active, created_at, updated_at) FROM stdin;
1	19	Karama an'i gardien	\N	500000.00	monthly	\N	1	2026-01-26	\N	2026-02-01	MR lel garde	f	2026-01-28 15:54:05	2026-01-28 16:28:45
\.


--
-- Data for Name: product_attributes; Type: TABLE DATA; Schema: public; Owner: express_sale_user
--

COPY public.product_attributes (id, product_id, attribute_type_id, is_required, created_at) FROM stdin;
\.


--
-- Data for Name: product_variant_locations; Type: TABLE DATA; Schema: public; Owner: express_sale_user
--

COPY public.product_variant_locations (id, variant_id, location_id, quantity, notes, created_at, updated_at, reserved_quantity) FROM stdin;
\.


--
-- Data for Name: product_variants; Type: TABLE DATA; Schema: public; Owner: express_sale_user
--

COPY public.product_variants (id, product_id, sku, price_adjustment, stock_quantity, reserved_quantity, low_stock_threshold, is_active, created_at, updated_at, image_path, credit_quantity, available_quantity) FROM stdin;
\.


--
-- Data for Name: products; Type: TABLE DATA; Schema: public; Owner: express_sale_user
--

COPY public.products (id, name, description, category_id, subcategory_id, base_price, is_active, created_at, updated_at, image_url) FROM stdin;
\.


--
-- Data for Name: reservation_deposits; Type: TABLE DATA; Schema: public; Owner: express_sale_user
--

COPY public.reservation_deposits (id, reservation_id, transaction_id, amount, payment_date, notes, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: reservations; Type: TABLE DATA; Schema: public; Owner: express_sale_user
--

COPY public.reservations (id, sale_id, customer_id, reservation_date, expiry_date, total_amount, deposit_amount, remaining_amount, status, cancellation_reason, completed_at, created_at, updated_at, transaction_complete_id) FROM stdin;
\.


--
-- Data for Name: sale_item_batches; Type: TABLE DATA; Schema: public; Owner: express_sale_user
--

COPY public.sale_item_batches (id, sale_item_id, batch_id, quantity, unit_price_at_sale, created_at, updated_at, discount_at_sale, status, location_id) FROM stdin;
\.


--
-- Data for Name: sale_items; Type: TABLE DATA; Schema: public; Owner: express_sale_user
--

COPY public.sale_items (id, sale_id, variant_id, quantity, unit_price, subtotal, created_at) FROM stdin;
\.


--
-- Data for Name: sales; Type: TABLE DATA; Schema: public; Owner: express_sale_user
--

COPY public.sales (id, sale_number, customer_id, user_id, sale_date, sale_type, subtotal, discount_amount, discount_reason, total_amount, payment_status, notes, created_at, updated_at, payment_method, status) FROM stdin;
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
Jk1wgniksnZRUGe454ZNaR07M3M5xOj8SZmEitMC	\N	127.0.0.1	Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36	YTozOntzOjY6Il90b2tlbiI7czo0MDoiOGNxd29VMkRsNVk0TkxpWjAybXdqT0lXN2dPaTVyOVBzNG5pQThWdCI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjE6Imh0dHA6Ly9sb2NhbGhvc3Q6ODAwMCI7czo1OiJyb3V0ZSI7Tjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==	1768364171
3vCBmbTHm8gQ2Cks31c3iY1aI2DKrfVxhTTym5LU	\N	127.0.0.1	PostmanRuntime/7.51.0	YTozOntzOjY6Il90b2tlbiI7czo0MDoiWkZObU5XRFVzR2hiaGJzWjhqU1BFZTVlc3dTaXJEMXFIQlpBS3lieSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjE6Imh0dHA6Ly9sb2NhbGhvc3Q6ODAwMCI7czo1OiJyb3V0ZSI7Tjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==	1768564114
\.


--
-- Data for Name: stock_batches; Type: TABLE DATA; Schema: public; Owner: express_sale_user
--

COPY public.stock_batches (id, variant_id, stock_receipt_item_id, batch_number, initial_quantity, remaining_quantity, supplier_unit_cost, freight_cost_per_unit, other_costs_per_unit, cost_status, cost_validated_at, received_date, reserved_quantity, available_quantity) FROM stdin;
\.


--
-- Data for Name: stock_movements; Type: TABLE DATA; Schema: public; Owner: express_sale_user
--

COPY public.stock_movements (id, variant_id, from_location_id, to_location_id, quantity, movement_type, sale_id, stock_receipt_id, performed_by, reason, notes, created_at, batch_id, loss_type) FROM stdin;
\.


--
-- Data for Name: stock_receipt_item_ratings; Type: TABLE DATA; Schema: public; Owner: express_sale_user
--

COPY public.stock_receipt_item_ratings (id, stock_receipt_item_id, attribute_type_id, conformity_rating, notes, rated_by, created_at) FROM stdin;
\.


--
-- Data for Name: stock_receipt_items; Type: TABLE DATA; Schema: public; Owner: express_sale_user
--

COPY public.stock_receipt_items (id, stock_receipt_id, variant_id, quantity_ordered, quantity_received, unit_cost_ariary, notes, created_at, quality_rating, quality_notes) FROM stdin;
\.


--
-- Data for Name: stock_receipts; Type: TABLE DATA; Schema: public; Owner: express_sale_user
--

COPY public.stock_receipts (id, receipt_number, supplier_id, freight_forwarder_id, total_cost_ariary, status, notes, created_by, created_at, updated_at, expected_delivery_date, actual_delivery_date, cost_validated_at, cost_validated_by) FROM stdin;
\.


--
-- Data for Name: suppliers; Type: TABLE DATA; Schema: public; Owner: express_sale_user
--

COPY public.suppliers (id, name, wechat, profile, contact, accessibility_notes, reliability_score, logo_url, is_active, created_at, updated_at, coordinate_id) FROM stdin;
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
4	Administrateur	admin	$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi	admin	t	2025-12-27 10:28:54.853759	2025-12-27 10:28:54.853759
2	christianHerimanantsoa	herimanantsoa51	$2y$12$M8EzX7//xt8Iu4owbXvBDej4KRZ0rDR9WqUhIHk8PFgTR8Pexz.vG	vendeur	t	2025-12-19 11:09:55	2026-01-16 11:50:50.889489
8	zareo mitsam	jmtsam	$2y$12$NMWizwotUVddqAAqFgw7we.14h/.g9vALa2koFuSf9UAAAiT9Ejiq	admin	t	2026-01-27 12:45:52	2026-01-27 12:45:52
7	heyo	coco	$2y$12$9tt9ozqglZdRnX.cnYj6mORSZc8phoMYoVYdnz5xYul0avYoncPAG	vendeur	t	2026-01-16 07:22:50	2026-01-27 22:50:35.616634
\.


--
-- Data for Name: variant_attribute_values; Type: TABLE DATA; Schema: public; Owner: express_sale_user
--

COPY public.variant_attribute_values (id, variant_id, attribute_value_id, created_at) FROM stdin;
\.


--
-- Name: account_transactions_id_seq; Type: SEQUENCE SET; Schema: public; Owner: express_sale_user
--

SELECT pg_catalog.setval('public.account_transactions_id_seq', 14, true);


--
-- Name: account_types_id_seq; Type: SEQUENCE SET; Schema: public; Owner: express_sale_user
--

SELECT pg_catalog.setval('public.account_types_id_seq', 3, true);


--
-- Name: accounts_id_seq; Type: SEQUENCE SET; Schema: public; Owner: express_sale_user
--

SELECT pg_catalog.setval('public.accounts_id_seq', 11, true);


--
-- Name: activity_logs_id_seq; Type: SEQUENCE SET; Schema: public; Owner: express_sale_user
--

SELECT pg_catalog.setval('public.activity_logs_id_seq', 114, true);


--
-- Name: attribute_types_id_seq; Type: SEQUENCE SET; Schema: public; Owner: express_sale_user
--

SELECT pg_catalog.setval('public.attribute_types_id_seq', 19, true);


--
-- Name: attribute_values_id_seq; Type: SEQUENCE SET; Schema: public; Owner: express_sale_user
--

SELECT pg_catalog.setval('public.attribute_values_id_seq', 67, true);


--
-- Name: audit_logs_id_seq; Type: SEQUENCE SET; Schema: public; Owner: express_sale_user
--

SELECT pg_catalog.setval('public.audit_logs_id_seq', 1, false);


--
-- Name: cash_count_denominations_id_seq; Type: SEQUENCE SET; Schema: public; Owner: express_sale_user
--

SELECT pg_catalog.setval('public.cash_count_denominations_id_seq', 3, true);


--
-- Name: cash_counts_id_seq; Type: SEQUENCE SET; Schema: public; Owner: express_sale_user
--

SELECT pg_catalog.setval('public.cash_counts_id_seq', 1, true);


--
-- Name: categories_id_seq; Type: SEQUENCE SET; Schema: public; Owner: express_sale_user
--

SELECT pg_catalog.setval('public.categories_id_seq', 30, true);


--
-- Name: coordinates_id_seq; Type: SEQUENCE SET; Schema: public; Owner: express_sale_user
--

SELECT pg_catalog.setval('public.coordinates_id_seq', 7, true);


--
-- Name: credit_installments_id_seq; Type: SEQUENCE SET; Schema: public; Owner: express_sale_user
--

SELECT pg_catalog.setval('public.credit_installments_id_seq', 1, true);


--
-- Name: credits_id_seq; Type: SEQUENCE SET; Schema: public; Owner: express_sale_user
--

SELECT pg_catalog.setval('public.credits_id_seq', 1, true);


--
-- Name: currency_rates_id_seq; Type: SEQUENCE SET; Schema: public; Owner: express_sale_user
--

SELECT pg_catalog.setval('public.currency_rates_id_seq', 10, true);


--
-- Name: customers_id_seq; Type: SEQUENCE SET; Schema: public; Owner: express_sale_user
--

SELECT pg_catalog.setval('public.customers_id_seq', 5, true);


--
-- Name: expense_categories_id_seq; Type: SEQUENCE SET; Schema: public; Owner: express_sale_user
--

SELECT pg_catalog.setval('public.expense_categories_id_seq', 19, true);


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

SELECT pg_catalog.setval('public.installment_transactions_id_seq', 1, false);


--
-- Name: jobs_id_seq; Type: SEQUENCE SET; Schema: public; Owner: express_sale_user
--

SELECT pg_catalog.setval('public.jobs_id_seq', 1, false);


--
-- Name: locations_id_seq; Type: SEQUENCE SET; Schema: public; Owner: express_sale_user
--

SELECT pg_catalog.setval('public.locations_id_seq', 11, true);


--
-- Name: migrations_id_seq; Type: SEQUENCE SET; Schema: public; Owner: express_sale_user
--

SELECT pg_catalog.setval('public.migrations_id_seq', 30, true);


--
-- Name: notification_preferences_id_seq; Type: SEQUENCE SET; Schema: public; Owner: express_sale_user
--

SELECT pg_catalog.setval('public.notification_preferences_id_seq', 10, true);


--
-- Name: notifications_id_seq; Type: SEQUENCE SET; Schema: public; Owner: express_sale_user
--

SELECT pg_catalog.setval('public.notifications_id_seq', 157, true);


--
-- Name: personal_access_tokens_id_seq; Type: SEQUENCE SET; Schema: public; Owner: express_sale_user
--

SELECT pg_catalog.setval('public.personal_access_tokens_id_seq', 76, true);


--
-- Name: planned_expenses_id_seq; Type: SEQUENCE SET; Schema: public; Owner: express_sale_user
--

SELECT pg_catalog.setval('public.planned_expenses_id_seq', 1, true);


--
-- Name: product_attributes_id_seq; Type: SEQUENCE SET; Schema: public; Owner: express_sale_user
--

SELECT pg_catalog.setval('public.product_attributes_id_seq', 73, true);


--
-- Name: product_variant_locations_id_seq; Type: SEQUENCE SET; Schema: public; Owner: express_sale_user
--

SELECT pg_catalog.setval('public.product_variant_locations_id_seq', 2, true);


--
-- Name: product_variants_id_seq; Type: SEQUENCE SET; Schema: public; Owner: express_sale_user
--

SELECT pg_catalog.setval('public.product_variants_id_seq', 2, true);


--
-- Name: products_id_seq; Type: SEQUENCE SET; Schema: public; Owner: express_sale_user
--

SELECT pg_catalog.setval('public.products_id_seq', 37, true);


--
-- Name: reservation_deposits_id_seq; Type: SEQUENCE SET; Schema: public; Owner: express_sale_user
--

SELECT pg_catalog.setval('public.reservation_deposits_id_seq', 1, false);


--
-- Name: reservations_id_seq; Type: SEQUENCE SET; Schema: public; Owner: express_sale_user
--

SELECT pg_catalog.setval('public.reservations_id_seq', 1, true);


--
-- Name: sale_item_batches_id_seq; Type: SEQUENCE SET; Schema: public; Owner: express_sale_user
--

SELECT pg_catalog.setval('public.sale_item_batches_id_seq', 5, true);


--
-- Name: sale_items_id_seq; Type: SEQUENCE SET; Schema: public; Owner: express_sale_user
--

SELECT pg_catalog.setval('public.sale_items_id_seq', 5, true);


--
-- Name: sales_id_seq; Type: SEQUENCE SET; Schema: public; Owner: express_sale_user
--

SELECT pg_catalog.setval('public.sales_id_seq', 5, true);


--
-- Name: stock_batches_id_seq; Type: SEQUENCE SET; Schema: public; Owner: express_sale_user
--

SELECT pg_catalog.setval('public.stock_batches_id_seq', 1, true);


--
-- Name: stock_movements_id_seq; Type: SEQUENCE SET; Schema: public; Owner: express_sale_user
--

SELECT pg_catalog.setval('public.stock_movements_id_seq', 16, true);


--
-- Name: stock_receipt_item_ratings_id_seq; Type: SEQUENCE SET; Schema: public; Owner: express_sale_user
--

SELECT pg_catalog.setval('public.stock_receipt_item_ratings_id_seq', 2, true);


--
-- Name: stock_receipt_items_id_seq; Type: SEQUENCE SET; Schema: public; Owner: express_sale_user
--

SELECT pg_catalog.setval('public.stock_receipt_items_id_seq', 1, true);


--
-- Name: stock_receipts_id_seq; Type: SEQUENCE SET; Schema: public; Owner: express_sale_user
--

SELECT pg_catalog.setval('public.stock_receipts_id_seq', 1, true);


--
-- Name: suppliers_id_seq; Type: SEQUENCE SET; Schema: public; Owner: express_sale_user
--

SELECT pg_catalog.setval('public.suppliers_id_seq', 8, true);


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

SELECT pg_catalog.setval('public.users_id_seq', 8, true);


--
-- Name: variant_attribute_values_id_seq; Type: SEQUENCE SET; Schema: public; Owner: express_sale_user
--

SELECT pg_catalog.setval('public.variant_attribute_values_id_seq', 4, true);


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
-- Name: activity_logs activity_logs_pkey; Type: CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.activity_logs
    ADD CONSTRAINT activity_logs_pkey PRIMARY KEY (id);


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
-- Name: cash_count_denominations cash_count_denominations_cash_count_id_denomination_unique; Type: CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.cash_count_denominations
    ADD CONSTRAINT cash_count_denominations_cash_count_id_denomination_unique UNIQUE (cash_count_id, denomination);


--
-- Name: cash_count_denominations cash_count_denominations_pkey; Type: CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.cash_count_denominations
    ADD CONSTRAINT cash_count_denominations_pkey PRIMARY KEY (id);


--
-- Name: cash_counts cash_counts_count_date_unique; Type: CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.cash_counts
    ADD CONSTRAINT cash_counts_count_date_unique UNIQUE (count_date);


--
-- Name: cash_counts cash_counts_pkey; Type: CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.cash_counts
    ADD CONSTRAINT cash_counts_pkey PRIMARY KEY (id);


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
-- Name: notification_preferences notification_preferences_pkey; Type: CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.notification_preferences
    ADD CONSTRAINT notification_preferences_pkey PRIMARY KEY (id);


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
-- Name: planned_expenses planned_expenses_pkey; Type: CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.planned_expenses
    ADD CONSTRAINT planned_expenses_pkey PRIMARY KEY (id);


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
-- Name: sale_item_batches sale_item_batches_pkey; Type: CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.sale_item_batches
    ADD CONSTRAINT sale_item_batches_pkey PRIMARY KEY (id);


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
-- Name: stock_batches stock_batches_batch_number_unique; Type: CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.stock_batches
    ADD CONSTRAINT stock_batches_batch_number_unique UNIQUE (batch_number);


--
-- Name: stock_batches stock_batches_pkey; Type: CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.stock_batches
    ADD CONSTRAINT stock_batches_pkey PRIMARY KEY (id);


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
-- Name: notification_preferences unique_user_notification_type; Type: CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.notification_preferences
    ADD CONSTRAINT unique_user_notification_type UNIQUE (user_id, notification_type);


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
-- Name: activity_logs_action_status_index; Type: INDEX; Schema: public; Owner: express_sale_user
--

CREATE INDEX activity_logs_action_status_index ON public.activity_logs USING btree (action, status);


--
-- Name: activity_logs_user_id_created_at_index; Type: INDEX; Schema: public; Owner: express_sale_user
--

CREATE INDEX activity_logs_user_id_created_at_index ON public.activity_logs USING btree (user_id, created_at);


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
-- Name: idx_attribute_values_type_value; Type: INDEX; Schema: public; Owner: express_sale_user
--

CREATE INDEX idx_attribute_values_type_value ON public.attribute_values USING btree (attribute_type_id, value);


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
-- Name: idx_cost_status; Type: INDEX; Schema: public; Owner: express_sale_user
--

CREATE INDEX idx_cost_status ON public.stock_batches USING btree (cost_status);


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
-- Name: idx_notif_prefs_type; Type: INDEX; Schema: public; Owner: express_sale_user
--

CREATE INDEX idx_notif_prefs_type ON public.notification_preferences USING btree (notification_type);


--
-- Name: idx_notif_prefs_user; Type: INDEX; Schema: public; Owner: express_sale_user
--

CREATE INDEX idx_notif_prefs_user ON public.notification_preferences USING btree (user_id);


--
-- Name: idx_notifications_created; Type: INDEX; Schema: public; Owner: express_sale_user
--

CREATE INDEX idx_notifications_created ON public.notifications USING btree (created_at DESC);


--
-- Name: idx_notifications_dismissed; Type: INDEX; Schema: public; Owner: express_sale_user
--

CREATE INDEX idx_notifications_dismissed ON public.notifications USING btree (dismissed_at) WHERE (dismissed_at IS NULL);


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
-- Name: idx_product_variants_product_active_stock; Type: INDEX; Schema: public; Owner: express_sale_user
--

CREATE INDEX idx_product_variants_product_active_stock ON public.product_variants USING btree (product_id, is_active, stock_quantity);


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
-- Name: idx_products_name_gin; Type: INDEX; Schema: public; Owner: express_sale_user
--

CREATE INDEX idx_products_name_gin ON public.products USING gin (to_tsvector('french'::regconfig, (name)::text));


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
-- Name: idx_sib_batch; Type: INDEX; Schema: public; Owner: express_sale_user
--

CREATE INDEX idx_sib_batch ON public.sale_item_batches USING btree (batch_id);


--
-- Name: idx_sib_date; Type: INDEX; Schema: public; Owner: express_sale_user
--

CREATE INDEX idx_sib_date ON public.sale_item_batches USING btree (created_at);


--
-- Name: idx_sib_sale_item; Type: INDEX; Schema: public; Owner: express_sale_user
--

CREATE INDEX idx_sib_sale_item ON public.sale_item_batches USING btree (sale_item_id);


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
-- Name: idx_variant_attribute_values_attr_value; Type: INDEX; Schema: public; Owner: express_sale_user
--

CREATE INDEX idx_variant_attribute_values_attr_value ON public.variant_attribute_values USING btree (attribute_value_id);


--
-- Name: idx_variant_attribute_values_variant; Type: INDEX; Schema: public; Owner: express_sale_user
--

CREATE INDEX idx_variant_attribute_values_variant ON public.variant_attribute_values USING btree (variant_id);


--
-- Name: idx_variant_remaining; Type: INDEX; Schema: public; Owner: express_sale_user
--

CREATE INDEX idx_variant_remaining ON public.stock_batches USING btree (variant_id, remaining_quantity);


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
-- Name: planned_expenses_frequency_index; Type: INDEX; Schema: public; Owner: express_sale_user
--

CREATE INDEX planned_expenses_frequency_index ON public.planned_expenses USING btree (frequency);


--
-- Name: planned_expenses_is_active_next_due_date_index; Type: INDEX; Schema: public; Owner: express_sale_user
--

CREATE INDEX planned_expenses_is_active_next_due_date_index ON public.planned_expenses USING btree (is_active, next_due_date);


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
-- Name: account_transactions account_transactions_planned_expense_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.account_transactions
    ADD CONSTRAINT account_transactions_planned_expense_id_foreign FOREIGN KEY (planned_expense_id) REFERENCES public.planned_expenses(id) ON DELETE SET NULL;


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
-- Name: activity_logs activity_logs_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.activity_logs
    ADD CONSTRAINT activity_logs_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


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
-- Name: cash_count_denominations cash_count_denominations_cash_count_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.cash_count_denominations
    ADD CONSTRAINT cash_count_denominations_cash_count_id_foreign FOREIGN KEY (cash_count_id) REFERENCES public.cash_counts(id) ON DELETE CASCADE;


--
-- Name: cash_counts cash_counts_created_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.cash_counts
    ADD CONSTRAINT cash_counts_created_by_foreign FOREIGN KEY (created_by) REFERENCES public.users(id) ON DELETE RESTRICT;


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
-- Name: notification_preferences notification_preferences_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.notification_preferences
    ADD CONSTRAINT notification_preferences_user_id_fkey FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: notifications notifications_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.notifications
    ADD CONSTRAINT notifications_user_id_fkey FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: planned_expenses planned_expenses_expense_category_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.planned_expenses
    ADD CONSTRAINT planned_expenses_expense_category_id_foreign FOREIGN KEY (expense_category_id) REFERENCES public.expense_categories(id) ON DELETE CASCADE;


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
-- Name: reservations reservations_transaction_complete_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.reservations
    ADD CONSTRAINT reservations_transaction_complete_id_fkey FOREIGN KEY (transaction_complete_id) REFERENCES public.account_transactions(id);


--
-- Name: sale_item_batches sale_item_batches_batch_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.sale_item_batches
    ADD CONSTRAINT sale_item_batches_batch_id_foreign FOREIGN KEY (batch_id) REFERENCES public.stock_batches(id) ON DELETE RESTRICT;


--
-- Name: sale_item_batches sale_item_batches_location_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.sale_item_batches
    ADD CONSTRAINT sale_item_batches_location_id_fkey FOREIGN KEY (location_id) REFERENCES public.locations(id);


--
-- Name: sale_item_batches sale_item_batches_sale_item_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.sale_item_batches
    ADD CONSTRAINT sale_item_batches_sale_item_id_foreign FOREIGN KEY (sale_item_id) REFERENCES public.sale_items(id) ON DELETE CASCADE;


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
-- Name: stock_batches stock_batches_stock_receipt_item_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.stock_batches
    ADD CONSTRAINT stock_batches_stock_receipt_item_id_foreign FOREIGN KEY (stock_receipt_item_id) REFERENCES public.stock_receipt_items(id) ON DELETE SET NULL;


--
-- Name: stock_batches stock_batches_variant_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.stock_batches
    ADD CONSTRAINT stock_batches_variant_id_foreign FOREIGN KEY (variant_id) REFERENCES public.product_variants(id) ON DELETE CASCADE;


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
-- Name: stock_receipts stock_receipts_cost_validated_by_fkey; Type: FK CONSTRAINT; Schema: public; Owner: express_sale_user
--

ALTER TABLE ONLY public.stock_receipts
    ADD CONSTRAINT stock_receipts_cost_validated_by_fkey FOREIGN KEY (cost_validated_by) REFERENCES public.users(id);


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

\unrestrict WuFuGhO1nl6skH3haCRrLWWqr5QjaZUEXqGtegDAO9GlSwJ1uaFmkbTBsOnpDcD

