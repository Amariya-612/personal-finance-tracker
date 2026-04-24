<?php
/**
 * File: controllers/BudgetController.php
 * Purpose: Business logic for budget management
 */

require_once __DIR__ . '/../models/Budget.php';
require_once __DIR__ . '/../models/Category.php';
require_once __DIR__ . '/../utils/validator.php';
require_once __DIR__ . '/../utils/functions.php';

class BudgetController
{
    private Budget   $budgetModel;
    private Category $catModel;

    public function __construct(PDO $pdo)
    {
        $this->budgetModel = new Budget($pdo);
        $this->catModel    = new Category($pdo);
    }

    /** Create a new budget entry. */
    public function store(int $userId, array $post): array
    {
        if (!verifyCsrf($post['csrf_token'] ?? '')) {
            return ['success' => false, 'errors' => ['general' => 'Invalid request.']];
        }

        $errors = $this->validate($post);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        $month = (int)$post['month'];
        $year  = (int)$post['year'];
        $catId = (int)$post['category_id'];

        // Verify category
        $cat = $this->catModel->findById($catId, $userId);
        if (!$cat || $cat['type'] !== 'expense') {
            return ['success' => false, 'errors' => ['category_id' => 'Please select a valid expense category.']];
        }

        // Prevent duplicate
        if ($this->budgetModel->exists($userId, $catId, $month, $year)) {
            return ['success' => false, 'errors' => ['category_id' => 'A budget for this category and month already exists.']];
        }

        $id = $this->budgetModel->create($userId, $catId, (float)$post['amount'], $month, $year);
        return ['success' => true, 'id' => $id];
    }

    /** Update an existing budget. */
    public function update(int $id, int $userId, array $post): array
    {
        if (!verifyCsrf($post['csrf_token'] ?? '')) {
            return ['success' => false, 'errors' => ['general' => 'Invalid request.']];
        }

        $existing = $this->budgetModel->findById($id, $userId);
        if (!$existing) {
            return ['success' => false, 'errors' => ['general' => 'Budget not found.']];
        }

        $v = new Validator($post);
        $v->required('amount', 'Amount')
          ->numeric('amount',  'Amount')
          ->positive('amount', 'Amount');

        if ($v->fails()) {
            return ['success' => false, 'errors' => $v->errors()];
        }

        $this->budgetModel->update($id, $userId, (float)$post['amount']);
        return ['success' => true];
    }

    /** Delete a budget. */
    public function destroy(int $id, int $userId, string $csrfToken): array
    {
        if (!verifyCsrf($csrfToken)) {
            return ['success' => false, 'message' => 'Invalid request.'];
        }

        $existing = $this->budgetModel->findById($id, $userId);
        if (!$existing) {
            return ['success' => false, 'message' => 'Budget not found.'];
        }

        $this->budgetModel->delete($id, $userId);
        return ['success' => true, 'message' => 'Budget deleted.'];
    }

    // ── Private helpers ───────────────────────────────────

    private function validate(array $post): array
    {
        $v = new Validator($post);
        $v->required('category_id', 'Category')
          ->numeric('category_id',  'Category')
          ->required('amount',      'Amount')
          ->numeric('amount',       'Amount')
          ->positive('amount',      'Amount')
          ->required('month',       'Month')
          ->inList('month', array_map('strval', range(1, 12)), 'Month')
          ->required('year',        'Year')
          ->numeric('year',         'Year');

        return $v->errors();
    }
}
