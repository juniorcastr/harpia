@extends('layouts.modulos.default')

@section('title')
    Detalhes do Registro de Ponto
@stop

@section('subtitle')
    Visão Unificada de Entradas e Saídas
@stop

@section('content')
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">Detalhamento do dia</h3>
        </div>
        <div class="box-body">
            <p><strong>Colaborador:</strong> {{ $colaborador->pessoa->pes_nome }}</p>
            <p><strong>Matrícula:</strong> {{ $colaborador->col_id }}</p>
            <p><strong>Data:</strong> {{ \Carbon\Carbon::parse($data)->format('d/m/Y') }}</p>
            <p><strong>Total do dia:</strong> {{ $totalHoras }}</p>
        </div>
    </div>

    <div class="box box-primary">
        <div class="box-body table-responsive">
            @if($eventos->count())
                <table class="table table-bordered table-striped">
                    <thead>
                    <tr>
                        <th>Tipo</th>
                        <th>Data/Hora</th>
                        <th>Origem</th>
                        <th>Status</th>
                        <th>Dispositivo</th>
                        <th>Observação</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($eventos as $evento)
                        <tr>
                            <td>{{ strtoupper(substr($evento->eva_tipo, 0, 4)) }}</td>
                            <td>{{ \Carbon\Carbon::parse($evento->eva_data_hora)->format('d/m/Y H:i:s') }}</td>
                            <td>{{ $evento->eva_origem }}</td>
                            <td>{{ ucfirst($evento->eva_status) }}</td>
                            <td>{{ $evento->dispositivo?->dis_nome ?: '-' }}</td>
                            <td>{{ $evento->eva_status_mensagem ?: '-' }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @else
                <div class="alert alert-info" style="margin-bottom: 0;">
                    Nenhum evento encontrado para este colaborador nesta data.
                </div>
            @endif
        </div>
        <div class="box-footer">
            <a href="{{ route('rh.registros-ponto.index') }}" class="btn btn-default">Voltar</a>
        </div>
    </div>
@stop
