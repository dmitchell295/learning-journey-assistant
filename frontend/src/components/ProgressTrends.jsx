export default function ProgressTrends({ assessments = [] }) {
  const count = assessments.length;
  const avg = count
    ? Math.round(assessments.reduce((sum, a) => sum + a.masteryPercent, 0) / count)
    : 0;

  return (
    <div className="list-card">
      <div className="list-icon icon-trends">📈</div>
      <div className="list-content">
        <h3>Progress Trends</h3>
        <p>{count === 0 ? "No assessments yet." : `Average mastery across ${count} assessment${count > 1 ? "s" : ""}: ${avg}%.`}</p>
      </div>
      <span className="tag tag-trends">Improving</span>
    </div>
  );
}