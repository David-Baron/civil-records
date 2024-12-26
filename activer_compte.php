<?php

use Symfony\Component\HttpFoundation\RedirectResponse;

require(__DIR__ . '/next/bootstrap.php');
require(__DIR__ . '/next/_COMMUN_env.inc.php'); // Compatibility only

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
            . "' AND rem='" . $request->request->get('key') . "' AND statut='W'", $u_db);
        if (EA_sql_num_rows($sql) == 1) {
            $user = EA_sql_fetch_array($sql);
            // $login = $user['login'];
            $id = $user['ID'];
            $nomprenom = $user['prenom'] . ' ' . $user['nom'];
            $login = $user['login'];
            $mes = "";
            $statut = 'N'; // A = attente approbation par admin, N = normal
            if ($config->get('USER_AUTO_DEF') == 1) {
                $statut = 'A';
            }

            $sql = "UPDATE " . $config->get('EA_UDB') . "_user3 SET statut='" . $statut . "', rem=' ' WHERE id=" . $id . ";";
            $result = EA_sql_query($sql, $u_db);
            $crlf = chr(10) . chr(13);
            $log = "Activation compte";
            if ($config->get('USER_AUTO_DEF') == 1) {
                $message  = $nomprenom . " (" . $login . ")" . $crlf;
                $message .= "vient de demander accès au site " . $config->get('SITENAME') . "." . $crlf;
                $message .= "Vous pouvez APPROUVER cet acces avec le lien suivant : " . $crlf;
                $message .= $config->get('EA_URL_SITE') . $root . "/admin/approuver_compte.php?id=" . $id . "&action=OK" . $crlf;
                $message .= "OU " . $crlf;
                $message .= "Vous pouvez REFUSER cet acces avec le lien suivant : " . $crlf;
                $message .= $config->get('EA_URL_SITE') . $root . "/admin/approuver_compte.php?id=" . $id . "&action=KO" . $crlf;
                $sujet = "Approbation acces de " . $nomprenom;
                $mes = " Votre demande de compte est soumise à l'approbation de l'administrateur.";
            } else {
                $message  = $nomprenom . " (" . $login . ")" . $crlf;
                $message .= "vient d'obtenir un accès au site " . $config->get('SITENAME') . "." . $crlf;
                $sujet = "Validation acces de " . $nomprenom;
                $mes = " Votre compte est actif et vous pouvez à présent vous connecter.";
            }
            $sender = mail_encode($config->get('SITENAME')) . ' <' . $config->get('LOC_MAIL') . ">";
            $okmail = sendmail($sender, $config->get('LOC_MAIL'), $sujet, $message);
            if ($okmail) {
                $log .= " + mail";
            } else {
                $log .= " NO mail";
            }
            writelog($log, $login, 0);
            $session->getFlashBag()->add('info', 'Votre adresse a été vérifiée.<br>' . $mes);
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
