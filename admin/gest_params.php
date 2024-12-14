<?php
define('ADM', 10); // Compatibility only
$admtxt = 'Gestion '; // Compatibility only
require(__DIR__ . '/../next/bootstrap.php');
require(__DIR__ . '/../next/_COMMUN_env.inc.php'); // Compatibility only

$userlogin = "";
$userlevel = logonok(9);
while ($userlevel < 9) {
    login($root);
}

function show_grp($grp, $current, $barre)
{
    if ($barre) {
        echo ' | ';
    }
    if ($grp == $current) {
        echo '<strong>' . $grp . '</strong>';
    } else {
        echo '<a href="gest_params.php?grp=' . $grp . '">' . $grp . '</a>';
    }
}

function alaligne($texte)
{
    // insert des BR pour provoquer des retour à la ligne
    $order   = array("\r\n", "\n", "\r");
    $replace = '<br />';
    // Traitement du premier \r\n, ils ne seront pas convertis deux fois.
    return str_replace($order, $replace, $texte);
}

$js_show_help = "";
$js_show_help .= "function show(id) \n ";
$js_show_help .= "{ \n ";
$js_show_help .= "	el = document.getElementById(id); \n ";
$js_show_help .= "	if (el.style.display == 'none') \n ";
$js_show_help .= "	{ \n ";
$js_show_help .= "		el.style.display = ''; \n ";
$js_show_help .= "		el = document.getElementById('help' + id); \n ";
$js_show_help .= "	} else { \n ";
$js_show_help .= "		el.style.display = 'none'; \n ";
$js_show_help .= "		el = document.getElementById('help' + id); \n ";
$js_show_help .= "	} \n ";
$js_show_help .= "} \n ";

$ok = false;
$missingargs = false;
$xgroupe  = getparam('grp');
$xconfirm = getparam('xconfirm');

if ($xgroupe == '') {
    // Données postées
    $xgroupe = "Affichage";
    $missingargs = true;  // par défaut
}

pathroot($root, $path, $xcomm, $xpatr, $page);

ob_start();
open_page("Paramétrage du logiciel", $root, $js_show_help);
navadmin($root, "Paramétrage du logiciel");
zone_menu(ADM, $userlevel, array()); //ADMIN STANDARD
echo '<div id="col_main_adm">';
echo '<p align="center"><strong>Administration du logiciel : </strong>';
showmenu('Paramétrage', 'gest_params.php', 'P', 'P', false);
showmenu('Etiquettes', 'gest_labels.php', 'Q', 'P');
showmenu('Etat serveur', 'serv_params.php', 'E', 'P');
showmenu('Fitrage IP', 'gesttraceip.php', 'F', 'P');
showmenu('Index', 'gestindex.php', 'I', 'P');
showmenu('Journal', 'listlog.php', 'J', 'P');
echo '</p>';
echo '<h2>Paramétrage du site "' . SITENAME . '"</h2>';

$request = "SELECT distinct groupe FROM " . EA_DB . "_params WHERE NOT (groupe in ('Hidden','Deleted')) ORDER BY groupe";
$result = EA_sql_query($request);
$barre = false;
echo '<p align="center"><strong>Paramètres : </strong>';
while ($row = EA_sql_fetch_array($result)) {
    show_grp($row["groupe"], $xgroupe, $barre);
    $barre = true;
}
echo ' || <a href="'.$root.'/admin/update_params.php">Backup</a>';
echo '</p>';

if (!$missingargs) {
    $oktype = true;

    if ($xconfirm == 'confirmed') {
        // *** Vérification des données reçues
        $parnbr = getparam("parnbr");
        $i = 1;
        $cpt = 0;
        while ($i <= $parnbr) {
            $parname = getparam("parname$i");
            $parvalue = htmlentities(getparam("parvalue$i"), ENTITY_REPLACE_FLAGS, ENTITY_CHARSET);
            if ($parvalue == "") {
                $request = "SELECT * FROM " . EA_DB . "_params WHERE param = '" . $parname . "'";
                $result = EA_sql_query($request);
                $row = EA_sql_fetch_array($result);
                if ($row["type"] == "B") {
                    $parvalue = 0;
                }
            }
            $request = "UPDATE " . EA_DB . "_params SET valeur = '" . sql_quote($parvalue) . "' WHERE param = '" . $parname . "'";
            //echo "<p>".$request;
            optimize($request);
            $result = EA_sql_query($request);
            $cpt += EA_sql_affected_rows();
            $i++;
        }
        if ($cpt > 0) {
            msg("Sauvegarde : " . $cpt . " paramètre(s) modifié(s).", "info");
        }
    }
}

$request = "SELECT * FROM " . EA_DB . "_params WHERE groupe='" . $xgroupe . "' ORDER BY ordre";
optimize($request);
$result = EA_sql_query($request);
//echo $request;
echo '<h2 align="center">' . $xgroupe . '</h2>';
if ($xgroupe == "Mail") {
    echo '<p align="center"><a href="test_mail.php"><b>Tester l\'envoi d\'e-mail</b></a></p>';
}
if ($xgroupe == "Utilisateurs" and isset($udbname)) {
    msg('ATTENTION : Base des utilisateurs déportée sur ' . $udbaddr . "/" . $udbuser . "/" . $udbname . "/" . EA_UDB . "</p>", 'info');
}

echo '<form method="post" action="">' . "\n";
echo '<table cellspacing="0" cellpadding="1" border="0" summary="Formulaire">' . "\n";
$i = 0;
$prog = "gest_params.php?grp=" . $xgroupe;
while ($row = EA_sql_fetch_array($result)) {
    $i++;
    echo ' <tr>' . "\n";
    echo '  <td align="right"><b>' . $row["libelle"] . "</b>";
    echo ' <a href="' . $prog . '" id="help' . $i . '" onclick="javascript:show(\'aide' . $i . '\');return false;"><b>(?)</b></a>';
    echo '<span id="aide' . $i . '" style="display: none" class="aide"><br>' . $row["param"] . " : " . alaligne($row["aide"]) . '</span>';
    echo " : </td>\n";
    echo '  <td>';
    echo '<input type="hidden" name="parname' . $i . '"  value="' . $row["param"] . '" />' . "\n";
    switch ($row["type"]) {
        case "B":
            $size = 1;
            break;
        case "N":
        case "L":
            $size = 5;
            $maxsize = 5;
            break;
        case "C":
            $size = 50;
            $maxsize = 250;
            break;
        case "T":
            $size = 1000;
            $maxsize = 0;
            break;
        default:
            $size = 1;
            $maxsize = 0;
            break;
    }
    if ($row["type"] == "B") {
        echo '<input type="checkbox" name="parvalue' . $i . '" value="1"' . checked($row["valeur"]) . ' />';
    } elseif ($row["type"] == "L") {
        $leschoix = explode(";", $row["listval"]);
        echo '<select name="parvalue' . $i . '">' . "\n";
        foreach ($leschoix as $lechoix) {
            echo '<option ' . selected_option(intval(mb_substr($lechoix, 0, isin($lechoix, "-", 0) - 1)), $row["valeur"]) . '>' . $lechoix . '</option>' . "\n";
        }
        echo " </select>\n";
    } else {
        if ($size <= 100) {
            echo '<input type="text" name="parvalue' . $i . '" size="' . $size . '" maxlength="' . $maxsize . '" value="' . $row["valeur"] . '" />';
        } else {
            echo '<textarea name="parvalue' . $i . '" cols="40" rows="6">' . html_entity_decode($row["valeur"], ENTITY_REPLACE_FLAGS, ENTITY_CHARSET) . '</textarea>';
        }
    }
    echo '  </td>';
    echo " </tr>\n";
}
echo ' <tr><td align="right">' . "\n";
echo '<input type="hidden" name="parnbr"  value="' . $i . '" />' . "\n";
echo '<input type="hidden" name="grp"  value="' . $xgroupe . '" />' . "\n";
echo '<input type="hidden" name="xconfirm" value="confirmed" />' . "\n";
echo '<a href="index.php">Annuler</a> &nbsp; &nbsp;</td>' . "\n";
echo '<td><input type="submit" value="ENREGISTRER" /></td>' . "\n";
echo "</tr></table>\n";
echo "</form>\n";
echo '</div>';
include(__DIR__ . '/../templates/front/_footer.php');
$response->setContent(ob_get_clean());
$response->send();
