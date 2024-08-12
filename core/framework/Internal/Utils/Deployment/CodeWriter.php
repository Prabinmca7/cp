<?php
namespace RightNow\Internal\Utils\Deployment;

use RightNow\Utils\Text as TextExternal;

/**
 * Quarantines most of the code-writing operations
 * that the Deployer has to perform in order to
 * produce optimized pages into this class.
 */
class CodeWriter {
    private static $namespaceRegex = '@(namespace\s+[_0-9a-zA-Z\\\\]+)\s*(\{|;)@';

    /**
     * Deletes the opening '<?' or '<?php'
     * from the given string.
     * @param string $phpCode PHP code
     * @return string      $phpCode with opening
     *                          php removed
     */
    static function deleteOpeningPHP($phpCode) {
        $phpCode = ltrim($phpCode);
        if(TextExternal::beginsWith($phpCode, "<?php"))
            return TextExternal::getSubstringAfter($phpCode, "<?php");
        if(TextExternal::beginsWith($phpCode, "<?"))
            return TextExternal::getSubstringAfter($phpCode, "<?");
        return $phpCode;
    }

    /**
     * Deletes the closing '?>' from the given string.
     * @param string $phpCode PHP code
     * @return string          $phpCode with closing
     *                                  php removed
     */
    static function deleteClosingPHP($phpCode) {
        $phpCode = rtrim($phpCode);
        if (TextExternal::endsWith($phpCode, '?>'))
            $phpCode = substr($phpCode, 0, strlen($phpCode) - 2);
        return $phpCode;
    }

    /**
     * Creates a string representing the specified array.
     * Differs from the standard `var_export` in that values
     * may be produced as raw php code, not as strings, so
     * that the result is suitable for dumping into pages.
     * @param array $list          Array of items to produce
     *                              the string representation for
     * @param \Closure $valueProducer Called for each item,
     *                                 passed the name and value;
     *                                 the returned result is used
     * @return string                Array representation
     */
    static function createArray(array $list, \Closure $valueProducer = null) {
        $values = '';
        foreach ($list as $name => $value) {
            $processedResult = ($valueProducer) ? $valueProducer($name, $value) : $value;
            $values .= "'$name' => " . $processedResult . ",\n";
        }
        return "array(" . $values . ")";
    }

    /**
     * Given a chunk of PHP code, this will:
     *
     * * remove the opening PHP tag
     * * remove the closing PHP tag
     * * convert a single namespace call to use the bracketed syntax so it can be combined with other PHP code
     *
     * If no namespace line is found, the entire
     * contents will be surrounded in a namespace{..} block.
     *
     * The bracket-surrounding logic simply appends a bracket to
     * the end of $code, so don't use this to transform code with more than one
     * namespace in it.
     * @param string $code PHP code to modify
     * @return string transformed $code
     */
    static function modifyPhpToAllowForCombination($code){
        $code = self::deleteOpeningPHP($code);
        $code = self::deleteClosingPHP($code);

        $foundBracketStyleSyntax = false;

        $code = preg_replace_callback(self::$namespaceRegex,
            function($matches) use(&$foundBracketStyleSyntax) {
                //Only convert namespace blocks if they used semi-colon syntax and not bracket syntax
                if($matches[2] === ';'){
                    return $matches[1] . '{';
                }
                $foundBracketStyleSyntax = true;
                return $matches[0];
            },
            $code, -1, $matchCount);
        if($matchCount === 0){
            $code = "namespace{ \n $code";
        }
        if($foundBracketStyleSyntax === false){
            $code .= "\n}\n";
        }
        return $code;
    }

    /**
     * Inject code into the given block of code after
     * its initial namespace declaration.
     * @param string $code     Code to insert into
     * @param string $toInsert Code to insert
     * @return string           $code with $toInsert inserted
     *                                after the namespace
     */
    static function insertAfterNamespace ($code, $toInsert) {
        return preg_replace(self::$namespaceRegex, "\\0{$toInsert}", $code, 1);
    }

    /**
     * Returns a script tag with the given src.
     * @param string $src Script's src
     * @return string      script tag
     */
    static function scriptTag($src) {
        return "<script type=\"text/javascript\" src=\"$src\"></script>\n";
    }

    /**
     * Returns a link tag with the given href.
     * @param string $href Link's href
     * @return string       link tag
     */
    static function linkTag($href) {
        return "<link href='$href' rel='stylesheet' type='text/css' media='all'/>\n";
    }

    /**
     * Generates a HTML base tag which points to the desired location
     * @param String $path Path to postfix onto base location; ignored if $optimizedAssetsDir is True
     * @param Boolean $shouldOutputHtmlFiveTags Denotes how closing base tag should be generated
     * @param String $optimizedAssetsDir Path to use for optimized assets if standard path should not be used
     */
    static function getBaseHrefTag($path, $shouldOutputHtmlFiveTags, $optimizedAssetsDir) {
        $startTag = "<base href='<?=\RightNow\Utils\Url::getShortEufBaseUrl('sameAsRequest', %s);?>'%s";
        $path = ($optimizedAssetsDir) ? "'$optimizedAssetsDir'" : "\RightNow\Utils\FileSystem::getOptimizedAssetsDir() . '$path'";
        $endTag = ($shouldOutputHtmlFiveTags) ? "/>\n" : "></base>\n";

        return sprintf($startTag, $path, $endTag);
    }

    /**
     * Builds up a chain of if-else if conditionals based on the given
     * theme data array.
     * @param array $cssByTheme Keys are theme paths, values are the css
     *                            for each them
     * @return string             Chunk of php for the runtime theme conditional
     */
    static function createRuntimeThemeConditions(array $cssByTheme) {
        $conditions = array();

        foreach ($cssByTheme as $themePath => $css) {
            $conditions []= "if (get_instance()->themes->getTheme() === '$themePath') { ?>\n$css\n<?}";
        }

        return "<?\n" . implode("\nelse ", $conditions) . "?>\n";
    }

    /**
     * Builds code which calls _checkMeta() with $meta, which is the page's meta information.
     * This is basically just doing a slightly different var_export()
     * (because the values of meta are already escaped).
     * You might see this code and think, "I'll just replace this janky loop with a var_export()."
     * But don't. Things will break.
     * @param array $meta Keyed by attribute name with attribute values.
     * @return string containing code which calls _checkMeta() with $meta.
     */
    static function buildMetaDataArray(array $meta) {
        static $metaCode = "get_instance()->_checkMeta(array(%s));\n";

        $extractedArray = array();
        foreach ($meta as $key => $value) {
            $extractedArray []= "'$key'=>'$value'";
        }

        return sprintf($metaCode, implode(",\n", $extractedArray));
    }

    /**
     * Wraps the given code in a shell that's used for every optimized page.
     * It's the caller's responsibility to ensure that all of the passed params
     * are valid php code.
     * @param string $pageContent    Page php code
     * @param string $widgetCode     All the widget classes for the page
     * @param string $widgetMetaData All the widget meta data for the widget instances
     *                                on the page
     * @param string $themeData      Runtime theme information
     * @return string                 constructed page suitable for writing out
     */
    static function buildOptimizedPageContent($pageContent, $widgetCode, $widgetMetaData, $themeData) {
        // Pacify PHP_CodeSniffer's unused variable check.
        assert($pageContent || $widgetCode || $widgetMetaData || $themeData);

        return "<?php\n" . <<<PAGE
namespace{
    get_instance()->themes->setRuntimeThemeData($themeData);
    $widgetMetaData
    get_instance()->clientLoader->setJavaScriptModule(get_instance()->meta['javascript_module']);
}
$widgetCode
namespace{
    use \RightNow\Utils\FileSystem;
    ?>$pageContent<?
}
?>

PAGE;
    }

    /**
     * Creates a function wrapper for $viewContent.
     * @param string $viewFunctionName Name of the function
     * @param string $viewContent      Content to render
     * @param bool $static             Whether the function is static
     * @return string                   function wrapper
     */
    static function createViewMethodWrapper($viewFunctionName, $viewContent, $static = false) {
        // Pacify PHP_CodeSniffer's unused variable check.
        assert($viewFunctionName || $viewContent);
        $static = ($static) ? 'static ' : '';

        return <<<FUNC
    {$static}function $viewFunctionName (\$data) {
        extract(\$data);
        ?>$viewContent<?
    }
FUNC;
    }

    /**
     * Creates a function that simply delegates to its parent.
     * @param string $viewFunctionName Function name
     * @param string $parentMethod     Name of parent method to call.
     * @return string                   function
     */
    static function createPassThruViewMethod($viewFunctionName, $parentMethod) {
        return <<<FUNC
        function $viewFunctionName (\$data) {
            parent::$parentMethod(\$data);
        }
FUNC;
    }

    /**
     * Creates a header function for a widget.
     * @param string $functionName Function's name
     * @param array $headerInfo   Meta data info for the widget
     * @return string               constructed function
     */
    static function createWidgetHeaderFunction($functionName, array $headerInfo) {
        // Pacify PHP_CodeSniffer's unused variable check.
        assert($functionName || $headerInfo);

        return <<<FUNC

function $functionName() {
    \$result = array(
        'js_name'        => '{$headerInfo['jsName']}',
        'library_name'   => '{$headerInfo['className']}',
        'view_func_name' => '{$headerInfo['viewFunctionName']}',
        'meta'           => {$headerInfo['metaArray']},
    );
    \$result['meta']['attributes'] = {$headerInfo['writtenAttributes']};
    return \$result;
}

FUNC;
    }

    /**
     * Creates a namespace and class wrapper around some code.
     * @param string $contents  PHP code to be injected into
     *                           a class
     * @param array $options Options:
     *                        - className: string
     *                        - namespace: string (optional)
     *                        - extends: string (optional)
     * @return string            wrapped $contents
     */
    static function wrapInsideClass($contents, array $options) {
        // Pacify PHP_CodeSniffer's unused variable check.
        assert(!is_null($contents));

        $namespace = (isset($options['namespace']) && $options['namespace']) ? " {$options['namespace']}" : '';
        $extends = (isset($options['extends']) && $options['extends']) ? " extends {$options['extends']}" : '';

        return <<<CLASS
        namespace{$namespace} {
            class {$options['className']}{$extends} {
                $contents
            }
        }

CLASS;
    }
}
