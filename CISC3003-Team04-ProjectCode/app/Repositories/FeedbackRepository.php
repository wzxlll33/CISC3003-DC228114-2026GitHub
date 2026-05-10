<?php

namespace App\Repositories;

use App\Core\Database;

class FeedbackRepository
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function create(array $data): int
    {
        $this->ensureTable();

        return (int) $this->db->insert('feedback_reports', [
            'user_id' => $data['user_id'] ?? null,
            'restaurant_id' => $data['restaurant_id'] ?? null,
            'food_id' => $data['food_id'] ?? null,
            'context_type' => $data['context_type'] ?? 'general',
            'issue_type' => $data['issue_type'],
            'message' => $data['message'],
            'contact_email' => $data['contact_email'] ?? null,
            'page_url' => $data['page_url'] ?? null,
            'user_agent' => $data['user_agent'] ?? null,
        ]);
    }

    public function getAll(): array
    {
        $this->ensureTable();

        return $this->db->fetchAll(
            "SELECT
                feedback_reports.*,
                users.username,
                restaurants.name_zh AS restaurant_name_zh,
                restaurants.name_en AS restaurant_name_en,
                foods.name_zh AS food_name_zh,
                foods.name_en AS food_name_en
             FROM feedback_reports
             LEFT JOIN users ON users.id = feedback_reports.user_id
             LEFT JOIN restaurants ON restaurants.id = feedback_reports.restaurant_id
             LEFT JOIN foods ON foods.id = feedback_reports.food_id
             ORDER BY
                CASE feedback_reports.status
                    WHEN 'new' THEN 0
                    WHEN 'reviewing' THEN 1
                    WHEN 'resolved' THEN 2
                    ELSE 3
                END,
                feedback_reports.created_at DESC,
                feedback_reports.id DESC"
        );
    }

    public function updateStatus(int $id, string $status): bool
    {
        $this->ensureTable();

        return $this->db->update(
            'feedback_reports',
            [
                'status' => $status,
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            'id = :id',
            [':id' => $id]
        ) > 0;
    }

    public function countByStatus(): array
    {
        $this->ensureTable();
        $rows = $this->db->fetchAll(
            'SELECT status, COUNT(*) AS aggregate
             FROM feedback_reports
             GROUP BY status'
        );
        $counts = [
            'new' => 0,
            'reviewing' => 0,
            'resolved' => 0,
        ];

        foreach ($rows as $row) {
            $counts[(string) ($row['status'] ?? 'new')] = (int) ($row['aggregate'] ?? 0);
        }

        return $counts;
    }

    private function ensureTable(): void
    {
        $this->db->query(
            "CREATE TABLE IF NOT EXISTS feedback_reports (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER DEFAULT NULL,
                restaurant_id INTEGER DEFAULT NULL,
                food_id INTEGER DEFAULT NULL,
                context_type VARCHAR(30) NOT NULL DEFAULT 'general',
                issue_type VARCHAR(30) NOT NULL,
                message TEXT NOT NULL,
                contact_email VARCHAR(255) DEFAULT NULL,
                page_url VARCHAR(500) DEFAULT NULL,
                user_agent VARCHAR(500) DEFAULT NULL,
                status VARCHAR(20) NOT NULL DEFAULT 'new',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
                FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE SET NULL,
                FOREIGN KEY (food_id) REFERENCES foods(id) ON DELETE SET NULL
            )"
        );
        $this->db->query('CREATE INDEX IF NOT EXISTS idx_feedback_reports_status ON feedback_reports(status)');
        $this->db->query('CREATE INDEX IF NOT EXISTS idx_feedback_reports_restaurant ON feedback_reports(restaurant_id)');
    }
}
