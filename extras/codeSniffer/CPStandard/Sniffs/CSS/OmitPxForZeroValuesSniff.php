<?php
/**
 * CPStandard_Sniffs_CSS_OmitPxForZeroValues.
 *
 * Ensure that a value of 0 within a CSS file does not have 'px' set afterwards
 *
 */
class CPStandard_Sniffs_CSS_OmitPxForZeroValuesSniff implements PHP_CodeSniffer_Sniff
{

    /**
     * A list of tokenizers this sniff supports.
     *
     * @var array
     */
    public $supportedTokenizers = array('CSS');


    /**
     * Returns the token types that this sniff is interested in.
     *
     * @return array(int)
     */
    public function register()
    {
        return array(T_STYLE);

    }//end register()


    /**
     * Processes the tokens that this sniff is interested in.
     *
     * @param PHP_CodeSniffer_File $phpcsFile The file where the token was found.
     * @param int                  $stackPtr  The position in the stack where
     *                                        the token was found.
     *
     * @return void
     */
    public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();
        $next    = $phpcsFile->findNext(array(T_COLON, T_WHITESPACE), ($stackPtr + 1), null, true);
        $numbers = array(T_DNUMBER, T_LNUMBER);

        //If there are no numbers in this style rule, then bail
        if ($next === false || in_array($tokens[$next]['code'], $numbers) === false) {
            return;
        }

        //Go over each token in the rule that is a number, and stop when we hit a semicolon
        while($next !== false && $tokens[$next]['code'] !== T_SEMICOLON){
            $value = $tokens[$next]['content'];
            if ($tokens[$next]['code'] === T_LNUMBER && $value === '0') {
                $pixelSpecifier = $tokens[$next+1];
                if($pixelSpecifier['code'] === T_STRING || $pixelSpecifier['code'] === T_MODULUS){
                    $phpcsFile->addError("Sizes of 0 do not need size specifiers, found 0" . $pixelSpecifier['content'], $next);
                }
            }
            $next = $phpcsFile->findNext(array(T_DNUMBER, T_LNUMBER, T_SEMICOLON), ($next+1));
        }
    }
}
