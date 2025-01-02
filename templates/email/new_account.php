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
Merci de vous être enregistré(e) sur le site <a href="<?= $urlsite; ?>"><?= $sitename; ?></a>. <br><br>
Pour valider votre adresse email, vous devez ACTIVER le compte avec le lien suivant : <br>
<?= $urlvalid; ?> <br>
Au besoin votre code d'activation est : <?= $keyvalid; ?> <br><br>
Votre demande de compte sera alors examinée par le gestionnaire du site dans les meilleurs délais. <br><br>
Cordialement, <br>
Votre webmestre.