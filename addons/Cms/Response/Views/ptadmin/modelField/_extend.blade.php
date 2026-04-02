{{--单文本扩展字段--}}
<script type="text/html" id="text_html">
    {!! \PTAdmin\Build\Layui::number('setup[length]', "文本长度", $setup['length'] ?? 255) !!}
</script>

{{--下拉选择/单选/多选扩展字段--}}
<script type="text/html" id="select_html">
    <div class="layui-form-item">
        <label for="" class="layui-form-label">
            选项内容
        </label>
        <div class="layui-input-block" id="options_config">
            <input type="hidden" name="setup[type]" value="{{$setup['type'] ?? 'textarea'}}">
            <div class="layui-btn-group group-btn">
                <button type="button" class="layui-btn layui-btn-normal layui-btn-sm" data-type="textarea">多行文本</button>
                <button type="button" class="layui-btn layui-btn-primary layui-btn-sm" data-type="key-val">键值对</button>
                <button type="button" class="layui-btn layui-btn-primary layui-btn-sm" data-type="config">系统配置</button>
            </div>
            <div class="box">
                <div class="t-textarea">
                    <textarea name="setup[content]" class="layui-textarea" placeholder="每行一个选项，格式：key=val 或 val">{{$setup['content'] ?? ''}}</textarea>
                </div>
                <div class="t-key-val">
                    @if(isset($setup['data']) && $setup['data'] && is_array($setup['data']))
                        @foreach($setup['data'] as $key => $val)
                            <div class="ptadmin-key-val">
                                <div class="ptadmin-key">
                                    <input type="text" name="setup[key][]" placeholder="请输入键" value="{{$key}}" class="layui-input">
                                </div>
                                <div class="ptadmin-value">
                                    <input type="text" name="setup[value][]" placeholder="请输入值" value="{{$val}}" class="layui-input">
                                </div>
                                <div class="ptadmin-btn">
                                    @if($loop->first)
                                        <button type="button" class="layui-btn layui-btn-normal layui-btn-sm" ptadmin-event="key-add">
                                            <i class="layui-icon layui-icon-addition"></i>
                                        </button>
                                    @else
                                        <button type="button" class="layui-btn layui-btn-primary layui-btn-sm" style="margin-left: 0" ptadmin-event="key-del">
                                            <i class="layui-icon layui-icon-subtraction"></i>
                                        </button>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="ptadmin-key-val">
                            <div class="ptadmin-key">
                                <input type="text" name="setup[key][]" placeholder="请输入键" value="" class="layui-input">
                            </div>
                            <div class="ptadmin-value">
                                <input type="text" name="setup[value][]" placeholder="请输入值" value="" class="layui-input">
                            </div>
                            <div class="ptadmin-btn">
                                <button type="button" class="layui-btn layui-btn-normal layui-btn-sm" ptadmin-event="key-add">
                                    <i class="layui-icon layui-icon-addition"></i>
                                </button>
                            </div>
                        </div>
                    @endif
                </div>
                <div class="t-config">
                    <input type="text" name="setup[config]" placeholder="请输入config配置信息" value="{{$setup['config'] ?? ''}}" class="layui-input">
                </div>
            </div>
            <div class="layui-text-em">
                <p>1、多行文本：每行一个选项，格式：key=val， 当设置不存在 key时默认为0开始</p>
                <p>2、键值对：通过设置键值方式设置选项配置信息</p>
                <p>
                    3、系统配置：通过laravel【config方法】或系统配置获取选项配置信息，
                    优先基于【config方法】配置获取，当【config方法】无法获取在通过系统配置获取。
                    如需要指定系统配置可增加 `$` 符号,如：`$website.title`。 指定获取网站标题
                    当前只支持系统配置类型为【多行文本】格式的配置
                </p>
            </div>
        </div>
    </div>
    <style>
        .box>div{
            display: none;
        }
        .group-btn {
            margin-bottom: 10px;
        }
        .ptadmin-key-val{
            display: flex;
            align-items: center;
        }
        .t-key-val .ptadmin-key-val:first-child .layui-btn-primary{
            display: none;
        }
        .t-key-val .ptadmin-key-val:nth-child(2) .layui-btn-normal{
            display: none;
        }
        .ptadmin-key-val{
            margin-bottom: 10px;
        }
        .ptadmin-key-val>div{
            margin-right: 10px;
        }
    </style>
</script>

<script type="text/html" id="key_val_html">
    <div class="ptadmin-key-val">
        <div class="ptadmin-key">
            <input type="text" name="setup[key][]" placeholder="请输入键" value="" class="layui-input">
        </div>
        <div class="ptadmin-value">
            <input type="text" name="setup[value][]" placeholder="请输入值" value="" class="layui-input">
        </div>
        <div class="ptadmin-btn">
            <button type="button" class="layui-btn layui-btn-primary layui-btn-sm" style="margin-left: 0" ptadmin-event="key-del">
                <i class="layui-icon layui-icon-subtraction"></i>
            </button>
        </div>
    </div>
</script>

{{--时间日期格式--}}
<script type="text/html" id="datetime_html">
    <div class="layui-form-item">
        <label for="" class="layui-form-label">
            格式配置
        </label>
        <fieldset class="layui-elem-field">
        <div class="layui-field-box">
            <div class="datetime-format">
                <div class="item">
                    <div class="layui-text-em title">日期类型:</div>
                    <select name="date[type]" id="">
                        <option value="">请选择日期类型</option>

                    </select>
                </div>
                <div class="item">
                    <div class="layui-text-em title">是否为区间:</div>
                    <div>
                        <input
                            type="radio"
                            name="date[range]"
                            value="0"
                            title="否"
                            @if(data_get($setup, 'date.range', '0') === '0') checked @endif>

                        <input
                            type="radio"
                            name="date[range]"
                            value="1"
                            title="是"
                            @if(data_get($setup, 'date.range', '0') === '1') checked @endif>
                    </div>
                </div>
                <div class="item">
                    <div class="layui-text-em title">范围限制:</div>
                    <div class="datetime-limit">
                        <input type="text" name="date[min]" value="{{data_get($setup, 'date.min', '')}}" class="layui-input" placeholder="请输入最小日期">
                        <div class="layui-text-em">——</div>
                        <input type="text" name="date[max]" value="{{data_get($setup, 'date.max', '')}}" class="layui-input" placeholder="请输入最大日期">
                    </div>
                </div>
                <div class="item">
                    <div class="layui-text-em title">格式化:</div>
                    <div>
                        <input type="text" name="date[format]" value="{{data_get($setup, 'date.format', '')}}" class="layui-input" placeholder="请输入格式化方法">
                    </div>
                </div>
            </div>
        </div>
    </fieldset>
    </div>
    <style>
        .datetime-format{
            display: flex;
            flex-direction: row;
            flex-wrap: wrap;
            justify-content: space-between;
        }
        .datetime-format .item{
            width: 49%;
        }
        .datetime-format .item .title{
            padding: 5px 0;
        }
        .datetime-limit{
            display: flex;
            align-items: center;
        }
    </style>
</script>

{{--数字输入框--}}
<script type="text/html" id="number_html">
    {!! \PTAdmin\Build\Layui::number('min', "最小值", $setup['min'] ?? 0) !!}
    {!! \PTAdmin\Build\Layui::number('max', "最大值", $setup['max'] ?? 255) !!}
</script>

{{--上传配置--}}
<script type="text/html" id="upload_html">
    上传配置
</script>

{{--切换选项--}}
<script type="text/html" id="switches_html">
    <div class="layui-form-item">
        <label for="" class="layui-form-label">
            自定义title
        </label>
        <div class="layui-input-block">
            <input name="switch" class="layui-input" value="{{$setup['switch'] ?? '开启|关闭'}}" placeholder="通过 | 分隔符可设置两种状态下的不同标题"/>
        </div>
    </div>
</script>


