<?php

use CivilRecords\Engine\MailerFactory;
use Symfony\Component\HttpFoundation\RedirectResponse;

require(__DIR__ . '/../tools/traitements.inc.php');

if (!$userAuthorizer->isGranted(9)) {
    $response = new RedirectResponse("$root/admin/");
    $response->send();
    exit();
}

$T0 = time();
$sujet    = '';
$message  = '';
$xdroits  = 0;
$condit   = '0';

$regime   = getparam('regime');
$rem      = getparam('rem');

$emailfound = false;
$cptok = 0;
$cptko = 0;
$today = today();

$form_errors = [];
$form_success = false;
$menu_user_active = 'M';

if ($request->getMethod() === 'POST') {
    dd($request->request->all());
    $condrem = '';
    $condlevel = '';
    $condreg = '';

    $xdroits  = $request->request->get('lelevel', 0);
    $condit   = $request->request->get('condit', 0);
    $regime = $request->request->get('regime', -1);

    if ($xdroits <> 10) $condlevel = " AND level=" . $xdroits;
    if ($condit <> '0') $condrem = " AND " . comparerSQL('REM', $rem, $condit);
    if ($regime >= 0) $condreg = " AND regime =" . $regime;

    $sql = "SELECT nom, prenom, email, level, statut FROM " . $config->get('EA_UDB') . "_user3 WHERE (1=1) " . $condlevel . $condreg . $condrem . " ;";
    $users = EA_sql_query($sql, $u_db);
    $nbsites = EA_sql_num_rows($users);
    $nbsend = 0;

    while ($user = EA_sql_fetch_array($users)) {
        if ($user['statut'] == 'N') {
            $from = $config->get('SITENAME') . ' <' . $_ENV['EMAIL_SITE'] . ">";
            $to = $user['email'];
            $subject = $request->request->get('sujet');
            $mailerFactory = new MailerFactory();
            $mail = $mailerFactory->createEmail($from, $to, $subject, 'email_default.php', [
                'sitename' => $config->get('SITENAME'),
                'urlsite' => $config->get('URL_SITE'),
                'message' => $request->request->get('message')
            ]);
            $mailerFactory->send($mail);
            // TODO: will be a streaming response
            echo "<p>Envoi à " . $user['prenom'] . " " . $user['nom'] . " (" . $user['email'] . ") ";
            if (!$okmail) {
                echo ' -> Mail PAS envoyé.';
                $cptko++;
            } else {
                echo ' -> Mail ENVOYE.';
                $cptok++;
            }
        }
    }
    $form_success = true;
}

ob_start();
open_page("Envoi d'un mail circulaire", $root); ?>
<div class="main">
    <?php zone_menu(10, $session->get('user')['level'], array()); ?>
    <div class="main-col-center text-center">
        <?php
        navadmin($root, "Envoi d'un mail circulaire");

        require(__DIR__ . '/../templates/admin/_menu-user.php');

        echo '<form method="post" enctype="multipart/form-data">';
        echo '<h2 align="center">Envoi d\'un mail circulaire</h2>';
        echo '<table class="m-auto">';
        echo "<tr><td colspan=\"2\"><b>Destinataires</b></td></tr>";
        echo "<tr>";
        echo "<td>Droits d'accès : </td>";
        echo '<td>';
        lb_droits_user($xdroits, 2);
        echo '</td>';
        echo "</tr>";

        if ($config->get('GEST_POINTS') > 0) {
            echo "<tr><td>ET</td><td></td></tr>";
            echo "<tr>";
            echo "<td>Régime (points) : </td>";
            echo '<td>';
            lb_regime_user($regime, 1);
            echo '</td>';
            echo "</tr>";
        } else {
            echo '<tr><td colspan="2"><input type="hidden" name="regime" value="-1"></td></tr>';
        }

        echo "<tr>";
        echo "<td>Commentaire : </td>";
        echo '<td>';
        listbox_trait('condit', "TST", $condit);
        echo '<input type="text" name="rem" size="50" value="' . $rem . '">';
        echo "</td>";
        echo "</tr>";
        echo "<tr><td colspan=\"2\"><b>Message</b></td></tr>";
        echo "<tr>";
        echo "<td>Sujet : </td>";
        echo '<td><input type="text" name="sujet" size="60" value="' . $sujet . '" required></td>';
        echo "</tr>";
        echo '<tr>';
        echo "<td>Texte du mail : </td>";
        echo '<td><textarea name="message" cols="60" rows="10" required>' . $message . '</textarea></td>';
        echo "</tr>";
        echo "<tr><td colspan=\"2\"></td></tr>";
        echo "<tr><td></td><td>";
        echo '<button type="reset" class="btn">Effacer</button>';
        echo '<button type="submit" class="btn">Envoyer</button>';
        echo "</td></tr>";
        echo "</table>";
        echo "</form>";
        if ($form_success === true) {
            echo '<hr>';
            if ($cptok > 0) {
                echo '<p>Mails envoyés  : ' . $cptok;
                writelog('Mails envoyés ', "USERS", $cptok);
            }
            if ($cptko > 0) {
                echo '<br>Envois impossibles : ' . $cptko;
            }
            echo '<br>Durée du traitement  : ' . (time() - $T0) . ' sec.';
            echo '</p>';
            echo '<p>Retour à la ';
            echo '<a href="' . $root . '/admin/utilisateurs"><b>liste des utilisateurs</b></a>';
            echo '</p>';
        }
        echo '</div>';
        echo '</div>';
        include(__DIR__ . '/../templates/front/_footer.php');
        return (ob_get_clean());
