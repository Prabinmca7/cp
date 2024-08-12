<?php

namespace RightNow\Controllers\UnitTest;

use RightNow\Connect\v1_4 as Connect;

if (IS_HOSTED) {
    exit('Did we ship the data loaders?  That would be sub-optimal.');
}

/**
 * DO NOT SHIP THIS
 */
class SeedTestData extends \RightNow\Controllers\Admin\Base {
    function __construct() {
        parent::__construct(true, '_verifyLoginWithCPEditPermission');

        $this->counters = array(
            'text/x-markdown' => 0,
            'text/html' => 0,
        );
    }

    public function devDBSetup() {
        \RightNow\Libraries\AbuseDetection::check();

        $this->contacts = $this->createContacts();
        $this->createQuestions();
        $this->createQuesProdCatSubscription();
        echo "done";
    }

    private function createContacts() {
        // pending - 37
        // active - 38
        // suspended - 39
        // deleted - 40
        // archive - 41
        $contactsToCreate = array(
            'useractive1'        => array(),
            'useractive2'        => array(),
            'modactive1'                   => array('type' => 'moderator', 'avatarUrl' => 'https://s3.amazonaws.com/uifaces/faces/twitter/sillyleo/128.jpg'),
            'modactive2'                   => array('type' => 'moderator'),
            'usermoderator'                => array('type' => 'usermoderator'),
            'contentmoderator'             => array('type' => 'contentmoderator'),
            'userarchive1'                 => array('status' => 41),
            'userpending'                  => array('status' => 37),
            'usersuspended'                => array('status' => 39),
            'userdeleted'                  => array('status' => 40),
            'modarchive'                   => array('type' => 'moderator', 'status' => 41),
            'modpending'                   => array('type' => 'moderator', 'status' => 37),
            'modsuspended'                 => array('type' => 'moderator', 'status' => 39),
            'moddeleted'                   => array('type' => 'moderator', 'status' => 40),
            'useradmin'                    => array('type' => 'admin'),
            'usersuspended2'               => array('status' => 39),
            'userupdateonly'               => array('type' => 'updateonly'),
            'userdeletenoupdatestatus'     => array('type' => 'deletenoupdate'),
        );
        $contacts = array();

        foreach ($contactsToCreate as $login => $info) {
            $contact = new Connect\Contact();
            $contact->Login = $login;
            $contact->Name->First = $login;
            $contact->Name->Last = $login;
            $contact->NewPassword = '';

            $email = new Connect\Email();
            $contact->Emails[0] = $email;
            $contact->Emails[0]->AddressType = new Connect\NamedIDOptList();
            $contact->Emails[0]->AddressType->LookupName = "Email - Primary"; //Primary email
            $contact->Emails[0]->Address = $login . "@social.com.invalid";
            $contact->Emails[0]->Invalid = false;

            if ($login === 'useractive1') {
                $contact->CustomFields->c->newsletter = false;
                $contact->CustomFields->c->pets_name = 'cat';
                $contact->CustomFields->c->last_login = 1443037377;
                $contact->CustomFields->c->pet_type->ID = 1;
            }

            $this->saveObject($contact, $login);

            //Create Social User
            $socialUser = new Connect\CommunityUser();

            $socialUser->StatusWithType = new Connect\CommunityUserStss();
            $socialUser->StatusWithType->Status->ID = $info['status'] ?: 38;

            if ($info['avatarUrl']) {
                $socialUser->AvatarUrl = $info['avatarUrl'];
            }

            $socialUser->DisplayName = $login;
            $socialUser->Contact = $contact;
            $this->saveObject($socialUser, $login);

            $this->commonUserToRoleSet($info['type'], $socialUser->ID);

            $contacts[$login] = array($contact, $socialUser);
        }
        return $contacts;
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

    private function createQuestions() {
        //NOTE: ONLY ADD NEW QUESTIONS TO THE BOTTOM OF THIS LIST
        $this->createQuestion($this->contacts['useractive1'], array('subscriber' => 'useractive1'));
        $this->createQuestion($this->contacts['useractive1'], array('bestAnswers' => 'author', 'rate' => array('social_user' => 'useractive2')));
        $this->createQuestion($this->contacts['useractive1'], array('bestAnswers' => 'moderator'));
        $this->createQuestion($this->contacts['useractive1'], array('bestAnswers' => 'same'));
        $this->createQuestion($this->contacts['useractive1'], array('status' => 'suspended'));
        $this->createQuestion($this->contacts['useractive1'], array('status' => 'deleted'));
        $this->createQuestion($this->contacts['useractive1'], array('locked' => true));

        $this->createQuestion($this->contacts['useractive2'], array());
        $this->createQuestion($this->contacts['useractive2'], array('status' => 'pending'));
        $this->createQuestion($this->contacts['useractive2'], array('status' => 'suspended'));
        $this->createQuestion($this->contacts['useractive2'], array('bestAnswers' => 'different'));
        $this->createQuestion($this->contacts['useractive2'], array('bestAnswers' => 'moderator'));
        $this->createQuestion($this->contacts['useractive2'], array('bestAnswers' => 'moderator', 'locked' => true));
        $this->createQuestion($this->contacts['useractive2'], array('bestAnswers' => 'same', 'locked' => true));

        $this->createQuestion($this->contacts['userarchive1'], array());
        $this->createQuestion($this->contacts['userarchive1'], array('status' => 'suspended'));
        $this->createQuestion($this->contacts['userarchive1'], array('bestAnswers' => 'author', 'locked' => true));
        $this->createQuestion($this->contacts['userarchive1'], array('bestAnswers' => 'different'));
        $this->createQuestion($this->contacts['userarchive1'], array('bestAnswers' => 'moderator', 'locked' => true));
        $this->createQuestion($this->contacts['userarchive1'], array('bestAnswers' => 'different', 'status' => 'suspended'));

        $this->createQuestion($this->contacts['userpending'], array('status' => 'pending'));
        $this->createQuestion($this->contacts['userpending'], array('bestAnswers' => 'same', 'locked' => true));
        $this->createQuestion($this->contacts['userpending'], array('status' => 'suspended'));

        $this->createQuestion($this->contacts['usersuspended'], array());
        $this->createQuestion($this->contacts['usersuspended'], array('status' => 'suspended'));
        $this->createQuestion($this->contacts['usersuspended'], array('bestAnswers' => 'different', 'locked' => true));
        $this->createQuestion($this->contacts['usersuspended'], array('status' => 'deleted'));

        $this->createQuestion($this->contacts['userdeleted'], array());
        $this->createQuestion($this->contacts['userdeleted'], array('status' => 'suspended'));
        $this->createQuestion($this->contacts['userdeleted'], array('status' => 'suspended', 'locked' => true));
        $this->createQuestion($this->contacts['userdeleted'], array('status' => 'deleted'));
        $this->createQuestion($this->contacts['userdeleted'], array('status' => 'deleted', 'locked' => true));

        $this->createQuestion($this->contacts['modactive2'], array());
        $this->createQuestion($this->contacts['modactive2'], array('locked' => true, 'status' => 'deleted'));
        $this->createQuestion($this->contacts['modactive2'], array('bestAnswers' => 'moderator'));
        $this->createQuestion($this->contacts['modactive2'], array('bestAnswers' => 'different'));
        $this->createQuestion($this->contacts['modactive2'], array('bestAnswers' => 'same'));

        $this->createQuestion($this->contacts['useradmin'], array());
        $this->createQuestion($this->contacts['useradmin'], array('locked' => true));
        $this->createQuestion($this->contacts['useradmin'], array('bestAnswers' => 'author', 'locked' => true));
        $this->createQuestion($this->contacts['useradmin'], array('status' => 'pending'));
        $this->createQuestion($this->contacts['useradmin'], array('status' => 'pending', 'locked' => true));

        //few questions with flags
        $this->createQuestion($this->contacts['useractive2'], array('flag' => array('social_user' => 'useractive1', 'type' => 'miscategorized')));
        $this->createQuestion($this->contacts['useractive1'], array('status' => 'suspended', 'flag' => array('social_user' => 'useractive2', 'type' => 'miscategorized')));
        $this->createQuestion($this->contacts['modactive2'], array('flag' => array('social_user' => 'useractive2', 'type' => 'redundant')));
        $this->createQuestion($this->contacts['useractive1'], array('flag' => array('social_user' => 'useractive2', 'type' => 'inappropriate')));
        $this->createQuestion($this->contacts['useractive2'], array('status' => 'suspended', 'flag' => array('social_user' => 'modactive2', 'type' => 'spam')));
        $this->createQuestion($this->contacts['modactive2'], array('flag' => array('social_user' => 'useractive2', 'type' => 'spam')));

        // Questions for suspended comment visibility
        $this->createQuestion($this->contacts['useractive1'], array(), array(
            array(),
            array('status' => 'suspended'),
            array('child' => true),
            array('child' => true, 'status' => 'suspended'),
        ));
        $this->createQuestion($this->contacts['useractive1'], array(), array(
            array(),
            array('status' => 'suspended'),
            array('child' => true, 'status' => 'suspended'),
            array('child' => true, 'status' => 'suspended'),
        ));
        $this->createQuestion($this->contacts['useractive1'], array(), array(
            array(),
            array('status' => 'suspended'),
        ));
        $this->createQuestion($this->contacts['useractive1'], array(), array(
            array(),
            array('child' => true, 'status' => 'suspended'),
        ));

        // html
        $this->createQuestion($this->contacts['useractive1'], array('contentType' => 'text/html'), array(
            array('contentType' => 'text/html', 'body' => $this->getText('text/html')),
            array('contentType' => 'text/html', 'body' => $this->getText('text/html')),
            array('contentType' => 'text/html', 'body' => $this->getText('text/html')),
            array('contentType' => 'text/html', 'body' => $this->getText('text/html')),
            array('contentType' => 'text/html', 'body' => $this->getText('text/html')),
        ));
        // Question for long best answers
        $this->createQuestion($this->contacts['useractive1'], array('bestAnswers' => 'different'), array(
            array('contentType' => 'text/x-markdown', 'body' => ($this->getText('text/x-markdown') . $this->getText('text/x-markdown'))),
            array('contentType' => 'text/x-markdown', 'body' => ($this->getText('text/x-markdown') . $this->getText('text/x-markdown'))),
        ));
        $this->createQuestion($this->contacts['userupdateonly'], array());
        $this->createQuestion($this->contacts['userdeletenoupdatestatus'], array());
        $this->createMockImportedQuestion();

        // Mix of html and markdown
        $this->createQuestion($this->contacts['modactive1'], array('contentType' => 'text/html'), array(
            array('contentType' => 'text/x-markdown', 'body' => $this->getText('text/x-markdown')),
            array('contentType' => 'text/html', 'body' => $this->getText('text/html')),
        ));
    }

    private function getTimestamp() {
        static $dt;
        if (!$dt) {
            $dt = new \DateTime();
            // subtract 30 days so that subsequent fixture
            // content will always be created after the seed data
            $dt->sub(new \DateInterval('P30D'));
        }

        $dt->add(new \DateInterval('PT1S'));
        return $dt->getTimestamp();
    }

    private function createQuestion(array $contactInfo, array $questionInfo, array $commentData = array()){
        static $questionBoilerplate = array(
            'status'      => 'active',
            'bestAnswers' => false,
            'locked'      => false,
            'contentType' => 'text/x-markdown',
        );

        $questionInfo = array_merge($questionBoilerplate, $questionInfo);
        $contact = $contactInfo[0];
        $socialUser = $contactInfo[1];

        $question = new Connect\CommunityQuestion();
        $timestamp = $this->getTimestamp();
        $question->CreatedTime = $timestamp;
        $question->UpdatedTime = $timestamp;
        $question->LastActivityTime = $timestamp;
        $question->Subject = $this->getQuestionSubject($questionInfo, $socialUser);
        $question->Body = $questionInfo['body'] ?: $this->getText($questionInfo['contentType']);
        $question->BodyContentType->LookupName = $questionInfo['contentType'];
        $question->CreatedByCommunityUser = $socialUser;
        $question->StatusWithType = new Connect\CommunityQuestionStss();
        $question->StatusWithType->Status->ID = $this->questionNameToStatusID($questionInfo['status']);
        if($questionInfo['locked']){
            $question->Attributes->ContentLocked = true;
        }
        $this->saveObject($question);

        $comments = $this->createComments($question, $commentData);
        $this->saveObject($question);

        if ($questionInfo['bestAnswers']) {
            // always use 'modactive1' as the moderator best answer selector
            $moderator = $this->contacts['modactive1'][1];
            $commentOne = $comments[$question->ID % count($comments)];
            $commentTwo = $comments[($question->ID + 3) % count($comments)];
            if ($questionInfo['bestAnswers'] === 'author' || $questionInfo['bestAnswers'] === 'same' || $questionInfo['bestAnswers'] === 'different') {
                $comment = $commentOne;
                $bestAnswer = new Connect\BestCommunityQuestionAnswer();
                $bestAnswer->BestAnswerType->ID = SSS_BEST_ANSWER_AUTHOR;
                $bestAnswer->CommunityComment = $comment;
                $bestAnswer->CommunityUser = $socialUser;
                $question->BestCommunityQuestionAnswers[] = $bestAnswer;

                $comment->Body .= " and comment marked best answer by author";
                $this->saveObject($comment);

                $question->Subject .= " and author best answer is " . $this->commentNameToStatusID($comment->StatusWithType->Status->ID, true);
            }
            if ($questionInfo['bestAnswers'] === 'moderator' || $questionInfo['bestAnswers'] === 'same' || $questionInfo['bestAnswers'] === 'different') {
                $comment = $questionInfo['bestAnswers'] === 'different' ? $commentTwo : $commentOne;
                $bestAnswer = new Connect\BestCommunityQuestionAnswer();
                $bestAnswer->BestAnswerType->ID = SSS_BEST_ANSWER_MODERATOR;
                $bestAnswer->CommunityComment = $comment;
                $bestAnswer->CommunityUser = $moderator;
                $question->BestCommunityQuestionAnswers[] = $bestAnswer;

                $comment->Body .= " and comment marked best answer by moderator";
                $this->saveObject($comment);

                $question->Subject .= " and moderator best answer is " . $this->commentNameToStatusID($comment->StatusWithType->Status->ID, true);
            }
            $timestamp = $this->getTimestamp();
            $question->UpdatedTime = $timestamp;
            $question->LastActivityTime = $timestamp;
            $this->saveObject($question);

            if ($questionInfo['rate']) {
                $socialUser = $this->contacts[$questionInfo['rate']['social_user']][1];
                $this->createSocialQuestionContentRating($question->ID, $socialUser->ID);
            }
        }
        if ($questionInfo['flag']) {
            $socialUser = $this->contacts[$questionInfo['flag']['social_user']][1];
            $flagTypeID = $this->flagNameToID($questionInfo['flag']['type']);
            $this->createSocialQuestionContentFlag($flagTypeID, $question->ID, $socialUser->ID);
        }

        if ($questionInfo['subscriber']) {
            $this->createSocialQuestionSubscription($question->ID, $this->contacts[$questionInfo['subscriber']][1]);
        }
    }

    private function createComments($question, array $commentData = array()) {
        static $commentsToCreate = array(
            array(),
            array('status' => 'pending'),
            array('status' => 'deleted'),
            array('rate' => array('social_user' => 'useractive1')),
            array('child' => true),
            array('child' => true),
            array('child' => true, 'status' => 'suspended'),
            array(),
        );

        static $defaults = array(
            'status' => 'active',
            'contentType' => 'text/x-markdown',
        );

        $comments = array();
        $contacts = array_values($this->contacts);
        $index = $question->ID % count($contacts);
        $childLevel = 0;

        foreach ($commentData ?: $commentsToCreate as $commentInfo) {
            $commentInfo = array_merge($defaults, $commentInfo);

            $author = $contacts[$index];
            $comment = new Connect\CommunityComment();
            $timestamp = $this->getTimestamp();
            $comment->CreatedTime = $timestamp;
            $comment->UpdatedTime = $timestamp;
            $question->UpdatedTime = $timestamp;
            $question->LastActivityTime = $timestamp;
            $comment->Body = $commentInfo['body'] ?: "Comment by ({$author[0]->Login}/{$author[1]->DisplayName}) with status = {$commentInfo['status']}";
            $comment->BodyContentType->LookupName = $commentInfo['contentType'];
            $comment->CommunityQuestion = $question;
            $comment->CreatedByCommunityUser = $author[1];
            $comment->StatusWithType = new Connect\CommunityCommentStss();
            $comment->StatusWithType->Status->ID = $this->commentNameToStatusID($commentInfo['status']);

            if ($commentInfo['child']) {
                $comment->Parent = $lastComment;
                $childLevel++;
                $comment->Body .= "\n\n_reply level {$childLevel}_\n";
            }
            else {
                $childLevel = 0;
            }

            $this->saveObject($comment);

            if ($commentInfo['rate']) {
                $socialUser = $this->contacts[$commentInfo['rate']['social_user']][1];
                $this->createSocialQuestionCommentContentRating($comment->ID, $socialUser->ID);
            }

            $comments[] = $comment;
            $lastComment = $comment;
            $index++;
            if ($index >= count($contacts))
                $index = 0;
        }
        return $comments;
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

    private function getQuestionSubject($questionDetails, $socialUser){
        $subject = '[' . strtoupper($questionDetails['status']) . ']';
        if($questionDetails['locked']){
            $subject .= " [LOCKED] ";
        }
        if($questionDetails['bestAnswers']){
            if(in_array($questionDetails['bestAnswers'], array('author', 'same', 'different'))){
                $subject .= ' [AUTHOR BA] ';
            }
            if(in_array($questionDetails['bestAnswers'], array('moderator', 'same', 'different'))){
                $subject .= ' [MOD BA] ';
            }
        }
        if ($questionDetails['flag']) {
             $subject .= ' [FLAG ' . strtoupper($questionDetails['flag']['type']) . '] ';
        }

        $subject .= "Question by {$socialUser->DisplayName}";
        return $subject;
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

    private function getText($contentType = 'text/x-markdown'){
        static $text = array(
            'text/x-markdown' => array(
                "In a moment he was standing in the boat's stern, and the Manilla men were springing to their oars. In vain the English Captain hailed him. With back to the stranger ship, and face set like a flint to his own, Ahab stood upright till alongside of the Pequod.\n\nEre the English ship fades from sight, be it set down here, that she hailed from London, and was named after the late Samuel Enderby, merchant of that city, the original of the famous whaling house of Enderby &amp; Sons; a house which in my poor whaleman's opinion, comes not far behind the united royal houses of the Tudors and Bourbons, in point of real historical interest. How long, prior to the year of our Lord 1775, this great whaling house was in existence, my numerous fish-documents do not make plain; but in that year (1775) it fitted out the first English ships that ever regularly hunted the Sperm Whale; though for some score of years previous (ever since 1726) our valiant Coffins and Maceys of Nantucket and the Vineyard had in large fleets pursued that Leviathan, but only in the North and South Atlantic: not elsewhere. Be it distinctly recorded here, that the Nantucketers were the first among mankind to harpoon with civilized steel the great Sperm Whale; and that for half a century they were the only people of the whole globe who so harpooned him.",
                "When I arrived home, my housekeeper screamed as I entered, and fled away. And when I rang, I found the housemaid had likewise fled. I investigated. In the kitchen I found the cook on the point of departure. But she screamed, too, and in her haste dropped a suitcase of her personal belongings and ran out of the house and across the grounds, still screaming. I can hear her scream to this day. You see, we did not act in this way when ordinary diseases smote us. We were always calm over such things, and sent for the doctors and nurses who knew just  what to do. But this was different. It struck so suddenly, and killed so  swiftly, and never missed a stroke. When the scarlet rash appeared on a person's face, that person was marked by death. There was never a known case of a recovery.\n\nI was alone in my big house. As I have told you often before, in those  days we could talk with one another over wires or through the air. The telephone bell rang, and I found my brother talking to me. He told me that he was not coming home for fear of catching the plague from me, and that he had taken our two sisters to stop at Professor Bacon's home. He advised me to remain where I was, and wait to find out whether or not I had caught the plague.",
                "As my machine sank among them I realized that it was fight or die, with good chances of dying in any event, and so I struck the ground with drawn long-sword ready to defend myself as I could.\n\nI fell beside a huge monster who was engaged with three antagonists, and as I glanced at his fierce face, filled with the light of battle, I recognized Tars Tarkas the Thark.\nHe did not see me, as I was a trifle behind him, and just then the three warriors opposing him, and whom I recognized as Warhoons, charged simultaneously.  The mighty fellow made quick work of one of them, but in stepping back for another thrust he fell over a dead body behind him and was down and at the mercy of his foes in an instant.\n\nQuick as lightning they were upon him, and Tars Tarkas would have been gathered to his fathers in short order had I not sprung before his prostrate form and engaged his adversaries.  I had accounted for one of them when the mighty Thark regained his feet and quickly settled the other.",
                "'Good Lord!' said Henderson.  'Fallen meteorite!  That's good.'\n\n'But it's something more than a meteorite.  It's a cylinder--an artificial cylinder, man!  And there's something inside.'\n\nHenderson stood up with his spade in his hand.",
                "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Et non ex maxima parte de tota iudicabis? At hoc in eo M. Duo Reges: constructio interrete. Numquam facies. Zenonis est, inquam, hoc Stoici. Gerendus est mos, modo recte sentiat. Prave, nequiter, turpiter cenabat; Facete M.\n\nQuo modo autem philosophus loquitur? Id mihi magnum videtur. Haec quo modo conveniant, non sane intellego. Quis Aristidem non mortuum diligit? At enim sequor utilitatem. Quonam, inquit, modo? Quibusnam praeteritis?\nSed quid sentiat, non videtis. Quare attende, quaeso. Quid ad utilitatem tantae pecuniae? Haec igitur Epicuri non probo, inquam.",
                "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Non quaeritur autem quid naturae tuae consentaneum sit, sed quid disciplinae. Illud quaero, quid ei, qui in voluptate summum bonum ponat, consentaneum sit dicere. Naturales divitias dixit parabiles esse, quod parvo esset natura contenta. Dicet pro me ipsa virtus nec dubitabit isti vestro beato M. Et harum quidem rerum facilis est et expedita distinctio. Quis negat? Neque solum ea communia, verum etiam paria esse dixerunt. Duo Reges: constructio interrete.\n Qui autem esse poteris, nisi te amor ipse ceperit? Rationis enim perfectio est virtus; ",
                "Lebowski ipsum jUST BECAUSE WE'RE BEREAVED DOESN'T MEAN WE'RE SAPS! Dolor sit amet, consectetur adipiscing elit praesent ac magna justo pellentesque ac. Your goons'll be able to get it off him, mean he's only fifteen and he's flunking social studies. So if you'll just write me a check for my ten per cent… of half a million… fifty grand. Lectus quis elit blandit fringilla a ut turpis praesent felis ligula.",
                "Well, the way they make shows is, they make one show. That show's called a pilot. Then they show that show to the people who make shows, and on the strength of that one show they decide if they're going to make more shows. Some pilots get picked and become television programs. Some don't, become nothing. She starred in one of the ones that became nothing.",
                "You think water moves fast? You should see ice. It moves like it has a mind. Like it knows it killed the world once and got a taste for murder. After the avalanche, it took us a week to climb out. Now, I don't know exactly when we turned on each other, but I know that seven of us survived the slide... and only five made it out. Now we took an oath, that I'm breaking now. We said we'd say it was the snow that killed the other two, but it wasn't. Nature is lethal but it doesn't hold a candle to man.\n\nNow that we know who you are, I know who I am. I'm not a mistake! It all makes sense! In a comic, you know how you can tell who the arch-villain's going to be? He's the exact opposite of the hero. And most times they're friends, like you and me! I should've known way back when... You know why, David? Because of the kids. They called me Mr Glass.\nYour bones don't break, mine do. That's clear. Your cells react to bacteria and viruses differently than mine. You don't get sick, I do. That's also clear. But for some reason, you and I react the exact same way to water. We swallow it too fast, we choke. We get some in our lungs, we drown. However unreal it may seem, we are connected, you and I. We're on the same curve, just on opposite ends.",
                "He's alright. Thanks a lot, kid. Okay, but I don't know what to say. Hey man, the dance is over. Unless you know someone else who could play the guitar. You know Marty, I'm gonna be very sad to see you go. You've really mad a difference in my life, you've given me something to shoot for. Just knowing, that I'm gonna be around to se 1985, that I'm gonna succeed in this. That I'm gonna have a chance to travel through time. It's going to be really hard waiting 30 years before I could talk to you about everything that's happened in the past few days. I'm really gonna miss you, Marty.\n\nAbout 30 years, it's a nice round number. Really. Hot, Jesus Christ, Doc. Jesus Christ, Doc, you disintegrated Einstein. He's your brother, Mom. Ahh. Ahh.\n\nNow remember, according to my theory you interfered with with your parent's first meeting. They don't meet, they don't fall in love, they won't get married and they wont have kids. That's why your older brother's disappeared from that photograph. Your sister will follow and unless you repair the damages, you will be next. I had a horrible nightmare, dreamed I went back in time, it was terrible. That's true, Marty, I think you should spend the night. I think you're our responsibility. What about George? Oh, great scott. You get the cable, I'll throw the rope down to you.\n\nRonald Reagon, the actor? Then who's vice president, Jerry Lewis? I suppose Jane Wymann is the first lady. I'll call you tonight. Oh hey, Biff, hey, guys, how are you doing? Whoa, wait a minute, Doc, are you telling me that my mother has got the hots for me? Yeah okay.",
                "You can’t play games on a Mac furthermore enterprise will always need Windows during Surface is the ultimate tablet, until you can’t get Office on an iPad but while Windows Phone is beautiful so as to no compromise, thus the iPhone is boring, this includes Windows Phone 8 is much better than Windows Phone 7.",
                "Collaboratively administrate empowered markets via plug-and-play networks. Dynamically procrastinate B2C users after installed base benefits. Dramatically visualize customer directed convergence without revolutionary ROI.\n\nEfficiently unleash cross-media information without cross-media value. Quickly maximize timely deliverables for real-time schemas. Dramatically maintain clicks-and-mortar solutions without functional solutions.\n\nCompletely synergize resource sucking relationships via premier niche markets. Professionally cultivate one-to-one customer service with robust ideas.",
                "One point twenty-one gigawatts. One point twenty-one gigawatts. Great Scott.",
                "These challenges are not all of government's making. I will build new partnerships to defeat the threats of the 21st century: terrorism and nuclear proliferation; poverty and genocide; climate change and disease. That does not mean we should ignore sources of tension.\n\nWe are the party of Kennedy. There must be a sustained effort to listen to each other; to learn from each other; to respect one another; and to seek common ground.",
                "You got any promising leads?\n\n- Leads? Yeah, sure. I'll, uh, just check with the boys down at the crime lab.\n- They got four more detectives working on the case.\n- They got us working in shifts.",
            ),
            'text/html' => array(
                'This text should be <em>emphasized</em>',
                'This text should be <small>small</small>',
                'This text should be <sub>subscripted</sub>',
                'This text should be <sup>superscripted</sup>',
                'This text should be <mark>highlighted</mark>',
                'This text should be <b>bold</b>',
            ),
        );

        $choices = $text[$contentType];
        $index = $this->counters[$contentType];
        $currentChoice = $choices[$index];
        $this->counters[$contentType] = ($index + 2 > count($choices)) ? 0 : ($index + 1);

        return $currentChoice;
    }

    private function createQuesProdCatSubscription () {
        $this->createSocialQuestionSubscription(3, $this->contacts['useractive1'][1]);
        $this->createSocialQuestionProductSubscription(7, $this->contacts['useractive1'][1]);
        $this->createSocialQuestionProductSubscription(6, $this->contacts['useractive1'][1]);
        $this->createSocialQuestionCategorySubscription(70, $this->contacts['useractive1'][1]);
        $this->createSocialQuestionCategorySubscription(71, $this->contacts['useractive1'][1]);
    }

    private function createSocialQuestionSubscription($questionID, $user){
        // Create the social question subscription object
        $socialQuestionSubscription = new Connect\CommunityQuestionSubscription();
        $socialQuestionSubscription->CommunityQuestion = $questionID;
        $socialQuestionSubscription->DeliveryFrequency = 1; // Now
        $socialQuestionSubscription->DeliveryMethod = 1; // Email
        $socialQuestionSubscription->StartNotificationTime = time();
        // Trigger a notification when new question or new comment is added
        $socialQuestionSubscription->NotificationTriggerOptions->NewContent = true;

        // Add the subscription to the social user's subscription list and save it
        $user->CommunityQuestionSubscriptions[] = $socialQuestionSubscription;

        // Save the social user object
        $this->saveObject($user);
    }

    private function createSocialQuestionProductSubscription ($productID, $user) {
        $socialProductSubscription = new Connect\CommunityProductSubscription();
        $socialProductSubscription->Product = $productID;
        $socialProductSubscription->DeliveryFrequency = 1; // Now
        $socialProductSubscription->DeliveryMethod = 1; // Email
        $socialProductSubscription->StartNotificationTime = time();
        // Trigger a notification when new question or new comment is added
        $socialProductSubscription->NotificationTriggerOptions->NewContent = true;

        // Add the subscription to the social user's subscription list and save it
        $user->CommunityProductSubscriptions[] = $socialProductSubscription;
        $this->saveObject($user);
    }

    private function createSocialQuestionCategorySubscription ($categoryID, $user) {
        $socialCategorySubscription = new Connect\CommunityCategorySubscription();
        $socialCategorySubscription->Category = $categoryID;
        $socialCategorySubscription->DeliveryFrequency = 1; // Now
        $socialCategorySubscription->DeliveryMethod = 1; // Email
        $socialCategorySubscription->StartNotificationTime = time();
        // Trigger a notification when new question or new comment is added
        $socialCategorySubscription->NotificationTriggerOptions->NewContent = true;

        // Add the subscription to the social user's subscription list and save it
        $user->CommunityCategorySubscriptions[] = $socialCategorySubscription;
        $this->saveObject($user);
    }

    private function createMockImportedQuestion () {
        // direct sql doesn't work on hosted sites, so just ignore this function
        if (IS_HOSTED)
            return;

        $question = new Connect\CommunityQuestion();
        $timestamp = $this->getTimestamp();
        $question->CreatedTime = $timestamp;
        $question->UpdatedTime = $timestamp;
        $question->LastActivityTime = $timestamp;
        $question->subject = "[ACTIVE][No Author] Question that simulates imported incomplete data ";
        $question->body = $this->getText();
        $question->CreatedByCommunityUser = null;
        $question->StatusWithType = new Connect\CommunityQuestionStss();
        $question->StatusWithType->Status->ID = $this->questionNameToStatusID('active');
        $this->saveObject($question);
        Connect\ConnectAPI::commit();

        $comment = new Connect\CommunityComment();
        $timestamp = $this->getTimestamp();
        $comment->CreatedTime = $timestamp;
        $comment->UpdatedTime = $timestamp;
        $comment->body = $this->body = $this->getText();
        $comment->CreatedByCommunityUser = null;
        $comment->CommunityQuestion = $question;
        $comment->StatusWithType = new Connect\CommunityCommentStss();
        $comment->StatusWithType->Status->ID = $this->commentNameToStatusID('active');
        $this->saveObject($comment);
        $question->UpdatedTime = $timestamp;
        $question->LastActivityTime = $timestamp;
        $this->saveObject($question);
        Connect\ConnectAPI::commit();

        \RightNow\Api::test_sql_exec_direct("update sss_questions set created_by=null where sss_question_id={$question->ID}");
        \RightNow\Api::test_sql_exec_direct("update sss_question_comments set created_by=null where sss_question_comment_id={$comment->ID}");
    }

    private function saveObject($object, $login = 'useradmin') {
        // useradmin can do just about anything, so default to that user if none is specified
        list($rawProfile, $rawSession) = $this->logIn($login);
        $object->save();
        $this->logOut($rawProfile, $rawSession);
    }

    private function logIn($login) {
        // generate a fresh sessionID each time
        $sessionID = \RightNow\Api::generate_session_id();
        $rawProfile = (object) \RightNow\Api::contact_login(array(
            'login' => $login,
            'sessionid' => $sessionID,
            'cookie_set' => 1,
            'login_method' => CP_LOGIN_METHOD_LOCAL,
        ));
        $rawSession = new \RightNow\Libraries\SessionData(array(
            's' => $rawProfile->sessionid, //Add the one from the contact_login method, just to be sure
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
