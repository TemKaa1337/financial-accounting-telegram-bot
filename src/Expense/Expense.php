<?php
declare(strict_types = 1);

namespace App\Expense;

use App\Exception\InvalidInputException;
use App\Categories\Categories;
use App\Database\Database;
use App\Http\Request;
use App\Model\User;
use Exception;

class Expense
{
    private User $user;
    private Database $db;
    private string|float $amount;
    private int $categoryId;

    public function __construct(User $user, Database $db, int $categoryId, string|float $message)
    {
        $this->db = $db;
        $this->user = $user;
        $this->categoryId = $categoryId;
        $this->amount = $message;
    }

    public function addExpense() : string
    {
        $note = $this->getNote($this->amount);
        $this->amount = $this->getAmount($this->amount);
        $this->user->addExpense($this->amount, $this->categoryId, $note);

        return 'Новая трата добавлена успешно!';
    }

    public function getMonthExpenses() : string
    {
        $expenses = $this->user->getMonthExpenses();

        if (empty($expenses)) return 'В этом месяце еще не было трат!';

        $result = [];
        $totalSumm = 0;

        foreach ($expenses as $expense) {
            $result[] = date('d.m.Y H:i:s', strtotime($expense['created_at']))." (/delete{$expense['id']}) - {$expense['amount']}р, {$expense['category_name']}".$this->getNoteForOutput($expense['note']);

            $totalSumm += $expense['amount'];
        }

        $avg = $totalSumm / (int)date('d');
        $result[] = "Итого {$avg}р. в среднем за день";
        $result[] = "Итого {$totalSumm}р.";

        return implode(PHP_EOL, $result);
    }

    public function getDayExpenses() : string
    {
        $expenses = $this->user->getDayExpenses();

        if (empty($expenses)) return 'На сегодня трат нет!';

        $result = [];
        $totalSumm = 0;

        foreach ($expenses as $expense) {
            $result[] = date('H:i:s', strtotime($expense['created_at']))." (/delete{$expense['id']}) - {$expense['amount']}р, {$expense['category_name']}".$this->getNoteForOutput($expense['note']);

            $totalSumm += $expense['amount'];
        }

        $result[] = "Итого {$totalSumm}р.";

        return implode(PHP_EOL, $result);
    }

    public function getPreviousMonthExpenses() : string
    {
        $expenses = $this->user->getPreviousMonthExpenses();
        
        if (empty($expenses)) return 'В прошлом месяце не было трат!';

        $result = [];
        $totalSumm = 0;

        foreach ($expenses as $expense) {
            $result[] = date('d.m.Y H:i:s', strtotime($expense['created_at']))." (/delete{$expense['id']}) - {$expense['amount']}р, {$expense['category_name']}".$this->getNoteForOutput($expense['note']);

            $totalSumm += $expense['amount'];
        }

        $result[] = "Итого {$totalSumm}р.";

        return implode(PHP_EOL, $result);
    }

    public function deleteExpense(int $expenseId) : string
    {
        $this->user->deleteExpense($expenseId);

        return 'Трата успешно удалена!';
    }

    public function getAmount(string $message) : float
    {
        if (strpos($message, ' ') !== false) {
            $message = explode(' ', $message);
            
            if (is_numeric($message[0])) return floatval($message[0]);
            else throw new InvalidInputException('Неправильный формат суммы.');
        } else throw new InvalidInputException('Неправильный формат сообщения.');
    }

    public function getNote(string $message) : ?string
    {
        if (strpos($message, ' ') !== false) {
            $message = explode(' ', $message);
            
            if (count($message) > 2) {
                return implode(' ', array_slice($message, 2, count($message) - 2));
            } else return null;
        } else throw new InvalidInputException('Неправильный формат сообщения.');
    }

    public function getNoteForOutput(?string $note) : string
    {
        if ($note === null) return '';

        return ", {$note}.";
    }

    public function isUserAllowedToDeleteExpense(int $expenseId) : bool
    {
        $isAllowed = $this->db->execute('SELECT user_id FROM expenses WHERE id = ?', [$expenseId]);

        if (empty($isAllowed)) return false;

        return $isAllowed[0]['user_id'] === $this->user->getUserId();
    }
}

?>