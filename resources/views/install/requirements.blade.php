@extends("ptadmin-install::layouts.base")

@section('content')
    @if(isset($allPassed) && !$allPassed)
        <div class="install-alert install-alert-warning">
            {{ __('ptadmin::install.requirements_failed') }}
            @if(!empty($failedItems))
                {{ __('ptadmin::install.requirements_failed_items') }}
                @foreach($failedItems as $index => $item)
                    {{ $item['group'] }} / {{ $item['title'] }}@if($index + 1 < count($failedItems))；@endif
                @endforeach
            @endif
        </div>
    @endif

    @foreach($results as $result)
        <div class="install-section">
            <h2 class="install-section-title">{{ $result['title'] }}</h2>
            <div class="install-table">
                <div class="install-table-head">
                    <div>{{ __('ptadmin::install.table.item') }}</div>
                    <div>{{ __('ptadmin::install.table.config') }}</div>
                    <div>{{ __('ptadmin::install.table.status') }}</div>
                </div>
                @if(isset($result['results']) && $result['results'])
                    @foreach($result['results'] as $item)
                        <div class="install-table-row @if(!$item['state']) is-error @endif">
                            <div>{{ $item['title'] }}</div>
                            <div>{{ $item['config'] }}</div>
                            <div>
                                @if($item['state'])
                                    <span class="install-status install-status-success">{{ __('ptadmin::install.table.passed') }}</span>
                                @else
                                    <span class="install-status install-status-error">{{ __('ptadmin::install.table.failed') }}</span>
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
        <button type="button" id="pre" class="install-button install-button-secondary">{{ __('ptadmin::install.prev') }}</button>
        <button type="button" id="reload" class="install-button install-button-warning">{{ __('ptadmin::install.reload') }}</button>
        <button
            type="button"
            id="next"
            class="install-button install-button-primary @if(isset($allPassed) && !$allPassed) is-disabled @endif"
            @if(isset($allPassed) && !$allPassed) disabled @endif
        >{{ __('ptadmin::install.next') }}</button>
    </div>
@endsection

@section('script')
<script>
    (function () {
        const allPassed = @json($allPassed ?? false);

        document.getElementById('pre').addEventListener('click', function () {
            window.location.href = @json(route('ptadmin.install.welcome'));
        });

        document.getElementById('reload').addEventListener('click', function () {
            window.location.reload();
        });

        document.getElementById('next').addEventListener('click', function () {
            if (!allPassed) {
                return;
            }

            window.location.href = @json(route('ptadmin.install.environment'));
        });
    })();
</script>
@endsection
