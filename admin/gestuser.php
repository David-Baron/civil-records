<?php

use Symfony\Component\HttpFoundation\RedirectResponse;

define('ADM', 10); // Compatibility only
$admtxt = 'Gestion '; // Compatibility only
require(__DIR__ . '/../next/bootstrap.php');
require(__DIR__ . '/../next/_COMMUN_env.inc.php'); // Compatibility only

if (!$userAuthorizer->isGranted(9)) {
    $response = new RedirectResponse("$root/admin/");
    $response->send();
    exit();
}

pathroot($root, $path, $xcomm, $xpatr, $page);

$id  = getparam('id', -1);
$act = getparam('act');
$sendmail   = getparam('SendMail', 0);
$autopw     = getparam('autopw', 0);
$xdroits    = getparam('lelevel');
$xregime    = getparam('regime');
$message    = getparam('Message');
$nom        = getparam('nom');
$email      = getparam('email');
$dtexpir    = getparam('dtexpir');
$missingargs = true;
$lelogin = getparam('lelogin');
$lepassw = getparam('lepassw');
$leid = getparam('id');
$menu_user_active = 'A';

if (getparam('action') == 'submitted') {
    setcookie("chargeUSERparam", $sendmail . $xdroits . $xregime, time() + 60 * 60 * 24 * 60);  // 60 jours
    // setcookie("chargeUSERmessage", $message, time()+60*60*24*180);  // 180 jours
}

ob_start();
open_page("Gestion des utilisateurs", $root); ?>
<div class="main">
    <?php zone_menu(ADM, $session->get('user')['level']); ?>
    <div class="main-col-center text-center">
        <?php
        navadmin($root, "Gestion des utilisateurs");

        require(__DIR__ . '/../templates/admin/_menu-user.php');

        if (isset($udbname)) {
            msg('ATTENTION : Données ajoutées/modifiées dans ' . $udbaddr . "/" . $udbuser . "/" . $udbname . "/" . $config->get('EA_UDB') . "</p>", 'info');
        }

        // Données postées -> ajouter ou modifier
        if (getparam('action') == 'submitted') {
            $ok = true;
            if (strlen($nom) < 3) {
                msg('Vous devez préciser le nom de la personne');
                $ok = false;
            }
            if (!valid_mail_adrs($email)) {
                msg("Vous devez préciser une adresse email valide pour la personne");
                $ok = false;
            }
            if (strlen($lelogin) < 3 or strlen($lelogin) > 15) {
                msg('Vous devez donner un LOGIN d\'au moins 3 et au plus 15 caractères');
                $ok = false;
            }
            if (!(sans_quote($lelogin) and sans_quote($lepassw))) {
                msg('Vous ne pouvez pas mettre d\'apostrophe dans le LOGIN ou le MOT DE PASSE');
                $ok = false;
            }
            if ($autopw) {
                $pw = MakeRandomPassword(8);
            } elseif ($id == -1 or !empty($lepassw)) {
                if (strlen($lepassw) < 6 or strlen($lepassw) > 15) {
                    msg('Vous devez donner un MOT DE PASSE d\'au moins 6 et au plus 15 caractères');
                    $ok = false;
                }
                if ($lepassw <> getparam('passwverif')) {
                    msg('Les deux copies du MOT DE PASSE ne sont pas identiques');
                    $ok = false;
                }
                $pw = $lepassw;
            } else {
                $pw = "";
            }
            $res = EA_sql_query("SELECT * FROM " . $config->get('EA_UDB') . "_user3 WHERE login='" . sql_quote($lelogin) . "' AND id <> " . $leid, $u_db);
            if (EA_sql_num_rows($res) != 0) {
                $row = EA_sql_fetch_array($res);
                msg('Ce code de login est déjà utilisé par "' . $row['prenom'] . ' ' . $row['nom'] . '" !');
                $ok = false;
            }
            if ($config->get('TEST_EMAIL_UNIC') == 1) {
                $res = EA_sql_query("SELECT * FROM " . $config->get('EA_UDB') . "_user3 WHERE email='" . sql_quote(getparam('email')) . "' AND id <> " . $leid, $u_db);
                if (EA_sql_num_rows($res) != 0) {
                    $row = EA_sql_fetch_array($res);
                    msg('Cette adresse email est déjà utilisé par "' . $row['prenom'] . ' ' . $row['nom'] . '" !');
                    $ok = false;
                }
            }
            if ($ok) {
                $mes = "";
                if ($dtexpir == "") {
                    $dtexpir = $config->get('TOUJOURS');
                }
                if ($id <= 0) {
                    $maj_solde = date("Y-m-d");
                    $reqmaj = "INSERT INTO " . $config->get('EA_UDB') . "_user3 "
                        . "(nom, prenom, email, level, login, hashpass, regime, solde, maj_solde, statut, dtcreation, dtexpiration, rem, libre)"
                        . " VALUES ('"
                        . sql_quote(getparam('nom')) . "','"
                        . sql_quote(getparam('prenom')) . "','"
                        . sql_quote(getparam('email')) . "','"
                        . sql_quote($xdroits) . "','"
                        . sql_quote($lelogin) . "','"
                        . sql_quote(sha1($pw)) . "','"
                        . sql_quote(getparam('regime')) . "','"
                        . sql_quote(getparam('solde')) . "','"
                        . sql_quote($maj_solde) . "','"
                        . sql_quote(getparam('statut')) . "','"
                        . sql_quote($maj_solde) . "','"
                        . sql_quote(date_sql($dtexpir)) . "','"
                        . sql_quote(getparam('rem')) . "','"
                        . sql_quote(getparam('libre')) . "')";
                } else {
                    $missingargs = false;
                    if (getparam('solde') != getparam('soldepre')) {
                        $maj_solde = date("Y-m-d");
                    } else {
                        $maj_solde = $_REQUEST['maj_solde'];
                    }

                    $reqmaj = "UPDATE " . $config->get('EA_UDB') . "_user3 SET ";
                    $reqmaj = $reqmaj .
                        "NOM        = '" . sql_quote(getparam('nom')) . "', " .
                        "PRENOM     = '" . sql_quote(getparam('prenom')) . "', " .
                        "EMAIL      = '" . sql_quote(getparam('email')) . "', " .
                        "LEVEL      = '" . sql_quote($xdroits) . "', " .
                        "LOGIN      = '" . sql_quote($lelogin) . "', ";
                    if ($pw <> "") {
                        $reqmaj = $reqmaj . "HASHPASS   = '" . sql_quote(sha1($pw)) . "', ";
                    }
                    $reqmaj = $reqmaj .
                        "REGIME     = '" . sql_quote(getparam('regime')) . "', " .
                        "SOLDE      = '" . sql_quote(getparam('solde')) . "', " .
                        "MAJ_SOLDE  = '" . sql_quote($maj_solde) . "', " .
                        "DTEXPIRATION= '" . sql_quote(date_sql($dtexpir)) . "', " .
                        "STATUT     = '" . sql_quote(getparam('statut')) . "', " .
                        "LIBRE      = '" . sql_quote(getparam('libre')) . "', " .
                        "REM        = '" . sql_quote(getparam('rem')) . "' " .
                        " WHERE ID=" . $id . ";";
                }
                //echo "<p>".$reqmaj."</p>";

                if ($result = EA_sql_query($reqmaj, $u_db)) {
                    // echo '<p>'.EA_sql_error().'<br />'.$reqmaj.'</p>';
                    if ($id <= 0) {
                        $log = "Ajout utilisateur";
                        if ($sendmail == 1) {
                            $urlsite = $config->get('EA_URL_SITE') . $root . "/index.php";
                            $codes = array("#NOMSITE#", "#URLSITE#", "#LOGIN#", "#PASSW#", "#NOM#", "#PRENOM#");
                            $decodes = array($config->get('SITENAME'), $urlsite, $lelogin, $pw, getparam('nom'), getparam('prenom'));
                            $bon_message = str_replace($codes, $decodes, $message);
                            $sujet = "Votre compte " . $config->get('SITENAME');
                            $sender = mail_encode($config->get('SITENAME')) . ' <' . $config->get('LOC_MAIL') . ">";
                            $okmail = sendmail($sender, getparam('email'), $sujet, $bon_message);
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
        }

        if ($id > 0 and $act == "del") {
            $reqmaj = "DELETE FROM " . $config->get('EA_UDB') . "_user3 WHERE ID=" . $id . ";";
            if ($result = EA_sql_query($reqmaj, $u_db)) {
                writelog('Suppression utilisateur #' . $id, $lelogin, 1);
                echo '<p><b>FICHE SUPPRIMEE.</b></p>';
                $id = 0;
            } else {
                echo ' -> Erreur : ';
                echo '<p>' . EA_sql_error() . '<br />' . $reqmaj . '</p>';
            }
        }

        if ($id == -1) {  // Initialisation
            if (isset($_COOKIE['chargeUSERparam'])) {
                $chargeUSERparam = $_COOKIE['chargeUSERparam'];
            } else {
                $chargeUSERparam = "042";
            }
            $sendmail   = $chargeUSERparam[0];
            $xdroits    = $chargeUSERparam[1];
            $xregime    = $chargeUSERparam[2];
            $message    = $config->get('MAIL_NEWUSER');
            /* $_COOKIE['chargeUSERmessage'];
    if ($message=="")
      {
      $message  = def_mes_sendmail();
      }
    */
            $action = 'Ajout';
            $nom   = "";
            $prenom = "";
            $email = "";
            $lelogin = "";
            $lepassw = "";
            $level = $xdroits;
            $regime = $xregime;
            $solde  = $config->get('PTS_PAR_PER');
            $maj_solde = today();
            $statut = "N";
            $dtcreation = $maj_solde;
            $dtexpir = dt_expiration_defaut();
            $pt_conso = 0;
            $rem   = "";
            $libre = "";
        }

        if ($id > 0) {  //
            $action = 'Modification';
            $request = "SELECT NOM, PRENOM, EMAIL, LEVEL, LOGIN, REGIME, SOLDE, MAJ_SOLDE, STATUT, DTCREATION, DTEXPIRATION, PT_CONSO, REM, LIBRE"
                . " FROM " . $config->get('EA_UDB') . "_user3 "
                . " WHERE ID =" . $id;
            //echo '<P>'.$request;
            if ($result = EA_sql_query($request, $u_db)) {
                $row = EA_sql_fetch_array($result);
                $nom       = $row["NOM"];
                $prenom    = $row["PRENOM"];
                $email     = $row["EMAIL"];
                $level     = $row["LEVEL"];
                $lelogin   = $row["LOGIN"];
                $regime    = $row["REGIME"];
                $solde     = $row["SOLDE"];
                $maj_solde = $row["MAJ_SOLDE"];
                $statut    = $row["STATUT"];
                $dtcreation = $row["DTCREATION"];
                $dtexpir   = $row["DTEXPIRATION"];
                $pt_conso  = $row["PT_CONSO"];
                $rem       = $row["REM"];
                $libre     = $row["LIBRE"];
            } else {
                echo "<p>*** FICHE NON TROUVEE***</p>";
            }
        }


        //Si pas tout les arguments nécessaire, on affiche le formulaire
        if ($id <> 0 && $missingargs) { ?>
            <h2><?= $action; ?> d'une fiche d'utilisateur</h2>
            <form method="post" id="fiche" name="eaform">
                <table class="m-auto" summary="Formulaire">
                    <tr>
                        <td>Nom : </td>
                        <td><input type="text" size="30" name="nom" value="<?= $nom; ?>"></td>
                    </tr>
                    <tr>
                        <td>Prénom : </td>
                        <td><input type="text" name="prenom" size="30" value="<?= $prenom; ?>"></td>
                    </tr>
                    <tr>
                        <td>E-mail : </td>
                        <td><input type="text" name="email" size="50" value="<?= $email; ?>"></td>
                    </tr>
                    <?php $zonelibre = $config->get('USER_ZONE_LIBRE');
                    if (empty($zonelibre)) {
                        $zonelibre = "Zone libre (à définir)";
                    } ?>
                    <tr>
                        <td><?= $zonelibre; ?> : </td>
                        <td><input type="text" name="libre" size="50" value="<?= $libre; ?>"></td>
                    </tr>
                    <tr>
                        <td colspan="2">&emsp;</td>
                    </tr>
                    <tr>
                        <td>Login : </td>
                        <td><input type="text" name="lelogin" size="15" maxlength="15" value="<?= $lelogin; ?>"></td>
                    </tr>
                    <tr>
                        <td>Mot de passe : </td>
                        <td><input type="password" name="lepassw" size="15" maxlength="15">
                            <?php if ($id == -1) { ?>
                                <input type="checkbox" name="autopw" value="1"> Mot de passe automatique
                            <?php } ?>
                        </td>
                    </tr>
                    <tr>
                        <td>Mot de passe (vérif.) : </td>
                        <td><input type="password" name="passwverif" size="15" maxlength="15"></td>
                    </tr>
                    <tr>
                        <td colspan="2">&emsp;</td>
                    </tr>
                    <tr>
                        <td>Statut : </td>
                        <td>
                            <?php lb_statut_user($statut); ?>
                            <?php if ($config->get('USER_AUTO_DEF') == 1 && ($statut == "A" || $statut == "W")) { ?>
                                <a href="<?= $root; ?>/approuver_compte.php?id=<?= $id; ?>&action=OK">Approuver</a>
                                ou <a href="<?= $root; ?>/approuver_compte.php?id=<?= $id; ?>&action=KO">Refuser</a>
                            <?php } ?>
                        </td>
                    </tr>
                    <tr>
                        <td>Date entrée : </td>
                        <td>
                            <?php if ($dtcreation != null) {
                                echo showdate($dtcreation, 'S');
                            } else {
                                echo '- Inconnue -';
                            } ?>
                        </td>
                    </tr>
                    <tr>
                        <td>Date expiration : </td>
                        <?php
                        $expiralert = "";
                        if ($dtexpir < today()) {
                            $expiralert = '<b><span style="color:red">EXPIREE</span></b>';
                        }
                        $dtexpir = showdate($dtexpir, 'S');
                        ?>
                        <td><input type="text" name="dtexpir" size="10" value="<?= $dtexpir; ?>"><?= $expiralert; ?></td>
                    </tr>
                    <tr>
                        <td>Droits d'accès : </td>
                        <td>
                            <?php lb_droits_user($level); ?>
                        </td>
                    </tr>
                    <?php if ($config->get('GEST_POINTS') > 0) { ?>
                        <tr>
                            <td colspan="2">&emsp;</td>
                        </tr>
                        <tr>
                            <td>Régime (points) : </td>
                            <td><?php lb_regime_user($regime); ?></td>
                        </tr>
                        <tr>
                            <td>Solde de points : </td>
                            <td>
                                <input type="text" name="solde" size="5" value="<?= $solde; ?>">
                                <input type="hidden" name="soldepre" value="<?= $solde; ?>">
                            </td>
                        </tr>
                        <tr>
                            <td>Dernière recharge : </td>
                            <td><?= date("d-m-Y", strtotime($maj_solde)); ?></td>
                        </tr>
                        <tr>
                            <td colspan="2">&emsp;</td>
                        </tr>
                        <tr>
                            <td>Points consommés : </td>
                            <td><?= $pt_conso; ?></td>
                        </tr>
                        <tr>
                            <td colspan="2">&emsp;</td>
                        </tr>
                    <?php } else { ?>
                        <tr>
                            <td colspan="2">
                                <input type="hidden" name="regime" value="<?= $regime; ?>">
                                <input type="hidden" name="solde" value="<?= $solde; ?>">
                                <input type="hidden" name="soldepre" value="<?= $solde; ?>">
                            </td>
                        </tr>
                    <?php } ?>
                    <tr>
                        <td colspan="2">&emsp;</td>
                    </tr>
                    <tr>
                        <td>Commentaire : </td>
                        <td>
                            <input type="text" name="rem" size="50" value="<?= $rem; ?>">
                            <input type="hidden" name="maj_solde" value="<?= $maj_solde; ?>">
                        </td>
                    </tr>
                    <?php if ($id == -1) { ?>
                        <tr>
                            <td>Envoi des codes d'accès : </td>
                            <td><input type="checkbox" name="SendMail" value="1" <?= ($sendmail == 1 ? ' checked' : ''); ?>>Envoi automatique du mail ci-dessous</td>
                        </tr>
                        <tr>
                            <td>Texte du mail : </td>
                            <td><textarea name="Message" cols=50 rows=6><?= $message; ?></textarea></td>
                        </tr>
                    <?php } ?>
                    <tr>
                        <td colspan="2">&emsp;</td>
                    </tr>
                    <tr>
                        <td></td>
                        <td>
                            <a href="<?= $root; ?>/admin/aide/gestuser.html" target="_blank">Aide</a>
                            <button type="reset">Effacer</button>
                            <button type="submit">Enregistrer</button>
                            <?php if ($id > 0 && $level < 9) { ?>
                                <a href="<?= $root; ?>/admin/gestuser.php?id=<?= $id; ?>&amp;act=del">Supprimer cet utilisateur</a>
                            <?php } ?>
                        </td>
                    </tr>
                </table>
                <input type="hidden" name="id" value="<?= $id; ?>">
                <input type="hidden" name="action" value="submitted">
            </form>
        <?php } else { ?>
            <p><a href="<?= $root; ?>/admin/listusers.php">Retour à la liste des utilisateurs</a>
                <?php if ($leid > 0 && $act != "del") { ?>
                    | <a href="<?= $root; ?>/admin/gestuser.php?id=<?= $leid; ?>">Retour à la fiche de <?= getparam('prenom'); ?> <?= getparam('nom'); ?></a>
                <?php }
                if ($leid == -1 && $act != "del") { ?>
                    | <a href="<?= $root; ?>/admin/gestuser.php?id=-1">Ajout d'une autre fiche</a>
                <?php } ?>
            </p>
        <?php } ?>
    </div>
</div>
<?php include(__DIR__ . '/../templates/front/_footer.php');

$response->setContent(ob_get_clean());
$response->send();
