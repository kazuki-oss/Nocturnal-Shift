# マルチテナント機能の使い方

このアプリケーションはマルチテナント対応で、複数の店舗を1つのシステムで管理できます。

## テナント識別方法

### 本番環境（ドメインベース）

各店舗に専用のドメインを割り当てます：

```
https://tokyo.example.com  → 東京店のデータ
https://osaka.example.com  → 大阪店のデータ
```

データベースの`tenants`テーブルで、各テナントの`domain`カラムとホスト名が一致するものが自動的に選択されます。

### 開発・テスト環境（URLパラメータ）

URLパラメータ `tenant_id` を使用してテナントを切り替えられます：

```
http://localhost:8000/admin/login?tenant_id=1  → テナントID=1
http://localhost:8000/admin/login?tenant_id=2  → テナントID=2
```

一度パラメータを渡すと、セッションに保存されるため、その後のページ遷移では指定不要です。

## テナントの優先順位

1. **URLパラメータ** (`?tenant_id=X`) - 最優先
2. **セッション** - 前回選択したテナント
3. **ドメイン** - ホスト名に基づく自動識別

## 初期データの作成

### 方法1: DataFixtures（推奨）

`src/DataFixtures/AppFixtures.php` を編集して複数テナントを作成：

```php
// Tenant 1
$tenant1 = new Tenant();
$tenant1->setName('Bar Tokyo');
$tenant1->setDomain('localhost');  // 開発環境用
$manager->persist($tenant1);

// Tenant 2
$tenant2 = new Tenant();
$tenant2->setName('Bar Osaka');
$tenant2->setDomain('osaka.localhost');
$manager->persist($tenant2);

// 各テナントの管理者ユーザーを作成...
```

その後、以下を実行：

```bash
docker-compose exec php php bin/console doctrine:fixtures:load
```

### 方法2: 直接SQLで作成

```sql
INSERT INTO tenants (name, domain) VALUES 
  ('Bar Tokyo', 'localhost'),
  ('Bar Osaka', 'tenant2.localhost');
```

## テナント切り替えの例

### ローカル開発での切り替え

1. **初回アクセス**
   ```
   http://localhost:8000/admin/login?tenant_id=1
   ```

2. **別のテナントに切り替え**
   ```
   http://localhost:8000/admin/login?tenant_id=2
   ```

3. **セッションクリア後は再度パラメータ指定**
   - ログアウト後やブラウザを閉じた後は、再度`?tenant_id=X`を指定

## データ分離の仕組み

### 自動分離

すべてのデータは自動的にテナント別に分離されます：

- **TenantFilter**: SQLレベルで自動フィルタリング
- **TenantSubscriber**: 新規データ作成時に自動でテナントを設定
- **TenantResolver**: 現在のテナントを自動識別

### 対象エンティティ

以下のエンティティがテナント分離されています：
- User（従業員）
- Shift（シフト）
- ShiftRequest（シフト希望）
- Attendance（勤怠）
- Event（イベント）
- DrinkRecord（ドリンク記録）
- ShiftTemplate（シフトテンプレート）

## トラブルシューティング

### テナントが見つからない場合

1. データベースにテナントが存在するか確認
   ```bash
   docker-compose exec php php bin/console doctrine:query:sql "SELECT * FROM tenants"
   ```

2. セッションをクリア
   - ブラウザのCookieをクリア
   - または`?tenant_id=X`を再度指定

### 別のテナントのデータが見える場合

- ログアウトして再ログイン
- 正しいテナントIDを`?tenant_id=X`で指定
- セッションが混在している可能性があるため、ブラウザのプライベートモードを使用

## 本番環境への展開

1. 各店舗に専用ドメインを設定
2. tenantsテーブルのdomainカラムを更新
3. DNS設定で各ドメインを同じサーバーに向ける
4. URLパラメータは不要（ドメインで自動識別）
