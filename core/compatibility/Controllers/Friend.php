<?php
namespace RightNow\Controllers;

use RightNow\Internal\Sql\Friend as Sql;

if (!IS_HOSTED){
    require_once CORE_FILES . 'compatibility/Mappings/Functions.php';
}

require_once CORE_FILES . 'compatibility/Internal/Sql/Friend.php';

/**
 * Endpoints for marketing/feedback forward to a friend functionality
 */
final class Friend extends Base {

    /**
     * Endpoint for marketing/feedback forward to a friend functionality
     *
     * @return void
     */
    public function forward() {
        require_once DOCROOT.'/ma/util.phph';

        $this->parameters = $this->uri->uri_to_assoc(3);
        $this->parameters[MA_QS_ENCODED_PARM] = urldecode($this->parameters[MA_QS_ENCODED_PARM]);

        $track = generic_track_decode($this->parameters[MA_QS_ENCODED_PARM]);
        $shortcut = $this->parameters[MA_QS_WF_SHORTCUT_PARM];

        if ($track)
        {
            if($track->format_id > 0 && $track->c_id > 0 && ($track->flags === 0 || $track->flags === GENERIC_TRACK_FLAG_FRIEND))
            {
                if (!($track->flags & GENERIC_TRACK_FLAG_FRIEND))
                {
                    $transData = Sql::getTransData($track);
                    $friendCount = $transData["friend_count"];
                }
            }

            // Simplest way to look for the proof, or preview flags.
            // If there are neither, then we need to log this email.
            if (($track->flags === 0) || ($track->flags === GENERIC_TRACK_FLAG_FRIEND))
            {
                $this->model('Friend')->updateViewStats($track, $friendCount, $transData);
                if($track->flags === 0 && $track->c_id > 0)
                    $this->emailAddress = $this->model('Friend')->getContactEmailAddress($track->c_id);
            }
            $this->subject = $this->model('Friend')->getSubjectFromTrackingString($track);
        }
        else if ($shortcut)
        {
            $this->subject = $this->model('Friend')->getSubjectFromShortcut($shortcut);
        }

        // both the tracking string and the shortcut were invalid
        // just give a standard subject
        if (strlen($this->subject) === 0 || $this->subject === 0)
        {
            $this->subject = getConfig(RNM_FORWARD_FRIEND_DEFAULT_SUBJECT, "MA");

            if (strlen($this->subject) === 0 || $this->subject === 0)
                $this->subject = getMessage(MESSAGE_CONTENT_FORWARDED_FRIEND_LBL);
        }
        $this->isMobile = $this->model('Document')->isMobile(Sql::getSurveyID($track->flow_id));

        $this->load->file(CORE_FILES.'compatibility/Views/ma/friend/index.php');
    }

    /**
     * Submit endpoint for marketing/feedback forward to a friend functionality
     *
     * @return void
     */
    function submit()
    {
        $this->parameters = $this->uri->uri_to_assoc(3);
        // Convert from recaptcha into generic for ADS
        $_POST['abuse_challenge_response'] = $_POST['recaptcha_response_field'];
        $_POST['abuse_challenge_opaque'] = $_POST['recaptcha_challenge_field'];

        if (\RightNow\Libraries\AbuseDetection::isAbuse())
        {
            $this->load->file(CORE_FILES."compatibility/Views/ma/abuse/index.php", true);
        }
        else
        {
            $this->model('Friend')->submit();
            $this->isMobile = $this->parameters['mobile'] === "1";
            $this->load->file(CORE_FILES.'compatibility/Views/ma/friend/results.php');
        }
    }
}
