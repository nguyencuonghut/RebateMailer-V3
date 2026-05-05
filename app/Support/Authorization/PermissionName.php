<?php

namespace App\Support\Authorization;

enum PermissionName: string
{
    case UsersView = 'users.view';
    case UsersCreate = 'users.create';
    case UsersUpdate = 'users.update';
    case UsersDelete = 'users.delete';

    case ImportsView = 'imports.view';
    case ImportsManage = 'imports.manage';

    case TemplatesView = 'templates.view';
    case TemplatesManage = 'templates.manage';

    case MailView = 'mail.view';
    case MailSend = 'mail.send';

    case TrackingView = 'tracking.view';
    case TrackingManage = 'tracking.manage';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(
            static fn (self $permission): string => $permission->value,
            self::cases(),
        );
    }

    /**
     * @return list<string>
     */
    public static function nonUserCrudValues(): array
    {
        return [
            self::ImportsView->value,
            self::ImportsManage->value,
            self::TemplatesView->value,
            self::TemplatesManage->value,
            self::MailView->value,
            self::MailSend->value,
            self::TrackingView->value,
            self::TrackingManage->value,
        ];
    }

    /**
     * @return list<string>
     */
    public static function guestValues(): array
    {
        return [
            self::ImportsView->value,
            self::MailView->value,
        ];
    }
}
