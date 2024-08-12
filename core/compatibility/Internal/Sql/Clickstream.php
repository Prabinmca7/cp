<?php

namespace RightNow\Internal\Sql;

use RightNow\Utils\Framework;

// Starting in 13.5, this file is part of optimized_includes.php. The related models in 3.1 will no longer try
// to include this file directly, but models in 3.0 will. Since the compatibility files are not versioned, prevent
// this class from being re-declared.
if (!class_exists('\RightNow\Internal\Sql\Clickstream')) {
    final class Clickstream
    {
        const DQA_CONTEXT_FIELD_SIZE = 254;
        const DQA_USER_AGENT_FIELD_SIZE = 254;
        const DQA_REFERRER_SIZE = 1024;

        /**
         * Returns actions for the provided session ID
         * @param string $sessionID Session ID to check
         * @return array Actions for the provided session ID
         */
        public static function getActions($sessionID)
        {
            $results = array();
            $sql = sprintf("SELECT a.action FROM clickstreams c, cs_actions a WHERE c.action_id = a.action_id AND c.cs_session_id='%s'", Framework::escapeForSql($sessionID));
            $si = sql_prepare($sql);
            sql_bind_col($si, 1, BIND_NTS, 255);
            while($row = sql_fetch($si)){
                $results[] = $row[0];
            }
            sql_free($si);
            return $results;
        }

        /**
         * Returns the flow type provided the shortcut string
         * @param string $shortcut Shortcut
         * @return int Flow type
         */
        public static function getFlowType($shortcut)
        {
            $sql = sprintf("SELECT f.type FROM flows f, flow_web_pages fwp WHERE f.flow_id = fwp.flow_id AND shortcut='%s'", Framework::escapeForSql($shortcut));
            return sql_get_int($sql);
        }

        /**
         * Returns proper documentId
         * NOTE: Regardless of if this is a survey, campaign, or view in browser there will be an associated documentId
         *
         * @param array|null $params Survey parameters
         * @return int Document ID
         */
        public static function getDocID($params)
        {
            if(isset($params[MA_QS_ITEM_PARM]))
            {
                return sql_get_int(sprintf("SELECT doc_id FROM flow_web_pages WHERE shortcut = '%s'", Framework::escapeForSql($params[MA_QS_ITEM_PARM])));
            }

            if(isset($params[MA_QS_SURVEY_PARM]))
            {
                return sql_get_int(sprintf('SELECT fwp.doc_id FROM flow_web_pages fwp, flows f, surveys s WHERE fwp.flow_id = f.flow_id AND f.flow_id = s.flow_id AND s.flow_id = fwp.flow_id
                                            AND s.survey_id = %d AND fwp.first_page = 1', $params[MA_QS_SURVEY_PARM]));
            }
        }
    // @codingStandardsIgnoreStart
    }
    // @codingStandardsIgnoreEnd
}