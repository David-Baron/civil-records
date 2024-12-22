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

function optimize($sql)  // pour détection des optimisations à faire
{
    if (defined("OPTIMIZE") or getparam('OPTIMIZE') == "YES") {
        if (isin(strtoupper($sql), 'SELECT') >= 0) {
            $optim = EA_sql_query("EXPLAIN " . $sql);
            echo '<p>' . preg_replace('/union/', '<br /><b>UNION</b><br />', $sql) . '</p>';
            if (strtoupper(mb_substr($sql, 0, 1)) == 'S') {
                $nbres = EA_sql_num_rows($optim);
                if ($nbres > 0) {
                    print '<pre> OPTIMISATION : <p> ';
                    while ($line = EA_sql_fetch_assoc($optim)) {
                        print_r($line);
                    }
                    echo '</pre>';
                }
            }
        } else {
            print '<p>REQUETE MAJ : ' . $sql . '<p> ';
        }
    }
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

function sans_quote($texte)  // retourne vrai si aucune quote dans le texte
{
    if (strpos($texte, "'") === false) {
        return true;
    } else {
        return false;
    }
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
    if ($mode == "2") {
        $separs = ";";
        $Saut_Ou_Espace = '<br/>';
    } else {
        $separs = " , "; // (séparés par des , ou des blancs)
        $Saut_Ou_Espace = ' ';
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

function val_status_mysql($label)
{
    if ($result = EA_sql_query("SHOW STATUS LIKE '" . $label . "'")) {
        $row = EA_sql_fetch_assoc($result);
        return $row['Value'];
    } 
    
    return "??";
}

function explode_csv($line)
// décompose selon ; ou tab en respectant les guillements éventuels
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

function close_db($dblink) // ferme la connexion à la DB
{
    EA_sql_close($dblink);
}

function msg($desc, $type = "erreur")
{
    if ($desc <> null) {
        echo "<p class=\"$type\">";
        if ($type == "erreur") {
            echo "Erreur : ";
        }
        echo "$desc</p>\n";
        global $root;
        if (empty($root)) {
            $root = ".";
        }
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

    $time = now();
    if(!$session->has('user')) {
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

function now()
{
    return date("Y-m-d H:i:s", time());
}

function today()
{
    return date("Y-m-d", time());
}

function showdate($sqldate, $mode = "T")
{
    //mode T : Texte 23 jan 2009  S : Slash 23/01/2009
    $moistxt = array("Jan", "Fév", "Mar", "Avr", "Mai", "Juin", "Juil", "Août", "Sep", "Oct", "Nov", "Déc");
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

function microdelay($delay) //Just for the fun ! ;-)
{
    @fsockopen("tcp://localhost", 31238, $errno, $errstr, $delay);
}

function MakeRandomPassword($length = 6)
{
    $_vowels = array('a', 'e', 'i', 'o', 'u', '2', '3', '4', '5', '6', '7', '8', '9');
    $_consonants = array('b', 'c', 'd', 'f', 'g', 'h', 'k', 'm', 'n', 'p', 'r', 's', 't', 'v', 'w', 'x', 'z');
    $_syllables = array();
    $newpass = "";
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

function valid_mail_adrs($email)
{
    if (preg_match('`^\w([-_.]?\w)*@\w([-_.]?\w)*\.([a-z]{2,4})$`', $email)) {
        return true;
    } else {
        return false;
    }
}

function mail_encode($texte)
{
    // code les textes pour l'adresse mail ou le sujet de façon à passer même en 7bits
    return "=?" . MAIL_CHARSET . "?B?" . base64_encode($texte) . "?=";
}

function sendmail($from, $to, $sujet, $message)
{
    global $config;
    /*
        echo '<p>Expéditeur : ['.htmlspecialchars($from, ENTITY_REPLACE_FLAGS, ENTITY_CHARSET).']';
        echo '<br />Destinataire : ['.htmlspecialchars($to, ENTITY_REPLACE_FLAGS, ENTITY_CHARSET).']';
        echo '<br />Sujet : ['.htmlspecialchars($sujet, ENTITY_REPLACE_FLAGS, ENTITY_CHARSET).']'.base64_encode($sujet);
        echo '<br />Message : ['.htmlspecialchars($message, ENTITY_REPLACE_FLAGS, ENTITY_CHARSET).']'.'</p>';
        */
    if ($config->get('EXTERN_MAIL') == 0) {
        // appel de la fonction interne ... pour autant qu'elle soit bien configurée
        $headers  = 'MIME-Version: 1.0' . "\n";
        $headers .= "Content-Type: text/plain; charset=" . MAIL_CHARSET . "; format=flowed\n";
        $headers .= "Content-Transfer-Encoding: 8bit\n";
        $headers .= "X-Mailer: PHP" . phpversion() . "\n";
        $headers .= 'From: ' . $from . "\n";

        $ok =  @mail($to, mail_encode($sujet), $message, $headers);
        if (!$ok) {
            msg("051 : L'envoi du mail via la procédure interne à PHP n'a pas réussi.");
            global $userlogin;
            if ($userlogin <> "") {
                echo '<p>Expéditeur : ' . htmlspecialchars($from, ENTITY_REPLACE_FLAGS, ENTITY_CHARSET);
                echo '<br />Destinataire : ' . htmlspecialchars($to, ENTITY_REPLACE_FLAGS, ENTITY_CHARSET) . '</p>';
                //mail($to, mail_encode($sujet), $message, $headers); // nouvel essai.
            }
        }
        return $ok;
    } else {
        if (file_exists(dirname(__FILE__) . '/PHPMailer/') and file_exists(dirname(__FILE__) . '/mail_externe.inc.php')) {
            // Gestion Envoi mel par script externe local et PHPMailer
            $retour_mail_externe = false;
            // Le script doit positionner la variable $retour_mail_externe à "false" ou "true", à défaut ce script considérera que l'envoi de mél a échoué même s'il a réussi.
            require_once(dirname(__FILE__) . '/mail_externe.inc.php');
            return $retour_mail_externe;
        }
        // envoi du mail vers un autre serveur smtp ... si c'est nécessaire
        $lb = "\r\n";                //linebreak

        if ($config->get('SMTP_HOST') == "" or $config->get('LOC_HOST') == "" or $config->get('LOC_MAIL') == "") {
            msg("052 : Paramètres de gestion du mail incomlètement configurés.");
            return false;
        }
        // if ($smtp_port=="")
        $smtp_port = 25;  // valeur par défaut

        $contenu  = "from:" . $from . $lb;
        $contenu .= "to:" . $to . $lb;
        $contenu .= "subject:" . $sujet . $lb;
        $contenu .= $message;

        $content   = explode($lb, $contenu);

        // if ($body) {$bdy = preg_replace("/^\./","..",explode($body_lb,$body));}

        // build the array for the SMTP dialog. Line content is array(command, success code, additonal error message)

        if ($config->get('SMTP_PASS') <> "") {
            // SMTP authentication methode AUTH LOGIN, use extended HELO "EHLO"
            $smtp = array(
                // call the server and tell the name of your local host
                array("EHLO " . $config->get('LOC_HOST') . $lb, "220,250", "HELO error: "),
                // request to auth
                array("AUTH LOGIN" . $lb, "334", "AUTH error:"),
                // username
                array(base64_encode($config->get('SMTP_ACC')) . $lb, "334", "AUTHENTIFICATION error : "),
                // password
                array(base64_encode($config->get('SMTP_PASS')) . $lb, "235", "AUTHENTIFICATION error : "),
            );
        } else {
            $smtp = array(array("HELO " . $config->get('LOC_HOST') . $lb, "220,250", "HELO error: "));
        }

        // call the server and tell the name of your local host

        // envelop
        $smtp[] = array("MAIL FROM: <" . $from . ">" . $lb, "250", "MAIL FROM error: ");

        $tos    = explode(",", $to); //header
        for ($i = 0; $i < count($tos); $i++) {
            $smtp[] = array("RCPT TO: <" . $tos[$i] . ">" . $lb, "250", "RCPT TO error: ");
        }
        // begin data
        $smtp[] = array("DATA" . $lb, "354", "DATA error: ");
        foreach ($content as $cont) {
            $smtp[] = array($cont . $lb, "", "");
        }
        $smtp[] = array("." . $lb, "250", "DATA(end)error: ");
        $smtp[] = array("QUIT" . $lb, "221", "QUIT error: ");

        // open socket
        $fp = @fsockopen($config->get('SMTP_HOST'), $smtp_port, $errno, $errstr, 15);
        if (!$fp) {
            writelog("Cannot connect to host", $config->get('SMTP_HOST'), 0);
            msg('053 : Impossible de se connecter au serveur mail "' . $config->get('SMTP_HOST') . '".');
            return false;
        }

        $banner = fgets($fp, 1024);
        // perform the SMTP dialog with all lines of the list
        foreach ($smtp as $req) {
            $r = $req[0];
            // send request
            @fputs($fp, $req[0]);
            // get available server messages and stop on errors
            if ($req[1]) {
                while ($result = fgets($fp, 1024)) {
                    if (mb_substr($result, 3, 1) == " ") {
                        break;
                    }
                }
                if (!strstr($req[1], mb_substr($result, 0, 3))) {
                    writelog($req[2] . $result, $config->get('SMTP_HOST'), 0);
                    msg('054 : Problème lors du dialogue avec le serveur mail "' . $config->get('SMTP_HOST') . '" : ' . $req[2] . $result);
                    return false;
                }
            }
        }
        $result = fgets($fp, 1024);

        fclose($fp);
        return true;
    }
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


// Détection du codage UTF-8 d'une chaîne.
function is_utf8($str)
{
    $c = 0;
    $b = 0;
    $bits = 0;
    $len = strlen($str);
    for ($i = 0; $i < $len; $i++) {
        $c = ord($str[$i]);
        if ($c > 128) {
            if (($c >= 254)) {
                return false;
            } elseif ($c >= 252) {
                $bits = 6;
            } elseif ($c >= 248) {
                $bits = 5;
            } elseif ($c >= 240) {
                $bits = 4;
            } elseif ($c >= 224) {
                $bits = 3;
            } elseif ($c >= 192) {
                $bits = 2;
            } else {
                return false;
            }
            if (($i + $bits) > $len) {
                return false;
            }
            while ($bits > 1) {
                $i++;
                $b = ord($str[$i]);
                if ($b < 128 || $b > 191) {
                    return false;
                }
                $bits--;
            }
        }
    }
    return true;
}
