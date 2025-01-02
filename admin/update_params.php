<?php

use Symfony\Component\HttpFoundation\RedirectResponse;

require(__DIR__ . '/../next/bootstrap.php');
// require(__DIR__ . '/../install/instutils.php');

if (!$userAuthorizer->isGranted(9)) {
    $response = new RedirectResponse("$root/admin/");
    $response->send();
    exit();
}

$menu_software_active = 'P';
$form_errors = [];
$success_message = '';

if ($request->getMethod() === 'POST') {
    if (!empty($_FILES['params']['tmp_name'])) { // fichier de paramètres
        if (strtolower(mb_substr($_FILES['params']['name'], -4)) != ".xml") { //Vérifie que l'extension est bien '.XML'
            $form_errors[] = ("Type de fichier incorrect !");
        }
        if (empty($form_errors)) {
            // type XML
            $filename = $_FILES['params']['tmp_name'];
            // paramètres généraux
           /*  update_params($filename, 1);
            // définitions des zones
            $table = EA_DB . "_metadb";
            $tabdata = xml_readDatabase($filename, $table);
            $tabkeys = array('ZID');
            update_metafile($tabdata, $tabkeys, $table, $par_add, $par_mod);
            // textes des étiquettes
            $table = EA_DB . "_metalg";
            $tabdata = xml_readDatabase($filename, $table);
            $tabkeys = array('ZID', 'lg');
            update_metafile($tabdata, $tabkeys, $table, $par_add, $par_mod);
            // etiquettes des groupes
            $table = EA_DB . "_mgrplg";
            $tabdata = xml_readDatabase($filename, $table);
            $tabkeys = array('grp', 'dtable', 'lg', 'sigle');
            update_metafile($tabdata, $tabkeys, $table, $par_add, $par_mod);

            writelog('Restauration des paramètres', "PARAMS", ($par_mod + $par_add)); */

            if ($par_add > 0) {
                $success_message = "<p>" . $par_add . " paramètres ajoutés.</p>";
            }
            if ($par_mod > 0) {
                $success_message = "<p>" . $par_mod . " paramètres modifiés.</p>";
            }
            if ($par_add + $par_mod == 0) {
                $success_message = "<p>Aucune modification nécessaire.</p>";
            }
        }
    }
}

ob_start();
open_page("Mise à jour des paramètres", $root); ?>
<div class="main">
    <?php zone_menu(10, $session->get('user')['level']); ?>
    <div class="main-col-center text-center">
        <?php
        navadmin($root, "Mise à jour des paramètres");
        require(__DIR__ . '/../templates/admin/_menu-software.php'); ?>
        <h2>Backup / Restauration</h2>
        <div><?= $success_message; ?></div>
        <p><strong>Actions sur les paramètres : </strong>
            <a href="<?= $root; ?>/admin/expparams.php"><b>Sauvegarder</b></a>
            | Restaurer
            || <a href="<?= $root; ?>/admin/gest_params.php">Retour</a>
        </p>
        <form method="post" enctype="multipart/form-data">
            <h2>Restauration de paramètres sauvegardés</h2>
            <table class="m-auto" summary="Formulaire">
                <tr>
                    <td>Dernier backup : </td>
                    <td>
                        <?= show_last_backup("P"); ?>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">&nbsp;</td>
                </tr>
                <tr>
                    <td>Fichier XML de paramètres : </td>
                    <td><input type="file" size="62" name="params"></td>
                </tr>
                <tr>
                    <td colspan="2">&nbsp;</td>
                </tr>
                <tr>
                    <td></td>
                    <td>
                        <button type="reset" class="btn">Annuler</button>
                        <button type="submit" class="btn">Charger</button>
                    </td>
                </tr>
            </table>
        </form>
    </div>
</div>
<?php include(__DIR__ . '/../templates/front/_footer.php');
$response->setContent(ob_get_clean());
$response->send();
