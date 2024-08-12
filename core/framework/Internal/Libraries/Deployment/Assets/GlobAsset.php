<?

namespace RightNow\Internal\Libraries\Deployment\Assets;

use RightNow\Utils\Text;

require_once __DIR__ . "/Asset.php";
require_once __DIR__ . "/AssetGroup.php";

/**
 * Container for assets that are found in a glob
 * path and exposed thru an Iterator interface.
 */
class GlobAsset extends AssetGroup {
    /**
     * Constructor. Builds a group of Assets found via `$globPath`.
     * @param string $globString     Absolute glob string
     * @param string $outputFilePath Absolute output dir path
     */
    function __construct ($globString, $outputFilePath) {
        $globDir = basename($outputFilePath);

        foreach (glob($globString) as $path) {
            $relativePath = Text::getSubstringAfter($path, $globDir);
            $this->add(new Asset($path, "{$outputFilePath}{$relativePath}"));
        }
    }
}
