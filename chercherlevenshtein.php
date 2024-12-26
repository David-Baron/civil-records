<?php

use Symfony\Component\HttpFoundation\RedirectResponse;

require(__DIR__ . '/next/bootstrap.php');
require(__DIR__ . '/next/_COMMUN_env.inc.php'); // Compatibility only
include(__DIR__ . '/tools/cree_table_levenshtein.php');
include(__DIR__ . '/tools/traite_tables_levenshtein.php');

//---------------------------------------------------------
function makecritjlc($xmin, $xmax, $xcomm, $xdepa, $pre, $c_pre, $xpre, $xc_pre) //
{
    $crit = "";
    if ((int) $xmin >= 1001) {
        // $crit = " (year(LADATE)>= " . $xmin.")";
        $crit = " (LADATE > '" . ((int) $xmin - 1) . "-12-31')";
    }
    if ((int) $xmax >= 1001) {
        // $critx = " (year(LADATE)<= " . $xmax.")";
        $critx = " (LADATE < '" . ((int) $xmax + 1) . "-01-01')";   // Se base sur 1er de l'année suivante pour avoir aussi les dates bizarres
        $crit = sql_and($crit) . $critx;
    }
    if (mb_substr($xcomm, 0, 2) != "**") {
        $critx = " (COMMUNE = '" . sql_quote($xcomm) . "' and DEPART= '" . sql_quote($xdepa) . "')";
        $crit = sql_and($crit) . $critx;
    }
    $critx = "(" . $pre . "  LIKE '" . $xpre . "%')  ";
    $crit = sql_and($crit) . $critx;
    if ($c_pre != "") {
        $critx = "(" . $c_pre . "  LIKE '" . $xc_pre . "%')  ";
        $crit = sql_and($crit) . $critx;
    }

    return $crit;
}

function cree_table_temp_sup($nom, $original)
{
    global $config;

    $sql = "CREATE TEMPORARY TABLE " . $config->get('EA_DB') . "_" . $nom . " LIKE " . $config->get('EA_DB') . "_" . $original . ";";
    $result = EA_sql_query($sql) or die('Erreur SQL duplication !' . $sql . '<br>' . EA_sql_error());
    $sql = "INSERT INTO " . $config->get('EA_DB') . "_" . $nom . " SELECT * FROM " . $config->get('EA_DB') . "_" . $original . ";";
    $result = EA_sql_query($sql) or die('Erreur SQL recopie !' . $sql . '<br>' . EA_sql_error());

    return "ok";
}

//--------------------------------------------------------

if (!$userAuthorizer->isGranted($config->get('LEVEL_LEVENSHTEIN'))) {
    $response = new RedirectResponse("$root/");
    $response->send();
    exit();
}

// récupération d l'adresse IP et substition de "_" aux "." pour créer les tables temporaires
// Modifié pour tenir compte des adresses IPV6
$orig = array('.', ':');
$repl = array('_', '_');
$ip_adr_trait = 'tmp_lev_' . str_replace($orig, $repl, getenv("REMOTE_ADDR"));

$SECURITE_TIME_OUT_PHP = 5;
$cherch_ts_typ = 1;
$rech_min = 3;
if (defined("SECURITE_TIME_OUT_PHP")) $SECURITE_TIME_OUT_PHP = $config->get('SECURITE_TIME_OUT_PHP');
if (defined("CHERCH_TS_TYP")) $cherch_ts_typ = $config->get('CHERCH_TS_TYP');
if (defined("RECH_MIN")) $rech_min = $config->get('RECH_MIN');

$Max_time = ini_get("max_execution_time") - $SECURITE_TIME_OUT_PHP;
$T0 = time();
$txtcomp = "";

pathroot($root, $path, $xcomm, $xpatr, $page);

$xach  = getparam('achercher');
$xpre  = getparam('prenom');
$xach2 = getparam('achercher2');
$xpre2 = getparam('prenom2');
$xtyps = getparam('TypNDMV');
if ($xtyps == "") { // plusieurs types possibles
    $xtypN = (getparam('TypN') == 'N');
    $xtypD = (getparam('TypD') == 'D');
    $xtypM = (getparam('TypM') == 'M');
    $xtypV = (getparam('TypV') == 'V');
} else { // un type à la fois
    $xtypN = ($xtyps == 'N');
    $xtypD = ($xtyps == 'D');
    $xtypM = ($xtyps == 'M');
    $xtypV = ($xtyps == 'V');
}

$xmin  = getparam('amin');
$xmax  = getparam('amax');
$xcomp = getparam('comp');
$xcomp2 = getparam('comp2');
$comdep  = html_entity_decode(getparam('ComDep'), ENTITY_REPLACE_FLAGS, ENTITY_CHARSET);
$xcomm = communede($comdep);
$xdepa  = departementde($comdep);
$xord  = getparam('xord');
$page  = getparam('pg');

ob_start();
open_page("Recherches dans les tables", $root); ?>
<div class="main">
    <?php zone_menu(0, $session->get('user', ['level' => 0])['level']);
    if (current_user_solde() > 0 or $config->get('RECH_ZERO_PTS') == 1) {

        $nav = "";
        if ($xcomp != "") {
            $nav = '<a href="rechlevenshtein.php">Recherche Levenshtein</a> &gt; ';
        }

        echo '<div class="main-col-center text-center">';
        navigation($root, 2, 'A', $nav . "Résultats de la recherche");
        echo '<h2>Résultats de la recherche</h2>';

        // Controles critères  de la recherche
        if (strlen($xach) < $rech_min) {
            msg('Le patronyme à chercher doit compter au moins ' . $rech_min . ' caractères.');
        } elseif (!($xtypN or $xtypD or $xtypM or $xtypV)) { // ###################NOUVEAU#######################
            msg('La recherche doit porter sur au moins un des types d\'actes.');
        } elseif (strpos("X" . $xach . $xpre . $xach2 . $xpre2, '%') > 0 or strpos("X" . $xach . $xpre . $xach2 . $xpre2, '__') > 0) {
            msg('La recherche ne peut contenir les caractères "%" ou "__".');
        } else {
            $critN = "";
            $critD = "";
            $critM = "";
            $mes = "";

            // création des requetes jointure
            if ($xcomp == "Z") {
                $dm = 'aucune différence';
            }
            if ($xcomp == "U") {
                $dm = 'une différence';
            }
            if ($xcomp == "D") {
                $dm = 'deux différences';
            }
            if ($xcomp == "T") {
                $dm = 'trois différences';
            }
            if ($xcomp == "Q") {
                $dm = 'quatre différences';
            }
            if ($xcomp == "C") {
                $dm = 'cinq différences';
            }
            if ($xmax != '') {
                if ($xmin != '') {
                    $critdate = ' de ' . $xmin . ' à ' . $xmax;
                } else {
                    $critdate = ' jusqu en ' . $xmax;
                }
                $xmin = (int) $xmin;
                $xmax = (int) $xmax;
            } else {
                if ($xmin != '') {
                    $critdate = ' à partir de ' . $xmin;
                    $xmin = (int) $xmin;
                } else {
                    $critdate = '';
                }
            }

            $fin_ok = 'ok';
            $sql = "";
            if (($xach != "") and ($xach2 != "")) { // recherche sur couple
                if ($xcomp2 == "MA") { // recherche mariages
                    $fin_ok = table_temp($xach, $xcomp, $config->get('EA_DB') . "_mar3", "H", $xcomm, $ip_adr_trait, $xmin, $xmax, $T0, $Max_time);
                    $fin_ok = table_temp($xach2, $xcomp, $config->get('EA_DB') . "_mar3", "F", $xcomm, $ip_adr_trait, $xmin, $xmax, $T0, $Max_time);
                    $sql = "SELECT ID, TYPACT, DATETXT, COMMUNE, NOM, PRE, C_NOM, C_PRE, LADATE,'Mariage' AS LIBELLE," . $config->get('EA_DB') . "_" . $ip_adr_trait . "_h.disth ," . $config->get('EA_DB') . "_" . $ip_adr_trait . "_f.distf "
                        . " FROM " . $config->get('EA_DB') . "_mar3 JOIN " . $config->get('EA_DB') . "_" . $ip_adr_trait . "_h on " . $config->get('EA_DB') . "_mar3.nom = " . $config->get('EA_DB') . "_" . $ip_adr_trait . "_h.nomlev "
                        . "  JOIN " . $config->get('EA_DB') . "_" . $ip_adr_trait . "_f on " . $config->get('EA_DB') . "_mar3.c_nom = " . $config->get('EA_DB') . "_" . $ip_adr_trait . "_f.nomlev ";
                    $crit = makecritjlc($xmin, $xmax, $xcomm, $xdepa, "PRE", "C_PRE", $xpre, $xpre2);
                    $sql .= " WHERE " . $crit . " ORDER BY ladate";
                    $mes = 'des mariages pour les noms <b>' . strtoupper($xach) . '</b> et <b>' . strtoupper($xach2) . '</b> avec ' . $dm . '  ' . $critdate;
                }
                if ($xcomp2 == "EN") { //recherche enfants naissances seulement
                    $fin_ok = table_temp($xach, $xcomp, $config->get('EA_DB') . "_nai3", "H", $xcomm, $ip_adr_trait, $xmin, $xmax, $T0, $Max_time);
                    $fin_ok = table_temp($xach2, $xcomp, $config->get('EA_DB') . "_nai3", "F", $xcomm, $ip_adr_trait, $xmin, $xmax, $T0, $Max_time);
                    //. " FROM " . EA_DB . "_nai3 JOIN " . $ip_adr_trait . "_h on " . EA_DB . "_nai3.p_nom = " . $ip_adr_trait . "_h.nomlev "
                    $sql = "SELECT ID, TYPACT, DATETXT, COMMUNE, NOM, PRE, P_PRE, M_PRE, LADATE,'Naissance' AS LIBELLE,NOM,M_NOM "
                        . " FROM " . $config->get('EA_DB') . "_nai3 JOIN " . $config->get('EA_DB') . "_" . $ip_adr_trait . "_h on " . $config->get('EA_DB') . "_nai3.P_NOM = " . $config->get('EA_DB') . "_" . $ip_adr_trait . "_h.nomlev "
                        . "  JOIN " . $config->get('EA_DB') . "_" . $ip_adr_trait . "_f on " . $config->get('EA_DB') . "_nai3.M_NOM = " . $config->get('EA_DB') . "_" . $ip_adr_trait . "_f.nomlev ";
                    $crit = makecritjlc($xmin, $xmax, $xcomm, $xdepa, "P_PRE", "M_PRE", $xpre, $xpre2);
                    $sql .= " WHERE " . $crit . " ORDER BY ladate";
                    $mes = 'des naissances enfants pour les noms <b>' . strtoupper($xach) . '</b> et <b>' . strtoupper($xach2) . '</b> avec ' . $dm . '  ' . $critdate;
                }
                if ($xcomp2 == "END") { //recherche enfants naissances deces
                    $fin_ok = table_temp($xach, $xcomp, $config->get('EA_DB') . "_nai3", "H", $xcomm, $ip_adr_trait, $xmin, $xmax, $T0, $Max_time);
                    $fin_ok = table_temp($xach2, $xcomp, $config->get('EA_DB') . "_nai3", "F", $xcomm, $ip_adr_trait, $xmin, $xmax, $T0, $Max_time);
                    cree_table_temp_sup($ip_adr_trait . "_hb", $ip_adr_trait . "_h");
                    cree_table_temp_sup($ip_adr_trait . "_fb", $ip_adr_trait . "_f");
                    //	. " FROM " . EA_DB . "_nai3 JOIN " . $ip_adr_trait . "_h on " . EA_DB . "_nai3.p_nom = " . $ip_adr_trait . "_h.nomlev "
                    $sql = "(SELECT ID, TYPACT, DATETXT, COMMUNE, NOM, PRE, P_PRE, M_PRE, LADATE,'Naissance' AS LIBELLE,P_NOM,M_NOM "
                        . " FROM " . $config->get('EA_DB') . "_nai3 JOIN " . $config->get('EA_DB') . "_" . $ip_adr_trait . "_h on " . $config->get('EA_DB') . "_nai3.p_nom = " . $config->get('EA_DB') . "_" . $ip_adr_trait . "_h.nomlev "
                        . "  JOIN " . $config->get('EA_DB') . "_" . $ip_adr_trait . "_f on " . $config->get('EA_DB') . "_nai3.m_nom = " . $config->get('EA_DB') . "_" . $ip_adr_trait . "_f.nomlev ";
                    $crit = makecritjlc($xmin, $xmax, $xcomm, $xdepa, "P_PRE", "M_PRE", $xpre, $xpre2);
                    //. " FROM " . EA_DB . "_dec3 JOIN " . $ip_adr_trait . "_hb on " . EA_DB . "_dec3.p_nom = " . $ip_adr_trait . "_hb.nomlev
                    $sql .= " WHERE " . $crit . "  )";
                    $sql .= " UNION (SELECT ID, TYPACT, DATETXT, COMMUNE, NOM, PRE, P_PRE, M_PRE, LADATE,'Décès' AS LIBELLE,P_NOM,M_NOM "
                        . " FROM " . $config->get('EA_DB') . "_dec3 JOIN " . $config->get('EA_DB') . "_" . $ip_adr_trait . "_hb on " . $config->get('EA_DB') . "_dec3.p_nom = " . $config->get('EA_DB') . "_" . $ip_adr_trait . "_hb.nomlev "
                        . "  JOIN " . $config->get('EA_DB') . "_" . $ip_adr_trait . "_fb on " . $config->get('EA_DB') . "_dec3.m_nom = " . $config->get('EA_DB') . "_" . $ip_adr_trait . "_fb.nomlev ";

                    $sql .= " WHERE " . $crit . "  )";
                    $sql .= " ORDER BY ladate ";
                    $mes = 'des naissances et décès enfants pour les noms <b>' . strtoupper($xach) . '</b> et <b>' . strtoupper($xach2) . '</b> avec ' . $dm . '  ' . $critdate;
                }
                if ($xcomp2 == "TOUT") { //recherche mariages enfants naissances deces
                    $fin_ok = table_temp($xach, $xcomp, $config->get('EA_DB') . "_mar3", "H", $xcomm, $ip_adr_trait, $xmin, $xmax, $T0, $Max_time);
                    $fin_ok = table_temp($xach2, $xcomp, $config->get('EA_DB') . "_mar3", "F", $xcomm, $ip_adr_trait, $xmin, $xmax, $T0, $Max_time);
                    $fin_ok = table_temp($xach, $xcomp, $config->get('EA_DB') . "_nai3", "N", $xcomm, $ip_adr_trait, $xmin, $xmax, $T0, $Max_time);
                    $fin_ok = table_temp($xach, $xcomp, $config->get('EA_DB') . "_dec3", "D", $xcomm, $ip_adr_trait, $xmin, $xmax, $T0, $Max_time);
                    $sql = "(SELECT ID, TYPACT, DATETXT, COMMUNE, NOM, PRE, C_NOM, C_PRE, LADATE,'Mariage' AS LIBELLE," . $config->get('EA_DB') . "_" . $ip_adr_trait . "_h.disth ," . $config->get('EA_DB') . "_" . $ip_adr_trait . "_f.distf "
                        . " FROM " . $config->get('EA_DB') . "_mar3 JOIN " . $config->get('EA_DB') . "_" . $ip_adr_trait . "_h on " . $config->get('EA_DB') . "_mar3.nom = " . $config->get('EA_DB') . "_" . $ip_adr_trait . "_h.nomlev "
                        . "  JOIN " . $config->get('EA_DB') . "_" . $ip_adr_trait . "_f on " . $config->get('EA_DB') . "_mar3.c_nom = " . $config->get('EA_DB') . "_" . $ip_adr_trait . "_f.nomlev ";
                    $crit = makecritjlc($xmin, $xmax, $xcomm, $xdepa, "P_PRE", "M_PRE", $xpre, $xpre2);
                    $sql .= " WHERE " . $crit . "  )";

                    cree_table_temp_sup($ip_adr_trait . "_fb", $ip_adr_trait . "_f");

                    $sql .= " UNION (SELECT ID, TYPACT, DATETXT, COMMUNE, NOM, PRE, P_PRE, M_PRE, LADATE,'Naissance' AS LIBELLE,NOM,M_NOM "
                        . " FROM " . $config->get('EA_DB') . "_nai3 JOIN " . $config->get('EA_DB') . "_" . $ip_adr_trait . "_n on " . $config->get('EA_DB') . "_nai3.nom = " . $config->get('EA_DB') . "_" . $ip_adr_trait . "_n.nomlev "
                        . "  JOIN " . $config->get('EA_DB') . "_" . $ip_adr_trait . "_fb on " . $config->get('EA_DB') . "_nai3.m_nom = " . $config->get('EA_DB') . "_" . $ip_adr_trait . "_fb.nomlev ";
                    $sql .= " WHERE " . $crit . "  )";

                    cree_table_temp_sup($ip_adr_trait . "_ft", $ip_adr_trait . "_f");

                    $sql .= " UNION (SELECT ID, TYPACT, DATETXT, COMMUNE, NOM, PRE, P_PRE, M_PRE, LADATE,'Décès' AS LIBELLE,P_NOM,M_NOM "
                        . " FROM " . $config->get('EA_DB') . "_dec3 JOIN " . $config->get('EA_DB') . "_" . $ip_adr_trait . "_d on " . $config->get('EA_DB') . "_dec3.nom = " . $config->get('EA_DB') . "_" . $ip_adr_trait . "_d.nomlev "
                        . "  JOIN " . $config->get('EA_DB') . "_" . $ip_adr_trait . "_ft on " . $config->get('EA_DB') . "_dec3.m_nom = " . $config->get('EA_DB') . "_" . $ip_adr_trait . "_ft.nomlev ";

                    $sql .= " WHERE " . $crit . "  )";

                    //############################# Ajout JLC V 2.1.8 - 20-02-2009 ######################################"

                    cree_table_temp_sup($ip_adr_trait . "_hq", $ip_adr_trait . "_h");
                    cree_table_temp_sup($ip_adr_trait . "_fq", $ip_adr_trait . "_f");

                    $sql .= " UNION (SELECT ID, TYPACT, DATETXT, COMMUNE, NOM, PRE, C_NOM, C_PRE, LADATE,'Mariage' AS LIBELLE," . $config->get('EA_DB') . "_" . $ip_adr_trait . "_hq.disth ," . $config->get('EA_DB') . "_" . $ip_adr_trait . "_fq.distf "
                        . " FROM " . $config->get('EA_DB') . "_mar3 JOIN " . $config->get('EA_DB') . "_" . $ip_adr_trait . "_hq on " . $config->get('EA_DB') . "_mar3.nom = " . $config->get('EA_DB') . "_" . $ip_adr_trait . "_hq.nomlev "
                        . " JOIN " . $config->get('EA_DB') . "_" . $ip_adr_trait . "_fq on " . $config->get('EA_DB') . "_mar3.m_nom = " . $config->get('EA_DB') . "_" . $ip_adr_trait . "_fq.nomlev ";
                    $crit = makecritjlc($xmin, $xmax, $xcomm, $xdepa, "P_PRE", "M_PRE", $xpre, $xpre2);
                    $sql .= " WHERE " . $crit . " )";

                    cree_table_temp_sup($ip_adr_trait . "_hc", $ip_adr_trait . "_h");
                    cree_table_temp_sup($ip_adr_trait . "_fc", $ip_adr_trait . "_f");

                    $sql .= " UNION (SELECT ID, TYPACT, DATETXT, COMMUNE, NOM, PRE, C_NOM, C_PRE, LADATE,'Mariage' AS LIBELLE," . $config->get('EA_DB') . "_" . $ip_adr_trait . "_hc.disth ," . $config->get('EA_DB') . "_" . $ip_adr_trait . "_fc.distf "
                        . " FROM " . $config->get('EA_DB') . "_mar3 JOIN " . $config->get('EA_DB') . "_" . $ip_adr_trait . "_hc on " . $config->get('EA_DB') . "_mar3.c_nom = " . $config->get('EA_DB') . "_" . $ip_adr_trait . "_hc.nomlev "
                        . " JOIN " . $config->get('EA_DB') . "_" . $ip_adr_trait . "_fc on " . $config->get('EA_DB') . "_mar3.cm_nom = " . $config->get('EA_DB') . "_" . $ip_adr_trait . "_fc.nomlev ";
                    $crit = makecritjlc($xmin, $xmax, $xcomm, $xdepa, "CP_PRE", "CM_PRE", $xpre, $xpre2);
                    $sql .= " WHERE " . $crit . " )";
                    //###########################################################""""

                    $sql .= " ORDER BY ladate ";

                    $mes = 'des mariages et évènements enfants pour les noms <b>' . strtoupper($xach) . '</b> et <b>' . strtoupper($xach2) . '</b> avec ' . $dm . '  ' . $critdate;
                }
                if ($xcomp2 == "DIV") { //recherche actes divers ###################NOUVEAU#######################
                    $fin_ok = table_temp($xach, $xcomp, $config->get('EA_DB') . "_div3", "H", $xcomm, $ip_adr_trait, $xmin, $xmax, $T0, $Max_time);
                    $fin_ok = table_temp($xach2, $xcomp, $config->get('EA_DB') . "_div3", "F", $xcomm, $ip_adr_trait, $xmin, $xmax, $T0, $Max_time);
                    // recherche Int1 Int2 et Int2 Int1 (ce n'est pas H et F )
                    $sql = "(SELECT ID, TYPACT, DATETXT, COMMUNE, NOM, PRE, C_NOM, C_PRE, LADATE,'Acte divers' AS LIBELLE," . $config->get('EA_DB') . "_" . $ip_adr_trait . "_h.disth ," . $config->get('EA_DB') . "_" . $ip_adr_trait . "_f.distf "
                        . " FROM " . $config->get('EA_DB') . "_div3 JOIN " . $config->get('EA_DB') . "_" . $ip_adr_trait . "_h on " . $config->get('EA_DB') . "_div3.nom = " . $config->get('EA_DB') . "_" . $ip_adr_trait . "_h.nomlev "
                        . "  JOIN " . $config->get('EA_DB') . "_" . $ip_adr_trait . "_f on " . $config->get('EA_DB') . "_div3.c_nom = " . $config->get('EA_DB') . "_" . $ip_adr_trait . "_f.nomlev ";
                    $crit = makecritjlc($xmin, $xmax, $xcomm, $xdepa, "PRE", "C_PRE", $xpre, $xpre2);
                    $sql .= " WHERE " . $crit . "   )";

                    cree_table_temp_sup($ip_adr_trait . "_hb", $ip_adr_trait . "_h");
                    cree_table_temp_sup($ip_adr_trait . "_fb", $ip_adr_trait . "_f");

                    $sql .= " UNION (SELECT ID, TYPACT, DATETXT, COMMUNE, NOM, PRE, C_NOM, C_PRE, LADATE,'Acte divers' AS LIBELLE, 'Z' AS P_NOM, 'T' AS M_NOM "
                        . " FROM " . $config->get('EA_DB') . "_div3 JOIN " . $config->get('EA_DB') . "_" . $ip_adr_trait . "_fb on " . $config->get('EA_DB') . "_div3.nom = " . $config->get('EA_DB') . "_" . $ip_adr_trait . "_fb.nomlev "
                        . "  JOIN " . $config->get('EA_DB') . "_" . $ip_adr_trait . "_hb on " . $config->get('EA_DB') . "_div3.c_nom = " . $config->get('EA_DB') . "_" . $ip_adr_trait . "_hb.nomlev ";
                    $crit = makecritjlc($xmin, $xmax, $xcomm, $xdepa, "c_PRE", "", $xpre, $xpre2);
                    $sql .= " WHERE " . $crit . "  )";
                    $sql .= " ORDER BY ladate ";


                    $mes = 'des actes divers pour les noms <b>' . strtoupper($xach) . '</b> et <b>' . strtoupper($xach2) . '</b> avec ' . $dm . '  ' . $critdate;
                } // ##########################FIN###############################"
            } elseif ($xach != "") { // recherche sur individu
                $mes = 'du nom <b>' . strtoupper($xach) . '</b> avec ' . $dm . ' sur les ';

                if ($xtypM) { // mariages
                    $fin_ok = table_temp($xach, $xcomp, $config->get('EA_DB') . "_mar3", "H", $xcomm, $ip_adr_trait, $xmin, $xmax, $T0, $Max_time);
                    $fin_ok = table_temp($xach, $xcomp, $config->get('EA_DB') . "_mar3", "F", $xcomm, $ip_adr_trait, $xmin, $xmax, $T0, $Max_time);
                    $sql = "(SELECT ID, TYPACT, DATETXT, COMMUNE, NOM, PRE,  C_NOM, C_PRE, LADATE,'Mariage  ' AS LIBELLE, 'Zzzzzzzzzzzzzzzzzzzzzzzzz' AS P_NOM, 'Ttttttttttttttttttttttttt' AS M_NOM  "
                        . " FROM " . $config->get('EA_DB') . "_mar3 JOIN " . $config->get('EA_DB') . "_" . $ip_adr_trait . "_h on " . $config->get('EA_DB') . "_mar3.nom =  " . $config->get('EA_DB') . "_" . $ip_adr_trait . "_h.nomlev ";
                    $crit = makecritjlc($xmin, $xmax, $xcomm, $xdepa, "PRE", "", $xpre, $xpre2);
                    $sql .= " WHERE " . $crit . "   )";

                    $sql .= " UNION (SELECT ID, TYPACT, DATETXT, COMMUNE, NOM, PRE, C_NOM, C_PRE, LADATE,'Mariage' AS LIBELLE, 'Z' AS P_NOM, 'T' AS M_NOM "
                        . " FROM " . $config->get('EA_DB') . "_mar3 JOIN " . $config->get('EA_DB') . "_" . $ip_adr_trait . "_f on " . $config->get('EA_DB') . "_mar3.c_nom =  " . $config->get('EA_DB') . "_" . $ip_adr_trait . "_f.nomlev ";
                    $crit = makecritjlc($xmin, $xmax, $xcomm, $xdepa, "c_PRE", "", $xpre, $xpre2);
                    $sql .= " WHERE " . $crit . "  )";
                    $mes = $mes . ' Mariages ';
                }
                if ($xtypD) { //deces
                    $fin_ok = table_temp($xach, $xcomp, $config->get('EA_DB') . "_dec3", "D", $xcomm, $ip_adr_trait, $xmin, $xmax, $T0, $Max_time);
                    if (strlen($sql) > 0) {
                        $sql .= ' UNION ';
                    }
                    $sql .= "(SELECT ID, TYPACT, DATETXT, COMMUNE, NOM,PRE,'X' AS C_NOM, 'Y' AS C_PRE, LADATE,'Décès' AS LIBELLE, 'Z' AS P_NOM, 'T' AS M_NOM  "
                        . " FROM " . $config->get('EA_DB') . "_dec3 JOIN " . $config->get('EA_DB') . "_" . $ip_adr_trait . "_d on " . $config->get('EA_DB') . "_dec3.nom = " . $config->get('EA_DB') . "_" . $ip_adr_trait . "_d.nomlev ";
                    $crit = makecritjlc($xmin, $xmax, $xcomm, $xdepa, "PRE", "", $xpre, $xpre2);
                    $sql .= " WHERE " . $crit . "  )";
                    $mes = $mes . ' Décès ';
                }
                if ($xtypN) { //naissances
                    $fin_ok = table_temp($xach, $xcomp, $config->get('EA_DB') . "_nai3", "N", $xcomm, $ip_adr_trait, $xmin, $xmax, $T0, $Max_time);
                    if (strlen($sql) > 0) {
                        $sql .= ' UNION ';
                    }
                    $sql .= "(SELECT ID, TYPACT, DATETXT, COMMUNE, NOM, PRE, P_PRE, M_PRE,  LADATE,'Naissance' AS LIBELLE,P_NOM,M_NOM  "
                        . " FROM " . $config->get('EA_DB') . "_nai3 JOIN " . $config->get('EA_DB') . "_" . $ip_adr_trait . "_n on " . $config->get('EA_DB') . "_nai3.nom = " . $config->get('EA_DB') . "_" . $ip_adr_trait . "_n.nomlev ";
                    $crit = makecritjlc($xmin, $xmax, $xcomm, $xdepa, "PRE", "", $xpre, $xpre2);
                    $sql .= " WHERE " . $crit . " )";
                    $mes = $mes . ' Naissances ';
                }
                if ($xtypV) { //actes divers ##########################NOUVEAU################################
                    $fin_ok = table_temp($xach, $xcomp, $config->get('EA_DB') . "_div3", "H", $xcomm, $ip_adr_trait, $xmin, $xmax, $T0, $Max_time);
                    $fin_ok = table_temp($xach, $xcomp, $config->get('EA_DB') . "_div3", "F", $xcomm, $ip_adr_trait, $xmin, $xmax, $T0, $Max_time);

                    cree_table_temp_sup($ip_adr_trait . "_hb", $ip_adr_trait . "_h");
                    cree_table_temp_sup($ip_adr_trait . "_fb", $ip_adr_trait . "_f");

                    if (strlen($sql) > 0) {
                        $sql .= ' UNION ';
                    }
                    $sql .= "(SELECT ID, TYPACT, DATETXT, COMMUNE, NOM, PRE,  C_NOM, C_PRE, LADATE,'Acte Divers' AS LIBELLE, 'Z' AS P_NOM,  'T' AS M_NOM  "
                        . " FROM " . $config->get('EA_DB') . "_div3 JOIN " . $config->get('EA_DB') . "_" . $ip_adr_trait . "_hb on " . $config->get('EA_DB') . "_div3.nom = " . $config->get('EA_DB') . "_" . $ip_adr_trait . "_hb.nomlev ";
                    $crit = makecritjlc($xmin, $xmax, $xcomm, $xdepa, "PRE", "", $xpre, $xpre2);
                    $sql .= " WHERE " . $crit . "   )";
                    $sql .= " UNION (SELECT ID, TYPACT, DATETXT, COMMUNE, NOM, PRE, C_NOM, C_PRE, LADATE,'Acte Divers' AS LIBELLE, 'Z' AS P_NOM, 'T' AS M_NOM "
                        . " FROM " . $config->get('EA_DB') . "_div3 JOIN " . $config->get('EA_DB') . "_" . $ip_adr_trait . "_fb on " . $config->get('EA_DB') . "_div3.c_nom = " . $config->get('EA_DB') . "_" . $ip_adr_trait . "_fb.nomlev ";
                    $crit = makecritjlc($xmin, $xmax, $xcomm, $xdepa, "c_PRE", "", $xpre, $xpre2);
                    $sql .= " WHERE " . $crit . "  )";
                    $mes = $mes . ' Actes divers ';   // ##########################FIN###############################"
                }
                $sql .= " ORDER BY ladate ";
                $mes = $mes . '  ' . $critdate;
            }

            if ((time() - $T0 >= $Max_time) or ($fin_ok != 'ok')) {
                echo msg('La recherche ne peut aboutir car elle prend trop de temps !');
                echo '<th></th>';
                echo '<th></th>';
                echo '<th></th>';
                echo '<th></th>';
                echo '<p>Quelques suggestions :</p>';
                echo '<th></th>';
                echo '<th></th>';
                echo '<p>1- Mettez si ce n\'est déjà fait des dates min et max</p>';
                echo '<p>2- Réduisez l\'intervalle de recherche sur les dates</p>';
                echo '<p>3- Diminuez le nombre de différences</p>';
                echo '<p>4- Ne faite la recherche que sur un type d\'acte</p>';
                echo '<p>5-  .....</p>';
                echo '<p>En désespoir de cause, essayez plus tard, le serveur est peut être trop chargé</p>';
                echo '<th></th>';
                echo '<th></th>';
            } else {

                $result = EA_sql_query($sql) or die('Erreur SQL requete générale!' . $sql . '<br>' . EA_sql_error());
                $nbtot = EA_sql_num_rows($result);
                $baselink = $path . '/chercherlevenshtein.php?achercher=' . $xach . '&amp;prenom=' . $xpre . '&amp;comp=' . $xcomp;
                $baselink .= '&amp;achercher2=' . $xach2 . '&amp;prenom2=' . $xpre2 . '&amp;comp2=' . $xcomp2;
                $baselink .= iif($xtypN, '&amp;TypN=N', '') . iif($xtypD, '&amp;TypD=D', '') . iif($xtypM, '&amp;TypM=M', '') . iif($xtypV, '&amp;TypV=V', '');
                $baselink .= '&amp;ComDep=' . urlencode($comdep);
                $baselink .= '&amp;amin=' . $xmin . '&amp;amax=' . $xmax;
                $limit = "";
                $listpages = "";
                pagination($nbtot, $page, $baselink, $listpages, $limit);
                if ($limit <> "") {
                    $sql = $sql . $limit;
                    $result = EA_sql_query($sql);
                    $nb = EA_sql_num_rows($result);
                } else {
                    $nb = $nbtot;
                }
                echo '<div class="critrech">Recherche Levenshtein <ul>' . $mes . '</ul></div>';
                if ($nb > 0) {
                    $i = ($page - 1) * $config->get('MAX_PAGE') + 1;
                    echo '<p><b>' . $nbtot . ' actes trouvés</b></p>';
                    echo '<p>' . $listpages . '</p>';
                    echo '<table summary="Liste des résultats">';
                    echo '<tr class="rowheader">';
                    echo '<th> &nbsp; </th>';
                    echo '<th>Type</th>';
                    echo '<th>Date</th>';
                    echo '<th>Intéressé(e)</th>';
                    echo '<th>Parents</th>';
                    echo '<th>Commune/Paroisse</th>';
                    echo '</tr>';
                    while ($ligne = EA_sql_fetch_row($result)) {
                        switch ($ligne[1]) {
                            case "N":
                                $url = $root . '/acte_naiss.php';
                                break;
                            case "D":
                                $url = $root . '/acte_deces.php';
                                break;
                            case "M":
                                $url = $root . '/acte_mari.php';
                                break;
                            case "V":
                                $url = $root . '/acte_bans.php';
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
                        if ($ligne[1] == 'N' or $ligne[1] == 'D') {
                            if ($ligne[6] == '') {
                                $ligne[6] = ' ';
                            }
                            if ($ligne[7] == '') {
                                $ligne[7] = ' ';
                            }
                            if ($ligne[6][0] . $ligne[10] . $ligne[7][0] . $ligne[11] != 'XZYT') {
                                echo '<td>' . $ligne[6][0] . ". " . $ligne[10] . " - " . $ligne[7][0] . ". " . $ligne[11] . ' </td>';
                            } else {
                                echo '<td></td>';
                            }
                        } else {
                            echo '<td></td>';
                        }
                        echo '<td>' . $ligne[3] . '</td>';
                        echo '</tr>';
                        $i++;
                    }
                    echo '</table>';
                    echo '<p>' . $listpages . '</p>';
                } else {
                    echo '<p> Aucun acte trouvé </p>';
                }
                echo '<p>Durée du traitement  : ' . (time() - $T0) . ' sec.</p>';
            }
        }
    } else {
        msg('Recherche non autorisée car votre solde de points est épuisé !');
    }
    echo '</div>';
    echo '</div>';
    include(__DIR__ . '/templates/front/_footer.php');
    $response->setContent(ob_get_clean());
    $response->send();
