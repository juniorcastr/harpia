@extends('layouts.site')

@section('content')
    <div class="login-box" style="padding-top:10vh">
        <div class="box box-widget widget-user" style="margin-bottom:5px">
            <div class="widget-user-header text-center" style="background-color:#E9F1F5;border-bottom:2px solid #0083CE">
                <img src="{{url('/')}}/img/logo.png" style="height:70px" />
                <h4 class="text-center" style="margin-top:2px">Sistema de Gestão <b>Educacional</b></h4>
            </div>
            <div class="box-content">
                <div class="login-box-body">
                    @if (isset($sent))
                        <div class="alert alert-success">
                            Nós lhe enviamos por email um link de redefinição de senha!
                        </div>
                    @endif

                    <p class="login-box-msg"> <b>Redefinição de Senha</b></p>
                    <form action="{{url('/reset-password')}}" method="post">
                        <input type="hidden" name="token" value="{{ request()->token }}">
                        <input type="hidden" name="_token" value="{{ csrf_token() }}">
                        <div class="form-group has-feedback @if ($errors->has('email')) has-error @endif">
                            {!! Form::text('email', old('email'), array('placeholder' => 'Confirme seu email', 'class'=>'form-control')) !!}
                            <span class="glyphicon glyphicon-envelope form-control-feedback"></span>
                            @if ($errors->has('email')) <p class="help-block">{{ $errors->first('email') }}</p> @endif
                        </div>
                        <div class="form-group has-feedback @if ($errors->has('password')) has-error @endif">
                            {!! Form::password('password', array('placeholder' => 'Nova Senha', 'class'=>'form-control')) !!}
                            <span class="glyphicon glyphicon-lock form-control-feedback"></span>
                            @if ($errors->has('password')) <p class="help-block">{{ $errors->first('password') }}</p> @endif
                        </div>
                        <div class="form-group has-feedback @if ($errors->has('password_confirmation')) has-error @endif">
                            {!! Form::password('password_confirmation', array('placeholder' => 'Confirme sua nova senha', 'class'=>'form-control')) !!}
                            <span class="glyphicon glyphicon-lock form-control-feedback"></span>
                            @if ($errors->has('password_confirmation')) <p class="help-block">{{ $errors->first('password_confirmation') }}</p> @endif
                        </div>
                        <div class="row">
                            <div class="col-xs-4">
                                <button type="submit" class="btn btn-primary btn-block btn-flat mt-lg">Enviar</button>
                            </div><!-- /.col -->
                        </div>
                    </form>
                </div>
                <div class="box-footer" style="padding-top:5px">
                    <a class="text-right col-md-12" href="{{url('/login')}}">Login</a>
                </div>
            </div>
        </div>
        <footer class="main-footer" style="margin-left:0px;padding:5px;text-align:center;">
            <strong style="font-size:12px">Copyright © 2016-{{ date('Y') }} <a href="http://www.uemanet.uema.br">UemaNet</a>.</strong> All rights
            reserved.
        </footer>
    </div>
@stop