{{--@pt:base::ad
<div>
    <div>这里是一个测试内容</div>
    <div><label>这是标题：</label>{$field.title}</div>
    <div><label>这是链接：</label><a href="{$field.links}">链接地址</a></div>
    <div><label>这是图片</label><img src="{$field.image}"></div>
</div>


@pt:end--}}


{{--@pt:base::link
<div>
    <div>这里是一个测试内容</div>
    <div><label>这是标题：</label>{$field.title}</div>
    <div><label>这是链接：</label><a href="{$field.url}">链接地址</a></div>
    <div><label>这是介绍：</label>{$field.intro}</div>
</div>

@pt:end--}}


{{--@pt:base::nav

<div>
    <div>这里是一个测试内容</div>
    <div><label>这是标题：</label>{$field.title}</div>
    <div><label>这是链接：</label><a href="{$field.url}">链接地址</a></div>
    <div><label>这是介绍：</label>{$field.intro}</div>
     @if(count($field['children']))
         <div><label>子菜单：</label>
             @foreach($field['children'] as $child)
                 <a href="{$child.url}">{$child.title}</a>
             @endforeach
     @endif
</div>
@pt:end--}}
@pt:cms::lists
<div>
    <label>{$field.title}</label>
    <label>{$field.subtitle}</label>
    <pre>
            {$field.data}
        </pre>
</div>
@pt:end

@pt:cms::page(active="active", class="page", layouts="home,prev,page,next,last,to,limit", align="center")
{!! $field !!}

@pt:end


