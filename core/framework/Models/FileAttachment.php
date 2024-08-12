<?php

namespace RightNow\Models;

require_once CORE_FILES . 'compatibility/Internal/Sql/FileAttachment.php';

use Rightnow\Api,
    RightNow\Internal\Sql\FileAttachment as Sql,
    RightNow\Utils\Framework,
    RightNow\Utils\Url,
    RightNow\Utils\Text;

/**
 * Model for retrieving file attachments from various sources such as answers, incidents, guides, etc.
 */
class FileAttachment extends Base {

    /**
     * Function to retrieve file attachment information from database.
     * @param int $fileID The id of the file attachment to retrieve
     * @param int $created Created date of attachment in timestamp format
     * @param boolean $verifyToken If true, the file token in the GET request will be verified
     * @return array|bool Details about the file attachment or false if not found
     */
    public function get($fileID, $created, $verifyToken = false) {
        $response = $this->getResponseObject(false, null);

        if (!$attachment = $this->getDetails($fileID, $created)) {
            $response->error = "A file attachment with an ID of $fileID was not found";
        }
        else if ($this->validate($attachment, ($created && (is_int($created) || ctype_digit($created))), $verifyToken, $warning, $error)) {
            $response->result = $attachment;
            if($attachment->table === TBL_ANSWERS){
                \RightNow\ActionCapture::record('answerFileAttachment', 'view', $attachment->id);
            }
            else if($attachment->table === TBL_SSS_QUESTIONS) {
                \RightNow\ActionCapture::record('socialquestion', 'viewattachment', $attachment->id);
            }
            else if($attachment->table === VTBL_SSS_QUESTION_COMMENTS) {
                \RightNow\ActionCapture::record('socialcomment', 'viewattachment', $attachment->id);
            }
        }
        else {
            if ($warning)
                $response->warning = $warning;
            if ($error)
                $response->error = $error;
        }

        return $response;
    }

    /**
     * Retrieves the first file attachment on the specified object that was created at the specified time. There might
     * be more than one possible result, but this function will only return the first one found.
     * @param int $objectID The ID of the object the file attachment is attached to (i.e. answer ID or incident ID)
     * @param int $objectType The type of object the attachment is on (i.e. VTBL_INCIDENTS or TBL_ANSWERS)
     * @param int $createdTime The creation time of the file attachment in timestamp format
     * @return int|bool The attachment ID or false if not found
     */
    public function getIDFromCreatedTime($objectID, $objectType, $createdTime) {
        return $this->getResponseObject(
            Sql::getIDFromCreatedTime(intval($objectID), intval($objectType), intval($createdTime))
        );
    }

    /**
     * Retrieve and cache relevant details of a file attachment
     * @param int $id The ID of the file attachment
     * @param int $created The creation time of the file attachment in timestamp format
     * @return object|bool File attachment info or false if not found
     */
    private function getDetails($id, $created = null) {
        if (!is_numeric($id)) return false;

        if ($attachment = Framework::checkCache("attachmentDetails-$id")) return $attachment;

        if ($attachment = Sql::get($id, $created)) {
            $attachment = (object) $attachment;
            Framework::setCache("attachmentDetails-$id", $attachment);
        }
        return $attachment;
    }

    /**
     * Retrieves local file name of the file attachment from the object before the full path is added
     * @param int $fileID The ID of the file attachment
     * @param int $created The creation time of the file attachment in timestamp format
     * @return string Local filename of the File attachment or false
     */
    public function getRawLocalFileName($fileID, $created) {
        $attachment = $this->getDetails($fileID, $created);
        return $attachment->localFileName ?: false;
    }

    /**
     * Validates the attachment and modify several attachment items for usage.
     * @param object $attachment Attachment
     * @param boolean $validatingCreateDate Whether the attachment's creation date is being validated
     * @param boolean $verifyToken If true, the file token in the GET request will be verified
     * @param string &$warning Used to populate any warning message
     * @param string &$error Used to populate any error message
     * @return boolean True if validation passed, False if validation failed
     */
    private function validate($attachment, $validatingCreateDate, $verifyToken, &$warning = '', &$error = '') {
        if ($attachment->table && !in_array($attachment->table, array(TBL_ANSWERS, TBL_META_ANSWERS, TBL_THREADS, TBL_SSS_QUESTIONS, VTBL_SSS_QUESTION_COMMENTS))
            && (!Framework::isLoggedIn() || !$validatingCreateDate)) {
            // Minimal check to see if user is logged in before viewing non-public file attachments.
            // Created time must also have been passed as well
            $warning = 'A non-logged-in user attempted to access a non public-type attachment';
            return false;
        }
        else if ($attachment->table === VTBL_INCIDENTS && !$this->CI->model('Incident')->get($attachment->id)->result) {
            $error = "An incident with ID {$attachment->id} was not found";
            return false;
        }
        else if ($attachment->table === TBL_ANSWERS && $attachment->id && !Sql::isFileAttachmentsAnswerAccessible($attachment->id)) {
            // Ensure that the attachment's answer is public
            if (!$this->CI->model('Answer')->isPrivate($attachment->id))
            {
                $warning = "The attachment's answer has an access level";
            }
            else
            {
                $error = "The attachment's answer is not public";
            }
            return false;
        }
         // Checking whether the sibling attachment is accessible
        else if ($attachment->table === TBL_META_ANSWERS && $attachment->id && !Sql::isMetaAnswerAccessible($attachment->id)) {
            if (Sql::isMetaAnswerAccessible($attachment->id, false))
            {
                $warning = "The attachment's answer has an access level";
            }
            else
            {
                $error = "The attachment's answer is not public";
            }
            return false;
        }
        else if ($attachment->table === TBL_THREADS && (CUSTOM_CONTROLLER_REQUEST || ($this->CI->router->fetch_class() !== 'inlineImage' && $this->CI->router->fetch_class() !== 'inlineImg'))) {
            // Incident threads
            $error = "An invalid attempt to retrieve a Thread attachment was made";
            return false;
        }
        else if ($attachment->table === TBL_SSS_QUESTIONS || $isCommentAttachment = ($attachment->table === VTBL_SSS_QUESTION_COMMENTS)) {
            $modelName = isset($isCommentAttachment) && $isCommentAttachment ? 'CommunityComment' : 'CommunityQuestion';
            if($verifyToken && !Framework::isValidCommunityAttachmentToken($attachment->fileID, $attachment->created, $attachment->localFileName)) {
                $qid = $attachment->id;
                if($isCommentAttachment) {
                    $qid = $this->CI->model('CommunityComment')->get($attachment->id)->result->CommunityQuestion->ID;
                    $commentID = $attachment->id;
                }
                Url::redirectToQuestionDetailPage($qid, $commentID);
            }
            //if token goes through, check permission
            $socialObject = $this->CI->model("{$modelName}")->get($attachment->id)->result;
            if(!$socialObject) {
                $error = "No social objects with ID {$attachment->id} were found";
                return false;
            }
        }
        else if (!$attachment->table && Text::stringContains($attachment->contentType, 'image')) {
            // Client workflow images don't have a userfname and table
            if (!$validatingCreateDate || $attachment->type !== FA_TYPE_WF_SCRIPT_IMAGE) {
                // Only allow workflow/guided assistance image requests
                $error = "The attachment doesn't have a parent object specified";
                return false;
            }
            // Client workflow images don't populate fattach tbl with userfname and tbl
            // Assert content_type is constructed like: image/png
            $attachment->userFileName = Sql::getLabel($attachment->fileID) . '.' . Text::getSubstringAfter($attachment->contentType, '/');
        }
        if ($this->CI->agent->browser() === 'Internet Explorer' && ($this->CI->router->fetch_class() !== 'inlineImage' && $this->CI->router->fetch_class() !== 'inlineImg')) {
            // IE needs a url-encoded filename to properly display unicode characters; all other browsers do not.
            // Raw encoded so that any whitespace chars in the filename aren't turned into plus chars.
            // Replace any encoded plus chars (they're the only encoded special chars that browsers display as still encoded) back to unencoded value.
            // Don't do this for the inline image controller though, since we need the name unmodified in order to calculate the hash correctly
            $attachment->userFileName = str_replace('%2B', '+', rawurlencode($attachment->userFileName));
        }
        $attachment->localFileName = Api::fattach_full_path($attachment->localFileName, true);
        return true;
    }
}
