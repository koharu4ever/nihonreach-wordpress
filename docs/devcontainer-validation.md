# Dev Container 改造验证 · 2026-09-05

目的：与原 Laravel NihonReach 保持相同的 VS Code「Reopen in Container」操作方式，复用 WordPress 开发站。

## 已实际执行

- devcontainer.json JSON 解析通过；其 Windows initializeCommand 实际执行通过。初始化只检查 `.env`，不修改系统脚本执行策略、不安装宿主机依赖。
- 两份 Compose 合并配置验证通过，项目名仍为 `nihonreach_wp_demo`。
- 开发镜像 `nihonreach-wordpress-dev:1.0.0` 构建成功；Composer 安装器通过官方 SHA-384 校验，安装固定版本 2.10.3。
- db/web 保持原容器，新增 dev 服务成功启动；没有创建额外数据库。
- dev 内实际运行 post-create.sh 成功：PHP 8.3.33、Composer 2.10.3、WP-CLI 2.12.0；Git 2.47.3 可用。
- 工作用户 UID/GID 33:33，目录 `/work`；自定义源码可写；没有 `/var/run/docker.sock`。
- `wp option get home` 返回 `http://127.0.0.1:8081`。
- 启动前后产品、分类、型号、媒体哈希、菜单与首页数据快照完全一致；对比文件保存在忽略的 `.local/devcontainer-before.json` 和 `.local/devcontainer-after.json`。
- 前台目录 HTTP 200，9 张产品卡片。
- Bash 脚本语法检查通过；普通开发用户执行 `bash scripts/coding-check.sh`，锁定依赖安装及 PHPCS 选定规则检查通过，退出码 0。

## 实际修正的问题

旧 `.cache/qa` 由 root 工具容器创建，普通用户无法写入其文件。Dev Container 改用 `/home/www-data/.cache/nrc-qa`，复用镜像里已有的 Composer；保留原宿主机工具容器入口兼容性，没有给开发用户 root 权限。

## 保留的验证边界

已验证镜像、Compose、初始化命令、容器用户、工具和站点连接；没有操作用户的 VS Code 图形界面完成一次真正的 Reopen，也没有验证扩展市场下载或 VS Code Server 安装。首次在 VS Code 点击 Reopen 仍需等待其编辑器服务器及扩展初始化。

`shutdownAction: stopCompose` 已配置为关闭开发窗口时停止整组开发服务、保留卷。此 VS Code 生命周期行为依据配置，未冒称实际点击关闭窗口验收。
