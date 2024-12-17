<p>
    <strong>Administration du logiciel : </strong>
    <a href="<?= $root; ?>/admin/gest_params.php" <?= ('P' == $menu_software_active ? 'class="bolder"' : ''); ?>>Paramétrage</a>
    | <a href="<?= $root; ?>/admin/gest_labels.php" <?= ('Q' == $menu_software_active ? 'class="bolder"' : ''); ?>>Etiquettes</a>
    | <a href="<?= $root; ?>/admin/serv_params.php" <?= ('E' == $menu_software_active ? 'class="bolder"' : ''); ?>>Etat serveur</a>
    | <a href="<?= $root; ?>/admin/gesttraceip.php" <?= ('F' == $menu_software_active ? 'class="bolder"' : ''); ?>>Fitrage IP</a>
    | <a href="<?= $root; ?>/admin/gestindex.php" <?= ('I' == $menu_software_active ? 'class="bolder"' : ''); ?>>Index</a>
    | <a href="<?= $root; ?>/admin/listlog.php" <?= ('J' == $menu_software_active ? 'class="bolder"' : ''); ?>>Journal</a>
</p>