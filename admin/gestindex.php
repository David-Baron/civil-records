<?php
define('ADM', 10); // Compatibility only
$admtxt = 'Gestion '; // Compatibility only
require(__DIR__ . '/../next/bootstrap.php');
require(__DIR__ . '/../next/_COMMUN_env.inc.php'); // Compatibility only
require(__DIR__ . '/../tools/defindex.inc.php');

$userlogin = "";
$userlevel = logonok(9);
while ($userlevel < 9) {
    login($root);
}

pathroot($root, $path, $xcomm, $xpatr, $page);

$action = getparam('act');
$aindex = getparam('ti');
$confirm = getparam('confirm');
$tablename = getparam('tbl');

ob_start();
open_page("Gestion des index", $root);
navadmin($root, "Gestion des index");
zone_menu(ADM, $userlevel, array()); //ADMIN STANDARD
echo '<div id="col_main_adm">';
menu_software('I');

if ($action == "ADD") {
    echo '<h2>Ajout d\'un index</h2>';
    if ($confirm <> 'YES') {
        echo '<p><font color="#FF0000"><b>AVERTISSEMENT IMPORTANT :</b><br />';
        echo 'Il est hautement conseillé de ';
        echo '<a href="exporte.php?Destin=B"><b>réaliser un backup de la table</b></a> ';
        echo '<b>AVANT</b> d\'ajouter un index car si le serveur est trop chargé ou trop lent ou encore que la fenêtre de temps allouée est trop courte, la table peut devenir INUTILISABLE !';
        echo '</font></p>';
        echo '<p>Confirmez-vous la création de l\'index <b>' . $idx[$aindex][6] . '</b> sur la table des <b>' . typact_txt($idx[$aindex][0]) . '</b> ?</p>';
        echo '<p><a href="?act=ADD&amp;confirm=YES&amp;ti=' . $aindex . '"><b>Confirmer</b></a>';
        echo ' - <a href="?act=SHO"><b>Annuler</b></a></p>';
    } else {
        $reqmaj = "ALTER TABLE " . EA_DB . '_' . $idx[$aindex][0] . ' ADD INDEX ' . $idx[$aindex][1] . ' (' . $idx[$aindex][2] . ');';
        echo '<p>Création de l\'index ' . $idx[$aindex][6] . ' sur la table ' . EA_DB . '_' . $idx[$aindex][0] . '... </p>';
        $res = EA_sql_query($reqmaj);
        //echo '<p>'.$reqmaj;
        if ($res === true) {
            echo " Terminé.";
            writelog("Ajout index " . $idx[$aindex][1] . " sur " . $idx[$aindex][0]);
        } else {
            echo '<font color="#FF0000"> Erreur </font>';
            echo '<p>' . EA_sql_error() . '<br>' . $reqmaj . '</p>';
            die();
        }
        echo '<p><a href="?act=SHO"><b>Retour à la liste des index</b></a></p>';
    }
} elseif ($action == "DEL") {
    echo '<h2>Suppression d\'un index</h2>';
    if ($confirm <> 'YES') {
        echo '<p>Confirmez-vous la SUPPRESSION de l\'index <b>' . $idx[$aindex][6] . '</b> de la table des <b>' . typact_txt($idx[$aindex][0]) . '</b> ?</p>';
        echo '<p><a href="?act=DEL&amp;confirm=YES&amp;ti=' . $aindex . '"><b>Confirmer</b></a>';
        echo ' - <a href="?act=SHO"><b>Annuler</b></a></p>';
    } else {
        $reqmaj = "ALTER TABLE " . EA_DB . '_' . $idx[$aindex][0] . ' DROP INDEX ' . $idx[$aindex][1] . ';';
        echo '<p>Suppression de l\'index ' . $idx[$aindex][6] . ' de la table ' . EA_DB . '_' . $idx[$aindex][0] . '... </p>';
        $res = EA_sql_query($reqmaj);
        //echo '<p>'.$reqmaj;
        if ($res === true) {
            echo " Terminée.";
            writelog("Suppression index " . $idx[$aindex][1] . " sur " . $idx[$aindex][0]);
        } else {
            echo '<font color="FF0000"> Erreur </font>';
            echo '<p>' . EA_sql_error() . '<br>' . $reqmaj . '</p>';
            die();
        }
        echo '<p><a href="?act=SHO"><b>Retour à la liste des index</b></a></p>';
    }
} elseif ($action == "ANA") {
    echo '<h2>Analyse d\'une table</h2>';
    /*
    if ($confirm<>'YES')
            {
            echo '<p>Confirmez-vous l'ANALYSE de l\'index <b>'.$idx[$aindex][6].'</b> de la table des <b>'.typact_txt($idx[$aindex][0]).'</b> ?</p>';
            echo '<p><a href="?act=DEL&amp;confirm=YES&amp;ti='.$aindex.'"><b>Confirmer</b></a>';
            echo ' - <a href="?act=SHO"><b>Annuler</b></a></p>';
            }
        else
    */ {
        $reqmaj = "ANALYZE TABLE " . EA_DB . '_' . $tablename . ';';
        echo '<p>Analyse de la table ' . EA_DB . '_' . $tablename . '... </p>';
        $res = EA_sql_query($reqmaj);
        $tabres = EA_sql_fetch_array($res);
        echo $tabres[2] . " : " . $tabres[3];
        writelog("Analyse de " . EA_DB . '_' . $tablename . ":" . $tabres[2]);
        echo '<p><a href="?act=SHO"><b>Retour à la liste des index</b></a></p>';
    }
} else { ?>
    <h2>Index de la base MySQL</h2>
    <table summary="Liste des index actifs ou à créer">
        <tr class="rowheader">
            <th>Zone clé</th>
            <th>Cardinalité</th>
            <th>Action possible</th>
        </tr>

        <?php
        $i = -1;
        $table = "XX";
        foreach ($idx as $index) {
            $i++;
            if ($table <> $index[0]) {
                $table = $index[0];
                $res = EA_sql_query("SELECT count(*) AS NBRE FROM " . EA_DB . '_' . $table . "; ");
                $row = EA_sql_fetch_array($res);
                $totfiches = $row[0];
                ?>
                <tr class="rowheader">
                <td colspan="3"><b>Table des <?= typact_txt($table); ?> (<?= EA_DB . '_' . $table; ?> : <?= entier($totfiches); ?> lignes)</b>
                 <a href="<?= $root; ?>/admin/gestindex.php?act=ANA&amp;tbl=<?= $table; ?>"><b>Analyser</b></a>
                </td>
            </tr>
                <?php
                $res = EA_sql_query("SHOW INDEX FROM " . EA_DB . '_' . $table . "; ");
                $nbr = EA_sql_num_rows($res);
                $realindex = array();
                for ($j = 1; $j <= $nbr; $j++) {
                    $row = EA_sql_fetch_array($res);
                    $ligne = array($row[2] => $row[6]);
                    $realindex = $realindex + $ligne;
                }
            } ?>
            <tr class="row<?= fmod($i, 2); ?>">
                <td><?= $index[6]; ?></td>
                <?php if (array_key_exists($index[1], $realindex)) { ?>
                    <td><?= entier($realindex[$index[1]]); ?></td>
                    <td><a href="<?= $root; ?>/admin/gestindex.php?act=DEL&amp;ti=<?= $i; ?>">Supprimer</a></td>
                <?php } else { ?>
                    <td>Absent</td>
                    <td><a href="<?= $root; ?>/admin/gestindex.php?act=ADD&amp;ti=<?= $i; ?>"><b>Ajouter</b></a></td>
                <?php } ?>
            </tr>
        <?php } ?>
    </table>
<?php } ?>
</div>
<?php include(__DIR__ . '/../templates/front/_footer.php');
$response->setContent(ob_get_clean());
$response->send();
