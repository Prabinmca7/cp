<?php
/**
 * CPStandard_Sniffs_CSS_DuplicateStyleDefinitionSniff.
 *
 * PHP version 5
 */

/**
 * CPStandard_Sniffs_CSS_DuplicateStyleDefinitionSniff.
 *
 * Check for duplicate style definitions in the same class, but allows for vendor prefix rule and IE hack duplication
 */
class CPStandard_Sniffs_CSS_DuplicateStyleDefinitionSniff implements PHP_CodeSniffer_Sniff
{

    /**
     * A list of tokenizers this sniff supports.
     *
     * @var array
     */
    public $supportedTokenizers = array('CSS');


    public $allowedDuplicatePrefixes = array('-moz-', '-webkit-', '-ms-', '-o-', 'linear-gradient');

    /**
     * Returns the token types that this sniff is interested in.
     *
     * @return array(int)
     */
    public function register()
    {
        return array(T_OPEN_CURLY_BRACKET);

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

        // Find the content of each style definition name.
        $end  = $tokens[$stackPtr]['bracket_closer'];
        $next = $phpcsFile->findNext(T_STYLE, ($stackPtr + 1), $end);
        if ($next === false) {
            // Class definition is empty.
            return;
        }

        $styleNames = array();

        while ($next !== false) {
            $name = $tokens[$next]['content'];
            if (isset($styleNames[$name]) === true) {
                if(!$this->shouldWeAllowDuplicateRuleException($name, $phpcsFile, $tokens, $next)){
                    $first = $styleNames[$name];
                    $error = 'Duplicate style definition found; first defined on line %s';
                    $data  = array($tokens[$first]['line']);
                    $phpcsFile->addError($error, $next, 'Found', $data);
                }
            }
            else {
                $styleNames[$name] = $next;
            }

            $next = $phpcsFile->findNext(T_STYLE, ($next + 1), $end);
        }
    }

    private function shouldWeAllowDuplicateRuleException($name, $phpcsFile, $tokens, $next){
        //Allow duplicate height/width rules to get browser support for the min/max-width/height rules
        if($tokens[$next]['content'] === 'width' || $tokens[$next]['content'] === 'height'){
            return true;
        }
        //Allow IE hacks such as *padding: ...
        if($tokens[$next-1]['code'] === T_MULTIPLY){
            return true;
        }

        //We some complex style definitions in some places that are hard to parse for
        if($name === 'text-shadow' || $name === 'background'){
            return true;
        }

        //Allow duplicate if the value uses a style prefix
        $duplicateRuleIndex = $phpcsFile->findNext(array(T_COLON, T_WHITESPACE), ($next + 1), null, true);
        $duplicateRuleValue = $tokens[$duplicateRuleIndex]['content'];
        //Didn't find a value, sad, guess we can't allow it
        if($tokens[$duplicateRuleIndex]['code'] !== T_STRING){
            return false;
        }

        foreach($this->allowedDuplicatePrefixes as $prefix){
            if(stripos($duplicateRuleValue, $prefix) !== false){
                return true;
            }
        }
        return false;
    }
}

?>
