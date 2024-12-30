<?php global $userAuthorizer; ?>
<p class="text-center">
    <strong>Administration des données : </strong>
    <a href="<?= $root; ?>/admin/maj_sums.php" <?= ('S' == $menu_data_active ? 'class="bolder"' : ''); ?>>Statistiques</a>
    | <a href="<?= $root; ?>/admin/ajout_1acte.php" <?= ('A' == $menu_data_active ? 'class="bolder"' : ''); ?>>Ajout d'un acte</a>
    <?php if ($userAuthorizer->isGranted(8)) { ?>
        | <a href="<?= $root; ?>/admin/listgeolocs.php" <?= ('L' == $menu_data_active ? 'class="bolder"' : ''); ?>>Localités</a>
        | <a href="<?= $root; ?>/admin/corr_grp_acte.php" <?= ('G' == $menu_data_active ? 'class="bolder"' : ''); ?>>Corrections groupées</a>
        | <a href="<?= $root; ?>/admin/exporte.php?Destin=B" <?= ('B' == $menu_data_active ? 'class="bolder"' : ''); ?>>Backup</a>
        | <a href="<?= $root; ?>/admin/charge.php?Origine=B" <?= ('R' == $menu_data_active ? 'class="bolder"' : ''); ?>>Restauration</a>
    <?php } ?>
</p>