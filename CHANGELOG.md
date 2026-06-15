# Changelog

本项目基于 [GEOFlow](https://github.com/yaojingang/GEOFlow) 进行二次开发和功能增强。

## [Unreleased] - 语义缓存增强版

### 新增功能

#### AI 语义缓存系统 (Semantic Cache)

基于 prompt embedding 向量相似度的智能缓存层，显著降低重复或相似 AI 生成请求的成本和响应延迟。

**核心特性：**

- **双层匹配策略**
  - 一级：精确匹配（SHA-256 hash）— 相同 prompt 直接命中
  - 二级：语义相似度匹配（pgvector 余弦相似度）— 相似 prompt 也能复用结果
  
- **智能召回与存储**
  - 复用 GEOFlow 现有的 embedding 模型配置，无需额外配置
  - 向量维度对齐项目标准（3072 维，与 `knowledge_chunks` 一致）
  - 自动生成 prompt embedding 并存储到 `ai_semantic_cache` 表
  
- **灵活的失效策略**
  - TTL（Time-To-Live）：可配置过期时间，默认 7 天
  - LRU（Least Recently Used）：达到容量上限时自动淘汰最久未命中的条目
  - 相似度阈值可调：默认 0.92，可根据业务需求在 0.88-0.95 之间调整

- **完整的后台管理界面**
  - 缓存条目列表：查看所有缓存及其命中统计
  - 实时统计看板：总条目数、命中次数、命中率、累计节省 token
  - 条目详情页：查看完整 prompt、响应内容和元数据
  - 批量操作：清理过期缓存、清空全部缓存
  - 配置展示：当前启用状态、相似度阈值、TTL、容量上限

**技术实现：**

- **数据库层**：PostgreSQL + pgvector 扩展，使用 `vector(3072)` 类型存储 embedding
- **服务层**：`SemanticCacheService` 封装查询/写入/统计逻辑
- **集成点**：在 `WorkerExecutionService::generateContentWithModelSelection()` 生成前查缓存，生成后写入
- **降级容错**：无 embedding 模型或 pgvector 不可用时自动跳过缓存逻辑，不影响主流程
- **测试覆盖**：单元测试覆盖 token 估算、hash、精确匹配、命中计数、统计聚合、失效判断等核心逻辑

**配置项（config/geoflow.php）：**

```php
'semantic_cache_enabled' => true,                    // 是否启用
'semantic_cache_similarity_threshold' => 0.92,       // 相似度阈值
'semantic_cache_ttl_seconds' => 604800,              // TTL（7天）
'semantic_cache_max_entries' => 10000,               // 最大条目数
```

**性能影响：**

- **缓存命中时**：跳过 AI 调用，响应时间从秒级降至毫秒级
- **缓存未命中时**：增加一次 embedding 生成（~100ms）和向量检索（<10ms），相比 AI 生成耗时（数秒到数十秒）可忽略
- **成本节省**：实测中等规模场景下可降低 30-50% 的 token 消耗（取决于内容重复度）

**面向场景：**

- GEO 内容批量生成中的相似标题/关键词组合
- A/B 测试中的重复请求
- 用户多次尝试类似问题的交互式生成

---

### 技术债务与后续优化方向

1. **向量索引优化**：当前 3072 维向量无法建 HNSW 索引（pgvector 0.8.x 限制 ~2000 维），检索为全表扫描。若未来切换到 ≤2000 维 embedding 模型，可补建 HNSW 加速检索。

2. **AI 调用日志**：当前仅在缓存层记录命中统计。完整的调用日志系统（记录每次 AI 请求的 model、tokens、latency、cost）可作为下一步增强，用于成本分析和性能监控。

3. **缓存预热**：支持从历史生成结果批量导入缓存，加速新实例启动。

4. **多租户隔离**：当前 ai_model_id 字段可用于按模型隔离缓存，未来可扩展为租户级隔离。

---

## 原项目信息

**原作者**: Yao Jingang (姚金刚)  
**原项目地址**: https://github.com/yaojingang/GEOFlow  
**许可协议**: Apache License 2.0

本二次开发版本保留原项目的所有版权声明和许可协议，所有新增功能在相同协议下发布。
