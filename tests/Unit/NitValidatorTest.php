<?php

declare(strict_types=1);

namespace Marzsv\DteSigner\Tests\Unit;

use Marzsv\DteSigner\Validators\NitValidator;
use PHPUnit\Framework\TestCase;

class NitValidatorTest extends TestCase
{
    public function testIsValidWithValidNit(): void
    {
        $this->assertTrue(NitValidator::isValid('12345678901234'));
    }

    public function testIsValidWithAllZeros(): void
    {
        $this->assertTrue(NitValidator::isValid('00000000000000'));
    }

    public function testIsValidWithTooShortNit(): void
    {
        $this->assertFalse(NitValidator::isValid('1234567890123'));
    }

    public function testIsValidWithTooLongNit(): void
    {
        $this->assertFalse(NitValidator::isValid('123456789012345'));
    }

    public function testIsValidWithLetters(): void
    {
        $this->assertFalse(NitValidator::isValid('ABCDEFGHIJKLMN'));
    }

    public function testIsValidWithMixedContent(): void
    {
        $this->assertFalse(NitValidator::isValid('1234567890123A'));
    }

    public function testIsValidWithSpecialCharacters(): void
    {
        $this->assertFalse(NitValidator::isValid('1234-5678-9012'));
    }

    public function testIsValidWithEmptyString(): void
    {
        $this->assertFalse(NitValidator::isValid(''));
    }

    public function testValidateWithValidNitReturnsEmptyArray(): void
    {
        $errors = NitValidator::validate('12345678901234');
        $this->assertEmpty($errors);
    }

    public function testValidateWithTooShortNitReturnsLengthError(): void
    {
        $errors = NitValidator::validate('123');
        $this->assertContains('NIT must be exactly 14 characters long', $errors);
        $this->assertContains('NIT must contain only digits', $errors);
    }

    public function testValidateWithLettersReturnsDigitError(): void
    {
        $errors = NitValidator::validate('ABCDEFGHIJKLMN');
        $this->assertContains('NIT must contain only digits', $errors);
    }

    public function testGetExpectedLength(): void
    {
        $this->assertEquals(14, NitValidator::getExpectedLength());
    }
}
