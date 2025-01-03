<?php

use CivilRecords\Engine\MailerFactory;
use Symfony\Component\HttpFoundation\RedirectResponse;

if (!$userAuthorizer->isGranted(1)) {
    $session->getFlashBag()->add('warning', 'Vous n\'êtes pas connecté ou vous n\'avez pas les autorisations nécessaires!');
    $response = new RedirectResponse("$root/");
    $response->send();
    exit();
}

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
    $actes_type_paths = array('N' => "/actes/naissances", 'M' => "/actes/mariages", 'D' => "/actes/deces", 'V' => "/actes/divers");
    $script = $actes_type_paths[$TypeActes];

    if (!in_array($TypeActes, array('N', 'M', 'D', 'V'))) {
        $table = $ntype = $script = '';
    } else {
        $table = $EA_Type_Table[$TypeActes];
        $ntype = $EA_TypAct_Txt[$TypeActes];
        $script = $actes_type_paths[$TypeActes];
    }

    return array($table, $ntype, $script);
}
function search_acte(int $xid, string $xtyp, $TYPE_TRT)
{
    global $session;
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
    $acte = EA_sql_fetch_array($result);
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

        echo '<h3>' . $logtxt . ' ' . $ntype . '</h3>';
        echo '<table class="m-auto" summary="Formulaire">';
        $grp = "";
        for ($i = 0; $i < count($mdb); $i++) {
            if ($mdb[$i]['GROUPE'] <> $grp) {
                $grp = $mdb[$i]['GROUPE'];
                echo '<tr>';
                echo '<td><b>' . $mdb[$i]['GETIQ'] . "  </b></td>";
                echo '<td></td>';
                echo '</tr>';
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
            }
            echo '<tr>';
            echo "<td>" . $mdb[$i]['ETIQ'] . " : </td>";
            echo '<td>';
            if ($col[$mdb[$i]['ZONE']] <= 70) {
                $value = str_replace('"', '&quot;', $value);

                echo '<input type="text" name="' . $mdb[$i]['ZONE'] . '" size=' . $col[$mdb[$i]['ZONE']] . '" maxlength=' . $col[$mdb[$i]['ZONE']] . ' value="' . $value . '">';
            } else {
                echo '<textarea name="' . $mdb[$i]['ZONE'] . '" cols=70 rows=' . (min(4, $col[$mdb[$i]['ZONE']] / 70)) . '>' . $value . '</textarea>';
            }
            echo '</td>';
            echo "</tr>";
        }
        echo ' <tr><td>';
        echo "</td></tr></table>";
        // return
    } else { //CAS 2  diff_acte et CAS 3  gen_modif  FUSION EN 1 SEUL APPEL
        $msg_diff_acte = '';
        $msg_gen_modif = '';

        $sep = '<br>';
        $identification = gen_id_nim($xtyp, $acte) . '<br>';
        $msg_diff_acte .= $identification . '<br>';
        $msg_diff_acte .= "Dans le tableau suivant, la 1ère colonne indique : libellé court (complément libellé), la 2e colonne la valeur actuelle et la 3e, la valeur proposée.";

        for ($i = 0; $i < count($mdb); $i++) {
            // paramètres : $name,$size,$value,$caption
            $value = getparam($mdb[$i]['ZONE']);
            if ($acte[$mdb[$i]['ZONE']] != $value) {
                $msg_diff_acte .= $sep . gen_desc($mdb[$i]) . ' : actuellement "' . $acte[$mdb[$i]['ZONE']] . '" devrait être "' . $value . '".';
                $msg_gen_modif .= "&" . $mdb[$i]['ZONE'] . "=" . $value;
            }
        }
        return array($msg_diff_acte, $msg_gen_modif);
    }
}


$user_name = $session->get('user')['nom'] . ", " . $session->get('user')['prenom'];
$user_email = $session->get('user')['email'];

$xid   = $request->get('xid');
$xty   = $request->get('xty');
$xdf   = $request->get('xdf');
$xcc   = $request->get('xcc');

$form_errors = [];

if ($request->getMethod() === 'POST') {

    if (!$AVEC_INFOS_SUGGESTION) { // CONDITIONNEL SIGNAL_ERREUR
        if (strlen($request->request->get('msgerreur')) < 10) {
            $form_errors['msgerreur'] = 'Vous devez décrire l\'erreur observée';
        }
    }
    if ($config->get('AUTO_CAPTCHA') && function_exists('imagettftext')) {
        if (md5(getparam('captcha')) != $_SESSION['valeur_image']) {
            $form_errors['captcha'] = 'Attention à bien recopier le code dissimulé dans l\'image !';
        }
    }
    if (empty($form_errors)) {
        $mail_message = '';
        $acte_type_paths = array('N' => "/actes/naissances", 'M' => "/actes/mariages", 'D' => "/actes/deces", 'V' => "/actes/divers");
        $urlvalid = $config->get('EA_URL_SITE') . $root . "/" . $acte_type_paths[$xty] . "?xid=" . $xid . '<br><br>';

        if ($AVEC_INFOS_SUGGESTION) { // CONDITIONNEL SIGNAL_ERREUR
            $mail_message .= "Destinataire final (Vérificateur, ou releveur sinon) : " . $xdf . '<br><br>';
        }
        $mail_message .= "Erreur signalée par " . $user_name . " (" . $user_email . ").<br><br>";
        if ($AVEC_INFOS_SUGGESTION) {
            $mail_message .= "Description générale : <br>";
            if ($msgerreur == '') {
                $msgerreur = 'Non remplie par le signaleur, voir champs individuels.';
            }
        }
        $mail_message .= $msgerreur . '<br><br>';

        $mail_message .= "Acte concerné (lien pour vérificateur) : <br><br>";
        $mail_message .= $urlvalid . '<br>';

        if ($AVEC_INFOS_SUGGESTION) { // CONDITIONNEL SIGNAL_ERREUR
            list($msg_diff_acte, $nouveaux_champs) = search_acte($xid, $xty, 'diff_et_gen');
            $mail_message .= $msg_diff_acte . '<br>';

            $nouveaux_champs = str_replace(" ", "%20", $nouveaux_champs);
            $nouveaux_champs = str_replace('"', "%22", $nouveaux_champs);
            $nouveaux_champs = str_replace(".", "%2E", $nouveaux_champs);
            $nouveaux_champs = str_replace("?", "%3F", $nouveaux_champs);
            $nouveaux_champs = str_replace("!", "%21", $nouveaux_champs);
            $nouveaux_champs = str_replace(")", "%29", $nouveaux_champs);
            $nouveaux_champs = str_replace("\r", "%0D", $nouveaux_champs);
            $nouveaux_champs = str_replace("\n", "%0A", $nouveaux_champs);

            $urlmodif = $config->get('EA_URL_SITE') . $root . "/admin/actes/modifier?xid=" . $xid . "&xtyp=" . $xty;
            $mail_message .= '<br>Lien pour le responsable des modifications sur ExpoActes : <br><br>';
            $mail_message .= $urlmodif . $nouveaux_champs . '<br><br>';
        }

        $from = $user_name . ' <' . $user_email . ">";
        $to = $_ENV['EMAIL_CORRECTOR'];
        $subject = "Erreur signalée sur " . $config->get('SITENAME');

        $mailerFactory = new MailerFactory();
        $mail = $mailerFactory->createEmail($from, $to, $subject, 'email_default.php', [
            'sitename' => $config->get('SITENAME'),
            'urlsite' => $config->get('SITE_URL'),
            'message' => $mail_message,
        ]);
        $mailerFactory->send($mail);

        $session->getFlashBag()->add('info', "Un mail a été envoyé à l'administrateur.");
        $response = new RedirectResponse($session->get('previous_url', "$root/"));
        $response->send();
        exit();
    }
}

ob_start();
open_page("Signalement d'une erreur", $root); ?>
<div class="main">
    <?php zone_menu(0, $session->get('user', ['level' => 0])['level']); ?>
    <div class="main-col-center text-center">
        <?php navigation($root, 2, "", "Signaler une erreur dans un acte");

        echo "<h2>Signalement d'une erreur dans un acte</h2>";
        if ($AVEC_INFOS_SUGGESTION) { // CONDITIONNEL SIGNAL_ERREUR
            echo "<p>Ce formulaire se décompose en deux parties :<ul><li>Tous les champs sont modifiables. 
                    Vous pouvez suggérer un remplacement, un ajout, une suppression…  L'acte apparaitra tel 
                    qu'il sera une fois vos modifications approuvées.</li><li>Une zone de texte libre dans 
                    laquelle vous pouvez, soit compléter votre saisie, soit expliquer ce qui vous paraît erroné, 
                    si les corrections individuelles ne suffisent pas à la compréhension.</li></ul></p>";
        }
        echo '<form method="post">';
        echo '<table class="m-auto" summary="Formulaire">';

        if ($AVEC_INFOS_SUGGESTION) { // CONDITIONNEL SIGNAL_ERREUR
            echo "<tr>";
            echo '<td colspan="2"><h4>Modification des champs individuels : </h4></td>';
            echo "</tr>";
            echo "<tr>";
            search_acte($xid, $xty, 'montre_formulaire_acte');
            echo "</tr>";
        }

        echo "<tr>";
        echo '<td colspan="2"><h4>Description de l\'erreur observée si elle est générale : </h4></td>';
        echo "</tr>";
        echo "<tr>";
        echo '<td colspan="2"><textarea name="msgerreur" cols="80" rows="12">' . $msgerreur . '</textarea></td>';
        echo "</tr>";

        if ($AVEC_INFOS_SUGGESTION) { // CONDITIONNEL SIGNAL_ERREUR
            echo "<tr>";
            echo '<td>Copie Courriel : </td>';
            echo '<td><input type="checkbox" id="xcc" name="xcc" value="cc" checked></td>';
            echo "</tr>";
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

        echo "<tr><td>";
        echo '<input type="hidden" name="xid" value="' . $xid . '">';
        echo '<input type="hidden" name="xty" value="' . $xty . '">';
        echo '<input type="hidden" name="xdf" value="' . $xdf . '">';
        // echo '<input type="hidden" name="xcc" value="'.$xcc.'">';
        echo "</td><td>";
        echo '<button type="reset" class="btn">Effacer</button>';
        echo '<button type="submit" class="btn">Envoyer</button>';
        echo "</td></tr>";
        echo "</table>";
        echo "</form>";
        echo '<p><a href="' . $root . '/">Revenir à l\'accueil</a></p>';
        ?>
    </div>
</div>
<?php include(__DIR__ . '/../templates/front/_footer.php');
return (ob_get_clean());
