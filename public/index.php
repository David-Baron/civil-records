<?php

require __DIR__ . '/../src/bootstrap.php';

$maped_routes = [
    /** FRONT */
    '/'                 => __DIR__ . '/../space_public/index.php',
    '/accueil'          => __DIR__ . '/../space_public/index.php',
    '/acces'            =>  __DIR__ . '/../space_public/acces.php',
    '/contact'          => __DIR__ . '/../space_public/contact.php',
    '/actes/divers'     =>  __DIR__ . '/../space_public/deed_divers_table.php',
    '/actes/divers/acte_details'            =>  __DIR__ . '/../space_public/deed_divers_detail.php',
    '/actes/deces'      =>  __DIR__ . '/../space_public/deed_death_table.php',
    '/actes/deces/acte_details'             =>  __DIR__ . '/../space_public/deed_death_detail.php',
    '/actes/mariages'   =>  __DIR__ . '/../space_public/deed_marriage_table.php',
    '/actes/mariages/acte_details'          =>  __DIR__ . '/../space_public/deed_marriage_detail.php',
    '/actes/naissances' =>  __DIR__ . '/../space_public/deed_birth_table.php',
    '/actes/naissances/acte_details'        =>  __DIR__ . '/../space_public/deed_birth_detail.php',
    '/eclair'           => __DIR__ . '/../space_public/eclair.php',
    '/localite'         => __DIR__ . '/../space_public/geo_localization.php',
    '/rss'              => __DIR__ . '/../space_public/rss.php',
    '/recherche'        => __DIR__ . '/../space_public/search.php',
    '/recherche_avancee'                    => __DIR__ . '/../space_public/search_advanced.php',
    '/recherche_levenshtein'                => __DIR__ . '/../space_public/search_levenshtein.php',
    '/recherche_levenshtein_2'              => __DIR__ . '/../space_public/search_levenshtein_2.php',
    '/signal_erreur'    => __DIR__ . '/../space_public/signal_error.php',
    '/statistiques'       => __DIR__ . '/../space_public/statistics.php',
    '/identification'   => __DIR__ . '/../space_public/user_login.php',
    '/mon_compte/creation_compte'           => __DIR__ . '/../space_public/user_register.php',
    '/mon_compte/validation_compte'         => __DIR__ . '/../space_public/user_register_validation.php',
    '/mon_compte/recuperation'              => __DIR__ . '/../space_public/user_renvoilogin.php',
    '/mon_compte/changer_motdepasse'        => __DIR__ . '/../space_public/user_change_password.php',
    
    /** ADMIN */
    '/admin/a-propos'                       => __DIR__ . '/../space_admin/about.php',
    '/admin/application/database_sauvegarde'    => __DIR__ . '/../space_admin/app_database_backup.php',
    '/admin/application/environement'       => __DIR__ . '/../space_admin/app_environement.php',
    '/admin/application/logs'               => __DIR__ . '/../space_admin/app_logs.php',
    '/admin/application/export_parametres'  => __DIR__ . '/../space_admin/app_parameters_export.php',
    '/admin/application/maj_parametres'     => __DIR__ . '/../space_admin/app_parameters_update.php',
    '/admin/application/parameters'         => __DIR__ . '/../space_admin/app_parameters.php',

    '/admin/'                               => __DIR__ . '/../space_admin/dashboard.php',
    '/admin/tableau_de_bord'                => __DIR__ . '/../space_admin/dashboard.php',

    '/admin/actes/nouveau'                  => __DIR__ . '/../space_admin/deed_create.php',
    '/admin/actes/export'                   => __DIR__ . '/../space_admin/deed_export_csv.php',
    '/admin/actes/import_csv'               => __DIR__ . '/../space_admin/deed_import_csv.php',
    '/admin/actes/import_nimegue'           => __DIR__ . '/../space_admin/deed_import_nimegue.php',
    '/admin/actes/gestion_index'            => __DIR__ . '/../space_admin/deed_index_update.php',
    '/admin/actes/gestion_labels'           => __DIR__ . '/../space_admin/deed_labels.php',
    '/admin/actes/permuter'                 => __DIR__ . '/../space_admin/deed_permute.php',
    '/admin/actes/supprime_groupe'          => __DIR__ . '/../space_admin/deed_remove_group.php',
    '/admin/actes/supprimer'                => __DIR__ . '/../space_admin/deed_remove.php',
    '/admin/actes/statistiques'             => __DIR__ . '/../space_admin/deed_statistics_update.php',
    '/admin/actes/correction_groupee'       => __DIR__ . '/../space_admin/deed_update_group.php',
    '/admin/actes/modifier'                 => __DIR__ . '/../space_admin/deed_update.php',

    '/admin/geolocalizations/detail'        => __DIR__ . '/../space_admin/geo_localization_detail.php',
    '/admin/geolocalizations'               => __DIR__ . '/../space_admin/geo_localization_table.php',
    '/admin/mail/test'                      => __DIR__ . '/../space_admin/mailer_test_mail.php',
    '/admin/serveur/parametres'             => __DIR__ . '/../space_admin/server_parameters.php',
    '/admin/aide'                           => __DIR__ . '/../space_admin/help/aide.html',

    '/admin/utilisateurs/approuver_compte'  => __DIR__ . '/../space_admin/user_accept_account.php',
    '/admin/utilisateurs/detail'            => __DIR__ . '/../space_admin/user_detail.php',
    '/admin/utilisateurs/export_csv'        => __DIR__ . '/../space_admin/user_export_csv.php',
    '/admin/utilisateurs/import_csv'        => __DIR__ . '/../space_admin/user_import_csv.php',
    '/admin/utilisateurs/gestion_ip'        => __DIR__ . '/../space_admin/user_ip.php',
    '/admin/utilisateurs/envoi_mail'        => __DIR__ . '/../space_admin/user_send_mail.php',
    '/admin/utilisateurs'                   => __DIR__ . '/../space_admin/user_table.php',
    '/admin/utilisateurs/gestion_points'    => __DIR__ . '/../space_admin/user_use_point.php',

    /** PROTECTED FILES && AJAX */
    '/captcha_image'    => __DIR__ . '/../tools/captchas/image.php',
];


$path = $request->getPathInfo();
if (isset($maped_routes[$path])) {
    $view = include $maped_routes[$path];
    $response->setContent($view);
} else {
    $response->setStatusCode(404);
    $response->setContent('Not Found');
}

$response->send();
