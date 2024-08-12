<?php
namespace RightNow\Widgets;

class TwitterPosts extends \RightNow\Libraries\Widget\Base {
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {
        $this->data['twitter_link'] = '';
        
        switch($this->data['attrs']['fetch_tweets_using']) {
            case 'account':
                if(!$this->data['attrs']['twitter_account']) {
                    echo $this->reportError(sprintf(\RightNow\Utils\Config::getMessage(PCT_S_ATTRIBUTE_IS_REQUIRED_MSG), 'twitter_account'));
                    return false;
                }
                $this->data['twitter_link'] = 'https://twitter.com/' . $this->data['attrs']['twitter_account'];
                break;
            case 'hashtag':
                if(!$this->data['attrs']['twitter_hashtag']) {
                    echo $this->reportError(sprintf(\RightNow\Utils\Config::getMessage(PCT_S_ATTRIBUTE_IS_REQUIRED_MSG), 'twitter_hashtag'));
                    return false;
                }
                $this->data['twitter_link'] = 'https://twitter.com/hashtag/' . $this->data['attrs']['twitter_hashtag'];
                break;
            case 'search_query':
                if(!$this->data['attrs']['twitter_search_query']) {
                    echo $this->reportError(sprintf(\RightNow\Utils\Config::getMessage(PCT_S_ATTRIBUTE_IS_REQUIRED_MSG), 'twitter_search_query'));
                    return false;
                }
                $this->data['twitter_link'] = 'https://twitter.com/' . "search?q=" . urlencode($this->data['attrs']['twitter_search_query']);
                break;
        }
        if(($this->data['attrs']['twitter_hashtag'] || $this->data['attrs']['twitter_search_query']) && !$this->data['attrs']['twitter_widget_id']) {
            echo $this->reportError(\RightNow\Utils\Config::getMessage(TWITTER_WIDGET_ID_IS_REQUIRED_LBL));
            return false;
        }
    }
}
