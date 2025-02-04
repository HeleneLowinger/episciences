<?php

/**
 * Class Episciences_Settings
 * Parametres d'Episciences
 *
 */

class Episciences_Settings
{

    static public $githubChangeLogURL = 'https://github.com/CCSDForge/episciences/blob/main/CHANGELOG.md';

    static public $githubCommitURL = 'https://github.com/CCSDForge/episciences/commit/';

    /** @var array */
    static private $_appversion = [];

    /**
     * Langues disponibles de l'interface
     * @var array
     */
    static private $_languages = ['fr', 'en'];

    /**
     * Récupération des langues de l'archive Episciences
     * @return array
     */
    public static function getLanguages()
    {
        return self::$_languages;
    }

    /**
     * Lit un fichier de conf, stocke en registry le résultat pour accès
     * ultérieur éventuel, retourne la conf sous forme de tableau
     * dépend de l'environnement en cours : MODULE et SPACE_NAME
     *
     * @param string $file
     * @param string
     * @return array|boolean
     * @throws Zend_Config_Exception
     */
    public static function getConfigFile($file = '', $type = 'json')
    {
        if ($file === '') {
            return false;
        }

        try {
            return Zend_Registry::get($file);
        } catch (Zend_Exception $e) {

            $filePath = self::getConfigFilePath($file);

            if (!$filePath) {
                return false;
            }
            switch ($type) {
                default:
                case 'json':
                    $configSolr = new Zend_Config_Json($filePath, null, ['ignoreconstants' => true]);
                    break;
                case 'ini':
                    $configSolr = new Zend_Config_Ini($filePath);
                    break;
            }

            if ($configSolr instanceof Zend_Config) {
                $configSolrArr = $configSolr->toArray();
            } else {
                $configSolrArr = [];
            }
            Zend_Registry::set($file, $configSolrArr);
        }

        return $configSolrArr;
    }

    /**
     * Retourne le chemin d'un fichier de conf pour un portail ou une collection
     * dépend de l'environnement en cours : MODULE et SPACE_NAME
     *
     * @param string $file
     * @return string
     */
    public static function getConfigFilePath(string $file)
    {
        $configPath = APPLICATION_PATH . '/../data/' . RVCODE . '/config/' . $file;

        if (is_readable($configPath)) {
            return $configPath;
        }

        $configPath = APPLICATION_PATH . '/../data/default/config/' . $file;
        if (is_readable($configPath)) {
            return $configPath;
        }

        return false;
    }


    /**
     * Return application version from application.ini or git index file
     * @return array
     */
    public static function getApplicationVersion(): array
    {

        if (!empty(self::$_appversion)) {
            return self::$_appversion;
        }

        $gitHash = '';
        $gitBranch = '';
        $deployDate = '';

        $versionFilename = APPLICATION_PATH . '/../version.php';

        if (is_readable($versionFilename)) {
            include($versionFilename);
            self::$_appversion['gitHash'] = $gitHash;
            self::$_appversion['gitBranch'] = $gitBranch;
            self::$_appversion['deployDate'] = $deployDate;
            return self::$_appversion;
        }


        $file = APPLICATION_PATH . '/../application/configs/application.ini';
        $gitfile = APPLICATION_PATH . '/../.git/index';
        if (file_exists($gitfile)) {
            $file = $gitfile;
        }

        $timestamp = filemtime($file);

        self::$_appversion['gitHash'] = (string)$timestamp;
        self::$_appversion['gitBranch'] = $gitBranch;
        self::$_appversion['deployDate'] = date('Y-m-d', $timestamp);

        return self::$_appversion;
    }


}

