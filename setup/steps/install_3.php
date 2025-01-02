<?php 

use CivilRecords\Model\UserModel;
use Symfony\Component\HttpFoundation\RedirectResponse;

$_ENV = require(__DIR__ . '/../../.env.local.php');

$form_errors = [];

if ($request->getMethod() === 'POST') {
    $user = $request->request->all();

    if (empty($user['email']) || !filter_var($user['email'], FILTER_VALIDATE_EMAIL)) {
        $form_errors['email'] = true;
    }

    if (empty($user['password'])) {
        $form_errors['password'] = true;
    }

    if ($user['password'] !== $user['password_repeat']) {
        $form_errors['password_repeat'] = true;
    }

    if (empty($form_errors)) {
        $user['hashpass'] = sha1($user['password']);
        $user['level'] = 9;
        $user['regime'] = 0;
        $user['solde'] = 0;
        $user['maj_solde'] = '1001-01-01';
        $user['statut'] = 'N';
        $user['pt_conso'] = 0;
        $user['REM'] = '';
        $user['libre'] = '';
        $userModel = new UserModel();
        $userModel->insert($user);

        $session->set('step', 'last');
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
        <h1>Civil-Records | New Install | Step 3</h1>
        <hr>
        <div class="row">
            <form method="post">
                <div class="row mb-4">
                    <label for="nom" class="col-2">Votre nom</label>
                    <div class="col-4">
                        <input type="text" class="form-control" name="nom" id="nom" min="3" max="24" required>
                        <?php if (isset($form_errors['nom'])) { ?>
                            <div class="invalid-feedback">
                                Votre nom est requis.
                            </div>
                        <?php } ?>
                    </div>
                </div>
                <div class="row mb-4">
                    <label for="prenom" class="col-2">Votre prénom</label>
                    <div class="col-4">
                        <input type="text" class="form-control" name="prenom" id="prenom" min="3" max="24" required>
                        <?php if (isset($form_errors['prenom'])) { ?>
                            <div class="invalid-feedback">
                                Votre prénom est requis.
                            </div>
                        <?php } ?>
                    </div>
                </div>
                <div class="row mb-4">
                    <label for="login" class="col-2">Votre identifiant</label>
                    <div class="col-4">
                        <input type="text" class="form-control" name="login" id="login" min="5" max="15" required>
                        <?php if (isset($form_errors['login'])) { ?>
                            <div class="invalid-feedback">
                                Un identifiant est requis.
                            </div>
                        <?php } ?>
                    </div>
                </div>
                <div class="row mb-4">
                    <label for="email" class="col-2">Votre adresse mail</label>
                    <div class="col-4">
                        <input 
                            type="text" 
                            class="form-control <?= isset($form_errors['email']) ? ' is-invalid' : ''; ?>" 
                            name="email" 
                            id="email" 
                            min="5" 
                            max="15" 
                            required
                        >
                        <?php if (isset($form_errors['email'])) { ?>
                            <div class="invalid-feedback">
                                Votre adresse mail est requise.
                            </div>
                        <?php } ?>
                    </div>
                </div>
                <div class="row mb-4">
                    <label for="password" class="col-2">Votre mot de passe</label>
                    <div class="col-4">
                        <input 
                            type="password" 
                            class="form-control <?= isset($form_errors['password']) ? ' is-invalid' : ''; ?>" 
                            name="password" 
                            id="password" 
                            min="5" 
                            max="15" 
                            required
                        >
                        <?php if (isset($form_errors['password'])) { ?>
                            <div class="invalid-feedback">
                                Le mot de passe doit contenir que des lettres, chiffres et ne doit pas dépasser 15 caractères.
                            </div>
                        <?php } ?>
                    </div>
                </div>
                <div class="row mb-4">
                    <label for="password_repeat" class="col-2">Confirmation du mot de passe</label>
                    <div class="col-4">
                        <input 
                            type="password" 
                            class="form-control <?= isset($form_errors['password_repeat']) ? ' is-invalid' : ''; ?>" 
                            name="password_repeat" 
                            id="password_repeat" 
                            required
                        >
                        <?php if (isset($form_errors['password_repeat'])) { ?>
                            <div class="invalid-feedback">
                                Le mot de passe de confirmation n'est pas identique au mot de passe.
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
        </div>
    </div>
</body>

</html>