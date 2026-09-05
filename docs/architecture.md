# 结构与运行逻辑

请求流程：浏览器 → 本机 8081 → WordPress Apache/PHP 容器内部 80 → WordPress 主查询 → 产品插件约束查询 → 子主题模板 → HTML。数据库地址只有网络内服务名 `db`，不存在宿主机数据库映射。

## 隔离清单

| 用途 | Compose 项目名 | 本机地址 | 卷前缀 / 网络 |
|---|---|---|---|
| 日常开发 | nihonreach_wp_demo | 127.0.0.1:8081 | nihonreach_wp_demo_* / nihonreach_wp_demo_default |
| 集成测试 | nihonreach_wp_test | 127.0.0.1:8083 | nihonreach_wp_test_* / nihonreach_wp_test_default |
| 恢复演练 | nihonreach_wp_restore | 127.0.0.1:8082 | nihonreach_wp_restore_* / nihonreach_wp_restore_default |

每套拥有 database、wordpress、uploads 三个命名卷。不加入 Laravel 网络、不使用旧数据库或 Mailpit、不挂 Docker socket、不挂完整用户配置目录。测试账号从本地测试配置初始化，数据库独立；恢复数据库密码重新随机生成，恢复的 WordPress 用户账号来自备份。

## 挂载与文件来源

新增 `.devcontainer/devcontainer.json` 使用现有 compose.yaml 加专用覆盖文件，固定 Compose 项目名 `nihonreach_wp_demo`，让 VS Code 连接 `dev` 服务。这个服务使用相同 PHP 8.3.33 基础镜像，加 Git、Composer 2.10.3 和官方 WP-CLI，采用 Debian/glibc 以兼容 VS Code Server。

dev 用 UID/GID 33:33（www-data），避免与 Web/CLI 的站点卷权限冲突；不使用 root 作为编辑用户，不挂载 Docker socket。`/work` 是本机项目目录的可写挂载；`/var/www/html` 是原站点卷，自定义代码在这个安装路径仍只读。终端通过 wp-cli.yml 找到原安装。初始化脚本只检查已有站点，不导入数据、不改管理员密码、不自动运行数据库测试。

开发工具镜像与容器占用 Docker 存储。编辑器服务器/扩展和 Composer 缓存会产生本地 Docker 或项目缓存，环境隔离不等于零磁盘占用。dev 与 Web 共享开发数据库，所以在 dev 中运行写入型 WP 命令会改变开发内容。

官方镜像的核心位于 `/usr/src/wordpress`；第一次启动时入口脚本将其初始化到 WordPress 卷 `/var/www/html`。因此空卷不会永久遮住核心。Web 和 CLI 共享相同卷及数据库环境变量。

开发与测试把自定义插件和子主题分别只读挂到 `wp-content/plugins/nrc-catalog` 与 `wp-content/themes/nrc-child`；只覆盖这两个目录，不替换整个 wp-content。父主题和语言包安装在 WordPress 卷里。uploads 另挂命名卷，初次由 setup 将所有者设为 33:33。CLI 同样用 UID/GID 33 读写安装与上传内容。

恢复 Compose 不挂开发自定义源码：主题、插件、本地 mu-plugin 均从归档展开到恢复卷。这样停用或修改副本不会改到开发文件。数据库备份仅挂载到 db/cli 的 `/backups`，Web 无法通过 URL 获取。

## 模板层级

- 静态首页优先 `front-page.php`。
- 产品归档命中 `archive-nrc_product.php`。
- 分类命中 `taxonomy-nrc_category.php`，复用归档布局。
- 产品单篇命中 `single-nrc_product.php`。
- 普通页面使用 `page.php`，不存在的地址使用 `404.php`。
- 子主题未覆盖的部分回退父主题，包括 header、footer；保留 `wp_head()`、`wp_footer()` 与标准结构。

Twenty Twenty-One 2.9 的函数已经加载父 CSS，子主题仅在其句柄之后加载一次自己的 style.css。没有复制父主题 functions.php，也没有二次加载父 CSS。

## 插件保存逻辑

`nrc_register()` 注册 CPT、taxonomy 与受保护 meta `_nrc_model`。产品关闭 REST 编辑，强制经典编辑器。meta box 写入 nonce，save_post 回调依次检查对象类型、autosave/revision、对象编辑能力、nonce 类型及有效性，再区分字段缺失与清空。严格允许列表验证后才更新 meta。

非法值保留原型号，以当前用户 ID＋产品 ID 的短期 transient 保存错误；经典编辑页重定向回来后显示一次提示。没有声称正文与 meta 是原子事务。REST 不公开该字段；插件不提供绕过能力检查的额外写入接口。

主归档查询设置每页 9 条、已发布、没有密码。首页自己的 WP_Query 也明确约束。详情模板在任何图片/型号/分类/正文输出前调用 `post_password_required()`。激活/停用重建路由，停用不删除产品或 meta，也没有自动卸载清库脚本。

## 测试边界

真实 WordPress 集成脚本在 8083 地址以外拒绝运行；通过 WordPress 函数建立临时账号和产品，调用真实 save_post，并对 Web 容器发送匿名 HTTP 请求。不是模拟整个 WordPress，也不是 Laravel PHPUnit。编码检查覆盖输出转义、输入验证、SQL 和危险函数的选定规则；严格格式校验与核心生成的密码表单各有一条带理由的精确忽略。
