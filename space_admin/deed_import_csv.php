<?php

use Symfony\Component\HttpFoundation\RedirectResponse;

require(__DIR__ . '/../tools/traitements.inc.php');
require(__DIR__ . '/../tools/adodb-time.inc.php');

if (!$userAuthorizer->isGranted(6)) {
    $response = new RedirectResponse("$root/admin/");
    $response->send();
    exit();
}

function nomcolonne($i)  // noms des colonnes à la Excel
{
    if ($i <= 26) {
        return chr(64 + $i);
    } else {
        $un = floor(($i - 1) / 26) + 64;
        $de = fmod($i - 1, 26) + 65;
        return chr($un) . chr($de);
    }
}

function listbox_cols($fieldname, $default)
{
    global $acte;
    $i = 1;
    echo '<select name="' . $fieldname . '" size="1">';
    echo '<option ' . (0 == $default ? 'selected' : '') . '> -- </option>';
    foreach ($acte as $zone) {
        echo '<option ' . ($i == $default ? 'selected' : '') . '>Col. ' . nomcolonne($i) . '-' . $i . ' (' . $zone . ')</option>';
        $i++;
    }
    echo " </select>";
}

$Max_time = min(ini_get("max_execution_time") - 3, $config->get('MAX_EXEC_TIME'));


$T0 = time();
$AnneeVide  = getparam('AnneeVide', 0); // for checked
$SuprRedon  = getparam('SuprRedon', 0); // for checked
$SuprPatVid = getparam('SuprPatVid', 0); // for checked
$logOk      = getparam('LogOk', 0); // for checked
$logKo      = getparam('LogKo', 0); // for checked
$logRed     = getparam('LogRed', 0); // for checked
$TypeActes  = getparam('TypeActes');
$commune    = html_entity_decode(getparam('Commune'), ENTITY_REPLACE_FLAGS, ENTITY_CHARSET);
$depart     = html_entity_decode(getparam('Depart'), ENTITY_REPLACE_FLAGS, ENTITY_CHARSET);
$communecsv = getparam('CommuneCsv', 0); // for checked
$departcsv  = getparam('DepartCsv', 0); // for checked
$typedoc    = getparam('typedoc');
$deposant   = getparam('deposant');
$Filtre        = getparam('Filtre');
$Condition     = getparam('Condition');
$Compare       = getparam('Compare');
$photo           = getparam('photo');
$trans           = getparam('trans');
$verif           = getparam('verif');
$photocsv   = getparam('photocsv', 0); // for checked
$transcsv   = getparam('transcsv', 0); // for checked
$verifcsv   = getparam('verifcsv', 0); // for checked
$isUTF8     = false;

if (isset($_REQUEST['submitD'])) {  // définir les mapping
    $submit = "D";
} elseif (isset($_REQUEST['submitC'])) {  // charger
    $submit = "C";
} elseif (isset($_REQUEST['submitR'])) {  // remise à zéro
    $submit = "R";
} elseif (isset($_REQUEST['submitV'])) {  // Voir exemple
    $submit = "V";
} elseif (isset($_REQUEST['submitS'])) { // Sauvegarde config CSV
    $submit = "S";
} elseif (isset($_REQUEST['submitL'])) { // Chargementconfig CSV
    $submit = "L";
}

if ($TypeActes == "") {
    $TypeActes = 'N';
}

$mdb = load_zlabels($TypeActes, $lg);

if (isset($_REQUEST['action'])) {
    setcookie("chargeCSV", $AnneeVide . $SuprRedon . $SuprPatVid . $logOk . $logKo . $logRed, time() + 60 * 60 * 24 * 365);  // 1 an
    if (isset($_REQUEST['Zone1'])) {
        $i = 0;
        $nameI = "ZID" . $i;
        $nameZ = "Zone" . $i;
        $nameT = "Trait" . $i;
        $cookie = "";
        while (isset($_REQUEST[$nameI])) {
            $cookie .= $_REQUEST[$nameI] . '-' . $_REQUEST[$nameZ] . '-' . $_REQUEST[$nameT] . '+';
            $i++;
            $nameI = "ZID" . $i;
            $nameZ = "Zone" . $i;
            $nameT = "Trait" . $i;
        }
        if ($submit == 'R') { // Remise à blanc
            $cookie = "               ";
        }
        setcookie("charge" . getparam('TypeActes'), $cookie, time() + 60 * 60 * 24 * 365);
    }
    switch ($TypeActes) {
        case "N":
            $ntype = "Naissance";
            $table = $config->get('EA_DB') . "_nai3";
            $annee = 8;
            $script = '/actes/naissances';
            break;
        case "M":
            $ntype = "Mariage";
            $table = $config->get('EA_DB') . "_mar3";
            $annee = 6;
            $script = '/actes/mariages';
            break;
        case "D":
            $ntype = "Décès";
            $table = $config->get('EA_DB') . "_dec3";
            $annee = 7;
            $script = '/actes/deces';
            break;
        case "V":
            $ntype = "Divers";
            $table = $config->get('EA_DB') . "_div3";
            $annee = 0;
            $script = '/actes/divers';
            break;
    }
}

$emailfound = false;
$oktype = false;
$cptign = 0;
$cptadd = 0;
$cptdeja = 0;
$cptfiltre = 0;
$avecidnim = false;

$today = date("Y-m-d", time());
$missingargs = true;

my_ob_start_affichage_continu();
open_page("Chargement des actes (CSV)", $root); ?>
<div class="main">
    <?php zone_menu(10, $session->get('user')['level'], array()); ?>
    <div class="main-col-center text-center">
        <?php 
navadmin($root, "Chargement des actes CSV");

if (isset($_REQUEST['action'])) {
    // Données postées
    $missingargs = false;
    if ($submit == 'D') {  // upload du fichier CSV
        if (empty($_FILES['Actes']['tmp_name'])) {
            msg('Pas trouvé le fichier spécifié.');
            $missingargs = true;
        }
        if (!is_uploaded_file($_FILES['Actes']['tmp_name'])) {
            msg('Méthode de téléchargement non valide.');
            writelog('Possible tentative de téléchargement CSV frauduleux');
        }
        if (strtolower(mb_substr($_FILES['Actes']['name'], -4)) <> ".csv") { //Vérifie que l'extension est bien '.CSV'
            msg('Les fichier doit être de type .CSV.');
            $missingargs = true;
        }
    }
    if ($submit <> 'D') {
        if (empty($_REQUEST['fileuploaded'])) {
            msg('Pas de fichier spécifié.');
            $missingargs = true;
        }
    }
    if (empty($TypeActes)) {
        msg('Vous devez préciser le type des actes.');
        $missingargs = true;
    }
    if (empty($commune) and !$communecsv) {
        msg('Vous devez préciser le nom de la commune ou de la paroisse ou indiquer qu\'il sera lu dans le fichier.');
        $missingargs = true;
    }
    if (empty($depart) and !$departcsv) {
        msg('Vous devez préciser le nom du département ou de la province ou indiquer qu\'il sera lu dans le fichier.');
        $missingargs = true;
    }
}

$zonelibelle = "ZoneX";
if ($TypeActes == 'V') {
    for ($i = 1; $i < 9; $i++) {  // tjrs entre 1 et 9
        if (array_key_exists('ZID' . $i, $_REQUEST) and $_REQUEST['ZID' . $i] == '4012') {  // LIBELLE de Divers
            $zonelibelle = "Zone" . $i;
        }
    }
    //echo "<P>ZONE : ".	$zonelibelle;
}
$meserrdivers1 = "Vous devez préciser le type de document dont il s'agit soit globalement, soit par chargement d'une zone";
$meserrdivers2 = "Vous ne pouvez pas spécifier simultanément un type de document global et une zone fournissant le type de document";

if (!$missingargs) { // fichier d'actes
    //	if ($TypeFich == "L")
    //		{ // Format LIBRE
    $resume = false;
    if (isset($_REQUEST['passlign'])) {
        $passlign = getparam('passlign');
    } else {
        $passlign = 1;
    }

    // switch à faire sur LDC
    if (($submit == 'L') and (getparam('ModeleL') !== '')) { //Chargement d'un fichier modèle
        $csv_modele = file(getparam('ModeleL'), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($csv_modele as $v) {
            $t = explode(' -||- ', $v);
            $_REQUEST[$t[0]] = $t[1];
        }
        $Filtre = $_REQUEST['Filtre'];
        $Condition = $_REQUEST['Condition'];
        $Compare = $_REQUEST['Compare'];
    }
    if ($submit == 'D') {  // upload du fichier CSV
        // Stockage du fichier chargé
        $uploadfile = $config->get('UPLOAD_DIR') . '/' . $session->get('user')['login'] . '.csv';
        if (!move_uploaded_file($_FILES['Actes']['tmp_name'], $uploadfile)) {
            msg('033 : Impossible de ranger le fichier dans "' . $config->get('UPLOAD_DIR') . '".');
            $missingargs = true;
        }
    }

    if ($submit == 'C') {
        // chargement effectif
        $missingargs = false;
        $csv = file(getparam('fileuploaded'));
        if ($TypeActes == 'V' and $typedoc == "" and empty($_REQUEST[$zonelibelle])) {
            msg($meserrdivers1);
            $resume = true;
        } elseif ($TypeActes == 'V' and $typedoc <> "" and !empty($_REQUEST[$zonelibelle])) {
            msg($meserrdivers2);
            $resume = true;
        } else { // all ok
            foreach ($csv as $line_num => $line) { // par ligne
                my_flush(200);
                if ($line_num >= getparam('passlign') and (time() - $T0 < $Max_time)) { // ligne à traiter
                    $curr_line = $line_num;
                    $listzone[0] = "";
                    $listzone[1] = "";
                    $listzone[2] = "";
                    $listdata[0] = "";
                    $listdata[1] = "";
                    $listdata[2] = "";
                    // -- découpage et construction des données
                    if (!$isUTF8) {
                        $line = ea_utf8_encode($line);
                    } // ADLC 24/09/2015
                    $acte = explode_csv($line);
                    if (!empty($Filtre) and !comparer($acte[$Filtre - 1], $Compare, $Condition)) {
                        $cptfiltre++;
                    } else { // Ok Filtre
                        $i = 0;
                        foreach ($mdb as $zone) {
                            if (array_key_exists('ZID' . $i, $_REQUEST) and $zone['ZID'] == $_REQUEST['ZID' . $i]) { // traiter ce champ
                                if ($_REQUEST['Zone' . $i] > 0) {  // la zone à été notée à charger
                                    $listzone[$zone['BLOC']] .= $zone['ZONE'] . ",";  // liste des champs
                                    if ($_REQUEST['Trait' . $i] == "0") {
                                        $info = trim($acte[$_REQUEST['Zone' . $i] - 1]);
                                    } else {
                                        $info = traitement($_REQUEST['Zone' . $i] - 1, $zone['TYP'], $_REQUEST['Trait' . $i]);
                                    }
                                    $data[$zone['ZONE']] = $info;
                                    if (($zone['ZONE'] == "NOM" or ($zone['ZONE'] == "C_NOM" and $TypeActes == 'M')) and $info == "") {
                                        $info = "N";
                                    }
                                    $listdata[$zone['BLOC']] .= "'" . sql_quote($info) . "',";     // Bloc 0 =communs, 1= 1er intervenant, 2 = 2d interv.

                                }
                                $i++; // suivant
                            }
                        }

                        /*
                        echo '<p>'.$line;
                        echo '<p>L1='.$listdata[1];
                        echo '<p>L2='.$listdata[2];
                        echo '<p>L0='.$listdata[0];
                        */
                        //echo '<pre>'; print_r($data); echo '</pre>';
                        // -- vérifications
                        $dateincomplete = false; // pour vérification dans ajuste_date et détection des doublons

                        $ladate = "";
                        $MauvaiseAnnee = 0;
                        ajuste_date($data["DATETXT"], $ladate, $MauvaiseAnnee);  // creée ladate en sql
                        $datetxt = trim($data["DATETXT"]);
                        if (! $communecsv) { // aligne $data["COMMUNE"] et $commune sauf si data["COMMUNE"] est vide pour conserver $commune cohérent
                            $data["COMMUNE"] = $commune;
                        } elseif ($data["COMMUNE"] !== '') {
                            $commune = $data["COMMUNE"];
                        } // Attention il faut conserver $commune cohérent pour les relances
                        if (($departcsv) and ($data["COMMUNE"] !== '')) { // ne pas récupérer une mauvaise valeur de depart. qd commune vide
                            $depart = $data["DEPART"];
                        }
                        $nom     = trim(mb_substr($data["NOM"], 0, metadata("TAILLE", "NOM")));
                        if (array_key_exists('PRE', $data)) {
                            $pre     = trim(mb_substr($data["PRE"], 0, metadata("TAILLE", "PRE")));
                        } else {
                            $pre = "";
                        }
                        $log = '<br />' . $ntype . ' ' . $nom . ' ' . $pre;
                        $cnom = "";
                        if ($TypeActes == "M") {
                            $cnom = trim(mb_substr($data["C_NOM"], 0, metadata("TAILLE", "C_NOM")));
                            $cpre = trim(mb_substr($data["C_PRE"], 0, metadata("TAILLE", "C_PRE")));
                            $log .= ' X ' . $cnom . ' ' . $cpre;
                        }
                        if ($TypeActes == "V") {
                            if (isset($data["C_NOM"])) {
                                $cnom = trim(mb_substr($data["C_NOM"], 0, metadata("TAILLE", "C_NOM")));
                            } else {
                                $cnom = "";
                            }
                            if (isset($data["C_PRE"])) {
                                $cpre = trim(mb_substr($data["C_PRE"], 0, metadata("TAILLE", "C_PRE")));
                            } else {
                                $cpre = "";
                            }
                            if (!empty($cnom) or !empty($cpre)) {
                                $log .= ' & ' . $cnom . ' ' . $cpre;
                            }
                        }
                        $log .= ' le ' . $datetxt . ' à ' . $data["COMMUNE"] . " : ";
                        if (($SuprPatVid == 1) and ($nom == "")) {
                            // pas de patronyme (ni de "N")
                            $cptign++;
                            if ($logKo == 1) {
                                echo $log . " INCOMPLET (" . $curr_line . ") -> Ignoré";
                            }
                        } elseif ($data["COMMUNE"] == "") {
                            // acte sans commune
                            $cptign++;
                            if ($logKo == 1) {
                                echo $log . "PAS DE COMMUNE (" . $curr_line . ") -> Ignoré";
                            }
                        } elseif (($SuprPatVid == 1) and ($cnom == "") and ($TypeActes == "M")) {
                            // pas de patronyme d'épouse (ni de "N")
                            $cptign++;
                            if ($logKo == 1) {
                                echo $log . " INCOMPLET (" . $curr_line . ") -> Ignoré";
                            }
                        } elseif (trim($data["DATETXT"]) == "00/00/0000") {
                            // acte avec date vide
                            $cptign++;
                            if ($logKo == 1) {
                                echo $log . "DATE MANQUANTE (" . $curr_line . ") -> Ignoré";
                            }
                        } elseif (($AnneeVide == 1) and ($MauvaiseAnnee == 1)) {
                            // acte avec année incomplète (testée dans ajuste_date)
                            $cptign++;
                            if ($logKo == 1) {
                                echo $log . "ANNEE INVALIDE (" . $curr_line . ") -> Ignoré";
                            }
                        } else {  // complet
                            if ($nom == "") {
                                $nom = "N";
                            }
                            //if ($cnom=="") $cnom = "N";
                            if ($TypeActes == "M" and $SuprRedon == 1) {  // inversion éventuelle des mariages
                                $inversion = false;
                                // Recherche si épouse en 1er
                                $prem_pre = explode(' ', $pre, 2);
                                $sql = "SELECT * FROM " . $config->get('EA_DB') . "_prenom WHERE prenom = '" . sql_quote($prem_pre[0]) . "'";
                                $res = EA_sql_query($sql);
                                $nb = EA_sql_num_rows($res);
                                if ($nb > 0) {
                                    // vérifier que cpre n'est pas feminin
                                    $prem_pre = explode(' ', $cpre, 2);
                                    $sql = "SELECT * FROM " . $config->get('EA_DB') . "_prenom WHERE prenom = '" . sql_quote($prem_pre[0]) . "'";
                                    $res = EA_sql_query($sql);
                                    $nb = EA_sql_num_rows($res);
                                    if ($nb == 0) {
                                        $inversion = true;
                                        $log .= ' ** Permuté ** ';
                                        permuter($listdata[1], $listdata[2]);
                                    }
                                }
                                // recherche doublon inversé
                                $condit = "DATETXT='" . $datetxt . "' AND NOM='" . sql_quote($cnom) . "' AND PRE='" . sql_quote($cpre) . "'";
                                $condit .= " AND C_NOM='" . sql_quote($nom) . "' AND C_PRE='" . sql_quote($pre) . "'";
                                if ($TypeActes == 'V') {
                                    $condit .= " AND LIBELLE='" . sql_quote($typedoc) . "'";
                                }
                                $sql = "SELECT ID FROM " . $table .
                                    " WHERE COMMUNE='" . sql_quote($commune) . "' AND DEPART='" . sql_quote($depart) . "' AND " . $condit . ";";

                                $result = EA_sql_query($sql);
                                $nbx = EA_sql_num_rows($result);
                                if ($nbx > 0) {
                                    //echo ' ** INVERSE DETECTE **';
                                }
                            } else {
                                $nbx = 0;
                            }
                            if ($SuprRedon == 1) {
                                // Détection si déjà présent
                                $condit = "DATETXT='" . $datetxt . "' AND NOM='" . sql_quote($nom) . "' AND PRE='" . sql_quote($pre) . "'";
                                if (($TypeActes == "M") or ($TypeActes == 'V' and !empty($cnom))) {
                                    $condit .= " AND C_NOM='" . sql_quote($cnom) . "'";
                                }
                                if (($TypeActes == "M") or ($TypeActes == 'V' and !empty($cpre))) {
                                    $condit .= " AND C_PRE='" . sql_quote($cpre) . "'";
                                }
                                if ($TypeActes == 'V') {
                                    $condit .= " AND LIBELLE='" . sql_quote($typedoc) . "'";
                                }
                                $sql = "SELECT ID FROM " . $table .
                                    " WHERE COMMUNE='" . sql_quote($commune) . "' AND DEPART='" . sql_quote($depart) . "' AND " . $condit . ";";
                                $result = EA_sql_query($sql);
                                $nb = EA_sql_num_rows($result);
                            } else {
                                $nb = 0;
                            }
                            if ($TypeActes == 'V' and !empty($typedoc)) {  // ajout du type d'acte divers global
                                $listzone[0] .= "LIBELLE,";
                                $listdata[0] .= "'" . sql_quote($typedoc) . "',";
                            }

                            if (!$communecsv) {
                                $listzone[0] .= "COMMUNE,";
                                $listdata[0] .= "'" . sql_quote($commune) . "',";
                            }
                            if (!$departcsv) {
                                $listzone[0] .= "DEPART,";
                                $listdata[0] .= "'" . sql_quote($depart) . "',";
                            }
                            if (!$photocsv) {
                                $listzone[0] .= "PHOTOGRA,";
                                $listdata[0] .= "'" . sql_quote($photo) . "',";
                            }
                            if (!$transcsv) {
                                $listzone[0] .= "RELEVEUR,";
                                $listdata[0] .= "'" . sql_quote($trans) . "',";
                            }
                            if (!$verifcsv) {
                                $listzone[0] .= "VERIFIEU,";
                                $listdata[0] .= "'" . sql_quote($verif) . "',";
                            }

                            if (($SuprRedon == 1) and ($nb + $nbx) > 0 and !($dateincomplete)) {
                                $reqmaj = "";
                            } else { // ADD
                                $action = "AJOUT";
                                $reqmaj = "INSERT INTO " . $table
                                    . " (BIDON,TYPACT," . $listzone[1] . $listzone[2] . $listzone[0]
                                    . "LADATE,DEPOSANT,DTDEPOT,DTMODIF)"
                                    . " VALUES('CSV','" . $TypeActes . "'," . $listdata[1] . $listdata[2] . $listdata[0]
                                    . "'" . $ladate . "'," . $deposant . ",'" . $today . "','" . $today . "');";
                            } // ADD
                            //echo "<p>".$reqmaj;
                            if ($reqmaj <> '') {
                                if ($result = EA_sql_query($reqmaj)) {
                                    if ($logOk == 1) {
                                        echo $log . $action . ' -> Ok.';
                                    }
                                    $cptadd++;
                                } else {
                                    echo ' -> Erreur : ';
                                    echo '<p>' . EA_sql_error() . '<br />' . $reqmaj . '</p>';
                                }
                            } else {
                                if ($logRed == 1) {
                                    echo $log . 'Déjà présent (' . $curr_line . ') ';
                                }
                                $cptdeja++;
                            }
                        } // Ok Filtre
                    }  // complet
                } // ligne à traiter
            }    // par ligne
            if ($curr_line < count($csv) - 1) {  // Si interruption
                msg("Temps maximum d'exécution écoulé");
                echo '<p>' . ($curr_line + 1) . ' lignes déjà traitées;</p>';
                echo '<p>Il reste ' . (count($csv) - $curr_line) . ' lignes à traiter.</p>';
                $resume = true;
                $passlign = $curr_line + 1;
            }
        } // all ok
    } // chargement effectif
    // CAS d'affichage des champs
    if (isin("DVARSL", $submit) >= 0 or $resume) { // liste des champs (+fiche exemple?)
        if ($resume) {
            echo '<h2>Poursuite du chargement</h2>';
        } else {
            echo '<h2>Lecture des affectations de zones</h2>';
        }
        if (isset($_REQUEST['fileuploaded'])) {
            $uploadfile = getparam('fileuploaded');
        }

        $csv = file($uploadfile);
        $line = $csv[0];
        if (!$isUTF8) {
            $line = ea_utf8_encode($line);
        } // ADLC 24/09/2015
        $acte = explode_csv($line);

        $cookname = "charge" . getparam('TypeActes');
        if (isset($_COOKIE[$cookname]) and strlen($_COOKIE[$cookname]) > 20) {
            $cookparam = $_COOKIE[$cookname];
        } else {
            $cookparam = str_repeat("0-0-0+", 2);
        }
        $presetslist = explode("+", $cookparam);
        $presets = array();
        $p = 0;
        foreach ($presetslist as $une) {
            if (isin($une, "-") >= 0) {
                $elem = explode("-", $une);
                $presets[$elem[0]][0] = $elem[1];  // source
                $presets[$elem[0]][1] = $elem[2];  // traitement
            }
        }

        echo '<form method="post" enctype="multipart/form-data">';
        echo '<input type="hidden" name="fileuploaded" value="' . $uploadfile . '">';
        echo '<input type="hidden" name="TypeActes" value="' . $TypeActes . '">';
        echo '<input type="hidden" name="Commune" value="' . htmlentities($commune, ENTITY_REPLACE_FLAGS, ENTITY_CHARSET) . '">';
        echo '<input type="hidden" name="CommuneCsv" value="' . $communecsv . '" />';
        echo '<input type="hidden" name="Depart" value="' . htmlentities($depart, ENTITY_REPLACE_FLAGS, ENTITY_CHARSET) . '">';
        echo '<input type="hidden" name="DepartCsv" value="' . $departcsv . '">';
        echo '<input type="hidden" name="deposant" value="' . $deposant . '">';
        echo '<input type="hidden" name="AnneeVide" value="' . getparam('AnneeVide', 0) . '">';
        echo '<input type="hidden" name="SuprRedon" value="' . getparam('SuprRedon', 0) . '">';
        echo '<input type="hidden" name="SuprPatVid" value="' . getparam('SuprPatVid', 0) . '">';
        echo '<input type="hidden" name="LogOk"  value="' . getparam('LogOk', 0) . '">';
        echo '<input type="hidden" name="LogKo"  value="' . getparam('LogKo', 0) . '">';
        echo '<input type="hidden" name="LogRed" value="' . getparam('LogRed', 0) . '">';

        echo '<table class="m-auto" summary="Formulaire">';
        echo '<tr>';
        echo '<td><strong>Type d\'actes :</strong></td>';
        echo '<td colspan="2">' . $ntype . '</td>';
        echo '</tr>';
        if (!$communecsv) {
            echo '<tr>';
            echo '<td><strong>Origine :</strong></td>';
            echo '<td colspan="2">' . $commune . ' [' . $depart . ']' . '</td>';
            echo '</tr>';
        }
        if (isset($_FILES['Actes']['name'])) {
            echo '<tr>';
            echo '<td><strong>Fichier téléchargé :</strong></td>';
            echo '<td colspan="2">' . $_FILES['Actes']['name'] . '</td>';
            echo '</tr>';
        }
        echo '<tr>';
        echo '<td><strong>Lignes à passer :</strong></td>';
        echo '<td colspan="2"><input type="text" name="passlign" value="' . $passlign . '" size="5"> (au chargement effectif)</td>';
        echo '</tr>';
        if ($TypeActes == "V") {
            if ($submit <> 'C') {
                echo '<tr>';
                echo '<td><strong>Type de document :</strong></td>';
                echo '<td colspan="2"><input type="text" name="typedoc" value="' . $typedoc . '" size="30"></td>';
                echo '</tr>';
            } else {
                echo '<input type="hidden" name="typedoc" value="' . $typedoc . '">';
            }
        }

        if ($submit <> 'C') {
            if (getparam('ModeleL') !== '') { // Affiche le nom du modèle chargé
                echo '<tr><td><strong>Modèle : </strong></td><td colspan="2">' . getparam('ModeleL') . '</td></tr>';
            }
            echo '<tr><td colspan="3">&nbsp;</td></tr>';
            echo '<tr class="rowheader">';
            echo '<th>Votre fichier</th>';
            echo '<th>Destination</th>';
            echo '<th>Traitement</th>';
            echo '</tr>';
        }
        $i = 0;
        foreach ($mdb as $zone) {
            /*
            [0] => Array
                    (
                            [ZID] => 1004
                            [ZONE] => CODCOM
                            [GROUPE] => A1
                            [TAILLE] => 12
                            [OBLIG] => N
                            [ETIQ] => Code INSEE
                            [TYP] => TXT
                            [AFFICH] => A
                            [GETIQ] => Acte de naissance
        )
            */
            if (($zone['ZONE'] == 'CODCOM'   and !($communecsv and isin('OFA', metadata('AFFICH', 'CODCOM')) >= 0))
                or ($zone['ZONE'] == 'COMMUNE'  and !($communecsv))
                or ($zone['ZONE'] == 'CODDEP'   and !($departcsv and isin('OFA', metadata('AFFICH', 'CODDEP')) >= 0))
                or ($zone['ZONE'] == 'DEPART'   and !($departcsv))
                or ($zone['ZONE'] == 'PHOTOGRA' and !($photocsv))
                or ($zone['ZONE'] == 'RELEVEUR' and !($transcsv))
                or ($zone['ZONE'] == 'VERIFIEU' and !($verifcsv))
                or ($zone['ZONE'] == 'DEPOSANT')
            ) {
                // ne rien lire car déjà lu ou automatique
                // echo "<p>Code ".$zone['ZONE']."déja vu ";
            } else {

                if (isset($_REQUEST['Zone' . $i])) {
                    $lechamp = $_REQUEST['Zone' . $i];
                } elseif (isset($presets[$zone['ZID']][0])) {
                    $lechamp = $presets[$zone['ZID']][0];
                } else {
                    $lechamp = 0;
                }

                if (isset($_REQUEST['Trait' . $i])) {
                    $letrait = $_REQUEST['Trait' . $i];
                } elseif (isset($presets[$zone['ZID']][1])) {
                    $letrait = $presets[$zone['ZID']][1];
                } else {
                    $letrait = 0;
                }

                if ($submit == 'R') { // Remise à blanc
                    $lechamp = 0;
                    $letrait = 0;
                }

                echo '<input type="hidden" name="ZID' . $i . '" value="' . $zone['ZID'] . '">';
                if ($submit == 'C') {
                    // Chargement : on n'affiche plus les mapping de zones
                    echo '<input type="hidden" name="Zone' . $i . '" value="' . $_REQUEST['Zone' . $i] . '">';
                    echo '<input type="hidden" name="Trait' . $i . '" value="' . $_REQUEST['Trait' . $i] . '">';
                } else {
                    // Affichage ud mapping des zones et traitements
                    echo '<tr class="row' . (fmod($i, 2)) . '">';
                    echo '<td>';
                    listbox_cols('Zone' . $i, $lechamp);
                    echo '</td>';
                    echo '<td>--> ' . $zone['GETIQ'] . ' : ' . $zone['ETIQ'] . '</td>';
                    echo '<td>';
                    listbox_trait('Trait' . $i, $zone['TYP'], $letrait);
                    echo '</td>';
                    echo '</tr>';
                }
                $i++;
            }
        }
        // Masque du Filtre
        if ($submit == 'C') {
            echo '<input type="hidden" name="Filtre" value="' . $Filtre . '">';
            echo '<input type="hidden" name="Condition" value="' . $Condition . '">';
            echo '<input type="hidden" name="Compare" value="' . $Compare . '">';
        } else {
            echo '<tr><td colspan="3"><br><strong>Filtre éventuel sur le fichier CSV</strong></td></tr>';
            echo '<tr class="row0">';
            echo '<td>';
            listbox_cols('Filtre', $Filtre);
            echo '</td>';
            echo '<td align="center">';
            listbox_trait('Condition', "TST", $Condition);
            echo '</td>';
            echo '<td><input type="text" name="Compare" value="' . $Compare . '" size="20">';
            echo '</td>';
            echo '</tr>';
        }

        if (($submit == 'S') and (getparam('ModeleS') !== '')) { // Sauvegarde modèle
            $Contenu_a_sauver = 'TypeActes' . ' -||- ' . $TypeActes;
            file_put_contents($config->get('UPLOAD_DIR') . '/' . $TypeActes . '-' . getparam('ModeleS') . '.m_csv', $Contenu_a_sauver . "\r\n");
            foreach ($_REQUEST as $k => $v) {
                $Contenu_a_sauver = $k . ' -||- ' . $v;
                if (in_array(substr($k, 0, 3), array('ZID', 'Tra', 'Zon')) or (in_array($k, array('Filtre', 'Condition', 'Compare')))) {
                    file_put_contents($config->get('UPLOAD_DIR') . '/' . $TypeActes . '-' . getparam('ModeleS') . '.m_csv', $Contenu_a_sauver . "\r\n", FILE_APPEND);
                }
            }
        }
        if ($submit == 'V') {  // Voir un exemple
            if (isset($_REQUEST['nofiche'])) {
                $nofiche = getparam('nofiche') + 1;
            } else {
                $nofiche = 1;
            }
            if (!isset($csv[$nofiche])) { // On arrive au bout du fichier on repart sur la 1ère ligne
                $nofiche = 1;
            }
            $line = $csv[$nofiche];
            if (!$isUTF8) {
                $line = ea_utf8_encode($line);
            } // ADLC 24/09/2015
            $acte = explode_csv($line);
            if (!empty($Filtre)) {
                while ((!comparer($acte[$Filtre - 1], $Compare, $Condition)) and ($nofiche < count($csv))) {
                    // echo "Passer ".$nofiche;
                    $nofiche++;
                    $acte = explode_csv($csv[$nofiche]);
                }
            }
            // Affichage de la fiche exemple
            echo '<tr><td colspan="3"<input type="hidden" name="nofiche" value="' . $nofiche . '"></td></tr>';
            echo '<tr><td colspan="3">';
            echo '<table class="m-auto" summary="Fiche exemple">';
            echo '<tr class="rowheader">';
            echo '<th>Fiche exemple</th>';
            echo '<th>Données de la ligne ' . $nofiche . '</th>';
            echo '</tr>';

            if ($TypeActes == 'V' and $typedoc == "" and empty($_REQUEST[$zonelibelle])) {
                msg($meserrdivers1);
            }
            if ($TypeActes == 'V' and $typedoc <> "" and !empty($_REQUEST[$zonelibelle])) {
                msg($meserrdivers2);
            }

            $i = 0;
            $j = 0;
            $data = array();
            foreach ($mdb as $zone) {
                // extraction des données à afficher pour l'exemple en suivant les consignes du modèle de chargement
                if (array_key_exists('ZID' . $i, $_REQUEST) and $zone['ZID'] == $_REQUEST['ZID' . $i]) { // traiter ce champ
                    //echo "<p> vu ".$i."->".$zone['ZID']." ".$zone['ZONE'];
                    if ($zone['OBLIG'] == 'Y' and $_REQUEST['Zone' . $i] == 0) { // zone obligatoire
                        if (!($TypeActes == 'V' and $typedoc <> "")) {  // cas particulier pour le libelle des actes divers : ne plus vérifier, déjà fait + haut
                            msg('Vous devez affecter un contenu à (' . $zone['GETIQ'] . ' : ' . $zone['ETIQ'] . ')');
                        }
                    }
                    if ($_REQUEST['Zone' . $i] > 0) {  // la zone à été notée à charger
                        $j++;
                        //echo "==>".trim($acte[$_REQUEST['Zone'.$i]-1]);
                        echo '<tr class="row' . (fmod($j, 2)) . '">';
                        echo '<td> &nbsp;' . $zone['GETIQ'] . ' : ' . $zone['ETIQ'] . '</td>';
                        if ($_REQUEST['Trait' . $i] == "0") {
                            $info = trim($acte[$_REQUEST['Zone' . $i] - 1]);
                        } else {
                            $info = traitement($_REQUEST['Zone' . $i] - 1, $zone['TYP'], $_REQUEST['Trait' . $i]);
                        }
                        $data[$zone['ZONE']] = $info;
                        echo '<td> &nbsp;' . $info . '</td>';
                        echo '</tr>';
                    }
                    $i++; // suivant
                }
                //else
                //echo "<p>pas vu ".$i."->".$zone['ZID']." ".$zone['ZONE'];
            }
            //{ print '<pre>';  print_r($data); echo '</pre>'; }
            $j++;
            echo '<tr class="row' . (fmod($j, 2)) . '">';
            echo '<td> &nbsp;Décodage de la date</td>';
            $info = "";
            $MauvaiseDate = 0;
            $info = ajuste_date($data["DATETXT"], $info, $MauvaiseDate);  // info est docn remise en forme
            if ($MauvaiseDate > 0) {
                $info .= " NON VALIDE !";
            }
            echo '<td> &nbsp;' . $info . '</td>';
            echo '</tr>';

            echo '</table>';
            echo '</td></tr>';
        }
        echo " <tr><td colspan=\"2\">";
        echo '<input type="hidden" name="photo" value="' . htmlentities($photo, ENTITY_REPLACE_FLAGS, ENTITY_CHARSET) . '">';
        echo '<input type="hidden" name="trans" value="' . htmlentities($trans, ENTITY_REPLACE_FLAGS, ENTITY_CHARSET) . '">';
        echo '<input type="hidden" name="verif" value="' . htmlentities($verif, ENTITY_REPLACE_FLAGS, ENTITY_CHARSET) . '">';
        echo '<input type="hidden" name="photocsv" value="' . $photocsv . '">';
        echo '<input type="hidden" name="transcsv" value="' . $transcsv . '">';
        echo '<input type="hidden" name="verifcsv" value="' . $verifcsv . '">';
        echo '<input type="hidden" name="action" value="phase_2">';

        if ($submit == 'C') {
            echo '<input type="submit" name="submitC" value=" Relancer le chargement ">';
        } else {
            echo '<input type="submit" name="submitR" value=" Remise à blanc ">';
            echo '<input type="submit" name="submitV" value=" VOIR un exemple ">';
            echo '<input type="submit" name="submitC" value=" CHARGER maintenant ">';
            echo '<br><br>';
            echo '<select name="ModeleL" size="1"><option value="" selected="selected">--</option>';
            foreach (glob($config->get('UPLOAD_DIR') . '/' . $TypeActes . '-*.m_csv') as $v) {
                echo '<option value="' . $v . '">' . $v . '</option>';
            }
            echo '</select>';
            echo '<input type="submit" name="submitL" value=" Charger Modèle ">';
            echo '<input type="texte" name="ModeleS" value="">';
            echo '<input type="submit" name="submitS" value=" Sauver Modèle ">';
        }
        echo "</td></tr>";
        echo "</table>";
        echo "</form>";
    } // 1er chargement

} // fichier d'actes

//Si pas tout les arguments nécessaire, on affiche le formulaire
if ($missingargs) {
    if (getparam('action') == '') {  // parametres par défaut
        if (isset($_COOKIE['chargeCSV'])) {
            $chargeCSV  = $_COOKIE['chargeCSV'] . str_repeat(" ", 10);
        } else {
            $chargeCSV  = "000111";
        }
        $AnneeVide  = $chargeCSV[0];
        $SuprRedon  = $chargeCSV[1];
        $SuprPatVid = $chargeCSV[2];
        $logOk      = $chargeCSV[3];
        $logKo      = $chargeCSV[4];
        $logRed     = $chargeCSV[5];
    }

    echo '<form method="post" enctype="multipart/form-data">';
    echo '<h2 align="center">Chargement de données CSV</h2>';
    echo '<table class="m-auto" summary="Formulaire">';
    echo "<tr>";
    echo '<td>Type des actes : </td>';
    echo '<td>';
    echo '<input type="radio" name="TypeActes" value="N">Naissances<br>';
    echo '<input type="radio" name="TypeActes" value="M">Mariages<br>';
    echo '<input type="radio" name="TypeActes" value="D">Décès<br>';
    echo '<input type="radio" name="TypeActes" value="V">Actes divers <br>';
    echo '</td>';
    echo "</tr>";
    echo "<tr><td colspan=\"2\">&nbsp;</td></tr>";
    echo "<tr>";
    echo '<td>Fichier CSV : </td>';
    echo '<td><input type="file" size="62" name="Actes">' . "</td>";
    echo "</tr>";
    echo "<tr>";
    echo '<td>' . metadata('ETIQ', 'COMMUNE') . ' : </td>';
    echo '<td><input type="text" size="40" name="Commune" value="' . $commune . '">';
    echo ' ou <input type="checkbox" name="CommuneCsv" value="1" ' . ($communecsv == 1 ? ' checked' : '') . '> Lu dans le CSV ';
    echo "</td>";
    echo "</tr>";
    echo "<tr>";
    echo '<td>' . metadata('ETIQ', 'DEPART') . ' : </td>';
    echo '<td><input type="text" size="40" name="Depart" value="' . $depart . '">';
    echo ' ou <input type="checkbox" name="DepartCsv" value="1" ' . ($departcsv == 1 ? ' checked' : '') . '> Lu dans le CSV ';
    echo "</td>";
    echo "</tr>";

    if (isin('OFA', metadata('AFFICH', 'PHOTOGRA')) >= 0) {
        echo "<tr>";
        echo '<td>' . metadata('ETIQ', 'PHOTOGRA') . ' : </td>';
        echo '<td><input type="text" size="40" name="photo" value="' . $photo . '">';
        echo ' ou <input type="checkbox" name="photocsv" value="1" ' . ($photocsv == 1 ? ' checked' : '') . '> Lu dans le CSV ';
        echo "</td>";
        echo "</tr>";
        echo "<tr>";
    }
    if (isin('OFA', metadata('AFFICH', 'RELEVEUR')) >= 0) {
        echo "<tr>";
        echo '<td>' . metadata('ETIQ', 'RELEVEUR') . ' : </td>';
        echo '<td><input type="text" size="40" name="trans" value="' . $trans . '">';
        echo ' ou <input type="checkbox" name="transcsv" value="1" ' . ($transcsv == 1 ? ' checked' : '') . '> Lu dans le CSV ';
        echo "</td>";
        echo "</tr>";
        echo "<tr>";
    }
    if (isin('OFA', metadata('AFFICH', 'VERIFIEU')) >= 0) {
        echo "<tr>";
        echo '<td>' . metadata('ETIQ', 'VERIFIEU') . ' : </td>';
        echo '<td><input type="text" size="40" name="verif" value="' . $verif . '">';
        echo 'ou <input type="checkbox" name="verifcsv" value="1" ' . ($verifcsv == 1 ? ' checked' : '') . '> Lu dans le CSV ';
        echo "</td>";
        echo "</tr>";
        echo "<tr>";
    }
    echo "<tr><td colspan=\"2\">&nbsp;</td></tr>";
    echo "<tr>";
    echo '<td>Filtrage des données : </td>';
    echo '<td>';
    echo '<input type="checkbox" name="AnneeVide" value="1"' . ($AnneeVide == 1 ? ' checked' : '') . '>Eliminer les actes dont l\'année est incomplète (ex. 17??)<br>';
    echo '<input type="checkbox" name="SuprRedon" value="1"' . ($SuprRedon == 1 ? ' checked' : '') . '>Eliminer les actes ayant mêmes noms et prénoms<br>';
    echo '<input type="checkbox" name="SuprPatVid" value="1"' . ($SuprPatVid == 1 ? ' checked' : '') . '>Eliminer les actes dont le patronyme est vide<br>';
    echo '</td>';
    echo "</tr>";
    echo "<tr><td colspan=\"2\">&nbsp;</td></tr>";
    echo "<tr>";
    echo '<td>Contrôle des résultats : </td>';
    echo '<td>';
    echo '<input type="checkbox" name="LogOk" value="1"' . ($logOk == 1 ? ' checked' : '') . '>Actes chargés';
    echo '<input type="checkbox" name="LogKo" value="1"' . ($logKo == 1 ? ' checked' : '') . '>Actes erronés';
    echo '<input type="checkbox" name="LogRed" value="1"' . ($logRed == 1 ? ' checked' : '') . '>Actes redondants<br>';
    echo '</td>';
    echo "</tr>";

    if ($session->get('user')['level'] >= 8) {
        echo "<tr><td colspan=\"2\">&nbsp;</td></tr>";
        echo "<tr>";
        echo '<td>Déposant : </td>';
        echo '<td>';
        listbox_users("deposant", $session->get('user')['ID'], $config->get('DEPOSANT_LEVEL'));
        echo '</td>';
        echo "</tr>";
    } else {
        echo '<input type="hidden" name="deposant" value="' . $session->get('user')['ID'] . '">';
    }
    echo "<tr><td colspan=\"2\">&nbsp;</td></tr>";
    echo "<tr><td></td>";
    echo '<input type="hidden" name="action" value="phase_1">';
    echo '<td><button type="reset" class="btn">Effacer</button>';
    echo '<button type="submit" class="btn" name="submitD">Charger</button>';
    echo '<a href="'.$root.'/admin/aide/chargecsv.html" class="btn" target="_blank">Aide</a>';
    echo "</td></tr>";
    echo "</table>";
    echo "</form>";
} else {
    if (isset($nom)) {
        echo '<hr /><p>';
        if ($cptadd > 0) {
            echo 'Actes ajoutés  : ' . $cptadd;
            writelog('Ajout CSV ' . $ntype, $commune, $cptadd);
        }
        if ($cptfiltre > 0) {
            echo '<br>Actes filtré  : ' . $cptfiltre;
        }
        if ($cptign > 0) {
            echo '<br>Actes ignorés  : ' . $cptign;
        }
        if ($cptdeja > 0) {
            echo '<br>Actes redondants  : ' . $cptdeja;
        }
        echo '<br>Durée du traitement  : ' . (time() - $T0) . ' sec.';
        echo '</p>';
        if (!$resume) {  // fini
            echo '<p>Voir la liste des actes de ';
            echo '<a href="' . $root . $script . '?xcomm=' . stripslashes($commune . ' [' . $depart . ']') . '"><b>' . stripslashes($commune) . '</b></a>';
            echo '</p>';
            if ($communecsv) {
                maj_stats($TypeActes, $T0, $path, "A");
            } else {
                maj_stats($TypeActes, $T0, $path, "C", $commune, $depart);
            }
        }
    }
}
echo '</div>';
echo '</div>';
include(__DIR__ . '/../templates/front/_footer.php');
return (ob_get_clean());
