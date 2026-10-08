export default function UnderstandingLevel({ percent = null }) {
  const hasData = typeof percent === "number";
  const width = hasData ? Math.min(Math.max(percent, 0), 100) : 0;

  return (
    <div className="hero-card">
      <div className="hero-top">
        <span className="hero-eyebrow">CURRENT UNDERSTANDING</span>
      </div>
      <div className="hero-main">
        <span className="hero-number">
          {hasData ? percent : "—"}
          {hasData && <span className="hero-unit">%</span>}
        </span>
        <span className="hero-caption">
          {hasData ? "average mastery" : "no results yet"}
        </span>
      </div>
      <div className="hero-progress-track">
        <div className="hero-progress-fill" style={{ width: `${width}%` }}></div>
      </div>
    </div>
  );
}