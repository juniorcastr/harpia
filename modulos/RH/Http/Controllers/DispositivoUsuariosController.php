<?php

namespace Modulos\RH\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;
use Modulos\Core\Http\Controller\BaseController;
use Modulos\RH\Http\Requests\DispositivoUsuarioRequest;
use Modulos\RH\Repositories\ColaboradorRepository;
use Modulos\RH\Repositories\DispositivoAcessoRepository;
use Modulos\RH\Services\ExportacaoUsuariosDispositivoService;
use Modulos\RH\Services\SincronizacaoUsuariosDispositivoService;

class DispositivoUsuariosController extends BaseController
{
    public function __construct(
        private DispositivoAcessoRepository $dispositivoRepository,
        private ColaboradorRepository $colaboradorRepository,
        private SincronizacaoUsuariosDispositivoService $sincronizacaoService,
        private ExportacaoUsuariosDispositivoService $exportacaoService
    ) {
    }

    public function getIndex(Request $request): View
    {
        $dispositivos = $this->dispositivoRepository->listarAtivosParaSelecao();
        $colaboradores = $this->colaboradorRepository->listarAtivosParaSelecao();
        $dispositivo = null;
        $usuarios = [];
        $resumo = null;

        if ($request->filled('dis_id')) {
            $dispositivo = $this->dispositivoRepository->buscarAtivo((int) $request->get('dis_id'));

            if (!$dispositivo) {
                flash()->error('Dispositivo ativo não encontrado.');
                return view('RH::dispositivo_usuarios.index', compact('dispositivos', 'colaboradores', 'dispositivo', 'usuarios', 'resumo'));
            }

            try {
                $consulta = $this->sincronizacaoService->consultar($dispositivo);
                $usuarios = $consulta['usuarios'];
                $resumo = $consulta['resumo'];
            } catch (\Throwable $exception) {
                $this->tratarExcecao($exception, 'Não foi possível consultar os usuários do dispositivo informado.');
            }
        }

        return view('RH::dispositivo_usuarios.index', compact('dispositivos', 'colaboradores', 'dispositivo', 'usuarios', 'resumo'));
    }

    public function getEdit($userId, Request $request)
    {
        $this->validate($request, [
            'dis_id' => 'required|integer|exists:reh_dispositivos_acesso,dis_id',
        ]);

        $dispositivo = $this->dispositivoRepository->buscarAtivo((int) $request->get('dis_id'));

        if (!$dispositivo) {
            flash()->error('Dispositivo ativo não encontrado.');
            return redirect()->route('rh.dispositivo-usuarios.index');
        }

        try {
            $usuario = $this->sincronizacaoService->buscarUsuario($dispositivo, (string) $userId);

            if (!$usuario) {
                flash()->error('Usuário do dispositivo não encontrado.');
                return redirect()->route('rh.dispositivo-usuarios.index', ['dis_id' => $dispositivo->dis_id]);
            }
        } catch (\Throwable $exception) {
            $this->tratarExcecao($exception, 'Não foi possível consultar o usuário informado no dispositivo.');
            return redirect()->route('rh.dispositivo-usuarios.index', ['dis_id' => $dispositivo->dis_id]);
        }

        $colaboradores = $this->colaboradorRepository->listarAtivosParaSelecao();

        return view('RH::dispositivo_usuarios.edit', compact('dispositivo', 'usuario', 'colaboradores'));
    }

    public function postCreate(DispositivoUsuarioRequest $request)
    {
        $dispositivo = $this->dispositivoRepository->buscarAtivo((int) $request->get('dis_id'));

        if (!$dispositivo) {
            flash()->error('Dispositivo ativo não encontrado.');
            return redirect()->back()->withInput($request->all());
        }

        try {
            $this->sincronizacaoService->criarUsuario(
                $dispositivo,
                (int) $request->get('col_id'),
                $request->only(['nome', 'registration']),
                $request->file('foto')
            );

            flash()->success('Usuário cadastrado no dispositivo com sucesso.');
            return redirect()->route('rh.dispositivo-usuarios.index', ['dis_id' => $dispositivo->dis_id]);
        } catch (\Throwable $exception) {
            $this->tratarExcecao($exception, 'Não foi possível cadastrar o usuário no dispositivo.');
            return redirect()->back()->withInput($request->all());
        }
    }

    public function putEdit($userId, DispositivoUsuarioRequest $request)
    {
        $dispositivo = $this->dispositivoRepository->buscarAtivo((int) $request->get('dis_id'));

        if (!$dispositivo) {
            flash()->error('Dispositivo ativo não encontrado.');
            return redirect()->back()->withInput($request->all());
        }

        try {
            $this->sincronizacaoService->atualizarUsuario(
                $dispositivo,
                (string) $userId,
                $request->only(['nome', 'registration', 'col_id']),
                $request->file('foto')
            );

            flash()->success('Usuário do dispositivo atualizado com sucesso.');
            return redirect()->route('rh.dispositivo-usuarios.index', ['dis_id' => $dispositivo->dis_id]);
        } catch (\Throwable $exception) {
            $this->tratarExcecao($exception, 'Não foi possível atualizar o usuário do dispositivo.');
            return redirect()->back()->withInput($request->all());
        }
    }

    public function postAtualizarFoto($userId, DispositivoUsuarioRequest $request)
    {
        $dispositivo = $this->dispositivoRepository->buscarAtivo((int) $request->get('dis_id'));

        if (!$dispositivo) {
            flash()->error('Dispositivo ativo não encontrado.');
            return redirect()->back();
        }

        try {
            $this->sincronizacaoService->atualizarUsuario(
                $dispositivo,
                (string) $userId,
                [],
                $request->file('foto')
            );

            flash()->success('Foto facial atualizada com sucesso.');
        } catch (\Throwable $exception) {
            $this->tratarExcecao($exception, 'Não foi possível atualizar a foto facial no dispositivo.');
        }

        return redirect()->route('rh.dispositivo-usuarios.edit', [
            'id' => $userId,
            'dis_id' => $dispositivo->dis_id,
        ]);
    }

    public function postRemoverFoto($userId, Request $request)
    {
        $this->validate($request, [
            'dis_id' => 'required|integer|exists:reh_dispositivos_acesso,dis_id',
        ]);

        $dispositivo = $this->dispositivoRepository->buscarAtivo((int) $request->get('dis_id'));

        if (!$dispositivo) {
            flash()->error('Dispositivo ativo não encontrado.');
            return redirect()->back();
        }

        try {
            $this->sincronizacaoService->removerFotoUsuario($dispositivo, (string) $userId);
            flash()->success('Foto facial removida do dispositivo com sucesso.');
        } catch (\Throwable $exception) {
            $this->tratarExcecao($exception, 'Não foi possível remover a foto facial do dispositivo.');
        }

        return redirect()->route('rh.dispositivo-usuarios.edit', [
            'id' => $userId,
            'dis_id' => $dispositivo->dis_id,
        ]);
    }

    public function postDelete(Request $request)
    {
        $this->validate($request, [
            'dis_id' => 'required|integer|exists:reh_dispositivos_acesso,dis_id',
            'user_id' => 'required|string|max:20',
        ]);

        $dispositivo = $this->dispositivoRepository->buscarAtivo((int) $request->get('dis_id'));

        if (!$dispositivo) {
            flash()->error('Dispositivo ativo não encontrado.');
            return redirect()->back();
        }

        try {
            $this->sincronizacaoService->removerUsuario($dispositivo, (string) $request->get('user_id'));
            flash()->success('Usuário removido do dispositivo com sucesso.');
        } catch (\Throwable $exception) {
            $this->tratarExcecao($exception, 'Não foi possível remover o usuário do dispositivo.');
        }

        return redirect()->route('rh.dispositivo-usuarios.index', ['dis_id' => $dispositivo->dis_id]);
    }

    public function postSincronizar(Request $request)
    {
        $this->validate($request, [
            'dis_id' => 'required|integer|exists:reh_dispositivos_acesso,dis_id',
        ]);

        $dispositivo = $this->dispositivoRepository->buscarAtivo((int) $request->get('dis_id'));

        if (!$dispositivo) {
            flash()->error('Dispositivo ativo não encontrado.');
            return redirect()->back();
        }

        try {
            $resultado = $this->sincronizacaoService->sincronizar($dispositivo);
            flash()->success(sprintf(
                'Sincronização concluída: %d usuários, %d vinculados, %d pendentes.',
                $resultado['resumo']['total'],
                $resultado['resumo']['vinculados'],
                $resultado['resumo']['pendentes']
            ));
        } catch (\Throwable $exception) {
            $this->tratarExcecao($exception, 'Não foi possível sincronizar os usuários do dispositivo.');
        }

        return redirect()->route('rh.dispositivo-usuarios.index', ['dis_id' => $dispositivo->dis_id]);
    }

    public function getExportarCsv($dispositivoId = null)
    {
        $dispositivo = null;

        if ($dispositivoId) {
            $dispositivo = $this->dispositivoRepository->buscarAtivo((int) $dispositivoId);

            if (!$dispositivo) {
                flash()->error('Dispositivo ativo não encontrado.');
                return redirect()->back();
            }
        }

        try {
            $conteudo = $this->exportacaoService->gerarCsv($dispositivo);
            $nomeArquivo = $this->exportacaoService->nomeArquivo($dispositivo);

            return response($conteudo, 200, [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="' . $nomeArquivo . '"',
            ]);
        } catch (\Throwable $exception) {
            $this->tratarExcecao($exception, 'Não foi possível gerar o CSV para o dispositivo informado.');
            return redirect()->back();
        }
    }

    private function tratarExcecao(\Throwable $exception, string $mensagemPadrao): void
    {
        if (config('app.debug')) {
            throw $exception;
        }

        flash()->error($exception instanceof \InvalidArgumentException ? $exception->getMessage() : $mensagemPadrao);
    }
}
