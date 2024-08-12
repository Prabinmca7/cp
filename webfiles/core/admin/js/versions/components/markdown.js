YUI.add('basic-markdown', function(Y) {
    "use strict"; // Because, why not.
    /**
    * Custom YUI Module that provides _basic_ markdown-to-HTML conversion.
    *
    * Markdown syntax that's supported:
    *
    * _Italic_ and *Italic*
    * __Bold__ and **Bold**
    * Inline `code` blocks
    * Automatic links <http://placesheen.com>
    * [link](http://placesheen.com) and [link](http://placesheen.com "with title")
    *
    * Intended for changelog line-item conversion. That's why the limited subset of markdown
    * that's supported (basically just inline markdown stuff--nothing block-level).
    * Also, because markdown-to-HTML conversion is kind of awful.
    */
    var italic = /(\*|_)(?=\S)([^\r]*?\S)\1/g,
        bold = /(\*\*|__)(?=\S)([^\r]*?\S[\*_]*)\1/g,
        autoLink = /<((https?):[^'">\s]+)>/gi,
        inlineCode = /(^|[^\\])(`+)([^\r]*?[^`])\2(?!`)/gm,
        htmlTag = /(<[a-z\/!$]("[^"]*"|'[^']*'|[^'">])*>|<!(--.*?--\s*)+>)/gi,
        inlineLink = /(\[((?:\[[^\]]*\]|[^\[\]])*)\]\([ \t]*()<?(.*?)>?[ \t]*((['"])(.*?)\6[ \t]*)?\))/g;

    /**
     * Replaces inline `code blocks` with
     * code tags.
     * @param  {string} text input markdown
     * @return {string}      converted string
     */
    function inlineCodeBlocks(text) {
        return text.replace(inlineCode, function(wholeMatch, beforeGrave, dontCare, code) {
            code = code
                // leading & trailing whitespace
                .replace(/^([ \t]*)/g,"")
                .replace(/[ \t]*$/g,"")
                // encode code blocks
                .replace(/&/g,"&amp;")
                .replace(/</g,"&lt;")
                .replace(/>/g,"&gt;");

            return beforeGrave + "<code>" + escapeMagicChars(code, "\*_{}[]\\") + "</code>";
        });
    }

    /**
     * Replaces _italics_ *italics* and __bold__ **bold**
     * with em and strong tags.
     * @param  {string} text input markdown
     * @return {string}      converted string
     */
    function italicsAndBold(text) {
        return text
            .replace(bold, "<strong>$2</strong>")
            .replace(italic, "<em>$2</em>");
    }

    /**
     * Replaces auto links <http://url> with
     * <a> tags. Adds target=_blank on <a> tags.
     * @param  {string} text input markdown
     * @return {string}      converted string
     */
    function autoLinks(text) {
        return text.replace(autoLink, "<a href='$1' target='" + escapeMagicChars('_blank', '_') + "'>$1</a>");
    }

    /**
     * Replaces normal, non-reference style [links](http://url "optional title")
     * with <a> tags. Adds target=_blank on <a> tags.
     * @param  {string} text input markdown
     * @return {string}      converted string
     */
    function links(text) {
        return text.replace(inlineLink, function(wholeMatch, dontCare1, linkText, dontCare2, url, title) {
            var link = "<a href=\"" + escapeMagicChars(url, '"*_') + "\" target=\"" + escapeMagicChars("_blank", "_") + "\"";

            if (title) {
                title = title.replace(/"/g, "");
                title = escapeMagicChars(title, "*_");
                link += " title=\"" + title + "\"";
            }

            return link += ">" + linkText + "</a>";
        });
    }

    /**
     * Escapes the specified characters that are magic markdown characters
     * with our own escape sequence so that they aren't matched and converted.
     * #unescapeMagicChars should be called after all conversions are done.
     * @param  {string} text  text input html
     * @param  {string} chars magic markdown chars to escape
     * @return {string}       converted string
     */
    function escapeMagicChars(text, chars) {
        // Make sure backslashes are properly backslashed
        return text.replace(new RegExp("([" + chars.replace(/([\[\]\\])/g,"\\$1") + "])", "g"), escapeCallbackReplacer);
    }

    /**
     * Intended as the callback for a string replace matched with a regex.
     * Surrounds the charCode of the match with designated placeholders
     * (tilda doesn't have any special meaning in markdown).
     * @param  {string} wholeMatch The entire match; don't care
     * @param  {string} match1     The match we care about that's to
     * be replaced
     * @return {string}            replacement
     */
    function escapeCallbackReplacer(wholeMatch, match1) {
        return "~Z" + match1.charCodeAt(0) + "Z";
    }

    /**
     * Undoes our escape sequence added
     * via #escapeMagicChars
     * @param  {string} text input html
     * @return {string}      converted string
     */
    function unescapeMagicChars(text) {
        return text.replace(/~Z(\d+)Z/g, function(wholeMatch, match1) {
            return String.fromCharCode(parseInt(match1, 10));
        });
    }

    /**
     * Magic markdown chars aren't magic
     * within literal code blocks--back to
     * their mundane little lives.
     * @param  {string} text input html
     * @return {string}      converted string
     */
    function escapeMagicCharsWithinCode(text) {
        return text.replace(htmlTag, function(wholeMatch) {
            return escapeMagicChars(wholeMatch.replace(/(.)<\/?code>(?=.)/g,"$1`"), "\\`*_");
        });
    }

    /**
     * Magic markdown chars can be backslashed escaped.
     * Preserve those with our escape sequence.
     * @param  {string} text backslashes to be encoded
     * @return {string}      converted string
     */
    function encodeBackslashes(text) {
        return text
            .replace(/\\(\\)/g, escapeCallbackReplacer)
            .replace(/\\([`*_{}\[\]()>#+-.!])/g, escapeCallbackReplacer);
    }

    /**
     * Takes some markdown and produces html.
     * See comments up top for supported markdown subset.
     * @param {string} text markdown to convert
     * @return {string}     converted string
     */
    Y.MarkdownToHTML = function(text) {
        if (!text) return text;

        text = inlineCodeBlocks(text);

        text = escapeMagicCharsWithinCode(text);
        text = encodeBackslashes(text);

        text = links(text);
        text = autoLinks(text);
        text = italicsAndBold(text);
        text = unescapeMagicChars(text);

        return text;
    };
});
