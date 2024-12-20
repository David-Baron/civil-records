<?php

use Symfony\Component\HttpFoundation\RedirectResponse;

define('ADM', 10); // Compatibility only
$admtxt = 'Gestion '; // Compatibility only
require(__DIR__ . '/../next/bootstrap.php');
require(__DIR__ . '/../next/_COMMUN_env.inc.php'); // Compatibility only

if (!$userAuthorizer->isGranted(7)) {
    $response = new RedirectResponse("$root/admin/");
    $response->send();
    exit();
}

pathroot($root, $path, $xcomm, $xpatr, $page);

$title = "Ajout d'un acte";
$ok = false;
$missingargs = false;
$oktype = false;
$today = today();

$menu_data_active = 'A';

ob_start();
open_page($title, $root);

include(__DIR__ . '/../tools/PHPLiveX/PHPLiveX.php');
$ajax = new PHPLiveX(array("getCommunes"));
$ajax->Run(false, "../tools/PHPLiveX/phplivex.js");

navadmin($root, $title);
zone_menu(ADM, $session->get('user')['level'], array()); ?>
<div id="col_main">
    <?php require(__DIR__ . '/../templates/admin/_menu-data.php'); ?>
    <form method="post" action="<?= $root; ?>/admin/edit_acte.php">
        <h2><?= $title; ?></h2>
        <table summary="Formulaire">
            <tr>
                <td>Commune / Paroisse : </td>
                <td>
                    <select id="ComDep" name="ComDep">
                        <option value="">Choisir d'abord le type d'acte</option>
                    </select><img id="prl" src="<?= $root; ?>/assets/img/minispinner.gif" style="visibility:hidden;">
                </td>
            </tr>
            <tr>
                <td colspan="2">&nbsp;</td>
            </tr>
            <tr>
                <td>Type de l'acte : </td>
                <td>
                    <?php $ajaxcommune = ' onClick="getCommunes(this.value, {"content_type": "json", "target": "ComDep", "preloader": "prl"})"; '; ?>
                    <input type="hidden" name="TypeActes" value="X">
                    <input type="radio" name="TypeActes" value="N" <?= $ajaxcommune; ?>>Naissance
                    <input type="radio" name="TypeActes" value="M" <?= $ajaxcommune; ?>>Mariage
                    <input type="radio" name="TypeActes" value="D" <?= $ajaxcommune; ?>>Décès
                    <input type="radio" name="TypeActes" value="V" <?= $ajaxcommune; ?>>Acte divers :
                    <?php listbox_divers("typdivers", "***Tous***", 0); ?>
                </td>
            </tr>
            <tr>
                <td colspan="2">&nbsp;</td>
            </tr>
            <tr>
                <td></td>
                <td>
                    <button type="reset">Annuler</button>
                    <button type="submit">Ajouter</button>
                </td>
            </tr>
        </table>
        <input type="hidden" name="action" value="submitted">
        <input type="hidden" name="xid" value="-1">
    </form>

</div>
<?php include(__DIR__ . '/../templates/front/_footer.php');

$response->setContent(ob_get_clean());
$response->send();
