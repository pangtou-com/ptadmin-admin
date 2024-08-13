<div class="layui-card-header ptadmin-card-header">
    <div class="ptadmin-card-header-left">
        <div class="layui-btn-group">
            <button class="layui-btn layui-btn-sm" ptadmin-event="create">
                <i class="layui-icon layui-icon-addition"></i>
            </button>
            <button class="layui-btn layui-btn-sm layui-btn-disabled" ptadmin-multiple ptadmin-event="delete">
                <i class="layui-icon layui-icon-delete"></i>
            </button>
            <button class="layui-btn layui-btn-sm" ptadmin-event="reload">
                <i class="layui-icon layui-icon-refresh"></i>
            </button>
        </div>
    </div>
    <div class="ptadmin-card-header-right layui-hide-xs">
        <div class="layui-input-inline">
            <input type="text" placeholder="请输入关键词" value="" name="keywords" class="layui-input">
        </div>
        <button class="layui-btn layui-btn-sm layui-btn-primary" lay-event="keywords"><i class="layui-icon layui-icon-search"></i></button>
    </div>
</div>
