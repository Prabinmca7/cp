<?
namespace RightNow\Internal\Utils;

use RightNow\Utils\Text,
    RightNow\Utils\Config as ConfigExternal,
    RightNow\Utils\Widgets as WidgetsExternal,
    RightNow\Utils\Framework as FrameworkExternal;

class Tags
{
    private static $OPEN_HEAD_TAG_REGEX = '@(<head(?:|\s+[^>]*)>)@i';
    private static $CLOSE_HEAD_TAG_REGEX = '@(</head(?:|\s+[^>]*)>)@i';
    private static $URL_PARAM_PATTERN = '@#rn:url_param(_value)?:(.+?)#@';
    private static $RN_MSG_PATTERN = '@#rn:msg:((\{.+?\})|([^\{\}]+?))#@';
    private static $activeFormAction = '';
    private static $activePostHandler = '';

    const OPEN_BODY_TAG_REGEX = '@(<body(?:|\s+(?:<\?\w*[^>]*>.*?<\?.*?>)*[^>]*)>)@i';
    const CLOSE_BODY_TAG_REGEX = '@(</body(?:|\s+[^>]*)>)@i';

    public static function getRightNowTagPattern(){
        static $pattern = null;
        if($pattern === null)
            $pattern = self::getCommentableRNTagPattern('widgets?|container|form|field|theme|condition|condition_else|page_title', true);
        return $pattern;
    }
    public static function getMetaTagPattern(){
        static $pattern = null;
        if($pattern === null)
            $pattern = self::getCommentableRNTagPattern('meta', false);
        return $pattern;
    }
    public static function getThemeTagPattern(){
        static $pattern = null;
        if($pattern === null)
            $pattern = self::getCommentableRNTagPattern('theme', false);
        return $pattern;
    }

    /**
     * Builds a regular expression pattern which will match one or more rn:tags which are outside of HTML comments.
     *
     * @param string $tagNames String containing a sub-pattern of the regex which will be used to match one or more tag names.  Multiple tags should be separated by '|'s.
     * @param bool $allowClosingTag Boolean indicating whether the produced pattern should optionally match a closing tag.
     */
    public static function getCommentableRNTagPattern($tagNames, $allowClosingTag) {
        return
            '@<!--.*?-->|' . // Match HTML comments with a higher priority than rn:tags so we can ignore rn:tags inside of comments
            (($allowClosingTag) ? '</?rn:' : '<rn:') . // Match the beginning of the tag, optionally matching closing tags in addition to opening and self-closing tags.
            "($tagNames)" . // Capture the tag name.
            '\b' . // Require that we match the entire tag name by requiring that there's a word break after it.

            /*
             * The following atrocity tries to find the end of the tag given that the markup might contain manifold malformations.
             *
             * Allow whitespace to separate attributes.  You'd think that'd be a requirement, but you'd be surprised.
             * Either:
             *   - Match an attribute name and its equal sign and
             *   - Either:
             *       * A well-formed attribute value which is delimited by single or double quotes
             *       * A badly-formed attribute value which is delimited by whitespace (roughly).
             *   - A really, really busted attribute which doesn't have an equal sign and which is bounded by whitespace or angle brackets.
             */
            '(?:\s*(?:[-.:\w]+\s*=\s*(?:([\'"]).*?\2|\S*\b)|[^<>\s]*))*' .

            '\s*/?>' . // Allow the tag to be a self-closing tag.
            '@s'; // The regex needs to have the 's' modifier, which means PCRE_DOTALL, which means that the '.' wildcard will match every character, including newlines.  We need that so that comments and attribute values can span multiple lines.
    }

    /**
     * Converts rn tags into php calls
     *
     * @param string $buffer The php buffer to transform tags in
     * @param array|null $parseOptions Extra details needed for widget inspection in dev mode
     * @param object|null $widget Current widget instance being processed
     * @return string The buffer with tags replaced
     * @throws \Exception If a tag was found that looks like a rn: tag, but wasn't one of the supported options
     */
    public static function transformTags($buffer, $parseOptions = null, $widget = null) {
        if (!$buffer) {
            return $buffer;
        }

         // match widget, condition, and field RightNow tags.
         // Depending on the tag type, the matched information is routed to the correct function.
        $buffer = preg_replace_callback(self::getRightNowTagPattern(), function($matches) use($parseOptions, $widget){
            if (Text::beginsWith($matches[0], '<!')) {
                // You might ask, "Why, pray tell, would we match HTML comments just to replace them with themselves?"
                // Well, the point is to match entire HTML comments so that we can't
                // accidentally match <rn:*> tags inside of the comments.  This is the
                // kind of thing you do when you don't have a proper parser.
                return $matches[0];
            }
            $isClosingTag = Text::beginsWith($matches[0], '</');
            $tagName = $matches[1];
            if ($isClosingTag) {
                if ($tagName === 'condition') {
                    return '<?endif;?>';
                }
                if($tagName === 'container') {
                    return WidgetsExternal::containerReplacerEnd();
                }
                if($tagName === 'form') {
                    return Tags::formReplacerEnd();
                }
                // Historically we haven't done anything with closing tags for any of these types, so I'll just leave those
                // tags in the source.
                return $matches[0];
            }

            // So, we know it's either an open tag or a self-closing tag.  Historically, we've treated those the same.
            if($tagName === 'widget' || $tagName === 'widgets') {
                if(IS_DEVELOPMENT && ($widget || $parseOptions)){
                    if(!$widget) {
                        list($widgetPosition, $parseOptions) = self::getWidgetTagPosition($matches, $parseOptions);
                    }
                    else {
                        $widgetPosition = $widget->getInfo("widgetPosition");
                    }
                    return WidgetsExternal::widgetReplacer($matches, $widgetPosition);
                }
                return WidgetsExternal::widgetReplacer($matches);
            }
            if($tagName === 'container') {
                if(Text::endsWith($matches[0], "/>"))
                    return WidgetsExternal::containerReplacerBegin($matches) . "\n" . WidgetsExternal::containerReplacerEnd();
                return WidgetsExternal::containerReplacerBegin($matches);
            }
            if($tagName === 'field')
                return Tags::fieldReplacer($matches);
            if($tagName === 'form')
                return Tags::formReplacerStart($matches);
            if($tagName === 'condition')
                return Tags::conditionReplacer($matches);
            if($tagName === 'condition_else')
                 return '<?else:?>';
            if($tagName === 'theme')
                return ''; // We always parse themes without modifying the content elsewhere, but now we need to remove the theme.
            if($tagName === 'page_title')
                return '<?=\RightNow\Utils\Tags::getPageTitleAtRuntime();?>';
            throw new \Exception("Found a tag that looks eerily similar to a RightNow tag, but isn't one of the expected tags.");
        }, $buffer);

        $buffer = preg_replace_callback(self::$RN_MSG_PATTERN, '\RightNow\Utils\Config::messageReplacer', $buffer);
        $buffer = preg_replace_callback('@#rn:config:.+?#@', '\RightNow\Utils\Config::configReplacer', $buffer);
        $buffer = preg_replace('@#rn:(ASTR|astr):(.+?)#@', '$2', $buffer);
        $buffer = preg_replace_callback(self::$URL_PARAM_PATTERN, '\RightNow\Utils\Url::urlParameterReplacer', $buffer);
        $buffer = preg_replace('@#rn:session\s*#@', '<?=\RightNow\Utils\Url::sessionParameter();?>', $buffer);
        $buffer = preg_replace('@#rn:profile:(.+)?\s*#@', '<?=get_instance()->session->getProfileData("\\1");?>', $buffer);
        $buffer = preg_replace('@#rn:community_token(:([?&])?)?\s*#@', '<?=\RightNow\Utils\Url::communitySsoToken("\\2");?>', $buffer);
        $buffer = preg_replace('@#rn:flashdata:(.+)?#@', '<?=get_instance()->session->getFlashData("\\1");?>', $buffer);
        return preg_replace('@#rn:language_code\s*#@', '<?=\RightNow\Utils\Text::getLanguageCode();?>', $buffer);
    }

    /**
     * Merges the page and template meta arrays
     *
     * @param array $pageMeta Mta array for a page
     * @param array $templateMeta Meta array for a template
     * @return array Combined page/template meta array
     */
    public static function mergeMetaArrays(array $pageMeta, array $templateMeta){
        //Only allow the following template meta array attributes: (per 080430-000016)
        $templateKeysAllowed = array('login_required'  => '',
                                     'sla_failed_page' => '',
                                     'sla_required_type' => '',
                                     'javascript_module' => '',
                                     'include_chat' => '');

        //perform an array_intersect_key(...) operation to filter out all unwanted meta attributes
        $templateMeta = array_intersect_key($templateMeta, $templateKeysAllowed);

        //merge the template and page meta array using array_merge(...)
        //Note: Page meta values will overwrite the template values.
        return array_merge($templateMeta, $pageMeta);
    }

    /**
     * Merges the page content into the template where the rn:page_content tag is.
     * @param string $mainContent The content of the template
     * @param string $subordinateContent The content of the page
     * @return string The merged content
     */
    public static function mergePageAndTemplate($mainContent, $subordinateContent)
    {
        if (!$subordinateContent)
            return $mainContent;

        return self::replaceUncommentedTagOnce($mainContent, $subordinateContent, self::getPageContentTagPattern());
    }

    /**
     * Inserts the $headContent into $content at the right place.
     *
     * Because the HEAD_CONTENT_TAG_PATTERN searchs for both rn:head_content and
     * HTML comments (in order to allow users to comment out rn:head_content tags),
     * we have to iteratively search for the pattern, even though we
     * only replace the first occurance we find.
     *
     * Failing that, just jam the content in before the close head tag.
     *
     * @param string $content A string to insert $headContent into.
     * @param string $headContent A string to insert into $content.
     *
     * @return string The value of $content with $headContent inserted in place of the first rn:head_content tag found outside of an HTML comment or before the close head tag.
     */
    public static function insertHeadContent($content, $headContent) {
        $replacedContent = self::replaceUncommentedTagOnce($content, $headContent, self::getHeadContentTagPattern());
        if ($replacedContent === false) {
            $replacedContent = self::insertBeforeTag($content, $headContent, self::$CLOSE_HEAD_TAG_REGEX);
        }
        return $replacedContent;
    }

    /**
     * Inserts text before a tag
     *
     * @param string $haystack The content to insert into
     * @param string $needle The content to insert
     * @param string $tagRegex The regex to use for insertion
     * @return string The text with the content inserted
     */
    public static function insertBeforeTag($haystack, $needle, $tagRegex) {
        return self::insertNearTag($haystack, $needle, $tagRegex, true);
    }

    /**
     * Pulls the <rn:meta../> tag from the buffer and returns
     * php array of meta information
     *
     * @param string $buffer The php buffer to pull tags from
     * @param bool $removeMetaTags Whether or not to strip rn:meta tags from $buffer
     * @return string The buffer with tags replaced
     */
    public static function parseMetaInfo($buffer, $removeMetaTags = true){
        $mp = new \RightNow\Internal\Libraries\MetaParser();
        return array($mp->buildInfo($buffer, $removeMetaTags), $buffer);
    }

    /**
     * Gets the SEO and caching header tags
     * @param bool $shouldOutputHtmlFiveTags Boolean indicating whether tags which aren't HTML5 compatible should be suppressed.
     * @return string The tags
     */
    public static function getMetaHeaders($shouldOutputHtmlFiveTags = false){
        $commonHeaders = '<?= \RightNow\Libraries\SEO::getCanonicalLinkTag() . "\\n"; ?>' . "\n";
        if ($shouldOutputHtmlFiveTags) {
            return $commonHeaders;
        }
        else {
            return $commonHeaders .
                '<meta http-equiv="Pragma" content="no-cache"/>' . "\n" .
                '<meta http-equiv="Expires" content="-1"/>' . "\n";
        }
    }

    /**
     * Gets the attributes from an html tag
     * @param string $htmlTag HTML tag to parse
     * @return array List off key value pairs
     */
    public static function getHtmlAttributes($htmlTag){
        // Find the first whitespace, which should put us right after the tag name.
        $htmlTag = strpbrk($htmlTag, " \t\n\r\x0B");
        if (false === $htmlTag) {
            // If we didn't find whitespace, then there are not attributes.
            return array();
        }
        // Trim the closing '>' or '/>' off the tag so that we're left with just the attributes.
        if ($htmlTag[strlen($htmlTag) - 1] === '>') {
            $htmlTag = substr($htmlTag, 0, ($htmlTag[strlen($htmlTag) - 2] === '/') ? -2 : -1);
        }

        $attributes = array();
        $result = preg_match_all('@(\s*)([-_.:A-Za-z0-9]+)(\s*)=(\s*)(?:([\'"])(.*?)\5|(\S*\b#?))@s', $htmlTag, $matchesArray, PREG_SET_ORDER);
        if ($result)
        {
            foreach ($matchesArray as $matches)
            {
                $attributes[]= self::createAttributeObject($matches);
            }
        }
        return $attributes;
    }

    /**
     * Given the array of ParsedHtmlAttribute elements returned by getHtmlAttributes(),
     * get the value for the attribute with the specified name.
     * @param array $attributes List of attributes
     * @param string $attributeName Specific attribute to retrieve
     * @return string|bool The value - false if not found
     */
    public static function getAttributeValueFromCollection(array $attributes, $attributeName){
        foreach ($attributes as $attribute)
        {
            if (strcasecmp($attribute->attributeName, $attributeName) === 0)
                return $attribute->attributeValue;
        }
        return false;
    }

    /**
     * Given the array of ParsedHtmlAttribute elements returned by getHtmlAttributes(),
     * gets the value for the attribute names specified in $attributeNames.
     * @param array $attributes Results of getHtmlAttributes
     * @param array $attributeNames Names of attributes to look for and return values for
     * @return array Keyed by each name in $attributeNames; values for each item are the String attribute value or false if not found
     */
    public static function getAttributeValuesFromCollection(array $attributes, array $attributeNames) {
        $results = $indexed = array();

        foreach ($attributes as $attribute) {
            $indexed[$attribute->attributeName] = $attribute->attributeValue;
        }
        foreach ($attributeNames as $name) {
            $results[$name] = isset($indexed[$name]) && !is_null($indexed[$name]) ? $indexed[$name] : false;
        }

        return $results;
    }

    /**
     * Replaces the rn tags in a string with PHP calls.
     * @param string $subject Value to escape
     * @return string Escaped value
     */
    public static function escapeForWithinPhp($subject) {
        $subject = preg_replace_callback('@#rn:php:(.*?)#|\'@', function($matches){
            if ($matches[0] == "'")
                return "\\'";
            return "' . " . $matches[1] . " . '";
        }, $subject);
        $subject = preg_replace_callback(self::$RN_MSG_PATTERN, '\RightNow\Utils\Config::messageReplacerWithinPhp', $subject);
        $subject = preg_replace_callback('@#rn:config:.+?#@', '\RightNow\Utils\Config::configReplacerWithinPhp', $subject);
        $subject = preg_replace('@#rn:(ASTR|astr):(.+?)#@', "' . '$2' . '", $subject);
        $subject = preg_replace_callback(self::$URL_PARAM_PATTERN, '\RightNow\Utils\Url::urlParameterReplacerWithinPhp', $subject);
        $subject = preg_replace('@#rn:session\s*#@', "' . \RightNow\Utils\Url::sessionParameter() . '", $subject);
        $subject = preg_replace('@#rn:profile:(.+)?\s*#@', "' . get_instance()->session->getProfileData('\\1') . '", $subject);
        $subject = preg_replace_callback('@#rn:community_token(:([?&])?)?\s*#@', '\RightNow\Utils\Url::communitySsoTokenWithinPhp', $subject);
        $subject = preg_replace('@#rn:flashdata:(.+)?\s*#@', "' . get_instance()->session->getFlashData('\\1') . '", $subject);
        return preg_replace('@#rn:language_code\s*#@', "' . \RightNow\Utils\Text::getLanguageCode() . '", $subject);
    }

    /**
     * Receives a list of matches for the <rn:condition> tag and parses out the
     * attributes. The tag is replaced by a PHP if statement based on the
     * attributes passed.
     *
     * @param array $matches List of matches when looking for the tag
     * @return string The PHP if statement
     * @throws \Exception If condition tag found wasn't a supported option
     */
    public static function conditionReplacer(array $matches){
        $attributes = self::getHtmlAttributes($matches[0]);
        $functionCall = '<?if(';
        $conditionStatements = array();
        $count = 0;
        $errors = array();
        $CI = get_instance();
        foreach($attributes as $attribute)
        {
            $conditionStatements[$count] = null;
            // ---------------------------------------------------------------------------
            // NOTE: If you add a new attribute, you must also add an entry within
            // the TagDefinitions.php file for this new attribute.
            // That way it will be documented in the tag gallery, too.
            // ---------------------------------------------------------------------------
            switch($attribute->attributeName)
            {
                case 'show_on_pages':
                case 'hide_on_pages':
                    $equalityCheck = ($attribute->attributeName === 'show_on_pages') ? '===' : '!==';
                    $logicOperator = ($attribute->attributeName === 'show_on_pages') ? '||' : '&&';
                    $pagesList = explode(',', str_replace(' ', '', $attribute->attributeValue));
                    $pagesCount = count($pagesList);
                    if($pagesCount > 0)
                        $conditionStatements[$count] .= ' (';
                    for($i = 0; $i < $pagesCount; $i++)
                    {
                        if($i + 1 === $pagesCount)
                            $conditionStatements[$count] .= '(get_instance()->page' . " $equalityCheck '{$pagesList[$i]}') ";
                        else
                            $conditionStatements[$count] .= '(get_instance()->page' . " $equalityCheck '{$pagesList[$i]}') $logicOperator ";
                    }
                    if($pagesCount > 0)
                        $conditionStatements[$count] .= ') ';
                    break;
                case 'logged_in':
                    if(strcasecmp($attribute->attributeValue, 'true') === 0){
                        $conditionStatements[$count] .= ' (\RightNow\Utils\Framework::isLoggedIn()) ';
                    }
                    else if(strcasecmp($attribute->attributeValue, 'false') === 0){
                        $conditionStatements[$count] .= ' (!\RightNow\Utils\Framework::isLoggedIn()) ';
                    }
                    else{
                        $errors[] = FrameworkExternal::addErrorToPageAndHeader(sprintf(ConfigExternal::getMessage(PCT_S_TAG_ERR_VAL_PCT_S_TRUE_FALSE_MSG), 'RN:CONDITION', 'logged_in', $attribute->attributeValue), true);
                    }
                    break;
                case 'is_social_user':
                    if (strcasecmp($attribute->attributeValue, 'true') === 0) {
                        $conditionStatements[$count] .= " (\RightNow\Utils\Framework::isSocialUser())";
                    }
                    else if (strcasecmp($attribute->attributeValue, 'false') === 0) {
                        $conditionStatements[$count] .= " (!\RightNow\Utils\Framework::isSocialUser())";
                    }
                    else {
                        $errors[] = FrameworkExternal::addErrorToPageAndHeader(sprintf(ConfigExternal::getMessage(PCT_S_TAG_ERR_VAL_PCT_S_TRUE_FALSE_MSG), 'RN:CONDITION', 'is_social_user', $attribute->attributeValue), true);
                    }
                    break;
                case 'is_active_social_user':
                    if (strcasecmp($attribute->attributeValue, 'true') === 0) {
                        $conditionStatements[$count] .= " (\RightNow\Utils\Framework::isActiveSocialUser())";
                    }
                    else if (strcasecmp($attribute->attributeValue, 'false') === 0) {
                        $conditionStatements[$count] .= " (!\RightNow\Utils\Framework::isActiveSocialUser())";
                    }
                    else {
                        $errors[] = FrameworkExternal::addErrorToPageAndHeader(sprintf(ConfigExternal::getMessage(PCT_S_TAG_ERR_VAL_PCT_S_TRUE_FALSE_MSG), 'RN:CONDITION', 'is_active_social_user', $attribute->attributeValue), true);
                    }
                    break;
                case 'is_social_moderator':
                    if(strcasecmp($attribute->attributeValue, 'true') === 0){
                        $conditionStatements[$count] .= ' (\RightNow\Utils\Framework::isSocialModerator()) ';
                    }
                    else if(strcasecmp($attribute->attributeValue, 'false') === 0){
                        $conditionStatements[$count] .= ' (!\RightNow\Utils\Framework::isSocialModerator()) ';
                    }
                    else{
                        $errors[] = FrameworkExternal::addErrorToPageAndHeader(sprintf(ConfigExternal::getMessage(PCT_S_TAG_ERR_VAL_PCT_S_TRUE_FALSE_MSG), 'RN:CONDITION', 'is_social_moderator', $attribute->attributeValue), true);
                    }
                    break;
                case 'is_social_user_moderator':
                    if(strcasecmp($attribute->attributeValue, 'true') === 0){
                        $conditionStatements[$count] .= ' (\RightNow\Utils\Framework::isSocialUserModerator()) ';
                    }
                    else if(strcasecmp($attribute->attributeValue, 'false') === 0){
                        $conditionStatements[$count] .= ' (!\RightNow\Utils\Framework::isSocialUserModerator()) ';
                    }
                    else{
                        $errors[] = FrameworkExternal::addErrorToPageAndHeader(sprintf(ConfigExternal::getMessage(PCT_S_TAG_ERR_VAL_PCT_S_TRUE_FALSE_MSG), 'RN:CONDITION', 'is_social_user_moderator', $attribute->attributeValue), true);
                    }
                    break;
                case 'sla':
                    if(strcasecmp($attribute->attributeValue, 'incident') === 0){
                        $conditionStatements[$count] .= ' (get_instance()->session->getProfileData("slai")) ';
                    }
                    else if(strcasecmp($attribute->attributeValue, 'chat') === 0){
                        $conditionStatements[$count] .= ' (get_instance()->session->getProfileData("slac")) ';
                    }
                    else if(strcasecmp($attribute->attributeValue, 'selfservice') === 0){
                        $conditionStatements[$count] .= ' (get_instance()->session->getProfileData("webAccess")) ';
                    }
                    else{
                        $errors[] = FrameworkExternal::addErrorToPageAndHeader(sprintf(ConfigExternal::getMessage(PCT_S_TAG_ERR_VAL_PCT_S_INC_CHAT_MSG), 'RN:CONDITION', 'sla', $attribute->attributeValue), true);
                    }
                    break;
                case 'language_in':
                    $languageCodes = strtolower(str_replace('_', '-', $attribute->attributeValue));
                    $languageCodesArray = preg_split("/[\s,]+/", $languageCodes, -1, PREG_SPLIT_NO_EMPTY);
                    $conditionStatements[$count] .= ' (in_array(strtolower(\RightNow\Utils\Text::getLanguageCode()), array("' . implode('","', $languageCodesArray) . '"))) ';
                    break;
                case 'answers_viewed':
                    $value = intval($attribute->attributeValue);
                    $conditionStatements[$count] .= ' (get_instance()->session->getSessionData("answersViewed") >= ' . $value . ') ';
                    break;
                case 'questions_viewed':
                    $value = intval($attribute->attributeValue);
                    $conditionStatements[$count] .= ' (get_instance()->session->getSessionData("questionsViewed") >= ' . $value . ') ';
                    break;
                case 'content_viewed':
                    $value = intval($attribute->attributeValue);
                    $conditionStatements[$count] .= ' ((get_instance()->session->getSessionData("answersViewed") + get_instance()->session->getSessionData("questionsViewed")) >= ' . $value . ') ';
                    break;
                case 'searches_done':
                    $value = intval($attribute->attributeValue);
                    $conditionStatements[$count] .= ' (get_instance()->session->getSessionData("numberOfSearches") >= ' . $value . ') ';
                    break;
                case 'incident_reopen_deadline_hours':
                    $value = intval($attribute->attributeValue);
                    if($value < 0){
                        $errors[] = FrameworkExternal::addErrorToPageAndHeader(sprintf(ConfigExternal::getMessage(PCT_S_TAG_ERR_VAL_PCT_S_0_FND_PCT_MSG), 'RN:CONDITION', 'incident_reopen_deadline_hours', $value), true);
                    }
                    else{
                        $conditionStatements[$count] .= " (!\RightNow\Utils\Framework::hasClosedIncidentReopenDeadlinePassed($value)) ";
                    }
                    break;
                case 'is_spider':
                    if(strcasecmp($attribute->attributeValue, 'true') === 0){
                        $conditionStatements[$count] .= ' (false) ';
                    }
                    else if(strcasecmp($attribute->attributeValue, 'false') === 0){
                        $conditionStatements[$count] .= ' (true) ';
                    }
                    else{
                        $errors[] = FrameworkExternal::addErrorToPageAndHeader(sprintf(ConfigExternal::getMessage(PCT_S_TAG_ERR_VAL_PCT_S_TRUE_FALSE_MSG), 'RN:CONDITION', 'is_spider', $attribute->attributeValue), true);
                    }
                    break;
                case 'external_login_used':
                    if(strcasecmp($attribute->attributeValue, 'true') === 0){
                        $conditionStatements[$count] .= ' (\RightNow\Utils\Framework::isPta() || \RightNow\Utils\Framework::isOpenLogin()) ';
                    }
                    else if(strcasecmp($attribute->attributeValue, 'false') === 0){
                        $conditionStatements[$count] .= ' (!\RightNow\Utils\Framework::isPta() && !\RightNow\Utils\Framework::isOpenLogin()) ';
                    }
                    else{
                        $errors[] = FrameworkExternal::addErrorToPageAndHeader(sprintf(ConfigExternal::getMessage(PCT_S_TAG_ERR_VAL_PCT_S_TRUE_FALSE_MSG), 'RN:CONDITION', 'external_login_used', $attribute->attributeValue), true);
                    }
                    break;
                case 'config_check':
                case 'site_config_check':
                case 'url_parameter_check':
                    preg_match('@\s*(.+?)\s*(===|!==|==|!=|<=|>=|<|>)\s*(true|false|null|\d+|([\'"])(.*?)\4)\s*$@', $attribute->attributeValue, $conditionParts);
                    if(!count($conditionParts))
                    {
                        $errors[] = FrameworkExternal::addErrorToPageAndHeader(sprintf(ConfigExternal::getMessage(PCT_S_TG_ERR_INV_SYTAX_PCT_S_MSG), 'RN:CONDITION', $attribute->attributeName, $attribute->attributeValue), true);
                        break;
                    }
                    //Remove first element which is the full matched value
                    array_shift($conditionParts);
                    if(count($conditionParts) === 1 ){
                        list($valueToCheck) = $conditionParts;
                        list($operator, $comparisonValue, $quoteFound, $nonQuotedString) = array('', '', '', '');
                    }else if(count($conditionParts) === 2){
                        list($valueToCheck, $operator) = $conditionParts;
                        list( $comparisonValue, $quoteFound, $nonQuotedString) = array('', '', '');
                    }else if(count($conditionParts) === 3){
                        list($valueToCheck, $operator, $comparisonValue) = $conditionParts;
                        list($quoteFound, $nonQuotedString) = array('', '');
                    }else if(count($conditionParts) === 4){
                        list($valueToCheck, $operator, $comparisonValue, $quoteFound) = $conditionParts;
                        list($nonQuotedString) = array('');
                    }else{
                        list($valueToCheck, $operator, $comparisonValue, $quoteFound, $nonQuotedString) = $conditionParts;
                    }

                    if($operator === '===' || $operator === '!=='){
                        $errors[] = FrameworkExternal::addErrorToPageAndHeader(sprintf(ConfigExternal::getMessage(PCT_S_TG_ERR_INV_SYTX_PCT_S_MSG), 'RN:CONDITION', $attribute->attributeName, $attribute->attributeValue, $operator), true);
                        break;
                    }

                    if($quoteFound)
                    {
                        $comparisonValue = $nonQuotedString;
                    }
                    else
                    {
                        if($comparisonValue === 'true')
                            $comparisonValue = true;
                        else if($comparisonValue === 'false')
                            $comparisonValue = false;
                        else if($comparisonValue === 'null')
                            $comparisonValue = null;
                        else
                            $comparisonValue = intval($comparisonValue);
                    }

                    //Make sure the put something on the right side of the comparison. We don't want to
                    //support them doing something like "$value ===".
                    if(!$quoteFound && $comparisonValue === '')
                    {
                        $errors[] = FrameworkExternal::addErrorToPageAndHeader(sprintf(ConfigExternal::getMessage(PCT_S_TAG_ERR_INV_SYTAX_PCT_S_MSG), 'RN:CONDITION', $attribute->attributeName, $attribute->attributeValue), true);
                        break;
                    }

                    if($attribute->attributeName === 'config_check')
                    {
                        $valueToCheck = explode(':', $valueToCheck, 2);
                        $configName = end($valueToCheck);
                        if(!defined($configName) || !$configValue = constant($configName))
                        {
                            $errors[] = FrameworkExternal::addErrorToPageAndHeader(sprintf(ConfigExternal::getMessage(PCT_S_TAG_ERR_CFG_SLOT_NAME_PCT_S_MSG), 'RN:CONDITION', $attribute->attributeName, $configName), true);
                            break;
                        }
                        $functionSyntax = "\RightNow\Utils\Config::getConfig($configValue)";
                    }
                    else if($attribute->attributeName === 'site_config_check')
                    {
                        $valueToCheck = explode(':', $valueToCheck, 2);
                        $configName = end($valueToCheck);
                        $functionSyntax = \RightNow\Utils\Framework::getSiteConfigValue($configName) ? : 0;
                    }
                    else
                    {
                        $functionSyntax = '\RightNow\Utils\Url::getParameter(' . var_export($valueToCheck, true) . ')';
                    }
                    $conditionStatements[$count] .= " ($functionSyntax $operator " . var_export($comparisonValue, true) . ') ';
                    break;
                case 'chat_available':
                    if(strcasecmp($attribute->attributeValue, 'true') === 0){
                        $conditionStatements[$count] .= ' (\RightNow\Utils\Chat::isChatAvailable()) ';
                    }
                    else if(strcasecmp($attribute->attributeValue, 'false') === 0){
                        $conditionStatements[$count] .= ' (!\RightNow\Utils\Chat::isChatAvailable()) ';
                    }
                    else{
                        $errors[] = FrameworkExternal::addErrorToPageAndHeader(sprintf(ConfigExternal::getMessage(PCT_S_TAG_ERR_VAL_PCT_S_TRUE_FALSE_MSG), 'RN:CONDITION', 'chat_available', $attribute->attributeValue), true);
                    }
                    break;
                case 'flashdata_value_for':
                    if (trim($attribute->attributeValue)) {
                        $conditionStatements[$count] .= " (get_instance()->session->getFlashData(\"{$attribute->attributeValue}\")) ";
                    }
                    else {
                        $errors[] = FrameworkExternal::addErrorToPageAndHeader(sprintf(\RightNow\Utils\Config::getMessage(TAG_INV_SYNTAX_T_FLASHDAT_KEY_SPECIFIED_MSG), 'RN:CONDITION', 'flashdata_value_for'), true);
                    }
                    break;
                default:
                    if(get_class($CI) === 'Overview'){
                        throw new \Exception(sprintf(ConfigExternal::getMessage(FND_UNKNOWN_ATTRIB_PCT_S_RN_MSG), $attribute->attributeName));
                    }
                    else{
                        $errors[] = FrameworkExternal::addErrorToPageAndHeader(sprintf(ConfigExternal::getMessage(FND_UNKNOWN_ATTRIB_PCT_S_RN_MSG), $attribute->attributeName), true);
                    }
            }
            // ---------------------------------------------------------------------------
            // NOTE: If you add a new attribute, you must also add an entry within
            // the TagDefinitions.php file for this new attribute as well.
            // That way it will be documented in the tag gallery, too.
            // ---------------------------------------------------------------------------
            if($conditionStatements[$count] !== '' && $conditionStatements[$count] !== null)
                $count++;
        }
        //Iterate over any errors and prepend them to the PHP code
        foreach($errors as $error)
            $functionCall = "$error<br/>$functionCall";
        $conditionStatements = array_filter($conditionStatements);
        if(count($conditionStatements))
        {
            for($i = 0; $i < count($conditionStatements); $i++)
            {
                if($conditionStatements[$i])
                {
                    if($i === 0)
                        $functionCall .= $conditionStatements[$i];
                    else
                        $functionCall .= ' || ' . $conditionStatements[$i];
                }
            }
        }
        else
        {
            $functionCall .= 'true';
        }
        return "$functionCall):?>";
    }

    /**
     * Replaces all <rn:field ../> tags with a PHP function that validates
     * the field name and outputs its value.
     * @param Array $matches Array of matches to the rn:field tag
     * @return String Field value or error message
     */
    public static function fieldReplacer(array $matches)
    {
        $attributes = self::getHtmlAttributes($matches[0]);
        $attributes = self::getAttributeValuesFromCollection($attributes, array('name', 'id', 'highlight', 'label'));
        $name = \RightNow\Utils\Connect::mapOldFieldName($attributes['name']);
        $highlightingEnabled = (strcasecmp($attributes['highlight'], 'true') === 0);
        $validFieldTagAttributes = \RightNow\Utils\Connect::parseFieldName($name);
        if(!is_array($validFieldTagAttributes))
        {
            return FrameworkExternal::addErrorToPageAndHeader(sprintf(ConfigExternal::getMessage(ERROR_WITH_RN_FIELD_TAG_PCT_S_LBL), $validFieldTagAttributes), true);
        }
        $args = array(
            var_export($validFieldTagAttributes, true),
            var_export($highlightingEnabled, true),
            var_export($attributes['id'], true),
        );

        $replacement = '\RightNow\Utils\Connect::getFormattedObjectFieldValue(' . implode(', ', $args) . ')';

        if ($attributes['label'] && Text::stringContains($attributes['label'], '%s')) {
            $replacement = 'sprintf(' . var_export($attributes['label'], true) . ", $replacement)";
        }

        return "<?=" . $replacement . ";?>";
    }

    /**
     * Replaces all <rn:form ..> tags with an HTML form tag with an action and POST handler
     * hidden input which specifies the POST data processing endpoint.
     * @param array $matches Array of matches to the rn:form pattern
     * @return string The HTML/PHP rendered in the tag's position
     */
    public static function formReplacerStart(array $matches) {
        self::$activeFormAction = self::$activePostHandler = '';
        $attributeList = array();
        foreach(self::getHtmlAttributes($matches[0]) as $attribute) {
            $name = strtolower($attribute->attributeName);

            //Typically #rn:php# tags only work inside of widget attributes. Enable them in rn:form attributes.
            $value = preg_replace_callback('@#rn:php:(.*?)#|\'@', function($matches){
                if ($matches[0] === "'")
                    return "\\'";
                return "<?= " . $matches[1] . " ?>";
            }, $attribute->attributeValue);

            //These two attributes are required to validate the form. Store them off so that formReplacerEnd can add them back when
            //calculating the form validation token.
            if($name === 'post_handler') {
                self::$activePostHandler = $attribute->attributeValue;
                continue;
            }
            if($name === 'action') {
                self::$activeFormAction = $attribute->attributeValue;
            }
            $attributeList[] = $attribute->attributeName . "='" . $value . "'";
        }

        //If an action is excluded, dynamically compute the same page
        if(!self::$activeFormAction) {
            $attributeList[] = 'action="<?= \RightNow\Utils\Url::deleteParameter(\RightNow\Utils\Url::deleteParameter(ORIGINAL_REQUEST_URI, \'session\'), \'messages\') . \RightNow\Utils\Url::sessionParameter(); ?>"';
        }

        $output = "<form method='post' " . implode(' ', $attributeList) . "><div>";
        if(!self::$activePostHandler) {
            FrameworkExternal::addErrorToPageAndHeader(sprintf(ConfigExternal::getMessage(PCT_S_TAG_ERR_INV_SYNTAX_POST_MSG), 'RN:FORM'));
        }

        return $output;
    }

    /**
     * Replaces all </rn:form> tags with an HTML close form tag and the encrypted
     * server side validation constraints
     * @return string The HTML/PHP rendered in the tag's position
     */
    public static function formReplacerEnd() {
        if(self::$activeFormAction) {
            $action = "'" . self::escapeForWithinPhp(self::$activeFormAction) . "'";
        }
        $handler = "'" . self::escapeForWithinPhp(self::$activePostHandler) . "'";
        return "<?= \RightNow\Utils\Widgets::addServerConstraints(" . (isset($action) ? $action : '\RightNow\Utils\Url::deleteParameter(ORIGINAL_REQUEST_URI, "messages")') . ", $handler); ?></div></form>";
    }

    /**
     * Determines whether the content contains an HTML5 style doctype.
     * See http://dev.w3.org/html5/spec/Overview.html#the-doctype.
     *
     * @param string $content String to search
     *
     * @return bool Whether the content contains an HTML5 style doctype.
     */
    public static function containsHtmlFiveDoctype($content) {
        return 1 === preg_match("@<!doctype +html( +system +(['\"])about:legacy-compat\\2)? *>@i", $content);
    }

    /**
     * Checks to make sure the passed in content has an html HEAD and BODY tag within it. If not,
     * an exception is thrown
     * @param string $content The content to scan for HEAD and BODY tags
     * @param string $contentPath  The file path to the content
     * @throws \Exception If content does not contain head or body tags
     */
    public static function ensureContentHasHeadAndBodyTags($content, $contentPath) {
        $tags = array(
            self::OPEN_BODY_TAG_REGEX => ConfigExternal::getMessage(PCT_S_MUST_CONTAIN_A_BODY_TAG_MSG),
            self::CLOSE_BODY_TAG_REGEX => ConfigExternal::getMessage(PCT_S_CONTAIN_CLOSING_BODY_TAG_MSG),
            self::$OPEN_HEAD_TAG_REGEX => ConfigExternal::getMessage(PCT_S_MUST_CONTAIN_A_HEAD_TAG_MSG),
            self::$CLOSE_HEAD_TAG_REGEX => ConfigExternal::getMessage(PCT_S_CONTAIN_CLOSING_HEAD_TAG_MSG),
        );

        foreach ($tags as $regex => $errorMessages) {
            $matchesCount = preg_match($regex, $content, $matches);
            if ($matchesCount < 1)
                throw new \Exception(sprintf($errorMessages, $contentPath));
        }
    }

    /**
     * Inserts text after a tag
     *
     * @param string $haystack The content to insert into
     * @param string $needle The content to insert
     * @param string $tagRegex The regex to use for insertion
     * @return string The text with the content inserted
     */
    public static function insertAfterTag($haystack, $needle, $tagRegex) {
        return self::insertNearTag($haystack, $needle, $tagRegex, false);
    }

    /**
     * Returns the JS to load the specified $urls using `YUI.Get.js`
     * @param string|array $urls The path to the JS being loaded, or a list of paths.
     * @param array $options Options to pass to `YUI.Get.js`
     * @param string $callback A callback function that is triggered after the resource is loaded.
     * @return string The code used to load the resource(s)
     */
    public static function createYUIGetJsTag($urls, $options = array(), $callback = null){
        return sprintf('<script>YUI().use("get", function(Y){Y.Get.js(%s, %s, %s);});</script>',
            json_encode($urls),
            $options ? str_replace('\/', '/', json_encode($options)) : 'null',
            $callback ?: 'null'
        );
    }

    /**
     * Searches for the pattern, which it assumes matches both HTML comments and a single tag, and replaces the tag exactly one.
     *
     * @param string $haystack String to search.
     * @param string $replacement String to replace the tag with
     * @param string $pattern String containing a regular expression which matches both HTML comments and a single tag.
     * @return string|bool False if the tag in pattern was never found or the replaced version of $haystack if it was.
     */
    private static function replaceUncommentedTagOnce($haystack, $replacement, $pattern) {
        list($matchedText, $matchOffset) = self::findFirstUncommentedTag($haystack, $pattern);
        if ($matchedText === null) {
            return false;
        }
        return substr($haystack, 0, $matchOffset) . $replacement . substr($haystack, strlen($matchedText) + $matchOffset);
    }

    /**
     * Finds the first time that $pattern matches that's not an HTML comment.
     *
     * @param string $haystack String to search.
     * @param string $pattern String containing a regex to search for. It is assumed to match both HTML comments and a single tag,
     * @return array|bool False if not found; an array containing the match and the match' offset if found.
     */
    protected static function findFirstUncommentedTag($haystack, $pattern) {
        $offset = 0;

        while (1 === preg_match($pattern, $haystack, $matches, PREG_OFFSET_CAPTURE, $offset)) {
            list($matchedText, $matchOffset) = $matches[0];
            if (Text::beginsWith($matchedText, '<!')) {
                $offset = strlen($matchedText) + $matchOffset;
                continue;
            }
            return $matches[0];
        }
        return false;
    }

    /**
     * Gets the character position of a widget line
     *
     * @param  array $matches Regular expression matches of a widget line
     * @param  array $parseOptions Extra infomration to find the widget position
     * @return array Array of matched position and the updated parse options
     */
    protected static function getWidgetTagPosition($matches, $parseOptions){
        $parseOptions["bodyStart"] = isset($parseOptions["bodyStart"]) && $parseOptions["bodyStart"] ?: strpos($parseOptions["mergedContent"], $parseOptions["noMetaBody"]);
        $parseOptions["bodyEnd"] = isset($parseOptions["bodyEnd"]) && $parseOptions["bodyEnd"] ?: $parseOptions["bodyStart"] + strlen($parseOptions["noMetaBody"]);
        $matchedWidgetTag = $matches[0] ? "@" .  str_replace("@", "\@", preg_quote($matches[0])) . "@sU" : null;

        list($matchedText, $matchOffset) = $matchedWidgetTag ? self::findFirstUncommentedTag($parseOptions["mergedContent"],  $matchedWidgetTag) : array(null, null);
        
        $parseOptions["mergedContent"] = self::replaceUncommentedTagOnce($parseOptions["mergedContent"], str_repeat("@", strlen($matches[0])), $matchedWidgetTag);
        if(isset($searchArea)) $parseOptions[$searchArea] = $parseOptions["mergedContent"];

        if($matchOffset && $matchedWidgetTag){
            $isBodyTag = !$parseOptions["template"] || ($matchOffset >= $parseOptions["bodyStart"] && $matchOffset < $parseOptions["bodyEnd"]);
            $searchArea = $isBodyTag ? "body" : "template";
            list($matchedText, $matchOffset) = self::findFirstUncommentedTag($parseOptions[$searchArea],  $matchedWidgetTag);
            $parseOptions[$searchArea] = self::replaceUncommentedTagOnce($parseOptions[$searchArea], str_repeat("@", strlen($matches[0])), $matchedWidgetTag);
            $matchOffset = $matchOffset ? $matchOffset . "@$searchArea@" . strlen($matchedText)  : $matchOffset;
        }
        return array($matchOffset, $parseOptions);
    }

    private static function getHeadContentTagPattern(){
        static $pattern = null;
        if($pattern === null)
            $pattern = self::getCommentableRNTagPattern('head_content', false);
        return $pattern;
    }

    private static function getPageContentTagPattern(){
        static $pattern = null;
        if($pattern === null)
            $pattern = self::getCommentableRNTagPattern('page_content', false);
        return $pattern;
    }

    private static function createAttributeObject($matches){
        $object = new \stdClass();
        $object->completeAttribute = $matches[0];
        $object->leadingWhitespace = $matches[1];
        $object->attributeName = $matches[2];
        $object->beforeEqualsWhitespace = $matches[3];
        $object->afterEqualsWhitespace = $matches[4];
        if (count($matches) === 7) {
            $object->valueDelimiter = $matches[5];
            $object->attributeValue = $matches[6];
        }
        else if (count($matches) === 8) {
            $object->valueDelimiter = '';
            $object->attributeValue = $matches[7];
        }
        else {
            throw new \Exception("I'm not sure what happened, but parsing the attribute failed because it had an unexpected number of captured groups.  The tag was '{$matches[0]}'");
        }
        return $object;
    }

    /**
     * Inserts text either before or after the tag specified
     *
     * @param string $haystack The content to insert into
     * @param string $needle The content to insert
     * @param string $tagRegex The regex to use when searching for the correct tag
     * @param bool $insertBeforeTag Denotes if we're inserting before or after a tag
     * @return string The content with text inserted
     */
    private static function insertNearTag($haystack, $needle, $tagRegex, $insertBeforeTag) {
        if (!preg_match($tagRegex, $haystack, $matches, PREG_OFFSET_CAPTURE)) {
            $ex = new \Exception("It appears that your page has some sort of error or unexpected output. The output is %s.");
            exit(sprintf($ex->getMessage(), "\n<pre>$haystack</pre>"));
        }
        $tag = $matches[0][0];
        $tagOffset = $matches[0][1];
        $before = substr($haystack, 0, $tagOffset);
        $after = substr($haystack, $tagOffset + strlen($tag ? $tag : 0));
        if ($insertBeforeTag) {
            return "$before\n$needle\n$tag\n$after";
        }
        return "$before\n$tag\n$needle\n$after";
    }
}
