<?php

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class TestTopAnswers extends WidgetTestCase
{
    public $testingWidget = "standard/reports/TopAnswers";

    function testDefaultAttributes()
    {
        $this->createWidgetInstance();
        $data = $this->getWidgetData();
        $this->assertIsA($data['results'], 'RightNow\Connect\Knowledge\v1\SummaryContentArray');
        $this->assertIdentical(10, count($data['results']));
    }

    function testLimitAttribute()
    {
        $this->createWidgetInstance(array('limit' => 5));
        $data = $this->getWidgetData();
        $this->assertIsA($data['results'], 'RightNow\Connect\Knowledge\v1\SummaryContentArray');
        $this->assertIdentical(5, count($data['results']));
    }

    function testProductCategoryAttributes()
    {
        $this->createWidgetInstance(array('product_filter_id' => 2));
        $data = $this->getWidgetData();
        $this->assertIsA($data['results'], 'RightNow\Connect\Knowledge\v1\SummaryContentArray');
        $this->assertIdentical(9, count($data['results']));

        $this->setWidgetAttributes(array('category_filter_id' => 161));
        $data = $this->getWidgetData();
        $this->assertIsA($data['results'], 'RightNow\Connect\Knowledge\v1\SummaryContentArray');
        $this->assertIdentical(6, count($data['results']));

        $this->setWidgetAttributes(array('product_filter_id' => 2, 'category_filter_id' => 161));
        $data = $this->getWidgetData();
        $this->assertIsA($data['results'], 'RightNow\Connect\Knowledge\v1\SummaryContentArray');
        $this->assertIdentical(6, count($data['results']));
    }

    function testInvalidAttributes(){
        $this->createWidgetInstance(array('product_filter_id' => "stuff"));
        $method = $this->getWidgetMethod('getData');

        ob_start();
        $this->assertFalse($method());
        $this->setWidgetAttributes(array('product_filter_id' => array(1)));
        $this->assertFalse($method());
        $this->setWidgetAttributes(array('product_filter_id' => array(-10)));
        $this->assertFalse($method());
        $this->setWidgetAttributes(array('product_filter_id' => 100));
        $this->assertFalse($method());

        $this->createWidgetInstance(array('category_filter_id' => "stuff"));
        $this->assertFalse($method());
        $this->setWidgetAttributes(array('category_filter_id' => array(10)));
        $this->assertFalse($method());
        $this->setWidgetAttributes(array('category_filter_id' => -10));
        $this->assertFalse($method());
        $this->setWidgetAttributes(array('category_filter_id' => 100));
        $this->assertFalse($method());
        ob_end_clean();
    }

    function testEmptyResultSet(){
        $this->createWidgetInstance(array('product_filter_id' => 132));
        $method = $this->getWidgetMethod('getData');
        $this->assertFalse($method());
        $this->createWidgetInstance(array('product_filter_id' => 132, 'category_filter_id' => 161));
        $this->assertFalse($method());
    }

    function toLength($seed, $max = 256) {
        $output = '';
        while(strlen($output) < $max) {
            $output .= "$seed ";
        }
        return \RightNow\Utils\Text::truncateText($output, $max, true);
    }

    function testTruncateExcerptIfNeeded(){
        // Default behavior. Excerpts do not exceed 256 chars from Api
        $apiMaxLength = 256;
        $instance = $this->createWidgetInstance();
        $method = $this->getWidgetMethod('truncateExcerptIfNeeded', $instance);
        $excerpt = $this->toLength('and so on');
        $results = (array) $method((object) array(
            (object) array(
                'Excerpt' => $excerpt,
            ),
        ));
        $excerpt = $results[0]->Excerpt;
        // Ends with ellipsis and doesn't truncate a word
        $this->assertEqual('and...', substr($excerpt, -6));
        $excerpt = substr($excerpt, 0, -3); // Strip ellipsis
        $excerptLength = strlen($excerpt);
        $this->assertTrue($excerptLength <= $apiMaxLength);
        $this->assertTrue($excerptLength > $apiMaxLength - 4); // Close to max

        $input = (object) array(
            (object) array(
                'Excerpt' => $this->toLength('large'),
            ),
            (object) array(
                'Excerpt' => $this->toLength('medium', 35),
            ),
            (object) array(
                'Excerpt' => $this->toLength('small', 10),
            ),
        );

        $instance = $this->createWidgetInstance(array('excerpt_max_length' => 50));
        $method = $this->getWidgetMethod('truncateExcerptIfNeeded', $instance);
        $output = (array) $method($input);
        $this->assertEqual(3, count($output));
        $this->assertEqual('...', substr($output[0]->Excerpt, -3));
        $this->assertTrue(strlen($output[0]->Excerpt) <= 100);
        $this->assertEqual(35, strlen($output[1]->Excerpt));
        $this->assertEqual(8, strlen($output[2]->Excerpt));
    }
}
