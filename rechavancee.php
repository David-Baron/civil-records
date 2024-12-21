<?php

use Symfony\Component\HttpFoundation\RedirectResponse;

define('ADM', 0); // Compatibility only
$admtxt = ''; // Compatibility only
require(__DIR__ . '/next/bootstrap.php');
require(__DIR__ . '/next/_COMMUN_env.inc.php'); // Compatibility only

if (!$userAuthorizer->isGranted(3)) {
    $response = new RedirectResponse("$root/login.php");
    $response->send();
    exit();
}


$xcomm = "";
$xpatr = "";
$page = "";

pathroot($root, $path, $xcomm, $xpatr, $page);

ob_start();
open_page($config->get('SITENAME') . " : Dépouillement d'actes de l'état-civil et des registres paroissiaux", $root, null, null, null, '../index.htm', 'rss.php'); ?>
<div class="main">
    <?php zone_menu(0, 0); ?>
    <div class="main-col-center text-center">
        <?php navigation($root, 2, 'A', "Recherche avancée"); ?>
        <h2>Recherche avancée</h2>

        <?php if (($config->get('RECH_LEVENSHTEIN') == 1) && (max($session->get('user')['level'], $config->get('PUBLIC_LEVEL')) >= $config->get('LEVEL_LEVENSHTEIN'))) { ?>
            <div>
                <a href="<?= $root; ?>/rechlevenshtein.php">Recherche Levenshtein</a>
            </div>
        <?php } ?>

        <div class="rech_zone">
            <form class="form_rech" name="rechercheav" method="post" action="<?= $root; ?>/chercher.php">
                <div class="rech_zone">
                    <div class="rech_titre">Première personne concernée par l'acte :</div>
                    <p>
                        Patronyme : <input type="text" name="achercher">
                        Prénom : <input type="text" name="prenom">
                    </p>
                    <p>
                        De :
                        <input type="radio" name="zone" value="1" checked="checked"> Intéressé(e)
                        <input type="radio" name="zone" value="4"> (future/ex) Conjoint
                        <input type="radio" name="zone" value="5"> Père
                        <input type="radio" name="zone" value="6"> Mère
                        <input type="radio" name="zone" value="7"> Parrain/témoin
                    <p>
                        Comparaison :
                        <input type="radio" name="comp" <?= prechecked("E"); ?>>Exacte
                        <input type="radio" name="comp" <?= prechecked("D"); ?>>Au début
                        <input type="radio" name="comp" <?= prechecked("F"); ?>>A la fin
                        <input type="radio" name="comp" <?= prechecked("C"); ?>>Est dans
                        <input type="radio" name="comp" <?= prechecked("S"); ?>>Sonore
                    </p>
                </div>
                <div class="rech_zone">
                    <div class="rech_titre">Seconde personne (éventuelle) :</div>
                    <p>
                        Patronyme : <input type="text" name="achercher2">
                        Prénom : <input type="text" name="prenom2">
                    </p>
                    <p>
                        De : <input type="radio" name="zone2" value="4" checked="checked"> (future/ex) Conjoint
                        <input type="radio" name="zone2" value="5"> Père
                        <input type="radio" name="zone2" value="6"> Mère
                        <input type="radio" name="zone2" value="7"> Parrain/témoin
                    </p>
                    <p>
                        Comparaison :
                        <input type="radio" name="comp2" <?= prechecked("E"); ?>>Exacte
                        <input type="radio" name="comp2" <?= prechecked("D"); ?>>Au début
                        <input type="radio" name="comp2" <?= prechecked("F"); ?>>A la fin
                        <input type="radio" name="comp2" <?= prechecked("C"); ?>>Est dans
                        <input type="radio" name="comp2" <?= prechecked("S"); ?>>Sonore
                    </p>
                </div>
                <div class="rech_zone">
                    <div class="rech_titre">Autres éléments de l'acte :</div>
                    <p>
                        Texte :
                        <input type="text" name="achercher3">
                    </p>
                    <p>
                        Dans : <input type="radio" name="zone3" value="9" checked="checked">Origines
                        <input type="radio" name="zone3" value="A"> Professions
                        <input type="radio" name="zone3" value="8"> Commentaires
                    </p>
                    <p>
                        Comparaison :
                        <input type="radio" name="comp3" <?= prechecked("E"); ?>>Exacte
                        <input type="radio" name="comp3" <?= prechecked("D"); ?>>Au début
                        <input type="radio" name="comp3" <?= prechecked("F"); ?>>A la fin
                        <input type="radio" name="comp3" <?= prechecked("C"); ?>>Est dans
                        <input type="radio" name="comp3" <?= prechecked("S"); ?>>Sonore
                    </p>
                </div>
                <div class="rech_zone">
                    <div class="rech_titre">Actes recherchés : </div>
                    <?php if ($config->get('CHERCH_TS_TYP') == 1) { ?>
                        <p>
                            <input type="checkbox" name="TypN" value="N" checked="checked">Naissances
                            <input type="checkbox" name="TypD" value="D" checked="checked">Décès
                            <input type="checkbox" name="TypM" value="M" checked="checked">Mariages
                            <input type="checkbox" name="TypV" value="V" checked="checked">Actes divers :
                        <?php } else { ?>
                        <p>
                            <input type="radio" name="TypNDMV" value="N" checked="checked">Naissances
                            <input type="radio" name="TypNDMV" value="D">Décès
                            <input type="radio" name="TypNDMV" value="M">Mariages
                            <input type="radio" name="TypNDMV" value="V">Actes divers :
                        <?php } ?>
                        <?php listbox_divers("typdivers", "***Tous***", 1); ?>
                        </p>
                        <p>
                            Années à partir de : <input type="text" name="amin" size="4">
                            jusqu'à : <input type="text" name="amax" size="4">
                        </p>
                        <p>
                            Commune ou paroisse :
                            <?php listbox_communes("ComDep", "***Toutes***", 1); ?>
                        </p>
                </div>
                <div class="text-center">
                    <button type="submit" name="Submit">Chercher</button>
                </div>
                <input type="hidden" name="debug" value="<?= getparam('debug'); ?>">
            </form>
        </div>
    </div>
</div>
<?php include(__DIR__ . '/templates/front/_footer.php');

$response->setContent(ob_get_clean());
$response->send();
