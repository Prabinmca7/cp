<?php
namespace RightNow\Libraries;
use RightNow\Api,
    RightNow\Utils\Text,
    RightNow\Utils\Config;

/**
 * Static class for all Search Engine Optimization functions.
 */
class SEO
{
    private static $CI;

    /**
     * Gets the page title specified by $type.
     * @param string $type One of:
     *   - Answer
     *   - Incident
     *   - Product
     *   - Category
     *   - Question
     *   - Asset
     *   - PublicProfile
     *
     * @param int $recordID The answer or incident ID
     *
     * @param array $args An optional associative array of method specific arguments.
     *   Supported arguments by type:
     *     type: PublicProfile
     *       -- 'suffix': {string} Optional text to append to the social user's name.
     *
     * @return string The value of the record's 'title' stripped of HTML tags
     */
    public static function getDynamicTitle($type, $recordID, array $args = array()) {
        return Api::print_text2str(self::getRecordTitle($type, $recordID, $args), OPT_STRIP_HTML_TAGS);
    }

    /**
     * Gets the 'title' of the supplied record.
     * @param string $type One of:
     *   - Answer
     *   - Incident
     *   - Product
     *   - Category
     *   - Question
     *   - Asset
     *   - PublicProfile
     *
     * @param int $recordID The answer or incident ID
     *
     * @param array $args An optional associative array of method specific arguments.
     *   Supported arguments by type:
     *     type: PublicProfile
     *       -- 'suffix': {string} Optional text to append to the social user's name.
     *
     * @return string The value of the record's 'title'
     * @throws \Exception If the $type is not recognized.
     */
    public static function getRecordTitle($type, $recordID, array $args = array()) {
        if(!self::$CI)
            self::$CI = get_instance();

        $methods = array(
            'answer'        => 'getAnswerTitle',
            'incident'      => 'getIncidentTitle',
            'question'      => 'getQuestionTitle',
            'product'       => 'getProductTitle',
            // 'categor' matches 'category' and 'categories'
            'categor'       => 'getCategoryTitle',
            'asset'         => 'getAssetTitle',
            'publicprofile' => 'getPublicProfileTitle',
        );

        $type = strtolower($type);
        foreach ($methods as $targetType => $method) {
            if (Text::beginsWith($type, $targetType)) {
                return self::$method($recordID, $args);
            }
        }

        throw new \Exception("Unknown record type: [$type]");
    }

    /**
     * Produces a URL that is "canonical" for the given answer. This URL is of the form:
     *
     *      http://www.site.com/app/{CP_ANSWERS_DETAIL_URL}/a_id/{$answerID}/~/{title-of-answer}
     *
     * The title is truncated to 80 multibyte characters (less if the truncation
     * cuts through a word). The individual words of the title are separated by hyphens.
     * @param int $answerID The answer ID
     * @return string The canonical URL
     */
    public static function getCanonicalAnswerURL($answerID)
    {
        return self::buildCanonicalURL(Config::getConfig(CP_ANSWERS_DETAIL_URL), 'a_id', $answerID,
            self::getAnswerSummarySlug(self::getRecordTitle('answer', $answerID)));
    }

    /**
     * Produces a URL that is "canonical" for the given question. This URL is of the form:
     *
     *      http://www.site.com/app/{TK config}/qid/{$questionID}/~/{title-of-question}
     *
     * The title is truncated to 80 multibyte characters (less if the truncation
     * cuts through a word). The individual words of the title are separated by hyphens.
     * @param int $questionID The answer ID
     * @return string The canonical URL
     */
    public static function getCanonicalQuestionURL($questionID)
    {
        return self::buildCanonicalURL(Config::getConfig(CP_SOCIAL_QUESTIONS_DETAIL_URL), 'qid', $questionID,
            self::getAnswerSummarySlug(self::getRecordTitle('question', $questionID)));
    }

    /**
     * Convert an answer summary to a slug for use in canonical URLs
     * @param string $summary The answer summary
     * @return string          The slug
     */
    public static function getAnswerSummarySlug($summary) {
        // the maximum number of (multibyte) characters to take from the title
        $maxTitleLength = 80;
        $titleLength = 0;

        // In order to make sure the URL isn't TOO ugly, we trim it down
        try
        {
            $titleLength = \RightNow\Utils\Text::getMultibyteStringLength($summary);
        }
        catch (\Exception $e)
        {
            $summary = Api::utf8_cleanse($summary);

            try
            {
                $titleLength = \RightNow\Utils\Text::getMultibyteStringLength($summary);
            }
            catch (\Exception $e)
            {
                // I give up
                $summary = '';
            }
        }

        if ($titleLength > $maxTitleLength)
        {
            $summary = Api::utf8_trunc_nchars($summary, $maxTitleLength);

            // We have truncated to the max size, but we might have cut a word in
            // half. So, find the last space and truncate to there. If no space is
            // found (it's one big giant word), then don't truncate at all - in
            // order to safeguard Asian language titles.
            if (($pos = strrpos($summary, ' ')) !== false){
                $summary = substr($summary, 0, $pos);
            }
        }

        // IE sees certain character combinations with quotes in cannonical links as XSS attacks, even when encoded.
        $summary = !is_null($summary) ? str_replace(array('\'', '"'), '', strtolower($summary)) : null;
        // Google recommends using hyphens instead of spaces or underscores
        $summary = !is_null($summary) ? preg_replace('/\s+/', '-', $summary) : null;
        // Don't forget to URL-encode the text
        $summary = !is_null($summary) ? urlencode($summary) : null;

        return $summary;
    }

    /**
     * Produces a meta tag that is used by internet spiders to know
     * what URL to associate the page content with. Uses getCanonicalAnswerURL()
     * @return string A ready to print (in the head section) canonical directive
     */
    public static function getCanonicalLinkTag()
    {
        if(!self::$CI)
            self::$CI = get_instance();

        $page = self::$CI->page;

        foreach (self::getCanonicalLinkTypes() as $linkType) {
            if ($page === Config::getConfig($linkType['page']) && ($paramValue = \RightNow\Utils\Url::getParameter($linkType['param']))) {
                return self::buildCanonicalLink(self::buildCanonicalURL($page, $linkType['param'], $paramValue,
                    self::getAnswerSummarySlug(self::getRecordTitle($linkType['type'], $paramValue))));
            }
        }
    }

    /**
     * Sets up the class to use a mock controller instead of the CP
     * standard one. Use SimpleTest's Mock class to generate the object.
     * New controller will override any previous controllers loaded.
     * Only useful for testing purposes (do NOT use in real code).
     * @param object $mockCI The mock controller.
     * @return void
     * @internal
     */
    public static function setMockController($mockCI)
    {
        self::$CI = $mockCI;
    }

    /**
     * Returns the answer summary for the given answer id.
     * @param string $id ID
     * @return string Answer summary or default label
     */
    private static function getAnswerTitle($id) {
        if(Config::getConfig(OKCS_ENABLED) && !(Config::getConfig(MOD_RNANS_ENABLED) && IS_PRODUCTION)) {
            $answerID = \RightNow\Utils\Text::getSubstringBefore(\RightNow\Utils\Url::getParameter('s'), '_');
            $searchSession = \RightNow\Utils\Text::getSubstringAfter(\RightNow\Utils\Url::getParameter('s'), '_');
            $searchData = array('answerId' => $answerID, 'searchSession' => $searchSession, 'prTxnId' => \RightNow\Utils\Url::getParameter('prTxnId'), 'txnId' => \RightNow\Utils\Url::getParameter('txnId'));
            $article = self::$CI->model('Okcs')->getArticleDetails($id, 'v1', $searchData);
            if(!isset($article->errors)) {
                if(isset($article['answerPreview']) && $article['answerPreview']) {
                    $previewTitle = Config::ASTRgetMessage('Preview - ') . $article['title'];
                    return $previewTitle;
                }
                else
                    return $article['title'];
            }
        }
        return self::getRecordField('Answer', $id, 'Summary') ?: Config::getMessage(ANSWER_LBL);
    }

    /**
     * Returns the incident subject for the given incident id.
     * @param string $id ID
     * @return string Incident subject or default label
     */
    private static function getIncidentTitle($id) {
        return self::getRecordField('Incident', $id, 'Subject') ?: Config::getMessage(VIEW_QUESTION_HDG);
    }

    /**
     * Returns the product label for the given product id.
     * @param string $id ID
     * @return string Product lookupname or default label
     */
    private static function getProductTitle($id) {
        if(Config::getConfig(OKCS_ENABLED) && !(Config::getConfig(MOD_RNANS_ENABLED) && IS_PRODUCTION)) {
            require_once CPCORE . 'Utils/Okcs.php';
            return \RightNow\Utils\Okcs::getProductCategoryTitle($id);
        }
        return self::getRecordField('Prodcat', Text::extractCommaSeparatedID($id), 'LookupName') ?: Config::getMessage(PRODUCT_LBL);
    }

    /**
     * Returns the category label for the given category id.
     * @param string $id ID
     * @return string Category lookupname or default label
     */
    private static function getCategoryTitle($id) {
        if(Config::getConfig(OKCS_ENABLED) && !(Config::getConfig(MOD_RNANS_ENABLED) && IS_PRODUCTION)) {
            require_once CPCORE . 'Utils/Okcs.php';
            return \RightNow\Utils\Okcs::getProductCategoryTitle($id);
        }
        return self::getRecordField('Prodcat', Text::extractCommaSeparatedID($id), 'LookupName') ?: Config::getMessage(CATEGORY_LBL);
    }

    /**
     * Returns the question subject for the given question id.
     * @param string $id ID
     * @return string Question subject or default label
     */
    private static function getQuestionTitle($id) {
        return self::getRecordField('CommunityQuestion', $id, 'Subject') ?: Config::getMessage(VIEW_QUESTION_HDG);
    }

    /**
     * Returns the asset name for the given asset id.
     * @param string $id ID
     * @return string Asset name or default label
     */
    private static function getAssetTitle($id) {
        return self::getRecordField('Asset', $id, 'Name') ?: Config::getMessage(VIEW_ASSET_CMD);
    }

    /**
     * Returns the public profile title for the given social user id.
     * @param string $id ID
     * @param array $args If a 'suffix' key is specified its value is appended to the user's name.
     * @return string The public profile title for the given social user id.
     */
    private static function getPublicProfileTitle($id, array $args = array()) {
        if (!$title = self::getRecordField('CommunityUser', $id, 'DisplayName')) {
            return Config::getMessage(PUBLIC_PROFILE_LBL);
        }

        return array_key_exists('suffix', $args) ? "$title{$args['suffix']}" : $title;
    }

    /**
     * Returns the specified field value for the specified record and model.
     * @param string $modelName Name of the model
     * @param number $id Record id
     * @param string $fieldName Name of the field
     * @return string|null Field value or null if the record isn't found
     */
    private static function getRecordField($modelName, $id, $fieldName) {
        if ($record = self::$CI->model($modelName)->get((int) $id)->result) {
            return $record->$fieldName;
        }
    }

    /**
     * Builds up a canonical URL.
     * @param  string $page       Page path
     * @param  string $paramKey   Parameter key (e.g. 'a_id')
     * @param  string $paramValue Parameter value (e.g. '2')
     * @param  string $slug       Slug snippet
     * @return string             Built up canonical URL
     */
    private static function buildCanonicalURL($page, $paramKey, $paramValue, $slug) {
        return \RightNow\Utils\Url::getShortEufAppUrl('sameAsCurrentPage', "{$page}/{$paramKey}/{$paramValue}/~/{$slug}");
    }

    /**
     * Returns a link tag.
     * @param  string $href Tag's href value
     * @return string       Tag
     */
    private static function buildCanonicalLink($href) {
        return "<link rel='canonical' href='$href'/>";
    }

    /**
     * Returns the data structure representing the
     * types of objects that are supported by canonical links.
     * @return array Link types
     */
    private static function getCanonicalLinkTypes() {
        static $types;

        if (is_null($types)) {
            $types = array(
                array(
                    'type'  => 'answer',
                    'page'  => CP_ANSWERS_DETAIL_URL,
                    'param' => 'a_id',
                ),
                array(
                    'type'  => 'question',
                    'page'  => CP_SOCIAL_QUESTIONS_DETAIL_URL,
                    'param' => 'qid',
                ),
            );
        }

        return $types;
    }
}
