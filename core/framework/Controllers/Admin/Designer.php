<?php

namespace RightNow\Controllers\Admin;

/**
 * The hollow shell of the controller that used to support the Dreamweaver extension.
 * I'm keeping this around just to give users a hint that it's dead.
 * Even this probably ought to be killed off at some point in the future, say 13.8.
 */
class Designer extends Base {
    function __construct() {
        parent::__construct(true, '_verifyLoginWithCPEditPermission');
    }

    function checkCredentials() {
        $this->index();
    }

    public function definitions() {
        $this->index();
    }

    public function searchMessageBase() {
        $this->index();
    }

    public function index() {
        $this->_render('designer/index', array(), \RightNow\Utils\Config::getMessage(TOOLS_LBL));
    }

    public function showEufConfigPage() {
        $this->index();
    }

    public function download() {
        $this->index();
    }

    public function getOptlist() {
        $this->index();
    }

    public function update() {
        $this->index();
    }

    public function versionChecksum() {
        $this->index();
    }

    public function mmpreview() {
        $this->index();
    }
}
