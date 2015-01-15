<?php

namespace PlaygroundMission\Mapper;

use Doctrine\ORM\EntityManager;
use PlaygroundMission\Options\ModuleOptions;
use PlaygroundGame\Mapper\Game;

class Mission extends Game
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    protected $em;

    /**
     * @var \PlaygroundGame\Options\ModuleOptions
     */
    protected $options;

     public function __construct(EntityManager $em, ModuleOptions $options)
    {
        $this->em      = $em;
        $this->options = $options;
    }

    public function findByIdentifier($identifier)
    {
        return $this->getEntityRepository()->findOneBy(array('identifier' => $identifier));
    }

    public function findById($id)
    {
        return $this->getEntityRepository()->find($id);
    }

    public function findBy($filter, $order = null, $limit = null, $offset = null)
    {
        return $this->getEntityRepository()->findBy($filter, $order, $limit, $offset);
    }
    
    public function findOneBy($array, $sortBy = Array())
    {
        return $this->getEntityRepository()->findOneBy($array, $sortBy);
    }

    public function insert($entity, $tableName = NULL, \Zend\Stdlib\Hydrator\HydratorInterface $hydrator = NULL)
    {
        return $this->persist($entity);
    }

    public function update($entity, $tableName = NULL, \Zend\Stdlib\Hydrator\HydratorInterface $hydrator = NULL)
    {
        return $this->persist($entity);
    }

    protected function persist($entity)
    {
        $this->em->persist($entity);
        $this->em->flush();

        return $entity;
    }

    public function findAll()
    {
        return $this->findBy(array(),array('createdAt' => 'DESC'));
    }

    public function remove($entity)
    {
        $this->em->remove($entity);
        $this->em->flush();
    }

    public function getEntityRepository()
    {
        if (null === $this->er) {
            $this->er = $this->em->getRepository('PlaygroundMission\Entity\Mission');
        }

        return $this->er;
    }
}
