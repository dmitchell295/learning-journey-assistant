import requests
import html
import sys
from dotenv import load_dotenv
load_dotenv()
sys.path.append("Prototype AI Gap Detection Engine")
from mastery_estimator import estimate_mastery

BASE_URL = "http://127.0.0.1:5000"

rubric_res = requests.get(f"{BASE_URL}/moodle/rubric/26")
rubric_data = rubric_res.json()

instance_res = requests.get(f"{BASE_URL}/moodle/rubric/definition/99/instances")
instance_data = instance_res.json()

grade_res = requests.get(f"{BASE_URL}/moodle/assignment/10/grades")
grade_data = grade_res.json()

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
                line = f"{criterion_name}: {level_text} (score {level['score']})"
                rubric_lines.append(line)
                break
    
    rubric_text = "\n".join(rubric_lines)
    feedback_text = "Assignment grade: " + str(grade["grade"]) + ". No written marker feedback is currently available. Use the selected live rubric levels as the evidence."

    print("\n====================")
    print("Student:", grade["userid"])
    print("Grade:", grade["grade"])
    print("====================")
    try:
        result = estimate_mastery(rubric_text, feedback_text)
        print("\nAI mastery result:")
        print(result)
    except Exception as error:
        print("\nAI error:")
        print(error)
