<?php

namespace RightNow\Internal\Libraries\CodeAssistant;

require_once CPCORE . 'Internal/Libraries/CodeAssistant/OperationContext.php';

/**
 * Used for any Code Assistant operations which are type `suggestion`.
 */
class Suggestion extends OperationContext {
    /**
     * Given an array of lines in a file and the associated line numbers for the desired snippets this function
     * generates a set of snippets which will cover all of those lines. Multiple adjacent `marked` lines are
     * collapsed into a single snippet, and no two marked lines are repeated. Suggestions for converting YUI2
     * code to YUI3 are added along with the marked lines.
     * @param string $path Path to source file
     * @param array $content The file content split by line number
     * @param array $lineNumbers The list of line numbers that should be highlighted.
     * @param array $suggestions The list of suggestion texts and links.
     */
    public function addSnippets($path, array $content, array $lineNumbers, array $suggestions) {
        $markedLines = array_flip($lineNumbers);
        $coveredLines = array();

        $allSnippets = array();
        foreach($lineNumbers as $lineNumber) {
            if($coveredLines[$lineNumber]) continue;

            $snippet = array();
            for($i = $lineNumber - 1, $lastLine = $lineNumber + 1; $i < $lastLine; $i++) {
                if(isset($content[$i])) {
                    $marked = isset($markedLines[$i]);
                    $coveredLines[$i] = true;
                    if($marked) {
                        $suggestionsLink = $suggestions[$i]['link'];
                        $suggestionsText = $suggestions[$i]['message'];
                    }

                    $snippet[] = array(
                        'lineNumber' => $i + 1,
                        'line' => str_replace("\r", "", $content[$i]),
                        'marked' => $marked,
                        'suggestionLink' => $suggestionsLink ?: null,
                        'suggestionText' => $suggestionsText ?: null
                    );

                    if($marked) {
                        $lastLine++;
                    }
                }
            }

            if(count($snippet)) {
                $allSnippets[] = $snippet;
            }
        }

        if(count($allSnippets)) {
            $this->addInstruction('codeSnippets', array('source' => $this->createPathObject($this->normalizePath($path)), 'snippets' => $allSnippets));
        }
    }
}