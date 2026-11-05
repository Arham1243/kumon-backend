# Core

Multi-center attendance for Kumon.

Each center keeps its own students. A student checks in and out by entering their student number on a public kiosk: the first punch of the day is check-in, the second is check-out. Staff can manage students and attendance, export records, and the system can remind about or auto-close sessions that were never checked out.

Around that, the app handles center settings, users, roles, and permissions.

## `students`

| Column | Type | Notes |
|---|---|---|
| `id` | `BIGINT UNSIGNED` PK | Auto-increment |
| `uuid` | `CHAR(36)` | Unique |
| `company_id` | `BIGINT UNSIGNED` | FK → `admin_companies.id`, cascade on delete |
| `student_id` | `BIGINT UNSIGNED` | The student's own ID number |
| `name` | `VARCHAR(255)` | |
| `card_uid` | `VARCHAR(32)` | Nullable; current NFC card UID (hex, no separators) |
| `created_at` | `TIMESTAMP` | Nullable |
| `updated_at` | `TIMESTAMP` | Nullable |

**Unique:** `(company_id, student_id)` — the same student number can exist at different companies, but not twice inside one company.

**Unique:** `(company_id, card_uid)` — at most one student per center holds a given card UID while it is assigned (multiple null `card_uid` values are allowed).

`student_id` is the business number the kiosk punches. It is not the row's primary key.

## `attendances`

| Column | Type | Notes |
|---|---|---|
| `id` | `BIGINT UNSIGNED` PK | Auto-increment |
| `uuid` | `CHAR(36)` | Unique |
| `company_id` | `BIGINT UNSIGNED` | FK → `admin_companies.id`, cascade on delete |
| `student_id` | `BIGINT UNSIGNED` | FK → `students.id`, cascade on delete |
| `date` | `DATE` | Attendance day |
| `check_in_time` | `TIME` | Required |
| `check_out_time` | `TIME` | Nullable until the student checks out |
| `created_at` | `TIMESTAMP` | Nullable |
| `updated_at` | `TIMESTAMP` | Nullable |

**Unique:** `(company_id, student_id, date)` — one attendance row per student per day.

## How they connect

```text
admin_companies (id)
    └── students (company_id → admin_companies.id)
            student_id = the student's own number
            card_uid = current NFC UID (optional)
        └── attendances (student_id → students.id)
                company_id → admin_companies.id
```

`attendances.student_id` points at `students.id`, the primary key. It does not store `students.student_id`. A student with attendance rows cannot be deleted from the app, even though the database foreign key is set to cascade.
