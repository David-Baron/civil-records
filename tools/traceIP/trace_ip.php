<?php

use CivilRecords\Engine\MailerFactory;
/*
* Trace IP v2 
*/

throw new \Exception("This function not exist anymore, deprecated functionality traceip...", 1);
exit;

function traceip()
{
    global $config, $session;
    global $bypassTIP; // bypassTIP : pour ne pas exécuter traceip sur une page il suffit de déclarer $bypassTIP=1;
    global $TIPlevel;  // $TIPlevel : niveau de la page : 1=page a compter (actes) 0 = autre (par défaut)
    // visitor general data
    $TIPlocked = 0;
    $Vcpt  = 1;
    $Vdate = date("Y/m/d H:i");
    $Vdatetime = time(); // timestamp
    $array_server_values = $_SERVER;
    $Vua   = 'Inconnu';
    if (isset($array_server_values['HTTP_USER_AGENT']))
        $Vua   = $array_server_values['HTTP_USER_AGENT'];
    $Vip   = $array_server_values['REMOTE_ADDR'];

    if (!isset($bypassTIP)) $bypassTIP = 0;
    if (!isset($TIPlevel)) $TIPlevel = 0;

    /* if (!defined("TIP_FILTRER")) define("TIP_FILTRER", "0");
    if (!defined("TIP_AUTOFREE")) define("TIP_AUTOFREE", "0");
    if (!defined("TIP_DUREE")) define("TIP_DUREE", "1"); */

    // Filtrage activé
    if (($config->get('TIP_FILTRER') == 1 or ($config->get('TIP_FILTRER') == 2 and $TIPlevel > 0)) and $bypassTIP != 1) {

        // Elimine l'IP non bloquée qui est plus âgée que TIP_DUREE minutes
        $req_tip_del_oldIP = "DELETE FROM " . $config->get('EA_DB') . "_traceip WHERE (datetime < " . ($Vdatetime - 60 * $config->get('TIP_DUREE')) . " AND locked = 0)";
        EA_sql_query($req_tip_del_oldIP) or die(EA_sql_error() . ' ' . __LINE__);
        if ($config->get('TIP_AUTOFREE') > 0) {
            // clean up IP banned older then TIP_AUTOFREE days (and with less then 2 X TIP_MAX_PAGE_COUNT)
            $req_tip_del_oldIP = "DELETE FROM " . $config->get('EA_DB') . "_traceip WHERE (datetime < " . ($Vdatetime - 60 * 60 * 24 * $config->get('TIP_AUTOFREE')) . " AND locked = 1 AND cpt <= " . (2 * $config->get('TIP_MAX_PAGE_COUNT')) . ")";
            EA_sql_query($req_tip_del_oldIP) or die(EA_sql_error() . ' ' . __LINE__);
        }

        $lock = 0;
        $whitelist = explode(",", $config->get('TIP_WHITELIST'));
        foreach ($whitelist as $whitesign) {
            if (isin($Vua, trim($whitesign)) >= 0) {
                $lock = -1;
                break;
            }
        }

        // is this visitor banned or already in DB ?
        $req_tip_ip = "SELECT ip, datetime, cpt, locked FROM " . $config->get('EA_DB') . "_traceip WHERE ip = '" . $Vip . "';";
        $tip_ip = EA_sql_query($req_tip_ip) or die(EA_sql_error() . ' ' . __LINE__);
        $tip_num_rows = EA_sql_num_rows($tip_ip);

        if ($tip_num_rows != 0) {
            $Vdata = EA_sql_fetch_array($tip_ip);
            $dateReOpen = date("d M Y H:i:s", $Vdata['datetime'] + 60 * 1440 * $config->get('TIP_AUTOFREE'));
            $dateTerme  = date("d M Y H:i:s", $Vdata['datetime'] + 60 * $config->get('TIP_DUREE'));

            if ($Vdata['locked'] == 1) {
                $TIPlocked = 1;
            };
        }; // end of if (EA_sql_num_rows($tip_ip) != 0)


        // here we assume he's not banned or not in DB list ...
        // if not in DB list : add him, otherwise add +1 to his counter.
        if ($tip_num_rows == 0) {
            if (($lock == 0) or ($lock == -1 and $config->get('TIP_MEMOWHITE') == 1)) {
                $req_tip_IP = "INSERT INTO " . $config->get('EA_DB') . "_traceip (ua, ip, datetime, cpt, locked, login) VALUES ('" . addslashes($Vua) . "','" . $Vip . "'," . $Vdatetime . "," . $Vcpt . "," . $lock . ",'" . $session->get('user')['login'] . "');";
                EA_sql_query($req_tip_IP) or die(EA_sql_error() . ' ' . __LINE__);
            }
        } else // he's in DB, neither locked nor banned
        {
            $Vcpt = $Vdata['cpt']++; // update his counter
            $req_tip_IP = "UPDATE " . $config->get('EA_DB') . "_traceip SET cpt=cpt+1, login='" . $session->get('user')['login'] . "' WHERE ip='" . $Vip . "';";
            EA_sql_query($req_tip_IP) or die(EA_sql_error() . ' ' . __LINE__);
        };
        // Avertissement du blocage imminent
        if ($Vcpt >= $config->get('TIP_MAX_PAGE_COUNT') - $config->get('TIP_ALERT') and $Vdata['locked'] == 0) {
            global $TIPmsg;
            $codes = array("#IPCLIENT#", "#COMPTE#", "#RESTE#", "#TERME#");
            $decodes = array($Vip, $Vcpt, $config->get('TIP_MAX_PAGE_COUNT') - $Vcpt, $dateTerme);
            $TIPmsg = str_replace($codes, $decodes, $config->get('TIP_MSG_ALERT'));
        }

        // if this visitor is at TIP_MAX_PAGE_COUNT, ban his IP.
        if ($Vcpt > $config->get('TIP_MAX_PAGE_COUNT') and $Vdata['locked'] == 0) {
            $TIPlocked = 1;
            $req_tip_banIP = "UPDATE " . $config->get('EA_DB') . "_traceip SET locked = 1 WHERE ip='" . $Vip . "';";
            EA_sql_query($req_tip_banIP) or die(EA_sql_error() . ' ' . __LINE__);

            $mail_message  = 'Variables serveur envoyées : <br>';
            foreach ($array_server_values as $key => $val) {
                $mail_message .= '  ' . $key . ' => ' . $val . "<br>";
            };

            $url_admin_tip = $config->get('EA_URL_SITE') . "/admin/gesttraceip.php";
            $mail_message .= "<br>Administrer les IP bannies : " . $url_admin_tip;

            $from = $config->get('SITENAME') . ' <' . $_ENV['EMAIL_SITE'] . ">";
            $to = $_ENV['EMAIL_ADMIN'];
            $subject = '[IP Interdite] ' . $Vip . ' - ' . $Vdate;
            $mailerFactory = new MailerFactory();
            $mail = $mailerFactory->createEmail($from, $to, $subject, null, [
                'message' => $mail_message
            ]);
            $mailerFactory->send($mail);
        };
        if ($TIPlocked == 1) {
            // this visitor is banned
            echo "<h2>" . $config->get('SITENAME') . " : IP " . $Vip . " bloqu&eacute;e";
            if ($config->get('TIP_AUTOFREE') > 0) echo " jusque " . $dateReOpen;
            echo " </h2>";
            echo "<h2>" . $config->get('TIP_MSG_BAN') . "</h2>";
            exit();
        };
        EA_sql_free_result($tip_ip);
    }
}

// Administration des IP Bannies 
function admin_traceip()
{
    global $config;
    echo '<h1>Gestion du filtrage d\'IP (adapté de TraceIP v2)</h1>' . "\n";

    if (!defined("TIP_FILTRER")) define("TIP_FILTRER", "0");

    if (TIP_FILTRER == 0) // Filtrage desactivé
    {
        echo "<p class=\"erreur\">" . "Le filtrage des adresses IP n'est pas activé.";
        echo '<br />Pour activer le filtrage passez par le paramétrage "FiltreIP" </p>';
    }

    $do = (isset($_GET['do'])) ? $_GET['do'] : '';
    $ipid = (isset($_GET['ipid'])) ? abs(sprintf("%d", $_GET['ipid'])) : 0;

    if (($do == 'cle') && ($ipid != 0)) {
        $req_delban = "DELETE FROM " . $config->get('EA_DB') . "_traceip WHERE locked = -1 AND cpt<='" . $ipid . "';";
        EA_sql_query($req_delban);
        echo '<h3>Nettoyage d\'IP effectuée.</h3>' . "\n";
    };
    if (($do == 'del') && ($ipid != 0)) {
        $req_delban = "DELETE FROM " . $config->get('EA_DB') . "_traceip WHERE id='" . $ipid . "' LIMIT 1;";
        EA_sql_query($req_delban);
        echo '<h3>Suppression d\'IP effectuée.</h3>' . "\n";
    };

    if (($do == 'fre') && ($ipid != 0)) {

        $req_delban = "UPDATE " . $config->get('EA_DB') . "_traceip SET locked = -1 WHERE id='" . $ipid . "' LIMIT 1;";
        EA_sql_query($req_delban);
        echo '<h3>Affranchissement permanent d\'IP effectué.</h3>' . "\n";
    };

    $req_allIP = "SELECT id,ua, ip, login, datetime, cpt,locked
										FROM " . $config->get('EA_DB') . "_traceip
										ORDER BY id ASC";

    $allIP = EA_sql_query($req_allIP) or die(EA_sql_error() . ' ' . __LINE__);
    $array_IP = array();
    while ($ip = EA_sql_fetch_array($allIP)) {
        $ip_id = $ip['id'];
        $array_IP[$ip_id]['ua'] = $ip['ua'];
        $array_IP[$ip_id]['ip'] = $ip['ip'];
        $array_IP[$ip_id]['login'] = $ip['login'];
        $array_IP[$ip_id]['datetime'] = $ip['datetime'];
        $array_IP[$ip_id]['cpt'] = $ip['cpt'];
        $array_IP[$ip_id]['locked'] = $ip['locked'];
    };

    // display lines

    echo '<h2>Liste des adresses IP récentes, affranchies ou bannies</h2>' . "\n";
    if (count($array_IP) == 0) {
        echo '<p>Aucune IP dans la base de données.</p>' . "\n";
    } else {
        $tot = array(-1 => 0, 0 => 0, 1 => 0);
        $totcpt = 0;
        echo '<p>Ci dessous est affichée la liste des IP présentes dans votre base de données. <br />Les IP en rouge sont bannies. Les IP en vert sont affranchie de façon permanente.</p>' . "\n";
        echo '<p><a href="?act=admin&amp;do=cle&amp;ipid=25" title="Nettoyer"> Nettoyer les IP-autoaffranchies</a> (cpt < 25)' . "\n";
        echo '<table width="100%" border="1" cellpadding="0" cellspacing="0" summary="" style="margin:auto;">' . "\n";
        echo '<thead>' . "\n";
        echo '	<tr style="background-color:#EFEFEF; text-align:center;">' . "\n";
        echo '		<th>ID</th>' . "\n";
        echo '		<th>User Agent</th>' . "\n";
        echo '		<th>IP</th>' . "\n";
        echo '		<th>Login</th>' . "\n";
        echo '		<th>Date</th>' . "\n";
        echo '		<th>Compte pages</th>' . "\n";
        echo '		<th>Opérations</th>' . "\n";
        echo '	</tr>' . "\n";
        echo '</thead>' . "\n";
        echo '<tbody>' . "\n";

        foreach ($array_IP as $id => $ip) {
            $tot[$ip['locked']]++;
            if ($ip['locked'] == 0) $totcpt += $ip['cpt'];
            echo '  <tr';
            echo ($ip['locked'] == 1) ? ' style="color:#CC0000;"' : '';
            echo ($ip['locked'] == -1) ? ' style="color:#339900;"' : '';
            echo '>' . "\n";
            echo '    <td style="text-align:center;">' . $id . '</td>' . "\n";
            echo '    <td>' . $ip['ua'] . '</td>' . "\n";
            echo '    <td style="text-align:center;">' . $ip['ip'] . '</td>' . "\n";
            echo '    <td style="text-align:center;">' . $ip['login'] . '</td>' . "\n";
            echo '    <td style="text-align:center;">' . date("d/m/Y H:i:s", $ip['datetime']) . '</td>' . "\n";
            echo '    <td style="text-align:center;">' . $ip['cpt'] . '</td>' . "\n";
            echo '    <td style="text-align:center;"><a href="?act=admin&amp;do=del&amp;ipid=' . $id . '" title="Supprimer">[Supprimer]</a> <a href="?act=admin&amp;do=fre&amp;ipid=' . $id . '" title="Affranchir">[Affranchir]</a></td>' . "\n";
            echo '	</tr>' . "\n";
        };
        $dateInstant  = date("d M Y H:i:s", time());

        echo $dateInstant . ' : Accès affranchis : ' . $tot[-1];
        echo ' - Accès normaux : ' . $tot[0] . ' (Total de vus : ' . $totcpt . ')';
        echo ' - Accès bloqués : ' . $tot[1];

        echo '</tbody>' . "\n";
        echo '</table>' . "\n";
    }; // end of if (count($array_IP) == 0)
};
