<?php

namespace App\Admin;

use App\Entity\User;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class UserAdmin extends AbstractAdmin
{
    private ?string $plainPassword = null;

    public function __construct(private readonly UserPasswordHasherInterface $passwordHasher)
    {
    }

    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->addIdentifier('id')
            ->add('email')
            ->add('prenom')
            ->add('nom')
            ->add('roles', null, ['template' => null])
            ->add('statut')
            ->add('createdAt', null, ['format' => 'd/m/Y'])
            ->add(ListMapper::NAME_ACTIONS, null, [
                'actions' => ['show' => [], 'edit' => [], 'delete' => []],
            ]);
    }

    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->add('email', EmailType::class)
            ->add('prenom', null, ['required' => false])
            ->add('nom', null, ['required' => false])
            ->add('roles', ChoiceType::class, [
                'choices' => [
                    'Utilisateur' => 'ROLE_USER',
                    'Support' => 'ROLE_SUPPORT',
                    'Admin' => 'ROLE_ADMIN',
                ],
                'multiple' => true,
                'expanded' => true,
            ])
            ->add('plainPassword', PasswordType::class, [
                'required' => false,
                'mapped' => false,
                'label' => 'Nouveau mot de passe (laisser vide pour ne pas changer)',
            ]);
    }

    protected function configureShowFields(ShowMapper $show): void
    {
        $show
            ->add('id')
            ->add('email')
            ->add('prenom')
            ->add('nom')
            ->add('roles')
            ->add('statut')
            ->add('situation')
            ->add('createdAt');
    }

    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter
            ->add('email')
            ->add('prenom')
            ->add('nom')
            ->add('statut');
    }

    protected function prePersist(object $object): void
    {
        $this->hashPasswordIfSet($object);
    }

    protected function preUpdate(object $object): void
    {
        $this->hashPasswordIfSet($object);
    }

    private function hashPasswordIfSet(object $object): void
    {
        /** @var User $object */
        $plain = $this->getForm()->get('plainPassword')->getData();
        if (!empty($plain)) {
            $object->setPassword($this->passwordHasher->hashPassword($object, $plain));
        }
    }
}
