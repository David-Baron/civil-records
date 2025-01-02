<?php

use Symfony\Component\HttpFoundation\RedirectResponse;

require(__DIR__ . '/src/bootstrap.php');

if (!$userAuthorizer->isGranted(3) && $config->get('RECH_ZERO_PTS') == 0) {
    $session->getFlashBag()->add('warning', 'Vous n\'êtes pas connecté ou vous n\'avez pas les autorisations nécessaires!');
    $response = new RedirectResponse("$root/");
    $response->send();
    exit();
}

ob_start();
open_page("Recherche avancée", $root, null, null, null, null, 'rss.php'); ?>
<div class="main">
    <?php zone_menu(0, $session->get('user', ['level' => 0])['level']); ?>
    <div class="main-col-center text-center">
        <?php navigation($root, 2, 'A', "Recherche avancée"); ?>
        <h2>Recherche avancée</h2>

        <?php if (
                $config->get('PUBLIC_LEVEL') >= 3
                || $userAuthorizer->isAuthenticated() && ($config->get('RECH_LEVENSHTEIN') == 1 && $session->get('user')['level'] >= $config->get('LEVEL_LEVENSHTEIN'))
            ) { ?>
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
