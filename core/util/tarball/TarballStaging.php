<?
class TarballStaging {
    static function commitDeploy() {
        $deployer = new \RightNow\Internal\Libraries\Deployer(new \RightNow\Internal\Libraries\TarballDeployOptions());
        $deployer->prepare_deploy();
        self::handleErrors($deployer);
        $deployer->commit_deploy();
        self::initializeStaging();
    }

    private static function initializeStaging($stagingName = STAGING_NAME) {
        echo "Initializing $stagingName environment.";
        try { // errors non-fatal for now
            require_once CPCORE . 'Internal/Libraries/Staging.php';
            $deployer = new \RightNow\Internal\Libraries\Deployer(new \RightNow\Internal\Libraries\TarballStagingDeployOptions(new \RightNow\Internal\Libraries\Stage($stagingName, array('initialize' => true)), '', true));
            $deployer->stage();
            self::handleErrors($deployer, false);
        }
        catch (Exception $e) {
            echo 'ERROR: ' . $e->getMessage();
        }
    }

    private static function handleErrors($deployer, $fatal = true) {
        $errors = $deployer->getCompileErrors();
        $numberOfErrors = count($errors);

        if ($fatal) {
            echo "Number of compile errors: $numberOfErrors\n";
        }
        if ($numberOfErrors > 0) {
            echo "Errors:\n", implode("\n", $errors), "\n\n";
            if ($fatal) {
                exit(1);
            }
        }
    }
}
