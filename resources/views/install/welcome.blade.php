@extends("ptadmin-install::layouts.base")

@section('content')
    @php
        $protocolHtml = str_replace(
            ['&', '"'],
            ['&amp;', '&quot;'],
            preg_replace("/[\r\n]+/", ' ', view('ptadmin-install::install_protocols')->render()) ?? ''
        );
    @endphp
    <iframe
        title="安装协议"
        style="width:100%;min-height:420px;border:1px solid #dbe4ef;border-radius:16px;background:#fff;"
        srcdoc="{{ $protocolHtml }}"
    ></iframe>
@endsection

@section('button')
    <div class="button-row">
        <label class="install-checkbox">
            <input type="checkbox" id="accept" value="yes">
            <span>我已阅读，并同意协议</span>
        </label>
        <button type="button" id="next" class="install-button install-button-primary is-disabled" disabled>下一步</button>
    </div>
@endsection

@section("script")
<script>
    (function () {
        const accept = document.getElementById('accept');
        const next = document.getElementById('next');

        accept.addEventListener('change', function () {
            next.disabled = !accept.checked;
            next.classList.toggle('is-disabled', !accept.checked);
        });

        next.addEventListener('click', function () {
            if (!accept.checked) {
                return;
            }

            window.location.href = @json(route('ptadmin.install.requirements'));
        });
    })();
</script>
@endsection
