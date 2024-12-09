<?php
define('ADM', 0);
require(__DIR__ . '/tools/_COMMUN_env.inc.php');

$xcomm = $xpatr = $page = "";
pathroot($root, $path, $xcomm, $xpatr, $page);

$uri = getparam('uri');
if ($uri == "") {
    $uri = "login.php";
}
$ok = false;

open_page("ExpoActes : Renvoi codes d'accès", $root, null, null, null, '../index.htm');
navigation($root, 2, "R", "Renvoi des codes d'accès");


zone_menu(0, 0, array('f' => 'N'));//PUBLIC SANS FORM_RECHERCHE
echo '<div id="col_main">' . "\n";

if (getparam('submit') <> '') {
    if (getparam('email') == "") {
        msg('Vous devez fournir votre adresse email');
    } else {
        $missingargs = false;
        $request = "SELECT nom, prenom,login,email,level FROM " . EA_UDB . "_user3 WHERE email = '" . getparam('email') . "'; ";

        $result = EA_sql_query($request, $u_db);
        $nb = EA_sql_num_rows($result);
        if ($nb == 1) {
            $user = EA_sql_fetch_array($result);
            $userlevel = $user["level"];
            $pw = MakeRandomPassword(8);
            $hash = sha1($pw);
            $reqmaj = "UPDATE " . EA_UDB . "_user3 SET HASHPASS = '" . $hash . "' " .
                " WHERE email = '" . getparam('email') . "'; ";

            //echo "<p>" . $reqmaj . "</p>";

            if ($result = EA_sql_query($reqmaj, $u_db)) {
                // echo '<p>' . EA_sql_error() . '<br>' . $reqmaj . '</p>';
                echo '<p><b>Mot de passe réinitialisé.</b></p>';
            } else {
                echo ' -> Erreur : ';
                echo '<p>' . EA_sql_error() . '<br>' . $reqmaj . '</p>';
            }

            //echo '<p>ENVOI DU MAIL à ' . $user['login'];

            $lb        = "\r\n";
            $message  = "Bonjour," . $lb;
            $message .= "" . $lb;
            $message .= "Voici vos codes d'accès au site  :" . $lb;
            $message .= "" . $lb;
            $message .= EA_URL_SITE . $root . "/index.php" . $lb;
            $message .= "" . $lb;
            $message .= "Votre login : " . $user['login'] . $lb;
            $message .= "Votre NOUVEAU mot de passe : " . $pw . $lb;
            $message .= "" . $lb;
            if ($userlevel >= CHANGE_PW) {
                $message .= "Après connexion, vous pourrez changer ce mot de passe pour un mot plus facile à retenir." . $lb;
                $message .= "" . $lb;
            }
            $message .= "Cordialement," . $lb;
            $message .= "" . $lb;
            $message .= "Votre webmestre." . $lb;

            $sujet = "Rappel de vos codes pour " . SITENAME;
            $sender = mail_encode(SITENAME) . ' <' . LOC_MAIL . ">";
            $okmail = sendmail($sender, $user['email'], $sujet, $message);
            if (!$okmail) {
                msg('Désolé, problème lors de l\'envoi du mail ! - Contactez <a href=mailto:' . LOC_MAIL . '>l\'administrateur.</a>');
                $ok = false;
            } else {
                echo "<p>Courrier envoyé.<br />Consultez votre messagerie pour récupérer vos codes d'accès.<p>";
                writelog('Renvoi login/password', $user['login'], 0);
                $ok = true;
                echo '<p><a href="' . $root . '/login.php"><b>Vous connecter à nouveau</b></a></p>';
            }
        } else {
            if ($nb > 1) {
                msg('Cette adresse email est référencée pour plusieurs comptes. Contactez <a href=mailto:' . LOC_MAIL . '>l\'administrateur.</a>');
            } else {
                msg('Cette adresse email n\'est pas connue !');
            }
            $ok = false;
        }
    }
}

if (!$ok) {
    echo "<h2>Renvoi des codes d'accès au site</h2>" . "\n";
    echo '<p>Vos codes d\'accès peuvent vous être renvoyés à l\'adresse mail associée à votre compte d\'utilisateur</p>' . "\n";

    echo '<form id="log" method="post" action="">' . "\n";
    echo '<table align="center" summary="Formulaire">' . "\n";
    echo '<tr><td align="right">Adresse e-mail : </td><td><input name="email" /></td></tr>' . "\n";
    echo '<tr><td colspan="2" align="center"><input type="submit" name="submit" value=" Envoyer " /></td></tr>' . "\n";
    echo '</table>' . "\n";
    echo '</form>' . "\n";

    echoln('<p><a href="' . $root . '/acces.php">Voir les conditions d\'accès à la partie privée du site</a></p>' . "\n");

    echoln('<p>&nbsp;</p>' . "\n");
}
echo '</div>' . "\n";
close_page(1, $root);
