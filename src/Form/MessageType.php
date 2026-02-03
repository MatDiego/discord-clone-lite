<?php

namespace App\Form;

use App\Entity\Channel;
use App\Entity\Message;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Contracts\Translation\TranslatorInterface;

class MessageType extends AbstractType
{
    public function __construct(private readonly TranslatorInterface $translator) {}
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $channel = $options['channel'];
        $placeholder = $channel
            ? $this->translator->trans(
                'message.content_placeholder',
                ['%name%' => $channel->getName()]
            )
            : '...';

        $builder
            ->add('content', TextType::class, [
                'label' => false,
                'translation_domain' => false,
                'attr' => [
                    'class' => 'form-control h-100 py-2',
                    'autocomplete' => 'off',
                    'autofocus' => true,
                    'placeholder' => $placeholder,
                ],
                'constraints' => [
                    new NotBlank(
                        message: 'message.content.not_blank'
                    ),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Message::class,
            'channel' => null,
            'attr' => [
                'class' => 'd-flex align-items-center gap-2 h-100',
                'novalidate' => 'novalidate',
                'data-controller' => 'form-reset chat-validation',
                'data-action' => 'turbo:submit-end->form-reset#reset input->chat-validation#validate',
            ],
        ]);

        $resolver->setAllowedTypes('channel', [Channel::class, 'null']);
    }
}
