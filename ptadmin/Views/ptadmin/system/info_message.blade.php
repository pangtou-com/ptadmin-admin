<form action="{{admin_route('system/info')}}" id="form" class="layui-form" method="post"
      style="width: 100%;height: 900px">
    @csrf
    @method('post')
    <div class="layui-row">
        基本资料
    </div>
    <div class="layui-row">
        <div class="layui-col-xs6">
            <div class="layui-form-item">
                <label class="layui-form-label">账号</label>
                <div class="layui-input-block">
                    <input type="text" autocomplete="off" class="layui-input" readonly="readonly"
                           value="{{ $dao->username }}">
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">昵称<span class="red">*</span></label>
                <div class="layui-input-block">
                    <input type="text" name="nickname" lay-verify="required" placeholder="请输入昵称" autocomplete="off"
                           class="layui-input" value="{{ $dao->nickname }}">
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">联系电话</label>
                <div class="layui-input-block">
                    <input type="text" name="mobile" placeholder="请输入联系方式" autocomplete="off" class="layui-input"
                           value="{{ $dao->mobile }}">
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">电子邮箱</label>
                <div class="layui-input-block">
                    <input type="email" name="email" placeholder="请输入电子邮箱" autocomplete="off" class="layui-input"
                           value="{{ $dao->email }}">
                </div>
            </div>
        </div>
        <div class="layui-col-xs6">
            <div class="layui-form-item">
                <label class="layui-form-label">头像</label>
                <div class="layui-input-block">
                    <button type="button" class="layui-btn" id="upload-avatar-btn">
                        <i class="layui-icon layui-icon-upload"></i> 头像上传
                    </button>
                    <div style="width: 132px;">
                        <div class="layui-upload-list">
                            <img class="layui-upload-img" id="upload-avatar-img" style="width: 100%; height: 92px;"
                                 src="{{ empty($dao->avatar)?'':url(\Illuminate\Support\Facades\Storage::url($dao->avatar)) }}">
                            <input type="hidden" name="avatar" class="avatar_path" value="{{ $dao->avatar }}">
                        </div>
                        <div class="layui-progress layui-progress-big" lay-showPercent="yes" lay-filter="filter-demo">
                            <div class="layui-progress-bar" lay-percent=""></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="layui-row">
        <div class="layui-row">
            <div class="layui-col-md2 layui-col-md-offset1">
                <div class="layui-btn-container">
                    <button type="submit" class="layui-btn layui-btn-info layui-btn-radius" lay-submit
                            lay-filter="PT-submit">提交
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>
