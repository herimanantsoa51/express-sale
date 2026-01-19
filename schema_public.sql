--
-- PostgreSQL database dump
--

\restrict UcIVCRaFTvHHIisBykNyp3eCBCJUTHoAbvNJMe3aU7htJOEYYcdeUWXFzJJfvLO

-- Dumped from database version 18.1 (Ubuntu 18.1-1.pgdg24.04+2)
-- Dumped by pg_dump version 18.1 (Ubuntu 18.1-1.pgdg24.04+2)

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET transaction_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

--
-- Name: public; Type: SCHEMA; Schema: -; Owner: -
--

CREATE SCHEMA public;


--
-- Name: calculate_freight_forwarder_score(integer); Type: FUNCTION; Schema: public; Owner: -
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


--
-- Name: FUNCTION calculate_freight_forwarder_score(ff_id_param integer); Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON FUNCTION public.calculate_freight_forwarder_score(ff_id_param integer) IS 'Calcule le score transitaire pondéré par valeur monétaire';


--
-- Name: calculate_supplier_score(integer); Type: FUNCTION; Schema: public; Owner: -
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


--
-- Name: FUNCTION calculate_supplier_score(supplier_id_param integer); Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON FUNCTION public.calculate_supplier_score(supplier_id_param integer) IS 'Calcule le score fournisseur pondéré par valeur monétaire';


--
-- Name: complete_reservation_payment(integer, integer, integer, numeric, character varying, character varying, integer); Type: FUNCTION; Schema: public; Owner: -
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


--
-- Name: FUNCTION complete_reservation_payment(p_reservation_id integer, p_sale_id integer, p_account_id integer, p_remaining_amount numeric, p_payment_method character varying, p_reference_number character varying, p_created_by integer); Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON FUNCTION public.complete_reservation_payment(p_reservation_id integer, p_sale_id integer, p_account_id integer, p_remaining_amount numeric, p_payment_method character varying, p_reference_number character varying, p_created_by integer) IS 'Finalise le paiement d''une réservation';


--
-- Name: create_account_with_initial_balance(integer, character varying, character varying, numeric, text, integer); Type: FUNCTION; Schema: public; Owner: -
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


--
-- Name: FUNCTION create_account_with_initial_balance(p_account_type_id integer, p_name character varying, p_account_number character varying, p_initial_balance numeric, p_notes text, p_created_by integer); Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON FUNCTION public.create_account_with_initial_balance(p_account_type_id integer, p_name character varying, p_account_number character varying, p_initial_balance numeric, p_notes text, p_created_by integer) IS 'Crée un compte avec son solde initial et enregistre la transaction';


--
-- Name: recalculate_variant_stock(); Type: FUNCTION; Schema: public; Owner: -
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


--
-- Name: record_account_transfer(integer, integer, numeric, text, integer); Type: FUNCTION; Schema: public; Owner: -
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


--
-- Name: record_credit_payment(integer, integer, numeric, character varying, character varying, text, integer); Type: FUNCTION; Schema: public; Owner: -
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


--
-- Name: FUNCTION record_credit_payment(p_credit_installment_id integer, p_account_id integer, p_amount_paid numeric, p_payment_method character varying, p_reference_number character varying, p_notes text, p_created_by integer); Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON FUNCTION public.record_credit_payment(p_credit_installment_id integer, p_account_id integer, p_amount_paid numeric, p_payment_method character varying, p_reference_number character varying, p_notes text, p_created_by integer) IS 'Enregistre un paiement de crédit et met à jour le compte bancaire';


--
-- Name: record_credit_sale_initial_payment(integer, integer, integer, numeric, character varying, character varying, integer); Type: FUNCTION; Schema: public; Owner: -
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


--
-- Name: FUNCTION record_credit_sale_initial_payment(p_sale_id integer, p_credit_id integer, p_account_id integer, p_amount_paid numeric, p_payment_method character varying, p_reference_number character varying, p_created_by integer); Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON FUNCTION public.record_credit_sale_initial_payment(p_sale_id integer, p_credit_id integer, p_account_id integer, p_amount_paid numeric, p_payment_method character varying, p_reference_number character varying, p_created_by integer) IS 'Enregistre l''acompte initial d''une vente à crédit';


--
-- Name: record_direct_sale_payment(integer, integer, character varying, numeric, numeric, numeric, character varying, numeric, numeric, character varying, integer); Type: FUNCTION; Schema: public; Owner: -
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


--
-- Name: FUNCTION record_direct_sale_payment(p_sale_id integer, p_account_id integer, p_payment_method character varying, p_amount numeric, p_cash_amount numeric, p_mobile_money_amount numeric, p_mobile_money_number character varying, p_mobile_money_fees numeric, p_bank_transfer_amount numeric, p_reference_number character varying, p_created_by integer); Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON FUNCTION public.record_direct_sale_payment(p_sale_id integer, p_account_id integer, p_payment_method character varying, p_amount numeric, p_cash_amount numeric, p_mobile_money_amount numeric, p_mobile_money_number character varying, p_mobile_money_fees numeric, p_bank_transfer_amount numeric, p_reference_number character varying, p_created_by integer) IS 'Enregistre le paiement d''une vente directe et met à jour le compte';


--
-- Name: record_freight_payment(integer, integer, integer, numeric, character varying, text, integer); Type: FUNCTION; Schema: public; Owner: -
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


--
-- Name: record_operating_expense(integer, integer, numeric, character varying, text, text, integer); Type: FUNCTION; Schema: public; Owner: -
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


--
-- Name: record_reservation_deposit(integer, integer, integer, numeric, character varying, character varying, integer); Type: FUNCTION; Schema: public; Owner: -
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


--
-- Name: FUNCTION record_reservation_deposit(p_reservation_id integer, p_sale_id integer, p_account_id integer, p_deposit_amount numeric, p_payment_method character varying, p_reference_number character varying, p_created_by integer); Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON FUNCTION public.record_reservation_deposit(p_reservation_id integer, p_sale_id integer, p_account_id integer, p_deposit_amount numeric, p_payment_method character varying, p_reference_number character varying, p_created_by integer) IS 'Enregistre l''acompte d''une réservation';


--
-- Name: record_sale_transaction(integer, integer, numeric, integer); Type: FUNCTION; Schema: public; Owner: -
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


--
-- Name: record_supplier_payment(integer, integer, integer, numeric, character varying, text, integer); Type: FUNCTION; Schema: public; Owner: -
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


--
-- Name: release_credit_stock(); Type: FUNCTION; Schema: public; Owner: -
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


--
-- Name: update_account_balance(); Type: FUNCTION; Schema: public; Owner: -
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


--
-- Name: update_supplier_quality_rating(); Type: FUNCTION; Schema: public; Owner: -
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


--
-- Name: update_supplier_stats_on_receipt(); Type: FUNCTION; Schema: public; Owner: -
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


--
-- Name: update_updated_at_column(); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.update_updated_at_column() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$;


SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: account_transactions; Type: TABLE; Schema: public; Owner: -
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


--
-- Name: TABLE account_transactions; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE public.account_transactions IS 'Historique complet de toutes les transactions (entrées, sorties, transferts)';


--
-- Name: COLUMN account_transactions.balance_before; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.account_transactions.balance_before IS 'Solde du compte AVANT la transaction';


--
-- Name: COLUMN account_transactions.balance_after; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.account_transactions.balance_after IS 'Solde du compte APRÈS la transaction';


--
-- Name: COLUMN account_transactions.related_account_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.account_transactions.related_account_id IS 'Compte de destination pour les transferts';


--
-- Name: COLUMN account_transactions.related_transaction_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.account_transactions.related_transaction_id IS 'Transaction liée (pour les transferts bidirectionnels)';


--
-- Name: COLUMN account_transactions.recipient_name; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.account_transactions.recipient_name IS 'Nom du bénéficiaire pour les dépenses opérationnelles';


--
-- Name: account_transactions_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.account_transactions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: account_transactions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.account_transactions_id_seq OWNED BY public.account_transactions.id;


--
-- Name: account_types; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.account_types (
    id integer NOT NULL,
    code character varying(50) NOT NULL,
    name character varying(100) NOT NULL,
    display_name character varying(255) NOT NULL,
    description text,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


--
-- Name: TABLE account_types; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE public.account_types IS 'Types de comptes monétaires disponibles';


--
-- Name: account_types_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.account_types_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: account_types_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.account_types_id_seq OWNED BY public.account_types.id;


--
-- Name: accounts; Type: TABLE; Schema: public; Owner: -
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


--
-- Name: TABLE accounts; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE public.accounts IS 'Comptes monétaires (Cash, Mobile Money, Banque)';


--
-- Name: COLUMN accounts.initial_balance; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.accounts.initial_balance IS 'Solde initial lors de la création du compte';


--
-- Name: COLUMN accounts.current_balance; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.accounts.current_balance IS 'Solde actuel du compte';


--
-- Name: accounts_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.accounts_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: accounts_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.accounts_id_seq OWNED BY public.accounts.id;


--
-- Name: attribute_types; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.attribute_types (
    id integer NOT NULL,
    name character varying(100) NOT NULL,
    display_name character varying(255) NOT NULL,
    input_type character varying(50) DEFAULT 'text'::character varying,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT attribute_types_input_type_check CHECK (((input_type)::text = ANY ((ARRAY['text'::character varying, 'number'::character varying, 'select'::character varying, 'color'::character varying])::text[])))
);


--
-- Name: attribute_types_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.attribute_types_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: attribute_types_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.attribute_types_id_seq OWNED BY public.attribute_types.id;


--
-- Name: attribute_values; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.attribute_values (
    id integer NOT NULL,
    attribute_type_id integer NOT NULL,
    value character varying(100) NOT NULL,
    sort_order integer DEFAULT 0,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


--
-- Name: attribute_values_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.attribute_values_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: attribute_values_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.attribute_values_id_seq OWNED BY public.attribute_values.id;


--
-- Name: audit_logs; Type: TABLE; Schema: public; Owner: -
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


--
-- Name: audit_logs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.audit_logs_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: audit_logs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.audit_logs_id_seq OWNED BY public.audit_logs.id;


--
-- Name: cache; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cache (
    key character varying(255) NOT NULL,
    value text NOT NULL,
    expiration integer NOT NULL
);


--
-- Name: cache_locks; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cache_locks (
    key character varying(255) NOT NULL,
    owner character varying(255) NOT NULL,
    expiration integer NOT NULL
);


--
-- Name: cash_count_denominations; Type: TABLE; Schema: public; Owner: -
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


--
-- Name: cash_count_denominations_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.cash_count_denominations_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: cash_count_denominations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.cash_count_denominations_id_seq OWNED BY public.cash_count_denominations.id;


--
-- Name: cash_counts; Type: TABLE; Schema: public; Owner: -
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


--
-- Name: cash_counts_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.cash_counts_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: cash_counts_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.cash_counts_id_seq OWNED BY public.cash_counts.id;


--
-- Name: categories; Type: TABLE; Schema: public; Owner: -
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


--
-- Name: categories_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.categories_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: categories_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.categories_id_seq OWNED BY public.categories.id;


--
-- Name: coordinates; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.coordinates (
    id bigint NOT NULL,
    country character varying(100) NOT NULL,
    city character varying(100) NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: coordinates_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.coordinates_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: coordinates_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.coordinates_id_seq OWNED BY public.coordinates.id;


--
-- Name: credit_installments; Type: TABLE; Schema: public; Owner: -
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
    CONSTRAINT credit_installments_status_check CHECK (((status)::text = ANY ((ARRAY['pending'::character varying, 'partial'::character varying, 'paid'::character varying, 'overdue'::character varying])::text[])))
);


--
-- Name: credit_installments_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.credit_installments_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: credit_installments_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.credit_installments_id_seq OWNED BY public.credit_installments.id;


--
-- Name: credits; Type: TABLE; Schema: public; Owner: -
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
    CONSTRAINT credits_status_check CHECK (((status)::text = ANY ((ARRAY['active'::character varying, 'partial_paid'::character varying, 'completed'::character varying, 'overdue'::character varying, 'defaulted'::character varying, 'recovered'::character varying])::text[])))
);


--
-- Name: credits_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.credits_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: credits_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.credits_id_seq OWNED BY public.credits.id;


--
-- Name: currency_rates; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.currency_rates (
    id integer NOT NULL,
    euro_rate numeric(10,4) NOT NULL,
    yuan_rate numeric(10,4) CONSTRAINT currency_rates_yen_rate_not_null NOT NULL,
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


--
-- Name: TABLE currency_rates; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE public.currency_rates IS 'Taux de change: valeur de 100 Ariary vers chaque devise (Euro, Yen, Dollar, Dirham)';


--
-- Name: currency_rates_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.currency_rates_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: currency_rates_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.currency_rates_id_seq OWNED BY public.currency_rates.id;


--
-- Name: customers; Type: TABLE; Schema: public; Owner: -
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


--
-- Name: customers_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.customers_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: customers_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.customers_id_seq OWNED BY public.customers.id;


--
-- Name: expense_categories; Type: TABLE; Schema: public; Owner: -
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


--
-- Name: TABLE expense_categories; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE public.expense_categories IS 'Catégories de dépenses opérationnelles';


--
-- Name: expense_categories_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.expense_categories_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: expense_categories_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.expense_categories_id_seq OWNED BY public.expense_categories.id;


--
-- Name: failed_jobs; Type: TABLE; Schema: public; Owner: -
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


--
-- Name: failed_jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.failed_jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: failed_jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.failed_jobs_id_seq OWNED BY public.failed_jobs.id;


--
-- Name: freight_forwarders; Type: TABLE; Schema: public; Owner: -
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
    CONSTRAINT freight_forwarders_type_check CHECK (((type)::text = ANY ((ARRAY['aerien'::character varying, 'maritime'::character varying])::text[])))
);


--
-- Name: TABLE freight_forwarders; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE public.freight_forwarders IS 'Transitaires pour transport depuis Chine';


--
-- Name: COLUMN freight_forwarders.service_score; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.freight_forwarders.service_score IS 'Score global sur 10 (pondéré par valeur)';


--
-- Name: COLUMN freight_forwarders.total_shipments; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.freight_forwarders.total_shipments IS 'Nombre total de transports effectués';


--
-- Name: COLUMN freight_forwarders.total_items_shipped; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.freight_forwarders.total_items_shipped IS 'Quantité totale expédiée';


--
-- Name: COLUMN freight_forwarders.total_items_delivered; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.freight_forwarders.total_items_delivered IS 'Quantité totale livrée';


--
-- Name: COLUMN freight_forwarders.service_rating_sum; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.freight_forwarders.service_rating_sum IS 'Somme simple des notes de service';


--
-- Name: COLUMN freight_forwarders.service_rating_count; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.freight_forwarders.service_rating_count IS 'Nombre de notations service reçues';


--
-- Name: COLUMN freight_forwarders.total_value_shipped; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.freight_forwarders.total_value_shipped IS 'Valeur totale expédiée en Ariary';


--
-- Name: COLUMN freight_forwarders.total_value_delivered; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.freight_forwarders.total_value_delivered IS 'Valeur totale livrée en Ariary';


--
-- Name: COLUMN freight_forwarders.weighted_service_sum; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.freight_forwarders.weighted_service_sum IS 'Somme pondérée: note × valeur expédition';


--
-- Name: COLUMN freight_forwarders.total_weighted_shipment_value; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.freight_forwarders.total_weighted_shipment_value IS 'Somme totale des valeurs utilisées pour pondération';


--
-- Name: freight_forwarders_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.freight_forwarders_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: freight_forwarders_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.freight_forwarders_id_seq OWNED BY public.freight_forwarders.id;


--
-- Name: installment_transactions; Type: TABLE; Schema: public; Owner: -
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


--
-- Name: installment_transactions_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.installment_transactions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: installment_transactions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.installment_transactions_id_seq OWNED BY public.installment_transactions.id;


--
-- Name: job_batches; Type: TABLE; Schema: public; Owner: -
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


--
-- Name: jobs; Type: TABLE; Schema: public; Owner: -
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


--
-- Name: jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.jobs_id_seq OWNED BY public.jobs.id;


--
-- Name: locations; Type: TABLE; Schema: public; Owner: -
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


--
-- Name: locations_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.locations_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: locations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.locations_id_seq OWNED BY public.locations.id;


--
-- Name: migrations; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.migrations (
    id integer NOT NULL,
    migration character varying(255) NOT NULL,
    batch integer NOT NULL
);


--
-- Name: migrations_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.migrations_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: migrations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.migrations_id_seq OWNED BY public.migrations.id;


--
-- Name: notifications; Type: TABLE; Schema: public; Owner: -
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


--
-- Name: notifications_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.notifications_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: notifications_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.notifications_id_seq OWNED BY public.notifications.id;


--
-- Name: password_reset_tokens; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.password_reset_tokens (
    username character varying(255) NOT NULL,
    token character varying(255) NOT NULL,
    created_at timestamp(0) without time zone
);


--
-- Name: personal_access_tokens; Type: TABLE; Schema: public; Owner: -
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


--
-- Name: personal_access_tokens_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.personal_access_tokens_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: personal_access_tokens_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.personal_access_tokens_id_seq OWNED BY public.personal_access_tokens.id;


--
-- Name: product_attributes; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.product_attributes (
    id integer NOT NULL,
    product_id integer NOT NULL,
    attribute_type_id integer NOT NULL,
    is_required boolean DEFAULT true,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


--
-- Name: product_attributes_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.product_attributes_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: product_attributes_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.product_attributes_id_seq OWNED BY public.product_attributes.id;


--
-- Name: product_variant_locations; Type: TABLE; Schema: public; Owner: -
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


--
-- Name: product_variant_locations_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.product_variant_locations_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: product_variant_locations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.product_variant_locations_id_seq OWNED BY public.product_variant_locations.id;


--
-- Name: product_variants; Type: TABLE; Schema: public; Owner: -
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


--
-- Name: product_variants_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.product_variants_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: product_variants_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.product_variants_id_seq OWNED BY public.product_variants.id;


--
-- Name: products; Type: TABLE; Schema: public; Owner: -
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


--
-- Name: products_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.products_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: products_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.products_id_seq OWNED BY public.products.id;


--
-- Name: reservation_deposits; Type: TABLE; Schema: public; Owner: -
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


--
-- Name: reservation_deposits_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.reservation_deposits_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: reservation_deposits_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.reservation_deposits_id_seq OWNED BY public.reservation_deposits.id;


--
-- Name: reservations; Type: TABLE; Schema: public; Owner: -
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
    CONSTRAINT reservations_status_check CHECK (((status)::text = ANY ((ARRAY['pending'::character varying, 'confirmed'::character varying, 'partial_paid'::character varying, 'completed'::character varying, 'expired'::character varying, 'cancelled'::character varying])::text[])))
);


--
-- Name: reservations_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.reservations_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: reservations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.reservations_id_seq OWNED BY public.reservations.id;


--
-- Name: sale_item_batches; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.sale_item_batches (
    id bigint NOT NULL,
    sale_item_id bigint NOT NULL,
    batch_id bigint NOT NULL,
    quantity integer NOT NULL,
    unit_price_at_sale numeric(12,2) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT chk_sib_price_positive CHECK ((unit_price_at_sale >= (0)::numeric)),
    CONSTRAINT chk_sib_quantity_positive CHECK ((quantity > 0))
);


--
-- Name: TABLE sale_item_batches; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE public.sale_item_batches IS 'Traçabilité FIFO : quel batch a été vendu dans quelle vente (sans snapshot de coût)';


--
-- Name: COLUMN sale_item_batches.quantity; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.sale_item_batches.quantity IS 'Quantité vendue provenant de ce lot';


--
-- Name: COLUMN sale_item_batches.unit_price_at_sale; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.sale_item_batches.unit_price_at_sale IS 'Snapshot du prix de vente (le coût est lu depuis batch.total_unit_cost)';


--
-- Name: sale_item_batches_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.sale_item_batches_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: sale_item_batches_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.sale_item_batches_id_seq OWNED BY public.sale_item_batches.id;


--
-- Name: sale_items; Type: TABLE; Schema: public; Owner: -
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


--
-- Name: sale_items_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.sale_items_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: sale_items_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.sale_items_id_seq OWNED BY public.sale_items.id;


--
-- Name: sales; Type: TABLE; Schema: public; Owner: -
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
    CONSTRAINT sale_type_check CHECK (((sale_type)::text = ANY ((ARRAY['immediate'::character varying, 'credit'::character varying, 'reservation'::character varying])::text[]))),
    CONSTRAINT sales_discount_amount_check CHECK ((discount_amount >= (0)::numeric)),
    CONSTRAINT sales_payment_method_check CHECK (((payment_method)::text = ANY ((ARRAY['cash'::character varying, 'mobile_money'::character varying, 'bank_transfer'::character varying, 'mixed'::character varying])::text[]))),
    CONSTRAINT sales_payment_status_check CHECK (((payment_status)::text = ANY ((ARRAY['pending'::character varying, 'partial'::character varying, 'paid'::character varying, 'cancelled'::character varying])::text[]))),
    CONSTRAINT sales_sale_type_check CHECK (((sale_type)::text = ANY ((ARRAY['immediate'::character varying, 'reservation'::character varying, 'credit'::character varying])::text[]))),
    CONSTRAINT sales_subtotal_check CHECK ((subtotal >= (0)::numeric)),
    CONSTRAINT sales_total_amount_check CHECK ((total_amount >= (0)::numeric))
);


--
-- Name: COLUMN sales.payment_method; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.sales.payment_method IS 'Méthode de paiement: cash, mobile_money, bank_transfer, mixed';


--
-- Name: sales_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.sales_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: sales_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.sales_id_seq OWNED BY public.sales.id;


--
-- Name: sessions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.sessions (
    id character varying(255) NOT NULL,
    user_id bigint,
    ip_address character varying(45),
    user_agent text,
    payload text NOT NULL,
    last_activity integer NOT NULL
);


--
-- Name: stock_batches; Type: TABLE; Schema: public; Owner: -
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
    CONSTRAINT chk_batch_costs_non_negative CHECK (((freight_cost_per_unit >= (0)::numeric) AND (other_costs_per_unit >= (0)::numeric))),
    CONSTRAINT chk_batch_remaining_lte_initial CHECK ((remaining_quantity <= initial_quantity)),
    CONSTRAINT chk_batch_remaining_positive CHECK ((remaining_quantity >= 0)),
    CONSTRAINT chk_batch_supplier_cost_positive CHECK ((supplier_unit_cost > (0)::numeric)),
    CONSTRAINT stock_batches_cost_status_check CHECK (((cost_status)::text = ANY ((ARRAY['pending'::character varying, 'estimated'::character varying, 'validated'::character varying])::text[])))
);


--
-- Name: TABLE stock_batches; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE public.stock_batches IS 'Lots de stock pour tracking FIFO des coûts réels';


--
-- Name: COLUMN stock_batches.initial_quantity; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.stock_batches.initial_quantity IS 'Quantité initiale du lot';


--
-- Name: COLUMN stock_batches.remaining_quantity; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.stock_batches.remaining_quantity IS 'Quantité encore disponible (diminue à chaque vente FIFO)';


--
-- Name: COLUMN stock_batches.supplier_unit_cost; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.stock_batches.supplier_unit_cost IS 'Coût unitaire payé au fournisseur (depuis stock_receipt_items)';


--
-- Name: COLUMN stock_batches.freight_cost_per_unit; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.stock_batches.freight_cost_per_unit IS 'Frais de transport par unité (répartis)';


--
-- Name: COLUMN stock_batches.other_costs_per_unit; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.stock_batches.other_costs_per_unit IS 'Autres frais par unité (manutention, stockage, etc.)';


--
-- Name: COLUMN stock_batches.total_unit_cost; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.stock_batches.total_unit_cost IS 'Coût complet par unité utilisé pour calcul des bénéfices';


--
-- Name: COLUMN stock_batches.cost_status; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.stock_batches.cost_status IS 'pending = pas réparti, estimated = recommandation calculée, validated = validé manuellement';


--
-- Name: COLUMN stock_batches.cost_validated_at; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.stock_batches.cost_validated_at IS 'Date de validation des coûts';


--
-- Name: stock_batches_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.stock_batches_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: stock_batches_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.stock_batches_id_seq OWNED BY public.stock_batches.id;


--
-- Name: stock_movements; Type: TABLE; Schema: public; Owner: -
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


--
-- Name: TABLE stock_movements; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE public.stock_movements IS 'Historique de tous les mouvements de stock';


--
-- Name: COLUMN stock_movements.from_location_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.stock_movements.from_location_id IS 'Location source (NULL pour réception/ajustement positif)';


--
-- Name: COLUMN stock_movements.to_location_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.stock_movements.to_location_id IS 'Location destination (NULL pour vente/ajustement négatif)';


--
-- Name: COLUMN stock_movements.movement_type; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.stock_movements.movement_type IS 'Types: transfer (transfert entre locations), receipt (réception stock), sale (vente), adjustment (ajustement), return (retour)';


--
-- Name: COLUMN stock_movements.sale_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.stock_movements.sale_id IS 'Référence à la vente si le mouvement est lié à une vente';


--
-- Name: COLUMN stock_movements.stock_receipt_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.stock_movements.stock_receipt_id IS 'Référence au réapprovisionnement si le mouvement est lié à une réception';


--
-- Name: stock_movements_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.stock_movements_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: stock_movements_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.stock_movements_id_seq OWNED BY public.stock_movements.id;


--
-- Name: stock_receipt_item_ratings; Type: TABLE; Schema: public; Owner: -
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


--
-- Name: TABLE stock_receipt_item_ratings; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE public.stock_receipt_item_ratings IS 'Évaluation de la qualité des articles reçus par commande';


--
-- Name: COLUMN stock_receipt_item_ratings.attribute_conformity_rating; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.stock_receipt_item_ratings.attribute_conformity_rating IS 'Note de conformité pour un attribut spécifique (couleur, taille, etc.) sur 10';


--
-- Name: COLUMN stock_receipt_item_ratings.quality_rating; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.stock_receipt_item_ratings.quality_rating IS 'Note de qualité générale du produit sur 10';


--
-- Name: stock_receipt_item_ratings_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.stock_receipt_item_ratings_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: stock_receipt_item_ratings_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.stock_receipt_item_ratings_id_seq OWNED BY public.stock_receipt_item_ratings.id;


--
-- Name: stock_receipt_items; Type: TABLE; Schema: public; Owner: -
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
    CONSTRAINT stock_receipt_items_quantity_ordered_check CHECK ((quantity_ordered >= 0)),
    CONSTRAINT stock_receipt_items_quantity_received_check CHECK ((quantity_received >= 0))
);


--
-- Name: COLUMN stock_receipt_items.unit_cost_ariary; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.stock_receipt_items.unit_cost_ariary IS 'Coût unitaire en Ariary (conversion faite côté frontend)';


--
-- Name: stock_receipt_items_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.stock_receipt_items_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: stock_receipt_items_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.stock_receipt_items_id_seq OWNED BY public.stock_receipt_items.id;


--
-- Name: stock_receipts; Type: TABLE; Schema: public; Owner: -
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


--
-- Name: TABLE stock_receipts; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE public.stock_receipts IS 'Réception de stock - Tous les montants en Ariary uniquement';


--
-- Name: COLUMN stock_receipts.status; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.stock_receipts.status IS 'pending, sent, in_transit, arrived, validated, cancelled';


--
-- Name: stock_receipts_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.stock_receipts_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: stock_receipts_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.stock_receipts_id_seq OWNED BY public.stock_receipts.id;


--
-- Name: suppliers; Type: TABLE; Schema: public; Owner: -
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


--
-- Name: TABLE suppliers; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE public.suppliers IS 'Fournisseurs Chine';


--
-- Name: COLUMN suppliers.reliability_score; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.suppliers.reliability_score IS 'Score global sur 10 (pondéré par valeur)';


--
-- Name: COLUMN suppliers.total_orders; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.suppliers.total_orders IS 'Nombre total de commandes passées';


--
-- Name: COLUMN suppliers.total_items_ordered; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.suppliers.total_items_ordered IS 'Quantité totale commandée (tous produits)';


--
-- Name: COLUMN suppliers.total_items_received; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.suppliers.total_items_received IS 'Quantité totale reçue (tous produits)';


--
-- Name: COLUMN suppliers.quality_rating_sum; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.suppliers.quality_rating_sum IS 'Somme simple des notes de qualité';


--
-- Name: COLUMN suppliers.quality_rating_count; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.suppliers.quality_rating_count IS 'Nombre de notations qualité reçues';


--
-- Name: COLUMN suppliers.total_value_ordered; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.suppliers.total_value_ordered IS 'Valeur totale commandée en Ariary';


--
-- Name: COLUMN suppliers.total_value_received; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.suppliers.total_value_received IS 'Valeur totale reçue en Ariary (quantité reçue × prix unitaire)';


--
-- Name: COLUMN suppliers.weighted_quality_sum; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.suppliers.weighted_quality_sum IS 'Somme pondérée: note × valeur article';


--
-- Name: COLUMN suppliers.total_weighted_value; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.suppliers.total_weighted_value IS 'Somme totale des valeurs utilisées pour pondération';


--
-- Name: suppliers_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.suppliers_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: suppliers_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.suppliers_id_seq OWNED BY public.suppliers.id;


--
-- Name: system_settings; Type: TABLE; Schema: public; Owner: -
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
    CONSTRAINT system_settings_value_type_check CHECK (((value_type)::text = ANY ((ARRAY['string'::character varying, 'number'::character varying, 'boolean'::character varying, 'json'::character varying])::text[])))
);


--
-- Name: system_settings_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.system_settings_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: system_settings_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.system_settings_id_seq OWNED BY public.system_settings.id;


--
-- Name: transaction_types; Type: TABLE; Schema: public; Owner: -
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
    CONSTRAINT transaction_types_category_check CHECK (((category)::text = ANY ((ARRAY['income'::character varying, 'expense'::character varying, 'transfer'::character varying, 'adjustment'::character varying, 'opening_balance'::character varying, 'refund'::character varying])::text[])))
);


--
-- Name: TABLE transaction_types; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE public.transaction_types IS 'Types de transactions possibles (revenus, dépenses, transferts)';


--
-- Name: transaction_types_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.transaction_types_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: transaction_types_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.transaction_types_id_seq OWNED BY public.transaction_types.id;


--
-- Name: user_sessions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.user_sessions (
    id integer NOT NULL,
    user_id integer NOT NULL,
    login_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    logout_at timestamp without time zone,
    ip_address character varying(45),
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


--
-- Name: user_sessions_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.user_sessions_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: user_sessions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.user_sessions_id_seq OWNED BY public.user_sessions.id;


--
-- Name: users; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.users (
    id integer NOT NULL,
    name character varying(255) NOT NULL,
    username character varying(255) CONSTRAINT users_usename_not_null NOT NULL,
    password character varying(255) NOT NULL,
    role character varying(50) DEFAULT 'vendeur'::character varying,
    is_active boolean DEFAULT true,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT users_role_check CHECK (((role)::text = ANY ((ARRAY['admin'::character varying, 'vendeur'::character varying])::text[])))
);


--
-- Name: TABLE users; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE public.users IS 'Utilisateurs internes (admin + vendeurs)';


--
-- Name: users_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.users_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: users_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.users_id_seq OWNED BY public.users.id;


--
-- Name: v_accounts_summary; Type: VIEW; Schema: public; Owner: -
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


--
-- Name: VIEW v_accounts_summary; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON VIEW public.v_accounts_summary IS 'Résumé de tous les comptes avec statistiques';


--
-- Name: v_credits_at_risk; Type: VIEW; Schema: public; Owner: -
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
  WHERE (((cr.status)::text = ANY ((ARRAY['active'::character varying, 'partial_paid'::character varying, 'overdue'::character varying])::text[])) AND ((cr.due_date <= (CURRENT_DATE + '7 days'::interval)) OR ((cr.status)::text = 'overdue'::text)))
  ORDER BY cr.due_date;


--
-- Name: v_recent_transactions; Type: VIEW; Schema: public; Owner: -
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


--
-- Name: VIEW v_recent_transactions; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON VIEW public.v_recent_transactions IS 'Dernières transactions avec tous les détails';


--
-- Name: v_reservations_pending; Type: VIEW; Schema: public; Owner: -
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
  WHERE (((r.status)::text = ANY ((ARRAY['pending'::character varying, 'confirmed'::character varying, 'partial_paid'::character varying])::text[])) AND (r.expiry_date > CURRENT_TIMESTAMP))
  ORDER BY r.expiry_date;


--
-- Name: v_treasury_dashboard; Type: VIEW; Schema: public; Owner: -
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


--
-- Name: VIEW v_treasury_dashboard; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON VIEW public.v_treasury_dashboard IS 'Tableau de bord de la trésorerie globale';


--
-- Name: variant_attribute_values; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.variant_attribute_values (
    id integer NOT NULL,
    variant_id integer NOT NULL,
    attribute_value_id integer NOT NULL,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


--
-- Name: variant_attribute_values_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.variant_attribute_values_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: variant_attribute_values_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.variant_attribute_values_id_seq OWNED BY public.variant_attribute_values.id;


--
-- Name: account_transactions id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.account_transactions ALTER COLUMN id SET DEFAULT nextval('public.account_transactions_id_seq'::regclass);


--
-- Name: account_types id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.account_types ALTER COLUMN id SET DEFAULT nextval('public.account_types_id_seq'::regclass);


--
-- Name: accounts id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.accounts ALTER COLUMN id SET DEFAULT nextval('public.accounts_id_seq'::regclass);


--
-- Name: attribute_types id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.attribute_types ALTER COLUMN id SET DEFAULT nextval('public.attribute_types_id_seq'::regclass);


--
-- Name: attribute_values id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.attribute_values ALTER COLUMN id SET DEFAULT nextval('public.attribute_values_id_seq'::regclass);


--
-- Name: audit_logs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.audit_logs ALTER COLUMN id SET DEFAULT nextval('public.audit_logs_id_seq'::regclass);


--
-- Name: cash_count_denominations id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cash_count_denominations ALTER COLUMN id SET DEFAULT nextval('public.cash_count_denominations_id_seq'::regclass);


--
-- Name: cash_counts id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cash_counts ALTER COLUMN id SET DEFAULT nextval('public.cash_counts_id_seq'::regclass);


--
-- Name: categories id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.categories ALTER COLUMN id SET DEFAULT nextval('public.categories_id_seq'::regclass);


--
-- Name: coordinates id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.coordinates ALTER COLUMN id SET DEFAULT nextval('public.coordinates_id_seq'::regclass);


--
-- Name: credit_installments id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.credit_installments ALTER COLUMN id SET DEFAULT nextval('public.credit_installments_id_seq'::regclass);


--
-- Name: credits id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.credits ALTER COLUMN id SET DEFAULT nextval('public.credits_id_seq'::regclass);


--
-- Name: currency_rates id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.currency_rates ALTER COLUMN id SET DEFAULT nextval('public.currency_rates_id_seq'::regclass);


--
-- Name: customers id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.customers ALTER COLUMN id SET DEFAULT nextval('public.customers_id_seq'::regclass);


--
-- Name: expense_categories id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.expense_categories ALTER COLUMN id SET DEFAULT nextval('public.expense_categories_id_seq'::regclass);


--
-- Name: failed_jobs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.failed_jobs ALTER COLUMN id SET DEFAULT nextval('public.failed_jobs_id_seq'::regclass);


--
-- Name: freight_forwarders id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.freight_forwarders ALTER COLUMN id SET DEFAULT nextval('public.freight_forwarders_id_seq'::regclass);


--
-- Name: installment_transactions id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.installment_transactions ALTER COLUMN id SET DEFAULT nextval('public.installment_transactions_id_seq'::regclass);


--
-- Name: jobs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.jobs ALTER COLUMN id SET DEFAULT nextval('public.jobs_id_seq'::regclass);


--
-- Name: locations id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.locations ALTER COLUMN id SET DEFAULT nextval('public.locations_id_seq'::regclass);


--
-- Name: migrations id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.migrations ALTER COLUMN id SET DEFAULT nextval('public.migrations_id_seq'::regclass);


--
-- Name: notifications id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.notifications ALTER COLUMN id SET DEFAULT nextval('public.notifications_id_seq'::regclass);


--
-- Name: personal_access_tokens id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.personal_access_tokens ALTER COLUMN id SET DEFAULT nextval('public.personal_access_tokens_id_seq'::regclass);


--
-- Name: product_attributes id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.product_attributes ALTER COLUMN id SET DEFAULT nextval('public.product_attributes_id_seq'::regclass);


--
-- Name: product_variant_locations id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.product_variant_locations ALTER COLUMN id SET DEFAULT nextval('public.product_variant_locations_id_seq'::regclass);


--
-- Name: product_variants id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.product_variants ALTER COLUMN id SET DEFAULT nextval('public.product_variants_id_seq'::regclass);


--
-- Name: products id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.products ALTER COLUMN id SET DEFAULT nextval('public.products_id_seq'::regclass);


--
-- Name: reservation_deposits id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reservation_deposits ALTER COLUMN id SET DEFAULT nextval('public.reservation_deposits_id_seq'::regclass);


--
-- Name: reservations id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reservations ALTER COLUMN id SET DEFAULT nextval('public.reservations_id_seq'::regclass);


--
-- Name: sale_item_batches id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sale_item_batches ALTER COLUMN id SET DEFAULT nextval('public.sale_item_batches_id_seq'::regclass);


--
-- Name: sale_items id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sale_items ALTER COLUMN id SET DEFAULT nextval('public.sale_items_id_seq'::regclass);


--
-- Name: sales id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sales ALTER COLUMN id SET DEFAULT nextval('public.sales_id_seq'::regclass);


--
-- Name: stock_batches id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.stock_batches ALTER COLUMN id SET DEFAULT nextval('public.stock_batches_id_seq'::regclass);


--
-- Name: stock_movements id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.stock_movements ALTER COLUMN id SET DEFAULT nextval('public.stock_movements_id_seq'::regclass);


--
-- Name: stock_receipt_item_ratings id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.stock_receipt_item_ratings ALTER COLUMN id SET DEFAULT nextval('public.stock_receipt_item_ratings_id_seq'::regclass);


--
-- Name: stock_receipt_items id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.stock_receipt_items ALTER COLUMN id SET DEFAULT nextval('public.stock_receipt_items_id_seq'::regclass);


--
-- Name: stock_receipts id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.stock_receipts ALTER COLUMN id SET DEFAULT nextval('public.stock_receipts_id_seq'::regclass);


--
-- Name: suppliers id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.suppliers ALTER COLUMN id SET DEFAULT nextval('public.suppliers_id_seq'::regclass);


--
-- Name: system_settings id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.system_settings ALTER COLUMN id SET DEFAULT nextval('public.system_settings_id_seq'::regclass);


--
-- Name: transaction_types id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.transaction_types ALTER COLUMN id SET DEFAULT nextval('public.transaction_types_id_seq'::regclass);


--
-- Name: user_sessions id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_sessions ALTER COLUMN id SET DEFAULT nextval('public.user_sessions_id_seq'::regclass);


--
-- Name: users id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users ALTER COLUMN id SET DEFAULT nextval('public.users_id_seq'::regclass);


--
-- Name: variant_attribute_values id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.variant_attribute_values ALTER COLUMN id SET DEFAULT nextval('public.variant_attribute_values_id_seq'::regclass);


--
-- PostgreSQL database dump complete
--

\unrestrict UcIVCRaFTvHHIisBykNyp3eCBCJUTHoAbvNJMe3aU7htJOEYYcdeUWXFzJJfvLO

