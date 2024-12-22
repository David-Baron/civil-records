<?php

//----------------------------------------------------------
function traite_tables_temps($ip_adr_trait, $heurecreation)
{
	// suppression des tables temporaires  : en cas de time out php les tables ne sont pas effacées. Ceci est normalement traité dans la fonction ci dessous
	// mais si la requete est relancé immédiatement elle ne seront pas effacées d'ou résultats abhérants

	$sql = "DROP TABLE " . $ip_adr_trait . "_h";
	$result = EA_sql_query($sql);
	$sql = "DROP TABLE " . $ip_adr_trait . "_f";
	$result = EA_sql_query($sql);
	$sql = "DROP TABLE " . $ip_adr_trait . "_d";
	$result = EA_sql_query($sql);
	$sql = "DROP TABLE " . $ip_adr_trait . "_n";
	$result = EA_sql_query($sql);


	// creation si elle n'existe pas de la table tmp_tables
	// chaque fois que l'on crée des tables temporaires on enregistre l' ip et l' heure correspondante à cette creéation
	// ensuite on regarde si il y a des enregistrement plus vieux que 1.5 mn.
	// si il y en a c'est que l'on a eu un time out php
	// dans ce cas on efface les tables tmp qui trainent
	// puis on efface dans tmp_tables l'enregistrement correspondant

	$sql = "CREATE TABLE IF NOT EXISTS tmp_tables (ip VARCHAR(20) NOT NULL,creation DATETIME NOT NULL)";
	$result = EA_sql_query($sql);

	$sql = "INSERT INTO tmp_tables (ip,creation) VALUES ('" . $ip_adr_trait . "','" . $heurecreation . "')";
	$result = EA_sql_query($sql);

	$sql = "SELECT * FROM tmp_tables WHERE creation < now() -  INTERVAL 90 SECOND";
	$result = EA_sql_query($sql);
	$nb = EA_sql_num_rows($result);

	if ($nb > 0) {
		while ($ligne = EA_sql_fetch_row($result)) {
			$sql1 = "DROP TABLE " . $ligne[0] . "_d";
			$result1 = EA_sql_query($sql1);
			$sql1 = "DROP TABLE " . $ligne[0] . "_h";
			$result1 = EA_sql_query($sql1);
			$sql1 = "DROP TABLE " . $ligne[0] . "_f";
			$result1 = EA_sql_query($sql1);
			$sql1 = "DROP TABLE " . $ligne[0] . "_n";
			$result1 = EA_sql_query($sql1);

			$sql1 = "DELETE FROM tmp_tables WHERE (ip = '" . $ligne[0] . "') AND (creation ='" . $ligne[1] . "')";
			$result1 = EA_sql_query($sql1);
		}
	}
}
