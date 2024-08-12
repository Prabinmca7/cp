<?

/**
 * Optimizes admin JS and CSS during tarball creation.
 */
class AdminAssets {
    private static $verbose;

    /**
     * Optimizes JS and CSS.
     * @param bool $verbose Whether to print out info messages (optional); defaults to true
     * @param string $basePath Base directory (optional); defaults to core/admin
     * @return int The number of optimized assets written out
     */
    static function optimize($verbose = true, $basePath = '') {
        self::$verbose = $verbose;
        $basePath || ($basePath = HTMLROOT . \RightNow\Utils\Url::getCoreAssetPath('admin'));

        self::say("\nOptimizing admin assets...");

        $files = self::getAssets($basePath);
        if ($files) {
            $includes = self::getDependencies($files);
            $optimized = self::combineAndMinify($files, $includes['files']);

            return self::write($optimized);
        }
        return 0;
    }

    /**
     * Retrieves all JS and CSS files inside $basePath.
     * @param string $basePath Base dir to look under
     * @return array Absolute paths to all JS & CSS
     */
    private static function getAssets($basePath) {
        $files = array_filter(\RightNow\Utils\Filesystem::getDirectoryTree($basePath, array('js', 'css')));
        $toProcess = array();
        $count = 0;

        // Paths returned from #getDirectoryTree are relative to $basePath. Prepend it back on.
        foreach ($files as $path => $modified) {
            $toProcess []= "$basePath/$path";
            $count++;
        }

        self::say("Found $count assets for $basePath");

        return $toProcess;
    }

    /**
     * Parses the given files for dependency declarations.
     * @param array $files Return value from #getAssets
     * @return array Return value from Admin#processAssetDirectives
     */
    private static function getDependencies(array $files) {
        require_once CPCORE . 'Internal/Utils/Admin.php';

        return \RightNow\Internal\Utils\Admin::processAssetDirectives($files);
    }

    /**
     * Combines assets and their dependencies and minifies.
     * @param array $files Return value from #getAssets
     * @param array $includes Array 'files' value from #getDependencies return value
     * @return array Keyed by absolute file path to the asset; values are each file's contents
     */
    private static function combineAndMinify(array $files, array $includes) {
        require_once CPCORE . 'Libraries/ThirdParty/JSMin.php';
        require_once CPCORE . 'Internal/Libraries/ThemeParser.php';

        $processed = array();

        // Minify_CSS_UriRewriter appends paths starting with / and replaces // with the HTMLROOT value.
        // To prevent HTMLROOT from appearing twice in the path, ensure it does not have trailinig slash.
        $htmlRoot = \RightNow\Utils\Text::removeTrailingSlash(HTMLROOT);

        foreach ($files as $file) {
            $pre = '';

            if ($dependencies = $includes[$file]) {
                foreach ($dependencies as $dependent) {
                    $dependentType = pathinfo($dependent, PATHINFO_EXTENSION);
                    $dependentContent = @file_get_contents(HTMLROOT . $dependent);

                    $pre .= ($dependentType === 'js')
                        ? $dependentContent
                        // fix asset paths in CSS depedencies such as YUI
                        : \RightNow\Internal\Libraries\CssUrlRewriter::rewrite(
                            $dependentContent, dirname($dependent), $htmlRoot);
                }
            }

            $fileType = pathinfo($file, PATHINFO_EXTENSION);
            $fileContent = file_get_contents($file);
            $processed[$file] = "$pre\n" . (($fileType === 'js')
                // asset paths in CSS are correct due to <base href ... />
                ? \RightNow\Libraries\ThirdParty\JSMin::minify($fileContent)
                : \RightNow\Utils\Text::minifyCss($fileContent)
            );
        }

        return $processed;
    }

    /**
     * Writes the content of the given files.
     * @param array $files Return of #combineAndMinify
     */
    private static function write(array $files) {
        $wrote = 0;
        foreach ($files as $path => $content) {
            file_put_contents($path, $content);
            $wrote++;
        }

        self::say("Optimized $wrote assets");

        return $wrote;
    }

    /**
     * Prints $message if the class is in verbose mode.
     * @param string $message String message
     */
    private static function say($message) {
        if (self::$verbose) {
            echo "$message\n";
        }
    }
}
