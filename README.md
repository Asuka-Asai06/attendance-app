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