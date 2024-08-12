<?

namespace RightNow\Internal\Libraries\Deployment\Assets;

/**
 * Container for Assets that exposes an Iterator interface.
 * This allows Assets, arrays of assets, and other AssetGroups
 * to be iterated thru in one swoop without having to care
 */
class AssetGroup implements \Iterator {
    private $position = 0;
    protected $assets = array();

    /**
     * Receives a variable number of Asset args which can be:
     * - Asset
     * - Array
     * - Iterator-type (GlobAsset)
     */
    function __construct () {
        $args = func_get_args();
        foreach ($args as $asset) {
            if (self::isAssetGroup($asset)) {
                $this->addGroupOfAssets($asset);
            }
            else {
                $this->add($asset);
            }
        }
    }

    /**
     * Adds a group of assets.
     * @param array|\Iterator $asset Group
     */
    function addGroupOfAssets ($asset) {
        foreach ($asset as $groupedAsset) {
            $this->add($groupedAsset);
        }
    }

    /**
     * Add a single asset.
     * @param Asset $asset Asset
     */
    function add ($asset) {
        $this->assets []= $asset;
    }

    /**
     * The \Iterator rewind method.
     */
    #[\ReturnTypeWillChange]
    function rewind () {
        $this->position = 0;
    }

    /**
     * The \Iterator current method.
     */
    #[\ReturnTypeWillChange]
    function current () {
        return $this->assets[$this->position];
    }

    /**
     * The \Iterator key method.
     */
    #[\ReturnTypeWillChange]
    function key () {
        return $this->position;
    }

    /**
     * The \Iterator next method.
     */
    #[\ReturnTypeWillChange]
    function next () {
        ++$this->position;
    }

    /**
     * The \Iterator valid method.
     */
    #[\ReturnTypeWillChange]
    function valid () {
        return isset($this->assets[$this->position]);
    }

    /**
     * It's an Asset if it quacks like an asset duck (quacks "minify").
     * It's not an Asset if it doesn't.
     * @param  object|array $asset Asset
     * @return boolean      Whether $asset conforms to
     *                                an Asset's duck-type
     */
    private static function isAssetGroup ($asset) {
        return !method_exists((object)$asset, 'minify');
    }
}
