<?php

// Fonction d'envoi de mél avec PHPMailer (à installer dans 'tools/PHPMailer/') : https://github.com/PHPMailer/PHPMailer
// En entrée :
//     SMTP_HOST : Serveur d'envoi avec port sous la forme  SERVEUR:PORT
//     SMTP_PASS : mot de passe du compte (si non indiqué, pas d'envoi authentifié)
//     SMTP_ACC  : Adresse mel du compte des envois authentifiés
//     LOC_HOST  : Nom du serveur d'origine des mels
//     LOC_MAIL  : Adresse mail de l'administrateur. Adresse d'émetteur par défaut
//     $from     : mel emetteur
//     $to       : mel destinataire
//     $sujet    : sujet du mel
//     $message  : texte du mel
//
// En sortie : $retour_mail_externe "true" si réussi, "false" sinon
// VOIR : /admin/aide/config.html#Mail

$retour_mail_externe = false;

$rep_PHPMailer = dirname(__FILE__) . '/PHPMailer/';
if (!file_exists($rep_PHPMailer)) {
    msg("052 : Dossier PHPMailer absent.");
    writelog('Dossier PHPMailer absent.', 'PHPMailer', 0);
    return $retour_mail_externe; // Le return est nécessaire pour stopper le script mais le contenu n'est pas utilisé
}
if (($config->get('SMTP_HOST') . $config->get('LOC_HOST')) == "") {
    msg("052 : Paramètres de gestion du mail incomplètement configurés.");
    writelog('Paramètres manquants.', 'PHPMailer', 0);
    return $retour_mail_externe; // Le return est nécessaire pour stopper le script mais le contenu n'est pas utilisé
}
// Content-Type: text/plain; charset=iso-8859-1
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require_once($rep_PHPMailer . "src/Exception.php");
require_once($rep_PHPMailer . "src/PHPMailer.php");
require_once($rep_PHPMailer . "src/SMTP.php");
//date_default_timezone_set("Europe/Paris");
$mail = new PHPMailer();

// Extrait le port du nom du serveur, par défaut port 465
$temp = explode(':', $config->get('SMTP_HOST'));
if (!isset($temp[1])) {
    $temp[1] = 465;
}

$mail->Host = $temp[0];
$mail->Port = $temp[1];
$mail->Hostname = $config->get('LOC_HOST');

if ($config->get('SMTP_PASS') == "") {
    $mail->SMTPAuth = false;
} else {
    $mail->SMTPAuth = true;
    $mail->Username = $config->get('SMTP_ACC'); //Username to use for SMTP authentication - use full email address for gmail
    $mail->Password = $config->get('SMTP_PASS'); //Password to use for SMTP authentication
}

$mail->isSMTP();
$mail->isHTML(false);
$mail->CharSet = 'UTF-8';
$mail->Subject = $sujet;
//$mail->AltBody = $message;
$mail->Body = $message;
// $mail->SMTPDebug = SMTP::DEBUG_SERVER;
$les_adresses = $mail->parseAddresses($from, false);
$mail->SetFrom($les_adresses[0]['address'], $les_adresses[0]['name']);
// $mail->setFrom(LOC_MAIL, SITENAME);
$to_array = explode(',', $to);
foreach ($to_array as $address) {
    $mail->AddAddress($address, 'ExpoActes');
}
$mail->addReplyTo($from);

try {
    $mail->send();
    $retour_mail_externe = true;
    return $retour_mail_externe; // Le return est nécessaire pour stopper le script mais le contenu n'est pas utilisé
} catch (\Exception $e) {
    msg('Problème lors du dialogue avec le serveur mail: " : ' . $e->getMessage());
    writelog($e->getMessage(), $mail->ErrorInfo, 0);
    return $retour_mail_externe; // Le return est nécessaire pour stopper le script mais le contenu n'est pas utilisé
}
