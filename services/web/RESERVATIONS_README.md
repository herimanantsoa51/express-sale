# 📋 Système de Gestion de Réservations - Apple Design

Un système complet de gestion de réservations avec un design inspiré d'Apple, incluant des filtres avancés, des vues multiples, et un système de validation/paiement en 4 étapes.

## ✨ Fonctionnalités Principales

### 📊 Page Liste des Réservations
- **Vues Multiples** : Tableau détaillé ou grille de cartes
- **Filtres Avancés** : 
  - Recherche textuelle (N° vente, client, code client)
  - Statut (pending, confirmed, partial_paid, completed, expired, cancelled)
  - Vendeur
  - Dates de réservation et d'expiration
  - Filtres rapides (actives, expirées, expiration proche)
- **Tri Dynamique** : Colonnes triables en ASC/DESC
- **Sélection Multiple** : Actions groupées sur les réservations
- **Export** : CSV et PDF avec filtres préservés
- **Statistiques en Temps Réel** : Cartes de stats par statut
- **Pagination** : Navigation fluide entre les pages

### 🔍 Page Détails de Réservation
- **Layout 2 Colonnes** : Informations principales + sidebar d'actions
- **Informations Complètes** :
  - Détails de la réservation (dates, statut, progression)
  - Informations client avec avatar
  - Liste des articles réservés avec images
  - Historique des transactions
  - Vente associée
- **Indicateurs Visuels** :
  - Alertes pour réservations expirantes/expirées
  - Barre de progression du paiement
  - Badges de statut colorés
  - Statistiques rapides
- **Actions** : Validation, paiement, annulation

### 💳 Modal de Validation & Paiement (4 Étapes)
1. **Confirmation** : Récapitulatif de la réservation
2. **Sélection du Compte** : Liste des comptes cash/mobile money
3. **Montant** : 
   - Input avec validation
   - Boutons rapides (50%, 75%, 100%)
   - Zone de notes optionnelle
4. **Validation Finale** : Récapitulatif avant confirmation

## 🏗️ Architecture du Projet

```
frontend/src/
├── components/
│   └── reservations/
│       ├── StatusBadge.jsx          # Badge de statut coloré
│       ├── StatusBadge.css
│       ├── ReservationFilters.jsx   # Filtres avancés
│       ├── ReservationFilters.css
│       ├── ReservationCard.jsx      # Carte de réservation
│       ├── ReservationCard.css
│       ├── ReservationTable.jsx     # Tableau des réservations
│       ├── ReservationTable.css
│       ├── PaymentModal.jsx         # Modal de paiement (4 steps)
│       └── PaymentModal.css
│
├── pages/
│   ├── ReservationsPage.jsx        # Page liste
│   ├── ReservationsPage.css
│   ├── ReservationDetailPage.jsx   # Page détails
│   └── ReservationDetailPage.css
│
├── hooks/
│   ├── useReservations.js           # Hook pour gérer les réservations
│   ├── useAccounts.js               # Hook pour les comptes
│   └── useUsers.js                  # Hook pour les utilisateurs
│
├── services/
│   ├── api.js                       # Configuration Axios
│   ├── reservationsService.js       # API réservations
│   ├── accountService.js            # API comptes
│   └── usersService.js              # API utilisateurs
│
├── utils/
│   ├── formatters.js                # Utilitaires de formatage
│   └── validators.js                # Fonctions de validation
│
└── styles/
    ├── variables.css                # Variables CSS (thèmes)
    ├── reset.css                    # Reset CSS
    └── global.css                   # Styles globaux
```

## 🎨 Design System - Apple Style

### Principes
- **Minimalisme** : Espace négatif généreux, hiérarchie claire
- **Typographie** : SF Pro-like (-apple-system, BlinkMacSystemFont)
- **Couleurs** : Palette limitée avec accents subtils
- **Transitions** : Douces (300ms cubic-bezier)
- **Ombres** : Subtiles et élégantes
- **Border-radius** : 12px (cartes), 16px (modals)

### Thèmes
- ✅ **Thème Clair** : Fond blanc, texte sombre
- ✅ **Thème Sombre** : Fond noir, texte clair
- 🔄 Switch automatique via `data-theme="dark"` sur `<html>`

### Variables CSS Clés
```css
--primary: #0071e3 / #0a84ff (dark)
--success: #30d158
--warning: #ff9f0a
--danger: #ff3b30 / #ff453a (dark)
--bg-primary: #ffffff / #000000 (dark)
--border-radius: 12px
--border-radius-lg: 16px
--transition-speed: 0.3s
```

## 🔌 API Endpoints

### Réservations
```javascript
GET    /api/reservations                    // Liste avec filtres
GET    /api/reservations/{id}               // Détails
POST   /api/reservations/{id}/validate      // Validation & paiement
POST   /api/reservations/{id}/cancel        // Annulation
GET    /api/reservations/stats              // Statistiques
GET    /api/reservations/export/csv         // Export CSV
GET    /api/reservations/export/pdf         // Export PDF
```

### Paramètres de Filtrage
```javascript
{
  status: 'confirmed',                       // Statut
  user_id: 5,                                // Vendeur
  search: 'VNT-2024-00123',                  // Recherche
  reservation_date_from: '2024-01-01',       // Date de début
  reservation_date_to: '2024-01-31',         // Date de fin
  expiry_date_from: '2024-02-01',            // Expiration de début
  expiry_date_to: '2024-02-28',              // Expiration de fin
  active_only: true,                         // Uniquement actives
  expired_only: false,                       // Uniquement expirées
  expiring_soon_days: 7,                     // Expire dans X jours
  sort_by: 'expiry_date',                    // Tri
  sort_order: 'asc',                         // Ordre
  per_page: 15,                              // Résultats par page
  page: 1                                    // Page courante
}
```

### Format de Réponse
```javascript
{
  "data": [
    {
      "id": 6,
      "sale_number": "VNT-20260107-0009",
      "sale_id": 16,
      "reservation_date": "2026-01-07T20:48:42.000000Z",
      "expiry_date": "2026-01-29T00:00:00.000000Z",
      "total_amount": 127398767,
      "deposit_amount": 15000,
      "remaining_amount": 127383767,
      "status": "confirmed",
      "customer": {
        "id": 3,
        "name": "Vaovao misy",
        "code": "CL-20260107-0002"
      }
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 15,
    "total": 5
  }
}
```

## 🚀 Installation & Utilisation

### 1. Installation des Dépendances
```bash
cd frontend
npm install
```

### 2. Configuration de l'API
Créer un fichier `.env` :
```env
VITE_API_URL=http://localhost:8000/api
```

### 3. Lancement du Serveur de Développement
```bash
npm run dev
```

### 4. Build pour Production
```bash
npm run build
```

## 📱 Responsive Design

### Breakpoints
- **Mobile** : < 640px (Single column, full-width modals)
- **Tablet** : 640px - 1024px (2 colonnes sur détails)
- **Desktop** : > 1024px (Layout complet)

### Adaptations Mobile
- Filtres en colonne
- Stats en grille 1 colonne
- Tableau avec scroll horizontal
- Modal plein écran
- Navigation simplifiée

## ⚡ Performances

### Optimisations
- **Lazy Loading** : Images des produits
- **Debounced Search** : 300ms
- **Skeleton Loaders** : États de chargement
- **Optimistic Updates** : Actions rapides
- **Pagination** : Charge uniquement les données nécessaires

## ♿ Accessibilité

- ✅ ARIA labels complets
- ✅ Navigation au clavier
- ✅ Contrast ratio 4.5:1 minimum
- ✅ Screen reader support
- ✅ Focus management dans les modals

## 🎯 Utilisation des Hooks

### useReservations
```javascript
const {
  reservations,        // Liste des réservations
  loading,            // État de chargement
  error,              // Erreur éventuelle
  meta,               // Métadonnées (pagination)
  filters,            // Filtres actifs
  selectedReservations, // Réservations sélectionnées
  viewMode,           // Mode d'affichage (table/card)
  stats,              // Statistiques
  updateFilters,      // Mettre à jour les filtres
  resetFilters,       // Réinitialiser les filtres
  toggleSelection,    // Sélectionner/désélectionner
  selectAll,          // Tout sélectionner
  deselectAll,        // Tout désélectionner
  changePage,         // Changer de page
  exportData,         // Exporter les données
  setViewMode,        // Changer le mode d'affichage
  debouncedSearch     // Recherche avec debounce
} = useReservations();
```

### useAccounts
```javascript
const {
  accounts,   // Liste des comptes cash/mobile money
  loading,    // État de chargement
  error,      // Erreur éventuelle
  refetch     // Recharger les comptes
} = useAccounts();
```

### useUsers
```javascript
const {
  users,      // Liste des utilisateurs
  loading,    // État de chargement
  error,      // Erreur éventuelle
  refetch     // Recharger les utilisateurs
} = useUsers();
```

## 🎨 Personnalisation du Thème

### Changer les Couleurs
Modifier les variables dans `styles/variables.css` :
```css
:root {
  --primary: #YOUR_COLOR;
  --success: #YOUR_COLOR;
  /* etc. */
}

[data-theme="dark"] {
  --primary: #YOUR_DARK_COLOR;
  /* etc. */
}
```

### Toggle du Thème
```javascript
// Ajouter un bouton de switch
const toggleTheme = () => {
  const html = document.documentElement;
  const currentTheme = html.getAttribute('data-theme');
  html.setAttribute('data-theme', currentTheme === 'dark' ? 'light' : 'dark');
};
```

## 🐛 Gestion des Erreurs

### States d'Erreur
- **Loading** : Spinner avec message
- **Error** : Message d'erreur avec bouton de retry
- **Empty** : Message et call-to-action

### Validation
- Formulaires avec validation temps réel
- Messages d'erreur contextuels
- Blocage des actions invalides

## 📝 Props des Composants

### StatusBadge
```javascript
<StatusBadge 
  status="confirmed"    // 'pending' | 'confirmed' | 'partial_paid' | etc.
  withDot={false}      // Afficher un point animé
  className=""         // Classes CSS supplémentaires
/>
```

### ReservationCard
```javascript
<ReservationCard 
  reservation={obj}    // Objet réservation
  onSelect={fn}       // Callback de sélection
  isSelected={bool}   // État de sélection
/>
```

### PaymentModal
```javascript
<PaymentModal 
  isOpen={bool}       // État ouvert/fermé
  onClose={fn}        // Callback de fermeture
  reservation={obj}   // Objet réservation
  onSubmit={fn}       // Callback de soumission
/>
```

## 🔒 Sécurité

- ✅ Validation côté client ET serveur
- ✅ Protection CSRF (via Laravel Sanctum)
- ✅ Authentification JWT
- ✅ Sanitization des inputs
- ✅ Gestion des permissions

## 📚 Dépendances Principales

```json
{
  "react": "^18.x",
  "react-dom": "^18.x",
  "react-router-dom": "^6.x",
  "axios": "^1.x"
}
```

## 🎓 Bonnes Pratiques Implémentées

- ✅ **Composants Atomiques** : Réutilisables et isolés
- ✅ **Custom Hooks** : Logique métier séparée
- ✅ **CSS Modules** : Styles scopés
- ✅ **Error Boundaries** : Gestion élégante des erreurs
- ✅ **Loading States** : Feedback visuel constant
- ✅ **Responsive First** : Mobile-first approach
- ✅ **Accessibilité** : WCAG 2.1 AA
- ✅ **Performance** : Optimisations multiples

## 📞 Support

Pour toute question ou problème, consultez la documentation de l'API backend ou contactez l'équipe de développement.

---

**Développé avec ❤️ en suivant les principes de design d'Apple**
