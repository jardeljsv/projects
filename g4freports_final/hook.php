<?php

function plugin_g4freports_install(): bool
{
    \Config::setConfigurationValues('plugin:g4freports', [
        'enabled'                 => 1,
        'default_profile_name'    => 'GLPI padrão',
        'api_base_url'            => '',
        'api_app_token'           => '',
        'api_user_token'          => '',
        'api_session_token'       => '',
        'api_app_token_env'       => '',
        'api_user_token_env'      => '',
        'api_session_token_env'   => '',
        'api_auth_mode'           => 'user_token',
        'api_verify_tls'          => 1,
        'default_entity_id'       => 0,
        'recursive_entities'      => 1,
        'allowed_groups'          => '',
        'allowed_profiles'        => '',
        'api_profiles_json'       => '',
        'group_routes_json'       => '',
        'max_items'               => 200,
        'max_candidates'          => 60,
        'subitem_limit'           => 50,
        'max_auto_groups'         => 5,
        'max_runtime_seconds'     => 22,
        'extract_request_timeout_ms' => 12000,
        'request_timeout_ms'      => 60000,
        'mask_sensitive_data'     => 1,
        'field_overrides_json'    => '{}',
        'default_footer'          => 'Brasília - DF, 70712-900 | SCN Q 2 BL A - Asa Norte, Corporate Financial Center | contato@g4f.com.br | www.g4f.com.br',
        'contract_label'          => 'SES-DF',
        'default_report_title'    => 'Relatório Gerencial de Atividades',
        'default_scope_text'      => 'Este relatório apresenta as atividades executadas no período selecionado, consolidando registros obtidos via GLPI e complementações narrativas inseridas pelo profissional responsável.',
        'default_objective_text'  => 'Formalizar, padronizar e evidenciar as ações técnicas executadas, mantendo rastreabilidade por chamados, projetos, tarefas, acompanhamentos e registros manuais.'
    ]);

    return true;
}

function plugin_g4freports_uninstall(): bool
{
    $cfg = new \Config();
    $cfg->deleteByCriteria(['context' => 'plugin:g4freports']);
    return true;
}
