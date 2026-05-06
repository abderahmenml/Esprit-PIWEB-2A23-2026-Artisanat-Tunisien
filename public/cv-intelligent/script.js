const STORAGE_KEY = 'herfa_cv_intelligent';

const JOB_DATA = {
  web_dev: {
    title: 'Junior Web Developer',
    skills: ['HTML', 'CSS', 'JavaScript', 'Git', 'Responsive Design'],
    interests: ['Open source', 'UI patterns', 'Web performance'],
    objective: 'Motivated to build clean and accessible web interfaces with strong attention to detail.'
  },
  ux_ui: {
    title: 'Junior UX/UI Designer',
    skills: ['Wireframing', 'Figma', 'User Research', 'Prototyping', 'Design Systems'],
    interests: ['Human centered design', 'Mobile UX', 'Design thinking'],
    objective: 'Focused on creating user-first experiences that balance clarity, usability, and brand identity.'
  },
  data_analyst: {
    title: 'Junior Data Analyst',
    skills: ['Excel', 'SQL', 'Data Visualization', 'Statistics', 'Dashboards'],
    interests: ['Business insights', 'Data storytelling', 'Automation'],
    objective: 'Driven to turn data into actionable insights and clear decision support.'
  },
  digital_marketing: {
    title: 'Digital Marketing Associate',
    skills: ['SEO', 'Content Strategy', 'Social Media', 'Analytics', 'Email Marketing'],
    interests: ['Brand growth', 'Campaigns', 'Audience research'],
    objective: 'Eager to build measurable marketing campaigns with audience-first messaging.'
  },
  artisan: {
    title: 'Crafts Artisan',
    skills: ['Craftsmanship', 'Quality Control', 'Material Knowledge', 'Client Service', 'Creativity'],
    interests: ['Local heritage', 'Custom orders', 'Product design'],
    objective: 'Passionate about creating authentic handmade products with consistent quality.'
  }
};

const state = {
  step: 1,
  job: '',
  skills: ['', '', ''],
  experience: '',
  education: ''
};

const steps = document.querySelectorAll('.step');
const stepIndicator = document.getElementById('step-indicator');
const prevBtn = document.getElementById('prev-btn');
const nextBtn = document.getElementById('next-btn');

const jobSelect = document.getElementById('job-select');
const skillInputs = [
  document.getElementById('skill-1'),
  document.getElementById('skill-2'),
  document.getElementById('skill-3')
];
const experienceInput = document.getElementById('experience');
const educationSelect = document.getElementById('education');

const cvTitle = document.getElementById('cv-title');
const cvObjective = document.getElementById('cv-objective');
const cvSkills = document.getElementById('cv-skills');
const cvInterests = document.getElementById('cv-interests');
const scoreBar = document.getElementById('score-bar');
const scoreValue = document.getElementById('score-value');
const missingKeywords = document.getElementById('missing-keywords');
const improvementSheet = document.getElementById('improvement-sheet');

const resetBtn = document.getElementById('reset-btn');
const downloadBtn = document.getElementById('download-btn');

function saveState() {
  localStorage.setItem(STORAGE_KEY, JSON.stringify(state));
}

function loadState() {
  const raw = localStorage.getItem(STORAGE_KEY);
  if (!raw) return;
  try {
    const data = JSON.parse(raw);
    Object.assign(state, data);
  } catch (err) {
    localStorage.removeItem(STORAGE_KEY);
  }
}

function updateStep() {
  steps.forEach((step) => {
    step.classList.toggle('is-active', Number(step.dataset.step) === state.step);
  });
  stepIndicator.textContent = `Step ${state.step} of 4`;
  prevBtn.disabled = state.step === 1;
  nextBtn.textContent = state.step === 4 ? 'Finish' : 'Next';
}

function getUserSkills() {
  return state.skills
    .map((s) => (s || '').trim())
    .filter((s) => s.length > 0);
}

function computeMatchScore(requiredSkills, userSkills) {
  if (!requiredSkills.length) return 0;
  const required = requiredSkills.map((s) => s.toLowerCase());
  const user = userSkills.map((s) => s.toLowerCase());
  const matched = required.filter((skill) => user.includes(skill)).length;
  return Math.round((matched / required.length) * 100);
}

function computeMissingKeywords(requiredSkills, userSkills) {
  const user = userSkills.map((s) => s.toLowerCase());
  return requiredSkills.filter((skill) => !user.includes(skill.toLowerCase()));
}

function renderTags(container, items) {
  container.innerHTML = '';
  if (!items.length) {
    container.innerHTML = '<span class="tag">No data</span>';
    return;
  }
  items.forEach((item) => {
    const tag = document.createElement('span');
    tag.className = 'tag';
    tag.textContent = item;
    container.appendChild(tag);
  });
}

function generateObjective(jobConfig) {
  const educationLabel = educationSelect.options[educationSelect.selectedIndex]?.text || '';
  const experience = state.experience.trim();
  const extra = [];
  if (educationLabel) {
    extra.push(`Education: ${educationLabel}`);
  }
  if (experience) {
    extra.push(`Experience: ${experience}`);
  }
  const extraText = extra.length ? ` ${extra.join(' | ')}` : '';
  return `${jobConfig.objective}${extraText}`;
}

function generateImprovementSheet(missing, jobConfig) {
  const priorities = [
    'Strengthen 2 key job keywords in your summary.',
    'Add measurable impact to your experience section.',
    'Align skills with the target role requirements.'
  ];

  return `
    <div><strong>Top priorities</strong></div>
    <ul>
      ${priorities.map((p) => `<li>${p}</li>`).join('')}
    </ul>
    <div style="margin-top:10px;"><strong>Suggested skills to learn</strong></div>
    <ul>
      ${missing.map((m) => `<li>${m}</li>`).join('') || '<li>All core skills covered.</li>'}
    </ul>
  `;
}

function updateResults() {
  if (!state.job || !JOB_DATA[state.job]) {
    cvTitle.textContent = '-';
    cvObjective.textContent = '-';
    renderTags(cvSkills, []);
    renderTags(cvInterests, []);
    renderTags(missingKeywords, []);
    scoreBar.style.width = '0%';
    scoreValue.textContent = '0%';
    improvementSheet.innerHTML = '<p>Select a target job to generate suggestions.</p>';
    return;
  }

  const config = JOB_DATA[state.job];
  const userSkills = getUserSkills();

  cvTitle.textContent = config.title;
  cvObjective.textContent = generateObjective(config);
  renderTags(cvSkills, config.skills);
  renderTags(cvInterests, config.interests);

  const score = computeMatchScore(config.skills, userSkills);
  scoreBar.style.width = `${score}%`;
  scoreValue.textContent = `${score}%`;

  const missing = computeMissingKeywords(config.skills, userSkills);
  renderTags(missingKeywords, missing.length ? missing : ['All keywords covered']);
  improvementSheet.innerHTML = generateImprovementSheet(missing, config);
}

function syncInputs() {
  state.job = jobSelect.value;
  state.skills = skillInputs.map((input) => input.value);
  state.experience = experienceInput.value;
  state.education = educationSelect.value;
  saveState();
  updateResults();
}

function bindInputs() {
  jobSelect.addEventListener('change', syncInputs);
  skillInputs.forEach((input) => input.addEventListener('input', syncInputs));
  experienceInput.addEventListener('input', syncInputs);
  educationSelect.addEventListener('change', syncInputs);
}

function resetAll() {
  state.step = 1;
  state.job = '';
  state.skills = ['', '', ''];
  state.experience = '';
  state.education = '';
  localStorage.removeItem(STORAGE_KEY);
  jobSelect.value = '';
  skillInputs.forEach((input) => { input.value = ''; });
  experienceInput.value = '';
  educationSelect.value = '';
  updateStep();
  updateResults();
}

function downloadSheet() {
  const content = improvementSheet.textContent || '';
  const blob = new Blob([content], { type: 'text/plain;charset=utf-8' });
  const link = document.createElement('a');
  link.href = URL.createObjectURL(blob);
  link.download = 'herfa_cv_improvement_sheet.txt';
  link.click();
  URL.revokeObjectURL(link.href);
}

function init() {
  loadState();
  jobSelect.value = state.job;
  skillInputs.forEach((input, idx) => { input.value = state.skills[idx] || ''; });
  experienceInput.value = state.experience;
  educationSelect.value = state.education;
  updateStep();
  updateResults();
  bindInputs();
}

prevBtn.addEventListener('click', () => {
  state.step = Math.max(1, state.step - 1);
  updateStep();
  saveState();
});

nextBtn.addEventListener('click', () => {
  if (state.step === 4) {
    syncInputs();
    return;
  }
  state.step = Math.min(4, state.step + 1);
  updateStep();
  saveState();
});

resetBtn.addEventListener('click', resetAll);

downloadBtn.addEventListener('click', downloadSheet);

init();
