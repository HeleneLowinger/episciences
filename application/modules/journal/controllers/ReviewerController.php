<?php
require_once APPLICATION_PATH . '/modules/common/controllers/PaperDefaultController.php';

class ReviewerController extends PaperDefaultController
{
    /**
     * @throws Zend_Mail_Exception
     * @throws Zend_Exception
     * @throws Zend_Form_Exception
     * @throws Zend_Db_Adapter_Exception
     * @throws Zend_Session_Exception
     */
    public function invitationAction(): void
    {
        /** @var Zend_Controller_Request_Http $request */
        $request = $this->getRequest();
        $invitationId = $request->getParam('id');
        $tmp_user = null;

        // check if invitation id is valid
        if (!$invitationId || !is_numeric($invitationId)) {
            $this->view->errors = array("Cette invitation n'existe pas !");
            return;
        }

        // fetch invitation
        $invitation = Episciences_User_InvitationsManager::find(array('ID' => $invitationId));
        if (!$invitation) {
            $this->view->errors = array("Cette invitation n'existe pas !");
            return;
        }

        // fetch assignment
        $assignmentId = $invitation->getAid();
        $assignment = Episciences_User_AssignmentsManager::findById($assignmentId);
        $isLogged = Episciences_Auth::isLogged();

        // check reviewer identity
        if ($assignment->isTmp_user()) {
            // if this is a temp user invitation, check user identity (md5 param)
            $tmp_user = Episciences_TmpUsersManager::findById($assignment->getUid());
            if ($isLogged || !$tmp_user || md5($tmp_user->getEmail()) !== $request->getParam('tmp')) {
                $message = $this->view->translate("Cette invitation ne vous est pas destinée.");
                $this->view->errors = array($message);
                return;
            }

        } elseif (!$isLogged) {
            // user needs to login
            $redirect_params = [
                'controller' => 'user',
                'action' => 'login',
                'forward-controller' => 'reviewer',
                'forward-action' => 'invitation',
                'id' => $invitationId
            ];
            $this->redirect($this->view->url($redirect_params));
            return;
        } elseif ($assignment->getUid() !== Episciences_Auth::getUid()) {
            // user is logged in: check if this invitation is really for him
            $message = $this->view->translate("Cette invitation ne vous est pas destinée.");
            $this->view->errors = array($message);
            return;
        }

        // fetch reviewer answer (if there is one)
        $invitation->loadAnswer();

        // INVITATION
        $this->view->invitation = $invitation;

        $this->view->rating_deadline = $assignment->getDeadline();


        // ARTICLE A RELIRE *******************************************
        $paper = Episciences_PapersManager::get($assignment->getItemid());
        $paper->setXslt($paper->getXml(), 'partial_paper');
        $this->view->paper = $paper;

        // Cover letter, git #160

        $author_comments = Episciences_CommentsManager::getList(
            $paper->getDocid(),
            [
                'type' => Episciences_CommentsManager::TYPE_AUTHOR_COMMENT
            ]);

        $this->view->author_comments = $author_comments;

        // check if paper still needs to be reviewed
        $error = $this->checkPaperStatus($paper);
        if ($error) {
            $this->view->errors = array($error);
            return;
        }

        // answer forms **************************************
        if (!$invitation->hasExpired() && !$invitation->isAnswered()) {

            // empty form created for validation only (real form is in viewscript)
            //$accept_form = new Episciences_User_Form_Create();
            $refuse_form = Episciences_ReviewersManager::refuseInvitationForm();

            if ($assignment->isTmp_user()) {
                $tmp_user->generateScreen_name();
                $user_form = Episciences_ReviewersManager::acceptInvitationForm();
                $user_form->setDefaults(array(
                    'SCREEN_NAME' => $tmp_user->getScreenName(),
                    'LASTNAME' => $tmp_user->getLastname(),
                    'FIRSTNAME' => $tmp_user->getFirstname(),
                    'EMAIL' => $tmp_user->getEmail(),
                    'LANGUEID' => $tmp_user->getLangueid(true)));
                $this->view->user_form = $user_form;
            }

            $accepted = (array_key_exists('submitaccept', $request->getPost()));
            $refused = (array_key_exists('submitrefuse', $request->getPost()));

            if ($accepted || $refused) {

                if (
                    $refused ||
                    (
                        $accepted &&
                        (
                            !$assignment->isTmp_user()  ||
                            (isset($user_form) && $user_form->isValid($request->getPost()))
                        )
                    )
                ) {

                    $this->saveanswer($invitation, $assignment, $paper, $request->getPost());
                    $this->_helper->FlashMessenger->setNamespace('success')->addMessage($this->view->translate("Votre réponse a bien été enregistrée."));

                    // redirect
                    if ($accepted) {
                        $this->_helper->redirector->gotoUrl($this->_helper->url('ratings', 'paper'));
                    } else {
                        $this->redirect('/');
                    }
                } else {
                    $this->view->invalid_form = true;
                }
            }

            $this->view->is_tmp_user = $assignment->isTmp_user();
            $this->view->refuse_form = $refuse_form;

        }
    }

    private function checkPaperStatus(Episciences_Paper $paper): ?string
    {
        $error = null;

        if ($paper->isAccepted()) {
            $error = $this->view->translate("Cet article a déjà été accepté, il n'est plus nécessaire de le relire.");
        } elseif ($paper->isPublished()) {
            $error = $this->view->translate("Cet article a déjà été publié, il n'est plus nécessaire de le relire.");
        } elseif ($paper->isRefused()) {
            $error = $this->view->translate("Cet article a été refusé, il n'est plus nécessaire de le relire.");
        } elseif ($paper->isRemoved() || $paper->isDeleted()) {
            $error = $this->view->translate("Cet article a été supprimé, il n'est plus nécessaire de le relire.");
        } elseif ($paper->isObsolete()) {
            $error = $this->view->translate("Cet article est obsolète, il n'est plus nécessaire de le relire.");
        }

        return $error;
    }

    /**
     * @param Episciences_User_Invitation $oInvitation
     * @param Episciences_User_Assignment $assignment
     * @param Episciences_Paper $paper
     * @param $data
     * @throws Zend_Db_Adapter_Exception
     * @throws Zend_Exception
     * @throws Zend_Mail_Exception
     * @throws Zend_Session_Exception
     */
    private function saveanswer(Episciences_User_Invitation $oInvitation, Episciences_User_Assignment $assignment, Episciences_Paper $paper, $data): void
    {
        if (array_key_exists('submitaccept', $data)) {

            // accepted invitation
            $this->accept($oInvitation, $assignment, $paper, $data);

        } elseif (array_key_exists('submitrefuse', $data)) {

            // declined invitation
            $this->decline($oInvitation, $assignment, $paper, $data);

        }
    }

    /**
     * @param Episciences_User_Invitation $oInvitation
     * @param Episciences_User_Assignment $assignment
     * @param Episciences_Paper $paper
     * @param $data
     * @throws Zend_Db_Adapter_Exception
     * @throws Zend_Exception
     * @throws Zend_Mail_Exception
     * @throws Zend_Session_Exception
     */
    private function accept(Episciences_User_Invitation $oInvitation, Episciences_User_Assignment $assignment, Episciences_Paper $paper, $data): void
    {
        // update user permissions
        if ($assignment->isTmp_user()) {

            $user = $this->createNewReviewerWithoutAccountProcessing($data);

        } else {

            $user = $this->createNewReviewerWithExistingAccountProcessing($assignment->getUid());
        }

        // save invitation answer
        $oInvitationAnswer = new Episciences_User_InvitationAnswer();
        $oInvitationAnswer->setId($oInvitation->getId());
        $oInvitationAnswer->setAnswer(Episciences_User_InvitationAnswer::ANSWER_YES);
        $oInvitationAnswer->save();

        // update invitation status
        $oInvitation->setStatus($oInvitation::STATUS_ACCEPTED);
        $oInvitation->save();

        // paper assignment
        /** @var Episciences_User_Assignment $newAssignment */
        $newAssignment = $user->assign($assignment->getItemid(), array('deadline' => $assignment->getDeadline()))[0];
        $newAssignment->setInvitation_id($oInvitation->getId());
        $newAssignment->save();
        $itemId = $assignment->getItemid();

        // if needed, create an alias
        if ($paper->getPaperid() !== $itemId) { // new version
            if ($user->hasAlias($itemId, false)) {// already has an alias for at least one version
                $user->createAlias($itemId, $user->getAlias($itemId, false));
            } else {
                $user->createAlias($itemId);
            }
        } elseif (!$user->hasAlias($itemId)) { // first version
            $user->createAlias($itemId);
        }

        $uid = $user->getUid();

        // create rating report
        $this->createRatingReport($paper, $uid);

        // log reviewer assignment to paper
        $paper->log(
            Episciences_Paper_Logger::CODE_REVIEWER_INVITATION_ACCEPTED,
            $uid,
            [
                'invitation_answer_id' => $oInvitationAnswer->getId(),
                'invitation_id' => $oInvitation->getId(),
                'assignment_id' => $newAssignment->getId(),
                'user' => array_merge($user->toArray(), ['alias' => $user->getAlias($assignment->getItemid())]),
            ]);

        // update paper status
        $paper->refreshStatus();

        $this->emailSendingProcessing($user, $paper, $newAssignment);
    }

    /**
     * @param Episciences_User_Invitation $oInvitation
     * @param Episciences_User_Assignment $assignment
     * @param Episciences_Paper $paper
     * @param $data
     * @throws Zend_Db_Adapter_Exception
     * @throws Zend_Exception
     * @throws Zend_Mail_Exception
     * @throws Zend_Session_Exception
     */
    private function decline(Episciences_User_Invitation $oInvitation, Episciences_User_Assignment $assignment, Episciences_Paper $paper, $data): void
    {
        // save invitation answer
        $oInvitationAnswer = new Episciences_User_InvitationAnswer();
        $oInvitationAnswer->setId($oInvitation->getId());
        $oInvitationAnswer->setAnswer(Episciences_User_InvitationAnswer::ANSWER_NO);
        $oInvitationAnswer->setDetail(Episciences_User_InvitationAnswer::DETAIL_SUGGEST, $data['suggestreviewer']);
        $oInvitationAnswer->setDetail(Episciences_User_InvitationAnswer::DETAIL_COMMENT, $data['comment']);
        $oInvitationAnswer->save();

        // update invitation status
        $oInvitation->setStatus($oInvitation::STATUS_DECLINED);
        $oInvitation->save();

        $uid = $assignment->getUId();
        if ($assignment->isTmp_user()) {
            $user = new Episciences_User_Tmp();

            if(!empty($user->find($uid))){
                $user->generateScreen_name();
            }

        } else {
            $user = new Episciences_User;
            $user->findWithCAS($uid);
        }

        // save assignment update
        $params = [
            'itemid' => $assignment->getItemid(),
            'item' => Episciences_User_Assignment::ITEM_PAPER,
            'roleid' => Episciences_User_Assignment::ROLE_REVIEWER,
            'status' => Episciences_User_Assignment::STATUS_DECLINED,
            'tmp_user' => $assignment->isTmp_user()
        ];

        $newAssignment = Episciences_UsersManager::unassign($uid, $params)[0];
        $newAssignment->setInvitation_id($oInvitation->getId());
        $newAssignment->save();

        // log reviewer invitation refusal
        $paper->log(
            Episciences_Paper_Logger::CODE_REVIEWER_INVITATION_DECLINED,
            $user->getUid(),
            [
                'invitation_answer_id' => $oInvitationAnswer->getId(),
                'invitation_id' => $oInvitation->getId(),
                'assignment_id' => $newAssignment->getId(),
                'user' => $user->toArray(),
                'reviewer_suggestion' => $data['suggestreviewer'],
                'refusal_reason' => $data['comment']
            ]);

        $this->emailSendingProcessing($user, $paper, $newAssignment, Episciences_User_InvitationAnswer::ANSWER_NO, $data);



    }

    /**
     *  create new user (don't have an account yet)
     * @param array $data
     * @return Episciences_Reviewer
     * @throws Zend_Db_Adapter_Exception
     * @throws Zend_Exception
     */
    private function createNewReviewerWithoutAccountProcessing(array $data): Episciences_Reviewer
    {
        $user = new Episciences_Reviewer($data);
        $user->setTime_registered();
        $user->setValid(1);
        $uid = $user->save();
        $user->setUid($uid);

        // give him reviewer permissions
        $user->saveUserRoles($uid, [Episciences_Acl::ROLE_REVIEWER]);

        // sign him in
        Episciences_Auth::getInstance()->clearIdentity();
        Episciences_Auth::setIdentity($user);
        $user->setScreenName();

        return $user;

    }

    /**
     * Create new reviewer (existing account)
     * @param int $uid
     * @return Episciences_Reviewer
     * @throws Zend_Db_Adapter_Exception
     * @throws Zend_Db_Statement_Exception
     * @throws Zend_Exception
     */
    private function createNewReviewerWithExistingAccountProcessing(int $uid): Episciences_Reviewer
    {
        $isNecessaryToSaveUser = false;

        $user = new Episciences_Reviewer();
        $user->findWithCAS($uid);

        if (!$user->getScreenName()) {
            $isNecessaryToSaveUser = true;
            $user->setScreenName($user->getFullName());
        }

        if (!$user->getLangueid()) {
            $isNecessaryToSaveUser = true;
            $user->setLangueid(Episciences_Review::DEFAULT_LANG);
        }

        if ($isNecessaryToSaveUser) {
            $user->save();
        }

        $uid = $user->getUid();

        $userRoles = $user->getRoles();

        $roles = !in_array(Episciences_Acl::ROLE_REVIEWER, $userRoles, true) ? array_merge($userRoles, array(Episciences_Acl::ROLE_REVIEWER)) : $userRoles;
        $key = array_search(Episciences_Acl::ROLE_MEMBER, $roles, true);

        unset($roles[$key]);

        $user->saveUserRoles($uid, $roles);

        return $user;

    }

    /**
     * send e-mails for reviewer and editorial committee
     * @param Episciences_User $user
     * @param Episciences_paper $paper
     * @param Episciences_User_Assignment $assignment
     * @param string $reviewerAnswer
     * @param array $data
     * @throws Zend_Db_Adapter_Exception
     * @throws Zend_Db_Statement_Exception
     * @throws Zend_Exception
     * @throws Zend_Mail_Exception
     */
    private function emailSendingProcessing(Episciences_User $user, Episciences_paper $paper, Episciences_User_Assignment $assignment, string $reviewerAnswer = Episciences_User_InvitationAnswer::ANSWER_YES, array $data = []): void
    {
        $locale = $user->getLangueid(true);

        $docId = $paper->getDocid();
        $reviewerUid = $user->getUid();

        $ratingUrl = $this->view->url(['controller' => 'paper', 'action' => 'rating', 'id' => $docId]);
        $ratingUrl = HTTP . '://' . $_SERVER['SERVER_NAME'] . $ratingUrl;

        $adminPaperUrl = $this->view->url(['controller' => 'administratepaper', 'action' => 'view', 'id' => $docId]);
        $adminPaperUrl = HTTP . '://' . $_SERVER['SERVER_NAME'] . $adminPaperUrl;

        $reviewerTemplateType = Episciences_Mail_TemplatesManager::TYPE_PAPER_REVIEWER_ACCEPTATION_REVIEWER_COPY;
        $editorialCommitteeTemplateType = Episciences_Mail_TemplatesManager::TYPE_PAPER_REVIEWER_ACCEPTATION_EDITOR_COPY;

        $commonTags = [
            Episciences_Mail_Tags::TAG_ARTICLE_ID => $docId,
            Episciences_Mail_Tags::TAG_AUTHORS_NAMES => $paper->formatAuthorsMetadata()
        ];


        $editorialCommitteeTags = [
            Episciences_Mail_Tags::TAG_REVIEWER_FULLNAME => $user->getFullName(),
            Episciences_Mail_Tags::TAG_REVIEWER_SCREEN_NAME => $user->getScreenName(),
            Episciences_Mail_Tags::TAG_PAPER_URL => $adminPaperUrl
        ];

        $reviewerTags = [Episciences_Mail_Tags::TAG_ARTICLE_TITLE => $paper->getTitle($locale, true)];

        if ($reviewerAnswer === Episciences_User_InvitationAnswer::ANSWER_NO) { // declined

            $reviewerTemplateType = Episciences_Mail_TemplatesManager::TYPE_PAPER_REVIEWER_REFUSAL_REVIEWER_COPY;
            $editorialCommitteeTemplateType = Episciences_Mail_TemplatesManager::TYPE_PAPER_REVIEWER_REFUSAL_EDITOR_COPY;

            if (isset($data['suggestreviewer'])) {
                $commonTags[Episciences_Mail_Tags::TAG_REVIEWER_SUGGESTION] = $data['suggestreviewer'];
            }

            if (isset($data['comment'])) {
                $commonTags[Episciences_Mail_Tags::TAG_REFUSAL_REASON] = $data['comment'];

            }

        } else {

            $reviewerTags = array_merge(
                $reviewerTags, [
                Episciences_Mail_Tags::TAG_PAPER_URL => $ratingUrl,
                Episciences_Mail_Tags::TAG_SUBMISSION_DATE => $this->view->Date($paper->getSubmission_date(), $locale),
                Episciences_Mail_Tags::TAG_RATING_DEADLINE => $this->view->Date($assignment->getDeadline(), $locale)
            ]);

        }

        $reviewerTags = array_merge($commonTags, $reviewerTags);

        $editorialCommitteeTags = array_merge($commonTags, $editorialCommitteeTags);

        Episciences_Mail_Send::sendMailFromReview($user, $reviewerTemplateType, $reviewerTags, $paper);

        //  > editors + admins + secretaries + chief editors notifications
        $recipients = $paper->getEditors(true, true);
        Episciences_Review::checkReviewNotifications($recipients);
        $CC = $paper->extractCCRecipients($recipients);

        if (empty($recipients)) {
            $arrayKeyFirstCC = Episciences_Tools::epi_array_key_first($CC);
            $recipients = !empty($arrayKeyFirstCC) ? [$arrayKeyFirstCC => $CC[$arrayKeyFirstCC]] : [];
            unset($CC[$arrayKeyFirstCC]);
        }

        /** @var Episciences_User $recipient */
        foreach ($recipients as $recipient) {

            if ($reviewerUid === $recipient->getUid()) { // has already been notified as a reviewer
                continue;
            }

            $locale = $recipient->getLangueid(true);

            if ($reviewerAnswer === Episciences_User_InvitationAnswer::ANSWER_YES) {
                $editorialCommitteeTags [Episciences_Mail_Tags::TAG_RATING_DEADLINE] = $this->view->Date($assignment->getDeadline(), $locale);
            }

            $editorialCommitteeTags += [
                Episciences_Mail_Tags::TAG_ARTICLE_TITLE => $paper->getTitle($locale, true),
                Episciences_Mail_Tags::TAG_SUBMISSION_DATE => $this->view->Date($paper->getSubmission_date(), $locale)
            ];

            Episciences_Mail_Send::sendMailFromReview($recipient, $editorialCommitteeTemplateType, $editorialCommitteeTags,
                $paper, null, [], false, $CC
            );
            //reset $CC
            $CC = [];

        }

    }

}
