<?php

declare(strict_types=1);

namespace App\Form;

use App\Dto\UpdateProfileRequest;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<UpdateProfileRequest>
 */
final class UpdateProfileType extends AbstractType
{
    #[Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $defaultInputClass = 'form-control bg-app-dark border-dark text-white py-2';
        $defaultLabelClass = 'form-label text-uppercase fw-bold small text-app-secondary';

        $builder
            ->add('username', TextType::class, [
                'label' => 'profile.label_username',
                'label_attr' => ['class' => $defaultLabelClass],
                'attr' => [
                    'placeholder' => 'profile.username_placeholder',
                    'class' => $defaultInputClass,
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'profile.label_email',
                'label_attr' => ['class' => $defaultLabelClass],
                'attr' => [
                    'placeholder' => 'profile.email_placeholder',
                    'class' => $defaultInputClass,
                ],
            ])
            ->add('plainPassword', PasswordType::class, [
                'label' => 'profile.label_password',
                'required' => false,
                'label_attr' => ['class' => $defaultLabelClass],
                'attr' => [
                    'placeholder' => 'profile.password_placeholder',
                    'autocomplete' => 'new-password',
                    'class' => $defaultInputClass,
                ],
            ])
        ;
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UpdateProfileRequest::class,
            'attr' => [
                'novalidate' => 'novalidate',
                'class' => 'd-flex flex-column gap-3',
                'data-turbo-frame' => '_top',
            ]
        ]);
    }
}
