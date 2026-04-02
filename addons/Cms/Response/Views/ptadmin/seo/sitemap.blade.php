
<form class="layui-form ptadmin-seo-config-form ptadmin-sitemap-config-form" action="">
    <x-hint>
        <div><strong>sitemap配置</strong></div>
    </x-hint>

    <div class="layui-form-item">
        <label class="layui-form-label">地图类型</label>
        <div class="layui-input-block">
            <div class="ptadmin-config-map-type-item">
                <input type="checkbox" name="sitemap_type[xml]" title="xml地图" lay-filter="sitemap_type" value="1"
                       @if(isset($sitemapConfigData['sitemap_type']) && (\Addon\Cms\Enum\SitemapTypeEnum::XML_TYPE & $sitemapConfigData['sitemap_type'])) checked @endif>
                <div class="operate" show>
                    <a href="javascript:void(0)">https://demo.yiyocms.com/zy/sitemap.xml</a>
                    <a href="javascript:void(0)">手工更新</a>
                </div>
            </div>
            <div class="ptadmin-config-map-type-item">
                <input type="checkbox" name="sitemap_type[txt]"  lay-filter="sitemap_type" title="txt地图" value="2"
                       @if(isset($sitemapConfigData['sitemap_type']) && (\Addon\Cms\Enum\SitemapTypeEnum::TXT_TYPE & $sitemapConfigData['sitemap_type'])) checked @endif>
                <div class="operate">
                    <a href="javascript:void(0)">https://demo.yiyocms.com/zy/sitemap.txt</a>
                    <a href="javascript:void(0)">手工更新</a>
                </div>
            </div>
            <div class="ptadmin-config-map-type-item">
                <input type="checkbox" name="sitemap_type[html]"  lay-filter="sitemap_type" title="html地图" value="4"
                       @if(isset($sitemapConfigData['sitemap_type']) && (\Addon\Cms\Enum\SitemapTypeEnum::HTML_TYPE & $sitemapConfigData['sitemap_type'])) checked @endif>
                <div class="operate">
                    <a href="javascript:void(0)">https://demo.yiyocms.com/zy/sitemap.html</a>
                    <a href="javascript:void(0)">手工更新</a>
                    <span class="look-template">
                        （<a href="javascript:void(0)">查看模板</a><span class="text show">：/zy/public/html/sitemap.htm</span>）
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="layui-form-item">
        <label class="layui-form-label">自动更新</label>
        <div class="layui-input-block tips-input-block">
            <input type="radio" name="auto_update" value="1" title="开启" @if(blank($sitemapConfigData) || !isset($sitemapConfigData['auto_update']) || $sitemapConfigData['auto_update'] !== 0) checked @endif>
            <input type="radio" name="auto_update" value="0" title="关闭" @if(isset($sitemapConfigData['auto_update']) && $sitemapConfigData['auto_update'] === 0) checked @endif>
            <span  class="tips-layer" data-text="地图跟随文档一起更新">
                <i class="layui-icon layui-icon-question"></i>
            </span>
        </div>
    </div>

    <div class="layui-form-item">
        <label class="layui-form-label">过滤规则</label>
        <div class="layui-input-block">
            <input type="checkbox" name="filter_rule[0]" title="过滤隐藏栏目" value="1"
                   @if(isset($sitemapConfigData['filter_rule']) && (\Addon\Cms\Enum\FilterRuleEnum::HIDE_COLUMN & $sitemapConfigData['filter_rule'])) checked @endif>
            <input type="checkbox" name="filter_rule[1]" title="过滤外部链接" value="2"
                   @if(isset($sitemapConfigData['filter_rule']) && (\Addon\Cms\Enum\FilterRuleEnum::EXTERNAL_LINK & $sitemapConfigData['filter_rule'])) checked @endif>
        </div>
    </div>

    <div class="layui-form-item">
        <label class="layui-form-label">更新频率</label>
        <div class="linkage-box">
            <div class="layui-input-inline">
                <span class="label">首页</span>
                <select name="update_frequency[index]">
                    <option value="">请选择</option>
                    @foreach(\Addon\Cms\Enum\CmsUpdateFrequencyEnum::getMaps() as $key => $value)
                        <option value="{{$key}}" @if(isset($sitemapConfigData['update_frequency']['index']) && (int)$sitemapConfigData['update_frequency']['index'] === $key)selected @endif>{{$value}}</option>
                    @endforeach
                </select>
            </div>
            <div class="layui-input-inline">
                <span class="label">列表页</span>
                <select name="update_frequency[category]">
                    <option value="">请选择</option>
                    @foreach(\Addon\Cms\Enum\CmsUpdateFrequencyEnum::getMaps() as $key => $value)
                        <option value="{{$key}}" @if(isset($sitemapConfigData['update_frequency']['category']) && (int)$sitemapConfigData['update_frequency']['category'] === $key) selected @endif>{{$value}}</option>
                    @endforeach
                </select>
            </div>
            <div class="layui-input-inline">
                <span class="label">内容页</span>
                <select name="update_frequency[content]">
                    <option value="">请选择</option>
                    @foreach(\Addon\Cms\Enum\CmsUpdateFrequencyEnum::getMaps() as $key => $value)
                        <option value="{{$key}}" @if(isset($sitemapConfigData['update_frequency']['content']) && (int)$sitemapConfigData['update_frequency']['content'] === $key) selected @endif>{{$value}}</option>
                    @endforeach
                </select>
            </div>
            <div class="layui-form-mid layui-text-em">
                <span class="tips-layer" data-text="xml地图文件使用，你输入的网站的网页内容更新的频率">
                    <i class="layui-icon layui-icon-question"></i>
                </span>
            </div>
        </div>
    </div>

    <div class="layui-form-item">
        <label class="layui-form-label">优先级别</label>
        <div class="linkage-box">
            <div class="layui-input-inline">
                <span class="label">首页</span>
                <select name="priority_level[index]">
                    <option value="">请选择</option>
                    @foreach(\Addon\Cms\Enum\PriorityLevelEnum::getMaps() as $key => $value)
                        <option value="{{$key}}" @if(isset($sitemapConfigData['priority_level']['index']) && (int)$sitemapConfigData['priority_level']['index'] === $key) selected @endif>
                            {{\Addon\Cms\Enum\PriorityLevelEnum::getDescription($key)}}
                        </option>
                    @endforeach

                </select>
            </div>
            <div class="layui-input-inline">
                <span class="label">列表页</span>
                <select name="priority_level[category]">
                    <option value="">请选择</option>
                    @foreach(\Addon\Cms\Enum\PriorityLevelEnum::getMaps() as $key => $value)
                        <option value="{{$key}}" @if(isset($sitemapConfigData['priority_level']['category']) && (int)$sitemapConfigData['priority_level']['category'] === $key) selected @endif>
                            {{\Addon\Cms\Enum\PriorityLevelEnum::getDescription($key)}}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="layui-input-inline">
                <span class="label">内容页</span>
                <select name="priority_level[content]">
                    <option value="">请选择</option>
                    @foreach(\Addon\Cms\Enum\PriorityLevelEnum::getMaps() as $key => $value)
                        <option value="{{$key}}" @if(isset($sitemapConfigData['priority_level']['content']) && (int)$sitemapConfigData['priority_level']['content'] === $key) selected @endif>
                            {{\Addon\Cms\Enum\PriorityLevelEnum::getDescription($key)}}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="layui-form-mid layui-text-em">
                <span class="tips-layer" data-text="xml地图文件使用，所抓取页面在您网站的重要性，告诉搜索引擎抓取的优先级。数值越大，优先级越高。">
                    <i class="layui-icon layui-icon-question"></i>
                </span>
            </div>
        </div>
    </div>

    <div class="layui-form-item">
        <label class="layui-form-label">生成数据</label>
        <div class="create-data-box">
            <div class="layui-input-inline layui-input-group">
                <div class="layui-input-split layui-input-prefix"> 文档 </div>
                <input type="number" lay-affix="number" name="doc_num" value="{{$sitemapConfigData['doc_num'] ?? 100}}"  placeholder="带任意前置和后置内容" class="layui-input">
                <div class="layui-input-split layui-input-suffix"> 篇 </div>
            </div>

            <div class="layui-input-inline layui-input-group">
                <div class="layui-input-split layui-input-prefix"> TAG </div>
                <input type="number" lay-affix="number" name="tag_num" value="{{$sitemapConfigData['tag_num'] ?? 100}}"  placeholder="带任意前置和后置内容" class="layui-input">
                <div class="layui-input-split layui-input-suffix"> 篇 </div>
            </div>
            <div class="desc">文档加TAG数量不能超过5000条；数值大对更新文档速度有影响</div>
        </div>
    </div>

    <div class="layui-form-item">
        <label class="layui-form-label">百度推送</label>
        <div class="layui-input-block layui-input-group">
            <input type="text" placeholder="请输入" class="layui-input" name="baidu_push" value="{{$sitemapConfigData['baidu_push'] ?? ''}}">
            <span class="tips-layer layui-input-suffix"
            data-text="
                <img src='{{addon_asset('cms', 'images/baidu.jpg')}}'>">
                <i class="layui-icon layui-icon-question"></i>
            </span>
        </div>
        <div class="layui-input-block baidu-desc">
            在<a href="https://ziyuan.baidu.com/?castk=LTE%3D" target="_blank">百度搜索资源平台</a>获取token并填入，更新文档时主动推送给百度
        </div>
    </div>

    <!--  底部提交 -->
    <footer class="submit-footer layui-btn-group">
        <button type="button" class="layui-btn  layui-bg-blue" lay-submit lay-filter="submit-sitemap">立即提交</button>
        <button type="reset" class="layui-btn">重置</button>
    </footer>
</form>
