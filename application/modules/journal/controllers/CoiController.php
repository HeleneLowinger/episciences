<?php
require_once APPLICATION_PATH . '/modules/common/controllers/PaperDefaultController.php';


class CoiController extends PaperDefaultController
{

    /**
     * @throws Zend_Db_Statement_Exception
     * @throws Zend_Exception
     */
    public function reportAction(): void
    {
        /** @var Zend_Controller_Request_Http $request */
        $request = $this->getRequest();

        $docId = (int)$this->getRequest()->getParam('id');

        $journalSettings = Zend_Registry::get('reviewSettings');
        $paper = Episciences_PapersManager::get($docId);

        // check if paper exists
        if (!$paper || $paper->getRvid() !== RVID) {
            Episciences_Tools::header('HTTP/1.1 404 Not Found');
            $this->renderScript('index/notfound.phtml');
            return;
        }

        $post = $request->getPost();

        $loggedUid = Episciences_Auth::getUid();

        $checkConflictResponse = $paper->checkConflictResponse($loggedUid);

        $isConflictDetected =
            !Episciences_Auth::isSecretary() && isset($journalSettings[Episciences_Review::SETTING_SYSTEM_IS_COI_ENABLED]) &&
            $journalSettings[Episciences_Review::SETTING_SYSTEM_IS_COI_ENABLED] === '1' &&
            (
            in_array($checkConflictResponse, [Episciences_Paper_Conflict::AVAILABLE_ANSWER['yes'], Episciences_Paper_Conflict::AVAILABLE_ANSWER['later']], true)
            );


        if ($isConflictDetected) {

            if ($checkConflictResponse === Episciences_Paper_Conflict::AVAILABLE_ANSWER['later']) {
                $form = Episciences_Paper_ConflictsManager::getCoiForm();
                if (array_key_exists('submit', $post) && $request->isPost() && $form->isValid($post)) {

                    $this->conflictProcessing($post, $paper);
                    return;
                }
                $this->view->paper = $paper;
                $this->view->form = $form;
                return;
            }

            $url = '/' . self::PAPER_URL_STR . $paper->getDocid();


        } else {
            $url = '/' . self::ADMINISTRATE_PAPER_CONTROLLER . '/view?id=' . $paper->getDocid();

        }

        $this->_helper->redirector->gotoUrl($url);
    }

    /**
     * Save reported conflict
     * @param array $post
     * @param Episciences_Paper $paper
     * @throws Zend_Db_Adapter_Exception
     */
    private function conflictProcessing(array $post, Episciences_Paper $paper): void
    {
        $docId = $paper->getDocid();
        $coiReport = $post['coiReport'][0];

        $uid = Episciences_Auth::getUid();

        $url = '/' . self::PAPER_URL_STR . $docId;

        if ($coiReport !== Episciences_Paper_Conflict::AVAILABLE_ANSWER['later']) {

            $conflict = new Episciences_Paper_Conflict([
                'by' => $uid,
                'paper_id' => $paper->getPaperid(),
                'answer' => $coiReport
            ]);


            $latestInsertId = $conflict->save();

            if ($latestInsertId < 1) {
                $message = sprintf("<strong>%s</strong>", $this->view->translate("Votre réponse n'a pas pu être enregistrée."));
                $this->_helper->FlashMessenger->setNamespace('error')->addMessage($message);

            } else {

                $conflict->setCid($latestInsertId);

                try {
                    $conflict->setDate();

                } catch (Exception $e) {
                    trigger_error($e->getMessage(), E_USER_ERROR);
                }

                $details = ['user' => ['fullname' => Episciences_Auth::getFullName()], 'conflict' => $conflict->toArray()];
                $paper->log(Episciences_Paper_Logger::CODE_COI_REPORTED, Episciences_Auth::getUid(), $details);

            }

            if ($coiReport === Episciences_Paper_Conflict::AVAILABLE_ANSWER['no']) {
                $url = '/' . self::ADMINISTRATE_PAPER_CONTROLLER . '/view?id=' . $docId;
            }

            $message = sprintf("<strong>%s</strong>", $this->view->translate("Votre réponse à bien été enregistrée."));
            $this->_helper->FlashMessenger->setNamespace('success')->addMessage($message);

        }

        $this->_helper->redirector->gotoUrl($url);
    }
}
