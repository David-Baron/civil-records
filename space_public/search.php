<?php

use Symfony\Component\HttpFoundation\RedirectResponse;


function sqlcomp($lazone, $valeur)
{
    global $typ_compare, $txtcomp, $codecomp;
    $valeur = sql_quote($valeur);
    switch ($typ_compare) {
        case "E": // Exacte
            $sql = $lazone . " = '" . $valeur . "'";
            if (isin("A", $codecomp) >= 0) {
                $txtcomp = ' est la';
            } else {
                $txtcomp = ' est le';
            }
            break;
        case "D": // Débute par
            $sql = $lazone . " LIKE '" . $valeur . "%'";
            if (isin("A", $codecomp) >= 0) {
                $txtcomp = ' au début de la';
            } else {
                $txtcomp = ' au debut du';
            }
            break;
        case "F": // Fini par
            $sql = $lazone . " LIKE '%" . $valeur . "'";
            if (isin("A", $codecomp) >= 0) {
                $txtcomp = ' à la fin de la';
            } else {
                $txtcomp = ' à la fin du';
            }
            break;
        case "C": // Contient
            $sql = $lazone . " LIKE '%" . $valeur . "%'";
            if (isin("A", $codecomp) >= 0) {
                $txtcomp = ' dans la';
            } else {
                $txtcomp = ' dans le';
            }
            break;
        case "S": // Soundex
            $sql = "soundex(" . $lazone . ") = soundex('" . $valeur . "')";
            if (isin("A", $codecomp) >= 0) {
                $txtcomp = ' sonne comme la';
            } else {
                $txtcomp = ' sonne comme le';
            }
            break;
    }
    return $sql;
}

function makecrit($nom, $pre, $zone, $comp)
{
    global $critN, $critD, $critM, $critM1, $critM2, $mes;  // valeurs mises à jour par la procédure
    global $xtypN, $xtypD, $xtypM;
    global $typ_compare, $txtcomp, $compmode, $codecomp;
    $typ_compare = $comp;
    $txtcomp = "";
    $codecomp = $zone;

    switch ($codecomp) {
        case "1": // recherche directe patronyme des intéressés
            {
                if ($nom != "") {
                    $critX = sqlcomp("NOM", $nom);
                    $critN = sql_and($critN) . "(" . $critX . ")";
                    $critD = sql_and($critD) . "(" . $critX . ")";
                    $critM = sql_and($critM) . "(" . $critX . " or " . sqlcomp("C_NOM", $nom) . ")";
                    $critM1 = sql_and($critM1) . "(" . $critX . ")";
                    $critM2 = sql_and($critM2) . "(" . sqlcomp("C_NOM", $nom) . ")";
                    $mes .= '<li><b>' . $nom . "</b>" . $txtcomp . " patronyme de la personne intéressée</li>\n";
                }
                if ($pre != "") {
                    $critX = sqlcomp("PRE", $pre);
                    $critN = sql_and($critN) . "(" . $critX . ")";
                    $critD = sql_and($critD) . "(" . $critX . ")";
                    $critM = sql_and($critM) . "(" . $critX . " or " . sqlcomp("C_PRE", $pre) . ")";
                    //$critM1 = sql_and($critM1) . "(" . $critX . ")";
                    //$critM2 = sql_and($critM2) . "(" . sqlcomp("C_PRE", $pre) . ")";
                    $mes .= '<li><b>' . $pre . "</b>" . $txtcomp . " prénom de la personne intéressée</li>\n";
                }
                break;
            }
        case "2": // recherche via patronyme de la mère, des témoins, parrains, ...
            {
                if ($nom != "") {
                    $critX = sqlcomp("M_NOM", $nom) . " or " . sqlcomp("T1_NOM", $nom) . " or " . sqlcomp("T2_NOM", $nom);
                    $critN = sql_and($critN) . "(" . $critX . ")";
                    $critD = sql_and($critD) . $critX . " or " . sqlcomp("C_NOM", $nom);
                    $critM = sql_and($critM) . "(" . $critD . " or " . sqlcomp("CM_NOM", $nom) . " or " . sqlcomp("T3_NOM", $nom) . " or " . sqlcomp("T4_NOM", $nom) . ")";
                    // trop de zones pour faire une recherche indexée ici
                    $critD = "(" . $critD . ")";
                    $mes .= '<li><b>' . $nom . "</b> dans le patronyme de la mère, des témoins, du parrain ou de la marraine</li>\n";
                }
                break;
            }
        case "4": // recherche sur le conjoint
            {
                if ($nom != "") {
                    $critX = sqlcomp("C_NOM", $nom);
                    $critD = sql_and($critD) . "(" . $critX . ")";
                    $critM = sql_and($critM) . "(" . $critX . " or " . sqlcomp("EXCON", $nom) . " or " . sqlcomp("C_EXCON", $nom) . ")";
                    // trop de zones pour faire une recherche indexée ici
                    $xtypN = false;
                    $mes .= '<li><b>' . $nom . "</b>" . $txtcomp . " patronyme du (futur/ex) conjoint</li>\n";
                }
                if ($pre != "") {
                    $critX = sqlcomp("C_PRE", $pre);
                    $xtypN = false;
                    $critD = sql_and($critD) . "(" . $critX . ")";
                    $critM = sql_and($critM) . "(" . $critX . ")";
                    $mes .= '<li><b>' . $pre . "</b>" . $txtcomp . " prénom de la (future) épouse</li>\n";
                }
                break;
            }
        case "5": // recherche sur patronyme du père
            {
                if ($nom != "") {
                    $critX = sqlcomp("P_NOM", $nom);
                    $critN = sql_and($critN) . "(" . $critX . ")";
                    $critD = sql_and($critD) . "(" . $critX . ")";
                    $critM = sql_and($critM) . "(" . $critX . " or " . sqlcomp("CP_NOM", $nom) . ")";
                    $critM1 = sql_and($critM1) . "(" . $critX . ")";
                    $critM2 = sql_and($critM2) . "(" . sqlcomp("CP_NOM", $nom) . ")";
                    $mes .= '<li><b>' . $nom . "</b>" . $txtcomp . " patronyme du père</li>\n";
                }
                if ($pre != "") {
                    $critX = sqlcomp("P_PRE", $pre);
                    $critN = sql_and($critN) . "(" . $critX . ")";
                    $critD = sql_and($critD) . "(" . $critX . ")";
                    $critM = sql_and($critM) . "(" . $critX . " or " . sqlcomp("CP_PRE", $pre) . ")";
                    $mes .= '<li><b>' . $pre . "</b>" . $txtcomp . " prénom du père</li>\n";
                }
                break;
            }
        case "6": // recherche sur patronyme de la mère
            {
                if ($nom != "") {
                    $critX = sqlcomp("M_NOM", $nom);
                    $critN = sql_and($critN) . "(" . $critX . ")";
                    $critD = sql_and($critD) . "(" . $critX . ")";
                    $critM = sql_and($critM) . "(" . $critX . " or " . sqlcomp("CM_NOM", $nom) . ")";
                    $critM1 = sql_and($critM1) . "(" . $critX . ")";
                    $critM2 = sql_and($critM2) . "(" . sqlcomp("CM_NOM", $nom) . ")";
                    $mes .= '<li><b>' . $nom . "</b>" . $txtcomp . " patronyme de la mère</li>\n";
                }
                if ($pre != "") {
                    $critX = sqlcomp("M_PRE", $pre);
                    $critN = sql_and($critN) . "(" . $critX . ")";
                    $critD = sql_and($critD) . "(" . $critX . ")";
                    $critM = sql_and($critM) . "(" . $critX . " or " . sqlcomp("CM_PRE", $pre) . ")";
                    $mes .= '<li><b>' . $pre . "</b>" . $txtcomp . " prénom de la mère</li>\n";
                }
                break;
            }
        case "7": // recherche sur les parrains / témoins et commentaires
            {
                if ($nom != "") {
                    $critX = sqlcomp("T1_NOM", $nom) . " or " . sqlcomp("T2_NOM", $nom);
                    $critN = sql_and($critN) . "(" . $critX . ")";
                    $critD = sql_and($critD) . "(" . $critX . ")";
                    $critM = sql_and($critM) . "(" . $critX . " or " . sqlcomp("T3_NOM", $nom) . " or " . sqlcomp("T4_NOM", $nom) . ")";
                    $mes .= '<li><b>' . $nom . "</b>" . $txtcomp . " patronyme des témoins ou des parrains et marraines</li>\n";
                }
                if ($pre != "") {
                    $critX = sqlcomp("T1_PRE", $pre) . " or " . sqlcomp("T2_PRE", $pre);
                    $critN = sql_and($critN) . "(" . $critX . ")";
                    $critD = sql_and($critD) . "(" . $critX . ")";
                    $critM = sql_and($critM) . "(" . $critX . " or " . sqlcomp("T3_PRE", $pre) . " or " . sqlcomp("T4_PRE", $pre) . ")";
                    $mes .= '<li><b>' . $pre . "</b>" . $txtcomp . " prénom des témoins ou des parrains et marraines</li>\n";
                }
                break;
            }
        case "8": // recherche sur les commentaires
            {
                if ($nom != "") {
                    $critX = sqlcomp("COM", $nom) . " or " . sqlcomp("P_COM", $nom) . " or " . sqlcomp("M_COM", $nom)
                        . " or " . sqlcomp("T1_COM", $nom) . " or " . sqlcomp("T2_COM", $nom) . " or " . sqlcomp("COMGEN", $nom);
                    $critN = sql_and($critN) . "(" . $critX . ")";
                    $critD = sql_and($critD) . "(" . $critX . " or " . sqlcomp("C_COM", $nom) . ")";
                    $critM = sql_and($critM) . "(" . $critX . " or " . sqlcomp("C_COM", $nom) . " or " . sqlcomp("CP_COM", $nom) . " or " . sqlcomp("CM_COM", $nom)
                        . " or " . sqlcomp("T3_COM", $nom) . " or " . sqlcomp("T4_COM", $nom) . ")";
                    $mes .= '<li><b>' . $nom . "</b>" . $txtcomp . " texte des commentaires personnels et généraux</li>\n";
                }
                break;
            }
        case "9": // recherche sur les origines
            {
                if ($nom != "") {
                    $critN = ""; // pas d'origine dans les naissances
                    $xtypN = false;
                    $critX = sqlcomp("ORI", $nom);
                    $critD = sql_and($critD) . "(" . $critX . ")";
                    $critM = sql_and($critM) . "(" . $critX . " or " . sqlcomp("C_ORI", $nom) . ")";
                    $critM1 = sql_and($critM1) . "(" . $critX . ")";
                    $critM2 = sql_and($critM2) . "(" . sqlcomp("C_ORI", $nom) . ")";
                    $mes .= '<li><b>' . $nom . "</b>" . $txtcomp . " lieu d'origine d'un des intéressés</li>\n";
                }
                break;
            }
        case "A": // recherche sur les professions
            {
                if ($nom != "") {
                    $critX = sqlcomp("P_PRO", $nom) . " or " . sqlcomp("M_PRO", $nom);
                    $critN = sql_and($critN) . "(" . $critX . ")";
                    $critD = sql_and($critD) . "(" . $critX . " or " . sqlcomp("PRO", $nom) . ")";
                    $critM = sql_and($critM) . "(" . $critX . " or " . sqlcomp("PRO", $nom)
                        . " or " . sqlcomp("C_PRO", $nom) . " or " . sqlcomp("CP_PRO", $nom) . " or " . sqlcomp("CM_PRO", $nom) . ")";
                    $mes .= '<li><b>' . $nom . "</b>" . $txtcomp . " profession (intéressé et parents)</li>\n";
                }
                break;
            }
    }
}

if ($request->getMethod() != 'POST') {
    $response = new RedirectResponse("$root/");
    $response->send();
    exit();
}

if (current_user_solde() == 0 && $config->get('RECH_ZERO_PTS') == 0) {
    $session->getFlashBag()->add('warning', 'Recherche non autorisée car votre solde de points est épuisé!');
    $response = new RedirectResponse("$root/");
    $response->send();
    exit();
}

$T0 = time();
$MT0 = microtime_float();
$txtcomp = '';
$pagination = '';

$search_modes = [
    1 => ['code' => 'E', 'libele' => 'Exacte'],
    2 => ['code' => 'D', 'libele' => 'au Début'],
    3 => ['code' => 'F', 'libele' => 'à la Fin'],
    4 => ['code' => 'C', 'libele' => 'est Compris dans'],
    5 => ['code' => 'S', 'libele' => 'Sonore']
];

$xach  = $request->request->get('achercher');
$xzone = $request->request->get('zone');
$xpre  = $request->request->get('prenom');
$xach2 = $request->request->get('achercher2');
$xzone2 = $request->request->get('zone2');
$xpre2 = $request->request->get('prenom2');
$xach3 = $request->request->get('achercher3');
$xzone3 = $request->request->get('zone3');
$xtyps = $request->request->get('TypNDMV');
if ($xtyps == "") { // plusieurs types possibles
    $xtypN = ($request->request->get('TypN') == 'N');
    $xtypD = ($request->request->get('TypD') == 'D');
    $xtypM = ($request->request->get('TypM') == 'M');
    $xtypV = ($request->request->get('TypV') == 'V');
} else { // un type à la fois
    $xtypN = ($xtyps == 'N');
    $xtypD = ($xtyps == 'D');
    $xtypM = ($xtyps == 'M');
    $xtypV = ($xtyps == 'V');
}

if ($request->request->get('direct') == 1) {  // ***** recherche directe ****
    $xcomp = $search_modes[$config->get('RECH_DEF_TYP')]['code'];
    $xcomm = "***";  // toutes
    $comdep = $xcomm;
    if ($config->get('CHERCH_TS_TYP') == 1) {
        $xtypN = true;
        $xtypD = true;
        $xtypM = true;
        $xtypV = true;
    } else {
        $xtypN = ($request->request->get('typact') == "N");
        $xtypD = ($request->request->get('typact') == "D");
        $xtypM = ($request->request->get('typact') == "M");
        $xtypV = ($request->request->get('typact') == "V");
    }
};

$xtdiv = $request->request->get('typdivers');
$xmin  = $request->request->get('amin');
$xmax  = $request->request->get('amax');
$xcomp = $request->request->get('comp');
$xcomp2 = $request->request->get('comp2');
$xcomp3 = $request->request->get('comp3');

$comdep  = html_entity_decode($request->request->get('ComDep'), ENTITY_REPLACE_FLAGS, ENTITY_CHARSET);
$xcomm = communede($comdep);
$xdepa  = departementde($comdep);

$xord  = getparam('xord', 'D'); // N = Nom, D = dates
$page  = getparam('page', 1);

$compmode = "I"; // Indexée par défaut mais ...
if (
    ((isin("FCS", $xcomp) >= 0  || isin("2478", $xzone) >= 0) && !empty($xach))
    || ((isin("FCS", $xcomp2) >= 0 || isin("2478", $xzone2) >= 0) && !empty($xach2))
    || ((isin("FCS", $xcomp3) >= 0 || isin("2478A", $xzone3) >= 0) && !empty($xach3))
    || ($xpre . $xpre2 <> "")
) {
    $compmode = "F";
}

ob_start();
open_page("Recherches dans les tables", $root); ?>
<div class="main">
    <?php zone_menu(0, $session->get('user', ['level' => 0])['level']);

    $nav = "";
    if ($xcomp != "") {
        $nav = '<a href="' . $root . '/recherche_avancee">Recherche (avancée)</a> &gt; ';
    }
    echo '<div class="main-col-center text-center">';
    navigation($root, 2, 'A', $nav . "Résultats de la recherche");

    echo '<h2>Résultats de la recherche</h2>';

    // Résultats de la recherche
    $critN = '';
    $critD = '';
    $critM = '';   // critère mariage/divers pour rech full scan
    $critM1 = '';  // critère mariage/divers pour rech indexée sur 1er intéressé  ( car le "or" bloque l'indexé)
    $critM2 = '';  // critère mariage/divers pour rech indexée sur 2d intéressé
    $critV = '';
    $mes = '';

    if ($xach . $xpre == "") {
        $xzone = "";
    }   // zone1 pas utilisé
    if ($xach2 . $xpre2 == "") {
        $xzone2 = "";
    }  // zone2 pas utilisé
    if ($xach3 == "") {
        $xzone3 = "";
    }  // zone3 pas utilisé

    if ((strlen(trim($xach . $xpre . $xach2 . $xpre2 . $xach3)) < $config->get('RECH_MIN')) and ($session->get('user')['level'] < 8)) {
        msg('La recherche doit porter sur au moins ' . $config->get('RECH_MIN') . ' caractères non blancs.');
    } elseif (!($xtypN or $xtypD or $xtypM or $xtypV)) {
        msg('La recherche doit porter sur au moins un des types d\'actes.');
    } elseif (strpos("X" . $xach . $xpre . $xach2 . $xpre2 . $xach3, '%') > 0 or strpos("X" . $xach . $xpre . $xach2 . $xpre2 . $xach3, '__') > 0) {
        msg('La recherche ne peut contenir les caractères "%" ou "__".');
    } elseif (($xzone == 4 or $xzone2 == 4) and $xtypN and !($xtypD or $xtypM or $xtypV)) {
        msg('Pas de "Conjoint" dans les actes de naissance.');
    } elseif ($xzone3 == 9 and $xtypN and !($xtypD or $xtypM or $xtypV)) {
        msg('Pas de zone "Origine" dans les actes de naissance.');
    } else {
        makecrit($xach, $xpre, $xzone, $xcomp);      // génération critère 1ere personne
        makecrit($xach2, $xpre2, $xzone2, $xcomp2);  // génération critère 2ème personne
        makecrit($xach3, "", $xzone3, $xcomp3);      // génération critère autres élements
        if ($xmin != "") {
            $critX = " (year(LADATE)>= " . $xmin . ")";
            $critN = sql_and($critN) . $critX;
            $critD = sql_and($critD) . $critX;
            $critM = sql_and($critM) . $critX;
            $critM1 = sql_and($critM1) . $critX;
            $critM2 = sql_and($critM2) . $critX;
            if ($xmax == "") {
                $mes .= '<li>Années égales ou postérieures à <b>' . $xmin . "</b></li>\n";
            }
        }
        if ($xmax != "") {
            $critX = " (year(LADATE)<= " . $xmax . ")";
            $critN = sql_and($critN) . $critX;
            $critD = sql_and($critD) . $critX;
            $critM = sql_and($critM) . $critX;
            $critM1 = sql_and($critM1) . $critX;
            $critM2 = sql_and($critM2) . $critX;
            if ($xmin == "") {
                $mes .= '<li>Années antérieures ou égales à <b>' . $xmax . "</b></li>\n";
            } else {
                $mes .= '<li>Années comprises entre <b>' . $xmin . '</b> et <b>' . $xmax . "</b></li>\n";
            }
        }
        if (mb_substr($xcomm, 0, 2) != "**") {
            $critX = " (COMMUNE = '" . sql_quote($xcomm) . "' and DEPART= '" . sql_quote($xdepa) . "') ";
            $critN = sql_and($critN) . $critX;
            $critD = sql_and($critD) . $critX;
            $critM = sql_and($critM) . $critX;
            $critM1 = sql_and($critM1) . $critX;
            $critM2 = sql_and($critM2) . $critX;
            $mes .= '<li>Commune ou paroisse de <b>' . $xcomm . " [" . $xdepa . "]</b></li>\n";
        }
    }
    $critV = $critM;
    $critV1 = $critM1;
    $critV2 = $critM2;
    if (($xtypV) and ($xtdiv <> "") and (mb_substr($xtdiv, 0, 2) <> "**")) {
        if (!empty($critV)) {
            $critV  = sql_and($critV) . " (LIBELLE='" . sql_quote(urldecode($xtdiv)) . "')";
        }
        if (!empty($critV1)) {
            $critV1 = sql_and($critV1) . " (LIBELLE='" . sql_quote(urldecode($xtdiv)) . "')";
        }
        if (!empty($critV2)) {
            $critV2 = sql_and($critV2) . " (LIBELLE='" . sql_quote(urldecode($xtdiv)) . "')";
        }
        $mes .= '<li>Actes divers de type <b>' . $xtdiv . "</b></li>";
    }

    if (trim($critN . $critM . $critD) == "") {
        msg('Aucun critère de recherche n\'a été spécifié.');
    } else {
        $sql = "";
        $listactes = "";
        $listtyps = "";
        $listcrit = "";
        if ($xtypM) {  // M en premier pour taille zones C_NOM et C_PRE
            $listzones = "ID, TYPACT, DATETXT, COMMUNE, NOM, PRE, C_NOM, C_PRE, LADATE, 'Mariage' AS LIBELLE ";
            if ($compmode == "F") {  // full scan
                $sql .= "(SELECT " . $listzones
                    . " FROM " . $config->get('EA_DB') . "_mar3 "
                    . " WHERE  " . $critM . ") ";
            } else { // indexed
                $sql .= "(SELECT " . $listzones
                    . " FROM " . $config->get('EA_DB') . "_mar3 "
                    . " WHERE  " . $critM1 . ") ";
                $sql .= ' union ';
                $sql .= "(SELECT " . $listzones
                    . " FROM " . $config->get('EA_DB') . "_mar3 "
                    . " WHERE  " . $critM2 . ") ";
            }
            $listactes = "mariages";
            $listtyps .= "N";
        }
        if ($xtypV) {
            if (strlen($sql) > 0) {
                $sql .= ' union ';
            }
            $listzones = "ID, TYPACT, DATETXT, COMMUNE, NOM, PRE, C_NOM, C_PRE, LADATE, LIBELLE ";
            if ($compmode == "F") {
                $sql .= "(SELECT " . $listzones
                    . " FROM " . $config->get('EA_DB') . "_div3 "
                    . " WHERE  " . $critV . ") ";
            } else {
                $sql .= "(SELECT " . $listzones
                    . " FROM " . $config->get('EA_DB') . "_div3 "
                    . " WHERE  " . $critV1 . ") ";
                $sql .= ' union ';
                $sql .= "(SELECT " . $listzones
                    . " FROM " . $config->get('EA_DB') . "_div3 "
                    . " WHERE  " . $critV2 . ") ";
            }
            if (strlen($listactes) > 0) {
                $listactes .= ", ";
            }
            $listactes .= "types divers";
            $listtyps .= "N";
        }
        if ($xtypD) {
            if (strlen($sql) > 0) {
                $sql .= ' union ';
            }
            $sql .= "(SELECT ID, TYPACT, DATETXT, COMMUNE, NOM, PRE, 'X' AS C_NOM, 'Y' AS C_PRE, LADATE,'Décès' AS LIBELLE "
                . " FROM " . $config->get('EA_DB') . "_dec3 "
                . " WHERE  " . $critD . ") ";
            if (strlen($listactes) > 0) {
                $listactes = ", " . $listactes;
            }
            $listactes = "décès" . $listactes;
            $listtyps .= "N";
        }
        if ($xtypN and !empty($critN)) {
            if (strlen($sql) > 0) {
                $sql .= ' union ';
            }
            $sql .= "(SELECT ID, TYPACT, DATETXT, COMMUNE, NOM, PRE, 'X' AS C_NOM, 'Y' AS C_PRE, LADATE,'Naissance' AS LIBELLE "
                . " FROM " . $config->get('EA_DB') . "_nai3 "
                . " WHERE  " . $critN . ") ";
            if (strlen($listactes) > 0) {
                $listactes = ", " . $listactes;
            }
            $listactes = "naissances" . $listactes;
            $listtyps .= "N";
        }
        $sql .= " ORDER BY LADATE";
        $listactes = "<li>Actes de " . $listactes . "</li>\n";
        $reqbigs = "set sql_big_selects=1";
        $resbase = EA_sql_query($reqbigs);
        
        $result = EA_sql_query($sql);
        $nbtot = EA_sql_num_rows($result);

        $baselink = $path . '/recherche?achercher=' . $xach . '&amp;zone=' . $xzone . '&amp;prenom=' . $xpre . '&amp;comp=' . $xcomp;
        $baselink .= '&amp;achercher2=' . $xach2 . '&amp;zone2=' . $xzone2 . '&amp;prenom2=' . $xpre2 . '&amp;comp2=' . $xcomp2;
        $baselink .= '&amp;achercher3=' . $xach3 . '&amp;zone3=' . $xzone3 . '&amp;comp3=' . $xcomp3;
        $baselink .= iif($xtypN, '&amp;TypN=N', '') . iif($xtypD, '&amp;TypD=D', '') . iif($xtypM, '&amp;TypM=M', '') . iif($xtypV, '&amp;TypV=V', '');
        $baselink .= '&amp;typdivers=' . urlencode($xtdiv) . '&amp;ComDep=' . urlencode($comdep);
        $baselink .= '&amp;amin=' . $xmin . '&amp;amax=' . $xmax;

        $limit = "";
        if ($limit <> "") {
            $sql = $sql . $limit;
            $result = EA_sql_query($sql);
            $nb = EA_sql_num_rows($result);
        } else {
            $nb = $nbtot;
        }

        $pagination = pagination($nbtot, $page, $baselink, $pagination, $limit);
        
        echo '<div class="critrech">Recherche de : <ul>' . $mes . $listactes . '</ul></div>';

        if ($nb > 0) {
            $i = ($page - 1) * $config->get('MAX_PAGE') + 1;
            echo '<p><b>' . $nbtot . ' actes trouvés</b></p>';
            echo '<p>' . $pagination . '</p>';
            echo '<table class="m-auto" summary="Liste des résultats">';
            echo '<tr class="rowheader">';
            echo '<th></th>';
            echo '<th>Type</th>';
            echo '<th>Date</th>';
            echo '<th>Intéressé(e)</th>';
            echo '<th>Commune/Paroisse</th>';
            echo '</tr>';
            while ($ligne = EA_sql_fetch_row($result)) {
                switch ($ligne[1]) {
                    case "N":
                        $url = $root . '/actes/naissances/acte_details';
                        break;
                    case "D":
                        $url = $root . '/actes/deces/acte_details';
                        break;
                    case "M":
                        $url = $root . '/actes/mariages/acte_details';
                        break;
                    case "V":
                        $url = $root . '/actes/divers/acte_details';
                        break;
                }
                echo '<tr class="row' . (fmod($i, 2)) . '">';
                echo '<td>' . $i . '. </td>';
                echo '<td>' . $ligne[9] . ' </td>';
                echo '<td>&nbsp;' . annee_seulement($ligne[2]) . '&nbsp;</td>';
                $EA_url = '<a href="' . $url . '?xid=' . $ligne[0] . '&amp;xct=' . ctrlxid($ligne[4], $ligne[5]) . '">';
                echo '<td>' . $EA_url . $ligne[4] . ' ' . $ligne[5] . '</a>';
                if ($ligne[1] == 'M' or ($ligne[1] == 'V' and $ligne[6] <> '')) {
                    echo ' x ' . $EA_url . $ligne[6] . ' ' . $ligne[7] . '</a>';
                }
                echo '</td>';
                echo '<td>' . $ligne[3] . '</td>';
                echo '</tr>';
                $i++;
            }
            echo '</table>';
            echo '<p>' . $pagination . '</p>';
        } else {
            echo '<p> Aucun acte trouvé </p>';
        }
    }
    echo '<p>Durée du traitement  : ' . round(microtime_float() - $MT0, 3) . ' sec.</p>';
    echo '</div>';
    echo '</div>';
    include(__DIR__ . '/templates/front/_footer.php');
    return (ob_get_clean());
