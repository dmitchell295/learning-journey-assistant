export default function Gaps({ items = [] }) {
  return (
    <div className="list-card">
      <div className="list-icon icon-gaps">🎯</div>
      <div className="list-content">
        <h3>Skill Gaps</h3>
        {items.length === 0 ? (
          <p>No gaps flagged yet.</p>
        ) : (
          items.map((item, i) => (
            <p key={i}>{item.subjectName} — {item.masteryLabel} ({item.masteryPercent}%)</p>
          ))
        )}
      </div>
      <span className="tag tag-gaps">Needs attention</span>
    </div>
  );
}