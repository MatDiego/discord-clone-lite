<?php

namespace App\Form;

use App\Enum\UserPermissionEnum;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ChannelPermissionsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        foreach (UserPermissionEnum::cases() as $permission) {
            $builder->add($permission->value, ChoiceType::class, [
                'choices' => [
                    'Inherit' => '',
                    'Allow' => 'allow',
                    'Deny' => 'deny',
                ],
                'expanded' => true,
                'multiple' => false,
                'required' => false,
                'placeholder' => false,
                'label' => false,
                'choice_attr' => function () {
                    return [
                        'class' => 'btn-check',
                        'autocomplete' => 'off',
                    ];
                },
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'channel_permissions',
            'attr' => [
                'class' => 'd-flex flex-column flex-grow-1 min-h-0',
                'data-turbo-frame' => '_top',
            ],
        ]);
    }
}
