<?php
namespace RightNow\Widgets;

class ResetPassword extends \RightNow\Libraries\Widget\Base
{
    function __construct($attrs)
    {
        parent::__construct($attrs);

        $this->setAjaxHandlers(array(
            'reset_password_ajax' => array(
                'method' => 'performResetPassword',
                'exempt_from_login_requirement' => true,
            ),
        ));
    }

    function getData()
    {
        if($parmString = \RightNow\Utils\Url::getParameter('cred'))
        {
            \RightNow\Utils\Url::redirectIfPageNeedsToBeSecure();

            $this->data['js']['resetString'] = $parmString;
            $parmString = \RightNow\Api::ver_ske_decrypt($parmString);
            // token should be in the form: 'c_id/expire_date'.
            // (with an optional /login if the contact doesn't have a login)
            // (and an optional email address if multiple contacts share the same email [CFG_OPT_DUPLICATE_EMAIL])
            $parmArray = explode('/', $parmString);
            if(count($parmArray) == 4){
                list($contactID, $expire, $loginRequired, $sharedEmail) = $parmArray;
            }else if(count($parmArray) == 3){
                list($contactID, $expire, $loginRequired) = $parmArray;
            }else if(count($parmArray) == 2){
                list($contactID, $expire) = $parmArray;
            }else if(count($parmArray) == 1){
                list($contactID) = $parmArray;
            }
            if($expire && (isset($contactID) || isset($sharedEmail)))
            {
                $contactID = intval($contactID);
                $expire = intval($expire);
                //contact without a login: put login Input widget in the form.
                $this->data['loginRequired'] = (isset($loginRequired) && $loginRequired) ? true : false;
                if($expire > time())
                {
                    if($contactID === 0 && $sharedEmail)
                    {
                        //creating a new contact for a shared email
                        return;
                    }
                    else
                    {
                        $contact = $this->CI->model('Contact')->get($contactID)->result;
                        //valid contact found
                        if($contact && isset($contact->Emails[0]->Address))
                        {
                            //Only two valid cases to display the password form:
                            //(1) if they don't have a login then display the login field in addition to pw form: for finishing account creation
                            if(!$contact->Login && $this->data['loginRequired'])
                                return;
                            //(2) they came from reset passwd link: if the db value matches the email parm value, display the form
                            if($contact->PasswordEmailExpirationTime === $expire)
                                return;
                        }
                    }
                }
            }
        }
        echo '<br/>' . \RightNow\Utils\Config::getMessage(LINK_ACCESS_PAGE_EXPIRED_CMD);
        echo '<br/>' . sprintf(\RightNow\Utils\Config::getMessage(REQ_PASSWD_RESET_MSG_PLS_VISIT_HREF_MSG), \RightNow\Utils\Config::getConfig(CP_ACCOUNT_ASSIST_URL, 'RNW_UI'), \RightNow\Utils\Config::getMessage(ACCOUNT_ASSISTANCE_LBL));
        return false;
    }

    /**
     * Reset password Ajax handler. Echos out JSON encoded result
     * @param array|null $parameters Post parameters
     */
    function performResetPassword($parameters)
    {
        \RightNow\Libraries\AbuseDetection::check();
        $data = json_decode($parameters['form']);
        if(!$data)
        {
            \RightNow\Utils\Framework::writeContentWithLengthAndExit(json_encode(\RightNow\Utils\Config::getMessage(JAVASCRIPT_ENABLED_FEATURE_MSG)), 'application/json');
        }
        $this->echoJSON($this->CI->model('Field')->resetPassword($data, $parameters['pw_reset'])->toJson());
    }
}
