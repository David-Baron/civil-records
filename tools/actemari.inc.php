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
$xid = $request->get('xid');
$ctrlcod = $request->get('xct');
$xcomm = $xpatr = $page = "";

$sql = "SELECT * FROM " . $config->get('EA_DB') . "_mar3 WHERE ID=" . $xid;

if ($stmt = EA_sql_query($sql)) {
    $document = EA_sql_fetch_array($stmt);
} else {
    // Id not found 'Identifiant incorrect'
    $response = new RedirectResponse("$root/tab_mari.php");
    $response->send();
    exit();
}

$title = "Mariage : " . $document["NOM"] . " " . $document["PRE"] . " x " . $document["C_NOM"] . " " . $document["C_PRE"];
$avertissement = "";

$xcomm = $document['COMMUNE'] . ' [' . $document['DEPART'] . ']';
if (solde_ok(1, $document["DEPOSANT"], 'M', $xid) > 0) {
    ob_start();
    open_page($title, $root); ?>
    <div class="main">
        <?php zone_menu(ADM, $session->get('user')['level']); ?>
        <div class="main-col-center text-center">
        <?php
        navigation($root, ADM + 4, 'M', $xcomm, $document["NOM"], $document["PRE"]);

        echo '<h2>Acte de mariage</h2>';
        echo '<table class="m-auto" summary="Fiche détaillée">';

        show_item3($document, 0, 5, 2003, $root .'/tab_mari.php?xcomm=' . $xcomm);  // Commune
        show_item3($document, 1, 0, 2002);  // Code INSEE
        show_item3($document, 0, 4, 2005);  // Departement
        show_item3($document, 1, 0, 2004);  // Code Departement

        show_item3($document, 1, 4, 2007);  // date de l'acte

        show_grouptitle3($document, 0, 5, 'M', 'D1'); // Epoux
        show_item3($document, 1, 4, 2011, $root .'/tab_mari.php?xcomm=' . $xcomm. '&xpatr='. $document["NOM"], 2012); // Nom et prénom de l'époux

        show_item3($document, 1, 0, 2013);  // Origine
        show_item3($document, 1, 0, 2014);  // Date naiss
        show_item3($document, 1, 0, 2015);  // Age
        show_item3($document, 1, 0, 2016);  // Commentaire
        show_item3($document, 1, 0, 2017);  // profession
        show_item3($document, 1, 0, 2018, '', 2019);  // veuf de
        show_item3($document, 2, 0, 2020); // commentaire

        show_grouptitle3($document, 1, 5, 'M', 'D2');  // Parents
        show_item3($document, 2, 0, 2021, '', 2022);  // Père
        show_item3($document, 3, 0, 2024);  // Profession
        show_item3($document, 3, 0, 2023);  // Commentaire

        show_item3($document, 2, 0, 2025, '', 2026);  // Mère
        show_item3($document, 3, 0, 2028);  // Profession
        show_item3($document, 3, 0, 2027);  // Commentaire

        show_grouptitle3($document, 0, 5, 'M', 'F1');  // Epouse
        show_item3($document, 1, 4, 2029, $root . '/tab_mari.php?xcomm=' . $xcomm . '&xpatr='. $document["C_NOM"], 2030); // Nom et prénom de l'épouse
        show_item3($document, 1, 0, 2031);  // Origine
        show_item3($document, 1, 0, 2032);  // Date naiss
        show_item3($document, 1, 0, 2033);  // Age
        show_item3($document, 1, 0, 2034);  // Commentaire
        show_item3($document, 1, 0, 2035);  // profession
        show_item3($document, 1, 0, 2036, '', 2037);  // veuve de
        show_item3($document, 2, 0, 2038); // commentaire

        show_grouptitle3($document, 1, 5, 'M', 'F2');  // Parents
        show_item3($document, 2, 0, 2039, '', 2040);  // Père
        show_item3($document, 3, 0, 2042);  // Profession
        show_item3($document, 3, 0, 2041);  // Commentaire

        show_item3($document, 2, 0, 2043, '', 2044);  // Mère
        show_item3($document, 3, 0, 2046);  // Profession
        show_item3($document, 3, 0, 2045);  // Commentaire

        show_grouptitle3($document, 0, 5, 'M', 'T1');  // Témoins
        show_item3($document, 0, 0, 2047, '', 2048);  // témoin 1
        show_item3($document, 1, 0, 2049);
        show_item3($document, 0, 0, 2050, '', 2051);  // témoin 2
        show_item3($document, 1, 0, 2052);
        show_item3($document, 0, 0, 2053, '', 2054);  // témoin 3
        show_item3($document, 1, 0, 2055);
        show_item3($document, 0, 0, 2056, '', 2057);  // témoin 4
        show_item3($document, 1, 0, 2058);

        show_grouptitle3($document, 0, 5, 'M', 'V1');  // Références
        show_item3($document, 0, 0, 2059, "", "", "1");  // Autres infos + Links ,
        show_item3($document, 0, 0, 2009, "", "", "1");  // Cote
        show_item3($document, 0, 0, 2010, "", "", "1");  // Libre (images)
        show_item3($document, 0, 0, 2069, "", "", "2");  // Photos (links ;)

        show_grouptitle3($document, 0, 5, 'M', 'W1');  // Crédits
        show_item3($document, 0, 2, 2064);  // Photographe
        show_item3($document, 0, 2, 2065);  // Releveur
        show_item3($document, 0, 2, 2066);  // Vérificateur
        show_deposant3($document, 0, 2, 2063, $xid, "M"); // Deposant (+corrections)

        show_grouptitle3($document, 0, 5, 'M', 'X0');  // Gestion
        show_item3($document, 0, 2, 2061);  // Date interne
        show_item3($document, 0, 2, 2067);  // DtDepot
        if ($document["DTDEPOT"] <> $document["DTMODIF"]) {
            show_item3($document, 0, 2, 2068);  // Date modif
        }
        if (ADM <> 10) {
            show_signal_erreur('M', $xid, $ctrlcod);
        }

        echo '</table>';
        if ($avertissement <> "") {
            echo '<p><b>' . $avertissement . '</b></p>';
        }
    } else {
        ob_start();
        open_page($title, $root);
        msg($avertissement);
    }

    echo '</div>';
    echo '</div>';
    include(__DIR__ . '/../templates/front/_footer.php');
    $response->setContent(ob_get_clean());
    $response->send();
