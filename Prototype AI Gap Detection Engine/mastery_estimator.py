"""
CBLS-38 prototype: rubric + feedback -> mastery estimate per learning outcome.

Built against a TEMPORARY output contract. Once Su finalises the real
contract in CBLS-40, only CONTRACT_FIELDS and parse_response() below
should need to change.
"""

import json
import os
from anthropic import Anthropic

# ---------------------------------------------------------------------------
# TEMPORARY CONTRACT - replace this block once CBLS-40 is finalised.
# Keeping the schema description in one place means the swap later is a
# small, contained edit instead of a hunt through the whole script.
# ---------------------------------------------------------------------------
CONTRACT_FIELDS = {
    "learning_outcome": str,   # name/id of the learning outcome
    "mastery_score": (int, float),  # 0-100 mastery estimate
    "evidence": str,           # short justification drawn from the feedback
}

CONTRACT_DESCRIPTION = """
Return ONLY a JSON array, no other text. Each item must have exactly these
fields:
- "learning_outcome": string, name of the learning outcome
- "mastery_score": number from 0 to 100
- "evidence": string, a short quote or paraphrase from the feedback that
  supports the score
"""
# ---------------------------------------------------------------------------


def build_prompt(rubric: str, feedback: str) -> str:
    """Construct the prompt sent to the model."""
    return f"""You are analysing marked student feedback against a rubric to
estimate mastery per learning outcome.

Rubric:
{rubric}

Feedback:
{feedback}

{CONTRACT_DESCRIPTION}
"""


def call_model(prompt: str) -> str:
    """Send the prompt to the model and return the raw text response."""
    client = Anthropic()  # reads ANTHROPIC_API_KEY from env
    response = client.messages.create(
        model="claude-sonnet-4-5",  # check docs.claude.com for the current model string
        max_tokens=1024,
        messages=[{"role": "user", "content": prompt}],
    )
    return "".join(
        block.text for block in response.content if block.type == "text"
    )


def parse_response(raw_text: str) -> list[dict]:
    """
    Parse and validate the model's raw output against CONTRACT_FIELDS.
    Raises ValueError if the output doesn't match the expected shape.
    This is the function to rewrite when the real contract lands.
    """
    # Strip markdown code fences if the model wrapped its JSON in them
    # (e.g. triple-backtick-json ... triple-backtick), even when told not to.
    cleaned = raw_text.strip()
    if cleaned.startswith("```"):
        cleaned = cleaned.split("\n", 1)[1] if "\n" in cleaned else cleaned
        if cleaned.rstrip().endswith("```"):
            cleaned = cleaned.rstrip()[:-3]
    cleaned = cleaned.strip()

    try:
        data = json.loads(cleaned)
    except json.JSONDecodeError as e:
        raise ValueError(f"Model output was not valid JSON: {e}\nRaw: {raw_text}")

    if not isinstance(data, list):
        raise ValueError(f"Expected a JSON array, got: {type(data)}")

    for i, item in enumerate(data):
        for field, expected_type in CONTRACT_FIELDS.items():
            if field not in item:
                raise ValueError(f"Item {i} missing field '{field}': {item}")
            if not isinstance(item[field], expected_type):
                raise ValueError(
                    f"Item {i} field '{field}' has wrong type: {item[field]!r}"
                )

    return data


def estimate_mastery(rubric: str, feedback: str) -> list[dict]:
    """Main entry point: rubric + feedback -> list of mastery estimates."""
    prompt = build_prompt(rubric, feedback)
    raw_output = call_model(prompt)
    return parse_response(raw_output)


if __name__ == "__main__":
    # Quick manual test - replace with real rubric/feedback, or feed in
    # your CBLS-39 synthetic samples once you have them.
    example_rubric = """
    Learning Outcome 1: Explain core cybersecurity principles.
    Learning Outcome 2: Apply threat modelling techniques to a system.
    """
    example_feedback = """
    The student clearly explained CIA triad concepts with good examples,
    but the threat model submitted only covered one attack surface and
    missed mitigation steps entirely.
    """

    results = estimate_mastery(example_rubric, example_feedback)
    print(json.dumps(results, indent=2))
