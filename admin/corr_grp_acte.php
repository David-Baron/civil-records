<?php

use Symfony\Component\HttpFoundation\RedirectResponse;

define('ADM', 10); // Compatibility only
$admtxt = 'Gestion '; // Compatibility only
require(__DIR__ . '/../next/bootstrap.php');
require(__DIR__ . '/../next/_COMMUN_env.inc.php'); // Compatibility only
require(__DIR__ . '/../next/Model/UserModel.php');

if (!$userAuthorizer->isGranted(8)) {
    $response = new RedirectResponse("$root/admin/");
    $response->send();
    exit();
}

pathroot($root, $path, $xcomm, $xpatr, $page);

$title = "Corrections groupées d'actes";
$ok = false;
$missingargs = false;
$oktype = false;
$today = today();
$olddepos = getparam('olddepos', 0);
$xaction = getparam('action');
$xtyp      = strtoupper(getparam('TypeActes'));
$xtdiv     = getparam('typdivers');
$AnneeDeb  = getparam('AnneeDeb');
$AnneeFin  = getparam('AnneeFin');
$newcom    = getparam('newcom');
$newdep    = getparam('newdep');
$newdepos  = getparam('newdepos');
$newcodcom    = getparam('newcodcom');
$newcoddep    = getparam('newcoddep');
$xaction   = getparam('action');
$newphoto  = getparam('newphoto');
$newtrans  = getparam('newtrans');
$newverif  = getparam('newverif');
$newsigle  = getparam('newsigle');
$newlibel  = getparam('newlibel');

$menu_data_active = 'G';

ob_start();
open_page($title, $root);
include(__DIR__ . '/../tools/PHPLiveX/PHPLiveX.php');
$ajax = new PHPLiveX(array("getCommunes"));
$ajax->Run(false, "../tools/PHPLiveX/phplivex.js");
?>
<div class="main">
    <?php zone_menu(ADM, $session->get('user')['level'], array()); ?>
    <div class="main-col-center text-center">
        <?php
        navadmin($root, $title);

        require(__DIR__ . '/../templates/admin/_menu_data.php');

        $comdep  = html_entity_decode(getparam('ComDep'), ENTITY_REPLACE_FLAGS, ENTITY_CHARSET);
        $oldcom = communede($comdep);
        $olddep  = departementde($comdep);
        $mdb = load_zlabels('N', $lg);


        if ($xaction == 'submitted' or $xaction == 'validated') {
            // Données postées
            if ((empty($xtyp) or ($xtyp == 'X'))) {
                msg('Vous devez préciser le type des actes.');
                $missingargs = true;
            }
            if (strlen($newcom . $newdep . $newcodcom . $newcoddep . $newphoto . $newtrans . $newverif . $newsigle . $newlibel) + $newdepos == 0) {
                msg('Vous devez préciser au moins une correction à faire.');
                $missingargs = true;
            }
            if (strlen($newsigle . $newlibel) > 0 and $xtyp <> "V") {
                msg('Vous ne pouvez pas modifier le sigle ou le libellé sur ce type d\'acte.');
                $missingargs = true;
            }
        } else {
            $missingargs = true;  // par défaut
        }

        if (! $missingargs) {
            $oktype = true;

            $params = array(
                'xtdiv' => $xtdiv,
                'userlevel' => $session->get('user')['level'],
                'userid' => $session->get('user')['ID'],
                'olddepos' => $olddepos,
                'TypeActes' => $xtyp,
                'AnneeDeb' => $AnneeDeb,
                'AnneeFin' => $AnneeFin,
                'comdep' => $comdep,
            );
            list($table, $ntype, $soustype, $condcom, $condad, $condaf, $condtdiv, $conddep) = set_cond_select_actes($params);
            if ($comdep == "") {
                $condcom = " NOT (ID IS NULL) ";
            }

            if ($xaction <> 'validated') {
                $sql = "SELECT count(*) FROM " . $table .
                    " WHERE " . $condcom . $conddep . $condtdiv . $condad . $condaf . " ;";
                $result = EA_sql_query($sql);
                $ligne = EA_sql_fetch_row($result);
                $nbrec = $ligne[0];
                if ($nbrec == 0) {
                    msg("Il n'y a aucun acte de " . $ntype . $soustype . " à " . $comdep . " !", "erreur");
                    echo '<p><a href="corr_grp_acte.php">Retour</a></p>';
                } else {
                    echo '<form method="post" enctype="multipart/form-data" action="">' . "\n";
                    echo '<h2 align="center">Confirmation de la modification</h2>';
                    echo '<p class="message">Vous allez modifier ' . $nbrec . ' actes de ' . $ntype . $soustype . ' de ' . $comdep . ' !</p>';
                    echo '<p class="message">';
                    echo '<input type="hidden" name="action" value="validated" />';
                    echo '<input type="hidden" name="TypeActes" value="' . $xtyp . '" />';
                    echo '<input type="hidden" name="ComDep"   value="' . htmlentities($comdep, ENTITY_REPLACE_FLAGS, ENTITY_CHARSET) . '" />';
                    echo '<input type="hidden" name="typdivers" value="' . $xtdiv . '" />';
                    echo '<input type="hidden" name="AnneeDeb" value="' . $AnneeDeb . '" />';
                    echo '<input type="hidden" name="AnneeFin" value="' . $AnneeFin . '" />';
                    echo '<input type="hidden" name="newcom" value="' . $newcom . '" />';
                    echo '<input type="hidden" name="newdep" value="' . $newdep . '" />';
                    echo '<input type="hidden" name="newcodcom" value="' . $newcodcom . '" />';
                    echo '<input type="hidden" name="newcoddep" value="' . $newcoddep . '" />';
                    echo '<input type="hidden" name="olddepos" value="' . $olddepos . '" />';
                    echo '<input type="hidden" name="newdepos" value="' . $newdepos . '" />';
                    echo '<input type="hidden" name="newphoto" value="' . $newphoto . '" />';
                    echo '<input type="hidden" name="newtrans" value="' . $newtrans . '" />';
                    echo '<input type="hidden" name="newverif" value="' . $newverif . '" />';
                    echo '<input type="hidden" name="newsigle" value="' . $newsigle . '" />';
                    echo '<input type="hidden" name="newlibel" value="' . $newlibel . '" />';
                    echo '<input type="submit" value=" >> CONFIRMER LA MODIFICATION >> " />' . "\n";
                    echo '&nbsp; &nbsp; &nbsp; <a href="index.php">Annuler</a></p>';
                    echo "</form>\n";
                }
            } else {
                $listmodif = "";
                $t = array(
                    'COMMUNE=' => $newcom,
                    'DEPART=' => $newdep,
                    'CODCOM=' => $newcodcom,
                    'CODDEP=' => $newcoddep,
                    'DEPOSANT=' => $newdepos,
                    'PHOTOGRA=' => $newphoto,
                    'RELEVEUR=' => $newtrans,
                    'VERIFIEU=' => $newverif,
                    'SIGLE=' => $newsigle,
                    'LIBELLE=' => $newlibel,
                );
                $sep = ''; // Separateur de liste vide au début
                foreach ($t as $k => $v) {
                    if (!empty($v)) {
                        $listmodif .= $sep . $k . "'" . sql_quote($v) . "'";
                        $sep = ', '; // separateur de liste après 1 passage
                    }
                };
                unset($t);

                $sql = "UPDATE " . $table . " SET " . $listmodif . " WHERE " . $condcom . $conddep . $condtdiv . $condad . $condaf . " ;";
                $result = EA_sql_query($sql);
                $nb = EA_sql_affected_rows();
                if ($nb > 0) {
                    echo '<p>' . $nb . ' actes de ' . $ntype . $soustype . ' modifiés.</p>';
                    writelog('MAJ globale ' . $ntype, $oldcom, $nb);
                    maj_stats($xtyp, $T0, $path, "C", $oldcom, $olddep);
                    if (!empty($newcom) or !empty($newdep)) {
                        maj_stats($xtyp, $T0, $path, "C", $newcom, $newdep);
                    }
                } else {
                    echo '<p>Aucun acte modifié.</p>';
                }
            } // validated ??
        } // ! missingargs
        else {
            //{ print '<pre>';  print_r($col); echo '</pre>'; }

            echo '<form method="post" action="">' . "\n";
            echo '<h2 align="center">' . $title . '</h2>';
            echo '<table align="center" cellspacing="0" cellpadding="1" border="0" summary="Formulaire">' . "\n";

            echo " <tr><td colspan=\"2\"><h3>Actes concernés</h3></td></tr>\n";
            form_typeactes_communes('');
            echo " <tr>\n";
            echo '  <td align="right">Déposant : </td>' . "\n";
            echo '  <td>';
            listbox_users("olddepos", 0, $config->get('DEPOSANT_LEVEL'), ' *** Tous *** ');
            echo '  </td>';
            echo " </tr>\n";
            //			echo " <tr><td colspan=\"2\">&nbsp;</td></tr>\n";
            echo " <tr>\n";
            echo '  <td align="right">Années : </td>' . "\n";
            echo '  <td>&nbsp;';
            echo '        de <input type="text" name="AnneeDeb" size="4" maxlength="4"/> ';
            echo '        à  <input type="text" name="AnneeFin" size="4" maxlength="4"/> (ces années comprises)';
            echo '  </td>';
            echo " </tr>\n";

            echo " <tr><td><h3>Modifications souhaitées</h3></td><td>(Ne compléter que la/les zone(s) à modifier)</td></tr>\n";
            echo " <tr>\n";
            echo '  <td align="right">Commune/Paroisse : </td>' . "\n";
            echo '  <td><input type="text" size="40" name="newcom" />' . "</td>\n";
            echo " </tr>\n";
            echo " <tr>\n";
            echo '  <td align="right">Code Commune/Paroisse : </td>' . "\n";
            echo '  <td><input type="text" size="12" name="newcodcom" />' . "</td>\n";
            echo " </tr>\n";
            echo " <tr>\n";
            echo '  <td align="right">Département/Province : </td>' . "\n";
            echo '  <td><input type="text" size="40" name="newdep" />' . "</td>\n";
            echo " </tr>\n";
            echo " <tr>\n";
            echo '  <td align="right">Code Département/Province : </td>' . "\n";
            echo '  <td><input type="text" size="10" name="newcoddep" />' . "</td>\n";
            echo " </tr>\n";
            //	echo " <tr><td colspan=\"2\">&nbsp;</td></tr>\n";
            echo " <tr>\n";
            echo '  <td align="right">Déposant : </td>' . "\n";
            echo '  <td>';
            listbox_users("newdepos", 0, $config->get('DEPOSANT_LEVEL'), ' -- Inchangé(s) --');
            echo '  </td>';
            echo " </tr>\n";
            if (isin('OFA', metadata('AFFICH', 'PHOTOGRA')) >= 0) {
                echo " <tr>\n";
                echo '  <td align="right">' . metadata('ETIQ', 'PHOTOGRA') . ' : </td>' . "\n";
                echo '  <td><input type="text" size="40" name="newphoto" />';
                echo "  </td>\n";
                echo " </tr>\n";
                echo " <tr>\n";
            }
            if (isin('OFA', metadata('AFFICH', 'RELEVEUR')) >= 0) {
                echo " <tr>\n";
                echo '  <td align="right">' . metadata('ETIQ', 'RELEVEUR') . ' : </td>' . "\n";
                echo '  <td><input type="text" size="40" name="newtrans" />';
                echo "  </td>\n";
                echo " </tr>\n";
                echo " <tr>\n";
            }
            if (isin('OFA', metadata('AFFICH', 'VERIFIEU')) >= 0) {
                echo " <tr>\n";
                echo '  <td align="right">' . metadata('ETIQ', 'VERIFIEU') . ' : </td>' . "\n";
                echo '  <td><input type="text" size="40" name="newverif" />';
                echo "  </td>\n";
                echo " </tr>\n";
                echo " <tr>\n";
            }
            echo " <tr>\n";
            echo '  <td align="right">Actes divers : </td>' . "\n";
            echo '  <td>&nbsp;';
            echo '    Sigle : <input type="text" name="newsigle" size="5" maxlength="5"/> ';
            echo '    = Libellé : <input type="text" name="newlibel" size="25" maxlength="50"/>';
            echo '  </td>';
            echo " </tr>\n";

            echo " <tr><td colspan=\"2\">&nbsp;</td></tr>\n";
            echo " <tr><td colspan=\"2\">&nbsp;</td></tr>\n";

            echo ' <tr><td>' . "\n";
            echo '  <input type="hidden" name="action" value="submitted" />';
            echo '  <input type="reset" value="Annuler" />' . "\n";
            echo '</td><td><input type="submit" value=" >> ENREGISTRER >> " />' . "\n";
            echo "</td></tr></table>\n";
            echo "</form>\n";
        }

        echo '</div>';
        echo '</div>';
        include(__DIR__ . '/../templates/front/_footer.php');
        $response->setContent(ob_get_clean());
        $response->send();
