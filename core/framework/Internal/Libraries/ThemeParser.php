<?php
namespace RightNow\Internal\Libraries;

use RightNow\Utils\Text,
    RightNow\Utils\Config;

require_once CPCORE . 'Internal/Libraries/Resolvers.php';
final class ThemeParser{
    private static $themes;
    private static $CI = false;
    /**
     * Finds all of the &lt;rn:theme&gt; tags in $content and returns an array of Theme objects.
     * @param string $content HTML containing 0 or more &lt;rn:theme&gt; tags.
     * @return array An array of Theme objects.
     */
    public static function parse($content) {
        preg_match_all(\RightNow\Utils\Tags::getThemeTagPattern(), $content, $matches, PREG_SET_ORDER);
        $matchesWithoutComments = array();
        foreach ($matches as $match) {
            if (!Text::beginsWith($match[0], '<!')) {
                array_push($matchesWithoutComments, $match);
            }
        }
        return array_map(array('\RightNow\Internal\Libraries\ThemeParser', 'parseThemeFromMatch'), $matchesWithoutComments);
    }

    /**
     * Finds rn:theme tags in $content, validates each, and merges tags with the same path.
     * @param string $content HTML containing 0 or more &lt;rn:theme&gt; tags.
     * @param string $contentPath Any validation error message will include this as the content description.
     * @param object $resolver Resolves theme paths based on the execution context/kind of deployment.
     * @return array An array of Theme objects or an error message if validation failed.
     */
    public static function parseAndValidate($content, $contentPath, $resolver=null) {
        if ($resolver === null) {
            $resolver = new NormalThemeResolver();
        }
        $themes = self::parse($content);
        foreach ($themes as $theme) {
            try {
                $theme->validate($resolver);
            }
            catch (ThemeException $ex) {
                return sprintf($ex->getMessage(), $contentPath);
            }
        }
        return self::reduceThemeArray($themes);
    }

    public static function reduceThemeArray($themes) {
        $newThemes = array();
        foreach ($themes as $theme) {
            if (!array_key_exists($theme->getParsedPath(), $newThemes)) {
                $newThemes[$theme->getParsedPath()] = $theme;
            }
            else {
                $newThemes[$theme->getParsedPath()]->mergeCssPaths($theme);
            }
        }
        return $newThemes;
    }

    /**
     * Calculates the default and all available themes for page and template.
     * @param array|null $pageThemes An array of Theme object parsed from the page.
     * @param array|null $templateThemes An array of Theme object parsed from the template.
     * @return array An array containing (defaultTheme, defaultThemePath, an array of all the declared themes keyed by theme with a value of theme path)
     */
    public static function convertListOfThemesToRuntimeInformation($pageThemes, $templateThemes) {
        $defaultTheme = false;
        if (is_array($pageThemes) && count($pageThemes) > 0) {
            reset($pageThemes);
            $defaultTheme = current($pageThemes); // Gets the first theme that was added to the array.
        }
        else if (is_array($templateThemes) && count($templateThemes) > 0) {
            reset($templateThemes);
            $defaultTheme = current($templateThemes); // Gets the first theme that was added to the array.
        }
        $availableThemes = array();
        foreach (array($pageThemes, $templateThemes) as $themeSet) {
            if (is_array($themeSet)) {
                foreach ($themeSet as $themePath => $theme) {
                    $availableThemes[$themePath] = $themePath;
                }
            }
        }
        if ($defaultTheme) {
            return array($defaultTheme->getParsedPath(), $defaultTheme->getParsedPath(), $availableThemes);
        }
        return array('', \Themes::standardThemePath, array());
    }

    public static function convertListOfThemesToResolvedThemePaths($pageThemes, $templateThemes) {
        $resolvedThemePaths = array();
        foreach (array($templateThemes, $pageThemes) as $themeSet) {
            if (!is_array($themeSet))
                continue;
            foreach ($themeSet as $themePath => $theme) {
                if (array_key_exists($themePath, $resolvedThemePaths)) {
                    if ($resolvedThemePaths[$themePath] !== $theme->getResolvedPath()) {
                        throw new \Exception("The resolved path for the same theme path differed between two theme instances.  The resolved paths were {$theme->getResolvedPath()} and {$resolvedThemePaths[$themePath]}");
                    }
                    continue;
                }
                $resolvedThemePaths[$themePath] = $theme->getResolvedPath();
            }
        }
        return $resolvedThemePaths;  //Somewhere I'm going to have to make sure that a page without themes gets the standard theme as a default.
    }

    public static function getCssPathsForTheme($selectedThemePath, $pageThemes, $templateThemes) {
        $cssFiles = array();
        foreach (array($templateThemes, $pageThemes) as $themeSet) {
            if (is_array($themeSet)) {
                foreach ($themeSet as $themePath => $theme) {
                    if ($selectedThemePath === $themePath) {
                        $cssPaths = $theme->getResolvedCssPaths();
                        if (is_array($cssPaths)) {
                            foreach ($cssPaths as $cssPath) {
                                $cssFile = Text::getSubstringAfter($cssPath, HTMLROOT);
                                if ($cssFile) {
                                    $cssFiles[] = $cssFile;
                                }
                            }
                        }
                    }
                }
            }
        }
        return $cssFiles;
    }

    /**
     * The reference pages say that they want the standard themes, but they really don't; they want the reference copy.
     * This lets us be lazy and not modify the reference copy of the pages to say that they need something else.
     * We just figure it out at runtime.
     * @param array $themes List of themes
     * @param string $contentPath Path to content
     * @throws \Exception If a theme exists that we don't have a reference copy of
     */
    public static function translateThemesToReferenceEquivalent(array $themes, $contentPath) {
        // Yes creating this big array every time the function is called is inefficient,
        // but I don't care because doing it this way allows me to avoid creating it most
        // of the time, since this function is rarely called.
        $mobileThemePath = \Themes::getReferenceMobileThemePath();
        $standardThemePath = \Themes::getReferenceThemePath();
        $basicThemePath = \Themes::getReferenceBasicThemePath();
        $yuiPath = HTMLROOT . \RightNow\Utils\Url::getYUICodePath();
        $coreAssetPath = HTMLROOT . \RightNow\Utils\Url::getCoreAssetPath();

        $mapping = array(
            \Themes::mobileThemePath => Theme::__set_state(array(
                'parsedPath' => $mobileThemePath,
                'resolvedPath' => HTMLROOT . $mobileThemePath,
                'parsedCss' => 'site.css',
                'resolvedCssPaths' => array(
                    HTMLROOT . $mobileThemePath . '/site.css'
                ),
            )),
            \Themes::basicThemePath => Theme::__set_state(array(
                'parsedPath' => $basicThemePath,
                'resolvedPath' => HTMLROOT . $basicThemePath,
                'parsedCss' => 'site.css',
                'resolvedCssPaths' => array(
                    HTMLROOT . $basicThemePath . '/site.css'
                ),
            )),
            \Themes::standardThemePath => Theme::__set_state(array(
                'parsedPath' => $standardThemePath,
                'resolvedPath' => HTMLROOT . $standardThemePath,
                'parsedCss' => 'site.css',
                'resolvedCssPaths' => array(
                    HTMLROOT . $standardThemePath . '/site.css'
                ),
            )),
        );

        $newArray = array();
        foreach ($themes as $path => $theme) {
            if (!array_key_exists($path, $mapping)) {
                throw new \Exception("You're in reference mode but '$contentPath' contained a reference to a theme ('$path') for which we don't have a reference copy.  We know about: (" . implode(', ', (array_keys($mapping))) . ")");
            }
            $theme = $mapping[$path];
            $newArray[$theme->getParsedPath()] = $theme;
        }
        return $newArray;
    }

    /**
     * Method to load okcs reference themes
     * @param array $themes List of themes
     * @param string $contentPath Path to content
     * @throws \Exception If a theme exists that we don't have a reference copy of
     */
    public static function translateThemesToKAReferenceEquivalent(array $themes, $contentPath) {
        $mobileThemePath = \Themes::getReferenceMobileThemePath();
        $standardThemePath = \Themes::getReferenceThemePath();
        $basicThemePath = \Themes::getReferenceBasicThemePath();
        $yuiPath = HTMLROOT . \RightNow\Utils\Url::getYUICodePath();
        $coreAssetPath = HTMLROOT . \RightNow\Utils\Url::getCoreAssetPath();

        $mapping = array(
            \Themes::mobileThemePath => Theme::__set_state(array(
                'parsedPath' => $mobileThemePath,
                'resolvedPath' => HTMLROOT . $mobileThemePath,
                'parsedCss' => 'site.css',
                'resolvedCssPaths' => array(
                    HTMLROOT . $mobileThemePath . '/site.css',
                    HTMLROOT . $mobileThemePath . '/okcs.css',
                    HTMLROOT . $mobileThemePath . '/intent.css'
                ),
            )),
            \Themes::basicThemePath => Theme::__set_state(array(
                'parsedPath' => $basicThemePath,
                'resolvedPath' => HTMLROOT . $basicThemePath,
                'parsedCss' => 'site.css',
                'resolvedCssPaths' => array(
                    HTMLROOT . $basicThemePath . '/site.css'
                ),
            )),
            \Themes::standardThemePath => Theme::__set_state(array(
                'parsedPath' => $standardThemePath,
                'resolvedPath' => HTMLROOT . $standardThemePath,
                'parsedCss' => 'site.css',
                'resolvedCssPaths' => array(
                    HTMLROOT . $standardThemePath . '/site.css',
                    HTMLROOT . $standardThemePath . '/okcs.css',
                    HTMLROOT . $standardThemePath . '/okcs_search.css',
                    HTMLROOT . $standardThemePath . '/intent.css'
                ),
            )),
        );

        $newArray = array();
        foreach ($themes as $path => $theme) {
            if (!array_key_exists($path, $mapping)) {
                throw new \Exception("You're in reference mode but '$contentPath' contained a reference to a theme ('$path') for which we don't have a reference copy.  We know about: (" . implode(', ', (array_keys($mapping))) . ")");
            }
            $theme = $mapping[$path];
            $newArray[$theme->getParsedPath()] = $theme;
        }
        return $newArray;
    }

    private static function parseThemeFromMatch($matches) {
        $css = false;
        $path = false;
        foreach (\RightNow\Utils\Tags::getHtmlAttributes($matches[0]) as $attribute) {
            if (Theme::PATH_ATTRIBUTE === $attribute->attributeName) {
                $path = $attribute->attributeValue;
            }
            else if (Theme::CSS_ATTRIBUTE === $attribute->attributeName) {
                $css = $attribute->attributeValue;
            }
        }
        return new Theme($matches[0], $path, $css);
    }
}

final class Theme{
    /**
     * Parsed theme tag
     */
    private $parsedTag;

    /**
     * Parsed path to theme
     */
    private $parsedPath;

    /**
     * Resolved path to theme
     */
    private $resolvedPath;

    /**
     * Parsed CSS to include with theme
     */
    private $parsedCss;

    /**
     * Resolved paths to CSS to include with theme
     */
    private $resolvedCssPaths;

    const PATH_ATTRIBUTE = 'path';
    const CSS_ATTRIBUTE = 'css';
    const REQUIRED_BASE_PATH = '/euf/assets/';

    public function __construct($tag = null, $path = null, $css = null) {
        $this->parsedTag = $tag;
        $this->parsedPath = $path;
        $this->parsedCss = $css;
    }

    public function getParsedTag() {
        return $this->parsedTag;
    }
    public function getTagName() {
        return Text::getSubstringBefore(Text::getSubstringAfter($this->parsedTag, '<'), ' ');
    }
    public function getParsedPath() {
        return $this->parsedPath;
    }
    public function getParsedCss() {
        return $this->parsedCss;
    }
    public function getResolvedPath() {
        return $this->resolvedPath;
    }
    public function getResolvedCssPaths() {
        return $this->resolvedCssPaths;
    }

    public function validate(ThemeResolverBase $resolver) {
        if (!is_a($resolver, '\RightNow\Internal\Libraries\ThemeResolverBase'))
            throw new \Exception('Expected ThemeResolverBase.');

        if (!$this->getParsedPath()) {
            throw new ThemeException($this, sprintf(Config::getMessage(PCT_S_PCT_S_TAG_MISSING_REQD_PCT_S_MSG), $this->getParsedTag(), self::PATH_ATTRIBUTE));
        }
        $this->parsedPath = trim($this->parsedPath);
        if ((!Text::beginsWith($this->getParsedPath(), self::REQUIRED_BASE_PATH) &&
            !Text::beginsWith($this->getParsedPath(), \RightNow\Utils\Url::getCoreAssetPath())) ||
            $this->getParsedPath() === self::REQUIRED_BASE_PATH ||
            Text::stringContains($this->getParsedPath(), '/..')) {
            throw new ThemeException($this, sprintf(Config::getMessage(PCT_S_PCT_S_TAG_SPECIFIES_PCT_S_PCT_MSG),
                $this->getTagName(), self::PATH_ATTRIBUTE, $this->getParsedPath(), self::REQUIRED_BASE_PATH));
        }
        if (Text::endsWith($this->getParsedPath(), '/')) {
            throw new ThemeException($this, sprintf(Config::getMessage(PCT_S_PCT_S_ATTRIB_PCT_S_TAG_END_MSG), self::PATH_ATTRIBUTE, $this->getParsedTag(), self::REQUIRED_BASE_PATH));
        }

        $this->resolvedPath = $resolver->resolvePath($this);
        $this->resolvedCssPaths = $resolver->resolveCss($this);
    }

    public function mergeCssPaths($theme) {
        $this->resolvedCssPaths = array_unique(array_merge($this->resolvedCssPaths, $theme->resolvedCssPaths));
    }

    public static function __set_state($members) {
        $x = new Theme();
        foreach ($members as $member => $value) {
            $x->$member = $value;
        }
        return $x;
    }
}

final class ThemeException extends \RightNow\Internal\Exception {
    protected $theme;
    public function __construct($theme, $message) {
        parent::__construct($message);
        $this->theme = $theme;
    }
    public function getTheme() {
        return $theme;
    }
}
// @codingStandardsIgnoreStart
/**
 * Class Minify_CSS_UriRewriter
 * @package Minify
 * From http://code.google.com/p/minify
 * License:
 *
 * Copyright (c) 2008 Ryan Grove <ryan@wonko.com>
 * Copyright (c) 2008 Steve Clay <steve@mrclay.org>
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 *   * Redistributions of source code must retain the above copyright notice,
 *     this list of conditions and the following disclaimer.
 *   * Redistributions in binary form must reproduce the above copyright notice,
 *     this list of conditions and the following disclaimer in the documentation
 *     and/or other materials provided with the distribution.
 *   * Neither the name of this project nor the names of its contributors may be
 *     used to endorse or promote products derived from this software without
 *     specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR
 * ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON
 * ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */

/**
 * Rewrite file-relative URIs as root-relative in CSS files
 *
 * @package Minify
 * @author Stephen Clay <steve@mrclay.org>
 */
class Minify_CSS_UriRewriter{
    /**
     * Defines which class to call as part of callbacks, change this
     * if you extend Minify_CSS_UriRewriter
     * @var string
     */
    protected static $className = 'Minify_CSS_UriRewriter';

    /**
     * rewrite() and rewriteRelative() append debugging information here
     * @var string
     */
    protected static $debugText = '';

    /**
     * An array of arrays specifying search and replace patterns to be run in addition to stripping HTMLROOT from asset paths
     * Example: array(array([search1], [replace1]), array([search2], [replace2]),...)
     */
    protected static $replacePatterns = array();

    /**
     * Rewrite file relative URIs as root relative in CSS files
     *
     * @param string $css
     *
     * @param string $currentDir The directory of the current CSS file.
     *
     * @param string $docRoot The document root of the web site in which
     * the CSS file resides (default = $_SERVER['DOCUMENT_ROOT']).
     *
     * @param array $symlinks (default = array()) If the CSS file is stored in
     * a symlink-ed directory, provide an array of link paths to
     * target paths, where the link paths are within the document root. Because
     * paths need to be normalized for this to work, use "//" to substitute
     * the doc root in the link paths (the array keys). E.g.:
     * <code>
     * array('//symlink' => '/real/target/path') // unix
     * array('//static' => 'D:\\staticStorage')  // Windows
     * </code>
     *
     * @return string
     */
    protected static function rewrite($css, $currentDir, $docRoot = null, $symlinks = array())
    {
        self::$docRoot = self::_realpath(
            $docRoot ? $docRoot : $_SERVER['DOCUMENT_ROOT']
        );
        self::$currentDir = self::_realpath($currentDir);
        self::$symlinks = array();

        // normalize symlinks
        foreach ($symlinks as $link => $target) {
            $link = ($link === '//')
                ? self::$docRoot
                : str_replace('//', self::$docRoot . '/', $link);
            $link = strtr($link, '/', DIRECTORY_SEPARATOR);
            self::$symlinks[$link] = self::_realpath($target);
        }

        self::$debugText .= "docRoot    : " . self::$docRoot . "\n"
                          . "currentDir : " . self::$currentDir . "\n";
        if (self::$symlinks) {
            self::$debugText .= "symlinks : " . var_export(self::$symlinks, 1) . "\n";
        }
        self::$debugText .= "\n";

        $css = self::_trimUrls($css);

        // rewrite
        $css = preg_replace_callback('/@import\\s+([\'"])(.*?)[\'"]/', array('\RightNow\Internal\Libraries\\' . self::$className, '_processUriCB'), $css);
        $css = preg_replace_callback('/url\\(\\s*([^\\)\\s]+)\\s*\\)/', array('\RightNow\Internal\Libraries\\' . self::$className, '_processUriCB'), $css);
        return $css;
    }

    /**
     * Prepend a path to relative URIs in CSS files
     *
     * @param string $css
     *
     * @param string $path The path to prepend.
     *
     * @return string
     */
    protected static function prepend($css, $path)
    {
        self::$prependPath = $path;

        $css = self::_trimUrls($css);

        // append
        $css = preg_replace_callback('/@import\\s+([\'"])(.*?)[\'"]/', array(self::$className, '_processUriCB'), $css);
        $css = preg_replace_callback('/url\\(\\s*([^\\)\\s]+)\\s*\\)/', array(self::$className, '_processUriCB'), $css);

        self::$prependPath = null;
        return $css;
    }

    /**
     * @var string directory of this stylesheet
     */
    private static $currentDir = '';

    /**
     * @var string DOC_ROOT
     */
    private static $docRoot = '';

    /**
     * @var array directory replacements to map symlink targets back to their
     * source (within the document root) E.g. '/var/www/symlink' => '/var/realpath'
     */
    private static $symlinks = array();

    /**
     * @var string path to prepend
     */
    private static $prependPath = null;

    private static function _trimUrls($css)
    {
        return preg_replace('/
            url\\(      # url(
            \\s*
            ([^\\)]+?)  # 1 = URI (assuming does not contain ")")
            \\s*
            \\)         # )
        /x', 'url($1)', $css);
    }

    private static function _processUriCB($m)
    {
        // $m matched either '/@import\\s+([\'"])(.*?)[\'"]/' or '/url\\(\\s*([^\\)\\s]+)\\s*\\)/'
        $isImport = ($m[0][0] === '@');
        // determine URI and the quote character (if any)
        if ($isImport) {
            $quoteChar = $m[1];
            $uri = $m[2];
        } else {
            // $m[1] is either quoted or not
            $quoteChar = ($m[1][0] === "'" || $m[1][0] === '"')
                ? $m[1][0]
                : '';
            $uri = ($quoteChar === '')
                ? $m[1]
                : substr($m[1], 1, strlen($m[1]) - 2);
        }
        // analyze URI
        if ('/' !== $uri[0]                  // root-relative
            && false === strpos($uri, '//')  // protocol (non-data)
            && 0 !== strpos($uri, 'data:')   // data protocol
        ) {
            // URI is file-relative: rewrite depending on options
            $uri = (self::$prependPath !== null)
                ? (self::$prependPath . $uri)
                : self::rewriteRelative($uri, self::$currentDir, self::$docRoot, self::$symlinks);
        }
        return $isImport
            ? "@import {$quoteChar}{$uri}{$quoteChar}"
            : "url({$quoteChar}{$uri}{$quoteChar})";
    }

    /**
     * Rewrite a file relative URI as root relative
     *
     * <code>
     * Minify_CSS_UriRewriter::rewriteRelative(
     *       '../img/hello.gif'
     *     , '/home/user/www/css'  // path of CSS file
     *     , '/home/user/www'      // doc root
     * );
     * // returns '/img/hello.gif'
     *
     * // example where static files are stored in a symlinked directory
     * Minify_CSS_UriRewriter::rewriteRelative(
     *       'hello.gif'
     *     , '/var/staticFiles/theme'
     *     , '/home/user/www'
     *     , array('/home/user/www/static' => '/var/staticFiles')
     * );
     * // returns '/static/theme/hello.gif'
     * </code>
     *
     * @param string $uri file relative URI
     *
     * @param string $realCurrentDir realpath of the current file's directory.
     *
     * @param string $realDocRoot realpath of the site document root.
     *
     * @param array $symlinks (default = array()) If the file is stored in
     * a symlink-ed directory, provide an array of link paths to
     * real target paths, where the link paths "appear" to be within the document
     * root. E.g.:
     * <code>
     * array('/home/foo/www/not/real/path' => '/real/target/path') // unix
     * array('C:\\htdocs\\not\\real' => 'D:\\real\\target\\path')  // Windows
     * </code>
     *
     * @return string
     */
    protected static function rewriteRelative($uri, $realCurrentDir, $realDocRoot, $symlinks = array())
    {
        // prepend path with current dir separator (OS-independent)
        $path = strtr($realCurrentDir, '/', DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR . strtr($uri, '/', DIRECTORY_SEPARATOR);
        self::$debugText .= "file-relative URI  : {$uri}\n"
                          . "path prepended     : {$path}\n";
        // "unresolve" a symlink back to doc root
        foreach ($symlinks as $link => $target) {
            if (0 === strpos($path, $target)) {
                // replace $target with $link
                $path = $link . substr($path, strlen($target));
                self::$debugText .= "symlink unresolved : {$path}\n";
                break;
            }
        }
        // strip doc root as well as any additional replacePatterns
        $patterns = array(array('/^' . str_replace('/', '\/', $realDocRoot) . '/', ''));
        if (self::$replacePatterns) {
            $patterns = array_merge($patterns, self::$replacePatterns);
        }
        foreach ($patterns as $pairs) {
            $path = preg_replace($pairs[0], $pairs[1], $path);
        }
        self::$debugText .= "docroot stripped   : {$path}\n";
        // fix to root-relative URI
        $uri = strtr($path, '/\\', '//');
        // remove /./ and /../ where possible
        $uri = str_replace('/./', '/', $uri);
        return self::removeInnerTraversals($uri);
    }

    private static function removeInnerTraversals($uri) {
        $leadingTraversals = '';
        if (preg_match('@^((?:\.\./)+)@', $uri, $matches)) {
            $leadingTraversals = substr($matches[1], 0, -1);
        }
        // inspired by patch from Oleg Cherniy
        do {
            $uri = preg_replace('@(/[^/]+/|^/?)\.\./@', '/', $uri, 1, $changed);
        } while ($changed);
        self::$debugText .= "traversals removed : {$uri}\n\n";
        return "$leadingTraversals$uri";
    }

    /**
     * Get realpath with any trailing slash removed. If realpath() fails,
     * just remove the trailing slash.
     *
     * @param string $path
     *
     * @return mixed path with no trailing slash
     */
    protected static function _realpath($path)
    {
        $realPath = realpath($path);
        if ($realPath !== false) {
            $path = $realPath;
        }
        return rtrim($path, '/\\');
    }
}

final class CssUrlRewriter extends Minify_CSS_UriRewriter {
    public static function rewrite($css, $currentDir, $docRoot = null, $replacePatterns = array()) {
        if ($docRoot === null) {
            $docRoot = HTMLROOT;
        }
        $symlinks = array();
        if (!IS_HOSTED) {
            foreach (array('/euf', Text::removeTrailingSlash(\RightNow\Utils\Url::getOldYuiCodePath('')), Text::removeTrailingSlash(\RightNow\Utils\Url::getYuiCodePath())) as $symlink) {
                $symlinks[HTMLROOT . $symlink] = realpath(HTMLROOT . $symlink);
            }
        }
        self::$replacePatterns = $replacePatterns;
        return parent::rewrite($css, $currentDir, $docRoot, $symlinks);
    }
}
// @codingStandardsIgnoreEnd
