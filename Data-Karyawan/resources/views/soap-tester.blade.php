<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>SOAP Tester - Data Karyawan Service</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, sans-serif; background: #1a1a2e; color: #eee; padding: 20px; }
        h1 { color: #00d4ff; margin-bottom: 5px; }
        .subtitle { color: #888; margin-bottom: 30px; font-size: 14px; }
        .card { background: #16213e; border-radius: 8px; padding: 20px; margin-bottom: 20px; }
        .card h2 { color: #00d4ff; margin-bottom: 15px; font-size: 16px; }
        label { display: block; margin-bottom: 5px; color: #aaa; font-size: 13px; }
        input, textarea { width: 100%; padding: 10px; background: #0f3460; border: 1px solid #00d4ff33; border-radius: 5px; color: #eee; font-size: 13px; margin-bottom: 15px; }
        textarea { height: 120px; resize: vertical; font-family: monospace; }
        button { background: #00d4ff; color: #000; border: none; padding: 10px 25px; border-radius: 5px; cursor: pointer; font-weight: bold; }
        button:hover { background: #00b8d9; }
        button:disabled { background: #444; color: #888; cursor: not-allowed; }
        .response-box { background: #0a0a1a; border-radius: 5px; padding: 15px; font-family: monospace; font-size: 12px; white-space: pre-wrap; word-break: break-all; max-height: 400px; overflow-y: auto; border: 1px solid #00d4ff33; }
        .badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 12px; font-weight: bold; margin-bottom: 10px; }
        .badge-post { background: #49cc90; color: #000; }
        .status-success { color: #49cc90; }
        .status-error { color: #f93e3e; }
        .step-num { background: #00d4ff; color: #000; width: 24px; height: 24px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-weight: bold; font-size: 12px; }
    </style>
</head>
<body>

<h1>🔧 IAE Tester</h1>
<p class="subtitle">Data Karyawan Service — IAE Tugas 3 | TEAM-10 | NIM: 102022400090</p>

<!-- Step 1: Get Token -->
<div class="card">
    <h2><span class="step-num">1</span> &nbsp; Get M2M Token</h2>
    <p style="color:#aaa; font-size:13px; margin-bottom:15px;">Ambil Bearer Token dari SSO dosen menggunakan API Key.</p>
    <label>API Key</label>
    <input type="text" id="apiKey" value="KEY-MHS-153" />
    <button onclick="getToken()" id="btnToken">Get Token</button>
    <br><br>
    <label>Token Result:</label>
    <div class="response-box" id="tokenResult">— belum dijalankan —</div>
</div>

<!-- Step 2: Send SOAP -->
<div class="card">
    <h2><span class="step-num">2</span> &nbsp; <span class="badge badge-post">POST</span> /soap/v1/audit</h2>
    <p style="color:#aaa; font-size:13px; margin-bottom:15px;">Kirim audit transaksi ke server dosen via SOAP XML.</p>
    <label>Bearer Token (dari Step 1)</label>
    <input type="text" id="bearerToken" placeholder="Otomatis terisi setelah Step 1..." />
    <label>Activity Name</label>
    <input type="text" id="activityName" value="EmployeeDataAccess" />
    <label>Transaction Data (JSON/teks bebas)</label>
    <textarea id="transactionData">{"action":"GET","resource":"employees","nim":"102022400090","timestamp":"{{ now()->toISOString() }}"}</textarea>
    <button onclick="sendSoap()" id="btnSoap">Send SOAP Audit</button>
    <br><br>
    <label>Response:</label>
    <div class="response-box" id="soapResult">— belum dijalankan —</div>
</div>

<!-- Step 3: RabbitMQ -->
<div class="card">
    <h2><span class="step-num">3</span> &nbsp; <span class="badge badge-post">POST</span> /api/v1/messages/publish</h2>
    <p style="color:#aaa; font-size:13px; margin-bottom:15px;">Publish event notification ke RabbitMQ server dosen.</p>
    <label>Bearer Token (dari Step 1)</label>
    <input type="text" id="mqToken" placeholder="Otomatis terisi setelah Step 1..." />
    <label>Event Type</label>
    <input type="text" id="eventType" value="EmployeeDataAccess" />
    <label>Payload (JSON)</label>
    <textarea id="mqPayload">{"service":"Data-Karyawan-Service","team":"TEAM-10","nim":"102022400090","action":"GET","resource":"employees"}</textarea>
    <button onclick="publishMq()" id="btnMq">Publish ke RabbitMQ</button>
    <br><br>
    <label>Response:</label>
    <div class="response-box" id="mqResult">— belum dijalankan —</div>
</div>

<script>
async function getToken() {
    const btn = document.getElementById('btnToken');
    btn.disabled = true;
    btn.textContent = 'Loading...';
    document.getElementById('tokenResult').textContent = 'Menghubungi SSO...';

    try {
        const res = await fetch('/api/v1/auth/login-m2m', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' }
        });
        const data = await res.json();

        if (data.status === 'success') {
            const token = data.data.token;
            document.getElementById('bearerToken').value = token;
            document.getElementById('mqToken').value = token;
            document.getElementById('tokenResult').innerHTML =
                '<span class="status-success">✅ Token berhasil didapat! (auto-filled ke Step 2 & 3)</span>\n\n' +
                JSON.stringify(data, null, 2);
        } else {
            document.getElementById('tokenResult').innerHTML =
                '<span class="status-error">❌ Gagal</span>\n\n' + JSON.stringify(data, null, 2);
        }
    } catch (e) {
        document.getElementById('tokenResult').innerHTML =
            '<span class="status-error">❌ Error: ' + e.message + '</span>';
    }

    btn.disabled = false;
    btn.textContent = 'Get Token';
}

async function sendSoap() {
    const btn = document.getElementById('btnSoap');
    btn.disabled = true;
    btn.textContent = 'Sending...';
    document.getElementById('soapResult').textContent = 'Mengirim SOAP...';

    try {
        const res = await fetch('/api/v1/audit/send', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                activity_name: document.getElementById('activityName').value,
                transaction_data: document.getElementById('transactionData').value,
                bearer_token: document.getElementById('bearerToken').value
            })
        });
        const data = await res.json();

        if (data.status === 'success') {
            document.getElementById('soapResult').innerHTML =
                '<span class="status-success">✅ SOAP berhasil dikirim!</span>\n\n' +
                JSON.stringify(data, null, 2);
        } else {
            document.getElementById('soapResult').innerHTML =
                '<span class="status-error">❌ Gagal</span>\n\n' + JSON.stringify(data, null, 2);
        }
    } catch (e) {
        document.getElementById('soapResult').innerHTML =
            '<span class="status-error">❌ Error: ' + e.message + '</span>';
    }

    btn.disabled = false;
    btn.textContent = 'Send SOAP Audit';
}

async function publishMq() {
    const btn = document.getElementById('btnMq');
    btn.disabled = true;
    btn.textContent = 'Publishing...';
    document.getElementById('mqResult').textContent = 'Mengirim ke RabbitMQ...';

    try {
        const res = await fetch('/api/v1/messages/publish', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                event_type: document.getElementById('eventType').value,
                payload: document.getElementById('mqPayload').value,
                bearer_token: document.getElementById('mqToken').value
            })
        });
        const data = await res.json();

        if (data.status === 'success') {
            document.getElementById('mqResult').innerHTML =
                '<span class="status-success">✅ Event berhasil dipublish!</span>\n\n' +
                JSON.stringify(data, null, 2);
        } else {
            document.getElementById('mqResult').innerHTML =
                '<span class="status-error">❌ Gagal</span>\n\n' + JSON.stringify(data, null, 2);
        }
    } catch (e) {
        document.getElementById('mqResult').innerHTML =
            '<span class="status-error">❌ Error: ' + e.message + '</span>';
    }

    btn.disabled = false;
    btn.textContent = 'Publish ke RabbitMQ';
}
</script>

</body>
</html>