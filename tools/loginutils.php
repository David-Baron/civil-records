<?php

require(__DIR__ . '/traceIP/trace_ip.php');

if (!defined("TIP_LEVEL_NO_IP_TEST")) define("TIP_LEVEL_NO_IP_TEST", 9);

/* function login($path = '', $cas = 3)  // Uniquement utilisé par authentification
{
    global $root;
    header("Location: " . $root . "/login.php?cas=" . $cas . "&uri=" . urlencode($_SERVER['REQUEST_URI']));
    die();
}
 */
/* function current_user($zone)
{
    global $userlogin, $u_db;
    if ($userlogin == "") {
        return 0;
    }  // non connecté
    else {
        $sql = "SELECT * FROM " . EA_UDB . "_user3 WHERE login = '" . $userlogin . "'";
        $res = EA_sql_query($sql, $u_db);
        if ($res and EA_sql_num_rows($res) != 0) {
            $row = EA_sql_fetch_array($res);
            if ($zone == "MD5") {
                return $row["hashpass"];
            }
            //return md5($row["login"].$row["passw"]);
            else {
                return $row[$zone];
            }
        } 

        return 0;
    }
} */

/* function logonok($level = 0)
{
    global $root,$userlogin,$statut,$expirok,$u_db;

    if (!defined("EA_MAINTENANCE")) {
        define("EA_MAINTENANCE", 0);
    }
    // Autentification PHP
    if (isset($_REQUEST['login'])) {  // si on présente un login, on le teste de suite
        $userid = 0;
        $statut = "";
        $expirok = true;
        $session->get('user')['level'] = CheckUser(getparam('login'), getparam('passwd'), getparam('codedpass'), getparam('iscoded'), $userid);
        $t = array("W" => 5, "A" => 5, "B" => 7);
        if($session->get('user')['level'] == 0) {
            if (!$expirok) {
                $cas = 6;
            } elseif (array_key_exists($statut, $t)) {
                $cas = $t[$statut];
            } else {
                $cas = 1;
            }
            login($root, $cas);
        } else {
            if (getparam('iscoded') == 'Y') {
                $md5 = getparam('codedpass');
            } else {
                $md5 = sha1(getparam('passwd'));
            }
            if (getparam('saved') == 'yes') {
                $duree = time() + (60 * 60 * 24) * 5;
            } else {
                $duree = 0;
            }
            setcookie('md5', $md5, $duree, $root);
            setcookie('userid', $userid, $duree, $root);
            $niveau = $userlevel;
            $userlogin = getparam('login');
            if ($userlevel >= LEVEL_MAIL_LOGIN) {
                $lb        = "\r\n";
                $Vdate = date("d/m/Y à H:i");
                $array_server_values = $_SERVER;
                $Vua   = $array_server_values['HTTP_USER_AGENT'];
                $Vip   = $array_server_values['REMOTE_ADDR'];
                $message  = "Information sécurité : Vous vous êtes connecté au site suivant " . $lb;
                $message .= "" . $lb;
                $message .= EA_URL_SITE . $root . "/index.php" . $lb;
                $message .= "" . $lb;
                $message .= "avec le login [" . getparam('login') . "] ce " . $Vdate . $lb;
                $message .= "" . $lb;
                $message .= "Votre adresse IP est :" . $Vip . $lb;
                $message .= "Votre client est : " . $Vua . $lb;
                $sujet = "Confirmation de login sur " . SITENAME;
                $sender = mail_encode(SITENAME) . ' <' . LOC_MAIL . ">";
                $okmail = sendmail($sender, current_user('email'), $sujet, $message);
            }
        }
    } elseif(isset($_COOKIE['userid']) and isset($_COOKIE['md5'])) {
        $niveau = CheckMD5($_COOKIE['userid'], $_COOKIE['md5']);  // mets à jour $userlogin
        if ($niveau < $level) {
            $niveau = 0;
        }
    } elseif ($level <= PUBLIC_LEVEL) {
        $niveau = PUBLIC_LEVEL;  // ne pas (re)positionner userlogin
    } else {
        //echo "ERROR LOGIN. redirigé sur ".$root."/login.php?cas=2&uri=".urlencode($_SERVER['REQUEST_URI']);
        login($root, '2');
    }
    if (!(EA_MAINTENANCE == 0 or $niveau == 9)) {
        $niveau = 0;
    }
    recharger_solde();
    // test IP
    if ($niveau < TIP_LEVEL_NO_IP_TEST && $niveau != 5) {
        traceip();
    }
    return $niveau;
} */

/* function CheckUser($login, $pw, $codedpw, $coded, &$userid)
// Vérification réelle du droit d'accès
{
    global $statut, $level, $expirok, $u_db;

    if (strlen($login) > 15 or strlen($pw) > 15) {
        writelog('Login ERROR : ' . $login, $pw, $nbresult);
        // probablement attaque avec injection de code
        return 0;
    } else {
        $res = EA_sql_query("SELECT * FROM " . EA_UDB . "_user3 WHERE login='" . $login . "'", $u_db);
        $nbresult = EA_sql_num_rows($res);
        if ($nbresult > 1) {
            writelog('Login ERROR : ', $login, $nbresult);
            // probablement attaque avec injection de code ou 2 logins identiques dans la base
            return 0;
        } else {
            if ($nbresult == 1) {
                $row = EA_sql_fetch_array($res);
                $statut = $row["statut"];
                if ($coded == 'N') {
                    $pwok = ($row["hashpass"] === sha1($pw));
                } else {
                    $pwok = ($row["hashpass"] === $codedpw);
                }
                if ($statut == 'N') {
                    $expirok = ($row["dtexpiration"] >= date("Y-m-d", time()) or $row["level"] == 9);
                }
                // recontrôle des user et pw pour assurer respect de la casse et anti injection
                if ($row["login"] == $login and $pwok and $expirok and $row["level"] >= $level and ($row["level"] == 9 or $row["statut"] == 'N')) {
                    $GLOBALS['EA_NP'] = $row["nom"] . ' ' . $row["prenom"]; // MENU $EA_NP
                    $userid = $row["ID"];
                    return max($row["level"], PUBLIC_LEVEL);
                } else {
                    return 0;
                }
            } else {
                return 0;
            }
        }
    }
} */

/* function CheckMD5($userid, $md5)
{
    global $userlogin, $level, $u_db;
    $res = EA_sql_query("SELECT * FROM " . EA_UDB . "_user3 WHERE ID='" . $userid . "'", $u_db);
    if (EA_sql_num_rows($res) == 1) {
        $row = EA_sql_fetch_array($res);
        if ($row["hashpass"] != $md5) {
            return 0;
        } else {
            if ($row["level"] >= $level) {
                $GLOBALS['EA_NP'] = $row["nom"] . ' ' . $row["prenom"];// MENU $EA_NP
                $userlogin = $row["login"];
                return max($row["level"], PUBLIC_LEVEL);
            } else {
                return 0;
            }
        }
    } else {
        return 0;
    }
} */
