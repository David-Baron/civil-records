<?php

use Symfony\Component\HttpFoundation\RedirectResponse;

require __DIR__ . '/../../next/Engine/EnvironmentFileParser.php';
$_ENV = require(__DIR__ . '/../../.env.local.php');
$requirements = json_decode(file_get_contents(__DIR__ . '/../requirements.json'), true);

$has_expo_database = false;
$is_expo_compatible = false;

$cnx = new \PDO("mysql:host=$_ENV[DB_HOST];dbname=$_ENV[DB_NAME]", $_ENV['DB_USER'], $_ENV['DB_PASS']);
$sql = "SHOW TABLES LIKE 'act_params'";
$stmt = $cnx->query($sql, \PDO::FETCH_ASSOC);
$res = $stmt->fetch();

// Si une db est trouvée:
// - si c'est une version expoactes ET que cette version est compatible avec la version telechargée de Civil-Records
//      - Demander: New install OR Update 
// - si c'est une version expoactes ET qu'elle n'est pas compatible avec la version telechargée de Civil-Record
//      - Demander: Delete db OR Quit
if ($res) {
    $has_expo_database = true;
    $sql = "SELECT valeur FROM act_params WHERE param='EA_VERSION'";
    $stmt = $cnx->query($sql, \PDO::FETCH_ASSOC);
    $res = $stmt->fetch();
    if (version_compare($requirements['expoactes_compatibility'], $res['valeur'], '=')) {
        $is_expo_compatible = true;
    }
} else {
    $sql = file_get_contents(__DIR__ . '/../civilrecords.sql');
    $cnx->exec($sql);
    $session->set('step', '3');
    $response = new RedirectResponse("$root/");
    $response->send();
    exit();
}

$form_errors = [];

if ($request->getMethod() === 'POST') {
    $choice = $request->request->get('choice');
    if (empty($form_errors)) {
        switch ($choice) {
            case 'install':
                $sql = file_get_contents(__DIR__ . '/../civilrecords.sql');
                $cnx->exec($sql);
                $session->set('step', '3');
                $response = new RedirectResponse("$root/");
                $response->send();
                exit();
                break;
            case 'update':
                $environmentFileParser = new EnvironmentFileParser();
                $environmentFileParser->set('db_tables_prefix', 'act');
                $session->set('step', '3');
                $response = new RedirectResponse("$root/");
                $response->send();
                exit();
                break;
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Civil-Records | Installer</title>
    <link rel="stylesheet" href="/assets/css/cosmo.css">
    <script src="/assets/js/bootstrap.bundle.min.js"></script>
</head>

<body>
    <div class="container">
        <h1>Civil-Records | New Install | Step 2</h1>
        <hr>
        <div class="row mb-3">
            <div class="col-lg-12">
                <?php if ($has_expo_database && !$is_expo_compatible) { ?>
                    <p class="text-danger">
                        Une database expoactes est installée sur ce serveur et n'est pas compatible avec la version Civil-Records téléchargée! <br>
                    </p>
                <?php } ?>
                <?php if ($has_expo_database && $is_expo_compatible) { ?>
                    <p class="text-info">
                        Une database expoactes est installée sur ce serveur et est compatible avec la version Civil-Records téléchargée! <br>
                    </p>
                <?php } ?>
            </div>
        </div>
        <div class="row">
            <form method="post">
                <div class="row mb-4">
                    <label for="choice" class="col-2">Que souhaitez vous faire ?</label>
                    <div class="col-4">
                        <select class="form-select" name="choice" id="choice">
                            <option value="install">Installer la database Civil-Records (ne détruit pas celle d'expoactes)</option>
                            <?php if ($has_expo_database && $is_expo_compatible) { ?>
                                <option value="update">Utiliser la database actuelle</option>
                            <?php } ?>
                        </select>
                    </div>
                </div>
                <div class="row mb-4">
                    <div class="col-2"></div>
                    <div class="col-4">
                        <button type="submit" class="btn btn-sm btn-primary">Valider</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</body>

</html>