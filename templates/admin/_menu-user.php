<p class="text-center">
    <strong>Administration utilisateurs : </strong>
    <a href="<?= $root; ?>/admin/listusers.php" <?= ('L' == $menu_user_active ? 'class="bolder"' : ''); ?>>Lister</a>
    <!-- | <a href="<?= $root; ?>/admin/gestuser.php" <?= ('A' == $menu_user_active ? 'class="bolder"' : ''); ?>>Ajouter</a> -->
    <!-- | <a href="<?= $root; ?>/admin/loaduser.php" <?= ('I' == $menu_user_active ? 'class="bolder"' : ''); ?>>Importer</a> -->
    <!-- | <a href="<?= $root; ?>/admin/expsupuser.php" <?= ('E' == $menu_user_active ? 'class="bolder"' : ''); ?>>Exporter/Supprimer</a> -->
    | <a href="<?= $root; ?>/admin/envoimail.php" <?= ('M' == $menu_user_active ? 'class="bolder"' : ''); ?>>Informer</a>
    | <a href="<?= $root; ?>/admin/gestpoints.php" <?= ('S' == $menu_user_active ? 'class="bolder"' : ''); ?>>Modifications groupées</a>
</p>