<?php
define('ADM', 0); // Compatibility only
$admtxt = ''; // Compatibility only
require(__DIR__ . '/next/bootstrap.php');
require(__DIR__ . '/next/_COMMUN_env.inc.php'); // Compatibility only

global $loc_mail;


pathroot($root, $path, $xcomm, $xpatr, $page);

$missingargs = true;
$nom   = getparam('nom');
$prenom = getparam('prenom');
$lelogin = getparam('lelogin');
$lepassw = getparam('lepassw');
$leid    = getparam('id');
$email   = getparam('email');
$emailverif = getparam('emailverif');
$libre   = getparam('libre');
$accept  = getparam('acceptcond');
$ok = false;

ob_start();
open_page("Créer mon compte utilisateur", $root);
navigation($root, 2, "", "Création de mon compte");
zone_menu(0, 0, array('f' => 'N')); //PUBLIC SANS FORM_RECHERCHE
?>
<div id="col_main">

<?php if ($config->get('USER_AUTO_DEF') == 0) { ?>
    <p><b>Désolé : Cette action n'est pas autorisée sur ce site</b></p>
    <p>Vous devez contacter le gestionnaire du site pour demander un compte utilisateur</p>
    </div>
    <?php include(__DIR__ . '/templates/front/_footer.php');
    $response->setContent(ob_get_clean());
    $response->send();
    exit();
}

// Données postées -> ajouter ou modifier
if (getparam('action') == 'submitted') {
    $ok = true;
    if (!isset($_REQUEST['nom'])) {
        msg('Vous devez préciser votre nom de famille');
        $ok = false;
    }
    if (!isset($_REQUEST['prenom'])) {
        msg('Vous devez préciser votre prénom');
        $ok = false;
    }
    if (empty($email) or isin($email, '@') == -1 or isin($email, '.') == -1) {
        msg("Vous devez préciser une adresse email valide");
        $ok = false;
    }
    if ($email <> getparam('emailverif')) {
        msg('Les deux copies de l\'adresse e-mail ne sont pas identiques');
        $ok = false;
    }
    $zonelibre = $config->get('USER_ZONE_LIBRE');
    if (!empty($zonelibre) and strlen($libre) < 2) {
        msg('Vous devez compléter la zone [' . $zonelibre . ']');
        $ok = false;
    }
    $txtconduse = $config->get('TXT_CONDIT_USAGE');
    if (!empty($txtconduse) and strlen($accept) == 0) {
        msg("Vous devez marquer votre accord sur les conditions d'utilisation");
        $ok = false;
    }
    if (!(sans_quote($lelogin) and sans_quote($lepassw))) {
        msg('Vous ne pouvez pas mettre d\'apostrophe dans le LOGIN ou le MOT DE PASSE');
        $ok = false;
    }
    if (strlen($lelogin) < 3 or strlen($lelogin) > 15) {
        msg('Vous devez donner un LOGIN d\'au moins 3 et au plus 15 caractères');
        $ok = false;
    }
    if (strlen($lepassw) < 6 or strlen($lepassw) > 15) {
        msg('Vous devez donner un MOT DE PASSE d\'au moins 6 et au plus 15 caractères');
        $ok = false;
    }
    if ($lepassw <> getparam('passwverif')) {
        msg('Les deux copies du MOT DE PASSE ne sont pas identiques');
        $ok = false;
    }
    if ($config->get('AUTO_CAPTCHA') and function_exists('imagettftext')) {
        if (md5(getparam('captcha')) != $_SESSION['valeur_image']) {
            msg('Attention à bien recopier le code dissimulé dans l\'image !');
            $ok = false;
        }
    }
    $pw = $lepassw;
    $res = EA_sql_query("SELECT * FROM " . $config->get('EA_UDB') . "_user3 WHERE login='" . sql_quote($lelogin) . "'", $u_db);
    if (EA_sql_num_rows($res) != 0) {
        $row = EA_sql_fetch_array($res);
        msg('Ce code de login est déjà utilisé par un autre utilisateur, choissisez-en un autre.');
        $ok = false;
    }
    $res = EA_sql_query("SELECT * FROM " . $config->get('EA_UDB') . "_user3 WHERE email='" . sql_quote($email) . "'", $u_db);
    if (EA_sql_num_rows($res) != 0) {
        $row = EA_sql_fetch_array($res);
        msg('Cette adresse mail possède déjà un code de login, utilisez-en une autre ou faite vous renvoyer votre mot de passe.');
        $ok = false;
    }
    if ($ok) {
        $missingargs = false;
        $clevalid = MakeRandomPassword(15);
        $dtexpir = dt_expiration_defaut();
        $mes = "";
        $maj_solde = date("Y-m-d");
        $reqmaj = "INSERT INTO " . $config->get('EA_UDB') . "_user3 "
            . "(nom, prenom, email, level, login, hashpass, regime, solde, maj_solde, statut, dtcreation, dtexpiration, pt_conso, libre, rem)"
            . " VALUES('"
            . sql_quote(getparam('nom')) . "','"
            . sql_quote(getparam('prenom')) . "','"
            . sql_quote($email) . "','"
            . sql_quote($config->get('USER_AUTO_LEVEL')) . "','"  // level
            . sql_quote($lelogin) . "','"
            . sql_quote(sha1($pw)) . "','"
            . sql_quote($config->get('GEST_POINTS')) . "','"  // regime
            . sql_quote($config->get('PTS_PAR_PER')) . "','"  // solde courant
            . sql_quote($maj_solde) . "','"   // date maj du solde
            . sql_quote('W') . "','"          // statut : toujours attendre validation de l'email (W)
            . sql_quote($maj_solde) . "','"   // dtcreation
            . sql_quote($dtexpir) . "','"    // dtexpiration
            . sql_quote('0') . "','"          // pt déjà consommés
            . sql_quote($libre) . "','"       // zone libre (si utilisée)
            . sql_quote($clevalid) . "')";    // Clé pour la validation du compte email dans REM

        //echo "<p>".$reqmaj."</p>";

        if ($result = EA_sql_query($reqmaj, $u_db)) {
            // echo '<p>'.EA_sql_error().'<br />'.$reqmaj.'</p>';
            $log = "Créat. auto user";
            $crlf = chr(10) . chr(13);
            $message = $config->get('MAIL_AUTOUSER');
            if ($config->get('USER_AUTO_DEF') == 1) {
                $message = $config->get('MAIL_VALIDUSER');
            }

            $urlvalid = $config->get('EA_URL_SITE') . $root . "/activer_compte.php?login=" . $lelogin . "&amp;key=" . $clevalid . $crlf . $crlf;
            $urlsite = $config->get('EA_URL_SITE') . $root . "/index.php";
            $codes = array("#NOMSITE#", "#URLSITE#", "#LOGIN#", "#PASSW#", "#NOM#", "#PRENOM#", "#URLVALID#", "#KEYVALID#");
            $decodes = array($config->get('SITENAME'), $urlsite, $lelogin, $pw, getparam('nom'), getparam('prenom'), $urlvalid, $clevalid);
            $bon_message = str_replace($codes, $decodes, $message);
            $sujet = "Votre compte " . $config->get('SITENAME');
            $sender = mail_encode($config->get('SITENAME')) . ' <' . $config->get('LOC_MAIL') . ">";
            $okmail = sendmail($sender, $email, $sujet, $bon_message);
            if ($okmail) {
                $log .= " + mail";
                $mes = " et un mail vous a été envoyé pour l'ACTIVER.";
                $mes .= "<br />Si vous ne recevez pas de mail, merci d'en avertir l'administrateur afin qu'il active votre compte.";
            } else {
                $log .= " NO mail";
                $mes = " mais le mail n'a pas pu être envoyé : contactez l'administrateur du site pour le faire activer.";
            }

            writelog($log, $lelogin, 0);
            echo '<p><b>Votre compte a été créé' . $mes . '</b></p>';
            $id = 0;
        } else {
            echo ' -> Erreur : ';
            echo '<p>' . EA_sql_error() . '<br />' . $reqmaj . '</p>';
        }
    }
}

//Si pas tout les arguments nécessaire, on affiche le formulaire
if (!$ok) {
    $id = -1;
    $action = 'Ajout';
?>
    <h2>Création de mon compte d'utilisateur</h2>
    <form method="post">
        <table cellspacing="0" cellpadding="1" summary="Formulaire">
            <tr>
                <td>Nom : </td>
                <td><input type="text" size="30" name="nom" value="<?= $nom; ?>"></td>
            </tr>
            <tr>
                <td>Prénom : </td>
                <td><input type="text" name="prenom" size="30" value="<?= $prenom; ?>"></td>
            </tr>
            <?php if (!empty($config->get('USER_ZONE_LIBRE'))) { ?>
                <tr>
                    <td><?= $config->get('USER_ZONE_LIBRE'); ?> : </td>
                    <td><input type="text" name="libre" size="50" value="<?= $libre; ?>"></td>
                </tr>
            <?php } ?>
            <tr>
                <td>E-mail : </td>
                <td><input type="email" name="email" size="50" value="<?= $email; ?>"></td>
            </tr>
            <tr>
                <td>E-mail (vérification) : </td>
                <td><input type="email" name="emailverif" size="50" value="<?= $emailverif; ?>"></td>
            </tr>
            <tr>
                <td>Login : </td>
                <td><input type="text" name="lelogin" size="15" maxlength="15" value="<?= $lelogin; ?>"></td>
            </tr>
            <tr>
                <td>Mot de passe : </td>
                <td><input type="password" name="lepassw" size="15" maxlength="15" value="<?= $lepassw; ?>"></td>
            </tr>
            <tr>
                <td>Mot de passe (vérification) : </td>
                <td>
                    <input type="password" name="passwverif" size="15" maxlength="15" value="<?= getparam('passwverif'); ?>">
                </td>
            </tr>
            <?php if ($config->get('TXT_CONDIT_USAGE') <> "") { ?>
                <tr>
                    <td>Conditions d'utilisation : </td>
                    <td>
                        <textarea name="captcha" cols="60" rows="10" readonly><?= $config->get('TXT_CONDIT_USAGE'); ?></textarea>
                        <input type="checkbox" name="acceptcond">J'ai lu et j'accepte les conditions ci-dessus.</input>
                    </td>
                </tr>
            <?php } ?>
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
                    <button type="submit">Inscrivez-moi</button>
                </td>
            </tr>
        </table>
        <input type="hidden" name="id" value="<?= $id; ?>">
        <input type="hidden" name="action" value="submitted">
    </form>
<?php } else { ?>
    <p>
        <a href="<?= $root; ?>/">Retour à la page d'accueil</a>
    </p>
<?php } ?>
</div>
<?php include(__DIR__ . '/templates/front/_footer.php');
$response->setContent(ob_get_clean());
$response->send();
