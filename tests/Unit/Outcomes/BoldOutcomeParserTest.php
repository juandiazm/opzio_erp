<?php

namespace Tests\Unit\Outcomes;

use App\Services\Outcomes\BoldOutcomeParser;
use Tests\TestCase;

class BoldOutcomeParserTest extends TestCase
{
    public function test_it_parses_bold_tabular_csv_and_colombian_amounts(): void
    {
        $result = (new BoldOutcomeParser())->parse(base_path('tests/Fixtures/outcomes/bold_statement.csv'));

        $this->assertCount(4, $result['rows']);
        $this->assertSame([], $result['errors']);
        $this->assertSame('2026-06-09', $result['rows'][1]['date']);
        $this->assertSame('-103964.00', $result['rows'][1]['amount']);
        $this->assertSame('-3118.92', $result['rows'][2]['amount']);
        $this->assertSame('493102192231-M-3455479', $result['rows'][1]['identifier']);
    }
}