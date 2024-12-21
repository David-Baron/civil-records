<?php global $userlogin; ?>
<div class="box">
    <div class="box-title">Administration &lt;<?= $userlogin ?>&gt;</div>
    <div class="box-body">
        <nav class="nav">
            <?php if ($userlevel >= 5) { ?>
                <a href="<?= $root ?>/admin/index.php">Inventaire des actes</a>
            <?php } ?>
            <?php if ($userlevel >= $config->get('CHANGE_PW')) { ?>
                <a href="<?= $root ?>/changepw.php">Changer le mot de passe</a>
            <?php } ?>
            <?php if ($userlevel >= 5) { ?>
                <a href="<?= $root ?>/admin/charge.php">Charger des actes NIMEGUE</a>
            <?php } ?>
            <?php if ($userlevel >= 6) { ?>
                <a href="<?= $root ?>/admin/chargecsv.php">Charger des actes CSV</a>
            <?php } ?>
            <?php if ($userlevel >= 5) { ?>
                <a href="<?= $root ?>/admin/supprime.php">Supprimer des actes</a>
                <a href="<?= $root ?>/admin/exporte.php">Réexporter des actes</a>
            <?php } ?>
            <?php if ($userlevel >= 7) { ?>
                <a href="<?= $root ?>/admin/maj_sums.php">Administrer les données</a>
            <?php } ?>
            <?php if ($userlevel >= 9) { ?>
                <a href="<?= $root ?>/admin/listusers.php">Administrer les utilisateurs</a>
                <a href="<?= $root ?>/admin/gest_params.php">Administrer le logiciel</a>
            <?php } ?>
            <a href="<?= $root ?>/admin/aide/aide.html">Aide</a>
            <a href="<?= $root ?>/index.php?act=logout">Déconnexion</a>
        </nav>
    </div>
</div>