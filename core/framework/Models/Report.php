<?php

namespace RightNow\Models;

use RightNow\Api,
    RightNow\Utils\Text,
    RightNow\Utils\Config,
    RightNow\ActionCapture,
    RightNow\Utils\Framework,
    RightNow\Internal\Sql\Report as Sql;

require_once CORE_FILES . 'compatibility/Internal/Sql/Report.php';

define('CP_BIND_BIG_DATE', 13);

/**
 * Methods for the retrieval and manipulation of analytics reports.
 */
class Report extends Base
{
    protected $isHTDigLoaded;
    protected $returnData;
    protected $reportID;
    protected $appliedFilters;
    protected $appliedFormats;
    protected $viewDefinition;
    protected $viewDataColumnDefinition;
    protected $reportIsTypeExternalDocument = false;
    protected $reportIsJoinedOnClusterTree = false;
    protected $answerTableAlias;
    protected $incidentTableAlias;
    protected $answerIDList = array();
    protected $assetTableAlias;
    protected $socialCommentTableAlias;
    protected $socialQuestionTableAlias;

    const CP_VIEW_OPT_BIGDATE = 319;

    /**
     * Returns a default search filter. Filters of type int or text are also included.
     * @param int $reportID The report to check
     * @return array A default search type filter array if one exists
     */
    public function getSearchFilterTypeDefault($reportID)
    {
        $default = array();
        if($this->isReportWidx($reportID))
        {
            if(!Config::getConfig(MOD_RNANS_ENABLED))
            {
                return $this->getResponseObject(array(), 'is_array');
            }
            else
            {
                return $this->getResponseObject(array('fltr_id' => Config::getConfig(EU_WIDX_SEARCH_BY_DEFAULT)), 'is_array');
            }
        }
        $rtFilters = $this->getRuntimeFilters($reportID)->result;
        $first = true;
        if(is_array($rtFilters))
        {
            foreach($rtFilters as $key => $value)
            {
                if($value['data_type'] === VDT_INT || $value['data_type'] === VDT_VARCHAR)
                {
                    if($first)
                    {
                        $default = $rtFilters[$key];
                        $first = false;
                    }
                    if($value['default_value'])
                    {
                        $default = $rtFilters[$key];
                    }
                }
            }
        }
        $default['default_value'] = isset($default['default_value']) ? $default['default_value'] : "";
        return $this->getResponseObject($default, 'is_array');
    }


    /**
     * Gets the runtime filter by fltr_id
     * @param int $reportID The report to check
     * @param int $filterID The filter you need
     * @return array Data for a runtime filter
     */
    public function getFilterById($reportID, $filterID)
    {
        foreach($this->getRuntimeFilters($reportID)->result as $value)
        {
            if($value['fltr_id'] == $filterID)
            {
                return $this->getResponseObject($value, 'is_array');
            }
        }
        return $this->getResponseObject(null, null, "No filter with ID $filterID found on report $reportID");
    }

    /**
     * Returns a filter provided its name
     * @param int $reportID The report ID
     * @param string $filterName The name of the filter (i.e. prod_hier)
     * @param bool $matchExpression Whether to match against the filter column expression or not
     * @return array|null Array of the data or null if the filter doesn't exist in the report or if the $filterName is empty.
     */
    public function getFilterByName($reportID, $filterName, $matchExpression = true)
    {
        if(strlen($filterName) > 0) {
            foreach($this->getRuntimeFilters($reportID)->result as $value) {
                if($value['name'] === $filterName || ($matchExpression && Text::stringContains($value['expression1'], $filterName))) {
                    return $this->getResponseObject($value, 'is_array');
                }
            }
        }
        return $this->getResponseObject(null, null, "No filter with name $filterName found on report $reportID");
    }

    /**
     * Converts the profile search type to the filter type
     * @param int $reportID The report to check
     * @param int $searchValue The profile value of the search type
     * @return array|null A default search type filter if one exists, null otherwise
     */
    public function getSearchTypeFromValue($reportID, $searchValue)
    {
        switch ($searchValue)
        {
            case SRCH_TYPE_NL:
                $searchName = PSEUDO_SEARCH_NL;
                break;
            case SRCH_TYPE_FNL:
                $searchName = PSEUDO_SEARCH_FNL;
                break;
            case SRCH_TYPE_EX:
                $searchName = PSEUDO_SEARCH_EX;
                break;
            case SRCH_TYPE_CPX:
                $searchName = PSEUDO_SEARCH_CPX;
                break;
            default:
                $searchName = "";
        }
        return $this->getFilterByName($reportID, $searchName);
    }

    /**
     * Gets the runtime filter data for a report id of type int or varchar
     * @param int $reportID The report to check
     * @return array All search filters of type int or text
     */
    public function getRuntimeIntTextData($reportID)
    {
        $runtimeFilters = $this->getRuntimeFilters($reportID)->result;
        $filters = array();
        foreach($runtimeFilters as $key => $value)
        {
            if($value['data_type'] === CDT_INT || $value['data_type'] === CDT_VARCHAR)
            {
                $filters[$key] = $value;
            }
        }
        return $this->getResponseObject($filters, 'is_array');
    }

    /**
     * Gets the search filter data for a report id
     * @param int $reportID The report to check
     * @return array All search filters
     */
    public function getSearchFilterData($reportID)
    {
        $incidentAlias = $this->getIncidentAlias($reportID)->result;
        $answerAlias = $this->getAnswerAlias($reportID)->result;
        $filters = array();
        foreach($this->getRuntimeFilters($reportID)->result as $value)
        {
            if($answerAlias)
            {
                if((Text::stringContains($value['expression1'], PSEUDO_SEARCH_NL)) ||
                    Text::stringContains($value['expression1'], PSEUDO_SEARCH_FNL) ||
                    Text::stringContains($value['expression1'], PSEUDO_SEARCH_EX) ||
                    Text::stringContains($value['expression1'], PSEUDO_SEARCH_CPX))
                {
                    $filters []= $value;
                }
            }
            else if($incidentAlias)
            {
                if(Text::stringContains($value['expression1'], 'search_') || Text::stringContains($value['expression1'], 'ref_no'))
                {
                    $filters []= $value;
                }
            }
        }
        return $this->getResponseObject($filters, 'is_array');
    }

    /**
     * Returns a column's id and index, given its definition.
     * @param int $reportID The report id that contains the column
     * @param string $columnDefinition The column definition e.g. 'answers.a_id', 'incidents.ref_no'
     * @return array|null Array containing columnID and index members for the column or null if not found
     */
    public function getIndexOfColumnDefinition($reportID, $columnDefinition)
    {
        $cacheKey = "Report-Get-$reportID";
        if(!($report = Framework::checkCache($cacheKey)))
        {
            $report = Sql::_report_get($reportID);
            Framework::setCache($cacheKey, $report);
        }
        if(is_array($report) && $report['nodes'] && $report['nodes']['node_item0'] && $report['nodes']['node_item0']['cols'])
        {
            foreach($report['nodes']['node_item0']['cols'] as $column)
            {
                if($column['val'] === $columnDefinition)
                {
                    // index within report results is zero-based whereas display_order is one-based
                    return $this->getResponseObject(array('columnID' => $column['col_id'], 'index' => $column['display_order'] - 1), 'is_array');
                }
            }
        }
        return $this->getResponseObject(null, null, "No column definition $columnDefinition found for report ID $reportID");
    }

    /**
     * Returns the search type options list for external documents
     * @return array List of search options
     */
    public function getExternalDocumentSearchOptions()
    {
        return $this->getResponseObject(array(WIDX_ANY_SEARCH => Config::getMessage(ANY_LBL), WIDX_ALL_SEARCH => Config::getMessage(ALL_LBL), WIDX_COMPLEX_SEARCH => Config::getMessage(COMPLEX_LBL)), 'is_array');
    }

    /**
     * Returns the sort options list for external documents
     * @return array List of sort options
     */
    public function getExternalDocumentSortOptions()
    {
        return $this->getResponseObject(array(WIDX_SCORE_SORT     => Config::getMessage(SCORE_LBL),
                     WIDX_TIME_SORT      => Config::getMessage(TIME_LBL),
                     WIDX_TITLE_SORT     => Config::getMessage(TITLE_LBL),
                     WIDX_REV_TIME_SORT  => Config::getMessage(REVERSE_TIME_LBL),
                     WIDX_REV_TITLE_SORT => Config::getMessage(REVERSE_TITLE_LBL)
                    ), 'is_array');
    }

    /**
     * Increments the number of searches performed by the user
     * @return void
     */
    public function updateSessionforSearch()
    {
        Framework::incrementNumberOfSearchesPerformed();
    }

    /**
     * Gets the table name aliased for answers table
     * @param int $reportID The report ID from which to get the alias
     * @return string|null Answer table alias
     */
    public function getAnswerAlias($reportID)
    {
        return $this->getTableAlias($reportID, TBL_ANSWERS);
    }

    /**
     * Gets the table name aliased for incidents table
     * @param int $reportID The report ID from which to get the alias
     * @return string|null Incident table alias
     */
    public function getIncidentAlias($reportID)
    {
        return $this->getTableAlias($reportID, VTBL_INCIDENTS);
    }

    /**
     * Gets the table name aliased for organizations table
     * @param int $reportID The report ID from which to get the alias
     * @return string|null Organization table alias
     */
    public function getOrganizationAlias($reportID)
    {
        return $this->getTableAlias($reportID, TBL_ORGS);
    }

    /**
     * Gets the table name aliased for cluster2answers table
     * @param int $reportID The report ID from which to get the alias
     * @return string|null Cluster2answer table alias
     */
    public function getClusterToAnswersAlias($reportID)
    {
        return $this->getTableAlias($reportID, TBL_CLUSTER2ANSWERS);
    }

    /**
     * Gets the table name aliased for assets table
     * @param int $reportID The report ID from which to get the alias
     * @return string|null Asset table alias
     */
    public function getAssetAlias($reportID)
    {
        return $this->getTableAlias($reportID, TBL_ASSETS);
    }

    /**
     * Gets the table name aliased for sss_question_comments table
     * @param int $reportID The report ID from which to get the alias
     * @return string|null The sss_question_comments table alias
     */
    public function getSocialCommentAlias($reportID)
    {
        return $this->getTableAlias($reportID, VTBL_SSS_QUESTION_COMMENTS);
    }

    /**
     * Gets the table name aliased for sss_questions table
     * @param int $reportID The report ID from which to get the alias
     * @return string|null The sss_questions table alias
     */
    public function getSocialQuestionAlias($reportID)
    {
        return $this->getTableAlias($reportID, TBL_SSS_QUESTIONS);
    }

    /**
     * Creates a search filter object.
     * This should be used if you are adding filters through hooks. Use this to create the correctly formatted filter and add it to the filter array
     * @param int $reportID Report ID
     * @param string $name Filter name
     * @param int $filterID Filter ID
     * @param mixed $value Filter value
     * @param string $rnSearchType Search type
     * @param int $operatorID Operator ID; defaults to 1 if not specified
     * @return object Filter object
     */
    public function createSearchFilter($reportID, $name, $filterID, $value, $rnSearchType = 'customName', $operatorID = null)
    {
        $operatorID = $operatorID ?: OPER_EQ;
        $filter = array(
            'filters' => (object)array(
                'rnSearchType' => $rnSearchType,
                'searchName' => $name,
                'report_id' => $reportID,
                'data' => (object)array('val' => $value),
                'oper_id' => $operatorID,
                'fltr_id' => $filterID
                )
            );
        return $this->getResponseObject((object)$filter);
    }

    /**
     * Returns all runtime filters for the provided report ID
     * @param int $reportID The report to check
     * @return array Array of all the runtime filter data
     */
    public function getRuntimeFilters($reportID)
    {
        if($this->isReportWidx($reportID))
            return $this->getResponseObject(array(), 'is_array');
        $cacheKey = "getRuntimeFilters$reportID";
        $runtimeFilters = Framework::checkCache($cacheKey);
        if($runtimeFilters === null){
            $runtimeFilters = Sql::view_get_srch_filters($reportID);
            Framework::setCache($cacheKey, $runtimeFilters);
        }
        return $this->getResponseObject($runtimeFilters, 'is_array');
    }


    /**
     * Get search_term for the report
     *
     * @param int $reportNumber The analytics report number
     * @param string $reportToken The token matching the report number for security
     * @param array|null $filters A php array containing all the view data
     * @return string containing the report's search term
     */
    function getSearchTerm($reportNumber, $reportToken, $filters)
    {
        if ($this->preProcessData($reportNumber, $reportToken, $filters, null))
        {
            return $this->getResponseObject((isset($this->appliedFilters['keyword']->filters->data->val)) ? $this->appliedFilters['keyword']->filters->data->val : $this->appliedFilters['keyword']->filters->data, null);
        }
        return $this->getResponseObject(null, null);
    }

    /**
     * Get the headers defined for the report.
     *
     * @param int $reportID The analytics report number
     * @param string $reportToken The token matching the report number for security
     * @param array|null $filters A php array containing all the filters to apply to the results
     * @param array|null $format An array of options for formatting the data
     * @return array The resulting report headers
     */
    public function getReportHeaders($reportID, $reportToken, $filters, $format)
    {
        if($this->preProcessData($reportID, $reportToken, $filters, $format))
        {
            $this->setViewDefinition();
            return $this->getResponseObject($this->getHeaders(isset($format['hiddenColumns']) ? $format['hiddenColumns'] : false), 'is_array');
        }
        return $this->getResponseObject(array(), 'is_array');
    }

    /**
     * Get the report data and format it. The `$filters` array options can contain:
     *
     * * **initial:** Should the data be shown initially (use value of 1 to override report setting)
     * * **page:** The page number to show
     * * **per_page:** Number to show
     * * **level:** The drill down level requested
     * * **no_truncate:** Send 1 to prevent search limiting
     * * **start_index:** Index of first result (overrides page])
     * * **search:** Send 1 to signify search, 0 for no search
     * * **recordKeywordSearch:** Send 1 to signify that this search should be recorded in the keyword_searches table (search must also be set)
     * * **sitemap:** True to signify report is being run for sitemap output
     *
     * The results will be formatted based on the `$format` array which expects:
     *
     *
     * * **highlight:** Highlight text with <span></span> tags
     * * **emphasisHighlight:** Highlight text with <em></em> tags
     * * **raw_date:** True to leave date fields alone
     * * **no_session:** Do not append the session ID to URLs (applies to grid only)
     * * **urlParms:** String of key value pairs to add to any links
     * * **hiddenColumns:** Whether to include hidden column data in the returned results
     * * **dateFormat:** How to format date fields (w/ correct internationalization):
     *      * **short:** m/d/Y
     *      * **date_time:** m/d/Y h:i A
     *      * **long:** l, M d, Y
     *      * **raw:** Unformatted unix timestamp
     *
     * The result from this method will be an array containing the following data:
     *
     * * **headers:** The visible headers formatted (i.e. date)
     * * **data:** The visible data
     *      *    **data[i][text]:** Item to print. Formatted for date and wrapping text size and highlighted
     *      *    **data[i][a_id]:** Answer ID of the item (if exists)
     *      *    **data[i][i_id]:** Incident ID of the item (if exists)
     *      *    **data[i][link]:** Anchor link (if exists)
     *      *    **data[i][drilldown]: Drilldown link to next level (if exists)
     * * **per_page:** Number of results per page
     * * **page:** Current page
     * * **total_num:** Total number of results
     * * **total_pages:** Total number of total pages
     * * **row_num:** Whether or not row numbers should be added to data
     * * **truncated:** Search config truncated number to show
     * * **grouped:** ID data on current level is grouped
     * * **initial:** If report should show on initial search
     * ]
     *
     * @param int $reportID The analytics report number
     * @param string $reportToken The token matching the report number for security
     * @param array|null &$filters A php array containing all the filters to apply to the results
     * @param array|null $format An array of options for formatting the data
     * @param boolean    $useSubReport Flag to indicate that the report will be executed using sub reports
     * @param bool $forceCacheBust Whether to willfully ignore any previously cached data
     * @param bool $cleanFilters Flag to clean the filters before calling the report
     * @return array The resulting report data
     */
    public function getDataHTML($reportID, $reportToken, &$filters, $format, $useSubReport = true, $forceCacheBust = false, $cleanFilters = true)
    {
        if ($filters && $cleanFilters) {
            $preFilterCleanHookData = array("filters" => $filters, "cleanFilterFunctionsMap" => $this->getCleanFilterFunctions());
            \RightNow\Libraries\Hooks::callHook('pre_report_filter_clean', $preFilterCleanHookData);
            $filters = $this->cleanFilterValues($preFilterCleanHookData["filters"], $preFilterCleanHookData["cleanFilterFunctionsMap"]);
        }
        $subReportMap = false;
        if ($useSubReport) {
            $preHookData = self::getSubReportMapping();
            \RightNow\Libraries\Hooks::callHook('pre_sub_report_check', $preHookData);
            $subReportMap = (isset($preHookData[$reportID]) && $preHookData[$reportID]) ? $preHookData[$reportID] : false;
        }
        if ($useSubReport && $subReportMap) {
            return $this->getDataHTMLUsingSubReports($reportID, $reportToken, $filters, $format, $subReportMap, $forceCacheBust);
        }

        if($this->preProcessData($reportID, $reportToken, $filters, $format))
        {
            $this->getData(isset($format['hiddenColumns']) ? $format['hiddenColumns'] : false, $forceCacheBust);
            $this->formatData(true, isset($format['hiddenColumns']) ? $format['hiddenColumns'] : false);
            $this->getOtherKnowledgeBaseData();
        }
        return $this->getResponseObject($this->returnData, 'is_array');
    }

    /**
     * Reads the report metadata and returns the sorting enabled column definitions
     *
     * @param integer $reportID Report ID
     * @return array Array of column definitions
     */
    public function getDefaultSortDefinitions($reportID) {
        $cacheKey = "Report-Get-$reportID";
        if (!($report = Framework::checkCache($cacheKey))) {
            $report = Sql::_report_get($reportID);
            Framework::setCache($cacheKey, $report);
        }
        $columns = array();
        if (is_array($report) && $report['nodes'] && $report['nodes']['node_item0'] && $report['nodes']['node_item0']['cols']) {
            foreach ($report['nodes']['node_item0']['cols'] as $column) {
                if (isset($column["sort_order"]) && isset($column["sort_direction"]) && $column["sort_order"] && $column["sort_direction"]) {
                    $columns[] = $column;
                }
            }
        }
        return $columns;
    }

    /**
     * Knowledgebase function to get topic words based on the specified query text.
     * @param string $searchTerm Search term to use when retrieving topic words
     * @return array The topic words associated with that keyword
     */
    public function getTopicWords($searchTerm = "")
    {
        $searchTermWord = isset($this->returnData['search_term']) && $this->returnData['search_term'] ? $this->returnData['search_term'] : '';
        $searchWord = $searchTerm ?: $searchTermWord;
        $cacheKey = "topicWords-$searchWord";
        $topicWordData = Framework::checkCache($cacheKey);
        if($topicWordData === null){
            $topicWordData = Sql::getTopicWords($searchWord);
            Framework::setCache($cacheKey, $topicWordData);
        }
        return $this->getResponseObject($topicWordData, 'is_array');
    }

    /**
     * Cleans the invalid filter values
     *
     * @param array $filters Array of filter values
     * @param array $cleanFunctionsMap Array of callback functions to clean the various filters
     * @return array Array of cleaned filter values
     */
    public function cleanFilterValues(array $filters, array $cleanFunctionsMap) {
        foreach ($cleanFunctionsMap as $filterName => $cleanFunction) {
            if ($filterValue = $this->getFilterValue($filters, $filterName)) {
                $filters = $this->setFilterValue($filters, $filterName, $cleanFunction($filterValue));
            }
        }
        return $filters;
    }

    /**
     * Method validates organization filter against the current profile data
     *
     * @param Object $filters Filter data
     * @param Object $profile Profile to which against the filter data should be validated
     * @return Boolean Returns true if organization filter is valid
     */
    public function isValidOrgFilter($filters, $profile) {
        if((isset($filters["org"]) && $filters["org"]) && $filters["org"]->filters && $filters["org"]->filters->data) {
            if($profile == null) {
                return false;
            }
            //if filter contains org filter, it should be validated
            $orgFilterData = $filters["org"]->filters->data;
            $filterID = $orgFilterData->fltr_id;
            //if filterID ends with c_id, then it's a contact specific search, else an organization specific search
            if(preg_match('/\.c_id$/', $filterID)) {
                $contactID = $profile->contactID;
                $cId = $orgFilterData->val;
                //if profile's contactID does not match the filter contact id, restrict access to the report
                if($contactID != $cId) {
                    return false;
                }
            }
            else {
                if(preg_match('/\.lvl([0-9]+)_id$/', $filterID, $matches)) {
                    $orgLevel = $profile->orgLevel ?: 1;
                    //if profile's orgLevel does not match the filter organization level, restrict access to the report
                    if($orgLevel != $matches[1]) {
                        return false;
                    }
                }
                $orgID = $orgFilterData->val;
                //if profile's orgID does not match the filter orgID, restrict access to the report
                if($profile->orgID != $orgID) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Gets suggested searches from the keyword temp table when a search is performed
     *
     * @param array $list Array of answer IDs that are present in the report
     * @param int $type Define for hm_type
     * @return array Suggested searches for the query that was done
     */
    protected function getSuggestedSearch(array $list, $type)
    {
        $searchTerm = $this->returnData['search_term'];
        $cacheKey = "suggestedSearches-$searchTerm-$type";
        $suggestedSearchData = Framework::checkCache($cacheKey);
        if($suggestedSearchData === null){
            if(count($list) === 0){
                $suggestedSearchData = null;
            }
            else{
                $suggestedSearchData = Sql::getSuggestedSearch($searchTerm, $type);
            }
            Framework::setCache($cacheKey, $suggestedSearchData);
        }
        return $suggestedSearchData;
    }

    /**
     * Gets KB data that is modified for html
     * @return void
     */
    protected function getKBStrings()
    {
        // did you mean
        $this->returnData['spelling'] = "";
        $this->returnData['not_dict'] = "";

        if(isset($this->returnData['search_term']) && $this->returnData['search_term'] !== "")
        {
            $data = $this->didYouMean();
            if(isset($data['dym']) && $data['dym'])
            {
                $this->returnData['spelling'] = str_replace("<i>", "", $data['dym']);
                $this->returnData['spelling'] = str_replace("</i>", "", $this->returnData['spelling']);
            }
            $this->returnData['not_dict'] = isset($data['nodict']) ? $data['nodict'] : null;
            $this->returnData['stopword'] = isset($data['stopword']) ? $data['stopword'] : null;
        }

        // suggested
        $this->returnData['ss_data'] = $this->getSimilarSearches();
        // topic words
        $this->returnData['topic_words'] = $this->getTopicWords()->result;
    }

    /**
     * Returns if the report number is WIDX and the configs are set correctly
     * @param int $reportID The ID of the report to check
     * @return bool Whether the report is a WIDX report
     */
    protected function isReportWidx($reportID)
    {
        return ($this->isReportNumberWidx($reportID) && (Config::getConfig(WIDX_MODE) !== 0));
    }

    /**
     * Returns whether or not the report ID provided does not include the answer special settings filter.
     *
     * @param int $reportID The ID of the report to check
     * @return bool Whether or not the report contains the answer special settings filter.
     */
    protected function isAnswerListReportWithoutSpecialSettingsFilter($reportID)
    {
        $cacheKey = "Report-Get-$reportID";
        if(!($report = Framework::checkCache($cacheKey)))
        {
            $report = Sql::_report_get($reportID);
            Framework::setCache($cacheKey, $report);
        }
        return $this->doesReportIncludeAnswerTable($report) && !$this->doesReportIncludeAnswerSpecialSettingsFilter($report);
    }

    /**
     * Whether the report has content from the answers table
     *
     * @param array $report Report definition
     * @return bool Whether the report has any content from the answers table
     */
    protected function doesReportIncludeAnswerTable(array $report)
    {
        $tables = $report['tables'];
        if(!is_array($tables))
        {
            return false;
        }

        foreach($tables as $table)
        {
            if(is_array($table) && $table['tbl'] === TBL_ANSWERS)
            {
                return true;
            }
        }
        return false;
    }

    /**
     * Returns whether or not the report ID provided includes the answer special settings filter.
     *
     * @param array $report The report definition
     * @return bool Whether or not the report contains the answer special settings filter.
     */
    protected function doesReportIncludeAnswerSpecialSettingsFilter(array $report)
    {
        $filters = $report['filters'];
        if(!is_array($filters))
        {
            return false;
        }
        foreach($filters as $filter)
        {
            if(is_array($filter) && array_key_exists('val1', $filter) && $filter['val1'] === Sql::ANSWERS_SPECIAL_SETTINGS_FILTER_NAME) {
                return true;
            }
        }
        return false;
    }

    /**
     * Runs the hooks with the incoming data; sets up the default return array and class defaults; checks for token and proper widx set up;
     * runs any preprocesses for the data.
     * @param int $reportID The ID of the report
     * @param string $reportToken Security token to validate request
     * @param array|null $filters Array of incoming filters
     * @param array|null $format Array of format options
     * @return bool False if error was encountered
     */
    protected function preProcessData($reportID, $reportToken, $filters, $format)
    {
        // run hook
        $preHookData = array('data' => array('reportId' => $reportID, 'filters' => $filters, 'format' => $format));
        \RightNow\Libraries\Hooks::callHook('pre_report_get', $preHookData);
        $this->reportID = intval($preHookData['data']['reportId']);
        $this->appliedFilters = $preHookData['data']['filters'];
        $this->appliedFormats = $preHookData['data']['format'];

        // set up default return values
        $this->reportIsTypeExternalDocument = $this->isReportWidx($this->reportID);
        $this->setDefaultReportResult();
        $this->returnData['report_id'] = $this->reportID;
        $this->answerTableAlias = $this->getAnswerAlias($this->reportID)->result;
        $this->incidentTableAlias = $this->getIncidentAlias($this->reportID)->result;
        $this->assetTableAlias = $this->getAssetAlias($this->reportID)->result;
        $this->socialCommentTableAlias = $this->getSocialCommentAlias($this->reportID)->result;
        $this->socialQuestionTableAlias = $this->getSocialQuestionAlias($this->reportID)->result;

        if($this->reportIsTypeExternalDocument && !Config::getConfig(MOD_RNANS_ENABLED))
        {
            $this->returnData['error'] = Config::getMessage(EXT_DOC_SEARCHING_IS_NOT_AVAIL_MSG);
            return false;
        }

        // validate token and report_id
        // A null token is allowed for admin requests.
        if ($this->checkInterfaceError() ||
            ($reportToken && $this->checkTokenError($reportToken)) ||
            (!$reportToken && !IS_ADMIN))
        {
            return false;
        }

        // check for valid widx set up
        if($this->isReportNumberWidx($this->reportID) && Config::getConfig(WIDX_MODE) === 0)
        {
            $this->returnData['error'] = Config::getMessage(ENABLE_WEB_INDEXING_ORDER_RPT_MSG);
            return false;
        }

        // preprocess data
        if(isset($this->appliedFilters['keyword']->filters->data) && is_array($this->appliedFilters['keyword']->filters->data))
            $this->appliedFilters['keyword']->filters->data = $this->appliedFilters['keyword']->filters->data[0];
        // don't use get_object_vars if the prod/cat filter data includes reconstructData because we have special (implicit)
        // handling of that in filtersToSearchArgs looking for node['0'] specifically
        if(isset($this->appliedFilters['p']->filters->data) && is_object($this->appliedFilters['p']->filters->data) && !$this->appliedFilters['p']->filters->data->reconstructData)
            $this->appliedFilters['p']->filters->data = get_object_vars($this->appliedFilters['p']->filters->data);
        if(isset($this->appliedFilters['c']->filters->data) && is_object($this->appliedFilters['c']->filters->data) && !$this->appliedFilters['c']->filters->data->reconstructData)
            $this->appliedFilters['c']->filters->data = get_object_vars($this->appliedFilters['c']->filters->data);
        $this->preProcessClusterTreeFilter();
        return true;
    }

    /**
     * Common data retrieval for getDataXML and getDataHTML.
     * @param bool $showHiddenColumns Whether or not to display hidden columns
     * @param bool $forceCacheBust Whether to willfully ignore any previously cached data
     * @return void
     */
    protected function getData($showHiddenColumns, $forceCacheBust = false)
    {
        if($this->reportIsTypeExternalDocument)
        {
            $this->getExternalSearchData();
        }
        else
        {
            $this->getReportData($showHiddenColumns, $forceCacheBust);
        }
        $this->viewDefinition['error'] = (isset($this->viewDefinition['error']) && $this->viewDefinition['error']) ? (Config::getMessage(ERR_RPT_TABULATURE_FMT_RPT_ID_MSG) . " $this->reportID") : null;
        $this->returnData['error'] = (isset($this->returnData['error']) && $this->returnData['error']) ? $this->returnData['error'] : $this->viewDefinition['error'];
    }

    /**
     * Formats the views data
     * @param bool $isHtml Whether to format links in html syntax; defaults to true
     * @param bool $includeHiddenColumns Whether to include hidden columns in the data
     * @return void
     */
    protected function formatData($isHtml = true, $includeHiddenColumns = false)
    {
        if($this->reportIsTypeExternalDocument && $this->returnData['search_term'])
        {
            $this->formatExternalSearchData();
        }
        else
        {
            $this->formatViewsData($isHtml, $includeHiddenColumns);
        }
        $this->keywordSearchUpdate();
    }

    /**
     * Function to add the related prod/cats, spelling, topic words
     * @return void
     */
    protected function getOtherKnowledgeBaseData()
    {
        $this->getKBStrings();
        $searchSuggestions = Config::getConfig(SEARCH_SUGGESTIONS_DISPLAY);

        $this->returnData['related_prods'] = $this->returnData['related_cats'] = array();
        if(($searchSuggestions & SRCH_SUGGESTIONS_DSPLY_PRODS) && $this->returnData['search_term'] && !$this->reportIsTypeExternalDocument)
            $this->returnData['related_prods'] = $this->getSuggestedSearch($this->answerIDList, HM_PRODUCTS);
        if(($searchSuggestions & SRCH_SUGGESTIONS_DSPLY_CATS) && $this->returnData['search_term'] && !$this->reportIsTypeExternalDocument)
            $this->returnData['related_cats'] = $this->getSuggestedSearch($this->answerIDList, HM_CATEGORIES);

        if($this->reportIsJoinedOnClusterTree)
        {
            $this->returnData['topics'] = $this->CI->model('Topicbrowse')->getSearchBrowseTree($this->returnData['search_term'])->result;
        }

        if(count($this->returnData['data']) && is_numeric($this->returnData['not_dict']))
            $this->returnData['not_dict'] = '';
    }

    /**
     * Checks report security tokens
     * @param string $token The token to validate
     * @return bool False if there is no error
     */
    protected function checkTokenError($token)
    {
        return !(Framework::isValidSecurityToken($token, $this->reportID));
    }

    /**
     * Checks if the report is valid for the current interface
     * @return bool False if there is no error, true otherwise
     */
    protected function checkInterfaceError()
    {
        if(!$this->isReportNumberWidx($this->reportID) && !Sql::verify_interface($this->reportID))
        {
            $this->returnData['error'] = Config::getMessage(REPORT_NOT_VISIBLE_INTERFACE_LBL);
            return true;
        }
        return false;
    }

    /**
     * Returns an array initialized to the defaults for a report result.
     * @return void
     */
    protected function setDefaultReportResult()
    {
        $this->returnData = array(
            'data' => array(),
            'headers' => array(),
            'per_page' => 0,
            'total_pages' => 0,
            'total_num' => 0,
            'row_num' => 1,
            'truncated' => 0,
            'start_num' => 0,
            'end_num' => 0,
            'initial' => 0,
            'search_type' => 0,
            'search' => 0
        );
    }

    /**
     * Gets the similar search terms
     * @return array|null List of similar search terms for null if none were found
     */
    protected function getSimilarSearches()
    {
        $suggestedSearchData = false;
        $searchTerm = $this->returnData['search_term'];
        if($searchTerm !== null && $searchTerm !== false && Config::getConfig(EU_SUGGESTED_SEARCHES_ENABLE)){
            $suggestedSearchData = Sql::getSimilarSearches($searchTerm);
        }
        return $suggestedSearchData ?: null;
    }

    /**
     * Puts keyword search term into keyword_searches and clickstreams
     * @return void
     */
    protected function keywordSearchUpdate()
    {
        if(!IS_PRODUCTION || $this->CI->rnow->isSpider())
        {
            return; //not collecting data
        }

        if($this->reportIsTypeExternalDocument)
            $source = SRCH_EXT_DOC;
        else if($this->reportIsJoinedOnClusterTree)
            $source = SRCH_BROWSE;
        else if($this->answerTableAlias)
            $source = SRCH_END_USER;
        else
            return; //unknown source

        $stats = array(
            'search_flag' => (isset($this->appliedFilters['search']) ? $this->appliedFilters['search'] : null),
            'record_flag' => (isset($this->appliedFilters['recordKeywordSearch']) ? $this->appliedFilters['recordKeywordSearch'] : null),
            'term' => (isset($this->returnData['search_term']) && $this->returnData['search_term'] ? trim($this->returnData['search_term']) : null),
            'total' => $this->returnData['total_num'],
            'page' => $this->returnData['page'],
            'source' => $source,
            'list' => $this->answerIDList);

        // This work-around is for our basic (non-JavaScript) page set.
        // If the keyword has been POST'ed assume that the user actively made a search
        // and record keywords and result lists and update the user's session to
        // indicate that a search has occurred.
        // Note that we are unable to record these activities when the user is selecting
        // a product or category. Only an interaction with the BasicKeywordSearch
        // widget will cause these actions in the basic page set.
        if($this->CI->input->post('kw') !== false) {
            $stats['search_flag'] = 1;
            $stats['record_flag'] = true;
            static $sessionAlreadyUpdated = false;
            if(!$sessionAlreadyUpdated) {
                $this->updateSessionforSearch();
                $sessionAlreadyUpdated = true;
            }
        }

        //insert keyword_searches table data
        if($stats['search_flag'] && $stats['record_flag'])
        {
            static $keywordsAlreadyUpdated = false;
            if(!$keywordsAlreadyUpdated) {
                $this->CI->model('Clickstream')->insertKeywords($stats);
                $keywordsAlreadyUpdated = true;
            }
        }
        //insert result list
        if(($stats['search_flag'] && $stats['record_flag']) || $stats['page'] > 0)
        {
            static $resultListAlreadyUpdated = false;
            if(!$resultListAlreadyUpdated) {
                $this->CI->model('Clickstream')->insertResultList($stats);
                $resultListAlreadyUpdated = true;
            }
        }
    }

    /**
     * Check if the page requested is outside of the proper bounds for the request
     * @return void
     */
    protected function checkValidPageNumberRequest()
    {
        if(isset($this->appliedFilters['page']) && $this->returnData['total_pages'] != 0 && ($this->appliedFilters['page'] > $this->returnData['total_pages'] || $this->appliedFilters['page'] <= 0))
        {
            $this->returnData['data'] = array();
            $this->returnData['total_num'] = 0;
            $this->returnData['start_num'] = 0;
            $this->returnData['total_pages'] = 0;
            $this->returnData['end_num'] = 0;
            $this->returnData['page'] = 1;
        }
    }

    /**
     * Takes the link in the report and replaces any column names with values
     * @param string $url The original link
     * @param array|null $values The row values
     * @param string $urlParms Existing parameters in the url
     * @return string A converted link
     */
    protected function replaceColumnLinks($url, $values, $urlParms = '')
    {
        // if $urlParms contains whitespace (good whitespace should be escaped), assume it's malicious and remove everything after it
        // use htmlspecialchars to convert everything else to play nicely in URLs
        // explicitly indicate that we want to double encode to make this manipulation less confusing
        $urlParms = htmlspecialchars(preg_replace('/\s.*$/', '', $urlParms), ENT_QUOTES, 'UTF-8', true);

        if($this->reportIsTypeExternalDocument)
        {
            $url = str_replace(Config::getConfig(CP_ANSWERS_DETAIL_URL), Config::getConfig(CP_WEBSEARCH_DETAIL_URL), $url);
        }
        $start = strpos($url, "&lt;");
        if($start === false)
        {
            return $url .= $urlParms;
        }
        while($start !== false)
        {
            $end = strpos($url, "&gt;");
            $name = substr($url, $start + 4, ($end - $start - 4));
            $rep = "&lt;$name&gt;";
            $value = $values[$name - 1];
            $url = substr_replace($url, isset($value) ? $value : '', $start, strlen($rep));
            $start = strpos($url, "&lt;", $end + 4 - (isset($rep) ? strlen($rep) : 0) + (isset($value) ? strlen($value) : 0));
        }
        return $url .= $urlParms;
    }

    /**
     * Gets the data for the result info for spelling, stopwords, etc.
     * @return array List of suggested spelling fixes and stopwords
     */
    protected function didYouMean()
    {
        $searchTerm = $this->returnData['search_term'];
        $searchType = $this->returnData['search_type'];
        $data = array();
        if(Config::getConfig(EU_SEARCH_TERM_FEEDBACK_ENABLE) && $searchTerm)
        {
            //The search_type filter is not filled in correctly for widx reports, so we'll assume
            //that it's a complex search type for widx.
            $isComplex = $this->isReportWidx($this->reportID) || $this->getComplex($searchType);
            $incident = ($this->incidentTableAlias) ? 1 : 0;
            $cacheKey = "rnkl_query_parse" . ($isComplex ? "1" : "0") . "-$incident-$searchTerm";
            if(null === ($queryResults = Framework::checkCache($cacheKey)))
            {
                $queryResults = Api::rnkl_query_parse(($isComplex) ? 0x0040 : 0x0004, $searchTerm, $incident);
                Framework::setCache($cacheKey, $queryResults);
            }

            if(($queryResults['nspelled'] > 0) || ($queryResults['ntrimmed'] > 0) || ($queryResults['nid'] > 0))
            {
                //did you mean
                if($queryResults['nspelled'])
                {
                    $data['dym'] = trim($queryResults['dym']);
                }
                //stopwords
                if($queryResults['ntrimmed'] > 0)
                {
                    $data['stopword'] = trim($queryResults['trimmed']);
                }
                //not in dict (dfa)
                if($queryResults['nid'] > 0)
                {
                    $data['nodict'] = trim($queryResults['nodict']);
                }
                $data['aliases'] = trim($queryResults['aliases']);
            }
            if($queryResults['nomatch'])
            {
                $data['nores'] = Config::getMessage(NO_RES_FND_PLEASE_EXP_YOUR_QUERY_MSG);
            }
            else if($queryResults['nodict'])
            {
                $data['nodict'] = trim($queryResults['nodict']);
            }
        }
        return $data;
    }

    /**
     * Function to get external document results
     * @return void
     */
    protected function getExternalSearchData()
    {
        $searchTerm = (isset($this->appliedFilters['keyword']->filters->data->val)) ? $this->appliedFilters['keyword']->filters->data->val : $this->appliedFilters['keyword']->filters->data;
        if(!$searchTerm)
        {
            $this->getStandardAnswerReportInsteadOfWidx();
            return;
        }
        $this->loadHTDigLibrary();
        $htSearchQueryArguments = $this->getHTSearchQueryArguments($searchTerm);

        $preHookData = array('data' => array('reportId' => $this->reportID, 'queryArguments' => $htSearchQueryArguments, 'reportType' => 'external'));
        \RightNow\Libraries\Hooks::callHook('pre_report_get_data', $preHookData);
        $htSearchQueryArguments = $preHookData['data']['queryArguments'];

        $cacheKey = "getExternalSearchData$this->reportID" . serialize($htSearchQueryArguments);
        if(null !== ($cachedResult = Framework::checkCache($cacheKey)))
        {
            $this->returnData = $cachedResult;
            return;
        }

        $errorMessage = Sql::htsearch_open();
        $rowCount = 0;
        $htSearchWasOpenedSuccessfully = false;
        if(!$errorMessage)
        {
            $htSearchWasOpenedSuccessfully = true;
            $rowCount = Sql::htsearch_query($htSearchQueryArguments);
            if($rowCount < 0)
            {
                if($rowCount == HTSEARCH_ERROR_QUERYPARSER_ERROR)
                    $errorMessage = Config::getMessage(ERR_BAD_SRCH_QUERY_SYNTAX_MSG);
                else if(($rowCount <= -209 ) && ($rowCount >= -212 ))
                    $errorMessage = Config::getMessage(ERR_OPEN_IDX_EMPTY_CORRUPT_MSG);
            }
        }

        if($errorMessage)
            $this->returnData['error'] = $errorMessage;
        $this->returnData['search_term'] = $searchTerm;
        $this->returnData['grouped'] = 0;
        $pageNumber = $this->appliedFilters['page'] ? intval($this->appliedFilters['page']) : 1;
        $numberPerPage = $this->getNumberPerPage(isset($this->appliedFilters['per_page']) ? $this->appliedFilters['per_page'] : null, 0);
        $this->returnData['data'] = $this->getExternalSearchResults($numberPerPage, $rowCount, $pageNumber);
        $numberThisPage = count($this->returnData['data']);
        $this->returnData['headers'] = $this->getExternalDocumentHeaders();
        $this->returnData['page'] = $pageNumber;
        $this->returnData['total_num'] = $rowCount;
        $this->returnData['start_num'] = ($this->returnData['total_num'] > 0) ? ($numberPerPage * ($pageNumber - 1) + 1) : 0;
        $this->returnData['per_page'] = $numberThisPage;
        $this->returnData['total_pages'] = ($numberPerPage > 0) ? ceil($rowCount / $numberPerPage) : 0;
        $this->returnData['end_num'] = ($this->returnData['total_num'] > 0) ? ($this->returnData['start_num'] + $numberThisPage - 1) : 0;
        $this->returnData['row_num'] = 1;

        if($htSearchWasOpenedSuccessfully)
        {
            Sql::htsearch_close();
        }
        $this->checkValidPageNumberRequest();

        $postHookData = array('data' => array('reportId' => $this->reportID, 'returnData' => &$this->returnData, 'reportType' => 'external'));
        \RightNow\Libraries\Hooks::callHook('post_report_get_data', $postHookData);

        Framework::setCache($cacheKey, $postHookData['data']['returnData']);
    }

    /**
     * Gets the external results from the api
     * @param int $numberPerPage Number of results per page
     * @param int $resultCount Total number of results
     * @param int $pageNumber Current page
     * @return array Result list
     */
    protected function getExternalSearchResults($numberPerPage, $resultCount, $pageNumber)
    {
        $results = array();
        $firstResultIndex = ($pageNumber - 1) * $numberPerPage;
        $maxResultIndex = min($resultCount, $firstResultIndex + $numberPerPage);
        for($n = $firstResultIndex; $n < $maxResultIndex; ++$n)
        {
            $row = Sql::htsearch_get_nth_match($n);
            $answerID = $row['id'];

            if($answerID == 0)
            {
                if((strlen($row['title']) == 0) || ($row['title'] == '[No title]'))
                    $title = $url;
                else
                    $title = $row['title'];

                $link = $row['URL'];
                $icon = Framework::getIcon($link, true);
                $url = "<a href='{$link}'>{$title}</a>";
            }
            else
            {
                $link = \RightNow\Utils\Url::getShortEufAppUrl('sameAsCurrentPage', Config::getConfig(CP_WEBSEARCH_DETAIL_URL));
                $link .= "/a_id/{$answerID}" . \RightNow\Utils\Url::sessionParameter();
                $url = "<a href='{$link}'>{$row['title']}</a>";
                if(strncmp($row['URL'], 'RNKLURL', 7) === 0)
                {
                    $row['URL'] = $row['name'];
                    $link = $row['name'];
                    $url = "<a href='{$link}'>{$row['title']}</a>";
                }
                else if(strncmp($row['URL'], 'RNKLATTACH', 10) == 0)
                {
                    if(preg_match('/filename=(.*):p_created=(.*)/', $row['name'], $matches) !== 0)
                        $row['URL'] = $matches[1];
                }
                $icon = Framework::getIcon($row['URL']);

                if(in_array(LANG_DIR, array('ja_JP', 'ko_KR', 'zh_CN', 'zh_TW', 'zh_HK')))
                    $row['excerpt'] = $this->getHighlightingFromAnswerID($answerID);
            }

            $score = max(1, (int)($row['score'] * 100));
            $result = array($icon, $url, $row['excerpt'], $row['size'], $row['time'], $score);
            if(Config::getConfig(EU_WIDX_SHOW_URL))
                $result[] = $link;
            $results[] = $result;
        }
        return $results;
    }

    /**
     * Returns correctly highlighted and truncated string
     * @param int $answerID ID of the answer
     * @return string Answer solution with escaped content
     */
    protected function getHighlightingFromAnswerID($answerID)
    {
        $text = '';
        $maximumTextLength = 1024; // this is the same as the webindexer

        if($answer = $this->CI->model('Answer')->get($answerID)->result){
            $text = Api::utf8_trunc_nchars(Api::print_text2str($answer->Solution, OPT_VAR_EXPAND | OPT_STRIP_HTML_TAGS | OPT_REF_TO_URL_PREVIEW | OPT_HIGHLIGHT_KEYWORDS | OPT_COND_SECT_FILTER), $maximumTextLength);
        }
        return $text;
    }

    /**
     * Converts filters to external document format
     * @param string $searchTerm Current search term
     * @return array External document search arguments
     */
    protected function getHTSearchQueryArguments($searchTerm)
    {
        $oldSortArgs = isset($this->appliedFilters['sort_args']) ? $this->appliedFilters['sort_args'] : null;
        $webSortArgs = isset($this->appliedFilters['webSearchSort']) ? $this->appliedFilters['webSearchSort'] : null;
        $webSearchArgs = isset($this->appliedFilters['webSearchType']) ? $this->appliedFilters['webSearchType'] : null;
        if($webSortArgs)
        {
            $sortArgs = $webSortArgs->filters->data->col_id;
        }
        if($webSearchArgs)
        {
            $searchArgs = $webSearchArgs->filters->data;
        }
        if(isset($oldSortArg) && !isset($sortArgs) && !isset($searchArgs))
        {
            if(is_array($oldSortArg))
            {
                $sortArgs = $oldSortArgs['filters']['col_id'];
                $searchArgs = $oldSortArgs['sort_field0']['search_type'];
            }
            else
            {
                $sortArgs = $oldSortArgs->filters->col_id;
                $searchArgs = $oldSortArgs->sort_field0->search_type;
            }
        }

        $internalSort = (isset($sortArgs)) ? $this->getExternalDocumentSortByType($sortArgs) : $this->getExternalDocumentSortByType(Config::getConfig(EU_WIDX_SORT_BY_DEFAULT));
        $searchType = (isset($searchArgs)) ? $this->getExternalDocumentSearchByType($searchArgs) : $this->getExternalDocumentSearchByType(Config::getConfig(EU_WIDX_SEARCH_BY_DEFAULT));
        // parse the text into seperate fields
        $searchFields = Api::rnkl_ext_doc_query_parse($searchType, 1, str_replace('%', ' ', $searchTerm));

        return array(
            'optional_query' => $searchFields['optional_query'],
            'required_query' => $searchFields['required_query'],
            'forbidden_query' => $searchFields['forbidden_query'],
            'prefix_query' => $searchFields['prefix_query'],
            'synonym_query' => $searchFields['synonym_query'],
            'algorithm' => HTSEARCH_ALG_BOOLEAN_STR,
            'sortby' => $internalSort,
            'format' => HTSEARCH_FORMAT_LONG_STR
        );
    }

    /**
     * Gets the headers array for external documents
     * @return array List of external document headers
     */
    protected function getExternalDocumentHeaders()
    {
        if($this->reportID === CP_NOV09_WIDX_DEFAULT)
        {
            $headers = array(
                array('heading' => ''),
                array('heading' => Config::getMessage(SUMMARY_LBL)),
                array('heading' => Config::getMessage(DESCRIPTION_LBL))
            );
            if(Config::getConfig(EU_WIDX_SHOW_URL))
                $headers []= array('heading' => Config::getMessage(URL_LBL));
            $headers []= array('heading' => Config::getMessage(LAST_UPDATED_LBL));
        }
        else
        {
            $headers = array(
                array('heading' => ''),
                array('heading' => Config::getMessage(SUMMARY_LBL)),
                array('heading' => Config::getMessage(DESCRIPTION_LBL)),
                array('heading' => Config::getMessage(SIZE_LBL)),
                array('heading' => Config::getMessage(LAST_UPDATED_LBL)),
                array('heading' => Config::getMessage(SCORE_LBL))
            );
            if(Config::getConfig(EU_WIDX_SHOW_URL))
                $headers []= array('heading' => Config::getMessage(URL_LBL));
        }
        return $headers;
    }

    /**
     * Opens the libraries for external documents
     * @return void
     */
    protected function loadHTDigLibrary()
    {
        if(!$this->isHTDigLoaded){
            Sql::loadHTDigLibrary();
            $this->isHTDigLoaded = true;
        }
    }

    /**
     * If the report is external document but there is no search term then we retrieve the standard report instead
     * @return void
     */
    protected function getStandardAnswerReportInsteadOfWidx()
    {
        //if using websearch mode but mode is set to display only answers then we want to set search filters back to default
        //rather than report id 10016
        $this->reportID = ($this->reportID == CP_WIDX_REPORT_DEFAULT) ? CP_REPORT_DEFAULT : CP_NOV09_ANSWERS_DEFAULT;
        $this->answerTableAlias = $this->getAnswerAlias($this->reportID)->result;
        $this->incidentTableAlias = $this->getIncidentAlias($this->reportID)->result;
        $this->reportIsTypeExternalDocument = false;
        unset($this->appliedFilters['sort_args']);

        // get a search type for the views engine
        $searchType = $this->getSearchFilterTypeDefault($this->reportID)->result;
        if($searchType)
        {
            if(!$this->appliedFilters['searchType']){
                $this->appliedFilters['searchType'] = (object)array('filters' => (object)array());
            }
            $this->appliedFilters['searchType']->filters->fltr_id = $searchType['fltr_id'];
            $this->appliedFilters['searchType']->filters->data = $searchType['fltr_id'];
            $this->appliedFilters['searchType']->filters->oper_id = $searchType['oper_id'];
        }
        $this->getReportData(false);
    }

    /**
     * Formats external searching report data
     * @return void
     */
    protected function formatExternalSearchData()
    {
        foreach($this->returnData['data'] as &$row)
        {
            if($this->appliedFormats['truncate_size'] > 0)
                $row[2] = Text::truncateText($row[2], $this->appliedFormats['truncate_size'], true, $this->appliedFormats['max_wordbreak_trunc']);

            //Highlight title and summary
            if(isset($this->appliedFormats['highlight']) && $this->appliedFormats['highlight'])
            {
                $row[1] = Text::highlightTextHelper($row[1], $this->returnData['search_term'], $this->appliedFormats['highlightLength']);
                $row[2] = Text::highlightTextHelper($row[2], $this->returnData['search_term'], $this->appliedFormats['highlightLength']);
            }
            else if($this->appliedFormats['emphasisHighlight'])
            {
                $row[1] = Text::emphasizeText($row[1], array('query' => $this->returnData['search_term']));
                $row[2] = Text::emphasizeText($row[2], array('query' => $this->returnData['search_term']));
            }
            if($this->reportID === CP_WIDX_REPORT_DEFAULT)
            {
                $row[3] = $this->convertBytesToLargestUnit($row[3]);
                $row[4] = Api::date_str(DATEFMT_SHORT, $row[4]);

                $score = $row[5];
                $scoreHtml = "$score&nbsp;&nbsp;";
                $altText = Config::getMessage(SCORE_LBL) . ": $score";
                $tempScore = $score / 2;
                $scoreHtml .= "<img src='images/icons/widxdark.gif' style='vertical-align:middle;' height=8 width=$tempScore alt='$altText'>";
                $tempScore = (HTSEARCH_MAX_SCORE - (int)($score / 2));
                $scoreHtml .= "<img src='images/icons/widxlight.gif' style='vertical-align:middle;' height=8 width=$tempScore alt='$altText'>";
                $row[5] = $scoreHtml;
            }
            else if($this->reportID === CP_NOV09_WIDX_DEFAULT)
            {
                //doesn't have size or score columns
                //1. summary 2. desc 3. url (optional) 4. updated
                $row[3] = Api::date_str(DATEFMT_SHORT, $row[4]); //overwrite size
                unset($row[5]); //get rid of score
                if(isset($row[6]) && $row[6])
                {
                    //if there's a url, swap it with updated
                    $temp = $row[3];
                    $row[3] = $row[6];
                    $row[4] = $temp;
                    unset($row[6]);
                }
            }
        }
    }

    /**
     * Gets the sort by type for external document searching
     *
     * @param int $sortBy The current sort by value
     * @return int Type of sort to use
     */
    protected function getExternalDocumentSortByType($sortBy)
    {
        switch ($sortBy)
        {
            case WIDX_TIME_SORT:
                return HTSEARCH_SORT_REV_TIME_STR;
            case WIDX_TITLE_SORT:
                return HTSEARCH_SORT_TITLE_STR;
            case WIDX_REV_TIME_SORT:
                return HTSEARCH_SORT_TIME_STR;
            case WIDX_REV_TITLE_SORT:
                return HTSEARCH_SORT_REV_TITLE_STR;
            default:
                return HTSEARCH_SORT_SCORE_STR;
        }
    }

    /**
     * Gets the search by type for external document searching
     * @param int $searchBy The current search by type ID
     * @return int Type of search type to use
     */
    protected function getExternalDocumentSearchByType($searchBy)
    {
        if($searchBy === WIDX_ANY_SEARCH){
            return 0x0004;
        }
        if($searchBy === WIDX_ALL_SEARCH){
            return 0x0001;
        }
        //WIDX_COMPLEX_SEARCH
        return 0x0004 | 0x0040 | 0x0008;
    }
     /**
     * Gets the report headers and view definition.
     * @param bool $showHiddenColumns Whether to show hidden columns on the report
     * @return array Headers for the current report
     */
    protected function getHeaders($showHiddenColumns)
    {
        $cacheKey = "reportVisHeaders{$this->reportID}{$showHiddenColumns}";
        $headers = Framework::checkCache($cacheKey);
        if($headers !== null)
        {
            return $headers;
        }
        $columns = $this->viewDefinition['all_cols'];
        $columnID = 0;
        $headers = array();
        foreach($columns as $column)
        {
            // always increment the columnID to keep sorted columns consistent between widgets, regardless of whether they're hidden or not
            $columnID++;
            if(!$column['visible'] && !$showHiddenColumns){
                continue;
            }
            $header = array(
                'heading' => $column['heading'],
                'width' => $column['width'],
                'data_type' => $column['data_type'],
                'col_id' => $columnID,  //used for sorting
                'order' => $column['order'],
                'col_definition' => $column['col_definition'],
                'visible' => $column['visible'] === 1,
            );
            if(isset($column['url_info']) && isset($column['url_info']['url']) && $column['url_info'] && $column['url_info']['url'] )
            {
                $header['url_info'] = $column['url_info']['url'];
            }

            if($showHiddenColumns)
            {
                $answerAlias = $this->answerTableAlias;
                if($column['col_definition'] === "$answerAlias.summary") {
                    $header['col_alias'] = 'summary';
                }
                else if($column['col_definition'] === "$answerAlias.updated") {
                    $header['col_alias'] = 'updated';
                }
                else if($column['col_definition'] === "$answerAlias.solved") {
                    $header['col_alias'] = 'score';
                }
            }
            $headers[] = $header;
        }
        Framework::setCache($cacheKey, $headers);
        return $headers;
    }

    /**
     * Sets the `$viewDefinition` field by calling view_get_grid_info with the current ReportID.
     * @param int $opts VIEW_BIG_DATE_OPTS value to use to handle and display post 2038 dates in CP reports.
     * @return void
     */
    protected function setViewDefinition($opts=0)
    {
        $this->viewDefinition = Sql::view_get_grid_info($this->reportID, null, $opts);
    }

    /**
     * Converts the filters array into a format used by the views engine
     * @return array Filters specified by CP converted to views engine format
     */
    protected function convertCPFiltersToQueryArguments()
    {
        $numberPerPage = $this->getNumberPerPage(intval($this->viewDefinition['rpt_per_page']));
        $pageNumber = (isset($this->appliedFilters['page'])) ? intval($this->appliedFilters['page']) : 1;
        $rowStart = (isset($this->appliedFilters['start_index'])) ? intval($this->appliedFilters['start_index']) : intval(($pageNumber - 1) * $numberPerPage);
        $rowStart = (intval($rowStart) >= 0) ? intval($rowStart) : INT_NULL;
        return array(
            'param_args' => $this->filtersToOutputVariables(),
            'search_args' => $this->filtersToSearchArgs(),
            'sort_args' => $this->filtersToSortArgs(),
            'limit_args' => array(
                'row_limit' => $numberPerPage,
                'row_start' => $rowStart,
            ),
            'count_args' => array(
                'get_row_count' => 1,
                'get_node_leaf_count' => 1,
            ),
        );
    }

    /**
     * Gets report data and view definition information from views engine and builds it up into the correct structure
     * @param bool $showHiddenColumns Whether or not to show hidden columns
     * @param bool $forceCacheBust Whether to willfully ignore any previously cached data
     * @return array The report data in the correct format
     */
    protected function getReportData($showHiddenColumns, $forceCacheBust = false)
    {
        \RightNow\Utils\Framework::removeCache("reportDef_bigdate_" . $this->reportID);
        \RightNow\Utils\Framework::removeCache("reportHeaders" . $this->reportID);

        $this->setViewDefinition(self::CP_VIEW_OPT_BIGDATE);
        $queryArguments = $this->convertCPFiltersToQueryArguments();
        $queryArguments['opts'] = self::CP_VIEW_OPT_BIGDATE;

        $preHookData = array('data' => array('reportId' => $this->reportID, 'queryArguments' => $queryArguments, 'reportType' => 'internal'));
        \RightNow\Libraries\Hooks::callHook('pre_report_get_data', $preHookData);
        $queryArguments = $preHookData['data']['queryArguments'];

        $cacheKey = "getReportData$this->reportID" . serialize($queryArguments) . (($showHiddenColumns) ? 'withHiddenColumns' : '');
        if(!$forceCacheBust && null !== ($cachedResult = Framework::checkCache($cacheKey)))
        {
            list($this->returnData, $this->viewDataColumnDefinition) = $cachedResult;
            return;
        }
        if(IS_DEVELOPMENT && $this->isAnswerListReportWithoutSpecialSettingsFilter($this->reportID))
        {
            Framework::addDevelopmentHeaderWarning(sprintf(Config::getMessage(RPT_ID_PCT_D_INCLUDES_ANS_TB_PCT_S_MSG), $this->reportID, Sql::ANSWERS_SPECIAL_SETTINGS_FILTER_NAME, Sql::ANSWERS_SPECIAL_SETTINGS_FILTER_NAME));
        }
        $viewData = Sql::view_get_query_cp($this->reportID, $queryArguments);
        $this->recordSearchData($queryArguments['search_args']);
        $this->viewDataColumnDefinition = isset($viewData['columns']) ? $viewData['columns'] : null;
        $exceptions = isset($viewData['view_handle']) ? $this->getViewExceptions($viewData['view_handle']) : null;

        $numberPerPage = $this->getNumberPerPage(intval(($this->viewDefinition['rpt_per_page'] === INT_NOT_SET) ? $this->viewDefinition['row_limit'] : $this->viewDefinition['rpt_per_page']));
        $this->setMaxResultsBasedOnSearchLimiting($numberPerPage);
        $dataArray = $this->getViewResults($viewData['view_handle'], min($numberPerPage, (isset($this->returnData['max_results']) && $this->returnData['max_results']) ? $this->returnData['max_results'] : 0x7fffffff));
        $numberThisPage = count($dataArray);
        $pageNumber = (isset($this->appliedFilters['page'])) ? intval($this->appliedFilters['page']) : 1;
        $pageNumber = (intval($pageNumber) <= 0) ? 1 : $pageNumber;
        $rowCount = (isset($this->returnData['max_results']) && ($this->returnData['max_results'] > 0)) ? $this->returnData['max_results'] : $viewData['row_count'];
        $this->returnData['headers'] = $this->getHeaders($showHiddenColumns);
        $this->returnData['total_num'] = ($numberPerPage > 0) ? $viewData['row_count'] : 0;
        $this->returnData['start_num'] = ($this->returnData['total_num'] > 0) ? ($numberPerPage * ($pageNumber - 1) + 1) : 0;
        $this->returnData['per_page'] = ($viewData['row_count'] < $numberThisPage) ? $rowCount : $numberThisPage;
        $this->returnData['total_pages'] = ($numberPerPage > 0) ? ceil($rowCount / $numberPerPage) : 0;
        $this->returnData['end_num'] = ($this->returnData['total_num'] > 0) ? ($this->returnData['start_num'] + $numberThisPage - 1) : 0;
        $this->returnData['search_term'] = isset($this->appliedFilters['keyword']->filters->data) ? $this->appliedFilters['keyword']->filters->data : null;
        if($this->returnData['total_num'] <= $numberThisPage)
        {
            $this->returnData['truncated'] = 0;
        }
        $this->returnData['row_num'] = $this->viewDefinition['row_num'];
        $this->returnData['grouped'] = $this->viewDefinition['grouped'];
        $this->returnData['data'] = $dataArray;
        $this->returnData['exceptions'] = $exceptions;
        $this->returnData['page'] = intval($pageNumber);
        $this->checkValidPageNumberRequest();

        $postHookData = array('data' => array('reportId' => $this->reportID, 'returnData' => &$this->returnData, 'reportType' => 'internal'));
        \RightNow\Libraries\Hooks::callHook('post_report_get_data', $postHookData);

        Framework::setCache($cacheKey, array($postHookData['data']['returnData'], $this->viewDataColumnDefinition));
        Api::view_cleanup($viewData['view_handle']);
    }

    /**
     * Executes the reports in a optimized way and fetches the results
     * with the following steps
     * 1. Determines the sub report to be executed for the current filters and sorting
     * 2. Executes the sub report and collect the object IDS
     * 3. Executes the main report only for the object IDS returned from the sub report
     *
     * @param int $reportID The analytics report number
     * @param string $reportToken The token matching the report number for security
     * @param array|null $filters A php array containing all the filters to apply to the results
     * @param array|null $format An array of options for formatting the data
     * @param array|null $subReportMap Arrsy of Sub Report mapping
     * @param bool $forceCacheBust Whether to willfully ignore any previously cached data
     * @return array The resulting report data
     */
    protected function getDataHTMLUsingSubReports($reportID, $reportToken, $filters, $format, $subReportMap, $forceCacheBust)
    {
        $subReportKeyAndFilters = $this->getSubReportKeyAndFilters($reportID, $subReportMap, $filters);
        $subReportDefinition = $this->getSubReportDefinition($subReportMap, $subReportKeyAndFilters);
        $mainReportResponse = null;
        if ($subReportDefinition) {
            $subReportFilters = $this->setSubReportFilters($filters, $subReportDefinition["SubReportID"], $subReportMap);
            $subReportFilters = $this->setSortArgsColumn($subReportFilters, $subReportDefinition["SubReportColID"], $subReportKeyAndFilters["defaultSortOrder"], $subReportKeyAndFilters["defaultSortDirection"]);
            $subReportToken = Framework::createToken($subReportDefinition["SubReportID"]);
            $subReportResponse = $this->getDataHTML($subReportDefinition["SubReportID"], $subReportToken, $subReportFilters, array(), false, $forceCacheBust, false);
            $objectIDs = array();
            foreach ($subReportResponse->result["data"] as $row) {
                $objectIDs[] = $row[0];
            }
            $mainReportIDFilter = $this->getFilterByName($reportID, $subReportMap["MainReportIDFilter"]);
            $filters [$subReportMap["MainReportIDFilter"]] = (object) array('filters' => (object) array(
                        'fltr_id' => $mainReportIDFilter->result['fltr_id'],
                        'oper_id' => $mainReportIDFilter->result['oper_id'],
                        'rnSearchType' => 'filter',
                        'data' => !empty($objectIDs) ? implode(",", $objectIDs) : "-1",
                        'report_id' => $reportID),
                    "type" => $mainReportIDFilter->result['name']);
            $filters = $this->setSortArgsColumn($filters, $subReportKeyAndFilters["sortingColID"], $subReportKeyAndFilters["defaultSortOrder"], $subReportKeyAndFilters["defaultSortDirection"]);
            $filters["page"] = 1;
            $mainReportResponse = $this->getDataHTML($reportID, $reportToken, $filters, $format, false, $forceCacheBust, false);
            $mainReportResponse->result = array_merge($mainReportResponse->result, array("total_num" => $subReportResponse->result["total_num"],
                "per_page" => $subReportResponse->result["per_page"],
                "total_pages" => $subReportResponse->result["total_pages"],
                "page" => $subReportResponse->result["page"],
                "row_num" => $subReportResponse->result["row_num"],
                "start_num" => $subReportResponse->result["start_num"],
                "end_num" => $subReportResponse->result["end_num"],
                "page" => $subReportResponse->result["page"],
                "initial" => $subReportResponse->result["initial"]
            ));
        }
        else {
            $mainReportResponse = $this->getDataHTML($reportID, $reportToken, $filters, $format, false, $forceCacheBust, false);
        }
        return $mainReportResponse;
    }

    /**
     * Inserts stats about the current search for keyword, product, and category search filters
     * @param array|null $reportQueryData Filters being used for the current report
     * @return void
     */
    protected function recordSearchData($reportQueryData){
        if (!ActionCapture::isInitialized()) {
            // When the request is coming from the Knowledge Syndication Widget where a search query is specified,
            // the session_id will not be present causing ActionCapture::record to fail.
            return;
        }
        $searchPerformed = false;
        $keywordSearch = isset($this->appliedFilters['keyword']->filters->data) ? $this->appliedFilters['keyword']->filters->data : false;
        $productSearchFilterID = isset($this->appliedFilters['p']->filters->fltr_id) ? $this->appliedFilters['p']->filters->fltr_id : 0;
        $categorySearchFilterID = isset($this->appliedFilters['c']->filters->fltr_id) ? $this->appliedFilters['c']->filters->fltr_id : 0;

        if($keywordSearch !== '' && $keywordSearch !== null && $keywordSearch !== false){
            ActionCapture::record('keyword', 'search', substr($keywordSearch, 0, ActionCapture::OBJECT_MAX_LENGTH));
            $searchPerformed = true;
        }

        foreach($reportQueryData as $filter){
            if(isset($filter['name']) && ($filter['name'] === $productSearchFilterID || $filter['name'] === $categorySearchFilterID)){
                //Data stored in the filter is in the format {level.ID;level.ID...}
                $itemChain = array_filter(explode(';', (isset($filter['val']) && $filter['val']) ? $filter['val'] : ''));
                foreach($itemChain as $idAndLevelCombination){
                    if($idAndLevelCombination === ANY_FILTER_VALUE){
                        continue;
                    }
                    $searchPerformed = true;
                    $idAndLevelArray = explode('.', $idAndLevelCombination);
                    $hierMenuID = end($idAndLevelArray);
                    if($filter['name'] === $productSearchFilterID){
                        ActionCapture::record('product', 'search', $hierMenuID);
                    }
                    else{
                        ActionCapture::record('category', 'search', $hierMenuID);
                    }
                }
            }
        }

        if($searchPerformed || (isset($this->appliedFilters['search']) && $this->appliedFilters['search'])){
            ActionCapture::record('report', 'search', $this->reportID);
        }
    }

    /**
     * Fetches the results from the views engine
     * @param resource $vhandle Views engine resource
     * @param int $maxResults The max number of results to get
     * @return array List of results
     */
    protected function getViewResults($vhandle, $maxResults)
    {
        $dataArray = array();
        while ((count($dataArray) < $maxResults) && ($row = Api::view_fetch($vhandle)))
        {
            $dataArray []= $row;
        }
        return $dataArray;
    }

    /**
     * Gets the exceptions out of the data column definition. These are used to add color to 'new' and 'updated'.
     * @param resource $vhandle Views engine resource
     * @return array Exceptions from the report
     */
    protected function getViewExceptions($vhandle)
    {
        $exceptions = array();
        if($this->viewDataColumnDefinition)
        {
            foreach($this->viewDataColumnDefinition as $column)
            {
                Api::view_bind_col($vhandle, $column['bind_pos'], $column['bind_type'], $column['bind_size'] + 1);
                if($column['type'] & VIEW_CTYPE_EXCEPTION)
                {
                    $exceptions []= $column['bind_pos'] - 1;
                }
            }
        }
        return $exceptions;
    }

    /**
     * Get the number_per_page. Order is
     * 1 - per_page attribute in widget
     * 2 - user profile set from the setFilters function
     * 3 - report
     * 4 - default of 15
     * $this->filters['per_page'] and $this->filters['sitemap'] should be set prior to this
     * @param int $reportPerPageSetting Number of results to display
     * @return int Number of results per page
     */
    protected function getNumberPerPage($reportPerPageSetting)
    {
        if(isset($this->appliedFilters['per_page']) && $this->appliedFilters['per_page'])
        {
            return ($this->appliedFilters['per_page'] < 0) ? 0 : intval($this->appliedFilters['per_page']);
        }
        return $reportPerPageSetting ?: 15;
    }

    /**
     * Sets the max results based on the search limiting config
     * @param int $reportPerPageSetting Number of results to display per page
     * @return void
     */
    protected function setMaxResultsBasedOnSearchLimiting($reportPerPageSetting)
    {
        $searchResultLimitingStyle = Config::getConfig(SEARCH_RESULT_LIMITING);
        if((!isset($this->appliedFilters['no_truncate']) || !$this->appliedFilters['no_truncate']) && 
            (!isset($this->appliedFilters['page']) || !($this->appliedFilters['page'] > 1)) && 
            $searchResultLimitingStyle && $this->appliedFilters['keyword']->filters->data)
        {
            $this->returnData['truncated'] = 1;
            $reportPerPageSetting = $reportPerPageSetting ?: 15;
            if($searchResultLimitingStyle === 1)
            {
                $this->returnData['max_results'] = (9 * $reportPerPageSetting) / 10;
            }
            else if($searchResultLimitingStyle === 2)
            {
                $this->returnData['max_results'] = (5 * $reportPerPageSetting) / 10;
            }
            else if($searchResultLimitingStyle >= 3)
            {
                $this->returnData['max_results'] = (2 * $reportPerPageSetting) / 10;
            }
            else
            {
                $this->returnData['max_results'] = 0;
            }
        }
    }

    /**
     * Formats all the data with HTML links and appropriate date and currency formatting.
     *
     * @param bool $formatAsHtml If true html links are added for column links and exceptions tags are added
     * @param bool $hiddenColumnsIncluded Whether or not to include hidden columns as part of the return data
     * @return void
     */
    protected function formatViewsData($formatAsHtml = true, $hiddenColumnsIncluded = false)
    {
        $formattedDataCacheKey = "getFormattedData$this->reportID" . crc32(serialize($this->appliedFormats) . serialize($this->returnData)) . ($hiddenColumnsIncluded ? 'withHiddenColumns' : '');
        $formattedAidCacheKey = "getFormattedAid$this->reportID" . crc32(serialize($this->appliedFormats) . serialize($this->returnData));

        if(null !== ($cachedResult = Framework::checkCache($formattedDataCacheKey)))
        {
            if(null !== ($aidResult = Framework::checkCache($formattedAidCacheKey)))
            {
                $this->answerIDList = $aidResult;
            }
            $this->returnData['data'] = $cachedResult;
            return;
        }
        $columnExceptionList = $this->setExceptionTags($this->viewDefinition['exceptions'], $this->returnData['exceptions']);
        $dataSize = count($this->returnData['data']);
        for($i = 0; $i < $dataSize; $i++)
        {
            $icon = '';
            $row = $this->returnData['data'][$i];
            $columnCount = count($this->viewDataColumnDefinition);
            $count = 0;
            $answersIDListIsUpdated = false;
            $temp = array();
            // using visibleColIndex to eliminate any hidden cols in between
            $visibleColIndex = 1;

            for($j = 0; $j < $columnCount; $j++)
            {
                $truncateSize = 0;
                $currentField = isset($this->viewDefinition['all_cols']["field$j"]) ? $this->viewDefinition['all_cols']["field$j"] : null;
                if($currentField)
                {
                    $columnDefinition = isset($currentField['col_definition']) ? $currentField['col_definition'] : null;
                    if($columnDefinition === ($this->answerTableAlias . '.a_id') && !$answersIDListIsUpdated)
                    {
                        $this->answerIDList[] = $row[$j];
                        $answersIDListIsUpdated = true;
                    }
                }

                if($this->viewDataColumnDefinition["col_item{$j}"]['val'] === "answers.url" && $row[$j] !== "" && $formatAsHtml)
                    $icon = Framework::getIcon($row[$j]);

                if((isset($this->viewDataColumnDefinition["col_item{$j}"]['hidden']) && ($this->viewDataColumnDefinition["col_item{$j}"]['hidden'] === 0)) || $hiddenColumnsIncluded)
                {
                    // the column is visible or it's hidden but we're told to include it
                    $bindType = $this->viewDataColumnDefinition["col_item{$j}"]['bind_type'];

                    if($bindType == BIND_MEMO && ($columnDefinition === "{$this->answerTableAlias}.solution" || $columnDefinition === "{$this->answerTableAlias}.description" || $this->reportIsTypeExternalDocument))
                    {
                        //expand answer tags if the column definition is either answer.solution or answer.description or the report is widx
                        $temp[$count] = Text::expandAnswerTags($row[$j]);
                        //The truncate_size attribute only applies to answer.solution and answer.description
                        $truncateSize = isset($this->appliedFormats['truncate_size']) ? $this->appliedFormats['truncate_size'] : 0;
                    }
                    else if($bindType == BIND_NTS || $bindType == BIND_MEMO)
                    {
                        //escape any text fields (long or short)
                        //This is analagous to the Formatter::formatField function which will escape all strings unless they are contained in the Answer or AnswerContent classes
                        //Conveniently for the Formatter::formatField logic, custom fields are within other classes, such as the AnswerContentCustomFieldsCO class, which means those fields get escaped
                        $flags = 0;
                        if(isset($currentField['col_definition']) && ($currentField['col_definition'] === "{$this->socialCommentTableAlias}.body" || $currentField['col_definition'] === "{$this->socialQuestionTableAlias}.body"))
                        {
                            $truncateSize = isset($this->appliedFormats['truncate_size']) ? $this->appliedFormats['truncate_size'] : 0;
                        }
                        else
                        {
                            $flags = OPT_ESCAPE_SCRIPT | OPT_ESCAPE_HTML;
                        }
                        if($this->answerTableAlias && Text::beginsWith($columnDefinition, $this->answerTableAlias)) {
                            $flags |= OPT_VAR_EXPAND;
                        }

                        $temp[$count] = Api::print_text2str($row[$j], $flags);
                    }
                    else if($bindType == BIND_DATE || $bindType == BIND_DTTM)
                    {
                        if(!empty($row[$j]))
                        {
                            $formatDefine = DATEFMT_SHORT;
                            $formatSpecified = '';
                            if(isset($this->appliedFormats['raw_date']) && $this->appliedFormats['raw_date'])
                            {
                                $formatDefine = false;
                            }
                            else if((isset($this->appliedFormats['dateFormat']) && $formatSpecified = $this->appliedFormats['dateFormat']) && $formatSpecified !== 'short')
                            {
                                if($formatSpecified === 'long')
                                    $formatDefine = DATEFMT_LONG;
                                else if($formatSpecified === 'date_time')
                                    $formatDefine = DATEFMT_DTTM;
                                else if($formatSpecified === 'raw')
                                    $formatDefine = false;
                            }

                            if ($formatDefine !== false)
                            {
                                if(!is_int($row[$j]))
                                   $dateTimeString = Api::date_str($formatDefine, $row[$j]);
                                else {
                                   if($formatDefine === DATEFMT_SHORT && $formatSpecified === 'short')
                                      $dateTimeString = Api::date_str($formatDefine, $row[$j]);
                                   else
                                      $dateTimeString = Api::date_str(DATEFMT_DTTM, $row[$j]);
                                }
                                $dateTimeArray = explode(' ', $dateTimeString);
                                if(count($dateTimeArray) === 4){
                                    list($dateString, $timeString, $suffixString, $yearString) = $dateTimeArray;
                                }else if(count($dateTimeArray) === 3){
                                    list($dateString, $timeString, $suffixString) = $dateTimeArray;
                                }else if(count($dateTimeArray) === 2){
                                    list($dateString, $timeString) = $dateTimeArray;
                                }else if(count($dateTimeArray) === 1){
                                    list($dateString) = $dateTimeArray;
                                }
                                 
                                if ($formatSpecified === 'date_time' || $formatSpecified === 'long')
                                {
                                    $dateTimeString = sprintf('<span>%s</span> <span>%s %s %s</span>', $dateString, $timeString, $suffixString, $yearString);
                                }
                            }
                            else
                            {
                                $dateTimeString = $row[$j];
                            }
                            $temp[$count] = $dateTimeString;
                        }
                        else
                        {
                            $temp[$count] = (isset($this->appliedFormats['raw_date']) && $this->appliedFormats['raw_date']) ? null : '';
                        }
                    }
                    else if($bindType == CP_BIND_BIG_DATE)
                    {
                        if(!empty($row[$j]))
                        {
                            $bigDateString = date_create($row[$j]);
                            $dateTimeString = date_format($bigDateString, "m/d/Y");
                            list($dateString, $timeString, $suffixString, $yearString) = explode(' ', $dateTimeString);
                            $dateTimeString = sprintf('<span>%s</span> <span>%s %s %s</span>', $dateString, $timeString, $suffixString, $yearString);

                            $temp[$count] = $dateTimeString;
                        }
                        else
                        {
                            $temp[$count] = ($this->appliedFormats['raw_date']) ? null : '';
                        }
                    }
                    else if($bindType == BIND_CURRENCY )
                    {
                        $temp[$count] = Api::currency_str($row[$j]->currency_id, $row[$j]->value);
                    }
                    else
                    {
                        $temp[$count] = $row[$j];
                    }

                    $temp[$count] = $this->sanitizeData($temp[$count], $visibleColIndex, $currentField, $row);
                    if($truncateSize > 0)
                    {
                        $temp[$count] = Text::truncateText($temp[$count], $truncateSize, true, $this->appliedFormats['max_wordbreak_trunc']);
                    }

                    if($formatAsHtml)
                    {
                        // add highlighting to non-numeric columns
                        if(((isset($this->appliedFormats['highlight']) && $this->appliedFormats['highlight']) || (isset($this->appliedFormats['emphasisHighlight']) && $this->appliedFormats['emphasisHighlight'])) && ($bindType !== BIND_INT))
                        {  
                            $searchTermArray = explode(' ', $this->returnData['search_term']);
                            if(count($searchTermArray))
                            {
                                $text = $temp[$count];
                                if($this->appliedFormats['emphasisHighlight'])
                                    $text = Text::emphasizeText($text, array('query' => $this->returnData['search_term']));
                                else
                                    $text = Text::highlightTextHelper($text, $this->returnData['search_term'], $this->appliedFormats['highlightLength']);
                                $temp[$count] = $text;
                            }
                        }

                        // add exceptions
                        if(in_array($j + 1, $columnExceptionList))
                        {
                            foreach($this->viewDefinition['exceptions'] as $k => $v)
                            {
                                if($row[$this->viewDefinition['exceptions'][$k]['data_col']] > 0 && $this->viewDefinition['exceptions'][$k]['col_id'] - 1 == $j)
                                {
                                    $temp[$count] = $this->viewDefinition['exceptions'][$k]['start_tag'].$temp[$count].$this->viewDefinition['exceptions'][$k]['end_tag'];
                                    break;
                                }
                            }
                        }
                    }

                    //add links
                    $url = $target = "";
                    if($currentField && isset($currentField['url_info'])){
                        $url = $currentField['url_info']['url'];
                        if ($url !== "") {
                            $url = $this->replaceColumnLinks($url, $row, isset($this->appliedFormats['urlParms']) ? $this->appliedFormats['urlParms'] : '');
                        }
                        $target = $currentField['url_info']['target'];
                    }
                    if($url != "" && (!empty($temp[$count]) || $temp[$count] === '0'))
                    {
                        if(isset($this->appliedFormats['no_session']) && $this->appliedFormats['no_session'])
                            $str = "<a href='{$url}' ";
                        else
                            $str = "<a href='{$url}" . \RightNow\Utils\Url::sessionParameter() . "'";

                        if($target != "")
                        {
                            $target = $this->replaceColumnLinks($target, $row);
                            $str .= " target='$target' ";
                        }
                        if(isset($this->appliedFormats['tabindex']) && $this->appliedFormats['tabindex'])
                        {
                            $str .= " tabindex='{$this->appliedFormats['tabindex']}{$i}' ";
                        }
                        $str .= '>' . $temp[$count] . '</a>';
                        $temp[$count] = $str;
                    }

                    // Sanitize data

                    $count++;
                }
            }
            if($icon)
                $temp[0] = "{$icon} {$temp[0]}";

            $dataArray[$i] = $temp;
        }
        if(isset($dataArray) && is_array($dataArray) && count($dataArray))
        {
            $this->returnData['data'] = $dataArray;
        }
        Framework::setCache($formattedDataCacheKey, $this->returnData['data']);
        Framework::setCache($formattedAidCacheKey, $this->answerIDList);
    }

    /**
     * Adds array elements of start tag and end tag which can be used as inline styles
     * @param array|null &$exceptions Array of exceptions
     * @param array|null $dataFields Array of data fields
     * @return array List of column IDs
     */
    protected function setExceptionTags(&$exceptions, $dataFields)
    {
        $exCount = 0;
        $colList = array();
        $exceptions = (is_array($exceptions)) ? $exceptions : array();
        foreach($exceptions as $key => $value)
        {
            $colList[$exCount] = $value['col_id'];
            if(is_array($exceptions[$key])){
                $exceptions[$key]['data_col'] = $dataFields[$exCount++];
            }
            $arr = $this->xmlToArray($value['xml_data']);
            foreach($arr as $secondaryValue)
            {
                if($secondaryValue['name'] === "Style")
                {
                    $elems = $secondaryValue['elements'];
                    $start = "";
                    $end = "";
                    foreach($elems as $type => $style)
                    {
                        if($style['name'] === "ForeColorString")
                        {
                            $color = $style['text'];
                            if(strlen($color) == 8)
                            {
                                $color = substr($color, 2);
                            }
                            $start .= "color:#{$color}";
                        }
                    }
                    if($start != "")
                    {
                        $start = "<span style='{$start}'>";
                        $end = "</span>";
                    }
                    if(is_array($exceptions[$key])){
                        $exceptions[$key]['start_tag'] = $start;
                        $exceptions[$key]['end_tag'] = $end;
                    }
                }
            }
        }
        return $colList;
    }

    /**
     * Changes an xml structure into an array
     * @param string $xml XML formatted string
     * @return array Data converted to an array
     */
    protected function xmlToArray($xml)
    {
        $xmlArray = array();
        $reels = '/<(\w+)\s*([^\/>]*)\s*(?:\/>|>(.*)<\/\s*\\1\s*>)/s';
        $reattrs = '/(\w+)=(?:"|\')([^"\']*)(:?"|\')/';
        preg_match_all($reels, $xml, $elements);
        foreach($elements[1] as $key => $value) {
            $xmlArray[$key]['name'] = $value;
            if($attributes = trim($elements[2][$key])) {
                preg_match_all($reattrs, $attributes, $attributeArray);
                foreach($attributeArray[1] as $nestedKey => $nestedValue)
                    $xmlArray[$key]['attributes'][$nestedValue] = $attributeArray[2][$nestedKey];
            }

            $endPosition = strpos($elements[3][$key], '<');
            if($endPosition > 0)
                $xmlArray[$key]['text'] = substr($elements[3][$key], 0, $endPosition - 1);

            if(preg_match($reels, $elements[3][$key]))
                $xmlArray[$key]['elements'] = $this->xmlToArray($elements[3][$key]);
            else if($elements[3][$key])
                $xmlArray[$key]['text'] = $elements[3][$key];
        }
        return $xmlArray;
    }

    /**
     * If filter is of type VDT_INT, non-integer parts of the value will be removed and an error message added
     * @param string $searchTypeName Search type being used
     * @param string $keywordValue Value of current keyword
     * @return string The possibly modified $keywordValue
     */
    protected function cleanKeywordValue($searchTypeName, $keywordValue)
    {
        if($keywordValue === "")
            return $keywordValue;

        $runtimeFilters = $this->getRuntimeFilters($this->reportID)->result;
        foreach($runtimeFilters as $runtimeFilter)
        {
            if($searchTypeName === $runtimeFilter['fltr_id'])
                $dataType = $runtimeFilter['data_type'];
        }

        if($dataType === VDT_INT)
        {
            if(Text::stringContains($keywordValue, ";"))
            {
                $valuesToTest = explode(";", $keywordValue);
                $valuesToUse = array();
                foreach($valuesToTest as $valueToTest)
                {
                    $valueToTest = trim($valueToTest);
                    if($valueToTest === "")
                        continue;
                    $valueToTestInt = intval($valueToTest, 10);
                    // only accept a value that parses exactly
                    if(strval($valueToTestInt) === $valueToTest)
                        $valuesToUse []= $valueToTest;
                    else
                        $this->returnData['error'] = sprintf(Config::getMessage(VAL_PCT_S_PCT_S_INT_KEYWORD_MSG), $valueToTest, $keywordValue);
                }
                $keywordValue = implode(";", $valuesToUse);
            }
            else
            {
                $keywordValue = trim($keywordValue);
                $keywordValueInt = intval($keywordValue, 10);
                // only accept a value that parses exactly
                if(strval($keywordValueInt) !== $keywordValue)
                {
                    $this->returnData['error'] = sprintf(Config::getMessage(VAL_PCT_S_INT_KEYWORD_SEARCHING_CHG_MSG), $keywordValue);
                    $keywordValue = "";
                }
            }
        }
        return $keywordValue;
    }

    /**
     * Validate that passed in filter ID is one of the accepted values
     * @param string|int $filterID Filter ID value
     * @return boolean Whether filter ID is valid
     */
    protected function isFilterIDValid($filterID)
    {
        if (is_numeric($filterID))
        {
            return $this->isNumericFilterIDValid($filterID);
        }

        if ($this->isColumnAliasFilterIDValid($filterID))
        {
            return true;
        }

        return $this->isNamedFilterIDValid($filterID);
    }

    /**
     * Validate that numeric filter ID is a valid runtime filter
     * @param int $filterID Filter ID value
     * @return boolean Whether filter ID is valid
     */
    protected function isNumericFilterIDValid($filterID)
    {
        $runtimeFilters = $this->getRuntimeFilters($this->reportID)->result;

        foreach($runtimeFilters as $runtimeFilter)
        {
            if ($runtimeFilter['fltr_id'] === intval($filterID))
            {
                return true;
            }
        }

        return false;
    }

    /**
     * Validate the string filter ID is a valid runtime filter
     * @param string $filterID Filter ID value
     * @return boolean Whether filter ID is valid
     */
    protected function isNamedFilterIDValid($filterID)
    {
        $runtimeFilters = $this->getRuntimeFilters($this->reportID)->result;

        foreach($runtimeFilters as $runtimeFilter)
        {
            if ($runtimeFilter['name'] === $filterID)
            {
                return true;
            }
        }

        return false;
    }

    /**
     * Validate the column alias filter ID is one of the whitelisted values
     * @param string $filterID Filter ID value
     * @return boolean Whether filter ID is valid
     */
    protected function isColumnAliasFilterIDValid($filterID)
    {
        $incidentAlias = $this->getIncidentAlias($this->reportID)->result;
        $organizationAlias = $this->getOrganizationAlias($this->reportID)->result;
        $assetAlias = $this->getAssetAlias($this->reportID)->result;

        /*
         * matches:
         *  incidents.c_id or incidents.org_id
         *  orgs.lvlX_id
         *  assets.c_id
         */
        return ($incidentAlias && preg_match("/^$incidentAlias\.(c_id|org_id)$/", $filterID)) ||
            ($organizationAlias && preg_match("/^$organizationAlias\.lvl\d_id$/", $filterID)) ||
            ($assetAlias && preg_match("/^$assetAlias\.c_id$/", $filterID));
    }

    /**
     * Takes an array of named search elements and converts it into the appropriate search arg array
     *
     * @return array An array of search args in an array for views engine
     */
    protected function filtersToSearchArgs()
    {
        $searchArgs = array();
        if(isset($this->appliedFilters['search']) && ($this->appliedFilters['search'] === '0' || $this->appliedFilters['search'] === 0) && !$this->appliedFilters['no_truncate'])
        {
            return $searchArgs;
        }

        $keywordValue = "";
        $seenKeyword = false;
        $seenSearchType = false;
        $searchTypeName = "";
        $searchTypeOperator = "";
        $contactData = false;
        $count = 0;

        if(is_array($this->appliedFilters)){
            foreach($this->appliedFilters as $key => $value)
            {
                // these are search filters
                if(!isset($value->filters->rnSearchType)){
                    continue;
                }
                // map to new events
                if(isset($value->filters->data->fltr_id))
                    $value->filters->fltr_id = $value->filters->data->fltr_id;
                if(isset($value->filters->data->oper_id))
                    $value->filters->oper_id = $value->filters->data->oper_id;
                if(isset($value->filters->data->val))
                    $value->filters->data = $value->filters->data->val;

                if((isset($value->filters->fltr_id) && $value->filters->fltr_id) && !$this->isFilterIDValid($value->filters->fltr_id)) {
                    continue;
                }

                // handle keyword term
                if($key === 'keyword')
                {
                    $seenKeyword = true;
                    $keywordValue = $value->filters->data;
                    $this->returnData['search_term'] = $keywordValue;
                    if($searchTypeName && $keywordValue)
                    {
                        $this->returnData['search'] = true;
                    }
                    if($seenKeyword && $seenSearchType)
                    {
                        $keywordValue = $this->cleanKeywordValue($searchTypeName, $keywordValue);
                        $searchArgs['search_field' . $count++] = $this->toFilterArray($searchTypeName, intval($searchTypeOperator), $keywordValue);
                    }
                }
                // handle search types
                else if($key === 'searchType')
                {
                    $seenSearchType = true;
                    $searchTypeName = $value->filters->fltr_id;
                    $searchTypeOperator = $value->filters->oper_id;
                    $this->returnData['search_type'] = $value->filters->fltr_id;
                    if($searchTypeName && $keywordValue)
                    {
                        $this->returnData['search'] = true;
                    }
                    if($seenKeyword && $seenSearchType)
                    {
                        $keywordValue = $this->cleanKeywordValue($searchTypeName, $keywordValue);
                        $searchArgs['search_field' . $count++] = $this->toFilterArray($searchTypeName, intval($searchTypeOperator), $keywordValue);
                    }
                }
                else if($key === 'org')
                {
                    if($value->filters->fltr_id)
                    {
                        $contactData = true;
                        $searchArgs['search_field' . $count++] = $this->toFilterArray(strval($value->filters->fltr_id),
                            intval($value->filters->oper_id),
                            strval($value->filters->val) ? $value->filters->val : $value->filters->data);
                    }
                    else
                    {
                        continue;
                    }
                }
                else if($key === 'pc')
                {
                    $valArray = $value->filters->data;
                    $val = null;
                    if(is_array($valArray) && $valArray[0] !== null)
                    {
                        $val = end($valArray[0]);
                    }
                    else if(is_string($valArray))
                    {
                        $data = explode(',', $valArray);
                        $val = end($data);
                    }
                    $searchArgs['search_field' . $count++] = $this->toFilterArray($value->filters->fltr_id, $value->filters->oper_id, $val ?: ANY_FILTER_VALUE);
                }
                else
                {
                    $vals = "";
                    $values = $value->filters->data;
                    // $values can be either number or an array 
                    if(is_array($values) && $values  ? count($values) : $values)
                    {
                        if(!is_array($values))
                        {
                            $values = array($values);
                        }

                        foreach($values as $k => $v)
                        {
                            if($value->filters->rnSearchType === 'menufilter')
                            {
                                $size = is_array($v) ? count($v) : 0;
                                if($v !== null && ((int)$v === -1 || (isset($v[0]) && (int)$v[0] === -1))) {
                                    //construct the filter value for "No Value"
                                    $vals = '1.u0';
                                    break;
                                }
                                else if(is_array($v) && (isset($v[$size]) && $v[$size - 1] && $v[$size - 1] > 0))
                                {
                                    $vals .= "{$size}." . $v[$size - 1] . ";";
                                }
                                else if(is_array($v))
                                {
                                    for($i = $size - 1; $i >= 0; $i--)
                                    {
                                        if($v[$i] != null && $v[$i] != "")
                                        {
                                            $vals .= ($i + 1) . "." . $v[$i] . ";";
                                            break;
                                        }
                                    }
                                }
                                else if(is_string($v))
                                {
                                    $temp = explode(',', $v);
                                    $s = count($temp);
                                    $last = 0;
                                    $num = 0;
                                    for($i = 0; $i < $s; $i++)
                                    {
                                        if($temp[$i])
                                        {
                                            $last = $temp[$i];
                                            $num = $i + 1;
                                        }
                                    }
                                    if($last > 0)
                                    {
                                        $vals .= "$num.$last;";
                                    }
                                }
                                else if($v)
                                {
                                    foreach($v as $node => $data)
                                    {
                                        if($node == '0')
                                        {
                                            if(is_string($data))
                                                $data = explode(',', $data);
                                            $s = count($data);
                                            $last = $num = 0;
                                            for($i = 0; $i < $s; $i++)
                                            {
                                                if($data[$i])
                                                {
                                                    $last = $data[$i];
                                                    $num = $i + 1;
                                                }
                                            }
                                            if($last > 0)
                                            {
                                                $vals .= "$num.$last;";
                                            }
                                        }
                                    }
                                }
                                // error check for bad data
                                $temp = explode('.', $vals);
                                if(!intval($temp[0]) || !intval($temp[1]))
                                    $vals = null;
                            }
                            else
                            {
                                $vals = ((isset($v->fltr_id) && $v->fltr_id) || (isset($v->oper_id) && $v->oper_id) || (isset($v->val) && $v->val)) ? $v->val : $v;
                            }
                        }
                        if($vals || $vals === '0')
                        {
                            $searchArgs['search_field' . $count++] = $this->toFilterArray($value->filters->fltr_id, $value->filters->oper_id, $vals);
                        }
                        else
                        {
                            $searchArgs['search_field' . $count++] = $this->toFilterArray($value->filters->fltr_id, $value->filters->oper_id, ANY_FILTER_VALUE);
                        }
                    }
                    else
                    {
                        $searchArgs['search_field' . $count++] = $this->toFilterArray($value->filters->fltr_id, $value->filters->oper_id, ANY_FILTER_VALUE);
                    }            
                }
            }
        }
        $searchArgs = $this->addContactInformation($contactData, $searchArgs, $count);
        return $searchArgs;
    }

    /**
     * Creates an array of output variables to be used for the view query.
     * @return array Output variables to be used in the view query
     */
    protected function filtersToOutputVariables() {
        $outputVariables = array();
        $index = 0;
        if($contactSessionAlias = Sql::view_tbl2alias($this->reportID, TBL_CONTACT_SESSIONS)) {
            foreach($this->viewDefinition['all_cols'] as $column) {
                if(Text::stringContains($column['col_definition'], '$sessionid')) {
                    // Add a sessionid output variable only if a column depends on it for a calculation
                    $outputVariables['param_item' . $index++] = array('param_id' => $index, 'val' => $this->CI->session->getSessionData('sessionID'));
                    break;
                }
            }
        }
        return $outputVariables;
    }

    /**
     * Adds contact information to the search args.
     * @param boolean $contactDataSet Whether to add the contact info for incidents (an org-filtered search isn't being done)
     * @param array|null $searchArgs Contains all query arguments for the search
     * @param int $index The index to add the contact info into the array
     * @return array The searchArgs modified array
     */
    protected function addContactInformation($contactDataSet, $searchArgs, $index = 0)
    {
        $contactID = isset($this->CI->session) ? ($this->CI->session->getProfileData('contactID') ?: 0) : 0;
        $assetID = \RightNow\Utils\Url::getParameter('asset_id');
        if($this->incidentTableAlias && !$contactDataSet)
        {
            // set contact
            $searchArgs['search_field' . $index++] = $this->toFilterArray("{$this->incidentTableAlias}.c_id", OPER_EQ, strval($contactID));
        }

        if($this->assetTableAlias && !$contactDataSet)
        {
            $searchArgs['search_field' . $index++] = $this->toFilterArray("{$this->assetTableAlias}.c_id", OPER_EQ, strval($contactID));
        }

        if($this->incidentTableAlias && $assetID)
        {
            $searchArgs['search_field' . $index++] = $this->toFilterArray("{$this->incidentTableAlias}.asset_id", OPER_EQ, strval($assetID));
        }

        $answerNotificationTableAlias = Sql::view_tbl2alias($this->reportID, TBL_ANS_NOTIF);
        if($answerNotificationTableAlias)
        {
            $searchArgs['search_field' . $index++] = $this->toFilterArray('ans_notif.interface_id', OPER_EQ, strval(Api::intf_id()));
            $searchArgs['search_field' . $index++] = $this->toFilterArray("$answerNotificationTableAlias.c_id", OPER_EQ, strval($contactID));
        }

        $slaTableAlias = Sql::view_tbl2alias($this->reportID, TBL_SLA_INSTANCES);
        if($slaTableAlias)
        {
            // set contact
            if(($orgID = $this->CI->session->getProfileData('orgID')) && $orgID !== INT_NULL)
            {
                $searchArgs['search_field' . $index++] = $this->toFilterArray("$slaTableAlias.owner_id", OPER_EQ, strval($orgID));
                $searchArgs['search_field' . $index++] = $this->toFilterArray("$slaTableAlias.owner_tbl", OPER_EQ, strval(TBL_ORGS));
            }
            else
            {
                $searchArgs['search_field' . $index++] = $this->toFilterArray("$slaTableAlias.owner_id", OPER_EQ, strval($contactID));
                $searchArgs['search_field' . $index++] = $this->toFilterArray("$slaTableAlias.owner_tbl", OPER_EQ, strval(TBL_CONTACTS));
            }
        }

        $contactSessionAlias = Sql::view_tbl2alias($this->reportID, TBL_CONTACT_SESSIONS);
        if($contactSessionAlias)
        {
            $searchArgs['search_field' . $index++] = $this->toFilterArray("$contactSessionAlias.c_id", OPER_EQ, " $contactID");
        }
        return $searchArgs;
    }

    /**
     * Converts a filter operation into the expected format of the views engine.
     *
     * @param string $name Name of filter
     * @param string $oper Operation being applied
     * @param string $val Value to check against
     * @return array Values converted into filter array
     */
    protected function toFilterArray($name, $oper, $val)
    {
        if(strlen(trim($val)) === 0)
            $val = null;
        return array(
            'name' => $name,
            'oper_id' => $oper,
            'val' => $val,
        );
    }

    /**
     * Converts search filter into sort args expected by the views engine.
     * @return array Converted sort filter
     */
    protected function filtersToSortArgs()
    {
        if(!isset($this->appliedFilters['sort_args']) || !$this->appliedFilters['sort_args'])
            return null;
         // from php controller
        if(is_array($this->appliedFilters['sort_args'])){
            if(isset($this->appliedFilters['sort_args']['filters']['sort_field0']))
                return $this->appliedFilters['sort_args']['filters'];
            return array('sort_field0' => $this->appliedFilters['sort_args']['filters']);
        }
        $sortArgs = $this->appliedFilters['sort_args']->filters;  // from javascript
        $sortFilters = (isset($sortArgs->data) && $sortArgs->data) ? $sortArgs->data : $sortArgs;
        return array(
            'sort_field0' => array(
                'col_id' => intval($sortFilters->col_id),
                'sort_direction' => intval($sortFilters->sort_direction),
                'sort_order' => 1,
            )
        );
    }

    /**
     * Returns true if the search filter is type complex
     * @param int $searchType Search type to check
     * @return bool Whether search filter is complex
     */
    protected function getComplex($searchType)
    {
        if(!$searchType)
        {
            return false;
        }
        $filter = $this->getFilterById($this->reportID, $searchType)->result;
        return (Text::stringContains($filter['expression1'], 'search_cpx'));
    }

    /**
     * Sets the cluster tree filter object in the $this->appliedFilters array to the cluster ID of the best match found by the topic browse model.
     * @return void
     */
    protected function preProcessClusterTreeFilter()
    {
        if($this->getClusterToAnswersAlias($this->reportID)->result)
        {
            if($this->appliedFilters['keyword']->filters->data && !(is_array($this->appliedFilters['parent']->filters->data) || $this->appliedFilters['parent']->filters->data->val))
            {
                //check for a best match cluster ID only if there's search terms and there's no cluster tree parent ID already being passed in
                $bestClusterID = $this->CI->model('Topicbrowse')->getBestMatchClusterID($this->appliedFilters['keyword']->filters->data)->result;
                if($bestClusterID)
                {
                    $filter = $this->getFilterByName($this->reportID, 'cluster_tree2answers.parent_id')->result;
                    $this->appliedFilters['parent'] = $this->createSearchFilter($this->reportID, 'parent', $filter['fltr_id'], $bestClusterID, 'topicBrowse', $filter['oper_id'])->result;
                }
            }
            $this->reportIsJoinedOnClusterTree = true;
        }
    }

    /**
     * Returns if the report number is WIDX
     * @param int $reportID The report ID to check
     * @return bool Whether the report is WIDX
     */
    protected function isReportNumberWidx($reportID)
    {
        return ((intval($reportID) === CP_WIDX_REPORT_DEFAULT || intval($reportID) === CP_NOV09_WIDX_DEFAULT) );
    }

    /**
     * Gets the table name aliased for provided table
     * @param int $reportID ID of report to check
     * @param int $table Table define
     * @return string|null The table alias or null if it doesn't exist
     */
    private function getTableAlias($reportID, $table)
    {
        if($this->isReportWidx($reportID))
            return $this->getResponseObject(null, 'is_null');
        $cacheKey = "getTableAlias{$reportID}table{$table}";
        $alias = Framework::checkCache($cacheKey);
        if($alias === null){
            $alias = Sql::view_tbl2alias($reportID, $table);
            Framework::setCache($cacheKey, $alias);
        }
        return $this->getResponseObject($alias, null);
    }

    /**
     * Convert number of bytes into more human readable form
     * @param int $bytes The number of bytes
     * @return string Bytes converted to an easier to consume format string
     */
    private static function convertBytesToLargestUnit($bytes)
    {
        if($bytes < 1024)
            return "{$bytes}b";
        if($bytes < 1048576)
            return round($bytes / 1024, 0) . 'KB';
        if($bytes < 1073741824)
            return round($bytes / 1048576, 0) . 'MB';
        // The numeric literal for TB must have ".0" on the end or PHP silently overflows and doesn't calculate correctly.
        if($bytes < 1099511627776.0)
            return round($bytes / 1073741824, 0) . 'GB';
        return round($bytes / 1099511627776.0, 2) . 'TB';
    }

    /**
     * Defines the column mapping between the main report and the sub reports.
     *
     * A Report(Ex:15100) to be executed by sup reports has to be added with the below details
     * 1. SubReportMapping - Key in this mapping is a regular expression which in the format "FilterName.Main report Soring column index"
     *                       Value is the array of sub report Id and the index of sorting column to be used.
     * 2. MainReportFilterID - The name of the IDs filter to be set with the IDs collected from the main report
     * 3. MainReportDefaultSortColID - The default Sorting column details in the main report
     *
     * @return array Array of Sub Report Mapping
     */
    private static function getSubReportMapping(){
        static $subReportMapping = array();
        if (!$subReportMapping) {
            $subReportMapping = array(15100 => array("SubReportMapping" => array(4 => array("SubReportID" => 15144, "SubReportColID" => 2),
                        "question_content_flags.flag4" => array("SubReportID" => 15145, "SubReportColID" => 2),
                        "p4" => array("SubReportID" => 15146, "SubReportColID" => 2),
                        "c4" => array("SubReportID" => 15147, "SubReportColID" => 2),
                        ".+4" => array("SubReportID" => 15123, "SubReportColID" => 2),
                        8 => array("SubReportID" => 15140, "SubReportColID" => 2),
                        "question_content_flags.flag8" => array("SubReportID" => 15141, "SubReportColID" => 2),
                        "p8" => array("SubReportID" => 15142, "SubReportColID" => 2),
                        "c8" => array("SubReportID" => 15143, "SubReportColID" => 2),
                        ".+8" => array("SubReportID" => 15122, "SubReportColID" => 2),
                    ), "MainReportIDFilter" => "question_id", "FilterNamesOnJoins" => array('questions.updated', 'questions.status', 'p', 'c', 'question_content_flags.flag')
                ),
                15101 => array("SubReportMapping" => array(5 => array("SubReportID" => 15152, "SubReportColID" => 2),
                        "comment_cnt_flgs.flag5" => array("SubReportID" => 15153, "SubReportColID" => 2),
                        "p5" => array("SubReportID" => 15154, "SubReportColID" => 2),
                        "c5" => array("SubReportID" => 15155, "SubReportColID" => 2),
                        ".+5" => array("SubReportID" => 15125, "SubReportColID" => 2),
                        9 => array("SubReportID" => 15148, "SubReportColID" => 2),
                        "comment_cnt_flgs.flag9" => array("SubReportID" => 15149, "SubReportColID" => 2),
                        "p9" => array("SubReportID" => 15150, "SubReportColID" => 2),
                        "c9" => array("SubReportID" => 15151, "SubReportColID" => 2),
                        ".+9" => array("SubReportID" => 15124, "SubReportColID" => 2),
                    ), "MainReportIDFilter" => "comment_id", "FilterNamesOnJoins" => array('comments.updated', 'comments.status', 'p', 'c', 'comment_cnt_flgs.flag')
                ),
                15102 => array("SubReportMapping" => array(1 => array("SubReportID" => 15115, "SubReportColID" => 1),
                        3 => array("SubReportID" => 15115, "SubReportColID" => 2),
                        4 => array("SubReportID" => 15133, "SubReportColID" => 2),
                        5 => array("SubReportID" => 15132, "SubReportColID" => 2),
                        6 => array("SubReportID" => 15116, "SubReportColID" => 2),
                        7 => array("SubReportID" => 15117, "SubReportColID" => 2),
                        8 => array("SubReportID" => 15118, "SubReportColID" => 2),
                        9 => array("SubReportID" => 15119, "SubReportColID" => 2),
                        10 => array("SubReportID" => 15120, "SubReportColID" => 2),
                        11 => array("SubReportID" => 15121, "SubReportColID" => 2),
                        12 => array("SubReportID" => 15133, "SubReportColID" => 3)
                    ), "MainReportIDFilter" => "user_id", "FilterNamesOnJoins" => array('users.status')
                )
            );
        }
        return $subReportMapping;
    }

    /**
     * Sets the sorting column in the filter
     *
     * @param array $filters Array of report filter parameters
     * @param integer $colID Sorting Column index
     * @param integer|null $sortOrder Position of the sorting order
     * @param integer|null $sortDirection NULL or 1 for Ascending or 2 for Descending
     * @return array Array of report filter parameters
     */
    private function setSortArgsColumn (array $filters, $colID, $sortOrder = null, $sortDirection = null) {
        if (isset($filters["sort_args"]) && is_object($filters["sort_args"])) {
            if ($filters["sort_args"]->filters->data->col_id) {
                $target = $filters["sort_args"]->filters->data;
            }
            else if ($filters["sort_args"]->filters->col_id) {
                $target = $filters["sort_args"]->filters;
            }
            if ($target) {
                $target->col_id = $colID;
                if ($sortOrder !== null) {
                    $target->sort_order = $sortOrder;
                }
                if ($sortDirection !== null) {
                    $target->sort_direction = $sortDirection;
                }
            }
        }
        else {
            $filters["sort_args"]["filters"]["col_id"] = $colID;
            if ($sortOrder !== null) {
                $filters["sort_args"]["filters"]["sort_order"] = $sortOrder;
            }
            if ($sortDirection !== null) {
                $filters["sort_args"]["filters"]["sort_direction"] = $sortDirection;
            }
        }
        return $filters;
    }

    /**
     * Reads the sorting column from the filter
     *
     * @param array $filters Array of report filter parameters
     * @return integer $colId Sorting Column index
     */
    private function getSortArgsColumn (array $filters) {
        if (isset($filters["sort_args"]) && is_object($filters["sort_args"])) {
            if ($filters["sort_args"]->filters->data->col_id) {
                return $filters["sort_args"]->filters->data->col_id;
            }
            else if ($filters["sort_args"]->filters->col_id) {
                return $filters["sort_args"]->filters->col_id;
            }
        }
        else {
            return isset($filters["sort_args"]["filters"]["col_id"]) ? $filters["sort_args"]["filters"]["col_id"] : null;
        }
    }

    /**
     * Sets the specific filter value in the filters array
     *
     * @param array $filters Array of report filter parameters
     * @param string $filterName Name of the filter
     * @param object|null $value The filter value to be set
     * @return array Array of report filter parameters
     */
    private function setFilterValue (array $filters, $filterName, $value) {
        if (is_object($filters[$filterName]) && is_object($filters[$filterName]->filters)) {
            $filters[$filterName]->filters->data = $value;
        }
        else if (array_key_exists("data", $filters[$filterName]["filters"])) {
            $filters[$filterName]["filters"]["data"] = $value;
        }
        return $filters;
    }

    /**
     * Copies the filter values of main report to sub report.
     *
     * @param array $filters Array of report filter parameters
     * @param integer $subReportID ID of the sub report
     * @param array $subReportMap Array of sub report mappings
     * @return array Array of report filter parameters
     */
    private function setSubReportFilters(array $filters, $subReportID, array $subReportMap) {
        $subReportFilters = $filters;
        if ($subReportMap["FilterNamesOnJoins"]) {
            $subReportFilters = $this->removeFilters($subReportFilters, $subReportMap["FilterNamesOnJoins"]);
            foreach ($subReportMap["FilterNamesOnJoins"] as $filterName) {
                $longFilterName = ($filterName === 'p') ? 'prod' : ($filterName === 'c' ? 'cat' : $filterName);
                $subReportFilterDefinition = $this->getFilterByName($subReportID, $longFilterName);
                if (!$subReportFilterDefinition->result) {
                    unset($subReportFilters[$filterName]);
                    continue;
                }
                if (isset($subReportFilters[$filterName]) && is_object($subReportFilters[$filterName]) && isset($subReportFilters[$filterName]->filters) && is_object($subReportFilters[$filterName]->filters)) {
                    $subReportFilters[$filterName] = clone $subReportFilters[$filterName];
                    $subReportFilters[$filterName]->filters = clone $subReportFilters[$filterName]->filters;
                    $subReportFilters[$filterName]->filters->fltr_id = $subReportFilterDefinition->result['fltr_id'] ?: $subReportFilters[$filterName]->filters->fltr_id;
                }
                else if (isset($subReportFilters[$filterName]["filters"]) && is_array($subReportFilters[$filterName]["filters"]) && array_key_exists("data", $subReportFilters[$filterName]["filters"])) {
                    $subReportFilters[$filterName]["filters"]["fltr_id"] = $subReportFilterDefinition->result['fltr_id'] ?: $subReportFilters[$filterName]["filters"]["fltr_id"];
                }
            }
        }
        return $subReportFilters;
    }

    /**
     * Removes the filter values from filter array except the excluded
     *
     * @param array $filters Array of report filters
     * @param array $excluded Array of filter names to be excluded
     * @return array Array of report filter parameters
     */
    private function removeFilters(array $filters, array $excluded = array()){
        $excluded[] = "sort_args";
        foreach ($filters as $filterName => $filter) {
            if ($this->isFilter($filters, $filterName) && !in_array($filterName, $excluded)) {
                unset($filters[$filterName]);
            }
        }
        return $filters;
    }

    /**
     * Returns True if there is a matching filter for the filter name
     *
     * @param array $filters Array of report filter parameters
     * @param string $filterName Name of the filter
     * @return boolean True if there is a matching filter for the filter name else false
     */
    private function isFilter (array $filters, $filterName) {
        if (is_object($filters[$filterName]) && is_object($filters[$filterName]->filters)) {
            return true;
        }
        else if (is_array($filters) && is_array($filters[$filterName]) && is_array($filters[$filterName]["filters"])) {
            return true;
        }
        return false;
    }

    /**
     * Reads the specific filter value from the filters array
     *
     * @param array $filters Array of report filter parameters
     * @param string $filterName Name of the filter
     * @return object|null The filter value to be set
     */
    private function getFilterValue (array $filters, $filterName) {
        $filterValue = null;
        if (isset($filters[$filterName]) && is_object($filters[$filterName]) && is_object($filters[$filterName]->filters)) {
            $filterValue = $filters[$filterName]->filters->data;
        }
        else if (isset($filters[$filterName]) && $filters[$filterName]["filters"] && is_array($filters[$filterName]["filters"]) && array_key_exists("data", $filters[$filterName]["filters"])) {
            $filterValue = $filters[$filterName]["filters"]["data"];
        }
        return is_array($filterValue) ? $filterValue[0] : $filterValue;
    }

    /**
     * Array of filter names and the respective callback functions to clean the filter values.
     * The callback function can be customized using the hook 'pre_report_filter_clean'
     *
     * @return array Array of callback functions to clean the various filters
     */
    private function getCleanFilterFunctions() {
        $cleanModerationDateFilter = function($dateFilterValue) {
            if (empty($dateFilterValue)) {
                return null;
            }
            $dateFormatObj = Text::getDateFormatFromDateOrderConfig();
            $dateFormat = $dateFormatObj["short"];
            $dateIntervals = array("day", "year", "month", "week", "hour");
            $dateIntervals = array_merge($dateIntervals, array_map(function ($interval) {
                return $interval . "s";
            }, $dateIntervals)
            );
            $dateValueParts = explode("_", $dateFilterValue);
            $interval = isset($dateValueParts[2]) ? strtolower($dateValueParts[2]) : null;
            $isHour = $interval === 'hours' || $interval === 'hour';
            if (count($dateValueParts) === 3 && $dateValueParts[0] === "last" && intval($dateValueParts[1]) && array_search($interval, $dateIntervals)) {
                $dateExpression = "-$dateValueParts[1] " . strtolower($dateValueParts[2]);
                $dateValue = $isHour ? strtotime($dateExpression) : strtotime("midnight", strtotime($dateExpression));
            }
            //$dateValue = $dateValue ? $dateValue . "|" : Text::validateDateRange($dateFilterValue, $dateFormat, "|", true);
            $dateValue = isset($dateValue) ? $dateValue : '';
            $dateValue = $dateValue ? $dateValue . "|" : Text::validateDateRange($dateFilterValue, $dateFormat, "|", true);

            $dateValue = $dateValue ?: null;
            return $dateValue;
        };

        return array("questions.updated" => $cleanModerationDateFilter,
            "comments.updated" => $cleanModerationDateFilter);
    }

    /**
     * Sanitize input data as per sanitization type specified by attribute sanitizeData
     * @param string $stringToSanitize Input string which needed to be sanitize
     * @param integer &$visibleColIndex Column number based on visible property of data in a given data set
     * @param array|null $currentField Array containing column definition and other properties for fetched report data
     * @param array|null $currentRow Array containing row values to fetch content_type for the sanitized column
     *        e.g. if sanitizeData is in format 2|16 then sanitize column 2 and
     *        fetch content_type(text/html | text/x-markdown) from column 16 of the current row
     * @return string Sanitized string
     */
    private function sanitizeData($stringToSanitize, &$visibleColIndex, array $currentField = null, array $currentRow = null) {
        $returnData = $stringToSanitize;

        if(!empty($this->appliedFormats['sanitizeData']) && ($currentField && !empty($currentField['visible'])))
        {
            // sanitize data is an object instead of an array.
            // This happens when $formats are passed through ajax call
            if(is_object($this->appliedFormats['sanitizeData']))
            {
                $this->appliedFormats['sanitizeData'] = (array)$this->appliedFormats['sanitizeData'];
            }

            foreach ($this->appliedFormats['sanitizeData'] as $sanitizeCol => $sanitizeType)
            {
                // Note: $sanitizeCol can be of type string due to object -> array casting.
                // Hence, '===' for type check is not added.
                if ($visibleColIndex == $sanitizeCol)
                {
                    if(is_numeric($sanitizeType)){
                        $sanitizeType = $currentRow[$sanitizeType - 1];
                    }
                    $returnData = \RightNow\Libraries\Formatter::formatTextEntry($stringToSanitize, $sanitizeType, false);
                }
            }
            $visibleColIndex++;
        }

        return $returnData;
    }

    /**
     * Gets the SubReportKey and filters which are used to determine the sub report to be executed
     *
     * @param integer $reportID Report ID
     * @param array $subReportMap Array of sub report mappings
     * @param array $filters Array of filters
     * @return array Array of SubReportKey and filters
     */
    private function getSubReportKeyAndFilters($reportID, array $subReportMap, array $filters){
        $subReportFilters = array();
        $subReportFilters["FilterValues"] = array();
        $subReportKey = "";
        if ($subReportMap["FilterNamesOnJoins"]) {
            foreach ($subReportMap["FilterNamesOnJoins"] as $filterName) {
                if ($subReportFilters["FilterValues"][$filterName] = $this->getFilterValue($filters, $filterName)) {
                    $subReportKey .= $filterName;
                }
            }
        }
        if (!($sortingColID = $this->getSortArgsColumn($filters))) {
            $sortingColumnDefinitions = $this->getDefaultSortDefinitions($reportID);
            $subReportFilters["defaultSortOrder"] = $sortingColumnDefinitions[0] ? $sortingColumnDefinitions[0]["sort_order"] : null;
            $subReportFilters["defaultSortDirection"] = $sortingColumnDefinitions[0] ? $sortingColumnDefinitions[0]["sort_direction"] : null;
            $sortingColID = $sortingColumnDefinitions[0] ? $sortingColumnDefinitions[0]["col_id"] : null;
        }
        $subReportFilters["sortingColID"] = $sortingColID;
        $subReportFilters["SubReportKey"] = $subReportKey . $sortingColID;
        return $subReportFilters;
    }

    /**
     * Gets the sub report definition which is array of sub report ID and the column to be used
     *
     * @param array $subReportMap Array of sub report mappings
     * @param array $subReportKeyAndFilters Array of SubReportKey and filters
     * @return array Array of sub report ID and column
     */
    private function getSubReportDefinition(array $subReportMap, array $subReportKeyAndFilters){
        if (isset($subReportMap["SubReportMapping"][$subReportKeyAndFilters["SubReportKey"]])) {
            return $subReportMap["SubReportMapping"][$subReportKeyAndFilters["SubReportKey"]];
        }
        foreach ($subReportMap["SubReportMapping"] as $subReportKey => $subReportDetails) {
            if (preg_match("/^$subReportKey$/", $subReportKeyAndFilters["SubReportKey"])) {
                return $subReportDetails;
            }
        }
    }

}
