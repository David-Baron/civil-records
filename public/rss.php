<?php

require(__DIR__ . '/../src/bootstrap.php');
require(__DIR__ . '/../tools/MakeRss/MakeRss.class.php');

$xtyp = $request->get('type', 'N');
$xall = $request->get('all', null);

$sql_limit = '';
$sql_params = '';

if (null === $xall) {
    $sql_limit = ' LIMIT 10';
}

if ($xtyp != 'A') {
    $sql_params = " WHERE TYPACT='" . $xtyp . "'";
}

$sql = "SELECT TYPACT AS TYP, sum(NB_TOT) AS CPT, COMMUNE, DEPART, max(DTDEPOT) AS DTE, min(AN_MIN) AS DEB, max(AN_MAX) AS FIN "
    . "FROM " . $config->get('EA_DB') . "_sums AS a "
    . $sql_params
    . ' GROUP BY COMMUNE, DEPART, TYP'
    . ' ORDER BY DTE DESC, COMMUNE, DEPART'
    . $sql_limit;

$result = EA_sql_query($sql);
$data = mysqli_fetch_all($result, MYSQLI_ASSOC);

/* CHARGEMENT DU GENERATEUR */
$rss = new GenRSS();

if ($xall !== "") {
    ob_end_clean();
} //ob_clean(); // Efface le tampon de sortie pour le RSS !!!

/* OUVERTURE DU FIL */
$rss->Load();

/* LES PARAMETRES OBLIGATOIRES */
$rss->SetTitre('Actes de ' . $config->get('SITENAME'));
$rss->SetLink($config->get('EA_URL_CE_SERVEUR') . $root . '/');
$rss->SetDetails("Dépouillement de tables et actes d'état-civil ou de registres paroissiaux");
/* LES PARAMETRES FACULTATIFS (Mettez // devant les paramètres que vous ne voulez pas renseigner) */
$rss->SetLanguage($lg);
//$rss->SetRights('copyright');
//$rss->SetEditor(EMAIL);
//$rss->SetMaster('email tech');
//$rss->SetImage('http://'.$_SERVER['SERVER_NAME'].DIR_VIGNET.$row["FICHIER"],'','lien');

/* AJOUT DES ARTICLES AU FIL */
foreach ($data as $row) {
    $titre = $row["COMMUNE"];
    if ($row["DEPART"] != "") {
        $titre .= ' [' . $row["DEPART"] . ']';
    }
    $date_rss = (new \DateTime($row["DTE"]))->format('Y-m-d'); //date_rss($row["DTE"]);
    switch ($row["TYP"]) {
        case "N":
            $typ = "Naissances/Baptêmes";
            $prog = "/actes/naissances";
            break;
        case "D":
            $typ = "Décès/Sépultures";
            $prog = "/actes/deces";
            break;
        case "M":
            $typ = "Mariages";
            $prog = "/actes/mariages";
            break;
        case "V":
            $typ = "Actes divers"; // : ".$row["LIBELLE"];
            $prog = "/actes/divers";
            break;
    }
    $titre = htmlspecialchars($row["COMMUNE"], ENTITY_REPLACE_FLAGS, ENTITY_CHARSET);
    if ($row["DEPART"] != "") {
        $titre .= ' [' . htmlspecialchars($row["DEPART"], ENTITY_REPLACE_FLAGS, ENTITY_CHARSET) . ']';
    }
    $titre .= ' : ' . $typ;
    $description = $row["CPT"] . ' ' . $typ . ' de ' . $row["DEB"] . ' à ' . $row["FIN"];
    $auteur = '';
    $url = $config->get('EA_URL_CE_SERVEUR') . $root . $prog . '?xcom=' . $row["COMMUNE"] . ' [' . $row["DEPART"] . ']';
    /* $rss->AddItem('Titre','Descripton','Auteur','Catégorie','date','http://'); */
    $rss->AddItem(
        htmlspecialchars($titre),
        htmlspecialchars($description),
        htmlspecialchars($auteur),
        $typ,
        $date_rss,
        $url
    );
}

/* FERMETURE DU FIL */
$rss->Close();

/* GENERATION DU RSS */
$rss->Generer();
