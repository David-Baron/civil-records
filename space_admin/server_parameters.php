<?php

use Symfony\Component\HttpFoundation\RedirectResponse;

if (!$userAuthorizer->isGranted(9)) {
    $response = new RedirectResponse("$root/admin/");
    $response->send();
    exit();
}

function paspoint($string)
{
    $x = strpos($string, ":");
    if ($x > 0) {
        return mb_substr($string, $x + 1);
    }

    return "";
}


/** @deprecated */
if ($request->query->has('maintenance')) {
    // dd($request->query->get('maintenance'));
    $sql = "UPDATE " . $config->get('EA_DB') . "_params SET valeur=" . $request->query->get('maintenance') . " WHERE param='EA_MAINTENANCE'";
    EA_sql_query($sql);

    $response = new RedirectResponse("$root/admin/serveur/parametres");
    $response->send();
    exit();
}

// paramètres du serveur MySQL
$status = explode('  ', EA_sql_stat($db));
$maxcon = val_var_mysql('max_user_connections');
if (val_var_mysql('max_user_connections') == 0) {
    $maxcon = val_var_mysql('max_connections');
}

$menu_software_active = 'E';

ob_start();
open_page("Paramètres serveur", $root); ?>
<div class="main">
    <?php zone_menu(10, $session->get('user')['level']); ?>
    <div class="main-col-center">
        <?php
        navadmin($root, "Paramètres serveur");

        require(__DIR__ . '/../templates/admin/_menu-software.php'); ?>
        <hr>
        <div class="p-2">
            <h2>Mode Maintenance : </h2>
            <?php if ((int) $config->get('EA_MAINTENANCE') == 1) { ?>
                <p class="danger">
                    <b> L'accès limité aux administrateurs.</b>
                    <a href="/admin/serveur/parametres?maintenance=0"><img src="/themes/img/shutdown.png" width="24px"></a>
                </p>
            <?php } else { ?>
                <p class="success">
                    <b>Le site est ouvert à la consultation.</b>
                    <a href="/admin/serveur/parametres?maintenance=1"><img src="/themes/img/power-on.png" width="24px"></a>
                </p>
            <?php } ?>
        </div>
        <div class="p-2">
            <h2>Informations sur le serveur web</h2>
            <p>Version du serveur PHP : <b><?= phpversion(); ?></b></p>
            <!-- <p>Type du serveur : <b><?= php_uname(); ?></b></p> -->
        </div>
        <div class="p-2">
            <h2>Informations sur le serveur MySQL (base de données)</h2>
            <p>Version du serveur MySQL : <b><?= EA_sql_get_server_info(); ?></b></p>
        </div>
        <div class="p-2">
            <h3>Etat du serveur</h3>
            <p>Serveur MySQL en fonctionnement depuis : <?= heureminsec(paspoint($status[0])); ?></p>
            <?php if (isset($status[7])) { ?>
                <p>Nombre moyen de requêtes par sec (tous clients confondus) : <?= paspoint($status[7]); ?></p>
            <?php } ?>
        </div>
        <div class="p-2">
            <h3>Paramètres du serveur</h3>
            <p>Temps limite pour l'exécution des requêtes (sec) : <?= val_var_mysql('wait_timeout'); ?></p>
            <p>Temps limite pour les lectures (sec) : <?= val_var_mysql('net_read_timeout'); ?></p>
            <p>Temps limite pour les écritures (sec) : <?= val_var_mysql('net_write_timeout'); ?></p>
            <p>Nombre maximal de connexions simultannées globalement : <?= val_var_mysql('max_connections'); ?></p>
            <p>Nombre maximal de connexions simultannées pour vous : <?= $maxcon; ?></p>

            <?php if (file_exists(__DIR__ . '/serv_params_accents.inc.php')) {
                include(__DIR__ . '/serv_params_accents.inc.php');
            } ?>
        </div>
        <div class="p-2">
            <h2>Informations sur le géocodage (via Google Maps)</h2>
            <?php test_geocodage(true); ?>
        </div>
    </div>
</div>
<?php include(__DIR__ . '/../templates/front/_footer.php');
return (ob_get_clean());
