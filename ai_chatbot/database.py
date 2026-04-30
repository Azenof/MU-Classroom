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
            SELECT title, type, due_date, description
            FROM events
            WHERE due_date >= date('now', 'localtime')
            ORDER BY due_date ASC
            LIMIT 20
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
            LIMIT 20
        """)
        rows = cursor.fetchall()
        return rows
    finally:
        conn.close()

def get_past_questions():
    conn = get_connection()
    if not conn: return []
    try:
        cursor = conn.cursor()
        cursor.execute("""
            SELECT course_name, exam_type, batch, image_path
            FROM past_questions
            ORDER BY created_at DESC
            LIMIT 10
        """)
        rows = cursor.fetchall()
        return rows
    finally:
        conn.close()

def get_cgpa_data():
    conn = get_connection()
    if not conn: return []
    try:
        cursor = conn.cursor()
        # Fetch all results to calculate stats or provide detailed info
        cursor.execute("""
            SELECT course_name, credits, grade, letter_grade, semester, status
            FROM course_results
            ORDER BY semester ASC
        """)
        rows = cursor.fetchall()
        return rows
    finally:
        conn.close()
