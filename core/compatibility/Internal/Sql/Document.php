<?php

namespace RightNow\Internal\Sql;

final class Document {
    /**
     * Runs SQL to get flow information from the DB
     *
     * @param int $surveyID The ID of the survey
     * @param bool $resumeSurvey True if the user is resuming a survey
     * @param bool $surveyNavigation True if we are in survey navigation mode
     * @param string $formShortcut The shortcut to this page
     * @param int $flowID The ID of the flow
     * @param trackingObject $track The tracking object
     *
     * @return An array of flow information
     */
    public static function getFlowInfoFromDatabase($surveyID, $resumeSurvey, $surveyNavigation, $formShortcut, $flowID, $track)
    {
        $CI = get_instance();
        $docID = $wpID = $noWebFormShortcut = 0;
        $secCookie = $secTracking = $secLogin = $secForcePw = false;

        // if the survey_id param is set then we need to find the "first page" for the survey.  This is a flag in the flow_web_pages table
        if($surveyID > 0 && $resumeSurvey !== true && !$surveyNavigation)
        {
            $si = sql_prepare(sprintf('SELECT w.flow_id, w.doc_id, w.flow_web_page_id,
                               w.sec_cookie, w.sec_tracking, w.sec_login,
                               w.sec_login_required, w.shortcut
                               FROM flow_web_pages w
                               WHERE w.flow_id = %d and w.first_page = 1', $flowID));
        }
        else
        {
            $si = sql_prepare(sprintf("SELECT w.flow_id, w.doc_id, w.flow_web_page_id, w.sec_cookie, w.sec_tracking, w.sec_login, w.sec_login_required FROM flow_web_pages w, documents d WHERE d.doc_id = w.doc_id AND w.shortcut = '%s' AND d.interface_id = %d", strtr($formShortcut, $CI->rnow->getSqlEscapeCharacters()), intf_id()));
        }

        sql_bind_col($si, 1, BIND_INT, 0); define('SQL_FLOW_ID', 0);
        sql_bind_col($si, 2, BIND_INT, 0); define('SQL_DOC_ID', 1);
        sql_bind_col($si, 3, BIND_INT, 0); define('SQL_WP_ID', 2);
        sql_bind_col($si, 4, BIND_INT, 0); define('SQL_SEC_COOKIE', 3);
        sql_bind_col($si, 5, BIND_INT, 0); define('SQL_SEC_TRACKING', 4);
        sql_bind_col($si, 6, BIND_INT, 0); define('SQL_SEC_LOGIN', 5);
        sql_bind_col($si, 7, BIND_INT, 0); define('SQL_SEC_FORCE_PW', 6);
        if($surveyID > 0 && $resumeSurvey != true && !$surveyNavigation)
            sql_bind_col($si, 8, BIND_NTS, 80); define('SQL_SHORTCUT', 7);

        if ($row = sql_fetch($si))
        {
            $flowID = $row[SQL_FLOW_ID];
            $docID = $row[SQL_DOC_ID];
            $wpID = $row[SQL_WP_ID];
            $secCookie = $row[SQL_SEC_COOKIE];
            $secTracking = $row[SQL_SEC_TRACKING];
            $secLogin = $row[SQL_SEC_LOGIN];
            $secForcePw = $row[SQL_SEC_FORCE_PW];
            $noWebFormShortcut = 0;

            if($surveyID > 0 && $resumeSurvey != true && !$surveyNavigation)
                $formShortcut = $row[SQL_SHORTCUT];
        }
        else
        {
            $noWebFormShortcut = 1;
            if ($track !== null) {
                $flowID = $track->flow_id;
                $docID = $track->doc_id;
            }
        }
        sql_free($si);
        return array($flowID, $docID, $noWebFormShortcut, $wpID, $secCookie, $secTracking, $secLogin, $secForcePw, $formShortcut);
    }

    /**
     * Runs SQL to get the status and type for the given flow ID
     *
     * @param int $flowID The ID of the flow
     *
     * @return array containing the flow's status and type
     */
    public static function getStatusAndTypeForFlow($flowID)
    {
        $si = sql_prepare("SELECT status_id,type FROM flows where flow_id = $flowID");
        sql_bind_col($si, 1, BIND_INT, 0);
        sql_bind_col($si, 2, BIND_INT, 0);
        if ($row = sql_fetch($si))
        {
            $flowStatus = $row[0];
            $flowType = $row[1];
        }
        sql_free($si);
        return array($flowStatus, $flowType);
    }

    /**
     * Runs SQL to get data required for the checkForPriorSubmission function
     *
     * @param string $identifier A string
     * @param int $sourceID The source ID
     * @param int $flowID The flow ID
     * @param array &$surveyData The survey data, q_session_id will be populated
     *
     * @return bool True if the survey has been previously submitted
     */
    public static function checkForPriorSubmissionSql($identifier, $sourceID, $flowID, &$surveyData)
    {
        $retVal = false;

        $si = sql_prepare(sprintf('SELECT q_session_id, completed FROM question_sessions WHERE %s = %d AND flow_id = %d AND proof_mode != 1 AND completed IS NOT NULL', $identifier, $sourceID, $flowID));
        sql_bind_col($si, 1, BIND_INT, 0); define('S_QS_ID', 0);
        sql_bind_col($si, 2, BIND_DTTM, 1); define('S_COMPLETED_DATE', 1);

        if ($qsrow = sql_fetch($si))
        {
            $surveyData['q_session_id'] = $qsrow[S_QS_ID];
            if ($qsrow[S_COMPLETED_DATE])
                $retVal = true;
        }
        sql_free($si);
        return $retVal;
    }

    /**
     * Runs SQL to get date where the auth parameter became required for this site
     *
     * @return The  date where the auth parameter became required for this site
     */
    public static function getUpgradeToAuthRequiredDate()
    {
        return sql_get_dttm("SELECT end_time FROM upgrade_invocations WHERE catalog_id > 12050103 AND state = 2 ORDER BY catalog_id LIMIT 1");
    }

    /**
     * Runs SQL to get the survey's data on allow_anonymous and disabled
     *
     * @param string $formShortcut The shortcut of the flow web page
     *
     * @return array An array containing the values for allowAnonymous and surveyDisabled
     */
    public static function getAllowAnonymousAndDisabled($formShortcut)
    {
        $CI = get_instance();
        $si = sql_prepare(sprintf("SELECT allow_anonymous, disabled
                               FROM surveys s, flow_web_pages fw
                               WHERE fw.shortcut='%s' AND fw.flow_id = s.flow_id
                               AND s.interface_id = %d", strtr($formShortcut, $CI->rnow->getSqlEscapeCharacters()), intf_id()));

        sql_bind_col($si, 1, BIND_INT, 0); define('SURVEY_ALLOW_ANONYMOUS', 0);
        sql_bind_col($si, 2, BIND_INT, 0); define('SURVEY_DISABLED', 1);

        if ($srow = sql_fetch($si))
        {
            $allowAnonymous = $srow[SURVEY_ALLOW_ANONYMOUS];
            $surveyDisabled = $srow[SURVEY_DISABLED];
        }
        sql_free($si);
        return array($allowAnonymous, $surveyDisabled);
    }

    /**
     * Runs SQL to get data about the question session
     *
     * @param int $flowID The ID of the flow
     * @param int $remQsID The question_session_id
     * @param string $identifier An identifier to be used in the query
     * @param int $sourceID The ID of the source object
     *
     * @return array An array containing data about the question session
     */
    public static function getQuestionSessionData($flowID, $remQsID, $identifier, $sourceID)
    {
        if (strlen($identifier) > 0)
        {
            $si = sql_prepare(sprintf('SELECT qs.score, qs.i_id, qs.op_id, qs.chat_id, qs.doc_id, qs.c_id, s.survey_id, s.question_nums FROM question_sessions qs, surveys s WHERE qs.flow_id = s.flow_id AND qs.flow_id = %d AND qs.q_session_id = %d AND qs.completed is NULL AND qs.%s = %d', $flowID, $remQsID, $identifier, $sourceID));
        }
        else
        {
            $si = sql_prepare(sprintf('SELECT qs.score, qs.i_id, qs.op_id, qs.chat_id, qs.doc_id, qs.c_id, s.survey_id, s.question_nums FROM question_sessions qs, surveys s WHERE qs.flow_id = s.flow_id AND qs.flow_id = %d AND qs.q_session_id = %d AND qs.completed is NULL', $flowID, $remQsID));
        }

        sql_bind_col($si, 1, BIND_INT, 0); define('SCORE', 0);
        sql_bind_col($si, 2, BIND_INT, 0); define('I_ID', 1);
        sql_bind_col($si, 3, BIND_INT, 0); define('OP_ID', 2);
        sql_bind_col($si, 4, BIND_INT, 0); define('CHAT_ID', 3);
        sql_bind_col($si, 5, BIND_INT, 0); define('DOC_ID', 4);
        sql_bind_col($si, 6, BIND_INT, 0); define('C_ID', 5);
        sql_bind_col($si, 7, BIND_INT, 0); define('SURVEY_ID', 6);
        sql_bind_col($si, 8, BIND_INT, 0); define('QUEST_NUMS', 7);

        if ($srow = sql_fetch($si))
        {
            $sScore     = $srow[SCORE];
            $sIID       = $srow[I_ID];
            $sOpID      = $srow[OP_ID];
            $sChatID    = $srow[CHAT_ID];
            $sDocID     = $srow[DOC_ID];
            $sCID       = $srow[C_ID];
            $sSurveyID  = $srow[SURVEY_ID];
            $sQuestNums = $srow[QUEST_NUMS];
        }

        sql_free($si);
        return array($sScore, $sIID, $sOpID, $sChatID, $sDocID, $sCID, $sSurveyID, $sQuestNums, $srow);
    }

    /**
     * Queries the database for information about the flow_web_page
     *
     * @param string $shortcut The flow_web_page's shortcut
     * @param array &$data Output parameter that is populated after the query
     */
    public static function getSwpData($shortcut, &$data)
    {
        $CI = get_instance();
        $si = sql_prepare(sprintf("SELECT w.flow_id, ms.state_id, w.set_cookie, w.doc_id, w.flow_web_page_id, w.sec_login FROM flow_web_pages w, flow_map2state ms, documents d WHERE w.shortcut = '%s' AND d.interface_id = %d AND w.flow_web_page_id = ms.flow_web_page_id AND d.doc_id = w.doc_id", strtr($shortcut, $CI->rnow->getSqlEscapeCharacters()), intf_id()));

        sql_bind_col($si, 1, BIND_INT, 0);
        sql_bind_col($si, 2, BIND_INT, 0);
        sql_bind_col($si, 3, BIND_INT, 0);
        sql_bind_col($si, 4, BIND_INT, 0);
        sql_bind_col($si, 5, BIND_INT, 0);
        sql_bind_col($si, 6, BIND_INT, 0);

        if ($row = sql_fetch($si))
        {
            $data['flowID']     = $row[0];
            $data['stateID']    = $row[1];
            $data['setCookie']  = $row[2];
            $data['docID']      = $row[3];
            $data['wpID']       = $row[4];
            $data['secLogin']   = $row[5];
        }
        sql_free($si);
    }

    /**
     * Queries the database for information about the flow
     *
     * @param array &$data Output parameter that is populated after the query, the flowID is expected to be populated
     *
     * @return bool True if the flow is active
     */
    public static function isActiveFlow(&$data)
    {
        $si = sql_prepare(sprintf('SELECT status_id,type FROM flows where flow_id = %d', $data['flowID']));

        sql_bind_col($si, 1, BIND_INT, 0);
        sql_bind_col($si, 2, BIND_INT, 0);

        if ($row = sql_fetch($si))
        {
            $data['flowStatus']  = $row[0];
            $data['flowType']    = $row[1];
        }
        sql_free($si);

        return ($data['flowStatus'] == FLOW_STATUS_LAUNCHED);
    }

    /**
     * Gets the flow_id for the given mailing format ID
     *
     * @param int $formatID The mailing format Id
     *
     * @return int The flow_id
     */
    public static function getFlowIDForMailing($formatID)
    {
        return sql_get_int("SELECT m.flow_id FROM mailings m, mailing_formats f WHERE f.format_id = $formatID AND f.mailing_id = m.mailing_id");
    }

    /**
     * Queries the database for information about the flow
     *
     * @param array &$data Output parameter that is populated after the query, the flowID is expected to be populated
     */
    public static function setUpFlowData(&$data)
    {
        $data['flowType'] = sql_get_int("SELECT type FROM flows WHERE flow_id = {$data['flowID']}");

        if ($data['flowType'] == FLOW_CAMPAIGN_TYPE)
            $data['campaignID'] = sql_get_int("SELECT campaign_id FROM campaigns WHERE flow_id = {$data['flowID']}");
        else if ($data['flowType'] == FLOW_SURVEY_TYPE)
            $data['surveyID'] = sql_get_int("SELECT survey_id FROM surveys WHERE flow_id = {$data['flowID']}");
    }

    /**
     * Queries the database for information about the flow's state_id
     *
     * @param array $params A bunch of parameters
     * @param int $formatID The format_id
     * @param array &$data Output parameter that is populated after the query, the flowID is expected to be populated
     */
    public static function setUpStateID($params, $formatID, &$data)
    {
        // Event Mailings include a node_id. Look for it.
        if(isset($params['p_node']) && $params['p_node'] > 0)
            $data['stateID'] = sql_get_int("SELECT state_id FROM flow_map2state WHERE flow_id = {$data['flowID']} AND node_id = {$params['p_node']}");
        else if ($formatID > 0)
            $data['stateID'] = sql_get_int("SELECT state_id FROM flow_map2state ns, mailing_formats f WHERE f.format_id = $formatID AND f.mailing_id = ns.mailing_id AND ns.flow_id = {$data['flowID']}");
    }

    /**
     * Queries the database for surveys.type and surveys.allow_multi_submit
     *
     * @param int $surveyID The survey ID
     *
     * @return array containing values for surveys.type and surveys.allow_multi_submit
     */
    public static function getTypeMultiSubmitForSurvey($surveyID)
    {
        $si = sql_prepare(sprintf('SELECT type, allow_multi_submit FROM surveys where survey_id = %d', $surveyID));

        sql_bind_col($si, 1, BIND_INT, 0);
        sql_bind_col($si, 2, BIND_INT, 0);

        if ($row = sql_fetch($si))
        {
            $surveyType  = $row[0];
            $surveyMulti = $row[1];
        }
        sql_free($si);
        return array($surveyType, $surveyMulti);
    }

    /**
     * Queries the database to check whether a document id is valid or not
     *
     * @param int $docId Document id
     *
     * @return bool True if document id is valid/exists
     */
    public static function isValidDocId($docId)
    {
        if($docId == 0 || !is_numeric($docId)) {
            return false;
        }
        return (bool) sql_get_int("SELECT EXISTS(SELECT 1 FROM documents WHERE doc_id = $docId)");
    }
}
