<?php

use CivilRecords\Domain\DeedMarriageModel;
use Symfony\Component\HttpFoundation\RedirectResponse;

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

$deedMarriageModel = new DeedMarriageModel();
$row = $deedMarriageModel->findId($xid);

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

$title = "Mariage : " . $row["NOM"] . " " . $row["PRE"] . " x " . $row["C_NOM"] . " " . $row["C_PRE"];
$xcomm = $row['COMMUNE'] . ' [' . $row['DEPART'] . ']';

ob_start();
open_page($title, $root); ?>
<div class="main">
    <?php zone_menu(0, $session->get('user', ['level' => 0])['level']); ?>
    <div class="main-col-center text-center">
        <?php
        navigation($root, 4, 'M', $xcomm, $row["NOM"], $row["PRE"]);

        echo '<h2>Acte de mariage</h2>';
        echo '<table class="m-auto" summary="Fiche détaillée">';

        show_item3($row, 0, 5, 2003, $root . '/actes/mariages?xcomm=' . $xcomm);  // Commune COMMUNE
        show_item3($row, 1, 0, 2002);  // Code INSEE CODCOM
        show_item3($row, 0, 4, 2005);  // Departement DEPART
        show_item3($row, 1, 0, 2004);  // Code Departement CODDEP
        show_item3($row, 1, 4, 2007);  // date de l'acte DATETXT

        # show_grouptitle3($row, 0, 5, 'M', 'D1'); // Epoux NOM + PRE
        echo '<tr>';
        echo '<td class="fich2 bolder">' . trans('Husband') . '</td>';
        echo '<td class="fich1"></td>';
        echo '</tr>';
        show_item3($row, 1, 4, 2011, $root . '/actes/mariages?xcomm=' . $xcomm . '&xpatr=' . $row["NOM"], 2012); // Nom et prénom de l'époux

        show_item3($row, 1, 0, 2013);  // Origine ORI
        show_item3($row, 1, 0, 2014);  // Date naiss DNAIS
        show_item3($row, 1, 0, 2015);  // Age AGE
        show_item3($row, 1, 0, 2016);  // Commentaire COM
        show_item3($row, 1, 0, 2017);  // profession PRO
        show_item3($row, 1, 0, 2018, '', 2019);  // veuf de
        show_item3($row, 2, 0, 2020); // commentaire

        # show_grouptitle3($row, 1, 5, 'M', 'D2');  // Parents
        echo '<tr>';
        echo '<td class="fich2 bolder">' . trans('Parents') . '</td>';
        echo '<td class="fich1"></td>';
        echo '</tr>';
        show_item3($row, 2, 0, 2021, '', 2022);  // Père
        show_item3($row, 3, 0, 2024);  // Profession 
        show_item3($row, 3, 0, 2023);  // Commentaire

        show_item3($row, 2, 0, 2025, '', 2026);  // Mère
        show_item3($row, 3, 0, 2028);  // Profession
        show_item3($row, 3, 0, 2027);  // Commentaire

        # show_grouptitle3($row, 0, 5, 'M', 'F1');  // Epouse
        echo '<tr>';
        echo '<td class="fich2 bolder">' . trans('Spouse') . '</td>';
        echo '<td class="fich1"></td>';
        echo '</tr>';
        show_item3($row, 1, 4, 2029, $root . '/actes/mariages?xcomm=' . $xcomm . '&xpatr=' . $row["C_NOM"], 2030); // Nom et prénom de l'épouse
        show_item3($row, 1, 0, 2031);  // Origine
        show_item3($row, 1, 0, 2032);  // Date naiss
        show_item3($row, 1, 0, 2033);  // Age
        show_item3($row, 1, 0, 2034);  // Commentaire
        show_item3($row, 1, 0, 2035);  // profession
        show_item3($row, 1, 0, 2036, '', 2037);  // veuve de
        show_item3($row, 2, 0, 2038); // commentaire

        # show_grouptitle3($row, 1, 5, 'M', 'F2');  // Parents
        echo '<tr>';
        echo '<td class="fich2 bolder">' . trans('Parents') . '</td>';
        echo '<td class="fich1"></td>';
        echo '</tr>';
        show_item3($row, 2, 0, 2039, '', 2040);  // Père
        show_item3($row, 3, 0, 2042);  // Profession
        show_item3($row, 3, 0, 2041);  // Commentaire

        show_item3($row, 2, 0, 2043, '', 2044);  // Mère
        show_item3($row, 3, 0, 2046);  // Profession
        show_item3($row, 3, 0, 2045);  // Commentaire

        # show_grouptitle3($row, 0, 5, 'M', 'T1');  // Témoins
        echo '<tr>';
        echo '<td class="fich2 bolder">' . trans('Witnesses') . '</td>';
        echo '<td class="fich1"></td>';
        echo '</tr>';
        show_item3($row, 0, 0, 2047, '', 2048);  // témoin 1
        show_item3($row, 1, 0, 2049);
        show_item3($row, 0, 0, 2050, '', 2051);  // témoin 2
        show_item3($row, 1, 0, 2052);
        show_item3($row, 0, 0, 2053, '', 2054);  // témoin 3
        show_item3($row, 1, 0, 2055);
        show_item3($row, 0, 0, 2056, '', 2057);  // témoin 4
        show_item3($row, 1, 0, 2058);

        # show_grouptitle3($row, 0, 5, 'M', 'V1');  // Références
        echo '<tr>';
        echo '<td class="fich2 bolder">' . trans('References') . '</td>';
        echo '<td class="fich1"></td>';
        echo '</tr>';
        show_item3($row, 0, 0, 2059, "", "", "1");  // Autres infos + Links ,
        show_item3($row, 0, 0, 2009, "", "", "1");  // Cote
        show_item3($row, 0, 0, 2010, "", "", "1");  // Libre (images)
        show_item3($row, 0, 0, 2069, "", "", "2");  // Photos (links ;)

        # show_grouptitle3($row, 0, 5, 'M', 'W1');  // Crédits
        echo '<tr>';
        echo '<td class="fich2 bolder">' . trans('Credits') . '</td>';
        echo '<td class="fich1"></td>';
        echo '</tr>';
        show_item3($row, 0, 2, 2064);  // Photographe
        show_item3($row, 0, 2, 2065);  // Releveur
        show_item3($row, 0, 2, 2066);  // Vérificateur
        show_deposant3($row, 0, 2, 2063, $xid, "M"); // Deposant (+corrections)

        # show_grouptitle3($row, 0, 5, 'M', 'X0');  // Gestion
        echo '<tr>';
        echo '<td class="fich2 bolder">' . trans('Management') . '</td>';
        echo '<td class="fich1"></td>';
        echo '</tr>';
        show_item3($row, 0, 2, 2061);  // Date interne
        show_item3($row, 0, 2, 2067);  // DtDepot
        if ($row["DTDEPOT"] <> $row["DTMODIF"]) {
            show_item3($row, 0, 2, 2068);  // Date modif
        }

        if (isset($_ENV['EMAIL_CORRECTOR']) && $userAuthorizer->isGranted(6)) { ?>
            <tr>
                <td class="fich2 bolder">Trouvé une erreur ?</td>
                <td class="fich1">
                <a href="<?= $root; ?>/signal_erreur?xtyp=M&xid=<?= $xid; ?>" target="_blank">Cliquez ici pour la signaler</a>
                </td>
            </tr>
        <?php } ?>
        </table>
    </div>
</div>
<?php
include(__DIR__ . '/../templates/front/_footer.php');
return (ob_get_clean());
