<?php

namespace RightNow\Controllers\Admin;

require_once CPCORE . 'Internal/Libraries/WebDav/Server.php';

class Webdav extends Base
{
    function __construct()
    {
        parent::__construct(false, '_verifyLoginWithHttpAuthWithCPEdit');
    }

    /**
     * Instantiate a PHP WebDAV Server and handle the request.
     */
    function index()
    {
        $server = new \RightNow\Internal\Libraries\WebDav\Server($this->account);
        umask(0);
        $server->serve();
    }
}
