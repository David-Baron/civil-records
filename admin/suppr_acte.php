<?php

use Symfony\Component\HttpFoundation\RedirectResponse;

require(__DIR__ . '/../next/bootstrap.php');

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
    <?php zone_menu(10, $session->get('user')['level']); ?>
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
                    $script = "/tab_naiss.php";
                    $conj = "";
                    break;
                case "V":
                    $ntype = "divers";
                    $table = $config->get('EA_DB') . "_div3";
                    $script = "/tab_bans.php";
                    $conj = ", C_NOM, C_PRE";
                    break;
                case "M":
                    $ntype = "de mariage";
                    $table = $config->get('EA_DB') . "_mar3";
                    $script = "/tab_mari.php";
                    $conj = ", C_NOM, C_PRE";
                    break;
                case "D":
                    $ntype = "de décès";
                    $table = $config->get('EA_DB') . "_dec3";
                    $script = "/tab_deces.php";
                    $conj = "";
                    break;
            }

            if ($xconfirm == 'confirmed') {
                $sql = "SELECT NOM,PRE,DATETXT,COMMUNE,DEPART " . $conj . " FROM " . $table . " WHERE ID=" . $xid;
                $result = EA_sql_query($sql);
                $ligne = EA_sql_fetch_row($result);
                $sql = "DELETE FROM " . $table . " WHERE ID=" . $xid;
                $result = EA_sql_query($sql);
                 $nb = EA_sql_affected_rows();
                if ($nb > 0) {
                    echo '<p>' . $nb . ' acte ' . $ntype . ' supprimé.</p>';
                    writelog('Suppression ' . $ntype . ' #' . $xid, $ligne[3], $nb);
                    echo '<p>Retourner à la liste des actes ';
                    $comdep = $ligne[3] . ' [' . $ligne[4] . ']';
                    echo '<a href="' . $root . $script . '?xcomm='  . stripslashes($comdep) . '&xpatr=' . $ligne[0] . '"><b>' . $ligne[0] . '</b></a>';
                    if (isset($ligne[5])) {
                        echo ' ou <a href="' . $root . $script . '?xcomm=' . stripslashes($comdep) . '&xpatr=' . $ligne[5] . '"><b>' . $ligne[5] . '</b></a>';
                    }
                    echo '</p>';
                    maj_stats($xtyp, $T0, $path, "C", $ligne[3]);
                } else {
                    echo '<p>Aucun acte supprimé.</p>';
                }
            } else {
                $sql = "SELECT NOM,PRE,DATETXT,COMMUNE,DEPART" . $conj . " FROM " . $table . " WHERE ID=" . $xid;
                $result = EA_sql_query($sql);
                if ($ligne = EA_sql_fetch_row($result)) {
                    echo '<form method="post" enctype="multipart/form-data">';
                    echo '<h2 align="center">Confirmation de la suppression</h2>';
                    echo '<p class="message">Vous allez supprimer l\'acte ' . $ntype . ' du ' . $ligne[2] . "</p>";
                    echo '<p class="message">(' . $ligne[0] . " " . $ligne[1];
                    if (isset($ligne[5])) {
                        echo ' et ' . $ligne[5] . " " . $ligne[6];
                    }
                    echo ')</p>';
                    echo '<p class="message">';
                    echo '<input type="hidden" name="xtyp" value="' . $xtyp . '">';
                    echo '<input type="hidden" name="xid"  value="' . $xid . '">';
                    echo '<input type="hidden" name="xconfirm" value="confirmed">';
                    $comdep = $ligne[3] . ' [' . $ligne[4] . ']';
                    echo ' <a href="' . $root . $script . '?xtyp=' . $xtyp . '&xcomm=' . stripslashes($comdep) . '&xpatr=' . $ligne[0] . '" class="btn">Annuler</a></p>';
                    echo '<button type="submit" class="btn">Confirmer</button>';
                    echo "</form>";
                } else {
                    msg('Impossible de trouver cet acte !');
                }
            }
        } ?>
    </div>
</div>
<?php include(__DIR__ . '/../templates/front/_footer.php');
$response->setContent(ob_get_clean());
$response->send();
