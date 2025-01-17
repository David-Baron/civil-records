<?php

use CivilRecords\Domain\DeedDiversModel;
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

$deedDiversModel = new DeedDiversModel();
$row = $deedDiversModel->findId($xid);

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

$title = $row["LIBELLE"] . " : " . $row["NOM"] . " " . $row["PRE"];
$xcomm = $row['COMMUNE'] . ' [' . $row['DEPART'] . ']';

ob_start();
open_page($title, $root); ?>
<div class="main">
    <?php zone_menu(0, $session->get('user', ['level' => 0])['level']); ?>
    <div class="main-col-center text-center">
        <?php
        navigation($root, 4, 'V', $xcomm, $row["NOM"], $row["PRE"]);

        echo '<h2>' . $row["LIBELLE"] . '</h2>';
        echo '<table class="m-auto" summary="Fiche détaillée">';

        show_item3($row, 0, 5, 4003, $root . '/actes/divers?xcomm=' . $xcomm);  // Commune
        show_item3($row, 1, 0, 4002);  // Code INSEE
        show_item3($row, 0, 4, 4005);  // Departement
        show_item3($row, 1, 0, 4004);  // Code Departement

        show_item3($row, 1, 4, 4007);  // date de l'acte

        # show_grouptitle3($row, 0, 5, 'V', 'D1', $row["SIGLE"]); // Intervenant 1
        echo '<tr>';
        echo '<td class="fich2 bolder">' . trans('Person') . ' 1</td>';
        echo '<td class="fich1"></td>';
        echo '</tr>';
        show_item3($row, 1, 4, 4013, $root . '/actes/divers?xcomm=' . $xcomm . '&xpatr=' . $row["NOM"], 4014); // Intervenant 1
        show_item3($row, 1, 0, 4015);  // Sexe
        show_item3($row, 1, 0, 4016);  // Origine
        show_item3($row, 1, 0, 4017);  // Date naiss
        show_item3($row, 1, 0, 4018);  // Age
        show_item3($row, 1, 0, 4019);  // Commentaire
        show_item3($row, 1, 0, 4020);  // profession
        show_item3($row, 1, 0, 4021, '', 4022);  // veuf de
        show_item3($row, 2, 0, 4023); // commentaire

        # show_grouptitle3($row, 1, 5, 'V', 'D2', $row["SIGLE"]); // Parents
        echo '<tr>';
        echo '<td class="fich2 bolder">' . trans('Parents') . '</td>';
        echo '<td class="fich1"></td>';
        echo '</tr>';
        show_item3($row, 2, 0, 4024, '', 4025);  // Père
        show_item3($row, 3, 0, 4027);  // Profession
        show_item3($row, 3, 0, 4026);  // Commentaire

        show_item3($row, 2, 0, 4028, '', 4029);  // Mère
        show_item3($row, 3, 0, 4031);  // Profession
        show_item3($row, 3, 0, 4030);  // Commentaire

        if (trim($row["C_NOM"]) != "") {
            # show_grouptitle3($row, 0, 5, 'V', 'F1', $row["SIGLE"]); // Intervenant 2
            echo '<tr>';
            echo '<td class="fich2 bolder">' . trans('Person') . ' 2</td>';
            echo '<td class="fich1"></td>';
            echo '</tr>';
            show_item3($row, 1, 4, 4032, $root . '/actes/divers?xcomm=' . $xcomm . '&xpatr=' . $row["C_NOM"], 4033); // Intervenant 2

            show_item3($row, 1, 0, 4034);  // Sexe
            show_item3($row, 1, 0, 4035);  // Origine
            show_item3($row, 1, 0, 4036);  // Date naiss
            show_item3($row, 1, 0, 4037);  // Age
            show_item3($row, 1, 0, 4038);  // Commentaire
            show_item3($row, 1, 0, 4039);  // profession
            show_item3($row, 1, 0, 4040, '', 4041);  // veuve de
            show_item3($row, 2, 0, 4042); // commentaire

            # show_grouptitle3($row, 1, 5, 'V', 'F2', $row["SIGLE"]); // Parents
            echo '<tr>';
            echo '<td class="fich2 bolder">' . trans('Parents') . '</td>';
            echo '<td class="fich1"></td>';
            echo '</tr>';
            show_item3($row, 2, 0, 4043, '', 4044);  // Père
            show_item3($row, 3, 0, 4046);  // Profession
            show_item3($row, 3, 0, 4045);  // Commentaire

            show_item3($row, 2, 0, 4047, '', 4048);  // Mère
            show_item3($row, 3, 0, 4050);  // Profession
            show_item3($row, 3, 0, 4049);  // Commentaire
        }

        # show_grouptitle3($row, 0, 5, 'V', 'T1', $row["SIGLE"]);  // Témoins
        echo '<tr>';
        echo '<td class="fich2 bolder">' . trans('Witnesses') . '</td>';
        echo '<td class="fich1"></td>';
        echo '</tr>';
        show_item3($row, 0, 0, 4051, '', 4052);  // témoin 1
        show_item3($row, 1, 0, 4053);
        show_item3($row, 0, 0, 4054, '', 4055);  // témoin 2
        show_item3($row, 1, 0, 4056);
        show_item3($row, 0, 0, 4057, '', 4058);  // témoin 3
        show_item3($row, 1, 0, 4059);
        show_item3($row, 0, 0, 4060, '', 4061);  // témoin 4
        show_item3($row, 1, 0, 4062);

        # show_grouptitle3($row, 0, 5, 'V', 'V1');  // Références
        echo '<tr>';
        echo '<td class="fich2 bolder">' . trans('References') . '</td>';
        echo '<td class="fich1"></td>';
        echo '</tr>';
        show_item3($row, 0, 0, 4063, "", "", "1");  // Autres infos + Links
        show_item3($row, 0, 0, 4009, "", "", "1");  // Cote
        show_item3($row, 0, 0, 4010, "", "", "1");  // Libre (images)
        show_item3($row, 0, 0, 4073, "", "", "2");  // Photos (links)

        # show_grouptitle3($row, 0, 5, 'V', 'W1');  // Crédits
        echo '<tr>';
        echo '<td class="fich2 bolder">' . trans('Credits') . '</td>';
        echo '<td class="fich1"></td>';
        echo '</tr>';
        show_item3($row, 0, 2, 4068);  // Photographe
        show_item3($row, 0, 2, 4069);  // Releveur
        show_item3($row, 0, 2, 4070);  // Vérificateur
        show_deposant3($row, 0, 2, 4067, $xid, "V"); // Deposant (+corrections)

        # show_grouptitle3($row, 0, 5, 'V', 'X0');  // Gestion
        echo '<tr>';
        echo '<td class="fich2 bolder">' . trans('Management') . '</td>';
        echo '<td class="fich1"></td>';
        echo '</tr>';
        show_item3($row, 0, 2, 4065);  // Date interne
        show_item3($row, 0, 2, 4071);  // DtDepot
        if ($row["DTDEPOT"] <> $row["DTMODIF"]) {
            show_item3($row, 0, 2, 4072);  // Date modif
        }

        if (isset($_ENV['EMAIL_CORRECTOR']) && $userAuthorizer->isGranted(6)) { ?>
            <tr>
                <td class="fich2 bolder">Trouvé une erreur ?</td>
                <td class="fich1">
                <a href="<?= $root; ?>/signal_erreur?xtyp=V&xid=<?= $xid; ?>" target="_blank">Cliquez ici pour la signaler</a>
                </td>
            </tr>
        <?php } ?>
        </table>
    </div>
</div>
<?php
include(__DIR__ . '/../templates/front/_footer.php');
return (ob_get_clean());
