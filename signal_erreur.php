<?php


use Symfony\Component\HttpFoundation\RedirectResponse;

define('ADM', 0); // Compatibility only
$admtxt = ''; // Compatibility only
require(__DIR__ . '/next/bootstrap.php');
require(__DIR__ . '/next/_COMMUN_env.inc.php'); // Compatibility only

global $crlf;
$crlf = chr(10) . chr(13);

$AVEC_INFOS_SUGGESTION = false; // DÉSACTIVE LA GESTION DES INFOS DETAILLÉES true|false

function gen_desc($fld_attr)
{
    $zone_parts = explode('_', $fld_attr['ZONE']);
    $cod_acteur = '';
    if (count($zone_parts) == 1) {
        $fld = $zone_parts[0];
    } else {
        $cod_acteur = $zone_parts[0];
        $fld = $zone_parts[1];
    }
    $qual = '';
    if ($cod_acteur != '') {
        switch ($cod_acteur) {
            case 'P':
                if ($fld != 'NOM') {
                    $qual = 'du père';
                }
                break;
            case 'M':
                if ($fld != 'NOM') {
                    $qual = 'de la mère';
                }
                break;
            case 'T1':
                if ($fld != 'NOM') {
                    $qual = 'du parrain/témoin 1';
                }
                break;
            case 'T2':
                if ($fld != 'NOM') {
                    $qual = 'de la marraine/témoin 2';
                }
                break;
            case 'EXC':
                $qual = 'de l\'ex-conjoint';
                break;
            case 'C':
                $qual = 'de l\'épouse';
                break;
            case 'CP':
                $qual = 'du père de l\'épouse';
                break;
            case 'CM':
                $qual = 'de la mère de l\'épouse';
                break;
            case 'T3':
                $qual = 'du témoin 3';
                break;
            case 'T4':
                $qual = 'du témoin 4';
                break;
        }
    }
    $desc = $fld_attr['ETIQ'];
    if ($qual != '') {
        $desc .= ' (' . $qual . ')';
    }
    return $desc;
}

function gen_id_nim($xty, $xacte)
{
    $id_pour_nimegue = 'Identification de l\'acte pour Nimègue : ' . $xacte['NOM'] . ', ' . $xacte['PRE'] . ', ' . $xacte['DATETXT'] . ', ' . $xacte['COMMUNE'] . ' (' . $xacte['CODCOM'] . ') ' . $xacte['DEPART'] . ' (' . $xacte['CODDEP'] . '), ' . $xty . '.';
    return $id_pour_nimegue;
}

function set_table_type_script_acte($TypeActes)
{
    global $config;
    // ENTREE : $TypeActes
    // SORTIE : array($table, $ntype, $script);
    // Utilisé dans search_acte et construction formulaire    list($table, $ntype, $script) = set_table_type_script_acte($TypeActes);
    $EA_TypAct_Txt = array('N' => 'de naissances', 'M' => 'de mariages', 'D' => 'de décès', 'V' => 'divers');
    $EA_Type_Table = array('N' => $config->get('EA_DB') . '_nai3', 'M' => $config->get('EA_DB') . '_mar3', 'D' => $config->get('EA_DB') . '_dec3', 'V' => $config->get('EA_DB') . '_div3');
    $EA_Type_TabScript = array('N' => "tab_naiss.php", 'M' => "tab_mari.php", 'D' => "tab_deces.php", 'V' => "tab_bans.php");
    $script = $EA_Type_TabScript[$TypeActes];

    if (!in_array($TypeActes, array('N', 'M', 'D', 'V'))) {
        $table = $ntype = $script = '';
    } else {
        $table = $EA_Type_Table[$TypeActes];
        $ntype = $EA_TypAct_Txt[$TypeActes];
        $script = $EA_Type_TabScript[$TypeActes];
    }
    return array($table, $ntype, $script);
}
function search_acte($xid, $xtyp, $TYPE_TRT)
{
    global $session, $crlf;
    $lg = $GLOBALS['lg'];
    list($table, $ntype, $script) = set_table_type_script_acte($xtyp);
    // LIBELLE","A0","50","V","Type de document","TXT"),
    $mdb = load_zlabels($xtyp, $lg);
    $champs = "";
    for ($i = 0; $i < count($mdb); $i++) {
        $champs .= $mdb[$i]['ZONE'] . ", ";
    }
    $sql = "SELECT " . $champs . " ID FROM " . $table . " WHERE ID=" . $xid;
    $result = EA_sql_query($sql);
    if ($acte = EA_sql_fetch_array($result) or $xid == -1) {
        // lecture des tailles effective des zones
        $qColumnNames = EA_sql_query("SHOW COLUMNS FROM " . $table);
        $numColumns = EA_sql_num_rows($qColumnNames);
        $xx = 0;
        while ($xx < $numColumns) {
            $colname = EA_sql_fetch_row($qColumnNames);
            $xy = isin($colname[1], '(');
            if ($xy > 0) {
                $xt = substr($colname[1], $xy + 1, isin($colname[1], ')') - $xy - 1);
            } else {
                switch (strtoupper($colname[1])) {
                    case "TEXT":
                        $xt = 1000;
                        break;
                    case "DATE":
                        $xt = 10;
                        break;
                }
            }

            $col[$colname[0]] = $xt;
            $xx++;
        } // if $xy>0

        if ($TYPE_TRT == 'montre_formulaire_acte') { // CAS 1	montre_formulaire_acte
            $logtxt = "Proposition de modification d'un acte ";

            //{ print '<pre>';  print_r($col); echo '</pre>'; }

            //echo '<form method="post" action="">'."\n";
            echo '<h3 align="center">' . $logtxt . ' ' . $ntype . '</h3>';
            //echo '<h3 align="center">Commune/paroisse : '.$acte["COMMUNE"].'</h3>';
            echo '<table class="m-auto" summary="Formulaire">';
            $grp = "";
            for ($i = 0; $i < count($mdb); $i++) {
                if ($mdb[$i]['GROUPE'] <> $grp) {
                    $grp = $mdb[$i]['GROUPE'];
                    echo ' <tr>';
                    echo '  <td align="left"><b>' . $mdb[$i]['GETIQ'] . "  </b></td>";
                    echo '  <td> </td>';
                    echo ' </tr>';
                }
                // parametres : $name,$size,$value,$caption
                $value = getparam($mdb[$i]['ZONE']);
                if ($value == "") { // premier affichage
                    if ($xid < 0) {
                        switch ($mdb[$i]['ZONE']) {
                            case "COMMUNE":
                                $value = $Commune;
                                break;
                            case "DEPART":
                                $value = $Depart;
                                break;
                            case "LIBELLE":
                                $value = $xtdiv;
                                break;
                            case "DEPOSANT":
                                $value = $session->get('user')['ID'];
                                break;
                            default:
                                $value = getparam($mdb[$i]['ZONE']);
                        }
                    } else {
                        $value = $acte[$mdb[$i]['ZONE']];
                    }
                } // if $value
                echo ' <tr>';
                echo "  <td>" . $mdb[$i]['ETIQ'] . " : </td>";
                echo '  <td>';
                if ($col[$mdb[$i]['ZONE']] <= 70) {
                    $value = str_replace('"', '&quot;', $value);

                    echo '<input type="text" name="' . $mdb[$i]['ZONE'] . '" size=' . $col[$mdb[$i]['ZONE']] . '" maxlength=' . $col[$mdb[$i]['ZONE']] . ' value="' . $value . '">';
                } else {
                    echo '<textarea name="' . $mdb[$i]['ZONE'] . '" cols=70 rows=' . (min(4, $col[$mdb[$i]['ZONE']] / 70)) . '>' . $value . '</textarea>';
                }
                echo '  </td>';
                echo " </tr>";
            } // for
            echo ' <tr><td>';
            echo "</td></tr></table>\n";
            // return
        } else { //CAS 2  diff_acte et CAS 3  gen_modif  FUSION EN 1 SEUL APPEL
            $msg_diff_acte = '';
            $msg_gen_modif = '';

            $sep = $crlf;
            $identification = gen_id_nim($xtyp, $acte) . $crlf;
            $msg_diff_acte .= $identification . $crlf;
            $msg_diff_acte .= "Dans le tableau suivant, la 1ère colonne indique : libellé court (complément libellé), la 2e colonne la valeur actuelle et la 3e, la valeur proposée.";

            for ($i = 0; $i < count($mdb); $i++) {
                // paramètres : $name,$size,$value,$caption
                $value = getparam($mdb[$i]['ZONE']);
                if ($acte[$mdb[$i]['ZONE']] != $value) {
                    $msg_diff_acte .= $sep . gen_desc($mdb[$i]) . ' : actuellement "' . $acte[$mdb[$i]['ZONE']] . '" devrait être "' . $value . '".';
                    $msg_gen_modif .= "&" . $mdb[$i]['ZONE'] . "=" . $value;
                }
            } // for
            return array($msg_diff_acte, $msg_gen_modif);
        }
    } // if $acte = mysql...
    else {
        msg('Impossible de trouver cet acte !');
    }
}


if (!$userAuthorizer->isGranted(1)) {
    $session->getFlashBag()->add('warning', 'Vous n\'êtes pas connecté ou vous n\'avez pas les autorisations nécessaires!');
    $response = new RedirectResponse("$root/");
    $response->send();
    exit();
}

pathroot($root, $path, $xcomm, $xpatr, $page);

$missingargs = true;
$nompre = getparam('nompre', $session->get('user')['nom'] . ", " . $session->get('user')['prenom']);
$msgerreur = getparam('msgerreur');
$email = getparam('email', $session->get('user')['email']);
$xid   = getparam('xid');
$xty   = getparam('xty');
$xdf   = getparam('xdf');
$xcc   = getparam('xcc');
$ok = false;

ob_start();
open_page("Signalement d'une erreur", $root); ?>
<div class="main">
    <?php zone_menu(0, 0); ?>
    <div class="main-col-center text-center">
        <?php navigation($root, 2, "", "Signaler une erreur dans un acte");

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

            if (!$AVEC_INFOS_SUGGESTION) { // CONDITIONNEL SIGNAL_ERREUR
                if (strlen($msgerreur) < 10) {
                    msg('Vous devez décrire l\'erreur observée');
                    $ok = false;
                }
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
                $log = "Signalmt erreur";

                $EA_Type_ActScript = array('N' => "acte_naiss.php", 'M' => "acte_mari.php", 'D' => "acte_deces.php", 'V' => "acte_bans.php");
                $s4 = $EA_Type_ActScript[$xty];

                $urlvalid = $config->get('EA_URL_SITE') . $root . "/admin/" . $s4 . "?xid=" . $xid . $crlf . $crlf;
                $lemessage = '';

                if ($AVEC_INFOS_SUGGESTION) { // CONDITIONNEL SIGNAL_ERREUR
                    $lemessage .= "Destinataire final (Vérificateur, ou releveur sinon) : " . $xdf . $crlf . $crlf;
                }
                $lemessage .= "Erreur signalée par " . $nompre . " (" . $email . ")." . $crlf . $crlf;
                if ($AVEC_INFOS_SUGGESTION) {
                    $lemessage .= "Description générale :" . $crlf;
                    if ($msgerreur == '') {
                        $msgerreur = 'Non remplie par le signaleur, voir champs individuels.';
                    }
                }
                $lemessage .= $msgerreur . $crlf . $crlf;

                $lemessage .= "Acte concerné (lien pour vérificateur) : " . $crlf . $crlf;
                $lemessage .= $urlvalid . $crlf;

                if ($AVEC_INFOS_SUGGESTION) { // CONDITIONNEL SIGNAL_ERREUR
                    list($msg_diff_acte, $nouveaux_champs) = search_acte($xid, $xty, 'diff_et_gen');
                    $lemessage .= $msg_diff_acte . $crlf;

                    $nouveaux_champs = str_replace(" ", "%20", $nouveaux_champs);
                    $nouveaux_champs = str_replace('"', "%22", $nouveaux_champs);
                    $nouveaux_champs = str_replace(".", "%2E", $nouveaux_champs);
                    $nouveaux_champs = str_replace("?", "%3F", $nouveaux_champs);
                    $nouveaux_champs = str_replace("!", "%21", $nouveaux_champs);
                    $nouveaux_champs = str_replace(")", "%29", $nouveaux_champs);
                    $nouveaux_champs = str_replace("\r", "%0D", $nouveaux_champs);
                    $nouveaux_champs = str_replace("\n", "%0A", $nouveaux_champs);

                    $urlmodif = $config->get('EA_URL_SITE') . $root . "/admin/edit_acte.php?xid=" . $xid . "&amp;xtyp=" . $xty;
                    $lemessage .= $crlf . "Lien pour le responsable des modifications sur ExpoActes :" . $crlf . $crlf;
                    $lemessage .= $urlmodif . $nouveaux_champs . $crlf . $crlf;
                }

                $sujet = "Erreur signalée sur " . $config->get('SITENAME');
                $sender = mail_encode($nompre) . ' <' . $email . ">";

                $dest = $config->get('EMAIL_SIGN_ERR');
                if ($AVEC_INFOS_SUGGESTION) { // CONDITIONNEL SIGNAL_ERREUR
                    if ($xcc == "cc") {
                        $dest = $config->get('EMAIL_SIGN_ERR') . "," . $email;
                    } else {
                        $dest = $config->get('EMAIL_SIGN_ERR');
                    }
                }

                $okmail = sendmail($sender, $dest, $sujet, $lemessage);
                if ($okmail) {
                    $log .= " + mail";
                    $mes = "Un mail a été envoyé à l'administrateur.";
                } else {
                    $log .= " NO mail";
                    $mes = "ERREUR : Le mail n'a pas pu être envoyé ! <br />Merci de contactez directement l'administrateur du site.";
                }

                $log .= ":" . $xty . "/" . $xid;
                writelog($log, $nompre, 1);
                echo '<p><b>' . $mes . '</b></p>';
                $id = 0;
            }
        }

        //Si pas tous les arguments nécessaires, on affiche le formulaire
        if (!$ok) {
            echo "<h2>Signalement d'une erreur dans un acte</h2>";
            if ($AVEC_INFOS_SUGGESTION) { // CONDITIONNEL SIGNAL_ERREUR
                echo "<p>Ce formulaire se décompose en deux parties&nbsp;:<ul><li>Tous les champs sont modifiables. Vous pouvez suggérer un remplacement, un ajout, une suppression…  L’acte apparaitra tel qu’il sera une fois vos modifications approuvées.</li><li>Une zone de texte libre dans laquelle vous pouvez, soit compléter votre saisie, soit expliquer ce qui vous paraît erroné, si les corrections individuelles ne suffisent pas à la compréhension.</li></ul></p>";
            }
            echo '<form method="post"  action="">';
            echo '<table class="m-auto" summary="Formulaire">';

            if ($AVEC_INFOS_SUGGESTION) { // CONDITIONNEL SIGNAL_ERREUR
                echo " <tr>\n";
                echo '  <td colspan="2">' . "<h4>Modification des champs individuels : </h4><br />\n";
                search_acte($xid, $xty, 'montre_formulaire_acte');
                echo " </tr>\n";
            }

            echo " <tr>\n";
            echo '  <td colspan="2">' . "<h4>Description de l'erreur observée si elle est générale : </h4><br />\n";
            echo '  <textarea name="msgerreur" cols="80" rows="12">' . $msgerreur . '</textarea>' . "</td>\n";
            echo " </tr>\n";
            echo " <tr>\n";
            echo '  <td>' . "Vos nom et prénom : </td>\n";
            echo '  <td><input type="text" size="50" name="nompre" value="' . $nompre . '" />' . "</td>\n";
            echo " </tr>\n";
            echo " <tr>\n";
            echo '  <td>' . "Votre e-mail : </td>\n";
            echo '  <td><input type="text" name="email" size="50" value="' . $email . '" />' . "</td>\n";
            echo " </tr>\n";
            if ($AVEC_INFOS_SUGGESTION) { // CONDITIONNEL SIGNAL_ERREUR
                echo " <tr>\n";
                echo '  <td>' . "Copie Courriel : </td>\n";
                echo '  <td><input type="checkbox" id="xcc" name="xcc" value="cc" checked>' . "</td>\n";
                echo " </tr>\n";
            } ?>

            <?php if ($config->get('AUTO_CAPTCHA') && function_exists('imagettftext')) { ?>
                <tr>
                    <td><img src="<?= $root; ?>/tools/captchas/image.php" alt="captcha" id="captcha"></td>
                    <td>
                        Recopiez le code ci-contre : <br>
                        <input type="text" name="captcha" size="6" maxlength="5" value="">
                    </td>
                </tr>
            <?php } ?>

        <?php if ($AVEC_INFOS_SUGGESTION) { // CONDITIONNEL SIGNAL_ERREUR

                list($table, $ntype, $script) = set_table_type_script_acte($xty);
                $sql = "SELECT VERIFIEU, RELEVEUR, ID FROM " . $table . " WHERE ID=" . $xid . ";";
                $result = EA_sql_query($sql);
                $acte = EA_sql_fetch_array($result);
                if ($acte['VERIFIEU'] != "") {
                    $xdf = $acte['VERIFIEU'];
                } else {
                    $xdf = $acte['RELEVEUR'];
                }
            }

            echo " <tr><td>\n";
            echo '  <input type="hidden" name="xid" value="' . $xid . '">';
            echo '  <input type="hidden" name="xty" value="' . $xty . '">';
            echo '  <input type="hidden" name="xdf" value="' . $xdf . '">';
            //echo '  <input type="hidden" name="xcc" value="'.$xcc.'">';
            echo '  <input type="hidden" name="action" value="submitted">';

            if (!$AVEC_INFOS_SUGGESTION) { // CONDITIONNEL AVANT SIGNAL_ERREUR
                echo ' <a href="' . $root . '/"">Revenir à l\'accueil</a></p>';
            }

            echo ' &nbsp; <input type="reset" value=" Effacer " />';
            echo " </td><td align=\"left\">\n";
            echo ' &nbsp; <input type="submit" value=" >> Envoyer >> " />';
            echo " </td></tr>\n";
            echo "</table>\n";
            echo "</form>\n";
        } else {
            echo '<p align="center"><b>Merci de  votre aide.</b><br><a href="' . $root . '/"">Revenir à l\'accueil</a></p>';
        } ?>
    </div>
</div>
<?php include(__DIR__ . '/templates/front/_footer.php');
$response->setContent(ob_get_clean());
$response->send();
