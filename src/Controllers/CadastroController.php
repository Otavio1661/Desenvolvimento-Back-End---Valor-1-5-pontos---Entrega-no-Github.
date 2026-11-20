<?php
namespace App\Controllers;

use App\Mongo;
use Core\Controller as ctrl;

/**
 * Coleções (relacionamento por referência, nada embutido):
 *   enderecos  <- estudantes.endereco_id            (1 estudante : 1 endereço)
 *   estudantes <- matriculas.estudante_id -> cursos (N estudantes : N cursos, via matriculas)
 */
final class CadastroController extends ctrl
{
    private const ESTUDANTE = ['nome', 'email', 'cpf', 'data_nascimento', 'telefone'];
    private const ENDERECO = ['logradouro', 'numero', 'bairro', 'cidade', 'estado', 'cep'];
    private const CURSO = ['nome', 'codigo', 'descricao', 'carga_horaria', 'modalidade'];

    public function index() { ctrl::render('cadastro/index', ['titulo' => 'Cadastro acadêmico']); }

    private function falha(string $msg, int $st = 422): void { ctrl::responder(['status' => $st, 'retorno' => $msg], $st); }

    /** Valida campos obrigatórios e devolve só os campos permitidos (texto aparado). */
    private function campos(array $d, array $obrig, array $opc = []): array
    {
        $r = [];
        foreach ($obrig as $c) {
            $v = $d[$c] ?? '';
            if (!is_scalar($v) || trim((string) $v) === '') $this->falha("Campo obrigatório: $c");
            $r[$c] = trim((string) $v);
        }
        foreach ($opc as $c) $r[$c] = is_scalar($d[$c] ?? null) ? trim((string) $d[$c]) : '';
        return $r;
    }

    private function executar(callable $fn)
    {
        try { return $fn(Mongo::banco()); }
        catch (\InvalidArgumentException $e) { $this->falha($e->getMessage()); }
        catch (\Throwable $e) { error_log('[cadastro] ' . $e->getMessage()); $this->falha('Sem conexão com o MongoDB. Verifique se o servidor está ligado e se MONGO_URI no .env está correta (porta padrão 27017; no Atlas, libere seu IP em Network Access).', 503); }
    }

    // ---------- listagens ----------
    public function estudantes()
    {
        $this->executar(function ($db) {
            $ends = [];
            foreach ($db->enderecos->find() as $e) $ends[(string) $e['_id']] = Mongo::paraArray($e);
            $out = [];
            foreach ($db->estudantes->find([], ['sort' => ['nome' => 1]]) as $e) {
                $e = Mongo::paraArray($e);
                $e['endereco'] = $ends[$e['endereco_id'] ?? ''] ?? null;
                $out[] = $e;
            }
            ctrl::responder(['status' => 200, 'dados' => $out]);
        });
    }

    public function cursos()
    {
        $this->executar(function ($db) {
            $qtd = [];
            foreach ($db->matriculas->aggregate([['$group' => ['_id' => '$curso_id', 'n' => ['$sum' => 1]]]]) as $g) $qtd[(string) $g['_id']] = $g['n'];
            $out = [];
            foreach ($db->cursos->find([], ['sort' => ['nome' => 1]]) as $c) {
                $c = Mongo::paraArray($c);
                $c['matriculados'] = $qtd[$c['_id']] ?? 0;
                $out[] = $c;
            }
            ctrl::responder(['status' => 200, 'dados' => $out]);
        });
    }

    public function matriculas()
    {
        $this->executar(function ($db) {
            $est = $cur = [];
            foreach ($db->estudantes->find() as $e) $est[(string) $e['_id']] = $e['nome'] ?? '?';
            foreach ($db->cursos->find() as $c) $cur[(string) $c['_id']] = $c['nome'] ?? '?';
            $out = [];
            foreach ($db->matriculas->find([], ['sort' => ['data_matricula' => -1]]) as $m) {
                $m = Mongo::paraArray($m);
                $m['estudante'] = $est[$m['estudante_id'] ?? ''] ?? '(removido)';
                $m['curso'] = $cur[$m['curso_id'] ?? ''] ?? '(removido)';
                $out[] = $m;
            }
            ctrl::responder(['status' => 200, 'dados' => $out]);
        });
    }

    // ---------- gravação ----------
    public function salvarEstudante()
    {
        $d = ctrl::getPost(false) ?: [];
        $est = $this->campos($d, self::ESTUDANTE);
        $end = $this->campos($d, self::ENDERECO, ['complemento']);
        if (!filter_var($est['email'], FILTER_VALIDATE_EMAIL)) $this->falha('E-mail inválido');
        $this->executar(function ($db) use ($d, $est, $end) {
            $id = (string) ($d['id'] ?? '');
            if ($id !== '') {
                $atual = $db->estudantes->findOne(['_id' => Mongo::id($id)]);
                if (!$atual) $this->falha('Estudante não encontrado', 404);
                $db->enderecos->updateOne(['_id' => $atual['endereco_id']], ['$set' => $end]);
                $db->estudantes->updateOne(['_id' => $atual['_id']], ['$set' => $est]);
            } else {
                if ($db->estudantes->countDocuments(['cpf' => $est['cpf']]) > 0) $this->falha('CPF já cadastrado');
                $est['endereco_id'] = $db->enderecos->insertOne($end)->getInsertedId();
                $db->estudantes->insertOne($est);
            }
            ctrl::responder(['status' => 200, 'retorno' => 'Estudante salvo']);
        });
    }

    public function salvarCurso()
    {
        $d = ctrl::getPost(false) ?: [];
        $c = $this->campos($d, self::CURSO);
        if (!ctype_digit($c['carga_horaria'])) $this->falha('Carga horária deve ser numérica');
        $c['carga_horaria'] = (int) $c['carga_horaria'];
        $this->executar(function ($db) use ($d, $c) {
            $id = (string) ($d['id'] ?? '');
            if ($id !== '') $db->cursos->updateOne(['_id' => Mongo::id($id)], ['$set' => $c]);
            else {
                if ($db->cursos->countDocuments(['codigo' => $c['codigo']]) > 0) $this->falha('Código de curso já existe');
                $db->cursos->insertOne($c);
            }
            ctrl::responder(['status' => 200, 'retorno' => 'Curso salvo']);
        });
    }

    public function matricular()
    {
        $d = ctrl::getPost(false) ?: [];
        $m = $this->campos($d, ['estudante_id', 'curso_id', 'data_matricula', 'status']);
        if (!in_array($m['status'], ['ativa', 'trancada', 'concluida', 'cancelada'], true)) $this->falha('Status inválido');
        $this->executar(function ($db) use ($m) {
            $e = Mongo::id($m['estudante_id']); $c = Mongo::id($m['curso_id']);
            if (!$db->estudantes->countDocuments(['_id' => $e]) || !$db->cursos->countDocuments(['_id' => $c])) $this->falha('Estudante ou curso inexistente');
            $filtro = ['estudante_id' => $e, 'curso_id' => $c];
            $dados = ['data_matricula' => $m['data_matricula'], 'status' => $m['status']];
            // upsert: o mesmo estudante não se matricula duas vezes no mesmo curso
            $db->matriculas->updateOne($filtro, ['$set' => $dados], ['upsert' => true]);
            ctrl::responder(['status' => 200, 'retorno' => 'Matrícula salva']);
        });
    }

    // ---------- exclusão ----------
    public function excluir()
    {
        $d = ctrl::getPost(false) ?: [];
        $tipo = (string) ($d['tipo'] ?? '');
        $this->executar(function ($db) use ($d, $tipo) {
            $id = Mongo::id((string) ($d['id'] ?? ''));
            if ($tipo === 'estudantes') {
                $e = $db->estudantes->findOne(['_id' => $id]);
                if ($e) $db->enderecos->deleteOne(['_id' => $e['endereco_id']]);
                $db->matriculas->deleteMany(['estudante_id' => $id]);
                $db->estudantes->deleteOne(['_id' => $id]);
            } elseif ($tipo === 'cursos') {
                $db->matriculas->deleteMany(['curso_id' => $id]);
                $db->cursos->deleteOne(['_id' => $id]);
            } elseif ($tipo === 'matriculas') {
                $db->matriculas->deleteOne(['_id' => $id]);
            } else $this->falha('Tipo inválido');
            ctrl::responder(['status' => 200, 'retorno' => 'Registro excluído']);
        });
    }
}
