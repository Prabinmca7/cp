<?
namespace RightNow\Libraries\ThirdParty\SimpleHtmlDomExtension;

use RightNow\Libraries\ThirdParty\SimpleHtmlDom;

require_once CPCORE . 'Libraries/ThirdParty/SimpleHtmlDom.php';

/**
 * Interfaces with the simple_html_dom library. Basically replaces #str_get_html so that
 * BlockDom is used when necessary.
 * @param $view View to parse
 * @param $parseBlockContents Boolean optional Whether to parse
 * rn:block contents (standard views) or to skip parsing (custom views)
 * @return Object|Boolean simple_html_dom instance or false if $view is empty
 */
function loadDom($view, $parseBlockContents = true) {
    if (empty($view)) return false;

    $className = '\RightNow\Libraries\ThirdParty\\' . (($parseBlockContents) 
        ? 'SimpleHtmlDom\simple_html_dom'
        : 'SimpleHtmlDomExtension\BlockDom');

    $dom = new $className(null, true, true, DEFAULT_TARGET_CHARSET, false);
    $dom->load($view, true, false);

    return $dom;
}

/**
 * @class BlockDom
 * Subclasses simple_html_dom in order to modify the default behavior
 * when encountering extending view rn:blocks.
 */
class BlockDom extends SimpleHtmlDom\simple_html_dom {
    /**
     * Calls into simple_html_dom#read_tag. Modifies its behavior when encountering rn:block tags.
     * Rather than parsing the content of a extending block and assuming valid close-tag HTML
     * (which can skip past the close-block tag),
     * just grab all the raw content in one fell swoop and skip parsing it.
     * @return Boolean The result of simple_html_dom#read_tag
     */
    protected function read_tag() {
        $parentReturn = parent::read_tag();

        if ($parentReturn && $this->tag === 'rn:block' && $this->node->_[SimpleHtmlDom\Defines::HDOM_INFO_END] !== 0) {
            // Don't do anything with self-closing blocks.
            // Copy all content until the next end-block tag.
            $endBlock = '</rn:block>';
            $contents = $this->copy_until_char($endBlock);
            // Library replaces all non-HTML content (e.g. PHP code) with temporary placeholder to aid parser.
            // Restore any of the original content that was replaced.
            $this->node->_[SimpleHtmlDom\Defines::HDOM_INFO_INNER] = $this->restore_noise($contents);
            // Update the parser's position to the next character after the end-block tag.
            $this->pos += strlen($endBlock);
            $this->char = $this->doc[$this->pos];
        }

        return $parentReturn;
    }
}
