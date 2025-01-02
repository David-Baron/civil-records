<?php 
/**
 * requirements:
 * - $sitename
 * - $urlsite
 * $user
 */
?>
Bonjour <?= $user['prenom']; ?>,<br><br>
Votre demande de compte utilisateur du site <a href="<?= $urlsite; ?>"><?= $sitename; ?></a> n'a pu être approuvée.<br><br>
Cordialement,<br>
Votre webmestre.