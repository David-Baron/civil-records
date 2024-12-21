<?php
// global $path, $userlogin, $scriptname, $commune; // TODO: Need test, will be running without now
$carcode = 'UTF-8';
$meta_description = $config->get('META_DESCRIPTION', '');
$meta_keywords = $config->get('META_KEYWORDS', '');

header('Content-Type: text/html; charset=UTF-8');

?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="expires" content="never">
    <meta name="revisit-after" content="15 days">
    <meta name="robots" content="index, nofollow">
    <meta name="description" content="<?= $meta_description; ?>, <?= $titre; ?>">
    <meta name="keywords" content="<?= $meta_keywords; ?>, <?= $titre; ?>">
    <meta name="generator" content="ExpoActes">
    <title><?= $titre; ?></title>
    <link rel="favicon" href="<?= $root; ?>/img/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="<?= $root; ?>/tools/css/actes.css" type="text/css">
    <?php if (file_exists(__DIR__ . '/../../_config/actes.css')) { ?>
        <link rel="stylesheet" href="<?= $root; ?>/_config/actes.css" type="text/css">
    <?php } ?>
    <link rel="stylesheet" href="<?= $root; ?>/tools/css/actes_print.css" type="text/css" media="print">
    <?php if ($rss <> "") { ?>
        <link rel="alternate" type="application/rss+xml" title="<?= $titre; ?>" href="<?= $root; ?>/<?= $rss; ?>">
    <?php } ?>

    <?php
    /**
     *   @deprecated
     * // Adapté de Cookie Consent plugin by Silktide - http://silktide.com/cookieconsent
     *   if (!defined("COOKIE_MESSAGE")) {
     *       $cookie_message = "Acceptez-vous d'utiliser les Cookies ?";
     *  } else {
     *      $cookie_message = COOKIE_MESSAGE;
     *  }
     *  if (!defined("COOKIE_URL_INFO")) {
     *      $cookie_url = "";
     *  } else {
     *      $cookie_url = COOKIE_URL_INFO;
     *  }
     *  $cookie_styles = array(1 => "dark-top", 2 => "light-top", 3 => "dark-bottom", 4 => "light-bottom", 5 => "dark-floating", 6 => "light-floating");
     *  if (!defined("COOKIE_STYLE")) {
     *     $cookie_style = $cookie_styles[1];
     * } else {
     *      $cookie_style = $cookie_styles[COOKIE_STYLE];
     *  }
     *   echo '<script type="text/javascript">
     *		window.cookieconsent_options = {
     *			"message":"' . $cookie_message . '",
     *		    "dismiss":"Accepter les cookies",
     *			"learnMore":"En savoir plus",
     *			"link":"' . $cookie_url . '",
     *			"theme":"' . $cookie_style . '"};</script>';
     *  echo '<script type="text/javascript" src="' . $root . '/tools/js/cookieconsent.min.js"></script>';
     *  // Cookie Consent plugin //
     */


    /**
     * @deprecated Js scripts will be only at the end of body 
     *
     * if (file_exists(__DIR__ . '/../../_config/js_externe_header.inc.php')) {
     *   include(__DIR__ . '/../../_config/js_externe_header.inc.php');
     * }
     */
    ?>

    <?php if (!($js == null)) {
        echo '<script type="text/javascript">';
        echo $js;
        echo '</script>';
    } ?>

    <?php
    /** TODO: All this part will be refacto */
    echo $config->get('INCLUDE_HEADER', '');
    if (!($addhead == null)) {
        echo $addhead;
    }
    // END TODO
    ?>
</head>

<body>

    <?php if (getparam(EL) == 'O') {
        echo $ExpoActes_Charset;
    }

    /** TODO: All this part will be refacto */
    global $TIPmsg;  // message d'alerte pré-blocage IP
    if ($TIPmsg <> "" && ($config->get('TIP_MODE_ALERT') % 2) == 1) {
        echo '<h2><font color="#FF0000">' . $TIPmsg . "</font></h2>\n";
    }
    /** END TODO */
    ?>
    <div id="top" class="entete">
        <?php /** TODO: Useless, will be deleted. */
        if ($config->get('EA_MAINTENANCE') == 1) { // TODO: Useless, will be deleted.
            echo '<font color="#FF0000"><b>!! MAINTENANCE !!</b></font>';
        }
        /**END TODO */
        ?>
        <?php include(__DIR__ . '/_bandeau.php'); ?>
    </div>