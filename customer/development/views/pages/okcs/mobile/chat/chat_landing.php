<!DOCTYPE html>
<html lang="#rn:language_code#">
<rn:meta clickstream="chat_landing" javascript_module="mobile" include_chat="true" noindex="true"/>
<head>
    <meta name="viewport" content="width=device-width; initial-scale=1.0; minimum-scale=1.0; maximum-scale=1.0; user-scalable=no;"/>
    <meta charset="utf-8"/>
    <title>#rn:msg:LIVE_ASSISTANCE_LBL#</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
    <!--[if lt IE 9]><script type="text/javascript" src="/euf/core/static/html5.js"></script><![endif]-->
    <rn:theme path="/euf/assets/themes/mobile" css="site.css,
        {YUI}/widget-stack/assets/skins/sam/widget-stack.css,
        {YUI}/widget-modality/assets/skins/sam/widget-modality.css,
        {YUI}/overlay/assets/overlay-core.css,
        {YUI}/panel/assets/skins/sam/panel.css" />
    <rn:head_content/>
    <rn:widget path="utils/ClickjackPrevention"/>
    <rn:widget path="utils/AdvancedSecurityHeaders"/>
    <link rel="icon" href="/euf/assets/images/favicon.png" type="image/png"/>
    <link rel="canonical" href="<?= \RightNow\Utils\Url::getShortEufAppUrl('sameAsCurrentPage', 'chat/chat_landing') ?>"/>
</head>
<body class="yui-skin-sam yui3-skin-sam">
    <h1 class="rn_ScreenReaderOnly">#rn:msg:LIVE_ASSISTANCE_LBL#</h1>
    <rn:widget path="utils/CapabilityDetector"/>
    <div id="rn_ChatContainer">
        <div id="rn_PageContent" class="rn_Live">
            <div class="rn_Padding">
                <div id="rn_ChatDialogContainer">
                    <div id="rn_ChatDialogHeaderContainer">
                        <div id="rn_ChatDialogTitle" class="rn_FloatLeft">#rn:msg:CHAT_LBL#</div>
                        <div id="rn_ChatDialogHeaderButtonContainer">
                            <rn:widget path="chat/ChatDisconnectButton" close_icon_path="" disconnect_icon_path="" mobile_mode="true"/>
                        </div>
                    </div>
                    <rn:widget path="chat/ChatServerConnect"/>
                    <rn:widget path="chat/ChatEngagementStatus"/>
                    <rn:widget path="chat/ChatQueueWaitTime"/>
                    <div id="rn_VirtualAssistantContainer">
                        <rn:widget path="chat/VirtualAssistantAvatar"/>
                    </div>
                    <rn:widget path="chat/ChatAgentStatus"/>
                    <div id="rn_TranscriptContainer">
                        <div id="rn_ChatTranscript">
                            <rn:widget path="chat/ChatTranscript" mobile_mode="true"/>
                        </div>
                    </div>
                    <div id="rn_PreChatButtonContainer">
                        <rn:widget path="chat/ChatCancelButton"/>
                        <rn:widget path="chat/ChatRequestEmailResponseButton"/>
                    </div>
                    <rn:widget path="chat/ChatPostMessage" label_send_instructions="#rn:msg:TYPE_YOUR_MESSAGE_AND_SEND_LBL#" mobile_mode="true"/>
                    <div id="rn_InChatButtonContainer">
                        <rn:widget path="chat/ChatSendButton"/>
                    </div>
                </div>
            </div>
        </div>
        <div id="rn_ChatFooter">
            <div class="rn_FloatRight">
                <rn:widget path="utils/OracleLogo"/>
            </div>
        </div>
    </div>
</body>
</html>
