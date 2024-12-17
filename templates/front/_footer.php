    <div id="pied_page2" class="pied_page2">
        <div id="totop2" class="totop2">
            <p class="totop2"><strong><a href="#top">Top</a></strong></p>
        </div>
        <div id="texte_pied2" class="texte_pied2">
            <p class="texte_pied2"><?= PIED_PAGE; ?></p>
        </div>
        <div id="copyright2" class="copyright2">
            <p class="copyright2">
                Ce site est propulsé par <em><a href="">Civil-Records</a></em>
            </p>
        </div>
    </div>

    <?php if (file_exists(__DIR__ . '/../../_config/js_externe_footer.inc.php')) {
        include(__DIR__ . '/../../_config/js_externe_footer.inc.php');
    }

    global $TIPmsg;  // message d'alerte pré-blocage IP
    if ($TIPmsg <> "" && TIP_MODE_ALERT >= 2) {
        echo '<script language="javascript">';
        echo 'alert("' . $TIPmsg . '")';
        echo '</script>';
    } ?>

    </body>

</html>