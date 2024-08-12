<?php

namespace RightNow\Utils;

use RightNow\Api;

/**
 * Methods for dealing with text comparisons, manipulation, etc.
 */
final class Text extends \RightNow\Internal\Utils\Text{
    /**
     * Returns true if $haystack begins with $needle; false otherwise.<h1>Always remember to use ..</h1>
     * @param string $haystack String too look in
     * @param string $needle String to find
     * @return bool
     */
    public static function beginsWith($haystack, $needle)
    {
        if ($haystack == null) {
            return false;
        }
        return 0 === strncmp($needle, $haystack, strlen($needle));
    }

    /**
     * Returns true if $haystack begins with $needle case insensitive, false otherwise.
     * @param string $haystack String to look in
     * @param string $needle String to find
     * @return bool
     */
    public static function beginsWithCaseInsensitive($haystack, $needle)
    {
        if ($haystack == null) {
            return false;
        }
        return 0 === strncasecmp($needle, $haystack, strlen($needle));
    }

    /**
     * Returns true if $haystack ends with $needle; false otherwise.
     * @param string $haystack String to look in
     * @param string $needle String to find
     * @return bool
     */
    public static function endsWith($haystack, $needle)
    {
        return !is_null($haystack) ? (substr($haystack, -strlen($needle)) === $needle) : false;
    }

    /**
     * Indicates if $haystack contains $needle.
     * @param string $haystack String to look in
     * @param string $needle String to find
     * @return true if $haystack contains $needle; false otherwise.  Also false if $needle is empty.
     */
    public static function stringContains($haystack, $needle) {
        return (strlen($needle) !== 0) && (false !== strpos($haystack ? $haystack : '', $needle));
    }

    /**
     * Indicates if $haystack contains $needle regardless of case.
     * @param string $haystack String to look in
     * @param string $needle String to find
     * @return true if $haystack contains $needle regardless of case; false otherwise
     */
    public static function stringContainsCaseInsensitive($haystack, $needle) {
        if ($haystack == null) {
            return false;
        }
        return (strlen($needle) !== 0) && (false !== stripos($haystack, $needle));
    }

    /**
     * Returns the portion of $haystack that follows the first occurrence of $needle.
     * E.g.
     *
     *     getSubstringAfter('a/b/c/d', 'b/c') == '/d'
     *
     * @param string $haystack String to look in
     * @param string $needle String to find
     * @param string $default Value to return if $needle not in $haystack.
     * @return string|boolean String - the substring value or $default if $default is specified and
     * $haystack doesn't contain $needle;
     * Boolean - False is returned if $needle does not occur in $haystack (and no $default is specified) or if $needle is found but
     * is at the end of $haystack (i.e. no substring occurs after it)
     */
    public static function getSubstringAfter($haystack, $needle, $default = false) {
        $index = strpos($haystack ? $haystack : '', $needle);
        if ($index === false) {
            return $default;
        }
        return substr($haystack, $index + strlen($needle));
    }

    /**
     * Returns the portion of $haystack which precedes the first occurrence of $needle.
     * E.g.
     *
     *      getSubstringBefore('a/b/c/d', 'b/c') == 'a/'
     *
     * @param string $haystack String to look in
     * @param string $needle String to find
     * @param mixed $default Value to return if $needle not in $haystack.
     * @return mixed Substring value or $default if $haystack doesn't contain $needle
     */
    public static function getSubstringBefore($haystack, $needle, $default = false) {
        $index = strpos($haystack ? $haystack : '', $needle);
        if ($index === false) {
            return $default;
        }
        return substr($haystack, 0, $index);
    }

    /**
     * Returns the portion of $haystack beginning with the first occurrence of $needle.
     * E.g.
     *
     *      getSubstringStartingWith('a/b/c/d', 'b/c') == 'b/c/d'
     *
     * @param string $haystack String to look in
     * @param string $needle String to find
     * @param mixed $default Value to return if $needle not in $haystack.
     * @return mixed Substring value or $default if $haystack doesn't contain $needle
     */
    public static function getSubstringStartingWith($haystack, $needle, $default = false) {
        $index = !is_null($haystack) ? strpos($haystack, $needle) : false;
        if ($index === false) {
            return $default;
        }
        return substr($haystack, $index);
    }

    /**
     * Returns a portion of a string and properly supports multibyte text.
     *
     * @param string $fullString String to take a chunk out of
     * @param int $start Character index to start the substring
     * @param int $length Number of characters to pull into the substring. If not specified, the substring will
     * start at $start and run until the end of the string.
     * @return mixed Substring of original string or false if $start or $length values are invalid
     */
    public static function getMultibyteSubstring($fullString, $start, $length = null) {
        if ($start > self::getMultibyteStringLength($fullString) - 1 ||
            $length === 0 || $length === false) {
            return false;
        }

        $fullChars = self::getMultibyteCharacters($fullString);
        return implode('', array_slice($fullChars, $start, $length));
    }

    /**
     * Compares the length of strings including multibyte support. Returns -1 if $firstString is shorter than $secondString,
     * 1 if $firstString is longer than $secondString, and 0 if the two strings are the same length.
     * @param string $firstString The first string
     * @param string $secondString The second string
     * @return int Value representing which string is longer
     */
    public static function strlenCompare($firstString, $secondString)
    {
        $firstLength = Api::utf8_char_len($firstString);
        $secondLength = Api::utf8_char_len($secondString);
        if($firstLength == $secondLength)
            return 0;
        if($firstLength < $secondLength)
            return -1;
        return 1;
    }

    /**
     * Function to find the number of characters in a string. This is what
     * should be used instead of regular strlen for character streams. If
     * you want the number of bytes in a string, feel free to use strlen.
     * Will throw an exception if given a string with invalid multibyte
     * characters. Call utf8_cleanse() to fix the string and try again.
     *
     * @param string $buffer The text whose length to measure
     * @return int The length, in characters, of the string
     * @throws \Exception If value provided is not a valid multibyte string
     */
    public static function getMultibyteStringLength($buffer)
    {
        $length = Api::utf8_char_len($buffer);
        if ($length == -1)
            throw new \Exception('Invalid multibyte string');
        return $length;
    }

    /**
     * Given a string split it into an array of its UTF-8 characters. This
     * function should be used anytime we iterate over the characters of a UTF-8
     * string.
     * @param string $buffer The text to break apart
     * @return array The list of characters.
     */
    public static function getMultibyteCharacters($buffer) {
        return preg_split('//u', $buffer, -1, PREG_SPLIT_NO_EMPTY);
    }


    /**
     * Converts the given string into a "sluggable" string that's suitable for using in a URL.
     * @param string $buffer The text to turn into a slug
     * @return string Slug text
     */
    public static function slugify($buffer) {
        $buffer = trim(strtolower($buffer)); // lowercase, strip leading and trailing whitespace
        $buffer = str_replace(array('\'', '"', '`'), '', $buffer); // remove quotes
        $buffer = preg_replace('/[\s~!@#$%^&*()_+={}\[\]:;<>,.?\/\\|]/', '-', $buffer); // replace non alphanumeric ASCII characters with hyphens
        $buffer = preg_replace('/\-+/', '-', $buffer); // change multiple hyphens to single
        return $buffer;
    }

    /**
     * Returns $haystack without trailing $needle.
     * @param string $haystack String to look in
     * @param string $needle String to find
     * @return string Returns $haystack without trailing $needle. If $haystack doesn't end with $needle, $haystack is returned.
     */
    public static function removeSuffixIfExists($haystack, $needle)
    {
        return (self::endsWith($haystack, $needle)) ? substr($haystack, 0, -strlen($needle)) : $haystack;
    }

    /**
     * Returns $path with all trailing slashes removed.  If $path consists only of
     * slashes, a single slash will be returned.
     * @param string $path String to remove slashes from
     * @return string $path with all trailing slashes removed.
     */
    public static function removeTrailingSlash($path)
    {
        $i = $strlen = strlen($path);
        while (--$i > 0)
        {
            if ($path[$i] !== '/')
            {
                if ($strlen === $i + 1)
                {
                    return $path;
                }
                else
                {
                    return substr($path, 0, $i + 1);
                }
            }
        }
        return $path[0];
    }

    /**
     * Correctly escapes a string so that it can
     * be property used within JavaScript.
     *
     * @param string $string The value to escape
     * @return string The escaped value.
     */
    public static function escapeStringForJavaScript($string)
    {
        $specialJSChars = array("\r\n" => '\n',
                                "\r"   => '\n',
                                "\n"   => '\n',
                                '\\'   => '\\\\',
                                "'"    => '\\\'',
                                '"'    => '\\"');
        return(strtr($string, $specialJSChars));
    }

    /**
     * Unescapes HTML quote entities back into the literal double quote character
     *
     * @param string $content The content to unescape
     * @return string The content with quote characters unescaped
     */
    public static function unescapeQuotes($content)
    {
        return str_ireplace('&quot;', '"', $content);
    }

    /**
     * Utility method to escape string using PHPs htmlspecialchars method. Escapes
     * all quotes and also forces UTF-8 encoding. If value passed in is not a string then
     * it will just be returned unmodified.
     * @param string $string String to escape
     * @param bool $doubleEncode Whether to encode existing html entities
     * @return mixed Escaped string or original unmodified value if not a string.
     */
    public static function escapeHtml($string, $doubleEncode = true){
        if(!is_string($string)){
            return $string;
        }
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8', $doubleEncode);
    }

    /**
     * Utility method to unescape string using PHPs html_entity_decode method. Unescapes
     * all quotes and also forces UTF-8 encoding. If value passed in is not a string then
     * it will just be returned unmodified.
     * @param string $string String to unescape
     * @return mixed Unescaped string or original unmodified value if not a string.
     */
    public static function unescapeHtml($string) {
        if(!is_string($string)){
            return $string;
        }
        return html_entity_decode($string, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Like php's join() or implode() functions, but excludes null and empty string items.
     * @param string $glue The string to be put between the elements of $array
     * @param array $array The items to be joined.
     * @return string A string containing a string representation of all the array elements in the same order,
     * with the glue string between each non-null, non-empty-string element.
     */
    public static function joinOmittingBlanks($glue, array $array)
    {
        return implode($glue, array_filter($array, 'strlen'));
    }

    /**
     * Strips javascript/html tags, truncates text to the wordbreak
     * before the specified length, and appends an ellipsis.
     * Postcondition: Tags will be removed from the string even
     * if it's length is smaller than the specified truncation length.
     * The string may be slightly longer than the specified length because
     * of the addition of the ellipsis.
     *
     * This function is UTF-8 safe: the truncation length is in characters, NOT bytes.
     *
     * @param string $data The text to truncate
     * @param int $length The number of characters to truncate to
     * @param bool $addEllipsis Whether to add an ellipsis after truncating the text
     * @param int $maxWordBreakTrunc The number of characters to limit the additional truncation done to the truncated string so it will be truncated at a
     *                               word break. If a space is not found within the number of characters passed in this parameter when searching backwards from the
     *                               end of the truncated string no further truncation of the string will occur. If null the search for a word break (space) is not limited. Default is null.
     * @param bool $stripHtml Whether or not to remove HTML formatting tags from the data
     * @return string The input string in tag-stripped and truncated form
     * @throws \Exception If Data provided is not a valid UTF-8 string
     */
    public static function truncateText($data, $length, $addEllipsis = true, $maxWordBreakTrunc = null, $stripHtml = true)
    {
        //Strip javascript and html tags so that the string displays correctly,
        //but leave single space to prevent concatenation of content.
        $htmlRegex = array('/<script[^>]*?>.*?<\/script>/si',                 // JavaScript
                           '/<+\s*\/*\s*([A-Z][A-Z0-9]*)\b[^>]*$/si',         // incomplete, trailing HTML
                           '/<+\s*\/*\s*([A-Z][A-Z0-9]*)\b[^>]*\/*\s*>+/si',  // HTML tags
                           '/\s+/',                                           // extra whitespace
                           '/<!--.*?-->/s',                                   // HTML comments
                           '/<!--.*$/s',                                      // incomplete HTML comments
                    );

        if ($stripHtml) {
            $data = preg_replace($htmlRegex, ' ', $data);
        }
        $data = ltrim($data);

        try
        {
            $dataLength = self::getMultibyteStringLength($data);
        }
        catch (Exception $e)
        {
            // invalid UTF-8. try cleansing first
            $cleansedData = Api::utf8_cleanse($data);

            try
            {
                $dataLength = self::getMultibyteStringLength($cleansedData);
                $data = $cleansedData;
            }
            catch (\Exception $e)
            {
                // this string is officially messed up. Bail with an exception
                throw new \Exception("Error in truncateText (data = $data): [$e]");
            }
        }

        // We have the correct string and its length. See if truncation is necessary
        if($dataLength > $length)
        {
            $data = Api::utf8_trunc_nchars($data, $length);

            // this might have cut a word in half. Find last space and trim to there.
            $wordBreak = false;
            if($maxWordBreakTrunc === null || !is_numeric($maxWordBreakTrunc) || ($maxWordBreakTrunc > 0))
            {
                $minSearch = 0;
                if (is_numeric($maxWordBreakTrunc))
                    $minSearch = (strlen($data) > $maxWordBreakTrunc) ? strlen($data) - $maxWordBreakTrunc : 0;
                for($i = strlen($data); $i >= $minSearch; $i--)
                {
                    if(isset($data[$i]) && ctype_space($data[$i]))
                    {
                        $wordBreak = $i;
                        break;
                    }
                }
            }

            if($wordBreak !== false)
            {
                $data = substr($data, 0, $wordBreak);
                $data = rtrim($data, ', .:'); // just in case there's a lot of punctuation
            }
            if($addEllipsis)
                $data .= Config::getMessage(ELLIPSIS_MSG);
        }

        return $data;
    }

    /**
     * Calls the print_text2str which will expand the <rn:answer_xref ..> and <rn:answer_section /> tags
     *
     * @param string $buffer The text to expand
     * @param bool $isAdmin True if this is requested from the admin console
     * @param bool $showConditionalSections True if you want to show answer conditional sections
     * @return string Modified $buffer with completed expansions
     */
    public static function expandAnswerTags($buffer, $isAdmin = false, $showConditionalSections = false)
    {
        if(!$buffer)
            return $buffer;

        if($isAdmin)
        {
            if($showConditionalSections)
                $buffer = Api::print_text2str(rtrim($buffer), OPT_VAR_EXPAND | OPT_REF_TO_URL | OPT_COND_SECT_SHOW);
            else
                $buffer = Api::print_text2str(rtrim($buffer), OPT_VAR_EXPAND | OPT_REF_TO_URL | OPT_COND_SECT_FILTER);
        }
        else
        {
            $buffer = Api::print_text2str(rtrim($buffer), OPT_VAR_EXPAND | OPT_REF_TO_URL | OPT_COND_SECT_FILTER);
        }

        //replace #PATH_INFO#?p_faqid=XXX#STD_PARMS#
        //with    http://OE_WEB_SERVER/app/CP_ANSWERS_DETAIL_URL/a_id/XXX

        $replacement = Url::getShortEufAppUrl('sameAsCurrentPage', Config::getConfig(CP_ANSWERS_DETAIL_URL) . '/a_id/$1' . Url::sessionParameter());
        $buffer = preg_replace('@#PATH_INFO#\?p_faqid=(\d+)#STD_PARMS#@', $replacement, $buffer);

        return $buffer;
    }

    /**
     * Converts the given integer (representing byte size)
     * into a readable file-size string.
     * @param int $size An integer byte size value
     * @return string A string containing the truncated file size value along with the B / KB / MB label
     */
    public static function getReadableFileSize($size)
    {
        //expected file sizes
        $label = array(Config::getMessage(BYTES_LBL), Config::getMessage(KB_LBL), Config::getMessage(MB_LBL));
        $remainder = $i = 0;
        while ($size >= 1024 && $i < 8) {
            $remainder = (($size & 0x3ff) + $remainder) / 1024;
            $size = $size >> 10;
            $i++;
        }
        return self::getLocaleTruncatedValue($size + $remainder, 2, true, true) . ' ' . $label[$i];
    }

    /**
     * Truncates a value with respect to locale or LANG_DIR (typically for display to an end user)
     * @param int $value Value to round
     * @param int $precision Precision (number of zeros) to round
     * @param bool $hideThousandsSeperator When true, hides the thousands seperator
     * @param bool $noDecimalWhenWhole When true, removes decimal when number is whole (eg, 2.00 becomes 2)
     * @param string $locale Optional locale to use ('fr_FR', for example)
     * @return string A string containing the truncated value with locale settings applied
     */
    public static function getLocaleTruncatedValue($value, $precision = 2, $hideThousandsSeperator = false, $noDecimalWhenWhole = false, $locale = null)
    {
        $currentLocale = setlocale(LC_NUMERIC, 0);
        setlocale(LC_NUMERIC, ($locale) ?: LANG_DIR);
        $locale = localeconv();
        $truncatedValueWithLocale = (string) number_format($value, $precision, $locale['decimal_point'], $locale['thousands_sep']);

        if($hideThousandsSeperator)
            $truncatedValueWithLocale = str_replace($locale['thousands_sep'], '', $truncatedValueWithLocale);

        if($noDecimalWhenWhole && self::endsWith($truncatedValueWithLocale, $locale['decimal_point'] . '00'))
            $truncatedValueWithLocale = substr($truncatedValueWithLocale, 0, -3);

        setlocale(LC_NUMERIC, $currentLocale);
        return $truncatedValueWithLocale;
    }

    /**
    * Returns the RFC1766-specified language code of the interface.
    * @return string
    */
    public static function getLanguageCode()
    {
        return str_replace('_', '-', LANG_DIR);
    }

    /**
     * Emphasizes the text passed in by surrounding it with HTML tags. The text to
     * match against can be sent in, or the function will use a URL parameter. Also,
     * you can change the HTML wrapper on the fly. Stemming and word normalization are
     * performed to align with how the search engine would match words.
     *
     *  e.g. if the query was 'run', then 'running', 'runs', 'runner', etc... would also
     *  be matched and highlighted
     *
     *
     * @param string $text The text to add tags to
     * @param array $options Contains options for doing highlighting. All elements (and the array itself) are optional.
     *                       $options['query'] The query text that will be matched. If not supplied, will not be changed. Initialized to 'kw' URL parameter.
     *                       $options['wrapperText'] sprintf-style text that will wrap any matched word in $text. If not supplied, will not be changed. Initialized to "<em class='rn_Highlight'>%s</em>", where the matched word is substituted for %s.
     * @return string Text with added emphasis markup
     */
    public static function emphasizeText($text, array $options = array())
    {
        static $initial = true;
        static $defaultTextWrapper = "<em class='rn_Highlight'>%s</em>";

        //Parse and set the query. If none is supplied, and this is the
        //first time through the function, use the URL parameter as a
        //good guess.
        if(isset($options['query']))
        {
            self::emphasizeTextSetQuery($options['query']);
        }
        else if($initial)
        {
            self::emphasizeTextSetQuery(Url::getParameter('kw'));
        }

        //Set the wrapper text. If none is supplied, and this is the
        //first time through the function, use the default wrapper
        //(defined in this function).
        if(isset($options['wrapperText']))
        {
            Api::set_highlight_wrapper($options['wrapperText']);
        }
        else if($initial)
        {
            Api::set_highlight_wrapper($defaultTextWrapper);
        }

        // Mark this function as initialized.
        $initial = false;

        // Do the actual highlighting and return
        return Api::print_text2str(trim($text), OPT_HIGHLIGHT_SEARCH);
    }


    /**
     * A helper function that will set the query text that is to be matched
     * by emphasizeText. This is fairly expensive, so should be done a minimum
     * number of times (read: once per execution). Just to be safe, this function
     * will cache the query sent to it, so repeated calls with the same query
     * will have no effect. Empty queries are ignored, as well.
     *
     * @param string $query The query text to be matched
     * @return void
     */
    private static function emphasizeTextSetQuery($query)
    {
        static $cachedQuery;

        if ($query && strcmp($query, isset($cachedQuery) ? $cachedQuery : '') != 0)
        {
            $highlightTerms = '';

            // store this for next time
            $cachedQuery = $query;

            // TODO: add flag to switch to incident stopwords
            // cacheKey has two parameters which are currently 0
            // here.  The first is whether the query is complex and
            // the second is incident stopwords.  If you do something
            // with incident stopwords you'll need to change how the
            // key is built.
            $cacheKey = "rnkl_query_parse0-0-" . $query;
            if (null === ($queryParseResults = Framework::checkCache($cacheKey)))
            {
                $queryParseResults = Api::rnkl_query_parse(0x0004, $query, 0);
                Framework::setCache($cacheKey, $queryParseResults);
            }

            // from lang.h:
            // TOKENIZE_SEARCHFORM           0x00000001
            // TOKENIZE_EXPANDCONTRACTIONS   0x00000002
            // TOKENIZE_ALLOWSTARS           0x00000004
            // TOKENIZE_EXPANDABBREVIATIONS  0x00000008
            // TOKENIZE_PERFORMSTEMMING      0x00000010
            // TOKENIZE_ANS_STOPWORDS        0x00000020
            // TOKENIZE_INC_STOPWORDS        0x00000040

            // everything but incident stopwords
            // TODO: add flag to switch to incident stopwords

            //Add words not found in dictionary to highlight list
            $stringTokens = $queryParseResults['dym'];
            if(strlen($queryParseResults['nodict']))
                $stringTokens .= ' ' . str_replace(',', '', $queryParseResults['nodict']);
            $tokens = Api::lang_tokenize($stringTokens, 0x1F, 500);

            if (is_array($tokens))
            {
                foreach ($tokens as $token)
                {
                    if (isset($token->type) && $token->type > 0 &&
                        $token->type !== 5 && // TOKEN_LONGTEXT
                        $token->type !== 7 && // TOKEN_NEWLINE
                        $token->type !== 12 && // TOKEN_SPACE
                        $token->type !== 14 && // TOKEN_STOPWORD
                        $token->type !== 16 && // TOKEN_SYMBOL
                        $token->type !== 21 && // TOKEN_XML
                        $token->type !== 22 && // TOKEN_XML_BLOCK
                        $token->type !== 11 && // TOKEN_REPEATEDCHAR
                        $token->type !== 10)   // TOKEN_PUNCTUATION
                    {
                        $highlightTerms .= '|' . $token->search_form;
                    }
                }
            }

            $highlightTerms = trim($highlightTerms, '|');
            $highlightTerms = preg_replace('/\|+/', '|', $highlightTerms);

            Api::set_highlight_terms($highlightTerms);
        }
    }


    /**
     * Accepts the entire keyword search phrase and splits it up into individual terms before
     * calling highlightText on each term. Orders terms longest to shortest
     * only highlights terms longer than 4 characters.
     * @param string $text The text to highlight
     * @param string $searchTermPhrase The term or terms to highlight within the text
     * @param int $minimumSearchTermLength The minimum value the search term needs to be to be highlighted
     * @return string The text with span highlight tags
     */
    public static function highlightTextHelper($text, $searchTermPhrase, $minimumSearchTermLength)
    {
        //order by descending length
        if(strlen($searchTermPhrase))
        {
            $searchTermArray = preg_split('@[-+()\[\] {}<>\'"*\@]+@', trim($searchTermPhrase), -1, PREG_SPLIT_NO_EMPTY);
            foreach($searchTermArray as $index => $searchTerm)
            {
                if(Api::utf8_char_len($searchTerm) < $minimumSearchTermLength)
                    unset($searchTermArray[$index]);
            }
            if(count($searchTermArray))
            {
                usort($searchTermArray, "\\RightNow\\Utils\\Text::strlenCompare");
                $searchTermArray = array_reverse($searchTermArray);
                $regex = '@(<[^>]+>)|(&[^;]+;)|(' . implode('|', array_map('preg_quote', $searchTermArray)) . ')@i';
                return preg_replace_callback($regex, function($matches){
                    if(count($matches) === 4)
                        return '<span class="highlight">' . $matches[3] . '</span>';
                    return $matches[0];
                }, $text);
            }
        }
        return $text;
    }

    /**
     * Takes CSS content and minifies it, stripping out whitespace
     * and comments
     * @param string $css The CSS you wish to minify
     * @return string The minified CSS
     */
    public static function minifyCss($css)
    {
        $css = preg_replace('@\s+@', ' ', $css);
        $css = preg_replace('@/[*].*?[*]/@', '', $css);
        $css = preg_replace('@\s*;\s*@', ';', $css);
        $css = preg_replace('@\s*{\s*@', '{', $css);
        $css = preg_replace('@\s*}\s*@', "}\n", $css);
        return trim($css);
    }

    /**
     * Determines if the entire string content matches DE_VALID_EMAIL_PATTERN and the string length is less than 81 characters.
     * If DE_VALID_EMAIL_PATTERN contains an empty or invalid regular expression, will return true if the string is of a valid length.
     * @param string $emailAddress To be matched against the DE_VALID_EMAIL_PATTERN email pattern and checked for length validity.
     * @return bool True if $emailAddress matches DE_VALID_EMAIL_PATTERN from beginning to end and is less than 81 characters;
     *          false if $emailAddress doesn't match, is empty, or the length is more than 80 characters.
     */
    public static function isValidEmailAddress($emailAddress) {
        $validLength = 255;

        if (!$emailAddress || self::getMultibyteStringLength(Api::utf8_cleanse($emailAddress)) > $validLength) {
            return false;
        }

        static $pattern;
        if (!isset($pattern)) {
            /*
             * 1. Add starting and delimiter characters as required by PCRE.  Escape any of those in the pattern.
             *    I'm using semi-colon because it doesn't appear in the default DE_VALID_EMAIL_PATTERN.
             * 2. Trim the pattern because somebody will inevitably add whitespace at the front or back of the config.
             * 3. Add beginning and ending anchors to require the pattern to match the entire input string.
             *    These may be duplicated with anchors in the config's value without effect.
             * 4. Translate the \w charater class, i.e. "word" characters, to match only ASCII "word" characters.
             *    The \w class is locale dependent, meaning when we're running in a German locale, umlauts would
             *    magically become valid in email addresses.  PHP itself had a bug on exactly the same thing: http://bugs.php.net/47598
             *    Say it with me now, kids: locale dependent character classes without a culture insensitive equivalent are a bad idea.
            */
            $pattern = trim(Config::getConfig(DE_VALID_EMAIL_PATTERN));
            if ($pattern) {
                $pattern = ";^" . str_replace('\w', '0-9A-Za-z_', str_replace(';', '\\;', $pattern)) . "$;";
            }
        }

        $apiEmailAddressCheck = function($emailAddress) {
            $pattern = ";^" . str_replace('\w', '0-9A-Za-z_', str_replace(';', '\\;', API_VALIDATION_REGEX_EMAIL)) . "$;";
            $result = @preg_match($pattern, $emailAddress);
            return $result !== false && $result !== 0;
        };

        // If a customer whacks their DE_VALID_EMAIL_PATTERN, we let everything through that the API will accept.
        if (!$pattern) {
            return $apiEmailAddressCheck($emailAddress);
        }
        $result = @preg_match($pattern, $emailAddress);
        // If preg_match returns false, meaning the regexp was invalid, we indicate a valid email address so the
        // site doesn't totally go down in flames if somebody makes a mistake.
        if ($result === false) {
            if(!IS_OPTIMIZED) {
                Framework::addErrorToPageAndHeader(Config::getMessage(VAL_DE_VALID_EMAIL_PATTERN_VALID_MSG));
            }
            return true;
        }
        else if ($result === 0) {
            return false;
        }
        return true;
    }

    /**
     * Determines if the specified string appears to be a valid Url.
     * @param string $url Matched against a Url regular expression
     * @return bool True if the string appears to be a valid URL or false if the string fails validation
     */
    public static function isValidUrl($url) {
        if(!is_string($url)){
            return false;
        }
        static $protocolCheck = '^[a-z][a-z0-9.+-]+://';
        static $printableCharacters = '[^\\x00-\\x20\\x7F]';
        static $printableCharactersAndNotBracketsOrSlash = '[^\\x00-\\x20\\x7F<>/]';
        static $urlRegex;
        if(!$urlRegex){
            $urlRegex = "@{$protocolCheck}" .                       //Require a protocol. We'll prepend a protocol before running this if one doesn't exist
                '(?:(?:' .
                "{$printableCharactersAndNotBracketsOrSlash}+" .    //Hostname should printable characters, except for <, >, and /
                '\.(?:' .                                           //After hostname, there should be at least period. We don't support things like 'localhost'
                "{$printableCharactersAndNotBracketsOrSlash}+" .    //This captures the rest of the hostname, including any number of additional sub domains.
                ')|\[[0-9a-f:]+\])' .                               //As another option, we can omit the prior three rules, and support IPv6 addresses which contain colons and hex values
                '(?::[0-9]+)?)' .                                   //Optional port. Must begin with a colon and have 1-n digits
                "{$printableCharacters}*$@i";                       //The rest of the path can be any printable character or nothing. And make this whole thing case insensitive
        }

        if(!preg_match("@{$protocolCheck}@i", $url)){
            $url = "http://{$url}";
        }

        return @preg_match($urlRegex, $url) === 1;
    }

    /**
     * Determines if the specified string appears to be a valid date (year, month, and day are a valid combination) in the "y-m-d h:m:s" format.
     * @param string $dateString The date as a string
     * @return bool|null Returns:
     * null if the string is not in a "y-m-d h:m:s" format,
     * true if the string is in the expected format and appears to be a valid date (year, month, and day are a valid combination),
     * false if the string is in the expected format and the year, month, and day combination is invalid.
     */
    public static function isValidDate($dateString) {
        if (!is_string($dateString) || !preg_match('@^(\d+)-(\d+)-(\d+) (\d+):(\d+):(\d+)$@', $dateString, $matches))
            return;
        return checkdate((int)$matches[2], (int)$matches[3], (int)$matches[1]);
    }

    /**
     * Validate $value matches $mask.
     *
     * Mask symbols (first and second characters below are paired; e.g ML = upper or lowercase letter):
     *   First character
     *   F - Formatting character (space, hyphen, parentheses)
     *   U - Uppercase letter
     *   L - Lowercase letter
     *   M - Ignore case (lowercase, uppercase, and numbers are acceptable)
     *
     *   Second character
     *   # -  Numeric (number only)
     *   A -  Alphanumeric (either letter or number)
     *   L -  Alpha (letter only)
     *   C -  Alphanumeric or formatting character
     *
     * @param mixed $value The value to validate against the specified mask.
     * @param string $mask The mask against which to validate the value.
     * @return array An array of error messages, or an empty array if validation was successful.
     */
    public static function validateInputMask($value, $mask) {
        try {
            $mapping = self::mapCharactersToMask($value, $mask, false);
        }
        catch (\Exception $e) {
            // $mask not a string having an even number of characters.
            return array($e->getMessage());
        }

        static $validSymbols;
        $validSymbols ?: $validSymbols = array(
            'UA' => array('[A-Z0-9\p{Lu}]', Config::getMessage(CHAR_PCT_S_POSITION_PCT_D_UPPERCASE_MSG)),
            'UL' => array('[A-Z\p{Lu}]', Config::getMessage(CHR_PCT_S_POSITION_PCT_D_UPPERCASE_MSG)),
            'UC' => array('[^a-z\p{Ll}]', Config::getMessage(CHAR_PCT_S_POSITION_PCT_D_SUPP_MSG)),
            'LA' => array('[a-z0-9\p{Ll}]', Config::getMessage(CHR_PCT_S_POSITION_PCT_D_LOWERCASE_MSG)),
            'LL' => array('[a-z\p{Ll}]', Config::getMessage(CHAR_PCT_S_POSITION_PCT_D_LOWERCASE_MSG)),
            'LC' => array('[^A-Z\p{Lu}]', Config::getMessage(CHAR_PCT_S_POSITION_PCT_D_SUPP_MSG)),
            'MA' => array('[A-Za-z0-9\p{L}]', Config::getMessage(CHR_PCT_S_POSITION_PCT_D_LETTER_MSG)),
            'ML' => array('[a-zA-Z\p{L}]', Config::getMessage(CHAR_PCT_S_POSITION_PCT_D_LETTER_MSG)),
            'MC' => array('.+', Config::getMessage(CHAR_PCT_S_POSITION_PCT_D_SUPP_MSG)),
        );

        $errors = array();
        $position = 0;
        foreach($mapping as $map) {
            list($character, $symbol) = $map;
            $position++;
            if ($symbol === null) {
                $errors[] = Config::getMessage(THE_INPUT_IS_TOO_LONG_MSG);
                break;
            }

            if ($symbol[0] === 'F') {
                if ($character !== $symbol[1]) {
                    $errors[] = sprintf(Config::getMessage(CHAR_PCT_S_POSITION_PCT_D_MATCH_LBL), $character, $position, substr($symbol, 1));
                }
            }
            else if ($symbol[1] === '#') {
                if (!@preg_match('/\d/', $character)) {
                    $errors[] = sprintf(Config::getMessage(CHARACTER_PCT_S_POSITION_PCT_D_MSG), $character, $position);
                }
            }
            else if ($validSymbol = $validSymbols[$symbol]) {
                if (!@preg_match("/{$validSymbol[0]}/u", $character)) {
                    $errors[] = sprintf($validSymbol[1], $character, $position);
                }
            }
            else {
                $errors[] = sprintf(Config::getMessage(REC_MASK_SYMBOL_PCT_S_COLON_LBL), $symbol);
            }

            if ($character === null) {
                $errors[] = Config::getMessage(THE_INPUT_IS_TOO_SHORT_MSG);
                break;
            }
        }
        return $errors;
    }

    /**
     * Remove punctuation from $value according to $mask.
     * @param mixed $value The value upon which punctuation will be stripped.
     * @param string $mask The mask specifying the punctuation characters.
     * @return string Value stripped of punctuation specified by mask.
     * @throws \Exception if $value and/or $mask invalid. Use validateInputMask() if verification desired.
     */
    public static function stripInputMask($value, $mask) {
        return array_reduce(self::mapCharactersToMask($value, $mask), function($stripped, $map) {
            return ($map[1][0] === 'F') ? $stripped : $stripped . $map[0];
        });
    }

    /**
     * Return a string used to describe input indicated by $mask.
     * Example: '(###) ###-####' describes $mask: 'F(M#M#M#F)F M#M#M#F-M#M#M#M#'
     * @param string $mask The mask specifying the expected format.
     * @return string The mask string
     */
    public static function getSimpleMaskString($mask) {
        $maskString = '';
        foreach(self::getMaskPairs($mask) as $symbol) {
            if ($symbol[0] === 'F')
                $maskString .= $symbol[1];
            else if ($symbol[1] === '#')
                $maskString .= '#';
            else if ($symbol[0] === 'M' || $symbol[1] === 'A' || $symbol[1] === 'C')
                $maskString .= '@';
            else if ($symbol[0] === 'U')
                $maskString .= 'A';
            else if ($symbol[0] === 'L')
                $maskString .= 'a';
        }
        return $maskString;
    }

    /**
     * Given a sequence of comma-separated values, returns the trailing portion of the string after the last occurrence of a comma.
     * @param string $input Input string
     * @return string The trailing portion of the string after the last comma or $input untouched if no occurrence of a comma appears
     */
    public static function extractCommaSeparatedID($input) {
        return (($positionOfLastComma = strrpos($input ? $input : '', ',')) !== false) ? substr($input, ++$positionOfLastComma) ?: '' : $input;
    }

    /**
     * Calls Text::generateRandomString() if a user is logged in on https.
     * @return string Some random string
     */
    public static function getRandomStringOnHttpsLogin() {
        if((Config::getConfig(SEC_END_USER_HTTPS) || Url::isRequestHttps()) && Framework::isLoggedIn()) {
            return '<!--' . self::generateRandomString() . '-->';
        }
    }

    /**
     * Generates a random string
     * @param integer $length The length of string to output. If no length is provided, it'll be chosen randomly between 300 and 1000 characters
     * @return string Some random string
    */
    public static function generateRandomString($length = null) {
        $codes = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*()";
        $maxLength = strlen($codes) - 1;
        $randomString = '';

        for ($length = !isset($length) ? mt_rand(300, 1000) : $length; $length > 0; $length--) {
            $randomString .= $codes[mt_rand(0, $maxLength)];
        }

        return $randomString;
    }

    /**
     * Validates the URL date range values and converts them to timestamp
     *
     * @param string $urlDateRange The value of date range URL parameter
     * @param string $dateFormat The format of the date value
     * @param string $delimiter The delimiter used to seperate the to and from
     * @param boolean $toTimeStamp Flag to indicate whether to convert dates as timestamp
     * @param string $maxInterval Maximum interval in PHP date string format
     * @return string|null String of from and to dates seperated by delimiter
     */
    public static function validateDateRange ($urlDateRange, $dateFormat = 'm/d/Y', $delimiter = '|', $toTimeStamp = false, $maxInterval = null) {
        $delimiterIndex = strpos($urlDateRange, $delimiter);
        if ($delimiterIndex === -1) {
            return null;
        }
        $urlFromDate = substr($urlDateRange, 0, $delimiterIndex);
        $urlToDate = substr($urlDateRange, $delimiterIndex + 1);
        $fromDate = \DateTime::createFromFormat($dateFormat, $urlFromDate);
        $toDate = \DateTime::createFromFormat($dateFormat, $urlToDate);
        if (!($fromDate && $toDate) || $fromDate > $toDate){
            return null;
        }
        if($maxInterval){
            $dateInterval = \DateInterval::createFromDateString($maxInterval);
            if(!$dateInterval){
                return null;
            }
            $fromDateClone = clone $fromDate;
            $maxToDate = $fromDateClone->add($dateInterval);
            $interval = $maxToDate->diff($toDate);
            if($interval->invert === 0 && $interval->days > 0){
                return null;
            }
        }
        $beginOfToDate = strtotime("midnight", $toDate->format('U'));
        $endOfToDate = strtotime("tomorrow", $beginOfToDate);
        return $toTimeStamp ? (strtotime("midnight", $fromDate->format('U')) . $delimiter . $endOfToDate) : $urlDateRange;
    }
    
    /**
     * Returns the string representation of a date format config.
     * @return array Array of date format strings
     */
    public static function getDateFormatFromDateOrderConfig() {
        static $allDateFormats;
        $allDateFormats ?: $allDateFormats = array(0 => array("long" => "mm/dd/yyyy", "short" => "m/d/Y", "label" => "mm/dd/yyyy", "dayOrder" => 1, "monthOrder" => 0, "yearOrder" => 2),
                    1 => array("long" => "yyyy/mm/dd", "short" => "Y/m/d", "label" => "yyyy/mm/dd", "dayOrder" => 2, "monthOrder" => 1, "yearOrder" => 0),
                    2 => array("long" => "dd/mm/yyyy", "short" => "d/m/Y", "label" => "dd/mm/yyyy", "dayOrder" => 0, "monthOrder" => 1, "yearOrder" => 2));
        return $allDateFormats[Config::getConfig(DTF_INPUT_DATE_ORDER)];
    }
    
    /**
     * Returns the localized labels of date units
     * @return array Array of date units and localized labels
     */
    public static function getDateUnitLabels() {
        static $dateUnitLabels;
        $dateUnitLabels ?: $dateUnitLabels = array("days" => Config::getMessage(DAYS_LWR_LBL),
                    "day" => Config::getMessage(DAY_LWR_LBL),
                    "month" => Config::getMessage(MONTH_LWR_LBL),
                    "months" => Config::getMessage(MONTHS_LWR_LBL),
                    "year" => Config::getMessage(YEAR_LWR_LBL),
                    "years" => Config::getMessage(YEARS_LWR_LBL),
                    "hour" => Config::getMessage(HOUR_LWR_LBL),
                    "hours" => Config::getMessage(HOURS_LWR_LBL),
                    "minute" => Config::getMessage(MINUTE_LC_LBL),
                    "minutes" => Config::getMessage(MINUTES_LWR_LBL));
        return $dateUnitLabels;
    }

    /**
     * Adding C as locale to bypass issue with international languages.
     * 
     * @param string $textString The format of the string value
     * @return string - Lower case string.
     */
    public static function cStrToLower($textString){
        $currentLocale = setlocale(LC_CTYPE, 0); 
        setlocale(LC_CTYPE, "C");
        $lowerString = strtolower($textString);
        setlocale(LC_CTYPE,  $currentLocale);
        return $lowerString;
    }

}
