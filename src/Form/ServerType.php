<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Server;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<Server>
 */
final class ServerType extends AbstractType
{
    #[Override]
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
            ]);
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Server::class,
            'attr' => [
                'class' => 'd-flex flex-column gap-3',
                'data-turbo-frame' => '_top'
            ]
        ]);
    }
}
