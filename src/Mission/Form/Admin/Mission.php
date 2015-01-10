<?php

namespace Mission\Form\Admin;

use Zend\Form\Form;
use PlaygroundCore\Stdlib\Hydrator\DoctrineObject as DoctrineHydrator;
use Zend\Form\Element;
use Zend\Mvc\I18n\Translator;
use Zend\ServiceManager\ServiceManager;
use PlaygroundGame\Form\Admin\Game;

class Mission extends Game
{
    public function __construct($name = null, ServiceManager $sm, Translator $translator)
    {
        $this->setServiceManager($sm);
        $entityManager = $sm->get('doctrine.entitymanager.orm_default');

        // having to fix a Doctrine-module bug :( https://github.com/doctrine/DoctrineModule/issues/180
        $hydrator = new DoctrineHydrator($entityManager, 'Mission\Entity\Mission');
        $hydrator->addStrategy('partner', new \PlaygroundCore\Stdlib\Hydrator\Strategy\ObjectStrategy());
        $this->setHydrator($hydrator);

        parent::__construct($name, $sm, $translator);

        $this->add(array(
        		'name' => 'winners',
        		'options' => array(
        				'label' => $translator->translate('Winners number', 'mission')
        		),
        		'attributes' => array(
        				'type' => 'text',
        				'placeholder' => $translator->translate('Winners number', 'mission')
        		)
        ));
        
        $this->add(array(
            'type' => 'Zend\Form\Element\Select',
            'name' => 'replayPuzzle',
            'options' => array(
                'value_options' => array(
                    '0' => $translator->translate('No', 'playgroundgame'),
                    '1' => $translator->translate('Yes', 'playgroundgame')
                ),
                'label' => $translator->translate('Go to the next puzzle only when the previous is won', 'playgroundgame')
            )
        ));
        
        $this->add(array(
        		'name' => 'timer',
        		'type' => 'Zend\Form\Element\Radio',
        		'attributes' => array(
        				'required' => 'required',
        				'value' => '0',
        		),
        		'options' => array(
        				'label' => 'Use a Timer',
        				'value_options' => array(
        						'0' => $translator->translate('No', 'mission'),
        						'1' => $translator->translate('yes', 'mission'),
        				),
        		),
        ));
        
        $this->add(array(
        		'name' => 'timerDuration',
        		'type' => 'Zend\Form\Element\Text',
        		'attributes' => array(
        				'placeholder' => $translator->translate('Duration in seconds','mission'),
        		),
        		'options' => array(
        				'label' => $translator->translate('Timer Duration','mission'),
        		),
        ));        
    }
}
