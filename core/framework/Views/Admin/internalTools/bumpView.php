<!DOCTYPE html>
<html>
    <body onload="updateURLs();">
        <hr>
        <p style="color: red">Before you proceed further, you need to change the file permissions for the info.yml files and the cpHistory file, please execute the following commands from your cp directory:</p>
        <p>1. chmod 777 core/cpHistory</p>
        <p>2. find ./core/widgets/standard -type f -name 'info.yml' -exec chmod 777 {} \;</p>
        <p>3.(optional) It's also recommended that you configure git to ignore file mode changes temporarily if you would like to see the status. You can do that using : "git config core.fileMode false" inside your cp directory</p>
        <p>4.(optional) If you are bumping the framework, you must change the permission of the manifest file as well using "chmod 777 core/framework/manifest"</p>

        <p style="color: red">Once you choose to minor or major bump a widget A, all the widgets that consume A (i.e. extend or contain A) will also be minor or major bumped respectively. If you wish to handle each widget that consumes A differently, please do the bumps manually and do not use this script. Currently, the only way to revert any changes made by this script is via git, so if you have any other changes please save them. </p>
        <p>Please enter the path of the widget, eg: standard/discussion/QuestionComments</p>

        <form action='processBump' method='POST'>
            Widget Path: <input type="text" name="widgetPath" />
            Type of bump:<input type="radio" name="typeOfBump" value="nano">nano
            <input type="radio" name="typeOfBump" value="minor">minor
            <input type="radio" name="typeOfBump" value="major">major

            <input type="submit" name="submit" value="submit"/>
        </form>



        <p style="color: red">Please enter a valid framework, eg :3.7.6 .This version will be added to the framework support of all widgets. Please note that this merely adds the latest framework to the list of supported frameworks for all the widgets and is required only for minor or major framework bumps. A framework bump requires other steps too such as updating the rakefile which are detailed <a href = "http://quartz.us.oracle.com/shelf/docs/Projects/CP/Archive/Dynamic%20Upgrades%20-%2012.11/Versioning/NewVersions.html">here</a> .</p>
        
        If a widget was bumped in this release, it will not support the older framework.
        
        <form action='addSupportedFrameworkToWidgets' method='POST'>
            New Framework: <input type="text" name="name" />
            <input type="submit" name="submit" value="addSupportedFrameworkToWidgets"/>
        </form>

        <p>After making all your changes, please update <a id="updateLink" href="">CPHistory</a> from internalTools and then run the test <a id ="validateLink" href="">ValidateCPHistory</a> to verify if there are any errors.</p>

        <p style="color:red">Now run the following commands to revert the file permissions that were changed:</p>
        <p>1. chmod 644 core/cpHistory</p>
        <p>2. find ./core/widgets/standard -type f -name 'info.yml' -exec chmod 644 {} \;</p>
        <p>3. if you have changed the permission of the manifest file, revert using "chmod 644 core/framework/manifest".
        <p>4. If you have changed git configurations as directed before, you can reset it with "git config core.fileMode true"</p>

        <hr>

        <script>
            function updateURLs() {
                var docURL = document.URL;
                var cpHistoryURL = docURL.substring(0, docURL.search("bumpWidgetsOrFramework")) + "updateCPHistory";
                var validateCPHistoryURL = docURL.substring(0, docURL.search("admin")) + "unitTest/validateCPHistory";
                document.getElementById("updateLink").href = cpHistoryURL;
                document.getElementById("validateLink").href = validateCPHistoryURL;
            }
        </script>
    </body>
</html>