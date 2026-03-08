# Express Sale - Manuel d'utilisation

## Concepts fondamentaux — Définitions

Un **produit** est un article que vous vendez dans votre boutique. Exemple : "T-shirt Col Rond" est un produit. Chaque produit a un nom, un prix de vente estimatif, une catégorie optionnelle, et des attributs (caractéristiques) qui définissent ses variantes. Dans Express Sale, un produit seul ne peut pas être vendu directement — il faut au moins une variante.

Une **variante** est une déclinaison spécifique d'un produit selon ses attributs. Exemple : le produit "T-shirt Col Rond" peut avoir les variantes "Taille S / Couleur Rouge", "Taille M / Couleur Bleu", etc. Chaque variante a son propre stock, son propre seuil d'alerte, et son propre historique de lots (batches). Le stock total d'un produit = somme des stocks de toutes ses variantes. Les variantes sont obligatoires : toute vente, réservation, transfert ou réapprovisionnement se fait sur une variante, jamais directement sur un produit.

Un **attribut** est une caractéristique qui différencie les variantes d'un produit. Exemples : Taille (S, M, L, XL), Couleur (rouge, bleu, noir), Pointure (36, 37, 38…). Un attribut peut être une liste déroulante (valeurs prédéfinies), un texte libre, ou un nombre.

Un **lot (batch)** est un groupe de stock créé automatiquement à chaque réapprovisionnement. Express Sale utilise la méthode FIFO : les lots les plus anciens sont vendus en premier.

Un **réapprovisionnement** (ou réception) est une commande fournisseur. Il permet d'enregistrer les produits reçus, les quantités, les prix d'achat, les frais de transport, et de mettre à jour le stock automatiquement.

Un **emplacement** est un endroit de stockage physique (magasin principal, entrepôt, réserve…). Le stock est suivi par emplacement. Les ventes rapides et crédits utilisent uniquement le magasin principal ; les réservations peuvent utiliser tous les emplacements.

Un **compte** est un compte de trésorerie (caisse espèces, compte bancaire, mobile money…). Toutes les transactions financières (ventes, paiements fournisseurs, dépenses) sont associées à un compte.

Une **vente rapide** est une vente immédiate avec paiement complet sur le moment. Une **réservation** est une vente différée avec acompte, où le client viendra récupérer ses produits plus tard. Un **crédit** est une vente avec paiement échelonné en plusieurs échéances.


## Introduction générale

Express Sale est une application de gestion pour entreprises de prêt-à-porter et vente de produits physiques. Elle permet de gérer : produits et stocks, ventes et finances, approvisionnements. L'hébergement est local (données sur votre réseau), multi-utilisateur (plusieurs employés simultanément avec rôles distincts), et multi-plateforme (ordinateurs, tablettes, smartphones sur le même réseau local).

Les fonctionnalités principales incluent : organisation des produits (catégories, sous-catégories, variantes), réapprovisionnement (commandes fournisseurs, devises, transitaires), gestion des stocks (suivi par emplacement, transferts, pertes), ventes (rapides, réservations, crédit, remises, paiements multiples), trésorerie (flux financiers via comptes espèces/banque/mobile money), gestion FIFO (lots les plus anciens sortent en premier), et dépenses (suivi, paiements, prévisions).

---

## Produits — Créer un nouveau produit

Pour créer un produit : ouvrir l'onglet Produits, cliquer sur le bouton bleu "Nouveau produit".

Informations obligatoires : nom du produit, prix de vente estimatif (modifiable lors des ventes avec remises).

Informations optionnelles : description, catégorie et sous-catégorie (ex : Catégorie "Haut", Sous-catégorie "T-shirt").

Les attributs sont les caractéristiques du produit (taille, pointure, couleur, etc.). Pour ajouter un attribut existant : dans la section "Attributs Configurés", cliquer sur Ajouter, puis sélectionner l'attribut dans la liste.

Pour créer un nouvel attribut : cliquer sur "Nouveau Type", remplir le formulaire avec le nom technique (identifiant unique, ex: "taille_vetement"), le nom d'affichage (ex: "Taille"), le type de saisie (liste déroulante, texte libre, ou nombre), les valeurs possibles pour liste déroulante, et si l'attribut est obligatoire. Pour modifier des attributs existants : aller dans Produits > Attributs.

Finaliser la création en cliquant sur "Créer".

---

## Produits — Gérer les produits existants

Après création, on est redirigé vers la liste des produits. On peut : rechercher par nom, filtrer par statut (Actif/Inactif). Les produits inactifs ne sont plus visibles lors des ventes et autres opérations.

En cliquant sur un produit, on voit la fiche avec : prix de vente de base, nombre de variantes (total créées), stock total (quantité totale incluant réservations), chiffre d'affaires net (montant total des ventes), coût total (prix d'achat + frais d'approvisionnement), bénéfice (prix de vente - coûts), marge (taux de rentabilité en %).

---

## Variantes — Pourquoi et comment

Les variantes sont obligatoires pour effectuer toute opération dans l'application. Elles constituent la base de tous les calculs et permettent les mouvements de stock. Le stock total d'un produit = somme des stocks de ses variantes.

Pour créer une variante : ouvrir le détail d'un produit, dans la section Variantes cliquer sur "Ajouter une variante".

Informations : image (optionnel, pour identification visuelle), seuil d'alerte (quantité minimale avant rupture, génère notifications pour les admins), caractéristiques obligatoires (valeurs des attributs du produit, ex: si attribut "couleur" → spécifier "marron"). Les attributs avec une étoile rouge sont obligatoires.

Important : toujours vérifier qu'une variante identique n'existe pas déjà avant de créer.

---

## Réapprovisionnement — Présentation

L'onglet Réapprovisionnement gère les commandes fournisseurs et réceptions de marchandises. Statuts principaux : "En cours" (commandes envoyées mais non reçues), "Validé" (commandes réceptionnées et traitées).

---

## Réapprovisionnement — Créer une nouvelle réception

Étape 1 (Informations de base) : cliquer sur "Nouvelle Réception", choisir la devise (à configurer dans Trésorerie > Conversion), sélectionner produits et variantes avec quantités, définir le prix d'achat (identique pour toutes les variantes d'un même produit), vérifier les prix totaux, puis cliquer sur Suivant.

Étape 2 (Transport) : indiquer la date d'arrivée approximative, sélectionner le fournisseur et le transitaire (pour en créer : Fournisseurs > Nouveau Fournisseur ou Transitaires > Nouveau Transitaire), puis cliquer sur Suivant.

Étape 3 (Paiement) : choisir le compte à débiter pour payer le fournisseur, le paiement sera marqué comme effectué, puis cliquer sur Valider.

---

## Réapprovisionnement — Suivre une commande

Après création : statut "En attente". Pour marquer envoyé : trouver la commande dans la liste, cliquer sur l'œil (colonne Actions), puis "Marquer envoyé".

Pendant transport : statut "En transit". Si la commande est en acheminement, cliquer sur "Marquer en transit".

À la réception : statut "Arrivé". Cliquer sur "Marquer Arrivé", une fenêtre de vérification s'affiche pour comparer quantités commandées vs reçues (on peut uniquement diminuer). Exemple : 20 commandées, 19 reçues → inscrire 19. Puis valider la réception.

---

## Réapprovisionnement — Traiter une réception

Après avoir marqué "Arrivé", deux actions disponibles :

1. Paiements : gérer les frais liés à la commande (payer le transitaire, autres dépenses avec sélection de la catégorie ou création d'une nouvelle).

2. Évaluer : évaluer la qualité des produits reçus avec critères (notes sur 10) : qualité générale du produit, respect des spécifications (ex: couleur jaune commandée mais jaune pâle reçu). Si erreur importante : utiliser "Transférer vers un autre variant" (ex: pointure 36 commandée, 40 reçue). Une fois les notes attribuées, cliquer sur Valider. Pour consulter l'historique : cliquer sur "Voir les évaluations".

---

## Réapprovisionnement — Répartition des coûts

Étape cruciale pour le calcul des bénéfices. Accéder via "Répartir les coûts" dans le détail du réapprovisionnement.

Statistiques affichées : Transport Total (frais de transit), Autres coûts (charges supplémentaires).

Méthodes de répartition : Par prix (proportionnel au prix des produits), Par quantité (proportionnel aux quantités), Par pondération (quantité × prix, recommandé).

Tableau de répartition : Produit (variante commandée), Prix Actuel (prix de vente actuel, modifiable), Prix Fournisseur (prix d'achat unitaire), Transport/U (coût transport par unité, ajustable), Autre/U (autres frais par unité, ajustable), Coût TOT/U (= Prix fournisseur + Transport/U + Autre/U), Coût Total (Coût TOT/U × Quantité), Marge (%), Profit/U, Profit Total, Prix de vente unitaire.

En modifiant une valeur, le système ajuste automatiquement les autres lignes pour maintenir l'équilibre des coûts totaux.

Validation finale : vérifier tous les prix de vente, cliquer sur "Valider définitivement", choisir l'emplacement de stockage. Le stock est mis à jour automatiquement.

---

## Réapprovisionnement — Gestion des lots (Batches)

Un lot (batch) est créé automatiquement pour chaque variante réceptionnée. Pour consulter les lots : Produits > cliquer sur un produit > section Variantes > sélectionner une variante > cliquer sur le bouton Batch > visualiser l'historique complet.

Principe FIFO : les lots les plus anciens sont utilisés en premier lors des ventes.

---

## Ventes — Point de Vente

Toutes les opérations de vente (ventes rapides, réservations et crédits) se font via l'onglet Point de Vente. Pour sélectionner le type de vente : en haut à droite, sous le bouton de déconnexion, utiliser le menu déroulant pour choisir le type d'opération.

---

## Ventes Rapides — Configuration

Identification du client : trois options disponibles : créer un nouveau client, rechercher un client existant (par nom ou code client), ou vente anonyme (aucune donnée client enregistrée).

Sélection des produits : recherche par nom ou code SKU, filtres par catégorie/sous-catégorie et par attributs (ex: afficher uniquement pointures 39). Pour ajouter un produit au panier : cliquer sur le produit, une fenêtre popup s'ouvre avec détails, infos stocks, liste des variantes disponibles. Dans "Choisir une variante" : seules les variantes du magasin principal sont affichées, uniquement celles en stock et non réservées. Définir la quantité, puis cliquer sur "Ajouter au panier".

Finalisation : dans le panier, visualiser produits et prix, appliquer des remises si nécessaire, choisir le mode de paiement, sélectionner le compte où l'argent sera versé, cliquer sur Valider.

---

## Ventes Rapides — Consultation et annulation

Accès : onglet Ventes Rapides. Fonctionnalités : recherche par numéro de vente, tri par vendeur, consultation des détails.

Annulation (processus en deux étapes) :

Étape 1 — Annuler la vente : ouvrir le détail de la vente, aller dans l'onglet Facture, en bas cliquer sur "Annuler la vente". Effet : le stock est restauré automatiquement. Attention : l'annulation de la vente ne rembourse PAS automatiquement l'argent.

Étape 2 — Annuler la transaction financière (optionnel mais recommandé) : dans le détail de la vente, aller dans la section Transaction, cliquer sur "Annuler la transaction". Condition : le solde du compte doit être suffisant. Effet : l'argent est retiré du compte correspondant. Restriction : seul le créateur de la transaction peut l'annuler. Si ça ne fonctionne pas, se connecter avec le compte qui a effectué la vente.

---

## Réservations — Créer une réservation

Étape 1 : dans l'onglet Point de Vente, sélectionner "Réservation". Identifier le client (obligatoire pour les réservations).

Étape 2 (Sélection produits) : différence avec ventes rapides — on peut réserver depuis tous les emplacements (pas uniquement le magasin principal). Les produits sont bloqués dans le stock jusqu'à finalisation ou annulation.

Étape 3 (Paramètres) : dans le panier, définir les remises (optionnel), la date limite d'expiration (génère des rappels automatiques), l'acompte (montant payé immédiatement, le reste sera payé lors du retrait), le mode de paiement et le compte pour l'acompte. Puis cliquer sur Valider.

---

## Réservations — Gérer les réservations

Accès : onglet Réservations. Statuts : Confirmé (en attente de retrait), Terminé (client a récupéré et payé intégralement), Annulé (stock restauré).

Filtres disponibles : tri par date, tri par statut, recherche par client ou numéro de réservation.

Finaliser une réservation : cliquer sur la réservation, dans le détail cliquer sur "Compléter la réservation", encaisser le solde restant (montant total - acompte), sélectionner mode de paiement et compte, valider. Le statut passe à Terminé.

Annuler une réservation (processus similaire aux ventes rapides) :

Étape 1 — Annuler la réservation : ouvrir le détail, cliquer en haut sur Annuler. Effet : stock restauré, statut passe à Annulé.

Étape 2 — Annuler les transactions (recommandé) : annuler la transaction de l'acompte si versé, aller dans la section Transaction, cliquer sur "Annuler la transaction", vérifier le solde disponible. Restriction : seul le créateur de la transaction peut l'annuler.

---

## Ventes à Crédit — Créer une vente à crédit

Étape 1 : dans l'onglet Point de Vente, sélectionner "Crédit". Identifier le client (obligatoire).

Étape 2 : sélection des produits (identique aux ventes rapides et réservations).

Étape 3 (Planification des paiements) : configurer l'échéancier — dates d'échéance (répartir le paiement dans le temps), montants (définir le montant de chaque échéance), rappels (notifications automatiques avant chaque échéance). Exemple d'échéancier pour 300 000 Ar : Échéance 1 (15/02) : 100 000 Ar, Échéance 2 (15/03) : 100 000 Ar, Échéance 3 (15/04) : 100 000 Ar. Valider la vente à crédit.

---

## Ventes à Crédit — Gérer les crédits

Accès : onglet Crédits. Statuts : Actif (crédit créé, aucun paiement reçu), Partiellement payé (au moins une échéance payée, reste dû), Terminé (toutes les échéances payées, crédit soldé).

Fonctionnalités : tri par statut, recherche par client, visualisation des échéances à venir, historique des paiements.

Enregistrer un paiement : ouvrir le détail du crédit, dans la section Échéances identifier l'échéance à payer, cliquer sur "Enregistrer un paiement", saisir le montant payé (peut être partiel), le mode de paiement, le compte de destination, valider. Le statut se met à jour automatiquement.

---

## Ventes à Crédit — Suivi et notifications

Système de rappels automatiques : notifications avant chaque échéance, alertes pour paiements en retard, tableau de bord des créances en cours.

Gestion des impayés : le système marque automatiquement l'échéance comme "En retard" et envoie des notifications. Options : renégocier l'échéancier (modifier les dates), marquer comme Contentieux, enregistrer des paiements partiels. L'annulation d'une vente à crédit suit le même processus que les ventes rapides.

---

## Récapitulatif des types de vente

Vente Rapide : paiement immédiat et complet, livraison immédiate, stock du magasin principal uniquement, pour achats immédiats en magasin.

Réservation : acompte + solde à la livraison, livraison différée (date limite), stock de tous les emplacements, pour commandes à retirer plus tard.

Crédit : paiement échelonné (plusieurs échéances), livraison immédiate, stock du magasin principal, pour ventes avec facilités de paiement.

---

## Dashboard et Statistiques

Le Dashboard (tableau de bord) offre une vue d'ensemble des indicateurs clés : synthèse des ventes, état du stock, accès rapide aux modules. Il est accessible via l'onglet principal ou en naviguant vers /dashboard. Il affiche les KPIs principaux de l'activité (chiffre d'affaires, bénéfices, stock, créances).

Les statistiques par produit sont consultables dans la fiche produit (chiffre d'affaires net, coût total, bénéfice, marge). Les statistiques de ventes sont accessibles depuis l'onglet Ventes Rapides, Réservations et Crédits.