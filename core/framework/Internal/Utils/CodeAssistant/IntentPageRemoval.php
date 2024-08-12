<?php

namespace RightNow\Internal\Utils\CodeAssistant;
use RightNow\Internal\Utils\CodeAssistant as CodeAssistantUtils;

final class IntentPageRemoval {
    public static function getUnits() {
        $units = array();
        foreach(CodeAssistantUtils::getFiles(CodeAssistantUtils::VIEWS, CodeAssistantUtils::FILETYPE_PHP) as $file) {
            if(\RightNow\Utils\Text::stringContains($file, 'intent.php') && preg_match("@knowledgebase/IntentGuideDisplay@", @file_get_contents($file))) {
                $units[] = \RightNow\Utils\Text::getSubstringAfter($file, CUSTOMER_FILES);
            }
        }
        return $units;
    }

    public static function executeUnit($unit, $context) {
        if(!$context->deleteFile($unit)) {
            throw new \Exception(sprintf("The file '%s' could not be deleted. Check the permissions and try again.", $unit));
        }
    }
}