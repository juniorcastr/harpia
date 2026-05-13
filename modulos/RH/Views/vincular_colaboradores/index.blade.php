@extends('layouts.modulos.default')

@section('stylesheets')
    <link rel="stylesheet" href="{{asset('/css/plugins/select2.css')}}">
@endsection

@section('title')
    Vincular Colaboradores
@stop

@section('subtitle')
    Mapeamento Harpia x Control iD
@stop

@section('content')
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title"><i class="fa fa-filter"></i> Filtros</h3>
        </div>
        <div class="box-body">
            <form method="GET" action="{{ route('rh.vincular-colaboradores.index') }}">
                <div class="row">
                    <div class="col-md-5">
                        <select name="dis_id" class="form-control">
                            <option value="">Selecione um dispositivo</option>
                            @foreach($dispositivos as $dispositivoId => $dispositivoNome)
                                <option value="{{ $dispositivoId }}" {{ (string) request('dis_id') === (string) $dispositivoId ? 'selected' : '' }}>
                                    {{ $dispositivoNome }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="status" class="form-control">
                            <option value="">Todos</option>
                            <option value="vinculado" {{ request('status') === 'vinculado' ? 'selected' : '' }}>Vinculados</option>
                            <option value="pendente" {{ request('status') === 'pendente' ? 'selected' : '' }}>Pendentes</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <input type="text" name="busca" class="form-control" value="{{ request('busca') }}" placeholder="Buscar por nome, ID ou registration">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary btn-block">Filtrar</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @if($dispositivo)
        <div class="row">
            <div class="col-md-4">
                <div class="small-box bg-aqua">
                    <div class="inner">
                        <h3>{{ $resumo['total'] ?? 0 }}</h3>
                        <p>Usuários no dispositivo</p>
                    </div>
                    <div class="icon"><i class="fa fa-users"></i></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="small-box bg-green">
                    <div class="inner">
                        <h3>{{ $resumo['vinculados'] ?? 0 }}</h3>
                        <p>Vinculados</p>
                    </div>
                    <div class="icon"><i class="fa fa-link"></i></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="small-box bg-yellow">
                    <div class="inner">
                        <h3>{{ $resumo['pendentes'] ?? 0 }}</h3>
                        <p>Pendentes</p>
                    </div>
                    <div class="icon"><i class="fa fa-warning"></i></div>
                </div>
            </div>
        </div>

        <div class="box box-default">
            <div class="box-body">
                <form method="POST" action="{{ route('rh.vincular-colaboradores.sincronizar') }}" style="display: inline-block; margin-right: 10px;">
                    {{ csrf_field() }}
                    <input type="hidden" name="dis_id" value="{{ $dispositivo->dis_id }}">
                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-refresh"></i> Sincronizar do dispositivo
                    </button>
                </form>

                <a href="{{ route('rh.vincular-colaboradores.exportar-csv', ['dis_id' => $dispositivo->dis_id]) }}" class="btn btn-default">
                    <i class="fa fa-download"></i> Exportar CSV
                </a>

                <a href="{{ route('rh.dispositivo-usuarios.index', ['dis_id' => $dispositivo->dis_id]) }}" class="btn btn-warning">
                    <i class="fa fa-cogs"></i> Abrir gestão de usuários
                </a>
            </div>
        </div>

        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">Usuários do dispositivo</h3>
            </div>
            <div class="box-body table-responsive">
                @if(count($usuarios))
                    <table class="table table-bordered table-striped">
                        <thead>
                        <tr>
                            <th>ID iDFace</th>
                            <th>Registration</th>
                            <th>Nome no dispositivo</th>
                            <th>Harpia</th>
                            <th style="width: 280px;">Ação</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($usuarios as $usuario)
                            <tr>
                                <td>{{ $usuario['user_id'] }}</td>
                                <td>{{ $usuario['registration'] ?: '-' }}</td>
                                <td>{{ $usuario['nome'] ?: '-' }}</td>
                                <td>
                                    @if($usuario['col_id'])
                                        <span class="label label-success">Vinculado</span>
                                        <div>{{ $usuario['colaborador_nome'] }}</div>
                                    @else
                                        <span class="label label-warning">Não vinculado</span>
                                    @endif
                                </td>
                                <td>
                                    @if($usuario['col_id'])
                                        <form method="POST" action="{{ route('rh.vincular-colaboradores.desvincular') }}">
                                            {{ csrf_field() }}
                                            <input type="hidden" name="dis_id" value="{{ $dispositivo->dis_id }}">
                                            <input type="hidden" name="user_id" value="{{ $usuario['user_id'] }}">
                                            <input type="hidden" name="status" value="{{ request('status') }}">
                                            <input type="hidden" name="busca" value="{{ request('busca') }}">
                                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Tem certeza que deseja desvincular este usuário?')">
                                                <i class="fa fa-unlink"></i> Desvincular
                                            </button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('rh.vincular-colaboradores.vincular') }}">
                                            {{ csrf_field() }}
                                            <input type="hidden" name="dis_id" value="{{ $dispositivo->dis_id }}">
                                            <input type="hidden" name="user_id" value="{{ $usuario['user_id'] }}">
                                            <input type="hidden" name="status" value="{{ request('status') }}">
                                            <input type="hidden" name="busca" value="{{ request('busca') }}">

                                            <div class="row">
                                                <div class="col-md-8">
                                                    <select name="col_id" class="form-control">
                                                        <option value="">Selecione um colaborador</option>
                                                        @foreach($colaboradores as $colaboradorId => $colaboradorNome)
                                                            <option value="{{ $colaboradorId }}">{{ $colaboradorNome }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="col-md-4">
                                                    <button type="submit" class="btn btn-success btn-sm btn-block">
                                                        <i class="fa fa-link"></i> Vincular
                                                    </button>
                                                </div>
                                            </div>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="alert alert-info" style="margin-bottom: 0;">
                        Nenhum usuário encontrado para os filtros informados.
                    </div>
                @endif
            </div>
        </div>
    @endif
@stop

@section('scripts')
    <script src="{{asset('/js/plugins/select2.js')}}" type="text/javascript"></script>
    <script type="text/javascript">
        $(document).ready(function () {
            $('select').select2();
        });
    </script>
@endsection
