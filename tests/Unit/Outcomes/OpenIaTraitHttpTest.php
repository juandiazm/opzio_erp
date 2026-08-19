<?php

namespace Tests\Unit\Outcomes;

use App\traits\open_ia_trait;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use ReflectionClass;
use Tests\TestCase;

class OpenIaTraitHttpTest extends TestCase
{
    public function test_it_reads_responses_text_output(): void
    {
        $service = $this->makeService([
            new Response(200, [], json_encode([
                'id' => 'resp_test_1',
                'model' => 'gpt-5.6-luna',
                'output' => [[
                    'type' => 'message',
                    'content' => [[
                        'type' => 'output_text',
                        'text' => 'Respuesta de prueba',
                    ]],
                ]],
            ])),
        ]);

        $result = $service->OpenIA_MakeQuestion('Pregunta de prueba');

        $this->assertSame(1, $result['status']);
        $this->assertSame(['Respuesta de prueba'], $result['data']);
        $this->assertSame('resp_test_1', $result['response_id']);
    }

    public function test_it_adapts_conversation_items_to_the_legacy_chat_shape(): void
    {
        $service = $this->makeService([
            new Response(200, [], json_encode([
                'object' => 'list',
                'data' => [[
                    'id' => 'msg_test_1',
                    'type' => 'message',
                    'role' => 'user',
                    'content' => [[
                        'type' => 'input_text',
                        'text' => 'Hola desde una conversacion',
                    ]],
                ]],
            ])),
        ]);

        $result = $service->OpenIA_GetMessages('conv_test_1');

        $this->assertSame(1, $result['status']);
        $this->assertSame('msg_test_1', $result['data']['data'][0]['id']);
        $this->assertSame('user', $result['data']['data'][0]['role']);
        $this->assertSame('Hola desde una conversacion', $result['data']['data'][0]['content'][0]['text']['value']);
    }

    public function test_it_exposes_new_image_base64_as_a_legacy_url(): void
    {
        $service = $this->makeService([
            new Response(200, [], json_encode([
                'created' => 1760000000,
                'data' => [[
                    'b64_json' => 'aGVsbG8=',
                ]],
            ])),
        ]);

        $result = $service->OpenIA_GenerateImage('Imagen de prueba');

        $this->assertSame(1, $result['status']);
        $this->assertSame('data:image/webp;base64,aGVsbG8=', $result['data']['data'][0]['url']);
    }

    private function makeService(array $responses): object
    {
        $service = new class {
            use open_ia_trait;
        };
        $handler = HandlerStack::create(new MockHandler($responses));
        $client = new Client([
            'base_uri' => 'https://api.openai.com/v1/',
            'handler' => $handler,
            'http_errors' => false,
        ]);
        $property = (new ReflectionClass($service))->getProperty('OpenIAClient');
        $property->setAccessible(true);
        $property->setValue($service, $client);

        return $service;
    }
}