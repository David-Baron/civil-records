<?php
define('ADM', 0); // Compatibility only
$admtxt = ''; // Compatibility only
require(__DIR__ . '/next/bootstrap.php');
require(__DIR__ . '/next/_COMMUN_env.inc.php'); // Compatibility only
require(__DIR__ . '/tools/MakeRss/MakeRss.class.php');

function antispam($email)
{
    return str_replace(array("@"), array("@anti.spam.com@"), $email);
}

$max = 10;
$xtyp = getparam('type');
$xall = getparam('all');
$xcomm = $xpatr = $page = "";


pathroot($root, $path, $xcomm, $xpatr, $page);

$request = "";
if ($xall == "") {
    $limit = ' LIMIT ' . $max;
} else {
    $limit = '';
    $max = 1E4;
}

if ($xtyp == "" or $xtyp == "A") {
    $condit = "";
} else {
    $condit = " WHERE TYPACT = '" . $xtyp . "'";
}

$request = "SELECT TYPACT AS TYP, sum(NB_TOT) AS CPT, COMMUNE, DEPART, max(DTDEPOT) AS DTE, min(AN_MIN) AS DEB, max(AN_MAX) AS FIN "
    . " FROM " . $config->get('EA_DB') . "_sums AS a "
    . $condit
    . ' GROUP BY COMMUNE, DEPART, TYP  '
    . ' ORDER BY DTE desc, COMMUNE, DEPART '
    . $limit;

$result = EA_sql_query($request);

/* CHARGEMENT DU GENERATEUR */
$rss = new GenRSS();

if ($xall !== "") {
    ob_end_clean();
} //ob_clean(); // Efface le tampon de sortie pour le RSS !!!

/* OUVERTURE DU FIL */
$rss->Load();
$titre = 'Actes de ' . $config->get('SITENAME');

/* LES PARAMETRES OBLIGATOIRES */
$rss->SetTitre(htmlspecialchars($titre, ENTITY_REPLACE_FLAGS, ENTITY_CHARSET));
$rss->SetLink($config->get('EA_URL_CE_SERVEUR') . $root . '/index.php');
$rss->SetDetails("Dépouillement de tables et actes d'état-civil ou de registres paroissiaux");
/* LES PARAMETRES FACULTATIFS (Mettez // devant les paramètres que vous ne voulez pas renseigner) */
$rss->SetLanguage($lg);
//$rss->SetRights('copyright');
//$rss->SetEditor(EMAIL);
//$rss->SetMaster('email tech');
//$rss->SetImage('http://'.$_SERVER['SERVER_NAME'].DIR_VIGNET.$row["FICHIER"],'','lien');

/* AJOUT DES ARTICLES AU FIL */

$cpt = 0;
while ($row = EA_sql_fetch_array($result) and $cpt < $max) {
    $cpt++;
    $titre = $row["COMMUNE"];
    if ($row["DEPART"] != "") {
        $titre .= ' [' . $row["DEPART"] . ']';
    }
    $date_rss = date_rss($row["DTE"]);
    switch ($row["TYP"]) {
        case "N":
            $typ = "Naissances/Baptêmes";
            $prog = "tab_naiss";
            break;
        case "D":
            $typ = "Décès/Sépultures";
            $prog = "tab_deces";
            break;
        case "M":
            $typ = "Mariages";
            $prog = "tab_mari";
            break;
        case "V":
            $typ = "Actes divers"; // : ".$row["LIBELLE"];
            $prog = "tab_bans";
            break;
    }
    $titre = htmlspecialchars($row["COMMUNE"], ENTITY_REPLACE_FLAGS, ENTITY_CHARSET);
    if ($row["DEPART"] != "") {
        $titre .= ' [' . htmlspecialchars($row["DEPART"], ENTITY_REPLACE_FLAGS, ENTITY_CHARSET) . ']';
    }
    $titre .= ' : ' . $typ;
    $description = $row["CPT"] . ' ' . $typ . ' de ' . $row["DEB"] . ' à ' . $row["FIN"];
    $auteur = "";
    $url = $root . '/' . $prog . ".php?args=" . urlencode($row["COMMUNE"] . ' [' . $row["DEPART"] . ']');

    /* $rss->AddItem('Titre','Descripton','Auteur','Catégorie','date','http://'); */
    $rss->AddItem(
        htmlspecialchars($titre),
        htmlspecialchars($description),
        htmlspecialchars($auteur),
        $typ,
        $date_rss,
        $config->get('EA_URL_CE_SERVEUR') . $url
    );
}

/* FERMETURE DU FIL */
$rss->Close();

/* GENERATION DU RSS */
$rss->Generer();
