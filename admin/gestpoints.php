<?php

use Symfony\Component\HttpFoundation\RedirectResponse;

require(__DIR__ . '/../next/bootstrap.php');
require(__DIR__ . '/../tools/traitements.inc.php');

if (!$userAuthorizer->isGranted(9)) {
    $response = new RedirectResponse("$root/admin/");
    $response->send();
    exit();
}

pathroot($root, $path, $xcomm, $xpatr, $page);

$T0 = time();
$oper     = getparam('oper');
$nbrepts  = getparam('nbrepts');
$xdroits  = getparam('lelevel');
$regime   = getparam('regime');
$rem      = getparam('rem');
$condit   = getparam('condit');
$statut   = getparam('statut');
$dtexpir   = getparam('dtexpir');
$conditexp = getparam('conditexp');
$ptitle = "Modifications groupées";
$missingargs = true;
$emailfound = false;
$cptok = 0;
$cptko = 0;
$ok = true;
$today = today();
$condrem = "";
$menu_user_active = 'S';

ob_start();
open_page($ptitle, $root); ?>
<div class="main">
    <?php zone_menu(10, $session->get('user')['level'], array()); ?>
    <div class="main-col-center text-center">
        <?php 
navadmin($root, $ptitle);

require(__DIR__ . '/../templates/admin/_menu-user.php');

if (getparam('action') == 'submitted') {
    if ($oper == "") {
        msg("Vous devez préciser une action à réaliser");
        $ok = false;
    }
    if ($oper == "E") {
        $sqlnewdt = "";
        $baddt = 0;
        ajuste_date($nbrepts, $sqlnewdt, $baddt);
        if ($sqlnewdt == '0000-00-00' or $baddt) {
            msg("La nouvelle date d'expiration n'est pas valide");
            $ok = false;
        }
    }

    if ($condit <> "0") {
        $condrem = " and " . comparerSQL('REM', $rem, $condit);
    }
    $condreg = "";
    if ($regime >= 0) {
        $condreg = " and regime =" . $regime;
    }
    if ($statut > 0) {
        $condreg = " and statut =" . $statut;
    }
    $sqlexpir = "";
    $baddt = 0;
    ajuste_date($dtexpir, $sqlexpir, $baddt);
    if ($sqlexpir > '0000-00-00' and $conditexp <> "0") {
        $condreg = " and " . comparerSQL('dtexpiration', $sqlexpir, $conditexp);
    }

    if ($ok) {
        if ($oper == "E") { // ==== ajustement de la date d'expiration
            $sql = "UPDATE " . $config->get('EA_UDB') . "_user3 SET"
                    . " dtexpiration='" . $sqlnewdt . "'"
                    . " WHERE level=" . $xdroits . $condreg . $condrem . " ;";
            $result = EA_sql_query($sql, $u_db);

            $nb = EA_sql_affected_rows($u_db);
            echo "<p>Modification de la date d'expiration de " . $nb . " comptes utilisateurs.</p>";
            writelog('Modif. dates expiration', "USERS", $nb);
            $missingargs = false;
        } elseif ($oper == "R") { //==== remise à 0 des points consommés
            $sql = "UPDATE " . $config->get('EA_UDB') . "_user3 SET"
                . " pt_conso=0"
                . " WHERE level=" . $xdroits . $condreg . $condrem . " ;";
            $result = EA_sql_query($sql, $u_db);

            $nb = EA_sql_affected_rows($u_db);
            echo "<p>Remise à zéro des points consommés de " . $nb . " comptes utilisateurs.</p>";
            writelog('RAZ des points consommés', "USERS", $nb);
            $missingargs = false;
        } elseif ($oper == "A" or $oper == "F") {
            // modification des points disponibles
            $sql = "SELECT id, nom, prenom, solde, pt_conso"
                . " FROM " . $config->get('EA_UDB') . "_user3 "
                . " WHERE level=" . $xdroits . $condreg . $condrem . " ;";

            $sites = EA_sql_query($sql, $u_db);
            $nbsites = EA_sql_num_rows($sites);
            $nbsend = 0;
            $missingargs = false;

            while ($site = EA_sql_fetch_array($sites)) {
                $idsit = $site['id'];
                $oldsolde = $site['solde'];
                $nom = $site['nom'];
                $prenom = $site['prenom'];
                if ($oper == "A") {
                    $newsolde = $oldsolde + $nbrepts;
                } else {
                    $newsolde = $nbrepts;
                }
                $sql = "UPDATE " . $config->get('EA_UDB') . "_user3 SET"
                    . " solde=" . $newsolde . ", maj_solde='" . $today . "'"
                    . " WHERE id=" . $idsit . " ;";
                $result = EA_sql_query($sql, $u_db);

                $nb = EA_sql_affected_rows($u_db);

                if ($nb == 1) {
                    echo "<p>Modifié le solde de " . $prenom . " " . $nom . " (" . $oldsolde . " -> " . $newsolde . ") </p>";
                    $cptok++;
                }
            }
        } // fichier d'actes
        if ($cptok > 0) {
            echo '<p>Soldes modifiés  : ' . $cptok;
            writelog('Soldes modifiés ', "USERS", $cptok);
        }
        if ($cptko > 0) {
            echo '<br>Soldes impossible à modifier : ' . $cptko;
        }
    }
}
//Si pas tout les arguments nécessaire, on affiche le formulaire
if ($missingargs) {
    echo '<form method="post" enctype="multipart/form-data">';
    echo '<h2>' . $ptitle . '</h2>';
    echo '<table class="m-auto">';

    echo "<tr><td colspan=\"2\"><b>Utilisateurs concernés</b></td></tr>";
    echo "<tr>";
    echo "<td>Droits d'accès : </td>";
    echo '<td>';
    lb_droits_user($xdroits);
    echo '</td>';
    echo "</tr>";
    if ($config->get('GEST_POINTS') > 0) {
        echo "<tr><td>ET</td><td>&nbsp;</td></tr>";
        echo "<tr>";
        echo "<td>Régime (points) : </td>";
        echo '<td>';
        lb_regime_user($regime, 1);
        echo '</td>';
        echo "</tr>";
    } else {
        echo '<tr><td colspan="2">';
        echo '<input type="hidden" name="regime" value="-1">';
        echo "</td></tr>";
    }

    echo "<tr><td>ET</td><td>&nbsp;</td></tr>";
    echo "<tr>";
    echo "<td>Statut : </td>";
    echo '<td>';
    lb_statut_user($statut, 1);
    echo '</td>';
    echo "</tr>";

    echo "<tr><td>ET</td><td>&nbsp;</td></tr>";
    echo "<tr>";
    echo "<td>Date expiration : </td>";
    echo '<td>';
    listbox_trait('conditexp', "NTS", $conditexp);
    echo '<input type="text" name="dtexpir" size="10" value="' . $dtexpir . '"></td>';
    echo " </tr>";

    echo "<tr><td>ET</td><td>&nbsp;</td></tr>";
    echo "<tr>";
    echo "<td>Commentaire : </td>";
    echo '<td>';
    listbox_trait('condit', "TST", $condit);

    echo '<input type="text" name="rem" size="50" value="' . $rem . '">';
    echo "</td>";
    echo "</tr>";

    echo "<tr><td colspan=\"2\"><b>Action à effectuer</b></td></tr>";
    echo "<tr>";
    echo '<td align="right">Opération : </td>';
    echo '<td>';
    echo '<input type="radio" name="oper" value="E">Fixer la date d\'expiration des comptes à <br>';
    if ($config->get('GEST_POINTS') > 0) {
        echo '<input type="radio" name="oper" value="R">Remettre à 0 les points <i>consommés</i> <br>';
        echo '<input type="radio" name="oper" value="A">Ajouter les points suivants au solde <i>disponible</i><br>';
        echo '<input type="radio" name="oper" value="F">Fixer le solde de points <i>disponibles</i> à <br>';
    }
    echo '</td>';
    echo "</tr>";

    echo "<tr>";
    echo "<td>Valeur : </td>";
    echo '<td><input type="text" name="nbrepts" size="12" value="' . $nbrepts . '"></td>';
    echo "</tr>";

    echo "</tr>";
    echo "<tr><td colspan=\"2\">&nbsp;</td></tr>";
    echo "<tr><td></td>";
    echo '<input type="hidden" name="action" value="submitted">';
    echo '<td><button type="reset" class="btn">Effacer</button>';
    echo '<button type="submit" class="btn">Effectuer</button>';
    echo "</td></tr>";
    echo "</table>";
    echo "</form>";
} else {
    echo '<hr>';
    echo '<br>Durée du traitement  : ' . (time() - $T0) . ' sec.';
    echo '</p>';
    echo '<p>Retour à la ';
    echo '<a href="' . $root . '/admin/listusers.php"><b>liste des utilisateurs</b></a>';
    echo '</p>';
}
echo '</div>';
echo '</div>';
include(__DIR__ . '/../templates/front/_footer.php');
$response->setContent(ob_get_clean());
$response->send();
