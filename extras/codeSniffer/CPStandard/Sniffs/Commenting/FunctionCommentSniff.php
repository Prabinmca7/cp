<?php
if (class_exists('PHP_CodeSniffer_CommentParser_FunctionCommentParser', true) === false) {
    $error = 'Class PHP_CodeSniffer_CommentParser_FunctionCommentParser not found';
    throw new PHP_CodeSniffer_Exception($error);
}
/**
 * CPStandard_Sniffs_Commenting_FunctionCommentSniff.
 *
 * Extends Squiz rule in order to inherit and provide the following function comment checks:
 * CPStandard.Commenting.FunctionComment.
 *     Missing
 *     WrongStyle - Not /**
 *     WrongEnd - Not star + slash
 *     FailedParse
 *     Empty
 *     ContentAfterOpen - /** must be on its own line
 *     MissingShort - Must have a function description
 *     SpacingBeforeShort - Must start on its own line
 *     SpacingBetween - Onle line between short and long descriptions
 *     LongNotCapital - Description must be capitalized
 *     SpacingBeforeTags - One empty line before tags
 *     ShortSingleLine - Short description must be on a single line
 *     ShortNotCapital - Short description must be capitalized
 *     ShortFullStop - Require class comment to end in a period
 *     ParamCommentFullStop - Require all @param comments to end in a period
 *     DuplicateReturn - More than one @return tag
 *     ReturnOrder - @return tag appears before @see
 *     MissingReturnType
 *     InvalidReturn - Invalid / unknown return type
 *     InvalidReturnVoid - Function contains return when return type is void
 *     InvalidReturnNotVoid - Function return type is void but function returns non-void
 *     InvalidNoReturn - Function doesn't have any returns when @return says it returns something
 *     ReturnIndent - Indentation is other than one space
 *     MissingReturn - No @return found
 *     ReturnNotRequired - @return is included for a constructor/destructor when it shouldn't be
 *     InvalidThrows - Exception type and comment are missing from @throws
 *     EmptyThrows - No description for @throws
 *     ThrowsNotCapital - Comment must be capitalized
 *     ThrowsNoFullStop - Comment tag must end with a full stop
 *     ThrowsOrder - Must appear before @return
 *     SpacingAfterParams - Last param comment requires an empty line after it
 *     SpacingBeforeParams - Params must appear before the comment
 *     SpacingBeforeParamType - One space before type
 *     ParameterNamesNotAligned
 *     ParameterCommentsNotAligned
 *     ParamNameNoMatch
 *     IncorrectParamVarName
 *     TypeHintMissing
 *     IncorrectTypeHint
 *     InvalidTypeHint
 *     ExtraParamComment
 *     MissingParamName
 *     MissingParamType
 *     MissingParamComment
 *     ParamCommentNotCapital
 *     ParamCommentFullStop
 *     SpacingAfterLongType - Expects one space after the longest type
 *     SpacingAfterLongName - Expects one space after the longest var name
 *     MissingParamTag
 *     SeeIndent
 * But:
 * - Ignores constructor/destructor methods
 * - Doesn't require `@return void` if the function doesn't return anything.
 */
class CPStandard_Sniffs_Commenting_FunctionCommentSniff extends Squiz_Sniffs_Commenting_FunctionCommentSniff
{
    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param PHP_CodeSniffer_File $phpcsFile The file being scanned.
     * @param int                  $stackPtr  The position of the current token
     *                                        in the stack passed in $tokens.
     *
     * @return void
     */
    public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        $methodName = $phpcsFile->getDeclarationName($stackPtr);
        //We don't require function comments for constructor/destructor methods
        if($methodName === '__construct' || $methodName === '__destruct'){
            return;
        }
        parent::process($phpcsFile, $stackPtr);
    }

    /**
     * Process the return comment of this function comment.
     *
     * @param int $commentStart The position in the stack where the comment started.
     * @param int $commentEnd   The position in the stack where the comment ended.
     *
     * @return void
     */
    protected function processReturn($commentStart, $commentEnd)
    {
        // Skip constructor and destructor.
        $className = '';
        if ($this->_classToken !== null) {
            $className = $this->currentFile->getDeclarationName($this->_classToken);
            $className = strtolower(ltrim($className, '_'));
        }

        $methodName      = strtolower(ltrim($this->_methodName, '_'));
        $isSpecialMethod = ($this->_methodName === '__construct' || $this->_methodName === '__destruct');
        $return          = $this->commentParser->getReturn();

        if ($isSpecialMethod === false && $methodName !== $className) {
            if ($return !== null) {
                $tagOrder = $this->commentParser->getTagOrders();
                $index    = array_keys($tagOrder, 'return');
                $errorPos = ($commentStart + $return->getLine());
                $content  = trim($return->getRawContent());

                if (count($index) > 1) {
                    $error = 'Only 1 @return tag is allowed in function comment';
                    $this->currentFile->addError($error, $errorPos, 'DuplicateReturn');
                    return;
                }

                $since = array_keys($tagOrder, 'since');
                if (count($since) === 1 && $this->_tagIndex !== 0) {
                    $this->_tagIndex++;
                    if ($index[0] !== $this->_tagIndex) {
                        $error = 'The @return tag is in the wrong order; the tag follows @see (if used)';
                        $this->currentFile->addError($error, $errorPos, 'ReturnOrder');
                    }
                }

                if (empty($content) === true) {
                    $error = 'Return type missing for @return tag in function comment';
                    $this->currentFile->addError($error, $errorPos, 'MissingReturnType');
                } else {
                    // Check return type (can be multiple, separated by '|').
                    $typeNames      = explode('|', $content);
                    $suggestedNames = array();
                    foreach ($typeNames as $i => $typeName) {
                        $suggestedName = PHP_CodeSniffer::suggestType($typeName);
                        if (in_array($suggestedName, $suggestedNames) === false) {
                            $suggestedNames[] = $suggestedName;
                        }
                    }

                    $suggestedType = implode('|', $suggestedNames);
                    if ($content !== $suggestedType) {
                        $error = 'Function return type "%s" is invalid';
                        $data  = array($content);
                        $this->currentFile->addError($error, $errorPos, 'InvalidReturn', $data);
                    }

                    $tokens = $this->currentFile->getTokens();

                    // If the return type is void, make sure there is
                    // no return statement in the function.
                    if ($content === 'void') {
                        if (isset($tokens[$this->_functionToken]['scope_closer']) === true) {
                            $endToken = $tokens[$this->_functionToken]['scope_closer'];

                            $tokens = $this->currentFile->getTokens();
                            for ($returnToken = $this->_functionToken; $returnToken < $endToken; $returnToken++) {
                                if ($tokens[$returnToken]['code'] === T_CLOSURE) {
                                    $returnToken = $tokens[$returnToken]['scope_closer'];
                                    continue;
                                }

                                if ($tokens[$returnToken]['code'] === T_RETURN) {
                                    break;
                                }
                            }

                            if ($returnToken !== $endToken) {
                                // If the function is not returning anything, just
                                // exiting, then there is no problem.
                                $semicolon = $this->currentFile->findNext(T_WHITESPACE, ($returnToken + 1), null, true);
                                if ($tokens[$semicolon]['code'] !== T_SEMICOLON) {
                                    $error = 'Function return type is void, but function contains return statement';
                                    $this->currentFile->addError($error, $errorPos, 'InvalidReturnVoid');
                                }
                            }
                        }
                    } else if ($content !== 'mixed') {
                        // If return type is not void, there needs to be a
                        // returns statement somewhere in the function that
                        // returns something.
                        if (isset($tokens[$this->_functionToken]['scope_closer']) === true) {
                            $endToken    = $tokens[$this->_functionToken]['scope_closer'];
                            $returnToken = $this->currentFile->findNext(T_RETURN, $this->_functionToken, $endToken);
                            if ($returnToken === false) {
                                $error = 'Function return type is not void, but function has no return statement';
                                $this->currentFile->addError($error, $errorPos, 'InvalidNoReturn');
                            } else {
                                $semicolon = $this->currentFile->findNext(T_WHITESPACE, ($returnToken + 1), null, true);
                                if ($tokens[$semicolon]['code'] === T_SEMICOLON) {
                                    $error = 'Function return type is not void, but function is returning void here';
                                    $this->currentFile->addError($error, $returnToken, 'InvalidReturnNotVoid');
                                }
                            }
                        }
                    }//end if

                    $spacing = substr_count($return->getWhitespaceBeforeValue(), ' ');
                    if ($spacing !== 1) {
                        $error = '@return tag indented incorrectly; expected 1 space but found %s';
                        $data  = array($spacing);
                        $this->currentFile->addError($error, $errorPos, 'ReturnIndent', $data);
                    }
                }//end if
            } else {
                // CPStandard START
                // The Squiz standard always requires a `@require type`, but it's
                // silly to require `@require void` for functions that don't return anything.
                $tokens = $this->currentFile->getTokens();
                if (isset($tokens[$this->_functionToken]['scope_closer']) === true) {
                    $endToken    = $tokens[$this->_functionToken]['scope_closer'];
                    $returnToken = $this->currentFile->findNext(T_RETURN, $this->_functionToken, $endToken);
                    if ($returnToken !== false) {
                        $error = 'Missing @return tag in function comment';
                        $this->currentFile->addError($error, $commentEnd, 'MissingReturn');
                    }
                }
                // CPStandard END

            }//end if

        } else {
            // No return tag for constructor and destructor.
            if ($return !== null) {
                $errorPos = ($commentStart + $return->getLine());
                $error    = '@return tag is not required for constructor and destructor';
                $this->currentFile->addError($error, $errorPos, 'ReturnNotRequired');
            }
        }//end if

    }//end processReturn()
}