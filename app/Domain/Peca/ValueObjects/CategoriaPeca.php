<?php

namespace App\Domain\Peca\ValueObjects;

final class CategoriaPeca
{
    private const CATEGORIAS_VALIDAS = [
        'motor',
        'transmissao',
        'suspensao',
        'freios',
        'iluminacao',
        'eletrica',
        'filtros',
        'correntes_correia',
        'amortecedores',
        'outros'
    ];

    private string $value;

    public function __construct(string $categoria)
    {
        $categoria = strtolower(trim($categoria));

        if (!in_array($categoria, self::CATEGORIAS_VALIDAS)) {
            throw new \InvalidArgumentException(
                'Categoria inválida. Categorias válidas: ' .
                implode(', ', self::CATEGORIAS_VALIDAS)
            );
        }

        $this->value = $categoria;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public static function listarValidas(): array
    {
        return self::CATEGORIAS_VALIDAS;
    }
}
