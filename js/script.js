/* script.js
 - Хранение: localStorage под ключом 'edu_data_v1'
 - Админ: вход только через пароль (ADMIN_SECRET)
 - После проверки — блокировка теста и сохранение результата
 - Добавлена кнопка "Выйти из администратора"
*/

const ADMIN_SECRET = "123";
const STORAGE_KEY = "edu_data_v1";

let state = { lectures: [], selectedLectureId: null, isAdmin: false };

const $ = s => document.querySelector(s);
const $$ = s => Array.from(document.querySelectorAll(s));
const uid = (p='id') => p + '_' + Math.random().toString(36).slice(2,9);

function saveState() {
  localStorage.setItem(STORAGE_KEY, JSON.stringify(state.lectures));
}
function loadState() {
  const data = localStorage.getItem(STORAGE_KEY);
  if (data) {
    try { state.lectures = JSON.parse(data); } catch { state.lectures = []; }
  }
}

/* ---------- Определение администратора ---------- */
function detectAdmin() {
  const localToken = localStorage.getItem('edu_admin_token');
  state.isAdmin = (localToken === ADMIN_SECRET);
  renderAdminControls();
}

/* ---------- Вход / Выход администратора ---------- */
function enableAdminFlow() {
  const p = prompt('Введите пароль администратора:');
  if (!p) return;
  if (p === ADMIN_SECRET) {
    localStorage.setItem('edu_admin_token', ADMIN_SECRET);
    state.isAdmin = true;
    alert('Режим администратора активирован.');
  } else {
    alert('Неверный пароль.');
  }
  renderAdminControls();
}

function logoutAdmin() {
  localStorage.removeItem('edu_admin_token');
  state.isAdmin = false;
  alert('Вы вышли из режима администратора.');
  renderAdminControls();
}

/* ---------- Список лекций ---------- */
function renderLecturesList(){
  const c = $('#lecturesList'); c.innerHTML='';
  state.lectures.forEach(l=>{
    const item = document.createElement('div');
    item.className='lecture-item'+(state.selectedLectureId===l.id?' active':'');
    item.dataset.id=l.id;

    const left=document.createElement('div');
    left.style.display='flex';left.style.flexDirection='column';
    const t=document.createElement('div');
    t.className='lecture-title-small';t.textContent=l.title||'Без названия';
    left.appendChild(t);
    const m=document.createElement('div');
    m.className='small';m.textContent=`${(l.questions||[]).length} вопрос(ов)`;
    left.appendChild(m);

    const controls=document.createElement('div');
    controls.className='lecture-controls';
    item.appendChild(left); item.appendChild(controls);
    item.onclick=()=>onSwitchLecture(l.id);

    if(state.isAdmin){
      const edit=document.createElement('button');
      edit.className='icon-btn'; edit.textContent='✎';
      edit.onclick=e=>{e.stopPropagation();openEditLectureModal(l.id);};
      const del=document.createElement('button');
      del.className='icon-btn'; del.textContent='🗑';
      del.onclick=e=>{e.stopPropagation();if(confirm('Удалить лекцию?'))deleteLecture(l.id);};
      controls.append(edit,del);
    }
    c.appendChild(item);
  });
}

/* ---------- Переключение лекции ---------- */
function onSwitchLecture(nextId){
  const cur=state.selectedLectureId;
  if(cur && cur!==nextId) evaluateCurrentLectureAnswers(cur);
  state.selectedLectureId=nextId;
  renderMain(); renderLecturesList();
}

/* ---------- Основная область ---------- */
function renderMain(){
  const titleEl=$('#lectureTitle'),textEl=$('#lectureText'),
        metaEl=$('#lectureMeta'),lectureActions=$('#lectureActions');
  lectureActions.innerHTML='';
  if(!state.selectedLectureId){
    titleEl.textContent='Выберите лекцию';
    textEl.textContent=''; metaEl.textContent='';
    $('#questionsArea').innerHTML=''; return;
  }

  const lec=state.lectures.find(l=>l.id===state.selectedLectureId);
  if(!lec){ titleEl.textContent='Лекция не найдена'; $('#questionsArea').innerHTML=''; return; }

  titleEl.textContent=lec.title||'Без названия';
  textEl.textContent=lec.content||'';
  metaEl.textContent=`Вопросов: ${(lec.questions||[]).length}`;

  if(state.isAdmin){
    const add=document.createElement('button');
    add.className='btn'; add.textContent='Добавить вопрос';
    add.onclick=()=>openAddQuestionModal(lec.id);
    lectureActions.appendChild(add);
  }

  const qa=$('#questionsArea'); qa.innerHTML='';
  (lec.questions||[]).forEach((q,qi)=>{
    const wrap=document.createElement('div'); wrap.className='question'; wrap.dataset.qid=q.id;
    const qText=document.createElement('p'); qText.textContent=`${qi+1}. ${q.text}`; wrap.appendChild(qText);

    if(state.isAdmin){
      const eBtn=document.createElement('button');
      eBtn.className='icon-btn'; eBtn.textContent='✎'; eBtn.style.float='right';
      eBtn.onclick=()=>openEditQuestionModal(lec.id,q.id);
      const dBtn=document.createElement('button');
      dBtn.className='icon-btn'; dBtn.textContent='🗑'; dBtn.style.float='right';
      dBtn.onclick=()=>{if(confirm('Удалить вопрос?'))deleteQuestion(lec.id,q.id);};
      wrap.append(eBtn,dBtn);
    }

    const opts=document.createElement('div'); opts.className='options';
    q.options.forEach((opt,oi)=>{
      const lab=document.createElement('label'); lab.className='option';
      const r=document.createElement('input'); r.type='radio'; r.name=`q_${q.id}`; r.value=String(oi);
      if(q.userChoice!=null && String(q.userChoice)===String(oi)) r.checked=true;
      if(lec.completed) r.disabled=true;
      r.onchange=()=>{q.userChoice=Number(r.value); saveState();};
      const span=document.createElement('span'); span.textContent=opt.text;
      lab.append(r,span); opts.appendChild(lab);
    });
    wrap.appendChild(opts);

    const res=document.createElement('div'); res.className='result';
    if(q.lastResult!=null){ res.textContent=q.lastResult.correct?'✅ Верно':'❌ Неверно'; }
    wrap.appendChild(res);
    qa.appendChild(wrap);
  });

  if(lec.completed){
    showResultSummary(lec.correctCount, lec.questions.length, lec.lastGrade);
    return;
  }

  if ((lec.questions || []).length > 0) {
    const checkBtn = document.createElement('button');
    checkBtn.textContent = 'Проверить';
    checkBtn.classList.add('btn', 'primary');
    checkBtn.style.marginTop = '16px';
    checkBtn.onclick = () => {
      let correct = 0;
      const total = lec.questions.length;
      lec.questions.forEach(q => {
        const chosen = q.userChoice;
        const correctIndex = q.options.findIndex(o => o.correct);
        const isCorrect = (chosen != null && chosen === correctIndex);
        q.lastResult = { correct: isCorrect };
        if (isCorrect) correct++;
      });
      const score = Math.round((correct / total) * 100);
      lec.correctCount = correct;
      lec.completed = true;
      lec.lastGrade = `${score} / 100 баллов`;
      saveState();
      renderMain();
    };
    qa.appendChild(checkBtn);
  }
}

/* ---------- Проверка при смене лекции ---------- */
function evaluateCurrentLectureAnswers(id){
  const lec=state.lectures.find(l=>l.id===id);
  if(!lec) return;
  let correct=0;
  (lec.questions||[]).forEach(q=>{
    const ch=q.userChoice;
    const ci=q.options.findIndex(o=>o.correct);
    const ok=(ch!=null && ch===ci);
    q.lastResult={correct:ok};
    if(ok) correct++;
  });
  lec.lastSummary={correct,total:lec.questions.length,time:Date.now()};
  saveState();
}

/* ---------- Показ результата ---------- */
function showResultSummary(correct,total,grade=null){
  const qa=$('#questionsArea');
  let old=$('.result-summary');
  if(old) old.remove();
  const box=document.createElement('div');
  box.className='result-summary';
  if(!grade){ const score = Math.round((correct/total)*100); grade = `${score} / 100 баллов`; }
  box.innerHTML=`<strong>Результат:</strong> ${correct} из ${total}<br>Оценка: <b>${grade}</b>`;
  qa.appendChild(box);
}

/* ---------- Навигация ---------- */
function goToNextLecture(){
  if(!state.selectedLectureId) return;
  const i=state.lectures.findIndex(l=>l.id===state.selectedLectureId);
  const n=state.lectures[i+1];
  n?onSwitchLecture(n.id):alert('Это последняя лекция.');
}
function goToPrevLecture(){
  if(!state.selectedLectureId) return;
  const i=state.lectures.findIndex(l=>l.id===state.selectedLectureId);
  const p=state.lectures[i-1];
  p?onSwitchLecture(p.id):alert('Это первая лекция.');
}

/* ---------- CRUD лекций и вопросов ---------- */
function addLectureBulk(titles=['Новая лекция']){
  titles.forEach(t=>state.lectures.push({id:uid('lec'),title:t,content:'Текст лекции...',questions:[]}));
  saveState(); renderLecturesList();
}
function openEditLectureModal(id){const l=state.lectures.find(x=>x.id===id);if(!l)return;openModal('Редактировать лекцию',buildLectureForm(l),()=>{l.title=$('#modalBody input[name="title"]').value.trim()||'Без названия';l.content=$('#modalBody textarea[name="content"]').value;saveState();renderLecturesList();renderMain();closeModal();});}
function openAddLectureModal(){openModal('Добавить лекцию',buildLectureForm(),()=>{const t=$('#modalBody input[name="title"]').value.trim(),c=$('#modalBody textarea[name="content"]').value;const n={id:uid('lec'),title:t||'Новая лекция',content:c||'',questions:[]};state.lectures.push(n);saveState();state.selectedLectureId=n.id;renderLecturesList();renderMain();closeModal();});}
function deleteLecture(id){const i=state.lectures.findIndex(l=>l.id===id);if(i>=0){state.lectures.splice(i,1);state.selectedLectureId=state.lectures[Math.max(0,i-1)]?.id||null;saveState();renderLecturesList();renderMain();}}
function buildLectureForm(l=null){const c=document.createElement('div');c.innerHTML=`<div><label class="small">Заголовок</label><input name="title" class="input" value="${l?escapeHtml(l.title):''}"/></div><div style="margin-top:8px"><label class="small">Текст лекции</label><textarea name="content" class="input">${l?escapeHtml(l.content):''}</textarea></div>`;return c;}

function openAddQuestionModal(id){
  const lec=state.lectures.find(l=>l.id===id);if(!lec)return;
  openModal('Добавить вопрос',buildQuestionForm(),()=>{
    const qtext=$('#modalBody input[name="qtext"]').value.trim();
    const ci=Number($('#modalBody input[name="correct"]:checked')?.value??-1);
    const opts=[];for(let i=1;i<=4;i++){const t=$('#modalBody input[name="opt'+i+'"]').value.trim();if(t)opts.push({text:t,correct:(i-1)===ci});}
    if(opts.length===0){alert('Добавьте хотя бы один вариант');return;}
    lec.questions.push({id:uid('q'),text:qtext||'Вопрос',options:opts});
    saveState();renderMain();renderLecturesList();closeModal();
  });
}
function openEditQuestionModal(lid,qid){const l=state.lectures.find(x=>x.id===lid);if(!l)return;const q=l.questions.find(x=>x.id===qid);if(!q)return;openModal('Редактировать вопрос',buildQuestionForm(q),()=>{const t=$('#modalBody input[name="qtext"]').value.trim();const ci=Number($('#modalBody input[name="correct"]:checked')?.value??-1);const o=[];for(let i=1;i<=4;i++){const tx=$('#modalBody input[name="opt'+i+'"]').value.trim();if(tx)o.push({text:tx,correct:(i-1)===ci});}q.text=t||'Вопрос';q.options=o;saveState();renderMain();closeModal();});}
function deleteQuestion(lid,qid){const l=state.lectures.find(x=>x.id===lid);if(!l)return;const i=l.questions.findIndex(q=>q.id===qid);if(i>=0){l.questions.splice(i,1);saveState();renderMain();}}
function buildQuestionForm(q=null){const c=document.createElement('div');const o=q?q.options:[];const ci=o.findIndex(x=>x.correct);c.innerHTML=`<div><label class="small">Текст вопроса</label><input name="qtext" class="input" value="${q?escapeHtml(q.text):''}"/></div><div style="margin-top:8px"><label class="small">Варианты (до 4). Отметьте правильный.</label><div style="display:flex;flex-direction:column;gap:8px;margin-top:6px">${[0,1,2,3].map(i=>`<label class="flex-row"><input type="radio" name="correct" value="${i}" ${ci===i?'checked':''}/> <input class="input" name="opt${i+1}" placeholder="Вариант ${i+1}" style="flex:1;margin-left:8px" value="${o[i]?escapeHtml(o[i].text):''}"/></label>`).join('')}</div></div>`;return c;}
function openModal(t,b,s){$('#modalTitle').textContent=t;const body=$('#modalBody');body.innerHTML='';body.append(b);$('#modalOverlay').classList.remove('hidden');const save=()=>{if(typeof s==='function')s();r();};const cancel=()=>{closeModal();r();};function r(){$('#modalSave').removeEventListener('click',save);$('#modalCancel').removeEventListener('click',cancel);}$('#modalSave').addEventListener('click',save);$('#modalCancel').addEventListener('click',cancel);}
function closeModal(){ $('#modalOverlay').classList.add('hidden'); }

/* ---------- Панель администратора ---------- */
function renderAdminControls(){
  const addBtn=$('#btnAddLecture');
  const enableBtn=$('#btnEnableAdmin');
  if(state.isAdmin){
    addBtn.style.display='inline-block';
    enableBtn.textContent='Выйти из администратора';
    enableBtn.onclick=logoutAdmin;
  }else{
    addBtn.style.display='none';
    enableBtn.textContent='Включить админ';
    enableBtn.onclick=enableAdminFlow;
  }
}



function escapeHtml(s){return String(s||'').replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;').replaceAll('"','&quot;');}
function attachEvents(){
  $('#btnAddLecture').onclick=()=>openAddLectureModal();
  $('#btnNext').onclick=goToNextLecture;
  $('#btnPrev').onclick=goToPrevLecture;
}

/* ---------- Запуск ---------- */
async function start(){
  loadState(); detectAdmin(); attachEvents();
  if(state.lectures.length===0){
    addLectureBulk(['Введение','Основы','Практика']);
    const f=state.lectures[0];
    f.content='Пример текста первой лекции.';
    f.questions=[{id:uid('q'),text:'Что такое HTML?',options:[{text:'Язык разметки',correct:true},{text:'Язык программирования',correct:false}]},
                 {id:uid('q'),text:'Сколько вариантов может быть?',options:[{text:'До четырёх',correct:true},{text:'Пять',correct:false}]}];
    saveState();
  }
  if(!state.selectedLectureId && state.lectures.length>0) state.selectedLectureId=state.lectures[0].id;
  renderLecturesList(); renderMain(); renderAdminControls();
}
start();
