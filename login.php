<?php

use Symfony\Component\HttpFoundation\RedirectResponse;

define('ADM', 0); // Compatibility only
$admtxt = ''; // Compatibility only
require(__DIR__ . '/next/bootstrap.php');
require(__DIR__ . '/next/_COMMUN_env.inc.php'); // Compatibility only
require(__DIR__ . '/next/Engine/AppUserAuthenticator.php');

if ($session->has('user')) {
    $response = new RedirectResponse("$root/?act=logout");
    $response->send();
    exit();
}
// $xcomm = ""; Useless now.
// $xpatr = ""; Useless now.
// $page = ""; Useless now.
// $uri = getparam('uri', $root . '/'); // Useless now.
// $motif = getparam('cas'); Useless now.
/* if ($motif == 1) {
    msg('Login ou mot de passe incorrect (vérifiez Majuscules/minuscules) !');
}
if ($motif == 2) {
    msg("L'accès à la page que vous voulez consulter est réservé"); // Oui mais pas à la bonne place...
}
if ($motif == 3) {
    msg('Vos droits sont insuffisants pour accéder à cette page'); // Ceci ne devrait JAMAIS arriver...
}
if ($motif == 4) {
    msg("Vous devez vous reconnecter avec le nouveau mot de passe"); // Oui mais pas à la bonne place...
}
if ($motif == 5) {
    msg("Votre compte doit encore être activé et/ou approuvé"); // Oui mais pas à la bonne place...
}
if ($motif == 6) {
    msg("Votre compte a expiré. Contactez l'administrateur pour le réactiver"); // Ceci ne devrait JAMAIS arriver...
}
if ($motif == 7) {
    msg("Votre compte est bloqué. Contactez l'administrateur"); // Ceci ne devrait JAMAIS arriver...
} */
$form_errors = [];

if ($request->getMethod() === 'POST') {
    if ($session->get('antiflood', 0) >= 5) {
        $form_errors['antiflood'] = 'Vous avez dépasser le nombre d\'essai! Vous pourrez réessayer dans 24 heures.';
    }

    if (empty($form_errors) && $request->request->get('login') && $request->request->get('passwd')) {
        $appUserAuthenticator = new AppUserAuthenticator($session);
        if ($appUserAuthenticator->authenticate($request->request->get('login'), $request->request->get('passwd'))) {
            $response = new RedirectResponse('.');
            $response->send();
            exit;
        }

        $form_errors['credencials'] = 'Login ou mot de passe incorrect!';
    }
}

ob_start();
open_page("Login", $root, null, null, null, '../index.htm'); ?>
<div class="main">
    <?php zone_menu(0, $session->get('user', ['level' => 0])['level']); ?>
    <div class="main-col-center text-center">
        <?php navigation($root, 2, 'A', "Connexion"); ?>
        <h2>Vous devez vous identifier : </h2>
        <?php foreach ($form_errors as $key => $value) { ?>
           <div class="danger"><?= $value; ?></div>
        <?php } ?>

        <form method="post">
            <table class="m-auto">
                <tr>
                    <td>Login</td>
                    <td><input type="text" name="login" maxlength="15"></td>
                    <td></td>
                </tr>
                <tr>
                    <td>Mot de passe</td>
                    <td>
                        <input type="password" name="passwd" id="EApwd" maxlength="15">
                    </td>
                    <td>
                        <img onmouseover="seetext(EApwd)" onmouseout="seeasterisk(EApwd)" src="<?= $root; ?>/assets/img/eye-16-16.png" alt="Voir mot de passe" width="16" height="16">
                    </td>
                </tr>
                <tr>
                    <td colspan="3">
                        <input type="checkbox" name="saved" value="yes">Mémoriser le mot de passe quelques jours.
                    </td>
                </tr>
                <tr>
                    <td></td>
                    <td colspan="2">
                        <button type="submit">Me connecter</button>
                    </td>
                </tr>
            </table>
        </form>

        <p><a href="<?= $root; ?>/acces.php">Voir les conditions d'accès à la partie privée du site</a></p>
        <p><a href="<?= $root; ?>/renvoilogin.php">Login ou mot de passe perdu ?</a></p>
        <?php if ($config->get('USER_AUTO_DEF') > 0) {
            if ($config->get('USER_AUTO_DEF') == 1) {
                $mescpte = "Demander ici la création d'un compte d'utilisateur";
            } else {
                $mescpte = "Créer ici votre compte d'utilisateur";
            } ?>
            <p>
                <a href="<?= $root; ?>/cree_compte.php"><b>Pas encore inscrit ? <?= $mescpte; ?></b></a>
            </p>
        <?php } ?>
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
