<?php

use Symfony\Component\HttpFoundation\RedirectResponse;

require(__DIR__ . '/../next/bootstrap.php');
include(__DIR__ . '/../next/_COMMUN_env.inc.php'); // Compatibility only

$lvl = 2;
if (ADM == 10) $lvl = 5;

if (!$userAuthorizer->isGranted($lvl)) {
    $session->getFlashBag()->add('warning', 'Vous n\'êtes pas connecté ou vous n\'avez pas les autorisations nécessaires!');
    $response = new RedirectResponse("$root/");
    $response->send();
    exit();
}

function barre($valeur, $max)
{
    $lgmax = 100;
    $chaine = "";
    $long = $valeur / $max * $lgmax;
    $chaine  = '<div class="histo"><strong class="barre" style="width:' . $long . '%;">' . $valeur . '</strong></div>';
    return $chaine;
}

$xcomm = $xpatr = $page = "";
$missingargs = false;
$oktype = false;
$TypeActes  = getparam('xtyp');
$xtdiv      = getparam('tdiv');
$comdep  = html_entity_decode(getparam('comdep'), ENTITY_REPLACE_FLAGS, ENTITY_CHARSET);
$Commune = communede($comdep);
$Depart  = departementde($comdep);

pathroot($root, $path, $xcomm, $xpatr, $page);

// Données postées
if (empty($TypeActes)) {
    msg('Vous devez préciser le type des actes.');
    $missingargs = true;
}
if (empty($Commune)) {
    msg('Vous devez préciser une commune.');
    $missingargs = true;
}
if (! $missingargs) {
    $oktype = true;
    $condtdiv = "";
    $soustype = "";
    $linkdiv = "";
    switch ($TypeActes) {
        case "N":
            $ntype = "naissance";
            $table = $config->get('EA_DB') . "_nai3";
            $program = "tab_naiss.php";
            break;
        case "V":
            $ntype = "types divers";
            $table = $config->get('EA_DB') . "_div3";
            $program = "tab_bans.php";
            $pos = isin($comdep, "];");
            if (($pos > 0)) {
                $Depart  = departementde(mb_substr($comdep, 1, $pos));
                $stype = mb_substr($comdep, $pos + 2);
                $condtdiv = " AND (LIBELLE='" . sql_quote($stype) . "')";
                $soustype = " (" . $stype . ")";
                $linkdiv = ";" . $stype;
            }
            break;
        case "M":
            $ntype = "mariage";
            $table = $config->get('EA_DB') . "_mar3";
            $program = "tab_mari.php";
            break;
        case "D":
            $ntype = "décès";
            $table = $config->get('EA_DB') . "_dec3";
            $program = "tab_deces.php";
            break;
    }
    $xcomm = $Commune . ' [' . $Depart . ']' . $linkdiv;;

    $title = $Commune . " : Répartition des actes de " . $ntype . $soustype;

    ob_start();
    open_page($title, $root); ?>
    <div class="main">
        <?php zone_menu(ADM, $session->get('user', ['level' => 0])['level']); ?>
        <div class="main-col-center text-center">
        <?php
        if (ADM < 10) {
            navigation($root, ADM + 2, 'A', $Commune);
        } else {
            navadmin($root, $title);
        }

        echo '<h2>' . $title . '</h2>';

        $sql = "SELECT year(ladate) AS ANNEE,count(*) AS CPT FROM " . $table .
            " WHERE COMMUNE='" . sql_quote($Commune) . "' AND DEPART='" . sql_quote($Depart) . "'" . $condtdiv . " GROUP BY year(ladate) ;";
        $result = EA_sql_query($sql);
        $k = 0;
        $annee = array(0);
        $cptan = array(0);
        $max = 0;
        while ($ligne = EA_sql_fetch_array($result)) {
            $k++;
            $annee[$k] = $ligne['ANNEE'];
            $cptan[$k] = $ligne['CPT'];
            if ($cptan[$k] > $max) {
                $max = $cptan[$k];
            }
        }
        $nban = $k;
        $annee_limite_coherence = 1010;

        echo '<table class="m-auto">';
        echo "<tr><th>Années</th><th>Nombres d'actes</th></tr>";
        for ($k = 1; $k <= $nban; $k++) {
            //echo $k."-".$annee[$k]."-".$cptan[$k];
            if ($annee[$k] <= $annee_limite_coherence) {
                echo '<tr>';
                echo '<td><b><a href="' . mkurl($path . '/' . $program, $xcomm, '!' . $annee[$k]) . '">Improbable</a></b></td>';
                echo '<td>' . barre($cptan[$k], $max) . '</td>';
                echo '</tr>';
                continue;
            } elseif ($annee[$k] > $annee[$k - 1] + 3 && $annee[$k - 1] > $annee_limite_coherence) {
                echo '<tr><td>...</td><td></td></tr>';
                echo '<tr><td>' . ($annee[$k] - $annee[$k - 1] - 1) . ' années</td><td></td></tr>';
                echo '<tr><td>...</td><td></td></tr>';
            } elseif ($annee[$k] > $annee[$k - 1] + 1 && $annee[$k - 1] > $annee_limite_coherence) {
                for ($kk = 1; $kk <= ($annee[$k] - $annee[$k - 1] - 1); $kk++) {
                    echo '<tr>';
                    $anneezero = ($annee[$k - 1] + $kk);
                    if ($anneezero % 10 == 0) {
                        echo '<td><b>' . $anneezero . '</b></td>';
                    } else {
                        echo '<td>' . $anneezero . '</td>';
                    }
                    //echo '<tr><td>'.($annee[$k-1]+$kk).'</td>';
                    echo '<td>' . barre(0, $max) . '</td><td></td></tr>';
                }
            }
            echo '<tr>';
            $link = '<a href="' . mkurl($path . '/' . $program, $xcomm, '!' . $annee[$k]) . '">' . $annee[$k] . '</a>';
            if ($annee[$k] % 10 == 0) {
                echo '<td><b>' . $link . '</b></td>';
            } else {
                echo '<td>' . $link . '</td>';
            }
            echo '<td>' . barre($cptan[$k], $max) . '</td>';
            echo '</tr>';
        }
        echo '</table>';
    }
    echo '</div>';
    echo '</div>';
    include(__DIR__ . '/../templates/front/_footer.php');
    $response->setContent(ob_get_clean());
    $response->send();
