<?php
define('ADM', 0); // Compatibility only
$admtxt = ''; // Compatibility only
require(__DIR__ . '/next/bootstrap.php');
require(__DIR__ . '/next/_COMMUN_env.inc.php'); // Compatibility only

$xtyp = getparam('xtyp', 'N');
$act = getparam('act');
$init = getparam('init');
$vue = getparam('vue', 'T'); // T = Tableau / C = Carte
$xpatr = "";
$page = "";
$JSheader = "";

if (SHOW_ALLTYPES != 1) {
    $xtyp = 'N';
}

$userlogin = "";
$userlevel = logonok(1);
if ($userlevel == 0) {
    login($root);
}

if ($act == "logout") {
    /* setcookie('userid', "", 0, $root);
    setcookie('md5', "", 0, $root); */
    header("Location: " . $root . "/index.php");
    exit();
}

pathroot($root, $path, $xtyp, $xpatr, $page);

if (GEO_MODE_PUBLIC == 5 or $vue == "C") { // si pas localité isolée et avec carte
    include_once("tools/GoogleMap/OrienteMap.inc.php");
    include_once("tools/GoogleMap/Jsmin.php");

    $carto = new GoogleMapAPI();
    $carto->_minify_js = isset($_REQUEST["min"]) ? false : true;
    include("tools/carto_index.php");
    //$carto->addMarkerByAddress("Bievre, Namur","Bièvre", "Texte de la bulle");
    $carto->setMapType("terrain");
    $carto->setTypeControlsStyle("dropdown");
    if (GEO_HAUT_CARTE == "") {
        $geo_haut_carte = 400;
    } else {
        $geo_haut_carte = GEO_HAUT_CARTE;
    }
    $carto->setHeight($geo_haut_carte);
    $carto->setWidth("100%");
    $carto->enableClustering();
    if (GEO_ZOOM_DEGROUPAGE == "") {
        $geo_degroupage = 10;
    } else {
        $geo_degroupage = GEO_ZOOM_DEGROUPAGE;
    }
    $carto->setClusterOptions($geo_degroupage); // plus de cluster au dela de ce niveau de zoom
    $carto->setClusterLocation("tools/GoogleMap/markerclusterer_compiled.js");
    if (GEO_CENTRE_CARTE <> "") {
        $georeq = "SELECT LON,LAT FROM " . EA_DB . "_geoloc WHERE COMMUNE = '" . sql_quote(GEO_CENTRE_CARTE) . "' AND STATUT IN ('A','M')";
        $geores =  EA_sql_query($georeq);
        if ($geo = EA_sql_fetch_array($geores)) {
            $carto->setCenterCoords($geo['LON'], $geo['LAT']);
        }
    }
    if (GEO_ZOOM_INITIAL == "") {
        $geo_zoom = 0;
    } else {
        $geo_zoom = GEO_ZOOM_INITIAL;
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

$menu_actes = zone_menu(0, 0, array('s' => $vue, 'c' => 'O'));//PUBLIC STAT(retour menu_actes) & CERT

echo '<div id="col_main">';

if (strlen(trim(AVERTISMT)) > 0) {
    if (isin(AVERTISMT, "</p>") > 0) {
        echo AVERTISMT;
    } else {
        echo '<p>' . AVERTISMT . '</p>';
    }
}

echo '<h2>Communes et paroisses';
if (GEO_MODE_PUBLIC >= 3 and GEO_MODE_PUBLIC < 5) {
    $argtyp = "";
    echo " : ";

    if ($xtyp != "") {
        $argtyp = "&amp;xtyp=" . $xtyp;
    }
    
    $href = '<a href="' . $root . '/index.php';
    if ($vue == "C") {
        echo "Carte";
    } else {
        echo '<a href="' . $root . '/index.php?vue=C' . $argtyp . '">Carte</a>';
    }
    echo " | ";
    if ($vue == "T") {
        echo "Tableau";
    } else {
        echo '<a href="' . $root . '/index.php?vue=T' . $argtyp . '">Tableau</a>';
    }
}
echo '</h2>';

if (GEO_MODE_PUBLIC == 5 or $vue == "C") { // si pas localité isolée et avec carte
    echo '<p><b>' . $menu_actes . '</b></p>';
    //--- Carte
    $carto->printOnLoad();
    $carto->printMap();
    //$carto->printSidebar();
}

// --- module principal
if (GEO_MODE_PUBLIC == 5 or $vue == "T") { // si pas localité isolée et avec carte
    // $menu_actes calculé dans le module statistiques
    echo '<p><b>' . $menu_actes . '</b></p>';
    include("tools/tableau_index.php");
}

include(__DIR__ . "/templates/front/_commentaire.php");

echo '</div>';
include(__DIR__ . '/templates/front/_footer.php');
$response->setContent(ob_get_clean());
$response->send();
