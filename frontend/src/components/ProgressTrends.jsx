// A trend needs results from at least two assignments. Until then, say so
// plainly instead of showing an "Improving" badge the data can't support.
export default function ProgressTrends({ assignmentCount = 0, avgMastery = null }) {
  let message;
  if (assignmentCount === 0) {
    message = "No results yet.";
  } else if (assignmentCount === 1) {
    message = "Trends appear once there are results from two or more assignments.";
  } else {
    message = `Average mastery across ${assignmentCount} assignments: ${avgMastery}%.`;
  }

  return (
    <div className="list-card">
      <div className="list-icon icon-trends">📈</div>
      <div className="list-content">
        <h3>Progress Trends</h3>
        <p>{message}</p>
      </div>
    </div>
  );
}