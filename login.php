<?php
define('ADM', 0); // Compatibility only
$admtxt = ''; // Compatibility only
require(__DIR__ . '/next/bootstrap.php');
require(__DIR__ . '/next/_COMMUN_env.inc.php'); // Compatibility only

$xcomm = "";
$xpatr = "";
$page = "";
$uri = getparam('uri', $root . '/');
$motif = getparam('cas');

pathroot($root, $path, $xcomm, $xpatr, $page);

ob_start();
open_page("ExpoActes : Login", $root, null, null, null, '../index.htm');
navigation($root, 2, 'A', "Connexion");
zone_menu(0, 0);
?>
<div id="col_main">

<?php if ($motif == 1) {
    msg('Login ou mot de passe incorrect (vérifiez Majuscules/minuscules) !');
}
if ($motif == 2) {
    msg("L'accès à la page que vous voulez consulter est réservé");
}
if ($motif == 3) {
    msg('Vos droits sont insuffisants pour accéder à cette page');
}
if ($motif == 4) {
    msg("Vous devez vous reconnecter avec le nouveau mot de passe");
}
if ($motif == 5) {
    msg("Votre compte doit encore être activé et/ou approuvé");
}
if ($motif == 6) {
    msg("Votre compte a expiré. Contactez l'administrateur pour le réactiver");
}
if ($motif == 7) {
    msg("Votre compte est bloqué. Contactez l'administrateur");
}
?>

<h2>Vous devez vous identifier : </h2>

<form method="post" action="<?= $uri; ?>">
    <table>
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
    <input type="hidden" name="codedpass" value="">
    <input type="hidden" name="iscoded" value="N">
</form>

<p><a href="<?= $root; ?>/acces.php">Voir les conditions d'accès à la partie privée du site</a></p>
<p><a href="<?= $root; ?>/renvoilogin.php">Login ou mot de passe perdu ?</a></p>
<?php if (USER_AUTO_DEF > 0) {
    if (USER_AUTO_DEF == 1) {
        $mescpte = "Demander ici la création d'un compte d'utilisateur";
    } else {
        $mescpte = "Créer ici votre compte d'utilisateur";
    } ?>
    <p>
        <a href="<?= $root; ?>/cree_compte.php"><b>Pas encore inscrit ? <?= $mescpte; ?></b></a>
    </p>
<?php } ?>
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
