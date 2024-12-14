<?php
define('ADM', 0); // Compatibility only
$admtxt = ''; // Compatibility only
require(__DIR__ . '/next/bootstrap.php');
require(__DIR__ . '/next/_COMMUN_env.inc.php'); // Compatibility only


$root = "";
$path = "";
$xcomm = "";
$xpatr = "";
$page = "";
pathroot($root, $path, $xcomm, $xpatr, $page);

$userlogin = "";
$userlevel = logonok(3);
while ($userlevel < 3) {
    login($root);
}

ob_start();
open_page(SITENAME . " : Dépouillement d'actes de l'état-civil et des registres paroissiaux", $root, null, null, null, '../index.htm', 'rss.php');
navigation($root, 2, 'A', "Recherche avancée");

zone_menu(0, 0, array('s' => '', 'c' => 'O'));//PUBLIC STAT & CERT

echo '<div id="col_main">';
echo '<h2>Recherche avancée</h2>';

if ((RECH_LEVENSHTEIN == 1) and (max($userlevel, PUBLIC_LEVEL) >= LEVEL_LEVENSHTEIN)) {
    echo '<div align="right">';
    echo '<a href="' . $root . '/rechlevenshtein.php">Recherche Levenshtein</a>&nbsp; &nbsp;';
    echo '</div>';
}

echo '<div class="rech_zone">';

echo '<form class="form_rech" name="rechercheav" method="post" action="' . $root . '/chercher.php">';

echo '<div class="rech_zone">';
echo '<div class="rech_titre">Première personne concernée par l\'acte :</div>';
echo '<p>&nbsp;&nbsp;Patronyme : <input type="text" name="achercher" />&nbsp; ';
echo 'Prénom :    <input type="text" name="prenom" /></p>';
echo '<p>&nbsp;&nbsp;De : <input type="radio" name="zone" value="1" checked="checked" /> Intéressé(e) &nbsp;';
echo '   <input type="radio" name="zone" value="4" /> (future/ex) Conjoint &nbsp;';
echo '   <input type="radio" name="zone" value="5" /> Père &nbsp;';
echo '   <input type="radio" name="zone" value="6" /> Mère &nbsp;';
echo '   <input type="radio" name="zone" value="7" /> Parrain/témoin &nbsp;';
//echo '   <input type="radio" name="zone" value="8" /> Commentaires </p>';
echo '<p>&nbsp;&nbsp;Comparaison :';
echo '  <input type="radio" name="comp"' . prechecked("E") . '/>Exacte&nbsp;';
echo '  <input type="radio" name="comp"' . prechecked("D") . '/>Au début&nbsp;';
echo '  <input type="radio" name="comp"' . prechecked("F") . '/>A la fin&nbsp;';
echo '  <input type="radio" name="comp"' . prechecked("C") . '/>Est dans&nbsp;';
echo '  <input type="radio" name="comp"' . prechecked("S") . '/>Sonore&nbsp;</p>';
echo '</div>';

echo '<div class="rech_zone">';
echo '<div class="rech_titre">Seconde personne (éventuelle) :</div>';
echo '<p>&nbsp;&nbsp;Patronyme : <input type="text" name="achercher2" />&nbsp; ';
echo 'Prénom :    <input type="text" name="prenom2" /></p>';
echo '<p>&nbsp;&nbsp;De : <input type="radio" name="zone2" value="4" checked="checked" /> (future/ex) Conjoint &nbsp;';
echo '   <input type="radio" name="zone2" value="5" /> Père &nbsp;';
echo '   <input type="radio" name="zone2" value="6" /> Mère &nbsp;';
echo '   <input type="radio" name="zone2" value="7" /> Parrain/témoin </p>';
echo '<p>&nbsp;&nbsp;Comparaison :';
echo '  <input type="radio" name="comp2"' . prechecked("E") . '/>Exacte&nbsp;';
echo '  <input type="radio" name="comp2"' . prechecked("D") . '/>Au début&nbsp;';
echo '  <input type="radio" name="comp2"' . prechecked("F") . '/>A la fin&nbsp;';
echo '  <input type="radio" name="comp2"' . prechecked("C") . '/>Est dans&nbsp;';
echo '  <input type="radio" name="comp2"' . prechecked("S") . '/>Sonore&nbsp;</p>';
echo '</div>';

echo '<div class="rech_zone">';
echo '<div class="rech_titre">Autres éléments de l\'acte :</div>';
echo '<p>&nbsp;&nbsp;Texte : <input type="text" name="achercher3" /></p>';
echo '<p>&nbsp;&nbsp;Dans :';
echo '   <input type="radio" name="zone3" value="9" checked="checked" /> Origines &nbsp;';
echo '   <input type="radio" name="zone3" value="A" /> Professions &nbsp;';
echo '   <input type="radio" name="zone3" value="8" /> Commentaires </p>';
echo '<p>&nbsp;&nbsp;Comparaison :';
echo '  <input type="radio" name="comp3"' . prechecked("E") . '/>Exacte&nbsp;';
echo '  <input type="radio" name="comp3"' . prechecked("D") . '/>Au début&nbsp;';
echo '  <input type="radio" name="comp3"' . prechecked("F") . '/>A la fin&nbsp;';
echo '  <input type="radio" name="comp3"' . prechecked("C") . '/>Est dans&nbsp;';
echo '  <input type="radio" name="comp3"' . prechecked("S") . '/>Sonore&nbsp;</p>';
echo '</div>';

echo '<div class="rech_zone">';
echo '<div class="rech_titre">Actes recherchés :</div>';
if (CHERCH_TS_TYP == 1) {
    echo '<p>&nbsp;<input type="checkbox" name="TypN" value="N" checked="checked" />Naissances&nbsp;';
    echo '  <input type="checkbox" name="TypD" value="D" checked="checked" />Décès&nbsp;';
    echo '  <input type="checkbox" name="TypM" value="M" checked="checked" />Mariages&nbsp;';
    echo '  <input type="checkbox" name="TypV" value="V" checked="checked" />Actes divers :&nbsp;';
} else {
    echo '<p>&nbsp;<input type="radio" name="TypNDMV" value="N" checked="checked" />Naissances&nbsp;';
    echo '  <input type="radio" name="TypNDMV" value="D"  />Décès&nbsp;';
    echo '  <input type="radio" name="TypNDMV" value="M"  />Mariages&nbsp;';
    echo '  <input type="radio" name="TypNDMV" value="V"  />Actes divers :&nbsp;';
}
listbox_divers("typdivers", "***Tous***", 1);
echo '</p>';
echo '<p>&nbsp;&nbsp;Années à partir de : <input type="text" name="amin" size="4" />&nbsp; ';
echo 'jusqu\'à :    <input type="text" name="amax" size="4" /></p>';
echo '<p>&nbsp;&nbsp;Commune ou paroisse : ';
listbox_communes("ComDep", "***Toutes***", 1);
echo '</p>';
echo '</div>';

echo '<input type="hidden" name="debug" value="' . getparam('debug') . '" />';
echo '<p align="center"><input type="submit" name="Submit" value="Chercher" /></p>';

echo '</form>';
echo '</div>';

echo '<p>&nbsp;</p>';

echo '</div>';
include(__DIR__ . '/templates/front/_footer.php');
$response->setContent(ob_get_clean());
$response->send();
