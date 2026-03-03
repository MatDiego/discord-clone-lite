<?php

declare(strict_types=1);

namespace App\Twig\Components\Permission;

use App\Dto\PermissionRowViewDTO;
use App\Entity\Channel;
use App\Enum\UserPermissionEnum;
use App\Form\ChannelPermissionsType;
use App\Repository\ServerMemberRepository;
use App\Repository\UserRoleRepository;
use App\Service\ChannelPermissionService;
use InvalidArgumentException;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormView;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;
use Symfony\UX\TwigComponent\Attribute\PostMount;

/**
 * @psalm-suppress PropertyNotSetInConstructor — properties are populated by the Twig Component mount lifecycle.
 */
#[AsTwigComponent('Permission:Editor')]
final class Editor
{
    public Channel $channel;
    public string $selected;
    private string $targetType;
    private string $targetUuid;
    private array $currentOverrides = [];
    private array $inheritedPermissions = [];
    private ?FormView $formView = null;

    public function __construct(
        private readonly ChannelPermissionService $permissionService,
        private readonly ServerMemberRepository $memberRepository,
        private readonly UserRoleRepository $roleRepository,
        private readonly FormFactoryInterface $formFactory,
    ) {
    }

    #[PostMount]
    public function initializeData(): void
    {
        $parts = explode(':', $this->selected);
        if (count($parts) !== 2) {
            throw new InvalidArgumentException(sprintf('Invalid selected target format: "%s"', $this->selected));
        }

        [$this->targetType, $this->targetUuid] = $parts;

        $allOverrides = $this->permissionService->getOverrideGroups($this->channel);

        $this->currentOverrides = $this->permissionService->resolveEffectiveOverrides(
            $this->targetType,
            $this->targetUuid,
            $allOverrides
        );

        $this->inheritedPermissions = $this->permissionService->resolveInheritedPermissions(
            $this->targetType,
            $this->targetUuid,
            UserPermissionEnum::cases(),
            $this->channel
        );
    }

    #[ExposeInTemplate]
    public function getTargetInfo(): array
    {
        if ($this->targetType === 'role') {
            $entity = $this->roleRepository->find($this->targetUuid);
            return [
                'name' => $entity?->getName() ?? 'Unknown role',
                'type' => 'role',
                'icon' => 'bi:shield-fill',
                'class' => 'text-primary'
            ];
        }

        $entity = $this->memberRepository->find($this->targetUuid);
        return [
            'name' => $entity?->getDisplayName() ?? 'Unknown member',
            'type' => 'member',
            'icon' => 'bi:person-fill',
            'class' => 'text-info'
        ];
    }

    #[ExposeInTemplate]
    public function getRows(): array
    {
        $rows = [];
        foreach (UserPermissionEnum::cases() as $perm) {
            $rows[] = new PermissionRowViewDTO(
                $perm,
                $this->inheritedPermissions[$perm->value] ?? null,
                $this->currentOverrides[$perm->value] ?? null
            );
        }

        return $rows;
    }

    #[ExposeInTemplate]
    public function getForm(): FormView
    {
        if ($this->formView !== null) {
            return $this->formView;
        }

        $defaults = [];
        foreach (UserPermissionEnum::cases() as $perm) {
            if (isset($this->currentOverrides[$perm->value])) {
                $defaults[$perm->value] = $this->currentOverrides[$perm->value];
            } elseif (isset($this->inheritedPermissions[$perm->value])) {
                $defaults[$perm->value] = $this->inheritedPermissions[$perm->value];
            } else {
                $defaults[$perm->value] = 'deny';
            }
        }

        $form = $this->formFactory->create(ChannelPermissionsType::class, $defaults);
        $this->formView = $form->createView();

        return $this->formView;
    }
}
