<?php

/** Factories Modulo Geral */
$factory->define(Modulos\Geral\Models\Pessoa::class, function (Faker\Generator $faker) {
    return [
        'pes_nome' => $faker->name,
        'pes_sexo' => $faker->randomElement(['M', 'F']),
        'pes_email' => $faker->email,
        'pes_telefone' => $faker->phoneNumber,
        'pes_nascimento' => $faker->date(),
        'pes_mae' => $faker->name,
        'pes_pai' => $faker->name,
        'pes_estado_civil' => $faker->randomElement(['solteiro', 'casado', 'viuvo', 'separado']),
        'pes_naturalidade' => $faker->city,
        'pes_nacionalidade' => $faker->country,
        'pes_raca' => $faker->randomElement(['branco', 'negro', 'amarelo']),
        'pes_necessidade_especial' => $faker->randomElement(['s', 'n']),
        'pes_estrangeiro' => $faker->boolean
    ];
});

$factory->define(Modulos\Geral\Models\Documento::class, function (Faker\Generator $faker) {
   return [
       'doc_pes_id' => factory(Modulos\Geral\Models\Pessoa::class)->create()->pes_id,
       'doc_tpd_id' => 2,
       'doc_conteudo' => $faker->creditCardNumber
   ];
});

$factory->define(Modulos\Geral\Models\Anexo::class, function (Faker\Generator $faker) {
    return [
        'anx_tax_id' => $faker->randomNumber(1),
        'anx_nome' => $faker->word,
        'anx_mime' => $faker->mimeType,
        'anx_localizacao' => base_path(),
    ];
});

/** Factories Modulo Segurança */
$factory->define(Modulos\Seguranca\Models\Modulo::class, function (Faker\Generator $faker) {
    $rota = $faker->name;

    return [
        'mod_nome' => $rota,
        'mod_rota' => strtolower($rota),
        'mod_descricao' => $faker->sentence(3),
        'mod_icone' => 'fa fa-cog',
        'mod_ativo' => 1
    ];
});

$factory->define(Modulos\Seguranca\Models\Perfil::class, function (Faker\Generator $faker) {
    return [
        'prf_mod_id' => 1,
        'prf_nome' => $faker->name,
        'prf_descricao' => $faker->sentence(3)
    ];
});

$factory->define(Modulos\Seguranca\Models\CategoriaRecurso::class, function (Faker\Generator $faker) {
    return [
        'ctr_mod_id' => 1,
        'ctr_nome' => $faker->name,
        'ctr_descricao' => $faker->sentence(3),
        'ctr_icone' => 'fa fa-cog',
        'ctr_ordem' => 1,
        'ctr_ativo' => 1,
        'ctr_visivel' => 1
    ];
});

$factory->define(Modulos\Seguranca\Models\Recurso::class, function (Faker\Generator $faker) {
    $rota = $faker->name;

    return [
        'rcs_ctr_id' => 1,
        'rcs_nome' => $rota,
        'rcs_rota' => strtolower($rota),
        'rcs_descricao' => $faker->sentence(3),
        'rcs_icone' => 'fa fa-cog',
        'rcs_ativo' => 1,
        'rcs_ordem' => 1
    ];
});

$factory->define(Modulos\Seguranca\Models\Permissao::class, function (Faker\Generator $faker) {
    return [
        'prm_rcs_id' => 1,
        'prm_nome' => $faker->name,
        'prm_descricao' => $faker->sentence(3)
    ];
});

$factory->define(Modulos\Seguranca\Models\Usuario::class, function (Faker\Generator $faker) {
    return [
        'usr_pes_id' => factory(Modulos\Geral\Models\Pessoa::class)->create()->pes_id,
        'usr_usuario' => $faker->email,
        'usr_senha' => $faker->password,
        'usr_ativo' => 1
    ];
});

/** Factories Modulo Academico */
$factory->define(Modulos\Academico\Models\Departamento::class, function(Faker\Generator $faker){
   return [
       'dep_cen_id' => 1,
       'dep_prf_diretor' => 1,
       'dep_nome' => $faker->word
   ];
});

$factory->define(Modulos\Academico\Models\Centro::class, function(Faker\Generator $faker){
    return [
        'cen_prf_diretor' => 1,
        'cen_nome' => $faker->word,
        'cen_sigla' => $faker->word,
    ];
});

$factory->define(Modulos\Academico\Models\Professor::class, function(Faker\Generator $faker){
    return [
        'prf_pes_id' => 1
    ];
});


$factory->define(Modulos\Academico\Models\PeriodoLetivo::class, function(Faker\Generator $faker){
    return [
        'per_nome' => $faker->word,
        'per_inicio' => $faker->date('d/m/Y'),
        'per_fim' => $faker->date('d/m/Y'),
    ];
});

$factory->define(Modulos\Academico\Models\Polo::class, function (Faker\Generator $faker) {
    return [
        'pol_nome' => $faker->city
    ];
});

$factory->define(Modulos\Academico\Models\Curso::class, function (Faker\Generator $faker) {
    return [
        'crs_dep_id' => 1,
        'crs_nvc_id' => 1,
        'crs_prf_diretor' => 1,
        'crs_nome' => $faker->name,
        'crs_sigla' => $faker->name,
        'crs_descricao' => $faker->sentence(3),
        'crs_resolucao' => $faker->sentence(3),
        'crs_autorizacao' => $faker->sentence(3),
        'crs_data_autorizacao' => $faker->date('d/m/Y'),
        'crs_eixo' => $faker->sentence(3),
        'crs_habilitacao' => $faker->sentence(3)
    ];
});

$factory->define(Modulos\Academico\Models\OfertaCurso::class, function (Faker\Generator $faker) {
    return [
        'ofc_crs_id' => 1,
        'ofc_mtc_id' => 1,
        'ofc_mdl_id' => 1,
        'ofc_ano' =>2005
    ];
});

$factory->define(Modulos\Academico\Models\MatrizCurricular::class, function (Faker\Generator $faker) {
    return [
        'mtc_crs_id' => 1,
        'mtc_anx_projeto_pedagogico' => $faker->randomNumber(2),
        'mtc_descricao' => $faker->words(5, true),
        'mtc_data' => $faker->date('d/m/Y'),
        'mtc_creditos' => $faker->randomNumber(3),
        'mtc_horas' => $faker->randomNumber(4),
        'mtc_horas_praticas' => $faker->randomNumber(4)
    ];
});

$factory->define(Modulos\Academico\Models\Grupo::class, function (Faker\Generator $faker) {
   return [
       'grp_trm_id' => 1,
       'grp_pol_id' => 1,
       'grp_nome' => $faker->name
   ];
});

$factory->define(Modulos\Academico\Models\Turma::class, function (Faker\Generator $faker) {
    return [
        'trm_ofc_id' => 1,
        'trm_per_id' => 1,
        'trm_nome' => $faker->sentence(3),
        'trm_qtd_vagas' => 30
    ];
});

$factory->define(Modulos\Academico\Models\ModuloMatriz::class, function (Faker\Generator $faker) {
    return [
        'mdo_mtc_id' => 1,
        'mdo_nome' => $faker->name,
        'mdo_descricao' => $faker->sentence(3),
        'mdo_qualificacao' => $faker->sentence(3)
    ];
});

$factory->define(Modulos\Academico\Models\Aluno::class, function (Faker\Generator $faker) {
    return [
        'alu_pes_id' => factory(Modulos\Geral\Models\Aluno::class)->create()->pes_id
    ];
});

$factory->define(Modulos\Academico\Models\Tutor::class, function (Faker\Generator $faker) {
    return [
        'tut_pes_id' => factory(Modulos\Geral\Models\Pessoa::class)->create()->pes_id
    ];
});

$factory->define(Modulos\Academico\Models\AmbienteVitual::class, function (Faker\Generator $faker) {
    return [
        'amb_nome' => $faker->name,
        'amb_versao' => $faker->sentence(2),
        'amb_token' => $faker->sentence(3)
    ];
});
