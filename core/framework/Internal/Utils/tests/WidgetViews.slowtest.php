<?php

use RightNow\Internal\Utils\FileSystem;

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class WidgetViewsSlowTest extends CPTestCase {
    public $testingClass = 'RightNow\Internal\Utils\WidgetViews';

    private $noBlocksExceptions = array(
        'standard/knowledgebase/GuidedAssistant/imageResponse.ejs',
        'standard/knowledgebase/GuidedAssistant/buttonResponse.ejs',
        'standard/knowledgebase/GuidedAssistant/linkResponse.ejs',
        'standard/knowledgebase/GuidedAssistant/menuResponse.ejs',
        'standard/knowledgebase/GuidedAssistant/textResponse.ejs',
        'standard/knowledgebase/GuidedAssistant/radioResponse.ejs',
        'standard/utils/ClickjackPrevention/view.php',
        'standard/surveys/SurveyLink/view.php',
        'standard/login/BasicLogoutLink/view.php',
        'standard/chat/ChatLaunchFormOpen/view.php',
        'standard/search/BrowserSearchPlugin/view.php',
        'standard/search/CombinedSearchResults/socialView.ejs',
        'standard/reports/BasicMultiline/view.php',
    );
    private $duplicateIDExceptions = array(
        'standard/knowledgebase/SearchSuggestions/view.php',
        'standard/social/CommunityPostDisplay/view.php',
        'standard/social/AnswerComments/view.php',
        'standard/input/SelectionInput/view.php',
        'standard/input/BasicTextInput/view.php',
        'standard/input/BasicProductCategoryInput/view.php',
        'standard/input/TextInput/view.php',
        'standard/input/BasicSelectionInput/view.php',
        'standard/input/SmartAssistantDialog/displayResults.ejs',
        'standard/surveys/Polling/view.php',
        'standard/feedback/BasicAnswerFeedback/view.php',
        'standard/login/ResetPassword/view.php',
        'standard/login/BasicResetPassword/view.php',
        'standard/chat/ChatTranscript/participantAddedResponse.ejs',
        'standard/chat/ChatTranscript/chatPostResponse.ejs',
        'standard/okcs/OkcsSmartAssistant/displayOkcsResults.ejs',
        'standard/discussion/QuestionComments/view.php',
        'standard/discussion/QuestionComments/CommentList.html.php',
    );

    function testBlocksInWidgetViews () {
        $views = $this->getWidgetViews();
        $this->assertTrue(count($views) > 0);

        foreach ($views as $relativePath) {
            $view = file_get_contents(CORE_WIDGET_FILES . "/$relativePath");
            $this->assertViewHasValidBlocks($view, $relativePath);
        }
    }

    function assertViewHasValidBlocks ($view, $path) {
        preg_match_all("/rn:block id=('|\")(.*)('|\")/", $view, $matches);

        if (!$matches && !in_array($path, $this->noBlocksExceptions)) {
            return $this->fail("$path doesn't have any rn:blocks");
        }
        $ids = $matches[2];

        if (array_unique($ids) !== $ids && !in_array($path, $this->duplicateIDExceptions)) {
            $this->fail("$path has duplicated rn:block ids");
        }

        $this->pass("$path checks out");
    }

    function getWidgetViews () {
        return array_keys(array_filter(FileSystem::getDirectoryTree(CORE_WIDGET_FILES, array(
            'regex' => '/(view.php|.ejs|.html.php)$/'))));
    }
}
