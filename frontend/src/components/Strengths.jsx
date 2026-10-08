const MAX_SHOWN = 4;

function Row({ item }) {
  const pct = item.masteryPercent != null ? ` (${item.masteryPercent}%)` : "";
  return (
    <p className="card-row">
      <span className="card-row-title">{item.criterion}</span>
      <span className="card-row-meta">{item.masteryLabel}{pct}</span>
    </p>
  );
}

export default function Strengths({ items = [] }) {
  const shown = items.slice(0, MAX_SHOWN);
  const hidden = items.length - shown.length;

  return (
    <div className="list-card">
      <div className="list-icon icon-strengths">👍</div>
      <div className="list-content">
        <h3>Strengths</h3>
        {items.length === 0 ? (
          <p>No criteria at Achieved yet.</p>
        ) : (
          <>
            {shown.map((item) => (
              <Row key={item.id} item={item} />
            ))}
            {hidden > 0 && <p>+{hidden} more</p>}
          </>
        )}
      </div>
      {items.length > 0 && <span className="tag tag-strengths">On track</span>}
    </div>
  );
}