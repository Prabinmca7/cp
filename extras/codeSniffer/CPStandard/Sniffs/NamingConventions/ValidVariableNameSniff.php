<?php
if (class_exists('PHP_CodeSniffer_Standards_AbstractVariableSniff', true) === false) {
    throw new PHP_CodeSniffer_Exception('Class PHP_CodeSniffer_Standards_AbstractVariableSniff not found');
}

/**
 * CPStandard_Sniffs_NamingConventions_ValidVariableNameSniff.
 *
 * Checks the naming of variables and member variables.
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
class CPStandard_Sniffs_NamingConventions_ValidVariableNameSniff extends PHP_CodeSniffer_Standards_AbstractVariableSniff
{

    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param PHP_CodeSniffer_File $phpcsFile The file being scanned.
     * @param int                  $stackPtr  The position of the current token in the
     *                                        stack passed in $tokens.
     *
     * @return void
     */
    protected function processVariable(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        $tokens  = $phpcsFile->getTokens();
        $varName = ltrim($tokens[$stackPtr]['content'], '$');

        if (!$this->isValidVariableName($varName)) {
            $phpcsFile->addError("Variable \"$varName\" is not in valid camel caps format.", $stackPtr);
        }

        // using $_SERVER['SCRIPT_URI'] is bad, so we recommend using $_SERVER['REQUEST_URI'], since it is cleansed
        // the variable is $_SERVER, but we want to check the key at $stackPtr+2 ($stackPtr+1 is the quote character)
        if ($varName === '_SERVER' && trim($tokens[$stackPtr + 2]['content'], "'\"") === 'SCRIPT_URI') {
            $phpcsFile->addError("Do not use SCRIPT_URI. You probably want the cleansed REQUEST_URI instead.", $stackPtr);
        }
    }//end processVariable()


    /**
     * Processes class member variables.
     *
     * @param PHP_CodeSniffer_File $phpcsFile The file being scanned.
     * @param int                  $stackPtr  The position of the current token in the
     *                                        stack passed in $tokens.
     *
     * @return void
     */
    protected function processMemberVar(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();
        $varName = ltrim($tokens[$stackPtr]['content'], '$');
        if (!$this->isValidVariableName($varName)) {
            $phpcsFile->addError("Variable \"$varName\" is not in valid camel caps format.", $stackPtr);
        }
    }


    /**
     * Processes the variable found within a double quoted string.
     *
     * @param PHP_CodeSniffer_File $phpcsFile The file being scanned.
     * @param int                  $stackPtr  The position of the double quoted
     *                                        string.
     *
     * @return void
     */
    protected function processVariableInString(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        if (preg_match_all('|[^\\\]\$([a-zA-Z0-9_]+)|', $tokens[$stackPtr]['content'], $matches) !== 0) {
            foreach ($matches[1] as $varName) {
                if (!$this->isValidVariableName($varName)) {
                    $phpcsFile->addError("Variable \"$varName\" is not in valid camel caps format.", $stackPtr);
                }

                // using $_SERVER['SCRIPT_URI'] is bad, so we recommend using $_SERVER['REQUEST_URI'], since it is cleansed
                // assume that anything in content containing 'SCRIPT_URI' is bad
                if ($varName === '_SERVER' && strpos($tokens[$stackPtr]['content'], 'SCRIPT_URI') !== false) {
                    $phpcsFile->addError("Do not use SCRIPT_URI. You probably want the cleansed REQUEST_URI instead.", $stackPtr);
                }
            }
        }//end if

    }//end processVariableInString()

    private function isValidVariableName($varName) {
        if (in_array($varName, CPStandard_Sniffs_NamingConventions_ValidVariableNameSniff::$phpReservedVars)) {
            return true;
        }
        //We use these quite a bit and nobody has any complaints about it
        else if($varName === 'IDs' || $varName === 'ID'){
            return true;
        }
        return preg_match('@^[a-z]+(([A-Z]+|[0-9]+[A-Z]+)[a-z]*)*$@', $varName) ? true : false;
    }

    private static $phpReservedVars = array(
        '_SERVER',
        '_GET',
        '_POST',
        '_REQUEST',
        '_SESSION',
        '_ENV',
        '_COOKIE',
        '_FILES',
        'GLOBALS',
        'CI',  // I'm cheating.  We use this all over the place and I don't want to fix it.
    );

}//end class

?>
