<?php

namespace Tests\Unit\Outcomes;

use App\Services\Outcomes\BoldPasteParser;
use App\Services\Outcomes\OutcomeImportService;
use Tests\TestCase;

class BoldPasteParserTest extends TestCase
{
    public function test_it_parses_pasted_tabular_data_and_keeps_additional_details(): void
    {
        $content = implode("\n", [
            "Fecha transacción\tID transacción\tTipo de movimiento\tDescripción\tValor neto\tComercio\tEstado",
            "05/10/2026 14:30\tTX-001\tCompra\tSuscripción mensual\t-25.000,00\tServicio en línea\tAprobado",
            "05/10/2026 15:00\tTX-002\tAbono\tReintegro\t12.000,00\tCuenta Bold\tAprobado",
            "05/10/2026 16:00\tTX-003\tCompra\tCompra de insumos\t8.500,00\tPapelería\tAprobado",
        ]);

        $result = (new BoldPasteParser())->parse($content);

        $this->assertCount(3, $result['rows']);
        $this->assertSame([], $result['errors']);
        $this->assertSame('2026-10-05', $result['rows'][0]['date']);
        $this->assertSame('-25000.00', $result['rows'][0]['amount']);
        $this->assertSame('TX-001', $result['rows'][0]['identifier']);
        $this->assertStringContainsString('Suscripción mensual', $result['rows'][0]['description']);
        $this->assertStringContainsString('COMERCIO: Servicio en línea', $result['rows'][0]['description']);
        $this->assertSame('12000.00', $result['rows'][1]['amount']);
        $this->assertSame('-8500.00', $result['rows'][2]['amount']);
    }

    public function test_it_treats_positive_amounts_in_debit_columns_as_expenses(): void
    {
        $content = implode("\n", [
            "Fecha\tComercio\tDébito",
            "05/10/2026\tPapelería\t18.500,00",
        ]);

        $result = (new BoldPasteParser())->parse($content);

        $this->assertSame('-18500.00', $result['rows'][0]['amount']);
    }

    public function test_it_skips_positive_non_expense_rows_when_importing_pasted_data(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'bold-paste-');
        $this->assertNotFalse($path);

        try {
            file_put_contents($path, implode("\n", [
                "Fecha\tTipo\tDescripción\tValor",
                "05/10/2026\tAbono\tReintegro\t12.000,00",
            ]));

            $result = app(OutcomeImportService::class)->import($path, 'bold', 1, 'paste');

            $this->assertSame(0, $result['data']['imported']);
            $this->assertSame(1, $result['data']['skipped']);
        } finally {
            unlink($path);
        }
    }

    public function test_it_parses_bold_transaction_cards_and_skips_non_expenses(): void
    {
        $content = implode("\n", [
            '30 de septiembre',
            '',
            'Bold',
            'Transferencia enviada',
            'Bre-B',
            'Beneficiario de prueba',
            '-$500.000,00',
            '30/09/2026 - 12:38:01',
            '',
            'Bold',
            'Transferencia recibida',
            'Bolsillos',
            'Cuenta de prueba',
            '$500.000,00',
            '30/09/2026 - 12:37:37',
            '',
            'Bold',
            'Abono enviado',
            'Bolsillos',
            '-$38.394,00',
            '29/09/2026 - 08:19:02',
            '',
            'Bold',
            'Compra',
            'Tarjeta débito',
            'Comercio de prueba',
            '-$164.700,00',
            '29/09/2026 - 11:06:26',
            '',
            'Bold',
            'Compra reembolsada',
            'Tarjeta débito',
            'Comercio de prueba',
            '-$12.000,00',
            '29/09/2026 - 10:00:00',
        ]);

        $result = (new BoldPasteParser())->parse($content);

        $this->assertCount(5, $result['rows']);
        $this->assertSame([], $result['errors']);
        $this->assertSame('-500000.00', $result['rows'][0]['amount']);
        $this->assertSame('500000.00', $result['rows'][1]['amount']);
        $this->assertSame('38394.00', $result['rows'][2]['amount']);
        $this->assertSame('-164700.00', $result['rows'][3]['amount']);
        $this->assertSame('12000.00', $result['rows'][4]['amount']);
        $this->assertStringContainsString('Tarjeta débito | Comercio de prueba', $result['rows'][3]['description']);
    }
}
