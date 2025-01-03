<?php

use Symfony\Component\HttpFoundation\RedirectResponse;

if (!$userAuthorizer->isGranted(6)) {
    $response = new RedirectResponse("$root/");
    $response->send();
    exit();
}

ob_start();
open_page("A propos", $root); ?>
<div class="main">
    <?php zone_menu(10, $session->get('user')['level']); ?>
    <div class="main-col-center">
        <?php navadmin($root, 'about'); ?>
        <div class="p-2">
            <h1 class="p-2">A propos de Civil-Records</h1>
            <p>
                Civil-Records à était créé pour plusieurs raisons dont les principales sont la diffusion du code, <br> le maintien du versionning,
                l'accès au code source et aux propositions de n'importe quel développeur ou contributeur. <br><br>

                Expoactes est un très bel outil qui a néanmoins ses défauts que Civil-Records va tenter de corriger.<br><br>
            </p>
        </div>
        <div class="p-2">
            Environement:
            <ul>
                <li>- les dependences requises seront automatiquement controlées à l'instalation ou la mise à jour de l'application.</li>
                <li>- les clés ou donnés sensibles de Civil-Records et des applications externes ne sont/seront plus dans la database.</li>
            </ul>
        </div>
        <div class="p-2">
            Utilisateur:
            <ul>
                <li>- le syteme de double databases est révoqué et sera remplacé par un systeme de choix local ou api.</li>
                <li>- la modification d'un compte par un autre utilisateur que la personne concernée. (exception faite pour le niveau d'accès).</li>
                <li>- l'encodage des mots de passe (sh1 actuellement) sera modifié par un encodage automatique par le serveur.</li>
            </ul>
        </div>
        <div class="p-2">
            Acte:
            <ul>
                <li>- l'utilisation de codes Insee est déprécié, ceci n'étant qu'un système de statistiques propre à la France.</li>
                <li>- un index d'un documents devra correspondre a l'index de la source.</li>
            </ul>
        </div>
        <div class="p-2">
            Carte:<br>
            <ul>
                <li>- le système de cartes sera modifié avec un choix entre plusieurs services (googlemap, openstreetmap, ...).</li>
            </ul>
        </div>
        <div class="p-2">
            Localité:<br>
            <ul>
                <li>- une table localité pour les lieux définis dans les actes sera créé.</li>
            </ul>
        </div>
        <div class="p-2">
            Source:<br>
            <ul>
                <li>- une table source sera créé pour palier au manque de clarté du système de cotation actuel. Cette table<br>
                    correspondra aux divers centres d'archives et ne sera pas modifiable depuis l'application.</li>
            </ul>
        </div>
        <div class="p-2">
            Un <a href="https://github.com/David-Baron/civil-records/discussions" target="_blank">centre de discution</a> vous permet de proposer, demander, aider et contribuer au projet Civil-Records. 
        </div>
    </div>
</div>
<?php include(__DIR__ . '/../templates/front/_footer.php');
return (ob_get_clean());
