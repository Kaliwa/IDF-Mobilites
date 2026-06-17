<?php

namespace App\Admin;

use App\Entity\Abonnement;
use App\Entity\Payeur;
use App\Entity\User;
use App\Enum\Periodicite;
use App\Enum\StatutAbonnement;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Sonata\DoctrineORMAdminBundle\Filter\ChoiceFilter;
use Sonata\DoctrineORMAdminBundle\Filter\StringFilter;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

final class AbonnementAdmin extends AbstractAdmin
{
    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->addIdentifier('id')
            ->add('beneficiaire.email', null, ['label' => 'Bénéficiaire'])
            ->add('typeOffre', null, ['label' => 'Offre'])
            ->add('montant', null, ['label' => 'Montant (€)'])
            ->add('periodicite')
            ->add('statut')
            ->add('dateDebut', null, ['format' => 'd/m/Y', 'label' => 'Début'])
            ->add('dateFin', null, ['format' => 'd/m/Y', 'label' => 'Fin'])
            ->add(ListMapper::NAME_ACTIONS, null, [
                'actions' => ['show' => [], 'edit' => []],
            ]);
    }

    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->add('beneficiaire', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'email',
            ])
            ->add('payeur', EntityType::class, [
                'class' => Payeur::class,
                'choice_label' => fn (Payeur $p) => $p->getPrenom().' '.$p->getNom(),
                'required' => false,
                'placeholder' => 'Auto-payeur',
            ])
            ->add('typeOffre', TextType::class, ['label' => 'Type d\'offre'])
            ->add('montant', TextType::class, ['label' => 'Montant (€)'])
            ->add('periodicite', EnumType::class, [
                'class' => Periodicite::class,
                'choice_label' => fn (Periodicite $p) => $p->label(),
            ])
            ->add('statut', EnumType::class, [
                'class' => StatutAbonnement::class,
                'choice_label' => fn (StatutAbonnement $s) => $s->label(),
            ])
            ->add('dateDebut', null, ['widget' => 'single_text', 'label' => 'Date de début'])
            ->add('dateFin', null, ['widget' => 'single_text', 'required' => false, 'label' => 'Date de fin']);
    }

    protected function configureShowFields(ShowMapper $show): void
    {
        $show
            ->add('id')
            ->add('beneficiaire.email', null, ['label' => 'Bénéficiaire'])
            ->add('payeur', null, ['label' => 'Payeur'])
            ->add('typeOffre')
            ->add('montant')
            ->add('periodicite')
            ->add('statut')
            ->add('dateDebut')
            ->add('dateFin')
            ->add('renewalNotifiedAt');
    }

    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter
            ->add('statut', ChoiceFilter::class, [
                'field_type' => ChoiceType::class,
                'field_options' => [
                    'choices' => [
                        'Actif' => StatutAbonnement::ACTIF->value,
                        'En attente' => StatutAbonnement::EN_ATTENTE->value,
                        'Suspendu' => StatutAbonnement::SUSPENDU->value,
                    ],
                ],
            ])
            ->add('typeOffre', StringFilter::class)
            ->add('periodicite', ChoiceFilter::class, [
                'field_type' => ChoiceType::class,
                'field_options' => [
                    'choices' => [
                        'Mensuel' => Periodicite::MENSUEL->value,
                        'Annuel' => Periodicite::ANNUEL->value,
                    ],
                ],
            ]);
    }
}
