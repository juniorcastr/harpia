<?php

namespace Modulos\RH\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modulos\RH\Models\DispositivoAcesso;
use Modulos\RH\Models\MapeamentoDispositivo;
use Modulos\RH\Repositories\ColaboradorRepository;
use Modulos\RH\Repositories\MapeamentoDispositivoRepository;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class SincronizacaoUsuariosDispositivoService
{
    public function __construct(
        private ControlIdApiClient $apiClient,
        private ColaboradorRepository $colaboradorRepository,
        private MapeamentoDispositivoRepository $mapeamentoRepository
    ) {
    }

    public function consultar(DispositivoAcesso $dispositivo): array
    {
        $usuarios = $this->extrairUsuarios(
            $this->apiClient->loadObjects($dispositivo, 'users')
        );

        $mapeamentos = $this->mapeamentoRepository->listarPorDispositivoEUsuarios(
            $dispositivo->dis_id,
            array_values(array_filter(array_map(function (array $usuario) {
                return isset($usuario['id']) ? (string) $usuario['id'] : null;
            }, $usuarios)))
        );

        $usuariosCombinados = $this->combinarUsuariosComMapeamentos($usuarios, $mapeamentos->toArray());

        return [
            'usuarios' => $usuariosCombinados,
            'resumo' => $this->resumirUsuarios($usuariosCombinados),
        ];
    }

    public function sincronizar(DispositivoAcesso $dispositivo): array
    {
        $usuarios = $this->extrairUsuarios(
            $this->apiClient->loadObjects($dispositivo, 'users')
        );

        DB::transaction(function () use ($dispositivo, $usuarios) {
            $userIds = [];

            foreach ($usuarios as $usuario) {
                $userId = isset($usuario['id']) ? (string) $usuario['id'] : null;

                if ($userId === null || $userId === '') {
                    continue;
                }

                $userIds[] = $userId;

                $mapeamentoExistente = $this->mapeamentoRepository->buscarPorDispositivoEUsuario($dispositivo->dis_id, $userId);
                $colaborador = $this->resolverColaboradorParaSincronizacao($mapeamentoExistente, $usuario);

                MapeamentoDispositivo::updateOrCreate(
                    [
                        'map_dis_id' => $dispositivo->dis_id,
                        'map_user_id' => $userId,
                    ],
                    [
                        'map_col_id' => $colaborador?->col_id,
                        'map_registration' => (string) ($usuario['registration'] ?? ''),
                        'map_nome_dispositivo' => (string) ($usuario['name'] ?? ''),
                        'map_ativo' => true,
                    ]
                );

                Cache::forget(sprintf('matching:%d:%s', $dispositivo->dis_id, $userId));
            }

            $query = MapeamentoDispositivo::where('map_dis_id', $dispositivo->dis_id);

            if (!empty($userIds)) {
                $query->whereNotIn('map_user_id', $userIds);
            }

            $query->update(['map_ativo' => false]);
        });

        return $this->consultar($dispositivo);
    }

    public function criarUsuario(
        DispositivoAcesso $dispositivo,
        int $colaboradorId,
        array $dados = [],
        ?UploadedFile $foto = null
    ): array {
        $colaborador = $this->colaboradorRepository->buscarAtivoComPessoa($colaboradorId);

        if (!$colaborador || !$colaborador->pessoa) {
            throw new InvalidArgumentException('Colaborador ativo não encontrado para cadastro no dispositivo.');
        }

        if ($this->mapeamentoRepository->buscarPorDispositivoEColaborador($dispositivo->dis_id, $colaboradorId)) {
            throw new InvalidArgumentException('Este colaborador já está vinculado a um usuário neste dispositivo.');
        }

        $nome = trim((string) ($dados['nome'] ?? ''));
        $registration = trim((string) ($dados['registration'] ?? ''));

        $nome = $nome !== '' ? $nome : (string) $colaborador->pessoa->pes_nome;
        $registration = $registration !== '' ? $registration : (string) $colaborador->col_id;

        $resposta = $this->apiClient->createUser($dispositivo, [
            'registration' => $registration,
            'name' => $nome,
            'password' => '',
            'user_type_id' => 1,
        ]);

        $userId = $this->resolverUserIdNaResposta($resposta);
        $sincronizacao = $this->sincronizar($dispositivo);
        $usuario = $this->buscarUsuarioSincronizado($sincronizacao['usuarios'], $userId, $registration);

        if (!$usuario) {
            throw new InvalidArgumentException('Usuário criado no dispositivo, mas não foi possível localizá-lo para concluir a sincronização.');
        }

        $this->vincularUsuario($dispositivo, (string) $usuario['user_id'], $colaboradorId);

        if ($foto) {
            $this->apiClient->setUserImage(
                $dispositivo,
                (int) $usuario['user_id'],
                $this->converterImagemParaBase64($foto)
            );
        }

        return $this->buscarUsuario($dispositivo, (string) $usuario['user_id']) ?? $usuario;
    }

    public function atualizarUsuario(
        DispositivoAcesso $dispositivo,
        string $userId,
        array $dados = [],
        ?UploadedFile $foto = null
    ): array {
        $nome = trim((string) ($dados['nome'] ?? ''));
        $registration = trim((string) ($dados['registration'] ?? ''));

        $payload = [];

        if ($nome !== '') {
            $payload['name'] = $nome;
        }

        if ($registration !== '') {
            $payload['registration'] = $registration;
        }

        if (!empty($payload)) {
            $this->apiClient->modifyUser($dispositivo, (int) $userId, $payload);
        }

        if ($foto) {
            $this->apiClient->setUserImage(
                $dispositivo,
                (int) $userId,
                $this->converterImagemParaBase64($foto)
            );
        }

        if (!empty($dados['col_id'])) {
            $this->vincularUsuario($dispositivo, $userId, (int) $dados['col_id']);
        }

        $sincronizacao = $this->sincronizar($dispositivo);

        return $this->buscarUsuarioSincronizado($sincronizacao['usuarios'], $userId, $registration)
            ?? throw new InvalidArgumentException('Não foi possível localizar o usuário atualizado no dispositivo.');
    }

    public function removerUsuario(DispositivoAcesso $dispositivo, string $userId): void
    {
        $this->apiClient->destroyUser($dispositivo, (int) $userId);

        $mapeamento = $this->mapeamentoRepository->buscarPorDispositivoEUsuario($dispositivo->dis_id, $userId);

        if ($mapeamento) {
            $mapeamento->fill([
                'map_ativo' => false,
                'map_col_id' => null,
            ])->save();

            Cache::forget(sprintf('matching:%d:%s', $dispositivo->dis_id, $userId));
        }
    }

    public function removerFotoUsuario(DispositivoAcesso $dispositivo, string $userId): void
    {
        $this->apiClient->destroyUserImage($dispositivo, (int) $userId);
    }

    public function vincularUsuario(DispositivoAcesso $dispositivo, string $userId, int $colaboradorId): MapeamentoDispositivo
    {
        $colaborador = $this->colaboradorRepository->buscarAtivoComPessoa($colaboradorId);

        if (!$colaborador) {
            throw new InvalidArgumentException('Colaborador ativo não encontrado para vinculação.');
        }

        if ($this->mapeamentoRepository->buscarPorDispositivoEColaborador($dispositivo->dis_id, $colaboradorId, $userId)) {
            throw new InvalidArgumentException('Este colaborador já está vinculado a outro usuário neste dispositivo.');
        }

        $mapeamento = $this->mapeamentoRepository->buscarPorDispositivoEUsuario($dispositivo->dis_id, $userId);

        if (!$mapeamento) {
            $usuario = $this->buscarUsuario($dispositivo, $userId);

            if (!$usuario) {
                throw new InvalidArgumentException('Usuário do dispositivo não encontrado para vinculação.');
            }

            $mapeamento = new MapeamentoDispositivo([
                'map_dis_id' => $dispositivo->dis_id,
                'map_user_id' => $userId,
                'map_registration' => (string) ($usuario['registration'] ?? ''),
                'map_nome_dispositivo' => (string) ($usuario['nome'] ?? ''),
                'map_ativo' => true,
            ]);
        }

        $mapeamento->fill([
            'map_col_id' => $colaboradorId,
            'map_ativo' => true,
        ])->save();

        Cache::forget(sprintf('matching:%d:%s', $dispositivo->dis_id, $userId));

        return $mapeamento;
    }

    public function desvincularUsuario(DispositivoAcesso $dispositivo, string $userId): void
    {
        $mapeamento = $this->mapeamentoRepository->buscarPorDispositivoEUsuario($dispositivo->dis_id, $userId);

        if (!$mapeamento) {
            throw new InvalidArgumentException('Mapeamento não encontrado para desvinculação.');
        }

        $mapeamento->fill([
            'map_col_id' => null,
            'map_ativo' => true,
        ])->save();

        Cache::forget(sprintf('matching:%d:%s', $dispositivo->dis_id, $userId));
    }

    public function buscarUsuario(DispositivoAcesso $dispositivo, string $userId): ?array
    {
        $consulta = $this->consultar($dispositivo);

        foreach ($consulta['usuarios'] as $usuario) {
            if ((string) $usuario['user_id'] === $userId) {
                return $usuario;
            }
        }

        return null;
    }

    public function extrairUsuarios(array $response): array
    {
        foreach (['users', 'values', 'objects'] as $key) {
            if (isset($response[$key]) && is_array($response[$key])) {
                return array_values(array_filter($response[$key], 'is_array'));
            }
        }

        return array_values(array_filter($response, 'is_array'));
    }

    public function combinarUsuariosComMapeamentos(array $usuarios, array $mapeamentos): array
    {
        $mapeamentosPorUsuario = [];

        foreach ($mapeamentos as $mapeamento) {
            $userId = (string) ($mapeamento['map_user_id'] ?? '');

            if ($userId !== '') {
                $mapeamentosPorUsuario[$userId] = $mapeamento;
            }
        }

        return array_values(array_map(function (array $usuario) use ($mapeamentosPorUsuario) {
            $userId = isset($usuario['id']) ? (string) $usuario['id'] : '';
            $mapeamento = $mapeamentosPorUsuario[$userId] ?? [];

            $fotoOk = !empty($usuario['image_timestamp'])
                || !empty($usuario['has_image'])
                || !empty($usuario['image']);

            return [
                'user_id' => $userId,
                'registration' => (string) ($usuario['registration'] ?? ''),
                'nome' => (string) ($usuario['name'] ?? ''),
                'foto_ok' => $fotoOk,
                'col_id' => $mapeamento['map_col_id'] ?? null,
                'colaborador_nome' => $mapeamento['colaborador_nome'] ?? null,
                'map_ativo' => (bool) ($mapeamento['map_ativo'] ?? true),
                'status_vinculo' => !empty($mapeamento['map_col_id']) ? 'vinculado' : 'pendente',
            ];
        }, $usuarios));
    }

    public function resumirUsuarios(array $usuarios): array
    {
        $resumo = [
            'total' => count($usuarios),
            'vinculados' => 0,
            'pendentes' => 0,
            'com_foto' => 0,
            'sem_foto' => 0,
        ];

        foreach ($usuarios as $usuario) {
            if (!empty($usuario['col_id'])) {
                $resumo['vinculados']++;
            } else {
                $resumo['pendentes']++;
            }

            if (!empty($usuario['foto_ok'])) {
                $resumo['com_foto']++;
            } else {
                $resumo['sem_foto']++;
            }
        }

        return $resumo;
    }

    private function resolverColaboradorParaSincronizacao(?MapeamentoDispositivo $mapeamentoExistente, array $usuario)
    {
        if ($mapeamentoExistente && $mapeamentoExistente->map_col_id) {
            $colaborador = $this->colaboradorRepository->buscarAtivoComPessoa((int) $mapeamentoExistente->map_col_id);

            if ($colaborador) {
                return $colaborador;
            }
        }

        return $this->colaboradorRepository->buscarAtivoPorRegistration((string) ($usuario['registration'] ?? ''));
    }

    private function buscarUsuarioSincronizado(array $usuarios, ?string $userId = null, ?string $registration = null): ?array
    {
        foreach ($usuarios as $usuario) {
            if ($userId !== null && (string) $usuario['user_id'] === (string) $userId) {
                return $usuario;
            }

            if ($registration !== null && (string) $usuario['registration'] === (string) $registration) {
                return $usuario;
            }
        }

        return null;
    }

    private function resolverUserIdNaResposta(array $resposta): ?string
    {
        foreach (['id', 'user_id'] as $key) {
            if (isset($resposta[$key])) {
                return (string) $resposta[$key];
            }
        }

        if (!empty($resposta['ids']) && is_array($resposta['ids'])) {
            return (string) reset($resposta['ids']);
        }

        if (!empty($resposta['values']) && is_array($resposta['values'])) {
            $primeiro = reset($resposta['values']);

            if (is_array($primeiro) && isset($primeiro['id'])) {
                return (string) $primeiro['id'];
            }
        }

        return null;
    }

    private function converterImagemParaBase64(UploadedFile $foto): string
    {
        $conteudo = file_get_contents($foto->getRealPath());

        if ($conteudo === false) {
            throw new InvalidArgumentException('Não foi possível ler a imagem enviada para o dispositivo.');
        }

        return base64_encode($conteudo);
    }
}
