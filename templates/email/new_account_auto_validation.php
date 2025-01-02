<?php 
/**
 * requirements: 
 * - $sitename
 * - $urlsite
 * - $user
 * - $urlvalid
 * - $keyvalid
 */
?>
Bonjour <?= $user['prenom']; ?>,<br><br>
Merci de vous être enregistré(e) sur le site <a href="<?= $urlsite; ?>"><?= $sitename; ?></a>.<br>
Pour valider votre adresse email et rendre votre compte opérationnel, vous devez ACTIVER le compte avec le lien suivant : <br>
<?= $urlvalid; ?><br>
Au besoin votre code d'activation est : <?= $keyvalid; ?><br><br>
Cordialement,<br>
Votre webmestre.