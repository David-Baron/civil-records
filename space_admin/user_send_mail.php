<?php

use CivilRecords\Engine\MailerFactory;
use Symfony\Component\HttpFoundation\RedirectResponse;

require(__DIR__ . '/../tools/traitements.inc.php');

if (!$userAuthorizer->isGranted(9)) {
    $response = new RedirectResponse("$root/admin/");
    $response->send();
    exit();
}

$T0 = time();
$sujet    = '';
$message  = '';
$xdroits  = 10;
$condit   = '0';
$rem = null;
$emailfound = false;
$cptok = 0;
$cptko = 0;
$today = date("Y-m-d", time());

$form_errors = [];
$form_success = false;
$menu_user_active = 'M';

if ($request->getMethod() === 'POST') {
    $condrem = '';
    $condlevel = '';
    $condreg = '';

    $xdroits  = $request->request->get('level');
    $condit   = $request->request->get('condit', null);
    $regime = $request->request->get('regime', null);

    if ($xdroits <> 10) $condlevel = " AND level=" . $xdroits;
    if (null !== $condit) $condrem = " AND " . comparerSQL('REM', $rem, $condit);
    if (null !== $regime) $condreg = " AND regime =" . $regime;

    $sql = "SELECT nom, prenom, email, level, statut FROM " . $config->get('EA_UDB') . "_user3 WHERE (1=1) " . $condlevel . $condreg . $condrem . " ;";
    $users = EA_sql_query($sql, $u_db);
    $nbsites = EA_sql_num_rows($users);
    $nbsend = 0;

    while ($user = EA_sql_fetch_array($users)) {
        if ($user['statut'] == 'N') {
            $from = $config->get('SITENAME') . ' <' . $_ENV['EMAIL_SITE'] . ">";
            $to = $user['email'];
            $subject = $request->request->get('sujet');
            $mailerFactory = new MailerFactory();
            $mail = $mailerFactory->createEmail($from, $to, $subject, 'email_default.php', [
                'sitename' => $config->get('SITENAME'),
                'urlsite' => $config->get('URL_SITE'),
                'message' => $request->request->get('message')
            ]);
            $mailerFactory->send($mail);
            // TODO: will be a streaming response
            echo "<p>Envoi à " . $user['prenom'] . " " . $user['nom'] . " (" . $user['email'] . ") ";
            if (!$okmail) {
                echo ' -> Mail PAS envoyé.';
                $cptko++;
            } else {
                echo ' -> Mail ENVOYE.';
                $cptok++;
            }
        }
    }
    $form_success = true;
}

ob_start();
open_page("Envoi d'un mail circulaire", $root); ?>
<div class="main">
    <?php zone_menu(10, $session->get('user')['level'], array()); ?>
    <div class="main-col-center text-center">
        <?php
        navadmin($root, "Envoi d'un mail circulaire");

        require(__DIR__ . '/../templates/admin/_menu-user.php');
        ?>
        <form method="post" enctype="multipart/form-data">
            <h2>Envoi d'un mail circulaire</h2>
            <table class="m-auto">
                <tr>
                    <td colspan="2"><b>Destinataires</b></td>
                </tr>
                <tr>
                    <td>Droits d'accès : </td>
                    <td>
                        <select name="level" size="1">
                            <option <?= (10 == $xdroits ? 'selected' : ''); ?>> -- Envoi à tous -- </option>
                            <option <?= (0 == $xdroits ? 'selected' : ''); ?>>0 : Public</option>
                            <option <?= (1 == $xdroits ? 'selected' : ''); ?>>1 : Liste des communes</option>
                            <option <?= (2 == $xdroits ? 'selected' : ''); ?>>2 : Liste des patronymes</option>
                            <option <?= (3 == $xdroits ? 'selected' : ''); ?>>3 : Table des actes</option>
                            <option <?= (4 == $xdroits ? 'selected' : ''); ?>>4 : Détails des actes (avec limites)</option>
                            <option <?= (5 == $xdroits ? 'selected' : ''); ?>>5 : Détails sans limitation</option>
                            <option <?= (6 == $xdroits ? 'selected' : ''); ?>>6 : Chargement NIMEGUE et CSV</option>
                            <option <?= (7 == $xdroits ? 'selected' : ''); ?>>7 : Ajout d' actes</option>
                            <option <?= (8 == $xdroits ? ' selected' : ''); ?>>8 : Administration tous actes</option>
                            <option <?= (9 == $xdroits ? 'selected' : ''); ?>>9 : Gestion des utilisateurs</option>
                        </select>
                    </td>
                </tr>
                <?php if ($config->get('GEST_POINTS') > 0) { ?>
                    <tr>
                        <td>ET</td>
                        <td></td>
                    </tr>
                    <tr>
                        <td>Régime (points) : </td>
                        <td>
                            <select name="regime" size="1">
                                <option <?= (0 == $regime ? 'selected' : ''); ?>>Accès libre</option>
                                <option <?= (1 == $regime ? 'selected' : ''); ?>>Recharge manuelle</option>
                                <option <?= (2 == $regime ? 'selected' : ''); ?>>Recharge automatique</option>
                            </select>
                        </td>
                    </tr>
                <?php } else { ?>
                    <tr>
                        <td colspan="2"><input type="hidden" name="regime" value="-1"></td>
                    </tr>
                <?php } ?>
                <tr>
                    <td>Commentaire : </td>
                    <td>
                        <?php listbox_trait('condit', "TST", $condit); ?>
                        <input type="text" name="rem" size="50" value="<?= $rem; ?>">
                    </td>
                </tr>
                <tr>
                    <td colspan="2"><b>Message</b></td>
                </tr>
                <tr>
                    <td>Sujet : </td>
                    <td><input type="text" name="sujet" size="60" value="<?= $sujet; ?>" required></td>
                </tr>
                <tr>
                    <td>Texte du mail : </td>
                    <td><textarea name="message" cols="60" rows="10" required><?= $message; ?></textarea></td>
                </tr>
                <tr>
                    <td colspan="2"></td>
                </tr>
                <tr>
                    <td></td>
                    <td>
                        <button type="reset" class="btn">Effacer</button>
                        <button type="submit" class="btn">Envoyer</button>
                    </td>
                </tr>
            </table>
        </form>
        <?php if ($form_success === true) { ?>
            <hr>
            <?php if ($cptok > 0) { ?>
                <p>Mails envoyés : <?= $cptok; ?>
                <?php } ?>
                <?php if ($cptko > 0) { ?>
                    <br>Envois impossibles : <?= $cptko; ?>
                <?php } ?>
                <br>Durée du traitement : <?= (time() - $T0); ?> sec.
                </p>
                <p>Retour à la
                    <a href="<?= $root; ?>/admin/utilisateurs"><b>liste des utilisateurs</b></a>
                </p>
            <?php } ?>
    </div>
</div>
<?php
include(__DIR__ . '/../templates/front/_footer.php');
return (ob_get_clean());
