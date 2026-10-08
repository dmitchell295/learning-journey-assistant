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

function gapBannerText(count) {
  if (count === 0) return 'No skill gaps flagged';
  return `${count} skill gap${count === 1 ? '' : 's'} flagged`;
}

function App() {
  const [activeTab, setActiveTab] = useState('understanding');
  const [studentId, setStudentId] = useState(null); // null = first student with data
  const { loading, error, data } = useDashboardData(studentId);

  return (
    <div className="page">
      <div className="phone-frame">
        <header className="app-header">
          <div className="app-header-top">
            <div className="brand">
              <span className="brand-badge">LT</span>
              <span className="brand-name">La Trobe University</span>
            </div>
            <div className="avatar">
              {data?.activeStudentId != null ? `S${data.activeStudentId}` : '–'}
            </div>
          </div>
          <h1>Learning Journey</h1>

          {data && data.studentIds.length > 1 && (
            <label className="student-picker">
              Viewing
              <select
                value={data.activeStudentId ?? ''}
                onChange={(e) => setStudentId(Number(e.target.value))}
              >
                {data.studentIds.map((id) => (
                  <option key={id} value={id}>Student {id}</option>
                ))}
              </select>
            </label>
          )}

          {data && (
            <div className="alert-banner">
              <span className="alert-dot"></span>
              {gapBannerText(data.gapsFlagged)}
            </div>
          )}
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
              <ProgressTrends assignmentCount={data.assignmentCount} avgMastery={data.avgMastery} />
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