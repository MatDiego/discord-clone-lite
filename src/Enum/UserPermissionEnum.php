<?php

declare(strict_types=1);

namespace App\Enum;

enum UserPermissionEnum: string
{
    case MANAGE_SERVER = 'MANAGE_SERVER';
    case MANAGE_ROLES = 'MANAGE_ROLES';
    case MANAGE_CHANNELS = 'MANAGE_CHANNELS';

    case KICK_MEMBERS = 'KICK_MEMBERS';
    case BAN_MEMBERS = 'BAN_MEMBERS';
    case MANAGE_NICKNAMES = 'MANAGE_NICKNAMES';

    case VIEW_CHANNELS = 'VIEW_CHANNELS';
    case SEND_MESSAGES = 'SEND_MESSAGES';
    case MANAGE_MESSAGES = 'MANAGE_MESSAGES';


    case CREATE_INVITE = 'CREATE_INVITE';

    case MANAGE_CHANNEL = 'MANAGE_CHANNEL';
    case MANAGE_PERMISSIONS = 'MANAGE_PERMISSIONS';
    case VIEW_CHANNEL = 'VIEW_CHANNEL';
    case ADD_MEMBER = 'ADD_MEMBER';

    public function trans(): string
    {
        return match ($this) {
            self::MANAGE_SERVER => 'Zarządzanie serwerem',
            self::MANAGE_ROLES => 'Zarządzanie rolami',
            self::MANAGE_CHANNELS => 'Zarządzanie kanałami',
            self::KICK_MEMBERS => 'Wyrzucanie członków',
            self::BAN_MEMBERS => 'Banowanie członków',
            self::MANAGE_NICKNAMES => 'Zarządzanie pseudonimami',
            self::VIEW_CHANNELS => 'Wyświetlanie kanałów',
            self::SEND_MESSAGES => 'Wysyłanie wiadomości',
            self::MANAGE_MESSAGES => 'Zarządzanie wiadomościami',
            self::CREATE_INVITE => 'Tworzenie zaproszeń',
            self::MANAGE_CHANNEL => 'Zarządzanie kanałem',
            self::MANAGE_PERMISSIONS => 'Zarządzanie uprawnieniami',
            self::VIEW_CHANNEL => 'Wyświetlanie kanału',
            self::ADD_MEMBER => 'Dodawanie członków',
        };
    }
}
