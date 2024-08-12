<?php
namespace RightNow\Widgets;

use \RightNow\Utils\Framework;

class FileAttachmentUpload extends \RightNow\Libraries\Widget\Input {
    const MAX_UPLOAD_LIMIT = 100;
    function __construct($attrs){
        parent::__construct($attrs);
    }

    function getData(){
        $this->data['js']['name'] = $this->data['attrs']['name'];
        $this->data['js']['id'] = $this->instanceID;
        $this->data['js']['path'] = $this->getPath();
        $this->data['js']['valid_file_extensions'] = \RightNow\Utils\Config::getConfig(VALID_FILE_EXTENSIONS);
        $this->data['js']['constraints'] = $this->generateFormConstraints();
        $this->data['js']['f_tok'] = Framework::createTokenWithExpiration(0);
        // Check if the key 'max_attachments' exists in $this->data['attrs']
        if (isset($this->data['attrs']['max_attachments'])) {
            $maxAttachments = $this->data['attrs']['max_attachments'];
            $this->data['attrs']['max_attachments'] = ($maxAttachments === 0 || $maxAttachments > 100) ? self::MAX_UPLOAD_LIMIT : $maxAttachments;
        }
        $this->data['js']['max_attach_hash'] = Framework::getSHA2Hash(get_instance()->session->getSessionData('sessionID') . (isset($this->data['attrs']['max_attachments']) ? $this->data['attrs']['max_attachments'] : 0 ) . strrev(get_instance()->session->getSessionData('sessionID')));

        if (parent::getData() === false) return false;

        //Check if incident already has max number of file attachments
        if($primaryObject = \RightNow\Utils\Connect::getObjectInstance($this->table))
        {
            $this->data['js']['attachmentCount'] = ($primaryObject->FileAttachments) ? count($primaryObject->FileAttachments) : 0;
        }
        // Check if the key 'max_attachments' exists in $this->data['attrs']
        if (isset($this->data['attrs']['max_attachments']) && $this->data['attrs']['max_attachments'] !== 0 && $this->data['attrs']['min_required_attachments'] > $this->data['attrs']['max_attachments']) {
            echo $this->reportError(sprintf(\RightNow\Utils\Config::getMessage(PCT_S_PCT_S_LBL), 'min_required_attachments', 'max_attachments'));
            return false;
        }
    }

    function generateFormConstraints() {
        $validFileExt = $this->data['js']['valid_file_extensions'];
        $constraintName = 'upload_' . $this->instanceID;
        $constraints = json_encode(array($constraintName => $validFileExt));
        return Framework::createPostToken($constraints, $this->getPath(), '/ci/fattach/upload');
    }
}
