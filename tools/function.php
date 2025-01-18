<?php

include_once(__DIR__ . '/EA_sql.inc.php');

define('INTERNAL_CHARSET', 'UTF-8');
define('MAIL_CHARSET', INTERNAL_CHARSET); // Charset pour les mails

define('ENTITY_CHARSET', INTERNAL_CHARSET); // POUR htmlspecialchars, htmlentities, html_entities_decode, htmlspecialchars_decode (il n'y en a pas pour version 3.2)
mb_internal_encoding(INTERNAL_CHARSET);
//define('ENTITY_REPLACE_FLAGS', ENT_COMPAT | ENT_XHTML); // idem ci dessus
define('ENTITY_REPLACE_FLAGS', ENT_COMPAT | 16); // idem ci dessus (ENT_XHTML n'est pas défini avant  php 5.4)
define('EL', '');  // definie mais vide

/**
 * Converti une chaine Windows en UTF8 après avoir recodé le caractère 134 : †
 * @param string $text
 * @return string
 */
function ea_utf8_encode($text)
{
    //$text = str_replace(chr(134), "+=+", $text);
    //$text = utf8_encode($text);
    //$text = str_replace("+=+",chr(226).chr(128).chr(160), $text);
    $text = iconv("Windows-1252", "UTF-8", $text);
    return $text;
}

/**
 * Converti une chaine UTF8 en Windows avec recodage du caractère 134 : †
 * @param string $text
 * @return string
 */
function ea_utf8_decode($text)
{
    //$text = str_replace(chr(226).chr(128).chr(160), "+=+", $text);
    //$text = utf8_decode($text);
    //$text = str_replace("+=+",chr(134), $text);
    $text = iconv("UTF-8", "Windows-1252", $text);
    return $text;
}

function return_bytes($val)
{  // conversion des valeurs de paramètres PHP de type 2M ou 256K
    $val = trim($val);
    $last = strtolower($val[strlen($val) - 1]);
    $val = substr($val, 0, -1);     // BG: 28/10/2021: enlever le dernier caractère pour n'avoir qu'une valeur numérique
    switch ($last) {
            // Le modifieur 'G' est disponible depuis PHP 5.1.0
        case 'g':
            $val *= 1024;
            // no break
        case 'm':
            $val *= 1024;
            // no break
        case 'k':
            $val *= 1024;
    }
    return $val;
}

function isin($grand, $petit, $debut = 0) // retourne la position de $petit dans $grand ou -1 si non présent
{
    //echo "recherche de ".$petit." dans ".$grand.".";
    if ($petit == "") {
        return -1;
    }

    $pos = mb_strpos($grand, $petit, $debut);
    if ($pos === false) {
        return -1;
    }

    return $pos;
}

/**
 * @deprecated Use Symfony\Component\HttpFoundation\Request insteed.
 */
function getparam($name, $default = "")
{
    if (isset($_REQUEST[$name])) {
        return $_REQUEST[$name];
    }

    return $default;
}

function mydir($dir, $ext) // retourne la liste des fichiers d'un répertoire
// (et remplace la fonction glob qui n'est pas supportée sur tous les hébergements)
{
    $files = array();
    $extup = strtoupper($ext); // mettre le .
    $lext = strlen($extup);
    if (is_dir($dir)) {
        if ($dh = opendir($dir)) {
            while (($file = readdir($dh)) !== false) {
                if (strtoupper(mb_substr($file, - ($lext))) == $extup) {
                    $files[] = $dir . $file;
                }
            }
        }
    }
    sort($files);
    return $files;
}

function iif($bool, $vrai, $faux) // is bool alors vrai sinon faux
{
    if ($bool) {
        return $vrai;
    }

    return $faux;
}

function div($nbre, $diviseur) // division entière
{
    return floor($nbre / $diviseur);
}

function entier($nbre) // pour mettre au format entier avec séparateur
{
    if ($nbre == '') {
        return '';
    }

    return number_format(intval($nbre), 0, ',', '.');
}

function ads_explode($chaine, $separ, $nbre) // explose une chaines dans un tableau et complete si besoin les cases manquantes
{
    $tableau = explode($chaine, $separ);
    if (count($tableau) < $nbre) {
        for ($i = count($tableau); $i < $nbre; $i++) {
            $tableau[$i] = "";
        }
    }
    return $tableau;
}

function zeros($nbre, $chiffres) // pour précéder un entier de 0  (ex 006)
{
    return mb_substr('0000000000' . $nbre, -$chiffres, $chiffres);
}

function multisin($grand, $listpetits)
{ // retourne la position de la première petite chaine trouvée dans la grande
    $resu = -1;
    foreach ($listpetits as $petit) {
        if (isin($grand, $petit) >= 0) {
            $resu = isin($grand, $petit);
            break;
        }
    }
    return $resu;
}

function linkifie($texte, $mode)  // transforme en lien actif les noms de fichier image et les URL rencontrées
// mode : 0 = ne rien faire, >0 activer les lien : 1 = séparés par des , ou des blancs ou  2 = séparés par des ;
{
    global $session, $config;
    
    $separs = " , "; // (séparés par des , ou des blancs)
    $Saut_Ou_Espace = ' ';

    if ($mode == "2") {
        $separs = ";";
        $Saut_Ou_Espace = '<br/>';
    } 
    
    $listExtImages = array(".JPG", ".TIF", ".PNG", ".PDF", ".GIF");
    $cpt = 0;  // N° de l'image

    // $URL_JPG = "http://site1/;000>http://site2/;RFT_>http://site3/"; exemple de format
    $uptexte = mb_strtoupper($texte);
    if (multisin($uptexte, $listExtImages) < 0 and isin($uptexte, 'HTTP://') < 0 and isin($uptexte, 'HTTPS://') < 0) {
        // pas d'image ni d'url donc aucune manipulation
        return $texte;
    } else {
        // préparation du tableau des substitutions
        $prefix = array();
        $siturl = array();
        $modurl = array();

        $lbase = explode(";", $config->get('URL_JPG'));
        if (count($lbase) == 0) {
            $base_url = "";
        } else {
            $base_url = $lbase[0];
        }
        foreach ($lbase as $code) {
            $pl = isin($code, '>>');
            if ($pl > 0) {
                $prefix[] = trim(mb_strtoupper(mb_substr($code, 0, $pl)));
                $siturl[] = trim(mb_substr($code, $pl + 2));
                $modurl[] = "R";  // Remplacement du préfixe
            } else {
                $pl = isin($code, '>');
                if ($pl > 0) {
                    $prefix[] = trim(mb_strtoupper(mb_substr($code, 0, $pl)));
                    $siturl[] = trim(mb_substr($code, $pl + 1));
                    $modurl[] = "A";  // Ajout d'un préfixe
                }
            }
        }
        // echo '<pre>'.$uptexte.'<br>'; print_r($prefix); print_r($siturl); print_r($modurl); echo '</pre>';

        // découpe du texte en fragments
        $list = array();
        $fragment = "";
        for ($k = 0; $k < mb_strlen($texte); $k++) {
            $char = mb_substr($texte, $k, 1);
            //echo '.'.$char;
            if (isin($separs, $char) >= 0) {
                $list[] = $fragment;
                $fragment = "";
            } else {
                $fragment .= $char;
            }
        }
        $list[] = $fragment;

        //echo '<pre>FRAG:';print_r($list);echo '</pre>';
        // analyse et reconstruction de la chaine
        $result = '';
        $ecarte_element = '';
        foreach ($list as $image) {
            if (isin(mb_strtoupper($image), 'IFRAME') == -1) {
                // Traitement des URL directes HTTP://
                $l = isin(mb_strtoupper($image), 'HTTP://');
                if ($l == -1) {
                    $l = isin(mb_strtoupper($image), 'HTTPS://');
                }
                if ($l >= 0) {
                    $debut = mb_substr($image, 0, $l);
                    $url = mb_substr($image, $l);
                    //echo "<p>L=".$l."LEN=".strlen($image)." URL=".$url;
                    $cpt++;
                    if (!$session->has('user') && ($config->get('JPG_SI_LOGIN') == 1)) {
                        $lien = 'Document' . $cpt . ' privé';
                    } else {
                        $lien = '<a href="' . $url . '" target="_blank">Document' . $cpt . '</a> ';
                    }
                    $result .= $ecarte_element . $debut . $lien;
                } else { //traitement des vraies images
                    $uptexte = trim(mb_strtoupper($image));
                    $l = multisin($uptexte, $listExtImages);
                    if ($l >= 0) {
                        $lesite = "";
                        for ($ii = 0; $ii < count($prefix); $ii++) {
                            if (mb_substr($uptexte, 0, mb_strlen($prefix[$ii])) == $prefix[$ii]) {
                                $lesite = $siturl[$ii];
                                if ($modurl[$ii] == "R") {
                                    $image = mb_substr(trim($image), mb_strlen($prefix[$ii]));
                                }
                                break;
                            }
                        }
                        if ($lesite == "") {
                            $lesite = $base_url;
                        }  // valeur par défaut
                        $urlimage = strtr($lesite . $image, "\\", "/");  // remplace les \ par des / (simples !)
                        $ipublic = true;
                        if (isin(mb_strtoupper($urlimage), 'PRIVE') > -1) {
                            $ipublic = false;
                            if ($session->get('user')['level'] < $config->get('LEVEL_JPG_PRIVE')) {
                                $result .= " ";
                            }    // On ne montre pas si prive et pas le niveau suffisant
                            else {
                                $ipublic = true;
                            }
                        } elseif (($session->has('user') == false) && ($config->get('JPG_SI_LOGIN') == 1)) {
                            $cpt++;
                            $result .= $ecarte_element . 'Image' . $cpt . ' privée';    // On ne montre pas car login obligatoire
                            $ipublic = false;
                        }

                        if ($ipublic) {
                            $cpt++;
                            $result .= $ecarte_element . '<a href="' . $urlimage . '" target="_blank">Image' . $cpt . '</a> ';
                        }
                    } else {
                        $result .= $ecarte_element . $image;
                    }
                }
            }  // iframe ?
            else {
                $result .= $ecarte_element . $image;
            }
            $ecarte_element = $Saut_Ou_Espace;
        } // foreach
        return $result;
    }
}

function sql_and($cond) // ajoute and si condition non vide
{
    if ($cond != "") {
        return $cond . " AND ";
    }

    return "";
}

function sql_quote($texte) // pour passer texte a MySQL en escapant les ' " \ ...
{
    $result = EA_sql_real_escape_string(trim($texte));
    return $result;
}

function sql_virgule($cond, $add) // ajoute , si liste non vide
{
    if ($cond != "") {
        return $cond . ", " . $add;
    }

    return $add;
}

function heureminsec($totalsecondes)
{
    $secondes = intval($totalsecondes % 60);
    $minutes = (intval($totalsecondes / 60)) % 60;
    $heures = (intval($totalsecondes / (60 * 60)) % 24);
    $jours = intval($totalsecondes / (60 * 60 * 24));
    return sprintf("%02dj %02dh %02d' %02d\"", $jours, $heures, $minutes, $secondes);
}

function val_var_mysql($label)
{
    if ($result = EA_sql_query("SHOW VARIABLES LIKE '" . $label . "'")) {
        $row = EA_sql_fetch_assoc($result);
        return $row['Value'];
    }

    return "??";
}

// décompose selon ; ou tab en respectant les guillements éventuels
function explode_csv($line)
{
    $l = strlen($line);
    $j = 0;
    $res = array("");
    $guill = false;
    for ($i = 0; $i < $l; $i++) {
        if ($line[$i] == '"') {
            if ($guill) {
                $guill = false;
            } else {
                $guill = true;
            }
        } elseif (($line[$i] == ';' or $line[$i] == chr(9)) and !$guill) {
            $j++;
            $res[$j] = "";
        } else {
            $res[$j] .= $line[$i];
        }
    }
    return $res;
}

function is__writable($path, $show = true)  // Teste si acces en ecriture est possible : attention aux deux _ _
{
    if ($path[strlen($path) - 1] == '/') {
        return is__writable($path . uniqid(mt_rand()) . '.tmp', $show);
    }

    if ($show) {
        echo '<p>Test de création du fichier ' . $path . '';
    }

    if (file_exists($path)) {
        if (!($f = @fopen($path, 'r+'))) {
            return false;
        }
        fclose($f);
        return true;
    }
    if (!($f = @fopen($path, 'w'))) {
        return false;
    }
    fclose($f);
    unlink($path);
    return true;
}

function permuter(&$un, &$deux)
{
    $trois = $un;
    $un = $deux;
    $deux = $trois;
}

function remove_accent($txt)
{ // adaptée UTF-8
    $tofind = array('À', 'Á', 'Â', 'Ã', 'Ä', 'Å', 'à', 'á', 'â', 'ã', 'ä', 'å', 'Ò', 'Ó', 'Ô', 'Õ', 'Ö', 'Ø', 'ò', 'ó', 'ô', 'õ', 'ö', 'ø', 'È', 'É', 'Ê', 'Ë', 'è', 'é', 'ê', 'ë', 'Ç', 'ç', 'Ì', 'Í', 'Î', 'Ï', 'ì', 'í', 'î', 'ï', 'Ù', 'Ú', 'Û', 'Ü', 'ù', 'ú', 'û', 'ü', 'ÿ', 'Ñ', 'ñ');
    $replac = array('A', 'A', 'A', 'A', 'A', 'A', 'a', 'a', 'a', 'a', 'a', 'a', 'O', 'O', 'O', 'O', 'O', 'O', 'o', 'o', 'o', 'o', 'o', 'o', 'E', 'E', 'E', 'E', 'e', 'e', 'e', 'e', 'C', 'c', 'I', 'I', 'I', 'I', 'i', 'i', 'i', 'i', 'U', 'U', 'U', 'U', 'u', 'u', 'u', 'u', 'y', 'N', 'n');
    return (str_replace($tofind, $replac, $txt));
}

function con_une_db($ladbaddr, $ladbuser, $ladbpass, $ladbname, $show = false, $new_link = false) // fonction de connexion à une DB
{
    global $dbok;

    if (file_exists('tools/function.php')) {
        $EA_Appel_dOu = '';
        $EA_Script_Courant = basename($_SERVER['PHP_SELF']);
    } else {
        $EA_Appel_dOu = '../';
        $EA_Script_Courant = basename(dirname($_SERVER['PHP_SELF'])) . '/' . basename($_SERVER['PHP_SELF']); // Ne pas utiliser DIRECTORY_SEPARATOR
    }

    if ($ladbaddr == '@@serveur_BD@@') {
        $dblink = false;
    } elseif (function_exists('EA_sql_connect')) {
        $dblink = @EA_sql_connect("$ladbaddr", "$ladbuser", "$ladbpass", $new_link);
    } else {
        $dblink = @mysqli_connect("$ladbaddr", "$ladbuser", "$ladbpass", "$ladbname");
    }
    if ($dblink) {
        if ($show) {
            echo '<p>Connexion au serveur MySQL :<b> OK</b></p>';
        }
        $dbok = EA_sql_select_db("$ladbname", $dblink);
        if ($dbok) {
            EA_sql_query('SET NAMES utf8', $dblink);  // oblige MySQL à répondre en UTF-8  (ISO-8859-1 par défaut)
            if ($show) {
                echo '<p>Connexion &agrave; la base de donn&eacute;es : <b> OK</b></p>';
            }
        } else {
            if (in_array($EA_Script_Courant, array('admin/index.php', 'install/install.php', 'install/update.php'))) {
                echo '<a href="' . $EA_Appel_dOu . 'install/configuration.php">Configurer la base de donn&eacute;es</a>';
            } else {
                msg("012 : La base sp&eacute;cifi&eacute;e n'est pas accessible sur le serveur MySQL : " . EA_sql_error());
            }
            exit(0);
        }
        return $dblink;
    } else {
        if (in_array($EA_Script_Courant, array('admin/index.php', 'install/install.php', 'install/update.php', 'index.php'))) {
            echo '<a href="' . $EA_Appel_dOu . 'install/configuration.php">Configurer la base de donn&eacute;es</a>';
        } else {
            msg("011: Impossible d'ouvrir la connexion au serveur MySQL avec l'utilisateur pr&eacute;sent&eacute; : " . EA_sql_error());
        }
        exit(0);
    }
}

function con_db($show = false) // fonction de connexion des DB
{
    global $dbaddr, $dbuser, $dbpass, $dbname, $a_db, $dbok;
    global $udbaddr, $udbuser, $udbpass, $udbname, $u_db;

    if (isset($udbaddr, $udbuser, $udbpass, $udbname)) {
        if ($show) {
            echo '<p><b>Base des utilisateurs :</b></p>';
        }
        $u_db = con_une_db($udbaddr, $udbuser, $udbpass, $udbname, $show);
        if ($show) {
            echo '<p><b>Base des actes :</b></p>';
        }
        $a_db = con_une_db($dbaddr, $dbuser, $dbpass, $dbname, $show, true);
    } else {
        if ($show) {
            echo '<p><b>Base des actes et des utilisateurs :</b></p>';
        }
        $u_db = $a_db = con_une_db($dbaddr, $dbuser, $dbpass, $dbname, $show);
    }
    return $a_db;
}

/**
 * @deprecated Use array form_errors insteed.
 */
function msg($desc, $type = "erreur")
{
    global $root;
    if ($desc <> null) {
        echo "<p class=\"$type\">$desc</p>";
        if (intval($desc) > 0) {
            echo '<p>Consultez la liste des <a href="' . $root . '/admin/aide/codeserreurs.html">codes d\'erreurs</a>.</p>';
        }
    }
}

function update_sql_mode($allow_mode = '', $replace = false)
{
    /*
    ''        false : Réinitialise le mode par défaut
    ''        true  : Remplace le mode par rien, donc ote toutes les définitions du mode
    'mode'    true  : Remplace le mode indiqué par rien, donc le supprime
    'mode'    false : Ajoute le mode indiqué
    */
    if ($allow_mode == '') {
        if ($replace) {
            $r = "SET @@SESSION.SQL_MODE = '';";
        } else {
            $r = "SET @@SESSION.SQL_MODE = (SELECT @@GLOBAL.SQL_MODE);";
        } // DOIT FAIRE PAREIL = RESET
    } else {
        if ($replace) {
            $r = "SET SESSION sql_mode = (SELECT REPLACE(@@GLOBAL.SQL_MODE,'" . $allow_mode . "',''))";
        } else {
            $r = "SET SESSION sql_mode = (SELECT CONCAT(@@GLOBAL.SQL_MODE, '," . $allow_mode . "'))";
        }
    }
    $res = EA_sql_query($r);
    return;
}

function writelog($texte, $commune = "-", $nbactes = 0)
{
    global $config, $session;

    $time = date("Y-m-d H:i:s", time());
    if (!$session->has('user')) {
        $user = 'Unidentified user';
        $texte = $_SERVER['REMOTE_ADDR'] . ":" . $texte;
    } else {
        $user = $session->get('user')['ID'];
    }
    $sql = "INSERT INTO " . $config->get('EA_DB') . "_log VALUES ($user, '$time', '" . sql_quote($texte) . "','" . sql_quote($commune) . "',$nbactes)";
    update_sql_mode('STRICT_TRANS_TABLES', true); // sur la session, lève le contrôle strict = longueur de champ
    EA_sql_query($sql);
    update_sql_mode('', false); // sur la session, reset
}

/**
 * Fonction simple identique à celle en PHP 5
 */
function microtime_float()
{
    list($usec, $sec) = explode(" ", microtime());
    return ((float) $usec + (float) $sec);
}

function showdate($sqldate, $mode = "T")
{
    //mode T : Texte 23 jan 2009  S : Slash 23/01/2009
    $moistxt = ["Jan", "Fév", "Mar", "Avr", "Mai", "Juin", "Juil", "Août", "Sep", "Oct", "Nov", "Déc"];
    $jour = mb_substr($sqldate, 8, 2);
    $mois = mb_substr($sqldate, 5, 2);
    $annee = mb_substr($sqldate, 0, 4);
    if ($mode == "T") {
        if ($annee == "0000") {
            $ladate = "Date inconnue";
        } else {
            $ladate = $jour . " " . $moistxt[intval($mois) - 1] . " " . $annee;
        }
    } else {
        $ladate = $jour . "/" . $mois . "/" . $annee;
    }
    //echo $sqldate ."-->".$ladate;
    return $ladate;
}

function date_rss($ladate)
{
    // Passe de date MySQL --> RSS
    $dt = explode('-', $ladate);
    $dtunix = mktime(12, 0, 0, $dt[1], $dt[2], $dt[0]);
    $texte = gmdate("D, d M Y H:i:s", $dtunix) . " GMT";
    return $texte;
}

function date_sql($ladate)
{
    // Passe de date jj/mm/aaaa --> aaaa-mm-jj
    if (isin($ladate, "/") > 0) {
        $dt = explode('/', $ladate);
        $dtunix = mktime(12, 0, 0, $dt[1], $dt[0], $dt[2]);
        $texte = gmdate("Y-m-d", $dtunix);
        return $texte;
    } else {
        return $ladate;
    }
}

function MakeRandomPassword($length = 6)
{
    $_vowels = array('a', 'e', 'i', 'o', 'u', '2', '3', '4', '5', '6', '7', '8', '9');
    $_consonants = array('b', 'c', 'd', 'f', 'g', 'h', 'k', 'm', 'n', 'p', 'r', 's', 't', 'v', 'w', 'x', 'z');
    $_syllables = array();
    $newpass = '';

    foreach ($_vowels as $v) {
        foreach ($_consonants as $c) {
            array_push($_syllables, "$c$v");
            array_push($_syllables, "$v$c");
        }
    }

    for ($i = 0; $i < ($length / 2); $i++) {
        $newpass = $newpass . $_syllables[array_rand($_syllables)];
    }

    return $newpass;
}

function crypter($mes, $password)
{
    $res = ' ';
    $j = 0;
    $tmp = 0;
    $lgmot = strlen($mes);
    for ($i = 0; $i < $lgmot; $i++) {
        $tmp = ord($mes[$i]) + ord($password[$j]);
        if ($tmp > 255) {
            $tmp = $tmp - 256;
        }
        $res[$i] = chr($tmp);
        if ($j == (strlen($password) - 1)) {
            $j = 0;
        } else {
            $j = (($j % (strlen($password))) + 1);
        }
    }
    $res = base64_encode($res);
    return $res;
}

function decrypter($mes, $password)
{
    $res = ' ';
    $j = 0;
    $tmp = 0;
    $mes = base64_decode($mes);
    $lgmot = strlen($mes);
    for ($i = 0; $i < $lgmot; $i++) {
        $tmp = ord($mes[$i]) - ord($password[$j]);
        if ($tmp < 0) {
            $tmp = 256 + $tmp;
        }
        $res[$i] = chr($tmp);
        if ($j == (strlen($password) - 1)) {
            $j = 0;
        } else {
            $j = (($j % (strlen($password))) + 1);
        }
    }
    return $res;
}

function my_flush($minsize = 0)
{
    // n'envoie que si le tampon n'est pas vide (
    $obtail = ob_get_length();
    if ($obtail and $obtail > $minsize) {
        ob_flush();
        flush();
    }
}

function my_ob_start_affichage_continu()
{ // Remplace les appels ob_start(); ob_implicit_flush(1); return;
    if (count(ob_list_handlers()) > 1) {
        ob_end_flush();
    } else {
        if (count(ob_list_handlers()) == 0) {
            ob_start();
        }
    }
    ob_implicit_flush(1);
}
