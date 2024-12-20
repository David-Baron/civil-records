<?php

use Symfony\Component\HttpFoundation\RedirectResponse;

define('ADM', 10); // Compatibility only
$admtxt = 'Gestion '; // Compatibility only
require(__DIR__ . '/../next/bootstrap.php');
require(__DIR__ . '/../next/_COMMUN_env.inc.php'); // Compatibility only
require(__DIR__ . '/../tools/traitements.inc.php');

if (!$userAuthorizer->isGranted(9)) {
    $response = new RedirectResponse("$root/admin/");
    $response->send();
    exit();
}

pathroot($root, $path, $xcomm, $xpatr, $page);

$T0 = time();
$sujet    = getparam('sujet');
$message  = getparam('message');
$xdroits  = getparam('lelevel');
$regime   = getparam('regime');
$rem      = getparam('rem');
$condit   = getparam('condit');
$xaction  = getparam('action');
$missingargs = true;
$emailfound = false;
$cptok = 0;
$cptko = 0;
$today = today();
$condrem = "";
$condlevel = "";

$menu_user_active = 'M';

ob_start();
open_page("Envoi d'un mail circulaire", $root); ?>
<div class="main">
    <?php zone_menu(ADM, $session->get('user')['level'], array()); ?>
    <div class="main-col-center text-center">
        <?php 
navadmin($root, "Envoi d'un mail circulaire");

require(__DIR__ . '/../templates/admin/_menu-user.php');

if ($xaction == 'submitted') {
    if ($xdroits <> "10") {
        $condlevel = " AND level=" . $xdroits;
    }
    if ($condit <> "0") {
        $condrem = " AND " . comparerSQL('REM', $rem, $condit);
    }
    $condreg = "";
    if ($regime >= 0) {
        $condreg = " AND regime =" . $regime;
    }
    $request = "SELECT nom, prenom, email, level, statut"
        . " FROM " . $config->get('EA_UDB') . "_user3 "
        . " WHERE (1=1) " . $condlevel . $condreg . $condrem . " ;";
    //echo $request1;
    $sites = EA_sql_query($request, $u_db);
    $nbsites = EA_sql_num_rows($sites);
    $nbsend = 0;
    $missingargs = false;

    while ($site = EA_sql_fetch_array($sites)) {
        if ($site['statut'] == 'N') {
            $mail = $site['email'];
            $nom = $site['nom'];
            $prenom = $site['prenom'];

            $urlsite = $config->get('EA_URL_SITE') . $root . "/index.php";
            $codes = array("#URLSITE#", "#NOM#", "#PRENOM#");
            $decodes = array($urlsite, $nom, $prenom);
            $bon_message = str_replace($codes, $decodes, $message);
            $sender = mail_encode($config->get('SITENAME')) . ' <' . $config->get('LOC_MAIL') . ">";
            $okmail = sendmail($sender, $mail, $sujet, $bon_message);
            //$okmail=false;
            echo "<p>Envoi à " . $prenom . " " . $nom . " (" . $mail . ") ";
            if (!$okmail) {
                echo ' -> Mail PAS envoyé.';
                $cptko++;
            } else {
                echo ' -> Mail ENVOYE.';
                $cptok++;
            }
        }
    }
} // fichier d'actes

//Si pas tout les arguments nécessaire, on affiche le formulaire
if ($missingargs) {
    if ($xaction == '') {  // parametres par défaut
        if (isset($_COOKIE['chargeUSERlogs'])) {
            $logOk = $_COOKIE['chargeUSERlogs'][0];
        } else {
            $logOk = "1";
        }
    }

    echo '<form method="post" enctype="multipart/form-data" action="">' . "\n";
    echo '<h2 align="center">Envoi d\'un mail circulaire</h2>';
    echo '<table class="m-auto">' . "\n";

    echo " <tr><td colspan=\"2\"><b>Destinataires</b></td></tr>\n";
    echo " <tr>\n";
    echo "  <td align=right>Droits d'accès : </td>\n";
    echo '  <td>';
    lb_droits_user($xdroits, 2);
    echo '  </td>';
    echo " </tr>\n";
    if ($config->get('GEST_POINTS') > 0) {
        echo " <tr><td align=right>ET</td><td>&nbsp;</td></tr>\n";
        echo " <tr>\n";
        echo "  <td align=right>Régime (points) : </td>\n";
        echo '  <td>';
        lb_regime_user($regime, 1);
        echo '  </td>';
        echo " </tr>\n";
    } else {
        echo ' <tr><td colspan="2">';
        echo '<input type="hidden" name="regime" value="-1" />';
        echo "</td></tr>\n";
    }

    echo " <tr><td align=right>ET</td><td>&nbsp;</td></tr>\n";
    echo " <tr>\n";
    echo "  <td align=right>Commentaire : </td>\n";
    echo '  <td>';
    listbox_trait('condit', "TST", $condit);

    echo ' <input type="text" name="rem" size="50" value="' . $rem . '" />';
    echo "</td>\n";
    echo " </tr>\n";

    echo " <tr><td colspan=\"2\"><b>Message</b></td></tr>\n";
    echo " <tr>\n";
    echo "  <td align=right>Sujet : </td>\n";
    echo '  <td><input type="text" name="sujet" size="60" value="' . $sujet . '">' . "</td>\n";
    echo " </tr>\n";

    echo ' <tr>' . "\n";
    echo "  <td align=right>Texte du mail : </td>\n";
    echo '  <td>';
    echo '<textarea name="message" cols=60 rows=10>' . $message . '</textarea>';
    echo '  </td>';
    echo " </tr>\n";

    echo " </tr>\n";
    echo " <tr><td colspan=\"2\">&nbsp;</td></tr>\n";
    echo " <tr><td colspan=\"2\" align=\"center\">\n<br>";
    echo '  <input type="hidden" name="action" value="submitted">';
    echo '  <input type="reset" value="Effacer">' . "\n";
    echo '  <input type="submit" value=" >> ENVOYER >> ">' . "\n";
    echo " </td></tr>\n";
    echo "</table>\n";
    echo "</form>\n";
} else {
    echo '<hr>';
    if ($cptok > 0) {
        echo '<p>Mails envoyés  : ' . $cptok;
        writelog('Mails envoyés ', "USERS", $cptok);
    }
    if ($cptko > 0) {
        echo '<br>Envois impossibles : ' . $cptko;
    }
    echo '<br>Durée du traitement  : ' . (time() - $T0) . ' sec.';
    echo '</p>';
    echo '<p>Retour à la ';
    echo '<a href="' . mkurl("listusers.php", "") . '"><b>' . "liste des utilisateurs" . '</b></a>';
    echo '</p>';
}
echo '</div>';
echo '</div>';
include(__DIR__ . '/../templates/front/_footer.php');
$response->setContent(ob_get_clean());
$response->send();
