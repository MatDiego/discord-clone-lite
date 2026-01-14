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
        $builder
            ->add('username', TextType::class, [
                'label' => 'register.username',
                'attr' => [
                    'placeholder' => 'register.username_placeholder',
                    'class' => 'form-control',
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'register.email',
                'attr' => [
                    'placeholder' => 'register.email_placeholder',
                ],
            ])
            ->add('plainPassword', PasswordType::class, [
                'mapped' => false,
                'label' => 'register.password',
                'attr' => [
                    'placeholder' => 'register.password_placeholder',
                    'autocomplete' => 'new-password',
                ],
                'constraints' => [
                    new NotBlank(
                        message: 'Proszę podać hasło',
                    ),
                    new Length(
                        min: 6,
                        max: 4096,
                        minMessage: 'Hasło musi mieć minimum {{ limit }} znaków',
                    ),
                ],
            ])
            ->add('agreeTerms', CheckboxType::class, [
                'mapped' => false,
                'label' => 'register.agree_terms',
                'constraints' => [
                    new IsTrue(
                        message: 'Musisz zaakceptować regulamin.',
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
