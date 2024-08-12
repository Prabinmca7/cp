<?php

namespace RightNow\Internal\Sql;

use RightNow\Utils\Framework;

require_once DOCROOT . '/views/view_utils.phph';

final class Report{
    const ANSWERS_SPECIAL_SETTINGS_FILTER_NAME = 'answers.special_settings';
    // @codingStandardsIgnoreStart
    //Keep old constant in for compatibility
    const answersSpecialSettingsFilterName = self::ANSWERS_SPECIAL_SETTINGS_FILTER_NAME;
    // @codingStandardsIgnoreEnd

    /**
     * Wrapper for call in view_utils
     * @param int $reportID Report ID to retrieve
     * @return array Details about report
     */
    public static function _report_get($reportID){
        return _report_get($reportID);
    }

    /**
     * Gets search filters for the provided report
     * @param int $reportID Report ID to use
     * @return array Filters for the provided report
     */
    public static function view_get_srch_filters($reportID){
        return view_get_srch_filters($reportID);
    }

    /**
     * Gets report settings
     * @param int $reportID Report ID
     * @param string $filepath File Path to use
     * @param int $opts VIEW_BIG_DATE_OPTS value to use to handle and display post 2038 dates in CP reports.
     * @return array Settings for the report
     */
    public static function view_get_grid_info($reportID, $filepath=null, $opts=0){
        return view_get_grid_info($reportID, $filepath, $opts);
    }

    /**
     * Executes the given report
     * @param int $reportID Report ID to execute
     * @param array|null $queryArguments Filters and arguments for the report
     * @return array Report data
     */
    public static function view_get_query_cp($reportID, $queryArguments){
        return view_get_query_cp($reportID, $queryArguments);
    }

    /**
     * Gets alias of table in report, if it exists
     * @param int $reportID Report ID to check
     * @param int $table Table to check
     * @return null|string Null if table doesn't exist in report, string alias if it does
     */
    public static function view_tbl2alias($reportID, $table){
        return view_tbl2alias($reportID, $table);
    }

    /**
     * Verifies that the report is executable on the current interface
     * @param int $reportID Report ID to check
     * @return bool Whether report can be executed
     */
    public static function verify_interface($reportID){
        return verify_interface($reportID);
    }

    /**
     * Get related searches to the search term provided
     * @param string $searchTerm The searched on keyword from which to get related searches
     * @return array List of related searches
     */
    public static function getSimilarSearches($searchTerm){
        $cacheKey = "getSimilarSearches-$searchTerm";

        if(($suggestedSearchData = Framework::checkCache($cacheKey)) !== null){
            return $suggestedSearchData;
        }
        $searchTerm = Framework::escapeForSql(sort_word_string(trim(strip_affixes($searchTerm))));

        $interfaceID = intf_id();
        $query = "SELECT s2.srch FROM similar_searches s1, similar_searches s2, similar_search_links sl
                  WHERE s1.id=sl.to_id AND sl.from_id=s2.id AND s1.stem='$searchTerm'
                  AND s2.interface_id = $interfaceID AND s1.interface_id = $interfaceID AND sl.interface_id = $interfaceID
                  ORDER BY sl.ml_weight DESC";

        $si = sql_prepare($query);
        sql_bind_col($si, 1, BIND_NTS, 255);

        $suggestedSearchData = array();
        $rowCount = 0;
        while(($row = sql_fetch($si)) && ($rowCount < 7)){
            $suggestedSearchData[] = $row[0];
            $rowCount++;
        }
        sql_free($si);
        Framework::setCache($cacheKey, $suggestedSearchData);
        return $suggestedSearchData;
    }

    /**
     * Get topic words for the search term provided
     * @param string $searchTerm The searched on keyword from which to get topic words
     * @return array List of topic words
     */
    public static function getTopicWords($searchTerm){
        $topicWordData = array();
        if(!icache_int_get(ICACHE_TOPIC_WORDS_EXIST)){
            return $topicWordData;
        }
        $interfaceID = intf_id();

        $visQuery = bit_list_to_where_clause(contact_answer_access(), "a.access_mask");

        if($visQuery) {
            $visQuery = "OR " . $visQuery;
        }

        if(isset($searchTerm) && $searchTerm !== "")
        {
            $keywords = preg_split("/[\s,]+/", strip_affixes($searchTerm));
            $wordList = '';
            foreach($keywords as $element)
            {
                $wordList .= "'" . Framework::escapeForSql($element) . "',";
            }
            $keywordCount = count($keywords);
            if($keywordCount > 1)
            {
                for($i = 0; $i < $keywordCount; $i++)
                {
                    $mwEscapedElement = Framework::escapeForSql($keywords[$i]);
                    //5-words is our limit
                    for($j = $i + 1; ($j < $keywordCount) && ($j < $i + 5); $j++)
                    {
                        $mwEscapedElement .= " " . Framework::escapeForSql($keywords[$j]);
                        $wordList .= "'$mwEscapedElement',";
                    }
                }
            }

            $wordListSize = strlen($wordList);
            $wordList[$wordListSize - 1] = " ";
            $sql = sprintf("SELECT DISTINCT t.tw_type, t.a_id, a.summary, t.title, t.text, t.url, a.type, a.url
                FROM topic_words_phrases ti, topic_words t
                LEFT OUTER JOIN answers a ON a.a_id = t.a_id
                WHERE t.interface_id = $interfaceID
                AND (a.status_type = %d OR a.status_type IS NULL)
                AND (ti.stem IN (%s) AND t.state = 1
                AND ti.tw_id = t.tw_id) AND (a.access_mask IS NULL %s)
                UNION SELECT DISTINCT t.tw_type, t.a_id, a.summary, t.title, t.text, t.url, a.type, a.url
                FROM topic_words_phrases ti, topic_words t
                LEFT OUTER JOIN answers a ON a.a_id = t.a_id
                WHERE t.interface_id = $interfaceID AND (a.status_type = %d OR a.status_type IS NULL) AND (t.state = 2) AND (a.access_mask IS NULL %s)",
                STATUS_TYPE_PUBLIC, $wordList, $visQuery, STATUS_TYPE_PUBLIC, $visQuery);
        }
        else
        {
            $sql = sprintf("SELECT t.tw_type, t.a_id, a.summary, t.title, t.text, t.url, a.type, a.url
                FROM topic_words t
                LEFT OUTER JOIN answers a ON a.a_id = t.a_id
                WHERE t.interface_id = %d
                AND (a.status_type = %d OR a.status_type IS NULL)
                AND t.state = 2 AND (a.access_mask IS NULL %s)", $interfaceID, STATUS_TYPE_PUBLIC, $visQuery);
        }

        $si = sql_prepare($sql);
        $i = 1;
        sql_bind_col($si, $i++, BIND_INT, 0);
        sql_bind_col($si, $i++, BIND_INT, 0);
        sql_bind_col($si, $i++, BIND_NTS, 255);
        sql_bind_col($si, $i++, BIND_NTS, 255);
        sql_bind_col($si, $i++, BIND_NTS, 255);
        sql_bind_col($si, $i++, BIND_NTS, 255);
        sql_bind_col($si, $i++, BIND_INT, 0);
        sql_bind_col($si, $i++, BIND_NTS, 255);

        $topicWordData = array();
        while(list($topicType, $answerID, $summary, $title, $text, $topicUrl, $answerType, $answerUrl) = sql_fetch($si))
        {
            $icon = '';
            if($answerType === ANSWER_TYPE_HTML)
                $icon = Framework::getIcon('RNKLANS');
            else if ($answerType === ANSWER_TYPE_ATTACHMENT || $answerType === ANSWER_TYPE_URL)
                $icon = Framework::getIcon($answerUrl);
            else if($topicUrl != "")
                $icon = Framework::getIcon($topicUrl);
            $topicWordTitle = $title != '' ? $title : $summary;

            $topicWordItem = array('url' => $topicUrl != "" ? $topicUrl : \RightNow\Utils\Url::getShortEufAppUrl('sameAsCurrentPage', \RightNow\Utils\Config::getConfig(CP_ANSWERS_DETAIL_URL) . '/a_id/' . $answerID . \RightNow\Utils\Url::sessionParameter()),
                                   'title' => print_text2str($topicWordTitle, OPT_VAR_EXPAND | OPT_ESCAPE_SCRIPT | OPT_ESCAPE_HTML),
                                   'text' => print_text2str($text, OPT_VAR_EXPAND | OPT_ESCAPE_SCRIPT | OPT_ESCAPE_HTML),
                                   'icon' => $icon
                                );
            array_push($topicWordData, $topicWordItem);
        }
        sql_free($si);
        return $topicWordData;
    }

    /**
     * Get product/category filters related to the search term and list of returned answers
     * @param string $searchTerm The searched on keyword from which to get related product/categories
     * @param int $type Type of suggestions to return, either HM_PRODUCTS or HM_CATEGORIES
     * @return array List of related product categories including their label and ID chain
     */
    public static function getSuggestedSearch($searchTerm, $type){
        // $type might be a string during testing
        $type = intval($type);
        $displayMax = \RightNow\Utils\Config::getConfig(MAX_SEARCH_SUGGESTIONS);
        $tempTableType = ($type === HM_PRODUCTS) ? PH_TYPE_PROD : PH_TYPE_CAT;
        $keywordTempTable = build_temp_index_hier($searchTerm);
        if($keywordTempTable === ""){
            return null;
        }
        $tempDb = \RightNow\Utils\Config::getConfig(temp_database_name);

        $sql = sprintf("SELECT l.label, m.lvl1_id, m.lvl2_id, m.lvl3_id, m.lvl4_id, m.lvl5_id, m.lvl6_id
                FROM labels l, hier_menus m, $tempDb.$keywordTempTable t
                WHERE l.label_id = t.id AND l.fld = 1 AND l.tbl = %s AND
                m.hm_type = $type AND m.id = t.id AND t.type = $tempTableType AND l.lang_id=%d ORDER BY t.weight DESC", TBL_HIER_MENUS, lang_id(LANG_DIR));

        if($displayMax)
            $sql .= " LIMIT $displayMax";

        $si = sql_prepare($sql);
        $i = 1;
        sql_bind_col($si, $i++, BIND_NTS, 41);
        sql_bind_col($si, $i++, BIND_INT, 0);
        sql_bind_col($si, $i++, BIND_INT, 0);
        sql_bind_col($si, $i++, BIND_INT, 0);
        sql_bind_col($si, $i++, BIND_INT, 0);
        sql_bind_col($si, $i++, BIND_INT, 0);
        sql_bind_col($si, $i++, BIND_INT, 0);

        $results = array();
        while($row = sql_fetch($si))
        {
            $idArray = array();
            //Prod/cats only go to level 6
            for($j = 1; $j <= 6; $j++)
            {
                if($row[$j] != "")
                    $idArray []= $row[$j];
                else
                    break;
            }
            $results []= array('label' => $row[0], 'id' => implode(",", $idArray));
        }
        sql_free($si);
        return $results;
    }

    /**
     * Includes HT Dig depedencies
     * @return void
     */
    public static function loadHTDigLibrary(){
        if(!extension_loaded('htdig')){
            if(CP_PHP_VERSION == 50600){
                load_lib('libhtdigphp' . sprintf(DLLVERSFX, MOD_HTDIG_BUILD_VER));
            }else{
                load_lib('libhtdigphp-'.CP_PHP_VERSION . sprintf(DLLVERSFX, MOD_HTDIG_BUILD_VER));
            }
        }
        if (IS_HOSTED && !IS_OPTIMIZED)
            require_once DOCROOT . '/include/src/htdig.phph';
        else if (!IS_HOSTED)
            require_once DOCROOT . '/include/htdig.phph';
    }

    /**
     * Wrapper for IAPI call to open HT Dig
     * @return string|null Error message or null on success
     */
    public static function htsearch_open(){
        $webIndexPath = webindex_path();
        $webIndexConfigFile = "$webIndexPath/webindex.conf";
        if(!\RightNow\Utils\FileSystem::isReadableFile($webIndexConfigFile)){
            return \RightNow\Utils\Config::getMessage(ERROR_READ_CONFIGURATION_FILE_MSG);
        }
        $restrict = \RightNow\Utils\Config::getConfig(WIDX_SEARCH_RESTRICT);
        $exclude = \RightNow\Utils\Config::getConfig(WIDX_SEARCH_EXCLUDE);
        $alwaysreturn = ' RNKLANS RNKLATTACH RNKLURL ';

        // EU_WIDX MODE can affect the search filters if it's zero or two
        $euWidxMode = \RightNow\Utils\Config::getConfig(EU_WIDX_MODE);
        if($euWidxMode === 0)
        {
            $restrict .= " RNKLANS RNKLATTACH RNKLURL ";
        }
        else if($euWidxMode === 2)
        {
            $exclude .= ' RNKLANS RNKLATTACH RNKLURL ';
            $alwaysreturn = '';
        }

        $statusResult = htsearch_open(array(
            'configFile' => $webIndexConfigFile,
            'logFile' => "$webIndexPath/logs/htsearch.log",
            'debugFile' => "$webIndexPath/logs/htsearch.debug.log",
            'DBpath' => $webIndexPath,
            'search_restrict' => $restrict,
            'search_exclude' => $exclude,
            'search_alwaysreturn' => $alwaysreturn,
            'keyword_factor' => \RightNow\Utils\Config::getConfig(SRCH_KEY_WEIGHT),
            'text_factor' => \RightNow\Utils\Config::getConfig(SRCH_BODY_WEIGHT),
            'title_factor' => \RightNow\Utils\Config::getConfig(SRCH_SUBJ_WEIGHT),
            'meta_description_factor' => \RightNow\Utils\Config::getConfig(SRCH_DESC_WEIGHT),
            'debug' => 0
        ));
        if($statusResult > 0){
            return null;
        }
        return self::getOpenHTDigErrorMessage($statusResult);
    }

    /**
     * Wrapper for IAPI method to query HT Dig
     * @param mixed $queryArguments Arguments to pass to HT Dig
     * @return mixed Result if IAPI
     */
    public static function htsearch_query($queryArguments){
        return htsearch_query($queryArguments);
    }

    /**
     * Wrapper for IAPI method to close htsearch
     * @return void
     */
    public static function htsearch_close(){
        htsearch_close();
    }

    /**
     * Wrapper for IAPI method
     * @param int $index Index of match
     * @return mixed IAPI method result
     */
    public static function htsearch_get_nth_match($index){
        return htsearch_get_nth_match($index);
    }

    /**
     * Gets the error string based on error type
     * @param int $htSearchOpenResult HTDIG flag
     * @return string Error message
     */
    private static function getOpenHTDigErrorMessage($htSearchOpenResult)
    {
        switch ($htSearchOpenResult)
        {
            case HTSEARCH_ERROR_INDEX_NOT_FOUND:
                return \RightNow\Utils\Config::getMessage(ERROR_EXTERNAL_DOC_INDEX_EXIST_MSG);
            case HTSEARCH_ERROR_CONFIG_READ:
                return \RightNow\Utils\Config::getMessage(ERROR_READ_CONFIGURATION_FILE_MSG);
            case HTSEARCH_ERROR_LOGFILE_OPEN:
                return \RightNow\Utils\Config::getMessage(ERROR_UNABLE_TO_OPEN_LOGFILE_MSG);
        }
        return \RightNow\Utils\Config::getMessage(ERR_OPEN_IDX_FAILED_MSG);
    }
}
