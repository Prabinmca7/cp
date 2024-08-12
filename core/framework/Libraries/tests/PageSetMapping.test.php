<?php
\RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

use RightNow\Libraries\PageSetMapping;

class PageSetMappingTest extends CPTestCase {
    function testGetString() {
        $pageSet = new PageSetMapping(array(
            'id' => 1,
            'item' => '/Android/i',
            'description' =>'Android',
            'value' => 'mobile',
            'enabled' => true,
            'locked' => false,
        ));
        $expected = "1 => new \RightNow\Libraries\PageSetMapping(array('id' => 1, 'item' => '/Android/i', 'description' => 'Android', 'value' => 'mobile', 'enabled' => true, 'locked' => false))";
        $this->assertIdentical($expected, (string) $pageSet);
    }

    function testEscapingQuotes() {
        $pageSet = new PageSetMapping(array(
            'id' => 1,
            'item' => "/Android's/i",
            'description' => "Android's",
            'value' => "mobile's",
            'locked' => false,
            'enabled' => true
        ));
        $expected = "1 => new \RightNow\Libraries\PageSetMapping(array('id' => 1, 'item' => '/Android\'s/i', 'description' => 'Android\'s', 'value' => 'mobile\'s', 'enabled' => true, 'locked' => false))";
        $this->assertIdentical($expected, $pageSet . '');
    }

    function testAccessingMembers() {
        $mapping = new PageSetMapping(array(
            'id' => 1,
            'item' => '/iphone/i',
            'value' => 'iphone',
            'description' => 'smart phone'
        ));
        $this->assertIdentical(1, $mapping->id);
        $this->assertIdentical('/iphone/i', $mapping->item);
        $this->assertIdentical('smart phone', $mapping->description);
        $this->assertIdentical('iphone', $mapping->value);
        $this->assertFalse($mapping->locked);
        $this->assertTrue($mapping->enabled);
        try {
            $mapping->banana;
            $this->fail('exception should get hit');
        }
        catch (\Exception $e) {
            $this->pass();
        }
    }

    function testToArray() {
        $mapping = new PageSetMapping(array(
            'id' => 1,
            'item' => '/iphone/i',
            'value' => 'iphone',
            'description' => 'smart phone',
            'locked' => 0,
            'enabled' => 1,
        ));
        $result = $mapping->toArray();
        $this->assertIdentical(1, $result['id']);
        $this->assertIdentical('/iphone/i', $result['item']);
        $this->assertIdentical('smart phone', $result['description']);
        $this->assertIdentical('iphone', $result['value']);
        $this->assertIdentical(0, $result['locked']);
        $this->assertIdentical(1, $result['enabled']);
    }

    function testDefaultValues() {
        $mapping = new PageSetMapping(array());
        $result = $mapping->toArray();
        $this->assertIdentical(null, $result['id']);
        $this->assertIdentical(null, $result['description']);
        $this->assertIdentical(null, $result['item']);
        $this->assertIdentical(null, $result['value']);
        $this->assertFalse($result['locked']);
        $this->assertTrue($result['enabled']);
    }

    function testGetters() {
        $mapping = new PageSetMapping(array(
            'id' => 1,
            'item' => '/iphone/i',
            'value' => 'iphone',
            'description' => 'smart phone',
            'locked' => 0,
            'enabled' => 1,
        ));
        $this->assertIdentical(1, $mapping->getID());
        $this->assertIdentical('/iphone/i', $mapping->getItem());
        $this->assertIdentical('smart phone', $mapping->getDescription());
        $this->assertIdentical('iphone', $mapping->getValue());
        $this->assertIdentical(0, $mapping->getLocked());
        $this->assertIdentical(1, $mapping->getEnabled());
        try {
            $mapping->getBanana();
            $this->fail("Exception should've been hit");
        }
        catch (\Exception $e) {
            $this->pass();
        }
    }
}
