# coachtech 勤怠管理アプリ
## 概要
Laravelで開発した、勤怠管理アプリケーションです。  

## 作成者
浅井 明日香

## 使用技術
### バックエンド
- PHP 8.5
- Laravel 10.4
- Laravel Fortify（認証機能）
- Laravel Sanctum（APIトークン認証）
- MySQL 8.4

### フロントエンド
- Tailwind CSS 3.4
- Vite
- Alpine.js

### 開発ツール
- Docker / Docker Compose / Laravel Sail
- phpMyAdmin
- Nginx
- PHPUnit（テスト）
- Postman（API動作確認）
- Git/GitHub（バージョン管理）

## ER図
```mermaid
erDiagram
    users {
        bigint id PK
        string name
        string email UK
        timestamp email_verified_at
        string password
        boolean admin_status
        string remember_token
        timestamp created_at
        timestamp updated_at
    }

    attendance_records {
        bigint id PK
        bigint user_id FK
        timestamp clock_in_at
        timestamp clock_out_at
        timestamp created_at
        timestamp updated_at
    }

    break_times {
        bigint id PK
        bigint attendance_record_id FK
        timestamp break_start_at
        timestamp break_end_at
        timestamp created_at
        timestamp updated_at
    }

    correction_requests {
        bigint id PK
        bigint attendance_record_id FK
        bigint user_id FK
        timestamp requested_clock_in_at
        timestamp requested_clock_out_at
        text comment
        string approval_status
        bigint approved_by FK
        timestamp approved_at
        timestamp created_at
        timestamp updated_at
    }

    correction_breaks {
        bigint id PK
        bigint correction_request_id FK
        timestamp break_start_at
        timestamp break_end_at
        timestamp created_at
        timestamp updated_at
    }

    users ||--o{ attendance_records : "has many"

    attendance_records ||--o{ break_times : "has many"

    users ||--o{ correction_requests : "requests"
    users ||--o{ correction_requests : "approves"

    attendance_records ||--o{ correction_requests : "has many"

    correction_requests ||--o{ correction_breaks : "has many"
}
```

## URL
- 開発環境：http://localhost
- phpMyAdmin：http://localhost:8080/

## 動作環境
- Docker
- Docker Compose

※ Windowsの場合はWSL2の利用を推奨します。

## 環境構築手順
//ログイン方法を記載

## テスト実行
```
sail artisan test
```
カバレッジ付きで実行する場合:
```
sail artisan test --coverage
```