<?

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

use RightNow\Connect\v1_4 as Connect;

class SocialSearchTest extends CPTestCase {
    public $testingClass = 'RightNow\Models\SocialSearch';

    function __construct() {
        parent::__construct();
        $this->model = new RightNow\Models\SocialSearch;
    }

    function testNoResultSearch () {
        $output = $this->model->search(array('query' => array('value' => '(ﾉಥ益ಥ）ﾉ﻿ ┻━┻')));
        $this->assertResponseObject($output);
        $this->assertIdentical(array(), $output->result->results);
        $this->assertSame(0, $output->result->size);
    }

    function testNoQuery () {
        $output = $this->model->search();
        $this->assertResponseObject($output, 0, 1);
        $this->assertIdentical(array(), $output->result->results);
        $this->assertSame(0, $output->result->size);
    }

    function testResultSearch () {
        $filters = $this->getFilters();
        // sort by created date in ascending order so KF is less likely to return recently deleted content
        $filters['sort']['value'] = 2;
        $filters['direction']['value'] = 2;
        $output = $this->model->search($filters);
        $this->assertResponseObject($output);
        $this->assertIdentical($filters['query'], $output->result->filters['query']);
        $this->assertNotEqual($output->result->size, 0);
        $this->assertTrue($output->result->size < 11, "results are too big? " . var_export($output->result->size, true));
        $total = count($output->result->results);

        foreach($output->result->results as $result) {
            $question = get_instance()->Model('CommunityQuestion')->get($result->SocialSearch->id)->result;
            $this->assertEqual('Active', $question->StatusWithType->Status->LookupName);
            $this->assertEqual($result->SocialSearch->author->ID, $question->CreatedByCommunityUser->ID);
        }
    }

    function testMultipleFilters() {
        // 150521-000035
        $this->assertIdentical('', '');
        return;
        $filters = $this->getFilters(array(
            'query' => 'question',
            'status' => '29,30,31,32',
            'numberOfBestAnswers' => '0,1,2,3,4,5',
            'limit' => 100,
            'offset' => 2,
            'product' => '1,6,140,162,163',
        ));
        $output = $this->model->search($filters);
        $this->assertNotEqual(0, $output->result->size);
    }

    function testConstraints() {
        $method = $this->getMethod('constraints');
        $filters = $this->getFilters(array(
            'limit' => 100,
            'offset' => 2,
            'sort' => 2,
            'direction' => 1,
        ));
        $output = $method($filters);
        $this->assertIsA($output, 'RightNow\Connect\Knowledge\v1\ContentSearchConstraints');
        $this->assertEqual($output->Limit, 100);
        $this->assertEqual($output->Start, 2);
        $this->assertIsA($output->SortOptions, 'RightNow\Connect\Knowledge\v1\ContentSortOptions');
        $this->assertEqual(2, $output->SortOptions->SortField->ID);
        $this->assertEqual('CreatedTime', $output->SortOptions->SortField->LookupName);
        $this->assertEqual(1, $output->SortOptions->SortOrder->ID);
        $this->assertEqual('Descending', $output->SortOptions->SortOrder->LookupName);
        $this->assertIsA($output->Filters, 'RightNow\Connect\Knowledge\v1\DomainContentFilterArray');
    }

    function testFilter() {
        $method = $this->getMethod('filter');
        $filters = $this->getFilters(array(
            'product' => 1,
            'category' => 2,
        ));
        $output = $method($filters);
        $this->assertIsA($output, 'RightNow\Connect\Knowledge\v1\DomainContentFilter');
        $this->assertIsA($output->Domain, 'RightNow\Connect\v1_4\NamedIDOptList');
        $this->assertEqual(10003, $output->Domain->ID);
        $this->assertEqual('Product = 1 AND Category = 2', $output->Filter);
    }

    function testFilterString() {
        // function testFilterString($value, $filterName, $operations = array('=', 'IN')) {
        $method = $this->getMethod('filterString');

        $expected = 'someField = 1';
        $actual = $method(1, 'someField');
        $this->assertEqual($expected, $actual);

        $expected = 'someField IN (1,2)';
        $actual = $method('1,2', 'someField');
        $this->assertEqual($expected, $actual);

        $expected = "someField BETWEEN 'abc' AND 'def'";
        $actual = $method('abc,def', 'someField', array('=','BETWEEN'));
        $this->assertEqual($expected, $actual);

        // BETWEEN ignored if 'IN' specified
        $expected = "someField IN (abc,def)";
        $actual = $method('abc,def', 'someField', array('=','IN','BETWEEN'));
        $this->assertEqual($expected, $actual);

        $expected = 'someField IN ("1","2")';
        $actual = $method('"1","2"', 'someField');
        $this->assertEqual($expected, $actual);

        $expected = 'someField IN (\'1\',\'2\')';
        $actual = $method('\'1\',\'2\'', 'someField');
        $this->assertEqual($expected, $actual);

        $expected = "createdTime BETWEEN '2014-09-16T15:23:10Z' AND '2014-09-17T15:23:10Z'";
        $actual = $method('2014-09-16T15:23:10Z, 2014-09-17T15:23:10Z', 'createdTime', array('BETWEEN'));
        $this->assertEqual($expected, $actual);

        $this->assertNull($method(null, 'createdTime', array('BETWEEN')));
        $this->assertNull($method('', 'createdTime', array('BETWEEN')));
        $this->assertNull($method('2014-09-16T15:23:10Z', 'createdTime', array('BETWEEN')));
        $this->assertNull($method('2014-09-16T15:23:10Z, 2014-09-17T15:23:10Z, 2014-09-18T15:23:10Z', 'createdTime', array('BETWEEN')));
    }

    function testCombineFilterStrings() {
        $method = $this->getMethod('combineFilterStrings');
        $filters = $this->getFilters(array(
            'author'              => 1,
            'category'            => 2,
            'createdTime'         => '2014-09-16T15:23:10Z, 2014-09-17T15:23:10Z',
            'numberOfBestAnswers' => '3,4,5',
            'product'             => 6,
            'status'              => 29,
            'updatedTime'         => '2014-09-16T15:23:10Z, 2014-09-17T15:23:10Z',
        ));
        $expected = "NumberOfBestAnswers IN (3,4,5) AND UpdatedTime BETWEEN '2014-09-16T15:23:10Z' AND '2014-09-17T15:23:10Z' AND CreatedTime BETWEEN '2014-09-16T15:23:10Z' AND '2014-09-17T15:23:10Z' AND Author = 1 AND Product = 6 AND Category = 2 AND Status = 29";
        $actual = $method($filters);
        $this->assertEqual($expected, $actual);

        $expected = '';
        $actual = $method(array(
            'product' => array('value' => null),
            'unknownFilterToBeIgnored' => array('value' => 'whatever'),
        ));
        $this->assertEqual($expected, $actual);

        $expected = "Product IN (\"1\",\"2\",\"3\") AND Category IN ('4','5','6')";
        $actual = $method(array(
            'product' => array('value' => '"1", "2", "3"'),
            'category' => array('value' => "'4', '5', '6'"),
        ));
        $this->assertEqual($expected, $actual);
    }

    function getField($fieldName, $search = 'question', $id = null) {
        $filters = array();
        if ($search !== null) {
            $filters[] = "(Subject LIKE '%$search%' OR Body LIKE '%$search%')";
            $filters[] = "$fieldName IS NOT NULL";
        }

        if ($id !== null) {
            $filters[] = "ID == {$id}";
        }

        $query = "SELECT ID, $fieldName FROM CommunityQuestion WHERE " . implode(' AND ', $filters) . " LIMIT 1";

        return Connect\ROQL::query($query)->next()->next();
    }

    function testProductFilter() {
        // 150521-000035
        $this->assertIdentical('', '');
        return;
        $fieldName = 'Product';
        $queryResults = $this->getField($fieldName);
        $productFromQueryResults = $queryResults[$fieldName];
        $filters = $this->getFilters(array('product' => $productFromQueryResults));
        $modelResults = $this->model->search($filters);
        $this->assertNotEqual(0, $modelResults->result->size);
        $queryResults = $this->getField($fieldName, null, $modelResults->result->results[0]->SocialSearch->id);
        $productFromModelResults = $queryResults[$fieldName];
        $this->assertEqual($productFromQueryResults, $productFromModelResults);
    }

    function testCategoryFilter() {
        // 150521-000035
        $this->assertIdentical('', '');
        return;
        //Category filters are ignored on Social Queries, thus, this should still return results even with an invalid category filter
        $outputNoFilters = $this->model->search($this->getFilters());
        $filters = $this->getFilters(array('category' => '1234567899'), 'category');
        $output = $this->model->search($filters);
        $this->assertNotEqual(0, $output->result->size);
        $this->assertEqual($outputNoFilters->result->results[0], $output->result->results[0]);
    }

    function testAuthorFilter() {
        // 150521-000035
        $this->assertIdentical('', '');
        return;
        $fieldName = 'CreatedByCommunityUser';
        $queryResults = $this->getField($fieldName);
        $author = $queryResults[$fieldName];
        $filters = $this->getFilters(array('author' => $author));
        $modelResults = $this->model->search($filters);
        $this->assertNotEqual(0, $modelResults->result->size);
        $this->assertEqual($author, $modelResults->result->results[0]->SocialSearch->author->ID);
    }

    function testStatusFilter() {
        // 150521-000035
        $this->assertIdentical('', '');
        return;
        $queryResults = $this->getField('StatusWithType.Status');
        $statusFromQueryResults = $queryResults['Status'];
        $filters = $this->getFilters(array('status' => $statusFromQueryResults));
        $modelResults = $this->model->search($filters);
        $this->assertNotEqual(0, $modelResults->result->size);
        $queryResults = $this->getField('StatusWithType.Status', null, $modelResults->result->results[0]->SocialSearch->id);
        $statusFromModelResults = $queryResults['Status'];
        $this->assertEqual($statusFromQueryResults, $statusFromModelResults);
    }

    function testCreatedTimeFilter() {
        $this->timeFilters('CreatedTime', 'createdTime', 'created');
    }

    function testUpdatedTimeFilter() {
        $this->timeFilters('UpdatedTime', 'updatedTime', 'updated');
    }

    function testTimeFilter() {
        $method = $this->getMethod('timeFilter');

        $this->assertNull($method(null));

        $input = '2013-11-17T11:09:09Z,2014-11-17T11:09:09Z';
        $this->assertIdentical($input, $method($input));

        foreach (array('day', 'week', 'month', 'year') as $interval) {
            $output = $method($interval);
            list($fromDate, $toDate) = explode(',', $output);
            $fromDate = strtotime($fromDate);
            $toDate = strtotime($toDate);
            $this->assertTrue($fromDate < $toDate);
        }
    }

    // Test CreatedTime and UpdatedTime Filters
    function timeFilters($fieldName, $filterName, $KFName) {
        $query = "SELECT %s($fieldName) as %s FROM CommunityQuestion WHERE (Subject LIKE '%%question%%' OR Body LIKE '%%question%%') AND StatusWithType.Status = 29";

        $result = Connect\ROQL::query(sprintf($query, 'MIN', 'min'))->next()->next();
        $min = strtotime($result['min']);

        $result = Connect\ROQL::query(sprintf($query, 'MAX', 'max'))->next()->next();
        $max = strtotime($result['max']);

        if ($min === $max) {
            // KF won't return results if $min === $max
            $max = $min + (60*60*24);
        }

        $toDate = function($epoch) {
            $dt = new DateTime("@$epoch");
            return $dt->format('Y-m-d\TH:i:s\Z');
        };

        $filters = $this->getFilters(array(
            $filterName => $toDate($min) . ', ' . $toDate($max),
        ));

        $output = $this->model->search($filters);
        $this->assertNotEqual(0, $output->result->size);
        foreach($output->result->results as $question) {
            $time = $question->$KFName;
            $this->assertTrue($time >= $min, "Expected $time to be greater than or equal to the min ($min)");
            $this->assertTrue($time <= $max, "Expected $time to be less than or equal to the max ($max)");
        }
    }

    function testNumberOfBestAnswersFilter() {
        // 150521-000035
        $this->assertIdentical('', '');
        return;
        $filtersArray = array('query' => 'post');

        // verify that query returns at least one question with no best answers and at least one question with at least one best answer
        $filters = $this->getFilters($filtersArray);
        $output = $this->model->search($filters);
        $this->assertNotEqual(0, $output->result->size);
        $foundNone = $foundSome = false;
        foreach($output->result->results as $question) {
            if ($question->SocialSearch->bestAnswerCount === 0)
                $foundNone = true;
            else
                $foundSome = true;
        }
        $this->assertTrue($foundNone && $foundSome, "Query did not return a mix of questions. 0?: " . var_export($foundNone, true) . ", some?: " . var_export($foundSome, true));

        $filtersArray['numberOfBestAnswers'] = '1,2';
        $filters = $this->getFilters($filtersArray);
        $output = $this->model->search($filters);
        $this->assertNotEqual(0, $output->result->size);
        foreach($output->result->results as $question) {
            $bestAnswerCount = $question->SocialSearch->bestAnswerCount;
            $this->assertTrue($bestAnswerCount === 1 || $bestAnswerCount === 2);
        }

        $filtersArray['numberOfBestAnswers'] = 'yes';
        $filters = $this->getFilters($filtersArray);
        $output = $this->model->search($filters);
        $this->assertNotEqual(0, $output->result->size);
        foreach($output->result->results as $question) {
            $bestAnswerCount = $question->SocialSearch->bestAnswerCount;
            $this->assertNotEqual(0, $bestAnswerCount);
        }

        $filtersArray['numberOfBestAnswers'] = 'no';
        $filters = $this->getFilters($filtersArray);
        $output = $this->model->search($filters);
        $this->assertNotEqual(0, $output->result->size);
        foreach($output->result->results as $question) {
            $bestAnswerCount = $question->SocialSearch->bestAnswerCount;
            $this->assertEqual(0, $bestAnswerCount);
        }
    }

    function testSortAscending() {
        // 150521-000035
        $this->assertIdentical('', '');
        return;
        $output = $this->model->search($this->getFilters(array(
            'query' => 'active',
            'sort' => 2, // CreatedTime
            'direction' => 2, // Ascending
        )));
        $lastDate = null;
        $lastID = null;
        $this->assertNotEqual(0, $output->result->size);
        foreach($output->result->results as $question) {
            $thisDate = $question->created;
            $thisID = $question->SocialSearch->id;
            $this->assertTrue($thisDate >= $lastDate, "NOT sorting Ascending by CreatedTime: lastDate: $lastDate ($lastID), thisDate: $thisDate ($thisID)");
            $lastDate = $thisDate;
            $lastID = $thisID;
        }
    }

    function testSortDescending() {
        // 150521-000035
        $this->assertIdentical('', '');
        return;
        $output = $this->model->search($this->getFilters(array(
            'query' => 'active',
            'sort' => 2, // CreatedTime
            'direction' => 1, // Descending
        )));
        $lastDate = null;
        $lastID = null;
        $this->assertNotEqual(0, $output->result->size);
        foreach($output->result->results as $question) {
            $thisDate = $question->created;
            $thisID = $question->SocialSearch->id;
            if ($lastDate) {
                $this->assertTrue($thisDate <= $lastDate, "NOT sorting Descending by CreatedTime: lastDate: $lastDate ($lastID), thisDate: $thisDate ($thisID)");
            }
            $lastDate = $thisDate;
            $lastID = $thisID;
        }
    }

    function testLimit() {
        $limit = 5;
        $output = $this->model->search($this->getFilters(array(
            'query' => 'question',
            'limit' => $limit,
        )));
        //$this->assertEqual($limit, count($output->result->results));
        // 150521-000035
        $this->assertTrue($limit >= count($output->result->results));
    }

    function testOffset() {
        // 150521-000035
        $this->assertIdentical('', '');
        return;
        $idToTrack = $initialIndex = null;
        // Iterate through offsets of 0,1,2,3 and ensure the question ID that
        // started at the end of the list moves over one position each time.
        foreach(range(0, 4) as $offset) {
            $output = $this->model->search($this->getFilters(array(
                'query' => 'question',
                'limit' => 5,
                'offset' => $offset,
            )));
            $results = $output->result->results;
            $numberOfResults = count($results);
            $ids = array_map(function($x) {return $x->SocialSearch->id;}, $results);
            if ($idToTrack) {
                $index = array_search($idToTrack, $ids);
                $this->assertEqual($initialIndex - $offset, $index);
            }
            else {
                $idToTrack = array_pop($ids);
                $initialIndex = $numberOfResults - 1;
            }
        }
    }

    function testGetFilterValuesForFilterType() {
        $output = $this->model->getFilterValuesForFilterType('someInvalidFilterType');
        $this->assertNull($output->results);

        $output = $this->model->getFilterValuesForFilterType('updatedTime');
        $this->assertEqual('day', $output->result[0]->ID);
        $this->assertEqual('day', $output->result[0]->LookupName);

        $output = $this->model->getFilterValuesForFilterType('numberOfBestAnswers');
        $this->assertEqual('yes', $output->result[0]->ID);
        $this->assertEqual('yes', $output->result[0]->LookupName);
        $this->assertEqual('no', $output->result[1]->ID);
        $this->assertEqual('no', $output->result[1]->LookupName);

        $output = $this->model->getFilterValuesForFilterType('sort');
        $this->assertEqual('UpdatedTime', $output->result[0]->LookupName);
        $this->assertEqual('CreatedTime', $output->result[1]->LookupName);

        $output = $this->model->getFilterValuesForFilterType('direction');
        $this->assertEqual('Descending', $output->result[0]->LookupName);
        $this->assertEqual('Ascending', $output->result[1]->LookupName);
    }

    function testUpdatedTimeValues() {
        $method = $this->getMethod('updatedTimeValues');
        $this->assertNull($method('nope'));
        $output = $method();
        $this->assertEqual('day', $output[0]->ID);
        $this->assertEqual('day', $method('day'));
    }

    function testStripCommunityFilter() {
        $stripCommunityFilter = $this->getMethod('stripCommunityFilter');

        //if both category and product are supplied, don't strip off anything
        $prodcatFilters = array(
            'category' => '21',
            'product' => '163',
        );
        $filters = $this->getFilters($prodcatFilters);
        $filters = $stripCommunityFilter($filters);
        $this->assertSame($filters['category']['value'], $prodcatFilters['category']);
        $this->assertSame($filters['product']['value'], $prodcatFilters['product']);

        //if product and category are supplied and stripCommunityFilter is specified to strip off product, remove product
        $prodcatFilters = array(
            'category' => '21',
            'product' => '163',
        );
        $filters = $this->getFilters($prodcatFilters);
        $filters = $stripCommunityFilter($filters, 'product');
        $this->assertSame($filters['category']['value'], $prodcatFilters['category']);
        $this->assertSame($filters['product']['value'], '');

        //if product and category are supplied and stripCommunityFilter is specified to strip off category, remove category
        $prodcatFilters = array(
            'category' => '21',
            'product' => '163',
        );
        $filters = $this->getFilters($prodcatFilters);
        $filters = $stripCommunityFilter($filters, 'category');
        $this->assertSame($filters['category']['value'], '');
        $this->assertSame($filters['product']['value'], $prodcatFilters['product']);

        //if only category is set, category should be intact and product should be unset
        $catFilter= array(
            'category' => '21',
        );
        $filters = $this->getFilters($catFilter);
        $filters = $stripCommunityFilter($filters);
        $this->assertSame($filters['category']['value'], $catFilter['category']);
        $this->assertSame($filters['product']['value'], null);

        //if only category is set and stripCommunityFilter is specified to strip off category, strip off category
        $catFilter= array(
            'category' => '21',
        );
        $filters = $this->getFilters($catFilter);
        $filters = $stripCommunityFilter($filters, 'category');
        $this->assertSame($filters['category']['value'], '');
        $this->assertSame($filters['product']['value'], null);

        //if only product is set, product should be intact and category should be unset
        $prodFilter = array(
            'product' => '163',
        );
        $filters = $this->getFilters($prodFilter);
        $filters = $stripCommunityFilter($filters);
        $this->assertSame($filters['category']['value'], null);
        $this->assertSame($filters['product']['value'], $prodFilter['product']);

        //if only product is set and stripCommunityFilter is specified to strip off product, strip off product
        $prodFilter = array(
            'product' => '163',
        );
        $filters = $this->getFilters($prodFilter);
        $filters = $stripCommunityFilter($filters, 'product');
        $this->assertSame($filters['category']['value'], null);
        $this->assertSame($filters['product']['value'], '');

        //if both category and product are supplied and $filterToStrip is passed incorrectly, nothing should be stripped off
        $prodcatFilters = array(
            'category' => '21',
            'product' => '163',
        );
        $filters = $this->getFilters($prodcatFilters);
        $filters = $stripCommunityFilter($filters, 'xyz');
        $this->assertSame($filters['category']['value'], $prodcatFilters['category']);
        $this->assertSame($filters['product']['value'], $prodcatFilters['product']);
    }

    function getFilters(array $filters = array()) {
        $allFilters = array(
            'query' => array(
                'value' => 'question',
                'key' => 'kw',
                'type' => 'query',
            ),
            'sort' => array(
                'value' => null,
                'key' => 'sort',
                'type' => 'sort',
            ),
            'direction' => array(
                'value' => null,
                'key' => 'dir',
                'type' => 'direction',
            ),
            'page' => array(
                'value' => null,
                'key' => 'page',
                'type' => 'page',
            ),
            'offset' => array(
                'value' => 0,
                'key' => 'offset',
                'type' => 'offset',
            ),
            'limit' => array(
                'value' => 10,
            ),
            'numberOfBestAnswers' => array(
                'value' => null,
                'key' => 'numberOfBestAnswers',
                'type' => 'numberOfBestAnswers',
            ),
            'updatedTime' => array(
                'value' => null,
                'key' => 'updatedTime',
                'type' => 'updatedTime',
            ),
            'createdTime' => array(
                'value' => null,
                'key' => 'createdTime',
                'type' => 'createdTime',
            ),
            'author' => array(
                'value' => null,
                'key' => 'author',
                'type' => 'author',
            ),
            'product' => array(
                'value' => null,
                'key' => 'p',
                'type' => 'product',
            ),
            'category' => array(
                'value' => null,
                'key' => 'c',
                'type' => 'category',
            ),
        );

        foreach($filters as $filterName => $value) {
            $allFilters[$filterName]['value'] = $value;
        }

        return $allFilters;
    }

    function testSortFilter () {
        $method = $this->getMethod('sortFilter');

        // No search arg; no other sort values
        $result = $method(null, null, '*');
        // Sort by updated (ID === 1), ascending (ID === 1)
        $this->assertSame($result->SortField->ID, 1);
        $this->assertSame($result->SortOrder->ID, 1);

        $result = $method(null, null, '');
        $this->assertSame($result->SortField->ID, 1);
        $this->assertSame($result->SortOrder->ID, 1);

        // No search arg; explicit sort values
        $result = $method(38, 43, '*');
        // Sorted by arbitrary args
        $this->assertSame($result->SortField->ID, 38);
        $this->assertSame($result->SortOrder->ID, 43);

        $result = $method(38, 43, '');
        $this->assertSame($result->SortField->ID, 38);
        $this->assertSame($result->SortOrder->ID, 43);

        // Arbitrary search arg; explicit sort values
        $result = $method(49, 88, 'hippo');
        // Sorted by arbitrary args
        $this->assertSame($result->SortField->ID, 49);
        $this->assertSame($result->SortOrder->ID, 88);

        // Arbitrary search arg; no sort values
        $result = $method(null, null, 'turtle');
        // No result - presumably sorted by relevance, so no sort object.
        $this->assertNull($result);

        // 150521-000035
        $this->assertIdentical('', '');
        return;

        // Test actual results from sort.
        // Get a very large number of answers for which to test against
        // to ensure proper sort; large chunks of social questions have
        // the same updated time.
        // (Max returned from a single query is 100)
        $results = $this->model->search($this->getFilters(array(
            'query' => '*',
            'limit' => 100,
        )))->result->results;

        $results = array_merge($results, $this->model->search($this->getFilters(array(
            'query' => '*',
            'offset' => 100,
            'limit' => 100,
        )))->result->results);

        $results = array_merge($results, $this->model->search($this->getFilters(array(
            'query' => '*',
            'offset' => 200,
            'limit' => 100,
        )))->result->results);

        for($i = 0; $i < count($results); $i++) {
            if($results[$i + 1])
                $this->assertTrue($results[$i]->updated >= $results[$i + 1]->updated,
                    "Times ($i): " . var_export($results[$i]->updated, true) . " (" . $results[$i]->SocialSearch->id
                    . "), " . var_export($results[$i + 1]->updated, true) . " (" . $results[$i + 1]->SocialSearch->id . ")");
        }
    }
}
