import os
import datetime
import json
import google.generativeai as genai
from dotenv import load_dotenv
from database import get_events, get_materials, get_cgpa_data, get_notes

# Load environment variables
load_dotenv()

# Configure Gemini
API_KEY = os.getenv("GOOGLE_API_KEY")
if API_KEY:
    genai.configure(api_key=API_KEY)
    model = genai.GenerativeModel('gemini-flash-latest')
else:
    model = None

def get_context_data():
    """Gathers all relevant classroom data to feed into the AI context."""
    events = get_events()
    materials = get_materials()
    cgpa_rows = get_cgpa_data()
    notes = get_notes()
    
    # Process CGPA stats
    total_points = 0
    total_credits = 0
    semesters = {}
    
    for row in cgpa_rows:
        name, credits, grade, lg, sem, status = row
        if sem not in semesters:
            semesters[sem] = []
        semesters[sem].append({"name": name, "credits": credits, "grade": grade, "lg": lg, "status": status})
        
        if grade > 0:
            total_points += (grade * credits)
            total_credits += credits
            
    cgpa = round(total_points / total_credits, 2) if total_credits > 0 else 0.0
    
    context = {
        "current_date": datetime.datetime.now().strftime("%A, %B %d, %Y"),
        "cgpa_summary": {
            "current_cgpa": cgpa,
            "total_credits_completed": total_credits,
            "remaining_credits_target": 160 - total_credits,
            "semesters": semesters
        },
        "upcoming_deadlines": [
            {"title": e[0], "type": e[1], "date": e[2], "description": e[3]} for e in events
        ],
        "recent_materials": [
            {"title": m[0], "type": m[1]} for m in materials
        ],
        "shared_notes_count": len(notes)
    }
    return context

SYSTEM_PROMPT = """
You are "Classroom Assistant" for MU-Classroom.
Be extremely concise. Only answer what is explicitly asked. No preambles, no fluff, no proactive advice unless requested.

CONTEXT DATA:
{context_json}

RULES:
- Answer the user's question directly using the provided context.
- If asked for a value (CGPA, credits), just give the value or a very short sentence.
- Use Markdown for formatting.
"""

# Simple in-memory session history (reset on server restart)
session_history = []

def run_chatbot(message: str):
    if not model:
        return "⚠️ AI Upgrade Error: `GOOGLE_API_KEY` not found in `.env`. Please add your Gemini API key to enable advanced intelligence."

    context = get_context_data()
    context_json = json.dumps(context, indent=2)
    
    # Build the prompt with history
    full_prompt = SYSTEM_PROMPT.format(context_json=context_json)
    
    # Update history
    session_history.append(f"User: {message}")
    if len(session_history) > 10: session_history.pop(0)
    
    history_str = "\n".join(session_history)
    
    try:
        # We use a simple generate_content for now; LangGraph can be integrated if complex state is needed,
        # but for a "Pro" upgrade, a direct LLM with full context is often more flexible.
        response = model.generate_content(f"{full_prompt}\n\nRecent History:\n{history_str}\n\nAssistant:")
        
        ai_reply = response.text.strip()
        session_history.append(f"Assistant: {ai_reply}")
        return ai_reply
    except Exception as e:
        return f"I'm having trouble thinking right now. Error: {str(e)}"
