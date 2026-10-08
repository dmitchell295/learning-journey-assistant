// Mastery helpers for the dashboard.
// The backend now sends a real AI mastery_score (0-100) plus a mastery_estimate
// category ("Not Yet Achieved" | "Partially Achieved" | "Achieved").
// Use the real score when it exists; fall back to a rough label-based
// percentage only for older rows that have no score.

const LABEL_FALLBACK_PERCENT = {
  "Achieved": 95,
  "Partially Achieved": 60,
  "Not Yet Achieved": 25,
};

// A subject counts as "on track" when its average mastery is at least this.
// Keep in step with PARTIAL_MIN in save_results.py.
export const ON_TRACK_MIN = 50;

export function normalizeMastery(score, label) {
  const percent =
    typeof score === "number" ? score : LABEL_FALLBACK_PERCENT[label] ?? null;
  return { percent, label: label ?? "No data yet" };
}

// Exact matches only: "Not Yet Achieved".includes("Achieved") is true,
// so never use includes() for these.
export const isStrength = (a) => a.mastery_estimate === "Achieved";

export const isGap = (a) =>
  a.mastery_estimate === "Not Yet Achieved" ||
  a.mastery_estimate === "Partially Achieved";

// Average that ignores missing values instead of treating them as 0.
export function average(values) {
  const valid = values.filter((v) => typeof v === "number");
  if (valid.length === 0) return null;
  return Math.round(valid.reduce((sum, v) => sum + v, 0) / valid.length);
}