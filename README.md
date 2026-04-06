# CIEL

Stable Diffusion画像/動画生成Webサービス。RunPod APIを利用し、プリペイド式クレジットによる従量課金で提供する。

## 技術スタック

| 領域 | 技術 |
|------|------|
| バックエンド | PHP 8.1+ |
| データベース | MySQL 8.0 |
| 認証 | Google OAuth 2.0 |
| 決済 | Stripe (プリペイド購入) |
| 画像/動画生成 | RunPod API (Stable Diffusion) |
| バッチ処理 | PHP cron |
| i18n | en, ja, zh, ko, es |
| ホスティング | heteml |

## ディレクトリ構成

```
ciel/
├── public/                 # Web root
│   ├── index.php           # トップページ
│   ├── image.php           # 画像生成UI
│   ├── video.php           # 動画生成UI
│   ├── edit.php            # 画像編集UI
│   ├── generated.php       # 生成履歴
│   ├── mypage.php          # マイページ (残高・明細)
│   ├── login.php           # ログイン
│   ├── logout.php          # ログアウト
│   ├── callback.php        # Google OAuth コールバック
│   ├── purchase.php        # Stripe購入開始
│   ├── purchase_success.php
│   ├── webhook.php         # Stripe Webhook
│   ├── service.php         # 利用規約
│   ├── api/
│   │   ├── run.php         # 生成リクエスト送信
│   │   ├── status.php      # ジョブステータス確認
│   │   ├── delete.php      # 生成画像削除
│   │   └── file.php        # 画像ファイル配信
│   └── admin/
│       ├── index.php       # 管理画面 (Dashboard/Users/Jobs/Transactions/Purchases/Endpoints/API Keys)
│       ├── job.php         # ジョブ詳細
│       └── file.php        # 管理用ファイル配信
├── src/
│   ├── bootstrap.php       # 環境変数読込・セッション・i18n・CSRF・エンドポイント読込
│   ├── db.php              # PDO接続
│   ├── auth.php            # 認証 (Google OAuth)
│   ├── user.php            # ユーザーCRUD
│   ├── stripe.php          # Stripe連携
│   └── crypto.php          # AES-256-CBC暗号化 (APIキー保護)
├── batch/
│   ├── poll_jobs.php       # ジョブポーリング (cron: 毎分)
│   └── reconcile_costs.php # コスト精算 (RunPod Billing API)
├── templates/              # 共通テンプレート (head/header/footer)
├── lang/                   # 多言語ファイル (en/ja/zh/ko/es)
├── sql/
│   ├── schema.sql          # 全テーブル定義
│   └── 002-005_*.sql       # マイグレーション
├── storage/                # 生成画像/動画ファイル (gitignore)
├── .env                    # 本番環境変数
├── .env.local              # ローカル開発用 (localhost時に自動読込)
└── .env.example            # 環境変数テンプレート
```

## データベース

7テーブル構成 (`sql/schema.sql`):

| テーブル | 用途 |
|---------|------|
| `users` | ユーザー (Google ID, 残高) |
| `jobs` | 生成ジョブ (RunPod job ID, コスト, ステータス) |
| `purchases` | Stripe決済 |
| `transactions` | 残高増減明細 |
| `billing_records` | RunPod Billing API生データ |
| `api_keys` | APIキー (AES-256-CBC暗号化) |
| `endpoints` | RunPodエンドポイント設定 (api_key_idで紐付け) |

## セットアップ

### 環境変数

`.env.example` をコピーして `.env` を作成:

```
GOOGLE_CLIENT_ID=        # Google OAuth
GOOGLE_CLIENT_SECRET=
STRIPE_SECRET_KEY=       # Stripe
STRIPE_WEBHOOK_SECRET=
MARGIN_RATE=3.5          # RunPod実費 x マージン率 = ユーザー課金額
STORAGE_PRICE_PER_MB=0.001
DB_HOST=localhost
DB_NAME=ciel
DB_USER=
DB_PASS=
APP_URL=                 # サイトURL
APP_KEY=                 # APIキー暗号化用 (openssl rand -hex 32)
ADMIN_GOOGLE_IDS=        # 管理者Google ID (カンマ区切り)
```

APIキーとエンドポイント設定はDBで管理 (管理画面から操作可能)。

### ローカル開発

```bash
brew install mysql
brew services start mysql
mysql -u root -e "CREATE DATABASE ciel CHARACTER SET utf8mb4"
mysql -u root ciel < sql/schema.sql
cp .env.example .env.local  # DB_HOST=127.0.0.1, APP_URL=http://localhost:8080 等を設定
php -S localhost:8080 -t public
```

localhost接続時は `.env.local` が自動読込され、管理者として自動ログインされる。

### 本番デプロイ

```bash
ssh bonsoleil "cd web/ciel && git pull"
```

### cron設定

```
* * * * * /usr/local/php/8.1/bin/php /path/to/batch/poll_jobs.php
0 2 * * * /usr/local/php/8.1/bin/php /path/to/batch/reconcile_costs.php
```

## 課金フロー

1. ユーザーがStripeでクレジット(USD)を購入
2. 画像/動画生成リクエスト → RunPod APIへ送信
3. 完了後、コストはNULLのまま保存
4. `reconcile_costs.php` がRunPod Billing APIから実コストを取得
5. worker_id (podId) または endpoint_id で按分計算
6. `cost_runpod * MARGIN_RATE` をユーザー残高から引き落とし

## セキュリティ

- APIキーはAES-256-CBCで暗号化してDBに保存 (`APP_KEY`で復号)
- CSRF対策 (全POSTフォーム)
- 管理画面は `ADMIN_GOOGLE_IDS` でアクセス制御
- エンドポイントIDホワイトリスト検証
