# 本地维护操作

后续资源清理（2026-09-05）：按用户要求移除了测试与恢复环境的 4 个停止容器和 2 个专属网络。它们的 6 个数据卷仍保留；下文 `up -d --wait` 会重新创建容器与网络并使用原数据。日常 `nihonreach_wp_demo` 的 db/web/dev 保持运行，前台检查 HTTP 200。镜像仍为日常开发或维护所需，未执行全局清理。

所有命令在 `C:\dev\nihonreach-wordpress` 下执行。先打开 Docker Desktop。

本文 Docker/PowerShell 命令使用 **Windows PowerShell**。VS Code Dev Container 中的 Bash 用于 `php`、`composer`、`wp`；它没有 Docker socket，不能从内部控制 Docker。

VS Code 已配置 `shutdownAction: stopCompose`，关闭容器开发窗口会停止这组开发服务。通常下次 Reopen in Container 即可继续。若需从 Windows 手动启动包含开发工具的完整环境：

```powershell
docker compose -f compose.yaml -f .devcontainer/compose.devcontainer.yaml up -d --wait
docker compose -f compose.yaml -f .devcontainer/compose.devcontainer.yaml stop
```

普通 `docker compose up -d --wait` 仍可单独启动网站和数据库，供浏览器访问。

## 看状态、停止、重开

```powershell
docker compose ps
docker compose logs --tail=50 web
docker compose stop
docker compose up -d --wait
```

日志不应包含密码。不要把 `.env` 内容或完整 `docker inspect` 环境变量粘贴到公开资料。

## 备份

```powershell
pwsh -NoProfile -File scripts/backup.ps1
```

脚本先停止开发 Web，保持数据库服务可用，用 MySQL 容器原生 mysqldump 完成一致性导出，再打包 WordPress 文件和上传卷，打包源码与必要配置，写 SHA-256 清单，finally 中重新启动 Web。请勿在备份期间另开 CLI 写入数据库；本项目不支持并发写入下跨数据库与文件系统的分布式快照。

本地 `.env` 也备份在私有归档目录。用户名密码文件可由原 `.local/credentials.txt` 读取，用户与密码哈希则在数据库中。不要把归档移到 Web 根目录。

## 恢复与本次残留资源

首次可执行 `pwsh -NoProfile -File scripts/restore.ps1 -Backup 20260905-051036`。脚本核验哈希，检测恢复项目卷是否存在；任何已有恢复卷都会拒绝覆盖。本次已经有恢复实例，可用下面命令重开它：

```powershell
docker compose --env-file .local/restore.env -f compose.restore.yaml -p nihonreach_wp_restore up -d --wait
# 前台 http://127.0.0.1:8082/，后台 /wp-admin/
docker compose --env-file .local/restore.env -f compose.restore.yaml -p nihonreach_wp_restore stop
```

恢复数据库密码来自 `.local/restore.env`，与开发数据库不同。后台使用备份中的本地管理员。新地址通过支持序列化数据的 WP-CLI search-replace 替换，GUID 不改。

交付时测试、恢复各保留 3 个卷和专属网络，容器停止。没有删除这些资源，也没有全局清理。已有恢复副本里产品 ID 5 的型号为 `RESTORE-OK`，开发站仍为 `EM-06-4F`，这是隔离证据。

重开测试：

```powershell
docker compose --env-file .local/test.env -p nihonreach_wp_test up -d --wait
docker compose --env-file .local/test.env -p nihonreach_wp_test run --rm cli eval-file /work/tests/integration.php
docker compose --env-file .local/test.env -p nihonreach_wp_test stop
```

## 常见问题

- **Docker permission denied**：先确认 Docker Desktop 已启动；Codex 沙箱可能需要批准本项目的 Docker 命令。不是 WordPress 密码错误。
- **端口已使用**：不要停止陌生进程。先检查 8081 占用，再同时调整映射及 WordPress 地址。
- **上传目录不可写**：`docker compose exec -T web chown -R www-data:www-data /var/www/html/wp-content/uploads`。只针对本项目上传卷。
- **产品地址 404**：确认「插件」中 NRC 产品目录已启用；再在后台「设置」→「固定链接」点击保存，或执行 `docker compose run --rm cli rewrite flush`。
- **型号输入后没变**：看页面顶部红色提示；非法值不会覆盖旧型号。无型号字段的快速编辑也不会清除型号。
- **更新镜像后核心版本没变**：核心存于持久卷中，镜像重建不会自动覆盖。先备份，在副本更新核心、父主题或插件，验证后再处理开发站。

## 实际排错案例：数据库导出客户端

最初尝试用 WP-CLI 的 `db export`，其镜像内客户端是 MariaDB 11.8.8，而服务端是 MySQL 8.4.11。依次遇到参数解释、TLS 自签名证书和缺失 caching_sha2_password 插件问题。每次备份失败，finally 都重新启动开发 Web；没有成功清单的时间戳目录视作不完整备份，不可用于恢复。

最终处理：在 MySQL 数据库容器内部使用同版本 mysqldump/mysql，经本地 socket 连接，密码由环境变量读取，不进入命令输出；不更改服务端认证方式，不降低数据库 TLS 配置，不把数据库端口公开到宿主机。成功备份后已实际恢复验证。

## 更新范围

本次使用已核验的当前核心及父主题，组件清单未发现这些采用组件的可用更新，因此完成的是兼容性检查和故障恢复演练，没有虚构组件升级记录。自动更新关闭是本地复现设置；若未来部署生产，必须另行设计更新流程、HTTPS、备份保留和安全策略，当前任务不包含部署。
