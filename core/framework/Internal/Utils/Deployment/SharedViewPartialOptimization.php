<?
namespace RightNow\Internal\Utils\Deployment;

use RightNow\Utils\Text,
    RightNow\Utils\FileSystem;

/**
 * Quarantines the shared view partial operations
 * that the Deployer has to perform in order to
 * produce optimized pages into this class.
 *
 * This class builds up a class (SharedViewPartials) that has one static method for every _standard_ view partial.
 * The intention is for Deployer to insert this class into optimized_includes.php.
 *
 * Then another class is built up (CustomSharedViewPartials) that contains one static method for every custom view partial.
 * This class extends from SharedViewPartials. If a custom view partial is registered in extensions.yml to override the
 * standard one, then this child class contains a view partial method that overrides the parent's.
 * The intention is for Deployer to inline this class into the page code. If the site doesn't have any custom view partials,
 * then the CustomSharedViewPartials class has an empty implementation and all of the method calls of course fall thru to
 * the SharedViewPartials class.
 */
class SharedViewPartialOptimization {
    /**
     * Builds a class with one static view method for each standard view partial where
     * the content of that method is the view file's content.
     * @return string Constructed class
     */
    static function buildSharedViewPartials () {
        static $phpContents;
        if (is_null($phpContents)) {
            return CodeWriter::wrapInsideClass(self::getSharedViewPartialContentAsViewFunctions(CPCORE . 'Views/Partials'), array(
                'className' => 'SharedViewPartials',
                'namespace' => 'RightNow\Libraries\Widgets',
            ));
        }

        return $phpContents;
    }

    /**
     * Builds a class with one static view method for each customer view partial where
     * the content of that method is the view file's content.
     * @return string Constructed class
     */
    static function buildCustomSharedViewPartials () {
        static $phpContents;

        if (is_null($phpContents)) {
            $phpContents = CodeWriter::wrapInsideClass(self::getSharedViewPartialContentAsViewFunctions(APPPATH . 'views/Partials', true), array(
                'className' => 'CustomSharedViewPartials',
                'namespace' => 'Custom\Libraries\Widgets',
                'extends'   => '\RightNow\Libraries\Widgets\SharedViewPartials',
            ));
        }

        return $phpContents;
    }

    /**
     * Builds a list of shared view partial content for the site.
     * @param string  $dir                  Base directory to look within
     * @param boolean $allowCustomOverrides Whether to allow view partial content
     *                                       in the customer directory to take precedence
     *                                       over standard content
     * @return array                        keys are each partial's name (excluding file extension)
     *                                           values are the partial's contents
     */
    private static function getSharedViewPartialContent ($dir, $allowCustomOverrides = false) {
        $partials = array();

        if (!FileSystem::isReadableDirectory($dir)) return $partials;

        $files = FileSystem::listDirectory($dir, false, true, array('match', '/.*\.html\.php$/'));
        foreach ($files as $filePath) {
            $filePathWithoutExtension = Text::getSubstringBefore($filePath, '.html.php');

            if ($allowCustomOverrides) {
                $viewHandler = new \RightNow\Internal\Libraries\Widget\ViewPartials\Handler(
                    'Partials.' . str_replace("/", ".", $filePathWithoutExtension), 'NonOptimizedCustom');
                try {
                    $partials[$filePathWithoutExtension] = $viewHandler->view->getContents('NonOptimizedCustom');
                }
                catch (\Exception $e) {
                    // Unregistered custom view with the same path as a standard one.
                    // Don't create anything for this case, since inheritance will fall thru
                    // to the parent class.
                    continue;
                }
            }
            else {
                $partials[$filePathWithoutExtension] = @file_get_contents("{$dir}/{$filePath}");
            }
        }

        return $partials;
    }

    /**
     * Gets all the shared view partial content as a string with functions
     * containing each partial's content.
     * @param string  $dir                  Base directory to look within
     * @param boolean $allowCustomOverrides Whether to allow view partial content
     *                                       in the customer directory to take precedence
     *                                       over standard content
     * @return string                        static php functions
     */
    private static function getSharedViewPartialContentAsViewFunctions ($dir, $allowCustomOverrides = false) {
        $methods = array();
        $partials = self::getSharedViewPartialContent($dir, $allowCustomOverrides);

        foreach ($partials as $name => $content) {
            $methods []= CodeWriter::createViewMethodWrapper(str_replace(array('/', ' '), '_', "{$name}_view"), $content, true);
        }

        return implode("\n", $methods);
    }
}
