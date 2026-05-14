@extends('layouts.modulos.default')

@section('stylesheets')
    <link rel="stylesheet" href="{{asset('/css/plugins/select2.css')}}">
    <link rel="stylesheet" href="{{asset('/css/plugins/datepicker3.css')}}">
@endsection

@section('title')
    Eventos de Acesso
@stop

@section('subtitle')
    Auditoria de Eventos Brutos
@stop

@section('content')
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title"><i class="fa fa-filter"></i> Filtrar dados</h3>
        </div>
        <div class="box-body">
            <form method="GET" action="{{ route('rh.eventos-acesso.index') }}">
                <div class="row">
                    <div class="form-group col-md-2">
                        <label>Data inicial</label>
                        <input type="text" name="data_inicio" class="form-control datepicker" value="{{ request('data_inicio', date('Y-m-01')) }}">
                    </div>
                    <div class="form-group col-md-2">
                        <label>Data final</label>
                        <input type="text" name="data_fim" class="form-control datepicker" value="{{ request('data_fim', date('Y-m-t')) }}">
                    </div>
                    <div class="form-group col-md-3">
                        <label>Colaborador</label>
                        <select name="col_id" class="form-control">
                            <option value="">Todos</option>
                            @foreach($colaboradores as $colaboradorId => $colaboradorNome)
                                <option value="{{ $colaboradorId }}" {{ (string) request('col_id') === (string) $colaboradorId ? 'selected' : '' }}>
                                    {{ $colaboradorNome }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-2">
                        <label>Dispositivo</label>
                        <select name="dis_id" class="form-control">
                            <option value="">Todos</option>
                            @foreach($dispositivos as $dispositivoId => $dispositivoNome)
                                <option value="{{ $dispositivoId }}" {{ (string) request('dis_id') === (string) $dispositivoId ? 'selected' : '' }}>
                                    {{ $dispositivoNome }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-1">
                        <label>Tipo</label>
                        <select name="tipo" class="form-control">
                            <option value="">Todos</option>
                            @foreach($tipos as $tipo)
                                <option value="{{ $tipo }}" {{ request('tipo') === $tipo ? 'selected' : '' }}>{{ ucfirst($tipo) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-2">
                        <label>Status</label>
                        <select name="status" class="form-control">
                            <option value="">Todos</option>
                            @foreach($statusList as $status)
                                <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>{{ ucfirst($status) }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="form-group col-md-2">
                        <label>Origem</label>
                        <select name="origem" class="form-control">
                            <option value="">Todas</option>
                            @foreach($origens as $origem)
                                <option value="{{ $origem }}" {{ request('origem') === $origem ? 'selected' : '' }}>{{ $origem }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-8">
                        <label>Hash</label>
                        <input type="text" name="hash" class="form-control" value="{{ request('hash') }}" placeholder="Buscar por hash do evento">
                    </div>
                    <div class="form-group col-md-2">
                        <label>&nbsp;</label>
                        <button type="submit" class="btn btn-primary btn-block">Buscar</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="box box-primary">
        <div class="box-body table-responsive">
            @if($eventos->count())
                <table class="table table-bordered table-striped">
                    <thead>
                    <tr>
                        <th>Data/Hora</th>
                        <th>Colaborador</th>
                        <th>Dispositivo</th>
                        <th>Tipo</th>
                        <th>Origem</th>
                        <th>Status</th>
                        <th>Hash</th>
                        <th style="width: 110px;">Ações</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($eventos as $evento)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($evento->eva_data_hora)->format('d/m/Y H:i:s') }}</td>
                            <td>{{ $evento->pes_nome ?: '-' }}</td>
                            <td>{{ $evento->dis_nome ?: '-' }}</td>
                            <td>{{ strtoupper(substr($evento->eva_tipo, 0, 4)) }}</td>
                            <td>{{ $evento->eva_origem }}</td>
                            <td>
                                <span class="label
                                    {{ $evento->eva_status === 'processado' || $evento->eva_status === 'aprovado' ? 'label-success' : '' }}
                                    {{ $evento->eva_status === 'pendente' || $evento->eva_status === 'duplicado' ? 'label-warning' : '' }}
                                    {{ $evento->eva_status === 'erro' || $evento->eva_status === 'reprovado' ? 'label-danger' : '' }}
                                    {{ $evento->eva_status === 'bruto' ? 'label-default' : '' }}">
                                    {{ ucfirst($evento->eva_status) }}
                                </span>
                            </td>
                            <td><small>{{ $evento->eva_hash }}</small></td>
                            <td>
                                <a href="{{ route('rh.eventos-acesso.show', ['id' => $evento->eva_id]) }}" class="btn btn-xs btn-primary">
                                    <i class="fa fa-search"></i> Ver
                                </a>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>

                <div class="text-center">{{ $eventos->appends(request()->except('page'))->links('pagination::bootstrap-4') }}</div>
            @else
                <div class="alert alert-info" style="margin-bottom: 0;">
                    Nenhum evento encontrado para os filtros informados.
                </div>
            @endif
        </div>
    </div>
@stop

@section('scripts')
    <script src="{{asset('/js/plugins/select2.js')}}" type="text/javascript"></script>
    <script src="{{asset('/js/plugins/bootstrap-datepicker.js')}}" type="text/javascript"></script>
    <script src="{{asset('/js/plugins/bootstrap-datepicker.pt-BR.js')}}" type="text/javascript"></script>
    <script>
        $(document).ready(function () {
            $('select').select2();
            $('.datepicker').datepicker({
                format: 'yyyy-mm-dd',
                language: 'pt-BR'
            });
        });
    </script>
@endsection
