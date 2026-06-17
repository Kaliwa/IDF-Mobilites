<?php

namespace App\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Sonata\DoctrineORMAdminBundle\Filter\ChoiceFilter;
use Sonata\DoctrineORMAdminBundle\Filter\StringFilter;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

final class SubscriptionDossierAdmin extends AbstractAdmin
{
    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->addIdentifier('id')
            ->add('user.email', null, ['label' => 'Demandeur'])
            ->add('type', null, ['label' => 'Type'])
            ->add('documentType', null, ['label' => 'Document'])
            ->add('status', null, ['label' => 'Statut'])
            ->add('ocrScore', null, ['label' => 'Score OCR'])
            ->add('createdAt', null, ['format' => 'd/m/Y', 'label' => 'Déposé le'])
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
                    'En cours d\'examen' => 'in_review',
                    'Approuvé' => 'approved',
                    'Rejeté' => 'rejected',
                ],
                'label' => 'Statut',
            ])
            ->add('agentNote', TextareaType::class, [
                'required' => false,
                'label' => 'Note de l\'agent',
                'attr' => ['rows' => 4],
            ]);
    }

    protected function configureShowFields(ShowMapper $show): void
    {
        $show
            ->add('id')
            ->add('user.email', null, ['label' => 'Demandeur'])
            ->add('type', null, ['label' => 'Type'])
            ->add('documentType', null, ['label' => 'Type de document'])
            ->add('documentRef', null, ['label' => 'Référence document'])
            ->add('ocrScore', null, ['label' => 'Score OCR'])
            ->add('ocrFlags', null, ['label' => 'Alertes OCR'])
            ->add('status', null, ['label' => 'Statut'])
            ->add('agentNote', null, ['label' => 'Note de l\'agent'])
            ->add('reviewedBy.email', null, ['label' => 'Examiné par'])
            ->add('reviewedAt', null, ['label' => 'Examiné le'])
            ->add('createdAt', null, ['label' => 'Déposé le']);
    }

    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter
            ->add('status', ChoiceFilter::class, [
                'field_type' => ChoiceType::class,
                'field_options' => [
                    'choices' => [
                        'En attente' => 'pending',
                        'En cours d\'examen' => 'in_review',
                        'Approuvé' => 'approved',
                        'Rejeté' => 'rejected',
                    ],
                ],
                'label' => 'Statut',
            ])
            ->add('type', ChoiceFilter::class, [
                'field_type' => ChoiceType::class,
                'field_options' => [
                    'choices' => [
                        'Nouvelle souscription' => 'subscription_request',
                        'Renouvellement' => 'renewal',
                        'Changement de payeur' => 'payer_change',
                    ],
                ],
                'label' => 'Type',
            ])
            ->add('documentType', ChoiceFilter::class, [
                'field_type' => ChoiceType::class,
                'field_options' => [
                    'choices' => [
                        "Carte d'identité" => 'carte_identite',
                        'Justificatif de domicile' => 'justificatif_domicile',
                        'RIB' => 'rib',
                        'Certificat de scolarité' => 'certificat_scolarite',
                    ],
                ],
                'label' => 'Document',
            ])
            ->add('user.email', StringFilter::class, ['label' => 'Email demandeur']);
    }
}
