<?php

use Symfony\Component\HttpFoundation\RedirectResponse;

require(__DIR__ . '/next/bootstrap.php');

$app_contacts = [
    'email_admin' => 'Administrateur',
    'email_corrector' => 'Correcteur'
];

$form_errors = [];
$identity = $request->get('identity', '');
$message = $request->get('message', '');
$email = $request->get('email', '');
$website  = $request->get('website', '');
$objet = $request->get('objet', '');

if ($request->getMethod() === 'POST') {
    if (empty($request->request->get('identity'))) {
        $form_errors['identity'] = 'Merci de préciser vos nom et prenom';
    }
    if (empty($request->request->get('email')) || !filter_var($request->request->get('email'), FILTER_VALIDATE_EMAIL)) {
        $form_errors['email'] = "L'adresse mail dooit être un email valide";
    }
    if ($request->request->get('website') && !filter_var($request->request->get('website'), FILTER_VALIDATE_URL)) {
        $form_errors['website'] = "Le site web doit être une url valide.";
    }
    if (strlen($request->request->get('message')) < 10) {
        $form_errors['message'] = 'Le message doit comporter minimum 10 caractères.';
    }
    if (strlen($request->request->get('objet')) < 6) {
        $form_errors['objet'] = 'L\'objet de votre message  doit comporter minimum 6 caractères.';
    }
    if ($config->get('AUTO_CAPTCHA') && function_exists('imagettftext')) {
        if (md5($request->request->get('captcha')) != $_SESSION['valeur_image']) {
            $form_errors['captcha'] = 'Attention à bien recopier le code dissimulé dans l\'image !';
        }
    }
    if (empty($form_errors)) {
        $mail_message = "Message envoyé par " . $identity . " (" . $email . ") via " . $config->get('SITENAME') . '<br><br>';
        if ($website <> "") {
            $mail_message .= "Site web : " . $website . "<br><br>";
        }
        $mail_message .= $mail_message . '<br><br>';

        $from = $nompre . ' <' . $email . '>';
        $to = $_ENV['EMAIL_ADMIN'];
        $mailerFactory = new MailerFactory();
        $mail = $mailerFactory->createEmail($from, $to, $objet, 'email_default.php', [
            'sitename' => $config->get('SITENAME'),
            'urlsite' => $config->get('SITE_URL'),
            'message' => $mail_message
        ]);
        $mailerFactory->send($mail);

        $session->getFlashBag()->add('info', 'Le message à été envoyer. Nous vous répondrons rapidement.');
        $response = new RedirectResponse("$root/");
        $response->send();
        exit();
    }
}

ob_start();
open_page("Formulaire de contact", $root); ?>
<div class="main">
    <?php zone_menu(0, $session->get('user', ['level' => 0])['level']); ?>

    <div class="main-col-center text-center">
        <?php navigation($root, 2, "", "Formulaire de contact"); ?>
        <h2>Formulaire de contact</h2>
        <form method="post">
            <table class="m-auto" summary="Formulaire">
                <tr>
                    <td>Vos nom et prénom : </td>
                    <td><input type="text" size="50" name="identity" value="<?= $identity; ?>" required></td>
                    <?php if (isset($form_errors['identity'])) { ?>
                        <div class="invalid-feedback erreur"><?= $form_errors['identity']; ?></div>
                    <?php } ?>
                </tr>
                <tr>
                    <td>Votre e-mail : </td>
                    <td>
                        <input type="text" name="email" size="50" value="<?= $email; ?>" required>
                        <?php if (isset($form_errors['email'])) { ?>
                            <div class="invalid-feedback erreur"><?= $form_errors['email']; ?></div>
                        <?php } ?>
                    </td>
                </tr>
                <tr>
                    <td>Votre site web : </td>
                    <td>
                        <input type="text" name="website" size="50" value="<?= $website; ?>">
                        <?php if (isset($form_errors['website'])) { ?>
                            <div class="invalid-feedback erreur"><?= $form_errors['website']; ?></div>
                        <?php } ?>
                    </td>
                </tr>
                <tr>
                    <td>Sujet :</td>
                    <td>
                        <input type="text" name="objet" size="50" value="<?= $objet; ?>" required>
                        <?php if (isset($form_errors['objet'])) { ?>
                            <div class="invalid-feedback erreur"><?= $form_errors['objet']; ?></div>
                        <?php } ?>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">Votre message : </td>
                </tr>
                <tr>
                    <td colspan="2">
                        <textarea name="message" cols="80" rows="12" required><?= $message; ?></textarea>
                        <?php if (isset($form_errors['message'])) { ?>
                            <div class="invalid-feedback erreur"><?= $form_errors['message']; ?></div>
                        <?php } ?>
                    </td>
                </tr>
                <?php if ($config->get('AUTO_CAPTCHA') && function_exists('imagettftext')) { ?>
                    <tr>
                        <td><img src="<?= $root; ?>/tools/captchas/image.php" alt="captcha" id="captcha"></td>
                        <td>
                            Recopiez le code ci-contre : <br>
                            <input type="text" name="captcha" size="6" maxlength="5" value="" required>
                            <?php if (isset($form_errors['captcha'])) { ?>
                                <div class="invalid-feedback erreur"><?= $form_errors['captcha']; ?></div>
                            <?php } ?>
                        </td>
                    </tr>
                <?php } ?>
                <tr>
                    <td></td>
                    <td>
                        <button type="reset" class="btn">Effacer</button>
                        <button type="submit" class="btn">Envoyer</button>
                    </td>
                </tr>
            </table>
            <input type="hidden" name="action" value="submitted">
        </form>
    </div>
</div>
<?php include(__DIR__ . '/templates/front/_footer.php');

$response->setContent(ob_get_clean());
$response->send();
