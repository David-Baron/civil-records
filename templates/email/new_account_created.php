<?php 
/**
 * requirements:
 * - $sitename
 * - $urlsite
 * - $user
 * - $plain_text_password
 */
?>
Bonjour <?= $user['prenom']; ?>,<br><br>
Un compte vient d'être créé pour vous permettre de vous connecter au site <a href="<?= $urlsite; ?>"><?= $sitename; ?></a> :<br><br>

Votre login : <?= $user['login']; ?><br>
Votre mot de passe : <?= $plain_text_password; ?><br><br>
Cordialement,<br>
Votre webmestre.