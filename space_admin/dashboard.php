<?php

use Symfony\Component\HttpFoundation\RedirectResponse;

if (! defined('EA_TYPE_SITE')) define('EA_TYPE_SITE', 'ACTES'); // Compatibility only

if (!$userAuthorizer->isGranted(6)) {
    $response = new RedirectResponse("$root/");
    $response->send();
    exit();
}

$xtyp  = $request->get('xtyp', 'A');
$init  = $request->get('init', '');
$missingargs = true;
$emailfound = false;
$oktype = false;
$cptact = 0;
$cptfil = 0;


$menu_actes = "";
$menu_actes .= '<a href="' . $root . '/admin/tableau_de_bord?xtyp=N"' . ($xtyp == "N" ? ' class="bolder"' : '') . '>Naissances</a>';
$menu_actes .= ' | <a href="' . $root . '/admin/tableau_de_bord?xtyp=M"' . ($xtyp == "M" ? ' class="bolder"' : '') . '>Mariages</a>';
$menu_actes .= ' | <a href="' . $root . '/admin/tableau_de_bord?xtyp=D"' . ($xtyp == "D" ? ' class="bolder"' : '') . '>Décès</a>';
$menu_actes .= ' | <a href="' . $root . '/admin/tableau_de_bord?xtyp=V"' . ($xtyp == "V" ? ' class="bolder"' : '') . '>Divers</a>';
$menu_actes .= ' | <a href="' . $root . '/admin/tableau_de_bord?xtyp=A"' . ($xtyp == "A" ? ' class="bolder"' : '') . '>Tous</a>';


/**
 * @deprecated Because: min version need to be checked on install and version up is backward compatible...
 * if ((! check_version(phpversion(), "5.1.0")) or (! check_version("8.1.999", phpversion()))) {
 *  echo '<p class="erreur">Vous utilisez ExpoActes sur une version de PHP ' . phpversion() . ' non validée.<br /></p>';
 * } 
 */
/**
 * @deprecated Because: expoactes system
 * if (!check_version(EA_VERSION, $newvers)) {
 *  echo '<p class="erreur">La version ' . $newvers . ' du logiciel Expoactes est maintenant disponible <br>';
 *  echo 'et peut être téléchargée sur le site <a href="' . SITE_INVENTAIRE . '">' . SITE_INVENTAIRE . '</a><p>';
 * }
 *
 * switch (substr($status_inv, 0, 1)) {
 *  case 'l': // site "localhost" RAS
 *       break;
 *  case '-': // site  Pas Actif&Publié
 *  case 'N': // site Pas dans l'inventaire
 *        echo '<p class="erreur">Votre site n\'est pas enregistr&eacute; dans l\'inventaire, vous pouvez demander &agrave; l\'inscrire (si minimum 2000 actes) : <br /> ';
 *        echo '<a href="' . SITE_INVENTAIRE . '">' . SITE_INVENTAIRE . '</a><p>';
 *        break;
 *  case 'i': // version inconnue : Information de la suppression de l'inventaire (ne devrait pas arriver)
 *        echo '<p class="erreur">La version ExpoActes de votre site n\'est pas reconnue, votre site sera supprimé de l\'inventaire<br /> ';
 *        echo '<a href="' . SITE_INVENTAIRE . '">' . SITE_INVENTAIRE . '</a><p>';
 *        break;
 *  default: // Oversion => Si la version locale est supérieure à celle du programme : Information de la suppression de l'inventaire car version non reconnue/incohérente (on a le cas de genealogie23 qui remonte v3.3.0 et en plus qui a géré 2 bases avec un paramètre ?base= sauf que ce n'est pas compatible rss.php car le chargement ajoute ?all=Y  donc 2 paramètres ? ce qui ne fonctionne pas !
 *       $t = mb_substr($status_inv, 1);
 *       if (($t != '') and (! check_version($newvers, EA_VERSION))) { // local > newvers
 *           echo '<p class="erreur">La version ExpoActes de votre site ' . EA_VERSION . ' est inconnue/incoh&eacute;rente n\'assurant pas la compatibilit&eacute;, votre site sera supprimé de l\'inventaire<br /> ';
 *           echo '<a href="' . SITE_INVENTAIRE . '">' . SITE_INVENTAIRE . '</a><p>';
 *       }
 * }
 *
 * if (((SITE_INVENTAIRE !== '') and ((substr(SITE_INVENTAIRE, 0, 7) == 'http://') or  (substr(SITE_INVENTAIRE, 0, 8) == 'https://')))) {
 *    $t = check_new_version("EXPOACTES", SITE_INVENTAIRE, EA_TYPE_SITE);
 *    $t = explode('|', $t . '|l');
 *    $newvers = $t[0];
 *    $status_inv = $t[1];
 * } else {
 *    $newvers = EA_VERSION;
 *    $status_inv = 'l';
 * }
 */

ob_start();
open_page("Administration des actes", $root); ?>
<div class="main">
    <?php zone_menu(10, $session->get('user')['level']); ?>
    <div class="main-col-center text-center">
        <?php
        navadmin($root, '');

        echo '<h1 align="center">Administration des actes &amp; tables</h1>';
        echo '<p><b>' . $menu_actes . '</b></p>';

        include(__DIR__ . '/../tools/tableau_index.php');

        // verification des statistiques
        $sql = "SELECT sum(NB_TOT) AS nb_sum FROM " . $config->get('EA_DB') . "_sums WHERE TYPACT='N'";
        $result = EA_sql_query($sql);
        $row = EA_sql_fetch_row($result);
        $nb_sum = $row[0];
        $sql = "SELECT count(*) AS nb_cnt FROM " . $config->get('EA_DB') . "_nai3";
        $result = EA_sql_query($sql);
        $row = EA_sql_fetch_row($result);
        $nb_cnt = $row[0];
        if ($nb_sum <> $nb_cnt and $nb_cnt > 0) {
            msg("Attention : les statistiques doivent être recalculées");
            echo '<p><a href="' . $root . '/admin/actes/statistiques"><b>Calcul des statistiques</b></a></p>';
        } ?>
    </div>
</div>
<?php include(__DIR__ . '/../templates/front/_footer.php');
return (ob_get_clean());
