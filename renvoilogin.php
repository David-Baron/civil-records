<?php

use CivilRecords\Engine\MailerFactory;
use Symfony\Component\HttpFoundation\RedirectResponse;

require(__DIR__ . '/src/bootstrap.php');

$form_errors = [];

if ($request->getMethod() === 'POST') {
    if ($request->request->get('email') == "" || filter_var(FILTER_VALIDATE_EMAIL) === false) {
        $form_errors['email'] = 'Vous devez fournir une adresse email valide.';
    }

    if (empty($form_errors)) {
        $sql = "SELECT nom, prenom, login, email, level FROM " . $config->get('EA_UDB') . "_user3 WHERE email = '" . getparam('email') . "'; ";
        $result = EA_sql_query($sql, $u_db);
        $nb = EA_sql_num_rows($result);
        if ($nb == 1) {
            $user = EA_sql_fetch_array($result);
            $userlevel = $user["level"];
            $pw = MakeRandomPassword(8);
            $hash = sha1($pw);
            $reqmaj = "UPDATE " . $config->get('EA_UDB') . "_user3 SET hashpass='" . $hash . "' " .
                " WHERE email = '" . $request->request->get('email') . "';";
            $result = EA_sql_query($reqmaj, $u_db);

            $message  = "Bonjour,<br><br>";
            $message .= "Voici vos codes d'accès au site : <br><br>";
            $message .= $config->get('EA_URL_SITE')  . "<br><br>";
            $message .= "Votre login : " . $user['login'] . '<br>';
            $message .= "Votre NOUVEAU mot de passe : " . $pw . '<br><br>';
            if ($userlevel >= $config->get('CHANGE_PW')) {
                $message .= "Vous pourrez changer ce mot de passe dans votre espace utilisateur.<br><br>";
            }
            $message .= "Cordialement,<br>";
            $message .= "Votre webmestre.";

            $from = $config->get('SITENAME') . ' <' . $_ENV['EMAIL_SITE'] . ">";
            $to = $user['email'];
            $subject = "Rappel de vos codes pour " . $config->get('SITENAME');
            $mailerFactory = new MailerFactory();
            $mail = $mailerFactory->createEmail($from, $to, $subject, 'email_default.php', [
                'sitename' => $config->get('SITENAME'),
                'urlsite' => $config->get('SITE_URL'),
                'message' => $message
            ]);
            $mailerFactory->send($mail);

            $session->getFlashBag()->add('info', 'Veuillez consultez votre messagerie pour récupérer vos codes d\'accès.');
            $response = new RedirectResponse("$root/");
            $response->send();
            exit();

        } elseif ($nb > 1) {
            $form_errors['email'] = 'Cette adresse email est référencée pour plusieurs comptes. Contactez nous directement <a href="' .$root .'/contact.php">ici.</a>';
        }
    }
}

ob_start();
open_page("Renvoi codes d'accès", $root); ?>
<div class="main">
    <?php zone_menu(0, $session->get('user', ['level' => 0])['level']); ?>
    <div class="main-col-center text-center">
        <?php navigation($root, 2, "R", "Renvoi des codes d'accès"); ?>

        <h2>Renvoi des codes d'accès au site</h2>
        <p>Vos codes d'accès peuvent vous être renvoyés à l'adresse mail associée à votre compte d'utilisateur</p>
        <form method="post">
            <table class="m-auto" summary="Formulaire">
                <tr>
                    <td>Adresse e-mail : </td>
                    <td><input name="email" /></td>
                </tr>
                <tr>
                    <td></td>
                    <td><button type="submit">Envoyer</button></td>
                </tr>
            </table>
        </form>
        <p>
            <a href="<?= $root; ?>/acces.php">Voir les conditions d'accès à la partie privée du site</a>
        </p>
    </div>
</div>
<?php include(__DIR__ . '/templates/front/_footer.php');

$response->setContent(ob_get_clean());
$response->send();
