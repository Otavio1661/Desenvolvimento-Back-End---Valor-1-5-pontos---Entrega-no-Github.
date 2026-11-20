<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= htmlspecialchars($titulo ?? 'Cadastro', ENT_QUOTES, 'UTF-8') ?></title>
<link rel="stylesheet" href="<?= asset('/libr4/dist/libr4.min.css') ?>">
<link rel="stylesheet" href="<?= asset('/cadastro.css') ?>">
</head>
<body>
<main>
  <r4-div flex="true" justify="between" align="center" wrap="true">
    <h1>Cadastro acadêmico</h1>
    <r4-themeswitch></r4-themeswitch>
  </r4-div>
  <div id="aviso"></div>
  <nav>
    <r4-button id="b-estudantes" text="Estudantes" variant="primary"></r4-button>
    <r4-button id="b-cursos" text="Cursos"></r4-button>
    <r4-button id="b-matriculas" text="Matrículas"></r4-button>
  </nav>

  <section id="aba-estudantes">
    <r4-panel title="Novo estudante" collapsible="true" class="painel-form">
      <r4-form id="f-estudantes" action="/cadastro/api/estudantes/salvar" submit-text="Salvar estudante" reset-on-success="true" buttons="true">
        <r4-formrow cols="2">
          <r4-textfield name="nome" label="Nome" required="true"></r4-textfield>
          <r4-textfield name="email" label="E-mail" required="true"></r4-textfield>
          <r4-textfield name="cpf" label="CPF" required="true"></r4-textfield>
          <r4-datefield name="data_nascimento" label="Nascimento" required="true"></r4-datefield>
          <r4-textfield name="telefone" label="Telefone" required="true"></r4-textfield>
        </r4-formrow>
        <r4-fieldset title="Endereço">
          <r4-formrow cols="3">
            <r4-textfield name="logradouro" label="Logradouro" required="true"></r4-textfield>
            <r4-textfield name="numero" label="Número" required="true"></r4-textfield>
            <r4-textfield name="complemento" label="Complemento"></r4-textfield>
            <r4-textfield name="bairro" label="Bairro" required="true"></r4-textfield>
            <r4-textfield name="cidade" label="Cidade" required="true"></r4-textfield>
            <r4-textfield name="estado" label="UF" required="true"></r4-textfield>
            <r4-textfield name="cep" label="CEP" required="true"></r4-textfield>
          </r4-formrow>
        </r4-fieldset>
      </r4-form>
    </r4-panel>
    <r4-panel title="Estudantes cadastrados">
      <div id="g-estudantes"></div>
      <div class="acoes"><r4-button id="e-estudantes" text="Editar selecionado"></r4-button><r4-button id="x-estudantes" text="Excluir selecionado" variant="danger"></r4-button></div>
    </r4-panel>
  </section>

  <section id="aba-cursos" hidden>
    <r4-panel title="Novo curso" collapsible="true" class="painel-form">
      <r4-form id="f-cursos" action="/cadastro/api/cursos/salvar" submit-text="Salvar curso" reset-on-success="true" buttons="true">
        <r4-formrow cols="2">
          <r4-textfield name="nome" label="Nome" required="true"></r4-textfield>
          <r4-textfield name="codigo" label="Código" required="true"></r4-textfield>
          <r4-numberfield name="carga_horaria" label="Carga horária (h)" required="true"></r4-numberfield>
          <r4-select id="s-modalidade" name="modalidade" label="Modalidade" required="true"></r4-select>
        </r4-formrow>
        <r4-textfield name="descricao" label="Descrição" required="true"></r4-textfield>
      </r4-form>
    </r4-panel>
    <r4-panel title="Cursos cadastrados">
      <div id="g-cursos"></div>
      <div class="acoes"><r4-button id="e-cursos" text="Editar selecionado"></r4-button><r4-button id="x-cursos" text="Excluir selecionado" variant="danger"></r4-button></div>
    </r4-panel>
  </section>

  <section id="aba-matriculas" hidden>
    <r4-panel title="Nova matrícula" collapsible="true" class="painel-form">
      <r4-form id="f-matriculas" action="/cadastro/api/matriculas/salvar" submit-text="Matricular" reset-on-success="true" buttons="true">
        <r4-formrow cols="2">
          <r4-select id="s-estudante" name="estudante_id" label="Estudante" required="true"></r4-select>
          <r4-select id="s-curso" name="curso_id" label="Curso" required="true"></r4-select>
          <r4-datefield name="data_matricula" label="Data" required="true"></r4-datefield>
          <r4-select id="s-status" name="status" label="Status" required="true"></r4-select>
        </r4-formrow>
      </r4-form>
    </r4-panel>
    <r4-panel title="Matrículas">
      <div id="g-matriculas"></div>
      <div class="acoes"><r4-button id="x-matriculas" text="Excluir selecionada" variant="danger"></r4-button></div>
    </r4-panel>
  </section>
</main>
<script src="<?= asset('/libr4/dist/libr4.min.js') ?>"></script>
<script src="<?= asset('/cadastro.js') ?>"></script>
</body>
</html>

