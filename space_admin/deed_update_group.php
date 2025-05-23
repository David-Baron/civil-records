<?php

use Symfony\Component\HttpFoundation\RedirectResponse;

if (!$userAuthorizer->isGranted(8)) {
    $response = new RedirectResponse("$root/admin/");
    $response->send();
    exit();
}


$title = "Corrections groupées d'actes";
$ok = false;
$missingargs = false;
$oktype = false;
$today = date("Y-m-d", time());
$olddepos = getparam('olddepos', 0);
$xaction = getparam('action');
$xtyp      = getparam('xtyp');
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
    <?php zone_menu(10, $session->get('user')['level'], array()); ?>
    <div class="main-col-center text-center">
        <?php
        navadmin($root, $title);

        require(__DIR__ . '/../templates/admin/_menu-data.php');

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
                    echo '<p><a href="'.$root.'/admin/actes/correction_groupee">Retour</a></p>';
                } else {
                    echo '<form method="post" enctype="multipart/form-data">';
                    echo '<h2 align="center">Confirmation de la modification</h2>';
                    echo '<p class="message">Vous allez modifier ' . $nbrec . ' actes de ' . $ntype . $soustype . ' de ' . $comdep . ' !</p>';
                    echo '<p class="message">';
                    echo '<input type="hidden" name="action" value="validated">';
                    echo '<input type="hidden" name="TypeActes" value="' . $xtyp . '">';
                    echo '<input type="hidden" name="ComDep"   value="' . htmlentities($comdep, ENTITY_REPLACE_FLAGS, ENTITY_CHARSET) . '">';
                    echo '<input type="hidden" name="typdivers" value="' . $xtdiv . '">';
                    echo '<input type="hidden" name="AnneeDeb" value="' . $AnneeDeb . '">';
                    echo '<input type="hidden" name="AnneeFin" value="' . $AnneeFin . '">';
                    echo '<input type="hidden" name="newcom" value="' . $newcom . '">';
                    echo '<input type="hidden" name="newdep" value="' . $newdep . '">';
                    echo '<input type="hidden" name="newcodcom" value="' . $newcodcom . '">';
                    echo '<input type="hidden" name="newcoddep" value="' . $newcoddep . '">';
                    echo '<input type="hidden" name="olddepos" value="' . $olddepos . '">';
                    echo '<input type="hidden" name="newdepos" value="' . $newdepos . '">';
                    echo '<input type="hidden" name="newphoto" value="' . $newphoto . '">';
                    echo '<input type="hidden" name="newtrans" value="' . $newtrans . '">';
                    echo '<input type="hidden" name="newverif" value="' . $newverif . '">';
                    echo '<input type="hidden" name="newsigle" value="' . $newsigle . '">';
                    echo '<input type="hidden" name="newlibel" value="' . $newlibel . '">';
                    echo '<button type="submit">Confirmer la modification</button>';
                    echo ' <a href="'.$root.'/admin/">Annuler</a></p>';
                    echo "</form>";
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
            echo '<form method="post">';
            echo '<h2 align="center">' . $title . '</h2>';
            echo '<table class="m-auto" summary="Formulaire">';

            echo "<tr><td colspan=\"2\"><h3>Actes concernés</h3></td></tr>";
            form_typeactes_communes('');

            echo "<tr>";
            echo '<td>Déposant : </td>';
            echo '<td>';
            listbox_users("olddepos", 0, $config->get('DEPOSANT_LEVEL'), ' -- Tous -- ');
            echo '</td>';
            echo "</tr>";
            // echo "<tr><td colspan=\"2\">&nbsp;</td></tr>";
            echo "<tr>";
            echo '<td>Années : </td>';
            echo '<td>';
            echo ' de <input type="text" name="AnneeDeb" size="4" maxlength="4"> ';
            echo ' à  <input type="text" name="AnneeFin" size="4" maxlength="4"> (ces années comprises)';
            echo '</td>';
            echo "</tr>";

            echo "<tr><td><h3>Modifications souhaitées</h3></td><td>(Ne compléter que la/les zone(s) à modifier)</td></tr>";
            echo "<tr>";
            echo '<td>Commune/Paroisse : </td>';
            echo '<td><input type="text" size="40" name="newcom"></td>';
            echo "</tr>";

            echo "<tr>";
            echo '<td>Code Commune/Paroisse : </td>';
            echo '<td><input type="text" size="12" name="newcodcom"></td>';
            echo "</tr>";

            echo "<tr>";
            echo '<td>Département/Province : </td>';
            echo '<td><input type="text" size="40" name="newdep"></td>';
            echo "</tr>";

            echo "<tr>";
            echo '<td>Code Département/Province : </td>';
            echo '<td><input type="text" size="10" name="newcoddep"></td>';
            echo "</tr>";

            // echo "<tr><td colspan=\"2\">&nbsp;</td></tr>\n";
            echo "<tr>";
            echo '<td>Déposant : </td>';
            echo '<td>';
            listbox_users("newdepos", 0, $config->get('DEPOSANT_LEVEL'), ' -- Inchangé(s) --');
            echo '</td>';
            echo "</tr>";

            if (isin('OFA', metadata('AFFICH', 'PHOTOGRA')) >= 0) {
                echo "<tr>";
                echo '<td>' . metadata('ETIQ', 'PHOTOGRA') . ' : </td>';
                echo '<td><input type="text" size="40" name="newphoto">';
                echo "</td>";
                echo "</tr>";
                echo "<tr>";
            }
            if (isin('OFA', metadata('AFFICH', 'RELEVEUR')) >= 0) {
                echo "<tr>";
                echo '<td>' . metadata('ETIQ', 'RELEVEUR') . ' : </td>';
                echo '<td><input type="text" size="40" name="newtrans">';
                echo "</td>";
                echo "</tr>";
                echo " <tr>";
            }
            if (isin('OFA', metadata('AFFICH', 'VERIFIEU')) >= 0) {
                echo "<tr>";
                echo '<td>' . metadata('ETIQ', 'VERIFIEU') . ' : </td>';
                echo '<td><input type="text" size="40" name="newverif">';
                echo "</td>";
                echo "</tr>";
                echo "<tr>";
            }
            echo "<tr>\n";
            echo '<td>Actes divers : </td>';
            echo '<td>';
            echo ' Sigle : <input type="text" name="newsigle" size="5" maxlength="5">';
            echo ' = Libellé : <input type="text" name="newlibel" size="25" maxlength="50">';
            echo '</td>';
            echo "</tr>";

            echo "<tr><td colspan=\"2\">&nbsp;</td></tr>";
            echo "<tr><td colspan=\"2\">&nbsp;</td></tr>";

            echo '<tr><td>';
            echo '<input type="hidden" name="action" value="submitted">';
            echo '</td>';
            echo '<td><button type="reset" class="btn">Annuler</button> <input type="submit" class="btn">Enregistrer</button></td>';
            echo "</tr></table>";
            echo "</form>";
        }

        echo '</div>';
        echo '</div>';
        include(__DIR__ . '/../templates/front/_footer.php');
        return (ob_get_clean());
