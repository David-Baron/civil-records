<p class="text-center">
    <strong>Administration du logiciel : </strong>
    <a href="<?= $root; ?>/admin/application/parameters" <?= ('P' == $menu_software_active ? 'class="bolder"' : ''); ?>>Paramétrage</a>
    | <a href="<?= $root; ?>/admin/actes/gestion_labels" <?= ('Q' == $menu_software_active ? 'class="bolder"' : ''); ?>>Etiquettes</a>
    | <a href="<?= $root; ?>/admin/serveur/parametres" <?= ('E' == $menu_software_active ? 'class="bolder"' : ''); ?>>Etat serveur</a>
    | <a href="<?= $root; ?>/admin/application/environement" <?= ('V' == $menu_software_active ? 'class="bolder"' : ''); ?>>Environement</a>
    <!-- | <a href="<?= $root; ?>/admin/utilisateurs/gestion_ip" <?= ('F' == $menu_software_active ? 'class="bolder"' : ''); ?>>Fitrage IP</a> -->
    | <a href="<?= $root; ?>/admin/actes/gestion_index" <?= ('I' == $menu_software_active ? 'class="bolder"' : ''); ?>>Index</a>
    | <a href="<?= $root; ?>/admin/application/logs" <?= ('J' == $menu_software_active ? 'class="bolder"' : ''); ?>>Journal</a>
</p>