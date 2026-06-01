#!/usr/bin/env python3
import argparse
import os
import ssl
import json
import sys
import urllib.request
from datetime import datetime, timedelta, timezone
import pymysql

# 1. Load Environment Variables from .env file
def load_env(env_path):
    env_vars = {}
    if os.path.exists(env_path):
        with open(env_path, 'r', encoding='utf-8') as f:
            for line in f:
                line = line.strip()
                if not line or line.startswith('#'):
                    continue
                if '=' in line:
                    key, val = line.split('=', 1)
                    key = key.strip()
                    val = val.strip().strip("'\"")
                    env_vars[key] = val
    return env_vars

# Resolve paths relative to this script
script_dir = os.path.dirname(os.path.abspath(__file__))
project_root = os.path.abspath(os.path.join(script_dir, '..'))
env_path = os.path.join(project_root, '.env')
config_path = os.path.join(project_root, 'app', 'Config', 'ward_mappings.json')

env = load_env(env_path)

# ช่วงบันทึก (นาที) — 30 = ดึงทุก 30 นาที ปัดเวลาเป็น :00 / :30
RECORD_INTERVAL_MINUTES = 30

QUIET = False


def log(msg: str) -> None:
    if not QUIET:
        print(msg)


def log_error(msg: str) -> None:
    print(msg, file=sys.stderr)
    if QUIET:
        print(msg)

# Extract config values with defaults
db_host = env.get('database.default.hostname', '127.0.0.1')
db_user = env.get('database.default.username', 'root')
db_pass = env.get('database.default.password', 'rootpass')
db_name = env.get('database.default.database', 'nurse_ward')
db_port = int(env.get('database.default.port', '3306'))

api_base_url = env.get('IPD_API_BASE_URL', 'https://uttaradit-hosp.moph.go.th/api-hos/api/v1/ipd')
api_token = env.get('IPD_API_TOKEN', 'ipd-prod-key-2026')

# 2. Database Connection
def get_db_connection():
    # If running outside container, 'shared_mysql' will fail. We automatically fallback to '127.0.0.1'.
    hosts_to_try = [db_host]
    if db_host in ('shared_mysql', 'mariadb'):
        hosts_to_try.append('127.0.0.1')
        
    for host in hosts_to_try:
        try:
            conn = pymysql.connect(
                host=host,
                user=db_user,
                password=db_pass,
                database=db_name,
                port=db_port,
                cursorclass=pymysql.cursors.DictCursor
            )
            return conn
        except Exception as e:
            log_error(f"Failed to connect to MySQL on {host}: {e}")
    raise Exception("Could not connect to any database hosts.")

# 3. Synchronize Wards Mapping from Config file to Database
def sync_ward_mappings(conn):
    if not os.path.exists(config_path):
        log(f"Mapping config not found at {config_path}. Skipping mapping sync.")
        return
        
    log("Syncing ward mappings from ward_mappings.json to database (only seeding empty values)...")
    with open(config_path, 'r', encoding='utf-8') as f:
        mappings = json.load(f)
        
    cursor = conn.cursor()
    for ward_id, mapping in mappings.items():
        api_code = mapping.get('api_ward_code')
        api_name = mapping.get('api_ward_name')
        
        cursor.execute(
            """UPDATE wards 
               SET api_ward_code = IF(api_ward_code IS NULL OR api_ward_code = '', %s, api_ward_code),
                   api_ward_name = IF(api_ward_name IS NULL OR api_ward_name = '', %s, api_ward_name)
               WHERE id = %s""",
            (api_code, api_name, int(ward_id))
        )
    conn.commit()
    cursor.close()
    log("Ward mappings seeded/synced successfully.")

def log_unmapped_api_wards(api_items, find_ward_fn):
    """แจ้งรายการ API ที่ยังจับคู่แผนกในระบบไม่ได้"""
    missing = []
    seen = set()
    for item in api_items:
        code = item.get("ward") or item.get("nward")
        name = item.get("ward_name")
        key = (str(code or ''), str(name or '').strip())
        if key in seen:
            continue
        seen.add(key)
        if not find_ward_fn(code, name):
            missing.append(key)
    if missing:
        log("WARNING: API rows without matching ward (ตั้ง api_ward_name ใน Admin):")
        for code, name in missing[:20]:
            log(f"  ward={code!r} ward_name={name!r}")
        if len(missing) > 20:
            log(f"  ... and {len(missing) - 20} more")

# 4. Fetch Data from API endpoints — returns (items, raw_response dict for logging)
def fetch_api_data(endpoint):
    url = f"{api_base_url}/{endpoint}"
    req = urllib.request.Request(url, headers={"Authorization": f"Bearer {api_token}"})

    ctx = ssl.create_default_context()
    ctx.check_hostname = False
    ctx.verify_mode = ssl.CERT_NONE

    try:
        with urllib.request.urlopen(req, context=ctx, timeout=15) as response:
            res = json.loads(response.read().decode())
            items = []
            if res.get("success") and "data" in res:
                items = res["data"].get("items", []) or []
            return items, res
    except Exception as e:
        log_error(f"API Error fetching {endpoint}: {e}")
        return [], {"success": False, "endpoint": endpoint, "error": str(e)}


def save_fetch_log(conn, fetched_at, record_time_str, api_raw, wards_saved, patient_total, success=1, error_message=None):
    """บันทึก raw JSON จาก HOSxP API สำหรับหน้า admin/hosxp-logs (superadmin)."""
    try:
        payload = json.dumps({
            "source": "HOSxP IPD API",
            "base_url": api_base_url,
            "interval_minutes": RECORD_INTERVAL_MINUTES,
            "endpoints": api_raw,
        }, ensure_ascii=False)
        cursor = conn.cursor()
        cursor.execute(
            """INSERT INTO ipd_api_fetch_logs
               (fetched_at, record_time, success, wards_saved, patient_total, payload_json, error_message, created_at, updated_at)
               VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s)""",
            (
                fetched_at, record_time_str, 1 if success else 0, wards_saved, patient_total,
                payload, error_message, fetched_at, fetched_at,
            ),
        )
        conn.commit()
        cursor.close()
    except Exception as e:
        log_error(f"Could not save ipd_api_fetch_logs: {e}")

def load_wards_from_db(conn):
    cursor = conn.cursor()
    cursor.execute("SELECT id, name, api_ward_code, api_ward_name FROM wards WHERE is_active = 1")
    rows = cursor.fetchall()
    cursor.close()
    return rows


def load_aliases_from_db(conn):
    try:
        cursor = conn.cursor()
        cursor.execute(
            "SELECT ward_id, api_ward_code, api_ward_name FROM ward_api_aliases"
        )
        rows = cursor.fetchall()
        cursor.close()
        return rows
    except Exception:
        return []


def load_wards_from_mapping_file():
    """Fallback เมื่อไม่มี DB — ใช้ ward_mappings.json สำหรับ dry-run"""
    if not os.path.exists(config_path):
        return []
    with open(config_path, 'r', encoding='utf-8') as f:
        mappings = json.load(f)
    wards = []
    for ward_id, m in mappings.items():
        wards.append({
            'id': int(ward_id),
            'name': m.get('api_ward_name', f'ward-{ward_id}'),
            'api_ward_code': m.get('api_ward_code'),
            'api_ward_name': m.get('api_ward_name'),
        })
    return wards


def build_ward_lookup(db_wards, alias_rows=None):
    alias_rows = alias_rows or []
    ward_by_id = {w['id']: w for w in db_wards}
    name_to_ward = {}
    db_name_to_ward = {}
    code_to_wards = {}
    for w in db_wards:
        if w.get('api_ward_name'):
            name_to_ward[w['api_ward_name'].strip()] = w
        if w.get('name'):
            db_name_to_ward[w['name'].strip()] = w
        code = w.get('api_ward_code')
        if code:
            code_to_wards.setdefault(str(code).strip(), []).append(w)
    for a in alias_rows:
        wid = a.get('ward_id')
        w = ward_by_id.get(wid)
        alias_name = (a.get('api_ward_name') or '').strip()
        if w and alias_name:
            name_to_ward[alias_name] = w

    def find_ward(api_code, api_name):
        clean_name = api_name.strip() if api_name else ''
        if clean_name:
            if clean_name in name_to_ward:
                return name_to_ward[clean_name]
            if clean_name in db_name_to_ward:
                return db_name_to_ward[clean_name]
        if api_code:
            code_key = str(api_code).strip()
            matches = code_to_wards.get(code_key, [])
            if len(matches) == 1:
                return matches[0]
        return None

    return find_ward, name_to_ward, code_to_wards


def truncate_record_time(now: datetime) -> datetime:
    """ปัดเวลาเป็นช่วง RECORD_INTERVAL_MINUTES (เช่น 30 → :00 / :30)."""
    interval = RECORD_INTERVAL_MINUTES
    if interval <= 0 or interval > 60 or 60 % interval != 0:
        raise ValueError(f'Invalid RECORD_INTERVAL_MINUTES: {interval}')
    minute_slot = (now.minute // interval) * interval
    return now.replace(minute=minute_slot, second=0, microsecond=0)


def empty_census_stats():
    return {
        'patient_count': 0,
        'admissions_today': 0,
        'discharges_today': 0,
        'moves_in_today': 0,
        'moves_out_today': 0,
        'deaths_today': 0,
    }


def census_slot(census_by_key, ward_id, source_name):
    """คีย์แยกตามชื่อ API — ไม่รวมตอนบันทึก"""
    key = (int(ward_id), (source_name or '').strip())
    if key not in census_by_key:
        census_by_key[key] = empty_census_stats()
    return census_by_key[key]


def print_dry_run_summary(db_wards, census_by_key, record_time_str):
    print(f"\n=== DRY RUN — ไม่บันทึก DB (record_time={record_time_str}) ===")
    ward_by_id = {w['id']: w for w in db_wards}
    matched = []
    for (wid, source), stats in census_by_key.items():
        if not (stats.get('patient_count') or stats.get('admissions_today') or stats.get('discharges_today')):
            continue
        w = ward_by_id.get(wid, {'id': wid, 'name': f'ward-{wid}'})
        matched.append((w, source, stats))
    matched.sort(key=lambda x: (str(x[0].get('api_ward_code') or ''), x[1]))

    print(f"แถว API ที่บันทึกแยก: {len(matched)} (รวมยอดตอนแสดงผลในระบบ)\n")
    for w, source, stats in matched:
        print(
            f"  [{w['id']}] {w.get('name')} | source={source!r} "
            f"=> patients={stats['patient_count']} adm={stats['admissions_today']} "
            f"dis={stats['discharges_today']} death={stats['deaths_today']} "
            f"in={stats['moves_in_today']} out={stats['moves_out_today']}"
        )


def run_fetch(dry_run=False):
    global QUIET
    conn = None
    db_wards = []

    try:
        conn = get_db_connection()
        if not dry_run:
            sync_ward_mappings(conn)
        db_wards = load_wards_from_db(conn)
        log(f"Connected to MySQL — {len(db_wards)} active wards")
    except Exception as e:
        if dry_run:
            db_wards = load_wards_from_mapping_file()
            log(f"MySQL unavailable ({e}) — dry-run using ward_mappings.json ({len(db_wards)} entries)")
        else:
            raise

    alias_rows = load_aliases_from_db(conn) if conn else []
    find_ward, _, _ = build_ward_lookup(db_wards, alias_rows)
    api_raw = {}

    # แยกบันทึกตาม (ward_id, source_api_ward_name) — ไม่รวมตอน cron
    census_by_key = {}

    # 1. Fetch current patients count
    log("Fetching current patients...")
    curr_patients, api_raw["current-patients"] = fetch_api_data("current-patients")
    if not curr_patients and not dry_run:
        raise Exception("API current-patients returned no data")
    log_unmapped_api_wards(curr_patients, find_ward)
    for item in curr_patients:
        code = item.get("ward")
        name = item.get("ward_name")
        ward = find_ward(code, name)
        if ward:
            src = (name or '').strip()
            census_slot(census_by_key, ward['id'], src)['patient_count'] = int(item.get("count_an", 0))

    # 2. Fetch admissions today
    log("Fetching admissions today...")
    admissions, api_raw["admissions-today"] = fetch_api_data("admissions-today")
    for item in admissions:
        code = item.get("ward")
        name = item.get("ward_name")
        ward = find_ward(code, name)
        if ward:
            src = (name or '').strip()
            census_slot(census_by_key, ward['id'], src)['admissions_today'] = int(item.get("total_admissions", 0))

    # 3. Fetch discharges and deaths today
    log("Fetching discharges today...")
    discharges, api_raw["discharges-today"] = fetch_api_data("discharges-today")
    for item in discharges:
        code = item.get("ward")
        name = item.get("ward_name")
        ward = find_ward(code, name)
        if ward:
            src = (name or '').strip()
            slot = census_slot(census_by_key, ward['id'], src)
            slot['discharges_today'] = int(item.get("total_discharges", 0))
            slot['deaths_today'] = int(item.get("count_dead", 0))

    # 4. Fetch bed moves today
    log("Fetching bed moves today...")
    moves, api_raw["bed-moves-today"] = fetch_api_data("bed-moves-today")
    for item in moves:
        code = item.get("nward")
        name = item.get("ward_name")
        ward = find_ward(code, name)
        if ward:
            src = (name or '').strip()
            slot = census_slot(census_by_key, ward['id'], src)
            slot['moves_in_today'] = int(item.get("count_receive", 0))
            slot['moves_out_today'] = int(item.get("count_move", 0))

    # Get local Thailand time (UTC+7)
    bangkok_tz = timezone(timedelta(hours=7))
    now = datetime.now(bangkok_tz)
    record_time = truncate_record_time(now)
    record_time_str = record_time.strftime('%Y-%m-%d %H:%M:%S')
    now_str = now.strftime('%Y-%m-%d %H:%M:%S')

    log(f"Recording census data for slot ({RECORD_INTERVAL_MINUTES} min): {record_time_str}")

    if dry_run:
        print_dry_run_summary(db_wards, census_by_key, record_time_str)
        if conn:
            conn.close()
        return record_time_str, len(census_by_key), sum(1 for s in census_by_key.values() if s['patient_count'] > 0)

    if not conn:
        raise Exception("Database connection required (use --dry-run to test API only)")

    cursor = conn.cursor()

    # Save/Upsert — แยกแถวตาม source_api_ward_name
    records_saved = 0
    for (ward_id, source_name), stats in census_by_key.items():
        cursor.execute(
            """SELECT id FROM hourly_patient_census
               WHERE ward_id = %s AND record_time = %s AND source_api_ward_name = %s""",
            (ward_id, record_time_str, source_name),
        )
        existing = cursor.fetchone()

        if existing:
            cursor.execute(
                """UPDATE hourly_patient_census
                   SET patient_count = %s, admissions_today = %s, discharges_today = %s,
                       moves_in_today = %s, moves_out_today = %s, deaths_today = %s, updated_at = %s
                   WHERE id = %s""",
                (
                    stats['patient_count'], stats['admissions_today'], stats['discharges_today'],
                    stats['moves_in_today'], stats['moves_out_today'], stats['deaths_today'],
                    now_str, existing['id'],
                ),
            )
        else:
            cursor.execute(
                """INSERT INTO hourly_patient_census
                   (ward_id, record_time, source_api_ward_name, patient_count, admissions_today,
                    discharges_today, moves_in_today, moves_out_today, deaths_today, created_at, updated_at)
                   VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)""",
                (
                    ward_id, record_time_str, source_name, stats['patient_count'], stats['admissions_today'],
                    stats['discharges_today'], stats['moves_in_today'], stats['moves_out_today'],
                    stats['deaths_today'], now_str, now_str,
                ),
            )
        records_saved += 1

    conn.commit()
    cursor.close()

    patient_total = sum(int(s['patient_count']) for s in census_by_key.values())
    save_fetch_log(conn, now_str, record_time_str, api_raw, records_saved, patient_total, success=1)
    conn.close()

    wards_with_patients = len({wid for (wid, _), s in census_by_key.items() if s['patient_count'] > 0})
    msg = (
        f"Completed! Upserted {records_saved} ward census records for {record_time_str} "
        f"({wards_with_patients} wards with patients)."
    )
    log(msg)
    return record_time_str, records_saved, wards_with_patients

def main():
    global QUIET
    parser = argparse.ArgumentParser(
        description='Fetch IPD census from hospital API (default: 30-minute record slots)',
    )
    parser.add_argument(
        '--dry-run',
        action='store_true',
        help='ดึง API และแสดงผลจับคู่แผนก ไม่บันทึกลง MySQL',
    )
    parser.add_argument(
        '--quiet',
        action='store_true',
        help='ลด output สำหรับ cron (แสดงเฉพาะสรุปและ error)',
    )
    args = parser.parse_args()
    QUIET = args.quiet
    try:
        run_fetch(dry_run=args.dry_run)
    except Exception as e:
        log_error(f"FATAL: {e}")
        if not args.dry_run:
            try:
                conn = get_db_connection()
                bangkok_tz = timezone(timedelta(hours=7))
                now = datetime.now(bangkok_tz)
                now_str = now.strftime('%Y-%m-%d %H:%M:%S')
                slot_str = truncate_record_time(now).strftime('%Y-%m-%d %H:%M:%S')
                save_fetch_log(conn, now_str, slot_str, {}, 0, 0, success=0, error_message=str(e))
                conn.close()
            except Exception:
                pass
        sys.exit(1)


if __name__ == '__main__':
    main()
