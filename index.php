<?php

use Symfony\Component\HttpFoundation\RedirectResponse;

require(__DIR__ . '/src/bootstrap.php');

if ($config->get('PUBLIC_LEVEL') < 4 && !$userAuthorizer->isGranted(1)) {
    $response = new RedirectResponse("$root/login.php");
    $response->send();
    exit();
}

if ($request->get('act') && $request->get('act') === 'logout') {
    $session->clear();
    $response = new RedirectResponse("$root/");
    $response->send();
    exit();
}

$xtyp = $request->get('xtyp', 'A');
$init = $request->get('init', '');
$vue = $request->get('vue', 'T'); // T = Tableau / C = Carte
$xpatr = $request->get('xpatr', '');
$page = $request->get('page', 1);
// $carto_available = (isset($_ENV['GOOGLE_API_KEY']) && null !== $_ENV['GOOGLE_API_KEY']) ? true : false;
$stylesheets = '';

if ($config->get('SHOW_ALLTYPES') != 1) $xtyp = 'N';

if ($config->get('GEO_MODE_PUBLIC') == 5 || $vue == 'C') {
    require(__DIR__ . '/tools/carto_openstreetmap.php');
}

ob_start();
open_page("Dépouillement d'actes de l'état-civil et des registres paroissiaux", $root, null, null, $stylesheets, '../index.htm', 'rss.php'); ?>
<div class="main">
    <?php $menu_actes = zone_menu(0, $session->get('user', ['level' => 0])['level']); ?>
    <div class="main-col-center text-center">
        <?php navigation($root, 1);

        if (null !== $config->get('AVERTISMT')) {
            echo '<p>' . $config->get('AVERTISMT') . '</p>';
        }
        require(__DIR__ . '/templates/front/_flash-message.php');
        echo '<h2>Communes et paroisses';
        if ($config->get('GEO_MODE_PUBLIC') >= 3 && $config->get('GEO_MODE_PUBLIC') < 5) {
            echo " : ";
            if ($vue == 'C') {
                echo 'Carte | <a href="' . $root . '/index.php?vue=T&xtyp=' . $xtyp . '"' . ($vue == 'T' ? ' class="bolder"' : '') . '>Tableau</a>';
            }

            if ($vue == 'T') {
                echo '<a href="' . $root . '/index.php?vue=C&xtyp=' . $xtyp . '"' . ($vue == 'C' ? ' class="bolder"' : '') . '>Carte</a> | Tableau';
            }
        }
        echo '</h2>';

        if ($config->get('GEO_MODE_PUBLIC') == 5 || $vue == 'C') { // si pas localité isolée et avec carte
            echo '<p><b>' . $menu_actes . '</b></p>';
            echo '<div class="container">';
            echo '<div id="map"></div>';
            echo '</div>';
        }

        if ($config->get('GEO_MODE_PUBLIC') == 5 || $vue == 'T') { // si pas localité isolée et avec carte
            // $menu_actes calculé dans le module statistiques
            echo '<p><b>' . $menu_actes . '</b></p>';
            require(__DIR__ . '/tools/tableau_index.php');
        }

        include(__DIR__ . "/templates/front/_commentaire.php"); ?>
    </div>
</div>
</div>
<?php include(__DIR__ . '/templates/front/_footer.php');
$response->setContent(ob_get_clean());
$response->send();
