<?php

namespace Modulos\RH\Services;

use GuzzleHttp\Client;
use InvalidArgumentException;
use JsonException;
use Modulos\RH\Models\DispositivoAcesso;

class ControlIdApiClient
{
    private Client $http;

    public function __construct(?Client $http = null)
    {
        $this->http = $http ?? new Client([
            'timeout' => 10,
            'connect_timeout' => 5,
        ]);
    }

    public function setClient(Client $client): void
    {
        $this->http = $client;
    }

    public function getClient(): Client
    {
        return $this->http;
    }

    public function ping(DispositivoAcesso $dispositivo): array
    {
        return $this->getConfiguration($dispositivo);
    }

    public function loadObjects(DispositivoAcesso $dispositivo, string $object, array $payload = []): array
    {
        return $this->post(
            $dispositivo,
            '/load_objects.fcgi',
            array_merge(['object' => $object], $payload)
        );
    }

    public function getConfiguration(DispositivoAcesso $dispositivo): array
    {
        return $this->post($dispositivo, '/get_configuration.fcgi', []);
    }

    public function setConfiguration(DispositivoAcesso $dispositivo, array $config): array
    {
        return $this->post($dispositivo, '/set_configuration.fcgi', $config);
    }

    public function configurarMonitor(
        DispositivoAcesso $dispositivo,
        string $hostname,
        int $port = 80,
        string $path = 'api/rh/monitor'
    ): array {
        return $this->setConfiguration($dispositivo, [
            'monitor' => [
                'hostname' => $hostname,
                'port' => (string) $port,
                'path' => $path,
                'request_timeout' => '5000',
                'alive_interval' => '30000',
                'inform_access_event_id' => '1',
            ],
        ]);
    }

    public function getSystemInfo(DispositivoAcesso $dispositivo): array
    {
        return $this->loadObjects($dispositivo, 'system_info');
    }

    public function login(DispositivoAcesso $dispositivo): array
    {
        return $this->postSemSessao($dispositivo, '/login.fcgi', [
            'login' => $this->resolverLogin($dispositivo),
            'password' => $this->resolverSenha($dispositivo),
        ]);
    }

    private function baseUrl(DispositivoAcesso $dispositivo): string
    {
        if (!$dispositivo->dis_ip) {
            throw new InvalidArgumentException('O dispositivo informado não possui IP configurado.');
        }

        return 'http://' . trim((string) $dispositivo->dis_ip, '/');
    }

    private function post(DispositivoAcesso $dispositivo, string $endpoint, array $payload): array
    {
        $session = $this->obterSessao($dispositivo);

        return $this->postSemSessao($dispositivo, $endpoint . '?session=' . urlencode($session), $payload);
    }

    private function obterSessao(DispositivoAcesso $dispositivo): string
    {
        $response = $this->login($dispositivo);

        if (empty($response['session']) || !is_string($response['session'])) {
            throw new InvalidArgumentException('Não foi possível obter uma sessão válida no iDFace.');
        }

        return $response['session'];
    }

    private function resolverLogin(DispositivoAcesso $dispositivo): string
    {
        $login = $dispositivo->getAttribute('dis_login_api');

        if (is_string($login) && $login !== '') {
            return $login;
        }

        return $this->resolverConfiguracao('services.controlid.login', 'CONTROLID_LOGIN', 'admin');
    }

    private function resolverSenha(DispositivoAcesso $dispositivo): string
    {
        $senha = $dispositivo->getAttribute('dis_senha_api');

        if (is_string($senha) && $senha !== '') {
            return $senha;
        }

        return $this->resolverConfiguracao('services.controlid.password', 'CONTROLID_PASSWORD', 'admin');
    }

    private function resolverConfiguracao(string $configKey, string $envKey, string $default): string
    {
        if (function_exists('app')) {
            try {
                $app = app();

                if ($app && $app->bound('config')) {
                    return (string) config($configKey, $default);
                }
            } catch (\Throwable $exception) {
            }
        }

        $value = getenv($envKey);

        return $value !== false && $value !== '' ? (string) $value : $default;
    }

    private function postSemSessao(DispositivoAcesso $dispositivo, string $endpoint, array $payload): array
    {
        $response = $this->http->request('POST', $this->baseUrl($dispositivo) . $endpoint, [
            'json' => empty($payload) ? (object) [] : $payload,
        ]);

        $body = (string) $response->getBody();

        try {
            $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);

            if (is_array($decoded)) {
                return $decoded;
            }

            return [
                'value' => $decoded,
                'status_code' => $response->getStatusCode(),
            ];
        } catch (JsonException $exception) {
            return [
                'raw' => $body,
                'status_code' => $response->getStatusCode(),
            ];
        }
    }
}
