<?php
/**
 * CPStandard_Sniffs_PHP_ForbiddenFunctionsSniff.
 *
 * PHP version 5
 *
 * @category  PHP
 * @package   PHP_CodeSniffer
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @author    Marc McIntyre <mmcintyre@squiz.net>
 * @copyright 2006 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   http://matrix.squiz.net/developer/tools/php_cs/licence BSD Licence
 * @version   CVS: $Id: ForbiddenFunctionsSniff.php,v 1.1 2014/01/08 23:28:38 eturner Exp $
 * @link      http://pear.php.net/package/PHP_CodeSniffer
 */

/**
 * CPStandard_Sniffs_PHP_ForbiddenFunctionsSniff.
 *
 * Discourages the use of alias functions that are kept in PHP for compatibility
 * with older versions. Can be used to forbid the use of any function.
 *
 * @category  PHP
 * @package   PHP_CodeSniffer
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @author    Marc McIntyre <mmcintyre@squiz.net>
 * @copyright 2006 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   http://matrix.squiz.net/developer/tools/php_cs/licence BSD Licence
 * @version   Release: 1.2.0a1
 * @link      http://pear.php.net/package/PHP_CodeSniffer
 */
class CPStandard_Sniffs_PHP_ForbiddenFunctionsSniff implements PHP_CodeSniffer_Sniff
{

    /**
     * A list of forbidden functions with their alternatives.
     *
     * The value is NULL if no alternative exists. IE, the
     * function should just not be used.
     * some alias functions are part of this forbidden list
     * see http://php.net/manual/en/aliases.php for a full list
     * @var array(string => string|null)
     */
    protected $forbiddenFunctions = array(
        'file_exists' => 'is_readable',
        'htmlentities' => 'htmlspecialchars',
        'delete' => 'unset',
        'chop' => 'rtrim',
        'close' => 'closedir',
        'die' => 'exit',
        'doubleval' => 'floatval',
        'fputs' => 'fwrite',
        'ini_alter' => 'ini_set',
        'is_double' => 'is_float',
        'is_integer' => 'is_int',
        'is_long' => 'is_int',
        'is_real' => 'is_float',
        'is_writeable' => 'is_writable',
        'join' => 'implode',
        'magic_quotes_runtime' => 'set_magic_quotes_runtime',
        'pos' => 'current',
        'rewind' => 'rewinddir',
        'show_source' => 'highlight_file',
        'sizeof' => 'count',
        'strchr' => 'strstr',
        '_print_array' => null,
        'ereg_replace' => 'preg_replace',
        'ereg' => 'preg_match',
        'eregi_replace' => 'preg_replace',
        'eregi' => 'preg_match',
        'split' => 'explode',
        'spliti' => 'explode',
        'sql_regcase' => null,
        'getDynamicTitle' => 'SEO::getDynamicTitle',
        'msg_get_rnw' => 'getMessage',
        'msg_get_js_rnw' => 'getMessageJS',
        'msg_get_common' => 'getMessage',
        'cfg_get_common' => 'getConfig',
        'cfg_get_common_bool' => 'getConfig',
        'cfg_get_common_int' => 'getConfig',
        'cfg_get_rnw_common' => 'getConfig',
        'cfg_get_rnw_common_bool' => 'getConfig',
        'cfg_get_rnw_common_int' => 'getConfig',
        'cfg_get_rnw_ui' => 'getConfig',
        'cfg_get_rnw_ui_bool' => 'getConfig',
        'cfg_get_rnw_ui_int' => 'getConfig',
        'cfg_get_js' => 'getConfigJS',
        'cfg_get_rnl' => 'getConfig',
        'cfg_get_rnl_bool' => 'getConfig',
        'cfg_get_rnl_int' => 'getConfig',
        'printFieldValidationStrings' => null,
        'printFeedbackValidationStrings' => null,
        'printNotificationStrings' => null,
        'printEmailValidationStrings' => null,
        'printFormButtonValidationStrings' => null,
        'printMenuFilterFormStrings' => null,
        'call_user_method' => 'call_user_func',
        'call_user_method_array' => 'call_user_func_array',
        'define_syslog_variables' => null,
        'set_magic_quotes_runtime' => null,
        'session_register' => null,
        'session_unregister' => null,
        'session_is_registered' => null,
        'set_socket_blocking' => null,
        'create_function' => null,
        'mysql_db_query' => 'mysql_select_db',
        'mysql_escape_string' => 'mysql_real_escape_string',
        'get_defined_vars' => null,
        'logmessage' => null,
    );

    /**
     * If true, an error will be thrown; otherwise a warning.
     *
     * @var bool
     */
    protected $error = true;


    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    public function register()
    {
        return array(T_STRING);

    }//end register()


    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param PHP_CodeSniffer_File $phpcsFile The file being scanned.
     * @param int                  $stackPtr  The position of the current token in the
     *                                        stack passed in $tokens.
     *
     * @return void
     */
    public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        $prevToken = $phpcsFile->findPrevious(T_WHITESPACE, ($stackPtr - 1), null, true);
        // the array below also included T_DOUBLE_COLON,
        //  but we'd like to prevent committed code from calling Framework::logMessage
        if (in_array($tokens[$prevToken]['code'], array(T_OBJECT_OPERATOR, T_FUNCTION)) === true) {
            // Not a call to a PHP function.
            return;
        }

        $function = strtolower($tokens[$stackPtr]['content']);

        if (in_array($function, array_keys($this->forbiddenFunctions)) === false) {
            return;
        }

        $error = "The use of function $function() is ";
        if ($this->error === true) {
            $error .= 'forbidden';
        } else {
            $error .= 'discouraged';
        }

        if ($this->forbiddenFunctions[$function] !== null) {
            $error .= '; use '.$this->forbiddenFunctions[$function].'() instead';
        }

        if ($this->error === true) {
            $phpcsFile->addError($error, $stackPtr);
        } else {
            $phpcsFile->addWarning($error, $stackPtr);
        }

    }//end process()


}//end class

?>
