<?php

namespace App\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;

final class TransitLineAdmin extends AbstractAdmin
{
    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->addIdentifier('id')
            ->add('code', null, ['label' => 'Code'])
            ->add('name', null, ['label' => 'Nom'])
            ->add('primRef', null, ['label' => 'Référence PRIM'])
            ->add(ListMapper::NAME_ACTIONS, null, [
                'actions' => ['show' => [], 'edit' => [], 'delete' => []],
            ]);
    }

    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->add('code', null, ['label' => 'Code (ex: rer-a)'])
            ->add('name', null, ['label' => 'Nom (ex: RER A)'])
            ->add('primRef', null, [
                'required' => false,
                'label' => 'Référence PRIM (ex: STIF:Line::C01742:)',
            ]);
    }

    protected function configureShowFields(ShowMapper $show): void
    {
        $show
            ->add('id')
            ->add('code')
            ->add('name')
            ->add('primRef');
    }

    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter
            ->add('code')
            ->add('name');
    }
}
