# 贡献指南 (Contributing Guide)

感谢你对 GEOFlow 语义缓存增强版的关注！

## 项目说明

本项目是基于 [GEOFlow](https://github.com/yaojingang/GEOFlow) 的二次开发版本，主要新增了 **AI 语义缓存**功能。

**原项目作者**: Yao Jingang (姚金刚)  
**原项目地址**: https://github.com/yaojingang/GEOFlow  
**许可协议**: Apache License 2.0

## 新增功能

详见 [CHANGELOG.md](CHANGELOG.md)。

核心功能：
- AI 语义缓存系统（基于 pgvector 向量相似度检索）
- 完整的后台管理界面
- TTL + LRU 失效策略
- 优雅降级与容错机制

## 开发环境要求

- PHP 8.2+
- PostgreSQL 14+ with pgvector extension
- Composer
- Node.js & npm (for frontend assets)

## 本地开发设置

```bash
# 克隆项目
git clone [your-repo-url]
cd GEOFlow-main

# 安装依赖
composer install
npm install && npm run build

# 环境配置
cp .env.example .env
php artisan key:generate

# 数据库迁移
php artisan migrate

# 启动开发服务器
php artisan serve
```

## 运行测试

```bash
# 运行所有测试
php artisan test

# 仅运行语义缓存测试
php artisan test --filter SemanticCacheServiceTest
```

## 代码风格

本项目遵循 PSR-12 编码标准。运行 Pint 进行代码格式化：

```bash
./vendor/bin/pint
```

## 提交规范

建议使用清晰的提交信息：

```
feat: 添加语义缓存统计接口
fix: 修复缓存过期判断逻辑
docs: 更新 README 中的配置说明
test: 添加 LRU 淘汰机制测试
```

## 问题报告

如果发现 bug 或有功能建议，请：
1. 检查是否已有相关 issue
2. 提供详细的复现步骤
3. 附上环境信息（PHP、PostgreSQL、pgvector 版本）

## 许可协议

本项目遵循原项目的 Apache License 2.0 协议。所有新增功能在相同协议下发布。

详见 [LICENSE](LICENSE) 文件。

## 致谢

- 感谢 [GEOFlow](https://github.com/yaojingang/GEOFlow) 原作者提供的优秀基础框架
- 感谢 pgvector 团队提供的 PostgreSQL 向量扩展
