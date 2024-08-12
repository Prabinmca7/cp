<?

namespace RightNow\Helpers;

use RightNow\Libraries\Formatter,
    RightNow\Utils\Text,
    RightNow\Utils\Url;

class SourceResultListingHelper extends \RightNow\Libraries\Widget\Helper {
    /**
     * Adds the given filters as url parameters on $url.
     * @param  string $url     Base page URL
     * @param  array $filters Search filters to add as params
     * @return string          String Built-up url
     */
    function constructMoreLink($url, $filters) {
        foreach ($filters as $filter) {
            if (isset($filter['key']) && $filter['key'] && $filter['value']) {
                $url = Url::addParameter($url, $filter['key'], $filter['key'] === 'kw' ? urlencode($filter['value']) : $filter['value']);
            }
        }

        return $url . Url::sessionParameter();
    }

    /**
     * Handles text formatting for the search result's summary.
     * @param string $summary Text content
     * @param string|boolean $highlight Set to true to highlight current 'kw' URL parameter
     *                                  Set to false to skip highlighting
     *                                  Set to a string to force highlighting on the given string instead of the current `kw` URL parameter. Useful when 'kw' param is unavailable
     * @param int $truncateSize Number of characters to begin truncation. Sending in 0 will result in no truncation.
     * @return string Formatted text
     */
    function formatSummary($summary, $highlight, $truncateSize = 0) {
        if ($truncateSize === 0) {
            return Formatter::formatHtmlEntry($summary, $highlight);
        }
        else {
            return Formatter::formatHtmlEntry(Text::truncateText($summary, $truncateSize), $highlight);
        }
    }
}
