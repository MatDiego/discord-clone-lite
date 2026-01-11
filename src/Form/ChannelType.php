<?php

namespace App\Form;

use App\Entity\Channel;
use App\Entity\Server;
use App\Enum\ChannelTypeEnum;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class ChannelType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nazwa kanału',
                'attr' => ['placeholder' => 'ogólny', 'autofocus' => true],
                'constraints' => [
                    new NotBlank(['message' => 'Podaj nazwę kanału']),
                    new Length(['max' => 255, 'min' => 2]),
                ],
            ])
            ->add('type', EnumType::class, [
                'class' => ChannelTypeEnum::class,
                'label' => 'Typ kanału',
                'choice_label' => fn ($choice) => match ($choice) {
                    ChannelTypeEnum::TEXT => 'Tekstowy',
                    ChannelTypeEnum::VOICE => 'Głosowy',
                },
                'expanded' => true,
                'multiple' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Channel::class,
        ]);
    }
}
