<?php 
namespace CivilRecords\Domain\Entity;


class DeedType 
{
    const TYPES = [
        'N' => 'Birth',
        'M' => 'Marriage',
        'D' => 'Death',
        'V' => 'Divers'
    ];
}