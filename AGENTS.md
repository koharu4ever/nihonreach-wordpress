# 本项目约束
- 只修改当前 WordPress 项目，Laravel 目录只读。
- 禁止全局 Docker 清理、修改其他项目和无关生产资源。
- 用户已授权：创建独立私有 nihonreach-wordpress 仓库、提交/推送，并部署到 Coolify 的 wp.kral-koharu.com。仅作用于本项目的新资源，不覆盖 Laravel。实际凭据不写入 Git。
- 开发、测试和恢复分别使用 nihonreach_wp_demo、nihonreach_wp_test、nihonreach_wp_restore。
- 凭据、数据库备份、浏览器会话只能放被忽略的 .local 或 backups，不能写入日志。
- 测试结果必须来自实际执行；普通停止不删除卷。
