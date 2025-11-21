# Nocturnal Shift

**Nocturnal Shift** は、飲食業界などの夜勤シフト管理に特化した、マルチテナント対応の従業員シフト・勤怠管理システムです。

## 📖 概要

このプロジェクトは、複数の店舗や組織（テナント）を単一のアプリケーションで管理できるSaaS型アプリケーションとして設計されています。
Symfony (バックエンド) と Vue.js (フロントエンド) を組み合わせたモダンなアーキテクチャを採用し、堅牢なセキュリティと快適なユーザー体験を提供します。

## ✨ 主な機能

- **マルチテナント対応**: データとアクセス権限をテナントごとに完全に分離。
- **権限管理 (RBAC)**: ロールベースのアクセス制御により、きめ細やかな権限設定が可能。
- **シフト管理**: 従業員のシフト作成、編集、ステータス管理、チップ記録。
- **勤怠管理**: 出勤・退勤の打刻、休憩時間の記録、位置情報記録。
- **従業員管理**: プロフィール管理、ユーザーアカウント連携。
- **イベント管理**: 店舗ごとのイベントスケジュール管理。

## 🛠️ 技術スタック

### バックエンド
- **Framework**: Symfony 6.4
- **Language**: PHP 8.1+
- **ORM**: Doctrine ORM 3.5
- **Database**: PostgreSQL 15

### フロントエンド
- **Framework**: Vue.js 3.4 (Composition API)
- **UI Library**: Vuetify 3.5
- **Build Tool**: Vite 5.0
- **Icons**: Material Design Icons

### インフラ・開発環境
- **Container**: Docker & Docker Compose
- **OS**: Linux (WSL2 推奨)

## 🚀 セットアップ手順

開発環境は Docker を使用して構築することを推奨します。

### 前提条件
- Docker & Docker Compose
- WSL2 (Windowsの場合)

### 1. リポジトリのクローン
```bash
git clone <repository-url>
cd nocturnal-shift
```

### 2. 環境変数の設定
`.env` ファイルをコピーして `.env.local` を作成し、必要に応じて設定を変更してください。
Docker環境を使用する場合、データベース接続情報は `docker-compose.yml` の設定に合わせて自動的に解決される場合がありますが、確認してください。

```bash
cp .env .env.local
```

### 3. Docker コンテナの起動
以下のコマンドでアプリケーション、データベース、Webサーバー、Node.jsサーバーを起動します。

```bash
docker-compose up -d
```

初回起動時は `npm install` などが実行されるため、立ち上がりまで時間がかかる場合があります。

### 4. データベースのセットアップ
PHPコンテナに入ってデータベースとスキーマを作成します。

```bash
# PHPコンテナに入る
docker-compose exec php bash

# データベース作成
php bin/console doctrine:database:create

# マイグレーション実行
php bin/console doctrine:migrations:migrate

# (オプション) 初期データ投入
php bin/console doctrine:fixtures:load
```

### 5. アクセス
ブラウザで以下のURLにアクセスしてください。

- **Webアプリケーション**: http://localhost:8005
- **Vite開発サーバー**: http://localhost:5175 (HMR対応)

## 📂 プロジェクト構造

```
.
├── assets/             # フロントエンドソース (Vue.js)
├── bin/                # Symfony コンソールコマンド
├── config/             # Symfony 設定ファイル
├── docker/             # Docker 設定ファイル
├── migrations/         # データベースマイグレーション
├── public/             # 公開ディレクトリ (index.php, assets)
├── src/                # バックエンドソース (PHP)
│   ├── Controller/     # コントローラー
│   ├── Entity/         # エンティティ
│   ├── Repository/     # リポジトリ
│   ├── Security/       # セキュリティ・Voter
│   └── Service/        # ビジネスロジック
├── templates/          # Twig テンプレート
└── tests/              # テストコード
```

*このドキュメントはプロジェクトの進行に合わせて随時更新されます。*
