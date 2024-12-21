<?php

use Symfony\Component\HttpFoundation\RedirectResponse;

define('ADM', 10); // Compatibility only
$admtxt = 'Gestion '; // Compatibility only
require(__DIR__ . '/../next/bootstrap.php');
require(__DIR__ . '/../next/_COMMUN_env.inc.php'); // Compatibility only

if (!$userAuthorizer->isGranted(9)) {
    $response = new RedirectResponse("$root/admin/");
    $response->send();
    exit();
}

pathroot($root, $path, $xcomm, $xpatr, $page);

$id  = getparam('id');
$act = getparam('act');
$lon    = getparam('lon');
$lat    = getparam('lat');
$noteN  = getparam('noteN');
$noteM  = getparam('noteM');
$noteD  = getparam('noteD');
$noteV  = getparam('noteV');
$leid = getparam('id');
$ast = array("M" => "Manuelle", "N" => "Non définie", "A" => "Automatique");
$missingargs = true;
$JSheader = "";

if ($id > 0) {  // édition
    $action = 'Modification';
    $request = "SELECT * FROM " . $config->get('EA_DB') . "_geoloc WHERE ID =" . $id;

    if ($result = EA_sql_query($request)) {
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
    } else {
        echo "<p>*** FICHE NON TROUVEE***</p>";
    }
    $zoom = 11;
    if ($lon == 0 and $lat == 0 and $config->get('GEO_CENTRE_CARTE') <> "") {
        $georeq = "SELECT LON,LAT FROM " . $config->get('EA_DB') . "_geoloc WHERE COMMUNE = '" . sql_quote($config->get('GEO_CENTRE_CARTE')) . "' AND STATUT IN ('A','M')";
        $geores =  EA_sql_query($georeq);
        if ($geo = EA_sql_fetch_array($geores)) {
            $lon = $geo['LON'];
            $lat = $geo['LAT'];
            $zoom = 5;
        }
    }
    if ($lon == 0 and $lat == 0) {
        $lon = 5;
        $lat = 50; // Froidfontaine !!
        $zoom = 5;
    }

    include_once(__DIR__ . '/../tools/GoogleMap/OrienteMap.inc.php');
    include_once(__DIR__ . '/../tools/GoogleMap/Jsmin.php');

    $carto = new GoogleMapAPI();
    $carto->_minify_js = isset($_REQUEST["min"]) ? false : true;
    $carto->setMapType("terrain");
    $carto->setTypeControlsStyle("dropdown");
    $carto->setHeight(300);
    $carto->setWidth(500);
    global $root;
    $image = $config->get('EA_URL_SITE') . $root . '/assets/img/pin_eye.png';
    $Xanchor = 10;
    $Yanchor = 35;
    $carto->setMarkerIcon($image, '', $Xanchor, $Yanchor); // défini le décalage du pied de la punaise
    $carto->addMarkerByCoords($lon, $lat);
    $carto->enableMarkerDraggable();
    $carto->setZoomLevel($zoom);

    $JSheader = $carto->getHeaderJS();
    $JSheader .= $carto->getMapJS();
}

$menu_data_active = 'L';
ob_start();
open_page("Gestion des localités", $root, null, null, $JSheader);
if ($id > 0) {
    $carto->printOnLoad(); // TODO: Need test, display fail...
}
navadmin($root, "Gestion d'une localité");
zone_menu(ADM, $session->get('user')['level'], array()); //ADMIN STANDARD
?>
<div id="col_main">
    <?php require(__DIR__ . '/../templates/admin/_menu_data.php'); ?>

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

            //echo "<p>".$reqmaj."</p>";
            if ($result = EA_sql_query($reqmaj)) {
                // echo '<p>'.EA_sql_error().'<br />'.$reqmaj.'</p>';
                echo '<p><b>Fiche enregistrée' . $mes . '.</b></p>';
                $id = 0;
            } else {
                echo ' -> Erreur : ';
                echo '<p>' . EA_sql_error() . '<br />' . $reqmaj . '</p>';
            }
        }
    }

    //Si pas tout les arguments nécessaire, on affiche le formulaire
    if ($id <> 0 && $missingargs) { ?>
        <h2><?= $action; ?> d'une fiche de localité</h2>
        <form method="post">
            <table cellspacing="0" cellpadding="1" summary="Formulaire">
                <tr>
                    <td>Localité : </td>
                    <td colspan="2"><b><?= $commune; ?> [<?= $depart; ?>]</b></td>
                </tr>
                <tr>
                    <td>Longitude : </td>
                    <td><input type="text" name="lon" id="lon" size="10" value="<?= $row['LON']; ?>"></td>
                    <td rowspan="4">
                        <?php $carto->printMap(); ?>
                    </td>
                </tr>
                <tr>
                    <td>Latitude : </td>
                    <td><input type="text" name="lat" id="lat" size="10" value="<?= $row['LAT']; ?>"></td>
                </tr>
                <tr>
                    <td>Géolocalisation : </td>
                    <td><?= $ast[$statut]; ?></td>
                </tr>
                <tr>
                    <td colspan="2"><b>Déplacer la punaise pour corriger la localisation </b>
                    </td>
                </tr>
                <tr>
                    <td>Commentaire Naissances : </td>
                    <td colspan="2">
                        <textarea name="noteN" cols="60" rows="2"><?= html_entity_decode($noteN, ENTITY_REPLACE_FLAGS, ENTITY_CHARSET); ?></textarea>
                    </td>
                </tr>
                <tr>
                    <td>Commentaire Mariages : </td>
                    <td colspan="2">
                        <textarea name="noteM" cols="60" rows="2"><?= html_entity_decode($noteM, ENTITY_REPLACE_FLAGS, ENTITY_CHARSET); ?></textarea>
                    </td>
                </tr>
                <tr>
                    <td>Commentaire Décès : </td>
                    <td colspan="2">
                        <textarea name="noteD" cols="60" rows="2"><?= html_entity_decode($noteD, ENTITY_REPLACE_FLAGS, ENTITY_CHARSET); ?></textarea>
                    </td>
                </tr>
                <tr>
                    <td>Commentaire Actes divers : </td>
                    <td colspan="2">
                        <textarea name="noteV" cols="60" rows="2"><?= html_entity_decode($noteV, ENTITY_REPLACE_FLAGS, ENTITY_CHARSET); ?></textarea>
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
                        <button type="reset">Effacer</button>
                        <button type="submit">Enregistrer</button>
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
            <?php if ($leid > 0 && $act != "del") { ?>
                <a href="<?= $root; ?>/admin/gestgeoloc.php?id=<?= $leid; ?>">Retour à la fiche de <?= getparam('commune'); ?></a>
            <?php } ?>
        </p>
    <?php } ?>
</div>
<?php include(__DIR__ . '/../templates/front/_footer.php');

$response->setContent(ob_get_clean());
$response->send();
