<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Voter;

use App\Entity\Channel;
use App\Entity\Server;
use App\Entity\User;
use App\Enum\UserPermissionEnum;
use App\Security\Voter\ChannelVoter;
use App\Service\PermissionService;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

final class ChannelVoterTest extends TestCase
{
    private PermissionService $permissionService;
    private ChannelVoter $voter;
    private User $user;
    private Server $server;
    private Channel $channel;

    #[Override]
    protected function setUp(): void
    {
        $this->permissionService = $this->createMock(PermissionService::class);
        $this->voter = new ChannelVoter($this->permissionService);
        $this->user = new User('test@example.com', 'test', 'password');
        $this->server = new Server('Test Server', $this->user);
        $this->channel = new Channel('test-channel', $this->server);
    }

    #[Test]
    public function it_should_abstain_when_attribute_is_not_supported(): void
    {
        // Arrange
        $token = $this->createMock(TokenInterface::class);

        // Act
        $result = $this->voter->vote($token, $this->channel, ['UNSUPPORTED_ACTION']);

        // Assert
        $this->assertSame(VoterInterface::ACCESS_ABSTAIN, $result);
    }

    #[Test]
    public function it_should_abstain_when_subject_is_not_a_channel(): void
    {
        // Arrange
        $subject = new \stdClass();
        $token = $this->createMock(TokenInterface::class);

        // Act
        $result = $this->voter->vote($token, $subject, [ChannelVoter::VIEW_CHANNEL]);

        // Assert
        $this->assertSame(VoterInterface::ACCESS_ABSTAIN, $result);
    }

    #[Test]
    public function it_should_deny_access_when_user_is_not_authenticated(): void
    {
        // Arrange
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn(null);

        // Act
        $result = $this->voter->vote($token, $this->channel, [ChannelVoter::VIEW_CHANNEL]);

        // Assert
        $this->assertSame(VoterInterface::ACCESS_DENIED, $result);
    }

    #[Test]
    #[DataProvider('provideSupportedAttributesAndExpectedEnums')]
    public function it_should_grant_access_if_user_has_permission(string $attribute, UserPermissionEnum $expectedEnum): void
    {
        // Arrange
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($this->user);

        $this->permissionService
            ->method('hasChannelPermission')
            ->with($this->user, $this->channel, $expectedEnum)
            ->willReturn(true);

        // Act
        $result = $this->voter->vote($token, $this->channel, [$attribute]);

        // Assert
        $this->assertSame(VoterInterface::ACCESS_GRANTED, $result);
    }

    #[Test]
    #[DataProvider('provideSupportedAttributesAndExpectedEnums')]
    public function it_should_deny_access_if_user_does_not_have_permission(string $attribute, UserPermissionEnum $expectedEnum): void
    {
        // Arrange
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($this->user);

        $this->permissionService
            ->method('hasChannelPermission')
            ->with($this->user, $this->channel, $expectedEnum)
            ->willReturn(false);

        // Act
        $result = $this->voter->vote($token, $this->channel, [$attribute]);

        // Assert
        $this->assertSame(VoterInterface::ACCESS_DENIED, $result);
    }

    public static function provideSupportedAttributesAndExpectedEnums(): \Generator
    {
        yield 'Edit Channel' => [ChannelVoter::EDIT_CHANNEL, UserPermissionEnum::MANAGE_CHANNEL];
        yield 'Delete Channel' => [ChannelVoter::DELETE_CHANNEL, UserPermissionEnum::MANAGE_CHANNEL];
        yield 'View Channel' => [ChannelVoter::VIEW_CHANNEL, UserPermissionEnum::VIEW_CHANNEL];
        yield 'Send Messages' => [ChannelVoter::SEND_MESSAGES, UserPermissionEnum::SEND_MESSAGES];
        yield 'Add Member' => [ChannelVoter::ADD_MEMBER, UserPermissionEnum::ADD_MEMBER];
        yield 'Manage Permissions' => [ChannelVoter::MANAGE_PERMISSIONS, UserPermissionEnum::MANAGE_PERMISSIONS];
    }
}
