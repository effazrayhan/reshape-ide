<?php
namespace App\Models;

use App\Config\Database;
use PDO;

class UserModel {
    private ?PDO $db;

    public function __construct() { $this->db = Database::getConnection(); }

    public function findByEmail(string $email): ?array {
        if (!$this->db) return null;
        $s = $this->db->prepare('SELECT * FROM users WHERE email = ?');
        $s->execute([$email]);
        return $s->fetch() ?: null;
    }

    public function findByUsername(string $username): ?array {
        if (!$this->db) return null;
        $s = $this->db->prepare('SELECT * FROM users WHERE username = ?');
        $s->execute([$username]);
        return $s->fetch() ?: null;
    }

    public function findById(int $id): ?array {
        if (!$this->db) return null;
        $s = $this->db->prepare('SELECT id, email, username, created_at FROM users WHERE id = ?');
        $s->execute([$id]);
        return $s->fetch() ?: null;
    }

    public function create(string $email, string $username, string $hash): ?int {
        if (!$this->db) return null;
        try {
            $s = $this->db->prepare('INSERT INTO users (email, username, password_hash) VALUES (?, ?, ?)');
            $s->execute([$email, $username, $hash]);
            return (int)$this->db->lastInsertId();
        } catch (\PDOException $e) { error_log($e->getMessage()); return null; }
    }

    public function verifyPassword(string $password, string $hash): bool { return password_verify($password, $hash); }

    public function getScore(int $userId): ?array {
        if (!$this->db) return null;
        $s = $this->db->prepare('SELECT total_score, lessons_completed, last_activity_at FROM user_scores WHERE user_id = ?');
        $s->execute([$userId]);
        return $s->fetch() ?: null;
    }

    public function getAllWithScores(): array {
        if (!$this->db) return [];
        return $this->db->query("
            SELECT u.id, u.email, u.username, u.created_at, COALESCE(us.total_score, 0) as total_score, COALESCE(us.lessons_completed, 0) as lessons_completed, us.last_activity_at
            FROM users u LEFT JOIN user_scores us ON u.id = us.user_id ORDER BY us.total_score DESC, u.created_at DESC
        ")->fetchAll();
    }
}
