<?php

use Symfony\Component\HttpFoundation\RedirectResponse;

require(__DIR__ . '/next/bootstrap.php');
require(__DIR__ . '/next/Engine/MailerFactory.php');

$form_errors = [];

if ($request->getMethod() === 'POST') {
    if (empty($request->request->get('login'))) {
        $form_errors['login'] = 'Vous devez préciser le login';
    }

    if (empty($request->request->get('key'))) {
        $form_errors['key'] = 'Vous devez inscrire la clé qui vous été envoyée par mail';
    }

    if (empty($form_errors)) {
        $sql = EA_sql_query("SELECT * FROM " . $config->get('EA_UDB') . "_user3 WHERE login='" . $request->request->get('login')
            . "' AND rem='" . $request->request->get('key') . "'", $u_db);
        if (EA_sql_num_rows($sql) == 1) {
            $user = EA_sql_fetch_array($sql);

            $flash = '';
            $statut = 'N'; // A = attente approbation par admin, N = normal
            if ($config->get('USER_AUTO_DEF') == 1) {
                $statut = 'A';
            }

            $sql = "UPDATE " . $config->get('EA_UDB') . "_user3 SET statut='" . $statut . "', rem=' ' WHERE id=" . $user['ID'] . ";";
            $result = EA_sql_query($sql, $u_db);

            $log = "Activation compte";
            if ($config->get('USER_AUTO_DEF') != 1) {
                $message  = 'Un utilisateur ' . $user['prenom'] . ' ' . $user['nom'] . '<br>';
                $message .= "a demander un accès au site " . $config->get('SITENAME') . '.<br>';
                $message .= 'Veuillez approuver ou refuser cet accès avec le lien suivant : <br>';
                $message .= $config->get('URL_SITE') . '/admin/approuver_compte.php?id=' . $user['ID'] . '<br>';
                $sujet = 'Approbation acces de ' . $user['prenom'] . ' ' . $user['nom'];
                $flash = " Votre demande de compte est soumise à l'approbation de l'administrateur.";
            } else {
                $message  = $user['prenom'] . ' ' . $user['nom'] . " (" . $user['login'] . ")" . '<br>';
                $message .= "vient d'obtenir un accès au site " . $config->get('SITENAME') . "." . '<br>';
                $sujet = "Validation acces de " . $user['prenom'] . ' ' . $user['nom'];
                $flash = " Votre compte est actif et vous pouvez à présent vous connecter.";
            }

            $from = $config->get('SITENAME') . ' <' . $_ENV['EMAIL_SITE'] . ">";
            $to = $_ENV['EMAIL_ADMIN'];
            $mailerFactory = new MailerFactory();
            $mail = $mailerFactory->createEmail($from, $to, $subject, 'email_default.php', [
                'sitename' => $config->get('SITENAME'),
                'urlsite' => $config->get('SITE_URL'),
                'message' => $message
            ]);
            $mailerFactory->send($mail);

            $session->getFlashBag()->add('info', 'Votre adresse a été vérifiée.<br>' . $flash);
            $response = new RedirectResponse("$root/");
            $response->send();
            exit();
        } else {
            $form_errors['key'] = 'Pas/Plus de compte à activer avec ces valeurs. Veuillez vérifier vos codes.';
        }
    }
}

ob_start();
open_page("Activer compte utilisateur", $root); ?>
<div class="main">
    <?php zone_menu(0, $session->get('user', ['level' => 0])['level']); ?>
    <div class="main-col-center text-center">
        <?php navigation($root, 2, "", "Activation de mon compte"); ?>

        <?php if ($config->get('USER_AUTO_DEF') == 0) { ?>
            <p><b>Cette action n'est pas autorisée sur ce site.</b></p>
            <p>Veuillez contacter le gestionnaire du site pour ouvrir un compte utilisateur.</p>
    </div>
</div>
<?php
            include(__DIR__ . '/templates/front/_footer.php');
            $response->setContent(ob_get_clean());
            $response->send();
            exit();
        } ?>
<h2>Activation de mon compte d'utilisateur</h2>

<form method="post">
    <table class="m-auto">
        <tr>
            <td>Login : </td>
            <td>
                <input type="text" size="30" name="login" <?= (isset($form_errors['login']) ? 'class="erreur"' : ''); ?>>
                <?php if (isset($form_errors['login'])) { ?>
                    <div class="invalid-feedback erreur"><?= $form_errors['login']; ?></div>
                <?php } ?>
            </td>
        </tr>

        <tr>
            <td>Clé d'activation : </td>
            <td>
                <input type="text" name="key" size="30" <?= (isset($form_errors['key']) ? 'class="erreur"' : ''); ?>>
                <?php if (isset($form_errors['login'])) { ?>
                    <div class="invalid-feedback erreur"><?= $form_errors['key']; ?></div>
                <?php } ?>
            </td>
        </tr>
        <tr>
            <td></td>
            <td>
                <button type="reset">Effacer</button>
                <button type="submit">Activer le compte</button>
            </td>
        </tr>
    </table>
</form>
<p>
    <a href="<?= $root; ?>/">Retour à la page d'accueil</a>
</p>
</div>
</div>
<?php include(__DIR__ . '/templates/front/_footer.php');

$response->setContent(ob_get_clean());
$response->send();
