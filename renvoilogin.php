<?php

use Symfony\Component\HttpFoundation\RedirectResponse;

require(__DIR__ . '/next/bootstrap.php');
require(__DIR__ . '/next/_COMMUN_env.inc.php'); // Compatibility only

$form_errors = [];

if ($request->getMethod() === 'POST') {
    if ($request->request->get('email') == "" || filter_var(FILTER_VALIDATE_EMAIL) === false) {
        $form_errors['email'] = 'Vous devez fournir une adresse email valide.';
    }
    
    if(empty($form_errors)) {
        $sql = "SELECT nom, prenom,login,email,level FROM " . $config->get('EA_UDB') . "_user3 WHERE email = '" . getparam('email') . "'; ";
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

            $lb        = "\r\n";
            $message  = "Bonjour," . $lb;
            $message .= "" . $lb;
            $message .= "Voici vos codes d'accès au site  :" . $lb;
            $message .= "" . $lb;
            $message .= $config->get('EA_URL_SITE') . $root . "/index.php" . $lb;
            $message .= "" . $lb;
            $message .= "Votre login : " . $user['login'] . $lb;
            $message .= "Votre NOUVEAU mot de passe : " . $pw . $lb;
            $message .= "" . $lb;
            if ($userlevel >= $config->get('CHANGE_PW')) {
                $message .= "Vous pourrez changer ce mot de passe dans votre espace utilisateur." . $lb;
                $message .= "" . $lb;
            }
            $message .= "Cordialement," . $lb;
            $message .= "" . $lb;
            $message .= "Votre webmestre." . $lb;

            $sujet = "Rappel de vos codes pour " . $config->get('SITENAME');
            $sender = mail_encode($config->get('SITENAME')) . ' <' . $config->get('LOC_MAIL') . ">";
            $okmail = sendmail($sender, $user['email'], $sujet, $message);
            if (!$okmail) {
                $form_errors['email'] = 'Un problème lors de l\'envoi du mail! Veuillez contactez <a href=mailto:' . $config->get('LOC_MAIL') . '>l\'administrateur.</a>';
            } else {
                writelog('Renvoi login/password', $user['login'], 0);
                $session->getFlashBag()->add('warning', 'Veuillez consultez votre messagerie pour récupérer vos codes d\'accès.');
                $response = new RedirectResponse("$root/");
                $response->send();
                exit();
            }
        } elseif ($nb > 1) {
            $form_errors['email'] = 'Cette adresse email est référencée pour plusieurs comptes. Contactez <a href=mailto:' . $config->get('LOC_MAIL') . '>l\'administrateur.</a>';
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
