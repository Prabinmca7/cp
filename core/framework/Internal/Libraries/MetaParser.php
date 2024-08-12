<?php
namespace RightNow\Internal\Libraries;
use RightNow\Utils\Tags;
final class MetaParser
{
    private $metainfo;
    private $removeMetaTags;

    public function buildInfo(&$buffer, $removeMetaTags = true)
    {
        $this->removeMetaTags = $removeMetaTags;
        //build the meta info array from the <rn:meta> tag stored within the buffer
        //example: <rn:meta title="Page Title" template="standard.php" />
        $this->metainfo = array();
        $buffer = preg_replace_callback(Tags::getMetaTagPattern(), array($this, 'metaReplacer'), $buffer);

        //return array built from within meta_replacer below
        //example: array[title => Page Title, template => standard.php]
        return $this->metainfo;
    }

    private function metaReplacer($matches)
    {
        if (\RightNow\Utils\Text::beginsWith($matches[0], '<!')) {
            // Just return comments unmodified.
            return $matches[0];
        }

        foreach (Tags::getHtmlAttributes($matches[0]) as $attribute)
        {
            if($attribute->attributeName == 'title')
                $value = Tags::escapeForWithinPhp($attribute->attributeValue);
            else
                $value = $attribute->attributeValue;

            $this->metainfo[$attribute->attributeName] = $value;
        }
        if ($this->removeMetaTags)
            return ''; //replaces the <rn:meta.../> tag with blank string
        return $matches[0]; //replaces the match with the same value, which should be a no-op.
    }
}
