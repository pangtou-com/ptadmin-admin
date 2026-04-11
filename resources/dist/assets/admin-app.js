(function () {
    var root = document.getElementById('ptadmin-root');
    var config = window.__PTADMIN__ || {};

    if (!root) {
        return;
    }

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function buildRuntimeItem(title, value, copyable) {
        var html = ''
            + '<div class="ptadmin-runtime-item">'
            + '<strong>' + escapeHtml(title) + '</strong>'
            + '<div class="ptadmin-runtime-value">'
            + '<code>' + escapeHtml(value) + '</code>';

        if (copyable) {
            html += '<button class="ptadmin-copy" type="button" data-copy="' + escapeHtml(value) + '">复制</button>';
        }

        html += '</div></div>';

        return html;
    }

    function buildStatusCard(title, value) {
        return ''
            + '<div class="ptadmin-status-card">'
            + '<strong>' + escapeHtml(value) + '</strong>'
            + '<span>' + escapeHtml(title) + '</span>'
            + '</div>';
    }

    var appName = config.appName || 'PTAdmin';
    var webBase = config.webBase || '/';
    var apiBase = config.apiBase || '/';
    var assetBase = config.assetBase || '/';
    var loginPath = config.loginPath || '/login';
    var uploadPath = config.uploadPath || '/upload';
    var userResourcesPath = config.userResourcesPath || '/user/resources';

    root.innerHTML = ''
        + '<div class="ptadmin-shell">'
        + '<section class="ptadmin-hero">'
        + '<div class="ptadmin-hero-inner">'
        + '<div class="ptadmin-hero-copy">'
        + '<span class="ptadmin-badge"><span class="ptadmin-badge-dot"></span>PTAdmin Admin Runtime Ready</span>'
        + '<h1>' + escapeHtml(appName) + '</h1>'
        + '<p class="ptadmin-hero-desc">后台首页已完成挂载。当前入口页会根据后端运行时配置自动注入前端入口、接口前缀、资源地址与登录路径，适合直接作为发布包默认壳页面使用。</p>'
        + '<div class="ptadmin-actions">'
        + '<a class="ptadmin-action" href="' + escapeHtml(webBase) + '">打开后台入口</a>'
        + '<a class="ptadmin-action-secondary" href="' + escapeHtml(loginPath) + '">查看登录接口</a>'
        + '<button class="ptadmin-copy" type="button" data-copy="' + escapeHtml(apiBase) + '">复制接口前缀</button>'
        + '</div>'
        + '<div class="ptadmin-status-list">'
        + buildStatusCard('前端入口', webBase)
        + buildStatusCard('接口前缀', apiBase)
        + buildStatusCard('资源目录', assetBase)
        + '</div>'
        + '</div>'
        + '<div class="ptadmin-meta-grid">'
        + '<div class="ptadmin-meta-card"><span class="ptadmin-meta-label">后台入口路径</span><span class="ptadmin-meta-value">' + escapeHtml(webBase) + '</span></div>'
        + '<div class="ptadmin-meta-card"><span class="ptadmin-meta-label">登录接口路径</span><span class="ptadmin-meta-value">' + escapeHtml(loginPath) + '</span></div>'
        + '<div class="ptadmin-meta-card"><span class="ptadmin-meta-label">上传接口路径</span><span class="ptadmin-meta-value">' + escapeHtml(uploadPath) + '</span></div>'
        + '<div class="ptadmin-meta-card"><span class="ptadmin-meta-label">菜单资源接口</span><span class="ptadmin-meta-value">' + escapeHtml(userResourcesPath) + '</span></div>'
        + '</div>'
        + '</div>'
        + '</section>'
        + '<section class="ptadmin-grid">'
        + '<div class="ptadmin-panel">'
        + '<div class="ptadmin-panel-head">'
        + '<div><h2>运行态信息</h2><p>用于快速核对当前发布包是否按预期挂载。</p></div>'
        + '</div>'
        + '<div class="ptadmin-runtime-list">'
        + buildRuntimeItem('后台前端入口', webBase, true)
        + buildRuntimeItem('后台接口前缀', apiBase, true)
        + buildRuntimeItem('登录接口', loginPath, true)
        + buildRuntimeItem('上传接口', uploadPath, true)
        + buildRuntimeItem('前端资源目录', assetBase, true)
        + buildRuntimeItem('菜单资源接口', userResourcesPath, true)
        + '</div>'
        + '</div>'
        + '<div class="ptadmin-panel">'
        + '<div class="ptadmin-panel-head">'
        + '<div><h2>默认能力</h2><p>当前后台包已经内置的基础模块与运行能力。</p></div>'
        + '</div>'
        + '<div class="ptadmin-capability-list">'
        + '<div class="ptadmin-capability-item"><strong>管理员与权限</strong><p>内置后台管理员、角色、资源菜单、授权状态与登录日志基础能力。</p></div>'
        + '<div class="ptadmin-capability-item"><strong>系统配置与资源管理</strong><p>已包含系统配置分组、配置项、上传接口、资源库与运行时前端配置注入。</p></div>'
        + '<div class="ptadmin-capability-item"><strong>插件与扩展接入</strong><p>支持通过 ptadmin/addon 对接扩展能力，安装后可继续挂载插件后台与微前端资源。</p></div>'
        + '<div class="ptadmin-capability-item"><strong>动态模型支撑</strong><p>运行环境会结合 ptadmin/easy 提供模型定义、版本与审计相关的数据层支撑。</p></div>'
        + '</div>'
        + '</div>'
        + '</section>'
        + '<div class="ptadmin-footer">PTAdmin Admin static shell • runtime configured by ptconfig.js</div>'
        + '</div>';

    root.addEventListener('click', function (event) {
        var target = event.target;
        var text;

        if (!target || !target.getAttribute) {
            return;
        }

        text = target.getAttribute('data-copy');
        if (!text) {
            return;
        }

        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text);
            target.textContent = '已复制';
            setTimeout(function () {
                target.textContent = '复制';
            }, 1200);
        }
    });
})();
