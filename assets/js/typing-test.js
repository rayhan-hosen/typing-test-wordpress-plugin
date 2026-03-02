/**
 * Bangla Typing Test (V2 performance-optimized)
 * - Difficulty based passages (easy/medium/hard)
 * - Incremental marking: only touches DOM when a word is committed
 * - Throttled UI stats updates
 * - Lazy-load certificate libraries only on demand
 * - Certificate unlock after passing all 3 levels (>= min WPM, >= min duration)
 */

(function ($) {
  'use strict';

  const BASE_STORAGE_KEY = 'typing_test_progress_';

  // CDN libs (lazy-loaded)
  const LIBS = {
    html2canvas: 'https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js',
    jspdf: 'https://cdn.jsdelivr.net/npm/jspdf@2.5.1/dist/jspdf.umd.min.js',
  };

  function safeJsonParse(str, fallback) {
    try {
      const v = JSON.parse(str);
      return v && typeof v === 'object' ? v : fallback;
    } catch {
      return fallback;
    }
  }

  function clamp(n, min, max) {
    return Math.max(min, Math.min(max, n));
  }

  function nowISODate() {
    const d = new Date();
    const y = d.getFullYear();
    const m = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${y}-${m}-${day}`;
  }

  function makeCertId() {
    const t = Date.now().toString(36).toUpperCase();
    const r = Math.random().toString(36).slice(2, 8).toUpperCase();
    return `TP-BTT-${t}-${r}`;
  }

  function normalizeWord(w) {
    // Lightweight normalization: trim only.
    return (w || '').trim();
  }

  function shuffleArray(array) {
    const a = array.slice();
    for (let i = a.length - 1; i > 0; i--) {
      const j = Math.floor(Math.random() * (i + 1));
      [a[i], a[j]] = [a[j], a[i]];
    }
    return a;
  }

  function endsWithWhitespace(str) {
    return /\s$/.test(str);
  }

  // Lazy-load external scripts (once)
  const loadedScripts = new Map();
  function loadScript(url) {
    if (loadedScripts.has(url)) return loadedScripts.get(url);
    const p = new Promise((resolve, reject) => {
      const s = document.createElement('script');
      s.src = url;
      s.async = true;
      s.onload = () => resolve(true);
      s.onerror = () => reject(new Error(`Failed to load: ${url}`));
      document.head.appendChild(s);
    });
    loadedScripts.set(url, p);
    return p;
  }

  function loadCertLibs() {
    // Load both libs in parallel
    return Promise.all([loadScript(LIBS.html2canvas), loadScript(LIBS.jspdf)]);
  }

  $(document).ready(function () {
    // -------- DOM --------
    const el = {
      duration: document.getElementById('btt-duration-select'),
      passage: document.getElementById('btt-passage-select'),
      start: document.getElementById('btt-start-btn'),
      newBtn: document.getElementById('btt-new-btn'),
      reset: document.getElementById('btt-reset-btn'),
      diffBtns: Array.from(document.querySelectorAll('.btt-diff-btn')),
      text: document.getElementById('btt-text'),
      input: document.getElementById('btt-input'),
      timeLeft: document.getElementById('btt-time-left'),
      wpm: document.getElementById('btt-wpm'),
      acc: document.getElementById('btt-accuracy'),
      correct: document.getElementById('btt-correct'),
      wrong: document.getElementById('btt-wrong'),
      result: document.getElementById('btt-result'),
      badgeEasy: document.getElementById('btt-badge-easy'),
      badgeMed: document.getElementById('btt-badge-medium'),
      badgeHard: document.getElementById('btt-badge-hard'),
      certBtn: document.getElementById('btt-certificate-btn'),
      certHint: document.getElementById('btt-certificate-hint'),
      modal: document.getElementById('btt-modal'),
      nameInput: document.getElementById('btt-user-name'),
      modalNote: document.getElementById('btt-modal-note'),
      genCert: document.getElementById('btt-generate-certificate'),
      cert: document.getElementById('btt-certificate'),
      certName: document.getElementById('btt-cert-name'),
      certDate: document.getElementById('btt-cert-date'),
      certId: document.getElementById('btt-cert-id'),
      certTable: document.getElementById('btt-cert-table'),
    };

    const currentLang = window.bttData && window.bttData.language ? window.bttData.language : 'default';
    const STORAGE_KEY = BASE_STORAGE_KEY + currentLang;

    // -------- Data --------
    const passagesByDifficulty = (window.bttData && window.bttData.passagesByDifficulty) || {};
    const certCfg = (window.bttData && window.bttData.certificate) || {};
    const MIN_WPM = Number(certCfg.minWpm || 30);
    const MIN_DURATION = Number(certCfg.minDuration || 60);
    const DOWNLOAD_NAME = String(certCfg.downloadFilename || 'Typing-Certificate');

    const difficultyOrder = ['easy', 'medium', 'hard'];

    // -------- State (optimized) --------
    let currentDifficulty = 'medium';
    let passages = [];
    let currentWords = [];            // expected words
    let wordSpans = [];               // cached span elements
    let wordState = [];               // 0 none, 1 correct, 2 wrong

    let committedCount = 0;           // number of committed words (completed by whitespace)
    let currentIndex = 0;             // current word index (in progress)

    let testStarted = false;
    let durationSec = 60;
    let timeRemaining = 60;
    let timer = null;
    let startMs = 0;

    // Stats counters updated incrementally
    let correctCount = 0;
    let wrongCount = 0;

    // UI update throttling
    let statsDirty = false;
    let statsRaf = 0;
    let lastStatsPaint = 0;
    const STATS_MIN_INTERVAL = 250; // ms

    // -------- Storage helpers --------
    function loadProgress() {
      const raw = localStorage.getItem(STORAGE_KEY);
      const base = { easy: null, medium: null, hard: null };
      const parsed = safeJsonParse(raw, base);
      return {
        easy: parsed.easy || null,
        medium: parsed.medium || null,
        hard: parsed.hard || null,
      };
    }

    function saveProgress(p) {
      localStorage.setItem(STORAGE_KEY, JSON.stringify(p));
    }

    function isPassed(entry) {
      return !!(entry && typeof entry.wpm === 'number' && entry.wpm >= MIN_WPM && typeof entry.accuracy === 'number');
    }

    function bestOf(existing, incoming) {
      if (!existing) return incoming;
      if (incoming.wpm > existing.wpm) return incoming;
      if (incoming.wpm === existing.wpm && incoming.accuracy > existing.accuracy) return incoming;
      return existing;
    }

    function setResult(message, type) {
      el.result.classList.remove('is-good', 'is-warn', 'is-bad');
      if (type) el.result.classList.add(type);
      el.result.textContent = message || '';
    }

    function renderBadges() {
      const p = loadProgress();

      function renderBadge(node, label, entry) {
        node.classList.remove('is-done', 'is-pending');
        if (isPassed(entry)) {
          node.classList.add('is-done');
          node.innerHTML = `${label}: <strong>${entry.wpm} WPM · ${entry.accuracy}%</strong>`;
        } else {
          node.classList.add('is-pending');
          node.innerHTML = `${label}: <strong>Need ${MIN_WPM}+ WPM</strong>`;
        }
      }

      renderBadge(el.badgeEasy, 'Easy', p.easy);
      renderBadge(el.badgeMed, 'Medium', p.medium);
      renderBadge(el.badgeHard, 'Hard', p.hard);

      const eligible = difficultyOrder.every((d) => isPassed(p[d]));
      el.certBtn.disabled = !eligible;
      el.certHint.textContent = eligible
        ? '✅ You are eligible! Enter your name to download your A4 certificate.'
        : `To unlock certificate: pass Easy, Medium, Hard with ${MIN_WPM}+ WPM (minimum ${Math.floor(MIN_DURATION / 60)} minute test).`;
    }

    // -------- Passage rendering (optimized) --------
    function renderPassage(content) {
      const words = String(content || '').trim().split(/\s+/).filter(Boolean);
      currentWords = words;
      committedCount = 0;
      currentIndex = 0;

      correctCount = 0;
      wrongCount = 0;
      wordState = new Array(words.length).fill(0);

      // Build HTML once
      el.text.innerHTML = words.map((w, idx) => `<span class="btt-word" data-index="${idx}">${w}</span>`).join(' ');
      // Cache spans for fast access
      wordSpans = Array.from(el.text.querySelectorAll('span.btt-word'));

      // Reset UI
      el.wpm.textContent = '0';
      el.acc.textContent = '0';
      el.correct.textContent = '0';
      el.wrong.textContent = '0';

      highlightIndex(0);
      markStatsDirty();
    }

    function highlightIndex(newIndex) {
      if (!wordSpans.length) return;
      const prev = currentIndex;
      currentIndex = clamp(newIndex, 0, wordSpans.length);
      if (wordSpans[prev]) wordSpans[prev].classList.remove('btt-current');
      if (wordSpans[currentIndex]) wordSpans[currentIndex].classList.add('btt-current');
    }

    function setWordState(i, newState) {
      if (i < 0 || i >= wordSpans.length) return;
      const prevState = wordState[i];
      if (prevState === newState) return;

      // Remove previous counters/classes
      if (prevState === 1) correctCount -= 1;
      if (prevState === 2) wrongCount -= 1;
      wordSpans[i].classList.remove('btt-correct', 'btt-wrong');

      // Apply new
      wordState[i] = newState;
      if (newState === 1) {
        correctCount += 1;
        wordSpans[i].classList.add('btt-correct');
      } else if (newState === 2) {
        wrongCount += 1;
        wordSpans[i].classList.add('btt-wrong');
      }
      markStatsDirty();
    }

    // -------- Stats painting (throttled) --------
    function computeWpmAcc(elapsedMs) {
      const elapsedMin = Math.max(0.0001, elapsedMs / 60000);
      const wpmVal = Math.round(correctCount / elapsedMin);
      const total = correctCount + wrongCount;
      const accVal = total > 0 ? Math.round((correctCount / total) * 100) : 0;
      return {
        wpm: clamp(wpmVal, 0, 999),
        acc: clamp(accVal, 0, 100),
      };
    }

    function paintStats(force) {
      const now = Date.now();
      if (!force && now - lastStatsPaint < STATS_MIN_INTERVAL) return;
      lastStatsPaint = now;

      const elapsedMs = testStarted ? (now - startMs) : 0;
      const s = computeWpmAcc(elapsedMs);
      el.wpm.textContent = String(s.wpm);
      el.acc.textContent = String(s.acc);
      el.correct.textContent = String(correctCount);
      el.wrong.textContent = String(wrongCount);
      statsDirty = false;
    }

    function markStatsDirty() {
      statsDirty = true;
      if (statsRaf) return;
      statsRaf = requestAnimationFrame(() => {
        statsRaf = 0;
        if (statsDirty) paintStats(false);
      });
    }

    // -------- Timer --------
    function stopTimer() {
      if (timer) {
        clearInterval(timer);
        timer = null;
      }
    }

    function finishTest(reason) {
      stopTimer();
      testStarted = false;
      el.input.disabled = true;
      paintStats(true);

      const elapsedMs = Math.max(1000, Date.now() - startMs);
      const s = computeWpmAcc(elapsedMs);
      const wpmFinal = s.wpm;
      const accFinal = s.acc;

      const durationOk = durationSec >= MIN_DURATION;
      const speedOk = wpmFinal >= MIN_WPM;

      // Friendly feedback
      if (accFinal >= 95 && speedOk) {
        setResult(`🎉 Great! ${wpmFinal} WPM · ${accFinal}% accuracy.`, 'is-good');
      } else if (accFinal < 85) {
        setResult(`⚠️ ${wpmFinal} WPM · ${accFinal}% accuracy. Tip: slow down—accuracy first.`, 'is-warn');
      } else {
        setResult(`Result: ${wpmFinal} WPM · ${accFinal}% accuracy.`, '');
      }

      // Update progress only if requirements met
      if (durationOk && speedOk) {
        const progress = loadProgress();
        const incoming = { wpm: wpmFinal, accuracy: accFinal, date: nowISODate(), duration: durationSec };
        progress[currentDifficulty] = bestOf(progress[currentDifficulty], incoming);
        saveProgress(progress);
        renderBadges();

        // Smart suggestion
        if (currentDifficulty !== 'hard' && accFinal >= 95 && wpmFinal >= (MIN_WPM + 5)) {
          setTimeout(() => {
            setResult('🚀 You’re doing great! Try the next difficulty for faster improvement.', 'is-good');
          }, 500);
        }
      } else {
        if (!durationOk) {
          setResult(`ℹ️ This attempt won't count for certificate. Use at least ${Math.floor(MIN_DURATION / 60)} minute duration.`, 'is-warn');
        } else if (!speedOk) {
          setResult(`ℹ️ This attempt won't count for certificate. Need ${MIN_WPM}+ WPM in this level.`, 'is-warn');
        }
      }

      if (reason === 'complete') {
        // no-op
      }
    }

    function startTest() {
      const passage = getSelectedPassage();
      if (!passage) {
        setResult('No passage available for this difficulty.', 'is-bad');
        return;
      }

      stopTimer();
      setResult('', '');

      durationSec = Number(el.duration.value || 60);
      timeRemaining = durationSec;
      el.timeLeft.textContent = String(timeRemaining);

      renderPassage(passage.content);

      el.input.value = '';
      el.input.disabled = false;
      el.input.focus();

      testStarted = true;
      startMs = Date.now();
      lastStatsPaint = 0;
      paintStats(true);

      timer = setInterval(() => {
        timeRemaining -= 1;
        if (timeRemaining < 0) timeRemaining = 0;
        el.timeLeft.textContent = String(timeRemaining);
        markStatsDirty();
        if (timeRemaining <= 0) finishTest('time');
      }, 1000);
    }

    function resetUi() {
      stopTimer();
      testStarted = false;
      el.input.value = '';
      el.input.disabled = true;
      el.text.innerHTML = '';
      durationSec = Number(el.duration.value || 60);
      timeRemaining = durationSec;
      el.timeLeft.textContent = String(timeRemaining);
      el.wpm.textContent = '0';
      el.acc.textContent = '0';
      el.correct.textContent = '0';
      el.wrong.textContent = '0';
      setResult('', '');
    }

    // -------- Passage selection --------
    function populatePassages() {
      el.passage.innerHTML = '';
      passages = shuffleArray((passagesByDifficulty[currentDifficulty] || []).slice());
      if (!passages.length) {
        const opt = document.createElement('option');
        opt.value = '';
        opt.textContent = 'No passages';
        el.passage.appendChild(opt);
        return;
      }
      for (const p of passages) {
        const opt = document.createElement('option');
        opt.value = p.id;
        opt.textContent = p.title;
        el.passage.appendChild(opt);
      }
    }

    function getSelectedPassage() {
      const id = el.passage.value;
      return passages.find((p) => p.id === id) || passages[0] || null;
    }

    function newRandomTest() {
      const list = shuffleArray((passagesByDifficulty[currentDifficulty] || []).slice());
      if (list.length) {
        passages = list;
        el.passage.innerHTML = '';
        for (const p of passages) {
          const opt = document.createElement('option');
          opt.value = p.id;
          opt.textContent = p.title;
          el.passage.appendChild(opt);
        }
        el.passage.value = passages[0].id;
      }
      startTest();
    }

    // -------- Input handling (incremental + low DOM churn) --------
    function parseCommittedWords(raw) {
      const text = String(raw || '');
      // Split but keep in-progress word excluded unless user typed whitespace.
      const parts = text.split(/\s+/);
      if (!parts.length) return { words: [], inProgressIndex: 0, committed: 0 };
      const hasTrailingWs = endsWithWhitespace(text);

      // If user is in the middle of typing, last token is in-progress; commit only full words.
      const committed = hasTrailingWs ? parts.filter(Boolean) : parts.slice(0, -1).filter(Boolean);
      const committedCountLocal = committed.length;
      const inProgressIndex = committedCountLocal; // where current word cursor points
      return { words: committed, inProgressIndex, committed: committedCountLocal, hasTrailingWs };
    }

    function recomputeFrom(index, committedWords) {
      // When user edits earlier words, we re-evaluate from that index onward.
      for (let i = index; i < committedWords.length && i < currentWords.length; i++) {
        const typed = normalizeWord(committedWords[i]);
        const expected = normalizeWord(currentWords[i] || '');
        const state = typed ? (typed === expected ? 1 : 2) : 0;
        setWordState(i, state);
      }
      // Clear states beyond current committed length (if user backspaced)
      for (let i = committedWords.length; i < wordSpans.length; i++) {
        if (wordState[i] !== 0) setWordState(i, 0);
      }
    }

    function onTyping() {
      if (!testStarted) return;
      const parsed = parseCommittedWords(el.input.value);

      // If committed count shrank, user backspaced: re-evaluate from new committed count.
      if (parsed.committed < committedCount) {
        recomputeFrom(parsed.committed, parsed.words);
      }

      // If committed advanced, mark newly committed word(s)
      if (parsed.committed > committedCount) {
        for (let i = committedCount; i < parsed.committed; i++) {
          const typed = normalizeWord(parsed.words[i]);
          const expected = normalizeWord(currentWords[i] || '');
          const state = typed ? (typed === expected ? 1 : 2) : 0;
          setWordState(i, state);
        }
      }

      committedCount = parsed.committed;
      highlightIndex(parsed.inProgressIndex);

      // Finish early only if all words are committed (user typed last whitespace)
      if (parsed.hasTrailingWs && committedCount >= currentWords.length && currentWords.length > 0) {
        finishTest('complete');
      }
    }

    // -------- Difficulty switching --------
    function setDifficulty(diff) {
      if (!passagesByDifficulty[diff]) diff = 'medium';
      currentDifficulty = diff;
      el.diffBtns.forEach((b) => b.classList.remove('is-active'));
      el.diffBtns.filter((b) => b.getAttribute('data-difficulty') === diff).forEach((b) => b.classList.add('is-active'));

      populatePassages();
      resetUi();

      const hint = diff === 'easy'
        ? 'Easy: for beginners—focus on accuracy.'
        : diff === 'hard'
          ? 'Hard: exam style—longer sentences & punctuation.'
          : 'Medium: balanced practice for most users.';
      setResult(hint, '');
    }

    // -------- Certificate (lazy libs) --------
    function openModal() {
      el.modal.classList.add('is-open');
      el.modal.setAttribute('aria-hidden', 'false');
      el.modalNote.textContent = 'Your certificate will include your name, accuracy and speed for Easy/Medium/Hard.';
      el.nameInput.value = '';
      el.nameInput.focus();
    }

    function closeModal() {
      el.modal.classList.remove('is-open');
      el.modal.setAttribute('aria-hidden', 'true');
    }

    function buildCertificateTable(progress) {
      el.certTable.innerHTML = difficultyOrder.map((d) => {
        const entry = progress[d];
        const label = d.charAt(0).toUpperCase() + d.slice(1);
        const wpm = entry ? entry.wpm : '—';
        const acc = entry ? entry.accuracy : '—';
        return `
          <div class="btt-cert__row">
            <div class="btt-cert__cell"><strong>${label}</strong></div>
            <div class="btt-cert__cell">${wpm} WPM</div>
            <div class="btt-cert__cell">${acc}% Accuracy</div>
          </div>
        `;
      }).join('');
    }

    function validateName(name) {
      const trimmed = String(name || '').trim();
      if (trimmed.length < 3) return { ok: false, msg: 'Please enter a valid name (min 3 characters).' };
      return { ok: true, value: trimmed };
    }

    async function generatePdfFromCertificate(name) {
      const progress = loadProgress();

      el.certName.textContent = name;
      el.certDate.textContent = nowISODate();
      el.certId.textContent = makeCertId();
      buildCertificateTable(progress);

      // Make it renderable for html2canvas
      el.cert.classList.add('is-render');

      const canvas = await window.html2canvas(el.cert, {
        scale: 2,
        useCORS: true,
        backgroundColor: '#ffffff',
      });

      const imgData = canvas.toDataURL('image/jpeg', 0.95);
      const { jsPDF } = window.jspdf;
      const pdf = new jsPDF({ orientation: 'portrait', unit: 'mm', format: 'a4' });
      pdf.addImage(imgData, 'JPEG', 0, 0, 210, 297);

      const safeName = name.replace(/[^a-zA-Z0-9\u0980-\u09FF\s._-]/g, '').trim().slice(0, 40) || 'User';
      pdf.save(`${DOWNLOAD_NAME}-${safeName}.pdf`);

      el.cert.classList.remove('is-render');
    }

    async function onGenerateCertificate() {
      const p = loadProgress();
      const eligible = difficultyOrder.every((d) => isPassed(p[d]));
      if (!eligible) {
        renderBadges();
        closeModal();
        return;
      }

      const v = validateName(el.nameInput.value);
      if (!v.ok) {
        el.modalNote.textContent = v.msg;
        return;
      }

      try {
        el.genCert.disabled = true;
        el.genCert.textContent = 'Loading…';
        await loadCertLibs();

        el.genCert.textContent = 'Generating…';
        await generatePdfFromCertificate(v.value);
        closeModal();
      } catch (e) {
        // eslint-disable-next-line no-console
        console.error(e);
        el.modalNote.textContent = 'Sorry—could not generate the PDF in this browser. Try Chrome/Edge or update your browser.';
      } finally {
        el.genCert.disabled = false;
        el.genCert.textContent = 'Generate PDF';
      }
    }

    // -------- Events --------
    el.diffBtns.forEach((b) => {
      b.addEventListener('click', () => setDifficulty(String(b.getAttribute('data-difficulty') || 'medium')));
    });

    el.start.addEventListener('click', startTest);
    el.newBtn.addEventListener('click', newRandomTest);
    el.reset.addEventListener('click', resetUi);
    el.input.addEventListener('input', onTyping, { passive: true });

    // Modal close handlers
    el.modal.addEventListener('click', (e) => {
      const t = e.target;
      if (t && t.hasAttribute && t.hasAttribute('data-btt-close')) closeModal();
    });
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && el.modal.classList.contains('is-open')) closeModal();
    });

    el.certBtn.addEventListener('click', openModal);
    el.genCert.addEventListener('click', onGenerateCertificate);

    // -------- Init --------
    populatePassages();
    resetUi();
    renderBadges();
    setDifficulty('medium');
  });
})(jQuery);
