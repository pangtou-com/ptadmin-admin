@extends("ptadmin-install::layouts.base")

@section('content')
    <form id="install-form">
        <div id="install-form-alert" class="install-alert install-alert-warning" style="display: none;"></div>
        <div class="install-section">
            <h2 class="install-section-title">{{ __('ptadmin::install.sections.basic') }}</h2>
            <div class="install-form-grid">
                <label class="install-field">
                    <span class="install-field-label"><span class="required">*</span> {{ __('ptadmin::install.fields.app_url') }}</span>
                    <input type="text" name="app_url" value="{{ $url }}" placeholder="{{ __('ptadmin::install.placeholders.app_url') }}" autocomplete="off" class="install-input" required>
                </label>
                <label class="install-field">
                    <span class="install-field-label"><span class="required">*</span> {{ __('ptadmin::install.fields.app_name') }}</span>
                    <input type="text" name="app_name" value="PTAdmin" placeholder="{{ __('ptadmin::install.placeholders.app_name') }}" autocomplete="off" class="install-input" required>
                </label>
                <label class="install-field">
                    <span class="install-field-label"><span class="required">*</span> {{ __('ptadmin::install.fields.username') }}</span>
                    <input type="text" name="username" placeholder="{{ __('ptadmin::install.placeholders.username') }}" autocomplete="off" class="install-input" required>
                </label>
                <label class="install-field">
                    <span class="install-field-label"><span class="required">*</span> {{ __('ptadmin::install.fields.password') }}</span>
                    <input type="password" name="password" placeholder="{{ __('ptadmin::install.placeholders.password') }}" autocomplete="off" class="install-input" required minlength="6">
                </label>
                <label class="install-field">
                    <span class="install-field-label">{{ __('ptadmin::install.fields.web_prefix') }}</span>
                    <input type="text" name="ptadmin_web_prefix" value="{!! \Illuminate\Support\Str::random(8) !!}" autocomplete="off" class="install-input">
                </label>
                <label class="install-field">
                    <span class="install-field-label">{{ __('ptadmin::install.fields.api_prefix') }}</span>
                    <input type="text" name="ptadmin_api_prefix" value="{!! \Illuminate\Support\Str::random(8) !!}" autocomplete="off" class="install-input">
                </label>
            </div>
        </div>

        <div class="install-section">
            <h2 class="install-section-title">{{ __('ptadmin::install.sections.database') }}</h2>
            <div class="install-form-grid">
                <label class="install-field">
                    <span class="install-field-label"><span class="required">*</span> {{ __('ptadmin::install.fields.db_connection') }}</span>
                    <select name="db_connection" class="install-select" required>
                        <option value="">{{ __('ptadmin::install.placeholders.db_connection') }}</option>
                        <option value="mysql" selected>{{ __('ptadmin::install.database_options.mysql') }}</option>
                    </select>
                </label>
                <label class="install-field">
                    <span class="install-field-label"><span class="required">*</span> {{ __('ptadmin::install.fields.db_host') }}</span>
                    <input type="text" name="db_host" placeholder="{{ __('ptadmin::install.placeholders.db_host') }}" value="127.0.0.1" class="install-input" required>
                </label>
                <label class="install-field">
                    <span class="install-field-label"><span class="required">*</span> {{ __('ptadmin::install.fields.db_port') }}</span>
                    <input type="text" name="db_port" placeholder="{{ __('ptadmin::install.placeholders.db_port') }}" value="3306" class="install-input" required>
                </label>
                <label class="install-field">
                    <span class="install-field-label"><span class="required">*</span> {{ __('ptadmin::install.fields.db_database') }}</span>
                    <input type="text" name="db_database" value="pang_tou" placeholder="{{ __('ptadmin::install.placeholders.db_database') }}" autocomplete="off" class="install-input" required>
                </label>
                <label class="install-field">
                    <span class="install-field-label"><span class="required">*</span> {{ __('ptadmin::install.fields.db_username') }}</span>
                    <input type="text" name="db_username" placeholder="{{ __('ptadmin::install.placeholders.db_username') }}" autocomplete="off" class="install-input" required>
                </label>
                <label class="install-field">
                    <span class="install-field-label">{{ __('ptadmin::install.fields.db_password') }}</span>
                    <input type="text" name="db_password" placeholder="{{ __('ptadmin::install.placeholders.db_password') }}" autocomplete="off" class="install-input">
                </label>
                <label class="install-field">
                    <span class="install-field-label">{{ __('ptadmin::install.fields.db_prefix') }}</span>
                    <input type="text" name="db_prefix" value="pt_" placeholder="{{ __('ptadmin::install.placeholders.db_prefix') }}" autocomplete="off" class="install-input">
                </label>
            </div>
        </div>
    </form>
@endsection

@section('button')
    <div class="button-row">
        <a href="{{ route('ptadmin.install.requirements') }}" class="install-button install-button-secondary">{{ __('ptadmin::install.prev') }}</a>
        <button type="button" id="submit" class="install-button install-button-primary">{{ __('ptadmin::install.submit') }}</button>
    </div>
@endsection

@section('script')
    @include("ptadmin-install::_js")
@endsection
