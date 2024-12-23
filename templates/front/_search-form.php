<?php
global $root, $session, $config;

$act_types = [
    ['code' => 'N', 'code_3' => 'NAI', 'label' => 'Naissances'],
    ['code' => 'M', 'code_3' => 'MAR', 'label' => 'Mariages'],
    ['code' => 'D', 'code_3' => 'DEC', 'label' => 'Décès'],
    ['code' => 'V', 'code_3' => 'DIV', 'label' => 'Actes divers'],
];

if (
    $config->get('PUBLIC_LEVEL') >= 3 && $config->get('RECH_ZERO_PTS') == 1
    || $userAuthorizer->isAuthenticated() && $session->get('user')['level'] >= $config->get('PUBLIC_LEVEL')
) { ?>
    <div class="box">
        <div class="box-title">Recherche directe</div>
        <div class="box-body p-2">

            <form name="recherche" method="post" action="<?= $root; ?>/chercher.php">
                <input type="text" name="achercher">
                <input type="submit" name="Submit" value="Chercher">
                <br><input type="radio" name="zone" value="1" checked="checked">Intéressé(e)
                <br><input type="radio" name="zone" value="2">Mère, conjoint, témoins, parrain...
                <?php if ($config->get('CHERCH_TS_TYP') != 1) { ?>
                    <br>Dans les actes de
                    <select name="typact" size="1">
                        <?php foreach ($act_types as $act_type) { ?>
                            <option value="<?= $act_type['code']; ?>" <?= ('N' === $act_type['code'] ? 'selected' : ''); ?>><?= $act_type['label']; ?></option>
                        <?php } ?>
                    </select>
                <?php } ?>
                <input type="hidden" name="direct" value="1">
                <div class="text-right p-2">
                    <a href="<?= $root; ?>/rechavancee.php">Recherche avancée</a>
                    <?php if (
                        $config->get('PUBLIC_LEVEL') >= 3
                        || $userAuthorizer->isAuthenticated() && $config->get('RECH_LEVENSHTEIN') == 2 && $session->get('user')['level'] >= $config->get('LEVEL_LEVENSHTEIN')
                    ) { ?>
                         | <a href="<?= $root; ?>/rechlevenshtein.php">Recherche Levenshtein</a>
                    <?php } ?>
                </div>
            </form>
        </div>
    </div>
<?php }
