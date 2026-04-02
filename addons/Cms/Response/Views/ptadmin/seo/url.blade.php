<form class="layui-form ptadmin-url-config-form ptadmin-seo-config-form" action="">
    <x-hint>
        <div><strong>URL配置</strong></div>
        <p>1、提示测试数据</p>
        <p>2、提示测试数据</p>
    </x-hint>
    <div class="layui-form-item">
        <label class="layui-form-label">访问模式：</label>
        <div class="layui-input-block">
            <input type="radio" name="access_type" value="1" title="伪静态化"
                   @if(blank($seoConfigData) || !isset($seoConfigData['access_type']) || $seoConfigData['access_type'] !== 2) checked @endif>
            <input type="radio" name="access_type" value="2" title="静态页面"
                   @if(isset($seoConfigData['access_type']) && $seoConfigData['access_type'] === 2) checked @endif>
        </div>
    </div>
    <div class="layui-form-item">
        <label class="layui-form-label">缓存处理：</label>
        <div class="layui-input-block">
            @foreach(\Addon\Cms\Enum\CacheHandlerTypeEnum::getMaps() as $key => $val)
                <input type="checkbox" name="cache_processing[{{$key}}]" title="{{$val}}" value="{{$key}}"
                    @if(isset($seoConfigData['cache_processing']) && ($key & $seoConfigData['cache_processing'])) checked @endif>
            @endforeach
        </div>
    </div>

    <fieldset class="layui-elem-field layui-field-title">
        <legend>详细配置</legend>
    </fieldset>
    <main class="ptadmin-url-config-main">
        <aside class="ptadmin-url-tabs">
            <div class="layui-this">频道路由：</div>
            <div>列表路由：</div>
            <div>详情路由：</div>
            <div>专题路由：</div>
            <div>单页：</div>
            <div>标签：</div>
        </aside>
        <section class="ptadmin-url-section">
            @foreach(\Addon\Cms\Enum\SEOEnum::getMaps() as $key => $val)
                <div class="layui-tab-item @if($key === 1) layui-show @endif">
                    <div class="layui-form-item">
                        <label class="layui-form-label">前置路由：</label>
                        <div class="layui-input-inline ptadmin-url-config">
                            <div class="url-input">
                                <input type="text" input-type="front" name="config[{{$key}}][pre_route]"
                                       class="layui-input"
                                       value="{{$seoConfigData['config'][$key]['pre_route'] ?? \Addon\Cms\Enum\SEOEnum::getSupportParams($key)['prefix']}}">
                            </div>
                        </div>
                    </div>
                    <div class="layui-form-item">
                        <label class="layui-form-label">访问地址：</label>
                        <div class="layui-input-block ptadmin-url-config">
                            <div class="url-input">
                                <div class="layui-input-group">
                                    <input type="text" name="config[{{$key}}][url]"
                                        placeholder="请输入测试URL格式(顺序可打乱，连接符号可随意填写)"
                                        class="layui-input"
{{--                                        value="{{data_get($seoConfigData, "config.{$key}.url",\Addon\Cms\Enum\SEOEnum::getSupportParams($key)['default_url'])}}">--}}
                                        value="{{$seoConfigData['config'][$key]['url'] ?? \Addon\Cms\Enum\SEOEnum::getSupportParams($key)['default_url']}}">
                                    <div class="layui-input-split layui-input-suffix url-demo" style="cursor: pointer;" >
                                        <i class="layui-icon layui-icon-eye"></i>
                                    </div>
                                </div>
                                <div class="generate">
                                    示例：{category} 或 {category}/{modName} 或 {category}_{modId}
                                </div>
                            </div>
                            <div class="tags">
                                <fieldset class="layui-elem-field layui-field-title">
                                    <legend>支持参数：</legend>
                                </fieldset>
                                <ul class="content">
                                    @foreach(\Addon\Cms\Enum\SEOEnum::getSupportParams($key)['params'] as $k => $title)
                                        <li class="item" title="{{$title}}">{<span>{{{$k}}}</span>}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="layui-form-item">
                        <label class="layui-form-label">标题格式：</label>
                        <div class="layui-input-block ptadmin-url-config">
                            <div class="url-input">
                                <div class="layui-input-group">
                                    <input type="text" name="config[{{$key}}][title]"
                                        placeholder="请输入测试URL格式" class="layui-input"
{{--                                        value="{{data_get($seoConfigData, "config.{$key}.title", $seoConfigData['config']['title'] ?? \Addon\Cms\Enum\SEOEnum::getSupportParams($key)['default_title'])}}">--}}
                                        value="{{$seoConfigData['config'][$key]['title'] ?? \Addon\Cms\Enum\SEOEnum::getSupportParams($key)['default_title']}}">
                                    <div class="layui-input-split layui-input-suffix format-demo" style="cursor: pointer;">
                                        <i class="layui-icon layui-icon-eye"></i>
                                    </div>
                                </div>
                                <div class="generate">
                                    示例：{category_title} 或 {category}_{site_title} 或 {page}_{category}_{site_title}
                                </div>
                            </div>
                            <div class="tags">
                                <fieldset class="layui-elem-field layui-field-title">
                                    <legend>支持参数：</legend>
                                </fieldset>
                                <ul class="content">
                                    @foreach(\Addon\Cms\Enum\SEOEnum::getSupportParams($key)['title_params'] as $k => $title)
                                        <li class="item" title="{{$title}}">{<span>{{{$k}}}</span>}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </section>
    </main>
    <!--  底部提交 -->
    <footer class="submit-footer layui-btn-group">
        <button type="button" class="layui-btn  layui-bg-blue" lay-submit lay-filter="submit-url">立即提交</button>
        <button type="reset" class="layui-btn">重置</button>
    </footer>

</form>
