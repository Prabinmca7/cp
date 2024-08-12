<?php
RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class ChatSqlTest extends CPTestCase
{
    public $testingClass = 'RightNow\Internal\Sql\Chat';

    public function XtestIsChatHoliday()
    {
        //Non-string values
        $method = $this->getMethod('isChatHoliday');
        $result = $method('','','');
        $this->assertFalse($result);

        //Legitimate but missing values
        $result = $method(7,17,1923);
        $this->assertFalse($result);

        $updateConfig = function($value) {
            return Rnow::updateConfig(CS_HOLIDAY_MSG_ENABLED, $value);
        };
        $holidayConfigValue = $updateConfig(true);

        //Legitimate values, so the config is enabled and the SQL query executed, however,
        //a legitimate holiday can't be tested because one isn't visible in the default
        //data set.
        $this->assertFalse($this->invokeChatHolidayRemote(7,17,1927));

        $updateConfig($holidayConfigValue);
    }

    public function testGetChatHoursAvailability()
    {
        $expectedResult = array();
        for($i = 0; $i < 7; $i++)
            $expectedResult[$i] = array('startTime' => mktime(0,0,0, 1, 1, 2000), 'endTime' => mktime(0,0,0, 1, 2, 2000));

        $method = $this->getMethod('getChatHoursAvailability');
        $result = $method();

        foreach($result as $key => $value)
        {
            $this->assertTrue(isset($expectedResult[$key]));
            if(isset($expectedResult[$key]))
            {
                $this->assertTrue($expectedResult[$key]['startTime'] == $value['startTime']);
                $this->assertTrue($expectedResult[$key]['endTime'] == $value['endTime']);
            }
        }
    }

    private function invokeChatHolidayRemote($month, $day, $year)
    {
        return json_decode(
            $this->makeRequest("/ci/unitTest/wgetRecipient/invokeCompatibilitySQLFunction/Chat/isChatHoliday/$month/$day/$year")
        );
    }

    function testGetAllMenuItems()
    {
        $method = $this->getMethod('getAllMenuItems');
        $menuItems = $method();
        $this->assertIsA($menuItems, 'array');
        $this->assertNotEqual(0, count($menuItems));
    }
}
