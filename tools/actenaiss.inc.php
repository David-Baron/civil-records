<?php

use Symfony\Component\HttpFoundation\RedirectResponse;

$lvl = 4;
if (ADM == 10) $lvl = 5;

if (!$userAuthorizer->isGranted($lvl)) {
    $response = new RedirectResponse("$root/");
    $response->send();
    exit();
}

$TIPlevel = 1;
$error = 0;
$xcomm = $xpatr = $page = "";
$xid = $_REQUEST['xid'];
$ctrlcod = $_REQUEST['xct'];

pathroot($root, $path, $xcomm, $xpatr, $page);

$request = "SELECT * FROM " . $config->get('EA_DB') . "_nai3 WHERE ID = " . $xid;
if ($result = EA_sql_query($request) and EA_sql_num_rows($result) != 0) {
    $row = EA_sql_fetch_array($result);
} else {
    $error = 1;
}

if (($error == 1) or !($ctrlcod == ctrlxid($row["NOM"], $row["PRE"]))) {
    $error = 1;
    $title = "Erreur";
} else {
    $title = "Naissance : " . $row["NOM"] . " " . $row["PRE"];
}
$avertissement = "";
if ($error == 0) {
    $xcomm = $row['COMMUNE'] . ' [' . $row['DEPART'] . ']';
    if (solde_ok(1, $row["DEPOSANT"], 'N', $xid) > 0) {
        ob_start();
        open_page($title, $root); ?>
        <div class="main">
            <?php zone_menu(ADM, $session->get('user')['level']); ?>
            <div class="main-col-center text-center">
        <?php
        navigation($root, ADM + 4, 'N', $xcomm, $row["NOM"], $row["PRE"]);

        // Afficher l acte
        echo '<h2>Acte de naissance/baptême</h2>';

        echo '<table summary="Fiche détaillée">';
        show_item3($row, 0, 5, 1003, mkurl('tab_naiss.php', $xcomm));  // Commune
        show_item3($row, 1, 0, 1002);  // Code INSEE
        show_item3($row, 0, 4, 1005);  // Departement
        show_item3($row, 1, 0, 1004);  // Code Departement

        show_grouptitle3($row, 0, 5, 'N', 'D1'); // Nouveau né
        show_item3($row, 0, 4, 1011, mkurl('tab_naiss.php', $xcomm, $row["NOM"]), 1012); // Nom et prénom du Nouveau-né
        show_item3($row, 1, 4, 1007);  // date de l'acte

        show_item3($row, 1, 0, 1013);  // sexe
        show_item3($row, 1, 0, 1014); // commentaire

        show_grouptitle3($row, 1, 5, 'N', 'D2'); // Parents
        show_item3($row, 2, 0, 1015, '', 1016);  // Père
        show_item3($row, 3, 0, 1018);  // Profession
        show_item3($row, 3, 0, 1017); // Commentaire

        show_item3($row, 2, 0, 1019, '', 1020); // Mère
        show_item3($row, 3, 0, 1022);  // Profession
        show_item3($row, 3, 0, 1021);  // Commentaire

        show_grouptitle3($row, 0, 5, 'N', 'T1');  // Témoins
        show_item3($row, 0, 0, 1023, '', 1024);  // Témoin 1
        show_item3($row, 1, 0, 1025);  // Commentaire
        show_item3($row, 0, 0, 1026, '', 1027);  // Témoin 2
        show_item3($row, 1, 0, 1028);  // Commentaire

        show_grouptitle3($row, 0, 5, 'N', 'V1');  // Références
        show_item3($row, 0, 0, 1029, "", "", "1");  // Autres infos + Links
        show_item3($row, 0, 0, 1009, "", "", "1");  // Cote
        show_item3($row, 0, 0, 1010, "", "", "1");  // Libre (images)
        show_item3($row, 0, 0, 1039, "", "", "2");  // Photos (links)

        show_grouptitle3($row, 0, 5, 'N', 'W1');  // Crédits
        show_item3($row, 0, 2, 1034);  // Photographe
        show_item3($row, 0, 2, 1035);  // Releveur
        show_item3($row, 0, 2, 1036);  // Vérificateur
        show_deposant3($row, 0, 2, 1033, $xid, "N"); // Deposant (+corrections)

        show_grouptitle3($row, 0, 5, 'N', 'X0');  // Gestion
        show_item3($row, 0, 2, 1031);  // Date interne
        show_item3($row, 0, 2, 1037);  // DtDepot
        if ($row["DTDEPOT"] <> $row["DTMODIF"]) {
            show_item3($row, 0, 2, 1038);  // Date modif
        }

        if (ADM <> 10) {
            show_signal_erreur('N', $xid, $ctrlcod);
        }

        echo '</table>';
        if ($avertissement <> "") {
            echo '<p><b>' . $avertissement . '</b></p>' . "\n";
        }
    } else {
        ob_start();
        open_page($title, $root);
        msg($avertissement);
    }
} else {
    ob_start();
    open_page($title, $root);
    msg('Identifiant incorrect');
}
echo '</div>';
echo '</div>';
include(__DIR__ . '/../templates/front/_footer.php');
$response->setContent(ob_get_clean());
$response->send();
