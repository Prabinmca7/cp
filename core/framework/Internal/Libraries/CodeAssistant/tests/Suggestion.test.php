<?php
require_once(CPCORE . 'Internal/Utils/CodeAssistant.php');
RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class SuggestionTest extends CPTestCase {
    public $testingClass = '\RightNow\Internal\Libraries\CodeAssistant\Suggestion';

    function testAddSnippets() {

    	$content = array(40 => 'YAHOO.util.Event.addListener(this._inputField, "blur", this._blurValidate, null, this);',
                         91 => "YAHOO.util.Dom.get('node');\r\rabc\r\r");
    	$lineNumbers = array('40', '91');
    	$suggestions = array(40 => array("link" => "http://yuilibrary.com/yui/docs/event/", "message" => "→ Yahoo.util.Event\'s functionality was replaced with \'Y.Event\'"),
                             91 => array("link" => "http://yuilibrary.com/yui/docs/node/#node-migration", "message" => "Replaced with Y.one et al"));

    	list($reflectionClass, $method) = $this->reflect('method:addSnippets');
    	$instance = $reflectionClass->newInstance();
    	$instance->addSnippets('widgets/custom/sample/SampleWidget/logic.js', $content, $lineNumbers, $suggestions);

        $createdSnippet = array(
            'type' => 'codeSnippets',
            'source' => array(
                'key' => 'cp',
                'hiddenPath' => 'customer/development/',
                'visiblePath' => 'widgets/custom/sample/SampleWidget/logic.js'
            ),
            'snippets' => array(
                array(
                    array(
                        'lineNumber' => 41,
                        'line' => 'YAHOO.util.Event.addListener(this._inputField, "blur", this._blurValidate, null, this);',
                        'marked' => true,
                        'suggestionLink' => "http://yuilibrary.com/yui/docs/event/",
                        'suggestionText' => "→ Yahoo.util.Event\'s functionality was replaced with \'Y.Event\'"
                    )
                ),
                array(
                    array(
                        'lineNumber' => 92,
                        'line' => "YAHOO.util.Dom.get('node');abc",
                        'marked' => true,
                        'suggestionLink' => "http://yuilibrary.com/yui/docs/node/#node-migration",
                        'suggestionText' => "Replaced with Y.one et al"
                    )
                )
            )
        );
        $instructions = $instance->getInstructions();
        $testSnippet = $instructions[0];
        $this->assertEqual($createdSnippet, $testSnippet);
   }
}
