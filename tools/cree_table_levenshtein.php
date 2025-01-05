<?php


function table_temp($xacht, $xcomp, $table, $hf, $xcomm, $ip_adr_trait, $xmin, $xmax, $T0, $Max_time, $COLLATION = '') // remplissage table temporaire pour requete avec jointure
{
    global $config;

    if ($COLLATION == '') {
        $COLLATION = 'latin1_general_ci';
    } // latin1_swedish_ci latin1_general_ci
    $COLLATION = 'latin1_swedish_ci';

    if  (time() - $T0 >= $Max_time) {
        return 'timeout' . $hf;
    } else {

        if ($xcomp == "Z") {
            $dm = 1;
        }
        if ($xcomp == "U") {
            $dm = 2;
        }
        if ($xcomp == "D") {
            $dm = 3;
        }
        if ($xcomp == "T") {
            $dm = 4;
        }
        if ($xcomp == "Q") {
            $dm = 5;
        }
        if ($xcomp == "C") {
            $dm = 6;
        }
        $commune1 = "T";
        $crit = "";
        if ($xmin != "") {
            $crit = " (year(LADATE)>= " . $xmin . ")";
        }
        if ($xmax != "") {
            $critx = " (year(LADATE)<= " . $xmax . ")";
            $crit = sql_and($crit) . $critx;
        }


        if ($xcomm[0] != "*") {
            $commune1 = "U";
        }

        if ($hf == "H") {  // recherche homme

            $sql = "CREATE TEMPORARY TABLE IF NOT EXISTS " . $config->get('EA_DB') . "_" . $ip_adr_trait . "_h (`disth` int( 11 ) NOT NULL DEFAULT 0, PRIMARY KEY ( `nomlev` ) )
		AS (SELECT `NOM` AS `nomlev` FROM " . $table . " WHERE `ID` = '0');";

            $result = EA_sql_query($sql) or die('Erreur SQL creation !' . $sql . '<br>' . EA_sql_error());
            if ($table == $config->get('EA_DB') . "_div3") {
                if ($commune1 == "U") {
                    if ($crit != '') {
                        $sql = "SELECT nom FROM " . $table . " WHERE " . $crit . " AND commune='" . sql_quote($xcomm) . "' GROUP BY nom ORDER BY nom";
                    } else {
                        $sql = "SELECT nom FROM " . $table . " WHERE commune='" . sql_quote($xcomm) . "' GROUP BY nom ORDER BY nom";
                    }
                } else {
                    if ($crit != '') {
                        $sql = "SELECT nom FROM " . $table . " WHERE " . $crit . " GROUP BY nom ORDER BY nom";
                    } else {
                        $sql = "SELECT nom FROM " . $table . " GROUP BY nom ORDER BY nom";
                    }
                }

            } elseif ($table == $config->get('EA_DB') . "_mar3") {
                if ($commune1 == "U") {
                    if ($crit != '') {
                        $sql = "SELECT nom FROM " . $table . " WHERE " . $crit . " AND commune='" . sql_quote($xcomm) . "' GROUP BY nom ORDER BY nom";
                    } else {
                        $sql = "SELECT nom FROM " . $table . " WHERE   commune ='" . sql_quote($xcomm) . "' GROUP BY nom ORDER BY nom";
                    }
                } else {
                    if ($crit != '') {
                        $sql = "SELECT nom FROM " . $table . " WHERE " . $crit . " GROUP BY nom ORDER BY nom";
                    } else {
                        $sql = "SELECT nom FROM " . $table . " GROUP BY nom ORDER BY nom";
                    }
                }
            } else {
                if ($commune1 == "U") {
                    if ($crit != '') {
                        $sql = "SELECT p_nom FROM " . $table . " WHERE " . $crit . " AND  commune ='" . sql_quote($xcomm) . "' GROUP BY p_nom ORDER BY p_nom";
                    } else {
                        $sql = "SELECT p_nom FROM " . $table . " WHERE commune ='" . sql_quote($xcomm) . "' GROUP BY p_nom ORDER BY p_nom";
                    }
                } else {
                    if ($crit != '') {
                        $sql = "SELECT p_nom FROM " . $table . " WHERE " . $crit . " GROUP BY p_nom ORDER BY p_nom";
                    } else {
                        $sql = "SELECT p_nom FROM " . $table . " GROUP BY p_nom ORDER BY p_nom";
                    }
                }
            }
        }

        if ($hf == "F") {
            $sql = "CREATE TEMPORARY TABLE IF NOT EXISTS " . $config->get('EA_DB') . "_" . $ip_adr_trait . "_f  
			(`distf` int( 11 ) NOT NULL DEFAULT 0, PRIMARY KEY ( `nomlev` ) )
			AS  (SELECT   `NOM` AS `nomlev` FROM " . $table . " WHERE `ID` = '0');";

            $result = EA_sql_query($sql) or die('Erreur SQL creation !' . $sql . '<br>' . EA_sql_error());
            if ($table == $config->get('EA_DB') . "_div3") {   // ##########################NOUVEAU###############################"
                if ($commune1 == "U") {
                    if ($crit != '') {
                        $sql = "SELECT c_nom FROM " . $table . " WHERE " . $crit . " AND  commune='" . sql_quote($xcomm) . "' GROUP BY c_nom ORDER BY c_nom";
                    } else {
                        $sql = "SELECT c_nom FROM " . $table . " WHERE  commune='" . sql_quote($xcomm) . "' GROUP BY c_nom ORDER BY c_nom";
                    }
                } else {
                    if ($crit != '') {
                        $sql = "SELECT c_nom FROM " . $table . " WHERE " . $crit . " GROUP BY c_nom ORDER BY c_nom";
                    } else {
                        $sql = "SELECT c_nom FROM " . $table . " GROUP BY c_nom ORDER BY c_nom";
                    }
                }
            } elseif ($table == $config->get('EA_DB') . "_mar3") {
                if ($commune1 == "U") {
                    if ($crit != '') {
                        $sql = "SELECT c_nom FROM " . $table . " WHERE " . $crit . " AND  commune='" . sql_quote($xcomm) . "' GROUP BY c_nom ORDER BY c_nom";
                    } else {
                        $sql = "SELECT c_nom FROM " . $table . " WHERE  commune ='" . sql_quote($xcomm) . "' GROUP BY c_nom ORDER BY c_nom";
                    }
                } else {
                    if ($crit != '') {
                        $sql = "SELECT c_nom FROM " . $table . " WHERE " . $crit . " GROUP BY c_nom ORDER BY c_nom";
                    } else {
                        $sql = "SELECT c_nom FROM " . $table . " GROUP BY c_nom ORDER BY c_nom";
                    }
                }
            } else {
                if ($commune1 == "U") {
                    if ($crit != '') {
                        $sql = "SELECT m_nom FROM " . $table . " WHERE " . $crit . " AND  commune='" . sql_quote($xcomm) . "' GROUP BY m_nom ORDER BY m_nom";
                    } else {
                        $sql = "SELECT m_nom FROM " . $table . " WHERE commune='" . sql_quote($xcomm) . "' GROUP BY m_nom ORDER BY m_nom";
                    }
                } else {
                    if ($crit != '') {
                        $sql = "SELECT m_nom FROM " . $table . " WHERE " . $crit . " GROUP BY m_nom ORDER BY m_nom";
                    } else {
                        $sql = "SELECT m_nom FROM " . $table . " GROUP BY m_nom ORDER BY m_nom";
                    }
                }
            }
        }

        if ($hf == "D") {
            $sql = "CREATE TEMPORARY TABLE IF NOT EXISTS " . $config->get('EA_DB') . "_" . $ip_adr_trait . "_d  
			(`distd` int( 11 ) NOT NULL DEFAULT 0, PRIMARY KEY ( `nomlev` ) )
			AS (SELECT `NOM` AS `nomlev` FROM " . $table . " WHERE `ID`='0');";

            $result = EA_sql_query($sql) or die('Erreur SQL creation !' . $sql . '<br>' . EA_sql_error());
            if ($commune1 == "U") {
                if ($crit != '') {
                    $sql = "SELECT nom FROM " . $table . " WHERE " . $crit . " AND commune='" . sql_quote($xcomm) . "' GROUP BY nom ORDER BY nom";
                } else {
                    $sql = "SELECT nom FROM " . $table . " WHERE commune='" . sql_quote($xcomm) . "' GROUP BY nom ORDER BY nom";
                }
            } else {
                if ($crit != '') {
                    $sql = "SELECT nom FROM " . $table . " WHERE " . $crit . " GROUP BY nom ORDER BY nom";
                } else {
                    $sql = "SELECT nom FROM " . $table . " GROUP BY nom ORDER BY nom";
                }
            }
        }

        if ($hf == "N") {
            $sql = "CREATE TEMPORARY TABLE IF NOT EXISTS " . $config->get('EA_DB') . "_" . $ip_adr_trait . "_n  
			(`distn` int( 11 ) NOT NULL DEFAULT 0, PRIMARY KEY ( `nomlev` ) )
			AS  (SELECT   `NOM` AS `nomlev` FROM " . $table . " WHERE `ID`='0');";

            $result = EA_sql_query($sql) or die('Erreur SQL creation !' . $sql . '<br>' . EA_sql_error());
            if ($commune1 == "U") {
                if ($crit != '') {
                    $sql = "SELECT nom FROM " . $table . " WHERE  " . $crit . " AND commune='" . sql_quote($xcomm) . "' GROUP BY nom ORDER BY nom";
                } else {
                    $sql = "SELECT nom FROM " . $table . " WHERE  commune ='" . sql_quote($xcomm) . "' GROUP BY nom ORDER BY nom";
                }
            } else {
                if ($crit != '') {
                    $sql = "SELECT nom FROM " . $table . " WHERE " . $crit . " GROUP BY nom ORDER BY nom";
                } else {
                    $sql = "SELECT nom FROM " . $table . " GROUP BY nom ORDER BY nom";
                }
            }
        }

        $result = EA_sql_query($sql) or die('Erreur SQL !' . $sql . '<br>' . EA_sql_error());
        $nbtot = EA_sql_num_rows($result);
        $nb = $nbtot;
        if ($nb > 0) {
            while ($ligne = EA_sql_fetch_row($result)) {
                $k = levenshtein(strtoupper($xacht), strtoupper($ligne[0]));
                if ($k < $dm) {
                    if ($hf == "H") {
                        $sql1 = "INSERT IGNORE INTO " . $config->get('EA_DB') . "_" . $ip_adr_trait . "_h (nomlev,disth) VALUES ('" . sql_quote($ligne[0]) . "'," . $k . " )";
                    }
                    if ($hf == "F") {
                        $sql1 = "INSERT IGNORE INTO " . $config->get('EA_DB') . "_" . $ip_adr_trait . "_f (nomlev,distf) VALUES ('" . sql_quote($ligne[0]) . "'," . $k . " )";
                    }
                    if ($hf == "D") {
                        $sql1 = "INSERT IGNORE INTO " . $config->get('EA_DB') . "_" . $ip_adr_trait . "_d (nomlev,distd) VALUES ('" . sql_quote($ligne[0]) . "'," . $k . " )";
                    }
                    if ($hf == "N") {
                        $sql1 = "INSERT IGNORE INTO " . $config->get('EA_DB') . "_" . $ip_adr_trait . "_n (nomlev,distn) VALUES ('" . sql_quote($ligne[0]) . "'," . $k . " )";
                    }
                    $result1 = EA_sql_query($sql1) or die('Erreur SQL insertion !' . $sql1 . '<br>' . EA_sql_error());
                }
            }
        }
        return 'ok';
    }
}
