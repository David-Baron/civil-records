<?php
define('ADM', 0); // Compatibility only
$admtxt = ''; // Compatibility only
require(__DIR__ . '/next/bootstrap.php');
require(__DIR__ . '/next/_COMMUN_env.inc.php'); // Compatibility only

$userlogin = "";
$userlevel = logonok(CHANGE_PW);
while ($userlevel < CHANGE_PW) {
    login($root);
}

pathroot($root, $path, $xcomm, $xpatr, $page);

$act = getparam('act');
$missingargs = true;
$userid = current_user("ID");

ob_start();
open_page("Changement de mot de passe", $root);
navigation($root, 2, 'A', "Changement de mot de passe");
zone_menu(0, 0, array('s' => '', 'c' => 'O')); //PUBLIC STAT & CERT
?>
<div id="col_main_adm">

<?php if ($act == "relogin") {
    echo '<p align="center"><a href="' . $root . '/index.php">Retour à la page d\'accueil</a></p>';
    echo '</div>';
    include(__DIR__ . '/templates/front/_footer.php');
    $response->setContent(ob_get_clean());
    $response->send();
    exit;
}

if (getparam('action') == 'submitted') {
    $ok = true;
    if (getparam('iscoded') == "N") {
        // Mot de passe transmis en clair
        if (strlen(getparam('passw')) < 6) {
            msg('Vous devez donner un nouveau MOT DE PASSE d\'au moins 6 caractères');
            $ok = false;
        }
        if (getparam('passw') <> getparam('passwverif')) {
            msg('Les deux copies du nouveau MOT DE PASSE ne sont pas identiques');
            $ok = false;
        }
        if (!(sans_quote(getparam('passw')))) {
            msg('Vous ne pouvez pas mettre d\'apostrophe dans le MOT DE PASSE');
            $ok = false;
        }
        $codedpass = sha1(getparam('passw'));
        $codedoldpass = sha1(getparam('oldpassw'));
    } else {
        $codedpass = getparam('codedpass');
        $codedoldpass = getparam('codedoldpass');
    }
    $userpw = current_user("hashpass");
    if ($codedoldpass <> $userpw) {
        msg('Votre ancien mot de passe n\'est pas correct');
        $ok = false;
    }

    if ($ok) {
        $missingargs = false;
        $reqmaj = "UPDATE " . EA_UDB . "_user3 SET hashpass = '" . $codedpass . "' WHERE id=" . $userid . ";";
        if ($result = EA_sql_query($reqmaj, $u_db)) {
            writelog('Modification mot de passe ', $_REQUEST['lelogin'], 0);
            echo '<p><b>MOT DE PASSE MODIFIE.</b></p>';
            // TODO: redirect to login
        } else {
            echo ' -> Erreur : ';
            echo '<p>' . EA_sql_error() . '<br>' . $reqmaj . '</p>';
        }
    }
}

if ($missingargs) { ?>
    <h2>Modification de votre mot de passe</h2>
    <form method="post">
        <table cellspacing="0" cellpadding="1" summary="Formulaire">
            <tr>
                <td>Code utilisateur : </td>
                <td><?= $userlogin; ?></td>
            </tr>
            <tr>
                <td>Ancien mot de passe : </td>
                <td>
                    <input type="password" name="oldpassw" size="15" value="">
                    <img onmouseover="seetext(EAoldpwd)" onmouseout="seeasterisk(EAoldpwd)"
                        src="<?= $root; ?>/assets/img/eye-16-16.png"
                        alt="Voir mot de passe" width="16" height="16">
                </td>
            </tr>
            <tr>
                <td>Nouveau mot de passe : </td>
                <td><input type="password" name="passw" size="15" value="" />
                    <img onmouseover="seetext(EApwd)" onmouseout="seeasterisk(EApwd)"
                        src="<?= $root; ?>/assets/img/eye-16-16.png"
                        alt="Voir mot de passe" width="16" height="16">
                </td>
            </tr>
            <tr>
                <td>Nouveau mot de passe (vérif.) : </td>
                <td><input type="password" name="passwverif" size="15" value="" />
                    <img onmouseover="seetext(EApwdverif)" onmouseout="seeasterisk(EApwdverif)"
                        src="<?= $root; ?>/assets/img/eye-16-16.png"
                        alt="Voir mot de passe" width="16" height="16">
                </td>
            </tr>
            <tr>
                <td></td>
                <td>
                    <button type="reset">Effacer</button>
                    <button type="submit">Modifier</button>
                </td>
            </tr>
        </table>
    </form>
<?php } else { ?>
    <p>
        <a href="<?= $root; ?>/login.php?cas=4">Vous DEVEZ vous reconnecter avec le nouveau mot de passe.</a>
    </p>
<?php } ?>
</div>
<?php include(__DIR__ . '/templates/front/_footer.php');
$response->setContent(ob_get_clean());
$response->send();
