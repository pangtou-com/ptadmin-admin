@extends("ptadmin-install::layouts.base")

@section('content')
    <style>
        .install-welcome-content {
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .install-welcome-content iframe {
            flex: 1 1 auto;
            min-height: 0;
        }
    </style>
    <div class="install-welcome-content">
        <iframe
            id="protocol-frame"
            title="{{ __('ptadmin::install.agreement_title') }}"
            style="width:100%;border:1px solid #dbe4ef;border-radius:16px;background:#fff;"
        ></iframe>
    </div>
@endsection

@section('button')
    <div class="button-row">
        <label class="install-checkbox">
            <input type="checkbox" id="accept" value="yes">
            <span>{{ __('ptadmin::install.agreement_accept') }}</span>
        </label>
        <button type="button" id="next" class="install-button install-button-primary is-disabled" disabled>{{ __('ptadmin::install.next') }}</button>
    </div>
@endsection

@section("script")
<script>
    (function () {
        const accept = document.getElementById('accept');
        const next = document.getElementById('next');
        const protocolFrame = document.getElementById('protocol-frame');
        const protocolView = @json(app()->getLocale() === 'en' ? 'ptadmin-install::install_protocols_en' : 'ptadmin-install::install_protocols');
        const protocolHtml = @json(view(app()->getLocale() === 'en' ? 'ptadmin-install::install_protocols_en' : 'ptadmin-install::install_protocols')->render());

        if (protocolFrame) {
            protocolFrame.srcdoc = protocolHtml;
        }

        accept.addEventListener('change', function () {
            next.disabled = !accept.checked;
            next.classList.toggle('is-disabled', !accept.checked);
        });

        next.addEventListener('click', function () {
            if (!accept.checked) {
                return;
            }

            const form = document.createElement('form');
            form.method = 'POST';
            form.action = @json(route('ptadmin.install.accept'));

            const redirect = document.createElement('input');
            redirect.type = 'hidden';
            redirect.name = 'redirect';
            redirect.value = @json($redirect ?? route('ptadmin.install.requirements'));

            form.appendChild(redirect);
            document.body.appendChild(form);
            form.submit();
        });
    })();
</script>
@endsection
