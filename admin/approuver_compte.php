<?php

// TODO: form switch textarea content when action change

use Symfony\Component\HttpFoundation\RedirectResponse;

require(__DIR__ . '/../next/bootstrap.php');
require(__DIR__ . '/../next/_COMMUN_env.inc.php'); // Compatibility only

if (!$userAuthorizer->isGranted(9) || $config->get('USER_AUTO_DEF') == 1) {
    $response = new RedirectResponse("$root/admin/");
    $response->send();
    exit();
}

if ($request->get('id', null) === null) {
    $response = new RedirectResponse("$root/admin/");
    $response->send();
    exit();
}

$user_id = $request->get('id', null);
$sql = "SELECT * FROM " . $config->get('EA_UDB') . "_user3 WHERE ID=" . $user_id . ";";
$res = EA_sql_query($sql, $u_db);
if (EA_sql_num_rows($res) != 1) {
    $session->getFlashBag()->add('danger', 'Pas de compte à approuver avec cette identification.');
    $response = new RedirectResponse("$root/admin/");
    $response->send();
    exit();
}

$user = EA_sql_fetch_array($res);
$nomprenom = $user['prenom'] . ' ' . $user['nom'];
$login = $user['login'];

$action = 'OK';
$message_model = $config->get('MAIL_APPROBATION');

$form_errors = [];

if ($request->getMethod() === 'POST') {

    $action = $request->request->get('action', null);
    if (null === $action) {
        $form_errors['action'] = 'Vous devez sélectionner l\'action à poser';
    }

    if (empty($form_errors)) {
        $mes = "";
        if ($action == "OK") {
            $statut = 'N';  // normal
            $sujet = "Approbation de votre compte";
            $mes = "approuvé";
        } else {
            $statut = 'B';  // bloqué
            $sujet = "Refus de votre compte";
            $mes = "refusé";
        }

        $sql = "UPDATE " . $config->get('EA_UDB') . "_user3 SET statut=" . $statut . ", REM='' WHERE ID=" . $user_id . ";";
        $crlf = chr(10) . chr(13);
        $log = "Cpte " . $mes;

        $urlsite = $config->get('EA_URL_SITE') . $root . "/";
        $codes = array("#NOMSITE#", "#URLSITE#", "#LOGIN#", "#NOM#", "#PRENOM#");
        $decodes = array($config->get('SITENAME'), $urlsite, $user['LOGIN'], $user['NOM'], $user['PRENOM']);
        $message = str_replace($codes, $decodes, $request->request->get('messageplus'));

        $sender = mail_encode($config->get('SITENAME')) . ' <' . $config->get('LOC_MAIL') . ">";
        $okmail = sendmail($sender, $user['email'], $sujet, $message);
        if ($okmail) {
            $log .= " + mail";
        } else {
            $log .= " NO mail";
        }
        writelog($log, $login, 0);
        $session->getFlashBag()->add('success', 'La demande de compte a été ' . $mes . 'e.');
        $response = new RedirectResponse("$root/admin/gestuser.php?id=$user_id");
        $response->send();
        exit();
    }
}


ob_start();
open_page("Approbation d'un compte utilisateur", $root); ?>
<div class="main">
    <?php zone_menu(10, $session->get('user')['level']); ?>
    <div class="main-col-center text-center">
        <?php
        navadmin($root, "Approbation d'un compte utilisateur");

        $message_model = $config->get('MAIL_APPROBATION');
        if ($action == 'KO') {
            $message_model = $config->get('MAIL_REFUS');
        }

        echo '<h2>Approbation d\'un compte d\'utilisateur</h2>';
        echo '<form method="post">';
        echo '<table class="m-auto" summary="Formulaire">';

        echo "<tr>";
        echo '<td>' . "Candidat : </td>";
        echo '<td><b>' . $nomprenom . "</b></td>";
        echo "</tr>";

        echo "<tr>";
        echo '<td>' . "Login : </td>";
        echo '<td>' . $login . "</td>";
        echo "</tr>";

        echo "<tr>";
        echo '<td>Action : </td>';
        echo '<td>';
        echo '<input type="radio" name="action" value="OK" ' . ("OK" === $action ? ' checked' : '') . '> Approuver';
        echo '<input type="radio" name="action" value="KO" ' . ("KO" === $action ? ' checked' : '') . '> Refuser';
        echo ' cet utilisateur';
        echo '</td>';
        echo "</tr>";

        echo '<tr>';
        echo "<td>Message : </td>";
        echo '<td>';
        echo '<textarea name="messageplus" cols=60 rows=10>' . $message_model . '</textarea>';
        echo '</td>';
        echo "</tr>";

        echo '<tr><td colspan="2">&nbsp;</td></tr>';

        echo "<tr><td></td><td>";
        echo '<button type="reset" class="btn">Effacer</button>';
        echo ' <button type="submit" class="btn">Envoyer</button>';
        echo "</td></tr>";
        echo "</table>";
        echo "</form>";

        echo '<p align="center">Aller à : <a href="index.php">la page d\'accueil</a>';
        echo ' | <a href="' . $root . '/admin/listusers.php">la liste des utilisateurs</a>';
        echo ' | <a href="' . $root . '/admin/gestuser.php?id=' . $user_id . '">la fiche de ' . $nomprenom . '</a></p>';

        echo '</div>';
        echo '</div>';
        include(__DIR__ . '/../templates/front/_footer.php');
        $response->setContent(ob_get_clean());
        $response->send();
