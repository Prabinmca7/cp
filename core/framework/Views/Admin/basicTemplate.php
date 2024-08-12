<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML Basic 1.1//EN" "http://www.w3.org/TR/xhtml-basic/xhtml-basic11.dtd">
<html lang="<?=\RightNow\Utils\Text::getLanguageCode();?>">
<head>
    <meta http-equiv="content-type" content="text/html; charset=UTF-8"/>
    <title><?=$pageTitle;?></title>
    <style type="text/css">
          .rn_Header{
              font-weight:bold;
              font-size:18pt;
              text-align:center;
          }
          .rn_LinksBlock a {
              display:block;
              margin-bottom:10px;
          }
          .rn_CenterText{
              text-align:center;
          }
      </style>
    </head>
    <body>
        <div class="rn_Header"><?= \RightNow\Utils\Config::getMessage(CUSTOMER_PORTAL_ADMINSTRATION_LBL) ?></div>
        <div class="rn_CenterText"><? printf(\RightNow\Utils\Config::getMessage(CURRENT_SITE_MODE_PCT_S_LBL), "{$siteMode['mode']} - {$siteMode['agent']}"); ?></div>
        <hr/>        
         <div>
            <?=$content;?>
        </div><br/>
    </body>
</html> 