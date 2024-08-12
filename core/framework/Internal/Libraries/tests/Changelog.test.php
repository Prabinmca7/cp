<?php
use RightNow\Internal\Libraries\Changelog,
    RightNow\Internal\Libraries\ChangelogException;

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class ChangelogTest extends CPTestCase {
    public $testingClass = 'RightNow\Internal\Libraries\Changelog';
    function __construct() {
        $this->widget = 'standard/input/DateInput';
        $this->frameworkChangelog = CPCORE . 'changelog.yml';
        $this->widgetDir = dirname(CPCORE) . '/widgets/standard/';

        // Entries as returned from QA report #168906 via ci/admin/internalTools/addNewChangelogEntries
        $this->entries = array(
          array (
            'refno' => '031208-000016',
            'release' => '12.11',
            'incident_id' => 21205,
            'subject' => 'Html entities dropped during parsing',
            'created_time' => '09/20/2012',
            'created_by_account' => 'Bob Dole',
            'last_updated' => '09/20/2012',
            'updated_by_account' => 'Bob Law',
            'email' => 'blaw@oracle.com',
            'account' => 'blaw',
            'changelog_id' => 9,
            'description' => 'Added new \'foo\' attribute',
            'level' => 'Minor',
            'category' => 'Bug Fix',
            'sort_position' => 'Middle',
            'targets' => 'framework',
            'details' => '- Affects users using custom models
        - May need to adjust styling',
          ),
          array (
            'refno' => '031208-000016',
            'release' => '12.11 (Nov 12)',
            'incident_id' => 21205,
            'subject' => 'Html entities dropped during parsing',
            'created_time' => '09/20/2012',
            'created_by_account' => 'Bob Dylan',
            'last_updated' => '09/20/2012',
            'updated_by_account' => 'Bob White',
            'email' => 'bwhite@oracle.com',
            'account' => 'bwhite',
            'changelog_id' => 10,
            'description' => 'Got rid of some whitespace',
            'level' => 'Nano',
            'category' => 'Bug Fix',
            'sort_position' => 'Middle',
            'targets' => 'input/FormInput
        input/TextInput',
            'details' => '- Should affect no one',
          ),
          array (
            'refno' => '031208-000016',
            'incident_id' => 21205,
            'release' => '12.11 (Nov 12)',
            'subject' => 'Html entities dropped during parsing',
            'created_time' => '09/20/2012',
            'created_by_account' => 'Jack White',
            'last_updated' => '09/20/2012',
            'updated_by_account' => 'Jack Black',
            'email' => 'jblack@oracle.com',
            'account' => 'jblack',
            'changelog_id' => 11,
            'description' => 'Migrated to Connect v11',
            'level' => 'Major',
            'category' => 'Bug Fix',
            'sort_position' => 'Middle',
            'targets' => 'framework',
            'details' => '',
          ),
          array (
            'refno' => '031208-000016',
            'incident_id' => 21205,
            'release' => '12.11 (Nov 12)',
            'subject' => 'Html entities dropped during parsing',
            'created_time' => '09/20/2012',
            'created_by_account' => 'John Doe',
            'last_updated' => '09/20/2012',
            'updated_by_account' => 'Jane Doe',
            'email' => 'jdoe@oracle.com',
            'account' => 'jdoe',
            'changelog_id' => 13,
            'description' => 'Got rid of some whitespace',
            'level' => 'Nano',
            'category' => 'Bug Fix',
            'sort_position' => 'Middle',
            'targets' => 'input/FormInput
        input/TextInput',
            'details' => '- Should affect no one',
          ),
          array (
            'refno' => '031208-000016',
            'incident_id' => 21205,
            'release' => '12.11 (Nov 12)',
            'subject' => 'Html entities dropped during parsing',
            'created_time' => '09/21/2012',
            'created_by_account' => 'John Doe',
            'last_updated' => '',
            'updated_by_account' => '',
            'email' => '',
            'account' => '',
            'changelog_id' => 13,
            'description' => 'Added back the whitespace',
            'level' => 'Nano',
            'category' => 'Bug Fix',
            'sort_position' => 'Middle',
            'targets' => 'input/FormInput
        input/TextInput',
            'details' => '- Should still affect no one',
          ),
          array (
            'refno' => '031208-000016',
            'incident_id' => 21205,
            'release' => '13.2 (Feb 13)',
            'subject' => 'Removed X',
            'created_time' => '',
            'created_by_account' => '',
            'last_updated' => '',
            'updated_by_account' => '',
            'email' => '',
            'account' => '',
            'changelog_id' => 14,
            'description' => 'Removed X',
            'level' => 'Major',
            'category' => 'Deprecation',
            'sort_position' => 'Top',
            'targets' => 'framework',
            'details' => '- Replaced it with Y',
          ),
          array (
            'refno' => '031208-000017',
            'incident_id' => 21206,
            'release' => '12.11 (Nov 12)',
            'subject' => 'Two changelogs on the same incident for the same widget [1 of 2]',
            'created_time' => '09/21/2012',
            'created_by_account' => 'John Doe',
            'last_updated' => '',
            'updated_by_account' => '',
            'email' => '',
            'account' => '',
            'changelog_id' => 15,
            'description' => 'blah blah',
            'level' => 'Nano',
            'category' => 'Bug Fix',
            'sort_position' => 'Middle',
            'targets' => 'input/TextInput',
            'details' => '- Should not be a problem',
          ),
          array (
            'refno' => '031208-000017',
            'incident_id' => 21206,
            'release' => '12.11 (Nov 12)',
            'subject' => 'Two changelogs on the same incident for the same widget [2 of 2]',
            'created_time' => '09/21/2012',
            'created_by_account' => 'John Doe',
            'last_updated' => '',
            'updated_by_account' => '',
            'email' => '',
            'account' => '',
            'changelog_id' => 16,
            'description' => 'yada yada',
            'level' => 'Nano',
            'category' => 'Bug Fix',
            'sort_position' => 'Middle',
            'targets' => 'input/TextInput
                          framework',
            'details' => '- Should still not be a problem',
          ),
        );

        // EXPECTED
        $this->expected = array(
          array (
            'refno' => '031208-000016',
            'changelogID' => 9,
            'release' => '12.11',
            'account' => 'blaw',
            'email' => 'blaw@oracle.com',
            'date' => '09/20/2012',
            'description' => 'Added new \'foo\' attribute',
            'level' => 'minor',
            'category' => 'Bug Fix',
            'sortPosition' => 'Middle',
            'targets' =>
            array ('framework'),
            'details' =>
            array (
                'Affects users using custom models',
                'May need to adjust styling',
            ),
          ),
          array (
            'refno' => '031208-000016',
            'changelogID' => 10,
            'release' => '12.11',
            'account' => 'bwhite',
            'email' => 'bwhite@oracle.com',
            'date' => '09/20/2012',
            'description' => 'Got rid of some whitespace',
            'level' => 'nano',
            'category' => 'Bug Fix',
            'sortPosition' => 'Middle',
            'targets' =>
            array (
                'input/FormInput',
                'input/TextInput',
            ),
            'details' =>
            array ('Should affect no one'),
          ),
          array (
            'refno' => '031208-000016',
            'changelogID' => 11,
            'release' => '12.11',
            'account' => 'jblack',
            'email' => 'jblack@oracle.com',
            'date' => '09/20/2012',
            'description' => 'Migrated to Connect v11',
            'level' => 'major',
            'category' => 'Bug Fix',
            'sortPosition' => 'Middle',
            'targets' =>
            array ('framework'),
          ),
          array (
            'refno' => '031208-000016',
            'changelogID' => 13,
            'release' => '12.11',
            'account' => 'jdoe',
            'email' => 'jdoe@oracle.com',
            'date' => '09/20/2012',
            'description' => 'Got rid of some whitespace',
            'level' => 'nano',
            'category' => 'Bug Fix',
            'sortPosition' => 'Middle',
            'targets' =>
            array (
              'input/FormInput',
              'input/TextInput',
            ),
            'details' =>
            array ('Should affect no one'),
          ),
          array (
            'refno' => '031208-000016',
            'changelogID' => 13,
            'release' => '12.11',
            'account' => '',
            'email' => '',
            'date' => '09/21/2012',
            'description' => 'Added back the whitespace',
            'level' => 'nano',
            'category' => 'Bug Fix',
            'sortPosition' => 'Middle',
            'targets' =>
            array (
              'input/FormInput',
              'input/TextInput',
            ),
            'details' =>
            array ('Should still affect no one'),
          ),
          array (
            'refno' => '031208-000016',
            'changelogID' => 14,
            'release' => '13.2',
            'account' => '',
            'email' => '',
            'date' => '',
            'description' => 'Removed X',
            'level' => 'major',
            'category' => 'Deprecation',
            'sortPosition' => 'Top',
            'targets' => array ('framework'),
            'details' => array ('Replaced it with Y'),
          ),
          array (
            'refno' => '031208-000017',
            'changelogID' => 15,
            'release' => '12.11',
            'account' => '',
            'email' => '',
            'date' => '09/21/2012',
            'description' => 'blah blah',
            'level' => 'nano',
            'category' => 'Bug Fix',
            'sortPosition' => 'Middle',
            'targets' => array('input/TextInput'),
            'details' => array('Should not be a problem'),
          ),
          array (
            'refno' => '031208-000017',
            'changelogID' => 16,
            'release' => '12.11',
            'account' => '',
            'email' => '',
            'date' => '09/21/2012',
            'description' => 'yada yada',
            'level' => 'nano',
            'category' => 'Bug Fix',
            'sortPosition' => 'Middle',
            'targets' => array('input/TextInput', 'framework'),
            'details' => array('Should still not be a problem'),
          ),
        );
    }

    function __setup() {
        $this->clearCache('framework');
    }

    function clearCache($target) {
        $clear = $this->getStaticMethod('clearCache');
        return $clear($target);
    }

    /**
     * This test needs to run first to ensure the cache is being properly initialized
     */
    function testCache() {
        $fetch = $this->getStaticMethod('getChangelogFromCache');
        $path = CPCORE . 'changelog.yml';
        $target = 'framework';
        $version = '3.0.1';
        $this->clearCache("{$target}.{$version}");
        $results = $fetch($path, $target);
        $this->assertIsA($results, 'array');
        $results= $fetch($path, $target, $version);
        $this->assertIdentical($results, $fetch($path, $target, $version));
        $this->assertIsA($fetch($path, $target, $version, $version), 'array');

        $this->assertTrue($this->clearCache($target));
        $this->assertFalse($this->clearCache($target));
    }

    function testGetChangelog() {
        try {
            $this->assertNull(Changelog::getChangelog('foo'));
            $this->fail('Expected exception did not occur');
        }
        catch (\Exception $e) {
            $this->assertIdentical('INVALID_TARGET', $e->getKey());
        }

        $yaml = Changelog::getChangelog('framework');
        $array = yaml_parse($yaml);
        $info = $array['3.0.1'];
        $this->assertNotNull($info['release']);
        $this->assertNotNull($info['entries'][0]['level']);

        $yaml = Changelog::getChangelog($this->widget);
        $array = yaml_parse($yaml);
        $info = $array['1.0.1'];
        $this->assertNotNull($info['release']);
        $this->assertNotNull($info['entries'][0]['level']);
    }

    function testArrayToYaml() {
        $arrayToYaml = $this->getStaticMethod('arrayToYaml');
        $changelog = array('1.0.1' => array(
            'release' => '12.11',
            'entries' => array(array(
                'date' => '10/16/2012',
                'level' => 'major',
                'description' => 'Initial version',
            )),
        ));
        $yaml = $arrayToYaml($changelog);
        // No leading spaces
        $this->assertSame('1.0.1', substr($yaml, 0, 5));
        // Parses back to original array
        $this->assertIdentical($changelog, yaml_parse($yaml));
    }

    function testGetChangelogAsArray() {
        try {
            $this->assertNull(Changelog::getChangelogAsArray('foo'));
            $this->fail('Expected exception did not occur');
        }
        catch (\Exception $e) {
            $this->assertIdentical('INVALID_TARGET', $e->getKey());
        }

        $array = Changelog::getChangelogAsArray($this->widget);
        $info = $array['1.0.1'];
        $this->assertNotNull($info['release']);
        $this->assertNotNull($info['entries'][0]['level']);

        $array = Changelog::getChangelogAsArray($this->widget, '1.0.1');
        $info = $array['1.0.1'];
        $this->assertNotNull($info['release']);
        $this->assertNotNull($info['entries'][0]['level']);

        $array = Changelog::getChangelogAsArray('framework');
        $info = $array['3.0.1'];
        $this->assertNotNull($info['release']);
        $this->assertNotNull($info['entries'][0]['level']);
    }

    function testAddEntry() {
        // EMPTY ENTRIES
        try {
            Changelog::addEntry('framework', array());
            $this->fail();
        }
        catch (\Exception $e) {
            $this->assertEqual('REQUIRED_KEY_MISSING', $e->getKey());
        }

        $entry = array(
            'description' => 'Lorem ipsum dolor sit amet',
            'level' => 'minor',
            'details' => array(
                'consectetur adipisicing elit sed do eiusmod',
                'tempor incididunt ut labore et dolore magna aliqua.',
                'Ut enim ad minim veniam quis nostrud exercitation',
            ),
        );

        try {
            Changelog::addEntry('foo', $entry);
            $this->fail();
        }
        catch (\Exception $e) {
            $this->assertEqual('INVALID_TARGET', $e->getKey());
        }

        $this->assertEqual($this->frameworkChangelog, Changelog::addEntry('framework', $entry, false));
        $this->assertEqual($this->frameworkChangelog, Changelog::addEntry("'framework'", $entry, false));
        $this->assertEqual($this->frameworkChangelog, Changelog::addEntry('"framework"', $entry, false));
        $this->assertEqual($this->frameworkChangelog, Changelog::addEntry('framework,', $entry, false));
        $this->assertEqual($this->frameworkChangelog, Changelog::addEntry('framework, ', $entry, false));
        $this->assertEqual($this->frameworkChangelog, Changelog::addEntry('framework , ', $entry, false));
    }

    function testParseDetails() {
        $parseDetails = $this->getStaticMethod('parseDetails');

        $input = "
        - All on one line
        -No space after hyphen.
        - Contains inner-hyphen character.
        ";
        $expected = array(
            'All on one line',
            'No space after hyphen.',
            'Contains inner-hyphen character.',
        );
        $this->assertIdentical($expected, $parseDetails($input));

        $input = "
        First line does not contain a hyphen
        - Second line.
        ";
        $expected = array(
            'First line does not contain a hyphen',
            'Second line.',
        );
        $this->assertIdentical($expected, $parseDetails($input));

        $input = "Line one.\nLine two.";
        $this->assertIdentical(array('Line one.', 'Line two.'), $parseDetails($input));
    }

    function testMergeEntry() {
        $mergeEntry = $this->getStaticMethod('mergeEntry');

        // First entry after initial population. Should add new incremented version key
        $changelog = array(
            '3.0.1' => array(
                'release' => '12.8',
                'entries' => array(
                    array(
                        'date' => '01/24/2012',
                        'level' => 'major',
                        'description' => 'Initial version',
                    ),
                ),
            ),
        );

        $entry = array(
            'description' => 'Lorem ipsum dolor sit amet',
            'level' => 'minor',
            'details' => array(
                'consectetur adipisicing elit sed do eiusmod',
                'tempor incididunt ut labore et dolore magna aliqua.',
                'Ut enim ad minim veniam quis nostrud exercitation',
            ),
        );

        $expected = array(
            '3.1.1' => array(
                'release' => '',
                'entries' => array(
                    array(
                        'description' => 'Lorem ipsum dolor sit amet',
                        'level' => 'minor',
                        'details' => array(
                            'consectetur adipisicing elit sed do eiusmod',
                            'tempor incididunt ut labore et dolore magna aliqua.',
                            'Ut enim ad minim veniam quis nostrud exercitation',
                        ),
                    ),
                ),
            ),
            '3.0.1' => array(
                'release' => '12.8',
                'entries' => array(
                    array(
                        'date' => '01/24/2012',
                        'level' => 'major',
                        'description' => 'Initial version',
                    ),
                ),
            ),
        );

        $actual = $mergeEntry($entry, $changelog);
        $this->assertIdentical($expected, $actual);

        // Subsequent entry. Should increment existing version (since release not specified)
        $changelog = $expected;
        $expected = array(
            '3.1.1' => array(
                'release' => '',
                'entries' => array(
                    array(
                        'description' => 'Lorem ipsum dolor sit amet',
                        'level' => 'minor',
                        'details' => array(
                            'consectetur adipisicing elit sed do eiusmod',
                            'tempor incididunt ut labore et dolore magna aliqua.',
                            'Ut enim ad minim veniam quis nostrud exercitation',
                        ),
                    ),
                    array(
                        'description' => 'Lorem ipsum dolor sit amet',
                        'level' => 'minor',
                        'details' => array(
                            'consectetur adipisicing elit sed do eiusmod',
                            'tempor incididunt ut labore et dolore magna aliqua.',
                            'Ut enim ad minim veniam quis nostrud exercitation',
                        ),
                    ),
                ),
            ),
            '3.0.1' => array(
                'release' => '12.8',
                'entries' => array(
                    array(
                        'date' => '01/24/2012',
                        'level' => 'major',
                        'description' => 'Initial version',
                    ),
                ),
            ),
        );

        $actual = $mergeEntry($entry, $changelog);
        $this->assertIdentical($expected, $actual);

        // Should add yet another version key
        $changelog = array(
            '3.1.1' => array(
                'release' => '12.11',
                'entries' => array(
                    array(
                        'description' => 'Lorem ipsum dolor sit amet',
                        'level' => 'minor',
                        'details' => array(
                            'consectetur adipisicing elit sed do eiusmod',
                            'tempor incididunt ut labore et dolore magna aliqua.',
                            'Ut enim ad minim veniam quis nostrud exercitation',
                        ),
                    ),
                    array(
                        'description' => 'Lorem ipsum dolor sit amet',
                        'level' => 'minor',
                        'details' => array(
                            'consectetur adipisicing elit sed do eiusmod',
                            'tempor incididunt ut labore et dolore magna aliqua.',
                            'Ut enim ad minim veniam quis nostrud exercitation',
                        ),
                    ),
                ),
            ),
            '3.0.1' => array(
                'release' => '12.8',
                'entries' => array(
                    array(
                        'date' => '01/24/2012',
                        'level' => 'major',
                        'description' => 'Initial version',
                    ),
                ),
            ),
        );

        $expected = array (
          '3.2.1' =>
          array (
            'release' => '',
            'entries' =>
            array (
              0 =>
              array (
                'description' => 'Lorem ipsum dolor sit amet',
                'level' => 'minor',
                'details' =>
                array (
                  0 => 'consectetur adipisicing elit sed do eiusmod',
                  1 => 'tempor incididunt ut labore et dolore magna aliqua.',
                  2 => 'Ut enim ad minim veniam quis nostrud exercitation',
                ),
              ),
            ),
          ),
          '3.1.1' =>
          array (
            'release' => '12.11',
            'entries' =>
            array (
              0 =>
              array (
                'description' => 'Lorem ipsum dolor sit amet',
                'level' => 'minor',
                'details' =>
                array (
                  0 => 'consectetur adipisicing elit sed do eiusmod',
                  1 => 'tempor incididunt ut labore et dolore magna aliqua.',
                  2 => 'Ut enim ad minim veniam quis nostrud exercitation',
                ),
              ),
              1 =>
              array (
                'description' => 'Lorem ipsum dolor sit amet',
                'level' => 'minor',
                'details' =>
                array (
                  0 => 'consectetur adipisicing elit sed do eiusmod',
                  1 => 'tempor incididunt ut labore et dolore magna aliqua.',
                  2 => 'Ut enim ad minim veniam quis nostrud exercitation',
                ),
              ),
            ),
          ),
          '3.0.1' =>
          array (
            'release' => '12.8',
            'entries' =>
            array (
              0 =>
              array (
                'date' => '01/24/2012',
                'level' => 'major',
                'description' => 'Initial version',
              ),
            ),
          ),
        );

        $actual = $mergeEntry($entry, $changelog);
        $this->assertIdentical($expected, $actual);
    }

    function getEntry($data = array()) {
        $entry =  array(
            'refno' => $data['refno'] ?: '120924-000001',
            'changelogID' => $data['changelogID'],
            'description' => $data['description'] ?: "Added new 'foo' feature",
            'level' => $data['level'] ?: 'nano',
            'category' => $data['category'] ?: 'Bug Fix',
            'date' => $data['date'] ?: '04/19/2013',
            'sortPosition' => $data['sortPosition'] ?: 'Middle',
            'details' => $data['details'] ?: array(
                'Add this new attribute for extra foo.',
                'May need to adjust styling.'
            ),
        );
        if ($release = $data['release']) {
            $entry['release'] = $release;
        }
        return $entry;
    }

    function testMergeEntryWithReleaseSpecified() {
        $mergeEntry = $this->getStaticMethod('mergeEntry');

        // An entry with a specified release should result in a new version entry with that release.
        $changelog = array(
            '3.0.1' => array(
                'release' => '12.8',
                'entries' => array(
                    $this->getEntry(),
                ),
            ),
        );

        $entry = $this->getEntry(array('release' => '13.2'));

        $expected = array(
            '3.0.2' => array(
                'release' => '13.2',
                'entries' => array(
                    $this->getEntry(),
                ),
            ),
            '3.0.1' => array(
                'release' => '12.8',
                'entries' => array(
                    $this->getEntry(),
                ),
            ),
        );

        $actual = $mergeEntry($entry, $changelog);
        $this->assertIdentical($expected, $actual);

        // An entry where the release is specified should be recorded in an existing version entry having the same release
        $changelog = array(
            '3.0.2' => array(
                'release' => '13.2',
                'entries' => array(
                    $this->getEntry(),
                ),
            ),
            '3.0.1' => array(
                'release' => '12.8',
                'entries' => array(
                    $this->getEntry(),
                ),
            ),
        );

        $entry = $this->getEntry(array('release' => '13.2'));

        $expected = array(
            '3.0.2' => array(
                'release' => '13.2',
                'entries' => array(
                    $this->getEntry(),
                    $this->getEntry(),
                ),
            ),
            '3.0.1' => array(
                'release' => '12.8',
                'entries' => array(
                    $this->getEntry(),
                ),
            ),
        );

        $actual = $mergeEntry($entry, $changelog);
        $this->assertIdentical($expected, $actual);

        // An entry not specifying a release should create a new version key
        $changelog = array(
            '3.0.2' => array(
                'release' => '13.2',
                'entries' => array(
                    $this->getEntry(),
                    $this->getEntry(),
                ),
            ),
            '3.0.1' => array(
                'release' => '12.8',
                'entries' => array(
                    $this->getEntry(),
                ),
            ),
        );

        $entry = $this->getEntry(array('level' => 'minor', 'description' => 'something else'));

        $expected = array(
            '3.1.1' => array(
                'release' => '',
                'entries' => array(
                    $entry,
                ),
            ),
            '3.0.2' => array(
                'release' => '13.2',
                'entries' => array(
                    $this->getEntry(),
                    $this->getEntry(),
                ),
            ),
            '3.0.1' => array(
                'release' => '12.8',
                'entries' => array(
                    $this->getEntry(),
                ),
            ),
        );

        $actual = $mergeEntry($entry, $changelog);
        $this->assertIdentical($expected, $actual);
    }

    function testMergeEntryWhereReleaseExistsInPriorVersion() {
        $mergeEntry = $this->getStaticMethod('mergeEntry');

        $changelog = array(
            '3.1.2' => array(
                'release' => '13.8',
                'entries' => array(
                    $this->getEntry(),
                ),
            ),
            '3.1.1' => array(
                'release' => '13.5',
                'entries' => array(
                    $this->getEntry(),
                ),
              ),
            '3.0.2' => array(
                'release' => '13.2',
                'entries' => array(
                    $this->getEntry(),
                ),
              ),
            '3.0.1' => array(
                'release' => '12.11',
                'entries' => array(
                    $this->getEntry(),
                ),
            ),
        );

        $entry = $expectedEntry = $this->getEntry(array(
            'release' => '13.5',
            'description' => 'This should get inserted into the existing 3.1.1 section as it is in the 13.5 release.',
            'level' => 'minor',
            'changelogID' => '123',
        ));

        unset($expectedEntry['release']);

        $expected = array(
            '3.1.2' => array(
                'release' => '13.8',
                'entries' => array(
                    $this->getEntry(),
                ),
            ),
            '3.1.1' => array(
                'release' => '13.5',
                'entries' => array(
                    $expectedEntry,
                    $this->getEntry(),
                ),
              ),
            '3.0.2' => array(
                'release' => '13.2',
                'entries' => array(
                    $this->getEntry(),
                ),
              ),
            '3.0.1' => array(
                'release' => '12.11',
                'entries' => array(
                    $this->getEntry(),
                ),
            ),
        );

        $actual = $mergeEntry($entry, $changelog);
        $this->assertIdentical($expected, $actual);
    }

    function testMergeEntryWhereReleaseExistsInCurrentVersion() {
        $mergeEntry = $this->getStaticMethod('mergeEntry');

        $changelog = array(
            '3.1.2' => array(
                'release' => '13.8',
                'entries' => array(
                    $this->getEntry(array(
                        'level' => 'nano',
                        'description' => 'I should get moved to 3.2.1'
                    )),
                ),
            ),
            '3.1.1' => array(
                'release' => '13.5',
                'entries' => array(
                    $this->getEntry(),
                ),
            ),
        );

        $entry = $expectedEntry = $this->getEntry(array(
            'release' => '13.8',
            'description' => 'This should get inserted into a new 3.2.1 section for 13.8 release.',
            'level' => 'minor',
            'changelogID' => '123',
        ));

        unset($expectedEntry['release']);

        $expected = array(
            '3.2.1' => array(
                'release' => '13.8',
                'entries' => array(
                    $expectedEntry,
                    $this->getEntry(array(
                        'level' => 'nano',
                        'description' => 'I should get moved to 3.2.1'
                    )),
                ),
            ),
            '3.1.1' => array(
                'release' => '13.5',
                'entries' => array(
                    $this->getEntry(),
                ),
            ),
        );

        $actual = $mergeEntry($entry, $changelog);
        $this->assertIdentical($expected, $actual);
    }


    function testValidateEntry() {
        $validateEntry = $this->getStaticMethod('validateEntry');
        $entry = $validateEntry(array('level' => 'nano', 'description' => 'Lorem ipsum dolor sit amet'));
        $this->assertTrue(array_key_exists('date', $entry));

        // Missing level
        try {
            $validateEntry(array('description' => 'Lorem ipsum dolor sit amet'));
            $this->fail();
        }
        catch (\Exception $e) {
            $this->assertIdentical('REQUIRED_KEY_MISSING', $e->getKey());
        }

        // Invalid level
        try {
            $validateEntry(array('level' => 'foo', 'description' => 'Lorem ipsum dolor sit amet'));
            $this->fail();
        }
        catch (\Exception $e) {
            $this->assertIdentical('INVALID_LEVEL', $e->getKey());
        }

        // Missing description
        try {
            $validateEntry(array('level' => 'major'));
            $this->fail();
        }
        catch (\Exception $e) {
            $this->assertIdentical('REQUIRED_KEY_MISSING', $e->getKey());
        }
    }

    function testCalculateNextVersion() {
        $calculateNextVersion = $this->getStaticMethod('calculateNextVersion');

        // Highest version already designated to a release. Create a new incremented version entry.
        $versions = array('3.0.1' => '12.8');
        $this->assertIdentical(array('4.0.1', null), $calculateNextVersion('major', $versions));
        $this->assertIdentical(array('3.1.1', null), $calculateNextVersion('minor', $versions));
        $this->assertIdentical(array('3.0.2', null), $calculateNextVersion('nano', $versions));

        // Highest version not yet designated to a release. Overwrite with incremented version.
        $versions = array('3.0.1' => null);
        $this->assertIdentical(array('4.0.1', '3.0.1'), $calculateNextVersion('major', $versions));
        $this->assertIdentical(array('3.1.1', '3.0.1'), $calculateNextVersion('minor', $versions));
        $this->assertIdentical(array('3.0.2', '3.0.1'), $calculateNextVersion('nano', $versions));

        // Highest version not yet designated to a release, and already incremented. Keep existing version.
        $versions = array('4.0.1' => null, '3.0.1' => '12.8');
        $this->assertIdentical(array('4.0.1', '4.0.1'), $calculateNextVersion('major', $versions));
        $this->assertIdentical(array('4.0.1', '4.0.1'), $calculateNextVersion('minor', $versions));
        $this->assertIdentical(array('4.0.1', '4.0.1'), $calculateNextVersion('nano', $versions));

        $versions = array('3.1.2' => '13.8', '3.1.1' => '13.5', '3.0.2' => '13.2', '3.0.1' => '12.11');
        // Release exists in prior version
        $this->assertIdentical(array('3.1.1', null), $calculateNextVersion('major', $versions, '13.5'));
        $this->assertIdentical(array('3.1.1', null), $calculateNextVersion('minor', $versions, '13.5'));
        $this->assertIdentical(array('3.1.1', null), $calculateNextVersion('nano', $versions, '13.5'));

        // Release exists in current version
        $this->assertIdentical(array('4.0.1', '3.1.2'), $calculateNextVersion('major', $versions, '13.8'));
        $this->assertIdentical(array('3.2.1', '3.1.2'), $calculateNextVersion('minor', $versions, '13.8'));
        $this->assertIdentical(array('3.1.2', '3.1.2'), $calculateNextVersion('nano', $versions, '13.8'));

        // Release not specified
        $this->assertIdentical(array('4.0.1', null), $calculateNextVersion('major', $versions));
        $this->assertIdentical(array('3.2.1', null), $calculateNextVersion('minor', $versions));
        $this->assertIdentical(array('3.1.3', null), $calculateNextVersion('nano', $versions));

        // Release specified, but does not exist in prior version
        $this->assertIdentical(array('4.0.1', null), $calculateNextVersion('major', $versions, '13.12'));
        $this->assertIdentical(array('3.2.1', null), $calculateNextVersion('minor', $versions, '13.12'));
        $this->assertIdentical(array('3.1.3', null), $calculateNextVersion('nano', $versions, '13.12'));
    }

    function testVersionAlreadyIncremented() {
        $versionAlreadyIncremented = $this->getStaticMethod('versionAlreadyIncremented');
        $this->assertTrue($versionAlreadyIncremented('3.0.1', '3.0.2', 'nano'));
        $this->assertTrue($versionAlreadyIncremented('3.0.1', '4.0.1', 'major'));
        $this->assertTrue($versionAlreadyIncremented('3.0.1', '4.0.1', 'minor'));
        $this->assertTrue($versionAlreadyIncremented('3.0.1', '4.0.1', 'nano'));
        $this->assertFalse($versionAlreadyIncremented(null, '3.0.1', 'nano'));
        $this->assertFalse($versionAlreadyIncremented(null, '3.0.1', 'major'));
        $this->assertFalse($versionAlreadyIncremented(null, '3.0.1', 'minor'));
        $this->assertFalse($versionAlreadyIncremented('3.0.1', '3.0.1', 'nano'));
        $this->assertTrue($versionAlreadyIncremented('3.1.1', '3.1.2', 'nano'));
    }

    function testGetChangelogPath() {
        $getChangelogPath = $this->getStaticMethod('getChangelogPath');

        $actual = $getChangelogPath('framework');
        $this->assertIdentical('framework', $actual[0]);
        $this->assertEqual($this->frameworkChangelog, $actual[1]);

        $actual = $getChangelogPath('standard/input/DateInput');
        $this->assertIdentical('standard/input/DateInput', $actual[0]);
        $this->assertEqual($this->widgetDir . 'input/DateInput/changelog.yml', $actual[1]);

        $actual = $getChangelogPath('input/DateInput');
        $this->assertIdentical('standard/input/DateInput', $actual[0]);
        $this->assertEqual($this->widgetDir . 'input/DateInput/changelog.yml', $actual[1]);

        try {
            $getChangelogPath(null);
            $this->fail();
        }
        catch (\Exception $e) {
            $this->assertIdentical('INVALID_TARGET', $e->getKey());
        }
    }

    function testIncrementVersion() {
        $incrementVersion = $this->getStaticMethod('incrementVersion');
        $this->assertIdentical('3.0.2', $incrementVersion('3.0.1', 'nano'));
        $this->assertIdentical('3.1.1', $incrementVersion('3.0.2', 'minor'));
        $this->assertIdentical('3.1.1', $incrementVersion('3.0.1', 'minor'));
        $this->assertIdentical('4.0.1', $incrementVersion('3.0.1', 'major'));
        $this->assertIdentical('4.0.1', $incrementVersion('3.1.2', 'major'));
    }

    function testFilterChangelog() {
        $changelog = $this->getChangelogArray();
        $filterChangelog = $this->getStaticMethod('filterChangelog');
        $filtered = $filterChangelog($changelog, '1.0', '1.0');
        $this->assertTrue(array_key_exists('1.0.1', $filtered));
        $this->assertTrue(array_key_exists('1.0.2', $filtered));
        $this->assertFalse(array_key_exists('1.1.1', $filtered));
    }

    function testFilterVersions() {
        $filterVersions = $this->getStaticMethod('filterVersions');
        $versions = array('1.0.1', '1.0.2', '1.0.3', '1.9.0', '1.1.1', '1.1.2', '1.11.3', '1.2.1', '1.3.1', '2.1.0');
        $expected = array('2.1.0', '1.11.3', '1.9.0', '1.3.1', '1.2.1', '1.1.2', '1.1.1', '1.0.3', '1.0.2', '1.0.1');

        // MIN only
        $this->assertIdentical($expected, $filterVersions($versions));
        $this->assertIdentical($expected, $filterVersions($versions, '1.0.1'));
        $this->assertIdentical($expected, $filterVersions($versions, '1'));
        $this->assertIdentical($expected, $filterVersions($versions, '0'));
        $this->assertIdentical(array('2.1.0', '1.11.3', '1.9.0', '1.3.1', '1.2.1', '1.1.2', '1.1.1'), $filterVersions($versions, '1.1'));
        $this->assertIdentical(array('2.1.0', '1.11.3', '1.9.0', '1.3.1', '1.2.1'), $filterVersions($versions, '1.2'));
        $this->assertIdentical(array('2.1.0', '1.11.3', '1.9.0'), $filterVersions($versions, '1.9'));
        $this->assertIdentical(array('2.1.0'), $filterVersions($versions, '2'));
        $this->assertIdentical(array('2.1.0'), $filterVersions($versions, '2.1'));
        $this->assertIdentical(array('2.1.0'), $filterVersions($versions, '2.1.0'));

        // MIN and MAX
        $this->assertIdentical($expected, $filterVersions($versions, '1', '2'));
        $this->assertIdentical($expected, $filterVersions($versions, '1.0', '2.1'));
        $this->assertIdentical($expected, $filterVersions($versions, '1.0.1', '2.1'));
        $this->assertIdentical($expected, $filterVersions($versions, '1.0.1', '2.1.0'));
        $this->assertIdentical($expected, $filterVersions($versions, null, '2.1.0'));
        $this->assertIdentical(array('1.9.0', '1.3.1', '1.2.1', '1.1.2', '1.1.1', '1.0.3', '1.0.2', '1.0.1'), $filterVersions($versions, null, '1.9'));
        $this->assertIdentical(array('1.11.3', '1.9.0', '1.3.1', '1.2.1', '1.1.2', '1.1.1', '1.0.3', '1.0.2', '1.0.1'), $filterVersions($versions, '1', '1'));
        $this->assertIdentical(array('1.2.1', '1.1.2', '1.1.1', '1.0.3', '1.0.2', '1.0.1'), $filterVersions($versions, '1.0', '1.2'));
        $this->assertIdentical(array('1.2.1', '1.1.2', '1.1.1', '1.0.3', '1.0.2', '1.0.1'), $filterVersions($versions, '1.0', '1.2'));

        // MIN > MAX
        try {
            $filterVersions($versions, '1.2', '1.0');
            $this->fail();
        }
        catch (\Exception $e) {
            $this->assertEqual('MIN_GREATER_THAN_MAX', $e->getKey());
        }

        // Treat non-integers as zero (since Version::versionToDigits() does this)
        $this->assertIdentical(array('1.2.1', '1.1.2', '1.1.1', '1.0.3', '1.0.2', '1.0.1'), $filterVersions($versions, '1.X', '1.2'));
        $actual = $filterVersions(array('X.X.X', '1.X.X', '2.X.X'));
    }

    function testParseEntry() {
        $parseEntry = $this->getStaticMethod('parseEntry');

        $this->assertNull($parseEntry(array()));

        for($i = 0; $i < count($this->entries); $i++) {
            $expected = $this->expected[$i];
            $actual = $parseEntry($this->entries[$i]);
            if ($expected !== $actual) {
                printf("<pre>EXPECTED : %s<br/>ACTUAL: %s</pre>", var_export($expected, true), var_export($actual, true));
            }
            $this->assertIdentical($expected, $actual);
        }

        // Test various release formats
        $parsed = $parseEntry(array('release' => '12.11'));
        $this->assertEqual('12.11', $parsed['release']);

        $parsed = $parseEntry(array('release' => '12.11 (Nov 12)'));
        $this->assertEqual('12.11', $parsed['release']);

        $parsed = $parseEntry(array('release' => ''));
        $this->assertEqual('', $parsed['release']);

        $parsed = $parseEntry(array());
        $this->assertEqual('', $parsed['release']);

        $parsed = $parseEntry(array('release' => null));
        $this->assertEqual('', $parsed['release']);

        $entry = array(
            "details"=>"Line one.\nLine two.",
        );
        $expected = array(
            "Line one.",
            "Line two.",
        );
        $actual = $parseEntry($entry);
        $this->assertEqual($expected, $actual['details']);
    }

    function testUpdateExistingEntry() {
        $updateExistingEntry = $this->getStaticMethod('updateExistingEntry');

        $changelog = array(
            '1.0.1' => array(
                'release' => '12.8',
                'entries' => array(
                    $this->getEntry(),
                    $this->getEntry(array('changelogID' => 123)),
                    $this->getEntry(array('changelogID' => '456')),
                ),
            ),
        );

        $expectedEntry = $entry = $this->getEntry(array(
            'changelogID' => 123,
            'release' => '12.8',
            'description' => 'And now for something completely different.')
        );
        unset($expectedEntry['release']);

        $expected = array(
            '1.0.1' => array(
                'release' => '12.8',
                'entries' => array(
                    $this->getEntry(),
                    $expectedEntry,
                    $this->getEntry(array('changelogID' => '456')),
                ),
            ),
        );

        $actual = $updateExistingEntry($entry, $changelog);
        $this->assertIdentical($expected, $actual);

        // ChangelogID AND refno must match
        $entry2 = $entry;
        $entry2['refno'] = '120924-000002';
        $this->assertNull($updateExistingEntry($entry2, $changelog));

        $entry = $this->getEntry(array('changelogID' => '123', 'description' => 'And now for something completely different.'));
        $actual = $updateExistingEntry($entry, $changelog);
        $this->assertIdentical($expected, $actual);

        $this->assertNull($updateExistingEntry($this->getEntry(), $changelog));

        $entry = $this->getEntry(array('changelogID' => 'NOT A NUMBER'));
        try {
            $this->assertFalse($updateExistingEntry($entry, $changelog));
            $this->fail('Expected exception did not occur');
        }
        catch (\Exception $e) {
            $this->assertEqual('INVALID_CHANGELOG_ID', $e->getKey());
        }
    }

    function testGetFormattedChangelog() {
        $results = Changelog::getFormattedChangelog('framework', '3.1', '3.1');
        $this->assertIsA($results, 'array');
        $this->assertIsA($results['3.1'], 'array');

        try {
            Changelog::getFormattedChangelog('someInvalidType', '3.1', '3.1');
            $this->fail();
        }
        catch (\Exception $e) {
            $this->assertEqual('INVALID_TARGET', $e->getKey());
        }
    }

    function testSortEntries() {
        $method = $this->getMethod('sortEntries');
        $entries = array(
            '3.1' => array(
                'API_CHANGE' => array(
                    $this->getEntry(array('description' => 'api 0', 'sortPosition' => 'Top')),
                    $this->getEntry(array('description' => 'api 2', 'sortPosition' => 'Bottom')),
                    $this->getEntry(array('description' => 'api 1', 'sortPosition' => 'Middle')),
                ),
                'BUG_FIX' => array(
                    $this->getEntry(array('description' => 'bug 0', 'sortPosition' => 'Top')),
                    $this->getEntry(array('description' => 'bug 2', 'sortPosition' => 'Bottom')),
                    $this->getEntry(array('description' => 'bug 1', 'sortPosition' => 'Middle')),
                ),
                'DEPRECATION' => array(
                    $this->getEntry(array('description' => 'dep 0', 'sortPosition' => 'Top')),
                    $this->getEntry(array('description' => 'dep 2', 'sortPosition' => 'Bottom')),
                    $this->getEntry(array('description' => 'dep 1', 'sortPosition' => 'Middle')),
                ),
                'NEW_FEATURE' => array(
                    $this->getEntry(array('description' => 'new 0', 'sortPosition' => 'Top')),
                    $this->getEntry(array('description' => 'new 2', 'sortPosition' => 'Bottom')),
                    $this->getEntry(array('description' => 'new 1', 'sortPosition' => 'Middle')),
                ),
            ),
            '3.0' => array(
                'OTHER' => array(
                    $this->getEntry(array('description' => 'other 0', 'sortPosition' => 'Top')),
                    $this->getEntry(array('description' => 'other 2', 'sortPosition' => 'Bottom')),
                    $this->getEntry(array('description' => 'other 1', 'sortPosition' => 'Middle')),
                ),
            ),
        );
        $sorted = $method($entries);
        $this->assertIdentical(array('NEW_FEATURE','DEPRECATION', 'API_CHANGE', 'BUG_FIX'), array_keys($sorted['3.1']));
        $this->assertEqual('new 0', $sorted['3.1']['NEW_FEATURE'][0]['description']);
        $this->assertEqual('new 1', $sorted['3.1']['NEW_FEATURE'][1]['description']);
        $this->assertEqual('new 2', $sorted['3.1']['NEW_FEATURE'][2]['description']);

        $this->assertEqual('other 0', $sorted['3.0']['OTHER'][0]['description']);
        $this->assertEqual('other 1', $sorted['3.0']['OTHER'][1]['description']);
        $this->assertEqual('other 2', $sorted['3.0']['OTHER'][2]['description']);
    }

    function testSortWithinCategory() {
        $method = $this->getMethod('sortWithinCategory');

        // sortPosition
        $entries = array(
            $this->getEntry(array('description' => 0,  'sortPosition' => 'top')),
            $this->getEntry(array('description' => 2,  'sortPosition' => 'bottom')),
            $this->getEntry(array('description' => 1,  'sortPosition' => 'middle')),
        );
        $sorted = $method($entries);

        $this->assertEqual(0, $sorted[0]['description']);
        $this->assertEqual(1, $sorted[1]['description']);
        $this->assertEqual(2, $sorted[2]['description']);

        // level
        $entries = array(
            $this->getEntry(array('description' => 0,  'level' => 'major')),
            $this->getEntry(array('description' => 2,  'level' => 'nano')),
            $this->getEntry(array('description' => 1,  'level' => 'minor')),
        );
        $sorted = $method($entries);

        $this->assertEqual(0, $sorted[0]['description']);
        $this->assertEqual(1, $sorted[1]['description']);
        $this->assertEqual(2, $sorted[2]['description']);

        // date
        $entries = array(
            $this->getEntry(array('description' => 0,  'date' => '1/1/2013')),
            $this->getEntry(array('description' => 2,  'date' => '12/31/2011')),
            $this->getEntry(array('description' => 1,  'date' => '11/31/2012')),
            $this->getEntry(array('description' => 3,  'date' => '1/1/2011')),
        );
        $sorted = $method($entries);

        $this->assertEqual(0, $sorted[0]['description']);
        $this->assertEqual(1, $sorted[1]['description']);
        $this->assertEqual(2, $sorted[2]['description']);
        $this->assertEqual(3, $sorted[3]['description']);

        // Mixed (sortPosition, level, date)
        $entries = array(
            $this->getEntry(array('description' => 2, 'sortPosition' => 'Middle', 'level' => 'minor')),
            $this->getEntry(array('description' => 4, 'sortPosition' => 'Bottom')),
            $this->getEntry(array('description' => 3, 'sortPosition' => 'middle', 'level' => 'nano')),
            $this->getEntry(array('description' => 5, 'sortPosition' => 'bottom', 'date' => '03/03/2013')),
            $this->getEntry(array('description' => 0, 'sortPosition' => 'top', 'level' => 'major')),
            $this->getEntry(array('description' => 1, 'sortPosition' => 'Top')),
        );

        $sorted = $method($entries);

        $this->assertEqual(0, $sorted[0]['description']);
        $this->assertEqual(1, $sorted[1]['description']);
        $this->assertEqual(2, $sorted[2]['description']);
        $this->assertEqual(3, $sorted[3]['description']);
        $this->assertEqual(4, $sorted[4]['description']);
        $this->assertEqual(5, $sorted[5]['description']);
    }

    function testGroupByCategory() {
        $method = $this->getMethod('groupByCategory');
        $entries = array(
            '3.1' => array(
                $this->getEntry(array('description' => 'api 0', 'category' => 'API_CHANGE')),
                $this->getEntry(array('description' => 'api 1', 'category' => 'API_CHANGE')),
                $this->getEntry(array('description' => 'bug 0', 'category' => 'BUG_FIX')),
                $this->getEntry(array('description' => 'new 0', 'category' => 'NEW_FEATURE')),
                $this->getEntry(array('description' => 'dep 0', 'category' => 'DEPRECATION')),
            ),
            '3.0' => array(
                $this->getEntry(array('description' => 'api 0', 'category' => 'API_CHANGE')),
                $this->getEntry(array('description' => 'api 1', 'category' => 'API_CHANGE')),
                $this->getEntry(array('description' => 'api 2', 'category' => 'API_CHANGE')),
            ),
        );
        $sorted = $method($entries);
        $this->assertEqual('api 0', $sorted['3.1']['API_CHANGE'][0]['description']);
        $this->assertEqual('api 1', $sorted['3.1']['API_CHANGE'][1]['description']);
        $this->assertEqual('bug 0', $sorted['3.1']['BUG_FIX'][0]['description']);
        $this->assertEqual('new 0', $sorted['3.1']['NEW_FEATURE'][0]['description']);
        $this->assertEqual('dep 0', $sorted['3.1']['DEPRECATION'][0]['description']);
        $this->assertEqual('api 0', $sorted['3.0']['API_CHANGE'][0]['description']);
    }

    function testGroupByMajorMinorAndStandardizeData() {
        $method = $this->getMethod('groupByMajorMinorAndStandardizeData');
        $entries = array(
            '3.1.3' => array(
                'release' => '99.2',
                'entries' => array(
                    $this->getEntry(array('description' => 'api 0', 'category' => 'Api Change')),
                    $this->getEntry(array('description' => 'api 1', 'category' => 'Api Change')),
                    $this->getEntry(array('description' => 'bug 0', 'category' => 'Bug Fix')),
                    $this->getEntry(array('description' => 'new 0', 'category' => 'New Feature')),
                    $this->getEntry(array('description' => 'dep 0', 'category' => 'Deprecation')),
                ),
            ),
            '3.1.2' => array(
                'release' => '13.8',
                'entries' => array(
                    $this->getEntry(array('description' => 'api 0', 'category' => 'Api Change')),
                    $this->getEntry(array('description' => 'api 1', 'category' => 'Api Change')),
                    $this->getEntry(array('description' => 'bug 0', 'category' => 'Bug Fix')),
                    $this->getEntry(array('description' => 'new 0', 'category' => 'New Feature')),
                    $this->getEntry(array('description' => 'dep 0', 'category' => 'Deprecation')),
                ),
            ),
            '3.1.1' => array(
                'release' => '13.5',
                'entries' => array(
                    $this->getEntry(array('description' => 'api 0', 'category' => 'Api Change')),
                    $this->getEntry(array('description' => 'api 1', 'category' => 'Api Change')),
                    $this->getEntry(array('description' => 'bug 0', 'category' => 'Bug Fix')),
                    $this->getEntry(array('description' => 'new 0', 'category' => 'New Feature')),
                    $this->getEntry(array('description' => 'dep 0', 'category' => 'Deprecation')),
                    $this->getEntry(array('description' => 'whatzit 0', 'category' => 'Should be filed under OTHER')),
                ),
            ),
        );
        list($grouped, $categories) = $method($entries);
        $this->assertIdentical(array('API_CHANGE', 'BUG_FIX', 'NEW_FEATURE', 'DEPRECATION', 'OTHER'), $categories);
        $this->assertIdentical(array('3.1'), array_keys($grouped));
        $this->assertEqual(11, count($grouped['3.1']));
        $this->assertEqual('api 0', $grouped['3.1'][0]['description']);
        $this->assertEqual('API_CHANGE', $grouped['3.1'][0]['category']);
        $this->assertEqual('whatzit 0', $grouped['3.1'][10]['description']);
        $this->assertEqual('OTHER', $grouped['3.1'][10]['category']);
    }

    function testAddEntriesFromReport() {
        $response = Changelog::addEntriesFromReport(array());
        $this->assertEqual(array(), $response['commits']);
        $this->assertIsA($exception = $response['exceptions'][0], 'RightNow\Internal\Libraries\ChangelogException');
        $this->assertEqual('NO_ENTRIES_FOUND', $exception->getKey());

        // returnExceptionsAsArrays
        $response = Changelog::addEntriesFromReport(array(), false, true);
        $this->assertEqual(array('refno' => NULL, 'changelogID' => NULL, 'level' => 'warning', 'key' => 'NO_ENTRIES_FOUND', 'message' => 'No entries to add'), $response['exceptions'][0]);

        $response = Changelog::addEntriesFromReport(array(array()));
        $this->assertEqual('INVALID_ENTRY', $response['exceptions'][0]->getKey());

        $entries = $this->entries;
        $entries[4]['targets'] = 'framewerk';
        $response = Changelog::addEntriesFromReport($entries, false);
        $this->assertEqual('INVALID_TARGET', $response['exceptions'][0]->getKey());

        $response = Changelog::addEntriesFromReport($this->entries, false);
        $expected = array(
          array(
            'files' => array(CPCORE . 'changelog.yml'),
            'account' => 'blaw',
            'email' => 'blaw@oracle.com',
            'refno' => '031208-000016',
          ),
          array(
            'files' => array(
                dirname(CPCORE) . '/widgets/standard/input/FormInput/changelog.yml',
                dirname(CPCORE) . '/widgets/standard/input/TextInput/changelog.yml',
            ),
            'account' => 'bwhite',
            'email' => 'bwhite@oracle.com',
            'refno' => '031208-000016',
          ),
          array(
            'files' => array(CPCORE . 'changelog.yml'),
            'account' => 'jblack',
            'email' => 'jblack@oracle.com',
            'refno' => '031208-000016',
          ),
          array(
            'files' => array(
                dirname(CPCORE) . '/widgets/standard/input/FormInput/changelog.yml',
                dirname(CPCORE) . '/widgets/standard/input/TextInput/changelog.yml',
            ),
            'account' => 'jdoe',
            'email' => 'jdoe@oracle.com',
            'refno' => '031208-000016',
          ),
          array(
            'files' => array(
                dirname(CPCORE) . '/widgets/standard/input/FormInput/changelog.yml',
                dirname(CPCORE) . '/widgets/standard/input/TextInput/changelog.yml',
                CPCORE . 'changelog.yml',
            ),
            'account' => '',
            'email' => '',
            'refno' => '031208-000016',
          ),
          array( // Numeric indexes important below to ensure we're not retaining keys following an array_unique in the case of duplicate files.
            'files' => array(
                0 => dirname(CPCORE) . '/widgets/standard/input/TextInput/changelog.yml',
                1 => CPCORE . 'changelog.yml',
            ),
            'account' => '',
            'email' => '',
            'refno' => '031208-000017',
            ),
        );
        $this->assertEqual($expected, $response['commits']);

        $this->assertEqual(array(), $response['exceptions']);
    }

    function testChangelogException() {
        $level = 'error';
        $errorKey = 'GENERIC';

        $e = new ChangelogException('boom');;
        $this->assertEqual('boom', $e->getMessage());
        $this->assertEqual($level, $e->getLevel());
        $this->assertEqual($errorKey, $e->getKey());

        $e = new ChangelogException('ka', 'boom');;
        $this->assertEqual('ka : boom', $e->getMessage());
        $this->assertEqual($level, $e->getLevel());
        $this->assertEqual($errorKey, $e->getKey());

        $errorKey = 'INVALID_TARGET';
        $refno = '120919-000001';
        $changelogID = 123;
        $target = 'framewerk';
        $message = "Target should be 'framework' or a valid widget specifier : $target";
        $e = new ChangelogException($errorKey, $target, $changelogID, $refno);
        $this->assertEqual("[$refno : $changelogID] $message", "$e"); // __toString
        $this->assertEqual($message, $e->getMessage());
        $this->assertEqual($refno, $e->getRefno());
        $this->assertEqual($changelogID, $e->getChangelogID());
        $this->assertEqual($level, $e->getLevel());
    }

    function getChangelogArray() {
        return array (
            '1.1.1' => array (
                'release' => '11.5',
                'entries' => array(
                    array(
                        'date' => '02/14/2012',
                        'level' => 'major',
                        'description' => 'Changed styling of that one thing',
                        'details' => array (
                            "Now it's pretty good I guess",
                        ),
                    ),
                ),
            ),
            '1.0.2' => array (
                'release' => '11.2',
                'entries' => array(
                    array(
                        'date' => '01/24/2012',
                        'level' => 'minor',
                        'description' => 'Changed styling of whatzit',
                        'details' => array (
                            "Doesn't overlap with header anymore.",
                        ),
                    ),
                ),
            ),
            '1.0.1' => array (
                'release' => '11.2',
                'entries' => array(
                    array (
                        'date' => '12/31/2011',
                        'level' => 'major',
                        'description' => 'Added new foo feature',
                        'details' => array (
                            'Make sure to update X first',
                        ),
                    ),
                    array (
                        'date' => '11/15/2011',
                        'level' => 'minor',
                        'description' => 'Added ZYX attribute',
                        'details' => array (
                            "Just add to widget definition and you\'re good to go",
                            "Useful when using in conjunction with SSO",
                            "lorem ipsum blah blah",
                        ),
                    ),
                    array (
                        'date' => '10/15/2011',
                        'level' => 'nano',
                        'description' => 'Lorem ipsum dolor sit amet',
                        'details' => array (
                            'consectetur adipisicing elit, sed do eiusmod',
                            'tempor incididunt ut labore et dolore magna aliqua.',
                            'Ut enim ad minim veniam, quis nostrud exercitation',
                        ),
                    ),
                ),
            ),
        );
    }
}
