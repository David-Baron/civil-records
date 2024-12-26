<?php

require(__DIR__ . '/next/bootstrap.php');
require(__DIR__ . '/next/_COMMUN_env.inc.php'); // Compatibility only

if (!defined("ECLAIR_LOG")) define("ECLAIR_LOG", 0);

if ($config->get('ECLAIR_AUTORISE') == 0) {
    header("Location: $root/");
    exit();
}

$MT0 = microtime_float();
$cptrec = 0;
$cptper = 0;

$xcom = getparam('xcom');
$xdep = getparam('xdep');
$xtyp = getparam('xtyp');
$xini = getparam('xini');

$xcomm = $xpatr = $page = "";
pathroot($root, $path, $xcomm, $xpatr, $page);

header('Content-Type: text/html; charset=UTF-8');
ob_start();
echo '<!DOCTYPE html>';
echo '<html lang="fr">';
echo '<head>';
echo '<meta charset="UTF-8">';
echo '<meta name="robots" content="nofollow">';
echo '<meta name="generator" content="Civil-Records">';
echo '</head>';
echo '<body>';

if (($xcom == "") or ($xtyp == "")) {
    // Lise des communes
    $sql = "SELECT TYPACT AS TYP, sum(NB_TOT) AS CPT, COMMUNE, DEPART FROM " . $config->get('EA_DB') . "_sums GROUP BY COMMUNE, DEPART, TYP";
    $result = EA_sql_query($sql);
    $nblign = EA_sql_num_rows($result);

    echo "<p>LISTE-URL</p>";
    echo "<ul>";
    while ($ligne = EA_sql_fetch_row($result)) {
        echo '<li><a href="' . $root . '/eclair.php?xtyp=' . $ligne[0] . '&amp;xcom=' . urlencode($ligne[2]) . '&amp;xdep=' . urlencode($ligne[3]) . '">' . typact_txt($ligne[0]) . ' de ' . $ligne[2] . ' [' . $ligne[3] . ']</a> (' . $ligne[1] . ' actes)</li>';
    }
    echo '</ul>';
} else {
    // Traitement d'une commune praticulière
    switch ($xtyp) {
        case "N":
            $ntype = "de naissance";
            $table = $config->get('EA_DB') . "_nai3";
            $zones = "NOM,P_NOM,M_NOM,T1_NOM,T2_NOM";
            break;
        case "D":
            $ntype = "de décès";
            $table = $config->get('EA_DB') . "_dec3";
            $zones = "NOM,C_NOM,P_NOM,M_NOM,T1_NOM,T2_NOM";
            break;
        case "V":
            $ntype = "divers";
            $table = $config->get('EA_DB') . "_div3";
            $zones = "NOM,C_NOM,P_NOM,M_NOM,CP_NOM,CM_NOM,T1_NOM,T2_NOM,T3_NOM,T4_NOM";
            break;
        case "M":
            $ntype = "de mariage";
            $table = $config->get('EA_DB') . "_mar3";
            $zones = "NOM,C_NOM,P_NOM,M_NOM,CP_NOM,CM_NOM,T1_NOM,T2_NOM,T3_NOM,T4_NOM";
            break;
        default:
            $oktype = false;
    }

    // Extraction de la commune voulue
    $cond = "";
    if ($xini <> "") {
        $cond .= " AND NOM LIKE '" . $xini . "%'";
    }

    $sql = "SELECT year(LADATE), " . $zones . " 
        FROM " . $table . " 
        WHERE COMMUNE='" . sql_quote($xcom) . "'" . $cond;
    $result = EA_sql_query($sql);
    $cptrow = EA_sql_num_rows($result);

    if ($cptrow > $config->get('ECLAIR_MAX_ROW')) {
        // Trop de lignes dans la commune => traiter par initiale
        $lgi  = strlen($xini) + 1;
        $initiale = "";
        if ($lgi > 0) {
            $initiale = " AND left(NOM,$lgi-1)= '" . sql_quote($xini) . "'";
        }

        $sql = "SELECT left(NOM, $lgi), count(*) 
            FROM $table 
            WHERE COMMUNE='" . sql_quote($xcom) . "' 
            AND DEPART='" . sql_quote($xdep) . "'" . $initiale . " 
            GROUP BY left(NOM, $lgi)";
        $result = EA_sql_query($sql);
        $nblign = EA_sql_num_rows($result);

        if ($nblign == 1 and $lgi > 3) { // Permet d'éviter un bouclage si le nom devient trop petit
            $sql = "SELECT NOM, count(NOM), min(NOM), max(NOM) 
                FROM $table 
                WHERE COMMUNE = '" . sql_quote($xcom) . "' 
                AND DEPART = '" . sql_quote($xdep) . "'" . $initiale . " 
                GROUP BY NOM";
            $result = EA_sql_query($sql);
        }
        echo "<p>LISTE-URL</p>";
        echo "<ul>";
        while ($ligne = EA_sql_fetch_row($result)) {
            echo '<li>
            <a href="' . $root . '/eclair.php?xtyp=' . $xtyp . '&amp;xcom=' . urlencode($xcom) . '&amp;xdep=' . urlencode($xdep) . '&amp;xini=' . urlencode($ligne[0]) . '">
            ' . typact_txt($xtyp) . ' [' . $xdep . '] de ' . $xcom . ' initiale ' . $ligne[0] . '
            </a> (' . $ligne[1] . ' actes)
            </li>';
        }
        echo '</ul>';
    } else {
        // Liste éclair Creation table temporaire
        $sql = "CREATE TEMPORARY TABLE  tmp_eclair (ANNEE varchar(4), PATRO varchar(25)) DEFAULT CHARACTER SET latin1 COLLATE latin1_general_ci";
        $res = EA_sql_query($sql);
        if (!($res === true)) {
            echo '<font color="#FF0000"> Erreur </font>';
            echo '<p>' . EA_sql_error() . '<br>' . $sql . '</p>';
            die();
        }
        // Insertion des patronymes dans la table temporaire
        $k = 0;
        while ($row = EA_sql_fetch_row($result)) {
            if ($row[0] == 0) {
                $annee = "NULL";
            } else {
                $annee = "'" . $row[0] . "'";
            }
            $insert = "";
            for ($i = 1; $i < count($row); $i++) {
                if ($row[$i] <> "") {
                    if ($insert <> "") {
                        $insert .= ",";
                    }
                    $insert .= "(" . $annee . ",'" . sql_quote($row[$i]) . "')";
                    $k++;
                }
            }
            $reqmaj = "INSERT into tmp_eclair VALUES " . $insert;
            $res = EA_sql_query($reqmaj);
            //echo '<p>'.$reqmaj;
            if (!($res === true)) {
                echo '<font color="#FF0000"> Erreur </font>';
                echo '<p>' . EA_sql_error() . '<br>' . $reqmaj . '</p>';
                die();
            }
        }
        // Extraction du décompte des patronymes
        $sql = "SELECT count(*), min(ANNEE), max(ANNEE), PATRO FROM tmp_eclair GROUP BY PATRO";
        $result = EA_sql_query($sql);

        echo "<p>LISTE-ECLAIR</p>";
        echo "<p>Attention : cette liste comprend les patronymes des interessés et des témoins (père, mère, ancien conjoint, parrain,..).</p>";
        echo "<p>Commune : $xcom</p>";
        echo "<p>Région : $xdep</p>";
        echo "<p>Type : " . typact_txt($xtyp) . "</p>";
        echo "<p>";

        $cptrec = 0;
        $cptper = 0;
        while ($row = EA_sql_fetch_row($result)) {
            echo "<br>" . $row[3] . ";" . $row[1] . ";" . $row[2] . ";" . $row[0] . ";";
            $cptrec++;
            $cptper = $cptper + $row[0];
        }
        echo "</p>\n";
    }
}
echo '<p>Durée totale  : ' . round(microtime_float() - $MT0, 3) . ' sec.</p>';
echo '<p>Individus cités : ' . $cptper . '.</p>';
echo '<p>Patronymes  : ' . $cptrec . '.</p>';
?>
</body>

</html>
<?php if ($config->get('ECLAIR_LOG') > 0) {
    $array_server_values = $_SERVER;
    $Vua   = $array_server_values['HTTP_USER_AGENT'];
    $Vip   = $array_server_values['REMOTE_ADDR'];
    $hf = @fopen("_logs/eclair-" . date('Y-m') . ".log", "a");
    $dur = round(microtime_float() - $MT0, 3) * 1000;
    @fwrite($hf, now() . ";" . $Vip . ";" . $xtyp . ";" . $xcom . ";" . $xdep . ";" . $xini . ";" . $cptrec . ";" . $dur . ";" . $Vua . chr(10));
    @fclose($hf);
}

$response->setContent(ob_get_clean());
$response->send();
