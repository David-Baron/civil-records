<?php

use Symfony\Component\HttpFoundation\RedirectResponse;

require(__DIR__ . '/next/bootstrap.php');
require(__DIR__ . '/next/_COMMUN_env.inc.php'); // Compatibility only

if ($config->get('PUBLIC_LEVEL') < 4 && !$userAuthorizer->isGranted(1)) {
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

$xtyp = $request->get('xtyp', 'A');
$init = $request->get('init', '');
$vue = $request->get('vue', 'T'); // T = Tableau / C = Carte
$xpatr = "";
$page = "";

$JSheader = "";

if ($config->get('SHOW_ALLTYPES') != 1) $xtyp = 'N';

if ($config->get('GEO_MODE_PUBLIC') == 5 || $vue == 'C') { // si pas localité isolée et avec carte

    $geo_haut_carte = $config->get('GEO_HAUT_CARTE', 400);
    $geo_degroupage = $config->get('GEO_ZOOM_DEGROUPAGE', 10);
    $geo_zoom = $config->get('GEO_ZOOM_INITIAL', 0);

    require(__DIR__ . '/tools/GoogleMap/OrienteMap.inc.php');
    require(__DIR__ . '/tools/GoogleMap/Jsmin.php');

    $carto = new GoogleMapAPI();
    $carto->_minify_js = isset($_REQUEST["min"]) ? false : true;
    require(__DIR__ . '/tools/carto_index.php');
    // $carto->addMarkerByAddress("Bievre, Namur","Bièvre", "Texte de la bulle");
    $carto->setMapType("terrain");
    $carto->setTypeControlsStyle("dropdown");
    $carto->setHeight($geo_haut_carte);
    $carto->setWidth("100%");
    $carto->enableClustering();
    $carto->setClusterOptions($geo_degroupage); // plus de cluster au dela de ce niveau de zoom
    $carto->setClusterLocation(__DIR__ . "/tools/GoogleMap/markerclusterer_compiled.js");

    if ($config->get('GEO_CENTRE_CARTE') !== null) {
        $georeq = "SELECT LON,LAT FROM " . $config->get('EA_DB') . "_geoloc WHERE COMMUNE='" . sql_quote($config->get('GEO_CENTRE_CARTE')) . "' AND STATUT IN ('A','M')";
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
open_page("Dépouillement d'actes de l'état-civil et des registres paroissiaux", $root, null, null, $JSheader, '../index.htm', 'rss.php'); ?>
<div class="main">
    <?php $menu_actes = zone_menu(0, $session->get('user', ['level' => 0])['level']); ?>
    <div class="main-col-center text-center">
        <?php navigation($root, 1);

        if (null !== $config->get('AVERTISMT')) {
            echo '<p>' . $config->get('AVERTISMT') . '</p>';
        }
        require(__DIR__ . '/templates/front/_flash-message.php');
        echo '<h2>Communes et paroisses';
        if ($config->get('GEO_MODE_PUBLIC') >= 3 && $config->get('GEO_MODE_PUBLIC') < 5) {
            echo " : ";
            if ($vue == 'C') {
                echo 'Carte | <a href="' . $root . '/index.php?vue=T&xtyp=' . $xtyp . '"' . ($vue == 'T' ? ' class="bolder"' : '') . '>Tableau</a>';
            }

            if ($vue == 'T') {
                echo '<a href="' . $root . '/index.php?vue=C&xtyp=' . $xtyp . '"' . ($vue == 'C' ? ' class="bolder"' : '') . '>Carte</a> | Tableau';
            }
        }
        echo '</h2>';

        if ($config->get('GEO_MODE_PUBLIC') == 5 || $vue == 'C') { // si pas localité isolée et avec carte
            echo '<p><b>' . $menu_actes . '</b></p>';
            //--- Carte
            $carto->printOnLoad();
            $carto->printMap();
            //$carto->printSidebar();
        }

        if ($config->get('GEO_MODE_PUBLIC') == 5 || $vue == 'T') { // si pas localité isolée et avec carte
            // $menu_actes calculé dans le module statistiques
            echo '<p><b>' . $menu_actes . '</b></p>';
            require(__DIR__ . '/tools/tableau_index.php');
        }

        include(__DIR__ . "/templates/front/_commentaire.php"); ?>
    </div>
</div>
</div>
<?php include(__DIR__ . '/templates/front/_footer.php');
$response->setContent(ob_get_clean());
$response->send();
