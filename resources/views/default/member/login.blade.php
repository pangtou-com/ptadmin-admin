@extends("default.common.base")
@section("title", "PTAdmin - 专业的后台管理系统")
@section("keywords", "PTAdmin,后台管理系统,后台管理模板,后台管理系统模板,后台管理系统开发,后台管理系统模板下载")
@section("description", "PTAdmin是一款专业的后台管理系统，基于Laravel框架开发，支持多种权限管理，支持多种插件，支持多种主题，支持多种语言，支持多种数据库，支持多种缓存，支持多种队列，支持多种文件存储，支持多种邮件发送，支持多种短信发送，支持多种支付接口，支持多种第三方登录，支持多种验证码，支持多种图形验证码，支持多种地图，支持多种富文本编辑器，支持多种Markdown编辑器，支持多种代码编辑器，支持多种文件管理器，支持多种数据库管理器，支持多种API接口，支持多种数据导入导出，支持多种数据备份恢复，支持多种数据迁移，支持多种数据清理，支持多种数据监控，支持多种数据分析，支持多种数据报表，支持多种数据图表，支持多种数据统计，支持多种数据分析，支持多种数据可视化，支持多种数据处理，支持多种数据加密解密，支持多种数据压缩解压，支持多种数据转换，支持多种数据验证，支持多种数据过滤，支持多种数据排序，支持多种数据搜索，支持多种数据筛选，支持多种数据分组，支持多种数据合并，支持多种数据拆分，支持多种数据比较，支持多种数据计算，支持多种数据转码，支持多种数据编码，支持多种数据解码，支持多种数据格式化，支持多种数据解析，支持多种数据序列化，支持多种数据反序列化，支持多种数据转义，支持多种数据反转义，支持多种数据加工，支持多种数据处理")
@section("content")
    <main class="main-box">
        <div class="my-web">
            <div class="title">网站搭建成功</div>
            <div style="padding-top: 20px">
                <a href="/member/center" type="button" class="layui-btn btn-bg">会员中心</a>
                <a href="{{admin_route("/login")}}" type="button" class="layui-btn">网站后台</a>
            </div>
        </div>
    </main>
@endsection

@section("script")
    <script>

    </script>
@endsection
