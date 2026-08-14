(function () {
  'use strict';

  var apiUrl = (window.G4FREPORTS && window.G4FREPORTS.api_url) || 'api.php';
  var exportUrl = (window.G4FREPORTS && window.G4FREPORTS.export_url) || 'export.php';
  var csrfToken = (window.G4FREPORTS && window.G4FREPORTS.csrf) || '';
  var draft = null;
  var selectedGroups = {};
  var selectedTicketTitles = {};
  var generationToken = 0;
  var detailPage = 1;
  var detailPageSize = 10;


  function $(id) { return document.getElementById(id); }

  function html(s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[c];
    });
  }

  function setStatus(type, message) {
    var box = $('g4fr-status');
    if (!box) return;
    if (!message) { box.innerHTML = ''; return; }
    box.innerHTML = '<div class="alert alert-' + html(type || 'info') + '">' + html(message) + '</div>';
  }

  function setDraftButtons(enabled) {
    ['g4fr-add-manual', 'g4fr-download-json', 'g4fr-export'].forEach(function (id) {
      var el = $(id);
      if (el) el.disabled = !enabled;
    });
  }

  function clearPreview() {
    var box = $('g4fr-preview');
    if (box) box.innerHTML = '';
  }

  function markExtractionDirty(reason) {
    if (!draft) return;
    setDraftButtons(false);
    var box = $('g4fr-status');
    if (box) {
      box.innerHTML = '<div class="g4fr-dirty-note">Parâmetros de extração alterados' + (reason ? ': ' + html(reason) : '') + '. Gere o rascunho novamente antes de exportar.</div>';
    }
  }

  function selectedContexts() {
    var out = [];
    document.querySelectorAll('#g4fr-contexts input[type="checkbox"]:checked').forEach(function (el) { out.push(el.value); });
    return out;
  }

  function selectedAnalytics() {
    var out = [];
    document.querySelectorAll('#g4fr-analytics input[type="checkbox"]:checked').forEach(function (el) { out.push(el.value); });
    return out;
  }

  function selectedDateContext() {
    var out = {};
    document.querySelectorAll('#g4fr-date-contexts input[type="checkbox"]').forEach(function (el) {
      out[el.value] = !!el.checked;
    });
    if (!Object.keys(out).some(function (k) { return out[k]; })) {
      out.updated_in_period = true;
    }
    return out;
  }

  function setDateContext(values) {
    values = values || {};
    document.querySelectorAll('#g4fr-date-contexts input[type="checkbox"]').forEach(function (el) {
      el.checked = !!values[el.value];
    });
  }

  function collectDataContext() {
    var enabledEl = $('g4fr-title-filter-enabled');
    var detailOnlyEl = $('g4fr-title-filter-detail-only');
    var managerEl = $('g4fr-manager-mode');
    var textEl = $('g4fr-title-filter-text');
    var fullDetailExportEl = $('g4fr-full-detail-export');
    var responseModeEl = $('g4fr-response-mode');
    var titles = selectedTicketTitlesArray();
    var inlineTitle = textEl && textEl.value ? textEl.value.trim() : '';
    if (inlineTitle !== '') {
      addTitleToArray(titles, { title: inlineTitle, ticket_id: textEl.getAttribute('data-ticket-id') || '' });
    }
    var titleFilterEnabled = !!(enabledEl && enabledEl.checked);
    var managerMode = !!(managerEl && managerEl.checked);
    var scope = detailOnlyEl && detailOnlyEl.checked ? 'detail_only' : 'global';
    if (managerMode && titleFilterEnabled) scope = 'global';
    return {
      ticket_title_filter_enabled: titleFilterEnabled,
      ticket_title_filter_scope: scope,
      ticket_titles: titles,
      manager_mode_enabled: managerMode,
      full_detail_export_enabled: !!(fullDetailExportEl && fullDetailExportEl.checked),
      response_time_mode: responseModeEl ? String(responseModeEl.value || 'fast') : 'fast',
      high_volume_fast: managerMode && (!responseModeEl || String(responseModeEl.value || 'fast') === 'fast')
    };
  }

  function selectedTicketTitlesArray() {
    return Object.keys(selectedTicketTitles).sort().map(function (key) {
      return Object.assign({}, selectedTicketTitles[key]);
    });
  }

  function addTitleToArray(list, item) {
    var title = String((item && item.title) || '').trim();
    if (!title) return;
    var ticketId = String((item && item.ticket_id) || '').trim();
    var key = normalizeForMatch(title) + '|' + ticketId;
    var exists = list.some(function (row) { return normalizeForMatch(row.title || '') + '|' + String(row.ticket_id || '') === key; });
    if (!exists) list.push({ title: title, ticket_id: ticketId });
  }

  function dataContextEnabledForTitle(ctx) {
    return ctx && ctx.ticket_title_filter_enabled && (ctx.ticket_titles || []).length > 0;
  }

  function normalizeForMatch(value) {
    var s = String(value || '').trim().toLowerCase();
    try { s = s.normalize('NFD').replace(/[̀-ͯ]/g, ''); } catch (e) {}
    return s.replace(/\s+/g, ' ');
  }


  function setChecked(containerId, values) {
    var map = {};
    (values || []).forEach(function (v) { map[v] = true; });
    document.querySelectorAll('#' + containerId + ' input[type="checkbox"]').forEach(function (el) {
      el.checked = !!map[el.value];
    });
  }

  function applyContextPreset(preset) {
    var contexts = [];
    var analytics = [];
    var dateCtx = null;
    if (preset === 'operational') {
      contexts = ['tickets_assigned_user','ticket_tasks_by_user','ticket_followups_by_user','ticket_solutions_by_user','manual_entries'];
      analytics = ['daily_volume','status_distribution','consolidated_table'];
      dateCtx = { updated_in_period: true, solved_or_closed_in_period: true };
    } else if (preset === 'project') {
      contexts = ['projects_managed_by_user','projects_member_user','project_tasks_user','manual_entries'];
      analytics = ['daily_volume','daily_heatmap','source_distribution','status_distribution','consolidated_table'];
      dateCtx = { updated_in_period: true };
    } else if (preset === 'managerial') {
      contexts = ['tickets_assigned_groups','manual_entries'];
      analytics = ['daily_volume','daily_heatmap','status_distribution','source_distribution','agent_vs_group','top_categories','location_distribution','avg_response_time','first_response_time','unanswered_events','consolidated_table'];
      dateCtx = { updated_in_period: true, solved_or_closed_in_period: true };
    } else if (preset === 'all') {
      contexts = Array.prototype.slice.call(document.querySelectorAll('#g4fr-contexts input[type="checkbox"]')).map(function (el) { return el.value; });
      analytics = Array.prototype.slice.call(document.querySelectorAll('#g4fr-analytics input[type="checkbox"]')).map(function (el) { return el.value; });
      dateCtx = { created_with_agent_action: true, updated_in_period: true, solved_or_closed_in_period: true, solved_or_closed_by_agent: true };
    } else if (preset === 'none') {
      contexts = [];
      analytics = [];
      dateCtx = { updated_in_period: true };
    }
    setChecked('g4fr-contexts', contexts);
    setChecked('g4fr-analytics', analytics);
    if (dateCtx) setDateContext(dateCtx);
    var mgr = $('g4fr-manager-mode');
    if (mgr) mgr.checked = preset === 'managerial';
    var rm = $('g4fr-response-mode');
    if (rm) {
      if (preset === 'managerial' || preset === 'none') rm.value = 'fast';
      if (preset === 'operational') rm.value = 'precise';
      if (preset === 'all') rm.value = 'fast';
    }
    markExtractionDirty('contextos/análises');
  }

  function analyticsDefaultsForTemplate(template) {
    if (template === 'formal_managerial') {
      return ['daily_volume','daily_heatmap','status_distribution','source_distribution','agent_vs_group','top_categories','location_distribution','avg_response_time','first_response_time','unanswered_events','consolidated_table'];
    }
    if (template === 'project_technical') {
      return ['daily_volume','daily_heatmap','source_distribution','status_distribution','consolidated_table'];
    }
    if (template === 'annex_full') {
      return ['consolidated_table','status_distribution','source_distribution'];
    }
    return ['daily_volume','status_distribution','consolidated_table'];
  }

  function collectRequest() {
    var dataContext = collectDataContext();
    var contexts = selectedContexts();
    // Manager mode is explicitly group-based. It uses the selected groups as
    // the operational scope, but it still composes with the other contexts; it
    // does not infer/fallback to the agent's own groups.
    if (dataContext.manager_mode_enabled && contexts.indexOf('tickets_assigned_groups') === -1) {
      contexts.push('tickets_assigned_groups');
    }
    return {
      profile_id: $('g4fr-profile').value,
      agent_id: $('g4fr-agent-id').value,
      agent_name: $('g4fr-agent-name').value,
      period_start: $('g4fr-start').value,
      period_end: $('g4fr-end').value,
      template: $('g4fr-template').value,
      group_ids: $('g4fr-group-ids').value,
      groups: selectedGroupsArray(),
      report_title: $('g4fr-title').value,
      scope_text: $('g4fr-scope').value,
      objective_text: $('g4fr-objective').value,
      contexts: contexts,
      analytics: selectedAnalytics(),
      date_context: selectedDateContext(),
      data_context: dataContext
    };
  }

  function callApi(action, payload) {
    var url = new URL(apiUrl, window.location.href);
    url.searchParams.set('action', action);
    url.searchParams.set('payload', JSON.stringify(payload || {}));
    url.searchParams.set('_', String(Date.now()));
    return fetch(url.toString(), {
      method: 'GET',
      credentials: 'same-origin',
      cache: 'no-store',
      headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
    }).then(parseApiResponse);
  }

  function postApi(action, payload) {
    // This environment follows the same hardening pattern used by GLPIBot:
    // browser -> plugin front endpoints must remain GET-compatible. PHP may
    // still call the GLPI REST API with any method server-side, but the
    // browser must not POST JSON directly to /plugins/*/front/*.php because
    // some GLPI/proxy/WAF layers reject it with 403 before plugin code runs.
    return callApi(action, payload || {});
  }

  function readableTransportError(txt, status) {
    var raw = String(txt || '');
    if (/504 Gateway/i.test(raw)) return 'HTTP 504 Gateway Time-out: um lote excedeu o tempo de resposta do servidor. O módulo reduzirá concorrência ou aplique modo rápido/gerencial.';
    if (/403 Forbidden/i.test(raw)) return 'HTTP 403 Forbidden: chamada bloqueada pela camada GLPI/proxy.';
    var stripped = raw.replace(/<script[\s\S]*?<\/script>/gi, '').replace(/<style[\s\S]*?<\/style>/gi, '').replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim();
    if (stripped.length > 280) stripped = stripped.slice(0, 280) + '...';
    return stripped || ('HTTP ' + status);
  }

  function parseApiResponse(res) {
    return res.text().then(function (txt) {
      var data;
      try { data = JSON.parse(txt); } catch (e) {
        data = { ok: false, error: readableTransportError(txt, res.status) };
      }
      if (!res.ok) {
        throw new Error((data && data.error) || ('HTTP ' + res.status));
      }
      if (!data || typeof data !== 'object' || Array.isArray(data)) {
        throw new Error('Resposta inválida do servidor ou bloqueada pela camada GLPI/proxy.');
      }
      if (data.ok === false) throw new Error(data.error || ('HTTP ' + res.status));
      return data;
    });
  }

  function sleep(ms) {
    return new Promise(function (resolve) { window.setTimeout(resolve, ms); });
  }

  function isTransientError(err) {
    var msg = String((err && err.message) || err || '').toLowerCase();
    return /failed to fetch|connection_reset|networkerror|timeout|temporar|502|503|504|gateway|aborted/.test(msg);
  }

  function withRetry(worker, attempts, label) {
    attempts = Math.max(1, attempts || 1);
    var run = function (tryNo) {
      return worker().catch(function (err) {
        if (tryNo >= attempts || !isTransientError(err)) throw err;
        setStatus('warning', 'Falha temporária em ' + (label || 'requisição') + '. Tentando novamente (' + tryNo + '/' + (attempts - 1) + ')...');
        return sleep(800 * tryNo).then(function () { return run(tryNo + 1); });
      });
    };
    return run(1);
  }

  function runQueue(items, concurrency, worker, onProgress) {
    items = items || [];
    concurrency = Math.max(1, Math.min(parseInt(concurrency || 1, 10) || 1, 6));
    var index = 0;
    var finished = 0;
    var failures = [];
    function next() {
      if (index >= items.length) return Promise.resolve();
      var item = items[index++];
      return worker(item, index).catch(function (err) {
        failures.push(err);
      }).then(function () {
        finished++;
        if (onProgress) onProgress(finished, items.length);
        return next();
      });
    }
    var workers = [];
    var count = Math.min(concurrency, items.length);
    for (var i = 0; i < count; i++) workers.push(next());
    return Promise.all(workers).then(function () {
      if (failures.length) throw failures[0];
    });
  }

  function agentRequiredForRequest(req) {
    var contexts = req.contexts || [];
    var dc = req.date_context || {};
    return contexts.some(function (c) {
      return ['tickets_assigned_user','tickets_requested_by_user','ticket_tasks_by_user','ticket_followups_by_user','ticket_solutions_by_user','projects_managed_by_user','projects_member_user','project_tasks_user'].indexOf(c) !== -1;
    }) || !!dc.created_with_agent_action || !!dc.solved_or_closed_by_agent;
  }

  function strictTemporalCriteriaSelected(req) {
    var dc = req.date_context || {};
    return !!(dc.created_with_agent_action || dc.solved_or_closed_by_agent);
  }

  function workloadPlan(req) {
    req = req || {};
    var groups = selectedGroupsArray();
    var contexts = (req.contexts || []).slice();
    var dc = req.data_context || {};
    var dateTargets = ticketDateTargets(req.date_context || selectedDateContext());
    var responseMode = String(dc.response_time_mode || 'fast');
    var responseAnalytics = (req.analytics || []).some(function (a) { return ['avg_response_time','first_response_time','unanswered_events'].indexOf(a) !== -1; });
    var roughTargets = 0;
    contexts.forEach(function (ctx) {
      if (ctx === 'tickets_assigned_groups') roughTargets += Math.max(1, groups.length) * Math.max(1, dateTargets.length);
      else if (ctx === 'tickets_assigned_user' || ctx === 'tickets_requested_by_user') roughTargets += Math.max(1, dateTargets.length);
      else if (ctx === 'projects_managed_by_groups' || ctx === 'project_tasks_groups') roughTargets += Math.max(1, groups.length);
      else if (ctx !== 'manual_entries') roughTargets += 1;
    });
    var reasons = [];
    if (groups.length >= 4) reasons.push(groups.length + ' grupos selecionados');
    if (roughTargets > 36) reasons.push(roughTargets + ' alvos de extração estimados');
    if (responseMode !== 'fast' && responseAnalytics && groups.length >= 2) reasons.push('tempo de resposta preciso com múltiplos grupos');
    if (dc.full_detail_export_enabled && (groups.length >= 2 || roughTargets > 20)) reasons.push('detalhamento completo em escopo amplo');
    if (dateTargets.length >= 4 && groups.length >= 2) reasons.push('múltiplos critérios temporais sobre múltiplos grupos');
    if ((contexts.indexOf('ticket_tasks_by_user') !== -1 || contexts.indexOf('ticket_followups_by_user') !== -1 || contexts.indexOf('ticket_solutions_by_user') !== -1) && groups.length >= 2) reasons.push('contextos diretos de eventos em escopo amplo');
    return { unsafe: reasons.length > 0, reasons: reasons, target_count: roughTargets };
  }

  function applyHighVolumeSafetyPlan(req) {
    var plan = workloadPlan(req);
    req.data_context = req.data_context || {};
    if (!plan.unsafe) {
      req.data_context.high_volume_fast = !!req.data_context.high_volume_fast;
      return { applied: false, warnings: [], plan: plan };
    }
    var warnings = [];
    warnings.push('Plano de alto volume aplicado automaticamente para evitar timeout: ' + plan.reasons.join('; ') + '.');
    warnings.push('A extração usará modo rápido, resumo gerencial e somente contextos pesquisáveis de chamados/projetos. Critérios que exigem leitura de eventos por chamado foram desativados neste rascunho.');
    req.data_context.high_volume_fast = true;
    req.data_context.response_time_mode = 'fast';
    req.data_context.full_detail_export_enabled = false;
    if (selectedGroupsArray().length > 0) {
      req.data_context.manager_mode_enabled = true;
    }
    var keep = { tickets_assigned_groups: true, projects_managed_by_groups: true, manual_entries: true };
    if (!selectedGroupsArray().length) {
      keep.tickets_assigned_user = true;
      keep.tickets_requested_by_user = true;
      keep.projects_managed_by_user = true;
    }
    req.contexts = (req.contexts || []).filter(function (c) { return !!keep[c]; });
    if (selectedGroupsArray().length && req.contexts.indexOf('tickets_assigned_groups') === -1) req.contexts.push('tickets_assigned_groups');
    req.date_context = { updated_in_period: true, solved_or_closed_in_period: true };
    return { applied: true, warnings: warnings, plan: plan };
  }

  function buildDraft() {
    var btn = $('g4fr-build');
    var req = collectRequest();
    var dc = req.data_context || {};
    var safetyPlan = null;
    if (dc.ticket_title_filter_enabled && !(dc.ticket_titles || []).length) {
      setStatus('danger', 'Filtro por título habilitado, mas nenhum título foi informado ou selecionado. Adicione pelo menos um título de chamado.');
      return;
    }
    safetyPlan = applyHighVolumeSafetyPlan(req);
    dc = req.data_context || {};
    if (safetyPlan && safetyPlan.applied) {
      setStatus('warning', safetyPlan.warnings.join(' '));
    }
    if (dc.manager_mode_enabled && !selectedGroupsArray().length) {
      setStatus('danger', 'Modo gerencial requer pelo menos um grupo selecionado. Pesquise e adicione os grupos do escopo antes de gerar o rascunho.');
      return;
    }
    var contexts = (req.contexts || []).filter(function (c) { return c !== 'manual_entries'; });
    if (agentRequiredForRequest(req) && !(parseInt(req.agent_id || 0, 10) > 0)) {
      setStatus('danger', 'Selecione um agente na busca antes de gerar. Contextos vinculados a usuário e critérios “pelo agente” exigem um agente principal válido.');
      return;
    }
    var responseMode = String((dc && dc.response_time_mode) || 'fast');
    if (strictTemporalCriteriaSelected(req) && responseMode === 'fast') {
      setStatus('danger', 'Os critérios “criados com ação do agente” e “solucionados/fechados pelo agente” exigem confirmação por eventos. Altere o modo de tempo de resposta para “Preciso” ou “Profundo”, ou desmarque esses critérios para alto volume.');
      return;
    }
    var responseAnalytics = (req.analytics || []).some(function (a) { return ['avg_response_time','first_response_time','unanswered_events'].indexOf(a) !== -1; });
    var highVolumeFast = !!dc.high_volume_fast || (dc.manager_mode_enabled && responseMode === 'fast');
    var batchLimit = highVolumeFast ? 80 : (dc.manager_mode_enabled ? (responseMode === 'fast' ? 120 : (responseAnalytics ? 20 : 60)) : 40);
    var concurrency = highVolumeFast ? 2 : (dc.manager_mode_enabled && responseMode === 'fast' ? 2 : 1);
    var mergeState = createMergeState(req);
    var token = ++generationToken;
    var buildToken = '';
    var totalTargets = 0;

    draft = null;
    clearPreview();
    setDraftButtons(false);
    btn.disabled = true;
    setStatus('info', 'Preparando construção temporária no servidor. O navegador usará chamadas GET pequenas, compatíveis com o padrão GLPIBot.');

    function finish() {
      if (token !== generationToken) return;
      draft = finalizeMergeState(mergeState, req);
      syncDraftHeaderFromForm();
      draft.analytics = computeAnalytics(draft);
      draft.metrics = computeMetrics(draft);
      compactWorkingDraftForLargeMode(draft);
      detailPage = 1;
      var detailCount = detailEntries(draft).length;
      var suffix = draft.data_context && draft.data_context.manager_mode_enabled ? ' Modo gerencial ativo: detalhamento individual omitido.' : ' Entradas detalhadas: ' + detailCount + '.';
      var loadedLabel = draft.data_context && draft.data_context.manager_mode_enabled ? ((draft.facts && draft.facts.tickets || []).length + ' chamados analisados') : ((draft.entries || []).length + ' entradas carregadas');
      setStatus('success', 'Rascunho gerado em lotes. ' + loadedLabel + '.' + suffix + ' Revise antes de exportar.');
      setDraftButtons(true);
      renderDraft();
      if (buildToken) {
        callApi('build_finish', { token: buildToken }).catch(function () {});
      }
    }

    function fetchTargetPage(targetIndex, offset, page, ordinal) {
      setStatus('info', 'Processando alvo ' + ordinal + '/' + totalTargets + ' • lote iniciado em ' + offset + ' • concorrência ' + concurrency + '.');
      return withRetry(function () {
        return callApi('build_step', { token: buildToken, target_index: targetIndex, offset: offset });
      }, 2, 'alvo ' + ordinal).then(function (data) {
        if (token !== generationToken) return;
        var d = data.draft || {};
        mergeDraftPartIntoState(mergeState, d);
        var b = d.batch || {};
        var returned = parseInt(b.returned_candidates || 0, 10) || 0;
        var hasMore = !!b.has_more;
        var nextOffset = offset + (parseInt(data.target_limit || batchLimit, 10) || batchLimit);
        if (hasMore && returned > 0 && page < 10000) {
          return fetchTargetPage(targetIndex, nextOffset, page + 1, ordinal);
        }
      });
    }

    function processTarget(targetIndex, ordinal) {
      if (token !== generationToken) return Promise.resolve();
      return fetchTargetPage(targetIndex, 0, 0, ordinal).catch(function (err) {
        var msg = 'Alvo ' + ordinal + '/' + totalTargets + ' não pôde ser concluído: ' + (err && err.message ? err.message : String(err));
        if (mergeState.warnings.indexOf(msg) === -1) mergeState.warnings.push(msg);
        if (highVolumeFast) {
          setStatus('warning', msg + ' O rascunho continuará parcial para evitar timeout geral.');
          return;
        }
        throw err;
      });
    }

    callApi('build_start', { request: req }).then(function (start) {
      if (token !== generationToken) return;
      buildToken = start.token || '';
      totalTargets = parseInt(start.total_targets || 0, 10) || 0;
      if (Array.isArray(start.plan_warnings) && start.plan_warnings.length) {
        start.plan_warnings.forEach(function (w) { if (mergeState.warnings.indexOf(w) === -1) mergeState.warnings.push(w); });
        setStatus('warning', start.plan_warnings.join(' '));
      }
      if (!buildToken) throw new Error('Token de construção não retornado pelo servidor.');
      if (!totalTargets) {
        finish();
        return;
      }
      var indexes = [];
      for (var i = 0; i < totalTargets; i++) indexes.push(i);
      return runQueue(indexes, concurrency, processTarget, function (done, total) {
        if (token === generationToken) setStatus('info', 'Extração em andamento: ' + done + '/' + total + ' alvos concluídos.');
      }).then(function () {
        if (token === generationToken) finish();
      });
    }).catch(function (err) {
      if (token === generationToken) setStatus('danger', err.message || String(err));
    }).finally(function () {
      if (token === generationToken) btn.disabled = false;
    });
  }

  function contextLabel(ctx) {
    var labels = {
      tickets_assigned_user: 'Chamados atribuídos ao agente',
      tickets_requested_by_user: 'Chamados solicitados pelo agente',
      tickets_assigned_groups: 'Chamados atualmente atribuídos aos grupos selecionados',
      ticket_tasks_by_user: 'Tarefas feitas pelo agente',
      ticket_followups_by_user: 'Acompanhamentos feitos pelo agente',
      ticket_solutions_by_user: 'Soluções registradas pelo agente',
      projects_managed_by_user: 'Projetos gerenciados pelo agente',
      projects_managed_by_groups: 'Projetos dos grupos selecionados',
      projects_member_user: 'Projetos com participação do agente',
      project_tasks_user: 'Tarefas de projeto do agente',
      project_tasks_groups: 'Tarefas de projeto dos grupos selecionados'
    };
    return labels[ctx] || ctx;
  }

  function buildBatchTargets(contexts, req) {
    var targets = [];
    var groups = selectedGroupsArray();
    var responseAnalytics = (req.analytics || []).some(function (a) { return ['avg_response_time','first_response_time','unanswered_events'].indexOf(a) !== -1; });
    var dc = req.data_context || {};
    var responseMode = String(dc.response_time_mode || 'fast');
    var highVolumeFast = !!dc.high_volume_fast || (dc.manager_mode_enabled && responseMode === 'fast');
    var managerLimit = highVolumeFast ? 80 : (responseMode === 'fast' ? 120 : (responseAnalytics ? 20 : 60));
    var dateTargets = ticketDateTargets(req.date_context || selectedDateContext());

    (contexts || []).forEach(function (ctx) {
      if (highVolumeFast && ['ticket_tasks_by_user','ticket_followups_by_user','ticket_solutions_by_user','project_tasks_user','project_tasks_groups','projects_member_user'].indexOf(ctx) !== -1) {
        return;
      }
      if (ctx === 'tickets_assigned_groups') {
        groups.forEach(function (g) {
          dateTargets.forEach(function (dt) {
            targets.push({
              context: ctx,
              target_type: 'group',
              target_id: g.id,
              date_scope: dt.scope,
              date_basis: dt.basis,
              limit: managerLimit,
              label: contextLabel(ctx) + ' • ' + (g.name || ('Grupo #' + g.id)) + ' • ' + dt.label
            });
          });
        });
        return;
      }
      if (ctx === 'projects_managed_by_groups' || ctx === 'project_tasks_groups') {
        groups.forEach(function (g) {
          targets.push({ context: ctx, target_type: 'group', target_id: g.id, label: contextLabel(ctx) + ' • ' + (g.name || ('Grupo #' + g.id)) });
        });
        return;
      }
      if (ctx === 'tickets_assigned_user' || ctx === 'tickets_requested_by_user') {
        dateTargets.forEach(function (dt) {
          targets.push({
            context: ctx,
            target_type: 'user',
            target_id: parseInt((req && req.agent_id) || 0, 10) || 0,
            date_scope: dt.scope,
            date_basis: dt.basis,
            label: contextLabel(ctx) + ' • ' + dt.label
          });
        });
        return;
      }
      targets.push({ context: ctx, label: contextLabel(ctx) });
    });
    return targets;
  }

  function ticketDateTargets(dateContext) {
    dateContext = dateContext || {};
    var targets = [];
    if (dateContext.created_with_agent_action) {
      targets.push({ scope: 'created_with_agent_action', basis: 'date', label: 'criados no período + ação do agente' });
    }
    if (dateContext.updated_in_period) {
      targets.push({ scope: 'updated_in_period', basis: 'date_mod', label: 'atualizados no período' });
    }
    if (dateContext.solved_or_closed_in_period) {
      targets.push({ scope: 'solved_or_closed_in_period', basis: 'solvedate', label: 'solucionados no período' });
      targets.push({ scope: 'solved_or_closed_in_period', basis: 'closedate', label: 'fechados no período' });
    }
    if (dateContext.solved_or_closed_by_agent) {
      targets.push({ scope: 'solved_or_closed_by_agent', basis: 'solvedate', label: 'solucionados pelo agente' });
      targets.push({ scope: 'solved_or_closed_by_agent', basis: 'closedate', label: 'fechados pelo agente' });
    }
    if (!targets.length) {
      targets.push({ scope: 'updated_in_period', basis: 'date_mod', label: 'atualizados no período' });
    }
    var seen = {};
    return targets.filter(function (t) {
      var key = t.scope + '|' + t.basis;
      if (seen[key]) return false;
      seen[key] = true;
      return true;
    });
  }

  function baseDraft(req) {
    var dc = req.data_context || collectDataContext();
    return {
      schema_version: 2,
      created_at: new Date().toISOString(),
      connection: { id: req.profile_id || '__default__', name: req.profile_id || 'GLPI' },
      report_title: req.report_title || 'Relatório Gerencial de Atividades',
      template: req.template || 'operational',
      period_start: req.period_start,
      period_end: req.period_end,
      subject: { type: 'user', id: parseInt(req.agent_id || 0, 10) || 0, name: cleanName(req.agent_name || 'Profissional não informado') },
      groups: selectedGroupsArray(),
      contexts: req.contexts || [],
      analytics_options: req.analytics || [],
      date_context: req.date_context || selectedDateContext(),
      data_context: dc,
      manager_context: { enabled: !!dc.manager_mode_enabled, selected_groups: selectedGroupsArray(), hide_detail_entries: !!dc.manager_mode_enabled },
      facts: { tickets: [], projects: [] },
      analytics: {},
      scope_text: req.scope_text || '',
      objective_text: req.objective_text || '',
      executive_summary: '',
      metrics: [],
      entries: [],
      detail_entries: [],
      validations: [],
      warnings: [],
      source_totals: {},
      source_returned: {},
      coverage: { sources: {}, filters: {}, limitations: [], warnings: [] }
    };
  }

  function createMergeState(req) {
    return {
      base: null,
      seenRaw: {},
      rawEntries: [],
      warnings: [],
      validations: [],
      sourceTotals: {},
      sourceReturned: {},
      coverage: { sources: {}, filters: {}, limitations: [], warnings: [] },
      ticketMap: {},
      projectMap: {},
      partCount: 0,
      managerMode: !!((req.data_context || {}).manager_mode_enabled)
    };
  }

  function mergeDraftPartIntoState(state, part) {
    if (!state || !part) return state;
    if (!state.base) state.base = Object.assign({}, part);
    state.partCount++;
    var managerMode = !!state.managerMode;

    // In manager/high-volume reports, individual editable entries are not used.
    // Do not keep raw detail evidence in browser memory; analytics are based on
    // canonical ticket facts and compact aggregate objects.
    if (!managerMode) {
      (part.entries || []).forEach(function (e) {
        var key = [e.source_type || '', e.source_itemtype || '', e.source_id || '', e.date || '', e.title || '', e.activity_performed || ''].join('|');
        if (state.seenRaw[key]) return;
        state.seenRaw[key] = true;
        state.rawEntries.push(Object.assign({}, e));
      });
    }

    (part.warnings || []).forEach(function (w) { if (state.warnings.indexOf(w) === -1) state.warnings.push(w); });
    (part.validations || []).forEach(function (v) { state.validations.push(v); });
    Object.keys(part.source_totals || {}).forEach(function (k) { state.sourceTotals[k] = Math.max(state.sourceTotals[k] || 0, parseInt(part.source_totals[k] || 0, 10) || 0); });
    Object.keys(part.source_returned || {}).forEach(function (k) { state.sourceReturned[k] = (state.sourceReturned[k] || 0) + (parseInt(part.source_returned[k] || 0, 10) || 0); });
    mergeCoverage(state.coverage, part.coverage || {});
    ((part.facts && part.facts.tickets) || []).forEach(function (t) { mergeTicketFact(state.ticketMap, t); });
    ((part.facts && part.facts.projects) || []).forEach(function (p) { mergeProjectFact(state.projectMap, p); });
    return state;
  }

  function finalizeMergeState(state, req) {
    state = state || createMergeState(req || {});
    var out = state.base ? Object.assign({}, state.base) : baseDraft(req || {});
    out.entries = [];
    out.detail_entries = [];
    out.raw_entries = [];
    out.facts = { tickets: [], projects: [] };
    out.contexts = req.contexts || [];
    out.analytics_options = req.analytics || [];
    out.date_context = req.date_context || selectedDateContext();
    out.data_context = req.data_context || collectDataContext();
    out.manager_context = { enabled: !!out.data_context.manager_mode_enabled, selected_groups: selectedGroupsArray(), hide_detail_entries: !!out.data_context.manager_mode_enabled };
    out.period_start = req.period_start;
    out.period_end = req.period_end;
    out.report_title = req.report_title || out.report_title;
    out.template = req.template || out.template;
    out.scope_text = req.scope_text || out.scope_text || '';
    out.objective_text = req.objective_text || out.objective_text || '';
    out.subject = { type: 'user', id: parseInt(req.agent_id || 0, 10) || 0, name: cleanName(req.agent_name || (out.subject && out.subject.name) || '') };
    out.groups = selectedGroupsArray();
    out.facts.tickets = Object.keys(state.ticketMap).map(function (k) { return state.ticketMap[k]; });
    out.facts.projects = Object.keys(state.projectMap).map(function (k) { return state.projectMap[k]; });
    out.raw_entries = state.managerMode ? [] : state.rawEntries;
    out.source_totals = state.sourceTotals;
    out.source_returned = state.sourceReturned;
    out.coverage = state.coverage;
    out.total_api_candidates = Object.keys(state.sourceTotals).reduce(function (n, k) { return n + (parseInt(state.sourceTotals[k] || 0, 10) || 0); }, 0);
    out.entries = state.managerMode ? [] : buildCanonicalEntries(out, state.rawEntries);
    out.detail_entries = out.data_context && out.data_context.manager_mode_enabled ? [] : out.entries.slice();

    var warnings = state.warnings.slice();
    var validations = state.validations.slice();
    if (out.entries.length > 0 || out.facts.tickets.length > 0) {
      validations = validations.filter(function (v) { return String((v && v.message) || '').indexOf('Nenhuma atividade foi retornada pela API') === -1; });
      warnings = warnings.filter(function (w) { return String(w || '').indexOf('Falha ao pesquisar atividades em ') === -1; });
    }
    out.warnings = warnings;
    out.validations = dedupeValidations(validations);
    applyDataContextFilters(out);
    var detailCount = detailEntries(out).length;
    if (out.data_context && out.data_context.manager_mode_enabled) {
      var ticketCount = ((out.facts && out.facts.tickets) || []).length;
      var groupNames = (out.groups || []).map(function (g) { return g.name || ('Grupo #' + g.id); }).join('; ');
      out.executive_summary = 'No período de ' + formatDate(out.period_start) + ' a ' + formatDate(out.period_end) + ', foram analisados ' + ticketCount + ' chamados vinculados aos grupos selecionados' + (groupNames ? ' (' + groupNames + ')' : '') + '. O detalhamento individual foi omitido no modo gerencial, priorizando indicadores por agente/equipe, status, categoria, localização, evolução diária e tempo de resposta quando disponível.';
    } else {
      out.executive_summary = 'No período de ' + formatDate(out.period_start) + ' a ' + formatDate(out.period_end) + ', foram consolidadas ' + detailCount + ' entradas detalhadas relacionadas a ' + ((out.subject && out.subject.name) || 'profissional selecionado') + '. O rascunho combina dados obtidos via API do GLPI com espaço para complementação narrativa.';
    }
    return out;
  }

  function mergeDraftParts(parts, req) {
    var out = parts.length ? Object.assign({}, parts[0]) : baseDraft(req);
    out.entries = [];
    out.detail_entries = [];
    out.raw_entries = [];
    out.facts = { tickets: [], projects: [] };
    out.contexts = req.contexts || [];
    out.analytics_options = req.analytics || [];
    out.date_context = req.date_context || selectedDateContext();
    out.data_context = req.data_context || collectDataContext();
    out.manager_context = { enabled: !!out.data_context.manager_mode_enabled, selected_groups: selectedGroupsArray(), hide_detail_entries: !!out.data_context.manager_mode_enabled };
    out.period_start = req.period_start;
    out.period_end = req.period_end;
    out.report_title = req.report_title || out.report_title;
    out.template = req.template || out.template;
    out.scope_text = req.scope_text || out.scope_text || '';
    out.objective_text = req.objective_text || out.objective_text || '';
    out.subject = { type: 'user', id: parseInt(req.agent_id || 0, 10) || 0, name: cleanName(req.agent_name || (out.subject && out.subject.name) || '') };
    out.groups = selectedGroupsArray();
    var seenRaw = {};
    var rawEntries = [];
    var warnings = [];
    var validations = [];
    var sourceTotals = {};
    var sourceReturned = {};
    var coverage = { sources: {}, filters: {}, limitations: [], warnings: [] };
    var ticketMap = {};
    var projectMap = {};
    parts.forEach(function (part) {
      (part.entries || []).forEach(function (e) {
        var key = [e.source_type || '', e.source_itemtype || '', e.source_id || '', e.date || '', e.title || '', e.activity_performed || ''].join('|');
        if (seenRaw[key]) return;
        seenRaw[key] = true;
        rawEntries.push(Object.assign({}, e));
      });
      (part.warnings || []).forEach(function (w) { if (warnings.indexOf(w) === -1) warnings.push(w); });
      (part.validations || []).forEach(function (v) { validations.push(v); });
      Object.keys(part.source_totals || {}).forEach(function (k) { sourceTotals[k] = Math.max(sourceTotals[k] || 0, parseInt(part.source_totals[k] || 0, 10) || 0); });
      Object.keys(part.source_returned || {}).forEach(function (k) { sourceReturned[k] = (sourceReturned[k] || 0) + (parseInt(part.source_returned[k] || 0, 10) || 0); });
      mergeCoverage(coverage, part.coverage || {});
      ((part.facts && part.facts.tickets) || []).forEach(function (t) { mergeTicketFact(ticketMap, t); });
      ((part.facts && part.facts.projects) || []).forEach(function (p) { mergeProjectFact(projectMap, p); });
    });
    out.facts.tickets = Object.keys(ticketMap).map(function (k) { return ticketMap[k]; });
    out.facts.projects = Object.keys(projectMap).map(function (k) { return projectMap[k]; });
    out.raw_entries = rawEntries;
    out.source_totals = sourceTotals;
    out.source_returned = sourceReturned;
    out.coverage = coverage;
    out.total_api_candidates = Object.keys(sourceTotals).reduce(function (n, k) { return n + (parseInt(sourceTotals[k] || 0, 10) || 0); }, 0);

    // Canonical pipeline: raw API rows and partial batch entries are evidence only.
    // Render and analytics use one final ticket fact per ticket_id. This prevents
    // the same ticket found by user + group + date contexts from becoming multiple
    // editable cards or inconsistent totals.
    out.entries = buildCanonicalEntries(out, rawEntries);
    out.detail_entries = out.data_context && out.data_context.manager_mode_enabled ? [] : out.entries.slice();

    if (out.entries.length > 0 || out.facts.tickets.length > 0) {
      validations = validations.filter(function (v) {
        var msg = String((v && v.message) || '');
        return msg.indexOf('Nenhuma atividade foi retornada pela API') === -1;
      });
      warnings = warnings.filter(function (w) {
        var msg = String(w || '');
        return msg.indexOf('Falha ao pesquisar atividades em ') === -1;
      });
    }
    out.warnings = warnings;
    out.validations = dedupeValidations(validations);
    applyDataContextFilters(out);
    var detailCount = detailEntries(out).length;
    if (out.data_context && out.data_context.manager_mode_enabled) {
      var ticketCount = ((out.facts && out.facts.tickets) || []).length;
      var groupNames = (out.groups || []).map(function (g) { return g.name || ('Grupo #' + g.id); }).join('; ');
      out.executive_summary = 'No período de ' + formatDate(out.period_start) + ' a ' + formatDate(out.period_end) + ', foram analisados ' + ticketCount + ' chamados vinculados aos grupos selecionados' + (groupNames ? ' (' + groupNames + ')' : '') + '. O detalhamento individual foi omitido no modo gerencial, priorizando indicadores por agente/equipe, status, categoria, localização, evolução diária e tempo de resposta quando disponível.';
    } else {
      var mainCount = detailCount + ' entradas detalhadas';
      out.executive_summary = 'No período de ' + formatDate(out.period_start) + ' a ' + formatDate(out.period_end) + ', foram consolidadas ' + mainCount + ' relacionadas a ' + ((out.subject && out.subject.name) || 'profissional selecionado') + '. O rascunho combina dados obtidos via API do GLPI com espaço para complementação narrativa.';
    }
    return out;
  }

  function mergeTicketFact(map, fact) {
    if (!fact || !fact.ticket_id) return;
    var id = String(fact.ticket_id);
    if (!map[id]) {
      map[id] = Object.assign({}, fact);
      map[id].events = (fact.events || []).slice();
      map[id].source_contexts = (fact.source_contexts || []).slice();
      map[id].primary_source = primarySource(map[id], true);
      return;
    }
    var tgt = map[id];
    ['title','status','category','opened_at','updated_at','solved_at','closed_at','requester_name','location_label','primary_source'].forEach(function (k) {
      if (!tgt[k] && fact[k]) tgt[k] = fact[k];
    });
    if (!tgt.requester_id && fact.requester_id) tgt.requester_id = fact.requester_id;
    ['assigned_users','assigned_groups'].forEach(function (k) {
      if (!Array.isArray(tgt[k])) tgt[k] = [];
      var seen = {};
      tgt[k].forEach(function (x) { seen[String((x && (x.id || x.name)) || '')] = true; });
      (fact[k] || []).forEach(function (x) {
        var key = String((x && (x.id || x.name)) || '');
        if (key && !seen[key]) { tgt[k].push(x); seen[key] = true; }
      });
    });
    (fact.source_contexts || []).forEach(function (c) { if (tgt.source_contexts.indexOf(c) === -1) tgt.source_contexts.push(c); });
    tgt.primary_source = primarySource(tgt, true);
    var seenEvents = {};
    (tgt.events || []).forEach(function (e) { seenEvents[[e.source_itemtype,e.source_id,e.datetime,e.event_type].join('|')] = true; });
    (fact.events || []).forEach(function (e) {
      var key = [e.source_itemtype,e.source_id,e.datetime,e.event_type].join('|');
      if (!seenEvents[key]) { tgt.events.push(e); seenEvents[key] = true; }
    });
  }


  function mergeCoverage(target, source) {
    target = target || { sources: {}, filters: {}, limitations: [], warnings: [] };
    source = source || {};
    Object.keys(source.sources || {}).forEach(function (k) {
      var cur = target.sources[k] || { api_total: 0, rows_loaded: 0, unique_added: 0, duplicates: 0, completed: false };
      var src = source.sources[k] || {};
      cur.api_total = Math.max(parseInt(cur.api_total || 0, 10) || 0, parseInt(src.api_total || 0, 10) || 0);
      cur.rows_loaded += parseInt(src.rows_loaded || 0, 10) || 0;
      cur.unique_added += parseInt(src.unique_added || 0, 10) || 0;
      cur.duplicates += parseInt(src.duplicates || 0, 10) || 0;
      cur.completed = !!(cur.completed || src.completed);
      target.sources[k] = cur;
    });
    Object.keys(source.filters || {}).forEach(function (k) { target.filters[k] = (target.filters[k] || 0) + (parseInt(source.filters[k] || 0, 10) || 0); });
    ['limitations','warnings'].forEach(function (key) {
      (source[key] || []).forEach(function (msg) {
        if (target[key].indexOf(msg) === -1) target[key].push(msg);
      });
    });
    return target;
  }

  function mergeProjectFact(map, fact) {
    if (!fact || !fact.project_id) return;
    var id = String(fact.project_id);
    if (!map[id]) {
      map[id] = Object.assign({}, fact);
      map[id].source_contexts = (fact.source_contexts || []).slice();
      return;
    }
    var tgt = map[id];
    ['title','status','percent_done','updated_at'].forEach(function (k) { if (!tgt[k] && fact[k]) tgt[k] = fact[k]; });
    (fact.source_contexts || []).forEach(function (c) { if (tgt.source_contexts.indexOf(c) === -1) tgt.source_contexts.push(c); });
  }

  function dedupeValidations(items) {
    var seen = {}, out = [];
    items.forEach(function (v) {
      var key = (v.level || '') + '|' + (v.message || '');
      if (seen[key]) return;
      seen[key] = true;
      out.push(v);
    });
    return out;
  }

  function buildCanonicalEntries(d, rawEntries) {
    rawEntries = rawEntries || [];
    var tickets = ((d.facts || {}).tickets) || [];
    var byTicket = {};
    var nonTicket = [];
    rawEntries.forEach(function (entry) {
      if (isTicketEntry(entry)) {
        var tid = ticketIdFromEntry(entry);
        if (!tid) return;
        if (!byTicket[tid]) byTicket[tid] = [];
        byTicket[tid].push(entry);
      } else {
        nonTicket.push(entry);
      }
    });

    var out = [];
    var rawTicketEntryCount = Object.keys(byTicket).reduce(function (n, k) { return n + byTicket[k].length; }, 0);
    var duplicateCount = Math.max(0, rawTicketEntryCount - tickets.length);
    if (duplicateCount > 0) addCoverageFilter(d.coverage, 'duplicate_merged', duplicateCount);

    var factIds = {};
    tickets.forEach(function (ticket) {
      if (!ticket || !ticket.ticket_id) return;
      factIds[String(ticket.ticket_id)] = true;
      var candidates = byTicket[String(ticket.ticket_id)] || [];
      var chosen = chooseBestTicketEntry(candidates);
      var date = displayDateForTicket(ticket, chosen, d);
      if (!date || !withinPeriod(date, d)) {
        addCoverageFilter(d.coverage, 'out_of_period_detail_suppressed', 1);
        return;
      }
      var entry = canonicalTicketEntry(ticket, chosen, date);
      out.push(entry);
    });

    // Defensive fallback: if a partial batch returned a ticket entry but the fact
    // object was not available because the request hit a safety budget, keep a
    // single canonical card instead of silently dropping the ticket.
    Object.keys(byTicket).forEach(function (tid) {
      if (factIds[tid]) return;
      var chosen = chooseBestTicketEntry(byTicket[tid]);
      if (!chosen) return;
      var pseudoFact = {
        ticket_id: parseInt(tid, 10) || 0,
        title: entryTitleForMatch(chosen),
        status: chosen.status || '',
        category: chosen.category || '',
        opened_at: chosen.date || '',
        updated_at: chosen.date || '',
        solved_at: '',
        closed_at: '',
        source_contexts: chosen.source_contexts || [],
        primary_source: primarySource(chosen, false)
      };
      var date = displayDateForTicket(pseudoFact, chosen, d);
      if (!date || !withinPeriod(date, d)) {
        addCoverageFilter(d.coverage, 'out_of_period_detail_suppressed', 1);
        return;
      }
      out.push(canonicalTicketEntry(pseudoFact, chosen, date));
    });

    var seen = {};
    nonTicket.forEach(function (entry) {
      if (!entry || entry.include_in_report === false) return;
      if (isProjectEntry(entry) && !withinPeriod(entry.date, d)) {
        addCoverageFilter(d.coverage, 'out_of_period_detail_suppressed', 1);
        return;
      }
      var key = [entry.source_type || '', entry.source_itemtype || '', entry.source_id || '', entry.date || '', entry.title || '', entry.activity_performed || ''].join('|');
      if (seen[key]) return;
      seen[key] = true;
      out.push(Object.assign({}, entry));
    });

    out.sort(function (a, b) { return String(a.date || '').localeCompare(String(b.date || '')) || String(a.title || '').localeCompare(String(b.title || '')); });
    return out;
  }

  function chooseBestTicketEntry(candidates) {
    if (!candidates || !candidates.length) return null;
    var priority = { ticket_solution: 80, ticket_followup: 70, ticket_task: 60, ticket: 50 };
    return candidates.slice().sort(function (a, b) {
      var sa = (priority[a.source_type] || 10) + Math.min(30, String(a.activity_performed || '').length / 100);
      var sb = (priority[b.source_type] || 10) + Math.min(30, String(b.activity_performed || '').length / 100);
      if (sb !== sa) return sb - sa;
      return String(b.date || '').localeCompare(String(a.date || ''));
    })[0];
  }

  function canonicalTicketEntry(ticket, chosen, date) {
    chosen = chosen || {};
    var id = ticket.ticket_id || chosen.ticket_id || chosen.source_id || 0;
    var title = ticket.title || chosen.ticket_title || entryTitleForMatch(chosen) || ('Chamado ' + id);
    return {
      date: date,
      source_type: 'ticket',
      source_itemtype: 'Ticket',
      source_id: parseInt(id || 0, 10) || 0,
      source_reference: chosen.source_reference || ('Chamado ' + id),
      ticket_id: parseInt(id || 0, 10) || 0,
      ticket_title: title,
      professional: chosen.professional || '',
      title: 'Chamado ' + id + ' - ' + title,
      status: ticket.status || chosen.status || '',
      category: ticket.category || chosen.category || '',
      activity_performed: chosen.activity_performed || 'Registro localizado no GLPI dentro do contexto selecionado. Complementar a narrativa técnica, se necessário.',
      result: chosen.result || resultFromStatus(ticket.status || chosen.status || ''),
      next_steps: chosen.next_steps || '',
      observations: chosen.observations || 'Origem: chamado vinculado ao contexto selecionado.',
      include_in_report: chosen.include_in_report !== false,
      source_contexts: (ticket.source_contexts || chosen.source_contexts || []).slice()
    };
  }

  function displayDateForTicket(ticket, chosen, d) {
    var ctx = (d && d.date_context) || {};
    var candidates = [];
    if (ctx.created_with_agent_action) candidates.push(ticket.opened_at);
    if (ctx.solved_or_closed_by_agent || ctx.solved_or_closed_in_period) {
      candidates.push(ticket.solved_at);
      candidates.push(ticket.closed_at);
    }
    if (ctx.updated_in_period) candidates.push(ticket.updated_at);
    if (chosen && chosen.date) candidates.push(chosen.date);
    candidates.push(ticket.opened_at, ticket.updated_at, ticket.solved_at, ticket.closed_at);
    for (var i = 0; i < candidates.length; i++) {
      var day = datePart(candidates[i]);
      if (day && withinPeriod(day, d)) return day;
    }
    return '';
  }

  function withinPeriod(date, d) {
    var day = datePart(date);
    if (!day) return false;
    var start = datePart(d && d.period_start);
    var end = datePart(d && d.period_end);
    if (!start || !end) return true;
    return day >= start && day <= end;
  }

  function isProjectEntry(entry) {
    if (!entry) return false;
    return /^project/.test(String(entry.source_type || '')) || /^(Project|ProjectTask)$/i.test(String(entry.source_itemtype || ''));
  }

  function addCoverageFilter(cov, key, count) {
    if (!cov) return;
    if (!cov.filters) cov.filters = {};
    cov.filters[key] = (parseInt(cov.filters[key] || 0, 10) || 0) + (parseInt(count || 1, 10) || 1);
  }

  function resultFromStatus(status) {
    status = String(status || '').toLowerCase();
    if (/fech|soluc|conclu|closed|solved|done/.test(status)) return 'Atividade registrada como concluída/solucionada no GLPI.';
    if (/pend|andamento|novo|process/.test(status)) return 'Atividade em acompanhamento, com continuidade conforme tratamento registrado.';
    return '';
  }



  function applyDataContextFilters(d) {
    var dc = d.data_context || {};
    var filters = normalizeTitleFilters(dc.ticket_titles || []);
    var titleFilter = !!dc.ticket_title_filter_enabled && filters.length > 0;
    var managerMode = !!dc.manager_mode_enabled;
    if (managerMode && titleFilter) dc.ticket_title_filter_scope = 'global';
    dc.effective_ticket_title_filter_scope = titleFilter ? (dc.ticket_title_filter_scope || 'detail_only') : 'none';
    d.data_context = dc;
    d.manager_context = d.manager_context || { enabled: managerMode };
    d.manager_context.enabled = managerMode;
    d.manager_context.hide_detail_entries = managerMode;
    d.manager_context.selected_groups = selectedGroupsArray();

    var allEntries = d.entries || [];
    var allTickets = ((d.facts && d.facts.tickets) || []);
    var ticketMap = {};
    allTickets.forEach(function (t) { if (t && t.ticket_id) ticketMap[String(t.ticket_id)] = t; });

    if (titleFilter && dc.effective_ticket_title_filter_scope === 'global') {
      var keptTicketIds = {};
      allTickets = allTickets.filter(function (t) {
        var ok = ticketMatchesTitle(t, filters);
        if (ok && t.ticket_id) keptTicketIds[String(t.ticket_id)] = true;
        return ok;
      });
      if (d.facts) d.facts.tickets = allTickets;
      d.entries = allEntries.filter(function (e) { return entryMatchesTitle(e, ticketMap, filters, keptTicketIds); });
      d.detail_entries = managerMode ? [] : d.entries.slice();
      d.validations = (d.validations || []).concat([{ level: 'info', message: 'Filtro global por título aplicado a indicadores, gráficos e detalhamento. Títulos: ' + filters.map(function (f) { return f.title; }).join('; ') }]);
    } else if (titleFilter) {
      d.detail_entries = allEntries.filter(function (e) { return entryMatchesTitle(e, ticketMap, filters); });
      d.validations = (d.validations || []).concat([{ level: 'info', message: 'Filtro por título aplicado somente ao detalhamento editável e corpo do DOCX. Indicadores e gráficos mantêm o escopo completo.' }]);
    } else {
      d.detail_entries = managerMode ? [] : allEntries.slice();
    }

    if (managerMode) {
      d.detail_entries = [];
      d.validations = (d.validations || []).concat([{ level: 'info', message: 'Modo gerencial ativo: o detalhamento individual de chamados foi omitido para priorizar indicadores por agente/grupo e evitar documentos excessivamente extensos.' }]);
    }
  }

  function normalizeTitleFilters(items) {
    var out = [];
    (items || []).forEach(function (item) {
      var title = String((item && item.title) || '').trim();
      if (!title) return;
      var ticketId = String((item && item.ticket_id) || '').trim();
      addTitleToArray(out, { title: title, ticket_id: ticketId });
    });
    return out;
  }

  function ticketMatchesTitle(ticket, filters) {
    var title = normalizeForMatch((ticket && ticket.title) || '');
    if (!title) return false;
    return (filters || []).some(function (f) {
      var ft = normalizeForMatch(f.title || '');
      return ft && (title.indexOf(ft) !== -1 || ft.indexOf(title) !== -1);
    });
  }

  function ticketIdFromEntry(entry) {
    if (!entry) return '';
    if (entry.ticket_id) return String(entry.ticket_id);
    if ((entry.source_itemtype || '') === 'Ticket' && entry.source_id) return String(entry.source_id);
    var ref = String(entry.source_reference || entry.title || '');
    var m = ref.match(/Chamado\s+(\d+)/i);
    return m ? m[1] : '';
  }

  function isTicketEntry(entry) {
    if (!entry) return false;
    if (entry.ticket_id) return true;
    var st = String(entry.source_type || '');
    var it = String(entry.source_itemtype || '');
    if (/^ticket/.test(st)) return true;
    if (/^(Ticket|TicketTask|ITILFollowup|ITILSolution)$/i.test(it)) return true;
    return /Chamado\s+\d+/i.test(String(entry.source_reference || entry.title || ''));
  }

  function entryTitleForMatch(entry) {
    if (!entry) return '';
    if (entry.ticket_title) return String(entry.ticket_title);
    var title = String(entry.title || '');
    // Keep the real ticket title, not the entry prefix. Examples:
    // "Chamado 123 - Título", "Solução do chamado 123 - Título".
    title = title.replace(/^Chamado\s+\d+\s*[-–]\s*/i, '');
    title = title.replace(/^(Tarefa|Acompanhamento|Solução)\s+do\s+chamado\s+\d+\s*[-–]\s*/i, '');
    return title;
  }

  function entryMatchesTitle(entry, ticketMap, filters, keptTicketIds) {
    if (!isTicketEntry(entry)) return true;
    var tid = ticketIdFromEntry(entry);
    if (keptTicketIds && tid) return !!keptTicketIds[tid];
    if (tid && ticketMap && ticketMap[tid]) return ticketMatchesTitle(ticketMap[tid], filters);
    var hay = normalizeForMatch(entryTitleForMatch(entry));
    if (!hay) return false;
    return (filters || []).some(function (f) {
      var ft = normalizeForMatch(f.title || '');
      return ft && (hay.indexOf(ft) !== -1 || ft.indexOf(hay) !== -1);
    });
  }

  function computeMetrics(d) {
    var dc = d.data_context || {};
    var managerMode = !!dc.manager_mode_enabled;
    var entries = analyticsEntries(d);
    var facts = (d.facts && d.facts.tickets) || [];
    var total = managerMode ? facts.length : entries.length;
    var tickets = managerMode ? facts.length : 0;
    var projects = managerMode ? ((d.facts && d.facts.projects) || []).length : 0;
    var closed = 0;
    if (managerMode) {
      facts.forEach(function (t) { if (/fech|soluc|conclu|closed|solved|done/.test(String(t.status || '').toLowerCase())) closed++; });
    } else {
      entries.forEach(function (e) {
        var stype = String(e.source_type || '');
        if (stype.indexOf('ticket') === 0) tickets++;
        if (stype.indexOf('project') === 0) projects++;
        var status = String(e.status || '').toLowerCase();
        if (/fech|soluc|conclu|closed|solved|done/.test(status)) closed++;
      });
    }
    var rate = total ? Math.round((closed / total) * 1000) / 10 : 0;
    var metrics = managerMode ? [
      { key: 'tickets_scope', label: 'Chamados no escopo', value: facts.length },
      { key: 'closed_tickets', label: 'Chamados concluídos/solucionados', value: closed },
      { key: 'agents_identified', label: 'Agentes com atividade detectada', value: managerAgentCount(d) },
      { key: 'closed_rate', label: 'Taxa de encerramento/conclusão', value: rate + '%' }
    ] : [
      { key: 'entries', label: 'Entradas carregadas', value: total },
      { key: 'tickets', label: 'Entradas de chamados', value: tickets },
      { key: 'projects', label: 'Entradas de projetos', value: projects },
      { key: 'closed_rate', label: 'Taxa de encerramento/conclusão', value: rate + '%' }
    ];
    var rt = d.analytics && d.analytics.response_time;
    if (rt && rt.avg_minutes != null && isFinite(rt.avg_minutes)) {
      metrics.push({ key: 'avg_response', label: 'Tempo médio de resposta', value: durationLabel(rt.avg_minutes) });
    }
    if (d.total_api_candidates && d.total_api_candidates > total) {
      metrics.unshift({ key: 'api_total', label: 'Registros encontrados na API', value: d.total_api_candidates });
    }
    return metrics;
  }

  function computeAnalytics(d) {
    var options = d.analytics_options || selectedAnalytics();
    var enabled = {};
    options.forEach(function (o) { enabled[o] = true; });
    var entries = analyticsEntries(d);
    var facts = d.facts || { tickets: [], projects: [] };
    var managerMode = !!(d.data_context && d.data_context.manager_mode_enabled);
    var out = { enabled: options, generated_at: new Date().toISOString() };
    var ticketFacts = facts.tickets || [];
    if (managerMode) {
      if (enabled.daily_volume || enabled.daily_heatmap) out.daily_volume = countBy(ticketFacts, function (t) { return datePart(t.closed_at || t.solved_at || t.updated_at || t.opened_at) || 'Sem data'; });
      if (enabled.daily_heatmap) out.daily_heatmap = out.daily_volume || {};
      if (enabled.status_distribution) out.status_distribution = countBy(ticketFacts, function (t) { return t.status || 'Sem status'; });
      if (enabled.source_distribution) out.source_distribution = countBy(ticketFacts, function (t) { return sourceLabel(primarySource(t, true)); });
      if (enabled.top_categories) out.top_categories = topMap(countBy(ticketFacts, function (t) { return t.category || 'Sem categoria'; }), 10);
      if (enabled.location_distribution) out.location_distribution = topMap(countBy(ticketFacts, function (t) { return t.location_label || 'Local não informado'; }), 10);
      if (enabled.agent_vs_group) out.agent_vs_group = agentVsGroup(entries, facts);
    } else {
      if (enabled.daily_volume || enabled.daily_heatmap) out.daily_volume = countBy(entries, function (e) { return e.date || 'Sem data'; });
      if (enabled.daily_heatmap) out.daily_heatmap = out.daily_volume || countBy(entries, function (e) { return e.date || 'Sem data'; });
      if (enabled.status_distribution) out.status_distribution = countBy(entries, function (e) { return e.status || 'Sem status'; });
      if (enabled.source_distribution) out.source_distribution = countBy(entries, function (e) { return sourceLabel(e.source_type || 'manual'); });
      if (enabled.top_categories) out.top_categories = topMap(countBy(entries, function (e) { return e.category || 'Sem categoria'; }), 10);
      if (enabled.location_distribution) out.location_distribution = topMap(countBy(ticketFacts, function (t) { return t.location_label || 'Local não informado'; }), 10);
      if (enabled.agent_vs_group) out.agent_vs_group = agentVsGroup(entries, facts);
    }
    if (enabled.avg_response_time || enabled.first_response_time || enabled.unanswered_events || managerMode) out.response_time = responseTimeAnalytics(ticketFacts);
    if (managerMode) out.manager_summary = managerSummaryAnalytics(d, out.response_time || {});
    if (enabled.consolidated_table) out.consolidated_table = consolidatedAnalytics(entries, facts, out, managerMode);
    return out;
  }


  function analyticsEntries(d) {
    if (!d) return [];
    var dc = d.data_context || {};
    if (dc.manager_mode_enabled) return (d.entries || []).filter(function (e) { return e.include_in_report !== false; });
    if (dc.ticket_title_filter_enabled && dc.effective_ticket_title_filter_scope === 'detail_only') {
      return (d.entries || []).filter(function (e) { return e.include_in_report !== false; });
    }
    return (d.entries || []).filter(function (e) { return e.include_in_report !== false; });
  }

  function detailEntries(d) {
    if (!d) return [];
    if (d.data_context && d.data_context.manager_mode_enabled) return [];
    var arr = Array.isArray(d.detail_entries) ? d.detail_entries : (d.entries || []);
    return arr.filter(function (e) { return e && e.include_in_report !== false; });
  }

  function datePart(value) {
    return value ? String(value).slice(0, 10) : '';
  }

  function managerAgentCount(d) {
    var summary = d.analytics && d.analytics.manager_summary;
    if (summary && summary.by_agent) return summary.by_agent.length;
    var seen = {};
    (((d.facts || {}).tickets) || []).forEach(function (ticket) {
      (ticket.events || []).forEach(function (ev) {
        if (isTechnicalResponse(ev)) seen[String(ev.actor_user_id || ev.actor_name || '0')] = true;
      });
    });
    return Object.keys(seen).filter(function (k) { return k !== '0' && k !== ''; }).length;
  }

  function countBy(items, keyFn) {
    var out = {};
    (items || []).forEach(function (item) {
      var key = String(keyFn(item) || 'N/I');
      out[key] = (out[key] || 0) + 1;
    });
    return out;
  }

  function topMap(map, limit, preserveOrder) {
    var rows = Object.keys(map || {}).map(function (k) { return { label: k, value: map[k] }; });
    if (preserveOrder) rows.sort(function (a, b) { return a.label.localeCompare(b.label); });
    else rows.sort(function (a, b) { return b.value - a.value || a.label.localeCompare(b.label); });
    if (limit && rows.length > limit) {
      var top = rows.slice(0, limit);
      var other = rows.slice(limit).reduce(function (n, r) { return n + r.value; }, 0);
      if (other > 0) top.push({ label: 'Outros', value: other });
      return top;
    }
    return rows;
  }


  function primarySource(item, managerMode) {
    var ctx = (item && item.source_contexts) || [];
    function has(re) { return ctx.some(function (c) { return re.test(c); }); }
    if (managerMode) {
      if (has(/tickets_assigned_groups/)) return 'tickets_assigned_groups';
      if (has(/ticket_solutions_by_user/)) return 'ticket_solutions_by_user';
      if (has(/ticket_tasks_by_user/)) return 'ticket_tasks_by_user';
      if (has(/ticket_followups_by_user/)) return 'ticket_followups_by_user';
      if (has(/tickets_assigned_user/)) return 'tickets_assigned_user';
      if (has(/tickets_requested_by_user/)) return 'tickets_requested_by_user';
    }
    return (item && (item.primary_source || (ctx[0] || 'Chamado'))) || 'Chamado';
  }

  function sourceLabel(src) {
    src = String(src || '');
    var base = src.split(':')[0];
    return {
      ticket: 'Chamado', ticket_task: 'Tarefa de chamado', ticket_followup: 'Acompanhamento', ticket_solution: 'Solução', project: 'Projeto', project_task: 'Tarefa de projeto', manual: 'Manual',
      tickets_assigned_user: 'Chamados do agente', tickets_requested_by_user: 'Chamados solicitados pelo agente', tickets_assigned_groups: 'Chamados atualmente atribuídos aos grupos selecionados',
      ticket_tasks_by_user: 'Tarefas do agente', ticket_followups_by_user: 'Acompanhamentos do agente', ticket_solutions_by_user: 'Soluções do agente',
      projects_managed_by_user: 'Projetos do agente', projects_managed_by_groups: 'Projetos dos grupos selecionados', project_tasks_groups: 'Tarefas de projeto dos grupos selecionados'
    }[base] || src;
  }

  function agentReference(row) {
    row = row || {};
    var ref = cleanName(row.agent_login || row.login || row.actor_login || row.reference || row.actor_reference || '');
    if (!ref && (row.agent_id || row.id || row.actor_user_id)) ref = '#' + String(row.agent_id || row.id || row.actor_user_id);
    if (ref && row.agent_id && ref.indexOf('#') !== 0) ref = '#' + row.agent_id + ' · ' + ref;
    return ref || 'N/D';
  }

  function displayAgentName(name, id) {
    name = cleanName(name || '');
    if (!name || /^#?\d+$/.test(name) || /^Usu[aá]rio #?\d+$/i.test(name)) {
      return id ? ('Usuário #' + id) : 'Técnico não identificado';
    }
    return name;
  }

  function agentVsGroup(entries, facts) {
    var out = { agent: 0, selected_groups: 0, mixed: 0, other: 0 };
    var rows = (entries && entries.length) ? entries : (((facts || {}).tickets) || []);
    rows.forEach(function (item) {
      var ctx = item.source_contexts || [];
      var hasAgent = ctx.some(function (c) { return /tickets_assigned_user|tickets_requested_by_user|by_user|_user/.test(c); });
      var hasGroup = ctx.some(function (c) { return /tickets_assigned_groups|projects_managed_by_groups|project_tasks_groups|groups/.test(c); });
      if (hasAgent && hasGroup) out.mixed++;
      else if (hasAgent) out.agent++;
      else if (hasGroup) out.selected_groups++;
      else out.other++;
    });
    return out;
  }

  function responseTimeAnalytics(tickets) {
    var intervals = [];
    var firstIntervals = [];
    var byDay = {};
    var byAgent = {};
    var unanswered = 0;
    var ticketsWithTimeline = 0;
    (tickets || []).forEach(function (ticket) {
      var events = (ticket.events || []).slice().sort(function (a, b) { return String(a.datetime || '').localeCompare(String(b.datetime || '')); });
      if (events.length) ticketsWithTimeline++;
      var waitingSince = null;
      var firstWaiting = null;
      var firstDone = false;
      events.forEach(function (ev) {
        var ts = parseTimestamp(ev.datetime);
        if (!ts) return;
        if (isRequesterEvent(ev)) {
          if (!waitingSince) waitingSince = ts;
          if (!firstWaiting) firstWaiting = ts;
        } else if (isTechnicalResponse(ev)) {
          if (waitingSince) {
            var minutes = Math.max(0, (ts - waitingSince) / 60000);
            intervals.push(minutes);
            var dayKey = new Date(ts).toISOString().slice(0, 10);
            if (!byDay[dayKey]) byDay[dayKey] = [];
            byDay[dayKey].push(minutes);
            var akey = String(ev.actor_user_id || ev.actor_name || 'Técnico não identificado');
            if (!byAgent[akey]) byAgent[akey] = { agent_id: ev.actor_user_id || 0, agent_name: displayAgentName(ev.actor_name, ev.actor_user_id || 0), agent_login: ev.actor_login || '', intervals: [], first_intervals: [], technical_events: 0 };
            byAgent[akey].intervals.push(minutes);
            byAgent[akey].technical_events++;
            if (!firstDone && firstWaiting) {
              var firstMinutes = Math.max(0, (ts - firstWaiting) / 60000);
              firstIntervals.push(firstMinutes);
              byAgent[akey].first_intervals.push(firstMinutes);
              firstDone = true;
            }
            waitingSince = null;
          } else {
            var key = String(ev.actor_user_id || ev.actor_name || 'Técnico não identificado');
            if (!byAgent[key]) byAgent[key] = { agent_id: ev.actor_user_id || 0, agent_name: displayAgentName(ev.actor_name, ev.actor_user_id || 0), agent_login: ev.actor_login || '', intervals: [], first_intervals: [], technical_events: 0 };
            byAgent[key].technical_events++;
          }
        }
      });
      if (waitingSince) unanswered++;
    });
    intervals.sort(function (a, b) { return a - b; });
    firstIntervals.sort(function (a, b) { return a - b; });
    var byAgentRows = Object.keys(byAgent).map(function (key) {
      var row = byAgent[key];
      row.intervals.sort(function (a, b) { return a - b; });
      row.first_intervals.sort(function (a, b) { return a - b; });
      return {
        agent_id: row.agent_id || 0,
        agent_name: displayAgentName(row.agent_name, row.agent_id || 0),
        agent_login: row.agent_login || '',
        intervals_count: row.intervals.length,
        technical_events: row.technical_events || 0,
        avg_minutes: average(row.intervals),
        median_minutes: percentile(row.intervals, 50),
        p90_minutes: percentile(row.intervals, 90),
        first_avg_minutes: average(row.first_intervals),
        first_count: row.first_intervals.length
      };
    }).sort(function (a, b) { return (b.technical_events || 0) - (a.technical_events || 0) || String(a.agent_name).localeCompare(String(b.agent_name)); });
    return {
      intervals_count: intervals.length,
      tickets_with_timeline: ticketsWithTimeline,
      avg_minutes: average(intervals),
      median_minutes: percentile(intervals, 50),
      p90_minutes: percentile(intervals, 90),
      max_minutes: intervals.length ? intervals[intervals.length - 1] : null,
      first_avg_minutes: average(firstIntervals),
      first_median_minutes: percentile(firstIntervals, 50),
      first_count: firstIntervals.length,
      requester_events_unanswered: unanswered,
      by_day: averageMap(byDay),
      by_agent: byAgentRows
    };
  }



  function managerSummaryAnalytics(d, rt) {
    var tickets = ((d.facts || {}).tickets) || [];
    var byAgent = {};
    function ensureAgent(id, name, login) {
      id = parseInt(id || 0, 10) || 0;
      name = displayAgentName(name, id);
      login = cleanName(login || '');
      var key = String(id || name || '0');
      if (!byAgent[key]) byAgent[key] = { agent_id: id, agent_name: name, agent_login: login, tickets: {}, tickets_touched: 0, tickets_closed: 0, technical_events: 0, response: null, attribution: '' };
      if (login && !byAgent[key].agent_login) byAgent[key].agent_login = login;
      if (name && byAgent[key].agent_name !== name && byAgent[key].agent_name.indexOf('Usuário #') === 0) byAgent[key].agent_name = name;
      return byAgent[key];
    }
    function addTicketTo(row, ticket) {
      if (!row || !ticket) return;
      var tid = String(ticket.ticket_id || '');
      if (!tid || row.tickets[tid]) return;
      row.tickets[tid] = true;
      row.tickets_touched++;
      if (/fech|soluc|conclu|closed|solved|done/.test(String(ticket.status || '').toLowerCase())) row.tickets_closed++;
    }

    tickets.forEach(function (ticket) {
      var touched = {};
      (ticket.events || []).forEach(function (ev) {
        if (!isTechnicalResponse(ev)) return;
        var id = ev.actor_user_id || 0;
        var name = ev.actor_name || (id ? ('Usuário #' + id) : 'Técnico não identificado');
        var row = ensureAgent(id, name, ev.actor_login || '');
        row.technical_events++;
        row.attribution = row.attribution || 'eventos técnicos';
        touched[String(id || name)] = row;
      });
      Object.keys(touched).forEach(function (k) { addTicketTo(touched[k], ticket); });

      if (!Object.keys(touched).length) {
        var assigned = Array.isArray(ticket.assigned_users) ? ticket.assigned_users : [];
        if (assigned.length) {
          assigned.forEach(function (u) {
            var row = ensureAgent(parseInt(u.id || 0, 10) || 0, u.name || (u.id ? ('Usuário #' + u.id) : 'Técnico não identificado'), u.login || '');
            row.attribution = row.attribution || 'técnico atribuído';
            addTicketTo(row, ticket);
          });
        } else {
          var unknown = ensureAgent(0, 'Técnico não identificado');
          unknown.attribution = unknown.attribution || 'sem autoria/atribuição detectável';
          addTicketTo(unknown, ticket);
        }
      }
    });
    var rtByAgent = {};
    ((rt && rt.by_agent) || []).forEach(function (r) {
      rtByAgent[String(r.agent_id || r.agent_name || '0')] = r;
      if (r.agent_login) rtByAgent[String(r.agent_login)] = r;
    });
    var rows = Object.keys(byAgent).map(function (key) {
      var row = byAgent[key];
      var rr = rtByAgent[String(row.agent_id || row.agent_name || '0')] || (row.agent_login ? rtByAgent[String(row.agent_login)] : null) || {};
      return {
        agent_id: row.agent_id,
        agent_name: displayAgentName(row.agent_name, row.agent_id),
        agent_login: row.agent_login || '',
        tickets_touched: row.tickets_touched,
        tickets_closed: row.tickets_closed,
        technical_events: row.technical_events,
        avg_response_minutes: rr.avg_minutes,
        median_response_minutes: rr.median_minutes,
        p90_response_minutes: rr.p90_minutes,
        first_response_avg_minutes: rr.first_avg_minutes,
        response_intervals: rr.intervals_count || 0,
        attribution: row.attribution || ''
      };
    }).sort(function (a, b) { return (b.tickets_touched || 0) - (a.tickets_touched || 0) || String(a.agent_name).localeCompare(String(b.agent_name)); });
    return { by_agent: rows, total_agents: rows.filter(function (r) { return r.agent_name !== 'Técnico não identificado' || r.tickets_touched > 0; }).length };
  }

  function isRequesterEvent(ev) {
    return ev.event_type === 'ticket_opened' || ev.event_type === 'requester_followup' || ev.actor_role === 'requester';
  }

  function isTechnicalResponse(ev) {
    if (!ev) return false;
    if (ev.is_technical === true || ev.is_technical === 1 || ev.is_technical === '1') return true;
    return ev.actor_role === 'selected_agent' || ev.actor_role === 'selected_group' || ev.actor_role === 'other_technical' || /^agent_/.test(ev.event_type || '') || /^team_/.test(ev.event_type || '');
  }

  function parseTimestamp(value) {
    if (!value) return null;
    var s = String(value).replace(' ', 'T');
    var d = new Date(s);
    if (isNaN(d.getTime())) {
      d = new Date(String(value).slice(0, 10) + 'T00:00:00');
    }
    return isNaN(d.getTime()) ? null : d.getTime();
  }

  function averageMap(map) {
    var out = {};
    Object.keys(map || {}).sort().forEach(function (k) { out[k] = average(map[k]); });
    return out;
  }

  function average(arr) {
    if (!arr || !arr.length) return null;
    return arr.reduce(function (a, b) { return a + b; }, 0) / arr.length;
  }

  function percentile(arr, p) {
    if (!arr || !arr.length) return null;
    var idx = Math.ceil((p / 100) * arr.length) - 1;
    idx = Math.max(0, Math.min(arr.length - 1, idx));
    return arr[idx];
  }

  function consolidatedAnalytics(entries, facts, analytics, managerMode) {
    var rt = analytics.response_time || {};
    var ticketCount = ((facts && facts.tickets) || []).length;
    var rows = [
      { indicator: managerMode ? 'Chamados no escopo gerencial' : 'Entradas no relatório', value: managerMode ? ticketCount : entries.length },
      { indicator: 'Chamados distintos', value: ticketCount },
      { indicator: 'Projetos distintos', value: ((facts && facts.projects) || []).length },
      { indicator: 'Tempo médio de resposta', value: rt.avg_minutes != null ? durationLabel(rt.avg_minutes) : 'N/D' },
      { indicator: 'Tempo mediano de resposta', value: rt.median_minutes != null ? durationLabel(rt.median_minutes) : 'N/D' },
      { indicator: 'P90 de resposta', value: rt.p90_minutes != null ? durationLabel(rt.p90_minutes) : 'N/D' },
      { indicator: 'Eventos do solicitante sem resposta técnica', value: rt.requester_events_unanswered != null ? rt.requester_events_unanswered : 'N/D' }
    ];
    if (managerMode && analytics.manager_summary) rows.push({ indicator: 'Agentes com atividade detectada', value: analytics.manager_summary.total_agents || 0 });
    return rows;
  }

  function durationLabel(minutes) {
    if (minutes == null || !isFinite(minutes)) return 'N/D';
    minutes = Math.round(minutes);
    if (minutes < 60) return minutes + ' min';
    var h = Math.floor(minutes / 60);
    var m = minutes % 60;
    if (h < 24) return h + 'h' + (m ? String(m).padStart(2, '0') : '');
    var d = Math.floor(h / 24);
    var rh = h % 24;
    return d + 'd' + (rh ? ' ' + rh + 'h' : '');
  }

  function renderDraft() {
    var box = $('g4fr-preview');
    if (!box || !draft) return;
    if (!draft.analytics_locked) {
      draft.analytics = computeAnalytics(draft);
      draft.metrics = computeMetrics(draft);
    }
    var metrics = (draft.metrics || []).slice(0, 6).map(function (m) {
      return '<div class="g4fr-metric"><div class="g4fr-metric-value">' + html(m.value) + '</div><div class="g4fr-metric-label">' + html(m.label) + '</div></div>';
    }).join('');
    var validations = (draft.validations || []).concat((draft.warnings || []).map(function (w) { return { level: 'warning', message: w }; })).map(function (v) {
      return '<div class="g4fr-validation ' + html(v.level || 'info') + '">' + html(v.message || '') + '</div>';
    }).join('');
    var allEntries = detailEntries(draft);
    var totalPages = Math.max(1, Math.ceil(allEntries.length / detailPageSize));
    if (detailPage > totalPages) detailPage = totalPages;
    if (detailPage < 1) detailPage = 1;
    var pageStart = (detailPage - 1) * detailPageSize;
    var pageEnd = Math.min(allEntries.length, pageStart + detailPageSize);
    var renderedEntries = allEntries.slice(pageStart, pageEnd);
    var groups = groupEntries(renderedEntries, 'detail_entries', pageStart);
    var entriesHtml = '';
    Object.keys(groups).sort().forEach(function (date) {
      entriesHtml += '<div class="g4fr-day-title">' + html(formatDate(date)) + '</div>';
      groups[date].forEach(function (entry) { entriesHtml += entryHtml(entry, entry.__idx, entry.__collection); });
    });
    var dc = draft.data_context || {};
    var titleNote = '';
    if (dc.ticket_title_filter_enabled && (dc.ticket_titles || []).length) {
      titleNote = '<div class="g4fr-ticket-title-filtered">Filtro por título: ' + html((dc.ticket_titles || []).map(function (t) { return t.title; }).join('; ')) + ' • Escopo: ' + (dc.effective_ticket_title_filter_scope === 'global' ? 'global' : 'somente detalhamento') + '</div>';
    }
    var managerNote = dc.manager_mode_enabled ? '<div class="g4fr-manager-note"><strong>Modo gerencial ativo.</strong> O detalhamento individual de chamados não será exibido nem exportado; o documento priorizará indicadores e resumo por agente dos grupos selecionados.</div>' : '';
    var volumeNotice = allEntries.length > detailPageSize ? '<div class="g4fr-validation info">Foram carregadas ' + html(allEntries.length) + ' entradas detalhadas. Para manter a tela responsiva, a prévia mostra ' + html(pageStart + 1) + '–' + html(pageEnd) + ' de ' + html(allEntries.length) + ' por página. A exportação usa o modo definido para o DOCX e não depende da página visível.</div>' : '';
    var pager = (!dc.manager_mode_enabled && allEntries.length > detailPageSize) ? detailPagerHtml(detailPage, totalPages, allEntries.length) : '';
    var detailBlock = dc.manager_mode_enabled ? managerSummaryPreview(draft) : (pager + '<div id="g4fr-entries">' + (entriesHtml || '<p class="g4fr-muted">Nenhuma entrada detalhada corresponde ao contexto/filtro selecionado.</p>') + '</div>' + pager);
    box.innerHTML = '<div class="g4fr-card"><div class="g4fr-preview-header"><div><h2>3. Prévia editável</h2><p><strong>' + html(draft.report_title) + '</strong><br>' + html(draft.subject && draft.subject.name) + ' • ' + html(formatDate(draft.period_start)) + ' a ' + html(formatDate(draft.period_end)) + '</p></div></div>' +
      '<div class="g4fr-metrics">' + metrics + '</div>' + titleNote + managerNote + volumeNotice +
      renderAnalyticsPreview(draft.analytics || {}) + renderCoveragePreview(draft.coverage || {}) +
      '<h2>Validações</h2>' + (validations || '<p>Nenhuma validação relevante.</p>') +
      '<h2>Resumo executivo</h2><textarea class="form-control" id="g4fr-exec-summary" rows="4">' + html(draft.executive_summary || '') + '</textarea>' +
      detailBlock + '</div>';
    bindEntryEditors();
    bindDetailPager();
    var summary = $('g4fr-exec-summary');
    if (summary) summary.addEventListener('input', function () { draft.executive_summary = summary.value; });
  }



  function renderCoveragePreview(cov) {
    cov = cov || {};
    var sources = cov.sources || {};
    var keys = Object.keys(sources);
    var filters = cov.filters || {};
    var limitations = cov.limitations || [];
    if (!keys.length && !Object.keys(filters).length && !limitations.length) return '';
    var sourceRows = keys.sort().slice(0, 12).map(function (k) {
      var r = sources[k] || {};
      return '<tr><td>' + html(sourceLabel(k)) + '<br><small>' + html(k) + '</small></td><td>' + html(r.api_total || 0) + '</td><td>' + html(r.rows_loaded || 0) + '</td><td>' + html(r.unique_added || 0) + '</td><td>' + html(r.duplicates || 0) + '</td></tr>';
    }).join('');
    var filterRows = Object.keys(filters).sort().map(function (k) { return '<span class="g4fr-coverage-pill">' + html(filterLabel(k)) + ': ' + html(filters[k]) + '</span>'; }).join(' ');
    var limitRows = limitations.slice(0, 8).map(function (m) { return '<div class="g4fr-validation warning">' + html(m) + '</div>'; }).join('');
    return '<div class="g4fr-coverage"><h2>Cobertura da extração</h2>' +
      (sourceRows ? '<table class="g4fr-mini-table"><thead><tr><th>Fonte</th><th>Total API</th><th>Linhas lidas</th><th>Únicos adicionados</th><th>Duplicados</th></tr></thead><tbody>' + sourceRows + '</tbody></table>' : '') +
      (filterRows ? '<div class="g4fr-coverage-filters">' + filterRows + '</div>' : '') + limitRows + '</div>';
  }

  function filterLabel(k) {
    return {
      title_filtered_out: 'Filtrados por título',
      date_scope_filtered_out: 'Filtrados por critério temporal',
      agent_action_not_detected: 'Sem ação detectável do agente',
      closed_by_agent_not_confirmed: 'Fechamento/autoria não confirmada',
      duplicate_merged: 'Duplicados mesclados',
      out_of_period_detail_suppressed: 'Detalhes fora do período omitidos'
    }[k] || k;
  }

  function managerSummaryPreview(d) {
    var rows = (((d.analytics || {}).manager_summary || {}).by_agent || []).slice(0, 40);
    if (!rows.length) return '<h2>Resumo gerencial por agente</h2><p class="g4fr-muted">Nenhuma atividade técnica com autoria detectável foi encontrada nos chamados carregados. Verifique se os contextos de grupo e análises de tempo de resposta estão selecionados.</p>';
    var body = rows.map(function (r) {
      return '<tr><td>' + html(displayAgentName(r.agent_name || 'Técnico não identificado', r.agent_id || 0)) + '</td><td>' + html(agentReference(r)) + '</td><td>' + html(r.tickets_touched || 0) + '</td><td>' + html(r.tickets_closed || 0) + '</td><td>' + html(r.technical_events || 0) + '</td><td>' + html(r.avg_response_minutes != null ? durationLabel(r.avg_response_minutes) : 'N/D') + '</td><td>' + html(r.p90_response_minutes != null ? durationLabel(r.p90_response_minutes) : 'N/D') + '</td></tr>';
    }).join('');
    return '<h2>Resumo gerencial por agente</h2><table class="g4fr-manager-table"><thead><tr><th>Agente</th><th>Login/ID</th><th>Chamados tocados</th><th>Chamados concluídos</th><th>Eventos técnicos</th><th>Resposta média</th><th>P90 resposta</th></tr></thead><tbody>' + body + '</tbody></table>';
  }

  function renderAnalyticsPreview(a) {
    if (!a || !a.enabled || !a.enabled.length) return '';
    var cards = [];
    if (a.daily_volume) {
      cards.push(lineCard('Evolução diária', topMap(a.daily_volume, 31, true)));
      cards.push(heatmapCard('Mapa de calor diário', a.daily_volume));
    }
    if (a.status_distribution) cards.push(donutCard('Status', topMap(a.status_distribution, 8), 'donut'));
    if (a.source_distribution) cards.push(donutCard('Origem das atividades', topMap(a.source_distribution, 8), 'pie'));
    if (a.top_categories) cards.push(barCard('Top categorias', a.top_categories));
    if (a.location_distribution) cards.push(donutCard('Localização dos chamados', a.location_distribution, 'donut'));
    if (a.agent_vs_group) cards.push(columnCard('Agente x grupos selecionados', [
      { label: 'Agente', value: a.agent_vs_group.agent || 0 },
      { label: 'Grupos selecionados', value: a.agent_vs_group.selected_groups || 0 },
      { label: 'Misto', value: a.agent_vs_group.mixed || 0 },
      { label: 'Outros', value: a.agent_vs_group.other || 0 }
    ]));
    if (a.response_time) cards.push(responseCard(a.response_time));
    if (!cards.length) return '';
    return '<div class="g4fr-analytics-preview"><h2>Análises selecionadas</h2><div class="g4fr-analytics-grid">' + cards.join('') + '</div></div>';
  }

  function lineCard(title, rows) {
    rows = (rows || []).slice(0, 31);
    var w = 520, h = 160, pad = 22;
    var vals = rows.map(function (r) { return parseFloat(r.value) || 0; });
    var max = vals.reduce(function (m, v) { return Math.max(m, v); }, 0) || 1;
    var pts = rows.map(function (r, i) {
      var x = pad + (rows.length <= 1 ? 0 : i * ((w - pad * 2) / (rows.length - 1)));
      var y = h - pad - ((parseFloat(r.value) || 0) / max) * (h - pad * 2);
      return [Math.round(x), Math.round(y)];
    });
    var poly = pts.map(function (p) { return p[0] + ',' + p[1]; }).join(' ');
    var dots = pts.map(function (p) { return '<circle cx="' + p[0] + '" cy="' + p[1] + '" r="3"></circle>'; }).join('');
    var labels = rows.length ? '<div class="g4fr-chart-caption">' + html(rows[0].label) + ' → ' + html(rows[rows.length - 1].label) + '</div>' : '';
    return '<div class="g4fr-analysis-card"><h3>' + html(title) + '</h3><svg class="g4fr-line-chart" viewBox="0 0 ' + w + ' ' + h + '" preserveAspectRatio="none"><polyline points="' + poly + '"></polyline>' + dots + '</svg>' + labels + '</div>';
  }

  function donutCard(title, rows, type) {
    rows = rows || [];
    var total = rows.reduce(function (n, r) { return n + (parseFloat(r.value) || 0); }, 0) || 1;
    var stops = [];
    var acc = 0;
    var colors = ['#2f6fed','#188da1','#002b49','#8cc2ff','#f7b731','#4caf50','#ef5350','#9aa6b2'];
    rows.slice(0, 8).forEach(function (r, i) {
      var val = parseFloat(r.value) || 0;
      var next = acc + (val / total) * 100;
      stops.push(colors[i % colors.length] + ' ' + acc.toFixed(2) + '% ' + next.toFixed(2) + '%');
      acc = next;
    });
    var legend = rows.slice(0, 8).map(function (r, i) {
      var pct = ((parseFloat(r.value) || 0) / total) * 100;
      return '<div><span style="background:' + colors[i % colors.length] + '"></span>' + html(r.label) + ' <strong>' + html(r.value) + '</strong> <small>' + pct.toFixed(1) + '%</small></div>';
    }).join('');
    var cls = type === 'pie' ? 'g4fr-pie' : 'g4fr-donut';
    return '<div class="g4fr-analysis-card"><h3>' + html(title) + '</h3><div class="g4fr-donut-wrap"><div class="' + cls + '" style="background:conic-gradient(' + stops.join(',') + ')"><b>' + html(total) + '</b></div><div class="g4fr-donut-legend">' + legend + '</div></div></div>';
  }

  function heatmapCard(title, map) {
    var keys = Object.keys(map || {}).sort();
    if (!keys.length) return '';
    var max = keys.reduce(function (m, k) { return Math.max(m, parseFloat(map[k]) || 0); }, 0) || 1;
    var cells = keys.slice(0, 42).map(function (k) {
      var val = parseFloat(map[k]) || 0;
      var level = val <= 0 ? 0 : Math.max(1, Math.min(4, Math.ceil((val / max) * 4)));
      return '<div class="g4fr-heat-cell l' + level + '" title="' + html(k + ': ' + val) + '">' + html(String(new Date(k + 'T00:00:00').getDate()).padStart(2, '0')) + '<small>' + html(val) + '</small></div>';
    }).join('');
    return '<div class="g4fr-analysis-card"><h3>' + html(title) + '</h3><div class="g4fr-heatmap">' + cells + '</div></div>';
  }

  function columnCard(title, rows) {
    rows = rows || [];
    var max = rows.reduce(function (n, r) { return Math.max(n, parseFloat(r.value) || 0); }, 0) || 1;
    var cols = rows.map(function (r) {
      var pct = Math.max(2, Math.round(((parseFloat(r.value) || 0) / max) * 100));
      return '<div class="g4fr-col-item"><div class="g4fr-col-box"><div style="height:' + pct + '%"></div></div><strong>' + html(r.value) + '</strong><span>' + html(r.label) + '</span></div>';
    }).join('');
    return '<div class="g4fr-analysis-card"><h3>' + html(title) + '</h3><div class="g4fr-column-chart">' + cols + '</div></div>';
  }

  function barCard(title, rows) {
    rows = rows || [];
    var max = rows.reduce(function (n, r) { return Math.max(n, parseFloat(r.value) || 0); }, 0) || 1;
    var body = rows.map(function (r) {
      var val = parseFloat(r.value) || 0;
      var pct = Math.max(0, Math.min(100, Math.round((val / max) * 100)));
      return '<div class="g4fr-bar-row"><div>' + html(r.label) + '</div><strong>' + html(r.value) + '</strong><div class="g4fr-bar-track"><div class="g4fr-bar-fill" style="width:' + pct + '%"></div></div></div>';
    }).join('') || '<p class="g4fr-muted">Sem dados.</p>';
    return '<div class="g4fr-analysis-card"><h3>' + html(title) + '</h3>' + body + '</div>';
  }

  function responseCard(rt) {
    var rows = [
      ['Intervalos respondidos', rt.intervals_count || 0],
      ['Tempo médio', rt.avg_minutes != null ? durationLabel(rt.avg_minutes) : 'N/D'],
      ['Mediana', rt.median_minutes != null ? durationLabel(rt.median_minutes) : 'N/D'],
      ['P90', rt.p90_minutes != null ? durationLabel(rt.p90_minutes) : 'N/D'],
      ['Primeira resposta média', rt.first_avg_minutes != null ? durationLabel(rt.first_avg_minutes) : 'N/D'],
      ['Eventos sem resposta', rt.requester_events_unanswered || 0]
    ];
    var trend = rt.by_day ? lineCard('Tendência de resposta', topMap(rt.by_day, 31, true)) : '';
    return trend + '<div class="g4fr-analysis-card"><h3>Tempo de resposta</h3><table class="g4fr-mini-table"><tbody>' + rows.map(function (r) { return '<tr><th>' + html(r[0]) + '</th><td>' + html(r[1]) + '</td></tr>'; }).join('') + '</tbody></table></div>';
  }

  function detailPagerHtml(page, totalPages, totalItems) {
    return '<div class="g4fr-detail-pager">' +
      '<button type="button" class="btn btn-sm btn-secondary g4fr-page-prev" ' + (page <= 1 ? 'disabled' : '') + '>Anterior</button>' +
      '<span>Página <strong>' + html(page) + '</strong> de <strong>' + html(totalPages) + '</strong> • ' + html(totalItems) + ' entradas</span>' +
      '<button type="button" class="btn btn-sm btn-secondary g4fr-page-next" ' + (page >= totalPages ? 'disabled' : '') + '>Próxima</button>' +
    '</div>';
  }

  function bindDetailPager() {
    document.querySelectorAll('.g4fr-page-prev').forEach(function (btn) {
      btn.addEventListener('click', function () { detailPage = Math.max(1, detailPage - 1); renderDraft(); });
    });
    document.querySelectorAll('.g4fr-page-next').forEach(function (btn) {
      btn.addEventListener('click', function () { detailPage += 1; renderDraft(); });
    });
  }

  function groupEntries(entries, collection, offset) {
    var groups = {};
    offset = parseInt(offset || 0, 10) || 0;
    entries.forEach(function (e, i) {
      e.__idx = offset + i;
      e.__collection = collection || 'entries';
      var d = e.date || new Date().toISOString().slice(0, 10);
      if (!groups[d]) groups[d] = [];
      groups[d].push(e);
    });
    return groups;
  }

  function entryHtml(e, idx, collection) {
    return '<div class="g4fr-entry" data-idx="' + idx + '" data-collection="' + html(collection || 'entries') + '">' +
      '<div class="g4fr-entry-head">' +
        '<div><label>Data</label><input type="date" class="form-control g4fr-entry-input" data-field="date" value="' + html(e.date || '') + '"></div>' +
        '<div><label>Título</label><input type="text" class="form-control g4fr-entry-input" data-field="title" value="' + html(e.title || '') + '"></div>' +
        '<div><label>Status</label><input type="text" class="form-control g4fr-entry-input" data-field="status" value="' + html(e.status || '') + '"></div>' +
        '<label class="g4fr-check"><input type="checkbox" class="g4fr-entry-include" ' + (e.include_in_report !== false ? 'checked' : '') + '> Incluir</label>' +
      '</div>' +
      '<div class="g4fr-entry-body">' +
        '<div><label>Referência</label><input type="text" class="form-control g4fr-entry-input" data-field="source_reference" value="' + html(e.source_reference || '') + '"></div>' +
        '<div><label>Categoria</label><input type="text" class="form-control g4fr-entry-input" data-field="category" value="' + html(e.category || '') + '"></div>' +
        '<div class="g4fr-full"><label>Atividades realizadas</label><textarea class="form-control g4fr-entry-input" data-field="activity_performed">' + html(e.activity_performed || '') + '</textarea></div>' +
        '<div><label>Resultado</label><textarea class="form-control g4fr-entry-input" data-field="result">' + html(e.result || '') + '</textarea></div>' +
        '<div><label>Plano de tratamento / próximos passos</label><textarea class="form-control g4fr-entry-input" data-field="next_steps">' + html(e.next_steps || '') + '</textarea></div>' +
        '<div class="g4fr-full"><label>Observações</label><textarea class="form-control g4fr-entry-input" data-field="observations">' + html(e.observations || '') + '</textarea></div>' +
      '</div></div>';
  }

  function bindEntryEditors() {
    document.querySelectorAll('.g4fr-entry').forEach(function (entryEl) {
      var idx = parseInt(entryEl.getAttribute('data-idx'), 10);
      var collection = entryEl.getAttribute('data-collection') || 'entries';
      function targetEntry() {
        if (!draft) return null;
        var arr = collection === 'detail_entries' && Array.isArray(draft.detail_entries) ? draft.detail_entries : draft.entries;
        return arr && arr[idx] ? arr[idx] : null;
      }
      entryEl.querySelectorAll('.g4fr-entry-input').forEach(function (input) {
        input.addEventListener('input', function () {
          var e = targetEntry();
          if (!e) return;
          e[input.getAttribute('data-field')] = input.value;
          draft.analytics = computeAnalytics(draft);
          draft.metrics = computeMetrics(draft);
        });
      });
      var inc = entryEl.querySelector('.g4fr-entry-include');
      if (inc) inc.addEventListener('change', function () {
        var e = targetEntry();
        if (!e) return;
        e.include_in_report = inc.checked;
        draft.analytics = computeAnalytics(draft);
        draft.metrics = computeMetrics(draft);
      });
    });
  }

  function addManualEntry() {
    if (!draft) {
      draft = baseDraft(collectRequest());
    }
    draft.entries = draft.entries || [];
    draft.entries.push({
      date: $('g4fr-start').value || new Date().toISOString().slice(0, 10),
      source_type: 'manual',
      source_itemtype: '',
      source_id: 0,
      source_reference: 'Entrada manual',
      professional: draft.subject && draft.subject.name || $('g4fr-agent-name').value || '',
      title: 'Atividade manual',
      status: '',
      category: '',
      activity_performed: 'Descrever a atividade realizada pelo profissional nesta data.',
      result: '',
      next_steps: '',
      observations: '',
      include_in_report: true,
      source_contexts: ['manual_entries']
    });
    draft.analytics = computeAnalytics(draft);
    draft.metrics = computeMetrics(draft);
    renderDraft();
  }

  function syncDraftHeaderFromForm() {
    if (!draft) return;
    var req = collectRequest();
    draft.report_title = req.report_title;
    draft.template = req.template;
    draft.scope_text = req.scope_text;
    draft.objective_text = req.objective_text;
    draft.analytics_options = req.analytics;
    var existingDc = draft.data_context || {};
    // Preserve server/client high-volume planner decisions. The form may still show
    // the user's original choices, but the generated draft must keep the safe
    // extraction mode that was actually used.
    draft.data_context = Object.assign({}, req.data_context || {}, existingDc);
    draft.manager_context = draft.manager_context || {};
    draft.manager_context.enabled = !!draft.data_context.manager_mode_enabled;
    draft.manager_context.hide_detail_entries = !!draft.data_context.manager_mode_enabled;
    draft.subject = { type: 'user', id: parseInt(req.agent_id || 0, 10) || 0, name: cleanName(req.agent_name || '') };
    draft.groups = selectedGroupsArray();
  }

  function compactWorkingDraftForLargeMode(d) {
    if (!d) return;
    var managerMode = !!(d.data_context && d.data_context.manager_mode_enabled);
    if (managerMode) {
      // Once analytics and manager summaries are computed, event timelines are
      // no longer needed in the browser. Removing them prevents 100k-ticket
      // manager reports from carrying megabytes of raw evidence into export.
      (((d.facts || {}).tickets) || []).forEach(function (t) {
        if (t && Array.isArray(t.events)) t.events = [];
      });
      d.entries = [];
      d.detail_entries = [];
      d.raw_entries = [];
      d.analytics_locked = true;
      d.high_volume_mode = true;
    } else if (((d.detail_entries || d.entries || []).length) > 1000) {
      d.high_volume_mode = true;
    }
  }

  function compactEntryForExport(e) {
    return {
      date: e.date || '',
      source_type: e.source_type || '',
      source_itemtype: e.source_itemtype || '',
      source_id: e.source_id || 0,
      source_reference: e.source_reference || '',
      ticket_id: e.ticket_id || 0,
      ticket_title: e.ticket_title || '',
      professional: e.professional || '',
      title: e.title || '',
      status: e.status || '',
      category: e.category || '',
      activity_performed: e.activity_performed || '',
      result: e.result || '',
      next_steps: e.next_steps || '',
      observations: e.observations || '',
      include_in_report: e.include_in_report !== false,
      source_contexts: (e.source_contexts || []).slice(0, 12)
    };
  }

  function compactCoverageForExport(cov) {
    cov = cov || {};
    var sources = {};
    Object.keys(cov.sources || {}).sort().slice(0, 80).forEach(function (k) { sources[k] = cov.sources[k]; });
    return {
      sources: sources,
      filters: Object.assign({}, cov.filters || {}),
      limitations: (cov.limitations || []).slice(0, 30),
      warnings: (cov.warnings || []).slice(0, 30)
    };
  }

  function compactExportDraft(d) {
    var dc = d.data_context || {};
    var managerMode = !!dc.manager_mode_enabled;
    var allDetailEntries = managerMode ? [] : detailEntries(d);
    var highVolumeDetail = !managerMode && allDetailEntries.length > 5000 && !dc.full_detail_export_enabled;
    var entries = highVolumeDetail ? [] : allDetailEntries.map(compactEntryForExport);
    var exportValidations = (d.validations || []).filter(function (v) { return v && v.level !== 'debug'; }).slice(0, 80);
    if (highVolumeDetail) {
      exportValidations.push({ level: 'info', message: 'Exportação em modo de alto volume: ' + allDetailEntries.length + ' entradas detalhadas não foram incluídas no corpo do DOCX para evitar limites de tamanho/tempo. Indicadores e gráficos permanecem completos. Marque “Exportar detalhamento completo” se realmente precisar gerar um DOCX evidencial muito grande.' });
    }
    var exportDraft = {
      schema_version: d.schema_version || 2,
      export_schema_version: 2,
      created_at: d.created_at || new Date().toISOString(),
      connection: d.connection || {},
      contract_label: d.contract_label || '',
      report_title: d.report_title || '',
      template: d.template || 'operational',
      period_start: d.period_start || '',
      period_end: d.period_end || '',
      subject: d.subject || {},
      groups: d.groups || [],
      contexts: d.contexts || [],
      analytics_options: d.analytics_options || [],
      date_context: d.date_context || {},
      data_context: d.data_context || {},
      manager_context: d.manager_context || {},
      scope_text: d.scope_text || '',
      objective_text: d.objective_text || '',
      executive_summary: d.executive_summary || '',
      metrics: d.metrics || [],
      analytics: d.analytics || {},
      entries: entries,
      detail_entries: entries,
      validations: exportValidations,
      warnings: (d.warnings || []).slice(0, 80),
      coverage: compactCoverageForExport(d.coverage || {}),
      footer: d.footer || '',
      high_volume_mode: !!(d.high_volume_mode || highVolumeDetail),
      high_volume_detail_omitted: !!highVolumeDetail,
      fact_counts: {
        tickets: (((d.facts || {}).tickets) || []).length,
        projects: (((d.facts || {}).projects) || []).length
      }
    };
    if (managerMode) {
      exportDraft.facts = { tickets: [], projects: [] };
      exportDraft.entries = [];
      exportDraft.detail_entries = [];
    }
    return exportDraft;
  }

  function prepareExportToken(exportDraft) {
    var json = JSON.stringify(exportDraft);
    var chunkSize = 1800;
    var total = Math.max(1, Math.ceil(json.length / chunkSize));
    setStatus('info', 'Preparando exportação DOCX. Payload compactado: ' + Math.round(json.length / 1024) + ' KB em ' + total + ' lote(s).');
    return postApi('prepare_export', { op: 'start', total_chunks: total }).then(function (res) {
      var token = res.token;
      if (!token) throw new Error('Token de exportação não retornado pelo servidor.');
      var seq = Promise.resolve();
      for (var i = 0; i < total; i++) {
        (function (idx) {
          seq = seq.then(function () {
            setStatus('info', 'Enviando rascunho compactado para exportação: lote ' + (idx + 1) + '/' + total + '.');
            return postApi('prepare_export', {
              op: 'chunk',
              token: token,
              index: idx,
              total_chunks: total,
              chunk: json.slice(idx * chunkSize, Math.min(json.length, (idx + 1) * chunkSize))
            });
          });
        })(i);
      }
      return seq.then(function () { return postApi('prepare_export', { op: 'finish', token: token, total_chunks: total }); }).then(function () { return token; });
    });
  }

  function submitExportToken(token) {
    var iframeName = 'g4fr-download-frame';
    var iframe = document.querySelector('iframe[name="' + iframeName + '"]');
    if (!iframe) {
      iframe = document.createElement('iframe');
      iframe.name = iframeName;
      iframe.style.display = 'none';
      document.body.appendChild(iframe);
    }
    var url = new URL(exportUrl, window.location.href);
    url.searchParams.set('export_token', token);
    url.searchParams.set('_', String(Date.now()));
    iframe.src = url.toString();
  }

  function exportDocx() {
    if (!draft) return;
    syncDraftHeaderFromForm();
    if (!draft.analytics_locked) {
      draft.analytics = computeAnalytics(draft);
      draft.metrics = computeMetrics(draft);
      compactWorkingDraftForLargeMode(draft);
    }
    var btn = $('g4fr-export');
    if (btn) btn.disabled = true;
    var exportDraft = compactExportDraft(draft);
    prepareExportToken(exportDraft).then(function (token) {
      setStatus('success', 'Exportação preparada. O download do DOCX será iniciado.');
      submitExportToken(token);
    }).catch(function (err) {
      setStatus('danger', 'Falha ao preparar exportação: ' + (err && err.message ? err.message : String(err)));
    }).finally(function () {
      if (btn) btn.disabled = false;
    });
  }

  function downloadJson() {
    if (!draft) return;
    syncDraftHeaderFromForm();
    if (!draft.analytics_locked) {
      draft.analytics = computeAnalytics(draft);
      draft.metrics = computeMetrics(draft);
    }
    var blob = new Blob([JSON.stringify(draft, null, 2)], { type: 'application/json' });
    var a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = 'rascunho-relatorio-g4f.json';
    a.click();
    setTimeout(function () { URL.revokeObjectURL(a.href); }, 1000);
  }

  function importJson(file) {
    var reader = new FileReader();
    reader.onload = function () {
      try {
        draft = JSON.parse(reader.result);
        draft.analytics = computeAnalytics(draft);
        draft.metrics = computeMetrics(draft);
        setDraftButtons(true);
        setStatus('success', 'Rascunho importado.');
        renderDraft();
      } catch (e) {
        setStatus('danger', 'JSON inválido: ' + e.message);
      }
    };
    reader.readAsText(file);
  }

  function selectedGroupsArray() {
    return Object.keys(selectedGroups).sort(function (a, b) { return parseInt(a, 10) - parseInt(b, 10); }).map(function (id) {
      return { id: parseInt(id, 10), name: selectedGroups[id] || ('Grupo #' + id) };
    });
  }

  function renderSelectedGroups() {
    var box = $('g4fr-selected-groups');
    var hidden = $('g4fr-group-ids');
    if (!box || !hidden) return;
    var ids = Object.keys(selectedGroups).sort(function (a, b) { return parseInt(a, 10) - parseInt(b, 10); });
    hidden.value = ids.join(',');
    if (!ids.length) {
      box.innerHTML = '<div class="g4fr-muted">Nenhum grupo adicional selecionado.</div>';
      return;
    }
    box.innerHTML = ids.map(function (id) {
      return '<span class="g4fr-chip" data-group-id="' + html(id) + '"><strong>#' + html(id) + '</strong> ' + html(selectedGroups[id]) + ' <button type="button" title="Remover grupo">×</button></span>';
    }).join('');
    box.querySelectorAll('.g4fr-chip button').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var chip = btn.closest('.g4fr-chip');
        if (!chip) return;
        delete selectedGroups[chip.getAttribute('data-group-id')];
        renderSelectedGroups();
        markExtractionDirty('grupo removido');
      });
    });
  }

  function addSelectedGroup(group) {
    if (!group || !group.id) return;
    selectedGroups[String(group.id)] = group.name || ('Grupo #' + group.id);
    renderSelectedGroups();
    markExtractionDirty('grupo selecionado');
  }



  function renderSelectedTicketTitles() {
    var box = $('g4fr-selected-ticket-titles');
    if (!box) return;
    var keys = Object.keys(selectedTicketTitles).sort();
    if (!keys.length) {
      box.innerHTML = '<div class="g4fr-muted">Nenhum título selecionado.</div>';
      return;
    }
    box.innerHTML = keys.map(function (key) {
      var item = selectedTicketTitles[key];
      var ref = item.ticket_id ? '<strong>#' + html(item.ticket_id) + '</strong> ' : '';
      return '<span class="g4fr-chip" data-title-key="' + html(key) + '">' + ref + html(item.title) + ' <button type="button" title="Remover título">×</button></span>';
    }).join('');
    box.querySelectorAll('.g4fr-chip button').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var chip = btn.closest('.g4fr-chip');
        if (!chip) return;
        delete selectedTicketTitles[chip.getAttribute('data-title-key')];
        renderSelectedTicketTitles();
        markExtractionDirty('título removido');
      });
    });
  }

  function addSelectedTicketTitle(item) {
    var title = String((item && item.title) || '').trim();
    if (!title) return;
    var ticketId = String((item && item.ticket_id) || '').trim();
    selectedTicketTitles[normalizeForMatch(title) + '|' + ticketId] = { title: title, ticket_id: ticketId };
    renderSelectedTicketTitles();
    markExtractionDirty('título de chamado selecionado');
  }

  function addManualTicketTitle() {
    var text = $('g4fr-title-filter-text');
    if (!text) return;
    var title = text.value.trim();
    if (!title) return;
    addSelectedTicketTitle({ title: title, ticket_id: text.getAttribute('data-ticket-id') || '' });
    clearTicketTitleInput();
  }

  function clearTicketTitleInput() {
    var text = $('g4fr-title-filter-text');
    if (!text) return;
    text.value = '';
    text.readOnly = false;
    text.classList.remove('g4fr-readonly');
    text.removeAttribute('data-ticket-id');
  }

  function setTicketTitleInput(title, ticketId) {
    var text = $('g4fr-title-filter-text');
    if (!text) return;
    text.value = title || '';
    text.readOnly = true;
    text.classList.add('g4fr-readonly');
    if (ticketId) text.setAttribute('data-ticket-id', String(ticketId));
  }

  function searchTicketById() {
    var qEl = $('g4fr-ticket-id-search');
    var q = qEl ? qEl.value : '';
    if (!q) return;
    var resBox = $('g4fr-ticket-results');
    if (!resBox) return;
    resBox.style.display = 'block';
    resBox.innerHTML = '<div class="g4fr-search-result">Buscando chamado...</div>';
    callApi('search_ticket', { profile_id: $('g4fr-profile').value, ticket_id: q }).then(function (data) {
      var t = data.ticket || null;
      if (!t || !t.id) {
        resBox.innerHTML = '<div class="g4fr-search-empty">Chamado não encontrado.</div>';
        return;
      }
      resBox.innerHTML = '<div class="g4fr-search-result" data-id="' + html(t.id) + '" data-title="' + html(t.title || '') + '"><strong>#' + html(t.id) + '</strong> ' + html(t.title || '') + '<br><small>' + html(t.status || '') + '</small></div>';
      resBox.querySelectorAll('.g4fr-search-result').forEach(function (el) {
        el.addEventListener('click', function () {
          var item = { ticket_id: el.getAttribute('data-id'), title: el.getAttribute('data-title') };
          setTicketTitleInput(item.title, item.ticket_id);
          addSelectedTicketTitle(item);
          resBox.innerHTML = '';
          resBox.style.display = 'none';
          if (qEl) qEl.value = '';
        });
      });
    }).catch(function (err) {
      resBox.innerHTML = '<div class="g4fr-search-result">' + html(err.message) + '</div>';
    });
  }

  function toggleTitleFilterPanel() {
    var enabled = $('g4fr-title-filter-enabled');
    var panel = $('g4fr-title-filter-panel');
    if (panel) panel.style.display = enabled && enabled.checked ? 'block' : 'none';
    markExtractionDirty('contexto de dados');
  }

  function searchGroups() {
    var qEl = $('g4fr-group-search-q');
    var q = qEl ? qEl.value : '';
    if (!q) return;
    var resBox = $('g4fr-group-results');
    if (!resBox) return;
    resBox.style.display = 'block';
    resBox.innerHTML = '<div class="g4fr-search-result">Buscando grupos...</div>';
    callApi('search_groups', { profile_id: $('g4fr-profile').value, q: q }).then(function (data) {
      var groups = data.groups || [];
      if (!groups.length) {
        resBox.innerHTML = '<div class="g4fr-search-empty">' + html(data.warning || 'Nenhum grupo encontrado.') + '</div>';
        return;
      }
      resBox.innerHTML = groups.map(function (g) {
        return '<div class="g4fr-search-result" data-id="' + html(g.id) + '" data-name="' + html(g.name || '') + '"><strong>#' + html(g.id) + '</strong> ' + html(g.name || '') + '</div>';
      }).join('');
      resBox.querySelectorAll('.g4fr-search-result').forEach(function (el) {
        el.addEventListener('click', function () {
          addSelectedGroup({ id: el.getAttribute('data-id'), name: el.getAttribute('data-name') });
          if (qEl) qEl.value = '';
          resBox.innerHTML = '';
          resBox.style.display = 'none';
        });
      });
    }).catch(function (err) {
      resBox.innerHTML = '<div class="g4fr-search-result">' + html(err.message) + '</div>';
    });
  }

  function renderSelectedAgent() {
    var box = $('g4fr-selected-agent');
    if (!box) return;
    var id = $('g4fr-agent-id') ? $('g4fr-agent-id').value : '';
    var name = $('g4fr-agent-name') ? $('g4fr-agent-name').value : '';
    if (!id && !name) {
      box.style.display = 'none';
      box.innerHTML = '';
      return;
    }
    box.style.display = 'flex';
    box.innerHTML = '<span>Agente selecionado: <strong>#' + html(id || '-') + ' — ' + html(name || 'sem nome') + '</strong></span><button type="button" class="btn btn-sm btn-outline-secondary" id="g4fr-clear-agent">Limpar agente</button>';
    var btn = $('g4fr-clear-agent');
    if (btn) btn.addEventListener('click', function () {
      $('g4fr-agent-id').value = '';
      $('g4fr-agent-name').value = '';
      renderSelectedAgent();
      markExtractionDirty('agente removido');
    });
  }

  function searchUsers() {
    var q = $('g4fr-agent-search-q').value;
    if (!q) return;
    var resBox = $('g4fr-agent-results');
    if (resBox) resBox.style.display = 'block';
    resBox.innerHTML = '<div class="g4fr-search-result">Buscando...</div>';
    callApi('search_users', { profile_id: $('g4fr-profile').value, q: q }).then(function (data) {
      var users = data.users || [];
      if (!users.length) {
        resBox.innerHTML = '<div class="g4fr-search-result">Nenhum usuário encontrado.</div>';
        return;
      }
      function selectUserElement(el) {
        $('g4fr-agent-id').value = el.getAttribute('data-id');
        $('g4fr-agent-name').value = cleanName(el.getAttribute('data-name'));
        resBox.innerHTML = '';
        resBox.style.display = 'none';
        renderSelectedAgent();
        markExtractionDirty('agente selecionado');
      }
      resBox.innerHTML = users.map(function (u) {
        return '<div class="g4fr-search-result" data-id="' + html(u.id) + '" data-name="' + html(cleanName(u.name || u.login || '')) + '"><strong>#' + html(u.id) + '</strong> ' + html(cleanName(u.name || u.login || '')) + '</div>';
      }).join('');
      var nodes = resBox.querySelectorAll('.g4fr-search-result');
      nodes.forEach(function (el) { el.addEventListener('click', function () { selectUserElement(el); }); });
      if (users.length === 1 || /^\d+$/.test(String(q).trim())) {
        var first = nodes[0];
        if (first) selectUserElement(first);
      }
    }).catch(function (err) {
      resBox.innerHTML = '<div class="g4fr-search-result">' + html(err.message) + '</div>';
    });
  }

  function cleanName(name) {
    return String(name || '').replace(/^[\s.]+/g, '').replace(/[\s.]+$/g, '');
  }

  function formatDate(iso) {
    if (!iso) return '';
    var p = String(iso).slice(0, 10).split('-');
    if (p.length === 3) return p[2] + '/' + p[1] + '/' + p[0];
    return iso;
  }

  function bindDirtyHandlers() {
    ['g4fr-profile','g4fr-start','g4fr-end'].forEach(function (id) {
      var el = $(id); if (el) el.addEventListener('change', function () { markExtractionDirty('parâmetros alterados'); });
    });
    var template = $('g4fr-template');
    if (template) template.addEventListener('change', function () {
      setChecked('g4fr-analytics', analyticsDefaultsForTemplate(template.value));
      if (draft) {
        syncDraftHeaderFromForm();
        draft.analytics = computeAnalytics(draft);
        draft.metrics = computeMetrics(draft);
        setDraftButtons(true);
        renderDraft();
      }
    });
    document.querySelectorAll('#g4fr-contexts input[type="checkbox"]').forEach(function (el) { el.addEventListener('change', function () { markExtractionDirty('contextos alterados'); }); });
    document.querySelectorAll('#g4fr-date-contexts input[type="checkbox"]').forEach(function (el) { el.addEventListener('change', function () { markExtractionDirty('critério temporal alterado'); }); });
    document.querySelectorAll('#g4fr-analytics input[type="checkbox"]').forEach(function (el) { el.addEventListener('change', function () {
      if (draft) {
        syncDraftHeaderFromForm();
        draft.analytics = computeAnalytics(draft);
        draft.metrics = computeMetrics(draft);
        setDraftButtons(true);
        renderDraft();
      }
    }); });
    ['g4fr-title','g4fr-scope','g4fr-objective'].forEach(function (id) {
      var el = $(id); if (el) el.addEventListener('input', function () { if (draft) { syncDraftHeaderFromForm(); } });
    });
    ['g4fr-title-filter-enabled','g4fr-title-filter-detail-only','g4fr-manager-mode'].forEach(function (id) {
      var el = $(id); if (el) el.addEventListener('change', function () { if (id === 'g4fr-title-filter-enabled') toggleTitleFilterPanel(); else markExtractionDirty('contexto de dados'); });
    });
    var tt = $('g4fr-title-filter-text');
    if (tt) tt.addEventListener('input', function () { markExtractionDirty('título de chamado'); });
  }

  document.addEventListener('DOMContentLoaded', function () {
    if ($('g4fr-build')) $('g4fr-build').addEventListener('click', buildDraft);
    if ($('g4fr-add-manual')) $('g4fr-add-manual').addEventListener('click', addManualEntry);
    if ($('g4fr-export')) $('g4fr-export').addEventListener('click', exportDocx);
    if ($('g4fr-download-json')) $('g4fr-download-json').addEventListener('click', downloadJson);
    if ($('g4fr-agent-search-btn')) $('g4fr-agent-search-btn').addEventListener('click', searchUsers);
    if ($('g4fr-agent-search-q')) $('g4fr-agent-search-q').addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); searchUsers(); } });
    if ($('g4fr-group-search-btn')) $('g4fr-group-search-btn').addEventListener('click', searchGroups);
    if ($('g4fr-group-search-q')) $('g4fr-group-search-q').addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); searchGroups(); } });
    if ($('g4fr-title-filter-add')) $('g4fr-title-filter-add').addEventListener('click', addManualTicketTitle);
    if ($('g4fr-title-filter-clear')) $('g4fr-title-filter-clear').addEventListener('click', function () { clearTicketTitleInput(); markExtractionDirty('título de chamado'); });
    if ($('g4fr-ticket-search-btn')) $('g4fr-ticket-search-btn').addEventListener('click', searchTicketById);
    if ($('g4fr-ticket-id-search')) $('g4fr-ticket-id-search').addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); searchTicketById(); } });
    if ($('g4fr-import-json')) $('g4fr-import-json').addEventListener('change', function () { if (this.files && this.files[0]) importJson(this.files[0]); });
    document.querySelectorAll('[data-context-preset]').forEach(function (btn) {
      btn.addEventListener('click', function () { applyContextPreset(btn.getAttribute('data-context-preset')); });
    });
    bindDirtyHandlers();
    renderSelectedGroups();
    renderSelectedTicketTitles();
    toggleTitleFilterPanel();
    renderSelectedAgent();
  });
})();
