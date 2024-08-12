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
 * User Agent Class
 *
 * Identifies the platform, browser, robot, or mobile devise of the browsing agent
 *
 * @package        CodeIgniter
 * @subpackage    Libraries
 * @category    User Agent
 * @author        Rick Ellis
 * @link        http://www.codeigniter.com/user_guide/libraries/user_agent.html
 */
class CI_User_agent {

    public $agent = null;

    public $is_browser = false;
    public $is_robot = false;
    public $is_mobile = false;

    public $languages = array();
    public $charsets = array();

    public $platforms = array(
        'windows nt 10.0'   => 'Windows 10',
        'windows nt 6.3'    => 'Windows 8.1',
        'windows nt 6.2'    => 'Windows 8',
        'windows nt 6.1'    => 'Windows 7',
        'windows nt 6.0'    => 'Windows Vista',
        'windows nt 5.2'    => 'Windows 2003',
        'windows nt 5.1'    => 'Windows XP',
        'windows nt 5.0'    => 'Windows 2000',
        'windows nt 4.0'    => 'Windows NT 4.0',
        'winnt4.0'          => 'Windows NT 4.0',
        'winnt 4.0'         => 'Windows NT',
        'winnt'             => 'Windows NT',
        'windows 98'        => 'Windows 98',
        'win98'             => 'Windows 98',
        'windows 95'        => 'Windows 95',
        'win95'             => 'Windows 95',
        'windows phone'     => 'Windows Phone',
        'windows'           => 'Unknown Windows OS',
        'android'           => 'Android',
        'blackberry'        => 'BlackBerry',
        'iphone'            => 'iOS',
        'ipad'              => 'iOS',
        'ipod'              => 'iOS',
        'os x'              => 'Mac OS X',
        'ppc mac'           => 'Power PC Mac',
        'freebsd'           => 'FreeBSD',
        'ppc'               => 'Macintosh',
        'linux'             => 'Linux',
        'debian'            => 'Debian',
        'sunos'             => 'Sun Solaris',
        'beos'              => 'BeOS',
        'apachebench'       => 'ApacheBench',
        'aix'               => 'AIX',
        'irix'              => 'Irix',
        'osf'               => 'DEC OSF',
        'hp-ux'             => 'HP-UX',
        'netbsd'            => 'NetBSD',
        'bsdi'              => 'BSDi',
        'openbsd'           => 'OpenBSD',
        'gnu'               => 'GNU/Linux',
        'unix'              => 'Unknown Unix OS',
        'webOS'             => 'Palm Web OS',
    );
    // The order of this array should NOT be changed. Many browsers return
    // multiple browser types so we want to identify the sub-type first.
    public $browsers = array(
        'Edge'              => 'Edge',
        'Chrome'            => 'Chrome',
        'criOS'             => 'Chrome for iOS',
        'Opera'             => 'Opera',
        'MSIE'              => 'Internet Explorer',
        'Internet Explorer' => 'Internet Explorer',
        'Trident'           => array('browser' => 'Internet Explorer', 'versionKey' => 'rv:'),
        'Shiira'            => 'Shiira',
        'Firefox'           => 'Firefox',
        'Chimera'           => 'Chimera',
        'Phoenix'           => 'Phoenix',
        'Firebird'          => 'Firebird',
        'Camino'            => 'Camino',
        'Netscape'          => 'Netscape',
        'OmniWeb'           => 'OmniWeb',
        'Safari'            => 'Safari',
        'Mozilla'           => 'Mozilla',
        'Konqueror'         => 'Konqueror',
        'icab'              => 'iCab',
        'Lynx'              => 'Lynx',
        'Links'             => 'Links',
        'hotjava'           => 'HotJava',
        'amaya'             => 'Amaya',
        'IBrowse'           => 'IBrowse',
        'Maxthon'           => 'Maxthon',
    );
    public $mobiles = array(
        // Legacy
        'mobileexplorer'       => 'Mobile Explorer',
        'palmsource'           => 'Palm',
        'palmscape'            => 'Palmscape',

        // Phones and Manufacturers
        'motorola'             => 'Motorola',
        'nokia'                => 'Nokia',
        'palm'                 => 'Palm',
        'iphone'               => 'Apple iPhone',
        'ipad'                 => 'Apple iPad',
        'ipod'                 => 'Apple iPod Touch',
        'sony'                 => 'Sony Ericsson',
        'ericsson'             => 'Sony Ericsson',
        'blackberry'           => 'BlackBerry',
        'cocoon'               => 'O2 Cocoon',
        'blazer'               => 'Treo',
        'lg'                   => 'LG',
        'amoi'                 => 'Amoi',
        'xda'                  => 'XDA',
        'mda'                  => 'MDA',
        'vario'                => 'Vario',
        'htc'                  => 'HTC',
        'samsung'              => 'Samsung',
        'sharp'                => 'Sharp',
        'sie-'                 => 'Siemens',
        'alcatel'              => 'Alcatel',
        'benq'                 => 'BenQ',
        'ipaq'                 => 'HP iPaq',
        'mot-'                 => 'Motorola',
        'playstation portable' => 'PlayStation Portable',
        'playstation 3'        => 'PlayStation 3',
        'playstation vita'     => 'PlayStation Vita',
        'hiptop'               => 'Danger Hiptop',
        'nec-'                 => 'NEC',
        'panasonic'            => 'Panasonic',
        'philips'              => 'Philips',
        'sagem'                => 'Sagem',
        'sanyo'                => 'Sanyo',
        'spv'                  => 'SPV',
        'zte'                  => 'ZTE',
        'sendo'                => 'Sendo',
        'nintendo dsi'         => 'Nintendo DSi',
        'nintendo ds'          => 'Nintendo DS',
        'nintendo 3ds'         => 'Nintendo 3DS',
        'wii'                  => 'Nintendo Wii',
        'open web'             => 'Open Web',
        'openweb'              => 'OpenWeb',

        // Operating Systems
        'android'              => 'Android',
        'symbian'              => 'Symbian',
        'SymbianOS'            => 'SymbianOS',
        'elaine'               => 'Palm',
        'series60'             => 'Symbian S60',
        'windows ce'           => 'Windows CE',

        // Browsers
        'obigo'                => 'Obigo',
        'netfront'             => 'Netfront Browser',
        'openwave'             => 'Openwave Browser',
        'mobilexplorer'        => 'Mobile Explorer',
        'operamini'            => 'Opera Mini',
        'opera mini'           => 'Opera Mini',
        'opera mobi'           => 'Opera Mobile',
        'fennec'               => 'Firefox Mobile',

        // Other
        'digital paths'        => 'Digital Paths',
        'avantgo'              => 'AvantGo',
        'xiino'                => 'Xiino',
        'novarra'              => 'Novarra Transcoder',
        'vodafone'             => 'Vodafone',
        'docomo'               => 'NTT DoCoMo',
        'o2'                   => 'O2',

        // Fallback
        'mobile'               => 'Generic Mobile',
        'wireless'             => 'Generic Mobile',
        'j2me'                 => 'Generic Mobile',
        'midp'                 => 'Generic Mobile',
        'cldc'                 => 'Generic Mobile',
        'up.link'              => 'Generic Mobile',
        'up.browser'           => 'Generic Mobile',
        'smartphone'           => 'Generic Mobile',
        'cellphone'            => 'Generic Mobile',
    );
    public $robots = array(
        'googlebot'     => 'Googlebot',
        'msnbot'        => 'MSNBot',
        'baiduspider'   => 'Baiduspider',
        'bingbot'       => 'Bing',
        'slurp'         => 'Inktomi Slurp',
        'yahoo'         => 'Yahoo',
        'askjeeves'     => 'AskJeeves',
        'fastcrawler'   => 'FastCrawler',
        'infoseek'      => 'InfoSeek Robot 1.0',
        'lycos'         => 'Lycos',
        'yandex'        => 'YandexBot',
    );

    public $platform = '';
    public $browser = '';
    public $version = '';
    public $mobile = '';
    public $robot = '';

    /**
     * Constructor
     *
     * Sets the User Agent and runs the compilation routine
     *
     * @access    public
     * @return    void
     */
    function __construct()
    {
        if (isset($_SERVER['HTTP_USER_AGENT']))
        {
            $this->agent = trim($_SERVER['HTTP_USER_AGENT']);
        }

        if(!is_null($this->agent)){
            $this->_compile_data();
        }
        log_message('debug', "User Agent Class Initialized");
    }

    // --------------------------------------------------------------------

    /**
     * Compile the User Agent Data
     *
     * @access    private
     * @return    bool
     */
    function _compile_data()
    {
        $this->_set_platform();

        foreach (array('_set_browser', '_set_robot', '_set_mobile') as $function)
        {
            if ($this->$function() === TRUE)
            {
                break;
            }
        }
    }

    // --------------------------------------------------------------------

    /**
     * Set the Platform
     *
     * @access    private
     * @return    mixed
     */
    function _set_platform()
    {
        if (is_array($this->platforms) AND count($this->platforms) > 0)
        {
            foreach ($this->platforms as $key => $val)
            {
                if (preg_match("|".preg_quote($key)."|i", $this->agent))
                {
                    $this->platform = $val;
                    return TRUE;
                }
            }
        }
        $this->platform = 'Unknown Platform';
    }

    // --------------------------------------------------------------------

    /**
     * Set the Browser
     *
     * @access    private
     * @return    bool
     */
    function _set_browser()
    {
        if (is_array($this->browsers) AND count($this->browsers) > 0)
        {
            foreach ($this->browsers as $key => $val)
            {
                //Some of our more complicated user agent detection has different values to match to get browser type vs browser version
                if(is_array($val)){
                    if(stripos($this->agent, $key) === false){
                        continue;
                    }
                    $key = $val['versionKey'];
                    $val = $val['browser'];
                }
                if (preg_match("|".preg_quote($key).".*?([0-9\.]+)|i", $this->agent, $match))
                {
                    $this->is_browser = TRUE;
                    $this->version = $match[1];
                    $this->browser = $val;
                    $this->_set_mobile();
                    return TRUE;
                }
            }
        }
        return FALSE;
    }

    // --------------------------------------------------------------------

    /**
     * Set the Robot
     *
     * @access    private
     * @return    bool
     */
    function _set_robot()
    {
        if (is_array($this->robots) AND count($this->robots) > 0)
        {
            foreach ($this->robots as $key => $val)
            {
                if (preg_match("|".preg_quote($key)."|i", $this->agent))
                {
                    $this->is_robot = TRUE;
                    $this->robot = $val;
                    return TRUE;
                }
            }
        }
        return FALSE;
    }

    // --------------------------------------------------------------------

    /**
     * Set the Mobile Device
     *
     * @access    private
     * @return    bool
     */
    function _set_mobile()
    {
        if (is_array($this->mobiles) AND count($this->mobiles) > 0)
        {
            foreach ($this->mobiles as $key => $val)
            {
                if (FALSE !== (strpos(strtolower($this->agent), $key)))
                {
                    $this->is_mobile = TRUE;
                    $this->mobile = $val;
                    return TRUE;
                }
            }
        }
        return FALSE;
    }

    // --------------------------------------------------------------------

    /**
     * Set the accepted languages
     *
     * @access    private
     * @return    void
     */
    function _set_languages()
    {
        if ((count($this->languages) == 0) AND isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) AND $_SERVER['HTTP_ACCEPT_LANGUAGE'] != '')
        {
            $languages = preg_replace('/(;q=.+)/i', '', trim($_SERVER['HTTP_ACCEPT_LANGUAGE']));

            $this->languages = explode(',', $languages);
        }

        if (count($this->languages) == 0)
        {
            $this->languages = array('Undefined');
        }
    }

    // --------------------------------------------------------------------

    /**
     * Set the accepted character sets
     *
     * @access    private
     * @return    void
     */
    function _set_charsets()
    {
        if ((count($this->charsets) == 0) AND isset($_SERVER['HTTP_ACCEPT_CHARSET']) AND $_SERVER['HTTP_ACCEPT_CHARSET'] != '')
        {
            $charsets = preg_replace('/(;q=.+)/i', '', trim($_SERVER['HTTP_ACCEPT_CHARSET']));

            $this->charsets = explode(',', $charsets);
        }

        if (count($this->charsets) == 0)
        {
            $this->charsets = array('Undefined');
        }
    }

    // --------------------------------------------------------------------

    /**
     * Is Browser
     *
     * @access    public
     * @return    bool
     */
    function is_browser()
    {
        return $this->is_browser;
    }

    // --------------------------------------------------------------------

    /**
     * Is Robot
     *
     * @access    public
     * @return    bool
     */
    function is_robot()
    {
        return $this->is_robot;
    }

    // --------------------------------------------------------------------

    /**
     * Is Mobile
     *
     * @access    public
     * @return    bool
     */
    function is_mobile()
    {
        return $this->is_mobile;
    }

    // --------------------------------------------------------------------

    /**
     * Is this a referral from another site?
     *
     * @access    public
     * @return    bool
     */
    function is_referral()
    {
        return ( ! isset($_SERVER['HTTP_REFERER']) OR $_SERVER['HTTP_REFERER'] == '') ? FALSE : TRUE;
    }

    // --------------------------------------------------------------------

    /**
     * Agent String
     *
     * @access    public
     * @return    string
     */
    function agent_string()
    {
        return $this->agent;
    }

    // --------------------------------------------------------------------

    /**
     * Get Platform
     *
     * @access    public
     * @return    string
     */
    function platform()
    {
        return $this->platform;
    }

    // --------------------------------------------------------------------

    /**
     * Get Browser Name
     *
     * @access    public
     * @return    string
     */
    function browser()
    {
        return $this->browser;
    }

    // --------------------------------------------------------------------

    /**
     * Get the Browser Version
     *
     * @access    public
     * @return    string
     */
    function version()
    {
        return $this->version;
    }

    // --------------------------------------------------------------------

    /**
     * Get The Robot Name
     *
     * @access    public
     * @return    string
     */
    function robot()
    {
        return $this->robot;
    }
    // --------------------------------------------------------------------

    /**
     * Get the Mobile Device
     *
     * @access    public
     * @return    string
     */
    function mobile()
    {
        return $this->mobile;
    }

    // --------------------------------------------------------------------

    /**
     * Get the referrer
     *
     * @access    public
     * @return    bool
     */
    function referrer()
    {
        return ( ! isset($_SERVER['HTTP_REFERER']) OR $_SERVER['HTTP_REFERER'] == '') ? '' : trim($_SERVER['HTTP_REFERER']);
    }

    // --------------------------------------------------------------------

    /**
     * Get the accepted languages
     *
     * @access    public
     * @return    array
     */
    function languages()
    {
        if (count($this->languages) == 0)
        {
            $this->_set_languages();
        }

        return $this->languages;
    }

    // --------------------------------------------------------------------

    /**
     * Get the accepted Character Sets
     *
     * @access    public
     * @return    array
     */
    function charsets()
    {
        if (count($this->charsets) == 0)
        {
            $this->_set_charsets();
        }

        return $this->charsets;
    }

    // --------------------------------------------------------------------

    /**
     * Test for a particular language
     *
     * @access    public
     * @return    bool
     */
    function accept_lang($lang = 'en')
    {
        return (in_array(strtolower($lang), $this->languages(), TRUE)) ? TRUE : FALSE;
    }

    // --------------------------------------------------------------------

    /**
     * Test for a particular character set
     *
     * @access    public
     * @return    bool
     */
    function accept_charset($charset = 'utf-8')
    {
        return (in_array(strtolower($charset), $this->charsets(), TRUE)) ? TRUE : FALSE;
    }

    /**
    * Returns the matching browser string if the current user agent is one of the RightNow
    * supported mobile browsers (iphone, ipod, android, webos)
    * or false if the current user agent is not one of the
    * RightNow supported mobile browsers.
    *
    * @access   public
    * @return   bool
    */
    function supportedMobileBrowser()
    {
        if(preg_match('/\b(iphone|ipod|android|webos)\b/i', $this->agent, $mobileBrowserMatch))
        {
           return strtolower($mobileBrowserMatch[1]);
        }
        return false;
    }
}

?>
