<?php

namespace RightNow\UnitTest;

use RightNow\Utils\FileSystem,
    RightNow\Utils\Text,
    RightNow\Connect\v1_4 as Connect,
    RightNow\Utils\Connect as ConnectUtil,
    RightNow\Libraries\Decorator,
    RightNow\UnitTest\Helper;

/** Fixture? Factory? Fixtory? Makes objects from yaml files for our tests **/
class Fixture {
    /**
     * Variables used to replace IDs in test output
     * @var array
     */
    public $variables = array();

    /**
     * Variables grouped by 'type' (e.g. CommunityQuestion, CommunityUser, etc)
     * @var array
     */
    private $variableTypes = array();

    /**
     * Errors encountered during processing
     * @var array
     */
    public $errors = array();

    /**
     * Warnings encountered during processing
     * @var array
     */
    public $warnings = array();

    /**
     * Saved and destroyed object messages encountered during processing
     * @var array
     */
    public $savedAndDestroyedMessages = array();

    /**
     * Object data loaded from YAML file
     * @var array
     */
    protected $loadedFixtures = array();

    /**
     * Whether to output echo statements while saving and destroying
     * @var boolean
     */
    private $verbose = false;

    function __construct() {
        $this->resetData();
    }

    /**
     * Clean up all loaded fixtures on destruct
     */
    function __destruct() {
        $this->destroy();
    }

    /**
     * Create an instance of an object from a fixture file
     * @returns
     */
    function make($fixtureName, $parameters = array()) {
        if (!array_key_exists($fixtureName, $this->loadedFixtures) &&
            $objectFromData = $this->loadObjectFromFixture($fixtureName, $parameters)) {
            $this->loadedFixtures[$fixtureName] = $objectFromData;
        }

        if (!$loadedObject = $this->loadedFixtures[$fixtureName]) {
            $this->errors []= "No fixture loaded for $fixtureName";
        }
        return $loadedObject;
    }

    /**
     * Clean up all loaded fixtures
     */
    function destroy() {
        $runCommit = false;
        $orderedDestructionPreference = array(
            CONNECT_NAMESPACE_PREFIX . '\CommunityQuestion',
            CONNECT_NAMESPACE_PREFIX . '\CommunityUser',
            CONNECT_NAMESPACE_PREFIX . '\Contact',
            CONNECT_NAMESPACE_PREFIX . '\ServiceProduct',
            CONNECT_NAMESPACE_PREFIX . '\ServiceCategory',
        );
        if (!$this->loadedFixtures) {
            return;
        }
        $loadedFixtures = array_reverse($this->loadedFixtures);

        // destroy objects in order
        foreach ($orderedDestructionPreference as $type) {
            foreach ($loadedFixtures as $key => $object) {
                try {
                    if (get_class($object) === $type) {
                        if ($this->verbose) {
                            $this->savedAndDestroyedMessages[] = "destroyed " . get_class($object) . " (" . $object->ID . ")";
                        }
                        unset($loadedFixtures[$key]);
                        Helper::destroyObject($object);
                        $runCommit = true;
                    }
                }
                catch(\Exception $e) {
                    // fixture has already been destroyed
                    if ($this->verbose) {
                        echo "error: failed while destroying " . get_class($fixture) . " " . $e->getMessage() . "<br>\n";
                    }
                }
            }
        }

        if ($runCommit) {
            try {
                Connect\ConnectAPI::commit();
            }
            catch(\Exception $e) {
                // fixture has already been destroyed
                if ($this->verbose) {
                    echo "error: trying to commit destruction " . $e->getMessage() . "<br>\n";
                }
            }
        }

        $this->resetData();
    }

    function softDelete() {
        $runCommit = false;
        if (!$this->loadedFixtures) {
            return;
        }
        $loadedFixtures = array_reverse($this->loadedFixtures);

        // first, destroy any best answers
        foreach ($loadedFixtures as $key => $object) {
            if (get_class($object) === CONNECT_NAMESPACE_PREFIX . '\CommunityQuestion') {
                if (count($object->BestCommunityQuestionAnswers)) {
                    $bestAnswerArrayType = CONNECT_NAMESPACE_PREFIX . '\BestCommunityQuestionAnswerArray';
                    $object->BestCommunityQuestionAnswers = new $bestAnswerArrayType();
                    if ($this->verbose) {
                        $this->savedAndDestroyedMessages[] = "destroyed best answers associated to question (" . $object->ID . "";
                    }
                    try {
                        $object->save();
                        $runCommit = true;
                    }
                    catch(\Exception $e) {
                        // fixture has already been destroyed
                        if ($this->verbose) {
                            echo "error: failed while destroying best answers associated to question (" . $object->ID . ") " . $e->getMessage() . "<br>\n";
                        }
                    }
                }
            }
        }

        $statusIDs = array(
            'CommunityQuestion' => 31,
            'CommunityComment' => 35,
            'CommunityUser' => 40,
        );

        foreach ($loadedFixtures as $key => $object) {
            foreach ($statusIDs as $connectObject => $statusID) {
                if (get_class($object) === CONNECT_NAMESPACE_PREFIX . '\\' . $connectObject) {
                    if ($this->verbose) {
                        $this->savedAndDestroyedMessages[] = "soft deleted a $connectObject (" . $object->ID . ")";
                    }
                    $object->StatusWithType->Status->ID = $statusID;
                    try {
                        $object->save();
                        $runCommit = true;
                    }
                    catch(\Exception $e) {
                        // fixture has already been destroyed
                        if ($this->verbose) {
                            echo "error: failed while soft deleting a $connectObject (" . $object->ID . ") " . $e->getMessage() . "<br>\n";
                        }
                    }
                }
            }
        }

        if ($runCommit) {
            try {
                Connect\ConnectAPI::commit();
            }
            catch(\Exception $e) {
                // fixture has already been destroyed
                if ($this->verbose) {
                    echo "error: trying to commit destruction " . $e->getMessage() . "<br>\n";
                }
            }
        }
    }

    /**
     * Create the appropriate objects from the fixture data
     * @param string $fixtureName Name used to look up the fixture file
     * @param array $parameters Additional parameters to be added to the fixture being loaded
     * @return Object|null Fully loaded object from the fixture
     */
    protected function loadObjectFromFixture($fixtureName, $parameters = array()) {
        if (!$fixtureData = $this->loadFixtureFromFile($fixtureName)) {
            $this->errors []= "No fixture data for $fixtureName";
            return;
        }

        if ($fixtureData['type'] && $fixtureData['object']) {
            $type = CONNECT_NAMESPACE_PREFIX . '\\' . $fixtureData['type'];
            $fixtureObject = new $type();
            $objectMetadata = $type::getMetadata();

            $nestedFixturesToAdd = array();

            $fixtureData['object'] = array_merge($fixtureData['object'], $parameters);

            while (list($fieldName, $valueToSet) = each($fixtureData['object'])) {
                $fieldNameWithType = $fixtureData['type'] . '.' . $fieldName;

                if ($fieldName === 'Contact' && $fixtureData['type'] === 'CommunityUser') {
                    $this->addUser($fixtureObject, 'CommunityUser.Contact', array('fixture' => $valueToSet['fixture']));
                }
                else if ($fieldName === 'CreatedByCommunityUser' || $fieldName === 'CommunityUser') {
                    $this->addUser($fixtureObject, $fieldNameWithType, $valueToSet);
                }
                else if (is_array($valueToSet)) {
                    $nestedFixturesToAdd[$fieldNameWithType] = $valueToSet;
                }
                else if ($fieldName !== 'fixture' && $fieldName !== 'moderatorType') {
                    $fieldNameArray = explode('.', $fieldNameWithType);
                    ConnectUtil::setFieldValue($fixtureObject, $fieldNameArray, $valueToSet);
                }
            }

            if ($objectMetadata->is_primary) {
                try {
                    // when creating social users, we need to login as that contact in order to have permissions to create that social user
                    if ($fixtureData['type'] === 'CommunityUser') {
                        list($profile, $session, $rawProfile, $rawSession) = Helper::logInUser($fixtureObject->Contact->Login);
                    }
                    $fixtureObject->save();
                    if ($this->verbose) {
                        $this->savedAndDestroyedMessages[] = "saved a " . get_class($fixtureObject) . " (" . $fixtureObject->ID . ")" . ($parameters ? " with parameters " . var_export(array_keys($parameters), true) : "");
                    }
                    Connect\ConnectAPI::commit();
                    if ($fixtureData['type'] === 'CommunityUser') {
                        Helper::logOutUser($rawProfile, $rawSession);
                    }
                }
                catch (\Exception $e) {
                    // if things are so screwy that a save caused an exception (assuming no one would ever write a bad fixture file), prevent destructor from running
                    $this->resetData();
                    if ($this->verbose) {
                        echo "error: while saving " . get_class($fixtureObject) . " " . $e->getMessage() . "<br>\n";
                    }
                    exit("error: while saving " . get_class($fixtureObject) . " " . $e->getMessage());
                }
            }

            $this->addDeferredSubFixtures($fixtureObject, $nestedFixturesToAdd);
            $this->addVariable($fixtureData['type'], $fixtureObject->ID);
            $this->addUserVariable($fixtureObject);

            if ($fixtureData['object']['moderatorType']) {
                 $this->insertModeratorPermissions($fixtureData['object']['moderatorType'], $fixtureObject->ID);
            }

            if ($fixtureData['type'] === 'CommunityQuestion') {
                Decorator::add($fixtureObject, array('class' => "Permission/SocialQuestionPermissions", 'property' => 'SocialPermissions'));
            }

            else if ($fixtureData['type'] === 'CommunityComment') {
                Decorator::add($fixtureObject, array('class' => "Permission/SocialCommentPermissions", 'property' => 'SocialPermissions'));
            }

            else if ($fixtureData['type'] === 'CommunityUser') {
                Decorator::add($fixtureObject, array('class' => "Permission/SocialUserPermissions", 'property' => 'SocialPermissions'));
            }

            return $fixtureObject;
        }
    }

    /**
     * Adds $name and $value to $this->variables.
     * If $name already exists but the $value is different, start appending '_X' to the name where X is a zero-based incrementing integer.
     * @param string $name The name to store with the value. Usually the fixture type (CommunityQuestion, CommunityUser, etc.)
     * @param mixed The value to store with $name. Usually the $fixtureObject->ID
     * @return string|null The variable name ($name or $name_X) if it did not already exist in variables, else null.
     */
    protected function addVariable($name, $value) {
        if (!$this->variableTypes[$name]) {
            $this->variableTypes[$name] = array();
        }

        $variable = null;
        if (!in_array($value, $this->variableTypes[$name])) {
            $count = count($this->variableTypes[$name]);
            if ($count === 0) {
                // Store the first variable name w/out the _X suffix so things like 'CommunityQuestion'
                // where there is likely only one does not need to become 'SocialQuestion_0'
                $variable = $name;
            }
            else if ($count === 1) {
                // Start appending _X suffix
                $firstValue = $this->variables[$name];
                unset($this->variables[$name]);
                $this->variables["{$name}_0"] = $firstValue;
                $variable = "{$name}_1";
            }
            else {
                $variable = "{$name}_{$count}";
            }
            $this->variableTypes[$name][] = $this->variables[$variable] = $value;
        }

        return $variable;
    }

    /**
     * Adds SocialUser_X variables from $fixtureObject
     * @param Object $fixtureObject The object created by the fixture.
     * @return string|null Returns the variable name (e.g. 'user_0') if it did not already exist, otherwise returns null.
     */
    protected function addUserVariable($fixtureObject) {
        if ($user = ($fixtureObject instanceof Connect\CommunityUser) ? $fixtureObject : $fixtureObject->CreatedByCommunityUser) {
            return $this->addVariable('CommunityUser', $user->ID);
        }
    }

    /**
     * Loads sub-fixtures which were deferred until the parent object was saved
     * @param object $fixtureObject Object that is currently being built from the fixture data
     * @param array $toAdd Sub-fixture data to be added
     */
    protected function addDeferredSubFixtures($fixtureObject, array $toAdd) {
        while (list($fieldName, $fixtureData) = each($toAdd)) {
            $fieldNameArray = explode('.', $fieldName);
            $nameChunk = $fieldNameArray[count($fieldNameArray) - 1];

            if (Text::beginsWith($nameChunk, '_')) {
                $this->processDetachedFixtures($fixtureObject, $fixtureData);
            }
            else {
                $this->processAttachedFixtures($fixtureObject, $fieldNameArray, $fixtureData);
            }
        }
    }

    /**
     * Process each sub-fixture to be loaded, but not attached to primary/parent object
     * @param object $fixtureObject Object that is currently being built from the fixture data
     * @param array $toProcess Fixture(s) to be processed
     */
    protected function processDetachedFixtures($fixtureObject, array $toProcess) {
        // single sub-fixture
        if ($toProcess['fixture']) {
            $this->loadPrimaryObjectFixture($fixtureObject, $toProcess);
        }
        // multiple sub-fixtures
        else {
            foreach ($toProcess as $fixture) {
                $this->loadPrimaryObjectFixture($fixtureObject, $fixture);
            }
        }
    }

    /**
     * Process each sub-fixture to be loaded and attached to primary/parent object
     * @param object $fixtureObject Object that is currently being built from the fixture data
     * @param array $fieldNameArray Name pieces of Connect field to set
     * @param array $toProcess Fixture(s) to be processed
     */
    protected function processAttachedFixtures($fixtureObject, array $fieldNameArray, array $toProcess) {
        // shift the prefixed object type into the ether
        array_shift($fieldNameArray);
        $nameChunk = array_shift($fieldNameArray);

        // single sub-fixture
        if ($toProcess['fixture']) {
            $fixtureObject->$nameChunk = $this->loadNonPrimaryObjectFixture($toProcess['fixture']);
        }
        // multiple sub-fixtures
        else {
            foreach ($toProcess as $fixture) {
                $loaded = $this->loadNonPrimaryObjectFixture($fixture['fixture']);

                if ($loaded && $fixtureObject->$nameChunk === null) {
                    // first time through, init the specific connect array type
                    $connectArrayType = get_class($loaded) . 'Array';
                    $fixtureObject->$nameChunk = new $connectArrayType();
                }
                $fixtureObject->{$nameChunk}[] = $loaded;
            }
        }

        if ($fixtureObject->getMetadata()->can_update) {
            try{
                $fixtureObject->save();
            }
            catch (Connect\ConnectAPIError $e) {
                if ($this->verbose) {
                    var_dump($e);
                }
            }
        }
    }

    /**
     * Processes sub-fixture notation and calls make() for the sub-fixture
     * @param object $fixtureObject Object that is currently being built from the fixture data
     * @param array $toLoad Fixture name and any parameters to pass down to sub-fixture
     * @return object Fully loaded sub-fixture object
     */
    protected function loadPrimaryObjectFixture($fixtureObject, array $toLoad) {
        $parameters = array();
        if ($toLoad['parameters']) {
            $parameters = $this->fillParameters($fixtureObject, $toLoad['parameters']);
        }

        return $this->make($toLoad['fixture'], $parameters);
    }

    /**
     * Load in a non-primary object from a fixture file
     * @param string $fixtureName Name of the fixture file to load
     * @return object|null Fully loaded non-primary object or null if the fixture doesn't specify a type.
     */
    protected function loadNonPrimaryObjectFixture($fixtureName) {
        $loaded = $this->loadFixtureFromFile($fixtureName);

        if (!$loaded['type']) return;

        $type = CONNECT_NAMESPACE_PREFIX . '\\' . $loaded['type'];
        $nonPrimaryObject = new $type();
        while (list($fieldName, $fieldValue) = each($loaded['object'])) {
            if (is_array($fieldValue) && $fieldValue['fixture'] && property_exists($nonPrimaryObject, $fieldName)) {
                $nonPrimaryObject->$fieldName = $this->make($fieldValue['fixture']);
            }
            else if (is_string($fieldValue) && ($fieldName === 'CreatedByCommunityUser' || $fieldName === 'CommunityUser')) {
                $nonPrimaryObject->$fieldName = $this->getSocialUser($fieldValue);
            }
            else {
                $fieldName = str_replace('.', '->', $fieldName);
                eval("\$nonPrimaryObject->$fieldName = \$fieldValue;");
            }
        }

        return $nonPrimaryObject;
    }

    /**
     * Handles loading of user sub-fixtures (CommunityUser and Contact).  Also supports using seed users
     * with a 'databaseLogin:' prefix on the name of the user fixture.
     * @param object $fixtureObject Object that is currently being built from the fixture data
     * @param string $fieldName Name of field to add the user at on the parent fixture
     * @param mixed $toAdd User fixture name and any parameters to pass down
     */
    protected function addUser(&$fixtureObject, $fieldName, $toAdd) {
        \RightNow\Libraries\AbuseDetection::check();

        $fieldNameArray = explode('.', $fieldName);

        if (is_string($toAdd)) {
            $loadedUser = $this->getSocialUser($toAdd);
        }
        else {
            $loadedUser = $this->loadPrimaryObjectFixture($fixtureObject, $toAdd);
        }

        if ($loadedUser->ID) {
            ConnectUtil::setFieldValue($fixtureObject, $fieldNameArray, $loadedUser->ID);
        }
    }

    /**
     * Fills out a parameter array with appropriate data from parent object
     * @param object $fixtureObject Object that is currently being built from the fixture data
     * @param array $parameters Parameters to pass down to sub-fixture, as specified in fixture file
     * @return array Fully filled out parameter array
     */
    protected function fillParameters($fixtureObject, array $parameters) {
        $filledParameters = array();

        while (list($parameterField, $fieldValue) = each($parameters)) {
            $fieldFromFixtureObject = Text::getSubstringAfter($fieldValue, 'this.');
            if (property_exists($fixtureObject, $fieldFromFixtureObject)) {
                $filledParameters[$parameterField] = $fixtureObject->$fieldFromFixtureObject;
            }
            else {
                $filledParameters[$parameterField] = $fieldValue;
            }
        }

        return $filledParameters;
    }

    /**
     * Handles the loading of the yaml file from the filesystem
     * @param string $fixtureName Name of the fixture to be loaded
     * @return array Parsed data from the yaml file
     */
    protected function loadFixtureFromFile($fixtureName) {
        if (!$fixtureName) return;

        $filepath = CPCORE . "Controllers/UnitTest/fixtures/{$fixtureName}.yml";
        $fixtureData = array();

        if (FileSystem::isReadableFile($filepath)) {
            $fixtureData = yaml_parse_file($filepath);
        }
        else {
            $this->errors []= "Fixture file at $filepath inaccessible";
        }

        return $fixtureData;
    }

    /**
     * Inserts moderator-specific permissions
     * @param string $type     type of moderator
     * @param int    $socialID Social User ID
     */
    private function insertModeratorPermissions($type, $socialID) {
        if ($type && $socialID) {
            $types = array(
                'moderator'        => '(%1$d, 5)',
                'admin'            => '(%1$d, 5),(%1$d, 6)',
                'usermoderator'    => '(%1$d, 100002)',
                'contentmoderator' => '(%1$d, 100003)',
            );

            if ($types[$type]) {
                \RightNow\Api::test_sql_exec_direct(sprintf('INSERT INTO common_user2role_sets VALUES ' . $types[$type], $socialID));
            }
        }
    }

    /**
     * Reset fields
     */
    private function resetData() {
        $this->loadedFixtures = $this->variables = $this->variableTypes = array();

        static $databaseContacts = array(
            'useractive1', 'useractive2', 'modactive1', 'modactive2', 'usermoderator', 'contentmoderator',
            'userarchive1', 'userpending', 'usersuspended', 'userdeleted',
            'modarchive', 'modpending', 'modsuspended', 'moddeleted',
            'useradmin', 'usersuspended2', 'userupdateonly', 'userdeletenoupdatestatus',
        );

        foreach ($databaseContacts as $databaseContact) {
            if ($socialUser = $this->getSocialUser($databaseContact)) {
                $this->addVariable('DatabaseSocialUser', $socialUser->ID);
            }
        }
    }

    /**
     * Get CommunityUser, given a login
     * @param string $login Login of the contact
     * @return Connect\CommunityUser|null CommunityUser associated to the contact with the given login
     */
    private function getSocialUser($login) {
        $contact = Connect\Contact::first("Login = '$login'");
        if ($contact && $contact->CommunityUser)
            return $contact->CommunityUser;
    }
}
