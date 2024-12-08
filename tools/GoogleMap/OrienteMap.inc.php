<?php
if (file_exists(__DIR__ . '/GoogleMapV3.php')) {
    include_once(__DIR__ . '/GoogleMapV3.php');
} else {
    include_once(__DIR__ . '/GoogleMapV3-2018.inc.php');
}
