<?php

use Symfony\Component\HttpFoundation\RedirectResponse;

if (!$userAuthorizer->isGranted(9)) {
    $response = new RedirectResponse("$root/admin/");
    $response->send();
    exit();
}


$id  = $request->get('id');
$act = $request->get('act');

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
$menu_data_active = 'L';
$form_errors = [];

if ($id > 0 && $act == "del") {
    $reqmaj = "DELETE FROM " . $config->get('EA_DB') . "_geoloc WHERE ID=" . $id . ";";
    $result = EA_sql_query($reqmaj, $a_db);

    $session->getFlashBag()->add('success', 'La localité est supprimée.');
    $response = new RedirectResponse("$root/admin/geolocalizations");
    $response->send();
    exit();
}

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

if ($request->getMethod() === 'POST') {

    // validations si besoin
    if (empty($form_errors)) {

        $newstatut = 'M';
        /*         if ($request->request->get('lon') != '' or $request->request->get('lat') != '') {
            $newstatut = 'M';
        } */

        $reqmaj = "UPDATE " . $config->get('EA_DB') . "_geoloc SET ";
        $reqmaj = $reqmaj .
            "NOTE_N = '" . $request->request->get('noteN') . "', " .
            "NOTE_M = '" . $request->request->get('noteM') . "', " .
            "NOTE_D = '" . $request->request->get('noteD') . "', " .
            "NOTE_V = '" . $request->request->get('noteV') . "', " .
            "STATUT = '" . $newstatut . "', " .
            "LON    = '" . $request->request->get('lon') . "', " .
            "LAT    = '" . $request->request->get('lat') . "' " .
            " WHERE ID=" . $id . ";";

        $result = EA_sql_query($reqmaj);

        $session->getFlashBag()->add('success', 'Les données sont enregistrées.');
        $response = new RedirectResponse("$root/admin/geolocalizations/detail?id=$id");
        $response->send();
        exit();
    }
}


ob_start();
open_page("Gestion des localités", $root, null, null, null); ?>
<div class="main">
    <?php zone_menu(10, $session->get('user')['level'], array()); ?>
    <div class="main-col-center text-center">
        <?php
        navadmin($root, "Gestion d'une localité");
        require(__DIR__ . '/../templates/admin/_menu-data.php'); ?>

        <h2><?= $action; ?> d'une fiche de localité</h2>
        <form method="post">
            <table class="m-auto" summary="Formulaire">
                <tr>
                    <td>Localité : </td>
                    <td><b><?= $row['COMMUNE']; ?> [<?= $row['DEPART']; ?>]</b></td>
                </tr>
                <tr>
                    <td></td>
                    <td>
                        <?php if ($row['LAT'] && $row['LON']) { ?>
                            <a href="https://www.openstreetmap.org/#map=12/<?= $row['LAT']; ?>/<?= $row['LON']; ?>" target="_blank" class="btn">
                                OpenStreetMap <img src="<?= $root; ?>/themes/img/open_link.png" width="16" height="16" alt="OpenStreetMap">
                            </a>
                            <a href="https://www.google.com/maps/place/<?= $row['DEPART']; ?>+<?= $row['COMMUNE']; ?>" target="_blank" class="btn">
                                GoogleMap <img src="<?= $root; ?>/themes/img/open_link.png" width="16" height="16" alt="GoogleMap">
                            </a>
                        <?php } ?>
                    </td>
                </tr>
                <tr>
                    <td>Coordonnées : </td>
                    <td>
                        Latitude <input type="text" name="lat" id="lat" size="10" value="<?= $row['LAT']; ?>">
                        Longitude <input type="text" name="lon" id="lon" size="10" value="<?= $row['LON']; ?>">
                        <?php if (!$row['LAT'] || !$row['LON']) { ?>
                            <a href="https://nominatim.openstreetmap.org/search?county=<?= $row['DEPART']; ?>&city=<?= $row['COMMUNE']; ?>&format=json" target="_blank" class="btn">
                                Nominatim <img src="<?= $root; ?>/themes/img/open_link.png" width="16" height="16" alt="Nominatim">
                            </a>
                        <?php } ?>
                    </td>
                </tr>
                <!-- <tr>
                        <td>Géolocalisation : </td>
                        <td><?= $geoloc_modes[$row['STATUT']]; ?></td>
                    </tr> -->
                <tr>
                    <td>Commentaire Naissances : </td>
                    <td>
                        <textarea name="noteN" cols="50" rows="2">
                                <?= html_entity_decode($row["NOTE_N"], ENTITY_REPLACE_FLAGS, ENTITY_CHARSET); ?>
                            </textarea>
                    </td>
                </tr>
                <tr>
                    <td>Commentaire Mariages : </td>
                    <td>
                        <textarea name="noteM" cols="50" rows="2">
                                <?= html_entity_decode($row["NOTE_M"], ENTITY_REPLACE_FLAGS, ENTITY_CHARSET); ?>
                            </textarea>
                    </td>
                </tr>
                <tr>
                    <td>Commentaire Décès : </td>
                    <td>
                        <textarea name="noteD" cols="50" rows="2">
                                <?= html_entity_decode($row["NOTE_D"], ENTITY_REPLACE_FLAGS, ENTITY_CHARSET); ?>
                            </textarea>
                    </td>
                </tr>
                <tr>
                    <td>Commentaire Actes divers : </td>
                    <td>
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
                    <td>
                        <button type="reset" class="btn">Effacer</button>
                        <button type="submit" class="btn">Enregistrer</button>
                        <?php if ($id > 0) { ?>
                            <a href="<?= $root; ?>/admin/geolocalizations/detail?id=<?= $id; ?>&amp;act=del" class="btn">Supprimer</a>
                        <?php } ?>
                        <a href="<?= $root; ?>/admin/aide/geoloc.html" target="_blank" class="btn">Aide</a>
                    </td>
                </tr>
            </table>
        </form>
    </div>
</div>
<?php include(__DIR__ . '/../templates/front/_footer.php');

return (ob_get_clean());
