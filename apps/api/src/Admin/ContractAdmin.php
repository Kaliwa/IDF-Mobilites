<?php

namespace App\Admin;

use App\Entity\Contract;
use App\Entity\Payeur;
use App\Entity\TransitLine;
use App\Entity\User;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Sonata\DoctrineORMAdminBundle\Filter\ChoiceFilter;
use Sonata\DoctrineORMAdminBundle\Filter\StringFilter;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

final class ContractAdmin extends AbstractAdmin
{
    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->addIdentifier('id')
            ->add('user.email', null, ['label' => 'Titulaire'])
            ->add('line.name', null, ['label' => 'Ligne'])
            ->add('status', null, ['label' => 'Statut'])
            ->add('payeur', null, ['label' => 'Payeur'])
            ->add('createdAt', null, ['format' => 'd/m/Y', 'label' => 'Créé le'])
            ->add(ListMapper::NAME_ACTIONS, null, [
                'actions' => ['show' => [], 'edit' => []],
            ]);
    }

    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->add('user', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'email',
                'label' => 'Titulaire',
            ])
            ->add('line', EntityType::class, [
                'class' => TransitLine::class,
                'choice_label' => fn (TransitLine $l) => $l->getCode().' — '.$l->getName(),
                'required' => false,
                'placeholder' => 'Aucune ligne',
                'label' => 'Ligne',
            ])
            ->add('status', ChoiceType::class, [
                'choices' => [
                    'En attente' => 'pending',
                    'Actif' => 'active',
                    'Suspendu' => 'suspended',
                    'Annulé' => 'cancelled',
                ],
            ])
            ->add('payeur', EntityType::class, [
                'class' => Payeur::class,
                'choice_label' => fn (Payeur $p) => $p->getPrenom().' '.$p->getNom(),
                'required' => false,
                'placeholder' => 'Auto-payeur',
            ])
            ->add('suspensionReason', TextType::class, [
                'required' => false,
                'label' => 'Motif de suspension',
            ]);
    }

    protected function configureShowFields(ShowMapper $show): void
    {
        $show
            ->add('id')
            ->add('user.email', null, ['label' => 'Titulaire'])
            ->add('line.name', null, ['label' => 'Ligne'])
            ->add('status')
            ->add('payeur')
            ->add('suspendedAt')
            ->add('suspendedUntil')
            ->add('suspensionReason')
            ->add('cancelledAt')
            ->add('createdAt');
    }

    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter
            ->add('status', ChoiceFilter::class, [
                'field_type' => ChoiceType::class,
                'field_options' => [
                    'choices' => [
                        'En attente' => 'pending',
                        'Actif' => 'active',
                        'Suspendu' => 'suspended',
                        'Annulé' => 'cancelled',
                    ],
                ],
            ])
            ->add('user.email', StringFilter::class, ['label' => 'Email titulaire']);
    }
}
