<?php

use RightNow\Utils\Date,
    RightNow\Api;

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class DateTest extends CPTestCase {
    
    public $testingClass = 'Rightnow\Utils\Date';
    
    public function testValidate() {
        $method = $this->getMethod('validate');
        $this->assertTrue($method('2015-02-27 23:24:25'));
        $this->assertFalse($method('2015-02-29 23:24:25'));   
    }
    
    public function testDateAdd() {
        $this->assertEqual('2015-03-05T00:00:00Z', Date::add('2015-02-26 23:24:25', 7, 'day', 1));
        $this->assertEqual('2016-02-19T00:00:00Z', Date::add('2016-02-26 23:24:25', -7, 'day', 1));
        
        $this->assertEqual('2015-01-27T06:24:25Z', Date::add('2015-01-26 23:24:25', 7, 'hour', 0));
        $this->assertEqual('2016-01-26T16:24:25Z', Date::add('2016-01-26 23:24:25', -7, 'hour', 0));
        
        $this->assertEqual('2015-03-26T23:24:25Z', Date::add('2015-01-26 23:24:25', 2, 'month', 0));
        $this->assertEqual('2015-11-26T23:24:25Z', Date::add('2016-01-26 23:24:25', -2, 'month', 0));
        
        $this->assertEqual('2015-02-08T00:00:00Z', Date::add('2015-01-26 23:24:25', 2, 'week', 1));
        $this->assertEqual('2016-01-10T00:00:00Z', Date::add('2016-01-26 23:24:25', -2, 'week', 1));
        // null value for invalid date
        $this->assertEqual(null, Date::add('2016-01-32 23:24:25', -2, 'week', 1));
    }
    
    public function testDateTrunc() {
        $this->assertEqual('2015-02-26T23:24:00Z', Date::trunc('2015-02-26 23:24:25', 'minute'));
        $this->assertEqual('2015-02-26T23:00:00Z', Date::trunc('2015-02-26 23:24:25', 'hour'));
        $this->assertEqual('2015-01-26T00:00:00Z', Date::trunc('2015-01-26 23:24:25', 'day'));
        $this->assertEqual('2015-01-25T00:00:00Z', Date::trunc('2015-01-26 23:24:25', 'week'));
        $this->assertEqual('2016-01-24T00:00:00Z', Date::trunc('2016-01-26 23:24:25', 'week'));
        $this->assertEqual('2015-01-01T00:00:00Z', Date::trunc('2015-01-26 23:24:25', 'month'));
        $this->assertEqual('2016-01-01T00:00:00Z', Date::trunc('2016-01-26 23:24:25', 'month'));
        $this->assertEqual('2016-01-01T00:00:00Z', Date::trunc('2016-08-12 23:24:25', 'year'));
        // null value for invalid date
        $this->assertEqual(null, Date::trunc('2016-08-26 25:24:25', 'year'));
    }
    
    public function testDateDiff() {
        $this->assertEqual(10, Date::diff('2015-02-26 23:24:25', '2015-02-26 23:24:35'));
        $this->assertEqual(-10, Date::diff('2015-02-26 23:24:25', '2015-02-26 23:24:15'));
        $this->assertEqual(7685990, Date::diff('2015-02-26 23:24:25', '2015-05-26 23:24:15'));
        // invalid date
        $this->assertEqual(0, Date::diff('2015-02-26 28:24:25', '2015-05-34 23:24:15'));
    }

    public function testFormatTimestamp() {
        $yesterday = time() - (24 * 60 * 60);
        $args = Date::formatTimestamp(gmdate("Y-m-d\TH:i:s\Z", $yesterday),'m/d/Y');
        $this->assertIdentical('Yesterday', $args);
    }

    public function testGetDateFormat() {
        $args = Date::getDateFormat('full_textual');
        $this->assertIdentical('F jS, Y', $args);

        $args = Date::getDateFormat('short_textual');
        $this->assertIdentical('M d, Y', $args);

        $args = Date::getDateFormat('numeric');
        $this->assertIdentical('m/d/Y', $args);
    }

    public function testGetDateObject() {
        $timestamp = 1588720305;
        $this->assertEqual(new \DateTime('2051-02-26 23:24:25'), Date::getDateObject('2051-02-26 23:24:25'));
        $this->assertEqual(new \DateTime("@$timestamp"), Date::getDateObject($timestamp));
        $this->assertEqual((new \DateTime())->setTimestamp($timestamp), Date::getDateObject($timestamp));

        // DateTime objects created with timestamp and date strings are equivalent
        $this->assertEqual((new \DateTime("@$timestamp")), Date::getDateObject('2020-05-05 17:11:45'));
        $this->assertEqual(new \DateTime('2020-05-05 17:11:45'), Date::getDateObject($timestamp));

        // timestamps work when passed as strings
        $this->assertEqual(new \DateTime("@$timestamp"), Date::getDateObject("$timestamp"));

        //2040
        $this->assertEqual(new \DateTime("@2208992461"), Date::getDateObject(2208992461));
    }

    public function testCanParse() {
        $timestamp = 1588720305;
        $dateString = '2015-02-26 23:24:25';
        $garbage = 'not a date';

        $this->assertTrue(Date::canParse($timestamp));
        $this->assertTrue(Date::canParse("$timestamp"));
        $this->assertTrue(Date::canParse($dateString));
        $this->assertTrue(Date::canParse($timestamp, "$timestamp", $dateString));

        $this->assertFalse(Date::canParse(null));
        $this->assertFalse(Date::canParse(false));
        $this->assertFalse(Date::canParse(''));
        $this->assertFalse(Date::canParse(array()));
        $this->assertFalse(Date::canParse($garbage));
        $this->assertFalse(Date::canParse($dateString, null));
        $this->assertFalse(Date::canParse(null, $dateString));
    }

    public function testConvertStrftimeFormatString() {
        // supported values are listed at https://cx.rightnow.com/app/answers/detail/a_id/2392
        // note that %j is excluded because there is not a one-based day-of-the-year specifier for PHP
        $search = array("%a","%A","%b","%B","%d","%D","%H","%I","%m","%M","%n","%p","%r","%R","%S","%t","%T","%y","%Y","%z","%Z");
        $timestamp = 1588720305;

        foreach($search as $value) {
            // date() uses the same format strings as DateTime->format()
            $this->assertEqual(strftime($value, $timestamp), date(Date::convertStrftimeFormatString($value), $timestamp));
        }
    }
}

