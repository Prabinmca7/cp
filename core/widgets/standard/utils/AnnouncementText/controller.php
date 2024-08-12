<?php
namespace RightNow\Widgets;

class AnnouncementText extends \RightNow\Libraries\Widget\Base
{
    function __construct($attrs)
    {
        parent::__construct($attrs);
    }

    function getData()
    {
        $announcementPath = HTMLROOT . $this->data['attrs']['file_path'];
        if(!\RightNow\Utils\FileSystem::isReadableFile($announcementPath))
            return false;
        $this->data['announcement'] = file_get_contents($announcementPath);
    }
}
