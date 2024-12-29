<?php

// Data builder for openstretmap map

function plot_commune($depart, $commune, $etiquette, $texte_html, $listTypes = "", $listSigles = "")
{
    global $root, $config;
    $georeq = "SELECT LON,LAT FROM " . $config->get('EA_DB') . "_geoloc WHERE COMMUNE = '" . sql_quote($commune) . "' AND DEPART = '" . sql_quote($depart) . "'";
    $geores =  EA_sql_query($georeq);
    if ($geo = EA_sql_fetch_array($geores)) {
        if (strlen($listTypes) == 1) {
            // $pin = $root . '/assets/img/pin_' . $listTypes . ".png";
            $pin = $listTypes;
        } else {
            // $pin = $root . '/assets/img/pin_' . strlen($listTypes) . ".png";
            $pin = strlen($listTypes);
            //$etiquette .= "(".$listSigles.")";
            $etiquette = $listSigles . " : " . $etiquette;
        }
        // $carto->addMarkerByAddress($ligne['COMMUNE'].", ".$ligne['DEPART'],$ligne['COMMUNE'], "");
        // $carto->addMarkerByCoords($geo['LON'],$geo['LAT'],$etiquette,"XX"); //$etiquette,$texte_html);
        // $carto->addMarkerByCoords($geo['LON'], $geo['LAT'], $etiquette, $texte_html, '', $pin);
        return ['LAT' => $geo['LAT'], 'LON' => $geo['LON'], 'LIBELE' => $etiquette, 'STATS' => $texte_html, 'PIN' => $pin];
    }
}


$sql_params = '';
if ($xtyp != 'A') {
    $sql_params = " WHERE TYPACT='" . $xtyp . "'";
}

$sql = "SELECT DEPART,COMMUNE,TYPACT,LIBELLE, sum(NB_TOT) AS NB_TOT 
    FROM " . $config->get('EA_DB') . "_sums " . $sql_params . " 
    GROUP BY DEPART,COMMUNE,TYPACT,LIBELLE 
    ORDER BY DEPART,COMMUNE,INSTR('NMDV',TYPACT),LIBELLE; ";

$pre_libelle = "XXX";
$pre_commune = "XXX";
$txthtml = '';
$listTypes = '';
$listSigles = '';
$pre_type = "W";
$map_plots = []; // array of stats for map popup [LAT, LON, LIBELE, STATS, PIN]



if ($result = EA_sql_query($sql)) {
    $i = 1;
    while ($ligne = EA_sql_fetch_array($result)) {
        $i++;
        if ($ligne['DEPART'] . $ligne['COMMUNE'] <> $pre_commune) { // nouvelle commune
            if ($pre_commune <> "XXX") {
                $map_plots[] = plot_commune($depart, $commune, $etiquette, $txthtml, $listTypes, $listSigles);
                $listTypes = '';
                $listSigles = '';
                $pre_type = "W";
            }
            $depart = $ligne['DEPART'];
            $commune = $ligne['COMMUNE'];
            $pre_commune = $depart . $commune;
            $etiquette = $commune . " [" . $depart . "]";
            $txthtml = "<b>" . $commune . " [" . $depart . "]</b><br>";
        }
        $linkdiv = '';
        switch ($ligne['TYPACT']) {
            case "N":
                $typel = "Naissances/Baptêmes";
                $prog = "/tab_naiss.php";
                $sigle = "°";
                break;
            case "M":
                $typel = "Mariages";
                $prog = "/tab_mari.php";
                $sigle = "x";
                break;
            case "D":
                $typel = "Décès/Sépultures";
                $prog = "/tab_deces.php";
                $sigle = "+";
                break;
            case "V":
                if ($ligne['LIBELLE'] == "") {
                    $typel = "Divers";
                } else {
                    $typel = $ligne['LIBELLE'];
                    $linkdiv = '&linkdiv=' . $ligne['LIBELLE'];
                }
                $prog = "/tab_bans.php";
                $sigle = "#";
                break;
        }
        if ($pre_type <> $ligne['TYPACT']) {
            $listTypes .= $ligne['TYPACT'];
            $pre_type = $ligne['TYPACT'];
            $listSigles .= $sigle;
        }
        $href = '<a href="' . $root . $prog . '?xcomm=' . $ligne['COMMUNE'] . ' [' . $ligne['DEPART'] . ']' . $linkdiv . '">';
        $txthtml .= $href . entier($ligne['NB_TOT']) . " " . $typel . "</a><br>";
    }
    if ($pre_commune <> "XXX") {
        $map_plots[] = plot_commune($depart, $commune, $etiquette, $txthtml, $listTypes, $listSigles);
    }
}

$map_plots = json_encode($map_plots);

$stylesheets =
    <<<AAA
<link rel="stylesheet" href="$root/assets/modules/leaflet/dist/leaflet.css">
<link rel="stylesheet" href="$root/assets/modules/leaflet.markercluster/dist/MarkerCluster.Default.css">
AAA;
$javascripts =
    <<<AAA
<script src="/assets/modules/leaflet/dist/leaflet.js"></script>
<script src="/assets/modules/leaflet.markercluster/dist/leaflet.markercluster.js"></script>
<script>
        var place = {
            name: 'Paris',
            lat: 48.8521,
            lon: 2.33553
        };

        const icons = [{
                name: 'M',
                path: '$root/assets/img/pin_M.png'
            },
            {
                name: 'N',
                path: '$root/assets/img/pin_N.png'
            },
            {
                name: 'D',
                path: '$root/assets/img/pin_D.png'
            },
            {
                name: 'V',
                path: '$root/assets/img/pin_V.png'
            },
            {
                name: '1',
                path: '$root/assets/img/pin_1.png'
            },
            {
                name: '2',
                path: '$root/assets/img/pin_2.png'
            },
            {
                name: '3',
                path: '$root/assets/img/pin_3.png'
            },
            {
                name: '4',
                path: '$root/assets/img/pin_4.png'
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
