import { useEffect, useState } from "react";
import { getAssessments, getSubjects } from "../api";
import { normalizeMastery } from "../utils/mastery";

export function useDashboardData() {
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

        const enriched = assessments.map((a) => {
          const { percent, label } = normalizeMastery(a.mastery_estimate);
          const subject = subjectById[a.subject_id];
          return {
            ...a,
            subjectName: subject ? subject.name : `Subject ${a.subject_id}`,
            masteryPercent: percent,
            masteryLabel: label,
          };
        });

        const avgMastery = enriched.length
          ? Math.round(enriched.reduce((sum, a) => sum + a.masteryPercent, 0) / enriched.length)
          : 0;

        const subjectsOnTrack = enriched.filter((a) => a.masteryPercent >= 50).length;
        const gapsFlagged = enriched.filter((a) => a.masteryPercent < 50).length;

        const sorted = [...enriched].sort((a, b) => b.masteryPercent - a.masteryPercent);

        setState({
          loading: false,
          error: null,
          data: {
            assessments: enriched,
            avgMastery,
            subjectsOnTrack,
            totalSubjects: subjects.length,
            gapsFlagged,
            strengths: sorted.slice(0, 3),
            gaps: sorted.slice(-3).reverse(),
          },
        });
      } catch (err) {
        if (!cancelled) {
          setState({ loading: false, error: err.message, data: null });
        }
      }
    }

    load();
    return () => { cancelled = true; };
  }, []);

  return state;
}