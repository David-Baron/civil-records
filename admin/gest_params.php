<?php

use Symfony\Component\HttpFoundation\RedirectResponse;

/** TODO: origin param system... Need to build a DTO and delete this code */
define('ADM', 10); // Compatibility only
$admtxt = 'Gestion '; // Compatibility only
require(__DIR__ . '/../next/bootstrap.php');
require(__DIR__ . '/../next/_COMMUN_env.inc.php'); // Compatibility only

if (!$userAuthorizer->isGranted(9)) {
    $response = new RedirectResponse("$root/admin/");
    $response->send();
    exit();
}

function show_grp($grp, $current, $barre)
{
    if ($barre) {
        echo ' | ';
    }
    if ($grp == $current) {
        echo '<strong>' . $grp . '</strong>';
    } else {
        echo '<a href="gest_params.php?grp=' . $grp . '">' . $grp . '</a>';
    }
}

function alaligne($texte)
{
    // insert des BR pour provoquer des retour à la ligne
    $order   = array("\r\n", "\n", "\r");
    $replace = '<br />';
    // Traitement du premier \r\n, ils ne seront pas convertis deux fois.
    return str_replace($order, $replace, $texte);
}

$js_show_help = "";
$js_show_help .= "function show(id) \n ";
$js_show_help .= "{ \n ";
$js_show_help .= "	el = document.getElementById(id); \n ";
$js_show_help .= "	if (el.style.display == 'none') \n ";
$js_show_help .= "	{ \n ";
$js_show_help .= "		el.style.display = ''; \n ";
$js_show_help .= "		el = document.getElementById('help' + id); \n ";
$js_show_help .= "	} else { \n ";
$js_show_help .= "		el.style.display = 'none'; \n ";
$js_show_help .= "		el = document.getElementById('help' + id); \n ";
$js_show_help .= "	} \n ";
$js_show_help .= "} \n ";

$ok = false;
$missingargs = false;
$xgroupe  = getparam('grp');
$xconfirm = getparam('xconfirm');

if ($xgroupe == '') {
    // Données postées
    $xgroupe = "Affichage";
    $missingargs = true;  // par défaut
}

pathroot($root, $path, $xcomm, $xpatr, $page);

$menu_software_active = 'P';

ob_start();
open_page("Paramétrage du logiciel", $root, $js_show_help); ?>
<div class="main">
    <?php zone_menu(ADM, $session->get('user')['level'], array()); ?>
    <div class="main-col-center text-center">
        <?php
        navadmin($root, "Paramétrage du logiciel");
        require(__DIR__ . '/../templates/admin/_menu-software.php'); ?>
        <h2>Paramétrage du site <?= $config->get('SITENAME'); ?></h2>
        <p>
            <strong>Paramètres : </strong>
            <?php $request = "SELECT distinct groupe FROM " . $config->get('EA_DB') . "_params WHERE NOT (groupe in ('Hidden','Deleted')) ORDER BY groupe";
            $result = EA_sql_query($request);
            $barre = false;
            while ($row = EA_sql_fetch_array($result)) {
                show_grp($row["groupe"], $xgroupe, $barre);
                $barre = true;
            } ?>
            | <a href="<?= $root; ?>/admin/update_params.php">Backup</a>
        </p>

        <?php if (!$missingargs) {
            $oktype = true;
            if ($xconfirm == 'confirmed') {
                // *** Vérification des données reçues
                $parnbr = getparam("parnbr");
                $i = 1;
                $cpt = 0;
                while ($i <= $parnbr) {
                    $parname = getparam("parname$i");
                    $parvalue = htmlentities(getparam("parvalue$i"), ENTITY_REPLACE_FLAGS, ENTITY_CHARSET);
                    if ($parvalue == "") {
                        $request = "SELECT * FROM " . $config->get('EA_DB') . "_params WHERE param = '" . $parname . "'";
                        $result = EA_sql_query($request);
                        $row = EA_sql_fetch_array($result);
                        if ($row["type"] == "B") {
                            $parvalue = 0;
                        }
                    }
                    $request = "UPDATE " . $config->get('EA_DB') . "_params SET valeur = '" . sql_quote($parvalue) . "' WHERE param = '" . $parname . "'";
                    $result = EA_sql_query($request);
                    $cpt += EA_sql_affected_rows();
                    $i++;
                }
                if ($cpt > 0) {
                    msg("Sauvegarde : " . $cpt . " paramètre(s) modifié(s).", "info");
                }
            }
        }

        $request = "SELECT * FROM " . $config->get('EA_DB') . "_params WHERE groupe='" . $xgroupe . "' ORDER BY ordre";
        $result = EA_sql_query($request);
        ?>
        <h2><?= $xgroupe; ?></h2>
        <?php if ($xgroupe == "Mail") { ?>
            <p><a href="<?= $root; ?>/admin/test_mail.php"><b>Tester l'envoi d'e-mail</b></a></p>
        <?php }
        if ($xgroupe == "Utilisateurs" and isset($udbname)) {
            msg('ATTENTION : Base des utilisateurs déportée sur ' . $udbaddr . "/" . $udbuser . "/" . $udbname . "/" . $config->get('EA_UDB') . "</p>", 'info');
        } ?>

        <form method="post">
            <table class="m-auto" summary="Formulaire">
                <?php $i = 0;
                while ($row = EA_sql_fetch_array($result)) {
                    $i++;
                ?>
                    <tr>
                        <td>
                            <b><?= $row["libelle"]; ?></b>
                            <a href="<?= $root; ?>/admin/gest_params.php?grp=<?= $xgroupe; ?>" id="help<?= $i; ?>" onclick="show('aide<?= $i; ?>');return false;"><b>(?)</b></a>
                            <span id="aide<?= $i; ?>" style="display: none" class="aide"><br><?= $row["param"]; ?> :
                                <?= alaligne($row["aide"]); ?></span> :
                        </td>
                        <td>
                            <input type="hidden" name="parname<?= $i; ?>" value="<?= $row["param"]; ?>">
                            <?php if ($row["type"] == "B") { ?>
                                <input type="checkbox" name="parvalue<?= $i; ?>" value="1" <?= ($row["valeur"] == 1 ? ' checked' : ''); ?>>
                            <?php } elseif ($row["type"] == "L") {
                                $leschoix = explode(";", $row["listval"]);
                            ?>
                                <select name="parvalue<?= $i; ?>">
                                    <?php foreach ($leschoix as $lechoix) { ?>
                                        <option <?= selected_option(intval(mb_substr($lechoix, 0, isin($lechoix, "-", 0) - 1)), $row["valeur"]); ?>><?= $lechoix; ?></option>
                                    <?php } ?>
                                </select>
                            <?php } else { ?>
                                <textarea name="parvalue<?= $i; ?>" cols="40" rows="6"><?= html_entity_decode($row["valeur"], ENTITY_REPLACE_FLAGS, ENTITY_CHARSET); ?></textarea>
                            <?php } ?>
                        </td>
                    </tr>
                <?php } ?>
                <tr>
                    <td></td>
                    <td>
                        <a href="<?= $root; ?>/admin/index.php">Annuler</a>
                        <button type="submit">Enregistrer</button>
                    </td>
                </tr>
            </table>
            <input type="hidden" name="parnbr" value="<?= $i; ?>">
            <input type="hidden" name="grp" value="<?= $xgroupe; ?>">
            <input type="hidden" name="xconfirm" value="confirmed">
        </form>
    </div>
</div>
<?php include(__DIR__ . '/../templates/front/_footer.php');

$response->setContent(ob_get_clean());
$response->send();
