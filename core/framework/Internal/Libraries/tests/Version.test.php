<?php
RightNow\UnitTest\Helper::loadTestedFile(__FILE__);
class VersionClassTest extends CPTestCase
{
    function __construct()
    {
        $this->versionName = 'August 2010';
        $this->versionNumber = '10.8';
        $this->digits = array(10, 8, 0, 0, 0);
    }

    function testVersionNumberInstantiation()
    {
        $v = new \RightNow\Internal\Libraries\Version($this->versionNumber);
        $this->assertEqual($this->versionNumber, $v->version);
        $this->assertEqual($this->versionName, $v->versionName);
    }

    function testVersionNameInstantiation()
    {
        $v = new \RightNow\Internal\Libraries\Version($this->versionName);
        $this->assertEqual($this->versionNumber, $v->version);
        $this->assertEqual($this->versionName, $v->versionName);
    }

    function testInvalidVersionInstantiation()
    {
        //$this->expectError($v = new Version('100.13');
        try {
            $v = new \RightNow\Internal\Libraries\Version('100.13');
        }
        catch (\Exception $e) {
            //echo $e;
            //TODO: assert Version specific exception is raised.
        }
    }

    function testVersionComparisons()
    {
        $v = new \RightNow\Internal\Libraries\Version($this->versionNumber);

        $lesserVersion = new \RightNow\Internal\Libraries\Version('8.2.0.1-b107h-mysql');
        $this->assertTrue($v->greaterThan('8.2'));
        $this->assertTrue($v->greaterThan($lesserVersion));
        $this->assertTrue($v->digits > $lesserVersion->digits);

        $greaterVersion = new \RightNow\Internal\Libraries\Version('99.11');
        $this->assertTrue($v->lessThan('99.11'));
        $this->assertTrue($v->lessThan($greaterVersion));
        $this->assertTrue($v->digits < $greaterVersion->digits);

        $sameVersion = new \RightNow\Internal\Libraries\Version($this->versionNumber);
        $this->assertTrue($v->equals($this->versionNumber));
        $this->assertTrue($v->equals($sameVersion));
        $this->assertTrue($v->digits === $sameVersion->digits);
    }

    function testDigits()
    {
        $v = new \RightNow\Internal\Libraries\Version($this->versionNumber);
        $this->assertEqual($this->digits, $v->digits);
    }

    function testYear()
    {
        $v = new \RightNow\Internal\Libraries\Version('10.2');
        $this->assertEqual(10, $v->year);
        $this->assertEqual(2010, $v->fullYear);

        $v = new \RightNow\Internal\Libraries\Version('9.2');
        $this->assertEqual(9, $v->year);
        $this->assertEqual(2009, $v->fullYear);
    }

    function testMonth()
    {
        $v = new \RightNow\Internal\Libraries\Version('10.2');
        $this->assertEqual(2, $v->month);
        $this->assertEqual('February', $v->monthName);
    }

    function testToVersionWithVersion()
    {
        $v = new \RightNow\Internal\Libraries\Version($this->versionNumber);
        $v2 = \RightNow\Internal\Libraries\Version::toVersion($v);
        $this->assertTrue($v === $v2, 'Objects are not the same instance.');
        $this->assertReference($v, $v2);
        $this->assertEqual($v->version, $v2->version);
    }

    function testToVersionWithNumber()
    {
        $v = new \RightNow\Internal\Libraries\Version($this->versionNumber);
        $v2 = \RightNow\Internal\Libraries\Version::toVersion($this->versionNumber);
        $this->assertTrue($v !== $v2, 'Objects are the same instance.');
        $this->assertEqual($v, $v2);
        $this->assertEqual($v->version, $v2->version);
    }

    function testToVersionWithName()
    {
        $v = new \RightNow\Internal\Libraries\Version($this->versionName);
        $v2 = \RightNow\Internal\Libraries\Version::toVersion($this->versionName);
        $this->assertTrue($v !== $v2, 'Objects are the same instance.');
        $this->assertEqual($v, $v2);
        $this->assertEqual($v->version, $v2->version);
    }
}
