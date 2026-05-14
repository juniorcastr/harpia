@extends('layouts.modulos.default')

@section('title')
    Ponto Remoto
@stop

@section('subtitle')
    Registro pelo Colaborador
@stop

@section('content')
    <div class="row">
        <div class="col-md-5">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-laptop"></i> Registrar ponto remoto</h3>
                </div>
                <div class="box-body">
                    <p><strong>Colaborador:</strong> {{ $colaborador->pessoa->pes_nome }}</p>
                    <p><strong>Matrícula:</strong> {{ $colaborador->col_id }}</p>
                    <p>
                        <strong>Situação atual:</strong>
                        <span class="label {{ $estadoAtual['tem_entrada_aberta'] ? 'label-warning' : 'label-success' }}">
                            {{ $estadoAtual['tem_entrada_aberta'] ? 'Entrada em aberto' : 'Aguardando nova entrada' }}
                        </span>
                    </p>

                    @if($estadoAtual['ultimo_evento'])
                        <p>
                            <strong>Último evento do dia:</strong>
                            {{ ucfirst($estadoAtual['ultimo_evento']->eva_tipo) }}
                            em {{ \Carbon\Carbon::parse($estadoAtual['ultimo_evento']->eva_data_hora)->format('d/m/Y H:i:s') }}
                            ({{ $estadoAtual['ultimo_evento']->eva_origem }})
                        </p>
                    @endif

                    <form method="POST">
                        @csrf
                        <div class="form-group @if ($errors->has('observacao')) has-error @endif">
                            <label for="observacao">Observação</label>
                            <textarea name="observacao" id="observacao" class="form-control" rows="4" placeholder="Opcional. Informe contexto para o gestor aprovar o registro.">{{ old('observacao') }}</textarea>
                            @if ($errors->has('observacao')) <p class="help-block">{{ $errors->first('observacao') }}</p> @endif
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <button type="submit"
                                        formaction="{{ route('rh.ponto-remoto.entrada') }}"
                                        class="btn btn-success btn-block"
                                        {{ $estadoAtual['pode_entrada'] ? '' : 'disabled' }}>
                                    <i class="fa fa-sign-in"></i> Registrar Entrada
                                </button>
                            </div>
                            <div class="col-md-6">
                                <button type="submit"
                                        formaction="{{ route('rh.ponto-remoto.saida') }}"
                                        class="btn btn-primary btn-block"
                                        {{ $estadoAtual['pode_saida'] ? '' : 'disabled' }}>
                                    <i class="fa fa-sign-out"></i> Registrar Saída
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-7">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-list"></i> Meus registros recentes</h3>
                </div>
                <div class="box-body table-responsive">
                    @if($registros->count())
                        <table class="table table-bordered table-striped">
                            <thead>
                            <tr>
                                <th>Data/Hora</th>
                                <th>Tipo</th>
                                <th>Origem</th>
                                <th>Status</th>
                                <th>Observação</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($registros as $registro)
                                @php($observacao = json_decode((string) $registro->eva_observacao, true))
                                <tr>
                                    <td>{{ \Carbon\Carbon::parse($registro->eva_data_hora)->format('d/m/Y H:i:s') }}</td>
                                    <td>{{ ucfirst($registro->eva_tipo) }}</td>
                                    <td>{{ $registro->eva_origem }}</td>
                                    <td>
                                        <span class="label
                                            {{ in_array($registro->eva_status, ['processado', 'aprovado']) ? 'label-success' : '' }}
                                            {{ in_array($registro->eva_status, ['pendente', 'duplicado']) ? 'label-warning' : '' }}
                                            {{ in_array($registro->eva_status, ['erro', 'reprovado']) ? 'label-danger' : '' }}">
                                            {{ ucfirst($registro->eva_status) }}
                                        </span>
                                    </td>
                                    <td>{{ $observacao['observacao_usuario'] ?? ($registro->eva_status_mensagem ?: '-') }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    @else
                        <div class="alert alert-info" style="margin-bottom: 0;">
                            Nenhum registro encontrado para o colaborador.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@stop
