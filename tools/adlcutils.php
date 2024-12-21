<?php

function pathroot(&$root, &$path, &$arg1, &$arg2, &$arg3)
{
    // Recupère les arguments passés en mode chemin ou args suivant config
    $defarg1 = $arg1;
    $defarg2 = $arg2;
    global $scriptname; // pour pouvoir le récupérer
    //$chemin = preg_split("/\/|\?/i", $_SERVER["REQUEST_URI"], -1, PREG_SPLIT_NO_EMPTY);
    //print '<p>' . $_SERVER["REQUEST_URI"] . '</p>';
    $_SERVER["REQUEST_URI"] = str_replace('/?', '/index.php?', $_SERVER["REQUEST_URI"]);
    $chemin = preg_split("/\//i", $_SERVER["REQUEST_URI"], -1, PREG_SPLIT_NO_EMPTY);
    // print '<pre>';  print_r($chemin); echo '</pre>';
    $i = 0;
    while (isset($chemin[$i]) and strpos($chemin[$i], ".php") === false and $i < count($chemin)) {
        if ($chemin[$i] != "admin" and $chemin[$i] != "install" and $chemin[$i] != "perso") {
            $root = $root . "/" . $chemin[$i];
        }
        $path = $path . "/" . $chemin[$i];
        $i++;
    }
    $arg1 = "";
    $arg2 = "";
    $arg3 = "";
    $pos = 0;
    if (isset($chemin[$i])) {
        $pos = strpos($chemin[$i], "?args=");
        $scriptname = mb_substr($chemin[$i], 0, strpos($chemin[$i], ".php"));  // nom du script sans le .php
    }
    if ($pos == 0) {
        $i++;
        if (count($chemin) > $i) {
            $arg1 = decodemyslash(urldecode(nogetargs($chemin[$i])));
        }
        $i++;
        if (count($chemin) > $i) {
            $arg2 = decodemyslash(urldecode(nogetargs($chemin[$i])));
        }
        $i++;
        if (count($chemin) > $i) {
            $arg3 = urldecode(nogetargs($chemin[$i]));
        }
    } else {
        $args = mb_substr($chemin[$i], $pos + 6);
        $pos = strpos($args, "&");
        if ($pos > 0) {
            $args = mb_substr($args, 0, $pos);
        }
        $argn = preg_split("/,/i", $args, -1, PREG_SPLIT_NO_EMPTY);
        $j = 0;
        if (count($argn) > $j) {
            $arg1 = urldecode(nogetargs($argn[$j]));
        }
        $j++;
        if (count($argn) > $j) {
            $arg2 = urldecode(nogetargs($argn[$j]));
        }
        $j++;
        if (count($argn) > $j) {
            $arg3 = urldecode(nogetargs($argn[$j]));
        }
    }
    // recup des valeurs par défaut
    if ($arg1 == "") {
        $arg1 = $defarg1;
    }
    if ($arg2 == "") {
        $arg2 = $defarg2;
    }
}

function encodemyslash($text)
{ // permet de passer des nom avec slash dans l'url (Alle s/Semois)
    $newslash = chr(190);  // 3/4
    return str_replace('/', $newslash, $text);
}

function decodemyslash($text)
{
    $newslash = chr(190);  // 3/4
    return str_replace($newslash, '/', $text);
}

function mkurl($script, $arg1, $arg2 = "", $args = "")
{ // Compose une URL avec les arguments passés en mode chemin ou non suivant config.
    global $config;
    $url = $script; // par défaut
    if ($config->get('FULL_URL') == 1) {
        if ($arg1 <> "") {
            $url = $script . '/' . urlencode(encodemyslash($arg1));
        }
        if ($arg2 <> "") {
            $url .= '/' . urlencode(encodemyslash($arg2));
        }
        if ($args <> "") {
            $url .= "?" . $args;
        }
    } else {
        if ($arg1 <> "") {
            $url = $script . '?args=' . urlencode($arg1);
        }
        if ($arg2 <> "") {
            $url .= ',' . urlencode($arg2);
        }
        if ($args <> "") {
            $url .= "&amp;" . $args;
        }
    }
    return $url;
}

function mkSiteUrl() // Compose le nom du serveur http:// ou https:// etc....  On récupère l'URL (sans le / de fin)
{
    // Utilisé dans :
    // activer_compte.php, cree_compte.php, localite.php, renvoilogin.php, signal_erreur.php, rss.php
    // admin/approuver_compte.php, admin/envoimail.php, admin/gestgeoloc.php, admin/gestuser.php, admin/loaduser.php
    // tools/carto_index.php, ?? tools/loginutils.php, ?? tools/traceIP/trace_ip.php
    // equivalent à   "http://".$_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"];
    //          ET   "http://".$_SERVER['HTTP_HOST']
    // règle le pb http ou https et SERVER_PORT particulier ou par défaut
    $is_SSL = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on');
    $HttpOuHttps = strtolower($_SERVER['SERVER_PROTOCOL']);
    $HttpOuHttps = substr($HttpOuHttps, 0, strpos($HttpOuHttps, '/')) . (($is_SSL) ? 's' : '');
    $ServerPort = ((!$is_SSL && $_SERVER['SERVER_PORT'] == '80') || ($is_SSL && $_SERVER['SERVER_PORT'] == '443'))
        ? '' : ':' . $_SERVER['SERVER_PORT']; // Ne met le port que si c'est autre chose que 80(http) ou 443(https)
    $Hote = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME'] . $ServerPort;
    $url_du_site = $HttpOuHttps . '://' . $Hote;
    // On peut forcer ici $url_du_site = "https://monsite.xxx" en cas de problème
    return $url_du_site;
}

function nogetargs($chaine)
{
    $x = strpos($chaine, "?");
    if ($x > 0) {
        return mb_substr($chaine, 0, $x);
    }

    return $chaine;
}

function selected_option($valeur, $defaut)  // pour listbox
{
    $valeur = strval($valeur);
    $defaut = strval($defaut);
    if ($valeur == $defaut) {
        return 'value="' . $valeur . '" selected="selected"';
    }

    return 'value="' . $valeur . '"';
}


function strmin($str1, $str2)
{ // Retourne la chaine la plus en avant par ordre alphabétique
    if ($str1 > $str2) {
        return $str2;
    }

    return $str1;
}

function strmax($str1, $str2)
{ // Retourne la chaine la plus en arriere par ordre alphabétique
    if ($str1 < $str2) {
        return $str2;
    }

    return $str1;
}

function execute_script_sql($filename, $prefixe = "", $selecttxt = "")
{
    global $config;
    if ($prefixe == "") {
        $prefixe = $config->get('EA_DB');
    }

    if (!file_exists($filename)) {
        msg('041 : Impossible de trouver le script SQL "' . $filename . '".');
        $ok = false;
        die();
    }

    $listreq = explode(';', file_get_contents($filename));
    $ok = true;
    $i  = 0;

    while ($ok and $i < count($listreq)) {
        $reqmaj = $listreq[$i];
        if ($selecttxt == "" or isin($reqmaj, $selecttxt) >= 0) { // si instruction selectionnée ou toutes
            $reqmaj = str_replace("EA_DB_", $prefixe . "_", $reqmaj);

            if (strlen(trim($reqmaj)) > 0) {
                if ($result = EA_sql_query($reqmaj . ';')) {
                    echo '<p>Action ' . ($i + 1) . ' ok</p>';
                } else {
                    echo ' -> Erreur : ';
                    echo '<p>' . EA_sql_error() . '<br>' . $reqmaj . '</p>';
                    $ok = false;
                }
            }
        }
        $i++;
    }
    return $ok;
}
