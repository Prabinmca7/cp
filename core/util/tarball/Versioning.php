<?
use RightNow\Utils\FileSystem,
    RightNow\Utils\Framework,
    RightNow\Utils\Widgets,
    RightNow\Utils\Text,
    RightNow\Internal\Libraries\Widget\Registry,
    RightNow\Internal\Utils\Version;

/**
 * Performs versioning tasks during tarball creation.
 */
class Versioning {

    /**
     * Returns the cpHistory file, ensuring it is only fetched once.
     * @param string $path Path to cpHistory file; optional for testing purposes
     * @throws \Exception if the yaml extension is not loaded.
     * @return array The cpHistory file as an array
     */
    static function getCpHistory($path = '') {
        static $cpHistory;
        if (!isset($cpHistory)) {
            if(!extension_loaded('yaml') && !dl('yaml.so')) {
                throw new \Exception('Failed to load the YAML extension!');
            }

            $cpHistory = Version::getVersionFile($path ?: Version::getVersionHistoryPath());
        }
        return $cpHistory;
    }

    /**
     * Converts the cpHistory file from yaml into serialized php object.
     * @param string $path Path to cpHistory file; optional for testing purposes
     */
    static function serializeCpHistoryFile($path = '') {
        $path || ($path = Version::getVersionHistoryPath());
        Version::writeVersionFile($path, self::getCpHistory($path), 'php');
    }

    /**
    * Attach a version stamp to models and widgets (logic/view/controller) when we deploy
    * so that upgrades will have an easier time identifying a file's
    * originating release. Note: This edits the scripts/euf/src files visible through webDAV.
    */
    static function addVersionStamps() {
        $release = ' /* Originating Release: ' . Version::versionNumberToName(MOD_BUILD_VER) . ' */';
        $tagMatch = '/(\<\?php|\<\?)/';
        $replace = '$1' . $release;

        //Prepend the release onto all the models.
        $models = FileSystem::listDirectory(CPCORESRC . 'Models/', true, true, array('method', 'isFile'));
        for($i = 0; $i < count($models); $i++)
        {
            if($buffer = file_get_contents($models[$i]))
            {
                $buffer = preg_replace($tagMatch, $replace, $buffer, 1);
                file_put_contents($models[$i], $buffer);
            }
        }

        //Prepend the release onto all the widget files
        $widgets = FileSystem::listDirectory(CORE_WIDGET_SRC_FILES . 'standard/', true, true, array('match', '/view\.php|logic\.js|controller\.php/i'));
        for($i = 0; $i < count($widgets); $i++)
        {
            $filename = end(explode('/', strtolower($widgets[$i])));
            if($buffer = file_get_contents($widgets[$i]))
            {
                switch($filename)
                {
                    case 'view.php':
                        $buffer = '<?php' . $release . "?>\n" . $buffer;
                        break;
                    case 'controller.php':
                        $buffer = preg_replace($tagMatch, $replace . "\n", $buffer, 1);
                        break;
                    case 'logic.js':
                        $buffer = $release . "\n" . $buffer;
                        break;
                }
                file_put_contents($widgets[$i], $buffer);
            }
        }
    }

    /**
     * Creates version directories for the framework and widgets.
     */
    static function insertVersionDirectories() {
        Framework::insertFrameworkVersionDirectory();
        Framework::insertFrameworkVersionDirectory(HTMLROOT . '/euf/core', CP_FRAMEWORK_VERSION);
        Framework::insertWidgetVersionDirectories();

        // insert version dirs in src folder
        Framework::insertFrameworkVersionDirectory(BASEPATH . 'src/core/framework', null);
        $widgetPaths = array_keys(Registry::getStandardWidgets(true));
        $widgetPaths = array_map(function($widgetPath) {
            return str_replace(CORE_WIDGET_FILES, CORE_WIDGET_SRC_FILES, $widgetPath);
        }, $widgetPaths);
        Framework::insertWidgetVersionDirectories($widgetPaths);
    }

    /**
     * Updates the widget declaration file to have the latest version of every widget declared.
     */
    static function updateDeclarationsFile() {
        $shippedVersions = array();
        $widgetVersionsContent = Version::getVersionFile(APPPATH . 'widgetVersions');
        foreach($widgetVersionsContent as $widgetPath => $widgetVersion)
        {
            if(Text::beginsWithCaseInsensitive($widgetPath, 'standard')) {
                $absolutePath = CORE_WIDGET_FILES . $widgetPath;
                $fullVersion = Widgets::getFullVersionFromManifest($absolutePath);
                $shippedVersions[$widgetPath] = substr($fullVersion, 0, strrpos($fullVersion, '.')); //Remove nano version
            }
        }

        //We don't ship any custom widgets (anymore), but if we did, we'd need to add their version to the $shippedVersions array as well
        Widgets::updateDeclaredWidgetVersions($shippedVersions);
    }

    /**
     * Copy the newly created `widgetVersions` file into the reference mode directory so that
     * the correct widget versions can be used on reference pages.
     */
    static function copyWidgetVersionsToReferenceMode() {
        FileSystem::copyFileOrThrowExceptionOnFailure(CUSTOMER_FILES . 'widgetVersions', CPCORESRC . 'widgetVersions');
    }

    /**
     * Given a list of versions, return those that specify the highest nano version.
     * @param array $versions A list of versions of format {major}.{minor}.{nano}
     * @throws \Exception if versions not in the expected format.
     * @return array The list of versions specifying the highest nano version.
     */
    static function filterByHighestNanoVersion(array $versions) {
        $byNano = array();
        foreach (array_diff($versions, array('category')) as $version) {
            $parts = explode('.', $version);
            if (count($parts) !== 3) {
                throw new \Exception('Versions need to be of format {major}.{minor}.{nano}');
            }
            $majorMinor = "{$parts[0]}.{$parts[1]}";
            if ($parts[2] > $byNano[$majorMinor]) {
                $byNano[$majorMinor] = $parts[2];
            }
        }
        // Update values to contain highest {major}.{minor}.{nano}
        array_walk($byNano, function(&$a, $b) {$a = "{$b}.{$a}";});
        return array_values($byNano);
    }

    /**
     * Return an associative array having 'framework' and widget relative paths as key and a list of highest nano versions as values.
     * @return array An array of 'framework' and widget paths as key and highest nano versions as value.
     */
    static function getHighestNanoVersions() {
        static $versions;
        if (!isset($versions)) {
            $history = self::getCpHistory();
            $versions = array(
                'framework' => self::filterByHighestNanoVersion(array_values($history['frameworkVersions'])),
            );
            foreach($history['widgetVersions'] as $widget => $widgetVersions) {
                $versions[$widget] = self::filterByHighestNanoVersion(array_keys($widgetVersions));
            }
        }
        return $versions;
    }

    /**
     * Returns true if $version is the highest nano version for $frameworkOrWidget
     * @param string $frameworkOrWidget A string specifying 'framework' or a relative widget path.
     * @param string $version A version of format {major}.{minor}.{nano}
     * @return bool Returns true if $version is the highest nano version for $frameworkOrWidget
     */
    static function isHighestNanoVersion($frameworkOrWidget, $version) {
        $versions = self::getHighestNanoVersions();
        if ($version && ($byNano = $versions[$frameworkOrWidget]) && in_array($version, $byNano)) {
            return true;
        }
        return false;
    }

    /**
     * Returns the /euf/core/{version} path where 'version' is {major}.{minor} unless $version older than 3.2.7
     * @param string $version The framework version in {major}.{minor}.{nano} format
     * @return string The euf/core/{version} path
     */
    static function getEufCorePath($version) {
        if (Version::compareVersionNumbers($version, '3.2.7') >= 0){
            list($major, $minor) = explode('.', $version);
            return "/euf/core/{$major}.{$minor}";
        }

        return "/euf/core/{$version}";
    }

    /**
     * Comparison function to sort branches (e.g. rnw-15-5-fixes, rnw-15-8-fixes)
     * @param string $a First branch to compare
     * @param string $b Second branch to compare
     * @return int Whether the first branch is less than, equal to, or greater than the second branch
     */
    static function compareBranchVersions($a, $b) {
        // we assume $a and $b are of the form rnw-15-5-fixes
        list(, $yearA, $monthA) = explode('-', $a);
        list(, $yearB, $monthB) = explode('-', $b);
        $yearA = (int)$yearA;
        $monthA = (int)$monthA;
        $yearB = (int)$yearB;
        $monthB = (int)$monthB;
        if ($yearA < $yearB)
            return -1;
        if ($yearA > $yearB)
            return 1;
        if ($monthA < $monthB)
            return -1;
        if ($monthA > $monthB)
            return 1;
        return 0;
    }

    /**
     * Adds directories for versions specified in the cp/versions directory.
     */
    static function addOldVersions() {
        if(!extension_loaded('yaml') && !dl('yaml.so')) {
            echo "\nFailed to load the YAML extension!\n";
            return;
        }

        $cpVersionsPath = BASEPATH . 'versions';
        $cpSourceVersionsPath = BASEPATH . 'src/versions';
        if (!FileSystem::isReadableDirectory($cpVersionsPath)) {
            return;
        }

        $insertVersionDirectory = function($path, $version) {
            if ($version && FileSystem::isReadableDirectory($path)) {
                Framework::insertVersionDirectory($path, $version);
            }
        };

        $copyDirectory = function($sourcePath, $destinationPath) {
            if (FileSystem::isReadableDirectory($sourcePath)) {
                FileSystem::copyDirectory($sourcePath, $destinationPath, false, false);
            }
        };

        $removeDirectory = function($path) {
            FileSystem::removeDirectory($path, true);
        };

        // versions are now 3.0.1, etc vs 12.11, etc - TODO: probably should check that the dir is also %d.%d.%d
        $versionsToPull = FileSystem::listDirectory($cpVersionsPath, false, false, array('method', 'isDir'));
        // if the current version is 13.5 (3.1.1), then we want to overlay 13.2 (3.0.2) before we overlay 12.11 (3.0.1)
        usort($versionsToPull, "Versioning::compareBranchVersions");
        foreach (array_reverse($versionsToPull) as $version) {
            $versionPath = "$cpVersionsPath/$version";
            $sourceVersionPath = "$cpSourceVersionsPath/$version";
            $versionMappingPath = "$versionPath/versionMapping";
            if (!FileSystem::isReadableDirectory($versionPath) || !FileSystem::isReadableFile($versionMappingPath))
                continue;
            $versionMapping = yaml_parse_file($versionMappingPath);
            if (!$versionMapping)
                continue;

            // Framework
            $frameworkVersion = $versionMapping['framework'];
            if (self::isHighestNanoVersion('framework', $frameworkVersion)) {
                $insertVersionDirectory("$versionPath/core/framework", $frameworkVersion);
                $insertVersionDirectory("$sourceVersionPath/core/framework", $frameworkVersion);
                $insertVersionDirectory("$versionPath/webfiles/assets", $frameworkVersion);

                // copy assets to doc_root/euf/core
                $eufCorePath = self::getEufCorePath($frameworkVersion);
                $copyDirectory("$versionPath/webfiles/core", HTMLROOT . $eufCorePath);
                $removeDirectory(HTMLROOT . "{$eufCorePath}/static");

                // copy compiled framework to scripts/cp/core/...
                $copyDirectory("$versionPath/core/framework", CPCORE);

                // copy source framework to scripts/cp/src/core/...
                $copyDirectory("$sourceVersionPath/core/framework", BASEPATH . 'src/core/framework');
                // insert minified JS assets to 'current' MOD_BUILD_SP and MOD_BUILD_NUM
                $insertVersionDirectory("$versionPath/webfiles/assets/js", MOD_BUILD_SP . '.' . MOD_BUILD_NUM);

                // remove reference page views from core framework
                $removeDirectory(CPCORE . "{$frameworkVersion}/views");
                // remove admin views from src folder
                $removeDirectory(BASEPATH . "src/core/framework/{$frameworkVersion}/Views");

                // copy customer pages to scripts/cp/src/core/...
                $copyDirectory("$versionPath/core/framework/views", BASEPATH . "src/core/framework/{$frameworkVersion}/views");
                // copy widgetVersions to scripts/cp/src/core/...
                FileSystem::copyFileOrThrowExceptionOnFailure("$versionPath/widgetVersions", BASEPATH . "src/core/framework/{$frameworkVersion}/widgetVersions");
            }

            // Widgets
            if (is_array($versionMapping['widgets'])) {
                foreach ($versionMapping['widgets'] as $widgetPath => $widgetVersion) {
                    if (self::isHighestNanoVersion($widgetPath, $widgetVersion)) {
                        $insertVersionDirectory("$versionPath/core/widgets/$widgetPath", $widgetVersion);
                        $insertVersionDirectory("$sourceVersionPath/core/widgets/$widgetPath", $widgetVersion);
                    }
                    else {
                        $removeDirectory("$versionPath/core/widgets/$widgetPath");
                        $removeDirectory("$sourceVersionPath/core/widgets/$widgetPath");
                    }
                }
            }

            // copy compiled widgets to scripts/cp/core/...
            $copyDirectory("$versionPath/core/widgets", CORE_WIDGET_FILES);

            // copy source widgets to scripts/cp/src/core/...
            $copyDirectory("$sourceVersionPath/core/widgets", BASEPATH . 'src/core/widgets');
        }
    }
}
