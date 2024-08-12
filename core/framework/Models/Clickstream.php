<?php

namespace RightNow\Models;

use RightNow\Internal\Sql\Clickstream as Sql,
    RightNow\Api,
    RightNow\Connect\v1_4 as Connect;

/**
 * Methods for inserting entries in the clickstream table
 */
class Clickstream extends Base
{
    /**
     * Invalid query placeholder string
     *
     * @internal
     */
    const DQA_INVALID_QUERY = "<invalid_query>";

    private static $clickstreamEnabled;
    private $dataLogger;
    private $productionMode = IS_PRODUCTION;

    function __construct() {
        parent::__construct();

        $this->dataLogger = (func_num_args() === 1) ? func_get_arg(0) : '\RightNow\Api';
    }

    /**
     *Inserts click activity into clickstream table
     * @param string $sessionID User's session ID
     * @param int $contactID Contact's ID
     * @param int $app Application source
     *       CS_APP_EU (1)  : EndUser
     *       CS_APP_CONS(2) : Console
     *       CS_APP_UTIL(5) : Utils
     *       CS_APP_PUB_API(6) : API
     *
     * @param string $action Activity string (click action)
     * @param string $contextOne Context (parameter) 1 for this entry
     * @param string $contextTwo Context (parameter) 2 for this entry
     * @param string $contextThree Context (parameter) 3 for this entry
     * @throws \Exception If an invalid parameter is found when in development or staging environment
     * @return void
     */
    public function insertAction($sessionID, $contactID, $app, $action, $contextOne, $contextTwo, $contextThree)
    {
        $referrer = $this->CI->agent->referrer();
        $referrer = $referrer ? Api::utf8_trunc_nchars($referrer, Sql::DQA_REFERRER_SIZE) : null;

        // Do some rudimentary edits on commonly misset parameters if in development or staging to prevent bad code
        // from going into production. For now production will continue to pass bad data to DQA to avoid a bad
        // customer experience.  (QA 140521-000082)
        if (!$this->productionMode) {
            if (!$sessionID || strlen($sessionID) > 8) {
                throw new \Exception("Invalid session ID: " .  var_export($sessionID, true));
            }
            if ($contactID && (!is_int($contactID) || ($contactID < 0 && $contactID !== INT_NOT_SET && $contactID !== INT_NULL))) {
                throw new \Exception("Invalid contact ID: " . var_export($contactID, true));
            }
            if ($app && (!is_int($app) || ($app < 0 && $app !== INT_NOT_SET && $app !== INT_NULL) || $app > INT8_MAX)) {
                throw new \Exception("Invalid app ID: " . var_export($app, true));
            }
        }

        $this->insertDqaAction(DQA_CLICKSTREAM, array(
            'cid'      => $contactID ?: null,
            'sid'      => $sessionID,
            'app'      => $app,
            'ts'       => time(),
            'psid'     => $this->CI->getPageSetID() ?: null,
            'referrer' => $referrer,
            'action'   => Api::utf8_trunc_nchars($action, Sql::DQA_CONTEXT_FIELD_SIZE),
            'c1'       => $contextOne ? Api::utf8_trunc_nchars($contextOne, Sql::DQA_CONTEXT_FIELD_SIZE) : null,
            'c2'       => $contextTwo ? Api::utf8_trunc_nchars($contextTwo, Sql::DQA_CONTEXT_FIELD_SIZE) : null,
            'c3'       => $contextThree ? Api::utf8_trunc_nchars($contextThree, Sql::DQA_CONTEXT_FIELD_SIZE) : null,
        ));
    }

    /**
     * Updates answer solved count for given ID
     * @param int $answerID Answer ID
     * @param int $rating Value to update the solved count. Negative values are meant to be decrement
     * @return void
     */
    public function insertSolvedCount($answerID, $rating)
    {
        $this->insertDqaAction(DQA_SOLVED_COUNT, array(
            'a_id'        => (int) $answerID,
            'rating'      => $rating,
            'last_access' => time(),
        ));
    }

    /**
     * Creates a link in links table
     * @param int $from Answer ID
     * @param int $to Answer ID
     * @return void
     */
    public function insertLink($from, $to)
    {
        if (!(is_numeric($from) && is_numeric($to) && ($from > 0) && ($to > 0)))
            return;

        $this->insertDqaAction(DQA_LINKS, array(
            'from'        => (int) $from,
            'to'          => (int) $to,
            'access_time' => time(),
        ));
    }

    /**
     * Insert data into DQA
     * @param int $type Stat type
     *  DQA_CLICKSTREAM(1) Clickstream action
     *  DQA_SOLVED_COUNT(2) Solved Count action
     *  DQA_LINKS (3) Links action
     *  DQA_KEYWORD_SEARCHES(8) keyword searches
     *  DQA_WIDGET_STATS(9) Widget Stats
     *  DQA_POLLING_STATS(18) Polling Widget Stats
     * @param object $action DQA data object
     * @return void
     */
    public function insertQuery($type, $action)
    {
        $this->insertDqaActionInProduction($type, $action);
    }


    /**
     * Updates stat values in stats table
     * @param int $type Stat type
     * @param int $action Stat value
     * @return void
     */
    public function insertWidgetStats($type, $action)
    {
        $this->insertDqaActionInProduction($type, $action);
    }

    /**
     * Inserts a transaction into the message_trans table
     * @param string $trackingString The encoded mail tracking string that includes thread_id, email_type, doc_id, c_id
     * @param string $page The page the link went to
     * @param string $sessionID The cs_session_id associated to this session
     * @param int $answerID The AnswerID for the this mail transaction
     * @return void
     */
    public function insertMailTransaction($trackingString, $page, $sessionID, $answerID)
    {
        $trackingObject = Api::generic_track_decode($trackingString);

        if (($trackingObject->flags & GENERIC_TRACK_FLAG_PREVIEW) || ($trackingObject->thread_id <= 0) || ($trackingObject->c_id <= 0) || ($trackingObject->doc_id <= 0))
            return;

        switch ($page)
        {
            case \RightNow\Utils\Config::getConfig(CP_ANSWERS_DETAIL_URL):
                $transType = MA_TRANS_CLICK_ANSWER;
                break;
            case \RightNow\Utils\Config::getConfig(CP_INCIDENT_RESPONSE_URL):
                $transType = MA_TRANS_CLICK_INCIDENT;
                break;
            case \RightNow\Utils\Config::getConfig(CP_LOGIN_URL):
            case \RightNow\Utils\Config::getConfig(CP_ACCOUNT_ASSIST_URL):
                $transType = MA_TRANS_CLICK_PROFILE;
                break;
            default:
                $transType = MA_TRANS_CLICK_CP;
                break;
        }

        $keyValues = array(
            'c_id'          => $trackingObject->c_id,
            'email_type'    => $trackingObject->email_type,
            'doc_id'        => $trackingObject->doc_id,
            'trans_type'    => $transType,
            'created'       => time(),
        );

        if ($trackingObject->thread_id > 0)
            $keyValues['thread_id'] = $trackingObject->thread_id;

        if ($answerID > 0)
            $keyValues['a_id'] = $answerID;

        if (!empty($sessionID) )
            $keyValues['cs_session_id'] = (string) $sessionID;

        $this->insertDqaAction(DQA_MESSAGE_TRANS, $keyValues);
    }

    /**
     * Inserts spider information into spider_track table
     * @param string $sessionID Session ID
     * @param string $ip IP address
     * @param string $userAgent User agent
     * @param string $page Page
     * @param int $pageType Page type
     *       1: Content Page
     *       2: List page
     *       3: Other Page
     * @param int $spiderType Spider type, check_spider function returns this value
     * @return void
     */
    public function insertSpider($sessionID, $ip, $userAgent, $page, $pageType, $spiderType)
    {
        $this->insertDqaAction(DQA_SPIDER, array(
            'sid'         => $sessionID,
            'ip'          => $ip,
            // Some spiders do not provide a user agent.
            'ua'          => Api::utf8_trunc_nchars(trim($userAgent) ?: 'Empty', Sql::DQA_USER_AGENT_FIELD_SIZE),
            'page'        => Api::utf8_trunc_nchars($page, Sql::DQA_CONTEXT_FIELD_SIZE),
            'page_type'   => $pageType,
            'spider_type' => $spiderType,
            'ts'          => time(),
        ));
    }

    /**
     * Inserts keyword searches into keyword_searches table
     * @param array $stats Stats object.
     * @return void
     */
    public function insertResultList(array $stats)
    {
        if (!$stats || !self::$clickstreamEnabled) return;

        $answerList = $stats['list'];
        if (is_array($answerList) && count($answerList))
        {
            $list = implode(',', array_unique($answerList));
            $list = trim($list);
            $list = $this->truncateList($list, Sql::DQA_CONTEXT_FIELD_SIZE);
        }
        else
        {
            $list = null;
        }

        $query = $stats['term'];
        $stem = '';
        if(!empty($query))
        {
            $result = Api::rnkl_stem($query, 0);
            $stem = trim($result['stem']);
            $wordCount = $result['word_count'];
            if($wordCount === 0 || strlen($stem) === 0)
                $stem = self::DQA_INVALID_QUERY;
        }

        if((isset($stem) && strlen($stem) > 0) || (isset($list) && strlen($list) > 0))
        {
            $this->insertAction(
                $this->CI->session->getSessionData('sessionID'),
                $this->CI->session->getProfileData('contactID'),
                (\RightNow\Utils\Framework::validateCustomerPortalAgent() === true) ? CS_APP_AGENT_EU : CS_APP_EU,
                '/' . \RightNow\Hooks\ClickstreamActionMapping::getAction(isset($stats['sa']) && $stats['sa'] ? 'SAResultList' : 'ResultList'),
                Api::utf8_trunc_nchars($stem, Sql::DQA_CONTEXT_FIELD_SIZE),
                $list,
                $stats['page']
            );
        }
    }

    /**
     * Enables the use of clickstreams.
     * @param bool $clickstreamEnabled Whether to enable or disable clickstreams
     * @return void
     * @throws \Exception If attempting to reset clickstream enabled to something
     *      other than what was originally intended.
     * @internal
     */
    public function setClickstreamEnabled($clickstreamEnabled)
    {
        if (isset(self::$clickstreamEnabled) && self::$clickstreamEnabled !== $clickstreamEnabled) {
            throw new \Exception("Attempt to reset the value of clickstreamEnabled in Models/Clickstream::setClickstreamEnabled()");
        }
        self::$clickstreamEnabled = $clickstreamEnabled;
    }

    /**
     * Inserts keyword search stats
     * @param array $stats Stats object
     * @return void
     */
    public function insertKeywords(array $stats) {
        if (!$stats['term'])
            return;

        $answerList = $stats['list'];
        if (is_array($answerList) && count($answerList)) {
            $list = implode(',', array_unique($answerList));
            $list = trim($list);
            if (strlen($list) !== 0)
                $list .= ','; //API require that the last char is ,. This needs to be changed
            $list = $this->truncateList($list, 255);
        }
        else {
            $list = null;
        }

        $this->insertKeywordSearch($stats['term'], $stats['total'], $list, $stats['source']);
    }

    /**
     * Inserts keyword searches into keyword_searches table
     * @param string $query Search query
     * @param int $results Number of results
     * @param string $resultList Comma seperated list of answer ids
     * @param int $source Source application invoking the search
     *       SRCH_END_USER(1): End user
     *       SRCH_EXT_DOC(2): External Document Searches
     *       SRCH_ANS_INC_CONS(3): Answer Searches in Incident Console
     *       SRCH_BROWSE(4): Search in Browse Page
     * @param int $incident Flag that indicate if it is answers or incidents
     * @return void
     */
    public function insertKeywordSearch($query, $results, $resultList, $source, $incident = 0)
    {
        $query = trim($query);
        $result = Api::rnkl_stem($query, $incident);
        if ($result == false) {
            return;
        }

        $stem = trim($result["stem"]);
        $wordCount = $result["word_count"];
        if (!$stem || !$wordCount){
            if (strlen($query) > 0) {
                 //must be an invalid query
                $stem = self::DQA_INVALID_QUERY;
            }
            else {
                return;
            }
        }

        $this->insertDqaAction(DQA_KEYWORD_SEARCHES, array(
            'query'       => Api::utf8_trunc_nchars($query, Sql::DQA_CONTEXT_FIELD_SIZE),
            'stem'        => Api::utf8_trunc_nchars($stem, Sql::DQA_CONTEXT_FIELD_SIZE),
            'word_count'  => $wordCount,
            'results'     => $results,
            'result_list' => $resultList,
            'source'      => $source,
            'ts'          => time(),
        ));
    }

    /**
     * Returns proper app for a marketing or feedback page
     * NOTE: this was created to ensure we are properly differentiating between Marketing and Feedback sessions
     *
     * @param string $shortcut Name of the page that we're checking
     * @param int $survey ID of survey
     * @param string $hiddenShortcut Name of the page that we're checking, but provided as part of the form content
     * @return int Which type of survey we're doing
     */
    public function getMaAppType($shortcut, $survey, $hiddenShortcut){
        $shortcutToUse = strlen($shortcut) > 0 ? $shortcut : $hiddenShortcut;
        $app = CS_APP_MA;
        if ($survey > 0)
        {
            $app = CS_APP_FB;
        }
        else if (strlen($shortcutToUse) > 0)
        {
            $typeID = Sql::getFlowType($shortcutToUse);

            $app = ($typeID === FLOW_SURVEY_TYPE)
                ? CS_APP_FB
                : CS_APP_MA;
        }

        return $this->getResponseObject($app, 'is_int');
    }

    /**
     * Truncates a list delimited by a pattern. It truncates at the delimiter.
     * @param string $list List of item
     * @param int $size Max size of the list
     * @param string $delimiter Delimiter used in the list
     * @return string Truncated string
     */
    private function truncateList($list, $size, $delimiter=",") {
        $listSize = strlen($list);
        if ($listSize > $size) {
            $lastPosition = strrpos($list, $delimiter, ($size - 1) - $listSize);
            if ($lastPosition === false) // Maybe list is misconstructed
                $list = substr($list, 0, $size);
            else
                $list = substr($list, 0, $lastPosition + 1);
        }
        return $list;
    }

    /**
     * Calls dqa_insert on the dataLogger member.
     * @param string $action Name of the action
     * @param array|object $description Values for the action
     * @return void
     */
    private function insertDqaAction($action, $description) {
        $sendTo = $this->dataLogger;
        $sendTo::dqa_insert($action, $description);
    }

    /**
     * Inserts the action object for the specified
     * action type into DQA if the current site mode
     * is production.
     * @param string $type Type of action
     * @param object $action Info about the action
     * @return void
     */
    private function insertDqaActionInProduction($type, $action) {
        if ($this->productionMode) {
            $action->ts = time();
            $this->insertDqaAction($type, $action);
        }
    }
}
