<?php
namespace RightNow\Controllers;

use RightNow\Api;
require_once CPCORE . 'Controllers/Fattach.php';

/**
 * Endpoint for serving images that are within incident threads. No need to document this
 * as it isn't something that CP users need to be aware of.
 *
 * @internal
 */
final class InlineImage extends Fattach
{
    public function __construct(){
        parent::__construct();
        parent::_setMethodsExemptFromContactLoginRequired(array(
            'get'
        ));
    }

    /**
     * Retrieves an inline image given it's ID and sends the content to the browser
     * @param int $id ID of the file attachment
     * @param string $token Hashed token to verify the request
     */
    public function get($id, $token = null){
        if($token === null)
            $this->send404Response();

        $attachment = $this->model('FileAttachment')->get($id, null);
        $attachment = $attachment->result;
        if($attachment === false)
            $this->send404Response();

        $generatedToken = md5($id . $attachment->created . $attachment->userFileName);
        if($token !== $generatedToken)
            $this->send404Response();

        parent::_sendContent($attachment);
    }

    /**
     * Retrieves an inline image given it's guid and sends the content to the browser
     * @param int $guid Guid of the file attachment
     */
    public function guidGet($guid = null){
        if($guid === null)
            $this->send400Response();

        $pairData = array(
            'index_field_name'    => 'guid_value',
            'index_field_value'      => $guid
        );
        $attachment = Api::fattach_thread_guid_get($pairData);
        if($attachment === false || $attachment === null)
            $this->send400Response();

        $attachment = (object) array(
            'fileID'        => $attachment['file_id'],
            'created'       => $attachment['created'],
            'type'          => $attachment['type'],
            'id'            => $attachment['id'],
            'size'          => $attachment['size'],
            'table'         => $attachment['tbl'],
            'contentType'   => $attachment['content_type'],
            'userFileName'  => $attachment['userfname'],
            'localFileName' => Api::fattach_full_path($attachment['localfname'], true),
        );

        parent::_sendContent($attachment);
    }

    /**
     * Utility method to send a 404 header and exit.
     */
    private function send404Response(){
        header($_SERVER["SERVER_PROTOCOL"] . " 404 Not Found");
        exit();
    }

    /**
     * Utility method to send a 400 header and exit.
     */
    private function send400Response(){
        header($_SERVER["SERVER_PROTOCOL"] . " 400 Bad Request");
        exit();
    }
}
