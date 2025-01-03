<?php

use CivilRecords\Engine\MailerFactory;
use Symfony\Component\HttpFoundation\RedirectResponse;

if (!$userAuthorizer->isGranted(8)) {
    $response = new RedirectResponse("$root/admin/");
    $response->send();
    exit();
}

$form_errors = [];
$success_or_failure_message = '';

if ($request->getMethod() === 'POST') {
    if (empty($request->request->get('email')) || !filter_var($request->request->get('email'), FILTER_VALIDATE_EMAIL)) {
        $form_errors['email'] = "Vous devez préciser votre adresse email";
    }
    if (empty($form_errors)) {
        $from = $config->get('SITENAME') . ' <' . $_ENV['EMAIL_SITE'] . ">";
        $to = $request->request->get('email');
        $subject = 'Test messagerie de ' . $config->get('SITENAME');
        $mailerFactory = new MailerFactory();
        $mail = $mailerFactory->createEmail($from, $to, $subject, null, [
            'message' => 'Ce message de test a été envoyé via Civil-Records.'
        ]);
        $mailerFactory->send($mail);
        $success_or_failure_message = "<p>Un mail de test vous a été envoyé. Vérifiez qu'il vous est bien parvenu.</p>";
    }
}

ob_start();
open_page("Test e-mail", $root); ?>
<div class="main">
    <?php zone_menu(10, $session->get('user')['level']); ?>
    <div class="main-col-center text-center">
        <?php navadmin($root, "Test du mail"); ?>
        <h1>Test de l'envoi d'un mail</h1>
        <div><?= $success_or_failure_message; ?></div>
        <form method="post">
            <table class="m-auto">
                <tr>
                    <td>Votre adresse email : </td>
                    <td><input type="email" name="email" size="40" required></td>
                </tr>
                <tr>
                    <td></td>
                    <td>
                        <button type="reset" class="btn">Effacer</button>
                        <button type="submit" class="btn">Envoyer</button>
                    </td>
                </tr>
            </table>
        </form>
        <p><a href="<?= $root; ?>/admin/application/parameters?grp=Mail">Retour au module de paramétrage</a></p>
    </div>
</div>
<?php
include(__DIR__ . '/../templates/front/_footer.php');
return (ob_get_clean());