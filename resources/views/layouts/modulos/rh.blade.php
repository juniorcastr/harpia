<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">

    <title>Módulo Admin - @yield('title')</title>

    <link rel="stylesheet" href="{{ asset('/css/plugins/sweetalert.css') }}" />
    <link rel="stylesheet" href="{{ asset('/css/plugins/toastr.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('/css/app.css') }}" />
    

    <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
    <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
    <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->

    @section('stylesheets')
    @show
</head>

<body class="hold-transition skin-blue-light sidebar-mini">

<div class="wrapper">

    <header class="main-header">
        @include('layouts.includes.logo')

        @include('layouts.includes.header_rightmenu')
    </header>

    <!-- Left side column. contains the main navigation menu-->
    @include('layouts.includes.left')

    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <section class="content-header" >
            <h1>
                @yield('title')
                <small>@yield('details')</small>
            </h1>

            <div class="actionbutton">
                @yield('actionButton')
            </div>

            <div style="display: flex;">
                @if (Breadcrumbs::exists() && !view()->hasSection('breadcrumbs'))
                    <ol class="breadcrumb" style="float: left; background-color: #ecf0f5">
                        {{-- Renderiza os breadcrumbs padrão --}}
                        {{ Breadcrumbs::render() }}
                    </ol>
                @elseif (view()->hasSection('breadcrumbs'))
                    <ol class="breadcrumb" style="float: left; background-color: #ecf0f5">
                        {{-- Renderiza os breadcrumbs personalizados definidos na view --}}
                        @yield('breadcrumbs')
                    </ol>
                @endif
            </div>
        </section>

        <!-- Main content -->
        <section class="content; margin">
            @yield('content')
        </section>
    </div><!-- /.content-wrapper -->

    <!-- Footer bar. -->
    @include('layouts.includes.footer')

</div><!-- ./wrapper -->

<!-- JQUERY-->
<script src="{{ asset('/js/jquery-2.2.3.min.js')}}"></script>
<script src="{{ asset('/js/bootstrap.min.js')}}"></script>
<script src="{{ asset('/js/app.min.js')}}"></script>
<script src="{{ asset('/js/plugins/sweetalert.min.js')}}"></script>
<script src="{{ asset('/js/plugins/toastr.min.js')}}"></script>
<script src="{{ asset('/js/harpia.js')}}"></script>

{!! Flash::render() !!}

@section('scripts')

@show
</body>
</html>
