    <div class="footer">
        <div class="text-right"><a href="#top"><strong>Top</strong></a></div>
        <div class="text-center"><p><?= $config->get('PIED_PAGE'); ?></p></div>
        <div class="text-center">
            <p>
                Ce site est propulsé par <em><a href="">Civil-Records</a></em>
            </p>
        </div>
    </div>

    <?php if (file_exists(__DIR__ . '/../../_config/js_externe_footer.inc.php')) {
        include(__DIR__ . '/../../_config/js_externe_footer.inc.php');
    }

    global $TIPmsg;  // message d'alerte pré-blocage IP
    if ($TIPmsg <> "" && $config->get('TIP_MODE_ALERT') >= 2) {
        echo '<script language="javascript">';
        echo 'alert("' . $TIPmsg . '")';
        echo '</script>';
    } ?>

    </body>

</html>