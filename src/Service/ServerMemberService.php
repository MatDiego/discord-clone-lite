<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\MemberRole;
use App\Entity\Server;
use App\Entity\ServerMember;
use App\Entity\User;
use App\Repository\MemberRoleRepository;
use App\Repository\ServerMemberRepository;
use App\Repository\UserRoleRepository;

final readonly class ServerMemberService
{
    public function __construct(
        private ServerMemberRepository $memberRepository,
        private UserRoleRepository $userRoleRepository,
        private MemberRoleRepository $memberRoleRepository,
    ) {
    }

    public function addMember(Server $server, User $user): ServerMember
    {
        $member = new ServerMember($server, $user);
        $this->memberRepository->add($member);

        $defaultRole = $this->userRoleRepository->findDefaultRoleForServer($server);
        if ($defaultRole !== null) {
            $this->memberRoleRepository->add(new MemberRole($member, $defaultRole));
        }

        return $member;
    }
}
