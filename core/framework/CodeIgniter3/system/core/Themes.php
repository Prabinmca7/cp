<?
/**
 * Selects which theme a page will be rendered with.
 */
class Themes {
    const standardThemePath = '/euf/assets/themes/standard';
    const mobileThemePath = '/euf/assets/themes/mobile';
    const basicThemePath = '/euf/assets/themes/basic';

    private $allowSettingTheme = true;
    private $theme;
    private $themePath;
    private $availableThemes;

    /**
     * Returns reference path to standard theme
     * @return string
     */
    public static function getReferenceThemePath() {
        return self::getSpecificReferencePath('standard');
    }

    /**
     * Returns reference path to mobile theme
     * @return string
     */
    public static function getReferenceMobileThemePath() {
        return self::getSpecificReferencePath('mobile');
    }

    /**
     * Returns reference path to basic theme
     * @return string
     */
    public static function getReferenceBasicThemePath() {
        return self::getSpecificReferencePath('basic');
    }

    /**
     * This function is intended for use by the Customer Portal framework.
     * @private
     */
    public function disableSettingTheme() {
        $this->allowSettingTheme = false;
    }

    /**
     * Selects which theme will be used.  Must be called in a pre_page_render hook.
     * @param $theme A string containing the value of the path attribute of an
     * rn:theme tag present in the page or template.
     */
    public function setTheme($theme)
    {
        if (!$this->allowSettingTheme) {
            if (IS_OPTIMIZED) {
                // Silently fail in production or staging.
                return;
            }
            throw new Exception(\RightNow\Utils\Config::getMessage(ATTEMPTED_SET_THEME_PRE_PG_RENDER_MSG));
        }

        if (!array_key_exists($theme, $this->availableThemes))
        {
            $availableThemes = $this->getAvailableThemes();
            if (count($availableThemes) > 0) {
                $message = sprintf(\RightNow\Utils\Config::getMessage(ATTEMPTED_SET_THEME_PCT_S_DECLARED_MSG), $theme);
                $message .= "<ul>";
                foreach ($availableThemes as $availableTheme) {
                    $message .= "<li>$availableTheme</li>";
                }
                $message .= "</ul>";
            }
            else {
                $message = sprintf(\RightNow\Utils\Config::getMessage(ATTEMPTED_SET_THEME_PCT_S_RN_THEME_MSG), $theme);
            }
            throw new Exception($message);
        }

        $this->theme = $theme;
        $this->themePath = $this->availableThemes[$theme];
    }

    /**
     * Gets the currently selected theme.
     *
     * The default value is the first theme declared on the page or, if the
     * page has no theme declared, the first theme on the template.  If no
     * rn:theme tag is present on the page or template, then the default is
     * '/euf/assets/themes/standard'.
     *
     * @returns A string containing the currently selected theme.
     */
    public function getTheme()
    {
        return $this->theme;
    }

    /**
     * Gets the URL path that the selected theme's assets are served from.
     *
     * The returned value does not include the URL's protocol or hostname.  In
     * development mode, this value will be the same as getTheme(); however, it
     * will differ in production mode.  On the filesystem, this path is
     * relative to the HTMLROOT define.
     *
     * @returns A string containing the URL path that the selected theme's assets are served from.
     */
    public function getThemePath()
    {
        return $this->themePath;
    }

    /**
     * Lists the themes which were declared on the page or template.
     *
     * Values returned are similar to getTheme().
     *
     * @returns An array of strings containing the value of path attribute of the rn:theme tags on the page and template.
     */
    public function getAvailableThemes()
    {
        return array_keys($this->availableThemes);
    }

    /**
     * This function is intended for use by the Customer Portal framework.
     * @private
     */
    public function setRuntimeThemeData($runtimeThemeData)
    {
        assert(is_string($runtimeThemeData[0]));
        assert(is_string($runtimeThemeData[1]));
        assert(is_array($runtimeThemeData[2]));
        list($this->theme, $this->themePath, $this->availableThemes) = $runtimeThemeData;
    }

    /**
     * Utility method to retrieve path to reference mode theme, provided the theme name
     * @param string $themeName Name of theme to retrieve
     * @return string Path to reference theme assets
     */
    private static function getSpecificReferencePath($themeName){
        $localThemeVariable = "{$themeName}ThemePath";
        return IS_HOSTED ? '/euf/core/' . CP_FRAMEWORK_VERSION . "/default/themes/$themeName" : constant("self::{$localThemeVariable}");
    }
}
// This file needs a line at the end because createCoreCodeIgniter.sh removes the first and last line of the file.
