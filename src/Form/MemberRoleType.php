<?php

declare(strict_types=1);

namespace App\Form;


use App\Entity\UserRole;
use Override;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<array<string, mixed>>
 */
final class MemberRoleType extends AbstractType
{
    #[Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('roles', EntityType::class, [
                'class' => UserRole::class,
                'choices' => $options['user_roles'],
                'choice_label' => 'name',
                'multiple' => true,
                'expanded' => true,
                'required' => false,
                'label' => false,
                'attr' => [
                    'class' => 'd-flex flex-column gap-2'
                ],
                'row_attr' => [
                    'class' => 'mb-0'
                ],
                'choice_attr' => function () {
                    return [
                        'class' => 'm-0 p-0 shadow-none border-secondary bg-app-dark form-check-input float-none mx-auto d-none'
                    ];
                },
                'label_attr' => [
                    'class' => 'flex-grow-1 mb-0 small text-app-secondary fw-semibold pointer text-truncate'
                ],
            ]);
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'user_roles' => [],
            'attr' => [
                'class' => 'm-0 p-0',
                'novalidate' => 'novalidate',
                'id' => 'role-edit-form'
            ],
        ]);

        $resolver->setAllowedTypes('user_roles', 'array');
    }
}
