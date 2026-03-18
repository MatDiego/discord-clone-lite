<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Channel;
use App\Entity\ChannelReadState;
use App\Entity\Message;
use App\Entity\User;
use App\Repository\ChannelReadStateRepository;

final readonly class ChannelReadStateService
{
    public function __construct(
        private ChannelReadStateRepository $readStateRepository
    ) {
    }

    public function getReadStateForUserInChannel(User $user, Channel $channel): ?ChannelReadState
    {
        return $this->readStateRepository->findOneBy([
            'owner' => $user,
            'channel' => $channel
        ]);
    }

    public function getLastReadMessageForUserInChannel(User $user, Channel $channel): ?Message
    {
        $readState = $this->getReadStateForUserInChannel($user, $channel);

        return $readState ? $readState->getLastReadMessage() : null;
    }

    public function updateReadState(User $user, Channel $channel, Message $message): void
    {
        $readState = $this->getReadStateForUserInChannel($user, $channel);

        if (!$readState) {
            $readState = new ChannelReadState($channel, $user);
        }

        $readState->setLastReadMessage($message);

        $this->readStateRepository->add($readState);
        $this->readStateRepository->flush();
    }
}
