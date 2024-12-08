<?php
//js_externe_header.inc.php
//-------------------------
function google_analytics4($Code) {
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
if (defined("GOOGLE_ANA_CODE") and strpos(GOOGLE_ANA_CODE, 'G-') === 0) { // Le code mis dans GOOGLE_ANA_CODE semble être un code G4
  google_analytics4( GOOGLE_ANA_CODE );
}
