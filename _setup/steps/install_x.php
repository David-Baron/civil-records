<?php
/*
 * Civil-Recods | installer | step 1
 * 
 * Select Language, Timezone
 */
$languages = [
    [
        'id' => 1,
        'code' => 'en',
        'locale' => 'en_GB',
        'name' => 'English',
        'by_default' => false
    ],
    [
        'id' => 2,
        'code' => 'fr',
        'locale' => 'fr_FR',
        'name' => 'Français',
        'by_default' => true
    ],
];

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
        <h1>Civil-Records | New Install</h1>
        <hr>
        <div class="row">
            <form method="post">
                <div class="row mb-4">
                    <label for="language" class="col-2">Language</label>
                    <div class="col-4">
                        <select class="form-select" name="language" id="language">
                            <?php foreach ($languages as $language) { ?>
                                <option value="<?= $language['id']; ?>" <?= $language['by_default'] ? 'selected' : ''; ?>><?= $language['name']; ?></option>
                            <?php } ?>
                        </select>
                    </div>
                </div>
                <div class="row mb-4">
                    <div class="col-2"></div>
                    <div class="col-4">
                        <button type="submit" class="btn btn-sm btn-primary">Submit</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</body>

</html>