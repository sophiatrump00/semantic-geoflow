# Git 仓库初始化指南

本文档指导你如何为 GEOFlow 语义缓存增强版初始化一个干净的 Git 仓库。

## 快速初始化（推荐）

在项目根目录执行以下命令：

```bash
# 初始化 Git 仓库
git init

# 添加所有文件到暂存区
git add .

# 创建初始提交
git commit -m "Initial commit: GEOFlow with semantic cache enhancement

- Base: GEOFlow 2.0.4 by Yao Jingang
- New feature: AI semantic cache with vector similarity search
- Includes: database migration, service layer, admin UI, tests, docs
- See CHANGELOG.md for details"

# （可选）添加远程仓库
git remote add origin YOUR_REPO_URL
git branch -M main
git push -u origin main
```

## 详细步骤

### 1. 初始化仓库

```bash
cd "D:\仿照研究项目\GEOFlow-main\GEOFlow-main"
git init
```

这会在项目根目录创建 `.git` 文件夹。

### 2. 检查 .gitignore

`.gitignore` 文件已配置好，会排除：
- 敏感文件（.env、*.log）
- 依赖目录（/vendor、/node_modules）
- IDE 配置（.idea、.vscode、.fleet 等）
- 构建产物（/public/build）

### 3. 添加文件到暂存区

```bash
git add .
```

查看将要提交的文件：
```bash
git status
```

### 4. 创建初始提交

```bash
git commit -m "Initial commit: GEOFlow with semantic cache enhancement"
```

你也可以用多行提交信息：
```bash
git commit -m "Initial commit: GEOFlow with semantic cache enhancement" \
           -m "Base: GEOFlow 2.0.4 by Yao Jingang" \
           -m "New feature: AI semantic cache" \
           -m "See CHANGELOG.md for details"
```

### 5. （可选）关联远程仓库

如果你想推送到 GitHub/GitLab：

```bash
# 添加远程仓库
git remote add origin https://github.com/YOUR_USERNAME/YOUR_REPO.git

# 设置主分支为 main
git branch -M main

# 推送到远程
git push -u origin main
```

## 后续 Git 工作流

### 日常开发

```bash
# 查看修改
git status

# 添加修改
git add .

# 提交
git commit -m "feat: 添加缓存预热功能"

# 推送
git push
```

### 分支管理

```bash
# 创建功能分支
git checkout -b feature/cache-warming

# 开发完成后合并回 main
git checkout main
git merge feature/cache-warming
```

### 标签管理

```bash
# 为语义缓存版本打标签
git tag -a v2.0.4-semantic-cache -m "Version 2.0.4 with semantic cache"
git push origin v2.0.4-semantic-cache
```

## 推荐的提交信息规范

遵循 Conventional Commits：

- `feat:` 新功能
- `fix:` Bug 修复
- `docs:` 文档更新
- `test:` 测试相关
- `refactor:` 代码重构
- `perf:` 性能优化
- `chore:` 构建/工具配置

示例：
```
feat: 添加语义缓存相似度阈值配置
fix: 修复 SQLite 测试环境下的向量列问题
docs: 更新面试预案中的技术细节
test: 添加缓存命中率统计测试
```

## 注意事项

1. **不要提交敏感信息**：`.env` 文件已在 `.gitignore` 中，但仍需检查代码中是否有硬编码的密钥。

2. **保留原作者信息**：所有提交都应尊重原项目的版权声明和 Apache-2.0 许可协议。

3. **清晰的提交历史**：每次提交应该是一个完整的逻辑单元，便于 code review 和回滚。

4. **定期推送**：及时推送到远程仓库，避免本地数据丢失。

## 常见问题

**Q: 如何重写提交历史（在首次推送前）？**
```bash
git commit --amend  # 修改最后一次提交
git rebase -i HEAD~3  # 交互式变基，修改最近 3 次提交
```

**Q: 如何撤销未推送的提交？**
```bash
git reset --soft HEAD~1  # 撤销提交但保留修改
git reset --hard HEAD~1  # 完全撤销提交和修改（谨慎使用）
```

**Q: 如何忽略已经被 track 的文件？**
```bash
git rm --cached FILE_NAME
echo "FILE_NAME" >> .gitignore
git commit -m "chore: stop tracking FILE_NAME"
```

---

**下一步**: 初始化完成后，建议立即推送到 GitHub/GitLab 私有仓库作为备份。
