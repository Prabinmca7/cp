<?

use RightNow\Libraries\ConnectTabular,
    RightNow\Libraries\TabularDataObject;

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class ConnectTabularTest extends CPTestCase {
    public $testingClass = 'RightNow\Libraries\ConnectTabular';

    function testQueryCachesInstances () {
        $query1 = ConnectTabular::query('select * from yo', 'diamonds');
        $query2 = ConnectTabular::query('select foo from bar', 'diamonds');
        $this->assertReference($query1, $query2);
        $query1->getFirst();
        $this->assertIsA($query1->error, 'string');
        $this->assertSame($query1->error, $query2->error);
    }

    function testCachingInstancesCanBeOptedOut () {
        $query1 = ConnectTabular::query('select * from yo');
        $query2 = ConnectTabular::query('select * from yo', false);
        $query1->getFirst();
        $this->assertIsA($query1->error, 'string');
        $this->assertNull($query2->error);
    }

    function testGetFirstWithSubFields () {
        $query = <<<ROQL
SELECT
    q.ID,
    q.LookupName,
    q.CreatedTime,
    q.UpdatedTime,
    q.ParentCreatedByCommunityUser.ID AS 'CreatedByCommunityUser.ID',
    q.ParentCreatedByCommunityUser.DisplayName AS 'CreatedByCommunityUser.DisplayName',
    q.ParentCreatedByCommunityUser.AvatarURL AS 'CreatedByCommunityUser.AvatarURL',
    q.Body,
    q.BodyContentType,
    q.Category,
    q.LastActivityTime,
    q.Product,
    q.StatusWithType.Status AS 'StatusWithType.Status.ID',
    q.StatusWithType.StatusType AS 'StatusWithType.StatusType.ID',
    q.Subject,
    q.BestCommunityQuestionAnswers.CommunityComment AS BestCommunityQuestionAnswerCommentID,
    q.BestCommunityQuestionAnswers.BestAnswerType AS BestCommunityQuestionAnswerType,
    q.ContentRatingSummaries.NegativeVoteCount,
    q.ContentRatingSummaries.PositiveVoteCount,
    q.ContentRatingSummaries.RatingTotal,
    q.ContentRatingSummaries.RatingWeightedCount,
    q.ContentRatingSummaries.RatingWeightedTotal
FROM CommunityQuestion q
WHERE q.ID = 1
LIMIT 1
ROQL;
        $tab = ConnectTabular::query($query, 'hey');
        $result = $tab->getFirst();

        $this->assertTrue(property_exists($result, 'ID'));
        $this->assertTrue(property_exists($result, 'LookupName'));
        $this->assertTrue(property_exists($result, 'CreatedTime'));
        $this->assertTrue(property_exists($result, 'UpdatedTime'));
        $this->assertTrue(property_exists($result, 'ParentCreatedByCommunityUser'));
        $this->assertTrue(property_exists($result, 'Body'));
        $this->assertTrue(property_exists($result, 'BodyContentType'));
        $this->assertTrue(property_exists($result, 'Category'));
        $this->assertTrue(property_exists($result, 'LastActivityTime'));
        $this->assertTrue(property_exists($result, 'Product'));
        $this->assertTrue(property_exists($result, 'Subject'));

        $this->assertTrue(property_exists($result->ParentCreatedByCommunityUser, 'ID'));
        $this->assertTrue(property_exists($result->ParentCreatedByCommunityUser, 'DisplayName'));
        $this->assertTrue(property_exists($result->ParentCreatedByCommunityUser, 'AvatarURL'));
        $this->assertTrue(property_exists($result->CreatedByCommunityUser, 'ID'));
        $this->assertTrue(property_exists($result->CreatedByCommunityUser, 'DisplayName'));
        $this->assertTrue(property_exists($result->CreatedByCommunityUser, 'AvatarURL'));

        $this->assertTrue(property_exists($result, 'StatusWithType'));
        $this->assertTrue(property_exists($result->StatusWithType, 'Status'));
        $this->assertTrue(property_exists($result->StatusWithType, 'StatusType'));

        $this->assertTrue(property_exists($result, 'BestCommunityQuestionAnswers'));
        $this->assertTrue(property_exists($result->BestCommunityQuestionAnswers, 'CommunityComment'));
        $this->assertTrue(property_exists($result->BestCommunityQuestionAnswers, 'BestAnswerType'));
        $this->assertTrue(property_exists($result, 'BestCommunityQuestionAnswerCommentID'));
        $this->assertTrue(property_exists($result, 'BestCommunityQuestionAnswerType'));

        $this->assertTrue(property_exists($result, 'ContentRatingSummaries'));
        $this->assertTrue(property_exists($result->ContentRatingSummaries, 'NegativeVoteCount'));
        $this->assertTrue(property_exists($result->ContentRatingSummaries, 'PositiveVoteCount'));
        $this->assertTrue(property_exists($result->ContentRatingSummaries, 'RatingTotal'));
        $this->assertTrue(property_exists($result->ContentRatingSummaries, 'RatingWeightedCount'));
        $this->assertTrue(property_exists($result->ContentRatingSummaries, 'RatingWeightedTotal'));
    }

    function testSpaces () {
        $roql = "SELECT Body as 'The Body' from CommunityComment WHERE ID=1";
        $query = \RightNow\Libraries\ConnectTabular::query($roql);
        $result = $query->getFirst();
        $this->assertIsA($result->Body, 'string');
        $property = "The Body";
        $this->assertIsA($result->{$property}, 'string');
    }

    function testCommentWithAliases () {
        $roql = <<<ROQL
SELECT
    c.ID,
    c.LookupName,
    c.CreatedTime,
    c.UpdatedTime,
    c.ParentCreatedByCommunityUser.ID AS Author_ID,
    c.ParentCreatedByCommunityUser.DisplayName AS Author_DisplayName,
    c.ParentCreatedByCommunityUser.AvatarURL AS Author_AvatarURL,
    c.Body,
    c.BodyContentType,
    c.Parent as 'Parent.ID',
    c.CommunityQuestion,
    c.StatusWithType.Status,
    c.StatusWithType.StatusType,
    c.Type,

    c.Parent.level1,
    c.Parent.level2,
    c.Parent.level3,
    c.Parent.level4,

    c.ContentRatingSummaries.NegativeVoteCount,
    c.ContentRatingSummaries.PositiveVoteCount,
    c.ContentRatingSummaries.RatingTotal,
    c.ContentRatingSummaries.RatingWeightedCount,
    c.ContentRatingSummaries.RatingWeightedTotal
FROM CommunityComment c
WHERE c.CommunityQuestion.ID = 1
ROQL;
        $query = \RightNow\Libraries\ConnectTabular::query($roql);
        $result = $query->getFirst();
        $this->assertIdentical($result->Author_ID, $result->ParentCreatedByCommunityUser->ID);
        $this->assertIdentical($result->Author_DisplayName, $result->ParentCreatedByCommunityUser->DisplayName);
        $this->assertIdentical($result->Author_AvatarURL, $result->ParentCreatedByCommunityUser->AvatarURL);
        $this->assertTrue(count($query->warnings) > 0);
    }

    function testGetFirstWithBadQuery () {
        $roql = "SLECT * from CommunityCommentFlg";
        $query = \RightNow\Libraries\ConnectTabular::query($roql);
        $result = $query->getFirst();
        $this->assertNull($result);
        $this->assertIsA($query->error, 'string');
    }

    function testGetFirstWithBadColumn () {
        $roql = "SELECT CommunityCommentFlg from CommunityCommentFlg";
        $query = \RightNow\Libraries\ConnectTabular::query($roql);
        $result = $query->getFirst();
        $this->assertNull($result);
        $this->assertIsA($query->error, 'string');
    }

    function testGetFirstWithDecorator(){
        $roql = "SELECT ID from CommunityComment where ID = 5";
        $query = \RightNow\Libraries\ConnectTabular::query($roql);
        $result = $query->getFirst('Permission/SocialCommentPermissions');
        $this->assertIsA($result, 'RightNow\Libraries\TabularDataObject', $result);
        $this->assertIsA($result->SocialCommentPermissions, 'RightNow\Decorators\SocialCommentPermissions');

        $roql = "SELECT ID from CommunityComment where ID = 7";
        $query = \RightNow\Libraries\ConnectTabular::query($roql);
        $result = $query->getFirst(array('class' => 'Permission/SocialCommentPermissions', 'property' => 'stuff'));
        $this->assertIsA($result, 'RightNow\Libraries\TabularDataObject');
        $this->assertIsA($result->stuff, 'RightNow\Decorators\SocialCommentPermissions');

        $roql = "SELECT ID from CommunityComment where ID = 10";
        $query = \RightNow\Libraries\ConnectTabular::query($roql);
        try{
            $result = $query->getFirst('asdf');
            $this->fail('Adding a decorator that does not exist should throw an exception');
        }
        catch(\Exception $e){}
    }

    function testGetCollectionWithBadQuery () {
        $roql = "SLECT * from CommunityCommentFlg";
        $query = \RightNow\Libraries\ConnectTabular::query($roql);
        $result = $query->getCollection();
        $this->assertIdentical(array(), $result);
        $this->assertIsA($query->error, 'string');
    }

    function testGetCollectionWithBadColumn () {
        $roql = "SELECT CommunityCommentFlg from CommunityCommentFlg";
        $query = \RightNow\Libraries\ConnectTabular::query($roql);
        $result = $query->getCollection();
        $this->assertIdentical(array(), $result);
        $this->assertIsA($query->error, 'string');
    }

    function testGetCollectionWithDecorator(){
        $roql = "SELECT ID from CommunityComment where ID IN (3, 4, 5)";
        $query = \RightNow\Libraries\ConnectTabular::query($roql);
        $result = $query->getCollection('Permission/SocialCommentPermissions');

        $this->assertIdentical(3, count($result));
        foreach($result as $queryItem){
            $this->assertIsA($queryItem, 'RightNow\Libraries\TabularDataObject');
            $this->assertIsA($queryItem->SocialCommentPermissions, 'RightNow\Decorators\SocialCommentPermissions');
        }

        $roql = "SELECT ID from CommunityComment where ID IN (6, 7, 8)";
        $query = \RightNow\Libraries\ConnectTabular::query($roql);
        $result = $query->getCollection(array('class' => 'Permission/SocialCommentPermissions', 'property' => 'stuff'));
        $this->assertIdentical(3, count($result));
        foreach($result as $queryItem){
            $this->assertIsA($queryItem, 'RightNow\Libraries\TabularDataObject');
            $this->assertIsA($queryItem->stuff, 'RightNow\Decorators\SocialCommentPermissions');
        }

        $roql = "SELECT ID from CommunityComment where ID IN (9, 10, 11)";
        $query = \RightNow\Libraries\ConnectTabular::query($roql);
        try{
            $result = $query->getCollection('asdf');
            $this->fail('Adding a decorator that does not exist should throw an exception');
        }
        catch(\Exception $e){}
    }

    function testGetFirstWithStarSelect () {
        $query = <<<ROQL
SELECT
    *
FROM CommunityQuestion
WHERE ID = 1
LIMIT 1
ROQL;
        $tab = ConnectTabular::query($query, 'no');
        $result = $tab->getFirst();
$this->dump($result);
        $this->assertNull($result);
        $this->assertStringContains($tab->error, '*');
    }

    function testGetCollectionWithStarSelect () {
        $query = <<<ROQL
SELECT
    *
FROM CommunityQuestion
LIMIT 10
ROQL;
        $tab = ConnectTabular::query($query, 'oklahoma');
        $result = $tab->getCollection();

        $this->assertIdentical(array(), $result);
        $this->assertStringContains($tab->error, '*');
    }

    function testMergeQueryResults () {
        $a = new TabularDataObject(array('inter' => 'lude', 'cat' => 'mouse'));
        $b = new TabularDataObject(array('inter' => 'communication', 'cats' => 'meece'));
        $c = ConnectTabular::mergeQueryResults($a, $b);
        $expected = new TabularDataObject(array('inter' => 'communication', 'cat' => 'mouse', 'cats' => 'meece'));
        $this->assertIdentical($expected, $c);
    }
}

class TabularDataObjectTest {
    function testConstructorPopulatesProperties () {
        $obj = new TabularDataObject(array(
            'bananas' => 'interlude',
            'hope' => array('help'),
        ));

        $this->assertSame('interlude', $obj->bananas);
        $this->assertIdentical(array('help'), $obj->hope);
    }
}
