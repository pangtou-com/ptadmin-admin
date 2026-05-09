<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>{{ __('ptadmin::install.title') }}</title>
    <style>
        :root {
            --install-bg-start: #0f4c81;
            --install-bg-end: #77a8d8;
            --install-card-bg: rgba(255, 255, 255, 0.96);
            --install-border: #dbe4ef;
            --install-muted: #6b7280;
            --install-text: #1f2937;
            --install-primary: #1d6fdc;
            --install-primary-hover: #1658ae;
            --install-secondary: #eef3f8;
            --install-secondary-hover: #dde7f2;
            --install-success: #1f8f53;
            --install-error: #c0392b;
            --install-info: #60758a;
            --install-shadow: 0 24px 60px rgba(15, 23, 42, 0.25);
        }

        * {
            box-sizing: border-box;
        }

        html, body {
            margin: 0;
            height: 100%;
            overflow: hidden;
            font-family: "PingFang SC", "Microsoft YaHei", sans-serif;
            color: var(--install-text);
            background:
                radial-gradient(circle at top left, rgba(255, 255, 255, 0.18), transparent 34%),
                linear-gradient(135deg, var(--install-bg-start), var(--install-bg-end));
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        .install-shell {
            height: 100vh;
            height: 100dvh;
            padding: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .install-card {
            width: min(1080px, 100%);
            height: calc(100vh - 32px);
            height: calc(100dvh - 32px);
            max-height: calc(100vh - 32px);
            max-height: calc(100dvh - 32px);
            border-radius: 24px;
            background: var(--install-card-bg);
            box-shadow: var(--install-shadow);
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .install-header {
            padding: 36px 40px 24px;
            background: linear-gradient(135deg, rgba(29, 111, 220, 0.12), rgba(255, 255, 255, 0.5));
        }

        .install-title {
            margin: 0;
            font-size: 34px;
            font-weight: 700;
            letter-spacing: 0.04em;
        }

        .install-subtitle {
            margin: 10px 0 0;
            font-size: 15px;
            color: var(--install-muted);
        }

        .install-steps {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 12px;
            padding: 10px 40px 24px;
            background: linear-gradient(135deg, rgba(29, 111, 220, 0.12), rgba(255, 255, 255, 0.2));
        }

        .install-step {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 16px;
            border-radius: 16px;
            background: rgba(255, 255, 255, 0.72);
            color: var(--install-muted);
        }

        .install-step.is-active {
            background: rgba(29, 111, 220, 0.12);
            color: var(--install-text);
        }

        .install-step.is-done {
            background: rgba(31, 143, 83, 0.12);
            color: var(--install-success);
        }

        .install-step-index {
            width: 34px;
            height: 34px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            font-weight: 700;
            background: rgba(148, 163, 184, 0.18);
        }

        .install-step.is-active .install-step-index {
            background: var(--install-primary);
            color: #fff;
        }

        .install-step.is-done .install-step-index {
            background: var(--install-success);
            color: #fff;
        }

        .install-step-title {
            font-size: 15px;
            font-weight: 600;
        }

        .install-content {
            flex: 1 1 auto;
            min-height: 0;
            padding: 32px 40px 20px;
            overflow-y: auto;
        }

        .install-footer {
            flex: 0 0 auto;
            min-height: 96px;
            padding: 16px 40px 24px;
            border-top: 1px solid rgba(219, 228, 239, 0.85);
            background: rgba(255, 255, 255, 0.68);
            display: flex;
            align-items: center;
        }

        .button-row {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .install-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 0;
            border-radius: 12px;
            padding: 11px 18px;
            min-width: 112px;
            min-height: 44px;
            font-size: 14px;
            line-height: 1;
            text-align: center;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.2s ease, color 0.2s ease, transform 0.2s ease;
        }

        .install-button:hover {
            transform: translateY(-1px);
        }

        .install-button-primary {
            background: var(--install-primary);
            color: #fff;
        }

        .install-button-primary:hover {
            background: var(--install-primary-hover);
        }

        .install-button-secondary {
            background: var(--install-secondary);
            color: var(--install-text);
        }

        .install-button-secondary:hover {
            background: var(--install-secondary-hover);
        }

        .install-button-warning {
            background: #f59e0b;
            color: #fff;
        }

        .install-button-warning:hover {
            background: #d97706;
        }

        .install-button[disabled],
        .install-button.is-disabled {
            cursor: not-allowed;
            opacity: 0.45;
            transform: none;
        }

        .install-checkbox {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            color: var(--install-muted);
        }

        .install-checkbox input {
            width: 18px;
            height: 18px;
        }

        .install-section {
            border: 1px solid var(--install-border);
            border-radius: 18px;
            padding: 22px 24px;
            background: rgba(255, 255, 255, 0.82);
        }

        .install-alert {
            margin-bottom: 20px;
            border-radius: 14px;
            padding: 14px 16px;
            font-size: 14px;
            line-height: 1.7;
        }

        .install-alert-error {
            border: 1px solid rgba(192, 57, 43, 0.28);
            background: rgba(192, 57, 43, 0.08);
            color: var(--install-error);
        }

        .install-alert-warning {
            border: 1px solid rgba(217, 119, 6, 0.28);
            background: rgba(245, 158, 11, 0.1);
            color: #b45309;
        }

        .install-section + .install-section {
            margin-top: 20px;
        }

        .install-section-title {
            margin: 0 0 18px;
            font-size: 18px;
            font-weight: 700;
        }

        .install-form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
        }

        .install-field {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .install-field.is-invalid .install-input,
        .install-field.is-invalid .install-select {
            border-color: var(--install-error);
            box-shadow: 0 0 0 4px rgba(192, 57, 43, 0.1);
        }

        .install-field-label {
            font-size: 14px;
            font-weight: 600;
        }

        .install-field-label .required {
            color: var(--install-error);
        }

        .install-input,
        .install-select {
            width: 100%;
            border: 1px solid #cbd5e1;
            border-radius: 12px;
            background: #fff;
            color: var(--install-text);
            min-height: 44px;
            padding: 10px 14px;
            font-size: 14px;
        }

        .install-input:focus,
        .install-select:focus {
            outline: none;
            border-color: var(--install-primary);
            box-shadow: 0 0 0 4px rgba(29, 111, 220, 0.12);
        }

        .install-table {
            display: grid;
            gap: 10px;
        }

        .install-table-head,
        .install-table-row {
            display: grid;
            grid-template-columns: 2fr 2fr 90px;
            gap: 12px;
            align-items: center;
            border-radius: 14px;
            padding: 12px 14px;
        }

        .install-table-head {
            background: var(--install-primary);
            color: #fff;
            font-size: 14px;
            font-weight: 700;
        }

        .install-table-row {
            border: 1px solid var(--install-border);
            background: rgba(255, 255, 255, 0.72);
            font-size: 14px;
        }

        .install-table-row.is-error {
            border-color: rgba(192, 57, 43, 0.3);
            background: rgba(192, 57, 43, 0.08);
        }

        .install-table-item-description {
            margin-top: 4px;
            color: var(--install-muted);
            font-size: 12px;
            line-height: 1.5;
        }

        .install-status {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            padding: 4px 10px;
            font-size: 12px;
            font-weight: 700;
        }

        .install-status-success {
            color: var(--install-success);
            background: rgba(31, 143, 83, 0.12);
        }

        .install-status-error {
            color: var(--install-error);
            background: rgba(192, 57, 43, 0.12);
        }

        .protocol-shell {
            line-height: 1.75;
            color: #374151;
            font-size: 14px;
        }

        .install-dialog-mask {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.62);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            padding: 16px;
        }

        .install-dialog-mask.is-visible {
            display: flex;
        }

        .install-dialog {
            width: min(780px, 100%);
            border-radius: 20px;
            overflow: hidden;
            background: #0f172a;
            color: #e2e8f0;
            box-shadow: 0 24px 60px rgba(15, 23, 42, 0.35);
        }

        .install-dialog-header {
            padding: 18px 22px;
            font-size: 16px;
            font-weight: 700;
            border-bottom: 1px solid rgba(148, 163, 184, 0.2);
        }

        .install-console {
            min-height: 320px;
            max-height: 420px;
            overflow-y: auto;
            padding: 18px 22px;
        }

        .install-console-item {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 12px;
            font-size: 13px;
            line-height: 1.7;
        }

        .install-console-badge {
            flex: 0 0 auto;
            border-radius: 999px;
            padding: 2px 9px;
            font-size: 12px;
            font-weight: 700;
        }

        .install-console-badge.process {
            background: rgba(29, 111, 220, 0.2);
            color: #93c5fd;
        }

        .install-console-badge.success {
            background: rgba(31, 143, 83, 0.2);
            color: #86efac;
        }

        .install-console-badge.error {
            background: rgba(192, 57, 43, 0.2);
            color: #fca5a5;
        }

        .install-console-badge.info {
            background: rgba(100, 116, 139, 0.2);
            color: #cbd5e1;
        }

        .install-dialog-actions {
            display: flex;
            justify-content: center;
            gap: 12px;
            padding: 0 22px 22px;
        }

        @media (max-width: 900px) {
            .install-shell {
                padding: 10px;
            }

            .install-card {
                height: calc(100vh - 20px);
                height: calc(100dvh - 20px);
                max-height: calc(100vh - 20px);
                max-height: calc(100dvh - 20px);
                border-radius: 18px;
            }

            .install-steps,
            .install-form-grid {
                grid-template-columns: 1fr;
            }

            .install-header,
            .install-steps,
            .install-content,
            .install-footer {
                padding-left: 18px;
                padding-right: 18px;
            }

            .install-content {
                padding-bottom: 16px;
            }

            .install-footer {
                min-height: 88px;
                padding-top: 14px;
                padding-bottom: 18px;
            }

            .install-table-head,
            .install-table-row {
                grid-template-columns: 1.6fr 1.6fr 72px;
            }
        }
    </style>
</head>
<body>
    <div class="install-shell">
        <div class="install-card">
            <div class="install-header">
                <h1 class="install-title">{{ __('ptadmin::install.heading') }}</h1>
                <p class="install-subtitle">{{ __('ptadmin::install.subtitle') }}</p>
            </div>
            <div class="install-steps">
                @foreach($tabs as $key => $val)
                    <div class="install-step @if($key === $step) is-active @elseif($key < $step) is-done @endif">
                        <span class="install-step-index">{{ $key + 1 }}</span>
                        <span class="install-step-title">{{ $val['title'] }}</span>
                    </div>
                @endforeach
            </div>
            <div class="install-content">
                @if(isset($installErrorMessage) && '' !== $installErrorMessage)
                    <div class="install-alert install-alert-error">{{ $installErrorMessage }}</div>
                @endif
                @yield('content')
            </div>
            <div class="install-footer">
                @yield('button')
            </div>
        </div>
    </div>
    @yield('script')
</body>
</html>
