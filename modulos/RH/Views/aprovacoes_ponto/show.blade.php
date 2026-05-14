@extends('layouts.modulos.default')

@section('title')
    Aprovação de Registro Remoto
@stop

@section('subtitle')
    Detalhamento da Pendência
@stop

@section('content')
    @php($observacao = json_decode((string) $evento->eva_observacao, true))

    <div class="row">
        <div class="col-md-6">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-info-circle"></i> Dados do registro</h3>
                </div>
                <div class="box-body">
                    <p><strong>Colaborador:</strong> {{ $evento->colaborador->pessoa->pes_nome ?? '-' }}</p>
                    <p><strong>Matrícula:</strong> {{ $evento->eva_matricula }}</p>
                    <p><strong>Tipo:</strong> {{ ucfirst($evento->eva_tipo) }}</p>
                    <p><strong>Data/Hora:</strong> {{ \Carbon\Carbon::parse($evento->eva_data_hora)->format('d/m/Y H:i:s') }}</p>
                    <p><strong>Status:</strong> <span class="label label-warning">{{ ucfirst($evento->eva_status) }}</span></p>
                    <p><strong>IP de origem:</strong> {{ $evento->eva_ip_origem ?: '-' }}</p>
                    <p><strong>User-Agent:</strong> {{ $evento->eva_user_agent ?: '-' }}</p>
                    <p><strong>Observação do colaborador:</strong> {{ $observacao['observacao_usuario'] ?? '-' }}</p>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="box box-success">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-thumbs-up"></i> Aprovar</h3>
                </div>
                <div class="box-body">
                    <form method="POST" action="{{ route('rh.aprovacoes-ponto.aprovar', ['id' => $evento->eva_id]) }}">
                        @csrf
                        <button type="submit" class="btn btn-success btn-block">
                            Confirmar aprovação
                        </button>
                    </form>
                </div>
            </div>

            <div class="box box-danger">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-thumbs-down"></i> Reprovar</h3>
                </div>
                <div class="box-body">
                    <form method="POST" action="{{ route('rh.aprovacoes-ponto.reprovar', ['id' => $evento->eva_id]) }}">
                        @csrf
                        <div class="form-group @if ($errors->has('motivo')) has-error @endif">
                            <label for="motivo">Motivo da reprovação</label>
                            <textarea name="motivo" id="motivo" class="form-control" rows="4" placeholder="Obrigatório para reprovação.">{{ old('motivo') }}</textarea>
                            @if ($errors->has('motivo')) <p class="help-block">{{ $errors->first('motivo') }}</p> @endif
                        </div>

                        <button type="submit" class="btn btn-danger btn-block">
                            Confirmar reprovação
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@stop
