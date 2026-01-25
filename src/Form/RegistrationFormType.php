<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $defaultInputClass = 'form-control bg-app-dark border-dark text-white py-2';
        $defaultLabelClass = 'form-label text-uppercase fw-bold small text-app-secondary';

        $builder
            ->add('username', TextType::class, [
                'label' => 'register.label_username',
                'label_attr' => ['class' => $defaultLabelClass],
                'attr' => [
                    'placeholder' => 'register.username_placeholder',
                    'class' => $defaultInputClass,
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'register.label_email',
                'label_attr' => ['class' => $defaultLabelClass],
                'attr' => [
                    'placeholder' => 'register.email_placeholder',
                    'class' => $defaultInputClass,
                ],
            ])
            ->add('plainPassword', PasswordType::class, [
                'mapped' => false,
                'label' => 'register.label_password',
                'label_attr' => ['class' => $defaultLabelClass],
                'attr' => [
                    'placeholder' => 'register.password_placeholder',
                    'autocomplete' => 'new-password',
                    'class' => $defaultInputClass,
                ],
                'constraints' => [
                    new NotBlank(
                        message: 'register.password.not_blank',
                    ),
                    new Length(
                        min: 6,
                        max: 4096,
                        minMessage: 'register.password.min_length',
                    ),
                ],
            ])
            ->add('agreeTerms', CheckboxType::class, [
                'mapped' => false,
                'attr' => [
                    'class' => 'form-check-input bg-app-dark border-dark',
                ],
                'constraints' => [
                    new IsTrue(
                        message: 'register.agree_terms.is_true',
                    ),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
