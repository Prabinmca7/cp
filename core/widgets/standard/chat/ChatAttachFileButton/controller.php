<?php
namespace RightNow\Widgets;

class ChatAttachFileButton extends FileAttachmentUpload
{
    function __construct($attrs)
    {
        parent::__construct($attrs);
    }

    function getData()
    {
        parent::getData();

        $this->data['js']['name'] = null;
        $this->classList->add('rn_Hidden');
        
        $this->data['js']['f_tok'] = \RightNow\Utils\Framework::createTokenWithExpiration(0);
    }
}
