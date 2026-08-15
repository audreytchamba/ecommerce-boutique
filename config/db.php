<?php
/**
 * config/db.php
 * Connexion PDO sécurisée (singleton) à MySQL/MariaDB.
 *
 * - Requêtes préparées obligatoires partout dans le projet (jamais de
 *   concaténation SQL directe).
 * - ATTR_ERRMODE en EXCEPTION : toute erreur SQL lève une PDOException,
 *   jamais un simple warning silencieux.
 * - Émulation des requêtes préparées désactivée : PDO envoie de vraies
 *   requêtes préparées au serveur MySQL (protection injection renforcée).
 */

declare(strict_types=1);

require_once __DIR__ . '/config.php';

// -----------------------------------------------------------------------
// Identifiants de connexion
// -> à modifier uniquement ici lors du passage en production sur LWS
//    (voir Étape 6 : Guide de déploiement)
// -----------------------------------------------------------------------
if (APP_ENV === 'local') {
    define('DB_HOST', '127.0.0.1');
    define('DB_NAME', 'ecommerce_boutique');
    define('DB_USER', 'root');
    define('DB_PASS', 'NouveauMotDePasseFort');
} else {
    // Valeurs fournies par le panel LWS lors de la création de la BDD
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'nomclient_ecommerce');
    define('DB_USER', 'nomclient_dbuser');
    define('DB_PASS', 'REMPLACER_PAR_MOT_DE_PASSE_LWS');
}

define('DB_CHARSET', 'utf8mb4');

/**
 * Retourne une instance PDO unique (pattern singleton).
 * Évite d'ouvrir une nouvelle connexion à chaque include du fichier.
 */
function getDbConnection(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = sprintf(
        'mysql:host=%s;dbname=%s;charset=%s',
        DB_HOST,
        DB_NAME,
        DB_CHARSET
    );

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    // Gère la dépréciation de PDO::MYSQL_ATTR_INIT_COMMAND dans les versions récentes de PHP
    if (defined('Pdo\Mysql::ATTR_INIT_COMMAND')) {
        $options[\Pdo\Mysql::ATTR_INIT_COMMAND] = "SET NAMES '" . DB_CHARSET . "'";
    } else {
        $options[PDO::MYSQL_ATTR_INIT_COMMAND] = "SET NAMES '" . DB_CHARSET . "'";
    }

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        // On ne renvoie JAMAIS le message d'exception brut au client
        // (il peut contenir host/user/mot de passe partiel).
        if (APP_ENV === 'local') {
            // En local uniquement, on affiche le détail pour déboguer
            die('Erreur de connexion à la base de données : ' . $e->getMessage());
        }
        error_log('DB Connection failed: ' . $e->getMessage());
        http_response_code(500);
        die('Une erreur technique est survenue. Merci de réessayer plus tard.');
    }

    return $pdo;
}
