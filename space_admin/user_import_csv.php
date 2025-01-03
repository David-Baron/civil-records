<?php

use CivilRecords\Engine\MailerFactory;
use Symfony\Component\HttpFoundation\RedirectResponse;

if (!$userAuthorizer->isGranted(9)) {
    $response = new RedirectResponse("$root/admin/");
    $response->send();
    exit();
}

throw new \Exception("This function not exist anymore, deprecated functionality user import from csv...", 1);
exit;

$T0 = time();
$logOk      = getparam('LogOk'); // for checked
$logKo      = getparam('LogKo'); // for checked
$logRed     = getparam('LogRed'); // for checked
$with_email   = getparam('with_email', 1); // for checked
$xdroits    = getparam('lelevel');
$xregime    = getparam('regime', 2); // pas activé -> automatique
$message    = getparam('Message');
$xaction    = getparam('action');

$missingargs = true;
$emailfound = false;
$oktype = false;
$cptmaj = 0;
$cptign = 0;
$cptadd = 0;
$cptdeja = 0;
$avecidnim = false;
$today = today();
$menu_user_active = 'I';

ob_start();
open_page("Chargement des utilisateurs (CSV)", $root); ?>
<div class="main">
    <?php zone_menu(10, $session->get('user')['level']); ?>
    <div class="main-col-center text-center">
        <?php
        navadmin($root, "Chargement des utilisateurs CSV");

        require(__DIR__ . '/../templates/admin/_menu-user.php');
        if ($xaction == 'submitted') {
            // Données postées
            if (empty($_FILES['Users']['tmp_name'])) {
                msg('Pas trouvé le fichier spécifié.');
            }
        }

        if (!empty($_FILES['Users']['tmp_name'])) { // fichier d'utilisateurs
            if (strtolower(mb_substr($_FILES['Users']['name'], -4)) == ".csv") { //Vérifie que l'extension est bien '.CSV'
                // type TXT
                $csv = file($_FILES['Users']['tmp_name']);
                foreach ($csv as $line_num => $line) { // par ligne
                    $line = ea_utf8_encode($line); // ADLC 24/09/2015
                    if ($line_num == 0) {
                        $line = str_replace('"', '', $line);      // Suppression des guillemets éventuels
                        $line = str_replace(chr(9), ';', $line);  // remplacement des TAB par des ;
                        $acte = explode(";", trim($line));
                    }
                    $oktype = true;
                    if ($oktype == true) {    // --------- Traitement ----------
                        $missingargs = false;
                        $line = str_replace('"', '', $line);      // Suppression des guillemets éventuels
                        $line = str_replace(chr(9), ';', $line);  // remplacement des TAB par des ;
                        $user = ads_explode(";", trim($line), 14);

                        $nom     = $user[0];
                        $pre     = $user[1];
                        $mail    = $user[2];
                        $log = '<br />USER ' . $nom . ' ' . $pre . ' ' . $mail;
                        $ok = true;

                        if (($nom == "") or ($pre == "") or ($mail == "")) {
                            // pas de nom ou de prenom
                            $cptign++;
                            if ($logKo == 1) {
                                echo $log . " INCOMPLET (nom, prenom ou e-mail) -> Ignoré";
                            }
                            $ok = false;
                        }
                        if (isin($mail, "@") == -1 or isin($mail, ".") == -1) {
                            // emal surement pas valide
                            $cptign++;
                            if ($logKo == 1) {
                                echo $log . " email invalide -> Ignoré";
                            }
                            $ok = false;
                        }
                        if ($ok) {
                            // Recherche si existant
                            $sql = "SELECT * FROM " . $config->get('EA_UDB') . "_user3 WHERE nom = '" . sql_quote($nom) . "' AND prenom = '" . sql_quote($pre) . "'";
                            $res = EA_sql_query($sql, $u_db);
                            $nb = EA_sql_num_rows($res);
                            if ($nb > 0) {
                                $cptdeja++;
                                if ($logRed == 1) {
                                    echo $log . " NOM+PRENOM DEJA PRESENT -> Ignoré";
                                }
                                $ok = false;
                            }
                        }
                        if ($ok and $config->get('TEST_EMAIL_UNIC') == 1) {
                            $sql = "SELECT * FROM " . $config->get('EA_UDB') . "_user3 WHERE email = '" . sql_quote($mail) . "'";
                            $res = EA_sql_query($sql, $u_db);
                            $nb = EA_sql_num_rows($res);
                            if ($nb > 0) {
                                $cptdeja++;
                                if ($logRed == 1) {
                                    echo $log . " ADRESSE EMAIL DEJA PRESENTE -> Ignoré";
                                }
                                $ok = false;
                            }
                        }
                        if ($ok) {
                            $login = $user[3];
                            $pw    = $user[4];
                            if (($login == "") or (strtoupper($login) == "AUTO")) {
                                // création automatique du login
                                $racine = strtolower(mb_substr($pre, 0, 3) . mb_substr($nom, 0, 3));
                                $login = $racine;
                                // recherche si existe
                                $sql = "SELECT * FROM " . $config->get('EA_UDB') . "_user3 WHERE login = '" . sql_quote($login) . "'";
                                $res = EA_sql_query($sql, $u_db);
                                $nb = EA_sql_num_rows($res);
                                if ($nb > 0) {
                                    // création d'un login numéroté
                                    $sql = "SELECT login FROM " . $config->get('EA_UDB') . "_user3"
                                        . " WHERE login LIKE '" . $racine . "__' AND cast( substring( login, 7, 2 ) AS unsigned ) >0"
                                        . " ORDER BY login DESC";
                                    $res = EA_sql_query($sql, $u_db);
                                    $nb = EA_sql_num_rows($res);
                                    $val = 1;
                                    if ($nb > 0) {
                                        $ligne = EA_sql_fetch_row($res);
                                        $val = mb_substr($ligne[0], 6, 2) + 1;
                                    }
                                    $login = $racine . mb_substr("0" . $val, -2, 2);
                                }
                            }
                            // TEST FINAL du login (dans tous les cas)
                            $sql = "SELECT * FROM " . $config->get('EA_UDB') . "_user3 WHERE login = '" . sql_quote($login) . "'";
                            $res = EA_sql_query($sql, $u_db);
                            $nb = EA_sql_num_rows($res);
                            if ($nb > 0) {
                                $ok = false;
                                if ($logRed == 1) {
                                    echo $log . " [login=" . $login . "]" . " LOGIN DEJA PRESENT -> Ignoré";
                                }
                                $cptign++;
                            }
                            if (($pw == "") or (strtoupper($pw) == "AUTO")) {
                                // création automatique du passw
                                $pw = MakeRandomPassword(8);
                            }
                            if (strlen($pw) < 40) {
                                // il faut hasher le pw
                                $hashpw = sha1($pw);
                            } else {
                                $hashpw = $pw;
                            }  // on présume que c'est le hash dans une restauration

                            if ($user[5] == "") {
                                $droits = $xdroits;
                            } else {
                                $droits = $user[5];
                                if (!($droits > 0 and $droits <= 8) and $user[9] = "") {   // Interdit de créer des administrateurs à la volée
                                    $ok = false;
                                    if ($logKo == 1) {
                                        echo $log . " ADMINISTRATEURS INTERDITS -> Ignoré";
                                    }
                                    $cptign++;
                                }
                            }
                            if ($user[6] == "") {
                                $regime = $xregime;
                            } else {
                                $regime = $user[6];
                                if (!($regime >= 0 and $regime <= 2)) {
                                    $ok = false;
                                    if ($logKo == 1) {
                                        echo $log . " REGIME " . $regime . " INVALIDE -> Ignoré";
                                    }
                                    $cptign++;
                                }
                            }
                            if ($user[7] == "") {
                                $solde = $config->get('PTS_PAR_PER');
                            } else {
                                $solde = $user[7];
                            }
                            $comment = $user[8]; // REM
                            if ($user[9] == "") {  // 11 = date d'expiration
                                $dtexpiration = dt_expiration_defaut();
                            } else {
                                $dtexpiration = $user[9];
                            }
                            $libre = $user[10]; // libre

                            // 11 = ID

                            if ($user[12] == "") {  // statut
                                $statut = "N";
                            } else {
                                $statut = $user[12];
                            }
                            if ($user[13] == "") {  // 13 = date de création
                                if (!empty($user[11])) {
                                    $dtcreation = "1001-01-01";
                                }  // restauration
                                else {
                                    $dtcreation = today();
                                }
                            } else {
                                $dtcreation = $user[13];
                            }
                            if (empty($user[14])) {
                                $pt_conso = 0;
                            } else {
                                $pt_conso = $user[14];
                            }
                            if (empty($user[15])) {  // 15 = date de dernière recharge
                                $maj_solde = today();
                            } else {
                                $maj_solde = $user[15];
                            }
                            // test sur le n° ID
                            if (!empty($user[11])) {
                                $iduser = $user[11];
                                $sql = "SELECT * FROM " . $config->get('EA_UDB') . "_user3 WHERE ID = '" . sql_quote($iduser) . "'";
                                $res = EA_sql_query($sql, $u_db);
                                $nb = EA_sql_num_rows($res);
                                if ($nb > 0) {
                                    $ok = false;
                                    if ($logRed == 1) {
                                        echo $log . " [login=" . $login . "]" . " ID déjà présent -> Ignoré";
                                    }
                                    $cptign++;
                                }
                            } else {
                                $iduser = "";
                            }
                        }

                        if ($ok) {
                            // insertion
                            $reqmaj = "INSERT INTO " . $config->get('EA_UDB') . "_user3 "
                                . " ( `login` , `hashpass` , `nom` , `prenom` , `email` , `level` , `regime` , `solde` , `maj_solde` , statut, dtcreation, dtexpiration, libre, pt_conso, ID,`REM`)"
                                . " VALUES('" . sql_quote($login) . "','"
                                . sql_quote($hashpw) . "','"
                                . sql_quote($nom) . "','"
                                . sql_quote($pre) . "','"
                                . sql_quote($mail) . "',"
                                . sql_quote($droits) . ","
                                . sql_quote($regime) . ","
                                . sql_quote($solde) . ",'"
                                . sql_quote(date_sql($maj_solde)) . "','"
                                . sql_quote($statut) . "','"
                                . sql_quote(date_sql($dtcreation)) . "','"
                                . sql_quote(date_sql($dtexpiration)) . "','"
                                . sql_quote($libre) . "','"
                                . sql_quote($pt_conso) . "','"
                                . sql_quote($iduser) . "','"
                                . sql_quote($comment) . "');";
                            //echo $reqmaj;
                            if ($result = EA_sql_query($reqmaj, $u_db)) {
                                if ($request->request->get('with_email')) {                                   
                                    $from = $config->get('SITENAME') . ' <' . $_ENV['EMAIL_SITE'] . ">";
                                    $to = $mail;
                                    $subject = "Votre compte " . $config->get('SITENAME');
                                    $mailerFactory = new MailerFactory();
                                    $mail = $mailerFactory->createEmail($from, $to, $subject, 'new_account_created.php', [
                                        'sitename' => $config->get('SITENAME'),
                                        'urlsite' => $config->get('URL_SITE'),
                                        'user' => ['nom' => $nom, 'prenom' => $pre, 'login' => $login],
                                        'plain_text_password' => $pw
                                    ]);
                                    $mailerFactory->send($mail);
                                }
                                if ($logOk == 1) {
                                    echo $log . '  -> Créé.';
                                }
                                $cptadd++;
                            } else {
                                echo ' -> Erreur : ';
                                echo '<p>' . EA_sql_error() . '<br />' . $reqmaj . '</p>';
                            }
                        }  // complet
                    }    // --------- Traitement ----------
                } // par ligne
            } // type TXT
            else {
                msg("Type de fichier incorrect !");
            }
        } // fichier d'actes

        //Si pas tout les arguments nécessaire, on affiche le formulaire
        if ($missingargs) {
            if ($xaction == '') {  // parametres par défaut
                $message    = $config->get('MAIL_NEWUSER');
            }

            echo '<form method="post" enctype="multipart/form-data">';
            echo '<h2>Chargement de comptes utilisateurs</h2>';
            echo '<div class="warning">Veillez à vérifier que le fichier votre fichier CSV 
                respecte le <a href="aide/gestuser.html">format</a> ad hoc !</div>';
            echo '<table class="m-auto" summary="Formulaire">';
            echo "<tr>";
            echo '<td>Fichier utilisateurs CSV : </td>';
            echo '<td><input type="file" size="62" name="Users"></td>';
            echo "</tr>";
            echo "<tr><td colspan=\"2\">&nbsp;</td></tr>";

            echo "<tr>";
            echo "<td>Droits d'accès AUTO : </td>";
            echo '<td>';
            lb_droits_user($xdroits);
            echo '</td>';
            echo "</tr>";
            echo "<tr><td colspan=2>&nbsp;</td></tr>";

            if ($config->get('GEST_POINTS') > 0) {
                echo "<tr>";
                echo "<td>Régime (points) AUTO : </td>";
                echo '<td>';
                lb_regime_user($xregime);
                echo '</td>';
                echo "</tr>";

                echo "<tr><td colspan=2>&nbsp;</td></tr>";
            } else {
                echo '<input type="hidden" name="regime" value="' . $xregime . '">';
            }

            echo "<tr>";
            echo '<td>Envoi des codes d\'accès : </td>';
            echo '<td>';
            echo '<input type="checkbox" name="with_email" ' . ($with_email == 1 ? 'checked' : '') . '> Envoi automatique du mail ci-dessous';
            echo '</td>';
            echo "</tr>";

            echo '<tr>';
            echo "<td>Texte du mail : </td>";
            echo '<td>';
            echo '<textarea name="Message" cols=50 rows=6>' . $message . '</textarea>';
            echo '</td>';
            echo "</tr>";

            echo "<tr>";
            echo '<td>Contrôle des résultats : </td>';
            echo '<td>';
            echo '<input type="checkbox" name="LogOk"  value="1"' . ($logOk == 1 ? ' checked' : '') . '> Comptes créés';
            echo '<input type="checkbox" name="LogKo"  value="1"' . ($logKo == 1 ? ' checked' : '') . '> Comptes erronés';
            echo '<input type="checkbox" name="LogRed" value="1"' . ($logRed == 1 ? ' checked' : '') . '> Comptes redondants';
            echo '</td>';
            echo "</tr>";
            echo "<tr><td colspan=\"2\">&nbsp;</td></tr>";
            echo "<tr><td></td>";
            echo ' <input type="hidden" name="action" value="submitted">';

            echo '<td><button type="reset" class="btn">Effacer</button>';
            echo '<button type="submit" class="btn">Charger</button>';
            echo '<a href="' . $root . '/admin/aide/gestuser.html" class="btn" target="_blank">Aide</a>&nbsp;';
            echo "</td></tr>";
            echo "</table>";
            echo "</form>";
        } else {
            echo '<p>';
            if ($cptadd > 0) {
                echo '<br>User ajoutés  : ' . $cptadd;
                writelog('Ajout USERS CSV ', "", "", $cptadd);
            }
            if ($cptign > 0) {
                echo '<br />User erronés  : ' . $cptign;
            }
            if ($cptdeja > 0) {
                echo '<br />User redondants  : ' . $cptdeja;
            }
            echo '<br />Durée du traitement  : ' . (time() - $T0) . ' sec.';
            echo '</p>';
            echo '<p>Retour à la ';
            echo '<a href="' . $root . '/admin/utilisateurs"><b>liste des utilisateurs</b></a>';
            echo '</p>';
        } ?>
    </div>
</div>
<?php include(__DIR__ . '/../templates/front/_footer.php');
return (ob_get_clean());
