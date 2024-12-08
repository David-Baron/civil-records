<?php

define('ADM', 10);

// Mise en place des répertoires avec un nom suffixé par "_init" lors d'une installation initiale (ne doivent pas être touchés en cas de maj)
$Pour_Install = array('.htaccess_init' => '.htaccess', '_config_init' => '_config', '_backup_init' => '_backup', '_logs_init' => '_logs', 'admin/_upload_init' => 'admin/_upload');
foreach ($Pour_Install as $k => $v) {
    if ((file_exists(__DIR__ . '/../' . $k)) and ! file_exists(__DIR__ . '/../' . $v)) {
        rename(__DIR__ . '/../' . $k, __DIR__ . '/../' . $v);
    }
}

require(__DIR__ . '/../tools/_COMMUN_env.inc.php');
require(__DIR__ . '/instutils.php');

if (!defined("UPLOAD_DIR")) define("UPLOAD_DIR", "_upload");
if (!defined("INCLUDE_HEADER")) define("INCLUDE_HEADER", "");
if (!defined("SITENAME")) define("SITENAME", "Civil-Records");
if (!defined("SITE_URL")) define("SITE_URL", "");
if (!defined("PIED_PAGE")) define("PIED_PAGE", "");
if (!defined("EA_VERSION")) define("EA_VERSION", EA_VERSION_PRG);
if (!defined("EA_MAINTENANCE")) define("EA_MAINTENANCE", 0);
if (!defined("EXTERN_MAIL")) define("EXTERN_MAIL", 0);

$xcomm = $xpatr = $page = "";
$missingargs = true;
$minPHPversion = "8.1";
$minMySQLversion = "8.3";

pathroot($root, $path, $xcomm, $xpatr, $page);

open_page("Civil-Records | Installer", $root);

echo "<h1>Civil Records - Nouvelle installation</h1> \n";
echo "<h3>Vérification de l'environnement</h3> \n";

$db = con_db(1);

if (version_compare(phpversion(), $minPHPversion, '<')) {
    msg('021 : Ce programme nécessite au moins la version ' . $minPHPversion . ' de PHP');
    die('Version PHP détectée : ' . phpversion());
}

if (version_compare(EA_sql_get_server_info(), $minMySQLversion, '<')) {
    msg('022 : Ce programme nécessite au moins la version ' . $minSQLversion . ' de MySQL');
    die('Version du serveur MySQL : ' . EA_sql_get_server_info());
}

if (UPLOAD_DIR == "_upload")
    $uploaddir = '../admin/' . UPLOAD_DIR;
else
    $uploaddir = UPLOAD_DIR;
if (!is_dir($uploaddir)) {
    msg('031 : Répertoire de téléchargement "' . $uploaddir . '" inaccessible ou inexistant.');
    die();
}
$uploaddir .= "/";
if (is__writable($uploaddir))
    echo " : <b>OK</b></p>";
else {
    msg('032 : Impossible de créer un fichier dans "' . $uploaddir . '".');
    die();
}

$sql = "SELECT * FROM " . EA_DB . "_user3;";
$result = EA_sql_query($sql);

if ($result) {
    echo "<p><b>Installation déjà réalisée</b> - ";
    echo '<a href="' . $root . '/admin/index.php">Administration de la base ExpoActes</a></p>';
    exit;
}

$xaction  = getparam('action');
$xlogin   = getparam('login');
$xnom     = getparam('nom');
$xprenom  = getparam('prenom');
$xemail   = getparam('email');
$xpw1     = getparam('pw1');
$xpw2     = getparam('pw2');

if ($xaction == 'submitted') {
    if (empty($xlogin) or empty($xpw1) or empty($xemail) or empty($xpw2) or empty($xnom)) {
        msg("Vous devez préciser votre nom, votre adresse email ainsi que le code d'accès et le mot de passe (2x) de votre choix");
    } elseif ((strlen($xlogin) > 15) or (strlen($xpw1) > 15)) {
        msg('Le login et le mot de passe sont limités à 15 catactères');
    } elseif (!(sans_quote($xlogin) and sans_quote($xpw1))) {
        msg('Vous ne pouvez pas mettre d\'apostrophe dans le LOGIN ou le MOT DE PASSE');
    } elseif ($xpw1 <> $xpw2) {
        msg('Les 2 mots de passe doivent être identiques');
    } else {
        $missingargs = false;

        echo "<h3>Création des tables de données</h3> \n";

        $ok = execute_script_sql('creetables.sql');  // création des tables
        if ($ok) {
            echo '<p>Toutes tables créées.</p>';
        } else {
            msg("042 : Problème prendant l'exécution du script de génération des tables.");
            die();
        }

        $prenoms = file('liste_prenoms.csv');
        $cpt = 0;
        reset($prenoms);
        echo '<p>';
        foreach ($prenoms as $line) {
            $line = rtrim($line);  # Get rid of newline characters
            $line = ltrim($line);  # Get rid of any leading spaces
            if ($line == "" || $line == "\n" || strstr($line, "#") == 1) {
                next($prenoms);
            } else {
                $reqmaj = "INSERT INTO " . EA_DB . "_prenom VALUES ('" . sql_quote($line) . "')";
                if ($result = EA_sql_query($reqmaj . ';')) {
                    $cpt++;
                    echo ".";
                }
            }
        }

        echo '</p>';
        echo '<p>' . $cpt . ' prénoms féminins enregistrés.</p>';

        define('LOC_MAIL', $xemail); // pour initialiser le paramètre automatiquement

        if (! is_utf8($line)) $line = iconv('iso-8859-15', 'UTF-8', $line); // Conversion en UTF8
        // TODO: BG pas convaincu du tout par la ligne ci-dessus; le fichier depuis 3.2.3 est en UTF-8 et n'a pas vocation à être complété ou remplacé par un fichier utilisateur en ISO.
        $reqmaj = "INSERT INTO " . EA_DB . "_user3 (login,hashpass,nom,prenom,email,level)"
            . " VALUES ('" . sql_quote($xlogin) . "','" . sql_quote(sha1($xpw1)) . "','" . sql_quote($xnom) . "','" . sql_quote($xprenom) . "','" . $xemail . "',9);";

        if ($result = EA_sql_query($reqmaj . ';')) {
            echo "<p>Codes d'accès enregistrés.</p>";
        } else {
            echo ' -> Erreur : ';
            echo '<p>' . EA_sql_error() . '<br>' . $reqmaj . '</p>';
            $ok = false;
        }

        echo "<h3>Initialisation de la base des paramètres</h3>";
        $par_add = 0;
        $par_mod = 0;
        update_params("act_params.xml", 0);  // Création des paramètres manquants et maj des définition des autres

        // Mise à jour de n° de version	
        $sql = "UPDATE " . EA_DB . "_params SET valeur = '" . EA_VERSION_PRG . "' WHERE param = 'EA_VERSION'";
        EA_sql_query($sql);

        if ($par_add > 0) {
            echo "<p>" . $par_add . " paramètres ajoutés.</p>";
        }

        $okmail = sendmail($xemail, $xemail, 'Test messagerie', 'Ce message de test a été envoyé via ExpoActes');
        if ($okmail)
            echo "<p>Un mail de test vous a été envoyé. Vérifiez qu'il vous est bien parvenu.</p>";
        else {
            echo "<p>La fonction mail n'a PAS pu être vérifée.<br>";
            echo "<b>Le logiciel peut très bien fonctionner sans mail</b> mais il est impossible d'envoyer automatiquement les mots de passe aux utilisateurs.";
        }

        $okgd = function_exists('imagettftext');  // fonction de la librairie GD
        if ($okgd)
            echo "<p>La librairie graphique GD a été vérifiée.</p>";
        else {
            echo "<p>La librairie graphique GD n'a PAS pu être vérifée.<br>";
            echo "<b>Sans cette librairie, il ne sera pas possible de protéger les formulaires d'auto-inscription par un code image.</b></p>";
            $sql = "UPDATE " . EA_DB . "_params SET valeur = '0' WHERE param = 'AUTO_CAPTCHA'";
            EA_sql_query($sql);
            echo '<p>La génération de "captcha" a donc été (provisoirement) désactivée.</p>';
        }

        test_geocodage(true);

        if ($ok) {
            writelog('Création de la base de données ' . EA_VERSION_PRG);
            echo '<h2>Installation terminée</h2>';
            echo '<p>Vous pouvez à présent administrer la base.</p>';
            echo '<p><a href="' . $root . '/admin/index.php">Gestion des actes</a></p>';
        }
    }
}

if ($missingargs) { ?>
    <h3>Administrateur</h3>
    <form method="POST">
        <table class="table">
            <tr>
                <td>Nom : </td>
                <td><input type="text" name="nom" size=30 value="<?= $xnom; ?>"></td>
            </tr>
            <tr>
                <td>Prénom : </td>
                <td><input type="text" name="prenom" size=30 value="<?= $xprenom; ?>"></td>
            </tr>
            <tr>
                <td>Adresse email : </td>
                <td><input type="text" name="email" size=30 value="<?= $xemail; ?>"></td>
            </tr>
            <tr>
                <td>Identifiant : </td>
                <td><input type="text" name="login" size=20 value="<?= $xlogin; ?>"></td>
            </tr>
            <tr>
                <td>Mot de passe : </td>
                <td><input type="password" name="pw1" size=20 value=""></td>
            </tr>
            <tr>
                <td>Confirmation du mot de passe : </td>
                <td><input type="password" name="pw2" size=20 value=""></td>
            </tr>
            <tr>
                <td></td>
                <td>
                    <input type="hidden" name="action" value="submitted">
                    <input type="reset" value="Effacer">
                    <input type="submit" value="Envoyer">
                </td>
            </tr>
        </table>
    </form>
<?php }

load_params();  // pour rafraichir le pied de page 
close_page(0);
