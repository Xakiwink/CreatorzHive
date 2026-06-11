<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class ValidatorTest extends TestCase
{
    public function testRequiredRuleFailsOnEmptyString(): void
    {
        $r = validator_validate(['x' => ''], ['x' => 'required']);
        $this->assertFalse($r['valid']);
    }

    public function testEmailRuleFailsOnInvalidEmail(): void
    {
        $r = validator_validate(['e' => 'not-an-email'], ['e' => 'required|email']);
        $this->assertFalse($r['valid']);
    }

    public function testMinRuleFailsOnShortString(): void
    {
        $r = validator_validate(['p' => 'abc'], ['p' => 'required|min:8']);
        $this->assertFalse($r['valid']);
    }

    public function testMaxRuleFailsOnLongString(): void
    {
        $long = str_repeat('a', 300);
        $r = validator_validate(['t' => $long], ['t' => 'required|max:255']);
        $this->assertFalse($r['valid']);
    }

    public function testConfirmedRuleFailsOnMismatch(): void
    {
        $r = validator_validate(
            ['password' => 'secret123', 'password_confirmation' => 'other'],
            ['password' => 'required|confirmed']
        );
        $this->assertFalse($r['valid']);
    }

    public function testInRuleFailsOnInvalidValue(): void
    {
        $r = validator_validate(['status' => 'bogus'], ['status' => 'in:draft,published']);
        $this->assertFalse($r['valid']);
    }
}
