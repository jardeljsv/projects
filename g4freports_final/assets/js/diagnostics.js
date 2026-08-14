(function () {
  function $(id) { return document.getElementById(id); }
  var apiUrl = (window.G4FREPORTS && window.G4FREPORTS.api_url) || 'api.php';
  function callApi(action, payload) {
    var url = new URL(apiUrl, window.location.href);
    url.searchParams.set('action', action);
    url.searchParams.set('payload', JSON.stringify(payload || {}));
    url.searchParams.set('_', String(Date.now()));
    return fetch(url.toString(), { method: 'GET', credentials: 'same-origin', cache: 'no-store', headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
      .then(function (res) { return res.text().then(function (txt) { var data; try { data = JSON.parse(txt); } catch (e) { data = { ok: false, error: txt || ('HTTP ' + res.status) }; } if (!res.ok || data.ok === false) throw new Error(data.error || ('HTTP ' + res.status)); return data; }); });
  }
  function print(x) { $('g4fr-diag-output').textContent = typeof x === 'string' ? x : JSON.stringify(x, null, 2); }
  document.addEventListener('DOMContentLoaded', function () {
    if ($('g4fr-diag-test')) $('g4fr-diag-test').addEventListener('click', function () {
      print('Testando conexão...');
      callApi('test_connection', { profile_id: $('g4fr-diag-profile').value }).then(print).catch(function (e) { print(e.message); });
    });
    if ($('g4fr-diag-options')) $('g4fr-diag-options').addEventListener('click', function () {
      print('Listando Search Options...');
      callApi('search_options', { profile_id: $('g4fr-diag-profile').value, itemtype: $('g4fr-diag-itemtype').value || 'Ticket' }).then(print).catch(function (e) { print(e.message); });
    });
  });
})();
