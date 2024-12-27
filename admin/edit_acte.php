<?php

use Symfony\Component\HttpFoundation\RedirectResponse;

require(__DIR__ . '/../next/bootstrap.php');
require(__DIR__ . '/../next/_COMMUN_env.inc.php'); // Compatibility only

if (!$userAuthorizer->isGranted(6)) {
    $response = new RedirectResponse("$root/admin/");
    $response->send();
    exit();
}

pathroot($root, $path, $xcomm, $xpatr, $page);

$lg = $GLOBALS['lg'];
$xid    = $request->get('xid', null);
$xtyp   = $request->get('xtyp');
$xcomm  = $request->get('xcomm', '');
$xtdiv  = $request->get('typdivers', '');

$xconfirm = $request->get('xconfirm');

$title = "Modification d'un acte";
$logtxt = "Modification";

if ($xid === null) {
    $title = "Ajout d'un acte";
    $logtxt = "Ajout";
    $comdep  = html_entity_decode($xcomm, ENTITY_REPLACE_FLAGS, ENTITY_CHARSET);
    $Commune = communede($comdep);
    $Depart  = departementde($comdep);
}

$ok = false;
$missingargs = false;
$oktype = false;
$today = today();

ob_start();
open_page($title, $root); ?>
<div class="main">
    <?php zone_menu(10, $session->get('user')['level'], array()); ?>
    <div class="main-col-center text-center">
        <?php
        navadmin($root, $title);

        if ($xid == '' || $xtyp == '' || $xtyp == 'X') {
            // Données postées
            msg("Vous devez préciser le numéro et le type de l'acte.");
            $missingargs = true;  // par défaut
        }
        if (! $missingargs) {
            $oktype = true;
            switch ($xtyp) {
                case "N":
                    $ntype = "de naissance";
                    $table = $config->get('EA_DB') . "_nai3";
                    $script = "/tab_naiss.php";
                    break;
                case "D":
                    $ntype = "de décès";
                    $table = $config->get('EA_DB') . "_dec3";
                    $script = "/tab_deces.php";
                    break;
                case "V":
                    $ntype = "divers";
                    $table = $config->get('EA_DB') . "_div3";
                    $script = "/tab_bans.php";
                    break;
                case "M":
                    $ntype = "de mariage";
                    $table = $config->get('EA_DB') . "_mar3";
                    $script = "/tab_mari.php";
                    break;
                default:
                    $oktype = false;
            }
            // LIBELLE","A0","50","V","Type de document","TXT"),
            $mdb = load_zlabels($xtyp, $lg);
            if ($xconfirm == 'confirmed' and $oktype) {
                // *** Vérification des données reçues
                $ladate = "";
                $ok = true;
                $MauvaiseDate = 0;
                $ladate = "";
                ajuste_date(getparam("DATETXT"), $ladate, $MauvaiseDate);

                if ($xid < 0) {
                    $sql = "INSERT INTO " . $table . " ";
                    $zlist = "(";
                    $vlist = "(";
                    $txt = "ajouté";
                } else {
                    $sql = "UPDATE " . $table . " SET ";
                    $txt = "modifié";
                    $logtxt = "Edition";
                }
                for ($i = 0; $i < count($mdb); $i++) {
                    if ($mdb[$i]['OBLIG'] == 'Y') {  //  obligatoire
                        // champ obligatoire
                        if (empty($_REQUEST[$mdb[$i]['ZONE']])) {
                            msg(sprintf('La zone [%1$s] de [%2$s] est obligatoire.', $mdb[$i]['ETIQ'], $mdb[$i]['GETIQ']));
                            $ok = false;
                        }
                    }
                    $valeurlue = getparam($mdb[$i]['ZONE']);
                    $valeurlue = str_replace("++", chr(226) . chr(128) . chr(160), $valeurlue);
                    if ($xid < 0) { // ajout
                        $zlist .= $mdb[$i]['ZONE'] . ",";
                        $vlist .= "'" . sql_quote($valeurlue) . "', ";
                    } else {
                        $sql .= $mdb[$i]['ZONE'] . " = '" . sql_quote($valeurlue) . "', ";
                    } // modif
                }
                if ($xid < 0) {
                    $sql .= $zlist . "LADATE,DTDEPOT,DTMODIF,TYPACT,IDNIM) VALUES " . $vlist . "'" . $ladate . "','" . $today . "','" . $today . "','" . $xtyp . "',0)";
                } else {
                    $sql .= "LADATE= '" . $ladate . "', " . "DTMODIF= '" . $today . "' WHERE ID=" . $xid . ";";
                }
                if ($ok) {
                    // *** si tout est ok : sauvegarde de l acte modifié
                    $result = EA_sql_query($sql);
                    $nb = EA_sql_affected_rows();
                    if ($nb > 0) {
                        echo '<p>' . sprintf('%1$s acte %2$s %3$s', $nb, $ntype, $txt) . '</p>';
                        writelog($logtxt . ' ' . $ntype . ' #' . $xid, getparam("COMMUNE"), $nb);
                        echo '<p>Retourner à la liste des actes ';
                        echo '<a href="' . $root . $script . '?xcomm=' . getparam("COMMUNE") . " [" . getparam("DEPART") . "]" . '&xpatr=' . getparam("NOM") . '"><b>' . getparam("NOM") . '</b></a>';
                        if (strpos("MV", $xtyp) !== false) {
                            echo ' ou <a href="' . $root . $script . '?xcomm=' . getparam("COMMUNE") . " [" . getparam("DEPART") . "]" . '&xpatr=' . getparam("C_NOM") . '"><b>' . getparam("C_NOM") . '</b></a>';
                        }
                        echo '</p>';
                        maj_stats($xtyp, $T0, $path, "C", getparam("COMMUNE"), getparam("DEPART"));
                    } else {
                        echo '<p>Aucun acte modifié.</p>';
                    }
                }
            }
            if (!$ok) {
                // *** pas encre Ok : On charge l acte pour édition ***
                $champs = "";
                for ($i = 0; $i < count($mdb); $i++) { {
                        $champs .= $mdb[$i]['ZONE'] . ", ";
                    }
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
                            $xt = mb_substr($colname[1], $xy + 1, isin($colname[1], ')') - $xy - 1);
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
                    }

                    echo '<form method="post">';
                    echo '<h2>' . sprintf('%1$s %2$s', $logtxt, $ntype) . '</h2>';
                    //echo '<h3 align="center">Commune/paroisse : '.$acte["COMMUNE"].'</h3>';
                    echo '<table class="m-auto" summary="Formulaire">';

                    $grp = "";
                    for ($i = 0; $i < count($mdb); $i++) {
                        $grp_courant = $mdb[$i]['GROUPE']; // echo 'ICI '.$grp_courant;
                        $etiq_courant = $mdb[$i]['GETIQ'];
                        if ($xtyp == 'V') {
                            $sigle = '';
                            $tb = $xtyp;
                            if (isset($acte['SIGLE'])) {
                                $sigle = $acte['SIGLE'];
                            } // $tb = 'V';
                            $etiq_courant = grp_label($mdb[$i]['GROUPE'], $tb, $lg, $sigle);
                        }
                        if ($grp_courant <> $grp) {
                            $grp = $grp_courant;
                            echo '<tr class="row0">';
                            echo '<td><b>' . $etiq_courant . "  </b></td>";
                            echo '<td></td>';
                            echo '</tr>';
                        }
                        // parametres : $name, $size, $value, $caption
                        $value = getparam($mdb[$i]['ZONE']);
                        if ($value == "") {  // premier affichage
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
                        echo '<tr class="row1">';
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
                    echo '<tr class="row0"><td>';
                    echo '<input type="hidden" name="xtyp" value="' . $xtyp . '">';
                    echo '<input type="hidden" name="xid"  value="' . $xid . '">';
                    echo '<input type="hidden" name="xconfirm" value="confirmed">';
                    echo '<td><button type="submit" class="btn">Enregistrer</button>';
                    echo ' <a href="' . $session->get('previous_url', "$root/") . '" class="btn">Annuler</a>';
                    echo "</td></tr></table>";
                    echo "</form>";
                } else {
                    msg('Impossible de trouver cet acte !');
                }
            }
        } ?>
    </div>
</div>
<?php include(__DIR__ . '/../templates/front/_footer.php');
$response->setContent(ob_get_clean());
$response->send();
