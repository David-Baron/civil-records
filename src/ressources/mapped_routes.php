<?php

return [
    /** FRONT */
    '/'                 => '/space_public/index.php',
    '/accueil'          => '/space_public/index.php',
    '/acces'            =>  '/space_public/acces.php',
    '/contact'          => '/space_public/contact.php',
    '/actes/divers'     =>  '/space_public/deed_divers_table.php',
    '/actes/divers/acte_details'            =>  '/space_public/deed_divers_detail.php',
    '/actes/deces'      =>  '/space_public/deed_death_table.php',
    '/actes/deces/acte_details'             =>  '/space_public/deed_death_detail.php',
    '/actes/mariages'   =>  '/space_public/deed_marriage_table.php',
    '/actes/mariages/acte_details'          =>  '/space_public/deed_marriage_detail.php',
    '/actes/naissances' =>  '/space_public/deed_birth_table.php',
    '/actes/naissances/acte_details'        =>  '/space_public/deed_birth_detail.php',
    '/eclair'           => '/space_public/eclair.php',
    '/localite'         => '/space_public/geo_localization.php',
    '/rss'              => '/space_public/rss.php',
    '/recherche'        => '/space_public/search.php',
    '/recherche_avancee'                    => '/space_public/search_advanced.php',
    '/recherche_levenshtein'                => '/space_public/search_levenshtein.php',
    '/recherche_levenshtein_2'              => '/space_public/search_levenshtein_2.php',
    '/signal_erreur'    => '/space_public/signal_error.php',
    '/statistiques'       => '/space_public/statistics.php',
    '/identification'   => '/space_public/user_login.php',
    '/mon_compte/creation_compte'           => '/space_public/user_register.php',
    '/mon_compte/validation_compte'         => '/space_public/user_register_validation.php',
    '/mon_compte/recuperation'              => '/space_public/user_renvoilogin.php',
    '/mon_compte/changer_motdepasse'        => '/space_public/user_change_password.php',

    /** ADMIN */
    '/admin/'                               => '/space_admin/dashboard.php',
    '/admin/tableau_de_bord'                => '/space_admin/dashboard.php',

    '/admin/a-propos'                       => '/space_admin/about.php',
    '/admin/application/database_sauvegarde'    => '/space_admin/app_database_backup.php',
    '/admin/application/environement'       => '/space_admin/app_environment.php',
    '/admin/application/logs'               => '/space_admin/app_logs.php',
    '/admin/application/export_parametres'  => '/space_admin/app_parameters_export.php',
    '/admin/application/maj_parametres'     => '/space_admin/app_parameters_update.php',
    '/admin/application/parameters'         => '/space_admin/app_parameters.php',
    '/admin/serveur/parametres'             => '/space_admin/server_parameters.php',
    '/admin/mail/test'                      => '/space_admin/mailer_test_mail.php',

    '/admin/actes/nouveau'                  => '/space_admin/deed_create.php',
    '/admin/actes/export'                   => '/space_admin/deed_export_csv.php',
    '/admin/actes/import_csv'               => '/space_admin/deed_import_csv.php',
    '/admin/actes/import_nimegue'           => '/space_admin/deed_import_nimegue.php',
    '/admin/actes/gestion_index'            => '/space_admin/deed_index_update.php',
    '/admin/actes/gestion_labels'           => '/space_admin/deed_labels.php',
    '/admin/actes/permuter'                 => '/space_admin/deed_permute.php',
    '/admin/actes/supprime_groupe'          => '/space_admin/deed_remove_group.php',
    '/admin/actes/supprimer'                => '/space_admin/deed_remove.php',
    '/admin/actes/statistiques'             => '/space_admin/deed_statistics_update.php',
    '/admin/actes/correction_groupee'       => '/space_admin/deed_update_group.php',
    '/admin/actes/modifier'                 => '/space_admin/deed_update.php',

    '/admin/geolocalizations/detail'        => '/space_admin/geo_localization_detail.php',
    '/admin/geolocalizations'               => '/space_admin/geo_localization_table.php',  

    '/admin/utilisateurs/approuver_compte'  => '/space_admin/user_accept_account.php',
    '/admin/utilisateurs/detail'            => '/space_admin/user_detail.php',
    '/admin/utilisateurs/export_csv'        => '/space_admin/user_export_csv.php',
    '/admin/utilisateurs/import_csv'        => '/space_admin/user_import_csv.php',
    '/admin/utilisateurs/gestion_ip'        => '/space_admin/user_ip.php',
    '/admin/utilisateurs/envoi_mail'        => '/space_admin/user_send_mail.php',
    '/admin/utilisateurs'                   => '/space_admin/user_table.php',
    '/admin/utilisateurs/gestion_points'    => '/space_admin/user_use_point.php',

    /** ADMIN HELP */
    '/admin/aide'                           => '/space_admin/help/aide.html',
    /** PROTECTED FILES && AJAX */
    '/captcha_image'    => '/tools/captchas/image.php',
];
