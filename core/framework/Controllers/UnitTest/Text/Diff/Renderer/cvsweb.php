<?php
require_once __DIR__ . '/../Renderer.php';
class Text_Diff_Renderer_cvsweb extends Text_Diff_Renderer {
    public $actualLabel = 'Actual';
    public $expectedLabel = 'Expected';

    var $_leading_context_lines = 2;
    var $_trailing_context_lines = 2;
    var $_split_level = 'lines';

    function _startDiff() {
        return <<<DIFF
<style>
table {table-layout:fixed; width:100%;}
.blank {background-color:#888}
.deleted {color:#FFF;background-color:#006;overflow-x:auto;}
.added {color:#FFF;background-color:#060;overflow-x:auto;}
.changed {color:#FFF;background-color:#660;overflow-x:auto;}
.header {color:#FFF;background-color:#333;text-align:center;}
.context {color:#000;background-color:#FFF}
</style>
<table columns='2' width='100%' padding='3'>
<tr>
<th class='deleted'>{$this->expectedLabel}</th>
<th class='added'>{$this->actualLabel}</th>
</tr>
DIFF;
    }

    function _endDiff() {
        return "</table>\n";
    }

    function _blockHeader($xbeg, $xlen, $ybeg, $ylen) {
        return <<<BLOCKHEADER
<tr class='header'>
<td>Line $xbeg in expected:</td>
<td>Line $ybeg in actual:</td>
</tr>
BLOCKHEADER;
    }

    function _encode($text) {
        if (is_array($text)) {
            $text = join("\n", $text);
        }
        $encoded = str_replace(' ', '&nbsp;', str_replace("\n", "<br/>\n", htmlspecialchars($text)));
        return $encoded;
    }

    function _context($text) {
        $text = $this->_encode($text);
        return <<<CONTEXT
<tr class='context'>
<td>
$text
</td>
<td>
$text
</td>
</tr>
CONTEXT;
    }

    function _changed($expected, $actual) {
        $expected = $this->_encode($expected);
        $actual = $this->_encode($actual);
        return <<<CHANGED
<tr>
<td class='changed'>
$expected
</td>
<td class='changed'>
$actual
</td>
</tr>
CHANGED;
    }

    function _added($added) {
        $added = $this->_encode($added);
        return <<<CHANGED
<tr>
<td class='blank'>
</td>
<td class='added'>
$added
</td>
</tr>
CHANGED;
    }

    function _deleted($deleted) {
        $deleted = $this->_encode($deleted);
        return <<<CHANGED
<tr>
<td class='deleted'>
$deleted
</td>
<td class='blank'>
</td>
</tr>
CHANGED;
    }

}
