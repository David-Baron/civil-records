<?php 
/**
 * requirements:
 * - $urlsite
 * - $sitename
 * - $user
 */
?>
Bonjour <?= $user['prenom']; ?>,<br><br>
Votre demande de compte utilisateur sur le site <a href="<?= $urlsite; ?>"><?= $sitename; ?></a>  a été approuvée. <br>
Votre compte <?= $user['login']; ?> est à présent opérationnel.<br><br>
Cordialement,<br>
Votre webmestre.