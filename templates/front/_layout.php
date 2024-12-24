<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="expires" content="never">
    <meta name="revisit-after" content="15 days">
    <meta name="robots" content="index, nofollow">
    <title><?= $title; ?></title>
    <meta name="description" content="<?= $meta_description; ?> <?= $title; ?>">
    <meta name="keywords" content="<?= $meta_keywords; ?>">
    <meta name="generator" content="Civil-Records">

    <link rel="shortcut icon" href="<?= $root; ?>/themes/default/img/favicon.ico" type="image/x-icon">

    <link rel="stylesheet" href="<?= $root; ?>/themes/default/css/default.css" type="text/css">
    <link rel="stylesheet" href="<?= $root; ?>/themes/default/css/style.css" type="text/css">
    <?php if (file_exists(__DIR__ . '/../../_config/actes.css')) { ?>
        <link rel="stylesheet" href="<?= $root; ?>/_config/actes.css" type="text/css">
    <?php } ?>
    <link rel="stylesheet" href="<?= $root; ?>/themes/default/css/print.css" type="text/css" media="print">

    <?php if (file_exists(__DIR__ . '/../../_config/js_externe_header.inc.php')) {
        include(__DIR__ . '/../../_config/js_externe_header.inc.php');
    } ?>

    <?php if ($rss <> "") { ?>
        <link rel="alternate" type="application/rss+xml" title="<?= $title; ?>" href="<?= $root; ?>/<?= $rss; ?>">
    <?php } ?>

    <?php if ($js !== null) { ?>
        <script type="text/javascript">
            <?= $js; ?>
        </script>
    <?php } ?>

    <?= $config->get('INCLUDE_HEADER', ''); ?>

</head>

<body>
    <div class="entete">
        <?php include(__DIR__ . '/_bandeau.php'); ?>
    </div>

    <div class="main">
        <?php zone_menu(0, 0); ?>
        <div class="main-col-center text-center">
            <?php navigation($root, 2, 'A', "Conditions d'accès"); ?>
            <?= $content; ?>
        </div>
    </div>

    <div class="footer">
        <div class="text-right"><a href="#top"><strong>Top</strong></a></div>
        <div class="text-center">
            <p><?= $config->get('PIED_PAGE'); ?></p>
        </div>
        <div class="text-center">
            <p>
                Ce site est propulsé par <em><a href="">Civil-Records</a></em>
            </p>
        </div>
    </div>

    <?php if (file_exists(__DIR__ . '/../../_config/js_externe_footer.inc.php')) {
        include(__DIR__ . '/../../_config/js_externe_footer.inc.php');
    } ?>

</body>

</html>