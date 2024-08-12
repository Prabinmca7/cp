<?php
namespace RightNow\Utils;
use RightNow\Utils\Text,
    RightNow\Utils\FileSystem;

/**
 * DO NOT SHIP THIS
 */
class VersionBump {
    private $documentRoot;
    private $cxRelease;
    private $currentFramework;
    private $previousFramework;

    function __construct() {
        $this->documentRoot = DOCROOT . "/cp";
        $cpHistoryPath = $this->documentRoot."/core/cpHistory";
        $cpHistoryYml = @yaml_parse_file($cpHistoryPath);
        $frameworkVersions = array_keys($cpHistoryYml["frameworkVersions"]);
        $this->cxRelease = end($frameworkVersions);
        $previousCXRelease = $frameworkVersions[count($frameworkVersions) - 2];
        $this->currentFramework = $cpHistoryYml["frameworkVersions"][$this->cxRelease];
        $this->previousFramework = $cpHistoryYml["frameworkVersions"][$previousCXRelease];
    }

    /**
     * Compares two frameworks and returns 1 if the current framework is newer than the previous framework by a minor or major bump
     * @param string $currentFramework The current release's framework version
     * @param string $previousFramework The previous release's framework version
     * @return 1 if there is  framework bump and 0 if there isn't
     */
    function compareFramework($currentFramework, $previousFramework) {
        $currentFrameworkArray = explode('.', $currentFramework);
        $previousFrameworkArray = explode('.', $previousFramework);
        if($currentFrameworkArray[0] > $previousFrameworkArray[0] || $currentFrameworkArray[1] > $previousFrameworkArray[1])
            return 1;
        // nano bump isn't relevant
        return 0;
    }

    /**
     * This function verifies if the widget being bumped has already been bumped this release
     * @param string $widgetURL The widget that is to be bumped
     * @param string $version The current version of the widget
     * @param string $bumpType The type of bump intended for the widget, eg: "major"
     * @return 1 if the widget is eligible to be bumped and 0 if it isn't
     */
    function bumpWorthy($widgetURL, $version, $bumpType) {
        $changelogPath = $this->documentRoot . "/core/widgets/" . $widgetURL . "/changelog.yml";
        $changelogYML = @yaml_parse_file($changelogPath);
        $changelogVersionArray = array_keys($changelogYML);
        $changelogVersion = $changelogVersionArray[0];
        $detectedBump = $this->findBumpType($version, $changelogVersion);
        $this->alreadyBumped = 0;
        if(floatval($changelogYML[$version]["release"]) === floatval($this->cxRelease) || $detectedBump !== "none") {
            $this->alreadyBumped = 1;
        }
        //has there been a bump in this release?
        if(floatval($changelogYML[$version]["release"]) < floatval($this->cxRelease) && $detectedBump === "none") {
            //if no bump we can proceeed to major, minor or nano bump this widget
            return 1;
        }
        //if there has been a bump, it's at least nano, so we can't nano bump
        if($bumpType === "nano") {
            return 0;
        }
        else if($bumpType === "minor") {
            if($detectedBump === "major" || $detectedBump === "minor") {
                return 0;
            }
            if($changelogYML[$version]) {
                foreach($changelogYML[$version]["entries"] as $entry) {
                    if($entry["level"] === "minor" || $entry["level"] === "major") {
                        return 0;
                    }
                }
            }
        }
        else if($bumpType === "major") {
            if($detectedBump === "major") {
                return 0;
            }
            if($changelogYML[$version]) {
                foreach($changelogYML[$version]["entries"] as $entry) {
                    if($entry["level"] === "major"){
                        return 0;
                    }
                }
            }
        }
        return 1;
        
    }

    /**
     * This function compares the version of the widget with the version indicated in the changelog to identify the appropriate type of bump if any.
     * @param string $version The version of the widget in the info.yml.
     * @param string $changelogVersion The version of the widget as indicated in the changelog.
     * @return string Type of bump detected
     */
    function findBumpType($version, $changelogVersion) {
        $versionArray = explode('.', $version);
        $changelogVersionArray = explode('.', $changelogVersion);
        if($versionArray[0] > $changelogVersionArray[0])
            return "major";
        else if($versionArray[1] > $changelogVersionArray[1])
            return "minor";
        else if($versionArray[2] > $changelogVersionArray[2])
            return "nano";
        else
            return "none";
    }

    /**
     * Function to nano bump a widget, the widgetURL is received from the form input
     * @param string $widgetURL The path of the widget that is to be nano bumped
     * @return void
     */
    public function nanoBumpWidget($widgetURL) {
        $widgetPath = $this->documentRoot."/core/widgets/".$widgetURL."/info.yml";
        $widgetYml = @yaml_parse_file($widgetPath);
        $version = $widgetYml[version];
        $newNano = intval(substr($version, strrpos($version, '.') + 1)) + 1;
        $newVersion = substr($version, 0, strrpos($version, '.')).".".$newNano;

        if(!$this->bumpWorthy($widgetURL, $version, "nano")) {
            echo $widgetURL, " has already been bumped in ", $this->cxRelease, "<br>";
            echo '<hr style="border-top: dotted 3px;" />';
            return;
        }

        $fileContent = file_get_contents($widgetPath);
        $newFileContent = preg_replace_callback('@(version:.*)\d{1,2}"@', function($matches) use($newNano) {
            return $matches[1] . $newNano . '"';
        }, $fileContent, 1);

        file_put_contents($widgetPath, $newFileContent);
        echo "<div style='color:red'>".$widgetURL." has been nano bumped <br> </div>";
        echo '<hr style="border-top: dotted 3px;" />';
        
    }

    /**
     * Function to major or minor bump a widget, can be called via submitting the form with the widgetURL as input or recursively.
     * @param string $widgetURL The widget to be bumped
     * @param string $type The type of bump
     * @return void
     */
    public function majorMinorBumpWidget($widgetURL, $type) {
        $widgetPath = $this->documentRoot."/core/widgets/".$widgetURL."/info.yml";
        $widgetYml = @yaml_parse_file($widgetPath);
        $version = $widgetYml[version];
        $frameworkBumped = false;
        $cpHistoryPath = $this->documentRoot."/core/cpHistory";
        $cpHistoryYml = @yaml_parse_file($cpHistoryPath);
        $extendedWidgets = array();
        $containedWidgets = array();

        // verify whether this widget needs to be bumped since it may have already been bumped this release
        if(!$this->bumpWorthy($widgetURL, $version, $type)) {
            echo $widgetURL, " has already been bumped in ", $this->cxRelease, "<br>";
            return;
        }

        //if a widget is major or minor bumped in the same release as a minor framework bump, it will not support older frameworks
        if($this->compareFramework($this->currentFramework, $this->previousFramework)) {
            $frameworkBumped = true;
            $fileContent = file_get_contents($widgetPath);
            $newFileContent = preg_replace_callback("@((requires:\n\s*framework:\s*\[\").*\"\])@", function($matches) {
                return $matches[2] . substr($this->currentFramework, 0, strrpos($this->currentFramework, '.')). '"]';
            }, $fileContent, 1);
            file_put_contents($widgetPath, $newFileContent);
            echo "<br>".$widgetPath." has been updated to work only with the latest framework"."<br>";
        }

        //we need to erase entries in cphistory that were made for this widget if it was already bumped in this release
        if($this->alreadyBumped) {
            array_pop($cpHistoryYml["widgetVersions"]["$widgetURL"]);
            yaml_emit_file($cpHistoryPath, $cpHistoryYml);
        }

        //in case the widget wasn't bumped before, when a framework bump happens, cphistory is updated to the latest framework in the event of a framework bump. Therefore, we now need to remove support for the latest framework for all the previous versions.
        if(!$this->alreadyBumped && $frameworkBumped === true) {
            foreach($cpHistoryYml["widgetVersions"]["$widgetURL"] as $i => $j) {
                $arrayLength = count($cpHistoryYml["widgetVersions"]["$widgetURL"][$i]["requires"]["framework"]);
                if ($cpHistoryYml["widgetVersions"]["$widgetURL"][$i]["requires"]["framework"][$arrayLength - 1] === substr($this->currentFramework, 0, strrpos($this->currentFramework, "."))) {
                    array_pop($cpHistoryYml["widgetVersions"]["$widgetURL"][$i]["requires"]["framework"]);
                }
            }   
            yaml_emit_file($cpHistoryPath, $cpHistoryYml);
        }

        // find all the widgets that extend this widget or contain this widget
        foreach ($cpHistoryYml["widgetVersions"] as $widgetName => $props) {
            $latestVersion = end($props);
            if($latestVersion["extends"]["widget"] === $widgetURL ) {
                array_push($extendedWidgets, $widgetName);
            }

            $containArray = $latestVersion["contains"];
            
            if (!is_null($containArray)) {
                foreach($containArray as $container) {
                    if($container["widget"] === $widgetURL)
                        array_push($containedWidgets, $widgetName);
                }
            }
        }

        //bump the widget
        if($type === "minor") {
            $newMinor = intval(substr($version, strpos($version, '.') + 1)) + 1;
            $newVersion = substr($version, 0, strpos($version, '.')).".".$newMinor.".1";
        }
        else {
            $newMajor = intval(substr($version, 0, strpos($version, '.'))) + 1;
            $newVersion = $newMajor.".0.1";
        }
        $fileContent = file_get_contents($widgetPath);

        $newFileContent = preg_replace_callback('@(version: ").*"@', function($matches) use($newVersion) {
            return $matches[1] . $newVersion. '"';
        }, $fileContent, 1);

        file_put_contents($widgetPath, $newFileContent);
        echo "<div style='color:red'>" . $widgetURL . " has been " . $type . " bumped <br> </div>";

        //update widgets that depend on this widget
        $dependentWidgets = array_merge($containedWidgets, $extendedWidgets);
        if(!empty($dependentWidgets)){
            echo "<div style='border: 2px solid green'>";
            echo "<div style='color:orange'>Widgets that contain/extend ", $widgetURL, " are </div>";
            foreach ($dependentWidgets as $iter) {
                echo "<p>", $iter, "</p>";
            }
            echo "</div>";
            echo "<hr>";

            foreach($dependentWidgets as $iter){
                $extendedWidgetPath = $this->documentRoot."/core/widgets/".$iter."/info.yml";
                $fileContent = file_get_contents($extendedWidgetPath);

                $newFileContent = preg_replace_callback("@(($widgetURL(\r|\n|.)*?versions:\s*\[\").*\"\])@", function($matches) use($newVersion, $iter) {
                    return $matches[2] . substr($newVersion, 0, strrpos($newVersion, '.')). '"]';
                }, $fileContent, 1);

                file_put_contents($extendedWidgetPath, $newFileContent);
                //recursively major or minor bump all widgets that depend on this widget
                $this->majorMinorBumpWidget($iter, $type);
            }
        }
    }
    

    /**
     * Function to add a new framework to the support list of frameworks for all widgets, input received via form input
     * @param string $version The new framework version that will be added to all widgets (only upto the minor version eg. "3.7" and not "3.7.2")
     * @return void   
     */
    public function addSupportedFrameworkToWidgets($version) {
        if(!$version){
            exit('You must specify a version to add.');
        }
        //validate whether the version entered is correct
        $validateArray = explode('.', $version);
        if(count($validateArray) === 3) {
            foreach($validateArray as $validNumber) {
                if(!is_numeric($validNumber))
                    exit("Please specify a valid version of the form major.minor.nano eg: 3.7.6");
            }
        }
        else {
            exit("Please specify a valid version of the form major.minor.nano eg: 3.7.6");
        }
        //updating the manifest with the latest framework version
        $manifestPath = $this->documentRoot."/core/framework/manifest";
        $fileContent = file_get_contents($manifestPath);
        $fileContent = preg_replace_callback('@(version:\s*").*"@', function($matches) use($version) {
            return $matches[1] . $version . '"';
        }, $fileContent, 1, $count);
        file_put_contents($manifestPath, $fileContent);

        //we need to update the widgets with the minor version of the framework
        $version = substr($version, 0, strrpos($version, '.'));

        $files = FileSystem::listDirectory(CORE_WIDGET_FILES, true, true, array('equals', 'info.yml'));
        foreach($files as $info){
            if(preg_match("/(standard.*)\/info\.yml/", $info, $results)) {
                $widgetURL = $results[1];
            }
            $fileContent = file_get_contents($info);
            if (preg_match("/version: \"(.*)\"/", $fileContent, $result)) {
                $widgetLatestVersion = $result[1];
            }
            
            //we need to handle widgets that have been minor bumped differently, these will no longer support previous frameworks
            if (!$this->bumpWorthy($widgetURL, $widgetLatestVersion, "minor")) {
                echo "Updating " . Text::getSubstringAfter($info, 'core/widgets/') . ': ';
                $fileContent = preg_replace_callback('@((framework:\s*\[").*)"\]@', function($matches) use($version) {
                    if(!Text::endsWith($matches[1], $version)){
                        echo "Added version $version<br/>\n";
                        return $matches[2] . $version . '"]';
                    }
                    echo "Version already exists<br/>\n";
                    return $matches[0];
                }, $fileContent, 1, $count);
                if($count > 1){
                    exit("<div style='color:red'>$info was not matched \n\n $fileContent \n\n</div>");
                }
                file_put_contents($info, $fileContent);
            }
            
            else {
                echo "Updating " . Text::getSubstringAfter($info, 'core/widgets/') . ': ';
                $fileContent = preg_replace_callback('@(framework:.*)"\]@', function($matches) use($version) {
                    if(!Text::endsWith($matches[1], $version)){
                        echo "Added version $version<br/>\n";
                        return $matches[1] . '", "' . $version . '"]';
                    }
                    echo "Version already exists<br/>\n";
                    return $matches[0];
                }, $fileContent, 1, $count);
                if($count > 1){
                    exit("<div style='color:red'>$info was not matched \n\n $fileContent \n\n</div>");
                }
                file_put_contents($info, $fileContent);
            }
        }
    }
}
