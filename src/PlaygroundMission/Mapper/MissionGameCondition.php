<?php

namespace PlaygroundMission\Mapper;

use Doctrine\ORM\EntityManager;
use PlaygroundMission\Options\ModuleOptions;

class MissionGameCondition
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    protected $em;

    /**
     * @var \Doctrine\ORM\EntityRepository
     */
    protected $er;

    /**
     * @var \PlaygroundDesign\Options\ModuleOptions
     */
    protected $options;


    /**
    * __construct
    * @param EntityManager $em
    * @param ModuleOptions $options
    *
    */
    public function __construct(EntityManager $em, ModuleOptions $options)
    {
        $this->em      = $em;
        $this->options = $options;
    }

    /**
    * findById : recupere l'entite en fonction de son id
    * @param int $id id du missionGameCondition
    *
    * @return PlaygroundMission\Entity\MissionGameCondition $missionGameCondition
    */
    public function findById($id)
    {
        return $this->getEntityRepository()->find($id);
    }

    /**
    * findOneBy : recupere l'entite en fonction de filtres
    * @param array $filters tableau de filtres
    *
    * @return PlaygroundMission\Entity\MissionGameCondition $missionGameCondition
    */
    public function findOneBy($filters)
    {
         return $this->getEntityRepository()->findOneBy($filters);
    }

    /**
    * findBy : recupere des entites en fonction de filtre
    *
    * @return collection $missionGameConditions collection de PlaygroundMission\Entity\MissionGameCondition
    */
    public function findBy($filter, $order = null, $limit = null, $offset = null)
    {
        return $this->getEntityRepository()->findBy($filter, $order, $limit, $offset);
    }

    /**
    * insert : insert en base une entité missionGameCondition
    * @param PlaygroundMission\Entity\MissionGameCondition $entity missionGameCondition
    *
    * @return PlaygroundMission\Entity\MissionGameCondition $missionGameCondition
    */
    public function insert($entity)
    {
        return $this->persist($entity);
    }

    /**
    * insert : met a jour en base une entité missionGameCondition
    * @param PlaygroundMission\Entity\MissionGameCondition $entity missionGameCondition
    *
    * @return PlaygroundMission\Entity\MissionGameCondition $missionGameCondition
    */
    public function update($entity)
    {
        return $this->persist($entity);
    }

    /**
    * insert : met a jour en base une entité missionGameCondition et persiste en base
    * @param PlaygroundMission\Entity\MissionGameCondition $entity missionGameCondition
    *
    * @return PlaygroundMission\Entity\MissionGameCondition $missionGameCondition
    */
    public function persist($entity)
    {
        $this->em->persist($entity);
        $this->em->flush();

        return $entity;
    }

    /**
    * findAll : recupere toutes les entites 
    *
    * @return collection $missionGameConditions collection de PlaygroundMission\Entity\MissionGameCondition
    */
    public function findAll()
    {
        return $this->getEntityRepository()->findAll();
    }

     /**
    * remove : supprimer une entite missionGameCondition
    * @param PlaygroundMission\Entity\MissionGameCondition $entity missionGameCondition
    *
    */
    public function remove($entity)
    {
        $this->em->remove($entity);
        $this->em->flush();
    }

    /**
    * refresh : supprimer une entite missionGameCondition
    * @param PlaygroundMission\Entity\MissionGameCondition $entity missionGameCondition
    *
    */
    public function refresh($entity)
    {
        $this->em->refresh($entity);
    }

    /**
    * getEntityRepository : recupere l'entite missionGameCondition
    *
    * @return \Doctrine\ORM\EntityRepository $missionGameCondition
    */
    public function getEntityRepository()
    {
        if (null === $this->er) {
            $this->er = $this->em->getRepository('PlaygroundMission\Entity\MissionGameCondition');
        }

        return $this->er;
    }
}
