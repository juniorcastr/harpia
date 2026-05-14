@extends('layouts.modulos.default')

@section('title')
    Detalhe do Evento de Acesso
@stop

@section('subtitle')
    Auditoria de Eventos Brutos
@stop

@section('content')
    <div class="row">
        <div class="col-md-8">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">Dados do evento</h3>
                </div>
                <div class="box-body">
                    <p><strong>Data/Hora:</strong> {{ \Carbon\Carbon::parse($evento->eva_data_hora)->format('d/m/Y H:i:s') }}</p>
                    <p><strong>Colaborador:</strong> {{ $evento->colaborador?->pessoa?->pes_nome ?: '-' }}</p>
                    <p><strong>Matrícula:</strong> {{ $evento->eva_matricula }}</p>
                    <p><strong>Dispositivo:</strong> {{ $evento->dispositivo?->dis_nome ?: '-' }}</p>
                    <p><strong>Tipo:</strong> {{ ucfirst($evento->eva_tipo) }}</p>
                    <p><strong>Origem:</strong> {{ $evento->eva_origem }}</p>
                    <p><strong>Status:</strong> {{ ucfirst($evento->eva_status) }}</p>
                    <p><strong>Mensagem de status:</strong> {{ $evento->eva_status_mensagem ?: '-' }}</p>
                    <p><strong>Hash:</strong> <small>{{ $evento->eva_hash }}</small></p>
                    <p><strong>IP de origem:</strong> {{ $evento->eva_ip_origem ?: '-' }}</p>
                    <p><strong>User-Agent:</strong> {{ $evento->eva_user_agent ?: '-' }}</p>
                </div>
            </div>

            <div class="box box-default">
                <div class="box-header with-border">
                    <h3 class="box-title">Observação bruta</h3>
                </div>
                <div class="box-body">
                    <pre style="white-space: pre-wrap;">{{ $evento->eva_observacao ?: 'Sem observação.' }}</pre>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="box box-default">
                <div class="box-header with-border">
                    <h3 class="box-title">Ações</h3>
                </div>
                <div class="box-body">
                    <a href="{{ route('rh.eventos-acesso.index') }}" class="btn btn-default btn-block">
                        <i class="fa fa-arrow-left"></i> Voltar para a listagem
                    </a>

                    @if($evento->eva_status === 'erro')
                        <form method="POST" action="{{ route('rh.eventos-acesso.reprocessar', ['id' => $evento->eva_id]) }}" style="margin-top: 10px;">
                            {{ csrf_field() }}
                            <button type="submit" class="btn btn-warning btn-block">
                                <i class="fa fa-refresh"></i> Reprocessar evento
                            </button>
                        </form>
                    @endif
                </div>
            </div>

            @if($evento->aprovacoes && $evento->aprovacoes->count())
                <div class="box box-default">
                    <div class="box-header with-border">
                        <h3 class="box-title">Aprovações vinculadas</h3>
                    </div>
                    <div class="box-body">
                        @foreach($evento->aprovacoes as $aprovacao)
                            <p>
                                <strong>Status:</strong> {{ ucfirst($aprovacao->app_status) }}<br>
                                <strong>Data:</strong> {{ \Carbon\Carbon::parse($aprovacao->app_data_aprovacao)->format('d/m/Y H:i:s') }}
                            </p>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
@stop
