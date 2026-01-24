--
-- PostgreSQL database dump
--

\restrict Kq5zuGwPNlRY2mgR2wI3GaF3kkWsmd2qAZugayoyXqprvjOaNtXnK02rZH9R3PS

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

ALTER TABLE IF EXISTS ONLY public.variant_attribute_values DROP CONSTRAINT IF EXISTS variant_attribute_values_variant_id_fkey;
ALTER TABLE IF EXISTS ONLY public.variant_attribute_values DROP CONSTRAINT IF EXISTS variant_attribute_values_attribute_value_id_fkey;
ALTER TABLE IF EXISTS ONLY public.user_sessions DROP CONSTRAINT IF EXISTS user_sessions_user_id_fkey;
ALTER TABLE IF EXISTS ONLY public.system_settings DROP CONSTRAINT IF EXISTS system_settings_updated_by_fkey;
ALTER TABLE IF EXISTS ONLY public.suppliers DROP CONSTRAINT IF EXISTS suppliers_coordinate_id_foreign;
ALTER TABLE IF EXISTS ONLY public.stock_receipts DROP CONSTRAINT IF EXISTS stock_receipts_supplier_id_fkey;
ALTER TABLE IF EXISTS ONLY public.stock_receipts DROP CONSTRAINT IF EXISTS stock_receipts_freight_forwarder_id_fkey;
ALTER TABLE IF EXISTS ONLY public.stock_receipts DROP CONSTRAINT IF EXISTS stock_receipts_created_by_fkey;
ALTER TABLE IF EXISTS ONLY public.stock_receipts DROP CONSTRAINT IF EXISTS stock_receipts_cost_validated_by_fkey;
ALTER TABLE IF EXISTS ONLY public.stock_receipt_items DROP CONSTRAINT IF EXISTS stock_receipt_items_variant_id_fkey;
ALTER TABLE IF EXISTS ONLY public.stock_receipt_items DROP CONSTRAINT IF EXISTS stock_receipt_items_stock_receipt_id_fkey;
ALTER TABLE IF EXISTS ONLY public.stock_receipt_item_ratings DROP CONSTRAINT IF EXISTS stock_receipt_item_ratings_stock_receipt_item_id_fkey;
ALTER TABLE IF EXISTS ONLY public.stock_receipt_item_ratings DROP CONSTRAINT IF EXISTS stock_receipt_item_ratings_rated_by_fkey;
ALTER TABLE IF EXISTS ONLY public.stock_receipt_item_ratings DROP CONSTRAINT IF EXISTS stock_receipt_item_ratings_attribute_type_id_fkey;
ALTER TABLE IF EXISTS ONLY public.stock_movements DROP CONSTRAINT IF EXISTS stock_movements_variant_id_fkey;
ALTER TABLE IF EXISTS ONLY public.stock_movements DROP CONSTRAINT IF EXISTS stock_movements_to_location_id_fkey;
ALTER TABLE IF EXISTS ONLY public.stock_movements DROP CONSTRAINT IF EXISTS stock_movements_stock_receipt_id_fkey;
ALTER TABLE IF EXISTS ONLY public.stock_movements DROP CONSTRAINT IF EXISTS stock_movements_sale_id_fkey;
ALTER TABLE IF EXISTS ONLY public.stock_movements DROP CONSTRAINT IF EXISTS stock_movements_performed_by_fkey;
ALTER TABLE IF EXISTS ONLY public.stock_movements DROP CONSTRAINT IF EXISTS stock_movements_from_location_id_fkey;
ALTER TABLE IF EXISTS ONLY public.stock_batches DROP CONSTRAINT IF EXISTS stock_batches_variant_id_foreign;
ALTER TABLE IF EXISTS ONLY public.stock_batches DROP CONSTRAINT IF EXISTS stock_batches_stock_receipt_item_id_foreign;
ALTER TABLE IF EXISTS ONLY public.sales DROP CONSTRAINT IF EXISTS sales_user_id_fkey;
ALTER TABLE IF EXISTS ONLY public.sales DROP CONSTRAINT IF EXISTS sales_customer_id_fkey;
ALTER TABLE IF EXISTS ONLY public.sale_items DROP CONSTRAINT IF EXISTS sale_items_variant_id_fkey;
ALTER TABLE IF EXISTS ONLY public.sale_items DROP CONSTRAINT IF EXISTS sale_items_sale_id_fkey;
ALTER TABLE IF EXISTS ONLY public.sale_item_batches DROP CONSTRAINT IF EXISTS sale_item_batches_sale_item_id_foreign;
ALTER TABLE IF EXISTS ONLY public.sale_item_batches DROP CONSTRAINT IF EXISTS sale_item_batches_location_id_fkey;
ALTER TABLE IF EXISTS ONLY public.sale_item_batches DROP CONSTRAINT IF EXISTS sale_item_batches_batch_id_foreign;
ALTER TABLE IF EXISTS ONLY public.reservations DROP CONSTRAINT IF EXISTS reservations_transaction_complete_id_fkey;
ALTER TABLE IF EXISTS ONLY public.reservations DROP CONSTRAINT IF EXISTS reservations_sale_id_fkey;
ALTER TABLE IF EXISTS ONLY public.reservations DROP CONSTRAINT IF EXISTS reservations_customer_id_fkey;
ALTER TABLE IF EXISTS ONLY public.reservation_deposits DROP CONSTRAINT IF EXISTS reservation_deposits_transaction_id_foreign;
ALTER TABLE IF EXISTS ONLY public.reservation_deposits DROP CONSTRAINT IF EXISTS reservation_deposits_reservation_id_foreign;
ALTER TABLE IF EXISTS ONLY public.products DROP CONSTRAINT IF EXISTS products_subcategory_id_fkey;
ALTER TABLE IF EXISTS ONLY public.products DROP CONSTRAINT IF EXISTS products_category_id_fkey;
ALTER TABLE IF EXISTS ONLY public.product_variants DROP CONSTRAINT IF EXISTS product_variants_product_id_fkey;
ALTER TABLE IF EXISTS ONLY public.product_attributes DROP CONSTRAINT IF EXISTS product_attributes_product_id_fkey;
ALTER TABLE IF EXISTS ONLY public.product_attributes DROP CONSTRAINT IF EXISTS product_attributes_attribute_type_id_fkey;
ALTER TABLE IF EXISTS ONLY public.notifications DROP CONSTRAINT IF EXISTS notifications_user_id_fkey;
ALTER TABLE IF EXISTS ONLY public.notification_preferences DROP CONSTRAINT IF EXISTS notification_preferences_user_id_fkey;
ALTER TABLE IF EXISTS ONLY public.installment_transactions DROP CONSTRAINT IF EXISTS installment_transactions_transaction_id_foreign;
ALTER TABLE IF EXISTS ONLY public.installment_transactions DROP CONSTRAINT IF EXISTS installment_transactions_installment_id_foreign;
ALTER TABLE IF EXISTS ONLY public.freight_forwarders DROP CONSTRAINT IF EXISTS freight_forwarders_coordinate_id_foreign;
ALTER TABLE IF EXISTS ONLY public.product_variant_locations DROP CONSTRAINT IF EXISTS fk_variant;
ALTER TABLE IF EXISTS ONLY public.product_variant_locations DROP CONSTRAINT IF EXISTS fk_location;
ALTER TABLE IF EXISTS ONLY public.currency_rates DROP CONSTRAINT IF EXISTS currency_rates_created_by_fkey;
ALTER TABLE IF EXISTS ONLY public.credits DROP CONSTRAINT IF EXISTS credits_sale_id_fkey;
ALTER TABLE IF EXISTS ONLY public.credits DROP CONSTRAINT IF EXISTS credits_customer_id_fkey;
ALTER TABLE IF EXISTS ONLY public.credit_installments DROP CONSTRAINT IF EXISTS credit_installments_credit_id_fkey;
ALTER TABLE IF EXISTS ONLY public.categories DROP CONSTRAINT IF EXISTS categories_parent_id_fkey;
ALTER TABLE IF EXISTS ONLY public.cash_counts DROP CONSTRAINT IF EXISTS cash_counts_created_by_foreign;
ALTER TABLE IF EXISTS ONLY public.cash_count_denominations DROP CONSTRAINT IF EXISTS cash_count_denominations_cash_count_id_foreign;
ALTER TABLE IF EXISTS ONLY public.audit_logs DROP CONSTRAINT IF EXISTS audit_logs_user_id_fkey;
ALTER TABLE IF EXISTS ONLY public.attribute_values DROP CONSTRAINT IF EXISTS attribute_values_attribute_type_id_fkey;
ALTER TABLE IF EXISTS ONLY public.accounts DROP CONSTRAINT IF EXISTS accounts_created_by_fkey;
ALTER TABLE IF EXISTS ONLY public.accounts DROP CONSTRAINT IF EXISTS accounts_account_type_id_fkey;
ALTER TABLE IF EXISTS ONLY public.account_transactions DROP CONSTRAINT IF EXISTS account_transactions_transaction_type_id_fkey;
ALTER TABLE IF EXISTS ONLY public.account_transactions DROP CONSTRAINT IF EXISTS account_transactions_supplier_id_fkey;
ALTER TABLE IF EXISTS ONLY public.account_transactions DROP CONSTRAINT IF EXISTS account_transactions_stock_receipt_id_fkey;
ALTER TABLE IF EXISTS ONLY public.account_transactions DROP CONSTRAINT IF EXISTS account_transactions_sale_id_fkey;
ALTER TABLE IF EXISTS ONLY public.account_transactions DROP CONSTRAINT IF EXISTS account_transactions_reversed_transaction_id_foreign;
ALTER TABLE IF EXISTS ONLY public.account_transactions DROP CONSTRAINT IF EXISTS account_transactions_related_transaction_id_fkey;
ALTER TABLE IF EXISTS ONLY public.account_transactions DROP CONSTRAINT IF EXISTS account_transactions_related_account_id_fkey;
ALTER TABLE IF EXISTS ONLY public.account_transactions DROP CONSTRAINT IF EXISTS account_transactions_freight_forwarder_id_fkey;
ALTER TABLE IF EXISTS ONLY public.account_transactions DROP CONSTRAINT IF EXISTS account_transactions_expense_category_id_fkey;
ALTER TABLE IF EXISTS ONLY public.account_transactions DROP CONSTRAINT IF EXISTS account_transactions_created_by_fkey;
ALTER TABLE IF EXISTS ONLY public.account_transactions DROP CONSTRAINT IF EXISTS account_transactions_account_id_fkey;
DROP TRIGGER IF EXISTS update_users_updated_at ON public.users;
DROP TRIGGER IF EXISTS update_sales_updated_at ON public.sales;
DROP TRIGGER IF EXISTS update_products_updated_at ON public.products;
DROP TRIGGER IF EXISTS update_product_variants_updated_at ON public.product_variants;
DROP TRIGGER IF EXISTS update_customers_updated_at ON public.customers;
DROP TRIGGER IF EXISTS trigger_update_supplier_stats ON public.stock_receipts;
DROP TRIGGER IF EXISTS trigger_update_supplier_quality ON public.stock_receipt_item_ratings;
DROP TRIGGER IF EXISTS trigger_release_credit_stock ON public.credits;
DROP TRIGGER IF EXISTS trg_pvl_insert_update_stock ON public.product_variant_locations;
DROP TRIGGER IF EXISTS trg_pvl_delete_update_stock ON public.product_variant_locations;
DROP INDEX IF EXISTS public.stock_movements_batch_id_index;
DROP INDEX IF EXISTS public.sessions_user_id_index;
DROP INDEX IF EXISTS public.sessions_last_activity_index;
DROP INDEX IF EXISTS public.reservation_deposits_reservation_id_payment_date_index;
DROP INDEX IF EXISTS public.personal_access_tokens_tokenable_type_tokenable_id_index;
DROP INDEX IF EXISTS public.personal_access_tokens_expires_at_index;
DROP INDEX IF EXISTS public.jobs_queue_index;
DROP INDEX IF EXISTS public.installment_transactions_installment_id_payment_date_index;
DROP INDEX IF EXISTS public.idx_variant_remaining;
DROP INDEX IF EXISTS public.idx_user_sessions_user_id;
DROP INDEX IF EXISTS public.idx_user_sessions_login_at;
DROP INDEX IF EXISTS public.idx_system_settings_key;
DROP INDEX IF EXISTS public.idx_stock_receipts_supplier;
DROP INDEX IF EXISTS public.idx_stock_receipts_number;
DROP INDEX IF EXISTS public.idx_stock_receipt_items_variant;
DROP INDEX IF EXISTS public.idx_stock_receipt_items_receipt;
DROP INDEX IF EXISTS public.idx_stock_receipt_item_ratings_item;
DROP INDEX IF EXISTS public.idx_stock_receipt_item_ratings_attribute;
DROP INDEX IF EXISTS public.idx_stock_movements_variant;
DROP INDEX IF EXISTS public.idx_stock_movements_type;
DROP INDEX IF EXISTS public.idx_stock_movements_to_location;
DROP INDEX IF EXISTS public.idx_stock_movements_sale;
DROP INDEX IF EXISTS public.idx_stock_movements_receipt;
DROP INDEX IF EXISTS public.idx_stock_movements_performed_by;
DROP INDEX IF EXISTS public.idx_stock_movements_from_location;
DROP INDEX IF EXISTS public.idx_stock_movements_created_at;
DROP INDEX IF EXISTS public.idx_sr_expected_date;
DROP INDEX IF EXISTS public.idx_sr_actual_date;
DROP INDEX IF EXISTS public.idx_sib_sale_item;
DROP INDEX IF EXISTS public.idx_sib_date;
DROP INDEX IF EXISTS public.idx_sib_batch;
DROP INDEX IF EXISTS public.idx_sales_user;
DROP INDEX IF EXISTS public.idx_sales_type;
DROP INDEX IF EXISTS public.idx_sales_status;
DROP INDEX IF EXISTS public.idx_sales_number;
DROP INDEX IF EXISTS public.idx_sales_date;
DROP INDEX IF EXISTS public.idx_sales_customer;
DROP INDEX IF EXISTS public.idx_sale_items_variant;
DROP INDEX IF EXISTS public.idx_sale_items_sale;
DROP INDEX IF EXISTS public.idx_reservations_status;
DROP INDEX IF EXISTS public.idx_reservations_sale;
DROP INDEX IF EXISTS public.idx_reservations_expiry;
DROP INDEX IF EXISTS public.idx_reservations_customer;
DROP INDEX IF EXISTS public.idx_pvl_variant_stock;
DROP INDEX IF EXISTS public.idx_pvl_variant;
DROP INDEX IF EXISTS public.idx_pvl_quantity;
DROP INDEX IF EXISTS public.idx_pvl_location;
DROP INDEX IF EXISTS public.idx_products_subcategory_id;
DROP INDEX IF EXISTS public.idx_products_category_id;
DROP INDEX IF EXISTS public.idx_products_active;
DROP INDEX IF EXISTS public.idx_product_variants_sku;
DROP INDEX IF EXISTS public.idx_product_variants_product;
DROP INDEX IF EXISTS public.idx_product_variants_low_stock;
DROP INDEX IF EXISTS public.idx_product_variants_active;
DROP INDEX IF EXISTS public.idx_product_attributes_product;
DROP INDEX IF EXISTS public.idx_notifications_user;
DROP INDEX IF EXISTS public.idx_notifications_read;
DROP INDEX IF EXISTS public.idx_notifications_dismissed;
DROP INDEX IF EXISTS public.idx_notifications_created;
DROP INDEX IF EXISTS public.idx_notif_prefs_user;
DROP INDEX IF EXISTS public.idx_notif_prefs_type;
DROP INDEX IF EXISTS public.idx_locations_warehouse;
DROP INDEX IF EXISTS public.idx_locations_active;
DROP INDEX IF EXISTS public.idx_installments_due_status;
DROP INDEX IF EXISTS public.idx_customers_phone;
DROP INDEX IF EXISTS public.idx_customers_name;
DROP INDEX IF EXISTS public.idx_credits_status;
DROP INDEX IF EXISTS public.idx_credits_sale;
DROP INDEX IF EXISTS public.idx_credits_due_date;
DROP INDEX IF EXISTS public.idx_credits_customer;
DROP INDEX IF EXISTS public.idx_credit_installments_due_date;
DROP INDEX IF EXISTS public.idx_credit_installments_credit;
DROP INDEX IF EXISTS public.idx_cost_status;
DROP INDEX IF EXISTS public.idx_categories_parent_id;
DROP INDEX IF EXISTS public.idx_audit_logs_user;
DROP INDEX IF EXISTS public.idx_audit_logs_table;
DROP INDEX IF EXISTS public.idx_audit_logs_created;
DROP INDEX IF EXISTS public.idx_attribute_values_type;
DROP INDEX IF EXISTS public.idx_accounts_type;
DROP INDEX IF EXISTS public.idx_accounts_active;
DROP INDEX IF EXISTS public.idx_account_transactions_type;
DROP INDEX IF EXISTS public.idx_account_transactions_supplier;
DROP INDEX IF EXISTS public.idx_account_transactions_sale;
DROP INDEX IF EXISTS public.idx_account_transactions_freight;
DROP INDEX IF EXISTS public.idx_account_transactions_date;
DROP INDEX IF EXISTS public.idx_account_transactions_account;
DROP INDEX IF EXISTS public.coordinates_country_index;
DROP INDEX IF EXISTS public.coordinates_city_index;
DROP INDEX IF EXISTS public.account_transactions_reversed_transaction_id_index;
ALTER TABLE IF EXISTS ONLY public.variant_attribute_values DROP CONSTRAINT IF EXISTS variant_attribute_values_variant_id_attribute_value_id_key;
ALTER TABLE IF EXISTS ONLY public.variant_attribute_values DROP CONSTRAINT IF EXISTS variant_attribute_values_pkey;
ALTER TABLE IF EXISTS ONLY public.users DROP CONSTRAINT IF EXISTS users_usename_key;
ALTER TABLE IF EXISTS ONLY public.users DROP CONSTRAINT IF EXISTS users_pkey;
ALTER TABLE IF EXISTS ONLY public.user_sessions DROP CONSTRAINT IF EXISTS user_sessions_pkey;
ALTER TABLE IF EXISTS ONLY public.product_variant_locations DROP CONSTRAINT IF EXISTS uq_variant_location;
ALTER TABLE IF EXISTS ONLY public.notification_preferences DROP CONSTRAINT IF EXISTS unique_user_notification_type;
ALTER TABLE IF EXISTS ONLY public.transaction_types DROP CONSTRAINT IF EXISTS transaction_types_pkey;
ALTER TABLE IF EXISTS ONLY public.transaction_types DROP CONSTRAINT IF EXISTS transaction_types_code_key;
ALTER TABLE IF EXISTS ONLY public.system_settings DROP CONSTRAINT IF EXISTS system_settings_pkey;
ALTER TABLE IF EXISTS ONLY public.system_settings DROP CONSTRAINT IF EXISTS system_settings_key_key;
ALTER TABLE IF EXISTS ONLY public.suppliers DROP CONSTRAINT IF EXISTS suppliers_pkey;
ALTER TABLE IF EXISTS ONLY public.stock_receipts DROP CONSTRAINT IF EXISTS stock_receipts_receipt_number_key;
ALTER TABLE IF EXISTS ONLY public.stock_receipts DROP CONSTRAINT IF EXISTS stock_receipts_pkey;
ALTER TABLE IF EXISTS ONLY public.stock_receipt_items DROP CONSTRAINT IF EXISTS stock_receipt_items_pkey;
ALTER TABLE IF EXISTS ONLY public.stock_receipt_item_ratings DROP CONSTRAINT IF EXISTS stock_receipt_item_ratings_pkey;
ALTER TABLE IF EXISTS ONLY public.stock_movements DROP CONSTRAINT IF EXISTS stock_movements_pkey;
ALTER TABLE IF EXISTS ONLY public.stock_batches DROP CONSTRAINT IF EXISTS stock_batches_pkey;
ALTER TABLE IF EXISTS ONLY public.stock_batches DROP CONSTRAINT IF EXISTS stock_batches_batch_number_unique;
ALTER TABLE IF EXISTS ONLY public.sessions DROP CONSTRAINT IF EXISTS sessions_pkey;
ALTER TABLE IF EXISTS ONLY public.sales DROP CONSTRAINT IF EXISTS sales_sale_number_key;
ALTER TABLE IF EXISTS ONLY public.sales DROP CONSTRAINT IF EXISTS sales_pkey;
ALTER TABLE IF EXISTS ONLY public.sale_items DROP CONSTRAINT IF EXISTS sale_items_pkey;
ALTER TABLE IF EXISTS ONLY public.sale_item_batches DROP CONSTRAINT IF EXISTS sale_item_batches_pkey;
ALTER TABLE IF EXISTS ONLY public.reservations DROP CONSTRAINT IF EXISTS reservations_sale_id_key;
ALTER TABLE IF EXISTS ONLY public.reservations DROP CONSTRAINT IF EXISTS reservations_pkey;
ALTER TABLE IF EXISTS ONLY public.reservation_deposits DROP CONSTRAINT IF EXISTS reservation_deposits_pkey;
ALTER TABLE IF EXISTS ONLY public.products DROP CONSTRAINT IF EXISTS products_pkey;
ALTER TABLE IF EXISTS ONLY public.product_variants DROP CONSTRAINT IF EXISTS product_variants_sku_key;
ALTER TABLE IF EXISTS ONLY public.product_variants DROP CONSTRAINT IF EXISTS product_variants_pkey;
ALTER TABLE IF EXISTS ONLY public.product_variant_locations DROP CONSTRAINT IF EXISTS product_variant_locations_pkey;
ALTER TABLE IF EXISTS ONLY public.product_attributes DROP CONSTRAINT IF EXISTS product_attributes_product_id_attribute_type_id_key;
ALTER TABLE IF EXISTS ONLY public.product_attributes DROP CONSTRAINT IF EXISTS product_attributes_pkey;
ALTER TABLE IF EXISTS ONLY public.personal_access_tokens DROP CONSTRAINT IF EXISTS personal_access_tokens_token_unique;
ALTER TABLE IF EXISTS ONLY public.personal_access_tokens DROP CONSTRAINT IF EXISTS personal_access_tokens_pkey;
ALTER TABLE IF EXISTS ONLY public.password_reset_tokens DROP CONSTRAINT IF EXISTS password_reset_tokens_pkey;
ALTER TABLE IF EXISTS ONLY public.notifications DROP CONSTRAINT IF EXISTS notifications_pkey;
ALTER TABLE IF EXISTS ONLY public.notification_preferences DROP CONSTRAINT IF EXISTS notification_preferences_pkey;
ALTER TABLE IF EXISTS ONLY public.migrations DROP CONSTRAINT IF EXISTS migrations_pkey;
ALTER TABLE IF EXISTS ONLY public.locations DROP CONSTRAINT IF EXISTS locations_pkey;
ALTER TABLE IF EXISTS ONLY public.locations DROP CONSTRAINT IF EXISTS locations_name_key;
ALTER TABLE IF EXISTS ONLY public.locations DROP CONSTRAINT IF EXISTS locations_code_key;
ALTER TABLE IF EXISTS ONLY public.jobs DROP CONSTRAINT IF EXISTS jobs_pkey;
ALTER TABLE IF EXISTS ONLY public.job_batches DROP CONSTRAINT IF EXISTS job_batches_pkey;
ALTER TABLE IF EXISTS ONLY public.installment_transactions DROP CONSTRAINT IF EXISTS installment_transactions_pkey;
ALTER TABLE IF EXISTS ONLY public.freight_forwarders DROP CONSTRAINT IF EXISTS freight_forwarders_pkey;
ALTER TABLE IF EXISTS ONLY public.failed_jobs DROP CONSTRAINT IF EXISTS failed_jobs_uuid_unique;
ALTER TABLE IF EXISTS ONLY public.failed_jobs DROP CONSTRAINT IF EXISTS failed_jobs_pkey;
ALTER TABLE IF EXISTS ONLY public.expense_categories DROP CONSTRAINT IF EXISTS expense_categories_pkey;
ALTER TABLE IF EXISTS ONLY public.customers DROP CONSTRAINT IF EXISTS customers_pkey;
ALTER TABLE IF EXISTS ONLY public.customers DROP CONSTRAINT IF EXISTS customers_customer_number_unique;
ALTER TABLE IF EXISTS ONLY public.currency_rates DROP CONSTRAINT IF EXISTS currency_rates_pkey;
ALTER TABLE IF EXISTS ONLY public.credits DROP CONSTRAINT IF EXISTS credits_sale_id_key;
ALTER TABLE IF EXISTS ONLY public.credits DROP CONSTRAINT IF EXISTS credits_pkey;
ALTER TABLE IF EXISTS ONLY public.credit_installments DROP CONSTRAINT IF EXISTS credit_installments_pkey;
ALTER TABLE IF EXISTS ONLY public.credit_installments DROP CONSTRAINT IF EXISTS credit_installments_credit_id_installment_number_key;
ALTER TABLE IF EXISTS ONLY public.coordinates DROP CONSTRAINT IF EXISTS coordinates_pkey;
ALTER TABLE IF EXISTS ONLY public.coordinates DROP CONSTRAINT IF EXISTS coordinates_country_city_unique;
ALTER TABLE IF EXISTS ONLY public.categories DROP CONSTRAINT IF EXISTS categories_pkey;
ALTER TABLE IF EXISTS ONLY public.cash_counts DROP CONSTRAINT IF EXISTS cash_counts_pkey;
ALTER TABLE IF EXISTS ONLY public.cash_counts DROP CONSTRAINT IF EXISTS cash_counts_count_date_unique;
ALTER TABLE IF EXISTS ONLY public.cash_count_denominations DROP CONSTRAINT IF EXISTS cash_count_denominations_pkey;
ALTER TABLE IF EXISTS ONLY public.cash_count_denominations DROP CONSTRAINT IF EXISTS cash_count_denominations_cash_count_id_denomination_unique;
ALTER TABLE IF EXISTS ONLY public.cache DROP CONSTRAINT IF EXISTS cache_pkey;
ALTER TABLE IF EXISTS ONLY public.cache_locks DROP CONSTRAINT IF EXISTS cache_locks_pkey;
ALTER TABLE IF EXISTS ONLY public.audit_logs DROP CONSTRAINT IF EXISTS audit_logs_pkey;
ALTER TABLE IF EXISTS ONLY public.attribute_values DROP CONSTRAINT IF EXISTS attribute_values_pkey;
ALTER TABLE IF EXISTS ONLY public.attribute_values DROP CONSTRAINT IF EXISTS attribute_values_attribute_type_id_value_key;
ALTER TABLE IF EXISTS ONLY public.attribute_types DROP CONSTRAINT IF EXISTS attribute_types_pkey;
ALTER TABLE IF EXISTS ONLY public.attribute_types DROP CONSTRAINT IF EXISTS attribute_types_name_key;
ALTER TABLE IF EXISTS ONLY public.accounts DROP CONSTRAINT IF EXISTS accounts_pkey;
ALTER TABLE IF EXISTS ONLY public.account_types DROP CONSTRAINT IF EXISTS account_types_pkey;
ALTER TABLE IF EXISTS ONLY public.account_types DROP CONSTRAINT IF EXISTS account_types_code_key;
ALTER TABLE IF EXISTS ONLY public.account_transactions DROP CONSTRAINT IF EXISTS account_transactions_pkey;
ALTER TABLE IF EXISTS public.variant_attribute_values ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.users ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.user_sessions ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.transaction_types ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.system_settings ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.suppliers ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.stock_receipts ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.stock_receipt_items ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.stock_receipt_item_ratings ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.stock_movements ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.stock_batches ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.sales ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.sale_items ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.sale_item_batches ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.reservations ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.reservation_deposits ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.products ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.product_variants ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.product_variant_locations ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.product_attributes ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.personal_access_tokens ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.notifications ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.notification_preferences ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.migrations ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.locations ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.jobs ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.installment_transactions ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.freight_forwarders ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.failed_jobs ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.expense_categories ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.customers ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.currency_rates ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.credits ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.credit_installments ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.coordinates ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.categories ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.cash_counts ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.cash_count_denominations ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.audit_logs ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.attribute_values ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.attribute_types ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.accounts ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.account_types ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.account_transactions ALTER COLUMN id DROP DEFAULT;
DROP SEQUENCE IF EXISTS public.variant_attribute_values_id_seq;
DROP TABLE IF EXISTS public.variant_attribute_values;
DROP VIEW IF EXISTS public.v_treasury_dashboard;
DROP VIEW IF EXISTS public.v_reservations_pending;
DROP VIEW IF EXISTS public.v_recent_transactions;
DROP VIEW IF EXISTS public.v_credits_at_risk;
DROP VIEW IF EXISTS public.v_accounts_summary;
DROP SEQUENCE IF EXISTS public.users_id_seq;
DROP TABLE IF EXISTS public.users;
DROP SEQUENCE IF EXISTS public.user_sessions_id_seq;
DROP TABLE IF EXISTS public.user_sessions;
DROP SEQUENCE IF EXISTS public.transaction_types_id_seq;
DROP TABLE IF EXISTS public.transaction_types;
DROP SEQUENCE IF EXISTS public.system_settings_id_seq;
DROP TABLE IF EXISTS public.system_settings;
DROP SEQUENCE IF EXISTS public.suppliers_id_seq;
DROP TABLE IF EXISTS public.suppliers;
DROP SEQUENCE IF EXISTS public.stock_receipts_id_seq;
DROP TABLE IF EXISTS public.stock_receipts;
DROP SEQUENCE IF EXISTS public.stock_receipt_items_id_seq;
DROP TABLE IF EXISTS public.stock_receipt_items;
DROP SEQUENCE IF EXISTS public.stock_receipt_item_ratings_id_seq;
DROP TABLE IF EXISTS public.stock_receipt_item_ratings;
DROP SEQUENCE IF EXISTS public.stock_movements_id_seq;
DROP TABLE IF EXISTS public.stock_movements;
DROP SEQUENCE IF EXISTS public.stock_batches_id_seq;
DROP TABLE IF EXISTS public.stock_batches;
DROP TABLE IF EXISTS public.sessions;
DROP SEQUENCE IF EXISTS public.sales_id_seq;
DROP TABLE IF EXISTS public.sales;
DROP SEQUENCE IF EXISTS public.sale_items_id_seq;
DROP TABLE IF EXISTS public.sale_items;
DROP SEQUENCE IF EXISTS public.sale_item_batches_id_seq;
DROP TABLE IF EXISTS public.sale_item_batches;
DROP SEQUENCE IF EXISTS public.reservations_id_seq;
DROP TABLE IF EXISTS public.reservations;
DROP SEQUENCE IF EXISTS public.reservation_deposits_id_seq;
DROP TABLE IF EXISTS public.reservation_deposits;
DROP SEQUENCE IF EXISTS public.products_id_seq;
DROP TABLE IF EXISTS public.products;
DROP SEQUENCE IF EXISTS public.product_variants_id_seq;
DROP TABLE IF EXISTS public.product_variants;
DROP SEQUENCE IF EXISTS public.product_variant_locations_id_seq;
DROP TABLE IF EXISTS public.product_variant_locations;
DROP SEQUENCE IF EXISTS public.product_attributes_id_seq;
DROP TABLE IF EXISTS public.product_attributes;
DROP SEQUENCE IF EXISTS public.personal_access_tokens_id_seq;
DROP TABLE IF EXISTS public.personal_access_tokens;
DROP TABLE IF EXISTS public.password_reset_tokens;
DROP SEQUENCE IF EXISTS public.notifications_id_seq;
DROP TABLE IF EXISTS public.notifications;
DROP SEQUENCE IF EXISTS public.notification_preferences_id_seq;
DROP TABLE IF EXISTS public.notification_preferences;
DROP SEQUENCE IF EXISTS public.migrations_id_seq;
DROP TABLE IF EXISTS public.migrations;
DROP SEQUENCE IF EXISTS public.locations_id_seq;
DROP TABLE IF EXISTS public.locations;
DROP SEQUENCE IF EXISTS public.jobs_id_seq;
DROP TABLE IF EXISTS public.jobs;
DROP TABLE IF EXISTS public.job_batches;
DROP SEQUENCE IF EXISTS public.installment_transactions_id_seq;
DROP TABLE IF EXISTS public.installment_transactions;
DROP SEQUENCE IF EXISTS public.freight_forwarders_id_seq;
DROP TABLE IF EXISTS public.freight_forwarders;
DROP SEQUENCE IF EXISTS public.failed_jobs_id_seq;
DROP TABLE IF EXISTS public.failed_jobs;
DROP SEQUENCE IF EXISTS public.expense_categories_id_seq;
DROP TABLE IF EXISTS public.expense_categories;
DROP SEQUENCE IF EXISTS public.customers_id_seq;
DROP TABLE IF EXISTS public.customers;
DROP SEQUENCE IF EXISTS public.currency_rates_id_seq;
DROP TABLE IF EXISTS public.currency_rates;
DROP SEQUENCE IF EXISTS public.credits_id_seq;
DROP TABLE IF EXISTS public.credits;
DROP SEQUENCE IF EXISTS public.credit_installments_id_seq;
DROP TABLE IF EXISTS public.credit_installments;
DROP SEQUENCE IF EXISTS public.coordinates_id_seq;
DROP TABLE IF EXISTS public.coordinates;
DROP TABLE IF EXISTS public.company_infos;
DROP SEQUENCE IF EXISTS public.categories_id_seq;
DROP TABLE IF EXISTS public.categories;
DROP SEQUENCE IF EXISTS public.cash_counts_id_seq;
DROP TABLE IF EXISTS public.cash_counts;
DROP SEQUENCE IF EXISTS public.cash_count_denominations_id_seq;
DROP TABLE IF EXISTS public.cash_count_denominations;
DROP TABLE IF EXISTS public.cache_locks;
DROP TABLE IF EXISTS public.cache;
DROP SEQUENCE IF EXISTS public.audit_logs_id_seq;
DROP TABLE IF EXISTS public.audit_logs;
DROP SEQUENCE IF EXISTS public.attribute_values_id_seq;
DROP TABLE IF EXISTS public.attribute_values;
DROP SEQUENCE IF EXISTS public.attribute_types_id_seq;
DROP TABLE IF EXISTS public.attribute_types;
DROP SEQUENCE IF EXISTS public.accounts_id_seq;
DROP TABLE IF EXISTS public.accounts;
DROP SEQUENCE IF EXISTS public.account_types_id_seq;
DROP TABLE IF EXISTS public.account_types;
DROP SEQUENCE IF EXISTS public.account_transactions_id_seq;
DROP TABLE IF EXISTS public.account_transactions;
DROP FUNCTION IF EXISTS public.update_updated_at_column();
DROP FUNCTION IF EXISTS public.update_supplier_stats_on_receipt();
DROP FUNCTION IF EXISTS public.update_supplier_quality_rating();
DROP FUNCTION IF EXISTS public.update_account_balance();
DROP FUNCTION IF EXISTS public.release_credit_stock();
DROP FUNCTION IF EXISTS public.record_supplier_payment(p_account_id integer, p_supplier_id integer, p_stock_receipt_id integer, p_amount numeric, p_reference_number character varying, p_notes text, p_created_by integer);
DROP FUNCTION IF EXISTS public.record_sale_transaction(p_account_id integer, p_sale_id integer, p_amount numeric, p_created_by integer);
DROP FUNCTION IF EXISTS public.record_reservation_deposit(p_reservation_id integer, p_sale_id integer, p_account_id integer, p_deposit_amount numeric, p_payment_method character varying, p_reference_number character varying, p_created_by integer);
DROP FUNCTION IF EXISTS public.record_operating_expense(p_account_id integer, p_expense_category_id integer, p_amount numeric, p_recipient_name character varying, p_description text, p_notes text, p_created_by integer);
DROP FUNCTION IF EXISTS public.record_freight_payment(p_account_id integer, p_freight_forwarder_id integer, p_stock_receipt_id integer, p_amount numeric, p_reference_number character varying, p_notes text, p_created_by integer);
DROP FUNCTION IF EXISTS public.record_direct_sale_payment(p_sale_id integer, p_account_id integer, p_payment_method character varying, p_amount numeric, p_cash_amount numeric, p_mobile_money_amount numeric, p_mobile_money_number character varying, p_mobile_money_fees numeric, p_bank_transfer_amount numeric, p_reference_number character varying, p_created_by integer);
DROP FUNCTION IF EXISTS public.record_credit_sale_initial_payment(p_sale_id integer, p_credit_id integer, p_account_id integer, p_amount_paid numeric, p_payment_method character varying, p_reference_number character varying, p_created_by integer);
DROP FUNCTION IF EXISTS public.record_credit_payment(p_credit_installment_id integer, p_account_id integer, p_amount_paid numeric, p_payment_method character varying, p_reference_number character varying, p_notes text, p_created_by integer);
DROP FUNCTION IF EXISTS public.record_account_transfer(p_from_account_id integer, p_to_account_id integer, p_amount numeric, p_description text, p_created_by integer);
DROP FUNCTION IF EXISTS public.recalculate_variant_stock();
DROP FUNCTION IF EXISTS public.create_account_with_initial_balance(p_account_type_id integer, p_name character varying, p_account_number character varying, p_initial_balance numeric, p_notes text, p_created_by integer);
DROP FUNCTION IF EXISTS public.complete_reservation_payment(p_reservation_id integer, p_sale_id integer, p_account_id integer, p_remaining_amount numeric, p_payment_method character varying, p_reference_number character varying, p_created_by integer);
DROP FUNCTION IF EXISTS public.calculate_supplier_score(supplier_id_param integer);
DROP FUNCTION IF EXISTS public.calculate_freight_forwarder_score(ff_id_param integer);
DROP EXTENSION IF EXISTS "uuid-ossp";
-- *not* dropping schema, since initdb creates it
--
-- Name: public; Type: SCHEMA; Schema: -; Owner: -
--

-- *not* creating schema, since initdb creates it


--
-- Name: SCHEMA public; Type: COMMENT; Schema: -; Owner: -
--

COMMENT ON SCHEMA public IS '';


--
-- Name: uuid-ossp; Type: EXTENSION; Schema: -; Owner: -
--

CREATE EXTENSION IF NOT EXISTS "uuid-ossp" WITH SCHEMA public;


--
-- Name: EXTENSION "uuid-ossp"; Type: COMMENT; Schema: -; Owner: -
--

COMMENT ON EXTENSION "uuid-ossp" IS 'generate universally unique identifiers (UUIDs)';


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
-- Name: company_infos; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.company_infos (
    name text CONSTRAINT company_info_name_not_null NOT NULL,
    phone text,
    address text,
    email text,
    logo_path text,
    invoice_signature text
);


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
    is_extra_customer boolean DEFAULT false,
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
-- Name: notification_preferences; Type: TABLE; Schema: public; Owner: -
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


--
-- Name: TABLE notification_preferences; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE public.notification_preferences IS 'Préférences de notifications par utilisateur et type';


--
-- Name: COLUMN notification_preferences.notification_type; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.notification_preferences.notification_type IS 'Types: stock_low, stock_out, reservation_expiring, credit_due';


--
-- Name: COLUMN notification_preferences.reminder_interval_days; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.notification_preferences.reminder_interval_days IS 'Intervalle en jours entre les rappels pour la même alerte';


--
-- Name: notification_preferences_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.notification_preferences_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: notification_preferences_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.notification_preferences_id_seq OWNED BY public.notification_preferences.id;


--
-- Name: notifications; Type: TABLE; Schema: public; Owner: -
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


--
-- Name: COLUMN notifications.dismissed_at; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.notifications.dismissed_at IS 'Date de dismissal définitif (différent de read_at)';


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
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    reserved_quantity integer DEFAULT 0 NOT NULL
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
    available_quantity integer,
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
    transaction_complete_id bigint,
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
    discount_at_sale real,
    status character varying(20),
    location_id integer,
    CONSTRAINT chk_sib_price_positive CHECK ((unit_price_at_sale >= (0)::numeric)),
    CONSTRAINT chk_sib_quantity_positive CHECK ((quantity > 0)),
    CONSTRAINT sale_item_batches_status_check CHECK (((status)::text = ANY ((ARRAY['reserved'::character varying, 'sold'::character varying, 'cancelled'::character varying])::text[])))
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
    received_date timestamp without time zone,
    reserved_quantity integer DEFAULT 0 NOT NULL,
    available_quantity integer,
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
-- Name: notification_preferences id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.notification_preferences ALTER COLUMN id SET DEFAULT nextval('public.notification_preferences_id_seq'::regclass);


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
-- Data for Name: account_transactions; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.account_transactions (id, account_id, transaction_type_id, amount, balance_before, balance_after, transaction_date, related_account_id, related_transaction_id, supplier_id, freight_forwarder_id, stock_receipt_id, expense_category_id, recipient_name, sale_id, reference_number, description, notes, created_by, created_at, reversed_transaction_id) FROM stdin;
187	7	17	1606500.00	27743505.34	26137005.34	2026-01-20 14:36:30	\N	\N	7	\N	38	15	Uswell	\N	RE-20260120-000001	Paiement fournisseur Uswell - Réception #RCP-20260120-0001	\N	4	2026-01-20 14:36:30	\N
188	7	17	50000.00	26137005.34	26087005.34	2026-01-20 00:00:00	\N	\N	\N	3	38	6	Madagascar Airlines	\N	RE-20260120-000002	Dépense Transport & Transit à Madagascar Airlines	\N	4	2026-01-20 14:37:29	\N
189	7	17	10000.00	26087005.34	26077005.34	2026-01-20 00:00:00	\N	\N	\N	\N	38	18	jean	\N	RE-20260120-000003	Dépense Livraison à domicile à jean	\N	4	2026-01-20 14:38:39	\N
190	6	16	40000.00	429000.00	469000.00	2026-01-20 15:51:53	\N	\N	\N	\N	\N	\N	\N	44	FAC-20260120-000001	Vente #VNT-20260120-0001		4	2026-01-20 15:51:53	\N
191	9	16	70000.00	121003.00	191003.00	2026-01-20 15:55:53	\N	\N	\N	\N	\N	\N	\N	45	FAC-20260120-000002	Vente #VNT-20260120-0002		4	2026-01-20 15:55:53	\N
193	7	16	295500.00	26177005.34	26472505.34	2026-01-20 18:24:55	\N	\N	\N	\N	\N	\N	\N	55	FAC-20260120-000003	Vente #VNT-20260120-0004		4	2026-01-20 18:24:55	\N
194	7	16	45000.00	26472505.34	26517505.34	2026-01-20 18:26:11	\N	\N	\N	\N	\N	\N	\N	56	FAC-20260120-000004	Acompte réservation #VNT-20260120-0005		4	2026-01-20 18:26:11	\N
195	7	16	152000.00	26517505.34	26669505.34	2026-01-20 18:35:41	\N	\N	\N	\N	\N	\N	\N	56	FAC-20260120-000005	Paiement final réservation #VNT-20260120-0005		4	2026-01-20 18:35:41	\N
196	7	16	196000.00	26669505.34	26865505.34	2026-01-20 18:42:13	\N	\N	\N	\N	\N	\N	\N	57	FAC-20260120-000006	Vente #VNT-20260120-0006		4	2026-01-20 18:42:13	\N
197	9	16	34000.00	191003.00	225003.00	2026-01-20 18:43:54	\N	\N	\N	\N	\N	\N	\N	58	FAC-20260120-000007	Acompte réservation #VNT-20260120-0007		4	2026-01-20 18:43:54	\N
198	6	16	63700.00	469000.00	532700.00	2026-01-20 18:44:55	\N	\N	\N	\N	\N	\N	\N	58	FAC-20260120-000008	Paiement final réservation #VNT-20260120-0007		4	2026-01-20 18:44:55	\N
199	10	17	510570.00	913146.00	402576.00	2026-01-20 19:28:32	\N	\N	3	\N	39	15	raggathon	\N	RE-20260120-000004	Paiement fournisseur raggathon - Réception #RCP-20260120-0002	\N	4	2026-01-20 19:28:32	\N
200	7	17	34000.00	26865505.34	26831505.34	2026-01-20 00:00:00	\N	\N	\N	2	39	6	htm logistics	\N	RE-20260120-000005	Dépense Transport & Transit à htm logistics	\N	4	2026-01-20 19:29:19	\N
201	7	17	90000.00	26831505.34	26741505.34	2026-01-20 00:00:00	\N	\N	\N	\N	39	17	douane	\N	RE-20260120-000006	Dépense Melinda à douane	\N	4	2026-01-20 19:29:47	\N
202	9	16	98500.00	225003.00	323503.00	2026-01-20 19:35:01	\N	\N	\N	\N	\N	\N	\N	59	FAC-20260120-000009	Vente #VNT-20260120-0008		4	2026-01-20 19:35:01	\N
203	9	16	1200.00	323503.00	324703.00	2026-01-20 19:39:21	\N	\N	\N	\N	\N	\N	\N	60	FAC-20260120-000010	Acompte réservation #VNT-20260120-0009		4	2026-01-20 19:39:21	\N
204	7	16	12800.00	26741505.34	26754305.34	2026-01-20 19:40:13	\N	\N	\N	\N	\N	\N	\N	60	FAC-20260120-000011	Paiement final réservation #VNT-20260120-0009		4	2026-01-20 19:40:13	\N
205	9	16	80000.00	324703.00	404703.00	2026-01-20 19:41:55	\N	\N	\N	\N	\N	\N	\N	61	FAC-20260120-000012	Acompte réservation #VNT-20260120-0010		4	2026-01-20 19:41:55	\N
206	7	16	75000.00	26754305.34	26829305.34	2026-01-20 19:45:59	\N	\N	\N	\N	\N	\N	\N	62	FAC-20260120-000013	Vente #VNT-20260120-0011		4	2026-01-20 19:45:59	\N
207	7	16	45000.00	26829305.34	26874305.34	2026-01-20 19:47:31	\N	\N	\N	\N	\N	\N	\N	63	FAC-20260120-000014	Acompte réservation #VNT-20260120-0012		4	2026-01-20 19:47:31	\N
208	6	16	41000.00	532700.00	573700.00	2026-01-20 19:48:13	\N	\N	\N	\N	\N	\N	\N	63	FAC-20260120-000015	Paiement final réservation #VNT-20260120-0012		4	2026-01-20 19:48:13	\N
209	7	16	20000.00	26874305.34	26894305.34	2026-01-20 19:53:18	\N	\N	\N	\N	\N	\N	\N	64	FAC-20260120-000016	Acompte réservation #VNT-20260120-0013		4	2026-01-20 19:53:18	\N
210	6	16	705000.00	573700.00	1278700.00	2026-01-20 19:56:33	\N	\N	\N	\N	\N	\N	\N	46	FAC-20260120-000017	Paiement crédit #VNT-20260120-0003 - Échéance #1		4	2026-01-20 19:56:33	\N
211	6	16	9000.00	1278700.00	1287700.00	2026-01-20 20:05:56	\N	\N	\N	\N	\N	\N	\N	66	FAC-20260120-000018	Paiement crédit #VNT-20260120-0015 - Échéance #1		4	2026-01-20 20:05:56	\N
212	6	16	9000.00	1287700.00	1296700.00	2026-01-20 20:07:58	\N	\N	\N	\N	\N	\N	\N	67	FAC-20260120-000019	Paiement crédit #VNT-20260120-0016 - Échéance #1		4	2026-01-20 20:07:58	\N
213	6	16	6700.00	1296700.00	1303400.00	2026-01-20 20:14:07	\N	\N	\N	\N	\N	\N	\N	68	FAC-20260120-000020	Paiement crédit #VNT-20260120-0017 - Échéance #1		4	2026-01-20 20:14:07	\N
214	7	16	44977.00	26894305.34	26939282.34	2026-01-20 20:20:20	\N	\N	\N	\N	\N	\N	\N	69	FAC-20260120-000021	Vente #VNT-20260120-0018		4	2026-01-20 20:20:20	\N
215	6	16	6000.00	1303400.00	1309400.00	2026-01-20 20:21:54	\N	\N	\N	\N	\N	\N	\N	70	FAC-20260120-000022	Paiement crédit #VNT-20260120-0019 - Échéance #1		4	2026-01-20 20:21:54	\N
216	7	16	90000.00	26939282.34	27029282.34	2026-01-20 20:23:46	\N	\N	\N	\N	\N	\N	\N	71	FAC-20260120-000023	Vente #VNT-20260120-0020		4	2026-01-20 20:23:46	\N
217	6	16	9700.00	1309400.00	1319100.00	2026-01-20 20:25:42	\N	\N	\N	\N	\N	\N	\N	72	FAC-20260120-000024	Paiement crédit #VNT-20260120-0021 - Échéance #1		4	2026-01-20 20:25:42	\N
218	6	16	4010.00	1319100.00	1323110.00	2026-01-20 20:32:30	\N	\N	\N	\N	\N	\N	\N	73	FAC-20260120-000025	Paiement crédit #VNT-20260120-0022 - Échéance #1		4	2026-01-20 20:32:30	\N
219	6	16	500.00	1323110.00	1323610.00	2026-01-20 20:37:54	\N	\N	\N	\N	\N	\N	\N	74	FAC-20260120-000026	Paiement crédit #VNT-20260120-0023 - Échéance #1		4	2026-01-20 20:37:54	\N
220	9	16	45000.00	404703.00	449703.00	2026-01-21 06:23:23	\N	\N	\N	\N	\N	\N	\N	75	FAC-20260121-000001	Acompte réservation #VNT-20260121-0001		4	2026-01-21 06:23:23	\N
221	7	16	13000.00	27029282.34	27042282.34	2026-01-21 06:25:01	\N	\N	\N	\N	\N	\N	\N	75	FAC-20260121-000002	Paiement final réservation #VNT-20260121-0001		4	2026-01-21 06:25:01	\N
222	6	16	174000.00	1323610.00	1497610.00	2026-01-21 06:28:00	\N	\N	\N	\N	\N	\N	\N	76	FAC-20260121-000003	Paiement crédit #VNT-20260121-0002 - Échéance #1		4	2026-01-21 06:28:00	\N
223	9	16	174400.00	449703.00	624103.00	2026-01-21 06:30:18	\N	\N	\N	\N	\N	\N	\N	77	FAC-20260121-000004	Vente #VNT-20260121-0003		4	2026-01-21 06:30:18	\N
226	9	16	45000.00	624103.00	669103.00	2026-01-21 08:41:38	\N	\N	\N	\N	\N	\N	\N	80	FAC-20260121-000006	Vente #VNT-20260121-0005		4	2026-01-21 08:41:38	\N
227	9	16	7000.00	669103.00	676103.00	2026-01-21 09:26:19	\N	\N	\N	\N	\N	\N	\N	84	FAC-20260121-000006	Vente #VNT-20260121-0007		4	2026-01-21 09:26:19	\N
228	9	16	7000.00	676103.00	683103.00	2026-01-21 09:26:31	\N	\N	\N	\N	\N	\N	\N	85	FAC-20260121-000007	Vente #VNT-20260121-0008		4	2026-01-21 09:26:31	\N
229	9	16	4000.00	683103.00	687103.00	2026-01-21 09:29:08	\N	\N	\N	\N	\N	\N	\N	86	FAC-20260121-000008	Acompte réservation #VNT-20260121-0009		4	2026-01-21 09:29:08	\N
230	7	16	45000.00	27045782.34	27090782.34	2026-01-21 09:42:33	\N	\N	\N	\N	\N	\N	\N	87	FAC-20260121-000009	Vente #VNT-20260121-0010		4	2026-01-21 09:42:33	\N
231	7	16	5000.00	27090782.34	27095782.34	2026-01-21 09:43:53	\N	\N	\N	\N	\N	\N	\N	88	FAC-20260121-000010	Acompte réservation #VNT-20260121-0011		4	2026-01-21 09:43:53	\N
232	7	16	2300.00	27095782.34	27098082.34	2026-01-21 09:48:58	\N	\N	\N	\N	\N	\N	\N	89	FAC-20260121-000011	Acompte réservation #VNT-20260121-0012		4	2026-01-21 09:48:58	\N
233	9	16	116000.00	687103.00	803103.00	2026-01-21 13:42:22	\N	\N	\N	\N	\N	\N	\N	90	FAC-20260121-000012	Vente #VNT-20260121-0013		4	2026-01-21 13:42:22	\N
234	7	16	22000.00	27098082.34	27120082.34	2026-01-21 14:33:24	\N	\N	\N	\N	\N	\N	\N	65	FAC-20260121-000013	Paiement crédit #VNT-20260120-0014 - Échéance #1		4	2026-01-21 14:33:24	\N
235	6	16	45000.00	1497610.00	1542610.00	2026-01-21 14:33:42	\N	\N	\N	\N	\N	\N	\N	65	FAC-20260121-000014	Paiement crédit #VNT-20260120-0014 - Échéance #1		4	2026-01-21 14:33:42	\N
240	7	16	3700.00	27120082.34	27123782.34	2026-01-21 20:49:07	\N	\N	\N	\N	\N	\N	\N	89	FAC-20260121-000015	Paiement final réservation #VNT-20260121-0012		4	2026-01-21 23:49:07.759759	\N
241	9	16	43000.00	803103.00	846103.00	2026-01-22 06:14:35	\N	\N	\N	\N	\N	\N	\N	92	FAC-20260122-000001	Vente #VNT-20260122-0001		4	2026-01-22 09:14:34.821722	\N
\.


--
-- Data for Name: account_types; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.account_types (id, code, name, display_name, description, created_at) FROM stdin;
1	CASH	cash	Espèces	Compte en espèces (liquide)	2025-12-28 11:35:31.68283
2	MOBILE_MONEY	mobile_money	Mobile Money	Compte Mobile Money (MVola, Orange Money, etc.)	2025-12-28 11:35:31.68283
3	BANK	bank	Banque	Compte bancaire	2025-12-28 11:35:31.68283
\.


--
-- Data for Name: accounts; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.accounts (id, account_type_id, name, account_number, initial_balance, current_balance, notes, is_active, created_by, created_at, updated_at) FROM stdin;
8	3	BNI	0000000000000000000000000000000	390000.00	95873.64	\N	t	4	2026-01-14 09:31:35.533215	2026-01-18 18:40:30
6	1	Caisse Principale	\N	4000000.00	1542610.00	\N	t	4	2026-01-14 09:30:29.12405	2026-01-21 14:33:42
7	2	Mvola Shop	0340412233	30000000.00	27123782.34	\N	t	4	2026-01-14 09:31:00.38502	2026-01-21 20:49:07
9	1	CAISSE numero 2	\N	100000.00	846103.00	\N	t	4	2026-01-14 13:37:33.707327	2026-01-22 06:14:35
10	3	BMOI	1526182719289180	4500000.00	402576.00	\N	t	4	2026-01-14 13:39:55.228949	2026-01-20 19:28:32
\.


--
-- Data for Name: attribute_types; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.attribute_types (id, name, display_name, input_type, created_at) FROM stdin;
16	color	couleur	select	2026-01-14 09:24:20.639543
17	pointure	Pointure	number	2026-01-14 09:24:41.363288
18	size	Taille	select	2026-01-14 09:28:55.488978
19	marque	Marque	text	2026-01-20 16:23:27.214977
\.


--
-- Data for Name: attribute_values; Type: TABLE DATA; Schema: public; Owner: -
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
55	18	M	2	2026-01-14 09:28:55.494349
56	18	L	3	2026-01-14 09:28:55.499137
57	18	XL	4	2026-01-14 09:28:55.502697
58	18	XXL	5	2026-01-14 09:28:55.505305
59	17	12	999	2026-01-17 23:27:51.973283
60	17	90	999	2026-01-18 00:57:00.882745
61	17	36	999	2026-01-20 16:24:45.385635
62	17	37	999	2026-01-20 16:28:36.820951
63	17	40	999	2026-01-20 16:29:24.724384
64	17	34	999	2026-01-20 16:44:56.509138
\.


--
-- Data for Name: audit_logs; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.audit_logs (id, user_id, action, table_name, record_id, old_values, new_values, ip_address, user_agent, created_at) FROM stdin;
\.


--
-- Data for Name: cache; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.cache (key, value, expiration) FROM stdin;
laravel-cache-notifications:last_gen	b:1;	1769065253
\.


--
-- Data for Name: cache_locks; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.cache_locks (key, owner, expiration) FROM stdin;
\.


--
-- Data for Name: cash_count_denominations; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.cash_count_denominations (id, cash_count_id, denomination, quantity, subtotal, created_at, updated_at) FROM stdin;
17	1	100	20	2000	2026-01-16 16:10:41	2026-01-16 16:10:41
18	1	200	15	3000	2026-01-16 16:10:41	2026-01-16 16:10:41
19	1	500	10	5000	2026-01-16 16:10:41	2026-01-16 16:10:41
20	1	1000	8	8000	2026-01-16 16:10:41	2026-01-16 16:10:41
21	1	2000	5	10000	2026-01-16 16:10:41	2026-01-16 16:10:41
22	1	5000	3	15000	2026-01-16 16:10:41	2026-01-16 16:10:41
23	1	10000	10	100000	2026-01-16 16:10:41	2026-01-16 16:10:41
24	1	20000	10	200000	2026-01-16 16:10:41	2026-01-16 16:10:41
25	2	20000	12	240000	2026-01-16 16:53:46	2026-01-16 16:53:46
26	2	10000	2	20000	2026-01-16 16:53:46	2026-01-16 16:53:46
27	2	5000	1	5000	2026-01-16 16:53:46	2026-01-16 16:53:46
\.


--
-- Data for Name: cash_counts; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.cash_counts (id, count_date, created_by, notes, created_at, updated_at, total_amount) FROM stdin;
1	2026-01-25	4	Comptage caisse du matin	2026-01-16 11:42:46	2026-01-16 16:10:41	343000
2	2026-01-16	4	\N	2026-01-16 16:53:46	2026-01-16 16:53:46	265000
\.


--
-- Data for Name: categories; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.categories (id, name, description, parent_id, image_url, sort_order, created_at, updated_at) FROM stdin;
21	kiraro	\N	\N	\N	0	2026-01-14 06:22:13	2026-01-14 06:22:13
22	tennis	\N	21	\N	0	2026-01-14 06:22:27	2026-01-14 06:22:27
23	Akanjo	\N	\N	\N	0	2026-01-14 06:27:52	2026-01-14 06:27:52
24	Robe	\N	23	\N	0	2026-01-14 06:28:00	2026-01-14 06:28:00
25	Haut	\N	\N	\N	0	2026-01-14 08:13:51	2026-01-14 08:13:51
26	T-Shirt	\N	25	\N	0	2026-01-14 08:14:14	2026-01-14 08:14:14
27	kapa	\N	21	\N	0	2026-01-20 13:25:33	2026-01-20 13:25:33
28	Bas	tout ce qui est bas comme les pantalons	\N	\N	0	2026-01-20 13:30:47	2026-01-20 13:30:47
29	cargo	\N	28	\N	0	2026-01-20 13:31:01	2026-01-20 13:31:01
30	Pantalon	\N	28	\N	0	2026-01-20 13:34:29	2026-01-20 13:34:29
\.


--
-- Data for Name: company_infos; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.company_infos (name, phone, address, email, logo_path, invoice_signature) FROM stdin;
Express Sale	0320001192	CASIN MALL Behoririka	express_sale@gmail.coma	http://localhost:8000/storage/uploads/images/2026/01/b049b2e3-73ca-47bc-83f5-66fa4c4c67e0.jpg	Fly Higher
\.


--
-- Data for Name: coordinates; Type: TABLE DATA; Schema: public; Owner: -
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
-- Data for Name: credit_installments; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.credit_installments (id, credit_id, installment_number, due_date, amount_due, amount_paid, status, paid_date, created_at) FROM stdin;
30	27	1	2026-02-08	233000.00	0.00	pending	\N	2026-01-21 18:30:46
19	16	1	2026-01-21	705000.00	705000.00	paid	2026-01-20	2026-01-20 16:01:17
21	18	1	2026-01-22	9000.00	9000.00	paid	2026-01-20	2026-01-20 20:05:01
22	19	1	2026-01-22	9000.00	9000.00	paid	2026-01-20	2026-01-20 20:07:22
23	20	1	2026-01-31	6700.00	6700.00	paid	2026-01-20	2026-01-20 20:12:39
24	21	1	2026-01-28	6000.00	6000.00	paid	2026-01-20	2026-01-20 20:21:04
25	22	1	2026-01-21	9700.00	9700.00	paid	2026-01-20	2026-01-20 20:24:58
26	23	1	2026-01-29	4010.00	4010.00	paid	2026-01-20	2026-01-20 20:30:12
27	24	1	2026-01-30	500.00	500.00	paid	2026-01-20	2026-01-20 20:37:34
28	25	1	2026-01-31	174000.00	174000.00	paid	2026-01-21	2026-01-21 06:27:28
29	26	1	2026-01-30	10000.00	0.00	pending	\N	2026-01-21 09:19:44
20	17	1	2026-01-31	114000.00	67000.00	partial	\N	2026-01-20 19:55:40
\.


--
-- Data for Name: credits; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.credits (id, sale_id, customer_id, total_amount, amount_paid, amount_due, credit_date, due_date, status, notes, created_at, updated_at, last_payment_date) FROM stdin;
16	46	8	705000.00	705000.00	0.00	2026-01-20	2026-01-21	completed	\N	2026-01-20 16:01:17	2026-01-20 19:56:33	2026-01-20 19:56:33
18	66	21	9000.00	9000.00	0.00	2026-01-20	2026-01-22	completed	\N	2026-01-20 20:05:01	2026-01-20 20:05:56	2026-01-20 20:05:56
19	67	21	9000.00	9000.00	0.00	2026-01-20	2026-01-22	completed	\N	2026-01-20 20:07:22	2026-01-20 20:07:58	2026-01-20 20:07:58
20	68	21	6700.00	6700.00	0.00	2026-01-20	2026-01-31	completed	\N	2026-01-20 20:12:39	2026-01-20 20:14:07	2026-01-20 20:14:07
21	70	9	6000.00	6000.00	0.00	2026-01-20	2026-01-28	completed	\N	2026-01-20 20:21:04	2026-01-20 20:21:54	2026-01-20 20:21:54
22	72	15	9700.00	9700.00	0.00	2026-01-20	2026-01-21	completed	\N	2026-01-20 20:24:58	2026-01-20 20:25:42	2026-01-20 20:25:42
23	73	14	4010.00	4010.00	0.00	2026-01-20	2026-01-29	completed	\N	2026-01-20 20:30:12	2026-01-20 20:32:30	2026-01-20 20:32:30
24	74	14	500.00	500.00	0.00	2026-01-20	2026-01-30	completed	\N	2026-01-20 20:37:34	2026-01-20 20:37:54	2026-01-20 20:37:54
25	76	21	174000.00	174000.00	0.00	2026-01-21	2026-01-31	completed	\N	2026-01-21 06:27:28	2026-01-21 06:28:00	2026-01-21 06:28:00
26	83	15	10000.00	0.00	10000.00	2026-01-21	2026-01-30	active	\N	2026-01-21 09:19:44	2026-01-21 09:19:44	\N
17	65	21	114000.00	67000.00	47000.00	2026-01-20	2026-01-31	partial_paid	\N	2026-01-20 19:55:40	2026-01-21 14:33:42	2026-01-21 14:33:42
27	91	34	233000.00	0.00	233000.00	2026-01-21	2026-02-08	active	\N	2026-01-21 18:30:46	2026-01-21 18:30:46	\N
\.


--
-- Data for Name: currency_rates; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.currency_rates (id, euro_rate, yuan_rate, dollar_rate, dirham_rate, effective_date, notes, created_by, created_at, updated_at, baht_rate, is_active) FROM stdin;
7	5200.0000	1200.0000	5200.0000	0.1000	2026-01-10	\N	4	2026-01-10 17:05:19	2026-01-14 10:44:17	145.0000	f
5	0.0204	3.1000	0.0201	0.1000	2025-12-28	Taux du 15 janvier 2025	4	2025-12-28 12:07:35	2026-01-14 10:44:17	0.1201	f
6	5000.0000	12000.0000	1022.0000	0.1000	2026-01-28	milay  eh	4	2026-01-01 10:32:48	2026-01-14 10:44:17	12000.0000	f
9	5200.0000	700.0000	4800.0000	0.1000	2026-01-14	\N	4	2026-01-14 08:22:06	2026-01-14 10:44:17	140.0000	f
8	5100.0000	645.0000	4578.0000	0.1000	2026-01-12	\N	4	2026-01-10 17:29:38	2026-01-14 10:44:17	135.0000	t
\.


--
-- Data for Name: customers; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.customers (id, name, phone, address, reliability_score, loyalty_points, credit_limit, notes, is_active, created_at, updated_at, customer_number, is_extra_customer) FROM stdin;
28	Anonyme	\N	\N	5.00	45	100000.00	[2026-01-21 08:41] +45 points: Achat de 45000 Ar	t	2026-01-21 08:41:38	2026-01-21 12:01:42.664311	CL-20260121-0006	f
14	chrichri	\N	\N	7.80	120	0.00	[2026-01-19 10:55] +10 points: Achat de 10002 Ar\n[2026-01-20 18:44] +97 points: Achat de 97700 Ar\n[2026-01-21 09:26] +7 points: Achat de 7000 Ar\n[2026-01-21 20:49] +6 points: Achat de 6000 Ar	t	2026-01-19 10:52:52	2026-01-21 23:49:07.759759	CL-20260119-0001	t
24	Anonyme	\N	\N	5.00	0	100000.00	\N	t	2026-01-21 08:22:51	2026-01-21 08:22:51	CL-20260121-0002	f
25	Anonyme	\N	\N	5.00	0	100000.00	\N	t	2026-01-21 08:29:01	2026-01-21 08:29:01	CL-20260121-0003	f
26	Anonyme	\N	\N	5.00	0	100000.00	\N	t	2026-01-21 08:33:59	2026-01-21 08:33:59	CL-20260121-0004	f
27	Anonyme	\N	\N	5.00	0	100000.00	\N	t	2026-01-21 08:35:37	2026-01-21 08:35:37	CL-20260121-0005	f
10	Dupont	\N	\N	5.00	0	0.00	\N	t	2026-01-14 06:41:35	2026-01-21 11:51:26.295296	CL-20260114-0004	t
11	aaa	\N	\N	5.00	0	0.00	\N	t	2026-01-14 10:01:27	2026-01-21 11:51:26.295296	CL-20260114-0005	t
15	chris	\N	\N	8.00	649	0.00	[2026-01-19 11:39] +30 points: Achat de 30000 Ar\n[2026-01-20 15:55] +70 points: Achat de 70000 Ar\n[2026-01-20 18:35] +197 points: Achat de 197000 Ar\n[2026-01-20 18:42] +196 points: Achat de 196000 Ar\n[2026-01-20 19:35] +98 points: Achat de 98500 Ar\n[2026-01-21 06:25] +58 points: Achat de 58000 Ar	t	2026-01-19 10:59:18	2026-01-21 11:51:26.295296	CL-20260119-0002	t
22	chrichri	\N	\N	5.00	174	0.00	[2026-01-21 06:30] +174 points: Achat de 174400 Ar	t	2026-01-21 06:30:07	2026-01-21 11:51:26.295296	CL-20260121-0001	t
7	Jean Dupont	\N	\N	5.00	639	0.00	[2026-01-14 06:38] +215 points: Achat de 215000 Ar\n[2026-01-14 10:32] +319 points: Achat de 319000 Ar\n[2026-01-19 11:08] +30 points: Achat de 30000 Ar\n[2026-01-20 19:45] +75 points: Achat de 75000 Ar	t	2026-01-14 06:38:27	2026-01-21 11:51:26.295296	CL-20260114-0001	t
13	vaovao	\N	\N	5.00	0	0.00	\N	t	2026-01-16 06:49:56	2026-01-21 11:51:26.295296	CL-20260116-0001	t
8	Jean Bas	\N	\N	8.00	1069	190000.00	[2026-01-14 10:17] +760 points: Achat de 760000 Ar\n[2026-01-20 18:24] +295 points: Achat de 295500 Ar\n[2026-01-20 19:40] +14 points: Achat de 14000 Ar	t	2026-01-14 06:38:51	2026-01-21 11:51:26.295296	CL-20260114-0002	t
21	christian	\N	\N	8.00	0	0.00	\N	t	2026-01-20 19:55:34	2026-01-21 11:51:26.295296	CL-20260120-0002	t
16	here	\N	\N	8.00	0	999000.00	\N	t	2026-01-19 11:10:43	2026-01-21 11:51:26.295296	CL-20260119-0003	t
17	hello	\N	\N	5.00	0	0.00	\N	t	2026-01-19 11:41:22	2026-01-21 11:51:26.295296	CL-20260119-0004	t
18	hello	\N	\N	8.00	0	9999000.00	\N	t	2026-01-19 11:41:50	2026-01-21 11:51:26.295296	CL-20260119-0005	t
9	hafa mihitsy	\N	\N	8.00	837	10000000.00	[2026-01-19 10:51] +617 points: Achat de 617970 Ar\n[2026-01-20 19:48] +86 points: Achat de 86000 Ar\n[2026-01-20 20:20] +44 points: Achat de 44977 Ar\n[2026-01-20 20:23] +90 points: Achat de 90000 Ar	t	2026-01-14 06:39:59	2026-01-21 11:51:26.295296	CL-20260114-0003	t
12	Marc	\N	\N	4.00	0	2000000000.00	\N	t	2026-01-14 10:23:22	2026-01-21 11:51:26.295296	CL-20260114-0006	t
19	ok	\N	\N	8.00	0	0.00	\N	t	2026-01-19 11:49:13	2026-01-21 11:51:26.295296	CL-20260119-0006	t
20	whoami	\N	\N	5.00	40	0.00	[2026-01-20 15:51] +40 points: Achat de 40000 Ar	t	2026-01-20 15:51:49	2026-01-21 11:51:26.295296	CL-20260120-0001	t
29	christian	+261340425089	Lot 102 ter P Mahity	5.00	0	0.00	\N	t	2026-01-21 09:09:09	2026-01-21 09:09:09	CL-20260121-0007	t
30	christianHerimanantsoa h	\N	\N	5.00	0	0.00	\N	t	2026-01-21 09:16:57	2026-01-21 09:16:57	CL-20260121-0008	t
31	Anonyme	\N	\N	5.00	7	100000.00	[2026-01-21 09:26] +7 points: Achat de 7000 Ar	t	2026-01-21 09:26:31	2026-01-21 12:26:31.365979	CL-20260121-0009	f
32	Anonyme	\N	\N	5.00	45	100000.00	[2026-01-21 09:42] +45 points: Achat de 45000 Ar	t	2026-01-21 09:42:33	2026-01-21 12:42:33.356796	CL-20260121-0010	f
33	Anonyme	\N	\N	5.00	116	100000.00	[2026-01-21 13:42] +116 points: Achat de 116000 Ar	t	2026-01-21 13:42:21	2026-01-21 16:42:22.061473	CL-20260121-0011	f
34	Herimanantsoa	\N	\N	5.00	0	0.00	\N	t	2026-01-21 18:30:42	2026-01-21 18:30:42	CL-20260121-0012	t
35	Anonyme	\N	\N	5.00	43	100000.00	[2026-01-22 06:14] +43 points: Achat de 43000 Ar	t	2026-01-22 06:14:34	2026-01-22 09:14:34.821722	CL-20260122-0001	f
\.


--
-- Data for Name: expense_categories; Type: TABLE DATA; Schema: public; Owner: -
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
17	Melinda	\N	truck	t	2026-01-16 18:29:14	2026-01-16 18:29:14
18	Livraison à domicile	\N	truck	t	2026-01-18 13:52:14	2026-01-18 13:52:14
\.


--
-- Data for Name: failed_jobs; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.failed_jobs (id, uuid, connection, queue, payload, exception, failed_at) FROM stdin;
\.


--
-- Data for Name: freight_forwarders; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.freight_forwarders (id, name, logo_url, contact, service_score, notes, is_active, created_at, updated_at, coordinate_id, total_shipments, total_items_shipped, total_items_delivered, service_rating_sum, service_rating_count, total_value_shipped, total_value_delivered, weighted_service_sum, total_weighted_shipment_value, type) FROM stdin;
3	Madagascar Airlines	\N	909289892891	10.00	\N	t	2026-01-18 18:37:47	2026-01-20 15:47:47	6	4	0	0	0.00	0	1595175.00	1569675.00	15696750.00	1569675.00	\N
2	htm logistics	uploads/images/2026/01/f4885185-0d31-4dff-bd6c-417c3fa1b845.png	+972918991	10.00	\N	t	2026-01-14 06:32:46	2026-01-20 19:32:23	6	8	0	0	0.00	0	3886770.00	3779670.00	37796700.00	3779670.00	\N
\.


--
-- Data for Name: installment_transactions; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.installment_transactions (id, installment_id, transaction_id, amount, payment_date, created_at, updated_at) FROM stdin;
19	19	210	705000.00	2026-01-20 19:56:33	2026-01-20 19:56:33	2026-01-20 19:56:33
20	21	211	9000.00	2026-01-20 20:05:56	2026-01-20 20:05:56	2026-01-20 20:05:56
21	22	212	9000.00	2026-01-20 20:07:58	2026-01-20 20:07:58	2026-01-20 20:07:58
22	23	213	6700.00	2026-01-20 20:14:07	2026-01-20 20:14:07	2026-01-20 20:14:07
23	24	215	6000.00	2026-01-20 20:21:54	2026-01-20 20:21:54	2026-01-20 20:21:54
24	25	217	9700.00	2026-01-20 20:25:42	2026-01-20 20:25:42	2026-01-20 20:25:42
25	26	218	4010.00	2026-01-20 20:32:31	2026-01-20 20:32:31	2026-01-20 20:32:31
26	27	219	500.00	2026-01-20 20:37:54	2026-01-20 20:37:54	2026-01-20 20:37:54
27	28	222	174000.00	2026-01-21 06:28:00	2026-01-21 06:28:00	2026-01-21 06:28:00
28	20	234	22000.00	2026-01-21 14:33:24	2026-01-21 14:33:24	2026-01-21 14:33:24
29	20	235	45000.00	2026-01-21 14:33:42	2026-01-21 14:33:42	2026-01-21 14:33:42
\.


--
-- Data for Name: job_batches; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.job_batches (id, name, total_jobs, pending_jobs, failed_jobs, failed_job_ids, options, cancelled_at, created_at, finished_at) FROM stdin;
\.


--
-- Data for Name: jobs; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.jobs (id, queue, payload, attempts, reserved_at, available_at, created_at) FROM stdin;
\.


--
-- Data for Name: locations; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.locations (id, name, code, warehouse, aisle, shelf, bin, description, capacity, is_active, created_at, updated_at) FROM stdin;
10	Entrepôt principal	ENTREPÔT-PRINCIPAL	Entrepot	\N	\N	\N	\N	\N	t	2026-01-20 13:05:11	2026-01-20 13:05:11
11	Magasin principal	MAGASIN-PRINCIPAL	MAGASIN PRINCIPAL	\N	\N	\N	\N	\N	t	2026-01-20 13:06:14	2026-01-20 13:06:14
\.


--
-- Data for Name: migrations; Type: TABLE DATA; Schema: public; Owner: -
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
\.


--
-- Data for Name: notification_preferences; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.notification_preferences (id, user_id, notification_type, reminder_interval_days, enabled, created_at, updated_at) FROM stdin;
1	4	stock_low	1	t	2026-01-19 17:16:29.368518	2026-01-19 17:16:29.368518
2	4	stock_out	1	t	2026-01-19 17:16:29.368518	2026-01-19 17:16:29.368518
4	4	credit_due	7	t	2026-01-19 17:16:29.368518	2026-01-19 17:16:29.368518
3	4	reservation_expiring	1	t	2026-01-19 17:16:29.368518	2026-01-20 06:43:38
\.


--
-- Data for Name: notifications; Type: TABLE DATA; Schema: public; Owner: -
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
38	4	stock_low	Stock faible	Le produit "Air Jordan" (AIR--0ICY) a un stock faible : 4 unités (seuil: 5)	t	2026-01-21 19:37:56	2026-01-21 22:09:57.648957	warning	{"sku": "AIR--0ICY", "threshold": 5, "product_id": 36, "variant_id": 44, "stock_quantity": 4}	stock_low_44	\N
39	4	stock_low	Stock faible	Le produit "Air Jordan" (AIR--3SXC) a un stock faible : 1 unités (seuil: 5)	t	2026-01-21 19:37:56	2026-01-21 22:09:57.655784	warning	{"sku": "AIR--3SXC", "threshold": 5, "product_id": 36, "variant_id": 43, "stock_quantity": 1}	stock_low_43	\N
42	4	stock_out	Stock épuisé	Le produit "Robe fitness" (ROB--BSBM) est en rupture de stock !	t	2026-01-22 06:14:55	2026-01-22 08:00:17.652796	critical	{"sku": "ROB--BSBM", "product_id": 32, "variant_id": 37}	stock_out_37	\N
41	4	stock_out	Stock épuisé	Le produit "Pointillé" (POI--QAZR) est en rupture de stock !	t	2026-01-22 06:14:56	2026-01-22 08:00:17.649424	critical	{"sku": "POI--QAZR", "product_id": 33, "variant_id": 39}	stock_out_39	\N
40	4	stock_low	Stock faible	Le produit "Air Jordan" (AIR--2DMS) a un stock faible : 1 unités (seuil: 5)	t	2026-01-22 06:14:57	2026-01-22 08:00:17.636519	warning	{"sku": "AIR--2DMS", "threshold": 5, "product_id": 36, "variant_id": 42, "stock_quantity": 1}	stock_low_42	\N
\.


--
-- Data for Name: password_reset_tokens; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.password_reset_tokens (username, token, created_at) FROM stdin;
\.


--
-- Data for Name: personal_access_tokens; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.personal_access_tokens (id, tokenable_type, tokenable_id, name, token, abilities, last_used_at, expires_at, created_at, updated_at) FROM stdin;
2	App\\Models\\User	2	express-sale-token	d8a9de02341ed9b1102651e97a91318373a401751aef6f95d5e2ee39f0673158	["*"]	\N	\N	2025-12-19 13:01:47	2025-12-19 13:01:47
54	App\\Models\\User	4	express-sale-token	923ffb829f97f0c60c77bdc515ddaef2d58c54751d80d9739d91c8d2d972bf21	["*"]	2026-01-22 06:40:53	\N	2026-01-21 10:58:14	2026-01-22 06:40:53
\.


--
-- Data for Name: product_attributes; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.product_attributes (id, product_id, attribute_type_id, is_required, created_at) FROM stdin;
55	29	16	t	2026-01-20 16:31:24.571039
56	29	18	t	2026-01-20 16:31:24.571039
57	30	16	t	2026-01-20 16:34:54.319187
58	30	18	t	2026-01-20 16:34:54.319187
59	31	16	t	2026-01-20 16:35:58.335483
60	31	18	t	2026-01-20 16:35:58.335483
61	32	16	t	2026-01-20 16:37:35.499958
62	32	18	t	2026-01-20 16:37:35.499958
63	33	16	t	2026-01-20 16:40:00.23657
64	33	18	t	2026-01-20 16:40:00.23657
65	34	16	t	2026-01-20 16:41:28.861233
66	34	18	t	2026-01-20 16:41:28.861233
67	35	16	t	2026-01-20 16:42:16.716113
68	35	18	t	2026-01-20 16:42:16.716113
69	36	16	t	2026-01-20 16:44:08.21881
71	36	17	t	2026-01-20 16:44:43.892683
49	26	16	t	2026-01-20 16:23:55.029257
50	26	17	t	2026-01-20 16:23:55.029257
51	27	16	t	2026-01-20 16:26:09.615121
52	27	17	t	2026-01-20 16:26:09.615121
53	28	16	t	2026-01-20 16:27:34.823453
54	28	17	t	2026-01-20 16:27:34.823453
\.


--
-- Data for Name: product_variant_locations; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.product_variant_locations (id, variant_id, location_id, quantity, notes, created_at, updated_at, reserved_quantity) FROM stdin;
61	37	10	0	\N	2026-01-20 19:32:23	2026-01-20 19:33:16	0
56	35	10	9	\N	2026-01-20 19:32:23	2026-01-21 13:41:35	0
55	36	10	2	\N	2026-01-20 19:32:23	2026-01-21 13:41:35	0
68	36	11	6	\N	2026-01-20 19:33:16	2026-01-21 13:41:35	0
49	42	10	0	\N	2026-01-20 15:47:46	2026-01-21 13:41:35	0
45	43	10	1	\N	2026-01-20 15:47:46	2026-01-21 13:41:35	0
48	44	10	2	\N	2026-01-20 15:47:46	2026-01-21 13:41:35	0
57	34	10	1	\N	2026-01-20 19:32:23	2026-01-21 13:41:35	0
63	34	11	6	\N	2026-01-20 19:33:16	2026-01-21 13:41:35	0
58	33	10	2	\N	2026-01-20 19:32:23	2026-01-21 13:41:35	0
65	33	11	5	\N	2026-01-20 19:33:16	2026-01-21 13:41:35	0
64	35	11	6	\N	2026-01-20 19:33:16	2026-01-21 13:42:22	3
47	41	10	0	\N	2026-01-20 15:47:46	2026-01-20 15:50:29	0
66	39	11	0	\N	2026-01-20 19:33:16	2026-01-21 13:42:22	0
54	43	11	0	\N	2026-01-20 15:50:29	2026-01-21 18:30:46	0
67	38	11	0	\N	2026-01-20 19:33:16	2026-01-21 18:30:46	0
50	42	11	1	\N	2026-01-20 15:50:29	2026-01-21 18:30:46	0
59	39	10	0	\N	2026-01-20 19:32:23	2026-01-21 20:48:47	0
60	38	10	4	\N	2026-01-20 19:32:23	2026-01-21 20:49:07	0
51	44	11	1	\N	2026-01-20 15:50:29	2026-01-22 06:14:34	0
53	40	11	6	\N	2026-01-20 15:50:29	2026-01-20 18:43:54	6
46	40	10	-6	\N	2026-01-20 15:47:46	2026-01-20 18:44:55	-6
52	41	11	0	\N	2026-01-20 15:50:29	2026-01-21 06:25:01	0
62	37	11	0	\N	2026-01-20 19:33:16	2026-01-21 08:29:01	0
\.


--
-- Data for Name: product_variants; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.product_variants (id, product_id, sku, price_adjustment, stock_quantity, reserved_quantity, low_stock_threshold, is_active, created_at, updated_at, image_path, credit_quantity, available_quantity) FROM stdin;
39	33	POI--QAZR	0.00	0	0	5	t	2026-01-20 13:40:17	2026-01-21 23:48:47.214621	\N	-2	0
38	32	ROB--9XLS	0.00	4	0	5	t	2026-01-20 13:38:31	2026-01-21 23:49:07.759759	\N	0	4
44	36	AIR--0ICY	0.00	3	0	5	t	2026-01-20 14:39:13	2026-01-22 09:14:34.821722	\N	3	3
37	32	ROB--BSBM	0.00	0	0	5	t	2026-01-20 13:38:16	2026-01-21 11:29:01.454994	\N	4	0
36	31	JEA--IAUX	0.00	8	0	5	t	2026-01-20 13:36:25	2026-01-21 16:41:35.536167	\N	0	8
34	29	NOI--OIG0	0.00	7	0	5	t	2026-01-20 13:32:17	2026-01-21 16:41:35.536167	\N	0	7
40	35	LAB--3NOC	0.00	0	0	5	t	2026-01-20 13:42:44	2026-01-20 23:33:00.267519	\N	0	0
28	26	STA--Y0JA	0.00	0	0	5	t	2026-01-20 13:24:18	2026-01-20 23:33:00.267519	\N	0	0
29	26	STA--R2BC	0.00	0	0	5	t	2026-01-20 13:24:45	2026-01-20 23:33:00.267519	\N	0	0
33	29	NOI--4FTG	0.00	7	0	5	t	2026-01-20 13:32:05	2026-01-21 16:41:35.536167	\N	0	7
35	31	JEA--JOKS	0.00	15	3	5	t	2026-01-20 13:36:15	2026-01-21 16:42:22.061473	\N	0	12
42	36	AIR--2DMS	0.00	1	0	5	t	2026-01-20 13:44:56	2026-01-21 21:30:46.010181	\N	-8	1
30	27	SAN--Y1M5	0.00	0	0	5	t	2026-01-20 13:28:10	2026-01-20 23:33:00.267519	\N	0	0
31	27	SAN--IZTF	0.00	0	0	5	t	2026-01-20 13:28:36	2026-01-20 23:33:00.267519	\N	0	0
32	27	SAN--9QG8	0.00	0	0	5	t	2026-01-20 13:29:04	2026-01-20 23:33:00.267519	\N	0	0
41	35	LAB--SINC	0.00	0	0	5	t	2026-01-20 13:42:56	2026-01-21 09:25:01.075693	\N	0	0
43	36	AIR--3SXC	0.00	1	0	5	t	2026-01-20 13:45:10	2026-01-21 21:30:46.010181	\N	0	1
\.


--
-- Data for Name: products; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.products (id, name, description, category_id, subcategory_id, base_price, is_active, created_at, updated_at, image_url) FROM stdin;
26	Stan Smith	Stan Smith amle tsara be mihitsy	21	22	66000.00	t	2026-01-20 13:23:55	2026-01-20 13:23:55	uploads/images/2026/01/72e7dfd2-1f00-4f80-9310-00d5c8dc66b7.jpeg
27	Sandale Adidas	\N	21	27	90000.00	t	2026-01-20 13:26:09	2026-01-20 13:26:09	uploads/images/2026/01/080d0ec7-ec49-4af2-b29b-2848e4ea75a5.jpeg
28	SUPERSTAR Adidas	\N	21	22	50000.00	t	2026-01-20 13:27:34	2026-01-20 13:27:34	uploads/images/2026/01/02d43283-5152-4504-ac8b-2854ce7c6847.jpg
30	Jean fitness	\N	28	30	20000.00	t	2026-01-20 13:34:54	2026-01-20 13:34:54	uploads/images/2026/01/c21574d8-bcdc-429a-8817-28c3d16b4d33.jpeg
34	Prada T-shirt	\N	25	26	50000.00	t	2026-01-20 13:41:28	2026-01-20 13:41:28	uploads/images/2026/01/4506039c-90c1-4bd0-b983-77516097484b.jpeg
35	LABUBU	\N	25	26	50000.00	t	2026-01-20 13:42:16	2026-01-20 13:42:16	uploads/images/2026/01/09402a10-dacc-4a7f-a253-bf3c9e9ab649.jpeg
29	Noir kely	\N	28	29	9000.00	t	2026-01-20 13:31:24	2026-01-20 22:32:12.003911	http://localhost:8000/storage/uploads/images/2026/01/af8e4ac3-5104-43f5-a590-c84354dfd044.jpeg
31	Jean Malalaka	\N	28	30	7000.00	t	2026-01-20 13:35:58	2026-01-20 22:32:12.003911	uploads/images/2026/01/62aa3c82-a59a-4a1b-8d9c-a4991a71c1b6.jpeg
32	Robe fitness	\N	23	24	6000.00	t	2026-01-20 13:37:35	2026-01-20 22:32:12.003911	uploads/images/2026/01/fbbe2446-af56-4800-afb7-c98ba6d3383b.jpeg
33	Pointillé	\N	23	24	5000.00	t	2026-01-20 13:40:00	2026-01-20 22:32:12.003911	uploads/images/2026/01/31531b77-cf49-4614-acae-c0d0513cae78.jpeg
36	Air Jordan	\N	21	22	45000.00	t	2026-01-20 13:44:08	2026-01-20 22:32:12.003911	http://localhost:8000/storage/uploads/images/2026/01/e42e1fac-3518-4d9e-ab07-3494152f9e10.jpeg
\.


--
-- Data for Name: reservation_deposits; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.reservation_deposits (id, reservation_id, transaction_id, amount, payment_date, notes, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: reservations; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.reservations (id, sale_id, customer_id, reservation_date, expiry_date, total_amount, deposit_amount, remaining_amount, status, cancellation_reason, completed_at, created_at, updated_at, transaction_complete_id) FROM stdin;
20	61	7	2026-01-20 19:41:55	2026-01-22 00:00:00	180000.00	80000.00	100000.00	cancelled	TSA CL2	\N	2026-01-20 19:41:55	2026-01-20 19:42:57	\N
22	64	7	2026-01-20 19:53:18	2026-01-24 00:00:00	112000.00	20000.00	92000.00	cancelled	tsy cle	\N	2026-01-20 19:53:18	2026-01-20 19:54:44	\N
24	86	21	2026-01-21 09:29:08	2026-01-25 00:00:00	20000.00	4000.00	16000.00	confirmed	\N	\N	2026-01-21 09:29:08	2026-01-21 09:29:08	\N
17	56	15	2026-01-20 18:26:11	2026-01-31 00:00:00	197000.00	197000.00	0.00	completed	\N	2026-01-20 18:35:41	2026-01-20 18:26:11	2026-01-20 18:35:41	195
18	58	14	2026-01-20 18:43:54	2026-01-21 00:00:00	97700.00	97700.00	0.00	completed	\N	2026-01-20 18:44:55	2026-01-20 18:43:54	2026-01-20 18:44:55	198
19	60	8	2026-01-20 19:39:21	2026-01-28 00:00:00	14000.00	14000.00	0.00	completed	\N	2026-01-20 19:40:13	2026-01-20 19:39:21	2026-01-20 19:40:13	204
21	63	9	2026-01-20 19:47:31	2026-01-22 00:00:00	86000.00	86000.00	0.00	completed	\N	2026-01-20 19:48:13	2026-01-20 19:47:31	2026-01-20 19:48:13	208
23	75	15	2026-01-21 06:23:23	2026-12-31 00:00:00	58000.00	58000.00	0.00	completed	\N	2026-01-21 06:25:01	2026-01-21 06:23:23	2026-01-21 06:25:01	221
25	88	14	2026-01-21 09:43:53	2026-01-30 00:00:00	10000.00	5000.00	5000.00	cancelled	tsy	\N	2026-01-21 09:43:53	2026-01-21 20:48:47	\N
26	89	14	2026-01-21 09:48:58	2026-01-28 00:00:00	6000.00	6000.00	0.00	completed	\N	2026-01-21 20:49:07	2026-01-21 09:48:58	2026-01-21 20:49:07	240
\.


--
-- Data for Name: sale_item_batches; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.sale_item_batches (id, sale_item_id, batch_id, quantity, unit_price_at_sale, created_at, updated_at, discount_at_sale, status, location_id) FROM stdin;
1	67	28	1	50000.00	2026-01-20 15:51:53	2026-01-20 15:51:53	10000	sold	\N
2	68	30	1	100000.00	2026-01-20 15:55:53	2026-01-20 15:55:53	53333.33	sold	\N
3	69	28	1	50000.00	2026-01-20 15:55:53	2026-01-20 15:55:53	26666.67	sold	\N
4	70	28	5	50000.00	2026-01-20 16:01:17	2026-01-20 16:01:17	15000	sold	\N
5	71	26	5	100000.00	2026-01-20 16:01:17	2026-01-20 16:01:17	30000	sold	\N
11	81	27	2	50000.00	2026-01-20 18:24:55	2026-01-20 18:24:55	1500	sold	\N
12	82	30	2	100000.00	2026-01-20 18:24:55	2026-01-20 18:24:55	3000	sold	\N
13	83	27	4	50000.00	2026-01-20 18:26:11	2026-01-20 18:35:41	0	sold	\N
14	84	27	4	50000.00	2026-01-20 18:42:13	2026-01-20 18:42:13	4000	sold	\N
15	85	27	2	50000.00	2026-01-20 18:43:54	2026-01-20 18:44:55	0	sold	\N
16	86	29	1	45000.00	2026-01-20 19:35:01	2026-01-20 19:35:01	1544.12	sold	10
17	87	34	1	7000.00	2026-01-20 19:35:01	2026-01-20 19:35:01	240.2	sold	10
18	88	28	1	50000.00	2026-01-20 19:35:01	2026-01-20 19:35:01	1715.68	sold	11
19	89	39	3	6000.00	2026-01-20 19:39:21	2026-01-20 19:40:13	1333.33	sold	11
20	90	30	4	45000.00	2026-01-20 19:41:55	2026-01-20 19:42:57	0	cancelled	11
21	91	26	3	45000.00	2026-01-20 19:45:59	2026-01-20 19:45:59	20000	sold	11
22	92	29	2	45000.00	2026-01-20 19:47:31	2026-01-20 19:48:13	2000	sold	11
23	93	31	3	45000.00	2026-01-20 19:53:18	2026-01-20 19:54:44	7666.67	cancelled	11
24	94	40	4	6000.00	2026-01-20 19:55:40	2026-01-20 19:55:40	1698.11	sold	11
25	95	31	3	45000.00	2026-01-20 19:55:40	2026-01-20 19:55:40	12735.85	sold	11
26	96	37	1	9000.00	2026-01-20 20:05:01	2026-01-20 20:05:01	0	sold	11
27	97	36	1	9000.00	2026-01-20 20:07:22	2026-01-20 20:07:22	0	sold	10
28	98	34	1	7000.00	2026-01-20 20:12:39	2026-01-20 20:12:39	300	sold	10
29	99	26	1	45000.00	2026-01-20 20:20:20	2026-01-20 20:20:20	23	sold	11
30	100	39	1	6000.00	2026-01-20 20:21:04	2026-01-20 20:21:04	0	sold	11
31	101	31	2	45000.00	2026-01-20 20:23:46	2026-01-20 20:23:46	0	sold	11
32	102	38	2	5000.00	2026-01-20 20:24:57	2026-01-20 20:24:57	150	sold	11
33	103	38	1	5000.00	2026-01-20 20:30:12	2026-01-20 20:30:12	990	sold	11
34	104	38	1	5000.00	2026-01-20 20:37:33	2026-01-20 20:37:33	4500	sold	10
35	105	36	1	9000.00	2026-01-21 06:23:22	2026-01-21 06:25:01	152.54	sold	11
36	106	28	1	50000.00	2026-01-21 06:23:23	2026-01-21 06:25:01	847.46	sold	11
37	107	30	4	45000.00	2026-01-21 06:27:28	2026-01-21 06:27:28	1500	sold	11
38	108	33	4	45000.00	2026-01-21 06:30:18	2026-01-21 06:30:18	1400	sold	11
41	111	31	1	45000.00	2026-01-21 08:41:38	2026-01-21 08:41:38	0	sold	11
42	112	38	2	5000.00	2026-01-21 09:19:44	2026-01-21 09:19:44	0	sold	10
43	113	35	1	7000.00	2026-01-21 09:26:19	2026-01-21 09:26:19	0	sold	11
44	114	35	1	7000.00	2026-01-21 09:26:31	2026-01-21 09:26:31	0	sold	11
45	115	35	3	7000.00	2026-01-21 09:29:08	2026-01-21 09:29:08	333.33	reserved	11
46	116	26	1	45000.00	2026-01-21 09:42:33	2026-01-21 09:42:33	0	sold	11
49	119	32	2	45000.00	2026-01-21 13:42:22	2026-01-21 13:42:22	1859.51	sold	11
50	120	35	3	7000.00	2026-01-21 13:42:22	2026-01-21 13:42:22	289.26	sold	11
51	121	38	2	5000.00	2026-01-21 13:42:22	2026-01-21 13:42:22	206.61	sold	11
52	122	32	2	45000.00	2026-01-21 18:30:46	2026-01-21 18:30:46	759.5	sold	11
53	123	39	2	6000.00	2026-01-21 18:30:46	2026-01-21 18:30:46	101.27	sold	11
54	124	33	3	45000.00	2026-01-21 18:30:46	2026-01-21 18:30:46	759.49	sold	11
47	117	38	2	5000.00	2026-01-21 09:43:53	2026-01-21 20:48:47	0	cancelled	10
48	118	39	1	6000.00	2026-01-21 09:48:58	2026-01-21 20:49:07	0	sold	10
55	125	31	1	45000.00	2026-01-22 06:14:34	2026-01-22 06:14:34	2000	sold	11
\.


--
-- Data for Name: sale_items; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.sale_items (id, sale_id, variant_id, quantity, unit_price, subtotal, created_at) FROM stdin;
67	44	41	1	50000.00	50000.00	2026-01-20 18:51:53.328414
68	45	42	1	100000.00	100000.00	2026-01-20 18:55:53.050017
69	45	41	1	50000.00	50000.00	2026-01-20 18:55:53.050017
70	46	41	5	50000.00	250000.00	2026-01-20 19:01:17.498715
71	46	43	5	100000.00	500000.00	2026-01-20 19:01:17.498715
81	55	40	2	50000.00	100000.00	2026-01-20 21:24:55.633955
82	55	42	2	100000.00	200000.00	2026-01-20 21:24:55.633955
83	56	40	4	50000.00	200000.00	2026-01-20 21:26:11.869662
84	57	40	4	50000.00	200000.00	2026-01-20 21:42:13.25654
85	58	40	2	50000.00	100000.00	2026-01-20 21:43:54.534708
86	59	44	1	45000.00	45000.00	2026-01-20 22:35:01.631043
87	59	36	1	7000.00	7000.00	2026-01-20 22:35:01.631043
88	59	41	1	50000.00	50000.00	2026-01-20 22:35:01.631043
89	60	38	3	6000.00	18000.00	2026-01-20 22:39:21.534926
90	61	42	4	45000.00	180000.00	2026-01-20 22:41:55.597332
91	62	43	3	45000.00	135000.00	2026-01-20 22:45:59.30056
92	63	44	2	45000.00	90000.00	2026-01-20 22:47:31.873677
93	64	44	3	45000.00	135000.00	2026-01-20 22:53:18.490593
94	65	37	4	6000.00	24000.00	2026-01-20 22:55:40.025815
95	65	44	3	45000.00	135000.00	2026-01-20 22:55:40.025815
96	66	33	1	9000.00	9000.00	2026-01-20 23:05:01.233211
97	67	34	1	9000.00	9000.00	2026-01-20 23:07:22.268739
98	68	36	1	7000.00	7000.00	2026-01-20 23:12:39.832117
99	69	43	1	45000.00	45000.00	2026-01-20 23:20:20.291058
100	70	38	1	6000.00	6000.00	2026-01-20 23:21:04.15829
101	71	44	2	45000.00	90000.00	2026-01-20 23:23:46.518536
102	72	39	2	5000.00	10000.00	2026-01-20 23:24:57.962032
103	73	39	1	5000.00	5000.00	2026-01-20 23:30:12.163408
104	74	39	1	5000.00	5000.00	2026-01-20 23:37:33.906708
105	75	34	1	9000.00	9000.00	2026-01-21 09:23:22.748841
106	75	41	1	50000.00	50000.00	2026-01-21 09:23:22.748841
107	76	42	4	45000.00	180000.00	2026-01-21 09:27:28.641945
108	77	42	4	45000.00	180000.00	2026-01-21 09:30:18.384023
111	80	44	1	45000.00	45000.00	2026-01-21 11:41:38.429608
112	83	39	2	5000.00	10000.00	2026-01-21 12:19:44.824205
113	84	35	1	7000.00	7000.00	2026-01-21 12:26:19.036698
114	85	35	1	7000.00	7000.00	2026-01-21 12:26:31.365979
115	86	35	3	7000.00	21000.00	2026-01-21 12:29:08.151161
116	87	43	1	45000.00	45000.00	2026-01-21 12:42:33.356796
117	88	39	2	5000.00	10000.00	2026-01-21 12:43:53.358146
118	89	38	1	6000.00	6000.00	2026-01-21 12:48:58.404869
119	90	43	2	45000.00	90000.00	2026-01-21 16:42:22.061473
120	90	35	3	7000.00	21000.00	2026-01-21 16:42:22.061473
121	90	39	2	5000.00	10000.00	2026-01-21 16:42:22.061473
122	91	43	2	45000.00	90000.00	2026-01-21 21:30:46.010181
123	91	38	2	6000.00	12000.00	2026-01-21 21:30:46.010181
124	91	42	3	45000.00	135000.00	2026-01-21 21:30:46.010181
125	92	44	1	45000.00	45000.00	2026-01-22 09:14:34.821722
\.


--
-- Data for Name: sales; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.sales (id, sale_number, customer_id, user_id, sale_date, sale_type, subtotal, discount_amount, discount_reason, total_amount, payment_status, notes, created_at, updated_at, payment_method) FROM stdin;
74	VNT-20260120-0023	14	4	2026-01-20 20:37:33	credit	5000.00	4500.00	\N	500.00	paid	\N	2026-01-20 20:37:33	2026-01-20 23:37:54.783233	\N
75	VNT-20260121-0001	15	4	2026-01-21 06:23:22	reservation	59000.00	1000.00	\N	58000.00	paid	\N	2026-01-21 06:23:22	2026-01-21 09:25:01.075693	cash
76	VNT-20260121-0002	21	4	2026-01-21 06:27:28	credit	180000.00	6000.00	\N	174000.00	paid	\N	2026-01-21 06:27:28	2026-01-21 09:28:00.499194	\N
77	VNT-20260121-0003	22	4	2026-01-21 06:30:18	immediate	180000.00	5600.00	\N	174400.00	paid	\N	2026-01-21 06:30:18	2026-01-21 06:30:18	cash
80	VNT-20260121-0005	28	4	2026-01-21 08:41:38	immediate	45000.00	0.00	\N	45000.00	paid	\N	2026-01-21 08:41:38	2026-01-21 08:41:38	cash
83	VNT-20260121-0006	15	4	2026-01-21 09:19:44	credit	10000.00	0.00	\N	10000.00	pending	\N	2026-01-21 09:19:44	2026-01-21 09:19:44	\N
84	VNT-20260121-0007	14	4	2026-01-21 09:26:19	immediate	7000.00	0.00	\N	7000.00	paid	\N	2026-01-21 09:26:19	2026-01-21 09:26:19	cash
85	VNT-20260121-0008	31	4	2026-01-21 09:26:31	immediate	7000.00	0.00	\N	7000.00	paid	\N	2026-01-21 09:26:31	2026-01-21 09:26:31	cash
86	VNT-20260121-0009	21	4	2026-01-21 09:29:08	reservation	21000.00	1000.00	\N	20000.00	partial	\N	2026-01-21 09:29:08	2026-01-21 09:29:08	cash
87	VNT-20260121-0010	32	4	2026-01-21 09:42:33	immediate	45000.00	0.00	\N	45000.00	paid	\N	2026-01-21 09:42:33	2026-01-21 09:42:33	mobile_money
90	VNT-20260121-0013	33	4	2026-01-21 13:42:22	immediate	121000.00	5000.00	\N	116000.00	paid	\N	2026-01-21 13:42:22	2026-01-21 13:42:22	cash
65	VNT-20260120-0014	21	4	2026-01-20 19:55:40	credit	159000.00	45000.00	\N	114000.00	partial	\N	2026-01-20 19:55:40	2026-01-21 17:33:24.680349	\N
91	VNT-20260121-0014	34	4	2026-01-21 18:30:46	credit	237000.00	4000.00	\N	233000.00	pending	\N	2026-01-21 18:30:46	2026-01-21 18:30:46	\N
88	VNT-20260121-0011	14	4	2026-01-21 09:43:53	reservation	10000.00	0.00	\N	10000.00	cancelled	\N	2026-01-21 09:43:53	2026-01-21 23:48:47.214621	mobile_money
89	VNT-20260121-0012	14	4	2026-01-21 09:48:58	reservation	6000.00	0.00	\N	6000.00	paid	\N	2026-01-21 09:48:58	2026-01-21 23:49:07.759759	mobile_money
92	VNT-20260122-0001	35	4	2026-01-22 06:14:34	immediate	45000.00	2000.00	\N	43000.00	paid	\N	2026-01-22 06:14:34	2026-01-22 06:14:34	cash
44	VNT-20260120-0001	20	4	2026-01-20 15:51:53	immediate	50000.00	10000.00	\N	40000.00	paid	\N	2026-01-20 15:51:53	2026-01-20 15:51:53	cash
45	VNT-20260120-0002	15	4	2026-01-20 15:55:53	immediate	150000.00	80000.00	\N	70000.00	paid	\N	2026-01-20 15:55:53	2026-01-20 15:55:53	cash
55	VNT-20260120-0004	8	4	2026-01-20 18:24:55	immediate	300000.00	4500.00	\N	295500.00	paid	\N	2026-01-20 18:24:55	2026-01-20 18:24:55	mobile_money
56	VNT-20260120-0005	15	4	2026-01-20 18:26:11	reservation	200000.00	3000.00	\N	197000.00	paid	\N	2026-01-20 18:26:11	2026-01-20 21:35:41.763813	mobile_money
57	VNT-20260120-0006	15	4	2026-01-20 18:42:13	immediate	200000.00	4000.00	\N	196000.00	paid	\N	2026-01-20 18:42:13	2026-01-20 18:42:13	mobile_money
58	VNT-20260120-0007	14	4	2026-01-20 18:43:54	reservation	100000.00	2300.00	\N	97700.00	paid	\N	2026-01-20 18:43:54	2026-01-20 21:44:55.096507	cash
59	VNT-20260120-0008	15	4	2026-01-20 19:35:01	immediate	102000.00	3500.00	\N	98500.00	paid	\N	2026-01-20 19:35:01	2026-01-20 19:35:01	cash
60	VNT-20260120-0009	8	4	2026-01-20 19:39:21	reservation	18000.00	4000.00	\N	14000.00	paid	\N	2026-01-20 19:39:21	2026-01-20 22:40:13.472864	cash
61	VNT-20260120-0010	7	4	2026-01-20 19:41:55	reservation	180000.00	0.00	\N	180000.00	cancelled	\N	2026-01-20 19:41:55	2026-01-20 22:42:57.26929	cash
62	VNT-20260120-0011	7	4	2026-01-20 19:45:59	immediate	135000.00	60000.00	\N	75000.00	paid	\N	2026-01-20 19:45:59	2026-01-20 19:45:59	mobile_money
63	VNT-20260120-0012	9	4	2026-01-20 19:47:31	reservation	90000.00	4000.00	\N	86000.00	paid	\N	2026-01-20 19:47:31	2026-01-20 22:48:13.570714	mobile_money
64	VNT-20260120-0013	7	4	2026-01-20 19:53:18	reservation	135000.00	23000.00	\N	112000.00	cancelled	\N	2026-01-20 19:53:18	2026-01-20 22:54:44.442938	mobile_money
46	VNT-20260120-0003	8	4	2026-01-20 16:01:17	credit	750000.00	45000.00	\N	705000.00	paid	\N	2026-01-20 16:01:17	2026-01-20 22:56:33.399121	\N
66	VNT-20260120-0015	21	4	2026-01-20 20:05:01	credit	9000.00	0.00	\N	9000.00	paid	\N	2026-01-20 20:05:01	2026-01-20 23:05:56.859704	\N
67	VNT-20260120-0016	21	4	2026-01-20 20:07:22	credit	9000.00	0.00	\N	9000.00	paid	\N	2026-01-20 20:07:22	2026-01-20 23:07:58.167516	\N
68	VNT-20260120-0017	21	4	2026-01-20 20:12:39	credit	7000.00	300.00	\N	6700.00	paid	\N	2026-01-20 20:12:39	2026-01-20 23:14:07.462503	\N
69	VNT-20260120-0018	9	4	2026-01-20 20:20:20	immediate	45000.00	23.00	\N	44977.00	paid	\N	2026-01-20 20:20:20	2026-01-20 20:20:20	mobile_money
70	VNT-20260120-0019	9	4	2026-01-20 20:21:04	credit	6000.00	0.00	\N	6000.00	paid	\N	2026-01-20 20:21:04	2026-01-20 23:21:54.807567	\N
71	VNT-20260120-0020	9	4	2026-01-20 20:23:46	immediate	90000.00	0.00	\N	90000.00	paid	\N	2026-01-20 20:23:46	2026-01-20 20:23:46	mobile_money
72	VNT-20260120-0021	15	4	2026-01-20 20:24:57	credit	10000.00	300.00	\N	9700.00	paid	\N	2026-01-20 20:24:57	2026-01-20 23:25:42.094583	\N
73	VNT-20260120-0022	14	4	2026-01-20 20:30:12	credit	5000.00	990.00	\N	4010.00	paid	\N	2026-01-20 20:30:12	2026-01-20 23:32:30.964013	\N
\.


--
-- Data for Name: sessions; Type: TABLE DATA; Schema: public; Owner: -
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
-- Data for Name: stock_batches; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.stock_batches (id, variant_id, stock_receipt_item_id, batch_number, initial_quantity, remaining_quantity, supplier_unit_cost, freight_cost_per_unit, other_costs_per_unit, cost_status, cost_validated_at, received_date, reserved_quantity, available_quantity) FROM stdin;
37	33	120	BATCH-20260120-012	8	7	1620.00	357.89	947.37	validated	2026-01-20 19:32:23	2026-01-20 19:29:02	0	\N
34	36	117	BATCH-20260120-009	10	8	2700.00	357.89	947.37	validated	2026-01-20 19:32:23	2026-01-20 19:29:02	0	\N
33	42	116	BATCH-20260120-008	8	1	16605.00	357.89	947.37	validated	2026-01-20 19:32:23	2026-01-20 19:29:02	0	\N
36	34	119	BATCH-20260120-011	9	7	1620.00	357.89	947.37	validated	2026-01-20 19:32:23	2026-01-20 19:29:02	0	\N
28	41	111	BATCH-20260120-003	9	0	25500.00	819.67	163.93	validated	2026-01-20 15:47:46	2026-01-20 14:37:09	0	\N
30	42	109	BATCH-20260120-005	7	0	51000.00	1639.34	327.87	validated	2026-01-20 15:47:46	2026-01-20 14:37:09	0	\N
40	37	123	BATCH-20260120-015	5	0	270.00	357.89	947.37	validated	2026-01-20 19:32:23	2026-01-20 19:29:02	0	\N
26	43	110	BATCH-20260120-001	10	0	51000.00	1639.34	327.87	validated	2026-01-20 15:47:46	2026-01-20 14:37:09	0	\N
35	35	118	BATCH-20260120-010	20	15	2700.00	357.89	947.37	validated	2026-01-20 19:32:23	2026-01-20 19:29:02	3	\N
32	43	115	BATCH-20260120-007	5	1	16605.00	357.89	947.37	validated	2026-01-20 19:32:23	2026-01-20 19:29:02	0	\N
38	39	121	BATCH-20260120-013	9	0	1755.00	357.89	947.37	validated	2026-01-20 19:32:23	2026-01-20 19:29:02	0	\N
27	40	112	BATCH-20260120-002	12	0	25500.00	819.67	163.93	validated	2026-01-20 15:47:46	2026-01-20 14:37:09	0	\N
39	38	122	BATCH-20260120-014	11	4	270.00	357.89	947.37	validated	2026-01-20 19:32:23	2026-01-20 19:29:02	0	\N
31	44	114	BATCH-20260120-006	10	3	16605.00	357.89	947.37	validated	2026-01-20 19:32:23	2026-01-20 19:29:02	0	\N
29	44	113	BATCH-20260120-004	3	0	51000.00	1639.34	327.87	validated	2026-01-20 15:47:46	2026-01-20 14:37:09	0	\N
\.


--
-- Data for Name: stock_movements; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.stock_movements (id, variant_id, from_location_id, to_location_id, quantity, movement_type, sale_id, stock_receipt_id, performed_by, reason, notes, created_at, batch_id) FROM stdin;
146	40	11	\N	2	sale	55	\N	4	\N	Vente #55	2026-01-20 18:24:55	\N
147	42	11	\N	2	sale	55	\N	4	\N	Vente #55	2026-01-20 18:24:55	\N
148	40	11	\N	4	reservation	56	\N	4	reservation	Réservation stock (FIFO + discount réparti)	2026-01-20 18:26:11	\N
149	40	10	\N	4	sale	56	\N	4	reservation_completed	Batch 27 vendu	2026-01-20 18:35:41	\N
150	40	11	\N	4	sale	57	\N	4	\N	Vente #57	2026-01-20 18:42:13	\N
151	40	11	\N	2	reservation	58	\N	4	reservation	Réservation stock (FIFO + discount réparti)	2026-01-20 18:43:54	\N
152	40	10	\N	2	sale	58	\N	4	reservation_completed	Batch 27 vendu	2026-01-20 18:44:55	\N
153	43	11	10	2	transfer	\N	\N	4	\N	\N	2026-01-20 18:50:48	TRF-20260120-185048-i9kqus
154	44	\N	10	10	receipt	\N	39	4	Réception RCP-20260120-0002	\N	2026-01-20 19:32:23	\N
155	43	\N	10	5	receipt	\N	39	4	Réception RCP-20260120-0002	\N	2026-01-20 19:32:23	\N
156	42	\N	10	8	receipt	\N	39	4	Réception RCP-20260120-0002	\N	2026-01-20 19:32:23	\N
157	36	\N	10	10	receipt	\N	39	4	Réception RCP-20260120-0002	\N	2026-01-20 19:32:23	\N
158	35	\N	10	20	receipt	\N	39	4	Réception RCP-20260120-0002	\N	2026-01-20 19:32:23	\N
159	34	\N	10	9	receipt	\N	39	4	Réception RCP-20260120-0002	\N	2026-01-20 19:32:23	\N
160	33	\N	10	8	receipt	\N	39	4	Réception RCP-20260120-0002	\N	2026-01-20 19:32:23	\N
161	39	\N	10	9	receipt	\N	39	4	Réception RCP-20260120-0002	\N	2026-01-20 19:32:23	\N
162	38	\N	10	11	receipt	\N	39	4	Réception RCP-20260120-0002	\N	2026-01-20 19:32:23	\N
163	37	\N	10	5	receipt	\N	39	4	Réception RCP-20260120-0002	\N	2026-01-20 19:32:23	\N
164	37	10	11	5	transfer	\N	\N	4	\N	\N	2026-01-20 19:33:16	TRF-20260120-193316-kryw0i
165	34	10	11	5	transfer	\N	\N	4	\N	\N	2026-01-20 19:33:16	TRF-20260120-193316-kryw0i
166	35	10	11	6	transfer	\N	\N	4	\N	\N	2026-01-20 19:33:16	TRF-20260120-193316-kryw0i
167	33	10	11	4	transfer	\N	\N	4	\N	\N	2026-01-20 19:33:16	TRF-20260120-193316-kryw0i
168	39	10	11	4	transfer	\N	\N	4	\N	\N	2026-01-20 19:33:16	TRF-20260120-193316-kryw0i
169	38	10	11	4	transfer	\N	\N	4	\N	\N	2026-01-20 19:33:16	TRF-20260120-193316-kryw0i
170	36	10	11	3	transfer	\N	\N	4	\N	\N	2026-01-20 19:33:16	TRF-20260120-193316-kryw0i
171	44	10	11	5	transfer	\N	\N	4	\N	\N	2026-01-20 19:33:16	TRF-20260120-193316-kryw0i
172	43	10	11	4	transfer	\N	\N	4	\N	\N	2026-01-20 19:33:16	TRF-20260120-193316-kryw0i
173	42	10	11	4	transfer	\N	\N	4	\N	\N	2026-01-20 19:33:16	TRF-20260120-193316-kryw0i
174	44	10	\N	1	sale	59	\N	4	sale	Vente FIFO - Vente #59	2026-01-20 19:35:01	\N
175	36	10	\N	1	sale	59	\N	4	sale	Vente FIFO - Vente #59	2026-01-20 19:35:01	\N
176	41	11	\N	1	sale	59	\N	4	sale	Vente FIFO - Vente #59	2026-01-20 19:35:01	\N
177	38	11	\N	3	reservation	60	\N	4	reservation	Réservation stock FIFO - Vente #60	2026-01-20 19:39:21	\N
178	42	11	\N	4	reservation	61	\N	4	reservation	Réservation stock FIFO - Vente #61	2026-01-20 19:41:55	\N
179	43	11	\N	3	sale	62	\N	4	sale	Vente FIFO - Vente #62	2026-01-20 19:45:59	\N
180	44	11	\N	2	reservation	63	\N	4	reservation	Réservation stock FIFO - Vente #63	2026-01-20 19:47:31	\N
181	44	11	\N	3	reservation	64	\N	4	reservation	Réservation stock FIFO - Vente #64	2026-01-20 19:53:18	\N
182	44	\N	11	3	return	64	\N	4	reservation_cancelled	Réservation annulée - Vente #64	2026-01-20 19:54:44	\N
183	37	11	\N	4	sale	65	\N	4	sale	Vente FIFO - Vente #65	2026-01-20 19:55:40	\N
184	44	11	\N	3	sale	65	\N	4	sale	Vente FIFO - Vente #65	2026-01-20 19:55:40	\N
185	33	11	\N	1	sale	66	\N	4	sale	Vente FIFO - Vente #66	2026-01-20 20:05:01	\N
186	34	10	\N	1	sale	67	\N	4	sale	Vente FIFO - Vente #67	2026-01-20 20:07:22	\N
187	36	10	\N	1	sale	68	\N	4	sale	Vente FIFO - Vente #68	2026-01-20 20:12:39	\N
188	43	11	\N	1	sale	69	\N	4	sale	Vente FIFO - Vente #69	2026-01-20 20:20:20	\N
189	38	11	\N	1	sale	70	\N	4	sale	Vente FIFO - Vente #70	2026-01-20 20:21:04	\N
190	44	11	\N	2	sale	71	\N	4	sale	Vente FIFO - Vente #71	2026-01-20 20:23:46	\N
191	39	11	\N	2	sale	72	\N	4	sale	Vente FIFO - Vente #72	2026-01-20 20:24:58	\N
192	39	11	\N	1	sale	73	\N	4	sale	Vente FIFO - Vente #73	2026-01-20 20:30:12	\N
193	39	10	\N	1	sale	74	\N	4	sale	Vente FIFO - Vente #74	2026-01-20 20:37:33	\N
194	34	11	\N	1	reservation	75	\N	4	reservation	Réservation stock FIFO - Vente #75	2026-01-21 06:23:22	\N
195	41	11	\N	1	reservation	75	\N	4	reservation	Réservation stock FIFO - Vente #75	2026-01-21 06:23:23	\N
129	43	\N	10	10	receipt	\N	38	4	Réception RCP-20260120-0001	\N	2026-01-20 15:47:46	\N
130	40	\N	10	12	receipt	\N	38	4	Réception RCP-20260120-0001	\N	2026-01-20 15:47:46	\N
131	41	\N	10	9	receipt	\N	38	4	Réception RCP-20260120-0001	\N	2026-01-20 15:47:46	\N
132	44	\N	10	3	receipt	\N	38	4	Réception RCP-20260120-0001	\N	2026-01-20 15:47:46	\N
133	42	\N	10	7	receipt	\N	38	4	Réception RCP-20260120-0001	\N	2026-01-20 15:47:46	\N
134	42	10	11	7	transfer	\N	\N	4	\N	\N	2026-01-20 15:50:29	TRF-20260120-155029-xHy8dN
135	44	10	11	3	transfer	\N	\N	4	\N	\N	2026-01-20 15:50:29	TRF-20260120-155029-xHy8dN
136	41	10	11	9	transfer	\N	\N	4	\N	\N	2026-01-20 15:50:29	TRF-20260120-155029-xHy8dN
137	40	10	11	12	transfer	\N	\N	4	\N	\N	2026-01-20 15:50:29	TRF-20260120-155029-xHy8dN
138	43	10	11	10	transfer	\N	\N	4	\N	\N	2026-01-20 15:50:29	TRF-20260120-155029-xHy8dN
139	41	11	\N	1	sale	44	\N	4	\N	Vente #44	2026-01-20 15:51:53	\N
140	42	11	\N	1	sale	45	\N	4	\N	Vente #45	2026-01-20 15:55:53	\N
141	41	11	\N	1	sale	45	\N	4	\N	Vente #45	2026-01-20 15:55:53	\N
142	41	11	\N	5	sale	46	\N	4	\N	Vente #46	2026-01-20 16:01:17	\N
143	43	11	\N	5	sale	46	\N	4	\N	Vente #46	2026-01-20 16:01:17	\N
196	34	11	\N	1	sale	75	\N	4	reservation_completed	Finalisation réservation - Vente #75 - Batch BATCH-20260120-011	2026-01-21 06:25:01	\N
197	41	11	\N	1	sale	75	\N	4	reservation_completed	Finalisation réservation - Vente #75 - Batch BATCH-20260120-003	2026-01-21 06:25:01	\N
144	41	11	\N	2	reservation	\N	\N	4	reservation	Réservation stock (FIFO + discount réparti)	2026-01-20 18:02:28	\N
145	43	11	\N	1	reservation	\N	\N	4	reservation	Réservation stock (FIFO + discount réparti)	2026-01-20 18:02:28	\N
198	42	11	\N	4	sale	76	\N	4	sale	Vente FIFO - Vente #76	2026-01-21 06:27:28	\N
199	42	11	\N	4	sale	77	\N	4	sale	Vente FIFO - Vente #77	2026-01-21 06:30:18	\N
200	39	11	\N	1	sale	\N	\N	4	sale	Vente FIFO - Vente #78	2026-01-21 08:22:51	\N
202	44	11	\N	1	sale	80	\N	4	sale	Vente FIFO - Vente #80	2026-01-21 08:41:38	\N
201	37	11	\N	1	sale	\N	\N	4	sale	Vente FIFO - Vente #79	2026-01-21 08:29:01	\N
203	39	10	\N	2	sale	83	\N	4	sale	Vente FIFO - Vente #83	2026-01-21 09:19:44	\N
204	35	11	\N	1	sale	84	\N	4	sale	Vente FIFO - Vente #84	2026-01-21 09:26:19	\N
205	35	11	\N	1	sale	85	\N	4	sale	Vente FIFO - Vente #85	2026-01-21 09:26:31	\N
206	35	11	\N	3	reservation	86	\N	4	reservation	Réservation stock FIFO - Vente #86	2026-01-21 09:29:08	\N
207	43	11	\N	1	sale	87	\N	4	sale	Vente FIFO - Vente #87	2026-01-21 09:42:33	\N
208	39	10	\N	2	reservation	88	\N	4	reservation	Réservation stock FIFO - Vente #88	2026-01-21 09:43:53	\N
209	38	10	\N	1	reservation	89	\N	4	reservation	Réservation stock FIFO - Vente #89	2026-01-21 09:48:58	\N
210	35	10	11	5	transfer	\N	\N	4	\N	\N	2026-01-21 13:41:35	TRF-20260121-134135-j8Hkzw
211	36	10	11	3	transfer	\N	\N	4	\N	\N	2026-01-21 13:41:35	TRF-20260121-134135-j8Hkzw
212	42	10	11	4	transfer	\N	\N	4	\N	\N	2026-01-21 13:41:35	TRF-20260121-134135-j8Hkzw
213	43	10	11	2	transfer	\N	\N	4	\N	\N	2026-01-21 13:41:35	TRF-20260121-134135-j8Hkzw
214	44	10	11	2	transfer	\N	\N	4	\N	\N	2026-01-21 13:41:35	TRF-20260121-134135-j8Hkzw
215	34	10	11	2	transfer	\N	\N	4	\N	\N	2026-01-21 13:41:35	TRF-20260121-134135-j8Hkzw
216	38	10	11	2	transfer	\N	\N	4	\N	\N	2026-01-21 13:41:35	TRF-20260121-134135-j8Hkzw
217	39	10	11	2	transfer	\N	\N	4	\N	\N	2026-01-21 13:41:35	TRF-20260121-134135-j8Hkzw
218	33	10	11	2	transfer	\N	\N	4	\N	\N	2026-01-21 13:41:35	TRF-20260121-134135-j8Hkzw
219	43	11	\N	2	sale	90	\N	4	sale	Vente FIFO - Vente #90	2026-01-21 13:42:22	\N
220	35	11	\N	3	sale	90	\N	4	sale	Vente FIFO - Vente #90	2026-01-21 13:42:22	\N
221	39	11	\N	2	sale	90	\N	4	sale	Vente FIFO - Vente #90	2026-01-21 13:42:22	\N
222	43	11	\N	2	sale	91	\N	4	sale	Vente FIFO - Vente #91	2026-01-21 18:30:46	\N
223	38	11	\N	2	sale	91	\N	4	sale	Vente FIFO - Vente #91	2026-01-21 18:30:46	\N
224	42	11	\N	3	sale	91	\N	4	sale	Vente FIFO - Vente #91	2026-01-21 18:30:46	\N
225	39	\N	10	2	return	88	\N	4	reservation_cancelled	Réservation annulée - Vente #88	2026-01-21 20:48:47	\N
226	38	10	\N	1	sale	89	\N	4	reservation_completed	Finalisation réservation - Vente #89 - Batch BATCH-20260120-014	2026-01-21 20:49:07	\N
227	44	11	\N	1	sale	92	\N	4	sale	Vente FIFO - Vente #92	2026-01-22 06:14:34	\N
\.


--
-- Data for Name: stock_receipt_item_ratings; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.stock_receipt_item_ratings (id, stock_receipt_item_id, attribute_type_id, attribute_conformity_rating, quality_rating, quality_notes, rated_by, created_at) FROM stdin;
258	110	\N	9.00	9.00	ok	4	2026-01-20 14:58:23
259	110	16	9.00	9.00	\N	4	2026-01-20 14:58:23
260	110	17	9.00	9.00	\N	4	2026-01-20 14:58:23
261	112	\N	9.00	9.00	tty	4	2026-01-20 14:58:23
262	112	16	8.00	9.00	\N	4	2026-01-20 14:58:23
263	112	18	9.00	9.00	\N	4	2026-01-20 14:58:23
264	111	\N	10.00	10.00	\N	4	2026-01-20 14:58:23
265	111	16	10.00	10.00	\N	4	2026-01-20 14:58:23
266	111	18	10.00	10.00	\N	4	2026-01-20 14:58:23
267	113	\N	10.00	10.00	\N	4	2026-01-20 14:58:24
268	113	16	10.00	10.00	\N	4	2026-01-20 14:58:24
269	113	17	10.00	10.00	\N	4	2026-01-20 14:58:24
270	109	\N	7.00	10.00	\N	4	2026-01-20 14:58:24
271	109	16	9.00	10.00	\N	4	2026-01-20 14:58:24
272	109	17	5.00	10.00	\N	4	2026-01-20 14:58:24
273	114	\N	10.00	9.00	\N	4	2026-01-20 19:30:35
274	114	16	9.00	9.00	\N	4	2026-01-20 19:30:35
275	114	17	10.00	9.00	\N	4	2026-01-20 19:30:35
276	115	\N	10.00	8.00	\N	4	2026-01-20 19:30:36
277	115	16	9.00	8.00	\N	4	2026-01-20 19:30:36
278	115	17	10.00	8.00	\N	4	2026-01-20 19:30:36
279	116	\N	10.00	9.00	\N	4	2026-01-20 19:30:36
280	116	16	10.00	9.00	\N	4	2026-01-20 19:30:36
281	116	17	9.00	9.00	\N	4	2026-01-20 19:30:36
282	117	\N	9.00	10.00	\N	4	2026-01-20 19:30:36
283	117	16	9.00	10.00	\N	4	2026-01-20 19:30:36
284	117	18	8.00	10.00	\N	4	2026-01-20 19:30:36
285	118	\N	9.00	9.00	\N	4	2026-01-20 19:30:36
286	118	16	9.00	9.00	\N	4	2026-01-20 19:30:36
287	118	18	9.00	9.00	\N	4	2026-01-20 19:30:36
288	119	\N	9.00	9.00	\N	4	2026-01-20 19:30:36
289	119	16	9.00	9.00	\N	4	2026-01-20 19:30:36
290	119	18	8.00	9.00	\N	4	2026-01-20 19:30:36
291	120	\N	10.00	10.00	\N	4	2026-01-20 19:30:36
292	120	16	10.00	10.00	\N	4	2026-01-20 19:30:36
293	120	18	10.00	10.00	\N	4	2026-01-20 19:30:36
294	121	\N	9.00	9.00	\N	4	2026-01-20 19:30:36
295	121	16	9.00	9.00	\N	4	2026-01-20 19:30:36
296	121	18	9.00	9.00	\N	4	2026-01-20 19:30:36
297	122	\N	10.00	9.00	\N	4	2026-01-20 19:30:36
298	122	16	9.00	9.00	\N	4	2026-01-20 19:30:36
299	122	18	10.00	9.00	\N	4	2026-01-20 19:30:36
300	123	\N	7.00	8.00	\N	4	2026-01-20 19:30:37
301	123	16	5.00	8.00	\N	4	2026-01-20 19:30:37
302	123	18	9.00	8.00	\N	4	2026-01-20 19:30:37
\.


--
-- Data for Name: stock_receipt_items; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.stock_receipt_items (id, stock_receipt_id, variant_id, quantity_ordered, quantity_received, unit_cost_ariary, notes, created_at) FROM stdin;
110	38	43	10	10	51000.00	\N	2026-01-20 17:36:30.207095
112	38	40	12	12	25500.00	\N	2026-01-20 17:36:30.207095
111	38	41	10	9	25500.00	\N	2026-01-20 17:36:30.207095
113	38	44	0	3	51000.00	\N	2026-01-20 17:39:40.843222
109	38	42	10	7	51000.00	\N	2026-01-20 17:36:30.207095
114	39	44	10	10	16605.00	\N	2026-01-20 22:28:31.96754
115	39	43	5	5	16605.00	\N	2026-01-20 22:28:31.96754
116	39	42	8	8	16605.00	\N	2026-01-20 22:28:31.96754
117	39	36	10	10	2700.00	\N	2026-01-20 22:28:31.96754
118	39	35	20	20	2700.00	\N	2026-01-20 22:28:31.96754
119	39	34	9	9	1620.00	\N	2026-01-20 22:28:31.96754
120	39	33	8	8	1620.00	\N	2026-01-20 22:28:31.96754
121	39	39	9	9	1755.00	\N	2026-01-20 22:28:31.96754
122	39	38	11	11	270.00	\N	2026-01-20 22:28:31.96754
123	39	37	5	5	270.00	\N	2026-01-20 22:28:31.96754
\.


--
-- Data for Name: stock_receipts; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.stock_receipts (id, receipt_number, supplier_id, freight_forwarder_id, total_cost_ariary, status, notes, created_by, created_at, updated_at, expected_delivery_date, actual_delivery_date, cost_validated_at, cost_validated_by) FROM stdin;
38	RCP-20260120-0001	7	3	1606500.00	validated	\N	4	2026-01-20 14:36:30	2026-01-20 15:47:46	2026-01-31	2026-01-20 14:37:09	2026-01-20 15:47:46+03	4
39	RCP-20260120-0002	3	2	510570.00	validated	\N	4	2026-01-20 19:28:31	2026-01-20 19:32:23	2026-01-24	2026-01-20 19:29:02	2026-01-20 19:32:23+03	4
\.


--
-- Data for Name: suppliers; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.suppliers (id, name, wechat, profile, contact, accessibility_notes, reliability_score, logo_url, is_active, created_at, updated_at, coordinate_id, total_orders, total_items_ordered, total_items_received, quality_rating_sum, quality_rating_count, total_value_ordered, total_value_received, weighted_quality_sum, total_weighted_value) FROM stdin;
3	raggathon	AZERTYUIOP	FZFHZGH	+97291899111	ALKJLKJEEJA	6.35	http://localhost:8000/storage/uploads/images/2025/12/6c8d3f45-5065-49ad-a382-ee2c0fe406a4.jpeg	t	2025-12-27 15:35:55	2026-01-20 19:32:23	4	10	193	164	988.00	117	10451740.02	8051740.02	520145889.74	88008275.09
4	HERIMANANTSOA Manitriniaina Christian	chirchri	\N	082893829	\N	5.00	uploads/images/2026/01/a4179a4f-d4af-4b46-8c2b-45c9db1d7b2f.png	t	2026-01-13 20:47:35	2026-01-13 20:47:35	4	0	0	0	0.00	0	0.00	0.00	0.00	0.00
6	dhhd	fashion	\N	+972918991	\N	5.00	uploads/images/2026/01/7cd544b9-a8e1-4bb9-ae78-df360ed2a1b5.png	t	2026-01-14 04:13:10	2026-01-14 04:14:53	2	0	0	0	0.00	0	0.00	0.00	0.00	0.00
7	Uswell	uswell	\N	+166117289101	\N	7.73	\N	t	2026-01-14 08:27:35	2026-01-20 15:47:47	7	4	86	85	303.00	33	3190350.00	3139350.00	59127660.00	6278700.00
1	Guangzhou_corpaaa	ajhsjhjash	fekzkjkjkjkejkjz	+97291899111	eahjhejhejhejahjhjhh	7.52	http://localhost:8000/storage/uploads/images/2025/12/e8d580c3-38b9-423f-925c-5eb8406fafad.jpeg	t	2025-12-27 07:49:06	2026-01-19 20:14:05	5	8	225	225	277.00	37	3159856.00	3159856.00	53827951.53	6725041.00
2	HERIMANANTSOA Manitriniaina Christian AA	ajhsjhjash	dkzjkjkjkjdkzj	+97291899111	dizuhuiiuhiu"h"uh	7.02	http://localhost:8000/storage/uploads/images/2025/12/00a857d2-dc4b-4694-993a-b3cc0e8872a9.jpeg	t	2025-12-27 14:50:39	2026-01-14 08:53:23	1	14	234	182	423.00	58	3536100.00	2647500.00	33629620.00	4530000.00
5	ghouangzou	fashion	\N	+97291899111	\N	9.38	uploads/images/2026/01/8cd3ceaf-511f-4b51-827a-c0a4ac16f99a.png	t	2026-01-14 04:10:46	2026-01-14 04:10:46	3	0	0	0	453.00	57	0.00	0.00	129483000.00	14797800.00
\.


--
-- Data for Name: system_settings; Type: TABLE DATA; Schema: public; Owner: -
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
-- Data for Name: transaction_types; Type: TABLE DATA; Schema: public; Owner: -
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
-- Data for Name: user_sessions; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.user_sessions (id, user_id, login_at, logout_at, ip_address, created_at) FROM stdin;
\.


--
-- Data for Name: users; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.users (id, name, username, password, role, is_active, created_at, updated_at) FROM stdin;
3	chirstian	herimanantsoa	$2y$12$JIz2z14FuV4vde7Y6TaXluB0WnUR5hLquq3keQZ5raES/bbfA/rAu	vendeur	t	2025-12-19 13:08:48	2025-12-19 13:08:48
4	Administrateur	admin	$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi	admin	t	2025-12-27 10:28:54.853759	2025-12-27 10:28:54.853759
7	heyo	coco	$2y$12$Wxu2QCrRxLuAEA7VTzt0VOmpXq/i5xW5Ydm7pkc23SuYfkD1AeQ4a	vendeur	t	2026-01-16 07:22:50	2026-01-16 11:44:00.135271
2	christianHerimanantsoa	herimanantsoa51	$2y$12$M8EzX7//xt8Iu4owbXvBDej4KRZ0rDR9WqUhIHk8PFgTR8Pexz.vG	vendeur	t	2025-12-19 11:09:55	2026-01-16 11:50:50.889489
\.


--
-- Data for Name: variant_attribute_values; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.variant_attribute_values (id, variant_id, attribute_value_id, created_at) FROM stdin;
115	28	47	2026-01-20 16:24:19.002948
116	28	51	2026-01-20 16:24:19.006698
117	29	42	2026-01-20 16:24:45.382101
118	29	61	2026-01-20 16:24:45.390762
119	30	46	2026-01-20 16:28:10.009205
120	30	51	2026-01-20 16:28:10.016946
121	31	47	2026-01-20 16:28:36.81699
122	31	62	2026-01-20 16:28:36.825082
125	32	46	2026-01-20 16:29:24.71593
126	32	63	2026-01-20 16:29:24.727165
127	33	48	2026-01-20 16:32:05.738142
128	33	56	2026-01-20 16:32:05.741726
129	34	45	2026-01-20 16:32:17.998798
130	34	56	2026-01-20 16:32:18.003299
131	35	44	2026-01-20 16:36:15.372657
132	35	55	2026-01-20 16:36:15.380153
133	36	48	2026-01-20 16:36:25.829669
134	36	57	2026-01-20 16:36:25.83409
135	37	44	2026-01-20 16:38:16.866708
136	37	55	2026-01-20 16:38:16.869836
137	38	47	2026-01-20 16:38:31.429445
138	38	57	2026-01-20 16:38:31.440233
139	39	49	2026-01-20 16:40:17.038224
140	39	58	2026-01-20 16:40:17.041904
141	40	48	2026-01-20 16:42:44.628652
142	40	56	2026-01-20 16:42:44.632992
143	41	47	2026-01-20 16:42:56.929102
144	41	54	2026-01-20 16:42:56.93275
145	42	47	2026-01-20 16:44:56.505731
146	42	64	2026-01-20 16:44:56.513138
147	43	44	2026-01-20 16:45:10.187445
148	43	51	2026-01-20 16:45:10.190627
149	44	48	2026-01-20 17:39:13.425031
150	44	51	2026-01-20 17:39:13.430333
\.


--
-- Name: account_transactions_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.account_transactions_id_seq', 241, true);


--
-- Name: account_types_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.account_types_id_seq', 3, true);


--
-- Name: accounts_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.accounts_id_seq', 10, true);


--
-- Name: attribute_types_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.attribute_types_id_seq', 19, true);


--
-- Name: attribute_values_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.attribute_values_id_seq', 64, true);


--
-- Name: audit_logs_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.audit_logs_id_seq', 1, false);


--
-- Name: cash_count_denominations_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.cash_count_denominations_id_seq', 27, true);


--
-- Name: cash_counts_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.cash_counts_id_seq', 2, true);


--
-- Name: categories_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.categories_id_seq', 30, true);


--
-- Name: coordinates_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.coordinates_id_seq', 7, true);


--
-- Name: credit_installments_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.credit_installments_id_seq', 30, true);


--
-- Name: credits_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.credits_id_seq', 27, true);


--
-- Name: currency_rates_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.currency_rates_id_seq', 9, true);


--
-- Name: customers_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.customers_id_seq', 35, true);


--
-- Name: expense_categories_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.expense_categories_id_seq', 18, true);


--
-- Name: failed_jobs_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.failed_jobs_id_seq', 1, false);


--
-- Name: freight_forwarders_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.freight_forwarders_id_seq', 3, true);


--
-- Name: installment_transactions_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.installment_transactions_id_seq', 29, true);


--
-- Name: jobs_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.jobs_id_seq', 1, false);


--
-- Name: locations_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.locations_id_seq', 11, true);


--
-- Name: migrations_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.migrations_id_seq', 25, true);


--
-- Name: notification_preferences_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.notification_preferences_id_seq', 4, true);


--
-- Name: notifications_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.notifications_id_seq', 42, true);


--
-- Name: personal_access_tokens_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.personal_access_tokens_id_seq', 54, true);


--
-- Name: product_attributes_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.product_attributes_id_seq', 71, true);


--
-- Name: product_variant_locations_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.product_variant_locations_id_seq', 68, true);


--
-- Name: product_variants_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.product_variants_id_seq', 44, true);


--
-- Name: products_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.products_id_seq', 36, true);


--
-- Name: reservation_deposits_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.reservation_deposits_id_seq', 1, false);


--
-- Name: reservations_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.reservations_id_seq', 26, true);


--
-- Name: sale_item_batches_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.sale_item_batches_id_seq', 55, true);


--
-- Name: sale_items_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.sale_items_id_seq', 125, true);


--
-- Name: sales_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.sales_id_seq', 92, true);


--
-- Name: stock_batches_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.stock_batches_id_seq', 40, true);


--
-- Name: stock_movements_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.stock_movements_id_seq', 227, true);


--
-- Name: stock_receipt_item_ratings_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.stock_receipt_item_ratings_id_seq', 302, true);


--
-- Name: stock_receipt_items_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.stock_receipt_items_id_seq', 123, true);


--
-- Name: stock_receipts_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.stock_receipts_id_seq', 39, true);


--
-- Name: suppliers_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.suppliers_id_seq', 7, true);


--
-- Name: system_settings_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.system_settings_id_seq', 7, true);


--
-- Name: transaction_types_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.transaction_types_id_seq', 21, true);


--
-- Name: user_sessions_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.user_sessions_id_seq', 1, false);


--
-- Name: users_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.users_id_seq', 7, true);


--
-- Name: variant_attribute_values_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.variant_attribute_values_id_seq', 150, true);


--
-- Name: account_transactions account_transactions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.account_transactions
    ADD CONSTRAINT account_transactions_pkey PRIMARY KEY (id);


--
-- Name: account_types account_types_code_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.account_types
    ADD CONSTRAINT account_types_code_key UNIQUE (code);


--
-- Name: account_types account_types_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.account_types
    ADD CONSTRAINT account_types_pkey PRIMARY KEY (id);


--
-- Name: accounts accounts_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.accounts
    ADD CONSTRAINT accounts_pkey PRIMARY KEY (id);


--
-- Name: attribute_types attribute_types_name_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.attribute_types
    ADD CONSTRAINT attribute_types_name_key UNIQUE (name);


--
-- Name: attribute_types attribute_types_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.attribute_types
    ADD CONSTRAINT attribute_types_pkey PRIMARY KEY (id);


--
-- Name: attribute_values attribute_values_attribute_type_id_value_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.attribute_values
    ADD CONSTRAINT attribute_values_attribute_type_id_value_key UNIQUE (attribute_type_id, value);


--
-- Name: attribute_values attribute_values_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.attribute_values
    ADD CONSTRAINT attribute_values_pkey PRIMARY KEY (id);


--
-- Name: audit_logs audit_logs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.audit_logs
    ADD CONSTRAINT audit_logs_pkey PRIMARY KEY (id);


--
-- Name: cache_locks cache_locks_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cache_locks
    ADD CONSTRAINT cache_locks_pkey PRIMARY KEY (key);


--
-- Name: cache cache_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cache
    ADD CONSTRAINT cache_pkey PRIMARY KEY (key);


--
-- Name: cash_count_denominations cash_count_denominations_cash_count_id_denomination_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cash_count_denominations
    ADD CONSTRAINT cash_count_denominations_cash_count_id_denomination_unique UNIQUE (cash_count_id, denomination);


--
-- Name: cash_count_denominations cash_count_denominations_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cash_count_denominations
    ADD CONSTRAINT cash_count_denominations_pkey PRIMARY KEY (id);


--
-- Name: cash_counts cash_counts_count_date_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cash_counts
    ADD CONSTRAINT cash_counts_count_date_unique UNIQUE (count_date);


--
-- Name: cash_counts cash_counts_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cash_counts
    ADD CONSTRAINT cash_counts_pkey PRIMARY KEY (id);


--
-- Name: categories categories_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.categories
    ADD CONSTRAINT categories_pkey PRIMARY KEY (id);


--
-- Name: coordinates coordinates_country_city_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.coordinates
    ADD CONSTRAINT coordinates_country_city_unique UNIQUE (country, city);


--
-- Name: coordinates coordinates_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.coordinates
    ADD CONSTRAINT coordinates_pkey PRIMARY KEY (id);


--
-- Name: credit_installments credit_installments_credit_id_installment_number_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.credit_installments
    ADD CONSTRAINT credit_installments_credit_id_installment_number_key UNIQUE (credit_id, installment_number);


--
-- Name: credit_installments credit_installments_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.credit_installments
    ADD CONSTRAINT credit_installments_pkey PRIMARY KEY (id);


--
-- Name: credits credits_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.credits
    ADD CONSTRAINT credits_pkey PRIMARY KEY (id);


--
-- Name: credits credits_sale_id_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.credits
    ADD CONSTRAINT credits_sale_id_key UNIQUE (sale_id);


--
-- Name: currency_rates currency_rates_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.currency_rates
    ADD CONSTRAINT currency_rates_pkey PRIMARY KEY (id);


--
-- Name: customers customers_customer_number_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.customers
    ADD CONSTRAINT customers_customer_number_unique UNIQUE (customer_number);


--
-- Name: customers customers_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.customers
    ADD CONSTRAINT customers_pkey PRIMARY KEY (id);


--
-- Name: expense_categories expense_categories_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.expense_categories
    ADD CONSTRAINT expense_categories_pkey PRIMARY KEY (id);


--
-- Name: failed_jobs failed_jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.failed_jobs
    ADD CONSTRAINT failed_jobs_pkey PRIMARY KEY (id);


--
-- Name: failed_jobs failed_jobs_uuid_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.failed_jobs
    ADD CONSTRAINT failed_jobs_uuid_unique UNIQUE (uuid);


--
-- Name: freight_forwarders freight_forwarders_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.freight_forwarders
    ADD CONSTRAINT freight_forwarders_pkey PRIMARY KEY (id);


--
-- Name: installment_transactions installment_transactions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.installment_transactions
    ADD CONSTRAINT installment_transactions_pkey PRIMARY KEY (id);


--
-- Name: job_batches job_batches_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.job_batches
    ADD CONSTRAINT job_batches_pkey PRIMARY KEY (id);


--
-- Name: jobs jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.jobs
    ADD CONSTRAINT jobs_pkey PRIMARY KEY (id);


--
-- Name: locations locations_code_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.locations
    ADD CONSTRAINT locations_code_key UNIQUE (code);


--
-- Name: locations locations_name_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.locations
    ADD CONSTRAINT locations_name_key UNIQUE (name);


--
-- Name: locations locations_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.locations
    ADD CONSTRAINT locations_pkey PRIMARY KEY (id);


--
-- Name: migrations migrations_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.migrations
    ADD CONSTRAINT migrations_pkey PRIMARY KEY (id);


--
-- Name: notification_preferences notification_preferences_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.notification_preferences
    ADD CONSTRAINT notification_preferences_pkey PRIMARY KEY (id);


--
-- Name: notifications notifications_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.notifications
    ADD CONSTRAINT notifications_pkey PRIMARY KEY (id);


--
-- Name: password_reset_tokens password_reset_tokens_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.password_reset_tokens
    ADD CONSTRAINT password_reset_tokens_pkey PRIMARY KEY (username);


--
-- Name: personal_access_tokens personal_access_tokens_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.personal_access_tokens
    ADD CONSTRAINT personal_access_tokens_pkey PRIMARY KEY (id);


--
-- Name: personal_access_tokens personal_access_tokens_token_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.personal_access_tokens
    ADD CONSTRAINT personal_access_tokens_token_unique UNIQUE (token);


--
-- Name: product_attributes product_attributes_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.product_attributes
    ADD CONSTRAINT product_attributes_pkey PRIMARY KEY (id);


--
-- Name: product_attributes product_attributes_product_id_attribute_type_id_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.product_attributes
    ADD CONSTRAINT product_attributes_product_id_attribute_type_id_key UNIQUE (product_id, attribute_type_id);


--
-- Name: product_variant_locations product_variant_locations_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.product_variant_locations
    ADD CONSTRAINT product_variant_locations_pkey PRIMARY KEY (id);


--
-- Name: product_variants product_variants_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.product_variants
    ADD CONSTRAINT product_variants_pkey PRIMARY KEY (id);


--
-- Name: product_variants product_variants_sku_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.product_variants
    ADD CONSTRAINT product_variants_sku_key UNIQUE (sku);


--
-- Name: products products_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.products
    ADD CONSTRAINT products_pkey PRIMARY KEY (id);


--
-- Name: reservation_deposits reservation_deposits_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reservation_deposits
    ADD CONSTRAINT reservation_deposits_pkey PRIMARY KEY (id);


--
-- Name: reservations reservations_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reservations
    ADD CONSTRAINT reservations_pkey PRIMARY KEY (id);


--
-- Name: reservations reservations_sale_id_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reservations
    ADD CONSTRAINT reservations_sale_id_key UNIQUE (sale_id);


--
-- Name: sale_item_batches sale_item_batches_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sale_item_batches
    ADD CONSTRAINT sale_item_batches_pkey PRIMARY KEY (id);


--
-- Name: sale_items sale_items_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sale_items
    ADD CONSTRAINT sale_items_pkey PRIMARY KEY (id);


--
-- Name: sales sales_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sales
    ADD CONSTRAINT sales_pkey PRIMARY KEY (id);


--
-- Name: sales sales_sale_number_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sales
    ADD CONSTRAINT sales_sale_number_key UNIQUE (sale_number);


--
-- Name: sessions sessions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sessions
    ADD CONSTRAINT sessions_pkey PRIMARY KEY (id);


--
-- Name: stock_batches stock_batches_batch_number_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.stock_batches
    ADD CONSTRAINT stock_batches_batch_number_unique UNIQUE (batch_number);


--
-- Name: stock_batches stock_batches_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.stock_batches
    ADD CONSTRAINT stock_batches_pkey PRIMARY KEY (id);


--
-- Name: stock_movements stock_movements_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.stock_movements
    ADD CONSTRAINT stock_movements_pkey PRIMARY KEY (id);


--
-- Name: stock_receipt_item_ratings stock_receipt_item_ratings_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.stock_receipt_item_ratings
    ADD CONSTRAINT stock_receipt_item_ratings_pkey PRIMARY KEY (id);


--
-- Name: stock_receipt_items stock_receipt_items_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.stock_receipt_items
    ADD CONSTRAINT stock_receipt_items_pkey PRIMARY KEY (id);


--
-- Name: stock_receipts stock_receipts_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.stock_receipts
    ADD CONSTRAINT stock_receipts_pkey PRIMARY KEY (id);


--
-- Name: stock_receipts stock_receipts_receipt_number_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.stock_receipts
    ADD CONSTRAINT stock_receipts_receipt_number_key UNIQUE (receipt_number);


--
-- Name: suppliers suppliers_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.suppliers
    ADD CONSTRAINT suppliers_pkey PRIMARY KEY (id);


--
-- Name: system_settings system_settings_key_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.system_settings
    ADD CONSTRAINT system_settings_key_key UNIQUE (key);


--
-- Name: system_settings system_settings_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.system_settings
    ADD CONSTRAINT system_settings_pkey PRIMARY KEY (id);


--
-- Name: transaction_types transaction_types_code_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.transaction_types
    ADD CONSTRAINT transaction_types_code_key UNIQUE (code);


--
-- Name: transaction_types transaction_types_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.transaction_types
    ADD CONSTRAINT transaction_types_pkey PRIMARY KEY (id);


--
-- Name: notification_preferences unique_user_notification_type; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.notification_preferences
    ADD CONSTRAINT unique_user_notification_type UNIQUE (user_id, notification_type);


--
-- Name: product_variant_locations uq_variant_location; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.product_variant_locations
    ADD CONSTRAINT uq_variant_location UNIQUE (variant_id, location_id);


--
-- Name: user_sessions user_sessions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_sessions
    ADD CONSTRAINT user_sessions_pkey PRIMARY KEY (id);


--
-- Name: users users_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_pkey PRIMARY KEY (id);


--
-- Name: users users_usename_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_usename_key UNIQUE (username);


--
-- Name: variant_attribute_values variant_attribute_values_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.variant_attribute_values
    ADD CONSTRAINT variant_attribute_values_pkey PRIMARY KEY (id);


--
-- Name: variant_attribute_values variant_attribute_values_variant_id_attribute_value_id_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.variant_attribute_values
    ADD CONSTRAINT variant_attribute_values_variant_id_attribute_value_id_key UNIQUE (variant_id, attribute_value_id);


--
-- Name: account_transactions_reversed_transaction_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX account_transactions_reversed_transaction_id_index ON public.account_transactions USING btree (reversed_transaction_id);


--
-- Name: coordinates_city_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX coordinates_city_index ON public.coordinates USING btree (city);


--
-- Name: coordinates_country_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX coordinates_country_index ON public.coordinates USING btree (country);


--
-- Name: idx_account_transactions_account; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_account_transactions_account ON public.account_transactions USING btree (account_id);


--
-- Name: idx_account_transactions_date; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_account_transactions_date ON public.account_transactions USING btree (transaction_date);


--
-- Name: idx_account_transactions_freight; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_account_transactions_freight ON public.account_transactions USING btree (freight_forwarder_id);


--
-- Name: idx_account_transactions_sale; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_account_transactions_sale ON public.account_transactions USING btree (sale_id);


--
-- Name: idx_account_transactions_supplier; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_account_transactions_supplier ON public.account_transactions USING btree (supplier_id);


--
-- Name: idx_account_transactions_type; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_account_transactions_type ON public.account_transactions USING btree (transaction_type_id);


--
-- Name: idx_accounts_active; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_accounts_active ON public.accounts USING btree (is_active);


--
-- Name: idx_accounts_type; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_accounts_type ON public.accounts USING btree (account_type_id);


--
-- Name: idx_attribute_values_type; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_attribute_values_type ON public.attribute_values USING btree (attribute_type_id);


--
-- Name: idx_audit_logs_created; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_audit_logs_created ON public.audit_logs USING btree (created_at DESC);


--
-- Name: idx_audit_logs_table; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_audit_logs_table ON public.audit_logs USING btree (table_name, record_id);


--
-- Name: idx_audit_logs_user; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_audit_logs_user ON public.audit_logs USING btree (user_id);


--
-- Name: idx_categories_parent_id; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_categories_parent_id ON public.categories USING btree (parent_id);


--
-- Name: idx_cost_status; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_cost_status ON public.stock_batches USING btree (cost_status);


--
-- Name: idx_credit_installments_credit; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_credit_installments_credit ON public.credit_installments USING btree (credit_id);


--
-- Name: idx_credit_installments_due_date; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_credit_installments_due_date ON public.credit_installments USING btree (due_date);


--
-- Name: idx_credits_customer; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_credits_customer ON public.credits USING btree (customer_id);


--
-- Name: idx_credits_due_date; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_credits_due_date ON public.credits USING btree (due_date);


--
-- Name: idx_credits_sale; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_credits_sale ON public.credits USING btree (sale_id);


--
-- Name: idx_credits_status; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_credits_status ON public.credits USING btree (status);


--
-- Name: idx_customers_name; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_customers_name ON public.customers USING btree (name);


--
-- Name: idx_customers_phone; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_customers_phone ON public.customers USING btree (phone);


--
-- Name: idx_installments_due_status; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_installments_due_status ON public.credit_installments USING btree (due_date, status);


--
-- Name: idx_locations_active; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_locations_active ON public.locations USING btree (is_active) WHERE (is_active = true);


--
-- Name: idx_locations_warehouse; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_locations_warehouse ON public.locations USING btree (warehouse);


--
-- Name: idx_notif_prefs_type; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_notif_prefs_type ON public.notification_preferences USING btree (notification_type);


--
-- Name: idx_notif_prefs_user; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_notif_prefs_user ON public.notification_preferences USING btree (user_id);


--
-- Name: idx_notifications_created; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_notifications_created ON public.notifications USING btree (created_at DESC);


--
-- Name: idx_notifications_dismissed; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_notifications_dismissed ON public.notifications USING btree (dismissed_at) WHERE (dismissed_at IS NULL);


--
-- Name: idx_notifications_read; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_notifications_read ON public.notifications USING btree (is_read) WHERE (is_read = false);


--
-- Name: idx_notifications_user; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_notifications_user ON public.notifications USING btree (user_id);


--
-- Name: idx_product_attributes_product; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_product_attributes_product ON public.product_attributes USING btree (product_id);


--
-- Name: idx_product_variants_active; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_product_variants_active ON public.product_variants USING btree (is_active) WHERE (is_active = true);


--
-- Name: idx_product_variants_low_stock; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_product_variants_low_stock ON public.product_variants USING btree (stock_quantity) WHERE (stock_quantity <= low_stock_threshold);


--
-- Name: idx_product_variants_product; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_product_variants_product ON public.product_variants USING btree (product_id);


--
-- Name: idx_product_variants_sku; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_product_variants_sku ON public.product_variants USING btree (sku);


--
-- Name: idx_products_active; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_products_active ON public.products USING btree (is_active) WHERE (is_active = true);


--
-- Name: idx_products_category_id; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_products_category_id ON public.products USING btree (category_id);


--
-- Name: idx_products_subcategory_id; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_products_subcategory_id ON public.products USING btree (subcategory_id);


--
-- Name: idx_pvl_location; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_pvl_location ON public.product_variant_locations USING btree (location_id);


--
-- Name: idx_pvl_quantity; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_pvl_quantity ON public.product_variant_locations USING btree (quantity);


--
-- Name: idx_pvl_variant; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_pvl_variant ON public.product_variant_locations USING btree (variant_id);


--
-- Name: idx_pvl_variant_stock; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_pvl_variant_stock ON public.product_variant_locations USING btree (variant_id, quantity);


--
-- Name: idx_reservations_customer; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_reservations_customer ON public.reservations USING btree (customer_id);


--
-- Name: idx_reservations_expiry; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_reservations_expiry ON public.reservations USING btree (expiry_date);


--
-- Name: idx_reservations_sale; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_reservations_sale ON public.reservations USING btree (sale_id);


--
-- Name: idx_reservations_status; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_reservations_status ON public.reservations USING btree (status);


--
-- Name: idx_sale_items_sale; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_sale_items_sale ON public.sale_items USING btree (sale_id);


--
-- Name: idx_sale_items_variant; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_sale_items_variant ON public.sale_items USING btree (variant_id);


--
-- Name: idx_sales_customer; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_sales_customer ON public.sales USING btree (customer_id);


--
-- Name: idx_sales_date; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_sales_date ON public.sales USING btree (sale_date);


--
-- Name: idx_sales_number; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_sales_number ON public.sales USING btree (sale_number);


--
-- Name: idx_sales_status; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_sales_status ON public.sales USING btree (payment_status);


--
-- Name: idx_sales_type; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_sales_type ON public.sales USING btree (sale_type);


--
-- Name: idx_sales_user; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_sales_user ON public.sales USING btree (user_id);


--
-- Name: idx_sib_batch; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_sib_batch ON public.sale_item_batches USING btree (batch_id);


--
-- Name: idx_sib_date; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_sib_date ON public.sale_item_batches USING btree (created_at);


--
-- Name: idx_sib_sale_item; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_sib_sale_item ON public.sale_item_batches USING btree (sale_item_id);


--
-- Name: idx_sr_actual_date; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_sr_actual_date ON public.stock_receipts USING btree (actual_delivery_date);


--
-- Name: idx_sr_expected_date; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_sr_expected_date ON public.stock_receipts USING btree (expected_delivery_date);


--
-- Name: idx_stock_movements_created_at; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_stock_movements_created_at ON public.stock_movements USING btree (created_at);


--
-- Name: idx_stock_movements_from_location; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_stock_movements_from_location ON public.stock_movements USING btree (from_location_id);


--
-- Name: idx_stock_movements_performed_by; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_stock_movements_performed_by ON public.stock_movements USING btree (performed_by);


--
-- Name: idx_stock_movements_receipt; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_stock_movements_receipt ON public.stock_movements USING btree (stock_receipt_id);


--
-- Name: idx_stock_movements_sale; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_stock_movements_sale ON public.stock_movements USING btree (sale_id);


--
-- Name: idx_stock_movements_to_location; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_stock_movements_to_location ON public.stock_movements USING btree (to_location_id);


--
-- Name: idx_stock_movements_type; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_stock_movements_type ON public.stock_movements USING btree (movement_type);


--
-- Name: idx_stock_movements_variant; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_stock_movements_variant ON public.stock_movements USING btree (variant_id);


--
-- Name: idx_stock_receipt_item_ratings_attribute; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_stock_receipt_item_ratings_attribute ON public.stock_receipt_item_ratings USING btree (attribute_type_id);


--
-- Name: idx_stock_receipt_item_ratings_item; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_stock_receipt_item_ratings_item ON public.stock_receipt_item_ratings USING btree (stock_receipt_item_id);


--
-- Name: idx_stock_receipt_items_receipt; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_stock_receipt_items_receipt ON public.stock_receipt_items USING btree (stock_receipt_id);


--
-- Name: idx_stock_receipt_items_variant; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_stock_receipt_items_variant ON public.stock_receipt_items USING btree (variant_id);


--
-- Name: idx_stock_receipts_number; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_stock_receipts_number ON public.stock_receipts USING btree (receipt_number);


--
-- Name: idx_stock_receipts_supplier; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_stock_receipts_supplier ON public.stock_receipts USING btree (supplier_id);


--
-- Name: idx_system_settings_key; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_system_settings_key ON public.system_settings USING btree (key);


--
-- Name: idx_user_sessions_login_at; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_user_sessions_login_at ON public.user_sessions USING btree (login_at);


--
-- Name: idx_user_sessions_user_id; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_user_sessions_user_id ON public.user_sessions USING btree (user_id);


--
-- Name: idx_variant_remaining; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_variant_remaining ON public.stock_batches USING btree (variant_id, remaining_quantity);


--
-- Name: installment_transactions_installment_id_payment_date_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX installment_transactions_installment_id_payment_date_index ON public.installment_transactions USING btree (installment_id, payment_date);


--
-- Name: jobs_queue_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX jobs_queue_index ON public.jobs USING btree (queue);


--
-- Name: personal_access_tokens_expires_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX personal_access_tokens_expires_at_index ON public.personal_access_tokens USING btree (expires_at);


--
-- Name: personal_access_tokens_tokenable_type_tokenable_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX personal_access_tokens_tokenable_type_tokenable_id_index ON public.personal_access_tokens USING btree (tokenable_type, tokenable_id);


--
-- Name: reservation_deposits_reservation_id_payment_date_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX reservation_deposits_reservation_id_payment_date_index ON public.reservation_deposits USING btree (reservation_id, payment_date);


--
-- Name: sessions_last_activity_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX sessions_last_activity_index ON public.sessions USING btree (last_activity);


--
-- Name: sessions_user_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX sessions_user_id_index ON public.sessions USING btree (user_id);


--
-- Name: stock_movements_batch_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX stock_movements_batch_id_index ON public.stock_movements USING btree (batch_id);


--
-- Name: product_variant_locations trg_pvl_delete_update_stock; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER trg_pvl_delete_update_stock AFTER DELETE ON public.product_variant_locations FOR EACH ROW EXECUTE FUNCTION public.recalculate_variant_stock();


--
-- Name: product_variant_locations trg_pvl_insert_update_stock; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER trg_pvl_insert_update_stock AFTER INSERT OR UPDATE OF quantity ON public.product_variant_locations FOR EACH ROW EXECUTE FUNCTION public.recalculate_variant_stock();


--
-- Name: credits trigger_release_credit_stock; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER trigger_release_credit_stock AFTER UPDATE ON public.credits FOR EACH ROW EXECUTE FUNCTION public.release_credit_stock();


--
-- Name: stock_receipt_item_ratings trigger_update_supplier_quality; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER trigger_update_supplier_quality AFTER INSERT ON public.stock_receipt_item_ratings FOR EACH ROW EXECUTE FUNCTION public.update_supplier_quality_rating();


--
-- Name: stock_receipts trigger_update_supplier_stats; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER trigger_update_supplier_stats AFTER UPDATE ON public.stock_receipts FOR EACH ROW EXECUTE FUNCTION public.update_supplier_stats_on_receipt();


--
-- Name: customers update_customers_updated_at; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER update_customers_updated_at BEFORE UPDATE ON public.customers FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column();


--
-- Name: product_variants update_product_variants_updated_at; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER update_product_variants_updated_at BEFORE UPDATE ON public.product_variants FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column();


--
-- Name: products update_products_updated_at; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER update_products_updated_at BEFORE UPDATE ON public.products FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column();


--
-- Name: sales update_sales_updated_at; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER update_sales_updated_at BEFORE UPDATE ON public.sales FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column();


--
-- Name: users update_users_updated_at; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER update_users_updated_at BEFORE UPDATE ON public.users FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column();


--
-- Name: account_transactions account_transactions_account_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.account_transactions
    ADD CONSTRAINT account_transactions_account_id_fkey FOREIGN KEY (account_id) REFERENCES public.accounts(id);


--
-- Name: account_transactions account_transactions_created_by_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.account_transactions
    ADD CONSTRAINT account_transactions_created_by_fkey FOREIGN KEY (created_by) REFERENCES public.users(id);


--
-- Name: account_transactions account_transactions_expense_category_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.account_transactions
    ADD CONSTRAINT account_transactions_expense_category_id_fkey FOREIGN KEY (expense_category_id) REFERENCES public.expense_categories(id);


--
-- Name: account_transactions account_transactions_freight_forwarder_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.account_transactions
    ADD CONSTRAINT account_transactions_freight_forwarder_id_fkey FOREIGN KEY (freight_forwarder_id) REFERENCES public.freight_forwarders(id);


--
-- Name: account_transactions account_transactions_related_account_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.account_transactions
    ADD CONSTRAINT account_transactions_related_account_id_fkey FOREIGN KEY (related_account_id) REFERENCES public.accounts(id);


--
-- Name: account_transactions account_transactions_related_transaction_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.account_transactions
    ADD CONSTRAINT account_transactions_related_transaction_id_fkey FOREIGN KEY (related_transaction_id) REFERENCES public.account_transactions(id);


--
-- Name: account_transactions account_transactions_reversed_transaction_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.account_transactions
    ADD CONSTRAINT account_transactions_reversed_transaction_id_foreign FOREIGN KEY (reversed_transaction_id) REFERENCES public.account_transactions(id) ON DELETE SET NULL;


--
-- Name: account_transactions account_transactions_sale_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.account_transactions
    ADD CONSTRAINT account_transactions_sale_id_fkey FOREIGN KEY (sale_id) REFERENCES public.sales(id);


--
-- Name: account_transactions account_transactions_stock_receipt_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.account_transactions
    ADD CONSTRAINT account_transactions_stock_receipt_id_fkey FOREIGN KEY (stock_receipt_id) REFERENCES public.stock_receipts(id);


--
-- Name: account_transactions account_transactions_supplier_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.account_transactions
    ADD CONSTRAINT account_transactions_supplier_id_fkey FOREIGN KEY (supplier_id) REFERENCES public.suppliers(id);


--
-- Name: account_transactions account_transactions_transaction_type_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.account_transactions
    ADD CONSTRAINT account_transactions_transaction_type_id_fkey FOREIGN KEY (transaction_type_id) REFERENCES public.transaction_types(id);


--
-- Name: accounts accounts_account_type_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.accounts
    ADD CONSTRAINT accounts_account_type_id_fkey FOREIGN KEY (account_type_id) REFERENCES public.account_types(id);


--
-- Name: accounts accounts_created_by_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.accounts
    ADD CONSTRAINT accounts_created_by_fkey FOREIGN KEY (created_by) REFERENCES public.users(id);


--
-- Name: attribute_values attribute_values_attribute_type_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.attribute_values
    ADD CONSTRAINT attribute_values_attribute_type_id_fkey FOREIGN KEY (attribute_type_id) REFERENCES public.attribute_types(id) ON DELETE CASCADE;


--
-- Name: audit_logs audit_logs_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.audit_logs
    ADD CONSTRAINT audit_logs_user_id_fkey FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: cash_count_denominations cash_count_denominations_cash_count_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cash_count_denominations
    ADD CONSTRAINT cash_count_denominations_cash_count_id_foreign FOREIGN KEY (cash_count_id) REFERENCES public.cash_counts(id) ON DELETE CASCADE;


--
-- Name: cash_counts cash_counts_created_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cash_counts
    ADD CONSTRAINT cash_counts_created_by_foreign FOREIGN KEY (created_by) REFERENCES public.users(id) ON DELETE RESTRICT;


--
-- Name: categories categories_parent_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.categories
    ADD CONSTRAINT categories_parent_id_fkey FOREIGN KEY (parent_id) REFERENCES public.categories(id) ON DELETE SET NULL;


--
-- Name: credit_installments credit_installments_credit_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.credit_installments
    ADD CONSTRAINT credit_installments_credit_id_fkey FOREIGN KEY (credit_id) REFERENCES public.credits(id) ON DELETE CASCADE;


--
-- Name: credits credits_customer_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.credits
    ADD CONSTRAINT credits_customer_id_fkey FOREIGN KEY (customer_id) REFERENCES public.customers(id) ON DELETE RESTRICT;


--
-- Name: credits credits_sale_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.credits
    ADD CONSTRAINT credits_sale_id_fkey FOREIGN KEY (sale_id) REFERENCES public.sales(id) ON DELETE CASCADE;


--
-- Name: currency_rates currency_rates_created_by_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.currency_rates
    ADD CONSTRAINT currency_rates_created_by_fkey FOREIGN KEY (created_by) REFERENCES public.users(id);


--
-- Name: product_variant_locations fk_location; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.product_variant_locations
    ADD CONSTRAINT fk_location FOREIGN KEY (location_id) REFERENCES public.locations(id) ON DELETE RESTRICT;


--
-- Name: product_variant_locations fk_variant; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.product_variant_locations
    ADD CONSTRAINT fk_variant FOREIGN KEY (variant_id) REFERENCES public.product_variants(id) ON DELETE CASCADE;


--
-- Name: freight_forwarders freight_forwarders_coordinate_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.freight_forwarders
    ADD CONSTRAINT freight_forwarders_coordinate_id_foreign FOREIGN KEY (coordinate_id) REFERENCES public.coordinates(id) ON DELETE SET NULL;


--
-- Name: installment_transactions installment_transactions_installment_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.installment_transactions
    ADD CONSTRAINT installment_transactions_installment_id_foreign FOREIGN KEY (installment_id) REFERENCES public.credit_installments(id) ON DELETE CASCADE;


--
-- Name: installment_transactions installment_transactions_transaction_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.installment_transactions
    ADD CONSTRAINT installment_transactions_transaction_id_foreign FOREIGN KEY (transaction_id) REFERENCES public.account_transactions(id) ON DELETE CASCADE;


--
-- Name: notification_preferences notification_preferences_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.notification_preferences
    ADD CONSTRAINT notification_preferences_user_id_fkey FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: notifications notifications_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.notifications
    ADD CONSTRAINT notifications_user_id_fkey FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: product_attributes product_attributes_attribute_type_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.product_attributes
    ADD CONSTRAINT product_attributes_attribute_type_id_fkey FOREIGN KEY (attribute_type_id) REFERENCES public.attribute_types(id) ON DELETE RESTRICT;


--
-- Name: product_attributes product_attributes_product_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.product_attributes
    ADD CONSTRAINT product_attributes_product_id_fkey FOREIGN KEY (product_id) REFERENCES public.products(id) ON DELETE CASCADE;


--
-- Name: product_variants product_variants_product_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.product_variants
    ADD CONSTRAINT product_variants_product_id_fkey FOREIGN KEY (product_id) REFERENCES public.products(id) ON DELETE CASCADE;


--
-- Name: products products_category_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.products
    ADD CONSTRAINT products_category_id_fkey FOREIGN KEY (category_id) REFERENCES public.categories(id) ON DELETE RESTRICT;


--
-- Name: products products_subcategory_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.products
    ADD CONSTRAINT products_subcategory_id_fkey FOREIGN KEY (subcategory_id) REFERENCES public.categories(id) ON DELETE SET NULL;


--
-- Name: reservation_deposits reservation_deposits_reservation_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reservation_deposits
    ADD CONSTRAINT reservation_deposits_reservation_id_foreign FOREIGN KEY (reservation_id) REFERENCES public.reservations(id) ON DELETE CASCADE;


--
-- Name: reservation_deposits reservation_deposits_transaction_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reservation_deposits
    ADD CONSTRAINT reservation_deposits_transaction_id_foreign FOREIGN KEY (transaction_id) REFERENCES public.account_transactions(id) ON DELETE CASCADE;


--
-- Name: reservations reservations_customer_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reservations
    ADD CONSTRAINT reservations_customer_id_fkey FOREIGN KEY (customer_id) REFERENCES public.customers(id) ON DELETE RESTRICT;


--
-- Name: reservations reservations_sale_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reservations
    ADD CONSTRAINT reservations_sale_id_fkey FOREIGN KEY (sale_id) REFERENCES public.sales(id) ON DELETE CASCADE;


--
-- Name: reservations reservations_transaction_complete_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reservations
    ADD CONSTRAINT reservations_transaction_complete_id_fkey FOREIGN KEY (transaction_complete_id) REFERENCES public.account_transactions(id);


--
-- Name: sale_item_batches sale_item_batches_batch_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sale_item_batches
    ADD CONSTRAINT sale_item_batches_batch_id_foreign FOREIGN KEY (batch_id) REFERENCES public.stock_batches(id) ON DELETE RESTRICT;


--
-- Name: sale_item_batches sale_item_batches_location_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sale_item_batches
    ADD CONSTRAINT sale_item_batches_location_id_fkey FOREIGN KEY (location_id) REFERENCES public.locations(id);


--
-- Name: sale_item_batches sale_item_batches_sale_item_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sale_item_batches
    ADD CONSTRAINT sale_item_batches_sale_item_id_foreign FOREIGN KEY (sale_item_id) REFERENCES public.sale_items(id) ON DELETE CASCADE;


--
-- Name: sale_items sale_items_sale_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sale_items
    ADD CONSTRAINT sale_items_sale_id_fkey FOREIGN KEY (sale_id) REFERENCES public.sales(id) ON DELETE CASCADE;


--
-- Name: sale_items sale_items_variant_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sale_items
    ADD CONSTRAINT sale_items_variant_id_fkey FOREIGN KEY (variant_id) REFERENCES public.product_variants(id) ON DELETE RESTRICT;


--
-- Name: sales sales_customer_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sales
    ADD CONSTRAINT sales_customer_id_fkey FOREIGN KEY (customer_id) REFERENCES public.customers(id) ON DELETE SET NULL;


--
-- Name: sales sales_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sales
    ADD CONSTRAINT sales_user_id_fkey FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE RESTRICT;


--
-- Name: stock_batches stock_batches_stock_receipt_item_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.stock_batches
    ADD CONSTRAINT stock_batches_stock_receipt_item_id_foreign FOREIGN KEY (stock_receipt_item_id) REFERENCES public.stock_receipt_items(id) ON DELETE SET NULL;


--
-- Name: stock_batches stock_batches_variant_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.stock_batches
    ADD CONSTRAINT stock_batches_variant_id_foreign FOREIGN KEY (variant_id) REFERENCES public.product_variants(id) ON DELETE CASCADE;


--
-- Name: stock_movements stock_movements_from_location_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.stock_movements
    ADD CONSTRAINT stock_movements_from_location_id_fkey FOREIGN KEY (from_location_id) REFERENCES public.locations(id) ON DELETE RESTRICT;


--
-- Name: stock_movements stock_movements_performed_by_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.stock_movements
    ADD CONSTRAINT stock_movements_performed_by_fkey FOREIGN KEY (performed_by) REFERENCES public.users(id) ON DELETE RESTRICT;


--
-- Name: stock_movements stock_movements_sale_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.stock_movements
    ADD CONSTRAINT stock_movements_sale_id_fkey FOREIGN KEY (sale_id) REFERENCES public.sales(id) ON DELETE SET NULL;


--
-- Name: stock_movements stock_movements_stock_receipt_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.stock_movements
    ADD CONSTRAINT stock_movements_stock_receipt_id_fkey FOREIGN KEY (stock_receipt_id) REFERENCES public.stock_receipts(id) ON DELETE SET NULL;


--
-- Name: stock_movements stock_movements_to_location_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.stock_movements
    ADD CONSTRAINT stock_movements_to_location_id_fkey FOREIGN KEY (to_location_id) REFERENCES public.locations(id) ON DELETE RESTRICT;


--
-- Name: stock_movements stock_movements_variant_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.stock_movements
    ADD CONSTRAINT stock_movements_variant_id_fkey FOREIGN KEY (variant_id) REFERENCES public.product_variants(id) ON DELETE RESTRICT;


--
-- Name: stock_receipt_item_ratings stock_receipt_item_ratings_attribute_type_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.stock_receipt_item_ratings
    ADD CONSTRAINT stock_receipt_item_ratings_attribute_type_id_fkey FOREIGN KEY (attribute_type_id) REFERENCES public.attribute_types(id);


--
-- Name: stock_receipt_item_ratings stock_receipt_item_ratings_rated_by_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.stock_receipt_item_ratings
    ADD CONSTRAINT stock_receipt_item_ratings_rated_by_fkey FOREIGN KEY (rated_by) REFERENCES public.users(id);


--
-- Name: stock_receipt_item_ratings stock_receipt_item_ratings_stock_receipt_item_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.stock_receipt_item_ratings
    ADD CONSTRAINT stock_receipt_item_ratings_stock_receipt_item_id_fkey FOREIGN KEY (stock_receipt_item_id) REFERENCES public.stock_receipt_items(id) ON DELETE CASCADE;


--
-- Name: stock_receipt_items stock_receipt_items_stock_receipt_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.stock_receipt_items
    ADD CONSTRAINT stock_receipt_items_stock_receipt_id_fkey FOREIGN KEY (stock_receipt_id) REFERENCES public.stock_receipts(id) ON DELETE CASCADE;


--
-- Name: stock_receipt_items stock_receipt_items_variant_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.stock_receipt_items
    ADD CONSTRAINT stock_receipt_items_variant_id_fkey FOREIGN KEY (variant_id) REFERENCES public.product_variants(id) ON DELETE RESTRICT;


--
-- Name: stock_receipts stock_receipts_cost_validated_by_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.stock_receipts
    ADD CONSTRAINT stock_receipts_cost_validated_by_fkey FOREIGN KEY (cost_validated_by) REFERENCES public.users(id);


--
-- Name: stock_receipts stock_receipts_created_by_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.stock_receipts
    ADD CONSTRAINT stock_receipts_created_by_fkey FOREIGN KEY (created_by) REFERENCES public.users(id);


--
-- Name: stock_receipts stock_receipts_freight_forwarder_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.stock_receipts
    ADD CONSTRAINT stock_receipts_freight_forwarder_id_fkey FOREIGN KEY (freight_forwarder_id) REFERENCES public.freight_forwarders(id) ON DELETE SET NULL;


--
-- Name: stock_receipts stock_receipts_supplier_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.stock_receipts
    ADD CONSTRAINT stock_receipts_supplier_id_fkey FOREIGN KEY (supplier_id) REFERENCES public.suppliers(id) ON DELETE RESTRICT;


--
-- Name: suppliers suppliers_coordinate_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.suppliers
    ADD CONSTRAINT suppliers_coordinate_id_foreign FOREIGN KEY (coordinate_id) REFERENCES public.coordinates(id) ON DELETE SET NULL;


--
-- Name: system_settings system_settings_updated_by_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.system_settings
    ADD CONSTRAINT system_settings_updated_by_fkey FOREIGN KEY (updated_by) REFERENCES public.users(id);


--
-- Name: user_sessions user_sessions_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_sessions
    ADD CONSTRAINT user_sessions_user_id_fkey FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: variant_attribute_values variant_attribute_values_attribute_value_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.variant_attribute_values
    ADD CONSTRAINT variant_attribute_values_attribute_value_id_fkey FOREIGN KEY (attribute_value_id) REFERENCES public.attribute_values(id) ON DELETE RESTRICT;


--
-- Name: variant_attribute_values variant_attribute_values_variant_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.variant_attribute_values
    ADD CONSTRAINT variant_attribute_values_variant_id_fkey FOREIGN KEY (variant_id) REFERENCES public.product_variants(id) ON DELETE CASCADE;


--
-- PostgreSQL database dump complete
--

\unrestrict Kq5zuGwPNlRY2mgR2wI3GaF3kkWsmd2qAZugayoyXqprvjOaNtXnK02rZH9R3PS

