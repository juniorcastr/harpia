@extends('layouts.modulos.default')

@section('stylesheets')
    <link rel="stylesheet" href="{{asset('/css/plugins/select2.css')}}">
    <link rel="stylesheet" href="{{asset('/css/plugins/datepicker3.css')}}">
@endsection

@section('title')
    Registros de Ponto
@stop

@section('subtitle')
    Visão Unificada de Entradas e Saídas
@stop

@section('content')
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title"><i class="fa fa-filter"></i> Filtrar dados</h3>
        </div>
        <div class="box-body">
            <form method="GET" action="{{ route('rh.registros-ponto.index') }}">
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
                        <label>Setores</label>
                        <select name="setores[]" class="form-control" multiple>
                            @foreach($setores as $setorId => $setorNome)
                                <option value="{{ $setorId }}" {{ in_array((string) $setorId, array_map('strval', (array) request('setores', [])), true) ? 'selected' : '' }}>
                                    {{ $setorNome }}
                                </option>
                            @endforeach
                        </select>
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
                        <label>Origem</label>
                        <select name="origem" class="form-control">
                            <option value="">Todas</option>
                            @foreach($origens as $origem)
                                <option value="{{ $origem }}" {{ request('origem') === $origem ? 'selected' : '' }}>{{ $origem }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="form-group col-md-2">
                        <label>Status</label>
                        <select name="status" class="form-control">
                            <option value="">Todos</option>
                            @foreach($statusList as $status)
                                <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>{{ ucfirst($status) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-2">
                        <label>&nbsp;</label>
                        <button type="submit" class="btn btn-primary btn-block">Buscar</button>
                    </div>
                    <div class="form-group col-md-2">
                        <label>&nbsp;</label>
                        <a href="{{ route('rh.registros-ponto.export', request()->all()) }}" class="btn btn-success btn-block">
                            <i class="fa fa-file-excel-o"></i> Exportar XLSX
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="box box-primary">
        <div class="box-body table-responsive">
            @if($registros->count())
                <table class="table table-bordered table-striped">
                    <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Matrícula</th>
                        <th>Setor</th>
                        <th>Data</th>
                        <th>Entrada</th>
                        <th>Saída</th>
                        <th>Total Horas</th>
                        <th>Origem</th>
                        <th>Status</th>
                        <th style="width: 100px;">Ações</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($registros as $registro)
                        <tr>
                            <td>{{ $registro->pes_nome }}</td>
                            <td>{{ $registro->col_id }}</td>
                            <td>{{ $registro->set_descricao ?: '-' }}</td>
                            <td>{{ \Carbon\Carbon::parse($registro->rpo_data)->format('d/m/Y') }}</td>
                            <td>{{ $registro->entrada_formatada }}</td>
                            <td>{{ $registro->saida_formatada }}</td>
                            <td>{{ $registro->total_horas }}</td>
                            <td>
                                <span class="label {{ $registro->origem_label === 'home_office' ? 'label-success' : ($registro->origem_label === 'misto' ? 'label-warning' : 'label-info') }}">
                                    {{ $registro->origem_label }}
                                </span>
                            </td>
                            <td>
                                <span class="label
                                    {{ in_array($registro->status_label, ['processado', 'aprovado']) ? 'label-success' : '' }}
                                    {{ in_array($registro->status_label, ['pendente', 'duplicado']) ? 'label-warning' : '' }}
                                    {{ in_array($registro->status_label, ['erro', 'reprovado']) ? 'label-danger' : '' }}
                                    {{ $registro->status_label === '-' ? 'label-default' : '' }}">
                                    {{ ucfirst($registro->status_label) }}
                                </span>
                            </td>
                            <td>
                                <a href="{{ route('rh.registros-ponto.detalhes', ['col_id' => $registro->col_id, 'data' => $registro->rpo_data]) }}" class="btn btn-xs btn-primary">
                                    <i class="fa fa-search"></i> Detalhes
                                </a>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>

                <div class="text-center">{{ $registros->appends(request()->except('page'))->links('pagination::bootstrap-4') }}</div>
            @else
                <div class="alert alert-info" style="margin-bottom: 0;">
                    Nenhum registro encontrado para os filtros informados.
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
            $('select').select2({
                closeOnSelect: false
            });
            $('.datepicker').datepicker({
                format: 'yyyy-mm-dd',
                language: 'pt-BR'
            });
        });
    </script>
@endsection
