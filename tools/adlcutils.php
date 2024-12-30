<?php

/**
 * Recupère les arguments passés en mode chemin ou args suivant config
 * @deprecated
 */
function pathroot(&$root, &$path, &$arg1, &$arg2, &$arg3)
{
    $defarg1 = $arg1;
    $defarg2 = $arg2;
    global $scriptname; // pour pouvoir le récupérer
    // $chemin = preg_split("/\/|\?/i", $_SERVER["REQUEST_URI"], -1, PREG_SPLIT_NO_EMPTY);
    $_SERVER["REQUEST_URI"] = str_replace('/?', '/index.php?', $_SERVER["REQUEST_URI"]);
    $chemin = preg_split("/\//i", $_SERVER["REQUEST_URI"], -1, PREG_SPLIT_NO_EMPTY);
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

/**
 * permet de passer des nom avec slash dans l'url (Alle s/Semois)
 * @deprecated
 */
function encodemyslash($text)
{
    $newslash = chr(190);  // 3/4
    return str_replace('/', $newslash, $text);
}

/**
 * @deprecated
 */
function decodemyslash($text)
{
    $newslash = chr(190);  // 3/4
    return str_replace($newslash, '/', $text);
}


/**
 * @deprecated
 */
function nogetargs($chaine)
{
    $x = strpos($chaine, "?");
    if ($x > 0) {
        return mb_substr($chaine, 0, $x);
    }

    return $chaine;
}

