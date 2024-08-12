<?php

namespace RightNow\Internal\Sql;

final class Polling {

    /**
     * Return an array of survey results for specified $surveyID and $surveyType.
     *
     * @param int $surveyID ID of the survey
     * @param int $surveyType Type of survey
     * @return array Survey results
     */
    public static function getResultsBySurvey($surveyID, $surveyType = null) {
        $si = sql_prepare(sprintf("SELECT s.flow_id, s.disabled, s.allow_multi_submit, s.type, s.expires, s.interface_id, w.doc_id, s.design_xml
                                   FROM surveys s
                                   JOIN flow_web_pages w ON w.flow_id = s.flow_id
                                   WHERE s.survey_id = %d
                                   AND s.type = %d AND s.interface_id = %d", $surveyID, $surveyType ?: SURVEY_TYPE_POLLING, intf_id()));

        $i = 0;
        sql_bind_col($si, ++$i, BIND_INT, 0);  // flow_id
        sql_bind_col($si, ++$i, BIND_INT, 0);  // disabled
        sql_bind_col($si, ++$i, BIND_INT, 0);  // multi_submit
        sql_bind_col($si, ++$i, BIND_INT, 0);  // type
        sql_bind_col($si, ++$i, BIND_DTTM, 0); // expires
        sql_bind_col($si, ++$i, BIND_INT, 0);  // interface_id
        sql_bind_col($si, ++$i, BIND_INT, 0);  // doc_id
        sql_bind_col($si, ++$i, BIND_MEMO, 0); // design_xml

        $results = sql_fetch($si) ?: array();
        sql_free($si);
        return $results;
    }

    /**
     * Returns results for a specific survey question
     * @param int $questionID ID of the question
     * @param int $flowID Flow type
     * @return array Results from the specified question
     */
    public static function getResultsByQuestion($questionID, $flowID) {
        $results = array();
        $si = sql_prepare(sprintf("SELECT qc.answer_text,
                                       (SELECT count(*) FROM surveys s JOIN question_sessions qs ON s.flow_id = qs.flow_id
                                        JOIN question_responses qr ON qr.q_session_id = qs.q_session_id
                                        AND qr.flow_id = s.flow_id WHERE s.flow_id = %d AND qr.choice_id = qc.choice_id
                                        AND qs.proof_mode != 1 AND qs.ac_ignore = 0) counter, q.name
                                   FROM questions q JOIN question_choices qc ON q.question_id = qc.question_id
                                   WHERE q.type = 2 AND q.question_id = %d order by qc.seq", $flowID, $questionID));

        $i = 0;
        sql_bind_col($si, ++$i, BIND_NTS, 100); // choice text
        sql_bind_col($si, ++$i, BIND_NTS, 5);   // number of responses
        sql_bind_col($si, ++$i, BIND_NTS, 100); // question name

        $total = 0;
        while($row = sql_fetch($si)) {
            list($response, $count, $questionName) = $row;
            $total += intval($count);
            $results[] = array('count' => $count, 'response' => $response);
        }

        sql_free($si);

        return array('results' => $results, 'total' => $total, 'question_name' => isset($questionName) ? $questionName : null);
    }

    /**
     * Gets survey results provided the flow ID
     * @param int $flowID Flow ID
     * @param int $questionType Question type
     * @return array Results by flow
     */
    public static function getResultsByFlow($flowID, $questionType = null) {
        $results = array();
        $si = sql_prepare(sprintf("SELECT q.question_id, q.name FROM questions q
                                   JOIN document_tags t on (q.question_id = t.tag_id)
                                   JOIN documents d on (t.id = d.doc_id)
                                   JOIN flow_web_pages fwp on (fwp.doc_id = d.doc_id)
                                   WHERE t.tag_type in (%d, %d) AND t.tbl = %d AND fwp.flow_id = %d AND q.type = %d", MA_TT_QUESTION, MA_TT_ANSWER, TBL_DOCUMENTS, $flowID, $questionType ?: QUESTION_TYPE_CHOICE));

        sql_bind_col($si, 1, BIND_INT, 0);   // question id
        sql_bind_col($si, 2, BIND_NTS, 100); // question name

        while($row = sql_fetch($si)) {
            $results[$row[0]] = $row[1];
        }

        sql_free($si);

        return $results;
    }
}

