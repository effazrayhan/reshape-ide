<?php
namespace App\Models;

use App\Config\Database;
use PDO;

class ProgressModel {
    private ?PDO $db;

    public function __construct() { $this->db = Database::getConnection(); }

    public function getByUserId(int $uid): array {
        if (!$this->db) return [];
        $s = $this->db->prepare('SELECT lesson_id, score, hints_used, completed_at FROM user_progress WHERE user_id = ? AND score > 0');
        $s->execute([$uid]);
        $r = [];
        while ($row = $s->fetch()) $r[$row['lesson_id']] = ['score' => (int)$row['score'], 'hints_used' => (int)$row['hints_used'], 'completed_at' => $row['completed_at']];
        return $r;
    }

    public function saveProgress(int $uid, int $lid, int $score, int $hints): bool {
        if (!$this->db) return false;
        try {
            $c = $this->db->prepare('SELECT id, score FROM user_progress WHERE user_id = ? AND lesson_id = ?');
            $c->execute([$uid, $lid]);
            if ($c->fetch()) {
                if ($score > $c->fetch()['score']) $this->db->prepare('UPDATE user_progress SET score=?, hints_used=?, completed_at=NOW() WHERE user_id=? AND lesson_id=?')->execute([$score, $hints, $uid, $lid]);
            } else {
                $this->db->prepare('INSERT INTO user_progress (user_id, lesson_id, score, hints_used, completed_at) VALUES (?, ?, ?, ?, NOW())')->execute([$uid, $lid, $score, $hints]);
            }
            return true;
        } catch (\PDOException $e) { error_log($e->getMessage()); return false; }
    }

    public function updateUserScore(int $uid): bool {
        if (!$this->db) return false;
        try {
            $s = $this->db->prepare('SELECT SUM(score) as ts, COUNT(*) as lc FROM user_progress WHERE user_id = ?');
            $s->execute([$uid]);
            $sc = $s->fetch();
            $this->db->prepare('INSERT INTO user_scores (user_id, total_score, lessons_completed, last_activity_at) VALUES (?, ?, ?, NOW()) ON DUPLICATE KEY UPDATE total_score=VALUES(total_score), lessons_completed=VALUES(lessons_completed), last_activity_at=VALUES(last_activity_at)')->execute([$uid, $sc['ts'] ?? 0, $sc['lc'] ?? 0]);
            return true;
        } catch (\PDOException $e) { error_log($e->getMessage()); return false; }
    }

    public function mergeAnonymousProgress(int $uid, string $anonId): int {
        if (!$this->db) return 0;
        try {
            $s = $this->db->prepare('SELECT lesson_id, score, hints_used, completed_at FROM user_progress WHERE user_id = ?');
            $s->execute([$anonId]);
            $m = 0;
            while ($p = $s->fetch()) {
                $c = $this->db->prepare('SELECT id, score FROM user_progress WHERE user_id = ? AND lesson_id = ?');
                $c->execute([$uid, $p['lesson_id']]);
                if ($c->fetch()) {
                    if ($p['score'] > $c->fetch()['score']) { $this->db->prepare('UPDATE user_progress SET score=?, hints_used=?, completed_at=? WHERE user_id=? AND lesson_id=?')->execute([$p['score'], $p['hints_used'], $p['completed_at'], $uid, $p['lesson_id']]); $m++; }
                } else {
                    $this->db->prepare('INSERT INTO user_progress (user_id, lesson_id, score, hints_used, completed_at) VALUES (?, ?, ?, ?, ?)')->execute([$uid, $p['lesson_id'], $p['score'], $p['hints_used'], $p['completed_at']]); $m++;
                }
            }
            if ($m > 0) $this->updateUserScore($uid);
            return $m;
        } catch (\PDOException $e) { error_log($e->getMessage()); return 0; }
    }

    public function getAllWithTitles(): array {
        if (!$this->db) return [];
        $p = $this->db->query('SELECT user_id, lesson_id, score, completed_at FROM user_progress WHERE score > 0')->fetchAll();
        $l = [];
        foreach ($this->db->query('SELECT id, title FROM lessons')->fetchAll() as $lt) $l[$lt['id']] = $lt['title'];
        $r = [];
        foreach ($p as $ro) { $u = $ro['user_id']; if (!isset($r[$u])) $r[$u] = []; $r[$u][] = ['lesson_id' => $ro['lesson_id'], 'lesson_title' => $l[$ro['lesson_id']] ?? 'Unknown', 'score' => $ro['score'], 'completed_at' => $ro['completed_at']]; }
        return $r;
    }
}
