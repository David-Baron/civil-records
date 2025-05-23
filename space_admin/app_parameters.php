<?php

use Symfony\Component\HttpFoundation\RedirectResponse;

/** TODO: origin param system... Need to build a DTO and delete this code */

if (!$userAuthorizer->isGranted(9)) {
    $response = new RedirectResponse("$root/admin/");
    $response->send();
    exit();
}

function show_grp($grp, $current, $barre)
{
    global $root;

    if ($barre) {
        echo ' | ';
    }
    if ($grp == $current) {
        echo '<strong>' . $grp . '</strong>';
    } else {
        echo '<a href="' . $root . '/admin/application/parameters?grp=' . $grp . '">' . $grp . '</a>';
    }
}


$show_help =
    <<<AAA
function show(id)
{
	el = document.getElementById(id);
	if (el.style.display == 'none') {
		el.style.display = '';
		el = document.getElementById('help' + id);
	} else {
		el.style.display = 'none';
		el = document.getElementById('help' + id);
	}
}
AAA;


$xgroupe  = $request->get('grp', 'Affichage');
$xconfirm = getparam('xconfirm');


$ok = false;
$missingargs = true;

$menu_software_active = 'P';


ob_start();
open_page("Paramétrage du logiciel", $root, $show_help); ?>
<div class="main">
    <?php zone_menu(10, $session->get('user')['level'], array()); ?>
    <div class="main-col-center text-center">
        <?php
        navadmin($root, "Paramétrage du logiciel");
        require(__DIR__ . '/../templates/admin/_menu-software.php'); ?>
        <hr>
        <h2>Paramétrage du site <?= $config->get('SITENAME'); ?></h2>
        <p>
            <strong>Paramètres : </strong>
            <?php $sql = "SELECT distinct groupe FROM " . $config->get('EA_DB') . "_params WHERE NOT (groupe in ('Hidden','Deleted', 'Replaced')) ORDER BY groupe";
            $result = EA_sql_query($sql);
            $barre = false;
            while ($row = EA_sql_fetch_array($result)) {
                show_grp($row["groupe"], $xgroupe, $barre);
                $barre = true;
            } ?>
            | <a href="<?= $root; ?>/admin/application/maj_parametres">Backup</a>
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
                        $sql = "SELECT * FROM " . $config->get('EA_DB') . "_params WHERE param = '" . $parname . "'";
                        $result = EA_sql_query($sql);
                        $row = EA_sql_fetch_array($result);
                        if ($row["type"] == "B") {
                            $parvalue = 0;
                        }
                    }
                    $sql = "UPDATE " . $config->get('EA_DB') . "_params SET valeur = '" . sql_quote($parvalue) . "' WHERE param = '" . $parname . "'";
                    $result = EA_sql_query($sql);
                    $cpt += EA_sql_affected_rows();
                    $i++;
                }
                if ($cpt > 0) {
                    msg("Sauvegarde : " . $cpt . " paramètre(s) modifié(s).", "info");
                }
            }
        }

        $sql = "SELECT * FROM " . $config->get('EA_DB') . "_params WHERE groupe='" . $xgroupe . "' ORDER BY ordre";
        $result = EA_sql_query($sql);
        ?>
        <h2><?= $xgroupe; ?></h2>
        <?php if ($xgroupe == "Mail" && !isset($_ENV['MAILER_FACTORY_DSN'])) { ?>
            <p><a href="<?= $root; ?>/admin/environment.php" class="danger"><b>Veuillez configurer votre messagerie</b></a></p>
        <?php }
        if ($xgroupe == "Utilisateurs" && isset($udbname)) {
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
                            <a href="<?= $root; ?>/admin/application/parameters?grp=<?= $xgroupe; ?>" id="help<?= $i; ?>" onclick="show('aide<?= $i; ?>');return false;"><b>(?)</b></a>
                            <span id="aide<?= $i; ?>" style="display: none" class="aide"><br><?= $row["aide"]; ?></span>
                        </td>
                        <td>
                            <input type="hidden" name="parname<?= $i; ?>" value="<?= $row["param"]; ?>">
                            <?php
                            switch ($row["type"]) {
                                case "B":
                                    $size = 1;
                                    break;
                                case "N":
                                case "L":
                                    $size = 5;
                                    $maxsize = 5;
                                    break;
                                case "C":
                                    $size = 50;
                                    $maxsize = 250;
                                    break;
                                case "T":
                                    $size = 1000;
                                    $maxsize = 0;
                                    break;
                                default:
                                    $size = 1;
                                    $maxsize = 0;
                                    break;
                            }
                            if ($row["type"] == "B") { ?>
                                <input type="checkbox" name="parvalue<?= $i; ?>" value="1" <?= ($row["valeur"] == 1 ? ' checked' : ''); ?>>
                            <?php } elseif ($row["type"] == "L") {
                                $leschoix = explode(";", $row["listval"]);
                            ?>
                                <select name="parvalue<?= $i; ?>">
                                    <?php foreach ($leschoix as $lechoix) { ?>
                                        <option <?= (intval(mb_substr($lechoix, 0, isin($lechoix, "-", 0) - 1)) == $row["valeur"] ? 'selected' : ''); ?>><?= $lechoix; ?></option>
                                    <?php } ?>
                                </select>
                                <?php } else {
                                if ($size <= 100) { ?>
                                    <input type="text" name="parvalue<?= $i; ?>" size="<?= $size; ?>" maxlength="<?= $maxsize; ?>" value="<?= $row["valeur"]; ?>">
                                <?php } else { ?>
                                    <textarea name="parvalue<?= $i; ?>" cols="40" rows="6"><?= html_entity_decode($row["valeur"], ENTITY_REPLACE_FLAGS, ENTITY_CHARSET); ?></textarea>
                                <?php } ?>
                            <?php } ?>
                        </td>
                    </tr>
                <?php } ?>
                <tr>
                    <td></td>
                    <td>
                        <a href="<?= $root; ?>/admin/" class="btn">Annuler</a>
                        <button type="submit" class="btn">Enregistrer</button>
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

return (ob_get_clean());
