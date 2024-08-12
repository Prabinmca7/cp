<?php
namespace RightNow\Models;

use RightNow\Internal\Sql\Friend as Sql,
    RightNow\Connect\v1_4 as Connect,
    RightNow\Internal\Utils\Version as Version;

if (!IS_HOSTED){
    require_once CORE_FILES . 'compatibility/Mappings/Functions.php';
}

/**
 * Methods for handling marketing/feedback forward to a friend
 */
class Friend extends Base
{
    const MAX_FRIEND_COUNT = 5;

    /**
     * Uses ROQL to get the email address for the contact ID given
     *
     * @param int $contactID The contact we are looking up
     *
     * @return string|null The address
     */
    public function getContactEmailAddress($contactID)
    {
        if ($contactID > 0)
        {
            if(Version::compareVersionNumbers(Version::getVersionNumber(CP_FRAMEWORK_VERSION), "3.8") >= 0)
                $res = Connect\ROQL::query("SELECT C.Emails.Address FROM Contact C WHERE C.Emails.EmailList.AddressType=0 AND C.ID = $contactID");
            else
                $res = \RightNow\Connect\v1_3\ROQL::query("SELECT C.Emails.Address FROM Contact C WHERE C.Emails.EmailList.AddressType=0 AND C.ID = $contactID");
            $row = $res->next();
            $contactRow = $row->next();
            return $contactRow['Address'];
        }
    }

    /**
     * Gets the subject of the mailing from the tracking object
     *
     * @param object $track The tracking object
     *
     * @return string|null The subject
     */
    public function getSubjectFromTrackingString($track)
    {
        if($track->format_id > 0)
            return Sql::getSubjectFromMailingFormatID($track->format_id);

        if ($track->doc_id)
        {
            $documentHtmlXml = Sql::getDocumentHtmlXml($track->doc_id);
            return $this->getTitleFromHtmlXml($documentHtmlXml);
        }
    }

    /**
     * Gets the subject of the mailing from the shortcut
     *
     * @param string $shortcut The shortcut to the flow_web_page
     *
     * @return string|null The subject
     */
    public function getSubjectFromShortcut($shortcut)
    {
        $docID = Sql::getDocIDFromShortcut($shortcut);

        if ($docID)
        {
            $documentHtmlXml = Sql::getDocumentHtmlXml($docID);
            return $this->getTitleFromHtmlXml($documentHtmlXml);
        }
    }

    /**
     * Searches the given html/xml for the contents of it's <title> tag
     *
     * @param string $xmlString The html/xml
     *
     * @return string The title
     */
    private function getTitleFromHtmlXml($xmlString)
    {
        $position = strpos($xmlString, "<title>");

        if ($position === false)
            return "";

        $position += strlen("<title>");
        $length = strpos($xmlString, "</title>", $position) - $position;
        return substr($xmlString, $position, $length);
    }

    /**
     * Updates stats for this forward to a friend transaction
     *
     * @param object $track The tracking object
     * @param int $friendCount The number of friends forwarded to
     * @param array $transData An array containing transaction data
     *
     * @return void
     */
    public function updateViewStats($track, $friendCount, $transData)
    {
        $pairdata = array('type' => MA_TRANS_CLICK_FRIEND, 'doc_id' => $track->doc_id);

        if($track->flags === 0 && $track->c_id > 0)
            $pairdata['c_id'] = $track->c_id;
        else if($track->c_id > 0)
            $pairdata['ref_c_id'] = $track->c_id;

        if($track->format_id > 0)
            $pairdata['format_id'] = $track->format_id;

        if($track->flow_id > 0)
            $pairdata['flow_id'] = $track->flow_id;

        if($track->media > 0)
            $pairdata['media'] = $track->media;

        if ($track->doc_id > 0 && $friendCount < self::MAX_FRIEND_COUNT)
        {
            $this->insertStats($pairdata, $pairdata['type']);
            // If they clicked this link but there is no view transaction, add a view transaction
            if ($transData['is_existing_view'] === 0 && $track->format_id > 0)
                $this->insertStats($pairdata, MA_TRANS_EMAIL_VIEW);
        }
    }

    /**
     * Inserts our stats into the DQA pipeline
     *
     * @param array $pairdata An array of data to be pushed through dqa_insert
     * @param int $type The transaction type (click or view)
     *
     * @return void
     */
    private function insertStats($pairdata, $type)
    {
        $created = time();
        $entry = array('ref_c_id' => $pairdata['ref_c_id'], 'c_id' => $pairdata['c_id'],
                       'format_id' => $pairdata['format_id'], 'media' => $pairdata['media'],
                       'flow_id' => $pairdata['flow_id'], 'type' => $type, 'created' => $created,
                       'doc_id' => $pairdata['doc_id'], 'flow_web_page_id' => $pairdata['flow_web_page_id'], 'table' => 'ma_trans');
        $json = json_encode($entry);
        dqa_insert(DQA_DOCUMENT_STATS, $json);
    }

    /**
     * Submits the forward to a friend (calls ma_forward_friend)
     *
     * @return void
     */
    public function submit()
    {
        $track = generic_track_decode($_POST['track']);
        $shortcut = $_POST['sc'];
        $webpageID = 0;
        if($shortcut !== "")
        {
            $webpageID = Sql::getFlowWebPageIDFromShortcut($shortcut);
            if ($webpageID < 1)
            {
                echo getMessage(ERR_INV_PARAMS_MSG);
                exit;
            }
        }
        if (!$track)
        {
            echo getMessage(ERR_INV_PARAMS_MSG);
            exit;
        }
        $previewMode = false;

        if($track->flags & GENERIC_TRACK_FLAG_PREVIEW)
        {
            $previewMode = true;
        }
        else
        {
            if(!($track->flags & GENERIC_TRACK_FLAG_PROOF))
            {
                $pairdata = array('type' => MA_TRANS_FRIEND_SUBMIT, 'doc_id' => $track->doc_id);

                if($track->flow_id > 0)
                    $pairdata['flow_id'] = $track->flow_id;

                if($track->format_id > 0)
                    $pairdata['format_id'] = $track->format_id;

                if($track->media > 0)
                    $pairdata['media'] = $track->media;

                if($track->flags & GENERIC_TRACK_FLAG_FRIEND && $track->c_id > 0)
                    $pairdata['ref_c_id'] = $track->c_id;
                else if($track->c_id > 0)
                    $pairdata['c_id'] = $track->c_id;

                if ($track->doc_id > 0)
                    $this->insertStats($pairdata, $pairdata['type']);

                if(is_int($track->format_id) && $track->format_id > 0)
                    $subject = Sql::getSubjectFromMailingFormatID($track->format_id);
            }

            if (strlen($subject) === 0)
            {
                $subject = getConfig(RNM_FORWARD_FRIEND_DEFAULT_SUBJECT, "MA");

                // getConfig() will return 0 for null which is different than cfg_get()
                if (strlen($subject) === 0 || $subject === 0)
                    $subject = getMessage(FORWARD_TO_FRIEND_UC_CMD);
            }

            $emailAddresses = preg_split("/[\s]*[,;][\s]*/", $_POST['p_addresses']);

            $oldGreeting = $_POST['p_greeting'];
            $greeting = "";

            $this->validateSubmission($emailAddresses, $_POST['p_from_email']);

            // only forward this if there is no greeting. There shouldn't be one as we have removed this from the view.
            // same idea of if there is a comment in the email field. We no longer allow it so it should not exist.
            if (strlen($oldGreeting) === 0)
                ma_forward_friend($_POST['track'], 0, $webpageID, count($emailAddresses), $emailAddresses, $greeting, $_POST['p_from_email'], $subject);
        }
    }

    /**
     * Validates the emails that the user input
     *
     * @param array $toEmailAddressArray An array containing each email address
     * @param string $fromEmailAddress The email address the user claimed as their own
     *
     * @return void
     */
    private function validateSubmission(array $toEmailAddressArray, $fromEmailAddress)
    {
        foreach ($toEmailAddressArray as $addr)
        {
            if (!isValidEmailAddress($addr))
            {
                echo getMessage(ENTRED_INV_EMAIL_ADDR_PLS_CHECK_MSG);
                exit;
            }
        }

        if (!isValidEmailAddress($fromEmailAddress))
        {
            echo getMessage(ENTRED_INV_EMAIL_ADDR_PLS_CHECK_MSG);
            exit;
        }
    }
}
