<?php

namespace Wbc\BranchBundle\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Wbc\BranchBundle\Entity\Branch;
use Wbc\BranchBundle\Form\DayType;

/**
 * Class TimingAdmin.
 *
 * @author Majid Mvulle <majid@majidmvulle.com>
 */
class TimingAdmin extends AbstractAdmin
{
    protected $datagridValues = [
        '_page' => 1,
        '_per_page' => 25,
        '_sort_order' => 'DESC',
        '_sort_by' => 'createdAt',
    ];

    /**
     * Fields to be shown on create/edit forms.
     *
     * @param FormMapper $formMapper
     */
    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper->add('branch', EntityType::class, [
            'class' => Branch::class,
            'property' => 'name',
            'placeholder' => '-- Select a Branch --',
        ])
            ->add('dayOfWeek', DayType::class)
            ->add('fromString', TextType::class, [
                'help' => 'e.g. 09:00',
            ])
            ->add('toString', TextType::class, [
                'help' => 'e.g. 13:00',
            ])
        ;
    }

    /**
     * Fields to be shown on filter forms.
     *
     * @param DatagridMapper $datagridMapper
     */
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper->add('branch.name');
    }

    /**
     * Fields to be shown on lists.
     *
     * @param ListMapper $listMapper
     */
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->add('dayOfWeek', 'choice', [
                'choices' => DayType::getDays(),
            ])
            ->add('branch.name')
            ->add('fromString')
            ->add('toString')
        ;
    }

    /**
     * Fields to be shown on show action.
     *
     * @param ShowMapper $showMapper
     */
    protected function configureShowFields(ShowMapper $showMapper)
    {
        $showMapper->add('branch.name')
            ->add('dayOfWeek')
            ->add('fromString')
            ->add('toString');
    }
}
