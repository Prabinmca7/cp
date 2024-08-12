<?php
namespace RightNow\Widgets;

use \RightNow\Utils\Config;

class ContactUs extends \RightNow\Libraries\Widget\Base {
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {
        $this->data['channelData'] = $this->constructChannelData(
            $this->getChannelList($this->data['attrs']['channels'])
        );
    }

    /**
     * Returns a list of valid channels to display.
     * @param array $allChannels The list of channels from the 'channels' attribute.
     * @return array The list of channels with any conditional channels pruned, such as 'chat' and 'feedback'.
     */
    protected function getChannelList(array $allChannels) {
        $channels = array();
        $attrs = $this->data['attrs'];
        $conditionals = array(
            'chat' => function() use ($attrs) {
                return $attrs['chat_link_always_displayed'] || Config::getConfig(MOD_CHAT_ENABLED) || Config::getConfig(MOD_ENGAGEMENT_CHANNELS_ENABLED);
            },
            'feedback' => function() use ($attrs) {
                return $attrs['feedback_link_always_displayed'] || !Config::getConfig(CP_CONTACT_LOGIN_REQUIRED) || \RightNow\Utils\Framework::isLoggedIn();
            },
        );

        foreach($allChannels as $channel) {
            $condition = isset($conditionals[$channel]) ? $conditionals[$channel] : null;
            if (!$condition || $condition()) {
                $channels[] = $channel;
            }
        }

        return $channels;
    }

    /**
     * Gathers label, url, view and description data for each specified channel.
     * @param array $channels A list of channels.
     * @return array An associative array of channel data whose key is the channel.
     */
    protected function constructChannelData(array $channels) {
        $channelData = array();
        foreach ($channels as $channel) {
            $channelData[$channel] = array(
                'label' => $this->data['attrs']["{$channel}_label"],
                'url' => $this->addUrlParametersToSelectChannels($channel, $this->data['attrs']["{$channel}_link"]),
                'view' => $this->data['attrs']["{$channel}_view"],
                'description' => $this->data['attrs']["{$channel}_description_label"],
            );
        }
        return $channelData;
    }

    /**
     * Adds product and category url parameters to the $channel $url for 'question' and 'community' channels.
     * @param string $channel The channel name
     * @param string $url The channel url
     * @param string $parameters A comma-separated string of url parameters to add to $url.
     * @return string The $url with product and/or category url parameters added as appropriate.
     */
    protected function addUrlParametersToSelectChannels($channel, $url, $parameters = 'p,c') {
        static $toAdd;
        if ($url && in_array($channel, array('question', 'community'))) {
            if (!isset($toAdd)) {
                $toAdd = \RightNow\Utils\Url::getParametersFromList($parameters);
            }
            if ($toAdd) {
                return "$url{$toAdd}";
            }
        }
        return $url;
    }
}
