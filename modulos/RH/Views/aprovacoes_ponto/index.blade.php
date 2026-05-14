@extends('layouts.modulos.default')

@section('title')
    Aprovações de Ponto
@stop

@section('subtitle')
    Pendências do Gestor
@stop

@section('content')
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title"><i class="fa fa-check-square-o"></i> Registros pendentes</h3>
        </div>
        <div class="box-body">
            <p><strong>Gestor:</strong> {{ $aprovador->pessoa->pes_nome }}</p>
            <p><strong>Total de pendências:</strong> {{ $pendencias->count() }}</p>
        </div>
        <div class="box-body table-responsive">
            @if($pendencias->count())
                <table class="table table-bordered table-striped">
                    <thead>
                    <tr>
                        <th>Data/Hora</th>
                        <th>Colaborador</th>
                        <th>Tipo</th>
                        <th>Status</th>
                        <th>Observação</th>
                        <th style="width: 90px;">Ações</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($pendencias as $pendencia)
                        @php($observacao = json_decode((string) $pendencia->eva_observacao, true))
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($pendencia->eva_data_hora)->format('d/m/Y H:i:s') }}</td>
                            <td>{{ $pendencia->colaborador->pessoa->pes_nome ?? '-' }}</td>
                            <td>{{ ucfirst($pendencia->eva_tipo) }}</td>
                            <td><span class="label label-warning">{{ ucfirst($pendencia->eva_status) }}</span></td>
                            <td>{{ $observacao['observacao_usuario'] ?? '-' }}</td>
                            <td>
                                <a href="{{ route('rh.aprovacoes-ponto.show', ['id' => $pendencia->eva_id]) }}" class="btn btn-xs btn-primary">
                                    <i class="fa fa-search"></i> Ver
                                </a>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @else
                <div class="alert alert-info" style="margin-bottom: 0;">
                    Não há registros pendentes para aprovação neste momento.
                </div>
            @endif
        </div>
    </div>
@stop
