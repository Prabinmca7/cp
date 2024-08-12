#! /bin/bash
# To use this, run the script and redirect the output to CoreCodeIgniter.php.
# For example: 
#
#    ./createCoreCodeIgniter.sh > CoreCodeIgniter.php
#
# or to create a CoreCodeIgniter.php which just requires its constituent parts, which is
# useful while you're making changes to one of those files, add the -d switch.  For example:
#
#    ./createCoreCodeIgniter.sh -d > CoreCodeIgniter.php
#


# Remember that any file you add to this list will have have its first and last lines removed.

INPUT_FILES="
    core/Common.php
    core/Hooks.php
    core/Config.php
    core/Router.php
    core/Output.php
    core/Input.php
    core/URI.php
    core/Loader.php
    core/Controller.php
    core/Exceptions.php
    core/Security.php
    core/Themes.php
    core/Rnow.php
    libraries/Parser.php
    libraries/User_agent.php
    core/CodeIgniter.php
    "

DIR=`dirname $0`
echo "<?php"
echo "/**"
echo " * system/CoreCodeIgniter.php"
echo " * ------------------------------------------------------------------------"
echo " * This file contains the core CodeIgniter files/classes combined into one"
echo " * large file for performance improvements.  This file is included from within"
echo " * the CP Initializer Script (cp/core/framework/init.php)."
echo " *"
echo " * This file contains the following files/classes (in order):"
echo " *"
for inputFile in $INPUT_FILES; do 
    echo " *           system/$inputFile"
done
echo " *"
echo " * ------------------------------------------------------------------------*/"
for inputFile in $INPUT_FILES; do 
    if [ "$1" == "-d" ]; then
        echo "require_once(dirname(__FILE__) . '/$inputFile');"
    else 
        echo ""
        echo ""
        # Echo the file minus its first and last lines.  
        # Those are expected to contain the open and close PHP tags, which we do not want repeated in the file.
        cat "$DIR/$inputFile" | sed '1d' 
    fi
done
