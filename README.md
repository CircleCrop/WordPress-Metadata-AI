# WordPress Metadata AI Generator

[English](README.md) | [简体中文](README.zh-CN.md) | [繁體中文](README.zh-TW.md) | [日本語](README.ja.md)

<p align="center">
  <img src="assets/readme-hero.svg" alt="WordPress Metadata AI Generator overview" width="840">
</p>

<p align="center">
  <strong>Generate cleaner excerpts and taxonomy descriptions from WordPress admin.</strong>
</p>

WordPress Metadata AI Generator is an admin-only plugin that creates post excerpts and taxonomy descriptions with an OpenAI-compatible API. It is designed for site owners who want cleaner metadata without manually editing every post, page, category, or tag.

## What It Does

- Generates descriptions for posts, pages, admin-facing custom post types, categories, and tags
- Writes post-like results to the native WordPress excerpt field
- Writes category and tag results to the built-in term description field
- Lets admins run single-item generation from the editor or batch generation from wp-admin
- Supports Dry Run mode so results can be reviewed before they are written
- Keeps simple admin logs for configuration reads, requests, generated results, saves, skips, and failures

> Built for practical publishing workflows: configure once, generate from wp-admin, and keep control with Dry Run and native WordPress fields.

## Requirements

- WordPress 6.0 or newer
- PHP 7.4 or newer
- An OpenAI-compatible Chat Completions API endpoint

## Installation

1. Upload the plugin folder to `wp-content/plugins/`, or install the release ZIP from wp-admin.
2. Activate the plugin.
3. Open `Settings > Metadata AI`.
4. Enter your API base URL, API key, model, and prompts.
5. Save the settings and test the connection.

## Quick Start

1. Open a post, page, category, or tag in wp-admin.
2. Run the generate action from the editor panel, or use `Tools > Metadata AI Batch`.
3. Review the result.
4. If Dry Run is disabled, the plugin writes the generated description to the native WordPress field.

## Scope of This Release

- This release focuses on description generation only.
- It supports posts, pages, admin-facing custom post types, categories, and tags.
- It does not include image alt generation, SEO plugin private-field syncing, or async queues.
