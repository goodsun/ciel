# CIEL サービス企画書

## 概要

個人運営のStable Diffusion画像/動画生成Webサービス。RunPodで稼働中のSD APIを活用し、プリペイド式クレジットによる従量課金で提供する。

## サービスコンセプト

- **対象**: 画像生成AIを手軽に使いたいユーザー
- **運営形態**: 個人運営
- **差別化**: 無料枠なし・シンプルなプリペイド従量課金・1枚ごとの明細表示
- **認証**: Googleアカウントのみ
- **対応言語**: 英語・日本語・中国語・韓国語・スペイン語

## 課金モデル

### プリペイド式クレジット

- ユーザーはStripeでクレジット(USD)を購入 ($5 / $10 / $25 / $50 / $100)
- 生成のたびにRunPodの実費 x マージン率(`MARGIN_RATE`)を残高から引き落とし
- コストはRunPod Billing APIから取得し、後日精算(`reconcile_costs.php`)
- 1枚ごとに明細を記録・表示

### コスト精算の仕組み

1. 生成完了時は `cost_runpod = NULL` で保存
2. `reconcile_costs.php` がRunPod Billing APIから時間帯別の実コストを取得
3. worker_id(podId)単位で按分 → endpointId単位でフォールバック
4. `cost_user = cost_runpod * MARGIN_RATE` を算出しユーザー残高から引き落とし
5. 全billing生データは `billing_records` テーブルに保存

### 残高不足の扱い

- 残高 <= 0 で生成リクエストを拒否 (HTTP 402)
- 失敗時の返金対応は行わない (利用規約に明記)

## 機能一覧

### ユーザー向け

| 機能 | ページ |
|------|--------|
| 画像生成 | image.php |
| 動画生成 | video.php |
| 画像編集 | edit.php |
| 生成履歴 | generated.php |
| マイページ (残高・明細) | mypage.php |
| クレジット購入 | purchase.php |
| ログイン/ログアウト | login.php / logout.php |

### 管理者向け (admin/)

| タブ | 機能 |
|------|------|
| Dashboard | ユーザー数・ジョブ数・収支サマリー |
| Users | ユーザー一覧 |
| Jobs | ジョブ一覧 (日時フィルタ・コスト集計) |
| Transactions | 取引明細 |
| Purchases | Stripe決済一覧 |
| Endpoints | RunPodエンドポイント管理 (CRUD・有効/無効) |
| API Keys | APIキー管理 (暗号化保存・CRUD) |

### バッチ処理

| バッチ | スケジュール | 機能 |
|--------|------------|------|
| poll_jobs.php | 毎分 | pending/processingジョブをポーリング、完了時にファイル保存 |
| reconcile_costs.php | 毎日02:00 UTC | Billing APIからコスト取得、按分精算 |

## セキュリティ

- APIキーはAES-256-CBC暗号化でDBに保存 (`APP_KEY`で復号)
- CSRF対策 (全POSTフォーム)
- 管理画面は `ADMIN_GOOGLE_IDS` でアクセス制御
- エンドポイントIDホワイトリスト検証
- Safeguard: 対象言語でのプロンプト自動フィルタリング

## システム構成

```
[ユーザー]
    ↓ Googleログイン
[PHP Webアプリ (heteml)]
    ↓ 生成リクエスト → api/run.php
[RunPod API] ← エンドポイント別APIキー (DBから復号)
    ↓ フロントからポーリング (api/status.php)
    ↓ 完了 → ファイル保存 + ジョブ更新
[cron: poll_jobs.php] ← バッチでも同様にポーリング
[cron: reconcile_costs.php] ← Billing APIでコスト精算
```

## ストレージ構成

```
storage/
└── users/
    └── {user_id}/
        └── generates/
            ├── {job_id}.jpg   # 画像
            └── {job_id}.mp4   # 動画
```

---

*初版: 2026年4月 / 最終更新: 2026年4月7日*
