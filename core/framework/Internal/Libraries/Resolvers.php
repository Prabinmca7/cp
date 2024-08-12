<?
namespace RightNow\Internal\Libraries;
use RightNow\Utils\FileSystem,
    RightNow\Utils\Config,
    RightNow\Utils\Text;

/**
 * The ThemeParser uses a theme resolver to determine the actual path of the theme and of the CSS files used by a theme.
 */
abstract class ThemeResolverBase{
    private $placeholderVariables = array();

    public function __construct(){
        $this->placeholderVariables = array(
            'YUI'         => \RightNow\Utils\Url::getYUICodePath(),
            'CORE_ASSETS' => \RightNow\Utils\Url::getCoreAssetPath(),
        );
    }

    /**
     * Determines the actual path of the theme.
     * @param object $theme An instance of the Theme class.
     * @return string A string containing the absolute path of the theme.
     * @throws ThemeException If no matching theme was found.
     */
    public function resolvePath($theme) {
        $resolvedPath = $this->resolvePathInternal($theme);
        if (!$resolvedPath) {
            $internalFullPath = $this->getInternalPath($theme);
            $internalPath = Text::getSubstringAfter($internalFullPath, HTMLROOT, $internalFullPath);
            $errorMessage = ($internalPath === $theme->getParsedPath())
                ? sprintf(Config::getMessage(PCT_S_PCT_S_TG_SPCIFIES_PATH_PCT_MSG), $theme->getTagName(), $theme->getParsedPath())
                : sprintf(Config::getMessage(PCT_S_PCT_S_TG_SPCFIES_PATH_PCT_MSG), $theme->getTagName(), $theme->getParsedPath(), $internalPath);

            throw new ThemeException($theme, $errorMessage);
        }
        return $resolvedPath;
    }

    /**
     * Determines the actual path of the theme's site CSS files.
     * @param object $theme An instance of the Theme class.
     * @return string A string containing the absolute path of the theme.
     * @throws ThemeException If no matching CSS file was found.
     */
    public function resolveCss($theme) {
        $resolvedCssPaths = array();
        if ($theme->getParsedCss()) {
            $cssFiles = array_map('trim', explode(',', $theme->getParsedCss()));
            foreach ($cssFiles as $cssFile) {
                if (strlen($cssFile) === 0) {
                    continue;
                }
                // Do not allow them to use parent directory notation
                if (Text::stringContains($cssFile, '../') !== false) {
                    throw new ThemeException($theme, sprintf(Config::getMessage(PCT_S_PCT_S_ATTRB_PCT_S_TAG_MSG), Theme::CSS_ATTRIBUTE, $theme->getParsedTag(), $cssFile));
                }
                $resolvedCssFile = $this->resolveCssFile($theme, $this->performVariableSubstitution($cssFile));
                if (!$resolvedCssFile) {
                    throw new ThemeException($theme, sprintf(Config::getMessage(PCT_S_PCT_S_TAG_SPECIFIES_PCT_S_MSG),
                        $theme->getTagName(), Theme::CSS_ATTRIBUTE, $cssFile));
                }
                $resolvedCssPaths[]= $resolvedCssFile;
            }
        }
        return array_unique($resolvedCssPaths);
    }

    /**
     * Called by resolvePath to do the actual work.
     * @param object $theme An instance of the Theme class.
     * @return string A string containing the absolute path or false if no such file was found.
     */
    abstract protected function resolvePathInternal($theme);

    /**
     * Called by resolveCss to resolve a single file.
     * @param object $theme An instance of the Theme class.
     * @param string $cssFile A string containing one of the comma separated values from the rn:theme tag's css attribute.
     * @return string A string with the absolute path to the file or false if no such file was found.
     */
    abstract protected function resolveCssFile($theme, $cssFile);

    /**
     * Replaces any of our special placeholder variables in the path.
     * @param  String $path Path to CSS file
     * @return String       Path with variables replaced, or untouched
     */
    private function performVariableSubstitution($path) {
        foreach ($this->placeholderVariables as $key => $value) {
            $inPath = '{' . $key . '}/';
            if (Text::beginsWith($path, $inPath)) {
                $path = $value . Text::getSubstringAfter($path, $inPath);
            }
        }

        return $path;
    }
}

/**
 * This is the default case. It looks for theme files in /euf/assets/ and requires that files exist.
 */
final class NormalThemeResolver extends ThemeResolverBase {
    protected function resolvePathInternal($theme) {
        $resolvedPath = $this->getInternalPath($theme);
        if (!$resolvedPath || !FileSystem::isReadableDirectory($resolvedPath)) {
            return false;
        }
        return $resolvedPath;
    }

    protected function getInternalPath($theme) {
        return HTMLROOT . $theme->getParsedPath();
    }

    protected function resolveCssFile($theme, $cssFile) {
        if (Text::beginsWith($cssFile, '/')) {
            $resolvedCssFile = HTMLROOT . $cssFile;
            if ($resolvedCssFile && FileSystem::isReadableFile($resolvedCssFile)) {
                return $resolvedCssFile;
            }
        }
        $resolvedCssFile = "{$theme->getResolvedPath()}/$cssFile";
        if ($resolvedCssFile && FileSystem::isReadableFile($resolvedCssFile)) {
            return $resolvedCssFile;
        }
        return false;
    }
}

/**
 * This is a resolver for rendering reference mode. Because the user may have deleted copies of the
 * originals and because I don't want to go to the trouble of fixing all the base paths,
 * it can't check if the files exist.
 *
 * Consequently, it just follows the same rules as the normal resolver without requiring files to
 * exist.
 */
final class ReferenceModeThemeResolver extends ThemeResolverBase {
    protected function resolvePathInternal($theme) {
        return HTMLROOT . $theme->getParsedPath();
    }

    protected function resolveCssFile($theme, $cssFile) {
        if (Text::beginsWith($cssFile, '/')) {
            return HTMLROOT . $cssFile;
        }
        return "{$theme->getResolvedPath()}/$cssFile";
    }
}

/**
 * When deploying during a service pack or staging, we need to pull the theme files from
 * the last successful deploy as opposed to the ones found in /euf/assets/.
 */
class SpecifiedHtmlRootThemeResolver extends ThemeResolverBase {
    protected $htmlRoot;

    /**
     * Creates an instance of the SpecifiedHtmlRootThemeResolver class
     * @param string $htmlRoot A string containing the absolute path to the equivalent of HTMLROOT/euf/assets/ for this context.
     */
    public function __construct($htmlRoot) {
        parent::__construct();
        assert(is_string($htmlRoot));
        assert(strlen($htmlRoot) > 1);
        assert(Text::beginsWith($htmlRoot, '/'));
        assert(!Text::endsWith($htmlRoot, '/'));
        $this->htmlRoot = $htmlRoot;
    }

    protected function resolvePathInternal($theme) {
        $resolvedPath = $this->getInternalPath($theme);
        if (FileSystem::isReadableDirectory($resolvedPath)) {
            return $resolvedPath;
        }
        return false;
    }

    protected function getInternalPath($theme) {
        return $this->htmlRoot . '/' . Text::getSubstringAfter($theme->getParsedPath(), Theme::REQUIRED_BASE_PATH);
    }

    protected function resolveCssFile($theme, $cssFile) {
        if (Text::beginsWith($cssFile, '/')) {
            // Check if it was an absolute path within /euf/assets
            if (Text::beginsWith($cssFile, '/euf/assets/')) {
                $resolvedPath = $this->htmlRoot . '/' . Text::getSubstringAfter($cssFile, '/euf/assets/');
                if (FileSystem::isReadableFile($resolvedPath)) {
                    return $resolvedPath;
                }
                //Fix applied to resolve the another theme css path issue, when used through absolute path within /euf/assets
                if (Text::stringContains($this->htmlRoot, '/optimized/')) {
                    return $resolvedPath;
                }
                return false;
            }

            // Any other absolute paths don't get special attention; we'll just
            // try to resolve them from the docroot.
            $resolvedPath = HTMLROOT . $cssFile;
            if (FileSystem::isReadableFile($resolvedPath)) {
                return $resolvedPath;
            }
            return false;
        }

        // Check if it's a regular relative path.
        $resolvedPath = "{$theme->getResolvedPath()}/$cssFile";
        if (FileSystem::isReadableFile($resolvedPath)) {
            return $resolvedPath;
        }

        return false;
    }
}

/**
 * When we upgrade, we need to pull theme files from the last successful deploy,
 * like the service pack; however, in addition, we need to consider files that
 * are new in the distribution. The latter handles a case like adding a new
 * page with a new widget which needs to deploy successfully at upgrade.
 */
final class UpgradeThemeResolver extends SpecifiedHtmlRootThemeResolver {
    /**
     * Creates an instance of the UpgradeThemeResolver class
     * @param string $htmlRoot A string containing the absolute path to the equivalent of HTMLROOT/euf/assets/ for this context.
     * @param array $newFiles An array of files which are new in the version keyed by their path relative to HTMLROOT/euf/assets/default with values of the absolute path to that file.
     */
    public function __construct($htmlRoot, array $newFiles) {
        parent::__construct($htmlRoot);
        $this->newFiles = $newFiles;
    }
    private $newFiles;

    protected function resolvePathInternal($theme) {
        $path = parent::resolvePathInternal($theme);

        $relativeTheme = Text::getSubstringAfter($theme->getParsedPath(), Theme::REQUIRED_BASE_PATH);
        if (array_key_exists($relativeTheme, $this->newFiles) && FileSystem::isReadableDirectory($this->newFiles[$relativeTheme]))
        {
            if ($path)
                $theme->extraThemePath = $this->newFiles[$relativeTheme];
            else
                $path = $this->newFiles[$relativeTheme];
        }

        return $path;
    }

    protected function resolveCssFile($theme, $cssFile) {
        $path = parent::resolveCssFile($theme, $cssFile);
        if ($path)
            return $path;

        if (Text::beginsWith($cssFile, '/')) {
            $path = FileSystem::normalizePath(HTMLROOT . $cssFile);
        }
        else {
            $path = FileSystem::normalizePath(HTMLROOT . $themePath . '/' . $cssFile);
        }

        if (!$path || !Text::beginsWith($path, HTMLROOT . '/euf/assets/')) {
            return false;
        }

        $path = Text::getSubstringAfter($path, HTMLROOT . '/euf/assets/');
        if (!array_key_exists($path, $this->newFiles) || !FileSystem::isReadableFile($this->newFiles[$path])) {
            return false;
        }

        return $this->newFiles[$path];
    }
}

/**
 * Finds the actual path to the file specified by a widget's rn:meta tag's presentation_css attribute.
 */
abstract class WidgetPresentationCssResolverBase{

    public function __construct(){}

    /**
     * Finds the actual path to the file specified by a widget's rn:meta tag's presentation_css attribute.
     * @param string $themePath The absolute, resolved path to the theme directory.
     * @param string $presentationCssAttribute A string containing the value from a widget's rn:meta tag's presentation_css attribute.
     */
    abstract function resolve($themePath, $presentationCssAttribute);

    /**
     * The deployer needs to know what CSS files outside a theme were used so that
     * it can make sure they're backed up in the production sandbox. This
     * allows a callback for that information to be set. That allows the deployer
     * to learn what it needs without placing a dependency on the deployer in
     * this class. It's kind of a dirty hack, but it's for the greater good.
     * @param array $extraThemeFileCallback Callback to use for extra theme files
     */
    public function setExtraThemeFileCallback(array $extraThemeFileCallback) {
        assert(is_object($extraThemeFileCallback[0]));
        assert(is_string($extraThemeFileCallback[1]));
        $this->extraThemeFileCallback = $extraThemeFileCallback;
    }

    private $extraThemeFileCallback;

    /**
     * Calls the callback which reports that a file outside of a theme was found.
     * @param string $sourcePath Path to source file
     * @param string $destinationPath Path to destination file
     */
    protected function callExtraThemeFileCallback($sourcePath, $destinationPath) {
        if (isset($this->extraThemeFileCallback)) {
            call_user_func($this->extraThemeFileCallback, $sourcePath, $destinationPath);
        }
    }
}

/**
 * Looks for presentation CSS files in the standard HTMLROOT.
 */
final class NormalWidgetPresentationCssResolver extends WidgetPresentationCssResolverBase {
    public function resolve($themePath, $presentationCssAttribute) {
        if (Text::beginsWith($presentationCssAttribute, '/'))
            $cssPath = HTMLROOT . $presentationCssAttribute;
        else
            $cssPath = HTMLROOT . $themePath . '/' . $presentationCssAttribute;

        if (FileSystem::isReadableFile($cssPath)) {
            $realThemePath = '';
            //Only realpath the theme and CSS paths if we're hosted. Because of
            //symlinks set in development, this will screw them up
            if(IS_HOSTED)
            {
                $cssPath = realpath($cssPath);
                $realThemePath = realpath(HTMLROOT . $themePath) . '/';
            }
            if (!Text::beginsWith($cssPath, $realThemePath)) {
                $realEufAssets = realpath(HTMLROOT . '/euf/assets') . '/';
                $this->callExtraThemeFileCallback($cssPath, Text::getSubstringAfter($cssPath, $realEufAssets));
            }
        }
        return $cssPath;
    }
}

/**
 * Looks for presentation CSS files in the production sandbox of the HTMLROOT.
 */
class SpecifiedHtmlRootWidgetPresentationCssResolver extends WidgetPresentationCssResolverBase {
    /**
     * Constructor
     * @param array $resolvedThemePaths Array keyed by relative theme path with values of absolute resolved theme paths.
     * @param string $htmlRoot The path to the current optimized asset timestamp directory, i.e. the production sandbox of the HTMLROOT.
     */
    public function __construct(array $resolvedThemePaths, $htmlRoot) {
        parent::__construct();
        $this->resolvedThemePaths = $resolvedThemePaths;
        $this->htmlRoot = realpath($htmlRoot);
    }

    protected $resolvedThemePaths;
    protected $htmlRoot;

    public function resolve($themePath, $presentationCssAttribute) {
        assert(Text::beginsWith($themePath, Theme::REQUIRED_BASE_PATH));
        if (Text::beginsWith($presentationCssAttribute, '/')) {
            if (!Text::beginsWith($presentationCssAttribute, '/euf/assets/')) {
                // Any other absolute paths don't get special attention; we'll just
                // try to resolve them from the docroot.
                $realCssPath = realpath(HTMLROOT . $presentationCssAttribute);
                return $realCssPath && FileSystem::isReadableFile($realCssPath);
            }
            $realCssPath = realpath($this->htmlRoot . '/' . Text::getSubstringAfter($presentationCssAttribute, '/euf/assets/'));
        }
        else {
            $realCssPath = realpath("{$this->resolvedThemePaths[$themePath]}/$presentationCssAttribute");
        }

        if (!$realCssPath || !FileSystem::isReadableFile($realCssPath)) {
            return false;
        }

        $realThemePath = realpath($this->resolvedThemePaths[$themePath]) . '/';
        if (!Text::beginsWith($realCssPath, $realThemePath)) {
            $this->callExtraThemeFileCallback($realCssPath, Text::getSubstringAfter($realCssPath, "{$this->htmlRoot}/"));
        }

        return $realCssPath;
    }
}

/**
 * Looks for presentation CSS files relative to the production sandbox of the HTMLROOT and in the new default files in this release.
 */
final class UpgradeWidgetPresentationCssResolver extends SpecifiedHtmlRootWidgetPresentationCssResolver {
    /**
     * Constructor
     * @param array $resolvedThemePaths Array keyed by relative theme path with values of absolute resolved theme paths.
     * @param string $htmlRoot The path to the current optimized asset timestamp directory, i.e. the production sandbox of the HTMLROOT.
     * @param array $newFiles An array of files which are new in the version keyed by their path relative to HTMLROOT/euf/assets/default with values of the absolute path to that file.
     */
    public function __construct(array $resolvedThemePaths, $htmlRoot, array $newFiles) {
        parent::__construct($resolvedThemePaths, $htmlRoot);
        $this->newFiles = $newFiles;
    }
    private $newFiles;

    public function resolve($themePath, $presentationCssAttribute) {
        $path = parent::resolve($themePath, $presentationCssAttribute);
        if ($path)
            return $path;

        if (Text::beginsWith($presentationCssAttribute, '/')) {
            $path = FileSystem::normalizePath(HTMLROOT . $presentationCssAttribute);
        }
        else {
            $path = FileSystem::normalizePath(HTMLROOT . $themePath . '/' . $presentationCssAttribute);
        }

        if (!$path || !Text::beginsWith($path, HTMLROOT . '/euf/assets/')) {
            return false;
        }

        $path = Text::getSubstringAfter($path, HTMLROOT . '/euf/assets/');
        if (!array_key_exists($path, $this->newFiles) || !FileSystem::isReadableFile($this->newFiles[$path])) {
            return false;
        }

        $this->callExtraThemeFileCallback($this->newFiles[$path], $path);
        return $this->newFiles[$path];
    }
}
