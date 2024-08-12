<?php
/**
 * CodeIgniter
 *
 * An open source application development framework for PHP 4.3.2 or newer
 *
 * @package        CodeIgniter
 * @author        Rick Ellis
 * @copyright    Copyright (c) 2006, EllisLab, Inc.
 * @license        http://www.codeignitor.com/user_guide/license.html
 * @link        http://www.codeigniter.com
 * @since        Version 1.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * Input Class
 *
 * Pre-processes global input data for security
 *
 * @package        CodeIgniter
 * @subpackage    Libraries
 * @category    Input
 * @author        Rick Ellis
 * @link        http://www.codeigniter.com/user_guide/libraries/input.html
 */
class CI_Input {
    public $use_xss_clean        = FALSE;
    public $ip_address            = FALSE;
    public $user_agent            = FALSE;
    public $allow_get_array    = FALSE;

    /**
     * Constructor
     *
     * Sets whether to globally enable the XSS processing
     * and whether to allow the $_GET array
     *
     * @access    public
     */
    function __construct()
    {
        log_message('debug', "Input Class Initialized");

        $CFG =& load_class('Config');
        $this->use_xss_clean    = ($CFG->item('global_xss_filtering') === TRUE) ? TRUE : FALSE;
        $this->allow_get_array    = ($CFG->item('enable_query_strings') === TRUE) ? TRUE : FALSE;
    }

    // --------------------------------------------------------------------

    // --------------------------------------------------------------------

    /**
     * Clean Input Data
     *
     * This is a helper function. It escapes data and
     * standardizes newline characters to \n
     *
     * @access    private
     * @param    string
     * @return string
     */
    function _clean_input_data($str)
    {
        if (is_array($str))
        {
            $new_array = array();
            foreach ($str as $key => $val)
            {
                $new_array[$this->_clean_input_keys($key)] = $this->_clean_input_data($val);
            }

            return $new_array;
        }

        // We strip slashes if magic quotes is on to keep things consistent
        if (get_magic_quotes_gpc())
        {
            $str = stripslashes($str);
        }

        // Should we filter the input data?
        if ($this->use_xss_clean === TRUE)
        {
            $str = $this->xss_clean($str);
        }

        // Standardize newlines
        return preg_replace("/\015\012|\015|\012/", "\n", $str);
    }

    // --------------------------------------------------------------------

    /**
     * Clean Keys
     *
     * This is a helper function. To prevent malicious users
     * from trying to exploit keys we make sure that keys are
     * only named with alpha-numeric text and a few other items.
     *
     * @access    private
     * @param    string
     * @return string
     */
    function _clean_input_keys($str)
    {
         if ( ! preg_match("/^[a-z0-9:_\/-\/!]+$/i", $str))
         {
            exit('Disallowed Key Characters: '.$str);
         }

        return $str;
    }

    // --------------------------------------------------------------------

    /**
     * Fetch an item from the GET array
     *
     * @access    public
     * @param    string
     * @param    bool
     * @return string
     */
    function get($index = '', $xss_clean = FALSE)
    {
        if ( ! isset($_GET[$index]))
        {
            return FALSE;
        }

        if ($xss_clean === TRUE)
        {
            if (is_array($_GET[$index]))
            {
                foreach($_GET[$index] as $key => $val)
                {
                    $_GET[$index][$key] = $this->xss_clean($val);
                }
            }
            else
            {
                return $this->xss_clean($_GET[$index]);
            }
        }

        return $_GET[$index];
    }

    // --------------------------------------------------------------------

    /**
     * Fetch an item from the POST array
     *
     * @access    public
     * @param    string
     * @param    bool
     * @return string|false
     */
    function post($index = '', $xss_clean = FALSE)
    {
        if ( ! isset($_POST[$index]))
        {
            return FALSE;
        }

        if ($xss_clean === TRUE)
        {
            if (is_array($_POST[$index]))
            {
                foreach($_POST[$index] as $key => $val)
                {
                    $_POST[$index][$key] = $this->xss_clean($val);
                }
            }
            else
            {
                return $this->xss_clean($_POST[$index]);
            }
        }

        return $_POST[$index];
    }

    // --------------------------------------------------------------------

    /**
     * Fetch an item from the COOKIE array
     *
     * @access    public
     * @param    string
     * @param    bool
     * @return string
     */
    function cookie($index = '', $xss_clean = FALSE)
    {
        if ( ! isset($_COOKIE[$index]))
        {
            return FALSE;
        }

        if ($xss_clean === TRUE)
        {
            if (is_array($_COOKIE[$index]))
            {
                $cookie = array();
                foreach($_COOKIE[$index] as $key => $val)
                {
                    $cookie[$key] = $this->xss_clean($val);
                }

                return $cookie;
            }
            else
            {
                return $this->xss_clean($_COOKIE[$index]);
            }
        }
        else
        {
            return $_COOKIE[$index];
        }
    }

    // --------------------------------------------------------------------

    /**
     * Fetch an item from either POST or GET
     *
     * @access    public
     * @param    string
     * @param    bool
     * @return string|boolean String value or False if not found
     */
    function request($index = '', $xssClean = true)
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST')
        {
            // #post returns false if not found
            if (($result = $this->post($index)) === false) return false;
            return ($xssClean) ? str_replace('"', '&quot;', $result) : $result;
        }

        // #getParameter returns null if not found
        if (($result = \RightNow\Utils\Url::getParameter($index)) === null) return false;
        return ($xssClean) ? $result : str_replace("&quot;", '"', $result);
    }

    // --------------------------------------------------------------------

    /**
     * Fetch an item from the SERVER array
     *
     * @access    public
     * @param    string
     * @param    bool
     * @return string
     */
    function server($index = '', $xss_clean = FALSE)
    {
        if ( ! isset($_SERVER[$index]))
        {
            return FALSE;
        }

        if ($xss_clean === TRUE)
        {
            return $this->xss_clean($_SERVER[$index]);
        }

        return $_SERVER[$index];
    }

    // --------------------------------------------------------------------

    /**
     * Fetch the IP Address
     *
     * @access    public
     * @return string
     */
    function ip_address()
    {
        if ($this->ip_address !== FALSE)
        {
            return $this->ip_address;
        }

        if ($this->server('REMOTE_ADDR') AND $this->server('HTTP_CLIENT_IP'))
        {
             $this->ip_address = $_SERVER['HTTP_CLIENT_IP'];
        }
        elseif ($this->server('REMOTE_ADDR'))
        {
             $this->ip_address = $_SERVER['REMOTE_ADDR'];
        }
        elseif ($this->server('HTTP_CLIENT_IP'))
        {
             $this->ip_address = $_SERVER['HTTP_CLIENT_IP'];
        }
        elseif ($this->server('HTTP_X_FORWARDED_FOR'))
        {
             $this->ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }

        if ($this->ip_address === FALSE)
        {
            $this->ip_address = '0.0.0.0';

            return $this->ip_address;
        }

        if (strstr($this->ip_address, ','))
        {
            $x = explode(',', $this->ip_address);
            $this->ip_address = end($x);
        }

        if ( ! $this->valid_ip($this->ip_address))
        {
            $this->ip_address = '0.0.0.0';
        }

        return $this->ip_address;
    }

    // --------------------------------------------------------------------

    /**
     * Validate IP Address
     *
     * Updated version suggested by Geert De Deckere
     *
     * @access    public
     * @param    string
     * @return string
     */
    function valid_ip($ip)
    {
        $ip_segments = explode('.', $ip);

        // Always 4 segments needed
        if (count($ip_segments) != 4)
        {
            return FALSE;
        }
        // IP can not start with 0
        if (substr($ip_segments[0], 0, 1) == '0')
        {
            return FALSE;
        }
        // Check each segment
        foreach ($ip_segments as $segment)
        {
            // IP segments must be digits and can not be
            // longer than 3 digits or greater then 255
            if (preg_match("/[^0-9]/", $segment) OR $segment > 255 OR strlen($segment) > 3)
            {
                return FALSE;
            }
        }

        return TRUE;
    }

    // --------------------------------------------------------------------

    /**
     * User Agent
     *
     * @access    public
     * @return string
     */
    function user_agent()
    {
        if ($this->user_agent !== FALSE)
        {
            return $this->user_agent;
        }

        $this->user_agent = ( ! isset($_SERVER['HTTP_USER_AGENT'])) ? FALSE : $_SERVER['HTTP_USER_AGENT'];

        return $this->user_agent;
    }

    // --------------------------------------------------------------------

    /**
     * Filename Security
     *
     * @access    public
     * @param    string
     * @return string
     */
    function filename_security($str)
    {
        $bad = array(
                        "../",
                        "./",
                        "<!--",
                        "-->",
                        "<",
                        ">",
                        "'",
                        '"',
                        '&',
                        '$',
                        '#',
                        '{',
                        '}',
                        '[',
                        ']',
                        '=',
                        ';',
                        '?',
                        "%20",
                        "%22",
                        "%3c",        // <
                        "%253c",     // <
                        "%3e",         // >
                        "%0e",         // >
                        "%28",         // (
                        "%29",         // )
                        "%2528",     // (
                        "%26",         // &
                        "%24",         // $
                        "%3f",         // ?
                        "%3b",         // ;
                        "%3d"        // =
                    );

        return stripslashes(str_replace($bad, '', $str));
    }

    // --------------------------------------------------------------------

    /**
     * XSS Clean
     *
     * Sanitizes data so that Cross Site Scripting Hacks can be
     * prevented.  This function does a fair amount of work but
     * it is extremely thorough, designed to prevent even the
     * most obscure XSS attempts.  Nothing is ever 100% foolproof,
     * of course, but I haven't been able to get anything passed
     * the filter.
     *
     * Note: This function should only be used to deal with data
     * upon submission.  It's not something that should
     * be used for general runtime processing.
     *
     * This function was based in part on some code and ideas I
     * got from Bitflux: http://blog.bitflux.ch/wiki/XSS_Prevention
     *
     * To help develop this script I used this great list of
     * vulnerabilities along with a few other hacks I've
     * harvested from examining vulnerabilities in other programs:
     * http://ha.ckers.org/xss.html
     *
     * @access    public
     * @param    string
     * @return string
     */
    function xss_clean($str)
    {
        /*
         * Remove Null Characters
         *
         * This prevents sandwiching null characters
         * between ascii characters, like Java\0script.
         *
         */
        $str = preg_replace('/\0+/', '', $str);
        $str = preg_replace('/(\\\\0)+/', '', $str);

        /*
         * Validate standard character entities
         *
         * Add a semicolon if missing.  We do this to enable
         * the conversion of entities to ASCII later.
         *
         */
        $str = preg_replace('#(&\#?[0-9a-z]+)[\x00-\x20]*;?#i', "\\1;", $str);

        /*
         * Validate UTF16 two byte encoding (x00)
         *
         * Just as above, adds a semicolon if missing.
         *
         */
        $str = preg_replace('#(&\#x?)([0-9A-F]+);?#i',"\\1\\2;",$str);

        /*
         * URL Decode
         *
         * Just in case stuff like this is submitted:
         *
         * <a href="http://%77%77%77%2E%67%6F%6F%67%6C%65%2E%63%6F%6D">Google</a>
         *
         * Note: Normally urldecode() would be easier but it removes plus signs
         *
         */
        $str = preg_replace("/(%20)+/", '9u3iovBnRThju941s89rKozm', $str);
        $str = preg_replace("/%u0([a-z0-9]{3})/i", "&#x\\1;", $str);
        $str = preg_replace("/%([a-z0-9]{2})/i", "&#x\\1;", $str);
        $str = str_replace('9u3iovBnRThju941s89rKozm', "%20", $str);

        /*
         * Convert character entities to ASCII
         *
         * This permits our tests below to work reliably.
         * We only convert entities that are within tags since
         * these are the ones that will pose security problems.
         *
         */

        $str = preg_replace_callback("/[a-z]+=([\'\"]).*?\\1/si", array($this, '_attribute_conversion'), $str);

        $str = preg_replace_callback("/<([\w]+)[^>]*>/si", array($this, '_html_entity_decode_callback'), $str);

        /*

        Old Code that when modified to use preg_replace()'s above became more efficient memory-wise

        if (preg_match_all("/[a-z]+=([\'\"]).*?\\1/si", $str, $matches))
        {
            for ($i = 0; $i < count($matches[0]); $i++)
            {
                if (stristr($matches[0][$i], '>'))
                {
                    $str = str_replace(    $matches['0'][$i],
                                        str_replace('>', '&lt;', $matches[0][$i]),
                                        $str);
                }
            }
        }

        if (preg_match_all("/<([\w]+)[^>]*>/si", $str, $matches))
        {
            for ($i = 0; $i < count($matches[0]); $i++)
            {
                $str = str_replace($matches[0][$i],
                                    $this->_html_entity_decode($matches[0][$i], $charset),
                                    $str);
            }
        }
        */

        /*
         * Convert all tabs to spaces
         *
         * This prevents strings like this: ja    vascript
         * NOTE: we deal with spaces between characters later.
         * NOTE: preg_replace was found to be amazingly slow here on large blocks of data,
         * so we use str_replace.
         *
         */

        $str = str_replace("\t", " ", $str);

        /*
         * Not Allowed Under Any Conditions
         */
        $bad = array(
                        'document.cookie'    => '[removed]',
                        'document.write'    => '[removed]',
                        '.parentNode'        => '[removed]',
                        '.innerHTML'        => '[removed]',
                        'window.location'    => '[removed]',
                        '-moz-binding'        => '[removed]',
                        '<!--'                => '&lt;!--',
                        '-->'                => '--&gt;',
                        '<!CDATA['            => '&lt;![CDATA['
                    );

        foreach ($bad as $key => $val)
        {
            $str = str_replace($key, $val, $str);
        }

        $bad = array(
                        "javascript\s*:"    => '[removed]',
                        "expression\s*\("    => '[removed]', // CSS and IE
                        "Redirect\s+302"    => '[removed]'
                    );

        foreach ($bad as $key => $val)
        {
            $str = preg_replace("#".$key."#i", $val, $str);
        }

        /*
         * Makes PHP tags safe
         *
         *  Note: XML tags are inadvertently replaced too:
         *
         *    <?xml
         *
         * But it doesn't seem to pose a problem.
         *
         */
        $str = str_replace(array('<?php', '<?PHP', '<?', '?'.'>'),  array('&lt;?php', '&lt;?PHP', '&lt;?', '?&gt;'), $str);

        /*
         * Compact any exploded words
         *
         * This corrects words like:  j a v a s c r i p t
         * These words are compacted back to their correct state.
         * This was updated with latest CodeIgniter code from
         * https://github.com/bcit-ci/CodeIgniter/blob/1e35792cc2d231cba11c2faefd71717ab67a46d2/system/core/Security.php
         * to fix the deprecated "e" modifier use in preg_replace()
         */
        $words = array(
            'javascript', 'expression', 'vbscript', 'jscript', 'wscript',
            'vbs', 'script', 'base64', 'applet', 'alert', 'document',
            'write', 'cookie', 'window', 'confirm', 'prompt'
        );
        foreach ($words as $word)
        {
            $word = implode('\s*', str_split($word)).'\s*';
            // We only want to do this when it is followed by a non-word character
            // That way valid stuff like "dealer to" does not become "dealerto"
            $str = preg_replace_callback('#('.substr($word, 0, -3).')(\W)#is', array($this, '_compact_exploded_words'), $str);
        }

        /*
         * Remove disallowed Javascript in links or img tags
         */
        do
        {
            $original = $str;

            if ((version_compare(PHP_VERSION, '5.0', '>=') === TRUE && stripos($str, '</a>') !== FALSE) OR
                 preg_match("/<\/a>/i", $str))
            {
                $str = preg_replace_callback("#<a.*?</a>#si", array($this, '_js_link_removal'), $str);
            }

            if ((version_compare(PHP_VERSION, '5.0', '>=') === TRUE && stripos($str, '<img') !== FALSE) OR
                 preg_match("/img/i", $str))
            {
                $str = preg_replace_callback("#<img.*?".">#si", array($this, '_js_img_removal'), $str);
            }

            if ((version_compare(PHP_VERSION, '5.0', '>=') === TRUE && (stripos($str, 'script') !== FALSE OR stripos($str, 'xss') !== FALSE)) OR
                 preg_match("/(script|xss)/i", $str))
            {
                $str = preg_replace("#</*(script|xss).*?\>#si", "", $str);
            }
        }
        while($original != $str);

        unset($original);

        /*
         * Remove JavaScript Event Handlers
         *
         * Note: This code is a little blunt.  It removes
         * the event handler and anything up to the closing >,
         * but it's unlikely to be a problem.
         *
         */
        $event_handlers = array('onblur','onchange','onclick','onfocus','onload','onmouseover','onmouseup','onmousedown','onselect','onsubmit','onunload','onkeypress','onkeydown','onkeyup','onresize', 'xmlns');
        $str = preg_replace("#<([^>]+)(".implode('|', $event_handlers).")([^>]*)>#iU", "&lt;\\1\\2\\3&gt;", $str);

        /*
         * Sanitize naughty HTML elements
         *
         * If a tag containing any of the words in the list
         * below is found, the tag gets converted to entities.
         *
         * So this: <blink>
         * Becomes: &lt;blink&gt;
         *
         */
        $str = preg_replace('#<(/*\s*)(alert|applet|basefont|base|behavior|bgsound|blink|body|embed|expression|form|frameset|frame|head|html|ilayer|iframe|input|layer|link|meta|object|plaintext|style|script|textarea|title|xml|xss)([^>]*)>#is', "&lt;\\1\\2\\3&gt;", $str);

        /*
         * Sanitize naughty scripting elements
         *
         * Similar to above, only instead of looking for
         * tags it looks for PHP and JavaScript commands
         * that are disallowed.  Rather than removing the
         * code, it simply converts the parenthesis to entities
         * rendering the code un-executable.
         *
         * For example:    eval('some code')
         * Becomes:        eval&#40;'some code'&#41;
         *
         */
        $str = preg_replace('#(alert|cmd|passthru|eval|exec|expression|system|fopen|fsockopen|file|file_get_contents|readfile|unlink)(\s*)\((.*?)\)#si', "\\1\\2&#40;\\3&#41;", $str);

        /*
         * Final clean up
         *
         * This adds a bit of extra precaution in case
         * something got through the above filters
         *
         */
        $bad = array(
                        'document.cookie'    => '[removed]',
                        'document.write'    => '[removed]',
                        '.parentNode'        => '[removed]',
                        '.innerHTML'        => '[removed]',
                        'window.location'    => '[removed]',
                        '-moz-binding'        => '[removed]',
                        '<!--'                => '&lt;!--',
                        '-->'                => '--&gt;',
                        '<!CDATA['            => '&lt;![CDATA['
                    );

        foreach ($bad as $key => $val)
        {
            $str = str_replace($key, $val, $str);
        }

        $bad = array(
                        "javascript\s*:"    => '[removed]',
                        "expression\s*\("    => '[removed]', // CSS and IE
                        "Redirect\s+302"    => '[removed]'
                    );

        foreach ($bad as $key => $val)
        {
            $str = preg_replace("#".$key."#i", $val, $str);
        }

        log_message('debug', "XSS Filtering completed");

        return $str;
    }

    // --------------------------------------------------------------------

    /**
     * JS Link Removal
     *
     * Callback function for xss_clean() to sanitize links
     * This limits the PCRE backtracks, making it more performance friendly
     * and prevents PREG_BACKTRACK_LIMIT_ERROR from being triggered in
     * PHP 5.2+ on link-heavy strings
     *
     * @access    private
     * @param    array
     * @return string
     */
    function _js_link_removal($match)
    {
        return preg_replace("#<a.+?href=.*?(alert\(|alert&\#40;|javascript\:|window\.|document\.|\.cookie|<script|<xss).*?\>.*?</a>#si", "", $match[0]);
    }

    /**
     * JS Image Removal
     *
     * Callback function for xss_clean() to sanitize image tags
     * This limits the PCRE backtracks, making it more performance friendly
     * and prevents PREG_BACKTRACK_LIMIT_ERROR from being triggered in
     * PHP 5.2+ on image tag heavy strings
     *
     * @access    private
     * @param    array
     * @return string
     */
    function _js_img_removal($match)
    {
        return preg_replace("#<img.+?src=.*?(alert\(|alert&\#40;|javascript\:|window\.|document\.|\.cookie|<script|<xss).*?\>#si", "", $match[0]);
    }

    // --------------------------------------------------------------------

    /**
     * Attribute Conversion
     *
     * Used as a callback for XSS Clean
     *
     * @access    public
     * @param    array
     * @return string
     */
    function _attribute_conversion($match)
    {
        return str_replace('>', '&lt;', $match[0]);
    }

    // --------------------------------------------------------------------

    /**
     * HTML Entity Decode Callback
     *
     * Used as a callback for XSS Clean
     *
     * @access    public
     * @param    array
     * @return string
     */
    function _html_entity_decode_callback($match)
    {
        $CI = get_instance();
        $charset = $CI->config->item('charset');

        return $this->_html_entity_decode($match[0], strtoupper($charset));
    }

    // --------------------------------------------------------------------

    /**
     * HTML Entities Decode
     *
     * This function is a replacement for html_entity_decode()
     * This was updated with latest CodeIgniter code from
     * https://github.com/bcit-ci/CodeIgniter/blob/1e35792cc2d231cba11c2faefd71717ab67a46d2/system/core/Security.php
     * to fix the deprecated "e" modifier use in preg_replace()
     *
     * In some versions of PHP the native function does not work
     * when UTF-8 is the specified character set, so this gives us
     * a work-around.  More info here:
     * http://bugs.php.net/bug.php?id=25670
     *
     * @access    private
     * @param    string
     * @param    string
     * @return string
     */
    /* -------------------------------------------------
    /*  Replacement for html_entity_decode()
    /* -------------------------------------------------*/

    /*
    NOTE: html_entity_decode() has a bug in some PHP versions when UTF-8 is the
    character set, and the PHP developers said they were not back porting the
    fix to versions other than PHP 5.x.
    */

    public function _html_entity_decode($str, $charset = NULL)
    {
        if (strpos($str, '&') === FALSE)
        {
            return $str;
        }
        static $_entities;
        isset($charset) OR $charset = $this->charset;
        $flag = (phpversion() === '5.4')
            ? ENT_COMPAT | ENT_HTML5
            : ENT_COMPAT;
        do
        {
            $str_compare = $str;
            // Decode standard entities, avoiding false positives
            if ($c = preg_match_all('/&[a-z]{2,}(?![a-z;])/i', $str, $matches))
            {
                if ( ! isset($_entities))
                {
                    $_entities = array_map(
                        'strtolower',
                        (phpversion() === '5.3.4')
                            ? get_html_translation_table(HTML_ENTITIES, $flag, $charset)
                            : get_html_translation_table(HTML_ENTITIES, $flag)
                    );
                    // If we're not on PHP 5.4+, add the possibly dangerous HTML 5
                    // entities to the array manually
                    if ($flag === ENT_COMPAT)
                    {
                        $_entities[':'] = '&colon;';
                        $_entities['('] = '&lpar;';
                        $_entities[')'] = '&rpar';
                        $_entities["\n"] = '&newline;';
                        $_entities["\t"] = '&tab;';
                    }
                }
                $replace = array();
                $matches = array_unique(array_map('strtolower', $matches[0]));
                for ($i = 0; $i < $c; $i++)
                {
                    if (($char = array_search($matches[$i].';', $_entities, TRUE)) !== FALSE)
                    {
                        $replace[$matches[$i]] = $char;
                    }
                }
                $str = str_ireplace(array_keys($replace), array_values($replace), $str);
            }
            // Decode numeric & UTF16 two byte entities
            $str = html_entity_decode(
                preg_replace('/(&#(?:x0*[0-9a-f]{2,5}(?![0-9a-f;])|(?:0*\d{2,4}(?![0-9;]))))/iS', '$1;', $str),
                $flag,
                $charset
            );
        }
        while ($str_compare !== $str);
        return $str;
    }

    /**
     * Compact Exploded Words
     *
     * Callback method for xss_clean() to remove whitespace from
     * things like 'j a v a s c r i p t'.
     *
     * @used-by CI_Security::xss_clean()
     * @param   array   $matches
     * @return  string
     */
    protected function _compact_exploded_words($matches)
    {
        return preg_replace('/\s+/s', '', $matches[1]).$matches[2];
    }

}
// END Input class
?>
