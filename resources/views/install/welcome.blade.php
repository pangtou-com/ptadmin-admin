@extends("ptadmin-install::layouts.base")

@section('content')
  @include("ptadmin-install::install_protocols")
@endsection

@section('button')
    <div style="display: flex; justify-content: center;align-items: center;">
        <form class="layui-form" lay-filter="form-data">
            <div class="layui-form-item">
                <div class="layui-input-inline">
                    <input lay-filter="checkbox" value="yes" type="checkbox" name="accept" title="我已阅读，并同意协议">
                </div>
            </div>
        </form>
        <button type="button" id="next" class="layui-btn layui-btn-sm layui-bg-blue layui-btn-disabled">下一步</button>
    </div>
@endsection

@section("script")
<script>
    layui.use('form', function(){
        const form = layui.form
        const $ = layui.$

        form.on("checkbox(checkbox)", (data) => {
            const elem = data.elem;
            const checked = elem.checked;
            if (!checked) {
                $("#next").addClass("layui-btn-disabled")
            } else {
                $("#next").removeClass("layui-btn-disabled")
            }
        })

        $("#next").on('click', () => {
            const data = form.val('form-data')
            if (data['accept'] === 'yes') {
                location.href = '/install/requirements'
            }
        })
    })
</script>
@endsection
