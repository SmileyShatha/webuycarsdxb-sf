<?php

namespace Wbc\CrawlerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use JMS\Serializer\Annotation as ApiSerializer;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Class ClassifiedsModel.
 *
 * @author Majid Mvulle <majid@majidmvulle.com>
 *
 * @ORM\Table(name="crawler_classifieds_model")
 * @ORM\Entity(repositoryClass="Wbc\CrawlerBundle\Repository\ClassifiedsModelRepository")
 * @ApiSerializer\ExclusionPolicy("all")
 * @SuppressWarnings(PHPMD.ShortVariable, PHPMD.BooleanGetMethodName)
 */
class ClassifiedsModel
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ApiSerializer\Expose
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=100)
     * @ApiSerializer\Expose
     */
    protected $name;

    /**
     * @var string
     *
     * @ORM\Column(name="source_id", type="string", length=100)
     */
    protected $sourceId;

    /**
     * @ORM\ManyToOne(targetEntity="\Wbc\CrawlerBundle\Entity\ClassifiedsMake", inversedBy="models", fetch="EAGER")
     * @ORM\JoinColumn(name="make_id", referencedColumnName="id")
     * @ApiSerializer\Expose
     */
    protected $make;

    /**
     * @var \DateTime
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(name="created_at", type="datetime")
     */
    protected $createdAt;

    /**
     * @var \DateTime
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(name="updated_at", type="datetime")
     */
    protected $updatedAt;

    /**
     * @ORM\OneToMany(targetEntity="\Wbc\CrawlerBundle\Entity\ClassifiedsAd", mappedBy="classifiedsModel")
     */
    protected $classifiedAds;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Wbc\CrawlerBundle\Entity\ClassifiedsModelType", mappedBy="model")
     */
    protected $modelTypes;

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name.
     *
     * @param string $name
     *
     * @return ClassifiedsModel
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set sourceId.
     *
     * @param string $sourceId
     *
     * @return ClassifiedsModel
     */
    public function setSourceId($sourceId)
    {
        $this->sourceId = $sourceId;

        return $this;
    }

    /**
     * Get sourceId.
     *
     * @return string
     */
    public function getSourceId()
    {
        return $this->sourceId;
    }

    /**
     * Set createdAt.
     *
     * @param \DateTime $createdAt
     *
     * @return ClassifiedsModel
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get createdAt.
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set updatedAt.
     *
     * @param \DateTime $updatedAt
     *
     * @return ClassifiedsModel
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Get updatedAt.
     *
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * Set make.
     *
     * @param \Wbc\CrawlerBundle\Entity\ClassifiedsMake $make
     *
     * @return ClassifiedsModel
     */
    public function setMake(ClassifiedsMake $make = null)
    {
        $this->make = $make;

        return $this;
    }

    /**
     * Get make.
     *
     * @return \Wbc\CrawlerBundle\Entity\ClassifiedsMake
     */
    public function getMake()
    {
        return $this->make;
    }
    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->classifiedAds = new ArrayCollection();
        $this->modelTypes = new ArrayCollection();
    }

    /**
     * Add classifiedAds.
     *
     * @param \Wbc\CrawlerBundle\Entity\ClassifiedsAd $classifiedAd
     *
     * @return ClassifiedsModel
     */
    public function addClassifiedAd(ClassifiedsAd $classifiedAd)
    {
        $this->classifiedAds[] = $classifiedAd;

        return $this;
    }

    /**
     * Remove classifiedAds.
     *
     * @param \Wbc\CrawlerBundle\Entity\ClassifiedsAd $classifiedAd
     */
    public function removeClassifiedAd(ClassifiedsAd $classifiedAd)
    {
        $this->classifiedAds->removeElement($classifiedAd);
    }

    /**
     * Get classifiedAds.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getClassifiedAds()
    {
        return $this->classifiedAds;
    }

    /**
     * Add modelTypes.
     *
     * @param ClassifiedsModelType $modelTypes
     *
     * @return ClassifiedsModel
     */
    public function addModelType(ClassifiedsModelType $modelTypes)
    {
        $this->modelTypes[] = $modelTypes;

        return $this;
    }

    /**
     * Remove modelTypes.
     *
     * @param ClassifiedsModelType $modelTypes
     */
    public function removeModelType(ClassifiedsModelType $modelTypes)
    {
        $this->modelTypes->removeElement($modelTypes);
    }

    /**
     * Get modelTypes.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getModelTypes()
    {
        return $this->modelTypes;
    }
}
