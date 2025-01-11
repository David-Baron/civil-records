<?php

use Symfony\Component\HttpFoundation\RedirectResponse;

require(__DIR__ . '/../tools/traitements.inc.php');

if (!$userAuthorizer->isGranted(9)) {
    $response = new RedirectResponse("$root/admin/");
    $response->send();
    exit();
}


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
$today = date("Y-m-d", time());
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
        } ?>

        <form method="post" enctype="multipart/form-data">
            <h2><?= $ptitle; ?></h2>
            <table class="m-auto">
                <tr>
                    <td colspan="2"><b>Utilisateurs concernés</b></td>
                </tr>
                <tr>
                    <td>Droits d'accès : </td>
                    <td>
                        <select name="lelevel" size="1">
                            <option <?= (0 == $xdroits ? 'selected' : ''); ?>>Public</option>
                            <option <?= (1 == $xdroits ? 'selected' : ''); ?>>1 : Liste des communes</option>
                            <option <?= (2 == $xdroits ? 'selected' : ''); ?>>2 : Liste des patronymes</option>
                            <option <?= (3 == $xdroits ? 'selected' : ''); ?>>3 : Table des actes</option>
                            <option <?= (4 == $xdroits ? 'selected' : ''); ?>>4 : Détails des actes (avec limites)</option>
                            <option <?= (5 == $xdroits ? 'selected' : ''); ?>>5 : Détails sans limitation</option>
                            <option <?= (6 == $xdroits ? 'selected' : ''); ?>>6 : Chargement NIMEGUE et CSV</option>
                            <option <?= (7 == $xdroits ? 'selected' : ''); ?>>7 : Ajout d' actes</option>
                            <option <?= (8 == $xdroits ? ' selected' : ''); ?>>8 : Administration tous actes</option>
                            <option <?= (9 == $xdroits ? 'selected' : ''); ?>>9 : Gestion des utilisateurs</option>
                        </select>
                    </td>
                </tr>
                <?php if ($config->get('GEST_POINTS') > 0) { ?>
                    <tr>
                        <td>ET</td>
                        <td>&nbsp;</td>
                    </tr>
                    <tr>
                        <td>Régime (points) : </td>
                        <td>
                            <select name="regime" size="1">
                                <option <?= (0 == $regime ? 'selected' : ''); ?>>Accès libre</option>
                                <option <?= (1 == $regime ? 'selected' : ''); ?>>Recharge manuelle</option>
                                <option <?= (2 == $regime ? 'selected' : ''); ?>>Recharge automatique</option>
                            </select>
                        </td>
                    </tr>
                <?php } else { ?>
                    <tr>
                        <td colspan="2"></td>
                    </tr>
                    <input type="hidden" name="regime" value="-1">
                <?php } ?>
                <tr>
                    <td>ET</td>
                    <td>&nbsp;</td>
                </tr>
                <tr>
                    <td>Statut : </td>
                    <td>
                        <select name="statut" size="1">
                            <option <?= ('W' == $statut ? 'selected' : ''); ?>>Attente d'activation</option>
                            <option <?= ('A' == $statut ? 'selected' : ''); ?>>Attente d'approbation</option>
                            <option <?= ('N' == $statut ? 'selected' : ''); ?>>Accès autorisé</option>
                            <option <?= ('B' == $statut ? 'selected' : ''); ?>>Accès bloqué</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td>ET</td>
                    <td>&nbsp;</td>
                </tr>
                <tr>
                    <td>Date expiration : </td>
                    <td>
                        <?php listbox_trait('conditexp', "NTS", $conditexp); ?>
                        <input type="text" name="dtexpir" size="10" value="<?= $dtexpir; ?>">
                    </td>
                </tr>
                <tr>
                    <td>ET</td>
                    <td>&nbsp;</td>
                </tr>
                <tr>
                    <td>Commentaire : </td>
                    <td>
                        <?php listbox_trait('condit', "TST", $condit); ?>

                        <input type="text" name="rem" size="50" value="<?= $rem; ?>">
                    </td>
                </tr>
                <tr>
                    <td colspan="2"><b>Action à effectuer</b></td>
                </tr>
                <tr>
                    <td>Opération : </td>
                    <td>
                        <input type="radio" name="oper" value="E">Fixer la date d'expiration des comptes à <br>
                        <?php if ($config->get('GEST_POINTS') > 0) { ?>
                            <input type="radio" name="oper" value="R">Remettre à 0 les points <i>consommés</i> <br>
                            <input type="radio" name="oper" value="A">Ajouter les points suivants au solde <i>disponible</i><br>
                            <input type="radio" name="oper" value="F">Fixer le solde de points <i>disponibles</i> à <br>
                        <?php } ?>
                    </td>
                </tr>
                <tr>
                    <td>Valeur : </td>
                    <td><input type="text" name="nbrepts" size="12" value="<?= $nbrepts; ?>"></td>
                </tr>
                <tr>
                    <td colspan="2">&nbsp;</td>
                </tr>
                <tr>
                    <td></td>
                    <input type="hidden" name="action" value="submitted">
                    <td><button type="reset" class="btn">Effacer</button>
                        <button type="submit" class="btn">Effectuer</button>
                    </td>
                </tr>
            </table>
        </form>
    </div>
</div>
<?php
include(__DIR__ . '/../templates/front/_footer.php');
return (ob_get_clean());
