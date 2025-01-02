<?php

use Symfony\Component\HttpFoundation\RedirectResponse;

require(__DIR__ . '/src/bootstrap.php');

if (!$userAuthorizer->isAuthenticated()) {
    $response = new RedirectResponse("$root/login.php");
    $response->send();
    exit();
}

$form_errors = [];

if ($request->getMethod() === 'POST') {
    // Mot de passe transmis en clair
    if (strlen($request->request->get('new_password')) < 6) {
        $form_errors['password'] = 'Le mot de passe doit contenir au moins 6 caractères';
    }
    if (!(sans_quote($request->request->get('new_password')))) {
        $form_errors['new_password'] = 'Le mot de passe ne doit pas contenir d\'apostrophe';
    }
    if ($request->request->get('new_password') <> $request->request->get('new_password_confirm')) {
        $form_errors['new_password_confirm'] = 'Les mots de passe ne sont pas identiques';
    }

    if ($session->get('user')['hashpass'] !== sha1($request->request->get('actual_password'))) {
        $form_errors['actual_password'] = 'Votre ancien mot de passe n\'est pas correct';
    }

    if (empty($form_errors)) {
        $new_password = sha1($request->request->get('new_password'));
        $sql = "UPDATE " . $config->get('EA_UDB') . "_user3 SET hashpass='" . $new_password . "' WHERE id=" . $session->get('user')['ID'] . ";";
        $result = EA_sql_query($sql);
        $session->getFlashBag()->add('info', 'Veuillez vous reconnecter avec le nouveau mot de passe.');
        $response = new RedirectResponse("$root/login.php");
        $response->send();
        exit();
    }
}

ob_start();
open_page("Changement de mot de passe", $root); ?>
<div class="main">
    <?php zone_menu(0, $session->get('user', ['level' => 0])['level']); ?>
    <div class="main-col-center text-center">
        <?php navigation($root, 2, 'A', "Changement de mot de passe"); ?>

        <h2>Modification de votre mot de passe</h2>
        <form method="post">
            <table class="m-auto" summary="Formulaire">
                <tr>
                    <td>Code utilisateur : </td>
                    <td><?= $session->get('user')['login']; ?></td>
                </tr>
                <tr>
                    <td>Ancien mot de passe : </td>
                    <td>
                        <input type="password" name="actual_password" id="actual_password" <?= isset($form_errors['actual_password']) ? ' erreur' : ''; ?>>
                    </td>
                    <td>
                        <?php if (isset($form_errors['actual_password'])) { ?>
                            <div class="erreur"> <?= $form_errors['actual_password']; ?></div>
                        <?php } else { ?>
                            <img onmouseover="seetext(actual_password)" onmouseout="seeasterisk(actual_password)"
                                src="<?= $root; ?>/assets/img/eye-16-16.png"
                                alt="Voir mot de passe" width="16" height="16">
                        <?php } ?>
                    </td>
                </tr>
                <tr>
                    <td>Nouveau mot de passe : </td>
                    <td>
                        <input type="password" name="new_password" id="new_password">
                    </td>
                    <td>
                        <?php if (isset($form_errors['new_password'])) { ?>
                            <div class="erreur"><?= $form_errors['new_password']; ?></div>
                        <?php } else { ?>
                            <img onmouseover="seetext(new_password)" onmouseout="seeasterisk(new_password)"
                                src="<?= $root; ?>/assets/img/eye-16-16.png"
                                alt="Voir mot de passe" width="16" height="16">
                        <?php } ?>
                    </td>
                </tr>
                <tr>
                    <td>Nouveau mot de passe (vérif.) : </td>
                    <td>
                        <input type="password" name="new_password_confirm" id="new_password_confirm">
                    </td>
                    <td>
                        <?php if (isset($form_errors['new_password_confirm'])) { ?>
                            <div class="erreur"><?= $form_errors['new_password_confirm']; ?></div>
                        <?php } else { ?>
                            <img onmouseover="seetext(new_password_confirm)" onmouseout="seeasterisk(new_password_confirm)"
                                src="<?= $root; ?>/assets/img/eye-16-16.png"
                                alt="Voir mot de passe" width="16" height="16">
                        <?php } ?>
                    </td>
                </tr>
                <tr>
                    <td></td>
                    <td>
                        <button type="reset" class="btn">Effacer</button>
                        <button type="submit" class="btn">Modifier</button>
                    </td>
                    <td></td>
                </tr>
            </table>
        </form>
    </div>
</div>
<script type="text/javascript">
    function seetext(x) {
        x.type = 'text';
    }

    function seeasterisk(x) {
        x.type = 'password';
    }
</script>
<?php include(__DIR__ . '/templates/front/_footer.php');

$response->setContent(ob_get_clean());
$response->send();
