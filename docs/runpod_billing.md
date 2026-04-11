# RunPod 課金モデルとCIELコスト精算の詳細

## RunPod Serverless の課金体系

### 課金が発生するタイミング

RunPod Serverlessでは、**workerが起動した瞬間**から課金が始まる。リクエスト送信時点ではなく、実際にGPUワーカーがアサインされ実行を開始した時点が課金開始となる。

```
リクエスト送信 → キュー待ち(無料) → ワーカー起動(課金開始) → 推論実行 → 完了(課金終了)
```

### 時間の内訳

RunPodのジョブ完了レスポンスには以下の時間情報が含まれる:

| フィールド | 意味 | 課金対象 |
|-----------|------|---------|
| `delayTime` (ms) | キュー待ち + コールドスタート時間 | **分離不可** (後述) |
| `executionTime` (ms) | 実際の推論処理時間 | **課金対象** |
| `workerId` | 処理したワーカー(Pod)のID | 按分キー |

### delayTime の内訳と課金

`delayTime` には2つの性質の異なる時間が合算されている:

1. **キュー待ち時間** — 全ワーカーがビジーで順番待ちの時間。**課金なし**
2. **コールドスタート時間** — アイドルワーカーがない場合に新規Podが起動する時間。**課金あり** (GPUが確保された瞬間から課金開始)

**問題: `delayTime` はこの2つを分離して返さない。** キュー待ちが5分でコールドスタートが1分なのか、キュー待ちが0分でコールドスタートが6分なのか、APIからは判別できない。

### Podの起動・維持コストの按分

Billing APIの `timeBilledMs` にはそのPodの全課金時間が含まれる:
- 各ジョブの **executionTime** (推論処理)
- **コールドスタート時間** (Pod起動)
- **アイドル時間** (ジョブ間の待機)

これらはPod単位で合算されるため、ジョブごとに分離できない。CIELでは `executionTime` で比例按分しており、**Podの起動・維持にかかるコストはそのワーカーの利用者全員で按分される。** コールドスタートを引き起こしたジョブだけが負担するのではなく、同一Pod・同一時間帯の全ジョブで公平に配分される。

実際の課金額はBilling APIの `timeBilledMs` に反映される。これにはコールドスタート・アイドル分が含まれるが、キュー待ち分は含まれない。

### 具体例: 動画生成1回のコスト

実データ (job_id=16, Wan2.1動画生成):

```
GPU:           NVIDIA A40
executionTime: 159,658ms (約2分40秒)
delayTime:     (記録なし = warm start)
cost_runpod:   $0.185203
cost_user:     $0.648211 (= $0.185203 × 3.5 マージン率)
```

比較: 画像生成1回 (job_id=22, Momoiro Pony):

```
GPU:           NVIDIA GeForce RTX 4090
executionTime: 4,378ms (約4秒)
delayTime:     402,220ms (約6分42秒 = コールドスタート)
cost_runpod:   $0.021277
cost_user:     $0.074470
```

注目: job_id=22は `delayTime` が6分超 (コールドスタート含む)。コールドスタートのコストはこのPodを利用した全ジョブの `executionTime` で按分され、job_id=22だけが負担するわけではない。

### GPU種別ごとの課金レート

RunPodの課金は **秒単位** でGPU種別ごとに異なる:

| GPU | おおよその単価 |
|-----|--------------|
| RTX 4090 | ~$0.00031/秒 |
| A40 | ~$0.00034/秒 |

正確な単価は `billing_records` の `amount / (timeBilledMs / 1000)` で算出可能。

---

## RunPod Billing API

### エンドポイント

```
GET https://rest.runpod.io/v1/billing/endpoints
Authorization: Bearer {API_KEY}
```

### パラメータ

| パラメータ | 値 | 説明 |
|-----------|-----|------|
| `bucketSize` | `hour` | 集計単位 (hour/day) |
| `startTime` | `2026-04-06T00:00:00Z` | 開始時刻 (UTC) |
| `endTime` | `2026-04-07T00:00:00Z` | 終了時刻 (UTC) |
| `grouping` | `endpointId` / `podId` / `gpuTypeId` | 集計グループ |

### レスポンス例 (grouping=podId)

```json
{
  "time": "2026-04-06 15:00:00",
  "podId": "qi1tkvazg9egjs",
  "amount": 0.006994259194,
  "timeBilledMs": 19860,
  "diskSpaceBilledGB": 80
}
```

### 3つのグルーピング

| grouping | 用途 |
|----------|------|
| `endpointId` | エンドポイント単位の合計コスト |
| `podId` | ワーカー(Pod)単位のコスト — **ジョブ按分の第一優先** |
| `gpuTypeId` | GPU種別ごとのコスト (参考情報) |

### 課金データの粒度

- Billing APIは **1時間バケット** 単位でしか返さない
- 個々のジョブ単位のコストは直接取得できない
- したがって、同一時間帯のジョブ間で **按分計算** が必要

---

## CIELのコスト精算フロー

### Phase 1: ジョブ完了時 (リアルタイム)

`api/status.php` または `batch/poll_jobs.php` がRunPodをポーリング:

```
1. RunPodからCOMPLETEDレスポンス受信
2. executionTime, delayTime, workerId を取得
3. jobs テーブルに保存 (cost_runpod = NULL, cost_reconciled = 0)
4. 出力ファイルをstorageに保存
```

この時点ではコストは**未確定**。

### Phase 2: コスト精算 (バッチ)

`batch/reconcile_costs.php` が日次でBilling APIからコストを取得:

```
1. 3つのgrouping (endpointId, podId, gpuTypeId) でBilling APIを呼ぶ
2. 全レスポンスを billing_records テーブルに保存
3. 未精算ジョブ (cost_reconciled = 0) を取得
4. 各ジョブについて:
   a. ジョブの created_at からUTCの時間バケットを特定
   b. worker_id で podId billing にマッチを試みる (第一優先)
   c. マッチしなければ endpoint_id で endpointId billing にフォールバック
   d. 同一バケット内の全ジョブの executionTime で按分
   e. cost_runpod = バケット金額 × (このジョブのexecTime / 全ジョブのexecTime合計)
   f. cost_user = cost_runpod × MARGIN_RATE
   g. ユーザー残高から引き落とし、transactions に記録
```

### 按分計算の詳細

```
同一Pod・同一時間帯に3ジョブがあった場合:
  job A: executionTime = 5,000ms
  job B: executionTime = 3,000ms
  job C: executionTime = 2,000ms
  合計: 10,000ms

  バケットの amount = $0.010000
  
  job A の cost_runpod = $0.010000 × (5000/10000) = $0.005000
  job B の cost_runpod = $0.010000 × (3000/10000) = $0.003000
  job C の cost_runpod = $0.010000 × (2000/10000) = $0.002000
```

### マッチ優先順位

1. **podId マッチ** — `worker_id` が記録されている場合、同一Pod内のジョブだけで按分。最も正確
2. **endpointId フォールバック** — `worker_id` がない場合 (古いジョブ等)、同一エンドポイント内の全ジョブで按分。精度は落ちる

---

## データベースでのコスト記録

### jobs テーブル

| カラム | 型 | 説明 |
|--------|-----|------|
| `execution_time` | INT UNSIGNED | 推論時間 (ms) — RunPodレスポンスから |
| `delay_time` | INT UNSIGNED | キュー待ち+コールドスタートの合計 (ms)。分離不可。キュー=無課金、コールドスタート=課金 |
| `worker_id` | VARCHAR(255) | 処理Pod ID — 按分キー |
| `cost_runpod` | DECIMAL(10,6) | RunPod実費 (USD) — 精算後に書込み |
| `cost_user` | DECIMAL(10,6) | ユーザー課金額 (= cost_runpod × MARGIN_RATE) |
| `cost_reconciled` | TINYINT(1) | 0=未精算, 1=精算済み |

### billing_records テーブル

Billing APIの生データを保存。3つのgrouping × 時間バケットごとに1行:

| カラム | 説明 |
|--------|------|
| `bucket_time` | 時間バケット開始 (UTC) |
| `grouping_type` | `endpointId` / `podId` / `gpuTypeId` |
| `grouping_value` | 実際のID値 |
| `amount` | USD (小数12桁精度) |
| `time_billed_ms` | 課金対象時間 (ms) |
| `disk_billed_gb` | ディスク課金 (GB) |

### transactions テーブル

ユーザー向けの明細。精算時に記録:

```
type: 'generation'
amount: -0.074470  (負値 = 引き落とし)
note: 'image 4.4s $0.074470'  (種別 実行時間 課金額)
```

再精算時:
```
note: 'reconcile: job 22 adj $0.074470 (was $0.052560)'
```

---

## ユーザーへのコスト提示

### 現状

- マイページ (`mypage.php`) の取引明細に表示
- 管理画面のJobsタブで `cost_runpod` / `cost_user` を確認可能

### 課題: 精算タイミングのラグ

- ジョブ完了直後は `cost_user = NULL` (未精算)
- `reconcile_costs.php` 実行後に確定 (通常は翌日02:00 UTC)
- `poll_jobs.php` が未精算ジョブを検出した場合、即時精算も試みる

### ユーザーに説明すべきポイント

1. **生成直後はコストが「精算中」と表示される可能性がある**
2. **最終的なコストはRunPodの実費に基づいて後日確定する**
3. **コールドスタートが発生した場合、待ち時間分のコストも含まれる**
4. **マージン率 (MARGIN_RATE) が実費に乗算される**

---

## RunPod APIの制約

### ジョブ単位のコストは取得不可

RunPodはジョブ単位のコスト情報を一切提供しない:

- **ジョブステータスAPI** (`/v2/{endpoint_id}/status/{job_id}`) — `executionTime`, `delayTime`, `workerId` は返すが、**コスト/料金フィールドは存在しない**
- **Billing API** (`/v1/billing/endpoints`) — 1時間バケット単位の集計のみ。ジョブ単位の内訳は取得不可
- **GraphQL API** / **ダッシュボード** — エンドポイント/Pod単位の集計のみ

したがって、ジョブ単位のコストは **按分計算による推定値** であり、RunPodから直接取得した値ではない。

### ジョブデータの保持期間

RunPodは完了したジョブのステータスを **30分間** しか保持しない:

- 完了後30分でステータスAPIが `404 Not Found` を返す
- アーカイブAPIや履歴照会APIは存在しない
- `executionTime`, `delayTime`, `workerId` はこの30分以内に取得しなければ永久に失われる

**CIELでの対策:**
- `api/status.php` がフロントからのポーリングで即座に取得・保存
- `batch/poll_jobs.php` が毎分cronで未完了ジョブをポーリング (バックアップ)
- 両方の経路でジョブデータを取りこぼさない設計

### workerId フィールドの信頼性

`workerId` はコスト按分精度の鍵だが、公式ドキュメント上の扱いは曖昧:

- **汎用APIリファレンス** (operation-reference) — COMPLETEDレスポンス例に `workerId` が**含まれていない**
- **チュートリアル・モデル別ドキュメント** — COMPLETEDレスポンス例に `workerId` が**含まれている**
- **APIコントラクトとして正式に保証されていない**

実際の挙動:
- COMPLETEDステータスでは**ほぼ返される**が、欠落するケースも確認済み (CIELの実データで確認)
- IN_PROGRESSステータスでも返される可能性がある (ワーカーに既にアサインされているため)
- IN_QUEUE, CANCELLED, FAILED では返されない

`workerId` = `podId` であることはRunPod Python SDKのソースで確認済み (`RUNPOD_POD_ID` 環境変数から取得)。Billing APIの `podId` グルーピングとの突き合わせに使える。

**CIELでの対策:**
- COMPLETED時だけでなく、**IN_PROGRESS時にも `workerId` を保存**して取得機会を最大化
- NULLの場合は endpointId 按分にフォールバック (防御的設計)
- `jobs.worker_id` カラムは `DEFAULT NULL` で設計

### GPU混在エンドポイントの按分誤差

RunPod Serverlessでは同一エンドポイントに**異なるGPU種別のワーカー**がアサインされうる:

```
endpoint: 3v6f5lcc0j94n3 (14:00 UTC)
  Pod A (NVIDIA A40):           $0.082624 — 243,809ms
  Pod B (NVIDIA GeForce RTX 4090): $0.006842 —  22,392ms
  endpoint合計:                 $0.089466
```

A40とRTX 4090では秒単価が異なるため、endpointId按分では正確なコスト配分ができない。

- **`workerId` あり** → podId按分。Pod = 1 GPU種別なので**正確**
- **`workerId` なし** → endpointId按分。GPU混在時に**誤差が生じる**

実データでは同一時間帯に RTX 4090, A40, RTX A6000 の3種が混在するケースも確認されている。

### 按分計算が最善の方法である理由

RunPodの制約をまとめると:

1. ジョブ単位のコストAPIが存在しない
2. Billing APIは1時間バケット集計のみ
3. ジョブデータは30分で消える
4. GPU単価の公式APIも存在しない
5. `workerId` はほぼ返されるがAPIコントラクトとして保証されていない
6. 同一エンドポイントに異なるGPU種別が混在しうる

現在のCIELの精算方式（Billing APIの時間バケット金額を `executionTime` で比例按分）は、これらの制約下での最も正確な方法。`workerId` による podId マッチで同一ワーカー内のジョブだけに限定することで精度を高めている。`workerId` が取れない場合のendpointId按分は最終手段であり、GPU混在時の誤差を許容する。

---

## 参考: 実際のコスト感

### 画像生成 (RTX 4090)

- warm start: $0.01〜0.02 (実費) → $0.04〜0.07 (ユーザー)
- cold start: $0.02〜0.09 (実費) → $0.07〜0.31 (ユーザー)
- 実行時間: 3〜10秒

### 動画生成 (A40)

- warm start: $0.18〜0.19 (実費) → $0.63〜0.66 (ユーザー)
- 実行時間: 150〜170秒 (2.5〜3分)

---

---

## 運用: Reconcileログの見方

### ログファイル

Reconcile結果は `logs/reconcile-YYYY-MM.log` にJSONL形式で月別に保存される。

```
logs/reconcile-2026-04.log
logs/reconcile-2026-05.log
...
```

管理画面の Reconcile タブで閲覧可能。月切り替えリンクで過去月も参照できる。

### カラムの意味

| カラム | 意味 | 注目すべき値 |
|--------|------|-------------|
| **Date** | reconcile対象日 (Billing APIに問い合わせた日付) | - |
| **Trigger** | `cron`=定時(02:00 UTC), `poll`=poll_jobsからの即時呼出, `admin`=管理画面から手動 | pollが多い場合は未精算ジョブが溜まっている |
| **Adjusted** | コスト補正されたジョブ数 | 0以外 = 実際にユーザー残高が変動した |
| **Skipped** | billingデータ未到着でスキップされたジョブ数 | 常に高い場合はBilling APIのラグを疑う |
| **Adjustment** | ユーザー残高に反映された差分合計 (USD) | 大きい値 = 見積もりと実績の乖離が大きい |
| **EP Updated** | `est_cost_per_sec` を更新したエンドポイント数 | - |
| **API Calls** | RunPod Billing APIの呼出回数 (APIキー数 x 3 grouping) | - |
| **Duration** | バッチ実行時間 (ms) | 極端に遅い場合はAPI応答遅延 |
| **Error** | エラー内容 | `no billing data` = その日のデータが未生成 |
| **Run At** | バッチ実行日時 (JST) | - |

### 正常パターン

```
Adjusted=15, Skipped=0, Adjustment=$0.85  → 通常の精算完了
Adjusted=0, Skipped=0                      → 未精算ジョブなし (全て精算済み)
```

### 要注意パターン

```
Adjusted=0, Skipped=30     → billingデータ遅延。翌日のcronで解消されるはず
Error="no billing data"    → RunPod側がまだ課金データを生成していない
Adjustment が異常に大きい    → GPU単価の変動、またはエンドポイント構成変更
```

### "hide zero adjustment" フィルタ

管理画面のチェックボックスで Adjustment=0 の行を非表示にできる。実質的にコスト変動があった実行だけを確認したい場合に使う。

---

## 運用: Stripeとの突き合わせ

### 金の流れの全体像

```
[ユーザー] --Stripe決済--> [purchases] --残高加算--> [users.balance]
                                                         |
[RunPod GPU実行] --Billing API--> [reconcile] --残高減算--> [users.balance]
                                                         |
                                              [transactions] に全記録
```

- **入金**: Stripe → `purchases` テーブル → `transactions(type='purchase')`
- **出金**: RunPod実費 x MARGIN_RATE → `transactions(type='generation')`
- **残高**: `users.balance` = 全transactionsのamount合計と一致するはず

### 突き合わせ手順

#### 1. Stripe入金 vs purchases テーブル

Stripeダッシュボードの Payment 一覧と `purchases` テーブルを照合:

```sql
-- 完了済み購入の合計
SELECT SUM(amount) as total_purchases,
       COUNT(*) as count
FROM purchases
WHERE status = 'completed';
```

Stripe側の `payment_intent` ID が `purchases.stripe_payment_id` と一致する。

**不一致が起きるケース:**
- Webhook未着 → `purchases.status = 'pending'` のまま残る
- `batch/recover_pending_purchases.php` が未回収分を定期リカバリ

```sql
-- 未回収の確認
SELECT id, user_id, amount, stripe_session_id, created_at
FROM purchases
WHERE status = 'pending'
ORDER BY created_at DESC;
```

#### 2. ユーザー残高の整合性チェック

```sql
-- transactions合計と現在残高の突き合わせ (全ユーザー)
SELECT u.id, u.balance,
       COALESCE(SUM(t.amount), 0) as tx_sum,
       u.balance - COALESCE(SUM(t.amount), 0) as diff
FROM users u
LEFT JOIN transactions t ON t.user_id = u.id
GROUP BY u.id
HAVING ABS(diff) > 0.000001;
```

結果が0行なら整合。差分がある場合は:
- reconcile中のトランザクション不整合
- 手動でbalanceを修正した形跡
- 並行処理によるrace condition

#### 3. RunPod実費 vs ユーザー課金の比較

```sql
-- 精算済みジョブの RunPod実費合計 vs ユーザー課金合計
SELECT COUNT(*) as jobs,
       SUM(cost_runpod) as total_runpod,
       SUM(cost_user) as total_user,
       SUM(cost_user) / NULLIF(SUM(cost_runpod), 0) as actual_margin
FROM jobs
WHERE cost_reconciled = 1;
```

`actual_margin` が `MARGIN_RATE` (デフォルト3.5) に近ければ正常。

#### 4. 収支サマリ

```sql
-- 期間指定の収支
SELECT
  (SELECT SUM(amount) FROM transactions WHERE type = 'purchase' AND created_at >= '2026-04-01') as revenue,
  (SELECT SUM(cost_runpod) FROM jobs WHERE cost_reconciled = 1 AND created_at >= '2026-04-01') as runpod_cost,
  (SELECT SUM(amount) FROM transactions WHERE type = 'purchase' AND created_at >= '2026-04-01')
    + (SELECT SUM(cost_runpod) FROM jobs WHERE cost_reconciled = 1 AND created_at >= '2026-04-01') as gross_profit;
```

### 月次チェックリスト

1. `purchases` に `pending` が残っていないか確認
2. 全ユーザーの `balance` と `transactions` 合計の整合性を確認
3. `actual_margin` が想定 MARGIN_RATE に近いか確認
4. reconcileログで `Skipped` が継続的に高くないか確認
5. Stripeダッシュボードの決済合計と `purchases(completed)` の合計が一致するか確認

---

*最終更新: 2026年4月11日*
*データソース: billing_records テーブル + jobs テーブル + transactions テーブル*
*RunPod API仕様: 2026年4月時点で確認*
