<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Channel;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<Channel>
 */
final class ChannelType extends AbstractType
{
    #[Override]
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
        ;
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Channel::class,
            'attr' => [
                'class' => 'd-flex flex-column gap-3',
                'novalidate' => 'novalidate',
                'data-turbo-frame' => '_top'
            ]
        ]);
    }
}
