<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"> 
<html>
<head>
<title><?=\RightNow\Utils\Config::getMessage(POLLING_SURVEY_PREVIEW_LBL);?></title>
<link rel="stylesheet" type="text/css" href="<?=\RightNow\Utils\Url::getYUICodePath('panel/assets/skins/sam/panel.css')?>" />
<rn:widget path="utils/ClickjackPrevention"/>
<rn:widget path="utils/AdvancedSecurityHeaders"/>
</head>
<body class="yui-skin-sam yui3-skin-sam">
<br />
<!-- survey_id is a fake number, the controller will grab the real survey_id from $_REQUEST -->
<rn:widget path="surveys/Polling" admin_console="true" survey_id="1234567"/>
</body>
</html>
