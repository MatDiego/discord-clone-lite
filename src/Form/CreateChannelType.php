<?php

namespace App\Form;

use App\Dto\CreateChannelRequest;
use App\Enum\ChannelTypeEnum;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CreateChannelType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'channel.label_name',
                'label_attr' => ['class' => 'form-label text-uppercase fw-bold small text-app-secondary'],
                'attr' => [
                    'placeholder' => 'channel.name_placeholder',
                    'autofocus' => true,
                    'class' => 'form-control text-white'
                ],
            ])
            ->add('type', EnumType::class, [
                'class' => ChannelTypeEnum::class,
                'label' => 'channel.label_type',
                'expanded' => true,
                'multiple' => false,
                'choice_attr' => function () {
                    return ['class' => 'btn-check'];
                },
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CreateChannelRequest::class,
            'attr' => [
                'class' => 'd-flex flex-column gap-3',
                'novalidate' => 'novalidate',
                'data-turbo-frame' => '_top'
            ]
        ]);
    }
}
