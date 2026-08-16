# Guide de Déploiement sur HostCM (Hébergement Mutualisé)

Ce guide détaille les étapes pour déployer votre projet e-commerce sur un hébergement mutualisé HostCM.

*Note : Ce guide est basé sur un abonnement de 30 000 XAF.*

## Étape 1 : Préparation

Avant de commencer, assurez-vous d'avoir :
1.  Les fichiers de votre projet prêts sur votre ordinateur.
2.  Vos identifiants de connexion à votre panel d'administration HostCM.
3.  Vos identifiants FTP (serveur, utilisateur, mot de passe) fournis par HostCM.
4.  Un client FTP comme [FileZilla](https://filezilla-project.org/).

## Étape 2 : Envoi des Fichiers sur le Serveur

1.  **Connectez-vous** à votre serveur via votre client FTP.
2.  Sur le serveur distant, naviguez jusqu'au dossier racine de votre site web. Il est généralement nommé `www/`, `public_html/` ou `htdocs/`.
3.  **Envoyez tous les fichiers et dossiers** de votre projet local vers ce répertoire, **à l'exception des suivants** :
    -   Le dossier `vendor/` (nous le générerons sur le serveur).
    -   Le fichier `DOC_LOCAL.md`.
    -   Le dossier `.git/` et le fichier `.gitignore` si vous utilisez Git.

## Étape 3 : Installation des Dépendances avec Composer

La plupart des hébergeurs modernes, y compris potentiellement HostCM, permettent d'utiliser Composer via un accès SSH.

1.  **Connectez-vous en SSH** à votre hébergement. Les informations sont disponibles dans votre panel LWS.
    - Si l'accès SSH n'est pas disponible, installez les dépendances sur votre machine locale (`composer install --no-dev --optimize-autoloader`) puis envoyez le dossier `vendor/` complet par FTP.

2.  Si vous êtes connecté en SSH, naviguez jusqu'au dossier où vous avez uploadé vos fichiers :
    ```bash
    cd www/ # ou public_html/, ou le nom de votre dossier racine
    ```
3.  Exécutez Composer pour installer les dépendances en mode production. Cela va créer le dossier `vendor/` avec PHPMailer.
    ```bash
    composer install --no-dev --optimize-autoloader
    ```
    *   `--no-dev` : ignore les paquets de développement.
    *   `--optimize-autoloader` : améliore les performances.

## Étape 4 : Configuration de la Base de Données

1.  **Créez la base de données :**
    -   Dans votre panel HostCM, allez dans la section "Bases de données MySQL" (ou un nom similaire).
    -   Cliquez sur "Créer une nouvelle base de données".
    -   HostCM vous fournira un **nom de base**, un **nom d'utilisateur** et un **mot de passe**. Notez-les précieusement.

2.  **Importez la structure :**
    -   Depuis la même section, cliquez sur le lien pour accéder à **phpMyAdmin**.
    -   Sélectionnez votre nouvelle base de données dans la colonne de gauche.
    -   Allez dans l'onglet "Importer".
    -   Cliquez sur "Choisir un fichier" et sélectionnez `database/schema.sql` depuis votre projet local.
    -   Cliquez sur "Exécuter".

## Étape 5 : Mise à Jour des Fichiers de Configuration

Vous devez maintenant modifier les fichiers de configuration directement sur le serveur (via le gestionnaire de fichiers de LWS ou en les éditant et en les ré-uploadant par FTP).

### a. Connexion à la base de données (`config/db.php`)

Ouvrez `config/db.php` et modifiez la section `else` avec les informations de la base de données que HostCM vous a fournies.

```php
// c:\xampp\htdocs\ecommerce-boutique\config\db.php

// ...
} else {
    // Valeurs fournies par le panel HostCM lors de la création de la BDD
    define('DB_HOST', 'localhost'); // ou l'hôte fourni par HostCM
    define('DB_NAME', 'nomclient_ecommerce'); // REMPLACER
    define('DB_USER', 'nomclient_dbuser');    // REMPLACER
    define('DB_PASS', 'MOT_DE_PASSE_FOURNI_PAR_HOSTCM'); // REMPLACER
}
// ...
```

### b. Configuration de l'environnement (`config/config.php`)

Ouvrez `config/config.php` pour passer le site en mode production.

```php
// c:\xampp\htdocs\ecommerce-boutique\config\config.php

// 1. Changer l'environnement
define('APP_ENV', 'production'); // au lieu de 'local'

// ...

// 2. Mettre à jour l'URL du site
define('SITE_URL', 'https://www.votredomaine.com'); // REMPLACER par votre nom de domaine, sans slash final
```
Ce changement est **crucial** : il masque les erreurs PHP détaillées aux visiteurs et active la journalisation des erreurs.

### c. Configuration des e-mails (`config/mail-config.php`)

LWS fournit des serveurs SMTP pour envoyer des e-mails. Modifiez la section `else` du fichier `config/mail-config.php` avec les paramètres SMTP de votre compte mail LWS.

```php
// c:\xampp\htdocs\ecommerce-boutique\config\mail-config.php

// ...
} else {
    // --- Configuration pour la PRODUCTION (LWS) ---
    define('SMTP_HOST', 'mail.votredomaine.com'); // Souvent mail.votredomaine.com ou un serveur LWS
    define('SMTP_PORT', 587); // ou 465
    define('SMTP_USER', 'adresse@votredomaine.com'); // Votre adresse email complète
    define('SMTP_PASS', 'MOT_DE_PASSE_EMAIL'); // Le mot de passe de cette adresse email
    define('SMTP_FROM_EMAIL', 'adresse@votredomaine.com');
    define('SMTP_FROM_NAME', SITE_NAME);
}
```

## Étape 6 : Création du Compte Administrateur

Le script `create-admin.php` doit être exécuté en ligne de commande.

1.  Connectez-vous à nouveau en **SSH** à votre hébergement.
2.  Naviguez jusqu'à la racine de votre projet (`cd www/`).
3.  Exécutez la commande suivante. Le script vous guidera pour créer votre compte.
    ```bash
    php scripts/create-admin.php
    ```

## Étape 7 : Vérifications Finales

1.  **Version de PHP :** Dans votre panel LWS, vérifiez que la version de PHP utilisée pour votre site est bien **8.1 ou supérieure**.
2.  **Testez le site :** Ouvrez `https://www.votredomaine.com` dans votre navigateur. Le site doit s'afficher.
3.  **Testez l'administration :** Allez sur `https://www.votredomaine.com/admin/` et connectez-vous avec les identifiants que vous venez de créer.
4.  **Testez l'envoi d'e-mail :** Passez une commande test. Vous devriez recevoir l'e-mail de confirmation. Si ce n'est pas le cas, vérifiez vos logs et la configuration SMTP.
5.  **Testez l'upload :** Essayez d'ajouter un produit avec une image pour vérifier que les permissions du dossier `uploads/` sont correctes. Si l'upload échoue, vous devrez peut-être changer les permissions du dossier `uploads/products/` en `755` ou `775` via votre client FTP (clic droit sur le dossier > Permissions de fichier).

Votre boutique est maintenant en ligne !