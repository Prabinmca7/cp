<?php

use RightNow\Internal\Libraries\ConnectExplorer,
    RightNow\Utils\Connect as ConnectUtil,
    RightNow\Connect\v1_4 as Connect,
    RightNow\Utils\Text;

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class ConnectExplorerTest extends CPTestCase {
    public $testingClass = '\RightNow\Internal\Libraries\ConnectExplorer';

    // A list of various legitimate queries known to return results
    private $queries = array(
        "desc Incident",
        "describe Incident",
        "describe incident",
        "desc incidents",
        "show objects",
        "Show Objects",
        "Show Tables",
        "SHOW TABLES",
        "SHOW OBJECTS",
        "SELECT * FROM Mailbox;",
        "SELECT ID, LookupName FROM Account LIMIT 10",
        "SELECT Contact FROM Contact LIMIT 10",
        "SELECT Incident.PrimaryContact.ParentContact FROM Incident LIMIT 10",
        "SELECT Contact FROM Contact C WHERE C.Name.First like 'C%' LIMIT 10",
        "SELECT Contact FROM Contact WHERE CreatedTime > '2011-10-17T16:27:42' LIMIT 10",
        "SELECT disabled, source FROM Contact WHERE Contact.Name.First like 'C%' AND Contact.Address.City='Bozeman' LIMIT 10",
        "SELECT LookupName FROM Contact WHERE LookupName LIKE 'A%';",
        "SELECT ID, Contact.Name.First, Login
         FROM Contact
         WHERE Contact.Name.First IS NOT NULL
         ORDER BY Contact.Name.First ASC LIMIT 5",
        "SELECT ID, Contact.Name.First, Login
         FROM Contact
         WHERE Contact.Name.First IS NOT NULL
         ORDER BY Contact.Name.First DESC LIMIT 5",
        "SELECT MIN(ID) AS theID FROM Contact LIMIT 10",
        "SELECT MAX(ID) AS theID FROM Contact LIMIT 10",
        "SELECT MIN(ID) AS theID FROM Contact LIMIT 10 OFFSET 0",
        "SELECT UPPER(Contact.Name.First) AS First FROM Contact WHERE First IS NOT NULL LIMIT 1",
        "SELECT LOWER(Contact.Name.First) AS First FROM Contact WHERE First IS NOT NULL LIMIT 1",
        "SELECT COUNT() FROM Contact LIMIT 10",
        "SELECT COUNT() FROM Contact LIMIT 10 OFFSET 0",
        "SELECT * FROM Task LIMIT 5",
        "SELECT Contact.ID, I.ReferenceNumber FROM Contact
         INNER JOIN Contact.PrimaryContactIncidents I
         WHERE Contact.ID = 1 LIMIT 10",
        "select id, lookupname, login from Contact where lookupname is not null limit 1",
        "SELECT ID, LOOKUPNAME, LOGIN FROM Contact WHERE LOOKUPNAME IS NOT NULL LIMIT 1",
        "SELECT ID, LookupName FROM Contact WHERE ID = 1 LIMIT 2",
        "SELECT ID, LookupName FROM Contact WHERE ID != 1 LIMIT 2",
        "SELECT ID, LookupName FROM Contact WHERE ID < 2 LIMIT 2",
        "SELECT ID, LookupName FROM Contact WHERE ID <= 2 LIMIT 2",
        "SELECT ID, LookupName FROM Contact WHERE ID > 1 LIMIT 2",
        "SELECT ID, LookupName FROM Contact WHERE ID >= 1 LIMIT 2",
        "SELECT ID, LookupName FROM Contact WHERE ID LIKE 1 LIMIT 2",
        "SELECT ID, LookupName FROM Contact WHERE ID NOT LIKE 1 LIMIT 2",
        "SELECT ID, LookupName FROM Contact WHERE ID IN (1) LIMIT 2",
        "SELECT ID, LookupName FROM Contact WHERE ID IN (1,2) LIMIT 2",
        "SELECT ID, LookupName FROM Contact WHERE ID IS NOT NULL LIMIT 2",
        "SELECT LookupName, sysdate() AS TIMESTAMP FROM Task LIMIT 5",
        "SELECT LookupName, date_add('2010-01-14 15:23:52', 3, 'hour', 1) AS TIMESTAMP FROM Task LIMIT 5",
        "SELECT LookupName, date_diff( '2010-01- 15:23:30', '2010-01-01 15:23:45') AS TIMESTAMP FROM Task LIMIT 5",
        "SELECT LookupName, date_trunc('2010-02-14 15:23:45', 'month') AS TIMESTAMP FROM Task LIMIT 5",
        "SELECT CO.Defect FROM CO.Defect LIMIT 10",
        "SELECT ID, CustomFields.CO.AcctBool,
         CustomFields.CO.AcctInteger,
         CustomFields.CO.AcctText
         FROM Account ORDER BY AcctText DESC LIMIT 10",
        "SELECT Source, COUNT(Source) as daCount FROM Organization GROUP BY Source LIMIT 10",
        "SELECT Source, COUNT(Source) FROM Organization GROUP BY Source HAVING COUNT(Source) <= 5 LIMIT 10",
        "select curAdminUser() as user,
         curAdminUserName() as username,
         curInterface() as interface,
         curInterfaceName() as interface_name,
         curLanguage() as lang,
         curLanguageName() as lang_name
         from Contact limit 1",
        "SELECT * FROM Contact WHERE LookupName LIKE '%''%' LIMIT 10",
        "SELECT
         CategoryLinks.ServiceCategory.Parent.level1,
         CategoryLinks.ServiceCategory.Parent.level2,
         CategoryLinks.ServiceCategory.Parent.level3,
         CategoryLinks.ServiceCategory.Parent.level4,
         CategoryLinks.ServiceCategory.Parent.ID as ParentID,
         CategoryLinks.ServiceCategory.ID
         FROM ServiceProduct
         WHERE (ID = 1 OR Parent.id = 1 OR Parent.level1 = 1 OR Parent.level2 = 1 OR Parent.level3 = 1 OR Parent.level4 = 1)
         AND CategoryLinks.ServiceCategory.EndUserVisibleInterfaces.ID = 1
         AND CategoryLinks.ServiceCategory.ID IS NOT NULL LIMIT 10",
        "SELECT LookupName FROM Contact WHERE LookupName LIKE 'Ad%';SELECT LookupName FROM Contact WHERE LookupName LIKE 'B%'",
        "SELECT C.ID, C.LookupName,
            C.CustomFields.c.pets_name,
            C.CustomFields.c.website,
            C.CustomFields.c.work_email,
            C.CustomFields.c.pager,
            C.CustomFields.c.birthday,
            C.CustomFields.c.last_login,
            C.CustomFields.c.age,
            C.CustomFields.c.pet_type,
            C.CustomFields.c.newsletter,
            C.CustomFields.c.special_offers,
            C.CustomFields.c.comments,
            C.CustomFields.c.pet_owner,
            C.CustomFields.c.date1,
            C.CustomFields.c.datetime1,
            C.CustomFields.c.int1,
            C.CustomFields.c.menu1,
            C.CustomFields.c.optin,
            C.CustomFields.c.textarea1,
            C.CustomFields.c.text1,
            C.CustomFields.c.yesno1
        FROM Contact C WHERE (
            C.CustomFields.c.pets_name IS NOT NULL OR
            C.CustomFields.c.website IS NOT NULL OR
            C.CustomFields.c.work_email IS NOT NULL OR
            C.CustomFields.c.pager IS NOT NULL OR
            C.CustomFields.c.birthday IS NOT NULL OR
            C.CustomFields.c.last_login IS NOT NULL OR
            C.CustomFields.c.age IS NOT NULL OR
            C.CustomFields.c.pet_type IS NOT NULL OR
            C.CustomFields.c.newsletter IS NOT NULL OR
            C.CustomFields.c.special_offers IS NOT NULL OR
            C.CustomFields.c.comments IS NOT NULL OR
            C.CustomFields.c.pet_owner IS NOT NULL OR
            C.CustomFields.c.date1 IS NOT NULL OR
            C.CustomFields.c.datetime1 IS NOT NULL OR
            C.CustomFields.c.int1 IS NOT NULL OR
            C.CustomFields.c.menu1 IS NOT NULL OR
            C.CustomFields.c.optin IS NOT NULL OR
            C.CustomFields.c.textarea1 IS NOT NULL OR
            C.CustomFields.c.text1 IS NOT NULL OR
            C.CustomFields.c.yesno1 IS NOT NULL);"
    );

    function testQuery() {
        $query = "SELECT ID, LookupName\nFROM Contact LIMIT 100";
        $results = ConnectExplorer::query($query);
        $this->assertIsA($results, 'array');
        $this->assertEqual('select', $results['queryType']);
        $this->assertEqual($query, $results['query']);

        // Run various queries through that are known to produce results,
        // as this exercises many ConnectExplorer methods.
        foreach($this->queries as $query) {
            $results = ConnectExplorer::query($query, 25);
            $this->assertIsA($results, 'array');
            $this->assertTrue(count($results['columns']) > 0);
            $this->assertTrue(count($results['results']) > 0);
            $firstRow = $results['results'][0];
            $this->assertTrue(array_key_exists('_ID_', $firstRow));
            $this->assertTrue(array_key_exists('_LINK_', $firstRow));
            $this->assertTrue(array_key_exists('_TITLE_', $firstRow));
            $this->assertTrue(count($firstRow) > 3);
        }
    }

    function testGetQueryObject() {
        $getQueryObject = $this->getMethod('getQueryObject', true);

        $query = "SELECT ID, LookupName\nFROM Contact LIMIT 100";
        $results = $getQueryObject($query);
        $this->assertEqual('select', $results->queryType);
        $this->assertEqual(array('ID','LookupName'), $results->columns);
        $this->assertEqual('Contact', $results->objectName);
        $this->assertEqual($query, $results->query);

        // lowercase object name
        $query = "select id, lookupname from contact limit 100";
        $results = $getQueryObject($query);
        $this->assertEqual('select', $results->queryType);
        $this->assertEqual(array('id','lookupname'), $results->columns);
        $this->assertEqual('Contact', $results->objectName);
        $this->assertEqual('select id, lookupname from Contact limit 100', $results->query);

        $query = 'SHOW objects';
        $results = $getQueryObject($query);
        $this->assertEqual('show', $results->queryType);
        $this->assertEqual('Objects', $results->objectName);

        $query = 'show objects';
        $results = $getQueryObject($query);
        $this->assertEqual('show', $results->queryType);
        $this->assertEqual('Objects', $results->objectName);

        // standard objects
        $query = 'DESC Account';
        $results = $getQueryObject($query);
        $this->assertEqual('describe', $results->queryType);
        $this->assertEqual('Account', $results->objectName);

        $query = 'desc account';
        $results = $getQueryObject($query);
        $this->assertEqual('describe', $results->queryType);
        $this->assertEqual('Account', $results->objectName);

        $query = 'DESCRIBE Account';
        $results = $getQueryObject($query);
        $this->assertEqual('describe', $results->queryType);
        $this->assertEqual('Account', $results->objectName);

        $query = 'describe account';
        $results = $getQueryObject($query);
        $this->assertEqual('describe', $results->queryType);
        $this->assertEqual('Account', $results->objectName);

        // custom object
        $query = 'describe CO.Defect';
        $results = $getQueryObject($query);
        $this->assertEqual('describe', $results->queryType);
        $this->assertEqual('CO.Defect', $results->objectName);

        // correct custom object name's case
        $query = 'describe co.defect';
        $results = $getQueryObject($query);
        $this->assertEqual('describe', $results->queryType);
        $this->assertEqual('CO.Defect', $results->objectName);

        // correct when custom object name is in all caps
        $query = 'describe co.rma';
        $results = $getQueryObject($query);
        $this->assertEqual('describe', $results->queryType);
        $this->assertEqual('CO.RMA', $results->objectName);
    }

    function testStrip() {
        $strip = $this->getMethod('strip', true);

        $query = $expected = "SELECT ID, LookupName\nFROM\rContact\r\nLIMIT 100";
        $actual = $strip($query);
        $this->assertIdentical($expected, $actual);

        $query = "SELECT ID, LookupName\nFROM\rContact\r\nLIMIT 100";
        $expected = "SELECT ID, LookupName FROM Contact LIMIT 100";
        $actual = $strip($query, true);
        $this->assertIdentical($expected, $actual);
    }

    function testGetQueryObjectLimits() {
        $getQueryObject = $this->getMethod('getQueryObject', true);
        $query = 'SELECT ID, Contact.Name.First, Contact.Name.Last FROM Contact';
        $queryWithLimit = "$query LIMIT 100";

        // No LIMITs
        $results = $getQueryObject($query);
        $this->assertEqual($query, $results->query);
        $this->assertEqual('select', $results->queryType);
        $this->assertIdentical(array('ID', 'Contact.Name.First', 'Contact.Name.Last'), $results->columns);

        // LIMIT specified in query
        $results = $getQueryObject($queryWithLimit);
        $this->assertEqual($queryWithLimit, $results->query);

        // LIMIT specified in query and the defaultLimit arg - LIMIT from $query should take precedence
        $results = $getQueryObject($queryWithLimit, 50);
        $this->assertEqual($queryWithLimit, $results->query);

        // LIMIT specified in defaultQuery arg, but not in the $query itself.
        $results = $getQueryObject($query, 50);
        $this->assertEqual("$query LIMIT 50", $results->query);

        // Ignore LIMIT statements in quotes
        $query = "SELECT ID, LookupName FROM Incident WHERE Subject LIKE '%LIMIT 20%'";
        $results = $getQueryObject($query, 50);
        $this->assertEqual("$query LIMIT 50", $results->query);

        // Line breaks don't interfere with LIMIT detection
        $results = $getQueryObject("SELECT * FROM\nContact\nLIMIT\n20", 50);
        $this->assertEqual("SELECT * FROM\nContact\nLIMIT\n20", $results->query);

        // Line breaks don't interfere with LIMIT detection
        $results = $getQueryObject("SELECT\n*\nFROM\nContact\nLIMIT\n20", 50);
        $this->assertEqual("SELECT\n*\nFROM\nContact\nLIMIT\n20", $results->query);
        $results = $getQueryObject("SELECT\n\n*\n\nFROM\n\nContact\n\nLIMIT\n\n20", 50);
        $this->assertEqual("SELECT\n\n*\n\nFROM\n\nContact\n\nLIMIT\n\n20", $results->query);
        $results = $getQueryObject("SELECT\r\n*\r\nFROM\r\nContact\r\nLIMIT\r\n20", 50);
        $this->assertEqual("SELECT\n*\nFROM\nContact\nLIMIT\n20", $results->query);

        // set default limit
        $query = "SELECT ID, LookupName FROM Contact";
        $results = $getQueryObject($query, 10);
        $this->assertEqual($query . ' LIMIT 10', $results->query);

        // limit without offset
        $query = "SELECT ID, LookupName FROM Contact LIMIT 50";
        $results = $getQueryObject($query, 50, 1);
        $this->assertEqual($query . ' OFFSET 50', $results->query);

        // limit with offset, requesting the next page
        $query = "SELECT ID, LookupName FROM Contact LIMIT 50 OFFSET 150";
        $results = $getQueryObject($query, 50, 1);
        $this->assertEqual('SELECT ID, LookupName FROM Contact LIMIT 50 OFFSET 200', $results->query);

        // limit with offset, requesting the previous page
        $query = "SELECT ID, LookupName FROM Contact LIMIT 50 OFFSET 150";
        $results = $getQueryObject($query, 50, -1);
        $this->assertEqual('SELECT ID, LookupName FROM Contact LIMIT 50 OFFSET 100', $results->query);

    }

    function testLimitExists() {
        $limitExists = $this->getMethod('limitExists');

        $this->assertFalse($limitExists(''));
        $this->assertFalse($limitExists(null));
        $this->assertFalse($limitExists('SELECT ID, LookupName FROM Contact'));
        $this->assertFalse($limitExists('SELECT ID, LookupName FROM Contact LIMIT'));
        $this->assertFalse($limitExists('SELECT ID, LookupName FROM Contact LIMIT YES'));
        $this->assertFalse($limitExists("SELECT ID, LookupName FROM Incident WHERE Subject LIKE '% LIMIT 20%'"));
        $this->assertFalse($limitExists("SELECT ID, LookupName FROM Incident WHERE Subject LIKE 'Issues with LIMIT 20 queries%' OR Subject LIKE 'Issues regarding LIMIT 20 queries%'"));

        $this->assertTrue($limitExists(' LIMIT 50'));
        $this->assertTrue($limitExists('SELECT ID, LookupName FROM Contact LIMIT 50'));
        $this->assertTrue($limitExists('select ID, LookupName from Contact limit 50'));
        $this->assertTrue($limitExists('SELECT ID, LookupName FROM Contact LIMIT 10 OFFSET 20'));
        $this->assertTrue($limitExists("SELECT ID, LookupName FROM Incident WHERE Subject LIKE 'Issues with LIMIT 20 queries%' LIMIT 20"));
        $this->assertTrue($limitExists("SELECT ID, LookupName FROM Incident WHERE Subject LIKE 'Issues with LIMIT 20 queries%' LIMIT 50 ORDER DESC"));
        $this->assertTrue($limitExists("SELECT ID, LookupName FROM Incident WHERE Subject LIKE 'Issues with LIMIT 20 queries%' OR Subject LIKE 'Issues regarding LIMIT 20 queries%' LIMIT 20"));
    }

    function testOffsetExists() {
        $method = $this->getMethod('offsetExists');

        $this->assertFalse($method(''));
        $this->assertFalse($method(null));
        $this->assertFalse($method('SELECT ID, LookupName FROM Contact'));
        $this->assertFalse($method('SELECT ID, LookupName FROM Contact OFFSET'));
        $this->assertFalse($method('SELECT ID, LookupName FROM Contact OFFSET YES'));
        $this->assertFalse($method("SELECT ID, LookupName FROM Incident WHERE Subject LIKE '% OFFSET 20%'"));
        $this->assertFalse($method("SELECT ID, LookupName FROM Incident WHERE Subject LIKE 'Issues with OFFSET 20 queries%' OR Subject LIKE 'Issues regarding OFFSET 20 queries%'"));

        $this->assertTrue($method(' OFFSET 50'));
        $this->assertTrue($method('SELECT ID, LookupName FROM Contact OFFSET 50'));
        $this->assertTrue($method('select ID, LookupName from Contact offset 50'));
        $this->assertTrue($method('SELECT ID, LookupName FROM Contact LIMIT 10 OFFSET 20'));
        $this->assertTrue($method("SELECT ID, LookupName FROM Incident WHERE Subject LIKE 'Issues with LIMIT 20 queries%' OFFSET 20"));
        $this->assertTrue($method("SELECT ID, LookupName FROM Incident WHERE Subject LIKE 'Issues with LIMIT 20 queries%' OFFSET 50 ORDER DESC"));
        $this->assertTrue($method("SELECT ID, LookupName FROM Incident WHERE Subject LIKE 'Issues with OFFSET 20 queries%' OR Subject LIKE 'Issues regarding OFFSET 20 queries%' OFFSET 20"));
    }

    function testGetLimitOrOffsetValue() {
        $method = $this->getMethod('getLimitOrOffsetValue');

        $this->assertIdentical($method('limit', ' LIMIT 10'), 10);
        $this->assertIdentical($method('LIMIT', 'select id, lookupname from contact limit 10'), 10);
        $this->assertIdentical($method('limit', 'select id, lookupname from contact LIMIT 10'), 10);
        $this->assertIdentical($method('limit', "select id, lookupname from contact where subject like 'blah blah LIMIT 20'"), null);
        $this->assertIdentical($method('limit', "select id, lookupname from contact where subject like 'blah blah LIMIT 20' limit 10"), 10);

        $this->assertIdentical($method('offset', ' OFFSET 10'), 10);
        $this->assertIdentical($method('OFFSET', 'select id, lookupname from contact offset 10'), 10);
        $this->assertIdentical($method('offset', 'select id, lookupname from contact OFFSET 10'), 10);
        $this->assertIdentical($method('OFFSET', "select id, lookupname from contact where subject like 'blah blah OFFSET 20'"), null);
        $this->assertIdentical($method('offset', "select id, lookupname from contact where subject like 'blah blah OFFSET 20' OFFSET 10"), 10);
        $this->assertIdentical($method('offset', "select id, lookupname from contact where subject like 'blah blah OFFSET 20' LIMIT 30 OFFSET 10"), 10);
    }

    function testGetLimit() {
        $method = $this->getMethod('getLimit');

        $this->assertIdentical($method(' LIMIT 10'), 10);
        $this->assertIdentical($method('select id, lookupname from contact limit 10'), 10);
        $this->assertIdentical($method("select id, lookupname from contact where subject like 'blah blah LIMIT 20'"), null);
        $this->assertIdentical($method("select id, lookupname from contact where subject like 'blah blah LIMIT 20'", 25), 25);
        $this->assertIdentical($method("select id, lookupname from contact where subject like 'blah blah LIMIT 20' limit 10"), 10);
    }

    function testGetOffset() {
        $method = $this->getMethod('getOffset');

        $this->assertIdentical($method(' OFFSET 10'), 10);
        $this->assertIdentical($method('select id, lookupname from contact offset 10'), 10);
        $this->assertIdentical($method('select id, lookupname from contact OFFSET 10'), 10);
        $this->assertIdentical($method("select id, lookupname from contact where subject like 'blah blah OFFSET 20'"), 0);
        $this->assertIdentical($method("select id, lookupname from contact where subject like 'blah blah OFFSET 20' OFFSET 10"), 10);
        $this->assertIdentical($method("select id, lookupname from contact where subject like 'blah blah OFFSET 20' LIMIT 30 OFFSET 10"), 10);
    }

    function testGetObjectName() {
        $getObjectName = $this->getMethod('getObjectName', true);

        try {
            $this->assertEqual('', $getObjectName('', false));
            $this->fail();
        }
        catch (\Exception $e) {
            $this->pass();
        }

        $this->assertEqual('Contact', $getObjectName('contact', false));
        $this->assertEqual('Contact', $getObjectName('contacts', false));
        $this->assertEqual('Contact', $getObjectName('Contact', false));
        $this->assertEqual(CONNECT_NAMESPACE_PREFIX . "\\" . 'Contact', $getObjectName('Contact'));

        $this->assertEqual('AnalyticsReport', $getObjectName('AnalyticsReport', false));
        $this->assertEqual('AnalyticsReport', $getObjectName('analyticsreport', false));
        $this->assertEqual('AnalyticsReport', $getObjectName('ANALYTICSREPORT', false));

        try {
            $getObjectName('answer.url', false);
            $this->fail();
        }
        catch (\Exception $e) {
            $this->pass();
        }

        // Custom objects - make sure they have the correct namespace if being appended
        $this->assertEqual(CONNECT_NAMESPACE_PREFIX . "\\CO\\Purchase", $getObjectName('CO.Purchase'));
        $this->assertEqual('CO.Purchase', $getObjectName('co.purchase', false));
        $this->assertEqual('CO.Purchase', $getObjectName('CO.Purchase', false));
        $this->assertEqual('CO.Purchase', $getObjectName('CO.PURCHASE', false));
    }

    function testGetResults() {
        $getQueryObject = $this->getMethod('getQueryObject');
        $method = $this->getMethod('getResults');

        $getResults = function($query, $defaultLimit = 100) use ($getQueryObject, $method) {
            return $method($getQueryObject($query, $defaultLimit));
        };

        $query = "select Name.First, Name.Last, Emails.Address\nFROM Contact\nLIMIT 10";
        $results = $getResults($query);
        $this->assertIsA($results, 'array');
        $this->assertEqual($query, $results['query']);
        $this->assertIsA($results['results'], 'array');
        $this->assertEqual(10, count($results['results']));

        $query = "show\nobjects";
        $results = $getResults($query);
        $this->assertEqual('show', $results['queryType']);
        $this->assertEqual('Objects', $results['objectName']);

        $query = "show\ntables";
        $results = $getResults($query);
        $this->assertEqual('show', $results['queryType']);
        $this->assertEqual('Objects', $results['objectName']);
    }

    function testGetColumns() {
        $getColumns = $this->getMethod('getColumns', true);
        $columns = array(
            '_ID_',
            '_LINK_',
            'ID',
            'First',
            'Last',
            'Address',
        );

        $expected = array(
          array('key' => 'ID'),
          array('key' => 'First'),
          array('key' => 'Last'),
          array('key' => 'Address'),
        );

        $actual = $getColumns($columns, 'Contact');
        $this->assertIdentical($expected, $actual);
    }

    function testDescribe() {
        $getQueryObject = $this->getMethod('getQueryObject', true);
        $method = $this->getMethod('describe', true);

        $describe = function($query, $defaultLimit = 100) use ($getQueryObject, $method) {
            return $method($getQueryObject($query, $defaultLimit));
        };

        $results = $describe('DESC Contact');
        $this->assertEqual(array('key' => 'Field'), $results[0][0]);
        $this->assertEqual(array('key' => 'Type'), $results[0][1]);
        $this->assertEqual(array('key' => 'Null'), $results[0][2]);
        $this->assertEqual(array('key' => 'Default'), $results[0][3]);

        $id = $results[1][0];
        $this->assertEqual('ID', $id['Field']);
        $this->assertEqual('No', $id['Null']);

        $address = $results[1][4];
        $this->assertEqual('Address', $address['Field']);
        $this->assertTrue(Text::beginsWith($address['Type'], 'Connect\\')); // Stripped of leading 'RightNow\'
        $this->assertEqual('Yes', $address['Null']);
    }

    function testShow() {
        $getQueryObject = $this->getMethod('getQueryObject', true);
        $method = $this->getMethod('show', true);

        $show = function($query, $defaultLimit = 100) use ($getQueryObject, $method) {
            return $method($getQueryObject($query, $defaultLimit));
        };

        $results = $show('SHOW Objects');
        $this->assertIdentical(array(array('key' => 'Object Name')), $results[0]);
        $this->assertIdentical(array(
            '_ID_' => 'Account',
            '_LINK_' => 'Account',
            '_TITLE_' => 'Inspect ',
            'Object Name' => 'Account'), $results[1][0]);
    }

    function testSelect() {
        $getQueryObject = $this->getMethod('getQueryObject', true);
        $method = $this->getMethod('select', true);
        $defaultLimit = 10;

        $select = function($query, $limit = null) use ($getQueryObject, $method, $defaultLimit) {
            return $method($getQueryObject($query, $limit === null ? $defaultLimit : $limit));
        };

        // Tabular query
        $results = $select('SELECT ID, LookupName FROM Account LIMIT 10');
        $this->assertIsA($results, 'array');
        $this->assertIdentical(array(array('key' => 'ID'), array('key' => 'LookupName')), $results[0]);
        $this->assertIdentical('1', $results[1][0]['ID']);

        // Object query should return all fields, with sub-objects denoted by RightNow\\Connect\\{version}\\{className}
        $results = $select("SELECT Contact FROM Contact WHERE LookupName = 'Elizabeth Jergan' LIMIT 1");
        $columns = $results[0];
        $this->assertIdentical(array('key' => 'ID'), $columns[0]);
        $this->assertIdentical(array('key' => 'LookupName'), $columns[1]);
        $this->assertIdentical(array('key' => 'CreatedTime'), $columns[2]);
        $rows = $results[1];
        $this->assertEqual(1, count($rows));
        list($row) = $this->getRow($rows, 'Elizabeth Jergan', 'LookupName');
        $this->assertEqual(2, $row['_ID_']);
        $this->assertEqual(2, $row['ID']);
        $this->assertEqual('Connect\\v1_4\\Address', $row['Address']);

        // Object query with dot notation
        $results = $select('SELECT Contact.ParentOrganization FROM Contact', 0);
        $columns = $results[0];
        $this->assertIdentical(array('key' => 'ID'), $columns[0]);
        $this->assertIdentical(array('key' => 'LookupName'), $columns[1]);
        $this->assertIdentical(array('key' => 'CreatedTime'), $columns[2]);
        $rows = $results[1];
        list($row) = $this->getRow($rows, 'Brindell', 'LookupName');
        $this->assertTrue(array_key_exists('NumberOfEmployees', $row));

        // TODO: Object query with dot notation AND a LIMIT returns 0 rows... ROQL bug?
        $results = $select('SELECT Contact.ParentOrganization FROM Contact LIMIT 10');

        // Object query with extended dot notation
        $results = $select('SELECT Incident.PrimaryContact.ParentContact FROM Incident LIMIT 25');
        //$results = $select("SELECT Incident.PrimaryContact.ParentContact.Notes.NoteList.Text FROM Incident");
        $columns = $results[0];
        $this->assertIdentical(array('key' => 'ID'), $columns[0]);
        $this->assertIdentical(array('key' => 'LookupName'), $columns[1]);
        $this->assertIdentical(array('key' => 'CreatedTime'), $columns[2]);
        $rows = $results[1];
        list($row) = $this->getRow($rows, 'Sarah Beckstein', 'LookupName');
        $this->assertTrue(array_key_exists('ContactType', $row));

        // Object query with alias and dot notation in WHERE clause
        $results = $select("SELECT Contact FROM Contact C WHERE C.Name.First like 'C%'");
        $columns = $results[0];
        $this->assertIdentical(array('key' => 'ID'), $columns[0]);
        $this->assertIdentical(array('key' => 'LookupName'), $columns[1]);
        $this->assertIdentical(array('key' => 'CreatedTime'), $columns[2]);
        $rows = $results[1];
        list($row) = $this->getRow($rows, 'C Downie', 'LookupName');
        $this->assertTrue(array_key_exists('ContactType', $row));

        // Object query with alias
        $results = $select("SELECT C FROM Contact C WHERE C.Name.First like 'C%'");
        $columns = $results[0];
        $this->assertIdentical(array('key' => 'ID'), $columns[0]);
        $this->assertIdentical(array('key' => 'LookupName'), $columns[1]);
        $this->assertIdentical(array('key' => 'CreatedTime'), $columns[2]);
        $rows = $results[1];
        list($row) = $this->getRow($rows, 'C Downie', 'LookupName');
        $this->assertTrue(array_key_exists('ContactType', $row));

        // Object query with '>' operator
        $results = $select("SELECT Contact FROM Contact WHERE CreatedTime > '2011-10-17T16:27:42'");
        $columns = $results[0];
        $this->assertIdentical(array('key' => 'ID'), $columns[0]);
        $this->assertIdentical(array('key' => 'LookupName'), $columns[1]);
        $this->assertIdentical(array('key' => 'CreatedTime'), $columns[2]);
        $rows = $results[1];
        list($row) = $this->getRow($rows, 'PlatinumOrg Contact', 'LookupName');
        $this->assertTrue(array_key_exists('ContactType', $row));

        // Tabular query
        $results = $select("SELECT disabled, source FROM Contact WHERE Contact.Name.First like 'C%' AND Contact.Address.City='Bozeman'");
        $columns = $results[0];
        $this->assertIdentical(array('key' => 'Disabled'), $columns[0]);
        $this->assertIdentical(array('key' => 'Source'), $columns[1]);
        $this->assertEqual('1002', $results[1][0]['Source']);

        // Tabular query with alias
        $results = $select("SELECT C.ID, C.Name.First, C.Name.Last FROM Contact C WHERE C.Name.First like 'C%'");
        $columns = $results[0];
        $this->assertIdentical(array('key' => 'ID'), $columns[0]);
        $this->assertIdentical(array('key' => 'First'), $columns[1]);
        $this->assertIdentical(array('key' => 'Last'), $columns[2]);
        $this->assertEqual('707', $results[1][0]['ID']);

        // Tabular query with full object name
        $results = $select("SELECT Contact.ID, Contact.Name.First, Contact.Name.Last FROM Contact WHERE Contact.Name.First like 'C%'");
        $columns = $results[0];
        $this->assertIdentical(array('key' => 'ID'), $columns[0]);
        $this->assertIdentical(array('key' => 'First'), $columns[1]);
        $this->assertIdentical(array('key' => 'Last'), $columns[2]);
        $this->assertEqual('707', $results[1][0]['ID']);

        // Tabular query with field names only
        $results = $select("SELECT ID, Name.First, Name.Last FROM Contact WHERE Name.First like 'C%'");
        $columns = $results[0];
        $this->assertIdentical(array('key' => 'ID'), $columns[0]);
        $this->assertIdentical(array('key' => 'First'), $columns[1]);
        $this->assertIdentical(array('key' => 'Last'), $columns[2]);
        $rows = $results[1];
        $this->assertEqual('707', $results[1][0]['ID']);

        // ASC / DESC
        $query = "
            SELECT ID, Contact.Name.First, Login
            FROM Contact
            WHERE Contact.Name.First IS NOT NULL
            ORDER BY Contact.Name.First %s LIMIT 5
        ";
        $results = $select(sprintf($query, 'ASC'));
        $this->assertTrue(Text::beginsWith(strtolower($results[1][0]['First']), 'a'));
        $results = $select(sprintf($query, 'DESC'));
        $this->assertTrue(Text::beginsWith(strtolower($results[1][0]['First']), 'z'));

        // MIN / MAX
        $query = "SELECT %s(ID) AS theID FROM Contact";
        $results = $select(sprintf($query, 'MIN'));
        $this->assertEqual('1', $results[1][0]['theID']);
        $results = $select(sprintf($query, 'MAX'));
        $this->assertTrue($results[1][0]['theID'] > 1200);

        $results = $select(sprintf($query, 'MIN') . ' LIMIT 10');
        $this->assertEqual('1', $results[1][0]['theID']);

        $results = $select(sprintf($query, 'MIN') . ' LIMIT 10 OFFSET 0');
        $this->assertEqual('1', $results[1][0]['theID']);

        $results = $select(sprintf($query, 'MIN') . ' LIMIT 10 OFFSET 10');
        $this->assertIdentical(array(array(), array()), $results);

        // UPPER / LOWER
        $query = "SELECT %s(Contact.Name.First) AS First FROM Contact WHERE First = 'Elizabeth' LIMIT 1";
        $results = $select(sprintf($query, 'UPPER'));
        $this->assertTrue(Text::beginsWith($results[1][0]['First'], 'E'));
        $results = $select(sprintf($query, 'LOWER'));
        $this->assertTrue(Text::beginsWith($results[1][0]['First'], 'e'));

        // COUNT
        $results = $select('SELECT COUNT() FROM Contact');
        $this->assertEqual('COUNT()', $results[0][0]['key']);
        $this->assertTrue(is_numeric($results[1][0]['COUNT()']));

        $results = $select('SELECT COUNT() FROM Contact LIMIT 10');
        $this->assertEqual('COUNT()', $results[0][0]['key']);
        $this->assertTrue(is_numeric($results[1][0]['COUNT()']));

        $results = $select('SELECT COUNT() FROM Contact LIMIT 10 OFFSET 0');
        $this->assertEqual('COUNT()', $results[0][0]['key']);
        $this->assertTrue(is_numeric($results[1][0]['COUNT()']));

        $results = $select('SELECT COUNT() FROM Contact LIMIT 10 OFFSET 10');
        $this->assertIdentical(array(array(), array()), $results);

        // SELECT *
        $results = $select('SELECT * FROM TASK LIMIT 5');
        $columns = $results[0];
        $this->assertIdentical(array('key' => 'ID'), $columns[0]);
        $this->assertIdentical(array('key' => 'LookupName'), $columns[1]);
        $this->assertIdentical(array('key' => 'CreatedTime'), $columns[2]);
        $rows = $results[1];
        list($row) = $this->getRow($rows, 'Follow up on lead', 'LookupName');
        $this->assertTrue(array_key_exists('PercentComplete', $row));

        // Relationships
        $results = $select('
            SELECT Contact.ID, I.ReferenceNumber FROM Contact
            INNER JOIN Contact.PrimaryContactIncidents I
            WHERE Contact.ID = 1');
        $this->assertEqual('060606-000011', $results[1][0]['ReferenceNumber']);

        // upper/lower case keywords and field names
        $query = "select id, lookupname, login from Contact where lookupname = 'James Walker' limit 1";
        $results = $select($query);
        $this->assertEqual('James Walker', $results[1][0]['LookupName']);
        $results = $select(strtoupper($query));
        $this->assertEqual('James Walker', $results[1][0]['LookupName']);

        // Operators [=, !=, <, <=, >, >=, LIKE, NOT LIKE, IN, NOT IN, IS NULL, IS NOT NULL]
        $query = "SELECT ID, LookupName FROM Contact WHERE ID %s %s LIMIT 2";
        $results = $select(sprintf($query, '=', 1));
        $this->assertEqual('1', $results[1][0]['ID']);
        $results = $select(sprintf($query, '!=', 1));
        $this->assertEqual('2', $results[1][0]['ID']);
        $results = $select(sprintf($query, '<', 2));
        $this->assertEqual('1', $results[1][0]['ID']);
        $results = $select(sprintf($query, '<=', 2));
        $this->assertEqual('1', $results[1][0]['ID']);
        $results = $select(sprintf($query, '>', 1));
        $this->assertEqual('2', $results[1][0]['ID']);
        $results = $select(sprintf($query, '>=', 1));
        $this->assertEqual('1', $results[1][0]['ID']);
        $results = $select(sprintf($query, 'LIKE', 1));
        $this->assertEqual('1', $results[1][0]['ID']);
        $results = $select(sprintf($query, 'NOT LIKE', 1));
        $this->assertEqual('2', $results[1][0]['ID']);
        $results = $select(sprintf($query, 'IN', '(1)'));
        $this->assertEqual('1', $results[1][0]['ID']);
        $results = $select(sprintf($query, 'IN', '(1,2)'));
        $this->assertEqual('2', $results[1][1]['ID']);
        $results = $select(sprintf($query, 'IS', 'NULL'));
        $this->assertIdentical(array(array(), array()), $results);
        $results = $select(sprintf($query, 'IS', 'NOT NULL'));
        $this->assertEqual('1', $results[1][0]['ID']);
        $this->assertEqual('2', $results[1][1]['ID']);

        // DATE / TIME
        $query = 'SELECT LookupName, %s AS TIMESTAMP FROM TASK LIMIT 5';
        $results = $select(sprintf($query, 'sysdate()'));
        $this->assertIdentical(array(array('key' => 'LookupName'), array('key' => 'TIMESTAMP')), $results[0]);
        // due to time zone differences, the date returned could be tomorrow
        $this->assertTrue(Text::beginsWith($results[1][0]['TIMESTAMP'], date('Y-m-d')) || Text::beginsWith($results[1][0]['TIMESTAMP'], date('Y-m-d', time() + 86400)));
        $results = $select(sprintf($query, "date_add('2010-01-14 15:23:52', 3, 'hour', 1)"));
        $this->assertEqual('2010-01-14T18:00:00.000Z', $results[1][0]['TIMESTAMP']);
        $results = $select(sprintf($query, "date_diff( '2010-01- 15:23:30', '2010-01-01 15:23:45')"));
        $this->assertEqual('1262384625', $results[1][0]['TIMESTAMP']);
        $results = $select(sprintf($query, "date_trunc('2010-02-14 15:23:45', 'month')"));
        $this->assertEqual('2010-02-01T00:00:00.000Z', $results[1][0]['TIMESTAMP']);

        // Custom objects and fields
        $results = $select('SELECT CO.Defect FROM CO.Defect');
        $this->assertEqual('FieldAbbr', $results[0][0]['key']);
        $this->assertEqual('Used to group incidents', $results[1][0]['Name']);

        $results = $select('SELECT CO$Purchase$ContactID FROM Contact');
        $this->assertIdentical(array(array(), array()), $results);

        $results = $select('
            SELECT ID, CustomFields.CO.AcctBool,
                   CustomFields.CO.AcctInteger,
                   CustomFields.CO.AcctText
            FROM Account ORDER BY AcctText DESC
        ');
        $this->assertNull($results[1][0]['AcctBool']);
        $this->assertNull($results[1][0]['AcctInteger']);
        $this->assertNull($results[1][0]['AcctText']);

        // GROUP BY and HAVING
        $results = $select('SELECT Source, COUNT(Source) as daCount FROM Organization GROUP BY Source');
        $this->assertEqual(3, count($results[1]));
        $this->assertEqual('1001', $results[1][0]['Source']);
        $this->assertTrue(is_numeric($results[1][0]['daCount']));
        $results = $select('SELECT Source, COUNT(Source) FROM Organization GROUP BY Source HAVING COUNT(Source) <= 5');
        $this->assertEqual(1, count($results[1]));
        $this->assertEqual('1003', $results[1][0]['Source']);

        // ROQL Internal functions
        $results = $select(
            'select curAdminUser() as user,
                    curAdminUserName() as username,
                    curInterface() as interface,
                    curInterfaceName() as interface_name,
                    curLanguage() as lang,
                    curLanguageName() as lang_name
            from Contact limit 1'
        );
        $row = $results[1][0];
        $this->assertTrue(is_numeric($row['user']));
        $this->assertEqual('1', $row['interface']);
        $this->assertTrue(is_numeric($row['lang']));
        $this->assertEqual('en_US', $row['lang_name']);

        // Escaping
        $results = $select("SELECT * FROM Contact WHERE LookupName LIKE '%''%'");
        $this->assertEqual("Casey O'Calloway", $results[1][0]['LookupName']);
        // Not sure it makes sense to try and utilize ROQL::escapeString() to escape columns and the "conditions" part of the query..
        try {
            $select("SELECT * FROM Contact WHERE LookupName LIKE '%\'%'");
            $this->fail('Expected Exception did not occur');
        }
        catch (\Exception $e) {
            $this->assertEqual('unrecognized token: "\' LIMIT 10"', $e->getMessage());
        }

        // A prodcat query, just 'cause
        $productID = 1;
        $results = $select("SELECT
            CategoryLinks.ServiceCategory.Parent.level1,
            CategoryLinks.ServiceCategory.Parent.level2,
            CategoryLinks.ServiceCategory.Parent.level3,
            CategoryLinks.ServiceCategory.Parent.level4,
            CategoryLinks.ServiceCategory.Parent.ID as ParentID,
            CategoryLinks.ServiceCategory.ID
        FROM ServiceProduct
        WHERE (ID = $productID OR Parent.id = $productID OR Parent.level1 = $productID OR Parent.level2 = $productID OR Parent.level3 = $productID OR Parent.level4 = $productID)
        AND CategoryLinks.ServiceCategory.EndUserVisibleInterfaces.ID = 1
        AND CategoryLinks.ServiceCategory.ID IS NOT NULL");
        $row = $results[1][0];
        $this->assertEqual('122', $row['Level1']);
        $this->assertEqual('123', $row['Level2']);
        $this->assertEqual('124', $row['Level3']);
        $this->assertEqual('125', $row['Level4']);
        $this->assertEqual('126', $row['ParentID']);

        // Queries chained together by semi-colons (;). Only query 1 is honored
        $results = $select("SELECT LookupName FROM Contact WHERE LookupName LIKE 'Ad%';SELECT LookupName FROM Contact WHERE LookupName LIKE 'B%'");
        $this->assertEqual(3, count($results[1]));
        $this->assertTrue(Text::beginsWith($results[1][0]['LookupName'], 'Ad'));

        // Object query should determine the link and title by the object identified in the SELECT statement, not the one specified by FROM
        $results = $select("SELECT Incident.PrimaryContact.ParentContact FROM Incident WHERE Incident.PrimaryContact.ParentContact.Login LIKE 'aschubert%' LIMIT 25");
        $this->assertEqual('Contact', $results[1][0]['_LINK_']); // NOT Incident
        $this->assertEqual('Inspect Contact ', $results[1][0]['_TITLE_']);

        // Invalid queries
        $queries = array(
            array('FOO FROM Contact', 'Invalid query'), // Shouldn't happen since query has been parsed at this point..
            array('SELECT Contact', 'Invalid query'),
            array('SELECT FROM Contact', 'syntax error'),
            array('SELECT FOO FROM Contact', 'Unknown table or column'),
            array('SELECT LookupName FROM FOO', 'Invalid object name: FOO'),
            array("SELECT * FROM Contact WHERE LookupName LIKE '%\'%'", 'unrecognized token'),
            array("SELECT LookupName FROM Contact WHERE LookupName = \"Robert'); DROP TABLE Students\"", 'Unknown table or column'),
            array("SELECT LookupName FROM Contact WHERE LookupName = 'James Walker' AND 1=(SELECT COUNT(*) FROM Account)", 'sub-queries are not permitted'),
        );

        foreach($queries as $pair) {
            list($query, $error) = $pair;
            try {
                $select($query);
                $this->fail('Expected Exception did not occur');
            }
            catch (\Exception $e) {
                $actual = $e->getMessage();
                if (!Text::stringContains($actual, $error)) {
                    $this->fail("Expected: '$error'  Got: '$actual'\n$query");
                }
            }
        };
    }

    function testGetPrimaryClasses() {
        $results = ConnectExplorer::getPrimaryClasses();
        $this->assertIsA($results, 'array');
        $this->assertEqual('RightNow\\Connect\\v1_4\\Account', $results[0]);
        $this->assertTrue(in_array('RightNow\\Connect\\v1_4\\CO\\Defect', $results));

        // Strip namespace
        $results = ConnectExplorer::getPrimaryClasses(true);
        $this->assertIsA($results, 'array');
        $this->assertEqual('Account', $results[0]);
        $this->assertTrue(in_array('CO.Defect', $results));
    }

    function testGetCustomNamespaces() {
        $results = ConnectExplorer::getCustomNamespaces();
        $this->assertIsA($results, 'array');
        $this->assertTrue(in_array('CO', $results));
        $this->assertTrue(in_array('Market_Test', $results));
    }

    function getRow(array $rows, $value, $fieldName = 'field') {
        for($i = 0; $i < count($rows); $i++) {
            $row = $rows[$i];
            if ($row[$fieldName] === $value) {
                return array($row, $i);
            }
        }
    }

    function testGetMeta() {
        // Primary object with no ID specified
        $results = ConnectExplorer::getMeta('Account');
        $this->assertEqual('Account', $results['objectName']);
        $this->assertEqual(array(), $results['fields']);
        $rows = $results['rows'];
        $this->assertTrue(count($rows) > 0);

        list($row) = $this->getRow($rows, 'type_name');
        $this->assertEqual(ConnectUtil::prependNamespace('Account'), $row['value']);
        $this->assertNull($row['type']);

        // Property without any relationships
        $results = ConnectExplorer::getMeta('Account.Name');
        $rows = $results['rows'];

        // Primary object with an ID specified
        $results = ConnectExplorer::getMeta('Account', 1);
        $rows = $results['rows'];
        list($row) = $this->getRow($rows, 'ID');
        $this->assertEqual(1, $row['value']);
        $this->assertEqual('Account.ID', $row['link']);
        list($row, $index) = $this->getRow($rows, 'Attributes');
        $this->assertEqual(ConnectUtil::prependNamespace('AccountOptions'), $row['type']);
        $row = $rows[$index + 1];
        $this->assertEqual('Attributes', $row['parent']);
        $this->assertEqual('AccountLocked', $row['field']);

        // Primary 'CO' type objects need a back-slash delimiter, 'cause that's how Connect does it..
        $results = ConnectExplorer::getMeta('CO.Defect');
        $this->assertEqual('CO.Defect', $results['objectName']);
        $rows = $results['rows'];
        list($row) = $this->getRow($rows, 'COM_type');
        $this->assertEqual('CO\\Defect', $row['value']);
        list($row) = $this->getRow($rows, 'LookupName');
        $this->assertEqual('CO.Defect.LookupName', $row['link']);

        // Array sub-fields such as 'constraints' are linked by index
        $results = ConnectExplorer::getMeta('Account.ID', 1);
        $rows = $results['rows'];
        list($row, $index) = $this->getRow($rows, 'constraints');
        $this->assertEqual('Array (1)', $row['value']);
        $row = $rows[$index + 1];
        $this->assertEqual(0, $row['field']);
        $this->assertEqual('constraints', $row['parent']);
        $this->assertEqual('kind: 1 value: 1', $row['value']);
        $this->assertEqual('Account.ID.constraints.0', $row['link']);
        $results = ConnectExplorer::getMeta('Account.ID.constraints.0', 1);
        $rows = $results['rows'];
        list($row) = $this->getRow($rows, 'kind');
        $this->assertEqual(1, $row['value']);
        list($row) = $this->getRow($rows, 'value');
        $this->assertEqual(1, $row['value']);
    }

    function testGetSubType() {
        // pass in something besides an object
        $method = $this->getMethod('getSubType', true);
        $result = $method(true);
        $this->assertSame('RightNow\Connect\v1_4\Meta', $result);

        // with an object
        $meta = Connect\Contact::getMetaData();
        $result = $method($meta);
        $this->assertSame('RightNow\Connect\v1_4\Contact', $result);

    }

    function testGetSubData() {
        $method = $this->getMethod('getSubData', true);
        $meta = Connect\Incident::getMetaData();
        $this->assertNull($method($meta));
        $this->assertSame('kind: 4 value: 255', $method($meta->LookupName->constraints[0]));
    }

    function testGetSubField() {
        $method = $this->getMethod('getSubField', true);
        $this->assertNull($method(new \stdClass));
        $this->assertSame(123, $method(new \stdClass, 123));

        $meta = Connect\Incident::getMetaData();
        $this->assertNull($method($meta));
        $this->assertSame(456, $method($meta, 456));
    }

    function testParseSelectQuery() {
        $method = $this->getMethod('parseSelectQuery');
        $getQueryParts = $this->getMethod('getQueryParts');
        $defaultLimit = 100;

        // Ensure we never tack on a 'LIMIT 0'
        $query = 'select contact from Contact';
        $parts = $getQueryParts(trim(str_replace('  ', ' ', $query)));
        foreach(array(0, null, '0', 'null') as $limit) {
            $results = $method($query, $parts, $limit);
            $this->assertEqual($query, $results[0]);
            $this->assertEqual(null, $results[3]); // limit
        }

        $parseSelectQuery = function($query, $limit = null, $page = 0) use ($method, $getQueryParts, $defaultLimit) {
            return $method($query, $getQueryParts(trim(str_replace('  ', ' ', $query))), $limit === null ? $defaultLimit : $limit, $page);
        };

        $queries = array(
            array( // Add a limit
                'SELECT * FROM Contact',
                'SELECT * FROM Contact LIMIT 100',
                'Contact',
                '*',
            ),
            array( // Preserve line breaks and strip extra spaces
                " SELECT\n  c.ID\nFROM Contact c \nLIMIT\n 100  ",
                "SELECT\nc.ID\nFROM Contact c\nLIMIT\n100",
                'Contact',
                'c.ID',
            ),
            array( // Preserve case
                'select count() from Incident where Subject like "%phone%" limit 100',
                'select count() from Incident where Subject like "%phone%" limit 100',
                'Incident',
                'count()',
            ),
            array( // Change incidents -> Incident
                'SELECT ID, LookupName from incidents LIMIT 100',
                'SELECT ID, LookupName from Incident LIMIT 100',
                'Incident',
                'ID, LookupName',
            ),
            array( // Aggregate query
                'SELECT COUNT() FROM Contact',
                'SELECT COUNT() FROM Contact LIMIT 100',
                'Contact',
                'COUNT()',
            ),
            array( // Ends with semi-colon
                "SELECT LookupName FROM Contact WHERE LookupName LIKE 'A%';",
                "SELECT LookupName FROM Contact WHERE LookupName LIKE 'A%' LIMIT 100",
                'Contact',
                'LookupName',
            ),
            // @@@ QA 130717-000061
            array( // Ends with semi-colon, no 'where' clause
                "SELECT LookupName FROM Mailbox;",
                "SELECT LookupName FROM Mailbox LIMIT 100",
                'Mailbox',
                'LookupName',
            ),
            array( //Over maximum limit value
                "SELECT Incident FROM Incident Limit 10001",
                "SELECT Incident FROM Incident LIMIT 10000",
                "Incident",
                "Incident"
            ),
            array( //Over maximum limit value with offset
                "SELECT Incident FROM Incident lImIt 9352532 Offset 10",
                "SELECT Incident FROM Incident LIMIT 10000 Offset 10",
                "Incident",
                "Incident"
            ),
            array( //At maximum limit value
                "SELECT Incident FROM Incident limit 10000",
                "SELECT Incident FROM Incident limit 10000",
                "Incident",
                "Incident"
            ),
            array( // With some new lines using \n
                "SELECT\nIncident\nFROM\nIncident\nLIMIT\n10000",
                "SELECT\nIncident\nFROM\nIncident\nLIMIT\n10000",
                "Incident",
                "Incident"
            ),
            array( // With some new lines using \r\n
                "SELECT\r\nIncident\r\nFROM\r\nIncident\r\nLIMIT\n10000",
                "SELECT\nIncident\nFROM\nIncident\nLIMIT\n10000",
                "Incident",
                "Incident"
            ),
        );

        foreach($queries as $pair) {
            list($query, $expectedQuery, $expectedObjectName, $expectedColumns) = $pair;
            list($actualQuery, $actualObjectName, $actualColumns) = $parseSelectQuery($query);
            $this->assertEqual($expectedQuery, $actualQuery);
            $this->assertEqual($expectedObjectName, $actualObjectName);
            $this->assertEqual($expectedColumns, $actualColumns);
        }
    }

    function testGetQueryParts() {
        $getQueryParts = $this->getMethod('getQueryParts', true);
        $parts = $getQueryParts("select * FROM Contact c \nWHERE c.LookupName LIKE 'Billy%'");
        $expected = array(
            'raw' => array(
                'select',
                '*',
                'FROM',
                'Contact',
                'c',
                '',
                '_CP_NEWLINE_',
                'WHERE',
                'c.LookupName',
                'LIKE',
                '\'Billy%\'',
            ),
            'upper' => array(
                'SELECT',
                '*',
                'FROM',
                'CONTACT',
                'C',
                '',
                '_CP_NEWLINE_',
                'WHERE',
                'C.LOOKUPNAME',
                'LIKE',
                '\'BILLY%\'',
            ),
        );
        $this->assertEqual($expected, $parts);
    }

    function testRestoreNewlines() {
        $restoreNewlines = $this->getMethod('restoreNewlines', true);

        $before = "blah blah _CP_NEWLINE_ blah";
        $after = $restoreNewlines($before);
        $this->assertEqual("blah blah\nblah", $after);

        $before = "blah blah _CP_NEWLINE_ _CP_NEWLINE_ blah";
        $after = $restoreNewlines($before);
        $this->assertEqual("blah blah\n\nblah", $after);
    }

    function testParseFieldName() {
        $parseFieldName = $this->getMethod('parseFieldName');

        $this->assertNull($parseFieldName(null));
        $this->assertNull($parseFieldName(''));
        $this->assertNull($parseFieldName(' '));

        $this->assertIdentical(array('Account', 'Account', array()), $parseFieldName('Account'));
        $this->assertIdentical(array('Account', 'Account.ID', array('ID')), $parseFieldName('Account.ID'));
        $this->assertIdentical(array('CO', 'CO', array()), $parseFieldName('CO'));
        $this->assertIdentical(array('CO\\Defect', 'CO\\Defect', array()), $parseFieldName('CO.Defect'));

        $expected = array(
            'CO\\Defect',
            'CO\\Defect.CO.whatever',
            array('CO', 'whatever'),
        );
        $actual = $parseFieldName('CO.Defect.CO.whatever');
        $this->assertIdentical($expected, $actual);

        $expected = array(
            'Account',
            'Account.CustomFields.CO.AcctText',
            array('CustomFields', 'CO', 'AcctText'),
        );
        $actual = $parseFieldName('Account.CustomFields.CO.AcctText');
        $this->assertIdentical($expected, $actual);

        // test custom fields in different package names
        $this->assertIdentical(array('Market_Test\\Bar', 'Market_Test\\Bar', array()), $parseFieldName('Market_Test.Bar'));
        $this->assertIdentical(array('Market_Test\\Bar', 'Market_Test\\Bar.Name', array('Name')), $parseFieldName('Market_Test.Bar.Name'));
    }

    function testGetClassnameOrValue() {
        $method = $this->getMethod('getClassnameOrValue');
        $this->assertEqual('aString', $method('aString'));
        $this->assertEqual(123, $method(123));
        $this->assertEqual(null, $method(null));

        $name = CONNECT_NAMESPACE_PREFIX . '\\Contact';
        $contact = new $name();
        $this->assertEqual('Connect\\v1_4\\Contact', $method($contact));
    }
}
