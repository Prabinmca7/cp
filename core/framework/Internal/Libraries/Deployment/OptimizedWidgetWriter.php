<?

namespace RightNow\Internal\Libraries\Deployment;

use RightNow\Utils\Text,
    RightNow\Utils\FileSystem,
    RightNow\Internal\Libraries\Widget\Registry,
    RightNow\Internal\Utils\Deployment\CodeWriter,
    RightNow\Internal\Libraries\Widget\PathInfo;

/**
 * Writes code to widget optimized directories.
 */
class OptimizedWidgetWriter {
    private $widget;
    private $targetDir;

    public $path;
    public $jsPath;
    public $phpPath;

    /**
     * Constructor.
     * @param PathInfo $widget    Widget's PathInfo instance
     * @param string $targetDir Custom optimized directory for
     *                          the widget
     */
    function __construct($widget, $targetDir = null) {
        $this->widget = $widget;

        $this->targetDir = !$targetDir || Text::endsWith($targetDir, '/') ? $targetDir : "{$targetDir}/";
        $this->path = self::getWidgetPath($this->widget, $this->targetDir);

        $this->jsPath = "{$this->path}optimizedWidget.js";
        $this->phpPath = "{$this->path}optimizedWidget.php";
    }

    /**
     * Writes out the given code to an
     * optimizedWidget.js file.
     * @param string $code Code to write
     * @return boolean Whether the file was
     *                         actually written
     */
    function writeJavaScript ($code) {
        if (FileSystem::isReadableFile($this->widget->logic)) {
            FileSystem::filePutContentsOrThrowExceptionOnFailure($this->jsPath, $code);
            return true;
        }

        return false;
    }

    /**
     * Writes out the given code to an optimizedWidget.php
     * file.
     * @param string $code         Code to write
     * @param array  $requirements Additional widgets to require_once on
     *                              their optimizedWidget.php files
     *                              Values expected to be relative widget paths
     */
    function writePhp ($code, array $requirements) {
        if ($requirements) {
            $code = $this->injectPhpRequirements($code, $requirements);
        }

        FileSystem::filePutContentsOrThrowExceptionOnFailure($this->phpPath, $code);
    }

    /**
     * Builds `require_once` statements for the given widgets'
     * optimizedWidget.php file and inserts them into $code.
     * @param string $code        Code to write
     * @param array $requirements Relative paths of widgets
     * @return string               $code with require statements
     *                                    inserted
     */
    private function injectPhpRequirements ($code, array $requirements) {
        $requireStatements = array();

        foreach ($requirements as $widgetPath) {
            $widgetInfo = Registry::getWidgetPathInfo($widgetPath);
            //handling standard and custom widget includes in the same way
            $requireStatements []= '\RightNow\Utils\Widgets::requireOptimizedWidgetController("' . $widgetInfo->relativePath . '");';
        }

        return CodeWriter::insertAfterNamespace($code, "\n" . implode("\n", $requireStatements));
    }

    /**
     * Generates the base path to a widget's optimized directory. Includes
     * trailing /.
     * @param PathInfo $widget The PathInfo object for the widget
     * @param string $customWidgetBaseDirectory Directory prefix to use for custom widget optimized directory
     * @return string Path to the widget's optimized directory
     */
    private static function getWidgetPath ($widget, $customWidgetBaseDirectory = null) {
        $path = '';

        if ($widget->type === 'standard') {
            $path = $widget->absolutePath;
        }
        else if ($customWidgetBaseDirectory) {
            $path = "{$customWidgetBaseDirectory}widgets/{$widget->relativePath}/{$widget->version}";
        }
        else {
            // If the widget's custom, then when writing out the `require` statements, use
            // the current site mode's path to custom widgets rather than anything hardcoded.
            $path = "' . APPPATH . 'widgets/{$widget->relativePath}/{$widget->version}";
        }

        return "{$path}/optimized/";
    }
}
