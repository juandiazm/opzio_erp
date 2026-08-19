<?php

namespace Tests\Unit\Outcomes;

use App\traits\open_ia_trait;
use Tests\TestCase;

class OpenIaTraitModelTest extends TestCase
{
    public function test_it_exposes_cost_appropriate_models_by_purpose(): void
    {
        $service = new class {
            use open_ia_trait;
        };

        $this->assertSame('gpt-5.6-luna', $service->OpenIA_GetModel('fast'));
        $this->assertSame('gpt-5.6-terra', $service->OpenIA_GetModel('chat'));
        $this->assertSame('gpt-5.6-terra', $service->OpenIA_GetModel('content'));
        $this->assertSame('gpt-5.6-sol', $service->OpenIA_GetModel('reasoning'));
        $this->assertSame('gpt-image-2', $service->OpenIA_GetModel('image'));
    }
}