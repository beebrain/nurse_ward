# การจับคู่ Ward จาก API โรงพยาบาล

## กรณีรหัสเดียวกัน หลายแผนกย่อย (ตัวอย่าง ศัลยกรรมหญิง)

API ส่ง `ward` = `08` เหมือนกัน แต่แยกด้วย `ward_name`:

| api_ward_name (ใน API) | ชื่อในระบบ nurse_ward |
|------------------------|------------------------|
| `ศญ1_สามัญ` | หอผู้ป่วยศัลยกรรมหญิง 1 (สามัญ) |
| `ศญ2_สามัญ` | หอผู้ป่วยศัลยกรรมหญิง 2 (สามัญ) |
| `ศญ_พิเศษ` | หอผู้ป่วยศัลยกรรมหญิง (พิเศษ) |
| `ศญ_แทรก1` | หอผู้ป่วยศัลยกรรมหญิง (แทรก 1) |

ทุกแผนกใช้ **API Ward Code** = `08` และ **API Ward Name** ต้องตรง `ward_name` จาก API ทุกตัวอักษร

## รวมหลายชื่อ API เข้าแผนกเดียว

เมื่อรหัส `ward` เดียวกันมีหลาย `ward_name` (เช่น `01` มี A, B, C):

- แผนกที่ 1: ชื่อหลัก = A, **ชื่อ API เพิ่มเติม** = B → ระบบ **บวกยอด** A+B
- แผนกที่ 2: ชื่อหลัก = C

ตั้งค่าใน Admin → จัดการ Ward → แก้ไข → เลือกชื่อหลัก แล้วติ๊กชื่อเพิ่มเติม (ตาราง `ward_api_aliases`)

## หลัง migrate

```bash
php spark migrate
```

ตรวจสอบ:

```sql
SELECT id, name, api_ward_code, api_ward_name
FROM wards
WHERE api_ward_code = '08';
```

## ดึงข้อมูลอัตโนมัติ (ทุก 30 นาที)

```bash
# ติดตั้ง cron (รันทุก 30 นาที — น. :00 และ :30 ตามเวลาไทย)
bash scripts/install-hourly-cron.sh

# ทดสอบด้วยมือ
bash scripts/run_ipd_hourly_fetch.sh
```

Log: `writable/logs/ipd_hourly_fetch.log`  
ข้อมูลบันทึกใน `hourly_patient_census` (ทุก ward × ทุกช่วง 30 นาที — `record_time` เป็น :00 หรือ :30)

```bash
python3 scripts/fetch_ipd_hourly.py
```

สคริปต์จับคู่ **ชื่อย่อย (`ward_name`) ก่อน** แล้วค่อยใช้รหัส `ward` (เมื่อมีแผนกเดียวต่อรหัส)
