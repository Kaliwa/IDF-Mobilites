<?php

namespace App\Admin;

use App\Enum\LienBeneficiaire;
use App\Enum\MoyenPaiement;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;

final class PayeurAdmin extends AbstractAdmin
{
    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->addIdentifier('id')
            ->add('prenom', null, ['label' => 'Prénom'])
            ->add('nom')
            ->add('email')
            ->add('lienBeneficiaire', null, ['label' => 'Lien'])
            ->add('moyenPaiement', null, ['label' => 'Moyen de paiement'])
            ->add(ListMapper::NAME_ACTIONS, null, [
                'actions' => ['show' => [], 'edit' => [], 'delete' => []],
            ]);
    }

    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->add('prenom', null, ['label' => 'Prénom'])
            ->add('nom')
            ->add('email', EmailType::class)
            ->add('lienBeneficiaire', EnumType::class, [
                'class' => LienBeneficiaire::class,
                'choice_label' => fn (LienBeneficiaire $l) => $l->label(),
                'label' => 'Lien avec le bénéficiaire',
            ])
            ->add('moyenPaiement', EnumType::class, [
                'class' => MoyenPaiement::class,
                'choice_label' => fn (MoyenPaiement $m) => $m->label(),
                'label' => 'Moyen de paiement',
            ]);
    }

    protected function configureShowFields(ShowMapper $show): void
    {
        $show
            ->add('id')
            ->add('prenom')
            ->add('nom')
            ->add('email')
            ->add('lienBeneficiaire')
            ->add('moyenPaiement');
    }

    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter
            ->add('email')
            ->add('nom');
    }
}
