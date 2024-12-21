<?php global $userlogin; ?>
<div class="menu_zone">
    <div class="menu_titre">Administration &lt;<?= $userlogin ?>&gt;</div>
    <div class="menuCorps">
        <dl>
            <?php if ($userlevel >= 5) { ?>
                <dt><a href="<?= $root ?>/admin/index.php">Inventaire des actes</a></dt>
            <?php } ?>
            <?php if ($userlevel >= $config->get('CHANGE_PW')) { ?>
                <dt><a href="<?= $root ?>/changepw.php">Changer le mot de passe</a></dt>
            <?php } ?>
            <?php if ($userlevel >= 5) { ?>
                <dt><a href="<?= $root ?>/admin/charge.php">Charger des actes NIMEGUE</a></dt>
            <?php } ?>
            <?php if ($userlevel >= 6) { ?>
                <dt><a href="<?= $root ?>/admin/chargecsv.php">Charger des actes CSV</a></dt>
            <?php } ?>
            <?php if ($userlevel >= 5) { ?>
                <dt><a href="<?= $root ?>/admin/supprime.php">Supprimer des actes</a></dt>
                <dt><a href="<?= $root ?>/admin/exporte.php">Réexporter des actes</a></dt>
            <?php } ?>
            <?php if ($userlevel >= 7) { ?>
                <dt><a href="<?= $root ?>/admin/maj_sums.php">Administrer les données</a></dt>
            <?php } ?>
            <?php if ($userlevel >= 9) { ?>
                <dt><a href="<?= $root ?>/admin/listusers.php">Administrer les utilisateurs</a></dt>
                <dt><a href="<?= $root ?>/admin/gest_params.php">Administrer le logiciel</a></dt>
            <?php } ?>
            <dt><a href="<?= $root ?>/admin/aide/aide.html">Aide</a></dt>
            <dt><a href="<?= $root ?>/index.php?act=logout">Déconnexion</a></dt>
        </dl>
    </div>
</div>