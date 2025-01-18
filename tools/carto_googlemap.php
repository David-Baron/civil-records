<?php

use CivilRecords\Domain\StatisticModel;
use CivilRecords\Domain\GeoLocalizationModel;
use Symfony\Component\HttpFoundation\RedirectResponse;

// Tableau en version cartographique pour la page d'accueil

function plot_commune($carto, $depart, $commune, $etiquette, $texte_html, $listTypes = "", $listSigles = "")
{
    global $root, $config;
    $geolocalizationModel = new GeoLocalizationModel();
    $geoloc = $geolocalizationModel->findOneByCriteria(['COMMUNE' => $commune, 'DEPART' => $depart]);
    if ($geoloc) {
        if (strlen($listTypes) == 1) {
            $pin = $config->get('EA_URL_SITE') . $root . '/themes/img/pin_' . $listTypes . ".png";
        } else {
            $pin = $config->get('EA_URL_SITE') . $root . '/themes/img/pin_' . strlen($listTypes) . ".png";
            //$etiquette .= "(".$listSigles.")";
            $etiquette = $listSigles . " : " . $etiquette;
        }
        //$carto->addMarkerByAddress($ligne['COMMUNE'].", ".$ligne['DEPART'],$ligne['COMMUNE'], "");
        //$carto->addMarkerByCoords($geo['LON'],$geo['LAT'],$etiquette,"XX"); //$etiquette,$texte_html);
        $carto->addMarkerByCoords($geoloc['LON'], $geoloc['LAT'], $etiquette, $texte_html, '', $pin);
    }
}

if (!isset($_ENV['GOOGLE_API_KEY'])) {
    /* $response = new RedirectResponse($session->get('previous_url', "$root/"));
    $response->send();
    exit(); */
    throw new \Exception("Google API key is missing", 1);
    
}

$condit1 = '';
$pre_libelle = 'XXX';
$pre_commune = 'XXX';
$txthtml = '';
$listTypes = '';
$listSigles = '';
$pre_type = 'W';

$statisticModel = new StatisticModel();
$statistics = $statisticModel->findAllForMap($xtyp);

include_once(__DIR__ . "/GoogleMap/OrienteMap.inc.php");
include_once(__DIR__ . "/GoogleMap/Jsmin.php");

$geo_haut_carte = $config->get('GEO_HAUT_CARTE', 400);
$geo_degroupage = $config->get('GEO_ZOOM_DEGROUPAGE', 10);
$geo_centre_carte = $config->get('GEO_CENTRE_CARTE', null);
$geo_zoom = $config->get('GEO_ZOOM_INITIAL', 0);

$carto = new GoogleMapAPI();
$carto->_minify_js = isset($_REQUEST["min"]) ? false : true;
include(__DIR__ . "/carto_googlemap.php");
//$carto->addMarkerByAddress("Bievre, Namur","Bièvre", "Texte de la bulle");
$carto->setMapType("terrain");
$carto->setTypeControlsStyle("dropdown");

$carto->setHeight($geo_haut_carte);
$carto->setWidth("100%");
$carto->enableClustering();
$carto->setClusterOptions($geo_degroupage); // plus de cluster au dela de ce niveau de zoom
$carto->setClusterLocation("/modules/googlemap/markerclusterer_compiled.js");
if (null !== $geo_centre_carte) {
    $geolocalizationModel = new GeoLocalizationModel();
    $centered_locality = $geolocalizationModel->findOneByCriteria(['COMMUNE' => $geo_centre_carte]);
    $carto->setCenterCoords($centered_locality['LON'], $centered_locality['LAT']);
}

if ($geo_zoom > 0) {
    $carto->disableZoomEncompass();
    $carto->setZoomLevel($geo_zoom);
}

$javascripts = $carto->getHeaderJS();
$javascripts .= $carto->getMapJS();

$Xanchor = 10;
$Yanchor = 42;
$carto->setMarkerIcon($config->get('EA_URL_SITE') . $root . '/themes/img/pin_N.png', '', $Xanchor, $Yanchor); // défini le décalage du pied de la punaise
$carto->addIcon($config->get('EA_URL_SITE') . $root . "/themes/img/M.png", '', $Xanchor, $Yanchor);
$carto->addIcon($config->get('EA_URL_SITE') . $root . "/themes/img/D.png", '', $Xanchor, $Yanchor);
$carto->addIcon($config->get('EA_URL_SITE') . $root . "/themes/img/V.png", '', $Xanchor, $Yanchor);
$carto->addIcon($config->get('EA_URL_SITE') . $root . "/themes/img/2.png", '', $Xanchor, $Yanchor);
$carto->addIcon($config->get('EA_URL_SITE') . $root . "/themes/img/3.png", '', $Xanchor, $Yanchor);
$carto->addIcon($config->get('EA_URL_SITE') . $root . "/themes/img/4.png", '', $Xanchor, $Yanchor);

foreach ($statistics as $statistic) {
    if ($statistic['DEPART'] != 'XXX' && $statistic['COMMUNE'] != 'XXX') { // nouvelle commune

        $linkdiv = '';
        switch ($statistic['TYPACT']) {
            case "N":
                $typel = "Naissances/Baptêmes";
                $prog = "/actes/naissances";
                $sigle = "°";
                break;
            case "M":
                $typel = 'Mariages';
                $prog = '/actes/mariages';
                $sigle = "x";
                break;
            case "D":
                $typel = 'Décès/Sépultures';
                $prog = '/actes/deces';
                $sigle = "+";
                break;
            case "V":
                if ($statistic['LIBELLE'] == "") {
                    $typel = 'Divers';
                } else {
                    $typel = $statistic['LIBELLE'];
                    $linkdiv = '&linkdiv=' . $statistic['LIBELLE'];
                }
                $prog = '/actes/divers';
                $sigle = "#";
                break;
        }

        $etiquette = $statistic['COMMUNE'] . " [" . $statistic['DEPART'] . "]";
        $txthtml = '<b>' . $etiquette . '</b><br>';
        $href = '<a href="' . $root . $prog . '?xcomm=' . $statistic['COMMUNE'] . ' [' . $statistic['DEPART'] . ']' . $linkdiv . '">';
        $txthtml .= $href . entier($statistic['NB_TOT']) . " " . $typel . "</a><br>";

        plot_commune($statistic['DEPART'], $statistic['COMMUNE'], $etiquette, $txthtml, $statistic['TYPACT'], $sigle);
    }
}
