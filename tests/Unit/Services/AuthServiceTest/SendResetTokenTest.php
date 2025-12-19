<?php

namespace Tests\Unit\Services\AuthServiceTest;

use PHPUnit\Framework\Attributes\Test;
use Tests\Unit\Services\AuthServiceTest;

/**
 * @deprecated
 * This test file is intentionally empty and serves no purpose.
 * The `AuthService::sendResetToken` method is a pure delegation to the `SendResetToken` action,
 * which is already fully tested in `tests/Unit/Actions/SendResetToken/`.
 * Since `AuthService` contains no logic of its own, testing it would be redundant.
 * This file can be safely deleted once all `AuthService` logic is delegated to Actions.
 */

class SendResetTokenTest extends AuthServiceTest
{
    #[Test]
    public function test()
    {
        // This serves as a placeholder to satisfy PHPUnit
        $this->assertTrue(true);
    }
}
