<?php

require(__DIR__ . '/ParameterModel.php');

class ParameterDTO
{
    private array $parameters = [];

    public function __construct()
    {
        $parameterModel = new ParameterModel();
        $this->parameters = $parameterModel->findAll();
    }

    public function dataToArray()
    {
        $array = [];
        /** FOR COMPATIBILITY ONLY */
        $array['EA_DB'] = 'cr'; // Préfixe des noms de tables
        $array['EA_UDB'] = 'cr'; //préfixe de la table utilisateurs
        $array['EA_ERROR'] = 0;   // Pas d'affichage d'erreur en production
        $array['EA_LANG'] = 'fr';
        $array['EA_VERSION'] = '0.0.0';
        $array['EA_MAINTENANCE'] = 0;
        $array['EXTERN_MAIL'] = 0;
        $array['CHERCH_TS_TYP'] = 0;
        $array['ECLAIR_LOG'] = 0;
        $array['UPLOAD_DIR'] = '_upload';
        $array['INCLUDE_HEADER'] = '';
        $array['PIED_PAGE'] = '';
        $array['PUB_ZONE_MENU'] = 'Zone info libre';
        $array['SITENAME'] = 'Civil-Records';
        $array['SITE_URL'] = '';
        $array['SITE_INVENTAIRE'] = '';
        $array['TIP_FILTRER'] = '0';
        $array['TIP_AUTOFREE'] = '0';
        $array['TIP_DUREE'] = '1';
        // On peut a) mettre dans "config" : define('EA_URL_CE_SERVEUR', 'http://127.0.0.1'); b) ajouter dans act_params "EA_URL_CE_SERVEUR"
        // if (!defined('EA_URL_CE_SERVEUR')) define('EA_URL_CE_SERVEUR', mkSiteUrl()); // "actutils.php" soit toujours référencé après "adlcutils.php" définissant mkSiteUrl
        $array['EA_URL_CE_SERVEUR'] = 'http://127.0.0.1';
        // if (!defined('EA_URL_SITE')) define('EA_URL_SITE', EA_URL_CE_SERVEUR); // dans l'immédiat on ne change pas les anciennes REFs
        $array['EA_URL_SITE'] = 'http://127.0.0.1';

        $array['TOUJOURS'] = '2033-12-31'; // limite des comptes illimités
        /** END FOR COMPATIBILITY ONLY */
        foreach ($this->parameters as $parameter) {
            $array[$parameter['param']] = $parameter['valeur'];
        }
        return $array;
    }

    public function arrayToData()
    {
        
    }
}