# `wordpress-metadata-aigen` 第一阶段项目书（精简版）

## 目标

开发一个轻量 WordPress 插件，专门为以下对象生成并写入**标准摘要（Excerpt / Description Source）**：

* 文章（post）
* 页面（page）
* 分类（category）
* 标签（post_tag）
* 已启用的自定义文章类型（custom post types）

第一阶段只做 **description**，不做图片 alt，不做复杂异步系统，不做 SEO 插件深度集成。

该插件的定位不是 SEO 平台，而是一个 **AI 摘要生成工具**。

---

## 核心思路

* 插件通过 **OpenAI 兼容 API** 生成摘要
* 生成结果写入 **WordPress 标准摘要/标准描述来源**
* 不直接连接、适配或写入 SEO 插件的私有字段
* 由现有 SEO 插件（如 Yoast）在缓存刷新或重新读取后，自己使用这些标准内容

Yoast 官方支持通过模板变量使用 excerpt 生成 meta description，因此“写标准摘要，再由 Yoast 自己读取”这条路线是合理的。([yoast.com](https://yoast.com/help/how-to-modify-default-snippet-templates-in-yoast-seo/?utm_source=chatgpt.com))

---

## 第一阶段范围

### 必做

1. **API 设置页**

   * Base URL
   * API Key
   * Model
   * Timeout
   * Dry Run 开关

2. **Prompt 设置**

   * Description 的 system prompt
   * 可按对象类型区分：

     * 文章 / 页面 / CPT
     * 分类 / 标签

3. **单项生成**

   * 在文章、页面、分类、标签、自定义文章类型的编辑界面中，支持手动点击生成摘要

4. **批量生成**

   * 在后台工具页扫描“摘要为空”的对象
   * 支持 Dry Run
   * 支持一键批量生成
   * 支持按对象类型筛选
   * 支持限制每次处理数量

5. **简易日志**

   * 同页显示近期生成记录
   * 至少包含：时间、对象类型、对象 ID、名称、动作、结果、错误信息

6. **自定义格式 description**

   * 通过 prompt 控制输出风格
   * 输出要求：完整句、长度适中、不要直接复制正文首段、不要出现截断和残片标题

---

## 明确不做

第一阶段不做以下内容：

* image alt
* 上传钩子
* OCR
* 队列系统
* Gutenberg 复杂 React 面板
* 与 Yoast / 等 SEO 插件的私有字段同步
* 自动修改标题
* 自动改写正文
* Open Graph 生成

---

## 数据写入策略

### 文章 / 页面 / 自定义文章类型

优先写入 **WordPress 标准摘要字段**。

### 分类 / 标签

分类和标签第一阶段直接写入其原生 描述（description）字段，内容为纯文本。

---

## 使用流程

### 单项生成

管理员在编辑页点击“生成摘要”：

* 读取标题、正文/描述等上下文
* 调用 OpenAI 兼容 API
* 返回摘要
* 预览或直接保存

### 批量生成

管理员在工具页中：

* 选择对象类型
* 扫描摘要为空的项目
* Dry Run 预览
* 确认后批量写入
* 页面下方显示日志

---

## 技术要求

* 纯 PHP 后台优先
* 不要求首版做复杂 JS 编辑器扩展
* 仅管理员可操作
* 默认跳过已有摘要
* 必须支持 Dry Run
* API 调用失败时不能导致后台致命报错
* 停用插件后站点前台不报错

---

## 交付标准

第一阶段完成后，应满足：

* 能配置 OpenAI 兼容 API
* 能测试 API 是否可用
* 能为单篇文章/页面生成摘要
* 能为分类/标签生成摘要
* 能批量扫描摘要为空的对象
* 能 Dry Run 预览而不写入
* 能批量写入
* 能输出简单日志
* 能支持至少一种自定义文章类型

---

## 给 Codex 的实现要求

1. 第一阶段只做 description
2. 支持 post / page / category / post_tag / enabled CPT
3. 使用 OpenAI 兼容 API
4. 支持单项生成
5. 支持批量补空白项
6. 支持 Dry Run
7. 支持简易日志
8. 不做 SEO 插件私有字段集成
9.  细节实现可自行判断，优先选择简单、稳妥、可维护方案
