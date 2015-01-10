<?php 

namespace Mission\Doctrine\Discriminator;

class Entry
{
    public function loadClassMetadata(\Doctrine\ORM\Event\LoadClassMetadataEventArgs $eventArgs)
    {        
        if ($eventArgs->getClassMetadata()->name == 'PlaygroundGame\Entity\Game') {
            $eventArgs->getClassMetadata()->discriminatorMap['mission'] =  "\Mission\Entity\Mission";
        }
    }
}