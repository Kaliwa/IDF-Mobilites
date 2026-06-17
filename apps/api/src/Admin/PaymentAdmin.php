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

final class PaymentAdmin extends AbstractAdmin
{
    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->addIdentifier('id')
            ->add('user.email', null, ['label' => 'Utilisateur'])
            ->add('abonnement.typeOffre', null, ['label' => 'Offre'])
            ->add('amount', null, ['label' => 'Montant (€)'])
            ->add('status', null, ['label' => 'Statut'])
            ->add('processedAt', null, ['format' => 'd/m/Y H:i', 'label' => 'Date'])
            ->add(ListMapper::NAME_ACTIONS, null, [
                'actions' => ['show' => []],
            ]);
    }

    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->add('amount', null, ['label' => 'Montant (€)'])
            ->add('status', ChoiceType::class, [
                'choices' => [
                    'Payé' => 'paid',
                    'En attente' => 'pending',
                    'Échoué' => 'failed',
                    'Remboursé' => 'refunded',
                ],
            ]);
    }

    protected function configureShowFields(ShowMapper $show): void
    {
        $show
            ->add('id')
            ->add('user.email', null, ['label' => 'Utilisateur'])
            ->add('abonnement', null, ['label' => 'Abonnement'])
            ->add('amount', null, ['label' => 'Montant (€)'])
            ->add('status')
            ->add('processedAt')
            ->add('failureNotifiedAt');
    }

    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter
            ->add('status', ChoiceFilter::class, [
                'field_type' => ChoiceType::class,
                'field_options' => [
                    'choices' => [
                        'Payé' => 'paid',
                        'En attente' => 'pending',
                        'Échoué' => 'failed',
                        'Remboursé' => 'refunded',
                    ],
                ],
            ])
            ->add('user.email', StringFilter::class, ['label' => 'Email utilisateur']);
    }
}
