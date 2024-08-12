<?

namespace RightNow\Libraries\Widget;

use RightNow\Utils\Url,
    RightNow\Utils\Text;

/**
 * Base view helper assigned to every widget.
 */
#[\AllowDynamicProperties]
class Helper {

    /**
     * Escapes $value.
     * @param  string  $value        String to escape
     * @param bool $doubleEncode Whether to encode existing html entities
     * @return mixed Escaped string or original unmodified value if not a string.
     */
    function escape ($value, $doubleEncode = true) {
        return Text::escapeHtml($value, $doubleEncode);
    }

    /**
     * Adds a session parameter onto $url.
     * If the current user has disabled browser cookies
     * then this parameter is required on url in order
     * to maintain the same user session.
     * @param  string $url URL
     * @return string      $url URL with a session parameter
     *                          appended if there is
     *                          no session cookie.
     */
    function appendSession ($url) {
        return $url . Url::sessionParameter();
    }
}
