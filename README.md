# NihonReach WordPress Companion

中文精密工具目录求职作品，明确标注 **Portfolio Demo**。提供 12 款公开虚构产品、3 条权限测试产品、4 个分类、首页、目录与分页、详情、关于、联系说明、空状态和 404。无真实交易、客户信息或邮件发送。

## 先打开这里

服务器部署配置和步骤见 [Coolify 上线指南](docs/coolify-deployment.md)。本地开发与线上站点使用独立数据库。

- 项目目录：`C:\dev\nihonreach-wordpress`
- 前台：<http://127.0.0.1:8081/>
- 产品目录：<http://127.0.0.1:8081/products/>
- 后台：<http://127.0.0.1:8081/wp-admin/>
- 本地用户名和密码：用记事本打开 `.local/credentials.txt`。密码不在本文档中。
- 修改产品：登录后台 → 左侧「产品」→「所有产品」→ 点击产品名称 → 修改标题、正文或「产品型号」→ 右侧「更新」。
- 更换图片：产品编辑页右侧「特色图片」→ 点击图片 → 媒体库或上传 →「设置特色图片」→「更新」。

从首页点「探索产品目录」，再查看第二页、任意产品详情和「关于项目」。完整操作见 [新手指南](docs/beginner-guide.md)。

## 已实际验证

2026-09-05 在 Windows + Docker Desktop 上运行。开发实例保持运行；恢复与测试实例完成后停止，卷保留。

- 36 项真实 WordPress 集成断言通过，使用独立 8083 测试数据库，见 [输出](docs/integration-results.txt)。
- 自定义 PHP 语法通过；PHPCS 3.13.6 / WPCS 3.4.1 的选定安全规则通过。不是完整 WPCS 所有样式规则认证。
- 浏览器实际验证中文登录、合法型号保存、非法型号报错及原值保留、退出登录、分页、详情、空状态、404、手机 390px 布局及菜单。
- 完整备份成功，已恢复到全新 8082 副本；15 条产品、分类、状态、型号、媒体哈希和菜单一致；恢复账号能登录，新修改可保存；开发实例未被覆盖。
- 恢复副本实际完成插件停用 → 404 → 重新启用 → 200 的维护演练。

证据见 [验收](docs/acceptance.md)、[恢复演练](docs/restore-drill.md)、[HTTP 检查](docs/http-results.json) 和 [真实截图](docs/screenshots/README.md)。

## 从干净副本启动

前置条件：Windows、Docker Desktop（Linux 容器模式）、PowerShell 7；Docker 可分配至少约 3 GB 空闲内存。安装 PHP、Node 或数据库客户端到 Windows 全局环境不是必要步骤。首次需要联网下载官方镜像、中文语言包和父主题。

在 PowerShell 7 进入本项目：

```powershell
cd C:\dev\nihonreach-wordpress
pwsh -NoProfile -File scripts/setup.ps1
```

首次自动生成 `.env` 和 `.local/credentials.txt`，随机凭据仅保存在忽略目录。脚本初始化上传卷权限、安装中文核心与父主题、启用子主题与插件、写入演示内容。重复执行 seed 不重复灌入内容，也不会覆盖后来修改的产品。不要删除 `nrc_seed_v1` 标记来重置站点。

## 日常开发：像 Laravel 项目一样使用 Dev Container

当前已有站点时，不需要重新初始化。打开 Docker Desktop，然后：

1. VS Code →「文件」→「打开文件夹」→ `C:\dev\nihonreach-wordpress`。
2. 确保 VS Code 已安装微软的 **Dev Containers** 扩展（原 Laravel 工作流已经使用它）。
3. 按 `Ctrl+Shift+P`，选择 **Dev Containers: Reopen in Container（在容器中重新打开）**。
4. 首次会构建 `nihonreach-wordpress-dev:1.0.0` 工具镜像，并启动现有开发站的 db、web 和 dev。等待左下角显示 **Dev Container: NihonReach WordPress**。
5. 打开「终端」→「新建终端」。这是容器中的 Bash，项目路径是 `/work`。可以直接执行：

```bash
php -v
composer --version
wp core version
wp plugin list
php -l plugins/nrc-catalog/nrc-catalog.php
bash scripts/coding-check.sh
```

`wp-cli.yml` 让项目目录中的 `wp` 命令找到共享的 `/var/www/html` 安装。这个终端连接的是 **开发数据库**；独立数据库集成测试仍用下方的宿主机命令，不能在开发库直接运行。

编辑 `/work/plugins`、`/work/themes` 的代码即修改本机对应文件，刷新前台生效。产品内容与图片仍在原来的数据库/上传卷，不会重新生成。PHP、Composer 2.10.3、WP-CLI、Git 在开发容器中；本次没有向 Windows 全局安装这些工具。

开发容器复用 `nihonreach_wp_demo` 的网络和三个数据卷，不创建第二套日常数据库。没有 Docker socket 挂载，所以容器内不运行 `docker compose`。Docker 启停、PowerShell 备份和隔离集成测试命令在 **Windows PowerShell** 中运行；PHP/Composer/WP 命令在 **VS Code 容器终端**中运行。

Dev Container 的代码检查缓存放在容器用户目录 `/home/www-data/.cache/nrc-qa`，避免旧 root 工具容器生成的项目缓存产生权限冲突。重建开发容器时可重新下载这些工具依赖，不影响源码和产品数据。

关闭这个 Dev Container 窗口时，VS Code 会停止这组服务并保留数据，和原 NihonReach 的 `stopCompose` 行为一致。下次再次“在容器中重新打开”即可启动。只想浏览网站时，在 Windows 运行 `docker compose up -d --wait`，无需启动开发工具容器。

若修改了开发 Dockerfile，使用 **Dev Containers: Rebuild Container（重新生成容器）**。工具镜像中的基础 PHP/WP-CLI 和 Composer 固定版本，Debian 开发辅助包构建时取官方软件源；不是全镜像位级复现保证。容器重建可能重新安装 VS Code Server 和扩展，项目文件与站点数据保留。

默认端口是 8081，启动前可用 `netstat -ano | findstr :8081` 检查占用；如占用，请先修改 `.env` 的 HTTP_PORT，并把 `scripts/setup.ps1` 的开发端口及 bootstrap 地址一起调整。已经安装的站点还需用 WP-CLI `search-replace` 迁移旧地址，不能只改端口。当前交付已验证 8081 空闲并成功绑定。

全新演示副本请使用 `setup.ps1 -Environment test`，它只在没有初始化过的独立 8083 实例创建演示内容；已有测试卷会保留原内容。若需要另一个全新副本，应先明确新的项目名、端口和独立 `.env`，不要通过删除开发卷实现。

## 停止、启动和重建

以下 Docker 命令都在 **Windows PowerShell** 的本项目目录执行（不是 VS Code 容器内 Bash）：

```powershell
# 停止，保留数据库和图片
docker compose stop

# 再次启动
docker compose up -d --wait

# 重建容器，保留命名卷中的内容和图片
docker compose up -d --force-recreate --wait

# 查看状态
docker compose ps
```

不要在日常操作中加 `down -v`，也不要运行全局 prune。核心保存在 WordPress 卷里，换镜像并不会自动替换已有卷中的核心；升级核心应先在恢复副本使用 WP-CLI 更新并测试，再决定开发实例是否升级。

## 验证与备份命令

以下命令在 **Windows PowerShell** 执行。容器内仅做代码检查时，可直接使用 `bash scripts/coding-check.sh`。

```powershell
pwsh -NoProfile -File scripts/setup.ps1 -Environment test
docker compose --env-file .local/test.env -p nihonreach_wp_test run --rm cli eval-file /work/tests/integration.php
docker compose run --rm --user 0:0 -v "${PWD}:/work" --entrypoint sh cli /work/scripts/coding-check.sh
pwsh -NoProfile -File scripts/backup.ps1
```

备份会短暂停止开发 Web，自动重启；输出到 `backups/时间戳/`，其中有数据库、上传媒体、完整 WordPress 文件、源码、配置与 SHA-256 清单。备份含账号哈希和本地配置，应保留在本地私有位置；未挂载到 Web 目录，已在 `.gitignore` 排除。

首次恢复：`pwsh -NoProfile -File scripts/restore.ps1`。若已有恢复卷，脚本明确拒绝覆盖；本次交付已存在演练卷，因此重跑会停止并解释原因。重开现有恢复副本的方法见 [维护指南](docs/maintenance.md)。

## 谁负责什么、保存在哪里

| 部分 | 来源 / 实测版本 | 保存位置 |
|---|---|---|
| WordPress 核心 | 官方 Docker 镜像，7.1 | `nihonreach_wp_demo_wordpress` 卷 |
| PHP / Apache | 官方镜像，PHP 8.3.33 | 镜像，Web 容器内部 80 端口 |
| MySQL | 官方镜像，8.4.11 | 独立 database 卷，无宿主机数据库端口 |
| WP-CLI | 官方 CLI 镜像，2.12.0，PHP 8.3.33 | 临时工具容器，与 Web 共享站点卷及数据库配置 |
| 父主题 | 官方 Twenty Twenty-One 2.9 | WordPress 卷，可独立更新 |
| 子主题 | 本项目自定义，1.0.0 | `themes/nrc-child` 只读挂载 |
| 产品插件 | 本项目自定义，1.0.0 | `plugins/nrc-catalog` 只读挂载 |
| 本地限制插件 | 本项目 `infra/local-only.php` | mu-plugin 挂载，禁发真实邮件与自动更新 |
| 上传媒体 | 初始化复制 6 张原创 Demo 图；后来在后台上传 | 独立 uploads 卷 |
| 测试工具 | PHPCS 3.13.6 / WPCS 3.4.1 | `.cache/qa`，依赖锁定在 `tests/composer.lock` |

Compose 使用镜像标签 **加不可变 SHA-256 摘要**。核心、父主题、数据库要求核对自 [WordPress 官方下载说明](https://wordpress.org/download/) 和 [父主题页面](https://wordpress.org/themes/twentytwentyone/)；父主题最低要求 WP 5.3 / PHP 5.6，当前组合经过本项目实际测试。

镜像附带未激活的 Akismet 5.7.2、Hello Dolly 1.7.2，以及 Twenty Twenty-Three 1.7、Twenty Twenty-Four 1.6、Twenty Twenty-Five 1.5；它们不承担本站功能，没有接入第三方服务。

## 自定义工作与限制

产品 CPT、分类、型号校验、能力检查、nonce 校验、错误反馈和生命周期属于插件。首页、目录、详情、空状态、404 和响应式外观属于子主题；父主题文件没有修改。详情页会先检查 WordPress 密码保护，再展示型号、图片和正文。型号可空，非空限大写字母、数字、横杠，长度 1–32，不保证唯一性。

产品采用经典编辑器。页面采用 WordPress 原生页面编辑方式。型号和正文不是原子事务：非法型号保留旧值，但正文可能已更新；后台会明确提示。

图片使用公共媒体 URL，WordPress 的页面密码不是媒体文件加密或下载权限。不要上传敏感文件。首页主视觉文案在 `front-page.php`，后台首页正文不控制这段固定作品介绍。分类导航隐藏没有公开内容的空分类，但可直接访问空分类地址验证空状态。

核心为单篇页面输出 canonical；归档和归档分页没有额外添加 canonical，不把第二页错误指向第一页。当前是本机演示且设置搜索引擎不抓取，不宣称完整 SEO 优化。无 WooCommerce、订单、支付、会员、复杂表单、Headless 或 Laravel 同步。

与 Laravel NihonReach 只共享虚构场景和获准复用的 Demo 图片；目录、账号、数据库、网络和文件卷独立。它们仍共享本机 Docker、内存和磁盘，不是两台虚拟机。没有修改旧项目、提交 Git、推送或部署生产。

授权与来源见 [LICENSE](LICENSE) 和 [来源清单](docs/sources.md)。
