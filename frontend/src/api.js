
// api.js - central place for all calls to Backend's Flask API.
// Keeping these together means components just import functions,
// rather than each writing its own fetch logic.

const BASE_URL = "http://127.0.0.1:5000";

export async function getStudents() {
  const res = await fetch(`${BASE_URL}/students`);
  if (!res.ok) throw new Error(`Failed to fetch students: ${res.status}`);
  const data = await res.json();
  return data.students;
}

export async function getSubjects() {
  const res = await fetch(`${BASE_URL}/subjects`);
  if (!res.ok) throw new Error(`Failed to fetch subjects: ${res.status}`);
  const data = await res.json();
  return data.subjects;
}

export async function getAssessments() {
  const res = await fetch(`${BASE_URL}/assessments`);
  if (!res.ok) throw new Error(`Failed to fetch assessments: ${res.status}`);
  const data = await res.json();
  return data.assessments;
}

export async function getAssessment(id) {
  const res = await fetch(`${BASE_URL}/assessment/${id}`);
  if (!res.ok) throw new Error(`Failed to fetch assessment ${id}: ${res.status}`);
  return res.json();
}