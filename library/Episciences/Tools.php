<?php

class Episciences_Tools
{
    public static $bashColors = [
        'red' => "\033[0;31m",
        'blue' => "\033[0;34m",
        'green' => "\033[0;32m",
        'cyan' => "\033[0;36m",
        'purple' => "\033[0;35m",
        'light_gray' => "\033[0;37m",
        'dark_gray' => "\033[1;30m",
        'light_blue' => "\033[1;34m",
        'light_green' => "\033[1;32m",
        'light_cyan' => "\033[1;36m",
        'light_red' => "\033[1;31m",
        'light_purple' => "\033[1;35m",
        'yellow' => "\033[1;33m",
        'bold' => "\033[1m",
        'default' => "\033[0m"
    ];

    public static $latex2utf8 = [
        //cedilla
        "\\c{c}" => 'ç',
        "\\c c" => 'ç',
        //ogonek
        "\\k{a}" => 'ą',
        "\\k a" => 'ą',
        //barred l (l with stroke)
        "\\l{}" => 'ł',
        "\\l " => 'ł',
        //dot under the letter
        "\\d{u}" => 'ụ',
        "\\d u" => 'ụ',
        //ring over the letter (for å there is also the special command \aa)
        "\\r{a}" => 'å',
        "\\r a" => 'å',
        //caron/háček ("v") over the letter
        "\\v{s}" => 'š',
        "\\v s" => 'š',
        // git #270 : (circumflex)
        '\\^a' => 'â',

        // a
        //acute accent
        "\\'{a}" => 'á',
        "\\'a" => 'á',
        // grave accent
        "\\`{a}" => 'à',
        "\\`a" => 'à',
        "\\u{a}" => 'ă',
        "\\u a" => 'ă',
        // a trema
        "\\\"{a}" => 'ä',
        "\\\"a" => 'ä',


        // e
        //grave accent
        "\\`{e}" => 'è',
        "\\`e" => 'è',

        //acute accent
        "\\'{e}" => 'é',
        "\\'e" => 'é',
        //circumflex
        "\\^{e}" => 'ê',
        "\\^e" => 'ê',
        //umlaut, trema or dieresis
        "\\\"{e}" => 'ë',
        "\\\"e" => 'ë',

        // i
        "\\`i" => "ì",

        // o
        //grave accent
        "\\`{o}" => 'ò',
        "\\`o" => 'ò',
        //acute accent
        "\\'{o}" => 'ó',
        "\\'o" => 'ó',
        //circumflex
        "\\^{o}" => 'ô',
        "\\^o" => 'ô',
        //umlaut, trema or dieresis
        "\\\"{o}" => 'ö',
        "\\\"o" => 'ö',
        //long Hungarian umlaut (double acute)
        "\\H{o}" => 'ő',
        "\\H o" => 'ő',
        //tilde
        "\\~{o}" => 'õ',
        "\\~o" => 'õ',
        //macron accent (bar over the letter)
        "\\={o}" => 'ō',
        "\\=o" => 'ō',
        //bar under the letter
        "\\b{o}" => 'o',
        "\\b o" => 'o',
        //dot over the letter
        "\\.{o}" => 'ȯ',
        "\\.o" => 'ȯ',
        //breve over the letter
        "\\u{o}" => 'ŏ',
        "\\u o" => 'ŏ',
        //"tie" (inverted u) over the two letters
        "\\t{oo}" => 'o͡o',
        "\\t oo" => 'o͡o',
        //slashed o (o with stroke)
        //"\\o" => 'ø',

        // u
        //long Hungarian umlaut (double acute)
        "\\H{u}" => "ű",
        "\\H u" => "ű",
        //umlaut, trema or dieresis
        "\\\"{u}" => 'ü',
        "\\\"u" => 'ü',

    ];

    public const APPLICATION_OCTET_STREAM = 'application/octet-stream';


    // check if string is a valid sha1 (40 hexadecimal characters)
    public static function isSha1($string): bool
    {
        return (bool)preg_match('/^[0-9a-f]{40}$/i', $string);
    }

    public static function isJson($string): bool
    {
        if (!is_string($string)) {
            return false;
        }
        json_decode($string, true);
        return (json_last_error() === JSON_ERROR_NONE);
    }

    // sort a multidimensional array from its keys
    public static function multi_ksort(&$arr)
    {
        ksort($arr);
        foreach ($arr as &$a) {
            if (is_array($a) && !empty($a)) {
                static::multi_ksort($a);
            }
        }
    }

    public static function multisort($array, $key)
    {
        uksort($array, static function ($a, $b) use ($key) {
            $a = $a[$key];
            $b = $b[$key];
            if ($a === $b) {
                $r = 0;
            } else {
                $r = ($a > $b) ? 1 : -1;
            }
            return $r;
        });

        return $array;
    }

    public static function filter_multiarray(&$input, $filter = '')
    {
        if (is_array($input)) {
            foreach ($input as $key => &$value) {

                if (!is_array($value) && $value === $filter) {
                    unset($input[$key]);
                }

                if (is_array($value) && count($value)) {
                    static::filter_multiarray($value);
                }

                if (is_array($value) && !count($value)) {
                    unset($input[$key]);
                }
            }
        }

        return $input;
    }

    public static function search_multiarray($array, $search, $keys = []): array
    {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $sub = static::search_multiarray($value, $search, array_merge($keys, [$key]));
                if (count($sub)) {
                    return $sub;
                }
            } elseif ($value === $search) {
                return array_merge($keys, [$key]);
            }
        }

        return [];
    }

    public static function preg_array_key_exists($pattern, $array): int
    {
        $keys = array_keys($array);
        return (int)preg_grep($pattern, $keys);
    }

    /**
     * upload form files to specified path
     * @param $path : folder where the files will be stored
     * @param array $replace : if $replace is defined, delete files having the same id before upload
     * @return array
     * @throws Zend_File_Transfer_Exception
     */
    public static function uploadFiles($path, $replace = []): array
    {
        $results = [];
        $upload = new Zend_File_Transfer_Adapter_Http();
        $files = $upload->getFileInfo();

        if (count($files) && !is_dir($path) && !mkdir($path, 0777, true) && !is_dir($path)) {
            error_log('Upload file failed: directory "%s" was not created', $path);
        }

        foreach ($files as $file => $info) {

            if (!$info['error'] && $info['size']) {

                // delete previous file version (if there is one)
                if (!empty($replace) && array_key_exists($file, $replace) && file_exists($path . $replace[$file])) {
                    unlink($path . $replace[$file]);
                }

                $filename = Ccsd_Tools::cleanFileName($info['name']);
                //rename file
                if (array_key_exists('file_unique_id', $replace) && !empty($replace['file_unique_id'])) {
                    $explode = explode('.', $filename);
                    if (!empty($explode[count($explode) - 1])) {
                        $filename = $replace['file_unique_id'] . '_' . $file . '.' . $explode[count($explode) - 1];
                    }

                }
                $filename = Episciences_Tools::filenameRotate($path, $filename);
                // save file
                $upload->addFilter('Rename', $path . $filename, $file);
                $results[$file]['name'] = $filename;
                $results[$file]['errors'] = (!$upload->receive($file)) ? $upload->getMessages() : null;
            }
        }
        return $results;
    }

    public static function getLastQuery()
    {
        $profiler = Zend_Db_Table::getDefaultAdapter()->getProfiler();
        if ($profiler->getTotalNumQueries()) {
            $lastQueryProfile = $profiler->getLastQueryProfile();
            $lastQuery = $lastQueryProfile->getQuery();
            $lastQueryParams = $lastQueryProfile->getQueryParams();
            foreach ($lastQueryParams as $param) {
                $lastQuery = substr_replace($lastQuery, "'" . $param . "'", strpos($lastQuery, '?'), 1);
            }
            return ($lastQuery === '') ? "---" : $lastQuery;
        }
        return false;
    }

    /**
     * @return mixed
     * @throws Zend_Exception
     */
    public static function getLocale()
    {
        return Zend_Registry::get("Zend_Translate")->getLocale();
    }

    /**
     * @return array
     * @throws Zend_Exception
     */
    public static function getLanguages(): array
    {
        $languages = (Zend_Registry::isRegistered('languages')) ? Zend_Registry::get('languages') : [];
        return array_intersect_key(Ccsd_Locale::getLanguage(), array_flip($languages));
    }

    /**
     * @return array
     * @throws Zend_Exception
     */
    public static function getRequiredLanguages(): array
    {
        return array_keys(static::getLanguages());
    }

    /**
     * @param $code
     * @param null $locale
     * @return bool
     * @throws Zend_Exception
     */
    public static function getLanguageLabel($code, $locale = null): bool
    {
        if (!isset($locale)) {
            $locale = Zend_Registry::get('lang');
        }

        $languages = Zend_Locale::getTranslationList('language', $locale);

        if (array_key_exists($code, $languages)) {
            return $languages[$code];
        }

        return false;
    }

    /**
     * @param $languages
     * @param null $locale
     * @return array|bool
     * @throws Zend_Exception
     */
    public static function sortLanguages($languages, $locale = null)
    {
        if (empty($languages)) {
            return false;
        }

        if (!isset($locale)) {
            $locale = Zend_Registry::get('lang');
        }

        $translated = [];
        foreach ($languages as $code) {
            $translated[$code] = static::getLanguageLabel($code, $locale);
        }
        asort($translated);

        return array_keys($translated);
    }

    /**
     * @param $translations
     * @param $path
     * @param $file
     * @return bool|false|int
     */
    public static function writeTranslations($translations, $path, $file)
    {
        if (!is_dir($path)) {
            return false;
        }

        static::multi_ksort($translations);
        $langs = array_keys($translations);

        $totalBytesWritten = 0;
        foreach ($langs as $lang) {

            // Fix temporaire pour éviter de perdre les traductions en cas de bug sur les langues envoyées
            if (is_numeric($lang)) {
                continue;
            }

            if (!is_dir($path . $lang) && !mkdir($concurrentDirectory = $path . $lang) && !is_dir($concurrentDirectory)) {
                error_log('Write translation failed: directory "%s" was not created', $concurrentDirectory);
            }

            $filePath = $path . $lang . '/' . $file;
            $result = '<?php' . PHP_EOL . 'return ' . var_export($translations[$lang], true) . ';';

            $target = fopen($filePath, 'wb');
            $bytesWritten = fwrite($target, $result);
            $totalBytesWritten += $bytesWritten;
            fclose($target);
        }

        return $totalBytesWritten;
    }

    public static function addTranslations($newTranslations, $path, $file)
    {
        $tmp = static::getTranslations($path, $file);

        foreach ($newTranslations as $lang => $translations) {
            $tmp[$lang] += $translations;
        }

        static::writeTranslations($tmp, $path, $file);
    }

    /**
     * Lit le contenu d'un fichier de traduction
     * Peut filtrer le résultat par langue et par expression régulière
     * @param $file
     * @param null $lang
     * @param bool $pattern
     * @return array
     */
    public static function readTranslation($file, $lang = null, $pattern = false): array
    {
        $res = [];

        if (is_file($file)) {

            try {
                /** @var Zend_Translate_Adapter $translation */
                $translation = new Zend_Translate('array', $file, $lang, ['disableNotices' => true]);
            } catch (Zend_Translate_Exception $e) {
                return $res;
            }

            $list = $translation->getList();

            if (is_array($list)) {

                if ($lang !== null && in_array($lang, $list, true)) {
                    foreach ($translation->getMessages($lang) as $key => $value) {
                        if (!$pattern || preg_match($pattern, $key)) {
                            $res[$key] = $value;
                        }
                    }
                } else if (count($list)) {
                    foreach ($translation->getList() as $l) {
                        foreach ($translation->getMessages($l) as $key => $value) {
                            if (!$pattern || preg_match($pattern, $key)) {
                                $res[$key][$l] = $value;
                            }
                        }
                    }
                }
            }
        }

        return $res;
    }

    /**
     * Charge les traductions du dossier passé en paramètre
     * Si un fichier est passé en paramètre, on ne charge que celui-ci
     * Sinon, on charge tous les fichiers contenus dans le dossier
     * @param $path
     * @param null $file
     * @return mixed
     * @throws Zend_Exception
     */
    public static function loadTranslations($path, $file = null)
    {
        $translator = Zend_Registry::get('Zend_Translate');
        $locale = $translator->getLocale();

        if (is_dir($path)) {
            $langDir = opendir($path);
            while ($lang = readdir($langDir)) {
                // loop through all language folders
                if ($lang !== '.' && $lang !== '..' && $lang !== '.svn' && is_dir($path . $lang)) {
                    $langs[] = $lang;

                    // only fetch translations for specified file
                    if ($file) {
                        if (file_exists($path . $lang . '/' . $file)) {
                            $translator->addTranslation($path . $lang . '/' . $file, $lang);
                        }
                    } // load all translations
                    else {
                        $dir = opendir($path . $lang);
                        while ($file = readdir($dir)) {
                            if ($file !== '.' && $file !== '..' && !is_dir($path . $lang . '/' . $file)) {
                                $translator->addTranslation($path . $lang . '/' . $file, $lang);
                            }
                        }
                    }
                }
            }
        }

        $translator->setLocale($locale);
        return $translator;
    }

    /** return requested translations, in every language
     * @param $path
     * @param null $file
     * @param bool $pattern
     * @return array
     */
    public static function getTranslations($path, $file = null, $pattern = false): array
    {
        $translations = [];
        if (is_dir($path)) {
            $langDir = opendir($path);
            while ($lang = readdir($langDir)) {
                // loop through all language folders
                if ($lang !== '.' && $lang !== '..' && is_dir($path . $lang)) {

                    // only fetch translations for specified file
                    if ($file) {
                        if (file_exists($path . $lang . '/' . $file)) {
                            $translations[$lang] = self::readTranslation($path . $lang . '/' . $file, $lang, $pattern);
                        }
                    } // fetch translations for all files
                    else {
                        $dir = opendir($path . $lang);
                        $tmp = [];
                        while ($file = readdir($dir)) {
                            if ($file !== '.' && $file !== '..' && !is_dir($path . $lang . '/' . $file)) {
                                $tmp += static::readTranslation($path . $lang . '/' . $file, $lang, $pattern);
                            }
                        }
                        $translations[$lang] = $tmp;
                    }
                }
            }
        }

        return $translations;
    }

    /**
     * Renvoie les traductions qui ne correspondent pas au pattern passé en paramètre
     * Peut scanner tous les fichiers du répertoire, ou chercher celui passé en paramètre
     * @param $path
     * @param $file
     * @param $pattern
     * @return array
     */
    public static function getOtherTranslations($path, $file, $pattern): array
    {

        if (!empty($file) && !preg_match('#(.*).php#', $file)) {
            $file .= '.php';
        }

        $translations = static::getTranslations($path, $file);
        // Filtre les traductions en fonction du pattern
        if (!empty($translations)) {
            foreach ($translations as $lang => $tmp_translation) {
                foreach ($tmp_translation as $key => $translation) {
                    if (preg_match($pattern, $key)) {
                        unset($translations[$lang][$key]);
                    }
                }
            }
        }

        return $translations;
    }

    public static function extension($filename): string
    {
        if (is_file($filename)) {
            $path_info = pathinfo($filename);
            $ext = Ccsd_Tools::ifsetor($path_info['extension'], '');
        } else {
            $ext = substr($filename, (strrpos($filename, '.') + 1));
        }
        return strtolower($ext);
    }


    /**
     * Retourne le type MIME d'une ressource
     * @param string $filename
     * @return string $mime
     */
    public static function getMimeType($filename): string
    {
        $finfo = new finfo(FILEINFO_MIME);
        $mime = $finfo->file($filename);
        if (strpos($mime, 'zip') !== false) {
            return static::getMimeFileZip($filename);
        }

        if (strpos($mime, 'htm') !== false) {
            $mime = 'application/octet-stream';
        }
        return $mime;
    }

    public static function getMimeFileZip($filename): string
    {
        $ext = static::extension($filename);
        $mime = null;

        if (in_array($ext, ['odt', 'ott', 'odp', 'otp', 'ods', 'ots', 'sxw'])) {
            $mime = "application/opendocument";
        } else if (in_array($ext, ['pptx', 'ppsx'])) {
            $mime = "application/vnd.ms-powerpoint";
        } else if (in_array($ext, ['docx', 'dotx'])) {
            $mime = "application/msword";
        } else if ($ext === 'xlsx') {
            $mime = "application/vnd.ms-excel";
        } else {
            $mime = "application/zip";
        }

        return $mime;
    }

    /**
     * retourne un nouveau nom de fichier si le nom fourni existe déjà dans le répertoire donné en paramètre
     * @param $dir
     * @param $filename
     * @return string|null (NULL en cas d'erreur)
     */
    public static function filenameRotate($dir, $filename)
    {
        while (is_file($dir . $filename)) {
            $filename = preg_replace_callback('/(?>_(\d*))?(\.\w*)?$/', static function ($matches) {
                $i = (isset($matches[1])) ? '_' . ((int)$matches[1] + 1) : '';
                $ext = $matches[2] ?? '';

                return $i . $ext;
            }, $filename);
        }
        return $filename;
    }


    public static function startTimer()
    {
        return microtime(true);
    }

    public static function getTimer($timer, $msg = null, $display = true)
    {
        $time = microtime(true) - $timer;

        $result = "<div>";
        $result .= ($msg) ? $msg . ' : ' : "running time: ";
        $result .= number_format($time, 3);
        $result .= "s</div>";

        if ($display) {
            echo $result;
        }

        return $result;
    }

    /**
     * Curl sur le serveur de requêtes de solr
     *
     * @param string $queryString
     *            requête du type q=docid:19
     * @param string $core
     *            par défaut hal
     * @param string $handler
     *            par défaut select
     * @param boolean $addDefaultFilters
     *            par défaut false
     * @return mixed string boolean du GET ou curl_error()
     * @throws Exception
     * @see /library/Ccsd/Search/Solr/configs/endpoints.ini
     */
    public static function solrCurl($queryString, $core = 'episciences', $handler = 'select', $addDefaultFilters = false)
    {
        if ($addDefaultFilters) {
            //Ajout des filtres par defaut de l'environnement
            $queryString .= Episciences_Search_Solr_Search::getDefaultFiltersAsURL(Episciences_Settings::getConfigFile('solr.episciences.defaultFilters.json'));
        }
        return Ccsd_Tools::solrCurl($queryString, $core, $handler);
    }

    /**
     * @param string $xml_string
     * @param string $path
     * @param bool $force_array
     * @param bool $overwriteLanguageValues
     * @return array|bool|mixed
     */
    public static function xpath($xml_string, $path, $force_array = false, $overwriteLanguageValues = true)
    {
        if (!$xml_string || !$path) {
            return false;
        }

        $out = [];
        $xml = new DOMDocument();

        set_error_handler('\Ccsd\Xml\Exception::HandleXmlError');
        $xml->loadXML($xml_string);
        restore_error_handler();
        $xpath = new DOMXPath($xml);
        foreach (Ccsd_Tools::getNamespaces($xml->documentElement) as $id => $ns) {
            $xpath->registerNamespace($id, $ns);
        }

        foreach ($xpath->query($path) as $entry) {
            $lang = $entry->getAttribute('xml:lang');
            if ($lang) {
                if (!$overwriteLanguageValues) {
                    // eg multiple keywords with the same language
                    $out[][$lang] = $entry->nodeValue;
                } else {
                    $out[$lang] = $entry->nodeValue;
                }
            } else {
                $out[] = $entry->nodeValue;
            }
        }

        if ($force_array) {
            return $out;
        }

        switch (count($out)) {
            case 0:
                return false;
            case 1:
                return array_shift($out);
            default:
                return $out;
        }

    }

    public static function getTitleFromIndexedPaper($doc, $locale)
    {
        if (array_key_exists($locale . '_paper_title_t', $doc)) {
            $title = $doc[$locale . '_paper_title_t'];
        } elseif (array_key_exists('language_s', $doc) && array_key_exists($doc['language_s'] . '_paper_title_t', $doc)) {
            $title = $doc[$doc['language_s'] . '_paper_title_t'];
        } elseif (is_array($doc['paper_title_t'])) {
            $title = $doc['paper_title_t'][0];
        } else {
            $title = $doc['paper_title_t'];
        }


        return static::decodeLatex($title);
    }

    public static function getAbstractFromIndexedPaper($doc, $locale)
    {
        if (array_key_exists($locale . '_abstract_t', $doc)) {
            $abstract = $doc[$locale . '_abstract_t'];
        } elseif (array_key_exists('language_s', $doc) && array_key_exists($doc['language_s'] . '_abstract_t', $doc)) {
            $abstract = $doc[$doc['language_s'] . '_abstract_t'];
        } elseif (is_array($doc['abstract_t'])) {
            $abstract = $doc['abstract_t'][0];
        } else {
            $abstract = $doc['abstract_t'];
        }

        return static::decodeLatex($abstract);
    }

    public static function getSectionFromIndexedPaper($doc, $locale)
    {
        if (array_key_exists($locale . '_section_title_t', $doc)) {
            $abstract = $doc[$locale . '_section_title_t'];

        } elseif (isset($doc['language_s']) && array_key_exists($doc['language_s'] . '_section_title_t', $doc)) {
            $abstract = $doc[$doc['language_s'] . '_section_title_t'];

        } elseif (array_key_exists('section_title_t', $doc)) {

            if (is_array($doc['section_title_t'])) {
                $abstract = $doc['section_title_t'][0];
            } else {
                $abstract = $doc['section_title_t'];
            }

        } else {
            $abstract = null;
        }

        return $abstract;
    }

    public static function addDateInterval($date, $interval, $format = 'Y-m-d')
    {
        $result = date_create($date);
        date_add($result, date_interval_create_from_date_string($interval));
        return date_format($result, $format);
    }

    public static function isValidDate($date, $format): bool
    {
        $d = DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }

    public static function isValidSQLDate($datestring): bool
    {
        return static::isValidDate($datestring, 'Y-m-d');
    }

    public static function isValidSQLDateTime($datestring): bool
    {
        return static::isValidDate($datestring, 'Y-m-d H:i:s');
    }


    public static function getValidSQLDate($datestring)
    {
        $result = null;
        if (static::isValidSQLDate($datestring)) {
            $result = $datestring;
        } elseif (static::isValidDate($datestring, 'Y-m')) {
            $result = $datestring . '-01';
        } elseif (static::isValidDate($datestring, 'Y')) {
            $result = $datestring . '-01-01';
        }
        return $result;
    }

    /**
     * @param $datestring
     * @return mixed|string|null
     */
    public static function getValidSQLDateTime($datestring)
    {
        $result = null;
        if (static::isValidSQLDateTime($datestring)) {
            $result = $datestring;
        } elseif ($datestring = static::getValidSQLDate($datestring)) {
            $result = $datestring . ' 00:00:00';
        }
        return $result;
    }

    public static function formatUser($firstname = "", $lastname = "", $civ = ""): string
    {

        $name = (($civ && is_string($civ)) ? $civ . " " : "");
        $name .= (($firstname && is_string($firstname)) ? ucfirst(mb_strtolower($firstname, 'UTF-8')) . " " : "");
        $name .= (($lastname && is_string($lastname)) ? ucfirst(mb_strtolower($lastname, 'UTF-8')) : "");

        return trim($name);
    }

    public static function decodeLatex($string)
    {
        //$string = Ccsd_Tools::decodeLatex($string);
        return str_replace(array_keys(static::$latex2utf8), array_values(static::$latex2utf8), $string);
    }

    // check if an url begins with http:// or https://. if not, add http at the beginning of the string.
    public static function checkUrl($url): string
    {
        if (!preg_match("#^http(s*)://#", $url)) {
            $url = 'http://' . $url;
        }
        return $url;
    }

    /**
     * recursively delete a folder and its content
     * @param $directory : directory to be deleted
     * @return bool: true on success or false on failure
     */
    public static function deleteDir($directory): bool
    {
        if (!file_exists($directory)) {
            return false;
        }

        foreach (glob("{$directory}/*") as $file) {
            if (is_dir($file)) {
                static::deleteDir($file);
            } else {
                unlink($file);
            }
        }
        return rmdir($directory);
    }

    /**
     * return a translation from a set of translations, given a set of preferred lang
     * @param $translations
     * @param $preferred
     * @param bool $forceResult
     * @return string
     */
    public static function getTranslation($translations, $preferred, $forceResult = true)
    {
        $result = null;

        if (!is_array($translations)) {
            $translations = [$translations];
        }
        if (!is_array($preferred)) {
            $preferred = [$preferred];
        }

        foreach ($preferred as $lang) {
            if (array_key_exists($lang, $translations)) {
                $result = $translations[$lang];
                break;
            }
        }

        if (!$result && $forceResult) {
            $result = array_shift($translations);
        }

        return $result;
    }

    /**
     * Spécifie l'en-tête HTTP string lors de l'envoi des fichiers HTML
     * @param $str
     */

    public static function header($str)
    {
        header($str);
    }

    /***
     * Analyse variable pour trouver l'expression regEx et met les résultats dans matches
     * Pour preg_mach_all : http://php.net/manual/fr/function.preg-match-all.php
     * @param string $regEx
     * @param string $variable
     * @return array
     */
    public static function extractPattern(string $regEx, string $variable): array
    {

        if (!isset($regEx, $variable) || !is_string($regEx) || !is_string($variable)) {
            return [];
        }

        if (!preg_match_all($regEx, $variable, $matches)) {
            return [];
        }

        return $matches[0];
    }

    /**
     * Retourne les données pour un Datatable :
     * La source de données principale utilisée pour un DataTable doit toujours être un tableau
     * Chaque élément de ce tableau définira une ligne à afficher.
     * N.B . Il est fortement recommandé, pour des raisons de sécurité, de convertir le paramètre "draw" en entier,
     *       plutôt que de simplement rappeler au client ce qu'il a envoyé dans le paramètre draw,
     *       afin d'éviter les attaques XSS (Cross Site Scripting)
     * @param string $tbody
     * @param int $draw : Le compteur de dessin.
     * @param int $recordsTotal : Nombre total d'enregistrements avant filtrage
     * @param int $recordsFiltred : Nombre total d'enregistrements, après filtrage
     * @return false|string
     */
    public static function getDataTableData(string $tbody = '', int $draw = 1, int $recordsTotal = 0, int $recordsFiltred = 0)
    {
        // Les données à afficher dans la table
        /** @var string[] $data */
        $data = [];

        if ($tbody !== '') {
            /** @var string $tbody */
            $tbody = Ccsd_Tools::spaces2Space(preg_replace("/\\t(\\r)?/i", " ", $tbody));
            $tbody = mb_convert_encoding($tbody, 'UTF-8');

            $matches_tr = self::extractPattern('#<tr[^>]*>(.*?)</tr>#is', $tbody);

            foreach ($matches_tr as $td) {
                $data[] = self::extractPattern('#<td[^>]*>(.*?)</td>#is', $td);
            }
        }

        return json_encode(
            [
                'draw' => $draw,
                'recordsTotal' => $recordsTotal,
                'recordsFiltered' => $recordsFiltred,
                'data' => $data
            ]
        );

    }

    /**
     * Retourne les colonnes sur lesquelles l'ordre est exécuté et le sens de l'ordre.
     * @param array $requestOrder
     * @param array $columns
     * @return array
     */
    public static function dataTableOrder(array $requestOrder = [], array $columns = []): array
    {
        $order = [];
        if (!empty($columns)) {
            foreach ($requestOrder as $columnOrder) {
                $column = $columnOrder['column'];
                $direction = $columnOrder['dir'];
                if (!empty($columns[$column])) {
                    if (is_array($columns[$column])) {
                        foreach ($columns[$column] as $value) {
                            $order[] = $value . ' ' . $direction;
                        }
                    } else {
                        $order[] = $columns[$column] . ' ' . $direction;
                    }
                }
            }
        }
        return $order;
    }

    /**
     * @param string $text
     * @return mixed|string|string[]|null
     */
    public static function formatText(string $text = '')
    {
        $tab = '&nbsp;&nbsp;&nbsp;&nbsp;';
        if (!empty($text)) {
            $text = str_replace('\t', $tab, $text);
            $text = preg_replace("/ {4,}/", $tab, $text);
            $text = nl2br($text);
        }
        return $text;
    }

    /**
     * Compare les deux tableaux + Calcule l'intersection et la différence entre eux
     * @param array $tab1
     * @param array $tab2
     * @return array
     */
    public static function checkArrayEquality(array $tab1, array $tab2): array
    {

        $result = ['equality' => false, 'arrayDiff' => [], 'arrayIntersect' => []];

        $arrayIntersectTab1 = array_intersect($tab1, $tab2);
        $arrayIntersectTab2 = array_intersect($tab2, $tab1);

        if ($tab1 === $arrayIntersectTab1 && $tab2 === $arrayIntersectTab2) { //$tab1 === $tab2
            $result['equality'] = true;
        }

        $result['arrayDiff'][0] = array_diff($tab1, $arrayIntersectTab1);
        $result['arrayDiff'][1] = array_diff($tab2, $arrayIntersectTab2);
        $result['arrayIntersect'] = $arrayIntersectTab1;

        return $result;
    }

    /**
     * @param array $filesList
     * @param string $source
     * @param string $dest
     * @return bool
     */
    public static function cpFiles(array $filesList, string $source, string $dest): bool
    {

        $nbFilesNotCopied = 0;
        if (!is_dir($dest)) {
            $resMkdir = mkdir($dest, 0777, true);
            if (!$resMkdir) {
                error_log('Fatal error : unable to create folder: ' . $dest);
                return $resMkdir;
            }
        }

        foreach ($filesList as $file) {
            if (!copy($source . $file, $dest . $file)) {
                error_log('FAILED_TO_COPY_FILE_ERROR: ' . $file . '( SOURCE: ' . $source . 'DESTINATION: ' . $dest . ' )');
                $nbFilesNotCopied++;
            }
        }

        return ($nbFilesNotCopied == count($filesList)) ? false : true;

    }

    /**
     * Enlève les traces d'un login
     * @param string $body
     * @return string|string[]|null
     */
    public static function cleanBody(string $body = '')
    {
        return preg_replace('#<span class="username">(.*)<\/span>#', '', $body);
    }

    /**
     * @deprecated use php 7.4 native function array_key_first
     * @param array $arr
     * @return int|string|null
     */
    public static function epi_array_key_first(array $arr)
    {
        if (function_exists('array_key_first')) {
            return array_key_first($arr);
        }

        foreach ($arr as $key => $unused) {
            return $key;
        }

        return NULL;
    }

    /**
     * formats the file sizes
     * @param $bytes
     * @param int $precision
     * @return string
     */
    public static function toHumanReadable($bytes, $precision = 2): string
    {
        if ($bytes === 0) {
            return "0.00 B";
        }

        $unit = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
        $e = floor(log($bytes, 1024));

        return round($bytes / (1024 ** $e), $precision) . ' ' . $unit[$e];
    }

    /**
     * build attached files description
     * @param array|string[] $extensions
     * @param string $additionalDescription : additional description
     * @param bool $forceAdditionalTranslation [true: translate also $additionalDescription]
     * @return string
     */
    public static function buildAttachedFilesDescription(array $extensions = ALLOWED_EXTENSIONS, string $additionalDescription = '', $forceAdditionalTranslation = false)
    {

        $implode_extensions = implode(', ', $extensions);

        try {
            $translator = Zend_Registry::get('Zend_Translate');
            $additionalDescription = $forceAdditionalTranslation ? $translator->translate($additionalDescription) : $additionalDescription;
            $description = $translator->translate('Extensions autorisées : ') . $implode_extensions . $additionalDescription;
            $description .= '<br>';
            $description .= $translator->translate('Taille maximale des fichiers que vous pouvez télécharger');
            $description .= $translator->translate(' :');
            return ($description . ' <strong>' . self::toHumanReadable(MAX_FILE_SIZE) . '</strong>');

        } catch (Zend_Exception $e) {
            error_log('ZEND_TRANSLATE_EXCEPTION: ' . $e->getMessage());
        }
        return $additionalDescription;
    }

    /**
     * @param string $mail
     * @return array
     */
    public static function postMailValidation(string $mail)
    {
        $result = [];
        $tmp = html_entity_decode(trim($mail));
        $tmp = explode(' ', $tmp);
        $email = array_pop($tmp);
        $result['email'] = str_replace(['<', '>'], '', $email);
        $result['name'] = (!empty($tmp)) ? implode(' ', $tmp) : null;
        return $result;
    }

    /**
     *  Supprime les éléments (chaines vides) d'un tableau
     * @param array $attachments
     * @return array
     */
    public static function arrayFilterAttachments(array $attachments): array
    {
        $attachments = array_filter($attachments, function ($value) {
            return '' !== $value;
        });

        return $attachments;
    }

    /**
     * Construit les liens vers les fichiers joints à une réponse avec une version temporaire
     * @param array $domElements
     * @return string
     */
    public static function buildHtmlTmpDocUrls(array $domElements): string
    {
        $text = '';
        /** @var DOMElement $firstElement */
        $firstElement = $domElements[0];
        try {
            $paper = Episciences_PapersManager::get($firstElement->nodeValue, false);

        } catch (Zend_Db_Statement_Exception $e) {
            error_log('(Zend_Db_Statement_Exception: ' . $e->getMessage());
            return $text;
        }

        if (!$paper->isTmp()) { //  reverification
            return $text;
        }

        try {
            $translator = Zend_Registry::get('Zend_Translate');
            $fileExp = $translator->translate('Fichier');
        } catch (Zend_Exception $e) {
            $fileExp = 'Fichier';
            error_log('Expression "%s" was not translated', $fileExp . ': ' . $e->getMessage());
        }

        $paperId = $paper->getPaperid();

        $identifier = $paper->getIdentifier();
        // Extract file(s) name
        $subStr = substr($identifier, (strlen($paperId) + 1));

        $result = !self::isJson($subStr) ? $result = (array)$subStr : json_decode($subStr, true);

        if (empty($result)) {
            error_log('No file(s) attached to the tmp version (docId = ' . $paper->getDocid() . "): the upload of the file(s) failed when responding to a revision request !");
            return $text;
        }

        $cHref = '/tmp_files/' . $paperId . '/';

        $text = '';
        foreach ($result as $index => $fileName) {
            $href = $cHref . $fileName;
            $text .= '<a target="_blank" href="' . $href . '">';
            $text .= $fileExp . ' ' . ($index + 1) . ' > ' . $fileName;
            $text .= '</a>';
            $text .= '</br>';
        }

        return $text;
    }

    /**
     * Convert to bytes (from human readable size)
     * @param string $humanReadableVal
     * @return int
     * @throws Exception
     */
    public static function convertToBytes(string $humanReadableVal): int
    {
        $availableUnits = ['b', 'k', 'm', 'g', 't', 'p', 'e'];

        $humanReadableVal = trim($humanReadableVal);
        $unit = ($humanReadableVal !== '') ? strtolower($humanReadableVal[strlen($humanReadableVal) - 1]) : 'b';
        $val = (int)$humanReadableVal;

        if (!in_array($unit, $availableUnits, true)) {
            throw new Exception('Conversion from { ' . $unit . ' } to { bytes } is not available.');
        }

        switch ($unit) {
            case 'e' :
                $val *= 1024;
            case 'p' :
                $val *= 1024;
            case 't' :
                $val *= 1024;
            case 'g':
                $val *= 1024;
            case 'm':
                $val *= 1024;
            case 'k':
                $val *= 1024;
            case 'b':
        }
        return $val;
    }

    public static function convertToCamelCase(string $string, string $separator = '_', bool $capitalizeFirstCharacter = false)
    {

        $str = str_replace($separator, '', ucwords($string, $separator));

        if (!$capitalizeFirstCharacter) {
            $str = lcfirst($str);
        }

        return $str;
    }

    /**
     * @param $authorString
     * @param bool $protectLatex
     * @return string
     */
    public static function reformatOaiDcAuthor($authorString, $protectLatex = false)
    {
        $fistname = '';
        $lastname = '';

        $authAsArray = explode(',', $authorString);

        if (!empty($authAsArray[1])) {
            $fistname = trim($authAsArray[1]);
        }

        if (!empty($authAsArray[0])) {
            $lastname = trim($authAsArray[0]);
        }

        if ($protectLatex) {
            $fistname = Ccsd_Tools::protectLatex($fistname);
            $lastname = Ccsd_Tools::protectLatex($lastname);
        }

        return sprintf("%s %s", $fistname, $lastname);
    }

    /**
     * Reset mb_internal_encoding to server selection
     * @see https://developer.wordpress.org/reference/functions/mbstring_binary_safe_encoding/
     */
    public static function resetMbstringEncoding() {
        self::mbstringBinarySafeEncoding( true );
    }

    /**
     * Set mb_internal_encoding to safe encoding for curl
     * mbstring.func_overload is enabled and body length is calculated incorrectly.
     * @see https://developer.wordpress.org/reference/functions/mbstring_binary_safe_encoding/
     * @param false $reset
     */
    public static function mbstringBinarySafeEncoding( $reset = false ) {
        static $encodings  = [];
        static $overloaded = null;

        if ( is_null( $overloaded ) ) {
            $overloaded = function_exists( 'mb_internal_encoding' ) && ( ini_get( 'mbstring.func_overload' ) & 2 );
        }

        if ( false === $overloaded ) {
            return;
        }

        if ( ! $reset ) {
            $encoding = mb_internal_encoding();
            $encodings[] = $encoding;
            mb_internal_encoding( 'ISO-8859-1' );
        }

        if ( $reset && $encodings ) {
            $encoding = array_pop( $encodings );
            mb_internal_encoding( $encoding );
        }
    }

}
