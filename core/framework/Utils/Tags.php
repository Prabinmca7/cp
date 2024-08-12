<?php
namespace RightNow\Utils;

/**
 * Methods for handling generic HTML tags.
 */
final class Tags extends \RightNow\Internal\Utils\Tags
{
    /**
     * Gets the page title out of the page meta information.
     *
     * @return string The title of the page
     * @internal
     */
    public static function getPageTitleAtRuntime() {
        $CI = get_instance();
        $meta = (method_exists($CI, '_getMetaInformation')) ? $CI->_getMetaInformation() : array();
        if (array_key_exists('title', $meta)) {
            if (IS_OPTIMIZED) {
                return $meta['title'];
            }

            // This seemingly needlessly complicated bit handles the case of the title attribute containing text before the #rn:msg# tag.
            return Framework::evalCodeAndCaptureOutput("<?= '{$meta['title']}'; ?>");
        }
        return Config::getMessage(NO_TITLE_LBL);
    }

    /**
     * Makes a CSS link tag with the specified URL.
     *
     * @param string $url URL to put in the tag.
     * @return string A CSS link tag with the specified URL.
     */
    public static function createCssTag($url) {
        return "<link href='$url' rel='stylesheet' type='text/css' media='all' />";
    }

    /**
     * Creates JS include tag given the path to the file
     *
     * @param string $path The file system path
     * @param string $attributes Additional script tag attributes
     * @return string The formed javascript load tag
     */
    public static function createJSTag($path, $attributes = ''){
        return "<script src='$path'" . ($attributes ? str_replace('"', "'", " $attributes") : '') . "></script>";
    }
}