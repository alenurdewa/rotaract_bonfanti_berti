import sqlite3, os
from flask import Flask, jsonify, send_from_directory, abort, request, session, redirect, render_template
from flask_cors import CORS
from werkzeug.utils import secure_filename
from functools import wraps
from datetime import datetime

# --- GESTIONE PERCORSO ASSOLUTO ---
SCRIPT_DIR = os.path.dirname(os.path.abspath(__file__))
DB_PATH = os.path.normpath(os.path.join(SCRIPT_DIR, '../database.db'))
# cartella dove mettere i PDF (modifica se vuoi)
BOLLETTINI_DIR = os.path.normpath(os.path.join(SCRIPT_DIR, '../bollettini'))
CSV_PATH = os.path.join(SCRIPT_DIR, '../csv/storia.csv')
# ------------------------------------

app = Flask(__name__)
CORS(app)

def get_db_connection():
    db_uri = f'file:{DB_PATH}?mode=ro'
    try:
        conn = sqlite3.connect(db_uri, uri=True)
        conn.row_factory = sqlite3.Row
        return conn
    except sqlite3.OperationalError as e:
        print(f"ERRORE: Impossibile connettersi al database in {DB_PATH}.")
        print(f"Dettagli: {e}")
        return None

@app.route('/api/eventi', methods=['GET'])
def get_eventi():
    conn = get_db_connection()
    if conn is None:
        return jsonify({"errore": "Servizio non disponibile, database non raggiungibile."}), 503
    try:
        eventi = conn.execute("SELECT * FROM eventi ORDER BY data ASC, ora ASC").fetchall()
        conn.close()
        eventi_list = [
            {
                "id": e["id"],
                "titolo": e["titolo"],
                "descrizione": e["descrizione"],
                "data": e["data"],
                "ora": e["ora"]
            }
            for e in eventi
        ]
        return jsonify(eventi_list)
    except sqlite3.OperationalError as e:
        print(f"ERRORE: {e}")
        conn.close()
        return jsonify({"errore": "Errore del database: la tabella 'eventi' potrebbe non esistere."}), 500

# ---------------------------
# API Bollettini (PDF)
# ---------------------------

def _ensure_bollettini_dir():
    if not os.path.isdir(BOLLETTINI_DIR):
        try:
            os.makedirs(BOLLETTINI_DIR, exist_ok=True)
        except Exception as e:
            print(f"Impossibile creare la cartella bollettini: {e}")

@app.route('/api/bollettini', methods=['GET'])
def list_bollettini():
    _ensure_bollettini_dir()
    files = []
    try:
        for fname in os.listdir(BOLLETTINI_DIR):
            if not fname.lower().endswith('.pdf'):
                continue

            full = os.path.join(BOLLETTINI_DIR, fname)
            stat = os.stat(full)

            files.append({
                "filename": fname,
                "title": os.path.splitext(fname)[0],
                "size": stat.st_size,
                "modified": datetime.fromtimestamp(stat.st_mtime).isoformat(),
                "preview_url": f"http://localhost:5000/preview/bollettini/{fname}",
                "download_url": f"http://localhost:5000/download/bollettini/{fname}"
            })

        files.sort(key=lambda x: x["modified"], reverse=True)
        return jsonify(files)

    except Exception as e:
        print(e)
        return jsonify({"errore": "Errore lettura bollettini"}), 500

@app.route('/preview/bollettini/<path:filename>', methods=['GET'])
def preview_bollettino(filename):
    _ensure_bollettini_dir()
    safe_name = secure_filename(filename)
    return send_from_directory(
        BOLLETTINI_DIR,
        safe_name,
        as_attachment=False
    )

@app.route('/download/bollettini/<path:filename>', methods=['GET'])
def download_bollettino(filename):
    _ensure_bollettini_dir()
    safe_name = secure_filename(filename)
    return send_from_directory(
        BOLLETTINI_DIR,
        safe_name,
        as_attachment=True
    )


# ---------------------------
# Avvio app
# ---------------------------
if __name__ == "__main__":
    if not os.path.exists(DB_PATH):
        print(f"ATTENZIONE: Il file database '{DB_PATH}' non è stato trovato.")
        print("Assicurati di aver eseguito 'python backend/init_db.py' prima di avviare il server.")
    else:
        print(f"Server API in avvio, database in uso: {DB_PATH}")

    # Assicuriamoci che la cartella bollettini esista
    _ensure_bollettini_dir()
    print(f"Cartella bollettini: {BOLLETTINI_DIR}")
    app.run(debug=True, port=5000)
