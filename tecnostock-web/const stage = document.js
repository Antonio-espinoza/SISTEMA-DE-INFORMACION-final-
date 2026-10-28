const stage = document.getElementById('stage');
const hint = document.getElementById('hint');
let current = 0;

const leafSVG = `<svg class="leaf-corner CLASS" viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg">
  <path d="M4 4 C 30 4, 60 20, 60 60 C 30 55, 6 34, 4 4 Z" fill="none" stroke="#7fa389" stroke-width="1.2"/>
  <path d="M8 10 C 24 18, 40 34, 52 52" fill="none" stroke="#7fa389" stroke-width="1"/>
</svg>`;

const pulseSVG = `<svg class="pulse" viewBox="0 0 150 34" xmlns="http://www.w3.org/2000/svg">
  <path d="M0 17 L35 17 L44 4 L54 30 L62 17 L150 17"/>
</svg>`;

function buildPage(p, index){
  const el = document.createElement('div');
  el.className = 'page';
  el.style.zIndex = pages.length - index;

  let inner = '<div class="page-content">';
  if(p.eyebrow) inner += `<div class="eyebrow">${p.eyebrow}</div>`;
  if(p.isFinale){
    inner += `<div class="finale-icon">🤍💚</div>`;
    inner += `<h1 class="script">${p.title}</h1>`;
    inner += `<p class="letter-text">${p.body}</p>`;
    inner += pulseSVG;
    inner += `<div class="sign">${p.sign}</div>`;
    inner += `<div class="hearts">${p.hearts}</div>`;
  } else if(p.title){
    inner += `<h1 class="script">${p.title}</h1>`;
    inner += `<p class="letter-text">${p.body}</p>`;
    if(p.pulse) inner += pulseSVG;
  } else {
    inner += `<p class="letter-text">${p.body}</p>`;
    if(p.pulse) inner += pulseSVG;
  }
  inner += '</div>';

  if(index !== pages.length - 1){
    inner += `<div class="tap-affordance">${index+1} / ${pages.length}</div>`;
  }

  inner += leafSVG.replace('CLASS','tl');
  inner += leafSVG.replace('CLASS','br');

  el.innerHTML = inner;
  return el;
}

pages.forEach((p, i) => {
  book.appendChild(buildPage(p, i));
});

pages.forEach((_, i) => {
  const d = document.createElement('div');
  d.className = 'dot' + (i === 0 ? ' active' : '');
  progress.appendChild(d);
});

function updateDots(){
  [...progress.children].forEach((d, i) => {
    d.classList.toggle('active', i === current);
  });
}

function turnNext(){
  if(current >= pages.length - 1) return;
  const pageEl = book.children[current];
  pageEl.classList.add('turned');
  current++;
  updateDots();
  if(hint) hint.style.display = 'none';
}

stage.addEventListener('click', turnNext);
document.addEventListener('keydown', (e) => {
  if(e.key === 'ArrowRight' || e.key === ' ' || e.key === 'Enter') turnNext();
});
</script>
</body>
</html>
