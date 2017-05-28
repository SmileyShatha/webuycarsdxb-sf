<?php

namespace Wbc\ValuationBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Wbc\VehicleBundle\Entity\Make;
use Wbc\VehicleBundle\Entity\Model;

/**
 * Class ValuationConfiguration.
 *
 * @author Majid Mvulle <majid@majidmvulle.com>
 *
 * @ORM\Table(name="valuation_configuration")
 * @ORM\Entity(repositoryClass="Wbc\ValuationBundle\Repository\ValuationConfigurationRepository")
 */
class ValuationConfiguration
{
    /**
     * @var string
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var Make
     *
     * @ORM\ManyToOne(targetEntity="\Wbc\VehicleBundle\Entity\Make")
     * @ORM\JoinColumn(name="vehicle_make_id", referencedColumnName="id", nullable=true)
     */
    protected $vehicleMake;

    /**
     * @var Model
     *
     * @ORM\ManyToOne(targetEntity="\Wbc\VehicleBundle\Entity\Model")
     * @ORM\JoinColumn(name="vehicle_model_id", referencedColumnName="id", nullable=true)
     */
    protected $vehicleModel;

    /**
     * @var int
     *
     * @ORM\Column(name="vehicle_year", type="smallint", nullable=true)
     */
    protected $vehicleYear;

    /**
     * @var float
     *
     * @ORM\Column(name="discount", type="decimal", precision=11, scale=2)
     */
    protected $discount;

    /**
     * @var \DateTime
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(name="created_at", type="datetime")
     */
    protected $createdAt;

    /**
     * @var \DateTime
     *
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(name="updated_at", type="datetime")
     */
    protected $updatedAt;

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
     * Set vehicleYear.
     *
     * @param int $vehicleYear
     *
     * @return ValuationConfiguration
     */
    public function setVehicleYear($vehicleYear)
    {
        $this->vehicleYear = $vehicleYear;

        return $this;
    }

    /**
     * Get vehicleYear.
     *
     * @return int
     */
    public function getVehicleYear()
    {
        return $this->vehicleYear;
    }

    /**
     * Set discount.
     *
     * @param string $discount
     *
     * @return ValuationConfiguration
     */
    public function setDiscount($discount)
    {
        $this->discount = $discount;

        return $this;
    }

    /**
     * Get discount.
     *
     * @return string
     */
    public function getDiscount()
    {
        return $this->discount;
    }

    /**
     * Set createdAt.
     *
     * @param \DateTime $createdAt
     *
     * @return ValuationConfiguration
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
     * @return ValuationConfiguration
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
     * Set vehicleMake.
     *
     * @param \Wbc\VehicleBundle\Entity\Make $vehicleMake
     *
     * @return ValuationConfiguration
     */
    public function setVehicleMake(\Wbc\VehicleBundle\Entity\Make $vehicleMake = null)
    {
        $this->vehicleMake = $vehicleMake;

        return $this;
    }

    /**
     * Get vehicleMake.
     *
     * @return \Wbc\VehicleBundle\Entity\Make
     */
    public function getVehicleMake()
    {
        return $this->vehicleMake;
    }

    /**
     * Set vehicleModel.
     *
     * @param \Wbc\VehicleBundle\Entity\Model $vehicleModel
     *
     * @return ValuationConfiguration
     */
    public function setVehicleModel(\Wbc\VehicleBundle\Entity\Model $vehicleModel = null)
    {
        $this->vehicleModel = $vehicleModel;

        return $this;
    }

    /**
     * Get vehicleModel.
     *
     * @return \Wbc\VehicleBundle\Entity\Model
     */
    public function getVehicleModel()
    {
        return $this->vehicleModel;
    }
}