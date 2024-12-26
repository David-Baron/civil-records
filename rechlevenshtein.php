<?php

use Symfony\Component\HttpFoundation\RedirectResponse;

require(__DIR__ . '/next/bootstrap.php');
require(__DIR__ . '/next/_COMMUN_env.inc.php'); // Compatibility only
include("tools/cree_table_levenshtein.php");

if (!$userAuthorizer->isGranted($config->get('LEVEL_LEVENSHTEIN'))) {
    $session->getFlashBag()->add('warning', 'Vous n\'êtes pas connecté ou vous n\'avez pas les autorisations nécessaires!');
    $response = new RedirectResponse("$root/");
    $response->send();
    exit();
}

$CRIT_RECH_COUPLES = $config->get('CRIT_RECH_COUPLES', 2);

ob_start();
open_page("Recherche Levenshtein", $root, null, null, null, null, 'rss.php'); ?>
<div class="main">
    <?php zone_menu(0, $session->get('user', ['level' => 0])['level']); ?>
    <div class="main-col-center text-center">
        <?php navigation($root, 2, 'A', "Recherche  Levenshtein"); ?>
        <h2>Recherche Levenshtein</h2>
        <p>
            Permet de rechercher des noms dont l'orthographe varie de 0 à 5 lettres (en plus, en moins, en remplacement)
            de celui indiqué
            <br>
            (ex : Recherche Vernhes + 2 différences donne : Vernhes, Verhnes, Vergne, Vergnes, Verghnes, Bernhes ...)
            <br>
            <b>Aide</b> :
            <a href="<?= $root; ?>/admin/aide/levenshtein_principes.html">Principes</a>
            et <a href="<?= $root; ?>/admin/aide/levenshtein_utilisation.html">Utilisation</a>
        </p>
        <div class="rech_zone">
            <form class="form_rech" name="rechercheav" method="post" action="<?= $root; ?>/chercherlevenshtein.php">
                <div class="rech_zone">
                    <div class="rech_titre">Individu concerné :</div>
                    <p>
                        Patronyme : <input type="text" name="achercher">
                        Prénom : <input type="text" name="prenom">
                    </p>
                    <p>
                        Comparaison :
                        <input type="radio" name="comp" value="Z">Exacte
                        <input type="radio" name="comp" value="U">1 différence
                        <input type="radio" name="comp" value="D" checked="checked">2 diff.
                        <input type="radio" name="comp" value="T">3 diff.
                        <input type="radio" name="comp" value="Q">4 diff.
                        <input type="radio" name="comp" value="C">5 diff.
                    </p>
                </div>
                <div class="rech_zone">
                    <div class="rech_titre">Conjoint (éventuel) :</div>
                    <p>
                        Patronyme : <input type="text" name="achercher2">
                        Prénom : <input type="text" name="prenom2">
                    </p>
                </div>
                <div class="rech_zone">
                    <div class="rech_titre">Actes recherchés individus (suppose conjoint non renseigné) :</div>
                    <?php if ($config->get('CHERCH_TS_TYP') == 1) { ?>
                        <p>
                            <input type="checkbox" name="TypN" value="N" checked="checked">Naissances
                            <input type="checkbox" name="TypD" value="D" checked="checked">Décès
                            <input type="checkbox" name="TypM" value="M" checked="checked">Mariages
                            <input type="checkbox" name="TypV" value="V" checked="checked">Actes divers
                        </p>
                    <?php } else { ?>
                        <p><input type="radio" name="TypNDMV" value="N" checked="checked">Naissances
                            <input type="radio" name="TypNDMV" value="D">Décès
                            <input type="radio" name="TypNDMV" value="M">Mariages
                            <input type="radio" name="TypNDMV" value="V">Actes divers
                        </p>
                    <?php } ?>
                </div>
                <div class="rech_zone">
                    <div class="rech_titre">Actes recherchés couples (suppose conjoint renseigné) :</div>
                    <p>
                        <input type="radio" name="comp2" value="MA" checked="checked">Mariages
                        <input type="radio" name="comp2" value="EN">Enfants (naissances)
                        <?php if ($CRIT_RECH_COUPLES == 3 or $CRIT_RECH_COUPLES == 4) { ?>
                            <input type="radio" name="comp2" value="END">Enfants (naissances et décès)
                        <?php } ?>
                        <?php if ($CRIT_RECH_COUPLES == 4) { ?>
                            <input type="radio" name="comp2" value="TOUT">Couple (mariage, naissances et décès enfants)
                        <?php } ?>
                        <input type="radio" name="comp2" value="DIV">Actes Divers
                    </p>
                </div>
                <div class="rech_zone">
                    <div class="rech_titre">Critères :</div>
                    <p>
                        Années à partir de : <input type="text" name="amin" size="4">
                        jusqu'à : <input type="text" name="amax" size="4">
                    </p>
                    <p>
                        Commune ou paroisse :
                        <?php listbox_communes("ComDep", "", 1); ?>
                    </p>
                </div>

                <p align="center">
                    <button type="submit" name="Submit">Chercher</button>
                </p>
                <input type="hidden" name="debug" value="<?= getparam('debug'); ?>">
            </form>
        </div>
    </div>
</div>
<?php include(__DIR__ . '/templates/front/_footer.php');

$response->setContent(ob_get_clean());
$response->send();
