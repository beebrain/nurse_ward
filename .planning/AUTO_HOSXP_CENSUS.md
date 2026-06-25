# แผน Auto-fill Census จาก HosXP (ภายใน 24 ชม.)

**เป้าหมาย:** ใช้ฟอร์มกรอกมือเป็นหลัก — ถ้าไม่มีการบันทึกภายใน 24 ชม. หลังจบเวร ระบบสร้าง `daily_census` จาก HosXP + ข้อมูลยกมา และตั้ง `entry_source = autohosxp`

**สถานะ:** วางแผน (ยังไม่ implement)  
**อัปเดต:** 2026-05-29

---

## 1. หลักการ

| ลำดับ | กติกา |
|-------|--------|
| 1 | บันทึกมือ (`entry_source = manual`) มีความสำคัญสูงสุด — **ไม่ทับ** |
| 2 | ครบ 24 ชม. หลังจบเวร ยังไม่มีแถว `daily_census` → สร้าง `autohosxp` |
| 3 | มีแถว `autohosxp` แล้ว พยาบาลแก้ทีหลังได้ → เปลี่ยนเป็น `manual` + audit log |
| 4 | ต้องมีข้อมูล `hourly_patient_census` ในช่วงเวรนั้น — ไม่มี则 skip + log |

---

## 2. กำหนดเวลา (24 ชั่วโมง)

ใช้เวลาไทย (Asia/Bangkok) สอดคล้อง `HourlyCensusModel::getShiftTotals`

| เวร | ช่วงเวร | จบเวร (shift_end) | ครบกำหนด auto-fill (deadline) |
|-----|---------|-------------------|-------------------------------|
| Night | 00:00–08:00 | วันเดียวกัน 08:00 | +24h → 08:00 วันถัดไป |
| Morning | 08:00–16:00 | วันเดียวกัน 16:00 | +24h → 16:00 วันถัดไป |
| Afternoon | 16:00–23:59 | วันเดียวกัน 23:59 | +24h → 23:59 วันถัดไป |

**เงื่อนไขรัน cron:** `now >= deadline` และ `daily_census` ยังไม่มี `(ward_id, record_date, shift)`

```
due_shifts = ทุก ward ที่ active + มี api mapping
             ที่ deadline ผ่านแล้ว และยังไม่มี record
```

---

## 3. แหล่งข้อมูลต่อฟิลด์

### 3.1 จาก HosXP (`hourly_patient_census` + `getShiftTotals`)

ใช้ logic เดียวกับหน้าบันทึก Handover:

| ฟิลด์ census | แหล่ง |
|--------------|--------|
| `admissions` | delta `admissions_today` ในเวร |
| `discharges` | delta `discharges_today` |
| `transfers_in` | delta `moves_in_today` |
| `transfers_out` | delta `moves_out_today` |
| `deaths` | delta `deaths_today` |
| `total_patients` / คงเหลือ | `patient_count` ช่วงท้ายเวร |

### 3.2 จากข้อมูลเก่า (ยกมา — จนกว่า HosXP จะส่ง level)

| ฟิลด์ | แหลงยกมา | หมายเหตุ |
|-------|----------|----------|
| `patients_*_level_*` | **เวรก่อนหน้า** (same ward) | ปรับสัดส่วนให้รวม = `total_patients` จาก HosXP |
| `nurses_*` | **เวรก่อนหน้า** | HosXP ไม่มีคนพยาบาล — ยกมาชั่วคราว |
| `equipment_*` | **เวรก่อนหน้า** | เดียวกัน |
| `carried_forward_patients` | `getPreviousShiftRecord` | มี logic อยู่แล้ว |

**อัลกอริทึม level ยกมา (ชั่วคราว):**

```
1. อ่าน level L1–L5 จากเวรก่อนหน้า
2. ถ้า total_patients (HosXP) = 0 → ศูนย์ทุก level
3. ถ้าเวรก่อนหน้า total = 0 → ใส่ทั้งหมดใน L3 (ค่า default ward ปรับได้ภายหลัง)
4. มิฉะนั้น scale สัดส่วน: new_L = round(old_L / old_total × hosxp_total)
5. ปรับผลรวมให้ตรง hosxp_total (distribute remainder จาก level สูงลง)
```

### 3.3 อนาคต (เมื่อ HosXP ส่ง level)

- แทนขั้นตอน 3.2 ด้วย level จาก slot ท้ายเวร หรือ timeline
- `entry_source` ยังเป็น `autohosxp` — เพิ่ม `level_source = hosxp`

---

## 4. Schema

### 4.1 `daily_census` — คอลัมน์ใหม่

```sql
entry_source ENUM('manual','autohosxp') NOT NULL DEFAULT 'manual'
auto_filled_at DATETIME NULL          -- เวลาที่ระบบสร้าง
auto_hosxp_slot_time DATETIME NULL    -- record_time ของ HosXP ที่ใช้เป็นคงเหลือ
```

### 4.2 `census_auto_hosxp_log` (audit)

```sql
id, ward_id, record_date, shift,
action ENUM('created','skipped','failed'),
reason VARCHAR(255) NULL,          -- e.g. no_hourly_data, already_manual
hosxp_snapshot JSON NULL,          -- totals ที่ใช้
created_at
```

---

## 5. สถาปัตยกรรม

```
cron (ทุกชั่วโมง)
  → php spark census:auto-hosxp
      → AutoHosxpCensusService::processDueShifts()
          → หา due shifts
          → ข้ามถ้ามี manual
          → HourlyCensusModel::getShiftTotals()
          → CensusCarryForwardHelper::buildLevelsFromPrevious()
          → CensusModel::insert + computeDerived + recalculateProductivity
          → log + แจ้งเตือน (optional)
```

**ไฟล์ใหม่ (แนะนำ):**

| ไฟล์ | บทบาท |
|------|--------|
| `app/Commands/AutoHosxpCensus.php` | Spark command |
| `app/Services/AutoHosxpCensusService.php` | business logic |
| `app/Services/CensusCarryForwardHelper.php` | level/staff carry-forward |
| Migration `AddEntrySourceToDailyCensus` | schema |
| Migration `CreateCensusAutoHosxpLog` | audit |

**Reuse:**

- `HourlyCensusModel::getShiftTotals()` — มีแล้ว
- `CensusModel::computeDerived()` / `recalculateProductivity()` — มีแล้ว
- `CensusEditLogModel` — บันทึกเมื่อพยาบาลแก้ autohosxp

---

## 6. UI / UX

### 6.1 หน้าบันทึก (`census/create`)

- โหลด record `autohosxp` ได้เหมือนมือ — แสดงแถบเตือน:
  > บันทึกอัตโนมัติจาก HosXP เมื่อ … — กรุณาตรวจสอบคนพยาบาลและระดับผู้ป่วย

### 6.2 ประวัติ / Productivity

- Badge `Auto HosXP` สีเทา-ฟ้า คอลัมน์ `entry_source`
- Tooltip: `auto_filled_at`, slot HosXP ที่ใช้
- กรองรายงาน: รวม/แยก autohosxp

### 6.3 เมื่อพยาบาลบันทึกทับ

```
POST store/confirm บน record autohosxp:
  entry_source → manual
  created_by → user ปัจจุบัน
  edit_log: action = autohosxp_corrected
```

---

## 7. Cron บน production

```cron
# มีอยู่แล้ว — ทุก 30 นาที
*/30 * * * * .../run_ipd_hourly_fetch.sh

# ใหม่ — ทุกชั่วโมง นาทีที่ 5
5 * * * * cd /path/nurse_ward && php spark census:auto-hosxp >> writable/logs/auto_hosxp_census.log 2>&1
```

รันหลัง fetch HosXP อย่างน้อย 1 รอบในช่วงเวรนั้น

---

## 8. ขั้นตอน implement

### Phase A — Foundation (1–2 วัน)

- [ ] Migration `entry_source`, `auto_filled_at`, `auto_hosxp_slot_time`
- [ ] Migration `census_auto_hosxp_log`
- [ ] `CensusCarryForwardHelper` (level scale + staff copy)
- [ ] `AutoHosxpCensusService` + unit tests (due detection, skip manual, build payload)
- [ ] Spark command `census:auto-hosxp` + `--dry-run` + `--date=`

### Phase B — Integration (1 วัน)

- [ ] `CensusModel` allowedFields + history select `entry_source`
- [ ] `store`/`confirm`: flip `autohosxp` → `manual` on user edit
- [ ] `CensusEditLogModel` action types

### Phase C — UI (1 วัน)

- [ ] Badge + banner ใน create/history
- [ ] Admin log viewer (optional): รายการ skip/fail

### Phase D — อนาคต

- [ ] HosXP level ใน `hourly_patient_census` → แทน carry-forward level
- [ ] แจ้งเตือน LINE/email ก่อน deadline 12 ชม. (optional)

---

## 9. กรณีพิเศษ

| กรณี | การทำ |
|------|--------|
| Ward ไม่มี `api_ward_code` | skip + log `no_api_mapping` |
| ไม่มี hourly ในเวร | skip + log `no_hourly_data` |
| มี autohosxp แล้ว | ไม่สร้างซ้ำ |
| มี manual แล้ว | ไม่สร้าง |
| Night เวรข้ามวัน (record_date) | ใช้ `record_date` = วันที่ 00:00 ของเวร Night (ตามฟอร์มปัจจุบัน) |
| LR (`productivity_mode=turnover`) | auto-fill แล้วคำนวณ productivity ตาม `NursingProductivityService` |

---

## 10. เกณฑ์ยอมรับ (UAT)

1. ไม่บันทึกเวรบ่ายภายใน 24 ชม. → มีแถว `autohosxp` movement ตรง HosXP
2. บันทึกมือก่อน deadline → **ไม่** สร้าง autohosxp
3. แก้ไข autohosxp → กลายเป็น `manual` + มี edit log
4. ประวัติแสดง badge Auto HosXP
5. `--dry-run` แสดงรายการที่จะสร้างโดยไม่ insert

---

## 11. ความเสี่ยง

| ความเสี่ยง | การลด |
|------------|--------|
| คนพยาบาลยกมาผิด | แสดงเตือนให้แก้; ไม่ใช้ autohosxp ใน KPI จนกว่าจะ manual confirm (optional flag) |
| Level ไม่ตรงจริง | รอ HosXP level; ระบุในรายงานว่า level ยกมา |
| Cron ซ้ำ | idempotent: check exists ก่อน insert |
| API mapping ผิด | ใช้ `ward_api_aliases` + log unmapped |
