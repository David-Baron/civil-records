<?php

use Symfony\Component\HttpFoundation\RedirectResponse;

define('ADM', 10); // Compatibility only
$admtxt = 'Gestion '; // Compatibility only
require(__DIR__ . '/../next/bootstrap.php');
require(__DIR__ . '/../next/_COMMUN_env.inc.php'); // Compatibility only

if (!$userAuthorizer->isGranted(6)) {
    $response = new RedirectResponse("$root/admin/");
    $response->send();
    exit();
}

pathroot($root, $path, $xcomm, $xpatr, $page);

$missingargs = false;
$oktype = false;
$xid  = getparam('xid');
$xtyp = getparam('xtyp');
$xconfirm = getparam('xconfirm');

ob_start();
open_page("Suppression d'un acte", $root); ?>
<div class="main">
    <?php zone_menu(ADM, $session->get('user')['level']); ?>
    <div class="main-col-center text-center">
        <?php
        navadmin($root, "Suppression d'un acte");

        if ($xid == '' or $xtyp == '') {
            // Données postées
            msg("Vous devez préciser le numéro et le type de l'acte.");
            $missingargs = true;  // par défaut
        }
        if (! $missingargs) {
            $oktype = true;
            switch ($xtyp) {
                case "N":
                    $ntype = "de naissance";
                    $table = $config->get('EA_DB') . "_nai3";
                    $script = "tab_naiss.php";
                    $conj = "";
                    break;
                case "V":
                    $ntype = "divers";
                    $table = $config->get('EA_DB') . "_div3";
                    $script = "tab_bans.php";
                    $conj = ", C_NOM, C_PRE";
                    break;
                case "M":
                    $ntype = "de mariage";
                    $table = $config->get('EA_DB') . "_mar3";
                    $script = "tab_mari.php";
                    $conj = ", C_NOM, C_PRE";
                    break;
                case "D":
                    $ntype = "de décès";
                    $table = $config->get('EA_DB') . "_dec3";
                    $script = "tab_deces.php";
                    $conj = "";
                    break;
            }

            if ($xconfirm == 'confirmed') {
                $request = "SELECT NOM,PRE,DATETXT,COMMUNE,DEPART " . $conj . " FROM " . $table . " WHERE ID=" . $xid;
                $result = EA_sql_query($request);
                $ligne = EA_sql_fetch_row($result);
                $request = "DELETE FROM " . $table . " WHERE ID=" . $xid;
                $result = EA_sql_query($request);
                //echo $request;
                $nb = EA_sql_affected_rows();
                if ($nb > 0) {
                    echo '<p>' . $nb . ' acte ' . $ntype . ' supprimé.</p>';
                    writelog('Suppression ' . $ntype . ' #' . $xid, $ligne[3], $nb);
                    echo '<p>Retourner à la liste des actes ';
                    $comdep = $ligne[3] . ' [' . $ligne[4] . ']';
                    echo '<a href="' . mkurl($script, stripslashes($comdep), $ligne[0]) . '"><b>' . $ligne[0] . '</b></a>';
                    if (isset($ligne[5])) {
                        echo ' ou <a href="' . mkurl($script, stripslashes($comdep), $ligne[5]) . '"><b>' . $ligne[5] . '</b></a>';
                    }
                    echo '</p>';
                    maj_stats($xtyp, $T0, $path, "C", $ligne[3]);
                } else {
                    echo '<p>Aucun acte supprimé.</p>';
                }
            } else {
                $request = "SELECT NOM,PRE,DATETXT,COMMUNE,DEPART" . $conj . " FROM " . $table . " WHERE ID=" . $xid;
                $result = EA_sql_query($request);
                if ($ligne = EA_sql_fetch_row($result)) {
                    echo '<form method="post" enctype="multipart/form-data" action="">' . "\n";
                    echo '<h2 align="center">Confirmation de la suppression</h2>';

                    echo '<p class="message">Vous allez supprimer l\'acte ' . $ntype . ' du ' . $ligne[2] . "</p>";
                    echo '<p class="message">(' . $ligne[0] . " " . $ligne[1];
                    if (isset($ligne[5])) {
                        echo ' et ' . $ligne[5] . " " . $ligne[6];
                    }
                    echo ')</p>';
                    echo '<p class="message">';
                    echo '<input type="hidden" name="xtyp" value="' . $xtyp . '" />';
                    echo '<input type="hidden" name="xid"  value="' . $xid . '" />';
                    echo '<input type="hidden" name="xconfirm" value="confirmed" />';
                    echo '<input type="submit" value=" >> CONFIRMER LA SUPPRESSION >> " />' . "\n";
                    $comdep = $ligne[3] . ' [' . $ligne[4] . ']';
                    $url = mkurl($script, stripslashes($comdep), $ligne[0]);
                    echo '&nbsp; &nbsp; &nbsp; <a href="' . $url . '">Annuler</a></p>';
                    echo "</form>\n";
                } else {
                    msg('Impossible de trouver cet acte !');
                }
            } // confirmed ??
        } ?>
    </div>
</div>
<?php include(__DIR__ . '/../templates/front/_footer.php');
$response->setContent(ob_get_clean());
$response->send();
