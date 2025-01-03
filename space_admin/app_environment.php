<?php

use CivilRecords\Engine\EnvironmentFileParser;
use Symfony\Component\HttpFoundation\RedirectResponse;

if (!$userAuthorizer->isGranted(9)) {
    $response = new RedirectResponse("$root/admin/");
    $response->send();
    exit();
}

$environmentFileParser = new EnvironmentFileParser();

$mailer_factory_dsn = (!isset($_ENV['MAILER_FACTORY_DSN']) || empty($_ENV['MAILER_FACTORY_DSN']) ? '' : $_ENV['MAILER_FACTORY_DSN']);
$email_site = (!isset($_ENV['EMAIL_SITE']) || empty($_ENV['EMAIL_SITE']) ? '' : $_ENV['EMAIL_SITE']);
$email_admin = (!isset($_ENV['EMAIL_ADMIN']) || empty($_ENV['EMAIL_ADMIN']) ? '' : $_ENV['EMAIL_ADMIN']);
$email_corrector = (!isset($_ENV['EMAIL_CORRECTOR']) || empty($_ENV['EMAIL_CORRECTOR']) ? '' : $_ENV['EMAIL_CORRECTOR']);

$form_errors = [];
$menu_software_active = 'V';

if ($request->getMethod() === 'POST') {
    $params = $request->request->all();
    if (empty($form_errors)) {
        foreach ($params as $key => $value) {
            $environmentFileParser->set($key, $value);
        }
        $session->getFlashBag()->add('success', 'Variables d\'environement enregistrées.');
        $response = new RedirectResponse("$root/admin/application/environement");
        $response->send();
        exit();
    }
}

ob_start();
open_page("Paramètres environement", $root); ?>
<div class="main">
    <?php zone_menu(10, $session->get('user')['level']); ?>
    <div class="main-col-center text-center">
        <?php navadmin($root, "Paramètres environement"); ?>

        <?php require(__DIR__ . '/../templates/admin/_menu-software.php'); ?>

        <h2>Paramètres sensibles de votre environement</h2>
        <form method="post">
            <table class="m-auto">
                <tr>
                    <th colspan="2">Mail</th>
                </tr>
                <tr>
                    <td>
                        <label for="mailer_factory_dsn">Service d'envoi email (?)</label>
                    </td>
                    <td>
                        <input type="text" name="mailer_factory_dsn" id="mailer_factory_dsn" value="<?= $mailer_factory_dsn; ?>" required>
                    </td>
                </tr>
                <tr>
                    <td>
                        <label for="email_site">Adresse mail du site (?)</label>
                    </td>
                    <td>
                        <input type="text" name="email_site" id="email_site" value="<?= $email_site; ?>" required>
                    </td>
                </tr>
                <tr>
                    <td>
                        <label for="email_admin">Adresse mail de l'administrateur (?)</label>
                    </td>
                    <td>
                        <input type="text" name="email_admin" id="email_admin" value="<?= $email_admin; ?>" required>
                    </td>
                </tr>
                <tr>
                    <td>
                        <label for="email_corrector">Adresse mail vérificateur (?)</label>
                    </td>
                    <td>
                        <input type="text" name="email_corrector" id="email_corrector" value="<?= $email_corrector; ?>" required>
                    </td>
                </tr>
                <tr>
                    <td></td>
                    <td>
                        <button type="submit" class="btn">Enregistrer</button>
                        <?php if (!empty($mailer_factory_dsn)) { ?>
                            <a href="<?= $root; ?>/admin/mail/test" class="btn">Tester</a>
                        <?php } ?>
                    </td>
                </tr>
            </table>
        </form>
    </div>
</div>
<?php include(__DIR__ . '/../templates/front/_footer.php');
return (ob_get_clean());
