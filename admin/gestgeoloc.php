<?php

use Symfony\Component\HttpFoundation\RedirectResponse;

require(__DIR__ . '/../src/bootstrap.php');

if (!$userAuthorizer->isGranted(9)) {
    $response = new RedirectResponse("$root/admin/");
    $response->send();
    exit();
}

pathroot($root, $path, $xcomm, $xpatr, $page);

$id  = getparam('id');
$act = getparam('act');

$geoloc = [
    'COMMUNE' => '',
    'DEPART' => '',
    'LAT' => '',
    'LON' => '',
    'STATUT' => 'M',
    'NOTE_N' => '',
    'NOTE_M' => '',
    'NOTE_D' => '',
    'NOTE_V' => '',
];

$geoloc_modes = ['M' => 'Manuelle', 'N' => 'Non définie', 'A' => 'Automatique'];

$missingargs = true;

if ($id > 0) {
    $action = 'Modification';
    $sql = "SELECT * FROM " . $config->get('EA_DB') . "_geoloc WHERE ID =" . $id;

    if ($result = EA_sql_query($sql)) {
        $row = EA_sql_fetch_array($result);
        $commune   = $row["COMMUNE"];
        $depart    = $row["DEPART"];
        $lon       = $row["LON"];
        $lat       = $row["LAT"];
        $statut    = $row["STATUT"];
        $noteN     = $row["NOTE_N"];
        $noteM     = $row["NOTE_M"];
        $noteD     = $row["NOTE_D"];
        $noteV     = $row["NOTE_V"];
    }
}


$menu_data_active = 'L';

ob_start();
open_page("Gestion des localités", $root, null, null, null); ?>
<div class="main">
    <?php zone_menu(10, $session->get('user')['level'], array()); ?>
    <div class="main-col-center text-center">
        <?php
        /* if ($id > 0) {
            $carto->printOnLoad(); // TODO: Need test, display fail...
        } */
        navadmin($root, "Gestion d'une localité");
        require(__DIR__ . '/../templates/admin/_menu-data.php'); ?>

        <?php if ($id > 0 && $act == "del") {
            $reqmaj = "DELETE FROM " . $config->get('EA_DB') . "_geoloc WHERE ID=" . $id . ";";
            $result = EA_sql_query($reqmaj, $a_db);
            //writelog('Suppression localité #'.$id,$lelogin,1);
            echo '<p><b>La localité est supprimée.</b></p>';
            header("Location: $root/admin/gestgeoloc.php");
            exit();
        }

        // Données postées -> ajouter ou modifier
        if (getparam('action') == 'submitted') {
            $ok = true;
            // validations si besoin
            if ($ok) {
                $mes = "";
                if (getparam('lon') <> $lon or getparam('lat') <> $lat) {
                    $newstatut = 'M';
                } else {
                    $newstatut = $statut;
                }
                $missingargs = false;
                $reqmaj = "UPDATE " . $config->get('EA_DB') . "_geoloc SET ";
                $reqmaj = $reqmaj .
                    "NOTE_N = '" . sql_quote(getparam('noteN')) . "', " .
                    "NOTE_M = '" . sql_quote(getparam('noteM')) . "', " .
                    "NOTE_D = '" . sql_quote(getparam('noteD')) . "', " .
                    "NOTE_V = '" . sql_quote(getparam('noteV')) . "', " .
                    "STATUT = '" . sql_quote($newstatut) . "', " .
                    "LON    = '" . sql_quote(getparam('lon')) . "', " .
                    "LAT    = '" . sql_quote(getparam('lat')) . "' " .
                    " WHERE ID=" . $id . ";";

                $result = EA_sql_query($reqmaj);
                echo '<p><b>Fiche enregistrée' . $mes . '.</b></p>';
            }
        }

        //Si pas tout les arguments nécessaire, on affiche le formulaire
        if ($id <> 0 && $missingargs) { ?>
            <h2><?= $action; ?> d'une fiche de localité</h2>
            <form method="post">
                <table class="m-auto" summary="Formulaire">
                    <tr>
                        <td>Localité : </td>
                        <td colspan="2"><b><?= $row['COMMUNE']; ?> [<?= $row['DEPART']; ?>]</b></td>
                    </tr>
                    <tr>
                        <td>Coordonnées : </td>
                        <td colspan="2">
                            Latitude <input type="text" name="lat" id="lat" size="10" value="<?= $row['LAT']; ?>">
                            Longitude <input type="text" name="lon" id="lon" size="10" value="<?= $row['LON']; ?>">
                            <?php if ($row['LAT'] && $row['LON']) { ?>
                                <a href="https://www.openstreetmap.org/#map=12/<?= $row['LAT']; ?>/<?= $row['LON']; ?>" target="_blank">
                                    <img src="<?= $root; ?>/assets/img/pin_eye.png" width="16" height="24" alt="Voir dans openstreetmap.">
                                </a>
                            <?php } else { ?>
                                <a href="https://nominatim.openstreetmap.org/search?county=<?= $row['DEPART']; ?>&city=<?= $row['COMMUNE']; ?>&format=json" target="_blank">
                                    <img src="<?= $root; ?>/assets/img/pin_eye.png" width="16" height="24" alt="Trouver les coordonées dans nominatim.">
                                </a>
                            <?php } ?>
                        </td>
                    </tr>
                    <!-- <tr>
                        <td>Géolocalisation : </td>
                        <td colspan="2"><?= $geoloc_modes[$row['STATUT']]; ?></td>
                    </tr> -->
                    <tr>
                        <td>Commentaire Naissances : </td>
                        <td colspan="2">
                            <textarea name="noteN" cols="50" rows="2">
                                <?= html_entity_decode($row["NOTE_N"], ENTITY_REPLACE_FLAGS, ENTITY_CHARSET); ?>
                            </textarea>
                        </td>
                    </tr>
                    <tr>
                        <td>Commentaire Mariages : </td>
                        <td colspan="2">
                            <textarea name="noteM" cols="50" rows="2">
                                <?= html_entity_decode($row["NOTE_M"], ENTITY_REPLACE_FLAGS, ENTITY_CHARSET); ?>
                            </textarea>
                        </td>
                    </tr>
                    <tr>
                        <td>Commentaire Décès : </td>
                        <td colspan="2">
                            <textarea name="noteD" cols="50" rows="2">
                                <?= html_entity_decode($row["NOTE_D"], ENTITY_REPLACE_FLAGS, ENTITY_CHARSET); ?>
                            </textarea>
                        </td>
                    </tr>
                    <tr>
                        <td>Commentaire Actes divers : </td>
                        <td colspan="2">
                            <textarea name="noteV" cols="50" rows="2">
                                <?= html_entity_decode($row["NOTE_V"], ENTITY_REPLACE_FLAGS, ENTITY_CHARSET); ?>
                            </textarea>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <input type="hidden" name="id" value="<?= $id; ?>">
                            <input type="hidden" name="commune" value="<?= $commune; ?>">
                            <input type="hidden" name="action" value="submitted">
                        </td>
                        <td colspan="2">
                            <a href="<?= $root; ?>/admin/aide/geoloc.html" target="_blank">Aide</a>
                            <button type="reset" class="btn">Effacer</button>
                            <button type="submit" class="btn">Enregistrer</button>
                            <?php if ($id > 0) { ?>
                                <a href="<?= $root; ?>/admin/gestgeoloc.php?id=<?= $id; ?>&amp;act=del">Supprimer cette localité</a>
                            <?php } ?>
                        </td>
                    </tr>
                </table>
            </form>
        <?php } else { ?>
            <p>
                <a href="<?= $root; ?>/admin/listgeolocs.php">Retour à la liste des localités</a>
                <?php if ($id > 0 && $act != "del") { ?>
                    <a href="<?= $root; ?>/admin/gestgeoloc.php?id=<?= $id; ?>">Retour à la fiche de <?= getparam('commune'); ?></a>
                <?php } ?>
            </p>
        <?php } ?>
    </div>
</div>
<?php include(__DIR__ . '/../templates/front/_footer.php');

$response->setContent(ob_get_clean());
$response->send();
