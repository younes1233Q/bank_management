<?php
class Transaction {
    private $conn;
    private $table_name = "transactions";

    public $id;
    public $account_id;
    public $type;
    public $amount;
    public $description;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . "
                SET
                    account_id = :account_id,
                    type = :type,
                    amount = :amount,
                    description = :description,
                    created_at = :created_at";

        $stmt = $this->conn->prepare($query);

        $this->created_at = date('Y-m-d H:i:s');

        $stmt->bindParam(":account_id", $this->account_id);
        $stmt->bindParam(":type", $this->type);
        $stmt->bindParam(":amount", $this->amount);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":created_at", $this->created_at);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function getTransactionsByAccountId($account_id) {
        $query = "SELECT * FROM " . $this->table_name . "
                WHERE account_id = :account_id
                ORDER BY created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":account_id", $account_id);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function transfer($from_account_id, $to_account_id, $amount) {
        try {
            $this->conn->beginTransaction();

            // Deduct from source account
            $this->account_id = $from_account_id;
            $this->type = 'transfer_out';
            $this->amount = -$amount;
            $this->description = "Transfer to account " . $to_account_id;
            $this->create();
            // Update source account balance
            $stmt1 = $this->conn->prepare("UPDATE accounts SET balance = balance - :amount WHERE id = :id");
            $stmt1->bindParam(":amount", $amount);
            $stmt1->bindParam(":id", $from_account_id);
            $stmt1->execute();

            // Add to destination account
            $this->account_id = $to_account_id;
            $this->type = 'transfer_in';
            $this->amount = $amount;
            $this->description = "Transfer from account " . $from_account_id;
            $this->create();
            // Update destination account balance
            $stmt2 = $this->conn->prepare("UPDATE accounts SET balance = balance + :amount WHERE id = :id");
            $stmt2->bindParam(":amount", $amount);
            $stmt2->bindParam(":id", $to_account_id);
            $stmt2->execute();

            $this->conn->commit();
            return true;
        } catch(Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }

    public function getRecentTransactionsByUserId($user_id, $limit = 5) {
        $query = "SELECT t.* FROM " . $this->table_name . " t
                  JOIN accounts a ON t.account_id = a.id
                  WHERE a.user_id = :user_id
                  ORDER BY t.created_at DESC
                  LIMIT :limit";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
        $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function deleteAccount($account_id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $account_id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
?> 