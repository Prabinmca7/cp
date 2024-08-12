<?php

namespace RightNow\Helpers;

class SocialContentFlaggingHelper extends \RightNow\Libraries\Widget\Helper {

    public $attrs;
    public $userFlag;
    public $flags;

    /**
     * Returns the already flagged label if the user has flagged that piece of content.
     * @return string Flag title to be displayed for this piece of content
     */
    function getFlagTitle() {
        return $this->userFlag ? $this->attrs['label_already_flagged_tooltip'] : '';
    }

    /**
     * Returns the text for screen reader if more than 1 flag type present.
     * @return string Flag menu text to be read by screen reader
     */
    function getFlagMenuScreenReaderText() {
        return (count($this->flags) > 1) ? sprintf(\RightNow\Utils\Config::getMessage(PRES_ENT_OPEN_CLOSE_MENU_TAB_NAVIGATE_MSG), $this->attrs['label_button']) : '';
    }

    /**
     * Determines which classes should be shown on the rn_Flagged element
     * @return string List of class names to be displayed. Separated by spaces.
     */
    function getFlaggedClassNames() {
        $classNames = '';

        if (count($this->flags) === 1) {
            $classNames .= 'rn_NotAllowed ';
        }

        if (!$this->userFlag) {
            $classNames .= 'rn_Hidden ';
        }

        return $classNames;
    }
}
