<?

namespace RightNow\Internal\Libraries\Deployment\Assets;

use RightNow\Utils\Url,
    RightNow\Utils\Text,
    RightNow\Libraries\ThirdParty\JSMin;

/**
 * Performs concatenation of JS files.
 */
class AssetOptimizer {
    /**
     * Remove --externs=/home/nkaushal/src/ejsExtern.js from $closureCommand on resolving the defect 150511-000052
     */
    private static $closureCommand = array(
        '/nfs/local/linux/jdk/1.7/current/bin/java',
        '-jar /nfs/local/generic/closure/20140407/compiler.jar',
        '--externs=/nfs/local/generic/closure/20140407/mainExterns.js',
        '--summary_detail_level=3',
    );
    private static $closureErrorFlags = array(
        '',
        'deprecated',
        'accessControls',
        'fileoverviewTags',
        'strictModuleDepCheck',
        'unknownDefines',
        'uselessCode',
        'nonStandardJsDocs',
        'ambiguousFunctionDecl',
        'checkRegExp',
        'checkVars',
        'constantProperty',
        'internetExplorerChecks',
        'invalidCasts',
        'typeInvalidation',
        'undefinedVars',
        'es5Strict',
        'externsValidation',
        'visibility',
    );
    private static $rightNowExtern = '--externs=/nfs/local/generic/closure/20140407/RightNowExtern.js';
    private static $requiresRightNowExtern = array(
        'RightNow.Chat', 'modules/ui', 'modules/widgetHelpers',
    );

    /**
     * Prepends a string onto every element in an array.
     * @param  array  $filePaths Array to prepend each element
     * @param  string $prepend   String to prepend onto each element
     * @return array            $filePaths with $prepend prepended onto
     *                                     every element
     */
    static function prependFilePaths (array $filePaths, $prepend) {
        return array_map(function ($path) use ($prepend) {
            return "{$prepend}{$path}";
        }, $filePaths);
    }

    /**
     * Performs the JS minification.
     * @param  string|array $inputFiles String path to file on disk or
     *                                  array paths to files on disk
     * @param  array  $options    Options:
     *                            - obfuscate: whether to use Closure Compiler (true)
     *                                or just JSMin (false)
     * @return string             minified JS
     * @throws \Exception If Closure Compiler emits an error or warning
     */
    static function minify ($inputFiles, array $options = array()) {
        if (!is_array($inputFiles)) {
            $inputFiles = array($inputFiles);
        }

        list($vendorFiles, $inputFiles) = self::extractVendorFiles($inputFiles);

        $vendorFiles = ($vendorFiles) ? self::concatFiles($vendorFiles) : '';

        return $vendorFiles . ((isset($options['obfuscate']) && $options['obfuscate'])
            ? self::minifyWithClosure($inputFiles)
            : self::minifyWithJSMin($inputFiles));
    }

    /**
     * Separate out the vendor and application js files.
     * @param  array $files Files to concat and minify
     * @return array        two elements:
     *                          array of vendor files
     *                          array of application files
     */
    private static function extractVendorFiles (array $files) {
        $types = array(
            'app'    => array(),
            'vendor' => array(),
        );

        foreach ($files as $file) {
            $types[self::isVendorFile($file) ? 'vendor' : 'app'] []= $file;
        }

        return array($types['vendor'], $types['app']);
    }

    /**
     * Determines if the specified file path is a vendor file that should
     * be excluded from minification.
     * @param  string $filePath File path to examine
     * @return boolean           True if the path is a vendor path
     */
    private static function isVendorFile ($filePath) {
        static $vendorFiles;
        if (!$vendorFiles) {
            $vendorFiles = array(
                Url::getYUICodePath(),
                Url::getCoreAssetPath("ejs"),
            );
        }

        return self::pathContainedInList($filePath, $vendorFiles);
    }

    /**
     * Search for $filePath in every element of $list.
     * @param  string  $filePath File path to examine
     * @param array $list List of paths to search within
     * @return boolean           True if a vendor file
     */
    private static function pathContainedInList ($filePath, array $list) {
        foreach ($list as $substring) {
            if (Text::stringContains($filePath, $substring)) return true;
        }
        return false;
    }

    /**
     * Combines the files specified at the paths.
     * @param  array  $files File paths to combine
     * @param  boolean $jsMin Whether to minify using jsmin
     * @return string         concatenated files
     */
    private static function concatFiles (array $files, $jsMin = false) {
        return array_reduce($files, function ($combined, $file) use ($jsMin) {
            $content = file_get_contents($file);
            if ($jsMin) {
                $content = trim(JSMin::minify($content));
            }
            return $combined . $content;
        }, '');
    }

    /**
     * Constructs the command needed for shelling out to the Google Closure Compiler jar.
     * @param array $files JS files to concat together
     * @return string shell command
     */
    private static function getClosureCommand (array $files) {
        static $command;
        if (!$command) {
            $command = implode(' ', self::$closureCommand) . implode(' --jscomp_error=', self::$closureErrorFlags);
        }

        $files = implode(' ', self::prependFilePaths($files, '--js='));

        return (self::pathContainedInList($files, self::$requiresRightNowExtern))
            ? "$command $files " . self::$rightNowExtern
            : "$command $files";
    }

    /**
     * Shells out to run the Closure Compiler jar.
     * @param  array $inputFiles Files to minify and combine
     * @return string             minified JS
     * @throws \Exception If Closure Compiler emits an error or warning
     */
    private static function minifyWithClosure (array $inputFiles) {
        $command = self::getClosureCommand($inputFiles);

        exec("$command 2>&1", $output);

        $statusOfCommand = array_shift($output);

        if (!Text::stringContains($statusOfCommand, '0 error(s), 0 warning(s)')) {
            array_unshift($output, $statusOfCommand);
            throw new \Exception(implode(' ', $output));
        }

        return implode('', $output);
    }

    /**
     * Runs JSMin on each file.
     * @param  array $inputFiles Files to minify and combine
     * @return string             minified JS
     */
    private static function minifyWithJSMin (array $inputFiles) {
        require_once CPCORE . 'Libraries/ThirdParty/JSMin.php';

        return self::concatFiles($inputFiles, true);
    }
}
