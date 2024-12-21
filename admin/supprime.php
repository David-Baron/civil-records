<?php

use Symfony\Component\HttpFoundation\RedirectResponse;

define('ADM', 10); // Compatibility only
$admtxt = 'Gestion '; // Compatibility only
require(__DIR__ . '/../next/bootstrap.php');
require(__DIR__ . '/../next/_COMMUN_env.inc.php'); // Compatibility only
require(__DIR__ . '/../next/Model/UserModel.php');

if (!$userAuthorizer->isGranted(6)) {
    $response = new RedirectResponse("$root/admin/");
    $response->send();
    exit();
}

pathroot($root, $path, $xcomm, $xpatr, $page);

$missingargs = false;
$oktype = false;
$AnneeDeb   = getparam('AnneeDeb');
$AnneeFin   = getparam('AnneeFin');
$TypeActes  = getparam('TypeActes');
$Filiation  = getparam('Filiation');
$AVerifier  = getparam('AVerifier');
$xtdiv      = getparam('typdivers');
$comdep  = html_entity_decode(getparam('ComDep'), ENTITY_REPLACE_FLAGS, ENTITY_CHARSET);
$Commune = communede($comdep);
$Depart  = departementde($comdep);
$xaction = getparam('action');

ob_start();
open_page("Suppression d'une série d'actes", $root);
include("../tools/PHPLiveX/PHPLiveX.php");
$ajax = new PHPLiveX(array("getCommunes"));
$ajax->Run(false, "../tools/PHPLiveX/phplivex.js");
?>
<div class="main">
    <?php zone_menu(ADM, $session->get('user')['level']); ?>
    <div class="main-col-center text-center">
        <?php
        navadmin($root, "Suppression d'actes");

        if ($xaction == 'submitted' or $xaction == 'validated') {
            // Données postées
            if ((empty($TypeActes) or ($TypeActes == 'X'))) {
                msg('Vous devez préciser le type des actes.');
                $missingargs = true;
            }
            if (empty($Commune)) {
                msg('Vous devez préciser une commune.');
                $missingargs = true;
            }
        } else {
            $missingargs = true;  // par défaut
        }
        if (! $missingargs) {
            $oktype = true;
            $olddepos = getparam('olddepos', 0);
            $params = array(
                'xtdiv' => $xtdiv,
                'userlevel' => $session->get('user')['level'],
                'userid' => $session->get('user')['ID'],
                'olddepos' => $olddepos,
                'TypeActes' => $TypeActes,
                'AnneeDeb' => $AnneeDeb,
                'AnneeFin' => $AnneeFin,
                'comdep' => $comdep,
            );
            list($table, $ntype, $soustype, $condcom, $condad, $condaf, $condtdiv, $conddep) = set_cond_select_actes($params);

            if ($xaction <> 'validated') {
                $request = "SELECT count(*) FROM " . $table .
                    " WHERE " . $condcom . $condad . $condaf . $conddep . $condtdiv . " ;";

                // echo $request;
                optimize($request);
                $result = EA_sql_query($request);
                $ligne = EA_sql_fetch_row($result);
                $nbrec = $ligne[0];
                if ($nbrec == 0) {
                    msg("Il n'y a aucun acte de " . $ntype . $soustype . " à " . $comdep . " (dont vous êtes le déposant) !", "erreur");
                    echo '<p><a href="supprime.php">Retour</a></p>';
                } else {
                    echo '<form method="post" enctype="multipart/form-data" action="">' . "\n";
                    echo '<h2 align="center">Confirmation de la suppression</h2>';
                    echo '<p class="message">Vous allez supprimer ' . $nbrec . ' actes de ' . $ntype . $soustype . ' de ' . $comdep . ' !</p>';
                    echo '<p class="message">';
                    echo '<input type="hidden" name="action" value="validated" />';
                    echo '<input type="hidden" name="TypeActes" value="' . $TypeActes . '" />';
                    echo '<input type="hidden" name="AnneeDeb"  value="' . $AnneeDeb . '" />';
                    echo '<input type="hidden" name="AnneeFin"  value="' . $AnneeFin . '" />';
                    echo '<input type="hidden" name="ComDep"   value="' . htmlentities($comdep, ENTITY_REPLACE_FLAGS, ENTITY_CHARSET) . '" />';
                    echo '<input type="hidden" name="typdivers" value="' . $xtdiv . '" />';
                    echo '<input type="hidden" name="olddepos" value="' . $olddepos . '" />';
                    echo '<input type="submit" value=" >> CONFIRMER LA SUPPRESSION >> " />' . "\n";
                    echo '&nbsp; &nbsp; &nbsp; <a href="index.php">Annuler</a></p>';
                    echo "</form>\n";
                }
            } else {
                $request = "DELETE FROM " . $table .
                    " WHERE " . $condcom . $condad . $condaf . $conddep . $condtdiv . " ;";
                // echo $request;
                $result = EA_sql_query($request);
                optimize($request);
                $nb = EA_sql_affected_rows();
                if ($nb > 0) {
                    echo '<p>' . $nb . ' actes de ' . $ntype . $soustype . ' supprimés.</p>';
                    writelog('Suppression ' . $ntype, $Commune, $nb);
                    maj_stats($TypeActes, $T0, $path, "D", $Commune);
                } else {
                    echo '<p>Aucun acte supprimé.</p>';
                }
            } // validated ??
        } else { // missingargs
            //Si pas tout les arguments nécessaire, on affiche le formulaire
            echo '<form method="post" enctype="multipart/form-data" action="">' . "\n";
            echo '<h2 align="center">Suppression de certains actes</h2>';
            if ($session->get('user')['level'] < 8) {
                msg('Attention : Vous ne pourrez supprimer que les données dont vous êtes le déposant !', 'info');
            }
            echo '<table class="m-auto" summary="Formulaire">' . "\n";

            form_typeactes_communes();
            echo '<tr><td colspan="2">&nbsp;</td></tr>';
            echo " <tr>\n";
            echo '  <td align="right">Déposant : </td>' . "\n";
            echo '  <td>';
            if ($session->get('user')['level'] < 8) {
                echo '<input type="hidden" name="olddepos" value="0" />';
            } else {
                listbox_users("olddepos", 0, $config->get('DEPOSANT_LEVEL'), ' *** Tous *** ');
            } ?>
            </td>
            </tr>
            <tr>
                <td colspan="2">&nbsp;</td>
            </tr>
            <tr>
                <td>Années : </td>
                <td>
                    de <input type="text" name="AnneeDeb" size="4" maxlength="4">
                    à <input type="text" name="AnneeFin" size="4" maxlength="4"> (ces années comprises)
                </td>
            </tr>
            <tr>
                <td colspan="2">&nbsp;</td>
            </tr>
            <tr>
                <td></td>
                <td>
                    <button type="reset">Annuler</button>
                    <button type="submit">Supprimer</button>
                </td>
            </tr>
            </table>
            <input type="hidden" name="action" value="submitted">
            </form>
        <?php } ?>
    </div>
</div>
<?php include(__DIR__ . '/../templates/front/_footer.php');

$response->setContent(ob_get_clean());
$response->send();
