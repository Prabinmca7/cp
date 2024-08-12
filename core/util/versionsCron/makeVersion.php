<?

use \RightNow\Internal\Libraries;

// This runs a portion of /ci/admin/cmdline_deploy without including all of the CI stuff
// so we can generate the optimized_widgets.*, optimized_include.php and minified assets
// files.
//
// This file expects to be run from it's current location. It has been crafted to run without
// needing to run make or even have a site - it can be ran from a fresh checkout.

putenv('HOSTED=');
putenv('PRODUCT=');
putenv('MAKEFLAGS=');

// the following CLI options are required
if (count($argv) !== 4)
    exit(sprintf("Usage: %s <PRODUCT_VERSION> <CP_FRAMEWORK_VERSION> <CP_FRAMEWORK_NANO_VERSION>\n", $argv[0]));

define("CP_FRAMEWORK_VERSION", $argv[2]);
define("CP_FRAMEWORK_NANO_VERSION", $argv[3]);

// specify the minimum set of defines needed
define('DOCROOT', str_replace('\\', '/', realpath(dirname(__FILE__) . '/../../../../../../') . '/'));
define('COMMON_DOCROOT', str_replace('\\', '/', realpath(dirname(__FILE__) . '/../../../../../../../../common/scripts/') . '/'));
define('BASEPATH', DOCROOT . "cp/versions/$argv[1]/");
define('APPPATH', BASEPATH . 'customer/development/');
define('CPCORE', BASEPATH . 'core/framework/');
define('OPTIMIZED_FILES', BASEPATH ."generated/");
define('CUSTOMER_FILES', APPPATH);
define('CORE_FILES', BASEPATH . "core/");
define('CORE_WIDGET_FILES', CORE_FILES . "widgets/");
define('HTMLROOT', realpath(BASEPATH . 'doc_root')); // we use a fake doc_root to store the minified assets

// reflect the non-src directories here, since we do not create version directories during cron execution
define('CPCORESRC', BASEPATH . 'core/framework/');
define('CORE_WIDGET_SRC_FILES', BASEPATH . 'core/widgets/');

define('DEPLOY_TIMESTAMP_FILE', BASEPATH . 'generated/production/deployTimestamp');
define('OPTIMIZED_ASSETS_PATH', HTMLROOT . '/euf/generated/optimized');
define('YUI_SOURCE_DIR', '/rnt/rnw/yui_3.18/');
define('IS_TARBALL_DEPLOY', true);
define('IS_HOSTED', false);

//check if CI 3 directory exists and load CI 3 bootstrap
if (is_dir(CPCORE . 'CodeIgniter3') && is_readable(CPCORE . 'CodeIgniter3')) {
    require_once CORE_FILES . 'util/versionsCron/VersionBootstrapCI3.php';
}
else {
    require_once CORE_FILES . 'util/versionsCron/VersionBootstrap.php';
}


$deployer = new \RightNow\Internal\Libraries\Deployer(new \RightNow\Internal\Libraries\VersionCronDeployOptions());
$deployer->prepare_deploy();

AdminAssets::optimize();
Versioning::addVersionStamps();
Versioning::updateDeclarationsFile();
