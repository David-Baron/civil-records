<?php

use Symfony\Component\HttpFoundation\RedirectResponse;

define('ADM', 0); // Compatibility only
$admtxt = ''; // Compatibility only
require(__DIR__ . '/next/bootstrap.php');
require(__DIR__ . '/next/_COMMUN_env.inc.php'); // Compatibility only

if (PUBLIC_LEVEL < 4 && !$userAuthorizer->isGranted(1)) {
    $response = new RedirectResponse("$root/login.php");
    $response->send();
    exit();
}

if ($request->get('act') && $request->get('act') === 'logout') {
    $session->clear();
    $response = new RedirectResponse("$root/");
    $response->send();
    exit();
}

$xtyp = getparam('xtyp', 'N');
// $act = getparam('act'); useless now
$init = getparam('init');
$vue = $request->get('vue', 'T'); // T = Tableau / C = Carte
$xpatr = "";
$page = "";
$JSheader = "";

if (SHOW_ALLTYPES != 1) $xtyp = 'N';


// pathroot($root, $path, $xtyp, $xpatr, $page);

if (GEO_MODE_PUBLIC == 5 || $vue == 'C') { // si pas localité isolée et avec carte

    $geo_haut_carte = 400;
    $geo_degroupage = 10;
    $geo_zoom = 0;

    if (GEO_HAUT_CARTE != "") $geo_haut_carte = GEO_HAUT_CARTE;
    if (GEO_ZOOM_DEGROUPAGE != "") $geo_degroupage = GEO_ZOOM_DEGROUPAGE;
    if (GEO_ZOOM_INITIAL != "") $geo_zoom = GEO_ZOOM_INITIAL;

    require(__DIR__ .'/tools/GoogleMap/OrienteMap.inc.php');
    require(__DIR__ .'/tools/GoogleMap/Jsmin.php');

    $carto = new GoogleMapAPI();
    $carto->_minify_js = isset($_REQUEST["min"]) ? false : true;
    require(__DIR__ .'/tools/carto_index.php');
    //$carto->addMarkerByAddress("Bievre, Namur","Bièvre", "Texte de la bulle");
    $carto->setMapType("terrain");
    $carto->setTypeControlsStyle("dropdown");
    $carto->setHeight($geo_haut_carte);
    $carto->setWidth("100%");
    $carto->enableClustering();
    $carto->setClusterOptions($geo_degroupage); // plus de cluster au dela de ce niveau de zoom
    $carto->setClusterLocation(__DIR__ . "/tools/GoogleMap/markerclusterer_compiled.js");

    if (GEO_CENTRE_CARTE <> "") {
        $georeq = "SELECT LON,LAT FROM " . EA_DB . "_geoloc WHERE COMMUNE='" . sql_quote(GEO_CENTRE_CARTE) . "' AND STATUT IN ('A','M')";
        $geores =  EA_sql_query($georeq);
        if ($geo = EA_sql_fetch_array($geores)) {
            $carto->setCenterCoords($geo['LON'], $geo['LAT']);
        }
    }

    if ($geo_zoom > 0) {
        $carto->disableZoomEncompass();
        $carto->setZoomLevel($geo_zoom);
    }

    $JSheader = $carto->getHeaderJS();
    $JSheader .= $carto->getMapJS();
}

ob_start();
open_page(SITENAME . " : Dépouillement d'actes de l'état-civil et des registres paroissiaux", $root, null, null, $JSheader, '../index.htm', 'rss.php');
navigation($root, 1);

$menu_actes = zone_menu(0, 0, array('s' => $vue, 'c' => 'O')); // PUBLIC STAT(retour menu_actes)

echo '<div id="col_main">';

if (strlen(trim(AVERTISMT)) > 0) {
    if (isin(AVERTISMT, "</p>") > 0) {
        echo AVERTISMT;
    } else {
        echo '<p>' . AVERTISMT . '</p>';
    }
}

echo '<h2>Communes et paroisses';
if (GEO_MODE_PUBLIC >= 3 && GEO_MODE_PUBLIC < 5) {
    echo " : ";
    if ($vue == 'C') {
        echo 'Carte | <a href="' . $root . '/index.php?vue=T&xtyp=' . $xtyp . '"' . ($vue == 'T' ? ' class="bolder"' : '') . '>Tableau</a>';
    }

    if ($vue == 'T') {
        echo '<a href="' . $root . '/index.php?vue=C&xtyp=' . $xtyp . '"' . ($vue == 'C' ? ' class="bolder"' : '') . '>Carte</a> | Tableau';
    }
}
echo '</h2>';

if (GEO_MODE_PUBLIC == 5 || $vue == 'C') { // si pas localité isolée et avec carte
    echo '<p><b>' . $menu_actes . '</b></p>';
    //--- Carte
    $carto->printOnLoad();
    $carto->printMap();
    //$carto->printSidebar();
}

if (GEO_MODE_PUBLIC == 5 || $vue == 'T') { // si pas localité isolée et avec carte
    // $menu_actes calculé dans le module statistiques
    echo '<p><b>' . $menu_actes . '</b></p>';
    require(__DIR__ .'/tools/tableau_index.php');
}

include(__DIR__ . "/templates/front/_commentaire.php");

echo '</div>';
include(__DIR__ . '/templates/front/_footer.php');
$response->setContent(ob_get_clean());
$response->send();
