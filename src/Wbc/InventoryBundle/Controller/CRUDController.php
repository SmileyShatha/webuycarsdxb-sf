<?php

declare(strict_types=1);

namespace Wbc\InventoryBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration as CF;
use Sonata\AdminBundle\Controller\CRUDController as Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Wbc\InventoryBundle\Entity\Inventory;
use Wbc\InventoryBundle\Entity\UsedCar;
use Application\Sonata\MediaBundle\Entity\Gallery;

/**
 * Class CRUDController.
 *
 * @author Majid Mvulle <majid@majidmvulle.com>
 */
class CRUDController extends Controller
{
    /**
     * Generates a Used Car from a Deal.
     *
     * @CF\ParamConverter("inventory", class="Wbc\InventoryBundle\Entity\Inventory")
     *
     * @param Inventory $inventory
     *
     * @return Response
     */
    public function generateUsedCarFromInventoryAction(Inventory $inventory)
    {
        $entityManager = $this->get('doctrine.orm.default_entity_manager');

        $usedCar = new UsedCar($inventory);
        $usedCar->setCreatedBy($this->getUser());
        $usedCar->setPrice(0);
        $usedCar->setActive(false);
        $gallery = new Gallery();
        $gallery->setName(sprintf('used-car-inventory-%d', $inventory->getId()));
        $gallery->setEnabled(true);
        $gallery->setContext('default');
        $usedCar->setGallery($gallery);

        $entityManager->persist($usedCar);
        $entityManager->flush();

        return new RedirectResponse($this->generateUrl('admin_wbc_inventory_usedcar_edit', ['id' => $usedCar->getId()]));
    }
}
