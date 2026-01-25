<?php

namespace App\Form;

use App\Entity\Server;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class ServerType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'server.label_name',
                'label_attr' => ['class' => 'form-label text-uppercase fw-bold small text-app-secondary'],
                'attr' => [
                    'placeholder' => 'server.name_placeholder',
                    'autofocus' => true,
                    'class' => 'form-control text-white'
                ],
                'constraints' => [
                    new NotBlank(message: 'server.name.not_blank'),
                    new Length(max: 100),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Server::class,
        ]);
    }
}
