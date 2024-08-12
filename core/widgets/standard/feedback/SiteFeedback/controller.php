<?php
namespace RightNow\Widgets;

class SiteFeedback extends \RightNow\Libraries\Widget\Base
{
    function __construct($attrs)
    {
        parent::__construct($attrs);

        $this->setAjaxHandlers(array(
            'submit_site_feedback_ajax' => array(
                'method' => 'submitSiteFeedback',
                'clickstream' => 'site_feedback',
            ),
        ));
    }

    function getData()
    {
        $this->data['js'] = array(
            'f_tok' => \RightNow\Utils\Framework::createTokenWithExpiration(0),
            'isProfile' => false,
            'email' => '',
        );

        if($emailAddress = $this->CI->session->getProfileData('email'))
        {
            $this->data['js']['email'] = $emailAddress;
            $this->data['js']['isProfile'] = true;
        }
        else if($this->CI->session->getSessionData('previouslySeenEmail'))
        {
            $this->data['js']['email'] = $this->CI->session->getSessionData('previouslySeenEmail');
        }
    }

    /**
     * Submit site feedback. Echoes the JSON encoded result.
     * @param array|null $parameters Post parameters
     */
    function submitSiteFeedback($parameters)
    {
        $incidentID = $error = null;
        $returnCode = 'error';

        if (count($parameters)) {
            $response = $this->CI->model('Incident')->submitFeedback(null, $parameters['rate'], null, null, $parameters['message'], $parameters['email']);
            if ((!$error = $response->error) && ($ID = $response->result->ID)) {
                $returnCode = 'ID';
                $incidentID = $ID;
            }
        }

        $this->renderJSON(array($returnCode => ($incidentID ?: strval($error) ?: \RightNow\Utils\Config::getMessage(THERE_PROB_REQ_ACTION_COULD_COMPLETED_MSG))));
    }
}
