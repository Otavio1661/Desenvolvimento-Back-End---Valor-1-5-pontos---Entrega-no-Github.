# Cadastro Acadêmico — PHP + MongoDB

Sistema de cadastro de **Estudantes** (com endereço), **Cursos** e **Matrículas**, desenvolvido em PHP e persistido em MongoDB.

## Participantes

| Nome | RA |
|---|---|
| Otávio Silva | 60005860 |
| Oscar de Mello Ribeiro | 60007517 |

## Tecnologias

- PHP 8.2+ com a extensão `mongodb` 2.4+ e a biblioteca oficial `mongodb/mongodb` (Composer)
- MongoDB (local ou Atlas)
- Mini framework PHP próprio (`core/` e `src/`): roteador, controller, segurança de requisições e leitura do `.env`
- **libr4**: biblioteca de interface em JavaScript, de autoria própria (`public/libr4/dist`)

## Uso de Inteligência Artificial

Foi utilizada IA (Claude, da Anthropic) como ferramenta de apoio no desenvolvimento deste trabalho, principalmente na implementação do cadastro, na integração com o MongoDB e na documentação.

A **base do PHP (mini framework em `core/`) e a biblioteca libr4 são de autoria própria** dos participantes e foram desenvolvidas anteriormente, sem depender deste trabalho.

## Estrutura do banco (MongoDB)

Banco: `cadastro_academico` (configurável em `MONGO_DB`). O relacionamento é feito por **referência** (`ObjectId`); nenhum dado é embutido em outro documento.

```
enderecos (1) ──── (1) estudantes (N) ──── (N) cursos
                          │                     │
                          └──── matriculas ─────┘
```

- **Estudante ↔ Endereço (1:1):** `estudantes.endereco_id` → `enderecos._id`
- **Estudante ↔ Curso (N:N):** coleção `matriculas`, com `estudante_id` e `curso_id`. Um curso tem vários estudantes e um estudante pode estar em vários cursos.

## DDL (definição das coleções)

O MongoDB não exige DDL, mas abaixo está o esquema equivalente (validação `$jsonSchema` e índices) que descreve a estrutura usada pela aplicação:

```javascript
use cadastro_academico

db.createCollection("enderecos", { validator: { $jsonSchema: {
  bsonType: "object",
  required: ["logradouro", "numero", "bairro", "cidade", "estado", "cep"],
  properties: {
    logradouro:  { bsonType: "string" },
    numero:      { bsonType: "string" },
    complemento: { bsonType: "string" },
    bairro:      { bsonType: "string" },
    cidade:      { bsonType: "string" },
    estado:      { bsonType: "string" },
    cep:         { bsonType: "string" }
  }
}}})

db.createCollection("estudantes", { validator: { $jsonSchema: {
  bsonType: "object",
  required: ["nome", "email", "cpf", "data_nascimento", "telefone", "endereco_id"],
  properties: {
    nome:            { bsonType: "string" },
    email:           { bsonType: "string" },
    cpf:             { bsonType: "string" },
    data_nascimento: { bsonType: "string" },
    telefone:        { bsonType: "string" },
    endereco_id:     { bsonType: "objectId" }   // ref. enderecos._id
  }
}}})
db.estudantes.createIndex({ cpf: 1 }, { unique: true })

db.createCollection("cursos", { validator: { $jsonSchema: {
  bsonType: "object",
  required: ["nome", "codigo", "descricao", "carga_horaria", "modalidade"],
  properties: {
    nome:          { bsonType: "string" },
    codigo:        { bsonType: "string" },
    descricao:     { bsonType: "string" },
    carga_horaria: { bsonType: "int" },
    modalidade:    { enum: ["Presencial", "EAD", "Híbrido"] }
  }
}}})
db.cursos.createIndex({ codigo: 1 }, { unique: true })

db.createCollection("matriculas", { validator: { $jsonSchema: {
  bsonType: "object",
  required: ["estudante_id", "curso_id", "data_matricula", "status"],
  properties: {
    estudante_id:   { bsonType: "objectId" },   // ref. estudantes._id
    curso_id:       { bsonType: "objectId" },   // ref. cursos._id
    data_matricula: { bsonType: "string" },
    status:         { enum: ["ativa", "trancada", "concluida", "cancelada"] }
  }
}}})
db.matriculas.createIndex({ estudante_id: 1, curso_id: 1 }, { unique: true })
```

### Campos por coleção

| Coleção | Campos |
|---|---|
| `enderecos` | logradouro, numero, complemento (opcional), bairro, cidade, estado, cep |
| `estudantes` | nome, email, cpf, data_nascimento, telefone, endereco_id |
| `cursos` | nome, codigo, descricao, carga_horaria, modalidade |
| `matriculas` | estudante_id, curso_id, data_matricula, status |

### Regras de integridade (aplicadas no PHP)

- CPF do estudante e código do curso são únicos.
- O mesmo estudante não se matricula duas vezes no mesmo curso.
- Excluir um estudante remove seu endereço e suas matrículas.
- Excluir um curso remove suas matrículas.

## Como executar

1. Instale o PHP 8.2+ com a extensão `mongodb` habilitada e rode `composer install`.
2. Copie `Exemplo.env` para `.env` e ajuste:
   ```
   MONGO_URI="mongodb://127.0.0.1:27017"   # ou a string do Atlas (mongodb+srv://...)
   MONGO_DB="cadastro_academico"
   ```
3. Inicie o servidor: `composer dev` (ou `php -S localhost:8000 -t public`).
4. Acesse http://localhost:8000

## Estrutura de pastas

```
core/     mini framework PHP (roteador, controller, segurança)
src/      aplicação (rotas, controller, conexão Mongo, view)
public/   index.php, cadastro.js e a libr4 (dist)
```
