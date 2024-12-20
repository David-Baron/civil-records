<?php
define('ADM', 0); // Compatibility only
$admtxt = ''; // Compatibility only
require(__DIR__ . '/next/bootstrap.php');
require(__DIR__ . '/next/_COMMUN_env.inc.php'); // Compatibility only

pathroot($root, $path, $xcomm, $xpatr, $page);

$missingargs = true;

ob_start();
open_page("Activer mon compte utilisateur", $root); ?>
<div class="main">
    <?php zone_menu(0, 0); ?>
    <div class="main-col-center text-center">
        <?php navigation($root, 2, "", "Activation de mon compte"); ?>

        <?php if ($config->get('USER_AUTO_DEF') == 0) {
            echo "<p><b>Désolé : Cette action n'est pas autorisée sur ce site</b></p>";
            echo "<p>Vous devez contacter le gestionnaire du site pour demander un compte utilisateur</p>";
            echo '</div>';
            include(__DIR__ . '/templates/front/_footer.php');
            $response->setContent(ob_get_clean());
            $response->send();
            exit();
        }

        // Données postées -> ajouter ou modifier
        $ok = true;
        if (empty($_REQUEST['login'])) {
            msg('Vous devez préciser le login');
            $ok = false;
        }
        if (empty($_REQUEST['key'])) {
            msg('Vous devez inscrire la clé qui vous été envoyée par mail');
            $ok = false;
        }
        $res = EA_sql_query("SELECT * FROM " . $config->get('EA_UDB') . "_user3 WHERE login='" . sql_quote($_REQUEST['login'])
            . "' and  rem='" . sql_quote($_REQUEST['key'])
            . "' and  statut='W'", $u_db);
        if (EA_sql_num_rows($res) != 1) {
            msg('Pas/Plus de compte à activer avec ces valeurs. Vérifiez vos codes.');
            $ok = false;
        }
        if ($ok) {
            $row = EA_sql_fetch_array($res);
            // $login = $row['login'];
            $id = $row['ID'];
            $nomprenom = $row['prenom'] . ' ' . $row['nom'];
            $login = $row['login'];
            $missingargs = false;
            $mes = "";
            $statut = 'N'; // A = attente approbation par admin, N = normal
            if ($config->get('USER_AUTO_DEF') == 1) {
                $statut = 'A';
            }

            $reqmaj = "UPDATE " . $config->get('EA_UDB') . "_user3 SET statut='" . $statut . "', rem=' ' WHERE id=" . $id . ";";
            if ($result = EA_sql_query($reqmaj, $u_db)) {
                $crlf = chr(10) . chr(13);
                $log = "Activation compte";
                if ($config->get('USER_AUTO_DEF') == 1) {
                    $message  = $nomprenom . " (" . $login . ")" . $crlf;
                    $message .= "vient de demander accès au site " . $config->get('SITENAME') . "." . $crlf;
                    $message .= "Vous pouvez APPROUVER cet acces avec le lien suivant : " . $crlf;
                    $message .= $config->get('EA_URL_SITE') . $root . "/admin/approuver_compte.php?id=" . $id . "&action=OK" . $crlf;
                    $message .= "OU " . $crlf;
                    $message .= "Vous pouvez REFUSER cet acces avec le lien suivant : " . $crlf;
                    $message .= $config->get('EA_URL_SITE') . $root . "/admin/approuver_compte.php?id=" . $id . "&action=KO" . $crlf;
                    $sujet = "Approbation acces de " . $nomprenom;
                    $mes = " Votre demande de compte est soumise à l'approbation de l'administrateur.";
                } else {
                    $message  = $nomprenom . " (" . $login . ")" . $crlf;
                    $message .= "vient d'obtenir un accès au site " . $config->get('SITENAME') . "." . $crlf;
                    $sujet = "Validation acces de " . $nomprenom;
                    $mes = " Votre compte est actif et vous pouvez à présent vous connecter.";
                }
                $sender = mail_encode($config->get('SITENAME')) . ' <' . $config->get('LOC_MAIL') . ">";
                $okmail = sendmail($sender, $config->get('LOC_MAIL'), $sujet, $message);
                if ($okmail) {
                    $log .= " + mail";
                } else {
                    $log .= " NO mail";
                }
                writelog($log, $login, 0);
                echo '<p><b>Votre adresse a été vérifiée.<br>' . $mes . '</b></p>';
            } else {
                echo ' -> Erreur : ';
                echo '<p>' . EA_sql_error() . '<br>' . $reqmaj . '</p>';
            }
        }


        //Si pas tout les arguments nécessaire, on affiche le formulaire
        if (!$ok) {
            $id = -1;
            $action = 'Ajout';
            $login = $_REQUEST['login'];
            $key   = $_REQUEST['key'];
        ?>
            <h2>Activation de mon compte d'utilisateur</h2>
            <form method="post">
                <table>
                    <tr>
                        <td>Login : </td>
                        <td><input type="text" size="30" name="login" value="<?= $login; ?>"></td>
                    </tr>

                    <tr>
                        <td>Clé d'activation : </td>
                        <td><input type="text" name="key" size="30" value="<?= $key; ?>"></td>
                    </tr>
                    <tr>
                        <td></td>
                        <td>
                            <button type="reset">Effacer</button>
                            <button type="submit">Activer le compte</button>
                        </td>
                    </tr>
                </table>
            </form>
        <?php } else { ?>
            <p>
                <a href="<?= $root; ?>/">Retour à la page d'accueil</a>
            </p>
        <?php } ?>
    </div>
</div>
<?php include(__DIR__ . '/templates/front/_footer.php');
$response->setContent(ob_get_clean());
$response->send();
