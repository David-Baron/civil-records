<?php

namespace CivilRecords\Domain\Entity;

class User
{
    const LEVELS = [
        0 => 'Public',
        1 => 'Liste des communes',
        2 => 'Liste des patronymes',
        3 => 'Table des actes',
        4 => 'Détails des actes (avec limites)',
        5 => 'Détails sans limitation',
        6 => 'Chargement NIMEGUE et CSV',
        7 => 'Ajout d\'actes',
        8 => 'Administration tous actes',
        9 => 'Gestion des utilisateurs',
        10 => 'Super administrator'
    ];

    const POINT_MANAGEMENT_MODES = [
        0 => 'Disabled',
        1 => 'Manual',
        2 => 'Automatic'
    ];

    const ACCOUNT_STATUS = [
        'W' => 'To activate',
        'A' => 'To be approved',
        'N' => 'Normal',
        'B' => 'Blocked'
    ];
}
