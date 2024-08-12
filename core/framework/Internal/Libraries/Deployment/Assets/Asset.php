<?

namespace RightNow\Internal\Libraries\Deployment\Assets;

require_once __DIR__ . "/AssetOptimizer.php";

/**
 * Represents an asset. The type is assumed to be JS.
 */
class Asset {
    /**
     * Optimized output file path
     * @var string
     */
    public $optimizedPath;

    /**
     * Non optimized input file path
     * @var string|array
     */
    private $nonOptimizedPath;

    /**
     * Memoized minified content
     * @var string|array
     */
    private $minifiedContent;

    /**
     * Constructor.
     * @param string|array $nonOptimizedPath string input path or array
     *                                       of input paths
     * @param string $optimizedPath    Output path
     */
    function __construct ($nonOptimizedPath, $optimizedPath) {
        $this->nonOptimizedPath = $nonOptimizedPath;
        $this->optimizedPath = $optimizedPath;
    }

    /**
     * Minify and memoize the contents of `$nonOptimizedPath`.
     * @param  boolean $obfuscate Whether to obfuscate (via Closure Compiler)
     *                            or just strip newlines and comments (via JSMin)
     * @return string             Minified content
     * @throws \Exception If `$nonOptimizedPath` is invalid
     */
    function minify ($obfuscate = true) {
        if (!$this->minifiedContent) {
            $this->minifiedContent = $this->runMinify($obfuscate);
        }
        return $this->minifiedContent;
    }

    /**
     * Does the minification. Intended as a hook method for
     * subclasses to override, if needed.
     * @param  boolean $obfuscate Whether to obfuscate or strip
     * @return string            Minified content
     */
    protected function runMinify ($obfuscate) {
        return AssetOptimizer::minify($this->nonOptimizedPath, array(
            'obfuscate' => $obfuscate,
        ));
    }
}
