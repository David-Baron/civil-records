<?php

use CivilRecords\Domain\UserModel;

function open_page($title, $path = "", $js = null, $addbody = null, $addhead = null, $index = null, $rss = null)
{
    global $root, $config, $scriptname, $commune, $TIPmsg;

    header('Content-Type: text/html; charset=UTF-8');

    echo '<!DOCTYPE html>';
    echo '<html lang="fr">';
    echo "<head>";
    echo '<meta charset="UTF-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
    echo '<meta name="expires" content="never">';
    echo '<meta name="revisit-after" content="15 days">';
    echo '<meta name="robots" content="index, nofollow">';
    echo '<title>' . $config->get('SITENAME') . ' | ' . $title . '</title>';
    echo '<meta name="description" content="' . $config->get('META_DESCRIPTION', '') . '">';
    echo '<meta name="keywords" content="' . $config->get('META_KEYWORDS', '') . '">';
    echo '<meta name="generator" content="Civil-Records">';

    echo '<link rel="shortcut icon" href="' . $root . '/themes/img/favicon.ico" type="image/x-icon">';

    echo '<link rel="stylesheet" href="' . $root . '/themes/olive.css" type="text/css">';
    echo '<link rel="stylesheet" href="' . $root . '/themes/css/style.css" type="text/css">';
    /*     if (file_exists(__DIR__ . '/../_config/actes.css')) {
        echo '<link rel="stylesheet" href="' . $root . '/_config/actes.css" type="text/css">';
    } */
    // echo '<link rel="stylesheet" href="' . $root . '/themes/olive-print.css" type="text/css" media="print">';

    /* if (file_exists(__DIR__ . '/../_config/js_externe_header.inc.php')) {
        include(__DIR__ . '/../_config/js_externe_header.inc.php');
    } */

    if (null !== $rss) {
        echo '<link rel="alternate" type="application/rss+xml" title="' . $config->get('SITENAME') . ' | ' . $title . '" href="' . $root . '/' . $rss . '">';
    }

    if (null !== $js) {
        echo '<script type="text/javascript">';
        echo $js;
        echo '</script>';
    }

    echo $config->get('INCLUDE_HEADER', '');

    if (null !== $addhead) {
        echo $addhead;
    }

    echo "</head>";
    echo '<body>';
    echo '<div class="entete">';
    include(__DIR__ . '/../templates/front/_bandeau.php');
    echo "</div>";
}

/**
 * transforme en date en un tableau en coupant sur / . - ou blanc
 */
function explode_date($datetxt)
{
    if (strpos($datetxt, '/') > 0) {
        $elements = explode('/', $datetxt);
    } elseif (strpos($datetxt, '-') > 0) {
        $elements = explode('-', $datetxt);
    } elseif (strpos($datetxt, '.') > 0) {
        $elements = explode('.', $datetxt);
    } elseif (strpos($datetxt, ' ') > 0) {
        $elements = explode(' ', $datetxt);
    } else {
        $elements[0] = $datetxt;
    }
    return $elements;
}

function ajuste_date($datetxt, &$datesql, &$badannee)  // remise en forme des dates incomplètes
{
    global $dateincomplete;

    $elements = explode_date($datetxt);
    $i = count($elements);
    //if (!isset($elements[0])) $elements[0]="";
    $j = 1;
    $tdate[1] = 0; // annee
    $tdate[2] = 0; // mois
    $tdate[3] = 0; // jour

    $dateincomplete = false;
    //	if ($elements[0] <= 31 and $elements[2] >= 100)  // --> retourner la date !
    //		permuter($elements[0],$elements[2]);
    if (($i == 2) and ($elements[0] > 100) and ($elements[1] > 100)) { // cas particulier d'une fourchette d'année assimilée à la 1ere année citée  !!
        $tdate[1] = intval($elements[0]);
        $dateincomplete = true;
    } else {
        while ($i >= 0) {
            if (!empty($elements[$i])) {
                $tdate[$j] = intval($elements[$i]);
                if ($tdate[$j] == 0) {
                    $dateincomplete = true;
                }
                $j++;
            }
            $i--;
        }
    }
    if ($tdate[1] < 100 or $tdate[1] > 2050) {
        $badannee = 1;
    } else {
        $badannee = 0;
    }
    $tdate[1] = trim(str_pad($tdate[1], 4, "0", STR_PAD_LEFT));
    $tdate[2] = trim(str_pad($tdate[2], 2, "0", STR_PAD_LEFT));
    $tdate[3] = trim(str_pad($tdate[3], 2, "0", STR_PAD_LEFT));
    $datetxt = $tdate[3] . '/' . $tdate[2] . '/' . $tdate[1];
    $datesql = $tdate[1] . '-' . $tdate[2] . '-' . $tdate[3];
    $datesql = str_replace('-00', '-01', $datesql);
    $d = DateTime::createFromFormat('Y-m-d', $datesql);
    if (!($d && $d->format('Y-m-d') === $datesql)) {
        $datesql = '1001-01-01';
    }
    // echo " Bad?".$badannee." --> ".$datetxt." --> ".$datesql." --> ".showdate($datesql);
    return $datetxt;
}

function statistiques($vue = "T")
{
    global $root, $config, $xtyp;

    echo '<div class="box">';
    echo '<div class="box-title">Statistiques</div>';

    $sql = "SELECT TYPACT, sum(NB_TOT) FROM " . $config->get('EA_DB') . "_sums GROUP BY TYPACT ORDER BY INSTR('NMDV',TYPACT)";
    $result = EA_sql_query($sql);
    if ($result) {
        $tot = 0;
        $texte = "";
        $menu_actes = "";
        if ($result) {
            while ($ligne = EA_sql_fetch_row($result)) {
                switch ($ligne[0]) {
                    case "N":
                        $typ = "Naissances/Baptêmes";
                        break;
                    case "M":
                        $typ = "Mariages";
                        break;
                    case "D":
                        $typ = "Décès/Sépultures";
                        break;
                    case "V":
                        $typ = "Actes divers";
                        break;
                }
                if ($ligne[1] > 0) {
                    $menu_actes .= iif($menu_actes == "", "", " | ");
                    if ($xtyp != $ligne[0]) {
                        $menu_actes .= '<a href="' . $root . '/accueil?vue=' . $vue . '&xtyp=' . $ligne[0] . '">' . $typ . '</a>';
                    } else {
                        $menu_actes .= $typ;
                    }
                    $texte .= '<dd>';
                    if ($config->get('SHOW_ALLTYPES') == 0) {
                        $texte .= '<a href="' . $root . '/accueil?vue=' . $vue . '&xtyp=' . $ligne[0] . '">';
                    }
                    $texte .= entier($ligne[1]) . ' ' . $typ;
                    if ($config->get('SHOW_ALLTYPES') == 0) {
                        $texte .= '</a>';
                    }
                    $texte .= '</dd>';
                }
                $tot += $ligne[1];
            }
            if ($config->get('SHOW_ALLTYPES') == 1) {
                $menu_actes .= iif($menu_actes == "", "", " | ");
                if ($xtyp != "A") {
                    $menu_actes .= '<a href="' . $root . '/accueil?vue=' . $vue . '&xtyp=A">Tous</a>';
                } else {
                    $menu_actes .= 'Tous';
                }
            }
        }
    }

    echo '<div class="box-body p-2"><dl>';
    echo '<dt><strong>' . entier($tot) . ' actes</strong> dont :</dt>' . $texte;
    if ($config->get('SHOW_RSS') <> 0) {
        $urlrss = $root . '/rss.php?type=A';
        $mesrss = 'Résumé de la base en RSS';
        if ($config->get('SHOW_ALLTYPES') == 0) {
            $urlrss = $root . '/rss.php?type=' . $xtyp;
            $mesrss .= " (" . typact_txt($xtyp) . ")";
        }
        echo '<dt><a href="' . $urlrss . '" title="' . $mesrss . '"><img src="' . $root . '/themes/img/feed-icon-16x16.gif" alt="' . $mesrss . '"></a></dt>';
    }
    echo '</dl></div>';
    echo '</div>';
    return $menu_actes;
}

function zone_menu($admin, int $userlevel, $pp = array())
{
    global $root, $config, $userAuthorizer;
    $menu_actes = '';
    echo '<div class="main-col-left">';
    require(__DIR__ . '/../templates/front/_search-form.php');
    if ($userlevel < 6) {
        $menu_actes = statistiques(); // TODO: need tests
    }
    echo '<div class="box">';
    echo '<div class="box-title">Membre</div>';
    echo '<div class="box-body">';
    echo '<nav class="nav">';
    echo '<a href="' . $root . '/">Accueil</a>';
    echo '<a href="' . $root . '/contact">Contact</a>';
    if (!$userAuthorizer->isAuthenticated()) {
        echo '<a href="' . $root . '/identification">Connexion</a>';
        if ($config->get('SHOW_ACCES') == 1) {
            echo '<a href="' . $root . '/acces">Conditions d\'accès</a>';
        }
    } else {
        if ($userAuthorizer->isGranted($config->get('CHANGE_PW'))) {
            echo '<a href="' . $root . '/mon_compte/changer_motdepasse">Changer le mot de passe</a>';
        }
        echo '<a href="' . $root . '/accueil?act=logout">Déconnexion</a>';
    }
    echo '</nav></div>';
    echo '</div>';
    if ($userlevel < 6) {
        echo '<div class="box">';
        echo '<div class="box-title">Info</div>';
        echo '<div class="box-body p-2">';
        echo $config->get('PUB_ZONE_MENU');
        echo '</div>';
        echo '</div>';
    } else {
        require(__DIR__ . '/../templates/admin/_menu-admin.php');
    }
    echo '</div>';
    return $menu_actes;
}

function navigation($root = "", $level = 1, $type = 'A', $commune = null, $patronyme = null, $prenom = null)
{
    global $config;

    $xtyp = "?xtyp=$type";
    $xcomm = null != $commune ? "&xcomm=$commune" : '';
    $xpatr = null != $patronyme ? "&xpatr=$patronyme" : '';
    $libele = '';
    $s2 = '';
    switch ($type) {
        case "N":
            $s2 = "/actes/naissances";
            $libele = "Naissances";
            break;
        case "M":
            $s2 = "/actes/mariages";
            $libele = "Mariages";
            break;
        case "D":
            $s2 = "/actes/deces";
            $libele = "Décès";
            break;
        case "V":
            $s2 = "/actes/divers";
            $libele = "Divers";
            break;
        case "A":
            $libele = "";
            break;
        case "R":  // recherche
            $libele = "";
            break;
    }

    echo '<div class="box">';
    echo '<div class="box-title">';
    echo '<div class="breadcrumb">';
    echo 'Navigation';
    if ($level > 1) {
        if ($level > 10) {
            echo ' :: <a href="' . $root . '/">Accueil</a>';
            echo ' &gt; <a href="' . $root . '/admin/tableau_de_bord">Administration</a>';
            $path = $root . '/admin';
            $level = $level - 10;
        } else {
            if ($config->get('SHOW_ALLTYPES') == 0) {
                echo ' :: <a href="' . $root . '/accueil?xtyp=' . $type . '">Communes et paroisses</a>';
            } else {
                echo ' :: <a href="' . $root . '/">Communes et paroisses</a>';
            }
            $path = $root;
        }
    } elseif ($level == 1) {
        echo ' :: Communes et paroisses';
    }
    if ($level > 2) {
        echo ' | <a href="' . $path . $s2 . $xtyp . $xcomm . '">' . $commune . '</a> | ' . $libele;
    } elseif ($level == 2) {
        echo ' | ' . $commune . ' | ' . $libele;
    }
    if ($level > 3) {
        echo ' | <a href="' . $path . $s2 . $xtyp . $xcomm . $xpatr . '">' . $patronyme . '</a>';
    } elseif ($level == 3) {
        echo ' | ' . $patronyme;
    }
    if ($level == 4) echo ' ' . $prenom;
    echo '</div>';
?>
    <div class="tool">
        <ul>
            <li><?= show_solde(); ?></li>
        </ul>
        <!-- <select name="theme-menu" id="theme-menu">
            <option data-ea-theme-value="default" aria-pressed="false">Thème jaune</option>
            <option data-ea-theme-value="olive" aria-pressed="false">Thème olive</option>
        </select> -->
    </div>
<?php
    echo '</div>';
    echo '</div>';
}

function navadmin($root = '', $current_view = '')
{
    echo '<div class="box">';
    echo '<div class="box-title">';
    echo '<div class="breadcrumb">';
    echo '<strong>Civil-Records</strong> :: <a href="' . $root . '/admin/tableau_de_bord">Administration</a>';
    if ($current_view == '') {
        echo ' | Tableau de bord';
    } else {
        echo ' | ' . $current_view;
    }
    echo '</div>';
    echo '</div>';
    echo '</div>';
}

function getCommunes($params)   // Utilisée pour remplir dynamiquement une listbox selon le type d'actes
{
    global $config;
    // nécessité de passer les parmètres dans une seule variable
    $typact = $params[0];
    $mode = '';
    if (isset($params[1])) {
        $mode = $params[1];
    }
    $rs = EA_sql_query("SELECT DISTINCT COMMUNE,DEPART FROM " . $config->get('EA_DB') . "_sums WHERE TYPACT = '$typact' ORDER BY COMMUNE, DEPART");
    $k = 0;
    if (EA_sql_num_rows($rs) == 0) {
        $options[$k] = array("value" => "", "text" => ("Aucune commune pour ce type"));
    } else {
        if ($mode == '2') { // backup
            $options[$k] = array("value" => "BACKUP COMPLET", "text" => ("*** Backup complet (par type) ***"));
        } elseif ($mode == '1') { // tous
            $options[$k] = array("value" => "TOUTES", "text" => ("*** Toutes ***"));
        } else {
            $options[$k] = array("value" => "", "text" => ("Sélectionner une commune"));
        }
        while ($row = EA_sql_fetch_array($rs)) {
            $k++;
            $comdep = ($row["COMMUNE"] . " [" . $row["DEPART"] . "]");
            $options[$k] = array("value" => $comdep, "text" => $comdep);
        }
    }
    return $options;
}

function form_typeactes_communes($mode = '', $alldiv = 1)
{
    global $root;
    // Tableau avec choix du type + choix d'une commune existante
    echo "<tr>";
    echo '<td>Type des actes : </td>';
    echo '<td>';
    $ajaxcommune = ' onClick="getCommunes(this.value, {"content_type": "json", "target": "ComDep", "preloader": "prl"})"';
    echo '<input type="hidden" name="xtyp" value="X">';
    echo '<input type="radio" name="xtyp" value="N' . $mode . '" ' . $ajaxcommune . '>Naissances<br>';
    echo '<input type="radio" name="xtyp" value="M' . $mode . '" ' . $ajaxcommune . '>Mariages<br>';
    echo '<input type="radio" name="xtyp" value="D' . $mode . '" ' . $ajaxcommune . '>Décès<br>';
    echo '<input type="radio" name="xtyp" value="V' . $mode . '" ' . $ajaxcommune . '>Actes divers : ';
    listbox_divers("typdivers", " -- Tous -- ", $alldiv);
    echo '<br><br>';
    echo '</td>';
    echo "</tr>";
    echo "<tr>";
    echo '<td>Commune / Paroisse : </td>';
    echo '<td>';
    echo '<select id="ComDep" name="ComDep">';
    echo '<option value="">Choisir d\'abord le type d\'acte</option> ';
    echo '</select><img id="prl" src="' . $root . '/themes/img/minispinner.gif" style="visibility:hidden;">';
    echo '</td>';
    echo "</tr>";
}

function listbox_communes($fieldname, $default, $vide = 0)  // liste de toutes les communes ts actes confondus
{
    global $config;
    $sql = "SELECT DISTINCT COMMUNE, DEPART FROM " . $config->get('EA_DB') . "_sums ORDER BY COMMUNE, DEPART ";

    if ($result = EA_sql_query($sql)) {
        $i = 1;
        echo '<select name="' . $fieldname . '" size="1">';
        if ($vide == 1) {
            echo '<option> -- Toutes -- </option>';
        }
        if ($vide == 2) {
            echo '<option>Backup complet (par type)</option>';
        }
        while ($row = EA_sql_fetch_array($result)) {
            $comdep = $row["COMMUNE"] . " [" . $row["DEPART"] . "]";
            echo '<option ' . (htmlentities($comdep, ENTITY_REPLACE_FLAGS, ENTITY_CHARSET) == $default ? 'selected' : '') . '>' . htmlentities($comdep, ENTITY_REPLACE_FLAGS, ENTITY_CHARSET) . '</option>';
            $i++;
        }
    }
    echo " </select>";
}

/**
 * Explode communne and departement string
 * 
 * @return array ['commune' => $commune, 'departement' => $departement]
 */
function decompose_comm_dep(string $communeAndDepartement): array
{
    $commune = $communeAndDepartement;
    $departement = '';
    $croch = mb_strrpos($communeAndDepartement, "[");
    if ($croch > 0) {
        $commune = mb_substr($communeAndDepartement, 0, $croch - 1);
        $departement = mb_substr($communeAndDepartement, $croch + 1, mb_strlen($communeAndDepartement) - $croch - 2);
    }

    return ['commune' => $commune, 'departement' => $departement];
}

function communede(string $communeAndDepartement)
{
    return decompose_comm_dep($communeAndDepartement)['commune'];
}

function departementde(string $communeAndDepartement)
{
    return decompose_comm_dep($communeAndDepartement)['departement'];
}

function load_zlabels($table, $lg, $ordre = "CSV")
{
    global $config;
    switch ($ordre) {
        case "CSV":
            $condit = "AND NOT (groupe LIKE '_0') ORDER BY groupe, OV3";
            break;
        case "NIM3":
            $condit = "AND (OV3>0 AND OV3<100) ORDER BY OV3";
            break;
        case "NIM2":
            $condit = "AND (OV2>0 AND OV2<100) ORDER BY OV2";
            break;
        case "EA3":
            $condit = "AND (OV3>0) ORDER BY OV3";
            break;
        case "EA2":
            $condit = "AND (OV2>0) ORDER BY OV2";
            break;
    }
    // Charges les labels dans un table
    $req1 = "SELECT d.ZID, ZONE, GROUPE, BLOC, TAILLE, OBLIG, ETIQ, TYP, AFFICH, GETIQ 
    FROM (" . $config->get('EA_DB') . "_metadb d JOIN " . $config->get('EA_DB') . "_metalg l JOIN " . $config->get('EA_DB') . "_mgrplg g)"
        . " WHERE ((d.ZID=l.ZID) 
        AND (d.GROUPE=g.GRP) 
        AND (g.LG='" . $lg . "') 
        AND (g.dtable='" . $table . "') 
        AND (g.sigle=' ') 
        AND (l.LG='" . $lg . "') 
        AND (d.dtable='" . $table . "')) " . $condit;
    $res = EA_sql_query($req1);
    $nbtot = EA_sql_num_rows($res);
    $mdb = array();
    for ($j = 0; $j < $nbtot; $j++) {
        array_push($mdb, EA_sql_fetch_assoc($res));
    }

    return $mdb;
}

function metadata($zone, $voulu)  // valeur $zone du record $voulu
{
    global $mdb; /// postule que $mdb a été convenablement initialisé
    $i = 0;
    $maxi = count($mdb);
    while (($i < $maxi) and $mdb[$i]['ZONE'] <> $voulu) {
        $i++;
    }

    if ($i < $maxi) {
        return $mdb[$i][$zone];
    }

    return "Zone $voulu inconnue";
}

function listbox_divers($fieldname, $default, $tous = 0)
{
    global $config;

    $sql = "SELECT DISTINCT LIBELLE FROM " . $config->get('EA_DB') . "_sums WHERE length(LIBELLE)>0";
    if ($result = EA_sql_query($sql)) {
        $i = 1;
        echo '<select name="' . $fieldname . '">';
        if ($tous) {
            echo '<option> -- Tous -- </option>';
        }
        while ($row = EA_sql_fetch_array($result)) {
            echo '<option ' . ($default == $row["LIBELLE"] ? 'selected' : '') . '>' . $row["LIBELLE"] . '</option>';
            $i++;
        }
    }
    echo " </select>";
}

function listbox_users($fieldname, $default, int $minUserlevel, $txtzero = '')
{
    $userModel = new UserModel();
    $users = $userModel->findAllWithMinLevel($minUserlevel);
    echo '<select name="' . $fieldname . '">';
    if ($default == 0 && $txtzero != '') {
        echo '<option value="0" selected>' . $txtzero . '</option>';
    }
    foreach ($users as $user) {
        echo '<option value="' . $user["ID"] . '"' . ($user["ID"] == $default ? ' selected' : '') . '>' . $user["nom"] . " " . $user["prenom"] . '</option>';
    }
    echo " </select>";
}

// format : somme de 1= label gras, 2 label italique, 4 info gras, 8 info italique
function show_simple_item($retrait, $format, $info, $label, $info2 = '', $url = '')
{
    // $sp = "";
    // $url1 = '';
    // $url2 = '';
    $claslab = 'fich0';
    $clasinf = 'fich1';

    /* for ($i = 0; $i < $retrait; $i++) {
        $sp .= "&nbsp;&nbsp;&nbsp;";
    } */

    if (fmod($format, 2) == 1) {
        $claslab = 'fich2 bolder';
    }

    if (div($format, 2) == 1) {
        $label = '<em>' . $label . '</em>';
    }
    if (div($format, 4) == 1) {
        $info  = '<strong>' . $info . '</strong>';
    }
    if (div($format, 8) == 1) {
        $info  = '<em>' . $info . '</em>';
    }
    if ($info2 <> "") {
        if (div($format, 4) == 1) {
            $info2  = '<strong>' . $info2 . '</strong>';
        }
        if (div($format, 8) == 1) {
            $info2  = '<em>' . $info2 . '</em>';
        }
        $info2 = " " . $info2;
    }
    if ($url <> "") {
        $info = '<a href="' . $url . '">' . $info . '</a> ';
    }
    echo '<tr>';
    echo '<td class="' . $claslab . '">' . $label . '</td>';
    echo '<td class="' . $clasinf . '">' . $info . $info2 . '</td>';
    echo '</tr>';
}

function grp_label($gp, $tb, $lg, $sigle = '')
{
    global $config;
    $sql = "SELECT GETIQ FROM " . $config->get('EA_DB') . "_mgrplg WHERE lg='" . $lg . "' AND dtable='" . $tb . "' AND grp='" . $gp . "' AND sigle=' '";
    $result = EA_sql_query($sql);
    $row = EA_sql_fetch_array($result);
    $label = $row["GETIQ"];
    if ($sigle <> '') {  // on cherche le label spécifique s'il existe
        $sql = "SELECT GETIQ FROM " . $config->get('EA_DB') . "_mgrplg WHERE lg='" . $lg . "' AND dtable='" . $tb . "' AND grp='" . $gp . "' AND sigle='" . $sigle . "'";
        $result = EA_sql_query($sql);
        if (EA_sql_num_rows($result) > 0) {
            $row = EA_sql_fetch_array($result);
            $label = $row["GETIQ"];
        }
    }
    return $label;
}

// format : somme de 1= label gras, 2 label italique, 4 info gras, 8 info italique
function show_item3($row, $retrait, $format, $zidinfo, $url = "", $zidinfo2 = "", $activelink = 0)
{
    global $config, $userAuthorizer;

    $sexes = [
        'M' => 'Masculin',
        'F' => 'Féminin',
        '?' => 'Non précisé'
    ];

    $lg = $GLOBALS['lg'];
    $req1 = "SELECT ZONE, GROUPE, TYP, TAILLE, OBLIG, AFFICH, ETIQ, AIDE FROM (" . $config->get('EA_DB') . "_metadb d JOIN " . $config->get('EA_DB') . "_metalg l)"
        . " WHERE ((d.ZID=l.ZID) AND (l.LG='" . $lg . "') AND d.ZID=" . $zidinfo . ")";
    $res1 = EA_sql_fetch_assoc(EA_sql_query($req1));

    $info  = $row[$res1["ZONE"]];
    $oblig = $res1["AFFICH"];  // F = Facultatif, O = Obligatoire, A= Adminstration seulmt
    $label = $res1["ETIQ"];
    $info2 = "";


    if ($zidinfo2 != "") {
        $req2 = "SELECT ZONE, GROUPE, TYP, TAILLE, OBLIG, AFFICH, ETIQ, AIDE FROM (" . $config->get('EA_DB') . "_metadb d JOIN " . $config->get('EA_DB') . "_metalg l)"
            . " WHERE ((d.ZID=l.ZID) AND (l.LG='" . $lg . "') AND d.ZID=" . $zidinfo2 . ")";
        $res2 = EA_sql_fetch_assoc(EA_sql_query($req2));
        $info2 = $row[$res2["ZONE"]];
    }

    if ((trim($info) . trim($info2) != "" && $oblig == "F") || $oblig == 'O' || ($userAuthorizer->isGranted(6) && $oblig == "A")) {

        switch ($res1["TYP"]) {
            case "TXT":
            case "AGE":
                //$info = strtr($info,"??","+"); signe "décédé" NIMEGUE chr(134)
                $info = str_replace("§", " <br />", $info); // retour ligne Nimegue "§" chr(167)
                break;
            case "DAT":  // date en format texte
                if ($res1["ZONE"] == "DATETXT") {
                    if (trim($row["DREPUB"]) != "") {
                        $info .= ' (' . $row["DREPUB"] . ')';
                    }
                }
                break;
            case "DTE":  // date en format SQL
                $info = showdate($info);
                break;
            case "SEX":
                $info = $sexes[$info];
                break;
        }
        if ($activelink <> 0) {  // urlifie les url et les images JPG et autres
            $info = linkifie($info, $activelink);
        }

        show_simple_item($retrait, $format, $info, $label, $info2, $url);
    }
}

// accessoirement affiche la possibilité de proposer une correction
// format : somme de 1= label gras, 2 label italique, 4 info gras, 8 info italique
function show_deposant3($row, $retrait, $format, $zidinfo, $xid, $tact)
{
    global $root, $config, $session, $userAuthorizer, $u_db;
    $lg = $GLOBALS['lg'];
    $req1 = "SELECT ZONE, GROUPE, TYP, TAILLE, OBLIG, AFFICH, ETIQ, AIDE FROM (" . $config->get('EA_DB') . "_metadb d JOIN " . $config->get('EA_DB') . "_metalg l)"
        . " WHERE ((d.ZID=l.ZID) AND (l.LG='" . $lg . "') AND d.ZID=" . $zidinfo . ")";
    $res1 = EA_sql_fetch_assoc(EA_sql_query($req1));
    //echo $req1;
    $info  = $row[$res1["ZONE"]];
    $oblig = $res1["AFFICH"];  // F = Facultatif, O = Obligatoire, A=Adminstration seulmt
    $label = $res1["ETIQ"];
    $depid  = $row["DEPOSANT"];
    $req = "SELECT NOM,PRENOM FROM " . $config->get('EA_UDB') . "_user3 WHERE (ID=" . $depid . ")";
    $curs = EA_sql_query($req, $u_db);
    if (EA_sql_num_rows($curs) == 1) {
        $res = EA_sql_fetch_assoc($curs);
        $info = $res["NOM"] . " " . $res["PRENOM"];
    } else {
        $info = "#" . $depid;
    }

    if ($userAuthorizer->isGranted(6)) {
        show_simple_item($retrait, $format, $info, $label);
        if ($session->get('user')['ID'] == $depid or $session->get('user')['level'] >= 8) {
            $actions = "";
            if ($tact == 'M' or $tact == 'V') {
                $actions .= '<a href="' . $root . '/admin/actes/permuter?xid=' . $xid . '&xtyp=' . $tact . '">Permuter</a> - ';
            }
            $actions .=  '<a href="' . $root . '/admin/actes/modifier?xid=' . $xid . '&xtyp=' . $tact . '">Editer</a>';
            $actions .=  ' - <a href="' . $root . '/admin/actes/supprimer?xid=' . $xid . '&xtyp=' . $tact . '">Supprimer</a>';
            show_simple_item($retrait, $format, $actions, 'Actions');
        }
    } else {
        if ($oblig == "O" or (trim($info) != "" and $oblig == "F")) {
            show_simple_item($retrait, $format, $info, $label);
        }
    }
}

// liste_patro_1("tabnaiss.php",$root,$xcomm,$xpatr,"Naissances / baptêmes",EA_DB."_nai");
// Liste des patronymes pour les actes à UN intervenant (naissance et décès)
function liste_patro_1($script, $root, $xcomm, $xpatr, $titre, $table, $gid = "", $note = "")
{
    global $config, $userAuthorizer;
    $lgi = 1;
    $initiale = "";
    $comdep  = html_entity_decode($xcomm, ENTITY_REPLACE_FLAGS, ENTITY_CHARSET);
    $Commune = communede($comdep);
    $Depart  = departementde($comdep);
    if (mb_substr($xpatr, 0, 1) == "_") {
        $lgi  = mb_strlen($xpatr); // utf-8 ==> multibytes si accents !
        $initiale = " AND left(NOM,($lgi-1))= '" . sql_quote(mb_substr($xpatr, 1)) . "'";
    }

    echo '<h2>' . $titre . '</h2>';
    echo '<p>Commune/Paroisse : <a href="' . $root . $script . '?xcomm=' . $xcomm . '"><strong>' . $xcomm . '</strong></a>';
    if ($gid > 0 && $config->get('GEO_LOCALITE') > 0 && $userAuthorizer->isGranted(1)) {
        echo ' <a href="' . $root . '/admin/geolocalizations/detail?id=' . $gid . '"><img src="' . $root . '/themes/img/boussole.png" alt="Localité détails" title="Localité détails"></a><br></p>';
    }
    if ($note <> '') {
        echo "<p>" . $note . "</p>";
    }

    $condDep = "";
    if ($Depart <> "") {
        $condDep = " AND DEPART='" . sql_quote($Depart) . "'";
    }

    // Faut-il découper le fichier par initiales ?
    $sql = "SELECT count(*)"
        . " FROM $table "
        . " WHERE COMMUNE = '" . sql_quote($Commune) . "'" . $condDep . $initiale;
    $result = EA_sql_query($sql);
    $ligne = EA_sql_fetch_row($result);
    $nbresu = $ligne[0];

    if ($nbresu <= $config->get('MAX_PATR')) {
        $sql = "SELECT NOM, count(*), min(year(LADATE)),max(year(LADATE)) "
            . " FROM $table "
            . " WHERE COMMUNE = '" . sql_quote($Commune) . "'" . $condDep . $initiale
            . " GROUP BY NOM ";
        $result = EA_sql_query($sql);
        $nblign = EA_sql_num_rows($result);

        $i = 1;
        echo '<table class="m-auto" summary="Liste alphabétique">';
        echo '<tr class="rowheader">';
        echo '<th></th>';
        echo '<th>Patronymes</th>';
        echo '<th>Périodes</th>';
        echo '<th>Actes</th>';
        echo '</tr>';
        while ($ligne = EA_sql_fetch_row($result)) {
            echo '<tr class="row' . (fmod($i, 2)) . '">';
            echo '<td>' . $i . '.</td>';
            echo '<td><a href="' . $root . $script . '?xcomm=' . $xcomm . '&xpatr=' . $ligne[0] . '">' . $ligne[0] . '</a></td>';
            echo '<td align="center">' . $ligne[2];
            if ($ligne[2] <> $ligne[3]) {
                echo '-' . $ligne[3];
            }
            echo '</td>';
            echo '<td align="center">' . $ligne[1] . '</td>';
            echo '</tr>';
            $i++;
        }
        echo '</table>';
    }
    if ($nbresu > $config->get('MAX_PATR')) { // Alphabet car trop de patronymes
        $sql = "SELECT left(NOM,$lgi), count(distinct NOM), min(NOM), max(NOM)"
            . " FROM $table "
            . " WHERE COMMUNE = '" . sql_quote($Commune) . "'" . $condDep . $initiale
            . " GROUP BY left(NOM,$lgi)";
        $result = EA_sql_query($sql);
        $nblign = EA_sql_num_rows($result);

        if ($nblign == 1 && $lgi > 3) { // Permet d'éviter un bouclage si le nom devient trop petit
            $sql = "SELECT NOM, count(distinct NOM), min(NOM), max(NOM)"
                . " FROM $table "
                . " WHERE COMMUNE = '" . sql_quote($Commune) . "'" . $condDep . $initiale
                . " GROUP BY NOM";
            $result = EA_sql_query($sql);
        }

        $i = 1;
        echo '<table class="m-auto" summary="Liste alphabétique">';
        echo '<tr class="rowheader">';
        echo '<th>Initiales</th>';
        echo '<th>Patronymes</th>';
        echo '<th>Noms</th>';
        echo '</tr>';
        while ($ligne = EA_sql_fetch_row($result)) {
            echo '<tr class="row' . (fmod($i, 2)) . '">';
            echo '<td align="center"><strong>' . $ligne[0] . '</strong></td>';
            if ($ligne[1] == 1) {
                echo '<td align="center">' . $ligne[1] . '</td>';
                echo '<td><a href="' . $root . $script . '?xcomm=' . $xcomm . '&xpatr=' . $ligne[2] . '">' . $ligne[2] . '</a></td>';
            } else {
                echo '<td align="center">' . $ligne[1] . '</td>';
                while (mb_strlen($ligne[0]) < $lgi) {
                    $ligne[0] = $ligne[0] . ' ';
                }
                echo '<td><a href="' . $root . $script . '?xcomm=' . $xcomm . '&xpatr=' . $ligne[0] . '">' . $ligne[2] . ' à ' . $ligne[3] . '</a></td>';
            }
            echo '</tr>';
            $i++;
        }
        echo '</table>';
    }
    if ($nbresu == 0) {
        echo 'Aucun patronyme trouvé';
    }
}

// Liste des patronymes pour les actes à DEUX intervenants (mariages et divers)
function liste_patro_2($script, $root, $xcomm, $xpatr, $titre, $table, $stype = "", $gid = "", $note = "")
{
    global $config, $userAuthorizer;
    $lgi = 1;
    $initiale  = "";
    $initialeF = "";
    $initdeux  = "";
    $comdep  = html_entity_decode($xcomm, ENTITY_REPLACE_FLAGS, ENTITY_CHARSET);
    $Commune = communede($comdep);
    $Depart  = departementde($comdep);

    if (mb_substr($xpatr, 0, 1) == "_") {
        $lgi  = mb_strlen($xpatr);
        $initiale  = " left(NOM,($lgi-1))= '" . sql_quote(mb_substr($xpatr, 1)) . "'";
        $initialeF = " left(C_NOM,($lgi-1))= '" . sql_quote(mb_substr($xpatr, 1)) . "'";
        $initdeux  = " AND (" . $initiale . " or " . $initialeF . ")";
        $initiale  = " AND " . $initiale;
        $initialeF = " AND " . $initialeF;
    }

    if ($stype <> "") {
        $soustype = " AND LIBELLE = '" . sql_quote($stype) . "'";
        $sousurl  = "&stype=" . $stype;
        $stitre   = " (" . $stype . ")";
    } else {
        $soustype = "";
        $sousurl  = "";
        $stitre   = "";
    }

    echo '<h2>' . $titre . '</h2>';
    echo '<p>Commune/Paroisse : <a href="' . $root . '/' . $script . '?xcomm=' . $xcomm . $sousurl . '"><strong>' . $xcomm . '</strong></a>';
    if ($gid > 0 && $config->get('GEO_LOCALITE') > 0 && $userAuthorizer->isGranted(1)) {
        echo ' <a href="' . $root . '/admin/geolocalizations/detail?id=' . $gid . '"><img src="' . $root . '/themes/img/boussole.png" alt="Localité détails" title="Localité détails"></a></p>';
    }
    if ($note <> '') {
        echo "<p>" . $note . "</p>";
    }
    $condDep = "";
    if ($Depart <> "") {
        $condDep = " AND DEPART = '" . sql_quote($Depart) . "'";
    }

    // Faut-il découper le fichier par initiales ?
    $sql = "SELECT count(*) FROM $table WHERE COMMUNE='" . sql_quote($Commune) . "'" . $condDep . $initdeux . $soustype;
    $result = EA_sql_query($sql);
    $ligne = EA_sql_fetch_row($result);
    $nbresu = $ligne[0];

    if ($nbresu > $config->get('MAX_PATR')) { // Alphabet car trop de patronymes
        $req1 = "SELECT left(NOM,$lgi), count(distinct NOM), min(NOM), max(NOM)"
            . " FROM $table "
            . " WHERE COMMUNE = '" . sql_quote($Commune) . "'" . $condDep . $initiale . $soustype
            . " GROUP BY left(NOM,$lgi)";
        $result1 = EA_sql_query($req1);
        $nb1 = EA_sql_num_rows($result1);

        $req2 = "SELECT left(C_NOM,$lgi), count(distinct C_NOM), min(C_NOM), max(C_NOM)"
            . " FROM $table "
            . " WHERE COMMUNE = '" . sql_quote($Commune) . "'" . $condDep . $initialeF . $soustype
            . " GROUP BY left(C_NOM,$lgi)";
        $result2 = EA_sql_query($req2);
        $nb2 = EA_sql_num_rows($result2);


        $i = 1;
        $fini = 0;
        $lire1 = 0;
        $lire2 = 0;
        $neof1 = ($ligne1 = EA_sql_fetch_row($result1));
        while ($neof1 and remove_accent($ligne1[0]) == "") {
            $neof1 = ($ligne1 = EA_sql_fetch_row($result1));
        }
        $neof2 = ($ligne2 = EA_sql_fetch_row($result2));
        while ($neof2 and remove_accent($ligne2[0]) == "") {
            $neof2 = ($ligne2 = EA_sql_fetch_row($result2));
        }
        echo '<table class="m-auto" summary="Liste alphabétique">';
        echo '<tr class="rowheader">';
        echo '<th>Initiales</th>';
        echo '<th>Patronymes</th>';
        echo '</tr>';
        $code = 250;
        $code_arret = chr($code);
        while ($fini == 0 and $i < 100) {
            if (!$neof1) {
                $mari = "";
            } else {
                $mari = remove_accent($ligne1[0]);
            }
            if (!$neof2) {
                $femm = "";
            } else {
                $femm = remove_accent($ligne2[0]);
            }
            if ($mari == "") {
                $mari = $code_arret;
            }
            if ($femm == "") {
                $femm = $code_arret;
            }
            //echo "<p>Mari = ".$mari." - Femme = ".$femm."</p>";
            if ($mari == $code_arret && $femm == $code_arret) {
                $fini = 1;
            } else {
                echo '<tr class="row' . (fmod($i, 2)) . '">';
                if ($mari < $femm) {
                    $lenom = $ligne1[0];
                    $lemin = $ligne1[2];
                    $lemax = $ligne1[3];
                    $lire1 = 1;
                } elseif ($mari > $femm) {
                    $lenom = $ligne2[0];
                    $lemin = $ligne2[2];
                    $lemax = $ligne2[3];
                    $lire2 = 1;
                } else {
                    // alors =
                    $lenom = $ligne1[0];
                    $lemin = strmin($ligne1[2], $ligne2[2]);
                    $lemax = strmax($ligne1[3], $ligne2[3]);
                    $lire1 = 1;
                    $lire2 = 1;
                }
                echo '<td><strong>' . $lenom . '</strong></td>';
                while (mb_strlen($lenom) < $lgi) {
                    $lenom = $lenom . ' ';
                }
                if ($lemin == $lemax) {
                    echo '<td><a href="' . $root . '/' . $script . '?$xcomm=' . $xcomm . $sousurl, $lemin . '">' . $lemin . '</a></td>';
                } else {
                    echo '<td><a href="' . $root . '/' . $script . '?$xcomm=' . $xcomm . $sousurl . '&xpatr' . $lenom . '">' . $lemin . ' à ' . $lemax . '</a></td>';
                }
                echo '</tr>';
                if ($lire1 == 1) { //and $neof1)
                    $neof1 = ($ligne1 = EA_sql_fetch_row($result1));
                    $lire1 = 0;
                }
                if ($lire2 == 1) { //and $neof2)
                    $neof2 = ($ligne2 = EA_sql_fetch_row($result2));
                    $lire2 = 0;
                }
                $i++;
            }
        }
        echo '</table>';
    } elseif ($nbresu > 0) {
        $req1 = "SELECT distinct NOM, count(*), min(year(LADATE)),max(year(LADATE))"
            . " FROM $table "
            . " WHERE COMMUNE = '" . sql_quote($Commune) . "'" . $condDep . $initiale . $soustype
            . " GROUP BY NOM "
            . " ORDER BY NOM";
        $result1 = EA_sql_query($req1);
        $nb1 = EA_sql_num_rows($result1);

        $req2 = "SELECT distinct C_NOM, count(*), min(year(LADATE)),max(year(LADATE)) "
            . " FROM $table "
            . " WHERE COMMUNE = '" . sql_quote($Commune) . "'" . $condDep . $initialeF . $soustype . " AND C_NOM<>''"
            . " GROUP BY C_NOM "
            . " ORDER BY C_NOM";

        $result2 = EA_sql_query($req2);
        $nb2 = EA_sql_num_rows($result2);

        $i = 1;
        $fini = 0;
        $lire1 = 0;
        $lire2 = 0;
        echo '<table class="m-auto" summary="Liste alphabétique">';
        echo '<tr class="rowheader">';
        echo '<th></th>';
        echo '<th>Patronymes</th>';
        echo '<th>Périodes</th>';
        if ($table == $config->get('EA_DB') . "_mar3") {
            echo '<th>Epoux</th>';
            echo '<th>Epouses</th>';
        } else {
            echo '<th>Person 1</th>';
            echo '<th>Person 2</th>';
        }

        echo '</tr>';
        $neof1 = ($ligne1 = EA_sql_fetch_row($result1));
        if (!$neof1) {
            $mari = "";
        } else {
            $mari = strtoupper(remove_accent($ligne1[0]));
        }
        $neof2 = ($ligne2 = EA_sql_fetch_row($result2));
        if (!$neof2) {
            $femm = "";
        } else {
            $femm = strtoupper(remove_accent($ligne2[0]));
        }

        $code = 254;
        $code_arret = chr($code);
        while ($fini == 0) {
            if ($mari == "" and !$neof1) {
                $mari = $code_arret;
            }
            if ($femm == "" and !$neof2) {
                $femm = $code_arret;
            }
            if ((!$neof1 and !$neof2)) { //or ($mari==$code_arret && $femm==$code_arret))
                $fini = 1;
            } else {
                echo '<tr class="row' . (fmod($i, 2)) . '">';
                echo '<td>' . $i . '. </td>';
                if ($mari < $femm) {
                    echo '<td><a href="' . $root . '/' . $script . '?xcomm=' . $xcomm . $sousurl . '&xpatr=' . $ligne1[0] . '">' . $ligne1[0] . '</a></td>';
                    echo '<td align="center"> ' . fourchette_dates($ligne1[2], $ligne1[3]) . '</td>';
                    echo '<td align="center"> ' . $ligne1[1] . '</td>';
                    echo '<td align="center"> - </td>';
                    $lire1 = 1;
                } elseif ($mari > $femm) {
                    echo '<td><a href="' . $root . '/' . $script . '?xcomm=' . $xcomm . $sousurl . '&xpatr=' . $ligne2[0] . '">' . $ligne2[0] . '</a></td>';
                    echo '<td align="center"> ' . fourchette_dates($ligne2[2], $ligne2[3]) . '</td>';
                    echo '<td align="center"> - </td>';
                    echo '<td align="center"> ' . $ligne2[1] . '</td>';
                    $lire2 = 1;
                } else {
                    // alors =
                    echo '<td><a href="' . $root . '/' . $script . '?xcomm=' . $xcomm . $sousurl . '&xpatr=' . $ligne1[0] . '">' . $ligne1[0] . '</a></td>';
                    echo '<td align="center"> ' . fourchette_dates($ligne1[2], $ligne1[3], $ligne2[2], $ligne2[3]) . '</td>';
                    echo '<td align="center"> ' . $ligne1[1] . '</td>';
                    echo '<td align="center"> ' . $ligne2[1] . '</td>';
                    $lire1 = 1;
                    $lire2 = 1;
                }
                echo '</tr>';
            }
            if ($lire1 == 1) { //and $neof1)
                $neof1 = ($ligne1 = EA_sql_fetch_row($result1));
                if (!$neof1) {
                    $mari = "";
                } else {
                    $mari = strtoupper(remove_accent($ligne1[0]));
                }
                $lire1 = 0;
            }
            if ($lire2 == 1) { //and $neof2)
                $neof2 = ($ligne2 = EA_sql_fetch_row($result2));
                if (!$neof2) {
                    $femm = "";
                } else {
                    $femm = strtoupper(remove_accent($ligne2[0]));
                }
                $lire2 = 0;
            }
            $i++;
        }
        echo '</table>';
    } else {
        echo 'Aucun patronyme trouvé';
    }
}

function fourchette_dates($d1min = 0, $d1max = 0, $d2min = 0, $d2max = 0)
{
    $min = 0;
    $max = 0;
    $res = "-";
    if ($d1min > 0) {
        $min = $d1min;
    }
    if ($d2min > 0 and $d2min < $min) {
        $min = $d2min;
    }
    if ($d1max > 0) {
        $max = $d1max;
    }
    if ($d2max > 0 and $d2max > $max) {
        $max = $d2max;
    }
    if ($max > $min) {
        if ($min > 0) {
            $res = $min . "-" . $max;
        } else {
            $res = $max;
        }
    } elseif ($min > 0) {
        $res = $min;
    }

    return $res;
}

function pagination($nbtot, &$page, $href, &$listpages, &$limit)
{
    global $config;
    // $nbtot : Nombre de records
    // $page : page courante
    // $href : URL de base
    // $listpages : liste des n° de page avec lien (en résultat)
    // $limit : clause LIMIT pour MySQL (résultat)

    $debut = 3;
    $autour = 4;
    $maxpage = $config->get('MAX_PAGE');
    if ($nbtot > $maxpage) {
        // Plus d'une page
        $totpages = intval(($nbtot - 1) / $maxpage) + 1;
        $listpages = "";
        if ($page == "") {
            $page = 1;
        }
        if ($totpages > 1) {
            $pp = false;
            $listpages = "";
            for ($p = 1; $p <= $totpages; $p++) {
                if (($p <= $debut) || ($p > ($totpages - $debut)) || ($p >= ($page - $autour) && $p <= ($page + $autour))) {
                    if ($p == $page) {
                        $pp = false;
                        $listpages = $listpages . "<strong> " . $p . "</strong>";
                    } else {
                        $listpages = $listpages . ' <a href="' . $href . '&page=' . $p . '">' . $p . "</a>";
                    }
                } else {
                    if (!$pp) {
                        $listpages .= " ..... ";
                    }
                    $pp = true;
                }
            }
            $listpages = "<strong>Pages :</strong>" . $listpages;
        }
        $limit = " LIMIT " . ($page - 1) * $maxpage . "," . $maxpage;
    } else {
        $listpages = "";
        $limit = "";
        $page = 1;
    }
    return $listpages;
}

function actions_deposant($userid, $depid, $actid, $typact)  // version graphique
{
    global $root, $userAuthorizer, $config;

    $depinfo = "#" . $depid;

    $req = "SELECT NOM, PRENOM FROM " . $config->get('EA_DB') . "_user3 WHERE (ID=" . $depid . ")";
    $curs = EA_sql_query($req);
    if (EA_sql_num_rows($curs) == 1) {
        $res = EA_sql_fetch_assoc($curs);
        $depinfo = $res["NOM"] . " " . $res["PRENOM"];
    }

    if ($userid == $depid || $userAuthorizer->isGranted(8)) {
        echo '<td>';
        echo $depinfo . ' ';
        echo '</td>';
        echo '<td>';
        if ($typact == 'M' || $typact == 'V') {
            echo '<a href="' . $root . '/admin/actes/permuter?xid=' . $actid . '&xtyp=' . $typact . '"><img width="16" height="16" title="Permuter" alt="Permuter" src="' . $root . '/themes/img/permuter.gif"></a>';
        }
        echo ' <a href="' . $root . '/admin/actes/modifier?xid=' . $actid . '&xtyp=' . $typact . '"><img width="16" height="16" title="Modifier" alt="Modifier" src="' . $root . '/themes/img/modifier.gif"></a>';
        echo ' <a href="' . $root . '/admin/actes/supprimer?xid=' . $actid . '&xtyp=' . $typact . '"><img width="16" height="16" title="Supprimer" alt="Supprimer" src="' . $root . '/themes/img/supprimer.gif"></a>';
        echo '</td>';
    } else {
        echo '<td>';
        echo $depinfo; //iif(($depnom==""),"#".$depid,$depnom.' '.$deppre);
        echo '</td><td></td>';
    }
}

function typact_txt($typact)
{
    $typ = "";
    if (is_null($typact)) {
        $typact = '';
    }
    switch (strtoupper($typact)) {
        case "N":
        case "NAI":
            $typ = "Naissances";
            break;
        case "D":
        case "DEC":
            $typ = "Décès";
            break;
        case "M":
        case "MAR":
            $typ = "Mariages";
            break;
        case "V":
        case "DIV":
            $typ = "Actes divers";
            break;
        case "U":  // par extension ..
            $typ = "Utilisateurs";
            break;
        case "P":
            $typ = "Paramètres";
            break;
    }
    return $typ;
}

// Vérification du solde des points et décompte de la consommation ($cout)
function solde_ok($cout = 0, $dep_id = "", $typact = "", $xid = ""): int
{
    global $session, $config, $avertissement, $u_db;

    $lstactvus = [];

    if ($config->get('GEST_POINTS') == 0 or $config->get('PUBLIC_LEVEL') >= 4 or $cout == 0) { // pas de gestion des points si .... !!
        return 1;  // pas de gestion des points
    }
    if (isset($_COOKIE['viewlst'])) {
        $lstactvus = explode(',', decrypter($_COOKIE['viewlst'], 'solde'));
    }

    $sql = "SELECT * FROM " . $config->get('EA_DB') . "_user3 WHERE login = '" . $session->get('user')['login'] . "'";
    $res = EA_sql_query($sql, $u_db);
    if ($res and EA_sql_num_rows($res) != 0) {
        $row = EA_sql_fetch_array($res);
        $userid = $row['ID'];
        if (($row['level'] >= 8) or ($row['regime'] == 0) or ($userid == $dep_id)) {
            // On note seulement la consultation
            $newconso = $row['pt_conso'] + $cout;
            $reqmaj = "UPDATE " . $config->get('EA_DB') . "_user3 SET pt_conso = " . $newconso . " WHERE ID=" . $userid . "";
            $result = EA_sql_query($reqmaj, $u_db);
            //$avertissement .= 'Solde inchangé ('.$lesolde. ' points) car vous avez déposé cet acte';
            return 1; // pas de restriction pour cet utilisateur car immunisé ou déposant
        } else {
            $lesolde = $row['solde'];
            $cle = $typact . number_format($xid, 0, '', '');
            if (!(array_search($cle, $lstactvus) === false)) {
                $avertissement .= 'Déjà examiné ce jour : solde inchangé (' . $lesolde . ' points)';
                return 1;
            } // déja vu => cout nul
            else {
                if ($lesolde >= 1) {
                    // imputer le cout
                    array_push($lstactvus, $cle);
                    $newsolde = max($lesolde - $cout, 0);
                    $newconso = $row['pt_conso'] + $cout;
                    $reqmaj = "UPDATE " . $config->get('EA_DB') . "_user3 SET solde = " . $newsolde . ", pt_conso = " . $newconso . " WHERE ID=" . $userid . "";
                    if ($result = EA_sql_query($reqmaj, $u_db)) {
                        $avertissement .= 'Il vous reste à présent ' . $newsolde . ' points';  // passé par variable globale
                    } else {
                        echo 'Erreur dans la gestion des points ';
                    }
                    setcookie('viewlst', crypter(implode(',', $lstactvus), 'solde'));
                    return $lesolde;  // solde avant retrait
                } else {
                    $avertissement .= 'Votre solde de points est épuisé !';  // passé par variable globale
                    if ($row['regime'] == 2) {
                        $datecredit = date("d-m-Y", strtotime($row['maj_solde']) + ($config->get('DUREE_PER_P') * 86400));
                        $avertissement .= '<br /> <br />Il sera automatiquement crédité de ' . $config->get('PTS_PAR_PER') . ' points le ' . $datecredit . '.';
                    }
                    return 0;
                }
            }
        }
    }
}

function recharger_solde()
{
    global $session, $config, $avertissement, $u_db;

    if (!$session->has('user')) {
        return;
    }   // hors connexion, rien à recharger

    // recharge SI conditions remplies par le compte de l'utilisateur
    $lesolde = $session->get('user')['solde'];
    $userid = $session->get('user')['ID'];
    if (($session->get('user')['regime'] == 2) && ($session->get('user')['level'] < 8)) {
        // recharger si nécessaire
        if ((strtotime("now") - ($config->get('DUREE_PER_P') * 86400)) >= strtotime($session->get('user')['maj_solde'])) {
            if ($lesolde < $config->get('PTS_PAR_PER')) { // pour ne pas supprimer des points "bonus" on attend.
                $lesolde = $config->get('PTS_PAR_PER');
                $reqmaj = "UPDATE " . $config->get('EA_DB') . "_user3 SET solde = " . $lesolde . ", maj_solde = '" . date("Y-m-d", time()) . "' WHERE ID=" . $userid . "";
                if ($result = EA_sql_query($reqmaj, $u_db)) {
                    $avertissement .= 'Votre compte a été automatiquement crédité de ' . $config->get('PTS_PAR_PER') . ' points<br>'; // passé par variable globale
                } else {
                    echo 'Erreur dans gestion des points ';
                    echo '<p>' . EA_sql_error() . '<br>' . $reqmaj . '</p>';
                }
            }
        }
    }
}

function current_user_solde()
{
    global $session, $config;

    if ($config->get('GEST_POINTS') == 0 || $session->get('user')['regime'] == 0 || $session->get('user')['level'] >= 8) {
        return 9999;
    }

    return $session->get('user')['solde'];
}

function show_solde()
{
    $html_li = '';
    $solde = current_user_solde();

    if ($solde < 9999) {
        if ($solde > 0) {
            $html_li = '<li>Solde : ' . $solde . 'pts</li>';
        } else {
            $html_li = '<li class="danger">Solde : ' . $solde . 'pts</li>';
        }
    }

    return $html_li;
}

function annee_seulement($date_txt)  // affichage date simplifié à l'annee si droits limités
{
    global $session, $config;

    if (
        ($config->get('ANNEE_TABLE') >= 3)
        || ($config->get('ANNEE_TABLE') >= 1 && $session->has('user')['ID'] == 0)
        || ($config->get('ANNEE_TABLE') >= 2 && $session->get('user')['level'] < 5)
        || (current_user_solde() == 0)
    ) {
        $dtsql = "";
        $bad = 0;
        $date_txt = ajuste_date($date_txt, $dtsql, $bad);
        return mb_substr($date_txt, strrpos($date_txt, "/") + 1);
    }

    return $date_txt; // date complète
}

function stats_1_comm($xtyp, $lacom)
{
    global $config;
    echo '<p>Traitement de <b>' . $lacom . "</b></p>";
    switch ($xtyp) {
        case "N":
            $table = $config->get('EA_DB') . "_nai3";
            $libel = "'' AS LIBELLE,";
            break;
        case "V":
            $table = $config->get('EA_DB') . "_div3";
            $libel = "LIBELLE,";
            break;
        case "M":
            $libel = "'' AS LIBELLE,";
            $table = $config->get('EA_DB') . "_mar3";
            break;
        case "D":
            $table = $config->get('EA_DB') . "_dec3";
            $libel = "'' AS LIBELLE,";
            break;
    }
    $sql = "SELECT COMMUNE, DEPART, " . $libel
        . " count(*) AS ctot,"
        . "  DEPOSANT, max(DTDEPOT) AS ddepot,"
        . "  min(if(year(LADATE)>0,year(LADATE), null)) AS dmin,"  // null indispensabel pour que le tri élimine les 0
        . "  max(year(LADATE)) AS dmax, "
        . "  sum(if(length(concat_ws('',P_PRE,M_NOM,M_PRE))>0,1,0)) AS cfil,"
        . "  sum(if(year(LADATE)>0,1,0)) AS cnnul"
        . " FROM " . $table
        . " WHERE COMMUNE='" . sql_quote($lacom) . "'"
        . " GROUP BY COMMUNE,DEPART,LIBELLE,DEPOSANT; ";

    $result = EA_sql_query($sql);
    $reqdel = "DELETE FROM " . $config->get('EA_DB') . "_sums WHERE TYPACT = '" . $xtyp . "' AND COMMUNE='" . sql_quote($lacom) . "'";
    $resdel = EA_sql_query($reqdel);

    while ($ligne = EA_sql_fetch_array($result)) {
        $reqins = "INSERT INTO " . $config->get('EA_DB') . "_sums (COMMUNE,DEPART,TYPACT,LIBELLE,DEPOSANT,DTDEPOT,AN_MIN,AN_MAX,NB_TOT,NB_N_NUL,NB_FIL,DER_MAJ) VALUES ("
            . "'" . sql_quote($ligne['COMMUNE']) . "', "
            . "'" . sql_quote($ligne['DEPART']) . "', "
            . "'" . $xtyp . "', "
            . "'" . sql_quote($ligne['LIBELLE']) . "', "
            . $ligne['DEPOSANT'] . ", "
            . "'" . sql_quote($ligne['ddepot']) . "', "
            . iif($ligne['dmin'] == '', 0, $ligne['dmin']) . ", "
            . $ligne['dmax'] . ", "
            . $ligne['ctot'] . ", "
            . $ligne['cnnul'] . ", "
            . $ligne['cfil'] . ", "
            . "'" . date("Y-m-d H:i:s", time()) . "'); ";
        //echo "<p>".$reqins;
        if ($ligne['dmax'] == 0) {
            msg('Les dates de ' . $ligne['COMMUNE'] . ' sont mal encodées');
        }
        if ($resins = EA_sql_query($reqins)) {
            // ajout ok
        } else {
            echo "Insertion non réalisée";
            echo '<p>' . EA_sql_error() . '<br />' . $reqins . '</p>';
        }
    }
}

function maj_stats($xtyp, $T0, $path, $mode, $com = "", $dep = "")
// mode : A = all, C=Commune unique, N=Next commune (qd All pas terminé)
{
    global $root, $config;
    if ($mode == "C") {
        $tpsreserve = min(3, ini_get("max_execution_time") / 2);
    } else {
        $tpsreserve = min(10, ini_get("max_execution_time") / 2);
    }

    $Max_time = ini_get("max_execution_time") - $tpsreserve;
    if (time() - $T0 > $Max_time) {
        echo "<p>Les statistiques n'ont pas pu être recalculées immédiatement.<br>";
        echo '<a href="' . $root . '/admin/actes/statistiques?xtyp=' . $xtyp . '&mode=' . $mode . '&com=' . urlencode($com) . '">Cliquez ici pour recalculer ces statistiques</a></p>';
    } else {
        switch ($xtyp) {
            case "N":
                $typ = "Naissances/Baptêmes";
                $table = $config->get('EA_DB') . "_nai3";
                break;
            case "V":
                $typ = "Actes divers";
                $table = $config->get('EA_DB') . "_div3";
                break;
            case "M":
                $typ = "Mariages";
                $table = $config->get('EA_DB') . "_mar3";
                break;
            case "D":
                $typ = "Décès/Sépultures";
                $table = $config->get('EA_DB') . "_dec3";
                break;
        }

        $Max_time = ini_get("max_execution_time") - 2;

        if ($mode == "C" or $mode == "D") {
            stats_1_comm($xtyp, $com);
            if ($mode == "C") {
                geoloc_1_com($com, $dep);
            }
        } else {
            if ($mode == "A") {
                $reqdel = "DELETE FROM " . $config->get('EA_DB') . "_sums WHERE TYPACT = '" . $xtyp . "'";
                echo "<p>Suppression des statistiques existantes</p>";
                $resdel = EA_sql_query($reqdel);
            }
            $reqbase = "set sql_big_selects=1";
            $resbase = EA_sql_query($reqbase);
            $reqbase = "SELECT DISTINCT COMMUNE,DEPART FROM " . $table . " ORDER BY COMMUNE";
            $resbase = EA_sql_query($reqbase);

            $listcomm = "";
            $timeisup = false;
            while ($comm = EA_sql_fetch_array($resbase) and !$timeisup) {
                $lacom = $comm['COMMUNE'];
                $ledep = $comm['DEPART'];
                $listcomm .= ",'" . sql_quote($lacom) . "'";
                if ($mode == "A" or mb_strtolower($lacom) >= mb_strtolower($com)) {
                    stats_1_comm($xtyp, $lacom);
                    my_flush(100); // Assure un affichage régulier
                    geoloc_1_com($lacom, $ledep);
                }
                $timeisup = (time() - $T0 >= $Max_time);
            }
        }
        if ($mode == "A" or $mode == "N") {
            if ($timeisup) {
                echo "<p><b>Mise à jour INCOMPLETE des statistiques des " . $typ . " </b></p>";
                //echo "<p>Pour continuer le calcul des statistiques cliquez le lien suivant :<br>";
                echo '<a href="' . $root . '/admin/actes/statistiques?xtyp=' . $xtyp . '&mode=N&com=' . urlencode($lacom) . '">Cliquez ici pour CONTINUER le recalcul de ces statistiques</a></p>';
            } else {
                echo "<p><b>Mise à jour globale des statistiques des " . $typ . " terminée.</b></p>";
            }
        } else {
            echo "<p><b>Les statistiques ont été recalculées.</b></p>";
        }
    }
}

/**
 * Interroge google pour pour connaitre les coordonnées d'une commune
 */
function geocode_google($com, $dep)
{
    include_once(__DIR__ . '/GoogleMap/OrienteMap.inc.php');
    include_once(__DIR__ . '/GoogleMap/Jsmin.php');

    global $carto;
    if (!isset($carto)) {
        $carto = new GoogleMapAPI();
        $carto->_minify_js = isset($_REQUEST["min"]) ? false : true;
    }
    $coord = $carto->geoGetCoords(remove_accent($com) . ", " . remove_accent($dep));
    if (!$coord) {
        $coord = array();
        $coord['lon'] = 0;
        $coord['lat'] = 0;
    }
    return $coord;
}

function geoloc_1_com($com, $dep)
{
    global $config;
    $reqbase = "SELECT STATUT FROM " . $config->get('EA_DB') . "_geoloc WHERE COMMUNE = '" . sql_quote($com) . "' AND DEPART = '" . sql_quote($dep) . "'";
    $res = EA_sql_query($reqbase);
    if ($res and EA_sql_num_rows($res) != 0) {
        $ligne = EA_sql_fetch_array($res);
        if ($ligne['STATUT'] == 'N') {
            $rech = 2;
        } // pas trouvé la dernière fois
        else {
            $rech = 0;
        } // présent
    } else {
        $rech = 1;
    } // absent
    if ($rech >= 1) { // il faut geocoder
        //echo "<p>Recherche de ".$com."/".$dep;
        $coord = geocode_google($com, $dep);
        if ($coord['lon'] == 0 && $coord['lat'] == 0) {
            $statut = 'N';
        } // Non trouvé
        else {
            $statut = 'A';
        } // Automatique
        if ($rech == 1) {
            $reqmaj = "INSERT INTO " . $config->get('EA_DB') . "_geoloc (COMMUNE,DEPART,LON,LAT,STATUT)"
                . " VALUES ('" . sql_quote($com) . "','" . sql_quote($dep) . "'," . $coord['lon'] . "," . $coord['lat'] . ",'" . $statut . "')";
        } else {
            $reqmaj = "UPDATE " . $config->get('EA_DB') . "_geoloc SET LON=" . $coord['lon'] . ", LAT=" . $coord['lat'] . ",STATUT='" . $statut . "'"
                . " WHERE COMMUNE = '" . sql_quote($com) . "' AND DEPART = '" . sql_quote($dep) . "'";
        }
        $result = EA_sql_query($reqmaj);
        //echo $reqmaj;
        if ($coord['lat'] <> 0) {
            echo "<p>" . $com . " [" . $dep . "] a été géocodé en " . $coord['lon'] . "," . $coord['lat'] . "</p>";
        } else {
            echo "<p>" . $com . " [" . $dep . "] n'a pu être géocodé</p>";
        }
    }
}

function test_geocodage($show = false)
{
    $coord = geocode_google("Paris", "France");
    $xx = $coord['lon'] + $coord['lat'];
    $gok = true;
    if (($xx > 51) && ($xx < 52)) {
        $msg = "<p>Le géocodage fonctionne normalement</p>";
    } else {
        $gok = false;
        $msg = "<p><b>Le géocodage NE fonctionne PAS normalement</b> : cela PEUT notamment provenir de l'hébergement qui empêche le recours aux web services !</b></p>";
    }
    if ($show) {
        echo $msg;
    }
    return $gok;
}

function geoNote($Commune, $Depart, $atyp)
{
    global $config, $gid;
    $georeq = "SELECT ID,LON,LAT,NOTE_" . $atyp . " FROM " . $config->get('EA_DB') . "_geoloc WHERE COMMUNE = '" . sql_quote($Commune) . "' AND  DEPART = '" . sql_quote($Depart) . "'";
    $geores =  EA_sql_query($georeq);
    $note = '';
    if ($geo = EA_sql_fetch_array($geores)) {
        $gid = $geo['ID'];
        $lon = $geo['LON'];
        $lat = $geo['LAT'];
        if ($lon == 0 && $lat == 0) {
            $gid = 0;
        } // indique de ne pas afficher la carte
        $note = $geo['NOTE_' . $atyp];
    }

    return $note;
}

/**
 * retourne le code de contrôle relatif au couple nom et prenom
 */
function ctrlxid($nom, $pre)
{
    $c1 = 13;
    $c2 = 19;
    if (!empty($nom)) {
        $c1 = (ord($nom[0]) + 3);
    }

    if (!empty($pre)) {
        $c2 = (ord($pre[0]) + 7);
    }

    return $c1 * $c2;
}

// recupère la liste des dates des derniers backups
function get_last_backups(): array
{
    global $config;

    $temp = explode(';', $config->get('EA_LSTBACKUP'));
    $list_backups = [];
    foreach ($temp as $tp) {
        $list_backups[mb_substr($tp, 0, 1)] = mb_substr($tp, 2);
    }

    return $list_backups;
}

// enregistre la liste des dates des derniers backups
function set_last_backups($list_backups)
{
    global $config;
    $laliste = '';
    foreach ($list_backups as $btyp => $bdate) {
        $laliste .= $btyp . ":" . $bdate . ";";
    }
    $sql = "UPDATE " . $config->get('EA_DB') . "_params SET valeur='" . $laliste . "' WHERE param='EA_LSTBACKUP'";
    $result = EA_sql_query($sql);
    return $result;
}

function show_last_backup($filtre = "NMDVUP")
{
    $list_backups = get_last_backups();
    $resu = '';
    foreach ($list_backups as $btyp => $bdate) {
        if (isin($filtre, $btyp) >= 0) {
            $resu .= typact_txt($btyp) . " : " . showdate($bdate) . '<br>';
        }
    }
    return $resu;
}

/**
 * @param array $params [$xtdiv, $userlevel, $userid, $olddepos, $TypeActes, $AnneeDeb, $AnneeFin, $comdep]
 * @return array [$table, $ntype, $soustype, $condcom, $condad, $condaf, $condtdiv, $conddep]
 */
function set_cond_select_actes(array $params): array
{
    global $session, $config;

    $deed_tables = [
        'N' => ['libele' => 'naissances', 'table' => $config->get('EA_DB') . '_nai3'],
        'M' => ['libele' => 'mariages', 'table' => $config->get('EA_DB') . '_mar3'],
        'D' => ['libele' => 'décès', 'table' => $config->get('EA_DB') . '_dec3'],
        'V' => ['libele' => 'type divers', 'table' => $config->get('EA_DB') . '_div3'],
    ];

    $soustype = $condtdiv = $condad = $condaf = $conddep = '';
    $table = $deed_tables[$params['TypeActes']]['table'];
    $type = $deed_tables[$params['TypeActes']]['libele'];

    if ($params['TypeActes'] == 'V') {
        if (($params['xtdiv'] <> '') && (mb_substr($params['xtdiv'], 0, 2) <> "**")) {
            $condtdiv = " AND (LIBELLE = '" . sql_quote(urldecode($params['xtdiv'])) . "')";
            $soustype = " (" . $params['xtdiv'] . ")";
        }
    }
    if ($params['AnneeDeb'] <> '') {
        $condad = " AND year(LADATE) >= " . $params['AnneeDeb'];
    }
    if ($params['AnneeFin'] <> '') {
        $condaf = " AND year(LADATE) <= " . $params['AnneeFin'];
    }
    if ($session->get('user')['level'] < 8) {
        $conddep = " AND DEPOSANT = " . $session->get('user')['ID'];
    } elseif ($params['olddepos'] > 0) {
        $conddep = " AND DEPOSANT = " . $params['olddepos'];
    }

    if (mb_substr($params['comdep'], 0, 4) == "TOUS") {
        $condcom = " NOT (ID IS NULL) ";
    } else {
        $Commune = communede($params['comdep']);
        $Depart  = departementde($params['comdep']);
        $condcom = " COMMUNE = '" . sql_quote($Commune) . "' AND DEPART = '" . sql_quote($Depart) . "'";
    }

    return [$table, $type, $soustype, $condcom, $condad, $condaf, $condtdiv, $conddep];
}

/**
 * Retourne la chaine la plus en avant par ordre alphabétique
 */
function strmin($str1, $str2)
{
    if ($str1 > $str2) {
        return $str2;
    }

    return $str1;
}

/**
 * Retourne la chaine la plus en arriere par ordre alphabétique
 */
function strmax($str1, $str2)
{
    if ($str1 < $str2) {
        return $str2;
    }

    return $str1;
}
