<?

namespace RightNow\Helpers;

use RightNow\Libraries\Formatter,
    RightNow\Utils\Text;

class SocialResultListingHelper extends SourceResultListingHelper {
    protected $metadataMapping;
    /**
     * Highlights CommunityQuestion / CommunityComment's title
     * @param string $title Content text
     * @param String|Boolean $highlight Set to true to highlight current 'kw' URL parameter
     *                                  Set to false to skip highlighting
     *                                  Set to a string to force highlighting on the given string instead of the current `kw` URL parameter. Useful when 'kw' param is unavailable
     * @param int $truncateSize Length to limit the content
     * @return string          Highlighted text
     */
    function highlightTitle ($title, $highlight, $truncateSize) {
        $truncatedText = ($truncateSize === 0) ? $title : Text::truncateText($title, $truncateSize);
        return Formatter::highlight($truncatedText, $highlight);
    }

    /**
     * Formats a CommunityQuestion / CommunityComment's body
     * @param string $summary Content text
     * @param String|Boolean $highlight Set to true to highlight current 'kw' URL parameter
     *                                  Set to false to skip highlighting
     *                                  Set to a string to force highlighting on the given string instead of the current `kw` URL parameter. Useful when 'kw' param is unavailable
     * @param int $truncateSize Length to limit to content to
     * @return string          Formatted text
     */
    function formatSummary ($summary, $highlight, $truncateSize = 0) {
        // We explicitly convert the MD to HTML first, so the truncate doesn't truncate in the middle of
        // a MD block (ie **an example** would have become **an), thus displaying incorrectly.
        $summary = Formatter::formatMarkdownEntry($summary, false);
        $truncatedText = ($truncateSize === 0) ? $summary : Text::truncateText($summary, $truncateSize);
        return Formatter::formatHtmlEntry($truncatedText, $highlight);
    }

    /**
     * Produces a date string.
     * @param String|Number $timestamp Date timestamp
     * @return String Formatted string (without time info)
     */
    function formatDate ($timestamp) {
        return Formatter::formatDate($timestamp);
    }

    /**
     * Gets required metadata elements depending upon the parameters passed.
     * @param array $metadataAttrs Widget attributes
     * @return array metadata elements
     */
    function getMetadataElements($metadataAttrs) {
        static $metadataMapping;
        if(!$metadataMapping) {
            $metadataMapping = array(
                'comment_count' => array(
                    'elementName' => 'commentCount',
                    'labelForSingleElement' => COMMENT_LC_LBL,
                    'labelForMultipleElements' => COMMENTS_LC_LBL
                ),
                'best_answers' => array(
                    'elementName' => 'bestAnswerCount',
                    'labelForSingleElement' => BEST_ANS_LBL,
                    'labelForMultipleElements' => BEST_ANSWERS_LC_LBL
                )
            );    
        }
        $metadataElements = array();
        if(is_array($metadataAttrs) && !empty($metadataAttrs)) {
            foreach ($metadataAttrs as $value) {
                $metadataElements[] = $metadataMapping[$value];
            }
        }
        return $metadataElements;       
    }
}
