<?php
declare(strict_types = 1);

namespace App\Model;

use App\Database\Database;
use App\Http\Request;

class User
{
    private int $userId;
    private int $requestUserId;
    private Database $db;

    public function __construct(Request $request, Database $db)
    {
        $this->requestUserId = $request->getUserId();
        $this->db = $db;

        $isUserExist = $this->db->execute('SELECT id FROM users WHERE telegram_id = ?', [$this->requestUserId]);

        if (empty($isUserExist)) {
            $this->createUser($request->getFirstName(), $request->getSecondName());
        }

        $this->userId = $this->setUserId();
    }

    public function createUser(string $firstName, string $secondName) : void
    {
        $query = 'INSERT INTO users (telegram_id, first_name, second_name) VALUES (?, ?, ?)';

        $this->db->execute($query, [$this->requestUserId, $firstName, $secondName]);
    }

    private function setUserId() : int
    {
        $user = $this->db->execute('SELECT id FROM users WHERE telegram_id = ?', [$this->requestUserId]);
        return $user[0]['id'];
    }

    public function addExpense(float $amount, int $categoryId, ?string $note) : void
    {
        $query = "INSERT INTO expenses (created_at, amount, user_id, category_id, note) VALUES (?, ?, ?, ?, ?)";

        $this->db->execute($query, [date('Y-m-d H:i:s'), $amount, $this->userId, $categoryId, $note]);
    }

    public function getMonthExpenses() : array
    {
        $query = "SELECT expenses.*, categories.category_name FROM expenses join categories on expenses.category_id = categories.id WHERE user_id = ? AND date_trunc('month', created_at) = date_trunc('month', NOW()::date) AND date_trunc('month', created_at) = date_trunc('month', NOW()::date) order by expenses.id asc";
        return $this->db->execute($query, [$this->userId]);
    }

    public function getDayExpenses() : array
    {
        $query = "SELECT expenses.*, categories.category_name FROM expenses join categories on expenses.category_id = categories.id WHERE user_id = ? AND DATE(created_at) = now()::date order by expenses.id asc";
        return $this->db->execute($query, [$this->userId]);
    }

    public function getPreviousMonthExpenses() : array
    {
        $query = "SELECT expenses.*, categories.category_name FROM expenses join categories on expenses.category_id = categories.id WHERE user_id = ? AND date_trunc('month', created_at) = date_trunc('month', NOW()::date - INTERVAL '1 MONTH') AND date_trunc('year', created_at) = date_trunc('year', NOW()::date - INTERVAL '1 MONTH') order by expenses.id asc";
        return $this->db->execute($query, [$this->userId]);
    }

    public function deleteExpense(int $expenseId) : void
    {
        $query = "DELETE FROM expenses WHERE id = ? AND user_id = ?";

        $this->db->execute($query, [$expenseId, $this->userId]);
    }

    public function getUserId() : int
    {
        return $this->userId;
    }
}

?>