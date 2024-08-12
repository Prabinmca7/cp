<?php
namespace RightNow\Internal\Utils\CodeAssistant;

class WidgetConverterWriter extends \RightNow\Internal\Libraries\Widget\FileWriter {
    private $context;
    function __construct($context, $appendedContent) {
        $this->context = $context;
        $this->appendedContent = $appendedContent;
        $context->setAbsolutePath(CUSTOMER_FILES . 'widgets/');
    }

    public function directoryAlreadyExists() {
        return $this->context->fileExists($this->baseDir);
    }

    public function writeDirectory() {
        return $this->context->createDirectory($this->baseDir);
    }

    public function write($file, $contents, $absolutePathSpecified = false) {
        //Since we're just moving over customer files, we don't want to create any new CSS files for them (neither base nor presentation)
        if(\RightNow\Utils\Text::endsWith($file, '.css')){
            return true;
        }

        $mapping = array(
            'logic.js' => 'js',
            'controller.php' => 'php',
            'view.php' => 'view'
        );

        //Append the original file content to the newly created files.
        $type = $mapping[$file];
        if(isset($this->appendedContent[$type]) && ($extraContent = $this->appendedContent[$type])) {
            if($extraContent['isStandard']) {
                $path = DOCROOT . '/euf/application/rightnow/widgets/' . $extraContent['path'];
            }
            else {
                $path = $extraContent['path'];
            }

            if(($extraContent = $this->context->getFile($path)) === false) {
                return false;
            }

            if($type === 'js' || $type === 'php') {
                $extraContent = implode("\n", array_map(function($line) { return '//   ' . $line; }, explode("\n", $extraContent)));
            }
            else if($type === 'view') {
                $extraContent = "<!-- \n$extraContent\n-->";
            }

            $contents = $contents . "\n\n\n" . $extraContent;
        }

        return $this->context->createFile(($absolutePathSpecified) ? $file : $this->baseDir . '/' . $file, $contents);
    }
}
