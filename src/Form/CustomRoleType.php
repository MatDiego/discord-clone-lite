<?php

declare(strict_types=1);

namespace App\Form;

use App\Dto\CustomRoleRequest;
use App\Entity\Permission;
use Override;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<CustomRoleRequest>
 */
final class CustomRoleType extends AbstractType
{
    #[Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'role.label_name',
                'attr' => [
                    'placeholder' => 'role.name_placeholder',
                    'class' => 'text-white form-control',
                ],
            ])
            ->add('permissions', EntityType::class, [
                'class' => Permission::class,
                'choice_label' => fn (Permission $permission) => $permission->getName()->trans(),
                'multiple' => true,
                'expanded' => true,
                'label' => 'role.label_permissions',
                'attr' => [
                    'class' => 'd-flex flex-column gap-2'
                ],
                'row_attr' => [
                    'class' => 'mb-0'
                ],
                'choice_attr' => function () {
                    return [
                        'class' => 'mx-auto m-0 p-0 fs-5 bg-app-dark border-secondary shadow-none form-check-input float-none'
                    ];
                },
                'label_attr' => [
                    'class' => 'flex-grow-1 mb-0 small fw-semibold text-app-secondary text-truncate pointer'
                ],
            ]);
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CustomRoleRequest::class,
            'attr' => [
                'data-turbo-frame' => '_top',
                'class' => 'd-flex flex-column flex-grow-1 min-h-0 mb-0 needs-validation'
            ]
        ]);
    }
}
