# Arborescence complète du projet — Boutique E-Commerce

Convention : chaque fonctionnalité = son propre fichier. Aucun fichier
monolithique. Les fichiers `actions/` ne produisent **aucun HTML** : ils
traitent une requête (POST/GET) et répondent en JSON ou redirigent.

```
ecommerce-boutique/
│
├── index.php                        # Page d'accueil (catalogue)
├── category.php                     # Liste produits d'une catégorie (?slug=)
├── product.php                      # Fiche produit détaillée (?id=)
├── cart.php                         # Page panier
├── checkout.php                     # Formulaire de commande
├── order-confirmation.php           # Page de confirmation après commande
├── doc_lws.md                       # Guide de déploiement sur LWS
├── .htaccess                        # Headers sécurité + réécriture d'URL
│
├── config/
│   ├── config.php                   # Constantes globales (nom du site, URL, devise)
│   ├── db.php                       # Connexion PDO sécurisée (singleton)
│   └── mail-config.php              # Paramètres SMTP (dev vs prod)
│
├── includes/
│   ├── header.php                   # <head> + ouverture <body>
│   ├── footer.php                   # Pied de page (contact, réseaux, mentions)
│   ├── navbar.php                   # Menu principal (Accueil, Services, Galeries...)
│   ├── functions.php                # Helpers génériques (formatage prix, slugify)
│   ├── csrf.php                     # Génération/validation des jetons CSRF
│   ├── sanitize.php                 # Échappement centralisé (htmlspecialchars wrapper)
│   ├── session.php                  # Démarrage sécurisé de session (cookie httponly)
│   ├── comment-form.php             # Formulaire d'ajout de commentaire
│   ├── auth.php                     # Vérification de connexion admin (guard)
│   └── admin/
│       ├── admin-header.php         # <head> back-office
│       ├── admin-sidebar.php        # Menu latéral admin
│       └── admin-footer.php         # Pied de page back-office
│
├── assets/
│   ├── css/
│   │   ├── variables.css            # Variables charte : --color-primary, --color-gold...
│   │   ├── reset.css                # Normalize / reset de base
│   │   ├── main.css                 # Styles globaux (typo, layout général)
│   │   ├── navbar.css               # Styles du menu
│   │   ├── product-card.css         # Carte produit (catalogue)
│   │   ├── product-detail.css       # Page détail produit
│   │   ├── checkout.css             # Formulaire de commande
│   │   └── admin/
│   │       ├── admin-layout.css     # Structure générale back-office
│   │       ├── admin-dashboard.css  # Cartes statistiques
│   │       ├── admin-products.css   # Tableau/formulaire produits
│   │       ├── admin-orders.css     # Tableau/détail commandes
│   │       └── admin-login.css      # Page de connexion admin
│   │
│   ├── js/
│   │   ├── cart.js                  # Logique panier (localStorage)
│   │   ├── navbar-mobile.js         # Gestion du menu hamburger
│   │   ├── checkout.js              # Validation front du formulaire de commande
│   │   ├── product-filter.js        # Filtrage catalogue par catégorie
│   │   └── admin/
│   │       ├── admin-media.js       # Upload/prévisualisation image-vidéo produit
│   │       ├── admin-products.js    # Interactions CRUD produits (confirmations, AJAX)
│   │       ├── admin-orders.js      # Changement de statut commande (AJAX)
│   │       └── admin-charts.js      # Rendu des graphiques (non utilisé)
│   │   ├── product-gallery.js       # Galerie d'images sur la fiche produit
│   │   └── loader.js                # Affiche un spinner pendant le chargement de la page
│   │
│   └── images/
│       ├── logo.png
│       └── placeholder-product.jpg
│
├── uploads/
│   ├── products/                    # Médias produits uploadés (renommés aléatoirement)
│   └── .htaccess                    # "php_flag engine off" — bloque l'exécution PHP
│
├── actions/                          # Traitements back-end (pas de HTML, JSON/redirect)
│   ├── cart-add.php
│   ├── cart-remove.php
│   ├── cart-update.php
│   ├── process_order.php            # Validation + insertion commande + envoi email
│   ├── comment-create.php           # Traitement du formulaire de commentaire
│   ├── send-confirmation-email.php  # Fonction d'envoi (PHPMailer)
│   └── admin/
│       ├── auth-login.php           # Traitement connexion (anti-bruteforce)
│       ├── auth-logout.php
│       ├── product_crud.php         # CRUD (Create, Update, Delete) des produits
│       ├── category-create.php
│       └── order-status-update.php
│
├── admin/                            # Pages HTML du back-office (protégées par auth.php)
│   ├── login.php
│   ├── index.php                    # Dashboard (CA mensuel, produit le + vendu)
│   ├── products.php                 # Liste produits
│   ├── product-form.php             # Ajout/édition produit (?id= si édition)
│   ├── categories.php               # Gestion des catégories
│   ├── orders.php                   # Liste chronologique des commandes
│   └── order-detail.php             # Détail d'une commande (?id=)
│
├── vendor/                           # Dépendances Composer (PHPMailer)
│   └── ...
│
├── scripts/
│   └── create-admin.php             # Script CLI one-shot pour créer le 1er compte admin
│
├── database/
│   └── schema.sql                   # Script SQL fourni (Étape 1)
│
└── composer.json                    # Déclare phpmailer/phpmailer
```

## Pourquoi ce découpage

- **`config/`** : tout ce qui touche à l'environnement (BDD, mail) est isolé
  → un seul endroit à modifier lors du passage local → LWS.
- **`includes/`** : briques réutilisables sans logique métier lourde (affichage,
  sécurité transverse). `auth.php` agit comme un "garde" inclus en haut de
  chaque page admin.
- **`actions/`** : aucune de ces pages n'affiche du HTML. Elles reçoivent une
  requête, valident, interrogent la BDD via PDO préparé, et répondent
  (JSON pour l'AJAX du panier, redirection pour la commande). Ça isole
  totalement la logique métier de l'affichage.
- **`assets/js/`** : un fichier = une responsabilité (`cart.js` ne connaît
  rien du checkout, `admin-media.js` ne connaît rien des statistiques).
- **`uploads/.htaccess`** : désactive l'exécution PHP dans ce dossier — même
  si un attaquant upload un `.php` déguisé, il ne pourra jamais l'exécuter.

Dans l'étape suivante, je vous fournis le contenu de `config/db.php` et
`assets/css/variables.css` (avec les couleurs bordeaux `#5C121E` / doré
`#C59B27` de votre charte).
