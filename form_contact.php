<?php
define('ADM', 0); // Compatibility only
$admtxt = ''; // Compatibility only
require(__DIR__ . '/next/bootstrap.php');
require(__DIR__ . '/next/_COMMUN_env.inc.php'); // Compatibility only

//global $loc_mail;

pathroot($root, $path, $xcomm, $xpatr, $page);

$missingargs = true;
$nompre = getparam('nompre');
$txtmsg = getparam('txtmsg');
$email = getparam('email');
$sweb  = htmlentities(getparam('sweb'), ENTITY_REPLACE_FLAGS, ENTITY_CHARSET);
$objet = getparam('objet');
$ok = false;

ob_start();
open_page("Formulaire de contact", $root);
navigation($root, 2, "", "Formulaire de contact");
zone_menu(0, 0, array('f' => 'N')); //PUBLIC SANS FORM_RECHERCHE

echo '<div id="col_main">';

// Données postées -> ajouter ou modifier
if (getparam('action') == 'submitted') {
    $ok = true;
    if (empty($nompre)) {
        msg('Merci de préciser vos nom et prenom');
        $ok = false;
    }
    if (empty($email) or isin($email, '@') == -1 or isin($email, '.') == -1) {
        msg("Vous devez préciser une adresse email valide");
        $ok = false;
    }
    if (strlen($txtmsg) < 10) {
        msg('Vous devez donner un message');
        $ok = false;
    }
    if (strlen($objet) < 2) {
        msg('Vous devez donner un objet');
        $ok = false;
    }
    if ($config->get('AUTO_CAPTCHA') and function_exists('imagettftext')) {
        if (md5(getparam('captcha')) != $_SESSION['valeur_image']) {
            msg('Attention à bien recopier le code dissimulé dans l\'image !');
            $ok = false;
        }
    }
    if ($ok) {
        $missingargs = false;
        $mes = "";
        $log = "Contact";
        $crlf = chr(10) . chr(13);

        $lemessage = "Message envoyé par " . $nompre . " (" . $email . ") via " . $config->get('SITENAME') . $crlf . $crlf;
        if ($sweb <> "") {
            $lemessage .= "Site web : " . $sweb . " " . $crlf . $crlf;
        }
        $lemessage .= $txtmsg . $crlf . $crlf;

        //echo "<p>MES = " . $lemessage . "<p>";

        $sujet = $objet;
        $sender = mail_encode($nompre) . ' <' . $email . ">";
        $okmail = sendmail($sender, $config->get('EMAIL_CONTACT'), $sujet, $lemessage);
        if ($okmail) {
            $mes = "Un mail a été envoyé à l'administrateur.";
        } else {
            $mes = "ERREUR : Le mail n'a pas pu être envoyé ! <br />Merci de contactez directement l'administrateur du site à l'adresse " . $config->get('EMAIL_CONTACT');
        }
        //writelog($log,$nompre,1);
        echo '<p><b>' . $mes . '</b></p>';
        $id = 0;
    }
}

//Si pas tout les arguments nécessaire, on affiche le formulaire
if (!$ok) { ?>
    <h2>Formulaire de contact</h2>
    <form method="post">
        <table cellspacing="0" cellpadding="1" summary="Formulaire">
            <tr>
                <td>Vos nom et prénom : </td>
                <td><input type="text" size="50" name="nompre" value="<?= $nompre; ?>"></td>
            </tr>
            <tr>
                <td>Votre e-mail : </td>
                <td><input type="text" name="email" size="50" value="<?= $email; ?>"></td>
            </tr>
            <tr>
                <td>Votre site web : </td>
                <td><input type="text" name="sweb" size="50" value="<?= $sweb; ?>"></td>
            </tr>
            <tr>
                <td colspan="2">Sujet :
                    <input type="text" name="objet" size="80" value="<?= $objet; ?>">
                </td>
            </tr>
            <tr>
                <td colspan="2">Votre message : <br>
                    <textarea name="txtmsg" cols="80" rows="12"><?= $txtmsg; ?></textarea>
                </td>
            </tr>
            <?php if ($config->get('AUTO_CAPTCHA') && function_exists('imagettftext')) { ?>
                <tr>
                    <td><img src="<?= $root; ?>/tools/captchas/image.php" alt="captcha" id="captcha"></td>
                    <td>
                        Recopiez le code ci-contre : <br>
                        <input type="text" name="captcha" size="6" maxlength="5" value="">
                    </td>
                </tr>
            <?php } ?>
            <tr>
                <td></td>
                <td>
                    <button type="reset">Effacer</button>
                    <button type="submit">Envoyer</button>
                </td>
            </tr>
        </table>
        <input type="hidden" name="action" value="submitted">
    </form>
<?php } else { ?>
    <p><b>Nous vous répondrons dès que possible.</b></p>
<?php } ?>
</div>
<?php include(__DIR__ . '/templates/front/_footer.php');
$response->setContent(ob_get_clean());
$response->send();
