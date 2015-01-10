<?php

namespace PlaygroundMission\Controller\Admin;

use PlaygroundGame\Entity\Game;

use PlaygroundMission\Entity\Mission;
use PlaygroundMission\Entity\MissionPuzzle;

use PlaygroundGame\Controller\Admin\GameController;
use Zend\View\Model\ViewModel;

class MissionController extends GameController
{
    /**
     * @var GameService
     */
    protected $adminGameService;

    protected $mission;

    public function createMissionAction()
    {
        $service = $this->getAdminGameService();
        $viewModel = new ViewModel();
        $viewModel->setTemplate('mission/mission/mission');

        $gameForm = new ViewModel();
        $gameForm->setTemplate('playground-game/admin/game-form');

        $mission = new Mission();

        $form = $this->getServiceLocator()->get('mission_mission_form');
        $form->bind($mission);
        $form->get('submit')->setAttribute('label', 'Add');
        $form->setAttribute('action', $this->url()->fromRoute('admin/playgroundgame/create-mission', array('gameId' => 0)));
        $form->setAttribute('method', 'post');

        $request = $this->getRequest();
        if ($request->isPost()) {
            $data = array_replace_recursive(
                $this->getRequest()->getPost()->toArray(),
                $this->getRequest()->getFiles()->toArray()
            );
            if(empty($data['prizes'])){
                $data['prizes'] = array();
            }
            $game = $service->create($data, $mission, 'mission_mission_form');
            if ($game) {

                $this->flashMessenger()->setNamespace('mission')->addMessage('The game was created');

                return $this->redirect()->toRoute('admin/playgroundgame/list');
            }
        }
        $gameForm->setVariables(array('form' => $form, 'game' => $mission));
        $viewModel->addChild($gameForm, 'game_form');

        return $viewModel->setVariables(array('form' => $form, 'title' => 'Create mission', 'mission' => $mission));
    }

    public function areapickerAction()
    {
    	$viewModel = new ViewModel();
    	$viewModel->setTerminal(true);
    	$viewModel->setTemplate('mission/mission/areapicker');
    	return $viewModel;
    }

    public function editMissionAction()
    {
        $service = $this->getAdminGameService();
        $gameId = $this->getEvent()->getRouteMatch()->getParam('gameId');

        if (!$gameId) {
            return $this->redirect()->toRoute('admin/playgroundgame/createMission');
        }

        $game = $service->getGameMapper()->findById($gameId);

        $viewModel = new ViewModel();
        $viewModel->setTemplate('mission/mission/mission');

        $gameForm = new ViewModel();
        $gameForm->setTemplate('playground-game/admin/game-form');

        $form   = $this->getServiceLocator()->get('mission_mission_form');
        $form->setAttribute('action', $this->url()->fromRoute('admin/playgroundgame/edit-mission', array('gameId' => $gameId)));
        $form->setAttribute('method', 'post');
        if ($game->getFbAppId()) {
            $appIds = $form->get('fbAppId')->getOption('value_options');
            $appIds[$game->getFbAppId()] = $game->getFbAppId();
            $form->get('fbAppId')->setAttribute('options', $appIds);
        }

        $gameOptions = $this->getAdminGameService()->getOptions();
        $gameStylesheet = $gameOptions->getMediaPath() . '/' . 'stylesheet_'. $game->getId(). '.css';
        if (is_file($gameStylesheet)) {
            $values = $form->get('stylesheet')->getValueOptions();
            $values[$gameStylesheet] = 'Style personnalisé de ce jeu';

            $form->get('stylesheet')->setAttribute('options', $values);
        }

        $form->bind($game);

        if ($this->getRequest()->isPost()) {
            $data = array_replace_recursive(
                $this->getRequest()->getPost()->toArray(),
                $this->getRequest()->getFiles()->toArray()
            );
            if(empty($data['prizes'])){
                $data['prizes'] = array();
            }

            $result = $service->edit($data, $game, 'mission_mission_form');

            if ($result) {
                return $this->redirect()->toRoute('admin/playgroundgame/list');
            }
        }

        $gameForm->setVariables(array('form' => $form, 'game' => $game));
        $viewModel->addChild($gameForm, 'game_form');

        return $viewModel->setVariables(array('form' => $form, 'title' => 'Edit mission', 'mission' => $game));
    }


    public function puzzleDeleteImageAction()
    {
        $service = $this->getAdminGameService();
        $gameId = $this->getEvent()->getRouteMatch()->getParam('gameId');
        $puzzleId = $this->getEvent()->getRouteMatch()->getParam('puzzleId');
        $imageId = $this->getEvent()->getRouteMatch()->getParam('imageId');
        
        if (!$puzzleId) {
            return $this->redirect()->toRoute('admin/playgroundgame/list');
        }
        $puzzle   = $service->getMissionPuzzleMapper()->findById($puzzleId);

        $images = json_decode($puzzle->getImage(), true);

        unset($images[$imageId]);

        $puzzle->setImage(json_encode($images));
        $service->getMissionPuzzleMapper()->update($puzzle);

        return $this->redirect()->toRoute('admin/playgroundgame/mission-puzzle-edit', array('gameId'=>$gameId, 'puzzleId'=>$puzzleId));
    }

    public function listPuzzleAction()
    {
    	$service 	= $this->getAdminGameService();
    	$gameId 	= $this->getEvent()->getRouteMatch()->getParam('gameId');
    	$filter		= $this->getEvent()->getRouteMatch()->getParam('filter');

    	if (!$gameId) {
    		return $this->redirect()->toRoute('admin/playgroundgame/list');
    	}

    	$puzzles = $service->getMissionPuzzleMapper()->findByGameId($gameId);
    	$game = $service->getGameMapper()->findById($gameId);

    	if (is_array($puzzles)) {
    		$paginator = new \Zend\Paginator\Paginator(new \Zend\Paginator\Adapter\ArrayAdapter($puzzles));
    		$paginator->setItemCountPerPage(50);
    		$paginator->setCurrentPageNumber($this->getEvent()->getRouteMatch()->getParam('p'));
    	} else {
    		$paginator = $puzzles;
    	}

    	return new ViewModel(
    			array(
    					'puzzles'     => $paginator,
    					'gameId' 	  => $gameId,
    					'filter'	  => $filter,
    					'game' 		  => $game,
    			)
    	);
    }

    public function addPuzzleAction()
    {
    	$viewModel = new ViewModel();
    	$viewModel->setTemplate('mission/mission/puzzle');
    	$service = $this->getAdminGameService();
    	$gameId = $this->getEvent()->getRouteMatch()->getParam('gameId');
    	if (!$gameId) {
    		return $this->redirect()->toRoute('admin/playgroundgame/list');
    	}

    	$form = $this->getServiceLocator()->get('mission_missionpuzzle_form');
    	$form->get('submit')->setAttribute('label', 'Add');
    	$form->setAttribute('action', $this->url()->fromRoute('admin/playgroundgame/mission-puzzle-add', array('gameId' => $gameId)));
    	$form->setAttribute('method', 'post');
    	$form->get('mission_id')->setAttribute('value', $gameId);

    	$puzzle = new MissionPuzzle();
    	$form->bind($puzzle);

    	if ($this->getRequest()->isPost()) {
    		$data = array_merge(
    			$this->getRequest()->getPost()->toArray(),
    			$this->getRequest()->getFiles()->toArray()
    		);

    		$puzzle = $service->createPuzzle($data);
    		if ($puzzle) {
    		    $service->uploadImages($puzzle, $data);
    			// Redirect to list of games
    			$this->flashMessenger()->setNamespace('mission')->addMessage('The puzzle was created');

    			return $this->redirect()->toRoute('admin/playgroundgame/mission-puzzle-list', array('gameId'=>$gameId));
    		}
    	}

    	return $viewModel->setVariables(
    		array(
    			'form'       => $form,
   				'gameId'     => $gameId,
   				'puzzle_id'  => 0,
   				'title'      => 'Add puzzle',
    		    'puzzle'     => $puzzle
    		)
    	);
    }

    public function editPuzzleAction()
    {
    	$service = $this->getAdminGameService();
    	$viewModel = new ViewModel();
    	$viewModel->setTemplate('mission/mission/puzzle');

    	$gameId = $this->getEvent()->getRouteMatch()->getParam('gameId');

    	$puzzleId = $this->getEvent()->getRouteMatch()->getParam('puzzleId');
    	if (!$puzzleId) {
    		return $this->redirect()->toRoute('admin/playgroundgame/list');
    	}
    	$puzzle   = $service->getMissionPuzzleMapper()->findById($puzzleId);
    	$missionId     = $puzzle->getMission()->getId();

    	$form = $this->getServiceLocator()->get('mission_missionpuzzle_form');
    	$form->get('submit')->setAttribute('label', 'Add');
    	$form->get('mission_id')->setAttribute('value', $missionId);

    	$form->bind($puzzle);

    	if ($this->getRequest()->isPost()) {
    		$data = array_merge(
    				$this->getRequest()->getPost()->toArray(),
    				$this->getRequest()->getFiles()->toArray()
    		);
    		$puzzle = $service->updatePuzzle($data, $puzzle);
    		if ($puzzle) {
    		    $service->uploadImages($puzzle, $data);
    			// Redirect to list of games
    			$this->flashMessenger()->setNamespace('mission')->addMessage('The puzzle was updated');

    			return $this->redirect()->toRoute('admin/playgroundgame/mission-puzzle-list', array('gameId'=>$missionId));
    		}
    	}

    	return $viewModel->setVariables(
    		array(
    			'form'       => $form,
    			'gameId'     => $missionId,
   				'puzzle_id'  => $puzzleId,
   				'title'      => 'Edit puzzle',
    		    'puzzle'     => $puzzle
   			)
    	);
    }

    public function removePuzzleAction()
    {
    	$service = $this->getAdminGameService();
    	$puzzleId = $this->getEvent()->getRouteMatch()->getParam('puzzleId');
    	if (!$puzzleId) {
    		return $this->redirect()->toRoute('admin/playgroundgame/list');
    	}
    	$puzzle   = $service->getMissionPuzzleMapper()->findById($puzzleId);
    	$missionId = $puzzle->getMission()->getId();

    	$service->getMissionPuzzleMapper()->remove($puzzle);
    	$this->flashMessenger()->setNamespace('mission')->addMessage('The puzzle was deleted');

    	return $this->redirect()->toRoute('admin/missionadmin/mission-puzzle-list', array('gameId'=>$missionId));
    }

    public function getAdminGameService()
    {
        if (!$this->adminGameService) {
            $this->adminGameService = $this->getServiceLocator()->get('mission_mission_service');
        }

        return $this->adminGameService;
    }

    public function setAdminGameService(AdminGameService $adminGameService)
    {
        $this->adminGameService = $adminGameService;

        return $this;
    }
}
