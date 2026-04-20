# MU-Classroom Management System

MU-Classroom is a resource management platform designed for students to track their academic progress and access course materials. It includes a centralized dashboard, an AI assistant, and a collaborative archive for past exam questions.

## Core Features

- **Academic Dashboard**: A central view of course statistics and imminent deadline notifications.
- **AI Assistant**: A sidebar chatbot integrated with LangGraph to answer queries regarding materials and schedules.
- **Past Question Archive**: A community-driven archive where students can upload and view past exam papers and class tests, categorized by course and batch.
- **Academic Calendar**: A tracking system for exams, assignments, projects, and presentations with specific color-coding.
- **Resource Management**: A hub for shared lecture notes, slides, and external reference links.

## System Requirements

- **PHP**: Version 7.4 or higher.
- **Python**: Version 3.9 or higher.
- **Database**: SQLite3 (included in the project).

## Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/Azenof/MU-Classroom.git
   cd MU-Classroom
   ```

2. **Install AI dependencies**
   ```bash
   pip install -r ai_chatbot/requirements.txt
   ```

3. **Directory Permissions**
   Ensure the following directories are writable by your system:
   - `uploads/questions/`
   - `db/`

## Running the Application

The system requires two separate servers to be running simultaneously.

### 1. Web Application (PHP)
Start the PHP built-in server from the root directory:
```bash
php -S localhost:8080
```
The application will be accessible at http://localhost:8080.

### 2. AI Chatbot API (Python)
Navigate to the `ai_chatbot` directory and start the FastAPI server:
```bash
python ai_chatbot/app.py
```
The AI service will run on http://127.0.0.1:8000. This must be active for the chat widget to function.

## Project Structure

- `ai_chatbot/`: Contains the logic for the AI assistant and API.
- `assets/`: Stores global CSS and image assets.
- `db/`: Contains the SQLite database file.
- `includes/`: Reusable PHP logic for database connections and layout components.
- `uploads/`: Stores user-uploaded question images and course files.
