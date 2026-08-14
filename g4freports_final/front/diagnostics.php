<?php

require_once dirname(__DIR__) . '/inc/bootstrap.php';

use GlpiPlugin\G4freports\Config;
use GlpiPlugin\G4freports\Security;

if (class_exists('Session')) {
    Session::checkLoginUser();
}

$cfg = Config::merged();
if (!Security::canConfigure()) {
    if (class_exists('Html')) Html::displayRightError();
    http_response_code(403);
    exit;
}

$profiles = Config::profiles($cfg);
if (empty($profiles)) {
    if (class_exists('Html')) { Html::displayRightError(); }
    http_response_code(403);
    exit;
}

if (class_exists('Html')) {
    Html::header(__('Diagnóstico - Relatórios G4F', 'g4freports'), $_SERVER['PHP_SELF'], 'tools', 'PluginG4freportsMenu');
}
?>
<style><?php $css = dirname(__DIR__) . '/assets/css/g4freports.css'; if (is_file($css)) { readfile($css); } ?></style>
<div class="g4fr-wrap">
  <div class="g4fr-header">
    <div>
      <h1>Diagnóstico</h1>
      <p>Teste conexão, sessão de API e resolução de Search Options por tipo de item.</p>
    </div>
    <span class="g4fr-muted">Acesso administrativo</span>
  </div>

  <div class="g4fr-card g4fr-grid">
    <div class="g4fr-field">
      <label>Perfil de API</label>
      <select id="g4fr-diag-profile" class="form-select">
        <?php foreach ($profiles as $id => $profile): ?>
          <option value="<?php echo htmlspecialchars($id); ?>"><?php echo htmlspecialchars((string)($profile['name'] ?? $id)); ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="g4fr-field">
      <label>Itemtype para Search Options</label>
      <input id="g4fr-diag-itemtype" class="form-control" value="Ticket" placeholder="Ticket, Project, ProjectTask, User">
    </div>
    <div class="g4fr-field g4fr-actions">
      <button type="button" id="g4fr-diag-test" class="btn btn-primary">Testar conexão</button>
      <button type="button" id="g4fr-diag-options" class="btn btn-outline-secondary">Listar Search Options</button>
    </div>
  </div>

  <pre id="g4fr-diag-output" class="g4fr-pre"></pre>
</div>
<script>window.G4FREPORTS = { api_url: 'api.php' };</script>
<script><?php $js = dirname(__DIR__) . '/assets/js/diagnostics.js'; if (is_file($js)) { readfile($js); } ?></script>
<?php
if (class_exists('Html')) Html::footer();
