<?php
// Ce fichier est nécessaire mais aucune modification ne doit y être faite.
// Depuis la version 3.2.4, le programme de configuration positionne les informations dans "BD- ... -connect.inc.php", la partie "..." étant le nom du serveur, et modifie celui-ci pour compatibilité avec les anciennes installations.
//
// Pour les utilisateurs GoogleMaps, ajouter la ligne suivante dans votre fichier "_config/BD-.........php"
// en indiquant votre clé à la place de 'MettreIciVotreCleApiGoogle' en conservant les quotes et Oter les // du début de ligne
// define('GOOGLE_API_KEY', 'MettreIciVotreCleApiGoogle');

// **********
// NE PAS MODIFIER / DON'T MODIFY
// **********

if ($_SERVER['SERVER_ADDR'] <> '127.0.0.1') {
    // Paramètres d'accès à la base de données CHEZ VOTRE HEBERGEUR
    $dbaddr = "@@serveur_BD@@"; // Adresse du serveur DISTANT
    $dbname = "@@nom_BD@@"; // Nom de la base
    $dbuser = "@@login_BD@@"; // Login MySQL
    $dbpass = "@@mot_de_passe_BD@@"; // Mot de passe
} else {
    // Paramètres d'accès à la base de données locale EasyPHP (facultatif)
    $dbaddr = "localhost"; // Adresse du serveur LOCAL
    $dbname = "expoactes"; // Nom de la base
    $dbuser = "expoactes"; // Login MySQL-EasyPHP
    $dbpass = "expoactes"; // Mot de passe
}
