#!/bin/bash
# set -x

# 设置基本变量
REPO_PATH=$(pwd)  # 自动获取当前目录作为仓库路径
MASTER_BRANCH="main"
GITHUB_REPO="git@github.com:pangtou-com/ptadmin-admin.git"  # 替换为你的GitHub仓库地址
COMPOSER_JSON_PATH="$REPO_PATH/composer.json"  # composer.json的路径
INDEX_PATH="$REPO_PATH/public/index.php"  # index 文件路径
NEW_VERSION="" # 新版本号


error() {
    # 定义颜色
    RED='\033[0;31m'
    NC='\033[0m' # No Color
    echo -e "${RED}"
    echo -e "================================================"
    echo -e "$1"
    echo -e "================================================${NC}"
    echo -e "${NC}"
    exit 1
}

# 前置检测
check_release_batch() {
    # 检测是否有未提交的变更
    status=$(git status --porcelain)
    if  [ -n "$status" ]; then
        error "当前工作区有未提交变更，发布流程终止。$status"
    fi

    # 检测分支是否是main分支
    current_branch=$(git rev-parse --abbrev-ref HEAD)
    if [ "$current_branch" != "$MASTER_BRANCH" ]; then
        error "当前分支【 $current_branch 】，发布需要分支【 $MASTER_BRANCH 】发布流程终止。"
    fi

    # 判断是否属于发布仓库
    remote_url=$(git config --get remote.github.url)
    if [ "$remote_url" != "$GITHUB_REPO" ]; then
        error "当前仓库未定义GitHub仓库，发布流程终止。"
    fi
}

# 2、检查当前分支与上一个版本是否有变化
get_last_tag() {
    latest_tag=$(git describe --tags --abbrev=0)
    if [ $? -eq 0 ]; then
        git diff --exit-code "$latest_tag"..HEAD > /dev/null
        if [ $? -eq 0 ]; then
            echo "当前分支与上一个版本没有变化，取消发布。"
            exit 1
        fi
    fi
    echo "$latest_tag"
}

# 格式化版本号
format_version() {
    local version=$1
    local -a parts=($(echo "$version" | tr '.' ' '))

    # 检查所有部分是否都为数字
    for value in "${parts[@]}"; do
       if ! [[ "$value" =~ ^(0|[1-9][0-9]*)$ ]]; then
           error "错误: 版本信息必须是0或非零开头的正整数"
       fi
    done
     # 如果只有一个数字，补全其他部分
    while [ ${#parts[@]} -lt 3 ]; do
        parts+=("0")
    done
    if [[ ${parts[0]} -eq 0 && ${parts[1]} -eq 0 && ${parts[2]} -eq 0 ]]; then
        error "错误: 版本信息格式不正确"
    fi

    # 格式化输出
    echo "v${parts[0]}.${parts[1]}.${parts[2]}"
}

# 定义函数用于更新版本号
update_version() {
    local current_version=$1
    local update_type=$2
    local new_version

    case $update_type in
        "patch")
            new_version=$(echo "$current_version" | awk -F. -v OFS=. '{$NF++;print}')
            ;;
        "minor")
            new_version=$(echo "$current_version" | awk -F. -v OFS=. '{++$2; $3=0; print}')
            ;;
        "major")
            new_version=$(echo "$current_version" | awk -F. -v OFS=. '{++$1; $2=0; $3=0;print}')
            ;;
        *)
            error "未知的更新类型: $update_type"
            ;;
    esac

    echo "$new_version"
}

# 创建tag
create_tag(){
    # 询问发布版本号信息
    latest_tag=$1
    options=(
        "patch：补丁发布"
        "minor：次版本发布"
        "major：主版本发布"
        "custom：自定义版本号"
    )
    PS3="请选择一个选项并输入对应的数字: "
    # shellcheck disable=SC2034
    select opt in "${options[@]}"; do
        case $REPLY in
            1)
                new_version=$(echo "$latest_tag" | awk -F. -v OFS=. '{$NF++;print}')
                break
                ;;
            2)
                new_version=$(echo "$latest_tag" | awk -F. -v OFS=. '{++$2; $3=0; print}')
                break
                ;;
            3)
                new_version=$(echo "$latest_tag" | awk -F. -v OFS=. '{++$1; $2=0; $3=0;print}')
                break
                ;;
            4)
                read -rp "请输入发布版本号： " new_version
                new_version=$(format_version "$new_version")
                if [ $? -ne 0 ]; then
                    error "版本信息错误，请输入正确的版本信息"
                fi
                break
                ;;
            *)
                error "无效的选项"
                break
                ;;
        esac
    done
    if [ -z "$new_version" ]; then
        error "版本信息错误，请输入正确的版本信息"
    fi
    NEW_VERSION="$new_version"
}

# 3. 生成git tag
handle_tag(){
    # 询问发布版本号信息
    latest_tag=$(get_last_tag)
    if [ $? -ne 0 ]; then
        error "获取标签失败。$latest_tag"
    fi

    create_tag "$latest_tag"
    if [ $? -ne 0 ]; then
        error "获取标签失败。$NEW_VERSION"
    fi

    # 更新composer.json版本信息
    sed -i '' "s/\"version\": \".*\"/\"version\": \"$NEW_VERSION\"/" "$COMPOSER_JSON_PATH"
    sed -i '' "s/ PTADMIN_FRAME_VERSION = \'.*\'/ PTADMIN_FRAME_VERSION = \'$NEW_VERSION\'/" "$INDEX_PATH"

    if [ $? -ne 0 ]; then
        error "更新composer.json版本信息失败。$NEW_VERSION 最新标签内容：$latest_tag"
    fi

    # 提交更改
    git add "$COMPOSER_JSON_PATH" "$INDEX_PATH"
    git commit -m "fix: Update version to $NEW_VERSION"
    if [ $? -ne 0 ]; then
        error "提交更新失败。"
    fi
}

publish_tag(){
    check_release_batch
    handle_tag
    if [ $? -ne 0 ]; then
        error "发布失败。标签内容是：$NEW_VERSION"
    fi

    git tag "$NEW_VERSION"
    if [ $? -ne 0 ]; then
        printf "生成git tag失败。标签内容是：\n"
        error "$new_version"
    fi
    # 推送到远程仓库
    git push github "$MASTER_BRANCH"
    git push origin "$MASTER_BRANCH"
    # 发布内部版本
    git push origin "$NEW_VERSION"
    # 发布github版本
    git push github "$NEW_VERSION"
    if [ $? -ne 0 ]; then
        error "推送git tag失败。"
    fi
}


publish_tag
