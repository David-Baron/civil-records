<?php

// Data builder for openstretmap map

use CivilRecords\Model\GeoLocalizationModel;
use CivilRecords\Model\StatisticModel;

/**
 * Return the map pin for one locality
 */
function plot_commune($depart, $commune, $etiquette, $texte_html, $listTypes = "", $sigle = "")
{
    $geolocalizationModel = new GeoLocalizationModel();
    $geoloc = $geolocalizationModel->findOneByCriteria(['COMMUNE' => $commune, 'DEPART' => $depart]);
    if ($geoloc) {
        if (strlen($listTypes) == 1) {
            $pin = $listTypes;
        } else {
            $pin = strlen($listTypes);
            $etiquette = $sigle . " : " . $etiquette;
        }

        return ['LAT' => $geoloc['LAT'], 'LON' => $geoloc['LON'], 'LIBELE' => $etiquette, 'STATS' => $texte_html, 'PIN' => $pin];
    }
}

$statisticModel = new StatisticModel();
$statistics = $statisticModel->findAllForMap($xtyp);

$map_plots = []; // array of stats for map popup [LAT, LON, LIBELE, STATS, PIN]

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

        $map_plots[] = plot_commune($statistic['DEPART'], $statistic['COMMUNE'], $etiquette, $txthtml, $statistic['TYPACT'], $sigle);
    }
}

$map_plots = json_encode($map_plots);

$stylesheets =
    <<<AAA
<link rel="stylesheet" href="$root/modules/leaflet/dist/leaflet.css">
<link rel="stylesheet" href="$root/modules/leaflet.markercluster/dist/MarkerCluster.Default.css">
AAA;
$javascripts =
    <<<AAA
<script src="/modules/leaflet/dist/leaflet.js"></script>
<script src="/modules/leaflet.markercluster/dist/leaflet.markercluster.js"></script>
<script>
        var place = {
            name: 'Paris',
            lat: 48.8521,
            lon: 2.33553
        };

        const icons = [{
                name: 'M',
                path: '$root/themes/img/pin_M.png'
            },
            {
                name: 'N',
                path: '$root/themes/img/pin_N.png'
            },
            {
                name: 'D',
                path: '$root/themes/img/pin_D.png'
            },
            {
                name: 'V',
                path: '$root/themes/img/pin_V.png'
            },
            {
                name: '1',
                path: '$root/themes/img/pin_1.png'
            },
            {
                name: '2',
                path: '$root/themes/img/pin_2.png'
            },
            {
                name: '3',
                path: '$root/themes/img/pin_3.png'
            },
            {
                name: '4',
                path: '$root/themes/img/pin_4.png'
            }
        ];

        const iconSize = [36, 36];
        const iconAnchor = [16, 32];
        const popupAnchor = [-6, -32];
        const mapplots = $map_plots;
        let ui_icons = [];

        // Leaflet only

        // Create pins
        icons.forEach(item => {
            ui_icons[item.name] = L.icon({
                iconUrl: item.path,
                iconSize: iconSize,
                iconAnchor: iconAnchor,
                popupAnchor: popupAnchor
            })
        });

        createMap = () => {

            var carte = L.tileLayer("https://{s}.tile.openstreetmap.fr/osmfr/{z}/{x}/{y}.png", {
                zoom: 2,
                maxZoom: 15,
                minZoom: 2,
                attribution: '&copy; <a href="http://osm.org/copyright">OpenStreetMap</a> contributors'
            });

            var map = L.map("map", {
                center: [46.1696, 1.87145],
                zoom: 4,
                layers: [carte]
            });

            // create cluster
            var markerClusters = L.markerClusterGroup().addTo(map);

            // create markers and add to cluster
            mapplots.forEach(item => {

                var mark = L.marker([item.LAT, item.LON], {
                    icon: ui_icons[item.PIN]
                });
                mark.bindPopup(item.STATS);
                markerClusters.addLayer(mark);
                L.markerClusterGroup().addTo(map);
            });


        };
        window.onload = function() {
            createMap();
        };
    </script>
AAA;
