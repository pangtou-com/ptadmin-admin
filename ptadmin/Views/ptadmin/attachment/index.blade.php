@extends('ptadmin.layouts.base')

@section("content")
<div class="layui-card ptadmin-page-container">
    <div class="layui-card-body">
        <table id="dataTable" lay-filter="dataTable"></table>
    </div>
</div>
<script id="titleHtml" type="text/html">
    <a href="@{{ d.url }}" target="_blank" style="color: #4397fd;">
        @{{d.title}}
    </a>
</script>
@endsection

@section("script")
<script>
    layui.use(['PTPage', 'common'], function() {
        let { PTPage } = layui;
        PTPage.make({
            urls: {
                del_url: "{{admin_route('attachment')}}/{id}",
            },
            btn_left: ['refresh', 'del'],
            table: [
                {type: 'checkbox', width: 50},
                {field: 'id', title: 'ID', width: 80},
                {field: 'title', title: '{{ __("table.attachments.title") }}', templet: "#titleHtml"},
                {field: 'mime', title: '{!! __("table.attachments.mime") !!}'},
                {field: 'size', title: '{!! __("table.attachments.size") !!}'},
                {field: 'driver', title: '{!! __("table.attachments.driver") !!}'},
                {field: 'groups', title: '{!! __("table.attachments.groups") !!}'},
                {fixed: 'right', width: 80, title: '{{ __("system.btn_handle") }}', align: 'center', operate: ['del']},
            ]
        });
    })
</script>
@endsection
