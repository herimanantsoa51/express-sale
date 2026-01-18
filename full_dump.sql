--
-- PostgreSQL database dump
--

\restrict pBqctdlmIuYKYpdMjqJLVBeG8CQsgnijqPlFGmHNuw25bndcyQFOckGJzyP7JCC

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
-- Data for Name: account_types; Type: TABLE DATA; Schema: public; Owner: express_sale_user
--

SET SESSION AUTHORIZATION DEFAULT;

ALTER TABLE public.account_types DISABLE TRIGGER ALL;

INSERT INTO public.account_types (id, code, name, display_name, description, created_at) VALUES (1, 'CASH', 'cash', 'Espèces', 'Compte en espèces (liquide)', '2025-12-28 11:35:31.68283');
INSERT INTO public.account_types (id, code, name, display_name, description, created_at) VALUES (2, 'MOBILE_MONEY', 'mobile_money', 'Mobile Money', 'Compte Mobile Money (MVola, Orange Money, etc.)', '2025-12-28 11:35:31.68283');
INSERT INTO public.account_types (id, code, name, display_name, description, created_at) VALUES (3, 'BANK', 'bank', 'Banque', 'Compte bancaire', '2025-12-28 11:35:31.68283');


ALTER TABLE public.account_types ENABLE TRIGGER ALL;

--
-- Data for Name: users; Type: TABLE DATA; Schema: public; Owner: express_sale_user
--

ALTER TABLE public.users DISABLE TRIGGER ALL;

INSERT INTO public.users (id, name, username, password, role, is_active, created_at, updated_at) VALUES (3, 'chirstian', 'herimanantsoa', '$2y$12$JIz2z14FuV4vde7Y6TaXluB0WnUR5hLquq3keQZ5raES/bbfA/rAu', 'vendeur', true, '2025-12-19 13:08:48', '2025-12-19 13:08:48');
INSERT INTO public.users (id, name, username, password, role, is_active, created_at, updated_at) VALUES (4, 'Administrateur', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', true, '2025-12-27 10:28:54.853759', '2025-12-27 10:28:54.853759');
INSERT INTO public.users (id, name, username, password, role, is_active, created_at, updated_at) VALUES (7, 'heyo', 'coco', '$2y$12$Wxu2QCrRxLuAEA7VTzt0VOmpXq/i5xW5Ydm7pkc23SuYfkD1AeQ4a', 'vendeur', true, '2026-01-16 07:22:50', '2026-01-16 11:44:00.135271');
INSERT INTO public.users (id, name, username, password, role, is_active, created_at, updated_at) VALUES (2, 'christianHerimanantsoa', 'herimanantsoa51', '$2y$12$M8EzX7//xt8Iu4owbXvBDej4KRZ0rDR9WqUhIHk8PFgTR8Pexz.vG', 'vendeur', true, '2025-12-19 11:09:55', '2026-01-16 11:50:50.889489');


ALTER TABLE public.users ENABLE TRIGGER ALL;

--
-- Data for Name: accounts; Type: TABLE DATA; Schema: public; Owner: express_sale_user
--

ALTER TABLE public.accounts DISABLE TRIGGER ALL;

INSERT INTO public.accounts (id, account_type_id, name, account_number, initial_balance, current_balance, notes, is_active, created_by, created_at, updated_at) VALUES (8, 3, 'BNI', '0000000000000000000000000000000', 390000.00, 224873.60, NULL, true, 4, '2026-01-14 09:31:35.533215', '2026-01-14 08:43:15');
INSERT INTO public.accounts (id, account_type_id, name, account_number, initial_balance, current_balance, notes, is_active, created_by, created_at, updated_at) VALUES (6, 1, 'Caisse Principale', NULL, 4000000.00, 139000.00, NULL, true, 4, '2026-01-14 09:30:29.12405', '2026-01-14 10:47:54');
INSERT INTO public.accounts (id, account_type_id, name, account_number, initial_balance, current_balance, notes, is_active, created_by, created_at, updated_at) VALUES (10, 3, 'BMOI', '1526182719289180', 4500000.00, 6200000.00, NULL, true, 4, '2026-01-14 13:39:55.228949', '2026-01-14 10:51:17');
INSERT INTO public.accounts (id, account_type_id, name, account_number, initial_balance, current_balance, notes, is_active, created_by, created_at, updated_at) VALUES (9, 1, 'CAISSE numero 2', NULL, 100000.00, 81000.00, NULL, true, 4, '2026-01-14 13:37:33.707327', '2026-01-16 06:43:36');
INSERT INTO public.accounts (id, account_type_id, name, account_number, initial_balance, current_balance, notes, is_active, created_by, created_at, updated_at) VALUES (7, 2, 'Mvola Shop', '0340412233', 30000000.00, 30778400.00, NULL, true, 4, '2026-01-14 09:31:00.38502', '2026-01-16 08:47:02');


ALTER TABLE public.accounts ENABLE TRIGGER ALL;

--
-- Data for Name: coordinates; Type: TABLE DATA; Schema: public; Owner: express_sale_user
--

ALTER TABLE public.coordinates DISABLE TRIGGER ALL;

INSERT INTO public.coordinates (id, country, city, is_active, created_at, updated_at) VALUES (1, 'Madagascar', 'Antananarivo', true, '2025-12-27 14:50:08', '2025-12-27 14:50:08');
INSERT INTO public.coordinates (id, country, city, is_active, created_at, updated_at) VALUES (2, 'MADAGASCAR', 'Antananarivo', true, '2025-12-27 15:15:24', '2025-12-27 15:15:24');
INSERT INTO public.coordinates (id, country, city, is_active, created_at, updated_at) VALUES (3, 'Madagascar', 'Antsirabe', true, '2025-12-27 15:34:46', '2025-12-27 15:34:46');
INSERT INTO public.coordinates (id, country, city, is_active, created_at, updated_at) VALUES (4, 'Chine', 'mandeha', true, '2025-12-27 15:35:06', '2025-12-27 15:35:06');
INSERT INTO public.coordinates (id, country, city, is_active, created_at, updated_at) VALUES (5, 'Madagascar', 'Fianarantsoa', true, '2025-12-27 17:14:30', '2025-12-27 17:14:30');
INSERT INTO public.coordinates (id, country, city, is_active, created_at, updated_at) VALUES (6, 'Chine', 'Shenzen', true, '2026-01-14 06:32:27', '2026-01-14 06:32:27');
INSERT INTO public.coordinates (id, country, city, is_active, created_at, updated_at) VALUES (7, 'Chine', 'Pekin', true, '2026-01-14 08:25:52', '2026-01-14 08:25:52');


ALTER TABLE public.coordinates ENABLE TRIGGER ALL;

--
-- Data for Name: customers; Type: TABLE DATA; Schema: public; Owner: express_sale_user
--

ALTER TABLE public.customers DISABLE TRIGGER ALL;

INSERT INTO public.customers (id, name, phone, address, reliability_score, loyalty_points, credit_limit, notes, is_active, created_at, updated_at, customer_number) VALUES (10, 'Dupont', NULL, NULL, 5.00, 0, 0.00, NULL, true, '2026-01-14 06:41:35', '2026-01-14 06:41:35', 'CL-20260114-0004');
INSERT INTO public.customers (id, name, phone, address, reliability_score, loyalty_points, credit_limit, notes, is_active, created_at, updated_at, customer_number) VALUES (11, 'aaa', NULL, NULL, 5.00, 0, 0.00, NULL, true, '2026-01-14 10:01:27', '2026-01-14 10:01:27', 'CL-20260114-0005');
INSERT INTO public.customers (id, name, phone, address, reliability_score, loyalty_points, credit_limit, notes, is_active, created_at, updated_at, customer_number) VALUES (8, 'Jean Bas', NULL, NULL, 5.00, 760, 190000.00, '[2026-01-14 10:17] +760 points: Achat de 760000 Ar', true, '2026-01-14 06:38:51', '2026-01-14 13:17:57.979478', 'CL-20260114-0002');
INSERT INTO public.customers (id, name, phone, address, reliability_score, loyalty_points, credit_limit, notes, is_active, created_at, updated_at, customer_number) VALUES (12, 'Marc', NULL, NULL, 5.00, 0, 2000000000.00, NULL, true, '2026-01-14 10:23:22', '2026-01-14 13:23:39.985061', 'CL-20260114-0006');
INSERT INTO public.customers (id, name, phone, address, reliability_score, loyalty_points, credit_limit, notes, is_active, created_at, updated_at, customer_number) VALUES (7, 'Jean Dupont', NULL, NULL, 5.00, 534, 0.00, '[2026-01-14 06:38] +215 points: Achat de 215000 Ar
[2026-01-14 10:32] +319 points: Achat de 319000 Ar', true, '2026-01-14 06:38:27', '2026-01-14 13:32:16.610624', 'CL-20260114-0001');
INSERT INTO public.customers (id, name, phone, address, reliability_score, loyalty_points, credit_limit, notes, is_active, created_at, updated_at, customer_number) VALUES (9, 'hafa mihitsy', NULL, NULL, 8.00, 0, 10000000.00, NULL, true, '2026-01-14 06:39:59', '2026-01-14 13:35:00.367875', 'CL-20260114-0003');
INSERT INTO public.customers (id, name, phone, address, reliability_score, loyalty_points, credit_limit, notes, is_active, created_at, updated_at, customer_number) VALUES (13, 'vaovao', NULL, NULL, 5.00, 0, 0.00, NULL, true, '2026-01-16 06:49:56', '2026-01-16 06:49:56', 'CL-20260116-0001');


ALTER TABLE public.customers ENABLE TRIGGER ALL;

--
-- Data for Name: expense_categories; Type: TABLE DATA; Schema: public; Owner: express_sale_user
--

ALTER TABLE public.expense_categories DISABLE TRIGGER ALL;

INSERT INTO public.expense_categories (id, name, description, icon, is_active, created_at, updated_at) VALUES (2, 'Électricité', 'Factures d''électricité', 'zap', true, '2025-12-28 11:35:31.745253', '2025-12-29 14:02:35.330082');
INSERT INTO public.expense_categories (id, name, description, icon, is_active, created_at, updated_at) VALUES (3, 'Eau', 'Factures d''eau', 'droplet', true, '2025-12-28 11:35:31.745253', '2025-12-29 14:02:35.330082');
INSERT INTO public.expense_categories (id, name, description, icon, is_active, created_at, updated_at) VALUES (4, 'Internet', 'Abonnement internet', 'wifi', true, '2025-12-28 11:35:31.745253', '2025-12-29 14:02:35.330082');
INSERT INTO public.expense_categories (id, name, description, icon, is_active, created_at, updated_at) VALUES (9, 'Maintenance', 'Réparations et maintenance', 'tool', true, '2025-12-28 11:35:31.745253', '2025-12-29 14:02:35.330082');
INSERT INTO public.expense_categories (id, name, description, icon, is_active, created_at, updated_at) VALUES (1, 'Loyer', 'Loyer du magasin/entrepôt', 'building', true, '2025-12-28 11:35:31.745253', '2025-12-29 11:02:39');
INSERT INTO public.expense_categories (id, name, description, icon, is_active, created_at, updated_at) VALUES (5, 'Salaires', 'Rémunération du personnel', 'users', true, '2025-12-28 11:35:31.745253', '2025-12-29 11:02:39');
INSERT INTO public.expense_categories (id, name, description, icon, is_active, created_at, updated_at) VALUES (7, 'Marketing', 'Publicité et promotion', 'megaphone', true, '2025-12-28 11:35:31.745253', '2025-12-29 11:02:39');
INSERT INTO public.expense_categories (id, name, description, icon, is_active, created_at, updated_at) VALUES (8, 'Fournitures', 'Fournitures de bureau et magasin', 'package', true, '2025-12-28 11:35:31.745253', '2025-12-29 11:02:39');
INSERT INTO public.expense_categories (id, name, description, icon, is_active, created_at, updated_at) VALUES (10, 'Taxes', 'Impôts et taxes', 'file-text', true, '2025-12-28 11:35:31.745253', '2025-12-29 11:02:39');
INSERT INTO public.expense_categories (id, name, description, icon, is_active, created_at, updated_at) VALUES (14, 'Assurance', 'Assurances diverses', 'shield', true, '2025-12-29 11:02:39', '2025-12-29 11:02:39');
INSERT INTO public.expense_categories (id, name, description, icon, is_active, created_at, updated_at) VALUES (11, 'Autres', 'Autres dépenses non catégorisées', 'more-horizontal', true, '2025-12-28 11:35:31.745253', '2025-12-29 11:02:39');
INSERT INTO public.expense_categories (id, name, description, icon, is_active, created_at, updated_at) VALUES (6, 'Transport & Transit', 'Frais de transport, transit et dédouanement', 'truck', true, '2025-12-28 11:35:31.745253', '2025-12-29 11:02:39');
INSERT INTO public.expense_categories (id, name, description, icon, is_active, created_at, updated_at) VALUES (15, 'Approvisionnement', 'Achat de marchandises auprès des fournisseurs', 'package', true, '2025-12-29 14:35:16.06127', '2025-12-29 14:35:16.06127');
INSERT INTO public.expense_categories (id, name, description, icon, is_active, created_at, updated_at) VALUES (16, 'mety be', NULL, 'more-horizontal', true, '2026-01-10 10:32:01', '2026-01-10 10:32:01');


ALTER TABLE public.expense_categories ENABLE TRIGGER ALL;

--
-- Data for Name: freight_forwarders; Type: TABLE DATA; Schema: public; Owner: express_sale_user
--

ALTER TABLE public.freight_forwarders DISABLE TRIGGER ALL;

INSERT INTO public.freight_forwarders (id, name, logo_url, contact, service_score, notes, is_active, created_at, updated_at, coordinate_id, total_shipments, total_items_shipped, total_items_delivered, service_rating_sum, service_rating_count, total_value_shipped, total_value_delivered, weighted_service_sum, total_weighted_shipment_value) VALUES (2, 'htm logistics', 'uploads/images/2026/01/f4885185-0d31-4dff-bd6c-417c3fa1b845.png', '+972918991', 10.00, NULL, true, '2026-01-14 06:32:46', '2026-01-14 08:53:23', 6, 4, 0, 0, 0.00, 0, 2713200.00, 2606100.00, 26061000.00, 2606100.00);


ALTER TABLE public.freight_forwarders ENABLE TRIGGER ALL;

--
-- Data for Name: sales; Type: TABLE DATA; Schema: public; Owner: express_sale_user
--

ALTER TABLE public.sales DISABLE TRIGGER ALL;

INSERT INTO public.sales (id, sale_number, customer_id, user_id, sale_date, sale_type, subtotal, discount_amount, discount_reason, total_amount, payment_status, notes, created_at, updated_at, payment_method) VALUES (28, 'VNT-20260114-0001', 7, 4, '2026-01-14 06:38:32', 'immediate', 218000.00, 3000.00, NULL, 215000.00, 'paid', NULL, '2026-01-14 06:38:32', '2026-01-14 06:38:32', 'cash');
INSERT INTO public.sales (id, sale_number, customer_id, user_id, sale_date, sale_type, subtotal, discount_amount, discount_reason, total_amount, payment_status, notes, created_at, updated_at, payment_method) VALUES (30, 'VNT-20260114-0003', 10, 4, '2026-01-14 06:41:49', 'reservation', 450000.00, 0.00, NULL, 450000.00, 'partial', NULL, '2026-01-14 06:41:49', '2026-01-14 06:41:49', 'mobile_money');
INSERT INTO public.sales (id, sale_number, customer_id, user_id, sale_date, sale_type, subtotal, discount_amount, discount_reason, total_amount, payment_status, notes, created_at, updated_at, payment_method) VALUES (31, 'VNT-20260114-0004', 8, 4, '2026-01-14 10:17:57', 'immediate', 770000.00, 10000.00, NULL, 760000.00, 'paid', NULL, '2026-01-14 10:17:57', '2026-01-14 10:17:57', 'mobile_money');
INSERT INTO public.sales (id, sale_number, customer_id, user_id, sale_date, sale_type, subtotal, discount_amount, discount_reason, total_amount, payment_status, notes, created_at, updated_at, payment_method) VALUES (32, 'VNT-20260114-0005', 12, 4, '2026-01-14 10:24:43', 'credit', 109000.00, 0.00, NULL, 109000.00, 'pending', NULL, '2026-01-14 10:24:43', '2026-01-14 10:24:43', NULL);
INSERT INTO public.sales (id, sale_number, customer_id, user_id, sale_date, sale_type, subtotal, discount_amount, discount_reason, total_amount, payment_status, notes, created_at, updated_at, payment_method) VALUES (33, 'VNT-20260114-0006', 7, 4, '2026-01-14 10:28:07', 'reservation', 320000.00, 1000.00, NULL, 319000.00, 'paid', NULL, '2026-01-14 10:28:07', '2026-01-14 13:32:16.610624', 'mobile_money');
INSERT INTO public.sales (id, sale_number, customer_id, user_id, sale_date, sale_type, subtotal, discount_amount, discount_reason, total_amount, payment_status, notes, created_at, updated_at, payment_method) VALUES (29, 'VNT-20260114-0002', 9, 4, '2026-01-14 06:40:35', 'credit', 90000.00, 0.00, NULL, 90000.00, 'paid', NULL, '2026-01-14 06:40:35', '2026-01-14 13:35:00.367875', NULL);
INSERT INTO public.sales (id, sale_number, customer_id, user_id, sale_date, sale_type, subtotal, discount_amount, discount_reason, total_amount, payment_status, notes, created_at, updated_at, payment_method) VALUES (34, 'VNT-20260116-0001', 9, 7, '2026-01-16 08:47:01', 'reservation', 619970.00, 2000.00, NULL, 617970.00, 'partial', NULL, '2026-01-16 08:47:01', '2026-01-16 08:47:01', 'mobile_money');


ALTER TABLE public.sales ENABLE TRIGGER ALL;

--
-- Data for Name: suppliers; Type: TABLE DATA; Schema: public; Owner: express_sale_user
--

ALTER TABLE public.suppliers DISABLE TRIGGER ALL;

INSERT INTO public.suppliers (id, name, wechat, profile, contact, accessibility_notes, reliability_score, logo_url, is_active, created_at, updated_at, coordinate_id, total_orders, total_items_ordered, total_items_received, quality_rating_sum, quality_rating_count, total_value_ordered, total_value_received, weighted_quality_sum, total_weighted_value) VALUES (1, 'Guangzhou_corpaaa', 'ajhsjhjash', 'fekzkjkjkjkejkjz', '+97291899111', 'eahjhejhejhejahjhjhh', 7.19, 'http://localhost:8000/storage/uploads/images/2025/12/e8d580c3-38b9-423f-925c-5eb8406fafad.jpeg', true, '2025-12-27 07:49:06', '2026-01-04 07:57:09', 5, 6, 205, 205, 223.00, 31, 1833856.00, 1833856.00, 29878351.53, 4073041.00);
INSERT INTO public.suppliers (id, name, wechat, profile, contact, accessibility_notes, reliability_score, logo_url, is_active, created_at, updated_at, coordinate_id, total_orders, total_items_ordered, total_items_received, quality_rating_sum, quality_rating_count, total_value_ordered, total_value_received, weighted_quality_sum, total_weighted_value) VALUES (4, 'HERIMANANTSOA Manitriniaina Christian', 'chirchri', NULL, '082893829', NULL, 5.00, 'uploads/images/2026/01/a4179a4f-d4af-4b46-8c2b-45c9db1d7b2f.png', true, '2026-01-13 20:47:35', '2026-01-13 20:47:35', 4, 0, 0, 0, 0.00, 0, 0.00, 0.00, 0.00, 0.00);
INSERT INTO public.suppliers (id, name, wechat, profile, contact, accessibility_notes, reliability_score, logo_url, is_active, created_at, updated_at, coordinate_id, total_orders, total_items_ordered, total_items_received, quality_rating_sum, quality_rating_count, total_value_ordered, total_value_received, weighted_quality_sum, total_weighted_value) VALUES (5, 'ghouangzou', 'fashion', NULL, '+97291899111', NULL, 5.00, 'uploads/images/2026/01/8cd3ceaf-511f-4b51-827a-c0a4ac16f99a.png', true, '2026-01-14 04:10:46', '2026-01-14 04:10:46', 3, 0, 0, 0, 0.00, 0, 0.00, 0.00, 0.00, 0.00);
INSERT INTO public.suppliers (id, name, wechat, profile, contact, accessibility_notes, reliability_score, logo_url, is_active, created_at, updated_at, coordinate_id, total_orders, total_items_ordered, total_items_received, quality_rating_sum, quality_rating_count, total_value_ordered, total_value_received, weighted_quality_sum, total_weighted_value) VALUES (6, 'dhhd', 'fashion', NULL, '+972918991', NULL, 5.00, 'uploads/images/2026/01/7cd544b9-a8e1-4bb9-ae78-df360ed2a1b5.png', true, '2026-01-14 04:13:10', '2026-01-14 04:14:53', 2, 0, 0, 0, 0.00, 0, 0.00, 0.00, 0.00, 0.00);
INSERT INTO public.suppliers (id, name, wechat, profile, contact, accessibility_notes, reliability_score, logo_url, is_active, created_at, updated_at, coordinate_id, total_orders, total_items_ordered, total_items_received, quality_rating_sum, quality_rating_count, total_value_ordered, total_value_received, weighted_quality_sum, total_weighted_value) VALUES (2, 'HERIMANANTSOA Manitriniaina Christian AA', 'ajhsjhjash', 'dkzjkjkjkjdkzj', '+97291899111', 'dizuhuiiuhiu"h"uh', 7.02, 'http://localhost:8000/storage/uploads/images/2025/12/00a857d2-dc4b-4694-993a-b3cc0e8872a9.jpeg', true, '2025-12-27 14:50:39', '2026-01-14 08:53:23', 1, 14, 234, 182, 423.00, 58, 3536100.00, 2647500.00, 33629620.00, 4530000.00);
INSERT INTO public.suppliers (id, name, wechat, profile, contact, accessibility_notes, reliability_score, logo_url, is_active, created_at, updated_at, coordinate_id, total_orders, total_items_ordered, total_items_received, quality_rating_sum, quality_rating_count, total_value_ordered, total_value_received, weighted_quality_sum, total_weighted_value) VALUES (3, 'raggathon', 'AZERTYUIOP', 'FZFHZGH', '+97291899111', 'ALKJLKJEEJA', 5.76, 'http://localhost:8000/storage/uploads/images/2025/12/6c8d3f45-5065-49ad-a382-ee2c0fe406a4.jpeg', true, '2025-12-27 15:35:55', '2026-01-14 06:36:37', 4, 8, 98, 69, 424.00, 57, 9430600.02, 7030600.02, 469954275.74, 82642700.09);
INSERT INTO public.suppliers (id, name, wechat, profile, contact, accessibility_notes, reliability_score, logo_url, is_active, created_at, updated_at, coordinate_id, total_orders, total_items_ordered, total_items_received, quality_rating_sum, quality_rating_count, total_value_ordered, total_value_received, weighted_quality_sum, total_weighted_value) VALUES (7, 'Uswell', 'uswell', NULL, '+166117289101', NULL, 5.00, NULL, true, '2026-01-14 08:27:35', '2026-01-16 06:33:31', 7, 0, 0, 0, 0.00, 0, 0.00, 0.00, 0.00, 0.00);


ALTER TABLE public.suppliers ENABLE TRIGGER ALL;

--
-- Data for Name: stock_receipts; Type: TABLE DATA; Schema: public; Owner: express_sale_user
--

ALTER TABLE public.stock_receipts DISABLE TRIGGER ALL;

INSERT INTO public.stock_receipts (id, receipt_number, supplier_id, freight_forwarder_id, total_cost_ariary, status, notes, created_by, created_at, updated_at, expected_delivery_date, actual_delivery_date, validated_at) VALUES (27, 'RCP-20260114-0001', 3, 2, 2295000.00, 'validated', NULL, 4, '2026-01-14 06:35:26', '2026-01-14 06:36:37', '2026-01-17', '2026-01-14 06:36:14', '2026-01-14 06:36:37');
INSERT INTO public.stock_receipts (id, receipt_number, supplier_id, freight_forwarder_id, total_cost_ariary, status, notes, created_by, created_at, updated_at, expected_delivery_date, actual_delivery_date, validated_at) VALUES (29, 'RCP-20260114-0003', 7, 2, 165126.40, 'cancelled', NULL, 4, '2026-01-14 08:39:28', '2026-01-14 08:43:15', '2026-02-08', NULL, NULL);
INSERT INTO public.stock_receipts (id, receipt_number, supplier_id, freight_forwarder_id, total_cost_ariary, status, notes, created_by, created_at, updated_at, expected_delivery_date, actual_delivery_date, validated_at) VALUES (28, 'RCP-20260114-0002', 7, 2, 165126.40, 'in_transit', NULL, 4, '2026-01-14 08:39:09', '2026-01-14 08:44:03', '2026-02-08', NULL, NULL);
INSERT INTO public.stock_receipts (id, receipt_number, supplier_id, freight_forwarder_id, total_cost_ariary, status, notes, created_by, created_at, updated_at, expected_delivery_date, actual_delivery_date, validated_at) VALUES (30, 'RCP-20260114-0004', 2, 2, 311100.00, 'validated', NULL, 4, '2026-01-14 08:46:35', '2026-01-14 08:53:23', '2026-01-25', '2026-01-14 08:47:36', '2026-01-14 08:53:23');


ALTER TABLE public.stock_receipts ENABLE TRIGGER ALL;

--
-- Data for Name: transaction_types; Type: TABLE DATA; Schema: public; Owner: express_sale_user
--

ALTER TABLE public.transaction_types DISABLE TRIGGER ALL;

INSERT INTO public.transaction_types (id, code, name, display_name, category, description, created_at, updated_at) VALUES (15, 'OPENING_BALANCE', 'opening_balance', 'Solde initial', 'income', 'Solde initial du compte', '2025-12-29 14:34:13.404169', '2025-12-29 14:34:13.404169');
INSERT INTO public.transaction_types (id, code, name, display_name, category, description, created_at, updated_at) VALUES (16, 'INCOME', 'income', 'Revenu', 'income', 'Entrée d’argent', '2025-12-29 14:34:13.404169', '2025-12-29 14:34:13.404169');
INSERT INTO public.transaction_types (id, code, name, display_name, category, description, created_at, updated_at) VALUES (17, 'EXPENSE', 'expense', 'Dépense', 'expense', 'Sortie d’argent', '2025-12-29 14:34:13.404169', '2025-12-29 14:34:13.404169');
INSERT INTO public.transaction_types (id, code, name, display_name, category, description, created_at, updated_at) VALUES (18, 'TRANSFER', 'transfer', 'Transfert', 'transfer', 'Transfert entre comptes', '2025-12-29 14:34:13.404169', '2025-12-29 14:34:13.404169');
INSERT INTO public.transaction_types (id, code, name, display_name, category, description, created_at, updated_at) VALUES (19, 'REFUND', 'refund', 'Remboursement', 'expense', 'Remboursement effectué', '2025-12-29 14:34:13.404169', '2025-12-29 14:34:13.404169');
INSERT INTO public.transaction_types (id, code, name, display_name, category, description, created_at, updated_at) VALUES (21, 'REVERSAL', 'Annulation', 'Annulation', 'adjustment', 'Transaction d''annulation/contre-passation', '2026-01-04 09:12:22', '2026-01-04 09:12:22');


ALTER TABLE public.transaction_types ENABLE TRIGGER ALL;

--
-- Data for Name: account_transactions; Type: TABLE DATA; Schema: public; Owner: express_sale_user
--

ALTER TABLE public.account_transactions DISABLE TRIGGER ALL;

INSERT INTO public.account_transactions (id, account_id, transaction_type_id, amount, balance_before, balance_after, transaction_date, related_account_id, related_transaction_id, supplier_id, freight_forwarder_id, stock_receipt_id, expense_category_id, recipient_name, sale_id, reference_number, description, notes, created_by, created_at, reversed_transaction_id) VALUES (124, 6, 15, 4000000.00, 0.00, 4000000.00, '2026-01-14 09:30:29.12405', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Solde initial du compte', NULL, 4, '2026-01-14 09:30:29.12405', NULL);
INSERT INTO public.account_transactions (id, account_id, transaction_type_id, amount, balance_before, balance_after, transaction_date, related_account_id, related_transaction_id, supplier_id, freight_forwarder_id, stock_receipt_id, expense_category_id, recipient_name, sale_id, reference_number, description, notes, created_by, created_at, reversed_transaction_id) VALUES (125, 7, 15, 30000000.00, 0.00, 30000000.00, '2026-01-14 09:31:00.38502', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Solde initial du compte', NULL, 4, '2026-01-14 09:31:00.38502', NULL);
INSERT INTO public.account_transactions (id, account_id, transaction_type_id, amount, balance_before, balance_after, transaction_date, related_account_id, related_transaction_id, supplier_id, freight_forwarder_id, stock_receipt_id, expense_category_id, recipient_name, sale_id, reference_number, description, notes, created_by, created_at, reversed_transaction_id) VALUES (126, 8, 15, 390000.00, 0.00, 390000.00, '2026-01-14 09:31:35.533215', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Solde initial du compte', NULL, 4, '2026-01-14 09:31:35.533215', NULL);
INSERT INTO public.account_transactions (id, account_id, transaction_type_id, amount, balance_before, balance_after, transaction_date, related_account_id, related_transaction_id, supplier_id, freight_forwarder_id, stock_receipt_id, expense_category_id, recipient_name, sale_id, reference_number, description, notes, created_by, created_at, reversed_transaction_id) VALUES (127, 6, 17, 2295000.00, 4000000.00, 1705000.00, '2026-01-14 06:35:26', NULL, NULL, 3, NULL, 27, 15, 'raggathon', NULL, 'RE-20260114-000001', 'Paiement fournisseur raggathon - Réception #RCP-20260114-0001', NULL, 4, '2026-01-14 06:35:26', NULL);
INSERT INTO public.account_transactions (id, account_id, transaction_type_id, amount, balance_before, balance_after, transaction_date, related_account_id, related_transaction_id, supplier_id, freight_forwarder_id, stock_receipt_id, expense_category_id, recipient_name, sale_id, reference_number, description, notes, created_by, created_at, reversed_transaction_id) VALUES (128, 7, 17, 61200.00, 30000000.00, 29938800.00, '2026-01-14 06:35:26', NULL, NULL, NULL, 2, 27, 6, 'htm logistics', NULL, 'RE-20260114-000002', 'Paiement transitaire htm logistics - Réception #RCP-20260114-0001', NULL, 4, '2026-01-14 06:35:26', NULL);
INSERT INTO public.account_transactions (id, account_id, transaction_type_id, amount, balance_before, balance_after, transaction_date, related_account_id, related_transaction_id, supplier_id, freight_forwarder_id, stock_receipt_id, expense_category_id, recipient_name, sale_id, reference_number, description, notes, created_by, created_at, reversed_transaction_id) VALUES (129, 6, 16, 215000.00, 1705000.00, 1920000.00, '2026-01-14 06:38:32', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 28, 'FAC-20260114-000001', 'Vente #VNT-20260114-0001', '', 4, '2026-01-14 06:38:32', NULL);
INSERT INTO public.account_transactions (id, account_id, transaction_type_id, amount, balance_before, balance_after, transaction_date, related_account_id, related_transaction_id, supplier_id, freight_forwarder_id, stock_receipt_id, expense_category_id, recipient_name, sale_id, reference_number, description, notes, created_by, created_at, reversed_transaction_id) VALUES (130, 7, 16, 50000.00, 29938800.00, 29988800.00, '2026-01-14 06:41:49', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 30, 'FAC-20260114-000002', 'Acompte réservation #VNT-20260114-0003', '', 4, '2026-01-14 06:41:49', NULL);
INSERT INTO public.account_transactions (id, account_id, transaction_type_id, amount, balance_before, balance_after, transaction_date, related_account_id, related_transaction_id, supplier_id, freight_forwarder_id, stock_receipt_id, expense_category_id, recipient_name, sale_id, reference_number, description, notes, created_by, created_at, reversed_transaction_id) VALUES (131, 8, 17, 165126.40, 390000.00, 224873.60, '2026-01-14 08:39:09', NULL, NULL, 7, NULL, 28, 15, 'Uswell', NULL, 'RE-20260114-000003', 'Paiement fournisseur Uswell - Réception #RCP-20260114-0002', NULL, 4, '2026-01-14 08:39:09', NULL);
INSERT INTO public.account_transactions (id, account_id, transaction_type_id, amount, balance_before, balance_after, transaction_date, related_account_id, related_transaction_id, supplier_id, freight_forwarder_id, stock_receipt_id, expense_category_id, recipient_name, sale_id, reference_number, description, notes, created_by, created_at, reversed_transaction_id) VALUES (132, 8, 17, 165126.40, 224873.60, 59747.20, '2026-01-14 08:39:28', NULL, NULL, 7, NULL, 29, 15, 'Uswell', NULL, 'RE-20260114-000004', 'Paiement fournisseur Uswell - Réception #RCP-20260114-0003', NULL, 4, '2026-01-14 08:39:28', NULL);
INSERT INTO public.account_transactions (id, account_id, transaction_type_id, amount, balance_before, balance_after, transaction_date, related_account_id, related_transaction_id, supplier_id, freight_forwarder_id, stock_receipt_id, expense_category_id, recipient_name, sale_id, reference_number, description, notes, created_by, created_at, reversed_transaction_id) VALUES (133, 7, 17, 12160.00, 29988800.00, 29976640.00, '2026-01-14 08:39:28', NULL, NULL, NULL, 2, 29, 6, 'htm logistics', NULL, 'RE-20260114-000005', 'Paiement transitaire htm logistics - Réception #RCP-20260114-0003', NULL, 4, '2026-01-14 08:39:28', NULL);
INSERT INTO public.account_transactions (id, account_id, transaction_type_id, amount, balance_before, balance_after, transaction_date, related_account_id, related_transaction_id, supplier_id, freight_forwarder_id, stock_receipt_id, expense_category_id, recipient_name, sale_id, reference_number, description, notes, created_by, created_at, reversed_transaction_id) VALUES (134, 8, 21, 165126.40, 59747.20, 224873.60, '2026-01-14 08:43:15', NULL, NULL, 7, NULL, 29, 15, 'Uswell', NULL, 'REV-20260114-000001', 'Annulation: Paiement fournisseur Uswell - Réception #RCP-20260114-0003', 'Annulation de la transaction #RE-20260114-000004', 4, '2026-01-14 08:43:15', 132);
INSERT INTO public.account_transactions (id, account_id, transaction_type_id, amount, balance_before, balance_after, transaction_date, related_account_id, related_transaction_id, supplier_id, freight_forwarder_id, stock_receipt_id, expense_category_id, recipient_name, sale_id, reference_number, description, notes, created_by, created_at, reversed_transaction_id) VALUES (135, 7, 21, 12160.00, 29976640.00, 29988800.00, '2026-01-14 08:43:15', NULL, NULL, NULL, 2, 29, 6, 'htm logistics', NULL, 'REV-20260114-000002', 'Annulation: Paiement transitaire htm logistics - Réception #RCP-20260114-0003', 'Annulation de la transaction #RE-20260114-000005', 4, '2026-01-14 08:43:15', 133);
INSERT INTO public.account_transactions (id, account_id, transaction_type_id, amount, balance_before, balance_after, transaction_date, related_account_id, related_transaction_id, supplier_id, freight_forwarder_id, stock_receipt_id, expense_category_id, recipient_name, sale_id, reference_number, description, notes, created_by, created_at, reversed_transaction_id) VALUES (136, 7, 17, 418200.00, 29988800.00, 29570600.00, '2026-01-14 08:46:35', NULL, NULL, 2, NULL, 30, 15, 'HERIMANANTSOA Manitriniaina Christian AA', NULL, 'RE-20260114-000006', 'Paiement fournisseur HERIMANANTSOA Manitriniaina Christian AA - Réception #RCP-20260114-0004', NULL, 4, '2026-01-14 08:46:35', NULL);
INSERT INTO public.account_transactions (id, account_id, transaction_type_id, amount, balance_before, balance_after, transaction_date, related_account_id, related_transaction_id, supplier_id, freight_forwarder_id, stock_receipt_id, expense_category_id, recipient_name, sale_id, reference_number, description, notes, created_by, created_at, reversed_transaction_id) VALUES (137, 7, 17, 61200.00, 29570600.00, 29509400.00, '2026-01-14 08:46:35', NULL, NULL, NULL, 2, 30, 6, 'htm logistics', NULL, 'RE-20260114-000007', 'Paiement transitaire htm logistics - Réception #RCP-20260114-0004', NULL, 4, '2026-01-14 08:46:35', NULL);
INSERT INTO public.account_transactions (id, account_id, transaction_type_id, amount, balance_before, balance_after, transaction_date, related_account_id, related_transaction_id, supplier_id, freight_forwarder_id, stock_receipt_id, expense_category_id, recipient_name, sale_id, reference_number, description, notes, created_by, created_at, reversed_transaction_id) VALUES (138, 7, 16, 760000.00, 29509400.00, 30269400.00, '2026-01-14 10:17:58', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 31, 'FAC-20260114-000003', 'Vente #VNT-20260114-0004', '', 4, '2026-01-14 10:17:58', NULL);
INSERT INTO public.account_transactions (id, account_id, transaction_type_id, amount, balance_before, balance_after, transaction_date, related_account_id, related_transaction_id, supplier_id, freight_forwarder_id, stock_receipt_id, expense_category_id, recipient_name, sale_id, reference_number, description, notes, created_by, created_at, reversed_transaction_id) VALUES (139, 7, 16, 100000.00, 30269400.00, 30369400.00, '2026-01-14 10:28:07', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 33, 'FAC-20260114-000004', 'Acompte réservation #VNT-20260114-0006', '', 4, '2026-01-14 10:28:07', NULL);
INSERT INTO public.account_transactions (id, account_id, transaction_type_id, amount, balance_before, balance_after, transaction_date, related_account_id, related_transaction_id, supplier_id, freight_forwarder_id, stock_receipt_id, expense_category_id, recipient_name, sale_id, reference_number, description, notes, created_by, created_at, reversed_transaction_id) VALUES (140, 6, 16, 219000.00, 1920000.00, 2139000.00, '2026-01-14 10:32:16', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 33, 'FAC-20260114-000005', 'Paiement final réservation #VNT-20260114-0006', '', 4, '2026-01-14 10:32:16', NULL);
INSERT INTO public.account_transactions (id, account_id, transaction_type_id, amount, balance_before, balance_after, transaction_date, related_account_id, related_transaction_id, supplier_id, freight_forwarder_id, stock_receipt_id, expense_category_id, recipient_name, sale_id, reference_number, description, notes, created_by, created_at, reversed_transaction_id) VALUES (141, 7, 16, 10000.00, 30369400.00, 30379400.00, '2026-01-14 10:34:13', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 29, 'FAC-20260114-000006', 'Paiement crédit #VNT-20260114-0002 - Échéance #1', '', 4, '2026-01-14 10:34:13', NULL);
INSERT INTO public.account_transactions (id, account_id, transaction_type_id, amount, balance_before, balance_after, transaction_date, related_account_id, related_transaction_id, supplier_id, freight_forwarder_id, stock_receipt_id, expense_category_id, recipient_name, sale_id, reference_number, description, notes, created_by, created_at, reversed_transaction_id) VALUES (142, 7, 16, 80000.00, 30379400.00, 30459400.00, '2026-01-14 10:35:00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 29, 'FAC-20260114-000007', 'Paiement crédit #VNT-20260114-0002 - Échéance #1', '', 4, '2026-01-14 10:35:00', NULL);
INSERT INTO public.account_transactions (id, account_id, transaction_type_id, amount, balance_before, balance_after, transaction_date, related_account_id, related_transaction_id, supplier_id, freight_forwarder_id, stock_receipt_id, expense_category_id, recipient_name, sale_id, reference_number, description, notes, created_by, created_at, reversed_transaction_id) VALUES (143, 9, 15, 100000.00, 0.00, 100000.00, '2026-01-14 13:37:33.707327', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Solde initial du compte', NULL, 4, '2026-01-14 13:37:33.707327', NULL);
INSERT INTO public.account_transactions (id, account_id, transaction_type_id, amount, balance_before, balance_after, transaction_date, related_account_id, related_transaction_id, supplier_id, freight_forwarder_id, stock_receipt_id, expense_category_id, recipient_name, sale_id, reference_number, description, notes, created_by, created_at, reversed_transaction_id) VALUES (144, 10, 15, 4500000.00, 0.00, 4500000.00, '2026-01-14 13:39:55.228949', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Solde initial du compte', NULL, 4, '2026-01-14 13:39:55.228949', NULL);
INSERT INTO public.account_transactions (id, account_id, transaction_type_id, amount, balance_before, balance_after, transaction_date, related_account_id, related_transaction_id, supplier_id, freight_forwarder_id, stock_receipt_id, expense_category_id, recipient_name, sale_id, reference_number, description, notes, created_by, created_at, reversed_transaction_id) VALUES (146, 10, 18, 2000000.00, 4500000.00, 6500000.00, '2026-01-14 10:47:54', 6, 145, NULL, NULL, NULL, NULL, NULL, NULL, 'TRF-20260114-000001', 'Transfert de compte Caisse Principale', NULL, 4, '2026-01-14 10:47:54', NULL);
INSERT INTO public.account_transactions (id, account_id, transaction_type_id, amount, balance_before, balance_after, transaction_date, related_account_id, related_transaction_id, supplier_id, freight_forwarder_id, stock_receipt_id, expense_category_id, recipient_name, sale_id, reference_number, description, notes, created_by, created_at, reversed_transaction_id) VALUES (145, 6, 18, 2000000.00, 2139000.00, 139000.00, '2026-01-14 10:47:54', 10, 146, NULL, NULL, NULL, NULL, NULL, NULL, 'TRF-20260114-000001', 'Transfert vers compte BMOI', NULL, 4, '2026-01-14 10:47:54', NULL);
INSERT INTO public.account_transactions (id, account_id, transaction_type_id, amount, balance_before, balance_after, transaction_date, related_account_id, related_transaction_id, supplier_id, freight_forwarder_id, stock_receipt_id, expense_category_id, recipient_name, sale_id, reference_number, description, notes, created_by, created_at, reversed_transaction_id) VALUES (147, 10, 17, 300000.00, 6500000.00, 6200000.00, '2026-01-14 00:00:00', NULL, NULL, NULL, NULL, NULL, 5, 'Jean', NULL, 'RE-20260114-000008', 'Notes test', 'Notes test', 4, '2026-01-14 10:51:17', NULL);
INSERT INTO public.account_transactions (id, account_id, transaction_type_id, amount, balance_before, balance_after, transaction_date, related_account_id, related_transaction_id, supplier_id, freight_forwarder_id, stock_receipt_id, expense_category_id, recipient_name, sale_id, reference_number, description, notes, created_by, created_at, reversed_transaction_id) VALUES (149, 7, 18, 19000.00, 30459400.00, 30478400.00, '2026-01-16 06:43:36', 9, 148, NULL, NULL, NULL, NULL, NULL, NULL, 'TRF-20260116-000001', 'Transfert de compte CAISSE numero 2', NULL, 4, '2026-01-16 06:43:36', NULL);
INSERT INTO public.account_transactions (id, account_id, transaction_type_id, amount, balance_before, balance_after, transaction_date, related_account_id, related_transaction_id, supplier_id, freight_forwarder_id, stock_receipt_id, expense_category_id, recipient_name, sale_id, reference_number, description, notes, created_by, created_at, reversed_transaction_id) VALUES (148, 9, 18, 19000.00, 100000.00, 81000.00, '2026-01-16 06:43:36', 7, 149, NULL, NULL, NULL, NULL, NULL, NULL, 'TRF-20260116-000001', 'Transfert vers compte Mvola Shop', NULL, 4, '2026-01-16 06:43:36', NULL);
INSERT INTO public.account_transactions (id, account_id, transaction_type_id, amount, balance_before, balance_after, transaction_date, related_account_id, related_transaction_id, supplier_id, freight_forwarder_id, stock_receipt_id, expense_category_id, recipient_name, sale_id, reference_number, description, notes, created_by, created_at, reversed_transaction_id) VALUES (150, 7, 16, 300000.00, 30478400.00, 30778400.00, '2026-01-16 08:47:02', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 34, 'FAC-20260116-000001', 'Acompte réservation #VNT-20260116-0001', '', 7, '2026-01-16 08:47:02', NULL);


ALTER TABLE public.account_transactions ENABLE TRIGGER ALL;

--
-- Data for Name: attribute_types; Type: TABLE DATA; Schema: public; Owner: express_sale_user
--

ALTER TABLE public.attribute_types DISABLE TRIGGER ALL;

INSERT INTO public.attribute_types (id, name, display_name, input_type, created_at) VALUES (16, 'color', 'couleur', 'select', '2026-01-14 09:24:20.639543');
INSERT INTO public.attribute_types (id, name, display_name, input_type, created_at) VALUES (17, 'pointure', 'Pointure', 'number', '2026-01-14 09:24:41.363288');
INSERT INTO public.attribute_types (id, name, display_name, input_type, created_at) VALUES (18, 'size', 'Taille', 'select', '2026-01-14 09:28:55.488978');


ALTER TABLE public.attribute_types ENABLE TRIGGER ALL;

--
-- Data for Name: attribute_values; Type: TABLE DATA; Schema: public; Owner: express_sale_user
--

ALTER TABLE public.attribute_values DISABLE TRIGGER ALL;

INSERT INTO public.attribute_values (id, attribute_type_id, value, sort_order, created_at) VALUES (42, 16, 'Rouge', 1, '2026-01-14 09:24:20.644974');
INSERT INTO public.attribute_values (id, attribute_type_id, value, sort_order, created_at) VALUES (43, 16, 'vert', 2, '2026-01-14 09:24:20.651634');
INSERT INTO public.attribute_values (id, attribute_type_id, value, sort_order, created_at) VALUES (44, 16, 'bleu', 3, '2026-01-14 09:24:20.653824');
INSERT INTO public.attribute_values (id, attribute_type_id, value, sort_order, created_at) VALUES (45, 16, 'violet', 4, '2026-01-14 09:24:20.65591');
INSERT INTO public.attribute_values (id, attribute_type_id, value, sort_order, created_at) VALUES (46, 16, 'beige', 5, '2026-01-14 09:24:20.660694');
INSERT INTO public.attribute_values (id, attribute_type_id, value, sort_order, created_at) VALUES (47, 16, 'blanc', 6, '2026-01-14 09:24:20.665549');
INSERT INTO public.attribute_values (id, attribute_type_id, value, sort_order, created_at) VALUES (48, 16, 'noir', 7, '2026-01-14 09:24:20.667986');
INSERT INTO public.attribute_values (id, attribute_type_id, value, sort_order, created_at) VALUES (49, 16, 'jaune', 8, '2026-01-14 09:24:20.670009');
INSERT INTO public.attribute_values (id, attribute_type_id, value, sort_order, created_at) VALUES (50, 16, 'arc-en-ciel', 9, '2026-01-14 09:24:20.671911');
INSERT INTO public.attribute_values (id, attribute_type_id, value, sort_order, created_at) VALUES (51, 17, '39', 999, '2026-01-14 09:25:18.408059');
INSERT INTO public.attribute_values (id, attribute_type_id, value, sort_order, created_at) VALUES (52, 17, '41', 999, '2026-01-14 09:25:37.665946');
INSERT INTO public.attribute_values (id, attribute_type_id, value, sort_order, created_at) VALUES (53, 17, '30', 999, '2026-01-14 09:26:49.748382');
INSERT INTO public.attribute_values (id, attribute_type_id, value, sort_order, created_at) VALUES (54, 18, 'S', 1, '2026-01-14 09:28:55.491753');
INSERT INTO public.attribute_values (id, attribute_type_id, value, sort_order, created_at) VALUES (55, 18, 'M', 2, '2026-01-14 09:28:55.494349');
INSERT INTO public.attribute_values (id, attribute_type_id, value, sort_order, created_at) VALUES (56, 18, 'L', 3, '2026-01-14 09:28:55.499137');
INSERT INTO public.attribute_values (id, attribute_type_id, value, sort_order, created_at) VALUES (57, 18, 'XL', 4, '2026-01-14 09:28:55.502697');
INSERT INTO public.attribute_values (id, attribute_type_id, value, sort_order, created_at) VALUES (58, 18, 'XXL', 5, '2026-01-14 09:28:55.505305');


ALTER TABLE public.attribute_values ENABLE TRIGGER ALL;

--
-- Data for Name: audit_logs; Type: TABLE DATA; Schema: public; Owner: express_sale_user
--

ALTER TABLE public.audit_logs DISABLE TRIGGER ALL;



ALTER TABLE public.audit_logs ENABLE TRIGGER ALL;

--
-- Data for Name: cache; Type: TABLE DATA; Schema: public; Owner: express_sale_user
--

ALTER TABLE public.cache DISABLE TRIGGER ALL;



ALTER TABLE public.cache ENABLE TRIGGER ALL;

--
-- Data for Name: cache_locks; Type: TABLE DATA; Schema: public; Owner: express_sale_user
--

ALTER TABLE public.cache_locks DISABLE TRIGGER ALL;



ALTER TABLE public.cache_locks ENABLE TRIGGER ALL;

--
-- Data for Name: cash_counts; Type: TABLE DATA; Schema: public; Owner: express_sale_user
--

ALTER TABLE public.cash_counts DISABLE TRIGGER ALL;

INSERT INTO public.cash_counts (id, count_date, created_by, notes, created_at, updated_at, total_amount) VALUES (1, '2026-01-25', 4, 'Comptage caisse du matin', '2026-01-16 11:42:46', '2026-01-16 16:10:41', 343000);
INSERT INTO public.cash_counts (id, count_date, created_by, notes, created_at, updated_at, total_amount) VALUES (2, '2026-01-16', 4, NULL, '2026-01-16 16:53:46', '2026-01-16 16:53:46', 265000);


ALTER TABLE public.cash_counts ENABLE TRIGGER ALL;

--
-- Data for Name: cash_count_denominations; Type: TABLE DATA; Schema: public; Owner: express_sale_user
--

ALTER TABLE public.cash_count_denominations DISABLE TRIGGER ALL;

INSERT INTO public.cash_count_denominations (id, cash_count_id, denomination, quantity, subtotal, created_at, updated_at) VALUES (17, 1, 100, 20, 2000, '2026-01-16 16:10:41', '2026-01-16 16:10:41');
INSERT INTO public.cash_count_denominations (id, cash_count_id, denomination, quantity, subtotal, created_at, updated_at) VALUES (18, 1, 200, 15, 3000, '2026-01-16 16:10:41', '2026-01-16 16:10:41');
INSERT INTO public.cash_count_denominations (id, cash_count_id, denomination, quantity, subtotal, created_at, updated_at) VALUES (19, 1, 500, 10, 5000, '2026-01-16 16:10:41', '2026-01-16 16:10:41');
INSERT INTO public.cash_count_denominations (id, cash_count_id, denomination, quantity, subtotal, created_at, updated_at) VALUES (20, 1, 1000, 8, 8000, '2026-01-16 16:10:41', '2026-01-16 16:10:41');
INSERT INTO public.cash_count_denominations (id, cash_count_id, denomination, quantity, subtotal, created_at, updated_at) VALUES (21, 1, 2000, 5, 10000, '2026-01-16 16:10:41', '2026-01-16 16:10:41');
INSERT INTO public.cash_count_denominations (id, cash_count_id, denomination, quantity, subtotal, created_at, updated_at) VALUES (22, 1, 5000, 3, 15000, '2026-01-16 16:10:41', '2026-01-16 16:10:41');
INSERT INTO public.cash_count_denominations (id, cash_count_id, denomination, quantity, subtotal, created_at, updated_at) VALUES (23, 1, 10000, 10, 100000, '2026-01-16 16:10:41', '2026-01-16 16:10:41');
INSERT INTO public.cash_count_denominations (id, cash_count_id, denomination, quantity, subtotal, created_at, updated_at) VALUES (24, 1, 20000, 10, 200000, '2026-01-16 16:10:41', '2026-01-16 16:10:41');
INSERT INTO public.cash_count_denominations (id, cash_count_id, denomination, quantity, subtotal, created_at, updated_at) VALUES (25, 2, 20000, 12, 240000, '2026-01-16 16:53:46', '2026-01-16 16:53:46');
INSERT INTO public.cash_count_denominations (id, cash_count_id, denomination, quantity, subtotal, created_at, updated_at) VALUES (26, 2, 10000, 2, 20000, '2026-01-16 16:53:46', '2026-01-16 16:53:46');
INSERT INTO public.cash_count_denominations (id, cash_count_id, denomination, quantity, subtotal, created_at, updated_at) VALUES (27, 2, 5000, 1, 5000, '2026-01-16 16:53:46', '2026-01-16 16:53:46');


ALTER TABLE public.cash_count_denominations ENABLE TRIGGER ALL;

--
-- Data for Name: categories; Type: TABLE DATA; Schema: public; Owner: express_sale_user
--

ALTER TABLE public.categories DISABLE TRIGGER ALL;

INSERT INTO public.categories (id, name, description, parent_id, image_url, sort_order, created_at, updated_at) VALUES (21, 'kiraro', NULL, NULL, NULL, 0, '2026-01-14 06:22:13', '2026-01-14 06:22:13');
INSERT INTO public.categories (id, name, description, parent_id, image_url, sort_order, created_at, updated_at) VALUES (22, 'tennis', NULL, 21, NULL, 0, '2026-01-14 06:22:27', '2026-01-14 06:22:27');
INSERT INTO public.categories (id, name, description, parent_id, image_url, sort_order, created_at, updated_at) VALUES (23, 'Akanjo', NULL, NULL, NULL, 0, '2026-01-14 06:27:52', '2026-01-14 06:27:52');
INSERT INTO public.categories (id, name, description, parent_id, image_url, sort_order, created_at, updated_at) VALUES (24, 'Robe', NULL, 23, NULL, 0, '2026-01-14 06:28:00', '2026-01-14 06:28:00');
INSERT INTO public.categories (id, name, description, parent_id, image_url, sort_order, created_at, updated_at) VALUES (25, 'Haut', NULL, NULL, NULL, 0, '2026-01-14 08:13:51', '2026-01-14 08:13:51');
INSERT INTO public.categories (id, name, description, parent_id, image_url, sort_order, created_at, updated_at) VALUES (26, 'T-Shirt', NULL, 25, NULL, 0, '2026-01-14 08:14:14', '2026-01-14 08:14:14');


ALTER TABLE public.categories ENABLE TRIGGER ALL;

--
-- Data for Name: credits; Type: TABLE DATA; Schema: public; Owner: express_sale_user
--

ALTER TABLE public.credits DISABLE TRIGGER ALL;

INSERT INTO public.credits (id, sale_id, customer_id, total_amount, amount_paid, amount_due, credit_date, due_date, status, notes, created_at, updated_at, last_payment_date) VALUES (12, 32, 12, 109000.00, 0.00, 109000.00, '2026-01-14', '2026-02-06', 'active', NULL, '2026-01-14 10:24:43', '2026-01-14 10:24:43', NULL);
INSERT INTO public.credits (id, sale_id, customer_id, total_amount, amount_paid, amount_due, credit_date, due_date, status, notes, created_at, updated_at, last_payment_date) VALUES (11, 29, 9, 90000.00, 90000.00, 0.00, '2026-01-14', '2026-01-29', 'completed', NULL, '2026-01-14 06:40:35', '2026-01-14 10:35:00', '2026-01-14 10:35:00');


ALTER TABLE public.credits ENABLE TRIGGER ALL;

--
-- Data for Name: credit_installments; Type: TABLE DATA; Schema: public; Owner: express_sale_user
--

ALTER TABLE public.credit_installments DISABLE TRIGGER ALL;

INSERT INTO public.credit_installments (id, credit_id, installment_number, due_date, amount_due, amount_paid, status, paid_date, created_at) VALUES (14, 12, 1, '2026-01-16', 9000.00, 0.00, 'pending', NULL, '2026-01-14 10:24:43');
INSERT INTO public.credit_installments (id, credit_id, installment_number, due_date, amount_due, amount_paid, status, paid_date, created_at) VALUES (15, 12, 2, '2026-01-21', 100000.00, 0.00, 'pending', NULL, '2026-01-14 10:24:43');
INSERT INTO public.credit_installments (id, credit_id, installment_number, due_date, amount_due, amount_paid, status, paid_date, created_at) VALUES (13, 11, 1, '2026-01-29', 90000.00, 90000.00, 'paid', '2026-01-14', '2026-01-14 06:40:35');


ALTER TABLE public.credit_installments ENABLE TRIGGER ALL;

--
-- Data for Name: currency_rates; Type: TABLE DATA; Schema: public; Owner: express_sale_user
--

ALTER TABLE public.currency_rates DISABLE TRIGGER ALL;

INSERT INTO public.currency_rates (id, euro_rate, yuan_rate, dollar_rate, dirham_rate, effective_date, notes, created_by, created_at, updated_at, baht_rate, is_active) VALUES (7, 5200.0000, 1200.0000, 5200.0000, 0.1000, '2026-01-10', NULL, 4, '2026-01-10 17:05:19', '2026-01-14 10:44:17', 145.0000, false);
INSERT INTO public.currency_rates (id, euro_rate, yuan_rate, dollar_rate, dirham_rate, effective_date, notes, created_by, created_at, updated_at, baht_rate, is_active) VALUES (5, 0.0204, 3.1000, 0.0201, 0.1000, '2025-12-28', 'Taux du 15 janvier 2025', 4, '2025-12-28 12:07:35', '2026-01-14 10:44:17', 0.1201, false);
INSERT INTO public.currency_rates (id, euro_rate, yuan_rate, dollar_rate, dirham_rate, effective_date, notes, created_by, created_at, updated_at, baht_rate, is_active) VALUES (6, 5000.0000, 12000.0000, 1022.0000, 0.1000, '2026-01-28', 'milay  eh', 4, '2026-01-01 10:32:48', '2026-01-14 10:44:17', 12000.0000, false);
INSERT INTO public.currency_rates (id, euro_rate, yuan_rate, dollar_rate, dirham_rate, effective_date, notes, created_by, created_at, updated_at, baht_rate, is_active) VALUES (9, 5200.0000, 700.0000, 4800.0000, 0.1000, '2026-01-14', NULL, 4, '2026-01-14 08:22:06', '2026-01-14 10:44:17', 140.0000, false);
INSERT INTO public.currency_rates (id, euro_rate, yuan_rate, dollar_rate, dirham_rate, effective_date, notes, created_by, created_at, updated_at, baht_rate, is_active) VALUES (8, 5100.0000, 645.0000, 4578.0000, 0.1000, '2026-01-12', NULL, 4, '2026-01-10 17:29:38', '2026-01-14 10:44:17', 135.0000, true);


ALTER TABLE public.currency_rates ENABLE TRIGGER ALL;

--
-- Data for Name: failed_jobs; Type: TABLE DATA; Schema: public; Owner: express_sale_user
--

ALTER TABLE public.failed_jobs DISABLE TRIGGER ALL;



ALTER TABLE public.failed_jobs ENABLE TRIGGER ALL;

--
-- Data for Name: installment_transactions; Type: TABLE DATA; Schema: public; Owner: express_sale_user
--

ALTER TABLE public.installment_transactions DISABLE TRIGGER ALL;

INSERT INTO public.installment_transactions (id, installment_id, transaction_id, amount, payment_date, created_at, updated_at) VALUES (12, 13, 141, 10000.00, '2026-01-14 10:34:13', '2026-01-14 10:34:13', '2026-01-14 10:34:13');
INSERT INTO public.installment_transactions (id, installment_id, transaction_id, amount, payment_date, created_at, updated_at) VALUES (13, 13, 142, 80000.00, '2026-01-14 10:35:00', '2026-01-14 10:35:00', '2026-01-14 10:35:00');


ALTER TABLE public.installment_transactions ENABLE TRIGGER ALL;

--
-- Data for Name: job_batches; Type: TABLE DATA; Schema: public; Owner: express_sale_user
--

ALTER TABLE public.job_batches DISABLE TRIGGER ALL;



ALTER TABLE public.job_batches ENABLE TRIGGER ALL;

--
-- Data for Name: jobs; Type: TABLE DATA; Schema: public; Owner: express_sale_user
--

ALTER TABLE public.jobs DISABLE TRIGGER ALL;



ALTER TABLE public.jobs ENABLE TRIGGER ALL;

--
-- Data for Name: locations; Type: TABLE DATA; Schema: public; Owner: express_sale_user
--

ALTER TABLE public.locations DISABLE TRIGGER ALL;

INSERT INTO public.locations (id, name, code, warehouse, aisle, shelf, bin, description, capacity, is_active, created_at, updated_at) VALUES (1, 'Entrepôt A - Allée 1 - Étagère 1', 'A-01-01', 'Entrepôt A', 'Allée 1', 'Étagère 1', NULL, NULL, 100, true, '2025-12-24 16:56:24.112216', '2025-12-24 16:56:24.112216');
INSERT INTO public.locations (id, name, code, warehouse, aisle, shelf, bin, description, capacity, is_active, created_at, updated_at) VALUES (2, 'Entrepôt A - Allée 1 - Étagère 2', 'A-01-02', 'Entrepôt A', 'Allée 1', 'Étagère 2', NULL, NULL, 100, true, '2025-12-24 16:56:24.112216', '2025-12-24 16:56:24.112216');
INSERT INTO public.locations (id, name, code, warehouse, aisle, shelf, bin, description, capacity, is_active, created_at, updated_at) VALUES (3, 'Entrepôt A - Allée 2 - Étagère 1', 'A-02-01', 'Entrepôt A', 'Allée 2', 'Étagère 1', NULL, NULL, 150, true, '2025-12-24 16:56:24.112216', '2025-12-24 16:56:24.112216');
INSERT INTO public.locations (id, name, code, warehouse, aisle, shelf, bin, description, capacity, is_active, created_at, updated_at) VALUES (4, 'Entrepôt B - Allée 1 - Étagère 1', 'B-01-01', 'Entrepôt B', 'Allée 1', 'Étagère 1', NULL, NULL, 200, true, '2025-12-24 16:56:24.112216', '2025-12-24 16:56:24.112216');
INSERT INTO public.locations (id, name, code, warehouse, aisle, shelf, bin, description, capacity, is_active, created_at, updated_at) VALUES (5, 'Magasin principal', 'SHOP-01', 'Magasin', NULL, NULL, NULL, NULL, 50, true, '2025-12-24 16:56:24.112216', '2025-12-24 16:56:24.112216');
INSERT INTO public.locations (id, name, code, warehouse, aisle, shelf, bin, description, capacity, is_active, created_at, updated_at) VALUES (8, 'Toerana hafa mihitsy eh', 'Loc A', 'Entrepôt C', 'A1', '4', '1', NULL, 1000, true, '2026-01-05 10:36:38', '2026-01-05 10:36:38');
INSERT INTO public.locations (id, name, code, warehouse, aisle, shelf, bin, description, capacity, is_active, created_at, updated_at) VALUES (9, 'Dépôt', 'depot-A1-E1-B1', 'Entrepot', 'A1', 'E1', 'B1', NULL, 20000, true, '2026-01-14 09:51:37', '2026-01-14 09:51:37');


ALTER TABLE public.locations ENABLE TRIGGER ALL;

--
-- Data for Name: migrations; Type: TABLE DATA; Schema: public; Owner: express_sale_user
--

ALTER TABLE public.migrations DISABLE TRIGGER ALL;

INSERT INTO public.migrations (id, migration, batch) VALUES (1, '0001_01_01_000000_create_users_table', 1);
INSERT INTO public.migrations (id, migration, batch) VALUES (2, '0001_01_01_000001_create_cache_table', 1);
INSERT INTO public.migrations (id, migration, batch) VALUES (3, '0001_01_01_000002_create_jobs_table', 1);
INSERT INTO public.migrations (id, migration, batch) VALUES (4, '2025_12_18_184343_create_personal_access_tokens_table', 1);
INSERT INTO public.migrations (id, migration, batch) VALUES (5, '2025_12_27_122501_create_coordinates_table_and_add_foreign_keys', 2);
INSERT INTO public.migrations (id, migration, batch) VALUES (6, '2026_01_01_125653_rename_yen_rate_to_yuan_rate_in_currency_rates_table', 3);
INSERT INTO public.migrations (id, migration, batch) VALUES (7, '2026_01_03_142905_update_stock_receipt_item_ratings_precision', 4);
INSERT INTO public.migrations (id, migration, batch) VALUES (8, '2026_01_03_143204_update_all_score_columns_precision', 5);
INSERT INTO public.migrations (id, migration, batch) VALUES (9, '2026_01_04_085120_add_reversed_transaction_id_to_account_transactions_table', 6);
INSERT INTO public.migrations (id, migration, batch) VALUES (10, '2026_01_04_091017_add_constraint_transaction_types', 7);
INSERT INTO public.migrations (id, migration, batch) VALUES (12, '2026_01_04_110350_modify_amount_check_constraint_on_account_transactions', 8);
INSERT INTO public.migrations (id, migration, batch) VALUES (13, '2026_01_05_123606_add_batch_id_to_stock_movements_table', 8);
INSERT INTO public.migrations (id, migration, batch) VALUES (15, '2026_01_05_195149_add_tracking_columns_to_customers_table', 9);
INSERT INTO public.migrations (id, migration, batch) VALUES (16, '2026_01_06_160758_create_installment_transactions_table', 9);
INSERT INTO public.migrations (id, migration, batch) VALUES (17, '2026_01_06_162638_create_reservation_deposits_table', 10);
INSERT INTO public.migrations (id, migration, batch) VALUES (18, '2026_01_06_170911_remove_customer_tracking_columns', 11);
INSERT INTO public.migrations (id, migration, batch) VALUES (19, '2026_01_07_163600_customer_add_customer_number', 12);
INSERT INTO public.migrations (id, migration, batch) VALUES (20, '2026_01_07_173807_product_variant_add_credit_quantity', 13);
INSERT INTO public.migrations (id, migration, batch) VALUES (21, '2026_01_16_103716_create_cash_counts_table', 14);
INSERT INTO public.migrations (id, migration, batch) VALUES (22, '2026_01_16_104236_create_cash_count_denominations_table', 14);


ALTER TABLE public.migrations ENABLE TRIGGER ALL;

--
-- Data for Name: notifications; Type: TABLE DATA; Schema: public; Owner: express_sale_user
--

ALTER TABLE public.notifications DISABLE TRIGGER ALL;



ALTER TABLE public.notifications ENABLE TRIGGER ALL;

--
-- Data for Name: password_reset_tokens; Type: TABLE DATA; Schema: public; Owner: express_sale_user
--

ALTER TABLE public.password_reset_tokens DISABLE TRIGGER ALL;



ALTER TABLE public.password_reset_tokens ENABLE TRIGGER ALL;

--
-- Data for Name: personal_access_tokens; Type: TABLE DATA; Schema: public; Owner: express_sale_user
--

ALTER TABLE public.personal_access_tokens DISABLE TRIGGER ALL;

INSERT INTO public.personal_access_tokens (id, tokenable_type, tokenable_id, name, token, abilities, last_used_at, expires_at, created_at, updated_at) VALUES (2, 'App\Models\User', 2, 'express-sale-token', 'd8a9de02341ed9b1102651e97a91318373a401751aef6f95d5e2ee39f0673158', '["*"]', NULL, NULL, '2025-12-19 13:01:47', '2025-12-19 13:01:47');
INSERT INTO public.personal_access_tokens (id, tokenable_type, tokenable_id, name, token, abilities, last_used_at, expires_at, created_at, updated_at) VALUES (47, 'App\Models\User', 4, 'express-sale-token', '85666431ebe627135146e6b0c494c748003b6240434b239f67e3fda8a7f4612d', '["*"]', '2026-01-16 16:57:48', NULL, '2026-01-16 11:33:34', '2026-01-16 16:57:48');


ALTER TABLE public.personal_access_tokens ENABLE TRIGGER ALL;

--
-- Data for Name: products; Type: TABLE DATA; Schema: public; Owner: express_sale_user
--

ALTER TABLE public.products DISABLE TRIGGER ALL;

INSERT INTO public.products (id, name, description, category_id, subcategory_id, base_price, is_active, created_at, updated_at, image_url) VALUES (22, 'Stan Smith', NULL, 21, 22, 90000.00, true, '2026-01-14 06:24:46', '2026-01-14 06:24:46', 'uploads/images/2026/01/74825a5b-4ef9-4a32-9495-565f771e3dac.jpeg');
INSERT INTO public.products (id, name, description, category_id, subcategory_id, base_price, is_active, created_at, updated_at, image_url) VALUES (23, 'Nike', NULL, 21, 22, 32000.00, true, '2026-01-14 06:26:30', '2026-01-14 06:26:30', NULL);
INSERT INTO public.products (id, name, description, category_id, subcategory_id, base_price, is_active, created_at, updated_at, image_url) VALUES (24, 'Robe tsara tarehy', NULL, 23, 24, 19000.00, true, '2026-01-14 06:29:15', '2026-01-14 06:29:15', NULL);
INSERT INTO public.products (id, name, description, category_id, subcategory_id, base_price, is_active, created_at, updated_at, image_url) VALUES (25, 'Labubu', NULL, 25, 26, 34997.00, true, '2026-01-14 08:16:37', '2026-01-14 08:16:37', 'uploads/images/2026/01/98d8c093-067c-4959-b576-1b8524c7152b.png');


ALTER TABLE public.products ENABLE TRIGGER ALL;

--
-- Data for Name: product_attributes; Type: TABLE DATA; Schema: public; Owner: express_sale_user
--

ALTER TABLE public.product_attributes DISABLE TRIGGER ALL;

INSERT INTO public.product_attributes (id, product_id, attribute_type_id, is_required, created_at) VALUES (41, 22, 16, true, '2026-01-14 09:24:46.717038');
INSERT INTO public.product_attributes (id, product_id, attribute_type_id, is_required, created_at) VALUES (42, 22, 17, true, '2026-01-14 09:24:46.717038');
INSERT INTO public.product_attributes (id, product_id, attribute_type_id, is_required, created_at) VALUES (43, 23, 16, true, '2026-01-14 09:26:30.167168');
INSERT INTO public.product_attributes (id, product_id, attribute_type_id, is_required, created_at) VALUES (44, 23, 17, true, '2026-01-14 09:26:30.167168');
INSERT INTO public.product_attributes (id, product_id, attribute_type_id, is_required, created_at) VALUES (45, 24, 18, true, '2026-01-14 09:29:15.261907');
INSERT INTO public.product_attributes (id, product_id, attribute_type_id, is_required, created_at) VALUES (46, 24, 16, true, '2026-01-14 09:29:15.261907');
INSERT INTO public.product_attributes (id, product_id, attribute_type_id, is_required, created_at) VALUES (47, 25, 18, true, '2026-01-14 11:16:37.036061');
INSERT INTO public.product_attributes (id, product_id, attribute_type_id, is_required, created_at) VALUES (48, 25, 16, true, '2026-01-14 11:16:37.036061');


ALTER TABLE public.product_attributes ENABLE TRIGGER ALL;

--
-- Data for Name: product_variants; Type: TABLE DATA; Schema: public; Owner: express_sale_user
--

ALTER TABLE public.product_variants DISABLE TRIGGER ALL;

INSERT INTO public.product_variants (id, product_id, sku, price_adjustment, stock_quantity, reserved_quantity, low_stock_threshold, is_active, created_at, updated_at, image_path, credit_quantity) VALUES (21, 25, 'LAB--5SNJ', 0.00, 10, 0, 10, true, '2026-01-14 08:17:56', '2026-01-14 11:47:36.446848', 'uploads/images/2026/01/7696d761-2915-417d-903a-0ad3f93d20f3.png', 0);
INSERT INTO public.product_variants (id, product_id, sku, price_adjustment, stock_quantity, reserved_quantity, low_stock_threshold, is_active, created_at, updated_at, image_path, credit_quantity) VALUES (20, 24, 'ROB--9ZGE', 0.00, 6, 0, 5, true, '2026-01-14 06:29:32', '2026-01-14 13:24:43.852582', NULL, 0);
INSERT INTO public.product_variants (id, product_id, sku, price_adjustment, stock_quantity, reserved_quantity, low_stock_threshold, is_active, created_at, updated_at, image_path, credit_quantity) VALUES (19, 23, 'NIK--UEK2', 0.00, 10, 0, 5, true, '2026-01-14 06:26:49', '2026-01-14 13:28:07.665697', NULL, 0);
INSERT INTO public.product_variants (id, product_id, sku, price_adjustment, stock_quantity, reserved_quantity, low_stock_threshold, is_active, created_at, updated_at, image_path, credit_quantity) VALUES (17, 22, 'STA--ZFTN', 0.00, 3, 0, 5, true, '2026-01-14 06:25:18', '2026-01-14 13:35:00.367875', NULL, -1);
INSERT INTO public.product_variants (id, product_id, sku, price_adjustment, stock_quantity, reserved_quantity, low_stock_threshold, is_active, created_at, updated_at, image_path, credit_quantity) VALUES (18, 22, 'STA--7ZLN', 0.00, 0, 0, 5, true, '2026-01-14 06:25:37', '2026-01-16 11:47:01.93806', NULL, 0);
INSERT INTO public.product_variants (id, product_id, sku, price_adjustment, stock_quantity, reserved_quantity, low_stock_threshold, is_active, created_at, updated_at, image_path, credit_quantity) VALUES (22, 25, 'LAB--DVPV', 0.00, 11, 0, 5, true, '2026-01-14 08:18:29', '2026-01-16 11:47:01.93806', NULL, 0);


ALTER TABLE public.product_variants ENABLE TRIGGER ALL;

--
-- Data for Name: product_variant_locations; Type: TABLE DATA; Schema: public; Owner: express_sale_user
--

ALTER TABLE public.product_variant_locations DISABLE TRIGGER ALL;

INSERT INTO public.product_variant_locations (id, variant_id, location_id, quantity, notes, created_at, updated_at) VALUES (40, 21, 5, 10, NULL, '2026-01-14 08:47:36', '2026-01-14 08:47:36');
INSERT INTO public.product_variant_locations (id, variant_id, location_id, quantity, notes, created_at, updated_at) VALUES (32, 17, 1, 1, NULL, '2026-01-14 06:36:14', '2026-01-14 09:48:53');
INSERT INTO public.product_variant_locations (id, variant_id, location_id, quantity, notes, created_at, updated_at) VALUES (33, 18, 1, 0, NULL, '2026-01-14 06:36:14', '2026-01-14 09:48:53');
INSERT INTO public.product_variant_locations (id, variant_id, location_id, quantity, notes, created_at, updated_at) VALUES (34, 20, 1, 1, NULL, '2026-01-14 06:36:14', '2026-01-14 09:55:37');
INSERT INTO public.product_variant_locations (id, variant_id, location_id, quantity, notes, created_at, updated_at) VALUES (37, 20, 5, 5, NULL, '2026-01-14 06:37:43', '2026-01-14 10:24:43');
INSERT INTO public.product_variant_locations (id, variant_id, location_id, quantity, notes, created_at, updated_at) VALUES (36, 17, 5, 2, NULL, '2026-01-14 06:37:43', '2026-01-14 10:24:43');
INSERT INTO public.product_variant_locations (id, variant_id, location_id, quantity, notes, created_at, updated_at) VALUES (38, 19, 2, 10, NULL, '2026-01-14 08:47:36', '2026-01-14 10:28:07');
INSERT INTO public.product_variant_locations (id, variant_id, location_id, quantity, notes, created_at, updated_at) VALUES (35, 18, 5, 0, NULL, '2026-01-14 06:37:43', '2026-01-16 08:47:01');
INSERT INTO public.product_variant_locations (id, variant_id, location_id, quantity, notes, created_at, updated_at) VALUES (39, 22, 2, 11, NULL, '2026-01-14 08:47:36', '2026-01-16 08:47:01');


ALTER TABLE public.product_variant_locations ENABLE TRIGGER ALL;

--
-- Data for Name: reservations; Type: TABLE DATA; Schema: public; Owner: express_sale_user
--

ALTER TABLE public.reservations DISABLE TRIGGER ALL;

INSERT INTO public.reservations (id, sale_id, customer_id, reservation_date, expiry_date, total_amount, deposit_amount, remaining_amount, status, cancellation_reason, completed_at, created_at, updated_at) VALUES (8, 30, 10, '2026-01-14 06:41:49', '2026-01-31 00:00:00', 450000.00, 50000.00, 400000.00, 'confirmed', NULL, NULL, '2026-01-14 06:41:49', '2026-01-14 06:41:49');
INSERT INTO public.reservations (id, sale_id, customer_id, reservation_date, expiry_date, total_amount, deposit_amount, remaining_amount, status, cancellation_reason, completed_at, created_at, updated_at) VALUES (9, 33, 7, '2026-01-14 10:28:07', '2026-01-22 00:00:00', 319000.00, 319000.00, 0.00, 'completed', NULL, '2026-01-14 10:32:16', '2026-01-14 10:28:07', '2026-01-14 10:32:16');
INSERT INTO public.reservations (id, sale_id, customer_id, reservation_date, expiry_date, total_amount, deposit_amount, remaining_amount, status, cancellation_reason, completed_at, created_at, updated_at) VALUES (10, 34, 9, '2026-01-16 08:47:01', '2026-01-31 00:00:00', 617970.00, 300000.00, 317970.00, 'confirmed', NULL, NULL, '2026-01-16 08:47:01', '2026-01-16 08:47:01');


ALTER TABLE public.reservations ENABLE TRIGGER ALL;

--
-- Data for Name: reservation_deposits; Type: TABLE DATA; Schema: public; Owner: express_sale_user
--

ALTER TABLE public.reservation_deposits DISABLE TRIGGER ALL;



ALTER TABLE public.reservation_deposits ENABLE TRIGGER ALL;

--
-- Data for Name: sale_items; Type: TABLE DATA; Schema: public; Owner: express_sale_user
--

ALTER TABLE public.sale_items DISABLE TRIGGER ALL;

INSERT INTO public.sale_items (id, sale_id, variant_id, quantity, unit_price, subtotal, created_at) VALUES (47, 28, 18, 2, 90000.00, 180000.00, '2026-01-14 09:38:32.502459');
INSERT INTO public.sale_items (id, sale_id, variant_id, quantity, unit_price, subtotal, created_at) VALUES (48, 28, 20, 2, 19000.00, 38000.00, '2026-01-14 09:38:32.502459');
INSERT INTO public.sale_items (id, sale_id, variant_id, quantity, unit_price, subtotal, created_at) VALUES (49, 29, 17, 1, 90000.00, 90000.00, '2026-01-14 09:40:35.595703');
INSERT INTO public.sale_items (id, sale_id, variant_id, quantity, unit_price, subtotal, created_at) VALUES (50, 30, 18, 5, 90000.00, 450000.00, '2026-01-14 09:41:49.00164');
INSERT INTO public.sale_items (id, sale_id, variant_id, quantity, unit_price, subtotal, created_at) VALUES (51, 31, 17, 5, 90000.00, 450000.00, '2026-01-14 13:17:57.979478');
INSERT INTO public.sale_items (id, sale_id, variant_id, quantity, unit_price, subtotal, created_at) VALUES (52, 31, 19, 10, 32000.00, 320000.00, '2026-01-14 13:17:57.979478');
INSERT INTO public.sale_items (id, sale_id, variant_id, quantity, unit_price, subtotal, created_at) VALUES (53, 32, 20, 1, 19000.00, 19000.00, '2026-01-14 13:24:43.852582');
INSERT INTO public.sale_items (id, sale_id, variant_id, quantity, unit_price, subtotal, created_at) VALUES (54, 32, 17, 1, 90000.00, 90000.00, '2026-01-14 13:24:43.852582');
INSERT INTO public.sale_items (id, sale_id, variant_id, quantity, unit_price, subtotal, created_at) VALUES (55, 33, 19, 10, 32000.00, 320000.00, '2026-01-14 13:28:07.665697');
INSERT INTO public.sale_items (id, sale_id, variant_id, quantity, unit_price, subtotal, created_at) VALUES (56, 34, 18, 3, 90000.00, 270000.00, '2026-01-16 11:47:01.93806');
INSERT INTO public.sale_items (id, sale_id, variant_id, quantity, unit_price, subtotal, created_at) VALUES (57, 34, 22, 10, 34997.00, 349970.00, '2026-01-16 11:47:01.93806');


ALTER TABLE public.sale_items ENABLE TRIGGER ALL;

--
-- Data for Name: sessions; Type: TABLE DATA; Schema: public; Owner: express_sale_user
--

ALTER TABLE public.sessions DISABLE TRIGGER ALL;

INSERT INTO public.sessions (id, user_id, ip_address, user_agent, payload, last_activity) VALUES ('vVmpFxbqYVHRPK9eC5d0w4rBaXmEHDW8Smq3cZ5x', NULL, '127.0.0.1', 'PostmanRuntime/7.49.1', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiRlVXM1Bob01nQUp6eGp0VzVKYU1rbXhnQ2ZDQWRvWm5JNGphM3lXZyI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjE6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMCI7czo1OiJyb3V0ZSI7Tjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1766089185);
INSERT INTO public.sessions (id, user_id, ip_address, user_agent, payload, last_activity) VALUES ('viawJtmGKrx1Pp8J1Yjeu6OX5TnljmGByMMOe9K9', NULL, '127.0.0.1', 'PostmanRuntime/7.49.1', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoieUlYVVZtR1VlU3d0Y2R5RDFYZ09tTjFlamhUdTZWSGxZWlJLb2Q0SCI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjE6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMCI7czo1OiJyb3V0ZSI7Tjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1766133920);
INSERT INTO public.sessions (id, user_id, ip_address, user_agent, payload, last_activity) VALUES ('poOKGY614sW6xO3f6gwjE4AYk7MI5DVJaSZeOrzL', NULL, '127.0.0.1', 'PostmanRuntime/7.51.0', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoicHEwenVnV1hWVllCdWxqbWdQYTBkSmpzbFBYUjZxM1U5Vk8xalRhWCI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjE6Imh0dHA6Ly9sb2NhbGhvc3Q6ODAwMCI7czo1OiJyb3V0ZSI7Tjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1767696450);
INSERT INTO public.sessions (id, user_id, ip_address, user_agent, payload, last_activity) VALUES ('K2x0RJTCDXoQpYjyZr2E4NANB1WRPbEqaltXveU6', NULL, '127.0.0.1', 'PostmanRuntime/7.51.0', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiSFpiZEt1amNwZW00MU00dWVtNlFUd25HNXp5NnRyMmRlM2lCWmNtRSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjE6Imh0dHA6Ly9sb2NhbGhvc3Q6ODAwMCI7czo1OiJyb3V0ZSI7Tjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1767786983);
INSERT INTO public.sessions (id, user_id, ip_address, user_agent, payload, last_activity) VALUES ('DPy7QiYfD57A1LT4bSlg4cVi1p1icU94BKWhzgcA', NULL, '127.0.0.1', 'PostmanRuntime/7.51.0', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiT3BSR09kQ2ZVR2FsZmdUek05dFdzYTRkd2FHOHlSNVl2VVhrbjg4NSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjE6Imh0dHA6Ly9sb2NhbGhvc3Q6ODAwMCI7czo1OiJyb3V0ZSI7Tjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1767810476);
INSERT INTO public.sessions (id, user_id, ip_address, user_agent, payload, last_activity) VALUES ('Jk1wgniksnZRUGe454ZNaR07M3M5xOj8SZmEitMC', NULL, '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiOGNxd29VMkRsNVk0TkxpWjAybXdqT0lXN2dPaTVyOVBzNG5pQThWdCI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjE6Imh0dHA6Ly9sb2NhbGhvc3Q6ODAwMCI7czo1OiJyb3V0ZSI7Tjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1768364171);
INSERT INTO public.sessions (id, user_id, ip_address, user_agent, payload, last_activity) VALUES ('3vCBmbTHm8gQ2Cks31c3iY1aI2DKrfVxhTTym5LU', NULL, '127.0.0.1', 'PostmanRuntime/7.51.0', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiWkZObU5XRFVzR2hiaGJzWjhqU1BFZTVlc3dTaXJEMXFIQlpBS3lieSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjE6Imh0dHA6Ly9sb2NhbGhvc3Q6ODAwMCI7czo1OiJyb3V0ZSI7Tjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1768564114);


ALTER TABLE public.sessions ENABLE TRIGGER ALL;

--
-- Data for Name: stock_movements; Type: TABLE DATA; Schema: public; Owner: express_sale_user
--

ALTER TABLE public.stock_movements DISABLE TRIGGER ALL;

INSERT INTO public.stock_movements (id, variant_id, from_location_id, to_location_id, quantity, movement_type, sale_id, stock_receipt_id, performed_by, reason, notes, created_at, batch_id) VALUES (79, 17, NULL, 1, 10, 'receipt', NULL, 27, 4, 'Réception RCP-20260114-0001', NULL, '2026-01-14 06:36:14', 'RCP-RCP-20260114-0001-GqnM');
INSERT INTO public.stock_movements (id, variant_id, from_location_id, to_location_id, quantity, movement_type, sale_id, stock_receipt_id, performed_by, reason, notes, created_at, batch_id) VALUES (80, 18, NULL, 1, 10, 'receipt', NULL, 27, 4, 'Réception RCP-20260114-0001', NULL, '2026-01-14 06:36:14', 'RCP-RCP-20260114-0001-GqnM');
INSERT INTO public.stock_movements (id, variant_id, from_location_id, to_location_id, quantity, movement_type, sale_id, stock_receipt_id, performed_by, reason, notes, created_at, batch_id) VALUES (81, 20, NULL, 1, 10, 'receipt', NULL, 27, 4, 'Réception RCP-20260114-0001', NULL, '2026-01-14 06:36:14', 'RCP-RCP-20260114-0001-GqnM');
INSERT INTO public.stock_movements (id, variant_id, from_location_id, to_location_id, quantity, movement_type, sale_id, stock_receipt_id, performed_by, reason, notes, created_at, batch_id) VALUES (82, 18, 1, 5, 6, 'transfer', NULL, NULL, 4, NULL, NULL, '2026-01-14 06:37:43', 'TRF-20260114-063743-wALps0');
INSERT INTO public.stock_movements (id, variant_id, from_location_id, to_location_id, quantity, movement_type, sale_id, stock_receipt_id, performed_by, reason, notes, created_at, batch_id) VALUES (83, 17, 1, 5, 6, 'transfer', NULL, NULL, 4, NULL, NULL, '2026-01-14 06:37:43', 'TRF-20260114-063743-wALps0');
INSERT INTO public.stock_movements (id, variant_id, from_location_id, to_location_id, quantity, movement_type, sale_id, stock_receipt_id, performed_by, reason, notes, created_at, batch_id) VALUES (84, 20, 1, 5, 6, 'transfer', NULL, NULL, 4, NULL, NULL, '2026-01-14 06:37:43', 'TRF-20260114-063743-wALps0');
INSERT INTO public.stock_movements (id, variant_id, from_location_id, to_location_id, quantity, movement_type, sale_id, stock_receipt_id, performed_by, reason, notes, created_at, batch_id) VALUES (85, 18, 1, NULL, 2, 'sale', 28, NULL, 4, NULL, 'Vente #28', '2026-01-14 06:38:32', NULL);
INSERT INTO public.stock_movements (id, variant_id, from_location_id, to_location_id, quantity, movement_type, sale_id, stock_receipt_id, performed_by, reason, notes, created_at, batch_id) VALUES (86, 20, 1, NULL, 2, 'sale', 28, NULL, 4, NULL, 'Vente #28', '2026-01-14 06:38:32', NULL);
INSERT INTO public.stock_movements (id, variant_id, from_location_id, to_location_id, quantity, movement_type, sale_id, stock_receipt_id, performed_by, reason, notes, created_at, batch_id) VALUES (87, 17, 1, NULL, 1, 'sale', 29, NULL, 4, NULL, 'Vente #29', '2026-01-14 06:40:35', NULL);
INSERT INTO public.stock_movements (id, variant_id, from_location_id, to_location_id, quantity, movement_type, sale_id, stock_receipt_id, performed_by, reason, notes, created_at, batch_id) VALUES (88, 18, 5, NULL, 5, 'reservation', 30, NULL, 4, 'reservation', 'Réservation vente #30', '2026-01-14 06:41:49', NULL);
INSERT INTO public.stock_movements (id, variant_id, from_location_id, to_location_id, quantity, movement_type, sale_id, stock_receipt_id, performed_by, reason, notes, created_at, batch_id) VALUES (89, 19, NULL, 2, 30, 'receipt', NULL, 30, 4, 'Réception RCP-20260114-0004', NULL, '2026-01-14 08:47:36', 'RCP-RCP-20260114-0004-hHdX');
INSERT INTO public.stock_movements (id, variant_id, from_location_id, to_location_id, quantity, movement_type, sale_id, stock_receipt_id, performed_by, reason, notes, created_at, batch_id) VALUES (90, 22, NULL, 2, 21, 'receipt', NULL, 30, 4, 'Réception RCP-20260114-0004', NULL, '2026-01-14 08:47:36', 'RCP-RCP-20260114-0004-hHdX');
INSERT INTO public.stock_movements (id, variant_id, from_location_id, to_location_id, quantity, movement_type, sale_id, stock_receipt_id, performed_by, reason, notes, created_at, batch_id) VALUES (91, 21, NULL, 5, 10, 'receipt', NULL, 30, 4, 'Réception RCP-20260114-0004', NULL, '2026-01-14 08:47:36', 'RCP-RCP-20260114-0004-hHdX');
INSERT INTO public.stock_movements (id, variant_id, from_location_id, to_location_id, quantity, movement_type, sale_id, stock_receipt_id, performed_by, reason, notes, created_at, batch_id) VALUES (92, 17, 1, 5, 2, 'transfer', NULL, NULL, 4, 'Réorganisation', NULL, '2026-01-14 09:48:53', 'TRF-20260114-094853-eOTBsk');
INSERT INTO public.stock_movements (id, variant_id, from_location_id, to_location_id, quantity, movement_type, sale_id, stock_receipt_id, performed_by, reason, notes, created_at, batch_id) VALUES (93, 18, 1, 5, 2, 'transfer', NULL, NULL, 4, 'Réorganisation', NULL, '2026-01-14 09:48:53', 'TRF-20260114-094853-eOTBsk');
INSERT INTO public.stock_movements (id, variant_id, from_location_id, to_location_id, quantity, movement_type, sale_id, stock_receipt_id, performed_by, reason, notes, created_at, batch_id) VALUES (94, 20, 1, NULL, 1, 'adjustment', NULL, NULL, 4, 'Inventaire physique', NULL, '2026-01-14 09:55:37', NULL);
INSERT INTO public.stock_movements (id, variant_id, from_location_id, to_location_id, quantity, movement_type, sale_id, stock_receipt_id, performed_by, reason, notes, created_at, batch_id) VALUES (95, 17, 5, NULL, 5, 'sale', 31, NULL, 4, NULL, 'Vente #31', '2026-01-14 10:17:58', NULL);
INSERT INTO public.stock_movements (id, variant_id, from_location_id, to_location_id, quantity, movement_type, sale_id, stock_receipt_id, performed_by, reason, notes, created_at, batch_id) VALUES (96, 19, 2, NULL, 10, 'sale', 31, NULL, 4, NULL, 'Vente #31', '2026-01-14 10:17:58', NULL);
INSERT INTO public.stock_movements (id, variant_id, from_location_id, to_location_id, quantity, movement_type, sale_id, stock_receipt_id, performed_by, reason, notes, created_at, batch_id) VALUES (97, 20, 5, NULL, 1, 'sale', 32, NULL, 4, NULL, 'Vente #32', '2026-01-14 10:24:43', NULL);
INSERT INTO public.stock_movements (id, variant_id, from_location_id, to_location_id, quantity, movement_type, sale_id, stock_receipt_id, performed_by, reason, notes, created_at, batch_id) VALUES (98, 17, 5, NULL, 1, 'sale', 32, NULL, 4, NULL, 'Vente #32', '2026-01-14 10:24:43', NULL);
INSERT INTO public.stock_movements (id, variant_id, from_location_id, to_location_id, quantity, movement_type, sale_id, stock_receipt_id, performed_by, reason, notes, created_at, batch_id) VALUES (99, 19, 2, NULL, 10, 'reservation', 33, NULL, 4, 'reservation', 'Réservation vente #33', '2026-01-14 10:28:07', NULL);
INSERT INTO public.stock_movements (id, variant_id, from_location_id, to_location_id, quantity, movement_type, sale_id, stock_receipt_id, performed_by, reason, notes, created_at, batch_id) VALUES (100, 19, 2, NULL, 10, 'sale', 33, NULL, 4, 'reservation_completed', 'Réservation #33 complétée - stock libéré', '2026-01-14 10:32:16', NULL);
INSERT INTO public.stock_movements (id, variant_id, from_location_id, to_location_id, quantity, movement_type, sale_id, stock_receipt_id, performed_by, reason, notes, created_at, batch_id) VALUES (101, 18, 5, NULL, 3, 'reservation', 34, NULL, 7, 'reservation', 'Réservation vente #34', '2026-01-16 08:47:01', NULL);
INSERT INTO public.stock_movements (id, variant_id, from_location_id, to_location_id, quantity, movement_type, sale_id, stock_receipt_id, performed_by, reason, notes, created_at, batch_id) VALUES (102, 22, 2, NULL, 10, 'reservation', 34, NULL, 7, 'reservation', 'Réservation vente #34', '2026-01-16 08:47:01', NULL);


ALTER TABLE public.stock_movements ENABLE TRIGGER ALL;

--
-- Data for Name: stock_receipt_items; Type: TABLE DATA; Schema: public; Owner: express_sale_user
--

ALTER TABLE public.stock_receipt_items DISABLE TRIGGER ALL;

INSERT INTO public.stock_receipt_items (id, stock_receipt_id, variant_id, quantity_ordered, quantity_received, unit_cost_ariary, notes, created_at) VALUES (64, 27, 17, 10, 10, 61200.00, NULL, '2026-01-14 09:35:26.598353');
INSERT INTO public.stock_receipt_items (id, stock_receipt_id, variant_id, quantity_ordered, quantity_received, unit_cost_ariary, notes, created_at) VALUES (65, 27, 18, 10, 10, 61200.00, NULL, '2026-01-14 09:35:26.598353');
INSERT INTO public.stock_receipt_items (id, stock_receipt_id, variant_id, quantity_ordered, quantity_received, unit_cost_ariary, notes, created_at) VALUES (66, 27, 20, 10, 10, 107100.00, NULL, '2026-01-14 09:35:26.598353');
INSERT INTO public.stock_receipt_items (id, stock_receipt_id, variant_id, quantity_ordered, quantity_received, unit_cost_ariary, notes, created_at) VALUES (67, 28, 18, 1, 0, 78720.00, NULL, '2026-01-14 11:39:09.343574');
INSERT INTO public.stock_receipt_items (id, stock_receipt_id, variant_id, quantity_ordered, quantity_received, unit_cost_ariary, notes, created_at) VALUES (68, 28, 17, 1, 0, 78720.00, NULL, '2026-01-14 11:39:09.343574');
INSERT INTO public.stock_receipt_items (id, stock_receipt_id, variant_id, quantity_ordered, quantity_received, unit_cost_ariary, notes, created_at) VALUES (69, 28, 20, 1, 0, 7686.40, NULL, '2026-01-14 11:39:09.343574');
INSERT INTO public.stock_receipt_items (id, stock_receipt_id, variant_id, quantity_ordered, quantity_received, unit_cost_ariary, notes, created_at) VALUES (70, 29, 18, 1, 0, 78720.00, NULL, '2026-01-14 11:39:28.542028');
INSERT INTO public.stock_receipt_items (id, stock_receipt_id, variant_id, quantity_ordered, quantity_received, unit_cost_ariary, notes, created_at) VALUES (71, 29, 17, 1, 0, 78720.00, NULL, '2026-01-14 11:39:28.542028');
INSERT INTO public.stock_receipt_items (id, stock_receipt_id, variant_id, quantity_ordered, quantity_received, unit_cost_ariary, notes, created_at) VALUES (72, 29, 20, 1, 0, 7686.40, NULL, '2026-01-14 11:39:28.542028');
INSERT INTO public.stock_receipt_items (id, stock_receipt_id, variant_id, quantity_ordered, quantity_received, unit_cost_ariary, notes, created_at) VALUES (73, 30, 19, 50, 30, 5100.00, NULL, '2026-01-14 11:46:35.472599');
INSERT INTO public.stock_receipt_items (id, stock_receipt_id, variant_id, quantity_ordered, quantity_received, unit_cost_ariary, notes, created_at) VALUES (74, 30, 22, 21, 21, 5100.00, NULL, '2026-01-14 11:46:35.472599');
INSERT INTO public.stock_receipt_items (id, stock_receipt_id, variant_id, quantity_ordered, quantity_received, unit_cost_ariary, notes, created_at) VALUES (75, 30, 21, 11, 10, 5100.00, NULL, '2026-01-14 11:46:35.472599');


ALTER TABLE public.stock_receipt_items ENABLE TRIGGER ALL;

--
-- Data for Name: stock_receipt_item_ratings; Type: TABLE DATA; Schema: public; Owner: express_sale_user
--

ALTER TABLE public.stock_receipt_item_ratings DISABLE TRIGGER ALL;

INSERT INTO public.stock_receipt_item_ratings (id, stock_receipt_item_id, attribute_type_id, attribute_conformity_rating, quality_rating, quality_notes, rated_by, created_at) VALUES (129, 64, NULL, 9.00, 8.00, NULL, 4, '2026-01-14 06:36:36');
INSERT INTO public.stock_receipt_item_ratings (id, stock_receipt_item_id, attribute_type_id, attribute_conformity_rating, quality_rating, quality_notes, rated_by, created_at) VALUES (130, 64, 16, 9.00, 8.00, NULL, 4, '2026-01-14 06:36:36');
INSERT INTO public.stock_receipt_item_ratings (id, stock_receipt_item_id, attribute_type_id, attribute_conformity_rating, quality_rating, quality_notes, rated_by, created_at) VALUES (131, 64, 17, 9.00, 8.00, NULL, 4, '2026-01-14 06:36:36');
INSERT INTO public.stock_receipt_item_ratings (id, stock_receipt_item_id, attribute_type_id, attribute_conformity_rating, quality_rating, quality_notes, rated_by, created_at) VALUES (132, 65, NULL, 9.00, 8.00, NULL, 4, '2026-01-14 06:36:36');
INSERT INTO public.stock_receipt_item_ratings (id, stock_receipt_item_id, attribute_type_id, attribute_conformity_rating, quality_rating, quality_notes, rated_by, created_at) VALUES (133, 65, 16, 9.00, 8.00, NULL, 4, '2026-01-14 06:36:36');
INSERT INTO public.stock_receipt_item_ratings (id, stock_receipt_item_id, attribute_type_id, attribute_conformity_rating, quality_rating, quality_notes, rated_by, created_at) VALUES (134, 65, 17, 9.00, 8.00, NULL, 4, '2026-01-14 06:36:36');
INSERT INTO public.stock_receipt_item_ratings (id, stock_receipt_item_id, attribute_type_id, attribute_conformity_rating, quality_rating, quality_notes, rated_by, created_at) VALUES (135, 66, NULL, 9.00, 8.00, NULL, 4, '2026-01-14 06:36:36');
INSERT INTO public.stock_receipt_item_ratings (id, stock_receipt_item_id, attribute_type_id, attribute_conformity_rating, quality_rating, quality_notes, rated_by, created_at) VALUES (136, 66, 18, 9.00, 8.00, NULL, 4, '2026-01-14 06:36:36');
INSERT INTO public.stock_receipt_item_ratings (id, stock_receipt_item_id, attribute_type_id, attribute_conformity_rating, quality_rating, quality_notes, rated_by, created_at) VALUES (137, 66, 16, 9.00, 8.00, NULL, 4, '2026-01-14 06:36:36');
INSERT INTO public.stock_receipt_item_ratings (id, stock_receipt_item_id, attribute_type_id, attribute_conformity_rating, quality_rating, quality_notes, rated_by, created_at) VALUES (138, 73, NULL, 5.00, 8.00, NULL, 4, '2026-01-14 08:53:20');
INSERT INTO public.stock_receipt_item_ratings (id, stock_receipt_item_id, attribute_type_id, attribute_conformity_rating, quality_rating, quality_notes, rated_by, created_at) VALUES (139, 73, 16, 5.00, 8.00, NULL, 4, '2026-01-14 08:53:20');
INSERT INTO public.stock_receipt_item_ratings (id, stock_receipt_item_id, attribute_type_id, attribute_conformity_rating, quality_rating, quality_notes, rated_by, created_at) VALUES (140, 73, 17, 5.00, 8.00, NULL, 4, '2026-01-14 08:53:20');
INSERT INTO public.stock_receipt_item_ratings (id, stock_receipt_item_id, attribute_type_id, attribute_conformity_rating, quality_rating, quality_notes, rated_by, created_at) VALUES (141, 74, NULL, 8.00, 8.00, NULL, 4, '2026-01-14 08:53:21');
INSERT INTO public.stock_receipt_item_ratings (id, stock_receipt_item_id, attribute_type_id, attribute_conformity_rating, quality_rating, quality_notes, rated_by, created_at) VALUES (142, 74, 18, 10.00, 8.00, NULL, 4, '2026-01-14 08:53:21');
INSERT INTO public.stock_receipt_item_ratings (id, stock_receipt_item_id, attribute_type_id, attribute_conformity_rating, quality_rating, quality_notes, rated_by, created_at) VALUES (143, 74, 16, 5.00, 8.00, NULL, 4, '2026-01-14 08:53:21');
INSERT INTO public.stock_receipt_item_ratings (id, stock_receipt_item_id, attribute_type_id, attribute_conformity_rating, quality_rating, quality_notes, rated_by, created_at) VALUES (144, 75, NULL, 6.00, 9.00, NULL, 4, '2026-01-14 08:53:21');
INSERT INTO public.stock_receipt_item_ratings (id, stock_receipt_item_id, attribute_type_id, attribute_conformity_rating, quality_rating, quality_notes, rated_by, created_at) VALUES (145, 75, 18, 9.00, 9.00, NULL, 4, '2026-01-14 08:53:21');
INSERT INTO public.stock_receipt_item_ratings (id, stock_receipt_item_id, attribute_type_id, attribute_conformity_rating, quality_rating, quality_notes, rated_by, created_at) VALUES (146, 75, 16, 2.00, 9.00, NULL, 4, '2026-01-14 08:53:21');


ALTER TABLE public.stock_receipt_item_ratings ENABLE TRIGGER ALL;

--
-- Data for Name: system_settings; Type: TABLE DATA; Schema: public; Owner: express_sale_user
--

ALTER TABLE public.system_settings DISABLE TRIGGER ALL;

INSERT INTO public.system_settings (id, key, value, value_type, description, is_public, updated_by, created_at, updated_at) VALUES (1, 'shop_name', 'Ma Boutique', 'string', 'Nom de la boutique', true, NULL, '2025-12-19 11:51:23.334656', '2025-12-19 11:51:23.334656');
INSERT INTO public.system_settings (id, key, value, value_type, description, is_public, updated_by, created_at, updated_at) VALUES (2, 'shop_address', '', 'string', 'Adresse boutique', true, NULL, '2025-12-19 11:51:23.334656', '2025-12-19 11:51:23.334656');
INSERT INTO public.system_settings (id, key, value, value_type, description, is_public, updated_by, created_at, updated_at) VALUES (3, 'reservation_default_days', '3', 'number', 'Délai réservation par défaut (jours)', false, NULL, '2025-12-19 11:51:23.334656', '2025-12-19 11:51:23.334656');
INSERT INTO public.system_settings (id, key, value, value_type, description, is_public, updated_by, created_at, updated_at) VALUES (4, 'low_stock_threshold', '5', 'number', 'Seuil alerte stock bas', false, NULL, '2025-12-19 11:51:23.334656', '2025-12-19 11:51:23.334656');
INSERT INTO public.system_settings (id, key, value, value_type, description, is_public, updated_by, created_at, updated_at) VALUES (5, 'credit_interest_rate', '0', 'number', 'Taux intérêt retard crédit (%)', false, NULL, '2025-12-19 11:51:23.334656', '2025-12-19 11:51:23.334656');
INSERT INTO public.system_settings (id, key, value, value_type, description, is_public, updated_by, created_at, updated_at) VALUES (6, 'currency_main', 'MGA', 'string', 'Devise principale', true, NULL, '2025-12-19 11:51:23.334656', '2025-12-19 11:51:23.334656');
INSERT INTO public.system_settings (id, key, value, value_type, description, is_public, updated_by, created_at, updated_at) VALUES (7, 'currency_secondary', 'CNY', 'string', 'Devise secondaire (achats)', true, NULL, '2025-12-19 11:51:23.334656', '2025-12-19 11:51:23.334656');


ALTER TABLE public.system_settings ENABLE TRIGGER ALL;

--
-- Data for Name: user_sessions; Type: TABLE DATA; Schema: public; Owner: express_sale_user
--

ALTER TABLE public.user_sessions DISABLE TRIGGER ALL;



ALTER TABLE public.user_sessions ENABLE TRIGGER ALL;

--
-- Data for Name: variant_attribute_values; Type: TABLE DATA; Schema: public; Owner: express_sale_user
--

ALTER TABLE public.variant_attribute_values DISABLE TRIGGER ALL;

INSERT INTO public.variant_attribute_values (id, variant_id, attribute_value_id, created_at) VALUES (93, 17, 42, '2026-01-14 09:25:18.403096');
INSERT INTO public.variant_attribute_values (id, variant_id, attribute_value_id, created_at) VALUES (94, 17, 51, '2026-01-14 09:25:18.410296');
INSERT INTO public.variant_attribute_values (id, variant_id, attribute_value_id, created_at) VALUES (95, 18, 43, '2026-01-14 09:25:37.662106');
INSERT INTO public.variant_attribute_values (id, variant_id, attribute_value_id, created_at) VALUES (96, 18, 52, '2026-01-14 09:25:37.668443');
INSERT INTO public.variant_attribute_values (id, variant_id, attribute_value_id, created_at) VALUES (97, 19, 42, '2026-01-14 09:26:49.744643');
INSERT INTO public.variant_attribute_values (id, variant_id, attribute_value_id, created_at) VALUES (98, 19, 53, '2026-01-14 09:26:49.750361');
INSERT INTO public.variant_attribute_values (id, variant_id, attribute_value_id, created_at) VALUES (99, 20, 56, '2026-01-14 09:29:32.612806');
INSERT INTO public.variant_attribute_values (id, variant_id, attribute_value_id, created_at) VALUES (100, 20, 42, '2026-01-14 09:29:32.615497');
INSERT INTO public.variant_attribute_values (id, variant_id, attribute_value_id, created_at) VALUES (101, 21, 56, '2026-01-14 11:17:56.5527');
INSERT INTO public.variant_attribute_values (id, variant_id, attribute_value_id, created_at) VALUES (102, 21, 42, '2026-01-14 11:17:56.557158');
INSERT INTO public.variant_attribute_values (id, variant_id, attribute_value_id, created_at) VALUES (103, 22, 56, '2026-01-14 11:18:29.536818');
INSERT INTO public.variant_attribute_values (id, variant_id, attribute_value_id, created_at) VALUES (104, 22, 43, '2026-01-14 11:18:29.540586');


ALTER TABLE public.variant_attribute_values ENABLE TRIGGER ALL;

--
-- Name: account_transactions_id_seq; Type: SEQUENCE SET; Schema: public; Owner: express_sale_user
--

SELECT pg_catalog.setval('public.account_transactions_id_seq', 150, true);


--
-- Name: account_types_id_seq; Type: SEQUENCE SET; Schema: public; Owner: express_sale_user
--

SELECT pg_catalog.setval('public.account_types_id_seq', 3, true);


--
-- Name: accounts_id_seq; Type: SEQUENCE SET; Schema: public; Owner: express_sale_user
--

SELECT pg_catalog.setval('public.accounts_id_seq', 10, true);


--
-- Name: attribute_types_id_seq; Type: SEQUENCE SET; Schema: public; Owner: express_sale_user
--

SELECT pg_catalog.setval('public.attribute_types_id_seq', 18, true);


--
-- Name: attribute_values_id_seq; Type: SEQUENCE SET; Schema: public; Owner: express_sale_user
--

SELECT pg_catalog.setval('public.attribute_values_id_seq', 58, true);


--
-- Name: audit_logs_id_seq; Type: SEQUENCE SET; Schema: public; Owner: express_sale_user
--

SELECT pg_catalog.setval('public.audit_logs_id_seq', 1, false);


--
-- Name: cash_count_denominations_id_seq; Type: SEQUENCE SET; Schema: public; Owner: express_sale_user
--

SELECT pg_catalog.setval('public.cash_count_denominations_id_seq', 27, true);


--
-- Name: cash_counts_id_seq; Type: SEQUENCE SET; Schema: public; Owner: express_sale_user
--

SELECT pg_catalog.setval('public.cash_counts_id_seq', 2, true);


--
-- Name: categories_id_seq; Type: SEQUENCE SET; Schema: public; Owner: express_sale_user
--

SELECT pg_catalog.setval('public.categories_id_seq', 26, true);


--
-- Name: coordinates_id_seq; Type: SEQUENCE SET; Schema: public; Owner: express_sale_user
--

SELECT pg_catalog.setval('public.coordinates_id_seq', 7, true);


--
-- Name: credit_installments_id_seq; Type: SEQUENCE SET; Schema: public; Owner: express_sale_user
--

SELECT pg_catalog.setval('public.credit_installments_id_seq', 15, true);


--
-- Name: credits_id_seq; Type: SEQUENCE SET; Schema: public; Owner: express_sale_user
--

SELECT pg_catalog.setval('public.credits_id_seq', 12, true);


--
-- Name: currency_rates_id_seq; Type: SEQUENCE SET; Schema: public; Owner: express_sale_user
--

SELECT pg_catalog.setval('public.currency_rates_id_seq', 9, true);


--
-- Name: customers_id_seq; Type: SEQUENCE SET; Schema: public; Owner: express_sale_user
--

SELECT pg_catalog.setval('public.customers_id_seq', 13, true);


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

SELECT pg_catalog.setval('public.freight_forwarders_id_seq', 2, true);


--
-- Name: installment_transactions_id_seq; Type: SEQUENCE SET; Schema: public; Owner: express_sale_user
--

SELECT pg_catalog.setval('public.installment_transactions_id_seq', 13, true);


--
-- Name: jobs_id_seq; Type: SEQUENCE SET; Schema: public; Owner: express_sale_user
--

SELECT pg_catalog.setval('public.jobs_id_seq', 1, false);


--
-- Name: locations_id_seq; Type: SEQUENCE SET; Schema: public; Owner: express_sale_user
--

SELECT pg_catalog.setval('public.locations_id_seq', 9, true);


--
-- Name: migrations_id_seq; Type: SEQUENCE SET; Schema: public; Owner: express_sale_user
--

SELECT pg_catalog.setval('public.migrations_id_seq', 22, true);


--
-- Name: notifications_id_seq; Type: SEQUENCE SET; Schema: public; Owner: express_sale_user
--

SELECT pg_catalog.setval('public.notifications_id_seq', 1, false);


--
-- Name: personal_access_tokens_id_seq; Type: SEQUENCE SET; Schema: public; Owner: express_sale_user
--

SELECT pg_catalog.setval('public.personal_access_tokens_id_seq', 47, true);


--
-- Name: product_attributes_id_seq; Type: SEQUENCE SET; Schema: public; Owner: express_sale_user
--

SELECT pg_catalog.setval('public.product_attributes_id_seq', 48, true);


--
-- Name: product_variant_locations_id_seq; Type: SEQUENCE SET; Schema: public; Owner: express_sale_user
--

SELECT pg_catalog.setval('public.product_variant_locations_id_seq', 40, true);


--
-- Name: product_variants_id_seq; Type: SEQUENCE SET; Schema: public; Owner: express_sale_user
--

SELECT pg_catalog.setval('public.product_variants_id_seq', 22, true);


--
-- Name: products_id_seq; Type: SEQUENCE SET; Schema: public; Owner: express_sale_user
--

SELECT pg_catalog.setval('public.products_id_seq', 25, true);


--
-- Name: reservation_deposits_id_seq; Type: SEQUENCE SET; Schema: public; Owner: express_sale_user
--

SELECT pg_catalog.setval('public.reservation_deposits_id_seq', 1, false);


--
-- Name: reservations_id_seq; Type: SEQUENCE SET; Schema: public; Owner: express_sale_user
--

SELECT pg_catalog.setval('public.reservations_id_seq', 10, true);


--
-- Name: sale_items_id_seq; Type: SEQUENCE SET; Schema: public; Owner: express_sale_user
--

SELECT pg_catalog.setval('public.sale_items_id_seq', 57, true);


--
-- Name: sales_id_seq; Type: SEQUENCE SET; Schema: public; Owner: express_sale_user
--

SELECT pg_catalog.setval('public.sales_id_seq', 34, true);


--
-- Name: stock_movements_id_seq; Type: SEQUENCE SET; Schema: public; Owner: express_sale_user
--

SELECT pg_catalog.setval('public.stock_movements_id_seq', 102, true);


--
-- Name: stock_receipt_item_ratings_id_seq; Type: SEQUENCE SET; Schema: public; Owner: express_sale_user
--

SELECT pg_catalog.setval('public.stock_receipt_item_ratings_id_seq', 146, true);


--
-- Name: stock_receipt_items_id_seq; Type: SEQUENCE SET; Schema: public; Owner: express_sale_user
--

SELECT pg_catalog.setval('public.stock_receipt_items_id_seq', 75, true);


--
-- Name: stock_receipts_id_seq; Type: SEQUENCE SET; Schema: public; Owner: express_sale_user
--

SELECT pg_catalog.setval('public.stock_receipts_id_seq', 30, true);


--
-- Name: suppliers_id_seq; Type: SEQUENCE SET; Schema: public; Owner: express_sale_user
--

SELECT pg_catalog.setval('public.suppliers_id_seq', 7, true);


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

SELECT pg_catalog.setval('public.users_id_seq', 7, true);


--
-- Name: variant_attribute_values_id_seq; Type: SEQUENCE SET; Schema: public; Owner: express_sale_user
--

SELECT pg_catalog.setval('public.variant_attribute_values_id_seq', 104, true);


--
-- PostgreSQL database dump complete
--

\unrestrict pBqctdlmIuYKYpdMjqJLVBeG8CQsgnijqPlFGmHNuw25bndcyQFOckGJzyP7JCC

