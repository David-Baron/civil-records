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

function init_page()
{
    global $root, $session, $htmlpage;

    open_page("Export d'une série d'utilisateur", $root); ?>
    <div class="main">
        <?php zone_menu(10, $session->get('user')['level'], array()); ?>
        <div class="main-col-center text-center">
            <?php 
    navadmin($root, "Export d'utilisateurs");
    $htmlpage = true;
}

my_ob_start_affichage_continu();

include(__DIR__ . '/../tools/traitements.inc.php');

$regime   = getparam('regime', -1);
$lelevel  = getparam('lelevel');
$rem      = getparam('rem');
$suppr    = getparam('suppr');
$condit   = getparam('condit');
$statut   = getparam('statut');
$xaction  = getparam('action');
$dtexpir   = getparam('dtexpir');
$conditexp = getparam('conditexp');
$conditpts = getparam('conditpts');
$ptscons     = getparam('ptscons');
$Destin = 'T'; // T = vers fichier, P = pour debug

$enclosed = '"';  // ou '"'
$htmlpage = false;
$missingargs = false;
$oktype = false;
$menu_user_active = 'E';
pathroot($root, $path, $xcomm, $xpatr, $page);

if ($xaction == 'submitted') {
    // Données postées
    if ($lelevel >= 9 && $suppr == 'Y') {
        init_page();
        require(__DIR__ . '/../templates/admin/_menu-user.php');
        msg('Interdit de supprimer les administrateurs !');
        $missingargs = true;
    }
} else {
    $missingargs = true;  // par défaut
    init_page();
    require(__DIR__ . '/../templates/admin/_menu-user.php');
}
if (! $missingargs) {
    $condlevel = "level>=0";
    if ($lelevel < 10) {
        $condlevel = "level=" . $lelevel;
    }
    $condrem = "";
    if ($condit <> "0") {
        $condrem = " AND " . comparerSQL('REM', $rem, $condit);
    }
    $condreg = "";
    if ($regime >= 0) {
        $condreg = " AND regime =" . $regime;
    }
    $condsta = "";
    if ($statut <> "0") {
        if ($statut == "X") {
            $condsta = " AND dtexpiration<'" . date("Y-m-d", time() - ($config->get('DUREE_EXPIR') * 24 * 60 * 60)) . "'";
        } else {
            $condsta = " AND statut ='" . $statut . "'";
        }
    }
    $sqlexpir = "";
    $baddt = 0;
    ajuste_date($dtexpir, $sqlexpir, $baddt);
    $condexp = "";
    if ($sqlexpir > '0000-00-00' and $conditexp <> "0") {
        $condexp = " AND " . comparerSQL('dtexpiration', $sqlexpir, $conditexp);
    }
    $condpts = "";
    if ($ptscons <> "" and $conditpts <> "0") {
        $condpts = " AND " . comparerSQL('pt_conso', $ptscons, $conditpts);
    }

    /**
     * @deprecated Only the user himself can delete their account! Except and TODO: send an email to the user for the deleted account within 30 days if he does not log back in.
     */
    if ($suppr == 'Y') {
        $sql = "SELECT count(*) FROM " . $config->get('EA_UDB') . "_user3 WHERE " . $condlevel . $condreg . $condrem . $condsta . $condexp . $condpts . " ;";
        $result = EA_sql_query($sql, $u_db);
        $ligne = EA_sql_fetch_row($result);
        $nbrec = $ligne[0];
        if ($nbrec == 0) {
            $suppr = 'N'; // retour à la procdure de base
        } else {
            init_page();
            require(__DIR__ . '/../templates/admin/_menu-user.php');
?>
            <form method="post">
                <h2>Confirmation de la suppression</h2>
                <p class="message">Vous allez supprimer <?= $nbrec; ?> utilisateurs !</p>
                <p class="message">
                    <input type="hidden" name="action" value="submitted">
                    <input type="hidden" name="regime" value="<?= $regime; ?>">
                    <input type="hidden" name="lelevel" value="<?= $lelevel; ?>'">
                    <input type="hidden" name="rem" value="<?= $rem; ?>">
                    <input type="hidden" name="condit" value="<?= $condit; ?>">
                    <input type="hidden" name="statut" value="<?= $statut; ?>">
                    <input type="hidden" name="conditexp" value="<?= $conditexp; ?>">
                    <input type="hidden" name="dtexpir" value="<?= $dtexpir; ?>">
                    <input type="hidden" name="conditpts" value="<?= $conditpts; ?>">
                    <input type="hidden" name="ptscons" value="<?= $ptscons; ?>">
                    <input type="hidden" name="suppr" value="Oui">
                    <a href="<?= $root; ?>/admin/index.php">Annuler</a>
                    <button type="submit">Confirmer export et suppression</button>
                </p>
            </form>
    <?php }
    }
    if ($xaction == 'submitted' && $suppr <> "Y") {
        $sql = "SELECT * FROM " . $config->get('EA_UDB') . "_user3 WHERE " . $condlevel . $condreg . $condrem . $condsta . $condexp . $condpts . " ;";
        $result = EA_sql_query($sql, $u_db);
        $nbdocs = EA_sql_num_rows($result);
        $fields_cnt = EA_sql_num_fields($result);
        if ($nbdocs == 0) {
            init_page();
            msg("Il n'y a aucun utilisateur avec ce critère !");
            echo '<p><a href="'.$root.'/admin/expsupuser.php">Retour</a></p>';
        } else {
            if ($lelevel < 10) {
                $texlevel = $lelevel;
            } else {
                $texlevel = "ALL";
            }
            $filename = "USERS_" . $texlevel;
            if ($regime >= 0) {
                $filename .= "_" . $regime;
            }
            if ($rem <> "") {
                $filename .= "_" . $rem;
            }
            if ($statut >= 0) {
                $filename .= "_" . $statut;
            }
            $filename  = strtr(remove_accent($filename), '-/ "', '____');
            $filename  .= '.CSV';
            $mime_type = 'text/x-csv';
            if ($Destin == 'T') {
                // Download
                $mime_type = 'text/x-csv;';
                header('Content-Type: ' . $mime_type . ' charset=iso-8859-1');
                header('Expires: ' . gmdate('D, d M Y H:i:s') . ' GMT');
                // lem9 & loic1: IE need specific headers
                if (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') === true) {
                    header('Content-Disposition: inline; filename="' . $filename . '"');
                    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
                    header('Pragma: public');
                } else {
                    header('Content-Disposition: attachment; filename="' . $filename . '"');
                    header('Pragma: no-cache');
                }
            } else {
                // HTML
                init_page();
                echo '<pre>' . "\n";
            } // end download

            $nb = 0;
            $zones = array('nom', 'prenom', 'email', 'login', 'hashpass', 'level', 'regime', 'solde', 'REM', 'dtexpiration', 'libre', 'ID', 'statut', 'dtcreation', 'pt_conso', 'maj_solde');
            while ($row = EA_sql_fetch_array($result)) {
                $data = "";
                $j = 0;
                foreach ($zones as $zone) {
                    if ($j > 0) {
                        $data .= ';';
                    }
                    $j++;
                    if (!isset($row[$zone])) {
                        $data .= '';
                    } elseif ($row[$zone] == '0' || $row[$zone] != '') {
                        $row[$zone] = stripslashes($row[$zone]);
                        $row[$zone] = preg_replace("/\015(\012)?/", "\012", $row[$zone]);
                        if ($enclosed == '') {
                            $data .= $row[$zone];
                        } else {
                            $data .= $enclosed . str_replace($enclosed, $enclosed . $enclosed, $row[$zone]) . $enclosed;
                        }
                    } else {
                        $data .= '';
                    }
                } // end foreach
                $nb++;
                if ($Destin == 'T') {
                    $data = ea_utf8_decode($data);
                }  // retour à ISO

                echo $data . "\r\n";  // pour mac : seulement \r  et pour linux \n !
            }
            if ($lelevel < 10) {
                $actie = "Export";
            } else {
                $actie = "Backup";
                $list_backups = get_last_backups();
                $list_backups["U"] = today();
                set_last_backups($list_backups);
            }
            writelog($actie . ' de fiches utilisateur', "USERS", $nb);
            if ($suppr == "Oui") {
                $sql = "DELETE FROM " . $config->get('EA_UDB') . "_user3 WHERE level=" . $lelevel . $condreg . $condrem . $condsta . $condexp . $condpts . " ;";
                $result = EA_sql_query($sql, $u_db);
                $nb = EA_sql_affected_rows($u_db);
                if ($nb > 0) {
                    writelog('Suppression d\'utilisateurs', "USERS", $nb);
                }
            } // supprimer pour de bon
        } // nbdocs
    } // submitted ??
} else { ?>
    <form method="post" enctype="multipart/form-data">
        <h2>Export/Suppression d'utilisateurs</h2>
        <table class="m-auto" summary="Formulaire">
            <tr>
                <td>Dernier backup : </td>
                <td><?= show_last_backup("U"); ?></td>
            </tr>
            <tr>
                <td colspan="2">&nbsp;</td>
            </tr>
            <tr>
                <td>Droits d'accès : </td>
                <td><?= lb_droits_user($lelevel, 1); ?></td>
            </tr>
            <?php if ($config->get('GEST_POINTS') > 0) { ?>
                <tr>
                    <td>ET</td>
                    <td></td>
                </tr>
                <tr>
                    <td>Régime (points) : </td>
                    <td><?= lb_regime_user($regime, 1); ?></td>
                </tr>
            <?php } else { ?>
                <tr>
                    <td colspan="2"> <input type="hidden" name="regime" value="-1"></td>
                </tr>
            <?php } ?>
            <tr>
                <td>ET</td>
                <td></td>
            </tr>
            <tr>
                <td>Commentaire : </td>
                <td>
                    <?= listbox_trait('condit', "TST", $condit); ?>
                    <input type="text" name="rem" size="50" value="<?= $rem; ?>">
                </td>
            </tr>
            <tr>
                <td>ET</td>
                <td></td>
            </tr>
            <tr>
                <td>Statut : </td>
                <td><?= lb_statut_user($statut, 3); ?></td>
            </tr>
            <tr>
                <td>ET</td>
                <td></td>
            </tr>
            <tr>
                <td>Date expiration : </td>
                <td>
                    <?= listbox_trait('conditexp', "NTS", $conditexp); ?>
                    <input type="text" name="dtexpir" size="10" value="<?= $dtexpir; ?>">
                </td>
            </tr>
            <tr>
                <td>ET</td>
                <td></td>
            </tr>
            <tr>
                <td>Points consommés : </td>
                <td>
                    <?php listbox_trait('conditpts', "NTS", $conditpts); ?>
                    <input type="text" name="ptscons" size="5" value="<?= $ptscons; ?>">
                </td>
            </tr>
            <tr>
                <td colspan="2">&nbsp;</td>
            </tr>
            <tr>
                <td>Suppression :</td>
                <td>
                    <input type="radio" name="suppr" value="N" checked="checked">Non
                    <input type="radio" name="suppr" value="Y">Supprimer les utilisateurs exportés
                </td>
            </tr>
            <tr>
                <td colspan="2">&nbsp;</td>
            </tr>
            <tr>
                <td></td>
                <td>
                    <button type="reset">Annuler</button>
                    <button type="submit">Suite</button>
                </td>
            </tr>
        </table>
        <input type="hidden" name="action" value="submitted">
    </form>
<?php }

if ($htmlpage) {
    echo '</div>';
    echo '</div>';
    include(__DIR__ . '/../templates/front/_footer.php');
}
