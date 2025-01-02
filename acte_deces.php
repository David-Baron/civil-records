<?php

use CivilRecords\Model\DocumentDeathModel;
use Symfony\Component\HttpFoundation\RedirectResponse;

require(__DIR__ . '/src/bootstrap.php');

if ($config->get('PUBLIC_LEVEL') < 4 && !$userAuthorizer->isGranted(4)) {
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

$documentDeathModel = new DocumentDeathModel();
$row = $documentDeathModel->findId($xid);

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

$title = "Décès : " . $row["NOM"] . " " . $row["PRE"];
$xcomm = $row['COMMUNE'] . ' [' . $row['DEPART'] . ']';

ob_start();
open_page($title, $root); ?>
<div class="main">
    <?php zone_menu(0, $session->get('user', ['level' => 0])['level']); ?>
    <div class="main-col-center text-center">
        <?php
        navigation($root, 4, 'D', $xcomm, $row["NOM"], $row["PRE"]);
        // Afficher l acte
        echo '<h2>Acte de décès/sépulture</h2>';
        echo '<table class="m-auto" summary="Fiche détaillée">';

        show_item3($row, 0, 5, 3003, $root . '/tab_deces.php?xcomm=' . $xcomm);  // Commune
        show_item3($row, 1, 0, 3002);  // Code INSEE
        show_item3($row, 0, 4, 3005);  // Departement
        show_item3($row, 1, 0, 3004);  // Code Departement

        show_grouptitle3($row, 0, 5, 'D', 'D1'); // Décédé
        show_item3($row, 1, 4, 3011, $root . '/tab_deces.php?xcomm=' . $xcomm . '&xpatr=' . $row["NOM"], 3012); // Nom et prénom é
        show_item3($row, 1, 4, 3007);  // date de l'acte

        show_item3($row, 1, 0, 3013); // origine
        show_item3($row, 1, 0, 3014); // date de naissance
        show_item3($row, 1, 0, 3015); // sexe
        show_item3($row, 1, 0, 3016); // age
        show_item3($row, 1, 0, 3017); // commentaire
        show_item3($row, 1, 0, 3018); // profession

        show_grouptitle3($row, 1, 5, 'D', 'D2'); // Parents
        show_item3($row, 2, 0, 3019, '', 3020);  // Père
        show_item3($row, 3, 0, 3022);  // Profession
        show_item3($row, 3, 0, 3021); // Commentaire

        show_item3($row, 2, 0, 3023, '', 3024); // Mère
        show_item3($row, 3, 0, 3026);  // Profession
        show_item3($row, 3, 0, 3025);  // Commentaire

        show_grouptitle3($row, 0, 5, 'D', 'F1'); // Conjoint
        show_item3($row, 1, 0, 3027, '', 3028);  // conjoint
        show_item3($row, 1, 0, 3030);  // Profession
        show_item3($row, 1, 0, 3029); // Commentaire

        show_grouptitle3($row, 0, 5, 'D', 'T1'); // Témoins
        show_item3($row, 0, 0, 3031, '', 3032);  // Témoin 1
        show_item3($row, 1, 0, 3033);  // Commentaire
        show_item3($row, 0, 0, 3034, '', 3035);  // Témoin 2
        show_item3($row, 1, 0, 3036);  // Commentaire

        show_grouptitle3($row, 0, 5, 'D', 'V1');  // Références
        show_item3($row, 0, 0, 3037, "", "", "1");  // Autres infos + Links
        show_item3($row, 0, 0, 3009, "", "", "1");  // Cote
        show_item3($row, 0, 0, 3010, "", "", "1");  // Libre (images)
        show_item3($row, 0, 0, 3047, "", "", "2");  // Photos (links)

        show_grouptitle3($row, 0, 5, 'D', 'W1');  // Crédits
        show_item3($row, 0, 2, 3042);  // Photographe
        show_item3($row, 0, 2, 3043);  // Releveur
        show_item3($row, 0, 2, 3044);  // Vérificateur
        show_deposant3($row, 0, 2, 3041, $xid, "D"); // Deposant (+corrections)

        show_grouptitle3($row, 0, 5, 'D', 'X0');  // Gestion
        show_item3($row, 0, 2, 3039);  // Date interne
        show_item3($row, 0, 2, 3045);  // DtDepot
        if ($row["DTDEPOT"] <> $row["DTMODIF"]) {
            show_item3($row, 0, 2, 3046);  // Date modif
        }

        if ($userAuthorizer->isGranted(6)) {
            show_signal_erreur('D', $xid);
        } ?>
        </table>
    </div>
</div>
<?php
include(__DIR__ . '/templates/front/_footer.php');
$response->setContent(ob_get_clean());
$response->send();
