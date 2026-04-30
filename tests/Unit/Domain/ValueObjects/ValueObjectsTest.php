<?php

namespace Tests\Unit\Domain\ValueObjects;

use App\Domain\Cliente\ValueObjects\Cpf;
use App\Domain\Cliente\ValueObjects\Cnpj;
use App\Domain\Veiculo\ValueObjects\Placa;
use PHPUnit\Framework\TestCase;

class ValueObjectsTest extends TestCase
{
    // ── CPF ───────────────────────────────────────────────────────

    public function test_cpf_valido_e_criado_com_sucesso(): void
    {
        $cpf = new Cpf('529.982.247-25');
        $this->assertEquals('52998224725', $cpf->getRaw());
    }

    public function test_cpf_sem_formatacao_e_valido(): void
    {
        $cpf = new Cpf('52998224725');
        $this->assertEquals('52998224725', $cpf->getRaw());
    }

    public function test_cpf_invalido_lanca_excecao(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('CPF inválido.');
        new Cpf('00000000000');
    }

    public function test_cpf_com_digitos_iguais_invalido(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Cpf('11111111111');
    }

    public function test_cpf_tamanho_errado_invalido(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Cpf('1234567890');
    }

    public function test_cpf_to_string_retorna_formatado(): void
    {
        $cpf = new Cpf('52998224725');
        $this->assertEquals('529.982.247-25', (string) $cpf);
    }

    // ── CNPJ ──────────────────────────────────────────────────────

    public function test_cnpj_valido_e_criado_com_sucesso(): void
    {
        $cnpj = new Cnpj('11.222.333/0001-81');
        $this->assertEquals('11222333000181', $cnpj->getRaw());
    }

    public function test_cnpj_sem_formatacao_e_valido(): void
    {
        $cnpj = new Cnpj('11222333000181');
        $this->assertEquals('11222333000181', $cnpj->getRaw());
    }

    public function test_cnpj_invalido_lanca_excecao(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('CNPJ inválido.');
        new Cnpj('00000000000000');
    }

    public function test_cnpj_digitos_iguais_invalido(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Cnpj('11111111111111');
    }

    public function test_cnpj_tamanho_errado_invalido(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Cnpj('123456789');
    }

    public function test_cnpj_to_string_retorna_formatado(): void
    {
        $cnpj = new Cnpj('11222333000181');
        $this->assertEquals('11.222.333/0001-81', (string) $cnpj);
    }

    // ── Placa ─────────────────────────────────────────────────────

    public function test_placa_padrao_antigo_valida(): void
    {
        $placa = new Placa('ABC1234');
        $this->assertEquals('ABC1234', $placa->getRaw());
    }

    public function test_placa_mercosul_valida(): void
    {
        $placa = new Placa('ABC1D23');
        $this->assertEquals('ABC1D23', $placa->getRaw());
    }

    public function test_placa_com_hifen_e_valida(): void
    {
        $placa = new Placa('ABC-1234');
        $this->assertEquals('ABC1234', $placa->getRaw());
    }

    public function test_placa_invalida_lanca_excecao(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Placa inválida.');
        new Placa('INVALIDA');
    }

    public function test_placa_vazia_invalida(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Placa('');
    }

    public function test_placa_minuscula_e_convertida(): void
    {
        $placa = new Placa('abc1234');
        $this->assertEquals('ABC1234', (string) $placa);
    }

    public function test_placa_to_string_retorna_valor(): void
    {
        $placa = new Placa('XYZ9876');
        $this->assertEquals('XYZ9876', (string) $placa);
    }
}
