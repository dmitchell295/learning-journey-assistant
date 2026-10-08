import { useEffect, useState } from "react";
import { getAssessments, getSubjects } from "../api";
import {
  normalizeMastery,
  isStrength,
  isGap,
  average,
  ON_TRACK_MIN,
} from "../utils/mastery";

// studentId: which student to show. If null, the first student with data is used.
export function useDashboardData(studentId = null) {
  const [state, setState] = useState({ loading: true, error: null, data: null });

  useEffect(() => {
    let cancelled = false;

    async function load() {
      try {
        const [assessments, subjects] = await Promise.all([
          getAssessments(),
          getSubjects(),
        ]);

        if (cancelled) return;

        const subjectById = Object.fromEntries(subjects.map((s) => [s.id, s]));

        // Students that have results, so the UI can offer a picker
        const studentIds = [...new Set(assessments.map((a) => a.student_id))].sort(
          (x, y) => x - y
        );
        const activeStudentId = studentId ?? studentIds[0] ?? null;

        // One row per rubric criterion for the active student
        const enriched = assessments
          .filter((a) => a.student_id === activeStudentId)
          .map((a) => {
            const { percent, label } = normalizeMastery(
              a.mastery_score,
              a.mastery_estimate
            );
            const subject = subjectById[a.subject_id];
            return {
              ...a,
              subjectName: subject ? subject.name : `Subject ${a.subject_id}`,
              criterion: a.identified_gap || a.assignment_name || "Unnamed criterion",
              masteryPercent: percent,
              masteryLabel: label,
            };
          });

        const avgMastery = average(enriched.map((a) => a.masteryPercent));

        // Subjects on track: the subject's average mastery meets ON_TRACK_MIN
        const scoresBySubject = {};
        for (const a of enriched) {
          (scoresBySubject[a.subject_id] ??= []).push(a.masteryPercent);
        }
        const subjectAverages = Object.values(scoresBySubject).map(average);
        const subjectsOnTrack = subjectAverages.filter(
          (avg) => avg !== null && avg >= ON_TRACK_MIN
        ).length;

        // Strengths: highest first. Gaps: weakest first.
        const score = (a) => a.masteryPercent ?? 0;
        const strengths = enriched.filter(isStrength).sort((x, y) => score(y) - score(x));
        const gaps = enriched.filter(isGap).sort((x, y) => score(x) - score(y));

        // A trend needs results from at least two assignments
        const assignmentCount = new Set(enriched.map((a) => a.assignment_name)).size;

        setState({
          loading: false,
          error: null,
          data: {
            assessments: enriched,
            studentIds,
            activeStudentId,
            avgMastery,          // null when there is no data
            subjectsOnTrack,
            totalSubjects: subjectAverages.length,
            gapsFlagged: gaps.length,
            strengths,           // full lists; cards decide how many to show
            gaps,
            assignmentCount,
          },
        });
      } catch (err) {
        if (!cancelled) {
          setState({ loading: false, error: err.message, data: null });
        }
      }
    }

    load();
    return () => {
      cancelled = true;
    };
  }, [studentId]);

  return state;
}