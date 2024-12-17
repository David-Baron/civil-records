<?php
define('ADM', 10); // Compatibility only
$admtxt = 'Gestion '; // Compatibility only
require(__DIR__ . '/../next/bootstrap.php');
require(__DIR__ . '/../next/_COMMUN_env.inc.php'); // Compatibility only

$userlogin = "";
$userlevel = logonok(7);
while ($userlevel < 7) {
    login($root);
}

pathroot($root, $path, $xcomm, $xpatr, $page);

$title = "Ajout d'un acte";
$ok = false;
$missingargs = false;
$oktype = false;
$today = today();

$menu_data_active = 'A';

ob_start();
open_page($title, $root);

include(__DIR__ . '/../tools/PHPLiveX/PHPLiveX.php');
$ajax = new PHPLiveX(array("getCommunes"));
$ajax->Run(false, "../tools/PHPLiveX/phplivex.js");

navadmin($root, $title);
zone_menu(ADM, $userlevel, array()); //ADMIN STANDARD
echo '<div id="col_main">';
require(__DIR__ . '/../templates/admin/_menu_data.php');

echo '<form method="post" action="' . $root . '/admin/edit_acte.php">';
echo '<h2 align="center">' . $title . '</h2>';
echo '<table  align="center" cellspacing="0" cellpadding="1" border="0" summary="Formulaire">';

//echo " <tr><td colspan=\"2\"><h3>Acte à ajouter : </h3></td></tr>\n";
form_typeactes_communes('', 0); ?>
<tr>
<tr>
    <td colspan="2">&nbsp;</td>
</tr>
<tr>
    <td></td>
    <td>
        <button type="reset">Annuler</button>
        <button type="submit">Ajouter</button>
    </td>
</tr>
</table>
<input type="hidden" name="action" value="submitted">
<input type="hidden" name="xid" value="-1">
</form>

</div>
<?php include(__DIR__ . '/../templates/front/_footer.php');
$response->setContent(ob_get_clean());
$response->send();
