<?php

// tableau des connexions aux bases de données. Sauf quand la base utilisateur est sur une autre base, il n'y en a toujours qu'une
$BD_EA_link = array();

// == fonctions communes appelées dans les 2 cas mysql et mysqli
// Recherche la liaison avec le serveur. Mysqli en a toujours besoin alors que Mysql utilise toujours le dernier
function EA_sql_which_link($link_identifier = null)
{
    // une liaison est indiqué, on la retourne
    if ($link_identifier !== null) {
        return $link_identifier;
    } else {
        // Aucune liason de BD connue dans la table, on retourne NULL pour indiquer qu'il n'y a pas de liaison active
        if (!count($GLOBALS['BD_EA_link'])) {
            return null;
        }
        // récupère la dernière liaison connue
        $last = end($GLOBALS['BD_EA_link']);
        // rentourner les infos correspondants à cette liaison
        return $last['link'];
    }
}

function EA_is_mysqli_or_resource($r)
{
    # get the type of the variable
    switch (gettype($r)) {
            # if it is a resource - could be mysql, file handle etc...
        case 'resource':
            return true;
            # if it is an object - must be a mysqli object then
        case 'object':
            # is this an instance of mysqli?
            if ($r instanceof mysqli) {
                # make sure there is no connection error
                return !($r->connect_error);
            }
            # or is this an instance of a mysqli result?
            if ($r instanceof mysqli_result) {
                return true;
            }
            return false;
            # negative on all other variable types
        default:
            return false;
    }
}

// Ajout d'une liaison dans la table
function BD_EA_link_add($ladbaddr, $ladbuser, $ladbpass, $new_link = false, $client_flags = 0, $is_mysql = false)
{
    // pas de nouveau lien, on vérifie si la connexion au serveur BD n'est pas déjà référencée
    if (!$new_link) {
        // il y a déjà des connexion dans le tableau
        if (count($GLOBALS['BD_EA_link'])) {
            $last = end($GLOBALS['BD_EA_link']); // dernière connexion faite
            // si elle correspond à ladbaddr/ladbuser/ladbpass indiqué
            if (($ladbaddr . '|' . $ladbuser . '|' . $ladbpass === $last['BD_sup']) &&
                (EA_is_mysqli_or_resource($last['link']))
            ) {
                // on la prend donc
                return EA_sql_which_link(null);
            }
        }
    }
    // Ici nouvelle connexion au serveur, la tenter avec les données
    $link = @mysqli_connect($ladbaddr, $ladbuser, $ladbpass, '');
    if (@mysqli_connect_errno()) {
        return false;
    }
    // insère les infos de la connexion serveur dans la table et retourne la liaison
    $GLOBALS['BD_EA_link'][] = array(
        'thread_id' => $link->thread_id,
        'BD_sup' => $ladbaddr . '|' . $ladbuser . '|' . $ladbpass,
        'link' => $link,
    );
    return $link;
}
// Retrait d'une liaison de la table
function BD_EA_link_remove($LINK, $is_mysql = false)
{
    $LINK = EA_sql_which_link($LINK);
    if (isset($LINK->thread_id) && is_numeric($LINK->thread_id)) {
        $thread_id =  $LINK->thread_id;
    } else {
        $thread_id =  false;
    }
    $result = mysqli_close($LINK);
    // la fermeture est OK et il y avait un ID de liaison BD
    if ($result && $thread_id) {
        // parcourir le tableau des liens pour supprimer celui traité
        foreach ($GLOBALS['BD_EA_link'] as $k => $v) {
            if ($v['thread_id'] === $thread_id) {
                array_splice($GLOBALS['BD_EA_link'], $k, 1);
                break;
            }
        }
    } else {
        // Ce cas ne devrait pas arriver
        // la fermeture d'une liaison existante dans le tableau est en échec
        if ($result === null) {
            return false;
        }
    }
    echo 'ON FERME';
    exit;
    foreach ($GLOBALS['BD_EA_link'] as $k => $v) {
        print_r($v);
    }

    return $result;
}
// == Fin des fonctions communes appelées dans les 2 cas mysql et mysqli
mysqli_report(MYSQLI_REPORT_OFF);
function EA_sql_query($QUERY, $LINKS = null)
{
    $LINKS = EA_sql_which_link($LINKS);
    try {
        $link = @mysqli_query($LINKS, $QUERY);
    } catch (Exception $e) {
        $link = false;
    }
    return  $link;
}
function EA_sql_fetch_array($RESULT)
{
    $row = mysqli_fetch_array($RESULT, MYSQLI_BOTH);
    if (!is_array($row)) {
        return $row;
    }
    foreach ($row as $k => $v) {
        if (is_null($v)) {
            $row[$k] = '';
        }
    }
    return $row;
}
function EA_sql_num_rows($RESULT)
{
    return mysqli_num_rows($RESULT);
}
function EA_sql_fetch_assoc($RESULT)
{
    $row = mysqli_fetch_assoc($RESULT);
    if (!is_array($row)) {
        return $row;
    }
    foreach ($row as $k => $v) {
        if (is_null($v)) {
            $row[$k] = '';
        }
    }
    return $row;
}
function EA_sql_fetch_row($RESULT)
{
    $row = mysqli_fetch_row($RESULT);
    if (!is_array($row)) {
        return $row;
    }
    foreach ($row as $k => $v) {
        if (is_null($v)) {
            $row[$k] = '';
        }
    }
    return $row;
}
function EA_sql_get_server_info($dblink = null)
{
    $dblink = EA_sql_which_link($dblink);
    return mysqli_get_server_info($dblink);
}
function EA_sql_affected_rows($dblink = null)
{
    $dblink = EA_sql_which_link($dblink);
    return mysqli_affected_rows($dblink);
}
function EA_sql_stat($dblink = null)
{
    $dblink = EA_sql_which_link($dblink);
    return mysqli_stat($dblink);
}
function EA_sql_error($dblink = null)
{
    $dblink = EA_sql_which_link($dblink);
    if ($dblink !== null) {
        return mysqli_error($dblink);
    }
}
function EA_sql_num_fields($RESULT)
{
    return  mysqli_num_fields($RESULT);
}
function EA_sql_free_result($RESULT)
{
    return mysqli_free_result($RESULT);
}
function EA_sql_real_escape_string($param)
{
    $dblink = EA_sql_which_link(null);
    return mysqli_real_escape_string($dblink, $param);
}
function EA_sql_connect($ladbaddr, $ladbuser, $ladbpass, $new_link = false, $client_flags = 0)
{
    return BD_EA_link_add($ladbaddr, $ladbuser, $ladbpass, $new_link, $client_flags, false);
}
function EA_sql_select_db($ladbname, $dblink)
{
    $dblink = EA_sql_which_link($dblink);
    return mysqli_select_db($dblink, $ladbname);
}

