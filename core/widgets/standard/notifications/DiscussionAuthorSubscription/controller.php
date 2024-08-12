<?php

namespace RightNow\Widgets;

use RightNow\Utils\Framework;

class DiscussionAuthorSubscription extends \RightNow\Libraries\Widget\Base {

    function __construct ($attrs) {
        parent::__construct($attrs);
        $this->setAjaxHandlers(array(
            'fetch_prodcat_subscription_ajax' => array(
                'method' => 'isSubscribedToProdCat',
                'clickstream' => 'is_subscribed_to_prodcat_action',
            )
        ));
    }

    function getData () {
        if (!Framework::isSocialUser()) {
            return false;
        }
      
        $prodCatID = (int) \RightNow\Utils\Url::getParameter($this->data['attrs']['prodcat_type'] === 'Product' ? 'p' : 'c');
        $this->data['subscription_id'] = $prodCatID ? $this->CI->Model('SocialSubscription')->getSubscriptionID($prodCatID, $this->CI->session->getProfileData('socialUserID'), $this->data['attrs']['prodcat_type'])->result : null;
        
        $this->data['js']['f_tok'] = \RightNow\Utils\Framework::createTokenWithExpiration(0);
    }

    /**
     * Fetches if the logged in user is subscribed to the product
     * @param array|null $params Post parameters
     */
    function isSubscribedToProdCat ($params) {
        $subscriptionID = $this->CI->Model('SocialSubscription')->getSubscriptionID($params['prodCatID'], $this->CI->session->getProfileData('socialUserID'), $this->data['attrs']['prodcat_type'])->result;
        echo json_encode($subscriptionID !== null);
    }
}
