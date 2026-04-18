from langgraph.graph import StateGraph, END
from typing import TypedDict, Annotated
from database import get_events, get_materials, get_notes


class ChatState(TypedDict):
    message: str
    intent: str
    response: str


def detect_intent(state: ChatState):
    msg = state["message"].lower()

    if any(word in msg for word in ["exam", "assignment", "due", "deadline"]):
        return {"intent": "events"}

    if any(word in msg for word in ["material", "slide", "resource", "book"]):
        return {"intent": "materials"}

    if any(word in msg for word in ["note", "lecture note", "shared note"]):
        return {"intent": "notes"}

    return {"intent": "general"}


def events_node(state: ChatState):
    events = get_events()
    if not events:
        return {"response": "I couldn't find any upcoming exams or assignments in the database."}

    text = "Here are the upcoming deadlines I found:\n"
    for title, etype, date in events:
        text += f"• {title} ({etype}) - Due: {date}\n"

    return {"response": text}


def materials_node(state: ChatState):
    materials = get_materials()
    if not materials:
        return {"response": "No course materials have been uploaded yet."}

    text = "Here are the latest course materials:\n"
    for title, mtype, url in materials:
        text += f"• {title} ({mtype})\n"

    return {"response": text}


def notes_node(state: ChatState):
    notes = get_notes()
    if not notes:
        return {"response": "There are no shared notes available at the moment."}

    text = "Here are the most recent shared notes:\n"
    for title, content in notes:
        text += f"• {title}\n"

    return {"response": text}


def general_node(state: ChatState):
    return {
        "response": "I am your Classroom Assistant. I can help you find info about exams, assignments, shared notes, and course materials. Try asking 'When is the next exam?' or 'Show me the latest notes'."
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

workflow.add_conditional_edges(
    "intent",
    route,
    {
        "events": "events",
        "materials": "materials",
        "notes": "notes",
        "general": "general",
    }
)

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
