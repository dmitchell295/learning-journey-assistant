export default function UnderstandingLevel({ percent = 0 }) {
  return (
    <div className="hero-card">
      <div className="hero-top">
        <span className="hero-eyebrow">CURRENT UNDERSTANDING</span>
      </div>
      <div className="hero-main">
        <span className="hero-number">{percent}<span className="hero-unit">%</span></span>
        <span className="hero-caption">average mastery</span>
      </div>
      <div className="hero-progress-track">
        <div className="hero-progress-fill" style={{ width: `${percent}%` }}></div>
      </div>
    </div>
  );
}