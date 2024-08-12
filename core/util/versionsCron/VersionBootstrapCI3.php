<?

/**
 * Fake stub classes required by framework files
 */
class CI_Controller {

}

require_once CPCORE . 'CodeIgniter3/system/core/Rnow.php';

$requiredFiles = array_merge(
    Rnow::getCorePhpIncludes(),
    Rnow::getCoreCompatibilityPhpIncludes(),
    array(
        CPCORE . 'Internal/Libraries/Deployer.php',
        CPCORE . 'Internal/Libraries/ThemeParser.php',
        CPCORE . 'CodeIgniter/system/libraries/Themes.php',
        CPCORE . 'Internal/Utils/FileSystem.php',
    )
);

foreach ($requiredFiles as $file) {
    require_once $file;
}

/**
 * This is a fake Rnow class. We need this for obvious reasons, but when this
 * runs, we don't have a site (e.g. there is no DB).
 */
class FakeRnow extends Rnow{
    function __construct() {}
}

class FakeCI {
    function __construct() {
        $this->rnow = new FakeRnow();
    }
}
// @codingStandardsIgnoreStart
function get_instance() {
// @codingStandardsIgnoreEnd
    static $instance;
    if (!isset($instance)) {
        $instance = new FakeCI();
    }
    return $instance;
}

//Include the mod_info file in order to get the build defines. We don't really need the API included as well, but this is
//probably the cleanest solution.
require_once BASEPATH . 'mod_info.phph';
require_once DOCROOT . '/include/rnwintf.phph';

//We need to include the message and config defines because we validate against them during deploy (for JavaScript).
require_once COMMON_DOCROOT . 'include/config/config.phph';
require_once COMMON_DOCROOT . 'include/msgbase/msgbase.phph';

//Include some of the files from tarball deploy
require_once CORE_FILES . 'util/tarball/AdminAssets.php';
require_once CORE_FILES . 'util/tarball/Versioning.php';
