<?php

use Symfony\Component\HttpFoundation\RedirectResponse;

define('ADM', 0); // Compatibility only
$admtxt = ''; // Compatibility only
require(__DIR__ . '/next/bootstrap.php');
require(__DIR__ . '/next/_COMMUN_env.inc.php'); // Compatibility only
require(__DIR__ . '/next/Model/UserModel.php');

if ($config->get('USER_AUTO_DEF') == 0) {
    $flash = 'Cette action n\'est pas autorisée sur ce site</b> Vous devez contacter le gestionnaire du site pour demander un compte utilisateur.';
    $session->getFlashBag()->add('warning', $flash);
    $response = new RedirectResponse("$root/");
    $response->send();
    exit();
}

$nom   = '';
$prenom = '';
$login = '';
$passw = '';
$email   = '';
$libre   = '';

$form_errors = [];

if ($request->getMethod() === 'POST') {
    $nom   = $request->request->get('nom', '');
    $prenom = $request->request->get('prenom', '');
    $login = $request->request->get('login', '');
    $passw = $request->request->get('passw', '');
    $passwverif = $request->request->get('passwverif', '');
    $email   = $request->request->get('email', '');
    $libre   = $request->request->get('libre', '');
    $acceptcond = $request->request->get('acceptcond', false);
    if (empty($nom)) {
        $form_errors['nom'] = 'Vous devez préciser votre nom de famille';
    }
    if (empty($prenom)) {
        $form_errors['prenom'] = 'Vous devez préciser votre prénom';
    }
    if (empty($email) or !filter_var(FILTER_VALIDATE_EMAIL)) {
        $form_errors['email'] = "Vous devez préciser une adresse email valide";
    }
    if (!empty($config->get('USER_ZONE_LIBRE')) && strlen($libre) < 2) {
        $form_errors['libre'] = 'Vous devez compléter la zone [' . $zonelibre . ']';
    }
    if (!empty($config->get('TXT_CONDIT_USAGE')) &&  false == $acceptcond) {
        $form_errors['acceptcond'] = "Vous devez marquer votre accord sur les conditions d'utilisation";
    }
    if (strlen($login) < 3 || strlen($login) > 15 || !sans_quote($login)) {
        $form_errors['login'] = 'Login entre 3 et 15 caractères alpha numérique sans apostrophe.';
    }
    if (strlen($passw) < 6 || strlen($passw) > 15 || !sans_quote($passw)) {
        $form_errors['passw'] = 'Mot de passe entre 6 et 15 caractères alpha numérique sans apostrophe.';
    }
    if ($passw <> $passwverif) {
        $form_errors['passwverif'] = 'Les deux copies du MOT DE PASSE ne sont pas identiques';
    }
    if ($config->get('AUTO_CAPTCHA') && function_exists('imagettftext')) {
        if (md5($request->request->get('captcha')) != $_SESSION['valeur_image']) {
            $form_errors['captcha'] = 'Attention à bien recopier le code dissimulé dans l\'image !';
        }
    }
    $res = EA_sql_query("SELECT * FROM " . $config->get('EA_UDB') . "_user3 WHERE login='" . $login . "'", $u_db);
    if (EA_sql_num_rows($res) != 0) {
        $row = EA_sql_fetch_array($res);
        $form_errors['login'] = 'Ce login est déjà utilisé par un autre utilisateur, choissisez-en un autre.';
    }
    $res = EA_sql_query("SELECT * FROM " . $config->get('EA_UDB') . "_user3 WHERE email='" . sql_quote($email) . "'", $u_db);
    if (EA_sql_num_rows($res) != 0) {
        $row = EA_sql_fetch_array($res);
        $form_errors['email'] = 'Cette adresse mail possède déjà un code de login, utilisez-en une autre ou faite vous renvoyer votre mot de passe.';
    }
    if (empty($form_errors)) {
        $clevalid = MakeRandomPassword(15);
        $user = [
            'nom' => $nom, 
            'prenom' => $prenom, 
            'email' => $email, 
            'level' => $config->get('USER_AUTO_LEVEL'), 
            'login' => $login, 
            'hashpass' => sha1($passw), // TODO: Attempting the new encryption
            'regime' => $config->get('GEST_POINTS'), 
            'solde' => $config->get('PTS_PAR_PER'), 
            'statut' => 'W', // TODO: Can cause pb if app no mail
            'libre' => $libre, 
            'REM' => $clevalid
        ];
        $userModel = new UserModel();
        $userModel->insert($user);

        $log = "Créat. auto user";
        $crlf = chr(10) . chr(13);
        $message = $config->get('MAIL_AUTOUSER');
        $flash = '';
        if ($config->get('USER_AUTO_DEF') == 1) {
            $message = $config->get('MAIL_VALIDUSER');
        }

        $urlvalid = $config->get('EA_URL_SITE') . $root . "/activer_compte.php?login=" . $login . "&amp;key=" . $clevalid . $crlf . $crlf;
        $urlsite = $config->get('EA_URL_SITE') . $root . "/";
        $codes = array("#NOMSITE#", "#URLSITE#", "#LOGIN#", "#PASSW#", "#NOM#", "#PRENOM#", "#URLVALID#", "#KEYVALID#");
        $decodes = array($config->get('SITENAME'), $urlsite, $login, $passw, $nom, $prenom, $urlvalid, $clevalid);
        $bon_message = str_replace($codes, $decodes, $message);
        $sujet = "Votre compte " . $config->get('SITENAME');
        $sender = mail_encode($config->get('SITENAME')) . ' <' . $config->get('LOC_MAIL') . ">";
        $okmail = sendmail($sender, $email, $sujet, $bon_message);
        if ($okmail) {
            $log .= " + mail";
            $flash = " et un mail vous a été envoyé pour procéder à l'activation.";
            $flash .= "<br>Si vous ne recevez pas de mail, merci d'en avertir l'administrateur afin qu'il active votre compte.";
        } else {
            $log .= " NO mail";
            $flash = " mais le mail n'a pas pu être envoyé : contactez l'administrateur du site pour le faire activer.";
        }

        writelog($log, $lelogin, 0);
        $session->getFlashBag()->add('info', 'Votre compte a été créé.<br>' . $flash);
        $response = new RedirectResponse("$root/");
        $response->send();
        exit();
    }
}

ob_start();
open_page("Créer mon compte utilisateur", $root); ?>
<div class="main">
    <?php zone_menu(0, 0); ?>

    <div class="main-col-center text-center">
        <?php navigation($root, 2, "", "Création de mon compte"); ?>
        <h2>Création de mon compte d'utilisateur</h2>
        <form method="post">
            <table class="m-auto" summary="Formulaire">
                <tr>
                    <td>Nom : </td>
                    <td>
                        <input type="text" size="30" name="nom" value="<?= $nom; ?>">
                        <?php if (isset($form_errors['nom'])) { ?>
                            <div class="invalid-feedback erreur"><?= $form_errors['nom']; ?></div>
                        <?php } ?>
                    </td>
                </tr>
                <tr>
                    <td>Prénom : </td>
                    <td>
                        <input type="text" name="prenom" size="30" value="<?= $prenom; ?>">
                        <?php if (isset($form_errors['prenom'])) { ?>
                            <div class="invalid-feedback erreur"><?= $form_errors['prenom']; ?></div>
                        <?php } ?>
                    </td>
                </tr>
                <?php if (!empty($config->get('USER_ZONE_LIBRE'))) { ?>
                    <tr>
                        <td><?= $config->get('USER_ZONE_LIBRE'); ?> : </td>
                        <td>
                            <input type="text" name="libre" size="50" value="<?= $libre; ?>">
                            <?php if (isset($form_errors['libre'])) { ?>
                                <div class="invalid-feedback erreur"><?= $form_errors['libre']; ?></div>
                            <?php } ?>
                        </td>
                    </tr>
                <?php } ?>
                <tr>
                    <td>E-mail : </td>
                    <td>
                        <input type="email" name="email" size="50" value="<?= $email; ?>">
                        <?php if (isset($form_errors['email'])) { ?>
                            <div class="invalid-feedback erreur"><?= $form_errors['email']; ?></div>
                        <?php } ?>
                    </td>
                </tr>
                <tr>
                    <td>Login : </td>
                    <td>
                        <input type="text" name="login" size="15" maxlength="15" value="<?= $login; ?>">
                        <?php if (isset($form_errors['login'])) { ?>
                            <div class="invalid-feedback erreur"><?= $form_errors['login']; ?></div>
                        <?php } ?>
                    </td>
                </tr>
                <tr>
                    <td>Mot de passe : </td>
                    <td>
                        <input type="password" name="passw" size="15" maxlength="15">
                        <?php if (isset($form_errors['passw'])) { ?>
                            <div class="invalid-feedback erreur"><?= $form_errors['passw']; ?></div>
                        <?php } ?>
                    </td>
                </tr>
                <tr>
                    <td>Mot de passe (vérification) : </td>
                    <td>
                        <input type="password" name="passwverif" size="15" maxlength="15">
                        <?php if (isset($form_errors['passwverif'])) { ?>
                            <div class="invalid-feedback erreur"><?= $form_errors['passwverif']; ?></div>
                        <?php } ?>
                    </td>
                </tr>
                <?php if ($config->get('TXT_CONDIT_USAGE') <> "") { ?>
                    <tr>
                        <td>Conditions d'utilisation : </td>
                        <td>
                            <textarea name="captcha" cols="60" rows="10" readonly><?= $config->get('TXT_CONDIT_USAGE'); ?></textarea>
                            <input type="checkbox" name="acceptcond">J'ai lu et j'accepte les conditions ci-dessus.</input>
                            <?php if (isset($form_errors['acceptcond'])) { ?>
                                <div class="invalid-feedback erreur"><?= $form_errors['acceptcond']; ?></div>
                            <?php } ?>
                        </td>
                    </tr>
                <?php } ?>
                <?php if ($config->get('AUTO_CAPTCHA') && function_exists('imagettftext')) { ?>
                    <tr>
                        <td><img src="<?= $root; ?>/tools/captchas/image.php" alt="captcha" id="captcha"></td>
                        <td>
                            Recopiez le code ci-contre : <br>
                            <input type="text" name="captcha" size="6" maxlength="5" value="">
                            <?php if (isset($form_errors['captcha'])) { ?>
                                <div class="invalid-feedback erreur"><?= $form_errors['captcha']; ?></div>
                            <?php } ?>
                        </td>
                    </tr>
                <?php } ?>
                <tr>
                    <td></td>
                    <td>
                        <button type="reset">Effacer</button>
                        <button type="submit">Inscrivez-moi</button>
                    </td>
                </tr>
            </table>
        </form>
        <p><a href="<?= $root; ?>/">Retour à la page d'accueil</a></p>
    </div>
</div>
<?php include(__DIR__ . '/templates/front/_footer.php');

$response->setContent(ob_get_clean());
$response->send();
