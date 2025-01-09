<?php

use Symfony\Component\HttpFoundation\RedirectResponse;

if (!$userAuthorizer->isGranted(9)) {
    $response = new RedirectResponse("$root/admin/");
    $response->send();
    exit();
}

global $dbaddr, $dbuser, $dbpass, $dbname;

$mysql_path = $config->get('MYSQL_PATH', '');
if (defined("MYSQL_PATH")) {

    if (mb_substr($mysql_path, -1, 1) != "\\") {
        $mysql_path .= "\\";
    }
}

my_ob_start_affichage_continu();
open_page("Backup de votre base de données", $root); ?>
<div class="main">
    <?php zone_menu(10, 0); ?>
    <div class="main-col-center text-center">
        <?php
        navadmin($root, "Backup de la base");

        echo "<h1>Backup de votre base de données</h1>";

        if (ini_get('safe_mode') or (strpos(ini_get('disable_functions'), "system") !== false)) {
            echo "<p>Désolé : la configuration SAFE_MODE ou DISABLE_FUNCTIONS de l'hébergement ne permet pas d'exécuter un backup via PHP !";
        } else {
            echo "<p>Commencement de la sauvegarde...\n<br>";
            my_flush(); // On affiche un minimum

            $file = $dbname . "_" . date("Y-m-d", time()) . ".sql";
            $command = "mysqldump";
            $options = "--opt --host=" . $dbaddr . " --user=" . $dbuser . " --password=" . $dbpass . " " . $dbname . " > ..\\_backup\\" . $file;
            $opt323 = "--compatible=mysql323 ";
            my_flush();

            $full_command = '"' . $mysql_path . $command . '" ' . $opt323 . $options;
            system($full_command, $ret_value);
            if ($ret_value != 0) {
                echo "<p>Nouvel essai sans option mysql323...\n<br>";
                $full_command = '"' . $mysql_path . $command . '" ' . $options;
                system($full_command, $ret_value);
            }

            if ($ret_value == 0) {
                echo "<p>Sauvegarde réussie.<br>Vous pouvez récupérer le fichier <b>_backup\\" . $file . "</b> par FTP";
            } else {
                //echo "<p>Commande exécutée : <br>".$full_command;
                echo "<p>Désolé : impossible d'exécuter le backup ou erreur au cours de l'exécution !";
                echo '<p>Consulter l\'<a href="'.$root.'/admin/aide/backup.html">aide</a> pour résoudre le problème.';
            }
        } ?>
    </div>
</div>
<?php include(__DIR__ . '/../templates/front/_footer.php');
return (ob_get_clean());
