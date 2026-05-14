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
        ]);

        $this->assertCount(2, $history);
        $this->assertSame('/create_objects.fcgi', $history[1]['request']->getUri()->getPath());
        $this->assertSame('session=crt789', $history[1]['request']->getUri()->getQuery());
        $this->assertJsonStringEqualsJsonString(
            json_encode([
                'object' => 'users',
                'values' => [
                    [
                        'registration' => '15',
                        'name' => 'Maria Silva',
                        'password' => '',
                    ],
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

    public function testSetUserImageUsesBinaryPayloadAndQueryString(): void
    {
        $history = [];
        $mock = new MockHandler([
            new Response(200, [], json_encode(['session' => 'img654'])),
            new Response(200, [], json_encode(['success' => true, 'user_id' => 99])),
        ]);
        $handler = HandlerStack::create($mock);
        $handler->push(\GuzzleHttp\Middleware::history($history));

        $client = new Client(['handler' => $handler]);
        $apiClient = new ControlIdApiClient($client);

        $dispositivo = new DispositivoAcesso();
        $dispositivo->dis_ip = '192.168.0.10';

        $response = $apiClient->setUserImage($dispositivo, 99, 'jpeg-binary', 1715600000, true);

        $this->assertCount(2, $history);
        $this->assertSame('/user_set_image.fcgi', $history[1]['request']->getUri()->getPath());
        $this->assertStringContainsString('user_id=99', $history[1]['request']->getUri()->getQuery());
        $this->assertStringContainsString('timestamp=1715600000', $history[1]['request']->getUri()->getQuery());
        $this->assertStringContainsString('match=1', $history[1]['request']->getUri()->getQuery());
        $this->assertStringContainsString('session=img654', $history[1]['request']->getUri()->getQuery());
        $this->assertSame('application/octet-stream', $history[1]['request']->getHeaderLine('Content-Type'));
        $this->assertSame('jpeg-binary', (string) $history[1]['request']->getBody());
        $this->assertSame(['success' => true, 'user_id' => 99], $response);
    }

    public function testTestUserImageUsesBinaryPayload(): void
    {
        $history = [];
        $mock = new MockHandler([
            new Response(200, [], json_encode(['session' => 'img987'])),
            new Response(200, [], json_encode(['success' => true])),
        ]);
        $handler = HandlerStack::create($mock);
        $handler->push(\GuzzleHttp\Middleware::history($history));

        $client = new Client(['handler' => $handler]);
        $apiClient = new ControlIdApiClient($client);

        $dispositivo = new DispositivoAcesso();
        $dispositivo->dis_ip = '192.168.0.10';

        $response = $apiClient->testUserImage($dispositivo, 'png-binary');

        $this->assertCount(2, $history);
        $this->assertSame('/user_test_image.fcgi', $history[1]['request']->getUri()->getPath());
        $this->assertStringContainsString('session=img987', $history[1]['request']->getUri()->getQuery());
        $this->assertSame('application/octet-stream', $history[1]['request']->getHeaderLine('Content-Type'));
        $this->assertSame('png-binary', (string) $history[1]['request']->getBody());
        $this->assertSame(['success' => true], $response);
    }
}
