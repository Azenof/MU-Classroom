from langgraph.graph import StateGraph, END
from typing import TypedDict, Annotated
from database import get_events, get_materials
import datetime

class ChatState(TypedDict):
    message: str
    intent: str
    response: str

def detect_intent(state: ChatState):
    msg = state["message"].lower()
    msg_words = set(msg.replace('?', '').replace('!', '').split())

    # 1. Explicit Event/Calendar Keywords
    event_trigger_words = {"exam", "quiz", "assignment", "due", "deadline", "final", "midterm", "test", "when", "date"}
    if any(word in msg_words for word in event_trigger_words):
        return {"intent": "events"}

    # 2. Explicit Notes Keywords - Just redirect
    if any(word in msg_words for word in ["note", "notes"]):
        return {"intent": "notes"}

    # 3. Explicit Materials Keywords
    if any(word in msg_words for word in ["material", "materials", "slide", "slides", "resource", "book", "pdf", "course"]):
        return {"intent": "materials"}

    # 4. Title Matching Fallback for Events
    all_events = [e[0].lower() for e in get_events()]
    if any(term in msg for term in all_events):
        return {"intent": "events"}

    return {"intent": "general"}

def format_date_friendly(date_str):
    try:
        dt = datetime.datetime.strptime(date_str, '%Y-%m-%d')
        return dt.strftime('%A, %B %d, %Y')
    except:
        return date_str

def events_node(state: ChatState):
    events = get_events()
    if not events:
        return {"response": "I couldn't find any upcoming exams or assignments in the database."}

    user_msg = state["message"].lower()
    user_words = set(user_msg.replace('?', '').replace('!', '').split())
    
    noise_words = {'when', 'is', 'my', 'the', 'on', 'at', 'for', 'a', 'an', 'in', 'of', 'to', 'me', 'show', 'tell', 'about'}
    query_keywords = user_words - noise_words
    
    if not query_keywords:
        text = "Here are your upcoming deadlines:\n"
        for title, etype, date, desc in events[:5]:
            text += f"• {title}: {format_date_friendly(date)}\n"
        return {"response": text}

    scored_events = []
    for title, etype, date, desc in events:
        title_lower = title.lower()
        title_words_list = title_lower.split()
        title_words_set = set(title_words_list)
        
        matches = query_keywords.intersection(title_words_set)
        match_count = len(matches)
        
        if match_count > 0:
            score = match_count * 100
            clean_query = " ".join([w for w in user_msg.split() if w not in noise_words])
            if clean_query and clean_query in title_lower:
                score += 50
            if title_words_list and title_words_list[0] in query_keywords:
                score += 30
            scored_events.append((score, title, etype, date, desc))

    scored_events.sort(key=lambda x: x[0], reverse=True)

    if scored_events:
        highest_score = scored_events[0][0]
        best_matches = [e for e in scored_events if e[0] >= highest_score * 0.9]
        
        if len(best_matches) == 1:
            _, title, etype, date, desc = best_matches[0]
            friendly_date = format_date_friendly(date)
            resp = f"Your {title} ({etype}) is on {friendly_date}."
            if desc:
                resp += f"\n\nDetails: {desc}"
            return {"response": resp}
        else:
            text = "I found these related deadlines:\n"
            best_matches.sort(key=lambda x: x[3])
            for _, title, etype, date, desc in best_matches:
                friendly_date = format_date_friendly(date)
                text += f"• {title}: {friendly_date}"
                if desc:
                    text += f" (Note: {desc})"
                text += "\n"
            return {"response": text}
    
    text = "I couldn't find a specific match. Here are the upcoming deadlines:\n"
    for title, etype, date, desc in events[:5]:
        text += f"• {title}: {format_date_friendly(date)}\n"
    return {"response": text}

def materials_node(state: ChatState):
    materials = get_materials()
    if not materials:
        return {"response": "No course materials have been uploaded yet."}
    user_msg = state["message"].lower()
    filtered = [m for m in materials if m[0].lower() in user_msg or any(word in user_msg for word in m[0].lower().split())]
    target = filtered if filtered else materials
    text = "I found these materials:\n" if filtered else "Here are the latest course materials:\n"
    for title, mtype, url in target:
        text += f"• {title} ({mtype})\n"
    return {"response": text}

def notes_node(state: ChatState):
    return {"response": "You can view and manage all shared notes on the **[Notes Page](notes.php)**."}

def general_node(state: ChatState):
    return {
        "response": "I am your Classroom Assistant. I can help you find info about exams, assignments, and course materials. Try asking 'When is the next exam?' or 'Show me the latest materials'."
    }

def route(state: ChatState):
    return state["intent"]

workflow = StateGraph(ChatState)
workflow.add_node("intent", detect_intent)
workflow.add_node("events", events_node)
workflow.add_node("materials", materials_node)
workflow.add_node("notes", notes_node)
workflow.add_node("general", general_node)
workflow.set_entry_point("intent")
workflow.add_conditional_edges("intent", route, {
    "events": "events",
    "materials": "materials",
    "notes": "notes",
    "general": "general",
})
workflow.add_edge("events", END)
workflow.add_edge("materials", END)
workflow.add_edge("notes", END)
workflow.add_edge("general", END)
graph = workflow.compile()

def run_chatbot(message: str):
    try:
        result = graph.invoke({"message": message})
        return result["response"]
    except Exception as e:
        return f"Sorry, I encountered an error: {str(e)}"
