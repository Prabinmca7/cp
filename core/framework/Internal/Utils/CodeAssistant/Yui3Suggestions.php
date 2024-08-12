<?php

namespace RightNow\Internal\Utils\CodeAssistant;
use RightNow\Utils\Filesystem,
    RightNow\Internal\Utils\CodeAssistant as CodeAssistantUtils,
    RightNow\Utils\Config as ConfigExternal;

final class Yui3Suggestions {
    const YAHOO_REGEX = "@YAHOO\.@";
    public static function getUnits() {
        $units = array();
        foreach(CodeAssistantUtils::getFiles(CodeAssistantUtils::ALL, CodeAssistantUtils::FILETYPE_JS) as $file) {
            if(preg_match(self::YAHOO_REGEX, @file_get_contents($file))) {
                $units[] = \RightNow\Utils\Text::getSubstringAfter($file, 'customer/development/');
            }
        }
        return $units;
    }

    public static function executeUnit($unit, $context) {
        if(!$content = $context->getFile($unit)) {
            throw new \Exception(sprintf("The file '%s' could not be found.", $unit));
        }

        $lines = explode("\n", $content);
        $matches = array();
        $suggestions = array();
        $messages = array(
        array("link" => "http://yuilibrary.com/yui/docs/node/#node-migration", "message" => sprintf(ConfigExternal::getMessage(A_YAHOO_PCT_SS_FUNCTIONALITY_MSG), 'util.Dom', 'Node'), "regex" => "@YAHOO\.util\.dom@i"),
        array("link" => "http://yuilibrary.com/yui/docs/api/classes/Lang.html", "message" => sprintf(ConfigExternal::getMessage(A_YAHOO_PCT_SS_FUNCTIONALITY_LBL), 'lang', 'Y.lang'), "regex" => "@YAHOO\.lang@i"),
        array("link" => "http://yuilibrary.com/yui/docs/overlay/index.html", "message" => sprintf(ConfigExternal::getMessage(A_YAHOO_PCT_SS_FUNCTIONALITY_MSG), 'widget.Overlay', 'Overlay'), "regex" => "@YAHOO\.widget\.Overlay@i"),
        array("link" => "http://yuilibrary.com/yui/docs/event/", "message" => sprintf(ConfigExternal::getMessage(A_YAHOO_PCT_SS_FUNCTIONALITY_LBL), 'util.Event', 'Y.Event'), "regex" => "@YAHOO\.util\.event@i"),
        array("link" => "http://yuilibrary.com/yui/docs/api/classes/EditorBase.html#property_TABKEY", "message" => sprintf(ConfigExternal::getMessage(A_YAHOO_PCT_SS_FUNCTIONALITY_LBL), 'util.KeyListener.KEY.TAB', 'TABKEY'), "regex" => "@YAHOO\.util\.KeyListener\.KEY\.TAB@i"),
        array("link" => "http://yuilibrary.com/yui/docs/api/classes/YUI.html#event_key", "message" => sprintf(ConfigExternal::getMessage(A_YAHOO_PCT_SS_FUNCTIONALITY_LBL), 'util.KeyListener', 'Y.Event'), "regex" => "@YAHOO\.util\.KeyListener@i"),
        array("link" => "http://yuilibrary.com/yui/docs/api/classes/IO.html", "message" => sprintf(ConfigExternal::getMessage(A_YAHOO_PCT_SS_FUNCTIONALITY_LBL), 'util.connect', 'Y.io'), "regex" => "@YAHOO\.util\.connect@i"),
        array("link" => "http://yuilibrary.com/yui/docs/api/classes/UA.html", "message" => sprintf(ConfigExternal::getMessage(A_YAHOO_PCT_SS_FUNCTIONALITY_LBL), 'env.ua', 'Y.UA'), "regex" => "@YAHOO\.env\.ua@i"),
        array("link" => "http://yuilibrary.com/yui/docs/panel/", "message" => sprintf(ConfigExternal::getMessage(A_YAHOO_PCT_SS_FUNCTIONALITY_MSG), 'widget.Panel', 'Panel'), "regex" => "@YAHOO\.widget\.panel@i"),
        array("link" => "http://yuilibrary.com/yui/docs/api/classes/DOM.html#method_region", "message" => sprintf(ConfigExternal::getMessage(A_YAHOO_PCT_SS_FUNCTIONALITY_MSG), 'util.Point', 'DOM'), "regex" => "@YAHOO\.util\.Point@i"),
        array("link" => "http://yuilibrary.com/yui/docs/test/", "message" => sprintf(ConfigExternal::getMessage(A_YAHOO_PCT_SS_FUNCTIONALITY_LBL), 'tool.Test', 'Y.Test'), "regex" => "@YAHOO\.tool\.Test@i"),
        array("link" => "http://yuilibrary.com/yui/docs/api/classes/Node.html#method_simulate", "message" => sprintf(ConfigExternal::getMessage(A_ACTIONS_SIMULATED_SIMULATE_MSG), 'Node'), "regex" => "@YAHOO\.util\.UserAction@i"),
        array("link" => "http://yuilibrary.com/yui/docs/api/classes/Test.Assert.html", "message" => sprintf(ConfigExternal::getMessage(A_YAHOO_PCT_SS_FUNCTIONALITY_LBL), 'util.Assert', 'Test.Assert'), "regex" => "@YAHOO\.util\.Assert@i"),
        array("link" => "http://yuilibrary.com/yui/docs/api/classes/Cookie.html", "message" => sprintf(ConfigExternal::getMessage(A_YAHOO_PCT_SS_FUNCTIONALITY_MSG), 'util.Cookie', 'Cookie'), "regex" => "@YAHOO\.util\.Cookie@i"),
        array("link" => "http://yuilibrary.com/yui/docs/scrollview/", "message" => sprintf(ConfigExternal::getMessage(A_YAHOO_PCT_SS_FUNCTIONALITY_MSG), 'util.Scroll', 'ScrollView'), "regex" => "@YAHOO\.util\.Scroll@i"),
        array("link" => "http://yuilibrary.com/yui/docs/api/classes/Anim.html", "message" => sprintf(ConfigExternal::getMessage(A_YAHOO_PCT_SS_FUNCTIONALITY_LBL), 'util.Anim', 'Anim'), "regex" => "@YAHOO\.util\.Anim@i"),
        array("link" => "http://yuilibrary.com/yui/docs/history/", "message" => sprintf(ConfigExternal::getMessage(A_YAHOO_PCT_SS_FUNCTIONALITY_MSG), 'util.History', 'History'), "regex" => "@YAHOO\.util\.History@i"),
        array("link" => "http://yuilibrary.com/yui/docs/api/classes/DOM.html#method_region", "message" => sprintf(ConfigExternal::getMessage(A_YAHOO_PCT_SS_FUNCTIONALITY_MSG), 'util.Region', 'DOM'), "regex" => "@YAHOO\.util\.Region@i"),
        array("link" => "http://yuilibrary.com/yui/docs/api/modules/datasource.html", "message" => sprintf(ConfigExternal::getMessage(A_YAHOO_PCT_SS_FUNCTIONALITY_MSG), 'util.DataSource', 'DataSource'), "regex" => "@YAHOO\.util\.DataSource@i"),
        array("link" => "http://yuilibrary.com/yui/docs/datatable/", "message" => sprintf(ConfigExternal::getMessage(A_YAHOO_PCT_SS_FUNCTIONALITY_MSG), 'widget.DataTable', 'DataTable'), "regex" => "@YAHOO\.widget\.DataTable@i"),
        array("link" => "http://yuilibrary.com/gallery/show/treeview", "message" => sprintf(ConfigExternal::getMessage(A_YAHOO_PCT_SS_FUNCALITY_MSG), 'widget.TreeView', 'gallery-treeview'), "regex" => "@YAHOO\.widget\.TreeView@i"),
        array("link" => "http://yuilibrary.com/yui/docs/node-menunav/", "message" => sprintf(ConfigExternal::getMessage(A_YAHOO_PCT_SS_FUNCTIONALITY_MSG), 'widget.MenuNode', 'node-menunav'), "regex" => "@YAHOO\.widget\.MenuNode@i"),
        array("link" => "http://yuilibrary.com/yui/docs/api/classes/Lang.html", "message" => sprintf(ConfigExternal::getMessage(A_YAHOO_PCT_SS_FUNCTIONALITY_MSG), 'util.Lang', 'Lang'), "regex" => "@YAHOO\.util\.Lang@i"),
        array("link" => "http://yuilibrary.com/yui/docs/api/classes/Anim.html", "message" => sprintf(ConfigExternal::getMessage(A_YAHOO_PCT_SS_FUNCTIONALITY_MSG), 'util.ColorAnim', 'Anim'), "regex" => "@YAHOO\.util\.ColorAnim@i"),
        );
        for ($i = 0; $i < count($lines); $i++) {
            if (preg_match(self::YAHOO_REGEX, $lines[$i])) {
                $matches[] = $i;
                foreach ($messages as $messageObject) {
                    if (preg_match($messageObject['regex'], $lines[$i])) {
                        $suggestions[$i] = array('link' => $messageObject['link'], 'message' => $messageObject['message']);
                        break;
                    }
                }
            }
        }

        if(!count($matches)) {
            throw new \Exception("No suggestions found.");
        }

        $context->addSnippets($unit, $lines, $matches, $suggestions);
    }
}