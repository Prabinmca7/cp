<?php

namespace RightNow\Internal\Sql;

final class Friend {

    /**
     * Gets the survey ID of a flow
     *
     * @param int $flowID The flow ID
     *
     * @return int The survey's ID
     */
    public static function getSurveyID($flowID)
    {
        if (!is_numeric($flowID))
            return 0;

        $surveyID = sql_get_int("SELECT survey_id FROM surveys WHERE flow_id = $flowID");
        return $surveyID;
    }

    /**
     * Gets the html/xml of a document
     *
     * @param int $docID The document's ID
     *
     * @return string The document
     */
    public static function getDocumentHtmlXml($docID)
    {
        $si = sql_prepare("SELECT live_html_xml FROM documents WHERE doc_id = $docID");
        sql_bind_col($si, 1, BIND_MEMO, 0);
        $row = sql_fetch($si);
        $htmlXml = $row[0];
        sql_free($si);
        return $htmlXml;
    }

    /**
     * Gets transaction data from ma_trans for the given tracking object
     *
     * @param trackingObject $track The tracking object
     *
     * @return array An array containing the transaction data
     */
    public static function getTransData($track)
    {
        $si = sql_prepare((sprintf(
            "SELECT trans.type, count(*)
            FROM ma_trans trans, mailing_formats f
            WHERE trans.mailing_id = f.mailing_id AND
                  f.format_id = {$track->format_id} AND
                  trans.c_id = {$track->c_id} AND
                  trans.type IN (%d, %d, %d, %d, %d, %d)
            GROUP BY trans.type ",
            MA_TRANS_CLICK_LINK, MA_TRANS_CLICK_FRIEND,
            MA_TRANS_CLICK_WP_LINK, MA_TRANS_CLICK_UNSUB,
            MA_TRANS_CLICK_FATTACH, MA_TRANS_EMAIL_VIEW)));

        sql_bind_col($si, 1, BIND_INT, 0);
        sql_bind_col($si, 2, BIND_INT, 0);

        $isExistingClick = 0;
        $isExistingView = 1;
        while ((list($transType, $transCount) = sql_fetch($si)) > 0)
        {
            if ($transType === MA_TRANS_EMAIL_VIEW)
            {
                $isExistingView = $transCount;
            }
            else
            {
                $isExistingClick += $transCount;

                if ($transType === MA_TRANS_CLICK_FRIEND)
                {
                    $friendCount = $transCount;
                }
            }
        }

        sql_free($si);

        return array("is_exsisting_click" => $isExistingClick, "is_exsisting_view" => $isExistingView, "friend_count" => $friendCount);
    }

    /**
     * Gets the flow_web_page_id from the database
     *
     * @param string $shortcut The shortcut to the web page
     *
     * @return int The flow_web_page_id
     */
    public static function getFlowWebPageIDFromShortcut($shortcut)
    {
        $CI = get_instance();
        $shortcut = strtr($shortcut, $CI->rnow->getSqlEscapeCharacters());
        return sql_get_int("SELECT flow_web_page_id FROM flow_web_pages WHERE shortcut = '{$shortcut}'");
    }

    /**
     * Gets the doc_id from the database
     *
     * @param string $shortcut The shortcut to the web page
     *
     * @return int The doc_id
     */
    public static function getDocIDFromShortcut($shortcut)
    {
        $CI = get_instance();
        $shortcut = strtr($shortcut, $CI->rnow->getSqlEscapeCharacters());
        return sql_get_int("SELECT doc_id FROM flow_web_pages WHERE shortcut = '{$shortcut}'");
    }

    /**
     * Gets the subject for the given mailing_format
     *
     * @param int $formatID The ID of the mailing_format
     *
     * @return string The subject
     */
    public static function getSubjectFromMailingFormatID($formatID)
    {
        return sql_get_str("SELECT subject_xml FROM mailing_formats WHERE format_id = $formatID AND subject_trans <> 1", 256);
    }
}
