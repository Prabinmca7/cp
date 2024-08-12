<?php

namespace RightNow\Helpers;

use RightNow\Utils\Config;

class IncidentThreadDisplayHelper extends \RightNow\Libraries\Widget\Helper {
     /**
     * Returns the translated string containing author information about the thread.
     * @param object $thread Thread object
     * @return string Author information (entry type, author, channel)
     */
    function getThreadAuthorInfo($thread) {
        $displayValue = '';
        $author = $thread->IncidentThreadPresenter->getAuthorName();
        if ($author) {
            if ($thread->Channel) {
                return sprintf(Config::getMessage(S_S_VIA_CHANNEL_S_LBL), $thread->EntryType->LookupName, $author, $thread->Channel->LookupName);
            }
            return sprintf(Config::getMessage(S_S_LBL), $thread->EntryType->LookupName, $author);
        }
        if ($thread->Channel) {
            return sprintf(Config::getMessage(S_VIA_CHANNEL_S_LBL), $thread->EntryType->LookupName, $thread->Channel->LookupName);
        }
        return $thread->EntryType->LookupName;
    }
}
