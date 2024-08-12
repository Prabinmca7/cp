<?php
/**
 * Calls to parseInt within JS should always specify 10 as the second parameter. This function ensures that happens
 */
class CPStandard_Sniffs_Functions_ParseIntParameterCheckSniff implements PHP_CodeSniffer_Sniff
{

    /**
     * A list of tokenizers this sniff supports. We only scan JS files in this sniff for obvious reasons
     *
     * @var array
     */
    public $supportedTokenizers = array(
                                   'JS',
                                  );


    /**
     * Since we don't have a T_FUNCTION_CALL token, we have to just scan for T_STRING which is what
     * it considers the call to parseInt
     *
     * @return array
     */
    public function register()
    {
        return array(T_STRING);

    }

    /**
     * Process the string and ensure it's a correctly formatted parseInt call
     *
     * @param PHP_CodeSniffer_File $phpcsFile The current file being checked.
     * @param int                  $stackPtr  The position of the current token in
     *                                        the stack passed in $tokens.
     *
     * @return void
     */
    public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        //String isn't a call to parseInt, bail
        if($tokens[$stackPtr]['content'] !== 'parseInt'){
            return;
        }

        //Found a parseInt call. Now try to parse it to ensure it specifies the second parameter. Our (non-bulletproof) solution is to look for
        //the following and fail if any of them aren't found:
        // - A comma on the same line as the parseInt call.
        // - A number after the comma that equals '10'.
        // - A closing parent after the number

        //Look for a comma on the current line
        $commaLocation = $phpcsFile->findNext(T_COMMA, ($stackPtr + 1), null, false, null, true);

        if($tokens[$commaLocation]['code'] !== T_COMMA){
            $phpcsFile->addError("Found parseInt() call which doesn't specify 10 as it's second parameter.", $stackPtr, 'InvalidSignature');
            return;
        }

        //Comma found, now find a number after on the same line
        $numericSecondParameterLocation = $phpcsFile->findNext(T_LNUMBER, ($commaLocation + 1), null, false, null, true);

        if($tokens[$numericSecondParameterLocation]['code'] !== T_LNUMBER){
            $phpcsFile->addError("Found parseInt() call which doesn't specify 10 as it's second parameter.", $commaLocation, 'InvalidSignature');
            return;
        }

        //Number found, now check if the value is 10 (yes, I know 10 isn't the only valid value you can pass, but it's the only one we use, so force it until we need it to be more lenient)
        if($tokens[$numericSecondParameterLocation]['content'] !== '10'){
            $phpcsFile->addError("Found parseInt() call which doesn't specify 10 as it's second parameter.", $numericSecondParameterLocation, 'InvalidSignature');
            return;
        }

        //Command and 10 found, next token should be a close paren
        if($tokens[$numericSecondParameterLocation+1]['code'] !== T_CLOSE_PARENTHESIS){
            $phpcsFile->addError("Found parseInt() call which doesn't specify 10 as it's second parameter.", $numericSecondParameterLocation, 'InvalidSignature');
            return;
        }
    }
}