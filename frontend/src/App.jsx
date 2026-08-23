import { useState } from 'react';
import UnderstandingLevel from './components/UnderstandingLevel';
import Strengths from './components/Strengths';
import Gaps from './components/Gaps';
import ProgressTrends from './components/ProgressTrends';
import './App.css';

const TABS = [
  { id: 'understanding', label: 'Understanding', icon: '📊' },
  { id: 'strengths', label: 'Strengths', icon: '💪' },
  { id: 'gaps', label: 'Gaps', icon: '🎯' },
  { id: 'trends', label: 'Trends', icon: '📈' },
];

function App() {
  const [activeTab, setActiveTab] = useState('understanding');

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
          <UnderstandingLevel />

          <div className="stat-row">
            <div className="stat-tile">
              <span className="stat-value">5/8</span>
              <span className="stat-label">Subjects on track</span>
            </div>
            <div className="stat-tile">
              <span className="stat-value">3</span>
              <span className="stat-label">Gaps flagged</span>
            </div>
          </div>

          <Strengths />
          <Gaps />
          <ProgressTrends />
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
