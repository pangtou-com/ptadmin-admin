@extends("ptadmin-install::layouts.base")

@section('content')
    <form id="install-form">
        <div class="install-section">
            <h2 class="install-section-title">基础信息</h2>
            <div class="install-form-grid">
                <label class="install-field">
                    <span class="install-field-label"><span class="required">*</span> 网站地址</span>
                    <input type="text" name="app_url" value="{{ $url }}" placeholder="请输入网站地址" autocomplete="off" class="install-input">
                </label>
                <label class="install-field">
                    <span class="install-field-label"><span class="required">*</span> 网站标题</span>
                    <input type="text" name="app_name" value="PTAdmin管理系统" placeholder="请输入网站标题" autocomplete="off" class="install-input">
                </label>
                <label class="install-field">
                    <span class="install-field-label"><span class="required">*</span> 登录账户</span>
                    <input type="text" name="username" placeholder="请输入管理员登录账户" autocomplete="off" class="install-input">
                </label>
                <label class="install-field">
                    <span class="install-field-label"><span class="required">*</span> 登录密码</span>
                    <input type="password" name="password" placeholder="请输入管理员登录密码" autocomplete="off" class="install-input">
                </label>
                <label class="install-field">
                    <span class="install-field-label">后台页面路径</span>
                    <input type="text" name="ptadmin_web_prefix" value="{!! \Illuminate\Support\Str::random(8) !!}" autocomplete="off" class="install-input">
                </label>
                <label class="install-field">
                    <span class="install-field-label">后台接口路径</span>
                    <input type="text" name="ptadmin_api_prefix" value="{!! \Illuminate\Support\Str::random(8) !!}" autocomplete="off" class="install-input">
                </label>
            </div>
        </div>

        <div class="install-section">
            <h2 class="install-section-title">数据库设置</h2>
            <div class="install-form-grid">
                <label class="install-field">
                    <span class="install-field-label">数据库</span>
                    <select name="db_connection" class="install-select">
                        <option value="">请选择数据库</option>
                        <option value="mysql" selected>Mysql</option>
                    </select>
                </label>
                <label class="install-field">
                    <span class="install-field-label">主机地址</span>
                    <input type="text" name="db_host" placeholder="请输入主机地址" value="127.0.0.1" class="install-input">
                </label>
                <label class="install-field">
                    <span class="install-field-label">数据库端口</span>
                    <input type="text" name="db_port" placeholder="请输入数据库端口" value="3306" class="install-input">
                </label>
                <label class="install-field">
                    <span class="install-field-label">数据库名称</span>
                    <input type="text" name="db_database" value="pang_tou" placeholder="请输入数据库名称" autocomplete="off" class="install-input">
                </label>
                <label class="install-field">
                    <span class="install-field-label">数据库账户</span>
                    <input type="text" name="db_username" placeholder="请输入数据库账户" autocomplete="off" class="install-input">
                </label>
                <label class="install-field">
                    <span class="install-field-label">数据库密码</span>
                    <input type="text" name="db_password" placeholder="请输入数据库密码" autocomplete="off" class="install-input">
                </label>
                <label class="install-field">
                    <span class="install-field-label">数据表前缀</span>
                    <input type="text" name="db_prefix" value="pt_" placeholder="请输入数据表前缀" autocomplete="off" class="install-input">
                </label>
            </div>
        </div>
    </form>
@endsection

@section('button')
    <div class="button-row">
        <a href="{{ route('ptadmin.install.requirements') }}" class="install-button install-button-secondary">上一步</a>
        <button type="button" id="submit" class="install-button install-button-primary">确认安装</button>
    </div>
@endsection

@section('script')
    @include("ptadmin-install::_js")
@endsection
