<?php

namespace App\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\DoctrineORMAdminBundle\Filter\ChoiceFilter;
use Sonata\DoctrineORMAdminBundle\Filter\StringFilter;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

final class SupportAccountRequestAdmin extends AbstractAdmin
{
    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->addIdentifier('id')
            ->add('email')
            ->add('status', null, ['label' => 'Statut'])
            ->add('createdAt', null, ['format' => 'd/m/Y H:i', 'label' => 'Déposé le'])
            ->add('reviewedBy.email', null, ['label' => 'Examiné par'])
            ->add('reviewedAt', null, ['format' => 'd/m/Y', 'label' => 'Examiné le'])
            ->add(ListMapper::NAME_ACTIONS, null, [
                'actions' => ['show' => [], 'edit' => []],
            ]);
    }

    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->add('status', ChoiceType::class, [
                'choices' => [
                    'En attente' => 'pending',
                    'Approuvé' => 'approved',
                    'Rejeté' => 'rejected',
                ],
                'label' => 'Statut',
            ])
            ->add('reviewerNote', TextareaType::class, [
                'required' => false,
                'label' => 'Note de l\'examinateur',
                'attr' => ['rows' => 3],
            ]);
    }

    protected function configureShowFields(ShowMapper $show): void
    {
        $show
            ->add('id')
            ->add('email')
            ->add('status', null, ['label' => 'Statut'])
            ->add('reviewerNote', null, ['label' => 'Note'])
            ->add('reviewedBy.email', null, ['label' => 'Examiné par'])
            ->add('reviewedAt', null, ['label' => 'Examiné le'])
            ->add('createdAt', null, ['label' => 'Déposé le']);
    }

    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter
            ->add('email', StringFilter::class)
            ->add('status', ChoiceFilter::class, [
                'field_type' => ChoiceType::class,
                'field_options' => [
                    'choices' => [
                        'En attente' => 'pending',
                        'Approuvé' => 'approved',
                        'Rejeté' => 'rejected',
                    ],
                ],
                'label' => 'Statut',
            ]);
    }
}
