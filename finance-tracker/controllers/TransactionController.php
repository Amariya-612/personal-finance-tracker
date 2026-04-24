<?php
/**
 * File: controllers/TransactionController.php
 * Purpose: Business logic for transaction CRUD operations
 */

require_once __DIR__ . '/../models/Transaction.php';
require_once __DIR__ . '/../models/Category.php';
require_once __DIR__ . '/../utils/validator.php';
require_once __DIR__ . '/../utils/functions.php';

class TransactionController
{
    private Transaction $txnModel;
    private Category    $catModel;

    public function __construct(PDO $pdo)
    {
        $this->txnModel = new Transaction($pdo);
        $this->catModel = new Category($pdo);
    }

    /** Validate and create a transaction. */
    public function store(int $userId, array $post): array
    {
        if (!verifyCsrf($post['csrf_token'] ?? '')) {
            return ['success' => false, 'errors' => ['general' => 'Invalid request.']];
        }

        $errors = $this->validate($post);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        // Verify category belongs to user
        $cat = $this->catModel->findById((int)$post['category_id'], $userId);
        if (!$cat) {
            return ['success' => false, 'errors' => ['category_id' => 'Invalid category.']];
        }

        $id = $this->txnModel->create(
            $userId,
            (int)$post['category_id'],
            $post['type'],
            (float)$post['amount'],
            $post['description'] ?? '',
            $post['date']
        );

        return ['success' => true, 'id' => $id];
    }

    /** Validate and update a transaction. */
    public function update(int $id, int $userId, array $post): array
    {
        if (!verifyCsrf($post['csrf_token'] ?? '')) {
            return ['success' => false, 'errors' => ['general' => 'Invalid request.']];
        }

        $existing = $this->txnModel->findById($id, $userId);
        if (!$existing) {
            return ['success' => false, 'errors' => ['general' => 'Transaction not found.']];
        }

        $errors = $this->validate($post);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        $cat = $this->catModel->findById((int)$post['category_id'], $userId);
        if (!$cat) {
            return ['success' => false, 'errors' => ['category_id' => 'Invalid category.']];
        }

        $this->txnModel->update(
            $id, $userId,
            (int)$post['category_id'],
            $post['type'],
            (float)$post['amount'],
            $post['description'] ?? '',
            $post['date']
        );

        return ['success' => true];
    }

    /** Delete a transaction. */
    public function destroy(int $id, int $userId, string $csrfToken): array
    {
        if (!verifyCsrf($csrfToken)) {
            return ['success' => false, 'message' => 'Invalid request.'];
        }

        $existing = $this->txnModel->findById($id, $userId);
        if (!$existing) {
            return ['success' => false, 'message' => 'Transaction not found.'];
        }

        $this->txnModel->delete($id, $userId);
        return ['success' => true, 'message' => 'Transaction deleted.'];
    }

    // ── Private helpers ───────────────────────────────────

    private function validate(array $post): array
    {
        $v = new Validator($post);
        $v->required('type',        'Type')
          ->inList('type', ['income', 'expense'], 'Type')
          ->required('category_id', 'Category')
          ->numeric('category_id',  'Category')
          ->required('amount',      'Amount')
          ->numeric('amount',       'Amount')
          ->positive('amount',      'Amount')
          ->required('date',        'Date')
          ->date('date',            'Y-m-d', 'Date')
          ->maxLength('description', 255, 'Description');

        return $v->errors();
    }
}
