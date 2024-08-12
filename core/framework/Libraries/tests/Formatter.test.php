<?php
RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

use RightNow\Libraries\Formatter,
    RightNow\Connect\v1_4 as Connect;

class FormatterTest extends CPTestCase {
    public $testingClass = 'RightNow\Libraries\Formatter';

    function newThread($text, $contentType) {
        $thread = new Connect\Thread();
        $thread->EntryType->ID = 1;
        $thread->Text = $text;
        $thread->ContentType->LookupName = $contentType;
        return $thread;
    }

    function testFormatMultipleFields() {
        // mock comment tabular data
        $data = array(
            array(
                'ID' => 1,
                'Status' => 1,
                'CreatedTime' => '2013-10-21T19:34:19Z',
                'Body' => 'banana split',
                'BodyContentType' => 1, //HTML
            ),
            array(
                'ID' => 2,
                'Status' => 1,
                'CreatedTime' => '2013-10-21T19:35:20Z',
                'Body' => 'ice __cream__ sundae',
                'BodyContentType' => 2, //Markdown
            ),
        );

        $results = Formatter::formatMultipleFields($data,
            Connect\CommunityComment::getMetaData(),
            array('CreatedTime', 'Body'),
            true
        );

        $this->assertIdentical(1, $results[0]['ID']);
        $this->assertIdentical('10/21/2013 01:34 PM', $results[0]['CreatedTime']);
        $this->assertIdentical('banana split', $results[0]['Body']);

        $this->assertIdentical(2, $results[1]['ID']);
        $this->assertIdentical('10/21/2013 01:35 PM', $results[1]['CreatedTime']);
        $this->assertIdentical('<p>ice <strong>cream</strong> sundae</p>', trim($results[1]['Body']));
    }

    function testFormatThreadEntry() {
        $thread = $this->newThread('<b>tag visible</b>', 'text/plain');
        $result = Formatter::formatThreadEntry($thread, false);
        $this->assertIdentical('&lt;b&gt;tag visible&lt;/b&gt;', $result);

        $thread = $this->newThread('__tag visible__', 'text/x-markdown');
        $result = Formatter::formatThreadEntry($thread, true);
        $this->assertIdentical('<p><strong>tag visible</strong></p>', trim($result));
    }

    function testFormatBodyEntry() {
        $commentMetaData = Connect\CommunityComment::getMetaData();
        $result = Formatter::formatBodyEntry('_today\'s_ weather', 2, $commentMetaData, true);
        $this->assertIdentical('<p><em>today\'s</em> weather</p>', trim($result));
    }

    function testFormatTextEntry() {
        $commentMetaData = Connect\CommunityComment::getMetaData();
        $result = Formatter::formatTextEntry('fried ice cream',
            $commentMetaData->BodyContentType->named_values[0]->LookupName,
            false);
        $this->assertIdentical('fried ice cream', $result);

        $result = Formatter::formatTextEntry('fried ice _cream_',
            $commentMetaData->BodyContentType->named_values[1]->LookupName,
            false);
        $this->assertIdentical('<p>fried ice <em>cream</em></p>', trim($result));

        $result = Formatter::formatTextEntry('fried <b>ice cream</b>', 'text/html', false);
        $this->assertEqual('fried <b>ice cream</b>', $result);

        $result = Formatter::formatTextEntry('fried <b>ice cream</b>', 'text', false);
        $this->assertEqual('fried &lt;b&gt;ice cream&lt;/b&gt;', $result);

        $result = Formatter::formatTextEntry('<body onload=alert("test1")>', Connect\PropertyUsage::HTML, false);
        $this->assertNotEqual('<body onload=alert("test1")>', $result);
    }

    function testMarkdownThreadFormatting() {
        // TK - Test Comment Object.

        $question = new Connect\CommunityQuestion();
        $question->Body = 'le *mepris*';
        $question->BodyContentType->LookupName = 'text/x-markdown';
        $meta = $question::getMetadata();

        $output = Formatter::formatThreadEntry($question, false);
        $this->assertSame('<p>le <em>mepris</em></p>', trim($output));

        $question->Body = "le [mepris] [1]\n\n[1]: http://placesheen.com\n";
        $meta = $question::getMetadata();

        $output = Formatter::formatThreadEntry($question, false);
        $this->assertSame('<p>le <a rel="nofollow" href="http://placesheen.com">mepris</a></p>', trim($output));

        $question->Body = '__phone__';
        list(, $keyword) = $this->reflect('keywordPhrase');
        $keyword->setValue('phoney%20phones');
        $output = Formatter::formatThreadEntry($question, true);
        $this->assertSame("<p><strong><em class='rn_Highlight'>phone</em></strong></p>", trim($output));
    }

    function testFormatMarkdownEntry() {
        $input = "## london";
        $output = Formatter::formatMarkdownEntry($input);
        $this->assertSame("<h2>london</h2>", trim($output));

        list(, $keyword) = $this->reflect('keywordPhrase');
        $keyword->setValue('phones');
        $input = 'hunting [phones](/foo/bar)';
        $output = Formatter::formatMarkdownEntry($input, true);
        $this->assertSame('<p>hunting <a rel="nofollow" href="/foo/bar"><em class=\'rn_Highlight\'>phones</em></a></p>', trim($output));
        $keyword->setValue('');

        $input = '<div>hunting<span>with cheese</span></div>';
        $output = Formatter::formatMarkdownEntry($input, true);
        $this->assertSame('<p>huntingwith cheese</p>', trim($output));

        $input = '*hunting*';
        $output = Formatter::formatMarkdownEntry($input, true);
        $this->assertSame('<p><em>hunting</em></p>', trim($output));

        $input = '&amp;&#27;hunting';
        $output = Formatter::formatMarkdownEntry($input, true);
        $this->assertSame('<p>&amp;&#27;hunting</p>', trim($output));
    }

    function testFormatDateTime() {
        $expected = new PatternExpectation("/^\d{2}\/\d{2}\/\d{4} \d{2}:\d{2} (AM|PM)$/");
        $this->assert($expected, Formatter::formatDateTime(time()));
        $this->assert($expected, Formatter::formatDateTime(0));
        $this->assert($expected, Formatter::formatDateTime(-1));
    }

    function testFormatDate() {
        $expected = new PatternExpectation("/^\d{2}\/\d{2}\/\d{4}$/");
        $this->assert($expected, Formatter::formatDate(time()));
        $this->assert($expected, Formatter::formatDate('0'));
        $this->assert($expected, Formatter::formatDate('-1'));

        // 200220-000055 formatDate() needs to support both date strings and timestamps,
        // and needs to respect the DTF_SHORT_DATE config (for localization of date formats)
        $dateConfig = \Rnow::getConfig(DTF_SHORT_DATE);
        try {
            \Rnow::updateConfig('DTF_SHORT_DATE', '%m/%d/%Y', true);
            $this->assertEqual('05/12/2020', Formatter::formatDate(1589323447));
            \Rnow::updateConfig('DTF_SHORT_DATE', '%Y/%m/%d', true);
            $this->assertEqual('2044/05/12', Formatter::formatDate('05/12/2044'));
        } finally {
            \Rnow::updateConfig('DTF_SHORT_DATE', $dateConfig, true);
        }

        // 200220-000055 formatDate() also needs to support timezone changes across the date line
        $tz = \Rnow::getConfig(TZ_INTERFACE);
        try {
            // get a timestamp for a date in Tokyo
            date_default_timezone_set('Asia/Tokyo');
            $date = '06/12/2020';
            $timestamp = (new \DateTime($date))->getTimestamp();

            // show that we still get the problem with mismatched timezones
            // (this simulates the problem the customer is seeing; ensuring it
            // still fails shows that the fix is still relevant)
            \Rnow::updateConfig('TZ_INTERFACE', 'America/Denver', true);
            $this->assertNotEqual($date, Formatter::formatDate($timestamp));

            // but when we set the timezone correctly the date is fixed
            \Rnow::updateConfig('TZ_INTERFACE', 'Asia/Tokyo', true);
            $this->assertEqual($date, Formatter::formatDate($timestamp));
        } finally {
            \Rnow::updateConfig('TZ_INTERFACE', $tz, true);
            date_default_timezone_set($tz);
        }
    }

    function testFormatBoolean() {
        $this->assertSame("No", Formatter::formatBoolean(null));
        $this->assertSame("No", Formatter::formatBoolean(false));
        $this->assertSame("No", Formatter::formatBoolean(0));
        $this->assertSame("No", Formatter::formatBoolean(''));
        $this->assertSame("No", Formatter::formatBoolean('moon'));
        $this->assertSame("Yes", Formatter::formatBoolean(true));
        $this->assertSame("Yes", Formatter::formatBoolean(1));
        $this->assertSame("Yes", Formatter::formatBoolean(-1));
        $this->assertSame("Yes", Formatter::formatBoolean(array()));
    }

    function testCountryFormatting() {
        $country = Connect\Country::fetch(1);
        $value = Formatter::formatField($country, null, false);
        $this->assertIdentical($country->Name, $value);

        //@@@ QA 130624-000067 The formatting should return the Countries `Name` field which is the internationalized label for a country.
        $country = Connect\Country::fetch(3);
        $value = Formatter::formatField($country, null, false);
        $this->assertIdentical($country->Name, $value);
        $this->assertTrue($country->Name !== $country->LookupName);
    }

    function testSLAFormatting() {
        $slaInstance = new Connect\SLAInstance();
        $slaInstance->NameOfSLA->LookupName = "Gold";
        $this->assertIdentical('Gold', Formatter::formatField($slaInstance, null, false));
        $this->assertIdentical('Gold', Formatter::formatField($slaInstance->NameOfSLA, null, false));

        $slaInstance = new Connect\SLAInstance();
        $slaInstance->NameOfSLA->LookupName = "Silver";
        $this->assertIdentical('Silver', Formatter::formatField($slaInstance, null, false));
        $this->assertIdentical('Silver', Formatter::formatField($slaInstance->NameOfSLA, null, false));

        $slaInstance = new Connect\SLAInstance();
        $slaInstance->NameOfSLA->LookupName = "<b>Silver and Bold</b>";
        $this->assertIdentical('&lt;b&gt;Silver and Bold&lt;/b&gt;', Formatter::formatField($slaInstance, null, false));
        $this->assertIdentical('&lt;b&gt;Silver and Bold&lt;/b&gt;', Formatter::formatField($slaInstance->NameOfSLA, null, false));
    }

    function testDateFormatting() {
        $fieldMeta = (object)array(
            'COM_type' => 'date'
        );

        $this->assertIdentical('10/08/2012', Formatter::formatField(1349738477, $fieldMeta, false));
        $this->assertIdentical('01/01/1970', Formatter::formatField(46800, $fieldMeta, false));
        // date(PHP_MAX_INT) is just after 3AM UTC in 1/19/2038, but due to the timezone issue reported
        // in 200220-000055 we see this as 1/18/2038 (see similar test in testDateTimeFormatting below)
        $this->assertIdentical('01/18/2038', Formatter::formatField(PHP_INT_MAX, $fieldMeta, false));

        $this->assertIdentical('01/01/1970', Formatter::formatField(46800, $fieldMeta, false));
        $fieldMeta->is_nillable = true;
        $this->assertNull(Formatter::formatField(null, $fieldMeta, false));
    }

    function testDateTimeFormatting() {
        $fieldMeta = (object)array(
            'COM_type' => 'datetime'
        );

        $this->assertIdentical('10/08/2012 05:21 PM', Formatter::formatField(1349738477, $fieldMeta, false));
        $this->assertIdentical('12/31/1969 05:00 PM', Formatter::formatField(0, $fieldMeta, false));
        $this->assertIdentical('01/01/1970 06:00 AM', Formatter::formatField(46800, $fieldMeta, false));
        $this->assertIdentical('01/18/2038 08:14 PM', Formatter::formatField(PHP_INT_MAX, $fieldMeta, false));

        $this->assertIdentical('12/31/1969 05:00 PM', Formatter::formatField(null, $fieldMeta, false));
        $fieldMeta->is_nillable = true;
        $this->assertNull(Formatter::formatField(null, $fieldMeta, false));
    }

    function testBooleanFormatting() {
        $meta = (object) array('COM_type' => 'Boolean');

        $this->assertIdentical('No', Formatter::formatField(null, $meta, false));
        $this->assertIdentical('No', Formatter::formatField(0, $meta, false));
        $this->assertIdentical('Yes', Formatter::formatField(1, $meta, false));
        $this->assertIdentical('No', Formatter::formatField(false, $meta, false));
        $this->assertIdentical('Yes', Formatter::formatField(true, $meta, false));
        $this->assertIdentical('No', Formatter::formatField('somehow a string', $meta, false));
        $meta->is_nillable = true;
        $this->assertNull(Formatter::formatField(null, $meta, false));
    }

    function testCustomFieldMask() {
        $mask = "F(U#U#U#F)F U#U#U#F-U#U#U#U#";

        $value = "4061234567";
        $maskedValue = Formatter::applyMask($value, $mask);
        $this->assertIdentical('(406) 123-4567', $maskedValue);

        $value = "406123";
        $maskedValue = Formatter::applyMask($value, $mask);
        $this->assertIdentical('(406) 123-', $maskedValue);

        $value = "";
        $maskedValue = Formatter::applyMask($value, $mask);
        $this->assertIdentical('', $maskedValue);
    }

    function testStringCustomFields() {
        $incident = Connect\Incident::fetch(1);
        $this->assertIdentical($incident->Language->LookupName, Formatter::formatField($incident->Language, null, false));
        $this->assertIdentical($incident->Severity->LookupName, Formatter::formatField($incident->Severity, null, false));

        $incident = new Connect\Incident();
        $incident->Severity->LookupName = "<b>severity 1</b>";
        $this->assertIdentical("&lt;b&gt;severity 1&lt;/b&gt;", Formatter::formatField($incident->Severity, null, false));
    }

    function testMenuType() {
        $fieldName = 'optionTheFirst';
        $fieldValue = (object) array(
            'ID' => 1,
            'Name' => $fieldName,
            'LookupName' => $fieldName,
        );
        $fieldMetaData = (object) array(
            'type_name' => 'RightNow\\Connect\\v1_4\\CO\\my_menu_only_object',
            'COM_type' => 'CO\\my_menu_only_object',
            'is_menu' => true,
        );
        $this->assertSame($fieldName, Formatter::formatField($fieldValue, $fieldMetaData, false));
    }

    function testStringStandardFields() {
        $meta = (object) array('COM_type' => 'String');

        $meta->container_class = CONNECT_NAMESPACE_PREFIX . '\Thread';
        $this->checkContainerClassFormatting($meta);

        $meta->container_class = CONNECT_NAMESPACE_PREFIX . '\IncidentCustomFieldsc';
        $this->checkContainerClassFormatting($meta);

        $meta->container_class = CONNECT_NAMESPACE_PREFIX . '\ContactCustomFieldsc';
        $this->checkContainerClassFormatting($meta);

        $meta->container_class = CONNECT_NAMESPACE_PREFIX . '\AnswerContentCustomFieldsc';
        $this->checkContainerClassFormatting($meta);

        $meta->container_class = CONNECT_NAMESPACE_PREFIX . '\AnswerContentCustomFieldsCO';
        $this->checkContainerClassFormatting($meta);

        $meta->container_class = CONNECT_NAMESPACE_PREFIX . '\AnswerContentCustomFieldsCO';
        $this->checkContainerClassFormatting($meta);

        $meta->container_class = CONNECT_NAMESPACE_PREFIX . '\AnswerContentCustomFieldsCO';
        $this->checkContainerClassFormatting($meta);

        $meta->container_class = CONNECT_NAMESPACE_PREFIX . '\Contact';
        $this->checkContainerClassFormatting($meta);

        $meta->container_class = CONNECT_NAMESPACE_PREFIX . '\Incident';
        $this->checkContainerClassFormatting($meta);

        $meta->container_class = CONNECT_NAMESPACE_PREFIX . '\Product';
        $this->checkContainerClassFormatting($meta);

        $meta->container_class = CONNECT_NAMESPACE_PREFIX . '\Category';
        $this->checkContainerClassFormatting($meta);

        $this->assertIdentical('', Formatter::formatField(null, $meta, false));
        $meta->is_nillable = true;
        $this->assertNull(Formatter::formatField(null, $meta, false));
    }

    function checkContainerClassFormatting($meta){
        $value = Formatter::formatField('banana@banana.foo', $meta, false);
        $this->assertIdentical('<a href="mailto:banana@banana.foo">banana@banana.foo</a>', $value);

        $value = Formatter::formatField("foo\n\nbar", $meta, false);
        $this->assertIdentical('foo<br /><br />bar', $value);

        $value = Formatter::formatField('<a href="foo">banana</a>', $meta, false);
        $this->assertIdentical('&lt;a href="foo"&gt;banana&lt;/a&gt;', $value);

        $value = Formatter::formatField('http://placesheen.com', $meta, false);
        $this->assertIdentical('<a href="http://placesheen.com" target="_blank">http://placesheen.com</a>', $value);
    }

    function testAnswerContentIsUnescaped(){
        $meta = (object) array('COM_type' => 'String', 'container_class' => CONNECT_NAMESPACE_PREFIX . '\Answer');
        $value = Formatter::formatField('banana@banana.foo', $meta, false);
        $this->assertIdentical('banana@banana.foo', $value);

        $value = Formatter::formatField("foo\n\nbar", $meta, false);
        $this->assertIdentical("foo\n\nbar", $value);

        $value = Formatter::formatField('<a href="foo">banana</a>', $meta, false);
        $this->assertIdentical('<a href="foo">banana</a>', $value);

        $value = Formatter::formatField('http://placesheen.com', $meta, false);
        $this->assertIdentical('http://placesheen.com', $value);

        $meta = (object) array('COM_type' => 'String', 'container_class' => KF_NAMESPACE_PREFIX . '\AnswerContent');
        $value = Formatter::formatField('banana@banana.foo', $meta, false);
        $this->assertIdentical('banana@banana.foo', $value);

        $value = Formatter::formatField("foo\n\nbar", $meta, false);
        $this->assertIdentical("foo\n\nbar", $value);

        $value = Formatter::formatField('<a href="foo">banana</a>', $meta, false);
        $this->assertIdentical('<a href="foo">banana</a>', $value);

        $value = Formatter::formatField('http://placesheen.com', $meta, false);
        $this->assertIdentical('http://placesheen.com', $value);
    }

    function testNamedIDType() {
        # code...
    }

    function testHighlight(){
        $method = $this->getStaticMethod('highlight');
        $property = new \ReflectionProperty('RightNow\Libraries\Formatter', 'keywordPhrase');
        $property->setAccessible(true);

        $property->setValue(null);
        $this->assertSame(array(), $method(array()));
        $objectTest = (object)array();
        $this->assertSame($objectTest, $method($objectTest));
        $this->assertSame(true, $method(true));
        $this->assertSame(0, $method(0));
        $this->assertSame('phone', $method('phone'));

        $property->setValue('phone');
        $this->assertIdentical("this is my <em class='rn_Highlight'>phone</em>", $method('this is my phone'));
        $this->assertIdentical("this is my <em class='rn_Highlight'><em class='rn_Highlight'>phone</em></em>", $method("this is my <em class='rn_Highlight'>phone</em>"));
        $this->assertIdentical("this is my phon", $method('this is my phon'));
        $this->assertIdentical("<em class='rn_Highlight'>phone</em>", $method('phone'));
        $this->assertIdentical("<em class='rn_Highlight'>pHoNe</em>", $method('pHoNe'));
        $this->assertIdentical("highlight <em class='rn_Highlight'>banana</em>, not phone", $method('highlight banana, not phone', 'banana'));
        $this->assertIdentical("don't highlight anything, not even phone", $method("don't highlight anything, not even phone", false));

        $property->setValue('run and walk');
        $this->assertIdentical("go <em class='rn_Highlight'>walking</em> or go <em class='rn_Highlight'>running</em>", $method('go walking or go running'));

        $property->setValue('running');
        $this->assertIdentical("go for a <em class='rn_Highlight'>run</em>", $method('go for a run'));

        $property->setValue(false);
        //rnkl_stem is weird. It uses a process cache to return the value, but clears out that cache after a call. If we didn't
        //call it here, the next time it was invoked, it would return 'PHONE' which could cause other tests to fail. Calling it here
        //with a fake value will clear that cache.
        \RightNow\Internal\Api::rnkl_stem('fake value', 0);
    }

    function testGetFormattingOptions(){
        $method = $this->getStaticMethod('getFormattingOptions');

        $nonHtmlOptions = OPT_VAR_EXPAND|OPT_HTTP_EXPAND|OPT_SPACE2NBSP|OPT_ESCAPE_SCRIPT|OPT_SUPPORT_AS_HTML|OPT_REF_TO_URL_PREVIEW;
        $this->assertIdentical($nonHtmlOptions, $method(false));
        $this->assertIdentical($nonHtmlOptions, $method(null));
        $this->assertIdentical($nonHtmlOptions, $method(0));

        $htmlOptions = $nonHtmlOptions|OPT_ESCAPE_HTML|OPT_NL_EXPAND;
        $this->assertIdentical($htmlOptions, $method(true));
        $this->assertIdentical($htmlOptions, $method(1));
        $this->assertIdentical($htmlOptions, $method('html'));
    }

    function testGetKeyword(){
        $method = $this->getStaticMethod('getKeyword');
        $property = new \ReflectionProperty('RightNow\Libraries\Formatter', 'keywordPhrase');
        $property->setAccessible(true);

        $this->assertNull($method());
        $property->setValue('phone');
        $this->assertIdentical('phone', $method());
        $property->setValue(null);
        $this->assertIdentical(null, $method());
        $property->setValue(0);
        $this->assertIdentical(0, $method());
        $property->setValue('0');
        $this->assertIdentical('0', $method());

        $property->setValue(false);
    }

    function testAssetFormatting() {
        $asset = Connect\Asset::fetch(1);
        $value = Formatter::formatField($asset, null, false);
        $this->assertIdentical($asset->Name, $value);
    }

    function testFormatHtmlEntry() {
        $method = $this->getStaticMethod('formatHtmlEntry');
        $text = 'this is <b>bold</b>';
        $this->assertEqual($text, $method($text));
        
        $text = '<IMG SRC=j&#X41vascript:alert("test2")>';
        $this->assertNotEqual($text, $method($text));
    }

    function testShouldFormatString() {
        $method = $this->getStaticMethod("shouldFormatString");

        $fakeMetaData = (object) array(
            "container_class" => 'RightNow\Connect\v1_4\Contact',
            "name" => "Pet's Name",
        );
        $this->assertTrue($method($fakeMetaData));

        $fakeMetaData = (object) array(
            "container_class" => 'RightNow\Connect\Knowledge\v1\AnswerContent',
            "name" => "Body",
        );
        $this->assertFalse($method($fakeMetaData));

        $fakeMetaData = (object) array(
            "container_class" => 'RightNow\Connect\Knowledge\v1\AnswerContent',
            "name" => "Summary",
        );
        $this->assertTrue($method($fakeMetaData));
    }
    
    function testFormatSafeHTML(){
        $method = $this->getStaticMethod('formatSafeHTML');
        $text = 'this is <b>bold</b>';
        $this->assertEqual($text, $method($text));
        
        $text = '<blockquote><p style="margin-left:40px;text-align:center;"><span style="color:#FFD700;"><s><u><em><strong>test</strong></em></u></s></span></p></blockquote>';
        $this->assertEqual($text, $method($text));
        
        $text = '<p><a target="_blank" href="http://www.oracle.com/ocom/groups/public/documents/digitalasset/016091_en.gif"><img alt="" src="http://www.oracle.com/ocom/groups/public/documents/digitalasset/016091_en.gif" style="height:512px;width:512px;" /></a></p>';
        $this->assertEqual($text, $method($text));
        
        $text = '<img src="http://url.to.file.which/not.exist" onerror=alert(document.cookie);>';https://www.seeklogo.net/wp-content/uploads/2015/10/google-photos-logo-vector-download.jpg" style="hei
        $this->assertNotEqual($text, $method($text));
        
        $text = '<body onload=alert("test1")>';
        $this->assertNotEqual($text, $method($text));
        
        $text = '<IMG SRC=j&#X41vascript:alert("test2")>';
        $this->assertNotEqual($text, $method($text));
        
        $text = '<p><a id="1" href="https://www.oracle.com">Oracle</a></p>';
        $this->assertNotEqual($text, $method($text));    
    }

    
    function testFormatSafeObject(){
        $method = $this->getStaticMethod('formatSafeObject');
        $question = get_instance()->model('CommunityQuestion')->getBlank()->result;
        $comment = get_instance()->model('CommunityComment')->getBlank()->result;
        
        $text = 'this is <b>bold</b>';
        $question->Body = $text;
        $this->assertEqual($text, $method($question)->Body);

        $text = '<blockquote><p style="margin-left:40px;text-align:center;"><span style="color:#FFD700;"><s><u><em><strong>test</strong></em></u></s></span></p></blockquote>';
        $comment->Body = $text;
        $this->assertEqual($text, $method($comment)->Body);
        
        $text = '<img src="http://url.to.file.which/not.exist" onerror=alert(document.cookie);>';https://www.seeklogo.net/wp-content/uploads/2015/10/google-photos-logo-vector-download.jpg" style="hei
        $question->Body = $text;
        $this->assertNotEqual($text, $method($question)->Body);

        $text = '<body onload=alert("test1")>""</body>';
        $comment->Body = $text;
        $this->assertNotEqual($text, $method($comment)->Body);
    }

    function testFormatFieldHTML(){
        $question = get_instance()->model('CommunityQuestion')->getBlank()->result;
        $text = 'this is <b>bold</b>';
        $question->Body = $text;       
        $this->assertEqual($text, Formatter::formatField($question->Body, $question::getMetadata(), false));

        $text = '<body onload=alert("test1")>';
        $question->Body = $text;
        $this->assertNotEqual($text, Formatter::formatField($question->Body, $question::getMetadata()->Body, false));
    }


    function testIsFormatHTMLType(){
        $this->assertTrue(Formatter::isFormatHTMLType(Connect\PropertyUsage::HTML));
        $this->assertFalse(Formatter::isFormatHTMLType(Connect\PropertyUsage::URI));
    }

    function testFormatHTMLUsageType(){
        $question = get_instance()->model('CommunityQuestion')->getBlank()->result;
        $comment = get_instance()->model('CommunityComment')->getBlank()->result;
        
        $text = 'this is <b>bold</b>';
        $this->assertEqual($text, Formatter::formatHTMLUsageType($text, $question::getMetaData()->Body));

        $text = '<blockquote><p style="margin-left:40px;text-align:center;"><span style="color:#FFD700;"><s><u><em><strong>test</strong></em></u></s></span></p></blockquote>';
        $this->assertEqual($text, Formatter::formatHTMLUsageType($text, $comment::getMetaData()->Body));
        
        $text = '<img src="http://url.to.file.which/not.exist" onerror=alert(document.cookie);>';
        $this->assertNotEqual($text, Formatter::formatHTMLUsageType($text, $question::getMetaData()->Body));

        $text = '<body onload=alert("test1")>';
        $this->assertNotEqual($text, Formatter::formatHTMLUsageType($text, $comment::getMetaData()->Body));
    }
}

