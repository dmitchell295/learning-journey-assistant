// mastery_estimate now always arrives as one of three category strings
// (fixed as part of CBLS-46's review). This maps that string to a
// display percent + label for the progress bar and cards.

export function normalizeMastery(value) {
  const map = {
    "Achieved": 95,
    "Partially Achieved": 60,
    "Not Yet Achieved": 25,
  };
  return { percent: map[value] ?? 0, label: value ?? "No data" };
}