<?php

use Symfony\Component\HttpFoundation\RedirectResponse;

if (!$userAuthorizer->isGranted(9)) {
    $response = new RedirectResponse("$root/admin/");
    $response->send();
    exit();
}

$xcomm = "";
$xpatr = "";
$xord  = getparam('xord', 'N'); // N = Nom
$page  = getparam('page', 1);
$init  = getparam('init');

$menu_user_active = 'L';
$initiale = '';
$pagination = '';

ob_start();
open_page($config->get('SITENAME') . " : Liste des utilisateurs enregistrés", $root); ?>
<div class="main">
    <?php zone_menu(10, $session->get('user')['level']); ?>
    <div class="main-col-center text-center">
        <?php
        navadmin($root, "Liste des utilisateurs");

        require(__DIR__ . '/../templates/admin/_menu-user.php');
        echo '<h2>Utilisateurs enregistrés du site ' . $config->get('SITENAME') . '</h2>';

        if (isset($udbname)) {
            msg('ATTENTION : Base des utilisateurs déportée sur ' . $udbaddr . "/" . $udbuser . "/" . $udbname . "/" . $config->get('EA_UDB') . "</p>", 'info');
        }

        //$sql = "SELECT DISTINCT upper(left(NOM,1)) AS init FROM ".EA_UDB."_user3 ORDER BY init";
        // Sélectionner et grouper sur initiale utilisateur et ascii(initiale), ordonner code ascii ascendant pour avoir + grand code (accentué) en dernier
        $sql = "SELECT alphabet.init FROM (SELECT upper(left(NOM,1)) AS init,ascii(upper(left(NOM,1))) AS oo FROM " . $config->get('EA_UDB') . "_user3 GROUP BY init,oo  ORDER BY init , oo ASC) AS alphabet GROUP BY init";

        $result = EA_sql_query($sql, $u_db);
        $alphabet = "";
        while ($row = EA_sql_fetch_row($result)) {
            if ($row[0] == $init) {
                $alphabet .= '<b>' . $row[0] . '</b> ';
            } else {
                $alphabet .= '<a href="' . $root . '/admin/utilisateurs?xord=' . $xord . '&amp;init=' . $row[0] . '">' . $row[0] . '</a> ';
            }
        }
        echo '<p align="center">' . $alphabet . '</p>';

        if ($init != "") {
            $initiale = '&amp;init=' . $init;
        }

        $hlogin = '<a href="' . $root . '/admin/utilisateurs?xord=L' . $initiale . '">Login</a>';
        $hnoms  = '<a href="' . $root . '/admin/utilisateurs?xord=N' . $initiale . '">Nom</a>';
        $hid    = '<a href="' . $root . '/admin/utilisateurs?xord=I' . $initiale . '">ID</a>';
        $hacces = '<a href="' . $root . '/admin/utilisateurs?xord=A' . $initiale . '">Niveau d\'accès</a>';
        $hstatu = '<a href="' . $root . '/admin/utilisateurs?xord=S' . $initiale . '">Statut</a>';
        $hsolde = '<a href="' . $root . '/admin/utilisateurs?xord=D' . $initiale . '">Solde</a>';
        $hrecha = '<a href="' . $root . '/admin/utilisateurs?xord=R' . $initiale . '">Rechargé</a>';
        $hconso = '<a href="' . $root . '/admin/utilisateurs?xord=C' . $initiale . '">Consommés</a>';
        $baselink = $root . '/admin/utilisateurs?xord=' . $xord . $initiale;

        switch ($xord) {
            case "L":
                $order = "LOGIN, NOM";
                $hlogin = '<b>Login</b>';
                break;
            case "A":
                $order = "LEVEL DESC";
                $hacces = '<b>Niveau d\'accès</b>';
                break;
            case "I":
                $order = "ID DESC";
                $hid = '<b>ID</b>';
                break;
            case "S":
                $order = "find_in_set(STATUT,'W,A,B,N,X')";
                $hstatu = '<b>Statut</b>';
                break;
            case "D":
                $order = "SOLDE DESC, REGIME ASC";
                $hsolde = '<b>Solde</b>';
                break;
            case "R":
                $order = "MAJ_SOLDE DESC";
                $hrecha = '<b>Rechargé</b>';
                break;
            case "C":
                $order = "PT_CONSO DESC";
                $hconso = '<b>Consommés</b>';
                break;
            case "N":
            default:
                $order = "NOM, PRENOM, LOGIN";
                $hnoms = '<b>Nom</b>';
        }

        if ($init == "") {
            $condit = "";
        } else {
            $condit = " WHERE NOM LIKE '" . $init . "%' ";
        }

        $sql = "SELECT NOM, PRENOM, LOGIN, LEVEL, ID, EMAIL, REGIME, SOLDE, MAJ_SOLDE, if(STATUT='N',if(dtexpiration<'" . date("Y-m-d", time()) . "','X',STATUT),STATUT) AS STATUT, PT_CONSO"
            . " FROM " . $config->get('EA_UDB') . "_user3 "
            . $condit
            . " ORDER BY " . $order;
        $result = EA_sql_query($sql, $u_db);
        $nbtot = EA_sql_num_rows($result);

        $limit = "";
        if ($limit <> "") {
            $sql = $sql . $limit;
            $result = EA_sql_query($sql, $u_db);
            $nb = EA_sql_num_rows($result);
        } else {
            $nb = $nbtot;
        }

        $pagination = pagination($nbtot, $page, $baselink, $pagination, $limit);

        if ($nb > 0) {
            $i = 1 + ($page - 1) * $config->get('MAX_PAGE_ADM');
            echo '<p>' . $pagination . '</p>';
            echo '<table class="m-auto" summary="Liste des utilisateurs">';
            echo '<tr class="rowheader">';
            echo '<th> Tri : </th>';
            echo '<th>' . $hlogin . '</th>';
            echo '<th>' . $hid . '</th>';
            echo '<th>' . $hnoms . '</th>';
            echo '<th>' . $hacces . '</th>';
            echo '<th>' . $hstatu . '</th>';
            if ($config->get('GEST_POINTS') > 0) {
                echo '<th>' . $hsolde . '</th>';
                echo '<th>' . $hrecha . '</th>';
                echo '<th>' . $hconso . '</th>';
            }
            echo '<th> </th>';
            echo '</tr>';


            while ($ligne = EA_sql_fetch_row($result)) {
                echo '<tr class="row' . (fmod($i, 2)) . '">';
                echo '<td>' . $i . '. </td>';
                echo '<td>' . $ligne[2] . ' </td>';
                echo '<td>' . $ligne[4] . ' </td>';
                $lenom = $ligne[0] . ' ' . $ligne[1];
                if (trim($lenom) == "") {
                    $lenom = '&lt;non précisé&gt;';
                }
                echo '<td><a href="' . $root . '/admin/utilisateurs/detail?id=' . $ligne[4] . '">' . $lenom . '</a> </td>';
                echo '<td align="center">' . $ligne[3] . '</td>';
                $ast = array("W" => "A activer", "A" => "A approuver", "N" => "Normal", "B" => "*Bloqué*", "X" => "*Expiré*");

                echo '<td align="center">' . $ast[$ligne[9]] . '</td>';
                if ($config->get('GEST_POINTS') > 0) {
                    if ($ligne[3] >= 8 or $ligne[6] == 0) {
                        echo '<td colspan=2 align="center">* Libre accès *</td>';
                    } else {
                        echo '<td align="center">' . $ligne[7] . '</td>';
                        echo '<td>' . date("d-m-Y", strtotime($ligne[8])) . '</td>';
                    }
                    echo '<td align="center">' . $ligne[10] . '</td>';
                }
                echo '<td>';
                if ($ligne[5] <> "") {
                    echo '<a href="mailto:' . $ligne[5] . '">e-mail</a>';
                }
                echo '</td>';
                echo '</tr>';
                $i++;
            }
            echo '</table>';
            echo '<p>' . $pagination . '</p>';
        } else {
            echo '<p>Aucun utilisateur enregistré</p>';
        } ?>
    </div>
</div>
<?php include(__DIR__ . '/../templates/front/_footer.php');
return (ob_get_clean());
