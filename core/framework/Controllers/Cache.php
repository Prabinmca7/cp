<?php

namespace RightNow\Controllers;

/**
 * Generic controller meant to be used to long cache certain data elements. For now, only provides a cached RSS
 * feed of answers.
 */
final class Cache extends Base
{
    public function __construct(){
        parent::__construct();

        //If login is required, we shouldn't be returning answer data via RSS. This wouldn't work anyway since a session isn't created
        //for this endpoint, so we can't even check if the user is logged in or not.
        if(\RightNow\Utils\Config::getConfig(CP_CONTACT_LOGIN_REQUIRED)){
            header($_SERVER["SERVER_PROTOCOL"] . " 403 Forbidden");
            \RightNow\Utils\Framework::writeContentWithLengthAndExit("RSS not available for this site.");
        }
    }

    /**
     * Displays XML content necessary to provides a RSS feed of answers
     * @return void
     */
    public function rss()
    {
        require_once CPCORE . 'Libraries/Rss.php';

        $feedMaker = new \RightNow\Libraries\Rss();
        $this->outputFeed($feedMaker);
    }

    /**
     * Displays XML content necessary to provides a RSS feed of Social Questions
     * @return void
     */
    public function socialRss () {
        require_once CPCORE . 'Libraries/SocialRss.php';
        $feedMaker = new \RightNow\Libraries\SocialRss();
        $this->outputFeed($feedMaker);
    }

    /**
     * Ouputs RSS Feed
     * @param \RightNow\Libraries\Rss|\RightNow\Libraries\SocialRss $feedMaker Instance of RSS class
     */
    private function outputFeed ($feedMaker) {
        $output = $feedMaker->feed();
        // Specify expire time in minutes
        $cacheTime = ((($time = \RightNow\Utils\Config::getConfig(CACHED_CONTENT_EXPIRE_TIME)) && $time > 0) ? $time : 5) * 60;
        header("Cache-Control: must-s-proxy-revalidate, s-maxage={$cacheTime}");
        echo $output;
    }
}