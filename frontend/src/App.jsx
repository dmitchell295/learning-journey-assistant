import { useState } from 'react';
import UnderstandingLevel from './components/UnderstandingLevel';
import Strengths from './components/Strengths';
import Gaps from './components/Gaps';
import ProgressTrends from './components/ProgressTrends';
import './App.css';
import { useDashboardData } from './hooks/useDashboardData';

const TABS = [
  { id: 'understanding', label: 'Understanding', icon: '📊' },
  { id: 'strengths', label: 'Strengths', icon: '💪' },
  { id: 'gaps', label: 'Gaps', icon: '🎯' },
  { id: 'trends', label: 'Trends', icon: '📈' },
];

function App() {
  const [activeTab, setActiveTab] = useState('understanding');
  const { loading, error, data } = useDashboardData();

  return (
    <div className="page">
      <div className="phone-frame">
        <header className="app-header">
          <div className="app-header-top">
            <div className="brand">
              <span className="brand-badge">LT</span>
              <span className="brand-name">La Trobe University</span>
            </div>
            <div className="avatar">DM</div>
          </div>
          <h1>Learning Journey</h1>
          <div className="alert-banner">
            <span className="alert-dot"></span>
            2 skill gaps flagged this week — tap to review
          </div>
        </header>

        <main className="app-body">
          {loading && <p className="loading-state">Loading dashboard...</p>}
          {error && <p className="error-state">Couldn't load dashboard: {error}</p>}
          {!loading && !error && data && (
            <>
              <UnderstandingLevel percent={data.avgMastery} />

              <div className="stat-row">
                <div className="stat-tile">
                  <span className="stat-value">{data.subjectsOnTrack}/{data.totalSubjects}</span>
                  <span className="stat-label">Subjects on track</span>
                </div>
                <div className="stat-tile">
                  <span className="stat-value">{data.gapsFlagged}</span>
                  <span className="stat-label">Gaps flagged</span>
                </div>
              </div>

              <Strengths items={data.strengths} />
              <Gaps items={data.gaps} />
              <ProgressTrends assessments={data.assessments} />
            </>
          )}
        </main>

        <nav className="bottom-nav">
          {TABS.map((tab) => (
            <button
              key={tab.id}
              className={`nav-item ${activeTab === tab.id ? 'active' : ''}`}
              onClick={() => setActiveTab(tab.id)}
            >
              <span className="nav-icon">{tab.icon}</span>
              <span className="nav-label">{tab.label}</span>
            </button>
          ))}
        </nav>
      </div>
    </div>
  );
}

export default App;