<?php

$needed_types = ['N', 'M', 'D', 'V'];
$initiale = '';
$condit1 = '';
$condit2 = '';

if ($xtyp != '' || $xtyp != 'A') {
    $condit1 = " WHERE TYPACT='" . sql_quote($xtyp) . "'";
    $needed_types = [$xtyp];
}

if ($init != '') {
    $initiale = '&init=' . $init;
    $leninit = mb_strlen($init);
    $condit2 = " AND upper(left(COMMUNE," . $leninit . "))='" . sql_quote($init) . "'";
}

// Sélectionner et grouper sur initiale de commune et ascii(initiale), ordonner code ascii ascendant pour avoir + grand code (accentué) en dernier
$sql = "SELECT alphabet.init FROM ( SELECT upper(left(COMMUNE,1)) AS init,ascii(upper(left(COMMUNE,1))) AS oo 
    FROM " . $config->get('EA_DB') . "_sums " . $condit1 . " GROUP BY init,oo  ORDER BY init , oo ASC) AS alphabet GROUP BY init";

$result = EA_sql_query($sql);
$alphabet = "";
while ($row = EA_sql_fetch_row($result)) {
    if ($row[0] == $init) {
        $alphabet .= '<b>' . $row[0] . '</b> ';
    } else {
        $alphabet .= '<a href="' . $root . '/accueil?xtyp=' . $xtyp . '&init=' . $row[0] . '">' . $row[0] . '</a> ';
    }
}
echo '<p align="center">' . $alphabet . '</p>';
echo '<table class="m-auto" summary="Liste des communes avec décompte des actes">';
echo '<tr class="rowheader">';
echo '<th>Localité</th>';
$nbcol = 3;
$cols = 1;  // pour graphique de répartition
if ($userAuthorizer->isGranted(6) || $config->get('SHOW_DATES') == 1) {
    if ($config->get('SHOW_DISTRIBUTION') == 1) {
        $cols = 2;
    }
    echo '<th colspan="' . $cols . '">Période</th>';
    $nbcol++;
}
echo '<th>Actes</th>';
if ($userAuthorizer->isGranted(6)) {
    echo '<th>Datés</th>';
    $nbcol++;
}
echo '<th>Filiatifs</th>';
echo '</tr>';

$nbcol += $cols;
$cptact = 0;
$cptnnul = 0;
$cptfil = 0;

$liste_champs_select = " TYPACT, LIBELLE,COMMUNE,DEPART, min(AN_MIN) R_AN_MIN, max(AN_MAX) R_AN_MAX, sum(NB_FIL) S_NB_FIL, sum(NB_TOT) S_NB_TOT, sum(NB_N_NUL) S_NB_N_NUL ";
$groupby = " GROUP BY TYPACT,LIBELLE,COMMUNE,DEPART ";

foreach ($needed_types as $needed_type) {
    $sql = "SELECT " . $liste_champs_select
        . " FROM " . $config->get('EA_DB') . "_sums "
        . " WHERE typact = '" . sql_quote($needed_type) . "'" . $condit2 . $groupby
        . " ORDER BY LIBELLE,COMMUNE,DEPART; ";
    $pre_libelle = "XXX";
    if ($result = EA_sql_query($sql)) {
        $i = 1;
        while ($ligne = EA_sql_fetch_array($result)) {
            if ($ligne['TYPACT'] . $ligne['LIBELLE'] <> $pre_libelle) {
                $pre_libelle = $ligne['TYPACT'] . $ligne['LIBELLE'];
                $linkdiv = "";
                switch ($needed_type) {
                    case "N":
                        $typel = "Naissances &amp; Baptêmes";
                        $prog = "/actes/naissances";
                        break;
                    case "V":
                        $typel = "Divers : " . $ligne['LIBELLE'];
                        $prog = "/actes/divers";
                        $linkdiv = '&stype=' . $ligne['LIBELLE'];
                        break;
                    case "M":
                        $typel = "Mariages";
                        $prog = "/actes/mariages";
                        break;
                    case "D":
                        $typel = "Décès &amp; Sépultures";
                        $prog = "/actes/deces";
                        break;
                }
                echo '<tr class="rowheader">';
                echo '<th colspan="' . $nbcol . '">' . $typel . '</th>';
                echo '</tr>';
            }
            echo '<tr class="row' . (fmod($i, 2)) . '">';
            echo '<td><a href="' . $root . $prog . '?xcomm=' . $ligne['COMMUNE'] . ' [' . $ligne['DEPART'] . ']' . $linkdiv . '">' . $ligne['COMMUNE'] . '</a>';
            if ($ligne['DEPART'] <> "") {
                echo ' [' . $ligne['DEPART'] . ']';
            }
            echo '</td>';
            $imgtxt = "Distribution par années";
            if ($userAuthorizer->isGranted(6) or $config->get('SHOW_DATES') == 1) {
                if ($config->get('SHOW_DISTRIBUTION') == 1) {
                    echo '<td><a href="' . $root . '/statistiques?comdep=' . urlencode($ligne['COMMUNE'] . ' [' . $ligne['DEPART'] . ']' . $linkdiv) . '&amp;xtyp=' . $needed_type . '"><img src="' . $root . '/img/histo.gif" border="0" alt="' . $imgtxt . '" title="' . $imgtxt . '"></a></td>';
                }
                echo '<td> (' . $ligne['R_AN_MIN'] . '-' . $ligne['R_AN_MAX'] . ') </td>';
            }
            echo '<td> ' . entier($ligne['S_NB_TOT']) . '</td>';
            if ($userAuthorizer->isGranted(6)) {
                echo '<td> ' . entier($ligne['S_NB_N_NUL']) . '</td>';
            }
            echo '<td> ' . entier($ligne['S_NB_FIL']) . '</td>';
            echo '</tr>';
            $cptact = $cptact + $ligne['S_NB_TOT'];
            $cptnnul = $cptnnul + $ligne['S_NB_N_NUL'];
            $cptfil = $cptfil + $ligne['S_NB_FIL'];
            $i++;
        }
    }
}
echo '<tr class="rowheader">';
echo '<td><b>Totaux :</b></td>';
if ($userAuthorizer->isGranted(6) || $config->get('SHOW_DATES') == 1) {
    echo '<td colspan="' . $cols . '">  </td>';
}
echo '<td> ' . entier($cptact) . '</td>';
if ($userAuthorizer->isGranted(6)) {
    echo '<td> ' . entier($cptnnul) . '</td>';
}
echo '<td> ' . entier($cptfil) . '</td>';
echo '</tr>';
echo '</table>';
