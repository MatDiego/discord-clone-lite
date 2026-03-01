<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Voter;

use App\Entity\Server;
use App\Entity\User;
use App\Enum\UserPermissionEnum;
use App\Security\Voter\ServerVoter;
use App\Service\PermissionService;
use DG\BypassFinals;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

final class ServerVoterTest extends TestCase
{
    private PermissionService $permissionService;
    private ServerVoter $voter;
    private User $user;
    private Server $server;

    #[Override]
    protected function setUp(): void
    {
        BypassFinals::enable();

        $this->permissionService = $this->createMock(PermissionService::class);
        $this->voter = new ServerVoter($this->permissionService);
        $this->user = new User('test@example.com', 'test', 'password');
        $this->server = new Server('Test Server', $this->user);
    }

    #[Test]
    public function it_should_abstain_when_attribute_is_not_supported(): void
    {
        // Arrange
        $token = $this->createStub(TokenInterface::class);

        // Act
        $result = $this->voter->vote($token, $this->server, ['UNSUPPORTED_ACTION']);

        // Assert
        $this->assertSame(VoterInterface::ACCESS_ABSTAIN, $result);
    }

    #[Test]
    public function it_should_abstain_when_subject_is_not_a_server(): void
    {
        // Arrange
        $subject = new \stdClass();
        $token = $this->createStub(TokenInterface::class);

        // Act
        $result = $this->voter->vote($token, $subject, [ServerVoter::VIEW]);

        // Assert
        $this->assertSame(VoterInterface::ACCESS_ABSTAIN, $result);
    }

    #[Test]
    public function it_should_deny_access_when_user_is_not_authenticated(): void
    {
        // Arrange
        $token = $this->createStub(TokenInterface::class);
        $token->method('getUser')->willReturn(null);

        // Act
        $result = $this->voter->vote($token, $this->server, [ServerVoter::VIEW]);

        // Assert
        $this->assertSame(VoterInterface::ACCESS_DENIED, $result);
    }

    #[Test]
    #[DataProvider('provideSupportedAttributesAndExpectedEnums')]
    public function it_should_grant_access_if_user_has_server_permission(string $attribute, UserPermissionEnum $expectedEnum): void
    {
        // Arrange
        $token = $this->createStub(TokenInterface::class);
        $token->method('getUser')->willReturn($this->user);

        $this->permissionService
            ->method('hasServerPermission')
            ->with($this->user, $this->server, $expectedEnum)
            ->willReturn(true);

        // Act
        $result = $this->voter->vote($token, $this->server, [$attribute]);

        // Assert
        $this->assertSame(VoterInterface::ACCESS_GRANTED, $result);
    }

    #[Test]
    #[DataProvider('provideSupportedAttributesAndExpectedEnums')]
    public function it_should_deny_access_if_user_does_not_have_server_permission(string $attribute, UserPermissionEnum $expectedEnum): void
    {
        // Arrange
        $token = $this->createStub(TokenInterface::class);
        $token->method('getUser')->willReturn($this->user);

        $this->permissionService
            ->method('hasServerPermission')
            ->with($this->user, $this->server, $expectedEnum)
            ->willReturn(false);

        // Act
        $result = $this->voter->vote($token, $this->server, [$attribute]);

        // Assert
        $this->assertSame(VoterInterface::ACCESS_DENIED, $result);
    }

    #[Test]
    public function it_should_grant_access_to_delete_server_if_user_is_owner(): void
    {
        // Arrange
        $token = $this->createStub(TokenInterface::class);
        $token->method('getUser')->willReturn($this->user);

        $this->permissionService
            ->method('isOwner')
            ->with($this->user, $this->server)
            ->willReturn(true);

        // Act
        $result = $this->voter->vote($token, $this->server, [ServerVoter::DELETE]);

        // Assert
        $this->assertSame(VoterInterface::ACCESS_GRANTED, $result);
    }

    #[Test]
    public function it_should_deny_access_to_delete_server_if_user_is_not_owner(): void
    {
        // Arrange
        $token = $this->createStub(TokenInterface::class);
        $token->method('getUser')->willReturn($this->user);

        $this->permissionService
            ->method('isOwner')
            ->with($this->user, $this->server)
            ->willReturn(false);

        // Act
        $result = $this->voter->vote($token, $this->server, [ServerVoter::DELETE]);

        // Assert
        $this->assertSame(VoterInterface::ACCESS_DENIED, $result);
    }

    public static function provideSupportedAttributesAndExpectedEnums(): \Generator
    {
        yield 'View Server' => [ServerVoter::VIEW, UserPermissionEnum::VIEW_CHANNELS];
        yield 'Edit Server' => [ServerVoter::EDIT, UserPermissionEnum::MANAGE_SERVER];
        yield 'Create Channel' => [ServerVoter::CREATE_CHANNEL, UserPermissionEnum::MANAGE_CHANNELS];
    }
}
