<?php

namespace RightNow\Controllers;

use RightNow\Utils\Config,
    RightNow\Internal\Utils\Version;

/**
 * Displays information about the CP site
 */
final class About extends Base
{
    /**
     * Shows current version information about the site
     */
    public function index()
    {
        $aboutHeading = Config::getMessage(ABOUT_HDG);
        $frameworkVersions = Version::getVersionsInEnvironments('framework');
        $developmentVersion = $frameworkVersions['Development'];
        $stagingVersion = $frameworkVersions['Staging'];
        $moduleName = MOD_NAME . ' ' . $frameworkVersions['Production'];
        $softwareVersionLabel = Config::getMessage(SOFTWARE_VERSION_LBL) . ': ' . Config::getMessage(RIGHTNOW_VERSION_LBL);
        $buildNumber = Config::getMessage(BUILD_LBL) . ' ' . MOD_CX_BUILD_NUM . ',  CP ' . MOD_BUILD_NUM . ')';
        if(MOD_BUILD_SP > 0)
            $buildNumber .= ' SP' . MOD_BUILD_SP;
        $buildDateTime = MOD_BUILD_DATE . ' ' . MOD_BUILD_TIME;
        $date = getdate();
        $copyrightMessage = sprintf(Config::getMessage(COPYR_COPY_1998_PCT_S_WIN_C_HK), $date['year']);
        // @codingStandardsIgnoreStart
        $oracleUrl = Config::getConfig(rightnow_url);
        // @codingStandardsIgnoreEnd

        echo <<<HTML
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset="utf-8"/>
                <title>$aboutHeading</title>
            </head>
            <style>
                h1 {margin: 0;}
                h3 {margin: 0 0 0 1em;}
            </style>
            <body>
                  <h1>$moduleName</h1>
                  <h3>(d-$developmentVersion / s-$stagingVersion)</h3>
                  <h2>$softwareVersionLabel ($buildNumber</h2>
                  <h2>$buildDateTime</h2>
                  <br/>
                  <p/><hr/>
                  <h2>$copyrightMessage</h2>
            </body>
            </html>
HTML;
    }

    /**
     * Nothing to see here.
     * @internal
     */
    public function _ensureContactIsAllowed() {
    }

    /**
     * Nothing to see here.
     * @internal
     */
    public function webOneDotOh()
    {
        ob_start();
        $this->index();
        $content = ob_get_clean();
        $content = str_replace("<body>", "<body style='background: url(/ci/about/uc);background-repeat: repeat-x;'><br/><br/>", $content);
        $content = str_replace("<h1>", "<h1><blink>", $content);
        $content = str_replace("</h1>", "</blink></h1>", $content);
        $replacer = function($matches){
            return "<h2><marquee " . ((rand(0, 1) == 1) ? "direction=\"right\" behavior=\"alternate\" scrollamount=" . rand(5, 25) .">" : "direction=\"down\">");
        };
        $content = preg_replace_callback('@<h2>@', $replacer, $content);
        $content = str_replace("</h2>", "</marquee></h2>", $content);
        echo $content;
    }

    /**
     * Nothing to see here.
     * @internal
     */
    public function uc()
    {
        $a = array_merge(array(71, 73, 70, 56, 57, 97, 40, 0, 40, 0, 132, 0, 0, 0, 255, 255, 251, 243, 5, 255, 100, 3, 221, 9, 7, 242, 8, 132, 71, 0, 165, 0, 0, 211, 2, 171, 234, 31, 183, 20, 0, 100, 18, 86, 44, 5, 144, 113, 58, 191, 191, 191, 128, 128, 128, 64, 64, 64, 0, 0, 0), array_fill(0, 48, 0xFF), array(33, 255, 11, 78, 69, 84, 83, 67, 65, 80, 69, 50, 46, 48, 3, 1, 0, 0, 0, 33, 249, 4, 4, 13, 0, 255, 0, 44, 0, 0, 0, 0, 40, 0, 40, 0, 0, 5, 202, 32, 35, 142, 100, 73, 62, 143, 169, 174, 172, 138, 162, 109, 44, 139, 104, 16, 192, 115, 94, 214, 246, 157, 234, 58, 94, 207, 7, 156, 9, 135, 196, 34, 235, 136, 76, 42, 119, 143, 102, 19, 247, 164, 69, 135, 175, 107, 143, 170, 100, 102, 189, 191, 34, 211, 151, 157, 134, 115, 99, 242, 75, 202, 141, 165, 213, 239, 246, 170, 246, 93, 151, 165, 54, 57, 52, 143, 93, 227, 205, 75, 90, 130, 95, 127, 128, 38, 71, 81, 117, 55, 133, 134, 35, 94, 112, 124, 125, 113, 97, 99, 138, 145, 91, 119, 128, 105, 117, 156, 157, 120, 153, 72, 132, 151, 121, 160, 161, 41, 155, 162, 102, 111, 78, 149, 150, 140, 141, 86, 166, 137, 126, 175, 152, 46, 90, 100, 164, 184, 175, 122, 177, 186, 191, 125, 159, 103, 183, 192, 180, 106, 176, 129, 185, 62, 146, 198, 189, 196, 60, 126, 158, 78, 104, 137, 197, 156, 211, 65, 187, 140, 206, 50, 171, 166, 85, 142, 218, 200, 85, 222, 220, 98, 226, 216, 224, 225, 227, 234, 235, 182, 237, 201, 233, 240, 80, 230, 243, 12, 245, 42, 33, 0, 33, 249, 4, 1, 13, 0, 14, 0, 44, 0, 0, 0, 0, 0, 0, 0, 0, 0, 5, 205, 32, 35, 142, 100, 73, 62, 143, 169, 174, 172, 138, 162, 109, 44, 139, 104, 16, 192, 115, 94, 214, 246, 157, 234, 58, 94, 207, 7, 156, 9, 135, 196, 34, 235, 136, 76, 42, 119, 143, 102, 19, 247, 164, 69, 165, 211, 223, 147, 137, 237, 81, 129, 220, 174, 87, 155, 11, 143, 185, 223, 24, 239, 138, 124, 189, 178, 70, 246, 123, 232, 14, 167, 77, 194, 181, 222, 141, 189, 143, 142, 108, 62, 117, 115, 82, 126, 76, 81, 131, 136, 132, 125, 100, 12, 135, 138, 131, 98, 112, 86, 89, 117, 55, 151, 146, 109, 41, 102, 150, 54, 129, 153, 103, 159, 161, 107, 160, 112, 118, 124, 139, 165, 84, 104, 150, 144, 130, 162, 99, 80, 154, 174, 163, 167, 46, 129, 115, 169, 145, 116, 141, 39, 114, 123, 188, 124, 188, 106, 87, 185, 128, 194, 177, 50, 53, 198, 193, 139, 126, 43, 203, 197, 123, 157, 207, 75, 136, 158, 211, 168, 189, 113, 165, 216, 85, 142, 176, 133, 219, 96, 225, 195, 223, 127, 229, 78, 231, 148, 226, 235, 120, 162, 213, 231, 199, 238, 214, 222, 244, 214, 241, 247, 224, 79, 33, 0, 59));
        header("Content-Type: image/gif");
        header("Content-Length: " . count($a));
        exit(implode('', array_map('chr', $a)));
    }
}
