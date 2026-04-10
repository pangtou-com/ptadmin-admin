@extends("ptadmin-install::layouts.base")

@section('content')
    @foreach($results as $result)
        <div class="install-section">
            <h2 class="install-section-title">{{ $result['title'] }}</h2>
            <div class="install-table">
                <div class="install-table-head">
                    <div>检测项</div>
                    <div>推荐配置</div>
                    <div>状态</div>
                </div>
                @if(isset($result['results']) && $result['results'])
                    @foreach($result['results'] as $item)
                        <div class="install-table-row @if(!$item['state']) is-error @endif">
                            <div>{{ $item['title'] }}</div>
                            <div>{{ $item['config'] }}</div>
                            <div>
                                @if($item['state'])
                                    <span class="install-status install-status-success">通过</span>
                                @else
                                    <span class="install-status install-status-error">失败</span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>
        </div>
    @endforeach
@endsection

@section('button')
    <div class="button-row">
        <button type="button" id="pre" class="install-button install-button-secondary">上一步</button>
        <button type="button" id="reload" class="install-button install-button-warning">重新检测</button>
        <button type="button" id="next" class="install-button install-button-primary">下一步</button>
    </div>
@endsection

@section('script')
<script>
    (function () {
        document.getElementById('pre').addEventListener('click', function () {
            window.location.href = @json(route('ptadmin.install.welcome'));
        });

        document.getElementById('reload').addEventListener('click', function () {
            window.location.reload();
        });

        document.getElementById('next').addEventListener('click', function () {
            window.location.href = @json(route('ptadmin.install.environment'));
        });
    })();
</script>
@endsection
