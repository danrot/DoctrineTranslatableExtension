<?php

namespace Danrot\Doctrine\TranslatableExtension\Tests\Functional\Fixtures;

use Danrot\Doctrine\TranslatableExtension\Mapping\Annotations\Locale;
use Danrot\Doctrine\TranslatableExtension\Mapping\Annotations\Translatable;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class Page
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     *
     * @var int
     */
    private $id;

    /**
     * @Translatable
     * @ORM\Column(type="string")
     *
     * @var string
     */
    private $title;

    /**
     * @Translatable
     * @ORM\Column(type="string")
     *
     * @var string
     */
    private $description;

    /**
     * @ORM\Column(type="datetime")
     *
     * @var DateTime
     */
    private $created;

    /**
     * @Locale
     *
     * @var string
     */
    private $locale;

    public function getId()
    {
        return $this->id;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function setDescription($description)
    {
        $this->description = $description;
    }

    public function getCreated()
    {
        return $this->created;
    }

    public function setCreated(\DateTime $created)
    {
        $this->created = $created;
    }

    public function getLocale()
    {
        return $this->locale;
    }

    public function setLocale($locale)
    {
        $this->locale = $locale;
    }
}
