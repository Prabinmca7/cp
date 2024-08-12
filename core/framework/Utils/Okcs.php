<?php

namespace RightNow\Utils;

use RightNow\Api,
    RightNow\ActionCapture;

require_once CPCORE . 'Utils/Okcs.php';
require_once CPCORE . 'Models/Okcs.php';

/**
 * Methods for dealing with okcs search result related functionality.
 */
final class Okcs {
    private $schemaAttributes;
    private $contentView;
    private $schemaData;
    private $currentSchemaType = "CHANNEL";
    private $currentElement;
    private $fileName;
    private $filePosition;
    private $listOption;
    private $isListOption;
    private $resourcePath;
    private $xPath = array();
    private $xpathCount = array();
    private $lastListNodeAdded;

    const FILE_ATTRIBUTE = 'FILE';
    const BOOLEAN_ATTRIBUTE = 'BOOLEAN';
    const CHECKBOX_ATTRIBUTE = 'CHECKBOX';
    const LIST_ATTRIBUTE = 'LIST';
    const DATE_ATTRIBUTE = 'DATE';
    const DATETIME_ATTRIBUTE = 'DATETIME';
    const TIME_ATTRIBUTE = 'TIME';
    const DISPLAY_ATTRIBUTE = 'DISPLAY';
    const RICHTEXT_ATTRIBUTE = 'WYSIWYG_EDIT';
    const TEXT_AREA = 'TEXT_AREA';
    const TEXTFIELD_ATTRIBUTE = 'TEXT_FIELD';
    const DEFAULT_SCHEMA = 'CHANNEL';
    const NODE_TYPE = 'NODE';
    const ANSWER_LINK_REGEX_PATTERN = '/<ok:answer-link ((.|\n)*?)>((.|\n)*?)<\/ok:answer-link>/';
    const ANSWER_LINK_ID_REGEX_PATTERN = '/answer_id="([(0-9)]*)"/';
    const ANSWER_LINK_CONTENTS_REGEX_PATTERN = '/contents="([^"]*)"/';
    const ANSWER_LINK_TITLE_REGEX_PATTERN = '/title="([^"]*)"/';
    const ANSWER_LINK_TARGET_REGEX_PATTERN = '/target="([^"]*)"/';
    const ANSWER_LINK_REL_REGEX_PATTERN = '/rel="([^"]*)"/';
    const ANSWER_LINK_ANCHOR_REGEX_PATTERN = '/anchor="([^"]*)"/';
    const DOC_LINK_REGEX_PATTERN = '/<ok:doc-link ((.|\n)*?)>((.|\n)*?)<\/ok:doc-link>/';
    const DOC_LINK_ID_REGEX_PATTERN = '/doc_id="([\S(1,10)]+[(0-9)(1,10)])"/';

    /**
    * This method returns an array of answer details
    * Each content object contains schema attribute header and corresponding details
    * @param string $contentXML Content xml string
    * @param array $channelSchemaAttributes Array of channel schema attrubtes
    * @param string $schemaType Type of content schema
    * @param string $resourcePath File resource path
    * @return array Answer details
    */
    public function getAnswerView($contentXML, array $channelSchemaAttributes, $schemaType, $resourcePath) {
        $this->contentView = array();
        $this->xPath = array();
        $this->xpathCount = array();
        $this->filePosition = 0;
        $contentXML = preg_replace('/\r/', '<OKCS_CONTENT_CR/>', $contentXML);
        $contentXML = preg_replace('/\n/', '<OKCS_CONTENT_LF/>', $contentXML);
        $contentXML = $this->processXMLContent($contentXML);
        if(!is_null($contentXML) && !is_null($channelSchemaAttributes) && is_array($channelSchemaAttributes)) {
            $this->resourcePath = is_null($resourcePath) ? '' : $resourcePath;
            $this->currentSchemaType = ($schemaType === null || $schemaType === '') ? self::DEFAULT_SCHEMA : $schemaType;
            foreach ($channelSchemaAttributes as &$attribute) {
                self::getSchemaNodeAttributes($attribute, $schemaType);
            }
            $this->schemaData = '';
            // @codingStandardsIgnoreStart
            $xmlParser = xml_parser_create();
            xml_set_element_handler($xmlParser, array(self::class, 'startElementHandler'), array(self::class, 'endElementHandler'));
            xml_set_character_data_handler($xmlParser, array(self::class, 'dataHandler'));
            xml_parse($xmlParser, $contentXML, true);
            // @codingStandardsIgnoreEnd
        }
        return $this->getValidContentAttributes($this->contentView);
    }

    /**
    * This handler is called when the XML parser encounters the beginning of an element
    * @param object $parser Reference to the XML parser
    * @param string $tagElement Tag element
    */
    protected function startElementHandler($parser, $tagElement) {
        // Pacify PHP_CodeSniffer's unused variable check.
        assert($parser || true);
        $this->currentElement = $tagElement;
        array_push($this->xPath, $tagElement);
        $xPath = implode("/", $this->xPath);
        $size = count($this->contentView);
        $contentKey = $xPath;
        $depth = count($this->xPath) - 1;
        $contentSchema = isset($this->schemaAttributes[$this->currentSchemaType . "_" . '//'. $xPath]) ? $this->schemaAttributes[$this->currentSchemaType . "_" . '//'. $xPath] : null;
        if($depth === 0 || !is_null($contentSchema['name'])){
            if(isset($this->xpathCount[$xPath])) {
                $pathCount = $this->xpathCount[$xPath];
                $xPath = $xPath . '-' . $pathCount;
            }
            $contentKey = $xPath;
            $this->contentView[$contentKey] = array('name' => isset($contentSchema['name']) ? $contentSchema['name'] : null, 'type' => self::NODE_TYPE, 'xPath' => $xPath, 'depth' => $depth);

            if(isset($contentSchema['type']))
                $this->filePosition++;
        }
    }

    /**
    * This handler is called when the XML parser encounters the end of an element
    * @param object $parser Reference to the XML parser
    * @param string $tagElement Tag element
    */
    protected function endElementHandler($parser, $tagElement) {
        // Pacify PHP_CodeSniffer's unused variable check.
        assert($parser || $tagElement || true);
        $xPath = implode("/", $this->xPath);
        $depth = count($this->xPath) - 1;
        $index = count($this->contentView) - 1;
        $contentSchema = isset($this->schemaAttributes[$this->currentSchemaType . "_" . '//' . $xPath]) ? $this->schemaAttributes[$this->currentSchemaType . "_" . '//' . $xPath] : null;
        $attributeType = isset($contentSchema['type']) ? $contentSchema['type'] : null;
        $attributeName = isset($contentSchema['name']) ? $contentSchema['name'] : null;
        if(isset($this->xpathCount[$xPath])) {
            $pathCount = $this->xpathCount[$xPath];
            $this->xpathCount[$xPath] = $pathCount + 1;
            $xPath = $xPath . '-' . $pathCount;
        }
        else{
            $this->xpathCount[$xPath] = 1;
        }
        $contentKey = $xPath;
        if(!is_null($attributeName)){
            if($attributeType !== self::LIST_ATTRIBUTE) {
                $this->isListOption = false;
            }
            if($attributeType === self::FILE_ATTRIBUTE) {
                $href = $this->resourcePath . $this->fileName;
                $fileName = rawurldecode($this->fileName);
                $this->contentView[$contentKey] = array('name' => $attributeName, 'value' => $fileName, 'type' => self::FILE_ATTRIBUTE, 'filePath' => $href, 'xPath' => $xPath, 'depth' => $depth, 'position' => $this->filePosition);
                $this->currentElement = '';
                $this->fileName = '';
            }
            else if($attributeType === self::LIST_ATTRIBUTE) {
                $originalPath = Text::getSubstringBefore($contentKey, '-');
                $listKey = $this->contentView[$originalPath]['latestListKey'];
                if($this->lastListNodeAdded !== $listKey) {
                    $this->isListOption = false;
                }
                if($originalPath && $this->isListOption) {
                    $this->contentView[$listKey]['value'] .= ',' . html_entity_decode($this->listOption);
                    unset($this->contentView[$contentKey]);
                }
                else {
                    if($originalPath) {
                        $this->contentView[$originalPath]['latestListKey'] = $contentKey;
                    }
                    $this->contentView[$contentKey] = array('name' => $attributeName, 'value' => html_entity_decode($this->listOption), 'type' => self::LIST_ATTRIBUTE, 'xPath' => $xPath, 'depth' => $depth, 'latestListKey' => $contentKey);
                    $this->isListOption = true;
                    $this->lastListNodeAdded = $contentKey;
                }
                $this->listOption = '';
            }
            else if($attributeType === self::BOOLEAN_ATTRIBUTE) {
                $this->contentView[$contentKey] = array('name' => $attributeName, 'value' => $this->schemaData, 'type' => self::CHECKBOX_ATTRIBUTE, 'xPath' => $xPath, 'depth' => $depth);
            }
            else {
                if($this->schemaData !== '')
                    $this->contentView[$contentKey] = array('name' => $attributeName, 'type' => self::DISPLAY_ATTRIBUTE, 'value' => $this->schemaData, 'xPath' => $xPath, 'depth' => $depth);
            }
        }
        $this->schemaData = '';
        array_pop($this->xPath);
    }

    /**
    * This method finds and replaces the answer-link tag with anchor tag
    * @param string $contentXML Content xml
    * @return string Processed Content xml
    */
    function processXMLContent($contentXML) {
        $answerLinks = $answerId = $contents = $title = $target = $rel = $anchor = array();
        preg_match_all(self::ANSWER_LINK_REGEX_PATTERN, $contentXML, $answerLinks);
        if(!empty($answerLinks[0])) {
            foreach($answerLinks[0] as $answerLink) {
                preg_match(self::ANSWER_LINK_ID_REGEX_PATTERN, $answerLink, $answerId);
                preg_match(self::ANSWER_LINK_CONTENTS_REGEX_PATTERN, $answerLink, $contents);
                preg_match(self::ANSWER_LINK_TITLE_REGEX_PATTERN, $answerLink, $title);
                preg_match(self::ANSWER_LINK_TARGET_REGEX_PATTERN, $answerLink, $target);
                preg_match(self::ANSWER_LINK_REL_REGEX_PATTERN, $answerLink, $rel);
                preg_match(self::ANSWER_LINK_ANCHOR_REGEX_PATTERN, $answerLink, $anchor);
                $target[1] == null ? $target[1] = "" : $target[1] = ' target= "'.  strip_tags(html_entity_decode($target[1])) . '"';
                $rel[1] == null ? $rel[1] = "" : $rel[1] = ' rel= "'.  strip_tags(html_entity_decode($rel[1])) . '"';
                $title[1] == null ? $title[1] = "" : $title[1] = ' title= "'.  strip_tags(html_entity_decode($title[1])) . '"';
                $anchor[1] == null ? $anchor[1] = "" : $anchor[1] = strip_tags(html_entity_decode($anchor[1]));
                $link = '<a href=' . '"/app/' . Config::getConfig(CP_ANSWERS_DETAIL_URL) . '/a_id/' . $answerId[1] . $anchor[1] . '"' . $title[1] .  $target[1] . ' ' . $rel[1] .'>' . (empty($contents[1]) ? Config::getMessage(CLICK_HERE_LC_LBL) : html_entity_decode($contents[1])) . '</a>';
                $contentXML = str_replace($answerLink, $link, $contentXML);
            }
        }

        $docIdLinks = $docIds = $docIdAns = array();
        preg_match_all(self::DOC_LINK_REGEX_PATTERN, $contentXML, $docIdLinks);
        if(!empty($docIdLinks[0])) {
            foreach($docIdLinks[0] as $docIdLink){
                preg_match(self::DOC_LINK_ID_REGEX_PATTERN, $docIdLink, $docId);
                array_push($docIds, "'" . $docId[1] . "'");
            }
            $docIdQuery = implode(",", $docIds);
            $okcsModel = new \RightNow\Models\Okcs();
            $docIdAns = $okcsModel->getAnswerIds($docIdQuery);

            foreach($docIdLinks[0] as $docIdLink){
                preg_match(self::DOC_LINK_ID_REGEX_PATTERN, $docIdLink, $docId);
                preg_match(self::ANSWER_LINK_CONTENTS_REGEX_PATTERN, $docIdLink, $contents);
                preg_match(self::ANSWER_LINK_TITLE_REGEX_PATTERN, $docIdLink, $title);
                preg_match(self::ANSWER_LINK_TARGET_REGEX_PATTERN, $docIdLink, $target);
                preg_match(self::ANSWER_LINK_REL_REGEX_PATTERN, $docIdLink, $rel);
                preg_match(self::ANSWER_LINK_ANCHOR_REGEX_PATTERN, $docIdLink, $anchor);
                $answerId = $docIdAns["'" . $docId[1] . "'"];
                $answerId = $answerId === null ? 0 : $answerId;
                $target[1] == null ? $target[1] = "" : $target[1] = ' target= "'.  strip_tags(html_entity_decode($target[1])) . '"';
                $rel[1] == null ? $rel[1] = "" : $rel[1] = ' rel= "'.  strip_tags(html_entity_decode($rel[1])) . '"';
                $title[1] == null ? $title[1] = "" : $title[1] = ' title= "'.  strip_tags(html_entity_decode($title[1])) . '"';
                $anchor[1] == null ? $anchor[1] = "" : $anchor[1] = strip_tags(html_entity_decode($anchor[1]));
                $link = '<a href=' . '"/app/' . Config::getConfig(CP_ANSWERS_DETAIL_URL) . '/a_id/' . $answerId . $anchor[1] . '"' . $title[1] .  $target[1] . ' ' . $rel[1] .'>' . (empty($contents[1]) ? Config::getMessage(CLICK_HERE_LC_LBL) : html_entity_decode($contents[1])) . '</a>';
                $contentXML = str_replace($docIdLink, $link, $contentXML);
            }
        }

        return $contentXML;
    }

    /**
    * This method handles all of the text between elements (character data, or CDATA in XML terminology)
    * @param object $parser Reference to the XML parser
    * @param string $data Text between elements
    */
    function dataHandler($parser, $data){
        // Pacify PHP_CodeSniffer's unused variable check.
        assert($parser || true);
        $attributeType = $this->schemaAttributes[$this->currentSchemaType . "_" . '//' . implode("/", $this->xPath)]['type'];
        if ($attributeType === self::FILE_ATTRIBUTE) {
            $this->fileName .= rawurlencode($data);
        }
        else {
            if($attributeType === self::DATE_ATTRIBUTE || $attributeType === self::DATETIME_ATTRIBUTE || $attributeType === self::TIME_ATTRIBUTE) {
                if(Text::stringContains($data, 'ok-highlight-sentence')) {
                    $actualData = $data;
                    $data = $dateValue = strip_tags($actualData);
                }
                $data = self::formatOkcsDate($data, $attributeType);
                if(!is_null($actualData))
                    $data = str_replace($dateValue, $data, $actualData);
            }
            else if($attributeType === self::TEXT_AREA) {
                $data = str_replace('<OKCS_CONTENT_CR/>', "\r", $data);
                $data = str_replace('<OKCS_CONTENT_LF/>', "\n", $data);
                $data = nl2br($data);
            }
            else if($attributeType !== self::RICHTEXT_ATTRIBUTE && $attributeType !== self::TEXTFIELD_ATTRIBUTE) {
                $data = html_entity_decode($data);
            }
            else if($attributeType === self::RICHTEXT_ATTRIBUTE) {
                $data = str_replace('<OKCS_CONTENT_CR/>', "\r", $data);
                $data = str_replace('<OKCS_CONTENT_LF/>', "\n", $data);
            }
            
            if ($attributeType === null){
                if($this->currentElement !== self::DISPLAY_ATTRIBUTE)
                    $data = '';
                else
                    $this->listOption = $data;
            }
            if($data !== '' && $data !== null)
                $this->schemaData .= $data;
        }
        $data = '';
    }

    /**
    * This method populates an array of schema attribute objects.
    * @param object $schemaAttribute Info about the schema attributes
    * @param string $schemaType Content schema type
    */
    function getSchemaNodeAttributes($schemaAttribute, $schemaType) {
        $this->schemaAttributes[$schemaType . "_" . $schemaAttribute->xpath] = array('name' => $schemaAttribute->name, 'type' => $schemaAttribute->schemaAttrType);
        if ($schemaAttribute->children !== null && count($schemaAttribute->children) > 0) {
            foreach($schemaAttribute->children as $childItem) {
                if ($childItem->children !== null && count($childItem->children) > 0) {
                    self::getSchemaNodeAttributes($childItem, $schemaType);
                }
                else {
                    $this->schemaAttributes[$schemaType . "_" . $childItem->xpath] = array('name' => $childItem->name, 'type' => $childItem->schemaAttrType);
                }
            }
        }
        else {
            $this->schemaAttributes[$schemaType . "_" . $schemaAttribute->xpath] = array('name' => $schemaAttribute->name, 'type' => $schemaAttribute->schemaAttrType);
        }
    }

    /**
     * Return a formatted date/time
     * @param staring $date Document date value
     * @param string $attributeType Type of schema attribute. possible values are 'DATE', 'DATETIME' and 'TIME'.
     * @return string The formatted date/time string
     */
    function formatDate($date, $attributeType) {
        date_default_timezone_set(Config::getConfig(TZ_INTERFACE));
        $dateFormat = $attributeType === self::DATE_ATTRIBUTE ? 'm/d/Y' : ($attributeType === self::DATETIME_ATTRIBUTE ? 'm/d/Y H:i A' : 'H:i A');
        return date($dateFormat, date_format(date_create_from_format('Y-m-d H:i:s T', $date), 'UTC'));
    }

    /**
    * Method returns file descriptions
    * @return array Array of file description
    */
    function getFileDescription() {
        return array(
            'document_plain_green' => Config::getMessage(PLAIN_GREEN_FILE_LBL),
            'cms_xml' => Config::getMessage(CMS_XML_FILE_LBL),
            'doc' => Config::getMessage(DOCUMENT_FILE_LBL),
            'html' => Config::getMessage(HTML_FILE_LBL),
            'image' => Config::getMessage(IMAGE_FILE_LBL),
            'iqxml' => Config::getMessage(IQXML_FILE_LBL),
            'ms_excel' => Config::getMessage(MS_EXCEL_FILE_LBL),
            'ms_powerpoint' => Config::getMessage(MS_POWERPOINT_FILE_LBL),
            'ms_word' => Config::getMessage(MS_WORD_FILE_LBL),
            'news' => Config::getMessage(NEWS_FILE_LBL),
            'pdf' => Config::getMessage(PDF_FILE_LBL),
            'rtf' => Config::getMessage(RTF_FILE_LBL),
            'table' => Config::getMessage(TABLE_FILE_LBL),
            'text' => Config::getMessage(TEXT_FILE_LBL),
            'xls' => Config::getMessage(SPREADSHEET_FILE_LBL),
            'intent' => Config::getMessage(INTENT_ICON_LBL),
            'message' => Config::getMessage(MESSAGE_ICON_LBL),
            'messages' => Config::getMessage(MESSAGES_ICON_LBL),
            'trail_arrow' => Config::getMessage(TRAIL_ARROW_ICON_LBL),
            'wizard_marker' => Config::getMessage(WIZARD_MARKER_ICON_LBL),
            'wizard_marker_rtl' => Config::getMessage(WIZARD_MARKER_RTL_ICON_LBL)
        );
    }

    /**
    * This method returns array of valid attributes
    * @param array $contentAttributes Array of content attributes
    * @return array Array of valid content attributes
    */
    function getValidContentAttributes(array $contentAttributes) {
        if(is_array($contentAttributes) && !is_null($contentAttributes)) {
            foreach($contentAttributes as $key => $attribute) {
                if(!is_null($attribute['type']) && ( $attribute['type'] === self::FILE_ATTRIBUTE || $attribute['type'] === self::LIST_ATTRIBUTE ) && empty( $attribute['value'] ))
                    unset($contentAttributes[$key]);
            }
        }
        $contentAttributes['xpathCount'] = $this->xpathCount;
        return $contentAttributes;
    }

    /**
    * This method is used to construct the query url 
    * for getChannelCategories call
    * @param array $queryParameters Array contains all the filter data with which the result is to be sorted
    * @return string Query URL
    */
    private function constructChannelCategoriesQuery(array $queryParameters) {
        $queryUrl = '';
        if($queryParameters['externalType'] !== '') {
            $queryUrl .= 'externalType+eq+' . '"' . $queryParameters['externalType'] . '"';
        }
        if($queryParameters['orderBy'] !== '') {
            $queryUrl .= '&orderBy=' . $queryParameters['orderBy'];
        }
        if($queryParameters['dateAdded'] !== '') {
            $queryUrl .= '&dateAdded=' . $queryParameters['dateAdded'];
        }
        if($queryParameters['offset'] !== '') {
            $queryUrl .= '&offset=' . $queryParameters['offset'];
        }
        if($queryParameters['limit'] !== '') {
            $queryUrl .= '&limit=' . $queryParameters['limit'];
        }
        $queryUrl = '?q=' . $queryUrl;
        return $queryUrl;
    }

    /**
    * This method is used to construct the query url
    * for REST API Requests
    * @param array $queryParameters Array contains all the filter data with which the result is to be sorted
    * @return string Query URL
    */
    static function getRestQueryString(array $queryParameters){
        $queryUrl = '';
        if($queryParameters['mode'] === 'sortArticles') {
            if(isset($queryParameters['contentType']) && !empty($queryParameters['contentType'])) {
                if(Text::stringContains($queryParameters['contentType'], '","')) {
                    $queryUrl .= 'contentType.referenceKey+in+("' . $queryParameters['contentType'] . '")';
                }
                else {
                    $queryUrl .= 'contentType.referenceKey+eq+"' . $queryParameters['contentType'] . '"';
                }
            }
            if(isset($queryParameters['category']) && $queryParameters['category'] !== '') {
                $queryUrl .= $queryUrl === '' ? 'categories.referenceKey+matchAll+("' . $queryParameters['category'] . '")' : '+and+categories.referenceKey+matchAll+("' . $queryParameters['category'] . '")';
            }
            if(isset($queryParameters['categoryExtId']) && $queryParameters['categoryExtId'] !== '') {
                $queryUrl .= $queryUrl === '' ? 'categories.externalId+eq+"' . $queryParameters['categoryExtId'] . '"' : '+and+categories.externalId+eq+"' . $queryParameters['categoryExtId'] . '"';
            }
            if($queryParameters['contentState'] !== '') {
                if($queryUrl === '') {
                    $queryUrl .= 'filterMode.contentState+eq+' . '"' . $queryParameters['contentState'] . '"';
                }
                else {
                    $queryUrl .= '+and+filterMode.contentState+eq+' . '"' . $queryParameters['contentState'] . '"';
                }
            }
            if($queryParameters['orderBy'] !== '') {
                $queryUrl .= '&orderBy=' . $queryParameters['orderBy'];
            }
            if($queryParameters['offset'] !== '') {
                $queryUrl .= '&offset=' . $queryParameters['offset'];
            }
            if($queryParameters['limit'] !== '') {
                $queryUrl .= '&limit=' . $queryParameters['limit'];
            }
            $queryUrl = '?q=' . $queryUrl;
        }
        else if($queryParameters['mode'] === 'getArticle') {
            if($queryParameters['contentState'] !== '') {
                $queryUrl .= '?contentState=' . $queryParameters['contentState'];
            }
        }
        else if($queryParameters['mode'] === 'user') {
            $queryUrl .= '?q=key+eq+' . '"' . $queryParameters['key'] . '"';
            if($queryParameters['userInformation.recordId'] !== null) {
                $queryUrl .= '+and+userInformation.recordId+eq+' . '"' . $queryParameters['userInformation.recordId'] . '"';
            }
        }
        else if($queryParameters['mode'] === 'sortRecommendations'){
            if(!empty($queryParameters['orderBy']))
                $queryUrl .= '&orderBy=' . $queryParameters['orderBy'];
            if($queryParameters['offset'] !== '')
                $queryUrl .= '&offset=' . $queryParameters['offset'];
            if($queryParameters['limit'] !== '')
                $queryUrl .= '&limit=' . $queryParameters['limit'];
            $queryUrl = '?q=requestedByUserId+eq+"' . $queryParameters['userID'] . '"' . $queryUrl;
        }
        return $queryUrl;
    }
    
    /**
    * This method is used to log
    * Errors, ACS Events and Timing Info
    * @param array $logData Array containing all response, requestUrl, requestOrigin, acsEventName, apiDuration, postData, and tokenHeader
    */
    static function eventLog(array $logData) {
        $level = 'info';
        if (isset($logData['response']->errors) && $logData['response']->errors !== null) {
            $level = 'error';
            if ($logData['apiDuration'] > Config::getConfig(OKCS_API_TIMEOUT)){
                // code sniffer isssue
                Api::phpoutlog($logData['requestOrigin'] . " request at: " . $logData['requestUrl'] . " was timed out");
            }
            else{
                Api::phpoutlog($logData['requestOrigin'] . " request - Url: " . $logData['requestUrl']);
            }
            Api::phpoutlog($logData['requestOrigin'] . " request - response: " . $logData['response']);
            ActionCapture::instrument($logData['acsEventName'], 'Request', $level, array('RequestUrl' => $logData['requestUrl'], 'RequestOrigin' => $logData['requestOrigin'], 'ResponseError' => json_encode($logData['response']->errors)), ceil($logData['apiDuration'] * 1000));
        }
        else
            ActionCapture::instrument($logData['acsEventName'] . '-timing', 'Request', $level, array('RequestUrl' => $logData['requestUrl'], 'RequestOrigin' => $logData['requestOrigin']), ceil($logData['apiDuration'] * 1000));
    }

    /**
    * Function to set API timings and status code into In process cache
    * @param string $timingCacheKey Timing cache key
    * @param string $timingDetails Timing details
    */
    public static function setTimingToCache($timingCacheKey, $timingDetails) {
        $cacheTimingArray = Framework::checkCache($timingCacheKey);

        if(is_null($cacheTimingArray))
            $cacheTimingArray = array();

        foreach($timingDetails as $timeDetailKey => $timeDetailValue) {
            array_push($cacheTimingArray, $timeDetailKey, $timeDetailValue);
            Framework::setCache($timingCacheKey, $cacheTimingArray, true);
        }
    }

    /**
    * Function to get API timings and status code From In process cache
    * @param string $timingCacheKey Timing cache key
    * @return string|null Cached response or null when key not fund
    */
    public static function getCachedTimings($timingCacheKey) {
        $cacheResponse = Framework::checkCache($timingCacheKey);
        if (!is_null($cacheResponse))
            return $cacheResponse;
    }
    
    /**
    * Return a formatted date/time based on the time zone
    * @param string $date Date value
    * @param string $attributeType Type of schema attribute. possible values are 'DATE', 'DATETIME' and 'TIME'.
    * @return string The formatted date/time string
    */
    public static function formatOkcsDate($date, $attributeType) {
        if($date) {
            $timeFormat = null;
            $date = str_replace("Etc/", "", $date);
            try {
                $date = new \DateTime($date);
            }
            catch(\Exception $e) {
                return $date;
            }
            $date->setTimezone(new \DateTimeZone(Config::getConfig(TZ_INTERFACE)));
            $CI = get_instance();
            if($attributeType === self::DATETIME_ATTRIBUTE || $attributeType === self::TIME_ATTRIBUTE) {
                $timeFormat = $CI->cpwrapper->cpPhpWrapper->getDtfTime();
            }
            $dateFormat = ($attributeType === self::TIME_ATTRIBUTE) ? '' : $CI->cpwrapper->cpPhpWrapper->getDtfShortDate();
            $timestamp = (int)$date->format('U');
            $date = Framework::formatDate($timestamp, $dateFormat, $timeFormat, true);
        }
        return $date;
    }

    /**
     * Returns the interface locale.
     * @return string Interface locale
     */
    public static function getInterfaceLocale() {
        $langCode = Text::getLanguageCode();
        if(strtolower(substr($langCode, 0, 3)) === 'cl-') {
            $langId = \RightNow\Api::lang_id(str_replace("-", "_", $langCode));
            $langConfig = json_decode(Config::getConfig(LANGUAGE_CUSTOM_ID_MAPPING));
            if($langConfig && $langConfig->$langId) {
                $langCode = str_replace("_", "-", $langConfig->$langId);
            }
            else {
                $langCode = "en-US";
            }
        }
        return $langCode;
    }

    /**
    * Returns title for a particular categoryRecordId
    * @param string $categoryRecordId Id specific to a product or category.
    * @return string Title for the categoryRecordId
    */
    public static function getProductCategoryTitle($categoryRecordId) {
        $okcsModel = new \RightNow\Models\Okcs();
        $prodCat = $okcsModel->getProductCategoryDetails($categoryRecordId);
        return $prodCat->name;
    }

    /**
    * Returns category external id for the categoryRecordId
    * @param string $categoryRecordId Id specific to a product or category.
    * @return int CategoryId for the categoryRecordId
    */
    public static function getProductCategoryId($categoryRecordId) {
        $okcsModel = new \RightNow\Models\Okcs();
        $prodCat = $okcsModel->getProductCategoryDetails($categoryRecordId);
        return $prodCat->externalId;
    }

    /**
     * Returns an array of prodcat levels for the product/category object.
     * @param object $categoryObj Category object
     * @return array The prodcat levels
     */
    public function getCategoryHierarchy($categoryObj) {
        $levels = array();
        if(isset($categoryObj->parents) && $categoryObj->parents && is_array($categoryObj->parents) && count($categoryObj->parents) > 0) {
            for($i = 0; $i < count($categoryObj->parents); $i++) {
                array_push($levels, array("id" => $categoryObj->parents[$i]->referenceKey, "label" => $categoryObj->parents[$i]->name, "externalId" => $categoryObj->parents[$i]->externalId));
            }
        }
        array_push($levels, array("id" => $categoryObj->referenceKey, "label" => $categoryObj->name));
        return $levels ?: array();
    }

    /**
     * Returns an array of selected products, categories, doctypes and collections specific to render view
     * @param string $explodedCommaArray Comma separated selected facet reference keys
     * @param array $prodCatList Array of chosen product category details
     * @param array $facets Array of facets from search results
     * @return array Array of facet detail objects specific to render view
     */
    public function createSelectedFacetDetailObject($explodedCommaArray, $prodCatList, $facets){
        $prodArray = array();$categArray = array();$docTypeArray = array();$collArray = array();
        $facetObject = [];$facetArray = [];$finalObject = [];
        // construct an array to be used to create the view
        foreach($explodedCommaArray as $item) {
            if(strpos($item, 'CMS-CATEGORY_REF') !== false){
                $explodedDotArray = (explode('.', $item));
                $refKey = $explodedDotArray[count($explodedDotArray) - 1];
                if(isset($prodCatList->items) && is_array($prodCatList->items)){
                    foreach($prodCatList->items as $category) {
                        if($category->externalType === 'CATEGORY') {
                            if($category->referenceKey === $refKey) {
                                $name = $item . ':' . $category->name;
                                array_push($categArray, $name);
                                $facetArray[$item] = $category->name;
                                break;
                            }
                        }
                    }
                }
            }
            else if(strpos($item, 'CMS-PRODUCT') !== false){
                $explodedDotArray = (explode('.', $item));
                $refKey = $explodedDotArray[count($explodedDotArray) - 1];
                if(isset($prodCatList->items) && is_array($prodCatList->items)){
                    foreach($prodCatList->items as $category) {
                        if($category->externalType === 'PRODUCT') {
                            if($category->referenceKey === $refKey) {
                                $name = $item . ':' . $category->name;
                                array_push($prodArray, $name);
                                $facetArray[$item] = $category->name;
                                break;
                            }
                        }
                    }
                }
            }
            else if(strpos($item, 'DOC_TYPES') !== false && is_array($facets)){
                foreach ($facets as $facetItem) {
                    if($facetItem->id === 'DOC_TYPES' && isset($facetItem->children) && is_array($facetItem->children)) {
                        foreach($facetItem->children as $childItem){
                            if($childItem->id === $item) {
                                $name = $item . ':' . $childItem->desc;
                                array_push($docTypeArray, $name);
                                $facetArray[$item] = $childItem->desc;
                                break;
                            }
                        }
                    }
                }
            }
            else if(strpos($item, 'COLLECTIONS') !== false && is_array($facets)){
                foreach ($facets as $facetItem) {
                    if($facetItem->id === 'COLLECTIONS' && isset($facetItem->children) && is_array($facetItem->children)) {
                        foreach($facetItem->children as $childItem){
                            if($childItem->id === $item) {
                                $name = $item . ':' . $childItem->desc;
                                array_push($collArray, $name);
                                $facetArray[$item] = $childItem->desc;
                                break;
                            }
                        }
                    }
                }
            }
        }//end foreach
        $facetObject['CMS-CATEGORY_REF'] = $categArray;
        $facetObject['CMS-PRODUCT'] = $prodArray;
        $facetObject['DOC_TYPES'] = $docTypeArray;
        $facetObject['COLLECTIONS'] = $collArray;
        $finalObject['facet'] = $facetArray; // returns necessary structure for Facet widget
        $finalObject['facetFilter'] = $facetObject; // returns necessary structure for Facet Filter widget
        return $finalObject;
    }

    /**
     * Method to construct an array of referenceKeys only from facet url.
     * @param string $url Url
     * @return array referenceKey list
     */
    public function getCategList($url) {
        $alteredUrl = str_replace('CMS-PRODUCT.', '', $url);
        $alteredUrl = str_replace('CMS-CATEGORY_REF.', '', $alteredUrl);
        $explodedAlteredCommaArray = (explode(',', $alteredUrl));
        $prodCategArray = array();
        // construct an array of refKeys only to fetch category names from IM API
        foreach($explodedAlteredCommaArray as $item) {
            $explodedDotArray = (explode('.', $item));
            $refKey = $explodedDotArray[count($explodedDotArray) - 1];
            array_push($prodCategArray, $refKey);
        }
        return $prodCategArray;
    }
}
