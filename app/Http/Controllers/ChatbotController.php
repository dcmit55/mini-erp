<?php

namespace App\Http\Controllers;

use App\Services\LlmService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ChatbotController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function ask(Request $request, LlmService $llm)
    {
        return $this->handleChat($request, $llm);
    }

    public function message(Request $request, LlmService $llm)
    {
        return $this->handleChat($request, $llm);
    }

    protected function handleChat(Request $request, LlmService $llm)
    {
        $request->validate(['message' => 'required|string|max:1000']);

        $message = $request->input('message');

        // Step 1 — ask LLM if it needs a DB query
        $sql = $this->generateSql($message, $llm);

        // Step 2 — execute the SQL if one was generated (SELECT only)
        $dbContext = '';
        if ($sql) {
            $dbContext = $this->executeSql($sql);
        }

        // Step 3 — final answer with optional DB context
        $system = "You are a helpful assistant for a company ERP system called Mini-ERP (PT Symcore). "
            . "The system covers: Inventory, HR (employees, attendance, leave, overtime), Finance (project costing), Procurement, and Production. "
            . "Answer clearly and concisely. Respond in the same language the user writes in.";

        if ($dbContext) {
            $system .= "\n\nThe following data was retrieved from the live database to answer the user's question:\n\n"
                     . $dbContext
                     . "\n\nUse this data to give an accurate answer. "
                     . "If the data is empty or not found, say so honestly.";
        }

        try {
            $reply = $llm->chat($message, $system);
            return response()->json(['reply' => $reply]);
        } catch (\Exception $e) {
            return response()->json([
                'reply' => 'Sorry, the AI assistant is currently unavailable. Please try again later.'
            ], 500);
        }
    }

    /**
     * Ask the LLM to produce a SQL SELECT query for the user's question.
     * Returns the SQL string, or null if no query is needed.
     */
    protected function generateSql(string $message, LlmService $llm): ?string
    {
        $schema = $this->getSchema();

        $prompt = <<<PROMPT
You are a SQL query generator for a MySQL database.

Given the database schema below and the user's question, decide:
- If the question requires database data → output ONLY a safe SQL SELECT query (no explanation, no markdown, no code block)
- If the question does NOT require database data (e.g. general questions, greetings) → output exactly: NO_QUERY

Rules:
- Only SELECT statements are allowed. Never INSERT, UPDATE, DELETE, DROP, etc.
- Use LIMIT 20 maximum
- Use LIKE for name/text searches (case-insensitive)
- Only use tables and columns from the schema below

DATABASE SCHEMA:
{$schema}

User question: {$message}
PROMPT;

        try {
            $raw = $llm->chat($prompt);
            $raw = trim($raw);

            // Strip markdown code fences if LLM wraps it
            $raw = preg_replace('/^```(?:sql)?\s*/i', '', $raw);
            $raw = preg_replace('/\s*```$/', '', $raw);
            $raw = trim($raw);

            if (stripos($raw, 'NO_QUERY') !== false || empty($raw)) {
                return null;
            }

            // Safety: must start with SELECT
            if (!preg_match('/^\s*SELECT\b/i', $raw)) {
                return null;
            }

            return $raw;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Execute a SELECT query safely and return results as formatted string.
     */
    protected function executeSql(string $sql): string
    {
        try {
            $rows = DB::select($sql);

            if (empty($rows)) {
                return "Query returned no results.";
            }

            // Format as plain text table
            $rows  = array_map(fn($r) => (array) $r, $rows);
            $keys  = array_keys($rows[0]);
            $lines = [implode(' | ', $keys)];
            $lines[] = str_repeat('-', 60);
            foreach ($rows as $row) {
                $lines[] = implode(' | ', array_map(fn($v) => $v ?? 'NULL', array_values($row)));
            }

            return implode("\n", $lines);
        } catch (\Exception $e) {
            return "DB query error: " . $e->getMessage();
        }
    }

    /**
     * Compact schema description for the LLM to understand the database.
     */
    protected function getSchema(): string
    {
        return <<<SCHEMA
TABLE: inventories
  id, name (product/material name), material_code, unit, category_id, deleted_at

TABLE: inventory_batches
  id, inventory_id (FK→inventories.id), batch_number, qty, qty_remaining (current stock), unit_price, received_date, deleted_at

TABLE: categories
  id, name

TABLE: employees
  id, name, employee_no, department_id, status (active/terminated), employment_type, gender, citizenship (WNI/WNA), contract_end_date, deleted_at

TABLE: departments
  id, name

TABLE: daily_attendances
  id, employee_id (FK→employees.id), date, status (Present/Late/Alpha/Permission/Early Leave), clock_in, clock_out

TABLE: leave_requests
  id, employee_id (FK→employees.id), type, start_date, end_date, approval_1 (pending/approved/rejected), approval_2 (pending/approved/rejected), created_at

TABLE: overtime_requests
  id, employee_id (FK→employees.id), start_time, end_time, net_hours, hr_approval_status (pending/approved/rejected), ot_code, created_at

TABLE: goods_in
  id, inventory_id (FK→inventories.id), qty, unit_price, received_date, po_number, created_at

TABLE: goods_out
  id, inventory_id (FK→inventories.id), qty, purpose, issued_date, created_at

TABLE: projects
  id, name, project_code, status, client, start_date, end_date

TABLE: purchase_requests
  id, project_id, status (draft/pending/approved/rejected), requested_by, created_at

TABLE: job_orders
  id, project_id, name, status, department_id, deadline, created_at

TABLE: suppliers
  id, name, contact, email

TABLE: units
  id, name (e.g. meter, pcs, kg)
SCHEMA;
    }
}
