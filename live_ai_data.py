import requests
import html
import sys
from dotenv import load_dotenv
load_dotenv()
sys.path.append("Prototype AI Gap Detection Engine")
from mastery_estimator import estimate_mastery
from save_results import save_student_results

BASE_URL = "http://127.0.0.1:5000"

def get_live_data(url):
    try:
        res = requests.get(url, timeout=10)
        res.raise_for_status()
        data = res.json()
        return data
    except requests.RequestException as error:
        print("API error:", error)
        return None

rubric_data = get_live_data(f"{BASE_URL}/moodle/rubric/26")
instance_data = get_live_data(f"{BASE_URL}/moodle/rubric/definition/99/instances")
grade_data = get_live_data(f"{BASE_URL}/moodle/assignment/10/grades")

if rubric_data == None or instance_data == None or grade_data == None:
    print("Live data is not available. Please try again later.")
    sys.exit()


areas = rubric_data.get("rubric", {}).get("areas", [])
if len(areas) == 0 or not areas[0].get("definitions"):
    print("Rubric data is missing.")
    sys.exit()

definition = rubric_data["rubric"]["areas"][0]["definitions"][0]
criteria = definition["rubric"]["rubric_criteria"]

criterion_map = {}
for c in criteria:
    criterion_map[c["id"]] = c

grades = grade_data["grades"]["assignments"][0]["grades"]
grade_map = {}
for g in grades:
    grade_map[g["id"]] = g

instances = instance_data["instances"]["instances"]



for instance in instances:
    if instance["status"] != 1:
        continue
    grade = grade_map.get(instance["itemid"])
    if grade == None:
        continue
    
    rubric_lines = []
    for result in instance["rubric"]["criteria"]:
        criterion = criterion_map.get(result["criterionid"])
        if criterion == None:
            continue
        for level in criterion["levels"]:
            if level["id"] == result["levelid"]:
                criterion_name = html.unescape(criterion["description"])
                level_text = html.unescape(level["definition"])
                max_score = max(l["score"] for l in criterion["levels"])
                line = f"{criterion_name}: {level_text} (score {level['score']}/{max_score})"
                rubric_lines.append(line)
                break
    
    rubric_text = "\n".join(rubric_lines)

    feedback_text = (
        "No written marker feedback is currently available. "
        "Calculate each criterion mastery only from its selected rubric level and score. "
        "Do not use the overall assignment grade as the criterion mastery score."
    )

    print("\n====================")
    print("Student:", grade["userid"])
    print("Grade:", grade["grade"])
    print("====================")

    try:
        result = estimate_mastery(rubric_text, feedback_text)
        print("\nAI mastery result:")
        print(result)
        save_student_results(grade["userid"], grade["grade"], result)
    except Exception as error:
        print("\nAI error:")
        print(error)
