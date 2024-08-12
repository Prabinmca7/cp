<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8" />
    <meta http-equiv="Content-Style-Type" content="text/css" />
    <meta http-equiv="Content-Script-Type" content="text/javascript" />
    <meta http-equiv="Pragma" content="no-cache"/>
    <rn:widget path="utils/ClickjackPrevention"/>
    <rn:widget path="utils/AdvancedSecurityHeaders"/>
    <title><rn:page_title/></title>
    <style type='text/css'>
        /* embedding the CSS rules directly into the template rather than creating a theme because, without an explicit theme, widget CSS
            is automatically loaded from the standard theme */
        input,textarea,select{font-family:inherit;}
        html {
            font-family:Helvetica, Arial, sans-serif;
        }
        html[lang="ja-JP"],
        html[lang="ja-JP"] input, html[lang="ja-JP"] textarea, html[lang="ja-JP"] select {
            font-family:"Hiragino Kaku Gothic Pro", "ヒラギノ角ゴ Pro W3", Meiryo, "メイリオ", "MS PGothic", "MS Pゴシック", Helvetica, Arial, sans-serif;
        }
        body {
            font-size:.75em;
            line-height:1.250em;
            text-align:left;
        }
        h2, h3, h4, h5, h6 {
            font-family:Arial, sans-serif;
        }
    </style>
</head>
<body class="yui-skin-sam">
    <noscript><h1>#rn:msg:SCRIPTING_ENABLED_SITE_MSG#</h1></noscript>
    <rn:page_content/>
</body>
</html>
