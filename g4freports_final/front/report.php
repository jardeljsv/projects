<?php

require_once dirname(__DIR__) . '/inc/bootstrap.php';

use GlpiPlugin\G4freports\Config;
use GlpiPlugin\G4freports\Security;

if (class_exists('Session')) {
    Session::checkLoginUser();
}

$cfg = Config::merged();
if (!Security::canUse($cfg)) {
    if (class_exists('Html')) {
        Html::displayRightError();
    }
    http_response_code(403);
    exit;
}

$profiles = Config::profilesForCurrentUser($cfg);
if (empty($profiles)) {
    if (class_exists('Html')) { Html::displayRightError(); }
    http_response_code(403);
    exit;
}

if (class_exists('Html')) {
    Html::header(__('Relatórios G4F', 'g4freports'), $_SERVER['PHP_SELF'], 'tools', 'PluginG4freportsMenu');
}
$css = dirname(__DIR__) . '/assets/css/g4freports.css';
$js  = dirname(__DIR__) . '/assets/js/report.js';
?>
<style><?php if (is_file($css)) { readfile($css); } ?></style>
<div class="g4fr-wrap">
  <div class="g4fr-header">
    <div>
      <h1>Relatórios G4F</h1>
      <p>Gerador API-first de rascunhos editáveis com múltiplos contextos, narrativa manual e exportação DOCX.</p>
    </div>
    <div class="g4fr-step-note">
      <strong>Uso operacional</strong><br>
      Escolha o profissional, marque os contextos necessários, gere o rascunho, revise as entradas e exporte o DOCX.
    </div>
  </div>

  <div class="g4fr-card">
    <h2>1. Profissional, período e modelo</h2>
    <form id="g4fr-form">
      <div class="g4fr-grid">
        <div class="g4fr-field">
          <label>Conexão GLPI</label>
          <select name="profile_id" id="g4fr-profile" class="form-select">
            <?php foreach ($profiles as $id => $profile): ?>
              <option value="<?php echo htmlspecialchars($id, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $id === '__default__' ? 'selected' : ''; ?>><?php echo htmlspecialchars((string)($profile['name'] ?? $id), ENT_QUOTES, 'UTF-8'); ?></option>
            <?php endforeach; ?>
          </select>
          <small>Perfis liberados pela configuração administrativa.</small>
        </div>

        <div class="g4fr-field">
          <label>Pesquisar agente</label>
          <div class="input-group">
            <input type="text" id="g4fr-agent-search-q" class="form-control" placeholder="Nome, login ou parte do nome">
            <button type="button" id="g4fr-agent-search-btn" class="btn btn-outline-secondary">Buscar</button>
          </div>
          <div id="g4fr-agent-results" class="g4fr-search-results" style="display:none"></div>
          <div id="g4fr-selected-agent" class="g4fr-selected-agent" style="display:none"></div>
        </div>

        <div class="g4fr-field">
          <label>ID do agente principal</label>
          <input type="number" name="agent_id" id="g4fr-agent-id" class="form-control g4fr-readonly" placeholder="Selecione pela busca acima" min="1" readonly>
        </div>

        <div class="g4fr-field">
          <label>Nome do agente</label>
          <input type="text" name="agent_name" id="g4fr-agent-name" class="form-control g4fr-readonly" placeholder="Selecione pela busca acima" readonly>
        </div>

        <div class="g4fr-field">
          <label>Início</label>
          <input type="date" name="period_start" id="g4fr-start" class="form-control" value="<?php echo date('Y-m-01'); ?>">
        </div>

        <div class="g4fr-field">
          <label>Fim</label>
          <input type="date" name="period_end" id="g4fr-end" class="form-control" value="<?php echo date('Y-m-t'); ?>">
        </div>

        <div class="g4fr-field g4fr-full">
          <label>Critério temporal dos chamados</label>
          <div class="g4fr-date-contexts" id="g4fr-date-contexts">
            <label><input type="checkbox" value="created_with_agent_action"> Criados no período com ação detectável do agente</label>
            <label><input type="checkbox" value="updated_in_period" checked> Atualizados no período</label>
            <label><input type="checkbox" value="solved_or_closed_in_period" checked> Solucionados/fechados no período</label>
            <label><input type="checkbox" value="solved_or_closed_by_agent"> Solucionados/fechados pelo agente</label>
          </div>
          <small>Estes critérios são aplicados junto com agente, grupos e contextos. “Pelo agente” exige autoria detectável em tarefas, acompanhamentos ou soluções expostas pela API.</small>
        </div>

        <div class="g4fr-field">
          <label>Modelo DOCX</label>
          <select name="template" id="g4fr-template" class="form-select">
            <option value="operational">Operacional diário/mensal</option>
            <option value="formal_managerial">Gerencial formal</option>
            <option value="project_technical">Técnico/projeto</option>
            <option value="annex_full">Anexo evidencial completo</option>
          </select>
        </div>

        <div class="g4fr-field g4fr-full">
          <label>Grupos adicionais</label>
          <div class="input-group">
            <input type="text" id="g4fr-group-search-q" class="form-control" placeholder="Nome do grupo ou ID">
            <button type="button" id="g4fr-group-search-btn" class="btn btn-outline-secondary">Buscar grupo</button>
          </div>
          <div id="g4fr-group-results" class="g4fr-search-results" style="display:none"></div>
          <div id="g4fr-selected-groups" class="g4fr-selected-list"></div>
          <input type="hidden" name="group_ids" id="g4fr-group-ids" value="">
          <small>Opcional. Pesquise pelo nome ou ID. Contextos por grupo usam somente os grupos escolhidos aqui; não há fallback automático para grupos do agente. A atribuição por grupo usa a atribuição atual do chamado quando a API não expõe histórico de grupo.</small>
        </div>
      </div>
    </form>
  </div>

  <div class="g4fr-card">
    <h2>2. Contextos e análises incluídos no rascunho</h2>
    <p class="g4fr-muted">Marque quantos contextos forem necessários. O agente principal é a âncora. Contextos por grupo só usam os grupos selecionados no campo acima e são processados em lotes para evitar timeout.</p>
    <div class="g4fr-context-toolbar">
      <button type="button" class="btn btn-outline-secondary btn-sm" data-context-preset="operational">Operacional</button>
      <button type="button" class="btn btn-outline-secondary btn-sm" data-context-preset="project">Projeto</button>
      <button type="button" class="btn btn-outline-secondary btn-sm" data-context-preset="managerial">Gerencial</button>
      <button type="button" class="btn btn-outline-secondary btn-sm" data-context-preset="all">Marcar todos</button>
      <button type="button" class="btn btn-outline-secondary btn-sm" data-context-preset="none">Limpar</button>
    </div>
    <div class="g4fr-contexts" id="g4fr-contexts">
      <div class="g4fr-context-section">
        <h3>Chamados</h3>
        <label><input type="checkbox" value="tickets_assigned_user" checked> Chamados atribuídos ao agente</label>
        <label><input type="checkbox" value="tickets_requested_by_user"> Chamados solicitados pelo agente</label>
        <label><input type="checkbox" value="tickets_assigned_groups"> Chamados atualmente atribuídos aos grupos selecionados</label>
      </div>
      <div class="g4fr-context-section">
        <h3>Atividades em chamados</h3>
        <label><input type="checkbox" value="ticket_tasks_by_user" checked> Tarefas feitas pelo agente</label>
        <label><input type="checkbox" value="ticket_followups_by_user" checked> Acompanhamentos feitos pelo agente</label>
        <label><input type="checkbox" value="ticket_solutions_by_user" checked> Soluções registradas pelo agente</label>
      </div>
      <div class="g4fr-context-section">
        <h3>Projetos</h3>
        <label><input type="checkbox" value="projects_managed_by_user"> Projetos gerenciados pelo agente</label>
        <label><input type="checkbox" value="projects_managed_by_groups"> Projetos gerenciados pelos grupos selecionados</label>
        <label><input type="checkbox" value="projects_member_user"> Projetos em que o agente participa</label>
      </div>
      <div class="g4fr-context-section">
        <h3>Tarefas e narrativa</h3>
        <label><input type="checkbox" value="project_tasks_user"> Tarefas de projeto do agente</label>
        <label><input type="checkbox" value="project_tasks_groups"> Tarefas de projeto dos grupos selecionados</label>
        <label><input type="checkbox" value="manual_entries" checked> Permitir entradas narrativas manuais</label>
      </div>
    </div>

    <h3 class="g4fr-subtitle">Análises e tabelas gerenciais</h3>
    <p class="g4fr-muted">As análises são calculadas a partir do mesmo rascunho carregado. Não há nova varredura separada de chamados ou projetos somente para gráficos.</p>
    <div class="g4fr-contexts g4fr-analytics" id="g4fr-analytics">
      <div class="g4fr-context-section">
        <h3>Volume e distribuição</h3>
        <label><input type="checkbox" value="daily_volume" checked> Evolução diária (linha)</label>
        <label><input type="checkbox" value="daily_heatmap"> Mapa de calor por semana</label>
        <label><input type="checkbox" value="status_distribution" checked> Status dos chamados/atividades (donut)</label>
        <label><input type="checkbox" value="source_distribution"> Origem das atividades (pizza)</label>
        <label><input type="checkbox" value="top_categories"> Top categorias (barras)</label>
      </div>
      <div class="g4fr-context-section">
        <h3>Equipe e localização</h3>
        <label><input type="checkbox" value="agent_vs_group"> Agente x grupos selecionados (pizza)</label>
        <label><input type="checkbox" value="location_distribution"> Localização dos chamados (donut)</label>
        <label><input type="checkbox" value="consolidated_table" checked> Tabela consolidada de indicadores</label>
      </div>
      <div class="g4fr-context-section">
        <h3>Tempo de resposta</h3>
        <label><input type="checkbox" value="avg_response_time"> Tempo médio de resposta após evento do solicitante</label>
        <label><input type="checkbox" value="first_response_time"> Tempo de primeira resposta técnica</label>
        <label><input type="checkbox" value="unanswered_events"> Eventos do solicitante sem resposta técnica</label>
      </div>
    </div>

    <h3 class="g4fr-subtitle">Contexto de dados</h3>
    <p class="g4fr-muted">Filtros de dados são aplicados em conjunto com os contextos escolhidos acima. Primeiro o módulo restringe por agente/grupos/período/contextos; depois aplica os filtros por título ou modo gerencial.</p>
    <div class="g4fr-contexts g4fr-data-context" id="g4fr-data-context">
      <div class="g4fr-context-section g4fr-fullish">
        <h3>Alto volume e desempenho</h3>
        <label for="g4fr-response-mode">Modo de análise de tempo de resposta</label>
        <select id="g4fr-response-mode" class="form-select">
          <option value="fast" selected>Rápido / alto volume — não lê a linha do tempo completa de cada chamado</option>
          <option value="precise">Preciso — lê eventos dos chamados deduplicados; pode ser lento</option>
          <option value="deep">Profundo — tenta mais páginas de eventos; usar apenas para volumes pequenos</option>
        </select>
        <small>Para relatórios com milhares de chamados, use o modo rápido. Os indicadores de volume, status, categoria, localização disponível e resumo por agente continuam sendo gerados; tempos de resposta precisos exigem leitura de eventos e devem ser usados em recortes menores.</small>
        <div class="mt-2">
          <label><input type="checkbox" id="g4fr-full-detail-export"> Exportar detalhamento completo no DOCX mesmo quando houver milhares de chamados</label>
          <small>Desmarcado por padrão para evitar arquivos DOCX excessivamente grandes. Em relatórios de alto volume, o DOCX mantém indicadores, gráficos e resumos; o detalhamento completo deve ser usado apenas quando realmente necessário.</small>
        </div>
      </div>

      <div class="g4fr-context-section g4fr-fullish">
        <h3>Filtro por título de chamado</h3>
        <label><input type="checkbox" id="g4fr-title-filter-enabled"> Filtrar chamados por título</label>
        <div id="g4fr-title-filter-panel" class="g4fr-filter-panel" style="display:none">
          <div class="g4fr-grid">
            <div class="g4fr-field">
              <label>Título do chamado</label>
              <div class="input-group">
                <input type="text" id="g4fr-title-filter-text" class="form-control" placeholder="Ex.: Catálogo de Atividades – Controle Diário">
                <button type="button" id="g4fr-title-filter-add" class="btn btn-outline-secondary">Adicionar título</button>
                <button type="button" id="g4fr-title-filter-clear" class="btn btn-outline-secondary">Limpar campo</button>
              </div>
              <small>Você pode digitar parte do título ou selecionar chamados de referência pelo número. É possível adicionar múltiplos títulos.</small>
            </div>
            <div class="g4fr-field">
              <label>Buscar chamado por número</label>
              <div class="input-group">
                <input type="number" id="g4fr-ticket-id-search" class="form-control" placeholder="Ex.: 255947" min="1">
                <button type="button" id="g4fr-ticket-search-btn" class="btn btn-outline-secondary">Buscar chamado</button>
              </div>
              <div id="g4fr-ticket-results" class="g4fr-search-results" style="display:none"></div>
            </div>
          </div>
          <div id="g4fr-selected-ticket-titles" class="g4fr-selected-list"></div>
          <div class="g4fr-filter-scope">
            <label><input type="checkbox" id="g4fr-title-filter-detail-only" checked> Aplicar filtro apenas ao detalhamento editável e ao corpo do DOCX. Indicadores e gráficos continuam considerando todos os chamados do escopo.</label>
            <small>Desmarque para aplicar o filtro também aos indicadores, gráficos e números macro.</small>
          </div>
        </div>
      </div>
      <div class="g4fr-context-section g4fr-fullish">
        <h3>Modo gerencial</h3>
        <label><input type="checkbox" id="g4fr-manager-mode"> Gerar relatório gerencial por agentes dos grupos selecionados</label>
        <p class="g4fr-muted">Quando ativo, o detalhamento individual de chamados é omitido. O relatório prioriza indicadores por agente/equipe, status, categoria, localização, evolução e tempo de resposta. Requer pelo menos um grupo selecionado.</p>
      </div>
    </div>

  </div>

  <div class="g4fr-card">
    <h2>3. Textos do relatório</h2>
    <div class="g4fr-grid">
      <div class="g4fr-field g4fr-full">
        <label>Título do relatório</label>
        <input type="text" name="report_title" id="g4fr-title" class="form-control" value="<?php echo htmlspecialchars((string)($cfg['default_report_title'] ?? 'Relatório Gerencial de Atividades'), ENT_QUOTES, 'UTF-8'); ?>">
      </div>
      <div class="g4fr-field">
        <label>Abrangência</label>
        <textarea name="scope_text" id="g4fr-scope" class="form-control" rows="5"><?php echo htmlspecialchars((string)($cfg['default_scope_text'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
      </div>
      <div class="g4fr-field">
        <label>Objetivo</label>
        <textarea name="objective_text" id="g4fr-objective" class="form-control" rows="5"><?php echo htmlspecialchars((string)($cfg['default_objective_text'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
      </div>
    </div>
    <div class="g4fr-sticky-actions mt-3">
      <div class="g4fr-actions">
        <button type="button" id="g4fr-build" class="btn btn-primary">Gerar rascunho pela API</button>
        <button type="button" id="g4fr-add-manual" class="btn btn-outline-secondary" disabled>Adicionar entrada manual</button>
        <button type="button" id="g4fr-download-json" class="btn btn-outline-secondary" disabled>Baixar rascunho JSON</button>
        <label class="btn btn-outline-secondary mb-0">Importar JSON <input type="file" id="g4fr-import-json" accept="application/json" hidden></label>
        <button type="button" id="g4fr-export" class="btn btn-success" disabled>Exportar DOCX</button>
      </div>
    </div>
  </div>

  <div id="g4fr-status" class="g4fr-status"></div>
  <div id="g4fr-preview"></div>
</div>
<script>
<?php
$g4frRootDoc = isset($GLOBALS['CFG_GLPI']['root_doc']) ? rtrim((string)$GLOBALS['CFG_GLPI']['root_doc'], '/') : '';
$g4frApiUrl = ($g4frRootDoc !== '' ? $g4frRootDoc : '') . '/plugins/g4freports/front/api.php';
$g4frExportUrl = ($g4frRootDoc !== '' ? $g4frRootDoc : '') . '/plugins/g4freports/front/export.php';
?>
window.G4FREPORTS = { api_url: <?php echo json_encode($g4frApiUrl); ?>, export_url: <?php echo json_encode($g4frExportUrl); ?>, csrf: <?php echo json_encode((class_exists('Session') && method_exists('Session', 'getNewCSRFToken')) ? Session::getNewCSRFToken() : ''); ?> };
</script>
<script><?php if (is_file($js)) { readfile($js); } ?></script>
<?php
if (class_exists('Html')) {
    Html::footer();
}
