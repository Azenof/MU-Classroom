import sys
import pytesseract
import cv2
import json
import re

def normalize_lg(lg_str):
    if not lg_str: return ""
    clean = lg_str.strip().strip('[](){}.').upper()
    # Handle common OCR typos
    clean = clean.replace('AT', 'A+').replace('AT+', 'A+')
    # Ensure it's a valid grade
    if re.match(r'^[A-DF][\+\-]?$', clean):
        return clean
    return ""

def get_grade_point(lg_str, gp_from_ocr=None):
    if gp_from_ocr is not None:
        try:
            return float(gp_from_ocr)
        except ValueError:
            pass
    normalized = normalize_lg(lg_str)
    mapping = {
        'A+': 4.0, 'A': 4.0, 'A-': 3.7, 
        'B+': 3.3, 'B': 3.0, 'B-': 2.7, 
        'C+': 2.3, 'C': 2.0, 'D': 1.0, 'F': 0.0
    }
    return mapping.get(normalized, 0.0)

def process_transcript(image_path):
    img = cv2.imread(image_path)
    if img is None:
        raise ValueError(f"Could not open or find the image: {image_path}")
    
    # Preprocessing
    gray = cv2.cvtColor(img, cv2.COLOR_BGR2GRAY)
    gray = cv2.resize(gray, None, fx=2, fy=2, interpolation=cv2.INTER_CUBIC)
    _, gray = cv2.threshold(gray, 0, 255, cv2.THRESH_BINARY + cv2.THRESH_OTSU)
    
    text = pytesseract.image_to_string(gray, config=r'--oem 3 --psm 6')
    
    results = []
    current_semester = None
    semester_pattern = re.compile(r'(\d:\d\s+\w+\s+\d{4})', re.IGNORECASE)
    
    lines = text.split('\n')
    for line in lines:
        line = line.strip()
        if not line: continue
            
        sem_match = semester_pattern.search(line)
        if sem_match:
            current_semester = sem_match.group(1)
            continue
            
        if current_semester:
            # Code: 3 Letters + Space/Dash + 3 Alphanumeric
            code_match = re.search(r'^([A-Z]{3}[\s-]?\w{3})', line)
            if not code_match: continue
            code = code_match.group(1)
            
            # Find Grade Point at the end
            gp_match = re.search(r'(\d\.\d{2})$', line)
            gp = gp_match.group(1) if gp_match else None
            
            # LG Extraction:
            # We look for the LG specifically before the GP.
            # Transcript format: ... Regular 6th[A] A+ 4.00
            # We split the line and look at the words from right to left.
            parts = line.split()
            lg = ""
            if gp and parts[-1] == gp:
                # The LG should be parts[-2]
                potential_lg = normalize_lg(parts[-2])
                if potential_lg:
                    lg = potential_lg
                elif len(parts) > 2:
                    # Maybe there's a typo or extra space, check parts[-3]
                    potential_lg = normalize_lg(parts[-3])
                    if potential_lg:
                        lg = potential_lg
            
            # Title extraction: Everything between code and status markers
            # Markers usually start with "Regular", "Clear", or Batch info like "6th"
            content = line[len(code):].strip()
            title_clean = re.split(r'\s+(?:Regular|Clear|Lab|\d\w{2}\[|clear\(|6th|6TH)', content, flags=re.IGNORECASE)[0]
            title_clean = title_clean.strip().rstrip('_').rstrip('|').strip()
            
            # Status and Credits
            status = "Completed"
            if 'clear(S)' in line.lower(): status = "Suppli (Cleared)"
            elif 'clear(R)' in line.lower(): status = "Retake (Cleared)"
            elif '(S)' in line.upper() or 'SUPPLI' in line.upper(): status = "Suppli"
            elif '(R)' in line.upper() or 'RETAKE' in line.upper(): status = "Retake"
            
            credits = 1.5 if '1.5' in line or 'lab' in title_clean.lower() or 'lab' in line.lower() else 3.0
            if credits == 1.5 and "lab" not in title_clean.lower():
                title_clean += " Lab"
                
            results.append({
                "course_name": f"{code}: {title_clean}",
                "credits": credits,
                "grade": get_grade_point(lg, gp),
                "letter_grade": lg,
                "semester": current_semester,
                "status": status
            })
                
    return results

if __name__ == "__main__":
    if len(sys.argv) < 2:
        print(json.dumps({"error": "No image path provided"})); sys.exit(1)
    try:
        print(json.dumps(process_transcript(sys.argv[1])))
    except Exception as e:
        print(json.dumps({"error": str(e)}))
