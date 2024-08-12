<?php

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class ChatServerConnectTest extends WidgetTestCase
{
    public $testingWidget = "standard/chat/ChatServerConnect";

    function testGetData()
    {

        $this->logIn();

        $this->createWidgetInstance();
        $data = $this->getWidgetData();

        $this->assertTrue(strstr($data['js']['maUrl'], 'ci/documents/detail/1/AvMG~wr~Dv8S~xb~Gv8e~yL~LP8q8y7~Mv8U~zr~Pv~d/6/223') !== false);
        $this->assertSame($data['js']['organizationID'], NULL);
        $this->assertSame($data['js']['contactEmail'], 'perpetualslacontactnoorg@invalid.com');
        $this->assertSame($data['js']['contactFirstName'], 'perpetual sla contact no org first');
        $this->assertSame($data['js']['contactLastName'], 'perpetual sla contact no org last');

        // Logout and make sure the contact email, first and last name are not stepped on
        $this->logOut();

        $data = $this->getWidgetData();

        $this->assertSame($data['js']['contactEmail'], 'perpetualslacontactnoorg@invalid.com');
        $this->assertSame($data['js']['contactFirstName'], 'perpetual sla contact no org first');
        $this->assertSame($data['js']['contactLastName'], 'perpetual sla contact no org last');

    }

}
