<?php
namespace RightNow\Internal\Sql;

/**
* Provides functions for retrieving File attachment info out of
* the fattach database table.
*/
final class FileAttachment {
    /**
     * Retrieves the specified file attachment.
     * @param int $id File attachment's ID
     * @param string $created Created timestamp
     * @return array Info about the file attachment
     */
    public static function get($id, $created = null) {
        $sql = sprintf("SELECT localfname, userfname, content_type, tbl, sz, id, type, created, file_id FROM fattach WHERE file_id = %d AND (private = 0 OR private IS NULL)", $id);
        if ($created && (is_int($created) || ctype_digit($created))) {
            $sql .= sprintf(' AND created = %s', time2db($created));
        }

        $si = sql_prepare($sql);
        $i = 0;
        sql_bind_col($si, ++$i, BIND_NTS, 197); // localfname
        sql_bind_col($si, ++$i, BIND_NTS, 41);  // userfname
        sql_bind_col($si, ++$i, BIND_NTS, 61);  // content_type
        sql_bind_col($si, ++$i, BIND_INT, 0);   // tbl
        sql_bind_col($si, ++$i, BIND_INT, 0);   // sz
        sql_bind_col($si, ++$i, BIND_INT, 0);   // id
        sql_bind_col($si, ++$i, BIND_INT, 0);   // type
        sql_bind_col($si, ++$i, BIND_DTTM, 0);  // created
        sql_bind_col($si, ++$i, BIND_INT, 0);   // file_id

        $row = sql_fetch($si);
        sql_free($si);

        if (!$row) return false;

        return array(
            'fileID'        => $row[--$i],
            'created'       => $row[--$i],
            'type'          => $row[--$i],
            'id'            => $row[--$i],
            'size'          => $row[--$i],
            'table'         => $row[--$i],
            'contentType'   => $row[--$i],
            'userFileName'  => $row[--$i],
            'localFileName' => $row[--$i],
        );
    }

    /**
     * Retrieves the specified file attachment's user file name.
     * @param int $fileID File attachment's ID
     * @return string Label of file attachment
     */
    public static function getLabel($fileID) {
        return sql_get_str(sprintf("SELECT label FROM labels WHERE tbl=%d AND label_id=%d AND lang_id=%d", TBL_FATTACH, $fileID, lang_id(LANG_DIR)), 241);
    }

    /**
    * Retrieves the first file attachment on the specified object that was created at the specified time. There might
    * be more than one possible result, but this function will only return the first one found.
    * @param int $objectID The ID of the object the file attachment is attached to (i.e. answer ID or incident ID)
    * @param int $objectType The type of object the attachment is on (i.e. VTBL_INCIDENTS or TBL_ANSWERS)
    * @param string $createdTime Timestamp The creation time of the file attachment
    * @return int The attachment ID or zero if not found
     */
    public static function getIDFromCreatedTime($objectID, $objectType, $createdTime) {
        return sql_get_int(sprintf("SELECT file_id FROM fattach WHERE id = %d AND tbl = %d AND created = %s",
            $objectID,
            $objectType,
            time2db($createdTime)
        ));
    }

    /**
     * Function to check if the Answer a File Attachment belongs to is accessible by current user
     *
     * @param int $answerID The Answer ID
     * @return bool True if the current user can access the Answer, false otherwise
     */
    public static function isFileAttachmentsAnswerAccessible($answerID)
    {
        $accessList = contact_answer_access();
        // always add 'Help' access list
        $accessList .= ($accessList) ? ",2" : "2";

        $queryString = sprintf("SELECT a_id FROM answers WHERE a_id = %d AND %s AND status_type != %d",
            $answerID,
            bit_list_to_where_clause($accessList, "access_mask"),
            STATUS_TYPE_PRIVATE);
        return (sql_get_int($queryString) > 0);
    }

    /**
     * Returns true if any answer corresponding to the meta answerID is accessible to the end-user.
     * @param int $metaAnswerID ID of the meta answer object.
     * @param bool $accessCheckRequired Whether access level check is required. defaults to true.
     * @return bool Returns true if any of the answers corresponding to the meta answer is accessible.
     */
    public static function isMetaAnswerAccessible($metaAnswerID, $accessCheckRequired = true)
    {
        if($accessCheckRequired)
        {
            $accessList = contact_answer_access();
            //Always add 'Help' access list answers
            $accessList .= ($accessList) ? ",2" : "2";
            $visibilityQuery = bit_list_to_where_clause("$accessList", 'access_mask');
            if($visibilityQuery)
                $whereClause .= " AND $visibilityQuery";
        }
        if(sql_get_int(sprintf('SELECT COUNT(*) FROM answers WHERE m_id = %d AND access_mask > 0 AND status_type = %d' . $whereClause, $metaAnswerID, STATUS_TYPE_PUBLIC)) == 0)
        {
            return false;
        }
        return true;
    }
}
