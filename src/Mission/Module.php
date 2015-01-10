<?php
/**
 * dependency Core
 * @author gbesson
 *
 */
namespace Mission;

use Zend\Session\Container;
use Zend\Mvc\ModuleRouteListener;
use Zend\Mvc\MvcEvent;
use Zend\Validator\AbstractValidator;

class Module
{
    public function onBootstrap(MvcEvent $e)
    {
        $serviceManager = $e->getApplication()->getServiceManager();
        $doctrine = $serviceManager->get('playgroundcore_doctrine_em');
        $doctrineEventManager = $doctrine->getEventManager();

        $options = $serviceManager->get('playgroundcore_module_options');
        $locale = $options->getLocale();
        $translator = $serviceManager->get('translator');
        if (!empty($locale)) {
            //translator
            $translator->setLocale($locale);

            // plugins
            $translate = $serviceManager->get('viewhelpermanager')->get('translate');
            $translate->getTranslator()->setLocale($locale);
        }

        AbstractValidator::setDefaultTranslator($translator,'playgroundcore');

        $eventManager        = $e->getApplication()->getEventManager();
        $moduleRouteListener = new ModuleRouteListener();
        $moduleRouteListener->attach($eventManager);

        $discriminatorEntry = new Doctrine\Discriminator\Entry();
        $doctrineEventManager->addEventListener(\Doctrine\ORM\Events::loadClassMetadata, $discriminatorEntry);

        // If cron is called, the $e->getRequest()->getPost() produces an error so I protect it with
        // this test
        if ((get_class($e->getRequest()) == 'Zend\Console\Request')) {
            return;
        }

    }


    public function getConfig()
    {
        return include __DIR__ . '/../../config/module.config.php';
    }

    public function getAutoloaderConfig()
    {
        return array(
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/../../src/' . __NAMESPACE__,
                ),
            ),
        );
    }

    public function getServiceConfig()
    {
        return array(
            

            'invokables' => array(
            	'mission_mission_service'      => 'Mission\Service\Mission',
            ),

            'factories' => array(
                'mission_module_options' => function ($sm) {
                        $config = $sm->get('Configuration');

                        return new Options\ModuleOptions(isset($config['mission']) ? $config['mission'] : array()
                    );
                },

                'mission_mission_mapper' => function ($sm) {
                	$mapper = new \Mission\Mapper\Mission(
                			$sm->get('doctrine.entitymanager.orm_default'),
                			$sm->get('mission_module_options')
                	);

                	return $mapper;
                },

                'mission_missionPuzzle_mapper' => function ($sm) {
                	$mapper = new \Mission\Mapper\MissionPuzzle(
                			$sm->get('doctrine.entitymanager.orm_default'),
                			$sm->get('mission_module_options')
                	);

                	return $mapper;
                },

                'mission_mission_form' => function($sm) {
                	$translator = $sm->get('translator');
                	$form = new Form\Admin\Mission(null, $sm, $translator);
                	$mission = new Entity\Mission();
                	$form->setInputFilter($mission->getInputFilter());

                	return $form;
                },

                'mission_missionpuzzle_form' => function($sm) {
                	$translator = $sm->get('translator');
                	$form = new Form\Admin\MissionPuzzle(null, $sm, $translator);
                	$missionPuzzle = new Entity\MissionPuzzle();
                	$form->setInputFilter($missionPuzzle->getInputFilter());

                	return $form;
                },
            ),
        );
    }
}

?>
