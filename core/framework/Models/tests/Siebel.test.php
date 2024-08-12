<?php
RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

use RightNow\Connect\Knowledge\v1 as KnowledgeFoundation,
    RightNow\Connect\v1_4 as Connect,
    RightNow\Libraries\Hooks,
    RightNow\UnitTest\Helper,
    RightNow\Utils\Text;

class SiebelTest extends CPTestCase {
    public $testingClass = 'RightNow\Models\Siebel';
    protected $hookEndpointClass = __CLASS__;
    protected $hookEndpointFilePath = __FILE__;
    private static $siebelHost = array('slc05ptd.us.oracle.com', 'slc04wlr.us.oracle.com');

    function testProcessRequest(){
        $formData = array();

        $incident = new Connect\Incident();
        $incident->PrimaryContact = new Connect\Contact();
        $incident->PrimaryContact->Emails = new Connect\EmailArray();
        $incident->PrimaryContact->Emails[] = new Connect\Email();
        $incident->PrimaryContact->Emails[0]->AddressType = new Connect\NamedIDOptList();
        $incident->PrimaryContact->Emails[0]->AddressType->ID = CONNECT_EMAIL_PRIMARY;
        $incident->PrimaryContact->Emails[0]->Address = 'j@oracle.com.invalid';
        $incident->Subject = 'subject';
        $incident->Threads = new Connect\ThreadArray();
        $incident->Threads[] = new Connect\Thread();
        $incident->Threads[0]->ContentType->LookupName = 'text/plain';
        $incident->Threads[0]->Text = 'thread';

        $hookData = array('formData' => $formData, 'incident' => $incident, 'shouldSave' => true);

        $this->setHooks(array(
            array('name' => 'pre_incident_create_save', 'class' => 'Siebel', 'function' => 'processRequest', 'filepath' => '', 'use_standard_model' => true),
        ));

        $incident->Subject = 'subject' . str_pad('.', 100, '.');

        $result = Hooks::callHook('pre_incident_create_save', $hookData);
        $this->assertFalse($hookData['shouldSave']);
        $this->assertIdentical($result, "The subject of your question is too long (max length is 100). Please shorten it.");
        $this->assertIdentical($hookData['incident'], "The subject of your question is too long (max length is 100). Please shorten it.");

        $hookData['shouldSave'] = true;
        $hookData['incident'] = $incident;
        $incident->Threads[0]->Text = 'thread' . str_pad('.', 2000, '.');
        $result = Hooks::callHook('pre_incident_create_save', $hookData);
        $this->assertFalse($hookData['shouldSave']);
        $this->assertIdentical($result, "The subject of your question is too long (max length is 100). Please shorten it.<br>The description of your question is too long (max length is 1900). Please shorten it.");
        $this->assertIdentical($hookData['incident'], "The subject of your question is too long (max length is 100). Please shorten it.<br>The description of your question is too long (max length is 1900). Please shorten it.");

        $hookData['shouldSave'] = true;
        $hookData['incident'] = $incident;
        $incident->Subject = 'subject';
        $result = Hooks::callHook('pre_incident_create_save', $hookData);
        $this->assertFalse($hookData['shouldSave']);
        $this->assertIdentical($result, "The description of your question is too long (max length is 1900). Please shorten it.");
        $this->assertIdentical($hookData['incident'], "The description of your question is too long (max length is 1900). Please shorten it.");

        $previousValues = Helper::getConfigValues(array('SIEBEL_EAI_HOST', 'SIEBEL_EAI_LANGUAGE', 'SIEBEL_EAI_USERNAME', 'SIEBEL_EAI_PASSWORD', 'SIEBEL_EAI_VALIDATE_CERTIFICATE'));

        Helper::setConfigValues(array('SIEBEL_EAI_HOST' => 'alice', 'SIEBEL_EAI_LANGUAGE' => 'bob', 'SIEBEL_EAI_USERNAME' => 'Carol', 'SIEBEL_EAI_PASSWORD' => 'Dave', 'SIEBEL_EAI_VALIDATE_CERTIFICATE' => false));

        $incident->Threads[0]->Text = 'thread';
        $hookData['shouldSave'] = true;
        $hookData['incident'] = $incident;
        $result = Hooks::callHook('pre_incident_create_save', $hookData);
        $this->assertFalse($hookData['shouldSave']);
        $this->assertIdentical($result, "We're sorry, but there was a problem processing your submission. Please try again later.");
        $this->assertIdentical($hookData['incident'], "We're sorry, but there was a problem processing your submission. Please try again later.");

        if (\RightNow\Internal\Api::intf_name() === 'jvswtrunk') {
            foreach (self::$siebelHost as $siebelHost) {
                $hookData['shouldSave'] = true;
                $hookData['incident'] = $incident;
                Helper::setConfigValues(array('SIEBEL_EAI_HOST' => $siebelHost, 'SIEBEL_EAI_LANGUAGE' => 'enu', 'SIEBEL_EAI_USERNAME' => 'SADMIN', 'SIEBEL_EAI_PASSWORD' => 'MSSQL', 'SIEBEL_EAI_VALIDATE_CERTIFICATE' => false));
                $result = Hooks::callHook('pre_incident_create_save', $hookData);
                $this->assertFalse($hookData['shouldSave']);
                $this->assertNull($result);
                $this->assertIdentical($hookData['incident'], $incident);

                // setup logging
                $logPath = \RightNow\Api::cfg_path() . '/log';
                umask(0);
                file_put_contents("$logPath/tr.acs", 'ALL');

                Helper::makeRequest("/ci/unitTest/wgetRecipient/invokeTestMethod/" . urlencode(__FILE__) . "/" . __CLASS__ . "/verifyAcsLogs/" . $siebelHost);

                $loggedError = $loggedSuccess = false;
                foreach(glob("$logPath/acs/*.log") as $logFile) {
                    $logFileContents = fopen($logFile, 'r');
                    while (($line = fgets($logFileContents)) !== false) {
                        if (Text::stringContains($line, '"level":"error","subject":"siebel","verb":"error",') && Text::stringContains($line, '"#RequestErrorNumber":6,"$RequestErrorMessage":"Couldn\'t resolve host \'alice\'","#HTTP_CODE":0,"$Response":"false","$RequestBody":"&lt;data:Abstract&gt;subject&lt;\/data:Abstract&gt;&lt;data:Description&gt;Email: j@oracle.com.invalid\nthread&lt;\/data:Description&gt;"')) {
                            $loggedError = $line;
                            continue;
                        }
                        if (Text::stringContains($line, '"subject":"siebel","verb":"submit",')) {
                            $loggedSuccess = $line;
                            continue;
                        }
                    }
                    fclose($logFileContents);
                    unlink($logFile);
                }

                $this->assertIsA($loggedError, 'string', "Did not find expected ACS error message");
                $this->assertIsA($loggedSuccess, 'string', "Did not find expected ACS success message");

                unlink("$logPath/tr.acs");
            }
        }

        Helper::setConfigValues($previousValues);
    }

    function verifyAcsLogs() {
        $siebelHost = Text::getSubstringAfter($this->CI->uri->uri_string(), 'verifyAcsLogs/');

        $previousValues = Helper::getConfigValues(array('SIEBEL_EAI_HOST', 'SIEBEL_EAI_LANGUAGE', 'SIEBEL_EAI_USERNAME', 'SIEBEL_EAI_PASSWORD', 'SIEBEL_EAI_VALIDATE_CERTIFICATE'));

        Helper::setConfigValues(array('SIEBEL_EAI_HOST' => 'alice', 'SIEBEL_EAI_LANGUAGE' => 'bob', 'SIEBEL_EAI_USERNAME' => 'Carol', 'SIEBEL_EAI_PASSWORD' => 'Dave', 'SIEBEL_EAI_VALIDATE_CERTIFICATE' => false));

        $formData = array();

        $incident = new Connect\Incident();
        $incident->PrimaryContact = new Connect\Contact();
        $incident->PrimaryContact->Emails = new Connect\EmailArray();
        $incident->PrimaryContact->Emails[] = new Connect\Email();
        $incident->PrimaryContact->Emails[0]->AddressType = new Connect\NamedIDOptList();
        $incident->PrimaryContact->Emails[0]->AddressType->ID = CONNECT_EMAIL_PRIMARY;
        $incident->PrimaryContact->Emails[0]->Address = 'j@oracle.com.invalid';
        $incident->Subject = 'subject';
        $incident->Threads = new Connect\ThreadArray();
        $incident->Threads[] = new Connect\Thread();
        $incident->Threads[0]->ContentType->LookupName = 'text/plain';
        $incident->Threads[0]->Text = 'thread';

        $hookData = array('formData' => $formData, 'incident' => $incident, 'shouldSave' => true);

        $this->setHooks(array(
            array('name' => 'pre_incident_create_save', 'class' => 'Siebel', 'function' => 'processRequest', 'filepath' => '', 'use_standard_model' => true),
        ));

        Hooks::callHook('pre_incident_create_save', $hookData);

        $hookData['shouldSave'] = true;
        $hookData['incident'] = $incident;
        Helper::setConfigValues(array('SIEBEL_EAI_HOST' => $siebelHost, 'SIEBEL_EAI_LANGUAGE' => 'enu', 'SIEBEL_EAI_USERNAME' => 'SADMIN', 'SIEBEL_EAI_PASSWORD' => 'MSSQL', 'SIEBEL_EAI_VALIDATE_CERTIFICATE' => false));
        Hooks::callHook('pre_incident_create_save', $hookData);

        Helper::setConfigValues($previousValues);
    }

    function xtestRegisterSmartAssistantResolution(){
        $registerSmartAssistantResolution = $this->getMethod('registerSmartAssistantResolution');

        $this->setHooks(array(
            array('name' => 'pre_register_smart_assistant_resolution', 'class' => 'Siebel', 'function' => 'registerSmartAssistantResolution', 'filepath' => '', 'use_standard_model' => true),
            array('name' => 'pre_register_smart_assistant_resolution', 'function' => 'hookEndpoint'),
        ));

        $resolution = new KnowledgeFoundation\SmartAssistantResolution();
        $resolution->ID = 3;
        $hookData = array('knowledgeApiSessionToken' => \RightNow\Models\Base::getKnowledgeApiSessionToken(),
            'smartAssistantToken' => null,
            'resolution' => $resolution,
            'incident' => $incident,
            'shouldRegister' => true,
        );
        Hooks::callHook('pre_register_smart_assistant_resolution', $hookData);
        $this->assertFalse($hookData['shouldRegister']);
        $this->assertFalse(self::$hookData['shouldRegister']);

        // generate a valid SA token
        $saToken = $this->CI->model('Incident')->create(array(
            'Incident.Subject' => (object) array('value' => 'iphone'),
            'Incident.Threads' => (object) array('value' => 'iphone'),
            'Incident.PrimaryContact' => $this->CI->model('Contact')->get(101)->result,
            'Incident.Category' => (object) array('value' => null),
            'Incident.Product' => (object) array('value' => 160),
        ), true)->result['token'];

        $hookData = array(
            'knowledgeApiSessionToken' => \RightNow\Models\Base::getKnowledgeApiSessionToken(),
            'smartAssistantToken' => null,
            'resolution' => $resolution,
            'incident' => $incident,
            'shouldRegister' => true,
        );
        $registerSmartAssistantResolution($hookData);
        $this->assertFalse(self::$hookData['shouldRegister']);
    }

    function testGenerateSiebelData(){
        $generateSiebelData = $this->getMethod('generateSiebelData');

        $incident = new Connect\Incident();
        $incident->PrimaryContact = new Connect\Contact();
        $incident->PrimaryContact->Name->First = 'John';

        $formData = array(
        );

        // no email, no thread, no problem!
        $incident->Subject = 'subject';
        $this->assertIdentical($generateSiebelData($formData, $incident),
            array(
                'Abstract' => 'subject',
                'Description' => "Email: (None supplied)\n",
            )
        );

        $incident->PrimaryContact->Emails = new Connect\EmailArray();
        $incident->PrimaryContact->Emails[] = new Connect\Email();
        $incident->PrimaryContact->Emails[0]->AddressType = new Connect\NamedIDOptList();
        $incident->PrimaryContact->Emails[0]->AddressType->ID = CONNECT_EMAIL_PRIMARY;
        $incident->PrimaryContact->Emails[0]->Address = 'j@oracle.com.invalid';
        $incident->Subject = 'subject' . str_pad('.', 200, '.');
        $incident->Threads = new Connect\ThreadArray();
        $incident->Threads[] = new Connect\Thread();
        $incident->Threads[0]->ContentType->LookupName = 'text/plain';
        $incident->Threads[0]->Text = 'thread';
        $this->assertIdentical($generateSiebelData($formData, $incident),
            array('rightnow_integration_errors' => array(
                "The subject of your question is too long (max length is 100). Please shorten it."
            ))
        );

        $incident->Threads[0]->Text = 'thread' . str_pad('.', 2100, '.');
        $this->assertIdentical($generateSiebelData($formData, $incident),
            array('rightnow_integration_errors' => array(
                "The subject of your question is too long (max length is 100). Please shorten it.",
                "The description of your question is too long (max length is 1900). Please shorten it.",
            ))
        );

        $incident->Subject = 'subject';
        $this->assertIdentical($generateSiebelData($formData, $incident),
            array('rightnow_integration_errors' => array(
                "The description of your question is too long (max length is 1900). Please shorten it.",
            ))
        );

        $incident->Threads[0]->Text = 'thread';
        $this->assertIdentical($generateSiebelData($formData, $incident),
            array(
                'Abstract' => 'subject',
                'Description' => "Email: j@oracle.com.invalid\nthread",
            )
        );

        $formData['Incident.ServiceProduct'] = (object)array('value' => 'xxx');
        $formData['Incident.ServiceCategory'] = (object)array('value' => 'xxx');
        $incident->ServiceProduct = Connect\ServiceProduct::fetch(8);
        $incident->ServiceCategory = Connect\ServiceCategory::fetch(71);
        $this->assertIdentical($generateSiebelData($formData, $incident),
            array(
                'Abstract' => 'subject',
                'Description' => "Email: j@oracle.com.invalid\nthread\nIncident.ServiceProduct: Mobile Phones - Android - Motorola Droid\nIncident.ServiceCategory: Troubleshooting",
            )
        );

        // truncate without error
        $incident->Threads[0]->Text = 'thread' . str_pad('.', 1890, '.');
        $this->assertIdentical($generateSiebelData($formData, $incident),
            array(
                'Abstract' => 'subject',
                'Description' => "Email: j@oracle.com.invalid\nthread" . str_pad('.', 1890, '.') . "\nIncident.ServiceProduct: Mobile Phones - Android - Motorola Droid\nIncident.",
            )
        );

        $incident->Threads[0]->Text = 'thread';
        $formData['Contact.Name.First'] = (object)array('value' => 'johnny NOT John');
        $formData['Contact.Name.Last'] = (object)array('value' => 'not in incident');
        $this->assertIdentical($generateSiebelData($formData, $incident),
            array(
                'Abstract' => 'subject',
                'Description' => "Email: j@oracle.com.invalid\nthread"
                    . "\nIncident.ServiceProduct: Mobile Phones - Android - Motorola Droid\nIncident.ServiceCategory: Troubleshooting"
                    . "\nContact.Name.First: johnny NOT John\nContact.Name.Last: not in incident",
            )
        );

        $incident->PrimaryContact->Address->Country = new Connect\Country();
        $formData['Contact.Address.Country'] = (object)array('value' => 'garbage');
        $this->assertIdentical($generateSiebelData($formData, $incident),
            array(
                'Abstract' => 'subject',
                'Description' => "Email: j@oracle.com.invalid\nthread"
                    . "\nIncident.ServiceProduct: Mobile Phones - Android - Motorola Droid\nIncident.ServiceCategory: Troubleshooting"
                    . "\nContact.Name.First: johnny NOT John\nContact.Name.Last: not in incident"
                    . "\nContact.Address.Country: garbage",
            )
        );

        $incident->CustomFields->c->priority = new Connect\NamedIDLabel();
        $incident->CustomFields->c->priority->ID = 11;
        $incident->CustomFields->c->dttm1 = 1361750475;
        $formData['Incident.CustomFields.c.priority'] = (object)array('value' => 'garbage');
        $formData['Incident.CustomFields.c.dttm1'] = (object)array('value' => 'garbage');
        $this->assertIdentical($generateSiebelData($formData, $incident),
            array(
                'Abstract' => 'subject',
                'Description' => "Email: j@oracle.com.invalid\nthread"
                    . "\nIncident.ServiceProduct: Mobile Phones - Android - Motorola Droid\nIncident.ServiceCategory: Troubleshooting"
                    . "\nContact.Name.First: johnny NOT John\nContact.Name.Last: not in incident"
                    . "\nContact.Address.Country: garbage"
                    . "\nIncident.CustomFields.c.priority: 1\nIncident.CustomFields.c.dttm1: 02/24/2013 05:01 PM",
            )
        );

        $incident->Subject = "<>'\"&ä©質問";
        $formData['Incident.CustomFields.c.int1'] = (object)array('value' => 'garbage');
        $this->assertIdentical($generateSiebelData($formData, $incident),
            array(
                'Abstract' => '&lt;&gt;&#039;&quot;&amp;ä©質問',
                'Description' => "Email: j@oracle.com.invalid\nthread"
                    . "\nIncident.ServiceProduct: Mobile Phones - Android - Motorola Droid\nIncident.ServiceCategory: Troubleshooting"
                    . "\nContact.Name.First: johnny NOT John\nContact.Name.Last: not in incident"
                    . "\nContact.Address.Country: garbage"
                    . "\nIncident.CustomFields.c.priority: 1\nIncident.CustomFields.c.dttm1: 02/24/2013 05:01 PM",
            )
        );
    }

    function testGetSiebelFieldValue(){
        $getSiebelFieldValue = $this->getMethod('getSiebelFieldValue');
        $incident = new Connect\Incident();
        $incident->PrimaryContact = new Connect\Contact();
        $incident->PrimaryContact->Name->First = 'John';
        $incident->Subject = 'problems?';
        $incident->CustomFields->c->priority = new Connect\NamedIDLabel();
        $incident->CustomFields->c->priority->ID = 11;
        $incident->CustomFields->c->int1 = 8;
        $incident->CustomFields->c->dttm1 = 1361750475;
        $incident->CustomFields->c->yesno1 = true;
        $incident->CustomFields->c->url = 'http://www.google.com/yay';

        // ignore non-contact and non-incident fields
        $this->assertNull($getSiebelFieldValue('Asset.Something', $incident, (object)array()));
        // use raw data for contact fields
        $this->assertIdentical($getSiebelFieldValue('Contact.Name.First', $incident, (object)array('value' => 'Johnny')), 'Johnny');
        // don't return blank raw data for contact fields
        $this->assertNull($getSiebelFieldValue('Contact.Name.First', $incident, (object)array('value' => '')), 'Johnny');
        // don't return blank raw data for contact fields
        $this->assertNull($getSiebelFieldValue('Contact.Name.First', $incident, (object)array('value' => null)), 'Johnny');
        // use incident data for incident fields (ignore raw data)
        $this->assertIdentical($getSiebelFieldValue('Incident.Subject', $incident, (object)array('value' => 'explosions!')), 'problems?');
        // menu fields on incidents get the names, not the values
        $this->assertIdentical($getSiebelFieldValue('Incident.CustomFields.c.priority', $incident, (object)array()), '1');
        // int
        $this->assertIdentical($getSiebelFieldValue('Incident.CustomFields.c.int1', $incident, (object)array()), 8);
        // datetimes
        $this->assertIdentical($getSiebelFieldValue('Incident.CustomFields.c.dttm1', $incident, (object)array()), '02/24/2013 05:01 PM');
        // bool
        $this->assertIdentical($getSiebelFieldValue('Incident.CustomFields.c.yesno1', $incident, (object)array()), 'Yes');
        // url
        $this->assertIdentical($getSiebelFieldValue('Incident.CustomFields.c.url', $incident, (object)array()), 'http://www.google.com/yay');
    }
}
