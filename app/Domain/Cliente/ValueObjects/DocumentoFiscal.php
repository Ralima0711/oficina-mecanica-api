<?php

namespace App\Domain\Cliente\ValueObjects;

interface DocumentoFiscal
{
    public function getRaw(): string;
    public function __toString(): string;
}