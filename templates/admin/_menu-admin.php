<?php global $userAuthorizer; ?>
<div class="box">
    <div class="box-title">Administration</div>
    <div class="box-body">
        <nav class="nav">
            <?php if ($userAuthorizer->isGranted(5)) { ?>
                <a href="<?= $root ?>/admin/tableau_de_bord">Inventaire des actes</a>
                <a href="<?= $root ?>/admin/actes/import_nimegue">Charger des actes NIMEGUE</a>
            <?php } ?>
            <?php if ($userAuthorizer->isGranted(6)) { ?>
                <a href="<?= $root ?>/admin/chargecsv.php">Charger des actes CSV</a>
                <a href="<?= $root ?>/admin/supprime.php">Supprimer des actes</a>
                <a href="<?= $root ?>/admin/exporte.php">Réexporter des actes</a>
            <?php } ?>
            <?php if ($userAuthorizer->isGranted(7)) { ?>
                <a href="<?= $root ?>/admin/maj_sums.php">Administrer les données</a>
            <?php } ?>
            <?php if ($userAuthorizer->isGranted(9)) { ?>
                <a href="<?= $root ?>/admin/listusers.php">Administrer les utilisateurs</a>
                <a href="<?= $root ?>/admin/gest_params.php">Administrer le logiciel</a>
            <?php } ?>
            <a href="<?= $root ?>/admin/aide/aide.html">Aide</a>
            <a href="<?= $root ?>/admin/about.php">A propos</a>
        </nav>
    </div>
</div>