<?php
namespace RightNow\Widgets;

use RightNow\Utils\Text;

class RichTextInput extends \RightNow\Widgets\TextInput {
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {
        // TK - verify the specified Connect field is a longtext and has a content type that includes MD.

        // TK - Need to implement better (low key) way to include these scripts.
        // Patrick nixed my grand, ideal solution that adds some pretty powerful JS code reuse to the framework.

        $parent = parent::getData();

        if ($parent === false) return false;

        $this->data['js']['ckeditorPath'] = \RightNow\Utils\Url::getCoreAssetPath('thirdParty/js/ORTL/ortl.js');

        $this->data['js']['lang'] = Text::getLanguageCode();
        
        // Need to check on fieldMetada->usageType once MAPI provides the support
        if ($this->data['value']) {
            $this->data['js']['initialValue'] = \RightNow\Libraries\Formatter::formatTextEntry($this->data['value'], $this->data['attrs']['sanitize_type']?:'text/html', false);
        }

        return $parent;
    }
}
