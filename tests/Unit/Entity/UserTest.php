<?php

namespace App\Tests\Unit\Entity;

use App\Entity\User;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function testGetRolesAlwaysContainsRoleUser(): void
    {
        $user = new User();
        $user->setRoles(['ROLE_ADMIN']);

        self::assertContains('ROLE_USER', $user->getRoles());
        self::assertContains('ROLE_ADMIN', $user->getRoles());
    }

    public function testIsAdminReturnsTrueWhenRoleAdminPresent(): void
    {
        $user = new User();
        $user->setRoles(['ROLE_ADMIN']);

        self::assertTrue($user->isAdmin());
    }

    public function testBlockedDefaultsToFalseAndCanBeChanged(): void
    {
        $user = new User();
        self::assertFalse($user->isBlocked());

        $user->setBlocked(true);
        self::assertTrue($user->isBlocked());
    }
}
