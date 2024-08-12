<?php

use \RightNow\Libraries\AbuseDetection,
    \RightNow\Controllers\UnitTest,
    \RightNow\Utils\Text;

class AbuseDetectionTest extends CPTestCase
{
    public $testingClass = '\RightNow\Libraries\AbuseDetection';

    function tearDown()
    {
        $this->clearIsAbuse();
    }

    function testCheck()
    {
        // if the site is in an abusive state, we should get back the challenge provider
        $output = $this->makeRequest('/ci/unitTest/wgetRecipient/invokeTestMethod/' . urlencode(__FILE__) . '/' . __CLASS__ . '/check');
        $this->assertBeginsWith($output, "==CHALLENGE REQUIRED==");
    }

    function testIsAbuse()
    {
        $this->setIsAbuse();
        $this->assertTrue(AbuseDetection::isForceAbuseCookieSet());
        $this->assertTrue(AbuseDetection::isAbuse());

        $this->clearIsAbuse();
        $this->assertFalse(AbuseDetection::isForceAbuseCookieSet());
        $this->assertFalse(AbuseDetection::isAbuse());
    }

    function testGetChallengeProvider()
    {
        // there is a blank line in the response from AbuseDetection::getChallengeProvider()
        $value = <<<EOF

var defaultChallengeOptions={scriptUrl:"//www.google.com/recaptcha/api.js?onload=onApiLoadCallback&render=explicit&hl=en-US",publicKey:"6LcHf0wUAAAAAFxRHKequHkHUGyUWA5dy24ZaB5s",lang:"en-US",custom_translations:{visual_challenge:"Get a visual challenge",audio_challenge:"Get an audio challenge",refresh_btn:"Get a new challenge",instructions_visual:"Type the two words:",instructions_audio:"Type what you hear:",help_btn:"Help",play_again:"Play sound again",cant_hear_this:"Download sound as MP3",incorrect_try_again:"Incorrect. Try again."},cant_see_image:"If you can\'t see the image use the audio option."};(function(){var _loadScript=function(url,context,callback){var script=document.createElement("script");script.type="text/javascript";onApiLoadCallback=function(){callback.call(context);};script.src=url;document.body.appendChild(script);},_augmentObject=function(target,source){for(var i in source){if(source.hasOwnProperty(i)){target[i]=source[i];}}},_captchaWidgetID;return{create:function(targetDivID,customOptions,callback,dialog){_loadScript(defaultChallengeOptions.scriptUrl,this,function(){var customTranslations=false;if(defaultChallengeOptions&&customOptions){customTranslations=customOptions.custom_translations;if(defaultChallengeOptions.custom_translations&&customTranslations){_augmentObject(defaultChallengeOptions.custom_translations,customTranslations);customOptions.custom_translations=null;}
_augmentObject(defaultChallengeOptions,customOptions);}
defaultChallengeOptions.callback=function(){var instructionsSpan=document.getElementById("recaptcha_instructions_image");if(instructionsSpan)
instructionsSpan.innerHTML+='<span style="position:absolute; height:1px; left:-10000px; overflow:hidden; top:auto; width:1px;">'+defaultChallengeOptions.cant_see_image+'</span>';var recaptchaImage=document.getElementById('recaptcha_image');if(recaptchaImage&&(recaptchaImage=recaptchaImage.getElementsByTagName("IMG"))[0]){recaptchaImage[0].setAttribute('alt','');}
if(callback)
callback.apply(this,arguments);};var captchaResponseCreated=function(response){if(dialog){dialog.enableButtons();Y.Lang.later(400,dialog.getButtons().item(0),'focus');}};var captchaResponseExpired=function(){if(dialog){dialog.disableButtons();}};_captchaWidgetID=grecaptcha.render(targetDivID,{'sitekey':defaultChallengeOptions.publicKey,'callback':captchaResponseCreated,'expired-callback':captchaResponseExpired});if(callback){callback.apply(this,arguments);}
if(customOptions&&customTranslations){customOptions.custom_translations=customTranslations;}});},getInputs:function(targetDivID){var parentDiv=document.getElementById(targetDivID),inputs={};if(parentDiv){var response=grecaptcha.getResponse(_captchaWidgetID),opaque='reCaptcha2';if(response){inputs.abuse_challenge_response=response.replace(/^\s+|\s+$/g,"");}
if(opaque){inputs.abuse_challenge_opaque=opaque;}}
return inputs;},focus:function(targetDivID){try{document.getElementById(targetDivID).focus();}
catch(ex){}},destroy:function(){try{grecaptcha.reset(_captchaWidgetID);}
catch(ex){}}};})();
EOF;

        $this->assertIdentical($value, AbuseDetection::getChallengeProvider());
    }

    function check()
    {
        // when isAbuse = true and AbuseDetection::check() is called, it will
        // print minified JavaScript then exit, so it needs to be called via makeRequest()
        list ($class, $method, $isAbuse) = $this->reflect('method:check', 'isAbuse');
        $instance = $class->newInstance();
        $isAbuse->setValue($instance, true);
        $this->setIsAbuse();
        $method->invoke($instance);
    }
}
