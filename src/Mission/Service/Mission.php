<?php

namespace Mission\Service;

use Zend\ServiceManager\ServiceManagerAwareInterface;
use Zend\ServiceManager\ServiceManager;
use DoctrineModule\Validator\NoObjectExists as NoObjectExistsValidator;
use PlaygroundGame\Mapper\GameInterface as GameMapperInterface;
use PlaygroundGame\Service\Game;
use Zend\Db\Sql\Sql;
use Zend\Db\Adapter\Adapter;

class Mission extends Game implements ServiceManagerAwareInterface
{

    /**
     * @var MissionMapperInterface
     */
    protected $missionMapper;
    
    /**
     * @var TreasurehuntPuzzleMapper
     */
    protected $missionPuzzleMapper;

    protected $options;

    public function getGameEntity()
    {
        return new \Mission\Entity\Mission;
    }
    
    /**
     * getGameMapper
     *
     * @return GameMapperInterface
     */
    public function getGameMapper()
    {
        if (null === $this->gameMapper) {
            $this->gameMapper = $this->getServiceManager()->get('mission_mission_mapper');
        }
    
        return $this->gameMapper;
    }
    
    public function analyzeClue($game, $data, $user)
    {
        $entryMapper = $this->getEntryMapper();
        $entry = $this->findLastActiveEntry($game, $user);
        
        if (!$entry) {
            return false;
        }
        
        // TODO : Replace with stepWinner
        $winner = $this->isWinner($game, $entry, $data);
        $entry->setWinner($winner);
        $entry->setDrawable($winner);
        
        if(($game->getReplayPuzzle() && $winner) || !$game->getReplayPuzzle()){
            if(count($game->getPuzzles()) > $entry->getStep()+1){
                $entry->setStep($entry->getStep()+1);
            } else {
                $entry->setActive(false);
            }   
        }
        
        $entry = $entryMapper->update($entry);
        $this->getEventManager()->trigger('analyze_clue.post', $this, array('user' => $user, 'entry' => $entry, 'game' => $game));

        return $entry;
    }
    
    /**
     * 
     * @param unknown $game
     * @param unknown $data
     * @return boolean
     */
    public function isWinner($game, $entry, $data=array())
    {
        $winner = false;    
        $json = json_decode($game->getPuzzle($entry->getStep())->getArea(), true);

        if(isset($json['area'])){
            $area = $json['area'];
            if ($data['x'] >= $area['x'] &&
                $data['x'] <= ($area['x']+$area['width']) &&
                $data['y'] >= $area['y'] &&
                $data['y'] <= ($area['y']+$area['height']) 
            ) {
                $winner = true;
                //echo "WINNER !!! x : ".$data['x']." - y : ".$data['y']. " - zone x : ".$json['area']['x']. " -y : ".$json['area']['y']. " width : ".$json['area']['width']. " height : ".$json['area']['height'];
            }
        }

        return $winner;
    }

    public function uploadImages($puzzle, $data)
    {

        foreach ($data['uploadImage'] as $uploadImage) {
            if (!empty($uploadImage['tmp_name'])) {
                $path = __DIR__.'/../../../../../../public/media/mission/';
                if (!is_dir($path)) {
                    mkdir($path,0777, true);
                }
                $media_url = $this->getOptions()->getMediaUrl() . '/';
                move_uploaded_file($uploadImage['tmp_name'], $path . $puzzle->getTreasurehunt()->getId() . "-" . $puzzle->getId() . "-" . $uploadImage['name']);

                $images = $puzzle->getImage();
                if (!empty($images)) {
                    $images = (array) json_decode($images);
                }else{
                    $images = array();
                }
                   
                $images[] = $media_url . $puzzle->getTreasurehunt()->getId() . "-" . $puzzle->getId() . "-" . $uploadImage['name'];
                
                $puzzle->setImage(json_encode($images));
                $this->getMissionMapper()->update($puzzle);
            }
        }

        return $puzzle;
    }
    /**
     *
     *
     * @param  array                  $data
     * @param  string                 $entityClass
     * @param  string                 $formClass
     * @return \PlaygroundGame\Entity\Game
     */
    public function createPuzzle(array $data)
    {
    
    	$puzzle  = new \Mission\Entity\MissionPuzzle();
    	$form  = $this->getServiceManager()->get('mission_missionpuzzle_form');
    	$entityManager = $this->getServiceManager()->get('doctrine.entitymanager.orm_default');
    	
    	$identifierInput = $form->getInputFilter()->get('identifier');
    	$noObjectExistsValidator = new NoObjectExistsValidator(array(
    	    'object_repository' => $entityManager->getRepository('Mission\Entity\Mission'),
    	    'fields' => 'identifier',
    	    'messages' => array(
    	        'objectFound' => 'This url already exists !'
    	    )
    	));
    	
    	$identifierInput->getValidatorChain()->addValidator($noObjectExistsValidator);
    	
    	$form->bind($puzzle);
    	
    	// If the identifier has not been set, I use the title to create one.
    	if (empty($data['identifier']) && ! empty($data['title'])) {
    	    $data['identifier'] = $data['title'];
    	}
    	
    	$form->setData($data);
    
    	$mission = $this->getGameMapper()->findById($data['mission_id']);
    
    	if (!$form->isValid()) {
    		return false;
    	}
    
    	$puzzle->setTreasurehunt($mission);
    
    	$this->getEventManager()->trigger(__FUNCTION__, $this, array('game' => $mission, 'data' => $data));
    	$this->getMissionPuzzleMapper()->insert($puzzle);
    	$this->getEventManager()->trigger(__FUNCTION__.'.post', $this, array('game' => $mission, 'data' => $data));
    
    	return $puzzle;
    }
    
    /**
     * @param  array                  $data
     * @param  string                 $entityClass
     * @param  string                 $formClass
     * @return \PlaygroundGame\Entity\Game
     */
    public function updatePuzzle(array $data, $puzzle)
    {
    
    	$form  = $this->getServiceManager()->get('mission_missionpuzzle_form');
    	$entityManager = $this->getServiceManager()->get('doctrine.entitymanager.orm_default');
    	
    	$identifierInput = $form->getInputFilter()->get('identifier');
    	$noObjectExistsValidator = new NoObjectExistsValidator(array(
    	    'object_repository' => $entityManager->getRepository('Mission\Entity\Mission'),
    	    'fields' => 'identifier',
    	    'messages' => array(
    	        'objectFound' => 'This url already exists !'
    	    )
    	));
    	
    	if ($puzzle->getIdentifier() != $data['identifier']) {
    	    $identifierInput->getValidatorChain()->addValidator($noObjectExistsValidator);
    	}
    	$form->bind($puzzle);
    	
    	if (! isset($data['identifier']) && isset($data['title'])) {
    	    $data['identifier'] = $data['title'];
    	}
    	
    	$form->setData($data);
    
    	if (!$form->isValid()) {
    		return false;
    	}
    
    	$mission = $puzzle->getTreasurehunt();
    
    	$this->getEventManager()->trigger(__FUNCTION__, $this, array('puzzle' => $puzzle, 'data' => $data));
    	$this->getMissionPuzzleMapper()->update($puzzle);
    	$this->getEventManager()->trigger(__FUNCTION__.'.post', $this, array('puzzle' => $puzzle, 'data' => $data));
    
    	return $puzzle;
    }

    /**
     * getMissionMapper
     *
     * @return MissionMapperInterface
     */
    public function getMissionMapper()
    {
        if (null === $this->missionMapper) {
            $this->missionMapper = $this->getServiceManager()->get('mission_mission_mapper');
        }

        return $this->missionMapper;
    }

    /**
     * setMissionMapper
     *
     * @param  MissionMapperInterface $missionMapper
     * @return User
     */
    public function setMissionMapper(GameMapperInterface $missionMapper)
    {
        $this->missionMapper = $missionMapper;

        return $this;
    }
    
    /**
     * getMissionPuzzleMapper
     *
     * @return MissionPuzzleMapperInterface
     */
    public function getMissionPuzzleMapper()
    {
    	if (null === $this->missionPuzzleMapper) {
    		$this->missionPuzzleMapper = $this->getServiceManager()->get('mission_missionpuzzle_mapper');
    	}
    
    	return $this->missionPuzzleMapper;
    }
    
    /**
     * setMissionPuzzleMapper
     *
     * @param  MissionPuzzleMapperInterface $quizquestionMapper
     * @return MissionPuzzle
     */
    public function setMissionPuzzleMapper($missionPuzzleMapper)
    {
    	$this->missionPuzzleMapper = $missionPuzzleMapper;
    
    	return $this;
    }
}
