<?php

use Symfony\Component\HttpFoundation\RedirectResponse;

require __DIR__ . '/../../next/Engine/EnvironmentFileParser.php';

$requirements = json_decode(file_get_contents(__DIR__ . '/../requirements.json'), true);
$storage = '_storage';
$db_host = 'localhost:3306';
$db_name = 'civil-records';
$db_user = 'root';
$db_pass = 'secret';

$requirements_ok = true;
$form_errors = [];
if ($request->getMethod() === 'POST') {
    $params = $request->request->all();
    try {
        $cnx = new \PDO("mysql:host=$params[db_host];dbname=$params[db_name]", $params['db_user'], $params['db_pass']);
    } catch (\PDOException $err) {
        // [2002] Hote inconnu
        // [1045] Utilisateur/Mot de passe inconnu
        // [1049] Database inconnue
        switch ($err->getCode()) {
            case 2002:
                $form_errors['database_connection'] = 'No connection could be established with the database server. ' . $err->getCode();
                $form_errors['db_host'] = true;
                break;
            case 1045:
                $form_errors['database_connection'] = 'No connection could be established with the database server. ' . $err->getCode();
                $form_errors['db_user'] = true;
                $form_errors['db_pass'] = true;
                break;
            case 1049:
                $form_errors['database_connection'] = 'No connection could be established with the database server. ' . $err->getCode();
                $form_errors['db_name'] = true;
                break;
            default:
                $form_errors['database_connection'] = 'No connection could be established with the database server. ' . $err->getCode();
                break;
        }
    }

    if (empty($form_errors)) {
        $environmentFileParser = new EnvironmentFileParser();
        $environmentFileParser->set('app_env', 'prod');
        $fileEnvironmentParser->set('app_root', $root);
        foreach ($params as $key => $value) {
            $environmentFileParser->set($key, $value);
        }
        $session->set('step', '2');
        $response = new RedirectResponse("$root/");
        $response->send();
        exit();
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Civil-Records | Installer</title>
    <link rel="stylesheet" href="/setup/themes/css/cosmo.css">
    <script src="/setup/themes/js/bootstrap.bundle.min.js"></script>
</head>

<body>
    <div class="container">
        <h1>Civil-Records | New Install | Step 1</h1>
        <hr>
        <div class="row">
            <div class="col-2">
                <strong>Php version :</strong>
            </div>
            <div class="col-2 <?= version_compare(phpversion(), $requirements['php']['min_version'], '<') ? 'text-danger' : 'text-success'; ?>"><?= phpversion(); ?></div>
            <?php if (version_compare(phpversion(), $requirements['php']['min_version'], '<')) {
                $requirements_ok = false; ?>
                <div class="col text-danger">
                    Cette version de Civil-Records nécessite au moins la version <?= $requirements['php']['min_version']; ?> de PHP!
                </div>
            <?php } else { ?>
                <div class="col"></div>
            <?php } ?>
        </div>
        <?php foreach ($requirements['php']['extensions'] as $key => $extension) { ?>
            <div class="row">
                <div class="col-2">
                    <strong>Php extention <?= $extension; ?> :</strong>
                </div>
                <div class="col-2 <?= !in_array($extension, get_loaded_extensions()) ? 'text-danger' : 'text-success'; ?>"><?= $extension; ?></div>
                <?php if (!in_array($extension, get_loaded_extensions())) {
                    $requirements_ok = false; ?>
                    <div class="col text-danger">
                        Cette version de Civil-Records nécessite l'extention <?= $exttension; ?> de PHP!
                    </div>
                <?php } else { ?>
                    <div class="col"></div>
                <?php } ?>
            </div>
        <?php } ?>
        <hr>
        <?php if ($requirements_ok) { ?>
            <h2>Database Connection</h2>
            <?php if (isset($form_errors['database_connection'])) { ?>
                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                    <?= $form_errors['database_connection']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php } ?>
            <form method="post" name="install" autocomplete="off">
                <div class="row mb-4">
                    <label for="db_host" class="col-2">Serveur + Port</label>
                    <div class="col-4">
                        <input 
                            type="text" 
                            class="form-control <?= isset($form_errors['db_host']) ? ' is-invalid' : ''; ?>" 
                            name="db_host" 
                            id="db_host" 
                            value="<?= $db_host; ?>" 
                            placeholder="localhost:3306"
                        >
                        <?php if (isset($form_errors['db_host'])) { ?>
                            <div class="invalid-feedback">Serveur inconnu.</div>
                        <?php } ?>
                    </div>
                </div>
                <div class="row mb-4">
                    <label for="db_user" class="col-2">Utilisateur</label>
                    <div class="col-4">
                        <input 
                            type="text" 
                            class="form-control <?= isset($form_errors['db_user']) ? ' is-invalid' : ''; ?>" 
                            name="db_user" 
                            id="db_user"
                            value="<?= $db_user; ?>"
                            placeholder="root"
                        >
                        <?php if (isset($form_errors['db_user'])) { ?>
                            <div class="invalid-feedback">
                                Utilisateur/Mot de passe inconnu.
                            </div>
                        <?php } ?>
                    </div>
                </div>
                <div class="row mb-4">
                    <label for="db_pass" class="col-2">Mot de passe</label>
                    <div class="col-4">
                        <input 
                            type="text" 
                            class="form-control <?= isset($form_errors['db_pass']) ? ' is-invalid' : ''; ?>" 
                            name="db_pass" 
                            id="db_pass" 
                            value="<?= $db_pass; ?>"
                            placeholder="secret"
                        >
                        <?php if (isset($form_errors['db_pass'])) { ?>
                            <div class="invalid-feedback">
                                Utilisateur/Mot de passe inconnu.
                            </div>
                        <?php } ?>
                    </div>
                </div>
                <div class="row mb-4">
                    <label for="db_name" class="col-2">Nom de la database</label>
                    <div class="col-4">
                        <input 
                            type="text" 
                            class="form-control <?= isset($form_errors['db_name']) ? ' is-invalid' : ''; ?>" 
                            name="db_name" 
                            id="db_name" 
                            value="<?= $db_name; ?>" 
                            placeholder="civil-records"
                        >
                        <?php if (isset($form_errors['db_name'])) { ?>
                            <div class="invalid-feedback">
                                Database inconnue.
                            </div>
                        <?php } ?>
                    </div>
                </div>
                <div class="row mb-4">
                    <div class="col-2"></div>
                    <div class="col-4">
                        <button type="submit" class="btn btn-sm btn-primary">Valider</button>
                    </div>
                </div>
            </form>
        <?php } ?>
    </div>
</body>

</html>