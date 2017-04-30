<?php

namespace Danrot\Doctrine\TranslatableExtension\Listener;

use Danrot\Doctrine\TranslatableExtension\Mapping\Annotations\Translatable;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Events;

class TranslatableListener implements EventSubscriber
{
    /**
     * @var AnnotationReader
     */
    private $annotationReader;

    public function __construct(AnnotationReader $annotationReader)
    {
        $this->annotationReader = $annotationReader;
    }

    public function getSubscribedEvents()
    {
        return [
            Events::loadClassMetadata,
        ];
    }

    public function loadClassMetadata(LoadClassMetadataEventArgs $event)
    {
        $classMetadata = $event->getClassMetadata();
        $class = $classMetadata->getReflectionClass();

        foreach ($class->getProperties() as $property) {
            if ($this->annotationReader->getPropertyAnnotation($property, Translatable::class)) {
                unset($classMetadata->fieldMappings[$property->getName()]);
            }
        }
    }
}
