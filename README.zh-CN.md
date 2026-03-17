# WordPress Metadata AI Generator

[English](README.md) | [简体中文](README.zh-CN.md) | [繁體中文](README.zh-TW.md) | [日本語](README.ja.md)

<p align="center">
  <img src="assets/readme-hero.svg" alt="WordPress Metadata AI Generator 概览图" width="840">
</p>

<p align="center">
  <strong>直接在 WordPress 后台生成更干净的摘要和分类描述。</strong>
</p>

WordPress Metadata AI Generator 是一个仅供后台管理员使用的插件，可通过兼容 OpenAI 的 API 为文章摘要和分类描述生成内容。它适合希望提升站点元信息质量、但不想逐条手工填写的站长。

## 功能概览

- 为文章、页面、后台可见的自定义文章类型、分类和标签生成 description
- 文章类对象写入 WordPress 原生摘要字段 `excerpt`
- 分类和标签写入 WordPress 原生 `description` 字段
- 支持在编辑页单项生成，也支持在后台批量生成
- 支持 `Dry Run` 预览模式，可先看结果再决定是否写入
- 提供简明后台日志，覆盖配置读取、请求发起、结果生成、写入、跳过与失败

> 面向实际内容发布流程：配置一次，在后台直接生成，必要时先 Dry Run 预览，再写入 WordPress 原生字段。

## 环境要求

- WordPress 6.0 或更高版本
- PHP 7.4 或更高版本
- 一个兼容 OpenAI Chat Completions 的 API 接口

## 安装方式

1. 将插件目录上传到 `wp-content/plugins/`，或在后台上传发布 ZIP 安装。
2. 启用插件。
3. 打开 `设置 > Metadata AI`。
4. 填写 API Base URL、API Key、模型和提示词。
5. 保存设置，并执行连接测试。

## 快速开始

1. 在后台打开一篇文章、页面、分类或标签。
2. 在编辑页执行生成，或使用 `工具 > Metadata AI Batch` 做批量生成。
3. 查看生成结果。
4. 如果 `Dry Run` 已关闭，插件会把结果写入 WordPress 原生字段。

## 当前版本范围

- 当前版本只聚焦 description 生成。
- 支持文章、页面、后台可见的自定义文章类型、分类和标签。
- 当前版本不包含图片 alt 生成、SEO 插件私有字段同步，也不包含异步队列。
