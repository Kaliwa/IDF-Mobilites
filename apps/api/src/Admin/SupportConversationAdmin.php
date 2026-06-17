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

final class SupportConversationAdmin extends AbstractAdmin
{
    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->addIdentifier('id')
            ->add('user.email', null, ['label' => 'Utilisateur'])
            ->add('subject', null, ['label' => 'Sujet'])
            ->add('status', null, ['label' => 'Statut'])
            ->add('createdAt', null, ['format' => 'd/m/Y', 'label' => 'Créé le'])
            ->add('updatedAt', null, ['format' => 'd/m/Y H:i', 'label' => 'Mis à jour'])
            ->add(ListMapper::NAME_ACTIONS, null, [
                'actions' => ['show' => [], 'edit' => []],
            ]);
    }

    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->add('subject', null, ['label' => 'Sujet'])
            ->add('status', ChoiceType::class, [
                'choices' => [
                    'Ouvert' => 'open',
                    'En cours' => 'in_progress',
                    'Résolu' => 'resolved',
                    'Fermé' => 'closed',
                ],
                'label' => 'Statut',
            ]);
    }

    protected function configureShowFields(ShowMapper $show): void
    {
        $show
            ->add('id')
            ->add('user.email', null, ['label' => 'Utilisateur'])
            ->add('subject', null, ['label' => 'Sujet'])
            ->add('status', null, ['label' => 'Statut'])
            ->add('messages', null, ['label' => 'Messages'])
            ->add('createdAt', null, ['label' => 'Créé le'])
            ->add('updatedAt', null, ['label' => 'Mis à jour']);
    }

    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter
            ->add('status', ChoiceFilter::class, [
                'field_type' => ChoiceType::class,
                'field_options' => [
                    'choices' => [
                        'Ouvert' => 'open',
                        'En cours' => 'in_progress',
                        'Résolu' => 'resolved',
                        'Fermé' => 'closed',
                    ],
                ],
                'label' => 'Statut',
            ])
            ->add('user.email', StringFilter::class, ['label' => 'Email utilisateur']);
    }
}
