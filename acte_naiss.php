<?php

use CivilRecords\Model\DocumentBirthModel;
use Symfony\Component\HttpFoundation\RedirectResponse;

require(__DIR__ . '/src/bootstrap.php');

if ($config->get('PUBLIC_LEVEL') < 4 && !$userAuthorizer->isGranted(4)) {
    // TODO: need to log error here
    $session->getFlashBag()->add('warning', 'Vous n\'êtes pas connecté ou vous n\'avez pas les autorisations nécessaires!');
    $response = new RedirectResponse($session->get('previous_url', "$root/"));
    $response->send();
    exit();
}

$TIPlevel = 1;
$xid = $request->get('xid');
$ctrlcod = $request->get('xct');
$xcomm = $request->get('xcomm');
$xpatr = $request->get('xpatr');

$documentBirthModel = new DocumentBirthModel();
$row = $documentBirthModel->findId($xid);

if (!$row) {
    // TODO: need to log error here and This will be a new Response 404
    $session->getFlashBag()->add('danger', 'Le document auquel vous tentez d\'acceder n\'est pas ou plus disponible sur ce serveur!');
    $response = new RedirectResponse($session->get('previous_url', "$root/"));
    $response->send();
    exit();
}

if (solde_ok(1, $row["DEPOSANT"], 'V', $xid) == 0) {
    $session->getFlashBag()->add('danger', 'Votre solde de points est épuisé!');
    $response = new RedirectResponse($session->get('previous_url', "$root/"));
    $response->send();
    exit();
}

$title = "Naissance : " . $row["NOM"] . " " . $row["PRE"];
$xcomm = $row['COMMUNE'] . ' [' . $row['DEPART'] . ']';

ob_start();
open_page($title, $root); ?>
<div class="main">
    <?php zone_menu(0, $session->get('user', ['level' => 0])['level']); ?>
    <div class="main-col-center text-center">
        <?php
        navigation($root, 4, 'N', $xcomm, $row["NOM"], $row["PRE"]);

        echo '<h2>Acte de naissance/baptême</h2>';
        echo '<table class="m-auto" summary="Fiche détaillée">';

        show_item3($row, 0, 5, 1003, $root . '/tab_naiss.php?xcomm=' . $xcomm);  // Commune
        show_item3($row, 1, 0, 1002);  // Code INSEE
        show_item3($row, 0, 4, 1005);  // Departement
        show_item3($row, 1, 0, 1004);  // Code Departement

        show_grouptitle3($row, 0, 5, 'N', 'D1'); // Nouveau né
        show_item3($row, 0, 4, 1011, $root . '/tab_naiss.php?xcomm=' . $xcomm. '&xpatr=' .$row["NOM"], 1012); // Nom et prénom du Nouveau-né
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

        if ($userAuthorizer->isGranted(6)) {
            show_signal_erreur('N', $xid);
        } ?>
        </table>
    </div>
</div>
<?php
include(__DIR__ . '/templates/front/_footer.php');
$response->setContent(ob_get_clean());
$response->send();
