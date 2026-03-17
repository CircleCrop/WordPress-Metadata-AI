# WordPress Metadata AI Generator

[English](README.md) | [简体中文](README.zh-CN.md) | [繁體中文](README.zh-TW.md) | [日本語](README.ja.md)

<p align="center">
  <img src="assets/readme-hero.svg" alt="WordPress Metadata AI Generator 概覽圖" width="840">
</p>

<p align="center">
  <strong>直接在 WordPress 後台生成更乾淨的摘要與分類描述。</strong>
</p>

WordPress Metadata AI Generator 是一個僅供後台管理員使用的外掛，可透過相容 OpenAI 的 API 為文章摘要與分類描述生成內容。它適合希望提升網站中繼資訊品質、但不想逐筆手動維護的站長。

## 功能概覽

- 為文章、頁面、後台可見的自訂文章類型、分類與標籤生成 description
- 文章類物件寫入 WordPress 原生摘要欄位 `excerpt`
- 分類與標籤寫入 WordPress 原生 `description` 欄位
- 支援在編輯頁單筆生成，也支援在後台批次生成
- 支援 `Dry Run` 預覽模式，可先檢查結果再決定是否寫入
- 提供簡潔的後台日誌，涵蓋設定讀取、請求發起、結果生成、寫入、跳過與失敗

> 面向實際內容發布流程：設定一次，在後台直接生成，必要時先 Dry Run 預覽，再寫入 WordPress 原生欄位。

## 環境需求

- WordPress 6.0 或以上版本
- PHP 7.4 或以上版本
- 一個相容 OpenAI Chat Completions 的 API 端點

## 安裝方式

1. 將外掛目錄上傳到 `wp-content/plugins/`，或在後台上傳發佈 ZIP 安裝。
2. 啟用外掛。
3. 開啟 `設定 > Metadata AI`。
4. 填寫 API Base URL、API Key、模型與提示詞。
5. 儲存設定，並執行連線測試。

## 快速開始

1. 在後台開啟文章、頁面、分類或標籤。
2. 在編輯頁執行生成，或使用 `工具 > Metadata AI Batch` 進行批次生成。
3. 檢查生成結果。
4. 若 `Dry Run` 已關閉，外掛會將結果寫入 WordPress 原生欄位。

## 目前版本範圍

- 目前版本只專注於 description 生成。
- 支援文章、頁面、後台可見的自訂文章類型、分類與標籤。
- 目前版本不包含圖片 alt 生成、SEO 外掛私有欄位同步，也不包含非同步佇列。
