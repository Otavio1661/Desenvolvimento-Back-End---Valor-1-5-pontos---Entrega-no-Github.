<?php
namespace App;

use MongoDB\Client;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;

/** Conexão MongoDB (MONGO_URI / MONGO_DB do .env) e conversões de documentos. */
final class Mongo
{
    public static function banco(): \MongoDB\Database
    {
        static $db;
        if ($db === null) {
            if (!extension_loaded('mongodb')) throw new \RuntimeException('Extensão PHP mongodb não instalada.');
            $uri = defined('MONGO_URI') ? (string) MONGO_URI : '';
            $nome = defined('MONGO_DB') ? (string) MONGO_DB : '';
            if ($uri === '' || $nome === '') throw new \RuntimeException('Configure MONGO_URI e MONGO_DB no .env.');
            $db = (new Client($uri, ['serverSelectionTimeoutMS' => 4000]))->selectDatabase($nome);
        }
        return $db;
    }

    public static function id(?string $id): ObjectId
    {
        if ($id === null || !preg_match('/^[a-f0-9]{24}$/i', $id)) throw new \InvalidArgumentException('ID inválido.');
        return new ObjectId($id);
    }

    /** Documento BSON → array simples (ObjectId vira string, data vira texto). */
    public static function paraArray($doc): array
    {
        $out = [];
        foreach ((array) $doc as $k => $v) {
            if ($v instanceof ObjectId) $v = (string) $v;
            elseif ($v instanceof UTCDateTime) $v = $v->toDateTime()->format('Y-m-d');
            elseif (is_object($v) || is_array($v)) $v = self::paraArray($v);
            $out[$k] = $v;
        }
        return $out;
    }
}
