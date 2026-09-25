export default function Strengths({ items = [] }) {
  return (
    <div className="list-card">
      <div className="list-icon icon-strengths">👍</div>
      <div className="list-content">
        <h3>Strengths</h3>
        {items.length === 0 ? (
          <p>No standout strengths yet.</p>
        ) : (
          items.map((item, i) => (
            <p key={i}>{item.subjectName} — {item.masteryLabel} ({item.masteryPercent}%)</p>
          ))
        )}
      </div>
      <span className="tag tag-strengths">On track</span>
    </div>
  );
}