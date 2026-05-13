<?php

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Modulos\RH\Models\DispositivoAcesso;
use Modulos\RH\Services\ControlIdApiClient;

class ControlIdApiClientTest extends \PHPUnit\Framework\TestCase
{
    public function testLoadObjectsUsesExpectedEndpointAndPayload(): void
    {
        $history = [];
        $mock = new MockHandler([
            new Response(200, [], json_encode(['session' => 'abc123'])),
            new Response(200, [], json_encode(['users' => [['id' => 1]]])),
        ]);
        $handler = HandlerStack::create($mock);
        $handler->push(\GuzzleHttp\Middleware::history($history));

        $client = new Client(['handler' => $handler]);
        $apiClient = new ControlIdApiClient($client);

        $dispositivo = new DispositivoAcesso();
        $dispositivo->dis_ip = '192.168.0.10';

        $response = $apiClient->loadObjects($dispositivo, 'users');

        $this->assertCount(2, $history);
        $this->assertSame('/login.fcgi', $history[0]['request']->getUri()->getPath());
        $this->assertJsonStringEqualsJsonString(
            json_encode(['login' => 'admin', 'password' => 'admin']),
            (string) $history[0]['request']->getBody()
        );
        $this->assertSame('POST', $history[1]['request']->getMethod());
        $this->assertSame('/load_objects.fcgi', $history[1]['request']->getUri()->getPath());
        $this->assertSame('session=abc123', $history[1]['request']->getUri()->getQuery());
        $this->assertJsonStringEqualsJsonString(
            json_encode(['object' => 'users']),
            (string) $history[1]['request']->getBody()
        );
        $this->assertSame(['users' => [['id' => 1]]], $response);
    }

    public function testGetConfigurationUsesExpectedEndpoint(): void
    {
        $history = [];
        $mock = new MockHandler([
            new Response(200, [], json_encode(['session' => 'cfg456'])),
            new Response(200, [], json_encode(['general' => ['name' => 'iDFace']])),
        ]);
        $handler = HandlerStack::create($mock);
        $handler->push(\GuzzleHttp\Middleware::history($history));

        $client = new Client(['handler' => $handler]);
        $apiClient = new ControlIdApiClient($client);

        $dispositivo = new DispositivoAcesso();
        $dispositivo->dis_ip = '192.168.0.10';

        $response = $apiClient->getConfiguration($dispositivo);

        $this->assertCount(2, $history);
        $this->assertSame('/login.fcgi', $history[0]['request']->getUri()->getPath());
        $this->assertSame('/get_configuration.fcgi', $history[1]['request']->getUri()->getPath());
        $this->assertSame('session=cfg456', $history[1]['request']->getUri()->getQuery());
        $this->assertJsonStringEqualsJsonString(
            json_encode(new stdClass()),
            (string) $history[1]['request']->getBody()
        );
        $this->assertSame(['general' => ['name' => 'iDFace']], $response);
    }

    public function testCreateUserUsesExpectedEndpointAndPayload(): void
    {
        $history = [];
        $mock = new MockHandler([
            new Response(200, [], json_encode(['session' => 'crt789'])),
            new Response(200, [], json_encode(['ids' => [12]])),
        ]);
        $handler = HandlerStack::create($mock);
        $handler->push(\GuzzleHttp\Middleware::history($history));

        $client = new Client(['handler' => $handler]);
        $apiClient = new ControlIdApiClient($client);

        $dispositivo = new DispositivoAcesso();
        $dispositivo->dis_ip = '192.168.0.10';

        $response = $apiClient->createUser($dispositivo, [
            'registration' => '15',
            'name' => 'Maria Silva',
            'password' => '',
            'user_type_id' => 1,
        ]);

        $this->assertCount(2, $history);
        $this->assertSame('/create_objects.fcgi', $history[1]['request']->getUri()->getPath());
        $this->assertSame('session=crt789', $history[1]['request']->getUri()->getQuery());
        $this->assertJsonStringEqualsJsonString(
            json_encode([
                'object' => 'users',
                'values' => [
                    'registration' => '15',
                    'name' => 'Maria Silva',
                    'password' => '',
                    'user_type_id' => 1,
                ],
            ]),
            (string) $history[1]['request']->getBody()
        );
        $this->assertSame(['ids' => [12]], $response);
    }

    public function testGetUserImageUsesGetRequestWithSession(): void
    {
        $history = [];
        $mock = new MockHandler([
            new Response(200, [], json_encode(['session' => 'img321'])),
            new Response(200, [], 'raw-image-content'),
        ]);
        $handler = HandlerStack::create($mock);
        $handler->push(\GuzzleHttp\Middleware::history($history));

        $client = new Client(['handler' => $handler]);
        $apiClient = new ControlIdApiClient($client);

        $dispositivo = new DispositivoAcesso();
        $dispositivo->dis_ip = '192.168.0.10';

        $response = $apiClient->getUserImage($dispositivo, 99);

        $this->assertCount(2, $history);
        $this->assertSame('GET', $history[1]['request']->getMethod());
        $this->assertSame('/user_get_image.fcgi', $history[1]['request']->getUri()->getPath());
        $this->assertStringContainsString('user_id=99', $history[1]['request']->getUri()->getQuery());
        $this->assertStringContainsString('session=img321', $history[1]['request']->getUri()->getQuery());
        $this->assertSame('raw-image-content', $response);
    }
}
