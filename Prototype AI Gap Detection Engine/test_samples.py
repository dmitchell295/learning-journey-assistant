"""
CBLS-39: test mastery_estimator.py against synthetic feedback samples.
"""

import json
from mastery_estimator import estimate_mastery

SAMPLES = [
    {
        "student_id": 1,
        "subject_code": "CSE1DBS",
        "assignment": "CSE1DBS Assignment 1",
        "competency": "Technical Implementation",
        "learning_outcome": "CSE1DBS-LO1",
        "rubric_criterion": "Query Design & Normalization",
        "rubric_level": "Strong",
        "feedback": "[Technical Implementation] Excellent normalization to 3NF, well-structured queries with clear use of joins and indexes. No issues found.",
    },
    {
        "student_id": 2,
        "subject_code": "CSE1DBS",
        "assignment": "CSE1DBS Assignment 1",
        "competency": "Technical Implementation",
        "learning_outcome": "CSE1DBS-LO1",
        "rubric_criterion": "Query Design & Normalization",
        "rubric_level": "Weak",
        "feedback": "[Technical Implementation] Tables are not normalized, queries contain unnecessary duplication and no indexes were used. Core concepts appear misunderstood.",
    },
    {
        "student_id": 3,
        "subject_code": "CSE1DBS",
        "assignment": "CSE1DBS Assignment 1",
        "competency": "Technical Implementation",
        "learning_outcome": "CSE1DBS-LO1",
        "rubric_criterion": "Query Design & Normalization",
        "rubric_level": "Adequate",
        "feedback": "[Technical Implementation] The solution is mostly correct but needs stronger technical detail.",
    },
]


def run_tests():
    results = []
    for i, sample in enumerate(SAMPLES, start=1):
        print(f"--- Sample {i} (rubric_level: {sample['rubric_level']}) ---")
        result = estimate_mastery(sample)
        print(json.dumps(result, indent=2))
        print()
        results.append(result)

    print("=== Summary ===")
    for i, r in enumerate(results, start=1):
        print(f"Sample {i}: rubric_level={SAMPLES[i-1]['rubric_level']:10s} -> mastery_estimate={r['mastery_estimate']}")

    return results


if __name__ == "__main__":
    run_tests()