# 部署到 Coolify

目标域名：`https://wp.kral-koharu.com`。原 Laravel 项目独立保留。

## 理解三个文件

- `.devcontainer/devcontainer.json`：VS Code 本地开发入口，不部署到服务器运行。
- `Dockerfile`：把 WordPress 核心、父主题、自定义代码和 PHP 打包成生产镜像。
- `compose.production.yaml`：让 Coolify 同时运行网站和独立 MySQL，并保留数据库与上传图片两个卷。

产品文字保存在数据库，图片保存在 uploads 卷；Git 管理代码。重新部署代码不会自动同步本地产品，也不会重置线上产品。首次线上使用新的管理员和虚构演示内容。

## Coolify 操作

1. 在域名 DNS 中添加 `wp` 的 A 记录，指向 VPS 公网 IPv4；有正确 IPv6 才添加 AAAA。已有通配符记录可复用。
2. 仓库现已公开。新部署可添加 Public Repository 资源，填写 `https://github.com/koharu4ever/nihonreach-wordpress`。现有站点最初通过 GitHub App 部署，公开后可以继续保留该连接，无需重新创建应用。
3. 选择唯一的现有服务器、`main` 分支、Build Pack **Docker Compose**、Base Directory `/`、Docker Compose Location `/compose.production.yaml`。不要选本地 `compose.yaml`。
4. 环境变量按 `infra/production/coolify.env.example` 设置。数据库密码与 auth secret 必须随机生成且持续保留。管理员密码仅初始化时使用。凭据只作为运行环境变量，不勾选构建参数；关闭 Advanced 中的 Inject Build Args to Dockerfile。
   使用 Normal view 逐项填写示例中的 7 个变量并点击 Update。保留 Coolify 自动管理的 `SERVICE_URL_WEB`、`SERVICE_FQDN_WEB`；不要用示例文件替换整个变量列表，也不要修改 Preview 区域。
5. 在 web 服务域名栏填写 `https://wp.kral-koharu.com`。网站容器使用默认内部端口 80，Coolify 负责 HTTPS。数据库不填写域名，不映射主机端口。不连接其他项目的预定义网络。
6. 保存并 Deploy。第一次需要下载镜像、编译 PHP 扩展，耗时数分钟。两个服务运行内存上限合计 1.25 GiB；构建额外占用资源，应先确认服务器有余量。
7. 容器启动后，在 Coolify 的 web 容器 Terminal 执行：

   ```sh
   su -s /bin/sh www-data -c 'sh /opt/nrc/initialize.sh'
   ```

   如果终端已经是 www-data，直接执行 `sh /opt/nrc/initialize.sh`。初始化成功后会提示完成；重复执行遇到已安装站点会退出，不覆盖产品或账号。
8. 打开前台和 `/wp-admin/` 登录，验证产品和图片。将管理员密码保存在密码管理器后，在 Coolify 将 `NRC_ADMIN_PASSWORD` 的值清空并 Update、重新部署；保留变量名，避免触发 Compose 变量删除限制。管理员登录密码仍保留在 WordPress 数据库中。

## 日常使用与维护

前台：`https://wp.kral-koharu.com/`；后台：`https://wp.kral-koharu.com/wp-admin/`。修改产品：后台 → 产品 → 所有产品 → 编辑 → 更新；特色图片用于替换产品图。

修改代码：VS Code 打开本项目并 Reopen in Container；本地验证后提交、推送，在 Coolify 部署新版本。后台禁用代码编辑和在线插件/主题安装；版本更新通过受控镜像完成。所有真实邮件被禁用，密码忘记时需用容器内 WP-CLI 重设。

上线后必须为数据库和 uploads 配置异地备份。代码仓库不是数据库备份。现有 `scripts/backup.ps1`、`restore.ps1` 针对本地开发环境，不能直接当线上恢复脚本使用。回滚代码可部署旧 commit；数据库结构变更回滚需备份配合。不要删除 Coolify 持久卷。

生产镜像的本地验证入口：先 `docker build -t nihonreach-wordpress:production-local .`，再用 PowerShell 7 执行 `pwsh -NoProfile -File scripts/Test-Production.ps1`。脚本创建随机命名的独立测试栈，验证后删除此次测试的容器与卷，不触碰开发数据。

参考：[Coolify Docker Compose 官方文档](https://coolify.io/docs/applications/build-packs/docker-compose)、[域名与容器端口](https://coolify.io/docs/knowledge-base/domains)。
