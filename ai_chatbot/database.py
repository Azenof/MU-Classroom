import sqlite3
import os

# Path to the SQLite database
DB_PATH = os.path.join(os.path.dirname(__file__), '..', 'db', 'classroom.sqlite')

def get_connection():
    try:
        conn = sqlite3.connect(DB_PATH)
        return conn
    except Exception as e:
        print(f"Error connecting to SQLite: {e}")
        return None

def get_events():
    conn = get_connection()
    if not conn: return []
    try:
        cursor = conn.cursor()
        cursor.execute("""
            SELECT title, type, due_date
            FROM events
            WHERE due_date >= date('now')
            ORDER BY due_date ASC
            LIMIT 5
        """)
        rows = cursor.fetchall()
        return rows
    finally:
        conn.close()

def get_materials():
    conn = get_connection()
    if not conn: return []
    try:
        cursor = conn.cursor()
        cursor.execute("""
            SELECT title, type, url
            FROM materials
            ORDER BY created_at DESC
            LIMIT 5
        """)
        rows = cursor.fetchall()
        return rows
    finally:
        conn.close()

def get_notes():
    conn = get_connection()
    if not conn: return []
    try:
        cursor = conn.cursor()
        cursor.execute("""
            SELECT title, content
            FROM notes
            ORDER BY created_at DESC
            LIMIT 5
        """)
        rows = cursor.fetchall()
        return rows
    finally:
        conn.close()
