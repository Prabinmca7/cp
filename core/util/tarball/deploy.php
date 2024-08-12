<?
// This runs /ci/admin/cmdline_deploy without including all of the CodeIgniter stuff so
// that we can "deploy" the development pages to production in the process of creating
// the product installation tarball.
//
// This file expects to be in the .cfg/scripts/cp/util/tarball directory when it is run.

// The PHP define replacement makefile works much better if it thinks it's running in development.
putenv('HOSTED=');
putenv('PRODUCT=');
putenv('MAKEFLAGS=');

// The CP code relies on these defines.
// DOCROOT should resolve to .cfg/scripts
define('DOCROOT', str_replace('\\', '/', realpath(dirname(__FILE__))) . '/../../../../');
define('BASEPATH', DOCROOT . 'cp/');
define('APPPATH', BASEPATH . 'customer/development/');
define('CPCORE', BASEPATH . 'core/framework/');
define('OPTIMIZED_FILES', BASEPATH ."generated/");
define('CUSTOMER_FILES', APPPATH);
define('CUSTOMER_CONFIG_FILES',  APPPATH."config/");
define('CORE_FILES', BASEPATH . "core/");
define('CORE_WIDGET_FILES', CORE_FILES . "widgets/");
define('HTMLROOT', realpath(DOCROOT . '/../../../doc_root/'));
define('STAGING_PREFIX', 'staging_');
define('STAGING_NAME', STAGING_PREFIX . '01');
define('DEPLOY_TIMESTAMP_FILE', BASEPATH . 'generated/production/deployTimestamp');
define('OPTIMIZED_ASSETS_PATH', HTMLROOT . '/euf/generated/optimized');
define('YUI_SOURCE_DIR', '/rnt/rnw/yui_3.18/');
define('IS_TARBALL_DEPLOY', true);
define('IS_HOSTED', false);
define("CP_FRAMEWORK_VERSION", $argv[1]);
define("CP_FRAMEWORK_NANO_VERSION", $argv[2]);
define('CPCORESRC', BASEPATH . 'src/core/framework/' . CP_FRAMEWORK_VERSION . '.' . CP_FRAMEWORK_NANO_VERSION . '/');
define('CORE_WIDGET_SRC_FILES', BASEPATH . 'src/core/widgets/');
define('IS_UNITTEST', false);

if (HTMLROOT == '') {
    echo "tarballDeploy expects to be run from the install/dist/unix/makefile.  If you're going to run it in development, you need to define HTMLROOT appropriately.\n";
    exit(1);
}

//check if CI 3 directory exists and load CI 3 bootstrap
if (is_dir(CPCORE . 'CodeIgniter3') && is_readable(CPCORE . 'CodeIgniter3')) {
    require_once CORE_FILES . 'util/tarball/BootstrapCI3.php';
}
else {
    require_once CORE_FILES . 'util/tarball/Bootstrap.php';
}


Versioning::updateDeclarationsFile();
TarballStaging::commitDeploy();
Versioning::serializeCpHistoryFile();

// Tasks that need to do things like require files and scan the filesystem should run
// before #insertVersionDirectories executes
// (it adds the interstitial dirs for the framework + nano version, but the value of defines like CPCORE aren't updated to reflect the dir change).
AdminAssets::optimize();

Versioning::insertVersionDirectories();
Versioning::addVersionStamps();
Versioning::copyWidgetVersionsToReferenceMode();

Versioning::addOldVersions();
