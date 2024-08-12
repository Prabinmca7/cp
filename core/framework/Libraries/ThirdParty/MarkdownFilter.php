<?
namespace RightNow\Libraries\ThirdParty;

require_once CPCORE . 'Libraries/ThirdParty/Markdown.php';

/**
 * Wraps the Markdown library.
 */
class MarkdownFilter {
    private static $parser;

    /**
     * Transforms the given markdown into HTML.
     * @param  string $text markdown
     * @return string       HTML
     */
    static function toHTML ($text) {
        self::$parser || (self::$parser = self::createParser());

        return self::transform(self::$parser->transform($text));
    }

    /**
     * Filters the given HTML.
     * @param  string $html HTML
     * @return string       HTML filtered.
     */
    private static function transform ($html) {
        // Add rel='nofollow' onto all links to prevent comment link spamming from having
        // negative SEO implications.
        return str_replace('<a href=', '<a rel="nofollow" href=', $html);
    }

    /**
     * Creates a new instance of the Markdown library,
     * setting the options we want.
     * @return Markdown instance
     */
    private static function createParser () {
        $parser = new Markdown();

        $parser->no_markup = true;
        $parser->empty_element_suffix = '>';

        return $parser;
    }
}
