<?php

use Symfony\Component\HttpFoundation\RedirectResponse;

require(__DIR__ . '/../next/bootstrap.php');
require(__DIR__ . '/../next/_COMMUN_env.inc.php'); // Compatibility only

if (!$userAuthorizer->isGranted(8)) {
    $response = new RedirectResponse("$root/admin/");
    $response->send();
    exit();
}

$form_errors = [];
$success_message = '';

if ($request->getMethod() === 'POST') {
    $dest = $request->request->get('email');
    if (empty($dest)) {
        $form_errors['email'] = "Vous devez préciser votre adresse email";
    }
    if (empty($form_errors)) {
        $sender = mail_encode($config->get('SITENAME')) . ' <' . $config->get('LOC_MAIL') . ">";
        $okmail = sendmail($sender, $dest, 'Test messagerie de ' . $config->get('SITENAME'), 'Ce message de test a été envoyé via ExpoActes');
        if ($okmail) {
            $success_message = "<p>Un mail de test vous a été envoyé. Vérifiez qu'il vous est bien parvenu.</p>";
        } else {
            $success_message = "<p>La fonction mail n'a pas pu être vérifée.<br>";
            $success_message .= "<b>La consultation des actes peut très bien fonctionner sans mail</b> mais plusieurs fonctions de gestion des utilisateurs ne fonctionneront pas.";
        }
    }
}

ob_start();
open_page("Test e-mail", $root); ?>
<div class="main">
    <?php zone_menu(10, $session->get('user')['level']); ?>
    <div class="main-col-center text-center">
        <?php navadmin($root, "Test du mail"); ?>
        <h1>Test de l'envoi d'un mail</h1>
        <h3>Cette procédure ne peut envoyer qu'un mail de test !</h3>
        <div><?= $sucess_message; ?></div>
        <p>Le texte du mail est donc fixe.</p>
        <form method="post">
            <table class="m-auto">
                <tr>
                    <td>Votre adresse email : </td>
                    <td><input type="text" name="email" size=40 value="<?= $config->get('LOC_MAIL'); ?>"></td>
                </tr>
                <tr>
                    <td></td>
                    <input type="hidden" name="action" value="submitted">
                    <td><button type="reset" class="btn">Effacer</button>
                        <button type="submit" class="btn">Envoyer</button>
                    </td>
                </tr>
            </table>
        </form>
        <p><a href="<?= $root; ?>/admin/gest_params.php?grp=Mail">Retour au module de paramétrage</a></p>
    </div>
</div>
<?php
include(__DIR__ . '/../templates/front/_footer.php');
$response->setContent(ob_get_clean());
$response->send();
