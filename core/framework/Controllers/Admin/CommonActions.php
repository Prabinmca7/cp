<?php

namespace RightNow\Controllers\Admin;

/**
 * Provides common action for admin flows
 */
class CommonActions extends Base {

    function __construct() {
        parent::__construct(true, '_verifyLoginWithCPEditPermission');
    }

    /**
     * Gets the new form token to be used for post request
     */
    public function getNewFormToken() {
        if ($formToken = $this->input->post('formToken')) {
            $this->_renderJSON(array(
                'newToken' => \RightNow\Internal\Utils\Framework::createAdminPageCsrfToken(1, $this->account->acct_id)
            ));
        }
    }
}
