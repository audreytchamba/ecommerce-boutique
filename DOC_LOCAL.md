# Guide d'Exécution en Local (XAMPP / LAMPP)

Ce guide vous explique comment installer, configurer et lancer le projet e-commerce sur votre machine locale pour le développement.

## 1. Prérequis

Avant de commencer, assurez-vous d'avoir installé les outils suivants :

- **Un environnement de serveur local :**
  - **XAMPP** (pour Windows/macOS) : [Télécharger XAMPP](https://www.apachefriends.org/fr/index.html)
  - **LAMPP** (pour Linux) ou une pile équivalente (Apache, MySQL/MariaDB, PHP).
- **Composer :** Le gestionnaire de dépendances pour PHP. [Installer Composer](https://getcomposer.org/download/).
- **Un terminal** ou une invite de commandes.
- **Un éditeur de code** (ex: Visual Studio Code).

## 2. Installation du Projet

### a. Placer les fichiers

1.  Téléchargez ou clonez ce projet.
2.  Placez l'intégralité du dossier du projet (`ecommerce-boutique/`) dans le répertoire web de votre serveur local :
    -   **Sur XAMPP (Windows) :** `C:\xampp\htdocs\`
    -   **Sur LAMPP (Linux) :** `/var/www/html/`

Le chemin final devrait ressembler à `C:\xampp\htdocs\ecommerce-boutique`.

### b. Installer les dépendances

Ouvrez un terminal, naviguez jusqu'au dossier du projet et exécutez Composer pour installer les dépendances (notamment PHPMailer pour les e-mails).

```bash
cd C:\xampp\htdocs\ecommerce-boutique
composer install
```

Un dossier `vendor/` sera créé à la racine du projet.

## 3. Configuration de la Base de Données

### a. Créer la base de données

1.  Lancez les serveurs **Apache** et **MySQL** depuis le panneau de contrôle de XAMPP.
2.  Ouvrez votre navigateur et allez sur `http://localhost/phpmyadmin/`.
3.  Cliquez sur l'onglet **"Bases de données"**.
4.  Dans le champ "Créer une base de données", entrez `ecommerce_boutique`.
5.  Pour l'encodage, choisissez **`utf8mb4_unicode_ci`** et cliquez sur "Créer".

### b. Importer le schéma

1.  Une fois la base de données créée, sélectionnez-la dans la colonne de gauche.
2.  Cliquez sur l'onglet **"Importer"**.
3.  Cliquez sur "Choisir un fichier" et sélectionnez le fichier `database/schema.sql` qui se trouve dans le projet.
4.  Assurez-vous que l'encodage du fichier est bien `utf-8`.
5.  Cliquez sur le bouton **"Exécuter"** en bas de page.

Toutes les tables et les catégories par défaut seront créées.

### c. Vérifier la connexion

Ouvrez le fichier `config/db.php`. Par défaut, la configuration locale est la suivante :

```php
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'ecommerce_boutique');
define('DB_USER', 'root');
define('DB_PASS', 'NouveauMotDePasseFort'); // ou '' par défaut sur XAMPP
```

**Important :** Par défaut, une installation fraîche de XAMPP utilise un mot de passe vide pour l'utilisateur `root`. Si c'est votre cas, modifiez la ligne `DB_PASS` pour qu'elle soit vide : `define('DB_PASS', '');`. Si vous avez défini un mot de passe, mettez-le ici.

## 4. Création du Compte Administrateur

Pour des raisons de sécurité, le compte administrateur n'est pas créé par le script SQL. Vous devez le créer via un script en ligne de commande.

1.  Ouvrez un terminal à la racine du projet (`C:\xampp\htdocs\ecommerce-boutique`).
2.  Exécutez la commande suivante :

    ```bash
    php scripts/create-admin.php
    ```

3.  Le script vous demandera de saisir un nom d'utilisateur, un e-mail et un mot de passe. Choisissez des identifiants sécurisés.

Votre compte est maintenant créé et vous pouvez vous connecter à l'interface d'administration.

## 5. Simulation d'Envoi d'E-mails

En local, il est déconseillé d'envoyer de vrais e-mails. On utilise des outils qui "interceptent" les e-mails envoyés par PHP pour les afficher dans une interface web.

**MailHog** est une excellente option, très simple à mettre en place.

1.  **Téléchargez MailHog** pour votre système depuis la page des releases GitHub.
2.  Décompressez et lancez l'exécutable (`MailHog.exe` sur Windows). Une fenêtre de terminal va s'ouvrir.
3.  Ouvrez votre navigateur à l'adresse `http://localhost:8025`. C'est la boîte de réception de MailHog.
4.  Vérifiez que le fichier `config/mail-config.php` est bien configuré pour MailHog (c'est le cas par défaut pour l'environnement `local`) :

    ```php
    // config/mail-config.php
    define('SMTP_HOST', '127.0.0.1');
    define('SMTP_PORT', 1025); // Port d'écoute de MailHog
    ```

Maintenant, chaque fois que le site essaiera d'envoyer un e-mail (ex: confirmation de commande), il apparaîtra instantanément dans l'interface de MailHog au lieu d'être envoyé sur Internet.

## 6. Lancer le Site

1.  Assurez-vous que les serveurs Apache et MySQL sont bien démarrés.
2.  Ouvrez votre navigateur et accédez aux URLs suivantes :
    -   **Site public :** `http://localhost/ecommerce-boutique/`
    -   **Administration :** `http://localhost/ecommerce-boutique/admin/`

Vous pouvez maintenant naviguer sur le site, ajouter des produits au panier, passer une commande (qui enverra un e-mail dans MailHog) et gérer la boutique depuis le back-office.