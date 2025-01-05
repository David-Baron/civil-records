<?php global $userAuthorizer; ?>
<p class="text-center">
    <strong>Administration des données : </strong>
    <a href="<?= $root; ?>/admin/actes/statistiques" <?= ('S' == $menu_data_active ? 'class="bolder"' : ''); ?>>Statistiques</a>
    | <a href="<?= $root; ?>/admin/actes/nouveau" <?= ('A' == $menu_data_active ? 'class="bolder"' : ''); ?>>Ajout d'un acte</a>
    <?php if ($userAuthorizer->isGranted(8)) { ?>
        | <a href="<?= $root; ?>/admin/geolocalizations" <?= ('L' == $menu_data_active ? 'class="bolder"' : ''); ?>>Localités</a>
        | <a href="<?= $root; ?>/admin/actes/correction_groupee" <?= ('G' == $menu_data_active ? 'class="bolder"' : ''); ?>>Corrections groupées</a>
        | <a href="<?= $root; ?>/admin/actes/export?Destin=B" <?= ('B' == $menu_data_active ? 'class="bolder"' : ''); ?>>Backup</a>
        | <a href="<?= $root; ?>/admin/actes/import_csv?Origine=B" <?= ('R' == $menu_data_active ? 'class="bolder"' : ''); ?>>Restauration</a>
    <?php } ?>
</p>