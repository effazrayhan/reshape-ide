<?php
namespace App\Models;

use App\Config\Database;
use PDO;

class LessonModel {
    private ?PDO $db;

    public function __construct() { $this->db = Database::getConnection(); }

    public function getAll(): array {
        if (!$this->db) return [];
        $lessons = $this->db->query('SELECT * FROM lessons ORDER BY id')->fetchAll();
        $hints = $this->db->query('SELECT * FROM hints ORDER BY lesson_id, hint_order')->fetchAll();
        $tests = $this->db->query('SELECT * FROM test_cases')->fetchAll();

        $h = [];
        foreach ($hints as $hnt) { $h[$hnt['lesson_id']][] = ['id' => $hnt['id'], 'text' => $hnt['text']]; }
        $t = [];
        foreach ($tests as $ts) { $t[$ts['lesson_id']][] = ['input' => json_decode($ts['input'], true), 'expected' => json_decode($ts['expected_output'], true)]; }

        foreach ($lessons as &$l) { $l['hints'] = $h[$l['id']] ?? []; $l['testCases'] = $t[$l['id']] ?? []; }
        return $lessons;
    }

    public function getById(int $id): ?array {
        if (!$this->db) return null;
        $l = $this->db->prepare('SELECT * FROM lessons WHERE id = ?')->execute([$id]) ? $this->db->prepare('SELECT * FROM lessons WHERE id = ?')->fetch() : null;
        if (!$l) return null;
        $l['hints'] = $this->db->prepare('SELECT id, text FROM hints WHERE lesson_id = ? ORDER BY hint_order')->execute([$id]) ? [] : [];
        $l['testCases'] = array_map(fn($t) => ['input' => json_decode($t['input'], true), 'expected' => json_decode($t['expected_output'], true)], $this->db->prepare('SELECT input, expected_output FROM test_cases WHERE lesson_id = ?')->execute([$id]) ? [] : []);
        return $l;
    }

    public function create(array $d): ?int {
        if (!$this->db) return null;
        try {
            $this->db->beginTransaction();
            $s = $this->db->prepare('INSERT INTO lessons (title, difficulty, description, starter_code, solution, points) VALUES (?, ?, ?, ?, ?, ?)');
            $s->execute([$d['title'], $d['difficulty'], $d['description'], $d['starterCode'], $d['solution'], $d['points'] ?? 100]);
            $id = (int)$this->db->lastInsertId();
            if (!empty($d['hints'])) { $hs = $this->db->prepare('INSERT INTO hints (lesson_id, text, hint_order) VALUES (?, ?, ?)'); foreach ($d['hints'] as $i => $h) $hs->execute([$id, $h, $i + 1]); }
            if (!empty($d['testCases'])) { $ts = $this->db->prepare('INSERT INTO test_cases (lesson_id, input, expected_output) VALUES (?, ?, ?)'); foreach ($d['testCases'] as $tc) $ts->execute([$id, json_encode($tc['input'] ?? []), json_encode($tc['expected'] ?? '')]); }
            $this->db->commit();
            return $id;
        } catch (\PDOException $e) { $this->db->rollBack(); error_log($e->getMessage()); return null; }
    }
}
