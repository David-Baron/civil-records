<?php

use CivilRecords\Model\UserModel;
use CivilRecords\Engine\MailerFactory;
use Symfony\Component\HttpFoundation\RedirectResponse;

if (!$userAuthorizer->isGranted(9)) {
    $response = new RedirectResponse("$root/admin/");
    $response->send();
    exit();
}

if ($request->get('id', null) === null) {
    $response = new RedirectResponse("$root/admin/");
    $response->send();
    exit();
}

$id  = $request->get('id', 0);

$sendmail = 1;
$menu_user_active = 'A';

/**
 * @deprecated
 */
/* if (!$id) {
    $action = 'Ajout';
    $user = [
        'nom'   => '',
        'prenom' => '',
        'email' => '',
        'login' => '',
        'passw' => '',
        'level' => 1,
        'regime' => $config->get('GEST_POINTS'),
        'solde'  => $config->get('PTS_PAR_PER'),
        'maj_solde' => today(),
        'statut' => 'N',
        'dtcreation' => today(),
        'pt_conso' => 0,
        'REM'   => '',
        'libre' => '',
    ];
} */

// $action = 'Modification';
$userModel = new UserModel();
$user = $userModel->findId($id);

// Données postées -> ajouter ou modifier
if ($request->getMethod() === 'POST') {
    dd($request->request->all());
    if ($result = EA_sql_query($reqmaj, $u_db)) {
        if ($id <= 0) {
            $log = "Ajout utilisateur";
            if ($sendmail == 1) {
                $from = $config->get('SITENAME') . ' <' . $_ENV['EMAIL_SITE'] . ">";
                $to = $mail;
                $subject = "Votre compte " . $config->get('SITENAME');
                $mailerFactory = new MailerFactory();
                $mail = $mailerFactory->createEmail($from, $to, $subject, 'new_account_created.php', [
                    'sitename' => $config->get('SITENAME'),
                    'urlsite' => $config->get('URL_SITE'),
                    'user' => ['nom' => $request->request->get('nom'), 'prenom' => $request->request->get('prenom'), 'login' => $lelogin],
                    'plain_text_password' => $pw
                ]);
                $mailerFactory->send($mail);
            } else {
                $okmail = false;
            }
            if ($okmail) {
                $log .= " + mail";
                $mes = " et mail envoyé";
            } else {
                $mes = " et mail PAS envoyé";
            }

            writelog($log, $lelogin, 0);
        } else {
            writelog('Modification utilisateur ', $lelogin, 0);
        }
        echo '<p><b>Fiche enregistrée' . $mes . '.</b></p>';
        $id = 0;
    } else {
        echo ' -> Erreur : ';
        echo '<p>' . EA_sql_error() . '<br />' . $reqmaj . '</p>';
    }
}

ob_start();
open_page("Gestion des utilisateurs", $root); ?>
<div class="main">
    <?php zone_menu(10, $session->get('user')['level']); ?>
    <div class="main-col-center text-center">
        <?php navadmin($root, "Gestion des utilisateurs");
        require(__DIR__ . '/../templates/admin/_menu-user.php'); ?>

        <h2>Modification d'une fiche d'utilisateur</h2>
        <form method="post" id="fiche" name="eaform">
            <table class="m-auto" summary="Formulaire">
                <tr>
                    <td>Nom : </td>
                    <td><?= $user['nom']; ?></td>
                </tr>
                <tr>
                    <td>Prénom : </td>
                    <td><?= $user['prenom']; ?></td>
                </tr>
                <tr>
                    <td>Login : </td>
                    <td><?= $user['login']; ?></td>
                </tr>
                <tr>
                    <td>E-mail : </td>
                    <td><?= $user['email']; ?></td>
                </tr>
                <tr>
                    <td>Date entrée : </td>
                    <td>
                        <?php if ($user['dtcreation'] != null) {
                            echo showdate($user['dtcreation'], 'S');
                        } else {
                            echo '- Inconnue -';
                        } ?>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">&emsp;</td>
                </tr>
                <tr>
                    <td>Statut : </td>
                    <td>
                        <?php lb_statut_user($user['statut']); ?>
                        <?php if ($config->get('USER_AUTO_DEF') == 1 && ($user['statut'] == "A" || $user['statut'] == "W")) { ?>
                            <a href="<?= $root; ?>/admin/utilisateurs/approuver_compte?id=<?= $id; ?>&action=OK">Approuver</a>
                            ou <a href="<?= $root; ?>/admin/utilisateurs/approuver_compte?id=<?= $id; ?>&action=KO">Refuser</a>
                        <?php } ?>
                    </td>
                </tr>
                <tr>
                    <td>Droits d'accès : </td>
                    <td>
                        <?php lb_droits_user($user['level']); ?>
                    </td>
                </tr>
                <?php if ($config->get('GEST_POINTS') > 0) { ?>
                    <tr>
                        <td colspan="2">&emsp;</td>
                    </tr>
                    <tr>
                        <td>Régime (points) : </td>
                        <td><?php lb_regime_user($user['regime']); ?></td>
                    </tr>
                    <tr>
                        <td>Solde de points : </td>
                        <td>
                            <input type="text" name="solde" size="5" value="<?= $user['solde']; ?>">
                            <input type="hidden" name="soldepre" value="<?= $user['solde']; ?>">
                        </td>
                    </tr>
                    <tr>
                        <td>Dernière recharge : </td>
                        <td><?= date("d-m-Y", strtotime($user['maj_solde'])); ?></td>
                    </tr>
                    <tr>
                        <td>Points consommés : </td>
                        <td><?= $user['pt_conso']; ?></td>
                    </tr>
                    <tr>
                        <td colspan="2">&emsp;</td>
                    </tr>
                <?php } ?>
                <tr>
                    <td colspan="2">&emsp;</td>
                </tr>
                <tr>
                    <td></td>
                    <td><input type="checkbox" name="SendMail" value="1" <?= ($sendmail == 1 ? ' checked' : ''); ?>>Envoi automatique du mail de modification</td>
                </tr>
                <tr>
                    <td></td>
                    <td>
                        <button type="reset" class="btn">Effacer</button>
                        <button type="submit" class="btn">Enregistrer</button>
                        <a href="<?= $root; ?>/admin/aide/gestuser.html" class="btn" target="_blank">Aide</a>
                    </td>
                </tr>
            </table>
        </form>
    </div>
</div>
<?php include(__DIR__ . '/../templates/front/_footer.php');
return (ob_get_clean());
