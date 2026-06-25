# Semantic GEOFlow

一个面向 AI 内容生成场景的语义缓存增强型 GEO 内容工程平台。

项目围绕“批量 AI 内容生成成本高、相似请求重复调用模型、内容分发链路分散”这类问题，提供从素材管理、知识库召回、AI 文章生成、审核发布到多站点分发的完整流程，并重点实现了基于 embedding 的语义缓存能力，用于复用相似 AI 生成结果，降低 token 成本和响应延迟。

## 项目定位

这是一个偏工程化的 AI 内容生产与分发系统，适用于：

- SEO / GEO / AEO 内容批量生成
- 跨境独立站、企业官网、内容站的文章生产
- 多关键词、多标题、多 prompt 的批量改写场景
- 知识库增强生成与内容审核发布
- 多站点内容分发、任务队列和生成日志管理

项目重点不是简单调用大模型，而是把 AI 生成能力接入一个可管理、可配置、可追踪的业务流程中。

## 核心功能

- 内容素材管理：支持素材、标题、关键词、图片、作者、分类等基础内容资产管理
- AI 文章生成：支持通过 OpenAI-compatible provider 或 Gemini 等模型生成内容
- 知识库召回：支持知识库内容切片、embedding 召回和生成增强
- 语义缓存：对相同或语义相近的 prompt 复用历史生成结果
- 后台审核：生成内容可在后台查看、编辑、审核和发布
- 多站点分发：支持分发到 Agent 站点、WordPress REST API 或通用 HTTP API
- 队列与日志：支持任务队列、分发记录、站点访问和基础统计

## 技术栈

- PHP 8.2+
- Laravel 12
- PostgreSQL / pgvector
- Redis
- Laravel Horizon
- Laravel Reverb
- Vite
- Docker Compose

## 技术亮点

### 1. AI 语义缓存机制

传统缓存通常只能处理 key 完全一致的情况，但 AI 内容生成场景中，很多 prompt 文本不同但语义接近，例如：

- “生成一篇蓝牙耳机选购指南”
- “写一篇无线耳机购买建议”

如果每次都重新调用大模型，会造成不必要的 token 消耗和等待时间。本项目通过 embedding 向量相似度实现语义级缓存复用。

### 2. 双层匹配策略

缓存命中分为两层：

1. 精确匹配：对 prompt 计算 SHA-256 hash，相同 prompt 直接命中缓存。
2. 语义匹配：对 prompt 生成 embedding，在 PostgreSQL + pgvector 中检索相似向量，达到阈值后复用历史结果。

这种设计兼顾了精确缓存的稳定性和语义缓存的灵活性。

### 3. 可配置的缓存控制

语义缓存支持多项配置：

- 是否启用语义缓存
- 相似度阈值
- 缓存 TTL
- 最大缓存条目数
- 过期清理
- 命中次数统计

在实际业务中，可以根据内容风险和成本目标调整阈值。例如内容要求更严谨时提高阈值，追求降本时适当放宽阈值。

### 4. 生成链路集成

语义缓存不是一个独立 demo，而是接入了完整内容生成流程：

1. 用户提交生成任务
2. 系统在调用 AI 前查询语义缓存
3. 命中缓存时直接返回历史生成结果
4. 未命中时调用 AI 模型生成内容
5. 生成完成后写入缓存，供后续相似请求复用

这样可以在不改变用户使用流程的前提下优化成本和响应速度。

### 5. 工程化后台管理

后台提供语义缓存相关管理能力：

- 缓存列表
- 缓存详情
- 命中统计
- 过期状态
- 手动清理
- 配置展示

这让缓存机制不仅停留在代码层，也能被运营或管理员观察和维护。

## 面试中可以怎么介绍

可以用下面这段作为 30 秒项目介绍：

> 这个项目是一个基于 Laravel 的 AI 内容生成和多站点分发系统。我主要实现了 AI 语义缓存模块，用 SHA-256 精确匹配加 embedding 相似度匹配来复用历史生成结果。技术上使用 PostgreSQL + pgvector 存储 prompt 向量，并在内容生成链路前后完成缓存查询和写入。同时我做了后台管理、命中统计、TTL、容量上限和单元测试。它解决的是批量 AI 内容生成中重复调用大模型导致成本高、响应慢的问题。

如果面试官追问“为什么不用普通 Redis 缓存”，可以这样回答：

> Redis 更适合 key 完全一致的缓存，但 AI 生成场景里很多 prompt 是语义相近但文本不同，普通 key-value 缓存无法命中。所以我用 embedding 做语义相似度匹配，让相近请求也能复用结果。Redis 仍然可以用于队列、会话或热点数据，而 pgvector 更适合语义召回。

如果面试官追问“这个方案有什么风险”，可以这样回答：

> 最大风险是相似但不等价的 prompt 被错误复用，所以我做了相似度阈值控制，并保留了开关配置。另一个风险是高维向量在数据量大时检索性能下降，后续可以通过低维 embedding、向量索引或分桶策略优化。

## 本地启动

### 常规方式

```bash
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate --seed
npm install
npm run build
php artisan serve
```

### 开发模式

```bash
composer run dev
```

### Docker 启动

```bash
cp .env.example .env
docker compose up -d --build
```

默认访问地址：

```text
http://localhost:18080
```

## 默认后台账号

默认账号配置在 `.env.example` 中：

```text
GEOFLOW_ADMIN_USERNAME=admin
GEOFLOW_ADMIN_EMAIL=admin@example.com
GEOFLOW_ADMIN_PASSWORD=password
```

公开部署前请务必修改默认账号和密码。

## 重要配置

- `APP_URL`：应用访问地址
- `ADMIN_BASE_PATH`：后台路径
- `GEOFLOW_CACHE_ENABLED`：是否启用缓存
- `GEOFLOW_CACHE_TTL`：缓存有效期
- `GEOFLOW_HTTP_PROXY` / `GEOFLOW_HTTPS_PROXY`：AI 服务出站代理
- `GEOFLOW_UPDATE_CHECK_ENABLED`：是否启用版本检查

AI 模型供应商配置可在后台管理界面中维护。

## 测试

```bash
composer test
```

## 目录结构

```text
app/                    应用核心代码
routes/                 路由定义
database/               数据库迁移与 Seeder
resources/              Blade 页面与前端资源
public/                 静态资源
docs/                   部署与分发说明
tests/                  单元测试
```

## License

Apache License 2.0. See [LICENSE](LICENSE).
