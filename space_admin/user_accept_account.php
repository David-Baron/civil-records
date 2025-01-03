<?php

// TODO: form switch textarea content when action change

use CivilRecords\Engine\MailerFactory;
use Symfony\Component\HttpFoundation\RedirectResponse;

if (!$userAuthorizer->isGranted(9)) {
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
    $session->getFlashBag()->add('danger', 'Pas de compte à approuver avec cette identifiant.');
    $response = new RedirectResponse("$root/admin/utilisateurs");
    $response->send();
    exit();
}

$user = EA_sql_fetch_array($res);
$form_errors = [];

if ($request->getMethod() === 'POST') {

    $action = $request->request->get('action', null);
    if (null === $action) {
        $form_errors['action'] = 'Vous devez sélectionner l\'action à poser';
    }

    if (empty($form_errors)) {
        $mes = "";
        if ($action == "accepted") {
            $statut = 'N';
            $subject = "Approbation de votre compte";
            $email_template = 'new_account_accepted.php'; // TODO: $config->get('MAIL_APPROBATION');
        } elseif ($action == "denied") {
            $statut = 'B';
            $subject = "Refus de votre compte";
            $email_template = 'new_account_denied.php'; // TODO: $config->get('MAIL_REFUS');
        }

        $sql = "UPDATE " . $config->get('EA_UDB') . "_user3 SET statut='" . $statut . "', REM=' ' WHERE ID=" . $user_id . ";";
        EA_sql_query($sql, $u_db);

        $mailerFactory = new MailerFactory();
        $mail = $mailerFactory->createEmail($_ENV['EMAIL_SITE'], $user['email'], $subject, $email_template, [
            'urlsite' => $config->get('EA_URL_SITE'),
            'sitename' => $config->get('SITENAME'),
            'user' => $user,
        ]);
        $mailerFactory->send($mail);

        $session->getFlashBag()->add('success', 'La demande de compte a été ' . $mes . 'e.');
        $response = new RedirectResponse("$root/admin/utilisateurs/detail?id=$user_id");
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

        echo '<h2>Approbation d\'un compte d\'utilisateur</h2>';
        echo '<form method="post">';
        echo '<table class="m-auto" summary="Formulaire">';

        echo "<tr>";
        echo '<td>' . "Candidat : </td>";
        echo '<td><b>' . $user['prenom'] . ' ' . $user['nom'] . "</b></td>";
        echo "</tr>";

        echo "<tr>";
        echo '<td>Login : </td>';
        echo '<td>' . $user['login'] . "</td>";
        echo "</tr>";

        echo "<tr>";
        echo '<td>Action : </td>';
        echo '<td>';
        echo '<select name="action" id="action"><option value="accepted">Approuver</option><option value="denied">Refuser</option></select>';
        echo ' cet utilisateur';
        echo '</td>';
        echo "</tr>";

        echo '<tr><td colspan="2">&nbsp;</td></tr>';

        echo "<tr><td></td><td>";
        echo '<button type="reset" class="btn">Effacer</button>';
        echo '<button type="submit" class="btn">Envoyer</button>';
        echo "</td></tr>";
        echo "</table>";
        echo "</form>";

        echo '<p><a href="' . $root . '/admin/utilisateurs">Liste des utilisateurs</a>';
        echo ' | <a href="' . $root . '/admin/utilisateurs/detail?id=' . $user_id . '">Fiche de ' . $user['prenom'] . ' ' . $user['nom'] . '</a></p>';

        echo '</div>';
        echo '</div>';
        include(__DIR__ . '/../templates/front/_footer.php');
        return (ob_get_clean());
