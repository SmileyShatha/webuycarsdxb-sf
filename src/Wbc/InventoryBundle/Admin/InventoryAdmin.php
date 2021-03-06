<?php

declare(strict_types=1);

namespace Wbc\InventoryBundle\Admin;

use Doctrine\ORM\EntityRepository;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Route\RouteCollection;
use Sonata\AdminBundle\Show\ShowMapper;
use Sonata\Form\Validator\ErrorElement;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Wbc\InventoryBundle\Entity\Dealer;
use Wbc\InventoryBundle\Entity\Inventory;
use Wbc\VehicleBundle\Entity\Model;
use Wbc\VehicleBundle\Entity\ModelType;
use Wbc\VehicleBundle\Form as WbcVehicleType;

/**
 * Class InventoryAdmin.
 *
 * @author Majid Mvulle <majid@majidmvulle.com>
 */
class InventoryAdmin extends AbstractAdmin
{
    protected $datagridValues = ['_page' => 1, '_per_page' => 25, '_sort_order' => 'DESC', '_sort_by' => 'createdAt'];

    /**
     * {@inheritdoc}
     */
    public function getExportFormats()
    {
        return ['xls'];
    }

    /**
     * {@inheritdoc}
     */
    public function getExportFields()
    {
        return [
            'id',
            'make',
            'model',
            'year',
            'mileage',
            'purchasedAt',
            'soldAt',
            'age',
            'additionalCost',
            'pricePurchased',
            'overallCost',
            'priceSold',
            'grossProfit',
            'netProfit',
            'salesman',
            'status',
            'source',
            'soldToDealer',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getTemplate($name)
    {
        switch ($name) {
            case 'create':
            case 'edit':
                return 'WbcBranchBundle:Admin:edit.html.twig';
            default:
                return parent::getTemplate($name);
        }
    }

    public function prePersist($object): void
    {
        $tokenStorage = $this->getConfigurationPool()->getContainer()->get('security.token_storage');
        if ($tokenStorage->getToken()) {
            $object->setCreatedBy($tokenStorage->getToken()->getUser());
        }
    }

    public function validate(ErrorElement $errorElement, $object): void
    {
        parent::validate($errorElement, $object);

        if ($this->getConfigurationPool()->getContainer()->get('security.authorization_checker')->isGranted('ROLE_SUPER_ADMIN')) {
            return;
        }

        $em = $this->getModelManager()->getEntityManager($this->getClass());
        $originalObject = $em->getUnitOfWork()->getOriginalEntityData($object);
        $newStatus = $object->getStatus();
        $oldStatus = $originalObject['status'];
        $canTransition = false;

        if ($newStatus === $oldStatus) {
            return; //status not changed
        }

        if ('sold' === $newStatus && \in_array($oldStatus, ['in-stock', 'in-garage'], true)) {
            $canTransition = true;
        }

        if ('in-stock' === $newStatus && 'in-garage' === $oldStatus) {
            $canTransition = true;
        }

        if ('in-garage' === $newStatus && 'in-stock' === $oldStatus) {
            $canTransition = true;
        }

        if (!$canTransition) {
            $errorElement->with('status')->addViolation('You cannot change the status to this value')->end();
        }
    }

    public function postUpdate($object): void
    {
        parent::postUpdate($object);

        $usedCar = $object->getUsedCar();

        if ($usedCar && $usedCar->getActive()) {
            $usedCar->setActive(false);
        }

        $this->getConfigurationPool()->getContainer()->get('doctrine.orm.entity_manager')->flush();
    }

    protected function configureFormFields(FormMapper $form): void
    {
        /** @var $subject \Wbc\InventoryBundle\Entity\Inventory */
        $subject = $this->getSubject();
        $request = $this->getRequest();

        $form->tab('Vehicle Information')
            ->with('')
            ->add('year', WbcVehicleType\ModelYearType::class)
            ->add('make', WbcVehicleType\MakeType::class)
            ->add('model', EntityType::class, [
                'placeholder' => '',
                'class' => Model::class,
                'query_builder' => function (EntityRepository $entityRepository) {
                    return $entityRepository->createQueryBuilder('m')->where('m.id IS NULL'); //don't populate anything
                }, ])
            ->add('modelType', EntityType::class, [
                'required' => false,
                'placeholder' => '',
                'class' => ModelType::class,
                'query_builder' => function (EntityRepository $entityRepository) {
                    return $entityRepository->createQueryBuilder('m')
                        ->where('m.id IS NULL'); //don't populate anything
                }, ]);

        if ($subject) {
            $vehicleMake = $subject->getMake();
            $vehicleModel = $subject->getModel();

            if ($vehicleMake || $request->isMethod('POST')) {
                $form->add('model', EntityType::class, [
                    'placeholder' => '',
                    'class' => Model::class,
                    'query_builder' => $request->isMethod('POST') ? null : function (EntityRepository $entityRepository) use ($subject, $vehicleMake) {
                        return $entityRepository->createQueryBuilder('m')
                            ->where('m.make = :make')
                            ->andWhere('m.active = :active')
                            ->setParameter('make', $vehicleMake)
                            ->setParameter('active', true)
                            ->orderBy('m.name', 'ASC');
                    }, ]);
            }
            if ($vehicleModel || $request->isMethod('POST')) {
                $form->add('modelType', EntityType::class, [
                    'placeholder' => '',
                    'class' => ModelType::class,
                    'label' => 'Vehicle Trim',
                    'required' => false,
                    'query_builder' => $request->isMethod('POST') ? null : function (EntityRepository $entityRepository) use ($subject, $vehicleModel) {
                        return $entityRepository->createQueryBuilder('m')
                            ->where('m.model = :model')
                            ->setParameter('model', $vehicleModel);
                    }, ]);
            }
        }

        $form->add('transmission', WbcVehicleType\TransmissionType::class, ['required' => false])
            ->add('mileage', WbcVehicleType\MileageType::class)
            ->add('specifications', WbcVehicleType\SpecificationType::class, ['required' => false])
            ->add('bodyCondition', WbcVehicleType\ConditionType::class, ['required' => false])
            ->add('options', ChoiceType::class, ['required' => false, 'choices' => ['basic' => 'Basic', 'mid' => 'Mid', 'full' => 'Full']])
            ->add('color', WbcVehicleType\ColorType::class, ['required' => false])
            ->end()
            ->end()
            ->tab('Sales Details')
            ->with('')
            ->add('status', ChoiceType::class, ['choices' => Inventory::getStatuses()])
            ->add('pricePurchased', MoneyType::class, ['label' => 'Price Purchased', 'currency' => 'AED'])
            ->add('priceSold', MoneyType::class, ['required' => false, 'label' => 'Price Sold', 'currency' => 'AED'])
            ->add('soldAt', DateType::class, ['required' => false, 'label' => 'Date Sold', 'widget' => 'single_text'])
            ->add('soldToDealer', EntityType::class, ['label' => 'Sold To', 'required' => false, 'placeholder' => '', 'class' => Dealer::class, 'query_builder' => function (EntityRepository $entityRepository) {
                return $entityRepository->createQueryBuilder('d')->where('d.active = true');
            }]);

        if ($subject) {
            $soldToDealer = $subject->getSoldToDealer();

            if ($soldToDealer || $request->isMethod('POST')) {
                $form->add('soldToDealer', EntityType::class, [
                    'required' => false,
                    'label' => 'Sold To',
                    'placeholder' => '',
                    'class' => Dealer::class,
                    'query_builder' => $request->isMethod('POST') ? null : function (EntityRepository $entityRepository) use ($subject, $soldToDealer) {
                        return $entityRepository->createQueryBuilder('d')
                            ->where('d = :dealer')
                            ->andWhere('d.active = :active')
                            ->setParameter('dealer', $soldToDealer)
                            ->setParameter('active', true)
                            ->orderBy('d.name', 'ASC');
                    }, ]);
            }
        }

        $form->end()
            ->end();
    }

    protected function configureListFields(ListMapper $list): void
    {
        $list->addIdentifier('id')
            ->add('make')
            ->add('model')
            ->add('modelType')
            ->add('mileage', null, ['label' => 'Mileage (Kms)'])
            ->add('status', 'choice', ['choices' => Inventory::getStatuses()])
            ->add('pricePurchased', MoneyType::class, ['currency' => 'AED'])
            ->add('priceSold', MoneyType::class, ['currency' => 'AED'])
            ->add('profit', MoneyType::class, ['currency' => 'AED', 'label' => 'GP'])
            ->add('soldAt')
            ->add('source')
            ->add('soldToDealer', null, ['label' => 'Sold To'])
            ->add('_action', 'actions', [
                'actions' => ['show' => [],
                    'edit' => [],
                    'delete' => [],
                    'generateUsedCarFromInventory' => ['template' => 'WbcInventoryBundle:Admin/CRUD:list__action_generate_used_car.html.twig'],
                ],
            ]);
    }

    protected function configureShowFields(ShowMapper $show): void
    {
        $show->tab('Vehicle Information')
            ->with('Vehicle Details')
            ->add('year', WbcVehicleType\ModelYearType::class)
            ->add('make', WbcVehicleType\MakeType::class)
            ->add('model', EntityType::class, [
                'placeholder' => '',
                'class' => Model::class,
                'query_builder' => function (EntityRepository $entityRepository) {
                    return $entityRepository->createQueryBuilder('m')
                        ->where('m.id IS NULL'); //don't populate anything
                }, ])->add('modelType', EntityType::class, [
                'required' => false,
                'placeholder' => '',
                'class' => ModelType::class,
                'query_builder' => function (EntityRepository $entityRepository) {
                    return $entityRepository->createQueryBuilder('m')
                        ->where('m.id IS NULL'); //don't populate anything
                }, ]);

        $show->add('transmission', WbcVehicleType\TransmissionType::class)
            ->add('mileage', WbcVehicleType\MileageType::class)
            ->add('specifications', WbcVehicleType\SpecificationType::class)
            ->add('bodyCondition', WbcVehicleType\ConditionType::class)
            ->add('mechanicalCondition', WbcVehicleType\ConditionType::class)
            ->add('options', ChoiceType::class, ['choices' => ['basic' => 'Basic', 'mid' => 'Mid', 'full' => 'Full']])
            ->add('color', WbcVehicleType\ColorType::class)
            ->end()
            ->end()
            ->tab('Sales Information')
            ->with('')
            ->add('status', ChoiceType::class, ['choices' => Inventory::getStatuses()])
            ->add('pricePurchased', MoneyType::class, ['label' => 'Price Purchased (AED)'])
            ->add('priceSold', MoneyType::class, ['label' => 'Price Sold (AED)'])
            ->add('soldAt')
            ->add('soldToDealer', null, ['label' => 'Sold To'])
            ->end()
            ->end();
    }

    protected function configureRoutes(RouteCollection $collection): void
    {
        $collection->remove('delete')
            ->add('generateUsedCarFromInventory', $this->getRouterIdParameter().'/generateUsedCarFromInventory');
    }
}
