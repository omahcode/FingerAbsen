from flask import Flask, request, jsonify
from zk import ZK
from zk.user import User
from zk.finger import Finger
import base64
import threading
import time
import requests

app = Flask(__name__)

# Konfigurasi Default API Laravel
LARAVEL_API_REALTIME = 'http://127.0.0.1:8000/api/attendance/realtime'
LARAVEL_API_DEVICES = 'http://127.0.0.1:8000/api/devices'
LARAVEL_API_SYNC_TEMPLATES = 'http://127.0.0.1:8000/api/sync/templates'

def get_zk_conn(data):
    ip = data.get('ip_address')
    port = data.get('port', 4370)
    if not ip:
        raise ValueError("ip_address is required")
    zk = ZK(ip, port=int(port), timeout=5, password=0, force_udp=False, ommit_ping=False)
    return zk.connect(), ip, port

@app.route('/api/check_status', methods=['POST'])
def check_status():
    conn = None
    try:
        conn, ip, port = get_zk_conn(request.json)
        return jsonify({"status": "online", "ip": ip, "port": port})
    except Exception as e:
        return jsonify({"status": "offline", "error": str(e)}), 200
    finally:
        if conn: conn.disconnect()

@app.route('/api/get_users', methods=['POST'])
def get_users():
    conn = None
    try:
        conn, _, _ = get_zk_conn(request.json)
        users = conn.get_users()
        user_list = []
        for u in users:
            user_list.append({
                "uid": u.uid,
                "name": u.name,
                "privilege": u.privilege,
                "password": u.password,
                "group_id": u.group_id,
                "user_id": u.user_id
            })
        return jsonify({"status": "success", "users": user_list})
    except Exception as e:
        return jsonify({"status": "error", "error": str(e)}), 200
    finally:
        if conn: conn.disconnect()

@app.route('/api/get_attendance', methods=['POST'])
def get_attendance():
    conn = None
    try:
        conn, _, _ = get_zk_conn(request.json)
        attendance = conn.get_attendance()
        att_list = []
        if attendance:
            for a in attendance:
                att_list.append({
                    "uid": a.uid,
                    "user_id": a.user_id,
                    "timestamp": a.timestamp.strftime('%Y-%m-%d %H:%M:%S'),
                    "status": a.status,
                    "punch": a.punch
                })
        return jsonify({"status": "success", "attendance": att_list})
    except Exception as e:
        return jsonify({"status": "error", "error": str(e)}), 200
    finally:
        if conn: conn.disconnect()

@app.route('/api/clear_admins', methods=['POST'])
def clear_admins():
    conn = None
    try:
        conn, _, _ = get_zk_conn(request.json)
        users = conn.get_users()
        for u in users:
            if u.privilege != 0: 
                conn.set_user(uid=u.uid, name=u.name, privilege=0, password=u.password, group_id=u.group_id, user_id=u.user_id)
        conn.enable_device()
        return jsonify({"status": "success"})
    except Exception as e:
        return jsonify({"status": "error", "error": str(e)}), 200
    finally:
        if conn: conn.disconnect()

@app.route('/api/set_user', methods=['POST'])
def set_user():
    conn = None
    try:
        data = request.json
        conn, _, _ = get_zk_conn(data)
        
        raw_id = str(data.get('device_user_id')) 
        name = str(data.get('name', ''))
        privilege = int(data.get('privilege', 0))
        
        users = conn.get_users()
        target_uid = None
        max_uid = 0
        already_exists_and_identical = False

        for u in users:
            if str(u.user_id) == raw_id:
                target_uid = u.uid
                # Cek jika nama dan privilege sudah sama persis, kita tak perlu update lagi
                if u.name == name and u.privilege == privilege:
                    already_exists_and_identical = True
                break
            if u.uid > max_uid:
                max_uid = u.uid
                
        # Jika data sudah persis sama, skip proses penulisan ke mesin untuk hemat resource
        if already_exists_and_identical:
            return jsonify({"status": "success", "message": "Already exists, skipped."})

        if target_uid is None:
            target_uid = max_uid + 1
            if target_uid > 65535:
                used_uids = {u.uid for u in users}
                for i in range(1, 65535):
                    if i not in used_uids:
                        target_uid = i
                        break
              
        conn.set_user(uid=target_uid, name=name, privilege=privilege, password='', group_id='', user_id=raw_id)
        return jsonify({"status": "success"})
    except Exception as e:
        return jsonify({"status": "error", "error": str(e)}), 200
    finally:
        if conn: conn.disconnect()

@app.route('/api/get_templates', methods=['POST'])
def get_templates():
    conn = None
    try:
        conn, _, _ = get_zk_conn(request.json)
        users = conn.get_users()
        uid_to_userid = {u.uid: str(u.user_id) for u in users}
        
        fingers = conn.get_templates()
        finger_list = []
        if fingers:
            for f in fingers:
                device_user_id = uid_to_userid.get(f.uid)
                if not device_user_id: continue # Abaikan jika user tidak dikenali
                
                t_bytes = f.template.encode('latin1') if isinstance(f.template, str) else f.template
                t_str = base64.b64encode(t_bytes).decode('utf-8')
                finger_list.append({
                    "uid": device_user_id, # Kirim NIS, bukan UID internal
                    "fid": f.fid,
                    "valid": f.valid,
                    "size": getattr(f, 'size', len(t_bytes)),
                    "template": t_str
                })
        return jsonify({"status": "success", "templates": finger_list})
    except Exception as e:
        return jsonify({"status": "error", "error": str(e)}), 200
    finally:
        if conn: conn.disconnect()

@app.route('/api/set_template', methods=['POST'])
def set_template():
    conn = None
    try:
        data = request.json
        conn, _, _ = get_zk_conn(data)

        user_id = str(data.get('device_user_id')) 
        name = str(data.get('name', 'User'))
        privilege = int(data.get('privilege', 0))
        
        users = conn.get_users()
        target_uid = None
        max_uid = 0
        for u in users:
            if u.user_id == user_id:
                target_uid = u.uid
                break
            if u.uid > max_uid:
                max_uid = u.uid
                
        if target_uid is None:
            target_uid = max_uid + 1
            if target_uid > 65535:
                used_uids = {u.uid for u in users}
                for i in range(1, 65535):
                    if i not in used_uids:
                        target_uid = i
                        break

        fid = int(data.get('fid'))
        valid = int(data.get('valid', 1))
        t_str = data.get('template')
        
        t_bytes = base64.b64decode(t_str)
        
        user = User(uid=target_uid, name=name, privilege=privilege, user_id=user_id)
        finger = Finger(uid=target_uid, fid=fid, valid=valid, template=t_bytes)
        
        conn.save_user_template(user, [finger])
        return jsonify({"status": "success"})
    except Exception as e:
        return jsonify({"status": "error", "error": str(e)}), 200
    finally:
        if conn: conn.disconnect()

@app.route('/api/delete_user', methods=['POST'])
def delete_user():
    conn = None
    try:
        data = request.json
        conn, _, _ = get_zk_conn(data)
        raw_id = str(data.get('device_user_id'))
        
        users = conn.get_users()
        target_uid = None
        for u in users:
            if u.user_id == raw_id:
                target_uid = u.uid
                break
                
        if target_uid is not None:
            conn.delete_user(uid=target_uid)
            
        return jsonify({"status": "success"})
    except Exception as e:
        return jsonify({"status": "error", "error": str(e)}), 200
    finally:
        if conn: conn.disconnect()

from datetime import datetime

@app.route('/api/sync_time', methods=['POST'])
def sync_time():
    conn = None
    try:
        data = request.json
        conn, _, _ = get_zk_conn(data)
        # Set jam mesin mengikuti jam komputer server saat ini
        now = datetime.now()
        conn.set_time(now)
        return jsonify({"status": "success", "time_synced": now.strftime('%Y-%m-%d %H:%M:%S')})
    except Exception as e:
        return jsonify({"status": "error", "error": str(e)}), 200
    finally:
        if conn: conn.disconnect()

# ==========================================
# THREADING: LIVE CAPTURE BACKGROUND
# ==========================================

def capture_from_device(ip, port):
    zk = ZK(ip, port=port, timeout=10, password=0, force_udp=False, ommit_ping=False)
    conn = None
    try:
        print(f"[LIVE CAPTURE - Mesin {ip}] Mencoba terhubung...")
        conn = zk.connect()
        print(f"[LIVE CAPTURE - Mesin {ip}] Berhasil terhubung. Memulai pemantauan sidik jari...")
        
        for attendance in conn.live_capture():
            if attendance is None:
                continue
                
            print(f"[LIVE CAPTURE - Mesin {ip}] 🔔 Sidik Jari Terdeteksi! ID: {attendance.user_id}")
            
            payload = {
                "ip_address": ip,
                "user_id": attendance.user_id,
                "timestamp": attendance.timestamp.strftime('%Y-%m-%d %H:%M:%S'),
                "status": attendance.punch
            }
            
            try:
                res = requests.post(LARAVEL_API_REALTIME, json=payload, timeout=5)
                if res.status_code == 200:
                    print(f"[LIVE CAPTURE - Mesin {ip}] ✅ Data realtime terkirim ke Laravel!")
                else:
                    print(f"[LIVE CAPTURE - Mesin {ip}] ❌ Gagal masuk Laravel. Status: {res.status_code}")
            except Exception as e:
                print(f"[LIVE CAPTURE - Mesin {ip}] ❌ API Laravel error: {e}")
                
    except Exception as e:
        print(f"[LIVE CAPTURE - Mesin {ip}] ❌ Koneksi terputus: {e}")
    finally:
        if conn:
            conn.disconnect()
        print(f"[LIVE CAPTURE - Mesin {ip}] Berhenti. Mencoba ulang dalam 10 detik...\n")
        time.sleep(10)
        capture_from_device(ip, port)

def start_live_capture_threads():
    time.sleep(3)
    
    print("\n--- Mengambil daftar mesin dari Laravel untuk dipantau secara Realtime ---")
    try:
        res = requests.get(LARAVEL_API_DEVICES, timeout=5)
        devices = res.json()
    except Exception as e:
        print(f"Gagal mengambil daftar mesin: {e}. Pastikan Laravel berjalan (php artisan serve).")
        return

    if not devices:
        print("Tidak ada mesin yang terdaftar di database Laravel.")
        return

    print(f"Ditemukan {len(devices)} mesin. Memulai thread Live Capture...\n")

    for device in devices:
        ip = device.get('ip_address')
        port = int(device.get('port', 4370))
        
        t = threading.Thread(target=capture_from_device, args=(ip, port))
        t.daemon = True
        t.start()

def auto_sync_templates(devices):
    print("\n[AUTO-SYNC] Memulai sistem sinkronisasi sidik jari (Interval 30 detik)...")
    while True:
        try:
            # Refresh devices list in case of changes
            try:
                res = requests.get(LARAVEL_API_DEVICES, timeout=5)
                devices_list = res.json()
            except:
                devices_list = devices
            
            # Loop setiap mesin untuk narik template
            for device in devices_list:
                ip = device.get('ip_address')
                port = int(device.get('port', 4370))
                
                zk = ZK(ip, port=port, timeout=5, password=0, force_udp=False, ommit_ping=False)
                conn = None
                try:
                    conn = zk.connect()
                    
                    users = conn.get_users()
                    uid_to_userid = {u.uid: str(u.user_id) for u in users}
                    
                    fingers = conn.get_templates()
                    finger_list = []
                    
                    if fingers:
                        for f in fingers:
                            device_user_id = uid_to_userid.get(f.uid)
                            if not device_user_id: continue
                            
                            t_bytes = f.template.encode('latin1') if isinstance(f.template, str) else f.template
                            t_str = base64.b64encode(t_bytes).decode('utf-8')
                            finger_list.append({
                                "uid": device_user_id, # NIS
                                "fid": f.fid,
                                "valid": f.valid,
                                "size": getattr(f, 'size', len(t_bytes)),
                                "template": t_str
                            })
                    
                    # Kirim ke Laravel untuk diverifikasi (apakah ada jari baru?)
                    payload = {
                        "ip_address": ip,
                        "templates": finger_list
                    }
                    sync_res = requests.post(LARAVEL_API_SYNC_TEMPLATES, json=payload, timeout=10)
                    
                    if sync_res.status_code == 200:
                        data = sync_res.json()
                        new_templates = data.get('new_templates', [])
                        
                        if len(new_templates) > 0:
                            print(f"[AUTO-SYNC] Menemukan {len(new_templates)} sidik jari baru di Mesin {ip}!")
                            
                            # Jika ada jari baru, sebar ke SEMUA mesin LAINNYA
                            for target_dev in devices_list:
                                t_ip = target_dev.get('ip_address')
                                if t_ip == ip: continue # Jangan dikirim balik ke mesin asalnya
                                
                                t_port = int(target_dev.get('port', 4370))
                                t_zk = ZK(t_ip, port=t_port, timeout=5)
                                t_conn = None
                                try:
                                    t_conn = t_zk.connect()
                                    t_users = t_conn.get_users()
                                    
                                    for nt in new_templates:
                                        # Cari uid internal mesin tujuan
                                        target_uid = None
                                        max_uid = 0
                                        for u in t_users:
                                            if str(u.user_id) == str(nt['device_user_id']):
                                                target_uid = u.uid
                                                break
                                            if u.uid > max_uid:
                                                max_uid = u.uid
                                                
                                        if target_uid is None:
                                            target_uid = max_uid + 1
                                        
                                        t_bytes = base64.b64decode(nt['template'])
                                        
                                        uid_int = int(target_uid)
                                        priv_int = int(nt['privilege'])
                                        fid_int = int(nt['fid'])
                                        valid_int = int(nt['valid'])
                                        userid_str = str(nt['device_user_id'])
                                        name_str = str(nt['name'])

                                        # Pastikan nama siswa juga diset/diperbarui di mesin tujuan
                                        t_conn.set_user(uid=uid_int, name=name_str, privilege=priv_int, password='', group_id='', user_id=userid_str)

                                        user = User(uid=uid_int, name=name_str, privilege=priv_int, user_id=userid_str)
                                        finger = Finger(uid=uid_int, fid=fid_int, valid=valid_int, template=t_bytes)
                                        
                                        t_conn.save_user_template(user, [finger])
                                    
                                    print(f"[AUTO-SYNC] ✅ Berhasil menyebarkan {len(new_templates)} jari ke Mesin {t_ip}")
                                except Exception as e:
                                    print(f"[AUTO-SYNC] ❌ Gagal menyebarkan ke Mesin {t_ip}: {e}")
                                finally:
                                    if t_conn: t_conn.disconnect()
                        else:
                            print(f"[AUTO-SYNC] Pengecekan Mesin {ip} selesai. Tidak ada sidik jari baru.")
                                    
                except Exception as e:
                    pass # Abaikan error koneksi saat auto-sync agar tidak spam console
                finally:
                    if conn: conn.disconnect()
        except Exception as e:
            print(f"[AUTO-SYNC] Error loop utama: {e}")
            
        # Tunggu 30 Detik sebelum muter lagi
        time.sleep(30)

def start_auto_sync_thread():
    time.sleep(5) # Biarkan Flask dan Live Capture jalan dulu
    try:
        res = requests.get(LARAVEL_API_DEVICES, timeout=5)
        devices = res.json()
        if devices:
            t = threading.Thread(target=auto_sync_templates, args=(devices,))
            t.daemon = True
            t.start()
    except:
        print("[AUTO-SYNC] Gagal menghubungi Laravel. Fitur Auto-Sync Sidik Jari dinonaktifkan.")

if __name__ == '__main__':
    # Jalankan thread pemantau sidik jari (Live Capture) di background
    threading.Thread(target=start_live_capture_threads, daemon=True).start()
    
    # Jalankan thread sinkronisasi sidik jari antar-mesin (Auto Sync 30s) di background
    threading.Thread(target=start_auto_sync_thread, daemon=True).start()
    
    # Jalankan server API (Port 5000) di foreground
    print("Memulai Server API Python di Port 5000...")
    app.run(host='0.0.0.0', port=5000, debug=False, use_reloader=False)