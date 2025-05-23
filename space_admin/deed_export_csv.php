<?php

use Symfony\Component\HttpFoundation\RedirectResponse;

$Destin   = getparam('Destin'); // TODO: will be from last url
$needlevel = 6;
if ($Destin == "B") $needlevel = 8;

if (!$userAuthorizer->isGranted($needlevel)) {
    $response = new RedirectResponse("$root/admin/");
    $response->send();
    exit();
}

function init_page($head = "")
{
    global $root, $session, $htmlpage, $titre;

    $menu_data_active = 'B';
    open_page($titre, $root, null, null, $head);
    include("../tools/PHPLiveX/PHPLiveX.php");
    $ajax = new PHPLiveX(array("getCommunes"));
    $ajax->Run(false, "../tools/PHPLiveX/phplivex.js");
?>
    <div class="main">
        <?php zone_menu(10, $session->get('user')['level'], array()); ?>
        <div class="main-col-center text-center">
        <?php
        navadmin($root, $titre);
        $htmlpage = true;
        my_flush(); // On affiche un minimum
    }

    $tpsreserve = 3;
    $separator = ';';
    $htmlpage = false;
    $Max_exe_time = ini_get("max_execution_time");
    $Max_time = min($Max_exe_time - $tpsreserve, $config->get('MAX_EXEC_TIME'));
    $Max_size = return_bytes(ini_get("upload_max_filesize"));
    $Format   = getparam('Format');
    $TypeActes = getparam('TypeActes', 'N');

    if ($Destin == "B") {  // Backup
        $listcom = 2;  // liste de commune avec *** Backup complet
        $titre = "Backup des actes";
        $supp_fields = 0; // exporter tout
        $enclosed = '"';
        $enteteligne = "EA32;";   // EA3 ansi / EA32 utf-8
    } else {
        $listcom = 0;
        $titre = "Export d'une série d'actes";
        $supp_fields = 5; // Champs à ne pas exporter vers nimegue
        $enclosed = '';  // comme NIMEGUE
        if ($Format == 'NIM2') {
            $enteteligne = "NIMEGUE-V2;";
        } else {
            $enteteligne = "NIMEGUEV3;";
        }
    }

    $missingargs = false;
    $oktype = false;
    $tokenfile  = "../" . $config->get('DIR_BACKUP') . $session->get('user')['login'] . '.txt';

    my_ob_start_affichage_continu();

    $comdep  = html_entity_decode(getparam('ComDep'), ENTITY_REPLACE_FLAGS, ENTITY_CHARSET);
    $Commune = communede($comdep);
    $Depart  = departementde($comdep);
    $AnneeDeb = getparam('AnneeDeb');
    $AnneeFin = getparam('AnneeFin');
    $TypeActes = mb_substr(getparam('TypeActes'), 0, 1);
    $xtdiv    = getparam('typdivers');
    $maxmega  = getparam('maxmega');
    $max_select_rec_i = getparam('maxrecord', $config->get('MAX_SELECT_REC')); // valeur visible Input
    $max_select_rec = $max_select_rec_i; // Valeur utile programme
    if ($max_select_rec == 0) {
        $max_select_rec = 999999999;
    }
    $skip = getparam('skip');
    $skip = iif($skip > 0, $skip, 0);
    $file = getparam('file');
    $file = iif($file > 1, $file, 1);
    $Maxbytes = (float) $maxmega * 1024 * 1024;
    $autokey = getparam('autokey');
    $continue = 1;
    $xaction = getparam('action');

    $menu_data_active = 'B';

    if ($xaction == 'go') {
        // Données postées
        if ((empty($TypeActes) || ($TypeActes == 'X')) || empty($Destin) || (empty($Commune))) {
            init_page();
            require(__DIR__ . '/../templates/admin/_menu_data.php');
            if (empty($TypeActes) or ($TypeActes == 'X')) {
                msg('Vous devez préciser le type des actes');
            }
            if (empty($Destin)) {
                msg("Vous devez indiquer la destination de l'export");
            }
            if (empty($Commune)) {
                msg('Vous devez préciser une commune.');
            }
            $missingargs = true;
        }
        if (empty($autokey)) {
            $autokey = md5(uniqid(rand(), true));
        } // généré si lancement du chargement
        else {
            // récupération des valeurs dans le fichier
            if (($tof = fopen($tokenfile, "r")) === false) {
                die('Impossible d\'ouvrir le fichier TOKEN en lecture!');
            } else {
                $vals = explode(";", fgets($tof));
                //{ print '<pre>';  print_r($vals); echo '</pre>'; }
                fclose($tof);
                if ($vals[0] <> "EA_TOKEN") {
                    die('Fichier TOKEN invalide');
                }
                if ($vals[1] <> $autokey) {
                    die('Mauvaise clé');
                }
                $file = $vals[2];
                $skip = $vals[3];
                $continue = $vals[4];
            }
        }
        if ($continue == 1) { // fichier a suivre
            $reloadurl = '/admin/actes/export?action=go&amp;TypeActes=' . $TypeActes . '&amp;Destin=' . $Destin . '&amp;maxmega=' . $maxmega . '&amp;ComDep=' . $comdep . '&amp;autokey=' . $autokey;
            $metahead = '<META HTTP-EQUIV="Refresh" CONTENT="10; URL=' . $reloadurl . '">';
        }
    } else {
        $missingargs = true;  // par défaut
        init_page();
        if ($Destin == "B") {  // Backup
            require(__DIR__ . '/../templates/admin/_menu-data.php');
        }
    }
    if (! $missingargs) {
        if ($Destin == "B") {  // Backup
            $mdb = load_zlabels($TypeActes, $lg, "EA3");
        } else {
            $mdb = load_zlabels($TypeActes, $lg, $Format);
        }

        if ($continue == 0) { // fin d'une chaine automatique
            init_page();
            require(__DIR__ . '/../templates/admin/_menu_data.php');
            echo '<p>Le backup est terminé, il a sauvegardé ' . entier($skip) . ' ' . typact_txt($TypeActes) . '.</p>';
            echo '<p><b>' . $file . ' fichier(s) peut/peuvent à présent être récupéré(s) via FTP dans le répertoire "_backup".</b></p>';
        } else {
            $oktype = true;
            if ($Destin == 'B') {
                $olddepos = 0;
            } else {
                $olddepos = getparam('olddepos', 0);
            }
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
            if (mb_substr($comdep, 0, 6) == "BACKUP") {
                $condcom = " NOT (ID IS NULL) ";
            }

            $extstype = ''; // $soustype est positionné quand un type divers a été choisi
            if (($TypeActes == 'V') and ($soustype != '')) {
                $extstype = "_" . mb_substr($xtdiv, 0, 4);
            }

            if ($xaction == 'go') {
                $sql = "SELECT count(*) FROM " . $table .
                    " WHERE " . $condcom . $condad . $condaf . $conddep . $condtdiv . ";";
                $result = EA_sql_query($resqlquest);
                $row = EA_sql_fetch_row($result);
                $nbdocs = $row[0];

                /* INUTILISE SANS DOUTE RESIDU ANCIENNE VERSION avec $nofiltre = 18,18,18,21 pour NMDV
            $sql = "SELECT * FROM ".$table.
                                 " WHERE ".$condcom.$condad.$condaf.$conddep.$condtdiv." LIMIT 0,1 ;";
            $result = EA_sql_query($sql);
            $fields_cnt = EA_sql_num_fields($result);
            */
                if (($Destin <> "B") and ($nbdocs > $max_select_rec)) {
                    init_page();
                    msg(sprintf('Traitement impossible, trop d\'actes (%1$s), exporter en plusieurs périodes', $nbdocs), "erreur");
                    echo '<p><a href="' . $root . '/admin/actes/export?Destin=' . $Destin . '">Retour</a></p>'; //exit;
                } elseif ($nbdocs == 0) {
                    init_page();
                    msg("Il n'y a aucun acte de " . $ntype . $soustype . " à " . $comdep . " (dont vous êtes le déposant) !", "erreur");
                    echo '<p><a href="' . $root . '/admin/actes/export">Retour</a></p>';
                } else {
                    switch ($Destin) {
                        case 'T':
                            // Download -> NIMEGUE -> ISO-8859-1 !!
                            $filename  = strtr(remove_accent($Commune . "_" . $TypeActes . $extstype), '-/ "', '____');
                            $filename  .= '.TXT';
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
                            $bytes = 0;
                            break;
                        case 'E':
                            // HTML
                            init_page();
                            echo '<pre>';
                            break;
                        case 'B':
                            // Backup
                            //$prc = intval($skip/$nbdocs*100);
                            //$titre = $prc." % ".$titre;
                            init_page($metahead);
                            require(__DIR__ . '/../templates/admin/_menu_data.php');
                            if (mb_substr($comdep, 0, 6) == "BACKUP") {
                                $com_name = "FULL";
                            } else {
                                $com_name  = mb_substr(strtr(remove_accent($Commune), '-/ "', '____'), 0, 30);
                            }
                            $filename  = 'backup_' . date('Y-m-d') . '_' . $com_name . '_' . $TypeActes . '.' . zeros($file, 3) . $config->get('EXT_BACKUP');
                            $bytes = 0;
                            if (!is_dir("../" . $config->get('DIR_BACKUP'))) {
                                msg('034 : Répertoire de backup "' . $config->get('DIR_BACKUP') . '" inaccessible ou inexistant.');
                                die();
                            }
                            if (!is__writable("../" . $config->get('DIR_BACKUP'), false)) {
                                msg('035 : Impossible de créer un fichier dans "' . $config->get('DIR_BACKUP') . '".');
                                die();
                            }

                            echo '<p>Backup en cours vers le fichier <b>' . $filename . '</b> ...';
                            $filename  = "../" . $config->get('DIR_BACKUP') . $filename;
                            if (($hof = fopen($filename, "w")) === false) {
                                die('Impossible d\'ouvrir le fichier en écriture!');
                                // NB "a" permet "append"
                            }
                    } // switch

                    $stop = 0;
                    $sql = "SELECT * FROM " . $table .
                        " WHERE " . $condcom . $condad . $condaf . $conddep . $condtdiv;
                    if ($Destin <> "B") {
                        $sql .= ' ORDER BY ladate, nom, pre';
                    }
                    $sql .= " LIMIT " . $skip . "," . $max_select_rec . " ;";
                    $result = EA_sql_query($sql);
                    $nb = $skip;
                    $nbzone = count($mdb);
                    while ($row = EA_sql_fetch_assoc($result) and $stop == 0) {
                        if ($nb < $skip) {
                            $nb++;
                        }  // passer les lignes déjà traitées
                        elseif (time() - $T0 < $Max_time) {  // on a encore le temps
                            $data = $enteteligne;
                            if ($Destin == "B") {
                                $nbsepar = $nbzone;
                            } else {
                                $nbsepar = $nbzone - 1;
                            }  // pas de ; final en NIMEGUE
                            for ($j = 1; $j < $nbzone; $j++) {
                                $value = $row[$mdb[$j]['ZONE']];
                                if (!isset($value)) {
                                    $data .= '';
                                } elseif ($value == '0' || $value != '') {
                                    if ($mdb[$j]['ZONE'] <> "PHOTOS") {  // ne pas enlever les slash des chemins windows !
                                        $value = stripslashes($value); // retire les slash protégeant les '  (\')
                                    }
                                    $value = preg_replace("/\r/", " ", $value);
                                    $value = preg_replace("/\n/", " ", $value);
                                    $donnee = $value;
                                    if ($enclosed == '') {
                                        $data .= $donnee;
                                    } else {
                                        // protection des guillemets intérieurs en les redoublant
                                        $data .= $enclosed . str_replace($enclosed, $enclosed . $enclosed, $donnee) . $enclosed;
                                    }
                                } else {
                                    $data .= '';
                                }
                                if ($j < $nbsepar) {
                                    $data .= $separator;
                                }
                            } // end for
                            switch ($Destin) {
                                case 'T':
                                    // Download... donc NIMEGUE en iso et NON UTF-8
                                    echo ea_utf8_decode($data) . "\r\n";  // pour mac : seulement \r  et pour linux \n !
                                    $nb++;
                                    break;
                                case 'E':
                                    // HTML
                                    echo htmlspecialchars($data, ENTITY_REPLACE_FLAGS, ENTITY_CHARSET) . '<br>';
                                    $nb++;
                                    break;
                                case 'B':
                                    // Backup en UTF8
                                    if ($bytes + strlen($data) + 2000 > $Maxbytes) {
                                        $stop = 2;
                                        $explic = "Taille maximale atteinte";
                                    } else {
                                        $bytes += fwrite($hof, $data . "\r\n");
                                        $nb++;
                                    }
                            }
                        }  // if
                        else {
                            $stop = 1;
                            $explic = "Temps maximum atteint";
                        }
                    } // while
                    if (($stop == 0) and ($nb < $nbdocs)) {
                        $stop = 3;
                        $explic = "Taille de requête maximale atteinte";
                    }

                    if ($Destin == 'B') {
                        if ($nb > 0) {
                            //echo '</pre>';
                            echo '<p>' . entier($nb - $skip) . ' actes de ' . $ntype . $soustype . ' exportés. ';
                            if ($Destin == "B") {
                                echo '(' . entier($bytes) . ' octets)';
                            }
                            echo '</p>';
                            writelog('Export ' . $ntype . $soustype, $comdep, $nb);
                            if ($stop > 0) {
                                // enregistrement fichier de passage de témoin
                                $bytes += fwrite($hof, "EA_NEXT;" . ($file + 1) . "\r\n");
                                $tof = fopen($tokenfile, "w");
                                $token = "EA_TOKEN;" . $autokey . ";" . ($file + 1) . ";" . ($nb) . ";1;";
                                fwrite($tof, $token . "\r\n");
                                fclose($tof);
                                //
                                echo '<p><b>' . $explic . '.</b></p>';

                                $fait = intval($nb / $nbdocs * 100);
                                echo '<p><div class="graphe"><strong class="barre" style="width:' . $fait . '%;">' . $fait . ' %</strong></div></p>';
                                echo '<p>Déjà ' . entier($nb) . ' actes copiés.</p>';
                                echo '<p>Il reste ' . entier($nbdocs - $nb) . ' actes à traiter.</p>';
                                $skip = $nb;
                                $file = $file + 1;
                                echo '<p><a href="'.$root.'/admin/actes/export?action=go&TypeActes=' . $TypeActes . '&Destin=' . $Destin . '&maxmega=' . $maxmega . '&ComDep=' . $comdep . '&autokey=' . $autokey . '">
								<b>Continuez immédiatement avec le fichier suivant</b></a>';
                                echo '<br>ou laissez le programme continuer seul dans quelques secondes.</p>';
                            } else {
                                if ($Destin == "B") {
                                    // Fin de la chaine
                                    $tof = fopen($tokenfile, "w");
                                    $token = "EA_TOKEN;" . $autokey . ";" . ($file) . ";" . ($nb) . ";0;";
                                    fwrite($tof, $token . "\r\n");
                                    fclose($tof);
                                    $list_backups = get_last_backups();
                                    $list_backups[$TypeActes] = date("Y-m-d", time());
                                    set_last_backups($list_backups);
                                }
                                echo '<p>Transfert terminé.</p>';
                                //			if ($Destin=="B")
                                //				echo '<p>'.$file.' fichier(s) peut/peuvent à présent être récupéré(s) via FTP dans le répertoire "_backup".</p>';
                            }
                            if ($Destin == "B") {
                                fclose($hof);
                            }
                        } else {
                            echo '<p>Aucun acte exporté.</p>';
                        }
                    } else {
                        if ($Destin <> "B") {
                            if ($Destin == "E") {
                                echo '</pre>';
                            }
                            if ($stop > 0) {
                                echo '<p>Export interrompu pour des raisons techniques';
                                if ($stop == 1) {
                                    echo ' : Temps alloué au traitement dépassé.';
                                }
                                echo '</p>';
                            }
                        }
                        writelog('Export ' . $ntype . $soustype, $comdep, $nb);
                    }
                } // nbdocs
            } // submitted ??
        }
    } else { // missingargs
        //Si pas tout les arguments nécessaire, on affiche le formulaire
        echo '<form method="post" enctype="multipart/form-data">';
        echo '<h2>' . $titre . '</h2>';
        if ($session->get('user')['level'] < 8) {
            msg('Attention : Vous ne pourrez réexporter que les données dont vous êtes le déposant !', 'info');
        }
        echo '<table class="m-auto" summary="Formulaire">';
        if ($Destin == "B") {
            echo '<tr><td>Derniers backups : </td><td>';
            echo show_last_backup("NMDV");
            echo "</td></tr>";
            echo "<tr><td colspan=\"2\"></td></tr>";
            $mode = '2';
            $bouton = "Sauvegarder";
        } else {
            $mode = '0';
            $bouton = "Exporter";
        }
        form_typeactes_communes($mode);
        echo " <tr><td colspan=\"2\"></td></tr>";
        if ($Destin == "B") {
            $Max_mega = $Max_size / 1024 / 1024;
            echo "<tr>";
            echo '<td>Taille maximale : </td>';
            echo '<td><input type="text" name="maxmega" value="' . $Max_mega . '"size="2">  Mb';
            echo '</td>';
        } else {
            echo "<tr>";
            echo '<td>Déposant : </td>';
            echo '<td>';
            if ($session->get('user')['level'] < 8) {
                echo '<input type="hidden" name="olddepos" value="0">';
            } else {
                listbox_users("olddepos", 0, $config->get('DEPOSANT_LEVEL'), ' -- Tous -- ');
            }
            echo '</td>';
            echo "</tr>";

            echo "<tr>";
            echo '<td>Années : </td>';
            echo '<td>';
            echo ' de <input type="text" name="AnneeDeb" size="4" maxlength="4"> ';
            echo ' à  <input type="text" name="AnneeFin" size="4" maxlength="4"> (ces années comprises)';
            echo '</td>';
            echo "</tr>";

            echo "<tr>";
            echo '<td>Destination : </td>';
            echo '<td>';
            echo '<br>';
            echo '<input type="radio" name="Destin" value="E" checked>Ecran <br>';
            echo '<input type="radio" name="Destin" value="T">Fichier TXT téléchargé directement<br>';
            //echo '<input type="radio" name="Destin" value="B">Fichier BACKUP sur le serveur<br>';
            echo '<br>';
            echo '</td>';
            echo "</tr>";

            echo "<tr>";
            echo '<td>Format : </td>';
            echo '<td>';
            echo '<br>';
            echo '<input type="radio" name="Format" value="NIM2"> Nimègue V2<br>';
            echo '<input type="radio" name="Format" value="NIM3" checked> Nimègue V3<br>';
            echo '<br>';
            echo '</td>';
        }
        echo "</tr>";

        echo "<tr>";
        echo '<td>Maximum d\'actes sélectionnés par requête <br>(voir <a target="_blank" href="' . 'gest_params.php?grp=Système">MAX_SELECT_REC</a>) 0 = pas de limite : </td>';
        echo '<td><input type="text" name="maxrecord" value="' . $max_select_rec_i . '" size="6"> ';
        echo '</td>';
        echo "</tr>";

        echo "<tr><td></td><td>";
        echo '<input type="hidden" name="action" value="go">';
        echo '<button type="reset" class="btn">Annuler</button>';
        echo '<button type="submit" class="btn">' . $bouton . '</button>';
        echo "</td></tr>";
        echo "</table>";
        echo "</form>";
    }
    if ($htmlpage) {
        echo '</div>';
        echo '</div>';
        include(__DIR__ . '/../templates/front/_footer.php');
        return (ob_get_clean());
    }
