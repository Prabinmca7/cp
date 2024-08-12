<?php 

namespace RightNow\Widgets;

class CommunityPosts extends \RightNow\Libraries\Widget\Base
{
    function __construct($attrs)
    {
        parent::__construct($attrs);
    }

    function getData()
    {
        if(!\RightNow\Utils\Config::getConfig(COMMUNITY_ENABLED))
        {
            echo $this->reportError(\RightNow\Utils\Config::getMessage(SOCIAL_ENABLED_CFG_SET_ENABLED_MSG));
            return false;
        }
        if(\RightNow\Utils\Config::getConfig(COMMUNITY_BASE_URL) === '')
        {
            echo $this->reportError(\RightNow\Utils\Config::getMessage(SOCIAL_BASE_URL_CFG_SET_SET_MSG));
            return false;
        }
        if($filterOnUserValue = trim($this->data['attrs']['filter_on_user']))
        {
            if($filterOnUserValue === '{current user}')
            {
                if(!isLoggedIn())
                    return false;
                $userToFilter = array('userID' => $this->CI->session->getProfileData('contactID'));
            }
            else
            {
                $urlParam = \RightNow\Utils\Url::getParameter('people');
                $userToFilter = array('userHash' => ($urlParam) ? \RightNow\Utils\Text::getSubStringBefore($urlParam, '?opentoken=', $urlParam) : $filterOnUserValue); //there may be an SSO querystring param in the URL
            }
        }
        if($this->data['attrs']['sort_order'])
        {
            switch($this->data['attrs']['sort_order'])
            {
                case 'alphabetical':
                    $sortFilter = 'az';
                    break;
                case 'highestRating':
                    $sortFilter = 'rating';
                    break;
                case 'mostViews':
                    $sortFilter = 'views';
                    break;
                case 'mostComments':
                    $sortFilter = 'comments';
                    break;
                case 'mostRecent':
                    $sortFilter = 'new';
                    break;
            }
        }
        $this->data['baseUrl'] = $this->data['attrs']['author_link_base_url'] ?: \RightNow\Utils\Config::getConfig(COMMUNITY_BASE_URL);
        $results = $this->CI->model('Social')->performSearch('', $this->data['attrs']['limit'], $sortFilter, $this->data['attrs']['resource_id'], $userToFilter)->result;
        if($results && count($results->searchResults) > 0)
        {
            $results = $results->searchResults;
            $this->data['results'] = $this->CI->model('Social')->formatSearchResults($results, $this->data['attrs']['truncate_size'], false, null, $this->data['attrs']['post_link_base_url'])->result;
            $this->data['fullResultsUrl'] = $this->data['baseUrl'] . "/posts?view=summary&amp;sort=$sortFilter";
            if($this->data['attrs']['resource_id'])
                $this->data['fullResultsUrl'] .= '&amp;hiveHash=' . $this->data['attrs']['resource_id'];
            $this->data['fullResultsUrl'] .= \RightNow\Utils\Url::communitySsoToken('&amp;');
        }
        else
        {
            return false;
        }
    }
}
