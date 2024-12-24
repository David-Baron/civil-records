<?php

use Symfony\Component\HttpFoundation\RedirectResponse;

define('ADM', 10); // Compatibility only
$admtxt = 'Gestion '; // Compatibility only
require(__DIR__ . '/../next/bootstrap.php');
require(__DIR__ . '/../next/_COMMUN_env.inc.php'); // Compatibility only

pathroot($root, $path, $xcomm, $xpatr, $page);

if (!$userAuthorizer->isGranted(9)) {
    $response = new RedirectResponse("$root/admin/");
    $response->send();
    exit();
}

$xaction = getparam('action');
$missingargs = true;

ob_start();
open_page("Approbation d'un compte utilisateur", $root); ?>
<div class="main">
    <?php zone_menu(10, $session->get('user')['level'], array('f' => 'N')); ?>
    <div class="main-col-center text-center">
        <?php 
navadmin($root, "Approbation d'un compte utilisateur");

if ($config->get('USER_AUTO_DEF') <> 1) {
    echo "<p><b>Désolé : Cette action n'a pas de sens dans la configuration actuelle du logiciel</b></p>";
    echo '</div>';
    include(__DIR__ . '/../templates/front/_footer.php');
    $response->setContent(ob_get_clean());
    $response->send();
    exit();
}

if (!isset($_REQUEST['id'])) {
    echo "<p><b>Cette procédure ne peut être appelée directement.</b></p>";
    echo '</div>';
    include(__DIR__ . '/../templates/front/_footer.php');
    $response->setContent(ob_get_clean());
    $response->send();
    die();
}

// Données postées -> ajouter ou modifier
$ok = true;
if (!isset($_REQUEST['complet'])) {
    $ok = false;
}
if (!isset($_REQUEST['action'])) {
    msg('Vous devez sélectionner l\'action à poser');
    $ok = false;
}
$res = EA_sql_query("SELECT * FROM " . $config->get('EA_UDB') . "_user3 WHERE id='" . sql_quote(getparam('id'))
    . "' and  (statut='W' or statut='A')", $u_db);
if (EA_sql_num_rows($res) != 1) {
    echo "<p><b>Pas de compte à approuver avec cette identification.</b></p>";
    echo '</div>';
    include(__DIR__ . '/../templates/front/_footer.php');
    $response->setContent(ob_get_clean());
    $response->send();
    exit();
} else {
    $row = EA_sql_fetch_array($res);
    $nomprenom = $row['prenom'] . ' ' . $row['nom'];
    $login = $row['login'];
}
if ($ok) {
    $id = getparam('id');
    $missingargs = false;
    $mes = "";
    if ($xaction == "OK") {
        $statut = 'N';  // normal
        $sujet = "Approbation de votre compte";
        $mes = "approuvé";
    } else {
        $statut = 'B';  // bloqué
        $sujet = "Refus de votre compte";
        $mes = "refusé";
    }
    $reqmaj = "UPDATE " . $config->get('EA_UDB') . "_user3 SET "
        . " statut = '" . $statut . "',"
        . " rem = ' '"
        . " WHERE id=" . $id . ";";

    //echo "<p>".$reqmaj."</p>";
    if ($result = EA_sql_query($reqmaj, $u_db)) {
        $crlf = chr(10) . chr(13);
        $log = "Cpte " . $mes;
        $message = getparam('messageplus');

        $sql = "SELECT NOM, PRENOM, LOGIN"
            . " FROM " . $config->get('EA_UDB') . "_user3 WHERE id=" . $id . ";";
        $res = EA_sql_query($sql, $u_db);
        $ligne = EA_sql_fetch_array($res);

        $urlsite = $config->get('EA_URL_SITE') . $root . "/index.php";
        $codes = array("#NOMSITE#", "#URLSITE#", "#LOGIN#", "#NOM#", "#PRENOM#");
        $decodes = array($config->get('SITENAME'), $urlsite, $ligne['LOGIN'], $ligne['NOM'], $ligne['PRENOM']);
        $bon_message = str_replace($codes, $decodes, $message);

        $sender = mail_encode($config->get('SITENAME')) . ' <' . $config->get('LOC_MAIL') . ">";
        $okmail = sendmail($sender, $row['email'], $sujet, $bon_message);
        if ($okmail) {
            $log .= " + mail";
        } else {
            $log .= " NO mail";
        }
        writelog($log, $login, 0);
        echo '<p><b>La demande de compte a été ' . $mes . 'e.</b></p>';
    } else {
        echo ' -> Erreur : ';
        echo '<p>' . EA_sql_error() . '<br />' . $reqmaj . '</p>';
    }
}


//Si pas tout les arguments nécessaire, on affiche le formulaire
if (!$ok) {
    $messageplus = $config->get('MAIL_APPROBATION');
    if ($xaction == 'KO') {
        $messageplus = $config->get('MAIL_REFUS');
    }

    echo '<h2>Approbation d\'un compte d\'utilisateur</h2>' . "\n";
    echo '<form method="post"  action="">' . "\n";
    echo '<table cellspacing="0" cellpadding="1" border="0" summary="Formulaire">' . "\n";

    echo " <tr>\n";
    echo '  <td align="right">' . "Candidat : </td>\n";
    echo '  <td><b>' . $nomprenom . "</b></td>\n";
    echo " </tr>\n";

    echo " <tr>\n";
    echo '  <td align="right">' . "Login : </td>\n";
    echo '  <td>' . $login . "</td>\n";
    echo " </tr>\n";

    echo " <tr>\n";
    echo '  <td align="right">' . "Action : </td>\n";
    echo '  <td>';
    echo '        <input type="radio" name="action" value="OK" ' . ("OK" === $_REQUEST['action'] ? ' checked' : '') . '> = Approuver';
    echo '        <input type="radio" name="action" value="KO" ' . ("KO" === $_REQUEST['action'] ? ' checked' : '') . '> = REFUSER';
    echo '        cet utilisateur';
    echo '  </td>';
    echo " </tr>\n";

    echo ' <tr>' . "\n";
    echo "  <td align=right>Message : </td>\n";
    echo '  <td>';
    echo '<textarea name="messageplus" cols=60 rows=10>' . $messageplus . '</textarea>';
    echo '  </td>';
    echo " </tr>\n";

    echo " <tr>\n";
    echo '  <td colspan="2">&nbsp;</td>' . "\n";
    echo " </tr>\n";

    echo " <tr><td align=\"right\">\n";
    echo '  <input type="reset" value=" Effacer " />' . "\n";
    echo '  <input type="hidden" name="complet" value="OK" />';
    echo " </td><td align=\"left\">\n";
    echo ' &nbsp; <input type="submit" value=" *** ENVOYER *** " />' . "\n";
    echo " </td></tr>\n";
    echo "</table>\n";
    echo "</form>\n";
} else {
    echo '<p align="center">Aller à : <a href="index.php">la page d\'accueil</a>';
    echo '&nbsp;|&nbsp; <a href="listusers.php">la liste des utilisateurs</a>';
    echo '&nbsp;|&nbsp; <a href="gestuser.php?id=' . $id . '">la fiche de ' . $nomprenom . '</a></p>';
}
echo '</div>';
echo '</div>';
include(__DIR__ . '/../templates/front/_footer.php');
$response->setContent(ob_get_clean());
$response->send();
