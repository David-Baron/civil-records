<?php
global $root, $session, $config;

$act_types = [
    ['code' => 'N', 'code_3' => 'NAI', 'label' => 'Naissances'],
    ['code' => 'M', 'code_3' => 'MAR', 'label' => 'Mariages'],
    ['code' => 'D', 'code_3' => 'DEC', 'label' => 'Décès'],
    ['code' => 'V', 'code_3' => 'DIV', 'label' => 'Actes divers'],
];

if ($config->get('PUBLIC_LEVEL') >= 3 || ($session->has('user') && $session->get('user')['level'] >= 3 && ((current_user_solde() > 0) || $config->get('RECH_ZERO_PTS') == 1))) { ?>
    <div class="menu_zone">
        <div class="menu_titre">Recherche directe</div>
        <form class="form_rech" name="recherche" method="post" action="<?= $root; ?>/chercher.php">
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
            <div class="menuTexte">
                <dl>
                    <dd>
                        <a href="<?= $root; ?>/rechavancee.php">Recherche avancée</a>
                        <?php if (($config->get('RECH_LEVENSHTEIN') == 2) && (max($session->get('user')['level'], $config->get('PUBLIC_LEVEL')) >= $config->get('LEVEL_LEVENSHTEIN'))) { ?>
                            <br><a href="<?= $root; ?>/rechlevenshtein.php">Recherche Levenshtein</a>
                        <?php } ?>
                    </dd>
                </dl>
            </div>
        </form>
    </div>
<?php }
