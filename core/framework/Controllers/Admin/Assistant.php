<?php

namespace RightNow\Controllers\Admin;

use RightNow\Internal\Utils\CodeAssistant,
    RightNow\Internal\Utils\Version,
    RightNow\Utils\Config,
    RightNow\Utils\FileSystem,
    RightNow\Utils\Text;

require_once CPCORE . 'Internal/Utils/CodeAssistant.php';

class Assistant extends Base {
    function __construct() {
        parent::__construct(true, '_verifyLoginWithCPEditPermission');
    }

    /**
     * Load up the default page with all available operations.
     */
    function index() {
        $data = array(
            'developmentVersions' => $this->getVersions('Development'),
            'productionVersions' => $this->getVersions('Production')
        );
        $this->_render('tools/codeAssistant', $data, Config::getMessage(CODE_ASSISTANT_LBL), array(
            'js' => 'tools/codeAssistant',
            'css' => array('tools/codeAssistant', 'tools/codeAssistantShared')
        ));
    }

    /**
     * Display a list of backups.
     */
    function backups() {
        $prefixMapping = array(
            'ca' => Config::getMessage(CODE_ASSISTANT_BACKUP_LBL)
        );

        $data = array();
        if(FileSystem::isReadableDirectory(CodeAssistant::getBackupPath())) {
            $data['useBackupUrl'] = true;
            $timestampDirectories = FileSystem::listDirectory(CodeAssistant::getBackupPath(), true);
            usort($timestampDirectories, function($first, $second) use ($prefixMapping) {
                $regex = '@(' . implode('|', array_keys($prefixMapping)) . ')([\d]{2}-[\d]{2}-[\d]{4} [\d]{2}\.[\d]{2}\.[\d]{2})@';
                $getDate = function($path) use ($regex) {
                    try {
                        if(!preg_match($regex, $path, $matches)) return false;
                        list($date, $time) = explode(' ', $matches[2]);
                        list($month, $day, $year) = explode('-', $date);
                        return new \DateTime("$year-$month-$day $time");
                    }
                    catch(\Exception $e) {
                        return false;
                    }
                };

                //Sort the dates
                if(($first = $getDate($first)) && ($second = $getDate($second))) {
                    return ($first < $second ? -1 : ($first > $second ? 1 : 0));
                }

                //If we can't parse the date, say that the invalid date comes first in the list.
                return (!$first && !$second ? 0 : (!$first ? -1 : 1));
            });

            foreach($timestampDirectories as $directory) {
                $shortDirectory = Text::getSubstringAfter($directory, CodeAssistant::getBackupPath());
                foreach($prefixMapping as $key => $message) {
                    if(Text::beginsWith($shortDirectory, $key)) {
                        $prefixKey = $key;
                        break;
                    }
                }

                $files = array();
                foreach(FileSystem::listDirectory($directory, true, true, array('method', 'isFile')) as $file) {
                    $handler = new \RightNow\Internal\Libraries\WebDav\PathHandler($file, true);
                    $files[] = array(
                        'visibleText' => Text::getSubstringAfter($file, CodeAssistant::getBackupPath() . $shortDirectory . '/'),
                        'davPath' => $handler->getDavPath()
                    );
                }

                if(count($files)) {
                    $data['backups'][] = array(
                        'prefixMessage' => $prefixMapping[$prefixKey] ?: Config::getMessage(STANDARD_BACKUP_LBL),
                        'directory' => Text::getSubstringAfter($shortDirectory, $prefixKey),
                        'files' => $files
                    );
                }
            }
        }

        $this->_render('tools/backups', $data, Config::getMessage(CODE_ASSISTANT_BACKUPS_LBL), array(
            'css' => array('tools/backups', 'tools/codeAssistantShared'),
            'js' => 'tools/backups'
        ));
    }

    /**
     * Given a source and a destination framework return the list of applicable operations.
     */
    function retrieveOperations() {
        $this->_verifyAjaxPost();

        try {
            $data = array('operations' => array());
            foreach(CodeAssistant::getOperations($this->input->post('current'), $this->input->post('next')) as $operation) {
                $data['operations'][] = array(
                    'title' => $operation['title'],
                    'description' => $operation['description'],
                    'id' => $operation['id'],
                    'type' => $operation['type']
                );
            }
        }
        catch(\Exception $e) {
            $data = array('genericError' => $e->getMessage());
        }
        $this->_renderJSONAndExit($data);
    }

    /**
     * Given an operation ID, load up the operation and get the units.
     */
    function retrieveUnits() {
        $this->_verifyAjaxPost();

        try {
            $operation = CodeAssistant::getOperationById($this->input->post('id'));
            $data = array(
                'units' => CodeAssistant::getUnits($operation),
                'instructions' => $operation['instructions']
            );
        }
        catch(\Exception $e) {
            $data = array('genericError' => $e->getMessage());
        }
        $this->_renderJSONAndExit($data);
    }

    /**
     * Given a unit or array of units, run each and return the instruction set.
     */
    function getInstructions() {
        $this->_verifyAjaxPost();

        try {
            $operation = CodeAssistant::getOperationById($this->input->post('id'));
            $unit = $this->input->post('unit');
            $data = CodeAssistant::getInstructions($operation, array($unit));
        }
        catch(\Exception $e) {
            $data = array('genericError' => $e->getMessage());
        }

        $this->_renderJSONAndExit($data);
    }

    /**
     * Run an array of units and commit the changes to disc
     */
    function commitInstructions() {
        $this->_verifyAjaxPost();

        try {
            $operation = CodeAssistant::getOperationById($this->input->post('id'));
            $instructions = CodeAssistant::getInstructions($operation, json_decode($this->input->post('units')));
            $instructions = array_merge($instructions, json_decode($this->input->post('instructions'), true));

            $results = CodeAssistant::processInstructions($operation, $instructions);
            $data = array(
                'successMessage' => $operation['success'],
                'failureMessage' => $operation['failure'],
                'successfulUnits' => $results['successfulUnits'],
                'failedUnits' => $results['failedUnits'],
                'postExecuteMessage' => $results['postExecuteMessage']
            );
        }
        catch(\Exception $e) {
            $data = array('genericError' => $e->getMessage());
        }

        $this->_renderJSONAndExit($data);
    }

    /**
     * Returns the various version information for the specified mode
     * @param string $mode Name of mode to check
     * @return array Details of version for the provided mode
     */
    private function getVersions($mode) {
        $environmentVersions = Version::getVersionsInEnvironments('framework');

        $allVersions = CodeAssistant::getAllFrameworkVersions();
        if(($key = array_search('2.0', $allVersions)) !== false) {
            $allVersions[$key] = Config::getMessage(PRE_3_0_LBL);
        }

        return array(
            'versions' => $allVersions,
            'selectedVersion' => ($environmentVersions[$mode] === '2.0') ? Config::getMessage(PRE_3_0_LBL) : $environmentVersions[$mode],
            'mode' => $mode,
            'modeLabel' => ($mode === 'Production') ? Config::getMessage(PRODUCTION_MODE_VERSION_LBL) : Config::getMessage(DEVELOPMENT_MODE_VERSION_LBL)
        );
    }
}
