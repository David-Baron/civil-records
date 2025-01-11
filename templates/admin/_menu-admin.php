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
                <a href="<?= $root ?>/admin/actes/import_csv">Charger des actes CSV</a>
                <a href="<?= $root ?>/admin/actes/supprime_groupe">Supprimer des actes</a>
                <a href="<?= $root ?>/admin/actes/export">Réexporter des actes</a>
            <?php } ?>
            <?php if ($userAuthorizer->isGranted(7)) { ?>
                <a href="<?= $root ?>/admin/actes/statistiques">Administrer les données</a>
            <?php } ?>
            <?php if ($userAuthorizer->isGranted(9)) { ?>
                <a href="<?= $root ?>/admin/utilisateurs">Administrer les utilisateurs</a>
                <a href="<?= $root ?>/admin/serveur/parametres">Administrer le logiciel</a>
            <?php } ?>
            <a href="<?= $root ?>/admin/a-propos">A propos</a>
            <a href="<?= $root ?>/admin/aide">Aide</a>
        </nav>
    </div>
</div>