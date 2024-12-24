<?php
/** 
 * Local Javascript features that implement Civil-Records
 * 
 * Rendered in the <head> Tag
 */

function google_analytics4(string $Code) {
	echo <<<AAA
<!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=$Code">
</script>
<script>
window.dataLayer = window.dataLayer || [];
function gtag() {dataLayer.push(arguments);}
gtag('js', new Date());
gtag('config', '$Code');
</script>
AAA;
};

// ajoute le code pour Google Analytics 4 (Le code GOOGLE_ANA_CODE doit commencer par "G-")
if ($config->get('GOOGLE_ANA_CODE') && strpos($config->get('GOOGLE_ANA_CODE'), 'G-') === 0) {
    google_analytics4($config->get('GOOGLE_ANA_CODE')); // Le code mis dans GOOGLE_ANA_CODE semble être un code G4
}
