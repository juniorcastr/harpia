<?php

return [
    'prelogin_openroutes' => array(
        'auth.login',
        'index',
        'alunos.comprovante.verifica',
        'auth.forget-password',
        'auth.reset-password',
        'rh.api.ponto-remoto.entrada',
        'rh.api.ponto-remoto.saida',
    ),

    'postlogin_openroutes' => array(
        'auth.logout',
        'index',
        'alunos.comprovante.verifica',
        'seguranca.profile.profile-picture',
        'seguranca.profile.picture',
        'seguranca.profile.index',
        'seguranca.profile.updatepassword',
        'rh.ponto-remoto.index',
        'rh.ponto-remoto.entrada',
        'rh.ponto-remoto.saida',
        'rh.aprovacoes-ponto.index',
        'rh.aprovacoes-ponto.show',
        'rh.aprovacoes-ponto.aprovar',
        'rh.aprovacoes-ponto.reprovar',

    )
];
