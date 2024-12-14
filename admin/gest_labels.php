<?php
define('ADM', 10); // Compatibility only
$admtxt = 'Gestion '; // Compatibility only
require(__DIR__ . '/../next/bootstrap.php');
require(__DIR__ . '/../next/_COMMUN_env.inc.php'); // Compatibility only

//define('EA_MASTER',"Y"); // pour editer les zones "Techniques"

//------------------------------------------------------------------------------

function show_grp($grp, $current, $barre)
{
    global $bases;
    if ($barre) {
        echo ' | ';
    }
    if ($grp == $current) {
        echo '<strong>' . $bases[$grp] . '</strong>';
    } else {
        echo '<a href="gest_labels.php?file=' . $grp . '">' . $bases[$grp] . '</a>';
    }
}

//------------------------------------------------------------------------------

function alaligne($texte)
{
    // insert des BR pour provoquer des retour à la ligne
    $order   = array("\r\n", "\n", "\r");
    $replace = '<br />';
    // Traitement du premier \r\n, ils ne seront pas convertis deux fois.
    return str_replace($order, $replace, $texte);
}

//------------------------------------------------------------------------------

$root = "";
$path = "";
$lg = $GLOBALS['lg'];

//**************************** ADMIN **************************

pathroot($root, $path, $xcomm, $xpatr, $page);

$userlogin = "";
$userlevel = logonok(9);
while ($userlevel < 9) {
    login($root);
}

ob_start();
open_page("Paramétrage des étiquettes", $root);
navadmin($root, "Paramétrage des étiquettes");
?>
<script type="text/javascript">
	function changesigle() {
		form = document.forms["labels"];
		form.chsigle.value = "1";
		form.submit();
		return true;
	}
</script>
<?php
//{ print '<pre>';  print_r($_REQUEST); echo '</pre>'; }

zone_menu(ADM, $userlevel, array());//ADMIN STANDARD

echo '<div id="col_main_adm">';

menu_software('Q');

echo '<h2>Gestions des étiquettes des données</h2>';

$ok = false;
$missingargs = false;

$xfile  = getparam('file');
$xconfirm = getparam('xconfirm');
$lesigle = getparam('SIGLE');
$code_liste = '';
$sans_sigle_par_defaut = 'CestUnCodeBidonQuiNeRisquePasDExister-jkhdkfjpqsifjzpekflskjfnksdf';
$chsigle = getparam('chsigle'); // 1 : on vient de le changer --> reconstruire la liste

if ($xfile == '') {
    // Données postées
    $xfile = "N";
    $missingargs = true;  // par défaut
}
$files = array('N', 'M', 'D', 'V', 'X');
$bases = array('N' => 'Naissances', 'M' => 'Mariages', 'D' => 'Décès', 'V' => 'Divers (par défaut)', 'X' => 'Divers spécifiques');
$grpes = array('A0' => 'Technique', 'A1' => 'Document', 'D1' => 'Intéressé', 'D2' => 'Parents intéressé', 'F1' => 'Second intéressé', 'F2' => 'Parents 2d intéressé', 'T1' => 'Témoins', 'V1' => 'Références', 'W1' => 'Crédits', 'X0' => 'Gestion');
$gspec = array('D1', 'D2', 'F1', 'F2', 'T1');
$barre = false;
echo '<p align="center"><strong>Bases : </strong>';
foreach ($files as $file) {
    show_grp($file, $xfile, $barre);
    $barre = true;
}
echo '</p>';


//{ print '<pre>';  print_r($_REQUEST); echo '</pre>'; }

if (!$missingargs) {
    $oktype = true;

    if ($xconfirm == 'default') { // etiquettes "normales"
        // *** Vérification des données reçues
        $parnbr = getparam("parnbr");
        $i = 1;
        $cpt = 0;
        while ($i <= $parnbr) {
            $pzid  = getparam("zid_$i");
            $paffi = getparam("affi_$i");
            $petiq = htmlentities(getparam("etiq_$i"), ENTITY_REPLACE_FLAGS, ENTITY_CHARSET);
            if ($petiq <> "") { // interdit de mettre à blanc
                $request = "UPDATE " . EA_DB . "_metadb SET affich = '" . sql_quote($paffi) . "' WHERE ZID = '" . $pzid . "'";
                //echo "<p>".$request;
                $result = EA_sql_query($request);
                $cpt += EA_sql_affected_rows();
                $request = "UPDATE " . EA_DB . "_metalg SET etiq = '" . sql_quote($petiq) . "' WHERE ZID = '" . $pzid . "' AND LG='" . $lg . "'" ;
                $result = EA_sql_query($request);
                $cpt += EA_sql_affected_rows();
            }
            $i++;
        }
        $grpnbr = getparam("grpnbr");
        $j = 1;
        while ($j <= $grpnbr) {
            $grp  = getparam("grp_$j");
            $getiq = htmlentities(getparam("group_$j"), ENTITY_REPLACE_FLAGS, ENTITY_CHARSET);
            if ($getiq <> "") { // interdit de mettre à blanc
                $request = "UPDATE " . EA_DB . "_mgrplg SET getiq = '" . sql_quote($getiq) . "' WHERE grp = '" . $grp . "' AND LG='" . $lg . "' AND dtable='" . $xfile . "'";
                $result = EA_sql_query($request);
                $tt = EA_sql_affected_rows();
                //if ($tt>0) echo '<p>'.$request;
                $cpt += $tt;
            }
            $j++;
        }
        if ($cpt > 0) {
            msg("Sauvegarde : " . $cpt . " valeur(s) modifiée(s).", "info");
        }
    } // etiquettes "normale"

    if ($xconfirm == 'specifique' and $chsigle == 0) { // etiquettes "spécifiques" des actes divers
        // *** Vérification des données reçues
        $grpnbr = getparam("grpnbr");
        $j = 1;
        $cpt = 0;
        while ($j <= $grpnbr) {
            $grp  = getparam("grp_$j");
            $getiq = htmlentities(getparam("group_$j"), ENTITY_REPLACE_FLAGS, ENTITY_CHARSET);
            //echo "<p>!" . $getiq . "==" . grp_label($grp, 'V', $lg) . "!";
            $libelle_default = grp_label($grp, 'V', $lg);
            $libelle_reset_defaut = (($getiq == '') or ($getiq == $libelle_default) or (htmlentities(getparam("group_$j"), ENT_QUOTES, 'UTF-8') == $libelle_default)); // si vide ou valeur par défaut
            $lesigne_traite = $lesigle;
            if ($lesigne_traite == $sans_sigle_par_defaut) {
                $lesigne_traite = '';
            }
            $libelle_reset_defaut = ($libelle_reset_defaut and ($lesigne_traite != ''));
            $request = "SELECT count(*) AS CPT FROM " . EA_DB . "_mgrplg WHERE lg='" . $lg . "' AND dtable='V' AND grp='" . $grp . "' AND sigle='" . $lesigne_traite . "'";
            $result = EA_sql_query($request);
            $row = EA_sql_fetch_array($result);
            $request_exec = true;
            if ($row["CPT"] > 0) {
                if ($libelle_reset_defaut) {
                    $request = "DELETE FROM " . EA_DB . "_mgrplg WHERE grp = '" . $grp . "' AND LG='" . $lg . "' AND dtable='V' AND sigle='" . $lesigne_traite . "'" ;
                } else {
                    $request = "UPDATE " . EA_DB . "_mgrplg SET getiq = '" . sql_quote($getiq) . "' WHERE grp = '" . $grp . "' AND LG='" . $lg . "' AND dtable='V' AND sigle='" . $lesigne_traite . "'";
                }
            } elseif ($libelle_reset_defaut) {
                $request_exec = false;
            } else {
                $request = "INSERT into " . EA_DB . "_mgrplg (grp,dtable,lg,sigle,getiq) VALUE ('" . $grp . "','V','" . $lg . "','" . $lesigne_traite . "','" . sql_quote($getiq) . "')";
            }
            if ($request_exec) {
                $result = EA_sql_query($request);
                $tt = EA_sql_affected_rows();
                //echo '<p>'.$request;
                $cpt += $tt;
            }
            $j++;
        }
        if ($cpt > 0) {
            msg("Sauvegarde : " . $cpt . " valeur(s) modifiée(s).", "info");
        }
    }
}
echo '<h2 align="center">Etiquettes des ' . $bases[$xfile] . '</h2>';
echo '<form method="post" action="" name="labels">' . "\n";
echo '<table cellspacing="3" cellpadding="1" border="0" summary="Formulaire">' . "\n";

if ($xfile == "X") {
    $sigle = "";
    $j = 0;
    $prog = "gest_labels.php?file=" . $xfile;
    echo " <tr>\n";
    echo '  <td align="right"><b>Sigle des actes divers : </b></td>' . "\n";
    echo '  <td>';

    // COALESCE : traite les "null" comme vide
    $request = "SELECT DISTINCT COALESCE(SIGLE, '') AS SIGLE FROM " . EA_DB . "_div3 WHERE length(SIGLE)>0 ORDER BY SIGLE";
    optimize($request);
    //echo $request;
    if ($result = EA_sql_query($request)) {
        $i = 1;
        echo '<select name="SIGLE" onchange="changesigle()">' . "\n";
        echo '<option ' . selected_option($code_liste, $lesigle) . '>*** Liste ***</option>' . "\n";
        echo '<option ' . selected_option($sans_sigle_par_defaut, $lesigle) . '>** vide=Défaut **</option>' . "\n";
        while ($row = EA_sql_fetch_array($result)) {
            echo '<option ' . selected_option($row["SIGLE"], $lesigle) . '>' . $row["SIGLE"] . '</option>' . "\n";
            $i++;
        }
    }
    echo " </select>\n";
    echo "</td></tr>\n";
    $request = "SELECT DISTINCT LIBELLE, COALESCE(SIGLE, '') AS SIGLE FROM " . EA_DB . "_div3 WHERE COALESCE(SIGLE, '') = '" . $lesigle . "' ORDER BY LIBELLE";
    $format = ' %2$s '; // ' %1$s -> %2$s';
    if ($lesigle == $code_liste) {
        $request = "SELECT DISTINCT LIBELLE, COALESCE(SIGLE, '') AS SIGLE FROM " . EA_DB . "_div3 ORDER BY LIBELLE, SIGLE";
        $format .= '( %1$s )';
    }

    if ($lesigle == $sans_sigle_par_defaut) {
        $request = "SELECT DISTINCT LIBELLE, COALESCE(SIGLE, '') AS SIGLE FROM " . EA_DB . "_div3 WHERE COALESCE(SIGLE, '') = '' ORDER BY LIBELLE";
    }
    echo "<tr>\n";
    echo '  <td align="right" style="vertical-align:top;"><b>&nbsp;</b></td>' . "\n";
    echo '<td>';
    echo sprintf($format, 'Sigle', '<b>Libellé concerné</b>') . '<br /><br />' . "\n";

    optimize($request);
    if ($result = EA_sql_query($request)) {
        $i = 1;
        while ($row = EA_sql_fetch_array($result)) {
            if ($row["SIGLE"] == '') {
                $row["SIGLE"] = '"vide=Défaut"';
            }
            echo sprintf($format, $row["SIGLE"], $row["LIBELLE"]) . '<br />' . "\n";
            $i++;
        }
    }
    echo "</td></tr>\n";

    if ($lesigle != $code_liste) { // Affichage du pavé de saisie
        echo '<tr><th>Zone</th><th>Etiquette spécifique</th></tr>';
        foreach ($gspec as $curgrp) {
            $grptxt = grp_label($curgrp, 'V', $lg, $lesigle);
            $type_txt = ' Défaut ';
            if ($lesigle !== '') {
                $grptxt_d = grp_label($curgrp, 'V', $lg); // Le libellé par défaut
                if ($grptxt != $grptxt_d) {
                    $type_txt = ' <b>Spécial</b> ';
                }
            }
            $j++;
            echo '<tr class="row0">' . "\n";
            echo ' <input type="hidden" name="grp_' . $j . '"  value="' . $curgrp . '" />' . "\n";
            echo '  <td align="left"><i>&nbsp; ' . $grpes[$curgrp] . "</i> </td>\n";
            echo ' <td>';
            echo '<input type="text" name="group_' . $j . '" size="30" maxlength="50" value="' . $grptxt . '" />';
            echo $type_txt;
            echo '</td>';
            echo '</tr>';
        }

    }
    echo ' <tr><td align="right">' . "\n";
    echo '<input type="hidden" name="grpnbr"  value="' . $j . '" />' . "\n";
    echo '<input type="hidden" name="chsigle"  value="0" />' . "\n";
    echo '<input type="hidden" name="file"  value="' . $xfile . '" />' . "\n";
    echo '<input type="hidden" name="xconfirm" value="specifique" />' . "\n";
    // cas des groupes spéciaux
} else { // cas des etiquettes par défaut
    $notech = "";
    $leschoix = array("F - Si non vide", "O - Toujours", "A - Administration", "M - Inutilisé");
    if (defined('EA_MASTER')) {
        array_push($leschoix, "T - Technique");
    } else {
        $notech = " AND affich<>'T' ";
    }
    $request = "SELECT * FROM (" . EA_DB . "_metadb d JOIN " . EA_DB . "_metalg l) WHERE d.zid=l.zid AND LG='" . $lg . "' AND dtable='" . $xfile . "'" . $notech . " ORDER BY GROUPE, OV3";
    optimize($request);
    $result = EA_sql_query($request);
    //echo $request;
    $i = 0;
    $j = 0;
    $prog = "gest_labels.php?file=" . $xfile;

    echo '<tr><th>Zone</th><th>Affichage</th><th>Etiquette</th></tr>';
    $curgrp = "AA";
    while ($row = EA_sql_fetch_array($result)) {
        $i++;
        if ($row["groupe"] <> $curgrp) {
            $curgrp = $row["groupe"];
            $grptxt = grp_label($curgrp, $xfile, $lg);
            $j++;
            echo '<tr class="row0">' . "\n";
            echo '  <td align="right"><b><i>Groupe : &nbsp;</i></b></td>' . "\n";
            echo ' <input type="hidden" name="grp_' . $j . '"  value="' . $curgrp . '" />' . "\n";
            echo '  <td align="left"><i>&nbsp; ' . $grpes[$curgrp] . "</i> </td>\n";
            echo ' <td><input type="text"   name="group_' . $j . '" size="30" maxlength="50" value="' . $grptxt . '" /></td>';
            echo '</tr>';
        }
        echo ' <tr class="row1">' . "\n";
        echo '  <td align="left"><b>' . $row["zone"] . "</b> : </td>\n";
        echo '<td>';
        if (mb_substr($row["zone"], -3) == "PRE") {
            echo 'Avec le nom' . "\n";
        } else {
            echo '<select name="affi_' . $i . '">' . "\n";
            foreach ($leschoix as $lechoix) {
                echo '<option ' . selected_option(mb_substr($lechoix, 0, isin($lechoix, "-", 0) - 1), $row["affich"]) . '>' . mb_substr($lechoix, isin($lechoix, "-", 0) + 1) . '</option>' . "\n";
            }
            echo " </select>\n";
        }
        echo '  </td>';
        echo '  <td>';
        echo ' <input type="hidden" name="zid_' . $i . '"  value="' . $row["ZID"] . '" />' . "\n";
        echo ' <input type="text"   name="etiq_' . $i . '" size="30" maxlength="50" value="' . $row["etiq"] . '" />';
        echo '  </td>';
        echo " </tr>\n";
    }
    echo ' <tr><td align="right">' . "\n";
    echo '<input type="hidden" name="grpnbr"  value="' . $j . '" />' . "\n";
    echo '<input type="hidden" name="parnbr"  value="' . $i . '" />' . "\n";
    echo '<input type="hidden" name="file"  value="' . $xfile . '" />' . "\n";
    echo '<input type="hidden" name="xconfirm" value="default" />' . "\n";
}
if (($xfile !== 'X') or ($lesigle != $code_liste)) {
    echo '<a href="index.php">Annuler</a> &nbsp; &nbsp;</td>' . "\n";
    echo '<td><input type="submit" value="ENREGISTRER" /></td>' . "\n";
}
echo "</tr></table>\n";
echo "</form>\n";
echo '</div>';
include(__DIR__ . '/../templates/front/_footer.php');
$response->setContent(ob_get_clean());
$response->send();
