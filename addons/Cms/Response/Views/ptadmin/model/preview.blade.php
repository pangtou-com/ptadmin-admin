@extends('ptadmin.layouts.base')

@section("content")
    <div class="layui-fluid">
        <div class="layui-card-body">
            {!! $render !!}
        </div>
    </div>
@endsection

@section("script")
<script>
    layui.use(['PTForm'], function () {
        let { PTForm } = layui;
        PTForm.init();
    })
</script>
@endsection
