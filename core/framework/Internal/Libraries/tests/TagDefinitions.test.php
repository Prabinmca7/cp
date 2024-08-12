<?php

use RightNow\Utils\Text;

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class TagDefinitionsTest extends CPTestCase {
    function setUp() {
        $this->tags = \RightNow\Internal\Libraries\TagDefinitions::getInstance();
    }

    function tearDown() {
        $this->tags = null;
    }

    function testTagsReturned() {
        $this->assertTrue(property_exists($this->tags, 'tags'));
        $this->assertIsA($this->tags->tags, 'array');
    }

    function testKeys() {
        foreach ($this->tags->tags as $name => $tag) {
            $this->assertTrue(Text::beginsWith($name, 'rn:'));
            $this->assertSame($name, $tag->name);
            $this->assertIsA($tag, 'RightNow\Internal\Libraries\TagDefinition');
        }
    }

    function testAttributeSorting() {
        foreach ($this->tags->tags as $name => $tag) {
            if ($tag->attributes) {
                $keys = array_values(array_map(function($a) { return $a->name; }, $tag->attributes));
                for ($i = 0; $i < count($keys) - 1; $i++) {
                    $this->assertTrue(0 > strcasecmp($keys[$i], $keys[$i + 1]), "'" . $keys[$i] . "' should be < '" . $keys[$i + 1] . "'");
                }
            }
        }
    }
}
