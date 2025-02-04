<?php

use Laravolt\Avatar\Avatar;
use neverbehave\Hcaptcha;
use ReCaptcha\ReCaptcha;


class UserDefaultController extends Zend_Controller_Action
{

    public const DEFAULT_IMG_PATH = '/../public/img/user.png';

    public function indexAction()
    {
        $this->renderScript('index/submenu.phtml');
    }


    /**
     * view user account
     * @throws Zend_Db_Statement_Exception
     * @throws Exception
     */
    public function viewAction(): void
    {
        $uid = $this->getRequest()->getParam('userid');

        // if uid parameter was given, view another user profile
        if ($uid) {

            // local data (episciences)
            $user = new Episciences_User();
            $uid = (int)$uid;
            $epiUserData = $user->find($uid);

            if (count($epiUserData) === 0) {
                $this->view->message = "Utilisateur inconnu";
                $this->view->description = "Cet utilisateur est inconnu.";
                $this->renderScript('error/error.phtml');
                return;
            }

            // CAS data
            $ccsdUserMapper = new Ccsd_User_Models_UserMapper();
            $ccsdUserMapper->find($uid, $user);

            $identity = $user->toArray();


        } // else, user views his own profile

        else if (Episciences_Auth::isLogged()) {
            $user = Episciences_Auth::getInstance()->getIdentity();
            $identity = Episciences_Auth::getInstance()->getIdentity()->toArray();
        } else {
            $this->view->message = "Vous n'êtes pas connecté";
            $this->view->description = "<a href='/user/login'>Connectez-vous</a>, ou <a href='/user/create'>créez votre compte</a>.";
            $this->renderScript('error/error.phtml');
            return;
        }


        $identity['editorSections'] = null;
        $user->loadRoles();


        if ($user->isChiefEditor() || $user->isEditor() || $user->isGuestEditor()) {
            $userEditor = new Episciences_Editor(['UID' => $user->getUid()]);

            $sections = $userEditor->getAssignedSections();

            if ($sections) {
                $identity['editorSections'] = $sections;
            }
        }


        $this->view->user = $identity;
    }


    /**
     * sign in an admin as another user
     * @throws Zend_Db_Statement_Exception
     * @throws Zend_Exception
     * @throws Zend_Session_Exception
     */
    public function suAction(): void
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->getHelper('layout')->disableLayout();


        $uidFrom = Episciences_Auth::getUid();
        $uidToSu = $this->getRequest()->getParam('uid');

        // Checks if user can use su function
        if ((RVID === 0 && !Episciences_Auth::isRoot()) || (RVID !== 0 && !Episciences_Auth::isRoot() && !Episciences_Auth::isSecretary())) { // git #235
            $this->_helper->FlashMessenger->setNamespace('danger')->addMessage("Vous n'avez pas les privilèges requis pour accéder à cette page.");
            $this->redirect($this->view->url(['controller' => 'user', 'action' => 'index'], null, true));
            return;
        }

        // Checks uid
        if (filter_var($uidToSu, FILTER_VALIDATE_INT) === false) {
            $this->_helper->FlashMessenger->setNamespace('danger')->addMessage("Cet identifiant n'est pas valide.");
            $this->redirect($this->view->url(['controller' => 'user', 'action' => 'list'], null, true));
            return;
        }

        $user = new Episciences_User();
        $res = $user->findWithCAS($uidToSu);

        if ($res === false) {
            $this->_helper->FlashMessenger->setNamespace('danger')->addMessage("Ce compte n'existe pas.");
            $this->redirect($this->view->url(['controller' => 'user', 'action' => 'list'], null, true));
            return;
        }


        Episciences_Auth::getInstance()->clearIdentity();
        Episciences_Auth::setIdentity($user);
        $user->setScreenName();
        Episciences_Auth::incrementPhotoVersion();

        Ccsd_User_Models_UserMapper::suLog($uidFrom, $uidToSu, 'GRANTED', 'episciences');

        Zend_Session::regenerateId();

        $this->_helper->FlashMessenger->setNamespace('success')->addMessage(Zend_Registry::get('Zend_Translate')->translate("Vous êtes connecté en tant que : ") . $user->getScreenName());
        $this->redirect($this->view->url(['controller' => 'user', 'action' => 'dashboard', 'lang' => Episciences_Auth::getLangueid()], null, true));
    }

    /**
     * Login utilisateur
     * Après login redirige :
     * - sur la page de modification de compte si pas de champs Application Episciences
     * - sur la page de destination envoyé en paramètre à CAS
     * - sur le compte utilisateur si pas de page de destination envoyé en
     * paramètre à CAS
     * @throws Zend_Exception
     */
    public function loginAction()
    {
        $localUser = new Episciences_User();

        $casAuthAdapter = new Ccsd_Auth_Adapter_Cas();
        $casAuthAdapter->setIdentityStructure($localUser);
        $casAuthAdapter->setServiceURL($this->_request->getParams());
        $result = Episciences_Auth::getInstance()->authenticate($casAuthAdapter);

        switch ($result->getCode()) {

            case Zend_Auth_Result::FAILURE:
                // on ne devrait jamais arriver là : c'est géré par CAS
                $this->view->message = "Erreur d'authentification";
                $this->view->description = "L'authentification a échoué";
                $this->renderScript('error/error.phtml');
                break;

            case Zend_Auth_Result::SUCCESS:

                Zend_Session::regenerateId();

                // Instance singleton de Episciences_Auth
                $auth = Episciences_Auth::getInstance();

                if ($auth && $auth->hasIdentity()) {
                    /* @var $identity Episciences_User */
                    $identity = $auth->getIdentity();

                    Episciences_Auth::incrementPhotoVersion();

                    // Chargement des rôles
                    $identity->load();
                } else {
                    throw new Zend_Exception("No identity");
                }

                $localUser->hasLocalData(Episciences_Auth::getUid());

                if ($localUser->getHasAccountData() === true) {
                    $localUser->find(Episciences_Auth::getUid());
                    $localeSession = new Zend_Session_Namespace('Zend_Translate');
                    $localeSession->lang = Episciences_Auth::getLangueid();
                } else {
                    $localUser->setScreenName();
                }

                $casAuthAdapter->setIdentityStructure($localUser);

                // pas de données dans la table de Episciences, formulaire pour
                // compléter données utilisateur
                if ($localUser->getHasAccountData() === false) {
                    $action = $this->getRequest()->getParam('forward-action', 'edit');
                    $controller = $this->getRequest()->getParam('forward-controller', 'user');
                    $this->redirect($controller . '/' . $action);
                    return;
                }

                // controller de retour existe
                if (null !== $this->_request->getParam('forward-controller') && $this->_request->getParam('forward-action') !== 'logoutfromcas') {

                    // action existe
                    if (null !== $this->_request->getParam('forward-action')) {

                        // Récupération des paramètres supplémentaires (et suppression de ceux dont on a plus besoin)
                        $params = $this->_request->getParams();
                        unset ($params['forward-controller'],
                            $params['forward-action'],
                            $params['controller'],
                            $params['action'],
                            $params['module']);

                        $params['controller'] = $this->_request->getParam('forward-controller');
                        $params['action'] = $this->_request->getParam('forward-action');

                        if ($params['action'] === 'lostpassword' || $params['action'] === 'resetpassword') {
                            $uri = $this->view->url(['controller' => 'user', 'action' => 'dashboard'], null, true);
                        } else {
                            $uri = $this->view->url($params, null, true);
                        }
                    } else {
                        // pas d'action
                        $uri = $this->view->url([
                            'controller' => $this->_request->getParam('forward-controller')
                        ], null, true);
                    }
                    $this->redirect($uri);
                    return;
                }

                // si pas de controller défini pour le retour
                $this->redirect($this->view->url([
                    'controller' => 'user',
                    'action' => 'dashboard'
                ], null, true));
        }
    }

    /**
     * user logout
     */
    public function logoutAction()
    {

        $scheme = (!isset($_SERVER['HTTPS']) || strtolower($_SERVER['HTTPS']) != 'on') ? "http://" : "https://";
        $urlParams = ['controller' => 'user', 'action' => 'logoutfromcas'];

        if ($this->getParam('reason') == 'passwordupdated') {
            $urlParams = array_merge($urlParams, ['reason' => 'passwordupdated']);
        }

        if (Episciences_Auth::isLogged()) {
            $urlParams = array_merge($urlParams, ['lang' => Episciences_Auth::getLangueid()]);
        }

        $url = $scheme . $_SERVER['HTTP_HOST'] . $this->view->url($urlParams);

        $auth = new Ccsd_Auth_Adapter_Cas();
        $auth->logout($url);
    }

    /**
     * lands here after CAS user logout
     * check if logout was succesful
     */
    public function logoutfromcasAction()
    {
        if (Episciences_Auth::isLogged()) {
            $this->view->message = Ccsd_User_Models_User::LOGOUT_FAILURE;
        } else {
            $this->view->message = Ccsd_User_Models_User::LOGOUT_SUCCESS;
            if ($this->getParam('reason') == 'passwordupdated') {
                $this->view->reason = 'Votre mot de passe a été modifié, pour des raisons de sécurité vous avez été déconnecté(e) afin de terminer les sessions ouvertes.';
            }
        }
        Zend_Session::regenerateId();
    }

    public function findcasusersAction()
    {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
        $res = [];
        $keyword = $_GET['term'];
        $ignore_list = (isset($_GET['ignore_list'])) ? $_GET['ignore_list'] : [];

        if ($keyword) {
            $users = new Ccsd_User_Models_DbTable_User();
            foreach ($users->search($_GET['term'], 100, true) as $user) {
                // if uid is in ignore list, skip this user
                if (in_array($user['UID'], $ignore_list) || in_array($user['EMAIL'], IGNORE_REVIEWERS_EMAIL_VALUES, true)) {
                    continue;
                }
                $fullname = $user['FIRSTNAME'] . ' ' . $user['LASTNAME'];
                $label = $fullname . ' (' . $user['UID'] . ') - ' . $user['EMAIL'];
                $res[] = [
                    'id' => $user['UID'],
                    'email' => $user['EMAIL'],
                    'user_name' => $user['USERNAME'],
                    'full_name' => $fullname,
                    'label' => $label];
            }
        }

        echo Zend_Json::encode($res);
    }

    public function listAction()
    {
        $usersList = Episciences_UsersManager::getAllUsers();

        if ($this->_helper->getHelper('FlashMessenger')->getMessages()) {
            $this->view->flashMessages = $this->_helper->getHelper('FlashMessenger')->getMessages();
        }

        $this->view->autocomplete = $this->autocomplete();
        $this->view->localUsers = $usersList['episciences'];
    }

    private function autocomplete()
    {
        $this->view->jQuery()->addJavascriptFile("/js/vendor/jquery.ui.autocomplete.html.js");
        $this->view->jQuery()->addJavascriptFile('/js/user/functions.js');
        $this->view->jQuery()->addStylesheet(VENDOR_JQUERY_UI_THEME_CSS);

        $form = new Ccsd_Form;
        $form->setAction('/user/create')
            ->setAttrib('id', 'fuser')
            ->setAttrib('class', 'form-horizontal');

        $form->addElement('text', 'autocompletedUserSelection', [
            'label' => 'Ajouter un utilisateur',
            'placeholder' => Zend_Registry::get('Zend_Translate')->translate('Rechercher un utilisateur'),
            'class' => 'form-control'
        ]);

        // Champ caché pour stocker l'id de l'user sélectionné
        $hiddenId = new Zend_Form_Element_Hidden('selectedUserId', ['decorators' => ['decorators' => 'ViewHelper']]);
        $hiddenId->setValue(0);
        $form->addElement($hiddenId);

        $form->addElement('submit', 'select_user', [
            'label' => "Ajouter",
            'class' => 'btn btn-default',
            'disabled' => true,
            'decorators' => [['HtmlTag', ['tag' => 'div', 'openOnly' => true, 'class' => 'form-actions text-center']], 'ViewHelper']]);

        $form->addElement('button', 'create_user', [
            'label' => 'Créer un nouveau compte',
            'class' => 'btn btn-default',
            'onclick' => "subForm()",
            'decorators' => ['ViewHelper', ['HtmlTag', ['tag' => 'div', 'closeOnly' => true]]]]);


        $form->addDisplayGroup(['autocompletedUserSelection', 'select_user', 'or', 'create_user'], 'myDisplayGroup');

        return $form;
    }

    /**
     * create an episciences user account (if needed, also create a CAS account)
     * if logged in user is an admin, the new account is automatically validated, and no mail is sent
     * if user is logged in, but not an admin, he can't create a new account
     * if user is not logged in, this is a classic account creation (account is not validated, and a mail is sent)
     * @throws Zend_Db_Statement_Exception
     * @throws Exception
     */
    public function createAction(): void
    {
        if (Episciences_Auth::isSecretary() || Episciences_Auth::isEditor()) {
            $isAllowedToAddUserAccounts = true;
        } else {
            $isAllowedToAddUserAccounts = false;
        }

        if (!$isAllowedToAddUserAccounts && Episciences_Auth::isLogged()) {
            // already signed in, and not an admin: not allowed to create another account
            $error = Zend_Registry::get('Zend_Translate')->translate("Vous ne pouvez pas créer de compte, car vous en possédez déjà un");
            $this->_helper->FlashMessenger->setNamespace('error')->addMessage($error);
            return;
        }


        if (((CAPTCHA_BRAND === 'RECAPTCHA') || (CAPTCHA_BRAND === 'HCAPTCHA')) && !$isAllowedToAddUserAccounts) {
            $displayCaptcha = true;
        } else {
            $displayCaptcha = false;
        }

        $form = new Episciences_User_Form_Create();
        $form->setAction('/user/create');
        $form->setActions(true)->createSubmitButton('submit', [
            'label' => 'Créer un compte',
            'class' => 'btn btn-primary'
        ]);

        if ($displayCaptcha) {
            if (CAPTCHA_BRAND === 'RECAPTCHA') {
                $datasitekey = RECAPTCHA_PUBKEY;
                $htmlClassId = 'g-recaptcha';
            } elseif (CAPTCHA_BRAND === 'HCAPTCHA') {
                $datasitekey = HCAPTCHA_SITEKEY;
                $htmlClassId = 'h-captcha';
            }
            $form->addElement(
                'hidden',
                'a-fake-element',
                [
                    'required' => false,
                    'ignore' => true,
                    'autoInsertNotEmptyValidator' => false,
                    'decorators' => [
                        [
                            'HtmlTag', [
                            'tag' => 'div',
                            'id' => $htmlClassId,
                            'class' => $htmlClassId,
                            'style' => 'margin-left:20%',
                            'data-sitekey' => $datasitekey]
                        ]
                    ]
                ]
            );
        }

        /** @var Zend_Controller_Request_Http $request */
        $request = $this->getRequest();
        $selectedUserId = (int)$request->getPost('selectedUserId');

        // create an episciences account from a CAS account
        if ($selectedUserId) {

            $user = new Episciences_User();

            if ($user->hasLocalData($selectedUserId) && $user->hasRoles($selectedUserId)) {
                $error = Zend_Registry::get('Zend_Translate')->translate("Cet utilisateur possède déjà un compte Episciences");
                $this->_helper->FlashMessenger->setNamespace('error')->addMessage($error);
            } else {

                // Récupération des données CAS
                $casUserMapper = new Ccsd_User_Models_UserMapper();
                $casUserMapper->find($selectedUserId, $user);
                $user->setScreenName();
                $user->setIs_valid();
                $user->setRegistration_date();
                $user->setModification_date();
                $screenName = $user->getScreenName();

                if ($user->save()) {
                    $success = Zend_Registry::get('Zend_Translate')->translate("L'utilisateur <strong>%%RECIPIENT_SCREEN_NAME%%</strong> a bien été ajouté à Episciences");
                    $success = str_replace('%%RECIPIENT_SCREEN_NAME%%', $screenName, $success);

                    $this->_helper->FlashMessenger->setNamespace('success')->addMessage($success);
                } else {
                    $error = "L'utilisateur <strong>$screenName</strong> n'a pu être ajouté à Episciences";
                    $error = str_replace('%%RECIPIENT_SCREEN_NAME%%', $screenName, $error);
                    $this->_helper->FlashMessenger->setNamespace('error')->addMessage($error);
                }
            }

            $this->_helper->redirector('list', 'user');
            return;
        }

        // create an account (CAS + Episciences)
        if ($request->getPost('submit') && $form->isValid($request->getPost())) {

            $user = new Episciences_User($form->getValues());
            $user->setTime_registered();
            $user->setRegistration_date(); // Episciences registration
            $user->setScreenName(Ccsd_Tools::formatUser($user->getFirstname(), $user->getLastname()));

            if ($isAllowedToAddUserAccounts) {
                // admin: new account does not need to be activated, no mail is sent
                $user->setValid(1);

                $lastInsertId = $user->save();
                try {
                    $user->setUid($lastInsertId);
                } catch (Exception $e) {
                    error_log('Error setUid UID: ' . $lastInsertId);
                }


            } else {
                // regular user: new account is not valid, a mail is sent with an activation link

                if ($displayCaptcha) {
                    if (CAPTCHA_BRAND == 'RECAPTCHA') {
                        $recaptcha = new ReCaptcha(RECAPTCHA_PRIVKEY);
                        $userResponse = $request->getPost('g-recaptcha-response');
                        $resp = $recaptcha->setExpectedHostname($_SERVER['SERVER_NAME'])
                            ->verify($userResponse, $_SERVER['REMOTE_ADDR']);
                    } elseif (CAPTCHA_BRAND == 'HCAPTCHA') {
                        $hcaptcha = new Hcaptcha(HCAPTCHA_SECRETKEY);
                        $userResponse = $request->getPost('h-captcha-response');
                        $resp = $hcaptcha->challenge($userResponse);
                    }

                    if (!$resp->isSuccess()) {
                        $this->_helper->FlashMessenger->setNamespace('error')->addMessage('Merci de compléter le <a target="_blank" rel="noopener" href="https://fr.wikipedia.org/wiki/CAPTCHA">CAPTCHA</a>');
                        $this->view->form = $form;
                        $this->render('create');
                        return;
                    }
                }


                $user->setValid(0);
                $user->setIs_valid(); // Episciences validation

                $lastInsertId = $user->save();
                try {
                    $user->setUid($lastInsertId);
                } catch (Exception $e) {
                    error_log('Error setUid UID: ' . $lastInsertId);
                }

                // activation token
                $userTokenData = ['UID' => $user->getUid(), 'EMAIL' => $user->getEmail()];
                $userToken = new Ccsd_User_Models_UserTokens($userTokenData);
                $userToken->generateUserToken();
                $userToken->setUsage('VALID');
                $userTokenMapper = new Ccsd_User_Models_UserTokensMapper();

                if (!$userTokenMapper->save($userToken)) {
                    $error = "La création du compte a échoué. Merci de réessayer.";
                    error_log($error);
                    $this->_helper->FlashMessenger->setNamespace('error')->addMessage($error);
                    $this->view->form = $form;
                    $this->render('create');
                    return;
                }

                $url = $this->view->url([
                    'controller' => 'user',
                    'action' => 'activate',
                    'token' => $userToken->getToken()], null, true);

                $tokenUrl = APPLICATION_URL . $url;

                $tags = [
                    Episciences_Mail_Tags::TAG_REVIEW_CODE => RVCODE,
                    Episciences_Mail_Tags::TAG_REVIEW_NAME => RVNAME,
                    Episciences_Mail_Tags::TAG_TOKEN_VALIDATION_LINK => $tokenUrl

                ];

                try {   // send mail
                    Episciences_Mail_Send::sendMailFromReview($user, Episciences_Mail_TemplatesManager::TYPE_USER_REGISTRATION, $tags);

                } catch (Zend_Exception $e) {

                    trigger_error($e->getMessage(), E_USER_ERROR);

                }

            }

            $this->view->userEmail = $user->getEmail();
            $this->view->fullUserName = $user->getScreenName();
            $this->view->resultMessage = Ccsd_User_Models_User::ACCOUNT_CREATE_SUCCESS;

            if ($isAllowedToAddUserAccounts) {
                $this->_helper->redirector('list', 'user');
            } else {
                $this->render('create');
            }

            return;
        }

        // Si on arrive ici par autocompletion, on pré-remplit les champs *************
        $input = $request->getPost('autocompletedUserSelection');
        if ($input) {

            $userDefaults = [];
            $validator = new Zend_Validate_EmailAddress();

            if ($validator->isValid($input)) {
                $userDefaults = ['EMAIL' => $input];
            } else {
                $terms = explode(' ', $input, 2);
                if (count($terms) == 2) {
                    $userDefaults = ['FIRSTNAME' => $terms[0], 'LASTNAME' => $terms[1]];
                } elseif (count($terms) == 1) {
                    $userDefaults = ['LASTNAME' => $terms[0]];
                }
            }

            $form->setDefaults($userDefaults);
        }

        $this->view->form = $form;

    }

    /**
     * edit user account
     * @throws Zend_Form_Exception
     */
    public function editAction()
    {
        /** @var Zend_Controller_Request_Http $request */
        $request = $this->getRequest();

        $userUid = !$request->isPost() ? (int)$request->getParam('userid') : (int)$request->getPost('UID');

        $user = new Episciences_User();
        $userId = (!empty($userUid) && Episciences_Auth::isSecretary()) ? $userUid : Episciences_Auth::getUid();

        // Données par défaut du compte CAS
        $ccsdUserMapper = new Ccsd_User_Models_UserMapper();
        $casUserDefaults = $ccsdUserMapper->find($userId, $user);

        if (!$casUserDefaults) {
            $this->_helper->FlashMessenger->setNamespace('danger')->addMessage('No user');
            $this->_helper->redirector('list', 'user');
        }

        // Données par défaut du compte local (Episciences)
        $localUserDefaults = $user->find($userId);

        $userDefaults = $casUserDefaults->toArray();
        $userDefaults = array_merge($userDefaults, $localUserDefaults);

        $form = new Episciences_User_Form_Edit(['UID' => $userId]);
        $form->setAction($this->view->url());
        $form->setActions(true)->createSubmitButton('submit', [
            'label' => 'Enregistrer les modifications',
            'class' => 'btn btn-primary'
        ]);

        $form->setDefaults($userDefaults);


        // update required
        if ($request->isPost() && $form->isValid($request->getPost())) {

            $values = $form->getValues();
            $updatedUserValues = array_merge($localUserDefaults, $values["ccsd"], $values["episciences"]);

            // keep username (not sent with form)
            $updatedUserValues['USERNAME'] = $casUserDefaults['USERNAME'];

            $user = new Episciences_User($updatedUserValues);
            $subform = $form->getSubForm('ccsd');

            if ($subform->PHOTO->isUploaded()) {

                $photoFileName = $subform->PHOTO->getFileName();

                try {
                    $user->savePhoto($photoFileName);
                    Episciences_Auth::incrementPhotoVersion();
                } catch (Exception $e) {
                    $this->_helper->FlashMessenger->setNamespace('danger')->addMessage($e->getMessage());
                }
            }

            if (!$user->save()) {
                $this->view->resultMessage = Ccsd_User_Models_User::ACCOUNT_EDIT_FAILURE;
                $this->view->form = $form;
                $this->render('edit');
                return;
            }

            // Si on modifie son propre compte, on met à jour la session
            if (Episciences_Auth::getUid() == $user->getUid()) {
                $user->setUsername(Episciences_Auth::getUsername()); //sinon le username est supprimé de l'identité : en modification il n'est pas utilisé dans la méthode save()
                Episciences_Auth::setIdentity($user);
                $localeSession = new Zend_Session_Namespace('Zend_Translate');
                $localeSession->lang = Episciences_Auth::getLangueid();
            }

            $this->_helper->FlashMessenger->setNamespace('success')->addMessage('Les modifications sont sauvegardées.');

            if (Episciences_Auth::isSecretary() && Episciences_Auth::getUid() != $user->getUid()) {
                $this->_helper->redirector('list', 'user');
            } else {
                $this->_helper->redirector('dashboard', 'user');
            }

        }

        $this->view->form = $form;
    }

    /**
     * delete an episciences user account
     * (do not remove user from CAS)
     */
    public function deleteAction()
    {
        $request = $this->getRequest();

        $userId = $request->getPost('userId');
        $table = $request->getPost('table');

        $respond = 0;

        if ($table == 'localUsers') {
            $respond = Episciences_User::deleteLocalData($userId);
        } elseif ($table == 'casUsers') {
            $respond = Episciences_User::deleteFromCAS($userId);
        }

        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->getHelper('layout')->disableLayout();
        echo $respond;

    }

    /**
     * user account activation
     * (when activation link has been clicked)
     * @throws Zend_Db_Adapter_Exception
     */
    public function activateAction(): void
    {
        $request = $this->getRequest();
        $token = $request->getParam('token');

        $userTokens = new Ccsd_User_Models_UserTokens(['TOKEN' => $request->getParam('token')]);
        $userTokensMapper = new Ccsd_User_Models_UserTokensMapper();
        $tokenData = $userTokensMapper->findByToken($token, $userTokens);

        // le client essaie d'utiliser un jeton prévu pour autre chose que la validation de compte,
        // ou il n'y a pas de jeton
        if ( empty($tokenData) || 'VALID' !== $userTokens->getUsage()) {
            $this->view->message = "Erreur lors de l'activation du compte";
            $this->view->description = "Erreur le jeton d'activation de ce compte n'est pas valable";
            $this->renderScript('error/error.phtml');
            return;
        }

        $userMapper = new Ccsd_User_Models_UserMapper();
        $userMapper->activateAccountByUid($userTokens->getUid());
        $userTokensMapper->delete($token);
    }

    /**
     * lost user password
     * send the user an e-mail for resetting his password
     * @throws Zend_Exception
     * @throws Zend_Form_Exception
     * @throws Zend_Mail_Exception
     * @throws Zend_Session_Exception
     */
    public function lostpasswordAction()
    {
        if (Episciences_Auth::isLogged()) {
            $this->_helper->redirector('/user/dashboard');
        }
        $form = new Ccsd_User_Form_Accountlostpassword();

        $form->setAction($this->view->url());
        $form->setActions(true)->createSubmitButton('submit', [
            'label' => 'Demander un nouveau mot de passe',
            'class' => 'btn btn-primary'
        ]);

        if ($this->getRequest()->isPost() && $form->isValid($this->getRequest()->getPost())) {

            $userMapper = new Ccsd_User_Models_UserMapper();
            $userInfo = $userMapper->findByUsername($form->getValue('USERNAME'));

            if (!$userInfo || $userInfo->count() === 0) {
                $this->view->resultMessage = Ccsd_User_Models_User::ACCOUNT_INVALID_USERNAME;
                $this->view->form = $form;
                $this->render('lostpassword');
                return;
            }

            $user = new Episciences_User($userInfo->current()->toArray());
            $user->find($user->getUid()); // Récupère les données propres à Episciences

            // Création du token
            $userTokenInfo = $userInfo->current()->toArray();
            $userTokenInfo['USAGE'] = $form->getValue('USAGE');
            $userToken = new Ccsd_User_Models_UserTokens($userTokenInfo);
            $userToken->generateUserToken();
            $userTokenMapper = new Ccsd_User_Models_UserTokensMapper();
            $userTokenMapper->save($userToken);

            $this->view->userEmail = $user->getEmail();
            $this->view->fullUserName = $user->getFullName();
            $this->view->resultMessage = Ccsd_User_Models_User::ACCOUNT_VALID_USERNAME;

            /**
             * Envoi du mail
             */
            $locale = $user->getLangueid(true);
            $template = new Episciences_Mail_Template();
            $template->findByKey(Episciences_Mail_TemplatesManager::TYPE_USER_LOST_PASSWORD);
            $template->loadTranslations();
            $template->setLocale($locale);

            $mail = new Episciences_Mail('UTF-8');
            // prepare retrieve password link
            $url = APPLICATION_URL . $this->view->url([
                    'controller' => 'user',
                    'action' => 'resetpassword',
                    'lang' => $locale,
                    'token' => $userToken->getToken()
                ], null, true);
            $mail->addTag(Episciences_Mail_Tags::TAG_TOKEN_VALIDATION_LINK, $url);
            $mail->setFromReview();
            $mail->setTo($user);
            $mail->setSubject($template->getSubject());
            $mail->setTemplate($template->getPath(), $template->getKey() . '.phtml');
            $mail->writeMail();

            $this->render('lostpassword');
            return;
        }

        $this->view->form = $form;
    }

    /**
     * lost user login
     * send the user an e-mail with a list of his logins
     * @throws Exception
     */
    public function lostloginAction(): void
    {
        if (Episciences_Auth::isLogged()) {
            $this->redirect('/user/dashboard');
            return;
        }

        $form = new Ccsd_User_Form_Accountlostlogin();
        $form->setActions(true)->createSubmitButton('submit', [
            'label' => 'Recevoir mon login',
            'class' => 'btn btn-primary'
        ]);

        $request = $this->getRequest();

        if ($this->getRequest()->isPost() && $form->isValid($request->getPost())) {

            $user = new Episciences_User($form->getValues());

            $userMapper = new Ccsd_User_Models_UserMapper();
            $userLogins = $userMapper->findLoginByEmail($form->getValue('EMAIL'));

            if ($userLogins !== null) {
                $userLogins = $userLogins->toArray();
            }

            // login non trouvé
            if (empty($userLogins)) {
                $this->view->resultMessage = Ccsd_User_Models_User::ACCOUNT_LOST_LOGIN_NOT_FOUND;
                $this->view->form = $form;
                $this->render('lostlogin');
                return;
            }

            // liste des logins trouvés + mention compte validé ou non
            $listeUserLogins = '';

            try {
                $unValidatedAccount = Zend_Registry::get('Zend_Translate')->translate(" (Vous n'avez pas encore validé ce compte par le courriel de validation)");
            } catch (Exception $e) {
                $unValidatedAccount = " (Vous n'avez pas encore validé ce compte par le courriel de validation)";
            }
            foreach ($userLogins as $login) {
                $listeUserLogins .= '- ' . $login['USERNAME'];
                if ($login['VALID'] == 0) {
                    $listeUserLogins .= $unValidatedAccount;
                }
                $listeUserLogins .= "\n";
            }

            $this->view->userEmail = $form->getValue('EMAIL');
            $this->view->resultMessage = Ccsd_User_Models_User::ACCOUNT_LOST_LOGIN_FOUND;


            /**
             * Envoi du mail
             */

            $template = new Episciences_Mail_Template();
            $template->findByKey(Episciences_Mail_TemplatesManager::TYPE_USER_LOST_LOGIN);
            $template->loadTranslations();
            $template->setLocale(Episciences_Tools::getLocale());

            $mail = new Episciences_Mail('UTF-8');
            $mail->addTag(Episciences_Mail_Tags::TAG_MAIL_ACCOUNT_USERNAME_LIST, $listeUserLogins);
            $mail->setFromReview();
            $mail->setTo($user);
            $mail->setSubject($template->getSubject());
            $mail->setTemplate($template->getPath(), $template->getKey() . '.phtml');
            $mail->writeMail();

            $this->render('lostlogin');
            return;
        }

        $this->view->form = $form;
    }

    /**
     * reset user password
     * @throws Exception
     */
    public function resetpasswordAction(): void
    {
        $request = $this->getRequest();
        $token = $request->getParam('token');

        $userTokens = new Ccsd_User_Models_UserTokens(['TOKEN' => $request->getParam('token')]);
        $userTokensMapper = new Ccsd_User_Models_UserTokensMapper();
        $tokenData = $userTokensMapper->findByToken($token, $userTokens);

        // le client essaie d'utiliser un jeton prévu pour autre chose que les
        // mots de passe
        if (empty($tokenData)) {
            $this->view->resultMessage = Ccsd_User_Models_User::ACCOUNT_RESET_PASSWORD_FAILURE;
            $this->render('resetpassword');
        }

        $form = new Ccsd_User_Form_Accountresetpassword();
        $form->setActions(true)->createSubmitButton('submit', [
            'label' => 'Changer le mot de passe',
            'class' => 'btn btn-primary'
        ]);
        $form->setDefault('token', $token);

        if ($this->getRequest()->isPost() && $form->isValid($request->getPost())) {

            $formToken = $form->getValue('token');
            $userTokens = new Ccsd_User_Models_UserTokens(['TOKEN' => $token]);
            $userTokensMapper = new Ccsd_User_Models_UserTokensMapper();

            $tokenData = $userTokensMapper->findByToken($formToken, $userTokens);

            if (!empty($tokenData)) {

                $user = new Ccsd_User_Models_User();
                $userMapper = new Ccsd_User_Models_UserMapper();

                try {
                    $user->setUid($tokenData['UID']);
                    $user->setPassword($form->getValue('PASSWORD'));
                    $user->setTime_modified();

                } catch (Exception $e) {
                    trigger_error($e->getMessage(), E_USER_ERROR);
                }

                $userMapper->savePassword($user);
                $userTokensMapper->delete($formToken);
                $this->view->resultMessage = Ccsd_User_Models_User::ACCOUNT_RESET_PASSWORD_SUCCESS;

                $this->render('resetpassword');

                return;
            }
        }

        $this->view->form = $form;
    }

    /**
     * Change User password
     * @throws Exception
     */
    public function changepasswordAction(): void
    {

        // Retour de l'activation OK
        if ($this->getRequest()->getParam('change') == 'done') {
            $this->render('changepassword');
            return;
        }

        $form = new Ccsd_User_Form_Accountchangepassword();
        $form->setAction($this->view->url());
        $form->setActions(true)->createSubmitButton('submit', [
            'label' => 'Changer le mot de passe',
            'class' => 'btn btn-primary'
        ]);

        if ($this->getRequest()->isPost() && $form->isValid($this->getRequest()
                ->getPost())) {

            $user = new Ccsd_User_Models_User();
            $userMapper = new Ccsd_User_Models_UserMapper();

            $testPreviousPassword = $userMapper->findByUsernamePassword(Episciences_Auth::getInstance()->getIdentity()
                ->getUsername(), $form->getValue('PREVIOUS_PASSWORD'));

            if ($testPreviousPassword === null) {

                $this->view->resultMessage = $this->view->message("Votre ancien mot de passe n'est pas correct.", 'danger');
                $this->view->form = $form;
                $this->render('changepassword');
                return;
            }

            try {

                $user->setUid(Episciences_Auth::getUid());
                $user->setPassword($form->getValue('PASSWORD'));
                $user->setTime_modified();
                $affectedRows = $userMapper->savePassword($user);

                if ($affectedRows === 1) {
                    $this->redirect('/user/logout/reason/passwordupdated/lang/' . Episciences_Auth::getLangueid());
                } else {
                    $this->view->resultMessage = $this->view->message("Échec de la modification. Votre mot de passe n'a pas été changé.", 'danger');
                    $this->render('changepassword');
                }
            } catch (Exception $e) {
                $this->view->resultMessage = $this->view->message("Échec de la modification. Votre mot de passe n'a pas été changé.", 'danger');
                $this->render('changepassword');
            }
        }

        $this->view->form = $form;
    }

    // GESTION DES ROLES *******************************************************************************************

    /**
     * fetch users list for autocomplete
     * use "term" param for searching users in database
     */
    public function findusersAction(): void
    {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender();

        $request = $this->getRequest();
        $users = [];

        if($request){

            $filter = $request->getParam('term');
            $withoutRoles = ($request->getParam('type') !== 'all');
            $result = Episciences_User::filterUsers($filter, $withoutRoles);

            if (!empty($result)) {

                foreach ($result as $key => $user) {
                    $name = $user['LASTNAME'];
                    if ($user['FIRSTNAME'] !== '') {
                        $name = $user['FIRSTNAME'] . ' ' . $name;
                    }
                    $label = $name . ' (' . mb_strtolower($user['USERNAME'], 'UTF-8') . ')' . ' <' . $user['EMAIL'] . '>';

                    $users[$key]['uid'] = $user['UID'];
                    $users[$key]['name'] = $name;
                    $users[$key]['mail'] = $user['EMAIL'];
                    $users[$key]['label'] = $label;
                }
            }
        }

        echo Zend_Json::encode(array_values($users));
    }

    /**
     * Retourne tous les logins associés à un email et les détails d'un compte utilisateurs par login
     * @throws Exception
     */
    public function ajaxfindusersbymailAction(): void
    {
        $result = '';
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();
        $detailByLogin = [];
        if ($request && $request->isXmlHttpRequest()) {
            $userMapper = new Ccsd_User_Models_UserMapper();
            /** @var Zend_Db_Table_Rowset_Abstract $rowSet */
            $rowSet = $userMapper->findLoginByEmail($request->getPost('email'));
            if ($rowSet) {
                $rowsetArray = $rowSet->toArray();
                foreach ($rowsetArray as $array) {
                    $login = $array['USERNAME'];
                    $resRowset = $userMapper->findByUsernameOrUID($login, false);
                    if ($resRowset) {
                        $detailByLogin[$login] = $resRowset->toArray()[0];
                    }
                }
            }
        }

        try {
            $result = json_encode($detailByLogin, JSON_THROW_ON_ERROR);

        }catch (Exception $e){
            trigger_error($e->getMessage(), E_USER_WARNING);
        }

        echo $result;

    }

    /**
     * retourne tous les utilisateurs ayant le même nom et prénom
     * @throws Exception
     */

    public function findusersbyfirstnameandnameAction(): void
    {
        $result = [];

        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender();

        $request = $this->getRequest();

        if ($request) {
            $firstName = $request->getPost('firstname');

            $name = $request->getPost('name');

            if ($request->isXmlHttprequest()) {
                $userMapper = new Episciences_User_UserMapper();
                /** @var Zend_Db_Table_Rowset_Abstract $rowSet */
                $rowSet = $userMapper->findUserByFirstNameAndName($name, $firstName);
                if ($rowSet) {
                    $result = $rowSet->toArray();
                }

            }

        }

        echo json_encode($result);
    }

    public function ajaxfindcasuserAction(): void
    {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
        $user = null;

        $request = $this->getRequest();

        if($request){
            $uid = $request->getPost('uid');

            if ($request->isXmlHttprequest()) {
                $user = new Episciences_User();
                $user->findWithCAS($uid);

            }

        }

        echo Zend_Json::encode($user);
    }

    /**
     * user roles form
     * @throws Zend_Db_Statement_Exception
     * @throws Zend_Form_Exception
     */
    public function rolesformAction(): void
    {
        $this->view->jQuery()->addJavascriptFile('/js/reviewer/functions.js');

        $request = $this->getRequest();

        if ($request) {

            $params = $request->getPost();
            $uid = $params['uid'];

            $user = new Episciences_User();
            $user->find($uid);
            $userRoles = $user->getRoles();

            $acl = new Episciences_Acl();
            $roles = $acl->getEditableRoles();

            $form = new Zend_Form();
            $form->setAction('/user/saveroles');
            $element = new Zend_Form_Element_MultiCheckbox('roles_' . $uid, ['multiOptions' => $roles]);
            $element->setValue($userRoles);
            $element->setSeparator('<br/>');
            $element->removeDecorator('Label');
            $element->addDecorator('HtmlTag', ['tag' => 'div', 'class' => "checkbox"]);
            //$element->getDecorator('HtmlTag')->setOption('tag', 'div');
            $form->addElement($element);

            $button = new Zend_Form_Element_Button('updateUserRoles');
            $button->setLabel("Valider")
                ->setOptions(["class" => "btn btn-sm btn-primary"])
                ->removeDecorator('DtDdWrapper')
                ->removeDecorator('Label')
                ->setAttrib('type', 'submit');
            $form->addElement($button);

            $button = new Zend_Form_Element_Button('cancel');
            $button->setLabel("Annuler")
                ->setOptions(["class" => "btn btn-sm btn-default"])
                ->removeDecorator('DtDdWrapper')
                ->removeDecorator('HtmlTag')
                ->removeDecorator('Label')
                ->setAttrib('onclick', 'closeResult()');
            $form->addElement($button);

            $this->_helper->layout->disableLayout();
            $this->view->form = $form;
        }
    }

    /**
     * save user roles
     */
    public function saverolesAction()
    {
        $request = $this->getRequest();
        $params = $request->getPost();
        $uid = $params['uid'];

        if (array_key_exists('roles_' . $uid, $params)) {
            $roles = $params['roles_' . $uid];
        } else {
            $roles = [];
        }

        $user = new Episciences_User();
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        if ($user->saveUserRoles($uid, $roles)) {
            echo 1;
        } else {
            echo 0;
        }
    }

    /**
     * partial render: display user role tags
     * use "uid" param for searching user in database
     */
    public function displaytagsAction()
    {
        $request = $this->getRequest();
        $uid = $request->getPost('uid');

        $user = new Episciences_User();
        $user->find($uid);
        $userRoles = $user->getRoles();
        if (count($userRoles) > 1) {
            foreach (array_keys($userRoles, Episciences_Acl::ROLE_MEMBER) as $key) {
                unset ($userRoles[$key]);
            }
        }
        $this->view->roles = $userRoles;
        $this->renderScript('partials/user_roles.phtml');
        $this->_helper->layout->disableLayout();
    }

    /**
     * fetch users e-mail addresses for autocompletion
     * use "term" param for searching users database
     */
    public function getmailsAction()
    {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender();

        $request = $this->getRequest();
        $query = $request->getQuery('term');

        //Retourne une liste de destinataires
        if ($request->isXmlHttpRequest()) {
            $users = Episciences_User::filterUsers($query, false);
            foreach ($users as &$user) {
                $user['label'] = htmlentities($user['SCREENNAME'] . ' <' . $user['EMAIL'] . '>');
            }
            echo Zend_Json::encode($users);
        }
    }

    public function ajaxdeletephotoAction()
    {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $params = $this->getRequest()->getPost();

        $res = false;
        if ($this->getRequest()->isXmlHttpRequest() && isset($params['uid'])) {
            if (Episciences_Auth::getUid() == $params['uid'] || Episciences_Auth::isSecretary()) {
                $user = new Ccsd_User_Models_User(['uid' => $params['uid']]);
                $user->deletePhoto();
                Episciences_Auth::incrementPhotoVersion();
                if (Episciences_Auth::getUid() == $params['uid']) {
                    $res = '1';
                } else {
                    $res = '2';
                }
            }
        }
        if ($res === false) {
            header("HTTP/1.0 404 Not Found");
            exit();
        }
        echo $res;
    }

    public function photoAction()
    {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
        $uid = $this->getParam('uid', 0);
        $size = $this->getParam('size', Ccsd_User_Models_User::IMG_NAME_NORMAL);

        $photoPathName = false;
        $data = false;
        $imageMimeType = 'image/jpg';

        $uid = (int)$uid;
        switch ($size) {
            case Ccsd_User_Models_User::IMG_NAME_INITIALS:
                $screenName = $this->getParam('name');
                $screenName = urldecode($screenName);
                $screenName = filter_var($screenName, FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
                $imageMimeType = 'image/svg+xml';
                break;
            case Ccsd_User_Models_User::IMG_NAME_THUMB:
            case Ccsd_User_Models_User::IMG_NAME_NORMAL:
            case Ccsd_User_Models_User::IMG_NAME_LARGE:
                break;
            default:
                $size = Ccsd_User_Models_User::IMG_NAME_NORMAL;
                break;
        }


        // photo of a specific user
        if ($uid != 0) {
            $user = new Ccsd_User_Models_User(['uid' => $uid]);
            $photoPathName = $user->getPhotoPathName($size);
        } else {
            // nobody or logged user
            $uid = Episciences_Auth::getUid();
            if ($uid != 0) {
                $user = new Ccsd_User_Models_User(['uid' => $uid]);
                $photoPathName = $user->getPhotoPathName($size);
            }
        }

        if (!$photoPathName) {
            if ($size === Ccsd_User_Models_User::IMG_NAME_INITIALS) {

                $userPhotoPath = $user->getPhotoPath();

                if (!is_dir($userPhotoPath) && !mkdir($userPhotoPath, 0777, true) && !is_dir($userPhotoPath)) {
                    trigger_error(sprintf('Directory "%s" was not created', $userPhotoPath), E_USER_WARNING);
                }
                $photoPathName = $userPhotoPath . '/' . Ccsd_User_Models_User::IMG_PREFIX_INITIALS . $user->getUid() . '.svg';
               $data = Episciences_View_Helper_GetAvatar::asSvg($screenName);
                file_put_contents($photoPathName, $data);


            } else {
                $photoPathName = APPLICATION_PATH . self::DEFAULT_IMG_PATH;
            }
        }

        $modifiedTime = filemtime($photoPathName);
        $size = filesize($photoPathName);
        if (!$data) {
            $data = file_get_contents($photoPathName);
        }
        $maxAge = 3600;

        $expires = gmdate('D, d M Y H:i:s \G\M\T', time() + $maxAge);


        $this->getResponse()
            ->setHeader('Last-Modified', $modifiedTime, true)
            ->setHeader('ETag', md5($modifiedTime), true)
            ->setHeader('Expires', $expires, true)
            ->setHeader('Pragma', '', true)
            ->setHeader('Cache-Control', 'private, max-age=' . $maxAge, true)
            ->setHeader('Content-Type', $imageMimeType, true)
            ->setHeader('Content-Length', $size, true)
            ->setBody($data);


    }

}
