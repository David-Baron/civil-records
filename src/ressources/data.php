<?php

$indexed_deed_types = [
    1 => ['code' => 'N', 'code_3' => 'NAI', 'libele' => 'Naissances'],
    2 => ['code' => 'M', 'code_3' => 'MAR', 'libele' => 'Mariages'],
    3 => ['code' => 'D', 'code_3' => 'DEC', 'libele' => 'Décès'],
    4 => ['code' => 'V', 'code_3' => 'DIV', 'libele' => 'Actes divers'],
];

$indexed_search_modes = [
    1 => ['code' => 'E', 'libele' => 'Exacte'],
    2 => ['code' => 'D', 'libele' => 'au Début'],
    3 => ['code' => 'F', 'libele' => 'à la Fin'],
    4 => ['code' => 'C', 'libele' => 'est Compris dans'],
    5 => ['code' => 'S', 'libele' => 'Sonore']
];

$indexed_sexes = [
    1 => ['code' => 'M', 'libele' => 'Masculin'],
    2 => ['code' => 'F', 'libele' => 'Féminin'],
    3 => ['code' => '?', 'libele' => 'Non précisé']
];

$sexes = [
    'M' => 'Masculin',
    'F' => 'Féminin',
    '?' => 'Non précisé'
];