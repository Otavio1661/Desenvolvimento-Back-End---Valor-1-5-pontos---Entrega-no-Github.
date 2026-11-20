(function () {
  'use strict';
  const $ = (s) => document.querySelector(s);
  const ABAS = ['estudantes', 'cursos', 'matriculas'];
  const dados = {};
  const grids = {};
  const editando = {};   // id em edição por formulário

  function aviso(texto, ok) {
    $('#aviso').replaceChildren();
    if (texto) R4.create({ xtype: 'alert', variant: ok ? 'ok' : 'err', text: texto, closable: true, renderTo: '#aviso' });
  }

  async function api(url, corpo) {
    const r = await fetch(url, corpo === undefined ? {} : {
      method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(corpo)
    });
    const j = await r.json().catch(() => ({}));
    if (!r.ok) throw new Error(j.retorno || 'Erro ' + r.status);
    return j;
  }

  const COLUNAS = {
    estudantes: [
      { header: 'Nome', dataIndex: 'nome' }, { header: 'E-mail', dataIndex: 'email' }, { header: 'CPF', dataIndex: 'cpf' },
      { header: 'Telefone', dataIndex: 'telefone' },
      { header: 'Endereço', dataIndex: 'endereco', renderer: (e) => e ? `${e.logradouro}, ${e.numero} - ${e.cidade}/${e.estado}` : '' }
    ],
    cursos: [
      { header: 'Nome', dataIndex: 'nome' }, { header: 'Código', dataIndex: 'codigo' },
      { header: 'Carga (h)', dataIndex: 'carga_horaria' }, { header: 'Modalidade', dataIndex: 'modalidade' },
      { header: 'Matriculados', dataIndex: 'matriculados' }
    ],
    matriculas: [
      { header: 'Estudante', dataIndex: 'estudante' }, { header: 'Curso', dataIndex: 'curso' },
      { header: 'Data', dataIndex: 'data_matricula' }, { header: 'Status', dataIndex: 'status' }
    ]
  };

  const opts = (lista, v, t) => lista.map((x) => ({ value: x[v], text: x[t] }));
  const campo = (id) => R4.get(id);

  async function carregar() {
    try {
      const r = await Promise.all(ABAS.map((a) => api('/cadastro/api/' + a)));
      ABAS.forEach((a, i) => {
        dados[a] = r[i].dados;
        if (!grids[a]) grids[a] = R4.create({ xtype: 'grid', renderTo: '#g-' + a, columns: COLUNAS[a], data: dados[a], selectable: 'single', idProperty: '_id', pageSize: 10 });
        else grids[a].loadData(dados[a]);
      });
      campo('s-estudante').setOptions(opts(dados.estudantes, '_id', 'nome'));
      campo('s-curso').setOptions(opts(dados.cursos, '_id', 'nome'));
    } catch (e) { aviso(e.message); }
  }

  function selecionado(aba) {
    const s = grids[aba] && grids[aba].getSelection();
    const linha = Array.isArray(s) ? s[0] : s;
    if (!linha) aviso('Selecione uma linha na tabela.');
    return linha || null;
  }

  async function excluir(aba) {
    const l = selecionado(aba);
    if (!l || !confirm('Excluir este registro?')) return;
    try { aviso((await api('/cadastro/api/excluir', { tipo: aba, id: l._id })).retorno, true); await carregar(); }
    catch (e) { aviso(e.message); }
  }

  function editar(aba) {
    const l = selecionado(aba);
    if (!l) return;
    editando[aba] = l._id;
    campo('f-' + aba).setValues(Object.assign({}, l, l.endereco || {}));
    $('#f-' + aba).scrollIntoView({ behavior: 'smooth' });
  }

  function mostrar(aba) {
    ABAS.forEach((a) => {
      $('#aba-' + a).hidden = a !== aba;
      campo('b-' + a).variant = a === aba ? 'primary' : 'default';
      const b = $('#b-' + a); b.classList.toggle('r4-btn-primary', a === aba);
    });
  }

  function iniciar() {
    campo('s-modalidade').setOptions(['Presencial', 'EAD', 'Híbrido'].map((v) => ({ value: v, text: v })));
    campo('s-status').setOptions(['ativa', 'trancada', 'concluida', 'cancelada'].map((v) => ({ value: v, text: v })));
    ABAS.forEach((a) => {
      const f = campo('f-' + a);
      f.on('beforesubmit', (_f, valores) => { if (editando[a]) valores.id = editando[a]; });
      f.on('success', () => { editando[a] = null; aviso('Registro salvo.', true); carregar(); });
      f.on('reset', () => { editando[a] = null; });
      campo('b-' + a).on('click', () => mostrar(a));
      campo('x-' + a).on('click', () => excluir(a));
      if (a !== 'matriculas') campo('e-' + a).on('click', () => editar(a));
    });
    carregar();
  }

  // as tags <r4-*> são montadas pela libr4 após o carregamento da página
  window.addEventListener('load', () => setTimeout(iniciar, 50));
})();
