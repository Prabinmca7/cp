<?php

namespace RightNow\Internal\Utils;

use RightNow\Internal\Libraries\Widget\Registry,
    RightNow\Connect\v1_4 as ConnectPHP,
    RightNow\Utils\Config as ConfigExternal;

/**
 * A collection of functions used by the CP admin pages
 */
final class Admin
{
    /**
     * Performs operations to set the site back to CP2.
     * @return bool Whether the operation succeeded
     */
    public static function back2cp2(){
        if (!IS_HOSTED) {
            //Change out htmlroot symlink
            $symlinkLocation = HTMLROOT . '/euf';
            $oldCPDirectorySymlink = DOCROOT . '/euf';
            if(!is_dir($oldCPDirectorySymlink)){
                exit("Unable to modify assets path to point to old v2 directory. Expected that $oldCPDirectorySymlink exists, but it doesn't.");
            }
            $oldCPDirectory = realpath($oldCPDirectorySymlink) . '/webfiles';
            @unlink($symlinkLocation);
            @symlink($oldCPDirectory, $symlinkLocation);
        }

        //Change development manifest file to use 2.0
        $frameworkVersion = CUSTOMER_FILES . 'frameworkVersion';
        if (is_writable($frameworkVersion)) {
            require_once CPCORE . 'Internal/Utils/VersionTracking.php';
            VersionTracking::recordVersionChanges(CUSTOMER_FILES, null, 'development');
            file_put_contents($frameworkVersion, '2.0');
            return true;
        }
        return false;
    }

    /**
     * Returns an array of environments (production, staging_XX, development, reference) and their respective labels
     *
     * @param bool $shortLabels If true, return <environment>_LBL slot, otherwise <environment>_AREA_LBL
     * @param bool $shortenStagingLabelIfOnlyOne If true and only 1 staging area, don't append label with staging index or name.
     * @return array List of modes
     */
    public static function getEnvironmentModes($shortLabels = true, $shortenStagingLabelIfOnlyOne = true)
    {
        $modes = array();
        $numberOfStagingEnvironments = 0;
        $lastStagingKey = $lastStagingValue = null;
        foreach(self::getEnvironmentModeDetails() as $mode => $details) {
            $slot = ($shortLabels === true) ? $details['shortLabelSlot'] : $details['areaLabelSlot'];
            $modes[$mode] = (is_int($slot)) ? ConfigExternal::getMessage($slot) : ConfigExternal::ASTRgetMessage($slot);
            if (array_key_exists('stagingName', $details)) {
                $numberOfStagingEnvironments++;
                list($lastStagingKey, $lastStagingValue) = array($mode, $modes[$mode]);
                $modes[$mode] .= sprintf(' %s %s', $details['stagingIndex'], $details['stagingName']);
            }
        }
        if (\RightNow\Utils\Config::getConfig(OKCS_ENABLED)) {
            $modes['okcs_reference'] = \RightNow\Utils\Config::getMessage(OKCS_REFERENCE_LBL);
        }
        if ($shortenStagingLabelIfOnlyOne === true && $numberOfStagingEnvironments === 1) {
            $modes[$lastStagingKey] = $lastStagingValue;
        }
        return $modes;
    }

    /**
     * Returns an array of all available staging environments (staging_XX) and their respective short labels.
     * @return array List of modes
     */
    public static function getStagingEnvironmentModes()
    {
        $modes = array();
        foreach(self::getStagingEnvironments() as $stagingDirectory => $details) {
            $modes[$stagingDirectory] = $details['shortLabelSlot'] . sprintf(' %s %s', $details['stagingIndex'], $details['stagingName']);
        }
        return $modes;
    }

    /**
     * Returns base path to staging area
     * @return string Path to .cfg>/scripts/cp/generated/staging/
     */
    public static function getStagingBasePath()
    {
        return OPTIMIZED_FILES . 'staging/';
    }

    /**
     * Returns an array of staging environments on disk with the key being the directory name
     * (staging_01 - 99) and an array of details (short_label, area_label, staging_name, etc).
     *
     * @param string $baseDir Base directory to look for staging_xx dirs.
     * @return array List of staging environments
     * @throws \Exception If the base directory provided doesn't exist
     */
    public static function getStagingEnvironments($baseDir = null)
    {
        require_once CPCORE . 'Internal/Libraries/Staging.php';
        if ($baseDir === null) {
            $baseDir = self::getStagingBasePath();
            if (!\RightNow\Utils\FileSystem::isReadableDirectory($baseDir)) {
                return array();
            }
        }
        else if (!is_dir($baseDir)) {
            throw new \Exception(ConfigExternal::getMessage(INVALID_PATH_MSG) . ": $baseDir");
        }

        // Labels below should be replaced with slot definitions. Don't want to do an \RightNow\Utils\Config::ASTRgetMessage()
        // here though as that should be done by the function caller.
        $shortLabel = STAGING_LBL;
        $areaLabel = STAGING_AREA_LBL;
        $filter = array('function', function($f) {return $f->isDir() && \RightNow\Utils\Text::beginsWith($f->getFilename(), STAGING_PREFIX);});
        $extraData = array(
            function($f) {return (\RightNow\Utils\FileSystem::isReadableFile($f->getPathname() . '/__STAGING_NAME__')) ? trim(file_get_contents($f->getPathname() . '/__STAGING_NAME__')) : '';},
            function($f) {return (preg_match(\RightNow\Internal\Libraries\Staging::stagingRegex(), $f->getFilename(), $matches) ? $matches[1] : null);},
        );

        $environments = array();
        foreach(\RightNow\Utils\FileSystem::listDirectory($baseDir, false, false, $filter, $extraData) as $data) {
            $environments[$data[0]] = array('shortLabelSlot' => $shortLabel, 'areaLabelSlot' => $areaLabel, 'stagingName' => $data[1], 'stagingIndex' => $data[2]);
        }
        return $environments;
    }

    /**
     * Generates a list of all widgets and all intermediate folders of widgets
     * in sorted order.
     * e.g. [standard, standard/foo, standard/foo/Bar, standard/qux, standard/qux/Banana, ... ]
     * @return array List of widgets
     */
    public static function getAllWidgetDirectories() {
        $list = array();

        foreach (Registry::getAllWidgets() as $path => $widgetInfo) {
            $dirs = '';
            $subDirs = explode('/', $path);
            foreach ($subDirs as $dir) {
                // Add each intermediate dir to the array, remove the leading slash that appears on the first dir.
                $dirs .= "/$dir";
                $list []= ltrim($dirs, '/');
            }
        }

        $list = array_unique($list);
        sort($list);
        return $list;
    }

    /**
     * Processes the given asset files for dependency declarations in each file's header.
     * @param array $assets Contains absolute paths to CSS or JS files
     * @return array The dependencies to include; keys:
     *  'all': array containing all found dependencies
     *  'files': array whose keys are absolute file paths and values are arrays containing dependencies for the file
     */
    public static function processAssetDirectives(array $assets) {
        static $directivePattern, $jsHeader, $cssHeader;
        if (!isset($directivePattern)) {
            /*
             * Grab out header declarations at the top of the file appearing before code in CSS and JS.
             * YUI files should have an additional 'yui' declaration between the
             * require directive and the file path so we know to prefix 'em properly.
             * Regular files are expected to be relative to the core asset path.
             * In JS:
             *  Single line comment declaration examples:
             *      //= require some/file.js
             *      // = require yui some/yui/module-min.js
             * In CSS:
             *  Multiline comment declaration examples:
             *      /*
             *      * = require some/file.css
             *      *= require yui some/yui/file-min.css
             *      *\/
             */
            $jsHeader = '/\A(\s*(\/\/.*))+/x';
            $cssHeader = '/\/\*(.*?)\*\//s';
            $directivePattern = '/^[\W]*=(.*)?(\*\/)?/m';
        }

        $found = array('all' => array(), 'files' => array());

        foreach ($assets as $file) {
            $headerMatch = $requireMatch = array();
            $extension = pathinfo($file, PATHINFO_EXTENSION);

            // Only look for directives in JS & CSS for now.
            if ($extension !== 'js' && $extension !== 'css') continue;

            $pattern = ($extension === 'js') ? $jsHeader : $cssHeader;

            if (preg_match($pattern, @file_get_contents($file), $headerMatch) &&
                preg_match_all($directivePattern, $headerMatch[0], $requireMatch)) {
                $included = array();

                foreach ($requireMatch[1] as $match) {
                    $matchArray = explode(' ', trim($match));
                    if(count($matchArray) === 3){
                        list($declaration, $asset, $optional) = $matchArray;
                    }else if(count($matchArray) === 2){
                        list($declaration, $asset) = $matchArray;
                    }else{
                        list($declaration) = $matchArray;
                    }
                    if ($declaration !== 'require') continue;
                    
                    if (isset($optional) && $optional && $asset === 'yui') {
                        // YUI file
                        $included []= \RightNow\Utils\Url::getYUICodePath($optional);
                    }
                    else if ($asset) {
                        // Some other generic file; expected to be the correct relative (to core assets) path
                        $included []= \RightNow\Utils\Url::getCoreAssetPath($asset);
                    }
                }
                if ($included) {
                    $found['files'][$file] = $included;
                    $found['all'] = array_merge($found['all'], $included);
                }
            }
        }

        return $found;
    }

    /**
     * Returns an array having the language code as the key and an array of ({lang label}, {interface name}) for the value.
     * @param bool $returnAll If True, return all languages each pointing to the current interface.
     * @return array List of langauges
     */
    public static function getLanguageInterfaceMap($returnAll = false) {
        $languages = self::getLanguageLabels();
        $map = array();
        if ($returnAll) {
            $interface = \RightNow\Internal\Api::intf_name();
            foreach($languages as $lang => $slot) {
                $map[$lang] = array(ConfigExternal::getMessage($slot), $interface);
            }
        }
        else {
            $row = ConnectPHP\ROQL::query('SELECT Name, Language.LookupName FROM SiteInterface')->next();
            while ($result = $row->next()) {
                if (($lang = $result['LookupName']) && ($slot = $languages[$lang])) {
                    $map[$lang] = array(ConfigExternal::getMessage($slot), $result['Name']);
                }
            }
        }

        return $map;
    }

    /**
     * Gather unique widget categories and their md5 hashes
     * @param array $widgets An array of arrays representing widget data with a 'category' key
     * @return array A key-sorted associative array representing unique widget categories (key)
     *   and their md5 hashed categories (value)
     */
    public static function getUniqueCategories($widgets) {
        $categoriesToHash = array();
        foreach($widgets as $widget) {
            foreach($widget['category'] as $widgetCategory) {
                if(!array_key_exists($widgetCategory, $categoriesToHash)) {
                    $widgetCategoryHash = md5($widgetCategory);
                    $categoriesToHash[$widgetCategory] = "category-{$widgetCategoryHash}";
                }
            }
        }
        ksort($categoriesToHash);
        return $categoriesToHash;
    }

    /**
     * Returns an array of supported language codes and corresponding message base slots.
     * @return array List of labels
     */
    private static function getLanguageLabels() {
        return array(
            'ar_EG' => ARABIC_AR_LBL,
            'bg_BG' => BULGARIAN_BG_LBL,
            'cs_CZ' => CZECH_CS_LBL,
            'da_DK' => DANISH_DA_LBL,
            'de_DE' => GERMAN_DE_LBL,
            'el_GR' => GREEK_EL_LBL,
            'en_AU' => EN_AU_LBL,
            'en_GB' => EN_GB_LBL,
            'en_US' => ENGLISH_EN_LBL,
            'es_ES' => SPANISH_ES_LBL,
            'et_EE' => ESTONIAN_ET_LBL,
            'fi_FI' => FINNISH_FI_LBL,
            'fr_CA' => FR_CA_LBL,
            'fr_FR' => FRENCH_FR_LBL,
            'hr_HR' => CROATIAN_HR_LBL,
            'hu_HU' => HUNGARIAN_HU_LBL,
            'it_IT' => ITALIAN_IT_LBL,
            'ja_JP' => JAPANESE_JA_LBL,
            'ko_KR' => KOREAN_KO_LBL,
            'lt_LT' => LITHUANIAN_LT_LBL,
            'lv_LV' => LATVIAN_LV_LBL,
            'nl_NL' => DUTCH_NL_LBL,
            'no_NO' => NORWEGIAN_NO_LBL,
            'pl_PL' => POLISH_PL_LBL,
            'pt_BR' => PORTUGUESE_PT_LBL,
            'ro_RO' => ROMANIAN_RO_LBL,
            'ru_RU' => RUSSIAN_RU_LBL,
            'sl_SI' => SLOVENIAN_SL_LBL,
            'sr_CS' => SERBIAN_SR_LBL,
            'sv_SE' => SWEDISH_SV_LBL,
            'uk_UA' => UKRAINIAN_UK_LBL,
            'zh_CN' => CHINESE_ZH_LBL,
            'zh_HK' => CHINESE_HK_LBL,
            'zh_TW' => CHINESE_TW_LBL,
        );
    }

    /**
     * Returns an array of environments (production, staging_XX, development, reference) each with an array of
     * details (short_label, area_label, etc).
     *
     * @return array List of modes
     */
    private static function getEnvironmentModeDetails()
    {
        $modes = ConfigExternal::getConfig(MOD_CP_ENABLED, 'COMMON') ? array('production' => array('shortLabelSlot' => PRODUCTION_LBL, 'areaLabelSlot' => PRODUCTION_AREA_LBL)) : array();
        foreach(self::getStagingEnvironments() as $stagingDir => $details) {
            $modes[$stagingDir] = $details;
        }
        $modes['development'] = array('shortLabelSlot' => DEVELOPMENT_LBL, 'areaLabelSlot' => DEVELOPMENT_AREA_LBL);
        if (\RightNow\Utils\Config::getConfig(MOD_RNANS_ENABLED) || !\RightNow\Utils\Config::getConfig(OKCS_ENABLED)) {
            $modes['reference'] = array('shortLabelSlot' => REFERENCE_LBL, 'areaLabelSlot' => REFERENCE_IMPLEMENTATION_LBL);
        }
        return $modes;
    }
}
