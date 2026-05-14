@extends('layouts.modulos.default')

@section('title')
    Configurações do Ponto
@stop

@section('subtitle')
    Parâmetros do Módulo
@stop

@section('content')
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title"><i class="fa fa-cogs"></i> Configurações operacionais</h3>
        </div>
        <form method="POST" action="{{ route('rh.configuracoes-ponto.edit') }}">
            @csrf
            {{ method_field('PUT') }}

            <div class="box-body">
                <div class="row">
                    <div class="form-group col-md-3 @if ($errors->has('janela_inicio')) has-error @endif">
                        <label for="janela_inicio">Janela inicial</label>
                        <input type="text" name="janela_inicio" id="janela_inicio" class="form-control" value="{{ old('janela_inicio', $configuracoes['ponto.janela_inicio'] ?? '05:00') }}">
                        @if ($errors->has('janela_inicio')) <p class="help-block">{{ $errors->first('janela_inicio') }}</p> @endif
                    </div>
                    <div class="form-group col-md-3 @if ($errors->has('janela_fim')) has-error @endif">
                        <label for="janela_fim">Janela final</label>
                        <input type="text" name="janela_fim" id="janela_fim" class="form-control" value="{{ old('janela_fim', $configuracoes['ponto.janela_fim'] ?? '23:00') }}">
                        @if ($errors->has('janela_fim')) <p class="help-block">{{ $errors->first('janela_fim') }}</p> @endif
                    </div>
                    <div class="form-group col-md-3 @if ($errors->has('anti_duplicidade_seg')) has-error @endif">
                        <label for="anti_duplicidade_seg">Anti-duplicidade (segundos)</label>
                        <input type="number" min="1" name="anti_duplicidade_seg" id="anti_duplicidade_seg" class="form-control" value="{{ old('anti_duplicidade_seg', $configuracoes['ponto.anti_duplicidade_seg'] ?? 60) }}">
                        @if ($errors->has('anti_duplicidade_seg')) <p class="help-block">{{ $errors->first('anti_duplicidade_seg') }}</p> @endif
                    </div>
                    <div class="form-group col-md-3 @if ($errors->has('retry_max_tentativas')) has-error @endif">
                        <label for="retry_max_tentativas">Máx. tentativas de retry</label>
                        <input type="number" min="0" name="retry_max_tentativas" id="retry_max_tentativas" class="form-control" value="{{ old('retry_max_tentativas', $configuracoes['ponto.retry_max_tentativas'] ?? 3) }}">
                        @if ($errors->has('retry_max_tentativas')) <p class="help-block">{{ $errors->first('retry_max_tentativas') }}</p> @endif
                    </div>
                </div>

                <div class="row">
                    <div class="form-group col-md-3 @if ($errors->has('retry_delay_min')) has-error @endif">
                        <label for="retry_delay_min">Delay de retry (minutos)</label>
                        <input type="number" min="0" name="retry_delay_min" id="retry_delay_min" class="form-control" value="{{ old('retry_delay_min', $configuracoes['ponto.retry_delay_min'] ?? 5) }}">
                        @if ($errors->has('retry_delay_min')) <p class="help-block">{{ $errors->first('retry_delay_min') }}</p> @endif
                    </div>
                </div>
            </div>

            <div class="box-footer">
                <button type="submit" class="btn btn-primary">
                    <i class="fa fa-save"></i> Salvar configurações
                </button>
            </div>
        </form>
    </div>
@stop
