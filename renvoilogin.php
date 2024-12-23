<?php
define('ADM', 0); // Compatibility only
$admtxt = ''; // Compatibility only
require(__DIR__ . '/next/bootstrap.php');
require(__DIR__ . '/next/_COMMUN_env.inc.php'); // Compatibility only

$xcomm = $xpatr = $page = "";
pathroot($root, $path, $xcomm, $xpatr, $page);

$uri = getparam('uri', $root . '/login.php');
$ok = false;

ob_start();
open_page("Renvoi codes d'accès", $root); ?>
<div class="main">
    <?php zone_menu(0, 0); ?>
    <div class="main-col-center text-center">
        <?php navigation($root, 2, "R", "Renvoi des codes d'accès"); ?>
        <?php if (getparam('submit') <> '') {
            if (getparam('email') == "") {
                msg('Vous devez fournir votre adresse email');
            } else {
                $missingargs = false;
                $sql = "SELECT nom, prenom,login,email,level FROM " . $config->get('EA_UDB') . "_user3 WHERE email = '" . getparam('email') . "'; ";
                $result = EA_sql_query($sql, $u_db);
                $nb = EA_sql_num_rows($result);
                if ($nb == 1) {
                    $user = EA_sql_fetch_array($result);
                    $userlevel = $user["level"];
                    $pw = MakeRandomPassword(8);
                    $hash = sha1($pw);
                    $reqmaj = "UPDATE " . $config->get('EA_UDB') . "_user3 SET HASHPASS = '" . $hash . "' " .
                        " WHERE email = '" . getparam('email') . "'; ";

                    //echo "<p>" . $reqmaj . "</p>";

                    if ($result = EA_sql_query($reqmaj, $u_db)) {
                        // echo '<p>' . EA_sql_error() . '<br>' . $reqmaj . '</p>';
                        echo '<p><b>Mot de passe réinitialisé.</b></p>';
                    } else {
                        echo ' -> Erreur : ';
                        echo '<p>' . EA_sql_error() . '<br>' . $reqmaj . '</p>';
                    }

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
                        $message .= "Après connexion, vous pourrez changer ce mot de passe pour un mot plus facile à retenir." . $lb;
                        $message .= "" . $lb;
                    }
                    $message .= "Cordialement," . $lb;
                    $message .= "" . $lb;
                    $message .= "Votre webmestre." . $lb;

                    $sujet = "Rappel de vos codes pour " . $config->get('SITENAME');
                    $sender = mail_encode($config->get('SITENAME')) . ' <' . $config->get('LOC_MAIL') . ">";
                    $okmail = sendmail($sender, $user['email'], $sujet, $message);
                    if (!$okmail) {
                        msg('Désolé, problème lors de l\'envoi du mail ! - Contactez <a href=mailto:' . $config->get('LOC_MAIL') . '>l\'administrateur.</a>');
                        $ok = false;
                    } else {
                        echo "<p>Courrier envoyé.<br />Consultez votre messagerie pour récupérer vos codes d'accès.<p>";
                        writelog('Renvoi login/password', $user['login'], 0);
                        $ok = true;
                        echo '<p><a href="' . $root . '/login.php"><b>Vous connecter à nouveau</b></a></p>';
                    }
                } else {
                    if ($nb > 1) {
                        msg('Cette adresse email est référencée pour plusieurs comptes. Contactez <a href=mailto:' . $config->get('LOC_MAIL') . '>l\'administrateur.</a>');
                    } else {
                        msg('Cette adresse email n\'est pas connue !');
                    }
                    $ok = false;
                }
            }
        }

        if (!$ok) { ?>
            <h2>Renvoi des codes d'accès au site</h2>
            <p>Vos codes d'accès peuvent vous être renvoyés à l'adresse mail associée à votre compte d'utilisateur</p>
            <form id="log" method="post">
                <table class="m-auto" summary="Formulaire">
                    <tr>
                        <td>Adresse e-mail : </td>
                        <td><input name="email" /></td>
                    </tr>
                    <tr>
                        <td></td>
                        <td>
                            <button type="submit" name="submit">Envoyer</button>
                        </td>
                    </tr>
                </table>
            </form>
            <p>
                <a href="<?= $root; ?>/acces.php">Voir les conditions d'accès à la partie privée du site</a>
            </p>
        <?php } ?>
    </div>
</div>
<?php include(__DIR__ . '/templates/front/_footer.php');
$response->setContent(ob_get_clean());
$response->send();
