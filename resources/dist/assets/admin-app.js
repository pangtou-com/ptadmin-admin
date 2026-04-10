(function () {
    var root = document.getElementById('ptadmin-root');
    var config = window.__PTADMIN__ || {};

    if (!root) {
        return;
    }

    root.innerHTML = ''
        + '<div class="ptadmin-shell">'
        + '<h1>' + (config.appName || 'PTAdmin') + '</h1>'
        + '<p>后台前端发布产物已挂载。当前页面前缀为 <strong>' + (config.webBase || '/') + '</strong>，接口前缀为 <strong>' + (config.apiBase || '/') + '</strong>。</p>'
        + '</div>';
})();
