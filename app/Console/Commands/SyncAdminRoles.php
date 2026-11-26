<?php

namespace App\Console\Commands;

use App\Services\Admin\DefaultAdminRoles;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('crm:sync-admin-roles')]
#[Description('Synchronize the built-in admin roles and permissions')]
class SyncAdminRoles extends Command
{
    public function handle(DefaultAdminRoles $roles): int
    {
        $roles->sync();
        $this->info('Built-in admin roles and permissions synchronized. Custom roles were left unchanged.');

        return self::SUCCESS;
    }
}
