<?php

use Symfony\Component\HttpFoundation\RedirectResponse;

require(__DIR__ . '/../next/bootstrap.php');
require(__DIR__ . '/../next/_COMMUN_env.inc.php'); // Compatibility only

if (!$userAuthorizer->isGranted(8)) {
    $response = new RedirectResponse("$root/admin/");
    $response->send();
    exit();
}

$xcomm = $xpatr = $page = "";
pathroot($root, $path, $xcomm, $xpatr, $page);

$missingargs = true;

ob_start();
open_page("Test e-mail", $root);
navadmin($root, "Test du mail");
zone_menu(10, $session->get('user')['level'], array());//ADMIN STANDARD
echo '<div id="col_main_adm">';
echo "<h1>Test de l'envoi d'un mail</h1> \n";

if (getparam('action') == 'submitted') {
    $dest = getparam('email');
    if(empty($dest)) {
        msg("Vous devez préciser votre adresse email");
    } else {
        $missingargs = false;
        $sender = mail_encode($config->get('SITENAME')) . ' <' . $config->get('LOC_MAIL') . ">";
        $okmail = sendmail($sender, $dest, 'Test messagerie de ' . $config->get('SITENAME'), 'Ce message de test a été envoyé via ExpoActes');
        if ($okmail) {
            echo "<p>Un mail de test vous a été envoyé. Vérifiez qu'il vous est bien parvenu.</p>";
        } else {
            echo "<p>La fonction mail n'a pas pu être vérifée.<br />";
            echo "<b>La consultation des actes peut très bien fonctionner sans mail</b> mais plusieurs fonctions de gestion des utilisateurs ne fonctionneront pas.";
        }
        echo '<p><a href="gest_params.php?grp=Mail">Retour au module de paramétrage</a></p>';
    }
}

if($missingargs) {
    echo "<h3>Cette procédure ne peut envoyer qu'un mail de test !</h3> \n";
    echo "<p>Le texte du mail est donc fixe.</p> \n";
    echo '<form method="post"  action="">' . "\n";
    echo '<table cellspacing="0" cellpadding="1" border="0">' . "\n";

    echo " <tr>\n";
    echo "  <td align=right>Votre adresse email : </td>\n";
    echo '  <td><input type="text" name="email" size=40 value="' . $config->get('LOC_MAIL') . '"></td>';
    echo " </tr>\n";

    echo " <tr><td colspan=\"2\" align=\"center\">\n<br>";
    echo '  <input type="hidden" name="action" value="submitted">';
    echo '  <input type="reset" value="Effacer">' . "\n";
    echo ' &nbsp; <input type="submit" value=" *** ENVOYER *** ">' . "\n";
    echo " </td></tr>\n";
    echo "</table>\n";
    echo "</form>\n";
}
echo '</div>';
include(__DIR__ . '/../templates/front/_footer.php');
$response->setContent(ob_get_clean());
$response->send();
