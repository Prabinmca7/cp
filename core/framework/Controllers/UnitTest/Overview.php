<?php

namespace RightNow\Controllers\UnitTest;

class Overview extends \RightNow\Controllers\Base {
    function index() {
        $this->load->view('tests/overview.php');
    }
}
