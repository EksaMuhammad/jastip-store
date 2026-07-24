<?php

namespace App\Exceptions;

class InsufficientBalanceException extends \RuntimeException
{
    public static function forWallet(float $balance, float $required): self
    {
        return new self("Saldo wallet tidak cukup. Saldo saat ini: {$balance}, dibutuhkan: {$required}.");
    }
}