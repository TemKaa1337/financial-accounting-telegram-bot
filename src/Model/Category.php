<?php declare(strict_types = 1);

namespace App\Model;

use App\Exception\CategoryAlreadyExistException;
use App\Exception\NoSuchCategoryException;
use App\Database\Database;
use App\Messages\ErrorMessage;
use App\Model\User;

class Category
{
    private readonly int $categoryId;

    public function __construct(
        private readonly Database $db,
        private readonly User $user,
        private readonly string $categoryName
    )
    {
        $this->setCategoryInfo();
    }

    private function setCategoryInfo(): void
    {
        $categoryInfo = $this->db->execute('SELECT id, user_id FROM categories WHERE category_name = ? and user_id = ?', [$this->categoryName, $this->user->getDatabaseUserId()]);
        if (isset($categoryInfo['id'])) {
            $this->categoryId = $categoryInfo['id'];
        }
    }

    private function checkIfCategoryExists(): void
    {
        if (!isset($this->categoryId)) {
            throw new NoSuchCategoryException(ErrorMessage::UnknownCategory->value);
        }
    }

    private function checkIfCategoryDoesntExist(): void
    {
        if (isset($this->categoryId)) {
            throw new CategoryAlreadyExistException(ErrorMessage::CategoryAlreadyExist->value);
        }
    }
    
    public function getCategoryId(): int
    {
        $this->checkIfCategoryExists();
        return $this->categoryId;
    }

    public function add(): void
    {
        $this->checkIfCategoryDoesntExist();
        $query = 'INSERT INTO categories (category_name, user_id) VALUES (?, ?)';
        $this->db->execute($query, [$this->categoryName, $this->user->getDatabaseUserId()]);
        $this->setCategoryInfo();
        
        $query = 'INSERT INTO category_aliases(category_id, alias) VALUES (?, ?)';
        $this->db->execute($query, [$this->categoryId, $this->categoryName]);
    }

    public function delete(): void
    {
        $this->checkIfCategoryExists();
        $this->db->execute('DELETE FROM categories WHERE id = ?', [$this->categoryId]);
        $this->db->execute('DELETE FROM category_aliases WHERE category_id = ?', [$this->categoryId]);
    }

    public function addAlias(string $alias): void
    {
        $this->checkIfCategoryExists();
        $alias = new CategoryAlias(db: $this->db, category: $this, alias: $alias);
        $alias->add();
    }

    public function getAliases(): array
    {
        $this->checkIfCategoryExists();
        $alias = new CategoryAlias(db: $this->db, category: $this);
        return $alias->getAliases();
    }
}

?>