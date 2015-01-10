<?php

namespace Mission\Controller\Frontend;

use Mission\Entity\Mission;
use PlaygroundGame\Controller\Frontend\GameController;
use Zend\View\Model\ViewModel;
use Zend\Session\Container;
use PlaygroundGame\Service\GameService;

class MissionController extends GameController
{

    /**
     * @var gameService
     */
    protected $gameService;
    protected $mission;

    public function homeAction()
    {

        $identifier = $this->getEvent()->getRouteMatch()->getParam('id');

        $sg = $this->getGameService();

        $game = $sg->checkGame($identifier, false);
        if (!$game) {
            return $this->notFoundAction();
        }

        // This fix exists only for safari in FB on Windows : we need to redirect the user to the page outside of iframe
        // for the cookie to be accepted. PlaygroundCore redirects to the FB Iframed page when
        // it discovers that the user arrives for the first time on the game in FB.
        // When core redirects, it adds a 'redir_fb_page_id' var in the querystring
        // Here, we test if this var exist, and then send the user back to the game in FB.
        // Now the cookie will be accepted by Safari...
        $pageId = $this->params()->fromQuery('redir_fb_page_id');
        if (!empty($pageId)) {
            $appId = 'app_'.$game->getFbAppId();
            $url = '//www.facebook.com/pages/game/'.$pageId.'?sk='.$appId;

            return $this->redirect()->toUrl($url);
        }
        
        // If an entry has already been done during this session, I reset the anonymous_identifier cookie
        // so that another person can play the same game (if game conditions are fullfilled)
        $session = new Container('anonymous_identifier');
        if ($session->offsetExists('anonymous_identifier')) {
            $session->offsetUnset('anonymous_identifier');
        }

        return $this->forward()->dispatch('mission_'.$game->getClassType(), array('controller' => 'mission_'.$game->getClassType(), 'action' => $game->firstStep(), 'id' => $identifier, 'channel' => $this->getEvent()->getRouteMatch()->getParam('channel')));

    }
    

    public function playAction()
    {
        
        $identifier = $this->getEvent()->getRouteMatch()->getParam('id');
        $user = $this->zfcUserAuthentication()->getIdentity();
        $sg = $this->getGameService();

        $game = $sg->checkGame($identifier);
        
        $socialLinkUrl = $this->frontendUrl()->fromRoute('mission', array('id' => $game->getIdentifier(), 'channel' => $this->getEvent()->getRouteMatch()->getParam('channel')), array('force_canonical' => true));

        $session = new Container('facebook');
        $channel = $this->getEvent()->getRouteMatch()->getParam('channel');

        // Redirect to fan gate if the game require to 'like' the page before playing

        if ($channel == 'facebook' && $session->offsetExists('signed_request')) {
            if($game->getFbFan()){
                if ($sg->checkIsFan($game) === false){
                    return $this->redirect()->toRoute($game->getClassType().'/fangate',array('id' => $game->getIdentifier()));
                }
            }
        }

        if (!$user) {

            // The game is deployed on Facebook, and played from Facebook : retrieve/register user

            if ($channel == 'facebook' && $session->offsetExists('signed_request')) {

                // Get Playground user from Facebook info

                $viewModel = $this->buildView($game);
                $beforeLayout = $this->layout()->getTemplate();

                $view = $this->forward()->dispatch('playgrounduser_user', array('controller' => 'playgrounduser_user','action' => 'registerFacebookUser', 'provider' => $channel));

                $this->layout()->setTemplate($beforeLayout);
                $user = $view->user;

                // If the user can not be created/retrieved from Facebook info, redirect to login/register form
                if (!$user){
                    $redirect = urlencode($this->frontendUrl()->fromRoute('mission/play', array('id' => $game->getIdentifier(), 'channel' => $this->getEvent()->getRouteMatch()->getParam('channel')), array('force_canonical' => true)));
                    return $this->redirect()->toUrl($this->frontendUrl()->fromRoute('zfcuser/register', array('channel' => $this->getEvent()->getRouteMatch()->getParam('channel'))) . '?redirect='.$redirect);
                }

                // The game is not played from Facebook : redirect to login/register form

            } elseif(!$game->getAnonymousAllowed()) {
                $redirect = urlencode($this->frontendUrl()->fromRoute('mission/play', array('id' => $game->getIdentifier(), 'channel' => $this->getEvent()->getRouteMatch()->getParam('channel')), array('force_canonical' => true)));
                return $this->redirect()->toUrl($this->frontendUrl()->fromRoute('zfcuser/register', array('channel' => $this->getEvent()->getRouteMatch()->getParam('channel'))) . '?redirect='.$redirect);
            }

        }


        $entry = $sg->play($game, $user);
        if (!$entry) {
            // the user has already taken part of this game and the participation limit has been reached
            $this->flashMessenger()->addMessage('Vous avez déjà participé');

            return $this->redirect()->toUrl($this->frontendUrl()->fromRoute('mission/result',array('id' => $identifier, 'channel' => $this->getEvent()->getRouteMatch()->getParam('channel'))));
        }
        
        if (!$game || $game->isClosed()) {
            return $this->notFoundAction();
        }
        
        if ($this->getRequest()->isPost()) {
            $response = $this->getResponse();
            $data = $this->getRequest()->getPost()->toArray();
        
            $entry = $this->getGameService()->analyzeClue($game, $data, $user);

            if($entry->getActive()){
            $response->setContent(\Zend\Json\Json::encode(array(
                'success' => $entry->getWinner(),
                'url' => $this->frontendUrl()->fromRoute('' . $game->getClassType().'/play', array('id' => $game->getIdentifier(), 'channel' => $channel), array('force_canonical' => true))
            )));
            } else{
                $response->setContent(\Zend\Json\Json::encode(array(
                    'success' => $entry->getWinner(),
                    'url' => $this->frontendUrl()->fromRoute('' . $game->getClassType().'/'.$game->nextStep($this->params('action')), array('id' => $game->getIdentifier(), 'channel' => $channel), array('force_canonical' => true))
                )));
            }
            
            return $response;
        }
        
        $viewModel = $this->buildView($game);
        $viewModel->setVariables(array(
                'game' => $game,
                'flashMessages' => $this->flashMessenger()->getMessages(),
                'step' => $entry->getStep()
            )
        );

        return $viewModel; 
    }

    public function resultAction()
    {
    	$identifier = $this->getEvent()->getRouteMatch()->getParam('id');
        $user = $this->zfcUserAuthentication()->getIdentity();
        $sg = $this->getGameService();

        $statusMail = null;

        $game = $sg->checkGame($identifier);
        if (!$game || $game->isClosed()) {
            return $this->notFoundAction();
        }

        $secretKey = strtoupper(substr(sha1(uniqid('pg_', true).'####'.time()),0,15));
        $socialLinkUrl = $this->frontendUrl()->fromRoute('mission', array('id' => $game->getIdentifier(), 'channel' => $this->getEvent()->getRouteMatch()->getParam('channel')), array('force_canonical' => true)).'?key='.$secretKey;
        // With core shortener helper
        $socialLinkUrl = $this->shortenUrl()->shortenUrl($socialLinkUrl);

        $lastEntry = $this->getGameService()->findLastInactiveEntry($game, $user, $this->params()->fromQuery('anonymous_identifier'));
        if (!$lastEntry) {
            return $this->redirect()->toUrl($this->frontendUrl()->fromRoute('mission', array('id' => $game->getIdentifier(), 'channel' => $this->getEvent()->getRouteMatch()->getParam('channel')), array('force_canonical' => true)));
        }

        if (!$user && !$game->getAnonymousAllowed()) {
            $redirect = urlencode($this->frontendUrl()->fromRoute('mission/result', array('id' => $game->getIdentifier(), 'channel' => $channel)));
            return $this->redirect()->toUrl($this->frontendUrl()->fromRoute('zfcuser/register', array('channel' => $channel)) . '?redirect='.$redirect);
        }

        $form = $this->getServiceLocator()->get('playgroundgame_sharemail_form');
        $form->setAttribute('method', 'post');

        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost()->toArray();
            $form->setData($data);
            if ($form->isValid()) {
                $result = $this->getGameService()->sendShareMail($data, $game, $user, $lastEntry);
                if ($result) {
                    $statusMail = true;
                    //$bonusEntry = $sg->addAnotherChance($game, $user, 1);
                }
            }
        }

        // buildView must be before sendMail because it adds the game template path to the templateStack
        // TODO : Improve this.
        $viewModel = $this->buildView($game);
        
        $this->sendMail($game, $user, $lastEntry);

        $nextGame = parent::getMissionGameService()->checkCondition($game, $lastEntry->getWinner(), true, $lastEntry);

        $viewModel->setVariables(array(
                'statusMail'       => $statusMail,
                'game'             => $game,
                'flashMessages'    => $this->flashMessenger()->getMessages(),
                'form'             => $form,
                'socialLinkUrl'    => $socialLinkUrl,
                'secretKey'		   => $secretKey,
                'nextGame'         => $nextGame,
            )
        );

        return $viewModel;
    }

    public function fbshareAction()
    {
         $sg = $this->getGameService();
         $result = parent::fbshareAction();
         $bonusEntry = false;

         if ($result) {
             $identifier = $this->getEvent()->getRouteMatch()->getParam('id');
             $user = $this->zfcUserAuthentication()->getIdentity();
             $game = $sg->checkGame($identifier);
             $bonusEntry = $sg->addAnotherChance($game, $user, 1);
         }

         $response = $this->getResponse();
         $response->setContent(\Zend\Json\Json::encode(array(
            'success' => $result,
            'playBonus' => $bonusEntry
         )));

         return $response;
    }

    public function fbrequestAction()
    {
        $sg = $this->getGameService();
        $result = parent::fbrequestAction();
        $bonusEntry = false;

        if ($result) {
            $identifier = $this->getEvent()->getRouteMatch()->getParam('id');
            $user = $this->zfcUserAuthentication()->getIdentity();
            $game = $sg->checkGame($identifier);
            $bonusEntry = $sg->addAnotherChance($game, $user, 1);
        }

        $response = $this->getResponse();
        $response->setContent(\Zend\Json\Json::encode(array(
            'success' => $result,
            'playBonus' => $bonusEntry
        )));

        return $response;
    }

    public function tweetAction()
    {
        $sg = $this->getGameService();
        $result = parent::tweetAction();
        $bonusEntry = false;

        if ($result) {
            $identifier = $this->getEvent()->getRouteMatch()->getParam('id');
            $user = $this->zfcUserAuthentication()->getIdentity();
            $game = $sg->checkGame($identifier);
            $bonusEntry = $sg->addAnotherChance($game, $user, 1);
        }

        $response = $this->getResponse();
        $response->setContent(\Zend\Json\Json::encode(array(
            'success' => $result,
            'playBonus' => $bonusEntry
        )));

        return $response;
    }

    public function googleAction()
    {
        $sg = $this->getGameService();
        $result = parent::googleAction();
        $bonusEntry = false;

        if ($result) {
            $identifier = $this->getEvent()->getRouteMatch()->getParam('id');
            $user = $this->zfcUserAuthentication()->getIdentity();
            $game = $sg->checkGame($identifier);
            $bonusEntry = $sg->addAnotherChance($game, $user, 1);
        }

        $response = $this->getResponse();
        $response->setContent(\Zend\Json\Json::encode(array(
            'success' => $result,
            'playBonus' => $bonusEntry
        )));

        return $response;
    }

    public function getGameService()
    {
        if (!$this->gameService) {
            $this->gameService = $this->getServiceLocator()->get('mission_mission_service');
        }

        return $this->gameService;
    }

    public function setGameService(GameService $gameService)
    {
        $this->gameService = $gameService;

        return $this;
    }
}
