<p class="text-center">
    <strong>Administration utilisateurs : </strong>
    <a href="<?= $root; ?>/admin/utilisateurs" <?= ('L' == $menu_user_active ? 'class="bolder"' : ''); ?>>Lister</a>
    <!-- | <a href="<?= $root; ?>/admin/utilisateurs/nouveau" <?= ('A' == $menu_user_active ? 'class="bolder"' : ''); ?>>Ajouter</a> -->
    <!-- | <a href="<?= $root; ?>/admin/utilisateurs/import_csv" <?= ('I' == $menu_user_active ? 'class="bolder"' : ''); ?>>Importer</a> -->
    <!-- | <a href="<?= $root; ?>/admin/utilisateurs/export_csv" <?= ('E' == $menu_user_active ? 'class="bolder"' : ''); ?>>Exporter/Supprimer</a> -->
    | <a href="<?= $root; ?>/admin/utilisateurs/envoi_mail" <?= ('M' == $menu_user_active ? 'class="bolder"' : ''); ?>>Informer</a>
    | <a href="<?= $root; ?>/admin/utilisateurs/gestion_points" <?= ('S' == $menu_user_active ? 'class="bolder"' : ''); ?>>Modifications groupées</a>
</p>