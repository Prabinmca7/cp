<?php

namespace RightNow\Controllers\UnitTest;

use RightNow\Connect\v1_4 as Connect,
    RightNow\Utils\Validation;

if (IS_HOSTED) {
    exit('Did we ship the data loaders?  That would be sub-optimal.');
}

/**
 * Script to load the users, questions, comments and flags using wiki dump available in JSON format. Number of users, questions and comments can be passed as a GET request.
 * This script skips the wiki files that's been used already to avoid duplicate content. It can be used to populate huge data for performance testing
 *
 * Example: CURL request to this script from command line
 * curl -u admin: "http://{SITE}/ci/admin/createDump/setup?object_types=user,question,comment&user_count=100&question_count=500&comment_count=2000&file_pattern=AA&processed_file_list_location=/home/palk/tmp/test.txt"
 *
 * OR without file pattern to process from the location where it left
 *
 * curl -u admin: "http://{SITE}/ci/admin/createDump/setup?object_types=user,question,comment&user_count=100&question_count=500&comment_count=2000"
 *
 * Also note that you can't create questions without creating users and comments without creating questions and users.
 *
 * DO NOT SHIP THIS
 */
class CreateDump extends \RightNow\Controllers\Admin\Base {

    private $jsonFileLocation = '/nfs/data/performance/wiki/json/';
    private $fileProcessedListLocation = "/nfs/project/cp/tmp/wiki_files_processed.txt";
    private $jsonFilesToUse = array();
    private $jsonFilesPatternFilter = array();
    private $userCount;
    private $questionCount;
    private $commentCount;
    private $users = array();
    private $questions = array();

    function __construct() {
        parent::__construct(true, '_verifyLoginWithCPEditPermission');

        if (!is_readable($this->jsonFileLocation)) {
            echo "No JSON wiki files found @ location: " . $this->jsonFileLocation . PHP_EOL;
            exit;
        }

        $this->userCount = (int)$this->input->get('user_count') ?: 100;
        $this->questionCount = (int)$this->input->get('question_count') ?: 200;
        $this->commentCount = (int)$this->input->get('comment_count') ?: 500;
        $this->jsonFilesPatternFilter = $this->input->get('file_pattern') ? explode(",", $this->input->get('file_pattern')) : array("");//empty to feth all files
        $this->fileProcessedListLocation = $this->input->get('processed_file_list_location') ?: $this->fileProcessedListLocation;

        foreach ($this->jsonFilesPatternFilter as $pattern) {
            foreach (glob($this->jsonFileLocation."index-" . $pattern . "*.json") as $filename) {
                $this->jsonFilesToUse[] = $filename;
            }
        }
        $processedFiles = is_readable($this->fileProcessedListLocation) ? file($this->fileProcessedListLocation, FILE_IGNORE_NEW_LINES) : array();
        $this->jsonFilesToUse = array_values(array_diff($this->jsonFilesToUse, $processedFiles));
        if (!$this->jsonFilesToUse) {
            echo "Error: All files are processed in the given file pattern, try different file pattern OR do not supply any file_pattern OR empty the list of file processed from the file located @  " . $this->fileProcessedListLocation . PHP_EOL;
            exit;
        }

    }

    /**
     * Setup file to invoke user, question and comment creation methods
     */
    public function setup() {
        \RightNow\Libraries\AbuseDetection::check();
        $objectTypes = $this->input->get('object_types');
        if ($objectTypes && in_array('user', explode(',', $objectTypes))) {
            echo PHP_EOL . " ========================= Creating social users - Start ========================= " . PHP_EOL;
            $this->createSocialUsers();
            echo PHP_EOL . " ========================= Creating social users - End =========================  " . PHP_EOL;
            if ($objectTypes && in_array('question', explode(',', $objectTypes))) {
                echo PHP_EOL . " ========================= Creating social questions - Start ========================= " . PHP_EOL;
                $this->createSocialQuestions();
                echo PHP_EOL . " ========================= Creating social questions - End =========================  " . PHP_EOL;
                if ($objectTypes && in_array('comment', explode(',', $objectTypes))) {
                    echo PHP_EOL . "  ========================= Creating social comments - Start ========================= " . PHP_EOL;
                    $this->createSocialComments();
                    echo PHP_EOL . " ========================= Creating social comments - End =========================  " . PHP_EOL;
                }
            }
        }
    }

    /**
     * Function to read chunk of content from one document
     * @param int $contentLength Number of bytes to read
     * @staticvar int $totalContentLength Total content length
     * @staticvar string $content Complete content
     * @staticvar int $startAt Starting offset     *
     * @return string Wiki content
     */
    private function getWikiContent($contentLength = 200) {
        static $totalContentLength;
        static $content;
        static $startAt = 0;
        if (!$content) {
            $content = $this->readWikiContent();
            $totalContentLength = strlen($content);
        }
        if ($startAt + $contentLength > $totalContentLength) {
            $startAt = 0;
            $content = $this->readWikiContent();
            $totalContentLength = strlen($content);
        }
        $requestedContent = substr($content, $startAt, $contentLength);
        $startAt += $contentLength;
        return utf8_encode($requestedContent);
    }

    /**
     * Function to read each document from wiki JSON file
     * @return string Wiki content
     */
    private function readWikiContent() {
        static $processedFiles = array();
        foreach ($this->jsonFilesToUse as $index => $jsonFile) {
            if ($jsonFile !== '.' && $jsonFile !== '..') {
                //each file has 10 documents, if all document is processed, continue with next document
                if ($processedFiles[$jsonFile] && $processedFiles[$jsonFile]['lastProcessedIndex'] === 10) {
                    continue;
                }
                if (!$processedFiles[$jsonFile]) {
                    $allProcessedFiles = file($this->fileProcessedListLocation, FILE_IGNORE_NEW_LINES);
                    if (in_array($jsonFile, $allProcessedFiles)) {
                        continue;
                    }
                    file_put_contents($this->fileProcessedListLocation, $jsonFile . PHP_EOL, FILE_APPEND);
                    $processedFiles[$jsonFile] = array('lastProcessedIndex' => 0);
                    echo PHP_EOL . "File in Process........................ ". $jsonFile . PHP_EOL;
                }

                $wikiData = json_decode(file_get_contents($jsonFile), true);
                $content = strip_tags($wikiData['data']['add'][$processedFiles[$jsonFile]['lastProcessedIndex']]['fields'][1]['value']);
                $processedFiles[$jsonFile]['lastProcessedIndex'] += 1;
                return $content;
            }
        }

    }

    /**
     * Funtion to create random user nmae and return
     * @return string User Name
     */
    private function generateRandomUserName() {
        $minUsernameLength = 5;
        $maxUsernameLength = 15;
        $chars = "abcdefghijklmnopqrstuvwxyz012345";
        $username = "";
        $length = mt_rand($minUsernameLength, $maxUsernameLength);
        for ($i = 0; $i < $length; $i++) {
            $username .= $chars[mt_rand(0, strlen($chars))];
        }
        return $username;
    }

    /**
     * Flush some output to broswer to keep the connection active
     */
    private function flushOutput() {
        echo '.';
        ob_flush();
        flush();
    }

    /**
     * Pick some random user from list of users
     * @return int Random user ID from the available user list
     */
    private function fetchRandomUserFromList() {
        return $this->users[rand(0, (count($this->users) - 1))];
    }

    /**
     * Create different types of social users
     */
    private function createSocialUsers() {
        $contacts = array();
        $contactsTypeToCreate = array(
            'useractive'                   => array('percent' => 85),
            'userpending'                  => array('percent' => 4, 'status' => 37),
            'usersuspended'                => array('percent' => 3, 'status' => 39),
            'userarchive'                  => array('percent' => 1, 'status' => 41),
            'userdeleted'                  => array('percent' => 2, 'status' => 40),
            'modactive'                    => array('percent' => 1, 'type' => 'moderator'),
            'usermoderator'                => array('percent' => 2, 'type' => 'usermoderator'),
            'contentmoderator'             => array('percent' => 1, 'type' => 'contentmoderator'),
            'userupdateonly'               => array('percent' => 1, 'type' => 'updateonly')
        );

        $userCount = ($userCount < 1 ) ? round($userCount * 10) : round($userCount);
        $userType = $userType && $contactsToCreate[$userType] ? $userType : 'useractive';
        foreach ($contactsTypeToCreate as $contactsType => $contactInfo) {
            $userTypeCount = ($contactInfo['percent'] / 100) * $this->userCount;
            $userTypeCount = ($userTypeCount < 1 ) ? round($userTypeCount * 10) : round($userTypeCount);

            for ($u = 0; $u < $userTypeCount; $u++) {
                if ($u % 10 === 0) {
                    $this->flushOutput();
                }
                $login = $this->generateRandomUserName();
                $contact = new Connect\Contact();
                $contact->Login = $login;
                $contact->Name->First = $login;
                $contact->Name->Last = $login;
                $contact->NewPassword = '';

                $email = new Connect\Email();
                $contact->Emails[0] = $email;
                $contact->Emails[0]->AddressType = new Connect\NamedIDOptList();
                $contact->Emails[0]->AddressType->LookupName = "Email - Primary"; //Primary email
                $contact->Emails[0]->Address = $userType. '-'. $login . "@social.com.invalid";
                $contact->Emails[0]->Invalid = false;

                $this->saveObject($contact, $login);

                //Create Social User
                $socialUser = new Connect\CommunityUser();

                $socialUser->StatusWithType = new Connect\CommunityUserStss();
                $socialUser->StatusWithType->Status->ID = $contactInfo['status'] ?: 38;
                $timestamp = $this->getTimestamp();
                $socialUser->CreatedTime = $timestamp;
                $socialUser->UpdatedTime = $timestamp;
                $socialUser->DisplayName = $login;
                $socialUser->Contact = $contact;
                $this->saveObject($socialUser, $login);
                $this->commonUserToRoleSet($contactInfo['type'], $socialUser->ID);
                if ($contactsType !== 'userdeleted' && $socialUser && $socialUser->ID) {
                    $contacts[] = $socialUser->ID;
                }
            }
            echo PHP_EOL . "Total social users created for user type  $contactsType :: $userTypeCount " . PHP_EOL;
        }
        $this->users = $contacts;
    }
    /**
     * Create different types of social questions
     */
    private function createSocialQuestions() {
        $questions = array();
        $questionTypeCreate = array(
            'question_active'                               => array('percent' => 68),
            'question_pending'                              => array('percent' => 3, 'status' => 'pending'),
            'question_suspended'                            => array('percent' => 3, 'status' => 'suspended'),
            'question_deleted'                              => array('percent' => 2, 'status' => 'deleted'),
            'question_locked'                               => array('percent' => 5, 'locked' => true),
            'question_deleted_locked'                       => array('percent' => 1, 'status' => 'deleted', 'locked' => true),
            'question_suspended_locked'                     => array('percent' => 3, 'status' => 'suspended', 'locked' => true),
            'question_pending_locked'                       => array('percent' => 1, 'status' => 'pending', 'locked' => true),
            'question_rate'                                 => array('percent' => 3, 'rate' => true),
            'question_rate_pending'                         => array('percent' => 2, 'status' => 'pending', 'rate' => true),
            'question_flag_redundant'                       => array('percent' => 3, 'flag' => array('type' => 'redundant')),
            'question_flag_miscategorized'                  => array('percent' => 3, 'flag' => array('type' => 'miscategorized')),
            'question_flag_spam_suspended'                  => array('percent' => 2, 'status' => 'suspended', 'flag' => array('type' => 'spam')),
            'question_flag_inappropriate_suspended'         => array('percent' => 1, 'status' => 'suspended', 'flag' => array('type' => 'inappropriate')),
        );

        $availableUserCount = count($this->users);
        foreach ($questionTypeCreate as $questionType => $questionInfo) {
            $questionTypeCount = ($questionInfo['percent'] / 100) * $this->questionCount;
            $questionTypeCount = ($questionTypeCount < 1 ) ? round($questionTypeCount * 10) : round($questionTypeCount);
            $questionCreatedCountForType = 0;

            for ($q = 0; $q < $questionTypeCount; $q++) {
                //fetch the users in round robin fashion
                ($socialUserID = next($this->users)) || ($socialUserID = reset($this->users));
                if ($socialUserID) {
                    if ($q % 10 === 0) {
                        $this->flushOutput();
                    }
                    $questionID = $this->createSocialQuestion($socialUserID, $questionInfo);
                    if ($questionID && !in_array($questionType, array('question_deleted', 'question_deleted_locked'))) {
                        $questions[] = $questionID;
                        $questionCreatedCountForType ++;
                    }
                }
            }
            echo PHP_EOL . "Total social questions created for question type  $questionType :: $questionCreatedCountForType " . PHP_EOL;
        }
        $this->questions = $questions;

    }

    /**
     * Function to create social question for give social user
     * @param int $socialUserID Social User ID
     * @param array $questionInfo Question information
     * @staticvar array $questionBoilerplate  Quetion default information
     * @return boolean True if question created
     */
    private function createSocialQuestion($socialUserID, array $questionInfo){
        static $questionBoilerplate = array(
            'status'      => 'active',
            'bestAnswers' => false,
            'locked'      => false,
            'contentType' => 'text/x-markdown',
        );

        $questionInfo = array_merge($questionBoilerplate, $questionInfo);
        $question = new Connect\CommunityQuestion();
        $timestamp = $this->getTimestamp();
        $question->CreatedTime = $timestamp;
        $question->UpdatedTime = $timestamp;
        $question->LastActivityTime = $timestamp;
        $questionSubject = $this->getWikiContent(rand(30, 230));
        $questionBody = $this->getWikiContent(rand(30, 3000));
        if (!$questionSubject || !$questionBody) {
            return false;
        }

        $question->Subject = $questionSubject;
        $question->Body = $questionBody;
        $question->BodyContentType->LookupName = $questionInfo['contentType'];
        $question->CreatedByCommunityUser = $socialUserID;

        $question->StatusWithType = new Connect\CommunityQuestionStss();
        $question->StatusWithType->Status->ID = $this->questionNameToStatusID($questionInfo['status']);
        if($questionInfo['locked']){
            $question->Attributes->ContentLocked = true;
        }
        if ($this->saveObject($question) === false ) {
            return false;
        }
        if ($questionInfo['rate']) {
            $socialUserID = $this->fetchRandomUserFromList();
            $this->createSocialQuestionContentRating($question->ID, $socialUserID);
        }
        if ($questionInfo['flag']) {
            $socialUserID = $this->fetchRandomUserFromList();
            $flagTypeID = $this->flagNameToID($questionInfo['flag']['type']);
            $this->createSocialQuestionContentFlag($flagTypeID, $question->ID, $socialUserID);
        }
        return $question->ID;

    }

    private function createSocialComments() {
        static $commentToCreate = array(
            'comment_active'            => array('percent' => 61),
            'comment_pending'           => array('percent' => 6, 'status' => 'pending'),
            'comment_deleted'           => array('percent' => 3, 'status' => 'deleted'),
            'comment_rate'              => array('percent' => 10, 'rate' => true),
            'comment_suspended'         => array('percent' => 4, 'status' => 'suspended'),
            'comment_more_and_random'   => array('percent' => 16) // create more than one comment for some questions
        );

        foreach ($commentToCreate as $commentType => $commentInfo) {
            $commentCreatedCountForType = 0;
            $commentTypeCount = ($commentInfo['percent'] / 100) * $this->commentCount;
            $commentTypeCount = ($commentTypeCount < 1) ? round($commentTypeCount * 10) : round($commentTypeCount);

            for ($c = 0; $c < $commentTypeCount; $c++) {
                if ($c % 10 === 0) {
                    $this->flushOutput();
                }
                //fetch the questions in round robin fashion
                ($questionID = next($this->questions)) || ($questionID = reset($this->questions));
                if ($questionID) {
                    if ($commentType === 'comment_more_and_random') {
                        if ($commentCreatedCountForType >= $commentTypeCount) break;
                        //add more thans one comments for few question
                        $moreMinCommentCount = 3;
                        $moreMaxCommentCount = ((3 / 100) * $this->commentCount) > 1000 ? 1000 : ((3 / 100) * $this->commentCount); //set maximum comments to 1000 only
                        $moreCommentTypeCount = rand($moreMinCommentCount, $moreMaxCommentCount);
                        for ($m = 0; $m < $moreCommentTypeCount; $m ++) {
                            if ($commentCreatedCountForType >= $commentTypeCount) break;
                            $commentTypeKey = array_rand($commentToCreate, 1);
                            if ($this->createSocialComment($questionID, $commentToCreate[$commentTypeKey])) {
                                $commentCreatedCountForType++;
                            }
                        }
                    }
                    else {
                        if ($this->createSocialComment($questionID, $commentInfo)) {
                            $commentCreatedCountForType++;
                        }
                    }
                }
            }
            echo PHP_EOL . "Total social comments created for comment type  $commentType :: $commentCreatedCountForType " . PHP_EOL;
        }
    }

    private function createSocialComment($questionID, array $commentInfo = array()) {
        static $defaults = array(
            'status' => 'active',
            'contentType' => 'text/x-markdown',
        );
        $comments = array();

        $commentInfo = array_merge($defaults, $commentInfo);
        $socialUserID = $this->fetchRandomUserFromList();
        $comment = new Connect\CommunityComment();
        $timestamp = $this->getTimestamp();
        $comment->CreatedTime = $timestamp;
        $comment->UpdatedTime = $timestamp;
        $commentBody = $this->getWikiContent(rand(40, 1500));;
        if (!$commentBody) {
            return false;
        }
        $comment->Body = $commentBody;
        $comment->BodyContentType->LookupName = $commentInfo['contentType'];
        $comment->CommunityQuestion = $questionID;
        $comment->CreatedByCommunityUser = $socialUserID;
        $comment->StatusWithType = new Connect\CommunityCommentStss();
        $comment->StatusWithType->Status->ID = $this->commentNameToStatusID($commentInfo['status']);

        $this->saveObject($comment);

        if ($commentInfo['rate']) {
            $this->createSocialQuestionCommentContentRating($comment->ID, $this->fetchRandomUserFromList());
        }

        return $comment;

    }

    private function commonUserToRoleSet($type, $socialID) {
        if (!IS_HOSTED) {
            $types = array(
                'moderator'        => '(%1$d, 5)',
                'admin'            => '(%1$d, 5),(%1$d, 6)',
                'usermoderator'    => '(%1$d, 100002)',
                'contentmoderator' => '(%1$d, 100003)',
                'updateonly'       => '(%1$d, 100215)',
                'deletenoupdate'   => '(%1$d, 100216)',
            );

            if ($types[$type]) {
                \RightNow\Api::test_sql_exec_direct(sprintf('INSERT INTO common_user2role_sets VALUES ' . $types[$type], $socialID));
            }
        }
    }

    private function getTimestamp() {
        // distribute data for over 1 year
        $dt = new \DateTime();
        $dt->sub(new \DateInterval('P' . rand(1, 365). 'D'));
        $dt->add(new \DateInterval('PT' . rand(1, 3600 * 24) . 'S'));
        return $dt->getTimestamp();
    }




    private function questionNameToStatusID($statusName){
        static $statuses = array('active'    => 29,
                                 'suspended' => 30,
                                 'deleted'   => 31,
                                 'pending'   => 32);
        return $statuses[$statusName];
    }

    private function commentNameToStatusID($statusName, $reverse = false){
        static $statuses = array('active'    => 33,
                                 'suspended' => 34,
                                 'deleted'   => 35,
                                 'pending'   => 36);
        return $reverse ? array_search((int)$statusName, $statuses) : $statuses[$statusName];
    }

    private function flagNameToID ($name, $reverse = false) {
        static $flagNameIDs = array('inappropriate' => 1,
            'spam' => 2,
            'miscategorized' => 3,
            'redundant' => 4);
        return $reverse ? array_search((int) $name, $flagNameIDs) : $flagNameIDs[$name];
    }

    private function createSocialQuestionContentFlag ($flagType, $socialQuestionID, $socialUserID) {
        try {
            //Create Social Question Flag
            $socialQuestionsFlag = new Connect\CommunityQuestionFlg();

            $socialQuestionsFlag->CommunityQuestion = $socialQuestionID;
            $socialQuestionsFlag->CommunityUser = $socialUserID;
            $socialQuestionsFlag->Type = intval($flagType);
            $this->saveObject($socialQuestionsFlag);
        }
        catch (Connect\ConnectAPIErrorBase $e) {
            return false;
        }
    }

    private function createSocialQuestionCommentContentRating ($socialCommentID, $socialUserID) {
        try {
            //Create Social Question Comment Rating
            $socialQuestionsRating = new Connect\CommunityCommentRtg();
            $socialQuestionsRating->CommunityComment = $socialCommentID;
            $socialQuestionsRating->CreatedByCommunityUser = $socialUserID;
            $socialQuestionsRating->RatingValue = 100;
            $socialQuestionsRating->RatingWeight = 100;
            $this->saveObject($socialQuestionsRating);
        }
        catch (Connect\ConnectAPIErrorBase $e) {
            return false;
        }
    }

    private function createSocialQuestionContentRating ($socialQuestionID, $socialUserID) {
        try {
            //Create Social Question Rating
            $socialQuestionRating = new Connect\CommunityQuestionRtg();
            $socialQuestionRating->CommunityQuestion = $socialQuestionID;
            $socialQuestionRating->CreatedByCommunityUser = $socialUserID;
            $socialQuestionRating->RatingValue = 100;
            $socialQuestionRating->RatingWeight = 100;
            $this->saveObject($socialQuestionRating);
        }
        catch (Connect\ConnectAPIErrorBase $e) {
            return false;
        }
    }

    private function saveObject($object, $login = 'useradmin') {
        try {
            // useradmin can do just about anything, so default to that user if none is specified
            list($rawProfile, $rawSession) = $this->logIn($login);
            $object->save();
            Connect\ConnectAPI::commit();
            $this->logOut($rawProfile, $rawSession);
        } catch (Connect\ConnectAPIErrorBase $e) {
            echo 'Error:' . $e->getMessage();
            return false;
        }
    }

    private function logIn($login) {
        $rawProfile = (object) \RightNow\Api::contact_login(array(
            'login' => $login,
            'sessionid' => '', // Pass a bogus sessionID to custlogin so that it will create a new one.
            'cookie_set' => 1,
            'login_method' => CP_LOGIN_METHOD_LOCAL,
        ));
        $rawSession = new \RightNow\Libraries\SessionData(array(
            's' => $rawProfile->sessionid, //Add the new, real session ID
            'a' => 0,
            'n' => 0,
            'u' => array(),
            'p' => false,
            'e' => '/session/L3NpZC9HVjRtWDFsag==',
            'r' => null,
            'l' => time(),
            'i' => \RightNow\Api::intf_id(),
        ));
        return array($rawProfile, $rawSession);
    }

    private function logOut($rawProfile, $rawSession) {
        \RightNow\Api::contact_logout(array(
            'cookie' => $rawProfile->cookie,
            'sessionid' => $rawSession->sessionID,
        ));
    }
}
