@extends('layouts.modulos.default')

@section('title')
    Teste de Dispositivos
@stop

@section('subtitle')
    Comunicação com iDFace
@stop

@section('content')
    <div class="row">
        <div class="col-md-4">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">Selecionar dispositivo</h3>
                </div>
                <div class="box-body">
                    <div class="form-group">
                        <label for="dispositivo">Dispositivo</label>
                        <select class="form-control" id="dispositivo" name="dis_id">
                            <option value="">Selecione</option>
                            @foreach($dispositivos as $dispositivoId => $dispositivoNome)
                                <option value="{{ $dispositivoId }}">{{ $dispositivoNome }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="alert alert-info" id="status-dispositivo">
                        Selecione um dispositivo para executar os testes.
                    </div>

                    <div class="btn-group-vertical" style="width: 100%;">
                        <button type="button" class="btn btn-primary btn-teste" data-url="{{ route('rh.teste-dispositivo.ping') }}">
                            Ping / Status
                        </button>
                        <button type="button" class="btn btn-default btn-teste" data-url="{{ route('rh.teste-dispositivo.listar-usuarios') }}">
                            Listar usuários
                        </button>
                        <button type="button" class="btn btn-default btn-teste" data-url="{{ route('rh.teste-dispositivo.consultar-logs') }}">
                            Consultar logs
                        </button>
                        <button type="button" class="btn btn-default btn-teste" data-url="{{ route('rh.teste-dispositivo.status') }}">
                            Verificar configuração
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">Resultado</h3>
                </div>
                <div class="box-body">
                    <pre id="resultado-json" style="min-height: 420px; white-space: pre-wrap;">Nenhuma consulta executada.</pre>
                </div>
            </div>
        </div>
    </div>
@stop

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const selectDispositivo = document.getElementById('dispositivo');
            const statusDispositivo = document.getElementById('status-dispositivo');
            const resultadoJson = document.getElementById('resultado-json');
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const buttons = document.querySelectorAll('.btn-teste');

            function notifyError(message) {
                if (window.toastr) {
                    window.toastr.error(message, null, {progressBar: true});
                    return;
                }

                window.alert(message);
            }

            function setStatus(type, message) {
                statusDispositivo.classList.remove('alert-info', 'alert-success', 'alert-danger');
                statusDispositivo.classList.add(type);
                statusDispositivo.textContent = message;
            }

            async function executarTeste(url, dispositivoId) {
                setStatus('alert-info', 'Executando teste...');
                resultadoJson.textContent = 'Carregando...';

                try {
                    const response = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: new URLSearchParams({
                            dis_id: dispositivoId
                        })
                    });

                    const data = await response.json();

                    if (!response.ok) {
                        throw data;
                    }

                    setStatus('alert-success', 'Comunicação realizada com sucesso.');
                    resultadoJson.textContent = JSON.stringify(data, null, 2);
                } catch (error) {
                    const response = error && typeof error === 'object'
                        ? error
                        : {
                            success: false,
                            message: 'Falha inesperada ao consultar o dispositivo.'
                        };

                    setStatus('alert-danger', response.message || 'Falha ao consultar o dispositivo.');
                    resultadoJson.textContent = JSON.stringify(response, null, 2);
                }
            }

            buttons.forEach(function (button) {
                button.addEventListener('click', function () {
                    const dispositivoId = selectDispositivo.value;
                    const url = button.dataset.url;

                    if (!dispositivoId) {
                        notifyError('Selecione um dispositivo antes de executar o teste.');
                        return;
                    }

                    executarTeste(url, dispositivoId);
                });
            });
        });
    </script>
@stop
