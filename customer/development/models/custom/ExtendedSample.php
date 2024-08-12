<?php
namespace Custom\Models;

/**
 * This is an example of a custom model that extends a standard model, specifically the standard Answer model. To
 * automatically have this model be used in place of calls to the standard answer model, open the
 * cp/customer/development/config/extensions.yml file via WebDAV and edit it so it contains:
 *
 *    modelExtensions:
 *        Answer: ExtendedSample
 */
class ExtendedSample extends \RightNow\Models\Answer
{
    function __construct()
    {
        parent::__construct();
    }

    /**
     * Since this model overrides and extends the standard Answer model, any calls in the form
     *
     *     $this->CI->model('Answer')->emailToFriend(...) From a custom widget or model OR,
     *     $this->model('Answer')->emailToFriend(...) From a custom controller          OR,
     *     get_instance()->model('Answer')->emailToFriend(...) From any static method
     *
     * will call into this function since it is named the same. Be sure to call into the standard model by using
     * parent::emailToFriend(...) and modify things either immediately before calling the parent or after getting
     * the result. Otherwise, you won't receive any critical bug fixes that may come in a later version.
     * @param string $sendTo Email address to send to
     * @param string $name Name of recipient
     * @param string $from Email address of sender
     * @param int $answerID ID of answer to email
     * @return int Result of email call
     */
    function emailToFriend($sendTo, $name, $from, $answerID){
        //Modify from field to company email and add user specified email to name
        $name .= "(Email: $from)";
        $from = "support@companyName.com";
        $response = parent::emailToFriend($sendTo, $name, $from, $answerID);
        //Remember that $response is an instance of a ResponseObject, so we need to check the return
        //property to see if it succeeded or not
        if($response->result){
            return $response;
        }
        //Add an additional error to the return which might be potentially handled by a custom widget
        $response->error = "Unable to send email, please try again later.";
        return $response;
    }
}
