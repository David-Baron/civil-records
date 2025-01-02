<?php

use Symfony\Component\HttpFoundation\RedirectResponse;

require(__DIR__ . '/../src/bootstrap.php');

my_ob_start_affichage_continu();

$autorise_autoload = true;  // Rechargement automatisé: true|false

//---------------------------------------------------------
// Générique : Traitement affichage des lignes d'informations avec trace éventuelle dans un fichier
// Dans "_upload"  créer un fichier "nomuser-ErreurNimegue-typeactes.txt"
function Trace_Ligne_Ignore($nombre, $avec_Log, $info, $line = "\r\n") // Ex : $cptign = Trace_Ligne_Ignore( $cptign, ($logKo==1), "LIGNE INCOMPLETE " . count($acte) . "/" . $minzones, $line);
{
    global $session, $config;
    if ($avec_Log) {
        // echo "<br />" . $info . " -> Ignorée" ;
        echo "<br>" . $info . " (" . ($GLOBALS['line_num'] + 1) . ") -> Ignorée";
    }
    // Enregistre éventuellement dans un fichier sur le serveur. Réinitialisé chaque jour
    //$f = realpath('./'.UPLOAD_DIR) . '/' . $GLOBALS['userlogin'] . '-ErreurNimegue-' . $GLOBLAS['TypeActes'] . '.txt';
    $f = dirname(__FILE__) . DIRECTORY_SEPARATOR . $config->get('UPLOAD_DIR') . DIRECTORY_SEPARATOR . $session->get('user')['login'] . '-ErreurNimegue-' . $GLOBALS['TypeActes'] . '.txt';
    if (file_exists($f)) {
        $t = 'Ymd';
        $Date_COUR = date($t);
        $Date_FIC = date($t, filemtime($f)); // echo 'La ' . $Date_COUR . ' et ' . $Date_FIC . ' f ' . $f;
        if (date($t) != date($t, filemtime($f))) {
            unlink($f);
            //touch( $f );
            file_put_contents($f, date('d/m/Y H:i:s') . "\r\n", FILE_APPEND | LOCK_EX);
        }
        if ($line !== '') {
            file_put_contents($f, '=>' . $info . " (" . ($GLOBALS['line_num'] + 1) . ") || " . $line, FILE_APPEND | LOCK_EX);
        }
    }
    $nombre++;
    return $nombre; // return $nombre++; ne fonctionne pas !
}

function msgplus($msg) // ouvre la page si elle ne l'est pas encore
{
    init_page();
    msg($msg);
}

function lienautoreload($msg)
{
    global $url_de_base;
    global $autoload;
    global $TypeActes, $Origine, $autokey;
    echo '<p><a href="' . $url_de_base . '&amp;TypeActes=' . $TypeActes . '&amp;autokey=' . $autokey . '"><b>' . $msg . '</b></a>';
    if ($autoload == "Y") {
        echo '<br>ou laissez le programme continuer seul dans quelques secondes.</p>';
    } else {
        echo '</p>';
    }
}

function init_page($head = "")
{
    global $root, $session, $titre, $moderestore, $pageinited;

    if (!$pageinited) {
        if ($moderestore or $head == "") {
            // pas de remontée des infos car pas de listage des données
            $js = "";
            $bodyaction = "";
        } else {
            // Remontées des données finales
            $js = "function updatemessage() { document.getElementById('topmsg').innerHTML = document.getElementById('finalmsg').innerHTML ; }";
            $bodyaction = " onload='updatemessage()'";
        }
        $menu_data_active = 'R';
        open_page($titre, $root, $js, $bodyaction, $head);
        // Ajaxify Your PHP Functions
        include(__DIR__ . "/../tools/PHPLiveX/PHPLiveX.php");
        $ajax = new PHPLiveX(array("getBkFiles"));
        $ajax->Run(false, "../tools/PHPLiveX/phplivex.js");
?>
        <div class="main">
            <?php zone_menu(10, $session->get('user')['level'], array()); ?>
            <div class="main-col-center text-center">
                <script type="text/javascript">
                    function ShowSpinner() {
                        document.getElementById("spinner").style.visibility = "visible";
                    }
                </script>
        <?php navadmin($root, $titre);

        if ($moderestore) {
            require(__DIR__ . '/../templates/admin/_menu-data.php');
        }
        $pageinited = true;
    }
}

function quote_explode($sep, $qot, $line) // découpe la ligne selon le separateur en tenant compte des quotes
{
    if ($qot == '') {
        return explode($sep, $line);
    } else {
        $ai = 0;
        $part = "";
        $cci = strlen($line);    // ATTENTION : NE PAS METTRE mb_strlen
        $inquot = false;
        $ci = 0;
        $tabl = array();
        while ($ci < $cci) {
            if ($line[$ci] == $sep and !$inquot) { // separateur qui n'est pas dans un ensemble avec quote
                $tabl[$ai] = $part;
                //echo "<br>".$ai."-".$tabl[$ai];
                $ai++;
                $part = "";
            } elseif (($qot == '') or ($line[$ci] !== $qot)) { // CAS "SANS QUOTE" = NIMEGUE ou CE N'EST PAS UNE QUOTE
                // AJOUT DU CARACTERE DANS $part = LE CAS LE PLUS FREQUENT
                $part .= $line[$ci];
            } else {
                // CAS QUOTE BACKUP !!! if ($line[$ci] == $qot)
                if ($part == "" and !$inquot) { // quote en début de partie
                    $inquot = true;
                } elseif ($line[$ci + 1] == $qot) { // quote redoublé -> en garder un seul
                    $part .= $line[$ci];
                    $ci++; // Saute 1 caractère
                } elseif (in_array($line[$ci + 1], array($sep, chr(10), chr(13)))) { // quote suivi par séparateur ou FIN LIGNE = quote de fin
                    $inquot = false;
                } else {
                    // AJOUT DE LA QUOTE DANS $part
                    $part .= $line[$ci];
                }
            }
            $ci++; // Passe au caractère suivant
        }
        if ($part <> "") {
            $tabl[$ai] = $part;
        }
        return $tabl;
    }
}

//---------------------------------------------------------

function acte2data($acte, $moderestore)
{
    $data = array();
    $i = 0;
    $lgacte = count($acte);
    global $mdb;
    foreach ($mdb as $zone) {
        if ($i < $lgacte) {
            $data[$zone['ZONE']] = $acte[$i];
            if ($zone['ZONE'] == 'PHOTOS' and !$moderestore) { // cas particulier de la liste des photos
                $i++;  // déjà chargé le 1er !
                while ($i < $lgacte) {
                    $data[$zone['ZONE']] .= ";" . $acte[$i];
                    $i++;
                }
            }
        } else {
            $data[$zone['ZONE']] = "";
        }
        $i++;
    }
    return $data;
}

//------------------------------------------------------------------------------

function getBkFiles($typact) // Utilisée pour remplir dynamiquement la listbox selon le type d'actes
{
    global $config;

    $limit_liste = '.001.'; // ne prends que les fichiers ".001." // avant v3.2.3 prenait tout => $limit_liste = '.';
    $restfiles = mydir("../" . $config->get('DIR_BACKUP'), $config->get('EXT_BACKUP'));
    $filterdfiles = array();
    foreach ($restfiles as $bkfile) {
        if (isin($bkfile, "_" . $typact . $limit_liste) > 1) {
            $filterdfiles[] = $bkfile;
        }
    }

    if (!empty($filterdfiles[0])) {
        $k = 0;
        $options[$k] = array("value" => "", "text" => ("Sélectionner un fichier"));
        foreach ($filterdfiles as $bkfile) {
            $k++;
            $options[$k] = array("value" => "$bkfile", "text" => (mb_substr($bkfile, 11)));
        }
    } else {
        $options[0] = array("value" => "", "text" => ("Pas de fichiers à restaurer de ce type"));
    }
    return $options;
}

function messageFinChargement()
{
    global $root, $totactes, $totpart, $path, $TypeActes, $T0, $commune, $depart, $script, $multiples_communes;
    echo '<p>Chargement terminé, ' . $totactes . ' actes traités en ' . ($totpart + 1) . ' étapes . </p>';
    if ($multiples_communes > 1) {
        echo "<p>Les statistiques n'ont pas été recalculées automatiquement.<br>";
        echo '<a href="' . $path . "/maj_sums.php" . '?xtyp=' . $TypeActes . '&mode=A&com=">Cliquez ici pour recalculer ces statistiques</a></p>';
        echo '</p>';
    } else {
        maj_stats($TypeActes, $T0, $path, "C", $commune, $depart);
        echo '<p>Voir la liste des actes de ';
        echo '<a href="' . $root . $script . '?xcomm=' . stripslashes($commune . ' [' . $depart . ']') . '"><b>' . stripslashes($commune) . '</b></a>';
        echo '</p>';
    }
}

$T0 = time();
$delaireload = 10;
$MT0 = microtime_float();
$Max_time = min(ini_get("max_execution_time") - 3, $config->get('MAX_EXEC_TIME'));
$Max_size = return_bytes(ini_get("upload_max_filesize"));

pathroot($root, $path, $xcomm, $xpatr, $page);

$Origine = getparam('Origine');

if ($Origine == "B") {
    $moderestore = true;
    $needlevel = 8;  // niveau d'accès
    $titre = "Restauration d'un backup";
    $lemess = "un backup Expoactes";
    $lefich = "Premier fichier à restaurer :";
    $txtaction = "la restauration";
    $autoload = "Y";
} else {
    $Origine = "N";
    $moderestore = false;
    $needlevel = 6;  // niveau d'accès (anciennement 5)
    $titre = "Chargement d'actes NIMEGUE";
    $txtaction = "le chargement";
    $lemess = "des actes NIMEGUE";
    $lefich = "Fichier de NIMEGUE (V2 ou V3) :";
    $autoload = "Y";
    $Max_time = $Max_time - 5; // réduit les temps maxi pour laisser du temps au calcul des stats (Auto pour Nimegue)
}

if (!$userAuthorizer->isGranted($needlevel)) {
    $response = new RedirectResponse("$root/admin/");
    $response->send();
    exit();
}

$missingargs = true;
$newload = false;
///////////////////////$emailfound=false;
$oktype = false;
$cptmaj = 0;
$cptadd = 0;
$cptign = 0;
$cptver = 0;
$cptdeja = 0;
$avecidnim = false;
$resume = false;
$bkfilename = "";
$file_no = 0;
$pageinited = false;
$numfile = 0;
$numpart = 0;
$totactes = 0;

$TypeActes = getparam('TypeActes');
if ($TypeActes == "") {
    $TypeActes = 'N';
}
if (!isset($lg)) {
    $lg = 'fr';
}
$mdb = load_zlabels($TypeActes, $lg);

$Dedoublon  = getparam('Dedoublon');

$Filiation  = getparam('Filiation', 0); // for checked
$AnneeVide  = getparam('AnneeVide', 0); // for checked
$AVerifier  = getparam('AVerifier', 0); // for checked
$logOk      = getparam('LogOk', 0); // for checked
$logKo      = getparam('LogKo', 0); // for checked
$logRed     = getparam('LogRed', 0); // for checked
$deposant   = getparam('deposant');
$passlign   = getparam('passlign');
if ($passlign == '') {
    $passlign = 0;
}
$photo           = getparam('photo');
$trans           = getparam('trans');
$verif           = getparam('verif');
$commune = '';
$depart = '';
$NewId      = getparam('NewId', 0); // for checked
$url_de_base = basename($_SERVER['PHP_SELF']) . '?action=go&amp;Origine=' . $Origine;
$multiples_communes = 0;

if (getparam('action') == 'submitted' and $Origine <> "B") {
    // setcookie("chargeNIM", $Filiation . $AnneeVide . $AVerifier . $logOk . $logKo . $logRed . $Dedoublon, time() + 60 * 60 * 24 * 60);
}  // 60 jours

$autokey = getparam('autokey');
$tokenfile  = "../" . $config->get('DIR_BACKUP') . $session->get('user')['login'] . '.txt';
$bkfile = getparam('bkfile');
if (empty($bkfile)) {
    $bkfile = "../" . $config->get('DIR_BACKUP') . getparam('bkfile2');
}  // lecture directe
if ($autokey == "" or $autokey == "NEW") {
    if (($tof = @fopen($tokenfile, "r")) and $autokey != "NEW") {
        $vals = explode(";", fgets($tof));
        fclose($tof);
        if ($vals[0] == "EA_RESTORE" and $vals[14] > 0 and $vals[1] <> "NEW") {
            $autokey = $vals[1];
            $totactes = $vals[2];
            $uploadfile = $vals[4];
            $TypeActes = mb_substr($uploadfile, -9, 1);
            msgplus('La dernière restauration n\'était pas terminée !');
            echo '<p><a href="' . $url_de_base . '&amp;TypeActes=' . $TypeActes . '&amp;autokey=' . $autokey . '">Poursuivre la restauration abandonnée</a></p>';
            echo '<p>ou</p>';
            echo '<p><a href="' . $url_de_base . '&amp;autokey=NEW' . '">Commencer une nouvelle restauration</a></p>';
            die();
        } else {
            $newload = true;
            $autokey = md5(uniqid(rand(), true)); // généré si lancement direct
            //echo 'NEW interne';
        }
    } else {
        $newload = true;
        $autokey = md5(uniqid(rand(), true)); // généré lancement récupéré new
        //echo 'NEW externe';
    }
}

$today = today();
$gosuivant = 0; // N° du fichier suivant
$metahead = '';
$ok = false;

if ($autoload == 'Y' and $autorise_autoload) {
    $metahead = '<META HTTP-EQUIV="Refresh" CONTENT="' . $delaireload . '; URL=' . $url_de_base . '&amp;TypeActes=' . $TypeActes . '&amp;autokey=' . $autokey . '">';
}

if (getparam('action') == 'submitted') { // Lancement d'une opération de chargement ou de restauration
    if (empty($TypeActes)) {
        msgplus('Vous devez préciser le type des actes.');
    } elseif ($bkfile == 'NULL') {
        // Cas d'une importation NIMEGUE -> fichier téléchargé en direct
        if (empty($_FILES['Actes']['tmp_name']) and empty($_FILES['Actes']['name'])) {
            msgplus('Pas trouvé le fichier spécifié.');
        } elseif (!is_uploaded_file($_FILES['Actes']['tmp_name'])) {
            msgplus('Méthode de téléchargement non valide.');
            writelog('Possible tentative de téléchargement NIM frauduleux');
        } elseif (empty($_FILES['Actes']['tmp_name']) and !empty($_FILES['Actes']['name'])) {
            msgplus('Fichier impossible à charger (probablement trop volumineux).');
        } elseif (empty($TypeActes)) {
            msgplus('Vous devez préciser le type des actes.');
        } elseif (strtolower(mb_substr($_FILES['Actes']['name'], -4)) <> ".txt") { //Vérifie que l'extension est bien '.TXT'
            // type TXT
            msgplus("Type de fichier incorrect (.TXT attendu)");
        } elseif (empty($_FILES)) {
            msgplus('Le fichier n\'a pu être chargé, il est peut-être trop volumineux.');
            // NB : Si la taille dépasse post_max_size, alors rien n'est renvoyé et la détection est impossible
        } else {
            $ok = true;
        }
        if ($ok) {
            // Stockage du fichier chargé
            $uploadfile = $config->get('UPLOAD_DIR') . '/' . $session->get('user')['login'] . '.csv';
            $filename = $_FILES['Actes']['tmp_name'];
            // Si le fichier proposé est vide ET qu'il existe le fichier "_upload/nomuser-LOCAL-typeactes.csv" ex: admin-LOCAL-M.csv , c'est ce fichier qui sera traité. A NOTER : le fichier en question est renommé en "nomuser.csv" pour le traitement, ainsi en cas de plantage, il faut remettre le fichier en place.
            $vide = false;
            if (($csv_file = @fopen($filename, "r"))) {
                $line = fgets($csv_file);
                if ($line === false) {
                    $vide = true;
                }
                fclose($csv_file);
            }
            $local_file = dirname(__FILE__) . '/' . $config->get('UPLOAD_DIR') . '/' . $session->get('user')['login'] . '-LOCAL-' . $TypeActes . '.csv';
            $allow_move = false;
            if (($vide) and (file_exists($local_file))) {
                $_FILES['Actes']['tmp_name'] = $filename = $local_file;
                if (rename($_FILES['Actes']['tmp_name'], $uploadfile)) {
                    $allow_move = true;
                }
            } else {
                if (move_uploaded_file($_FILES['Actes']['tmp_name'], $uploadfile)) {
                    $allow_move = true;
                }
            }
            if (!$allow_move) {
                msgplus('033 : Impossible de ranger le fichier dans "' . $config->get('UPLOAD_DIR') . '".');
                $missingargs = true;
                $ok = false;
            }
        }
        $numpart = 1;
        $totpart = 0;
    } else {
        // Cas de la restauration d'un backup
        $ok = true;
        if (!file_exists($bkfile) or (!is_file($bkfile))) {
            msgplus('Pas trouvé le fichier spécifié.');
            $missingargs = true;
            $ok = false;
            $TypeActes = '';
        }
        $uploadfile = $bkfile;
        $numfile = mb_substr($uploadfile, -7, 3);
        $filename = $bkfile;
        $logOk = $logRed = 0;
        $logKo = 1;
        $numpart = 1;
        $totpart = 0;
    }
} else {
    if (!$newload) { // On continue un chargement déjà commencé
        if ($Origine == "N") {
            if (isset($_COOKIE['chargeNIM'])) {
                $chargeNIM  = $_COOKIE['chargeNIM'] . str_repeat(" ", 10);
            } else {
                $chargeNIM  = "000111M";
            }
        }  // vals par défaut
        else {
            $chargeNIM  = "000000M";
        }  // vals pour restauration
        $Filiation  = $chargeNIM[0];
        $AnneeVide  = $chargeNIM[1];
        $AVerifier  = $chargeNIM[2];
        $logOk      = $chargeNIM[3];
        $logKo      = $chargeNIM[4];
        $logRed     = $chargeNIM[5];
        $Dedoublon  = $chargeNIM[6];
        // récupération des valeurs dans le fichier
        $ok = true;
        if (($tof = @fopen($tokenfile, "r")) === false) {
            $ok = false;
            msgplus('Impossible d\'ouvrir le fichier TOKEN (' . $tokenfile . ') en lecture!');
        } else {
            $ok = true;
            $continue = 0;
            $vals = explode(";", fgets($tof));
            //	{ print '<pre>TOF : ';  print_r($vals); echo '</pre>'; }
            fclose($tof);
            if ($vals[0] <> "EA_RESTORE") {
                $ok = false;
                msgplus('Fichier TOKEN invalide');
            } elseif ($autokey == "NEW") {
                $ok = false;
            } elseif ($vals[1] <> $autokey) {
                $ok = false;
                msgplus('Clé invalide ou non trouvée');
            } else {
                $totactes = $vals[2];
                $file_no = $vals[3];
                $uploadfile = $vals[4];
                if ($Origine == "B") {
                    $numfile = mb_substr($uploadfile, -7, 3);
                } else {
                    $numfile = 0;
                }

                $deposant = $vals[5];
                $passlign = $vals[6];
                $totpart = $vals[7];
                $numpart = $vals[8];
                $commune = $vals[9];
                $depart = $vals[10];
                $photo  = $vals[11];
                $trans  = $vals[12];
                $verif  = $vals[13];
                $continue = $vals[14];
                if (isset($vals[15])) {
                    $multiples_communes = $vals[15];
                }
                $numpart = $numpart + 1;
            }
        }
    } else {  // appel simple de la page
        $ok = false;
        init_page();
    }
}
switch ($TypeActes) {
    case "N":
        $ntype = "Naissance";
        $table = $config->get('EA_DB') . "_nai3";
        $script = '/tab_naiss.php';
        break;
    case "V":
        $ntype = "Divers";
        $table = $config->get('EA_DB') . "_div3";
        $script = '/tab_bans.php';
        break;
    case "M":
        $ntype = "Mariage";
        $table = $config->get('EA_DB') . "_mar3";
        $script = '/tab_mari.php';
        break;
    case "D":
        $ntype = "Décès";
        $table = $config->get('EA_DB') . "_dec3";
        $script = '/tab_deces.php';
        break;
}

if ($ok) {
    if (isset($continue) and $continue == 0) {
        init_page("");
        echo '<h2 align="center">' . $titre . '</h2>';
        $missingargs = false;
        if ($Origine == "B") {
            echo '<p>Restauration terminée, ' . $totactes . ' actes traités en ' . ($totpart + 1) . ' étapes.</p>';
            echo '<p>Optimisation de la table ...';
            $reqmaj = "ANALYZE TABLE " . $table . ";";
            $res = EA_sql_query($reqmaj);
            echo " Ok.</p>";
            writelog($reqmaj);
            if ($multiples_communes == 1) {
                maj_stats($TypeActes, $T0, $path, "C", $commune, $depart);
            } else {
                maj_stats($TypeActes, $T0, $path, "A", "");
            }
        } else {
            messageFinChargement();
        }
    } else {
        // Suite du traitement
        // fichier d'actes
        $missingargs = false;
        //
        $csv_total_line = 0;
        if ((! $moderestore) and (($totactes != 0) and ($passlign != 0))) { // Chargement Nimegue poursuite. On ne relit pas le fichier $passlign est le nombre de lignes déjà traitées / $totactes
            $csv_total_line = $totactes;
        } else { // autres cas Restauration ou 1ère boucle Nimegue. On compte le nombre de lignes du fichier.
            if (($csv_file = @fopen($uploadfile, "r"))) {
                $line = fgets($csv_file);
                while ($line !== false) {
                    $csv_total_line++;
                    $line = fgets($csv_file);
                }
                fclose($csv_file);
            }
            if (! $moderestore) { // Chargement Nimegue. Mémorise le nombre total d'acte
                $totactes = $csv_total_line;
            }
        }
        // On a calculé ou récupéré $csv_total_line (et $totactes pour Nimegue)

        $fichier_present = false;
        $line = '';
        // Initialisations nécessaires dans le cas de fichier vide, absent ou erreur ouverture
        $line_num = -1;
        $no_ligne = 0;
        $acte = array('');
        if ($csv_total_line > 0) {
            if (($csv_file = @fopen($uploadfile, "r"))) {
                $fichier_present = true;
                $line = fgets($csv_file);
                $line_num = 0;
            }
        }
        if ($fichier_present) {
            if (! $moderestore) { // pas de " dans les NIMEGUES
                $separateur_champ = '';
            } else {
                $separateur_champ = '"';
            }
            // Traiter la ligne 0
            $no_ligne = 0;
            $fichiersuivant = false;

            $T1 = $T0;

            $traiter_fichier = true;
            $isUTF8 = false;
            if ($line_num == 0) { // SI c'est la première ligne. C'est toujours le cas maintenant en principe

                // on décode la première pour vérifier le contenu
                $acte = quote_explode(';', $separateur_champ, $line);

                switch ($acte[0]) {
                    case "EA2":
                        $identif = "EA2";
                        $mdb = load_zlabels($TypeActes, $lg, "EA2");
                        $minzones = count($mdb);
                        break;
                    case "EA3":
                        $identif = "EA3";
                        $mdb = load_zlabels($TypeActes, $lg, "EA3");
                        $minzones = count($mdb);
                        break;
                    case "EA32":
                        $identif = "EA32";
                        $mdb = load_zlabels($TypeActes, $lg, "EA3");
                        $minzones = count($mdb);
                        $isUTF8 = true;
                        break;
                    case "NIMEGUE-V2":
                        $lemess = "des actes NIMEGUE version 2";
                        $lefich = "Fichier de NIMEGUE-V2 :";
                        $identif = "NIMEGUE-V2";
                        $mdb = load_zlabels($TypeActes, $lg, "NIM2");
                        $minzones = count($mdb);
                        break;
                    case "NIMEGUEV3":
                        $lemess = "des actes NIMEGUE version 3";
                        $lefich = "Fichier de NIMEGUEV3 :";
                        $identif = "NIMEGUEV3";
                        $mdb = load_zlabels($TypeActes, $lg, "NIM3");
                        $minzones = count($mdb) - 1; // PHOTOS est facultatif
                        break;
                    default:
                        $identif = "PASBON";
                }
                //	{ print '<pre>';  print_r($acte); echo '</pre>'; }
                //	echo '<pre>'; print_r($mdb); echo '</pre>';

                $data = acte2data($acte, $moderestore);

                if ($data['BIDON'] <> $identif) {
                    echo "<div id='topmsg'></div>";
                    $errdesc = 'Le fichier <i>"' . $filename . '"</i> ne contient pas ' . $lemess . $data['BIDON'];
                    msgplus($errdesc);
                    $no_ligne = -1;
                    $traiter_fichier = false; // break;
                } elseif ($data['TYPACT'] <> $TypeActes) {
                    echo "<div id='topmsg'></div>";
                    $errdesc = 'Le fichier "' . $filename . '" ne contient pas des actes du type annoncé <br />mais des actes ' . $acte[5];
                    msgplus($errdesc);
                    $no_ligne = -1;
                    $traiter_fichier = false; // break;
                } else {
                    $oktype = true;
                    init_page($metahead);
                    echo '<h2 align="center">' . $titre . '</h2>';
                    if ($numfile > 0) {
                        echo '<p>Fichier : <b>' . mb_substr($uploadfile, 11) . '</b></p> ';
                    }
                    if ($numpart > 1) {
                        echo '<p>Partie ' . $numpart . '</p>';
                    }

                    echo '<div id="topmsg"><p>Traitement en cours ... <b>!! NE PAS INTERROMPRE !!</b></p><p align="center"><img src="../img/spinner.gif"></p></div>';
                    my_flush(); // On affiche un minimum
                }
            } // ligne 0
            // Se positionner au bon endroit dans le fichier : SAUTER LES LIGNES A PASSER
            $trouve = false;
            while ((! $trouve) and  ($line !== false)) {
                if ($line_num < $passlign) {
                    $line = fgets($csv_file);
                    $line_num++;
                } else {
                    $trouve = true;
                }
            } // On est positionné sur la ligne à traiter
            if ($traiter_fichier) { // Blocage sur erreur ligne 0 =>  if ($no_ligne !== -1)
                while ($line !== false) { // par ligne
                    my_flush(200);
                    if (time() - $T0 > $Max_time) {
                        break;
                    } // Le temps limite d'un script est atteint, on sort de la boucle pour relance
                    /*
                    $TX = time();
                    if ($T1<>$TX)
                        {
                      echo "<p>Déjà ".($TX-$T0)." sec.</p>";
                      $T1=$TX;
                      }
                    */
                    $no_ligne = $line_num;
                    $reqmaj = "";
                    if (!$isUTF8) { // Supprime les caractères réservés qui plantent l'encodage
                        $line = str_replace(array(chr(129), chr(141), chr(143), chr(144), chr(157)), '', $line); // 81, 8d, 8f, 90, 9D
                        $line = ea_utf8_encode($line); // ADLC 24/09/2015
                    }
                    if ($line === false) { // Erreur d'encodage
                        $cptign = Trace_Ligne_Ignore($cptign, ($logKo == 1), "LIGNE ERREUR DE DONNÉES !"); // $line est false, paramètre ignoré donc simple ligne d'information !!!
                        $acte = array(0 => 'vide');
                    } else {
                        $acte = quote_explode(';', $separateur_champ, $line);
                    }

                    if ($oktype == true and $line_num >= $passlign) {    // --------- Traitement ----------
                        $data = acte2data($acte, $moderestore);
                        if ($data["BIDON"] == "EA_NEXT") {
                            $fichiersuivant = true;
                            $gosuivant = $acte[1];
                            break;
                        } elseif ($data["BIDON"] <> $identif) { // format invalide
                            $cptign = Trace_Ligne_Ignore($cptign, ($logKo == 1), "LIGNE INVALIDE", $line);
                        } elseif (count($acte) < $minzones) { // format invalide = nombre de zones
                            $cptign = Trace_Ligne_Ignore($cptign, ($logKo == 1), "LIGNE INCOMPLETE " . count($acte) . "/" . $minzones, $line);
                        } else { // complet
                            $lignvalide = false;
                            $missingargs = false;
                            if ($data["COMMUNE"] !== '') {
                                if (($data["COMMUNE"] . ' [' . $data["DEPART"] . ']' <> $commune . ' [' . $depart . ']')) {
                                    $multiples_communes++;
                                }
                                $commune = $data["COMMUNE"];
                                $depart = $data["DEPART"];
                            };
                            $datetxt = $data["DATETXT"];
                            if ($TypeActes == "V") {
                                $decal = 2;
                            } else {
                                $decal = 0;
                            }
                            $nom     = trim($data["NOM"]);
                            $pre     = trim($data["PRE"]);
                            $idnim   = $data["IDNIM"];
                            if (trim($idnim) == '') {
                                $idnim = $data["IDNIM"] = 0;
                            }
                            $log = '<br />' . $ntype . ' ' . $nom . ' ' . $pre . ' le ' . $datetxt . ' à ' . stripslashes($data["COMMUNE"]) . " : ";
                            if ($moderestore) { // Cas Backup
                                if ($idnim == 'NULL') {
                                    $idnim = $data["IDNIM"] = 0;
                                } // corrige les exports avec IDNIM "NULL"
                                $ladate = $data["LADATE"];
                                $MauvaiseDate = 0;
                                ajuste_date($datetxt, $ladate, $MauvaiseDate);
                                $data['LADATE'] = $ladate;
                                if ($NewId == 1) {
                                    $id     = 'null';
                                    $condit = "ID=0";
                                } else {
                                    $id     = $data["ID"];
                                    $condit = "ID=" . $id;
                                }
                                $deposant = $data["DEPOSANT"];
                                $dtdepot = $data["DTDEPOT"];
                                $dtmodif = $data["DTMODIF"];
                                if ($acte[0] == "EA2") {
                                    $photo = "";
                                    $trans = "";
                                    $verif = "";
                                } else {
                                    $photo = $data["PHOTOGRA"];
                                    $trans = $data["RELEVEUR"];
                                    $verif = $data["VERIFIEU"];
                                }
                                $lignvalide = true;
                            } else { // mode nimegue
                                $id      = 'null';
                                $dtdepot = $today;
                                $dtmodif = $today;
                                $ladate = "";
                                $avecidnim = ($data["IDNIM"] > 0);
                                $MauvaiseDate = 0;
                                ajuste_date($datetxt, $ladate, $MauvaiseDate);
                                if (($data["P_NOM"] . $data["P_PRE"] . $data["M_NOM"] . $data["M_NOM"] == "") and ($Filiation == 1)) { // pas le nom d'au moins un parent
                                    $cptign = Trace_Ligne_Ignore($cptign, ($logKo == 1), $log . "INCOMPLET", $line);
                                } elseif ($avecidnim and intval($idnim) == 0 and $acte[0] <> "EA2") {
                                    $cptign++;
                                    $cptign = Trace_Ligne_Ignore($cptign, ($logKo == 1), $log . "INVALIDE (; dans une zone)", $line);
                                } elseif ($data["COMMUNE"] == "") { // acte sans commune
                                    $cptign = Trace_Ligne_Ignore($cptign, ($logKo == 1), $log . "PAS DE COMMUNE", $line);
                                } elseif ($nom == "") { // acte sans nom
                                    $cptign = Trace_Ligne_Ignore($cptign, ($logKo == 1), $log . "PAS DE NOM", $line);
                                } elseif (($AnneeVide == 1) and ($MauvaiseDate == 1)) { // acte avec année incomplète (testée dans ajuste_date)
                                    $cptign = Trace_Ligne_Ignore($cptign, ($logKo == 1), $log . "ANNEE INCOMPLETE", $line);
                                } elseif (($AVerifier == 1) and (mb_strpos($acte[8], "RIF") > 0)) { // acte restant à vérifier
                                    $cptver = Trace_Ligne_Ignore($cptver, ($logKo == 1), $log . "A VERIFIER", $line);
                                } else {  // complet
                                    $lignvalide = true;
                                    $condit = "COMMUNE='" . sql_quote($commune) . "' AND DEPART='" . sql_quote($depart);
                                    if ($avecidnim and ($Dedoublon == 'I' or $Dedoublon == 'M')) {
                                        $condit .= "' AND IDNIM=" . $idnim;
                                    } else {
                                        $condit .= "' AND DATETXT='" . $datetxt . "' AND NOM='" . sql_quote($nom) . "' AND PRE='" . sql_quote($pre) . "'";
                                    }
                                }
                            }
                            if ($lignvalide) {
                                // Dédoublonnage
                                if ($Dedoublon <> 'A' and $NewId == 0) {
                                    $sql = "SELECT ID FROM " . $table . " WHERE " . $condit . ";";
                                    $result = EA_sql_query($sql);
                                    $nb = EA_sql_num_rows($result);
                                } else {
                                    $nb = 0;
                                }
                                if ($nb > 0) { // record existe
                                    if ($moderestore or $Dedoublon == 'M') { // MAJ
                                        $ligne = EA_sql_fetch_assoc($result);
                                        $id = $ligne["ID"];
                                        //------
                                        $reqtest = "SELECT * FROM " . $table . " WHERE " . $condit . ";";
                                        $restest = EA_sql_query($reqtest);
                                        $ligtest = implode('|', EA_sql_fetch_row($restest));
                                        $ligtest = mb_substr($ligtest, 0, mb_strlen($ligtest) - 10); // éliminer la date de dernière modif
                                        $crc1 = crc32($ligtest);
                                        //echo $crc1. " --> ".$ligtest;
                                        //------
                                        $action = "MISE A JOUR";

                                        $listmaj = "";
                                        $suivant = ''; // Gère l'existance ou pas d'un premier cas pour éviter un test
                                        foreach ($mdb as $zone) {
                                            $listmaj .= $suivant . $zone['ZONE'] . "='" . sql_quote($data[$zone['ZONE']]) . "'";
                                            $suivant = ', ';
                                        }
                                        $listmaj .= ",LADATE = '" . $ladate . "'";
                                        $listmaj .= ",DEPOSANT = '" . $deposant . "'";
                                        $listmaj .= ",PHOTOGRA = '" . sql_quote($photo) . "'";
                                        $listmaj .= ",RELEVEUR = '" . sql_quote($trans) . "'";
                                        $listmaj .= ",VERIFIEU = '" . sql_quote($verif) . "'";
                                        $listmaj .= ",DTMODIF= '" . $today . "' ";
                                        $reqmaj = "UPDATE " . $table . " SET " . $listmaj . " WHERE ID=" . $id . ";";
                                    } // MAJ
                                    else {
                                        $cptdeja = Trace_Ligne_Ignore($cptdeja, ($logRed == 1), $log . "Acte existe déjà", ''); // '' pour ne pas avoir toutes les lignes identique dans le log. // Mode Ignorer NIMEGUE
                                    }
                                } // record existe
                                else { // ADD
                                    $action = "AJOUT";
                                    $crc1 = 0;
                                    $listzon = "";
                                    $listmaj = "";
                                    $suivant = ''; // Gère l'existance ou pas d'un premier cas pour éviter un test
                                    foreach ($mdb as $zone) {
                                        if ($zone['ZONE'] == "LADATE") {
                                            break;
                                        }  // les autres sont gérés autrement ci-dessous
                                        $listzon .= $suivant . $zone['ZONE'];
                                        $listmaj .= $suivant . "'" . sql_quote($data[$zone['ZONE']]) . "'";
                                        $suivant = ', ';
                                    }
                                    //if (true) // (!$moderestore)  // dans tous les cas à présent
                                    {
                                        $listzon .= ", LADATE, ID, DEPOSANT, PHOTOGRA, RELEVEUR, VERIFIEU, DTDEPOT, DTMODIF";
                                        $listmaj .= ",'" . $ladate . "'," . $id . "," . $deposant . ",'" . sql_quote($photo) . "','" . sql_quote($trans) . "','" . sql_quote($verif) . "','" . $dtdepot . "','" . $dtmodif . "'";
                                    }
                                    //echo "<p>".$listzon." <br>--> ".$listmaj." <br>cond-> ".$condit;
                                    $reqmaj = "INSERT INTO " . $table . "(" . $listzon . ") VALUES (" . $listmaj . ");";
                                } // ADD
                                //	if ($cptadd+$cptmaj<5)	echo "<p>".$reqmaj;

                                if (!empty($reqmaj)) {
                                    if ($result = EA_sql_query($reqmaj)) {
                                        if ($NewId == 0) { // pas d'ajout forcé
                                            //------
                                            $reqtest = "SELECT *   FROM " . $table . " WHERE " . $condit . ";";
                                            $restest = EA_sql_query($reqtest);
                                            $ligtest = implode('|', EA_sql_fetch_row($restest));
                                            $ligtest = mb_substr($ligtest, 0, mb_strlen($ligtest) - 10); // éliminer la date de dernière modif
                                            $crc2 = crc32($ligtest);
                                            //------
                                        } else {
                                            $crc2 = 1;
                                        } // bidon
                                        if ($crc2 != $crc1) {
                                            if ($logOk == 1) {
                                                echo $log . $action . ' -> Ok.';
                                            }
                                            if ($nb > 0) {
                                                $cptmaj++;
                                            } else {
                                                $cptadd++;
                                            }
                                        } else {
                                            $cptdeja = Trace_Ligne_Ignore($cptdeja, ($logRed == 1), $log . "Identique pas mis à jour", ''); // '' pour ne pas avoir toutes les lignes identique dans le log. // Mode "Mise à jour Nimegue"
                                        }
                                    } else {
                                        echo ' -> Erreur Fatale : ';
                                        echo '<p>' . EA_sql_error() . '<br />' . $reqmaj . '</p>';
                                        $cptign = Trace_Ligne_Ignore($cptign, ($logKo == 1), $log . "ERREUR FATALE", $line);
                                    }
                                } // reqmaj pas vide
                            }  // complet
                        } // lignvalide
                    }    // --------- Traitement ----------
                    // Passe à la ligne suivante
                    $line = fgets($csv_file);
                    $line_num++;
                } // par ligne
            } // traiter_fichier

            fclose($csv_file);
        } // Fin fichier_present

        if ($no_ligne == -1) {
            $continue = 0;  // fin sur erreur
        } elseif ($acte[0] == "") { //($no_ligne==0)
            msg("Fichier " . $uploadfile . " vide ou absent !");
        } elseif ($no_ligne + 1 < $csv_total_line) { // Si interruption
            // Fin du temps -> relancer pour continuer
            echo "<div id='finalmsg'>";
            echo "<p><b>Attention : Temps maximum d'exécution écoulé</b></p>";
            echo '<p>' . ($no_ligne + 1) . ' lignes déjà traitées;</p>';
            echo '<p>Il reste ' . ($csv_total_line - $no_ligne - 1) . ' lignes à traiter.</p>';
            $resume = true;
            if ($passlign > $no_ligne) {
                echo 'PROGRAMME BOUCLE ';
            }
            $passlign = $no_ligne + 1;
            $totpart = $totpart + 1;
            $continue = 1;
            lienautoreload('Continuez immédiatement ' . $txtaction);
            echo "</div>";
        } elseif ($gosuivant > 0) { // Si fichier suivant
            // Passer au fichier suivant
            $file_no = $file_no + 1;
            $numfile = $numfile + 1;
            $numpart = 0;
            $totpart = $totpart + 1;
            $uploadfile = mb_substr($uploadfile, 0, -7) . zeros($numfile, 3) . '.bea';
            $totactes = $totactes + $no_ligne;
            $passlign = 0;
            echo "<div id='finalmsg'>";
            lienautoreload('Continuez immédiatement avec le fichier ' . zeros($numfile, 3));
            echo "</div>";
            $continue = 2;
        } else {
            // Fin du dernier fichier
            echo "<div id='finalmsg'>";
            echo "<p>Traitement terminé.</p>";
            $continue = 0;
            // Dans le cas Import NIMEGUE, no_ligne est le numéro absolu.
            if (!$moderestore) {
                $totactes = $no_ligne + 1;
            } else { // Dans le cas restore, no_ligne est le nombre du fichier courant à ajouter au total cumulé.
                $totactes = $totactes + $no_ligne + 1;
            }
            if (!($moderestore)) {
                messageFinChargement();
            } else {
                lienautoreload('Recalculer les statistiques');
            }
            echo "</div>";
        }
    }
} // fichier d'actes

//if ($resume)
if (!$missingargs) {
    $tof = @fopen($tokenfile, "w");
    $token  = "EA_RESTORE;" . $autokey . ";";      // 0 et 1
    $token .= ($totactes) . ";" . ($file_no) . ";";     // 2 et 3 : total des actes et indice fichier backup
    $token .= $uploadfile . ";" . ($deposant) . ";"; // 4 et 5 : nom fichier import NIMEGUE + code déposant
    $token .= ($passlign) . ";";                 // 6  : lignes à passer
    $token .= ($totpart) . ";" . ($numpart) . ";";   // 7 et 8 : totale de parties et de la partie traitée
    $token .= ($commune) . ";" . ($depart) . ";";         // 9 et 10: commune et depart
    $token .= ($photo) . ";" . ($trans) . ";" . ($verif) . ";";    // 11,12 et 13: credits

    if (!isset($continue)) {
        $continue = 0;
    }
    $token .= ($continue) . ";";                                 // 14 : continue ?
    $token .= ($multiples_communes) . ";"; // 15 : nombre de communes traitées
    fwrite($tof, $token . "\r\n");
    fclose($tof);
}

init_page("");

//Si pas tout les arguments nécessaire, on affiche le formulaire
if ($missingargs) {
    if (getparam('action') == '') { // parametres par défaut
        if (isset($_COOKIE['chargeNIM'])) {
            $chargeNIM  = $_COOKIE['chargeNIM'] . str_repeat(" ", 10);
        } else {
            $chargeNIM  = "000111M";
        }
        $Filiation  = $chargeNIM[0];
        $AnneeVide  = $chargeNIM[1];
        $AVerifier  = $chargeNIM[2];
        $logOk      = $chargeNIM[3];
        $logKo      = $chargeNIM[4];
        $logRed     = $chargeNIM[5];
        $Dedoublon  = $chargeNIM[6];
        $TypeActes = "X";
    }
    if ($moderestore) {
        $ajaxbackup = ' onClick="' . "getBkFiles(this.value, {'content_type': 'json', 'target': 'bkfile', 'preloader': 'prl'})" . '" ';
    } else {
        $ajaxbackup = '';
    }

    echo "<div id='topmsg'></div>";
    echo '<form method="post" enctype="multipart/form-data">';
    echo '<h2 align="center">' . $titre . '</h2>';
    echo '<table class="m-auto" summary="Formulaire">';
    echo "<tr>";
    echo '<td>Type des actes : </td>';
    echo '<td>';
    echo '<input type="radio" name="TypeActes" value="N"' . ($TypeActes === 'N' ? ' checked' : '') . $ajaxbackup . '>Naissances<br>';
    echo '<input type="radio" name="TypeActes" value="M"' . ($TypeActes === 'M' ? ' checked' : '') . $ajaxbackup . '>Mariages<br>';
    echo '<input type="radio" name="TypeActes" value="D"' . ($TypeActes === 'D' ? ' checked' : '') . $ajaxbackup . '>Décès<br>';
    echo '<input type="radio" name="TypeActes" value="V"' . ($TypeActes === 'V' ? ' checked' : '') . $ajaxbackup . '>Actes divers<br>';
    echo '</td>';
    echo "</tr>";
    echo "<tr>";
    echo "<td>" . $lefich . " </td>";
    if ($moderestore) {
        echo '<td> <select id="bkfile" name="bkfile">';
        echo '<option value="">Choisir d\'abord le type d\'acte</option> ';
        echo '</select><img id="prl" src="' . $root . '/assets/img/minispinner.gif" style="visibility:hidden;"></td>';
    } else {
        echo '<td><input type="file" size="62" name="Actes">';
        // MAX_FILE_SIZE doit précéder le champs input de type file
        echo '<input type="hidden" name="MAX_FILE_SIZE" value="' . $Max_size . '">';
        echo '<input type="hidden" name="bkfile" value="NULL">';
        echo "</td>";
    }
    echo " </tr>";
    if ($moderestore) {
        echo "<tr><td colspan=\"2\">&nbsp;</td></tr>";
        echo "<tr>";
        echo '<td>Migration de données : </td>';
        echo '<td>';
        echo '<input type="checkbox" name="NewId" value="1"> AJOUTER toutes ces données à la base SANS vérification <br>'; // jamais par défaut
        echo '</td>';
        echo "</tr>";
    }
    if (!$moderestore) {
        if (isin('OFA', metadata('AFFICH', 'PHOTOGRA')) >= 0) {
            echo "<tr>";
            echo '<td>' . metadata('ETIQ', 'PHOTOGRA') . ' : </td>';
            echo '<td><input type="text" size="40" name="photo" value="' . $photo . '">';
            // echo ' ou <input type="checkbox" name="photocsv" value="1" '.checked($photocsv).'/> Lu dans le CSV ';
            echo "</td>";
            echo "</tr>";
            echo "<tr>";
        }
        if (isin('OFA', metadata('AFFICH', 'RELEVEUR')) >= 0) {
            echo "<tr>";
            echo '<td>' . metadata('ETIQ', 'RELEVEUR') . ' : </td>';
            echo '<td><input type="text" size="40" name="trans" value="' . $trans . '">';
            // echo ' ou <input type="checkbox" name="transcsv" value="1" '.checked($transcsv).'/> Lu dans le CSV ';
            echo "</td>";
            echo "</tr>";
            echo "<tr>";
        }
        if (isin('OFA', metadata('AFFICH', 'VERIFIEU')) >= 0) {
            echo "<tr>";
            echo '<td>' . metadata('ETIQ', 'VERIFIEU') . ' : </td>';
            echo '<td><input type="text" size="40" name="verif" value="' . $verif . '">';
            // echo ' ou <input type="checkbox" name="verifcsv" value="1" '.checked($verifcsv).'/> Lu dans le CSV ';
            echo "</td>";
            echo "</tr>";
            echo "<tr>";
        }
        echo "<tr><td colspan=\"2\">&nbsp;</td></tr>";
        echo "<tr>";
        echo '<td>Dédoublonnage : </td>';
        echo '<td>';
        echo '<input type="radio" name="Dedoublon" value="N"' . ($Dedoublon === 'N' ? ' checked' : '') . '>Sur la combinaison date+nom+prenom<br>';
        echo '<input type="radio" name="Dedoublon" value="I"' . ($Dedoublon === 'I' ? ' checked' : '') . '>Sur le n° ID de NIMEGUE (Ignorer si existe déjà)<br>';
        echo '<input type="radio" name="Dedoublon" value="M"' . ($Dedoublon === 'M' ? ' checked' : '') . '>Sur le n° ID de NIMEGUE (Mettre à jour si existe déjà)<br>';
        echo '<input type="radio" name="Dedoublon" value="A"' . ($Dedoublon === 'A' ? ' checked' : '') . '>Aucune vérification<br>';
        echo '</td>';
        echo "</tr>";
        echo "<tr>";
        echo '<td>Filtrage des données : </td>';
        echo '<td>';
        echo '<input type="checkbox" name="Filiation" value="1"' . ($Filiation == 1 ? ' checked' : '') . '>Eliminer les actes sans filiation <br>';
        echo '<input type="checkbox" name="AnneeVide" value="1"' . ($AnneeVide == 1 ? ' checked' : '') . '>Eliminer les actes dont l\'année est incomplète (ex. 17??)<br>';
        echo '<input type="checkbox" name="AVerifier" value="1"' . ($AVerifier == 1 ? ' checked' : '') . '>Eliminer les actes "A VERIFIER" ("VERIF" dans zone Cote) <br>';
        echo '</td>';
        echo "</tr>";
        echo "<tr>";
        echo '<td> <br>Contrôle des résultats : </td>';
        echo '<td>';

        echo '<br><input type="checkbox" name="LogOk" value="1"' . ($logOk == 1 ? ' checked' : '') . '>Actes chargés ';
        echo '<input type="checkbox" name="LogKo" value="1"' . ($logKo == 1 ? ' checked' : '') . '>Actes erronés ';
        echo '<input type="checkbox" name="LogRed" value="1"' . ($logRed == 1 ? ' checked' : '') . '>Actes redondants<br>';
        echo '</td>';
        echo "</tr>";
        if ($session->get('user')['level'] >= 8) {
            echo "<tr><td colspan=\"2\">&nbsp;</td></tr>";
            echo "<tr>";
            echo '<td>Déposant : </td>';
            echo '<td>';
            listbox_users("deposant", $session->get('user')['ID'], $config->get('DEPOSANT_LEVEL'));
            echo '</td>';
            echo "</tr>";
        } else {
            echo '<input type="hidden" name="deposant" value="' . $session->get('user')['ID'] . '">';
        }
        echo "<tr><td colspan=\"2\">&nbsp;</td></tr>";
    }
    echo "<tr><td></td>";
    // echo '<input type="hidden" name="uploadfile" value="NULL">';
    echo '<input type="hidden" name="action" value="submitted">';
    // echo '<input type="hidden" name="autokey" value="">';
    echo '<td><button type="reset" class="btn">Effacer</button>';
    echo '<button type="submit" class="btn" onclick="ShowSpinner();">Charger</button>';
    echo '<a href="' . $root . '/admin/aide/charge.html" class="btn" target="_blank">Aide</a>';
    echo "</td></tr>";

    echo '<tr><td colspan="2" align="center"><span id="spinner" style="visibility:hidden"><img src="' . $root . '/assets/img/spinner.gif"></span></td></tr>';
    echo "</table>";
    echo "</form>";
    echo "<div id='finalmsg'></div>";
} else {
    echo "<hr><p id='finalmsg'>";
    if ($moderestore) {
        $commune = substr(str_replace('../' . $config->get('DIR_BACKUP'), '', $uploadfile), 0, 30);
        $action = "restaurés";
        $txtTRT = "Restauration ";
    } else {
        if ($multiples_communes > 1) {
            $commune = 'Plusieurs';
        }
        $action = "ajoutés";
        $txtTRT = "NIMEGUE ";
    }
    if ($cptadd > 0) {
        $txtlog = $txtTRT . "Ajout";
        echo 'Actes ' . $action . '  : ' . $cptadd;
        writelog($txtlog . ' ' . $ntype, $commune, $cptadd);
    }
    if ($cptmaj > 0) {
        $action = "modifiés"; // Les 2 cas
        $txtlog = $txtTRT . "Mise à jour";
        echo '<br>Actes ' . $action . '  : ' . $cptmaj;
        writelog($txtlog . ' ' . $ntype, $commune, $cptmaj);
    }
    if ($cptign > 0) {
        echo '<br>Actes incomplets  : ' . $cptign;
    }
    if ($cptver > 0) {
        echo '<br>Actes à vérifier  : ' . $cptver;
    }
    if ($cptdeja > 0) {
        echo '<br />Actes redondants  : ' . $cptdeja;
    }
    echo '</p>';
    echo '<p>Durée du traitement  : ' . (time() - $T0) . ' sec.</p>';
    echo '<p>Durée du traitement  : ' . (microtime_float() - $MT0) . ' microsec.</p>';
}
echo '</div>';
echo '</div>';
include(__DIR__ . '/../templates/front/_footer.php');
$response->setContent(ob_get_clean());
$response->send();
