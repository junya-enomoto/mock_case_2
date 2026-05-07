# 勤怠管理アプリ

## プロジェクト概要

このプロジェクトは、LaravelとDockerを使用して構築されたシンプルな勤怠管理アプリケーションです。
一般ユーザーは出退勤・休憩の打刻、自身の勤怠履歴の確認、修正申請を行うことができます。
管理者は、全ユーザーの勤怠履歴の確認、スタッフ一覧の管理、および一般ユーザーからの修正申請の承認・却下を行うことができます。

## 機能一覧

### 一般ユーザー機能
- **会員登録・ログイン**: Fortifyを使用した認証機能。
- **勤怠打刻**: 出勤、退勤、休憩入、休憩戻の打刻（複数回休憩対応）。
- **勤怠一覧**: 月ごとの自身の勤怠履歴を表示。
- **勤怠詳細**: 特定の日の勤怠詳細を表示し、修正申請を提出。
- **申請一覧**: 自身の提出した修正申請のステータス（承認待ち、承認済み）を確認。
- **メール認証**: 新規登録時のメールアドレス認証機能。

### 管理者機能
- **管理者ログイン**: 一般ユーザーとは分離された管理者専用のログイン。
- **勤怠一覧**: 全ユーザーの日ごとの勤怠履歴を表示し、日付移動が可能。
- **勤怠詳細**: 各ユーザーの勤怠詳細を直接編集・修正。
- **スタッフ一覧**: 全一般ユーザーのリストを表示し、各スタッフの勤怠詳細へ遷移可能。
- **スタッフ別勤怠一覧**: 特定のスタッフの月ごとの勤怠履歴を表示し、月移動が可能。
- **申請一覧**: 全ユーザーからの修正申請を一覧で表示（承認待ち、承認済みでフィルタリング可能）。
- **修正申請承認**: ユーザーからの修正申請の内容を確認し、承認を実行。

## データベース設計ER図
mermaid

erDiagram
    USERS ||--o{ ATTENDANCES : registers
    USERS ||--o{ CORRECTION : "has_requests"

    ATTENDANCES ||--o{ RESTS : has
    ATTENDANCES ||--o{ CORRECTION : corrects

    CORRECTION ||--o{ CORRECTION_RESTS : details

    RESTS }o--o| CORRECTION_RESTS : original_rest

    USERS {
        int id PK
        varchar name
        varchar email
        datetime email_verified_at
        varchar password
        datetime created_at
        datetime updated_at
    }
    ADMINS {
        int id PK
        varchar name
        varchar email
        varchar password
        datetime created_at
        datetime updated_at
    }
    ATTENDANCES {
        int id PK
        int user_id FK
        date work_date
        time clock_in
        time clock_out
        datetime created_at
        datetime updated_at
    }
    RESTS {
        int id PK
        int attendance_id FK
        time start_time
        time end_time
        datetime created_at
        datetime updated_at
    }
    CORRECTION {
        int id PK
        int attendance_id FK
        time clock_in_new
        time clock_out_new
        text remarks
        varchar status
        datetime created_at
        datetime updated_at
    }
    CORRECTION_RESTS {
        int id PK
        int correction_id FK
        int original_rest_id FK
        time start_time_new
        time end_time_new
        datetime created_at
        datetime updated_at
    }

## 使用技術

- **バックエンド**: PHP 8.x, Laravel 11.x
- **データベース**: MySQL 8.x
- **フロントエンド**: Bladeテンプレート, Alpine.js (Fortifyデフォルト)
- **開発環境**: Docker, Docker Compose
- **認証**: Laravel Fortify
- **メール**: MailHog (開発時のみ)

## 環境構築手順

1.  **リポジトリのクローン**
    ```bash
    git clone https://github.com/junya-enomoto/mock_case_2.git
    ```

2.  **`.env` ファイルの設定**
    `.env.example` をコピーして `.env` ファイルを作成し、データベースやメール設定を記述します。
    ```bash
    cp .env.example .env
    ```
    `.env` ファイルを開き、以下の設定を確認または追記します。
    ```dotenv
    APP_NAME="勤怠管理アプリ"
    APP_ENV=local
    APP_KEY=
    APP_DEBUG=true
    APP_LOCALE=ja # 日本語化のため

    DB_CONNECTION=mysql
    DB_HOST=mysql
    DB_PORT=3306
    DB_DATABASE=laravel_db
    DB_USERNAME=laravel_user
    DB_PASSWORD=laravel_pass 

    MAIL_MAILER=smtp
    MAIL_HOST=mailhog
    MAIL_PORT=1025
    MAIL_USERNAME=null
    MAIL_PASSWORD=null
    MAIL_ENCRYPTION=null
    MAIL_FROM_ADDRESS="hello@example.com"
    MAIL_FROM_NAME="${APP_NAME}"
    ```

3.  **Docker環境の構築と起動**
    プロジェクトルートディレクトリ（`docker-compose.yml` がある場所）でDocker Composeを起動します。
    ```bash
    docker-compose up -d --build
    ```

4.  **Composerの依存関係インストール**
    PHPコンテナ内でComposerコマンドを実行し、必要なライブラリをインストールします。
    ```bash
    docker-compose run --rm php composer install
    ```

5.  **アプリケーションキーの生成**
    Laravelアプリケーションキーを生成します。
    ```bash
    docker-compose run --rm php php artisan key:generate
    ```

6.  **データベースのマイグレーションとシーディング**
    データベーステーブルを作成し、テスト用のダミーデータを投入します。（既存データは全て削除されます）
    ```bash
    docker-compose run --rm php php artisan migrate:fresh --seed
    ```

7.  **Laravelのキャッシュクリア**
    アプリケーションのキャッシュをクリアします。
    ```bash
    docker-compose run --rm php php artisan optimize:clear
    ```

## 動作確認

ブラウザで以下のURLにアクセスしてください。
- **アプリケーション**: `http://localhost/login`
- **MailHog (メール受信確認)**: `http://localhost:8025`

### ログイン情報（重要）

`php artisan migrate:fresh --seed` コマンド実行時に、以下のテスト用アカウントがデータベースに自動登録されます。

**管理者ユーザー**
- 名前: `管理者`
- メールアドレス: `admin@example.com`
- パスワード: `password`
- ログインURL: `http://localhost/admin/login`

**一般ユーザー（例として「西怜奈」アカウントを使用）**
- 名前: `西怜奈`
- メールアドレス: `reina.n@coatctech.com`
- パスワード: `password`
- ログインURL: `http://localhost/login`

## テストデータ生成ロジック
- php artisan db:seed を実行することで、動作確認用のダミーデータを自動生成します。
- AttendanceSeeder では、以下のルールに基づきリアルな勤怠状況を再現しています。
- 対象期間と頻度各ユーザーに対し、本日より過去 30日間 のデータを生成します。毎日出勤とするのではなく、20%の確率で欠勤（データなし） を作り、リアルな出勤簿を再現しています。
- 勤務時間と休憩基本の勤務時間は 09:00 〜 18:00 としています。
- すべての勤務データに対し、12:00 〜 13:00 の休憩データを自動付随させています。
- 修正申請データのシミュレート生成された勤怠データのうち 10%の確率 で、「承認待ち（pending）」状態の修正申請データを自動作成します。
- 修正理由として「電車の遅延のため」などのテキストを挿入し、管理画面側での承認フローを即座にテストできる状態にします。

## テストの実行

PHPUnitによるテストを実行します。
```bash
docker-compose run --rm php php artisan test